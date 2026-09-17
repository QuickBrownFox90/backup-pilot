<?php
/**
 * Class for upgrade / deactivation / uninstall
 */
class BackupPilot_Install {

    /**
     * Creates DB und updates settings
     */

    public static function activate() {

        if ( class_exists( 'BackWPup' ) ) {
            deactivate_plugins( plugin_basename( __DIR__ . '/../backuppilot.php' ) );
            wp_die(
                __( 'BackupPilot cannot be activated while BackWPup is active. Please deactivate BackWPup first.', 'backuppilot' ),
                __( 'Plugin Activation Error', 'backuppilot' ),
                [ 'back_link' => true ]
            );
        }

        //one-time migration of an existing BackWPup installation (pre-rename)
        if ( ! get_site_option( 'backuppilot_version' ) && get_site_option( 'backwpup_version' ) ) {
            self::migrate_from_backwpup();
        }

        $version_db = get_site_option( 'backuppilot_version' );

        delete_site_option( 'backuppilot_cfg_jobziparchivemethod' );

        //create new options
        if ( is_multisite() ) {
            add_site_option( 'backuppilot_jobs', [] );
        }

        else {
            add_option( 'backuppilot_jobs', [], null, 'no' );
        }

        //remove old schedule (wp_clear_scheduled_hook() without $args only matches events
        //scheduled with an empty args array, which 'backuppilot_cron' never is - it needs
        //wp_unschedule_hook() to actually catch every args variant, e.g. leftover pre-1.0.7
        //events scheduled with the old ['arg' => $id] shape instead of the current [$id])
        wp_unschedule_hook( 'backuppilot_cron' );
        //make new schedule
        $activejobs = BackupPilot_Option::get_job_ids( 'activetype', 'wpcron' );

        if ( ! empty( $activejobs ) ) {

            foreach ( $activejobs as $id ) {
                $cron_next = BackupPilot_Cron::cron_next( BackupPilot_Option::get( $id, 'cron') );
                wp_schedule_single_event( $cron_next, 'backuppilot_cron', [ $id ] );
            }
        }

        $activejobs = BackupPilot_Option::get_job_ids( 'activetype', 'easycron' );

        if ( ! empty( $activejobs ) ) {

            foreach ( $activejobs as $id ) {
                BackupPilot_EasyCron::update( $id );
            }
        }

        //add Cleanup schedule
        if ( ! wp_next_scheduled( 'backuppilot_check_cleanup' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'backuppilot_check_cleanup' );
        }

        //add capabilities to administrator role
        $role = get_role( 'administrator' );

        if ( is_object( $role ) && method_exists( $role, 'add_cap' ) ) {
            $role->add_cap( 'backuppilot' );
            $role->add_cap( 'backuppilot_jobs' );
            $role->add_cap( 'backuppilot_jobs_edit' );
            $role->add_cap( 'backuppilot_jobs_start' );
            $role->add_cap( 'backuppilot_backups' );
            $role->add_cap( 'backuppilot_backups_download' );
            $role->add_cap( 'backuppilot_backups_delete' );
            $role->add_cap( 'backuppilot_logs' );
            $role->add_cap( 'backuppilot_logs_delete' );
            $role->add_cap( 'backuppilot_settings' );
            $role->add_cap( 'backuppilot_restore' );
        }

        //add/overwrite roles
        add_role( 'backuppilot_admin', __( 'BackupPilot Admin', 'backuppilot' ), [
            'read'                         => true, // make it usable for single user
            'backuppilot'                  => true, // BackupPilot general accesses (like Dashboard)
            'backuppilot_jobs'             => true, // accesses for job page
            'backuppilot_jobs_edit'        => true, // user can edit/delete/copy/export jobs
            'backuppilot_jobs_start'       => true, // user can start jobs
            'backuppilot_backups'          => true, // accesses for backups page
            'backuppilot_backups_download' => true, // user can download backup files
            'backuppilot_backups_delete'   => true, // user can delete backup files
            'backuppilot_logs'             => true, // accesses for logs page
            'backuppilot_logs_delete'      => true, // user can delete log files
            'backuppilot_settings'         => true, // accesses for settings page
            'backuppilot_restore'          => true, // accesses for restore page
        ] );

        add_role( 'backuppilot_check', __( 'BackupPilot jobs checker', 'backuppilot' ), [
            'read'                         => true,
            'backuppilot'                  => true,
            'backuppilot_jobs'             => true,
            'backuppilot_jobs_edit'        => false,
            'backuppilot_jobs_start'       => false,
            'backuppilot_backups'          => true,
            'backuppilot_backups_download' => false,
            'backuppilot_backups_delete'   => false,
            'backuppilot_logs'             => true,
            'backuppilot_logs_delete'      => false,
            'backuppilot_settings'         => false,
            'backuppilot_restore'          => false,
        ] );

        add_role( 'backuppilot_helper', __( 'BackupPilot jobs functions', 'backuppilot' ), [
            'read'                         => true,
            'backuppilot'                  => true,
            'backuppilot_jobs'             => true,
            'backuppilot_jobs_edit'        => false,
            'backuppilot_jobs_start'       => true,
            'backuppilot_backups'          => true,
            'backuppilot_backups_download' => true,
            'backuppilot_backups_delete'   => true,
            'backuppilot_logs'             => true,
            'backuppilot_logs_delete'      => true,
            'backuppilot_settings'         => false,
            'backuppilot_restore'          => false,
        ] );

        //add default options
        BackupPilot_Option::default_site_options();

        //update version
        update_site_option( 'backuppilot_version', BackupPilot::get_plugin_data( 'Version' ) );

        //only redirect if not in WP CLI environment
        if ( ! $version_db && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            wp_redirect( network_admin_url( 'admin.php' ) . '?page=backuppilot' );

            die();
        }

    }


