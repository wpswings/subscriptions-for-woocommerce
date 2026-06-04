<?php
/**
 * Membership Layer — Access Rules Admin Tab (Day 12 stub)
 *
 * Registers the "Access Rules" settings tab and handles rule CRUD + AJAX
 * target search used by the rules UI.
 * Full implementation lands on Day 12 (June 19).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Access_Rules_Admin' ) ) {

	/**
	 * Access Rules settings tab and supporting AJAX handlers.
	 *
	 * @since 2.0.0
	 */
	class WPS_Access_Rules_Admin {

		/**
		 * Add the "Access Rules" tab to the plugin settings navigation.
		 *
		 * Hooked to `wps_sfw_sfw_plugin_standard_admin_settings_tabs` at priority 35.
		 * Full implementation: Day 12.
		 *
		 * @since  2.0.0
		 * @param  array $tabs Existing settings tabs.
		 * @return array
		 */
		public function register_tab( $tabs ) {
			// Day 12: add 'membership-access-rules' tab entry.
			return $tabs;
		}

		/**
		 * Render the Access Rules tab.
		 *
		 * Full implementation: Day 12.
		 *
		 * @since 2.0.0
		 */
		public function render_tab() {
			// Day 12: load admin/partials/membership/access-rules-tab.php.
		}

		/**
		 * AJAX: search purchasable products for the Linked Products meta box.
		 *
		 * Hooked to `wp_ajax_wps_search_plan_products`.
		 * Full implementation: Day 08.
		 *
		 * @since 2.0.0
		 */
		public function ajax_search_plan_products() {
			check_ajax_referer( 'wps_membership_admin_nonce', 'nonce' );
			wp_send_json( array() );
		}

		/**
		 * AJAX: search content targets (posts, terms) for an access rule row.
		 *
		 * Hooked to `wp_ajax_wps_search_rule_targets`.
		 * Full implementation: Day 12.
		 *
		 * @since 2.0.0
		 */
		public function ajax_search_rule_targets() {
			check_ajax_referer( 'wps_membership_admin_nonce', 'nonce' );
			wp_send_json( array() );
		}
	}
}
