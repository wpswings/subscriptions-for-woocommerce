<?php
/**
 * The admin-specific paypal functionality of the plugin.
 *
 * @link       https://makewebbetter.com
 * @since      1.0.1
 *
 * @package     Subscriptions_For_Woocommerce

 * @subpackage  Subscriptions_For_Woocommerce/includes
 */

/**

 * The paypal-specific functionality of the public side.
 *
 * @package     Subscriptions_For_Woocommerce

 * @subpackage  Subscriptions_For_Woocommerce/includes

 * @author      makewebbetter <webmaster@makewebbetter.com>
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

if ( ! class_exists( 'Mwb_Subscriptions_Payment_Paypal_Main' ) ) {

	/**
	 * Define class and module for paypal.
	 */
	class Mwb_Subscriptions_Payment_Paypal_Main {

		/**
		 * The mwb_wclog variable.
		 *
		 * @since   1.0.1
		 * @var $mwb_wclog mwb_wclog.
		 */

		private $mwb_wclog = '';

		/**
		 * The mwb_debug variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_debug mwb_debug.
		 */
		private $mwb_debug;

		/**
		 * The mwb_sfw_testmode variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_testmode mwb_sfw_testmode.
		 */
		private $mwb_sfw_testmode;

		/**
		 * The mwb_sfw_email variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_email mwb_sfw_email.
		 */
		private $mwb_sfw_email;

		/**
		 * The mwb_sfw_receiver_email variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_receiver_email mwb_sfw_receiver_email.
		 */
		private $mwb_sfw_receiver_email;

		/**
		 * The mwb_sfw_api_username variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_api_username mwb_sfw_api_username.
		 */

		private $mwb_sfw_api_username;

		/**
		 * The mwb_sfw_api_password variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_api_password mwb_sfw_api_password.
		 */
		private $mwb_sfw_api_password;

		/**
		 * The mwb_sfw_api_signature variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_api_signature mwb_sfw_api_signature.
		 */
		private $mwb_sfw_api_signature;

		/**
		 * The mwb_sfw_api_endpoint variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_api_endpoint mwb_sfw_api_endpoint.
		 */
		private $mwb_sfw_api_endpoint;

		/**
		 * Define the paypal functionality of the plugin.
		 *
		 * @since    1.0.1
		 */
		public function __construct() {

			if ( $this->mwb_sfw_paypal_check_settings() && $this->mwb_sfw_paypal_credential_set() ) {

				add_filter( 'mwb_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'mwb_sfw_paypal_payment_gateway_for_woocommerce' ), 10, 2 );

				add_filter( 'woocommerce_paypal_args', array( $this, 'mwb_sfw_add_paypal_args' ), 10, 2 );

				add_action( 'valid-paypal-standard-ipn-request', array( $this, 'mwb_sfw_validate_process_ipn_request' ), 0 );
				add_action( 'mwb_sfw_subscription_cancel', array( $this, 'mwb_sfw_cancel_paypal_subscription' ), 10, 2 );

			}

		}

		/**
		 * This function is add paypal payment gateway.
		 *
		 * @name mwb_sfw_paypal_payment_gateway_for_woocommerce
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @since    1.0.1
		 */
		public function mwb_sfw_paypal_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {

			if ( 'paypal' == $payment_method ) {
				$supported_payment_method[] = $payment_method;
			}

			return $supported_payment_method;
		}

		/**
		 * This function is used to validate paypal response.
		 *
		 * @name mwb_sfw_validate_process_ipn_request
		 * @param array $mwb_transaction_details mwb_transaction_details.
		 * @since    1.0.1
		 */
		public function mwb_sfw_validate_process_ipn_request( $mwb_transaction_details ) {

			if ( ! isset( $mwb_transaction_details['txn_type'] ) ) {
					return;
			}
			include_once WC()->plugin_path() . '/includes/gateways/paypal/includes/class-wc-gateway-paypal-ipn-handler.php';
			include_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/paypal/class-mwb-sfw-paypal-ipn-handler.php';

				WC_Gateway_Paypal::log( 'MWB Subscription Transaction Type: ' . $mwb_transaction_details['txn_type'] );
				// show the data in log file.
				WC_Gateway_Paypal::log( 'MWB Subscription Transaction Details: ' . wc_print_r( $mwb_transaction_details, true ) );

			if ( class_exists( 'MWB_Sfw_PayPal_IPN_Handler' ) ) {
				$mwb_paypal_obj = new MWB_Sfw_PayPal_IPN_Handler( $this->mwb_sfw_testmode, $this->mwb_sfw_receiver_email );

				$mwb_paypal_obj->mwb_sfw_valid_response( $mwb_transaction_details );
			}

		}


		/**
		 * This function is used to check paypal settings.
		 *
		 * @name mwb_sfw_paypal_check_settings
		 * @since    1.0.1
		 */
		public function mwb_sfw_paypal_check_settings() {

			$mwb_paypal_enable = true;

			$mwb_paypal_settings = get_option( 'woocommerce_paypal_settings' );

			if ( ! isset( $mwb_paypal_settings['enabled'] ) || 'yes' != $mwb_paypal_settings['enabled'] ) {

				$mwb_paypal_enable = false;

			}
			$this->mwb_debug           = ( isset( $mwb_paypal_settings['debug'] ) && 'yes' == $mwb_paypal_settings['debug'] ) ? true : false;

			$this->mwb_sfw_testmode        = ( isset( $mwb_paypal_settings['testmode'] ) && 'yes' == $mwb_paypal_settings['testmode'] ) ? true : false;

			$this->mwb_sfw_email           = ( isset( $mwb_paypal_settings['email'] ) ) ? $mwb_paypal_settings['email'] : '';

			$this->mwb_sfw_receiver_email  = ( isset( $mwb_paypal_settings['receiver_email'] ) ) ? $mwb_paypal_settings['receiver_email'] : $this->mwb_sfw_email;

			return $mwb_paypal_enable;

		}


		/**
		 * This function is used to get paypal credenstial.
		 *
		 * @name mwb_sfw_paypal_credential_set
		 * @since    1.0.1
		 */
		public function mwb_sfw_paypal_credential_set() {

			$mwb_credential_set = false;

			$mwb_paypal_settings = get_option( 'woocommerce_paypal_settings' );

			if ( ! empty( $mwb_paypal_settings ) ) {

				if ( isset( $mwb_paypal_settings['testmode'] ) && 'yes' == $mwb_paypal_settings['testmode'] ) {

					if ( '' != $mwb_paypal_settings['sandbox_api_username'] && '' != $mwb_paypal_settings['sandbox_api_password'] && '' != $mwb_paypal_settings['sandbox_api_signature'] ) {

						 $this->mwb_sfw_api_username = $mwb_paypal_settings['sandbox_api_username'];
						 $this->mwb_sfw_api_password = $mwb_paypal_settings['sandbox_api_password'];
						 $this->mwb_sfw_api_signature = $mwb_paypal_settings['sandbox_api_signature'];
						 $this->mwb_sfw_api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
						$mwb_credential_set = true;

					}
				} else {

					if ( '' != $mwb_paypal_settings['api_username'] && '' != $mwb_paypal_settings['api_password'] && '' != $mwb_paypal_settings['api_signature'] ) {

						$this->mwb_sfw_api_username = $mwb_paypal_settings['api_username'];
						$this->mwb_sfw_api_password = $mwb_paypal_settings['api_password'];
						$this->mwb_sfw_api_signature = $mwb_paypal_settings['api_signature'];
						$this->mwb_sfw_api_endpoint = 'https://api-3t.paypal.com/nvp';

						$mwb_credential_set = true;

					}
				}
			}

			return $mwb_credential_set;
		}

		/**
		 * This function is used to add paypal args for subscriptions.
		 *
		 * @name mwb_sfw_add_paypal_args
		 * @param array  $mwb_args mwb_args.
		 * @param object $order order.
		 * @since    1.0.1
		 */
		public function mwb_sfw_add_paypal_args( $mwb_args, $order ) {

			if ( empty( $order ) ) {

				return $mwb_args;
			}

			$order_id = $order->get_id();

			$mwb_is_renewal_order = get_post_meta( $order_id, 'mwb_sfw_renewal_order', true );

			$mwb_upgrade_downgrade_order = get_post_meta( $order_id, 'mwb_upgrade_downgrade_order', true );

			if ( 'yes' == $mwb_is_renewal_order ) {
				return $mwb_args;
			}

			if ( mwb_sfw_check_valid_subscription( $order_id ) ) {
				$mwb_susbcription_free_trial = get_post_meta( $order_id, 'mwb_susbcription_trial_end', true );
				if ( isset( $mwb_susbcription_free_trial ) && ! empty( $mwb_susbcription_free_trial ) ) {
					$mwb_subscription_id = $order_id;
				}
			} elseif ( 'yes' == $mwb_upgrade_downgrade_order ) {
				$mwb_subscription_id = get_post_meta( $order_id, 'mwb_subscription_id', true );
				$mwb_old_subscription_id = get_post_meta( $order_id, 'mwb_old_subscription_id', true );
				$mwb_old_subscription_data = get_post_meta( $mwb_old_subscription_id, 'mwb_upgrade_downgrade_data', true );

				update_post_meta( $mwb_subscription_id, 'mwb_upgrade_downgrade_data', $mwb_old_subscription_data );

				if ( empty( $mwb_subscription_id ) ) {
					return $mwb_args;
				}
			} else {
				$mwb_order_has_susbcription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );
				if ( 'yes' != $mwb_order_has_susbcription ) {
					return $mwb_args;
				}

				$mwb_subscription_id = get_post_meta( $order_id, 'mwb_subscription_id', true );
				if ( empty( $mwb_subscription_id ) ) {

					return $mwb_args;

				}
			}
			do_action( 'mwb_sfw_paypal_order', $mwb_args, $order );
			/*check for valid subscription*/
			if ( ! mwb_sfw_check_valid_subscription( $mwb_subscription_id ) ) {

				return $mwb_args;
			}
			$susbcription = wc_get_order( $mwb_subscription_id );

			$mwb_order_items = $susbcription->get_items();

			if ( empty( $mwb_order_items ) ) {

				return $mwb_args;

			}

			$mwb_chk_susbcription = false;

			$mwb_item_names = array();

			foreach ( $mwb_order_items as $key => $order_item ) {

				$product_id = ( $order_item['variation_id'] ) ? $order_item['variation_id'] : $order_item['product_id'];

				$product    = wc_get_product( $product_id );

				if ( mwb_sfw_check_product_is_subscription( $product ) ) {

					// It is initialized as susbcription.

					$mwb_args['cmd']      = '_xclick-subscriptions';

					// reattempt failed payments use 0 for not.

					$mwb_args['sra'] = 1;
					$mwb_sfw_subscription_interval = get_post_meta( $mwb_subscription_id, 'mwb_sfw_subscription_interval', true );
					$mwb_price_frequency = $this->mwb_sfw_get_reccuring_time_interval_for_paypal( $mwb_sfw_subscription_interval );
					$mwb_price_is_per = get_post_meta( $mwb_subscription_id, 'mwb_sfw_subscription_number', true );
					$mwb_sfw_subscription_expiry_number = get_post_meta( $mwb_subscription_id, 'mwb_sfw_subscription_expiry_number', true );

					$mwb_schedule_start = get_post_meta( $mwb_subscription_id, 'mwb_schedule_start', true );

					$mwb_susbcription_trial_end = get_post_meta( $mwb_subscription_id, 'mwb_susbcription_trial_end', true );
					$mwb_susbcription_trial_end = mwb_sfw_susbcription_trial_date( $mwb_subscription_id, $mwb_schedule_start );
					update_post_meta( $mwb_subscription_id, 'mwb_susbcription_trial_end', $mwb_susbcription_trial_end );

					if ( isset( $mwb_sfw_subscription_expiry_number ) && ! empty( $mwb_sfw_subscription_expiry_number ) ) {

						$mwb_susbcription_end = mwb_sfw_susbcription_expiry_date( $mwb_subscription_id, $mwb_schedule_start, $mwb_susbcription_trial_end );
						update_post_meta( $mwb_subscription_id, 'mwb_susbcription_end', $mwb_susbcription_end );

						$mwb_subscription_num = ( $mwb_sfw_subscription_expiry_number ) ? $mwb_sfw_subscription_expiry_number / $mwb_price_is_per : '';
					} else {
						$mwb_subscription_num = '';

					}
					$mwb_free_trial_num = get_post_meta( $mwb_subscription_id, 'mwb_sfw_subscription_free_trial_number', true );

					// order total.
					if ( $mwb_free_trial_num > 0 ) {

						$mwb_free_trial_frequency = get_post_meta( $mwb_subscription_id, 'mwb_sfw_subscription_free_trial_interval', true );

						$mwb_free_trial_frequency = $this->mwb_sfw_get_reccuring_time_interval_for_paypal( $mwb_free_trial_frequency );
						$mwb_args['a1'] = wc_format_decimal( $order->get_total(), 2 );

						$mwb_args['p1'] = $mwb_free_trial_num;

						$mwb_args['t1'] = $mwb_free_trial_frequency;

					}

					$mwb_args['a3'] = wc_format_decimal( $susbcription->get_total(), 2 );

					$mwb_args['p3'] = $mwb_price_is_per;

					$mwb_args['t3'] = $mwb_price_frequency;

					if ( '' == $mwb_subscription_num || $mwb_subscription_num > 1 ) {

						$mwb_args['src'] = 1;

						if ( '' != $mwb_subscription_num ) {

							$mwb_args['srt'] = $mwb_subscription_num;

						}
					} else {

						$mwb_args['src'] = 0;

					}

					$mwb_chk_susbcription = true;

				}
				if ( $order_item['qty'] > 1 ) {

					$mwb_item_names[] = $order_item['qty'] . ' x ' . $this->mwb_format_item_name( $order_item['name'] );

				} else {

					$mwb_item_names[] = $this->mwb_format_item_name( $order_item['name'] );

				}
			}

			if ( ! $mwb_chk_susbcription ) {

				return $mwb_args;

			}

			if ( count( $mwb_item_names ) > 1 ) {
				/* translators: %s: order number */
				$mwb_args['item_name'] = $this->mwb_format_item_name( sprintf( __( 'Order %s', 'subscriptions-for-woocommerce' ), $order->get_order_number() . ' - ' . implode( ', ', $mwb_item_names ) ) );

			} else {

				$mwb_args['item_name'] = implode( ', ', $mwb_item_names );

			}
			$mwb_args['rm'] = 2;
			// show the data in log file.
			WC_Gateway_Paypal::log( 'MWB - Subscription Request: ' . wc_print_r( $mwb_args, true ) );

			return apply_filters( 'mwb_sfw_paypal_args_data', $mwb_args, $order );

		}

		/**
		 * This function is used to add format item for subscriptions.
		 *
		 * @name mwb_format_item_name
		 * @param string $item_name item_name.
		 * @since    1.0.1
		 */
		public function mwb_format_item_name( $item_name ) {

			if ( strlen( $item_name ) > 127 ) {

				$item_name = substr( $item_name, 0, 124 ) . '...';

			}

			return html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );

		}

		/**
		 * This function is used to change subscriptions status.
		 *
		 * @name mwb_sfw_change_paypal_subscription_status
		 * @param string $profile_id profile_id.
		 * @param string $action action.
		 * @since    1.0.1
		 */
		public function mwb_sfw_change_paypal_subscription_status( $profile_id, $action ) {
			$mwb_sfw_api_request = 'USER=' . urlencode( $this->mwb_sfw_api_username )
						. '&PWD=' . urlencode( $this->mwb_sfw_api_password )
						. '&SIGNATURE=' . urlencode( $this->mwb_sfw_api_signature )
						. '&VERSION=76.0'
						. '&METHOD=ManageRecurringPaymentsProfileStatus'
						. '&PROFILEID=' . urlencode( $profile_id )
						. '&ACTION=' . urlencode( $action )
						/* translators: %s: subscription status */
						. '&NOTE=' . urlencode( sprintf( __( 'MWB Subscription %s', 'subscriptions-for-woocommerce' ), strtolower( $action ) ) );

			$url = $this->mwb_sfw_api_endpoint;
			$request = array(
				'httpversion' => '1.0',
				'sslverify'   => false,
				'method'      => 'POST',
				'timeout'     => 45,
				'body'        => $mwb_sfw_api_request,
			);

			$response = wp_remote_post( $url, $request );

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				echo esc_html( 'Something went wrong' );
				WC_Gateway_Paypal::log( 'MWB - Change Paypal status for ' . $profile_id . ' has been Failed: ' . $error_message );
			} else {
				$response    = wp_remote_retrieve_body( $response );
				// show the data in log file.
				WC_Gateway_Paypal::log( 'MWB - Change Paypal status for ' . $profile_id . ' has been successfull: ' . wc_print_r( $response, true ) );
				parse_str( $response, $parsed_response );
				return $parsed_response;
			}

			return $response;

		}
		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name mwb_sfw_cancel_paypal_subscription
		 * @param string $mwb_subscription_id mwb_subscription_id.
		 * @param string $status status.
		 * @since    1.0.1
		 */
		public function mwb_sfw_cancel_paypal_subscription( $mwb_subscription_id, $status ) {
			$mwb_sfw_paypal_subscriber_id = get_post_meta( $mwb_subscription_id, 'mwb_sfw_paypal_subscriber_id', true );

			if ( ! isset( $mwb_sfw_paypal_subscriber_id ) || empty( $mwb_sfw_paypal_subscriber_id ) ) {
				return;
			}
			$response = $this->mwb_sfw_change_paypal_subscription_status( $mwb_sfw_paypal_subscriber_id, $status );

			if ( ! empty( $response ) ) {
				if ( 'Failure' == $response['ACK'] ) {

					 WC_Gateway_Paypal::log( 'MWB - Change Paypal status for ' . $mwb_sfw_paypal_subscriber_id . ' has been Failed: ' . $response['L_LONGMESSAGE0'] );
				} elseif ( 'Success' == $response['ACK'] ) {
					if ( 'Cancel' == $status ) {
						mwb_sfw_send_email_for_cancel_susbcription( $mwb_subscription_id );
						update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
					}
				}
			}
		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name mwb_sfw_get_reccuring_time_interval_for_paypal
		 * @param string $mwb_reccuring_period mwb_reccuring_period.
		 * @since    1.0.1
		 */
		public function mwb_sfw_get_reccuring_time_interval_for_paypal( $mwb_reccuring_period ) {
			$mwb_converted_period = 'D';
			switch ( strtolower( $mwb_reccuring_period ) ) {
				case 'day':
					$mwb_converted_period = 'D';
					break;
				case 'week':
					$mwb_converted_period = 'W';
					break;
				case 'month':
					$mwb_converted_period = 'M';
					break;
				case 'year':
					$mwb_converted_period = 'Y';
					break;
				default:
			}

			return $mwb_converted_period;
		}

	}
}
return new Mwb_Subscriptions_Payment_Paypal_Main();

