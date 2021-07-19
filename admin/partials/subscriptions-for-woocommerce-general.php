<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for general tab.
 *
 * @link       https://makewebbetter.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $sfw_mwb_sfw_obj;
$sfw_genaral_settings = apply_filters( 'mwb_sfw_general_settings_array', array() );
?>
<!--  template file for admin settings. -->
<form action="" method="POST" class="mwb-sfw-gen-section-form">
	<div class="sfw-secion-wrap">
		<?php
		$sfw_general_html = $sfw_mwb_sfw_obj->mwb_sfw_plug_generate_html( $sfw_genaral_settings );
		echo esc_html( $sfw_general_html );
		wp_nonce_field( 'mwb-sfw-general-nonce', 'mwb-sfw-general-nonce-field' );
		?>
	</div>
</form>
