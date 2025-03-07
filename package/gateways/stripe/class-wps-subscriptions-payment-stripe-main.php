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

			add_filter( 'wc_stripe_display_save_payment_method_checkbox', array( $this, 'wps_sfw_wc_stripe_force_save_source_callback' ) );

			add_filter( 'wc_stripe_force_save_source', array( $this, 'wps_sfw_wc_stripe_force_save_source_callback_old' ), 10, 2 );

			add_filter( 'wc_stripe_generate_create_intent_request', array( $this, 'wps_sfw_wc_stripe_generate_create_intent_request' ), 10, 3 );
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
		  * Force stripe to Save payment information to my account for future purchases.
		  *
		  * @param bool $force_save_source Should we force save payment source.
		  */
		public function wps_sfw_wc_stripe_force_save_source_callback( $force_save_source ) {
			if ( wps_sfw_is_cart_has_subscription_product() ) {
				return false;
			}
			return $force_save_source;
		}

		/**
		 * Force stripe to Save payment information to my account for future purchases.
		 *
		 * @param bool  $force_save_source Should we force save payment source.
		 * @param array $customer as customer.
		 */
		public function wps_sfw_wc_stripe_force_save_source_callback_old( $force_save_source, $customer = null ) {
			if ( wps_sfw_is_cart_has_subscription_product() ) {
				return true;
			}
			return $force_save_source;
		}

		/**
		 * Function to generate intent request.
		 *
		 * @param array  $request as request.
		 * @param object $order as order.
		 * @param object $prepared_source as prepared source.
		 * @return array
		 */
		public function wps_sfw_wc_stripe_generate_create_intent_request( $request, $order, $prepared_source ) {
			// Ensure $order is a valid WooCommerce order instance.
			if ( ! is_a( $order, 'WC_Order' ) ) {
				return $request;
			}

			// Check if order has a subscription (based on custom meta key).
			if ( 'yes' !== $order->get_meta( 'wps_sfw_order_has_subscription' ) ) {
				return $request;
			}

			// Get payment method used in the order.
			$payment_method = $order->get_payment_method();

			// Define payment methods that support `setup_future_usage`.
			$supported_methods = array( 'stripe', 'stripe_cc', 'stripe_sepa' ); // Add more if needed.

			// Apply only if the payment method is supported.
			if ( in_array( $payment_method, $supported_methods, true ) ) {
				$request['setup_future_usage'] = 'off_session';
			}

			return $request;
		}
	}
}
new Wps_Subscriptions_Payment_Stripe_Main();
