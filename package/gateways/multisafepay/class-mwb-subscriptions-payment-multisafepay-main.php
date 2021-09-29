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
if ( ! class_exists( 'Mwb_Subscriptions_Payment_Multisafepay_Main' ) ) {

	/**
	 * Define class and module for multisafepay.
	 */
	class Mwb_Subscriptions_Payment_Multisafepay_Main {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'mwb_sfw_other_payment_gateway_renewal', array( $this, 'mwb_sfw_multisafepay_renewal_order' ), 20, 3 );
			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'mwb_sfw_add_multisafepay_order_statuses_for_payment_complete' ), 10, 2 );
		}

		/**
		 * Function name mwb_sfw_multisafepay_renewal_order.
		 * this function is used to create renewal order using multisafepay.
		 *
		 * @param object $mwb_new_order order object.
		 * @param int    $subscription_id subscription id.
		 * @param mixed  $payment_method payment method.
		 * @return void
		 */
		public function mwb_sfw_multisafepay_renewal_order( $mwb_new_order, $subscription_id, $payment_method  ) {
			if ( 'multisafepay_multisafepay' === $payment_method ) {
				global $woocommerce;
				$notification_url = get_rest_url( get_current_blog_id(), 'multisafepay/v1/notification' );
				$check_url = $mwb_new_order->get_checkout_order_received_url();
				$cancel_url = wp_specialchars_decode( $mwb_new_order->get_cancel_order_url() );

				$parent_order_id = get_post_meta( $subscription_id, 'mwb_parent_order', true );
				$method_title = get_post_meta( $parent_order_id, '_payment_method_title', true );
				$parent_payment_method = get_post_meta( $parent_order_id, '_payment_method', true );
				$default_description = sprintf( _x( 'Orders with %s', 'data sent to multisafepay', 'subscriptions-for-woocommerce' ), get_bloginfo( 'name' ) );

				$current_order_id = $mwb_new_order->get_id();
				$payment_options = array(
					'notification_url' => $notification_url,
					'redirect_url' => $check_url,
					'cancel_url'   => $cancel_url,
				);

				if ( get_option( 'multisafepay_testmode', false ) ) {
					$api_url = 'https://testapi.multisafepay.com/v1/json/orders/';
					$api_key = get_option( 'multisafepay_test_api_key', false );
				} else {
					$api_url = 'https://api.multisafepay.com/v1/json/orders/';
					$api_key = get_option( 'multisafepay_api_key', false );
				}
				$url = $api_url . $parent_order_id;

				$request = array(
					'method'      => 'GET',
					'timeout'     => 120,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'cookies'     => array(),
					'headers' => array( 'api_key' => $api_key ),
				);
				$main_url = esc_url_raw(  $url );
				$response = wp_remote_get( $main_url, $request );
				if ( ! is_wp_error( $response ) ) {
					$response = wp_remote_retrieve_body( $response );
					$previous_payment_details = json_decode( $response, true );
					$recurring_id = $previous_payment_details['data']['payment_details']['recurring_id'];
					$gateway = $previous_payment_details['data']['payment_methods']['type'];
					$customer = array(
						'locale' => $previous_payment_details['data']['customer']['locale'],
						'ip_address' => $mwb_new_order->get_customer_ip_address(),
						'forwarded_ip' => $mwb_new_order->get_customer_ip_address(),
						'first_name' => $mwb_new_order->get_billing_first_name(),
						'last_name' => $mwb_new_order->get_billing_last_name(),
						'gender'    => null,
						'birthday' => null,
						'address1' => $mwb_new_order->get_billing_address_1(),
						'address2' => $mwb_new_order->get_billing_address_2(),
						'house_number' => null,
						'zip_code' => $mwb_new_order->get_billing_postcode(),
						'city' => $mwb_new_order->get_billing_city(),
						'country' => $mwb_new_order->get_billing_country(),
						'phone' => $mwb_new_order->get_billing_phone(),
						'email' => $mwb_new_order->get_billing_email(),
						'user_agent' => $mwb_new_order->get_customer_user_agent(),
						'referrer' => get_site_url(),
						'reference' => null,
					);
					$recurring_param = array(
						'type' => 'direct',
						'gateway' => $gateway,
						'order_id' => $current_order_id,
						'currency'  => $mwb_new_order->get_currency(),
						'recurring_model' => 'subscription',
						'recurring_id' => $recurring_id,
						'recurring_flow' => 'token',
						'amount' => ( $mwb_new_order->get_total() * 100 ),
						'description' => $default_description,
						'payment_options' => $payment_options,
						'customer' => $customer,
					);
					$recurring_url = $api_url;
					$args = array(
						'method' => 'POST',
						'body' => wp_json_encode( $recurring_param ),
						'headers' => array( 'api_key' => $api_key ),

					);

					$data = wp_remote_post( $recurring_url, $args );

					if ( ! is_wp_error( $data ) ) {
						$data = wp_remote_retrieve_body( $data );
						$final_payment_details = json_decode( $data, true );
						if ( true === $final_payment_details['success'] ) {
							$transaction_id = $final_payment_details['data']['transaction_id'];
							if ( $transaction_id && ( 'completed' === $final_payment_details['data']['status'] ) ) {
								$order_note = sprintf( __( 'Multisafepay Renewal Transaction Successful (%s)', 'subscriptions-for-woocommerce' ), $transaction_id );
								$mwb_new_order->add_order_note( $order_note );
								update_post_meta( $current_order_id, '_payment_method_title', $method_title );
								update_post_meta( $current_order_id, '_payment_method', $parent_payment_method );
								$mwb_new_order->payment_complete( $transaction_id );
							}
						}
					} else {
						wp_mail( get_option( 'admin_email' ), __( 'Recurring payment error', 'subscriptions-for-woocommerce' ), wp_json_encode( $data ) );
					}
				} else {
					wp_mail( get_option( 'admin_email' ), __( 'Order fecthing error', 'subscriptions-for-woocommerce' ), wp_json_encode( $response ) );
				}
			}

		}


		/**
		 * This function is add subscription order status.
		 *
		 * @name mwb_sfw_add_multisafepay_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @since    1.0.2
		 */
		public function mwb_sfw_add_multisafepay_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {

				$order_id = $order->get_id();
				$payment_method = get_post_meta( $order_id, '_payment_method', true );
				$mwb_sfw_renewal_order = get_post_meta( $order_id, 'mwb_sfw_renewal_order', true );
				if ( 'multisafepay' == substr( $payment_method, 0, 12 ) && 'yes' == $mwb_sfw_renewal_order ) {
					$order_status[] = 'mwb_renewal';

				}
			}
			return apply_filters( 'mwb_sfw_add_subscription_order_statuses_for_payment_complete', $order_status, $order );

		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name mwb_sfw_cancel_multisafepay_subscription
		 * @param int    $mwb_subscription_id mwb_subscription_id.
		 * @param string $status status.
		 * @since    1.0.1
		 */
		public function mwb_sfw_cancel_multisafepay_subscription( $mwb_subscription_id, $status ) {

			$mwb_payment_method = get_post_meta( $mwb_subscription_id, '_payment_method', true );
			if ( 'multisafepay' == substr( $mwb_payment_method, 0, 12 ) ) {
				if ( 'Cancel' == $status ) {
					mwb_sfw_send_email_for_cancel_susbcription( $mwb_subscription_id );
					update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
				}
			}
		}
	}
}
return new Mwb_Subscriptions_Payment_Multisafepay_Main();
