<?php

/**
 * Self-hosted plugin updater via GitHub Releases.
 *
 * Hooks into WordPress's update system to check the latest GitHub release and
 * surface it as a standard plugin update — no wordpress.org required.
 *
 * Inspired by Misha Rudrastyh and Austin Ginder
 *
 * @see     https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
 * @see     https://anchor.host/using-github-to-self-host-updates-for-wordpress-plugins/
 *
 * @since   1.0.3
 * @author  QuickBrownFox90
 */

class BackupPilot_Updater {

    private string $github_repo;
    private string $plugin_file;
    private string $plugin_slug;
    private string $plugin_basename;
    private string $cache_key;
    private int    $cache_ttl = DAY_IN_SECONDS;


    /**
     * @since   1.0.3
     *
     * @param  string  $github_repo  GitHub repository in "owner/repo" format, e.g. "QuickBrownFox90/backup-pilot".
     * @param  string  $plugin_file  Absolute path to the plugin's main file (typically __FILE__ from backuppilot.php).
     */

    public function __construct( string $github_repo, string $plugin_file ) {

        $this->github_repo     = $github_repo;
        $this->plugin_file     = $plugin_file;
        $this->plugin_basename = plugin_basename( $plugin_file );
        $this->plugin_slug     = dirname( $this->plugin_basename );
        $this->cache_key       = 'backuppilot_updater_' . md5( $github_repo );
    }


    /**
     * Registers all WordPress hooks required for the update flow.
     *
     * Must be called once after instantiation. Safe to call on every request —
     * the actual GitHub API call is deferred and cached.
     *
     * @since   1.0.3
     * @since   1.0.5  Added "Check for updates" actions and filter
     *
     * @return  void
     */

    public function init() : void {

        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
        add_filter( 'site_transient_update_plugins', [ $this, 'check_update' ] );
        add_action( 'upgrader_process_complete', [ $this, 'purge_cache' ], 10, 2 );
        add_filter( 'plugin_action_links_' . $this->plugin_basename, [ $this, 'action_links' ] );
        add_action( 'admin_post_backuppilot_check_updates', [ $this, 'handle_cache_bust' ] );
        add_action( 'admin_notices', [ $this, 'update_check_notice' ] );
    }


    /**
     * Fetches the latest release metadata from the GitHub API.
     *
     * Results are cached in a transient for {@see self::$cache_ttl} seconds (default: 24h)
     * to stay well within GitHub's unauthenticated rate limit of 60 requests/hour.
     *
     * When a release has an attached ZIP asset it is preferred over GitHub's
     * auto-generated zipball, since the attached asset has the correct plugin
     * folder structure expected by the WordPress upgrader.
     *
     * @since   1.0.3
     *
     * @return  object|false  Release object with version, download_url, changelog and published_at properties, or false on failure.
     */

    private function get_release_info() : object|false {

        $cached = get_transient( $this->cache_key );

        if ( $cached !== false ) {
            return $cached;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . $this->github_repo . '/releases/latest',
            [
                'timeout' => 10,
                'headers' => [
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => BackupPilot::get_plugin_data( 'user-agent' ),
                ],
            ]
        );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( empty( $data->tag_name ) ) {
            return false;
        }

        // Prefer an explicitly attached ZIP asset over the auto-generated zipball
        $download_url = '';

        if ( ! empty( $data->assets ) ) {

            foreach ( $data->assets as $asset ) {

                if ( str_ends_with( $asset->name, '.zip' ) ) {
                    $download_url = $asset->browser_download_url;
                    break;
                }
            }
        }

        if ( empty( $download_url ) ) {
            $download_url = $data->zipball_url;
        }

        $release = (object) [
            'version'      => ltrim( $data->tag_name, 'v' ),
            'download_url' => $download_url,
            'changelog'    => $data->body ?? '',
            'published_at' => $data->published_at ?? '',
        ];

        set_transient( $this->cache_key, $release, $this->cache_ttl );

        return $release;
    }


    /**
     * Populates the plugin detail modal ("View Details") in the plugin list table.
     *
     * Hooked into {@see 'plugins_api'} at priority 20. Returns early for any slug
     * that does not match this plugin so other plugins' update checks are unaffected.
     *
     * @since   1.0.3
     *
     * @param   mixed   $result  Default result passed through the filter chain.
     * @param   string  $action  The API action being performed (e.g. "plugin_information").
     * @param   object  $args    Request arguments; $args->slug identifies the plugin.
     *
     * @return  mixed            Populated stdClass with plugin metadata, or the original $result when the request does not concern this plugin or the API call fails.
     */

    public function plugin_info( mixed $result, string $action, object $args ): mixed {

        if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== $this->plugin_slug ) {
            return $result;
        }

        $release = $this->get_release_info();

