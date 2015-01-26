<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_Install
 */
class WP_Resume_Manager_Install {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->init_user_roles();
		$this->create_files();
		$this->cron();

		// Meta update
		if ( version_compare( get_option( 'wp_resume_manager_version' ), '1.6.1', '<' ) ) {
			global $wpdb;

			$wpdb->query( "INSERT INTO {$wpdb->postmeta}( post_id, meta_key, meta_value ) SELECT DISTINCT ID AS post_id, '_featured' AS meta_key, 0 AS meta_value FROM {$wpdb->posts} WHERE post_type = 'resume' AND post_status = 'publish';" );
		}

		// Update legacy options
		if ( false === get_option( 'resume_manager_submit_page_id', false ) && get_option( 'resume_manager_submit_page_slug' ) ) {
			$page    = get_page_by_path( get_option( 'resume_manager_submit_page_slug' ) );
			$page_id = $page ? $page->ID : '';
			update_option( 'resume_manager_submit_page_id', $page_id );
		}

		update_option( 'wp_resume_manager_version', RESUME_MANAGER_VERSION );
	}

	/**
	 * Init user roles
	 *
	 * @access public
	 * @return void
	 */
	public function init_user_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'administrator', 'manage_resumes' );

			// Customer role
			add_role( 'candidate', __( 'Candidate', 'wp-job-manager-resumes' ), array(
			    'read' 						=> true,
			    'edit_posts' 				=> false,
			    'delete_posts' 				=> false
			) );
		}
	}

	/**
	 * Create files/directories
	 */
	private function create_files() {
		// Install files and folders for uploading files and prevent hotlinking
		$upload_dir =  wp_upload_dir();

		// Remove old htaccess
		@unlink( $upload_dir['basedir'] . '/resumes/.htaccess' );

		$files = array(
			array(
				'base' 		=> $upload_dir['basedir'] . '/resumes/resume_files',
				'file' 		=> '.htaccess',
				'content' 	=> 'deny from all'
			),
			array(
				'base' 		=> $upload_dir['basedir'] . '/resumes/resume_files',
				'file' 		=> 'index.html',
				'content' 	=> ''
			)
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

	/**
	 * Setup cron jobs
	 */
	public function cron() {
		wp_clear_scheduled_hook( 'resume_manager_check_for_expired_resumes' );
		wp_schedule_event( time(), 'hourly', 'resume_manager_check_for_expired_resumes' );
	}
}

new WP_Resume_Manager_Install();