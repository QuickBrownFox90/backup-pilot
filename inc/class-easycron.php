<?php
/**
 * Class for integration of EasyCron.com
 * Documentation: https://www.easycron.com/document
 */
class BackupPilot_EasyCron {

    public function __construct() {

        add_action( 'backuppilot_page_settings_tab_apikey', [ $this, 'api_key_form' ], 11 );
        add_action( 'backuppilot_page_settings_save', [ $this, 'api_key_save_form' ], 11 );
    }

    public static function update( $backuppilot_jobid ) {

        $params = [
            'id' => NULL,
            'email_me' => 0,
            'log_output_length' => 0,
            'testfirst' => 0
        ];

        if ( empty( $backuppilot_jobid ) ) {
            $params[ 'id' ] = get_site_option( 'backuppilot_cfg_easycronjobid' );
            $params[ 'cron_job_name' ] = sprintf( 'WordPress on %s', home_url() );
            $params[ 'cron_expression' ] = '*/5 * * * *';
            $url = BackupPilot_Job::get_jobrun_url( 'runext', 0 );
            $url = remove_query_arg( '_nonce', $url[ 'url' ] );
            $url = remove_query_arg( 'doing_wp_cron', $url );
            $url = remove_query_arg( 'backuppilot_run', $url );
            $url = add_query_arg( [ 'doing_wp_cron' => '' ], $url );
            $cookies = get_site_transient( 'backuppilot_cookies' );
            $params[ 'url' ] = $url;
            if ( ! empty( $cookies ) ) {
                $params[ 'cookies' ] = http_build_query( $cookies );
            }
        }

        else {
            $params[ 'id' ] = BackupPilot_Option::get( $backuppilot_jobid, 'easycronjobid' );
            if ( empty( $params[ 'id' ] ) ) {
                $params[ 'id' ] = NULL;
            }
            $params[ 'cron_job_name' ] = sprintf( 'BackupPilot %s on %s', BackupPilot_Option::get( $backuppilot_jobid, 'name' ), home_url() );
            $params[ 'cron_expression' ] = BackupPilot_Option::get( $backuppilot_jobid, 'cron' );
            $url = BackupPilot_Job::get_jobrun_url( 'runext', $backuppilot_jobid );
            $cookies = get_site_transient( 'backuppilot_cookies' );
            $params[ 'url' ] = $url[ 'url' ];
            if ( ! empty( $cookies ) ) {
                $params[ 'cookies' ] = http_build_query( $cookies );
            }
        }

        if ( empty( $params[ 'id' ] ) ) {
            $message = self::query_api( 'add' ,$params );
        }

        else {
            $message = self::query_api( 'edit', $params );
        }

        delete_site_transient( 'backuppilot_easycron_' . $params[ 'id' ] );

        if ( $message[ 'status' ] == 'success' && !empty( $message[ 'cron_job_id' ] ) ) {
            if ( empty( $backuppilot_jobid ) ) {
                update_site_option( 'backuppilot_cfg_easycronjobid', $message[ 'cron_job_id' ] );
            }

            else {
                BackupPilot_Option::update( $backuppilot_jobid, 'easycronjobid', $message[ 'cron_job_id' ] );
            }
            return TRUE;
        }

        else {
            if ( $message[ 'error' ][ 'code' ] == 25 ) {
                if ( empty( $backuppilot_jobid ) ) {
                    delete_site_option( 'backuppilot_cfg_easycronjobid' );
                }

                else {
                    BackupPilot_Option::delete( $backuppilot_jobid, 'easycronjobid' );
                }
            }
        }

        return FALSE;
    }


    public static function delete( $backuppilot_jobid ) {

        if ( empty( $backuppilot_jobid ) ) {
            $id = get_site_option( 'backuppilot_cfg_easycronjobid' );
        }

        else {
            $id = BackupPilot_Option::get( $backuppilot_jobid, 'easycronjobid' );
        }

        if ( empty( $id ) ) {
            return TRUE;
        }


        $message = self::query_api( 'delete', [ 'id' => $id ] );

        delete_site_transient( 'backuppilot_easycron_' . $id );

        if ( $message[ 'status' ] == 'success' && !empty( $message[ 'cron_job_id' ] ) ) {
            if ( empty( $backuppilot_jobid ) ) {
                delete_site_option( 'backuppilot_cfg_easycronjobid' );
            }

            else {
                BackupPilot_Option::delete( $backuppilot_jobid, 'easycronjobid' );
            }
            return TRUE;
        }

        else {
            if ( $message[ 'error' ][ 'code' ] == 25 ) {
                if ( empty( $backuppilot_jobid ) ) {
                    delete_site_option( 'backuppilot_cfg_easycronjobid' );
                }

                else {
                    BackupPilot_Option::delete( $backuppilot_jobid, 'easycronjobid' );
                }
            }
        }

        return FALSE;
    }


