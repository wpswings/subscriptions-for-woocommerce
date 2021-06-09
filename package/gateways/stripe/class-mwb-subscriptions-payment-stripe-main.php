<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://makewebbetter.com
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
 * @author      makewebbetter <webmaster@makewebbetter.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Mwb_Subscriptions_Payment_Stripe_Main' ) ) {

	/**
	 * Define class and module for stripe.
	 */
	class Mwb_Subscriptions_Payment_Stripe_Main {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'mwb_sfw_subscription_cancel', array( $this, 'mwb_sfw_cancel_stripe_subscription' ),10,2 );
			include SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/stripe/class-subscriptions-for-woocommerce-stripe.php';
		}

		public function mwb_sfw_cancel_stripe_subscription( $mwb_subscription_id, $status ) {
			
			$mwb_payment_method = get_post_meta( $mwb_subscription_id, '_payment_method',true );
			if ( $mwb_payment_method == 'stripe' ) {
				if ( 'Cancel' == $status ) {
					mwb_sfw_send_email_for_cancel_susbcription( $mwb_subscription_id );
					update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
				}
			}
		}
	}
}
return new Mwb_Subscriptions_Payment_Stripe_Main();
