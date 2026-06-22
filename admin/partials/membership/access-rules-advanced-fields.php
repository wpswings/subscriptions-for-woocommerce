<?php
/**
 * Membership Layer — Access Rule advanced fields (Day 16, Pro feature UI).
 *
 * Drip / scheduled access + rule exclusions. The markup is owned by the Free
 * plugin and ALWAYS renders inside the Access Rules wizard. When the Pro plugin
 * is inactive the controls are shown with the "Pro" badge and disabled via the
 * established `wps_pro_settings_tag` + `wps-ai-pro-locked` pattern; the Pro
 * plugin supplies the enforcement that runs once the controls are unlocked.
 *
 * Expected in scope (set by the including template before each `require`):
 *   string       $wps_idx      Row index token — a real integer for stored rules
 *                              or the literal '__IDX__' for the JS row template.
 *   array        $wps_rule_adv The rule array being rendered (empty for template).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Index token + rule data are provided by the including scope.
$wps_idx      = isset( $wps_idx ) ? $wps_idx : '__IDX__';
$wps_rule_adv = isset( $wps_rule_adv ) && is_array( $wps_rule_adv ) ? $wps_rule_adv : array();

// Pro gate — when inactive, lock the controls and show the "Pro" badge.
$wps_adv_is_pro   = (bool) apply_filters( 'wsp_sfw_check_pro_plugin', false );
$wps_adv_lock_cls = $wps_adv_is_pro ? '' : ' wps_pro_settings_tag wps-ai-pro-locked';
$wps_adv_disabled = $wps_adv_is_pro ? '' : ' disabled';

// Current values (defaults match wps_sanitize_access_rule()).
$wps_drip_mode    = isset( $wps_rule_adv['drip_mode'] ) ? (string) $wps_rule_adv['drip_mode'] : 'none';
$wps_drip_days    = isset( $wps_rule_adv['drip_days'] ) ? (int) $wps_rule_adv['drip_days'] : 0;
$wps_drip_date    = isset( $wps_rule_adv['drip_date'] ) ? (string) $wps_rule_adv['drip_date'] : '';
$wps_exclude_ids  = isset( $wps_rule_adv['exclude_ids'] ) && is_array( $wps_rule_adv['exclude_ids'] )
	? implode( ', ', array_map( 'absint', $wps_rule_adv['exclude_ids'] ) )
	: '';

// Field name fragments (index is interpolated, never user input here).
$wps_fld_drip_mode = 'wps_rules[' . $wps_idx . '][drip_mode]';
$wps_fld_drip_days = 'wps_rules[' . $wps_idx . '][drip_days]';
$wps_fld_drip_date = 'wps_rules[' . $wps_idx . '][drip_date]';
$wps_fld_exclude   = 'wps_rules[' . $wps_idx . '][exclude_ids]';

// Inline display toggles for the drip sub-fields (JS keeps them in sync).
$wps_days_style = 'days' === $wps_drip_mode ? '' : ' style="display:none;"';
$wps_date_style = 'date' === $wps_drip_mode ? '' : ' style="display:none;"';
?>
<div class="wps-field-group wps-advanced-fields<?php echo esc_attr( $wps_adv_lock_cls ); ?>"
	data-wps-pro-locked="<?php echo esc_attr( $wps_adv_is_pro ? '0' : '1' ); ?>">

	<span class="wps-field-label">
		<?php esc_html_e( 'Drip / Scheduled Access', 'subscriptions-for-woocommerce' ); ?>
	</span>

	<select name="<?php echo esc_attr( $wps_fld_drip_mode ); ?>"
		class="wps-rule-drip-mode"<?php echo esc_attr( $wps_adv_disabled ); ?>>
		<option value="none" <?php selected( $wps_drip_mode, 'none' ); ?>>
			<?php esc_html_e( 'No scheduling — grant immediately', 'subscriptions-for-woocommerce' ); ?>
		</option>
		<option value="days" <?php selected( $wps_drip_mode, 'days' ); ?>>
			<?php esc_html_e( 'Unlock a number of days after membership starts', 'subscriptions-for-woocommerce' ); ?>
		</option>
		<option value="date" <?php selected( $wps_drip_mode, 'date' ); ?>>
			<?php esc_html_e( 'Unlock on a fixed calendar date', 'subscriptions-for-woocommerce' ); ?>
		</option>
	</select>

	<div class="wps-drip-days-field" style="margin-top:6px;"<?php echo esc_attr( $wps_days_style ); ?>>
		<label>
			<?php esc_html_e( 'Days after membership start:', 'subscriptions-for-woocommerce' ); ?>
			<input type="number" min="0" max="3650"
				name="<?php echo esc_attr( $wps_fld_drip_days ); ?>"
				value="<?php echo esc_attr( $wps_drip_days ); ?>"
				class="wps-rule-drip-days"<?php echo esc_attr( $wps_adv_disabled ); ?>>
		</label>
	</div>

	<div class="wps-drip-date-field" style="margin-top:6px;"<?php echo esc_attr( $wps_date_style ); ?>>
		<label>
			<?php esc_html_e( 'Unlock on:', 'subscriptions-for-woocommerce' ); ?>
			<input type="date"
				name="<?php echo esc_attr( $wps_fld_drip_date ); ?>"
				value="<?php echo esc_attr( $wps_drip_date ); ?>"
				class="wps-rule-drip-date"<?php echo esc_attr( $wps_adv_disabled ); ?>>
		</label>
	</div>

	<p class="description" style="margin-top:4px;">
		<?php
		esc_html_e(
			'Members only see this content once the schedule above is reached.',
			'subscriptions-for-woocommerce'
		);
		?>
	</p>
</div>

<div class="wps-field-group wps-advanced-fields<?php echo esc_attr( $wps_adv_lock_cls ); ?>"
	data-wps-pro-locked="<?php echo esc_attr( $wps_adv_is_pro ? '0' : '1' ); ?>">

	<span class="wps-field-label">
		<?php esc_html_e( 'Exclusions', 'subscriptions-for-woocommerce' ); ?>
	</span>

	<input type="text"
		name="<?php echo esc_attr( $wps_fld_exclude ); ?>"
		value="<?php echo esc_attr( $wps_exclude_ids ); ?>"
		class="wps-rule-exclude-ids"
		placeholder="<?php esc_attr_e( 'e.g. 42, 108, 256', 'subscriptions-for-woocommerce' ); ?>"
		<?php echo esc_attr( $wps_adv_disabled ); ?>>

	<p class="description" style="margin-top:4px;">
		<?php
		esc_html_e(
			'Comma-separated post / product IDs to exempt from this rule (restrict everything except these).',
			'subscriptions-for-woocommerce'
		);
		?>
	</p>
</div>
