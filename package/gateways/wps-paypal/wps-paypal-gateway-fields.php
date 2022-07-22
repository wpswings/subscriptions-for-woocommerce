<?php
/**
 * The file that defines the fields for payment class
 *
 * @link  https://wpswings.com/
 * @since 1.6.4
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/package/wps-build-in-paypal
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'enabled'               => array(
		'title'   => __( 'Enable/Disable', 'wps-paypal-integration-for-woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable PayPal Standard', 'wps-paypal-integration-for-woocommerce' ),
		'default' => 'no',
	),
	'title'                 => array(
		'title'       => __( 'Title', 'wps-paypal-integration-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'wps-paypal-integration-for-woocommerce' ),
		'default'     => __( 'PayPal', 'wps-paypal-integration-for-woocommerce' ),
		'desc_tip'    => true,
	),
	'description'           => array(
		'title'       => __( 'Description', 'wps-paypal-integration-for-woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'wps-paypal-integration-for-woocommerce' ),
		'default'     => __( "Pay via PayPal; you can pay with your credit card if you don't have a PayPal account.", 'wps-paypal-integration-for-woocommerce' ),
	),
	'testmode'              => array(
		'title'       => __( 'PayPal sandbox', 'wps-paypal-integration-for-woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable PayPal sandbox', 'wps-paypal-integration-for-woocommerce' ),
		'default'     => 'no',
		/* translators: %s: URL */
		'description' => sprintf( __( 'PayPal sandbox can be used to test payments. Sign up for a <a href="%s">developer account</a>.', 'wps-paypal-integration-for-woocommerce' ), 'https://developer.paypal.com/' ),
	),
	'ipn_notification'      => array(
		'title'       => __( 'IPN email notifications', 'wps-paypal-integration-for-woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable IPN email notifications', 'wps-paypal-integration-for-woocommerce' ),
		'default'     => 'no',
		'description' => sprintf(
			/* translators: navigate to setting and update url. */
			__( 'Send notifications when an IPN is received from PayPal indicating refunds, chargebacks and cancellations. Please make sure to login your PayPal business account navigate to %s  <button class="wps-ipn-url-copy-to-clipboard">Copy</button>', 'wps-paypal-integration-for-woocommerce' ),
			'<b> Account-settings > Notifications > Instant payment notifications </b> :: click on Update and enter the url provided below and make sure to select the Receive IPN messages (Enabled) and then save. <span id="wps-ipn-url-copy">' . home_url( '/' ) . 'wc-api/wc_gateway_wps_paypal_integration</span>'
		),
	),
	'email'        => array(
		'title'       => __( 'Email', 'wps-paypal-integration-for-woocommerce' ),
		'type'        => 'email',
		'description' => __( 'You will recieve IPN email notifications on this mail.', 'wps-paypal-integration-for-woocommerce' ),
		'default'     => get_option( 'admin_email' ),
		'desc_tip'    => true,
		'placeholder' => 'you@youremail.com',
	),
	'invoice_prefix'        => array(
		'title'       => __( 'Invoice prefix', 'wps-paypal-integration-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'wps-paypal-integration-for-woocommerce' ),
		'default'     => 'wps-paypal-',
		'desc_tip'    => true,
	),
	'api_details'           => array(
		'title'       => __( 'API credentials', 'wps-paypal-integration-for-woocommerce' ),
		'type'        => 'title',
		'description' => sprintf(
			/* translators: 1: paypal developer home url 2: create application link */
			__( 'To get your API credentials please create a <a href="%1$s" target="_blank">PayPal developer account</a>. Visit <a href="%2$s" target="_blank">My Apps & Credentials</a> select the tab ( Sandbox or Live ), Create app and get the below credentails.', 'wps-paypal-integration-for-woocommerce' ),
			'https://developer.paypal.com',
			'https://developer.paypal.com/developer/applications'
		),
	),
	'client_id'          => array(
		'title'             => __( 'Client ID', 'wps-paypal-integration-for-woocommerce' ),
		'type'              => 'text',
		'description'       => __( 'Please enter client ID here after following the above mentioned process.', 'wps-paypal-integration-for-woocommerce' ),
		'default'           => '',
		'desc_tip'          => true,
		'custom_attributes' => array( 'autocomplete' => 'new-password' ),
	),
	'client_secret'          => array(
		'title'             => __( 'Client secret', 'wps-paypal-integration-for-woocommerce' ),
		'type'              => 'password',
		'description'       => __( 'Please enter client secret here after following the above mentioned process.', 'wps-paypal-integration-for-woocommerce' ),
		'default'           => '',
		'desc_tip'          => true,
		'custom_attributes' => array( 'autocomplete' => 'new-password' ),
	),
);
