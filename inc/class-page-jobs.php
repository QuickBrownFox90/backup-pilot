<?php
/**
 * Class For BackupPilot Jobs page
 */
class BackupPilot_Page_Jobs extends WP_List_Table {

    private static $listtable = NULL;
    public static $logfile    = NULL;
    private $job_object       = NULL;
    private $job_types        = NULL;
    private $destinations     = NULL;


    public function __construct() {

        parent::__construct( [
            'plural'   => 'jobs',
            'singular' => 'job',
            'ajax'     => true
        ] );
    }


    /**
     * @return bool|void
     */

    public function ajax_user_can() {

        return current_user_can( 'backuppilot' );
    }

    /**
     * @return  void
     */

    public function prepare_items() {

        $this->items        = BackupPilot_Option::get_job_ids();
        $this->job_object   = BackupPilot_Job::get_working_data();
        $this->job_types    = BackupPilot::get_job_types();
        $this->destinations = BackupPilot::get_registered_destinations();

        if ( ! isset( $_GET[ 'order' ] ) || ! isset( $_GET[ 'orderby' ] ) )
            return;

        if ( strtolower( $_GET[ 'order' ] ) === 'asc' )
            $order = SORT_ASC;

        else
            $order = SORT_DESC;

        if ( empty( $_GET[ 'orderby' ] ) || ! in_array( strtolower( $_GET[ 'orderby' ] ), [ 'jobname', 'type', 'dest', 'next', 'last' ], true ) )
            $orderby = 'jobname';

        else
            $orderby = strtolower( $_GET[ 'orderby' ] );

        //sorting
        $job_configs = [];
        $i           = 0;

        foreach ( $this->items as $item ) {

            $job_configs[ $i ][ 'jobid' ]   = $item;
            $job_configs[ $i ][ 'jobname' ] = BackupPilot_Option::get( $item, 'name' );
            $job_configs[ $i ][ 'type' ]    = BackupPilot_Option::get( $item, 'type' );
            $job_configs[ $i ][ 'dest' ]    = BackupPilot_Option::get( $item, 'destinations' );

            if ( $order === SORT_ASC ) {
                sort( $job_configs[ $i ][ 'type' ] );
                sort( $job_configs[ $i ][ 'dest' ] );
            }

            else {
                rsort( $job_configs[ $i ][ 'type' ] );
                rsort( $job_configs[ $i ][ 'dest' ] );
            }

            $job_configs[ $i ][ 'type' ]    = array_shift( $job_configs[ $i ][ 'type' ] );
            $job_configs[ $i ][ 'dest' ]    = array_shift( $job_configs[ $i ][ 'dest' ] );
            $job_configs[ $i ][ 'next' ]    = (int) wp_next_scheduled( 'backuppilot_cron', [ 'arg' => $item ] );
            $job_configs[ $i ][ 'last' ]    = BackupPilot_Option::get( $item, 'lastrun' );

            $i++;
        }

        $tmp = [];

        foreach ( $job_configs as &$ma )
            $tmp[] = &$ma[ $orderby ];

        array_multisort( $tmp, $order, $job_configs );

        $this->items = [];

        foreach ( $job_configs as $item )
            $this->items[] = $item[ 'jobid' ];
    }

    public function no_items() {

        _e( 'No Jobs.', 'backuppilot' );
    }


    /**
     * @return array
     */

    public function get_bulk_actions() {

        if ( ! $this->has_items() ) {
            return [];
        }

        $actions             = [];
        $actions[ 'delete' ] = __( 'Delete', 'backuppilot' );

        return apply_filters( 'backuppilot_page_jobs_get_bulk_actions', $actions );
    }


    /**
     * Get the admin columns
     *
     * @since   1.0.0
     * @since   1.0.5  Added notes field
     *
     * @return  array
     */

    public function get_columns() {

        $jobs_columns              = [];
        $jobs_columns[ 'cb' ]      = '<input type="checkbox" />';
        $jobs_columns[ 'jobname' ] = __( 'Job Name', 'backuppilot' );
        $jobs_columns[ 'notes' ]   = __( 'Notes', 'backuppilot' );
        $jobs_columns[ 'type' ]    = __( 'Type', 'backuppilot' );
        $jobs_columns[ 'dest' ]    = __( 'Destinations', 'backuppilot' );
        $jobs_columns[ 'next' ]    = __( 'Next Run', 'backuppilot' );
        $jobs_columns[ 'last' ]    = __( 'Last Run', 'backuppilot' );

        return $jobs_columns;
    }


    /**
     * @return array
     */

    public function get_sortable_columns() {

        return [
            'jobname'   => 'jobname',
            'type'      => 'type',
            'dest'      => 'dest',
            'next'      => 'next',
            'last'      => 'last',
        ];
    }


