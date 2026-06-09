<?php
/**
 * Membership Layer — Access Rules CRUD + Resolver (Day 11)
 *
 * Functions for storing, retrieving, and resolving content-access rules.
 * All rules live in the `wps_access_rules` option (no per-post meta).
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

if ( ! defined( 'WPS_ACCESS_RULES_OPTION' ) ) {
	define( 'WPS_ACCESS_RULES_OPTION', 'wps_access_rules' );
}
if ( ! defined( 'WPS_ACCESS_RULES_INDEX_OPTION' ) ) {
	define( 'WPS_ACCESS_RULES_INDEX_OPTION', 'wps_access_rules_index' );
}
if ( ! defined( 'WPS_ACCESS_RULES_CACHE_GROUP' ) ) {
	define( 'WPS_ACCESS_RULES_CACHE_GROUP', 'wps_access_rules' );
}

/** Allowed target type values. */
define( 'WPS_ACCESS_RULE_TARGET_TYPES', array( 'post', 'page', 'product', 'post_type', 'taxonomy' ) );

/** Allowed restriction behavior values. */
define( 'WPS_ACCESS_RULE_BEHAVIORS', array( 'message', 'redirect' ) );

// ---------------------------------------------------------------------------
// Sanitization helper
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_sanitize_access_rule' ) ) {
	/**
	 * Sanitize and normalize a single access rule array.
	 *
	 * Returns a fully-populated rule array with every key present and all values
	 * sanitized. Callers must escape values before output.
	 *
	 * @since  2.0.0
	 * @param  array $raw Raw rule data.
	 * @return array Sanitized rule array.
	 */
	function wps_sanitize_access_rule( array $raw ) {
		$raw_type    = isset( $raw['target_type'] ) ? $raw['target_type'] : '';
		$target_type = in_array( $raw_type, WPS_ACCESS_RULE_TARGET_TYPES, true ) ? $raw_type : 'post_type';

		$behavior = isset( $raw['behavior'] ) && in_array( $raw['behavior'], WPS_ACCESS_RULE_BEHAVIORS, true )
			? $raw['behavior']
			: 'message';

		$plans = array();
		if ( isset( $raw['plans'] ) && is_array( $raw['plans'] ) ) {
			$plans = array_values( array_filter( array_map( 'sanitize_key', $raw['plans'] ) ) );
		}
		if ( empty( $plans ) ) {
			$plans = array( 'any' );
		}

		$wps_flag = function ( $key, $raw ) {
			return ( isset( $raw[ $key ] ) && '1' === (string) $raw[ $key ] ) ? '1' : '0';
		};

		return array(
			'id'                           => isset( $raw['id'] ) ? sanitize_key( $raw['id'] ) : '',
			'target_type'                  => $target_type,
			'post_type'                    => isset( $raw['post_type'] ) ? sanitize_key( $raw['post_type'] ) : '',
			'object_ids'                   => isset( $raw['object_ids'] ) && is_array( $raw['object_ids'] )
				? array_values( array_filter( array_map( 'absint', $raw['object_ids'] ) ) )
				: array(),
			'taxonomy'                     => isset( $raw['taxonomy'] ) ? sanitize_key( $raw['taxonomy'] ) : '',
			'term_ids'                     => isset( $raw['term_ids'] ) && is_array( $raw['term_ids'] )
				? array_values( array_filter( array_map( 'absint', $raw['term_ids'] ) ) )
				: array(),
			'plans'                        => $plans,
			'behavior'                     => $behavior,
			'message'                      => isset( $raw['message'] ) ? wp_kses_post( $raw['message'] ) : '',
			'redirect_url'                 => isset( $raw['redirect_url'] ) ? esc_url_raw( $raw['redirect_url'] ) : '',
			'priority'                     => isset( $raw['priority'] ) ? absint( $raw['priority'] ) : 10,
			'enabled'                      => ( isset( $raw['enabled'] ) && '0' === (string) $raw['enabled'] ) ? '0' : '1',
			'restrict_comments'            => $wps_flag( 'restrict_comments', $raw ),
			'include_archive'              => $wps_flag( 'include_archive', $raw ),
			'show_cta'                     => $wps_flag( 'show_cta', $raw ),
			'restrict_product_description' => $wps_flag( 'restrict_product_description', $raw ),
		);
	}
}

