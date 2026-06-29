<?php

class BackupPilot_Destination_Email extends BackupPilot_Destinations {

	/**
	 * @return array
	 */
	public function option_defaults() {

		$default                      = array();
		$default['emailaddress']      = sanitize_email( get_bloginfo( 'admin_email' ) );
		$default['emailefilesize']    = 20;
		$default['emailsndemail']     = sanitize_email( get_bloginfo( 'admin_email' ) );
		$default['emailsndemailname'] = 'BackupPilot ' . get_bloginfo( 'name' );

		return $default;
	}


	/**
	 * @param $jobid
	 */
	public function edit_tab( $jobid ) {

		?>
		<h3 class="title"><?php esc_html_e( 'Email address', 'backuppilot' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label
						for="emailaddress"><?php esc_html_e( 'To email address (separate with commas for multiple addresses)', 'backuppilot' ); ?></label>
				</th>
				<td>
					<input name="emailaddress" id="emailaddress" type="text"
					       value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'emailaddress' ) ); ?>"
					       class="regular-text"/>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="sendemailtest"><?php esc_html_e( 'Send test email', 'backuppilot' ); ?></label>
				</th>
				<td>
					<button id="sendemailtest"
					        class="button secondary"><?php esc_html_e( 'Send test email', 'backuppilot' ); ?></button>
				</td>
			</tr>
		</table>