    public static function status( $backuppilot_jobid ) {

        if ( empty( $backuppilot_jobid ) ) {
            $id = get_site_option( 'backuppilot_cfg_easycronjobid' );
        }

        else {
            $id = BackupPilot_Option::get( $backuppilot_jobid, 'easycronjobid' );
        }

        if ( empty( $id ) ) {
            return [];
        }

        $cron_job = get_site_transient( 'backuppilot_easycron_' . $id );
        if ( ! empty( $cron_job ) ) {
            return  $cron_job;
        }

        $message = self::query_api( 'detail', [ 'id' => $id ] );

        if ( $message[ 'status' ] == 'success' && ! empty( $message[ 'cron_job' ] ) ) {
            set_site_transient( 'backuppilot_easycron_' . $id, $message[ 'cron_job' ], HOUR_IN_SECONDS - 30 );
            return $message[ 'cron_job' ];
        }

        else {
            if ( $message[ 'error' ][ 'code' ] == 25 ) {
                if ( empty( $backuppilot_jobid ) ) {
                    delete_site_option( 'backuppilot_cfg_easycronjobid' );
                }

                else {
                    BackupPilot_Option::delete( $backuppilot_jobid, 'easycronjobid' );
                }
            }
        }

        return [];
    }


    private static function query_api( $endpoint, array $params ) {

        $message = [ 'status' => 'error', 'error' => [ 'code' => 0, 'message' => 'Please setup EasyCron auth api key in settings' ] ];

        $params[ 'token' ] = get_site_option( 'backuppilot_cfg_easycronapikey' );
        if ( empty( $params[ 'token' ] ) ) {
            return $message;
        }

        $result = wp_remote_get( 'https://www.easycron.com/rest/' . $endpoint .'?' . http_build_query( $params ) );

        if ( wp_remote_retrieve_response_code( $result ) != 200 ) {
            $message[ 'error' ][ 'code' ]    = wp_remote_retrieve_response_code( $result );
            $message[ 'error' ][ 'message' ] = wp_remote_retrieve_response_message( $result );
        }

        else {
            $json = wp_remote_retrieve_body( $result );
            $message = json_decode( $json, TRUE );
        }

        if ( $message[ 'status' ] != 'success' ) {
            BackupPilot_Admin::message( sprintf( __( 'EasyCron.com API returns (%s): %s', 'backuppilot' ), esc_attr( $message[ 'error' ][ 'code' ] ), esc_attr( $message[ 'error' ][ 'message' ] ) ), TRUE );
        }

        return $message;
    }


    public function api_key_form() {
        ?>
        <h3 class="title"><?php esc_html_e( 'EasyCron', 'backuppilot' ); ?></h3>
        <p><?php _e( 'Here you can setup your <a href="https://www.easycron.com/user/token?ref=36673" title="Affiliate Link!">EasyCron.com API key</a> to use this service.', 'backuppilot' ); ?></p>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="easycronapikeyid"><?php esc_html_e( 'Api key:', 'backuppilot' ); ?></label></th>
                <td>
                    <input name="easycronapikey" type="password" id="easycronapikeyid"
                        value="<?php echo esc_attr( get_site_option( 'backuppilot_cfg_easycronapikey' ) );?>"
                        class="regular-text" autocomplete="off" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="easycronwpid"><?php esc_html_e( 'Trigger WordPress Cron:', 'backuppilot' ); ?></label></th>
                <td>
                    <input name="easycronwp" type="checkbox" id="easycronwpid" value="1" <?php $wpcron = get_site_option( 'backuppilot_cfg_easycronjobid' ); checked( !empty( $wpcron ) ); ?> />
                    <?php esc_html_e( 'If you check this box, a cron job will be created on EasyCron that all 5 Minutes calls the WordPress cron.', 'backuppilot' ); ?>
                </td>
            </tr>
        </table>
        <?php
    }


    public function api_key_save_form() {

        if ( ! empty( $_POST[ 'easycronapikey' ] ) ) {
            update_site_option( 'backuppilot_cfg_easycronapikey',  sanitize_text_field( $_POST[ 'easycronapikey' ] ) );
        }

        else {
            delete_site_option( 'backuppilot_cfg_easycronapikey' );
        }

        if ( ! empty( $_POST[ 'easycronwp' ] ) ) {
            BackupPilot_EasyCron::update( 0 );
        }

        else {
            BackupPilot_EasyCron::delete( 0 );
        }
    }
}