// ---------------------------------------------------------------------------
// CRUD — Read
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_get_access_rules' ) ) {
	/**
	 * Return all configured access rules.
	 *
	 * Rules are read from the `wps_access_rules` option and cached in the
	 * object cache. The cache is busted on every write via wps_save_access_rules().
	 *
	 * @since  2.0.0
	 * @return array Ordered array of rule arrays (may be empty).
	 */
	function wps_get_access_rules() {
		$cache_key = 'all_rules';
		$cached    = wp_cache_get( $cache_key, WPS_ACCESS_RULES_CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$rules = get_option( WPS_ACCESS_RULES_OPTION, array() );
		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		wp_cache_set( $cache_key, $rules, WPS_ACCESS_RULES_CACHE_GROUP );

		return $rules;
	}
}

// ---------------------------------------------------------------------------
// CRUD — Write
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_save_access_rules' ) ) {
	/**
	 * Overwrite the entire rules array.
	 *
	 * Sanitizes every rule, persists to the option, rebuilds the index, and
	 * busts the object cache. Called by both wps_add_access_rule() and the
	 * admin save handler.
	 *
	 * @since 2.0.0
	 * @param array $rules Array of rule arrays.
	 */
	function wps_save_access_rules( array $rules ) {
		$sanitized = array();
		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			$sanitized[] = wps_sanitize_access_rule( $rule );
		}

		// Not autoloaded — the full rules array is only read on saves and admin pages.
		update_option( WPS_ACCESS_RULES_OPTION, $sanitized, false );

		wp_cache_delete( 'all_rules', WPS_ACCESS_RULES_CACHE_GROUP );

		wps_rebuild_access_rules_index();
	}
}

if ( ! function_exists( 'wps_add_access_rule' ) ) {
	/**
	 * Append a new rule to the rule set.
	 *
	 * Generates a unique string ID for the rule, sanitizes it, and persists.
	 * The `id` key in $rule is ignored; a new one is always generated.
	 *
	 * @since  2.0.0
	 * @param  array $rule Rule data. See wps_sanitize_access_rule() for accepted keys.
	 * @return string The auto-generated rule ID.
	 */
	function wps_add_access_rule( array $rule ) {
		$rules = wps_get_access_rules();

		$id         = wps_generate_access_rule_id( $rules );
		$rule['id'] = $id;
		$sanitized  = wps_sanitize_access_rule( $rule );
		$rules[]    = $sanitized;

		update_option( WPS_ACCESS_RULES_OPTION, $rules, false );
		wp_cache_delete( 'all_rules', WPS_ACCESS_RULES_CACHE_GROUP );
		wps_rebuild_access_rules_index();

		/**
		 * Fires after a new access rule is added.
		 *
		 * @since 2.0.0
		 *
		 * @param string $id        The new rule ID.
		 * @param array  $sanitized The sanitized rule array.
		 */
		do_action( 'wps_access_rule_added', $id, $sanitized );

		return $id;
	}
}

if ( ! function_exists( 'wps_delete_access_rule' ) ) {
	/**
	 * Remove a rule by its ID.
	 *
	 * @since  2.0.0
	 * @param  string $id Rule ID.
	 * @return bool True if the rule was found and removed, false if not found.
	 */
	function wps_delete_access_rule( $id ) {
		$id    = sanitize_key( $id );
		$rules = wps_get_access_rules();

		$filtered = array_values(
			array_filter(
				$rules,
				function ( $rule ) use ( $id ) {
					return isset( $rule['id'] ) && $rule['id'] !== $id;
				}
			)
		);

		if ( count( $filtered ) === count( $rules ) ) {
			return false;
		}

		update_option( WPS_ACCESS_RULES_OPTION, $filtered, false );
		wp_cache_delete( 'all_rules', WPS_ACCESS_RULES_CACHE_GROUP );
		wps_rebuild_access_rules_index();

		/**
		 * Fires after an access rule is deleted.
		 *
		 * @since 2.0.0
		 *
		 * @param string $id The deleted rule ID.
		 */
		do_action( 'wps_access_rule_deleted', $id );

		return true;
	}
}

