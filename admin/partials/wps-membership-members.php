<?php
/**
 * Members list table — admin tab partial (Day 08 / Day 09).
 *
 * Loaded by the tab system via:
 *   SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/wps-membership-members.php'
 *
 * Day 09 additions: CSV export link, Grant Membership button + form
 * (sourced from admin/partials/membership/members-tab.php).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
	wp_die( esc_html__( 'You do not have permission to view this page.', 'subscriptions-for-woocommerce' ) );
}

require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
	. 'admin/partials/membership/class-wps-members-list-table.php';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wps_status_filter = isset( $_GET['member_status'] ) ? sanitize_key( wp_unslash( $_GET['member_status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wps_plan_filter = isset( $_GET['plan_slug'] ) ? sanitize_key( wp_unslash( $_GET['plan_slug'] ) ) : '';

$wps_all_plans = wps_get_all_plans( 'active' );

$wps_export_url = wp_nonce_url(
	add_query_arg(
		array(
			'page'               => 'subscriptions_for_woocommerce_menu',
			'sfw_tab'            => 'wps-membership-members',
			'wps_export_members' => '1',
		),
		admin_url( 'admin.php' )
	),
	'wps_export_members'
);
?>

<div class="wps_sfw_subscription_table_inner_wrap">
	<a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>"
		class="button button-primary">
		<?php esc_html_e( 'Add New User', 'subscriptions-for-woocommerce' ); ?>
	</a>
	<a href="<?php echo esc_url( $wps_export_url ); ?>" class="button">
		<?php esc_html_e( 'Export CSV', 'subscriptions-for-woocommerce' ); ?>
	</a>
	<button type="button" class="button" id="wps-toggle-grant-form">
		<?php esc_html_e( 'Grant Membership', 'subscriptions-for-woocommerce' ); ?>
	</button>
</div>

<?php require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/membership/members-tab.php'; ?>

<?php if ( ! empty( $wps_all_plans ) ) : ?>
<div class="wps-member-plan-filter" style="margin:12px 0;">
	<label for="wps_plan_filter_select">
		<?php esc_html_e( 'Filter by Plan:', 'subscriptions-for-woocommerce' ); ?>
	</label>
	<select id="wps_plan_filter_select" onchange="
		var url = new URL(window.location.href);
		if (this.value) { url.searchParams.set('plan_slug', this.value); }
		else { url.searchParams.delete('plan_slug'); }
		window.location = url.toString();
	">
		<option value="">— <?php esc_html_e( 'All Plans', 'subscriptions-for-woocommerce' ); ?> —</option>
		<?php foreach ( $wps_all_plans as $wps_plan ) : ?>
			<option value="<?php echo esc_attr( $wps_plan['slug'] ); ?>"
				<?php selected( $wps_plan_filter, $wps_plan['slug'] ); ?>>
				<?php echo esc_html( $wps_plan['name'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
<?php endif; ?>

<form method="get">
	<input type="hidden" name="page" value="subscriptions_for_woocommerce_menu">
	<input type="hidden" name="sfw_tab" value="wps-membership-members">
	<?php if ( ! empty( $wps_status_filter ) ) : ?>
		<input type="hidden" name="member_status"
			value="<?php echo esc_attr( $wps_status_filter ); ?>">
	<?php endif; ?>
	<?php if ( ! empty( $wps_plan_filter ) ) : ?>
		<input type="hidden" name="plan_slug"
			value="<?php echo esc_attr( $wps_plan_filter ); ?>">
	<?php endif; ?>
	<?php wp_nonce_field( 'bulk-members', '_wpnonce' ); ?>

	<div class="wps_sfw_list_table">
		<?php
		$wps_members_table = new WPS_Members_List_Table();
		$wps_members_table->prepare_items();
		$wps_members_table->search_box(
			__( 'Search Members', 'subscriptions-for-woocommerce' ),
			'wps-membership-member'
		);
		$wps_members_table->display();
		?>
	</div>
</form>
