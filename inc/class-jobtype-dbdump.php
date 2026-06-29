<?php
class BackupPilot_JobType_DBDump extends BackupPilot_JobTypes {

    public function __construct() {

        $this->info[ 'ID' ]          = 'DBDUMP';
        $this->info[ 'name' ]        = __( 'DB Backup', 'backuppilot' );
        $this->info[ 'description' ] = __( 'Database backup', 'backuppilot' );
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
        global $wpdb;
        /* @var wpdb $wpdb */

        $defaults = [
            'dbdumpexclude' => [], 'dbdumpfile' => sanitize_file_name( DB_NAME ), 'dbdumptype' => 'sql', 'dbdumpfilecompression' => ''
        ];
        //set only wordpress tables as default
        $dbtables = $wpdb->get_results( 'SHOW TABLES FROM `' . DB_NAME . '`', ARRAY_N );
        foreach ( $dbtables as $dbtable) {
            if ( $wpdb->prefix != substr( $dbtable[ 0 ], 0, strlen( $wpdb->prefix ) ) )
                $defaults[ 'dbdumpexclude' ][] = $dbtable[ 0 ];
        }

        return $defaults;
    }


    /**
     * @param $jobid
     */
    public function edit_tab( $jobid ) {
        global $wpdb;
        /* @var wpdb $wpdb */

        ?>
        <input name="dbdumpwpony" type="hidden" value="1" />
        <h3 class="title"><?php _e( 'Settings for database backup', 'backuppilot' ); ?></h3>
        <p></p>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Tables to backup', 'backuppilot' ); ?></th>
                <td>
                    <input type="button" class="button-secondary" id="dball" value="<?php esc_attr_e( 'all', 'backuppilot' ); ?>">&nbsp;
                    <input type="button" class="button-secondary" id="dbnone" value="<?php esc_attr_e( 'none', 'backuppilot' ); ?>">&nbsp;
                    <input type="button" class="button-secondary" id="dbwp" value="<?php echo esc_attr($wpdb->prefix); ?>">
                    <?php
                    $tables = $wpdb->get_results( 'SHOW FULL TABLES FROM `' . DB_NAME . '`', ARRAY_N );
                    echo '<fieldset id="dbtables"><div style="width: 30%; float:left; min-width: 250px; margin-right: 10px;">';
                    $next_row = ceil( count( $tables ) / 3 );
                    $counter = 0;
                    foreach ( $tables as $table ) {
                        $tabletype = '';
                        if ( $table[ 1 ] !== 'BASE TABLE' ) {
                            $tabletype = ' <i>(' . strtolower( esc_html( $table[ 1 ] ) ) . ')</i>';
                        }
                        echo '<label for="idtabledb-' . esc_html( $table[ 0 ] ) . '""><input class="checkbox" type="checkbox"' . checked( ! in_array( $table[ 0 ], BackupPilot_Option::get( $jobid, 'dbdumpexclude' ), true ), TRUE, FALSE ) . ' name="tabledb[]" id="idtabledb-' . esc_html( $table[ 0 ] ) . '" value="' . esc_html( $table[ 0 ] ) . '"/> ' . esc_html( $table[ 0 ] ) . $tabletype . '</label><br />';
                        $counter++;
                        if ($next_row <= $counter) {
                            echo '</div><div style="width: 30%; float:left; min-width: 250px; margin-right: 10px;">';
                            $counter = 0;
                        }
                    }
                    echo '</div></fieldset>';
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="iddbdumpfile"><?php _e( 'Backup file name', 'backuppilot' ); ?></label></th>
                <td>
                    <input id="iddbdumpfile" name="dbdumpfile" type="text"
                           value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'dbdumpfile' ) );?>"
                           class="medium-text code"/>.sql
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Backup file compression', 'backuppilot' ); ?></th>
                <td>
                    <fieldset>
                        <?php
                        echo '<label for="iddbdumpfilecompression"><input class="radio" type="radio"' . checked( '', BackupPilot_Option::get( $jobid, 'dbdumpfilecompression' ), FALSE ) . ' name="dbdumpfilecompression"  id="iddbdumpfilecompression" value="" /> ' . __( 'none', 'backuppilot' ). '</label><br />';
                        if ( function_exists( 'gzopen' ) ) {
                            echo '<label for="iddbdumpfilecompression-gz"><input class="radio" type="radio"' . checked( '.gz', BackupPilot_Option::get( $jobid, 'dbdumpfilecompression' ), FALSE ) . ' name="dbdumpfilecompression" id="iddbdumpfilecompression-gz" value=".gz" /> ' . __( 'GZip', 'backuppilot' ). '</label><br />';
                        }

                        else {
                            echo '<label for="iddbdumpfilecompression-gz"><input class="radio" type="radio"' . checked( '.gz', BackupPilot_Option::get( $jobid, 'dbdumpfilecompression' ), FALSE ) . ' name="dbdumpfilecompression" id="iddbdumpfilecompression-gz" value=".gz" disabled="disabled" /> ' . __( 'GZip', 'backuppilot' ). '</label><br />';
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
        global $wpdb;
        /* @var wpdb $wpdb */

        if ( $_POST[ 'dbdumpfilecompression' ] === '' || $_POST[ 'dbdumpfilecompression' ] === '.gz' ) {
            BackupPilot_Option::update( $id, 'dbdumpfilecompression', $_POST[ 'dbdumpfilecompression' ] );
        }
        BackupPilot_Option::update( $id, 'dbdumpfile', BackupPilot_Job::sanitize_file_name( $_POST[ 'dbdumpfile' ] ) );
        //selected tables
        $dbdumpexclude = [];
        $checked_db_tables = [];
        if ( isset( $_POST[ 'tabledb' ] ) ) {
            foreach ( $_POST[ 'tabledb' ] as $dbtable ) {
                $checked_db_tables[ ] = sanitize_text_field( $dbtable );
            }
        }
        $dbtables = $wpdb->get_results( 'SHOW TABLES FROM `' . DB_NAME . '`', ARRAY_N );
        foreach ( $dbtables as $dbtable ) {
            if ( ! in_array( $dbtable[ 0 ], $checked_db_tables, true ) ) {
                $dbdumpexclude[ ] = $dbtable[ 0 ];
            }
        }
        BackupPilot_Option::update( $id, 'dbdumpexclude', $dbdumpexclude );

    }

    /**
     * Dumps the Database
     *
     * @param $job_object BackupPilot_Job
     *
     * @return bool
     */
    public function job_run( BackupPilot_Job $job_object ) {

        $job_object->substeps_todo = 1;

        if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] )
            $job_object->log( sprintf( __( '%d. Try to backup database&#160;&hellip;', 'backuppilot' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) );

        //build filename
        if ( empty( $job_object->steps_data[ $job_object->step_working ][ 'dbdumpfile' ] ) )
            $job_object->steps_data[ $job_object->step_working ][ 'dbdumpfile' ] = $job_object->generate_filename( $job_object->job[ 'dbdumpfile' ], 'sql' ) . $job_object->job[ 'dbdumpfilecompression' ];

        try {

            //Connect to Database
            $sql_dump = new BackupPilot_MySQLDump( [
                                                     'dumpfile'   => BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->steps_data[ $job_object->step_working ][ 'dbdumpfile' ],
                                                ] );

            if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) {
                $job_object->log( sprintf( __( 'Connected to database %1$s on %2$s', 'backuppilot' ), DB_NAME, DB_HOST ) );
            }


            //Exclude Tables
            foreach ( $sql_dump->tables_to_dump as $key => $table ) {
                if ( in_array( $table, $job_object->job[ 'dbdumpexclude' ], true ) )
                    unset( $sql_dump->tables_to_dump[ $key ] );
            }

            //set steps must done
            $job_object->substeps_todo = count( $sql_dump->tables_to_dump );

            if ( $job_object->substeps_todo == 0 ) {
                $job_object->log( __( 'No tables to backup.', 'backuppilot' ), E_USER_WARNING );
                unset( $sql_dump );

                return TRUE;
            }

            //dump head
            if ( ! isset( $job_object->steps_data[ $job_object->step_working ][ 'is_head' ] ) ) {
                $sql_dump->dump_head( TRUE );
                $job_object->steps_data[ $job_object->step_working ][ 'is_head' ] = TRUE;
            }
            //dump tables
            $i = 0;
            foreach(  $sql_dump->tables_to_dump as $table ) {
                if ( $i < $job_object->substeps_done ) {
                    $i++;
                    continue;
                }
                if ( empty( $job_object->steps_data[ $job_object->step_working ][ 'tables' ][ $table ] ) ) {
                    $num_records = $sql_dump->dump_table_head( $table );
                    $job_object->steps_data[ $job_object->step_working ][ 'tables' ][ $table ] = [ 'start'   => 0,
                                                                                                        'length'   => 1000 ];
                    if ( $job_object->is_debug() ) {
                        $job_object->log( sprintf( __( 'Backup database table "%s" with "%s" records', 'backuppilot' ), $table, $num_records ) );
                    }
                }
                $while = true;
                while ( $while ) {
                    $dump_start_time = microtime( TRUE );
                    $done_records = $sql_dump->dump_table( $table ,$job_object->steps_data[ $job_object->step_working ][ 'tables' ][ $table ][ 'start' ], $job_object->steps_data[ $job_object->step_working ][ 'tables' ][ $table ][ 'length' ] );
                    $dump_time = microtime( TRUE ) - $dump_start_time;
                    if ( empty( $dump_time ) )
                        $dump_time = 0.01;
                    if ( $done_records < $job_object->steps_data[ $job_object->step_working ][ 'tables' ][ $table ][ 'length' ] ) //that is the last chunk
                        $while = FALSE;
                    $job_object->steps_data[ $job_object->step_working ][ 'tables' ][ $table ][ 'start' ] = $job_object->steps_data[ $job_object->step_working ][ 'tables' ][ $table ][ 'start' ] + $done_records;
                    // dump time per record and set next length
                    $length = ceil( ( $done_records / $dump_time ) * $job_object->get_restart_time() );
                    if ( $length > 25000 || 0 >= $job_object->get_restart_time() )
                        $length = 25000;
                    if ( $length < 1000 )
                        $length = 1000;
                    $job_object->steps_data[ $job_object->step_working ][ 'tables' ][ $table ][ 'length' ] =  $length;
                    $job_object->do_restart_time();
                }
                $sql_dump->dump_table_footer( $table );
                $job_object->substeps_done++;
                $i++;
                $job_object->update_working_data();
            }
            //dump footer
            $sql_dump->dump_footer();
            unset( $sql_dump );

        } catch ( Exception $e ) {
            $job_object->log( $e->getMessage(), E_USER_ERROR, $e->getFile(), $e->getLine() );
            unset( $sql_dump );
            return FALSE;
        }

        $filesize = filesize( BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->steps_data[ $job_object->step_working ][ 'dbdumpfile' ] );

        if ( ! is_file( BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->steps_data[ $job_object->step_working ][ 'dbdumpfile' ] ) || $filesize < 1 ) {
            $job_object->log( __( 'MySQL backup file not created', 'backuppilot' ), E_USER_ERROR );
            return FALSE;
        }

        else {
            $job_object->additional_files_to_backup[ ] = BackupPilot::get_plugin_data( 'TEMP' ) . $job_object->steps_data[ $job_object->step_working ][ 'dbdumpfile' ];
            $job_object->log( sprintf( __( 'Added database dump "%1$s" with %2$s to backup file list', 'backuppilot' ), $job_object->steps_data[ $job_object->step_working ][ 'dbdumpfile' ], size_format( $filesize, 2 ) ) );
        }

        //cleanups
        unset( $job_object->steps_data[ $job_object->step_working ][ 'tables' ] );

        $job_object->log( __( 'Database backup done!', 'backuppilot' ) );

        return TRUE;
    }

    public function admin_print_scripts() {

        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
            wp_enqueue_script( 'backuppilotjobtypedbdump', BackupPilot::get_plugin_data( 'URL' ) . '/assets/js/page_edit_jobtype_dbdump.js', ['jquery'], time(), TRUE );
        }

        else {
            wp_enqueue_script( 'backuppilotjobtypedbdump', BackupPilot::get_plugin_data( 'URL' ) . '/assets/js/page_edit_jobtype_dbdump.min.js', ['jquery'], BackupPilot::get_plugin_data( 'Version' ), TRUE );
        }
    }


}
