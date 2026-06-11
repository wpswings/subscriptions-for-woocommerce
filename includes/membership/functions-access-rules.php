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

/**
 * Allowed rule kinds.
 *
 *  'content' — gates posts, pages, whole post types, or taxonomy terms. Renders
 *              through the_content / template_redirect / template_include and
 *              supports the message / redirect / template behaviors.
 *  'product' — gates WooCommerce products (or product categories). Renders only
 *              through purchasability gating + a product-page members-only
 *              notice; it never touches the_content. This keeps the product and
 *              content render paths fully separate so a product rule can't be
 *              used for content restriction and vice-versa.
 */
define( 'WPS_ACCESS_RULE_KINDS', array( 'content', 'product' ) );

/** Target types valid for a 'content' rule (products are owned by 'product' rules). */
define( 'WPS_ACCESS_RULE_CONTENT_TARGETS', array( 'post', 'page', 'post_type', 'taxonomy' ) );

/**
 * Target types valid for a 'product' rule.
 *
 *  'product'   — specific product IDs.
 *  'taxonomy'  — product category / tag terms.
 *  'post_type' — every product (post_type is forced to 'product').
 */
define( 'WPS_ACCESS_RULE_PRODUCT_TARGETS', array( 'product', 'taxonomy', 'post_type' ) );

/**
 * Allowed restriction behavior values.
 *
 *  'message'  — replace the content with the restriction notice (Free).
 *  'redirect' — send non-members to a configured URL (Free).
 *  'template' — render a dedicated full-page template with an optional teaser
 *               then the restriction notice (Pro — Day 18).
 *
 * 'template' is persisted by the Free plugin regardless of whether Pro is
 * active; only the Pro plugin's enforcement layer (`template_include`) acts on
 * it. When Pro is inactive a 'template' rule falls back to message behavior in
 * the Free enforcer (which treats any non-'redirect' value as 'message').
 */
define( 'WPS_ACCESS_RULE_BEHAVIORS', array( 'message', 'redirect', 'template' ) );

/**
 * Allowed teaser modes for the 'template' behavior (Pro — Day 18).
 *
 *  'none'  — no teaser; show the restriction notice only.
 *  'words' — show the first N words of the content (wp_trim_words).
 *
 * Persisted by the Free plugin; only the Pro plugin's template enforcement
 * acts on these values.
 */
define( 'WPS_ACCESS_RULE_TEASER_MODES', array( 'none', 'words' ) );

/**
 * Allowed drip/scheduled-access modes (Pro — Day 16).
 *
 *  'none' — no scheduling; the rule grants immediately to plan holders.
 *  'days' — content unlocks N days after the user's membership start.
 *  'date' — content unlocks on a fixed calendar date for everyone.
 *
 * The values are persisted by the Free plugin regardless of whether Pro is
 * active; only the Pro plugin's enforcement layer acts on them.
 */
define( 'WPS_ACCESS_RULE_DRIP_MODES', array( 'none', 'days', 'date' ) );

