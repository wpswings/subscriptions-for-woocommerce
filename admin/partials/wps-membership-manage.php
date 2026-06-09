<?php
/**
 * Manage Membership — unified admin tab partial.
 *
 * Consolidates Membership Plans, Members, and Access Rules into a single
 * top-level tab with inner sub-tab navigation driven by the `wps_mem_tab`
 * query parameter.
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

$wps_mem_sub_tabs = array(
	'plans'        => esc_html__( 'Membership Plans', 'subscriptions-for-woocommerce' ),
	'members'      => esc_html__( 'Members', 'subscriptions-for-woocommerce' ),
	'access-rules' => esc_html__( 'Access Rules', 'subscriptions-for-woocommerce' ),
);

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$wps_active_sub = isset( $_GET['wps_mem_tab'] )
	? sanitize_key( wp_unslash( $_GET['wps_mem_tab'] ) )
	: 'plans';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

if ( ! array_key_exists( $wps_active_sub, $wps_mem_sub_tabs ) ) {
	$wps_active_sub = 'plans';
}

$wps_partial_map = array(
	'plans'        => 'wps-membership-plans.php',
	'members'      => 'wps-membership-members.php',
	'access-rules' => 'wps-membership-access-rules.php',
);
?>

<div class="wps-mem-wrap">

	<nav class="nav-tab-wrapper wps-mem-tab-nav">
		<?php
		foreach ( $wps_mem_sub_tabs as $wps_slug => $wps_label ) :
			$wps_url       = add_query_arg(
				array(
					'page'        => 'subscriptions_for_woocommerce_menu',
					'sfw_tab'     => 'wps-membership-manage',
					'wps_mem_tab' => $wps_slug,
				),
				admin_url( 'admin.php' )
			);
			$wps_is_active = ( $wps_active_sub === $wps_slug );
			?>
			<a href="<?php echo esc_url( $wps_url ); ?>"
				class="nav-tab<?php echo $wps_is_active ? ' nav-tab-active' : ''; ?>">
				<?php echo esc_html( $wps_label ); ?>
			</a>
			<?php
		endforeach;
		?>
	</nav>

	<div class="wps-mem-content">
		<?php
		require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
			. 'admin/partials/' . $wps_partial_map[ $wps_active_sub ];
		?>
	</div>

</div>
