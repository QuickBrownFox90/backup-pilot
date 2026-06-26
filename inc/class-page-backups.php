<?php

final class BackupPilot_Page_Backups extends WP_List_Table {

	private static $listtable = null;

	private $destinations = array();

	private $jobid = 1;

	private $dest = 'FOLDER';

	public function __construct() {

		parent::__construct( array(
			'plural' => 'backups',
			'singular' => 'backup',
			'ajax' => true,
		) );

		$this->destinations = BackupPilot::get_registered_destinations();

	}

	public function ajax_user_can() {

		return current_user_can( 'backuppilot_backups' );
	}

	public function prepare_items() {

		$per_page = $this->get_items_per_page( 'backuppilotbackups_per_page' );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = 20;
		}

		$jobdest = '';
		if ( isset( $_GET['jobdets-button-top'] ) ) {
			$jobdest = sanitize_text_field( $_GET['jobdest-top'] );
		}
		if ( isset( $_GET['jobdets-button-bottom'] ) ) {
			$jobdest = sanitize_text_field( $_GET['jobdest-bottom'] );
		}

		if ( empty( $jobdest ) ) {
			$jobdests = $this->get_destinations_list();
			if ( empty( $jobdests ) ) {
				$jobdests = array( '_' );
			}
			$jobdest = $jobdests[0];
			$_GET['jobdest-top'] = $jobdests[0];
			$_GET['jobdets-button-top'] = 'empty';
		}

		list( $this->jobid, $this->dest ) = explode( '_', $jobdest );

		if ( ! empty( $this->destinations[ $this->dest ]['class'] ) ) {
			/** @var BackupPilot_Destinations $dest_object */
			$dest_object = BackupPilot::get_destination( $this->dest );
			$this->items = $dest_object->file_get_list( $jobdest );
		}

		if ( ! $this->items ) {
			$this->items = '';

			return;
		}

		// Sorting.
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'time';
		$tmp = array();

