<?php
/**
 * Membership Layer — Plan CRUD (Day 02)
 *
 * All plan read/write operations go through these functions.
 * Raw wp_insert_post / update_post_meta calls must not be made directly
 * by other code — route everything through this API.
 *
 * Storage: CPT `wps_membership_plan` + post meta.
 * No custom DB tables are used.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_membership_plan_row' ) ) {
	/**
	 * Build a normalised plan array from a WP_Post + its meta.
	 *
	 * Values are raw (unescaped) — callers must escape before output.
	 * This is the canonical shape every public CRUD function returns.
	 *
	 * @since  2.0.0
	 * @param  WP_Post $post A post of type WPS_MEMBERSHIP_PLAN_CPT.
	 * @return array Plan data array.
	 */
	function wps_membership_plan_row( WP_Post $post ) {
		$access_length = get_post_meta( $post->ID, '_wps_plan_access_length', true );
		$products      = get_post_meta( $post->ID, '_wps_plan_products', true );
		$status        = get_post_meta( $post->ID, '_wps_plan_status', true );
		$sub_products  = get_post_meta( $post->ID, '_wps_plan_sub_products', true );

		// Single grant method — mutually exclusive.
		// Default to 'purchase' for backward compat with plans saved before this field.
		$grant_method  = get_post_meta( $post->ID, '_wps_plan_grant_method', true );
		$valid_methods = array( 'purchase', 'subscription', 'auto_enroll' );
		if ( ! in_array( $grant_method, $valid_methods, true ) ) {
			$grant_method = 'purchase';
		}

		return array(
			'id'                    => $post->ID,
			'name'                  => $post->post_title,
			'slug'                  => get_post_meta( $post->ID, '_wps_plan_slug', true ),
			'description'           => $post->post_content,
			'status'                => in_array( $status, array( 'active', 'inactive' ), true )
				? $status
				: 'active',
			'color'                 => get_post_meta( $post->ID, '_wps_plan_color', true ),
			'access_length'         => is_array( $access_length ) ? $access_length : array(
				'type'  => 'lifetime',
				'value' => 0,
				'unit'  => 'day',
			),
			'products'              => is_array( $products )
				? array_map( 'absint', $products )
				: array(),
			'subscription_products' => is_array( $sub_products )
				? array_map( 'absint', $sub_products )
				: array(),
			'grant_method'          => $grant_method,
			// Convenience booleans derived from grant_method — used by callers
			// that check enabled flags (order grant, sync, badge, enforcer).
			'purchase_enabled'      => 'purchase' === $grant_method,
			'subscription_enabled'  => 'subscription' === $grant_method,
			'auto_enroll'           => 'auto_enroll' === $grant_method,
		);
	}
}

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_create_plan' ) ) {
	/**
	 * Create a new membership plan.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Plan arguments. {
	 *   @type string $name          Required. Plan display name.
	 *   @type string $slug          Optional. Auto-generated from name if omitted.
	 *   @type string $description   Optional. Plan description (wp_kses_post applied).
	 *   @type string $status        Optional. 'active' (default) or 'inactive'.
	 *   @type string $color         Optional. Badge hex color.
	 *   @type array  $products      Optional. Array of WC product IDs to link.
	 *   @type array  $access_length Optional. [ 'type' => 'lifetime'|'fixed',
	 *                                           'value' => int, 'unit' => 'day'|'month'|'year' ].
	 * }
	 * @return int|WP_Error New plan post ID on success, WP_Error on failure.
	 */
	function wps_create_plan( array $args ) {
		$name = isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : '';
		if ( empty( $name ) ) {
			return new WP_Error(
				'wps_plan_no_name',
				__( 'Plan name is required.', 'subscriptions-for-woocommerce' )
			);
		}

		$slug = isset( $args['slug'] ) && ! empty( $args['slug'] )
			? wps_generate_unique_plan_slug( $args['slug'] )
			: wps_generate_unique_plan_slug( $name );

		$post_id = wp_insert_post(
			array(
				'post_type'    => WPS_MEMBERSHIP_PLAN_CPT,
				'post_title'   => $name,
				'post_content' => isset( $args['description'] ) ? wp_kses_post( $args['description'] ) : '',
				'post_status'  => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$status        = ( isset( $args['status'] ) && 'inactive' === $args['status'] ) ? 'inactive' : 'active';
		$raw_color     = isset( $args['color'] ) ? $args['color'] : '';
		$color         = sanitize_hex_color( $raw_color );
		$raw_length    = isset( $args['access_length'] ) ? $args['access_length'] : array();
		$access_length = wps_sanitize_access_length( $raw_length );

		update_post_meta( $post_id, '_wps_plan_slug', $slug );
		update_post_meta( $post_id, '_wps_plan_status', $status );
		update_post_meta( $post_id, '_wps_plan_color', $color ? $color : '' );
		update_post_meta( $post_id, '_wps_plan_access_length', $access_length );

		$valid_methods = array( 'purchase', 'subscription', 'auto_enroll' );
		$grant_method  = isset( $args['grant_method'] ) && in_array( $args['grant_method'], $valid_methods, true )
			? $args['grant_method']
			: 'purchase';
		update_post_meta( $post_id, '_wps_plan_grant_method', $grant_method );

		$products = isset( $args['products'] ) && is_array( $args['products'] )
			? array_values( array_filter( array_map( 'absint', $args['products'] ) ) )
			: array();
		update_post_meta( $post_id, '_wps_plan_products', $products );

		wps_rebuild_product_plan_map();

		/**
		 * Fires after a membership plan is created.
		 *
		 * @since 2.0.0
		 *
		 * @param int   $post_id Plan post ID.
		 * @param array $args    The original args passed to wps_create_plan().
		 */
		do_action( 'wps_plan_created', $post_id, $args );

		return $post_id;
	}
}

// ---------------------------------------------------------------------------
// Read
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_get_plan' ) ) {
	/**
	 * Retrieve a plan by its post ID.
	 *
	 * @since  2.0.0
	 * @param  int $plan_id Plan post ID.
	 * @return array|null Normalised plan array or null if not found / wrong type.
	 */
	function wps_get_plan( $plan_id ) {
		$post = get_post( absint( $plan_id ) );

		if ( ! $post || WPS_MEMBERSHIP_PLAN_CPT !== $post->post_type ) {
			return null;
		}

		return wps_membership_plan_row( $post );
	}
}

