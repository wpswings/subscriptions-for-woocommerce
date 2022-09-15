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
 * Version:           1.4.4
 * Author:            WP Swings
 * Author URI:        https://wpswings.com/?utm_source=wpswings-subs-official&utm_medium=subs-org-backend&utm_campaign=official
 * Text Domain:       subscriptions-for-woocommerce
 * Domain Path:       /languages
 *
 * Requires at least:        5.0
 * Tested up to:             6.0.2
 * WC requires at least:     5.0
 * WC tested up to:          6.8.2
 *
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$old_pro_exists = false;
$plug           = get_plugins();
if ( isset( $plug['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php'] ) ) {
	if ( version_compare( $plug['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php']['Version'], '2.1.0', '<' ) ) {
		$old_pro_exists = true;
	}
}
add_action( 'after_plugin_row_woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', 'wps_sfw_old_upgrade_notice', 0, 3 );
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

	}//end wps_sfw_lite_add_updatenow_notice()
	add_action( 'admin_notices', 'wps_sfw_check_and_inform_update' );

	/**
	 * Check update if pro is old.
	 */
	function wps_sfw_check_and_inform_update() {
		$update_file = plugin_dir_path( dirname( __FILE__ ) ) . 'woocommerce-subscriptions-pro/class-woocommerce-subscriptions-pro-update.php';

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
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/**
	 * Define plugin constants.
	 *
	 * @since             1.0.0
	 */
	function define_subscriptions_for_woocommerce_constants() {

		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION', '1.4.4' );
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
	 */
	function activate_subscriptions_for_woocommerce() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-subscriptions-for-woocommerce-activator.php';
		Subscriptions_For_Woocommerce_Activator::subscriptions_for_woocommerce_activate();
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
	 */
	function deactivate_subscriptions_for_woocommerce() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-subscriptions-for-woocommerce-deactivator.php';
		Subscriptions_For_Woocommerce_Deactivator::subscriptions_for_woocommerce_deactivate();
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

	register_activation_hook( __FILE__, 'activate_subscriptions_for_woocommerce' );
	register_deactivation_hook( __FILE__, 'deactivate_subscriptions_for_woocommerce' );

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
		wc_register_order_type(
			'wps_subscriptions',
			apply_filters(
				'wps_sfw_register_custom_order_types',
				array(
					'labels'                           => array(
						'name'               => __( 'Subscriptions', 'subscriptions-for-woocommerce' ),
						'singular_name'      => __( 'Subscription', 'subscriptions-for-woocommerce' ),
						'add_new'            => __( 'Add Subscription', 'subscriptions-for-woocommerce' ),
						'add_new_item'       => __( 'Add New Subscription', 'subscriptions-for-woocommerce' ),
						'edit'               => __( 'Edit', 'subscriptions-for-woocommerce' ),
						'edit_item'          => __( 'Edit Subscription', 'subscriptions-for-woocommerce' ),
						'new_item'           => __( 'New Subscription', 'subscriptions-for-woocommerce' ),
						'view'               => __( 'View Subscription', 'subscriptions-for-woocommerce' ),
						'view_item'          => __( 'View Subscription', 'subscriptions-for-woocommerce' ),
						'search_items'       => __( 'Search Subscriptions', 'subscriptions-for-woocommerce' ),
						'not_found'          => __( 'Not Found', 'subscriptions-for-woocommerce' ),
						'not_found_in_trash' => __( 'No Subscriptions found in the trash', 'subscriptions-for-woocommerce' ),
						'parent'             => __( 'Parent Subscriptions', 'subscriptions-for-woocommerce' ),
						'menu_name'          => __( 'Subscriptions', 'subscriptions-for-woocommerce' ),
					),
					'description'                      => __( 'These subscriptions are stored.', 'subscriptions-for-woocommerce' ),
					'public'                           => false,
					'show_ui'                          => true,
					'capability_type'                  => 'shop_order',
					'map_meta_cap'                     => true,
					'publicly_queryable'               => false,
					'exclude_from_search'              => true,
					'show_in_menu'                     => false,
					'hierarchical'                     => false,
					'show_in_nav_menus'                => false,
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
				)
			)
		);
	}
	add_action( 'activated_plugin', 'wps_sfe_redirect_on_settings' );

	if ( ! function_exists( 'wps_sfe_redirect_on_settings' ) ) {
		/**
		 * This function is used to check plugin.
		 *
		 * @name wps_sfe_redirect_on_settings
		 * @param string $plugin plugin.
		 * @since 1.0.3
		 */
		function wps_sfe_redirect_on_settings( $plugin ) {
			if ( plugin_basename( __FILE__ ) === $plugin ) {
				$general_settings_url = admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu' );
				wp_safe_redirect( esc_url( $general_settings_url ) );
				exit();
			}
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
	 * Extending main WC_Payment_Gateway class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function wps_paypal_integration_for_woocommerce_gateway() {
		require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-wc-gateway-wps-paypal-integration.php';
	}

	add_action( 'init', 'wps_paypal_integration_for_woocommerce_gateway' );
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
			if ( true === $old_pro_plugin ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><strong><?php esc_html_e( 'Version 2.1.0 of Subscriptions For WooCommerce Pro ', 'subscriptions-for-woocommerce' ); ?></strong><?php esc_html_e( ' is not available on your system! Please Update ', 'subscriptions-for-woocommerce' ); ?><strong><?php esc_html_e( 'WooCommerce Subscripiton Pro', 'subscriptions-for-woocommerce' ); ?></strong><?php esc_html_e( '.', 'subscriptions-for-woocommerce' ); ?></p>
				</div>
				<?php
			}
		}
	}
}
add_action( 'admin_init', 'migration_work_for_db_key' );
/**
 * Migration_work_for_db_key function.
 *
 * @return void
 */
