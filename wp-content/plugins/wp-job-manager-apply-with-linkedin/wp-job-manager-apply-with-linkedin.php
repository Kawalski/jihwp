<?php
/*
Plugin Name: WP Job Manager - Apply with LinkedIn
Plugin URI: https://wpjobmanager.com/add-ons/apply-with-linkedin/
Description: Add an "Apply with LinkedIn" button to job listings which have an 'email' apply method. Requires API keys from LinkedIn (https://www.linkedin.com/secure/developer).
Version: 2.0.4
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
 * WP_Job_Manager_Apply_With_Linkedin class.
 */
class WP_Job_Manager_Apply_With_Linkedin extends WPJM_Updater {

	private $error   = "";
	private $message = "";

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Define constants
		define( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_VERSION', '2.0.4' );
		define( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		if ( defined( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_API_KEY' ) && defined( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_SECRET_KEY' ) ) {
			define( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_CONFIG_KEYS', true );
		} else {
			define( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_CONFIG_KEYS', false );
			define( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_SECRET_KEY', get_option( 'job_manager_linkedin_secret_key' ) );
			define( 'JOB_MANAGER_APPLY_WITH_LINKEDIN_API_KEY', get_option( 'job_manager_linkedin_api_key' ) );
		}

		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'handle_http_post' ), 0 );
		add_filter( 'job_manager_settings', array( $this, 'settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'job_application_start', array( $this, 'apply_button' ) );
		add_action( 'job_application_end', array( $this, 'apply_content' ) );
		add_action( 'wp_job_manager_apply_with_linkedin_application', array( $this, 'email_application' ), 10, 3 );

		// Init updates
		$this->init_updates( __FILE__ );
	}

	/**
	 * Enqueue scripts
	 */
	public function frontend_scripts() {
		wp_enqueue_style( 'wp-job-manager-apply-with-linkedin-styles', JOB_MANAGER_APPLY_WITH_LINKEDIN_PLUGIN_URL . '/assets/css/frontend.css' );
		wp_register_script( 'wp-job-manager-apply-with-linkedin-js', JOB_MANAGER_APPLY_WITH_LINKEDIN_PLUGIN_URL . '/assets/js/apply-with-linkedin.js', array( 'jquery' ), JOB_MANAGER_APPLY_WITH_LINKEDIN_VERSION, true );
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-apply-with-linkedin' );
		load_textdomain( 'wp-job-manager-apply-with-linkedin', WP_LANG_DIR . "/wp-job-manager-apply-with-linkedin/wp-job-manager-apply-with-linkedin-$locale.mo" );
		load_plugin_textdomain( 'wp-job-manager-apply-with-linkedin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add Settings
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = array() ) {
		$settings['linkedin'] = array(
			__( 'Apply with LinkedIn', 'wp-job-manager-apply-with-linkedin' ),
			apply_filters(
				'wp_job_manager_apply_with_linkedin_settings',
				array(
					'api_key' => array(
						'name' 		=> 'job_manager_linkedin_api_key',
						'std' 		=> '',
						'label' 	=> __( 'API Key', 'wp-job-manager-apply-with-linkedin' ),
						'desc'		=> __( 'Get your API key by creating a new application on https://www.linkedin.com/secure/developer', 'wp-job-manager-apply-with-linkedin' ),
						'type'      => 'input'
					),
					'secret_key' => array(
						'name' 		=> 'job_manager_linkedin_secret_key',
						'std' 		=> '',
						'label' 	=> __( 'Secret Key', 'wp-job-manager-apply-with-linkedin' ),
						'desc'		=> __( 'Get your secret key by creating a new application on https://www.linkedin.com/secure/developer', 'wp-job-manager-apply-with-linkedin' ),
						'type'      => 'input'
					),
					array(
						'name' 		=> 'job_manager_apply_with_linkedin_cover_letter',
						'std' 		=> 'optional',
						'label' 	=> __( 'Cover letter field', 'wp-job-manager-apply-with-linkedin' ),
						'desc'		=> '',
						'type'      => 'select',
						'options'   => array(
							'optional' => __( 'Optional', 'wp-job-manager-apply-with-linkedin' ),
							'required' => __( 'Required', 'wp-job-manager-apply-with-linkedin' ),
							'hidden'   => __( 'Hidden', 'wp-job-manager-apply-with-linkedin' ),
						)
					),
				)
			)
		);

		if ( JOB_MANAGER_APPLY_WITH_LINKEDIN_CONFIG_KEYS ) {
			unset( $settings['linkedin'][1]['api_key'] );
			unset( $settings['linkedin'][1]['secret_key'] );
		}

		return $settings;
	}

	/**
	 * Apply button
	 */
	public function apply_button( $apply ) {
		global $post;

		// For email based applications
		if ( isset( $apply->raw_email ) ) {
			$email = apply_filters( 'wp_job_manager_apply_with_linkedin_email', $apply->raw_email, $post, $apply );
		} else {
			$email = apply_filters( 'wp_job_manager_apply_with_linkedin_email', '', $post, $apply );
		}

		// Post application to URL (JSON)
		if ( apply_filters( 'wp_job_manager_apply_with_linkedin_enable_http_post', false, $post, $apply ) ) {
			$url   = add_query_arg( array( 'job_id' => $post->ID, 'apply_with_linkedin_application' => 1 ), home_url( '/' ) );
		} else {
			$url   = '';
		}

		if ( empty( $email ) && empty( $url ) ) {
			return;
		}

		// Enqueue script
		wp_enqueue_script( 'wp-job-manager-apply-with-linkedin-js' );

		// Output button template
		get_job_manager_template( 'apply-with-linkedin.php', array(
			'company_name' => get_the_company_name(),
			'job_title'    => $post->post_title,
			'cover_letter' => get_option( 'job_manager_apply_with_linkedin_cover_letter', 'optional' ),
			'job_id'       => $post->ID
		), 'wp-job-manager-apply-with-linkedin', JOB_MANAGER_APPLY_WITH_LINKEDIN_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Apply content
	 */
	public function apply_content( $apply ) {
		global $post;

		// For email based applications
		if ( isset( $apply->raw_email ) ) {
			$email = apply_filters( 'wp_job_manager_apply_with_linkedin_email', $apply->raw_email, $post, $apply );
		} else {
			$email = apply_filters( 'wp_job_manager_apply_with_linkedin_email', '', $post, $apply );
		}

		// Post application to URL (JSON)
		if ( apply_filters( 'wp_job_manager_apply_with_linkedin_enable_http_post', false, $post, $apply ) ) {
			$url   = add_query_arg( array( 'job_id' => $post->ID, 'apply_with_linkedin_application' => 1 ), home_url( '/' ) );
		} else {
			$url   = '';
		}

		if ( empty( $email ) && empty( $url ) ) {
			return;
		}

		// Output button template
		get_job_manager_template( 'apply-with-linkedin-form.php', array(
			'company_name' => get_the_company_name(),
			'job_title'    => $post->post_title,
			'cover_letter' => get_option( 'job_manager_apply_with_linkedin_cover_letter', 'optional' ),
			'job_id'       => $post->ID
		), 'wp-job-manager-apply-with-linkedin', JOB_MANAGER_APPLY_WITH_LINKEDIN_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Handle a posted application - fire off an action to be handled elsewhere.
	 */
	public function handle_http_post() {
		if ( ! empty( $_POST['apply-with-linkedin-submit'] ) ) {
			$cover_letter = isset( $_POST['apply-with-linkedin-cover-letter'] ) ? wp_kses_post( stripslashes( $_POST['apply-with-linkedin-cover-letter'] ) ) : '';
			$profile_data = json_decode( stripslashes( $_POST['apply-with-linkedin-profile-data'] ) );
			$job_id       = absint( $_POST['apply-with-linkedin-job-id'] );

			if ( $job_id && 'job_listing' === get_post_type( $job_id ) && $profile_data ) {
				do_action( 'wp_job_manager_apply_with_linkedin_application', $job_id, $profile_data, $cover_letter );
				add_action( 'job_content_start', array( $this, 'apply_result' ) );
			}
		}
	}

	/**
	 * Email the employer a new linkedin Application
	 */
	public function email_application( $job_id, $profile_data, $cover_letter ) {
		$apply = get_the_job_application_method( $job_id );

		// For email based applications
		if ( isset( $apply->raw_email ) ) {
			$email = apply_filters( 'wp_job_manager_apply_with_linkedin_email', $apply->raw_email, $job_id, $apply );
		} else {
			$email = apply_filters( 'wp_job_manager_apply_with_linkedin_email', '', $job_id, $apply );
		}

		if ( is_email( $email ) ) {
			$subject = sprintf( _x( '%s - %s has submitted an application', 'Job - Name has submitted an application', 'wp-job-manager-apply-with-linkedin' ), get_the_title( $job_id ), $profile_data->formattedName );

			ob_start();

			get_job_manager_template( 'apply-with-linkedin-email.php', array(
				'company_name' => get_the_company_name(  $job_id ),
				'job_title'    => get_the_title( $job_id ),
				'cover_letter' => $cover_letter,
				'job_id'       => $job_id,
				'profile_data' => $profile_data
			), 'wp-job-manager-apply-with-linkedin', JOB_MANAGER_APPLY_WITH_LINKEDIN_PLUGIN_DIR . '/templates/' );

			$content   = ob_get_clean();
			$headers   = array();
			$headers[] = "Reply-To: " . esc_attr( $profile_data->formattedName ) . " <" . esc_attr( $profile_data->emailAddress ) . ">";
			$headers[] = "Content-type: text/html";

			wp_mail( $email, $subject, $content, $headers );
		}

		$this->message = __( 'Your job application has been submitted successfully', 'wp-job-manager-applications' );
	}

	/**
	 * Show results - errors and messages
	 */
	public function apply_result() {
		if ( $this->message ) {
			echo '<p class="job-manager-message">' . esc_html( $this->message ) . '</p>';
		} elseif ( $this->error ) {
			echo '<p class="job-manager-error">' . esc_html( $this->error ) . '</p>';
		}
	}
}
$GLOBALS['wp-job-manager-apply-with-linkedin'] = new WP_Job_Manager_Apply_With_Linkedin();