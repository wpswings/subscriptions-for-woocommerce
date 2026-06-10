<?php
/**
 * Membership Layer — Content Restriction Enforcer (Day 13)
 *
 * Hooks `the_content`, `template_redirect`, `woocommerce_is_purchasable`,
 * `pre_get_posts`, and `comments_open` to gate access based on Access Rules.
 * Also registers the `[wps-restrict]` shortcode.
 *
 * Restriction logic: `wps_object_is_restricted()` (Day 11).
 * Purchase CTA HTML:  `wps_render_plan_purchase_cta()` (Day 11).
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
	 * Gates content, products, archives, and comments based on Access Rules.
	 *
	 * @since 2.0.0
	 */
	class WPS_Restriction_Enforcer {

		// -----------------------------------------------------------------------
		// Public hook handlers (wired via the Loader in class-subscriptions-for-woocommerce.php)
		// -----------------------------------------------------------------------

		/**
		 * Enqueue the membership frontend stylesheet on restricted singular views.
		 *
		 * The purchase CTA and restriction notice can surface on any singular
		 * post, page, or product — not only WooCommerce pages — so the styles are
		 * loaded here whenever the queried object is actually restricted.
		 *
		 * Hooked to `wp_enqueue_scripts`.
		 *
		 * @since 2.0.0
		 */
		public function enqueue_styles() {
			if ( is_admin() || ! is_singular() ) {
				return;
			}

			$post = get_queried_object();
			if ( ! $post instanceof WP_Post ) {
				return;
			}

			if ( null === wps_object_is_restricted( $post, get_current_user_id() ) ) {
				return;
			}

			wp_enqueue_style(
				'wps-membership-badges',
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'public/css/wps-membership-badges.css',
				array(),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
			);
		}

		/**
		 * Filter post content, replacing it with a restriction notice when the
		 * current user lacks the required plan membership.
		 *
		 * Only fires on singular views. Redirect-behavior rules are handled by
		 * maybe_redirect() which runs on template_redirect (earlier in the WP
		 * execution order), so if we arrive here with a redirect rule it means
		 * either the redirect already fired (and we should not alter content) or
		 * no URL is configured (fall through to the message).
		 *
		 * @since  2.0.0
		 * @param  string $content Post content.
		 * @return string Original or restricted content.
		 */
		public function maybe_restrict_content( $content ) {
			if ( is_admin() || ! is_singular() ) {
				return $content;
			}

			$post = get_post();
			if ( ! $post instanceof WP_Post ) {
				return $content;
			}

			$user_id = get_current_user_id();
			$rule    = wps_object_is_restricted( $post, $user_id );

			if ( null === $rule ) {
				return $content;
			}

			if ( 'redirect' === $this->rule_behavior( $rule ) ) {
				$url = ! empty( $rule['redirect_url'] )
					? $rule['redirect_url']
					: get_option( 'wps_access_redirect_url', '' );

				if ( ! empty( $url ) ) {
					// maybe_redirect() has already called wp_safe_redirect() + exit.
					// We only reach here in edge cases (e.g. unit tests); pass through.
					return $content;
				}
				// No URL configured — fall through and show message instead.
			}

			return $this->build_message_html( $rule, $post, $user_id );
		}

		/**
		 * Redirect non-members away from restricted singular URLs before render.
		 *
		 * Fires on template_redirect (before the_content). Only acts when the
		 * matching rule's behavior is 'redirect' and a URL is configured.
		 * When no URL exists the request falls through and maybe_restrict_content()
		 * shows a message instead.
		 *
		 * @since 2.0.0
		 */
		public function maybe_redirect() {
			if ( is_admin() || ! is_singular() ) {
				return;
			}

			$post = get_post();
			if ( ! $post instanceof WP_Post ) {
				return;
			}

			$user_id = get_current_user_id();
			$rule    = wps_object_is_restricted( $post, $user_id );

			if ( null === $rule ) {
				return;
			}

			if ( 'redirect' !== $this->rule_behavior( $rule ) ) {
				return;
			}

			$url = ! empty( $rule['redirect_url'] )
				? $rule['redirect_url']
				: get_option( 'wps_access_redirect_url', '' );

			if ( empty( $url ) ) {
				return; // No URL — maybe_restrict_content() will show a message.
			}

			wp_safe_redirect( esc_url_raw( $url ) );
			exit;
		}

		/**
		 * Filter product/variation purchasability for non-members.
		 *
		 * Hooked to woocommerce_is_purchasable and woocommerce_variation_is_purchasable
		 * at priority 99. Returns false when the product post is restricted for the
		 * current user.
		 *
		 * @since  2.0.0
		 * @param  bool       $purchasable Whether the product is purchasable.
		 * @param  WC_Product $product     Product object.
		 * @return bool
		 */
		public function maybe_restrict_purchasability( $purchasable, $product ) {
			if ( ! $purchasable ) {
				return false;
			}

			if ( ! ( $product instanceof WC_Product ) ) {
				return $purchasable;
			}

			$post_id = $product->get_id();
			if ( ! $post_id ) {
				return $purchasable;
			}

			// Plan-granting products must stay purchasable only while their grant
			// method is enabled. If purchase/subscription is disabled the product
			// is no longer a grant vehicle, so normal access rules apply to it.
			// Use wps_product_actively_grants_membership() which checks both the
			// map and the per-method enabled flag in one call.
			if ( function_exists( 'wps_product_actively_grants_membership' )
				&& null !== wps_product_actively_grants_membership( $post_id )
			) {
				return true;
			}
			// Stale-map fallback: rebuild and re-check with the enabled-aware helper.
			if ( function_exists( 'wps_rebuild_product_plan_map' )
				&& function_exists( 'wps_product_actively_grants_membership' )
			) {
				wps_rebuild_product_plan_map();
				if ( null !== wps_product_actively_grants_membership( $post_id ) ) {
					return true;
				}
			}

			$post = get_post( $post_id );
			if ( ! $post instanceof WP_Post ) {
				return $purchasable;
			}

			return null === wps_object_is_restricted( $post, get_current_user_id() );
		}

		/**
		 * Register the [wps-restrict] shortcode and wire init-time public filters.
		 *
		 * Hooked to init at priority 5 (via the Loader). Also adds comments_open
		 * and pre_get_posts here to avoid extra Loader registrations.
		 *
		 * @since 2.0.0
		 */
		public function register_shortcode() {
			add_shortcode( 'wps-restrict', array( $this, 'shortcode_output' ) );
			add_filter( 'comments_open', array( $this, 'maybe_close_comments' ), 99, 2 );
			add_action( 'pre_get_posts', array( $this, 'maybe_filter_archive' ), 99 );
		}

		/**
		 * Render [wps-restrict] shortcode output.
		 *
		 * Attributes:
		 *   plans (string) — comma-separated plan slugs or 'any' (default).
		 *                    Omitting the attribute is equivalent to 'any'.
		 *
		 * Members (and admins) see the enclosed content; everyone else gets an
		 * empty string. Use CSS or another shortcode to show a teaser to guests.
		 *
		 * @since  2.0.0
		 * @param  array       $atts    Shortcode attributes.
		 * @param  string|null $content Enclosed content.
		 * @return string
		 */
		public function shortcode_output( $atts, $content = '' ) {
			$atts = shortcode_atts(
				array( 'plans' => 'any' ),
				$atts,
				'wps-restrict'
			);

			$user_id = get_current_user_id();

			if ( $user_id > 0 && user_can( $user_id, 'manage_options' ) ) {
				return do_shortcode( (string) $content );
			}

			$raw_plans  = sanitize_text_field( $atts['plans'] );
			$plan_slugs = 'any' === $raw_plans
				? 'any'
				: array_values(
					array_filter( array_map( 'sanitize_key', explode( ',', $raw_plans ) ) )
				);

			$has_access = $user_id > 0
				&& function_exists( 'wps_user_has_plan' )
				&& wps_user_has_plan( $user_id, $plan_slugs );

			return $has_access ? do_shortcode( (string) $content ) : '';
		}

		/**
		 * Close comments on restricted posts when the global option is enabled.
		 *
		 * Hooked to comments_open at priority 99 (wired in register_shortcode).
		 *
		 * @since  2.0.0
		 * @param  bool $open    Whether comments are currently open.
		 * @param  int  $post_id Post ID.
		 * @return bool
		 */
		public function maybe_close_comments( $open, $post_id ) {
			if ( ! $open ) {
				return false;
			}

			if ( '1' !== get_option( 'wps_access_restrict_comments', '0' ) ) {
				return $open;
			}

			$post = get_post( absint( $post_id ) );
			if ( ! $post instanceof WP_Post ) {
				return $open;
			}

			return null === wps_object_is_restricted( $post, get_current_user_id() );
		}

		/**
		 * Exclude restricted posts from archive and search queries.
		 *
		 * Fires on pre_get_posts (wired in register_shortcode). Only modifies the
		 * main query outside admin/singular views. Respects the global
		 * wps_access_include_in_archive option — when '1', restricted content is
		 * shown in archives (but content is replaced on the singular view).
		 *
		 * Covers object-level rules (specific post IDs). Post-type–level exclusions
		 * would require removing entire post types from the query; that edge case is
		 * left for a future Pro feature.
		 *
		 * @since  2.0.0
		 * @param  WP_Query $query The current WP_Query object.
		 */
		public function maybe_filter_archive( WP_Query $query ) {
			if ( is_admin() || ! $query->is_main_query() || is_singular() ) {
				return;
			}

			if ( '1' === get_option( 'wps_access_include_in_archive', '1' ) ) {
				return; // Site is configured to show restricted posts in archives.
			}

			$user_id = get_current_user_id();

			if ( $user_id > 0 && user_can( $user_id, 'manage_options' ) ) {
				return;
			}

			$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION, array() );
			if ( empty( $index['object'] ) || ! is_array( $index['object'] ) ) {
				return;
			}

			$all_rules = wps_get_access_rules();
			$rules_map = array();
			foreach ( $all_rules as $rule ) {
				if ( isset( $rule['id'] ) ) {
					$rules_map[ $rule['id'] ] = $rule;
				}
			}

			$exclude_ids = array();
			foreach ( $index['object'] as $post_id => $rule_ids ) {
				foreach ( (array) $rule_ids as $rid ) {
					if ( isset( $rules_map[ $rid ] )
						&& $this->user_fails_rule( $user_id, $rules_map[ $rid ] )
					) {
						$exclude_ids[] = absint( $post_id );
						break;
					}
				}
			}

			if ( ! empty( $exclude_ids ) ) {
				$existing = (array) $query->get( 'post__not_in' );
				$query->set(
					'post__not_in',
					array_unique( array_merge( $existing, $exclude_ids ) )
				);
			}
		}

		// -----------------------------------------------------------------------
		// Private helpers
		// -----------------------------------------------------------------------

		/**
		 * Build the full restriction notice HTML for a blocked user.
		 *
		 * A rule-specific (custom) message is rendered exactly as entered — no
		 * "Members Only" card chrome and no auto CTA. Otherwise the global/default
		 * message is wrapped in the shared card with the optional purchase CTA.
		 *
		 * @since  2.0.0
		 * @param  array   $rule    The first failing access rule.
		 * @param  WP_Post $post    The restricted post.
		 * @param  int     $user_id The current user ID (0 = guest).
		 * @return string HTML output.
		 */
		private function build_message_html( array $rule, WP_Post $post, $user_id ) {
			$cta = $this->resolve_purchase_options( $rule );

			if ( ! empty( $rule['message'] ) ) {
				// Custom (rule-specific) message: render exactly what the admin
				// entered — no "Members Only" card chrome and no auto-appended CTA,
				// the custom message is authoritative. The {purchase_options} tag
				// still expands so a buy-link can be added on demand.
				$message = str_replace( '{purchase_options}', $cta, $rule['message'] );
				$html    = '<div class="wps-restricted-message">' . wpautop( wp_kses_post( $message ) ) . '</div>';
			} else {
				// No custom message: fall back to the global/default text wrapped in
				// the shared "Members Only" card, with the optional auto-appended CTA.
				$message = $this->resolve_message_text( $rule, $user_id );

				// Replace {purchase_options} merge tag wherever it appears.
				$message = str_replace( '{purchase_options}', $cta, $message );

				// Auto-append CTA when tag is absent, CTA is non-empty, and option is on.
				$tag_was_used = false !== strpos(
					get_option( 'wps_access_logged_out_message', '' ),
					'{purchase_options}'
				)
					|| false !== strpos(
						get_option( 'wps_access_wrong_plan_message', '' ),
						'{purchase_options}'
					);

				if ( ! $tag_was_used
					&& ! empty( $cta )
					&& '1' === get_option( 'wps_access_show_purchase_cta', '0' )
				) {
					$message .= $cta;
				}

				$body = wpautop( wp_kses_post( $message ) );
				$html = function_exists( 'wps_restriction_notice_html' )
					? wps_restriction_notice_html( $body )
					: '<div class="wps-restricted-content">' . $body . '</div>';
			}

			/**
			 * Filter the full restriction HTML before it replaces post content.
			 *
			 * @since 2.0.0
			 *
			 * @param string  $html    The restriction notice HTML.
			 * @param array   $rule    The first failing access rule.
			 * @param WP_Post $post    The restricted post.
			 * @param int     $user_id Current user ID (0 = guest).
			 */
			return apply_filters( 'wps_restriction_message_html', $html, $rule, $post, $user_id );
		}

		/**
		 * Resolve the restriction message text for this rule and user state.
		 *
		 * Priority order:
		 *   1. Rule-specific message (non-empty).
		 *   2. Global wps_access_logged_out_message (guest users).
		 *   3. Global wps_access_wrong_plan_message (logged-in users).
		 *   4. Hard-coded fallback string.
		 *
		 * @since  2.0.0
		 * @param  array $rule    The failing access rule.
		 * @param  int   $user_id Current user ID (0 = guest).
		 * @return string Unsanitized message text (sanitized in build_message_html).
		 */
		private function resolve_message_text( array $rule, $user_id ) {
			if ( ! empty( $rule['message'] ) ) {
				return $rule['message'];
			}

			if ( 0 === absint( $user_id ) ) {
				$default = get_option( 'wps_access_logged_out_message', '' );
				return '' !== $default
					? $default
					: __( 'You must be logged in to view this content.', 'subscriptions-for-woocommerce' );
			}

			$default = get_option( 'wps_access_wrong_plan_message', '' );
			return '' !== $default
				? $default
				: __(
					'Your current subscription does not include access to this content.',
					'subscriptions-for-woocommerce'
				);
		}

		/**
		 * Build the {purchase_options} HTML replacement for a rule.
		 *
		 * For rules requiring 'any' plan, gathers all active plans' purchasable
		 * products. For specific plans, uses only those plan slugs.
		 * Delegates rendering to wps_render_plan_purchase_cta().
		 *
		 * @since  2.0.0
		 * @param  array $rule The failing access rule.
		 * @return string HTML string (empty when WC is unavailable or no products).
		 */
		private function resolve_purchase_options( array $rule ) {
			if ( ! function_exists( 'wps_render_plan_purchase_cta' ) ) {
				return '';
			}

			$plans = isset( $rule['plans'] ) ? (array) $rule['plans'] : array( 'any' );

			if ( in_array( 'any', $plans, true ) ) {
				if ( ! function_exists( 'wps_get_all_plans' ) ) {
					return '';
				}
				$all_plans = wps_get_all_plans( 'active' );
				$plans     = wp_list_pluck( $all_plans, 'slug' );
			}

			if ( empty( $plans ) ) {
				return '';
			}

			return wps_render_plan_purchase_cta( $plans );
		}

		/**
		 * Return the effective restriction behavior for a rule.
		 *
		 * Falls back to the wps_access_default_behavior option when the rule
		 * stores an unrecognised value.
		 *
		 * @since  2.0.0
		 * @param  array $rule Access rule array.
		 * @return string 'message' or 'redirect'.
		 */
		private function rule_behavior( array $rule ) {
			$behavior = isset( $rule['behavior'] ) ? $rule['behavior'] : '';
			if ( in_array( $behavior, array( 'message', 'redirect' ), true ) ) {
				return $behavior;
			}
			return 'redirect' === get_option( 'wps_access_default_behavior', 'message' )
				? 'redirect'
				: 'message';
		}

		/**
		 * Return true when the given user does NOT satisfy the rule's plan requirement.
		 *
		 * Guests always fail. Logged-in users pass through wps_user_has_plan().
		 *
		 * @since  2.0.0
		 * @param  int   $user_id WordPress user ID (0 = guest).
		 * @param  array $rule    Access rule array.
		 * @return bool
		 */
		private function user_fails_rule( $user_id, array $rule ) {
			$user_id = absint( $user_id );
			if ( 0 === $user_id ) {
				return true;
			}
			if ( ! function_exists( 'wps_user_has_plan' ) ) {
				return true;
			}
			$plans = isset( $rule['plans'] ) ? (array) $rule['plans'] : array( 'any' );
			return ! wps_user_has_plan( $user_id, $plans );
		}
	}
}
