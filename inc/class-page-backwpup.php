<?php
/**
 * Render plugin dashboard.
 *
 */
class BackupPilot_Page_BackupPilot {


	/**
	 * Called on load action.
	 *
	 * @return void
	 */
	public static function load() {
		global $wpdb;

		if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'dbdumpdl' ) {

			//check permissions
			check_admin_referer( 'backuppilotdbdumpdl' );

			if ( ! current_user_can( 'backuppilot_jobs_edit' ) )
				die();

			//doing dump
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Content-Type: application/octet-stream; charset=". get_bloginfo( 'charset' ) );
			header( "Content-Disposition: attachment; filename=" . DB_NAME . ".sql;" );
			try {
				$sql_dump = new BackupPilot_MySQLDump();
				foreach ( $sql_dump->tables_to_dump as $key => $table ) {
					if ( $wpdb->prefix != substr( $table,0 , strlen( $wpdb->prefix ) ) )
						unset( $sql_dump->tables_to_dump[ $key ] );
				}
				$sql_dump->execute();
				unset( $sql_dump );
			} catch ( Exception $e ) {
				die( $e->getMessage() );
			}
			die();
		}
	}

	/**
	 * Enqueue script.
	 *
	 * @return void
	 */
	public static function admin_print_scripts() {

		wp_enqueue_script( 'backuppilotgeneral' );

	}

	/**
	 * Print the markup.
	 *
	 * @return void
	 */
	public static function page() {
		// get wizards
		$wizards = BackupPilot::get_wizards();
		?>
        <div class="wrap" id="backuppilot-page">
            <h1><?php echo sprintf( __( '%s &rsaquo; Dashboard', 'backuppilot' ), BackupPilot::get_plugin_data( 'name') ); ?></h1>
			<?php

			BackupPilot_Admin::display_messages();

			?>
				<div class="backuppilot-welcome backuppilot-max-width">
					<h3><?php _ex( 'Planning backups', 'Dashboard heading', 'backuppilot' ); ?></h3>
					<p><?php _e('Use the short links in the <strong>First steps</strong> box to plan and schedule backup jobs.','backuppilot' ); echo ' '; _e('Use your backup archives to save your entire WordPress installation including <code>/wp-content/</code>. Push them to an external storage service if you don’t want to save the backups on the same server.','backuppilot'); ?></p>
					<h3><?php _ex( 'Restoring backups', 'Dashboard heading', 'backuppilot' ); ?></h3>
					<p><?php _e( 'With a single backup archive you are able to restore an installation.', 'backuppilot' ) ?></p>
					<h3><?php _ex( 'Ready to set up a backup job?', 'Dashboard heading','backuppilot' ); ?></h3>
					<p><?php printf( __('<a href="%s">Add a new backup job</a> and plan what you want to save.','backuppilot'), network_admin_url( 'admin.php') . '?page=backuppiloteditjob' ); ?>
					<br /><?php _e( '<strong>Please note: You are solely responsible for the security of your data; the authors of this plugin are not.</strong>', 'backuppilot' ); ?></p>
				</div>
			<?php

			if ( current_user_can( 'backuppilot_jobs_edit' ) && current_user_can( 'backuppilot_logs' ) && current_user_can( 'backuppilot_jobs_start' ) ) {
			?>
				<div  id="backuppilot-first-steps" class="metabox-holder postbox backuppilot-floated-postbox">
					<h3 class="hndle"><span><?php  _e( 'First Steps', 'backuppilot' ); ?></span></h3>
					<div class="inside">
						<ul>
                           		<li type="1"><a href="<?php echo network_admin_url( 'admin.php' ) . '?page=backuppilotsettings#backuppilot-tab-information'; ?>"><?php  _e( 'Check the installation', 'backuppilot' ); ?></a></li>
                            	<li type="1"><a href="<?php echo network_admin_url( 'admin.php' ) . '?page=backuppiloteditjob'; ?>"><?php  _e( 'Create a Job', 'backuppilot' ); ?></a></li>
							<li type="1"><a href="<?php echo network_admin_url( 'admin.php' ) . '?page=backuppilotjobs'; ?>"><?php  _e( 'Run the created job', 'backuppilot' ); ?></a></li>
							<li type="1"><a href="<?php echo network_admin_url( 'admin.php' ) . '?page=backuppilotlogs'; ?>"><?php  _e( 'Check the job log', 'backuppilot' ); ?></a></li>
						</ul>
					</div>
				</div>
			<?php }

			if ( current_user_can( 'backuppilot_jobs_start' ) ) {?>
				<div id="backuppilot-one-click-backup" class="metabox-holder postbox backuppilot-floated-postbox">
					<h3 class="hndle"><span><?php esc_html_e( 'One click backup', 'backuppilot' ); ?></span></h3>
					<div class="inside">
						<a href="<?php echo wp_nonce_url( network_admin_url( 'admin.php?page=backuppilot&action=dbdumpdl' ), 'backuppilotdbdumpdl' ); ?>" class="button button-primary button-primary-bwp" title="<?php esc_attr_e( 'Generate a database backup of WordPress tables and download it right away!', 'backuppilot' ); ?>"><?php esc_html_e( 'Download database backup', 'backuppilot' ); ?></a><br />
					</div>
				</div>
			<?php } ?>

			<?php /**
			<div id="backuppilot-rss-feed" class="metabox-holder postbox backuppilot-cleared-postbox backuppilot-max-width">
				<h3 class="hndle"><span><?php esc_attr_e( 'BackupPilot News', 'backuppilot' ); ?></span></h3>
				<div class="inside">
					<?php

						$rss = fetch_feed( _x( 'https://backwpup.com/feed/', 'BackupPilot News RSS Feed URL', 'backuppilot' ) );

						if ( is_wp_error( $rss ) ) {
							echo '<p>' . sprintf( __('<strong>RSS Error</strong>: %s', 'backuppilot' ), $rss->get_error_message() ) . '</p>';
						} elseif ( ! $rss->get_item_quantity() ) {
							echo '<ul><li>' . esc_html__( 'An error has occurred, which probably means the feed is down. Try again later.', 'backuppilot' ) . '</li></ul>';
							$rss->__destruct();
							unset( $rss );
						} else {
							echo '<ul>';
							$first = TRUE;
							foreach ( $rss->get_items( 0, 4 ) as $item ) {
								$link = $item->get_link();
								while ( stristr($link, 'http') != $link ) {
									$link = substr($link, 1);
								}
								$link = esc_url(strip_tags($link));
								$title = esc_attr(strip_tags($item->get_title()));
								if ( empty($title) ) {
									$title = __( 'Untitled', 'backuppilot' );
								}

								$desc = str_replace( array("\n", "\r"), ' ', esc_attr( strip_tags( @html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) ) ) ) );
								$excerpt = wp_html_excerpt( $desc, 360 );

								// Append ellipsis. Change existing [...] to [&hellip;].
								if ( '[...]' == substr( $excerpt, -5 ) )
									$excerpt = substr( $excerpt, 0, -5 ) . '[&hellip;]';
								elseif ( '[&hellip;]' != substr( $excerpt, -10 ) && $desc != $excerpt )
									$excerpt .= ' [&hellip;]';

								$excerpt = esc_html( $excerpt );

								if ( $first ) {
									$summary = "<div class='rssSummary'>$excerpt</div>";
								} else {
									$summary = '';
								}

								$date = '';
								if ( $first ) {
									$date = $item->get_date( 'U' );

									if ( $date ) {
										$date = ' <span class="rss-date">' . date_i18n( get_option( 'date_format' ), $date ) . '</span>';
									}
								}

								echo "<li><a href=\"$link\" title=\"$desc\">$title</a>{$date}{$summary}</li>";
								$first = FALSE;
							}
							echo '</ul>';
							$rss->__destruct();
							unset($rss);
						}
					?>
				</div>
			</div>
			/*__*/ ?>

			<div id="backuppilot-stats" class="metabox-holder postbox backuppilot-cleared-postbox backuppilot-max-width">
				<div class="backuppilot-table-wrap">
				<?php
					self::mb_next_jobs();
					self::mb_last_logs();
				?>
				</div>
			</div>
        </div>
	<?php
	}

	/**
	 * Displaying next jobs
	 */
	private static function mb_next_jobs() {

		if ( ! current_user_can( 'backuppilot_jobs' ) )
			return;
		?>
		<table class="wp-list-table widefat" cellspacing="0">
			<caption><?php _e( 'Next scheduled jobs', 'backuppilot' ); ?></caption>
			<thead>
			<tr>
				<th style="width: 30%"><?php  esc_html_e( 'Time', 'backuppilot' ); ?></th>
				<th style="width: 70%"><?php  esc_html_e( 'Job', 'backuppilot' ); ?></th>
			</tr>
			</thead>
			<?php
			//get next jobs
			$mainsactive = BackupPilot_Option::get_job_ids( 'activetype', 'wpcron' );
			sort( $mainsactive );
			$alternate = TRUE;
			// add working job if it not in active jobs
			$job_object = BackupPilot_Job::get_working_data();
			if ( ! empty( $job_object ) && ! empty( $job_object->job[ 'jobid' ] ) && ! in_array($job_object->job[ 'jobid' ], $mainsactive, true ) )
				$mainsactive[ ] = $job_object->job[ 'jobid' ];
			foreach ( $mainsactive as $jobid ) {
				$name = BackupPilot_Option::get( $jobid, 'name' );
				if ( ! empty( $job_object ) && $job_object->job[ 'jobid' ] == $jobid ) {
					$runtime  = current_time( 'timestamp' ) -  $job_object->job[ 'lastrun' ];
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = TRUE;
					} else {
						echo '<tr class="alternate">';
						$alternate = FALSE;
					}
					echo '<td>' . sprintf( '<span style="color:#e66f00;">' . esc_html__( 'working since %d seconds', 'backuppilot' ) . '</span>', $runtime ) . '</td>';
					echo '<td><span style="font-weight:bold;">' . esc_html ( $job_object->job[ 'name' ] ) . '</span><br />';
					echo "<a style=\"color:red;\" href=\"" . wp_nonce_url( network_admin_url( 'admin.php?page=backuppilotjobs&action=abort'), 'abort-job' ) . "\">" . esc_html__( 'Abort', 'backuppilot' ) . "</a>";
					echo "</td></tr>";
				}
				else {
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = TRUE;
					} else {
						echo '<tr class="alternate">';
						$alternate = FALSE;
					}
					if ( $nextrun = wp_next_scheduled( 'backuppilot_cron', array( 'arg' => $jobid ) ) + ( get_option( 'gmt_offset' ) * 3600 ) )
						echo '<td>' . sprintf( __( '%1$s at %2$s', 'backuppilot' ), date_i18n( get_option( 'date_format' ), $nextrun, TRUE ), date_i18n( get_option( 'time_format' ), $nextrun, TRUE ) ) . '</td>';
					else
						echo '<td><em>' . esc_html__( 'Not scheduled!', 'backuppilot' ) . '</em></td>';

					echo '<td><a href="' . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backuppiloteditjob&jobid=' . $jobid, 'edit-job' ) . '" title="' . esc_attr( __( 'Edit Job', 'backuppilot' ) ) . '">' . esc_html($name) . '</a></td></tr>';
				}
			}
			if ( empty( $mainsactive ) and ! empty( $job_object ) ) {
				echo '<tr><td colspan="2"><i>' . esc_html__( 'none', 'backuppilot' ) . '</i></td></tr>';
			}
			?>
		</table>
		<?php
	}

	/**
	 * Displaying last logs
	 */
	private static function mb_last_logs() {

		if ( ! current_user_can( 'backuppilot_logs' ) )
			return;
		?>
		<table class="wp-list-table widefat" cellspacing="0">
			<caption><?php esc_html_e( 'Last logs', 'backuppilot' ); ?></caption>
			<thead>
			<tr><th style="width:30%"><?php esc_html_e( 'Time', 'backuppilot' ); ?></th><th style="width:55%"><?php  esc_html_e( 'Job', 'backuppilot' ); ?></th><th style="width:20%"><?php  esc_html_e( 'Result', 'backuppilot' ); ?></th></tr>
			</thead>
			<?php
			//get log files
			$logfiles = array();
			$log_folder = get_site_option( 'backuppilot_cfg_logfolder' );
			$log_folder = BackupPilot_File::get_absolute_path( $log_folder );
			if ( is_readable( $log_folder ) ) {
				try {
					$dir = new BackupPilot_Directory( $log_folder );
					foreach ( $dir as $file ) {
						if ( $file->isReadable() && $file->isFile() && strpos( $file->getFilename(), 'backuppilot_log_' ) !== false && strpos( $file->getFilename(), '.html' ) !== false ) {
							$logfiles[ $file->getMTime() ] = clone $file;
						}
					}
					krsort( $logfiles, SORT_NUMERIC );
				}
					catch ( UnexpectedValueException $e ) {
					echo '<tr><td colspan="3"><span style="color:red;font-weight:bold;">' .
						sprintf( __( 'Could not open log folder: %s', 'backuppilot' ), $log_folder ) .
						'</td></tr>';
				}
			}

			if ( count( $logfiles ) > 0 ) {
				$count = 0;
				$alternate = TRUE;
				foreach ( $logfiles as $logfile ) {
					$logdata = BackupPilot_Job::read_logheader( $logfile->getPathname() );
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = TRUE;
					}
					else {
						echo '<tr class="alternate">';
						$alternate = FALSE;
					}
					echo '<td>' . sprintf( __( '%1$s at %2$s', 'backuppilot' ), date_i18n( get_option( 'date_format' ) , $logdata[ 'logtime' ] ), date_i18n( get_option( 'time_format' ), $logdata[ 'logtime' ] ) ) . '</td>';
					$log_name = str_replace( array( '.html', '.gz' ), '', $logfile->getBasename() );
					echo '<td><a class="thickbox" href="' . admin_url( 'admin-ajax.php?action=backuppilot_view_log&log=' . $log_name .'&_ajax_nonce=' . wp_create_nonce( 'view-log_' . $log_name ) . '&TB_iframe=true&width=640&height=440' ) . '" title="' . esc_attr( $logfile->getBasename() ) . '">' . esc_html( $logdata['name'] ) . '</i></a></td>';
					echo '<td>';
					if ( $logdata['errors'] ) {
						printf( '<span style="color:red;font-weight:bold;">' . _n( "%d ERROR", "%d ERRORS", $logdata['errors'], 'backuppilot' ) . '</span><br />', $logdata[ 'errors' ] );
					}
					if ( $logdata['warnings'] ) {
						printf( '<span style="color:#e66f00;font-weight:bold;">' . _n( "%d WARNING", "%d WARNINGS", $logdata['warnings'], 'backuppilot' ) . '</span><br />', $logdata['warnings'] );
					}
					if ( ! $logdata['errors'] && ! $logdata['warnings'] ) {
						echo '<span style="color:green;font-weight:bold;">' . __( 'OK', 'backuppilot' ) . '</span>';
					}
					echo '</td></tr>';
					$count ++;
					if ( $count >= 5 )
						break;
				}
			}
			else {
				echo '<tr><td colspan="3">' . __( 'none', 'backuppilot' ) . '</td></tr>';
			}
			?>
		</table>
		<?php
	}

}