if ( ! function_exists( 'wps_get_plan_by_slug' ) ) {
	/**
	 * Retrieve a plan by its unique slug.
	 *
	 * @since  2.0.0
	 * @param  string $slug Plan slug.
	 * @return array|null Normalised plan array or null if not found.
	 */
	function wps_get_plan_by_slug( $slug ) {
		$slug = sanitize_key( $slug );
		if ( empty( $slug ) ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'      => WPS_MEMBERSHIP_PLAN_CPT,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_wps_plan_slug',
						'value' => $slug,
					),
				),
			)
		);

		if ( empty( $posts ) ) {
			return null;
		}

		return wps_membership_plan_row( $posts[0] );
	}
}

if ( ! function_exists( 'wps_get_all_plans' ) ) {
	/**
	 * Retrieve all membership plans, optionally filtered by plan status.
	 *
	 * @since  2.0.0
	 * @param  string $status 'active', 'inactive', or 'all' (default).
	 * @return array Array of normalised plan arrays.
	 */
	function wps_get_all_plans( $status = 'all' ) {
		$args = array(
			'post_type'      => WPS_MEMBERSHIP_PLAN_CPT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( 'all' !== $status ) {
			if ( 'inactive' === $status ) {
				$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_wps_plan_status',
						'value'   => 'inactive',
						'compare' => '=',
					),
				);
			} else {
				// Plans with no status meta default to 'active' per wps_membership_plan_row().
				$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_wps_plan_status',
						'value'   => 'active',
						'compare' => '=',
					),
					array(
						'key'     => '_wps_plan_status',
						'compare' => 'NOT EXISTS',
					),
				);
			}
		}

		$posts = get_posts( $args );

		return array_map( 'wps_membership_plan_row', $posts );
	}
}

