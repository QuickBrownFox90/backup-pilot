<?php

class BackupPilot_Page_Editjob {

    public static function auth() {

        if ( isset($_GET[ 'tab' ] ) ) {
            $_GET[ 'tab' ] = sanitize_title_with_dashes( $_GET[ 'tab' ] );
            if ( substr( $_GET[ 'tab' ], 0, 5 ) != 'dest-' && substr( $_GET[ 'tab' ], 0, 8 ) != 'jobtype-'  && ! in_array( $_GET[ 'tab' ], [ 'job','cron' ], true ) )
                $_GET[ 'tab' ] = 'job';
        }

        else {
            $_GET[ 'tab' ] = 'job';
        }

        if ( substr( $_GET[ 'tab' ], 0, 5 ) == 'dest-' ) {
            $jobid        = (int)$_GET[ 'jobid' ];
            $id = strtoupper( str_replace( 'dest-', '', $_GET[ 'tab' ] ) );
            $dest_class = BackupPilot::get_destination( $id );
            $dest_class->edit_auth( $jobid );
        }
    }


    /**
     * Save Form data
     *
     * @since  1.0.0
     * @since  1.0.5  Added notice field
     */

    public static function save_post_form($tab, $jobid) {

        if ( ! current_user_can( 'backuppilot_jobs_edit' ) ) {
            return __( 'Sorry, you don\'t have permissions to do that.', 'backuppilot' );
        }

        $job_types = BackupPilot::get_job_types();

        switch ( $tab ) {
            case 'job':
                BackupPilot_Option::update( $jobid, 'jobid', $jobid );

                //set type of backup
                $backuptype = 'archive';
                BackupPilot_Option::update( $jobid, 'backuptype', $backuptype );

                $type_post = isset( $_POST['type'] ) ? (array) $_POST['type'] : [];
                //check existing type
                foreach ( $type_post as $key => $value ) {
                    if ( ! isset( $job_types[ $value ] ) ) {
                        unset( $type_post[ $key ] );
                    }
                }
                sort( $type_post );
                BackupPilot_Option::update( $jobid, 'type', $type_post );

                //test if job type makes backup
                $makes_file = false;
                /* @var BackupPilot_JobTypes $job_type */
                foreach ( $job_types as $type_id => $job_type ) {
                    if ( in_array( $type_id, $type_post, true ) ) {
                        if ( $job_type->creates_file() ) {
                            $makes_file = true;
                            break;
                        }
                    }
                }

                if ( $makes_file ) {
                    $destinations_post = isset( $_POST['destinations'] ) ? (array) $_POST['destinations'] : [];
                }

                else {
                    $destinations_post = [];
                }

                $destinations = BackupPilot::get_registered_destinations();
                foreach ( $destinations_post as $key => $dest_id ) {
                    //remove all destinations that not exists
                    if ( ! isset( $destinations[ $dest_id ] ) ) {
                        unset( $destinations_post[ $key ] );
                        continue;
                    }
                    //if sync remove all not sync destinations
                    if ( $backuptype === 'sync' ) {
                        if ( ! $destinations[ $dest_id ]['can_sync'] ) {
                            unset( $destinations_post[ $key ] );
                        }
                    }
                }
                sort( $destinations_post );
                BackupPilot_Option::update( $jobid, 'destinations', $destinations_post );

                $name = sanitize_text_field( trim( $_POST['name'] ) );
                if ( ! $name || $name === __( 'New Job', 'backuppilot' ) ) {
                    $name = sprintf( __( 'Job with ID %d', 'backuppilot' ), $jobid );
                }
                BackupPilot_Option::update( $jobid, 'name', $name );
                BackupPilot_Option::update( $jobid, 'notes', sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ) );

                $emails = explode( ',', sanitize_text_field( $_POST['mailaddresslog'] ) );
                foreach ( $emails as $key => $email ) {
                    $emails[ $key ] = sanitize_email( trim( $email ) );
                    if ( ! is_email( $emails[ $key ] ) ) {
                        unset( $emails[ $key ] );
                    }
                }
                $mailaddresslog = implode( ', ', $emails );
                BackupPilot_Option::update( $jobid, 'mailaddresslog', $mailaddresslog );

                $mailaddresssenderlog = trim( $_POST['mailaddresssenderlog'] );
                BackupPilot_Option::update( $jobid, 'mailaddresssenderlog', $mailaddresssenderlog );

                BackupPilot_Option::update( $jobid, 'mailerroronly', ! empty( $_POST['mailerroronly'] ) );

                $archiveformat = isset( $_POST['archiveformat'] ) && in_array( $_POST['archiveformat'], [
                    '.zip',
                    '.tar',
                    '.tar.gz'
                ], true ) ? $_POST['archiveformat'] : '.zip';
                BackupPilot_Option::update( $jobid, 'archiveformat', $archiveformat );
                BackupPilot_Option::update( $jobid, 'archiveencryption', ! empty( $_POST['archiveencryption'] ) );

                BackupPilot_Option::update( $jobid, 'archivename', BackupPilot_Job::sanitize_file_name( BackupPilot_Option::normalize_archive_name( $_POST['archivename'], $jobid, false ) ) );
                break;
            case 'cron':
                $activetype = in_array( $_POST['activetype'], [
                    '',
                    'wpcron',
                    'easycron',
                    'link'
                ], true ) ? $_POST['activetype'] : '';
                BackupPilot_Option::update( $jobid, 'activetype', $activetype );

                $cronselect = $_POST['cronselect'] === 'advanced' ? 'advanced' : 'basic';
                BackupPilot_Option::update( $jobid, 'cronselect', $cronselect );

                //save advanced
                if ( $cronselect === 'advanced' ) {
                    if ( empty( $_POST['cronminutes'] ) || $_POST['cronminutes'][0] === '*' ) {
                        if ( ! empty( $_POST['cronminutes'][1] ) ) {
                            $_POST['cronminutes'] = [ '*/' . $_POST['cronminutes'][1] ];
                        }

                        else {
                            $_POST['cronminutes'] = [ '*' ];
                        }
                    }
                    if ( empty( $_POST['cronhours'] ) || $_POST['cronhours'][0] === '*' ) {
                        if ( ! empty( $_POST['cronhours'][1] ) ) {
                            $_POST['cronhours'] = [ '*/' . $_POST['cronhours'][1] ];
                        }

                        else {
                            $_POST['cronhours'] = [ '*' ];
                        }
                    }
                    if ( empty( $_POST['cronmday'] ) || $_POST['cronmday'][0] === '*' ) {
                        if ( ! empty( $_POST['cronmday'][1] ) ) {
                            $_POST['cronmday'] = [ '*/' . $_POST['cronmday'][1] ];
                        }

                        else {
                            $_POST['cronmday'] = [ '*' ];
                        }
                    }
                    if ( empty( $_POST['cronmon'] ) || $_POST['cronmon'][0] === '*' ) {
                        if ( ! empty( $_POST['cronmon'][1] ) ) {
                            $_POST['cronmon'] = [ '*/' . $_POST['cronmon'][1] ];
                        }

                        else {
                            $_POST['cronmon'] = [ '*' ];
                        }
                    }
                    if ( empty( $_POST['cronwday'] ) || $_POST['cronwday'][0] === '*' ) {
                        if ( ! empty( $_POST['cronwday'][1] ) ) {
                            $_POST['cronwday'] = [ '*/' . $_POST['cronwday'][1] ];
                        }

                        else {
                            $_POST['cronwday'] = [ '*' ];
                        }
                    }
                    $cron = implode( ",", $_POST['cronminutes'] ) . ' ' . implode( ",", $_POST['cronhours'] ) . ' ' . implode( ",", $_POST['cronmday'] ) . ' ' . implode( ",", $_POST['cronmon'] ) . ' ' . implode( ",", $_POST['cronwday'] );
                    BackupPilot_Option::update( $jobid, 'cron', $cron );
                }

                else {
                    //Save basic
                    if ( $_POST['cronbtype'] === 'mon' ) {
                        BackupPilot_Option::update( $jobid, 'cron', absint( $_POST['moncronminutes'] ) . ' ' . absint( $_POST['moncronhours'] ) . ' ' . absint( $_POST['moncronmday'] ) . ' * *' );
                    }
                    if ( $_POST['cronbtype'] === 'week' ) {
                        BackupPilot_Option::update( $jobid, 'cron', absint( $_POST['weekcronminutes'] ) . ' ' . absint( $_POST['weekcronhours'] ) . ' * * ' . absint( $_POST['weekcronwday'] ) );
                    }
                    if ( $_POST['cronbtype'] === 'day' ) {
                        BackupPilot_Option::update( $jobid, 'cron', absint( $_POST['daycronminutes'] ) . ' ' . absint( $_POST['daycronhours'] ) . ' * * *' );
                    }
                    if ( $_POST['cronbtype'] === 'hour' ) {
                        BackupPilot_Option::update( $jobid, 'cron', absint( $_POST['hourcronminutes'] ) . ' * * * *' );
                    }
                }
                //reschedule
                $activetype = BackupPilot_Option::get( $jobid, 'activetype' );
                wp_clear_scheduled_hook( 'backuppilot_cron', [ 'arg' => $jobid ] );
                if ( $activetype === 'wpcron' ) {
                    $cron_next = BackupPilot_Cron::cron_next( BackupPilot_Option::get( $jobid, 'cron' ) );
                    wp_schedule_single_event( $cron_next, 'backuppilot_cron', [ 'arg' => $jobid ] );
                }
                $easy_cron_job_id = BackupPilot_Option::get( $jobid, 'easycronjobid' );
                if ( $activetype === 'easycron' ) {
                    BackupPilot_EasyCron::update( $jobid );
                }

                elseif ( $easy_cron_job_id ) {
                    BackupPilot_EasyCron::delete( $jobid );
                }
                break;
            default:
                if ( strstr( $tab, 'dest-' ) ) {
                    $dest_class = BackupPilot::get_destination( str_replace( 'dest-', '', $tab ) );
                    $dest_class->edit_form_post_save( $jobid );
                }
                if ( strstr( $tab, 'jobtype-' ) ) {
                    $id = strtoupper( str_replace( 'jobtype-', '', $tab ) );
                    $job_types[ $id ]->edit_form_post_save( $jobid );
                }
        }

