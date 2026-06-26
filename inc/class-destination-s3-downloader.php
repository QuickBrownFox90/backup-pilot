<?php
/**
 * S3 Downloader
 *
 * @since   3.5.0
 * @package QBF\BackupPilot
 */

/**
 * Class BackupPilot_Destination_S3_Downloader
 *
 * @since   3.5.0
 * @package QBF\BackupPilot
 */
final class BackupPilot_Destination_S3_Downloader implements BackupPilot_Destination_Downloader_Interface {

	const OPTION_BASE_URL = 's3base_url';
	const OPTION_BUCKET = 's3bucket';
	const OPTION_ACCESS_KEY = 's3accesskey';
	const OPTION_SECRET_KEY = 's3secretkey';

	/**
	 * @var \BackupPilot_Destination_Downloader_Data
	 */
	private $data;

	/**
	 * @var Aws\S3\S3Client
	 */
	private $s3_client;

	/**
	 * @var resource
	 */
	private $local_file_handler;

	/**
	 * BackupPilot_Destination_S3_Downloader constructor
	 *
	 * @param \BackupPilot_Destination_Downloader_Data $data
	 */
	public function __construct( BackupPilot_Destination_Downloader_Data $data ) {

		$this->data     = $data;
		$this->s3_client();
	}

	/**
	 * Clean stuffs
	 */
	public function __destruct() {

		fclose( $this->local_file_handler );
	}

	/**
	 * @inheritdoc
	 */
	public function download_chunk( $start_byte, $end_byte ) {

		$file = $this->s3_client->getObject( array(
			'Bucket' => BackupPilot_Option::get( $this->data->job_id(), self::OPTION_BUCKET ),
			'Key'    => $this->data->source_file_path(),
			'Range'  => 'bytes=' . $start_byte . '-' . $end_byte,
		) );

		if ( empty( $file['ContentType'] ) || $file['ContentLength'] === 0 ) {
			throw new \RuntimeException( __( 'Could not write data to file. Empty source file.', 'backuppilot' ) );
		}

		$this->local_file_handler( $start_byte );

		$bytes = (int) fwrite( $this->local_file_handler, (string) $file['Body'] );
		if ( $bytes === 0 ) {
			throw new \RuntimeException( __( 'Could not write data to file.', 'backuppilot' ) );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function calculate_size() {

		$file = $this->s3_client->getObject( array(
			'Bucket' => BackupPilot_Option::get( $this->data->job_id(), self::OPTION_BUCKET ),
			'Key'    => $this->data->source_file_path(),
		) );

		return (int) ( ! empty( $file['ContentType'] ) ? $file['ContentLength'] : 0 );
	}

	/**
	 * @param int $start_byte
	 */
	private function local_file_handler( $start_byte ) {

		if ( is_resource( $this->local_file_handler ) ) {
			return;
		}

		$this->local_file_handler = fopen( $this->data->local_file_path(), $start_byte == 0 ? 'wb' : 'ab' );

		if ( ! is_resource( $this->local_file_handler ) ) {
			throw new \RuntimeException( __( 'File could not be opened for writing.', 'backuppilot' ) );
		}
	}

	/**
	 * Build S3 Client
	 */
	private function s3_client() {

		if ($this->s3_client) {
			return;
		}

		if ( empty( BackupPilot_Option::get( $this->data->job_id(), self::OPTION_BASE_URL ) ) ) {
			$aws_destination = BackupPilot_S3_Destination::fromOption(
				BackupPilot_Option::get( $this->data->job_id(), self::OPTION_BASE_URL )
			);
		} else {
			$aws_destination = BackupPilot_S3_Destination::fromJobId( $this->data->job_id() );
		}

		$this->s3_client = $aws_destination->client(
			BackupPilot_Option::get($this->data->job_id(), self::OPTION_ACCESS_KEY),
			BackupPilot_Option::get($this->data->job_id(), self::OPTION_SECRET_KEY)
		);
	}
}