// ---------------------------------------------------------------------------
// Rule kind
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_get_access_rule_kind' ) ) {
	/**
	 * Resolve a rule's kind ('content' or 'product').
	 *
	 * Uses the stored `rule_kind` when present and valid. For legacy rules saved
	 * before the kind field existed, the kind is inferred from the target so the
	 * enforcer keeps the content and product render paths separate without a data
	 * migration: a product target (or a product-category taxonomy target) is a
	 * product rule; everything else is a content rule. Re-saving a legacy rule
	 * persists the resolved kind.
	 *
	 * @since  2.0.0
	 * @param  array $rule Raw or sanitized rule array.
	 * @return string 'content' | 'product'.
	 */
	function wps_get_access_rule_kind( array $rule ) {
		if ( isset( $rule['rule_kind'] ) && in_array( $rule['rule_kind'], WPS_ACCESS_RULE_KINDS, true ) ) {
			return $rule['rule_kind'];
		}

		$target_type = isset( $rule['target_type'] ) ? $rule['target_type'] : '';
		$taxonomy    = isset( $rule['taxonomy'] ) ? $rule['taxonomy'] : '';
		$post_type   = isset( $rule['post_type'] ) ? $rule['post_type'] : '';

		if ( 'product' === $target_type
			|| ( 'taxonomy' === $target_type && in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) )
			|| ( 'post_type' === $target_type && 'product' === $post_type )
		) {
			return 'product';
		}

		return 'content';
	}
}

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
		// Resolve the rule kind first — it constrains which target types and
		// behaviors are valid and which fields are kept vs. dropped. Content and
		// product rules render through entirely separate paths.
		$kind = wps_get_access_rule_kind( $raw );

		$raw_type = isset( $raw['target_type'] ) ? $raw['target_type'] : '';

		if ( 'product' === $kind ) {
			$allowed_targets = WPS_ACCESS_RULE_PRODUCT_TARGETS;
			$default_target  = 'product';
		} else {
			$allowed_targets = WPS_ACCESS_RULE_CONTENT_TARGETS;
			$default_target  = 'post_type';
		}
		$target_type = in_array( $raw_type, $allowed_targets, true ) ? $raw_type : $default_target;

		// Product rules always gate purchasability — they have no behavior
		// selector. Content rules choose message / redirect / template.
		if ( 'product' === $kind ) {
			$behavior = 'message';
		} else {
			$behavior = isset( $raw['behavior'] ) && in_array( $raw['behavior'], WPS_ACCESS_RULE_BEHAVIORS, true )
				? $raw['behavior']
				: 'message';
		}

		// Product rules drop every content-only field so invalid combinations
		// can't be stored: redirect, template/teaser, drip, comments, archive,
		// and description restriction are all content concerns.
		$is_product = ( 'product' === $kind );

		// An "all products" rule is modelled as a whole-post-type target whose
		// post type is forced to 'product' (the engine already maps post_type
		// rules to the post_type bucket).
		$post_type = isset( $raw['post_type'] ) ? sanitize_key( $raw['post_type'] ) : '';
		if ( $is_product ) {
			$post_type = ( 'post_type' === $target_type ) ? 'product' : '';
		}

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

		// Advanced scheduling (Pro — Day 16). Persisted by Free; enforced by Pro.
		$drip_mode = isset( $raw['drip_mode'] ) && in_array( $raw['drip_mode'], WPS_ACCESS_RULE_DRIP_MODES, true )
			? $raw['drip_mode']
			: 'none';

		$drip_date = '';
		if ( isset( $raw['drip_date'] ) ) {
			$candidate = sanitize_text_field( $raw['drip_date'] );
			// Accept only an ISO Y-m-d date; anything else is discarded.
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $candidate ) ) {
				$drip_date = $candidate;
			}
		}

		// Rule exclusions (Pro — Day 16): specific post IDs exempted from a
		// broad (post-type / taxonomy) rule. Accepts either an array of IDs or
		// a comma-separated string (the admin field is a plain text input);
		// always stored as a clean, de-duplicated int list.
		$exclude_raw = isset( $raw['exclude_ids'] ) ? $raw['exclude_ids'] : array();
		if ( is_string( $exclude_raw ) ) {
			$exclude_raw = explode( ',', $exclude_raw );
		}
		$exclude_ids = is_array( $exclude_raw )
			? array_values( array_unique( array_filter( array_map( 'absint', $exclude_raw ) ) ) )
			: array();

		// Template behavior teaser (Pro — Day 18). Persisted by Free; enforced by Pro.
		$raw_teaser  = isset( $raw['teaser_mode'] ) ? $raw['teaser_mode'] : '';
		$teaser_mode = in_array( $raw_teaser, WPS_ACCESS_RULE_TEASER_MODES, true ) ? $raw_teaser : 'none';

		$enabled = ( isset( $raw['enabled'] ) && '0' === (string) $raw['enabled'] ) ? '0' : '1';

		return array(
			'id'                           => isset( $raw['id'] ) ? sanitize_key( $raw['id'] ) : '',
			'rule_kind'                    => $kind,
			'target_type'                  => $target_type,
			'post_type'                    => $post_type,
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
			'redirect_url'                 => ( ! $is_product && isset( $raw['redirect_url'] ) )
				? esc_url_raw( $raw['redirect_url'] )
				: '',
			'priority'                     => isset( $raw['priority'] ) ? absint( $raw['priority'] ) : 10,
			'enabled'                      => $enabled,
			'restrict_comments'            => $is_product ? '0' : $wps_flag( 'restrict_comments', $raw ),
			'include_archive'              => $is_product ? '0' : $wps_flag( 'include_archive', $raw ),
			'show_cta'                     => $wps_flag( 'show_cta', $raw ),
			'restrict_product_description' => '0',
			'drip_mode'                    => $is_product ? 'none' : $drip_mode,
			'drip_days'                    => ( ! $is_product && isset( $raw['drip_days'] ) )
				? absint( $raw['drip_days'] )
				: 0,
			'drip_date'                    => $is_product ? '' : $drip_date,
			'exclude_ids'                  => $is_product ? array() : $exclude_ids,
			'teaser_mode'                  => $is_product ? 'none' : $teaser_mode,
			'teaser_words'                 => ( ! $is_product && isset( $raw['teaser_words'] ) )
				? absint( $raw['teaser_words'] )
				: 0,
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

		$result = null;
		foreach ( $rules as $rule ) {
			$plans = isset( $rule['plans'] ) ? (array) $rule['plans'] : array( 'any' );

			$granted = $user_id > 0 && function_exists( 'wps_user_has_plan' )
				? wps_user_has_plan( $user_id, $plans )
				: false;

			if ( ! $granted ) {
				$result = $rule;
				break;
			}
		}

		/**
		 * Filter the resolved restriction for a post/user pair.
		 *
		 * Lets advanced (Pro) logic override the core decision after the base
		 * plan check has run — e.g. drip/scheduled access can restrict a post
		 * for a plan holder, and rule exclusions can exempt a specific post
		 * from an otherwise-matching broad rule. Receives every matching rule
		 * (priority-ordered) so the callback can re-evaluate from scratch.
		 *
		 * @since 2.0.0
		 *
		 * @param array|null $result  First failing rule, or null when access is granted.
		 * @param WP_Post    $post    The post being checked.
		 * @param int        $user_id Current user ID (0 = guest).
		 * @param array      $rules   All matching rules in priority order.
		 */
		$result = apply_filters( 'wps_object_is_restricted', $result, $post, $user_id, $rules );

		if ( ! is_array( $result ) ) {
			$result = null;
		}

		wp_cache_set(
			$cache_key,
			null === $result ? 'granted' : $result,
			WPS_ACCESS_RULES_CACHE_GROUP
		);

		return $result;
	}
}

