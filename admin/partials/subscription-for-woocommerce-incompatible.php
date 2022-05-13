<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpswings.com/?utm_source=wpswings-official&utm_medium=order-bump-org-backend&utm_campaign=official
 * @since      1.0.0
 *
 * @package    Upsell_Order_Bump_Offer_For_Woocommerce
 * @subpackage Upsell_Order_Bump_Offer_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( class_exists( 'Subscriptions_For_Woocommerce_Admin' ) ) {

	$wps_sfw_get_count = new Subscriptions_For_Woocommerce_Admin( 'subscriptions-for-woocommerce', '1.4.1' );
	$wps_sfw_pending_product_count  = $wps_sfw_get_count->wps_sfw_get_count( 'pending', 'count', 'products' );
	$wps_sfw_pending_orders_count   = $wps_sfw_get_count->wps_sfw_get_count( 'pending', 'count', 'mwb_renewal_orders' );
	$wps_sfw_pending_subs_count     = $wps_sfw_get_count->wps_sfw_get_count( 'pending', 'count', 'post_type_subscription' );


	if ( '0' != $wps_sfw_pending_product_count || '0' != $wps_sfw_pending_orders_count || '0' != $wps_sfw_pending_subs_count ) {
		$wps_par_global_custom_css = 'const triggerError = () => {
        swal({
    
            title: "Attention Required!",
            text: "Please Migrate Your Database Keys First By Clicking On Below Button , Then You can Have Access To Your Dashboard Button",
            icon: "error",
            button: "Click To Import",
            closeOnClickOutside: false,
        }).then(function() {
            wps_subscripiton_migration_success();
        });
    }
    triggerError();';
		wp_register_script( 'wps_par_incompatible_css', false, array(), '1.2.8', 'all' );
		wp_enqueue_script( 'wps_par_incompatible_css' );
		wp_add_inline_script( 'wps_par_incompatible_css', $wps_par_global_custom_css );
	}
}

