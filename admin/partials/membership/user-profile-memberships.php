<?php
/**
 * User profile memberships section (Day 09).
 *
 * Included by WPS_Members_Admin::render_profile_section() which is hooked
 * to `show_user_profile` and `edit_user_profile`.
 *
 * Variables available from the calling method's local scope:
 *   $user  WP_User  The user whose profile is being viewed/edited.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wps_profile_user_id  = $user instanceof WP_User ? $user->ID : 0;
$wps_profile_nonce    = wp_create_nonce( 'wps_profile_membership_' . $wps_profile_user_id );
$wps_profile_plans    = wps_get_all_plans( 'active' );
$wps_profile_memberships = wps_get_user_memberships( $wps_profile_user_id, 'all' );
$wps_date_format      = get_option( 'date_format' );
?>

<h2><?php esc_html_e( 'Memberships', 'subscriptions-for-woocommerce' ); ?></h2>

<?php if ( ! empty( $wps_profile_memberships ) ) : ?>
<table class="widefat striped" style="max-width:900px;margin-bottom:16px;">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Plan', 'subscriptions-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Status', 'subscriptions-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Source', 'subscriptions-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Since', 'subscriptions-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Expires', 'subscriptions-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'subscriptions-for-woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $wps_profile_memberships as $wps_pm_row ) : ?>
			<?php
			$wps_pm_plan = wps_get_plan_by_slug( $wps_pm_row['plan_slug'] );
			$wps_pm_name = $wps_pm_plan ? esc_html( $wps_pm_plan['name'] ) : '<code>' . esc_html( $wps_pm_row['plan_slug'] ) . '</code>';

			$wps_pm_since = ! empty( $wps_pm_row['start_date'] )
				? esc_html( date_i18n( $wps_date_format, $wps_pm_row['start_date'] ) )
				: '—';

			$wps_pm_expires = empty( $wps_pm_row['expiry_date'] )
				? esc_html__( 'Lifetime', 'subscriptions-for-woocommerce' )
				: esc_html( date_i18n( $wps_date_format, $wps_pm_row['expiry_date'] ) );

			$wps_pm_is_active = in_array( $wps_pm_row['status'], array( 'active', 'on-hold' ), true );
			?>
			<tr>
				<td><?php echo $wps_pm_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				<td>
					<mark class="order-status status-<?php echo esc_attr( sanitize_html_class( $wps_pm_row['status'] ) ); ?>">
						<span><?php echo esc_html( ucfirst( $wps_pm_row['status'] ) ); ?></span>
					</mark>
				</td>
				<td><?php echo esc_html( ucfirst( $wps_pm_row['source'] ) ); ?></td>
				<td><?php echo $wps_pm_since; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				<td><?php echo $wps_pm_expires; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				<td>
					<?php if ( $wps_pm_is_active ) : ?>
						<form method="post" style="display:inline;">
							<input type="hidden" name="wps_profile_membership_nonce"
								value="<?php echo esc_attr( $wps_profile_nonce ); ?>">
							<input type="hidden" name="wps_profile_membership_action" value="revoke">
							<input type="hidden" name="wps_profile_action_plan"
								value="<?php echo esc_attr( $wps_pm_row['plan_slug'] ); ?>">
							<button type="submit" class="button button-small"
								onclick="return confirm( '<?php echo esc_js( __( 'Revoke this membership?', 'subscriptions-for-woocommerce' ) ); ?>' );">
								<?php esc_html_e( 'Revoke', 'subscriptions-for-woocommerce' ); ?>
							</button>
						</form>
					<?php else : ?>
						<form method="post" style="display:inline;">
							<input type="hidden" name="wps_profile_membership_nonce"
								value="<?php echo esc_attr( $wps_profile_nonce ); ?>">
							<input type="hidden" name="wps_profile_membership_action" value="reactivate">
							<input type="hidden" name="wps_profile_action_plan"
								value="<?php echo esc_attr( $wps_pm_row['plan_slug'] ); ?>">
							<button type="submit" class="button button-small">
								<?php esc_html_e( 'Reactivate', 'subscriptions-for-woocommerce' ); ?>
							</button>
						</form>
					<?php endif; ?>
					<form method="post" style="display:inline;">
						<input type="hidden" name="wps_profile_membership_nonce"
							value="<?php echo esc_attr( $wps_profile_nonce ); ?>">
						<input type="hidden" name="wps_profile_membership_action" value="remove">
						<input type="hidden" name="wps_profile_action_plan"
							value="<?php echo esc_attr( $wps_pm_row['plan_slug'] ); ?>">
						<button type="submit" class="button button-small button-link-delete"
							onclick="return confirm( '<?php echo esc_js( __( 'Permanently remove this membership record?', 'subscriptions-for-woocommerce' ) ); ?>' );">
							<?php esc_html_e( 'Remove', 'subscriptions-for-woocommerce' ); ?>
						</button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php else : ?>
<p class="description">
	<?php esc_html_e( 'This user has no memberships.', 'subscriptions-for-woocommerce' ); ?>
</p>
<?php endif; ?>

<?php if ( ! empty( $wps_profile_plans ) ) : ?>
<h3><?php esc_html_e( 'Grant Membership', 'subscriptions-for-woocommerce' ); ?></h3>
<form method="post" style="max-width:500px;">
	<input type="hidden" name="wps_profile_membership_nonce"
		value="<?php echo esc_attr( $wps_profile_nonce ); ?>">

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="wps_profile_grant_plan">
						<?php esc_html_e( 'Plan', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</th>
				<td>
					<select id="wps_profile_grant_plan" name="wps_profile_grant_plan" class="regular-text">
						<option value="">— <?php esc_html_e( 'Select a plan', 'subscriptions-for-woocommerce' ); ?> —</option>
						<?php foreach ( $wps_profile_plans as $wps_pp ) : ?>
							<option value="<?php echo esc_attr( $wps_pp['slug'] ); ?>">
								<?php echo esc_html( $wps_pp['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wps_profile_grant_expiry">
						<?php esc_html_e( 'Expiry Date', 'subscriptions-for-woocommerce' ); ?>
					</label>
				</th>
				<td>
					<input
						type="date"
						id="wps_profile_grant_expiry"
						name="wps_profile_grant_expiry"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Leave blank for lifetime.', 'subscriptions-for-woocommerce' ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<p>
		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Grant Membership', 'subscriptions-for-woocommerce' ); ?>
		</button>
	</p>
</form>
<?php endif; ?>
