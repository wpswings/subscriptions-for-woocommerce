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
if ( ! class_exists( 'Mwb_Subscriptions_Payment_Payfast_Main' ) ) {

	/**
	 * Define class and module for woo stripe.
	 */
	class Mwb_Subscriptions_Payment_Payfast_Main {
		/**
		 * Constructor
		 */
		public function __construct() {

			if ( $this->mwb_sfw_check_woo_redsys_enable() && mwb_sfw_check_plugin_enable() ) {

				add_action( 'mwb_sfw_subscription_cancel', array( $this, 'mwb_wsp_cancel_redsys_subscription' ), 10, 2 );
				add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'mwb_wsp_payfast_payment_order_statuses_for_payment_complete' ), 10, 2 );

				add_filter( 'mwb_sfw_supported_payment_gateway_for_woocommerce', array( $this, 'mwb_wsp_payfast_payment_gateway_for_woocommerce' ), 10, 2 );
				add_action( 'mwb_sfw_other_payment_gateway_renewal', array( $this, 'mwb_sfw_woo_payfast_process_subscription_payment' ), 10, 3 );
                
                add_action( 'woocommerce_api_wc_gateway_payfast', array( $this, 'mwb_sfw_save_payfast_token' ) );

			}

		}
		/**
		 * This function is add subscription order status.
		 *
		 * @name mwb_wsp_payfast_payment_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 * @since 2.0.0
		 * @return mixed
		 */
		public function mwb_wsp_payfast_payment_order_statuses_for_payment_complete( $order_status, $order ) {
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
		 * @name mwb_wsp_payfast_payment_gateway_for_woocommerce.
		 * @param array  $supported_payment_method supported_payment_method.
		 * @param string $payment_method payment_method.
		 * @since 2.0.0
		 * @return array
		 */
		public function mwb_wsp_payfast_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {
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
			if ( in_array( 'woocommerce-payfast-gateway/gateway-payfast.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
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
		public function mwb_sfw_woo_payfast_process_subscription_payment( $order, $subscription_id, $payment_method ) {
            $payfast_gateway_object = new WC_Gateway_PayFast();
            $parent_id = get_post_meta( $subscription_id, 'mwb_parent_id', true );
            $token = get_post_meta( $parent_id, 'mwb_sfw_user_token', true );
		    $response = $payfast_gateway_object->submit_ad_hoc_payment( $token, 100, 'product test', 'this is description' );
            return $response;
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
        /**

        * Check PayFast ITN response.

        *

        * @since 1.0.0

        */

        public function checking_payfast_token() {

            $data  = $_POST;

            $token = sanitize_text_field( $data['signature'] );

            $parent_id = sanitize_text_field( $data['custom_str3'] );

            update_post_meta( $parent_id, 'mwb_sfw_user_token', $token );

            update_post_meta( $parent_id, 'mwb_sfw_user_token_data', $data );

            // return $this->api_request( 'cancel', $token, array(), 'PUT' );

        }
	}
}
new Mwb_Subscriptions_Payment_Payfast_Main();
