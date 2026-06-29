<p><?php _e( 'You have one or more BackupPilot jobs that need to reauthenticate with Dropbox.', 'backuppilot' ); ?></p>
<p><?php _e( 'The Dropbox API is discontinuing long-lived access tokens. To conform to these new changes, we must implement the use of refresh tokens, which can only be fetched when you reauthenticate.', 'backuppilot' ); ?></p>
<p><?php _e( 'Please visit each job below and reauthenticate your Dropbox connection.', 'backuppilot' ); ?></p>

<ul>
    <?php foreach ( $bind->jobs as $id => $name ) : ?>
        <li>
            <a href="<?php echo wp_nonce_url( add_query_arg( [
                'page' => 'backuppiloteditjob',
                'tab' => 'dest-dropbox',
                'jobid' => $id,
            ], network_admin_url( 'admin.php' ) ), 'edit-job' ); ?>">
                <?php echo esc_html( $name ); ?>
            </a>
        </li>
    <?php endforeach ?>
</ul>
