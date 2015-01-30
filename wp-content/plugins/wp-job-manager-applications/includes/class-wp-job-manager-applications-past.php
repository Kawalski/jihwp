<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Job_Manager_Applications_Past class.
 */
class WP_Job_Manager_Applications_Past {

	/**
	 * Constructor
	 */
	function __construct() {
 		add_shortcode( 'past_applications', array( $this, 'past_applications' ) );
    }

    /**
     * Past Applications
     */
    public function past_applications() {
    	// If user is not logged in, abort
    	if ( ! is_user_logged_in() ) {
			do_action( 'job_manager_job_applications_past_logged_out' );
			return;
		}

    	$args = apply_filters( 'job_manager_job_applications_past_args', array(
			'post_type'           => 'job_application',
			'post_status'         => array( 'publish', 'new', 'interviewed', 'offer', 'hired', 'archived' ),
			'posts_per_page'      => -1,
			'ignore_sticky_posts' => 1,
			'meta_key'            => '_candidate_user_id',
			'meta_value'          => get_current_user_id(),
		) );

		$applications = new WP_Query( $args );

		if ( $applications->have_posts() ) {
			get_job_manager_template( 'past-applications.php', array( 'applications' => $applications->query( $args ), ), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
		} else {
			get_job_manager_template( 'past-applications-none.php', array(), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
		}
    }

}

new WP_Job_Manager_Applications_Past();