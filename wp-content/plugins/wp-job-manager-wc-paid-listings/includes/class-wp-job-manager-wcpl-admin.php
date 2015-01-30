<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Admin
 */
class WP_Job_Manager_WCPL_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_subscription_product_types', array( $this, 'woocommerce_subscription_product_types' ) );
		add_filter( 'product_type_selector', array( $this, 'product_type_selector' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_data' ) );
	}

	/**
	 * Types for subscriptions
	 * @param  string $types
	 * @return string
	 */
	public function woocommerce_subscription_product_types( $types ) {
		$types[] = 'job_package_subscription';
		$types[] = 'resume_package_subscription';
		return $types;
	}

	/**
	 * Add the product type
	 */
	public function product_type_selector( $types ) {
		$types[ 'job_package' ] = __( 'Job Package', 'wp-job-manager-wc-paid-listings' );
		if ( class_exists( 'WP_Resume_Manager' ) ) {
			$types[ 'resume_package' ] = __( 'Resume Package', 'wp-job-manager-wc-paid-listings' );
		}
		if ( class_exists( 'WC_Subscriptions' ) ) {
			$types['job_package_subscription'] = __( 'Job Package Subscription', 'wp-job-manager-wc-paid-listings' );
			if ( class_exists( 'WP_Resume_Manager' ) ) {
				$types[ 'resume_package_subscription' ] = __( 'Resume Package Subscription', 'wp-job-manager-wc-paid-listings' );
			}
		}
		return $types;
	}

	/**
	 * Show the job package product options
	 */
	public function product_data() {
		global $post;
		$post_id = $post->ID;
		include( 'views/html-job-package-data.php' );
		include( 'views/html-resume-package-data.php' );
	}

	/**
	 * Save Job Package data for the product
	 *
	 * @param  int $post_id
	 */
	public function save_product_data( $post_id ) {
		global $wpdb;

		// Save meta
		$meta_to_save = array(
			'_job_listing_duration' => '',
			'_job_listing_limit'    => 'int',
			'_job_listing_featured' => 'yesno',
			'_resume_duration'      => '',
			'_resume_limit'         => '',
			'_resume_featured'      => 'yesno'
		);

		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			$value = ! empty( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
			switch ( $sanitize ) {
				case 'int' :
					$value = absint( $value );
					break;
				case 'float' :
					$value = floatval( $value );
					break;
				case 'yesno' :
					$value = $value == 'yes' ? 'yes' : 'no';
					break;
				default :
					$value = sanitize_text_field( $value );
			}
			update_post_meta( $post_id, $meta_key, $value );
		}
	}
}

new WP_Job_Manager_WCPL_Admin();