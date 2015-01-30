<?php
/*
Plugin Name: WP Job Manager - Applications
Plugin URI: https://wpjobmanager.com/add-ons/applications/
Description: Lets candidates submit applications to jobs which are stored on the employers jobs page, rather than simply emailed. Works standalone with it's built in application form.
Version: 1.6.0
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 3.8
Tested up to: 4.1

	Copyright: 2014 Mike Jolley
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPJM_Updater' ) ) {
	include( 'includes/updater/class-wpjm-updater.php' );
}

/**
 * WP_Job_Manager_Applications class.
 */
class WP_Job_Manager_Applications extends WPJM_Updater {

	/**
	 * __construct function.
	 */
	public function __construct() {

		// Define constants
		define( 'JOB_MANAGER_APPLICATIONS_VERSION', '1.6.0' );
		define( 'JOB_MANAGER_APPLICATIONS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'JOB_MANAGER_APPLICATIONS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		// Includes
		include( 'includes/class-wp-job-manager-applications-post-types.php' );
		include( 'includes/class-wp-job-manager-applications-apply.php' );
		include( 'includes/class-wp-job-manager-applications-dashboard.php' );
		include( 'includes/class-wp-job-manager-applications-past.php' );
		include( 'includes/wp-job-manager-applications-functions.php' );

		// Init classes
		$this->post_types = new WP_Job_Manager_Applications_Post_Types();

		// Add actions
		add_action( 'admin_notices', array( $this, 'version_check' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 12 );
		add_action( 'plugins_loaded', array( $this, 'integration' ), 12 );
		add_action( 'admin_init', array( $this, 'load_admin' ), 12 );
		add_filter( 'job_manager_settings', array( $this, 'settings' ) );
		add_action( 'after_setup_theme', array( $this, 'template_functions' ) );
		add_action( 'admin_init', array( $this, 'updater' ) );

		// Activate
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		// Init updates
		$this->init_updates( __FILE__ );
	}

	/**
	 * Check JM version
	 */
	public function version_check() {
		if ( version_compare( '1.14.0', JOB_MANAGER_VERSION, '>' ) ) {
			?>
			<div class="error"><p><?php printf( __( 'Applications requies WP Job Manager 1.14.0 and above; you are using %s', 'wp-job-manager-applications' ), JOB_MANAGER_VERSION ); ?></p></div>
			<?php
		}
	}

	/**
	 * Load template functions
	 */
	public function template_functions() {
		include( 'includes/wp-job-manager-applications-template.php' );
	}

	/**
	 * Handle Updates
	 */
	public function updater() {
		if ( version_compare( JOB_MANAGER_APPLICATIONS_VERSION, get_option( 'wp_job_manager_applications_version' ), '>' ) ) {
			$this->install();
		}
	}

	/**
	 * Install
	 */
	public function install() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		if ( is_object( $wp_roles ) ) {
			$capabilities = $this->get_core_capabilities();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}
		}

		update_option( 'wp_job_manager_applications_version', JOB_MANAGER_APPLICATIONS_VERSION );
	}

	/**
	 * Get capabilities
	 *
	 * @return array
	 */
	public function get_core_capabilities() {
		$capabilities     = array();
		$capability_types = array( 'job_application' );

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms"
			);
		}

		return $capabilities;
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-applications' );
		load_textdomain( 'wp-job-manager-applications', WP_LANG_DIR . "/wp-job-manager-applications/wp-job-manager-applications-$locale.mo" );
		load_plugin_textdomain( 'wp-job-manager-applications', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Integrate with other plugins
	 */
	public function integration() {
		include_once( 'includes/class-wp-job-manager-applications-integration.php' );
	}

	/**
	 * Init the admin area
	 */
	public function load_admin() {
		include_once( 'includes/class-wp-job-manager-applications-admin.php' );
	}

	/**
	 * Add Settings
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = array() ) {
		$settings['job_applications'] = array(
			__( 'Job Application', 'wp-job-manager-applications' ),
			apply_filters(
				'wp_job_manager_applications_settings',
				array(
					array(
						'name' 		=> 'job_application_form_for_email_method',
						'std' 		=> '1',
						'label' 	=> __( 'Email Application method', 'wp-job-manager-applications' ),
						'cb_label' 	=> __( 'Use application form', 'wp-job-manager-applications' ),
						'desc'		=> __( 'Show application form for jobs with an email application method. Disable to use the default application functionality, or another form plugin.', 'wp-job-manager-applications' ),
						'type'      => 'checkbox'
					),
					array(
						'name' 		=> 'job_application_form_for_url_method',
						'std' 		=> '1',
						'label' 	=> __( 'Website URL Application method', 'wp-job-manager-applications' ),
						'cb_label' 	=> __( 'Use application form', 'wp-job-manager-applications' ),
						'desc'		=> __( 'Show application form for jobs with a website url application method. Disable to use the default application functionality, or another form plugin.', 'wp-job-manager-applications' ),
						'type'      => 'checkbox'
					),
					array(
						'name' 		=> 'job_application_form_require_login',
						'std' 		=> '0',
						'label' 	=> __( 'User restriction', 'wp-job-manager-applications' ),
						'cb_label' 	=> __( 'Only allow registered users to apply', 'wp-job-manager-applications' ),
						'desc'		=> __( 'If enabled, only logged in users can apply. Non-logged in users will see the contents of the <code>application-form-login.php</code> file instead of a form.', 'wp-job-manager-applications' ),
						'type'      => 'checkbox'
					),
					array(
						'name' 		=> 'job_application_delete_with_job',
						'std' 		=> '0',
						'label' 	=> __( 'Delete applications', 'wp-job-manager-applications' ),
						'cb_label' 	=> __( 'Delete applications when a job is deleted', 'wp-job-manager-applications' ),
						'desc'		=> __( 'If enabled, job applications will be deleted when the parent job listing is deleted. Otherwise they will be kept on file and visible in the backend.', 'wp-job-manager-applications' ),
						'type'      => 'checkbox'
					)
				)
			)
		);
		return $settings;
	}
}

$GLOBALS['job_manager_applications'] = new WP_Job_Manager_Applications();