    /**
     * The cb Column
     *
     * @param   string  $item  The item name as field value for checkbox markup
     * @return  string
     */

    public function column_cb( $item ) {

        return '<input type="checkbox" name="jobs[]" value="' . esc_attr( $item ) . '" />';
    }


    /**
     * The jobname Column
     *
     * @param  string  $item  The item name
     * @return string
     */

    public function column_jobname( $item ) {

        $job_normal_hide = '';

        if ( is_object( $this->job_object ) )
            $job_normal_hide = ' style="display:none;"';

        $r       = '<strong title="' . sprintf( __( 'Job ID: %d', 'backuppilot' ), $item ) . '">' . esc_html( BackupPilot_Option::get( $item, 'name' ) ) . '</strong>';
        $actions = [];

        if ( current_user_can( 'backuppilot_jobs_edit' ) ) {
            $actions[ 'edit' ]   = "<a href=\"" . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backuppiloteditjob&jobid=' . $item, 'edit-job' ) . "\">" . esc_html__( 'Edit', 'backuppilot' ) . "</a>";
            $actions[ 'copy' ]   = "<a href=\"" . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backuppilotjobs&action=copy&jobid=' . $item, 'copy-job_' . $item ) . "\">" . esc_html__( 'Copy', 'backuppilot' ) . "</a>";
            $actions[ 'delete' ] = "<a class=\"submitdelete\" href=\"" . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backuppilotjobs&action=delete&jobs[]=' . $item, 'bulk-jobs' ) . "\" onclick=\"return showNotice.warn();\">" . esc_html__( 'Delete', 'backuppilot' ) . "</a>";
        }

        if ( current_user_can( 'backuppilot_jobs_start' ) ) {
            $url                 = BackupPilot_Job::get_jobrun_url( 'runnowlink', $item );
            $actions[ 'runnow' ] = "<a href=\"" . esc_attr($url[ 'url' ]) . "\">" . esc_html__( 'Run now', 'backuppilot' ) . "</a>";
        }

        if ( current_user_can( 'backuppilot_logs' ) && BackupPilot_Option::get( $item, 'logfile' ) ) {

            $logfile = basename( BackupPilot_Option::get( $item, 'logfile' ) );

            if ( is_object( $this->job_object ) && $this->job_object->job[ 'jobid' ] == $item )
                $logfile = basename( $this->job_object->logfile );

            $log_name             = str_replace( [ '.html', '.gz' ], '', basename( $logfile ) );
            $actions[ 'lastlog' ] = '<a href="' . admin_url( 'admin-ajax.php' ) . '?&action=backuppilot_view_log&log=' . $log_name .'&_ajax_nonce=' . wp_create_nonce( 'view-log_'. $log_name ) . '&amp;TB_iframe=true&amp;width=640&amp;height=440\" title="' . esc_attr( $logfile ) . '" class="thickbox">' . __( 'Last log', 'backuppilot' ) . '</a>';
        }

        $actions  = apply_filters( 'backuppilot_page_jobs_actions', $actions, $item, FALSE );
        $r       .= '<div class="job-normal"' . $job_normal_hide . '>' . $this->row_actions( $actions ) . '</div>';

        if ( is_object( $this->job_object ) ) {

            $actionsrun  = [];
            $actionsrun  = apply_filters( 'backuppilot_page_jobs_actions', $actionsrun, $item, TRUE );
            $r          .= '<div class="job-run">' . $this->row_actions( $actionsrun ) . '</div>';
        }

        return $r;
    }


    /**
     * The notes Column
     *
     * @since   1.0.5
     *
     * @param   int      $item  Job ID.
     * @return  string
     */

    public function column_notes( $item ) {

        $notes = BackupPilot_Option::get( $item, 'notes' );

        if ( ! $notes )
            return '';

        $short = wp_trim_words( $notes, 20, '&hellip;' );

        if ( $short !== $notes )
            return '<span title="' . esc_attr( $notes ) . '">' . esc_html( $short ) . '</span>';

        return esc_html( $notes );
    }


    /**
     * The type Column
     *
     * @param   string  $item  The item name
     * @return  string
     */

    public function column_type( $item ) {

        $r = '';
        if ( $types = BackupPilot_Option::get( $item, 'type' ) ) {

            foreach ( $types as $type ) {

                if ( isset( $this->job_types[ $type ] ) )
                    $r .= $this->job_types[ $type ]->info[ 'name' ] . '<br />';

                else
                    $r .= $type . '<br />';
            }
        }

        return $r;
    }

