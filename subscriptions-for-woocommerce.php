<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpswings.com/
 * @since             1.0.0
 * @package           Subscriptions_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Subscriptions For WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/subscriptions-for-woocommerce/
 * Description:       <code><strong>Subscriptions for WooCommerce</strong></code> allow collecting repeated payments through subscriptions orders on the eCommerce store for both admin and users. <a target="_blank" href="https://wpswings.com/woocommerce-plugins/?utm_source=wpswings-subs-shop&utm_medium=subs-org-backend&utm_campaign=shop-page">Elevate your e-commerce store by exploring more on WP Swings</a>
 * Version:           1.8.1
 * Author:            WP Swings
 * Author URI:        https://wpswings.com/?utm_source=wpswings-subs-official&utm_medium=subs-org-backend&utm_campaign=official
 * Text Domain:       subscriptions-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * Requires at least:        5.1.0
 * Tested up to:             6.7.2
 * WC requires at least:     5.1.0
 * WC tested up to:          9.7.1
 *
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
use Automattic\WooCommerce\Utilities\OrderUtil;
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$old_pro_exists = false;
$plug           = get_plugins();
if ( isset( $plug['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php'] ) ) {
	if ( version_compare( $plug['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php']['Version'], '2.1.0', '<' ) ) {
		$old_pro_exists = true;
	}
}
add_action( 'after_plugin_row_woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', 'wps_sfw_old_upgrade_notice', 0, 3 );
if ( ! function_exists( 'wps_sfw_old_upgrade_notice ' ) ) {
	/**
	 * Migration to ofl pro plugin.
	 *
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $status Status filter currently applied to the plugin list.
	 */
	function wps_sfw_old_upgrade_notice( $plugin_file, $plugin_data, $status ) {
		global $old_pro_exists;
		if ( $old_pro_exists ) {
			?>
		<tr class="plugin-update-tr active notice-warning notice-alt">
			<td colspan="4" class="plugin-update colspanchange">
				<div class="notice notice-error inline update-message notice-alt">
					<p class='wps-notice-title wps-notice-section'>
						<strong><?php esc_html_e( 'This plugin will not work anymore correctly.', 'subscriptions-for-woocommerce' ); ?></strong><br>
						<?php esc_html_e( 'We highly recommend to update to latest pro version.', 'subscriptions-for-woocommerce' ); ?>
					</p>
				</div>
			</td>
		</tr>
		<style>
			.wps-notice-section > p:before {
				content: none;
			}
		</style>
			<?php
		}
	}
}
$old_sfw_pro_present   = false;
$installed_plugins = get_plugins();

