<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Integration
 */
class WP_Job_Manager_WCPL_Submit_Resume_Form {

	private static $package_id = 0;
	private static $is_user_package = false;

	/**
	 * Init
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'styles' ) );
		add_filter( 'submit_resume_steps', array( __CLASS__, 'submit_resume_steps' ), 10 );

		// Posted Data
		if ( ! empty( $_POST['resume_package'] ) ) {
			if ( is_numeric( $_POST['resume_package'] ) ) {
				self::$package_id      = absint( $_POST['resume_package'] );
				self::$is_user_package = false;
			} else {
				self::$package_id      = absint( substr( $_POST['resume_package'], 5 ) );
				self::$is_user_package = true;
			}
		} elseif ( ! empty( $_COOKIE['chosen_package_id'] ) ) {
			self::$package_id      = absint( $_COOKIE['chosen_package_id'] );
			self::$is_user_package = absint( $_COOKIE['chosen_package_is_user_package'] ) === 1;
		}
	}

	/**
	 * Add form styles
	 */
	public static function styles() {
		wp_enqueue_style( 'wc-paid-listings-packages', JOB_MANAGER_WCPL_PLUGIN_URL . '/assets/css/packages.css' );
	}

	/**
	 * Change submit button text
	 * @return string
	 */
	public static function submit_button_text() {
		return __( 'Choose a package &rarr;', 'wp-job-manager-wc-paid-listings' );
	}

