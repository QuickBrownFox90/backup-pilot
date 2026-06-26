<?php
class BackupPilot_JobType_WPPlugin extends BackupPilot_JobTypes {

	public function __construct() {

		$this->info[ 'ID' ]          = 'WPPLUGIN';
		$this->info[ 'name' ]        = __( 'Plugins', 'backuppilot' );
		$this->info[ 'description' ] = __( 'Installed plugins list', 'backuppilot' );
		$this->info[ 'URI' ]         = __( 'http://backwpup.com', 'backuppilot' );
		$this->info[ 'author' ]      = 'Inpsyde GmbH';
		$this->info[ 'authorURI' ]   = __( 'http://inpsyde.com', 'backuppilot' );
		$this->info[ 'version' ]     = BackupPilot::get_plugin_data( 'Version' );

	}

	/**
	 * @return bool
	 */
	public function creates_file() {

		return TRUE;
	}

	/**
	 * @return array
	 */
	public function option_defaults() {
		return array( 'pluginlistfilecompression' => '', 'pluginlistfile' => sanitize_file_name( get_bloginfo( 'name' ) ) . '.pluginlist.%Y-%m-%d' );
	}

	/**
	 * @param $jobid
	 * @return void
	 */
	public function edit_tab( $jobid ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="idpluginlistfile"><?php esc_html_e( 'Plugin list file name', 'backuppilot' ) ?></label></th>
				<td>
					<input name="pluginlistfile" type="text" id="idpluginlistfile"
						   value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'pluginlistfile' ) );?>"
						   class="medium-text code"/>.txt
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'File compression', 'backuppilot' ) ?></th>
				<td>
					<fieldset>
						<?php
						echo '<label for="idpluginlistfilecompression"><input class="radio" type="radio"' . checked( '', BackupPilot_Option::get( $jobid, 'pluginlistfilecompression' ), FALSE ) . ' name="pluginlistfilecompression"  id="idpluginlistfilecompression" value="" /> ' . esc_html__( 'none', 'backuppilot' ). '</label><br />';
						if ( function_exists( 'gzopen' ) ) {
							echo '<label for="idpluginlistfilecompression-gz"><input class="radio" type="radio"' . checked( '.gz', BackupPilot_Option::get( $jobid, 'pluginlistfilecompression' ), FALSE ) . ' name="pluginlistfilecompression" id="idpluginlistfilecompression-gz" value=".gz" /> ' . esc_html__( 'GZip', 'backuppilot' ). '</label><br />';
						} else {
							echo '<label for="idpluginlistfilecompression-gz"><input class="radio" type="radio"' . checked( '.gz', BackupPilot_Option::get( $jobid, 'pluginlistfilecompression' ), FALSE ) . ' name="pluginlistfilecompression" id="idpluginlistfilecompression-gz" value=".gz" disabled="disabled" /> ' . esc_html__( 'GZip', 'backuppilot' ). '</label><br />';
						}
						?>
					</fieldset>
				</td>
			</tr>
		</table>
	<?php
	}


	/**
	 * @param $id
	 */
	public function edit_form_post_save( $id ) {

		BackupPilot_Option::update( $id, 'pluginlistfile', sanitize_text_field( $_POST[ 'pluginlistfile' ] ) );
		if ( $_POST[ 'pluginlistfilecompression' ] === '' || $_POST[ 'pluginlistfilecompression' ] === '.gz' || $_POST[ 'pluginlistfilecompression' ] === '.bz2' ) {
			BackupPilot_Option::update( $id, 'pluginlistfilecompression', $_POST[ 'pluginlistfilecompression' ] );
		}
	}

	/**
	 * @param $job_object
	 * @return bool
	 */
	public function job_run( BackupPilot_Job $job_object ) {

		$job_object->substeps_todo = 1;

		$job_object->log( sprintf( __( '%d. Trying to generate a file with installed plugin names&#160;&hellip;', 'backuppilot' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) );
		//build filename
		if ( empty( $job_object->temp[ 'pluginlistfile' ] ) )
			$job_object->temp[ 'pluginlistfile' ] = $job_object->generate_filename( $job_object->job[ 'pluginlistfile' ], 'txt' ) . $job_object->job[ 'pluginlistfilecompression' ];

		if ( $job_object->job[ 'pluginlistfilecompression' ] == '.gz' )
			$handle = fopen( 'compress.zlib://' . BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'pluginlistfile' ], 'w' );
		else
			$handle = fopen( BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'pluginlistfile' ], 'w' );

		if ( $handle ) {
			//open file
			$header = "------------------------------------------------------------" . PHP_EOL;
			$header .= "  Plugin list generated with BackupPilot version: " . BackupPilot::get_plugin_data( 'Version' ) . PHP_EOL;
			$header .= "  http://backwpup.com" . PHP_EOL;
			$header .= "  Blog Name: " . get_bloginfo( 'name' ) . PHP_EOL;
			$header .= "  Blog URL: " . get_bloginfo( 'url' ) . PHP_EOL;
			$header .= "  Generated on: " . date( 'Y-m-d H:i.s', current_time( 'timestamp' ) ) . PHP_EOL;
			$header .= "------------------------------------------------------------" . PHP_EOL . PHP_EOL;
			fwrite( $handle, $header );
			//get Plugins
			if ( ! function_exists( 'get_plugins' ) )
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$plugins        = get_plugins();
			$plugins_active = get_option( 'active_plugins' );
			//write it to file
			fwrite( $handle, PHP_EOL . __( 'All plugin information:', 'backuppilot' ) . PHP_EOL . '------------------------------' . PHP_EOL );
			foreach ( $plugins as $plugin ) {
				fwrite( $handle, $plugin[ 'Name' ] . ' (v.' . $plugin[ 'Version' ] . ') ' . html_entity_decode( sprintf( __( 'from %s', 'backuppilot' ), $plugin[ 'Author' ] ), ENT_QUOTES ) . PHP_EOL . "\t" . $plugin[ 'PluginURI' ] . PHP_EOL );
			}
			fwrite( $handle, PHP_EOL . __( 'Active plugins:', 'backuppilot' ) . PHP_EOL . '------------------------------' . PHP_EOL );

			foreach ( $plugins as $key => $plugin ) {
				if ( in_array( $key, $plugins_active, true ) )
					fwrite( $handle, $plugin[ 'Name' ] . PHP_EOL );
			}
			fwrite( $handle, PHP_EOL . __( 'Inactive plugins:', 'backuppilot' ) . PHP_EOL . '------------------------------' . PHP_EOL );
			foreach ( $plugins as $key => $plugin ) {
				if ( ! in_array( $key, $plugins_active, true ) )
					fwrite( $handle, $plugin[ 'Name' ] . PHP_EOL );
			}
			fclose( $handle );
		} else {
			$job_object->log( __( 'Can not open target file for writing.', 'backuppilot' ), E_USER_ERROR );
			return FALSE;
		}

		//add file to backup files
		if ( is_readable( BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'pluginlistfile' ] ) ) {
			$job_object->additional_files_to_backup[ ] = BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'pluginlistfile' ];
			$job_object->log( sprintf( __( 'Added plugin list file "%1$s" with %2$s to backup file list.', 'backuppilot' ), $job_object->temp[ 'pluginlistfile' ], size_format( filesize( BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'pluginlistfile' ] ), 2 ) ) );
		}
		$job_object->substeps_done = 1;

		return TRUE;
	}
}