		if ( $orderby === 'time' ) {
			if ( $order === 'asc' ) {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma["time"];
				}
				array_multisort( $tmp, SORT_ASC, $this->items );
			} else {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma["time"];
				}
				array_multisort( $tmp, SORT_DESC, $this->items );
			}
		} elseif ( $orderby === 'file' ) {
			if ( $order === 'asc' ) {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma["filename"];
				}
				array_multisort( $tmp, SORT_ASC, $this->items );
			} else {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma["filename"];
				}
				array_multisort( $tmp, SORT_DESC, $this->items );
			}
		} elseif ( $orderby === 'folder' ) {
			if ( $order === 'asc' ) {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma["folder"];
				}
				array_multisort( $tmp, SORT_ASC, $this->items );
			} else {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma["folder"];
				}
				array_multisort( $tmp, SORT_DESC, $this->items );
			}
		} elseif ( $orderby === 'size' ) {
			if ( $order === 'asc' ) {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma["filesize"];
				}
				array_multisort( $tmp, SORT_ASC, $this->items );
			} else {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma["filesize"];
				}
				array_multisort( $tmp, SORT_DESC, $this->items );
			}
		}

		$this->set_pagination_args( array(
			'total_items' => count( $this->items ),
			'per_page' => $per_page,
			'jobdest' => $jobdest,
			'orderby' => $orderby,
			'order' => $order,
		) );

		// Only display items on page.
		$start = intval( ( $this->get_pagenum() - 1 ) * $per_page );
		$end = $start + $per_page;
		if ( $end > count( $this->items ) ) {
			$end = count( $this->items );
		}

		$i = - 1;
		$paged_items = array();
		foreach ( $this->items as $item ) {
			$i ++;
			if ( $i < $start ) {
				continue;
			}
			if ( $i >= $end ) {
				break;
			}
			$paged_items[] = $item;
		}

		$this->items = $paged_items;

	}

	public function no_items() {

		_e( 'No files could be found. (List will be generated during next backup.)', 'backuppilot' );
	}

	public function get_bulk_actions() {

		if ( ! $this->has_items() ) {
			return array();
		}

		$actions = array();
		$actions['delete'] = __( 'Delete', 'backuppilot' );

		return $actions;
	}

	public function extra_tablenav( $which ) {

		$destinations_list = $this->get_destinations_list();

		if ( count( $destinations_list ) < 1 ) {
			return;
		}

		if ( count( $destinations_list ) === 1 ) {
			echo '<input type="hidden" name="jobdest-' . $which . '" value="' . $destinations_list[0] . '">';

			return;
		}

		?>
		<div class="alignleft actions">
			<label for="jobdest-<?php echo esc_attr( $which ); ?>">
				<select name="jobdest-<?php echo esc_html( $which ); ?>" class="postform"
				        id="jobdest-<?php echo esc_attr( $which ); ?>">
					<?php
					foreach ( $destinations_list as $jobdest ) {
						list( $jobid, $dest ) = explode( '_', $jobdest );
						echo "\t<option value=\"" . $jobdest . "\" " . selected( $this->jobid . '_' . $this->dest,
								$jobdest,
								false ) . ">" . $dest . ": " . esc_html( BackupPilot_Option::get( $jobid,
								'name' ) ) . "</option>" . PHP_EOL;
					}
					?>
				</select>
			</label>
			<?php submit_button( __( 'Change destination', 'backuppilot' ),
				'secondary',
				'jobdets-button-' . $which,
				false,
				array( 'id' => 'query-submit-' . $which ) ); ?>
		</div>
		<?php
	}

	public function get_columns() {

		$posts_columns           = array();
		$posts_columns['cb']     = '<input type="checkbox" />';
		$posts_columns['time']   = __( 'Time', 'backuppilot' );
		$posts_columns['file']   = __( 'File', 'backuppilot' );
		$posts_columns['folder'] = __( 'Folder', 'backuppilot' );
		$posts_columns['size']   = __( 'Size', 'backuppilot' );

		return $posts_columns;
	}

	public function get_sortable_columns() {

		return array(
			'file' => array( 'file', false ),
			'folder' => 'folder',
			'size' => 'size',
			'time' => array( 'time', false ),
		);
	}

	public function column_cb( $item ) {

		return '<input type="checkbox" name="backupfiles[]" value="' . esc_attr( $item['file'] ) . '" />';
	}

	public function get_destinations_list() {

		$jobdest = array();
		$jobids = BackupPilot_Option::get_job_ids();

		foreach ( $jobids as $jobid ) {
			if ( BackupPilot_Option::get( $jobid, 'backuptype' ) === 'sync' ) {
				continue;
			}
			$dests = BackupPilot_Option::get( $jobid, 'destinations' );
			foreach ( $dests as $dest ) {
				if ( ! $this->destinations[ $dest ]['class'] ) {
					continue;
				}
				$dest_class = BackupPilot::get_destination( $dest );
				$can_do_dest = $dest_class->file_get_list( $jobid . '_' . $dest );
				if ( ! empty( $can_do_dest ) ) {
					$jobdest[] = $jobid . '_' . $dest;
				}
			}
		}

		return $jobdest;
	}

	public function column_file( $item ) {

		$actions = array();

		$r = '<strong>' . esc_attr( $item['filename'] ) . '</strong><br />';
		if ( ! empty( $item['info'] ) ) {
			$r .= esc_attr( $item['info'] ) . '<br />';
		}

		if ( current_user_can( 'backuppilot_backups_delete' ) ) {
			$actions['delete'] = $this->delete_item_action( $item );
		}

		if ( ! empty( $item['downloadurl'] ) && current_user_can( 'backuppilot_backups_download' ) ) {
			try {
                $actions['download'] = $this->download_item_action($item);
            } catch (BackupPilot_Factory_Exception $e) {
                $actions['download'] = sprintf(
                    '<a href="%1$s">%2$s</a>',
                    wp_nonce_url($item['downloadurl'], 'backuppilot_action_nonce'),
                    __('Download', 'backuppilot')
                );
            }
		}

		// Add restore url to link list
		if ( current_user_can( 'backuppilot_restore' ) && ! empty( $item['restoreurl'] ) ) {

			$item['restoreurl'] = add_query_arg(
				array(
					'step' => 1,
					'trigger_download' => 1,
				),
				$item['restoreurl']
			);
			$actions['restore'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				wp_nonce_url( $item['restoreurl'], 'restore-backup_' . $this->jobid ),
				__( 'Restore', 'backuppilot' )
			);
		}

		$r .= $this->row_actions( $actions );

		return $r;
	}

	public function column_folder( $item ) {

		return esc_attr( $item['folder'] );
	}

	public function column_size( $item ) {

		if ( ! empty( $item['filesize'] ) && $item['filesize'] != - 1 ) {
			return size_format( $item['filesize'], 2 );
		} else {
			return __( '?', 'backuppilot' );
		}
	}

	public function column_time( $item ) {

		return sprintf( __( '%1$s at %2$s', 'backuppilot' ),
			date_i18n( get_option( 'date_format' ), $item['time'], true ),
			date_i18n( get_option( 'time_format' ), $item['time'], true ) );
	}

	public static function load() {

		//Create Table
		self::$listtable = new BackupPilot_Page_Backups;

		switch ( self::$listtable->current_action() ) {
			case 'delete': //Delete Backup archives
				check_admin_referer( 'bulk-backups' );
				if ( ! current_user_can( 'backuppilot_backups_delete' ) ) {
					wp_die( __( 'Sorry, you don\'t have permissions to do that.', 'backuppilot' ) );
				}

				$jobdest = '';
				if ( isset( $_GET['jobdest'] ) ) {
					$jobdest = sanitize_text_field( $_GET['jobdest'] );
				}
				if ( isset( $_GET['jobdest-top'] ) ) {
					$jobdest = sanitize_text_field( $_GET['jobdest-top'] );
				}

				$_GET['jobdest-top'] = $jobdest;
				$_GET['jobdets-button-top'] = 'submit';

				if ( $jobdest === '' ) {
					return;
				}

				list( $jobid, $dest ) = explode( '_', $jobdest );
				/** @var BackupPilot_Destinations $dest_class */
				$dest_class = BackupPilot::get_destination( $dest );
				$files = $dest_class->file_get_list( $jobdest );
				foreach ( $_GET['backupfiles'] as $backupfile ) {
					foreach ( $files as $file ) {
						if ( is_array( $file ) && $file['file'] == $backupfile ) {
							$dest_class->file_delete( $jobdest, $backupfile );
						}
					}
				}
				$files = $dest_class->file_get_list( $jobdest );
				if ( empty ( $files ) ) {
					$_GET['jobdest-top'] = '';
				}
				break;
			default:
				if ( isset( $_GET['jobid'] ) ) {
					$jobid = absint( $_GET['jobid'] );
					if ( ! current_user_can( 'backuppilot_backups_download' ) ) {
						wp_die( __( 'Sorry, you don\'t have permissions to do that.', 'backuppilot' ) );
					}
					check_admin_referer( 'backuppilot_action_nonce' );

					$filename = untrailingslashit( BackupPilot::get_plugin_data( 'temp' ) ) . '/' . basename( isset( $_GET['local_file'] ) ? $_GET['local_file'] : $_GET['file'] );
					if ( file_exists( $filename ) ) {
						$downloader = new BackupPilot_Download_File(
							$filename,
							function ( \BackupPilot_Download_File_Interface $obj ) use ( $filename ) {

								$obj->clean_ob()
								    ->headers();

								readfile( $filename );

								// Delete the temporary file.
								unlink( $filename );
								die();
							},
							'backuppilot_backups_download'
						);
						$downloader->download();
					} else {
						// If the file doesn't exist, fallback to old way of downloading
						// This is for destinations without a downloader class
						$dest = strtoupper( str_replace( 'download', '', self::$listtable->current_action() ) );
						if ( ! empty( $dest ) && strstr( self::$listtable->current_action(), 'download' ) ) {
							/** @var BackupPilot_Destinations $dest_class */
							$dest_class = BackupPilot::get_destination( $dest );

							try {
								$dest_class->file_download( $jobid, trim( sanitize_text_field( $_GET['file'] ) ) );
							} catch ( BackupPilot_Destination_Download_Exception $e ) {
								header( 'HTTP/1.0 404 Not Found' );
								wp_die(
									esc_html__( 'Ops! Unfortunately the file doesn\'t exists. May be was deleted?', 'backuppilot' ),
									esc_html__( '404 - File Not Found.', 'backuppilot' ),
									array(
										'back_link' => esc_html__( '&laquo; Go back', 'backuppilot' ),
									)
								);
							}
						}
					}
				}
				break;
		}

		//Save per page
		if ( isset( $_POST['screen-options-apply'] ) && isset( $_POST['wp_screen_options']['option'] ) && isset( $_POST['wp_screen_options']['value'] ) && $_POST['wp_screen_options']['option'] == 'backuppilotbackups_per_page' ) {
			check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );
			global $current_user;
			if ( $_POST['wp_screen_options']['value'] > 0 && $_POST['wp_screen_options']['value'] < 1000 ) {
				update_user_option( $current_user->ID,
					'backuppilotbackups_per_page',
					(int) $_POST['wp_screen_options']['value'] );
				wp_redirect( remove_query_arg( array( 'pagenum', 'apage', 'paged' ), wp_get_referer() ) );
				exit;
			}
		}

		add_screen_option( 'per_page',
			array(
				'label' => __( 'Backup Files', 'backuppilot' ),
				'default' => 20,
				'option' => 'backuppilotbackups_per_page',
			) );

		self::$listtable->prepare_items();
	}

	public static function admin_print_styles() {

		?>
		<style type="text/css" media="screen">
			.column-size, .column-time {
				width: 10%;
			}

			@media screen and (max-width: 782px) {
				.column-size, .column-runtime, .column-size, .column-folder {
					display: none;
				}

				.column-time {
					width: 18%;
				}
			}
		</style>
		<?php
	}

	public static function admin_print_scripts() {

        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $plugin_url = BackupPilot::get_plugin_data( 'url' );
        $plugin_dir = BackupPilot::get_plugin_data( 'plugindir' );
        $plugin_scripts_url = "{$plugin_url}/assets/js";
        $plugin_scripts_dir = "{$plugin_dir}/assets/js";
        $shared_scripts_path = "{$plugin_url}/vendor/inpsyde/backwpup-shared/resources/js";
        $shared_scripts_dir = "{$plugin_dir}/vendor/inpsyde/backwpup-shared/resources/js";

        wp_register_script(
            'backuppilot_functions',
            "{$shared_scripts_path}/functions{$suffix}.js",
            array('underscore', 'jquery'),
            filemtime("{$shared_scripts_dir}/functions{$suffix}.js"),
            true
        );
        wp_register_script(
            'backuppilot_states',
            "{$shared_scripts_path}/states{$suffix}.js",
            array(
                'backuppilot_functions',
            ),
            filemtime("{$shared_scripts_dir}/states{$suffix}.js"),
            true
        );

		$dependencies = array(
			'jquery',
			'underscore',
			'backuppilotgeneral',
            'backuppilot_functions',
            'backuppilot_states',
		);
		wp_enqueue_script(
			'backuppilot-backup-downloader',
			"{$plugin_scripts_url}/backup-downloader{$suffix}.js",
			$dependencies,
			filemtime( "{$plugin_scripts_dir}/backup-downloader{$suffix}.js" ),
			true
		);
	}

	public static function page() {

		?>
		<div class="wrap" id="backuppilot-page">
			<h1><?php echo esc_html( sprintf( __( '%s &rsaquo; Manage Backup Archives', 'backuppilot' ), BackupPilot::get_plugin_data( 'name' ) ) ); ?></h1>
			<?php BackupPilot_Admin::display_messages(); ?>
			<form id="posts-filter" action="" method="get">
				<input type="hidden" name="page" value="backuppilotbackups"/>
				<?php self::$listtable->display(); ?>
				<div id="ajax-response"></div>
			</form>
		</div>

		<div id="tb_download_file" style="display: none;">
			<div id="tb_container">
				<p id="download-file-waiting">
					<?php esc_html_e( 'Please wait &hellip;', 'backuppilot' ) ?>
				</p>
				<p id="download-file-success" style="display: none;">
					<?php esc_html_e(
						'Your download has been generated. It should begin downloading momentarily.',
						'backuppilot'
					) ?>
				</p>
				<div class="progressbar" style="display: none;">
					<div id="progresssteps" class="bwpu-progress" style="width:0%;">0%</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function delete_item_action( $item ) {

		$query = sprintf(
			'?page=backuppilotbackups&action=delete&jobdest-top=%1$s&paged=%2$s&backupfiles[]=%3$s',
			$this->jobid . '_' . $this->dest,
			$this->get_pagenum(),
			esc_attr( $item['file'] )
		);
		$url = wp_nonce_url( network_admin_url( 'admin.php' ) . $query, 'bulk-backups' );
		$js = sprintf(
			'if ( confirm(\'%s\') ) { return true; } return false;',
			esc_js(
				__(
					'You are about to delete this backup archive. \'Cancel\' to stop, \'OK\' to delete.',
					"backuppilot"
				)
			)
		);

		return sprintf(
			'<a class="submitdelete" href="%1$s" onclick="%2$s">%3$s</a>',
			$url,
			$js,
			__( 'Delete', 'backuppilot' )
		);
	}

	private function download_item_action( $item ) {

		$local_file = untrailingslashit( BackupPilot::get_plugin_data( 'TEMP' ) ) . "/{$item['filename']}";

		return sprintf(
			'<a href="#TB_inline?height=300&width=630&inlineId=tb_download_file" 
				class="backup-download-link thickbox" 
				id="backup-download-link"
				data-jobid="%1$s" 
				data-destination="%2$s" 
				data-file="%3$s" 
				data-local-file="%4$s" 
				data-nonce="%5$s" 
				data-url="%6$s">%7$s</a>',
			intval( $this->jobid ),
			esc_attr( $this->dest ),
			esc_attr( $item['file'] ),
			esc_attr( $local_file ),
			wp_create_nonce( 'backuppilot_action_nonce' ),
			wp_nonce_url( $item['downloadurl'], 'backuppilot_action_nonce' ),
			__( 'Download', 'backuppilot' )
		);
	}
}
