<?php
/**
 * Plugin Name: BackupPilot
 * Plugin URI: http://dev.patrick-mauersberger.de
 * Description: WordPress Backup Plugin based on the fantastic BackWPup (GPLv2+)
 * Author: Patrick Mauersberger
 * Author URI: http://dev.patrick-mauersberger.de
 * Version: 1.0.0
 * Text Domain: backuppilot
 * _Domain Path: /plugins/backuppilot/languages/
 * Network: true
 * License: GPLv2+
 *
 * This is a fork of the fantastic BackWPup Plugin in a working condition 3.10.0 before Version 5.0 Release
 */

if ( ! class_exists( 'BackupPilot', false ) ) {

	/**
	 * Main BackupPilot Plugin Class
	 */

	final class BackupPilot {

		private static $instance                = null;
		private static $plugin_data             = [];
		private static $destinations            = [];
		private static $registered_destinations = [];
		private static $job_types               = [];
		private static $wizards                 = [];


		/**
		 * Set needed filters and actions and load
		 */

		private function __construct() {

			// Nothing else matters if we're not on the main site
			if ( ! is_main_network() && ! is_main_site() ) {
				return;
			}

			require_once __DIR__ . '/inc/functions.php';

			if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
                require_once __DIR__ . '/vendor/autoload.php';
            }

			// Start upgrade if needed
			if ( get_site_option( 'backuppilot_version' ) !== self::get_plugin_data( 'Version' )
			     || ! wp_next_scheduled( 'backuppilot_check_cleanup' )
			) {
				BackupPilot_Install::activate();
			}

			// WP-Cron
			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {

				if ( ! empty( $_GET['backuppilot_run'] ) && class_exists( 'BackupPilot_Job' ) ) {
					// Early disable caches
					BackupPilot_Job::disable_caches();
					// Add action for running jobs in wp-cron.php
					add_action( 'wp_loaded', array( 'BackupPilot_Cron', 'cron_active' ), PHP_INT_MAX );
				}

				else {
					// Add cron actions
					add_action( 'backuppilot_cron', array( 'BackupPilot_Cron', 'run' ) );
					add_action( 'backuppilot_check_cleanup', array( 'BackupPilot_Cron', 'check_cleanup' ) );
				}

				// If in cron the rest is not needed
				return;
			}

			// Deactivation hook
			register_deactivation_hook( __FILE__, array( 'BackupPilot_Install', 'deactivate' ) );

			// Only in backend
			if ( is_admin() && class_exists( 'BackupPilot_Admin' ) ) {

                $settings_views = [];
                $settings_updaters = [];

                $settings = new BackupPilot_Page_Settings(
                    $settings_views,
                    $settings_updaters
                );

                $admin = new BackupPilot_Admin($settings);
                $admin->init();

                if (get_site_option('backuppilot_cfg_showadminbar')) {
                    $adminBar = new BackupPilot_Adminbar($admin);
                    add_action('init', [$adminBar, 'init']);
                }

                new BackupPilot_EasyCron();
            }

            // Work with wp-cli
			if ( defined( 'WP_CLI' ) && WP_CLI && method_exists( 'WP_CLI', 'add_command' ) ) {
				WP_CLI::add_command( 'backuppilot', 'BackupPilot_WP_CLI' );
			}
		}

		/**
		 * @return self
		 */
		public static function get_instance() {

			if ( null === self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * @return bool
		 */
		public static function is_pro() {

			return false;
		}

		/**
		 * Prevent Cloning
		 */
		public function __clone() {

			wp_die( 'Cheatin&#8217; huh?' );
		}

		/**
		 * Prevent deserialization
		 */
		public function __wakeup() {

			wp_die( 'Cheatin&#8217; huh?' );
		}

		/**
		 * get information about the Plugin
		 *
		 * @param string $name Name of info to get or NULL to get all
		 *
		 * @return string|array
		 */
		public static function get_plugin_data( $name = null ) {

			if ( $name ) {
				$name = strtolower( trim( $name ) );
			}

			if ( empty( self::$plugin_data ) ) {
				self::$plugin_data = get_file_data(
					__FILE__,
					array(
						'name' => 'Plugin Name',
						'version' => 'Version',
					),
					'plugin'
				);
				self::$plugin_data['name'] = trim( self::$plugin_data['name'] );
				//set some extra vars
				self::$plugin_data['basename'] = plugin_basename( __DIR__ );
				self::$plugin_data['mainfile'] = __FILE__;
				self::$plugin_data['plugindir'] = untrailingslashit( __DIR__ );
				self::$plugin_data['hash'] = get_site_option( 'backuppilot_cfg_hash' );
				if ( empty( self::$plugin_data['hash'] ) || strlen( self::$plugin_data['hash'] ) < 6
				     || strlen(
					        self::$plugin_data['hash']
				        ) > 12 ) {
					self::$plugin_data['hash'] = self::get_generated_hash(6);
					update_site_option( 'backuppilot_cfg_hash', self::$plugin_data['hash'] );
				}
				if ( defined( 'WP_TEMP_DIR' ) && is_dir( WP_TEMP_DIR ) ) {
					self::$plugin_data['temp'] = str_replace(
						                             '\\',
						                             '/',
						                             get_temp_dir()
					                             ) . 'backuppilot-' . self::$plugin_data['hash'] . '/';
				} else {
					$upload_dir = wp_upload_dir();
					self::$plugin_data['temp'] = str_replace(
						                             '\\',
						                             '/',
						                             $upload_dir['basedir']
					                             ) . '/backuppilot-' . self::$plugin_data['hash'] . '-temp/';
				}
				self::$plugin_data['running_file'] = self::$plugin_data['temp'] . 'backuppilot-working.php';
				self::$plugin_data['url'] = plugins_url( '', __FILE__ );
				self::$plugin_data['cacert'] = apply_filters(
					'backuppilot_cacert_bundle',
					ABSPATH . WPINC . '/certificates/ca-bundle.crt'
				);
				//get unmodified WP Versions
				include ABSPATH . WPINC . '/version.php';
				/** @var $wp_version string */
				self::$plugin_data['wp_version'] = $wp_version;
				//Build User Agent
				self::$plugin_data['user-agent'] = self::$plugin_data['name'] . '/' . self::$plugin_data['version'] . '; WordPress/' . self::$plugin_data['wp_version'] . '; ' . home_url();
			}

			if ( ! empty( $name ) ) {
				return self::$plugin_data[ $name ];
			} else {
				return self::$plugin_data;
			}
		}

        /**
         * Generates a random hash
         *
         * @param int $length
         *
         * @return string
         */
		public static function get_generated_hash( $length = 6 ) {

		    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            $hash = '';
            for ( $i = 0; $i < 254; $i++ ) {
                $hash .= $chars[random_int(0, 61)];
            }

            return substr(md5($hash), random_int(0, 31 - $length), $length);
        }

		/**
		 * Load Plugin Translation
		 *
		 * @return bool Text domain loaded
		 */
		public static function load_text_domain() {

			if ( is_textdomain_loaded( 'backuppilot' ) ) {
				return true;
			}

			return load_plugin_textdomain( 'backuppilot', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Get a array of instances for Backup Destination's
		 *
		 * @param $key string Key of Destination where get class instance from
		 *
		 * @return array BackupPilot_Destinations
		 */
		public static function get_destination( $key ) {

			$key = strtoupper( $key );

			if ( isset( self::$destinations[ $key ] ) && is_object( self::$destinations[ $key ] ) ) {
				return self::$destinations[ $key ];
			}

			$reg_dests = self::get_registered_destinations();
			if ( ! empty( $reg_dests[ $key ]['class'] ) ) {
				self::$destinations[ $key ] = new $reg_dests[ $key ]['class'];
			} else {
				return null;
			}

			return self::$destinations[ $key ];
		}

		/**
		 * Get a array of registered Destination's for Backups
		 *
		 * @return array BackupPilot_Destinations
		 */
		public static function get_registered_destinations() {

			//only run it one time
			if ( ! empty( self::$registered_destinations ) ) {
				return self::$registered_destinations;
			}

			//add BackupPilot Destinations
			// to folder
			self::$registered_destinations['FOLDER'] = array(
				'class'    => 'BackupPilot_Destination_Folder',
				'info'     => array(
					'ID'          => 'FOLDER',
					'name'        => __( 'Folder', 'backuppilot' ),
					'description' => __( 'Backup to Folder', 'backuppilot' ),
				),
				'can_sync'  => false,
				'needed'    => array(
					'php_version' => '',
					'functions'   => array(),
					'classes'     => array(),
				),
			);
			/* backup with mail --- no support for E-Mail sending giga bytes via mail is shit
			self::$registered_destinations['EMAIL'] = array(
				'class' => 'BackupPilot_Destination_Email',
				'info' => array(
					'ID' => 'EMAIL',
					'name' => __( 'Email', 'backuppilot' ),
					'description' => __( 'Backup sent via email', 'backuppilot' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array(),
					'classes' => array(),
				),
			);
			/*__*/
			// backup to ftp
			self::$registered_destinations['FTP'] = array(
				'class' => 'BackupPilot_Destination_Ftp',
				'info' => array(
					'ID' => 'FTP',
					'name' => __( 'FTP', 'backuppilot' ),
					'description' => __( 'Backup to FTP', 'backuppilot' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'ftp_nb_fput' ),
					'classes' => array(),
				),
			);
			// backup to dropbox
			self::$registered_destinations['DROPBOX'] = array(
				'class' => 'BackupPilot_Destination_Dropbox',
				'info' => array(
					'ID' => 'DROPBOX',
					'name' => __( 'Dropbox', 'backuppilot' ),
					'description' => __( 'Backup to Dropbox', 'backuppilot' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'curl_exec' ),
					'classes' => array(),
				),
			);
			// Backup to S3
			self::$registered_destinations['S3'] = array(
				'class' => 'BackupPilot_Destination_S3',
				'info' => array(
					'ID' => 'S3',
					'name' => __( 'S3 Service', 'backuppilot' ),
					'description' => __( 'Backup to an S3 Service', 'backuppilot' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'curl_exec' ),
					'classes' => array( 'XMLWriter' ),
				),
			);
			// backup to MS Azure
			self::$registered_destinations['MSAZURE'] = array(
				'class' => 'BackupPilot_Destination_MSAzure',
				'info' => array(
					'ID' => 'MSAZURE',
					'name' => __( 'MS Azure', 'backuppilot' ),
					'description' => __( 'Backup to Microsoft Azure (Blob)', 'backuppilot' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '5.6.0',
					'functions' => array(),
					'classes' => array(),
				),
			);
			// backup to Sugarsync
			self::$registered_destinations['SUGARSYNC'] = array(
				'class' => 'BackupPilot_Destination_SugarSync',
				'info' => array(
					'ID' => 'SUGARSYNC',
					'name' => __( 'SugarSync', 'backuppilot' ),
					'description' => __( 'Backup to SugarSync', 'backuppilot' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'curl_exec' ),
					'classes' => array(),
				),
			);
			/*__*/

			//Hook for adding Destinations like above
			self::$registered_destinations = apply_filters(
				'backuppilot_register_destination',
				self::$registered_destinations
			);

			//check BackupPilot Destinations
			foreach ( self::$registered_destinations as $dest_key => $dest ) {
				self::$registered_destinations[ $dest_key ]['error'] = '';
				// check PHP Version
				if ( ! empty( $dest['needed']['php_version'] )
				     && version_compare(
					     PHP_VERSION,
					     $dest['needed']['php_version'],
					     '<'
				     ) ) {
					self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
						                                                        __( 'PHP Version %1$s is to low, you need Version %2$s or above.',
							                                                        'backuppilot' ),
						                                                        PHP_VERSION,
						                                                        $dest['needed']['php_version']
					                                                        ) . ' ';
					self::$registered_destinations[ $dest_key ]['class'] = null;
				}
				//check functions exists
				if ( ! empty( $dest['needed']['functions'] ) ) {
					foreach ( $dest['needed']['functions'] as $function_need ) {
						if ( ! function_exists( $function_need ) ) {
							self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
								                                                        __( 'Missing function "%s".',
									                                                        'backuppilot' ),
								                                                        $function_need
							                                                        ) . ' ';
							self::$registered_destinations[ $dest_key ]['class'] = null;
						}
					}
				}
				//check classes exists
				if ( ! empty( $dest['needed']['classes'] ) ) {
					foreach ( $dest['needed']['classes'] as $class_need ) {
						if ( ! class_exists( $class_need ) ) {
							self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
								                                                        __( 'Missing class "%s".',
									                                                        'backuppilot' ),
								                                                        $class_need
							                                                        ) . ' ';
							self::$registered_destinations[ $dest_key ]['class'] = null;
						}
					}
				}
			}

			return self::$registered_destinations;
		}

		/**
		 * Gets a array of instances from Job types
		 *
		 * @return array BackupPilot_JobTypes
		 */
		public static function get_job_types() {

			if ( ! empty( self::$job_types ) ) {
				return self::$job_types;
			}

			self::$job_types['DBDUMP']   = new BackupPilot_JobType_DBDump;
			self::$job_types['FILE']     = new BackupPilot_JobType_File;
			self::$job_types['WPEXP']    = new BackupPilot_JobType_WPEXP;
			self::$job_types['WPPLUGIN'] = new BackupPilot_JobType_WPPlugin;
			self::$job_types['DBCHECK']  = new BackupPilot_JobType_DBCheck;

			self::$job_types = apply_filters( 'backuppilot_job_types', self::$job_types );

			//remove types can't load
			foreach ( self::$job_types as $key => $job_type ) {
				if ( empty( $job_type ) || ! is_object( $job_type ) ) {
					unset( self::$job_types[ $key ] );
				}
			}

			return self::$job_types;
		}

		/**
		 * Gets a array of instances from Wizards
		 *
		 * @return array
		 */
		public static function get_wizards() {

			if ( ! empty( self::$wizards ) ) {
				return self::$wizards;
			}

			self::$wizards = apply_filters( 'backuppilot_pro_wizards', self::$wizards );

			//remove wizards can't load
			foreach ( self::$wizards as $key => $wizard ) {
				if ( empty( $wizard ) || ! is_object( $wizard ) ) {
					unset( self::$wizards[ $key ] );
				}
			}

			return self::$wizards;

		}
	}

	require_once __DIR__ . '/inc/class-system-requirements.php';
	require_once __DIR__ . '/inc/class-system-tests.php';

	$system_requirements = new BackupPilot_System_Requirements();
	$system_tests = new BackupPilot_System_Tests( $system_requirements );

	// Don't activate on anything less than PHP 5.3 or WordPress 3.9
	if ( ! $system_tests->is_php_version_compatible() || ! $system_tests->is_wp_version_compatible() ) {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );

		die(
			sprintf(
				esc_html__(
					'BackupPilot requires PHP version %1$s with spl extension or greater and WordPress %2$s or greater.',
					'backuppilot'
				),
				$system_requirements->php_minimum_version(),
				$system_requirements->wp_minimum_version()
			)
		);
	}

	//Start Plugin
	add_action( 'plugins_loaded', array( 'BackupPilot', 'get_instance' ), 11 );
}
