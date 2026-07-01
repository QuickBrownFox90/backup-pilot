<?php
/**
 * Class for WP-CLI commands
 */

class BackupPilot_WP_CLI extends WP_CLI_Command {

    /**
     * Start a BackupPilot job
     *
     * # EXAMPLES
     *
     *   backuppilot start 13
     *   backuppilot start --jobid=13 (deprecated)
     *
     * @param  $args
     * @param  $assoc_args
     */

    public function start( $args, $assoc_args ) {

        $jobid = 0;

        if ( file_exists( BackupPilot::get_plugin_data( 'running_file' ) ) ) {
            WP_CLI::error( __( 'A job is already running.', 'backuppilot' ) );
        }

        if ( isset( $assoc_args['jobid'] ) ) {
            $jobid = (int) $assoc_args[ 'jobid' ];
        }

        if ( ! empty( $args[ 0 ] ) ) {
            $jobid = (int) $args[ 0 ];
        }

        if ( empty( $jobid ) ) {
            WP_CLI::error( __( 'No job ID specified!', 'backuppilot' ) );
        }

        $jobids = BackupPilot_Option::get_job_ids();

        if ( ! in_array( $jobid, $jobids, true ) ) {
            WP_CLI::error( __( 'Job ID does not exist!', 'backuppilot' ) );
        }

        BackupPilot_Job::start_cli( $jobid );
    }


    /**
     *  Abort a working BackupPilot Job
     *
     */

    public function abort( $args, $assoc_args ) {

        if ( ! file_exists( BackupPilot::get_plugin_data( 'running_file' ) ) ) {
            WP_CLI::error( __( 'Nothing to abort!', 'backuppilot' ) );
        }

        //abort
        BackupPilot_Job::user_abort();
        WP_CLI::success( __( 'Job will be terminated.', 'backuppilot' ) ) ;
    }


    /**
     * Display a List of Jobs
     */

    public function jobs( $args, $assoc_args ) {

        $formatter_args = [
            'format' => 'table',
            'fields' => [
                'Job ID',
                'Name'
            ],
            'field'  => NULL
        ];

        $items = [];

        $formatter = new WP_CLI\Formatter( $formatter_args );

        $jobids = BackupPilot_Option::get_job_ids();

        foreach ( $jobids as $jobid ) {

            $items[] = [
                'Job ID' => $jobid,
                'Name'   => BackupPilot_Option::get( $jobid, 'name' )
            ];
        }

        $formatter->display_items( $items );
    }


    /**
     * See Status of a working job
     *
     * @param  $args
     * @param  $assoc_args
     */

    public function working( $args, $assoc_args ) {

        $job_object = BackupPilot_Job::get_working_data();

        if ( ! is_object( $job_object ) ) {
            WP_CLI::error( __( 'No job running', 'backuppilot' ) );
        }

        $formatter_args = [
            'format' => 'table',
            'fields' => [
                'JobID',
                'Name',
                'Warnings',
                'Errors',
                'On Step',
                'Done',
            ],
            'field'  => NULL
        ];

        $formatter = new WP_CLI\Formatter( $formatter_args );

        $items   = [];
        $items[] = [
            'JobID'        => $job_object->job[ 'jobid' ],
            'Name'         => $job_object->job[ 'name' ],
            'Warnings'     => $job_object->warnings,
            'Errors'       => $job_object->errors,
            'On Step'      => $job_object->steps_data[ $job_object->step_working ][ 'NAME' ],
            'Done'         => $job_object->step_percent . ' / ' . $job_object->substep_percent,
            'Last message' => str_replace( '&hellip;', '...', strip_tags( $job_object->lastmsg ) )
        ];

        $formatter->display_items( $items );

        WP_CLI::log( 'Last Message: ' . str_replace( '&hellip;', '...', strip_tags( $job_object->lastmsg ) ) );
    }

}
