<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders
 */
class WP_Job_Manager_WCPL_Orders {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Displaying user packages on the frontend
		add_action( 'woocommerce_before_my_account', array( $this, 'my_packages' ) );

		// Statuses
		add_action( 'woocommerce_order_status_processing', array( $this, 'order_paid' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_paid' ) );

		// Subscriptions support
		add_action( 'subscription_expired', array( $this, 'subscription_delete_packs' ), 10, 2 );
		add_action( 'scheduled_subscription_payment', array( $this, 'subscription_renew_packs' ), 10, 2 );
		add_action( 'subscription_trashed', array( $this, 'subscription_delete_packs' ), 10, 2 );
	}

	/**
	 * Show my packages
	 */
	public function my_packages() {
		if ( ( $packages = wc_paid_listings_get_user_packages( get_current_user_id(), 'job_listing' ) ) && is_array( $packages ) && sizeof( $packages ) > 0 ) {
			woocommerce_get_template( 'my-packages.php', array( 'packages' => $packages, 'type' => 'job_listing' ), 'wc-paid-listings/', JOB_MANAGER_WCPL_TEMPLATE_PATH );
		}
		if ( ( $packages = wc_paid_listings_get_user_packages( get_current_user_id(), 'resume' ) ) && is_array( $packages ) && sizeof( $packages ) > 0 ) {
			woocommerce_get_template( 'my-packages.php', array( 'packages' => $packages, 'type' => 'resume' ), 'wc-paid-listings/', JOB_MANAGER_WCPL_TEMPLATE_PATH );
		}
	}

	/**
	 * Triggered when an order is paid
	 * @param  int $order_id
	 */
	public function order_paid( $order_id ) {
		// Get the order
		$order = new WC_Order( $order_id );

		if ( get_post_meta( $order_id, 'wc_paid_listings_packages_processed', true ) ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			$product = get_product( $item['product_id'] );

			if ( ( $product->is_type( 'job_package' ) || $product->is_type( 'job_package_subscription' ) || $product->is_type( 'resume_package' ) || $product->is_type( 'resume_package_subscription' ) ) && $order->customer_user ) {
				for ( $i = 0; $i < $item['qty']; $i ++ ) {
					$user_package_id = wc_paid_listings_give_user_package( $order->customer_user, $product->id );
				}

				if ( isset( $item['job_id'] ) && ( $job_id = $item['job_id'] ) ) {
					$job = get_post( $job_id );

					// Approve the job
					if ( in_array( $job->post_status, array( 'pending_payment', 'expired' ) ) ) {
						wc_paid_listings_approve_job_listing_with_package( $job_id, $order->customer_user, $user_package_id );
					}
				} elseif ( isset( $item['resume_id'] ) && ( $resume_id = $item['resume_id'] ) ) {
					$resume = get_post( $resume_id );

					// Approve the job
					if ( in_array( $resume->post_status, array( 'pending_payment', 'expired' ) ) ) {
						wc_paid_listings_approve_resume_with_package( $resume_id, $order->customer_user, $user_package_id );
					}
				}
			}
		}

		update_post_meta( $order_id, 'wc_paid_listings_packages_processed', true );
	}

	/**
	 * Subscription has expires - cancel job packs
	 */
	public function subscription_delete_packs( $user_id, $subscription_key ) {
		global $wpdb;

		$subscription = WC_Subscriptions_Manager::get_subscription( $subscription_key );

		$wpdb->delete(
			"{$wpdb->prefix}wcpl_user_packages",
			array(
				'user_id'    => $user_id,
				'product_id' => $subscription['product_id']
			)
		);
	}

	/**
	 * Subscription term renewed - renew the job pack
	 */
	public function subscription_renew_packs( $user_id, $subscription_key ) {
		global $wpdb;

		$subscription = WC_Subscriptions_Manager::get_subscription( $subscription_key );

		if ( ! $wpdb->update(
			"{$wpdb->prefix}wcpl_user_packages",
			array(
				'package_count'  => 0
			),
			array(
				'user_id'    => $user_id,
				'product_id' => $subscription['product_id']
			)
		) ) {
			wc_paid_listings_give_user_package( $user_id, $subscription['product_id'] );
		}
	}
}

new WP_Job_Manager_WCPL_Orders();