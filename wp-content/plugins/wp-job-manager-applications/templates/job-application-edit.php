<form class="job-manager-application-edit-form job-manager-form" method="post">
	
	<fieldset class="fieldset-status">
		<label for="application-status-<?php esc_attr_e( $application->ID ); ?>"><?php _e( 'Application status', 'wp-job-manager-applications' ); ?>:</label>
		<div class="field">
			<select id="application-status-<?php esc_attr_e( $application->ID ); ?>" name="application_status">
				<option value="new" <?php selected( $application->post_status, 'new' ); ?>><?php _e( 'New', 'wp-job-manager-applications' ); ?></option>
				<option value="interviewed" <?php selected( $application->post_status, 'interviewed' ); ?>><?php _e( 'Interviewed', 'wp-job-manager-applications' ); ?></option>
				<option value="offer" <?php selected( $application->post_status, 'offer' ); ?>><?php _e( 'Offer extended', 'wp-job-manager-applications' ); ?></option>
				<option value="hired" <?php selected( $application->post_status, 'hired' ); ?>><?php _e( 'Hired', 'wp-job-manager-applications' ); ?></option>
				<option value="archived" <?php selected( $application->post_status, 'archived' ); ?>><?php _e( 'Archived', 'wp-job-manager-applications' ); ?></option>
			</select>
		</div>
	</fieldset>

	<fieldset class="fieldset-rating">
		<label for="application-rating-<?php esc_attr_e( $application->ID ); ?>"><?php _e( 'Rating (out of 5)', 'wp-job-manager-applications' ); ?>:</label>
		<div class="field">
			<input type="number" id="application-rating-<?php esc_attr_e( $application->ID ); ?>" name="application_rating" step="0.1" max="5" min="0" placeholder="0" value="<?php echo esc_attr( get_job_application_rating( $application->ID ) ); ?>" />
		</div>
	</fieldset>

	<p>
		<a class="delete_job_application" href="<?php echo wp_nonce_url( add_query_arg( 'delete_job_application', $application->ID ), 'delete_job_application' ); ?>"><?php _e( 'Delete', 'wp-job-manager-applications' ); ?></a>
		<input type="submit" name="wp_job_manager_edit_application" value="<?php esc_attr_e( 'Save changes', 'wp-job-manager-applications' ); ?>" />
		<input type="hidden" name="application_id" value="<?php echo absint( $application->ID ); ?>" />
		<?php wp_nonce_field( 'edit_job_application' ); ?>
	</p>
</form>