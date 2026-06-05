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
				esc_html__( 'Linked Products', 'subscriptions-for-woocommerce' ),
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

			// Products — rebuild map only when the list changes.
			$old_products = (array) get_post_meta( $post_id, '_wps_plan_products', true );
			$products     = isset( $_POST['_wps_plan_products'] ) && is_array( $_POST['_wps_plan_products'] )
				? array_values( array_filter( array_map( 'absint', wp_unslash( $_POST['_wps_plan_products'] ) ) ) )
				: array();
			update_post_meta( $post_id, '_wps_plan_products', $products );
			if ( $products !== $old_products ) {
				wps_rebuild_product_plan_map();
			}
		}
	}
}
