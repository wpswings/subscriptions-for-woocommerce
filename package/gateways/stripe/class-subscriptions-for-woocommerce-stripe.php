<?php
/**
 * The admin-specific on-boarding functionality of the plugin.
 *
 * @link       https://wpswing.com
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
 * @author      WP Swings <webmaster@wpswings.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( class_exists( 'Subscriptions_For_Woocommerce_Stripe' ) ) {
	return;
}

/**
 * Define class and module for stripe.
 */
class Subscriptions_For_Woocommerce_Stripe {

	/**
	 * Generate the request for the payment.
	 *
	 * @name wps_sfw_process_renewal_payment.
	 * @since  1.0.0.
	 * @param  int $order_id order_id.
	 * @param  int $parent_order_id parent_order_id.
	 * @return array()
	 */
	public function wps_sfw_process_renewal_payment( $order_id, $parent_order_id ) {

		$order = wc_get_order( $order_id );
		$parent_order = wc_get_order( $parent_order_id );

		$is_successful = false;

		try {
			// Return if order total is zero.
			if ( 0 == $order->get_total() ) {
				$order->payment_complete();
				return;
			}

			$gateway = $this->wps_sfw_get_wc_gateway();

			if ( ! $gateway ) {
				$order_note = __( 'Stripe payment gateway not activated.', 'subscriptions-for-woocommerce' );
				$order->update_status( 'failed', $order_note );
				return;
			}
			$source = $gateway->prepare_order_source( $parent_order );

			$response = WC_Stripe_API::request( $this->wps_sfw_generate_payment_request( $order, $source ) );
			// show the data in log file.
			WC_Stripe_Logger::log( 'WPS response: ' . wc_print_r( $response, true ) );
			// Log here complete response.
			if ( is_wp_error( $response ) ) {
				// show the data in log file.
				WC_Stripe_Logger::log( 'WPS response error: ' . wc_print_r( $response, true ) );
				// @todo handle the error part here/failure of order.

				$error_message = sprintf( __( 'Something Went Wrong. Please see the log file for more info.', 'subscriptions-for-woocommerce' ) );

			} else {
				if ( ! empty( $response->error ) ) {
					WC_Stripe_Logger::log( 'WPS response error: ' . wc_print_r( $response, true ) );
					$is_successful = false;
					$order_note = __( 'Stripe Transaction Failed', 'subscriptions-for-woocommerce' );
					$order->update_status( 'failed', $order_note );

				} else {
					// show the data in log file.
					WC_Stripe_Logger::log( 'WPS response succes: ' . wc_print_r( $response, true ) );

					update_post_meta( $order_id, '_wps_sfw_payment_transaction_id', $response->id );
					/* translators: %s: transaction id */
					$order_note = sprintf( __( 'Stripe Renewal Transaction Successful (%s)', 'subscriptions-for-woocommerce' ), $response->id );
					$order->add_order_note( $order_note );
					$order->payment_complete( $response->id );

					$is_successful = true;
				}
			}

			// Returns boolean.
			return $is_successful;

		} catch ( Exception $e ) {
			WC_Stripe_Logger::log( 'WPS response Failed: ' );
			// @todo transaction failure to handle here.
			$order_note = __( 'Stripe Transaction Failed', 'subscriptions-for-woocommerce' );
			$order->update_status( 'failed', $order_note );
			return false;
		}
	}

	/**
	 * Generate the request for the payment.
	 *
	 * @name wps_sfw_generate_payment_request.
	 * @since  1.0.00
	 * @param  object $order order.
	 * @param  object $source source.
	 *
	 * @return array()
	 */
	public function wps_sfw_generate_payment_request( $order, $source ) {
		$order_id = $order->get_id();
		$charge_amount = $order->get_total();

		$gateway                  = $this->wps_sfw_get_wc_gateway();
		$post_data                = array();
		$post_data['currency']    = strtolower( $this->wps_sfw_get_order_currency( $order ) );
		$post_data['amount']      = WC_Stripe_Helper::get_stripe_amount( $charge_amount, $post_data['currency'] );
		/* translators: 1$: site name,2$: order number */
		$post_data['description'] = sprintf( __( '%1$s - Order %2$s - Renewal Order.', 'subscriptions-for-woocommerce' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_order_number() );
		$post_data['capture']     = 'true';
		$billing_first_name       = $order->get_billing_first_name();
		$billing_last_name        = $order->get_billing_last_name();
		$billing_email            = $order->get_billing_email( $order, 'billing_email' );

		if ( ! empty( $billing_email ) && apply_filters( 'wc_stripe_send_stripe_receipt', false ) ) {
			$post_data['receipt_email'] = $billing_email;
		}
		$metadata              = array(
			'customer_name'  => sanitize_text_field( $billing_first_name ) . ' ' . sanitize_text_field( $billing_last_name ),
			'customer_email' => sanitize_email( $billing_email ),
			'order_id'                                           => $order_id,
		);
		$post_data['expand[]'] = 'balance_transaction';
		$post_data['metadata'] = apply_filters( 'wc_stripe_payment_metadata', $metadata, $order, $source );

		if ( $source->customer ) {
			$post_data['customer']  = ! empty( $source->customer ) ? $source->customer : '';
		}

		if ( $source->source ) {
			$post_data['source']  = ! empty( $source->source ) ? $source->source : '';
		}
		return apply_filters( 'wc_stripe_generate_payment_request', $post_data, $order, $source );
	}

	/**
	 * Get payment gateway.
	 *
	 * @since  1.0.0
	 * @return WC_Payment_Gateway.
	 */
	public function wps_sfw_get_wc_gateway() {
		global $woocommerce;
		$gateways = $woocommerce->payment_gateways->payment_gateways();
		if ( isset( $gateways['stripe'] ) && ! empty( $gateways['stripe'] ) ) {
			return $gateways['stripe'];
		}
		return false;
	}

	/**
	 * Get order currency.
	 *
	 * @name wps_sfw_get_order_currency.
	 * @since  1.0.0
	 * @param  object $order order.
	 *
	 * @return mixed|string
	 */
	public function wps_sfw_get_order_currency( $order ) {

		if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			return $order ? $order->get_currency() : get_woocommerce_currency();
		} else {
			return $order ? $order->get_order_currency() : get_woocommerce_currency();

		}
	}
}