// ---------------------------------------------------------------------------
// Restriction notice markup
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_restriction_notice_html' ) ) {
	/**
	 * Wrap restriction notice content in the shared "locked content" card.
	 *
	 * Produces a consistent panel — a header bar with a lock icon + title, and a
	 * body holding the (already-escaped, already-wpautop'd) message HTML — used
	 * by both the content-restriction enforcer and the Pro block-restriction
	 * notice so they look identical. Styling lives in
	 * public/css/wps-membership-badges.css (`.wps-restricted-content*`).
	 *
	 * @since  2.0.0
	 * @param  string $body_html   Inner message HTML (caller sanitizes/escapes).
	 * @param  string $extra_class Optional extra class on the wrapper.
	 * @param  string $title       Optional header title. Defaults to "Members Only".
	 * @return string
	 */
	function wps_restriction_notice_html( $body_html, $extra_class = '', $title = '' ) {
		if ( '' === $title ) {
			$title = __( 'Members Only', 'subscriptions-for-woocommerce' );
		}

		$classes = 'wps-restricted-content' . ( '' !== $extra_class ? ' ' . $extra_class : '' );

		$lock = '<svg class="wps-restricted-content__lock" viewBox="0 0 24 24" fill="none" '
			. 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>'
			. '<path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';

		return '<div class="' . esc_attr( $classes ) . '">'
			. '<div class="wps-restricted-content__head">'
			. $lock
			. '<span class="wps-restricted-content__title">' . esc_html( $title ) . '</span>'
			. '</div>'
			. '<div class="wps-restricted-content__msg">' . $body_html . '</div>'
			. '</div>';
	}
}

// ---------------------------------------------------------------------------
// Admin preview
// ---------------------------------------------------------------------------

if ( ! function_exists( 'wps_render_restriction_preview' ) ) {
	/**
	 * Render the non-member view of a rule for the admin Preview modal.
	 *
	 * Reuses the exact same renderers the front-end enforcer uses, so the preview
	 * is guaranteed to match what a logged-out visitor actually sees:
	 *   - Content / message + template → WPS_Restriction_Enforcer::build_message_html().
	 *   - Content / redirect           → a short "will redirect" note (no live redirect).
	 *   - Product                      → WPS_Restriction_Enforcer::render_product_gate_notice().
	 *
	 * The rule is sanitized first so unsaved admin input is normalized the same
	 * way a saved rule would be.
	 *
	 * @since  2.0.0
	 * @param  array $rule Raw (unsaved) rule array from the admin form.
	 * @return string Preview HTML.
	 */
	function wps_render_restriction_preview( array $rule ) {
		$rule = wps_sanitize_access_rule( $rule );
		$kind = wps_get_access_rule_kind( $rule );

		if ( ! class_exists( 'WPS_Restriction_Enforcer' ) ) {
			return '';
		}

		$enforcer = new WPS_Restriction_Enforcer();

		if ( 'product' === $kind ) {
			return $enforcer->render_product_gate_notice( $rule );
		}

		if ( 'redirect' === $rule['behavior'] ) {
			$url = ! empty( $rule['redirect_url'] )
				? $rule['redirect_url']
				: get_option( 'wps_access_redirect_url', '' );

			if ( ! empty( $url ) ) {
				return '<div class="wps-restricted-message wps-preview-redirect">'
					. esc_html__( 'Non-members are redirected to:', 'subscriptions-for-woocommerce' )
					. ' <code>' . esc_html( $url ) . '</code></div>';
			}
			// No URL configured — the enforcer falls back to the message, so the
			// preview shows the message too.
		}

		// Preview the guest (logged-out) view — the most restrictive state.
		return $enforcer->build_message_html( $rule, null, 0 );
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
