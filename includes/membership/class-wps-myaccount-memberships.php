<?php
/**
 * Membership Layer — My Account Memberships Tab (Day 14)
 *
 * Registers a "Memberships" endpoint on the WooCommerce My Account page and
 * renders the customer's active and historical membership records.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Myaccount_Memberships' ) ) {

	/**
	 * Adds and renders the Memberships tab on the WC My Account page.
	 *
	 * @since 2.0.0
	 */
	class WPS_Myaccount_Memberships {

		// -----------------------------------------------------------------------
		// Endpoint + nav
		// -----------------------------------------------------------------------

		/**
		 * Register the rewrite endpoint for /my-account/memberships/.
		 *
		 * Hooked to `init` at priority 5. flush_rewrite_rules() is not called here
		 * because the plugin handles that on activation.
		 *
		 * @since 2.0.0
		 */
		public function register_endpoint() {
			add_rewrite_endpoint( 'memberships', EP_PAGES );
		}

		/**
		 * Add `memberships` to the WP query vars so WC recognises the endpoint.
		 *
		 * @since  2.0.0
		 * @param  array $vars Registered query vars.
		 * @return array
		 */
		public function add_query_var( $vars ) {
			$vars[] = 'memberships';
			return $vars;
		}

		/**
		 * Insert the "Memberships" item into the My Account navigation menu.
		 *
		 * Inserted immediately after the "Subscriptions" item (`wps_subscriptions`)
		 * when present. Falls back to inserting before `customer-logout` so the
		 * item always appears regardless of whether the subscriptions tab exists.
		 *
		 * @since  2.0.0
		 * @param  array $items Existing menu items keyed by endpoint slug.
		 * @return array
		 */
		public function add_menu_item( $items ) {
			$label = __( 'Memberships', 'subscriptions-for-woocommerce' );
			$keys  = array_keys( $items );
			$pos   = array_search( 'wps_subscriptions', $keys, true );

			if ( false !== $pos ) {
				// Insert directly after the Subscriptions item.
				$before = array_slice( $items, 0, $pos + 1, true );
				$after  = array_slice( $items, $pos + 1, null, true );
				return array_merge( $before, array( 'memberships' => $label ), $after );
			}

			// Fallback: insert before customer-logout.
			if ( isset( $items['customer-logout'] ) ) {
				$logout = $items['customer-logout'];
				unset( $items['customer-logout'] );
				$items['memberships']     = $label;
				$items['customer-logout'] = $logout;
				return $items;
			}

			$items['memberships'] = $label;
			return $items;
		}

		// -----------------------------------------------------------------------
		// Tab content
		// -----------------------------------------------------------------------

		/**
		 * Render the Memberships tab content.
		 *
		 * Hooked to `woocommerce_account_memberships_endpoint`. Loads the template
		 * at public/partials/membership/myaccount-memberships.php, passing the
		 * current user's membership rows as `$wps_memberships`.
		 *
		 * @since 2.0.0
		 */
		public function render_tab() {
			$user_id         = get_current_user_id();
			$wps_memberships = function_exists( 'wps_get_user_memberships' )
				? wps_get_user_memberships( $user_id )
				: array();

			$wps_template = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
				. 'public/partials/membership/myaccount-memberships.php';

			if ( file_exists( $wps_template ) ) {
				include $wps_template;
			}
		}
	}
}
