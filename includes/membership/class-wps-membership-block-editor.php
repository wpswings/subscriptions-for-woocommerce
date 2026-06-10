<?php
/**
 * Membership Layer — Gutenberg block restriction editor integration (Day 17).
 *
 * Owns the editor-side UI for the "restrict this block to members" Pro feature.
 * Following the Pro-visibility rule, the controls always render in the editor:
 * the localized `isPro` flag tells the JS whether to enable them or show them
 * locked with the "Pro" tag. The Pro plugin supplies only the frontend
 * `render_block` enforcement (see WPS_Block_Restriction), so when Pro is absent
 * the controls appear disabled and nothing is enforced.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Membership_Block_Editor' ) ) {

	/**
	 * Registers block attributes and enqueues the block-editor controls.
	 *
	 * @since 2.0.0
	 */
	class WPS_Membership_Block_Editor {

		/**
		 * Script handle for the editor integration.
		 *
		 * @var string
		 */
		const HANDLE = 'wps-membership-block-restriction';

		/**
		 * Declare the membership attributes on every server-registered block.
		 *
		 * The editor JS injects the same attributes for blocks registered only in
		 * JS (most core blocks); this server-side pass keeps dynamic/server blocks
		 * in sync. Hooked to `register_block_type_args`.
		 *
		 * @since  2.0.0
		 * @param  array  $args       Block type registration args.
		 * @param  string $block_type Block type name (unused).
		 * @return array
		 */
		public function add_block_attributes( $args, $block_type ) {
			unset( $block_type );

			if ( ! isset( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
				$args['attributes'] = array();
			}
			if ( ! isset( $args['attributes']['wpsRestricted'] ) ) {
				$args['attributes']['wpsRestricted'] = array(
					'type'    => 'boolean',
					'default' => false,
				);
			}
			if ( ! isset( $args['attributes']['wpsRequiredPlans'] ) ) {
				$args['attributes']['wpsRequiredPlans'] = array(
					'type'    => 'array',
					'default' => array(),
				);
			}

			return $args;
		}

		/**
		 * Enqueue the block-editor controls and localize the plan list + Pro flag.
		 *
		 * Hooked to `enqueue_block_editor_assets` (editor context only).
		 *
		 * @since 2.0.0
		 */
		public function enqueue_editor_assets() {
			wp_enqueue_script(
				self::HANDLE,
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-block-restriction.js',
				array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION,
				true
			);

			$plans = array();
			if ( function_exists( 'wps_get_all_plans' ) ) {
				foreach ( (array) wps_get_all_plans( 'active' ) as $plan ) {
					if ( empty( $plan['slug'] ) ) {
						continue;
					}
					$plans[] = array(
						'slug' => $plan['slug'],
						'name' => isset( $plan['name'] ) ? $plan['name'] : $plan['slug'],
					);
				}
			}

			wp_localize_script(
				self::HANDLE,
				'wpsBlockRestriction',
				array(
					'isPro' => (bool) apply_filters( 'wsp_sfw_check_pro_plugin', false ),
					'plans' => $plans,
					'i18n'  => array(
						'panelTitle'    => __( 'Membership Restriction', 'subscriptions-for-woocommerce' ),
						'restrict'      => __( 'Restrict this block to members', 'subscriptions-for-woocommerce' ),
						'requiredPlans' => __( 'Required plans', 'subscriptions-for-woocommerce' ),
						'anyPlanHint'   => __(
							'Leave all unchecked to allow any active membership.',
							'subscriptions-for-woocommerce'
						),
						'noPlans'       => __( 'No membership plans found.', 'subscriptions-for-woocommerce' ),
						'proNotice'     => __(
							'Upgrade to Pro to restrict blocks by membership.',
							'subscriptions-for-woocommerce'
						),
						'proTag'        => __( 'Pro', 'subscriptions-for-woocommerce' ),
					),
				)
			);
		}

		/**
		 * Enqueue the membership notice stylesheet on the frontend when the
		 * current singular post contains a restricted block.
		 *
		 * The Pro plugin renders the restriction notice on `render_block`, which
		 * is too late to enqueue head styles, so the decision is made here at
		 * `wp_enqueue_scripts` via a cheap marker check on the post content. Only
		 * runs when Pro is active, since nothing is enforced otherwise.
		 *
		 * Hooked to `wp_enqueue_scripts`.
		 *
		 * @since 2.0.0
		 */
		public function maybe_enqueue_frontend_styles() {
			if ( is_admin() || ! is_singular() ) {
				return;
			}
			if ( ! apply_filters( 'wsp_sfw_check_pro_plugin', false ) ) {
				return;
			}

			$post = get_queried_object();
			if ( ! $post instanceof WP_Post ) {
				return;
			}
			if ( false === strpos( (string) $post->post_content, '"wpsRestricted":true' ) ) {
				return;
			}

			wp_enqueue_style(
				'wps-membership-badges',
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'public/css/wps-membership-badges.css',
				array(),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
			);
		}
	}
}