if ( ! function_exists( 'wps_get_plans_for_product' ) ) {
	/**
	 * Return all active plans that list a given product (via any grant method).
	 *
	 * Checks the purchase product list (`products`) for purchase-grant plans and
	 * the subscription product list (`subscription_products`) for subscription-grant
	 * plans. Auto-enroll plans are not linked to specific products.
	 *
	 * @since  2.0.0
	 * @param  int $product_id WC product or variation ID.
	 * @return array Filtered array of plan arrays from wps_membership_plan_row().
	 */
	function wps_get_plans_for_product( $product_id ) {
		$product_id = absint( $product_id );
		$all_plans  = wps_get_all_plans( 'active' );
		return array_values(
			array_filter(
				$all_plans,
				function ( $plan ) use ( $product_id ) {
					if ( 'purchase' === $plan['grant_method'] ) {
						return in_array( $product_id, $plan['products'], true );
					}
					if ( 'subscription' === $plan['grant_method'] ) {
						return in_array( $product_id, $plan['subscription_products'], true );
					}
					return false;
				}
			)
		);
	}
}

if ( ! function_exists( 'wps_get_plans_for_subscription_product' ) ) {
	/**
	 * Return all active subscription-grant plans that list a given product.
	 *
	 * @since  2.0.0
	 * @param  int $product_id WC product (or variation) ID.
	 * @return array Filtered array of plan arrays from wps_membership_plan_row().
	 */
	function wps_get_plans_for_subscription_product( $product_id ) {
		$product_id = absint( $product_id );
		$all_plans  = wps_get_all_plans( 'active' );
		return array_values(
			array_filter(
				$all_plans,
				function ( $plan ) use ( $product_id ) {
					return 'subscription' === $plan['grant_method']
						&& in_array( $product_id, $plan['subscription_products'], true );
				}
			)
		);
	}
}

// ---------------------------------------------------------------------------
// Update
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_update_plan' ) ) {
	/**
	 * Update an existing membership plan.
	 *
	 * Only the keys present in $args are updated; omitted keys are left unchanged.
	 *
	 * @since 2.0.0
	 *
	 * @param int   $plan_id Plan post ID.
	 * @param array $args    Keys accepted: name, description, status, color,
	 *                       access_length, products.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	function wps_update_plan( $plan_id, array $args ) {
		$plan_id = absint( $plan_id );
		$post    = get_post( $plan_id );

		if ( ! $post || WPS_MEMBERSHIP_PLAN_CPT !== $post->post_type ) {
			return new WP_Error(
				'wps_plan_not_found',
				__( 'Plan not found.', 'subscriptions-for-woocommerce' )
			);
		}

		$post_data = array( 'ID' => $plan_id );

		if ( isset( $args['name'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $args['name'] );
		}
		if ( isset( $args['description'] ) ) {
			$post_data['post_content'] = wp_kses_post( $args['description'] );
		}

		if ( count( $post_data ) > 1 ) {
			$result = wp_update_post( $post_data, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( isset( $args['status'] ) ) {
			$new_status = ( 'inactive' === $args['status'] ) ? 'inactive' : 'active';
			update_post_meta( $plan_id, '_wps_plan_status', $new_status );
		}

		if ( isset( $args['color'] ) ) {
			$color = sanitize_hex_color( $args['color'] );
			update_post_meta( $plan_id, '_wps_plan_color', $color ? $color : '' );
		}

		if ( isset( $args['access_length'] ) ) {
			$access_length = wps_sanitize_access_length( $args['access_length'] );
			update_post_meta( $plan_id, '_wps_plan_access_length', $access_length );
		}

		if ( isset( $args['products'] ) && is_array( $args['products'] ) ) {
			$old_products = (array) get_post_meta( $plan_id, '_wps_plan_products', true );
			$new_products = array_values( array_filter( array_map( 'absint', $args['products'] ) ) );

			$added   = array_diff( $new_products, $old_products );
			$removed = array_diff( $old_products, $new_products );

			update_post_meta( $plan_id, '_wps_plan_products', $new_products );
			wps_rebuild_product_plan_map();

			$slug = get_post_meta( $plan_id, '_wps_plan_slug', true );

			foreach ( $added as $pid ) {
				/**
				 * Fires after a product is linked to a plan.
				 *
				 * @since 2.0.0
				 *
				 * @param int    $pid     WC product ID.
				 * @param string $slug    Plan slug.
				 * @param int    $plan_id Plan post ID.
				 */
				do_action( 'wps_plan_product_linked', $pid, $slug, $plan_id );
			}

			foreach ( $removed as $pid ) {
				/**
				 * Fires after a product is unlinked from a plan.
				 *
				 * @since 2.0.0
				 *
				 * @param int    $pid     WC product ID.
				 * @param string $slug    Plan slug.
				 * @param int    $plan_id Plan post ID.
				 */
				do_action( 'wps_plan_product_unlinked', $pid, $slug, $plan_id );
			}
		}

		/**
		 * Fires after a membership plan is updated.
		 *
		 * @since 2.0.0
		 *
		 * @param int   $plan_id Plan post ID.
		 * @param array $args    The args passed to wps_update_plan().
		 */
		do_action( 'wps_plan_updated', $plan_id, $args );

		return true;
	}
}