    /**
     * The destination Column
     *
     * @param $item
     * @return string
     */
    public function column_dest( $item ) {

        $r = '';
        $backup_to = FALSE;
        foreach ( BackupPilot_Option::get( $item, 'type' ) as $typeid ) {
            if ( isset( $this->job_types[ $typeid ] ) && $this->job_types[ $typeid ]->creates_file() ) {
                $backup_to = TRUE;
                break;
            }
        }
        if ( $backup_to ) {
            foreach ( BackupPilot_Option::get( $item, 'destinations' ) as $destid ) {
                if ( isset( $this->destinations[ $destid ][ 'info' ][ 'name' ] ) ) {
                    $r .= $this->destinations[ $destid ][ 'info' ][ 'name' ] . '<br />';
                }

                else {
                    $r .= $destid . '<br />';
                }
            }
        }
        else {
            $r .= '<i>' . __( 'Not needed or set', 'backuppilot' ) . '</i><br />';
        }

        return $r;
    }

    /**
     * The next Column
     *
     * @param $item
     * @return string
     */
    public function column_next( $item ) {

        $r = '';

        $job_normal_hide = '';
        if ( is_object( $this->job_object ) ) {
            $job_normal_hide = ' style="display:none;"';
        }
        if ( is_object( $this->job_object ) && $this->job_object->job[ 'jobid' ] == $item ) {
            $runtime = current_time( 'timestamp' ) - $this->job_object->start_time;
            $r .= '<div class="job-run">' . sprintf( esc_html__( 'Running for: %s seconds', 'backuppilot' ), '<span id="runtime">' . $runtime . '</span>' ) .'</div>';
        }
        if ( is_object( $this->job_object ) && $this->job_object->job[ 'jobid' ] == $item ) {
            $r .='<div class="job-normal"' . $job_normal_hide . '>';
        }
        if ( BackupPilot_Option::get( $item, 'activetype' ) == 'wpcron' ) {
            if ( $nextrun = wp_next_scheduled( 'backuppilot_cron', [ 'arg' => $item ] ) + ( get_option( 'gmt_offset' ) * 3600 )  ) {
                $r .= '<span title="' . sprintf( esc_html__( 'Cron: %s','backuppilot'),BackupPilot_Option::get( $item, 'cron' ) ). '">' . sprintf( __( '%1$s at %2$s by WP-Cron', 'backuppilot' ) , date_i18n( get_option( 'date_format' ), $nextrun, TRUE ) , date_i18n( get_option( 'time_format' ), $nextrun, TRUE ) ) . '</span><br />';
            }

            else {
                $r .= __( 'Not scheduled!', 'backuppilot' ) . '<br />';
            }
        }
        elseif ( BackupPilot_Option::get( $item, 'activetype' ) == 'easycron' ) {
            $easycron_status = BackupPilot_EasyCron::status( $item );
            if ( !empty( $easycron_status ) ) {
                $nextrun = BackupPilot_Cron::cron_next( $easycron_status[ 'cron_expression' ] ) + ( get_option( 'gmt_offset' ) * 3600 );
                $r .= '<span title="' . sprintf( esc_html__( 'Cron: %s','backuppilot'), $easycron_status[ 'cron_expression' ] ). '">' . sprintf( __( '%1$s at %2$s by EasyCron', 'backuppilot' ) , date_i18n( get_option( 'date_format' ), $nextrun, TRUE ) , date_i18n( get_option( 'time_format' ), $nextrun, TRUE ) ) . '</span><br />';
            }

            else {
                $r .= __( 'Not scheduled!', 'backuppilot' ) . '<br />';
            }
        }
        elseif ( BackupPilot_Option::get( $item, 'activetype' ) == 'link' ) {
            $r .= __( 'External link', 'backuppilot' ) . '<br />';
        }
        else {
            $r .= __( 'Inactive', 'backuppilot' );
        }
        if ( is_object( $this->job_object ) && $this->job_object->job[ 'jobid' ] == $item ) {
            $r .= '</div>';
        }
        return $r;
    }