	/**
	 * Return packages
	 * @return array
	 */
	public static function get_packages() {
		return get_posts( array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'resume_package', 'resume_package_subscription' )
				)
			),
			'meta_query'     => array(
				array(
					'key'     => '_visibility',
					'value'   => array( 'visible', 'catalog' ),
					'compare' => 'IN'
				)
			)
		) );
	}

	/**
	 * Change the steps during the submission process
	 *
	 * @param  array $steps
	 * @return array
	 */
	public static function submit_resume_steps( $steps ) {
		if ( self::get_packages() && apply_filters( 'wcpl_enable_paid_resume_submission', true ) ) {
			// We need to hijack the preview submission so we can take a payment
			$steps['preview']['handler'] = array( __CLASS__, 'preview_handler' );

			// Add the payment step
			$steps['wc-pay'] = array(
				'name'     => __( 'Choose a package', 'wp-job-manager-wc-paid-listings' ),
				'view'     => array( __CLASS__, 'choose_package' ),
				'handler'  => array( __CLASS__, 'choose_package_handler' ),
				'priority' => ( 'before' === get_option( 'job_manager_paid_listings_flow' ) ? 5 : 25 )
			);

			if ( 'before' !== get_option( 'job_manager_paid_listings_flow' ) ) {
				add_filter( 'submit_resume_step_preview_submit_text', array( __CLASS__, 'submit_button_text' ), 10 );
			}
		}
		return $steps;
	}

	/**
	 * Get the package ID being used for resume submission, expanding any user package
	 * @return int
	 */
	public static function get_package_id() {
		if ( self::$is_user_package ) {
			$package = wc_paid_listings_get_user_package( self::$package_id );
			return $package->get_product_id();
		}

		return self::$package_id;
	}

	/**
	 * Choose package form
	 */
	public static function choose_package() {
		$packages      = self::get_packages();
		$user_packages = wc_paid_listings_get_user_packages( get_current_user_id(), 'resume' );
		$button_text   = 'before' !== get_option( 'job_manager_paid_listings_flow' ) ? __( 'Submit &rarr;', 'wp-job-manager-wc-paid-listings' ) : __( 'Listing Details &rarr;', 'wp-job-manager-wc-paid-listings' );
		?>
		<form method="post" id="job_package_selection">
			<div class="job_listing_packages_title">
				<input type="submit" name="continue" class="button" value="<?php echo apply_filters( 'submit_job_step_choose_package_submit_text', $button_text ); ?>" />
				<input type="hidden" name="resume_id" value="<?php echo esc_attr( WP_Resume_Manager_Form_Submit_Resume::get_resume_id() ); ?>" />
				<input type="hidden" name="job_id" value="<?php echo esc_attr( WP_Resume_Manager_Form_Submit_Resume::get_job_id() ); ?>" />
				<input type="hidden" name="step" value="<?php echo esc_attr( WP_Resume_Manager_Form_Submit_Resume::get_step() ); ?>" />
				<input type="hidden" name="resume_manager_form" value="<?php echo WP_Resume_Manager_Form_Submit_Resume::$form_name; ?>" />
				<h2><?php _e( 'Choose a package', 'wp-job-manager-wc-paid-listings' ); ?></h2>
			</div>
			<div class="job_listing_packages">
				<?php get_job_manager_template( 'resume-package-selection.php', array( 'packages' => $packages, 'user_packages' => $user_packages ), 'wc-paid-listings', JOB_MANAGER_WCPL_PLUGIN_DIR . '/templates/' ); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Choose package handler
	 * @return bool
	 */
	public static function choose_package_handler() {
		// Validate Selected Package
		$validation = self::validate_package( self::$package_id, self::$is_user_package );

		if ( is_wp_error( $validation ) ) {
			WP_Resume_Manager_Form_Submit_Resume::add_error( $validation->get_error_message() );
			return false;
		}

		// Store selection in cookie
		wc_setcookie( 'chosen_package_id', self::$package_id );
		wc_setcookie( 'chosen_package_is_user_package', self::$is_user_package ? 1 : 0 );

		// Process the package unless we're doing this before a listing is submitted
		if ( 'before' !== get_option( 'job_manager_paid_listings_flow' ) ) {
			$result = self::process_package( self::$package_id, self::$is_user_package, WP_Resume_Manager_Form_Submit_Resume::get_resume_id() );

			if ( $result ) {
				WP_Resume_Manager_Form_Submit_Resume::next_step();
			}
		} else {
			WP_Resume_Manager_Form_Submit_Resume::next_step();
		}
	}

	/**
	 * Validate package
	 * @param  int $package_id
	 * @param  bool $is_user_package
	 * @return bool|WP_Error
	 */
	private static function validate_package( $package_id, $is_user_package ) {
		if ( empty( $package_id ) ) {
			return new WP_Error( 'error', __( 'Invalid Package', 'wp-job-manager-wc-paid-listings' ) );
		} elseif ( $is_user_package ) {
			if ( ! wc_paid_listings_package_is_valid( get_current_user_id(), $package_id ) ) {
				return new WP_Error( 'error', __( 'Invalid Package', 'wp-job-manager-wc-paid-listings' ) );
			}
		} else {
			$package = get_product( $package_id );

			if ( ! $package->is_type( 'resume_package' ) && ! $package->is_type( 'resume_package_subscription' ) ) {
				return new WP_Error( 'error', __( 'Invalid Package', 'wp-job-manager-wc-paid-listings' ) );
			}

			// Don't let them buy the same subscription twice
			if ( class_exists( 'WC_Subscriptions' ) && is_user_logged_in() ) {
				$user_subscriptions = WC_Subscriptions_Manager::get_users_subscriptions( get_current_user_id() );
				foreach ( $user_subscriptions as $user_subscription ) {
					if ( $user_subscription['product_id'] == $package_id ) {
						return new WP_Error( 'error', __( 'You already have this subscription.', 'wp-job-manager-wc-paid-listings' ) );
					}
				}
			}
		}
		return true;
	}

	/**
	 * Purchase a job package
	 * @param  int|string $package_id
	 * @param  int $resume_id
	 * @return bool Did it work or not?
	 */
	private static function process_package( $package_id, $is_user_package, $resume_id ) {
		if ( $is_user_package ) {
			$package = wc_paid_listings_get_user_package( $package_id );

			// Give resume the package attributes
			update_post_meta( $resume_id, '_featured', $package->is_featured() ? 1 : 0 );
			update_post_meta( $resume_id, '_resume_duration', $package->get_duration() );
			update_post_meta( $resume_id, '_package_id', $package->get_product_id() );

			// Approve the resume
			if ( in_array( get_post_status( $resume_id ), array( 'pending_payment', 'expired' ) ) ) {
				wc_paid_listings_approve_resume_with_package( $resume_id, get_current_user_id(), $package_id );
			}

			return true;
		} else {
			$package = get_product( $package_id );

			// Give resume the package attributes
			update_post_meta( $resume_id, '_featured', $package->is_featured() ? 1 : 0 );
			update_post_meta( $resume_id, '_resume_duration', $package->get_duration() );
			update_post_meta( $resume_id, '_package_id', $package->get_product_id() );

			// Add package to the cart
			WC()->cart->add_to_cart( $package_id, 1, '', '', array(
				'resume_id' => $resume_id
			) );

			woocommerce_add_to_cart_message( $package_id );

			// Redirect to checkout page
			wp_redirect( get_permalink( woocommerce_get_page_id( 'checkout' ) ) );
			exit;
		}
	}

	/**
	 * Handle the form when the preview page is submitted
	 */
	public static function preview_handler() {
		if ( ! $_POST ) {
			return;
		}

		// Edit = show submit form again
		if ( ! empty( $_POST['edit_resume'] ) ) {
			WP_Resume_Manager_Form_Submit_Resume::previous_step();
		}

		// Continue to the next step
		if ( ! empty( $_POST['continue'] ) ) {
			$resume = get_post( WP_Resume_Manager_Form_Submit_Resume::get_resume_id() );

			// Update resume status to pending_payment
			if ( $resume->post_status == 'preview' ) {
				$update_resume                = array();
				$update_resume['ID']          = $resume->ID;
				$update_resume['post_status'] = 'pending_payment';
				wp_update_post( $update_resume );
			}

			// If we're already chosen a package, apply its properties to the job here and add to cart
			if ( 'before' === get_option( 'job_manager_paid_listings_flow' ) ) {
				// Validate Selected Package
				$validation = self::validate_package( self::$package_id, self::$is_user_package );

				if ( is_wp_error( $validation ) ) {
					WP_Resume_Manager_Form_Submit_Resume::add_error( $validation->get_error_message() );
					WP_Resume_Manager_Form_Submit_Resume::previous_step();
					WP_Resume_Manager_Form_Submit_Resume::previous_step();
				}

				self::process_package( self::$package_id, self::$is_user_package, WP_Resume_Manager_Form_Submit_Resume::get_resume_id() );
				WP_Resume_Manager_Form_Submit_Resume::next_step();

			// Proceeed to the choose package step if the above did not redirect
			} else {
				WP_Resume_Manager_Form_Submit_Resume::next_step();
			}
		}
	}
}

WP_Job_Manager_WCPL_Submit_Resume_Form::init();