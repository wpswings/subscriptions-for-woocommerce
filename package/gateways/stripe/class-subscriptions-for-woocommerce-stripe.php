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
		global $woocommerce;
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

			$gateways_check = $woocommerce->payment_gateways->payment_gateways();

			if ( 'stripe' == $gateways_check['stripe']->id ) {

				$amount = $order->get_total();
				$response = $this->wps_sfw_create_and_confirm_intent_for_off_session( $order, $source, $amount );

			} else {

				$response = WC_Stripe_API::request( $this->wps_sfw_generate_payment_request( $order, $source ) );

			}
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
					do_action( 'wps_sfw_recurring_payment_failed', $order_id );

				} else {
					// show the data in log file.
					WC_Stripe_Logger::log( 'WPS response succes: ' . wc_print_r( $response, true ) );

					update_post_meta( $order_id, '_wps_sfw_payment_transaction_id', $response->id );
					/* translators: %s: transaction id */
					$order_note = sprintf( __( 'Stripe Renewal Transaction Successful (%s)', 'subscriptions-for-woocommerce' ), $response->id );
					$order->add_order_note( $order_note );
					$order->payment_complete( $response->id );
					do_action( 'wps_sfw_recurring_payment_success', $order_id );

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
	 * Create and confirm a new PaymentIntent.
	 *
	 * @param WC_Order $order           The order that is being paid for.
	 * @param object   $source          The source that is used for the payment.
	 * @param float    $amount          The amount to charge. If not specified, it will be read from the order.
	 * @return object                   An intent or an error.
	 */
	public function wps_sfw_create_and_confirm_intent_for_off_session( $order, $source, $amount ) {

		$full_request = $this->wps_sfw_generate_payment_request( $order, $source );

		$payment_method_types = array( 'card' );

		$payment_method_types = array( $source->source_object->type );

		$request = array(
			'amount'               => $amount ? WC_Stripe_Helper::get_stripe_amount( $amount, $full_request['currency'] ) : $full_request['amount'],
			'currency'             => $full_request['currency'],
			'description'          => $full_request['description'],
			'metadata'             => $full_request['metadata'],
			'payment_method_types' => $payment_method_types,
			'off_session'          => 'true',
			'confirm'              => 'true',
			'confirmation_method'  => 'automatic',
		);

		if ( isset( $full_request['statement_descriptor'] ) ) {
			$request['statement_descriptor'] = $full_request['statement_descriptor'];
		}

		if ( isset( $full_request['customer'] ) ) {
			$request['customer'] = $full_request['customer'];
		}

		if ( isset( $full_request['source'] ) ) {
			$request = WC_Stripe_Helper::add_payment_method_to_request_array( $full_request['source'], $request );
		}

		/**
		 * Filter the value of the request.
		 *
		 * @since 4.5.0
		 * @param array $request
		 * @param WC_Order $order
		 * @param object $source
		 */
		$request = apply_filters( 'wc_stripe_generate_create_intent_request', $request, $order, $source );

		if ( isset( $full_request['shipping'] ) ) {
			$request['shipping'] = $full_request['shipping'];
		}

		$level3_data                = $this->wps_sfw_get_level3_data_from_order( $order );

		$intent                     = WC_Stripe_API::request_with_level3_data(
			$request,
			'payment_intents',
			$level3_data,
			$order
		);
		$is_authentication_required = $this->wps_sfw_is_authentication_required_for_payment( $intent );
		if ( ! empty( $intent->error ) && ! $is_authentication_required ) {
			return $intent;
		}

			$intent_id      = ( ! empty( $intent->error )
			? $intent->error->payment_intent->id
			: $intent->id
		);

		$payment_intent = ( ! empty( $intent->error )
			? $intent->error->payment_intent
			: $intent
		);
		$order_id       = $order->get_id();
		WC_Stripe_Logger::log( "Stripe PaymentIntent $intent_id initiated for order $order_id" );

		return $intent;

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
	 * Create the level 3 data array to send to Stripe when making a purchase.
	 *
	 * @param WC_Order $order The order that is being paid for.
	 * @return array          The level 3 data to send to Stripe.
	 */
	public function wps_sfw_get_level3_data_from_order( $order ) {
		// Get the order items. Don't need their keys, only their values.
		// Order item IDs are used as keys in the original order items array.
		$order_items = array_values( $order->get_items( array( 'line_item', 'fee' ) ) );
		$currency    = $order->get_currency();

		$stripe_line_items = array_map(
			function( $item ) use ( $currency ) {
				if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
					$product_id = $item->get_variation_id()
						? $item->get_variation_id()
						: $item->get_product_id();
					$subtotal   = $item->get_subtotal();
				} else {
					$product_id = substr( sanitize_title( $item->get_name() ), 0, 12 );
					$subtotal   = $item->get_total();
				}
				$product_description = substr( $item->get_name(), 0, 26 );
				$quantity            = $item->get_quantity();
				$unit_cost           = WC_Stripe_Helper::get_stripe_amount( ( $subtotal / $quantity ), $currency );
				$tax_amount          = WC_Stripe_Helper::get_stripe_amount( $item->get_total_tax(), $currency );
				$discount_amount     = WC_Stripe_Helper::get_stripe_amount( $subtotal - $item->get_total(), $currency );

				return (object) array(
					'product_code'        => (string) $product_id, // Up to 12 characters that uniquely identify the product.
					'product_description' => $product_description, // Up to 26 characters long describing the product.
					'unit_cost'           => $unit_cost, // Cost of the product, in cents, as a non-negative integer.
					'quantity'            => $quantity, // The number of items of this type sold, as a non-negative integer.
					'tax_amount'          => $tax_amount, // The amount of tax this item had added to it, in cents, as a non-negative integer.
					'discount_amount'     => $discount_amount, // The amount an item was discounted—if there was a sale,for example, as a non-negative integer.
				);
			},
			$order_items
		);

		$level3_data = array(
			'merchant_reference' => $order->get_id(), // An alphanumeric string of up to  characters in length. This unique value is assigned by the merchant to identify the order. Also known as an “Order ID”.
			'shipping_amount'    => WC_Stripe_Helper::get_stripe_amount( (float) $order->get_shipping_total() + (float) $order->get_shipping_tax(), $currency ), // The shipping cost, in cents, as a non-negative integer.
			'line_items'         => $stripe_line_items,
		);

		// The customer’s U.S. shipping ZIP code.
		$shipping_address_zip = $order->get_shipping_postcode();

		$level3_data['shipping_address_zip'] = $shipping_address_zip;

		// The merchant’s U.S. shipping ZIP code.
		$store_postcode = get_option( 'woocommerce_store_postcode' );

		$level3_data['shipping_from_zip'] = $store_postcode;

		return $level3_data;
	}
	/**
	 * Given a response from Stripe, check if it's a card error where authentication is required
	 * to complete the payment.
	 *
	 * @param object $response The response from Stripe.
	 * @return boolean Whether or not it's a 'authentication_required' error
	 */
	public function wps_sfw_is_authentication_required_for_payment( $response ) {
		return ( ! empty( $response->error ) && 'authentication_required' === $response->error->code )
			|| ( ! empty( $response->last_payment_error ) && 'authentication_required' === $response->last_payment_error->code );
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
