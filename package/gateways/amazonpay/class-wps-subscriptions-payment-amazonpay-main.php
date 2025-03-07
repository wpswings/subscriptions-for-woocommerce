<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://wpswings.com
 * @since      2.0.0
 *
 * @package     subscriptions-for-woocommerce
 * @subpackage  subscriptions-for-woocommerce/package
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * The Payment-specific functionality of the plugin admin side.
 *
 * @package     subscriptions-for-woocommerce
 * @subpackage  subscriptions-for-woocommerce/package
 * @author      WP Swings <webmaster@wpswings.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wps_Subscriptions_Payment_Amazonpay_Main' ) ) {
	/**
	 * Define class and module for Amazon Pay Method.
	 */
	class Wps_Subscriptions_Payment_Amazonpay_Main {

		/**
		 * Constructor
		 */
		public function __construct() {

			add_filter( 'wps_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'wps_amazon_pay_payment_integration_gateway' ), 10, 2 );
			add_action( 'wps_sfw_subscription_cancel', array( $this, 'wps_sfw_cancel_amazon_pay_subscription' ), 10, 2 );
			add_action( 'wps_sfw_other_payment_gateway_renewal', array( $this, 'wps_sfw_process_amazonpay_payment_renewal' ), 10, 3 );
			add_filter( 'woocommerce_amazon_pa_processed_order', array( $this, 'wps_sfw_copy_meta_to_sub' ), 10, 2 );
			add_filter( 'woocommerce_amazon_pa_update_checkout_session_payload', array( $this, 'wps_recurring_checkout_session_update' ), 10, 4 );
			add_action( 'woocommerce_amazon_pa_refresh_cached_charge_permission_status', array( $this, 'wps_sfw_propagate_status_update_to_related_callback' ), 1, 3 );
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'wps_sfw_validation_for_amazon_on_checkout' ), 10, 1 );
		}

		/**
		 * Validation for 0 trail subscription in case of amazon pay
		 *
		 * @param array $posted as posted value.
		 */
		public function wps_sfw_validation_for_amazon_on_checkout( $posted ) {

			$error = false;
			if ( 'amazon_payments_advanced' == $posted['payment_method'] ) {

				$wps_recurring_total = 0;
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					$wps_recurring_total += $cart_item['line_total'];
				}
				if ( 0 == $wps_recurring_total ) {

					foreach ( WC()->cart->get_cart() as $cart_item ) {
						$product_id = $cart_item['product_id'];
						$_wps_sfw_product = wps_sfw_get_meta_data( $product_id, '_wps_sfw_product', true );

						if ( 'yes' == $_wps_sfw_product ) {
							$wps_sfw_subscription_initial_signup_price = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_initial_signup_price', true );
							$wps_sfw_subscription_free_trial_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_number', true );
							if ( 0 == $wps_recurring_total && empty( $wps_sfw_subscription_initial_signup_price ) && $wps_sfw_subscription_free_trial_number > 0 ) {
								$error = true;
							}
						}
					}
				}
			}

			if ( $error ) {
				 wc_add_notice( __( 'Amazon pay will not work for subscription if your order total is 0. Choose Another Payment Method ', 'subscriptions-for-woocommerce' ), 'error' );
			}
		}

		/**
		 * Wps_sfw_propagate_status_update_to_related_callback function
		 *
		 * @param array $_order as order.
		 * @param array $charge_permission_id as charge permision id.
		 * @param array $charge_permission_status as status.
		 * @return void
		 */
		public function wps_sfw_propagate_status_update_to_related_callback( $_order, $charge_permission_id, $charge_permission_status ) {
			$order = $_order;
			$order_id = $order->get_id();
			$parent_order = $order;
			$wps_subscription_id = wps_sfw_get_meta_data( $order_id, 'wps_subscription_id', true );
			if ( wps_sfw_check_valid_subscription( $wps_subscription_id ) ) {

				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$wps_subscription_order = new WPS_Subscription( $wps_subscription_id );
				} else {
					$wps_subscription_order = wc_get_order( $wps_subscription_id );
				}
				$this->wps_sfw_handle_order_propagation( $order_id, $charge_permission_id, $charge_permission_status );
				$this->wps_sfw_handle_order_propagation( $wps_subscription_order, $charge_permission_id, $charge_permission_status );
			}
		}

		/**
		 * Wps_sfw_handle_order_propagation function
		 *
		 * @param array $wps_subscription_id as subscription id.
		 * @param array $charge_permission_id as permission id.
		 * @param array $charge_permission_status as status.
		 * @return void
		 */
		public function wps_sfw_handle_order_propagation( $wps_subscription_id, $charge_permission_id, $charge_permission_status ) {
			$current_charge_permission_id = WC_Amazon_Payments_Advanced::get_order_charge_permission( $wps_subscription_id );
			if ( $current_charge_permission_id !== $charge_permission_id ) {
				return;
			}
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$rel_order = new WPS_Subscription( $wps_subscription_id );
			} else {
				$rel_order = wc_get_order( $wps_subscription_id );
			}
			$old_status = wc_apa()->get_gateway()->get_cached_charge_permission_status( $rel_order, true )->status;
			$new_status = $charge_permission_status->status; // phpcs:ignore WordPress.NamingConventions

			wps_sfw_update_meta_data( $wps_subscription_id, 'amazon_charge_permission_status', wp_json_encode( $charge_permission_status ) );
			$rel_order->save();
		}

		/**
		 * Wps_recurring_checkout_session_update function
		 *
		 * @param array $payload as payload.
		 * @param array $checkout_session_id as session id.
		 * @param array $order as order.
		 * @param array $doing_classic_payment as old method.
		 * @return array
		 */
		public function wps_recurring_checkout_session_update( $payload, $checkout_session_id, $order, $doing_classic_payment ) {

			$order_id = $order->get_id();
			$wps_subscription_id = wps_sfw_get_meta_data( $order_id, 'wps_subscription_id', true );
			if ( wps_sfw_check_valid_subscription( $wps_subscription_id ) ) {

				if ( isset( $_POST['_wcsnonce'] ) && isset( $_POST['woocommerce_change_payment'] ) && $order->get_id() === absint( $_POST['woocommerce_change_payment'] ) ) {
						$checkout_session = wc_apa()->get_gateway()->get_checkout_session();

						$payload['paymentDetails']['paymentIntent'] = 'Confirm';
						unset( $payload['paymentDetails']['canHandlePendingAuthorization'] );

						$payload['paymentDetails']['chargeAmount'] = WC_Amazon_Payments_Advanced::format_amount( $checkout_session->recurringMetadata->amount ); // phpcs:ignore WordPress.NamingConventions

						return $payload;
				}

				$wps_sfw_subscription_interval = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_interval', true );
				$wps_sfw_subscription_number = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_number', true );

				$wps_sfw_subscription_interval = ucfirst( $wps_sfw_subscription_interval );

				$settings = WC_Amazon_Payments_Advanced_API::get_settings();
				$redirect_url = null;
				if ( is_null( $redirect_url ) ) {
					if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
						$parts        = wp_parse_url( home_url() );
						$path         = ! empty( $parts['path'] ) ? $parts['path'] : '';
						$redirect_url = "{$parts['scheme']}://{$parts['host']}{$path}" . add_query_arg( null, null );
					} else {
						$redirect_url = get_permalink( wc_get_page_id( 'checkout' ) );
					}
				}
				$redirect_url = add_query_arg( 'amazon_payments_advanced', 'true', $redirect_url );

				$site_name = WC_Amazon_Payments_Advanced::get_site_name();

				$wps_recurring_total = 0;
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					$wps_recurring_total += $cart_item['line_total'] + $cart_item['line_tax'];
				}
				$check_shipping = get_option( 'wsp_allow_shipping_subscription', '' );
				$wps_shipping_charge = 0;
				if ( 'on' === $check_shipping && apply_filters( 'wsp_sfw_check_pro_plugin', false ) ) {
					$order_shipping = $order->get_items( 'shipping' );
					foreach ( $order_shipping as $item_id => $item ) {
						$item_data = $item->get_data();

						$shipping_data_total = $item_data['total'];

						$shipping_data_taxes = $item_data['taxes'];

						$wps_shipping_charge = $shipping_data_total + $shipping_data_taxes['total'][1];
					}
				}
				if ( $wps_shipping_charge > 0 ) {

					$wps_recurring_total += $wps_shipping_charge;
				}

				$wps_recurring_total = wc_format_decimal( $wps_recurring_total, '' );

				$payload = array(
					'storeId' => $settings['store_id'],
					'platformId' => 'A1BVJDFFHQ7US4',
					'webCheckoutDetails' => array(
						'checkoutMode' => 'ProcessOrder',
						'checkoutResultReturnUrl' => site_url() . '/checkout/order-pay/' . $order_id . '/?pay_for_order=true&key=' . $order->get_order_key() . '&amazon_payments_advanced=true&amazon_return_classic=1',
					),
					'chargePermissionType' => 'Recurring',
					'recurringMetadata' => array(
						'frequency' => array(
							'unit' => $wps_sfw_subscription_interval,
							'value' => $wps_sfw_subscription_number,
						),

						'amount' => array(
							'amount' => $wps_recurring_total,
							'currencyCode' => 'USD',
						),

					),

					'paymentDetails' => array(
						'paymentIntent' => 'AuthorizeWithCapture',
						'canHandlePendingAuthorization' => '',
						'chargeAmount' => array(
							'amount' => $wps_recurring_total,
							'currencyCode' => 'USD',
						),

					),

					'merchantMetadata' => array(
						'merchantReferenceId' => $order_id,
						'customInformation' => 'Created by WC_Gateway_Amazon_Pay/2.4.1 (Platform=WooCommerce/7.8.1)',
						'merchantStoreName' => $site_name,
					),
				);
				return $payload;
			} else {
				return $payload;
			}
		}


		/**
		 * Copy meta from order to the relevant subscriptions
		 *
		 * @param WC_Order $order Order object.
		 * @param object   $response Response from the API.
		 */
		public function wps_sfw_copy_meta_to_sub( $order, $response ) {
			$order_id = $order->get_id();
			$wps_subscription_id = wps_sfw_get_meta_data( $order_id, 'wps_subscription_id', true );
			if ( wps_sfw_check_valid_subscription( $wps_subscription_id ) ) {

				$perm_status = wc_apa()->get_gateway()->get_cached_charge_permission_status( $order, true );

				$meta_keys_to_copy = array(
					'amazon_charge_permission_id',
					'amazon_charge_permission_status',
					'amazon_payment_advanced_version',
					'woocommerce_version',
				);
				wc_apa()->get_gateway()->log_charge_permission_status_change( $order, $response->chargePermissionId );

				foreach ( $meta_keys_to_copy as $key ) {
					$value = $order->get_meta( $key );
					if ( empty( $value ) ) {
						continue;
					}
					wps_sfw_update_meta_data( $wps_subscription_id, $key, $value );
				}
			}
		}

		/**
		 * Replace the main gateway with the sources gateway.
		 *
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @since 2.0.0
		 * @return array
		 */
		public function wps_amazon_pay_payment_integration_gateway( $supported_payment_method, $payment_method ) {

			if ( strpos( $payment_method, 'amazon_payments_advanced' ) !== false ) {

					$supported_payment_method[] = $payment_method;

			}
			return $supported_payment_method;
		}
		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name mwb_sfw_cancel_eway_subscription
		 * @param int    $wps_subscription_id mwb_subscription_id.
		 * @param string $status status.
		 * @since    1.0.1
		 */
		public function wps_sfw_cancel_amazon_pay_subscription( $wps_subscription_id, $status ) {

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$subscription = new WPS_Subscription( $wps_subscription_id );
				$wps_payment_method = $subscription->get_payment_method();
			} else {
				$wps_payment_method = get_post_meta( $wps_subscription_id, '_payment_method', true );
			}
			if ( strpos( $wps_payment_method, 'amazon_payments_advanced' ) !== false ) {
				if ( 'Cancel' == $status ) {
					wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
					wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
				}
			}
		}

		/**
		 * Wps_sfw_process_amazonpay_payment_renewal function
		 *
		 * @param array $renewal_order as renewal order.
		 * @param array $subscription_id as subscription id.
		 * @param array $payment_method as payment method.
		 * @return void
		 */
		public function wps_sfw_process_amazonpay_payment_renewal( $renewal_order, $subscription_id, $payment_method ) {

			if ( strpos( $payment_method, 'amazon_payments_advanced' ) !== false ) {
				if ( ! $renewal_order ) {
					return;
				}

				$perm_status = wc_apa()->get_gateway()->get_cached_charge_permission_status( $renewal_order, true );
				$currency = wc_apa_get_order_prop( $renewal_order, 'order_currency' );
				$order_id        = $renewal_order->get_id();
				$can_do_async         = ( 'async' === WC_Amazon_Payments_Advanced_API::get_settings( 'authorization_mode' ) );

				$charge_permission_id = wps_sfw_get_meta_data( $subscription_id, 'amazon_charge_permission_id', true );

				$response = WC_Amazon_Payments_Advanced_API::create_charge(
					$charge_permission_id,
					array(
						'merchantMetadata'              => WC_Amazon_Payments_Advanced_API::get_merchant_metadata( $order_id ),
						'captureNow'                    => true,
						'canHandlePendingAuthorization' => $can_do_async,
						'chargeAmount'                  => array(
							'amount'       => $renewal_order->get_total(),
							'currencyCode' => $currency,
						),
					)
				);

				if ( is_wp_error( $response ) ) {
					wc_apa()->log( "Error processing payment for renewal order #{$order_id}. Charge Permission ID: {$charge_permission_id}", $response );
					/* translators: 1) Reason. */
					$renewal_order->add_order_note( sprintf( __( 'Amazon Pay subscription renewal failed - %s', 'subscriptions-for-woocommerce' ), $response->get_error_message() ) );
					wc_apa()->get_gateway()->log_charge_permission_status_change( $renewal_order );
					$renewal_order->update_status( 'failed' );
					return;
				} else {
					$renewal_order->update_status( 'processing' );
					$renewal_order->set_transaction_id( $response->chargeId );
					// Save the order to apply changes.
					$renewal_order->save();
				}

				wc_apa()->get_gateway()->log_charge_permission_status_change( $renewal_order );
				wc_apa()->get_gateway()->log_charge_status_change( $renewal_order, $response );

			}
		}
	}

}
return new Wps_Subscriptions_Payment_Amazonpay_Main();
