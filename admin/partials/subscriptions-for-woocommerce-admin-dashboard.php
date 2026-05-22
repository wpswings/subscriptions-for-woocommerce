<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

global $sfw_wps_sfw_obj;
global $wps_sfw_notices;

$sfw_active_tab   = isset( $_GET['sfw_tab'] ) ? sanitize_key( $_GET['sfw_tab'] ) : 'subscriptions-for-woocommerce-general';
$sfw_default_tabs = $sfw_wps_sfw_obj->wps_sfw_plug_default_tabs();
$is_pro_active    = function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' );
$is_multistep_pending = ! wps_sfw_check_multistep();
$is_renewal_table_view = isset( $_GET['wps_subscription_view_renewal_order'] ) && ! empty( $_GET['wps_subscription_view_renewal_order'] );
$is_full_width_tab     = in_array(
	$sfw_active_tab,
	array(
		'subscriptions-for-woocommerce-subscriptions-table',
		'woocommerce-subscriptions-pro-report',
	),
	true
) || $is_renewal_table_view;
$sfw_lite_version = defined( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION' ) ? (string) SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION : '1.0.0';
$sfw_pro_version  = $sfw_lite_version;

if ( $is_pro_active ) {
	if ( defined( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_VERSION' ) ) {
		$sfw_pro_version = (string) WOOCOMMERCE_SUBSCRIPTIONS_PRO_VERSION;
	} elseif ( function_exists( 'get_plugins' ) ) {
		$sfw_plugins = get_plugins();
		if ( ! empty( $sfw_plugins['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php']['Version'] ) ) {
			$sfw_pro_version = (string) $sfw_plugins['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php']['Version'];
		}
	}
}

$sfw_version_label = sprintf(
	/* translators: 1: plugin version, 2: edition label */
	__( 'v%s %s', 'subscriptions-for-woocommerce' ),
	$is_pro_active ? $sfw_pro_version : $sfw_lite_version,
	$is_pro_active ? __( 'Pro', 'subscriptions-for-woocommerce' ) : __( 'Lite', 'subscriptions-for-woocommerce' )
);
$plugin_title     = apply_filters( 'wps_sfw_dashboard_plugin_title', esc_attr( strtoupper( str_replace( '-', ' ', $sfw_wps_sfw_obj->sfw_get_plugin_name() ) ) ) );
$active_tab_data  = isset( $sfw_default_tabs[ $sfw_active_tab ] ) ? $sfw_default_tabs[ $sfw_active_tab ] : array(
	'title' => esc_html__( 'General Settings', 'subscriptions-for-woocommerce' ),
);
$page_intro_map   = array(
	'subscriptions-for-woocommerce-overview'                    => array(
		'eyebrow'     => esc_html__( 'Overview', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'Subscription tools built for recurring commerce', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Review the product, featured capabilities, and the core setup path before configuring the plugin.', 'subscriptions-for-woocommerce' ),
	),
	'subscriptions-for-woocommerce-general'                     => array(
		'eyebrow'     => esc_html__( 'Settings', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'General', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Control the base subscription behavior, storefront labels, payment setup, and the default recurring workflow.', 'subscriptions-for-woocommerce' ),
	),
	'subscriptions-for-woocommerce-subscriptions-table'         => array(
		'eyebrow'     => esc_html__( 'Operations', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'Subscription Table', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Monitor subscriptions, review statuses, and manage the operational flow for renewal records from a single table.', 'subscriptions-for-woocommerce' ),
	),
	'subscription-for-woocommerce-api'                         => array(
		'eyebrow'     => esc_html__( 'Integrations', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'API Settings', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Configure API access, credentials, and integration-specific behavior used by subscription workflows.', 'subscriptions-for-woocommerce' ),
	),
	'subscription-for-woocommerce-subscription-box'            => array(
		'eyebrow'     => esc_html__( 'Merchandising', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'Subscription Box', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Configure bundled subscription-box behavior, product selection flow, and customer-facing presentation.', 'subscriptions-for-woocommerce' ),
	),
	'wps-subscriptions-ai-settings'                            => array(
		'eyebrow'     => esc_html__( 'AI', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'AI Settings', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Configure the shared AI provider, request limits, and feature toggles used by subscription AI workflows.', 'subscriptions-for-woocommerce' ),
	),
	'subscriptions-for-woocommerce-subscriptions-pro-features' => array(
		'eyebrow'     => esc_html__( 'Upgrade', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'Advanced Settings', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Review advanced subscription capabilities available in the pro layer and decide which workflows need to be unlocked.', 'subscriptions-for-woocommerce' ),
	),
	'subscriptions-for-woocommerce-subscriptions-free-vs-pro'  => array(
		'eyebrow'     => esc_html__( 'Compare', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'Free Vs Pro', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Compare the free and pro layers to understand which subscription workflows and management tools are included.', 'subscriptions-for-woocommerce' ),
	),
	'subscriptions-for-woocommerce-system-status'              => array(
		'eyebrow'     => esc_html__( 'Diagnostics', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'System Status', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Inspect environment details, active services, and platform information used to debug subscription issues quickly.', 'subscriptions-for-woocommerce' ),
	),
	'subscriptions-for-woocommerce-developer'                  => array(
		'eyebrow'     => esc_html__( 'Developer', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'Developer', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Access developer utilities, implementation notes, and plugin-level integration tools for custom workflows.', 'subscriptions-for-woocommerce' ),
	),
	'woocommerce-subscriptions-pro-others'                     => array(
		'eyebrow'     => esc_html__( 'Pro Settings', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'Advance Settings', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Manage advanced pro-level customer controls, retry flows, upgrade paths, and extended recurring behavior.', 'subscriptions-for-woocommerce' ),
	),
	'woocommerce-subscriptions-pro-report'                     => array(
		'eyebrow'     => esc_html__( 'Insights', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'Report', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Track subscription revenue, renewals, churn, ARR, and product-level reporting from the shared admin experience.', 'subscriptions-for-woocommerce' ),
	),
	'woocommerce-subscriptions-pro-license'                    => array(
		'eyebrow'     => esc_html__( 'License', 'subscriptions-for-woocommerce' ),
		'title'       => esc_html__( 'License', 'subscriptions-for-woocommerce' ),
		'description' => esc_html__( 'Validate the pro license, unlock premium capabilities, and keep the recurring commerce toolset active.', 'subscriptions-for-woocommerce' ),
	),
);
$active_intro     = isset( $page_intro_map[ $sfw_active_tab ] ) ? $page_intro_map[ $sfw_active_tab ] : array(
	'eyebrow'     => esc_html__( 'Settings', 'subscriptions-for-woocommerce' ),
	'title'       => isset( $active_tab_data['title'] ) ? $active_tab_data['title'] : esc_html__( 'Subscriptions', 'subscriptions-for-woocommerce' ),
	'description' => esc_html__( 'Manage plugin settings and subscription workflow controls from the shared admin dashboard.', 'subscriptions-for-woocommerce' ),
);
$shell_classes    = array(
	'wps-sfw-admin-shell',
	'wps-sfw-admin-shell--tab-' . sanitize_html_class( $sfw_active_tab ),
);
?>
<div class="<?php echo esc_attr( implode( ' ', $shell_classes ) ); ?>">
	<?php if ( $is_multistep_pending ) : ?>
		<div class="wps-sfw-admin-notices">
			<?php
			if ( $wps_sfw_notices ) {
				$wps_sfw_error_text = esc_html__( 'Settings saved !', 'subscriptions-for-woocommerce' );
				$sfw_wps_sfw_obj->wps_sfw_plug_admin_notice( $wps_sfw_error_text, 'success' );
			}
			do_action( 'wps_sfw_notice_message' );
			?>
		</div>

		<div class="wps-sfw-admin-surface wps-bg-white wps-r-8">
			<section class="wps-section">
				<div class="wps-sfw-react-shell">
					<div id="react-app"></div>
				</div>
			</section>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="wps-sfw-admin-banner">
		<div class="wps-sfw-admin-banner__copy">
			<span class="wps-sfw-admin-banner__eyebrow"><?php esc_html_e( 'NEW LAYOUT', 'subscriptions-for-woocommerce' ); ?></span>
			<div>
				<h2><?php esc_html_e( 'A redesigned subscription workspace is now available across your recurring commerce flows', 'subscriptions-for-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Configure core settings, pro extensions, reports, and support resources from one consistent admin experience.', 'subscriptions-for-woocommerce' ); ?></p>
			</div>
		</div>
		<a class="wps-sfw-admin-banner__dismiss" href="javascript:void(0)"><?php esc_html_e( 'Dismiss', 'subscriptions-for-woocommerce' ); ?></a>
	</div>

	<div class="wps-sfw-admin-status-bar">
		<span class="wps-sfw-admin-status-bar__badge"><?php echo esc_html( $is_pro_active ? __( 'PRO ACTIVE', 'subscriptions-for-woocommerce' ) : __( 'LITE ACTIVE', 'subscriptions-for-woocommerce' ) ); ?></span>
		<span class="wps-sfw-admin-status-bar__text">
			<?php
			echo esc_html(
				$is_pro_active
					? __( 'WooCommerce Subscriptions Pro is extending the shared admin experience with advanced settings, reports, and license management.', 'subscriptions-for-woocommerce' )
					: __( 'Activate the pro plugin to unlock advanced subscription controls, customer self-management tools, and recurring commerce reports.', 'subscriptions-for-woocommerce' )
			);
			?>
		</span>
	</div>

	<div class="wps-sfw-admin-notices">
		<?php
		if ( $wps_sfw_notices ) {
			$wps_sfw_error_text = esc_html__( 'Settings saved !', 'subscriptions-for-woocommerce' );
			$sfw_wps_sfw_obj->wps_sfw_plug_admin_notice( $wps_sfw_error_text, 'success' );
		}
		do_action( 'wps_sfw_notice_message' );
		?>
	</div>

	<div class="wps-sfw-admin-surface wps-bg-white wps-r-8">
		<nav class="wps-navbar">
			<div class="wps-navbar__tabs">
				<span class="wps-sfw-admin-brand__version"><?php echo esc_html( $sfw_version_label ); ?></span>
				<button type="button" class="wps-navbar__toggle" aria-expanded="false" aria-controls="wps-sfw-admin-tabs">
					<span class="wps-navbar__toggle-label"><?php esc_html_e( 'Menu', 'subscriptions-for-woocommerce' ); ?></span>
					<span class="wps-navbar__toggle-icon" aria-hidden="true"></span>
				</button>
				<ul id="wps-sfw-admin-tabs" class="wps-navbar__items">
					<?php
					if ( is_array( $sfw_default_tabs ) && ! empty( $sfw_default_tabs ) ) {
						foreach ( $sfw_default_tabs as $sfw_tab_key => $sfw_default_tab ) {
							$sfw_tab_classes = 'wps-link ';

							if ( ! empty( $sfw_active_tab ) && $sfw_active_tab === $sfw_tab_key ) {
								$sfw_tab_classes .= 'active';
							}
							?>
							<li>
								<a id="<?php echo esc_attr( $sfw_tab_key ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu' ) . '&sfw_tab=' . esc_attr( $sfw_tab_key ) ); ?>" class="<?php echo esc_attr( $sfw_tab_classes ); ?>"><?php echo esc_html( $sfw_default_tab['title'] ); ?></a>
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>
		</nav>
		<script>
			jQuery( function( $ ) {
				var mobileQuery = window.matchMedia( '(max-width: 767px)' );

				$( '.wps-navbar__tabs' ).each( function() {
					var $tabs   = $( this );
					var $button = $tabs.find( '.wps-navbar__toggle' );
					var $items  = $tabs.find( '.wps-navbar__items' );

					if ( ! $button.length || ! $items.length ) {
						return;
					}

					var syncTabs = function() {
						if ( mobileQuery.matches ) {
							var isOpen = $tabs.hasClass( 'is-open' );
							$button.attr( 'aria-expanded', isOpen ? 'true' : 'false' );
							$items.prop( 'hidden', ! isOpen );
						} else {
							$tabs.removeClass( 'is-open' );
							$button.attr( 'aria-expanded', 'false' );
							$items.prop( 'hidden', false );
						}
					};

					$button.on( 'click', function() {
						$tabs.toggleClass( 'is-open' );
						syncTabs();
					} );

					if ( mobileQuery.addEventListener ) {
						mobileQuery.addEventListener( 'change', syncTabs );
					} else if ( mobileQuery.addListener ) {
						mobileQuery.addListener( syncTabs );
					}

					syncTabs();
				} );
			} );
		</script>

		<section class="wps-section">
			<div class="wps_sfw_lite_go_pro_popup_wrap">
				<div class="wps_wsfw_popup_shadow"></div>
				<div class="wps_sfw_lite_go_pro_popup">
					<div class="wps_sfw_lite_go_pro_popup_head">
						<h2><?php esc_html_e( 'Upgrade To Subscription For WooCommerce Pro', 'subscriptions-for-woocommerce' ); ?></h2>
						<a href="javascript:void(0)" class="wps_sfw_lite_go_pro_popup_close">
							<span>×</span>
						</a>
					</div>
					<div class="wps_sfw_lite_go_pro_popup_head"><img class="wps_go_pro_images" src="<?php echo esc_attr( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/go-pro.png' ); ?>"></div>
					<div class="wps_sfw_lite_go_pro_popup_content">
						<p class="wps_sfw_lite_go_pro_popup_text">
							<?php
							esc_html_e(
								'Subscriptions for WooCommerce Pro plugin add a recurring business model to your online store, allowing you to provide subscription-based products & services with simple and variable options',
								'subscriptions-for-woocommerce'
							);
							?>
						</p>
					</div>
					<div class="wps_sfw_lite_go_pro_popup_button">
						<a class="button wps_ubo_lite_overview_go_pro_button" target="_blank" href="https://wpswings.com/product/subscriptions-for-woocommerce-pro?utm_source=wpswings-subs-pro&utm_medium=subs-org-backend&utm_campaign=go-pro"><?php esc_html_e( 'Upgrade', 'subscriptions-for-woocommerce' ); ?>
							<span class="dashicons dashicons-arrow-right-alt"></span>
						</a>
					</div>
				</div>
			</div>

			<div class="wps-sfw-admin-layout<?php echo $is_full_width_tab ? ' wps-sfw-admin-layout--full' : ''; ?>">
				<div class="wps-sfw-admin-layout__main">
					<?php if ( 'subscriptions-for-woocommerce-overview' !== $sfw_active_tab ) : ?>
						<div class="wps-sfw-page-intro">
							<div class="wps-sfw-page-intro__content">
								<p class="wps-sfw-page-intro__eyebrow"><?php echo esc_html( $active_intro['eyebrow'] ); ?></p>
								<h2><?php echo esc_html( $active_intro['title'] ); ?></h2>
								<p><?php echo esc_html( $active_intro['description'] ); ?></p>
							</div>
							<div class="wps-sfw-page-intro__actions">
								<a href="https://docs.wpswings.com/subscriptions-for-woocommerce/?utm_source=wpswings-subs-doc&utm_medium=subs-org-backend&utm_campaign=documentation" class="wps-sfw-primary-action" target="_blank"><?php esc_html_e( 'Read Documentation', 'subscriptions-for-woocommerce' ); ?></a>
							</div>
						</div>
					<?php endif; ?>

					<!-- <div class="wps-sfw-page-panel"> -->
						<?php
						do_action( 'wps_sfw_before_general_settings_form' );

						if ( empty( $sfw_active_tab ) ) {
							$sfw_active_tab = 'subscriptions-for-woocommerce-general';
						}

						if ( ! isset( $sfw_default_tabs[ $sfw_active_tab ]['file_path'] ) ) {
							$file_path = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH;
						} else {
							$file_path = $sfw_default_tabs[ $sfw_active_tab ]['file_path'];
						}

						if ( ! wps_sfw_check_multistep() ) {
							echo '<div class="wps-sfw-react-shell"><div id="react-app"></div></div>';
						} else {
							$sfw_tab_content_path = $file_path . 'admin/partials/' . $sfw_active_tab . '.php';
							$sfw_wps_sfw_obj->wps_sfw_plug_load_template( $sfw_tab_content_path );
						}

						do_action( 'wps_sfw_after_general_settings_form' );
						?>
					<!-- </div> -->
				</div>

				<?php if ( ! $is_full_width_tab ) : ?>
					<aside class="wps-sfw-admin-layout__sidebar">
						<div class="wps-sfw-sidebar-card">
							<h3><?php esc_html_e( 'Need help with this plugin?', 'subscriptions-for-woocommerce' ); ?></h3>
							<div class="wps-sfw-sidebar-card__links">
								<a href="https://www.youtube.com/watch?v=2VFyxZl3l-A" target="_blank"><?php esc_html_e( 'Watch Video', 'subscriptions-for-woocommerce' ); ?></a>
								<a href="https://docs.wpswings.com/subscriptions-for-woocommerce/?utm_source=wpswings-subs-doc&utm_medium=subs-org-backend&utm_campaign=documentation" target="_blank"><?php esc_html_e( 'Documentation', 'subscriptions-for-woocommerce' ); ?></a>
								<a href="https://wpswings.com/submit-query/?utm_source=wpswings-subs-support&utm_medium=subs-org-backend&utm_campaign=support" target="_blank"><?php esc_html_e( 'Support', 'subscriptions-for-woocommerce' ); ?></a>
							</div>
						</div>
						<?php Subscriptions_For_Woocommerce_Talk_To_Expert_Form::get_instance()->render_sidebar_card(); ?>

						<div class="wps-sfw-sidebar-card wps-sfw-sidebar-card--accent">
							<h3><?php esc_html_e( 'Still facing problems?', 'subscriptions-for-woocommerce' ); ?></h3>
							<p><?php esc_html_e( 'We are ready to resolve workflow, styling, and integration issues across your store setup.', 'subscriptions-for-woocommerce' ); ?></p>
							<a class="wps-sfw-secondary-action" href="https://wpswings.com/submit-query/?utm_source=wpswings-subs-support&utm_medium=subs-org-backend&utm_campaign=support" target="_blank"><?php esc_html_e( 'Contact Us', 'subscriptions-for-woocommerce' ); ?></a>
						</div>

						<div class="wps-sfw-sidebar-card">
							<h3><?php esc_html_e( 'Explore more plugins', 'subscriptions-for-woocommerce' ); ?></h3>
							<p><?php esc_html_e( 'Discover additional commerce and automation plugins from the same product family.', 'subscriptions-for-woocommerce' ); ?></p>
							<a class="wps-sfw-sidebar-card__button" href="https://wpswings.com/wordpress-plugins/?utm_source=wpswings-subs-crosssell&utm_medium=subs-backend&utm_campaign=more-plugins" target="_blank"><?php esc_html_e( 'View More Plugins', 'subscriptions-for-woocommerce' ); ?></a>
						</div>
					</aside>
				<?php endif; ?>
			</div>
		</section>
	</div>
</div>
<?php Subscriptions_For_Woocommerce_Talk_To_Expert_Form::get_instance()->render_modal(); ?>
