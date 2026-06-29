<?php

class BackupPilot_Destination_Ftp extends BackupPilot_Destinations {

	/**
	 * FTP Connection Resource
	 *
	 * @var resource
	 */
	private $ftp_conn_id;

	/**
	 * Default Options
	 *
	 * @return array The default options
	 */
	public function option_defaults() {

		return array(
			'ftphost'       => '',
			'ftphostport'   => 21,
			'ftptimeout'    => 90,
			'ftpuser'       => '',
			'ftppass'       => '',
			'ftpdir'        => trailingslashit( sanitize_title_with_dashes( get_bloginfo( 'name' ) ) ),
			'ftpmaxbackups' => 15,
			'ftppasv'       => true,
			'ftpssl'        => false,
		);
	}

	/**
	 * Edit Tab
	 *
	 * @param int $jobid The job ID.
	 *
	 * @return void
	 */
	public function edit_tab( $jobid ) {

		?>

		<h3 class="title"><?php esc_html_e( 'FTP server and login', 'backuppilot' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="idftphost"><?php esc_html_e( 'FTP server', 'backuppilot' ); ?></label></th>
				<td>
					<input id="idftphost" name="ftphost" type="text"
						value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'ftphost' ) ); ?>"
						class="regular-text" autocomplete="off" />
					&nbsp;&nbsp;
					<label for="idftphostport"><?php esc_html_e( 'Port:', 'backuppilot' ); ?>
						<input name="ftphostport" id="idftphostport" type="number" step="1" min="1" max="66000"
							value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'ftphostport' ) ); ?>"
							class="small-text" /></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="idftpuser"><?php esc_html_e( 'Username', 'backuppilot' ); ?></label></th>
				<td>
					<input id="idftpuser" name="ftpuser" type="text"
						value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'ftpuser' ) ); ?>"
						class="user regular-text" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="idftppass"><?php esc_html_e( 'Password', 'backuppilot' ); ?></label></th>
				<td>
					<input id="idftppass" name="ftppass" type="password"
						value="<?php echo esc_attr(
							BackupPilot_Encryption::decrypt( BackupPilot_Option::get( $jobid, 'ftppass' ) )
						); ?>"
						class="password regular-text" autocomplete="off" />
				</td>
			</tr>
		</table>

		<h3 class="title"><?php esc_html_e( 'Backup settings', 'backuppilot' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label
						for="idftpdir"><?php esc_html_e( 'Folder to store files in', 'backuppilot' ); ?></label></th>
				<td>
					<input id="idftpdir" name="ftpdir" type="text"
						value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'ftpdir' ) ); ?>"
						class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'File Deletion', 'backuppilot' ); ?></th>
				<td>
					<?php
					if ( BackupPilot_Option::get( $jobid, 'backuptype' ) === 'archive' ) {
						?>
						<label for="idftpmaxbackups">
							<input id="idftpmaxbackups" name="ftpmaxbackups" type="number" min="0" step="1"
								value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'ftpmaxbackups' ) ); ?>"
								class="small-text" />
							&nbsp;<?php esc_html_e( 'Number of files to keep in folder.', 'backuppilot' ); ?>
						</label>
						<p><?php _e(
								'<strong>Warning</strong>: Files belonging to this job are now tracked. Old backup archives which are untracked will not be automatically deleted.',
								'backuppilot'
							) ?></p>
					<?php } else { ?>
						<label for="idftpsyncnodelete">
							<input class="checkbox" value="1"
								type="checkbox" <?php checked(
								BackupPilot_Option::get( $jobid, 'ftpsyncnodelete' ),
								true
							); ?>
								name="ftpsyncnodelete" id="idftpsyncnodelete" />
							&nbsp;<?php esc_html_e(
								'Do not delete files while syncing to destination!',
								'backuppilot'
							); ?>
						</label>
					<?php } ?>
				</td>
			</tr>
		</table>

		<h3 class="title"><?php esc_html_e( 'FTP specific settings', 'backuppilot' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label
						for="idftptimeout"><?php esc_html_e( 'Timeout for FTP connection', 'backuppilot' ); ?></label></th>
				<td>
					<input id="idftptimeout" name="ftptimeout" type="number" step="1" min="1" max="300"
						value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'ftptimeout' ) ); ?>"
						class="small-text" /> <?php esc_html_e( 'seconds', 'backuppilot' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'SSL-FTP connection', 'backuppilot' ); ?></th>
				<td>
					<label for="idftpssl"><input class="checkbox" value="1"
							type="checkbox" <?php checked( BackupPilot_Option::get( $jobid, 'ftpssl' ), true ); ?>
							id="idftpssl"
							name="ftpssl"<?php if ( ! function_exists( 'ftp_ssl_connect' ) ) {
							echo " disabled=\"disabled\"";
						} ?> /> <?php esc_html_e( 'Use explicit SSL-FTP connection.', 'backuppilot' ); ?></label>

				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'FTP Passive Mode', 'backuppilot' ); ?></th>
				<td>
					<label for="idftppasv"><input class="checkbox" value="1"
							type="checkbox" <?php checked( BackupPilot_Option::get( $jobid, 'ftppasv' ), true ); ?>
							name="ftppasv"
							id="idftppasv" /> <?php esc_html_e( 'Use FTP Passive Mode.', 'backuppilot' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * Save Settings
	 *
	 * @param int $id The ID of the settings to save.
	 *
	 * @return void
	 */
	public function edit_form_post_save( $id ) {

		$_POST['ftphost'] = str_replace( array( 'http://', 'ftp://' ), '', sanitize_text_field( $_POST['ftphost'] ) );
		BackupPilot_Option::update( $id, 'ftphost', isset( $_POST['ftphost'] ) ? $_POST['ftphost'] : '' );

		BackupPilot_Option::update(
			$id,
			'ftphostport',
			! empty( $_POST['ftphostport'] ) ? absint( $_POST['ftphostport'] ) : 21
		);
		BackupPilot_Option::update(
			$id,
			'ftptimeout',
			! empty( $_POST['ftptimeout'] ) ? absint( $_POST['ftptimeout'] ) : 90
		);
		BackupPilot_Option::update( $id, 'ftpuser', sanitize_text_field( $_POST['ftpuser'] ) );
		BackupPilot_Option::update( $id, 'ftppass', BackupPilot_Encryption::encrypt( $_POST['ftppass'] ) );

		if ( ! empty( $_POST['ftpdir'] ) ) {
			$_POST['ftpdir'] = trailingslashit(
				str_replace( '//', '/', str_replace( '\\', '/', trim( sanitize_text_field( $_POST['ftpdir'] ) ) ) )
			);
		}
		BackupPilot_Option::update( $id, 'ftpdir', $_POST['ftpdir'] );

		BackupPilot_Option::update(
			$id,
			'ftpmaxbackups',
			! empty( $_POST['ftpmaxbackups'] ) ? absint( $_POST['ftpmaxbackups'] ) : 0
		);

		if ( function_exists( 'ftp_ssl_connect' ) ) {
			BackupPilot_Option::update( $id, 'ftpssl', ! empty( $_POST['ftpssl'] ) );
		} else {
			BackupPilot_Option::update( $id, 'ftpssl', false );
		}

		BackupPilot_Option::update( $id, 'ftppasv', ! empty( $_POST['ftppasv'] ) );
	}

	/**
	 * Delete File
	 *
	 * @param string $jobdest    The job destination identifier.
	 * @param string $backupfile The file to delete.
	 *
	 * @return void
	 */
	public function file_delete( $jobdest, $backupfile ) {

		$files = get_site_transient( 'backuppilot_' . strtolower( $jobdest ) );
		list( $jobid, $dest ) = explode( '_', $jobdest );

		$job_options = (object) BackupPilot_Option::get_job( $jobid );
		$service     = new BackupPilot_Destination_Ftp_Connect(
			$job_options->ftphost,
			$job_options->ftpuser,
			BackupPilot_Encryption::decrypt( $job_options->ftppass ),
			$job_options->ftphostport,
			$job_options->ftptimeout,
			$job_options->ftpssl,
			$job_options->ftppasv
		);

		$resource = $service
			->connect()
			->resource();

		// Delete file.
		ftp_delete( $resource, $backupfile );

		// Update file list of existing files.
		foreach ( $files as $key => $file ) {
			if ( is_array( $file ) && $file['file'] == $backupfile ) {
				unset( $files[ $key ] );
			}
		}

		set_site_transient( 'backuppilot_' . strtolower( $jobdest ), $files, YEAR_IN_SECONDS );
	}

	/**
	 * @inheritdoc
	 */
	public function file_get_list( $jobdest ) {

		$list = (array) get_site_transient( 'backuppilot_' . strtolower( $jobdest ) );
		$list = array_filter( $list );

		return $list;
	}

	/**
	 * File Update List
	 *
	 * Update the list of files in the transient.
	 *
	 * @param BackupPilot_Job|int $job    Either the job object or job ID
	 * @param bool             $delete Whether to delete old backups.
	 */
	public function file_update_list( $job, $delete = false ) {

		if ( $job instanceof BackupPilot_Job ) {
			$job_object = $job;
			$jobid      = $job->job['jobid'];
		} else {
			$job_object = null;
			$jobid = $job;
		}

		if ( ! $this->ftp_conn_id ) {
			$ftp_ssl = BackupPilot_Option::get( $jobid, 'ftpssl' );
			if ( ! empty( $ftp_ssl )
				&& function_exists( 'ftp_ssl_connect' ) ) {
				$ftp_conn_id = ftp_ssl_connect(
					BackupPilot_Option::get( $jobid, 'ftphost' ),
					BackupPilot_Option::get( $jobid, 'ftphostport' ),
					BackupPilot_Option::get( $jobid, 'ftptimeout' )
				);
			} else { //make normal FTP connection if SSL not work
				$ftp_conn_id = ftp_connect(
					BackupPilot_Option::get( $jobid, 'ftphost' ),
					BackupPilot_Option::get( $jobid, 'ftphostport' ),
					BackupPilot_Option::get( $jobid, 'ftptimeout' )
				);
			}

			//FTP Login
			if ( $loginok = @ftp_login(
				$ftp_conn_id,
				BackupPilot_Option::get( $jobid, 'ftpuser' ),
				BackupPilot_Encryption::decrypt( BackupPilot_Option::get( $jobid, 'ftppass' ) )
			) ) {
			} else { //if PHP ftp login don't work use raw login
				$return = ftp_raw( $ftp_conn_id, 'USER ' . BackupPilot_Option::get( $jobid, 'ftpuser' ) );
				if ( substr( trim( $return[0] ), 0, 3 ) <= 400 ) {
					$return = ftp_raw(
						$ftp_conn_id,
						'PASS ' . BackupPilot_Encryption::decrypt( BackupPilot_Option::get( $jobid, 'ftppass' ) )
					);
					if ( substr( trim( $return[0] ), 0, 3 ) <= 400 ) {
						$loginok = true;
					}
				}
			}

			if ( ! $loginok ) {
				throw new Exception( __( 'Could not log in to FTP server.', 'backuppilot' ) );
			}

			//set actual ftp dir to ftp dir
			$ftp_dir = BackupPilot_Option::get( $jobid, 'ftpdir' );
			if ( empty( $ftp_dir ) ) {
				$ftp_dir = trailingslashit( ftp_pwd( $ftp_conn_id ) );
			}
			// prepend actual ftp dir if relative dir
			if ( substr( $ftp_dir, 0, 1 ) != '/' ) {
				$ftp_dir = trailingslashit( ftp_pwd( $ftp_conn_id ) ) . $ftp_dir;
			}
			ftp_chdir( $ftp_conn_id, $ftp_dir );

			if ( BackupPilot_Option::get( $jobid, 'ftppasv' ) ) {
				ftp_pasv( $ftp_conn_id, true );
			} else {
				ftp_pasv( $ftp_conn_id, false );
			}

		} else {
			$ftp_conn_id = $this->ftp_conn_id;
			$ftp_dir = $job_object->job['ftpdir'];
		}

		$backupfilelist = array();
		$filecounter    = 0;
		$files          = array();
		if ( $filelist = ftp_nlist( $ftp_conn_id, '.' ) ) {
			foreach ( $filelist as $file ) {
				if ( basename( $file ) != '.' && basename( $file ) != '..' ) {
					if ( $this->is_backup_archive( $file )
						&& $this->is_backup_owned_by_job(
							$file, $jobid
						) == true ) {
						$time = ftp_mdtm( $ftp_conn_id, $file );
						if ( $time != - 1 ) {
							$backupfilelist[ $time ] = basename( $file );
						} else {
							$backupfilelist[] = basename( $file );
						}
					}
					$files[ $filecounter ]['folder']      = 'ftp://' . BackupPilot_Option::get( $jobid, 'ftphost' ) . ':' . BackupPilot_Option::get( $jobid, 'ftphostport' ) . $ftp_dir;
					$files[ $filecounter ]['file']        = trailingslashit( $ftp_dir ) . basename( $file );
					$files[ $filecounter ]['filename']    = basename( $file );
					$files[ $filecounter ]['downloadurl'] = network_admin_url(
						'admin.php?page=backuppilotbackups&action=downloadftp&file=' . trailingslashit( $ftp_dir ) . basename( $file ) . '&local_file=' . basename( $file ) . '&jobid=' . $jobid
					);
					$files[ $filecounter ]['filesize']    = ftp_size( $ftp_conn_id, $file );
					$files[ $filecounter ]['time']        = ftp_mdtm( $ftp_conn_id, $file );
					$filecounter ++;
				}
			}
		}

		if ( $delete && $job_object && ! empty( $job_object->job['ftpmaxbackups'] ) && $job_object->job['ftpmaxbackups'] > 0 ) { //Delete old backups
			if ( count( $backupfilelist ) > $job_object->job['ftpmaxbackups'] ) {
				ksort( $backupfilelist );
				$numdeltefiles = 0;
				while ( $file = array_shift( $backupfilelist ) ) {
					if ( count( $backupfilelist ) < $job_object->job['ftpmaxbackups'] ) {
						break;
					}
					if ( ftp_delete( $ftp_conn_id, $file ) ) { //delete files on ftp
						foreach ( $files as $key => $filedata ) {
							if ( $filedata['file'] == trailingslashit( $job_object->job['ftpdir'] ) . $file ) {
								unset( $files[ $key ] );
							}
						}
						$numdeltefiles ++;
					} else {
						$job_object->log(
							sprintf(
								__( 'Cannot delete "%s" on FTP server!', 'backuppilot' ),
								$job_object->job['ftpdir'] . $file
							),
							E_USER_ERROR
						);
					}
				}
				if ( $numdeltefiles > 0 ) {
					$job_object->log(
						sprintf(
							_n(
								'One file deleted on FTP server',
								'%d files deleted on FTP server',
								$numdeltefiles,
								'backuppilot'
							),
							$numdeltefiles
						),
						E_USER_NOTICE
					);
				}
			}
		}
		set_site_transient( 'backuppilot_' . $jobid . '_ftp', $files, YEAR_IN_SECONDS );
	}

	/**
	 * Run Job
	 *
	 * @param BackupPilot_Job $job_object The instance of the job to use.
	 *
	 * @return bool True on success false on error
	 */
	public function job_run_archive( BackupPilot_Job $job_object ) {

		$job_object->substeps_todo = 2 + $job_object->backup_filesize;
		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) {
			$job_object->log(
				sprintf(
					__( '%d. Try to send backup file to an FTP server&#160;&hellip;', 'backuppilot' ),
					$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
				),
				E_USER_NOTICE
			);
		}

		if ( ! empty( $job_object->job['ftpssl'] ) ) { //make SSL FTP connection
			if ( function_exists( 'ftp_ssl_connect' ) ) {
				$ftp_conn_id = ftp_ssl_connect(
					$job_object->job['ftphost'],
					$job_object->job['ftphostport'],
					$job_object->job['ftptimeout']
				);
				if ( $ftp_conn_id ) {
					$job_object->log(
						sprintf(
							__( 'Connected via explicit SSL-FTP to server: %s', 'backuppilot' ),
							$job_object->job['ftphost'] . ':' . $job_object->job['ftphostport']
						),
						E_USER_NOTICE
					);
				} else {
					$job_object->log(
						sprintf(
							__( 'Cannot connect via explicit SSL-FTP to server: %s', 'backuppilot' ),
							$job_object->job['ftphost'] . ':' . $job_object->job['ftphostport']
						),
						E_USER_ERROR
					);

					return false;
				}
			} else {
				$job_object->log(
					__( 'PHP function to connect with explicit SSL-FTP to server does not exist!', 'backuppilot' ),
					E_USER_ERROR
				);

				return true;
			}
		} else { //make normal FTP connection if SSL not work
			$ftp_conn_id = ftp_connect(
				$job_object->job['ftphost'],
				$job_object->job['ftphostport'],
				$job_object->job['ftptimeout']
			);
			if ( $ftp_conn_id ) {
				$job_object->log(
					sprintf(
						__( 'Connected to FTP server: %s', 'backuppilot' ),
						$job_object->job['ftphost'] . ':' . $job_object->job['ftphostport']
					),
					E_USER_NOTICE
				);
			} else {
				$job_object->log(
					sprintf(
						__( 'Cannot connect to FTP server: %s', 'backuppilot' ),
						$job_object->job['ftphost'] . ':' . $job_object->job['ftphostport']
					),
					E_USER_ERROR
				);

				return false;
			}
		}

		//FTP Login
		$job_object->log(
			sprintf( __( 'FTP client command: %s', 'backuppilot' ), 'USER ' . $job_object->job['ftpuser'] ),
			E_USER_NOTICE
		);
		if ( $loginok = @ftp_login(
			$ftp_conn_id,
			$job_object->job['ftpuser'],
			BackupPilot_Encryption::decrypt( $job_object->job['ftppass'] )
		) ) {
			$job_object->log(
				sprintf(
					__( 'FTP server response: %s', 'backuppilot' ),
					'User ' . $job_object->job['ftpuser'] . ' logged in.'
				),
				E_USER_NOTICE
			);
		} else { //if PHP ftp login don't work use raw login
			$return = ftp_raw( $ftp_conn_id, 'USER ' . $job_object->job['ftpuser'] );
			$job_object->log( sprintf( __( 'FTP server reply: %s', 'backuppilot' ), $return[0] ), E_USER_NOTICE );
			if ( substr( trim( $return[0] ), 0, 3 ) <= 400 ) {
				$job_object->log(
					sprintf( __( 'FTP client command: %s', 'backuppilot' ), 'PASS *******' ),
					E_USER_NOTICE
				);
				$return = ftp_raw(
					$ftp_conn_id,
					'PASS ' . BackupPilot_Encryption::decrypt( $job_object->job['ftppass'] )
				);
				if ( substr( trim( $return[0] ), 0, 3 ) <= 400 ) {
					$job_object->log( sprintf( __( 'FTP server reply: %s', 'backuppilot' ), $return[0] ), E_USER_NOTICE );
					$loginok = true;
				} else {
					$job_object->log( sprintf( __( 'FTP server reply: %s', 'backuppilot' ), $return[0] ), E_USER_ERROR );
				}
			}
		}

		if ( ! $loginok ) {
			return false;
		}

		$this->ftp_conn_id = $ftp_conn_id;

		//SYSTYPE
		$job_object->log( sprintf( __( 'FTP client command: %s', 'backuppilot' ), 'SYST' ), E_USER_NOTICE );
		$systype = ftp_systype( $ftp_conn_id );
		if ( $systype ) {
			$job_object->log( sprintf( __( 'FTP server reply: %s', 'backuppilot' ), $systype ), E_USER_NOTICE );
		} else {
			$job_object->log(
				sprintf( __( 'FTP server reply: %s', 'backuppilot' ), __( 'Error getting SYSTYPE', 'backuppilot' ) ),
				E_USER_ERROR
			);
		}

		//set actual ftp dir to ftp dir
		if ( empty( $job_object->job['ftpdir'] ) ) {
			$job_object->job['ftpdir'] = trailingslashit( ftp_pwd( $ftp_conn_id ) );
		}
		// prepend actual ftp dir if relative dir
		if ( substr( $job_object->job['ftpdir'], 0, 1 ) != '/' ) {
			$job_object->job['ftpdir'] = trailingslashit( ftp_pwd( $ftp_conn_id ) ) . $job_object->job['ftpdir'];
		}

		//test ftp dir and create it if not exists
		if ( $job_object->job['ftpdir'] != '/' ) {
			@ftp_chdir( $ftp_conn_id, '/' ); //go to root
			$ftpdirs = explode( '/', trim( $job_object->job['ftpdir'], '/' ) );

			foreach ( $ftpdirs as $ftpdir ) {
				if ( empty( $ftpdir ) ) {
					continue;
				}

				// Suppress the E_WARNING emitted when the directory does not exist yet —
				// a missing directory is expected and handled by create_dir().
				// A no-op handler is reliable across PHP versions; @ alone is not.
				set_error_handler( static function () { return true; } );
				$can_chdir = ftp_chdir( $ftp_conn_id, $ftpdir );
				restore_error_handler();

				if ( ! $can_chdir ) {
					if ( ! $this->create_dir( $ftp_conn_id, $ftpdir, $job_object ) ) {
						return false;
					}

					ftp_chdir( $ftp_conn_id, $ftpdir );
				}
			}
		}

		// Get the current working directory
		$current_ftp_dir = trailingslashit( ftp_pwd( $ftp_conn_id ) );
		if ( $job_object->substeps_done == 0 ) {
			$job_object->log(
				sprintf( __( 'FTP current folder is: %s', 'backuppilot' ), $current_ftp_dir ),
				E_USER_NOTICE
			);
		}

		//get file size to resume upload
		@clearstatcache();
		$job_object->substeps_done = @ftp_size( $ftp_conn_id, $job_object->job['ftpdir'] . $job_object->backup_file );
		if ( $job_object->substeps_done == - 1 ) {
			$job_object->substeps_done = 0;
		}

		//PASV
		$job_object->log( sprintf( __( 'FTP client command: %s', 'backuppilot' ), 'PASV' ), E_USER_NOTICE );

		if ( $job_object->job['ftppasv'] ) {

			if ( ftp_pasv( $ftp_conn_id, true ) ) {
				$job_object->log(
					sprintf( __( 'FTP server reply: %s', 'backuppilot' ), __( 'Entering passive mode', 'backuppilot' ) ),
					E_USER_NOTICE
				);
			}

			else {
				$job_object->log(
					sprintf( __( 'FTP server reply: %s', 'backuppilot' ), __( 'Cannot enter passive mode', 'backuppilot' ) ),
					E_USER_WARNING
				);
			}
		}

		else {

			if ( ftp_pasv( $ftp_conn_id, false ) ) {
				$job_object->log(
					sprintf( __( 'FTP server reply: %s', 'backuppilot' ), __( 'Entering normal mode', 'backuppilot' ) ),
					E_USER_NOTICE
				);
			}

			else {
				$job_object->log(
					sprintf( __( 'FTP server reply: %s', 'backuppilot' ), __( 'Cannot enter normal mode', 'backuppilot' ) ),
					E_USER_WARNING
				);
			}
		}

		if ( $job_object->substeps_done < $job_object->backup_filesize ) {

			$job_object->log( __( 'Starting upload to FTP &#160;&hellip;', 'backuppilot' ), E_USER_NOTICE );

			if ( $fp = fopen( $job_object->backup_folder . $job_object->backup_file, 'rb' ) ) {

				//go to actual file pos
				fseek( $fp, $job_object->substeps_done );
				$ret = ftp_nb_fput(
					$ftp_conn_id,
					$current_ftp_dir . $job_object->backup_file,
					$fp,
					FTP_BINARY,
					$job_object->substeps_done
				);

				while ( $ret == FTP_MOREDATA ) {
					$job_object->substeps_done = ftell( $fp );
					$job_object->update_working_data();
					$job_object->do_restart_time();

					$ret = ftp_nb_continue( $ftp_conn_id );
				}

				if ( $ret != FTP_FINISHED ) {
					$job_object->log( __( 'Cannot transfer backup to FTP server!', 'backuppilot' ), E_USER_ERROR );

					return false;
				}

				else {
					$job_object->substeps_done = $job_object->backup_filesize + 1;
					$job_object->log(
						sprintf(
							__( 'Backup transferred to FTP server: %s', 'backuppilot' ),
							$current_ftp_dir . $job_object->backup_file
						),
						E_USER_NOTICE
					);
					if ( ! empty( $job_object->job['jobid'] ) ) {
						//build an internal proxy download link instead of embedding the decrypted FTP credentials in a stored/rendered URL
						BackupPilot_Option::update(
							$job_object->job['jobid'],
							'lastbackupdownloadurl',
							network_admin_url( 'admin.php' ) . '?page=backuppilotbackups&action=downloadftp&file=' . $current_ftp_dir . $job_object->backup_file . '&jobid=' . $job_object->job['jobid']
						);
					}
				}

				fclose( $fp );
			}

			else {
				$job_object->log( __( 'Can not open source file for transfer.', 'backuppilot' ), E_USER_ERROR );

				return false;
			}
		}

			$this->file_update_list( $job_object, true );
		$job_object->substeps_done ++;

		ftp_close( $ftp_conn_id );

		return true;
	}

	/**
	 * Can Run
	 *
	 * @param array $job_settings The settings of the job.
	 *
	 * @return bool True if the job can run, false otherwise
	 */
	public function can_run( array $job_settings ) {

		if ( empty( $job_settings['ftphost'] ) ) {
			return false;
		}

		if ( empty( $job_settings['ftpuser'] ) ) {
			return false;
		}

		if ( empty( $job_settings['ftppass'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Create Dir
	 *
	 * Try to create the directory and if not possible, try to change the permissions of the parent,
	 * then try to create it again.
	 *
	 * @param resource     $stream     The ftp stream pointer.
	 * @param string       $dir        The directory to create.
	 * @param BackupPilot_Job $job_object The job instance.
	 *
	 * @return bool True on success false on failure
	 */
	private function create_dir( $stream, $dir, BackupPilot_Job $job_object ) {

		// Try to create the directory.
		$response = (bool) ftp_mkdir( $stream, $dir );

		if ( ! $response ) {
			// Trying to set the parent directory permissions.
			$response = (bool) ftp_chmod( $stream, 0775, './' );

			if ( ! $response ) {
				$job_object->log(
					sprintf(
						esc_html__(
							'FTP Folder "%s" cannot be created! Parent directory may be not writable.',
							'backuppilot'
						),
						$dir
					),
					E_USER_ERROR
				);

				return $response;
			}

			// Try to create the directory for the second time.
			$response = (bool) ftp_mkdir( $stream, $dir );

			if ( ! $response ) {
				$job_object->log(
					sprintf(
						esc_html__( 'FTP Folder "%s" cannot be created!', 'backuppilot' ),
						$dir
					),
					E_USER_ERROR
				);

				return $response;
			}
		}

		$job_object->log(
			sprintf(
				esc_html__( 'FTP Folder "%s" created!', 'backuppilot' ),
				$dir
			),
			E_USER_NOTICE
		);

		return $response;
	}
}
