<?php
/**
 * The admin-specific cron functionality of the plugin.
 *
 * @link       https://wpswing.com
 * @since      1.6.4
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/package/gateways/wps-paypal/subscription-module-compatibility
 */

/**
 * The cron-specific functionality of the plugin admin side.
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 * @author      WP Swings <webmaster@wpswings.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Wps_Subscriptions_Payment_Wps_Paypal_Main' ) ) {

	/**
	 * Define class and module for cron.
	 */
	class Wps_Subscriptions_Payment_Wps_Paypal_Main {
		/**
		 * Constructor
		 */
		public function __construct() {

			if ( $this->wps_pifw_paypal_check_plugin_setup() ) {

				add_filter( 'wps_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'wps_pifw_paypal_payment_gateway_for_woocommerce' ), 10, 2 );
				add_action( 'wps_sfw_other_payment_gateway_renewal', array( $this, 'wps_pifw_process_subscription_payment' ), 10, 3 );
				add_action( 'wps_sfw_subscription_cancel', array( $this, 'wps_pifw_cancel_paypal_subscription' ), 10, 2 );
				add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'wps_pifw_add_order_statuses_for_payment_complete' ), 10, 2 );
			}
		}

		/**
		 * Check if plugin setting fully setup.
		 */
		public function wps_pifw_paypal_check_plugin_setup() {
			$flag = false;

			$saved_setting = get_option( 'woocommerce_wps_paypal_settings', array() );

			if ( ! empty( $saved_setting ) && is_array( $saved_setting ) ) {
				if ( isset( $saved_setting['enabled'] ) && 'yes' === $saved_setting['enabled'] ) {
					if ( isset( $saved_setting['client_id'] ) && isset( $saved_setting['client_secret'] ) ) {
						if ( ! empty( $saved_setting['client_id'] ) && ! empty( $saved_setting['client_secret'] ) ) {
							$flag = true;
						}
					}
				}
			}

			return $flag;
		}

		/**
		 * This function is add paypal payment gateway.
		 *
		 * @name wps_sfw_paypal_payment_gateway_for_woocommerce
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @since    1.6.4
		 */
		public function wps_pifw_paypal_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {

			if ( 'wps_paypal' === $payment_method ) {
				$supported_payment_method[] = $payment_method;
			}

			return $supported_payment_method;
		}

		/**
		 * Process subscription payment.
		 *
		 * @name wps_sfw_process_subscription_payment.
		 * @param object $order order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 * @since    1.6.4
		 */
		public function wps_pifw_process_subscription_payment( $order, $subscription_id, $payment_method ) {
			if ( $order && is_object( $order ) ) {
				$order_id              = $order->get_id();
				$payment_method        = get_post_meta( $order_id, '_payment_method', true );
				$wps_sfw_renewal_order = get_post_meta( $order_id, 'wps_sfw_renewal_order', true );

				if ( 'wps_paypal' === $payment_method && 'yes' === $wps_sfw_renewal_order ) {

					$wps_parent_order_id = get_post_meta( $subscription_id, 'wps_parent_order', true );

					$saved_setting = get_option( 'woocommerce_wps_paypal_settings', array() );
					$client_id     = null;
					$client_secret = null;
					$testmode      = false;
					if ( ! empty( $saved_setting ) && is_array( $saved_setting ) ) {
						if ( isset( $saved_setting['client_id'] ) ) {
							$client_id = $saved_setting['client_id'];
						}
						if ( isset( $saved_setting['client_secret'] ) ) {
							$client_secret = $saved_setting['client_secret'];
						}
						if ( isset( $saved_setting['testmode'] ) && 'yes' === $saved_setting['testmode'] ) {
							$testmode = true;
						}
					}

					require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/wps-paypal/class-wps-paypal-requests.php';
					wps_Paypal_Requests::$client_id     = $client_id;
					wps_Paypal_Requests::$client_secret = $client_secret;
					wps_Paypal_Requests::$testmode      = $testmode;
					$wps_request                        = new wps_Paypal_Requests( array() );
					$saved_token                        = get_post_meta( $wps_parent_order_id, 'wps_order_payment_token', true );
					if ( empty( $saved_token ) ) {
						$order_notes = __( 'payment token not found', 'wps-paypal-integration-for-woocommerce' );
						$order->update_status( 'failed', $order_notes );
						return;
					}
					$token_response = $wps_request->wps_validate_saved_customer_token( $saved_token );
					$token_flag     = false;
					if ( is_object( $token_response ) && isset( $token_response->status ) && 'CREATED' === $token_response->status && isset( $token_response->id ) && $saved_token === $token_response->id ) {
						$token_flag = true;
					}
					if ( $token_flag ) {
						$wps_request->create_renewal_payment( $saved_token, $order );
					} else {
						$order->update_status( 'failed', 'Token Verification failed !' );
					}
				}
			}
		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name wps_sfw_cancel_paypal_subscription
		 * @param string $wps_subscription_id wps_subscription_id.
		 * @param string $status status.
		 * @since 1.6.4
		 */
		public function wps_pifw_cancel_paypal_subscription( $wps_subscription_id, $status ) {
			$wps_payment_method = get_post_meta( $wps_subscription_id, '_payment_method', true );
			if ( 'wps_paypal' === $wps_payment_method ) {
				if ( 'Cancel' === $status ) {
					wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
					update_post_meta( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
				}
			}
		}

		/**
		 * This function is add subscription order status.
		 *
		 * @name wps_wsp_add_woocybs_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @since 1.6.4
		 * @return mixed
		 */
		public function wps_pifw_add_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {

				$order_id              = $order->get_id();
				$payment_method        = get_post_meta( $order_id, '_payment_method', true );
				$wps_sfw_renewal_order = get_post_meta( $order_id, 'wps_sfw_renewal_order', true );
				if ( 'wps_paypal' === $payment_method && 'yes' === $wps_sfw_renewal_order ) {
					$order_status[] = 'wps_renewal';

				}
			}
			return $order_status;

		}
	}
}
return new Wps_Subscriptions_Payment_Wps_Paypal_Main();
