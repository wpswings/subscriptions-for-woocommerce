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
	 * Define the main attributes and methods to be set in the parent class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->id                 = 'wps_paypal';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Proceed to PayPal', 'wps-paypal-integration-for-woocommerce' );
		$this->method_title       = __( 'WPS Paypal Payment', 'wps-paypal-integration-for-woocommerce' );
		$this->method_description = __( 'Accept Payments from PayPal', 'wps-paypal-integration-for-woocommerce' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title         = $this->get_option( 'title' );
		$this->description   = $this->get_option( 'description' );
		$this->testmode      = $this->get_option( 'testmode' );
		$this->client_id     = $this->get_option( 'client_id' );
		$this->client_secret = $this->get_option( 'client_secret' );

		if ( 'no' == $this->testmode ) {
			$this->testmode = false;
		}
		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'capture_payment' ), 5 );

	}

	/**
	 * If this payment method needs further setup.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function needs_setup() {
		if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
			return true;
		}
		return false;
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
					<strong><?php esc_html_e( 'Gateway disabled', 'wps-paypal-integration-for-woocommerce' ); ?></strong>: <?php esc_html_e( 'PayPal Standard does not support your store currency.', 'wps-paypal-integration-for-woocommerce' ); ?>
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
			return new WP_Error( 'error', __( 'Unable to proceed the refund process', 'wps-paypal-integration-for-woocommerce' ) );
		}

		if ( empty( $amount ) || $amount <= 0 ) {
			return new WP_Error( 'error', __( 'Please enter the amount to refund', 'wps-paypal-integration-for-woocommerce' ) );
		}

		$this->init_api();
		$response = WPS_Paypal_Requests::refund_order( $order, $amount, $reason );
		if ( 'success' === $response['result'] ) {
			if ( 'COMPLETED' === $response['response']->status ) {
				update_post_meta( $order_id, '_wps_paypal_payment_status', 'refunded' );
				$order->add_order_note(
					sprintf(
						/* translators: %s paypal refund ID. */
						__( 'Refunded : %1$s from PayPal- Refund ID : %2$s', 'wps-paypal-integration-for-woocommerce' ),
						$amount,
						$response['response']->id
					)
				);
				return true;
			}
		}

		return new WP_Error( 'error', __( 'Unable to refund the amount from PayPal.', 'wps-paypal-integration-for-woocommerce' ) );
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
		$this->form_fields = include SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/wps-paypal/wps-paypal-gateway-fields.php';
	}

	/**
	 * Initialise api credentials.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function init_api() {
		require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/wps-paypal/class-wps-paypal-requests.php';
		WPS_Paypal_Requests::$client_id     = $this->client_id;
		WPS_Paypal_Requests::$client_secret = $this->client_secret;
		WPS_Paypal_Requests::$testmode      = $this->testmode;
	}

	/**
	 * Capture payment from paypal.
	 *
	 * @param integer $order_id current order id.
	 * @return void
	 * @since 1.0.0
	 */
	public function capture_payment( $order_id ) {

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
				$this->init_api();
				$response = WPS_Paypal_Requests::do_capture( $order, $paypal_order_token );

				$order->add_order_note(
					sprintf(
						/* translators: paypal order id. */
						esc_html__( 'order captured from PayPal for PayPal order ID : %s', 'wps-paypal-integration-for-woocommerce' ),
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
							__( 'payment completed txn ID : %s', 'wps-paypal-integration-for-woocommerce' ),
							$txn_id
						)
					);
					$order->payment_complete( $txn_id );
					$order->update_meta_data( '_wps_paypal_payment_status', 'completed' );

					if ( 'yes' === $this->get_option( 'ipn_notification' ) && ! empty( $this->get_option( 'email' ) ) ) {
						wp_mail(
							$this->get_option( 'email' ),
							__( 'IPN received from the PayPal', 'wps-paypal-integration-for-woocommerce' ),
							sprintf(
								/* translators: 1- Order ID, 2- Txn ID. */
								__( 'Order ID : %1$s , PayPal Txn ID : %2$s', 'wps-paypal-integration-for-woocommerce' ),
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
	 * Process payments.
	 *
	 * @param int $order_id current order id.
	 * @since 1.0.0
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		$this->init_api();
		$wps_request = new WPS_Paypal_Requests( $this );
		$response    = $wps_request->paypal_create_order( $order );

		if ( 'success' !== $response['result'] ) {
			wc_add_notice( __( 'Payment error : Please choose another payment method.', 'wps-paypal-integration-for-woocommerce' ), 'error' );
			return;
		}
		return $response;
	}

	/**
	 * Get the transaction URL.
	 *
	 * @param  WC_Order $order Order object.
	 * @return string
	 */
	public function get_transaction_url( $order ) {
		if ( $this->testmode ) {
			$this->view_transaction_url = 'https://www.sandbox.paypal.com/activity/payment/%s';
		} else {
			$this->view_transaction_url = 'https://www.paypal.com/activity/payment/%s';
		}
		return parent::get_transaction_url( $order );
	}

}
