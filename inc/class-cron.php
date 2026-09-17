<?php

/**
 * Class for BackupPilot cron methods
 */
class BackupPilot_Cron {


    /**
     * The 'backuppilot_cron' argument captured by watch_dispatch() for the event
     * currently being processed, or null if no dispatch has been observed yet.
     *
     * @var  string|int|null
     */

    private static $dispatch_arg = null;


    /**
     * Records which 'backuppilot_cron' argument is currently being dispatched.
     *
     * Hooked at priority 0, ahead of run() at the default priority, so the value
     * is captured even if run() itself never gets to execute (e.g. a fatal error
     * while WordPress binds the call). notify_missed_dispatch() reads this back
     * on shutdown to identify which job a missed automatic run belonged to.
     *
     * @since   1.0.7
     *
     * @param   string|int  $arg
     * @return  void
     */

    public static function watch_dispatch( $arg = 'restart' ) {

        self::$dispatch_arg = $arg;
    }


    /**
     * Notifies about an automatic backup run that never started because of a
     * fatal error during the WP-Cron dispatch itself.
     *
     * Registered via register_shutdown_function() only for genuine WP-Cron
     * requests. A fatal error here means the run() callback for 'backuppilot_cron'
     * crashed before (or while) it was invoked - the job never got a chance to
     * start, so none of the job's own error handling or notification logic ever
     * ran either. This is the only place that can still tell the site owner
     * their automatic backup was missed.
     *
     * @since   1.0.7
     *
     * @return  void
     */

    public static function notify_missed_dispatch() {

        $error = error_get_last();

        $fatal_types = [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ];

        if ( ! $error || ! in_array( $error['type'], $fatal_types, true ) ) {
            return;
        }

        if ( self::$dispatch_arg === null ) {
            return;
        }

        $jobid = is_numeric( self::$dispatch_arg ) ? abs( (int) self::$dispatch_arg ) : 0;
        $jobname = $jobid ? BackupPilot_Option::get( $jobid, 'name' ) : '';
        $recipient = $jobid ? BackupPilot_Option::get( $jobid, 'mailaddresslog' ) : '';

        if ( empty( $recipient ) ) {
            $recipient = get_bloginfo( 'admin_email' );
        }

        $subject = sprintf(
            __( '[%s] Automatic backup job could not start', 'backuppilot' ),
            get_bloginfo( 'name' )
        );

        $body = sprintf(
            __( "A scheduled BackupPilot job was due to run automatically but never started, because WordPress itself hit a fatal error while trying to trigger it.\n\nJob: %1\$s\nTime: %2\$s\nError: %3\$s\nFile: %4\$s:%5\$d\n\nThis is different from a job that ran and failed - it means the scheduled run was skipped entirely. The next scheduled run may be affected in the same way; if this keeps happening, please check with your hosting provider or a developer.", 'backuppilot' ),
            $jobname ?: self::$dispatch_arg,
            current_time( 'mysql' ),
            $error['message'],
            $error['file'],
            $error['line']
        );

        wp_mail( $recipient, $subject, $body );

        error_log( sprintf(
            'BackupPilot: automatic backup dispatch for arg "%1$s" was missed due to a fatal error: %2$s in %3$s:%4$d',
            self::$dispatch_arg,
            $error['message'],
            $error['file'],
            $error['line']
        ) );
    }


    /**
     * @since  1.0.0
     *
     * @param  string $arg
     */

    public static function run( $arg = 'restart' ) {

        if ( ! is_main_site( get_current_blog_id() ) ) {
            return;
        }

        if ( $arg === 'restart' ) {
            //reschedule restart
            wp_schedule_single_event( time() + 60, 'backuppilot_cron', [ 'restart' ] );
            //restart job if not working or a restart imitated
            self::cron_active( [ 'run' => 'restart' ] );

            return;
        }

        $arg = is_numeric( $arg ) ? abs( (int) $arg ) : 0;

        if ( ! $arg ) {
            return;
        }

        //check that job exits
        $jobids = BackupPilot_Option::get_job_ids( 'activetype', 'wpcron' );

        if ( ! in_array( $arg, $jobids, true ) ) {
            return;
        }

        //delay other job start for 5 minutes if already one is running
        $job_object = BackupPilot_Job::get_working_data();

        if ( $job_object ) {
            wp_schedule_single_event( time() + 300, 'backuppilot_cron', [ $arg ] );

            return;
        }

        //reschedule next job run
        $cron_next = self::cron_next( BackupPilot_Option::get( $arg, 'cron' ) );
        wp_schedule_single_event( $cron_next, 'backuppilot_cron', [ $arg ] );

        //start job
        self::cron_active( [
            'run' => 'cronrun',
            'jobid' => $arg,
        ] );

    }


