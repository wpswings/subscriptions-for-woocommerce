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
fhfhfgh
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $sfw_mwb_sfw_obj;
$sfw_template_settings = apply_filters( 'sfw_template_settings_array', array() );
?>
<!--  template file for admin settings. -->
<div class="sfw-section-wrap">
	<?php
		$sfw_template_html = $sfw_mwb_sfw_obj->mwb_sfw_plug_generate_html( $sfw_template_settings );
		echo esc_html( $sfw_template_html );
	?>
</div>
