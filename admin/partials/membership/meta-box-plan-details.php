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

$wps_slug   = $wps_plan ? esc_attr( $wps_plan['slug'] ) : '';
$wps_status = $wps_plan ? $wps_plan['status'] : 'active';
$wps_color  = $wps_plan ? esc_attr( $wps_plan['color'] ) : '';

wp_nonce_field( WPS_Membership_Plan_CPT::NONCE_ACTION, WPS_Membership_Plan_CPT::NONCE_FIELD );
?>
<table class="form-table wps-plan-details-table">
	<tbody>

		<tr>
			<th scope="row">
				<label for="wps_plan_slug">
					<?php esc_html_e( 'Slug', 'subscriptions-for-woocommerce' ); ?>
				</label>
			</th>
			<td>
				<input
					type="text"
					id="wps_plan_slug"
					name="_wps_plan_slug"
					value="<?php echo $wps_slug; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
					class="regular-text"
				/>
				<p class="description">
					<?php esc_html_e( 'Unique identifier used in code. Auto-generated from the plan name if left unchanged.', 'subscriptions-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>

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
					value="<?php echo $wps_color ? $wps_color : '#0073aa'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
				/>
				<p class="description">
					<?php esc_html_e( 'Optional color shown as a badge next to the plan name in the admin list.', 'subscriptions-for-woocommerce' ); ?>
				</p>
			</td>
		</tr>

	</tbody>
</table>