    /**
     * Check Jobs worked and Cleanup logs and so on
     */

    public static function check_cleanup() {

        $job_object = BackupPilot_Job::get_working_data();
        $log_folder = get_site_option( 'backuppilot_cfg_logfolder' );
        $log_folder = BackupPilot_File::get_absolute_path( $log_folder );

        // check aborted jobs for longer than a tow hours, abort them courtly and send mail
        if ( is_object( $job_object ) && ! empty( $job_object->logfile ) ) {

            $not_worked_time = microtime( true ) - $job_object->timestamp_last_update;

            if ( $not_worked_time > 3600 ) {
                $job_object->log( E_USER_ERROR,
                    __( 'Aborted, because no progress for one hour!', 'backuppilot' ),
                    __FILE__,
                    __LINE__ );
                unlink( BackupPilot::get_plugin_data( 'running_file' ) );
                $job_object->update_working_data();
            }
        }

        //Compress not compressed logs
        if (
            is_readable( $log_folder ) && function_exists( 'gzopen' ) &&
            get_site_option( 'backuppilot_cfg_gzlogs' ) && ! is_object( $job_object )
        ) {
            //Compress old not compressed logs
            try {
                $dir = new BackupPilot_Directory( $log_folder );

                $jobids = BackupPilot_Option::get_job_ids();

                foreach ( $dir as $file ) {

                    if ( $file->isWritable() && '.html' == substr( $file->getFilename(), - 5 ) ) {

                        $compress = new BackupPilot_Create_Archive( $file->getPathname() . '.gz' );

                        if ( $compress->add_file( $file->getPathname() ) ) {
                            unlink( $file->getPathname() );
                            //change last logfile in jobs
                            foreach ( $jobids as $jobid ) {
                                $job_logfile = BackupPilot_Option::get( $jobid, 'logfile' );
                                if ( ! empty( $job_logfile ) && $job_logfile === $file->getPathname() ) {
                                    BackupPilot_Option::update( $jobid, 'logfile', $file->getPathname() . '.gz' );
                                }
                            }
                        }

                        $compress->close();
                        unset( $compress );
                    }
                }
            }

            catch ( UnexpectedValueException $e ) {
                error_log( 'BackupPilot: Could not open log folder for compression: ' . $e->getMessage() );
            }
        }

        //Jobs cleanings
        if ( ! $job_object ) {
            //remove restart cron
            wp_clear_scheduled_hook( 'backuppilot_cron', [ 'restart' ] );
            //temp cleanup
            BackupPilot_Job::clean_temp_folder();
        }

        //check scheduling jobs that not found will removed because there are single scheduled
        $activejobs = BackupPilot_Option::get_job_ids( 'activetype', 'wpcron' );

        foreach ( $activejobs as $jobid ) {

            $cron_next = wp_next_scheduled( 'backuppilot_cron', [ $jobid ] );

            if ( ! $cron_next || $cron_next < time() ) {

                wp_unschedule_event( $cron_next, 'backuppilot_cron', [ $jobid ] );
                $cron_next = BackupPilot_Cron::cron_next( BackupPilot_Option::get( $jobid, 'cron' ) );
                wp_schedule_single_event( $cron_next, 'backuppilot_cron', [ $jobid ] );
            }
        }

    }


    /**
     * Start job if in cron and run query args are set.
     * @since   1.0.0
     *
     * @param   array  $args  Arguments
     * @return  void
     */

