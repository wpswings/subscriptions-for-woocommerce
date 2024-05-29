<?php
/**
 * The admin-specific paypal functionality of the plugin.
 *
 * @link       https://wpswings.com
 * @since      1.0.2
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
	exit; // Exit if accessed directly.
}

/**
 * The paypal functionality of the plugin.
 *
 * Defines the paypal functionality
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/public
 * @author     WP Swings <webmaster@wpswings.com>
 */
class Wps_Sfw_Paypal_Api_Request {

	/**
	 * The payment_args variable.
	 *
	 * @since   1.0.1
	 * @var $payment_args payment_args.
	 */
	private $payment_args = array();

	/**
	 * The invoice_prefix variable.
	 *
	 * @since   1.0.1
	 * @var $invoice_prefix invoice_prefix.
	 */
	private $invoice_prefix = 'WC-';

	/**
	 * Define the paypal functionality of the plugin.
	 *
	 * @param string $wps_sfw_api_username wps_sfw_api_username.
	 * @param string $wps_sfw_api_password wps_sfw_api_password.
	 * @param string $wps_sfw_api_signature wps_sfw_api_signature.
	 * @param string $wps_sfw_api_version wps_sfw_api_version.
	 * @param string $wps_sfw_invoice_prefix wps_sfw_invoice_prefix.
	 * @since    1.0.2
	 */
	public function __construct( $wps_sfw_api_username, $wps_sfw_api_password, $wps_sfw_api_signature, $wps_sfw_api_version, $wps_sfw_invoice_prefix ) {

		$this->payment_args['USER'] = $wps_sfw_api_username;
		$this->payment_args['PWD'] = $wps_sfw_api_password;
		$this->payment_args['SIGNATURE'] = $wps_sfw_api_signature;
		$this->payment_args['VERSION'] = $wps_sfw_api_version;

		$this->invoice_prefix = $wps_sfw_invoice_prefix;
	}

	/**
	 * Get express checkout params.
	 *
	 * @name wps_sfw_get_express_checkout_param.
	 * @param array $args args for payment.
	 * @since    1.0.2
	 */
	public function wps_sfw_get_express_checkout_param( $args ) {

		// translators: placeholder is blogname.
		$default_description = sprintf( _x( 'Orders with %s', 'data sent to paypal', 'subscriptions-for-woocommerce' ), get_bloginfo( 'name' ) );

		$defaults_agrs = array(
			'currency'            => get_woocommerce_currency(),
			'billing_type'        => 'MerchantInitiatedBillingSingleAgreement',
			'billing_description' => html_entity_decode( $default_description, ENT_NOQUOTES, 'UTF-8' ),
			'maximum_amount'      => null,
			'no_shipping'         => 1,
			'page_style'          => null,
			'brand_name'          => html_entity_decode( get_bloginfo( 'name' ), ENT_NOQUOTES, 'UTF-8' ),
			'landing_page'        => 'login',
			'payment_action'      => 'Sale',
		);

		$args = wp_parse_args( $args, $defaults_agrs );

		$this->payment_args['METHOD']  = 'SetExpressCheckout';

		$this->payment_args = array_merge(
			$this->payment_args,
			array(
				'L_BILLINGTYPE0'                 => $args['billing_type'],
				'L_BILLINGAGREEMENTDESCRIPTION0' => $this->wps_sfw_billing_agreement_description( $args['billing_description'] ),
				'L_BILLINGAGREEMENTCUSTOM0'      => $args['custom'],

				'RETURNURL'                      => $args['return_url'],
				'CANCELURL'                      => $args['cancel_url'],
				'PAGESTYLE'                      => $args['page_style'],
				'BRANDNAME'                      => $args['brand_name'],
				'LANDINGPAGE'                    => ( 'login' == $args['landing_page'] ) ? 'Login' : 'Billing',
				'NOSHIPPING'                     => $args['no_shipping'],

				'MAXAMT'                         => $args['maximum_amount'],
			)
		);

		if ( isset( $args['order'] ) ) {

			if ( 0 == $args['order']->get_total() ) {

				$this->payment_args = array_merge(
					$this->payment_args,
					array(
						'PAYMENTREQUEST_0_AMT'           => 0,
						'PAYMENTREQUEST_0_ITEMAMT'       => 0,
						'PAYMENTREQUEST_0_SHIPPINGAMT'   => 0,
						'PAYMENTREQUEST_0_TAXAMT'        => 0,
						'PAYMENTREQUEST_0_CURRENCYCODE'  => $args['currency'],
						'PAYMENTREQUEST_0_CUSTOM'        => $args['custom'],
						'PAYMENTREQUEST_0_PAYMENTACTION' => $args['payment_action'],
					)
				);

			} else {

				$this->wps_sfw_get_order_express_checkout_params( $args['order'], $args['payment_action'] );
			}
		}
		return $this->payment_args;
	}

