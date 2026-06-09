<?php
/**
 * Meta box partial — Access Length (lifetime vs fixed duration).
 *
 * Variables available from WPS_Membership_Plan_CPT::render_access_meta_box():
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

$wps_access  = $wps_plan ? $wps_plan['access_length'] : array(
	'type'  => 'lifetime',
	'value' => 1,
	'unit'  => 'month',
);
$wps_type    = isset( $wps_access['type'] ) ? $wps_access['type'] : 'lifetime';
$wps_value   = isset( $wps_access['value'] ) ? absint( $wps_access['value'] ) : 1;
$wps_unit    = isset( $wps_access['unit'] ) ? $wps_access['unit'] : 'month';
$wps_is_fixed = 'fixed' === $wps_type;
?>
<div class="wps-plan-access-wrap">

	<p>
		<label>
			<input
				type="radio"
				name="_wps_plan_access_length[type]"
				value="lifetime"
				<?php checked( $wps_is_fixed, false ); ?>
				class="wps-access-type-radio"
			/>
			<?php esc_html_e( 'Lifetime access', 'subscriptions-for-woocommerce' ); ?>
		</label>
	</p>

	<p>
		<label>
			<input
				type="radio"
				name="_wps_plan_access_length[type]"
				value="fixed"
				<?php checked( $wps_is_fixed, true ); ?>
				class="wps-access-type-radio"
			/>
			<?php esc_html_e( 'Fixed duration', 'subscriptions-for-woocommerce' ); ?>
		</label>
	</p>

	<div id="wps-fixed-duration-row" style="<?php echo $wps_is_fixed ? '' : 'display:none;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>margin-left:24px;margin-top:6px;">
		<input
			type="number"
			name="_wps_plan_access_length[value]"
			value="<?php echo absint( $wps_value ); ?>"
			min="1"
			step="1"
			style="width:70px;"
			class="small-text"
			<?php echo $wps_is_fixed ? '' : 'disabled'; ?>
		/>

		<select name="_wps_plan_access_length[unit]">
			<option value="day" <?php selected( $wps_unit, 'day' ); ?>>
				<?php esc_html_e( 'Day(s)', 'subscriptions-for-woocommerce' ); ?>
			</option>
			<option value="month" <?php selected( $wps_unit, 'month' ); ?>>
				<?php esc_html_e( 'Month(s)', 'subscriptions-for-woocommerce' ); ?>
			</option>
			<option value="year" <?php selected( $wps_unit, 'year' ); ?>>
				<?php esc_html_e( 'Year(s)', 'subscriptions-for-woocommerce' ); ?>
			</option>
		</select>

		<p class="description">
			<?php esc_html_e( 'Access expires this many days/months/years after the membership is granted.', 'subscriptions-for-woocommerce' ); ?>
		</p>
	</div>

</div>

<!-- Shown when grant method = subscription; hidden otherwise -->
<div id="wps-access-sub-preview" style="display:none;">
	<div class="wps-access-sub-preview__header">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2" stroke-linecap="round"
			stroke-linejoin="round" aria-hidden="true">
			<polyline points="23 4 23 10 17 10"></polyline>
			<polyline points="1 20 1 14 7 14"></polyline>
			<path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
		</svg>
		<?php esc_html_e( 'Subscription Billing', 'subscriptions-for-woocommerce' ); ?>
	</div>
	<div id="wps-access-sub-preview__body">
		<p class="wps-access-sub-preview__empty">
			<?php
			esc_html_e(
				'Select a subscription product above to preview its billing period.',
				'subscriptions-for-woocommerce'
			);
			?>
		</p>
	</div>
</div>

<script>
( function () {
	var radios  = document.querySelectorAll( '.wps-access-type-radio' );
	var row     = document.getElementById( 'wps-fixed-duration-row' );
	if ( ! row ) return;
	var numInput = row.querySelector( 'input[type="number"]' );
	radios.forEach( function ( radio ) {
		radio.addEventListener( 'change', function () {
			var isFixed = ( 'fixed' === this.value );
			row.style.display = isFixed ? '' : 'none';
			if ( numInput ) {
				numInput.disabled = ! isFixed;
			}
		} );
	} );
} )();
</script>
