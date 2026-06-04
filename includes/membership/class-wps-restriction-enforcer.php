<?php
/**
 * Membership Layer — Content Restriction Enforcer (Day 13 stub)
 *
 * Hooks `the_content`, `template_redirect`, and WooCommerce purchasability
 * filters to gate access to restricted content and products.
 * Also registers the `[wps-restrict]` shortcode.
 * Full implementation lands on Day 13 (June 22).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Restriction_Enforcer' ) ) {

	/**
	 * Gates content, products, and archives based on Access Rules.
	 *
	 * @since 2.0.0
	 */
	class WPS_Restriction_Enforcer {

		/**
		 * Filter post content, replacing it with the restriction message when
		 * the current user lacks the required plan membership.
		 *
		 * Passes content through unchanged until Day 13.
		 *
		 * @since  2.0.0
		 * @param  string $content Post content.
		 * @return string Original or restricted content.
		 */
		public function maybe_restrict_content( $content ) {
			return $content;
		}

		/**
		 * Redirect non-members away from restricted URLs before the page renders.
		 *
		 * No-op until Day 13.
		 *
		 * @since 2.0.0
		 */
		public function maybe_redirect() {
			// Day 13: wps_object_is_restricted() → wp_safe_redirect + exit.
		}

		/**
		 * Filter product purchasability for non-members.
		 *
		 * Passes the value through unchanged until Day 13.
		 *
		 * @since  2.0.0
		 * @param  bool       $purchasable Whether the product is purchasable.
		 * @param  WC_Product $product     Product object.
		 * @return bool
		 */
		public function maybe_restrict_purchasability( $purchasable, $product ) {
			return $purchasable;
		}

		/**
		 * Register the [wps-restrict] shortcode.
		 *
		 * Hooked to `init`. The shortcode callback is a no-op until Day 13.
		 *
		 * @since 2.0.0
		 */
		public function register_shortcode() {
			add_shortcode( 'wps-restrict', array( $this, 'shortcode_output' ) );
		}

		/**
		 * Render [wps-restrict] shortcode output.
		 *
		 * Shows wrapped content to all users until Day 13 enforces plan checks.
		 *
		 * @since  2.0.0
		 * @param  array  $atts    Shortcode attributes.
		 * @param  string $content Wrapped content.
		 * @return string
		 */
		public function shortcode_output( $atts, $content = '' ) {
			return do_shortcode( $content );
		}
	}
}
