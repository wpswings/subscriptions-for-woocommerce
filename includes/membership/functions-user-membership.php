<?php
/**
 * Membership Layer — User Membership CRUD + Access API (Day 03)
 *
 * Every read/write against a user's membership data goes through these
 * functions. No other code should touch `wps_memberships` user meta directly.
 *
 * Storage — two user-meta keys written together on every mutation:
 *   wps_memberships          serialised array, hot read path, object-cached.
 *   wps_member_of_{slug}     flat status string, queryable by WP_User_Query.
 *
 * A third pointer key is maintained for subscription-sourced memberships:
 *   wps_sub_membership_{sub_id}   plan slug — enables O(1) lookup by sub ID.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

if ( ! defined( 'WPS_MEMBERSHIP_CACHE_GROUP' ) ) {
	define( 'WPS_MEMBERSHIP_CACHE_GROUP', 'wps_membership' );
}

/** Allowed membership status values. */
define( 'WPS_MEMBERSHIP_STATUSES', array( 'active', 'on-hold', 'cancelled', 'expired', 'paused' ) );

/** Source privilege order — higher index wins when collapsing duplicates. */
define( 'WPS_MEMBERSHIP_SOURCE_PRIORITY', array( 'manual' => 1, 'order' => 2, 'subscription' => 3 ) );

// ---------------------------------------------------------------------------
// Cache helpers
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_get_user_memberships_cache_key' ) ) {
	/**
	 * Return the object-cache key for a user's memberships array.
	 *
	 * @since  2.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return string Cache key string.
	 */
	function wps_get_user_memberships_cache_key( $user_id ) {
		return 'user_memberships_' . absint( $user_id );
	}
}

if ( ! function_exists( 'wps_invalidate_membership_cache' ) ) {
	/**
	 * Bust the object-cache entry for a user's memberships.
	 *
	 * Must be called after every write to `wps_memberships` user meta.
	 *
	 * @since 2.0.0
	 * @param int $user_id WordPress user ID.
	 */
	function wps_invalidate_membership_cache( $user_id ) {
		wp_cache_delete( wps_get_user_memberships_cache_key( absint( $user_id ) ), WPS_MEMBERSHIP_CACHE_GROUP );
	}
}

// ---------------------------------------------------------------------------
// Internal read / write helpers
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_read_user_memberships_meta' ) ) {
	/**
	 * Read the raw `wps_memberships` array for a user, from cache or meta.
	 *
	 * Always returns an array (empty when the user has no memberships).
	 *
	 * @since  2.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return array Keyed by plan slug.
	 */
	function wps_read_user_memberships_meta( $user_id ) {
		$user_id  = absint( $user_id );
		$cache_key = wps_get_user_memberships_cache_key( $user_id );
		$cached   = wp_cache_get( $cache_key, WPS_MEMBERSHIP_CACHE_GROUP );

		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}

		$meta = get_user_meta( $user_id, 'wps_memberships', true );
		$data = is_array( $meta ) ? $meta : array();

		wp_cache_set( $cache_key, $data, WPS_MEMBERSHIP_CACHE_GROUP );

		return $data;
	}
}

if ( ! function_exists( 'wps_write_user_memberships_meta' ) ) {
	/**
	 * Persist the memberships array and keep the flat queryable keys in sync.
	 *
	 * Writes `wps_memberships` (serialised) and, for every entry in the array,
	 * updates `wps_member_of_{slug}` to the current status. Busts the cache.
	 *
	 * @since 2.0.0
	 * @param int   $user_id    WordPress user ID.
	 * @param array $memberships Full memberships array keyed by plan slug.
	 */
	function wps_write_user_memberships_meta( $user_id, array $memberships ) {
		$user_id = absint( $user_id );

		update_user_meta( $user_id, 'wps_memberships', $memberships );

		foreach ( $memberships as $slug => $entry ) {
			$slug   = sanitize_key( $slug );
			$status = isset( $entry['status'] ) ? $entry['status'] : 'cancelled';
			update_user_meta( $user_id, 'wps_member_of_' . $slug, $status );
		}

		wps_invalidate_membership_cache( $user_id );
	}
}

