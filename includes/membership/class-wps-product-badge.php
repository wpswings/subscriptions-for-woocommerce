<?php
/**
 * Membership Layer — Product Badge
 *
 * Adds a "Members Only" overlay badge to restricted products in the shop
 * loop and a membership-gate panel with plan details on single product pages.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Product_Badge' ) ) {

	/**
	 * Renders membership badges on product listings and single product pages.
	 *
	 * @since 2.0.0
	 */
	class WPS_Product_Badge {

		// -----------------------------------------------------------------------
		// Assets
		// -----------------------------------------------------------------------

		/**
		 * Enqueue the badge stylesheet on WooCommerce frontend pages.
		 *
		 * @since 2.0.0
		 */
		public function enqueue_styles() {
			if ( ! function_exists( 'is_woocommerce' ) ) {
				return;
			}
			if ( ! is_woocommerce() && ! is_shop() && ! is_product() ) {
				return;
			}

			wp_enqueue_style(
				'wps-membership-badges',
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'public/css/wps-membership-badges.css',
				array(),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
			);

			// Inline script: set position:relative on the parent li.product for each
			// badge so position:absolute works on any theme (block or classic).
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_register_script( 'wps-membership-badges', false, array(), null, true );
			wp_enqueue_script( 'wps-membership-badges' );
			wp_add_inline_script(
				'wps-membership-badges',
				'document.addEventListener("DOMContentLoaded",function(){' .
				'document.querySelectorAll(".wps-members-badge").forEach(function(b){' .
				'var li=b.closest("li.product");if(li)li.style.position="relative";' .
				'});});'
			);
		}

		// -----------------------------------------------------------------------
		// Shop / archive loop — "Members Only" pill
		// -----------------------------------------------------------------------

		/**
		 * Output a shop-loop badge on the product thumbnail.
		 *
		 * Hooked to `woocommerce_before_shop_loop_item_title` at priority 5 so
		 * the badge renders inside the product anchor before the image. CSS uses
		 * `ul.products li.product` (always present) as the `position: relative`
		 * ancestor, making positioning theme-independent.
		 *
		 * @since 2.0.0
		 */
		public function render_shop_badge() {
			global $product;

			if ( ! $product instanceof WC_Product ) {
				return;
			}

			$product_id = $product->get_id();

			// Plan-granting products → green "Unlocks Membership" badge.
			if ( function_exists( 'wps_product_actively_grants_membership' ) ) {
				$grant_slug = wps_product_actively_grants_membership( $product_id );
				if ( null !== $grant_slug ) {
					echo '<span class="wps-members-badge wps-members-badge--grant">'
						. '<svg class="wps-members-badge__icon" xmlns="http://www.w3.org/2000/svg"'
						. ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
						. ' stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"'
						. ' aria-hidden="true">'
						. '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77'
						. 'l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>'
						. '</svg>'
						. esc_html__( 'Unlocks Membership', 'subscriptions-for-woocommerce' )
						. '</span>';
					return;
				}
			}

			// Restricted products → blue "Members Only" badge.
			$post = get_post( $product_id );
			if ( ! $post || empty( wps_get_rules_for_object( $post ) ) ) {
				return;
			}

			echo '<span class="wps-members-badge">'
				. '<svg class="wps-members-badge__icon" xmlns="http://www.w3.org/2000/svg"'
				. ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
				. ' stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"'
				. ' aria-hidden="true">'
				. '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>'
				. '<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>'
				. '</svg>'
				. esc_html__( 'Members Only', 'subscriptions-for-woocommerce' )
				. '</span>';
		}

		// -----------------------------------------------------------------------
		// Single product page — membership gate panel
		// -----------------------------------------------------------------------

		/**
		 * Output the membership-gate panel on the single product summary.
		 *
		 * Shows a "Membership Required" header followed by a card for each
		 * required plan, including its name, description, and access duration.
		 *
		 * Hooked to `woocommerce_single_product_summary` at priority 25.
		 *
		 * @since 2.0.0
		 */
		public function render_product_page_plans() {
			global $product;

			if ( ! $product instanceof WC_Product ) {
				return;
			}

				// Plan-granting products show a "grant" panel instead of the gate.
			if ( function_exists( 'wps_product_actively_grants_membership' ) ) {
				$grant_slug = wps_product_actively_grants_membership( $product->get_id() );
				if ( null !== $grant_slug ) {
					// Subscription products: the public class hook renders the grant card.
					$is_sub = function_exists( 'wps_sfw_check_product_is_subscription' )
						&& wps_sfw_check_product_is_subscription( $product );
					if ( ! $is_sub ) {
						$this->render_grant_panel( $grant_slug );
					}
					return;
				}
			}

			$post = get_post( $product->get_id() );
			if ( ! $post ) {
				return;
			}

			$rules = wps_get_rules_for_object( $post );
			if ( empty( $rules ) ) {
				return;
			}

			// Collect unique plans, preserving first-seen order.
			$plans = array();
			foreach ( $rules as $rule ) {
				if ( empty( $rule['plans'] ) || ! is_array( $rule['plans'] ) ) {
					continue;
				}
				foreach ( $rule['plans'] as $slug ) {
					if ( isset( $plans[ $slug ] ) ) {
						continue;
					}
					// 'any' is a special sentinel meaning "any active membership".
					if ( 'any' === $slug ) {
						$plans['any'] = array(
							'name'        => __( 'Any Active Membership', 'subscriptions-for-woocommerce' ),
							'description' => __(
								'You need any active membership plan to access this product.',
								'subscriptions-for-woocommerce'
							),
							'color'       => '#2271b1',
						);
						continue;
					}
					$plan_data      = function_exists( 'wps_get_plan_by_slug' )
						? wps_get_plan_by_slug( $slug )
						: null;
					$plans[ $slug ] = $plan_data ? $plan_data : array( 'name' => ucfirst( $slug ) );
				}
			}

			if ( empty( $plans ) ) {
				return;
			}

			?>
			<div class="wps-membership-gate">

				<div class="wps-membership-gate__header">
					<svg class="wps-membership-gate__lock" xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 24 24" fill="none" stroke="currentColor"
						stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
						<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
					</svg>
					<span class="wps-membership-gate__title">
						<?php esc_html_e( 'Membership Required', 'subscriptions-for-woocommerce' ); ?>
					</span>
				</div>

				<p class="wps-membership-gate__intro">
					<?php
					esc_html_e(
						'This product is available to members of the following plan(s):',
						'subscriptions-for-woocommerce'
					);
					?>
				</p>

				<div class="wps-membership-gate__plans">
					<?php foreach ( $plans as $slug => $plan ) : ?>
						<?php
						$name        = ! empty( $plan['name'] ) ? $plan['name'] : ucfirst( $slug );
						$description = ! empty( $plan['description'] ) ? $plan['description'] : '';
						$color       = ! empty( $plan['color'] ) ? sanitize_hex_color( $plan['color'] ) : '#2271b1';
						$duration    = ( 'any' !== $slug )
							? $this->format_access_length(
								isset( $plan['access_length'] ) && is_array( $plan['access_length'] )
									? $plan['access_length']
									: array( 'type' => 'lifetime' )
							)
							: '';

						// Purchasable products that grant this specific plan.
						$cta_products = ( 'any' !== $slug && function_exists( 'wps_get_plan_purchasable_products' ) )
							? wps_get_plan_purchasable_products( $slug )
							: array();
						?>
						<div class="wps-plan-card"
							style="--plan-color: <?php echo esc_attr( $color ); ?>">

							<div class="wps-plan-card__top">
								<span class="wps-plan-card__dot" aria-hidden="true"></span>
								<span class="wps-plan-card__name"><?php echo esc_html( $name ); ?></span>
								<?php if ( $duration ) : ?>
									<span class="wps-plan-card__duration">
										<?php echo esc_html( $duration ); ?>
									</span>
								<?php endif; ?>
							</div>

							<?php if ( $description ) : ?>
								<p class="wps-plan-card__desc">
									<?php echo wp_kses_post( $description ); ?>
								</p>
							<?php endif; ?>

							<?php if ( ! empty( $cta_products ) ) : ?>
								<div class="wps-plan-card__cta">
									<span class="wps-plan-card__cta-label">
										<?php esc_html_e( 'Get access:', 'subscriptions-for-woocommerce' ); ?>
									</span>
									<?php foreach ( $cta_products as $cta_product ) : ?>
										<a class="wps-plan-card__cta-link"
											href="<?php echo esc_url( $cta_product->get_permalink() ); ?>">
											<?php echo esc_html( $cta_product->get_name() ); ?>
											&rarr;
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

						</div>
					<?php endforeach; ?>
				</div>

			</div>
			<?php
		}

		// -----------------------------------------------------------------------
		// Single product page — membership grant panel (plan-linked products)
		// -----------------------------------------------------------------------

		/**
		 * Render a "Purchasing this product grants you membership" info panel.
		 *
		 * Called from render_product_page_plans() when the current product is
		 * itself linked to a plan (i.e. buying it grants the plan).
		 *
		 * @since  2.0.0
		 * @param  string $plan_slug The plan slug linked to this product.
		 */
		private function render_grant_panel( $plan_slug ) {
			if ( ! function_exists( 'wps_get_plan_by_slug' )
				|| ! function_exists( 'wps_build_membership_card_html' )
			) {
				return;
			}

			$plan = wps_get_plan_by_slug( $plan_slug );
			if ( ! $plan ) {
				$plan = array(
					'name'          => ucfirst( $plan_slug ),
					'description'   => '',
					'color'         => '#1a7f4b',
					'grant_method'  => 'purchase',
					'access_length' => array( 'type' => 'lifetime' ),
				);
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wps_build_membership_card_html( array( $plan ), 'simple' );
		}

		// -----------------------------------------------------------------------
		// Private helpers
		// -----------------------------------------------------------------------

		/**
		 * Convert an access_length array to a human-readable string.
		 *
		 * @since  2.0.0
		 * @param  array $access { type: 'lifetime'|'fixed', value: int, unit: string }.
		 * @return string
		 */
		private function format_access_length( array $access ) {
			if ( empty( $access['type'] ) || 'lifetime' === $access['type'] ) {
				return __( 'Lifetime Access', 'subscriptions-for-woocommerce' );
			}

			$value = absint( isset( $access['value'] ) ? $access['value'] : 0 );
			$unit  = isset( $access['unit'] ) ? $access['unit'] : 'day';

			if ( $value <= 0 ) {
				return __( 'Lifetime Access', 'subscriptions-for-woocommerce' );
			}

			$units = array(
				'day'   => array(
					__( 'Day', 'subscriptions-for-woocommerce' ),
					__( 'Days', 'subscriptions-for-woocommerce' ),
				),
				'month' => array(
					__( 'Month', 'subscriptions-for-woocommerce' ),
					__( 'Months', 'subscriptions-for-woocommerce' ),
				),
				'year'  => array(
					__( 'Year', 'subscriptions-for-woocommerce' ),
					__( 'Years', 'subscriptions-for-woocommerce' ),
				),
			);

			$label = isset( $units[ $unit ] )
				? ( 1 === $value ? $units[ $unit ][0] : $units[ $unit ][1] )
				: ucfirst( $unit );

			/* translators: 1: number, 2: unit label (Day/Days/Month etc.) */
			return sprintf( __( '%1$d %2$s Access', 'subscriptions-for-woocommerce' ), $value, $label );
		}
	}
}
