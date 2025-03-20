<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://wpswings.com
 * @since      1.6.2
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
use Automattic\WooCommerce\Utilities\OrderUtil;
if ( ! class_exists( 'Wps_Subscriptions_Payment_Stripe_Sepa' ) ) {
	/**
	 * Extending the existing stripe sepa class.
	 */
	class Wps_Subscriptions_Payment_Stripe_Sepa extends WC_Gateway_Stripe_Sepa {
		/**
		 * Instance of Wps_Subscriptions_Payment_Stripe_Sepa
		 *
		 * @var null
		 */
		protected static $instance = null;

		/**
		 * Stripe gateway id
		 *
		 * @var   string ID of specific gateway
		 */
		public static $gateway_id = 'stripe_sepa';

		/**
		 * Return the instance of Gateway
		 *
		 * @return Wps_Subscriptions_Payment_Stripe_Sepa
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct();

			$this->supports = array(
				'products',
				'refunds',
				'tokenization',
			);
		}

		/**
		 * Process the payment
		 *
		 * @param int  $order_id Reference.
		 * @param bool $retry Should we retry on fail.
		 * @param bool $force_save_source Force save the payment source.
		 * @param bool $previous_error .
		 * @param bool $use_order_source .
		 *
		 * @throws Exception If payment will not be accepted.
		 *
		 * @return array|void
		 */
		public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false, $use_order_source = false ) {
			$order_has_sub = function_exists( 'wps_sfw_order_has_subscription' ) ? wps_sfw_order_has_subscription( $order_id ) : false;

			if ( wps_sfw_is_cart_has_subscription_product() || ! empty( $order_has_sub ) ) {
				return parent::process_payment( $order_id, $retry, true, $previous_error );
			}

			return parent::process_payment( $order_id, $retry, $force_save_source, $previous_error );
		}


		 /**
		  * Process subscription payment.
		  *
		  * @name wps_sfw_process_subscription_payment.
		  * @param object $renewal_order renewal order.
		  * @param int    $subscription_id subscription_id.
		  * @param string $payment_method payment_method.
		  *
		  * @return array|bool|WP_Error
		  * @throws WC_Stripe_Exception Trigger an error.
		  */
		public function wps_sfw_process_stripe_sepa_renewal_payment( $renewal_order, $subscription_id, $payment_method ) {

			if ( $renewal_order && is_object( $renewal_order ) && ( 'stripe_sepa' === $payment_method || 'stripe_sepa_debit' === $payment_method ) ) {
				$previous_error        = false;
				$order_id              = $renewal_order->get_id();
				$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );

				if ( 'yes' === $wps_sfw_renewal_order ) {
					$renewal_order->update_status( 'pending' );
					$parent_id    = wps_sfw_get_meta_data( $subscription_id, 'wps_parent_order', true );
					$parent_order = wc_get_order( $parent_id );
					$amount       = $renewal_order->get_total();
					if ( $amount <= 0 ) {
						$renewal_order->payment_complete();
						return true;
					}

					try {

						if ( $amount * 100 < WC_Stripe_Helper::get_minimum_amount() ) {
							/* translators: minimum amount */
							$message = sprintf( __( 'Sorry, the minimum allowed order total is %1$s to use this payment method.', 'subscriptions-for-woocommerce' ), wc_price( WC_Stripe_Helper::get_minimum_amount() / 100 ) );

							return new WP_Error( 'stripe_error', $message );
						}

						$order_id = $renewal_order->get_id();

						// Get source from order.
						$prepared_source = $this->prepare_order_source( $parent_order );

						if ( ! $prepared_source ) {
							throw new WC_Stripe_Exception( WC_Stripe_Helper::get_localized_messages()['missing'] );
						}

						$source_object = $prepared_source->source_object;

						if ( ! $prepared_source->customer ) {
							throw new WC_Stripe_Exception(
								'Failed to process renewal for order ' . $renewal_order->get_id() . '. Stripe customer id is missing in the order',
								__( 'Customer not found', 'subscriptions-for-woocommerce' )
							);
						}

						WC_Stripe_Logger::log( "Info: Begin processing subscription payment for order {$order_id} for the amount of {$amount}" );

						/*
						 * If we're doing a retry and source is chargeable, we need to pass
						 * a different idempotency key and retry for success.
						 */
						if ( is_object( $source_object ) && empty( $source_object->error ) && $this->need_update_idempotency_key( $source_object, $previous_error ) ) {
							add_filter( 'wc_stripe_idempotency_key', array( $this, 'change_idempotency_key' ), 10, 2 );
						}

						if ( ( $this->is_no_such_source_error( $previous_error ) || $this->is_no_linked_source_error( $previous_error ) ) && apply_filters( 'wc_stripe_use_default_customer_source', true ) ) {
							// Passing empty source will charge customer default.
							$prepared_source->source = '';
						}

						if ( $this->lock_order_payment( $renewal_order ) ) {
							return false;
						}

						$this->lock_order_payment( $renewal_order );
						$response                   = $this->create_and_confirm_intent_for_off_session( $renewal_order, $prepared_source, $amount );
						$is_authentication_required = $this->is_authentication_required_for_payment( $response );

						if ( ! empty( $response->error ) && ! $is_authentication_required ) {

							$localized_message = __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'subscriptions-for-woocommerce' );
							$renewal_order->add_order_note( $localized_message );

							throw new WC_Stripe_Exception( wc_print_r( $response, true ), $localized_message );
						}

						if ( $is_authentication_required ) {
							do_action( 'wc_gateway_stripe_process_payment_authentication_required', $renewal_order, $response );

							$error_message = __( 'This transaction requires authentication.', 'subscriptions-for-woocommerce' );
							$renewal_order->add_order_note( $error_message );

							$charge = end( $response->error->payment_intent->charges->data );
							$id     = $charge->id;
							$renewal_order->set_transaction_id( $id );
							/* translators: %s is the charge Id */
							$renewal_order->update_status( 'failed', sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'subscriptions-for-woocommerce' ), $id ) );
							$renewal_order->save();
						} else {
							do_action( 'wc_gateway_stripe_process_payment', $response, $renewal_order );

							// Use the last charge within the intent or the full response body in case of SEPA.
							$latest_charge = $this->get_latest_charge_from_intent( $response );
							$this->process_response( ( ! empty( $latest_charge ) ) ? $latest_charge : $response, $renewal_order );
						}
					} catch ( WC_Stripe_Exception $e ) {
						WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );
						do_action( 'wc_gateway_stripe_process_payment_error', $e, $renewal_order );
					}
				}
			}
		}

		/**
		 * Locks an order for payment intent processing for 5 days.
		 *
		 * @param WC_Order $order  The order that is being paid.
		 * @param stdClass $intent The intent that is being processed.
		 * @return bool            A flag that indicates whether the order is already locked.
		 */
		public function lock_order_payment( $order, $intent = null ) {
			$order_id       = $order->get_id();
			$transient_name = 'wc_stripe_processing_intent_' . $order_id;
			$processing     = get_transient( $transient_name );

			// Block the process if the same intent is already being handled.
			if ( '-1' === $processing || ( isset( $intent->id ) && $processing === $intent->id ) ) {
				return true;
			}

			// Save the new intent as a transient, eventually overwriting another one.
			set_transient( $transient_name, empty( $intent ) ? '-1' : $intent->id, 5 * DAY_IN_SECONDS );

			return false;
		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name wps_sfw_cancel_stripe_subscription
		 * @param int    $wps_subscription_id wps_subscription_id.
		 * @param string $status status.
		 */
		public function wps_sfw_cancel_stripe_subscription( $wps_subscription_id, $status ) {

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$subscription = new WPS_Subscription( $wps_subscription_id );
				$wps_payment_method = $subscription->get_payment_method();
			} else {
				$wps_payment_method = get_post_meta( $wps_subscription_id, '_payment_method', true );
			}
			if ( 'stripe_sepa' == $wps_payment_method && 'Cancel' == $status ) {
				wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
				wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
			}
		}
	}
}
