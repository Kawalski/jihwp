<?php
/**
 * WP_Job_Manager_Alerts_Post_Types class.
 */
class WP_Job_Manager_Alerts_Post_Types {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ), 20 );
	}

	/**
	 * register_post_types function.
	 */
	public function register_post_types() {
		if ( post_type_exists( "job_alert" ) ) {
			return;
		}

		register_post_type( "job_alert",
			apply_filters( "register_post_type_job_alert", array(
				'public'              => false,
				'show_ui'             => false,
				'capability_type'     => 'post',
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false,
				'has_archive'         => false,
				'show_in_nav_menus'   => false
			) )
		);

		if ( taxonomy_exists( 'job_listing_category' ) ) {
			register_taxonomy_for_object_type( 'job_listing_category', 'job_alert' );
		}

		register_taxonomy_for_object_type( 'job_listing_type', 'job_alert' );
	}
}