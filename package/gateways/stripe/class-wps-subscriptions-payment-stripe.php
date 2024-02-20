<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://wpswing.com
 * @since      1.6.0
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
if ( ! class_exists( 'Wps_Subscriptions_Payment_Stripe' ) ) {
	/**
	 * Extending the existing stripe class.
	 */
    class Wps_Subscriptions_Payment_Stripe extends WC_Gateway_Stripe {

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
                'tokenization'
            );

			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'wps_sfw_add_stripe_order_statuses_for_payment_complete' ), 10, 2 );
			add_action( 'wps_sfw_subscription_cancel', array( $this, 'wps_sfw_cancel_stripe_subscription' ), 10, 2 );

            add_action( 'wps_sfw_other_payment_gateway_renewal', array( $this, 'wps_sfw_process_stripe_renewal_payment' ), 10, 3 );
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
            }
        }

        /**
		 * This function is add subscription order status.
		 *
		 * @name wps_sfw_add_stripe_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 */
		public function wps_sfw_add_stripe_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {
				$order_id = $order->get_id();

				$payment_method = $order->get_payment_method();

				$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
				if ( 'stripe' ==  $payment_method && 'yes' == $wps_sfw_renewal_order ) {
					$order_status[] = 'wps_renewal';

				}
			}
			return apply_filters( 'wps_sfw_add_subscription_order_statuses_for_payment_complete', $order_status, $order );
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

            if ( $renewal_order && is_object( $renewal_order ) && 'stripe' === $payment_method  ) {
				$order_id              = $renewal_order->get_id();
				$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );

                
				if ( 'yes' === $wps_sfw_renewal_order ) {
                    $renewal_order->update_status( 'pending' );
                    $parent_id    = wps_sfw_get_meta_data( $subscription_id, 'wps_parent_order', true );
                    $parent_order = wc_get_order($parent_id);
                    $amount       = $renewal_order->get_total();
                    if ( $amount <= 0 ) {
                        $renewal_order->payment_complete();
                        return true;
                    }
                    try {
    
                        if ( $amount * 100 < WC_Stripe_Helper::get_minimum_amount() ) {
                            /* translators: minimum amount */
                            $message = sprintf( __( 'Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-stripe' ), wc_price( WC_Stripe_Helper::get_minimum_amount() / 100 ) );
        
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
                                __( 'Customer not found', 'woocommerce-gateway-stripe' )
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
        
                        $response                   = $this->create_and_confirm_intent_for_off_session( $renewal_order, $prepared_source, $amount );
                        $is_authentication_required = $this->is_authentication_required_for_payment( $response );
        
                        if ( ! empty( $response->error ) && ! $is_authentication_required ) {
                            $localized_message = __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'woocommerce-gateway-stripe' );
                            $renewal_order->add_order_note( $localized_message );
                            throw new WC_Stripe_Exception( print_r( $response, true ), $localized_message );
                        }
        
                        if ( $is_authentication_required ) {
                            do_action( 'wc_gateway_stripe_process_payment_authentication_required', $renewal_order, $response );
        
                            $error_message = __( 'This transaction requires authentication.', 'woocommerce-gateway-stripe' );
                            $renewal_order->add_order_note( $error_message );
        
                            $charge = end( $response->error->payment_intent->charges->data );
                            $id     = $charge->id;
                            $renewal_order->set_transaction_id( $id );
                            /* translators: %s is the charge Id */
                            $renewal_order->update_status( 'failed', sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'woocommerce-gateway-stripe' ), $id ) );
                            $renewal_order->save();
                        } else {
                            do_action( 'wc_gateway_stripe_process_payment', $response, $renewal_order );
        
                            // Use the last charge within the intent or the full response body in case of SEPA.
                            $this->process_response( isset( $response->charges ) ? end( $response->charges->data ) : $response, $renewal_order );
                        }
                    } catch ( WC_Stripe_Exception $e ) {
                        WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );
                        do_action( 'wc_gateway_stripe_process_payment_error', $e, $renewal_order );
                    }
                }
            }
        }


       /**
         * Get payment source from an order.
         *
         * Not using 2.6 tokens for this part since we need a customer AND a card
         * token, and not just one.
         *
         * @param WC_Order $order Order.
         *
         * @return  boolean|object
         */
        public function prepare_order_source( $order = null ) {
            $stripe_customer = new WC_Stripe_Customer();
            $stripe_source   = false;
            $token_id        = false;
            $source_object   = false;

            if ( $order ) {

                $stripe_customer_id = $order->get_meta( '_stripe_customer_id' );

                if ( $stripe_customer_id ) {
                    $stripe_customer->set_id( $stripe_customer_id );
                }
                $source_id = $order->get_meta( '_stripe_source_id' );
                if ( $source_id ) {
                    $stripe_source = $source_id;
                    $source_object = WC_Stripe_API::retrieve( 'sources/' . $source_id );

                    if (
                        (
                            empty( $source_object ) ||
                            isset( $source_object->error->code ) && 'resource_missing' === $source_object->error->code ||
                            isset( $source_object->status ) && 'consumed' === $source_object->status
                        ) )
                    {
                        /**
                         * If the source status is "Consumed" this means that the customer has removed it from its account.
                         * So we search for the default source ID.
                         * If this ID is empty, this means that the customer has no credit card saved on the account so the payment will fail.
                         */

                        // Retrieve the available PaymentMethods from the customer.
                        $customer       = WC_Stripe_API::retrieve( "payment_methods?customer=$stripe_customer_id" );
                        $default_source = '';
                        if ( ! empty( $customer->data ) && is_array( $customer->data ) ) {
                            // Iterate over the PaymentMethods and take the first one.
                            foreach ( $customer->data as $payment_method ) {
                                if ( ! empty( $payment_method->id ) ) {
                                    $default_source = $payment_method->id;
                                    break;
                                }
                            }
                        }

                        if ( $default_source ) {
                            $stripe_source = $default_source;
                            $source_object = WC_Stripe_API::retrieve( 'sources/' . $default_source );
                        } else {
                            return false;
                        }
                    }
                } elseif ( apply_filters( 'wc_stripe_use_default_customer_source', true ) ) {
                    /*
                    * We can attempt to charge the customer's default source
                    * by sending empty source id.
                    */
                    $stripe_source = '';
                }

                return (object) array(
                    'token_id'      => $token_id,
                    'customer'      => $stripe_customer ? $stripe_customer->get_id() : false,
                    'source'        => $stripe_source,
                    'source_object' => $source_object,
                );
            }
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
            if ( 'stripe' ==  $wps_payment_method && 'Cancel' == $status  ) {
                wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
                wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
            }
        }
    }
}