if ( ! function_exists( 'wps_sanitize_membership_row' ) ) {
	/**
	 * Sanitise and normalise a raw membership entry array.
	 *
	 * @since  2.0.0
	 * @param  array $raw Raw input array.
	 * @return array Sanitised membership row.
	 */
	function wps_sanitize_membership_row( array $raw ) {
		$allowed_statuses = WPS_MEMBERSHIP_STATUSES;
		$allowed_sources  = array_keys( WPS_MEMBERSHIP_SOURCE_PRIORITY );

		$status = isset( $raw['status'] ) && in_array( $raw['status'], $allowed_statuses, true )
			? $raw['status']
			: 'active';

		$source = isset( $raw['source'] ) && in_array( $raw['source'], $allowed_sources, true )
			? $raw['source']
			: 'manual';

		$expiry = isset( $raw['expiry_date'] ) && is_numeric( $raw['expiry_date'] )
			? absint( $raw['expiry_date'] )
			: null;

		return array(
			'plan_slug'       => sanitize_key( isset( $raw['plan_slug'] ) ? $raw['plan_slug'] : '' ),
			'status'          => $status,
			'source'          => $source,
			'subscription_id' => isset( $raw['subscription_id'] ) ? absint( $raw['subscription_id'] ) : null,
			'order_id'        => isset( $raw['order_id'] ) ? absint( $raw['order_id'] ) : null,
			'start_date'      => isset( $raw['start_date'] ) ? absint( $raw['start_date'] ) : time(),
			'expiry_date'     => $expiry,
			'updated_at'      => time(),
		);
	}
}

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_create_user_membership' ) ) {
	/**
	 * Grant a user a membership in a plan.
	 *
	 * If a membership for the same plan already exists the entries are merged:
	 * the longer (or lifetime) expiry is kept and the higher-privilege source
	 * wins (subscription > order > manual). This ensures a subscription + a
	 * one-time purchase for the same plan never creates duplicate rows.
	 *
	 * @since 2.0.0
	 *
	 * @param int   $user_id WordPress user ID.
	 * @param array $args {
	 *   @type string   $plan_slug       Required. Plan slug.
	 *   @type string   $status          Optional. Default 'active'.
	 *   @type string   $source          Optional. 'subscription'|'order'|'manual'. Default 'manual'.
	 *   @type int|null $subscription_id Optional. Subscription post ID when source = 'subscription'.
	 *   @type int|null $order_id        Optional. Order ID when source = 'order'.
	 *   @type int      $start_date      Optional. Unix timestamp. Defaults to now.
	 *   @type int|null $expiry_date     Optional. Unix timestamp or null for lifetime.
	 * }
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	function wps_create_user_membership( $user_id, array $args ) {
		$user_id = absint( $user_id );

		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'wps_membership_invalid_user',
				__( 'Invalid user ID.', 'subscriptions-for-woocommerce' )
			);
		}

		$plan_slug = sanitize_key( isset( $args['plan_slug'] ) ? $args['plan_slug'] : '' );
		if ( empty( $plan_slug ) ) {
			return new WP_Error(
				'wps_membership_no_plan',
				__( 'Plan slug is required.', 'subscriptions-for-woocommerce' )
			);
		}

		$new_row     = wps_sanitize_membership_row( array_merge( array( 'plan_slug' => $plan_slug ), $args ) );
		$memberships = wps_read_user_memberships_meta( $user_id );

		if ( isset( $memberships[ $plan_slug ] ) ) {
			$existing = $memberships[ $plan_slug ];
			$new_row  = wps_merge_membership_rows( $existing, $new_row );
		}

		$memberships[ $plan_slug ] = $new_row;
		wps_write_user_memberships_meta( $user_id, $memberships );

		// Store subscription pointer for O(1) lookup.
		if ( 'subscription' === $new_row['source'] && $new_row['subscription_id'] ) {
			update_user_meta(
				$user_id,
				'wps_sub_membership_' . $new_row['subscription_id'],
				$plan_slug
			);
		}

		/**
		 * Fires after a user membership is created or merged.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $user_id   WordPress user ID.
		 * @param string $plan_slug Plan slug.
		 * @param array  $row       The saved membership row.
		 */
		do_action( 'wps_membership_created', $user_id, $plan_slug, $new_row );

		return true;
	}
}