        if ( ! $release ) {
            return $result;
        }

        $plugin_data = get_plugin_data( $this->plugin_file );

        $info                = new stdClass();
        $info->name          = $plugin_data['Name'];
        $info->slug          = $this->plugin_slug;
        $info->version       = $release->version;
        $info->author        = $plugin_data['AuthorName'];
        $info->homepage      = $plugin_data['PluginURI'];
        $info->requires      = '6.0';
        $info->requires_php  = '8.1';
        $info->last_updated  = $release->published_at;
        $info->download_link = $release->download_url;
        $info->sections      = [
            'changelog' => nl2br( esc_html( $release->changelog ) ),
        ];

        return $info;
    }


    /**
     * Injects an available update into WordPress's plugin update transient.
     *
     * Hooked into {@see 'site_transient_update_plugins'}. Compares the latest
     * GitHub release version against the currently installed version and adds
     * an update entry when a newer version is available, triggering the standard
     * WordPress update badge and "Update now" flow.
     *
     * @since   1.0.3
     *
     * @param   mixed   $transient  The update transient object populated by WordPress.
     * @return  mixed               The transient with an update entry injected when applicable, or unchanged when no update is available or the API call fails.
     */

    public function check_update( mixed $transient ): mixed {

        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_release_info();

        if ( ! $release ) {
            return $transient;
        }

        $installed_version = $transient->checked[ $this->plugin_basename ] ?? '0';

        if ( version_compare( $release->version, $installed_version, '>' ) ) {

            $update              = new stdClass();
            $update->slug        = $this->plugin_slug;
            $update->plugin      = $this->plugin_basename;
            $update->new_version = $release->version;
            $update->tested      = BackupPilot::get_plugin_data( 'wp_version' );
            $update->package     = $release->download_url;
            $update->url         = 'https://github.com/' . $this->github_repo;

            $transient->response[ $this->plugin_basename ] = $update;
        }

        return $transient;
    }


    /**
     * Clears the cached release info after this plugin has been updated.
     *
     * Hooked into {@see 'upgrader_process_complete'} to ensure the next update
     * check fetches fresh data from GitHub rather than serving stale cache.
     *
     * @since   1.0.3
     *
     * @param   WP_Upgrader  $upgrader    The upgrader instance that just ran.
     * @param   array        $hook_extra  Metadata about the completed upgrade action.
     *
     * @return  void
     */

    public function purge_cache( WP_Upgrader $upgrader, array $hook_extra ): void {

        if (
            ( $hook_extra['action'] ?? '' ) === 'update' &&
            ( $hook_extra['type'] ?? '' ) === 'plugin' &&
            in_array( $this->plugin_basename, (array) ( $hook_extra['plugins'] ?? [] ), true )
        ) {
            delete_transient( $this->cache_key );
        }
    }


    /**
     * Adds a "Check for updates" link to this plugin's row in the Plugins list table.
     *
     * Hooked into {@see 'plugin_action_links_{basename}'}. The link leads to a
     * nonce-protected admin-post handler that clears the release cache and redirects
     * back to the Plugins page.
     *
     * @since   1.0.5
     *
     * @param   array  $links  Existing action links for this plugin row.
     * @return  array          Links with the cache-bust entry appended.
     */

    public function action_links( array $links ): array {

        $url = wp_nonce_url(
            add_query_arg( 'action', 'backuppilot_check_updates', admin_url( 'admin-post.php' ) ),
            'backuppilot_check_updates'
        );

        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check for updates', 'backuppilot' ) . '</a>';

        return $links;
    }


    /**
     * Handles the cache-bust request: verifies the nonce, deletes the release transient,
     * then redirects back to the Plugins page with a success flag.
     *
     * Hooked into {@see 'admin_post_backuppilot_check_updates'}.
     *
     * @since   1.0.5
     *
     * @return  void
     */

    public function handle_cache_bust(): void {

        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'backuppilot' ) );
        }

        check_admin_referer( 'backuppilot_check_updates' );

        delete_transient( $this->cache_key );

        wp_safe_redirect( add_query_arg( 'bp_update_checked', '1', self_admin_url( 'plugins.php' ) ) );
        exit;
    }


    /**
     * Displays a one-time success notice after the release cache has been cleared.
     *
     * Hooked into {@see 'admin_notices'}. The notice appears only when the
     * {@see handle_cache_bust()} redirect lands with `?bp_update_checked=1`.
     *
     * @since   1.0.5
     *
     * @return  void
     */

    public function update_check_notice(): void {

        if ( empty( $_GET['bp_update_checked'] ) ) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>'
            . esc_html__( 'Update cache cleared — WordPress will check for a new BackupPilot version on the next update check.', 'backuppilot' )
            . '</p></div>';
    }
}
