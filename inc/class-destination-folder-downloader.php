<?php
/**
 * Folder Downloader
 *
 * @package QBF\BackupPilot
 */

/**
 * Class BackupPilot_Destination_Folder_Downloader
 *
 * @since   3.6.0
 * @package QBF\BackupPilot
 */
final class BackupPilot_Destination_Folder_Downloader implements BackupPilot_Destination_Downloader_Interface {

	const OPTION_BACKUP_DIR = 'backupdir';

	/**
	 * @var \BackupPilot_Destination_Downloader_Data
	 */
	private $data;

	/**
	 * @var resource
	 */
	private $source_file_handler;

	/**
	 * @var resource
	 */
	private $local_file_handler;

	/**
	 * BackupPilot_Destination_Folder_Downloader constructor
	 */
	public function __construct( BackupPilot_Destination_Downloader_Data $data ) {

		$this->data = $data;

		$this->source_file_handler();
		$this->local_file_handler();
	}

	/**
	 * Clean up things
	 */
	public function __destruct() {

		if ( is_resource( $this->local_file_handler ) ) {
			fclose( $this->local_file_handler );
		}
		if ( is_resource( $this->source_file_handler ) ) {
			fclose( $this->source_file_handler );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function download_chunk( $start_byte, $end_byte ) {

		if ( ftell( $this->source_file_handler ) !== $start_byte ) {
			fseek( $this->source_file_handler, $start_byte );
		}

		$data = fread( $this->source_file_handler, $end_byte - $start_byte + 1 );
		if ( ! $data ) {
			throw new Exception( __( 'Could not read data from source file.', 'backuppilot' ) );
		}

		$bytes = (int) fwrite( $this->local_file_handler, $data );
		if ( $bytes === 0 ) {
			throw new Exception( __( 'Could not write data into target file.', 'backuppilot' ) );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function calculate_size() {

		return filesize( $this->source_backup_file() );
	}

	/**
	 * Retrieve the file handler for the source file
	 *
	 * @return void
	 */
	private function source_file_handler() {

		if ( is_resource( $this->source_file_handler ) ) {
			return;
		}

		$file = $this->source_backup_file();

		$this->source_file_handler = @fopen( $file, 'rb' );
		if ( ! is_resource( $this->source_file_handler ) ) {
			throw new \RuntimeException( __( 'File could not be opened for reading.', 'backuppilot' ) );
		}
	}

	/**
	 * @return string
	 */
	private function backup_dir() {

		$backup_dir = esc_attr( BackupPilot_Option::get( $this->data->job_id(), self::OPTION_BACKUP_DIR ) );
		$backup_dir = trailingslashit( BackupPilot_File::get_absolute_path( $backup_dir ) );

		return (string) $backup_dir;
	}

	/**
	 * @return string
	 */
	private function source_backup_file() {

		return (string) realpath(
			BackupPilot_Sanitize_Path::sanitize_path(
				$this->backup_dir() . basename( $this->data->source_file_path() )
			)
		);
	}

	/**
	 * Retrieve the file handler for the local file
	 *
	 * @return void
	 */
	private function local_file_handler() {

		if ( is_resource( $this->local_file_handler ) ) {
			return;
		}

		$this->local_file_handler = @fopen( $this->data->local_file_path(), 'wb' );
		if ( ! is_resource( $this->local_file_handler ) ) {
			throw new \RuntimeException( __( 'File could not be opened for writing.', 'backuppilot' ) );
		}
	}
}