    /**
     * The last Column
     *
     * @param $item
     * @return string
     */
    public function column_last( $item ) {

        $r = '';

        if ( BackupPilot_Option::get( $item, 'lastrun' ) ) {
            $lastrun = BackupPilot_Option::get( $item, 'lastrun' );
            $r .= sprintf( __( '%1$s at %2$s', 'backuppilot' ), date_i18n( get_option( 'date_format' ), $lastrun, TRUE ), date_i18n( get_option( 'time_format' ), $lastrun, TRUE ) );
            if ( BackupPilot_Option::get( $item, 'lastruntime' ) ) {
                $r .= '<br />' . sprintf( __( 'Runtime: %d seconds', 'backuppilot' ), BackupPilot_Option::get( $item, 'lastruntime' ) );
            }
        }
        else {
            $r .= __( 'not yet', 'backuppilot' );
        }
        $r .= "<br /><span class=\"last-action-links\">";
        if ( current_user_can( 'backuppilot_backups_download' ) ) {
            $download_url = BackupPilot_Option::get( $item, 'lastbackupdownloadurl' );
            if ( ! empty( $download_url ) ) {
                $r .= "<a  href=\"" . wp_nonce_url( $download_url, 'backuppilot_action_nonce' ). "\" title=\"" . esc_attr( __( 'Download last backup', 'backuppilot' ) ) . "\">" . esc_html__( 'Download', 'backuppilot' ) . "</a> | ";
            }
        }
        if ( current_user_can( 'backuppilot_logs' ) && BackupPilot_Option::get( $item, 'logfile' ) ) {
            $logfile = basename( BackupPilot_Option::get( $item, 'logfile' ) );
            if ( is_object( $this->job_object ) && $this->job_object->job[ 'jobid' ] == $item ) {
                $logfile = basename( $this->job_object->logfile );
            }
            $log_name = str_replace( [ '.html', '.gz' ], '', basename( $logfile ) );
            $r .= '<a class="thickbox" href="' . admin_url( 'admin-ajax.php' ) . '?&action=backuppilot_view_log&log=' . $log_name .'&_ajax_nonce=' . wp_create_nonce( 'view-log_' . $log_name ) . '&amp;TB_iframe=true&amp;width=640&amp;height=440" title="' . esc_attr( $logfile ) . '">' . esc_html__( 'Log', 'backuppilot' ) . '</a>';

        }
        $r .= "</span>";

        return $r;
    }

