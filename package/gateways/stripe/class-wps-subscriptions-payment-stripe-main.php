<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://wpswings.com
 * @since      1.6.2
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 */

/**
 * The Payment-specific functionality of the plugin admin side.
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 * @author      WP Swings <webmaster@wpswings.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! class_exists( 'Wps_Subscriptions_Payment_Stripe_Main' ) ) {
	/**
	 * Define class to handle the recurring and cancellation.
	 */
	class Wps_Subscriptions_Payment_Stripe_Main {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wps_sfw_other_payment_gateway_renewal', array( $this, 'wps_sfw_process_stripe_renewal_payment_callback' ), 10, 3 );

			add_action( 'wps_sfw_subscription_cancel', array( $this, 'wps_sfw_cancel_stripe_subscription' ), 10, 2 );

			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'wps_sfw_add_stripe_order_statuses_for_payment_complete' ), 10, 2 );

			add_filter( 'wc_stripe_display_save_payment_method_checkbox', array( $this, 'wps_sfw_display_save_payment_method_checkbox' ) );

			add_filter( 'wc_stripe_is_optimized_checkout_available', array( $this, 'wps_sfw_disable_optimized_checkout_for_subscription' ) );

			add_filter( 'wc_stripe_generate_create_intent_request', array( $this, 'wps_sfw_add_setup_future_usage_for_parent_order' ), 50, 4 );

			// Path to Stripe's main plugin file.
			$stripe_main_file = WP_PLUGIN_DIR . '/woocommerce-gateway-stripe/woocommerce-gateway-stripe.php';

			$version = null;
			if ( file_exists( $stripe_main_file ) ) {
				$plugin_data = get_file_data(
					$stripe_main_file,
					array( 'Version' => 'Version' ),
					'plugin'
				);
				$version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : null;
			}
			if ( $version ) {
				if ( version_compare( $version, '9.6.0', '>=' ) ) {
					// New filter (≥ 9.6.0) — no deprecation.
					add_filter( 'wc_stripe_force_save_payment_method', array( $this, 'wps_sfw_wc_stripe_force_save_source_new' ), 10, 2 );
				} else {
					// Old filter (< 9.6.0).
					add_filter( 'wc_stripe_force_save_source', array( $this, 'wps_sfw_wc_stripe_force_save_source_old' ), 10, 2 );
				}
			}
		}

		/**
		 * Process subscription payment.
		 *
		 * @name wps_sfw_process_stripe_renewal_payment.
		 * @param object $renewal_order renewal order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 */
		public function wps_sfw_process_stripe_renewal_payment_callback( $renewal_order, $subscription_id, $payment_method ) {
			if ( class_exists( 'Wps_Subscriptions_Payment_Stripe' ) ) {
				$obj = new Wps_Subscriptions_Payment_Stripe();
				$obj->wps_sfw_process_stripe_renewal_payment( $renewal_order, $subscription_id, $payment_method );
			}
		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name wps_sfw_cancel_stripe_subscription
		 * @param int    $wps_subscription_id wps_subscription_id.
		 * @param string $status status.
		 */
		public function wps_sfw_cancel_stripe_subscription( $wps_subscription_id, $status ) {

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$subscription = new WPS_Subscription( $wps_subscription_id );
				$wps_payment_method = $subscription->get_payment_method();
			} else {
				$wps_payment_method = get_post_meta( $wps_subscription_id, '_payment_method', true );
			}
			
			if ( 'stripe' == $wps_payment_method && 'Cancel' == $status ) {
				wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
				wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
			}
		}

		/**
		 * This function is add subscription order status.
		 *
		 * @name wps_sfw_add_stripe_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 */
		public function wps_sfw_add_stripe_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {
				$order_id = $order->get_id();

				$payment_method = $order->get_payment_method();

				$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
				if ( 'stripe' == $payment_method && 'yes' == $wps_sfw_renewal_order ) {
					$order_status[] = 'wps_renewal';
				}
			}
			return apply_filters( 'wps_sfw_add_subscription_order_statuses_for_payment_complete', $order_status, $order );
		}

		/**
		 * Display save payment method checkbox.
		 *
		 * @param bool $display_save_payment_method_checkbox display_save_payment_method_checkbox.
		 */
		public function wps_sfw_display_save_payment_method_checkbox( $display_save_payment_method_checkbox ) {
			if ( wps_sfw_is_cart_has_subscription_product() ) {
				return false;
			}
			return $display_save_payment_method_checkbox;
		}

		/**
		 * Disable Stripe's Optimized Checkout / Adaptive Pricing (Checkout Sessions) flow for subscription carts.
		 *
		 * The Checkout Sessions flow builds the initial payment via Stripe's `checkout/sessions` API, which both
		 * ignores the `wc_stripe_force_save_payment_method` / `wc_stripe_generate_create_intent_request` filters this
		 * plugin relies on AND initialises Stripe Checkout with `enableSave: "never"`. The card is therefore charged
		 * once but never attached to the Stripe customer, so off-session renewals fail with "The provided
		 * PaymentMethod was previously used ... you must attach it to a Customer first." Forcing the standard
		 * deferred-intent UPE flow keeps the payment method attached and reusable for renewals.
		 *
		 * @param bool $is_available Whether Optimized Checkout is available.
		 * @return bool
		 */
		public function wps_sfw_disable_optimized_checkout_for_subscription( $is_available ) {
			if ( ! $is_available || ! function_exists( 'WC' ) || ! WC()->cart ) {
				return $is_available;
			}
			if ( wps_sfw_is_cart_has_subscription_product() ) {
				return false;
			}
			return $is_available;
		}

		/**
		 * Add setup future usage for parent order if subscription exist in order and setup future usage is missing in request.
		 *
		 * This forces Stripe to attach the payment method to the customer during the initial subscription
		 * payment so it can be charged off-session on renewals.
		 *
		 * @param array    $request         The request array.
		 * @param WC_Order $order           The order object.
		 * @param array    $prepared_source The prepared source array.
		 * @param bool     $is_setup_intent Whether it's a setup intent or not.
		 *
		 * @return array The modified request array.
		 */
		public function wps_sfw_add_setup_future_usage_for_parent_order( $request, $order, $prepared_source, $is_setup_intent = false ) {
			if ( ! $order instanceof WC_Order ) {
				return $request;
			}

			$order_id = $order->get_id();

			$is_renewal       = 'yes' === wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
			$has_subscription = function_exists( 'wps_sfw_order_has_subscription' ) && wps_sfw_order_has_subscription( $order_id );

			$is_parent_order      = $has_subscription && ! $is_renewal;
			$missing_future_usage = empty( $request['setup_future_usage'] );

			if ( $is_parent_order && $missing_future_usage ) {
				$request['setup_future_usage'] = 'off_session';
			}

			return $request;
		}

		/**
		 * Force stripe to Save payment information to my account for future purchases.
		 *
		 * @param bool  $force_save_source Should we force save payment source.
		 * @param array $customer as customer.
		 */
		public function wps_sfw_wc_stripe_force_save_source_old( $force_save_source, $customer = null ) {
			if ( wps_sfw_is_cart_has_subscription_product() ) {
				return true;
			}
			return $force_save_source;
		}

		/**
		 * Force stripe to Save payment information to my account for future purchases.
		 *
		 * @param bool  $force_save_source Should we force save payment source.
		 * @param int   $order_id Order ID.
		 */
		public function wps_sfw_wc_stripe_force_save_source_new( $force_save_source, $order_id ) {
			if ( wps_sfw_is_cart_has_subscription_product() || ( $order_id && wps_sfw_order_has_subscription( $order_id ) ) ) {
				return true;
			}
			return $force_save_source;
		}
	}
}
new Wps_Subscriptions_Payment_Stripe_Main();
