<?php
/**
 * Membership Plans list table — admin tab partial (Day 06).
 *
 * Loaded by the tab system via:
 *   SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/wps-membership-plans.php'
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
	. 'admin/partials/membership/class-wps-membership-plans-list-table.php';

// Preserve current status filter in the hidden field (display only, nonce not needed).
$wps_plans_status = isset( $_GET['status'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	? sanitize_key( wp_unslash( $_GET['status'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	: '';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wps_plan_saved = ! empty( $_GET['wps_plan_saved'] );
?>

<?php if ( $wps_plan_saved ) : ?>
<div class="notice notice-success inline" style="margin:0 0 16px;">
	<p><?php esc_html_e( 'Plan saved.', 'subscriptions-for-woocommerce' ); ?></p>
</div>
<?php endif; ?>

<div class="wps_sfw_subscription_table_inner_wrap">
	<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wps_membership_plan' ) ); ?>"
		class="button button-primary">
		<?php esc_html_e( 'Add New Plan', 'subscriptions-for-woocommerce' ); ?>
	</a>
</div>

<form method="get">
	<input type="hidden" name="page" value="subscriptions_for_woocommerce_menu">
	<input type="hidden" name="sfw_tab" value="wps-membership-manage">
	<input type="hidden" name="wps_mem_tab" value="plans">
	<?php if ( ! empty( $wps_plans_status ) ) : ?>
		<input type="hidden" name="status"
			value="<?php echo esc_attr( $wps_plans_status ); ?>">
	<?php endif; ?>
	<?php wp_nonce_field( 'bulk-plans', '_wpnonce' ); ?>

	<div class="wps_sfw_list_table">
		<?php
		$wps_plans_table = new WPS_Membership_Plans_List_Table();
		$wps_plans_table->prepare_items();
		$wps_plans_table->search_box(
			__( 'Search Plans', 'subscriptions-for-woocommerce' ),
			'wps-membership-plan'
		);
		$wps_plans_table->display();
		?>
	</div>
</form>

<?php
$wps_js_delete_with_members = esc_js(
	/* translators: 1: plan name 2: active member count */
	__(
		'Delete plan "%1$s"? It has %2$d active member(s) — memberships will be cancelled. Cannot be undone.',
		'subscriptions-for-woocommerce'
	)
);

$wps_js_delete_no_members = esc_js(
	/* translators: %1$s: plan name */
	__(
		'Delete plan "%1$s"? This cannot be undone.',
		'subscriptions-for-woocommerce'
	)
);

$wps_js_bulk_delete = esc_js(
	__(
		'Delete the selected plans? Active memberships will be cancelled. This cannot be undone.',
		'subscriptions-for-woocommerce'
	)
);
?>
<script>
function wps_confirm_plan_delete( el ) {
	var name    = el.getAttribute( 'data-plan' );
	var members = parseInt( el.getAttribute( 'data-members' ), 10 );
	var tpl = members > 0
		? '<?php echo esc_js( $wps_js_delete_with_members ); ?>'
		: '<?php echo esc_js( $wps_js_delete_no_members ); ?>';
	var msg = tpl.replace( '%1$s', name ).replace( '%2$d', members );
	return window.confirm( msg );
}

( function () {
	var bulkMsg = '<?php echo esc_js( $wps_js_bulk_delete ); ?>';
	var applyBtns = document.querySelectorAll(
		'.wps_sfw_list_table .bulkactions input[type="submit"]'
	);
	applyBtns.forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			var select = btn.closest( '.bulkactions' ).querySelector( 'select' );
			if ( ! select || 'bulk-delete' !== select.value ) {
				return;
			}
			var checked = document.querySelectorAll(
				'.wps_sfw_list_table input[name="wps_plan_ids[]"]:checked'
			);
			if ( checked.length > 0 && ! window.confirm( bulkMsg ) ) {
				e.preventDefault();
			}
		} );
	} );
} )();
</script>
