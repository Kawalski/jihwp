<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_Apply class.
 */
class WP_Resume_Manager_Apply {

	private $error   = "";
	private $message = "";

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp', array( $this, 'apply_with_resume_handler' ) );
		add_action( 'submit_resume_form_start', array( $this, 'resume_form_intro' ) );
		add_action( 'job_manager_application_details_email', array( $this, 'apply_with_resume' ), 20 );

		if ( class_exists( 'WP_Job_Manager_Applications' ) ) {
			add_action( 'job_manager_application_details_url', array( $this, 'apply_with_resume' ), 20 );
		}
	}

	/**
	 * Init
	 */
	public function init() {
		if ( get_option( 'resume_manager_force_application' ) ) {
			global $job_manager;
			remove_action( 'job_manager_application_details_email', array( $job_manager->post_types, 'application_details_email' ) );

			if ( class_exists( 'WP_Job_Manager_Applications' ) ) {
				remove_action( 'job_manager_application_details_url', array( $job_manager->post_types, 'application_details_url' ) );
			}
		}
	}

	/**
	 * Resume form intro
	 */
	public function resume_form_intro() {
		if ( ! empty( $_POST['wp_job_manager_resumes_apply_with_resume_create'] ) ) {
			$job_id = absint( $_POST['job_id'] );

			if ( get_post_type( $job_id ) !== 'job_listing' ) {
				return;
			}

			echo '<p class="applying_for">' . sprintf( __( 'Submit your resume below to apply for the job "%s".', 'wp-job-manager-resumes' ), '<a href="' . get_permalink( $job_id ) . '">' . get_the_title( $job_id ) . '</a>' ) .'</p>';
		}
	}

	/**
	 * Allow users to apply to a job with a resume
	 */
	public function apply_with_resume() {
		if ( is_user_logged_in() ) {
			$args = apply_filters( 'resume_manager_get_application_form_resumes_args', array(
				'post_type'           => 'resume',
				'post_status'         => array( 'publish', 'pending' ),
				'ignore_sticky_posts' => 1,
				'posts_per_page'      => -1,
				'orderby'             => 'date',
				'order'               => 'desc',
				'author'              => get_current_user_id()
			) );

			$resumes = get_posts( $args );
		} else {
			$resumes = array();
		}

		get_job_manager_template( 'apply-with-resume.php', array( 'resumes' => $resumes ), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Send the application email if posted
	 */
	public function apply_with_resume_handler() {
		if ( ! empty( $_POST['wp_job_manager_resumes_apply_with_resume'] ) ) {
			add_action( 'job_content_start', array( $this, 'apply_with_resume_result' ) );

			$resume_id           = absint( $_POST['resume_id'] );
			$job_id              = absint( $_POST['job_id'] );
			$application_message = str_replace( '[nl]', "\n", sanitize_text_field( str_replace( "\n", '[nl]', strip_tags( stripslashes( $_POST['application_message'] ) ) ) ) );

			if ( empty( $resume_id ) ) {
				$this->error = __( 'Please choose a resume to apply with', 'wp-job-manager-resumes' );
				return;
			}

			if ( empty( $job_id ) ) {
				$this->error = __( 'This job cannot be applied for using a resume', 'wp-job-manager-resumes' );
				return;
			}

			if ( empty( $application_message ) ) {
				$this->error = __( 'Please enter a message to include with your application', 'wp-job-manager-resumes' );
				return;
			}

			$method = get_the_job_application_method( $job_id );

			if ( "email" !== $method->type && ! class_exists( 'WP_Job_Manager_Applications' ) ) {
				$this->error = __( 'This job cannot be applied for using a resume', 'wp-job-manager-resumes' );
				return;
			}

			if ( $this->send_application( $job_id, $resume_id, $application_message ) ) {
				$this->message = __( 'Your application has been sent successfully', 'wp-job-manager-resumes' );
			} else {
				$this->error = __( 'Error sending application', 'wp-job-manager-resumes' );
			}
		}
	}

	/**
	 * Sent the application email
	 */
	public static function send_application( $job_id, $resume_id, $application_message ) {
		$user            = wp_get_current_user();
		$resume_link     = get_resume_share_link( $resume_id );
		$candidate_email = get_post_meta( $resume_id, '_candidate_email', true );
		$method          = get_the_job_application_method( $job_id );
		$sent            = false;

		if ( empty( $candidate_email ) ) {
			$candidate_email = $user->user_email;
		}

		$message     = apply_filters( 'apply_with_resume_email_message', array(
			'greeting'      => __( 'Hello', 'wp-job-manager-resumes' ),
			'position'      => sprintf( "\n\n" . __( 'A candidate has applied online for the position "%s".', 'wp-job-manager-resumes' ), get_the_title( $job_id ) ),
			'start_message' => "\n\n-----------\n\n",
			'message'       => $application_message,
			'end_message'   => "\n\n-----------\n\n",
			'view_resume'   => sprintf( __( 'You can view their online resume here: %s.', 'wp-job-manager-resumes' ), $resume_link ),
			'contact'       => "\n" . sprintf( __( 'Or you can contact them directly at: %s.', 'wp-job-manager-resumes' ), $candidate_email ),
		), get_current_user_id(), $job_id, $resume_id, $application_message );

		if ( ! empty( $method->raw_email ) ) {
			add_filter( 'wp_mail_from', array( __CLASS__, 'get_from_address' ) );
			add_filter( 'wp_mail_from_name', array( __CLASS__, 'get_from_name' ) );

			$sent = wp_mail( $method->raw_email, $method->subject, implode( '', $message ) );

			remove_filter( 'wp_mail_from', array( __CLASS__, 'get_from_address' ) );
			remove_filter( 'wp_mail_from_name', array( __CLASS__, 'get_from_name' ) );
		}

		do_action( 'applied_with_resume', get_current_user_id(), $job_id, $resume_id, $application_message, $sent );

		return ( $sent || class_exists( 'WP_Job_Manager_Applications' ) );
	}

	/**
	 * Get from name for email.
	 *
	 * @access public
	 * @return string
	 */
	public static function get_from_name() {
		return wp_specialchars_decode( esc_html( get_bloginfo( 'name' ) ), ENT_QUOTES );
	}

	/**
	 * Get from email address.
	 *
	 * @access public
	 * @return string
	 */
	public static function get_from_address() {
		$site_url  = parse_url( site_url() );
		$nice_host = str_replace( 'www.', '', $site_url['host'] );
		return sanitize_email( 'noreply@' . $nice_host );
	}

	/**
	 * Show results - errors and messages
	 */
	public function apply_with_resume_result() {
		if ( $this->message ) {
			echo '<p class="job-manager-message">' . esc_html( $this->message ) . '</p>';
		} elseif ( $this->error ) {
			echo '<p class="job-manager-error">' . esc_html( $this->error ) . '</p>';
		}
	}
}