    public static function load() {

        //Create Table
        self::$listtable = new self;

        switch ( self::$listtable->current_action() ) {
            case 'delete': //Delete Job
                if ( ! current_user_can( 'backuppilot_jobs_edit' ) ) {
                    break;
                }
                if ( is_array( $_GET[ 'jobs' ] ) ) {
                    check_admin_referer( 'bulk-jobs' );
                    foreach ( $_GET[ 'jobs' ] as $jobid ) {
                        wp_clear_scheduled_hook( 'backuppilot_cron', [ 'arg' => absint( $jobid ) ] );
                        BackupPilot_Option::delete_job( absint( $jobid ) );
                    }
                }
                break;
            case 'copy': //Copy Job
                if ( ! current_user_can( 'backuppilot_jobs_edit' ) ) {
                    break;
                }
                $old_job_id = absint( $_GET[ 'jobid' ] );
                check_admin_referer( 'copy-job_' . $old_job_id );
                //create new
                $newjobid = BackupPilot_Option::get_job_ids();
                sort( $newjobid );
                $newjobid    = end( $newjobid ) + 1;
                $old_options = BackupPilot_Option::get_job( $old_job_id );
                foreach ( $old_options as $key => $option ) {
                    if ( $key === "jobid" )
                        $option = $newjobid;
                    if ( $key === "name" )
                        $option = __( 'Copy of', 'backuppilot' ) . ' ' . $option;
                    if ( $key === "activetype" )
                        $option = '';
                    if ( $key === "archivename" )
                        $option = str_replace( $old_job_id, $newjobid, $option );
                    if ( $key === "logfile" || $key === "lastbackupdownloadurl" || $key === "lastruntime" || $key === "lastrun" )
                        continue;
                    BackupPilot_Option::update( $newjobid, $key, $option );
                }
            break;

            case 'runnow':

                $jobid = absint( $_GET[ 'jobid' ] );

                if ( $jobid ) {

                    if ( ! current_user_can( 'backuppilot_jobs_start' ) )
                        wp_die( __( 'Sorry, you don\'t have permissions to do that.', 'backuppilot') );

                    check_admin_referer( 'backuppilot_job_run-runnowlink' );

                    //check temp folder
                    $temp_folder_message = BackupPilot_File::check_folder( BackupPilot::get_plugin_data( 'TEMP' ), TRUE );
                    BackupPilot_Admin::message( $temp_folder_message, TRUE );

                    //check log folder
                    $log_folder = get_site_option( 'backuppilot_cfg_logfolder' );
                    $log_folder = BackupPilot_File::get_absolute_path( $log_folder );
                    $log_folder_message = BackupPilot_File::check_folder( $log_folder );
                    BackupPilot_Admin::message( $log_folder_message, TRUE );

                    //check backup destinations
                    $job_types = BackupPilot::get_job_types();
                    $job_conf_types = BackupPilot_Option::get( $jobid, 'type' );
                    $creates_file = FALSE;

                    foreach ( $job_types as $id => $job_type_class ) {

                        if ( in_array( $id, $job_conf_types, true ) && $job_type_class->creates_file( ) ) {
                            $creates_file = TRUE;
                            break;
                        }
                    }

                    if ( $creates_file ) {

                        $job_conf_dests = BackupPilot_Option::get( $jobid, 'destinations' );
                        $destinations = 0;

                        /* @var BackupPilot_Destinations $dest_class */
                        foreach ( BackupPilot::get_registered_destinations() as $id => $dest ) {

                            if ( ! in_array( $id, $job_conf_dests, true ) || empty( $dest[ 'class' ] ) )
                                continue;

                            $dest_class = BackupPilot::get_destination( $id );
                            $job_settings = BackupPilot_Option::get_job( $jobid );

                            if ( ! $dest_class->can_run( $job_settings ) )
                                BackupPilot_Admin::message( sprintf( __( 'The job "%s" destination "%s" is not configured properly','backuppilot' ), esc_attr( BackupPilot_Option::get( $jobid, 'name' ) ), $id ), TRUE );

                            $destinations++;
                        }

                        if ( $destinations < 1 ) {
                            BackupPilot_Admin::message( sprintf( __( 'The job "%s" needs properly configured destinations to run!','backuppilot' ), esc_attr( BackupPilot_Option::get( $jobid, 'name' ) ) ), TRUE );
                        }
                    }

                    //only start job if messages empty
                    $log_messages = BackupPilot_Admin::get_messages();

                    if ( empty( $log_messages ) ) {

                        $old_log_file = BackupPilot_Option::get( $jobid, 'logfile' );
                        BackupPilot_Job::get_jobrun_url( 'runnow', $jobid );

                        usleep( 250000 ); //wait a quarter second

                        $new_log_file = BackupPilot_Option::get( $jobid, 'logfile', null, false );

                        //sleep as long as job not started
                        $i = 0;

                        while ( $old_log_file === $new_log_file ) {

                            usleep( 250000 ); //wait a quarter second for next try
                            $new_log_file = BackupPilot_Option::get( $jobid, 'logfile', null, false );

                            //wait maximal 10 sec.
                            if ( $i >= 40 ) {
                                BackupPilot_Admin::message( sprintf( __( 'Job "%s" has started, but not responded for 10 seconds. Please check <a href="%s">information</a>.', 'backuppilot' ), esc_attr( BackupPilot_Option::get( $jobid, 'name' ) ), network_admin_url( 'admin.php' ) . '?page=backuppilotsettings#backuppilot-tab-information' ), true );
                                break 2;
                            }

                            $i++;
                        }

                        BackupPilot_Admin::message( sprintf( __( 'Job "%s" started.', 'backuppilot' ), esc_attr( BackupPilot_Option::get( $jobid, 'name' ) ) ) );
                    }
                }
            break;

            case 'abort': //Abort Job

                if ( ! current_user_can( 'backuppilot_jobs_start' ) )
                    break;

                check_admin_referer( 'abort-job' );

                if ( file_exists( BackupPilot::get_plugin_data( 'running_file' ) ) ) {
                    BackupPilot_Job::user_abort();
                    BackupPilot_Admin::message( __( 'Job will be terminated.', 'backuppilot' ) );
                }

                wp_safe_redirect( network_admin_url( 'admin.php?page=backuppilotjobs' ) );

                exit;

            default:
                do_action( 'backuppilot_page_jobs_load', self::$listtable->current_action() );
        }

        self::$listtable->prepare_items();
    }

    public static function admin_print_styles() {

        ?>
        <style type="text/css" media="screen">

            .column-last, .column-next, .column-type, .column-dest {
                width: 15%;
            }

            #TB_ajaxContent {
                background-color: black;
                color: #c0c0c0;
            }

