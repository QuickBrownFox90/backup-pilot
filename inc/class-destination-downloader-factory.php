<?php
/**
 * BackupPilot_Destination_Downloader_Factory
 *
 * @since   3.5.0
 * @package QBF\BackupPilot
 */

/**
 * Class BackupPilot_Destination_Downloader_Factory
 *
 * @since   3.5.0
 * @package QBF\BackupPilot
 */
class BackupPilot_Destination_Downloader_Factory {

    const CLASS_PREFIX = 'BackupPilot_Destination_';
    const CLASS_SUFFIX = '_Downloader';

    /**
     * @param string $service_name
     * @param int    $job_id
     * @param string $source_file_path
     * @param string $local_file_path
     * @param string $base_url
     *
     * @return \BackupPilot_Destination_Downloader
     */
    public function create( $service_name, $job_id, $source_file_path, $local_file_path, $base_url = '' ) {

        $destination = null;
        $service_name = ucwords( $service_name );
        $class = self::CLASS_PREFIX . $service_name . self::CLASS_SUFFIX;

        if ( ! class_exists( $class ) ) {
            throw new BackupPilot_Factory_Exception(
                sprintf(
                    'No way to instantiate class %s. Class doesn\'t exist.',
                    $class
                )
            );
        }

        $data = new BackupPilot_Destination_Downloader_Data( $job_id, $source_file_path, $local_file_path );

        /** @var \BackupPilot_Destination_Downloader_Interface $destination */
        $destination = new $class( $data );

        return new BackupPilot_Destination_Downloader( $data, $destination );
    }
}