// ---------------------------------------------------------------------------
// Delete
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_delete_plan' ) ) {
	/**
	 * Delete a membership plan and cascade:
	 *   1. Unlink all products (rebuilds the reverse map).
	 *   2. Cancel every user's membership for this plan via the membership CRUD
	 *      (if those functions are loaded — avoids a hard dependency on Day 03).
	 *   3. Delete the CPT post.
	 *
	 * @since 2.0.0
	 *
	 * @param int $plan_id Plan post ID.
	 * @return true|WP_Error True on success, WP_Error if the plan is not found.
	 */
	function wps_delete_plan( $plan_id ) {
		$plan_id = absint( $plan_id );
		$plan    = wps_get_plan( $plan_id );

		if ( ! $plan ) {
			return new WP_Error(
				'wps_plan_not_found',
				__( 'Plan not found.', 'subscriptions-for-woocommerce' )
			);
		}

		/**
		 * Fires before a plan is deleted.
		 *
		 * Allows the membership CRUD layer and other code to cancel memberships
		 * before the plan record disappears.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $plan_id Plan post ID.
		 * @param string $slug    Plan slug.
		 */
		do_action( 'wps_plan_before_delete', $plan_id, $plan['slug'] );

		// Cancel active memberships — guarded in case Day 03 is not loaded yet.
		if ( function_exists( 'wps_cancel_all_memberships_for_plan' ) ) {
			wps_cancel_all_memberships_for_plan( $plan['slug'] );
		}

		// Unlink products and rebuild the reverse map.
		update_post_meta( $plan_id, '_wps_plan_products', array() );
		wps_rebuild_product_plan_map();

		wp_delete_post( $plan_id, true ); // Force-delete, no trash.

		/**
		 * Fires after a membership plan is deleted.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $plan_id Plan post ID (now deleted).
		 * @param string $slug    Plan slug.
		 */
		do_action( 'wps_plan_deleted', $plan_id, $plan['slug'] );

		return true;
	}
}

// ---------------------------------------------------------------------------
// Plan ↔ Product link helpers
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_link_product_to_plan' ) ) {
	/**
	 * Add a WC product to a plan's product list.
	 *
	 * Idempotent — calling it twice with the same IDs is safe.
	 *
	 * @since  2.0.0
	 * @param  int    $product_id WC product ID.
	 * @param  string $plan_slug  Plan slug.
	 * @return bool True if the product was newly added, false if already linked.
	 */
	function wps_link_product_to_plan( $product_id, $plan_slug ) {
		$product_id = absint( $product_id );
		$plan       = wps_get_plan_by_slug( sanitize_key( $plan_slug ) );

		if ( ! $plan || ! $product_id ) {
			return false;
		}

		$products = $plan['products'];
		if ( in_array( $product_id, $products, true ) ) {
			return false;
		}

		$products[] = $product_id;
		update_post_meta( $plan['id'], '_wps_plan_products', $products );
		wps_rebuild_product_plan_map();

		/**
		 * Fires after a product is linked to a plan.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $product_id WC product ID.
		 * @param string $plan_slug  Plan slug.
		 * @param int    $plan_id    Plan post ID.
		 */
		do_action( 'wps_plan_product_linked', $product_id, $plan_slug, $plan['id'] );

		return true;
	}
}

