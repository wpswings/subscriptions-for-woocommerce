<?php
/**
 * Membership Layer — Access Rule template-behavior teaser fields (Day 18, Pro feature UI).
 *
 * Sub-fields for the "Template" restriction behavior: a teaser mode (none /
 * word-count) plus the word count used by the word-count mode. The
 * markup is owned by the Free plugin and ALWAYS renders inside the Access Rules
 * wizard, sitting in the Behavior step alongside the message / redirect inputs.
 * When the Pro plugin is inactive the controls show the "Pro" badge and are
 * disabled via the established `wps_pro_settings_tag` + `wps-ai-pro-locked`
 * pattern; the Pro plugin supplies the enforcement that runs once unlocked.
 *
 * The wrapper is hidden unless the rule's behavior is 'template' — `wps-access-rules.js`
 * toggles `.wps-behavior-template` visibility in lockstep with the behavior radios.
 *
 * Expected in scope (set by the including template before each `require`):
 *   string $wps_idx      Row index token — a real integer for stored rules
 *                        or the literal '__IDX__' for the JS row template.
 *   array  $wps_rule_tpl The rule array being rendered (empty for the template).
 *   bool   $wps_tpl_show Whether the wrapper should be visible on load.
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
$wps_rule_tpl = isset( $wps_rule_tpl ) && is_array( $wps_rule_tpl ) ? $wps_rule_tpl : array();
$wps_tpl_show = ! empty( $wps_tpl_show );

// Pro gate — when inactive, lock the controls and show the "Pro" badge.
$wps_tpl_is_pro   = (bool) apply_filters( 'wsp_sfw_check_pro_plugin', false );
$wps_tpl_lock_cls = $wps_tpl_is_pro ? '' : ' wps_pro_settings_tag wps-ai-pro-locked';
$wps_tpl_disabled = $wps_tpl_is_pro ? '' : ' disabled';

// Current values (defaults match wps_sanitize_access_rule()).
$wps_teaser_mode  = isset( $wps_rule_tpl['teaser_mode'] ) ? (string) $wps_rule_tpl['teaser_mode'] : 'none';
$wps_teaser_words = isset( $wps_rule_tpl['teaser_words'] ) ? (int) $wps_rule_tpl['teaser_words'] : 0;

// Field names (index is interpolated, never user input here).
$wps_fld_teaser_mode  = 'wps_rules[' . $wps_idx . '][teaser_mode]';
$wps_fld_teaser_words = 'wps_rules[' . $wps_idx . '][teaser_words]';

// Inline display toggles: the wrapper follows the selected behavior; the
// word-count input follows the selected teaser mode (JS keeps both in sync).
$wps_wrap_style  = $wps_tpl_show ? '' : ' style="display:none;"';
$wps_words_style = 'words' === $wps_teaser_mode ? '' : ' style="display:none;"';
?>
<div class="wps-behavior-template wps-field-group<?php echo esc_attr( $wps_tpl_lock_cls ); ?>"
	data-wps-pro-locked="<?php echo esc_attr( $wps_tpl_is_pro ? '0' : '1' ); ?>"
	<?php echo $wps_wrap_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<span class="wps-field-label">
		<?php esc_html_e( 'Teaser', 'subscriptions-for-woocommerce' ); ?>
	</span>

	<select name="<?php echo esc_attr( $wps_fld_teaser_mode ); ?>"
		class="wps-rule-teaser-mode"<?php echo $wps_tpl_disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<option value="none" <?php selected( $wps_teaser_mode, 'none' ); ?>>
			<?php esc_html_e( 'No teaser — show the restriction notice only', 'subscriptions-for-woocommerce' ); ?>
		</option>
		<option value="words" <?php selected( $wps_teaser_mode, 'words' ); ?>>
			<?php esc_html_e( 'Show the first N words of the content', 'subscriptions-for-woocommerce' ); ?>
		</option>
	</select>

	<div class="wps-teaser-words-field" style="margin-top:6px;"<?php echo $wps_words_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<label>
			<?php esc_html_e( 'Number of words:', 'subscriptions-for-woocommerce' ); ?>
			<input type="number" min="0" max="5000"
				name="<?php echo esc_attr( $wps_fld_teaser_words ); ?>"
				value="<?php echo esc_attr( $wps_teaser_words ); ?>"
				class="wps-rule-teaser-words"<?php echo $wps_tpl_disabled; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		</label>
	</div>

	<p class="description" style="margin-top:4px;">
		<?php
		esc_html_e(
			'Non-members see this teaser on a dedicated page template, followed by the restriction message above.',
			'subscriptions-for-woocommerce'
		);
		?>
	</p>
</div>
