<?php
class BackupPilot_JobType_DBCheck extends BackupPilot_JobTypes {

    public function __construct() {

        $this->info[ 'ID' ]          = 'DBCHECK';
        $this->info[ 'name' ]        = __( 'DB Check', 'backuppilot' );
        $this->info[ 'description' ] = __( 'Check database tables', 'backuppilot' );
        $this->info[ 'URI' ]         = __( 'http://backwpup.com', 'backuppilot' );
        $this->info[ 'author' ]      = 'Inpsyde GmbH';
        $this->info[ 'authorURI' ]   = __( 'http://inpsyde.com', 'backuppilot' );
        $this->info[ 'version' ]     = BackupPilot::get_plugin_data( 'Version' );

    }

    /**
     * @return array
     */
    public function option_defaults() {
        return [ 'dbcheckwponly' => TRUE, 'dbcheckrepair' => FALSE ];
    }


    /**
     * @param $jobid
     */
    public function edit_tab( $jobid ) {
        ?>
        <h3 class="title"><?php esc_html_e( 'Settings for database check', 'backuppilot' ); ?></h3>
        <p></p>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'WordPress tables only', 'backuppilot' ); ?></th>
                <td>
                    <label for="iddbcheckwponly">
                    <input class="checkbox" value="1" id="iddbcheckwponly"
                           type="checkbox" <?php checked( BackupPilot_Option::get( $jobid, 'dbcheckwponly' ), TRUE ); ?>
                           name="dbcheckwponly"/> <?php esc_html_e( 'Check WordPress database tables only', 'backuppilot' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Repair', 'backuppilot' ); ?></th>
                <td>
                    <label for="iddbcheckrepair">
                    <input class="checkbox" value="1" id="iddbcheckrepair"
                           type="checkbox" <?php checked( BackupPilot_Option::get( $jobid, 'dbcheckrepair' ), TRUE ); ?>
                           name="dbcheckrepair" /> <?php esc_html_e( 'Try to repair defect table', 'backuppilot' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }


    /**
     * @param $jobid
     */
    public function edit_form_post_save( $jobid ) {
        BackupPilot_Option::update( $jobid, 'dbcheckwponly', ! empty( $_POST[ 'dbcheckwponly' ] ) );
        BackupPilot_Option::update( $jobid, 'dbcheckrepair', ! empty( $_POST[ 'dbcheckrepair' ] ) );
    }

    /**
     * @param $job_object
     * @return bool
     */
    public function job_run( BackupPilot_Job $job_object ) {
        global $wpdb;
        /* @var wpdb $wpdb */

        $job_object->log( sprintf( __( '%d. Trying to check database&#160;&hellip;', 'backuppilot' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) );
        if ( ! isset( $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] ) || ! is_array( $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] ) )
            $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] = [];

        //to check
        $tables = [];
        $tablestype = [];
        $restables = $wpdb->get_results( 'SHOW FULL TABLES FROM `' . DB_NAME . '`', ARRAY_N );
        foreach ( $restables as $table ) {
            if ( $job_object->job[ 'dbcheckwponly' ] && substr( $table[ 0 ], 0, strlen( $wpdb->prefix ) ) != $wpdb->prefix )
                continue;
            $tables[ ]                 = $table[ 0 ];
            $tablestype[ $table[ 0 ] ] = $table[ 1 ];
        }

        //Set num
        $job_object->substeps_todo = sizeof( $tables );

        //Get table status
        $status = [];
        $resstatus = $wpdb->get_results( "SHOW TABLE STATUS FROM `" . DB_NAME . "`", ARRAY_A );
        foreach ( $resstatus as $tablestatus ) {
            $status[ $tablestatus[ 'Name' ] ] = $tablestatus;
        }