if ( ! function_exists( 'wps_unlink_product_from_plan' ) ) {
	/**
	 * Remove a WC product from a plan's product list.
	 *
	 * @since  2.0.0
	 * @param  int    $product_id WC product ID.
	 * @param  string $plan_slug  Plan slug.
	 * @return bool True if the product was removed, false if it was not linked.
	 */
	function wps_unlink_product_from_plan( $product_id, $plan_slug ) {
		$product_id = absint( $product_id );
		$plan       = wps_get_plan_by_slug( sanitize_key( $plan_slug ) );

		if ( ! $plan || ! $product_id ) {
			return false;
		}

		$products = $plan['products'];
		$key      = array_search( $product_id, $products, true );

		if ( false === $key ) {
			return false;
		}

		unset( $products[ $key ] );
		update_post_meta( $plan['id'], '_wps_plan_products', array_values( $products ) );
		wps_rebuild_product_plan_map();

		/**
		 * Fires after a product is unlinked from a plan.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $product_id WC product ID.
		 * @param string $plan_slug  Plan slug.
		 * @param int    $plan_id    Plan post ID.
		 */
		do_action( 'wps_plan_product_unlinked', $product_id, $plan_slug, $plan['id'] );

		return true;
	}
}

if ( ! function_exists( 'wps_get_plan_products' ) ) {
	/**
	 * Return the array of WC product IDs linked to a plan.
	 *
	 * @since  2.0.0
	 * @param  string $plan_slug Plan slug.
	 * @return int[] Array of product IDs (may be empty).
	 */
	function wps_get_plan_products( $plan_slug ) {
		$plan = wps_get_plan_by_slug( sanitize_key( $plan_slug ) );

		return $plan ? $plan['products'] : array();
	}
}

if ( ! function_exists( 'wps_get_plan_by_product' ) ) {
	/**
	 * Return the plan slug linked to a given product ID.
	 *
	 * Reads the autoloaded `wps_product_plan_map` option — O(1), no post query.
	 * Used by the sync bridge and order-grant layer on every purchase/renewal event.
	 *
	 * @since  2.0.0
	 * @param  int $product_id WC product ID.
	 * @return string|null Plan slug, or null if no plan is linked to this product.
	 */
	function wps_get_plan_by_product( $product_id ) {
		$product_id = absint( $product_id );
		$map        = wps_get_product_plan_map();

		return isset( $map[ $product_id ] ) ? $map[ $product_id ] : null;
	}
}

if ( ! function_exists( 'wps_product_actively_grants_membership' ) ) {
	/**
	 * Return the plan slug only when the product is linked AND the relevant
	 * grant method (purchase or subscription) is currently enabled.
	 *
	 * Use this wherever UI or enforcement decisions depend on whether the
	 * grant is live — badge display, product page panel, purchasability bypass.
	 * `wps_get_plan_by_product()` is intentionally left as a raw map lookup so
	 * the sync/order-grant layer can still read the plan even when the method
	 * is temporarily disabled (those callers check the flag themselves).
	 *
	 * @since  2.0.0
	 * @param  int $product_id WC product ID.
	 * @return string|null Plan slug if an enabled method links this product,
	 *                     null otherwise.
	 */
	function wps_product_actively_grants_membership( $product_id ) {
		$product_id = absint( $product_id );
		$plan_slug  = wps_get_plan_by_product( $product_id );
		if ( ! $plan_slug ) {
			return null;
		}

		$plan = wps_get_plan_by_slug( $plan_slug );
		if ( ! $plan ) {
			return null;
		}

		// Product is in the one-time purchase list and that method is on.
		if ( $plan['purchase_enabled'] && in_array( $product_id, $plan['products'], true ) ) {
			return $plan_slug;
		}

		// Product is in the subscription list and that method is on.
		if ( $plan['subscription_enabled']
			&& in_array( $product_id, $plan['subscription_products'], true ) ) {
			return $plan_slug;
		}

		return null;
	}
}

