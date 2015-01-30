<div class="options_group show_if_resume_package show_if_resume_package_subscription">
	<?php woocommerce_wp_text_input( array( 'id' => '_resume_limit', 'label' => __( 'Resume posting limit', 'wp-job-manager-wc-paid-listings' ), 'description' => __( 'The number of resumes a user can post with this package. If more than 1, registration will be forced on checkout.', 'wp-job-manager-wc-paid-listings' ), 'value' => ( $limit = get_post_meta( $post_id, '_resume_limit', true ) ) ? $limit : '', 'placeholder' => __( 'Unlimited', 'wp-job-manager-wc-paid-listings' ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
		'min'   => '',
		'step' 	=> '1'
	) ) ); ?>

	<?php woocommerce_wp_text_input( array( 'id' => '_resume_duration', 'label' => __( 'Resume listing duration', 'wp-job-manager-wc-paid-listings' ), 'description' => __( 'The number of days that the resume will be active.', 'wp-job-manager-wc-paid-listings' ), 'value' => get_post_meta( $post_id, '_resume_duration', true ), 'placeholder' => get_option( 'resume_manager_submission_duration' ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
		'min'   => '',
		'step' 	=> '1'
	) ) ); ?>

	<?php woocommerce_wp_checkbox( array( 'id' => '_resume_featured', 'label' => __( 'Feature resumes?', 'wp-job-manager-wc-paid-listings' ), 'description' => __( 'Feature this resume - it will be styled differently and sticky.', 'wp-job-manager-wc-paid-listings' ), 'value' => get_post_meta( $post_id, '_resume_featured', true ) ) ); ?>

	<script type="text/javascript">
		jQuery(function(){
			jQuery('.pricing').addClass( 'show_if_resume_package' );
			jQuery('.show_if_subscription, .grouping').addClass( 'show_if_resume_package_subscription' );
			jQuery('#product-type').change();
		});
	</script>
</div>