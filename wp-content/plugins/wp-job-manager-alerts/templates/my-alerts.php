<div id="job-manager-alerts">
	<p><?php printf( __( 'Your job alerts are shown in the table below. Your alerts will be sent to %s.', 'wp-job-manager-alerts' ), $user->user_email ); ?></p>
	<table class="job-manager-alerts">
		<thead>
			<tr>
				<th><?php _e( 'Alert Name', 'wp-job-manager-alerts' ); ?></th>
				<th><?php _e( 'Date Created', 'wp-job-manager-alerts' ); ?></th>
				<th><?php _e( 'Keywords', 'wp-job-manager-alerts' ); ?></th>
				<th><?php _e( 'Location', 'wp-job-manager-alerts' ); ?></th>
				<th><?php _e( 'Frequency', 'wp-job-manager-alerts' ); ?></th>
				<th><?php _e( 'Status', 'wp-job-manager-alerts' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="6">
					<a href="<?php echo remove_query_arg( 'updated', add_query_arg( 'action', 'add_alert' ) ); ?>"><?php _e( 'Add alert', 'wp-job-manager-alerts' ); ?></a>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ( $alerts as $alert ) : ?>
				<tr>
					<td>
						<?php echo esc_html( $alert->post_title ); ?>
						<ul class="job-alert-actions">
							<?php
								$actions = apply_filters( 'job_manager_alert_actions', array(
									'view' => array(
										'label' => __( 'Show Results', 'wp-job-manager-alerts' ),
										'nonce' => false
									),
									'email' => array(
										'label' => __( 'Email', 'wp-job-manager-alerts' ),
										'nonce' => true
									),
									'edit' => array(
										'label' => __( 'Edit', 'wp-job-manager-alerts' ),
										'nonce' => false
									),
									'toggle_status' => array(
										'label' => $alert->post_status == 'draft' ? __( 'Enable', 'wp-job-manager-alerts' ) : __( 'Disable', 'wp-job-manager-alerts' ),
										'nonce' => true
									),
									'delete' => array(
										'label' => __( 'Delete', 'wp-job-manager-alerts' ),
										'nonce' => true
									)
								), $alert );

								foreach ( $actions as $action => $value ) {
									$action_url = remove_query_arg( 'updated', add_query_arg( array( 'action' => $action, 'alert_id' => $alert->ID ) ) );

									if ( $value['nonce'] )
										$action_url = wp_nonce_url( $action_url, 'job_manager_alert_actions' );

									echo '<li><a href="' . $action_url . '" class="job-alerts-action-' . $action . '">' . $value['label'] . '</a></li>';
								}
							?>
						</ul>
					</td>
					<td class="date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $alert->post_date ) ); ?></td>
					<td class="alert_keyword"><?php
						if ( $value = get_post_meta( $alert->ID, 'alert_keyword', true ) )
							echo esc_html( '&ldquo;' . $value . '&rdquo;' );
						else
							echo '&ndash;';
					?></td>
					<td class="alert_location"><?php
						if ( $value = get_post_meta( $alert->ID, 'alert_location', true ) )
							echo esc_html( '&ldquo;' . $value . '&rdquo;' );
						else
							echo '&ndash;';
					?></td>
					<td class="alert_frequency"><?php
						switch ( $freq = get_post_meta( $alert->ID, 'alert_frequency', true ) ) {
							case "daily" :
								_e( 'Daily', 'wp-job-manager-alerts' );
							break;
							case "weekly" :
								_e( 'Weekly', 'wp-job-manager-alerts' );
							break;
							case "fornightly" :
								_e( 'Fornightly', 'wp-job-manager-alerts' );
							break;
						}
					?></td>
					<td class="status"><?php echo $alert->post_status == 'draft' ? __( 'Disabled', 'wp-job-manager-alerts' ) : __( 'Enabled', 'wp-job-manager-alerts' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>