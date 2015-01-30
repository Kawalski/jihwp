<?php
/*
Plugin Name: WP Job Manager - Job Alerts
Plugin URI: https://wpjobmanager.com/add-ons/job-alerts/
Description: Allow users to subscribe to job alerts for their searches. Once registered, users can access a 'My Alerts' page which you can create with the shortcode [job_alerts].
Version: 1.3.4
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 3.8
Tested up to: 4.1

	Copyright: 2013 Mike Jolley
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'WPJM_Updater' ) ) {
	include( 'includes/updater/class-wpjm-updater.php' );
}

/**
 * WP_Job_Manager_Alerts class.
 */
class WP_Job_Manager_Alerts extends WPJM_Updater {

	/**
	 * __construct function.
	 */
	public function __construct() {

		// Define constants
		define( 'JOB_MANAGER_ALERTS_VERSION', '1.3.4' );
		define( 'JOB_MANAGER_ALERTS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'JOB_MANAGER_ALERTS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		// Includes
		include( 'includes/class-wp-job-manager-alerts-shortcodes.php' );
		include( 'includes/class-wp-job-manager-alerts-post-types.php' );
		include( 'includes/class-wp-job-manager-alerts-notifier.php' );

		// Init classes
		$this->post_types = new WP_Job_Manager_Alerts_Post_Types();

		// Add actions
		add_action( 'init', array( $this, 'init' ), 12 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_filter( 'job_manager_settings', array( $this, 'settings' ) );
		add_filter( 'job_manager_job_filters_showing_jobs_links', array( $this, 'alert_link' ), 10, 2 );
		add_action( 'single_job_listing_end', array( $this, 'single_alert_link' ) );

		// Update legacy options
		if ( false === get_option( 'job_manager_alerts_page_id', false ) && get_option( 'job_manager_alerts_page_slug' ) ) {
			$page_id = get_page_by_path( get_option( 'job_manager_alerts_page_slug' ) )->ID;
			update_option( 'job_manager_alerts_page_id', $page_id );
		}

		// Init updates
		$this->init_updates( __FILE__ );
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function init() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-alerts' );
		load_textdomain( 'wp-job-manager-alerts', WP_LANG_DIR . "/wp-job-manager-alerts/wp-job-manager-alerts-$locale.mo" );
		load_plugin_textdomain( 'wp-job-manager-alerts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		wp_register_script( 'job-alerts', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/js/job-alerts.min.js', array( 'jquery', 'chosen' ), JOB_MANAGER_ALERTS_VERSION, true );

		wp_localize_script( 'job-alerts', 'job_manager_alerts', array(
			'i18n_confirm_delete' => __( 'Are you sure you want to delete this alert?', 'wp-job-manager-alerts' )
		) );

		wp_enqueue_style( 'job-alerts-frontend', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/css/frontend.css' );
	}

	/**
	 * Return the default email content for alerts
	 */
	public function get_default_email() {
		return "Hello {display_name},

The following jobs were found matching your \"{alert_name}\" job alert.
----
{jobs}

Your next alert for this search will be sent {alert_next_date}. To manage your alerts please login and visit your alerts page here: {alert_page_url}.

{alert_expirey}";
	}

	/**
	 * Add Settings
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = array() ) {
		if ( ! get_option( 'job_manager_alerts_email_template' ) ) {
			delete_option( 'job_manager_alerts_email_template' );
		}

		$settings['job_alerts'] = array(
			__( 'Job Alerts', 'wp-job-manager-alerts' ),
			apply_filters(
				'wp_job_manager_alerts_settings',
				array(
					array(
						'name' 		=> 'job_manager_alerts_email_template',
						'std' 		=> $this->get_default_email(),
						'label' 	=> __( 'Alert Email Content', 'wp-job-manager-alerts' ),
						'desc'		=> __( 'Enter the content for your email alerts. Leave blank to use the default message. The following tags can be used to insert data dynamically:', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{display_name}</code>' . ' - ' . __( 'The users display name in WP', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{alert_name}</code>' . ' - ' . __( 'The name of the alert being sent', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{alert_expirey}</code>' . ' - ' . __( 'A sentance explaining if an alert will be stopped automatically', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{alert_next_date}</code>' . ' - ' . __( 'The date this alert will next be sent', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{alert_page_url}</code>' . ' - ' . __( 'The url to your alerts page', 'wp-job-manager-alerts' ) . '<br/>' .
							'<code>{jobs}</code>' . ' - ' . __( 'The name of the alert being sent', 'wp-job-manager-alerts' ) . '<br/>' .
							'',
						'type'      => 'textarea',
						'required'  => true
					),
					array(
						'name' 		=> 'job_manager_alerts_auto_disable',
						'std' 		=> '90',
						'label' 	=> __( 'Alert Duration', 'wp-job-manager-alerts' ),
						'desc'		=> __( 'Enter the number of days before alerts are automatically disabled, or leave blank to disable this feature. By default, alerts will be turned off for a search after 90 days.', 'wp-job-manager-alerts' ),
						'type'      => 'input'
					),
					array(
						'name' 		=> 'job_manager_alerts_matches_only',
						'std' 		=> 'no',
						'label' 	=> __( 'Alert Matches', 'wp-job-manager-alerts' ),
						'cb_label' 	=> __( 'Send alerts with matches only', 'wp-job-manager-alerts' ),
						'desc'		=> __( 'Only send an alert when jobs are found matching its criteria. When disabled, an alert is sent regardless.', 'wp-job-manager-alerts' ),
						'type'      => 'checkbox'
					),
					array(
						'name' 		=> 'job_manager_alerts_page_id',
						'std' 		=> '',
						'label' 	=> __( 'Alerts Page ID', 'wp-job-manager-alerts' ),
						'desc'		=> __( 'So that the plugin knows where to link users to view their alerts, you must select the page where you have placed the [job_alerts] shortcode.', 'wp-job-manager-alerts' ),
						'type'      => 'page'
					)
				)
			)
		);
		return $settings;
	}

	/**
	 * Add the alert link
	 */
	public function alert_link( $links, $args ) {
		if ( is_user_logged_in() && get_option( 'job_manager_alerts_page_id' ) ) {
			if ( isset( $_POST[ 'form_data' ] ) ) {
				parse_str( $_POST[ 'form_data' ], $params );
				$alert_region = isset( $params[ 'search_region' ] ) ? absint( $params[ 'search_region' ] ) : '';
			} else {
				$alert_region = '';
			}

			$links['alert'] = array(
				'name' => __( 'Add alert', 'wp-job-manager-alerts' ),
				'url'  => add_query_arg( array(
					'action'         => 'add_alert',
					'alert_job_type' => $args['filter_job_types'],
					'alert_location' => urlencode( $args['search_location'] ),
					'alert_cats'     => $args['search_categories'],
					'alert_keyword'  => urlencode( $args['search_keywords'] ),
					'alert_region'   => $alert_region
				), get_permalink( get_option( 'job_manager_alerts_page_id' ) ) )
			);
		}

		return $links;
	}

	/**
	 * Single listing alert link
	 */
	public function single_alert_link() {
		global $post;

		if ( is_user_logged_in() && get_option( 'job_manager_alerts_page_id' ) ) {
			$job_type = get_the_job_type( $post );
			$link     =  add_query_arg( array(
				'action'         => 'add_alert',
				'alert_name'     => urlencode( $post->post_title ),
				'alert_job_type' => array( $job_type->slug ),
				'alert_location' => urlencode( get_the_job_location( $post ) ),
				'alert_cats'     => wp_get_post_terms( $post->ID, 'job_listing_category', array( 'fields' => 'ids' ) ),
				'alert_keyword'  => urlencode( $post->post_title ),
				'alert_region'   => current( wp_get_post_terms( $post->ID, 'job_listing_region', array( 'fields' => 'ids' ) ) ),
			), get_permalink( get_option( 'job_manager_alerts_page_id' ) ) );
			echo '<p class="job-manager-single-alert-link"><a href="' . esc_url( $link ) . '">' . __( 'Alert me to jobs like this', 'wp-job-manager-alerts' ) . '</a></p>';
		}
	}
}

$GLOBALS['job_manager_alerts'] = new WP_Job_Manager_Alerts();