	/**
	 * Get order express checkout params.
	 *
	 * @name wps_sfw_get_order_express_checkout_params.
	 * @param object $order order.
	 * @param string $payment_action payment_action.
	 * @param string $is_reference_payment is_reference_payment.
	 * @since    1.0.2
	 */
	public function wps_sfw_get_order_express_checkout_params( $order, $payment_action, $is_reference_payment = '' ) {

		$order_subtotal   = 0;
		$item_count       = 0;
		$order_items      = array();
		$total_amount = round( $order->get_total() );

		foreach ( $order->get_items() as $item ) {

			$product = new WC_Product( $item['product_id'] );

			$order_items[] = array(
				'NAME'    => $this->wps_get_paypal_item_name( $product->get_title() ),
				'DESC'    => $this->wps_get_paypal_item_name( $product->get_description() ),
				'AMT'     => round( $order->get_item_subtotal( $item ) ),
				'QTY'     => ( ! empty( $item['qty'] ) ) ? absint( $item['qty'] ) : 1,
				'ITEMURL' => $product->get_permalink(),
			);

			$order_subtotal += $order->get_line_total( $item );
		}

		foreach ( $order->get_fees() as $fee ) {

			$order_items[] = array(
				'NAME' => $this->wps_get_paypal_item_name( $fee['name'] ),
				'AMT'  => round( $fee['line_total'] ),
				'QTY'  => 1,
			);

			$order_subtotal += $order->get_line_total( $fee );
		}

		if ( $order->get_total_discount() > 0 ) {

			$order_items[] = array(
				'NAME' => __( 'Total Discount', 'subscriptions-for-woocommerce' ),
				'QTY'  => 1,
				'AMT'  => - round( $order->get_total_discount() ),
			);
		}

		if ( 'reference_payment' == $is_reference_payment ) {

			foreach ( $order_items as $item ) {
				$this->wps_sfw_add_line_item_params( $item, $item_count++, $is_reference_payment );
			}

			$this->payment_args = array_merge(
				$this->payment_args,
				array(
					'AMT'              => $total_amount,
					'CURRENCYCODE'     => $order->get_currency(),
					'ITEMAMT'          => round( $order_subtotal ),
					'SHIPPINGAMT'      => round( $order->get_total_shipping() ),
					'TAXAMT'           => round( $order->get_total_tax() ),
					'INVNUM'           => $this->invoice_prefix . $order->get_order_number(),
					'PAYMENTACTION'    => $payment_action,
					'PAYMENTREQUESTID' => $order->get_id(),
					'CUSTOM'           => json_encode(
						array(
							'order_id'  => $order->get_id(),
							'order_key' => $order->get_order_key(),
						)
					),
				)
			);

		} else {
			foreach ( $order_items as $item ) {
				$this->wps_sfw_add_line_item_params( $item, $item_count++ );
			}
			$this->payment_args = array_merge(
				$this->payment_args,
				array(
					'PAYMENTREQUEST_0_AMT'              => $total_amount,
					'PAYMENTREQUEST_0_CURRENCYCODE'     => $order->get_currency(),
					'PAYMENTREQUEST_0_ITEMAMT'          => round( $order_subtotal ),
					'PAYMENTREQUEST_0_SHIPPINGAMT'      => round( $order->get_total_shipping() ),
					'PAYMENTREQUEST_0_TAXAMT'           => round( $order->get_total_tax() ),
					'PAYMENTREQUEST_0_PAYMENTACTION'    => $payment_action,
					'PAYMENTREQUEST_0_PAYMENTREQUESTID' => $order->get_id(),
					'INVNUM'           => $this->invoice_prefix . $order->get_order_number(),
					'PAYMENTREQUEST_0_CUSTOM'           => json_encode(
						array(
							'order_id'  => $order->get_id(),
							'order_key' => $order->get_order_key(),
						)
					),
				)
			);
		}
	}