if ( array_key_exists( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', $installed_plugins ) ) {
	$pro_plugin = $installed_plugins['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php'];
	if ( version_compare( $pro_plugin['Version'], '2.1.0', '<' ) ) {
		$old_sfw_pro_present = true;
	}
}

if ( $old_sfw_pro_present ) {

	add_action( 'admin_notices', 'wps_sfw_lite_add_updatenow_notice' );

	/**
	 * Displays notice to upgrade to Subscription.
	 */
	function wps_sfw_lite_add_updatenow_notice() {
		$screen = get_current_screen();
		if ( isset( $screen->id ) && 'wp-swings_page_subscriptions_for_woocommerce_menu' === $screen->id ) {
			?>
		<tr class="plugin-update-tr active notice-warning notice-alt">
			<td colspan="4" class="plugin-update colspanchange">
				<div class="notice notice-error inline update-message notice-alt">
					<div class='wps-notice-title wps-notice-section'>
						<p><strong>IMPORTANT NOTICE:</strong></p>
					</div>
					<div class='wps-notice-content wps-notice-section'>
						<p><strong><?php esc_html_e( 'Your Woocommerce Subscriptions Pro plugin update is here! Please Update it now via plugins page', 'subscriptions-for-woocommerce' ); ?></strong></p>
					</div>
				</div>
			</td>
		</tr>
		<style>
			.wps-notice-section > p:before {
				content: none;
			}	
		</style>
			<?php
		}
	}
	// end of functio wps_sfw_lite_add_updatenow_notice.
	add_action( 'admin_notices', 'wps_sfw_check_and_inform_update' );

	/**
	 * Check update if pro is old.
	 */
	function wps_sfw_check_and_inform_update() {
		$update_file = plugin_dir_path( __DIR__ ) . 'woocommerce-subscriptions-pro/class-woocommerce-subscriptions-pro-update.php';

		// If present but not active.
		if ( ! is_plugin_active( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' ) ) {
			if ( file_exists( $update_file ) ) {
				$wps_mfw_pro_license_key = get_option( 'mwb_wsp_license_key', '' );
				! defined( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_LICENSE_KEY' ) && define( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_LICENSE_KEY', $wps_mfw_pro_license_key );
				! defined( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_BASE_FILE' ) && define( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_BASE_FILE', 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' );
			}
			require_once $update_file;
		}

		if ( defined( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_BASE_FILE' ) ) {
			$wps_sfw_version_old_pro = new Woocommerce_Subscriptions_Pro_Update();
			$wps_sfw_version_old_pro->mwb_wsp_check_update();
			$plugin_transient  = get_site_transient( 'update_plugins' );
			$update_obj        = ! empty( $plugin_transient->response[ WOOCOMMERCE_SUBSCRIPTIONS_PRO_BASE_FILE ] ) ? $plugin_transient->response[ WOOCOMMERCE_SUBSCRIPTIONS_PRO_BASE_FILE ] : false;

			if ( ! empty( $update_obj ) ) :
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( 'Your WooCommerce Subscription Pro plugin update is here! Please Update it now.', 'subscriptions-for-woocommerce' ); ?></p>
				</div>
				<?php
			endif;
		}
	}
}
$activated      = true;
$active_plugins = get_option( 'active_plugins', array() );
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	$active_network_wide = get_site_option( 'active_sitewide_plugins', array() );
	if ( ! empty( $active_network_wide ) ) {
		foreach ( $active_network_wide as $key => $value ) {
			$active_plugins[] = $key;
		}
	}
	$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
	if ( ! in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) ) {
		$activated = false;
	}
} elseif ( ! in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) ) {
		$activated = false;
}
if ( $activated ) {

	/**
	 * Define plugin constants.
	 *
	 * @since             1.0.0
	 */
	function define_subscriptions_for_woocommerce_constants() {

		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION', '1.8.1' );
		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH', plugin_dir_path( __FILE__ ) );
		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL', plugin_dir_url( __FILE__ ) );
		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_SERVER_URL', 'https://wpswings.com' );
		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_ITEM_REFERENCE', 'Subscriptions For Woocommerce' );
	}


	/**
	 * Callable function for defining plugin constants.
	 *
	 * @param   String $key    Key for contant.
	 * @param   String $value   value for contant.
	 * @since             1.0.0
	 */
	function subscriptions_for_woocommerce_constants( $key, $value ) {

		if ( ! defined( $key ) ) {

			define( $key, $value );
		}
	}

	// Upgrade notice.
	add_action( 'after_plugin_row_' . plugin_basename( __FILE__ ), 'sfw_upgrade_notice' );

	/**
	 * Upgrade Notice for Subscription Plugin.
	 *
	 * @return void
	 */
	function sfw_upgrade_notice() {
		$wps_sfw_get_count = new Subscriptions_For_Woocommerce_Admin( 'subscriptions-for-woocommerce', '1.4.2' );
		$wps_sfw_pending_product_count  = $wps_sfw_get_count->wps_sfw_get_count( 'pending', 'count', 'products' );
		$wps_sfw_pending_orders_count   = $wps_sfw_get_count->wps_sfw_get_count( 'pending', 'count', 'mwb_renewal_orders' );
		$wps_sfw_pending_subs_count     = $wps_sfw_get_count->wps_sfw_get_count( 'pending', 'count', 'post_type_subscription' );
		if ( '0' != $wps_sfw_pending_product_count || '0' != $wps_sfw_pending_orders_count || '0' != $wps_sfw_pending_subs_count ) {
			?>
	<tr class="plugin-update-tr active notice-warning notice-alt">
			<td  colspan="4" class="plugin-update colspanchange">
				<div class="notice notice-warning inline update-message notice-alt">
					<p>
						<?php esc_html_e( 'Heads up, The latest update includes some substantial changes across different areas of the plugin.', 'subscriptions-for-woocommerce' ); ?>
					</p>
					<p><b><?php esc_html_e( 'Please Click', 'subscriptions-for-woocommerce' ); ?><a href="<?php echo esc_attr( admin_url( 'admin.php' ) . '?page=subscriptions_for_woocommerce_menu' ); ?>"> here </a><?php esc_html_e( 'To Goto the Migration Page and Run the Migration Functionality.', 'subscriptions-for-woocommerce' ); ?></b></p>
				</div>
			</td>
		</tr>
	<style>
	.wps-notice-section > p:before {
		content: none;
	}
	</style>

			<?php
		}
	}

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-subscriptions-for-woocommerce-activator.php
	 *
	 * @param   String $network_wide   network_wide for network.
	 */
	function activate_subscriptions_for_woocommerce( $network_wide ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-subscriptions-for-woocommerce-activator.php';
		Subscriptions_For_Woocommerce_Activator::subscriptions_for_woocommerce_activate( $network_wide );
		$wps_sfw_active_plugin = get_option( 'wps_all_plugins_active', false );
		if ( is_array( $wps_sfw_active_plugin ) && ! empty( $wps_sfw_active_plugin ) ) {
			$wps_sfw_active_plugin['subscriptions-for-woocommerce'] = array(
				'plugin_name' => __( 'Subscriptions For Woocommerce', 'subscriptions-for-woocommerce' ),
				'active' => '1',
			);
		} else {
			$wps_sfw_active_plugin = array();
			$wps_sfw_active_plugin['subscriptions-for-woocommerce'] = array(
				'plugin_name' => __( 'Subscriptions For Woocommerce', 'subscriptions-for-woocommerce' ),
				'active' => '1',
			);
		}
		update_option( 'wps_all_plugins_active', $wps_sfw_active_plugin );
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-subscriptions-for-woocommerce-deactivator.php
	 *
	 * @param   String $network_wide   network_wide for network.
	 */
	function deactivate_subscriptions_for_woocommerce( $network_wide ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-subscriptions-for-woocommerce-deactivator.php';
		Subscriptions_For_Woocommerce_Deactivator::subscriptions_for_woocommerce_deactivate( $network_wide );
		$wps_sfw_deactive_plugin = get_option( 'wps_all_plugins_active', false );
		if ( is_array( $wps_sfw_deactive_plugin ) && ! empty( $wps_sfw_deactive_plugin ) ) {
			foreach ( $wps_sfw_deactive_plugin as $wps_sfw_deactive_key => $wps_sfw_deactive ) {
				if ( 'subscriptions-for-woocommerce' === $wps_sfw_deactive_key ) {
					$wps_sfw_deactive_plugin[ $wps_sfw_deactive_key ]['active'] = '0';
				}
			}
		}
		update_option( 'wps_all_plugins_active', $wps_sfw_deactive_plugin );
	}

	add_action(
		'before_woocommerce_init',
		function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			}
		}
	);

	/**
	 * To Remove Cron schedule.
	 *
	 * @return void
	 */
	function wps_sfw_remove_cron_for_notification_update() {
		wp_clear_scheduled_hook( 'wps_wgm_check_for_notification_update' );
	}

	register_activation_hook( __FILE__, 'activate_subscriptions_for_woocommerce' );
	register_deactivation_hook( __FILE__, 'deactivate_subscriptions_for_woocommerce' );
	register_deactivation_hook( __FILE__, 'wps_sfw_remove_cron_for_notification_update' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-subscriptions-for-woocommerce.php';

	if ( ! function_exists( 'wps_sfw_check_multistep' ) ) {
		/**
		 * This function is used to check susbcripton product in cart.
		 *
		 * @name wps_sfw_check_multistep
		 * @since 1.0.2
		 */
		function wps_sfw_check_multistep() {
			$bool = false;
			$wps_sfw_check = get_option( 'wps_sfw_multistep_done', false );
			$wps_sfw_enable_plugin = get_option( 'wps_sfw_enable_plugin', false );
			if ( ! empty( $wps_sfw_check ) && 'on' == $wps_sfw_enable_plugin ) {
				$bool = true;
			}

			return $bool;
		}
	}
	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_subscriptions_for_woocommerce() {

		define_subscriptions_for_woocommerce_constants();

		$sfw_sfw_plugin_standard = new Subscriptions_For_Woocommerce();
		$sfw_sfw_plugin_standard->sfw_run();
		$GLOBALS['sfw_wps_sfw_obj'] = $sfw_sfw_plugin_standard;
		$GLOBALS['wps_sfw_notices'] = false;
	}
	run_subscriptions_for_woocommerce();


	// Add settings link on plugin page.
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'subscriptions_for_woocommerce_settings_link' );

	/**
	 * Settings link.
	 *
	 * @since    1.0.0
	 * @param   Array $links    Settings link array.
	 */
	function subscriptions_for_woocommerce_settings_link( $links ) {

		$my_link = array(
			'<a href="' . admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu' ) . '">' . __( 'Settings', 'subscriptions-for-woocommerce' ) . '</a>',
		);
		if ( ! is_plugin_active( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' ) ) {

			$my_link['goPro'] = '<a class="wps-sfw-go-pro" target="_blank" href="https://wpswings.com/product/subscriptions-for-woocommerce-pro?utm_source=wpswings-subs-pro&utm_medium=subs-org-backend&utm_campaign=go-pro">' . esc_html__( 'GO PRO', 'subscriptions-for-woocommerce' ) . '</a>';
		}
		return array_merge( $my_link, $links );
	}

	add_filter( 'plugin_row_meta', 'wps_sfw_doc_and_premium_link', 10, 2 );

	/**
	 * Callable function for adding plugin row meta.
	 *
	 * @name wps_sfw_doc_and_premium_link.
	 * @param string $links link of the constant.
	 * @param array  $file name of the plugin.
	 */
	function wps_sfw_doc_and_premium_link( $links, $file ) {

		if ( strpos( $file, 'subscriptions-for-woocommerce.php' ) !== false ) {

			$row_meta = array(
				'demo' => '<a target="_blank" href="https://demo.wpswings.com/subscriptions-for-woocommerce-pro/?utm_source=wpswings-subs-demo&utm_medium=subs-org-backend&utm_campaign=demo"><img src="' . esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/images/Demo.svg" class="wps-info-img" alt="Demo image">' . esc_html__( 'Demo', 'subscriptions-for-woocommerce' ) . '</a>',

				'docs'    => '<a target="_blank" href="https://docs.wpswings.com/subscriptions-for-woocommerce/?utm_source=wpswings-subs-doc&utm_medium=subs-org-backend&utm_campaign=documentation"><img src="' . esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/images/Documentation.svg" class="wps-info-img" alt="documentation image">' . esc_html__( 'Documentation', 'subscriptions-for-woocommerce' ) . '</a>',

				'video'    => '<a target="_blank" href="https://www.youtube.com/watch?v=2VFyxZl3l-A"><img src="' . esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/images/YouTube_32px.svg" class="wps-info-img" alt="video image">' . esc_html__( 'Video', 'subscriptions-for-woocommerce' ) . '</a>',

				'support' => '<a target="_blank" href="https://wpswings.com/submit-query/?utm_source=wpswings-subs-support&utm_medium=subs-org-backend&utm_campaign=support"><img src="' . esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/images/Support.svg" class="wps-info-img" alt="support image">' . esc_html__( 'Support', 'subscriptions-for-woocommerce' ) . '</a>',

				'services' => '<a target="_blank" href="https://wpswings.com/woocommerce-services/?utm_source=wpswings-subs-services&utm_medium=subs-pro-backend&utm_campaign=woocommerce-services"><img src="' . esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/images/Services.svg" class="wps-info-img" alt="services image">' . esc_html__( 'Services', 'subscriptions-for-woocommerce' ) . '</a>',

			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	register_activation_hook( __FILE__, 'wps_sfw_flush_rewrite_rules' );
	register_deactivation_hook( __FILE__, 'wps_sfw_flush_rewrite_rules' );

	/**
	 * This function is used to create tabs
	 *
	 * @name wps_sfw_flush_rewrite_rules
	 * @since 1.0.0.
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	function wps_sfw_flush_rewrite_rules() {
		add_rewrite_endpoint( 'wps_subscriptions', EP_PAGES );
		add_rewrite_endpoint( 'show-subscription', EP_PAGES );
		add_rewrite_endpoint( 'wps-add-payment-method', EP_PAGES );
		flush_rewrite_rules();
	}

	add_action( 'init', 'wps_sfw_register_custom_order_types' );

	/**
	 * This function is used to create custom post type for subscription.
	 *
	 * @name wps_sfw_register_custom_order_types
	 * @since 1.0.0
	 */
	function wps_sfw_register_custom_order_types() {
		if ( function_exists( 'wc_register_order_type' ) ) {
			wc_register_order_type(
				'wps_subscriptions',
				apply_filters(
					'wps_sfw_register_custom_order_types',
					array(
						'labels'                           => array(
							'name'               => __( 'WPS Subscriptions', 'subscriptions-for-woocommerce' ),
							'singular_name'      => __( 'WPS Subscription', 'subscriptions-for-woocommerce' ),
							'add_new'            => __( 'Add WPS Subscription', 'subscriptions-for-woocommerce' ),
							'add_new_item'       => __( 'Add New WPS Subscription', 'subscriptions-for-woocommerce' ),
							'edit'               => __( 'Edit', 'subscriptions-for-woocommerce' ),
							'edit_item'          => __( 'Edit WPS Subscription', 'subscriptions-for-woocommerce' ),
							'new_item'           => __( 'New WPS Subscription', 'subscriptions-for-woocommerce' ),
							'view'               => __( 'View WPS Subscription', 'subscriptions-for-woocommerce' ),
							'view_item'          => __( 'View WPS Subscription', 'subscriptions-for-woocommerce' ),
							'search_items'       => __( 'Search WPS Subscriptions', 'subscriptions-for-woocommerce' ),
							'not_found'          => __( 'Not Found', 'subscriptions-for-woocommerce' ),
							'not_found_in_trash' => __( 'No WPS Subscriptions found in the trash', 'subscriptions-for-woocommerce' ),
							'parent'             => __( 'Parent WPS Subscriptions', 'subscriptions-for-woocommerce' ),
							'menu_name'          => __( 'WPS Subscriptions', 'subscriptions-for-woocommerce' ),
						),
						'description'                      => __( 'These WPS subscriptions are stored.', 'subscriptions-for-woocommerce' ),
						'public'                           => false,
						'show_ui'                          => true,
						'capability_type'                  => 'shop_order',
						'map_meta_cap'                     => true,
						'publicly_queryable'               => false,
						'exclude_from_search'              => true,
						'show_in_menu'                     => true,
						'hierarchical'                     => false,
						'show_in_nav_menus'                => true,
						'rewrite'                          => false,
						'query_var'                        => false,
						'supports'                         => array( 'title', 'comments', 'custom-fields' ),
						'has_archive'                      => false,
						'exclude_from_orders_screen'       => true,
						'add_order_meta_boxes'             => true,
						'exclude_from_order_count'         => true,
						'exclude_from_order_views'         => true,
						'exclude_from_order_webhooks'      => true,
						'exclude_from_order_reports'       => true,
						'exclude_from_order_sales_reports' => true,
						'class_name'                       => 'WPS_Subscription',
					)
				)
			);
		}
	}

	if ( is_plugin_active( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' ) ) {
		$sfw_plugins = get_plugins();
		if ( $sfw_plugins['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php']['Version'] < '2.1.0' ) {
			sleep( 30 );
			deactivate_plugins( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' );
		}
	}
	/**
	 * Load custom payment gateway.
	 *
	 * @param array $methods array containing the payment methods in WooCommerce.
	 * @since 1.0.0
	 * @return array
	 */
	function wps_paypal_integration_for_woocommerce_extended( $methods ) {
		$methods[] = 'WC_Gateway_Wps_Paypal_Integration';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'wps_paypal_integration_for_woocommerce_extended' );

	/**
	 * Load the WPS Paypal and Woommerce Stripe.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function wps_paypal_integration_for_woocommerce_gateway() {
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-wc-gateway-wps-paypal-integration.php';
		}
	}
	add_action( 'plugin_loaded', 'wps_paypal_integration_for_woocommerce_gateway' );

	/**
	 * Replace the main gateway with the sources gateway.
	 *
	 * @param array $methods List of gateways.
	 *
	 * @return array
	 */
	function wps_add_stripe_integration_gateway( $methods ) {
		foreach ( $methods as $key => $method ) {
			if ( 'WC_Gateway_Stripe' === $method || $method instanceof WC_Gateway_Stripe ) {
				$methods[ $key ] = 'Wps_Subscriptions_Payment_Stripe';
			}
			if ( 'WC_Gateway_Stripe_Sepa' === $method || $method instanceof WC_Gateway_Stripe_Sepa ) {
				$methods[ $key ] = 'Wps_Subscriptions_Payment_Stripe_Sepa';
			}
		}

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'wps_add_stripe_integration_gateway' );

	/**
	 * Allow to enable/diasble paypal standard
	 */
	function wps_sfw_enable_paypal_standard() {
		$check_paypal_standard = get_option( 'wps_sfw_enable_paypal_standard', 'no' );
		if ( 'on' === $check_paypal_standard ) {
			add_filter( 'woocommerce_should_load_paypal_standard', '__return_true' );
		}
		if ( class_exists( '\WC_Gateway_Stripe' ) && version_compare( WC_STRIPE_VERSION, '4.1.11', '>' ) ) {
			include_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/stripe-sepa/class-wps-subscriptions-payment-stripe-sepa.php';
			include_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/gateways/stripe/class-wps-subscriptions-payment-stripe.php';
		}
	}
	add_action( 'init', 'wps_sfw_enable_paypal_standard' );

	/**
	 *
	 * Get the data from the order table if hpos enabled otherwise default working.
	 *
	 * @param int    $id .
	 * @param string $key .
	 * @param int    $v .
	 */
	function wps_sfw_get_meta_data( $id, $key, $v ) {

		if ( 'shop_order' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

			// HPOS usage is enabled.

			$order    = wc_get_order( $id );

			$meta_val = $order->get_meta( $key );

			return $meta_val;

		} elseif ( 'wps_subscriptions' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			$order    = new WPS_Subscription( $id );
			$meta_val = $order->get_meta( $key );

			return $meta_val;
		} else {
			// Traditional CPT-based orders are in use.
			$meta_val = get_post_meta( $id, $key, $v );

			return $meta_val;
		}
	}
	/**
	 *
	 * Update the data into the order table if hpos enabled otherwise default working.
	 *
	 * @param int               $id .
	 * @param string            $key .
	 * @param init|array|object $value .
	 */
	function wps_sfw_update_meta_data( $id, $key, $value ) {

		if ( 'shop_order' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

			// HPOS usage is enabled.
			$order = wc_get_order( $id );

			$order->update_meta_data( $key, $value );
			$order->save();

		} elseif ( 'wps_subscriptions' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			$order = new WPS_Subscription( $id );

			$order->update_meta_data( $key, $value );
			$order->save();
		} else {
			// Traditional CPT-based orders are in use.
			update_post_meta( $id, $key, $value );
		}
	}

	add_action(
		'admin_notices',
		function () {
			?>
		<style>
			#toplevel_page_woocommerce a[href='admin.php?page=wc-orders--wps_subscriptions'] {
				display:none;
			}
		</style>
			<?php
		}
	);

	/**
	 * Function to Remove subscription menu.
	 *
	 * @return void
	 */
	function wps_sfw_remove_custom_woocommerce_menu() {
		global $submenu, $pagenow;

		// Allow direct access.
		if ( isset( $_GET['page'] ) && 'wc-orders--wps_subscriptions' == $_GET['page'] && isset( $_GET['action'] ) && 'new' == $_GET['action'] ) {
			return;
		}

		// Remove the submenu from WooCommerce.
		remove_submenu_page( 'woocommerce', 'wc-orders--wps_subscriptions' );
	}
	add_action( 'admin_menu', 'wps_sfw_remove_custom_woocommerce_menu', 999 );
	// HPOS Compatibility for Custom Order type i.e. WPS_Subscription.
	add_action(
		'woocommerce_init',
		function () {
			require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-wps-subscription.php';
		}
	);

	/**
	 * Create a new subscription
	 *
	 * @param array() $args .
	 */
	function wps_create_subscription( $args = array() ) {
		$now   = gmdate( 'Y-m-d H:i:s' );
		$order = ( isset( $args['order_id'] ) ) ? wc_get_order( $args['order_id'] ) : null;

		$default_args = array(
			'status'             => 'wc-wps_renewal',
			'order_id'           => 0,
			'customer_note'      => null,
			'customer_id'        => null,
			'date_created'       => $now,
			'created_via'        => '',
			'currency'           => get_woocommerce_currency(),
			'prices_include_tax' => get_option( 'woocommerce_prices_include_tax' ),
		);

		if ( $order instanceof \WC_Order ) {
			$default_args['customer_id']        = $order->get_user_id();
			$default_args['created_via']        = $order->get_created_via( 'edit' );
			$default_args['currency']           = $order->get_currency( 'edit' );
			$default_args['prices_include_tax'] = $order->get_prices_include_tax( 'edit' ) ? 'yes' : 'no';
			$default_args['date_created']       = $order->get_date_created( 'edit' );
		}

		$args = wp_parse_args( $args, $default_args );

		$subscription = new \WPS_Subscription();

		if ( $args['status'] ) {
			$subscription->set_status( $args['status'] );
		}

		$subscription->set_customer_id( $args['customer_id'] );
		$subscription->set_date_created( $args['date_created'] );
		$subscription->set_created_via( $args['created_via'] );
		$subscription->set_currency( $args['currency'] );
		$subscription->set_prices_include_tax( 'no' !== $args['prices_include_tax'] );

		$subscription->save();

		return $subscription;
	}
	// code to register subscription product type.
	add_action( 'init', 'register_subscription_box_product_type' );
	/**
	 * Function to Regsiter Subscription box type.
	 *
	 * @return string
	 */
	function register_subscription_box_product_type() {
		if ( 'on' == get_option( 'wsp_enable_subscription_box_features' ) && class_exists( 'WC_Product' ) ) {
			/**
			 * Extend Product class.
			 */
			class WC_Product_Subscription_Box extends WC_Product {
				/**
				 * Construct.
				 *
				 * @param object $product as product.
				 */
				public function __construct( $product ) {
					parent::__construct( $product );
				}

				/**
				 * Get type function
				 */
				public function get_type() {
					return 'subscription_box';
				}
			}
		}
	}

} else {
	// WooCommerce is not active so deactivate this plugin.
	add_action( 'admin_init', 'wps_sfw_activation_failure' );

	/**
	 * Deactivate this plugin.
	 *
	 * @name wps_sfw_activation_failure
	 * @since 1.0.0
	 */
	function wps_sfw_activation_failure() {

		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	// Add admin error notice.
	add_action( 'admin_notices', 'wps_sfw_activation_failure_admin_notice' );
	add_action( 'network_admin_notices', 'wps_sfw_activation_failure_admin_notice' );

	/**
	 * This function is used to display admin error notice when WooCommerce is not active.
	 *
	 * @name wps_sfw_activation_failure_admin_notice
	 * @since 1.0.0
	 */
	function wps_sfw_activation_failure_admin_notice() {
		// to hide Plugin activated notice.
		unset( $_GET['activate'] );
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'WooCommerce is not activated, Please activate WooCommerce first to activate Subscriptions for Woocommerce.', 'subscriptions-for-woocommerce' ); ?></p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'wps_subscripition_plugin_updation_notice' );

if ( ! function_exists( 'wps_subscripition_plugin_updation_notice' ) ) {
	/**
	 * Migration Notice.
	 *
	 * @return void
	 */
	function wps_subscripition_plugin_updation_notice() {
		$sfw_plugins = get_plugins();
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( ! empty( $screen->id ) && 'plugins' === $screen->id ) {
				$old_pro_plugin = false;
				$plug           = get_plugins();
				if ( isset( $plug['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php'] ) ) {
					if ( version_compare( $plug['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php']['Version'], '2.1.0', '<' ) ) {
						$old_pro_plugin = true;
					}
				}
				if ( $old_pro_plugin ) {
					?>
					<div class="notice notice-error is-dismissible">
						<p><strong><?php esc_html_e( 'Version 2.1.0 of Subscriptions For WooCommerce Pro ', 'subscriptions-for-woocommerce' ); ?></strong><?php esc_html_e( ' is not available on your system! Please Update ', 'subscriptions-for-woocommerce' ); ?><strong><?php esc_html_e( 'WooCommerce Subscripiton Pro', 'subscriptions-for-woocommerce' ); ?></strong><?php esc_html_e( '.', 'subscriptions-for-woocommerce' ); ?></p>
					</div>
					<?php
				}
			}
		}
	}
}

add_action( 'admin_notices', 'wps_banner_notification_plugin_html' );
if ( ! function_exists( 'wps_banner_notification_plugin_html' ) ) {
	/**
	 * Common Function To show banner image.
	 *
	 * @return void
	 */
	function wps_banner_notification_plugin_html() {

		$screen = get_current_screen();
		if ( isset( $screen->id ) ) {
			$pagescreen = $screen->id;
		}
		if ( ( isset( $pagescreen ) && 'plugins' === $pagescreen ) || ( 'wp-swings_page_home' == $pagescreen ) ) {
			$banner_id = get_option( 'wps_wgm_notify_new_banner_id', false );
			if ( isset( $banner_id ) && '' !== $banner_id ) {
				$hidden_banner_id            = get_option( 'wps_wgm_notify_hide_baneer_notification', false );
				$banner_image = get_option( 'wps_wgm_notify_new_banner_image', '' );
				$banner_url = get_option( 'wps_wgm_notify_new_banner_url', '' );
				if ( isset( $hidden_banner_id ) && $hidden_banner_id < $banner_id ) {

					if ( '' !== $banner_image && '' !== $banner_url ) {

						?>
							<div class="wps-offer-notice notice notice-warning is-dismissible">                
								<div class="notice-container">
									<a href="<?php echo esc_url( $banner_url ); ?>" target="_blank"><img src="<?php echo esc_url( $banner_image ); ?>" alt="Subscription cards"/></a>
								</div>
								<button type="button" class="notice-dismiss dismiss_banner" id="dismiss-banner"><span class="screen-reader-text">Dismiss this notice.</span></button>
							</div>
						<?php
					}
				}
			}
		}
	}
}
add_action( 'admin_notices', 'wps_sfw_banner_notification_html' );
/**
 * Function to show banner image based on subscription.
 *
 * @return void
 */
function wps_sfw_banner_notification_html() {
	$screen = get_current_screen();
	if ( isset( $screen->id ) ) {
		$pagescreen = $screen->id;
	}
	if ( ( isset( $_GET['page'] ) && 'subscriptions_for_woocommerce_menu' === $_GET['page'] ) ) {
		$banner_id = get_option( 'wps_wgm_notify_new_banner_id', false );
		if ( isset( $banner_id ) && '' !== $banner_id ) {
			$hidden_banner_id            = get_option( 'wps_wgm_notify_hide_baneer_notification', false );
			$banner_image = get_option( 'wps_wgm_notify_new_banner_image', '' );
			$banner_url = get_option( 'wps_wgm_notify_new_banner_url', '' );
			if ( isset( $hidden_banner_id ) && $hidden_banner_id < $banner_id ) {

				if ( '' !== $banner_image && '' !== $banner_url ) {

					?>
							<div class="wps-offer-notice notice notice-warning is-dismissible">
								<div class="notice-container">
									<a href="<?php echo esc_url( $banner_url ); ?>"target="_blank"><img src="<?php echo esc_url( $banner_image ); ?>" alt="Subscription cards"/></a>
								</div>
								<button type="button" class="notice-dismiss dismiss_banner" id="dismiss-banner"><span class="screen-reader-text">Dismiss this notice.</span></button>
							</div>
							
						<?php
				}
			}
		}
	}
}

