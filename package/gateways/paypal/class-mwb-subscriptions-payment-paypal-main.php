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
		 * The mwb_sfw_request variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_request mwb_sfw_request.
		 */
		private $mwb_sfw_request;

		/**
		 * The mwb_sfw_parse_response variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_parse_response mwb_sfw_parse_response.
		 */
		private $mwb_sfw_parse_response = array();

		/**
		 * The mwb_sfw_response variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_response mwb_sfw_response.
		 */
		private $mwb_sfw_response = array();

		/**
		 * The mwb_sfw_invoice_prefix variable.
		 *
		 * @since   1.0.1

		 * @var $mwb_sfw_invoice_prefix mwb_sfw_invoice_prefix.
		 */
		private $mwb_sfw_invoice_prefix;



		/**
		 * Define the paypal functionality of the plugin.
		 *
		 * @since    1.0.1
		 */
		public function __construct() {

			if ( $this->mwb_sfw_paypal_check_settings() && $this->mwb_sfw_paypal_credential_set() ) {

				include SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/paypal/class-mwb-sfw-paypal-api-request.php';

				add_filter( 'woocommerce_paypal_args', array( $this, 'mwb_sfw_add_paypal_args' ), 10, 2 );

				add_action( 'valid-paypal-standard-ipn-request', array( $this, 'mwb_sfw_validate_process_ipn_request' ), 0 );

				// Express checkout.
				add_action( 'woocommerce_api_mwb_sfw_paypal', array( $this, 'mwb_sfw_handle_express_checkout_api' ) );

			}

			add_filter( 'mwb_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'mwb_sfw_paypal_payment_gateway_for_woocommerce' ), 10, 2 );
			add_action( 'mwb_sfw_other_payment_gateway_renewal', array( $this, 'mwb_sfw_process_subscription_payment' ), 10, 3 );
			add_action( 'mwb_sfw_subscription_cancel', array( $this, 'mwb_sfw_cancel_paypal_subscription' ), 10, 2 );
			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'mwb_sfw_add_order_statuses_for_payment_complete' ), 10, 2 );

			add_filter( 'woocommerce_paypal_express_checkout_needs_billing_agreement', array( $this, 'mwb_sfw_create_billing_agreement_for_express_checkout' ) );

		}

		/**
		 * Create Billing for subscription.
		 *
		 * @name mwb_sfw_create_billing_agreement_for_express_checkout.
		 * @param bool $mwb_create_billing mwb_create_billing.
		 * @since    1.0.2
		 */
		public function mwb_sfw_create_billing_agreement_for_express_checkout( $mwb_create_billing ) {
			if ( $this->mwb_sfw_check_paypal_express_enable() && ! $mwb_create_billing ) {
				if ( mwb_sfw_is_cart_has_subscription_product() ) {
					$mwb_create_billing = true;
				}
			}
			return $mwb_create_billing;
		}
		/**
		 * Process subscription payment.
		 *
		 * @name mwb_sfw_process_subscription_payment.
		 * @param object $order order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 * @since    1.0.2
		 */
		public function mwb_sfw_process_subscription_payment( $order, $subscription_id, $payment_method ) {

			if ( $order && is_object( $order ) ) {
				$order_id = $order->get_id();
				$payment_method = get_post_meta( $order_id, '_payment_method', true );
				$mwb_sfw_renewal_order = get_post_meta( $order_id, 'mwb_sfw_renewal_order', true );
				if ( 'paypal' == $payment_method && 'yes' == $mwb_sfw_renewal_order ) {
					if ( $this->mwb_sfw_paypal_check_settings() && $this->mwb_sfw_paypal_credential_set() ) {
						if ( mwb_sfw_check_valid_subscription( $subscription_id ) ) {
							$paypal_profile_id = get_post_meta( $subscription_id, '_mwb_paypal_subscription_id', true );

							if ( isset( $paypal_profile_id ) && ! empty( $paypal_profile_id ) ) {
								if ( $this->mwb_sfw_check_billing_id( $paypal_profile_id, 'billing_agreement' ) ) {

									if ( 0 == $order->get_total() ) {
										$order->payment_complete();

										return;
									}
									$response = $this->mwb_sfw_do_reference_transaction( $paypal_profile_id, $order );

									$this->mwb_sfw_process_payment_response( $order, $response );
								}
							}
						}
					}
				} elseif ( 'ppec_paypal' == $payment_method && 'yes' == $mwb_sfw_renewal_order ) {

					if ( mwb_sfw_check_valid_subscription( $subscription_id ) ) {
						$paypal_profile_id = get_post_meta( $subscription_id, '_mwb_paypal_subscription_id', true );

						if ( isset( $paypal_profile_id ) && ! empty( $paypal_profile_id ) ) {

							if ( 0 == $order->get_total() ) {
								$order->payment_complete();
								return;
							}
							if ( $this->mwb_sfw_check_paypal_express_enable() && class_exists( 'WC_Gateway_PPEC_With_PayPal_Addons' ) ) {

								update_post_meta( $order_id, '_ppec_billing_agreement_id', $paypal_profile_id );
								$paypal_obj = new WC_Gateway_PPEC_With_PayPal_Addons();
								$paypal_obj->scheduled_subscription_payment( $order->get_total(), $order );
								mwb_sfw_send_email_for_renewal_susbcription( $order_id );
							}
						}
					}
				}
			}
		}

		/**
		 * This function is add subscription order status.
		 *
		 * @name mwb_sfw_add_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @since    1.0.2
		 */
		public function mwb_sfw_add_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {

				$order_id = $order->get_id();
				$payment_method = get_post_meta( $order_id, '_payment_method', true );
				$mwb_sfw_renewal_order = get_post_meta( $order_id, 'mwb_sfw_renewal_order', true );
				if ( 'paypal' == $payment_method && 'yes' == $mwb_sfw_renewal_order ) {
					$order_status[] = 'mwb_renewal';
				}
				if ( 'ppec_paypal' == $payment_method && 'yes' == $mwb_sfw_renewal_order ) {
					$order_status[] = 'mwb_renewal';
				}
			}
			return $order_status;
		}

		/**
		 * This function is add paypal payment gateway.
		 *
		 * @name mwb_sfw_paypal_payment_gateway_for_woocommerce
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @since    1.0.2
		 */
		public function mwb_sfw_paypal_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {

			if ( 'paypal' == $payment_method ) {
				$supported_payment_method[] = $payment_method;
			}
			if ( 'ppec_paypal' == $payment_method ) {
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

			$this->mwb_sfw_invoice_prefix  = ( isset( $mwb_paypal_settings['invoice_prefix'] ) ) ? $mwb_paypal_settings['invoice_prefix'] : 'WC-';

			return $mwb_paypal_enable;

		}

		/**
		 * This function is used to check paypal express settings enable.
		 *
		 * @name mwb_sfw_check_paypal_express_enable
		 * @since    1.0.1
		 */
		public function mwb_sfw_check_paypal_express_enable() {
			$mwb_ppec_enable = true;
			$mwb_ppec_settings = get_option( 'woocommerce_ppec_paypal_settings' );
			if ( ! isset( $mwb_ppec_settings['enabled'] ) || 'yes' != $mwb_ppec_settings['enabled'] ) {
				$mwb_ppec_enable = false;
			}

			return $mwb_ppec_enable;
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
		 * This function is used to get request.
		 *
		 * @name mwb_get_new_request
		 * @since    1.0.2
		 */
		public function mwb_get_new_request() {
			return new Mwb_Sfw_Paypal_Api_Request( $this->mwb_sfw_api_username, $this->mwb_sfw_api_password, $this->mwb_sfw_api_signature, 124, $this->mwb_sfw_invoice_prefix );
		}

		/**
		 * This function is used to set express checkout.
		 *
		 * @name mwb_sfw_set_express_checkout.
		 * @param array $args args.
		 * @since    1.0.2
		 */
		public function mwb_sfw_set_express_checkout( $args ) {
			$request_obj = $this->mwb_get_new_request();

			$payments_args = $request_obj->mwb_sfw_get_express_checkout_param( $args );
			$response = $this->mwb_sfw_process_request( $payments_args );
			return $response;
		}

		/**
		 * This function is used to process request.
		 *
		 * @name mwb_sfw_process_request.
		 * @param array $payments_args payments_args.
		 * @since    1.0.2
		 */
		public function mwb_sfw_process_request( $payments_args ) {

			$response = $this->mwb_sfw_process_remote_request( $this->mwb_sfw_api_endpoint, $payments_args );
			$response = $this->mwb_sfw_process_api_response( $response );

			return $response;
		}

		/**
		 * This function is used to process request.
		 *
		 * @name mwb_sfw_process_api_response.
		 * @param array $response response.
		 * @throws Exception Return error.
		 * @since    1.0.2
		 */
		public function mwb_sfw_process_api_response( $response ) {

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message(), (int) $response->get_error_code() );
			}

			$response_body = wp_remote_retrieve_body( $response );

			parse_str( $response_body, $response_result );

			return $response_result;
		}

		/**
		 * This function is used to process request.
		 *
		 * @name mwb_sfw_process_remote_request.
		 * @param string $url url.
		 * @param array  $mwb_args mwb_args.
		 * @since    1.0.2
		 */
		public function mwb_sfw_process_remote_request( $url, $mwb_args ) {

			$args = array(
				'method'      => 'POST',
				'timeout'     => 45,
				'httpversion' => '1.0',
				'sslverify'   => true,
				'blocking'    => true,
				'user-agent'  => '',
				'headers'     => array(),
				'body'        => $mwb_args,
				'cookies'     => array(),
			);

			return wp_safe_remote_request( $url, $args );
		}

		/**
		 * This function is used to create url.
		 *
		 * @name mwb_sfw_get_callback_url.
		 * @param array $url url.
		 * @since    1.0.2
		 */
		public function mwb_sfw_get_callback_url( $url ) {
			return add_query_arg( 'action', $url, WC()->api_request_url( 'mwb_sfw_paypal' ) );
		}

		/**
		 * This function is used to get express checkout details.
		 *
		 * @name mwb_sfw_get_express_checkout_details.
		 * @param string $token token.
		 * @since    1.0.2
		 */
		public function mwb_sfw_get_express_checkout_details( $token ) {

			$request_obj = $this->mwb_get_new_request();
			$payments_args = $request_obj->mwb_sfw_get_express_checkout_params( $token );

			return $this->mwb_sfw_process_request( $payments_args );
		}

		/**
		 * This function is used to get order.
		 *
		 * @name get_paypal_order.
		 * @param string $mwb_args mwb_args.
		 * @since    1.0.2
		 */
		public function get_paypal_order( $mwb_args ) {

			$custom = json_decode( $mwb_args );
			if ( $custom && is_object( $custom ) ) {
				$order_id  = $custom->order_id;
				$order_key = $custom->order_key;
			} else {
				// Nothing was found.
				WC_Gateway_Paypal::log( 'Order ID and key were not found in "custom".', 'error' );
				return false;
			}

			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				// We have an invalid $order_id, probably because invoice_prefix has changed.
				$order_id = wc_get_order_id_by_order_key( $order_key );
				$order    = wc_get_order( $order_id );
			}

			if ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
				WC_Gateway_Paypal::log( 'Order Keys do not match.', 'error' );
				return false;
			}

			return $order;
		}

		/**
		 * Create billing agreement.
		 *
		 * @name mwb_sfw_create_billing_agreement.
		 * @param string $token token.
		 * @since    1.0.2
		 */
		public function mwb_sfw_create_billing_agreement( $token ) {

			$request_obj = $this->mwb_get_new_request();

			$payments_args = $request_obj->mwb_sfw_create_billing_agreement_params( $token );

			return $this->mwb_sfw_process_request( $payments_args );
		}

		/**
		 * Do exprss checkout.
		 *
		 * @name mwb_sfw_do_express_checkout.
		 * @param string $token token.
		 * @param object $order order.
		 * @param arraya $args args.
		 * @since    1.0.2
		 */
		public function mwb_sfw_do_express_checkout( $token, $order, $args ) {

			$request_obj = $this->mwb_get_new_request();

			$payments_args = $request_obj->mwb_sfw_do_express_checkout_params( $token, $order, $args );
			return $this->mwb_sfw_process_request( $payments_args );
		}

		/**
		 * Do reference  transaction.
		 *
		 * @name mwb_sfw_do_reference_transaction.
		 * @param string $reference_id reference_id.
		 * @param object $order order.
		 * @since    1.0.2
		 */
		public function mwb_sfw_do_reference_transaction( $reference_id, $order ) {

			$request_obj = $this->mwb_get_new_request();

			$payments_args = $request_obj->mwb_sfw_do_reference_transaction_params( $reference_id, $order );

			return $this->mwb_sfw_process_request( $payments_args );
		}

		/**
		 * Set paypal id
		 *
		 * @name mwb_sfw_set_paypal_id.
		 * @param object $order order.
		 * @param string $paypal_subscription_id paypal_subscription_id.
		 * @since    1.0.2
		 */
		public function mwb_sfw_set_paypal_id( $order, $paypal_subscription_id ) {

			if ( ! is_object( $order ) ) {
				$order = wc_get_order( $order );
			}
			$mwb_subscription_id = get_post_meta( $order->get_id(), 'mwb_subscription_id', true );
			if ( isset( $mwb_subscription_id ) && ! empty( $mwb_subscription_id ) ) {
				if ( ! in_array( $paypal_subscription_id, get_user_meta( $order->get_user_id(), '_paypal_subscription_id', false ) ) ) {
					add_user_meta( $order->get_user_id(), '_mwb_paypal_subscription_id', $paypal_subscription_id );
				}
				update_post_meta( $mwb_subscription_id, '_mwb_paypal_subscription_id', $paypal_subscription_id );

			}
		}

		/**
		 * Check transaction.
		 *
		 * @name mwb_sfw_check_transaction.
		 * @param array $response response.
		 * @since    1.0.2
		 */
		public function mwb_sfw_check_transaction( $response ) {

			return in_array( $this->mwb_sfw_get_payment_status( $response ), array( 'Completed', 'Processed' ) );
		}

		/**
		 * Check pending transaction.
		 *
		 * @name mwb_sfw_check_pending_transaction.
		 * @param array $response response.
		 * @since    1.0.2
		 */
		public function mwb_sfw_check_pending_transaction( $response ) {

			return in_array( $this->mwb_sfw_get_payment_status( $response ), array( 'Pending' ) );
		}

		/**
		 * Get  transaction status.
		 *
		 * @name mwb_sfw_get_payment_status.
		 * @param array $response response.
		 * @since    1.0.2
		 */
		public function mwb_sfw_get_payment_status( $response ) {

			return $this->mwb_sfw_get_payment_parameter( $response, 'PAYMENTSTATUS' );
		}
		/**
		 * Get transaction id.
		 *
		 * @name get_transaction_id.
		 * @param array $response response.
		 * @since    1.0.2
		 */
		public function get_transaction_id( $response ) {

			return $this->mwb_sfw_get_payment_parameter( $response, 'TRANSACTIONID' );
		}

		/**
		 * Get transaction paramenter.
		 *
		 * @name mwb_sfw_get_payment_parameter.
		 * @param array  $response response.
		 * @param string $name name.
		 * @since    1.0.2
		 */
		public function mwb_sfw_get_payment_parameter( $response, $name ) {
			if ( isset( $response[ 'PAYMENTINFO_0_' . $name ] ) ) {

				return $response[ 'PAYMENTINFO_0_' . $name ];
			} elseif ( isset( $response[ $name ] ) ) {
				return $response[ $name ];
			} else {
				return null;
			}
		}

		/**
		 * Check pappal id.
		 *
		 * @name mwb_sfw_check_billing_id.
		 * @param string $profile_id profile_id.
		 * @param string $profile_type profile_type.
		 * @since    1.0.2
		 */
		public function mwb_sfw_check_billing_id( $profile_id, $profile_type ) {

			if ( 'billing_agreement' === $profile_type && 'B-' == substr( $profile_id, 0, 2 ) ) {
				$result = true;
			} elseif ( 'out_of_date_id' === $profile_type && 'S-' == substr( $profile_id, 0, 2 ) ) {
				$result = true;
			} else {
				$result = false;
			}
			return $result;
		}

		/**
		 * Process payment.
		 *
		 * @name mwb_sfw_process_payment_response.
		 * @param object $order order.
		 * @param array  $response response.
		 * @since    1.0.2
		 */
		public function mwb_sfw_process_payment_response( $order, $response ) {

			if ( isset( $response['ACK'] ) && 'Success' != $response['ACK'] ) {

				$order->update_status( 'failed' );
			} elseif ( $this->mwb_sfw_check_transaction( $response ) ) {
				// translators: placeholder is a transaction ID.
				$order->add_order_note( sprintf( __( 'PayPal payment approved (ID: %s)', 'subscriptions-for-woocommerce' ), $this->get_transaction_id( $response ) ) );

				$order->payment_complete( $this->get_transaction_id( $response ) );
				$order_id = $order->get_id();
				mwb_sfw_send_email_for_renewal_susbcription( $order_id );
			} elseif ( $this->mwb_sfw_check_pending_transaction( $response ) ) {
				$order_note   = sprintf( __( 'PayPal Transaction Held:', 'subscriptions-for-woocommerce' ) );
				$order->update_status( 'on-hold', $order_note );
			}

		}

		/**
		 * Check api response.
		 *
		 * @name mwb_sfw_handle_express_checkout_api.
		 * @throws Exception Return error.
		 * @since    1.0.2
		 */
		public function mwb_sfw_handle_express_checkout_api() {

			if ( ! isset( $_GET['action'] ) ) {
				return;
			}
			if ( isset( $_GET['action'] ) && 'create_billing_agreement' == $_GET['action'] ) {
				if ( ! isset( $_GET['token'] ) ) {
					return;
				}
					$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
				try {
					$mwb_sfw_express_checkout_response = $this->mwb_sfw_get_express_checkout_details( $token );
					if ( ! isset( $mwb_sfw_express_checkout_response['BILLINGAGREEMENTACCEPTEDSTATUS'] ) ) {
						return;
					}
					$mwb_sfw_express_response = $mwb_sfw_express_checkout_response['BILLINGAGREEMENTACCEPTEDSTATUS'];

					if ( 1 == $mwb_sfw_express_response ) {
						$order_data = isset( $mwb_sfw_express_checkout_response['CUSTOM'] ) ? $mwb_sfw_express_checkout_response['CUSTOM'] : '';
						$order = $this->get_paypal_order( $order_data );

						if ( is_null( $order ) ) {
							throw new Exception( __( 'Unable to find order for PayPal billing agreement.', 'subscriptions-for-woocommerce' ) );
						}
						if ( $order->get_total() > 0 && ! mwb_sfw_check_valid_subscription( $order->get_id() ) ) {

							$payments_args = array(
								'payment_action' => 'Sale',
								'payer_id'       => isset( $mwb_sfw_express_checkout_response['PAYERID'] ) ? $mwb_sfw_express_checkout_response['PAYERID'] : '',
							);

							$mwb_sfw_billing_response = $this->mwb_sfw_do_express_checkout( $token, $order, $payments_args );

						} else {

							$mwb_sfw_billing_response = $this->mwb_sfw_create_billing_agreement( $token );
						}

						if ( isset( $mwb_sfw_billing_response['ACK'] ) && 'Success' != $mwb_sfw_billing_response['ACK'] ) {

							return;
						}

						$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
						$payment_method     = isset( $available_gateways['paypal'] ) ? $available_gateways['paypal'] : false;
						$order->set_payment_method( $payment_method );

						if ( isset( $mwb_sfw_billing_response['BILLINGAGREEMENTID'] ) ) {
							$this->mwb_sfw_set_paypal_id( $order, $mwb_sfw_billing_response['BILLINGAGREEMENTID'] );
						}

						if ( ! mwb_sfw_check_valid_subscription( $order->get_id() ) ) {
							if ( 0 == $order->get_total() ) {
								$order->payment_complete();
							} else {
								$this->mwb_sfw_process_payment_response( $order, $mwb_sfw_billing_response );
							}
						}
						wp_safe_redirect( $order->get_checkout_order_received_url() );
						exit;
					} else {
						wp_safe_redirect( wc_get_cart_url() );
						exit;
					}
				} catch ( Exception $e ) {

					wc_add_notice( __( 'An error occurred, please try again or try an alternate form of payment.', 'subscriptions-for-woocommerce' ), 'error' );

					wp_redirect( wc_get_cart_url() );
					exit;
				}
			}
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
			$mwb_order_has_susbcription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );
			if ( 'yes' != $mwb_order_has_susbcription ) {
				return $mwb_args;
			}
			// Set express checkout.
			$response = $this->mwb_sfw_set_express_checkout(
				array(
					'currency'   => $mwb_args['currency_code'],
					'return_url' => $this->mwb_sfw_get_callback_url( 'create_billing_agreement' ),
					'cancel_url' => $mwb_args['cancel_return'],
					'notify_url' => $mwb_args['notify_url'],
					'custom'     => $mwb_args['custom'],
					'order'      => $order,
				)
			);

			$mwb_args = array(
				'cmd'   => '_express-checkout',
				'token' => $response['TOKEN'],
			);
			return $mwb_args;

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
			$paypal_profile_id = get_post_meta( $mwb_subscription_id, '_mwb_paypal_subscription_id', true );

			if ( isset( $paypal_profile_id ) && ! empty( $paypal_profile_id ) ) {
				if ( $this->mwb_sfw_check_billing_id( $paypal_profile_id, 'billing_agreement' ) ) {
					if ( 'Cancel' == $status ) {
						mwb_sfw_send_email_for_cancel_susbcription( $mwb_subscription_id );
						update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
					}
				}
			} elseif ( isset( $mwb_sfw_paypal_subscriber_id ) && ! empty( $mwb_sfw_paypal_subscriber_id ) && $this->mwb_sfw_paypal_check_settings() ) {
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
			} else {
				return;
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

