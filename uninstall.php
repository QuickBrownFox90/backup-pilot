<?php
//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

global $wpdb;
/* @var wpdb $wpdb */

//only uninstall if no BackupPilot Version active
if ( ! class_exists( 'BackupPilot' ) ) {
    //do nothing if `keep plugin data` enabled
    if ( ! empty( get_site_option( 'backuppilot_cfg_keepplugindata' ) ) ) {
        return;
    }

    //delete plugin options
    if ( is_multisite() ) {
        $wpdb->query( "DELETE FROM " . $wpdb->sitemeta . " WHERE meta_key LIKE '%backuppilot_%' " );
    } else {
        $wpdb->query( "DELETE FROM " . $wpdb->options . " WHERE option_name LIKE '%backuppilot_%' " );
    }

	//delete BackupPilot user roles
	// Special handling for multisite when network-activated.
	if ( is_multisite() ) {
		$sites = get_sites( array(
			'fields' => 'ids',
		) );
		$current_site = get_current_blog_id();

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			backuppilot_remove_roles();
		}

		switch_to_blog( $current_site );
	} else {
		backuppilot_remove_roles();
	}

}

/**
 * Removes BackupPilot roles and capabilities.
 */
function backuppilot_remove_roles() {
	remove_role( 'backuppilot_admin' );
 	remove_role( 'backuppilot_helper' );
	remove_role( 'backuppilot_check' );

	//remove capabilities to administrator role
	$role = get_role( 'administrator' );
	if ( is_object( $role ) && method_exists( $role, 'remove_cap' ) ) {
		$role->remove_cap( 'backuppilot' );
		$role->remove_cap( 'backuppilot_jobs' );
		$role->remove_cap( 'backuppilot_jobs_edit' );
		$role->remove_cap( 'backuppilot_jobs_start' );
		$role->remove_cap( 'backuppilot_backups' );
		$role->remove_cap( 'backuppilot_backups_download' );
		$role->remove_cap( 'backuppilot_backups_delete' );
		$role->remove_cap( 'backuppilot_logs' );
		$role->remove_cap( 'backuppilot_logs_delete' );
		$role->remove_cap( 'backuppilot_settings' );
		$role->remove_cap( 'backuppilot_restore' );
	}
}
