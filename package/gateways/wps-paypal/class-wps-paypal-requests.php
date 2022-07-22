<?php
/**
 * The request class for paypal payments.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/package/wps-build-in-paypal
 */

/**
 * Handles all api requests for paypal api.
 *
 * @since 1.6.4
 */
class WPS_Paypal_Requests {

	/**
	 * Client id.
	 *
	 * @var string
	 * @since 1.6.4
	 */
	public static $client_id;

	/**
	 * Client secret.
	 *
	 * @var string
	 * @since 1.6.4
	 */
	public static $client_secret;

	/**
	 * Either sanbox mode or not.
	 *
	 * @var boolean
	 * @since 1.6.4
	 */
	public static $testmode;

	/**
	 * Current payment method.
	 *
	 * @var object
	 * @since 1.6.4
	 */
	public $payment_method;

	/**
	 * Constructor for request class.
	 *
	 * @param object $payment_method payment method.
	 * @since 1.6.4
	 */
	public function __construct( $payment_method ) {
		$this->payment_method = $payment_method;
	}

	/**
	 * Get access token for paypal REST APIs.
	 *
	 * @since  1.0.0
	 * @throws Exception As handling Exception.
	 * @return array
	 */
	public static function get_access_token() {
		$endpoint = self::$testmode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

		try {
			$response = wp_remote_post(
				$endpoint . '/v1/oauth2/token',
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(
						'Accept' => 'application/json',
						'Accept-Language' => 'en_US',
						'Authorization'   => 'Basic ' . base64_encode( self::$client_id . ':' . self::$client_secret ),
					),
					'body' => array(
						'grant_type' => 'client_credentials',
					),
				)
			);

			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				$response = json_decode( wp_remote_retrieve_body( $response ) );
				return array(
					'result'       => 'success',
					'access_token' => isset( $response->access_token ) ? $response->access_token : '',
					'app_id'       => isset( $response->app_id ) ? $response->app_id : '',
				);
			}
			throw new Exception( __( 'Unable to generate access token', 'wps-paypal-integration-for-woocommerce' ) );

		} catch ( Exception $e ) {
			return array(
				'result' => 'error',
				'msg'    => $e->getMessage(),
			);
		}
	}

	/**
	 * Paypal create order.
	 *
	 * @param WC_Order $order current order object.
	 * @since 1.6.4
	 * @throws Exception As handling expception.
	 * @return array
	 */
	public function paypal_create_order( $order ) {
		$access_response = self::get_access_token();

		if ( 'success' !== $access_response['result'] ) {
			return array(
				'result'   => 'error',
				'redirect' => '',
			);
		}

		try {
			$endpoint   = self::$testmode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
			$request_id = uniqid( 'wps_paypal-', true );
			$url        = $endpoint . '/v2/checkout/orders';
			$args       = array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'Authorization'                 => 'Bearer ' . $access_response['access_token'],
					'Content-Type'                  => 'application/json',
					'Prefer'                        => 'return=representation',
					'PayPal-Request-Id'             => $request_id,
					'PayPal-Partner-Attribution-Id' => 'Woo-wps_paypal',
				),
				'body'        => wp_json_encode(
					array(
						'intent'              => 'CAPTURE',
						'purchase_units'      => array(
							$this->get_purchase_details( $order ),
						),
						'application_context' => array(
							'brand_name'          => get_bloginfo( 'name' ),
							'return_url'          => $order->get_checkout_order_received_url(),
							'cancel_url'          => wc_get_checkout_url(),
							'landing_page'        => 'NO_PREFERENCE',
							'shipping_preference' => 'SET_PROVIDED_ADDRESS',
							'user_action'         => 'PAY_NOW',
						),
						'payment_method'      => array(
							'payee_preferred' => 'UNRESTRICTED',
							'payer_selected'  => 'PAYPAL',
						),
						'payer'               => array(
							'name'          => array(
								'given_name' => $order->get_billing_first_name(),
								'surname'    => $order->get_billing_last_name(),
							),
							'email_address' => $order->get_billing_email(),
							'address'       => array(
								'country_code'   => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country(),
								'address_line_1' => $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1(),
								'address_line_2' => $order->get_shipping_address_2() ? $order->get_shipping_address_2() : $order->get_billing_address_2(),
								'postal_code'    => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),

							),
						),
						'payment_source' => array(
							'paypal' => array(
								'attributes' => array(
									'customer' => array(
										'id' => 'wps_paypal_' . get_current_user_id(),
									),
									'vault' => array(
										'confirm_payment_token' => 'ON_ORDER_COMPLETION',
										'usage_type' => 'MERCHANT',
										'customer_type' => 'CONSUMER',
									),
								),
							),
						),
					)
				),
			);

			$response = wp_remote_post( $url, $args );

			if ( ! is_wp_error( $response ) && in_array( (int) wp_remote_retrieve_response_code( $response ), array( 200, 201, 202, 204 ), true ) ) {
				$response = json_decode( wp_remote_retrieve_body( $response ) );
				if ( isset( $response->links ) ) {
					foreach ( $response->links as $link ) {
						if ( 'approve' === $link->rel || 'payer-action' === $link->rel ) {
							update_post_meta( $order->get_id(), 'wps_paypal_request_id', $request_id );
							return array(
								'result'   => 'success',
								'redirect' => $link->href,
							);
						}
					}
				}
			}

			throw new Exception( __( 'Unable to create order', 'wps-paypal-integration-for-woocommerce' ) . wp_remote_retrieve_body( $response ) );
		} catch ( Exception $e ) {
			return array(
				'result'    => 'error',
				'error_msg' => $e->getMessage(),
			);
		}
	}

	/**
	 * Get purchase details.
	 *
	 * @param WC_Order $order current order object.
	 * @since 1.6.4
	 * @return array
	 */
	public function get_purchase_details( $order ) {
		$saved_setting = get_option( 'woocommerce_wps_paypal_settings', array() );
		if ( ! empty( $saved_setting ) && is_array( $saved_setting ) && isset( $saved_setting['invoice_prefix'] ) && ! empty( $saved_setting['invoice_prefix'] ) ) {
			$invoice_prefix = $saved_setting['invoice_prefix'];
		} else {
			$invoice_prefix = 'wps-paypal-';
		}

		$purchase_details = array(
			'reference_id'                    => 'wps-paypal',
			'custom_id'                       => $this->limit_length( $order->get_order_number() ),
			'invoice_id'                      => $this->limit_length( $invoice_prefix . wp_rand( 10, 99 ) . $order->get_id() ),
			'payee_payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
			'items'                           => $this->get_item_details( $order ),
			'shipping'                        => $this->get_shipping_details( $order ),
			'amount'                          => array(
				'value'         => $order->get_total(),
				'currency_code' => $order->get_currency(),
				'breakdown'     => array(
					'item_total' => array(
						'value'         => $this->limit_length( $order->get_subtotal(), 32 ),
						'currency_code' => $this->limit_length( $order->get_currency(), 3 ),
					),
					'shipping'   => array(
						'value'         => $this->limit_length( $order->get_shipping_total(), 32 ),
						'currency_code' => $this->limit_length( $order->get_currency(), 3 ),
					),
					'tax_total'  => array(
						'value'         => $this->limit_length( $order->get_total_tax(), 32 ),
						'currency_code' => $this->limit_length( $order->get_currency(), 3 ),
					),
					'discount'   => array(
						'value'         => $this->limit_length( $order->get_total_discount(), 32 ),
						'currency_code' => $this->limit_length( $order->get_currency(), 3 ),
					),
				),
			),
		);

		return $purchase_details;
	}

	/**
	 * Get line item details.
	 *
	 * @param WC_Order $order current order object.
	 * @since 1.6.4
	 * @return array
	 */
	public function get_item_details( $order ) {
		$items        = $order->get_items();
		$item_details = array();
		foreach ( $items as $item ) {
			$item_details[] = array(
				'name'        => $this->limit_length( $item->get_name() ),
				'unit_amount' => array(
					'currency_code' => $order->get_currency(),
					'value'         => ( $item->get_total() ) / ( $item->get_quantity() ),
				),
				'quantity' => $item->get_quantity(),
			);
		}
		return $item_details;
	}

	/**
	 * Get order shipping details to send at paypal.
	 *
	 * @param WC_Order $order current WC_Order order object.
	 * @since 1.6.4
	 * @return array
	 */
	public function get_shipping_details( $order ) {
		return array(
			'name'    => array(
				'full_name' => ! empty( $order->get_formatted_billing_full_name() ) ? $order->get_formatted_billing_full_name() : ( ! empty( $order->get_formatted_billing_full_name() ) ? $order->get_formatted_billing_full_name() : '*******' ),
			),
			'type'    => ( 'local_pickup' === $order->get_shipping_method() ) ? 'PICKUP_IN_PERSON' : 'SHIPPING',
			'address' => array(
				'address_line_1' => $this->limit_length( ! empty( $order->get_shipping_address_1() ) ? $order->get_shipping_address_1() : ( ! empty( $order->get_billing_address_1() ) ? $order->get_billing_address_1() : '******' ), 300 ),
				'address_line_2' => $this->limit_length( ! empty( $order->get_shipping_address_2() ) ? $order->get_shipping_address_2() : ( ! empty( $order->get_billing_address_2() ) ? $order->get_billing_address_2() : '******' ), 300 ),
				'admin_area_2'   => $this->limit_length( ! empty( $order->get_shipping_city() ) ? $order->get_shipping_city() : ( ! empty( $order->get_billing_city() ) ? $order->get_billing_city() : '****' ), 120 ),
				'admin_area_1'   => $this->limit_length( ! empty( $order->get_shipping_state() ) ? $order->get_shipping_state() : ( ! empty( $order->get_billing_state() ) ? $order->get_billing_state() : '*****' ), 300 ),
				'postal_code'    => $this->limit_length( ! empty( $order->get_shipping_postcode() ) ? $order->get_shipping_postcode() : ( ! empty( $order->get_billing_postcode() ) ? $order->get_billing_postcode() : '000000' ), 60 ),
				'country_code'   => ! empty( $order->get_shipping_country() ) ? $order->get_shipping_country() : ( ! empty( $order->get_billing_country() ) ? $order->get_billing_country() : '**' ),
			),
		);
	}

	/**
	 * Limit length of the string.
	 *
	 * @param string  $string string to crop.
	 * @param integer $limit limit of the length of the string.
	 * @since 1.6.4
	 * @return string
	 */
	protected function limit_length( $string, $limit = 127 ) {
		$str_limit = $limit - 3;
		if ( function_exists( 'mb_strimwidth' ) ) {
			if ( mb_strlen( $string ) > $limit ) {
				$string = mb_strimwidth( $string, 0, $str_limit ) . '...';
			}
		} else {
			if ( strlen( $string ) > $limit ) {
				$string = substr( $string, 0, $str_limit ) . '...';
			}
		}
		return $string;
	}

	/**
	 * Capture payment for the current order.
	 *
	 * @param object  $order current order object.
	 * @param integer $paypal_order_id paypal order id.
	 * @since 1.6.4
	 * @throws Exception As handling expception.
	 * @return array
	 */
	public static function do_capture( $order, $paypal_order_id ) {
		$access_response = self::get_access_token();
		if ( 'success' !== $access_response['result'] ) {
			return array(
				'result'   => 'error',
				'response' => '',
			);
		}

		try {
			$endpoint = self::$testmode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
			$response = wp_remote_post(
				$endpoint . '/v2/checkout/orders/' . $paypal_order_id . '/capture',
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(
						'Authorization'                 => 'Bearer ' . $access_response['access_token'],
						'Content-Type'                  => 'application/json',
						'Prefer'                        => 'return=representation',
						'PayPal-Request-Id'             => get_post_meta( $order->get_id(), 'wps_paypal_request_id', true ),
						'PayPal-Partner-Attribution-Id' => 'Woo-wps_paypal',
					),
				)
			);
			update_post_meta( $order->get_id(), 'wps_order_payment_token', json_decode( $response['body'], true )['payment_source']['paypal']['attributes']['vault']['id'] );

			if ( ! is_wp_error( $response ) && in_array( (int) wp_remote_retrieve_response_code( $response ), array( 200, 201 ), true ) ) {
				return array(
					'result'   => 'success',
					'response' => json_decode( wp_remote_retrieve_body( $response ) ),
				);
			}
			throw new Exception( __( 'Unable to capture the payment : response', 'wps-paypal-integration-for-woocommerce' ) );

		} catch ( Exception $e ) {
			return array(
				'result'   => 'error',
				'response' => $e->getMessage(),
			);
		}
	}

	/**
	 * Refund order from paypal.
	 *
	 * @param object $order current order object.
	 * @param float  $amount amount to refund.
	 * @param string $reason reason for refund.
	 * @since 1.6.4
	 * @throws Exception As handling expception.
	 * @return array
	 */
	public static function refund_order( $order, $amount, $reason ) {
		$access_response = self::get_access_token();
		if ( 'success' !== $access_response['result'] ) {
			return array(
				'result'   => 'error',
				'response' => '',
			);
		}

		$endpoint = $order->get_meta( '_wps_paypal_refund_link' );

		try {
			if ( ! empty( $endpoint ) ) {
				$response = wp_remote_post(
					$endpoint,
					array(
						'method'      => 'POST',
						'timeout'     => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking'    => true,
						'headers'     => array(
							'Authorization' => 'Bearer ' . $access_response['access_token'],
							'Content-Type'  => 'application/json',
						),
						'body' => wp_json_encode(
							array(
								'amount' => array(
									'value'         => $amount,
									'currency_code' => $order->get_currency(),
								),
								'note_to_payer' => strlen( $reason ) > 3 ? $reason : '***',
							)
						),
					)
				);

				if ( ! is_wp_error( $response ) && in_array( (int) wp_remote_retrieve_response_code( $response ), array( 201, 200 ), true ) ) {
					return array(
						'result'   => 'success',
						'response' => json_decode( wp_remote_retrieve_body( $response ) ),
					);
				}

				throw new Exception( __( 'Error', 'wps-paypal-integration-for-woocommerce' ) . wp_remote_retrieve_body( $response ) );
			}
		} catch ( Exception $e ) {
			return array(
				'result'   => 'error',
				'response' => $e->getMessage(),
			);
		}

		return array(
			'result'   => 'error',
			'response' => '',
		);
	}

	/**
	 * Verify IPN response.
	 *
	 * @param array $ipn_data array containing IPN data.
	 * @since 1.6.4
	 * @return boolean
	 */
	public static function verify_ipn_response( $ipn_data ) {
		$ipn_data['cmd'] = '_notify-validate';
		$endpoint        = self::$testmode ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' : 'https://ipnpb.paypal.com/cgi-bin/webscr';
		$response        = wp_remote_post(
			$endpoint,
			array(
				'method'      => 'POST',
				'timeout'     => 60,
				'httpversion' => '1.1',
				'blocking'    => true,
				'compress'    => false,
				'decompress'  => false,
				'headers'     => array(
					'User-Agent' => 'WooCommerce/' . WC()->version,
				),
				'body'        => $ipn_data,
			)
		);

		if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) >= 200 && (int) wp_remote_retrieve_response_code( $response ) < 300 ) {
			return ( 'VERIFIED' === wp_remote_retrieve_body( $response ) ) ? true : false;
		}
		return false;

	}

	/**
	 * Create renewal payment
	 *
	 * @param [type] $token .
	 * @param [type] $order .
	 * @return $response
	 */
	public function create_renewal_payment( $token, $order ) {
		$access_response = self::get_access_token();
		$data   = array(
			'intent'         => 'CAPTURE',
			'purchase_units' => array(
				$this->get_purchase_details( $order ),
			),
			'application_context' => array(
				'brand_name'          => get_bloginfo( 'name' ),
				'return_url'          => $order->get_checkout_order_received_url(),
				'cancel_url'          => wc_get_checkout_url(),
				'landing_page'        => 'LOGIN',
				'shipping_preference' => 'GET_FROM_FILE',
				'user_action'         => 'CONTINUE',
			),
		);

		$payer = array(
			'name'          => array(
				'given_name' => $order->get_billing_first_name(),
				'surname'    => $order->get_billing_last_name(),
			),
			'email_address' => $order->get_billing_email(),
		);
		$payer['address'] = array(
			'country_code'   => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country(),
			'address_line_1' => $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1(),
			'address_line_2' => $order->get_shipping_address_2() ? $order->get_shipping_address_2() : $order->get_billing_address_2(),
			'postal_code'    => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
		);
		// $payer['payer_id'] = get_post_meta( $parent_order->get_id(), '_wps_paypal_payer_id', true );

		$data['payer'] = $payer;

		$data['payment_source']['token'] = array(
			'id'   => $token,
			'type' => 'PAYMENT_METHOD_TOKEN',
		);
		$data['payment_source']['source'] = array(
			'paypal' => array( 'email_address', $order->get_billing_email() ),
		);

		$endpoint = self::$testmode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

		$url        = $endpoint . '/v2/checkout/orders';
		$request_id = uniqid( 'wps_paypal-', true );
		$args       = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization'                 => 'Bearer ' . $access_response['access_token'],
				'Content-Type'                  => 'application/json',
				'Prefer'                        => 'return=representation',
				'PayPal-Request-Id'             => $request_id,
				'PayPal-Partner-Attribution-Id' => 'Woo-wps_paypal',
			),
			'body'    => wp_json_encode( $data ),
		);

		update_post_meta( $order->get_id(), 'wps_paypal_request_id', $request_id );

		$response = wp_remote_post( $url, $args );

		$json = json_decode( $response['body'] );

		if ( class_exists( 'Subscriptions_For_Woocommerce_Log' ) ) {
			Subscriptions_For_Woocommerce_Log::log( 'WPS Paypal Renewal Response: ' . wc_print_r( $json, true ) );
		}

		if ( is_wp_error( $response ) ) {
			$order_notes = __( 'renewal payment failed', 'wps-paypal-integration-for-woocommerce' );
			$order->update_status( 'failed', $order_notes );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 'COMPLETED' === $json->status ) {
			$txn_id = $json->purchase_units[0]->payments->captures[0]->id;
			$order->add_order_note(
				sprintf(
					/* translators: %s transaction ID. */
					__( 'payment completed txn ID : %s', 'wps-paypal-integration-for-woocommerce' ),
					$txn_id
				)
			);
			if ( isset( $json->purchase_units ) ) {
				$links = array_shift( array_shift( $json->purchase_units )->payments->captures )->links;
				foreach ( $links as $link ) {
					if ( 'refund' === $link->rel ) {
						$order->update_meta_data( '_wps_paypal_refund_link', esc_url( $link->href ) );
					}
				}
			}
			$order->payment_complete( $txn_id );
			$order->update_meta_data( '_wps_paypal_payment_status', 'completed' );
			$order->update_meta_data( '_wps_paypal_order_id', $json->id );
			$order->update_meta_data( '_wps_paypal_payment_status', 'captured' );
			$order->save();
		} else {
			$order_notes = __( 'renewal payment failed', 'wps-paypal-integration-for-woocommerce' );
			$order->update_status( 'failed', $order_notes );
		}

		return $json;
	}

	/**
	 * Get saved customer token from Paypal server via API
	 *
	 * @param array $token_id as token id.
	 * @return $tokens
	 */
	public function wps_validate_saved_customer_token( $token_id ) {

		$access_response = self::get_access_token();

		$endpoint = self::$testmode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

		$url  = $endpoint . '/v2/vault/payment-tokens/' . $token_id;
		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_response['access_token'],
				'Content-Type'  => 'application/json',
			),
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return __( 'Could not fetch payment token.', 'wps-paypal-integration-for-woocommerce' );
		}

		$json        = json_decode( $response['body'] );
		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return __( 'Could not fetch payment token.', 'wps-paypal-integration-for-woocommerce' );
		}

		$token = array();
		if ( ! empty( $json ) ) {
			$token = $json;
		}
		return $token;
	}
}
