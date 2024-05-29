<?php
/**
 * The admin-specific paypal functionality of the plugin.
 *
 * @link       https://wpswings.com
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

 * @author      WP Swings <webmaster@wpswings.com>
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

if ( ! class_exists( 'Wps_Subscriptions_Payment_Paypal_Main' ) ) {

	/**
	 * Define class and module for paypal.
	 */
	class Wps_Subscriptions_Payment_Paypal_Main {

		/**
		 * The wps_wclog variable.
		 *
		 * @since   1.0.1
		 * @var $wps_wclog wps_wclog.
		 */

		private $wps_wclog = '';

		/**
		 * The wps_debug variable.
		 *
		 * @since   1.0.1

		 * @var $wps_debug wps_debug.
		 */
		private $wps_debug;

		/**
		 * The wps_sfw_testmode variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_testmode wps_sfw_testmode.
		 */
		private $wps_sfw_testmode;

		/**
		 * The wps_sfw_email variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_email wps_sfw_email.
		 */
		private $wps_sfw_email;

		/**
		 * The wps_sfw_receiver_email variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_receiver_email wps_sfw_receiver_email.
		 */
		private $wps_sfw_receiver_email;

		/**
		 * The wps_sfw_api_username variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_api_username wps_sfw_api_username.
		 */

		private $wps_sfw_api_username;

		/**
		 * The wps_sfw_api_password variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_api_password wps_sfw_api_password.
		 */
		private $wps_sfw_api_password;

		/**
		 * The wps_sfw_api_signature variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_api_signature wps_sfw_api_signature.
		 */
		private $wps_sfw_api_signature;

		/**
		 * The wps_sfw_api_endpoint variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_api_endpoint wps_sfw_api_endpoint.
		 */
		private $wps_sfw_api_endpoint;

		/**
		 * The wps_sfw_request variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_request wps_sfw_request.
		 */
		private $wps_sfw_request;

		/**
		 * The wps_sfw_parse_response variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_parse_response wps_sfw_parse_response.
		 */
		private $wps_sfw_parse_response = array();

		/**
		 * The wps_sfw_response variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_response wps_sfw_response.
		 */
		private $wps_sfw_response = array();

		/**
		 * The wps_sfw_invoice_prefix variable.
		 *
		 * @since   1.0.1

		 * @var $wps_sfw_invoice_prefix wps_sfw_invoice_prefix.
		 */
		private $wps_sfw_invoice_prefix;



		/**
		 * Define the paypal functionality of the plugin.
		 *
		 * @since    1.0.1
		 */
		public function __construct() {

			if ( $this->wps_sfw_paypal_check_settings() && $this->wps_sfw_paypal_credential_set() ) {

				include SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/paypal/class-wps-sfw-paypal-api-request.php';

				add_filter( 'woocommerce_paypal_args', array( $this, 'wps_sfw_add_paypal_args' ), 10, 2 );

				add_action( 'valid-paypal-standard-ipn-request', array( $this, 'wps_sfw_validate_process_ipn_request' ), 0 );

				// Express checkout.
				add_action( 'woocommerce_api_wps_sfw_paypal', array( $this, 'wps_sfw_handle_express_checkout_api' ) );

			}

			add_filter( 'wps_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'wps_sfw_paypal_payment_gateway_for_woocommerce' ), 10, 2 );
			add_action( 'wps_sfw_other_payment_gateway_renewal', array( $this, 'wps_sfw_process_subscription_payment' ), 10, 3 );
			add_action( 'wps_sfw_subscription_cancel', array( $this, 'wps_sfw_cancel_paypal_subscription' ), 10, 2 );
			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'wps_sfw_add_order_statuses_for_payment_complete' ), 10, 2 );

			add_filter( 'woocommerce_paypal_express_checkout_needs_billing_agreement', array( $this, 'wps_sfw_create_billing_agreement_for_express_checkout' ) );
		}

		/**
		 * Create Billing for subscription.
		 *
		 * @name wps_sfw_create_billing_agreement_for_express_checkout.
		 * @param bool $wps_create_billing wps_create_billing.
		 * @since    1.0.2
		 */
		public function wps_sfw_create_billing_agreement_for_express_checkout( $wps_create_billing ) {
			if ( $this->wps_sfw_check_paypal_express_enable() && ! $wps_create_billing ) {
				if ( wps_sfw_is_cart_has_subscription_product() ) {
					$wps_create_billing = true;
				}
			}
			return $wps_create_billing;
		}
		/**
		 * Process subscription payment.
		 *
		 * @name wps_sfw_process_subscription_payment.
		 * @param object $order order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 * @since    1.0.2
		 */
		public function wps_sfw_process_subscription_payment( $order, $subscription_id, $payment_method ) {
			if ( $order && is_object( $order ) ) {
				$order_id = $order->get_id();

				$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );

				if ( 'paypal' == $payment_method && 'yes' == $wps_sfw_renewal_order ) {
					if ( $this->wps_sfw_paypal_check_settings() && $this->wps_sfw_paypal_credential_set() ) {
						if ( wps_sfw_check_valid_subscription( $subscription_id ) ) {
							$paypal_profile_id = wps_sfw_get_meta_data( $subscription_id, '_wps_paypal_subscription_id', true );

							if ( isset( $paypal_profile_id ) && ! empty( $paypal_profile_id ) ) {
								if ( $this->wps_sfw_check_billing_id( $paypal_profile_id, 'billing_agreement' ) ) {

									if ( 0 == $order->get_total() ) {
										$order->payment_complete();

										return;
									}
									$response = $this->wps_sfw_do_reference_transaction( $paypal_profile_id, $order );

									$this->wps_sfw_process_payment_response( $order, $response );
								}
							}
						}
					}
				} elseif ( 'ppec_paypal' == $payment_method && 'yes' == $wps_sfw_renewal_order ) {
					if ( wps_sfw_check_valid_subscription( $subscription_id ) ) {
						$paypal_profile_id = wps_sfw_get_meta_data( $subscription_id, '_wps_paypal_subscription_id', true );

						if ( isset( $paypal_profile_id ) && ! empty( $paypal_profile_id ) ) {

							if ( 0 == $order->get_total() ) {
								$order->payment_complete();
								return;
							}
							if ( $this->wps_sfw_check_paypal_express_enable() && class_exists( 'WC_Gateway_PPEC_With_PayPal_Addons' ) ) {

								wps_sfw_update_meta_data( $order_id, '_ppec_billing_agreement_id', $paypal_profile_id );
								$paypal_obj = new WC_Gateway_PPEC_With_PayPal_Addons();
								$paypal_obj->scheduled_subscription_payment( $order->get_total(), $order );
								wps_sfw_send_email_for_renewal_susbcription( $order_id );
							}
						}
					}
				}
			}
		}

		/**
		 * This function is add subscription order status.
		 *
		 * @name wps_sfw_add_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @since    1.0.2
		 */
		public function wps_sfw_add_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {

				$order_id = $order->get_id();

				$payment_method = $order->get_payment_method();

				$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
				if ( 'paypal' == $payment_method && 'yes' == $wps_sfw_renewal_order ) {
					$order_status[] = 'wps_renewal';
				}
				if ( 'ppec_paypal' == $payment_method && 'yes' == $wps_sfw_renewal_order ) {
					$order_status[] = 'wps_renewal';
				}
			}
			return $order_status;
		}

		/**
		 * This function is add paypal payment gateway.
		 *
		 * @name wps_sfw_paypal_payment_gateway_for_woocommerce
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @since    1.0.2
		 */
		public function wps_sfw_paypal_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {

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
		 * @name wps_sfw_validate_process_ipn_request
		 * @param array $wps_transaction_details wps_transaction_details.
		 * @since    1.0.1
		 */
		public function wps_sfw_validate_process_ipn_request( $wps_transaction_details ) {

			if ( ! isset( $wps_transaction_details['txn_type'] ) ) {
					return;
			}
			include_once WC()->plugin_path() . '/includes/gateways/paypal/includes/class-wc-gateway-paypal-ipn-handler.php';
			include_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/paypal/class-wps-sfw-paypal-ipn-handler.php';

				WC_Gateway_Paypal::log( 'WPS Subscription Transaction Type: ' . $wps_transaction_details['txn_type'] );
				// show the data in log file.
				WC_Gateway_Paypal::log( 'WPS Subscription Transaction Details: ' . wc_print_r( $wps_transaction_details, true ) );

			if ( class_exists( 'WPS_Sfw_PayPal_IPN_Handler' ) ) {
				$wps_paypal_obj = new WPS_Sfw_PayPal_IPN_Handler( $this->wps_sfw_testmode, $this->wps_sfw_receiver_email );

				$wps_paypal_obj->wps_sfw_valid_response( $wps_transaction_details );
			}
		}


		/**
		 * This function is used to check paypal settings.
		 *
		 * @name wps_sfw_paypal_check_settings
		 * @since    1.0.1
		 */
		public function wps_sfw_paypal_check_settings() {

			$wps_paypal_enable = true;

			$wps_paypal_settings = get_option( 'woocommerce_paypal_settings' );

			if ( ! isset( $wps_paypal_settings['enabled'] ) || 'yes' != $wps_paypal_settings['enabled'] ) {

				$wps_paypal_enable = false;

			}
			$this->wps_debug           = ( isset( $wps_paypal_settings['debug'] ) && 'yes' == $wps_paypal_settings['debug'] ) ? true : false;

			$this->wps_sfw_testmode        = ( isset( $wps_paypal_settings['testmode'] ) && 'yes' == $wps_paypal_settings['testmode'] ) ? true : false;

			$this->wps_sfw_email           = ( isset( $wps_paypal_settings['email'] ) ) ? $wps_paypal_settings['email'] : '';

			$this->wps_sfw_receiver_email  = ( isset( $wps_paypal_settings['receiver_email'] ) ) ? $wps_paypal_settings['receiver_email'] : $this->wps_sfw_email;

			$this->wps_sfw_invoice_prefix  = ( isset( $wps_paypal_settings['invoice_prefix'] ) ) ? $wps_paypal_settings['invoice_prefix'] : 'WC-';

			return $wps_paypal_enable;
		}

		/**
		 * This function is used to check paypal express settings enable.
		 *
		 * @name wps_sfw_check_paypal_express_enable
		 * @since    1.0.1
		 */
		public function wps_sfw_check_paypal_express_enable() {
			$wps_ppec_enable = true;
			$wps_ppec_settings = get_option( 'woocommerce_ppec_paypal_settings' );
			if ( ! isset( $wps_ppec_settings['enabled'] ) || 'yes' != $wps_ppec_settings['enabled'] ) {
				$wps_ppec_enable = false;
			}

			return $wps_ppec_enable;
		}


		/**
		 * This function is used to get paypal credenstial.
		 *
		 * @name wps_sfw_paypal_credential_set
		 * @since    1.0.1
		 */
		public function wps_sfw_paypal_credential_set() {

			$wps_credential_set = false;

			$wps_paypal_settings = get_option( 'woocommerce_paypal_settings' );

			if ( ! empty( $wps_paypal_settings ) ) {

				if ( isset( $wps_paypal_settings['testmode'] ) && 'yes' == $wps_paypal_settings['testmode'] ) {

					if ( '' != $wps_paypal_settings['sandbox_api_username'] && '' != $wps_paypal_settings['sandbox_api_password'] && '' != $wps_paypal_settings['sandbox_api_signature'] ) {

						$this->wps_sfw_api_username = $wps_paypal_settings['sandbox_api_username'];
						$this->wps_sfw_api_password = $wps_paypal_settings['sandbox_api_password'];
						$this->wps_sfw_api_signature = $wps_paypal_settings['sandbox_api_signature'];
						$this->wps_sfw_api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
						$wps_credential_set = true;

					}
				} elseif ( '' != $wps_paypal_settings['api_username'] && '' != $wps_paypal_settings['api_password'] && '' != $wps_paypal_settings['api_signature'] ) {

						$this->wps_sfw_api_username = $wps_paypal_settings['api_username'];
						$this->wps_sfw_api_password = $wps_paypal_settings['api_password'];
						$this->wps_sfw_api_signature = $wps_paypal_settings['api_signature'];
						$this->wps_sfw_api_endpoint = 'https://api-3t.paypal.com/nvp';

						$wps_credential_set = true;
				}
			}

			return $wps_credential_set;
		}

		/**
		 * This function is used to get request.
		 *
		 * @name wps_get_new_request
		 * @since    1.0.2
		 */
		public function wps_get_new_request() {
			return new Wps_Sfw_Paypal_Api_Request( $this->wps_sfw_api_username, $this->wps_sfw_api_password, $this->wps_sfw_api_signature, 124, $this->wps_sfw_invoice_prefix );
		}

		/**
		 * This function is used to set express checkout.
		 *
		 * @name wps_sfw_set_express_checkout.
		 * @param array $args args.
		 * @since    1.0.2
		 */
		public function wps_sfw_set_express_checkout( $args ) {
			$request_obj = $this->wps_get_new_request();

			$payments_args = $request_obj->wps_sfw_get_express_checkout_param( $args );
			$response = $this->wps_sfw_process_request( $payments_args );

			return $response;
		}

		/**
		 * This function is used to process request.
		 *
		 * @name wps_sfw_process_request.
		 * @param array $payments_args payments_args.
		 * @since    1.0.2
		 */
		public function wps_sfw_process_request( $payments_args ) {

			$response = $this->wps_sfw_process_remote_request( $this->wps_sfw_api_endpoint, $payments_args );
			$response = $this->wps_sfw_process_api_response( $response );

			return $response;
		}

		/**
		 * This function is used to process request.
		 *
		 * @name wps_sfw_process_api_response.
		 * @param array $response response.
		 * @throws Exception Return error.
		 * @since    1.0.2
		 */
		public function wps_sfw_process_api_response( $response ) {

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
		 * @name wps_sfw_process_remote_request.
		 * @param string $url url.
		 * @param array  $wps_args wps_args.
		 * @since    1.0.2
		 */
		public function wps_sfw_process_remote_request( $url, $wps_args ) {

			$args = array(
				'method'      => 'POST',
				'timeout'     => 45,
				'httpversion' => '1.0',
				'sslverify'   => true,
				'blocking'    => true,
				'user-agent'  => '',
				'headers'     => array(),
				'body'        => $wps_args,
				'cookies'     => array(),
			);

			return wp_safe_remote_request( $url, $args );
		}

		/**
		 * This function is used to create url.
		 *
		 * @name wps_sfw_get_callback_url.
		 * @param array $url url.
		 * @since    1.0.2
		 */
		public function wps_sfw_get_callback_url( $url ) {
			return add_query_arg( 'action', $url, WC()->api_request_url( 'wps_sfw_paypal' ) );
		}

		/**
		 * This function is used to get express checkout details.
		 *
		 * @name wps_sfw_get_express_checkout_details.
		 * @param string $token token.
		 * @since    1.0.2
		 */
		public function wps_sfw_get_express_checkout_details( $token ) {

			$request_obj = $this->wps_get_new_request();
			$payments_args = $request_obj->wps_sfw_get_express_checkout_params( $token );

			return $this->wps_sfw_process_request( $payments_args );
		}

		/**
		 * This function is used to get order.
		 *
		 * @name get_paypal_order.
		 * @param string $wps_args wps_args.
		 * @since    1.0.2
		 */
		public function get_paypal_order( $wps_args ) {

			$custom = json_decode( $wps_args );
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
		 * @name wps_sfw_create_billing_agreement.
		 * @param string $token token.
		 * @since    1.0.2
		 */
		public function wps_sfw_create_billing_agreement( $token ) {

			$request_obj = $this->wps_get_new_request();

			$payments_args = $request_obj->wps_sfw_create_billing_agreement_params( $token );

			return $this->wps_sfw_process_request( $payments_args );
		}

		/**
		 * Do exprss checkout.
		 *
		 * @name wps_sfw_do_express_checkout.
		 * @param string $token token.
		 * @param object $order order.
		 * @param arraya $args args.
		 * @since    1.0.2
		 */
		public function wps_sfw_do_express_checkout( $token, $order, $args ) {

			$request_obj = $this->wps_get_new_request();

			$payments_args = $request_obj->wps_sfw_do_express_checkout_params( $token, $order, $args );
			return $this->wps_sfw_process_request( $payments_args );
		}

		/**
		 * Do reference  transaction.
		 *
		 * @name wps_sfw_do_reference_transaction.
		 * @param string $reference_id reference_id.
		 * @param object $order order.
		 * @since    1.0.2
		 */
		public function wps_sfw_do_reference_transaction( $reference_id, $order ) {

			$request_obj = $this->wps_get_new_request();

			$payments_args = $request_obj->wps_sfw_do_reference_transaction_params( $reference_id, $order );

			return $this->wps_sfw_process_request( $payments_args );
		}

		/**
		 * Set paypal id
		 *
		 * @name wps_sfw_set_paypal_id.
		 * @param object $order order.
		 * @param string $paypal_subscription_id paypal_subscription_id.
		 * @since    1.0.2
		 */
		public function wps_sfw_set_paypal_id( $order, $paypal_subscription_id ) {

			if ( ! is_object( $order ) ) {
				$order = wc_get_order( $order );
			}
			$wps_subscription_id = wps_sfw_get_meta_data( $order->get_id(), 'wps_subscription_id', true );
			if ( isset( $wps_subscription_id ) && ! empty( $wps_subscription_id ) ) {
				if ( ! in_array( $paypal_subscription_id, get_user_meta( $order->get_user_id(), '_paypal_subscription_id', false ) ) ) {
					add_user_meta( $order->get_user_id(), '_wps_paypal_subscription_id', $paypal_subscription_id );
				}
				wps_sfw_update_meta_data( $wps_subscription_id, '_wps_paypal_subscription_id', $paypal_subscription_id );
			}
		}

		/**
		 * Check transaction.
		 *
		 * @name wps_sfw_check_transaction.
		 * @param array $response response.
		 * @since    1.0.2
		 */
		public function wps_sfw_check_transaction( $response ) {

			return in_array( $this->wps_sfw_get_payment_status( $response ), array( 'Completed', 'Processed' ) );
		}

		/**
		 * Check pending transaction.
		 *
		 * @name wps_sfw_check_pending_transaction.
		 * @param array $response response.
		 * @since    1.0.2
		 */
		public function wps_sfw_check_pending_transaction( $response ) {

			return in_array( $this->wps_sfw_get_payment_status( $response ), array( 'Pending' ) );
		}

		/**
		 * Get  transaction status.
		 *
		 * @name wps_sfw_get_payment_status.
		 * @param array $response response.
		 * @since    1.0.2
		 */
		public function wps_sfw_get_payment_status( $response ) {

			return $this->wps_sfw_get_payment_parameter( $response, 'PAYMENTSTATUS' );
		}
		/**
		 * Get transaction id.
		 *
		 * @name get_transaction_id.
		 * @param array $response response.
		 * @since    1.0.2
		 */
		public function get_transaction_id( $response ) {

			return $this->wps_sfw_get_payment_parameter( $response, 'TRANSACTIONID' );
		}

		/**
		 * Get transaction paramenter.
		 *
		 * @name wps_sfw_get_payment_parameter.
		 * @param array  $response response.
		 * @param string $name name.
		 * @since    1.0.2
		 */
		public function wps_sfw_get_payment_parameter( $response, $name ) {
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
		 * @name wps_sfw_check_billing_id.
		 * @param string $profile_id profile_id.
		 * @param string $profile_type profile_type.
		 * @since    1.0.2
		 */
		public function wps_sfw_check_billing_id( $profile_id, $profile_type ) {

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
		 * @name wps_sfw_process_payment_response.
		 * @param object $order order.
		 * @param array  $response response.
		 * @since    1.0.2
		 */
		public function wps_sfw_process_payment_response( $order, $response ) {

			if ( isset( $response['ACK'] ) && 'Success' != $response['ACK'] ) {

				$order->update_status( 'failed' );
			} elseif ( $this->wps_sfw_check_transaction( $response ) ) {
				// translators: placeholder is a transaction ID.
				$order->add_order_note( sprintf( __( 'PayPal payment approved (ID: %s)', 'subscriptions-for-woocommerce' ), $this->get_transaction_id( $response ) ) );
				$order->update_status( 'processing' );
				$order->set_transaction_id( $this->get_transaction_id( $response ) );
				$order_id = $order->get_id();
				wps_sfw_send_email_for_renewal_susbcription( $order_id );
			} elseif ( $this->wps_sfw_check_pending_transaction( $response ) ) {
				$order_note   = sprintf( __( 'PayPal Transaction Held:', 'subscriptions-for-woocommerce' ) );
				$order->update_status( 'on-hold', $order_note );
			}
		}

		/**
		 * Check api response.
		 *
		 * @name wps_sfw_handle_express_checkout_api.
		 * @throws Exception Return error.
		 * @since    1.0.2
		 */
		public function wps_sfw_handle_express_checkout_api() {

			if ( ! isset( $_GET['action'] ) ) {
				return;
			}
			if ( isset( $_GET['action'] ) && 'create_billing_agreement' == $_GET['action'] ) {
				if ( ! isset( $_GET['token'] ) ) {
					return;
				}
					$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
				try {
					$wps_sfw_express_checkout_response = $this->wps_sfw_get_express_checkout_details( $token );
					if ( ! isset( $wps_sfw_express_checkout_response['BILLINGAGREEMENTACCEPTEDSTATUS'] ) ) {
						return;
					}
					$wps_sfw_express_response = $wps_sfw_express_checkout_response['BILLINGAGREEMENTACCEPTEDSTATUS'];

					if ( 1 == $wps_sfw_express_response ) {
						$order_data = isset( $wps_sfw_express_checkout_response['CUSTOM'] ) ? $wps_sfw_express_checkout_response['CUSTOM'] : '';
						$order = $this->get_paypal_order( $order_data );

						if ( is_null( $order ) ) {
							throw new Exception( __( 'Unable to find order for PayPal billing agreement.', 'subscriptions-for-woocommerce' ) );
						}
						if ( $order->get_total() > 0 && ! wps_sfw_check_valid_subscription( $order->get_id() ) ) {

							$payments_args = array(
								'payment_action' => 'Sale',
								'payer_id'       => isset( $wps_sfw_express_checkout_response['PAYERID'] ) ? $wps_sfw_express_checkout_response['PAYERID'] : '',
							);

							$wps_sfw_billing_response = $this->wps_sfw_do_express_checkout( $token, $order, $payments_args );

						} else {

							$wps_sfw_billing_response = $this->wps_sfw_create_billing_agreement( $token );
						}

						if ( isset( $wps_sfw_billing_response['ACK'] ) && 'Success' != $wps_sfw_billing_response['ACK'] ) {

							return;
						}

						$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
						$payment_method     = isset( $available_gateways['paypal'] ) ? $available_gateways['paypal'] : false;
						$order->set_payment_method( $payment_method );

						if ( isset( $wps_sfw_billing_response['BILLINGAGREEMENTID'] ) ) {
							$this->wps_sfw_set_paypal_id( $order, $wps_sfw_billing_response['BILLINGAGREEMENTID'] );
						}

						if ( ! wps_sfw_check_valid_subscription( $order->get_id() ) ) {
							if ( 0 == $order->get_total() ) {
								$order->payment_complete();
							} else {
								$this->wps_sfw_process_payment_response( $order, $wps_sfw_billing_response );
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

					wp_safe_redirect( wc_get_cart_url() );
					exit;
				}
			}
		}

		/**
		 * This function is used to add paypal args for subscriptions.
		 *
		 * @name wps_sfw_add_paypal_args
		 * @param array  $wps_args wps_args.
		 * @param object $order order.
		 * @since    1.0.1
		 */
		public function wps_sfw_add_paypal_args( $wps_args, $order ) {

			if ( empty( $order ) ) {

				return $wps_args;
			}
			$order_id = $order->get_id();
			$wps_order_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_order_has_subscription', true );
			if ( 'yes' != $wps_order_has_susbcription ) {
				return $wps_args;
			}
			// Set express checkout.
			$response = $this->wps_sfw_set_express_checkout(
				array(
					'currency'   => $wps_args['currency_code'],
					'return_url' => $this->wps_sfw_get_callback_url( 'create_billing_agreement' ),
					'cancel_url' => $wps_args['cancel_return'],
					'notify_url' => $wps_args['notify_url'],
					'custom'     => $wps_args['custom'],
					'order'      => $order,
				)
			);

			$wps_args = array(
				'cmd'   => '_express-checkout',
				'token' => $response['TOKEN'],
			);
			return $wps_args;

			$wps_is_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );

			$wps_upgrade_downgrade_order = wps_sfw_get_meta_data( $order_id, 'wps_upgrade_downgrade_order', true );

			if ( 'yes' == $wps_is_renewal_order ) {
				return $wps_args;
			}

			if ( wps_sfw_check_valid_subscription( $order_id ) ) {
				$wps_susbcription_free_trial = wps_sfw_get_meta_data( $order_id, 'wps_susbcription_trial_end', true );
				if ( isset( $wps_susbcription_free_trial ) && ! empty( $wps_susbcription_free_trial ) ) {
					$wps_subscription_id = $order_id;
				}
			} elseif ( 'yes' == $wps_upgrade_downgrade_order ) {
				$wps_subscription_id = wps_sfw_get_meta_data( $order_id, 'wps_subscription_id', true );
				$wps_old_subscription_id = wps_sfw_get_meta_data( $order_id, 'wps_old_subscription_id', true );
				$wps_old_subscription_data = wps_sfw_get_meta_data( $wps_old_subscription_id, 'wps_upgrade_downgrade_data', true );

				wps_sfw_update_meta_data( $wps_subscription_id, 'wps_upgrade_downgrade_data', $wps_old_subscription_data );

				if ( empty( $wps_subscription_id ) ) {
					return $wps_args;
				}
			} else {
				$wps_order_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_order_has_subscription', true );
				if ( 'yes' != $wps_order_has_susbcription ) {
					return $wps_args;
				}

				$wps_subscription_id = wps_sfw_get_meta_data( $order_id, 'wps_subscription_id', true );
				if ( empty( $wps_subscription_id ) ) {

					return $wps_args;

				}
			}
			do_action( 'wps_sfw_paypal_order', $wps_args, $order );
			/*check for valid subscription*/
			if ( ! wps_sfw_check_valid_subscription( $wps_subscription_id ) ) {

				return $wps_args;
			}
			$susbcription = wc_get_order( $wps_subscription_id );

			$wps_order_items = $susbcription->get_items();

			if ( empty( $wps_order_items ) ) {

				return $wps_args;

			}

			$wps_chk_susbcription = false;

			$wps_item_names = array();

			foreach ( $wps_order_items as $key => $order_item ) {

				$product_id = ( $order_item['variation_id'] ) ? $order_item['variation_id'] : $order_item['product_id'];

				$product    = wc_get_product( $product_id );

				if ( wps_sfw_check_product_is_subscription( $product ) ) {

					// It is initialized as susbcription.

					$wps_args['cmd']      = '_xclick-subscriptions';

					// reattempt failed payments use 0 for not.

					$wps_args['sra'] = 1;
					$wps_sfw_subscription_interval = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_interval', true );
					$wps_price_frequency = $this->wps_sfw_get_reccuring_time_interval_for_paypal( $wps_sfw_subscription_interval );
					$wps_price_is_per = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_number', true );
					$wps_sfw_subscription_expiry_number = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_expiry_number', true );

					$wps_schedule_start = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_schedule_start', true );

					$wps_susbcription_trial_end = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_susbcription_trial_end', true );
					$wps_susbcription_trial_end = wps_sfw_susbcription_trial_date( $wps_subscription_id, $wps_schedule_start );
					wps_sfw_update_meta_data( $wps_subscription_id, 'wps_susbcription_trial_end', $wps_susbcription_trial_end );

					if ( isset( $wps_sfw_subscription_expiry_number ) && ! empty( $wps_sfw_subscription_expiry_number ) ) {

						$wps_susbcription_end = wps_sfw_susbcription_expiry_date( $wps_subscription_id, $wps_schedule_start, $wps_susbcription_trial_end );
						wps_sfw_update_meta_data( $wps_subscription_id, 'wps_susbcription_end', $wps_susbcription_end );

						$wps_subscription_num = ( $wps_sfw_subscription_expiry_number ) ? $wps_sfw_subscription_expiry_number / $wps_price_is_per : '';
					} else {
						$wps_subscription_num = '';

					}
					$wps_free_trial_num = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_free_trial_number', true );

					// order total.
					if ( $wps_free_trial_num > 0 ) {

						$wps_free_trial_frequency = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_free_trial_interval', true );

						$wps_free_trial_frequency = $this->wps_sfw_get_reccuring_time_interval_for_paypal( $wps_free_trial_frequency );
						$wps_args['a1'] = wc_format_decimal( $order->get_total(), 2 );

						$wps_args['p1'] = $wps_free_trial_num;

						$wps_args['t1'] = $wps_free_trial_frequency;

					}

					$wps_args['a3'] = wc_format_decimal( $susbcription->get_total(), 2 );

					$wps_args['p3'] = $wps_price_is_per;

					$wps_args['t3'] = $wps_price_frequency;

					if ( '' == $wps_subscription_num || $wps_subscription_num > 1 ) {

						$wps_args['src'] = 1;

						if ( '' != $wps_subscription_num ) {

							$wps_args['srt'] = $wps_subscription_num;

						}
					} else {

						$wps_args['src'] = 0;

					}

					$wps_chk_susbcription = true;

				}
				if ( $order_item['qty'] > 1 ) {

					$wps_item_names[] = $order_item['qty'] . ' x ' . $this->wps_format_item_name( $order_item['name'] );

				} else {

					$wps_item_names[] = $this->wps_format_item_name( $order_item['name'] );

				}
			}

			if ( ! $wps_chk_susbcription ) {

				return $wps_args;

			}

			if ( count( $wps_item_names ) > 1 ) {
				/* translators: %s: order number */
				$wps_args['item_name'] = $this->wps_format_item_name( sprintf( __( 'Order %s', 'subscriptions-for-woocommerce' ), $order->get_order_number() . ' - ' . implode( ', ', $wps_item_names ) ) );

			} else {

				$wps_args['item_name'] = implode( ', ', $wps_item_names );

			}
			$wps_args['rm'] = 2;
			// show the data in log file.
			WC_Gateway_Paypal::log( 'WPS - Subscription Request: ' . wc_print_r( $wps_args, true ) );

			return apply_filters( 'wps_sfw_paypal_args_data', $wps_args, $order );
		}

		/**
		 * This function is used to add format item for subscriptions.
		 *
		 * @name wps_format_item_name
		 * @param string $item_name item_name.
		 * @since    1.0.1
		 */
		public function wps_format_item_name( $item_name ) {

			if ( strlen( $item_name ) > 127 ) {

				$item_name = substr( $item_name, 0, 124 ) . '...';
			}
			return html_entity_decode( $item_name, ENT_NOQUOTES, 'UTF-8' );
		}

		/**
		 * This function is used to change subscriptions status.
		 *
		 * @name wps_sfw_change_paypal_subscription_status
		 * @param string $profile_id profile_id.
		 * @param string $action action.
		 * @since    1.0.1
		 */
		public function wps_sfw_change_paypal_subscription_status( $profile_id, $action ) {
			$wps_sfw_api_request = 'USER=' . urlencode( $this->wps_sfw_api_username )
						. '&PWD=' . urlencode( $this->wps_sfw_api_password )
						. '&SIGNATURE=' . urlencode( $this->wps_sfw_api_signature )
						. '&VERSION=76.0'
						. '&METHOD=ManageRecurringPaymentsProfileStatus'
						. '&PROFILEID=' . urlencode( $profile_id )
						. '&ACTION=' . urlencode( $action )
						/* translators: %s: subscription status */
						. '&NOTE=' . urlencode( sprintf( __( 'WPS Subscription %s', 'subscriptions-for-woocommerce' ), strtolower( $action ) ) );

			$url = $this->wps_sfw_api_endpoint;
			$request = array(
				'httpversion' => '1.0',
				'sslverify'   => false,
				'method'      => 'POST',
				'timeout'     => 45,
				'body'        => $wps_sfw_api_request,
			);

			$response = wp_remote_post( $url, $request );

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				echo esc_html( 'Something went wrong' );
				WC_Gateway_Paypal::log( 'WPS - Change Paypal status for ' . $profile_id . ' has been Failed: ' . $error_message );
			} else {
				$response    = wp_remote_retrieve_body( $response );
				// show the data in log file.
				WC_Gateway_Paypal::log( 'WPS - Change Paypal status for ' . $profile_id . ' has been successfull: ' . wc_print_r( $response, true ) );
				parse_str( $response, $parsed_response );
				return $parsed_response;
			}

			return $response;
		}
		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name wps_sfw_cancel_paypal_subscription
		 * @param string $wps_subscription_id wps_subscription_id.
		 * @param string $status status.
		 * @since    1.0.1
		 */
		public function wps_sfw_cancel_paypal_subscription( $wps_subscription_id, $status ) {

			$wps_sfw_paypal_subscriber_id = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_paypal_subscriber_id', true );
			$paypal_profile_id = wps_sfw_get_meta_data( $wps_subscription_id, '_wps_paypal_subscription_id', true );

			if ( isset( $paypal_profile_id ) && ! empty( $paypal_profile_id ) ) {
				if ( $this->wps_sfw_check_billing_id( $paypal_profile_id, 'billing_agreement' ) ) {
					if ( 'Cancel' == $status ) {
						wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
						wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
					}
				}
			} elseif ( isset( $wps_sfw_paypal_subscriber_id ) && ! empty( $wps_sfw_paypal_subscriber_id ) && $this->wps_sfw_paypal_check_settings() ) {
				$response = $this->wps_sfw_change_paypal_subscription_status( $wps_sfw_paypal_subscriber_id, $status );
				if ( ! empty( $response ) ) {
					if ( 'Failure' == $response['ACK'] ) {

						 WC_Gateway_Paypal::log( 'WPS - Change Paypal status for ' . $wps_sfw_paypal_subscriber_id . ' has been Failed: ' . $response['L_LONGMESSAGE0'] );
					} elseif ( 'Success' == $response['ACK'] ) {
						if ( 'Cancel' == $status ) {
							wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
							wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
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
		 * @name wps_sfw_get_reccuring_time_interval_for_paypal
		 * @param string $wps_reccuring_period wps_reccuring_period.
		 * @since    1.0.1
		 */
		public function wps_sfw_get_reccuring_time_interval_for_paypal( $wps_reccuring_period ) {
			$wps_converted_period = 'D';
			switch ( strtolower( $wps_reccuring_period ) ) {
				case 'day':
					$wps_converted_period = 'D';
					break;
				case 'week':
					$wps_converted_period = 'W';
					break;
				case 'month':
					$wps_converted_period = 'M';
					break;
				case 'year':
					$wps_converted_period = 'Y';
					break;
				default:
			}

			return $wps_converted_period;
		}
	}
}
return new Wps_Subscriptions_Payment_Paypal_Main();
