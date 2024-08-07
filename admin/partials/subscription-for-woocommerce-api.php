<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for API tab.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Woocommerce_Subscriptions_Pro
 * @subpackage Woocommerce_Subscriptions_Pro/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $sfw_wps_sfw_obj;
$sfw_api_settings = apply_filters( 'wps_sfw_api_settings_array', array() );
?>
<!--  template file for admin settings. -->
<form action="" method="POST" class="wps-sfw-gen-section-form">
	<div class="sfw-secion-wrap">
		<?php
		$wsp_api_html = $sfw_wps_sfw_obj->wps_sfw_plug_generate_html( $sfw_api_settings );
		echo esc_html( $wsp_api_html );
		wp_nonce_field( 'wps-sfw-api-nonce', 'wps-sfw-api-nonce-field' );
		?>
	</div>
</form>
<div class="wps_sfw_api_details_main_wrapper">
	<h3><?php esc_html_e( 'Plugin API Details', 'subscriptions-for-woocommerce' ); ?></h3>
	<!-- Show Authentication -->
	<h4><?php esc_html_e( 'Authentication', 'subscriptions-for-woocommerce' ); ?></h4>
	<div class="wps_sfw_rest_api_response">
		<p>
			<?php
			esc_html_e( 'For authentication you need ', 'subscriptions-for-woocommerce' );
			esc_html_e( ' Consumer Secret ', 'subscriptions-for-woocommerce' );
			echo '<strong>{consumer_secret}</strong>';
			esc_html_e( ' keys. Response on wrong api details:', 'subscriptions-for-woocommerce' );
			?>
		</p>
	<pre>
	{
	"code": "rest_forbidden",
	"message": "Sorry, you are not allowed to do that.",
	"data": {
		"status": 401
		}
	}
	</pre>
	</div>

	<!-- To get user points -->
	<h4><?php esc_html_e( 'To Retrive All Subscription', 'subscriptions-for-woocommerce' ); ?></h4>
	<div class="wps_sfw_rest_api_response">
		<p><strong><?php esc_html_e( 'Base Url to get all subscription  : ', 'subscriptions-for-woocommerce' ); ?></strong>{site_url}/wp-json/wsp-route/v1/wsp-view-subscription</p>
		<p>
			<strong>
			<?php
			esc_html_e( 'Example : ', 'subscriptions-for-woocommerce' );
			echo esc_html( site_url() );
			esc_html_e( '/wp-json/wsp-route/v1/wsp-view-subscription', 'subscriptions-for-woocommerce' );
			?>
			</strong>
		<p>
		<?php
		esc_html_e( 'Parameters Required : ', 'subscriptions-for-woocommerce' );
		echo wp_kses_post( '<strong> {consumer_secret}</strong>' );
		?>
		</p>
		<p><?php esc_html_e( 'JSON response example:', 'subscriptions-for-woocommerce' ); ?></p>
	<pre>
	{
	"code": 200,
	"status": "success",
	"data": [
		{

			"subscription_id": 490,
			"parent_order_id": "489",
			"status": "cancelled",
			"product_name": "wpswings-daily-susbcription",
			"recurring_amount": "6",
			"user_name": "admin",
			"next_payment_date": "April 8, 2021 9:09 am",
			"subscriptions_expiry_date": "—"
		},
		{
			"subscription_id": 486,
			"parent_order_id": "485",
			"status": "active",
			"product_name": "free trial",
			"recurring_amount": "8",
			"user_name": "admin",
			"next_payment_date": "May 4, 2021 12:42 pm",
			"subscriptions_expiry_date": "—"
		},
		]
	}
	</pre>
	</div>
</div>
