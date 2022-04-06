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
$wps_success_option = get_option( 'wps_subscription_migrated_successfully', 'no' );
if ( $wps_success_option == 'no' ) {

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
?>