    /**
     * Migrates an existing BackWPup installation (pre-rename) to BackupPilot.
     *
     * Copies all 'backwpup*' site options (config, jobs, version) to their
     * 'backuppilot*' equivalents, and re-assigns the legacy roles to users
     * who held them, so existing jobs/settings/permissions survive the switch.
     *
     * @since  1.0.0
     * @since  1.0.5  Values are passed through transform_migrated_value() to fix encrypted passwords and plugin-specific path segments before storage.
     */

    private static function migrate_from_backwpup() {

        global $wpdb;

        if ( is_multisite() ) {

            $rows = $wpdb->get_results( "SELECT meta_key, meta_value FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'backwpup%'" );

            foreach ( $rows as $row ) {
                $new_key = 'backuppilot' . substr( $row->meta_key, strlen( 'backwpup' ) );
                if ( get_site_option( $new_key ) === false ) {
                    update_site_option( $new_key, self::transform_migrated_value( maybe_unserialize( $row->meta_value ) ) );
                }
            }
        }
        else {

            $rows = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'backwpup%'" );

            foreach ( $rows as $row ) {

                $new_key = 'backuppilot' . substr( $row->option_name, strlen( 'backwpup' ) );

                if ( get_option( $new_key ) === false ) {
                    update_option( $new_key, self::transform_migrated_value( maybe_unserialize( $row->option_value ) ) );
                }
            }
        }

        //re-assign legacy roles to users who held them
        $role_map = [
            'backwpup_admin'  => 'backuppilot_admin',
            'backwpup_helper' => 'backuppilot_helper',
            'backwpup_check'  => 'backuppilot_check',
        ];

        foreach ( $role_map as $old_role => $new_role ) {

            $users = get_users( [ 'role' => $old_role ] );

            foreach ( $users as $user ) {
                $user->add_role( $new_role );
                $user->remove_role( $old_role );
            }
        }
    }


    /**
     * Recursively transforms a BackWPup option value for storage under BackupPilot.
     *
     * Two string substitutions are applied to every string node in the value tree:
     *
     * 1. Encrypted-string prefix: "$BackWPup$" → "$BackupPilot$"
     *    BackWPup and BackupPilot derive the encryption key identically
     *    (DB_NAME . DB_USER . DB_PASSWORD), so only the outer label differs.
     *    Swapping it lets BackupPilot_Encryption::decrypt() process the ciphertext.
     *
     * 2. Generated folder segment: "backwpup-" → "backuppilot-"
     *    Folder names such as "backwpup-abc123-logs/" become
     *    "backuppilot-abc123-logs/". The hash is preserved because
     *    backwpup_cfg_hash is migrated to backuppilot_cfg_hash before
     *    BackupPilot evaluates any path.
     *
     * @since   1.0.5
     *
     * @param   mixed  $value  Unserialized option value (scalar, array, or object).
     * @return  mixed          Transformed value of the same type.
     */

    private static function transform_migrated_value( $value ) {

        if ( is_array( $value ) ) {

            foreach ( $value as $k => $v ) {
                $value[ $k ] = self::transform_migrated_value( $v );
            }

            return $value;
        }

        if ( ! is_string( $value ) ) {
            return $value;
        }

        $value = str_replace( '$BackWPup$', '$BackupPilot$', $value );
        $value = str_replace( 'backwpup-', 'backuppilot-', $value );

        return $value;
    }


    /**
     *
     * Cleanup on Plugin deactivation
     *
     * @return void
     */

    public static function deactivate() {

        //catches every args variant, including leftover pre-1.0.7 events scheduled
        //with the old ['arg' => $id] shape instead of the current [$id]
        wp_unschedule_hook( 'backuppilot_cron' );

        wp_clear_scheduled_hook( 'backuppilot_check_cleanup' );

        $activejobs = BackupPilot_Option::get_job_ids( 'activetype', 'easycron' );

        if ( ! empty( $activejobs ) ) {

            foreach ( $activejobs as $id ) {
                BackupPilot_EasyCron::delete( $id );
            }
        }
    }
}