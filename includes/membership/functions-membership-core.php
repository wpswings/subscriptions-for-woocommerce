<?php
/**
 * Membership Layer — Core utilities (Day 01)
 *
 * Data-model constants, schema-version seeding, the product→plan reverse-lookup
 * map builder, and the plan-slug uniqueness utility.
 *
 * No DB tables are created here or anywhere in the membership layer.
 * Plans = CPT `wps_membership_plan` + post meta.
 * Memberships = user meta.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Schema version — gives future migrations a reliable baseline to compare to.
// add_option() is a no-op when the key already exists, so this is idempotent.
// ---------------------------------------------------------------------------
add_action( 'init', 'wps_membership_seed_schema_version', 1 );

if ( ! function_exists( 'wps_membership_seed_schema_version' ) ) {
	/**
	 * Seed the schema-version option on first load.
	 *
	 * Stores the version the DB was initialised at; future upgrade routines compare
	 * against this to decide whether to run. Using `add_option` means an existing
	 * value is never overwritten here — only migration code should call
	 * `update_option( 'wps_membership_schema_version', $new )`.
	 */
	function wps_membership_seed_schema_version() {
		add_option( 'wps_membership_schema_version', '1.0.0', '', 'no' );
	}
}

// ---------------------------------------------------------------------------
// Plan post-meta key constants — single source of truth.
// ---------------------------------------------------------------------------
if ( ! defined( 'WPS_MEMBERSHIP_PLAN_CPT' ) ) {
	define( 'WPS_MEMBERSHIP_PLAN_CPT', 'wps_membership_plan' );
}
if ( ! defined( 'WPS_MEMBERSHIP_PLAN_MAP_OPTION' ) ) {
	define( 'WPS_MEMBERSHIP_PLAN_MAP_OPTION', 'wps_product_plan_map' );
}

// ---------------------------------------------------------------------------
// Product → Plan reverse-lookup map
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_rebuild_product_plan_map' ) ) {
	/**
	 * Build (or rebuild) the product-ID → plan-slug lookup stored in wp_options.
	 *
	 * The map is autoloaded so the sync bridge and grant layer can resolve a
	 * product to its plan in O(1) without querying every plan post on each event.
	 *
	 * Should be called:
	 *  - After a plan's linked products are saved/updated.
	 *  - After a plan is deleted.
	 *
	 * @return array The freshly-built map: [ product_id (int) => plan_slug (string) ].
	 */
	function wps_rebuild_product_plan_map() {
		$map = array();

		$plans = get_posts(
			array(
				'post_type'      => WPS_MEMBERSHIP_PLAN_CPT,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		foreach ( $plans as $plan_id ) {
			$slug = sanitize_key( get_post_meta( $plan_id, '_wps_plan_slug', true ) );
			if ( empty( $slug ) ) {
				continue;
			}

			// Purchase products.
			$products = get_post_meta( $plan_id, '_wps_plan_products', true );
			if ( is_array( $products ) ) {
				foreach ( $products as $product_id ) {
					$product_id = absint( $product_id );
					if ( $product_id > 0 ) {
						$map[ $product_id ] = $slug;
					}
				}
			}

			// Subscription products (separate selector).
			$sub_products = get_post_meta( $plan_id, '_wps_plan_sub_products', true );
			if ( is_array( $sub_products ) ) {
				foreach ( $sub_products as $product_id ) {
					$product_id = absint( $product_id );
					if ( $product_id > 0 ) {
						$map[ $product_id ] = $slug;
					}
				}
			}
		}

		update_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION, $map, true ); // autoload = yes.

		return $map;
	}
}

if ( ! function_exists( 'wps_get_product_plan_map' ) ) {
	/**
	 * Return the cached product → plan map, loading it from options if needed.
	 *
	 * @return array [ product_id (int) => plan_slug (string) ]
	 */
	function wps_get_product_plan_map() {
		$map = get_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION, null );

		if ( ! is_array( $map ) ) {
			$map = wps_rebuild_product_plan_map();
		}

		return $map;
	}
}

// ---------------------------------------------------------------------------
// Plan slug utility
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_generate_unique_plan_slug' ) ) {
	/**
	 * Generate a unique slug for a membership plan.
	 *
	 * Runs `sanitize_title()` on the given name, then checks for collisions
	 * against existing `_wps_plan_slug` post meta values. Appends -2, -3, …
	 * until the slug is unique across all plan CPT posts.
	 *
	 * @param string $name        The human-readable plan name.
	 * @param int    $exclude_id  Optional plan post ID to exclude from the
	 *                            uniqueness check (pass the current plan's ID on
	 *                            update so the plan doesn't collide with itself).
	 * @return string A slug that does not yet exist in any other plan.
	 */
	function wps_generate_unique_plan_slug( $name, $exclude_id = 0 ) {
		$base_slug = sanitize_title( $name );

		if ( empty( $base_slug ) ) {
			$base_slug = 'plan';
		}

		$candidate = $base_slug;
		$suffix    = 2;

		while ( wps_plan_slug_exists( $candidate, $exclude_id ) ) {
			$candidate = $base_slug . '-' . $suffix;
			++$suffix;
		}

		return $candidate;
	}
}

if ( ! function_exists( 'wps_plan_slug_exists' ) ) {
	/**
	 * Check whether a plan slug is already in use.
	 *
	 * @param string $slug       The slug to check.
	 * @param int    $exclude_id Plan post ID to ignore (0 = check all plans).
	 * @return bool True when another plan already uses this slug.
	 */
	function wps_plan_slug_exists( $slug, $exclude_id = 0 ) {
		$slug       = sanitize_key( $slug );
		$exclude_id = absint( $exclude_id );

		if ( empty( $slug ) ) {
			return false;
		}

		$args = array(
			'post_type'      => WPS_MEMBERSHIP_PLAN_CPT,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_wps_plan_slug',
					'value' => $slug,
				),
			),
		);

		if ( $exclude_id > 0 ) {
			$args['post__not_in'] = array( absint( $exclude_id ) );
		}

		$results = get_posts( $args );

		return ! empty( $results );
	}
}