    public static function cron_active( $args = [] ) {

        //only if cron active
        if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
            return;
        }

        if ( ! is_array( $args ) ) {
            $args = [];
        }

        // 'cronrun' is only ever passed internally by BackupPilot_Cron::run() for the normal,
        // scheduled WordPress-cron job execution. Its nonce check below is intentionally
        // skipped, so it must be fully isolated from the current request's query string -
        // otherwise a request that happens to coincide with a due 'backuppilot_cron' event
        // could use backuppilot_run/_nonce/jobid to hijack which job actually gets started.

        $is_internal_cronrun = isset( $args['run'] ) && $args['run'] === 'cronrun';

        if ( ! $is_internal_cronrun ) {

            if ( isset( $_GET['backuppilot_run'] ) && $_GET['backuppilot_run'] !== 'cronrun' ) {
                $args['run'] = sanitize_text_field( $_GET['backuppilot_run'] );
            }

            if ( isset( $_GET['_nonce'] ) ) {
                $args['nonce'] = sanitize_text_field( $_GET['_nonce'] );
            }

            if ( isset( $_GET['jobid'] ) ) {
                $args['jobid'] = absint( $_GET['jobid'] );
            }
        }

        $args = array_merge( [
            'run' => '',
            'nonce' => '',
            'jobid' => 0,
        ],
            $args );

        if (
            ! in_array(
                $args['run'],
                [ 'test', 'restart', 'runnow', 'runnowalt', 'runext', 'cronrun' ],
                true
            )
        ) {
            return;
        }

