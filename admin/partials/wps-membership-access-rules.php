<?php
/**
 * Access Rules tab — entry partial (Day 12).
 *
 * Loaded by the tab system via:
 *   SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/wps-membership-access-rules.php'
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
	wp_die( esc_html__( 'You do not have permission to view this page.', 'subscriptions-for-woocommerce' ) );
}

$wps_all_plans = wps_get_all_plans( 'active' );
$wps_rules     = wps_get_access_rules();

require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
	. 'admin/partials/membership/access-rules-tab.php';