if ( ! function_exists( 'wps_merge_membership_rows' ) ) {
	/**
	 * Merge two membership rows for the same plan, keeping the best of each.
	 *
	 * Rules:
	 *  - Source: keep the higher-privilege one (subscription > order > manual).
	 *  - Expiry: keep the longer one (null = lifetime = wins over any timestamp).
	 *  - Status: keep the incoming row's status (the caller controls the intent).
	 *  - IDs: keep both subscription_id and order_id when both are present.
	 *
	 * @since  2.0.0
	 * @param  array $existing The currently stored membership row.
	 * @param  array $incoming The new row being written.
	 * @return array The merged row.
	 */
	function wps_merge_membership_rows( array $existing, array $incoming ) {
		$priority      = WPS_MEMBERSHIP_SOURCE_PRIORITY;
		$existing_prio = isset( $priority[ $existing['source'] ] ) ? $priority[ $existing['source'] ] : 0;
		$incoming_prio = isset( $priority[ $incoming['source'] ] ) ? $priority[ $incoming['source'] ] : 0;

		// Higher-privilege source wins; keep its IDs.
		if ( $incoming_prio >= $existing_prio ) {
			$merged_source          = $incoming['source'];
			$merged_subscription_id = $incoming['subscription_id'];
			$merged_order_id        = $incoming['order_id']
				? $incoming['order_id']
				: $existing['order_id'];
		} else {
			$merged_source          = $existing['source'];
			$merged_subscription_id = $existing['subscription_id'];
			$merged_order_id        = $incoming['order_id']
				? $incoming['order_id']
				: $existing['order_id'];
		}

		// null expiry (lifetime) always wins; otherwise keep the later timestamp.
		if ( null === $existing['expiry_date'] || null === $incoming['expiry_date'] ) {
			$merged_expiry = null;
		} else {
			$merged_expiry = max( $existing['expiry_date'], $incoming['expiry_date'] );
		}

		return array(
			'plan_slug'       => $existing['plan_slug'],
			'status'          => $incoming['status'],
			'source'          => $merged_source,
			'subscription_id' => $merged_subscription_id,
			'order_id'        => $merged_order_id,
			'start_date'      => $existing['start_date'],
			'expiry_date'     => $merged_expiry,
			'updated_at'      => time(),
		);
	}
}

// ---------------------------------------------------------------------------
// Read
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_get_membership' ) ) {
	/**
	 * Return a single membership entry for a user + plan combination.
	 *
	 * @since  2.0.0
	 * @param  int    $user_id   WordPress user ID.
	 * @param  string $plan_slug Plan slug.
	 * @return array|null Membership row, or null if the user is not a member.
	 */
	function wps_get_membership( $user_id, $plan_slug ) {
		$user_id   = absint( $user_id );
		$plan_slug = sanitize_key( $plan_slug );

		if ( ! $user_id || empty( $plan_slug ) ) {
			return null;
		}

		$memberships = wps_read_user_memberships_meta( $user_id );

		return isset( $memberships[ $plan_slug ] ) ? $memberships[ $plan_slug ] : null;
	}
}

if ( ! function_exists( 'wps_get_membership_by_subscription' ) ) {
	/**
	 * Return a membership entry located by subscription ID.
	 *
	 * Uses the `wps_sub_membership_{sub_id}` pointer stored on the subscriber
	 * so the lookup is O(1) — no full meta scan.
	 *
	 * @since  2.0.0
	 * @param  int $subscription_id Subscription post / order ID.
	 * @return array|null Array with keys 'user_id', 'plan_slug', and all
	 *                    membership row fields; or null if not found.
	 */
	function wps_get_membership_by_subscription( $subscription_id ) {
		$subscription_id = absint( $subscription_id );
		if ( ! $subscription_id ) {
			return null;
		}

		$subscription = wc_get_order( $subscription_id );
		if ( ! $subscription ) {
			return null;
		}

		$user_id = (int) wps_sfw_get_meta_data( $subscription_id, 'wps_customer_id', true );
		if ( ! $user_id ) {
			return null;
		}

		$plan_slug = get_user_meta( $user_id, 'wps_sub_membership_' . $subscription_id, true );
		if ( empty( $plan_slug ) ) {
			return null;
		}

		$row = wps_get_membership( $user_id, $plan_slug );
		if ( ! $row ) {
			return null;
		}

		return array_merge( $row, array( 'user_id' => $user_id ) );
	}
}

