<?php

use QBF\BackupPilot\Pro\Settings;
use QBF\BackupPilot\Notice;

/**
 * BackupPilot_Admin
 */
final class BackupPilot_Admin {

	public $page_hooks = [];

	/** @var BackupPilot_Page_Settings */
	private $settings;

    /**
     * @param BackupPilot_Page_Settings $settings
     */
	public function __construct(BackupPilot_Page_Settings $settings) {

        $this->settings = $settings;

		BackupPilot::load_text_domain();
    }

    /**
     * Enqueues main css file
     */
    public function admin_css()
    {
        $pluginDir = untrailingslashit(BackupPilot::get_plugin_data('plugindir'));
        $filePath = "{$pluginDir}/assets/css/main.min.css";

        wp_enqueue_style(
            'backuppilot',
            BackupPilot::get_plugin_data('URL') . '/assets/css/main.min.css',
            array(),
            filemtime($filePath),
            'screen'
        );
    }

	/**
	 * Load for all BackupPilot pages
	 */
	public static function init_general() {

		add_thickbox();

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script(
			'backuppilotgeneral',
			BackupPilot::get_plugin_data( 'URL' ) . "/assets/js/general{$suffix}.js",
			array( 'jquery' ),
			( $suffix ? BackupPilot::get_plugin_data( 'Version' ) : time() ),
			false
		);

		// Register clipboard.js script
		wp_register_script(
			'backuppilot_clipboard',
			BackupPilot::get_plugin_data( 'URL' ) . '/assets/js/vendor/clipboard.min.js',
			array( 'jquery' ),
			'1.7.1',
			true
		);

		// Add Help.
		BackupPilot_Help::help();
	}

	/**
	 * Add Message (across site loadings)
	 *
	 * @param string $message string Message test.
	 * @param bool $error bool ist it a error message.
	 */
	public static function message( $message, $error = false ) {

		if ( empty( $message ) ) {
			return;
		}

		$saved_message = self::get_messages();

		if ( $error ) {
			$saved_message['error'][] = $message;
		} else {
			$saved_message['updated'][] = $message;
		}

		update_site_option( 'backuppilot_messages', $saved_message );
	}

	/**
	 * Get all Message that not displayed
	 *
	 * @return array
	 */
	public static function get_messages() {

		return get_site_option( 'backuppilot_messages', array() );
	}

	/**
	 * Display Messages
	 *
	 * @param bool $echo
	 *
	 * @return string
	 */
	public static function display_messages( $echo = true ) {

		/**
		 * This hook can be used to display more messages in all BackupPilot pages
		 */
		do_action( 'backuppilot_admin_messages' );

		$message_updated = '';
		$message_error = '';
		$saved_message = self::get_messages();
		$message_id = ' id="message"';

		if ( empty( $saved_message ) ) {
			return '';
		}

		if ( ! empty( $saved_message['updated'] ) ) {
			foreach ( $saved_message['updated'] as $msg ) {
				$message_updated .= '<p>' . $msg . '</p>';
			}
		}
		if ( ! empty( $saved_message['error'] ) ) {
			foreach ( $saved_message['error'] as $msg ) {
				$message_error .= '<p>' . $msg . '</p>';
			}
		}

		update_site_option( 'backuppilot_messages', array() );

		if ( ! empty( $message_updated ) ) {
			$message_updated = '<div' . $message_id . ' class="updated">' . $message_updated . '</div>';
			$message_id = '';
		}
		if ( ! empty( $message_error ) ) {
			$message_error = '<div' . $message_id . ' class="bwu-message-error">' . $message_error . '</div>';
		}

		if ( $echo ) {
			echo $message_updated . $message_error;
		}

		return $message_updated . $message_error;
	}

	/**
	 * Admin init function
	 */
	public function admin_init() {

		if ( ! is_admin() ) {
			return;
		}
		if ( ! defined( 'DOING_AJAX' ) || ( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) ) {
			// Only init notices if this is not an AJAX request
			$this->init_notices();

			// Everything after this point applies to AJAX
			return;
		}

		$jobtypes = BackupPilot::get_job_types();
		$destinations = BackupPilot::get_registered_destinations();

		add_action( 'wp_ajax_backuppilot_working', array( 'BackupPilot_Page_Jobs', 'ajax_working' ) );
		add_action( 'wp_ajax_backuppilot_cron_text', array( 'BackupPilot_Page_Editjob', 'ajax_cron_text' ) );
		add_action( 'wp_ajax_backuppilot_view_log', array( 'BackupPilot_Page_Logs', 'ajax_view_log' ) );
		add_action( 'wp_ajax_download_backup_file', array( 'BackupPilot_Destination_Downloader', 'download_by_ajax' ) );

		foreach ( $jobtypes as $id => $jobtypeclass ) {
			add_action( 'wp_ajax_backuppilot_jobtype_' . strtolower( $id ), array( $jobtypeclass, 'edit_ajax' ) );
		}

		foreach ( $destinations as $id => $dest ) {
			if ( ! empty( $dest['class'] ) ) {
				add_action(
					'wp_ajax_backuppilot_dest_' . strtolower( $id ),
					array(
						BackupPilot::get_destination( $id ),
						'edit_ajax',
					), 10, 0
				);
			}
		}

	}

