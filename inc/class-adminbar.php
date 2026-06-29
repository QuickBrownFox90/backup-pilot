<?php
/**
 * Class to display BackupPilot in Adminbar
 */
class BackupPilot_Adminbar {

    /**
     * @var BackupPilot_Admin
     */
    private $admin;

    public function __construct(BackupPilot_Admin $admin) {

        $this->admin = $admin;
    }

    public function init()
    {
        BackupPilot::load_text_domain();

        add_action('admin_bar_menu', [$this, 'adminbar'], 100);
        add_action('wp_head', [$this->admin, 'admin_css']);
    }

    /**
     * @global $wp_admin_bar WP_Admin_Bar
     */
    public function adminbar() {
        if (!is_admin_bar_showing()) {
            return;
        }

        global $wp_admin_bar;
        /* @var WP_Admin_Bar $wp_admin_bar */

        $menu_title = '<span class="ab-icon dashicons dashicons-backup"></span>';
        $menu_herf  = network_admin_url( 'admin.php?page=backuppilot' );
        if ( file_exists( BackupPilot::get_plugin_data( 'running_file' ) ) && current_user_can( 'backuppilot_jobs_start' ) ) {
            $menu_title = '<span class="ab-icon dashicons dashicons-backup"></span><span class="ab-label">' . esc_html( BackupPilot::get_plugin_data( 'name' ) )  . ' <span id="backuppilot-adminbar-running">' . esc_html__( 'running', 'backuppilot' ) . '</span></span>';
            $menu_herf  = network_admin_url( 'admin.php?page=backuppilotjobs' );
        }

        if ( current_user_can( 'backuppilot' ) )
            $wp_admin_bar->add_menu( [
                                          'id'    => 'backuppilot',
                                          'title' => $menu_title,
                                          'href'  => $menu_herf,
                                          'meta'  => [ 'title' => BackupPilot::get_plugin_data( 'name' ) ]
                                     ] );

        if ( file_exists( BackupPilot::get_plugin_data( 'running_file' ) ) && current_user_can( 'backuppilot_jobs_start' ) ) {
            $wp_admin_bar->add_menu( [
                                          'id'     => 'backuppilot_working',
                                          'parent' => 'backuppilot_jobs',
                                          'title'  => __( 'Now Running', 'backuppilot' ),
                                          'href'   => network_admin_url( 'admin.php?page=backuppilotjobs' )
                                     ] );
            $wp_admin_bar->add_menu( [
                                          'id'     => 'backuppilot_working_abort',
                                          'parent' => 'backuppilot_working',
                                          'title'  => __( 'Abort!', 'backuppilot' ),
                                          'href'   => wp_nonce_url( network_admin_url( 'admin.php?page=backuppilot&action=abort' ), 'abort-job' )
                                     ] );
        }

        if ( current_user_can( 'backuppilot_jobs' ) )
            $wp_admin_bar->add_menu( [
                                      'id'     => 'backuppilot_jobs',
                                      'parent' => 'backuppilot',
                                      'title'  => __( 'Jobs', 'backuppilot' ),
                                      'href'   => network_admin_url( 'admin.php?page=backuppilotjobs' )
                                 ] );

        if ( current_user_can( 'backuppilot_jobs_edit' ) )
            $wp_admin_bar->add_menu( [
                                      'id'     => 'backuppilot_jobs_new',
                                      'parent' => 'backuppilot_jobs',
                                      'title'  => __( 'Add new', 'backuppilot' ),
                                      'href'   => network_admin_url( 'admin.php?page=backuppiloteditjob&tab=job' )
                                 ] );

        if ( current_user_can( 'backuppilot_logs' ) )
            $wp_admin_bar->add_menu( [
                                      'id'     => 'backuppilot_logs',
                                      'parent' => 'backuppilot',
                                      'title'  => __( 'Logs', 'backuppilot' ),
                                      'href'   => network_admin_url( 'admin.php?page=backuppilotlogs' )
                                 ] );

        if ( current_user_can( 'backuppilot_backups' ) )
            $wp_admin_bar->add_menu( [
                                      'id'     => 'backuppilot_backups',
                                      'parent' => 'backuppilot',
                                      'title'  => __( 'Backups', 'backuppilot' ),
                                      'href'   => network_admin_url( 'admin.php?page=backuppilotbackups' )
                                 ] );


        //add jobs
        $jobs = (array)BackupPilot_Option::get_job_ids();
        foreach ( $jobs as $jobid ) {
            if ( current_user_can( 'backuppilot_jobs_edit' ) ) {
                $name = BackupPilot_Option::get( $jobid, 'name' );
                $wp_admin_bar->add_menu( [
                                              'id'     => 'backuppilot_jobs_' . $jobid,
                                              'parent' => 'backuppilot_jobs',
                                              'title'  => $name,
                                              'href'   => wp_nonce_url( network_admin_url( 'admin.php?page=backuppiloteditjob&tab=job&jobid=' . $jobid ) , 'edit-job' )
                                         ] );
            }
            if ( current_user_can( 'backuppilot_jobs_start' ) ) {
                $url = BackupPilot_Job::get_jobrun_url( 'runnowlink', $jobid );
                $wp_admin_bar->add_menu( [
                                              'id'     => 'backuppilot_jobs_runnow_' . $jobid,
                                              'parent' => 'backuppilot_jobs_' . $jobid,
                                              'title'  => __( 'Run Now', 'backuppilot' ),
                                              'href'   => esc_url( $url[ 'url' ] )
                                         ] );
            }
        }
    }
}