if ( ! function_exists( 'wps_get_user_memberships' ) ) {
	/**
	 * Return all memberships for a user, optionally filtered by status.
	 *
	 * @since  2.0.0
	 * @param  int    $user_id WordPress user ID.
	 * @param  string $status  'active', 'on-hold', 'cancelled', 'expired',
	 *                         'paused', or 'all' (default).
	 * @return array Array of membership rows (may be empty).
	 */
	function wps_get_user_memberships( $user_id, $status = 'all' ) {
		$user_id     = absint( $user_id );
		$memberships = wps_read_user_memberships_meta( $user_id );

		if ( empty( $memberships ) ) {
			return array();
		}

		if ( 'all' === $status ) {
			return array_values( $memberships );
		}

		$filtered = array();
		foreach ( $memberships as $row ) {
			if ( isset( $row['status'] ) && $status === $row['status'] ) {
				$filtered[] = $row;
			}
		}

		return $filtered;
	}
}

// ---------------------------------------------------------------------------
// Update
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_update_membership_status' ) ) {
	/**
	 * Change the status of a user's membership for a given plan.
	 *
	 * Always fires `wps_membership_status_changed` — even when the new status
	 * equals the old one — so listeners can stay idempotent.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $user_id    WordPress user ID.
	 * @param string $plan_slug  Plan slug.
	 * @param string $new_status One of: active, on-hold, cancelled, expired, paused.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	function wps_update_membership_status( $user_id, $plan_slug, $new_status ) {
		$user_id    = absint( $user_id );
		$plan_slug  = sanitize_key( $plan_slug );
		$new_status = sanitize_key( $new_status );

		if ( ! in_array( $new_status, WPS_MEMBERSHIP_STATUSES, true ) ) {
			return new WP_Error(
				'wps_membership_invalid_status',
				/* translators: %s: status string */
				sprintf( __( 'Invalid membership status: %s', 'subscriptions-for-woocommerce' ), $new_status )
			);
		}

		$memberships = wps_read_user_memberships_meta( $user_id );

		if ( ! isset( $memberships[ $plan_slug ] ) ) {
			return new WP_Error(
				'wps_membership_not_found',
				__( 'Membership not found.', 'subscriptions-for-woocommerce' )
			);
		}

		$old_status                              = $memberships[ $plan_slug ]['status'];
		$memberships[ $plan_slug ]['status']     = $new_status;
		$memberships[ $plan_slug ]['updated_at'] = time();

		wps_write_user_memberships_meta( $user_id, $memberships );

		/**
		 * Fires after a membership status changes.
		 *
		 * Also invalidates the access-check cache for this user.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $user_id    WordPress user ID.
		 * @param string $plan_slug  Plan slug.
		 * @param string $new_status The status just written.
		 * @param string $old_status The status before the change.
		 */
		do_action( 'wps_membership_status_changed', $user_id, $plan_slug, $new_status, $old_status );

		return true;
	}
}