        //special header
        @session_write_close();
        @header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ), true );
        @header( 'X-Robots-Tag: noindex, nofollow', true );

        nocache_headers();

        //on test die for fast feedback
        if ( $args['run'] === 'test' ) {
            die( 'BackupPilot test request' );
        }

        if ( $args['run'] === 'restart' ) {
            $job_object = BackupPilot_Job::get_working_data();

            // Restart if cannot find job
            if ( ! $job_object ) {
                BackupPilot_Job::start_http( 'restart' );

                return;
            }
            //restart job if not working or a restart wished
            $not_worked_time = microtime( true ) - $job_object->timestamp_last_update;

            if ( ! $job_object->pid || $not_worked_time > 300 ) {
                BackupPilot_Job::start_http( 'restart' );

                return;
            }
        }

        // generate normal nonce
        $nonce = substr( wp_hash( wp_nonce_tick() . 'backuppilot_job_run-' . $args['run'], 'nonce' ), - 12, 10 );

        //special nonce on external start (e.g. a third-party trigger like MainWP or an external cron service)
        if ( $args['run'] === 'runext' ) {
            $nonce = get_site_option( 'backuppilot_cfg_jobrunauthkey' );
        }

        //'cronrun' is only reachable via the internal call from BackupPilot_Cron::run() (never from $_GET, see above), so no nonce is required
        if ( $args['run'] === 'cronrun' ) {
            $nonce = '';
        }

        // check nonce
        if ( $nonce !== $args['nonce'] ) {
            return;
        }

        //check runext is allowed for job
        if ( $args['run'] === 'runext' ) {

            $jobids_link     = BackupPilot_Option::get_job_ids( 'activetype', 'link' );
            $jobids_easycron = BackupPilot_Option::get_job_ids( 'activetype', 'easycron' );
            $jobids_external = array_merge( $jobids_link, $jobids_easycron );

            if ( ! in_array( $args['jobid'], $jobids_external, true ) ) {
                return;
            }
        }

        //run BackupPilot job
        BackupPilot_Job::start_http( $args['run'], $args['jobid'] );
    }


    /**
     * Get the local time timestamp of the next cron execution
     * @since   1.0.0
     * @since   1.0.7  Updated minimum value for minutes to 5
     *
     * @param   string  $cronstring  cron (* * * * *).
     * @return  int                  Timestamp
     */

    public static function cron_next( $cronstring ) {

        $cron      = [];
        $cronarray = [];

        //Cron string
        list( $cronstr['minutes'], $cronstr['hours'], $cronstr['mday'], $cronstr['mon'], $cronstr['wday'] ) = explode( ' ', trim( $cronstring ), 5 );

        //make arrays form string
        foreach ( $cronstr as $key => $value ) {

            if ( strstr( $value, ',' ) ) {
                $cronarray[ $key ] = explode( ',', $value );
            }

            else {
                $cronarray[ $key ] = [ 0 => $value ];
            }
        }

        //make arrays complete with ranges and steps
        foreach ( $cronarray as $cronarraykey => $cronarrayvalue ) {

            $cron[ $cronarraykey ] = [];

            foreach ( $cronarrayvalue as $value ) {
                //steps
                $step = 1;

                if ( strstr( $value, '/' ) ) {
                    list( $value, $step ) = explode( '/', $value, 2 );
                }

                //replace weekday 7 with 0 for sundays
                if ( $cronarraykey === 'wday' ) {
                    $value = str_replace( '7', '0', $value );
                }

                //ranges
                if ( strstr( $value, '-' ) ) {

                    list( $first, $last ) = explode( '-', $value, 2 );

                    if ( ! is_numeric( $first ) || ! is_numeric( $last ) || $last > 60 || $first > 60 ) //check
                        return PHP_INT_MAX;

                    if ( $cronarraykey === 'minutes' && $step < 5 ) //set step minimum to 5 min.
                        $step = 5;

                    $range = [];

                    for ( $i = $first; $i <= $last; $i = $i + $step )
                        $range[] = $i;

                    $cron[ $cronarraykey ] = array_merge( $cron[ $cronarraykey ], $range );
                }

                elseif ( $value === '*' ) {

                    $range = [];

                    if ( $cronarraykey === 'minutes' ) {

                        if ( $step < 5 ) //set step minimum to 5 min.
                            $step = 5;

                        for ( $i = 0; $i <= 59; $i = $i + $step )
                            $range[] = $i;
                    }

                    if ( $cronarraykey === 'hours' ) {

                        for ( $i = 0; $i <= 23; $i = $i + $step )
                            $range[] = $i;
                    }

                    if ( $cronarraykey === 'mday' ) {

                        for ( $i = $step; $i <= 31; $i = $i + $step )
                            $range[] = $i;
                    }

                    if ( $cronarraykey === 'mon' ) {

                        for ( $i = $step; $i <= 12; $i = $i + $step )
                            $range[] = $i;
                    }

                    if ( $cronarraykey === 'wday' ) {

                        for ( $i = 0; $i <= 6; $i = $i + $step )
                            $range[] = $i;
                    }

                    $cron[ $cronarraykey ] = array_merge( $cron[ $cronarraykey ], $range );
                }

                else {

                    if ( ! is_numeric( $value ) || (int) $value > 60 )
                        return PHP_INT_MAX;

                    $cron[ $cronarraykey ] = array_merge( $cron[ $cronarraykey ], [ 0 => absint( $value ) ] );
                }
            }
        }

        //generate years
        $year = (int) gmdate( 'Y' );
        for ( $i = $year; $i < $year + 100; $i ++ ) {
            $cron['year'][] = $i;
        }

        //calc next timestamp
        $current_timestamp = (int) current_time( 'timestamp' );

        foreach ( $cron['year'] as $year ) {

            foreach ( $cron['mon'] as $mon ) {

                foreach ( $cron['mday'] as $mday ) {

                    if ( ! checkdate( $mon, $mday, $year ) ) {
                        continue;
                    }

                    foreach ( $cron['hours'] as $hours ) {

                        foreach ( $cron['minutes'] as $minutes ) {

                            $timestamp = gmmktime( $hours, $minutes, 0, $mon, $mday, $year );

                            if (
                                $timestamp &&
                                in_array( (int) gmdate( 'j', $timestamp ), $cron['mday'], true ) &&
                                in_array( (int) gmdate( 'w', $timestamp ), $cron['wday'], true ) &&
                                $timestamp > $current_timestamp
                            ) {
                                return $timestamp - ( (int) get_option( 'gmt_offset' ) * 3600 );
                            }
                        }
                    }
                }
            }
        }

        return PHP_INT_MAX;
    }
}
