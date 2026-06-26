<?php
/**
 * Render plugin about Page.
 *
 */
class BackupPilot_Page_About {

	/**
	 * Enqueue style.
	 *
	 * @return void
	 */
	public static function admin_print_styles() {

		?>
		<style type="text/css" media="screen">
			#backuppilot-page {
				background: #fff;
				margin-top: 22px;
				padding: 0 20px;
			}
			#backuppilot-page .inpsyde + h2 {
				visibility: hidden;
			}
			.welcome {
/* 				max-width: 960px; */
			}
			.welcome .welcome_inner {
				margin:0 auto;
				max-width: 960px;
			}
			.welcome .welcome_inner h1{
				font-size:42px;
			}
			.welcome .welcome_inner .welcometxt {
				margin-bottom: 40px;
				overflow: hidden;
				border-bottom: 1px #ccc dotted;
				text-align: center;
				padding-bottom: 25px;
				position: relative;
			}
			.welcome .welcome_inner .welcometxt p{
				line-height:20px;
				font-size:18px;
			}
			.welcome .welcome_inner .feature-box{
				clear: both;
				margin-bottom: 40px;
				overflow: hidden;
			}
			.welcome .welcome_inner .feature-box .feature-image{
				float: left;
				width:18%;
				height:auto;
			}
			.welcome .welcome_inner .feature-box .feature-image img{
				width:100%;
				height:auto;
				max-width:350px;
			}
			.welcome .welcome_inner .feature-box .feature-text{
				float: left;
				width:72%;
				padding: 0 0 20px 20px;
			}
			.welcome .welcome_inner .feature-box-right .feature-text {
				padding: 0 20px 20px 0;
			}
			.welcome .welcome_inner .feature-box .feature-text h3{
				color:rgb(0, 155, 204);
				font-weight:normal;
				font-size:24px;
				margin:0 0 10px 0;
				text-align:left;
			}
			.welcome .welcome_inner .feature-box .left {
				float:left;
			}
			.welcome .welcome_inner .feature-box .right {
				float:right;
			}
			.welcome .welcome_inner .featuretitle h3 {
				font-size:28px;
				font-weight:normal;
				text-align:left;
				margin-bottom:25px;
			}
			.welcome .button-primary-bwp {
				float:left;
				padding:15px;
				font-size:18px;
				text-decoration:none;
				background-color:#38b0eb;
				color:#fff;
				border:none;
				cursor:pointer;
				margin: 35px 0;
			}
			.welcome .button-primary-bwp:hover {
				background-color:#064565;
				cursor:pointer;
			}
			@media only screen and (max-width: 1100px), only screen and (max-device-width: 1100px) {
				.welcome .welcome_inner h1{
					font-size:32px;
				}
				.welcome .welcome_inner .featuretitle h3 {
					font-size:22px;
					font-weight:normal;
					text-align:left;
					margin-bottom:25px;
				}
				.welcome .welcome_inner .welcometxt p{
					line-height:20px;
					font-size:14px;
				}
				.welcome .welcome_inner .feature-box .feature-text h3{
					font-weight:normal;
					font-size:20px;
					margin:0 0 10px 0;
					text-align:left;
				}
				.welcome .welcome_inner .feature-box .feature-text{
					width:72%;
					font-size:14px;
					line-height:20px;
				}
				.welcome .button-primary-bwp {
					float:left;
					padding:10px;
					font-size:16px;
					text-decoration:none;
					background-color:#38b0eb;
					color:#fff;
					border:none;
					cursor:pointer;
					margin: 35px 0;
				}
			}
			@media only screen and (max-width: 780px), only screen and (max-device-width: 780px) {
				.welcome .welcome_inner h1{
					font-size:22px;
				}
				.welcome .welcome_inner .featuretitle h3 {
					font-size:22px;
					font-weight:normal;
					text-align:left;
				}
				.welcome .welcome_inner .welcometxt p{
					line-height:20px;
					font-size:14px;
				}
				.welcome .welcome_inner .feature-box .feature-text h3{
					font-weight:normal;
					font-size:16px;
					margin:0 0 10px 0;
				}
				.welcome .welcome_inner .feature-box .feature-text{
					width:72%;
					font-size:12px;
					line-height:16px;
					text-align:left;
				}
				.welcome .button-primary-bwp {
					float:left;
					padding:10px;
					font-size:16px;
					text-decoration:none;
					background-color:#38b0eb;
					color:#fff;
					border:none;
					cursor:pointer;
					margin: 35px 0;
				}
			}

			.backuppilot_comp {
				margin: 20px auto;
				width: 100%;
				font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
				font-size: 16px;
			}

			.backuppilot_comp table {
				border: none;
			}

			.backuppilot_comp table tbody tr.even td {
				border: none;
				background: none;
				padding: 15px;
				margin: 0;
			}

			.backuppilot_comp table tbody tr.odd td {
				border: none;
				background: none;
				padding: 15px;
				margin: 0;
			}

			.backuppilot_comp h3 {
				font-family: "Arial", sans-serif;
				font-size: 42px;
				text-align: center;
				font-weight: normal;
				color: #333;
				line-height: 44px;
				margin: 20px 0;
			}

			.backuppilot_comp table tbody tr.ub {
				font-family: 'MisoRegular', "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
				font-size: 26px;
			}

			.backuppilot_comp table tbody tr.ubdown {
				font-family: 'MisoRegular', "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
				font-size: 26px;
				background: none !important;
			}

			.backuppilot_comp table tbody tr.even {
				background-image: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/even.png);
			}

			.backuppilot_comp table tbody tr.odd {
				background-image: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/odd.png);
			}

			.backuppilot_comp table tbody tr.ub td.pro {
				height: 50px;
				text-align: center;
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/hgtopgreen.png) no-repeat bottom center;
				color: #fff;
				border-left: 1px solid #112a32;
			}

			.backuppilot_comp table tbody tr.ub td.free {
				height: 50px;
				text-align: center;
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/hgtopgreen.png) no-repeat bottom center;
				color: #fff;
			}

			.backuppilot_comp table tbody tr.ubdown td.pro {
				height: 50px;
				text-align: center;
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/hgbtgreen.png) no-repeat top center;
				color: #fff;
				border-left: 1px solid #1c3e49;
				background-color: #153540;
			}

			.backuppilot_comp table tbody tr.ubdown td.pro a {
				color: #fff;
				text-decoration: none;
				cursor: auto;
				font-weight: 300;
				line-height: 1.4em;
				font-size: 18px;
			}

			.backuppilot_comp table tbody tr.ubdown td.free {
				height: 50px;
				text-align: center;
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/hgbtred.png) no-repeat top center;
				color: #fff;
			}

			.backuppilot_comp table tbody tr.ubdown td.free a {
				color: #fff;
			}

			.backuppilot_comp table tbody tr.even td.tick {
				width: 100px;
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/tickeven.png) no-repeat center;
				border-bottom: 1px solid #799e14;
				border-top: 1px solid #a2d123;
				border-left: 1px solid #799e14;
			}

			.backuppilot_comp table tbody tr.odd td.tick {
				width: 100px;
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/tickodd.png) no-repeat center;
				border-left: 1px solid #799e14;
			}

			.backuppilot_comp table tbody tr.even td.error {
				width: 100px;
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/erroreven.png) no-repeat center;
				border-bottom: 1px solid #b13020;
				border-top: 1px solid #e84936;
			}

			.backuppilot_comp table tbody tr.odd td.error {
				width: 100px;
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/errorodd.png) no-repeat center;
			}

			.backuppilot_comp table tbody tr.even:hover {
				background-image: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/hover.png);
			}

			.backuppilot_comp table tbody tr.odd:hover {
				background-image: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/hover.png);
			}

			.backuppilot_comp table tbody tr.even:hover td.tick {
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/tickhover.png) center;
			}

			.backuppilot_comp table tbody tr.odd:hover td.tick {
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/tickhover.png) center;
			}

			.backuppilot_comp table tbody tr.even:hover td.error {
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/errorhover.png) center;
			}

			.backuppilot_comp table tbody tr.odd:hover td.error {
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/errorhover.png) center;
			}

			.backuppilot_comp table tbody tr.ubdown:hover td.pro:hover {
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/hgbtgreenhover.png) no-repeat top center;
				background-color: #255667;
			}

			.backuppilot_comp table tbody tr.ubdown:hover td.free:hover {
				background: url(<?php echo BackupPilot::get_plugin_data( 'URL' );?>/assets/images/hgbtredhover.png) no-repeat top center;
			}
		</style>
		<?php
	}

	/**
	 * Enqueue script.
	 *
	 * @return void
	 */
	public static function admin_print_scripts() {

		wp_enqueue_script( 'backuppilotgeneral' );

	}


	/**
	 * Print the markup.
	 *
	 * @return void
	 */
	public static function page() {

		?>
        <div class="wrap" id="backuppilot-page">
			<?php BackupPilot_Admin::display_messages(); ?>
            <div class="welcome">
            	<div class="welcome_inner">
                    <div class="welcometxt">
                        <div class="backuppilot-welcome">
                            <img class="backuppilot-banner-img" src="<?php echo BackupPilot::get_plugin_data( 'URL' ) ?>/assets/images/banner.jpg" alt="<?php esc_html_e( 'BackupPilot banner', 'backuppilot' ); ?>" />
                            <h1><?php esc_html_e( 'Welcome to BackupPilot', 'backuppilot' ); ?></h1>
                            <p><?php
_e( 'Use your backup archives to save your entire WordPress installation including <code>/wp-content/</code>. Push them to an external storage service if you don’t want to save the backups on the same server. With a single backup archive you are able to restore an installation.', 'backuppilot' ); ?></p>
                            <p><?php esc_html_e( 'Ready to set up a backup job? Use one of the wizards to plan what you want to save.', 'backuppilot' ); ?></p>
                        </div>
                    </div>
		            <div>
		            </div>
            		<div class="features">

                    	<div class="feature-box <?php self::feature_class(); ?>">
                        	<div class="feature-image">
                                <img title="<?php esc_html_e( 'Save your database', 'backuppilot' ); ?>" src="<?php echo esc_attr(BackupPilot::get_plugin_data( 'URL' )); ?>/assets/images/imagesave.png" />
                            </div>
                            <div class="feature-text">
                            	<h3><?php esc_html_e( 'Save your database regularly', 'backuppilot' ); ?></h3>
                                <p><?php echo str_replace( '\"','"', sprintf( __( 'With BackupPilot you can schedule the database backup to run automatically. With a single backup file you can restore your database. You should <a href="%s">set up a backup job</a>, so you will never forget it. There is also an option to repair and optimize the database after each backup.', 'backuppilot' ), network_admin_url( 'admin.php').'?page=backuppiloteditjob' ) ); ?></p>
                            </div>
                        </div>
                        <div class="feature-box <?php self::feature_class(); ?>">
                            <div class="feature-text">
                            	<h3><?php esc_html_e('WordPress XML Export', 'backuppilot' ); ?></h3>
                                <p><?php esc_html_e('You can choose the built-in WordPress export format in addition or exclusive to save your data. This works in automated backups too of course. The advantage is: you can import these files into a blog with the regular WordPress importer.', 'backuppilot'); ?></p>
                            </div>
                            <div class="feature-image">
                            	<img title="<?php esc_html_e( 'WordPress XML Export', 'backuppilot' ); ?>" src="<?php echo BackupPilot::get_plugin_data( 'URL' ); ?>/assets/images/imagexml.png" />
                            </div>
                        </div>
                        <div class="feature-box <?php self::feature_class(); ?>">
                            <div class="feature-image">
                            	<img title="<?php esc_html_e( 'Save all data from the webserver', 'backuppilot' ); ?>" src="<?php echo BackupPilot::get_plugin_data( 'URL' ); ?>/assets/images/imagedata.png" />
                            </div>
                            <div class="feature-text">
                            	<h3><?php esc_html_e('Save all files', 'backuppilot'); ?></h3>
                                <p><?php echo str_replace( '\"','"', sprintf( __('You can backup all your attachments, also all system files, plugins and themes in a single file. You can <a href="%s">create a job</a> to update a backup copy of your file system only when files are changed.', 'backuppilot'), network_admin_url( 'admin.php' ) . '?page=backuppiloteditjob' ) ); ?></p>
                            </div>
                        </div>
                        <div class="feature-box <?php self::feature_class(); ?>">
                            <div class="feature-text">
                            	<h3><?php esc_html_e( 'Security!', 'backuppilot' ); ?></h3>
                                <p><?php esc_html_e('By default everything is encrypted: connections to external services, local files and access to directories.', 'backuppilot'); ?></p>
                            </div>
                        	<div class="feature-image">
                            	<img title="<?php esc_html_e( 'Security!', 'backuppilot' ); ?>" src="<?php echo esc_attr(BackupPilot::get_plugin_data( 'URL' )); ?>/assets/images/imagesec.png" />
                            </div>
                        </div>
                        <div class="feature-box <?php self::feature_class(); ?>">
                            <div class="feature-image">
                            	<img title="<?php esc_html_e( 'Cloud Support', 'backuppilot' ); ?>" src="<?php echo BackupPilot::get_plugin_data( 'URL' ); ?>/assets/images/imagecloud.png" />
                            </div>
                            <div class="feature-text">
                            	<h3><?php esc_html_e( 'Cloud Support', 'backuppilot' ); ?></h3>
                                <p><?php esc_html_e( 'BackupPilot supports multiple cloud services in parallel. This ensures backups are redundant.', 'backuppilot' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
					<div class="backuppilot_comp">
						<h3><?php esc_html_e( 'Features / differences between Free and Pro', 'backuppilot' ); ?></h3>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr class="even ub">
								<td><?php esc_html_e( 'Features', 'backuppilot' ); ?></td>
								<td class="free"><?php esc_html_e( 'FREE', 'backuppilot' ); ?></td>
								<td class="pro"><?php esc_html_e( 'PRO', 'backuppilot' ); ?></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Complete database backup', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Complete file backup', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Database check', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Data compression', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'WordPress XML export', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'List of installed plugins', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Backup archives management', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Log file management', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Start jobs per WP-Cron, URL, system, backend or WP-CLI', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Log report via email', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Backup to Microsoft Azure', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Backup as email', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Backup to S3 services (Amazon, Google Storage, Hosteurope and more)', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Backup to Dropbox', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Backup to FTP server', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Backup to your web space', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Backup to SugarSync', 'backuppilot' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Backup to Google Drive', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Backup to Amazon Glacier', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Backup to OneDrive', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Backup to HiDrive', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Restore manually uploaded Backup Archives', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Standalone Restore App', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Encrypt Backup Archives', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Restore Encrypted Backups', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Custom API keys for DropBox and SugarSync', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'XML database backup as PHPMyAdmin schema', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Database backup as mysqldump per command line', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Database backup for additional MySQL databases', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Import and export job settings as XML', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Wizard for system tests', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Wizard for scheduled backup jobs', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Wizard to import settings and backup jobs', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Differential backup of changed directories to Dropbox', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Differential backup of changed directories to S3', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Differential backup of changed directories to MS Azure', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Differential backup of changed directories to Google Drive', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Differential backup of changed directories to local folder', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Restore from Folder', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Restore from Google Drive', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Restore from Amazon S3', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Restore from Microsoft Azure', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e( 'Restore from Dropbox', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php esc_html_e( 'Restore from FTP', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( '<strong>Premium support</strong>', 'backuppilot' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( '<strong>Automatic updates</strong>', 'backuppilot' ); ?></td>
								<td class="error" style="border-bottom:none;"></td>
								<td class="tick" style="border-bottom:none;"></td>
							</tr>
						</table>
					</div>
            </div>
        </div>
	<?php
	}

	/**
	 * Alternate between 'left' and 'right' CSS class.
	 *
	 * @since  2013.02.19
	 * @return void
	 */
	protected static function feature_class() {

		static $class = 'feature-box-left';

		print $class;

		$class = 'feature-box-left' === $class ? 'feature-box-right' : 'feature-box-left';
	}
}
