<?php
/**
 * Meta box partial — Plan Details (slug, status, color).
 *
 * Variables available from WPS_Membership_Plan_CPT::render_details_meta_box():
 *   $post     WP_Post  Current plan post.
 *   $wps_plan array    Normalised plan data from wps_get_plan(), or null for a new post.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wps_status = $wps_plan ? $wps_plan['status'] : 'active';
$wps_color  = $wps_plan ? esc_attr( $wps_plan['color'] ) : '';

/**
 * Whether Subscriptions for WooCommerce Pro is active.
 *
 * Drives the locked/disabled state of the Pro-only role-assignment fields below
 * (Day 19). The field markup always renders; the Pro plugin supplies the
 * add_role()/remove_role() enforcement once unlocked.
 *
 * @since 2.0.0
 *
 * @param bool $is_active Default false; Pro flips this true when present.
 */
$wps_role_is_pro   = (bool) apply_filters( 'wsp_sfw_check_pro_plugin', false );
$wps_role_lock_cls = $wps_role_is_pro ? '' : ' wps_pro_settings_tag wps-ai-pro-locked';
$wps_role_disabled = $wps_role_is_pro ? '' : ' disabled';
$wps_role_value    = ( $wps_plan && ! empty( $wps_plan['user_role'] ) ) ? $wps_plan['user_role'] : '';
$wps_role_remove   = ( $wps_plan && ! empty( $wps_plan['remove_role'] ) && '1' === (string) $wps_plan['remove_role'] );
$wps_role_names    = function_exists( 'wp_roles' ) ? wp_roles()->get_names() : array();

wp_nonce_field( WPS_Membership_Plan_CPT::NONCE_ACTION, WPS_Membership_Plan_CPT::NONCE_FIELD );
?>
<table class="form-table wps-plan-details-table">
	<tbody>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Status', 'subscriptions-for-woocommerce' ); ?>
			</th>
			<td>
				<label>
					<input
						type="radio"
						name="_wps_plan_status"
						value="active"
						<?php checked( $wps_status, 'active' ); ?>
					/>
					<?php esc_html_e( 'Active', 'subscriptions-for-woocommerce' ); ?>
				</label>
				&nbsp;&nbsp;
				<label>
					<input
						type="radio"
						name="_wps_plan_status"
						value="inactive"
						<?php checked( $wps_status, 'inactive' ); ?>
					/>
					<?php esc_html_e( 'Inactive', 'subscriptions-for-woocommerce' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Inactive plans are hidden from customers and block new memberships.', 'subscriptions-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="wps_plan_color">
					<?php esc_html_e( 'Badge Color', 'subscriptions-for-woocommerce' ); ?>
				</label>
			</th>
			<td>
				<input
					type="color"
					id="wps_plan_color"
					name="_wps_plan_color"
					value="<?php echo esc_attr( $wps_color ? $wps_color : '#0073aa' ); ?>"
				/>
				<p class="description">
					<?php esc_html_e( 'Optional color shown as a badge next to the plan name in the admin list.', 'subscriptions-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="wps_plan_user_role">
					<?php esc_html_e( 'Member Role', 'subscriptions-for-woocommerce' ); ?>
				</label>
			</th>
			<td>
				<div class="wps-plan-roles<?php echo esc_attr( $wps_role_lock_cls ); ?>"
					data-wps-pro-locked="<?php echo esc_attr( $wps_role_is_pro ? '0' : '1' ); ?>">
					<select id="wps_plan_user_role" name="_wps_plan_user_role"
						<?php echo esc_attr( $wps_role_disabled ); ?>
					>
						<option value="">
							<?php esc_html_e( '— No role change —', 'subscriptions-for-woocommerce' ); ?>
						</option>
						<?php foreach ( $wps_role_names as $wps_role_slug => $wps_role_label ) : ?>
							<option value="<?php echo esc_attr( $wps_role_slug ); ?>"
								<?php selected( $wps_role_value, $wps_role_slug ); ?>>
								<?php echo esc_html( translate_user_role( $wps_role_label ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php
						esc_html_e(
							'Role added to the member while this plan is active (existing roles are kept).',
							'subscriptions-for-woocommerce'
						);
						?>
					</p>
					<label class="wps-plan-roles__remove">
						<input type="hidden" name="_wps_plan_remove_role" value="0">
						<input type="checkbox" name="_wps_plan_remove_role" value="1"
							<?php checked( $wps_role_remove ); ?>
							<?php echo esc_attr( $wps_role_disabled ); ?>
						>
						<?php
						esc_html_e(
							'Remove this role when the membership is cancelled or expires',
							'subscriptions-for-woocommerce'
						);
						?>
					</label>
				</div>
			</td>
		</tr>

	</tbody>
</table>