        //check tables
        if ( $job_object->substeps_todo > 0 ) {
            foreach ( $tables as $table ) {
                if ( in_array( $table, $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ], true ) )
                    continue;

                if ( $tablestype[ $table ] == 'VIEW' ) {
                    $job_object->log( sprintf( __( 'Table %1$s is a view. Not checked.', 'backuppilot' ), $table ) );
                    continue;
                }

                if ( $status[ $table ][ 'Engine' ] != 'MyISAM' && $status[ $table ][ 'Engine' ] != 'InnoDB' ) {
                    $job_object->log( sprintf( __( 'Table %1$s is not a MyISAM/InnoDB table. Not checked.', 'backuppilot' ), $table ) );
                    continue;
                }

                //CHECK TABLE funktioniert bei MyISAM- und InnoDB-Tabellen (http://dev.mysql.com/doc/refman/5.1/de/check-table.html)
                //Note: CHECK TABLE can return multiple rows (e.g. InnoDB often adds an informational
                //"Auto_increment will be checked..." status row before the final OK row), so all rows
                //must be inspected instead of just the first one.
                $check_rows = $wpdb->get_results( "CHECK TABLE `" . $table . "` MEDIUM", OBJECT );
                list( $check_level, $check_text ) = self::evaluate_check_result( $check_rows );
                if ( $check_level === E_USER_ERROR ) {
                    $job_object->log( sprintf( __( 'Result of table check for %1$s is: %2$s', 'backuppilot' ), $table, $check_text ), E_USER_ERROR );
                }

                elseif ( $check_level === E_USER_WARNING ) {
                    $job_object->log( sprintf( __( 'Result of table check for %1$s is: %2$s', 'backuppilot' ), $table, $check_text ), E_USER_WARNING );
                }

                elseif ( $job_object->is_debug() ) {
                    $job_object->log( sprintf( __( 'Result of table check for %1$s is: %2$s', 'backuppilot' ), $table, $check_text ) );
                }
                //Try to Repair table
                if ( ! empty( $job_object->job[ 'dbcheckrepair' ] ) && $check_level !== false && $status[ $table ][ 'Engine' ] == 'MyISAM' ) {
                    $repair_rows = $wpdb->get_results( 'REPAIR TABLE `' . $table . '` EXTENDED', OBJECT );
                    list( $repair_level, $repair_text ) = self::evaluate_check_result( $repair_rows );
                    if ( $repair_level === E_USER_ERROR ) {
                        $job_object->log( sprintf( __( 'Result of table repair for %1$s is: %2$s', 'backuppilot' ), $table, $repair_text ), E_USER_ERROR );
                    }

                    elseif ( $repair_level === E_USER_WARNING ) {
                        $job_object->log( sprintf( __( 'Result of table repair for %1$s is: %2$s', 'backuppilot' ), $table, $repair_text ), E_USER_WARNING );
                    }

                    else {
                        $job_object->log( sprintf( __( 'Result of table repair for %1$s is: %2$s', 'backuppilot' ), $table, $repair_text ) );
                    }
                }
                $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ][ ] = $table;
                $job_object->substeps_done ++;
            }
            $job_object->log( __( 'Database check done!', 'backuppilot' ) );
        }
        else {
            $job_object->log( __( 'No tables to check.', 'backuppilot' ) );
        }

        unset( $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] );
        return TRUE;
    }

    /**
     * Evaluate all result rows of a CHECK TABLE / REPAIR TABLE statement.
     *
     * MySQL/MariaDB can return multiple rows for these statements (e.g. InnoDB commonly adds an
     * informational "status" row, such as a note about auto_increment, before the final "OK" row).
     * Looking only at the first row therefore misclassifies harmless informational messages as errors.
     * Only rows with Msg_type "error"/"Corrupt" (or any other unrecognized type) count as a real
     * problem; "status", "note" and "info" rows are informational and not failures.
     *
     * @param array $rows Result rows from $wpdb->get_results(), each with ->Msg_type and ->Msg_text.
     *
     * @return array [ $level, $text ] where $level is false (no problem), E_USER_WARNING or E_USER_ERROR.
     */
    private static function evaluate_check_result( $rows ) {

        $level = FALSE;
        $messages = [];

        foreach ( (array) $rows as $row ) {
            $messages[] = $row->Msg_text;

            $msg_type = strtolower( $row->Msg_type );
            $msg_text = strtolower( $row->Msg_text );

            if ( $msg_text === 'ok' || in_array( $msg_type, [ 'status', 'note', 'info' ], true ) ) {
                continue;
            }

            if ( $msg_type === 'warning' ) {
                if ( $level !== E_USER_ERROR ) {
                    $level = E_USER_WARNING;
                }
            }

            else {
                $level = E_USER_ERROR;
            }
        }

        return [ $level, implode( '; ', $messages ) ];
    }
}
