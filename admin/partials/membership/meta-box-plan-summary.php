<?php
/**
 * Meta box partial — Plan Summary (read-only side panel).
 *
 * Variables available from WPS_Membership_Plan_CPT::render_summary_meta_box():
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

if ( ! $wps_plan ) {
	echo '<p class="description">' . esc_html__( 'Save the plan to view its summary.', 'subscriptions-for-woocommerce' ) . '</p>';
	return;
}

$wps_slug           = $wps_plan['slug'];
$wps_active_members = WPS_Membership_Plans_Admin::get_active_member_count( $wps_slug );
$wps_product_count  = count( $wps_plan['products'] );
$wps_created        = get_post_field( 'post_date', $post->ID );
$wps_list_url       = admin_url(
	'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=wps-membership-manage&wps_mem_tab=plans'
);
?>
<table class="wps-plan-summary-table" style="width:100%;border-collapse:collapse;">
	<tbody>

		<tr>
			<td style="padding:4px 0;color:#646970;">
				<?php esc_html_e( 'Plan ID', 'subscriptions-for-woocommerce' ); ?>
			</td>
			<td style="padding:4px 0;text-align:right;">
				<strong>#<?php echo absint( $post->ID ); ?></strong>
			</td>
		</tr>

		<tr>
			<td style="padding:4px 0;color:#646970;">
				<?php esc_html_e( 'Slug', 'subscriptions-for-woocommerce' ); ?>
			</td>
			<td style="padding:4px 0;text-align:right;">
				<code><?php echo esc_html( $wps_slug ); ?></code>
			</td>
		</tr>

		<tr>
			<td style="padding:4px 0;color:#646970;">
				<?php esc_html_e( 'Active Members', 'subscriptions-for-woocommerce' ); ?>
			</td>
			<td style="padding:4px 0;text-align:right;">
				<strong><?php echo absint( $wps_active_members ); ?></strong>
			</td>
		</tr>

		<tr>
			<td style="padding:4px 0;color:#646970;">
				<?php esc_html_e( 'Linked Products', 'subscriptions-for-woocommerce' ); ?>
			</td>
			<td style="padding:4px 0;text-align:right;">
				<strong><?php echo absint( $wps_product_count ); ?></strong>
			</td>
		</tr>

		<tr>
			<td style="padding:4px 0;color:#646970;">
				<?php esc_html_e( 'Created', 'subscriptions-for-woocommerce' ); ?>
			</td>
			<td style="padding:4px 0;text-align:right;">
				<?php echo esc_html( mysql2date( get_option( 'date_format' ), $wps_created ) ); ?>
			</td>
		</tr>

	</tbody>
</table>

<p style="margin-top:12px;">
	<a href="<?php echo esc_url( $wps_list_url ); ?>" class="button button-secondary" style="width:100%;text-align:center;">
		<?php esc_html_e( '&larr; All Plans', 'subscriptions-for-woocommerce' ); ?>
	</a>
</p>
