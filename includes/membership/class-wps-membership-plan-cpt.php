<?php
/**
 * Membership Layer — Plan CPT Registration (Day 07)
 *
 * Registers the `wps_membership_plan` custom post type and wires four meta boxes:
 * Plan Details, Access Length, Linked Products, and a read-only Plan Summary side panel.
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

		const NONCE_ACTION = 'wps_save_plan_meta';
		const NONCE_FIELD  = 'wps_plan_meta_nonce';

		/**
		 * Search subscription products for the Select2 field on the plan edit screen.
		 *
		 * Hooked to `wp_ajax_wps_search_subscription_products`.
		 * Uses the same WC data-store search that powers the standard product search,
		 * then filters results to only products that are WPS subscriptions:
		 * simple subscriptions (`_wps_sfw_product = 'yes'`) or variable
		 * subscriptions (`wps_sfw_variable_product = 'yes'`).
		 *
		 * @since 2.0.0
		 */
		public function ajax_search_subscription_products() {
			check_ajax_referer( 'search-products', 'security' );

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_die( -1 );
			}

			$term = isset( $_GET['term'] )
				? wc_clean( sanitize_text_field( wp_unslash( $_GET['term'] ) ) )
				: '';

			if ( '' === $term ) {
				wp_send_json( array() );
			}

			// Search parent products only — variation titles are empty in the DB,
			// so searching product_variation post types yields nothing useful.
			$parent_ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => 30,
					's'              => $term,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'   => '_wps_sfw_product',
							'value' => 'yes',
						),
						array(
							'key'   => 'wps_sfw_variable_product',
							'value' => 'yes',
						),
					),
				)
			);

			$results = array();
			foreach ( $parent_ids as $parent_id ) {
				$product = wc_get_product( absint( $parent_id ) );
				if ( ! $product ) {
					continue;
				}

				if ( $product->is_type( 'variable' ) ) {
					// Expand variable products into their individual variations.
					foreach ( $product->get_children() as $variation_id ) {
						$variation = wc_get_product( absint( $variation_id ) );
						if ( $variation && 'publish' === $variation->get_status() ) {
							$results[ $variation_id ] = rawurldecode(
								wp_strip_all_tags( $variation->get_formatted_name() )
							);
						}
					}
				} else {
					$results[ $parent_id ] = rawurldecode(
						wp_strip_all_tags( $product->get_formatted_name() )
					);
				}
			}

			wp_send_json( $results );
		}

		/**
		 * Return billing details for a single subscription product.
		 *
		 * Hooked to `wp_ajax_wps_get_subscription_duration`.
		 * Used by the Grant Methods meta box to populate the Access Length
		 * preview panel when the "Active Subscription" method is selected.
		 *
		 * @since 2.0.0
		 */
		public function ajax_get_subscription_duration() {
			check_ajax_referer( 'wps_membership_admin_nonce', 'security' );

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_die( -1 );
			}

			$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
			if ( ! $product_id ) {
				wp_send_json_error();
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				wp_send_json_error();
			}

			// Subscription meta is stored on the variation post itself; fall back
			// to parent only when the variation has no value set.
			$meta_id  = $product->get_id();
			$number   = (int) get_post_meta( $meta_id, 'wps_sfw_subscription_number', true );
			$interval = get_post_meta( $meta_id, 'wps_sfw_subscription_interval', true );
			if ( $product->is_type( 'variation' ) && ! $number ) {
				$parent_id = $product->get_parent_id();
				$number    = (int) get_post_meta( $parent_id, 'wps_sfw_subscription_number', true );
				$interval  = get_post_meta( $parent_id, 'wps_sfw_subscription_interval', true );
			}
			$exp_num = (int) get_post_meta( $meta_id, 'wps_sfw_subscription_expiry_number', true );
			$exp_int = get_post_meta( $meta_id, 'wps_sfw_subscription_expiry_interval', true );
			if ( $product->is_type( 'variation' ) && ! $exp_num ) {
				$parent_id = $product->get_parent_id();
				$exp_num   = (int) get_post_meta( $parent_id, 'wps_sfw_subscription_expiry_number', true );
				$exp_int   = get_post_meta( $parent_id, 'wps_sfw_subscription_expiry_interval', true );
			}

			$interval_labels = array(
				'day'   => array(
					__( 'day', 'subscriptions-for-woocommerce' ),
					__( 'days', 'subscriptions-for-woocommerce' ),
				),
				'week'  => array(
					__( 'week', 'subscriptions-for-woocommerce' ),
					__( 'weeks', 'subscriptions-for-woocommerce' ),
				),
				'month' => array(
					__( 'month', 'subscriptions-for-woocommerce' ),
					__( 'months', 'subscriptions-for-woocommerce' ),
				),
				'year'  => array(
					__( 'year', 'subscriptions-for-woocommerce' ),
					__( 'years', 'subscriptions-for-woocommerce' ),
				),
			);

			$billing = '';
			if ( $number && $interval && isset( $interval_labels[ $interval ] ) ) {
				$unit    = 1 === $number
					? $interval_labels[ $interval ][0]
					: $interval_labels[ $interval ][1];
				$billing = sprintf(
					/* translators: 1: billing number, 2: period unit */
					__( 'Every %1$s %2$s', 'subscriptions-for-woocommerce' ),
					$number,
					$unit
				);
			}

			$duration = __( 'No fixed expiry — access while subscription is active', 'subscriptions-for-woocommerce' );
			if ( $exp_num && $exp_int && isset( $interval_labels[ $exp_int ] ) ) {
				$unit     = 1 === $exp_num
					? $interval_labels[ $exp_int ][0]
					: $interval_labels[ $exp_int ][1];
				$duration = sprintf(
					/* translators: 1: expiry number, 2: period unit */
					__( 'Expires after %1$s %2$s', 'subscriptions-for-woocommerce' ),
					$exp_num,
					$unit
				);
			}

			$thumbnail_id  = $product->get_image_id();
			$thumbnail_url = $thumbnail_id
				? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' )
				: wc_placeholder_img_src( 'thumbnail' );

			wp_send_json_success(
				array(
					'billing'       => $billing,
					'duration'      => $duration,
					'name'          => wp_strip_all_tags( $product->get_formatted_name() ),
					'price_html'    => html_entity_decode(
						wp_strip_all_tags( $product->get_price_html() ),
						ENT_QUOTES | ENT_HTML5,
						'UTF-8'
					),
					'thumbnail_url' => esc_url_raw( $thumbnail_url ),
				)
			);
		}

		/**
		 * Enqueue WooCommerce admin assets on the plan CPT edit screen.
		 *
		 * Hooked to `admin_enqueue_scripts`.
		 *
		 * @since 2.0.0
		 */
		public function enqueue_admin_scripts() {
			$screen = get_current_screen();
			if ( ! $screen || WPS_MEMBERSHIP_PLAN_CPT !== $screen->id ) {
				return;
			}
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style(
				'wps-plan-grant-methods',
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-plan-grant-methods.css',
				array(),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
			);
		}

		/**
		 * Register the wps_membership_plan CPT with WordPress.
		 *
		 * Hooked to `init` at priority 5.
		 *
		 * @since 2.0.0
		 */
		public function register() {
			$labels = array(
				'name'               => esc_html__( 'Membership Plans', 'subscriptions-for-woocommerce' ),
				'singular_name'      => esc_html__( 'Membership Plan', 'subscriptions-for-woocommerce' ),
				'add_new'            => esc_html__( 'Add New Plan', 'subscriptions-for-woocommerce' ),
				'add_new_item'       => esc_html__( 'Add New Membership Plan', 'subscriptions-for-woocommerce' ),
				'edit_item'          => esc_html__( 'Edit Membership Plan', 'subscriptions-for-woocommerce' ),
				'new_item'           => esc_html__( 'New Membership Plan', 'subscriptions-for-woocommerce' ),
				'search_items'       => esc_html__( 'Search Membership Plans', 'subscriptions-for-woocommerce' ),
				'not_found'          => esc_html__( 'No membership plans found.', 'subscriptions-for-woocommerce' ),
				'not_found_in_trash' => esc_html__(
					'No membership plans found in Trash.',
					'subscriptions-for-woocommerce'
				),
				'menu_name'          => esc_html__( 'Membership Plans', 'subscriptions-for-woocommerce' ),
			);

			register_post_type(
				WPS_MEMBERSHIP_PLAN_CPT,
				array(
					'labels'            => $labels,
					'public'            => false,
					'show_ui'           => true,
					'show_in_menu'      => false,
					'show_in_nav_menus' => false,
					'show_in_rest'      => false,
					'supports'          => array( 'title', 'editor' ),
					'rewrite'           => false,
					'has_archive'       => false,
					'query_var'         => false,
					'capability_type'   => 'post',
				)
			);
		}

		/**
		 * Register the four meta boxes on the plan edit screen.
		 *
		 * Hooked to `add_meta_boxes`.
		 *
		 * @since 2.0.0
		 */
		public function add_meta_boxes() {
			$cpt = WPS_MEMBERSHIP_PLAN_CPT;

			add_meta_box(
				'wps-plan-details',
				esc_html__( 'Plan Details', 'subscriptions-for-woocommerce' ),
				array( $this, 'render_details_meta_box' ),
				$cpt,
				'normal',
				'high'
			);

			add_meta_box(
				'wps-plan-access',
				esc_html__( 'Access Length', 'subscriptions-for-woocommerce' ),
				array( $this, 'render_access_meta_box' ),
				$cpt,
				'normal',
				'default'
			);

			add_meta_box(
				'wps-plan-products',
				esc_html__( 'Grant Methods', 'subscriptions-for-woocommerce' ),
				array( $this, 'render_products_meta_box' ),
				$cpt,
				'normal',
				'default'
			);

			add_meta_box(
				'wps-plan-summary',
				esc_html__( 'Plan Summary', 'subscriptions-for-woocommerce' ),
				array( $this, 'render_summary_meta_box' ),
				$cpt,
				'side',
				'default'
			);
		}

		/**
		 * Render the Plan Details meta box.
		 *
		 * @since 2.0.0
		 * @param WP_Post $post Current post object.
		 */
		public function render_details_meta_box( $post ) {
			$wps_plan = wps_get_plan( $post->ID );
			require SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
				. 'admin/partials/membership/meta-box-plan-details.php';
		}

		/**
		 * Render the Access Length meta box.
		 *
		 * @since 2.0.0
		 * @param WP_Post $post Current post object.
		 */
		public function render_access_meta_box( $post ) {
			$wps_plan = wps_get_plan( $post->ID );
			require SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
				. 'admin/partials/membership/meta-box-plan-access.php';
		}

		/**
		 * Render the Linked Products meta box.
		 *
		 * @since 2.0.0
		 * @param WP_Post $post Current post object.
		 */
		public function render_products_meta_box( $post ) {
			$wps_plan = wps_get_plan( $post->ID );
			require SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
				. 'admin/partials/membership/meta-box-plan-products.php';
		}

		/**
		 * Render the read-only Plan Summary side panel.
		 *
		 * @since 2.0.0
		 * @param WP_Post $post Current post object.
		 */
		public function render_summary_meta_box( $post ) {
			$wps_plan = wps_get_plan( $post->ID );
			require SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
				. 'admin/partials/membership/meta-box-plan-summary.php';
		}

		/**
		 * Persist meta box values on post save.
		 *
		 * Hooked to `save_post` at priority 10.
		 *
		 * @since 2.0.0
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 */
		public function save_meta_boxes( $post_id, $post ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}
			if ( WPS_MEMBERSHIP_PLAN_CPT !== $post->post_type ) {
				return;
			}

			$nonce = isset( $_POST[ self::NONCE_FIELD ] )
				? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) )
				: '';
			if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Slug — regenerate for uniqueness only when the value changes.
			if ( isset( $_POST['_wps_plan_slug'] ) ) {
				$new_slug = sanitize_title( wp_unslash( $_POST['_wps_plan_slug'] ) );
				$old_slug = get_post_meta( $post_id, '_wps_plan_slug', true );
				if ( $new_slug !== $old_slug ) {
					$new_slug = wps_generate_unique_plan_slug(
						$new_slug ? $new_slug : $post->post_title,
						$post_id
					);
				}
				update_post_meta( $post_id, '_wps_plan_slug', $new_slug );
			}

			// Status.
			if ( isset( $_POST['_wps_plan_status'] ) ) {
				$status = 'inactive' === $_POST['_wps_plan_status'] ? 'inactive' : 'active';
				update_post_meta( $post_id, '_wps_plan_status', $status );
			}

			// Color.
			$color = isset( $_POST['_wps_plan_color'] )
				? sanitize_hex_color( wp_unslash( $_POST['_wps_plan_color'] ) )
				: '';
			update_post_meta( $post_id, '_wps_plan_color', $color ? $color : '' );

			// Access length.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_length = isset( $_POST['_wps_plan_access_length'] ) && is_array( $_POST['_wps_plan_access_length'] )
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['_wps_plan_access_length'] ) )
				: array();
			update_post_meta( $post_id, '_wps_plan_access_length', wps_sanitize_access_length( $raw_length ) );

			// Grant method — single mutually exclusive selection.
			$allowed_methods = array( 'purchase', 'subscription', 'auto_enroll' );
			$grant_method    = isset( $_POST['_wps_plan_grant_method'] )
				? sanitize_key( wp_unslash( $_POST['_wps_plan_grant_method'] ) )
				: 'purchase';
			if ( ! in_array( $grant_method, $allowed_methods, true ) ) {
				$grant_method = 'purchase';
			}
			update_post_meta( $post_id, '_wps_plan_grant_method', $grant_method );

			// Purchase products — rebuild map only when the list changes.
			$old_products = (array) get_post_meta( $post_id, '_wps_plan_products', true );
			$products     = isset( $_POST['_wps_plan_products'] ) && is_array( $_POST['_wps_plan_products'] )
				? array_values( array_filter( array_map( 'absint', wp_unslash( $_POST['_wps_plan_products'] ) ) ) )
				: array();
			update_post_meta( $post_id, '_wps_plan_products', $products );

			// Subscription product — single selection stored as a one-element array.
			$old_sub_products = (array) get_post_meta( $post_id, '_wps_plan_sub_products', true );
			$sub_product_id   = isset( $_POST['_wps_plan_sub_product'] )
				? absint( $_POST['_wps_plan_sub_product'] )
				: 0;
			$sub_products     = $sub_product_id ? array( $sub_product_id ) : array();
			update_post_meta( $post_id, '_wps_plan_sub_products', $sub_products );

			if ( $products !== $old_products || $sub_products !== $old_sub_products ) {
				wps_rebuild_product_plan_map();
			}

			// Auto-enroll.
			$auto_enroll = isset( $_POST['_wps_plan_auto_enroll'] )
				&& '1' === sanitize_text_field( wp_unslash( $_POST['_wps_plan_auto_enroll'] ) )
				? '1' : '0';
			update_post_meta( $post_id, '_wps_plan_auto_enroll', $auto_enroll );
		}
	}
}
