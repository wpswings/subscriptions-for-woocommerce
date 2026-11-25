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
if ( ! class_exists( 'Wps_Subscriptions_Payment_Stripe' ) ) {
	/**
	 * Extending the existing stripe class.
	 */
	class Wps_Subscriptions_Payment_Stripe extends \WC_Gateway_Stripe {

		/**
		 * Instance of Wps_Subscriptions_Payment_Stripe
		 *
		 * @var null
		 */
		protected static $instance = null;

		/**
		 * Stripe gateway id
		 *
		 * @var   string ID of specific gateway
		 */
		public static $gateway_id = 'stripe';

		/**
		 * Array mapping payment method string IDs to classes
		 *
		 * @var WC_Stripe_UPE_Payment_Method[]
		 */
		public $payment_methods = array();

		/**
		 * Return the instance of Gateway
		 *
		 * @return Wps_Subscriptions_Payment_Stripe
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
		 * @param mix  $previous_error Any error message from previous request.
		 * @param bool $use_order_source Whether to use the source, which should already be attached to the order.
		 *
		 * @throws Exception If payment will not be accepted.
		 * @return array|void
		 */
		public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false, $use_order_source = false ) {
			$order_has_sub = function_exists( 'wps_sfw_order_has_subscription' ) ? wps_sfw_order_has_subscription( $order_id ) : false;

			if ( wps_sfw_is_cart_has_subscription_product() || ! empty( $order_has_sub ) ) {
				return parent::process_payment( $order_id, $retry, true, $previous_error );
			} else {
				return parent::process_payment( $order_id, $retry, $force_save_source, $previous_error );
			}
		}

		/**
		 * Returns the list of enabled payment method types that will function with the current checkout.
		 *
		 * @param int|null $order_id as order id.
		 * @return string[]
		 */
		public function get_upe_enabled_at_checkout_payment_method_ids( $order_id = null ) {
			$is_automatic_capture_enabled = $this->is_automatic_capture_enabled();
			$available_method_ids         = array();

			$arr = array( WC_Stripe_UPE_Payment_Method_CC::class );
			foreach ( $arr as $payment_method_class ) {

				$payment_method                                     = new $payment_method_class();
				$this->payment_methods[ $payment_method->get_id() ] = $payment_method;
			}
			$get_upe_enabled_payment_method_ids = $this->get_option( 'upe_checkout_experience_accepted_payments', array( 'card' ) );
			foreach ( $get_upe_enabled_payment_method_ids as $payment_method_id ) {
				if ( ! isset( $this->payment_methods[ $payment_method_id ] ) ) {
					continue;
				}

				$method = $this->payment_methods[ $payment_method_id ];
				if ( $method->is_enabled_at_checkout( $order_id ) === false ) {
					continue;
				}

				if ( ! $is_automatic_capture_enabled && $method->requires_automatic_capture() ) {
					continue;
				}

				$available_method_ids[] = $payment_method_id;
			}

			return $available_method_ids;
		}


		/**
		 * Process subscription payment.
		 *
		 * @name wps_sfw_process_stripe_renewal_payment.
		 * @param object $renewal_order renewal order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 *
		 * @return array|bool|WP_Error
		 * @throws WC_Stripe_Exception Trigger an error.
		 */
		public function wps_sfw_process_stripe_renewal_payment( $renewal_order, $subscription_id, $payment_method ) {

			if ( $renewal_order && is_object( $renewal_order ) && 'stripe' === $payment_method ) {
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

						// Pre-flight check: if the saved PM is detached, replace it before we ever attempt a charge
						// so the parent order is updated immediately and subsequent renewals also start from the right card.
						if ( $prepared_source && class_exists( 'WC_Stripe_API' ) ) {
							$prepared_source = $this->wps_sfw_maybe_replace_detached_source( $renewal_order, $parent_order, $prepared_source );
						}

						// Per-order diagnostics: capture exactly why a renewal may stop before POST payment_intents.
						$wps_sfw_src_obj    = ( $prepared_source && isset( $prepared_source->source_object ) ) ? $prepared_source->source_object : null;
						$wps_sfw_src_id     = ( $prepared_source && isset( $prepared_source->source ) ) ? $prepared_source->source : '';
						$wps_sfw_src_type   = is_object( $wps_sfw_src_obj ) ? ( isset( $wps_sfw_src_obj->type ) ? $wps_sfw_src_obj->type : ( isset( $wps_sfw_src_obj->object ) ? $wps_sfw_src_obj->object : 'unknown' ) ) : 'none';
						$wps_sfw_src_status = ( is_object( $wps_sfw_src_obj ) && isset( $wps_sfw_src_obj->status ) ) ? $wps_sfw_src_obj->status : 'n/a';
						

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

						

						$response                   = $this->create_and_confirm_intent_for_off_session( $renewal_order, $prepared_source, $amount );
						$is_authentication_required = $this->is_authentication_required_for_payment( $response );

						// The saved card was detached from the Stripe customer: retrying it can never succeed, so try the
						// customer's default saved card once instead, and stop retrying when there is none.
						if ( ! empty( $response->error ) && ! $is_authentication_required && $this->wps_sfw_is_detached_payment_method_error( $response->error ) ) {
							$detached_pm_id  = $prepared_source->source;
							$fallback_source = $this->wps_sfw_get_default_stripe_source( $renewal_order, $parent_order, $prepared_source );

							if ( ! $fallback_source ) {
								$this->wps_sfw_fail_renewal_for_detached_card( $renewal_order, $detached_pm_id );
								return false;
							}

							/* translators: 1: detached Stripe payment method ID, 2: fallback Stripe payment method ID */
							$renewal_order->add_order_note( sprintf( __( 'Stripe payment method %1$s was detached from the customer. Trying the customer\'s default saved card %2$s instead.', 'subscriptions-for-woocommerce' ), $detached_pm_id, $fallback_source->source ) );

							$prepared_source            = $fallback_source;
							$response                   = $this->create_and_confirm_intent_for_off_session( $renewal_order, $prepared_source, $amount );
							$is_authentication_required = $this->is_authentication_required_for_payment( $response );

							if ( ! empty( $response->error ) && ! $is_authentication_required ) {
								$this->wps_sfw_fail_renewal_for_detached_card( $renewal_order, $detached_pm_id, isset( $response->error->message ) ? $response->error->message : '' );
								return false;
							}

							// Charge future renewals to the working card instead of the detached one.
							if ( class_exists( 'WC_Stripe_Order_Helper' ) && $parent_order instanceof WC_Order ) {
								$order_helper = WC_Stripe_Order_Helper::get_instance();
								$order_helper->update_stripe_source_id( $parent_order, $prepared_source->source );
								$parent_order->save();
								/* translators: 1: Stripe payment method ID, 2: renewal order number */
								$parent_order->add_order_note( sprintf( __( 'Future subscription renewals will be charged to Stripe payment method %1$s, the customer\'s default saved card used for renewal order #%2$s.', 'subscriptions-for-woocommerce' ), $prepared_source->source, $renewal_order->get_order_number() ) );
							}
						}

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
						
						do_action( 'wc_gateway_stripe_process_payment_error', $e, $renewal_order );

						$wps_wsp_stripe_message = method_exists( $e, 'getLocalizedMessage' ) ? $e->getLocalizedMessage() : $e->getMessage();
						do_action( 'wps_sfw_recurring_payment_failed', $renewal_order->get_id(), new WP_Error( $e->getCode(), $wps_wsp_stripe_message ) );
					}
				}
			}
		}

		/**
		 * Pre-flight check: if the payment method on the parent order has been detached from its
		 * Stripe customer, replace it with the customer's current default card and update the
		 * parent order now — before we ever attempt to charge.
		 *
		 * Without this check the renewal fires one doomed charge against the detached card, then
		 * recovers reactively. With this check the charge is skipped entirely and the parent order
		 * is kept up-to-date so the next renewal also starts from the right card.
		 *
		 * When no fallback card exists, the original prepared source is returned unchanged so the
		 * charge attempt surfaces the real Stripe error and the existing error-handling path takes
		 * over. In that case the parent order's source is cleared so the same doomed charge is not
		 * retried on subsequent renewal cycles.
		 *
		 * @param WC_Order $renewal_order   The renewal order.
		 * @param WC_Order $parent_order    The subscription's parent order.
		 * @param object   $prepared_source The source built from the parent order.
		 * @return object The (possibly updated) prepared source.
		 */
		private function wps_sfw_maybe_replace_detached_source( $renewal_order, $parent_order, $prepared_source ) {
			if ( empty( $prepared_source->source ) || 0 !== strpos( (string) $prepared_source->source, 'pm_' ) ) {
				return $prepared_source;
			}

			$pm_object = WC_Stripe_API::retrieve( 'payment_methods/' . $prepared_source->source );
			if ( is_wp_error( $pm_object ) || ! empty( $pm_object->error ) ) {
				return $prepared_source;
			}

			// A non-empty `customer` field means the PM is still attached — nothing to do.
			if ( ! empty( $pm_object->customer ) ) {
				return $prepared_source;
			}

			$detached_pm_id = $prepared_source->source;

			// Try to fall back to the customer's current default saved card.
			$fallback_source = $this->wps_sfw_get_default_stripe_source( $renewal_order, $parent_order, $prepared_source );

			if ( $fallback_source ) {
				/* translators: 1: detached Stripe payment method ID, 2: fallback Stripe payment method ID */
				$renewal_order->add_order_note(
					sprintf(
						__( 'Stripe payment method %1$s is no longer attached to the customer. Using the customer\'s default saved card %2$s for this renewal.', 'subscriptions-for-woocommerce' ),
						$detached_pm_id,
						$fallback_source->source
					)
				);

				// Update the parent order now so that the next renewal also uses the working card.
				if ( $parent_order instanceof WC_Order && class_exists( 'WC_Stripe_Order_Helper' ) ) {
					$order_helper = WC_Stripe_Order_Helper::get_instance();
					$order_helper->update_stripe_source_id( $parent_order, $fallback_source->source );
					if ( ! empty( $fallback_source->customer ) ) {
						$order_helper->update_stripe_customer_id( $parent_order, $fallback_source->customer );
					}
					$parent_order->save();
					/* translators: 1: Stripe payment method ID, 2: renewal order number */
					$parent_order->add_order_note(
						sprintf(
							__( 'Future subscription renewals will be charged to Stripe payment method %1$s, the customer\'s default saved card used for renewal order #%2$s.', 'subscriptions-for-woocommerce' ),
							$fallback_source->source,
							$renewal_order->get_order_number()
						)
					);
				}

				return $fallback_source;
			}

			// No fallback card: clear the detached card from the parent order so the same doomed
			// charge is not retried on the next renewal cycle. The charge will still be attempted
			// below (and fail), triggering the existing error path and customer notification.
			if ( $parent_order instanceof WC_Order && class_exists( 'WC_Stripe_Order_Helper' ) ) {
				$order_helper = WC_Stripe_Order_Helper::get_instance();
				$order_helper->update_stripe_source_id( $parent_order, '' );
				$parent_order->save();
				/* translators: %s: detached Stripe payment method ID */
				$parent_order->add_order_note(
					sprintf(
						__( 'Stripe payment method %s was detached from the customer and no default saved card is available. The card has been cleared from the order to prevent repeated failed renewal attempts. Update the payment method to resume renewals.', 'subscriptions-for-woocommerce' ),
						$detached_pm_id
					)
				);
			}

			return $prepared_source;
		}

		/**
		 * Whether a Stripe error says the payment method is no longer attached to the customer.
		 *
		 * @param object $error The `error` object from a Stripe response.
		 * @return bool
		 */
		private function wps_sfw_is_detached_payment_method_error( $error ) {
			return is_object( $error ) && isset( $error->message ) && preg_match( '/detached from a Customer/i', $error->message );
		}

		/**
		 * Build a source for the customer's default saved Stripe card, to charge when the subscription's card was detached.
		 *
		 * The WooCommerce default saved token is used first, then the default payment method set on the Stripe customer.
		 * A card is only used when it is attached to the same Stripe customer the renewal is charged to.
		 *
		 * @param WC_Order $renewal_order   The renewal order.
		 * @param WC_Order $parent_order    The subscription's parent order.
		 * @param object   $prepared_source The source that failed.
		 * @return object|false A prepared source for the default card, or false when there is no usable card.
		 */
		private function wps_sfw_get_default_stripe_source( $renewal_order, $parent_order, $prepared_source ) {
			if ( empty( $prepared_source->customer ) ) {
				return false;
			}

			$user_id = $renewal_order->get_user_id();
			if ( ! $user_id && $parent_order instanceof WC_Order ) {
				$user_id = $parent_order->get_user_id();
			}

			$candidate_ids = array();
			if ( $user_id ) {
				$default_token = WC_Payment_Tokens::get_customer_default_token( $user_id );
				if ( $default_token && 'stripe' === $default_token->get_gateway_id() ) {
					$candidate_ids[] = $default_token->get_token();
				}
			}

			$stripe_customer = WC_Stripe_API::retrieve( 'customers/' . $prepared_source->customer );
			if ( is_object( $stripe_customer ) && empty( $stripe_customer->error ) && ! empty( $stripe_customer->invoice_settings->default_payment_method ) ) {
				$default_pm      = $stripe_customer->invoice_settings->default_payment_method;
				$candidate_ids[] = is_object( $default_pm ) ? $default_pm->id : $default_pm;
			}

			foreach ( array_unique( $candidate_ids ) as $payment_method_id ) {
				if ( empty( $payment_method_id ) || $payment_method_id === $prepared_source->source || 0 !== strpos( $payment_method_id, 'pm_' ) ) {
					continue;
				}

				$payment_method = WC_Stripe_API::retrieve( 'payment_methods/' . $payment_method_id );
				if ( ! is_object( $payment_method ) || ! empty( $payment_method->error ) || ! isset( $payment_method->customer ) || $payment_method->customer !== $prepared_source->customer ) {
					continue;
				}

				$fallback_source                = clone $prepared_source;
				$fallback_source->source        = $payment_method_id;
				$fallback_source->source_object = $payment_method;
				return $fallback_source;
			}

			return false;
		}

		/**
		 * Mark a renewal failed because its saved card was detached, with a reason the customer and merchant can read.
		 *
		 * The failure is reported with a non-retryable code so the same card is not charged again by the retry pass.
		 *
		 * @param WC_Order $renewal_order  The renewal order.
		 * @param string   $detached_pm_id The detached Stripe payment method ID.
		 * @param string   $fallback_error Stripe's error from charging the default saved card, if one was tried.
		 */
		private function wps_sfw_fail_renewal_for_detached_card( $renewal_order, $detached_pm_id, $fallback_error = '' ) {
			$reason = __( 'The card saved for this subscription is no longer available. Please add a new card or pay this renewal manually.', 'subscriptions-for-woocommerce' );

			if ( $fallback_error ) {
				/* translators: 1: detached Stripe payment method ID, 2: Stripe error message */
				$note = sprintf( __( 'Renewal payment failed: Stripe payment method %1$s was detached from the customer, and charging the customer\'s default saved card also failed: %2$s', 'subscriptions-for-woocommerce' ), $detached_pm_id, $fallback_error );
			} else {
				/* translators: %s: detached Stripe payment method ID */
				$note = sprintf( __( 'Renewal payment failed: Stripe payment method %s was detached from the customer and the customer has no other saved card. Automatic retries are stopped until the customer updates their payment method.', 'subscriptions-for-woocommerce' ), $detached_pm_id );
			}

			$renewal_order->update_status( 'failed', $note );

			do_action( 'wps_sfw_recurring_payment_failed', $renewal_order->get_id(), new WP_Error( 'payment_method_detached', $reason ) );
		}
	}
}