// ---------------------------------------------------------------------------
// Purchase CTA helpers
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_get_plan_purchasable_products' ) ) {
	/**
	 * Return purchasable, in-stock WC products linked to a plan.
	 *
	 * Skips products that are drafts, hidden, out of stock, or not purchasable.
	 * Used by the restriction message to offer the visitor a buy link.
	 *
	 * @since  2.0.0
	 * @param  string $plan_slug Plan slug.
	 * @return WC_Product[] Array of purchasable WC_Product objects (may be empty).
	 */
	function wps_get_plan_purchasable_products( $plan_slug ) {
		$product_ids = wps_get_plan_products( $plan_slug );
		$purchasable = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}
			if ( ! $product->is_purchasable() ) {
				continue;
			}
			if ( ! $product->is_in_stock() ) {
				continue;
			}
			if ( 'publish' !== $product->get_status() ) {
				continue;
			}

			$purchasable[] = $product;
		}

		return $purchasable;
	}
}

if ( ! function_exists( 'wps_render_plan_purchase_cta' ) ) {
	/**
	 * Render an HTML "Get access" block for one or more plan slugs.
	 *
	 * Collects all purchasable products across the given plan(s) (de-duped by
	 * product ID), then renders a list with name, price, and an add-to-cart
	 * link for each. The final HTML is passed through the
	 * `wps_plan_purchase_cta_html` filter so themes and Pro can customise it.
	 *
	 * @since  2.0.0
	 * @param  string|string[] $plan_slugs One or more plan slugs.
	 * @return string HTML string (empty string when no purchasable products exist).
	 */
	function wps_render_plan_purchase_cta( $plan_slugs ) {
		$plan_slugs = (array) $plan_slugs;
		$seen       = array();
		$products   = array();

		foreach ( $plan_slugs as $slug ) {
			foreach ( wps_get_plan_purchasable_products( sanitize_key( $slug ) ) as $product ) {
				$pid = $product->get_id();
				if ( ! isset( $seen[ $pid ] ) ) {
					$seen[ $pid ] = true;
					$products[]   = $product;
				}
			}
		}

		if ( empty( $products ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="wps-plan-purchase-cta">
			<p class="wps-plan-purchase-cta__heading">
				<?php esc_html_e( 'Get access:', 'subscriptions-for-woocommerce' ); ?>
			</p>
			<ul class="wps-plan-purchase-cta__list">
				<?php foreach ( $products as $product ) : ?>
					<li class="wps-plan-purchase-cta__item">
						<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
							class="wps-plan-purchase-cta__link button">
							<?php
							echo esc_html( $product->get_name() );
							echo ' &mdash; ';
							echo wp_kses_post( wc_price( $product->get_price() ) );
							?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		$html = ob_get_clean();

		/**
		 * Filter the purchase CTA HTML before output.
		 *
		 * @since 2.0.0
		 *
		 * @param string       $html       The generated HTML.
		 * @param WC_Product[] $products   The purchasable products included.
		 * @param string[]     $plan_slugs The plan slugs that were requested.
		 */
		return apply_filters( 'wps_plan_purchase_cta_html', $html, $products, $plan_slugs );
	}
}

// ---------------------------------------------------------------------------
// Sanitization helper
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_sanitize_access_length' ) ) {
	/**
	 * Sanitize and normalise a plan access-length array.
	 *
	 * @since  2.0.0
	 * @param  mixed $raw Input value (should be an array).
	 * @return array Sanitised array with keys type, value, unit.
	 */
	function wps_sanitize_access_length( $raw ) {
		$defaults = array(
			'type'  => 'lifetime',
			'value' => 0,
			'unit'  => 'day',
		);

		if ( ! is_array( $raw ) ) {
			return $defaults;
		}

		$type  = isset( $raw['type'] ) && 'fixed' === $raw['type'] ? 'fixed' : 'lifetime';
		$value = isset( $raw['value'] ) ? absint( $raw['value'] ) : 0;
		$unit  = isset( $raw['unit'] ) && in_array( $raw['unit'], array( 'day', 'month', 'year' ), true )
			? $raw['unit']
			: 'day';

		return array(
			'type'  => $type,
			'value' => $value,
			'unit'  => $unit,
		);
	}
}

// ---------------------------------------------------------------------------
// Front-end card builder
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_build_membership_card_html' ) ) {
	/**
	 * Build the "MEMBERSHIP INCLUDED" card HTML for a set of plans.
	 *
	 * Used on single product pages for both subscription-grant and
	 * purchase-grant products. The returned string contains no unescaped
	 * user content — all values are escaped inline.
	 *
	 * @since  2.0.0
	 * @param  array  $plans        Array of plan arrays from wps_membership_plan_row().
	 * @param  string $product_type WC product type — affects the subtitle copy.
	 * @return string HTML string (may be empty when $plans is empty).
	 */
	function wps_build_membership_card_html( array $plans, $product_type = 'simple' ) {
		if ( empty( $plans ) ) {
			return '';
		}

		$is_subscription = ( 'variation' === $product_type || 'subscription' === $product_type );

		if ( $is_subscription ) {
			$subtitle = esc_html__(
				'Active subscription grants you the following membership:',
				'subscriptions-for-woocommerce'
			);
		} else {
			$subtitle = esc_html__(
				'Purchasing this product will grant you the following membership:',
				'subscriptions-for-woocommerce'
			);
		}

		$unit_singular = array(
			'day'   => __( 'Day', 'subscriptions-for-woocommerce' ),
			'month' => __( 'Month', 'subscriptions-for-woocommerce' ),
			'year'  => __( 'Year', 'subscriptions-for-woocommerce' ),
		);
		$unit_plural   = array(
			'day'   => __( 'Days', 'subscriptions-for-woocommerce' ),
			'month' => __( 'Months', 'subscriptions-for-woocommerce' ),
			'year'  => __( 'Years', 'subscriptions-for-woocommerce' ),
		);

		$html  = '<div class="wps-membership-included">';
		$html .= '<div class="wps-membership-included__header">';
		$html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"'
			. ' stroke="currentColor" stroke-width="2" stroke-linecap="round"'
			. ' stroke-linejoin="round" aria-hidden="true">'
			. '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77'
			. ' 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>'
			. '</svg>';
		$html .= '<span>' . esc_html__( 'MEMBERSHIP INCLUDED', 'subscriptions-for-woocommerce' ) . '</span>';
		$html .= '</div>';

		$html .= '<div class="wps-membership-included__body">';
		$html .= '<p class="wps-membership-included__subtitle">' . $subtitle . '</p>';

		foreach ( $plans as $plan ) {
			$color        = ! empty( $plan['color'] ) ? $plan['color'] : '#2e7d32';
			$al           = isset( $plan['access_length'] ) ? $plan['access_length'] : array();
			$al_type      = isset( $al['type'] ) ? $al['type'] : 'lifetime';
			$al_val       = isset( $al['value'] ) ? absint( $al['value'] ) : 0;
			$al_unit      = isset( $al['unit'] ) ? $al['unit'] : 'day';
			$grant_method = isset( $plan['grant_method'] ) ? $plan['grant_method'] : 'purchase';

			if ( 'subscription' === $grant_method ) {
				$badge = esc_html__( 'While Subscribed', 'subscriptions-for-woocommerce' );
			} elseif ( 'fixed' === $al_type && $al_val ) {
				$ul    = 1 === $al_val
					? ( isset( $unit_singular[ $al_unit ] ) ? $unit_singular[ $al_unit ] : $al_unit )
					: ( isset( $unit_plural[ $al_unit ] ) ? $unit_plural[ $al_unit ] : $al_unit );
				$badge = $al_val . ' ' . $ul . ' ' . esc_html__( 'Access', 'subscriptions-for-woocommerce' );
			} else {
				$badge = esc_html__( 'Lifetime Access', 'subscriptions-for-woocommerce' );
			}

			$desc = ! empty( $plan['description'] )
				? wp_strip_all_tags( $plan['description'] )
				: '';

			$html .= '<div class="wps-membership-included__plan"'
				. ' style="border-left-color:' . esc_attr( $color ) . ';">';
			$html .= '<div class="wps-membership-included__plan-main">';
			$html .= '<span class="wps-membership-included__dot"'
				. ' style="background:' . esc_attr( $color ) . ';"></span>';
			$html .= '<strong class="wps-membership-included__plan-name">'
				. esc_html( $plan['name'] ) . '</strong>';
			$html .= '<span class="wps-membership-included__badge">'
				. esc_html( $badge ) . '</span>';
			$html .= '</div>';
			if ( $desc ) {
				$html .= '<p class="wps-membership-included__desc">'
					. esc_html( $desc ) . '</p>';
			}
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}