	private function init_notices() {
		// Show notice if PHP < 7.2
		$phpNotice = new Notice\PhpNotice(
			new Notice\NoticeView( Notice\PhpNotice::ID )
		);
		$phpNotice->init( Notice\PhpNotice::TYPE_ADMIN );

		// Show notice if WordPress < 5.0
		$wpNotice = new Notice\WordPressNotice(
			new Notice\NoticeView( Notice\WordPressNotice::ID )
		);
		$wpNotice->init( Notice\WordPressNotice::TYPE_ADMIN );

		// Show notice if Dropbox needs to be reauthenticated
		$dropboxNotice = new Notice\DropboxNotice(
			new Notice\NoticeView( Notice\DropboxNotice::ID ),
			false // Not dismissible
		);
		$dropboxNotice->init( Notice\DropboxNotice::TYPE_ADMIN );
	}

	/**
	 *
	 * Add Links in Plugins Menu to BackupPilot
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function plugin_links( $links, $file ) {

		return $links;
	}

	/**
	 * Add menu entries
	 */
	public function admin_menu() {

		add_menu_page( BackupPilot::get_plugin_data( 'name' ),
			BackupPilot::get_plugin_data( 'name' ),
			'backuppilot',
			'backuppilot',
			array(
				'BackupPilot_Page_BackupPilot',
				'page',
			),
			'div' );
		$this->page_hooks['backuppilot'] = add_submenu_page( 'backuppilot',
			__( 'BackupPilot Dashboard', 'backuppilot' ),
			__( 'Dashboard', 'backuppilot' ),
			'backuppilot',
			'backuppilot',
			array(
				'BackupPilot_Page_BackupPilot',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backuppilot'], array( 'BackupPilot_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backuppilot'], array( 'BackupPilot_Page_BackupPilot', 'load' ) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backuppilot'],
			array(
				'BackupPilot_Page_BackupPilot',
				'admin_print_scripts',
			) );

		//Add pages form plugins
		$this->page_hooks = apply_filters( 'backuppilot_admin_pages', $this->page_hooks );

	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_jobs( $page_hooks ) {

		$this->page_hooks['backuppilotjobs'] = add_submenu_page( 'backuppilot',
			__( 'Jobs', 'backuppilot' ),
			__( 'Jobs', 'backuppilot' ),
			'backuppilot_jobs',
			'backuppilotjobs',
			array(
				'BackupPilot_Page_Jobs',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backuppilotjobs'], array( 'BackupPilot_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backuppilotjobs'], array( 'BackupPilot_Page_Jobs', 'load' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backuppilotjobs'],
			array(
				'BackupPilot_Page_Jobs',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backuppilotjobs'],
			array(
				'BackupPilot_Page_Jobs',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_editjob( $page_hooks ) {

		$this->page_hooks['backuppiloteditjob'] = add_submenu_page( 'backuppilot',
			__( 'Add new job', 'backuppilot' ),
			__( 'Add new job', 'backuppilot' ),
			'backuppilot_jobs_edit',
			'backuppiloteditjob',
			array(
				'BackupPilot_Page_Editjob',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backuppiloteditjob'], array( 'BackupPilot_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backuppiloteditjob'], array( 'BackupPilot_Page_Editjob', 'auth' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backuppiloteditjob'],
			array(
				'BackupPilot_Page_Editjob',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backuppiloteditjob'],
			array(
				'BackupPilot_Page_Editjob',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_logs( $page_hooks ) {

		$this->page_hooks['backuppilotlogs'] = add_submenu_page( 'backuppilot',
			__( 'Logs', 'backuppilot' ),
			__( 'Logs', 'backuppilot' ),
			'backuppilot_logs',
			'backuppilotlogs',
			array(
				'BackupPilot_Page_Logs',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backuppilotlogs'], array( 'BackupPilot_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backuppilotlogs'], array( 'BackupPilot_Page_Logs', 'load' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backuppilotlogs'],
			array(
				'BackupPilot_Page_Logs',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backuppilotlogs'],
			array(
				'BackupPilot_Page_Logs',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_backups( $page_hooks ) {

		$this->page_hooks['backuppilotbackups'] = add_submenu_page( 'backuppilot',
			__( 'Backups', 'backuppilot' ),
			__( 'Backups', 'backuppilot' ),
			'backuppilot_backups',
			'backuppilotbackups',
			array(
				'BackupPilot_Page_Backups',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backuppilotbackups'], array( 'BackupPilot_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backuppilotbackups'], array( 'BackupPilot_Page_Backups', 'load' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backuppilotbackups'],
			array(
				'BackupPilot_Page_Backups',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backuppilotbackups'],
			array(
				'BackupPilot_Page_Backups',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_settings( $page_hooks ) {

		$this->page_hooks['backuppilotsettings'] = add_submenu_page(
			'backuppilot',
			esc_html__( 'Settings', 'backuppilot' ),
			esc_html__( 'Settings', 'backuppilot' ),
			'backuppilot_settings',
			'backuppilotsettings',
			array( $this->settings, 'page' )
		);
		add_action( 'load-' . $this->page_hooks['backuppilotsettings'], array( 'BackupPilot_Admin', 'init_general' ) );
		add_action(
			'admin_print_scripts-' . $this->page_hooks['backuppilotsettings'],
			array( $this->settings, 'admin_print_scripts' )
		);

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 * @deprecated
	 */
	public function admin_page_about( $page_hooks ) {

		$this->page_hooks['backuppilotabout'] = add_submenu_page( 'backuppilot',
			__( 'About', 'backuppilot' ),
			__( 'About', 'backuppilot' ),
			'backuppilot',
			'backuppilotabout',
			array(
				'BackupPilot_Page_About',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backuppilotabout'], array( 'BackupPilot_Admin', 'init_general' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backuppilotabout'],
			array(
				'BackupPilot_Page_About',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backuppilotabout'],
			array(
				'BackupPilot_Page_About',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * Called on save form. Only POST allowed.
	 */
	public function save_post_form() {

		$allowed_pages = array(
			'backuppiloteditjob',
			'backuppilotinformation',
			'backuppilotsettings',
		);

		if ( ! in_array( $_POST['page'], $allowed_pages, true ) ) {
			wp_die( esc_html__( 'Cheating, huh?', 'backuppilot' ) );
		}

		//nonce check
		check_admin_referer( $_POST['page'] . '_page' );

		if ( ! current_user_can( 'backuppilot' ) ) {
			wp_die( esc_html__( 'Cheating, huh?', 'backuppilot' ) );
		}

		//build query for redirect
		if ( ! isset( $_POST['anchor'] ) ) {
			$_POST['anchor'] = null;
		}
		$query_args = array();
		if ( isset( $_POST['page'] ) ) {
			$query_args['page'] = $_POST['page'];
		}
		if ( isset( $_POST['tab'] ) ) {
			$query_args['tab'] = $_POST['tab'];
		}
		if ( isset( $_POST['tab'] ) && isset( $_POST['nexttab'] ) && $_POST['tab'] !== $_POST['nexttab'] ) {
			$query_args['tab'] = $_POST['nexttab'];
		}

		$jobid = null;
		if ( isset( $_POST['jobid'] ) ) {
			$jobid = (int) $_POST['jobid'];
			$query_args['jobid'] = $jobid;
		}

		// Call method to save data
		if ( $_POST['page'] === 'backuppiloteditjob' ) {
			BackupPilot_Page_Editjob::save_post_form( $_POST['tab'], $jobid );
		} elseif ( $_POST['page'] === 'backuppilotsettings' ) {
			$this->settings->save_post_form();
		}

		//Back to topic
		wp_safe_redirect( add_query_arg( $query_args, network_admin_url( 'admin.php' ) ) . $_POST['anchor'] );
		exit;
	}

	/**
	 * Overrides WordPress text in Footer
	 *
	 * @param $admin_footer_text string
	 *
	 * @return string
	 */
	public function admin_footer_text( $admin_footer_text ) {

		return $admin_footer_text;
	}

	/**
	 * Overrides WordPress Version in Footer
	 *
	 * @param $update_footer_text string
	 *
	 * @return string
	 */
	public function update_footer( $update_footer_text ) {

		$default_text = $update_footer_text;

		if ( isset( $_REQUEST['page'] ) && strstr( $_REQUEST['page'], 'backuppilot' ) ) {
			$update_footer_text = '<span class="backuppilot-update-footer">' . BackupPilot::get_plugin_data( 'Name' ) . ' ' . sprintf( __( 'version %s',
					'backuppilot' ),
					BackupPilot::get_plugin_data( 'Version' ) ) . '</span>';

			return $update_footer_text . $default_text;
		}

		return $update_footer_text;
	}

	/**
	 *  Add filed for selecting user role in user section
	 *
	 * @param $user WP_User
	 */
	public function user_profile_fields( $user ) {

		global $wp_roles;

		if ( ! is_super_admin() && ! current_user_can( 'backuppilot_admin' ) ) {
			return;
		}

		//user is admin and has BackupPilot rights
		if ( $user->has_cap( 'administrator' ) && $user->has_cap( 'backuppilot_settings' ) ) {
			return;
		}

		//get backuppilot roles
		$backuppilot_roles = array();
		foreach ( $wp_roles->roles as $role => $role_value ) {
			if ( substr( $role, 0, 8 ) != 'backuppilot' ) {
				continue;
			}
			$backuppilot_roles[ $role ] = $role_value;
		}

		//only if user has other than backuppilot role
		if ( ! empty( $user->roles[0] ) && in_array( $user->roles[0], array_keys( $backuppilot_roles ), true ) ) {
			return;
		}

		?>
		<h3><?php echo BackupPilot::get_plugin_data( 'name' ); ?></h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="backuppilot_role"><?php _e( 'Add BackupPilot Role', 'backuppilot' ); ?></label>
				</th>
				<td>
					<select name="backuppilot_role" id="backuppilot_role" style="display:inline-block; float:none;">
						<option
							value=""><?php _e( '&mdash; No additional role for BackupPilot &mdash;',
								'backuppilot' ); ?></option>
						<?php
						foreach ( $backuppilot_roles as $role => $role_value ) {
							echo '<option value="' . $role . '" ' . selected( $user->has_cap( $role ),
									true,
									false ) . '>' . $role_value['name'] . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save for user role adding
	 *
	 * @param $user_id int
	 */
	public function save_profile_update( $user_id ) {

		global $wp_roles;

		if ( ! is_super_admin() && ! current_user_can( 'backuppilot_admin' ) ) {
			return;
		}

		if ( empty( $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST['backuppilot_role'] ) ) {
			return;
		}

		$backuppilot_role = esc_attr( $_POST['backuppilot_role'] );

		//get BackupPilot roles
		$backuppilot_roles = array();
		foreach ( array_keys( $wp_roles->roles ) as $role ) {
			if ( ! strstr( $role, 'backuppilot_' ) ) {
				continue;
			}
			$backuppilot_roles[] = $role;
		}

		//get user for adding/removing role
		$user = new WP_User( $user_id );
		//a admin needs no extra role
		if ( $user->has_cap( 'administrator' ) && $user->has_cap( 'backuppilot_settings' ) ) {
			$backuppilot_role = '';
		}

		//remove BackupPilot role from user if it not the actual
		foreach ( $user->roles as $role ) {
			if ( ! strstr( $role, 'backuppilot_' ) ) {
				continue;
			}
			if ( $role !== $backuppilot_role ) {
				$user->remove_role( $role );
			} else {
				$backuppilot_role = '';
			}
		}

		//add new role to user if it not the actual
		if ( $backuppilot_role && in_array( $backuppilot_role, $backuppilot_roles, true ) ) {
			$user->add_role( $backuppilot_role );
		}

		return;
	}

    public function init()
    {
        //Add menu pages
        add_filter('backuppilot_admin_pages', [$this, 'admin_page_jobs'], 2);
        add_filter('backuppilot_admin_pages', [$this, 'admin_page_editjob'], 3);
        add_filter('backuppilot_admin_pages', [$this, 'admin_page_logs'], 4);
        add_filter('backuppilot_admin_pages', [$this, 'admin_page_backups'], 5);
        add_filter('backuppilot_admin_pages', [$this, 'admin_page_settings'], 6);
        //add_filter('backuppilot_admin_pages', [$this, 'admin_page_about'], 20);

        //Add Menu
        if (is_multisite()) {
            add_action('network_admin_menu', [$this, 'admin_menu']);
        } else {
            add_action('admin_menu', [$this, 'admin_menu']);
        }
        //add Plugin links
        add_filter('plugin_row_meta', [$this, 'plugin_links'], 10, 2);
        //add more actions
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_enqueue_scripts', [$this, 'admin_css']);
        //Save Form posts general
        add_action('admin_post_backuppilot', [$this, 'save_post_form']);
        //Admin Footer Text replacement
        add_filter('admin_footer_text', [$this, 'admin_footer_text'], 100);
        add_filter('update_footer', [$this, 'update_footer'], 100);
        //User Profile fields
        add_action('show_user_profile', [$this, 'user_profile_fields']);
        add_action('edit_user_profile', [$this, 'user_profile_fields']);
        add_action('profile_update', [$this, 'save_profile_update']);
    }

    private function __clone()
    {
    }

}
