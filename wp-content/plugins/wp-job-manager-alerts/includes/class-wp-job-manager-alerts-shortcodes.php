<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Job_Manager_Alerts_Shortcodes class.
 */
class WP_Job_Manager_Alerts_Shortcodes {

	private $alert_message = '';
	private $action = '';

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'shortcode_action_handler' ) );

		add_shortcode( 'job_alerts', array( $this, 'job_alerts' ) );

		$this->action = isset( $_REQUEST['action'] ) ? sanitize_title( $_REQUEST['action'] ) : '';
	}

	/**
	 * Handle actions which need to be run before the shortcode e.g. post actions
	 */
	public function shortcode_action_handler() {
		global $post;

		if ( is_page() && strstr( $post->post_content, '[job_alerts' ) ) {
			$this->job_alerts_handler();
		}
	}

	/**
	 * Handles actions
	 */
	public function job_alerts_handler() {
		if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'job_manager_alert_actions' ) ) {

			try {
				switch ( $this->action ) {
					case 'add_alert' :
					case 'edit' :
						if ( isset( $_POST['submit-job-alert'] ) ) {
							$alert_name      = sanitize_text_field( $_POST['alert_name'] );
							$alert_keyword   = sanitize_text_field( $_POST['alert_keyword'] );
							$alert_location  = sanitize_text_field( $_POST['alert_location'] );
							$alert_frequency = sanitize_text_field( $_POST['alert_frequency'] );

							if ( empty( $alert_name ) ) {
								throw new Exception( __( 'Please name your alert', 'wp-job-manager-alerts' ) );
							}

							if ( $this->action == 'add_alert' ) {
								$alert_data = array(
									'post_title'     => $alert_name,
									'post_status'    => 'publish',
									'post_type'      => 'job_alert',
									'comment_status' => 'closed',
									'post_author'    => get_current_user_id()
								);

								$alert_id = wp_insert_post( $alert_data );
							} else {
								$alert_id = absint( $_REQUEST['alert_id'] );
								$alert    = get_post( $alert_id );

								// Check ownership
								if ( $alert->post_author != get_current_user_id() )
									throw new Exception( __( 'Invalid Alert', 'wp-job-manager-alerts' ) );

								$update_alert = array();
								$update_alert['ID'] = $alert_id;
								$update_alert['post_title'] = $alert_name;
								wp_update_post( $update_alert );
							}

							if ( taxonomy_exists( 'job_listing_category' ) ) {
								$alert_cats = isset( $_POST['alert_cats'] ) ? array_map( 'absint', $_POST['alert_cats'] ) : '';
								wp_set_object_terms( $alert_id, $alert_cats, 'job_listing_category' );
							}

							if ( taxonomy_exists( 'job_listing_region' ) ) {
								$alert_regions = isset( $_POST['alert_regions'] ) ? array_map( 'absint', $_POST['alert_regions'] ) : '';
								wp_set_object_terms( $alert_id, $alert_regions, 'job_listing_region' );
							}

							$alert_job_type = isset( $_POST['alert_job_type'] ) ? array_map( 'sanitize_title', $_POST['alert_job_type'] ) : '';
							wp_set_object_terms( $alert_id, $alert_job_type, 'job_listing_type' );

							update_post_meta( $alert_id, 'alert_frequency', $alert_frequency );
							update_post_meta( $alert_id, 'alert_keyword', $alert_keyword );
							update_post_meta( $alert_id, 'alert_location', $alert_location );

							wp_clear_scheduled_hook( 'job-manager-alert', array( $alert_id ) );

							// Schedule new alert
							switch ( $alert_frequency ) {
								case 'daily' :
									$next = strtotime( '+1 day' );
								break;
								case 'fortnightly' :
									$next = strtotime( '+2 week' );
								break;
								default :
									$next = strtotime( '+1 week' );
								break;
							}

							// Create cron
							wp_schedule_event( $next, $alert_frequency, 'job-manager-alert', array( $alert_id ) );

							wp_redirect( add_query_arg( 'updated', 'true', remove_query_arg( array( 'action', 'alert_id' ) ) ) );
							exit;
						}
					break;
					case 'toggle_status' :
						$alert_id = absint( $_REQUEST['alert_id'] );
						$alert    = get_post( $alert_id );

						// Check ownership
						if ( $alert->post_author != get_current_user_id() )
							throw new Exception( __( 'Invalid Alert', 'wp-job-manager-alerts' ) );

						// Handle cron
						wp_clear_scheduled_hook( 'job-manager-alert', array( $alert_id ) );

						if ( $alert->post_status == 'draft' ) {
							// Schedule new alert
							switch ( $alert->alert_frequency ) {
								case 'daily' :
									$next = strtotime( '+1 day' );
								break;
								case 'fortnightly' :
									$next = strtotime( '+2 week' );
								break;
								default :
									$next = strtotime( '+1 week' );
								break;
							}

							// Create cron
							wp_schedule_event( $next, $alert->alert_frequency, 'job-manager-alert', array( $alert_id ) );
						}

						$update_alert = array();
						$update_alert['ID'] = $alert_id;
						$update_alert['post_status'] = $alert->post_status == 'publish' ? 'draft' : 'publish';
						wp_update_post( $update_alert );

						// Message
						$this->alert_message = '<div class="job-manager-message">' . sprintf( __( '%s has been %s', 'wp-job-manager-alerts' ), $alert->post_title, $alert->post_status == 'draft' ? __( 'Enabled', 'wp-job-manager-alerts' ) : __( 'Disabled', 'wp-job-manager-alerts' ) ) . '</div>';
					break;
					case 'delete' :
						$alert_id = absint( $_REQUEST['alert_id'] );
						$alert    = get_post( $alert_id );

						// Check ownership
						if ( $alert->post_author != get_current_user_id() )
							throw new Exception( __( 'Invalid Alert', 'wp-job-manager-alerts' ) );

						// Trash it
						wp_trash_post( $alert_id );

						// Message
						$this->alert_message = '<div class="job-manager-message">' . sprintf( __( '%s has been deleted', 'wp-job-manager-alerts' ), $alert->post_title ) . '</div>';
					break;
					case 'email' :
						$alert_id = absint( $_REQUEST['alert_id'] );
						$alert    = get_post( $alert_id );

						do_action( 'job-manager-alert', $alert_id, true );

						$this->alert_message = '<div class="job-manager-message">' . sprintf( __( '%s has been triggered', 'wp-job-manager-alerts' ), $alert->post_title ) . '</div>';
					break;
				}

			} catch ( Exception $e ) {
				$this->alert_message = '<div class="job-manager-error">' . $e->getMessage() . '</div>';
			}
		}
	}

	/**
	 * Shortcode for the alerts page
	 */
	public function job_alerts( $atts ) {
		global $job_manager;

		if ( ! is_user_logged_in() ) {
			_e( 'You need to be signed in to manage your alerts.', 'wp-job-manager-alerts' );
			return;
		}

		wp_enqueue_script( 'job-alerts' );

		ob_start();

		if ( ! empty( $_GET['updated'] ) )
			echo '<div class="job-manager-message">' . __( 'Your alerts have been updated', 'wp-job-manager-alerts' ) . '</div>';
		else
			echo $this->alert_message;

		// If doing an action, show conditional content if needed....
		if ( ! empty( $this->action ) ) {

			$alert_id = isset( $_REQUEST['alert_id'] ) ? absint( $_REQUEST['alert_id'] ) : '';

			switch ( $this->action ) {
				case 'add_alert' :
					$this->add_alert();
					return;
				case 'edit' :
					$this->edit_alert( $alert_id );
					return;
				case 'view' :
					$this->view_results( $alert_id );
					return;
			}
		}

		// ....If not show the job dashboard
		$args = array(
			'post_type'           => 'job_alert',
			'post_status'         => array( 'publish', 'draft' ),
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => -1,
			'orderby'             => 'title',
			'order'               => 'asc',
			'author'              => get_current_user_id()
		);

		$alerts = get_posts( $args );
		$user   = wp_get_current_user();

		get_job_manager_template( 'my-alerts.php', array( 'alerts' => $alerts, 'user' => $user ), 'wp-job-manager-alerts', JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/' );

		return ob_get_clean();
	}

	/**
	 * Add alert form
	 */
	public function add_alert() {
		get_job_manager_template( 'alert-form.php', array(
			'alert_id'        => '',
			'alert_name'      => isset( $_REQUEST['alert_name'] ) ? sanitize_text_field( $_REQUEST['alert_name'] ) : '',
			'alert_keyword'   => isset( $_REQUEST['alert_keyword'] ) ? sanitize_text_field( $_REQUEST['alert_keyword'] ) : '',
			'alert_location'  => isset( $_REQUEST['alert_location'] ) ? sanitize_text_field( $_REQUEST['alert_location'] ) : '',
			'alert_region'    => isset( $_REQUEST['alert_region'] ) ? array( sanitize_text_field( $_REQUEST['alert_region'] ) ) : array(),
			'alert_frequency' => isset( $_REQUEST['alert_frequency'] ) ? sanitize_text_field( $_REQUEST['alert_frequency'] ) : '',
			'alert_cats'      => isset( $_REQUEST['alert_cats'] ) ? array_map( 'absint', $_REQUEST['alert_cats'] ) : array(),
			'alert_job_type'  => isset( $_REQUEST['alert_job_type'] ) ? array_map( 'sanitize_title', $_REQUEST['alert_job_type'] ) : array()
		), 'wp-job-manager-alerts', JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Edit alert form
	 */
	public function edit_alert( $alert_id ) {
		$alert = get_post( $alert_id );

		if ( $alert->post_author != get_current_user_id() )
			return;

		get_job_manager_template( 'alert-form.php', array(
			'alert_id'        => $alert_id,
			'alert_name'      => isset( $_POST['alert_name'] ) ? sanitize_text_field( $_POST['alert_name'] ) : $alert->post_title,
			'alert_keyword'   => isset( $_POST['alert_keyword'] ) ? sanitize_text_field( $_POST['alert_keyword'] ) : $alert->alert_keyword,
			'alert_location'  => isset( $_POST['alert_location'] ) ? sanitize_text_field( $_POST['alert_location'] ) : $alert->alert_location,
			'alert_frequency' => isset( $_POST['alert_frequency'] ) ? sanitize_text_field( $_POST['alert_frequency'] ) : $alert->alert_frequency,
			'alert_cats'      => isset( $_POST['alert_cats'] ) ? array_map( 'absint', $_POST['alert_cats'] ) : wp_get_post_terms( $alert_id, 'job_listing_category', array( 'fields' => 'ids' ) ),
			'alert_job_type'  => isset( $_POST['alert_job_type'] ) ? array_map( 'sanitize_title', $_POST['alert_job_type'] ) : wp_get_post_terms( $alert_id, 'job_listing_type', array( 'fields' => 'slugs' ) )
		), 'wp-job-manager-alerts', JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * View results
	 */
	public function view_results( $alert_id ) {
		$alert = get_post( $alert_id );
		$jobs  = WP_Job_Manager_Alerts_Notifier::get_matching_jobs( $alert, true );

		echo wpautop( sprintf( __( 'Jobs matching your "%s" alert:', 'wp-job-manager-alerts' ), $alert->post_title ) );

		if ( $jobs->have_posts() ) : ?>

			<ul class="job_listings">

				<?php while ( $jobs->have_posts() ) : $jobs->the_post(); ?>

					<?php get_job_manager_template_part( 'content', 'job_listing' ); ?>

				<?php endwhile; ?>

			</ul>

		<?php else :
			echo wpautop( __( 'No jobs found', 'wp-job-manager-alerts' ) );
		endif;

		wp_reset_postdata();
	}
}

new WP_Job_Manager_Alerts_Shortcodes();