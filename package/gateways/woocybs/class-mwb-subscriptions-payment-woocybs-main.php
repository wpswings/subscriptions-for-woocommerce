<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://makewebbetter.com
 * @since      1.3.0
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
if ( ! class_exists( 'Mwb_Subscriptions_Payment_Woocybs_Main' ) ) {

	/**
	 * Define class and module for woo stripe.
	 */
	class Mwb_Subscriptions_Payment_Woocybs_Main {
		/**
		 * Constructor
		 */
		public function __construct() {

			if ( $this->mwb_sfw_check_woo_cybs_enable() && mwb_sfw_check_plugin_enable() ) {

				add_action( 'mwb_sfw_subscription_cancel', array( $this, 'mwb_wsp_cancel_woo_cybs_subscription' ), 10, 2 );

				add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'mwb_wsp_add_woocybs_order_statuses_for_payment_complete' ), 10, 2 );

				add_filter( 'mwb_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'mwb_wsp_woo_cybs_payment_gateway_for_woocommerce' ), 10, 2 );
				add_action( 'mwb_sfw_other_payment_gateway_renewal', array( $this, 'mwb_wsp_woo_cybs_process_subscription_payment' ), 10, 3 );

				add_filter( 'woo_cybs_create_payment_token', array( $this, 'mwb_wsp_woo_cybs_create_payment_token' ), 10, 2 );
				add_action( 'woo_cybs_save_payment_token', array( $this, 'mwb_wsp_woo_cybs_save_payment_token' ), 10, 2 );

			}

		}

		/**
		 * Allow recurring payment.
		 *
		 * @name mwb_wsp_woo_cybs_save_payment_token.
		 * @param object $payment_token payment_token.
		 * @param int    $order_id order_id.
		 * @since 1.3.0
		 * @return void
		 */
		public function mwb_wsp_woo_cybs_save_payment_token( $payment_token, $order_id ) {
			if ( ! empty( $payment_token ) ) {
				$mwb_has_subscription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );
				$mwb_subscription_id = get_post_meta( $order_id, 'mwb_subscription_id', true );
				if ( 'yes' === $mwb_has_subscription ) {
					update_post_meta( $mwb_subscription_id, '_woo_cybs_payment_token', $payment_token );
				}
			}

		}


		/**
		 * Allow recurring payment.
		 *
		 * @name mwb_wsp_woo_cybs_create_payment_token.
		 * @param bool $bool bool.
		 * @param int  $order_id order_id.
		 * @since 1.3.0
		 * @return boolean
		 */
		public function mwb_wsp_woo_cybs_create_payment_token( $bool, $order_id ) {
			if ( ! mwb_sfw_check_plugin_enable() ) {
				return $bool;
			}
			if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
				if ( ! isset( $_POST['wc-cybs-payment-token'] ) && ! $bool ) {
					$mwb_has_subscription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );
					if ( 'yes' === $mwb_has_subscription ) {
						$bool = true;
					}
				} elseif ( isset( $_POST['wc-cybs-payment-token'] ) && 'new' == $_POST['wc-cybs-payment-token'] && ! $bool ) {
					$mwb_has_subscription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );
					if ( 'yes' === $mwb_has_subscription ) {
						$bool = true;
					}
				} elseif ( is_user_logged_in() && isset( $_POST['wc-cybs-payment-token'] ) && 'new' !== $_POST['wc-cybs-payment-token'] ) {
					$token_id = sanitize_text_field( wp_unslash( $_POST['wc-cybs-payment-token'] ) );
					$token = WC_Payment_Tokens::get( $token_id );

					// Verify token belongs to the logged in user.
					if ( $token->get_user_id() == get_current_user_id() ) {
						$mwb_has_subscription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );
						$mwb_subscription_id = get_post_meta( $order_id, 'mwb_subscription_id', true );
						if ( 'yes' == $mwb_has_subscription ) {
							update_post_meta( $mwb_subscription_id, '_woo_cybs_payment_token', $token->get_token() );
						}
					}
				}
			}
			return $bool;
		}

		/**
		 * Process subscription payment.
		 *
		 * @name mwb_wsp_woo_cybs_process_subscription_payment.
		 * @param object $order order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 * @since 1.3.0
		 * @return void
		 */
		public function mwb_wsp_woo_cybs_process_subscription_payment( $order, $subscription_id, $payment_method ) {

			if ( $order && is_object( $order ) ) {
				$order_id = $order->get_id();
				$mwb_sfw_renewal_order = get_post_meta( $order_id, 'mwb_sfw_renewal_order', true );
				if ( ! $this->mwb_wsp_check_supported_payment_options( $payment_method ) || 'yes' != $mwb_sfw_renewal_order ) {
					return;
				}
				$mwb_enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();
				if ( isset( $mwb_enabled_gateways[ $payment_method ] ) ) {
					$payment_method_obj = $mwb_enabled_gateways[ $payment_method ];
				}
				if ( empty( $payment_method_obj ) ) {
					return;
				}

				if ( class_exists( 'CybsSoapiCC' ) ) {
					$payment_token = '';
					$woo_cybs_payment_token = get_post_meta( $subscription_id, '_woo_cybs_payment_token', true );
					$mwb_parent_order_id = get_post_meta( $subscription_id, 'mwb_parent_order', true );

					$user_id = $order->get_user_id();
					$tokens = WC_Payment_Tokens::get_tokens( $user_id );
					if ( ! empty( $tokens ) && is_array( $tokens ) ) {
						foreach ( $tokens as $token ) {
							if ( $woo_cybs_payment_token == $token->get_token() ) {
								$payment_token = $token;
								break;
							}
						}
					}
					if ( empty( $payment_token ) ) {
						/* translators: %s: method title */
						$order->update_status( 'failed', sprintf( __( 'Payment token not found %s', 'subscriptions-for-woocommerce' ), $order->get_payment_method_title() ) );
						return;
					}

					$order->set_payment_method( $payment_method_obj );
					// Return if order total is zero.
					if ( 0 == $order->get_total() ) {
						$order->payment_complete();
						return;
					}
					$soap_client = new CybsSoapiCC( $payment_method_obj->merchantId, $payment_method_obj->transactionKey, $payment_method_obj->wsdl_version, $payment_method_obj->testmode );

					$request = $soap_client->createRequest( $order_id );
					$soap_client->setAuthInRequest( $request );

					// Request capture of funds if immediate capture/settlement enabled.
					if ( 'yes' == $payment_method_obj->capture ) {
						$soap_client->setCaptureInRequest( $request );
					}

					$payment_method_obj->setBillToInRequest( $request, $order, $payment_method_obj->deviceFingerPrint );
					$payment_method_obj->setShipToInRequest( $request, $order );
					$soap_client->setPurchaseTotals( $request, $order->get_currency(), $order->get_total() );
					$payment_method_obj->setDeviceFingerInRequest( $request, $payment_method_obj->deviceFingerPrint );

					$soap_client->setTokenInRequest( $request, $payment_token->get_token() );
					$reply = $soap_client->runTransaction( $request );
					$decision = $reply->decision;
					$reason_code = $reply->reasonCode;

					if ( strcmp( $decision, 'ACCEPT' ) == 0 && strcmp( $reason_code, '100' ) == 0 ) {

						foreach ( $reply as $key => $value ) {
							update_post_meta( $order_id, $key, $value );
						}

						$numero_cuenta = substr( $token->get_token(), -4 );

						$card_name  = get_post_meta( $mwb_parent_order_id, 'cardholder', true );
						$brand_card = get_post_meta( $mwb_parent_order_id, 'brand_card', true );
						// Save last 4 digits.
						update_post_meta( $order_id, 'last_digits', $numero_cuenta );
						update_post_meta( $order_id, 'transaction_time', gmdate( 'd-m-Y H:i', current_time( 'timestamp', 0 ) ) );
						update_post_meta( $order_id, 'cardholder', $card_name );
						update_post_meta( $order_id, 'brand_card', $brand_card );
						$audit_cybs_number = str_pad( (int) get_option( 'audit_cybs_number' ) + 1, 10, '0', STR_PAD_LEFT );
						update_option( 'audit_cybs_number', $audit_cybs_number );

						$order->payment_complete();
						/* translators: %s: card name */
						$order->add_order_note( sprintf( __( 'Renewal Order is successfully paid!. Cardholder: %1$s Last 4 card digits: : %2$s', 'subscriptions-for-woocommerce' ), $card_name, $numero_cuenta ) );
					}
				}
			}
		}

		/**
		 * Allow payment method.
		 *
		 * @name mwb_wsp_woo_cybs_payment_gateway_for_woocommerce.
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @since 1.3.0
		 * @return array
		 */
		public function mwb_wsp_woo_cybs_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {

			if ( $this->mwb_wsp_check_supported_payment_options( $payment_method ) ) {
				$supported_payment_method[] = $payment_method;
			}
			return apply_filters( 'mwb_wsp_supported_payment_woocybs', $supported_payment_method, $payment_method );
		}

		/**
		 * This function is add subscription order status.
		 *
		 * @name mwb_wsp_add_woocybs_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @since 1.3.0
		 * @return mixed
		 */
		public function mwb_wsp_add_woocybs_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {

				$order_id = $order->get_id();
				$payment_method = get_post_meta( $order_id, '_payment_method', true );
				$mwb_sfw_renewal_order = get_post_meta( $order_id, 'mwb_sfw_renewal_order', true );
				if ( $this->mwb_wsp_check_supported_payment_options( $payment_method ) && 'yes' == $mwb_sfw_renewal_order ) {
					$order_status[] = 'mwb_renewal';

				}
			}
			return apply_filters( 'mwb_wsp_add_subscription_order_statuses_for_payment_complete', $order_status, $order );

		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name mwb_wsp_cancel_woo_cybs_subscription
		 * @param int    $mwb_subscription_id mwb_subscription_id.
		 * @param string $status status.
		 * @since 1.3.0
		 * @return void
		 */
		public function mwb_wsp_cancel_woo_cybs_subscription( $mwb_subscription_id, $status ) {

			$mwb_payment_method = get_post_meta( $mwb_subscription_id, '_payment_method', true );
			if ( $this->mwb_wsp_check_supported_payment_options( $mwb_payment_method ) ) {
				if ( 'Cancel' == $status ) {
					mwb_sfw_send_email_for_cancel_susbcription( $mwb_subscription_id );
					update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
				}
			}
		}

		/**
		 * Check supported payment method.
		 *
		 * @name mwb_wsp_check_supported_payment_options
		 * @param string $payment_method payment_method.
		 * @since 1.3.0
		 * @return boolean
		 */
		public function mwb_wsp_check_supported_payment_options( $payment_method ) {
			$result = false;
			if ( 'cybs' == $payment_method ) {
				$result = true;
			}
			return $result;
		}

		/**
		 * Check woo cybs enable.
		 *
		 * @name mwb_sfw_check_woo_cybs_enable
		 * @since 1.3.0
		 * @return boolean
		 */
		public function mwb_sfw_check_woo_cybs_enable() {
			$activated = false;
			if ( in_array( 'woocommerce-cybs/woocommerce-vncybs.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$activated = true;
			}
			return $activated;
		}
	}
}
new Mwb_Subscriptions_Payment_Woocybs_Main();
