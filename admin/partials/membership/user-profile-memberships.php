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

$wps_profile_user_id     = $user instanceof WP_User ? $user->ID : 0;
$wps_profile_nonce       = wp_create_nonce( 'wps_profile_membership_' . $wps_profile_user_id );
$wps_profile_plans       = wps_get_all_plans( 'active' );
$wps_profile_memberships = wps_get_user_memberships( $wps_profile_user_id, 'all' );
$wps_date_format         = get_option( 'date_format' );
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
			$wps_pm_name = $wps_pm_plan
				? esc_html( $wps_pm_plan['name'] )
				: '<code>' . esc_html( $wps_pm_row['plan_slug'] ) . '</code>';

			$wps_pm_since = ! empty( $wps_pm_row['start_date'] )
				? esc_html( date_i18n( $wps_date_format, $wps_pm_row['start_date'] ) )
				: '—';

			$wps_pm_expires = empty( $wps_pm_row['expiry_date'] )
				? esc_html__( 'Lifetime', 'subscriptions-for-woocommerce' )
				: esc_html( date_i18n( $wps_date_format, $wps_pm_row['expiry_date'] ) );

			$wps_pm_is_active = in_array( $wps_pm_row['status'], array( 'active', 'on-hold' ), true );
			$wps_pm_slug      = $wps_pm_row['plan_slug'];

			$wps_toggle_action = $wps_pm_is_active ? 'revoke' : 'reactivate';
			$wps_toggle_label  = $wps_pm_is_active
				? esc_html__( 'Revoke', 'subscriptions-for-woocommerce' )
				: esc_html__( 'Reactivate', 'subscriptions-for-woocommerce' );
			$wps_toggle_url    = wp_nonce_url(
				add_query_arg(
					array(
						'wps_profile_mem_action'  => $wps_toggle_action,
						'user_id'                 => $wps_profile_user_id,
						'wps_profile_action_plan' => $wps_pm_slug,
					),
					get_edit_user_link( $wps_profile_user_id )
				),
				'wps_profile_mem_' . $wps_toggle_action . '_' . $wps_profile_user_id . '_' . $wps_pm_slug
			);

			$wps_remove_url = wp_nonce_url(
				add_query_arg(
					array(
						'wps_profile_mem_action'  => 'remove',
						'user_id'                 => $wps_profile_user_id,
						'wps_profile_action_plan' => $wps_pm_slug,
					),
					get_edit_user_link( $wps_profile_user_id )
				),
				'wps_profile_mem_remove_' . $wps_profile_user_id . '_' . $wps_pm_slug
			);
			?>
			<tr>
				<td><?php echo $wps_pm_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				<td>
					<?php $wps_status_class = esc_attr( sanitize_html_class( $wps_pm_row['status'] ) ); ?>
					<mark class="order-status status-<?php echo $wps_status_class; // phpcs:ignore ?>">
						<span><?php echo esc_html( ucfirst( $wps_pm_row['status'] ) ); ?></span>
					</mark>
				</td>
				<td><?php echo esc_html( ucfirst( $wps_pm_row['source'] ) ); ?></td>
				<td><?php echo $wps_pm_since; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				<td><?php echo $wps_pm_expires; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				<td>
					<?php
					$wps_revoke_msg     = esc_js( __( 'Revoke this membership?', 'subscriptions-for-woocommerce' ) );
					$wps_toggle_confirm = $wps_pm_is_active
						? ' onclick="return confirm( \'' . $wps_revoke_msg . '\' );"'
						: '';
					$wps_remove_confirm = ' onclick="return confirm( \''
						. esc_js( __( 'Permanently remove this membership record?', 'subscriptions-for-woocommerce' ) )
						. '\' );"';
					?>
					<a href="<?php echo esc_url( $wps_toggle_url ); ?>"
						class="button button-small"
						<?php echo $wps_toggle_confirm; // phpcs:ignore ?>>
						<?php echo $wps_toggle_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
					<a href="<?php echo esc_url( $wps_remove_url ); ?>"
						class="button button-small button-link-delete"
						<?php echo $wps_remove_confirm; // phpcs:ignore ?>>
						<?php esc_html_e( 'Remove', 'subscriptions-for-woocommerce' ); ?>
					</a>
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

<?php
/*
 * No <form> wrapper here — this section is rendered inside WordPress's own
 * user-edit <form>. Fields are submitted when the admin clicks "Update User".
 * Save is handled by WPS_Members_Admin::save_profile_section() via the
 * edit_user_profile_update / personal_options_update hooks.
 */
?>
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
					<option value="">
						— <?php esc_html_e( 'Select a plan', 'subscriptions-for-woocommerce' ); ?> —
					</option>
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
					<?php
					esc_html_e(
						'Leave blank for lifetime. Click "Update User" to apply.',
						'subscriptions-for-woocommerce'
					);
					?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
<?php endif; ?>
