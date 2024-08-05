<?php
/**
 * The file that defines the main payment class
 *
 * @link  https://wpswings.com/
 * @since 1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/package/wps-build-in-paypal
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main payment class.
 *
 * This is used to extend the WC_Payment_Gateway class.
 *
 * @since      1.0.0
 * @package    wps_PayPal_Integration_for_WooCommerce
 * @subpackage wps_PayPal_Integration_for_WooCommerce/includes/paypal
 */
class WC_Gateway_Wps_Paypal_Integration extends WC_Payment_Gateway {
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
	 * Define the main attributes and methods to be set in the parent class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->id                 = 'wps_paypal';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Proceed to PayPal', 'subscriptions-for-woocommerce' );
		$this->method_title       = __( 'WPS Paypal Payment', 'subscriptions-for-woocommerce' );
		$this->method_description = __( 'Accept Payments from PayPal ( Recurring Support for US only )', 'subscriptions-for-woocommerce' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled       = $this->get_option( 'enabled' );
		$this->title         = $this->get_option( 'title' );
		$this->description   = $this->get_option( 'description' );
		self::$testmode      = $this->get_option( 'testmode' );
		self::$client_id     = $this->get_option( 'client_id' );
		self::$client_secret = $this->get_option( 'client_secret' );

		if ( 'no' == self::$testmode ) {
			self::$testmode = false;
		}
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'wps_sfw_capture_payment' ), 5 );
	}

	/**
	 * Show admin options is valid for use.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error">
				<p>
					<strong><?php esc_html_e( 'Gateway disabled', 'subscriptions-for-woocommerce' ); ?></strong>: <?php esc_html_e( 'PayPal Standard does not support your store currency.', 'subscriptions-for-woocommerce' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Can the order be refunded via PayPal?
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		$has_api_creds = $this->get_option( 'client_id' ) && $this->get_option( 'client_secret' );
		return $order && $order->get_meta( '_wps_paypal_refund_link' ) && $has_api_creds;
	}

	/**
	 * Process payment refund from paypal.
	 *
	 * @param integer $order_id current order id.
	 * @param float   $amount amount to refund.
	 * @param string  $reason reason to update for refund.
	 * @since 1.0.0
	 * @return boolean|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'error', __( 'Unable to proceed the refund process', 'subscriptions-for-woocommerce' ) );
		}

		if ( empty( $amount ) || $amount <= 0 ) {
			return new WP_Error( 'error', __( 'Please enter the amount to refund', 'subscriptions-for-woocommerce' ) );
		}

		$this->init_api();
		$response = WPS_Paypal_Requests::refund_order( $order, $amount, $reason );
		if ( 'success' === $response['result'] ) {
			if ( 'COMPLETED' === $response['response']->status ) {
				wps_sfw_update_meta_data( $order_id, '_wps_paypal_payment_status', 'refunded' );
				$order->add_order_note(
					sprintf(
						/* translators: %s paypal refund ID. */
						__( 'Refunded : %1$s from PayPal- Refund ID : %2$s', 'subscriptions-for-woocommerce' ),
						$amount,
						$response['response']->id
					)
				);
				return true;
			}
		}

		return new WP_Error( 'error', __( 'Unable to refund the amount from PayPal.', 'subscriptions-for-woocommerce' ) );
	}

	/**
	 * Check if paypal can be used for the currency selected in the store.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_valid_for_use() {
		return in_array(
			get_woocommerce_currency(),
			apply_filters(
				'wps_paypal_supported_currencies',
				array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RUB', 'INR' )
			),
			true
		);
	}

	/**
	 * Form fields to show for payment gateway.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_form_fields() {

		$data = get_option( 'woocommerce_wps_paypal_settings' );
		if ( ! empty( $data ) ) {
			if ( key_exists( 'wps_validate_button', $data ) ) {

				if ( '' == $data['wps_validate_button'] ) {
					$data['wps_validate_button'] = 'Validate';
					update_option( 'woocommerce_wps_paypal_settings', $data );
				}
			}
		}

		$this->form_fields = array(
			'enabled'               => array(
				'title'   => __( 'Enable/Disable', 'subscriptions-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable WP Swings PayPal', 'subscriptions-for-woocommerce' ),
				'default' => 'no',
			),
			'title'                 => array(
				'title'       => __( 'Title', 'subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'subscriptions-for-woocommerce' ),
				'default'     => __( 'PayPal', 'subscriptions-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'description'           => array(
				'title'       => __( 'Description', 'subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user sees during checkout.', 'subscriptions-for-woocommerce' ),
				'default'     => __( "Pay via PayPal; you can pay with your credit card if you don't have a PayPal account.", 'subscriptions-for-woocommerce' ),
			),
			'ipn_notification'      => array(
				'title'       => __( 'IPN email notifications', 'subscriptions-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable IPN email notifications', 'subscriptions-for-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf(
					/* translators: navigate to setting and update url. */
					__( 'Send notifications when an IPN is received from PayPal indicating refunds, chargebacks and cancellations. Please make sure to login your PayPal business account navigate to %s  <button class="wps-ipn-url-copy-to-clipboard">Copy</button>', 'subscriptions-for-woocommerce' ),
					'<b> Account-settings > Notifications > Instant payment notifications </b> :: click on Update and enter the url provided below and make sure to select the Receive IPN messages (Enabled) and then save. <span id="wps-ipn-url-copy">' . home_url( '/' ) . 'wc-api/wc_gateway_wps_paypal_integration</span>'
				),
			),
			'email'        => array(
				'title'       => __( 'Email', 'subscriptions-for-woocommerce' ),
				'type'        => 'email',
				'description' => __( 'You will recieve IPN email notifications on this mail.', 'subscriptions-for-woocommerce' ),
				'default'     => get_option( 'admin_email' ),
				'desc_tip'    => true,
				'placeholder' => 'you@youremail.com',
			),
			'invoice_prefix'        => array(
				'title'       => __( 'Invoice prefix', 'subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'subscriptions-for-woocommerce' ),
				'default'     => 'wps-paypal-',
				'desc_tip'    => true,
			),
			'api_details'           => array(
				'title'       => __( 'API credentials', 'subscriptions-for-woocommerce' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: 1: paypal developer home url 2: create application link */
					__( 'To get your API credentials please create a <a href="%1$s" target="_blank">PayPal developer account</a>. Visit <a href="%2$s" target="_blank">My Apps & Credentials</a> select the tab ( Sandbox or Live ), Create app and get the below credentails.', 'subscriptions-for-woocommerce' ),
					'https://developer.paypal.com',
					'https://developer.paypal.com/developer/applications'
				),
			),
			'testmode'              => array(
				'title'       => __( 'PayPal sandbox', 'subscriptions-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable PayPal sandbox', 'subscriptions-for-woocommerce' ),
				'default'     => 'no',
				/* translators: %s: URL */
				'description' => sprintf( __( 'PayPal sandbox can be used to test payments. Make Sure you have filled the Test Credentials below If you are using the test mode. Sign up for a <a href="%s">developer account</a>.', 'subscriptions-for-woocommerce' ), 'https://developer.paypal.com/' ),
			),
			'client_id'          => array(
				'title'             => __( 'Client ID', 'subscriptions-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Please enter client ID here after following the above mentioned process.', 'subscriptions-for-woocommerce' ),
				'default'           => '',
				'desc_tip'          => true,
				'custom_attributes' => array( 'autocomplete' => 'new-password' ),
			),
			'client_secret'          => array(
				'title'             => __( 'Client secret', 'subscriptions-for-woocommerce' ),
				'type'              => 'password',
				'description'       => __( 'Please enter client secret here after following the above mentioned process.', 'subscriptions-for-woocommerce' ),
				'default'           => '',
				'desc_tip'          => true,
				'custom_attributes' => array( 'autocomplete' => 'new-password' ),
			),
			'wps_validate_button'  => array(
				'title'       => '',
				'type'        => 'button',
				'label'       => __( 'Enable PayPal sandbox', 'subscriptions-for-woocommerce' ),
				'class'       => 'button wps_sfw_paypal_validate',
				'default'     => __( 'Validate', 'subscriptions-for-woocommerce' ),
			),
		);
	}

	/**
	 * Process payments.
	 *
	 * @param int $order_id current order id.
	 * @since 1.0.0
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		$response = self::paypal_create_order( $order );
		if ( 'success' !== $response['result'] ) {
			return $response;
		}
		return $response;
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

				throw new Exception( __( 'Error', 'subscriptions-for-woocommerce' ) . wp_remote_retrieve_body( $response ) );
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
			$response_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				$response = json_decode( wp_remote_retrieve_body( $response ) );
				return array(
					'result'       => 'success',
					'access_token' => isset( $response->access_token ) ? $response->access_token : '',
					'app_id'       => isset( $response->app_id ) ? $response->app_id : '',
				);
			}
			throw new Exception( $response_data->error_description );

		} catch ( Exception $e ) {
			return array(
				'result' => 'error',
				'response' => $e->getMessage(),
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
	public static function paypal_create_order( $order ) {
		$access_response = self::get_access_token();
		if ( 'success' !== $access_response['result'] ) {
			return array(
				'result'   => 'error',
				'redirect' => '',
				'response' => $access_response['response'],
			);
		}

		try {
			$endpoint   = self::$testmode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
			$request_id = uniqid( 'wps-paypal-', true );
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
							self::get_purchase_details( $order ),
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

			$response_data = json_decode( wp_remote_retrieve_body( $response ) );
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( ! is_wp_error( $response ) && in_array( (int) $response_code, array( 200, 201, 202, 204 ), true ) ) {
				$response = json_decode( wp_remote_retrieve_body( $response ) );
				if ( isset( $response->links ) ) {
					foreach ( $response->links as $link ) {
						if ( 'approve' === $link->rel || 'payer-action' === $link->rel ) {
							wps_sfw_update_meta_data( $order->get_id(), 'wps_paypal_request_id', $request_id );
							return array(
								'result'   => 'success',
								'redirect' => $link->href,
							);
						}
					}
				}
			} elseif ( 401 == $response_code ) {
				return array(
					'result'   => 'error',
					'response' => $response_data->message,
					'redirect' => '',
				);
			}
			throw new Exception( __( 'Unable to create order', 'subscriptions-for-woocommerce' ) . wp_remote_retrieve_body( $response ) );
		} catch ( Exception $e ) {
			return array(
				'result'    => 'error',
				'response' => $e->getMessage(),
				'redirect' => '',
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
	public static function get_purchase_details( $order ) {
		$saved_setting = get_option( 'woocommerce_wps_paypal_settings', array() );
		if ( ! empty( $saved_setting ) && is_array( $saved_setting ) && isset( $saved_setting['invoice_prefix'] ) && ! empty( $saved_setting['invoice_prefix'] ) ) {
			$invoice_prefix = $saved_setting['invoice_prefix'];
		} else {
			$invoice_prefix = 'wps-paypal-';
		}

		$purchase_details = array(
			'reference_id'                    => uniqid( 'wps-paypal-', true ),
			'payee_payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
			'shipping'                        => self::get_shipping_details( $order ),
			'amount'                          => array(
				'value'         => $order->get_total(),
				'currency_code' => $order->get_currency(),
			),
		);

		return $purchase_details;
	}

	/**
	 * Get order shipping details to send at paypal.
	 *
	 * @param WC_Order $order current WC_Order order object.
	 * @since 1.6.4
	 * @return array
	 */
	public static function get_shipping_details( $order ) {
		return array(
			'name'    => array(
				'full_name' => ! empty( $order->get_formatted_billing_full_name() ) ? $order->get_formatted_billing_full_name() : ( ! empty( $order->get_formatted_billing_full_name() ) ? $order->get_formatted_billing_full_name() : '*******' ),
			),
			'type'    => ( 'local_pickup' === $order->get_shipping_method() ) ? 'PICKUP_IN_PERSON' : 'SHIPPING',
			'address' => array(
				'address_line_1' => self::limit_length( ! empty( $order->get_shipping_address_1() ) ? $order->get_shipping_address_1() : ( ! empty( $order->get_billing_address_1() ) ? $order->get_billing_address_1() : '******' ), 300 ),
				'address_line_2' => self::limit_length( ! empty( $order->get_shipping_address_2() ) ? $order->get_shipping_address_2() : ( ! empty( $order->get_billing_address_2() ) ? $order->get_billing_address_2() : '******' ), 300 ),
				'admin_area_2'   => self::limit_length( ! empty( $order->get_shipping_city() ) ? $order->get_shipping_city() : ( ! empty( $order->get_billing_city() ) ? $order->get_billing_city() : '****' ), 120 ),
				'admin_area_1'   => self::limit_length( ! empty( $order->get_shipping_state() ) ? $order->get_shipping_state() : ( ! empty( $order->get_billing_state() ) ? $order->get_billing_state() : '*****' ), 300 ),
				'postal_code'    => self::limit_length( ! empty( $order->get_shipping_postcode() ) ? $order->get_shipping_postcode() : ( ! empty( $order->get_billing_postcode() ) ? $order->get_billing_postcode() : '000000' ), 60 ),
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
	protected static function limit_length( $string, $limit = 127 ) {
		$str_limit = $limit - 3;
		if ( function_exists( 'mb_strimwidth' ) ) {
			if ( mb_strlen( $string ) > $limit ) {
				$string = mb_strimwidth( $string, 0, $str_limit ) . '...';
			}
		} elseif ( strlen( $string ) > $limit ) {
				$string = substr( $string, 0, $str_limit ) . '...';
		}
		return $string;
	}

	/**
	 * Get line item details.
	 *
	 * @param WC_Order $order current order object.
	 * @since 1.6.4
	 * @return array
	 */
	public static function get_item_details( $order ) {
		$items        = $order->get_items();
		$item_details = array();
		foreach ( $items as $item ) {
			$item_details[] = array(
				'name'        => self::limit_length( $item->get_name() ),
				'unit_amount' => array(
					'currency_code' => $order->get_currency(),
					'value'         => number_format( ( $item->get_total() / $item->get_quantity() ), 2 ),
				),
				'quantity' => $item->get_quantity(),
			);
		}
		return $item_details;
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
	public static function wps_sfw_do_capture( $order, $paypal_order_id ) {
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
						'PayPal-Request-Id'             => wps_sfw_get_meta_data( $order->get_id(), 'wps_paypal_request_id', true ),
						'PayPal-Partner-Attribution-Id' => 'Woo-wps_paypal',
					),
				)
			);
			wps_sfw_update_meta_data( $order->get_id(), 'wps_order_payment_token', json_decode( $response['body'], true )['payment_source']['paypal']['attributes']['vault']['id'] );

			if ( ! is_wp_error( $response ) && in_array( (int) wp_remote_retrieve_response_code( $response ), array( 200, 201 ), true ) ) {
				return array(
					'result'   => 'success',
					'response' => json_decode( wp_remote_retrieve_body( $response ) ),
				);
			}
			throw new Exception( __( 'Unable to capture the payment : response', 'subscriptions-for-woocommerce' ) );

		} catch ( Exception $e ) {
			return array(
				'result'   => 'error',
				'response' => $e->getMessage(),
			);
		}
	}

	/**
	 * Capture payment from paypal.
	 *
	 * @param integer $order_id current order id.
	 * @return void
	 * @since 1.0.0
	 */
	public function wps_sfw_capture_payment( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( $this->id !== $order->get_payment_method() ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification

		if ( isset( $_GET['token'] ) && isset( $_GET['PayerID'] ) ) {
			if ( 'captured' !== $order->get_meta( '_wps_paypal_payment_status' ) ) {
				$paypal_order_token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
				$paypal_payer_id    = sanitize_text_field( wp_unslash( $_GET['PayerID'] ) );
				$order->update_meta_data( '_wps_paypal_order_id', $paypal_order_token );
				$order->update_meta_data( '_wps_paypal_payer_id', $paypal_payer_id );
				$order->update_meta_data( '_wps_paypal_payment_status', 'captured' );
				$response = self::wps_sfw_do_capture( $order, $paypal_order_token );

				$order->add_order_note(
					sprintf(
						/* translators: paypal order id. */
						esc_html__( 'order captured from PayPal for PayPal order ID : %s', 'subscriptions-for-woocommerce' ),
						esc_html( $paypal_order_token )
					)
				);
				if ( 'success' === $response['result'] && 'COMPLETED' === $response['response']->status ) {
					$txn_id = $response['response']->purchase_units[0]->payments->captures[0]->id;

					if ( isset( $response['response']->purchase_units ) ) {
						$links = array_shift( array_shift( $response['response']->purchase_units )->payments->captures )->links;
						foreach ( $links as $link ) {
							if ( 'refund' === $link->rel ) {
								$order->update_meta_data( '_wps_paypal_refund_link', esc_url( $link->href ) );
							}
						}
					}

					$order->add_order_note(
						sprintf(
							/* translators: %s transaction ID. */
							__( 'payment completed txn ID : %s', 'subscriptions-for-woocommerce' ),
							$txn_id
						)
					);
					$order->payment_complete( $txn_id );
					$order->update_meta_data( '_wps_paypal_payment_status', 'completed' );

					if ( 'yes' === $this->get_option( 'ipn_notification' ) && ! empty( $this->get_option( 'email' ) ) ) {
						wp_mail(
							$this->get_option( 'email' ),
							__( 'IPN received from the PayPal', 'subscriptions-for-woocommerce' ),
							sprintf(
								/* translators: 1- Order ID, 2- Txn ID. */
								__( 'Order ID : %1$s , PayPal Txn ID : %2$s', 'subscriptions-for-woocommerce' ),
								$order_id,
								$txn_id
							)
						);
					}
				}
				$order->save();
			}
		}
		// phpcs:disable WordPress.Security.NonceVerification
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
				self::get_purchase_details( $order ),
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
		// $payer['payer_id'] = wps_sfw_get_meta_data( $parent_order->get_id(), '_wps_paypal_payer_id', true );

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
		$request_id = uniqid( 'wps-paypal-', true );
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

		wps_sfw_update_meta_data( $order->get_id(), 'wps_paypal_request_id', $request_id );
		$response = wp_remote_post( $url, $args );

		$json = json_decode( $response['body'] );

		if ( class_exists( 'Subscriptions_For_Woocommerce_Log' ) ) {
			Subscriptions_For_Woocommerce_Log::log( 'WPS Paypal Renewal Response: ' . wc_print_r( $json, true ) );
		}

		if ( is_wp_error( $response ) ) {
			$order_notes = __( 'renewal payment failed', 'subscriptions-for-woocommerce' );
			$order->update_status( 'failed', $order_notes );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 'COMPLETED' === $json->status ) {
			$txn_id = $json->purchase_units[0]->payments->captures[0]->id;
			$order->add_order_note(
				sprintf(
					/* translators: %s transaction ID. */
					__( 'payment completed txn ID : %s', 'subscriptions-for-woocommerce' ),
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
			do_action( 'wps_sfw_recurring_payment_success', $order->get_id() );
		} else {
			$order_notes = __( 'renewal payment failed', 'subscriptions-for-woocommerce' );
			$order->update_status( 'failed', $order_notes );
			do_action( 'wps_sfw_recurring_payment_failed', $order->get_id() );
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
			return __( 'Could not fetch payment token.', 'subscriptions-for-woocommerce' );
		}

		$json        = json_decode( $response['body'] );
		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return __( 'Could not fetch payment token.', 'subscriptions-for-woocommerce' );
		}

		$token = array();
		if ( ! empty( $json ) ) {
			$token = $json;
		}
		return $token;
	}
}
