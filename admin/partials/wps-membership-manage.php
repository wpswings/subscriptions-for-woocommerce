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
	'plans'        => array(
		'label' => esc_html__( 'Membership Plans', 'subscriptions-for-woocommerce' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
			. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
			. '<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/>'
			. '<path d="m2 12 10 5 10-5"/></svg>',
	),
	'members'      => array(
		'label' => esc_html__( 'Members', 'subscriptions-for-woocommerce' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
			. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
			. '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>'
			. '<circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>'
			. '<path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
	),
	'access-rules' => array(
		'label' => esc_html__( 'Access Rules', 'subscriptions-for-woocommerce' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
			. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
			. '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1'
			. 'c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1Z"/>'
			. '<path d="m9 12 2 2 4-4"/></svg>',
	),
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

	<?php $wps_nav_label = esc_attr__( 'Membership sections', 'subscriptions-for-woocommerce' ); ?>
	<nav class="wps-mem-tab-nav" aria-label="<?php echo esc_attr( $wps_nav_label ); ?>">
		<?php
		foreach ( $wps_mem_sub_tabs as $wps_slug => $wps_tab ) :
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
				class="wps-mem-tab<?php echo $wps_is_active ? ' is-active' : ''; ?>"
				<?php echo $wps_is_active ? 'aria-current="page"' : ''; ?>>
				<span class="wps-mem-tab__icon">
					<?php
					// Icon markup is a hardcoded inline SVG constant defined above — no user input.
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $wps_tab['icon'];
					?>
				</span>
				<span class="wps-mem-tab__label"><?php echo esc_html( $wps_tab['label'] ); ?></span>
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
