<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://makewebbetter.com/
 * @since             1.0.0
 * @package           Subscriptions_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Subscriptions For WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/search/subscriptions-for-woocommerce/
 * Description:       With Subscriptions for Woocommerce, allow the WooCommerce merchants to sell their subscriptions and avail recurring revenue.
 * Version:           1.0.0
 * Author:            MakeWebBetter
 * Author URI:        https://makewebbetter.com/
 * Text Domain:       subscriptions-for-woocommerce
 * Domain Path:       /languages
 *
 * Requires at least:        4.6
 * Tested up to:             5.7
 * WC requires at least:     4.0
 * WC tested up to:          5.1
 *
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/**
	 * Define plugin constants.
	 *
	 * @since             1.0.0
	 */
	function define_subscriptions_for_woocommerce_constants() {

		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION', '1.0.0' );
		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH', plugin_dir_path( __FILE__ ) );
		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL', plugin_dir_url( __FILE__ ) );
		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_SERVER_URL', 'https://makewebbetter.com' );
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

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-subscriptions-for-woocommerce-activator.php
	 */
	function activate_subscriptions_for_woocommerce() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-subscriptions-for-woocommerce-activator.php';
		Subscriptions_For_Woocommerce_Activator::subscriptions_for_woocommerce_activate();
		$mwb_sfw_active_plugin = get_option( 'mwb_all_plugins_active', false );
		if ( is_array( $mwb_sfw_active_plugin ) && ! empty( $mwb_sfw_active_plugin ) ) {
			$mwb_sfw_active_plugin['subscriptions-for-woocommerce'] = array(
				'plugin_name' => __( 'Subscriptions For Woocommerce', 'subscriptions-for-woocommerce' ),
				'active' => '1',
			);
		} else {
			$mwb_sfw_active_plugin = array();
			$mwb_sfw_active_plugin['subscriptions-for-woocommerce'] = array(
				'plugin_name' => __( 'Subscriptions For Woocommerce', 'subscriptions-for-woocommerce' ),
				'active' => '1',
			);
		}
		update_option( 'mwb_all_plugins_active', $mwb_sfw_active_plugin );
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-subscriptions-for-woocommerce-deactivator.php
	 */
	function deactivate_subscriptions_for_woocommerce() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-subscriptions-for-woocommerce-deactivator.php';
		Subscriptions_For_Woocommerce_Deactivator::subscriptions_for_woocommerce_deactivate();
		$mwb_sfw_deactive_plugin = get_option( 'mwb_all_plugins_active', false );
		if ( is_array( $mwb_sfw_deactive_plugin ) && ! empty( $mwb_sfw_deactive_plugin ) ) {
			foreach ( $mwb_sfw_deactive_plugin as $mwb_sfw_deactive_key => $mwb_sfw_deactive ) {
				if ( 'subscriptions-for-woocommerce' === $mwb_sfw_deactive_key ) {
					$mwb_sfw_deactive_plugin[ $mwb_sfw_deactive_key ]['active'] = '0';
				}
			}
		}
		update_option( 'mwb_all_plugins_active', $mwb_sfw_deactive_plugin );
	}

	register_activation_hook( __FILE__, 'activate_subscriptions_for_woocommerce' );
	register_deactivation_hook( __FILE__, 'deactivate_subscriptions_for_woocommerce' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-subscriptions-for-woocommerce.php';


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

		$sfw_plugin_standard = new Subscriptions_For_Woocommerce();
		$sfw_plugin_standard->sfw_run();
		$GLOBALS['sfw_mwb_sfw_obj'] = $sfw_plugin_standard;

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
		return array_merge( $my_link, $links );
	}

	add_filter( 'plugin_row_meta', 'mwb_sfw_doc_and_premium_link', 10, 2 );

	/**
	 * Callable function for adding plugin row meta.
	 *
	 * @name mwb_sfw_doc_and_premium_link.
	 * @param string $links link of the constant.
	 * @param array  $file name of the plugin.
	 */
	function mwb_sfw_doc_and_premium_link( $links, $file ) {

		if ( strpos( $file, 'subscriptions-for-woocommerce.php' ) !== false ) {

			$row_meta = array(
				'demo' => '<a target="_blank" href="https://demo.makewebbetter.com/subscriptions-for-woocommerce/"><i class="fas fa-laptop" style="margin-right:3px;"></i>' . esc_html__( 'Free Demo', 'subscriptions-for-woocommerce' ) . '</a>',

				'docs'    => '<a target="_blank" href="https://docs.makewebbetter.com/subscriptions-for-woocommerce/"><i class="far fa-file-alt" style="margin-right:3px;"></i>' . esc_html__( 'Documentation', 'subscriptions-for-woocommerce' ) . '</a>',

				'support' => '<a target="_blank" href="https://makewebbetter.com/submit-query/"><i class="fas fa-user-ninja" style="margin-right:3px;"></i>' . esc_html__( 'Support', 'subscriptions-for-woocommerce' ) . '</a>',

			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	register_activation_hook( __FILE__, 'mwb_sfw_flush_rewrite_rules' );
	register_deactivation_hook( __FILE__, 'mwb_sfw_flush_rewrite_rules' );

	/**
	 * This function is used to create tabs
	 *
	 * @name mwb_sfw_flush_rewrite_rules
	 * @since 1.0.0.
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	function mwb_sfw_flush_rewrite_rules() {
		add_rewrite_endpoint( 'mwb_subscriptions', EP_PAGES );
		add_rewrite_endpoint( 'show-subscription', EP_PAGES );
		add_rewrite_endpoint( 'mwb-add-payment-method', EP_PAGES );
		flush_rewrite_rules();
	}

	add_action( 'init', 'mwb_register_custom_order_types' );

	/**
	 * This function is used to create custom post type for subscription.
	 *
	 * @name mwb_register_custom_order_types
	 * @since 1.0.0
	 */
	function mwb_register_custom_order_types() {
		wc_register_order_type(
			'mwb_subscriptions',
			apply_filters(
				'mwb_sfw_register_custom_order_types',
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
					'description'                      => __( 'This subscriptions are stored.', 'subscriptions-for-woocommerce' ),
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
} else {
	// WooCommerce is not active so deactivate this plugin.
	add_action( 'admin_init', 'mwb_sfw_activation_failure' );

	/**
	 * Deactivate this plugin.
	 *
	 * @name mwb_sfw_activation_failure
	 * @since 1.0.0
	 */
	function mwb_sfw_activation_failure() {

		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	// Add admin error notice.
	add_action( 'admin_notices', 'mwb_sfw_activation_failure_admin_notice' );

	/**
	 * This function is used to display admin error notice when WooCommerce is not active.
	 *
	 * @name mwb_sfw_activation_failure_admin_notice
	 * @since 1.0.0
	 */
	function mwb_sfw_activation_failure_admin_notice() {

		// to hide Plugin activated notice.
		unset( $_GET['activate'] );

		?>

		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'WooCommerce is not activated, Please activate WooCommerce first to activate Subscriptions for Woocommerce.', 'subscriptions-for-woocommerce' ); ?></p>
		</div>

		<?php
	}
}