	/**
	 * Get billing description.
	 *
	 * @name wps_sfw_billing_agreement_description.
	 * @since    1.0.2
	 */
	public function wps_sfw_billing_agreement_description() {
		/* Translators: placeholder is blogname. */
		$description = sprintf( _x( 'Orders with %s', 'data sent to PayPal', 'subscriptions-for-woocommerce' ), get_bloginfo( 'name' ) );

		if ( strlen( $description ) > 127 ) {
			$description = substr( $description, 0, 124 ) . '...';
		}

		return html_entity_decode( $description, ENT_NOQUOTES, 'UTF-8' );
	}

	/**
	 * Get billing description.
	 *
	 * @name wps_get_paypal_item_name.
	 * @param string $wps_item_name wps_item_name.
	 * @since    1.0.2
	 */
	public function wps_get_paypal_item_name( $wps_item_name ) {

		if ( strlen( $wps_item_name ) > 127 ) {
			$wps_item_name = substr( $wps_item_name, 0, 124 ) . '...';
		}
		return html_entity_decode( $wps_item_name, ENT_NOQUOTES, 'UTF-8' );
	}

	/**
	 * Get do express checkout parameters.
	 *
	 * @name wps_sfw_do_express_checkout_params.
	 * @param string $token token.
	 * @param object $order order.
	 * @param array  $args args.
	 * @since    1.0.2
	 */
	public function wps_sfw_do_express_checkout_params( $token, $order, $args ) {

		$this->payment_args['METHOD']  = 'DoExpressCheckoutPayment';
		$this->payment_args = array_merge(
			$this->payment_args,
			array(
				'TOKEN'            => $token,
				'PAYERID'          => $args['payer_id'],
				'BUTTONSOURCE'     => 'WPS_Cart',
			)
		);
		$this->wps_sfw_get_order_express_checkout_params( $order, $args['payment_action'] );
		return $this->payment_args;
	}

	/**
	 * Get get do express checkout parameters.
	 *
	 * @name wps_sfw_get_express_checkout_params.
	 * @param string $token token.
	 * @since    1.0.2
	 */
	public function wps_sfw_get_express_checkout_params( $token ) {
		$this->payment_args['METHOD']  = 'GetExpressCheckoutDetails';
		$this->payment_args['TOKEN']  = $token;

		return $this->payment_args;
	}

	/**
	 * Create billing parameters.
	 *
	 * @name wps_sfw_create_billing_agreement_params.
	 * @param string $token token.
	 * @since    1.0.2
	 */
	public function wps_sfw_create_billing_agreement_params( $token ) {

		$this->payment_args['METHOD']  = 'CreateBillingAgreement';
		$this->payment_args['TOKEN']  = $token;

		return $this->payment_args;
	}

	/**
	 * Create billing parameters.
	 *
	 * @name wps_sfw_do_reference_transaction_params.
	 * @param string $reference_id reference_id.
	 * @param string $order order.
	 * @since    1.0.2
	 */
	public function wps_sfw_do_reference_transaction_params( $reference_id, $order ) {

		$wps_params = array(
			'payment_action'       => 'Sale',
			'return_fraud_filters' => 1,
			'notify_url'           => WC()->api_request_url( 'WC_Gateway_Paypal' ),
		);

		$this->payment_args['METHOD']  = 'DoReferenceTransaction';

		$this->payment_args = array_merge(
			$this->payment_args,
			array(
				'REFERENCEID'      => $reference_id,
				'BUTTONSOURCE'     => 'WPS_Cart',
				'RETURNFMFDETAILS' => $wps_params['return_fraud_filters'],
				'NOTIFYURL'        => $wps_params['notify_url'],
			)
		);

		$this->wps_sfw_get_order_express_checkout_params( $order, $wps_params['payment_action'], 'reference_payment' );
		return $this->payment_args;
	}

	/**
	 * Add line item parameters.
	 *
	 * @name wps_sfw_add_line_item_params.
	 * @param array  $wps_args wps_args.
	 * @param int    $item_count item_count.
	 * @param string $reference_payment reference_payment.
	 * @since    1.0.2
	 */
	public function wps_sfw_add_line_item_params( $wps_args, $item_count, $reference_payment = '' ) {

		foreach ( $wps_args as $key => $value ) {
			if ( 'reference_payment' == $reference_payment ) {

				$this->payment_args[ "L_{$key}{$item_count}" ] = $value;
			} else {
				$this->payment_args[ "L_PAYMENTREQUEST_0_{$key}{$item_count}" ] = $value;
			}
		}
	}
}