// ---------------------------------------------------------------------------
// Index builder
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_rebuild_access_rules_index' ) ) {
	/**
	 * Rebuild the `wps_access_rules_index` option from the current rule set.
	 *
	 * Called automatically on every rule save. The index enables O(1)-ish
	 * lookups during enforcement without scanning every rule per request.
	 *
	 * Delegates to WPS_Access_Rules_Engine::rebuild_index().
	 *
	 * @since 2.0.0
	 */
	function wps_rebuild_access_rules_index() {
		$engine = new WPS_Access_Rules_Engine();
		$engine->rebuild_index();
	}
}

// ---------------------------------------------------------------------------
// Resolver — read path
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_get_rules_for_object' ) ) {
	/**
	 * Return all access rules that apply to a given post object.
	 *
	 * Checks the index in three passes: post ID → taxonomy terms → post type.
	 * Results are de-duplicated and sorted by priority (ascending; lower number
	 * = evaluated first / higher priority).
	 *
	 * Delegates to WPS_Access_Rules_Engine::get_rules_for_object().
	 *
	 * @since  2.0.0
	 * @param  WP_Post $post The post to resolve rules for.
	 * @return array Ordered array of matching rule arrays (may be empty).
	 */
	function wps_get_rules_for_object( WP_Post $post ) {
		$engine = new WPS_Access_Rules_Engine();
		return $engine->get_rules_for_object( $post );
	}
}

if ( ! function_exists( 'wps_object_is_restricted' ) ) {
	/**
	 * Check whether a post is restricted for a given user.
	 *
	 * Resolves access rules for the post, then tests the user's memberships
	 * against each rule's required plans in priority order.
	 *
	 * Short-circuits:
	 *  - No rules → null (open access).
	 *  - User has `manage_options` → null (always granted to admins).
	 *
	 * Results are cached per request (per post + user pair) in the object cache.
	 *
	 * @since  2.0.0
	 * @param  WP_Post $post    Post to check.
	 * @param  int     $user_id WordPress user ID. 0 = not logged in.
	 * @return array|null The first failing rule array, or null when access is granted.
	 */
	function wps_object_is_restricted( WP_Post $post, $user_id = 0 ) {
		$user_id   = absint( $user_id );
		$cache_key = 'restriction_' . $post->ID . '_' . $user_id;

		$cached = wp_cache_get( $cache_key, WPS_ACCESS_RULES_CACHE_GROUP );
		if ( false !== $cached ) {
			return 'granted' === $cached ? null : $cached;
		}

		// Admins always have full access — check before loading rules.
		if ( $user_id > 0 && user_can( $user_id, 'manage_options' ) ) {
			wp_cache_set( $cache_key, 'granted', WPS_ACCESS_RULES_CACHE_GROUP );
			return null;
		}

		$rules = wps_get_rules_for_object( $post );

		if ( empty( $rules ) ) {
			wp_cache_set( $cache_key, 'granted', WPS_ACCESS_RULES_CACHE_GROUP );
			return null;
		}

		foreach ( $rules as $rule ) {
			$plans = isset( $rule['plans'] ) ? (array) $rule['plans'] : array( 'any' );

			$granted = $user_id > 0 && function_exists( 'wps_user_has_plan' )
				? wps_user_has_plan( $user_id, $plans )
				: false;

			if ( ! $granted ) {
				wp_cache_set( $cache_key, $rule, WPS_ACCESS_RULES_CACHE_GROUP );
				return $rule;
			}
		}

		wp_cache_set( $cache_key, 'granted', WPS_ACCESS_RULES_CACHE_GROUP );
		return null;
	}
}

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_generate_access_rule_id' ) ) {
	/**
	 * Generate a unique rule ID that does not collide with any existing rule.
	 *
	 * @since  2.0.0
	 * @param  array $existing_rules The current rules array (used for collision check).
	 * @return string Unique rule ID string (e.g. 'r1a2b3').
	 */
	function wps_generate_access_rule_id( array $existing_rules = array() ) {
		$existing_ids = array_column( $existing_rules, 'id' );

		do {
			// wp_generate_password produces A-Za-z0-9; strtolower keeps the ID
			// consistent with the sanitize_key() applied on save.
			$id = 'r' . strtolower( substr( wp_generate_password( 8, false, false ), 0, 6 ) );
		} while ( in_array( $id, $existing_ids, true ) );

		return $id;
	}
}
