<?php
class BackupPilot_Help {

	public static function help() {

		if ( method_exists( get_current_screen(), 'add_help_tab' ) ) {
			get_current_screen()->add_help_tab( array(
													 'id'      => 'plugininfo',
													 'title'   => __( 'Plugin Info', 'backuppilot' ),
													 'content' =>
													 '<p> '.str_replace( '\"','"', sprintf( _x( '%1$s version %2$s. A project by Quick Brown Fox.','Plugin name and link; Plugin Version','backuppilot' ), '<a href="' . esc_attr__( 'http://dev.patrick-mauersberger.de', 'backuppilot' ) . '">' . BackupPilot::get_plugin_data( 'Name' ) . '</a>' , BackupPilot::get_plugin_data( 'Version' ) ) ) . '</p>'
													 . '<p>' . esc_html__( 'BackupPilot comes with ABSOLUTELY NO WARRANTY. This is a free software, and you are welcome to redistribute it under certain conditions.', 'backuppilot' ) . '</p>'
												) );

			$text_help_sidebar = '<p><strong>' . __( 'For more information:', 'backuppilot' ) . '</strong></p>';
			$text_help_sidebar .= '<p><a href="' . esc_attr__( 'http://dev.patrick-mauersberger.de', 'backuppilot' ) . '">' . BackupPilot::get_plugin_data( 'Name' ) . '</a></p>';
			//$text_help_sidebar .= '<p><a href="http://wordpress.org/extend/plugins/backuppilot/">' . esc_html__( 'Plugin on wordpress.org', 'backuppilot' ) . '</a></p>';

			get_current_screen()->set_help_sidebar( $text_help_sidebar );
		}

	}

	/**
	 * @static
	 *
	 * @param array $tab
	 */
	public static function add_tab( $tab = array() ) {

		if ( method_exists( get_current_screen(), 'add_help_tab' ) )
			get_current_screen()->add_help_tab( $tab );

	}

}
