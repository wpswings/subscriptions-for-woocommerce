<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for general tab.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $sfw_wps_sfw_obj;
// Add filed above susbcription list.
$sfw_template_settings = apply_filters( 'sfw_template_settings_array', array() );
?>
<!--  template file for admin settings. -->
<div class="sfw-section-wrap">
	<?php

		require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/class-subscriptions-for-woocommerce-admin-subscription-list.php';
	?>
</div>
