<?php
/**
 * Membership Layer — Access Rules Admin Tab (Day 12)
 *
 * Registers the "Access Rules" settings tab, handles rule saves,
 * global-defaults saves, and the AJAX target-search handler.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Access_Rules_Admin' ) ) {

	/**
	 * Access Rules settings tab and supporting handlers.
	 *
	 * @since 2.0.0
	 */
	class WPS_Access_Rules_Admin {

		const TAB_KEY      = 'wps-membership-access-rules';
		const NONCE_ACTION = 'wps_save_access_rules';
		const NONCE_FIELD  = 'wps_access_rules_nonce';

		/**
		 * Wire admin_init for save handling and admin_enqueue_scripts for the JS file.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'handle_save' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		// -----------------------------------------------------------------------
		// Tab registration
		// -----------------------------------------------------------------------

		/**
		 * Add the "Access Rules" tab to the plugin settings navigation.
		 *
		 * Hooked to `wps_sfw_sfw_plugin_standard_admin_settings_tabs` at priority 35.
		 *
		 * @since  2.0.0
		 * @param  array $tabs Existing settings tabs.
		 * @return array
		 */
		public function register_tab( $tabs ) {
			$tabs[ self::TAB_KEY ] = array(
				'title'     => esc_html__( 'Access Rules', 'subscriptions-for-woocommerce' ),
				'name'      => self::TAB_KEY,
				'file_path' => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
			);
			return $tabs;
		}

		// -----------------------------------------------------------------------
		// Asset enqueueing
		// -----------------------------------------------------------------------

		/**
		 * Enqueue the Access Rules JS only on the Access Rules tab.
		 *
		 * @since 2.0.0
		 */
		public function enqueue_scripts() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab = isset( $_GET['sfw_tab'] ) ? sanitize_key( wp_unslash( $_GET['sfw_tab'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$sub_tab = isset( $_GET['wps_mem_tab'] ) ? sanitize_key( wp_unslash( $_GET['wps_mem_tab'] ) ) : '';
			if ( 'wps-membership-manage' !== $tab || 'access-rules' !== $sub_tab ) {
				return;
			}

			wp_enqueue_style(
				'wps-access-rules',
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-access-rules.css',
				array(),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
			);

			wp_enqueue_script(
				'wps-access-rules',
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-access-rules.js',
				array(),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION,
				true
			);

			wp_localize_script(
				'wps-access-rules',
				'wpsAccessRules',
				array(
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'wps_membership_admin_nonce' ),
					'searching'       => esc_html__( 'Searching…', 'subscriptions-for-woocommerce' ),
					'noResults'       => esc_html__( 'No results found.', 'subscriptions-for-woocommerce' ),
					'removeItem'      => esc_html__( '×', 'subscriptions-for-woocommerce' ),
					'subGrantNotice'  => esc_html__(
						'Access duration follows the subscription lifecycle — the Access Length setting is ignored.',
						'subscriptions-for-woocommerce'
					),
					'autoGrantNotice' => esc_html__(
						'Auto-Enroll plan: access is granted automatically on registration.',
						'subscriptions-for-woocommerce'
					),
				)
			);
		}

		// -----------------------------------------------------------------------
		// Save handler (admin_init)
		// -----------------------------------------------------------------------

		/**
		 * Process the Access Rules form submission.
		 *
		 * Saves global defaults and the full rules array, then redirects back
		 * to the tab with a `wps_saved=1` flag.
		 *
		 * Requires: `manage_woocommerce` capability + valid nonce.
		 *
		 * @since 2.0.0
		 */
		public function handle_save() {
			if ( ! isset( $_POST['wps_save_access_rules'] ) ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			if ( 'subscriptions_for_woocommerce_menu' !== $page ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab = isset( $_GET['sfw_tab'] ) ? sanitize_key( wp_unslash( $_GET['sfw_tab'] ) ) : '';
			if ( 'wps-membership-manage' !== $tab ) {
				return;
			}
			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die(
					esc_html__( 'You do not have permission to perform this action.', 'subscriptions-for-woocommerce' )
				);
			}
			if ( ! isset( $_POST[ self::NONCE_FIELD ] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ),
					self::NONCE_ACTION
				)
			) {
				wp_die( esc_html__( 'Security check failed.', 'subscriptions-for-woocommerce' ) );
			}

			$this->persist_rules();

			$tab_url = admin_url(
				'admin.php?page=subscriptions_for_woocommerce_menu'
				. '&sfw_tab=wps-membership-manage&wps_mem_tab=access-rules'
			);
			wp_safe_redirect( add_query_arg( 'wps_saved', '1', $tab_url ) );
			exit;
		}

		// -----------------------------------------------------------------------
		// AJAX handlers
		// -----------------------------------------------------------------------

		/**
		 * AJAX: search purchasable products for the Linked Products meta box.
		 *
		 * Hooked to `wp_ajax_wps_search_plan_products`.
		 *
		 * @since 2.0.0
		 */
		public function ajax_search_plan_products() {
			check_ajax_referer( 'wps_membership_admin_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'subscriptions-for-woocommerce' ) ) );
				return;
			}

			$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

			if ( strlen( $term ) < 2 ) {
				wp_send_json_success( array( 'results' => array() ) );
				return;
			}

			$posts = get_posts(
				array(
					's'              => $term,
					'post_type'      => 'product',
					'posts_per_page' => 20,
					'post_status'    => 'publish',
				)
			);

			$results = array();
			foreach ( $posts as $p ) {
				$results[] = array(
					'id'   => (int) $p->ID,
					'text' => $p->post_title,
				);
			}

			wp_send_json_success( array( 'results' => $results ) );
		}

		/**
		 * AJAX: search content targets (posts, terms) for an access rule row.
		 *
		 * Hooked to `wp_ajax_wps_search_rule_targets`.
		 * POST params: `target_type` (post|page|product|taxonomy), `term` (search string),
		 *              `taxonomy` (when target_type=taxonomy).
		 * Returns JSON: `{success: true, data: {results: [{id, text}, …]}}`.
		 *
		 * @since 2.0.0
		 */
		public function ajax_search_rule_targets() {
			check_ajax_referer( 'wps_membership_admin_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'subscriptions-for-woocommerce' ) ) );
				return;
			}

			$target_type = isset( $_POST['target_type'] ) ? sanitize_key( wp_unslash( $_POST['target_type'] ) ) : '';
			$term        = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

			if ( strlen( $term ) < 2 ) {
				wp_send_json_success( array( 'results' => array() ) );
				return;
			}

			$results = array();

			if ( 'taxonomy' === $target_type ) {
				$taxonomy = isset( $_POST['taxonomy'] )
					? sanitize_key( wp_unslash( $_POST['taxonomy'] ) )
					: 'category';

				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'name__like' => $term,
						'number'     => 20,
						'hide_empty' => false,
					)
				);

				if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
					foreach ( $terms as $t ) {
						$results[] = array(
							'id'   => (int) $t->term_id,
							'text' => $t->name,
						);
					}
				}
			} else {
				$type_map  = array(
					'post'    => 'post',
					'page'    => 'page',
					'product' => 'product',
				);
				$post_type = isset( $type_map[ $target_type ] ) ? $type_map[ $target_type ] : 'post';

				$posts = get_posts(
					array(
						's'              => $term,
						'post_type'      => $post_type,
						'posts_per_page' => 20,
						'post_status'    => 'publish',
					)
				);

				foreach ( $posts as $p ) {
					$results[] = array(
						'id'   => (int) $p->ID,
						'text' => $p->post_title,
					);
				}
			}

			wp_send_json_success( array( 'results' => $results ) );
		}

		// -----------------------------------------------------------------------
		// Private helpers
		// -----------------------------------------------------------------------

		/**
		 * Save the global access-rule defaults from POST data.
		 *
		 * @since 2.0.0
		 */
		private function persist_global_defaults() {
			// Nonce verified by the calling method (handle_save).
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$behavior = isset( $_POST['wps_access_default_behavior'] )
				&& 'redirect' === $_POST['wps_access_default_behavior']
					? 'redirect'
					: 'message';

			update_option( 'wps_access_default_behavior', $behavior );

			update_option(
				'wps_access_logged_out_message',
				isset( $_POST['wps_access_logged_out_message'] )
					? wp_kses_post( wp_unslash( $_POST['wps_access_logged_out_message'] ) )
					: ''
			);

			update_option(
				'wps_access_wrong_plan_message',
				isset( $_POST['wps_access_wrong_plan_message'] )
					? wp_kses_post( wp_unslash( $_POST['wps_access_wrong_plan_message'] ) )
					: ''
			);

			update_option(
				'wps_access_redirect_url',
				isset( $_POST['wps_access_redirect_url'] )
					? esc_url_raw( wp_unslash( $_POST['wps_access_redirect_url'] ) )
					: ''
			);

			update_option(
				'wps_access_restrict_comments',
				! empty( $_POST['wps_access_restrict_comments'] ) ? '1' : '0'
			);

			update_option(
				'wps_access_include_in_archive',
				! empty( $_POST['wps_access_include_in_archive'] ) ? '1' : '0'
			);

			update_option(
				'wps_access_show_purchase_cta',
				! empty( $_POST['wps_access_show_purchase_cta'] ) ? '1' : '0'
			);
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Parse and save the rules array from POST data.
		 *
		 * Each rule in `$_POST['wps_rules']` is an associative array; we pass
		 * each through `wps_sanitize_access_rule()` so all validation lives in one place.
		 * New rules have no `id` (or an empty one) — `wps_save_access_rules()` preserves
		 * the id field and `wps_sanitize_access_rule()` returns it as-is; the engine
		 * does NOT auto-generate IDs for rules that already have one.
		 *
		 * To ensure new rows get a generated ID we assign them one here before passing
		 * to the save function.
		 *
		 * @since 2.0.0
		 */
		private function persist_rules() {
			// Nonce verified by the calling method (handle_save).
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// Individual fields are sanitized inside wps_sanitize_access_rule().
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_rules = isset( $_POST['wps_rules'] ) ? (array) wp_unslash( $_POST['wps_rules'] ) : array();
			$rules     = array();

			// Collect existing IDs so generated IDs don't collide.
			$existing_ids = array();
			foreach ( $raw_rules as $raw ) {
				if ( is_array( $raw ) && ! empty( $raw['id'] ) ) {
					$existing_ids[] = sanitize_key( $raw['id'] );
				}
			}

			foreach ( $raw_rules as $raw ) {
				if ( ! is_array( $raw ) ) {
					continue;
				}

				// Auto-generate an ID for new rows.
				if ( empty( $raw['id'] ) ) {
					do {
						$new_id = 'r' . strtolower( substr( wp_generate_password( 8, false, false ), 0, 6 ) );
					} while ( in_array( $new_id, $existing_ids, true ) );
					$raw['id']      = $new_id;
					$existing_ids[] = $new_id;
				}

				// Normalize plans — POST multi-select sends an array or nothing.
				if ( ! isset( $raw['plans'] ) || ! is_array( $raw['plans'] ) ) {
					$raw['plans'] = array( 'any' );
				}

				// Normalize object/term ID arrays from hidden inputs.
				if ( isset( $raw['object_ids'] ) && ! is_array( $raw['object_ids'] ) ) {
					$raw['object_ids'] = array( $raw['object_ids'] );
				}
				if ( isset( $raw['term_ids'] ) && ! is_array( $raw['term_ids'] ) ) {
					$raw['term_ids'] = array( $raw['term_ids'] );
				}

				$rules[] = wps_sanitize_access_rule( $raw );
			}

			wps_save_access_rules( $rules );
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}
	}
}