function migration_work_for_db_key() {
	$wps_upgrade_sfw_wp_migration_option_check = get_option( 'wps_upgrade_sfw_wp_migration_option_check', 'not_done' );
	if ( 'not_done' === $wps_upgrade_sfw_wp_migration_option_check ) {
		add_rewrite_endpoint( 'wps_subscriptions', EP_PAGES );
		add_rewrite_endpoint( 'show-subscription', EP_PAGES );
		add_rewrite_endpoint( 'wps-add-payment-method', EP_PAGES );
		flush_rewrite_rules();

		subscriptions_for_woocommerce_upgrade_wp_options();
		subscriptions_for_woocommerce_pro_upgrade_wp_options();

		update_option( 'wps_upgrade_sfw_wp_migration_option_check', 'done' );
	}
}
/**
 * Short Description. (use period)
 *
 * Long Description.
 *
 * @since    1.0.0
 */
function subscriptions_for_woocommerce_pro_upgrade_wp_options() {

	$wp_options = array(
		'mwb_wsp_plugin_update',
		'mwb_all_plugins_active',
		'mwb_wsp_license_key',
		'mwb_wsp_license_key_status',
		'mwb_wsp_lcns_thirty_days',
		'mwb_wsp_upgrade_downgrade_btn_text',
		'mwb_wsp_manage_prorate_amount',
	);

	foreach ( $wp_options as $index => $key ) {
		$new_key = str_replace( 'mwb', 'wps', $key );

		if ( ! empty( get_option( $new_key ) ) ) {
			continue;
		}

		$new_value = get_option( $key );
		update_option( $new_key, $new_value );
	}
}
/**
 * Short Description. (use period)
 *
 * Long Description.
 *
 * @since    1.0.0
 */
function subscriptions_for_woocommerce_upgrade_wp_options() {

		$wp_options = array(
			'mwb_sfw_enable_tracking'                       => '',
			'mwb_sfw_enable_plugin'                         => '',
			'mwb_sfw_add_to_cart_text'                      => '',
			'mwb_sfw_place_order_button_text'               => '',
			'mwb_sfw_multistep_product_create_done'         => '',
			'mwb_sfw_multistep_done'                        => '',
			'mwb_sfw_onboarding_data_skipped'               => '',
			'mwb_sfw_onboarding_data_sent'                  => '',
		);

		foreach ( $wp_options as $key => $value ) {
			$new_key = str_replace( 'mwb_', 'wps_', $key );

			if ( ! empty( get_option( $new_key ) ) ) {
				continue;
			}

			$new_value = get_option( $key, $value );
			update_option( $new_key, $new_value );
		}
}