		<h3 class="title"><?php esc_html_e( 'Send email settings', 'backuppilot' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label
						for="idemailefilesize"><?php esc_html_e( 'Maximum file size', 'backuppilot' ); ?></label></th>
				<td>
					<input id="idemailefilesize" name="emailefilesize" type="number" min="0" step="1"
					       value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'emailefilesize' ) ); ?>"
					       class="small-text"/><?php esc_html_e( 'MB', 'backuppilot' ); ?>
                    <p class="description">
                        <?php esc_html_e(
                            'Maximum file size to be included in an email. 0 = unlimited',
                            'backuppilot'
                        ); ?><br/>
                        <strong><?php esc_html_e( 'Note', 'backuppilot' ); ?></strong>: <?php esc_html_e(
                            'Every email provider has different allowed attachment file sizes. If the backup archive exceeds this limit, the email will not be sent.',
                            'backuppilot'
                        ); ?><br/>
                        <?php esc_html_e( 'The recommended value is 20 MB.', 'backuppilot' ); ?>
                    </p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label
						for="emailsndemail"><?php esc_html_e( 'From email address', 'backuppilot' ); ?></label></th>
				<td>
					<input name="emailsndemail" type="text" id="emailsndemail"
					       value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'emailsndemail' ) ); ?>"
					       class="regular-text"/>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="emailsndemailname"><?php esc_html_e( 'From name', 'backuppilot' ); ?></label>
				</th>
				<td>
					<input name="emailsndemailname" type="text" id="emailsndemailname"
					       value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'emailsndemailname' ) ); ?>"
					       class="regular-text"/>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Mail delivery', 'backuppilot' ); ?></th>
				<td>
					<p class="description">
						<?php esc_html_e( 'Emails are sent via the WordPress mail system (wp_mail). To configure the mail server, SMTP credentials, or delivery provider, use a dedicated mail plugin such as WP Mail SMTP, FluentSMTP, or Post SMTP.', 'backuppilot' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}


	public function edit_inline_js() {

		?>
		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( '#sendemailtest' ).live( 'click', function () {
					$( '#sendemailtest' ).after( '&nbsp;<img id="emailsendtext" src="<?php echo get_admin_url() . 'images/loading.gif'; ?>" width="16" height="16" />' );
					var data = {
						action           : 'backuppilot_dest_email',
						emailaddress     : $( 'input[name="emailaddress"]' ).val(),
						emailsndemail    : $( 'input[name="emailsndemail"]' ).val(),
						emailsndemailname: $( 'input[name="emailsndemailname"]' ).val(),
						_ajax_nonce      : $( '#backuppilotajaxnonce' ).val()
					};
					$.post( ajaxurl, data, function ( response ) {
						$( '#emailsendtext' ).replaceWith( response );
					} );
					return false;
				} );
			} );
		</script>
		<?php
	}

	/**
	 * @param $jobid
	 */
	public function edit_form_post_save( $jobid ) {

		BackupPilot_Option::update( $jobid, 'emailaddress', isset( $_POST['emailaddress'] ) ? implode( ', ', $this->get_email_array( $_POST['emailaddress'] ) ) : '' );
		BackupPilot_Option::update( $jobid, 'emailefilesize', ! empty( $_POST['emailefilesize'] ) ? absint( $_POST['emailefilesize'] ) : 0 );
		BackupPilot_Option::update( $jobid, 'emailsndemail', sanitize_email( $_POST['emailsndemail'] ) );
		BackupPilot_Option::update( $jobid, 'emailsndemailname', sanitize_text_field( $_POST['emailsndemailname'] ) );
	}

	/**
	 * @param $job_object
	 *
	 * @return bool
	 */
	public function job_run_archive( BackupPilot_Job $job_object ) {

		$job_object->substeps_todo = 1;
		$job_object->log( sprintf( __( '%d. Try to send backup with email&#160;&hellip;', 'backuppilot' ), $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ), E_USER_NOTICE );

		if ( ! empty( $job_object->job['emailefilesize'] ) ) {
			$limit_bytes  = $job_object->job['emailefilesize'] * 1024 * 1024;
			if ( $job_object->backup_filesize > $limit_bytes ) {
				$job_object->log(
					sprintf(
						__( 'Backup archive too big to be sent by email (file: %1$s, limit: %2$s). Increase the maximum file size in the Email destination settings or choose a different destination for large backups.', 'backuppilot' ),
						size_format( $job_object->backup_filesize ),
						size_format( $limit_bytes )
					),
					E_USER_ERROR
				);
				$job_object->substeps_done = 1;

				return true;
			}
		}

		$job_object->log( sprintf( __( 'Sending email to %s&hellip;', 'backuppilot' ), $job_object->job['emailaddress'] ), E_USER_NOTICE );

		$attachment_path = $job_object->backup_folder . $job_object->backup_file;
		$job_object->log( sprintf( __( 'Attaching file: %s', 'backuppilot' ), $attachment_path ), E_USER_NOTICE );

		if ( ! is_readable( $attachment_path ) ) {
			$job_object->log(
				sprintf( __( 'Backup archive not found or not readable: %s', 'backuppilot' ), $attachment_path ),
				E_USER_ERROR
			);

			return false;
		}

		// PHP's mail() must base64-encode the full attachment in memory. Request enough
		// headroom upfront so ini_set can raise the limit before the send attempt.
		$job_object->need_free_memory( $job_object->backup_filesize * 8 );

		$to      = $this->get_email_array( $job_object->job['emailaddress'] );
		$subject = sprintf( __( 'BackupPilot archive from %1$s: %2$s', 'backuppilot' ), date_i18n( 'd-M-Y H:i', $job_object->start_time, true ), esc_attr( $job_object->job['name'] ) );
		$body    = sprintf( __( 'Backup archive: %s', 'backuppilot' ), $job_object->backup_file );
		$headers = array(
			'From: ' . $job_object->job['emailsndemailname'] . ' <' . $job_object->job['emailsndemail'] . '>',
			'Content-Type: text/plain; charset=' . get_bloginfo( 'charset' ),
		);
		$attachments = array( $attachment_path );

		$mail_error = null;
		$mail_error_handler = function ( WP_Error $error ) use ( &$mail_error ) {
			$mail_error = $error;
		};
		add_action( 'wp_mail_failed', $mail_error_handler );
		$result = wp_mail( $to, $subject, $body, $headers, $attachments );
		remove_action( 'wp_mail_failed', $mail_error_handler );

		if ( ! $result ) {
			$reason = $mail_error instanceof WP_Error ? $mail_error->get_error_message() : __( 'Unknown error', 'backuppilot' );
			$job_object->log(
				sprintf(
					__( 'Error while sending email: %1$s — If you are using PHP\'s mail() function, your mail server may be rejecting the message (e.g. attachment too large for the MTA).', 'backuppilot' ),
					$reason
				),
				E_USER_ERROR
			);

			return false;
		}

		$job_object->substeps_done = 1;
		$job_object->log( __( 'Email sent.', 'backuppilot' ), E_USER_NOTICE );

		return true;
	}

	/**
	 * @param $job_settings
	 *
	 * @return bool
	 */
	public function can_run( array $job_settings ) {

		if ( empty( $job_settings['emailaddress'] ) ) {
			return false;
		}

		if ( $job_settings['backuptype'] != 'archive' ) {
			return false;
		}

		return true;
	}


	/**
	 * sends test mail
	 */
	public function edit_ajax() {

		if ( ! current_user_can( 'backuppilot_jobs_edit' ) ) {
			wp_die( -1 );
		}
		check_ajax_referer( 'backuppilot_ajax_nonce' );

		$to      = $this->get_email_array( $_POST['emailaddress'] );
		$subject = __( 'BackupPilot archive sending TEST Message', 'backuppilot' );
		$body    = __( 'If this message reaches your inbox, sending backup archives via email should work for you.', 'backuppilot' );
		$from    = sanitize_email( $_POST['emailsndemail'] );
		$name    = sanitize_text_field( $_POST['emailsndemailname'] );
		$headers = array( 'From: ' . $name . ' <' . $from . '>' );

		$mail_error = null;
		$mail_error_handler = function ( WP_Error $error ) use ( &$mail_error ) {
			$mail_error = $error;
		};
		add_action( 'wp_mail_failed', $mail_error_handler );
		$result = wp_mail( $to, $subject, $body, $headers );
		remove_action( 'wp_mail_failed', $mail_error_handler );

		if ( ! $result ) {
			$reason  = $mail_error instanceof WP_Error ? $mail_error->get_error_message() : __( 'Unknown error', 'backuppilot' );
			$message = sprintf(
				__( 'Error while sending email: %1$s — If you are using PHP\'s mail() function, your mail server may be rejecting the message.', 'backuppilot' ),
				$reason
			);
			echo '<span id="emailsendtext" class="bwu-message-error">' . esc_html( $message ) . '</span>';
		} else {
			echo '<span id="emailsendtext" class="bwu-message-success">' . esc_html__( 'Email sent.', 'backuppilot' ) . '</span>';
		}
		die();
	}

	/**
	 * Get an array of emails from comma-separated string.
	 *
	 * @param string $emailString
	 *
	 * @return array
	 */
	private function get_email_array( $emailString ) {

		$emails = explode( ',', sanitize_text_field( $emailString ) );

		foreach ( $emails as $key => $email ) {
			$emails[ $key ] = sanitize_email( trim( $email ) );
			if ( ! is_email( $emails[ $key ] ) ) {
				unset( $emails[ $key ] );
			}
		}

		return $emails;
	}
}
