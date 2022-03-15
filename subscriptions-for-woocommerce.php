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
 * Description:       With Subscriptions for WooCommerce, allow the WooCommerce merchants to sell their subscriptions and avail recurring revenue.
 * Version:           1.3.2
 * Author:            WP Swings
 * Author URI:        https://wpswings.com/?utm_source=wpswings-subs-home&utm_medium=subs-org-backend&utm_campaign=home
 * Text Domain:       subscriptions-for-woocommerce
 * Domain Path:       /languages
 *
 * Requires at least:        4.6
 * Tested up to:             5.9.2
 * WC requires at least:     4.0
 * WC tested up to:          6.3.1
 *
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' ) ) {

	deactivate_plugins( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' );
	$general_settings_url = admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu' );
	header( 'Location: ' . $general_settings_url );
}
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/**
	 * Define plugin constants.
	 *
	 * @since             1.0.0
	 */
	function define_subscriptions_for_woocommerce_constants() {

		subscriptions_for_woocommerce_constants( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION', '1.3.2' );
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

		?>

	<tr class="plugin-update-tr active notice-warning notice-alt">
	<td colspan="4" class="plugin-update colspanchange">
		<div class="notice notice-success inline update-message notice-alt">
			<div class='wps-notice-title wps-notice-section'>
				<p><strong>IMPORTANT NOTICE:</strong></p>
			</div>
			<div class='wps-notice-content wps-notice-section'>
				<p>From this update <strong>Version 1.3.1</strong> onwards, the plugin and its support will be handled by <strong>WP Swings</strong>.</p><p><strong>WP Swings</strong> is just our improvised and rebranded version with all quality solutions and help being the same, so no worries at your end.
				Please connect with us for all setup, support, and update related queries without hesitation.</p>
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

	add_action( 'admin_notices', 'wps_sfw_plugin_upgrade_notice', 20 );

	/**
	 * Upgrade Notice for Subscription Plugin.
	 *
	 * @return void
	 */
	function wps_sfw_plugin_upgrade_notice() {
		$screen = get_current_screen();
		if ( isset( $screen->id ) && 'wp-swings_page_subscriptions_for_woocommerce_menu' === $screen->id ) {
			?>
		
		<tr class="plugin-update-tr active notice-warning notice-alt">
		<td colspan="4" class="plugin-update colspanchange">
			<div class="notice notice-success inline update-message notice-alt">
				<div class='wps-notice-title wps-notice-section'>
					<p><strong>IMPORTANT NOTICE:</strong></p>
				</div>
				<div class='wps-notice-content wps-notice-section'>
					<p>From this update <strong>Version 1.3.1</strong> onwards, the plugin and its support will be handled by <strong>WP Swings</strong>.</p><p><strong>WP Swings</strong> is just our improvised and rebranded version with all quality solutions and help being the same, so no worries at your end.
					Please connect with us for all setup, support, and update related queries without hesitation.</p>
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
			if ( ! empty( $wps_sfw_check ) ) {
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
		if ( ! is_plugin_active( 'subscriptions-for-woocommerce-pro/subscriptions-for-woocommerce-pro.php' ) ) {

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
				'demo' => '<a target="_blank" href="https://demo.wpswings.com/subscriptions-for-woocommerce-pro/?utm_source=wpswings-subs-demo&utm_medium=subs-org-backend&utm_campaign=demo"><img src="' . esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/images/Demo.svg" class="wps-info-img" alt="Demo image">' . esc_html__( 'Free Demo', 'subscriptions-for-woocommerce' ) . '</a>',

				'docs'    => '<a target="_blank" href="https://docs.wpswings.com/subscriptions-for-woocommerce/?utm_source=wpswings-subs-doc&utm_medium=subs-org-backend&utm_campaign=documentation"><img src="' . esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/images/Documentation.svg" class="wps-info-img" alt="documentation image">' . esc_html__( 'Documentation', 'subscriptions-for-woocommerce' ) . '</a>',

				'support' => '<a target="_blank" href="https://wpswings.com/submit-query/?utm_source=wpswings-subs-support&utm_medium=subs-org-backend&utm_campaign=support"><img src="' . esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/images/Support.svg" class="wps-info-img" alt="support image">' . esc_html__( 'Support', 'subscriptions-for-woocommerce' ) . '</a>',

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
			if ( $sfw_plugins['woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php']['Version'] < '2.0.2' && ! isset( $sfw_plugins['subscriptions-for-woocommerce-pro/subscriptions-for-woocommerce-pro.php'] ) ) {
				?>
	
				<div class="notice notice-error is-dismissible">
					<p><strong><?php esc_html_e( 'Version 2.0.2 of Woocommerce Subcription Pro ', 'subscriptions-for-woocommerce' ); ?></strong><?php esc_html_e( ' is not available on your system! Please Update ', 'subscriptions-for-woocommerce' ); ?><strong><?php esc_html_e( 'WooCommerce Subscripiton Pro', 'subscriptions-for-woocommerce' ); ?></strong><?php esc_html_e( '.', 'subscriptions-for-woocommerce' ); ?></p>
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
	if ( 'not_done' == $wps_upgrade_sfw_wp_migration_option_check ) {

		subscriptions_for_woocommerce_upgrade_wp_postmeta();
		subscriptions_for_woocommerce_upgrade_wp_options();

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
function subscriptions_for_woocommerce_upgrade_wp_postmeta() {
		$post_meta_keys = array(
			'_mwb_sfw_product',
			'mwb_sfw_subscription_number',
			'mwb_sfw_subscription_interval',
			'mwb_sfw_subscription_expiry_number',
			'mwb_sfw_subscription_expiry_interval',
			'mwb_sfw_subscription_initial_signup_price',
			'mwb_sfw_subscription_free_trial_number',
			'mwb_sfw_subscription_free_trial_interval',
			'mwb_sfw_subscription',
			'mwb_sfw_renewal_order',
			'mwb_sfw_parent_order_id',
			'mwb_renewal_subscription_order',
			'mwb_wsp_no_of_renewal_order',
			'mwb_wsp_renewal_order_data',
			'mwb_wsp_last_renewal_order_id',
			'mwb_next_payment_date',
			'mwb_subscription_status',
			'_mwb_paypal_transaction_ids',
			'_mwb_sfw_payment_transaction_id',
			'_mwb_paypal_subscription_id',
			'mwb_upgrade_downgrade_data',
			'mwb_susbcription_trial_end',
			'mwb_susbcription_end',
			'mwb_sfw_order_has_subscription',
			'mwb_subscription_id',
			'mwb_schedule_start',
			'mwb_sfw_subscription_activated',
			'mwb_parent_order',
			'mwb_recurring_total',
			'mwb_customer_id',
			'mwb_sfw_variable_product',
			'mwb_susbcription_end',
		);

		foreach ( $post_meta_keys as $key => $meta_keys ) {
			$products = get_posts(
				array(
					'numberposts' => -1,
					'post_status' => array( 'publish', 'draft', 'trash', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed' ),
					'fields'      => 'ids', // return only ids.
					'meta_key'    => $meta_keys, //phpcs:ignore
					'post_type'   => 'product',
					'order'       => 'ASC',
				)
			);
			if ( ! empty( $products ) && is_array( $products ) ) {
				foreach ( $products as $k => $product_id ) {
					$value   = get_post_meta( $product_id, $meta_keys, true );
					$new_key = str_replace( 'mwb_', 'wps_', $meta_keys );

					if ( ! empty( get_post_meta( $product_id, $new_key, true ) ) ) {
						continue;
					}
					update_post_meta( $product_id, $new_key, $value );
				}
			}
		}
		foreach ( $post_meta_keys as $key => $meta_keys ) {
			$products = get_posts(
				array(
					'numberposts' => -1,
					'post_status' => 'wc-mwb_renewal',
					'fields'      => 'ids', // return only ids.
					'meta_key'    => $meta_keys, //phpcs:ignore
					'post_type'   => 'mwb_subscriptions',
					'order'       => 'ASC',
				)
			);
			if ( ! empty( $products ) && is_array( $products ) ) {
				foreach ( $products as $k => $product_id ) {
					$value   = get_post_meta( $product_id, $meta_keys, true );
					$new_key = str_replace( 'mwb_', 'wps_', $meta_keys );

					if ( ! empty( get_post_meta( $product_id, $new_key, true ) ) ) {
						continue;
					}
					update_post_meta( $product_id, $new_key, $value );
				}
			}
		}
		foreach ( $post_meta_keys as $key => $meta_keys ) {
			$products = get_posts(
				array(
					'numberposts' => -1,
					'post_status' => array( 'wc-mwb_renewal', 'publish', 'draft', 'trash', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed' ),
					'fields'      => 'ids', // return only ids.
					'meta_key'    => $meta_keys, //phpcs:ignore
					'post_type'   => 'shop_order',
					'order'       => 'ASC',
				)
			);
			if ( ! empty( $products ) && is_array( $products ) ) {
				foreach ( $products as $k => $product_id ) {
					$value   = get_post_meta( $product_id, $meta_keys, true );
					$new_key = str_replace( 'mwb_', 'wps_', $meta_keys );

					if ( ! empty( get_post_meta( $product_id, $new_key, true ) ) ) {
						continue;
					}
					update_post_meta( $product_id, $new_key, $value );
				}
			}
		}
		$args = array(
			'numberposts' => -1,
			'post_type'   => 'mwb_subscriptions',
			'post_status' => 'wc-mwb_renewal',
			'meta_query' => array(
				array(
					'key'   => 'mwb_customer_id',
					'compare' => 'EXISTS',
				),
			),
		);
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$data           = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
			$args['meta_query'] = array(
				array(
					'key'   => 'mwb_parent_order',
					'value' => $data,
					'compare' => 'LIKE',
				),
			);
		}

		$wps_subscriptions = get_posts( $args );

		foreach ( $wps_subscriptions as $key => $value ) {
			$args = array();
			foreach ( $value as $key2 => $value2 ) {

				$new_value1 = str_replace( 'MWB', 'WPS', $value2 );
				$new_value = str_replace( 'mwb', 'wps', $new_value1 );

				$args[ $key2 ] = $new_value;

			}

			wp_update_post( $args );
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
