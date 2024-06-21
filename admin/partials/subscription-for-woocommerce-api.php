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