if ( ! function_exists( 'wps_extend_membership_expiry' ) ) {
	/**
	 * Update the expiry timestamp of an existing membership.
	 *
	 * Pass null for $new_expiry to grant a lifetime membership.
	 * Pass a Unix timestamp to set a fixed end date.
	 *
	 * @since 2.0.0
	 *
	 * @param int      $user_id    WordPress user ID.
	 * @param string   $plan_slug  Plan slug.
	 * @param int|null $new_expiry Unix timestamp or null for lifetime.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	function wps_extend_membership_expiry( $user_id, $plan_slug, $new_expiry ) {
		$user_id   = absint( $user_id );
		$plan_slug = sanitize_key( $plan_slug );

		if ( null !== $new_expiry ) {
			$new_expiry = absint( $new_expiry );
		}

		$memberships = wps_read_user_memberships_meta( $user_id );

		if ( ! isset( $memberships[ $plan_slug ] ) ) {
			return new WP_Error(
				'wps_membership_not_found',
				__( 'Membership not found.', 'subscriptions-for-woocommerce' )
			);
		}

		$memberships[ $plan_slug ]['expiry_date'] = $new_expiry;
		$memberships[ $plan_slug ]['updated_at']  = time();

		wps_write_user_memberships_meta( $user_id, $memberships );

		/**
		 * Fires after a membership expiry date is updated.
		 *
		 * @since 2.0.0
		 *
		 * @param int      $user_id    WordPress user ID.
		 * @param string   $plan_slug  Plan slug.
		 * @param int|null $new_expiry The new expiry timestamp, or null for lifetime.
		 */
		do_action( 'wps_membership_expiry_updated', $user_id, $plan_slug, $new_expiry );

		return true;
	}
}

// ---------------------------------------------------------------------------
// Bulk helpers (used by plan deletion and admin tools)
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_cancel_all_memberships_for_plan' ) ) {
	/**
	 * Cancel every user's active membership for a given plan.
	 *
	 * Called by `wps_delete_plan()` before the plan CPT post is deleted.
	 * Uses the flat `wps_member_of_{slug}` key via WP_User_Query so only
	 * users with a recorded membership are touched.
	 *
	 * @since 2.0.0
	 * @param string $plan_slug Plan slug.
	 */
	function wps_cancel_all_memberships_for_plan( $plan_slug ) {
		$plan_slug = sanitize_key( $plan_slug );
		if ( empty( $plan_slug ) ) {
			return;
		}

		$users = new WP_User_Query(
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'   => 'wps_member_of_' . $plan_slug,
				'fields'     => 'ID',
				'count_total' => false,
				'number'     => -1,
			)
		);

		foreach ( $users->get_results() as $user_id ) {
			wps_update_membership_status( absint( $user_id ), $plan_slug, 'cancelled' );
		}
	}
}

// ---------------------------------------------------------------------------
// Cache invalidation hook
// ---------------------------------------------------------------------------

add_action( 'wps_membership_status_changed', 'wps_on_membership_status_changed_bust_cache', 10, 4 );

if ( ! function_exists( 'wps_on_membership_status_changed_bust_cache' ) ) {
	/**
	 * Bust the membership object-cache when a status changes.
	 *
	 * Hooked to `wps_membership_status_changed` so the access-check cache
	 * never serves a stale result after a grant or revocation.
	 *
	 * @since 2.0.0
	 * @param int    $user_id   WordPress user ID.
	 * @param string $plan_slug Plan slug (unused here, required by hook signature).
	 * @param string $new_status New membership status (unused here).
	 * @param string $old_status Previous membership status (unused here).
	 */
	function wps_on_membership_status_changed_bust_cache( $user_id, $plan_slug, $new_status, $old_status ) {
		wps_invalidate_membership_cache( $user_id );
	}
}

