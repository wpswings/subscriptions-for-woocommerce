<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://wpswings.com
 * @since      2.2.6
 *
 * @package     subscriptions-for-woocommerce
 * @subpackage  subscriptions-for-woocommerce/package
 */

/**
 * The Payment-specific functionality of the plugin admin side.
 *
 * @package     subscriptions-for-woocommerce
 * @subpackage  subscriptions-for-woocommerce/package
 * @author      wpswings <webmaster@wpswings.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! class_exists( 'Wps_Subscriptions_Payment_Payfast_Main' ) ) {

	/**
	 * Define class and module for woo stripe.
	 */
	class Wps_Subscriptions_Payment_Payfast_Main {
		/**
		 * Constructor
		 */
		public function __construct() {

			if ( $this->wps_sfw_check_woo_payfast_enable() && wps_sfw_check_plugin_enable() ) {

				add_action( 'wps_sfw_subscription_cancel', array( $this, 'wps_sfw_cancel_payfast_subscription' ), 10, 2 );
				add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'wps_sfw_payfast_payment_order_statuses_for_payment_complete' ), 10, 2 );

				add_filter( 'wps_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'wps_sfw_payfast_payment_gateway_for_woocommerce' ), 10, 2 );
				add_action( 'wps_sfw_other_payment_gateway_renewal', array( $this, 'wps_sfw_woo_payfast_process_subscription_payment' ), 10, 3 );

				add_action( 'woocommerce_api_wc_gateway_payfast', array( $this, 'wps_sfw_save_payfast_token' ) );

				add_filter( 'woocommerce_gateway_payfast_payment_data_to_send', array( $this, 'wps_sfw_payfast_payment_data_to_send_modify' ), 10, 2 );
			}
		}

		/**
		 * This function is add subscription order status.
		 *
		 * @name wps_sfw_payfast_payment_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @return mixed
		 */
		public function wps_sfw_payfast_payment_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {
				$order_id = $order->get_id();

				$payment_method = $order->get_payment_method();
				$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
				if ( $this->wps_sfw_check_supported_payment_options( $payment_method ) && 'yes' == $wps_sfw_renewal_order ) {
					$order_status[] = 'wps_renewal';
				}
			}
			return apply_filters( 'wps_sfw_add_subscription_order_statuses_for_payment_complete', $order_status, $order );
		}
		/**
		 * Allow payment method.
		 *
		 * @name wps_sfw_payfast_payment_gateway_for_woocommerce.
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @return array
		 */
		public function wps_sfw_payfast_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {
			if ( $this->wps_sfw_check_supported_payment_options( $payment_method ) ) {
				$supported_payment_method[] = $payment_method;
			}
			return apply_filters( 'wps_sfw_supported_payment_payfast', $supported_payment_method, $payment_method );
		}

		/**
		 * Check supported payment method.
		 *
		 * @name wps_sfw_check_supported_payment_options
		 * @param string $payment_method payment_method.
		 * @return boolean
		 */
		public function wps_sfw_check_supported_payment_options( $payment_method ) {
			$result = false;
			if ( 'payfast' == $payment_method ) {
				$result = true;
			}
			return $result;
		}

		/**
		 * Check woo payfast enable.
		 *
		 * @name wps_sfw_check_woo_payfast_enable
		 * @return boolean
		 */
		public function wps_sfw_check_woo_payfast_enable() {
			$activated = false;
			if ( in_array( 'woocommerce-payfast-gateway/woocommerce-gateway-payfast.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$activated = true;
			}
			return $activated;
		}

		/**
		 * Process subscription payment.
		 *
		 * @name wps_sfw_woo_cybs_process_subscription_payment.
		 * @param object $order order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 * @return void
		 */
		public function wps_sfw_woo_payfast_process_subscription_payment( $order, $subscription_id, $payment_method ) {
			if ( strpos( $payment_method, 'payfast' ) !== false ) {
				if ( ! $order ) {
					return;
				}
				$payment_method_obj   = '';
				$wps_enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();
				if ( isset( $wps_enabled_gateways[ $payment_method ] ) ) {
					$payment_method_obj = $wps_enabled_gateways[ $payment_method ];
				}
				if ( empty( $payment_method_obj ) ) {
					return;
				}
				if ( class_exists( 'WC_Gateway_PayFast' ) ) {
					$payfast_gateway_object = new WC_Gateway_PayFast();
					$parent_id = wps_sfw_get_meta_data( $subscription_id, 'wps_parent_order', true );
					$token     = wps_sfw_get_meta_data( $parent_id, 'wps_sfw_user_token', true );
					$items     = $order->get_items();
					if ( empty( $items ) ) {
						return get_bloginfo( 'name' );
					}
					$item = array_shift( $items );
					$item_description = json_encode( array( 'renewal_order_id' => self::get_order_prop( $order, 'id' ) ) );

					$response = $payfast_gateway_object->submit_ad_hoc_payment( $token, $order->get_total(), $item['name'], $item_description );

					if ( is_wp_error( $response ) ) {
						/* translators: 1: error code 2: error message */
						$order->update_status( 'failed', sprintf( __( 'PayFast Subscription renewal transaction failed (%1$s:%2$s)', 'subscriptions-for-woocommerce' ), $response->get_error_code(), $response->get_error_message() ) );
					} else {
						// Payment will be completion will be capture only when the ITN callback is sent to $this->handle_itn_request().
						$order->add_order_note( __( 'PayFast Subscription renewal transaction submitted.', 'subscriptions-for-woocommerce' ) );
						$order->update_status( 'processing' );
					}
				}
			}
		}

		/**
		 * Get order property with compatibility check on order getter introduced
		 * in WC 3.0.
		 *
		 * @param WC_Order $order Order object.
		 * @param string   $prop  Property name.
		 *
		 * @return mixed Property value
		 */
		public static function get_order_prop( $order, $prop ) {
			switch ( $prop ) {
				case 'order_total':
					$getter = array( $order, 'get_total' );
					break;
				default:
					$getter = array( $order, 'get_' . $prop );
					break;
			}
			return is_callable( $getter ) ? call_user_func( $getter ) : $order->{ $prop };
		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name wps_sfw_cancel_payfast_subscription
		 * @param int    $wps_subscription_id wps_subscription_id.
		 * @param string $status status.
		 * @return void
		 */
		public function wps_sfw_cancel_payfast_subscription( $wps_subscription_id, $status ) {

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$subscription = new WPS_Subscription( $wps_subscription_id );
				$wps_payment_method = $subscription->get_payment_method();
			} else {
				$wps_payment_method = get_post_meta( $wps_subscription_id, '_payment_method', true );
			}
			if ( $this->wps_sfw_check_supported_payment_options( $wps_payment_method ) ) {
				if ( class_exists( 'WC_Gateway_PayFast' ) ) {
					$payfast_gateway_object = new WC_Gateway_PayFast();
					$parent_id              = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_parent_order', true );
					$token                  = wps_sfw_get_meta_data( $parent_id, 'wps_sfw_user_token', true );
					$payfast_gateway_object->api_request( 'cancel', $token, array(), 'PUT' );
					if ( 'Cancel' == $status ) {
						wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
						wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
					}
				}
			}
		}
		/**
		 * Saved the token for the future payment from the webhook response
		 */
		public function wps_sfw_save_payfast_token() {

			if ( isset( $_GET['wc-api'] ) && 'WC_Gateway_PayFast' === $_GET['wc-api'] ) {
				$data      = $_POST;
				$token     = sanitize_text_field( $data['token'] );
				$parent_id = sanitize_text_field( $data['custom_str3'] );
				$subscription_id    = wps_sfw_get_meta_data( $parent_id, 'wps_subscription_id', true );
				if ( ! empty( $subscription_id ) ) {
					wps_sfw_update_meta_data( $parent_id, 'wps_sfw_user_token', $token );
					Subscriptions_For_Woocommerce_Log::log( 'WPS Payfast Webhook Data Order #' . $parent_id . ' Data :-' . wc_print_r( $data, true ) );
				}
			}
		}

		/**
		 * Add args, so it can return a token for future payments
		 *
		 * @param array $args .
		 * @param int   $order_id .
		 */
		public function wps_sfw_payfast_payment_data_to_send_modify( $args, $order_id ) {
			$order = wc_get_order( $order_id );
			if ( function_exists( 'wps_sfw_order_has_subscription' ) && wps_sfw_order_has_subscription( $order ) ) {
				$args['subscription_type'] = '2';
			}
			return $args;
		}
	}
}
new Wps_Subscriptions_Payment_Payfast_Main();
