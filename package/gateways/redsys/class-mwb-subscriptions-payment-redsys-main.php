<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://makewebbetter.com
 * @since      2.0.0
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
if ( ! class_exists( 'Mwb_Subscriptions_Payment_Redsys_Main' ) ) {

	/**
	 * Define class and module for woo stripe.
	 */
	class Mwb_Subscriptions_Payment_Redsys_Main {
		/**
		 * Constructor
		 */
		public function __construct() {

			if ( $this->mwb_sfw_check_woo_redsys_enable() && mwb_sfw_check_plugin_enable() ) {

				add_action( 'mwb_sfw_subscription_cancel', array( $this, 'mwb_wsp_cancel_redsys_subscription' ), 10, 2 );
				add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'mwb_wsp_redsys_payment_order_statuses_for_payment_complete' ), 10, 2 );

				add_filter( 'mwb_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'mwb_wsp_redsys_payment_gateway_for_woocommerce' ), 10, 2 );
				add_action( 'mwb_sfw_other_payment_gateway_renewal', array( $this, 'mwb_sfw_woo_redsys_process_subscription_payment' ), 10, 3 );

				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'mwb_sfw_redsys_save_field_update_order_meta' ), 99, 1 );
			}

		}
		/**
		 * This function is add subscription order status.
		 *
		 * @name mwb_wsp_redsys_payment_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @since 2.0.0
		 * @return mixed
		 */
		public function mwb_wsp_redsys_payment_order_statuses_for_payment_complete( $order_status, $order ) {
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
		 * Allow payment method.
		 *
		 * @name mwb_wsp_redsys_payment_gateway_for_woocommerce.
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @since 2.0.0
		 * @return array
		 */
		public function mwb_wsp_redsys_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {
			if ( $this->mwb_wsp_check_supported_payment_options( $payment_method ) ) {
				$supported_payment_method[] = $payment_method;
			}
			return apply_filters( 'mwb_wsp_supported_payment_redsys', $supported_payment_method, $payment_method );
		}

		/**
		 * Check supported payment method.
		 *
		 * @name mwb_wsp_check_supported_payment_options
		 * @param string $payment_method payment_method.
		 * @since 2.0.0
		 * @return boolean
		 */
		public function mwb_wsp_check_supported_payment_options( $payment_method ) {
			$result = false;
			if ( 'redsys' == $payment_method ) {
				$result = true;
			}
			return $result;
		}

		/**
		 * Check woo redsys enable.
		 *
		 * @name mwb_sfw_check_woo_redsys_enable
		 * @since 2.0.0
		 * @return boolean
		 */
		public function mwb_sfw_check_woo_redsys_enable() {
			$activated = false;
			if ( in_array( 'woocommerce-gateway-redsys/woocommerce-gateway-redsys.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$activated = true;
			}
			return $activated;
		}

		/**
		 * Process subscription payment.
		 *
		 * @name mwb_wsp_woo_cybs_process_subscription_payment.
		 * @param object $order order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 * @since 2.0.0
		 * @return void
		 */
		public function mwb_sfw_woo_redsys_process_subscription_payment( $order, $subscription_id, $payment_method ) {
			
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
				$gateway = $payment_method_obj;
				//
				$order_total_sign    = '';
				$transaction_id2     = '';
				$transaction_type    = '';
				$DSMerchantTerminal  = '';
				$final_notify_url    = '';
				$returnfromredsys    = '';
				$gatewaylanguage     = '';
				$currency            = '';
				$secretsha256        = '';
				$customer            = '';
				$url_ok              = '';
				$product_description = '';
				$merchant_name       = '';
				//
				$order_id         = $order->get_id();
				$user_id  = $order->get_user_id();
				$amount = $order->get_total();
				$redsys_done      = get_post_meta( $order_id, '_redsys_done', true );
				if ( 'yes' !== $redsys_done ) {
					$order            = WCRed()->get_order( $order_id );
					$currency_codes   = WCRed()->get_currencies();
					$transaction_id2  = WCRed()->prepare_order_number( $order_id, 'redsys' );
					$order_total_sign = WCRed()->redsys_amount_format( $order->get_total() );

					$transaction_type = '0';

					$gatewaylanguage = $gateway->redsyslanguage;

					if ( $gateway->wooredsysurlko ) {
						if ( 'returncancel' === $gateway->wooredsysurlko ) {
							$returnfromredsys = $order->get_cancel_order_url();
						} else {
							$returnfromredsys = wc_get_checkout_url();
						}
					} else {
						$returnfromredsys = $order->get_cancel_order_url();
					}
					if ( 'yes' === $gateway->useterminal2 ) {
						$toamount  = number_format( $gateway->toamount, 2, '', '' );
						$terminal  = $gateway->terminal;
						$terminal2 = $gateway->terminal2;
						if ( $order_total_sign <= $toamount ) {
							$DSMerchantTerminal = $terminal2;
						} else {
							$DSMerchantTerminal = $terminal;
						}
					} else {
						$DSMerchantTerminal = $gateway->terminal;
					}
		
					if ( 'yes' === $gateway->not_use_https ){
						$final_notify_url = $gateway->notify_url_not_https;
					} else {
						$final_notify_url = $tgatewayhis->notify_url;
					}
					if ( 'yes' === $gateway->psd2 ) {
						$customer_token = WCRed()->get_users_token_bulk( $user_id, 'R' );
						$txnid          = WCRed()->get_txnid( $customer_token );
					} else {
						$customer_token = WCRed()->get_users_token_bulk( $user_id );
					}

					if ( ! $customer_token ) {
						$order->add_order_note( __( 'No User Token !!!', 'woocommerce-redsys' ) );
						return false;
					}

					$redsys_data_send = array();

					$currency            = $currency_codes[ get_woocommerce_currency() ];
					$secretsha256        = $gateway->get_redsys_sha256( $user_id );
					$customer            = $gateway->customer;
					$url_ok              = add_query_arg( 'utm_nooverride', '1', $gateway->get_return_url( $order ) );
					$product_description = WCRed()->product_description( $order, 'redsys' );
					$merchant_name       = $gateway->commercename;

					$redsys_data_send = array(
						'order_total_sign'    => $order_total_sign,
						'transaction_id2'     => $transaction_id2,
						'transaction_type'    => $transaction_type,
						'DSMerchantTerminal'  => $DSMerchantTerminal,
						'final_notify_url'    => $final_notify_url,
						'returnfromredsys'    => $returnfromredsys,
						'gatewaylanguage'     => $gatewaylanguage,
						'currency'            => $currency,
						'secretsha256'        => $secretsha256,
						'customer'            => $customer,
						'url_ok'              => $url_ok,
						'product_description' => $product_description,
						'merchant_name'       => $merchant_name,
					);
					$secretsha256     = $redsys_data_send['secretsha256'];
					$order_total_sign = $redsys_data_send['order_total_sign'];
					$orderid2         = $redsys_data_send['transaction_id2'];
					$customer         = $redsys_data_send['customer'];
					$currency         = $redsys_data_send['currency'];
					$transaction_type = $redsys_data_send['transaction_type'];
					$terminal         = $redsys_data_send['DSMerchantTerminal'];
					$final_notify_url = $redsys_data_send['final_notify_url'];
					$url_ok           = $redsys_data_send['url_ok'];
					$gatewaylanguage  = $redsys_data_send['gatewaylanguage'];
					$merchant_name    = $redsys_data_send['merchant_name'];
					$merchan_name     = get_post_meta( $order_id, '_billing_first_name', true );
					$merchant_lastnme = get_post_meta( $order_id, '_billing_last_name', true );

					$miObj = new RedsysAPIWs();
					if ( ! empty( $this->merchantgroup ) ) {
						$ds_merchant_group = '<DS_MERCHANT_GROUP>' . $this->merchantgroup . '</DS_MERCHANT_GROUP>';
					} else {
						$ds_merchant_group = '';
					}

					$datos_usuario = array(
						'threeDSInfo'          => 'AuthenticationData',
						'protocolVersion'      => $protocolVersion,
						'browserAcceptHeader'  => $http_accept,
						'browserColorDepth'    => WCPSD2()->get_profundidad_color( $order_id ),
						'browserIP'            => $browserIP,
						'browserJavaEnabled'   => WCPSD2()->get_browserjavaenabled( $order_id ),
						'browserLanguage'      => WCPSD2()->get_idioma_navegador( $order_id ),
						'browserScreenHeight'  => WCPSD2()->get_altura_pantalla( $order_id ),
						'browserScreenWidth'   => WCPSD2()->get_anchura_pantalla( $order_id ),
						'browserTZ'            => WCPSD2()->get_diferencia_horaria( $order_id ),
						'browserUserAgent'     => WCPSD2()->get_agente_navegador( $order_id ),
						'notificationURL'      => $final_notify_url,
					);
					//$acctinfo       = WCPSD2()->get_acctinfo( $order, false , $user_id );
					$DATOS_ENTRADA  = '<DATOSENTRADA>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_AMOUNT>' . $order_total_sign . '</DS_MERCHANT_AMOUNT>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_ORDER>' . $orderid2 . '</DS_MERCHANT_ORDER>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_MERCHANTCODE>' . $customer . '</DS_MERCHANT_MERCHANTCODE>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_TERMINAL>' . $terminal . '</DS_MERCHANT_TERMINAL>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_TRANSACTIONTYPE>' . $transaction_type . '</DS_MERCHANT_TRANSACTIONTYPE>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_CURRENCY>' . $currency . '</DS_MERCHANT_CURRENCY>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_IDENTIFIER>' . $customer_token . '</DS_MERCHANT_IDENTIFIER>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_COF_INI>N</DS_MERCHANT_COF_INI>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_COF_TYPE>R</DS_MERCHANT_COF_TYPE>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_COF_TXNID>' . $txnid . '</DS_MERCHANT_COF_TXNID>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_EXCEP_SCA>MIT</DS_MERCHANT_EXCEP_SCA>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_DIRECTPAYMENT>TRUE</DS_MERCHANT_DIRECTPAYMENT>';
					$DATOS_ENTRADA .= '<DS_MERCHANT_EMV3DS>{"threeDSInfo":"CardData"}</DS_MERCHANT_EMV3DS>';
					$DATOS_ENTRADA .= "</DATOSENTRADA>";

					$XML = "<REQUEST>";
					$XML .= $DATOS_ENTRADA;
					$XML .= "<DS_SIGNATUREVERSION>HMAC_SHA256_V1</DS_SIGNATUREVERSION>";
					$XML .= "<DS_SIGNATURE>" . $miObj->createMerchantSignatureHostToHost( $secretsha256, $DATOS_ENTRADA ) . "</DS_SIGNATURE>";
					$XML .= "</REQUEST>";
					$type       = 'ws';
					$redsys_adr = $gateway->get_redsys_url_gateway( $user_id, $type );

					$CLIENTE  = new SoapClient( $redsys_adr );
					$responsews = $CLIENTE->iniciaPeticion( array( "datoEntrada" => $XML ) );
					

					if ( isset( $responsews->iniciaPeticionReturn ) ) {
						$XML_RETORNO = new SimpleXMLElement( $responsews->iniciaPeticionReturn );
						$respuesta   = (string) $XML_RETORNO->INFOTARJETA->Ds_EMV3DS;
					}

					$ds_emv3ds_json       = $XML_RETORNO->INFOTARJETA->Ds_EMV3DS;
					$ds_emv3ds            = json_decode( $ds_emv3ds_json );
					$protocolVersion      = $ds_emv3ds->protocolVersion;
					$threeDSServerTransID = $ds_emv3ds->threeDSServerTransID;
					$threeDSInfo          = $ds_emv3ds->threeDSInfo;
					
					if ( '2.1.0' === $protocolVersion || '2.2.0' === $protocolVersion ) {

						$DATOS_ENTRADA  = '<DATOSENTRADA>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_AMOUNT>' . $order_total_sign . '</DS_MERCHANT_AMOUNT>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_ORDER>' . $orderid2 . '</DS_MERCHANT_ORDER>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_MERCHANTCODE>' . $customer . '</DS_MERCHANT_MERCHANTCODE>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_TERMINAL>' . $terminal . '</DS_MERCHANT_TERMINAL>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_TRANSACTIONTYPE>' . $transaction_type . '</DS_MERCHANT_TRANSACTIONTYPE>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_CURRENCY>' . $currency . '</DS_MERCHANT_CURRENCY>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_IDENTIFIER>' . $customer_token . '</DS_MERCHANT_IDENTIFIER>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_COF_INI>N</DS_MERCHANT_COF_INI>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_COF_TYPE>R</DS_MERCHANT_COF_TYPE>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_COF_TXNID>' . $txnid . '</DS_MERCHANT_COF_TXNID>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_EXCEP_SCA>MIT</DS_MERCHANT_EXCEP_SCA>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_DIRECTPAYMENT>TRUE</DS_MERCHANT_DIRECTPAYMENT>';
						//$DATOS_ENTRADA .= '<DS_MERCHANT_EMV3DS>' . $acctinfo . '</DS_MERCHANT_EMV3DS>';
						$DATOS_ENTRADA .= '</DATOSENTRADA>';
						$XML            = "<REQUEST>";
						$XML           .= $DATOS_ENTRADA;
						$XML           .= "<DS_SIGNATUREVERSION>HMAC_SHA256_V1</DS_SIGNATUREVERSION>";
						$XML           .= "<DS_SIGNATURE>" . $miObj->createMerchantSignatureHostToHost( $secretsha256, $DATOS_ENTRADA ) . "</DS_SIGNATURE>";
						$XML           .= "</REQUEST>";
						
						$CLIENTE  = new SoapClient( $redsys_adr );
						$responsews = $CLIENTE->trataPeticion( array( "datoEntrada" => $XML ) );
						
						if ( isset( $responsews->trataPeticionReturn ) ) {
							$XML_RETORNO = new SimpleXMLElement( $responsews->trataPeticionReturn );
							$authorisationcode = (string) $XML_RETORNO->OPERACION->Ds_AuthorisationCode;
							$codigo            = (string) $XML_RETORNO->CODIGO;
							$redsys_order      = (string) $XML_RETORNO->OPERACION->Ds_Order;
							$terminal          = (string) $XML_RETORNO->OPERACION->Ds_Terminal;
							$currency_code     = (string) $XML_RETORNO->OPERACION->Ds_Currency;
						}
						if ( $authorisationcode ) {
							update_post_meta( $order->get_id(), '_redsys_done', 'yes' );
							$order->payment_complete();
							if ( ! empty( $redsys_order ) ) {
								update_post_meta( $order->get_id(), '_payment_order_number_redsys', $redsys_order );
							}
							if ( ! empty( $terminal ) ) {
								update_post_meta( $order->get_id(), '_payment_terminal_redsys', $terminal );
							}
							if ( ! empty( $authorisationcode ) ) {
								update_post_meta( $order->get_id(), '_authorisation_code_redsys', $authorisationcode );
							}
							if ( ! empty( $currency_code ) ) {
								update_post_meta( $order->get_id(), '_corruncy_code_redsys', $currency_code );
							}
							if ( ! empty( $secretsha256 ) ) {
								update_post_meta( $order->get_id(), '_redsys_secretsha256', $secretsha256 );
							}
							$order->add_order_note( __( 'Order payed succesfully', 'woocommerce-redsys' ) );
						} else {
							if ( ! WCRed()->check_order_is_paid_loop( $order->get_id() ) ) {
								$order->add_order_note( __( 'There wasn\'t respond from Redsys', 'woocommerce-redsys' ) );
								return false;
							} else {
								return true;
							}
						}
					} else {
						$protocolVersion = '1.0.2';

						$acctinfo       = WCPSD2()->get_acctinfo( $order, $datos_usuario );
						$DATOS_ENTRADA = "<DATOSENTRADA>";
						$DATOS_ENTRADA .= "<DS_MERCHANT_AMOUNT>" . $order_total_sign . "</DS_MERCHANT_AMOUNT>";
						$DATOS_ENTRADA .= "<DS_MERCHANT_ORDER>" . $orderid2 . "</DS_MERCHANT_ORDER>";
						$DATOS_ENTRADA .= "<DS_MERCHANT_MERCHANTCODE>" . $customer . "</DS_MERCHANT_MERCHANTCODE>";
						$DATOS_ENTRADA .= "<DS_MERCHANT_TERMINAL>" . $terminal . "</DS_MERCHANT_TERMINAL>";
						$DATOS_ENTRADA .= "<DS_MERCHANT_TRANSACTIONTYPE>0</DS_MERCHANT_TRANSACTIONTYPE>";
						$DATOS_ENTRADA .= "<DS_MERCHANT_CURRENCY>" . $currency . "</DS_MERCHANT_CURRENCY>";
						$DATOS_ENTRADA .= "<DS_MERCHANT_COF_INI>N</DS_MERCHANT_COF_INI>";
						$DATOS_ENTRADA .= "<DS_MERCHANT_COF_TYPE>R</DS_MERCHANT_COF_TYPE>";
						$DATOS_ENTRADA .= $ds_merchant_group;
						$DATOS_ENTRADA .= "<DS_MERCHANT_IDENTIFIER>" . $customer_token . "</DS_MERCHANT_IDENTIFIER>";
						$DATOS_ENTRADA .= '<DS_MERCHANT_COF_TXNID>' . $txnid . '</DS_MERCHANT_COF_TXNID>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_EXCEP_SCA>MIT</DS_MERCHANT_EXCEP_SCA>';
						$DATOS_ENTRADA .= '<DS_MERCHANT_DIRECTPAYMENT>TRUE</DS_MERCHANT_DIRECTPAYMENT>';
						//$DATOS_ENTRADA .= '<DS_MERCHANT_EMV3DS>' . $acctinfo . '</DS_MERCHANT_EMV3DS>';
						//$DATOS_ENTRADA .= "<DS_MERCHANT_MERCHANTURL>" . $final_notify_url . "</DS_MERCHANT_MERCHANTURL>";
						//$DATOS_ENTRADA .= "<DS_MERCHANT_TITULAR>" . $merchan_name . ' ' . $merchant_lastnme . "</DS_MERCHANT_TITULAR>";
						$DATOS_ENTRADA .= "</DATOSENTRADA>";
						$XML            = "<REQUEST>";
						$XML           .= $DATOS_ENTRADA;
						$XML           .= "<DS_SIGNATUREVERSION>HMAC_SHA256_V1</DS_SIGNATUREVERSION>";
						$XML           .= "<DS_SIGNATURE>" . $miObj->createMerchantSignatureHostToHost( $secretsha256, $DATOS_ENTRADA ) . "</DS_SIGNATURE>";
						$XML           .= "</REQUEST>";

						$CLIENTE    = new SoapClient( $redsys_adr );
						$responsews = $CLIENTE->trataPeticion( array( "datoEntrada" => $XML ) );

						if ( isset( $responsews->trataPeticionReturn ) ) {
							$XML_RETORNO = new SimpleXMLElement( $responsews->trataPeticionReturn );
							// $respuesta = json_decode( $XML_RETORNO->INFOTARJETA->Ds_EMV3DS );
						}

						$authorisationcode = (string) $XML_RETORNO->OPERACION->Ds_AuthorisationCode;
						$codigo            = (string) $XML_RETORNO->CODIGO;
						$redsys_order      = (string) $XML_RETORNO->OPERACION->Ds_Order;
						$terminal          = (string) $XML_RETORNO->OPERACION->Ds_Terminal;
						$currency_code     = (string) $XML_RETORNO->OPERACION->Ds_Currency;
						
						if ( $authorisationcode ) {
							echo 'yes in the condition';
							update_post_meta( $order_id, '_redsys_done', 'yes' );
							$order->payment_complete();
							if ( ! empty( $redsys_order ) ) {
								update_post_meta( $order->get_id(), '_payment_order_number_redsys', $redsys_order );
							}
							if ( ! empty( $terminal ) ) {
								update_post_meta( $order->get_id(), '_payment_terminal_redsys', $terminal );
							} 
							if ( ! empty( $authorisationcode ) ) {
								update_post_meta( $order->get_id(), '_authorisation_code_redsys', $authorisationcode );
							}
							if ( ! empty( $currency_code ) ) {
								update_post_meta( $order->get_id(), '_corruncy_code_redsys', $currency_code );
							}
							if ( ! empty( $secretsha256 ) ) {
								update_post_meta( $order->get_id(), '_redsys_secretsha256', $secretsha256 );	
							}
							$order->add_order_note( __( 'This order was paid successfully from Redsys', 'woocommerce-redsys' ) );
							return true;
						} else {
							if ( ! WCRed()->check_order_is_paid_loop( $order->get_id() ) ) {
								$order->add_order_note( __( 'There wasn\'t respond from Redsys', 'woocommerce-redsys' ) );
								return false;
							} else {
								return true;
							}
						}
					}
				}
			}
		}

		/**
		 * Force to set the token for redsys renewal payment.
		 *
		 * @param integer $order_id .
		 * @return void
		 */
		public function mwb_sfw_redsys_save_field_update_order_meta( $order_id ) {
			if ( in_array( 'woocommerce-gateway-redsys/woocommerce-gateway-redsys.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$mwb_has_subscription = mwb_sfw_is_cart_has_subscription_product();
				if ( $mwb_has_subscription ) {
					update_post_meta( $order_id, '_redsys_save_token', 'yes' );
					set_transient( $order_id . '_redsys_save_token', 'yes', 36000 );
				}
			}
		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name mwb_wsp_cancel_redsys_subscription
		 * @param int    $mwb_subscription_id mwb_subscription_id.
		 * @param string $status status.
		 * @since 2.0.0
		 * @return void
		 */
		public function mwb_wsp_cancel_redsys_subscription( $mwb_subscription_id, $status ) {

			$mwb_payment_method = get_post_meta( $mwb_subscription_id, '_payment_method', true );
			if ( $this->mwb_wsp_check_supported_payment_options( $mwb_payment_method ) ) {
				if ( 'Cancel' == $status ) {
					mwb_sfw_send_email_for_cancel_susbcription( $mwb_subscription_id );
					update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
				}
			}
		}
	}
}
new Mwb_Subscriptions_Payment_Redsys_Main();
