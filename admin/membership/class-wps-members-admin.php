<?php
/**
 * Membership Layer — Members Admin Tab + User Profile Section (Day 09 stub)
 *
 * Registers the central "Members" settings tab and the per-user profile section
 * that lets admins grant, edit, and revoke memberships.
 * Full implementation lands on Day 09 (June 16).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Members_Admin' ) ) {

	/**
	 * Central members management tab and user-profile membership section.
	 *
	 * @since 2.0.0
	 */
	class WPS_Members_Admin {

		/**
		 * Add the "Members" tab to the plugin settings navigation.
		 *
		 * Hooked to `wps_sfw_sfw_plugin_standard_admin_settings_tabs` at priority 30.
		 * Full implementation: Day 09.
		 *
		 * @since  2.0.0
		 * @param  array $tabs Existing settings tabs.
		 * @return array
		 */
		public function register_tab( $tabs ) {
			// Day 09: add 'membership-members' tab entry.
			return $tabs;
		}

		/**
		 * Render the Members tab list/grant/bulk-action UI.
		 *
		 * Full implementation: Day 09.
		 *
		 * @since 2.0.0
		 */
		public function render_tab() {
			// Day 09: load admin/partials/membership/members-tab.php.
		}

		/**
		 * Render the membership section on a user's profile page.
		 *
		 * Hooked to `show_user_profile` and `edit_user_profile`.
		 * Full implementation: Day 09.
		 *
		 * @since 2.0.0
		 * @param WP_User $user The user object being edited.
		 */
		public function render_profile_section( $user ) {
			// Day 09: load admin/partials/membership/user-profile-memberships.php.
		}

		/**
		 * Save membership changes submitted from the user profile form.
		 *
		 * Hooked to `personal_options_update` and `edit_user_profile_update`.
		 * Full implementation: Day 09.
		 *
		 * @since 2.0.0
		 * @param int $user_id The ID of the user being updated.
		 */
		public function save_profile_section( $user_id ) {
			// Day 09: nonce + manage_woocommerce check → CRUD.
		}

		/**
		 * Handle AJAX membership admin actions (grant, revoke, status-change).
		 *
		 * Hooked to `wp_ajax_wps_membership_admin_action`.
		 * Full implementation: Day 09.
		 *
		 * @since 2.0.0
		 */
		public function handle_admin_actions() {
			// Day 09: check_ajax_referer + manage_woocommerce → CRUD.
			wp_die();
		}
	}
}
