<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://makewebbetter.com
 * @since      1.0.0
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 */

/**
 * The Payment-specific functionality of the plugin admin side.
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 * @author      makewebbetter <webmaster@makewebbetter.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Mwb_Subscriptions_Payment_Stripe_Main' ) ) {

	/**
	 * Define class and module for stripe.
	 */
	class Mwb_Subscriptions_Payment_Stripe_Main {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'mwb_sfw_subscription_cancel', array( $this, 'mwb_sfw_cancel_stripe_subscription' ), 10, 2 );
			add_filter( 'wc_stripe_force_save_source', array( $this, 'mwb_sfw_stripe_force_save_source' ), 10, 2 );
			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'mwb_sfw_add_stripe_order_statuses_for_payment_complete' ), 10, 2 );

			include SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/stripe/class-subscriptions-for-woocommerce-stripe.php';
		}

		/**
		 * This function is add subscription order status.
		 *
		 * @name mwb_sfw_add_stripe_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @since    1.0.2
		 */
		public function mwb_sfw_add_stripe_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {
				$order_id = $order->get_id();
				$payment_method = get_post_meta( $order_id, '_payment_method', true );
				$mwb_sfw_renewal_order = get_post_meta( $order_id, 'mwb_sfw_renewal_order', true );
				if ( 'stripe' == $payment_method && 'yes' == $mwb_sfw_renewal_order ) {
					$order_status[] = 'mwb_renewal';

				}
			}
			return apply_filters( 'mwb_sfw_add_subscription_order_statuses_for_payment_complete', $order_status, $order );

		}

		/**
		 * This function is used to attache customer for future payment.
		 *
		 * @name mwb_sfw_stripe_force_save_source
		 * @param bool   $force_save_source force_save_source.
		 * @param object $customer customer.
		 * @since    1.0.1
		 */
		public function mwb_sfw_stripe_force_save_source( $force_save_source, $customer = false ) {

			if ( ! $customer ) {
				return;
			}
			if ( ! $force_save_source && mwb_sfw_check_plugin_enable() ) {
				$force_save_source = true;
			}
			return $force_save_source;
		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name mwb_sfw_cancel_stripe_subscription
		 * @param int    $mwb_subscription_id mwb_subscription_id.
		 * @param string $status status.
		 * @since    1.0.1
		 */
		public function mwb_sfw_cancel_stripe_subscription( $mwb_subscription_id, $status ) {

			$mwb_payment_method = get_post_meta( $mwb_subscription_id, '_payment_method', true );
			if ( 'stripe' == $mwb_payment_method ) {
				if ( 'Cancel' == $status ) {
					mwb_sfw_send_email_for_cancel_susbcription( $mwb_subscription_id );
					update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
				}
			}
		}
	}
}
return new Mwb_Subscriptions_Payment_Stripe_Main();
