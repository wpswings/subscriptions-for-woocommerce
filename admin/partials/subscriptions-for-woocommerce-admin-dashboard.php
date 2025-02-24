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

if ( $wps_sfw_notices ) {
	$wps_sfw_error_text = esc_html__( 'Settings saved !', 'subscriptions-for-woocommerce' );
	$sfw_wps_sfw_obj->wps_sfw_plug_admin_notice( $wps_sfw_error_text, 'success' );
}
do_action( 'wps_sfw_notice_message' );
if ( ! wps_sfw_check_multistep() ) {
	?>
	<div id="react-app"></div>
	<?php
	return;
}
$wps_sfw_pro_name = apply_filters( 'wps_sfw_dashboard_plugin_title', esc_attr( strtoupper( str_replace( '-', ' ', $sfw_wps_sfw_obj->sfw_get_plugin_name() ) ) ) );
?>
<header>
	<div class="wps-header-container wps-bg-white wps-r-8">
		<h1 class="wps-header-title"><?php echo esc_html( $wps_sfw_pro_name ); ?></h1>
		<div class="wps-header-container__links">
			<?php
			if ( ! is_plugin_active( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' ) ) {
				?>
				<span class="goPro">
					<a class="wps-sfw-go-pro" target="_blank" href="https://wpswings.com/product/subscriptions-for-woocommerce-pro?utm_source=wpswings-subs-pro&utm_medium=subs-org-backend&utm_campaign=go-pro"><?php esc_html_e( 'GO PRO', 'subscriptions-for-woocommerce' ); ?></a>
					<span class="wps-header-container__links-divider">|</span>
				</span>
				<?php
			}
			?>
			<a href="https://docs.wpswings.com/subscriptions-for-woocommerce/?utm_source=wpswings-subs-doc&utm_medium=subs-org-backend&utm_campaign=documentation" class="wps-link" target="_blank"><?php esc_html_e( 'Documentation', 'subscriptions-for-woocommerce' ); ?></a>
			<span class="wps-header-container__links-divider">|</span>
			<a href="https://www.youtube.com/watch?v=2VFyxZl3l-A" class="wps-link" target="_blank"><?php esc_html_e( 'Video', 'subscriptions-for-woocommerce' ); ?></a>
			<span class="wps-header-container__links-divider">|</span>
			<a href="https://wpswings.com/submit-query/?utm_source=wpswings-subs-support&utm_medium=subs-org-backend&utm_campaign=support" class="wps-link" target="_blank"><?php esc_html_e( 'Support', 'subscriptions-for-woocommerce' ); ?></a>
		</div>

	</div>
</header>

<main class="wps-main wps-bg-white wps-r-8">
	
	<nav class="wps-navbar">
		<ul class="wps-navbar__items">
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
	</nav>

	<section class="wps-section">
		<!-- pro popup -->
		<div class="wps_sfw_lite_go_pro_popup_wrap ">
		<!-- Go pro popup main start. -->
		<div class="wps_wsfw_popup_shadow"></div>
		<div class="wps_sfw_lite_go_pro_popup">
			<!-- Main heading. -->
			<div class="wps_sfw_lite_go_pro_popup_head">
				<h2><?php esc_html_e( 'Upgrade To Subscription For WooCommerce Pro', 'subscriptions-for-woocommerce' ); ?></h2>
				<!-- Close button. -->
				<a href="javascript:void(0)" class="wps_sfw_lite_go_pro_popup_close">
					<span>×</span>
				</a>
			</div>  

			<!-- Notice icon. -->
			<div class="wps_sfw_lite_go_pro_popup_head"><img class="wps_go_pro_images" src="<?php echo esc_attr( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/go-pro.png' ); ?>">
			</div>
			
				
			<!-- Notice. -->
			<div class="wps_sfw_lite_go_pro_popup_content">
				<p class="wps_sfw_lite_go_pro_popup_text">
				<?php
				esc_html_e(
					'Subscriptions for WooCommerce Pro plugin add a recurring business model to your online store, allowing you to provide subscription-based products & services with simple and variable options',
					'subscriptions-for-woocommerce'
				)
				?>
							</p>
					
				</div>

			<!-- Go pro button. -->
			<div class="wps_sfw_lite_go_pro_popup_button">
				<a class="button wps_ubo_lite_overview_go_pro_button" target="_blank" href="https://wpswings.com/product/subscriptions-for-woocommerce-pro?utm_source=wpswings-subs-pro&utm_medium=subs-org-backend&utm_campaign=go-pro">	<?php esc_html_e( 'Upgrade', 'subscriptions-for-woocommerce' ); ?> 
			<span class="dashicons dashicons-arrow-right-alt"></span></a>
			</div>
		</div>
		<!-- Go pro popup main end. -->
	</div>
		<!-- pro popup -->
		<div>
			<?php
			do_action( 'wps_sfw_before_general_settings_form' );
			// if submenu is directly clicked on woocommerce.
			if ( empty( $sfw_active_tab ) ) {
				$sfw_active_tab = 'subscriptions-for-woocommerce-general';
			}

			// look for the path based on the tab id in the admin templates.
			if ( ! isset( $sfw_default_tabs[ $sfw_active_tab ]['file_path'] ) ) {
				$file_path = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH;
			} else {
				$file_path = $sfw_default_tabs[ $sfw_active_tab ]['file_path'];
			}
				$sfw_tab_content_path = $file_path . 'admin/partials/' . $sfw_active_tab . '.php';
				$sfw_wps_sfw_obj->wps_sfw_plug_load_template( $sfw_tab_content_path );

				do_action( 'wps_sfw_after_general_settings_form' );
			?>
		</div>
	</section>