// ---------------------------------------------------------------------------
// Access API
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_user_has_plan' ) ) {
	/**
	 * Check whether a user holds an active, non-expired membership in any of
	 * the given plans.
	 *
	 * Reads `wps_memberships` once per user per page (via the object cache) so
	 * multiple calls on the same request cost a single meta lookup at most.
	 *
	 * Pass the string 'any' as $plan_slugs to return true when the user has at
	 * least one active membership in any plan.
	 *
	 * @since  2.0.0
	 * @param  int              $user_id    WordPress user ID.
	 * @param  string|string[]  $plan_slugs Plan slug, array of slugs, or 'any'.
	 * @return bool True when the user has an active, unexpired membership.
	 */
	function wps_user_has_plan( $user_id, $plan_slugs ) {
		$user_id     = absint( $user_id );
		$memberships = wps_read_user_memberships_meta( $user_id );

		if ( empty( $memberships ) ) {
			return false;
		}

		$now = time();

		// 'any' — true when at least one plan is active and unexpired.
		if ( 'any' === $plan_slugs ) {
			foreach ( $memberships as $row ) {
				if ( wps_membership_row_is_active( $row, $now ) ) {
					return true;
				}
			}
			return false;
		}

		foreach ( (array) $plan_slugs as $slug ) {
			$slug = sanitize_key( $slug );
			if ( ! isset( $memberships[ $slug ] ) ) {
				continue;
			}
			if ( wps_membership_row_is_active( $memberships[ $slug ], $now ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'wps_membership_row_is_active' ) ) {
	/**
	 * Return true when a membership row is active and not expired.
	 *
	 * @since  2.0.0
	 * @param  array $row Single membership entry from `wps_memberships`.
	 * @param  int   $now Current Unix timestamp to compare expiry against.
	 * @return bool
	 */
	function wps_membership_row_is_active( array $row, $now ) {
		if ( ! isset( $row['status'] ) || 'active' !== $row['status'] ) {
			return false;
		}
		if ( null !== $row['expiry_date'] && absint( $row['expiry_date'] ) <= $now ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'wps_current_user_has_plan' ) ) {
	/**
	 * Check whether the currently logged-in user holds an active membership.
	 *
	 * Returns false for non-logged-in visitors.
	 *
	 * @since  2.0.0
	 * @param  string|string[] $plan_slugs Plan slug, array of slugs, or 'any'.
	 * @return bool
	 */
	function wps_current_user_has_plan( $plan_slugs ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		return wps_user_has_plan( $user_id, $plan_slugs );
	}
}

if ( ! function_exists( 'wps_user_is_member' ) ) {
	/**
	 * Return true when a user has at least one active membership in any plan.
	 *
	 * Convenience alias for `wps_user_has_plan( $user_id, 'any' )`.
	 *
	 * @since  2.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return bool
	 */
	function wps_user_is_member( $user_id ) {
		return wps_user_has_plan( absint( $user_id ), 'any' );
	}
}

// ---------------------------------------------------------------------------
// Delete
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_remove_user_membership' ) ) {
	/**
	 * Permanently delete a membership record for a user.
	 *
	 * Removes the entry from `wps_memberships`, deletes the flat queryable key
	 * `wps_member_of_{slug}`, and cleans up the subscription pointer when set.
	 * This is a hard delete — use `wps_update_membership_status()` to cancel
	 * without removing the record.
	 *
	 * @since 2.0.0
	 *
	 * @param  int    $user_id   WordPress user ID.
	 * @param  string $plan_slug Plan slug.
	 * @return bool True when a record was found and removed, false otherwise.
	 */
	function wps_remove_user_membership( $user_id, $plan_slug ) {
		$user_id   = absint( $user_id );
		$plan_slug = sanitize_key( $plan_slug );

		if ( ! $user_id || empty( $plan_slug ) ) {
			return false;
		}

		$memberships = wps_read_user_memberships_meta( $user_id );

		if ( ! isset( $memberships[ $plan_slug ] ) ) {
			return false;
		}

		$removed_row = $memberships[ $plan_slug ];
		unset( $memberships[ $plan_slug ] );

		// Persist the array (also re-writes every remaining flat key).
		wps_write_user_memberships_meta( $user_id, $memberships );

		// The flat key for the removed plan is no longer in the array — delete it.
		delete_user_meta( $user_id, 'wps_member_of_' . $plan_slug );

		// Clean up the subscription pointer when present.
		if ( ! empty( $removed_row['subscription_id'] ) ) {
			delete_user_meta( $user_id, 'wps_sub_membership_' . absint( $removed_row['subscription_id'] ) );
		}

		/**
		 * Fires after a membership record is permanently removed.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $user_id   WordPress user ID.
		 * @param string $plan_slug Plan slug that was removed.
		 * @param array  $row       The membership row that was deleted.
		 */
		do_action( 'wps_membership_removed', $user_id, $plan_slug, $removed_row );

		return true;
	}
}
