<?php
/**
 * WP_Job_Manager_Applications_Post_Types class.
 */
class WP_Job_Manager_Applications_Post_Types {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'single_job_listing_meta_after', array( $this, 'already_applied_message' ) );
		add_action( 'init', array( $this, 'register_post_types' ), 20 );
		if ( get_option( 'job_application_delete_with_job', 0 ) ) {
			add_action( 'delete_post', array( $this, 'delete_post' ) );
		}
	}

	/**
	 * Show message if already applied
	 */
	public function already_applied_message() {
		global $post;

		if ( is_user_logged_in() && user_has_applied_for_job( get_current_user_id(), $post->ID ) ) {
			 get_job_manager_template( 'applied-notice.php', array(), 'wp-job-manager-applications', JOB_MANAGER_APPLICATIONS_PLUGIN_DIR . '/templates/' );
		}
	}

	/**
	 * register_post_types function.
	 */
	public function register_post_types() {
		if ( post_type_exists( "job_application" ) ) {
			return;
		}

		$plural   = __( 'Job Applications', 'wp-job-manager-applications' );
		$singular = __( 'Application', 'wp-job-manager-applications' );

		register_post_type( "job_application",
			apply_filters( "register_post_type_job_application", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $plural,
					'all_items'             => sprintf( __( 'All %s', 'wp-job-manager-applications' ), $plural ),
					'add_new' 				=> __( 'Add New', 'wp-job-manager-applications' ),
					'add_new_item' 			=> sprintf( __( 'Add %s', 'wp-job-manager-applications' ), $singular ),
					'edit' 					=> __( 'Edit', 'wp-job-manager-applications' ),
					'edit_item' 			=> sprintf( __( 'Edit %s', 'wp-job-manager-applications' ), $singular ),
					'new_item' 				=> sprintf( __( 'New %s', 'wp-job-manager-applications' ), $singular ),
					'view' 					=> sprintf( __( 'View %s', 'wp-job-manager-applications' ), $singular ),
					'view_item' 			=> sprintf( __( 'View %s', 'wp-job-manager-applications' ), $singular ),
					'search_items' 			=> sprintf( __( 'Search %s', 'wp-job-manager-applications' ), $plural ),
					'not_found' 			=> sprintf( __( 'No %s found', 'wp-job-manager-applications' ), $plural ),
					'not_found_in_trash' 	=> sprintf( __( 'No %s found in trash', 'wp-job-manager-applications' ), $plural ),
					'parent' 				=> sprintf( __( 'Parent %s', 'wp-job-manager-applications' ), $singular )
				),
				'description'         => __( 'This is where you can edit and view applications.', 'wp-job-manager-applications' ),
				'public'              => false,
				'show_ui'             => true,
				'capability_type'     => 'job_application',
				'map_meta_cap'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'custom-fields', 'editor' ),
				'has_archive'         => false,
				'show_in_nav_menus'   => false
			) )
		);

		/**
		 * Post status
		 */
		register_post_status( 'new', array(
			'label'                     => _x( 'New', 'job_application', 'wp-job-manager-applications' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'New <span class="count">(%s)</span>', 'New <span class="count">(%s)</span>', 'wp-job-manager' ),
		) );
		register_post_status( 'interviewed', array(
			'label'                     => _x( 'Interviewed', 'job_application', 'wp-job-manager-applications' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Interviewed <span class="count">(%s)</span>', 'Interviewed <span class="count">(%s)</span>', 'wp-job-manager' ),
		) );
		register_post_status( 'offer', array(
			'label'                     => _x( 'Offer extended', 'job_application', 'wp-job-manager-applications' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Offer extended <span class="count">(%s)</span>', 'Offer extended <span class="count">(%s)</span>', 'wp-job-manager' ),
		) );
		register_post_status( 'hired', array(
			'label'                     => _x( 'Hired', 'job_application', 'wp-job-manager-applications' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Hired <span class="count">(%s)</span>', 'Hired <span class="count">(%s)</span>', 'wp-job-manager' ),
		) );
		register_post_status( 'archived', array(
			'label'                     => _x( 'Archived', 'job_application', 'wp-job-manager-applications' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>', 'wp-job-manager' ),
		) );
	}

	/**
	 * Delete applications when deleting a job
	 */
	public function delete_post( $id ) {
		global $wpdb;

		if ( $id > 0 ) {

			$post_type = get_post_type( $id );

			if ( 'job_listing' === $post_type ) {
				$applications = get_children( 'post_parent=' . $id . '&post_type=job_application' );

				if ( $applications ) {
					foreach ( $applications as $application ) {
						wp_delete_post( $application->ID, true );
					}
				}
			}
		}
	}
}