            #showworking {
                white-space:nowrap;
                display: block;
                width: 100%;
                font-family:monospace;
                font-size:12px;
                line-height:15px;
            }
            #runningjob {
                padding:10px;
                position:relative;
                margin: 15px 0 25px 0;
                padding-bottom:25px;
            }
            h2#runningtitle {
                margin-bottom: 15px;
                padding: 0;
            }
            #warningsid, #errorid {
                margin-right: 10px;
            }

            .infobuttons {
                position: absolute;
                right: 10px;
                bottom: 0;
            }

            .progressbar {
                margin-top: 20px;
                height: auto;
                background: #f6f6f6 url('<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/progressbarhg.jpg');
            }

            #lastmsg, #onstep, #lasterrormsg {
                text-align: center;
                margin-bottom: 20px;
            }
            #backuppilot-page #lastmsg,
            #backuppilot-page #onstep,
            #backuppilot-page #lasterrormsg {
                font-family: "Open Sans", sans-serif;
            }
            .bwpu-progress {
                background-color: #1d94cf;
                color: #fff;
                padding: 5px 0;
                text-align: center;
            }
            #progresssteps {
                background-color: #007fb6;
            }

            .row-actions .lastlog {
                display: none;
            }

            @media screen and (max-width: 782px) {
                .column-type, .column-dest {
                    display: none;
                }
                .row-actions .lastlog {
                    display: inline-block;
                }
                .last-action-links {
                    display: none;
                }
            }
        </style>
        <?php
    }


    public static function admin_print_scripts() {

        wp_enqueue_script( 'backuppilotgeneral' );
    }


    public static function page() {

        echo '<div class="wrap" id="backuppilot-page">';
        echo '<h1>' . esc_html( sprintf( __( '%s &rsaquo; Jobs', 'backuppilot' ), BackupPilot::get_plugin_data( 'name' ) ) ) . '&nbsp;<a href="' . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backuppiloteditjob', 'edit-job' ) . '" class="add-new-h2">' . esc_html__( 'Add new', 'backuppilot' ) . '</a></h1>';

        BackupPilot_Admin::display_messages();

        $job_object = BackupPilot_Job::get_working_data();

        if ( current_user_can( 'backuppilot_jobs_start' ) && is_object( $job_object )  ) {

                //read existing logfile
                $logfiledata = file_get_contents( $job_object->logfile );
                preg_match( '/<body[^>]*>/si', $logfiledata, $match );

                if ( ! empty( $match[ 0 ] ) )
                    $startpos = strpos( $logfiledata, $match[ 0 ] ) + strlen( $match[ 0 ] );

                else
                    $startpos = 0;

                $endpos = stripos( $logfiledata, '</body>' );

                if ( empty( $endpos ) )
                    $endpos = strlen( $logfiledata );

                $length = strlen( $logfiledata ) - ( strlen( $logfiledata ) - $endpos ) - $startpos;

                ?>
            <div id="runningjob">
                <div id="runniginfos">
                    <h2 id="runningtitle"><?php echo esc_html( sprintf( __( 'Job currently running: %s', 'backuppilot' ), $job_object->job[ 'name' ] ) ); ?></h2>
                    <span id="warningsid"><?php esc_html_e( 'Warnings:', 'backuppilot' ); ?> <span id="warnings"><?php echo $job_object->warnings; ?></span></span>
                    <span id="errorid"><?php esc_html_e( 'Errors:', 'backuppilot' ); ?> <span id="errors"><?php echo $job_object->errors; ?></span></span>
                    <div class="infobuttons"><a href="#TB_inline?height=440&width=630&inlineId=tb-showworking" id="showworkingbutton" class="thickbox button button-primary button-primary-bwp" title="<?php esc_attr_e( 'Log of running job', 'backuppilot'); ?>"><?php esc_html_e( 'Display working log', 'backuppilot' ); ?></a>
                    <a href="<?php echo wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backuppilotjobs&action=abort', 'abort-job' ); ?>" id="abortbutton" class="backuppilot-fancybox button button-bwp"><?php esc_html_e( 'Abort', 'backuppilot' ); ?></a>
                    <a href="#" id="showworkingclose" title="<?php esc_html_e( 'Close working screen', 'backuppilot'); ?>" class="button button-bwp" style="display:none" ><?php esc_html_e( 'Close', 'backuppilot' ); ?></a></div>
                </div>
                <input type="hidden" name="logpos" id="logpos" value="<?php echo strlen( $logfiledata ); ?>">
                <div id="lasterrormsg"></div>
                <div class="progressbar"><div id="progressstep" class="bwpu-progress" style="width:<?php echo $job_object->step_percent; ?>%;"><?php echo  esc_html($job_object->step_percent); ?>%</div></div>
                <div id="onstep"><?php echo esc_html($job_object->steps_data[ $job_object->step_working ][ 'NAME' ]); ?></div>
                <div class="progressbar"><div id="progresssteps" class="bwpu-progress" style="width:<?php echo $job_object->substep_percent; ?>%;"><?php echo esc_html($job_object->substep_percent); ?>%</div></div>
                <div id="lastmsg"><?php echo esc_html($job_object->lastmsg); ?></div>
                <div id="tb-showworking" style="display:none;">
                    <div id="showworking"><?php echo substr( $logfiledata, $startpos, $length ); ?></div>
                </div>
            </div>
        <?php }

        //display jos Table
        ?>
        <form id="posts-filter" action="" method="get">
        <input type="hidden" name="page" value="backuppilotjobs" />
        <?php
            echo wp_nonce_field( 'backuppilot_ajax_nonce', 'backuppilotajaxnonce', FALSE );
            self::$listtable->display();
        ?>
        <div id="ajax-response"></div>
        </form>
        </div>
        <?php

        if ( ! empty( $job_object->logfile ) ) { ?>
            <script type="text/javascript">
                //<![CDATA[
                jQuery(document).ready(function ($) {
                    backuppilot_show_working = function () {

                        var save_log_pos = 0;

                        $.ajax( {
                            type: 'GET',
                            url: ajaxurl,
                            cache: false,
                            data:{
                                action: 'backuppilot_working',
                                logpos: $('#logpos').val(),
                                logfile: '<?php echo basename( $job_object->logfile );?>',
                                _ajax_nonce: '<?php echo wp_create_nonce( 'backuppilotworking_ajax_nonce' );?>'
                            },
                            dataType: 'json',
                            success: function ( rundata ) {
                                if ( rundata == 0 ) {
                                    $("#abortbutton").remove();
                                    $("#backuppilot-adminbar-running").remove();
                                    $(".job-run").hide();
                                    $("#message").hide();
                                    $(".job-normal").show();
                                    $('#showworkingclose').show();
                                }

                                if (0 < rundata.log_pos) {
                                    $('#logpos').val(rundata.log_pos);
                                }

                                if ('' != rundata.log_text) {
                                    $('#showworking').append(rundata.log_text);
                                    $('#TB_ajaxContent').scrollTop(rundata.log_pos * 15);
                                }

                                if (0 < rundata.error_count) {
                                    $('#errors').replaceWith('<span id="errors">' + rundata.error_count + '</span>');
                                }

                                if (0 < rundata.warning_count) {
                                    $('#warnings').replaceWith('<span id="warnings">' + rundata.warning_count + '</span>');
                                }

                                if (0 < rundata.step_percent) {
                                    $('#progressstep').replaceWith('<div id="progressstep" class="bwpu-progress">' + rundata.step_percent + '%</div>');
                                    $('#progressstep').css('width', parseFloat(rundata.step_percent) + '%');
                                }

                                if (0 < rundata.sub_step_percent) {
                                    $('#progresssteps').replaceWith('<div id="progresssteps" class="bwpu-progress">' + rundata.sub_step_percent + '%</div>');
                                    $('#progresssteps').css('width', parseFloat(rundata.sub_step_percent) + '%');
                                }

                                if (0 < rundata.running_time) {
                                    $('#runtime').replaceWith('<span id="runtime">' + rundata.running_time + '</span>');
                                }

                                if ( '' != rundata.onstep ) {
                                    $('#onstep').replaceWith('<div id="onstep">' + rundata.on_step + '</div>');
                                }

                                if ( '' != rundata.last_msg ) {
                                    $('#lastmsg').replaceWith('<div id="lastmsg">' + rundata.last_msg + '</div>');
                                }

                                if ( '' != rundata.last_error_msg ) {
                                    $('#lasterrormsg').replaceWith('<div id="lasterrormsg">' + rundata.last_error_msg + '</div>');
                                }

                                if ( rundata.job_done == 1 ) {
                                    $("#abortbutton").remove();
                                    $("#backuppilot-adminbar-running").remove();
                                    $(".job-run").hide();
                                    $("#message").hide();
                                    $(".job-normal").show();
                                    $('#showworkingclose').show();
                                }

                                else {
                                    if ( rundata.restart_url !== '' ) {
                                        backuppilot_trigger_cron( rundata.restart_url );
                                    }
                                    setTimeout('backuppilot_show_working()', 750);
                                }
                            },
                            error:function( ) {
                                setTimeout('backuppilot_show_working()', 750);
                            }
                        } );
                    };

                    backuppilot_trigger_cron = function ( cron_url ) {
                        $.ajax( {
                            type:        'POST',
                            url:         cron_url,
                            dataType:    'text',
                            cache:       false,
                            processData: false,
                            timeout:     1
                        } );
                    };

                    backuppilot_show_working();

                    $('#showworkingclose').click( function() {
                        $("#runningjob").hide( 'slow' );
                        return false;
                    });
                });
                //]]>
            </script>
        <?php }
    }


    /**
     * Function to generate json data
     */

    public static function ajax_working() {

        check_ajax_referer( 'backuppilotworking_ajax_nonce' );

        if ( ! current_user_can( 'backuppilot_jobs_start' ) ) {
            die( '-1' );
        }

        $log_folder  = get_site_option( 'backuppilot_cfg_logfolder' );
        $log_folder  = BackupPilot_File::get_absolute_path( $log_folder );
        $logfile     = isset( $_GET[ 'logfile' ] ) ? $log_folder . basename( trim( $_GET[ 'logfile' ] ) ) : NULL;
        $logpos      = isset( $_GET[ 'logpos' ] ) ? absint( $_GET[ 'logpos' ] ) : 0;
        $restart_url = '';

        //check if logfile renamed
        if ( file_exists( $logfile . '.gz' ) )
            $logfile .= '.gz';

        if ( ! is_readable( $logfile ) || strstr( $_GET[ 'logfile' ], 'backuppilot_log_' ) === false )
            die( '0' );

        $job_object = BackupPilot_Job::get_working_data();
        $done       = 0;

        if ( is_object( $job_object ) ) {
            $warnings        = $job_object->warnings;
            $errors          = $job_object->errors;
            $step_percent    = $job_object->step_percent;
            $substep_percent = $job_object->substep_percent;
            $runtime         = current_time( 'timestamp' ) - $job_object->start_time;
            $onstep          = $job_object->steps_data[ $job_object->step_working ][ 'NAME' ];
            $lastmsg         = $job_object->lastmsg;
            $lasterrormsg    = $job_object->lasterrormsg;
        }

        else {
            $logheader       = BackupPilot_Job::read_logheader( $logfile );
            $warnings        = $logheader[ 'warnings' ];
            $runtime         = $logheader[ 'runtime' ];
            $errors          = $logheader[ 'errors' ];
            $step_percent    = 100;
            $substep_percent = 100;
            $onstep          = '<div class="backuppilot-message backuppilot-info"><p>' . esc_html__( 'Job completed' , 'backuppilot' ) . '</p></div>';

            if ( $errors > 0 )
                $lastmsg = '<div class="bwu-message-error"><p>' . esc_html__( 'ERROR:', 'backuppilot' ) . ' ' .  sprintf( esc_html__( 'Job has ended with errors in %s seconds. You must resolve the errors for correct execution.', 'backuppilot' ), $logheader[ 'runtime' ] ) . '</p></div>';

            elseif ( $warnings > 0 )
                $lastmsg = '<div class="backuppilot-message backuppilot-warning"><p>' . esc_html__( 'WARNING:', 'backuppilot' ) . ' ' .  sprintf( esc_html__( 'Job has done with warnings in %s seconds. Please resolve them for correct execution.', 'backuppilot' ), $logheader[ 'runtime' ] ) . '</p></div>';

            else
                $lastmsg  = '<div class="updated"><p>' .  sprintf( esc_html__( 'Job done in %s seconds.', 'backuppilot' ), $logheader[ 'runtime' ] ) . '</p></div>';

            $lasterrormsg = '';
            $done         = 1;
        }

        if ( '.gz' == substr( $logfile, -3 ) )
            $logfiledata = file_get_contents( 'compress.zlib://' . $logfile, FALSE, NULL, $logpos );

        else
            $logfiledata = file_get_contents( $logfile, FALSE, NULL, $logpos );

        preg_match( '/<body[^>]*>/si', $logfiledata, $match );

        if ( ! empty( $match[ 0 ] ) )
            $startpos = strpos( $logfiledata, $match[ 0 ] ) + strlen( $match[ 0 ] );

        else
            $startpos = 0;

        $endpos = stripos( $logfiledata, '</body>' );

        if ( FALSE === $endpos )
            $endpos = strlen( $logfiledata );

        $length = strlen( $logfiledata ) - ( strlen( $logfiledata ) - $endpos ) - $startpos;

        //check if restart must done on ALTERNATE_WP_CRON
        if ( is_object( $job_object ) && defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {

            $restart = BackupPilot_Job::get_jobrun_url( 'restartalt' );

            if ( $job_object->pid === 0 && $job_object->uniqid === '' )
                $restart_url = $restart[ 'url' ];

            $last_update = microtime( TRUE ) - $job_object->timestamp_last_update;

            if ( empty( $job_object->pid ) && $last_update > 10 )
                $restart_url = $restart[ 'url' ];
        }

        wp_send_json( [
            'log_pos'         => strlen( $logfiledata ) + $logpos,
            'log_text'        => substr( $logfiledata, $startpos, $length ),
            'warning_count'   => $warnings,
            'error_count'     => $errors,
            'running_time'    => $runtime,
            'step_percent'    => $step_percent,
            'on_step'         => $onstep,
            'last_msg'        => $lastmsg,
            'last_error_msg'  => $lasterrormsg,
            'sub_step_percent'=> $substep_percent,
            'restart_url'     => $restart_url,
            'job_done'        => $done
        ] );
    }
}