        //saved message
        $messages = BackupPilot_Admin::get_messages();
        if ( empty( $messages['error'] ) ) {
            $url = BackupPilot_Job::get_jobrun_url( 'runnowlink', $jobid );
            BackupPilot_Admin::message( sprintf( __( 'Changes for job <i>%s</i> saved.', 'backuppilot' ), BackupPilot_Option::get( $jobid, 'name' ) ) . ' <a href="' . network_admin_url( 'admin.php' ) . '?page=backuppilotjobs">' . __( 'Jobs overview', 'backuppilot' ) . '</a> | <a href="' . $url['url'] . '">' . __( 'Run now', 'backuppilot' ) . '</a>' );
        }
    }


    /**
     *
     * Output css
     *
     * @return void
     */

    public static function admin_print_styles() {

        ?>
        <style type="text/css" media="screen">
            #cron-min, #cron-hour, #cron-day, #cron-month, #cron-weekday {
                overflow: auto;
                white-space: nowrap;
                height: 7em;
            }
            #cron-min-box, #cron-hour-box, #cron-day-box, #cron-month-box, #cron-weekday-box {
                border: 1px solid gray;
                margin: 10px 0 10px 10px;
                padding: 2px 2px;
                width: 100px;
                float: left;
            }
            #wpcronbasic {
                border-collapse: collapse;
            }
            #wpcronbasic th, #wpcronbasic td {
                width:80px;
                border-bottom: 1px solid gray;
            }
        </style>
        <?php
        //add css for all other tabs
        if ( substr( $_GET[ 'tab' ], 0, 5 ) == 'dest-' ) {
            $dest_object = BackupPilot::get_destination( str_replace( 'dest-', '', $_GET[ 'tab' ] ) );
            $dest_object->admin_print_styles();
        }
        elseif ( substr( $_GET[ 'tab' ], 0, 8 ) == 'jobtype-' ) {
            $job_type = BackupPilot::get_job_types();
            $id       = strtoupper( str_replace( 'jobtype-', '', $_GET[ 'tab' ] ) );
            $job_type[ $id ]->admin_print_styles( );
        }
    }

    /**
     *
     * Output js
     *
     * @return void
     */
    public static function admin_print_scripts() {

        wp_enqueue_script( 'backuppilotgeneral' );

        //add js for the first tabs
        if ( $_GET[ 'tab' ] == 'job' ) {
            if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
                wp_enqueue_script( 'backuppilottabjob', BackupPilot::get_plugin_data( 'URL' ) . '/assets/js/page_edit_tab_job.js', ['jquery'], time(), TRUE );
            }

            else {
                wp_enqueue_script( 'backuppilottabjob', BackupPilot::get_plugin_data( 'URL' ) . '/assets/js/page_edit_tab_job.min.js', ['jquery'], BackupPilot::get_plugin_data( 'Version' ), TRUE );
            }
        }
        elseif ( $_GET[ 'tab' ] == 'cron' ) {
            if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
                wp_enqueue_script( 'backuppilottabcron', BackupPilot::get_plugin_data( 'URL' ) . '/assets/js/page_edit_tab_cron.js', ['jquery'], time(), TRUE );
            }

            else {
                wp_enqueue_script( 'backuppilottabcron', BackupPilot::get_plugin_data( 'URL' ) . '/assets/js/page_edit_tab_cron.min.js', ['jquery'], BackupPilot::get_plugin_data( 'Version' ), TRUE );
            }
        }
        //add js for all other tabs
        elseif ( strstr( $_GET[ 'tab' ], 'dest-' ) ) {
            $dest_object = BackupPilot::get_destination( str_replace( 'dest-', '', $_GET[ 'tab' ] ) );
            $dest_object->admin_print_scripts();
        }
        elseif (  strstr( $_GET[ 'tab' ], 'jobtype-' ) ) {
            $job_type = BackupPilot::get_job_types();
            $id       = strtoupper( str_replace( 'jobtype-', '', $_GET[ 'tab' ] ) );
            $job_type[ $id ]->admin_print_scripts( );
        }
    }


    /**
     * @since  1.0.0
     * @since  1.0.5  Added notice field
     */

    public static function page() {

        if ( ! empty( $_GET[ 'jobid' ] ) ) {
            $jobid = (int)$_GET[ 'jobid' ];
        }

        else {
            //generate jobid if not exists
            $jobid = BackupPilot_Option::next_job_id();
        }

        $destinations = BackupPilot::get_registered_destinations();
        $job_types    = BackupPilot::get_job_types();

        $archive_format_option = BackupPilot_Option::get( $jobid, 'archiveformat' );
        ?>
    <div class="wrap" id="backuppilot-page">
        <?php
        echo '<h1>' . sprintf( esc_html__( '%1$s &rsaquo; Job: %2$s', 'backuppilot' ), BackupPilot::get_plugin_data( 'name' ), '<span id="h2jobtitle">' . esc_html( BackupPilot_Option::get( $jobid, 'name' ) ) . '</span>' ). '</h1>';

        //default tabs
        $tabs = [ 'job' => [ 'name' => esc_html__( 'General', 'backuppilot' ), 'display' => TRUE ], 'cron' => [ 'name' => __( 'Schedule', 'backuppilot' ), 'display' => TRUE ] ];
        //add jobtypes to tabs
        $job_job_types = BackupPilot_Option::get( $jobid, 'type' );
        foreach ( $job_types as $typeid => $typeclass ) {
            $tabid          = 'jobtype-' . strtolower( $typeid );
            $tabs[ $tabid ][ 'name' ] = $typeclass->info[ 'name' ];
            $tabs[ $tabid ][ 'display' ] = TRUE;
            if ( ! in_array( $typeid, $job_job_types, true ) )
                $tabs[ $tabid ][ 'display' ] = FALSE;

        }
        //add destinations to tabs
        $jobdests = BackupPilot_Option::get( $jobid, 'destinations' );
        foreach ( $destinations as $destid => $dest ) {
            $tabid          = 'dest-' . strtolower( $destid );
            $tabs[ $tabid ][ 'name' ] = sprintf( __( 'To: %s', 'backuppilot' ), $dest[ 'info' ][ 'name' ] );
            $tabs[ $tabid ][ 'display' ] = TRUE;
            if ( ! in_array( $destid, $jobdests, true ) )
                $tabs[ $tabid ][ 'display' ] = FALSE;
        }
        //display tabs
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $id => $tab ) {
            $addclass = '';
            if ( $id === $_GET[ 'tab' ] ) {
                $addclass = ' nav-tab-active';
            }
            $display = '';
            if ( ! $tab[ 'display' ] ) {
                $display = ' style="display:none;"';
            }
            echo '<a href="' . wp_nonce_url( network_admin_url( 'admin.php?page=backuppiloteditjob&tab=' . $id . '&jobid=' . $jobid ), 'edit-job' )   . '" class="nav-tab' . $addclass . '" id="tab-' . esc_attr( $id ) . '" data-nexttab="' . esc_attr( $id ) . '"' . $display . '>' . esc_html( $tab[ 'name' ] ) . '</a>';
        }
        echo '</h2>';
        //display messages
        BackupPilot_Admin::display_messages();
        echo '<form name="editjob" id="editjob" method="post" action="' . esc_attr(admin_url( 'admin-post.php' )) . '">';
        echo '<input type="hidden" id="jobid" name="jobid" value="' .  esc_attr( $jobid ) . '" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr( $_GET[ 'tab' ] ) . '" />';
        echo '<input type="hidden" name="nexttab" value="' . esc_attr( $_GET[ 'tab' ] ) . '" />';
        echo '<input type="hidden" name="page" value="backuppiloteditjob" />';
        echo '<input type="hidden" name="action" value="backuppilot" />';
        echo '<input type="hidden" name="anchor" value="" />';
        wp_nonce_field( 'backuppiloteditjob_page' );
        wp_nonce_field( 'backuppilot_ajax_nonce', 'backuppilotajaxnonce', FALSE );

        switch ( $_GET[ 'tab' ] ) {
            case 'job':
                ?>
                <div class="table" id="info-tab-job">
                    <h3><?php esc_html_e( 'Job Name', 'backuppilot' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="name"><?php esc_html_e( 'Please name this job.', 'backuppilot' ); ?></label></th>
                            <td>
                                <input name="name" type="text" id="name" placeholder="<?php esc_attr_e( 'Job Name', 'backuppilot' ); ?>" data-empty="<?php esc_attr_e( 'New Job', 'backuppilot' ); ?>" value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'name' ) ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="notes"><?php esc_html_e( 'Notes', 'backuppilot' ); ?></label></th>
                            <td>
                                <textarea name="notes" id="notes" rows="3" class="large-text"><?php echo esc_textarea( BackupPilot_Option::get( $jobid, 'notes' ) ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Optional notes visible in the jobs overview. Use this to document what this job backs up, where it sends files, or any other context.', 'backuppilot' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php esc_html_e( 'Job Tasks', 'backuppilot' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'This job is a&#160;&hellip;', 'backuppilot' ); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php esc_html_e( 'Job tasks', 'backuppilot' ); ?></span>
                                    </legend><?php
                                    foreach ( $job_types as $id => $typeclass ) {
                                        $addclass = '';
                                        if ( $typeclass->creates_file() ) {
                                            $addclass .= ' filetype';
                                        }
                                        echo '<p><label for="jobtype-select-' . strtolower( $id ) . '"><input class="jobtype-select checkbox' . $addclass . '" id="jobtype-select-' . strtolower( $id ) . '" type="checkbox" ' . checked( TRUE, in_array( $id, BackupPilot_Option::get( $jobid, 'type' ), true ), FALSE ) . ' name="type[]" value="' .esc_attr( $id ) . '" /> ' . esc_attr( $typeclass->info[ 'description' ] ) . '</label>';
                                        if ( ! empty( $typeclass->info[ 'help' ] ) ) {
                                            echo '<br><span class="description">' . esc_attr( $typeclass->info[ 'help' ] ) . '</span>';
                                        }
                                        echo '</p>';
                                    }
                                    ?></fieldset>
                            </td>
                        </tr>
                    </table>

                    <h3 class="title hasdests"><?php esc_html_e( 'Backup File Creation', 'backuppilot' ); ?></h3>
                    <p class="hasdests"></p>
                    <table class="form-table hasdests">
                        <tr class="nosync">
                            <th scope="row"><label for="archivename"><?php esc_html_e( 'Archive name', 'backuppilot' ); ?></label></th>
                            <td>
                                <input name="archivename" type="text" id="archivename" placeholder="%Y-%m-%d_%H-%i-%s_%hash%" value="<?php echo esc_attr( BackupPilot_Option::get( $jobid, 'archivenamenohash' ) );?>" class="regular-text code" />
                                <p><?php _e( '<em>Note</em>: In order for backup file tracking to work, %hash% must be included anywhere in the archive name.', 'backuppilot' ); ?></p>
                                <?php
                                $archivename = BackupPilot_Option::substitute_date_vars(
                                    BackupPilot_Option::get( $jobid, 'archivenamenohash' ) );
                                echo '<p>' . esc_html__( 'Preview: ', 'backuppilot' ) . '<code><span id="archivefilename">' . esc_attr( $archivename ) . '</span><span id="archiveformat">' . esc_attr( $archive_format_option ) . '</span></code></p>';
                                echo '<p class="description">';
                                echo "<strong>" . esc_attr__( 'Replacement patterns:', 'backuppilot' ) . "</strong><br />";
                                echo esc_attr__( '%d = Two digit day of the month, with leading zeros', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%j = Day of the month, without leading zeros', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%m = Two-digit representation of the month, with leading zeros', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%n = Representation of the month (without leading zeros)', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%Y = Four digit representation of the year', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%y = Two digit representation of the year', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%a = Lowercase ante meridiem (am) and post meridiem (pm)', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%A = Uppercase ante meridiem (AM) and post meridiem (PM)', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%B = Swatch Internet Time', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%g = Hour in 12-hour format, without leading zeros', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%G = Hour in 24-hour format, without leading zeros', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%h = Two-digit hour in 12-hour format, with leading zeros', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%H = Two-digit hour in 24-hour format, with leading zeros', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%i = Two digit representation of the minute', 'backuppilot' ) . '<br />';
                                echo esc_attr__( '%s = Two digit representation of the second', 'backuppilot' ) . '<br />';
                                echo '</p>';
                                ?>
                            </td>
                        </tr>
                        <tr class="nosync">
                            <th scope="row"><?php esc_html_e( 'Archive Format', 'backuppilot' ); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php esc_html_e( 'Archive Format', 'backuppilot' ); ?></span></legend>
                                    <?php
                                    if ( class_exists( 'ZipArchive' ) ) {
                                        echo '<p><label for="idarchiveformat-zip"><input class="radio" type="radio"' . checked( '.zip', $archive_format_option, FALSE ) . ' name="archiveformat" id="idarchiveformat-zip" value=".zip" /> ' . esc_html__( 'Zip', 'backuppilot' ) . '</label></p>';
                                    }

                                    else {
                                        echo '<p><label for="idarchiveformat-zip"><input class="radio" type="radio"' . checked( '.zip', $archive_format_option, FALSE ) . ' name="archiveformat" id="idarchiveformat-zip" value=".zip" /> ' . esc_html__( 'Zip', 'backuppilot' ) . '</label>';
                                        echo '<br /><span class="description">' . esc_html(__( 'ZipArchive PHP class is missing, so BackupPilot will use PclZip instead.', 'backuppilot' )) . '</span></p>';
                                    }
                                    echo '<p><label for="idarchiveformat-tar"><input class="radio" type="radio"' . checked( '.tar', $archive_format_option, FALSE ) . ' name="archiveformat" id="idarchiveformat-tar" value=".tar" /> ' . esc_html__( 'Tar', 'backuppilot' )  . '</label></p>';
                                    if ( function_exists( 'gzopen' ) ) {
                                        echo '<p><label for="idarchiveformat-targz"><input class="radio" type="radio"' . checked( '.tar.gz', $archive_format_option, FALSE ) . ' name="archiveformat" id="idarchiveformat-targz" value=".tar.gz" /> ' . esc_html__( 'Tar GZip', 'backuppilot' ) .  '</label></p>';
                                    }

                                    else {
                                        echo '<p><label for="idarchiveformat-targz"><input class="radio" type="radio"' . checked( '.tar.gz', $archive_format_option, FALSE ) . ' name="archiveformat" id="idarchiveformat-targz" value=".tar.gz" disabled="disabled" /> ' . esc_html__( 'Tar GZip', 'backuppilot' ) . '</label>';
                                        echo '<br /><span class="description">' . esc_html(sprintf( __( 'Disabled due to missing %s PHP function.', 'backuppilot' ), 'gzopen()' )) . '</span></p>';
                                    }
                                    ?>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <h3 class="title hasdests"><?php esc_html_e( 'Job Destination', 'backuppilot' ); ?></h3>
                    <p class="hasdests"></p>
                    <table class="form-table hasdests">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Where should your backup file be stored?', 'backuppilot' ); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php esc_html_e( 'Where should your backup file be stored?', 'backuppilot' ); ?></span>
                                    </legend><?php
                                    foreach ( $destinations as $id => $dest ) {
                                        $syncclass = '';
                                        if ( ! $dest[ 'can_sync' ] ) {
                                            $syncclass = 'nosync';
                                        }
                                        echo '<p class="' . esc_attr($syncclass) . '"><label for="dest-select-' . strtolower( $id ) . '"><input class="checkbox" id="dest-select-' . strtolower(  esc_attr( $id ) ) . '" type="checkbox" ' . checked( TRUE, in_array( $id, BackupPilot_Option::get( $jobid, 'destinations' ), true ), FALSE ) . ' name="destinations[]" value="' . esc_attr($id) . '" ' . disabled( ! empty( $dest[ 'error' ] ), TRUE, FALSE ) . ' /> ' . esc_attr( $dest[ 'info' ][ 'description' ] );
                                        if ( ! empty( $dest[ 'error' ] ) ) {
                                            echo '<br><span class="description">' . esc_attr( $dest[ 'error' ] ) . '</span>';
                                        }
                                        echo '</label></p>';
                                    }
                                    ?></fieldset>
                            </td>
                        </tr>
                    </table>

                    <h3 class="title"><?php esc_html_e( 'Log Files', 'backuppilot' ); ?></h3>
                    <p></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="mailaddresslog"><?php esc_html_e( 'Send log to email address', 'backuppilot' ); ?></label></th>
                            <td>
                                <input name="mailaddresslog" type="text" id="mailaddresslog" value="<?php echo esc_html( BackupPilot_Option::get( $jobid, 'mailaddresslog' ) );?>" class="regular-text" />
                                <p class="description"><?php esc_attr_e( 'Leave empty to not have log sent. Or separate with , for more than one receiver.', 'backuppilot' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="mailaddresssenderlog"><?php esc_html_e( 'Email FROM field', 'backuppilot' ); ?></label></th>
                            <td>
                                <input name="mailaddresssenderlog" type="text" id="mailaddresssenderlog" value="<?php echo esc_html( BackupPilot_Option::get( $jobid, 'mailaddresssenderlog' ) );?>" class="regular-text" placeholder="<?php esc_attr_e( 'Your Name &lt;mail@domain.tld&gt;', 'backuppilot' ); ?>"/>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Errors only', 'backuppilot' ); ?></th>
                            <td>
                                <label for="idmailerroronly">
                                <input class="checkbox" value="1" id="idmailerroronly"
                                       type="checkbox" <?php checked( BackupPilot_Option::get( $jobid, 'mailerroronly' ), TRUE ); ?>
                                       name="mailerroronly" /> <?php esc_html_e( 'Send email with log only when errors occur during job execution.', 'backuppilot' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php
                break;
            case 'cron':
                ?>
                <div class="table" id="info-tab-cron">
                    <h3 class="title"><?php esc_html_e( 'Job Schedule', 'backuppilot' ); ?></h3>
                    <p></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Start job', 'backuppilot' ); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php esc_html_e( 'Start job', 'backuppilot' ); ?></span></legend>
                                    <label for="idactivetype"><input class="radio"
                                           type="radio"<?php checked( '', BackupPilot_Option::get( $jobid, 'activetype' ), TRUE ); ?>
                                           name="activetype" id="idactivetype"
                                           value="" /> <?php esc_html_e( 'manually only', 'backuppilot' ); ?></label><br/>
                                    <label for="idactivetype-wpcron"><input class="radio"
                                           type="radio"<?php checked( 'wpcron', BackupPilot_Option::get( $jobid, 'activetype' ), TRUE ); ?>
                                           name="activetype" id="idactivetype-wpcron"
                                           value="wpcron" /> <?php esc_html_e( 'with WordPress cron', 'backuppilot' ); ?></label><br/>
                                    <?php
                                    $disabled = '';
                                    $easycron_api = get_site_option( 'backuppilot_cfg_easycronapikey' );
                                    if ( empty( $easycron_api ) ) {
                                        $disabled = ' disabled="disabled"';
                                    }
                                    ?>
                                    <label for="idactivetype-easycron"><input class="radio" type="radio"<?php checked( 'easycron', BackupPilot_Option::get( $jobid, 'activetype' ), TRUE ); ?> name="activetype" id="idactivetype-easycron"<?php echo $disabled; ?> value="easycron" />
                                    <?php _e( 'with <a href="https://www.easycron.com?ref=36673" title="Affiliate Link!">EasyCron.com</a>', 'backuppilot' );
                                    if ( empty( $easycron_api ) ) {
                                        echo '&nbsp;-&nbsp;<span class="description">' . sprintf( __( 'First setup <a href="%s">API Key</a>.', 'backuppilot' ), network_admin_url( 'admin.php' ) . '?page=backuppilotsettings#backuppilot-tab-apikey' ) . '</span>';
                                    }
                                    ?>
                                    </label><br/>
                                    <?php
                                    $url = BackupPilot_Job::get_jobrun_url( 'runext', BackupPilot_Option::get( $jobid, 'jobid' ) );
                                    ?>
                                    <label for="idactivetype-link">
                                        <input class="radio" type="radio"<?php checked( 'link', BackupPilot_Option::get( $jobid, 'activetype' ), TRUE ); ?> name="activetype" id="idactivetype-link" value="link" />
                                        &nbsp;<?php esc_html_e( 'with a link', 'backuppilot' ); ?> <code><a href="<?php echo $url[ 'url' ];?>" target="_blank"><?php echo esc_html($url[ 'url' ]);?></a></code><br>
                                        <span class="description"><?php esc_attr_e( 'Copy the link for an external start. This option has to be activated to make the link work.', 'backuppilot' ); ?></span>
                                    </label>

                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Start job with CLI', 'backuppilot' ); ?></th>
                            <td>
                                <?php
                                _e( 'Use <a href="http://wp-cli.org/">WP-CLI</a> to run jobs from commandline.', 'backuppilot' );
                                ?>
                            </td>
                        </tr>
                    </table>
                    <h3 class="title wpcron"><?php esc_html_e( 'Schedule execution time', 'backuppilot' ); ?></h3>
                    <?php BackupPilot_Page_Editjob::ajax_cron_text( [ 'cronstamp' => BackupPilot_Option::get( $jobid, 'cron' ), 'crontype' => BackupPilot_Option::get( $jobid, 'cronselect' ) ] ); ?>
                    <table class="form-table wpcron">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Scheduler type', 'backuppilot' ); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php esc_html_e( 'Scheduler type', 'backuppilot' ); ?></span></legend>
                                    <label for="idcronselect-basic"><input class="radio"
                                           type="radio"<?php checked( 'basic', BackupPilot_Option::get( $jobid, 'cronselect' ), TRUE ); ?>
                                           name="cronselect" id="idcronselect-basic"
                                           value="basic" /> <?php esc_html_e( 'basic', 'backuppilot' ); ?></label><br/>
                                    <label for="idcronselect-advanced"><input class="radio"
                                           type="radio"<?php checked( 'advanced', BackupPilot_Option::get( $jobid, 'cronselect' ), TRUE ); ?>
                                           name="cronselect" id="idcronselect-advanced"
                                           value="advanced" /> <?php esc_html_e( 'advanced', 'backuppilot' ); ?></label><br/>
                                </fieldset>
                            </td>
                        </tr>
                        <?php

                        list( $cronstr[ 'minutes' ], $cronstr[ 'hours' ], $cronstr[ 'mday' ], $cronstr[ 'mon' ], $cronstr[ 'wday' ] ) = explode( ' ', BackupPilot_Option::get( $jobid, 'cron' ), 5 );
                        if ( strstr( $cronstr[ 'minutes' ], '*/' ) )
                            $minutes = explode( '/', $cronstr[ 'minutes' ] );
                        else
                            $minutes = explode( ',', $cronstr[ 'minutes' ] );
                        if ( strstr( $cronstr[ 'hours' ], '*/' ) )
                            $hours = explode( '/', $cronstr[ 'hours' ] );
                        else
                            $hours = explode( ',', $cronstr[ 'hours' ] );
                        if ( strstr( $cronstr[ 'mday' ], '*/' ) )
                            $mday = explode( '/', $cronstr[ 'mday' ] );
                        else
                            $mday = explode( ',', $cronstr[ 'mday' ] );
                        if ( strstr( $cronstr[ 'mon' ], '*/' ) )
                            $mon = explode( '/', $cronstr[ 'mon' ] );
                        else
                            $mon = explode( ',', $cronstr[ 'mon' ] );
                        if ( strstr( $cronstr[ 'wday' ], '*/' ) )
                            $wday = explode( '/', $cronstr[ 'wday' ] );
                        else
                            $wday = explode( ',', $cronstr[ 'wday' ] );
                        ?>
                        <tr class="wpcronbasic"<?php if ( BackupPilot_Option::get( $jobid, 'cronselect' ) !== 'basic' ) echo ' style="display:none;"';?>>
                            <th scope="row"><?php _e( 'Scheduler', 'backuppilot' ); ?></th>
                            <td>
                                <table id="wpcronbasic">
                                    <tr>
                                        <th>
                                            <?php _e( 'Type', 'backuppilot' ); ?>
                                        </th>
                                        <th>
                                        </th>
                                        <th>
                                            <?php _e( 'Hour', 'backuppilot' ); ?>
                                        </th>
                                        <th>
                                            <?php _e( 'Minute', 'backuppilot' ); ?>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td><label for="idcronbtype-mon"><?php echo '<input class="radio" type="radio"' . checked( TRUE, is_numeric( $mday[ 0 ] ), FALSE ) . ' name="cronbtype" id="idcronbtype-mon" value="mon" /> ' . esc_html__( 'monthly', 'backuppilot' ); ?></label></td>
                                        <td><select name="moncronmday"><?php for ( $i = 1; $i <= 31; $i ++ ) {
                                            echo '<option ' . selected( in_array( (string) $i, $mday, TRUE ), TRUE, FALSE ) . '  value="' . esc_attr($i) . '" />' . esc_html__( 'on', 'backuppilot' ) . ' ' . esc_html($i) . '</option>';
                                        } ?></select></td>
                                        <td><select name="moncronhours"><?php for ( $i = 0; $i < 24; $i ++ ) {
                                            echo '<option ' . selected( in_array( (string) $i, $hours, TRUE ), TRUE, FALSE ) . '  value="' . esc_attr($i) . '" />' . esc_html($i) . '</option>';
                                        } ?></select></td>
                                        <td><select name="moncronminutes"><?php for ( $i = 0; $i < 60; $i = $i + 5 ) {
                                            echo '<option ' . selected( in_array( (string) $i, $minutes, TRUE ), TRUE, FALSE ) . '  value="' . esc_attr($i) . '" />' . esc_html($i) . '</option>';
                                        } ?></select></td>
                                    </tr>
                                    <tr>
                                        <td><label for="idcronbtype-week"><?php echo '<input class="radio" type="radio"' . checked( TRUE, is_numeric( $wday[ 0 ] ), FALSE ) . ' name="cronbtype" id="idcronbtype-week" value="week" /> ' . esc_html__( 'weekly', 'backuppilot' ); ?></label></td>
                                        <td><select name="weekcronwday">
                                            <?php     echo '<option ' . selected( in_array( "0", $wday, TRUE ), TRUE, FALSE ) . '  value="0" />' . esc_html__( 'Sunday', 'backuppilot' ) . '</option>';
                                            echo '<option ' . selected( in_array( "1", $wday, TRUE ), TRUE, FALSE ) . '  value="1" />' . esc_html__( 'Monday', 'backuppilot' ) . '</option>';
                                            echo '<option ' . selected( in_array( "2", $wday, TRUE ), TRUE, FALSE ) . '  value="2" />' . esc_html__( 'Tuesday', 'backuppilot' ) . '</option>';
                                            echo '<option ' . selected( in_array( "3", $wday, TRUE ), TRUE, FALSE ) . '  value="3" />' . esc_html__( 'Wednesday', 'backuppilot' ) . '</option>';
                                            echo '<option ' . selected( in_array( "4", $wday, TRUE ), TRUE, FALSE ) . '  value="4" />' . esc_html__( 'Thursday', 'backuppilot' ) . '</option>';
                                            echo '<option ' . selected( in_array( "5", $wday, TRUE ), TRUE, FALSE ) . '  value="5" />' . esc_html__( 'Friday', 'backuppilot' ) . '</option>';
                                            echo '<option ' . selected( in_array( "6", $wday, TRUE ), TRUE, FALSE ) . '  value="6" />' . esc_html__( 'Saturday', 'backuppilot' ) . '</option>'; ?>
                                        </select></td>
                                        <td><select name="weekcronhours"><?php for ( $i = 0; $i < 24; $i ++ ) {
                                            echo '<option ' . selected( in_array( (string) $i, $hours, TRUE ), TRUE, FALSE ) . '  value="' . esc_attr( $i ) . '" />' . esc_html( $i ) . '</option>';
                                        } ?></select></td>
                                        <td><select name="weekcronminutes"><?php for ( $i = 0; $i < 60; $i = $i + 5 ) {
                                            echo '<option ' . selected( in_array( (string) $i, $minutes, TRUE ), TRUE, FALSE ) . '  value="' . esc_attr( $i ) . '" />' . esc_html( $i ) . '</option>';
                                        } ?></select></td>
                                    </tr>
                                    <tr>
                                        <td><label for="idcronbtype-day"><?php echo '<input class="radio" type="radio"' . checked( "**", $mday[ 0 ] . $wday[ 0 ], FALSE ) . ' name="cronbtype" id="idcronbtype-day" value="day" /> ' . esc_html__( 'daily', 'backuppilot' ); ?></label></td>
                                        <td></td>
                                        <td><select name="daycronhours"><?php for ( $i = 0; $i < 24; $i ++ ) {
                                            echo '<option ' . selected( in_array( (string) $i, $hours, TRUE ), TRUE, FALSE ) . '  value="' . esc_attr( $i ) . '" />' . esc_html( $i ) . '</option>';
                                        } ?></select></td>
                                        <td><select name="daycronminutes"><?php for ( $i = 0; $i < 60; $i = $i + 5 ) {
                                            echo '<option ' . selected( in_array( (string) $i, $minutes, TRUE ), TRUE, FALSE ) . '  value="' . esc_attr( $i ) . '" />' . esc_html( $i ) . '</option>';
                                        } ?></select></td>
                                    </tr>
                                    <tr>
                                        <td><label for="idcronbtype-hour"><?php echo '<input class="radio" type="radio"' . checked( "*", $hours[ 0 ], FALSE ) . ' name="cronbtype" id="idcronbtype-hour" value="hour" /> ' . esc_html__( 'hourly', 'backuppilot' ); ?></label></td>
                                        <td></td>
                                        <td></td>
                                        <td><select name="hourcronminutes"><?php for ( $i = 0; $i < 60; $i = $i + 5 ) {
                                            echo '<option ' . selected( in_array( (string) $i, $minutes, TRUE ), TRUE, FALSE ) . '  value="' . esc_attr( $i ) . '" />' . esc_html( $i ) . '</option>';
                                        } ?></select></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="wpcronadvanced"<?php if ( BackupPilot_Option::get( $jobid, 'cronselect' ) != 'advanced' ) echo ' style="display:none;"';?>>
                            <th scope="row"><?php _e( 'Scheduler', 'backuppilot' ); ?></th>
                            <td>
                                <div id="cron-min-box">
                                    <b><?php _e( 'Minutes:', 'backuppilot' ); ?></b><br/>
                                    <?php
                                    echo '<label for="idcronminutes"><input class="checkbox" type="checkbox"' . checked( in_array( "*", $minutes, TRUE ), TRUE, FALSE ) . ' name="cronminutes[]" id="idcronminutes" value="*" /> ' . __( 'Any (*)', 'backuppilot' ) . '</label><br />';
                                    ?>
                                    <div id="cron-min"><?php
                                        for ( $i = 0; $i < 60; $i = $i + 5 ) {
                                            echo '<label for="idcronminutes-' . $i . '"><input class="checkbox" type="checkbox"' . checked( in_array( (string) $i, $minutes, TRUE ), TRUE, FALSE ) . ' name="cronminutes[]" id="idcronminutes-' . esc_attr( $i ) . '" value="' . esc_attr( $i ) . '" /> ' . esc_attr( $i ) . '</label><br />';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div id="cron-hour-box">
                                    <b><?php _e( 'Hours:', 'backuppilot' ); ?></b><br/>
                                    <?php

                                    echo '<label for="idcronhours"><input class="checkbox" type="checkbox"' . checked( in_array( "*", $hours, TRUE ), TRUE, FALSE ) . ' name="cronhours[]" id="idcronhours" value="*" /> ' . __( 'Any (*)', 'backuppilot' ) . '</label><br />';
                                    ?>
                                    <div id="cron-hour"><?php
                                        for ( $i = 0; $i < 24; $i ++ ) {
                                            echo '<label for="idcronhours-' . $i . '"><input class="checkbox" type="checkbox"' . checked( in_array( (string) $i, $hours, TRUE ), TRUE, FALSE ) . ' name="cronhours[]" id="idcronhours-' . esc_attr( $i ) . '" value="' . esc_attr( $i ) . '" /> ' . esc_html( $i ) . '</label><br />';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div id="cron-day-box">
                                    <b><?php _e( 'Day of Month:', 'backuppilot' ); ?></b><br/>
                                    <label for="idcronmday"><input class="checkbox" type="checkbox"<?php checked( in_array( "*", $mday, TRUE ), TRUE, TRUE ); ?>
                                           name="cronmday[]" id="idcronmday" value="*"/> <?php _e( 'Any (*)', 'backuppilot' ); ?></label>
                                    <br/>

                                    <div id="cron-day">
                                        <?php
                                        for ( $i = 1; $i <= 31; $i ++ ) {
                                            echo '<label for="idcronmday-' . $i . '"><input class="checkbox" type="checkbox"' . checked( in_array( (string) $i, $mday, TRUE ), TRUE, FALSE ) . ' name="cronmday[]" id="idcronmday-' . esc_attr( $i ) . '" value="' . esc_attr( $i ) . '" /> ' . esc_html( $i ) . '</label><br />';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div id="cron-month-box">
                                    <b><?php _e( 'Month:', 'backuppilot' ); ?></b><br/>
                                    <?php
                                    echo '<label for="idcronmon"><input class="checkbox" type="checkbox"' . checked( in_array( "*", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon" value="*" /> ' . esc_html__( 'Any (*)', 'backuppilot' ) . '</label><br />';
                                    ?>
                                    <div id="cron-month">
                                        <?php
                                        echo '<label for="idcronmon-1"><input class="checkbox" type="checkbox"' . checked( in_array( "1", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-1" value="1" /> ' . esc_html__( 'January', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-2"><input class="checkbox" type="checkbox"' . checked( in_array( "2", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-2" value="2" /> ' . esc_html__( 'February', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-3"><input class="checkbox" type="checkbox"' . checked( in_array( "3", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-3" value="3" /> ' . esc_html__( 'March', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-4"><input class="checkbox" type="checkbox"' . checked( in_array( "4", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-4" value="4" /> ' . esc_html__( 'April', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-5"><input class="checkbox" type="checkbox"' . checked( in_array( "5", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-5" value="5" /> ' . esc_html__( 'May', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-6"><input class="checkbox" type="checkbox"' . checked( in_array( "6", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-6" value="6" /> ' . esc_html__( 'June', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-7"><input class="checkbox" type="checkbox"' . checked( in_array( "7", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-7" value="7" /> ' . esc_html__( 'July', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-8"><input class="checkbox" type="checkbox"' . checked( in_array( "8", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-8" value="8" /> ' . esc_html__( 'August', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-9"><input class="checkbox" type="checkbox"' . checked( in_array( "9", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-9" value="9" /> ' . esc_html__( 'September', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-10"><input class="checkbox" type="checkbox"' . checked( in_array( "10", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-10" value="10" /> ' . esc_html__( 'October', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-11"><input class="checkbox" type="checkbox"' . checked( in_array( "11", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-11" value="11" /> ' . esc_html__( 'November', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronmon-12"><input class="checkbox" type="checkbox"' . checked( in_array( "12", $mon, TRUE ), TRUE, FALSE ) . ' name="cronmon[]" id="idcronmon-12" value="12" /> ' . esc_html__( 'December', 'backuppilot' ) . '</label><br />';
                                        ?>
                                    </div>
                                </div>
                                <div id="cron-weekday-box">
                                    <b><?php esc_html_e( 'Day of Week:', 'backuppilot' ); ?></b><br/>
                                    <?php
                                    echo '<label for="idcronwday"><input class="checkbox" type="checkbox"' . checked( in_array( "*", $wday, TRUE ), TRUE, FALSE ) . ' name="cronwday[]" id="idcronwday" value="*" /> ' . __( 'Any (*)', 'backuppilot' ) . '</label><br />';
                                    ?>
                                    <div id="cron-weekday">
                                        <?php
                                        echo '<label for="idcronwday-0"><input class="checkbox" type="checkbox"' . checked( in_array( "0", $wday, TRUE ), TRUE, FALSE ) . ' name="cronwday[]" id="idcronwday-0" value="0" /> ' . esc_html__( 'Sunday', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronwday-1"><input class="checkbox" type="checkbox"' . checked( in_array( "1", $wday, TRUE ), TRUE, FALSE ) . ' name="cronwday[]" id="idcronwday-1" value="1" /> ' . esc_html__( 'Monday', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronwday-2"><input class="checkbox" type="checkbox"' . checked( in_array( "2", $wday, TRUE ), TRUE, FALSE ) . ' name="cronwday[]" id="idcronwday-2" value="2" /> ' . esc_html__( 'Tuesday', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronwday-3"><input class="checkbox" type="checkbox"' . checked( in_array( "3", $wday, TRUE ), TRUE, FALSE ) . ' name="cronwday[]" id="idcronwday-3" value="3" /> ' . esc_html__( 'Wednesday', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronwday-4"><input class="checkbox" type="checkbox"' . checked( in_array( "4", $wday, TRUE ), TRUE, FALSE ) . ' name="cronwday[]" id="idcronwday-4" value="4" /> ' . esc_html__( 'Thursday', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronwday-5"><input class="checkbox" type="checkbox"' . checked( in_array( "5", $wday, TRUE ), TRUE, FALSE ) . ' name="cronwday[]" id="idcronwday-5" value="5" /> ' . esc_html__( 'Friday', 'backuppilot' ) . '</label><br />';
                                        echo '<label for="idcronwday-6"><input class="checkbox" type="checkbox"' . checked( in_array( "6", $wday, TRUE ), TRUE, FALSE ) . ' name="cronwday[]" id="idcronwday-6" value="6" /> ' . esc_html__( 'Saturday', 'backuppilot' ) . '</label><br />';
                                        ?>
                                    </div>
                                </div>
                                <br class="clear"/>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php
                break;
            default:
                echo '<div class="table" id="info-tab-' . $_GET[ 'tab' ] . '">';
                if ( strstr( $_GET[ 'tab' ], 'dest-' ) ) {
                    $dest_object = BackupPilot::get_destination( str_replace( 'dest-', '', $_GET[ 'tab' ] ) );
                    $dest_object->edit_tab( $jobid );
                }
                if ( strstr( $_GET[ 'tab' ], 'jobtype-' ) ) {
                    $id = strtoupper( str_replace( 'jobtype-', '', $_GET[ 'tab' ] ) );
                    $job_types[ $id ]->edit_tab( $jobid );
                }
                echo '</div>';
        }
        echo '<p class="submit">';
        submit_button( __( 'Save changes', 'backuppilot' ), 'primary', 'save', FALSE, [ 'tabindex' => '2', 'accesskey' => 'p' ] );
        echo '</p></form>';
        ?>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            // auto post if things changed
            var changed = false;
            $( '#editjob' ).change( function () {
                changed = true;
            });
            $( '.nav-tab' ).click( function () {
                if ( changed ) {
                    $( 'input[name="nexttab"]' ).val( $(this).data( "nexttab" ) );
                    $( '#editjob' ).submit();
                    return false;
                }
            });
        });
    </script>
        <?php
        //add inline js
        if ( strstr( $_GET[ 'tab' ], 'dest-' ) ) {
            $dest_object = BackupPilot::get_destination( str_replace( 'dest-', '', sanitize_text_field( $_GET[ 'tab' ] ) ) );
            $dest_object->edit_inline_js();
        }
        if ( strstr( $_GET[ 'tab' ], 'jobtype-' ) ) {
            $id = strtoupper( str_replace( 'jobtype-', '', sanitize_text_field( $_GET[ 'tab' ] ) ) );
            $job_types[ $id ]->edit_inline_js();
        }

    }

    /**
     * @static
     *
     * @param string $args
     *
     * @return mixed
     */
    public static function ajax_cron_text( $args = '' ) {

        if ( is_array( $args ) ) {
            extract( $args );
            $ajax = FALSE;
        }

        else {
            if ( ! current_user_can( 'backuppilot_jobs_edit' ) )
                wp_die( -1 );
            check_ajax_referer( 'backuppilot_ajax_nonce' );
            if ( empty( $_POST[ 'cronminutes' ] ) || $_POST[ 'cronminutes' ][ 0 ] == '*' ) {
                if ( ! empty( $_POST[ 'cronminutes' ][ 1 ] ) )
                    $_POST[ 'cronminutes' ] = [ '*/' . $_POST[ 'cronminutes' ][ 1 ] ];
                else
                    $_POST[ 'cronminutes' ] = [ '*' ];
            }
            if ( empty( $_POST[ 'cronhours' ] ) || $_POST[ 'cronhours' ][ 0 ] == '*' ) {
                if ( ! empty( $_POST[ 'cronhours' ][ 1 ] ) )
                    $_POST[ 'cronhours' ] = [ '*/' . $_POST[ 'cronhours' ][ 1 ] ];
                else
                    $_POST[ 'cronhours' ] = [ '*' ];
            }
            if ( empty( $_POST[ 'cronmday' ] ) || $_POST[ 'cronmday' ][ 0 ] == '*' ) {
                if ( ! empty( $_POST[ 'cronmday' ][ 1 ] ) )
                    $_POST[ 'cronmday' ] = [ '*/' . $_POST[ 'cronmday' ][ 1 ] ];
                else
                    $_POST[ 'cronmday' ] = [ '*' ];
            }
            if ( empty( $_POST[ 'cronmon' ] ) || $_POST[ 'cronmon' ][ 0 ] == '*' ) {
                if ( ! empty( $_POST[ 'cronmon' ][ 1 ] ) )
                    $_POST[ 'cronmon' ] = [ '*/' . $_POST[ 'cronmon' ][ 1 ] ];
                else
                    $_POST[ 'cronmon' ] = [ '*' ];
            }
            if ( empty( $_POST[ 'cronwday' ] ) || $_POST[ 'cronwday' ][ 0 ] == '*' ) {
                if ( ! empty( $_POST[ 'cronwday' ][ 1 ] ) )
                    $_POST[ 'cronwday' ] = [ '*/' . $_POST[ 'cronwday' ][ 1 ] ];
                else
                    $_POST[ 'cronwday' ] = [ '*' ];
            }
            $crontype  = $_POST[ 'crontype' ];
            $cronstamp = implode( ",", $_POST[ 'cronminutes' ] ) . ' ' . implode( ",", $_POST[ 'cronhours' ] ) . ' ' . implode( ",", $_POST[ 'cronmday' ] ) . ' ' . implode( ",", $_POST[ 'cronmon' ] ) . ' ' . implode( ",", $_POST[ 'cronwday' ] );
            $ajax      = TRUE;
        }
        echo '<p class="wpcron" id="schedulecron">';

        if ( $crontype == 'advanced' ) {
            echo str_replace( '\"','"', __( 'Working as <a href="http://wikipedia.org/wiki/Cron">Cron</a> schedule:', 'backuppilot' ) );
            echo ' <i><b>' . esc_attr( $cronstamp ). '</b></i><br />';
        }

        list( $cronstr[ 'minutes' ], $cronstr[ 'hours' ], $cronstr[ 'mday' ], $cronstr[ 'mon' ], $cronstr[ 'wday' ] ) = explode( ' ', $cronstamp, 5 );
        if ( FALSE !== strpos( $cronstr[ 'minutes' ], '*/' ) || $cronstr[ 'minutes' ] == '*' ) {
            $repeatmins = str_replace( '*/', '', $cronstr[ 'minutes' ] );
            if ( $repeatmins == '*' || empty( $repeatmins ) )
                $repeatmins = 5;
            echo '<span class="bwu-message-error">' . sprintf( __( 'ATTENTION: Job runs every %d minutes!', 'backuppilot' ), $repeatmins ) . '</span><br />';
        }
        $cron_next = BackupPilot_Cron::cron_next( $cronstamp ) + ( get_option( 'gmt_offset' ) * 3600 );
        if ( PHP_INT_MAX === $cron_next ) {
            echo '<span class="bwu-message-error">' . __( 'ATTENTION: Can\'t calculate cron!', 'backuppilot' ) . '</span><br />';
        }
        else {
            _e( 'Next runtime:', 'backuppilot' );
            echo ' <b>' . date_i18n( 'D, j M Y, H:i', $cron_next, TRUE ) . '</b>';
        }
        echo "</p>";

        if ( $ajax )
            die();
        else
            return;
    }
}

