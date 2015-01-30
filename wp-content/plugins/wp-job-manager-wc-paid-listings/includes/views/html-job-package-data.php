<div class="options_group show_if_job_package show_if_job_package_subscription">
	<?php woocommerce_wp_text_input( array( 'id' => '_job_listing_limit', 'label' => __( 'Job listing limit', 'wp-job-manager-wc-paid-listings' ), 'description' => __( 'The number of job listings a user can post with this package. If more than 1, registration will be forced on checkout.', 'wp-job-manager-wc-paid-listings' ), 'value' => ( $limit = get_post_meta( $post_id, '_job_listing_limit', true ) ) ? $limit : '', 'placeholder' => __( 'Unlimited', 'wp-job-manager-wc-paid-listings' ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
		'min'   => '',
		'step' 	=> '1'
	) ) ); ?>

	<?php woocommerce_wp_text_input( array( 'id' => '_job_listing_duration', 'label' => __( 'Job listing duration', 'wp-job-manager-wc-paid-listings' ), 'description' => __( 'The number of days that the job listing will be active.', 'wp-job-manager-wc-paid-listings' ), 'value' => get_post_meta( $post_id, '_job_listing_duration', true ), 'placeholder' => get_option( 'job_manager_submission_duration' ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
		'min'   => '',
		'step' 	=> '1'
	) ) ); ?>

	<?php woocommerce_wp_checkbox( array( 'id' => '_job_listing_featured', 'label' => __( 'Feature job listings?', 'wp-job-manager-wc-paid-listings' ), 'description' => __( 'Feature this job listing - it will be styled differently and sticky.', 'wp-job-manager-wc-paid-listings' ), 'value' => get_post_meta( $post_id, '_job_listing_featured', true ) ) ); ?>

	<script type="text/javascript">
		jQuery(function(){
			jQuery('.pricing').addClass( 'show_if_job_package' );
			jQuery('._tax_status_field').closest('div').addClass( 'show_if_job_package' );
			jQuery('.show_if_subscription, .grouping').addClass( 'show_if_job_package_subscription' );
			jQuery('#product-type').change();
		});
	</script>
</div>