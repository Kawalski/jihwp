<?php
/*
Plugin Name: WP Job Manager - Apply with XING
Plugin URI: https://wpjobmanager.com/add-ons/apply-with-xing/
Description: Add an "Apply with XING" button to job listings which have an 'email' apply method. Requires API keys from XING (https://dev.xing.com/)
Version: 1.0.0
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 3.8
Tested up to: 4.0

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
 * WP_Job_Manager_Apply_With_XING class.
 */
class WP_Job_Manager_Apply_With_XING extends WPJM_Updater {

	private $error   = "";
	private $message = "";

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Define constants
		define( 'JOB_MANAGER_APPLY_WITH_XING_VERSION', '1.0.0' );
		define( 'JOB_MANAGER_APPLY_WITH_XING_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'JOB_MANAGER_APPLY_WITH_XING_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		if ( defined( 'JOB_MANAGER_APPLY_WITH_XING_CONSUMER_KEY' ) && defined( 'JOB_MANAGER_APPLY_WITH_XING_SECRET_KEY' ) ) {
			define( 'JOB_MANAGER_APPLY_WITH_XING_CONFIG_KEYS', true );
		} else {
			define( 'JOB_MANAGER_APPLY_WITH_XING_CONFIG_KEYS', false );
			define( 'JOB_MANAGER_APPLY_WITH_XING_SECRET_KEY', get_option( 'job_manager_xing_api_secret_key' ) );
			define( 'JOB_MANAGER_APPLY_WITH_XING_CONSUMER_KEY', get_option( 'job_manager_xing_api_key' ) );
		}

		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'handle_http_post' ), 0 );
		add_action( 'init', array( $this, 'handle_oauth' ) );
		add_filter( 'job_manager_settings', array( $this, 'settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'job_application_start', array( $this, 'apply_button' ) );
		add_action( 'job_application_end', array( $this, 'apply_content' ) );
		add_action( 'wp_job_manager_apply_with_xing_application', array( $this, 'email_application' ), 10, 3 );

		// Init updates
		$this->init_updates( __FILE__ );
	}

	/**
	 * Enqueue scripts
	 */
	public function frontend_scripts() {
		wp_enqueue_style( 'wp-job-manager-apply-with-xing-styles', JOB_MANAGER_APPLY_WITH_XING_PLUGIN_URL . '/assets/css/frontend.css' );
		wp_register_script( 'hello', JOB_MANAGER_APPLY_WITH_XING_PLUGIN_URL . '/assets/js/hello.js', array(), "1.3.2", true );
		wp_register_script( 'hello-then', JOB_MANAGER_APPLY_WITH_XING_PLUGIN_URL . '/assets/js/hello.then.js', array( 'hello' ), "1.3.2", true );
		wp_register_script( 'hello-xing', JOB_MANAGER_APPLY_WITH_XING_PLUGIN_URL . '/assets/js/hello-xing.js', array( 'hello' ), JOB_MANAGER_APPLY_WITH_XING_VERSION, true );
		wp_register_script( 'wp-job-manager-apply-with-xing-js', JOB_MANAGER_APPLY_WITH_XING_PLUGIN_URL . '/assets/js/apply-with-xing.js', array( 'jquery', 'hello-xing', 'hello-then' ), JOB_MANAGER_APPLY_WITH_XING_VERSION, true );
		wp_localize_script( 'wp-job-manager-apply-with-xing-js', 'apply_with_xing', array(
			'consumer_key' => JOB_MANAGER_APPLY_WITH_XING_CONSUMER_KEY,
			'oauth_proxy'  => add_query_arg( 'network', 'xing', site_url( '/' ) ),
			'redirect_uri' => add_query_arg( 'network', 'xing', site_url( '/' ) ),
		) );
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-apply-with-xing' );
		load_textdomain( 'wp-job-manager-apply-with-xing', WP_LANG_DIR . "/wp-job-manager-apply-with-xing/wp-job-manager-apply-with-xing-$locale.mo" );
		load_plugin_textdomain( 'wp-job-manager-apply-with-xing', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add Settings
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = array() ) {
		$settings['xing'] = array(
			__( 'Apply with XING', 'wp-job-manager-apply-with-xing' ),
			apply_filters(
				'wp_job_manager_apply_with_xing_settings',
				array(
					'api_key' => array(
						'name' 		=> 'job_manager_xing_api_key',
						'std' 		=> '',
						'label' 	=> __( 'Consumer Key', 'wp-job-manager-apply-with-xing' ),
						'desc'		=> __( 'Get your consumer key by creating a new application on https://dev.xing.com/applications/dashboard', 'wp-job-manager-apply-with-xing' ),
						'type'      => 'input'
					),
					'secret_key' => array(
						'name' 		=> 'job_manager_xing_api_secret_key',
						'std' 		=> '',
						'label' 	=> __( 'Consumer Secret', 'wp-job-manager-apply-with-xing' ),
						'desc'		=> __( 'Get your consumer secret by creating a new application on https://dev.xing.com/applications/dashboard', 'wp-job-manager-apply-with-xing' ),
						'type'      => 'input'
					),
					array(
						'name' 		=> 'job_manager_apply_with_xing_cover_letter',
						'std' 		=> 'optional',
						'label' 	=> __( 'Cover letter field', 'wp-job-manager-apply-with-xing' ),
						'desc'		=> '',
						'type'      => 'select',
						'options'   => array(
							'optional' => __( 'Optional', 'wp-job-manager-apply-with-xing' ),
							'required' => __( 'Required', 'wp-job-manager-apply-with-xing' ),
							'hidden'   => __( 'Hidden', 'wp-job-manager-apply-with-xing' ),
						)
					),
				)
			)
		);

		if ( JOB_MANAGER_APPLY_WITH_XING_CONFIG_KEYS ) {
			unset( $settings['xing'][1]['api_key'] );
			unset( $settings['xing'][1]['secret_key'] );
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
			$email = apply_filters( 'wp_job_manager_apply_with_xing_email', $apply->raw_email, $post, $apply );
		} else {
			$email = apply_filters( 'wp_job_manager_apply_with_xing_email', '', $post, $apply );
		}

		// Post application to URL (JSON)
		if ( apply_filters( 'wp_job_manager_apply_with_xing_enable_http_post', false, $post, $apply ) ) {
			$url   = add_query_arg( array( 'job_id' => $post->ID, 'apply_with_xing_application' => 1 ), home_url( '/' ) );
		} else {
			$url   = '';
		}

		if ( empty( $email ) && empty( $url ) ) {
			return;
		}

		// Enqueue script
		wp_enqueue_script( 'wp-job-manager-apply-with-xing-js' );

		// Output button template
		get_job_manager_template( 'apply-with-xing.php', array(
			'company_name' => get_the_company_name(),
			'job_title'    => $post->post_title,
			'cover_letter' => get_option( 'job_manager_apply_with_xing_cover_letter', 'optional' ),
			'job_id'       => $post->ID
		), 'wp-job-manager-apply-with-xing', JOB_MANAGER_APPLY_WITH_XING_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Apply content
	 */
	public function apply_content( $apply ) {
		global $post;

		// For email based applications
		if ( isset( $apply->raw_email ) ) {
			$email = apply_filters( 'wp_job_manager_apply_with_xing_email', $apply->raw_email, $post, $apply );
		} else {
			$email = apply_filters( 'wp_job_manager_apply_with_xing_email', '', $post, $apply );
		}

		// Post application to URL (JSON)
		if ( apply_filters( 'wp_job_manager_apply_with_xing_enable_http_post', false, $post, $apply ) ) {
			$url   = add_query_arg( array( 'job_id' => $post->ID, 'apply_with_xing_application' => 1 ), home_url( '/' ) );
		} else {
			$url   = '';
		}

		if ( empty( $email ) && empty( $url ) ) {
			return;
		}

		// Output button template
		get_job_manager_template( 'apply-with-xing-form.php', array(
			'company_name' => get_the_company_name(),
			'job_title'    => $post->post_title,
			'cover_letter' => get_option( 'job_manager_apply_with_xing_cover_letter', 'optional' ),
			'job_id'       => $post->ID
		), 'wp-job-manager-apply-with-xing', JOB_MANAGER_APPLY_WITH_XING_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Handle a posted application - fire off an action to be handled elsewhere.
	 */
	public function handle_http_post() {
		if ( ! empty( $_POST['apply-with-xing-submit'] ) ) {
			$cover_letter = isset( $_POST['apply-with-xing-cover-letter'] ) ? wp_kses_post( stripslashes( $_POST['apply-with-xing-cover-letter'] ) ) : '';
			$profile_data = json_decode( stripslashes( $_POST['apply-with-xing-profile-data'] ) );
			$job_id       = absint( $_POST['apply-with-xing-job-id'] );

			if ( $job_id && 'job_listing' === get_post_type( $job_id ) && $profile_data ) {
				do_action( 'wp_job_manager_apply_with_xing_application', $job_id, $profile_data, $cover_letter );
				add_action( 'job_content_start', array( $this, 'apply_result' ) );
			}
		}
	}

	/**
	 * Email the employer a new xing Application
	 */
	public function email_application( $job_id, $profile_data, $cover_letter ) {
		$apply = get_the_job_application_method( $job_id );

		// For email based applications
		if ( isset( $apply->raw_email ) ) {
			$email = apply_filters( 'wp_job_manager_apply_with_xing_email', $apply->raw_email, $job_id, $apply );
		} else {
			$email = apply_filters( 'wp_job_manager_apply_with_xing_email', '', $job_id, $apply );
		}

		if ( is_email( $email ) ) {
			$subject = sprintf( _x( '%s - %s has submitted an application', 'Job - Name has submitted an application', 'wp-job-manager-apply-with-xing' ), get_the_title( $job_id ), $profile_data->display_name );

			ob_start();

			get_job_manager_template( 'apply-with-xing-email.php', array(
				'company_name' => get_the_company_name(  $job_id ),
				'job_title'    => get_the_title( $job_id ),
				'cover_letter' => $cover_letter,
				'job_id'       => $job_id,
				'profile_data' => $profile_data
			), 'wp-job-manager-apply-with-xing', JOB_MANAGER_APPLY_WITH_XING_PLUGIN_DIR . '/templates/' );

			$content   = ob_get_clean();
			$headers   = array();
			$headers[] = "Reply-To: " . esc_attr( $profile_data->display_name ) . " <" . esc_attr( $profile_data->active_email ) . ">";
			$headers[] = "Content-type: text/html";

			wp_mail( $email, $subject, $content, $headers );
		}

		$this->message = __( 'Your job application has been submitted successfully', 'wp-job-manager-apply-with-xing' );
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

	/**
	 * Handle oauth requests
	 */
	public function handle_oauth() {
		if ( ! empty( $_GET['network'] ) && 'xing' === $_GET['network'] ) {

			// Get access token from oauth token
			if ( ! empty( $_GET['oauth_token'] ) ) {
				$token_secret = get_transient( 'token_secret_' . $_GET['oauth_token'] );
				$state        = get_transient( 'token_state_' . $_GET['oauth_token'] );

				if ( ! empty( $token_secret ) && ! empty( $state ) ) {
					$response     = wp_remote_get( "https://api.xing.com/v1/access_token", array(
						'body' => array(
							'oauth_token'            => $_GET['oauth_token'],
							'oauth_verifier'         => $_GET['oauth_verifier'],
							'oauth_signature_method' => 'PLAINTEXT',
							'oauth_consumer_key'     => JOB_MANAGER_APPLY_WITH_XING_CONSUMER_KEY,
							'oauth_signature'        => JOB_MANAGER_APPLY_WITH_XING_SECRET_KEY . '&' . $token_secret
						)
					) );

					if ( wp_remote_retrieve_response_code( $response ) > 201 ) {
						_e( 'Error: Unable to obtain access token. Please close this window and try again.', 'wp-job-manager-apply-with-xing' );
						exit;
					}

					parse_str( $response['body'], $args );

					set_transient( 'token_secret_' . $args['oauth_token'], $args['oauth_token_secret'], 60 * 60 * 24 );

					wp_redirect( site_url( '/?oauth-redirect=1' ) . '#state=' . urlencode( $state ) . '&access_token=' . sanitize_text_field( $args['oauth_token'] ) . '&expires=' . strtotime( '+1 hour' ) );
					exit;
				} else {
					_e( 'Error: Unable to obtain access token. Please close this window and try again.', 'wp-job-manager-apply-with-xing' );
					exit;
				}
			}

			// Get request
			elseif ( ! empty( $_GET['access_token'] ) ) {
				$token_secret = get_transient( 'token_secret_' . $_GET['access_token'] );
				$response     = wp_remote_get( urldecode( $_GET['path'] ), array(
					'body' => array(
						'oauth_token'            => $_GET['access_token'],
						'oauth_signature_method' => 'PLAINTEXT',
						'oauth_consumer_key'     => JOB_MANAGER_APPLY_WITH_XING_CONSUMER_KEY,
						'oauth_signature'        => JOB_MANAGER_APPLY_WITH_XING_SECRET_KEY . '&' . $token_secret
					)
				) );

				header('content-type: application/json; charset=utf-8');

				if ( wp_remote_retrieve_response_code( $response ) > 201 ) {
					echo json_encode( array(
						'error' => 'Could not get access_token - ' . wp_remote_retrieve_response_code( $response )
					) );
				} else {
					echo $response['body'];
				}

				exit;
			}

			// Get ouath token
			elseif ( ! empty( $_GET['response_type'] ) && 'token' === $_GET['response_type'] ) {
				$response = wp_remote_get( "https://api.xing.com/v1/request_token", array(
					'body' => array(
						'oauth_callback'         => sanitize_text_field( $_GET['redirect_uri'] ),
						'oauth_version'          => '1.0',
						'oauth_signature_method' => 'PLAINTEXT',
						'oauth_consumer_key'     => JOB_MANAGER_APPLY_WITH_XING_CONSUMER_KEY,
						'oauth_signature'        => JOB_MANAGER_APPLY_WITH_XING_SECRET_KEY . '&'
					)
				) );

				if ( wp_remote_retrieve_response_code( $response ) > 201 ) {
					_e( 'Error: Unable to obtain access token. Please close this window and try again.', 'wp-job-manager-apply-with-xing' );
					exit;
				}

				parse_str( $response['body'], $args );

				set_transient( 'token_secret_' . $args['oauth_token'], $args['oauth_token_secret'], 60 * 60 * 24 );
				set_transient( 'token_state_' . $args['oauth_token'], stripslashes( $_GET['state'] ), 60 * 60 * 24 );

				wp_redirect( add_query_arg( array( 'oauth_token' => $args['oauth_token'] ), "https://api.xing.com/v1/authorize" ) );
				exit;
			}

			$this->handle_oauth_redirect();

		} elseif ( ! empty( $_GET['oauth-redirect'] ) ) {
			$this->handle_oauth_redirect();
		}
	}

	/**
	 * Handle oauth requests
	 */
	public function handle_oauth_redirect() {
		?>
		<!DOCTYPE html>
		<html id="xing-redirect">
			<head>
				<title><?php _e( 'Redirecting...', 'wp-job-manager-apply-with-xing' ); ?></title>
				<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
				<link href="<?php echo JOB_MANAGER_APPLY_WITH_XING_PLUGIN_URL . '/assets/css/frontend.css'; ?>" rel="stylesheet" />
			</head>
			<body>
			<div class="loading"><span>&bull;</span><span>&bull;</span><span>&bull;</span></div>
			<p><?php _e( 'Close this window to continue.', 'wp-job-manager-apply-with-xing' ); ?></p>
			<script type="text/javascript" src="<?php echo JOB_MANAGER_APPLY_WITH_XING_PLUGIN_URL . '/assets/js/hello.js'; ?>" >
			<script>
				hello.init();
			</script>
			</body>
		</html>
		<?php
		exit;
	}
}
$GLOBALS['wp-job-manager-apply-with-xing'] = new WP_Job_Manager_Apply_With_XING();