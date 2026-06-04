<?php
/**
 * Membership Layer — Plan CPT Registration (Day 07 stub)
 *
 * Registers the `wps_membership_plan` custom post type and its four meta boxes.
 * Full implementation lands on Day 07 (June 12).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Membership_Plan_CPT' ) ) {

	/**
	 * Registers the wps_membership_plan CPT and meta boxes.
	 *
	 * @since 2.0.0
	 */
	class WPS_Membership_Plan_CPT {

		/**
		 * Register the CPT with WordPress.
		 *
		 * Hooked to `init` at priority 5.
		 * Full implementation: Day 07.
		 *
		 * @since 2.0.0
		 */
		public function register() {
			// Day 07: register_post_type( WPS_MEMBERSHIP_PLAN_CPT, [...] )
		}

		/**
		 * Save CPT meta boxes on post save.
		 *
		 * Hooked to `save_post` at priority 10.
		 * Full implementation: Day 07.
		 *
		 * @since 2.0.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		public function save_meta_boxes( $post_id, $post ) {
			// Day 07: nonce check, sanitize, save meta, rebuild product→plan map.
		}
	}
}
