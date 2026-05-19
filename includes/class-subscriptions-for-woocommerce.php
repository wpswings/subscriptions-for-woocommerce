<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes
 * @author     WP Swings <webmaster@wpswings.com>
 */
class Subscriptions_For_Woocommerce {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Subscriptions_For_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $sfw_onboard    To initializsed the object of class onboard.
	 */
	protected $sfw_onboard;

	/**
	 * Whether payment integration has been loaded for this request.
	 *
	 * @var bool
	 */
	private $wps_sfw_payment_integration_loaded = false;

	/**
	 * Get the outgoing IP address with caching and a short timeout.
	 *
	 * @return string
	 */
	private function wps_sfw_get_outgoing_ip_address() {
		$cache_key = 'wps_sfw_outgoing_ip';
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (string) $cached;
		}

		$response = wp_remote_get(
			'https://ipecho.net/plain',
			array(
				'timeout'     => 2,
				'redirection' => 1,
			)
		);

		if ( is_wp_error( $response ) ) {
			set_transient( $cache_key, '', HOUR_IN_SECONDS );
			return '';
		}

		$ip = trim( (string) wp_remote_retrieve_body( $response ) );
		if ( '' === $ip ) {
			set_transient( $cache_key, '', HOUR_IN_SECONDS );
			return '';
		}

		set_transient( $cache_key, $ip, DAY_IN_SECONDS );
		return $ip;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		if ( defined( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION' ) ) {
			$this->version = SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION;
		} else {

			$this->version = '1.9.6';
		}

		$this->plugin_name = 'subscriptions-for-woocommerce';

		$this->subscriptions_for_woocommerce_dependencies();
		$this->subscriptions_for_woocommerce_locale();
		if ( is_admin() ) {
			$this->subscriptions_for_woocommerce_admin_hooks();
		}
		$this->subscriptions_for_woocommerce_public_hooks();

		$this->subscriptions_for_woocommerce_api_hooks();
		$this->init();

		// Gate payment gateway integration includes to only the requests that need them.
		$this->loader->add_action( 'init', $this, 'wps_sfw_maybe_init_payment_integration', 5 );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Subscriptions_For_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - Subscriptions_For_Woocommerce_i18n. Defines internationalization functionality.
	 * - Subscriptions_For_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - Subscriptions_For_Woocommerce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function subscriptions_for_woocommerce_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-subscriptions-for-woocommerce-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-subscriptions-for-woocommerce-i18n.php';

		if ( is_admin() ) {

			// The class responsible for defining all actions that occur in the admin area.
			require_once plugin_dir_path( __DIR__ ) . 'admin/class-subscriptions-for-woocommerce-admin.php';
			require_once plugin_dir_path( __DIR__ ) . 'includes/class-subscriptions-for-woocommerce-talk-to-expert-form.php';

			// The class responsible for on-boarding steps for plugin.
			if ( is_dir( plugin_dir_path( __DIR__ ) . 'onboarding' ) && ! class_exists( 'Subscriptions_For_Woocommerce_Onboarding_Steps' ) ) {

				require_once plugin_dir_path( __DIR__ ) . 'includes/class-subscriptions-for-woocommerce-onboarding-steps.php';
			}

			if ( class_exists( 'Subscriptions_For_Woocommerce_Onboarding_Steps' ) ) {
				$sfw_onboard_steps = new Subscriptions_For_Woocommerce_Onboarding_Steps();
			}
		}

		// The class responsible for defining all actions that occur in the public-facing side of the site.
		require_once plugin_dir_path( __DIR__ ) . 'public/class-subscriptions-for-woocommerce-public.php';

		require_once plugin_dir_path( __DIR__ ) . 'package/rest-api/class-subscriptions-for-woocommerce-rest-api.php';

		require_once plugin_dir_path( __DIR__ ) . 'includes/subscriptions-for-woocommerce-common-function.php';

		$this->loader = new Subscriptions_For_Woocommerce_Loader();

		/**
		 * Include the log file.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-subscriptions-for-woocommerce-log.php';
		/**
		 * Include the cron file.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-subscriptions-for-woocommerce-scheduler.php';
	}
	/**
	 * The function is used to include email class.
	 */
	public function init() {
		add_filter( 'woocommerce_email_classes', array( $this, 'wps_sfw_woocommerce_email_classes' ) );
	}

	/**
	 * The function is used to include payment gateway integration.
	 */
	public function wps_sfw_init_payment_integration() {

		$wps_sfw_dir = plugin_dir_path( __DIR__ ) . 'package/gateways';
		wps_sfw_include_process_directory( $wps_sfw_dir );
		do_action( 'wps_sfw_payment_integration' );
	}

	/**
	 * Decide whether payment gateway integrations should be loaded for this request.
	 *
	 * Loading every gateway on every request increases TTFB significantly. We only need
	 * these integrations on WooCommerce flows (cart/checkout/account), background tasks
	 * (cron), AJAX/REST, and relevant admin pages.
	 *
	 * @return bool
	 */
	private function wps_sfw_should_load_payment_integration() {
		$should_load = false;

		if ( is_admin() ) {
			$should_load = true;
		}

		if ( ! $should_load && function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			$should_load = true;
		}

		if ( ! $should_load && function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			$should_load = true;
		}

		if ( ! $should_load && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$should_load = true;
		}

		// wc-api endpoints (e.g., PayPal Express callbacks).
		if ( ! $should_load && isset( $_GET['wc-api'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$should_load = true;
		}

		if ( ! $should_load && function_exists( 'is_cart' ) && function_exists( 'is_checkout' ) && function_exists( 'is_account_page' ) ) {
			if ( is_cart() || is_checkout() || is_account_page() ) {
				$should_load = true;
			}
		}

		/**
		 * Filter whether to load payment integration on this request.
		 *
		 * @param bool $should_load Whether to load integrations.
		 */
		return (bool) apply_filters( 'wps_sfw_should_load_payment_integration', $should_load );
	}

	/**
	 * Load payment gateway integrations when needed (once per request).
	 *
	 * @return void
	 */
	public function wps_sfw_maybe_init_payment_integration() {
		if ( $this->wps_sfw_payment_integration_loaded ) {
			return;
		}

		if ( ! $this->wps_sfw_should_load_payment_integration() ) {
			return;
		}

		$this->wps_sfw_payment_integration_loaded = true;
		$this->wps_sfw_init_payment_integration();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Subscriptions_For_Woocommerce_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function subscriptions_for_woocommerce_locale() {

		$plugin_i18n = new Subscriptions_For_Woocommerce_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function subscriptions_for_woocommerce_admin_hooks() {

		$sfw_plugin_admin = new Subscriptions_For_Woocommerce_Admin( $this->sfw_get_plugin_name(), $this->sfw_get_version() );
		$sfw_talk_to_expert = Subscriptions_For_Woocommerce_Talk_To_Expert_Form::get_instance();

		$this->loader->add_action( 'admin_enqueue_scripts', $sfw_plugin_admin, 'wps_sfw_admin_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $sfw_plugin_admin, 'wps_sfw_admin_enqueue_scripts' );
		$this->loader->add_action( 'admin_init', $this, 'wps_sfw_register_pro_report_ajax_fallback', 1 );
		$this->loader->add_action( 'wp_ajax_' . Subscriptions_For_Woocommerce_Talk_To_Expert_Form::AJAX_ACTION, $sfw_talk_to_expert, 'submit_form_ajax' );

		// Add settings menu for Subscriptions For Woocommerce.
		$this->loader->add_action( 'admin_menu', $sfw_plugin_admin, 'wps_sfw_options_page' );
		$this->loader->add_action( 'admin_menu', $sfw_plugin_admin, 'wps_sfw_remove_default_submenu', 50 );

		// All admin actions and filters after License Validation goes here.
		$this->loader->add_filter( 'wps_add_plugins_menus_array', $sfw_plugin_admin, 'wps_sfw_admin_submenu_page', 15 );

		$this->loader->add_filter( 'wps_sfw_general_settings_array', $sfw_plugin_admin, 'wps_sfw_admin_general_settings_page', 10 );

		// Saving tab settings.
		$this->loader->add_action( 'admin_init', $sfw_plugin_admin, 'sfw_admin_save_tab_settings' );
		// Multistep.
		$this->loader->add_action( 'wp_ajax_wps_sfw_save_settings_filter', $sfw_plugin_admin, 'wps_sfw_save_settings_filter' );
		$this->loader->add_action( 'wp_ajax_nopriv_wps_sfw_save_settings_filter', $sfw_plugin_admin, 'wps_sfw_save_settings_filter' );

		$this->loader->add_action( 'wp_ajax_wps_sfw_install_plugin_configuration', $sfw_plugin_admin, 'wps_sfw_install_plugin_configuration' );
		$this->loader->add_action( 'wp_ajax_nopriv_wps_sfw_install_plugin_configuration', $sfw_plugin_admin, 'wps_sfw_install_plugin_configuration' );
		// Developer's Hook Listing.
		$this->loader->add_action( 'sfw_developer_admin_hooks_array', $sfw_plugin_admin, 'wps_developer_admin_hooks_listing' );
		$this->loader->add_action( 'sfw_developer_public_hooks_array', $sfw_plugin_admin, 'wps_developer_public_hooks_listing' );

		$this->loader->add_filter( 'wps_sfw_api_settings_array', $sfw_plugin_admin, 'wps_sfw_admin_api_settings_fields', 10 );

		// subscritpion box listing.
		$this->loader->add_filter( 'wps_sfw_subscription_box_settings_array', $sfw_plugin_admin, 'wps_sfw_subscription_box_settings_fields', 10 );

		$this->loader->add_filter( 'woocommerce_product_data_tabs', $sfw_plugin_admin, 'wps_sfw_subscription_box_product_data_tabs', 10, 1 );

		if ( wps_sfw_check_plugin_enable() ) {
			$this->loader->add_action( 'product_type_options', $sfw_plugin_admin, 'wps_sfw_create_subscription_product_type' );

			$this->loader->add_filter( 'woocommerce_product_data_tabs', $sfw_plugin_admin, 'wps_sfw_custom_product_tab_for_subscription' );

			$this->loader->add_action( 'woocommerce_product_data_panels', $sfw_plugin_admin, 'wps_sfw_custom_product_fields_for_subscription' );

			$this->loader->add_action( 'woocommerce_process_product_meta', $sfw_plugin_admin, 'wps_sfw_save_custom_product_fields_data_for_subscription', 10, 2 );

			$this->loader->add_action( 'admin_init', $sfw_plugin_admin, 'wps_sfw_admin_cancel_susbcription', 99 );
			$this->loader->add_action( 'admin_init', $sfw_plugin_admin, 'wps_sfw_admin_reactivate_onhold_susbcription', 99 );

			// WPLM Translation.
			$this->loader->add_filter( 'wcml_js_lock_fields_ids', $sfw_plugin_admin, 'wps_sfw_add_lock_custom_fields_ids' );

			// paypal Keys Validation.
			$this->loader->add_filter( 'wp_ajax_wps_sfw_paypal_keys_validation', $sfw_plugin_admin, 'wps_sfw_paypal_keys_validation_callack' );

			// subscription box working.
			$this->loader->add_filter( 'product_type_selector', $sfw_plugin_admin, 'wsp_register_subscription_box_product_type', 10, 1 );
			$this->loader->add_filter( 'woocommerce_product_data_tabs', $sfw_plugin_admin, 'wps_sfw_custom_product_tab_for_subscription_box' );
			$this->loader->add_action( 'woocommerce_product_data_panels', $sfw_plugin_admin, 'wps_sfw_custom_product_fields_for_subscription_box' );
			$this->loader->add_action( 'woocommerce_process_product_meta', $sfw_plugin_admin, 'wps_sfw_save_subscription_box_data_for_subscription', 999, 2 );


			$this->loader->add_filter( 'manage_edit-shop_order_columns', $sfw_plugin_admin , 'wps_sfw_add_contains_subscription_column' );
			$this->loader->add_filter( 'woocommerce_shop_order_list_table_columns', $sfw_plugin_admin, 'wps_sfw_add_contains_subscription_column' );
			$this->loader->add_action( 'manage_shop_order_posts_custom_column', $sfw_plugin_admin, 'wps_sfw_add_contains_subscription_column_content', 10, 2 );
			$this->loader->add_action( 'woocommerce_shop_order_list_table_custom_column', $sfw_plugin_admin, 'wps_sfw_add_contains_subscription_column_content', 10, 2 );
		}

		/*cron for notification*/
		$this->loader->add_action( 'admin_init', $sfw_plugin_admin, 'wps_sfw_set_cron_for_plugin_notification' );
		$this->loader->add_action( 'wps_wgm_check_for_notification_update', $sfw_plugin_admin, 'wps_sfw_save_notice_message' );
		$this->loader->add_action( 'wp_ajax_wps_sfw_dismiss_notice_banner', $sfw_plugin_admin, 'wps_sfw_dismiss_notice_banner_callback' );

		$this->loader->add_action( 'admin_menu', $sfw_plugin_admin, 'wps_sfw_remove_subscription_custom_menu' );

		// Add 'Upsell Support' column on payment gateways page.
		$this->loader->add_filter( 'woocommerce_payment_gateways_setting_columns', $sfw_plugin_admin, 'wps_sfw_subscription_support_in_payment_gateway' );
		// 'Upsell Support' content on payment gateways page.
		$this->loader->add_action( 'woocommerce_payment_gateways_setting_column_wps_sub_renewal', $sfw_plugin_admin, 'wps_sfw_subscription_content_in_payment_gateway' );
	}

	/**
	 * Register fallback AJAX handlers for the Pro report screen.
	 *
	 * The Pro plugin currently gates these handlers behind the core "plugin enabled" flag,
	 * but the report tab can still render when that flag is off. In that state the React
	 * report app sends valid admin-ajax requests to unregistered actions and WordPress
	 * returns HTTP 400. This fallback keeps the report endpoints available without
	 * duplicating hooks when Pro already registered them.
	 *
	 * @return void
	 */
	public function wps_sfw_register_pro_report_ajax_fallback() {
		if ( ! apply_filters( 'wsp_sfw_check_pro_plugin', false ) ) {
			return;
		}

		if ( ! class_exists( 'Woocommerce_Subscriptions_Pro_Admin' ) ) {
			return;
		}

		$report_hooks = array(
			'wps_wsp_chart_data'                 => 'wps_sfw_report_chart_data_callback',
			'wps_wsp_chart_product_data'         => 'wps_sfw_report_chart_product_data_callback',
			'wps_wsp_chart_total_renewal'        => 'wps_sfw_report_chart_total_renewal_callback',
			'wps_wsp_get_cancelled_subscription' => 'wps_sfw_report_get_cancelled_subscription_callback',
			'wps_wsp_get_renewed_subscription'   => 'wps_sfw_report_get_renewed_subscription_callback',
			'wps_wsp_get_mrr_data'               => 'wps_sfw_report_get_mrr_data_callback',
			'wps_wsp_get_grid_data'              => 'wps_sfw_report_get_grid_data_callback',
			'wps_wsp_get_churn_arr_data'         => 'wps_sfw_report_get_churn_arr_data_callback',
		);

		$pro_version = defined( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_VERSION' ) ? WOOCOMMERCE_SUBSCRIPTIONS_PRO_VERSION : $this->sfw_get_version();
		$pro_admin   = new Woocommerce_Subscriptions_Pro_Admin( 'woocommerce-subscriptions-pro', $pro_version );

		foreach ( $report_hooks as $hook_name => $method ) {
			if ( ! has_action( 'wp_ajax_' . $hook_name ) ) {
				add_action( 'wp_ajax_' . $hook_name, array( $this, $method ) );
			}
		}

		if ( ! has_action( 'wp_ajax_wps_wsp_generate_csv' ) ) {
			add_action( 'wp_ajax_wps_wsp_generate_csv', array( $pro_admin, 'wps_wsp_export_csv_report_callback' ) );
		}
	}

	/**
	 * Read and normalize report date range from AJAX payload.
	 *
	 * @return array{0:string,1:string}
	 */
	private function wps_sfw_get_report_date_range() {
		$start_date = isset( $_POST['startDate'] ) ? sanitize_text_field( wp_unslash( $_POST['startDate'] ) ) : '';
		$end_date   = isset( $_POST['endDate'] ) ? sanitize_text_field( wp_unslash( $_POST['endDate'] ) ) : '';

		return array( $start_date, $end_date );
	}

	/**
	 * Fetch subscription order IDs for the report date range.
	 *
	 * @param array $extra_args Extra wc_get_orders arguments.
	 * @return array
	 */
	private function wps_sfw_get_report_subscription_ids( $extra_args = array() ) {
		list( $start_date, $end_date ) = $this->wps_sfw_get_report_date_range();

		$args = wp_parse_args(
			(array) $extra_args,
			array(
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'post_type'    => 'wps_subscriptions',
				'return'       => 'ids',
			)
		);

		return wc_get_orders( $args );
	}

	/**
	 * Report callback: total subscription sales.
	 *
	 * @return void
	 */
	public function wps_sfw_report_chart_data_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$orders               = $this->wps_sfw_get_report_subscription_ids();
		$temp_order_date      = array();
		$temp_no_order        = array();
		$temp_order_revenue   = array();
		$display_data         = array();

		if ( ! empty( $orders ) ) {
			$orders = array_reverse( $orders );

			foreach ( $orders as $order_id ) {
				$subscription = new WPS_Subscription( $order_id );
				$created      = $subscription->get_date_created();

				if ( ! $created ) {
					continue;
				}

				$formatted_date = $created->format( wc_date_format() );
				$total          = (float) $subscription->get_total();
				$parent_id      = $subscription->get_meta( 'wps_parent_order' );
				$status         = $subscription->get_meta( 'wps_subscription_status' );
				$renewals       = $subscription->get_meta( 'wps_wsp_renewal_order_data' );
				$last_renewal   = esc_attr__( 'No Renewal', 'subscriptions-for-woocommerce' );

				if ( is_array( $renewals ) && ! empty( $renewals ) ) {
					$last_renewal = end( $renewals );
				}

				if ( ! in_array( $formatted_date, $temp_order_date, true ) ) {
					$temp_order_date[] = $formatted_date;
				}

				$temp_no_order[ $formatted_date ]      = isset( $temp_no_order[ $formatted_date ] ) ? $temp_no_order[ $formatted_date ] + 1 : 1;
				$temp_order_revenue[ $formatted_date ] = isset( $temp_order_revenue[ $formatted_date ] ) ? $temp_order_revenue[ $formatted_date ] + $total : $total;

				$display_data[] = array(
					'id'              => $order_id,
					'parent_id'       => $parent_id,
					'status'          => $status,
					'last_renewal_id' => $last_renewal,
					'date'            => $formatted_date,
				);
			}
		}

		wp_send_json(
			array(
				'dates'                => array_values( $temp_order_date ),
				'newSubscriptions'     => array_values( $temp_no_order ),
				'subscriptionsRevenue' => array_values( $temp_order_revenue ),
				'displayData'          => $display_data,
			)
		);
	}

	/**
	 * Report callback: top products.
	 *
	 * @return void
	 */
	public function wps_sfw_report_chart_product_data_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$orders       = $this->wps_sfw_get_report_subscription_ids();
		$product_ids  = array();
		$result       = array();
		$display_data = array();

		if ( ! empty( $orders ) ) {
			$orders = array_reverse( $orders );

			foreach ( $orders as $order_id ) {
				$subscription = new WPS_Subscription( $order_id );
				$product_id   = absint( $subscription->get_meta( 'product_id' ) );

				if ( ! $product_id ) {
					continue;
				}

				$product_ids[ $product_id ] = isset( $product_ids[ $product_id ] ) ? $product_ids[ $product_id ] + 1 : 1;
			}
		}

		if ( ! empty( $product_ids ) ) {
			$total_count    = array_sum( $product_ids );
			$others_percent = 0;

			arsort( $product_ids );

			$top_products   = array_slice( $product_ids, 0, 4, true );
			$other_products = array_slice( $product_ids, 4, null, true );

			foreach ( $top_products as $product_id => $count ) {
				$product = get_post( $product_id );
				if ( ! $product ) {
					continue;
				}

				$result[] = array(
					'value' => round( ( $count / $total_count ) * 100, 2 ),
					'name'  => 'ID : ' . $product->ID . ' | ' . $product->post_title,
				);
			}

			foreach ( $other_products as $count ) {
				$others_percent += ( $count / $total_count ) * 100;
			}

			if ( ! empty( $other_products ) ) {
				$result[] = array(
					'value' => round( $others_percent, 2 ),
					'name'  => 'others',
				);
			}

			foreach ( $product_ids as $product_id => $count ) {
				$product = wc_get_product( $product_id );
				if ( ! $product ) {
					continue;
				}

				$display_data[] = array(
					'id'    => $product_id,
					'name'  => $product->get_name(),
					'type'  => $product->get_type(),
					'count' => $count,
				);
			}
		}

		wp_send_json(
			array(
				'displayData' => $display_data,
				'result1'     => $result,
			)
		);
	}

	/**
	 * Report callback: renewal totals.
	 *
	 * @return void
	 */
	public function wps_sfw_report_chart_total_renewal_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		list( $start_date, $end_date ) = $this->wps_sfw_get_report_date_range();

		$orders = wc_get_orders(
			array(
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'meta_key'     => 'wps_sfw_renewal_order',
				'meta_value'   => 'yes',
				'return'       => 'ids',
			)
		);

		$array_data   = array();
		$display_data = array();

		if ( ! empty( $orders ) ) {
			$orders = array_reverse( $orders );

			foreach ( $orders as $id ) {
				$renewal_order = wc_get_order( $id );
				if ( ! $renewal_order ) {
					continue;
				}

				$subscription_id = $renewal_order->get_meta( 'wps_sfw_subscription' );
				$parent_id       = $renewal_order->get_meta( 'wps_sfw_parent_order_id' );
				$status          = $renewal_order->get_status();
				$product_id      = function_exists( 'wps_wsp_get_meta_data' ) ? wps_wsp_get_meta_data( $subscription_id, 'product_id', true ) : '';
				$created_date    = $renewal_order->get_date_created();
				$date            = $created_date ? $created_date->date( wc_date_format() ) : '';
				$total           = (float) $renewal_order->get_total();

				if ( '' === $date ) {
					continue;
				}

				$array_data[ $date ]['ids'][]             = $id;
				$array_data[ $date ]['parent_id'][]       = $parent_id;
				$array_data[ $date ]['subcription_id'][]  = $subscription_id;
				$array_data[ $date ]['status'][]          = $status;
				$array_data[ $date ]['product_id'][]      = $product_id;
				$array_data[ $date ]['total'][]           = $total;

				$display_data[] = array(
					'id'              => $id,
					'subscription_id' => $subscription_id,
					'status'          => $status,
					'date'            => $date,
				);
			}
		}

		wp_send_json(
			array(
				'result2'     => $array_data,
				'displayData' => $display_data,
			)
		);
	}

	/**
	 * Report callback: cancelled subscriptions.
	 *
	 * @return void
	 */
	public function wps_sfw_report_get_cancelled_subscription_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$orders = $this->wps_sfw_get_report_subscription_ids(
			array(
				'meta_key'   => 'wps_subscription_status',
				'meta_value' => 'cancelled',
			)
		);

		$cancelled_subs = array();
		$display_data   = array();

		if ( ! empty( $orders ) ) {
			foreach ( $orders as $subscription_id ) {
				$subscription = new WPS_Subscription( $subscription_id );
				$created_date = $subscription->get_date_created();
				$date         = $created_date ? $created_date->date( wc_date_format() ) : '';

				if ( '' === $date ) {
					continue;
				}

				$reason         = $subscription->get_meta( 'wps_subscription_cancelled_by' );
				$cancelled_date = $subscription->get_meta( 'wps_subscription_cancelled_date' );

				$cancelled_subs[ $date ]['id'][]     = $subscription_id;
				$cancelled_subs[ $date ]['reason'][] = $reason;

				$display_data[] = array(
					'id'             => $subscription_id,
					'reason'         => $reason,
					'status'         => 'cancelled',
					'date'           => $date,
					'cancelled_date' => $cancelled_date ? date_i18n( wc_date_format(), $cancelled_date ) : null,
				);
			}
		}

		wp_send_json(
			array(
				'result3'     => $cancelled_subs,
				'displayData' => $display_data,
			)
		);
	}

	/**
	 * Report callback: renewed subscriptions.
	 *
	 * @return void
	 */
	public function wps_sfw_report_get_renewed_subscription_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$orders = $this->wps_sfw_get_report_subscription_ids(
			array(
				'meta_query' => array(
					array(
						'key'     => 'wps_wsp_renewal_order_data',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

		$subscription_data = array();
		$display_data      = array();

		if ( ! empty( $orders ) ) {
			$orders = array_reverse( $orders );

			foreach ( $orders as $subscription_id ) {
				$subscription = new WPS_Subscription( $subscription_id );
				$created      = $subscription->get_date_created();
				$date         = $created ? $created->format( wc_date_format() ) : '';

				if ( '' === $date ) {
					continue;
				}

				$parent_id  = $subscription->get_meta( 'wps_parent_order' );
				$status     = $subscription->get_meta( 'wps_subscription_status' );
				$product_id = $subscription->get_meta( 'product_id' );
				$total      = (float) $subscription->get_total();

				$subscription_data[ $date ]['id'][]         = $subscription_id;
				$subscription_data[ $date ]['parent_id'][]  = $parent_id;
				$subscription_data[ $date ]['status'][]     = $status;
				$subscription_data[ $date ]['product_id'][] = $product_id;
				$subscription_data[ $date ]['total'][]      = $total;

				$display_data[] = array(
					'id'           => $subscription_id,
					'status'       => $status,
					'see_renewals' => $subscription_id,
					'date'         => $date,
				);
			}
		}

		wp_send_json(
			array(
				'result4'     => $subscription_data,
				'displayData' => $display_data,
			)
		);
	}

	/**
	 * Report callback: MRR data.
	 *
	 * @return void
	 */
	public function wps_sfw_report_get_mrr_data_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		list( $start_date, $end_date ) = $this->wps_sfw_get_report_date_range();

		$orders = wc_get_orders(
			array(
				'limit'        => -1,
				'date_created' => $start_date . '...' . $end_date,
				'return'       => 'ids',
				'meta_key'     => 'wps_sfw_renewal_order',
				'meta_value'   => 'yes',
				'status'       => array( 'wc-processing', 'wc-completed' ),
			)
		);

		$subscription_data = array();
		$display_data      = array();

		if ( ! empty( $orders ) ) {
			$orders = array_reverse( $orders );

			foreach ( $orders as $renewal_id ) {
				$renewal = wc_get_order( $renewal_id );
				if ( ! $renewal ) {
					continue;
				}

				$created = $renewal->get_date_created();
				if ( ! $created ) {
					continue;
				}

				$formatted_date = $created->format( 'F, y' );
				$total          = (float) $renewal->get_total();
				$subscription_id = $renewal->get_meta( 'wps_sfw_subscription' );

				$subscription_data[ $formatted_date ]['ids'][]   = $renewal_id;
				$subscription_data[ $formatted_date ]['total'][] = $total;

				$display_data[] = array(
					'id'              => $renewal_id,
					'subscription_id' => $subscription_id,
					'total'           => $total,
					'date'            => $formatted_date,
				);
			}
		}

		wp_send_json(
			array(
				'result5'     => $subscription_data,
				'displayData' => $display_data,
			)
		);
	}

	/**
	 * Report callback: grid counts.
	 *
	 * @return void
	 */
	public function wps_sfw_report_get_grid_data_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$response_data = array(
			1 => 0,
			2 => 0,
			3 => 0,
			4 => 0,
			5 => 0,
			6 => 0,
		);

		$subscriptions = $this->wps_sfw_get_report_subscription_ids();
		if ( ! empty( $subscriptions ) ) {
			$response_data[1] = count( $subscriptions );

			$product_ids = array();
			foreach ( $subscriptions as $order_id ) {
				$subscription = new WPS_Subscription( $order_id );
				$product_id   = absint( $subscription->get_meta( 'product_id' ) );

				if ( ! $product_id ) {
					continue;
				}

				$product_ids[ $product_id ] = true;
			}
			$response_data[2] = count( $product_ids );
		}

		list( $start_date, $end_date ) = $this->wps_sfw_get_report_date_range();

		$renewals = wc_get_orders(
			array(
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'meta_key'     => 'wps_sfw_renewal_order',
				'meta_value'   => 'yes',
				'return'       => 'ids',
			)
		);
		$response_data[3] = ! empty( $renewals ) ? count( $renewals ) : 0;

		$cancelled = $this->wps_sfw_get_report_subscription_ids(
			array(
				'meta_key'   => 'wps_subscription_status',
				'meta_value' => 'cancelled',
			)
		);
		$response_data[4] = ! empty( $cancelled ) ? count( $cancelled ) : 0;

		$renewed = $this->wps_sfw_get_report_subscription_ids(
			array(
				'meta_query' => array(
					array(
						'key'     => 'wps_wsp_renewal_order_data',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);
		$response_data[5] = ! empty( $renewed ) ? count( $renewed ) : 0;

		if ( ! empty( $renewals ) ) {
			$total = 0;
			foreach ( $renewals as $renewal_id ) {
				$renewal = wc_get_order( $renewal_id );
				if ( $renewal ) {
					$total += (float) $renewal->get_total();
				}
			}
			$response_data[6] = $total;
		}

		wp_send_json( $response_data );
	}

	/**
	 * Report callback: churn rate and ARR.
	 *
	 * @return void
	 */
	public function wps_sfw_report_get_churn_arr_data_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$current_year = gmdate( 'Y' );
		$start_date   = $current_year . '-01-01';
		$end_date     = gmdate( 'Y-m-d' );
		$total        = 0;
		$sub_count    = 0;
		$cancel_count = 0;

		$renewals = wc_get_orders(
			array(
				'limit'        => -1,
				'date_created' => $start_date . '...' . $end_date,
				'return'       => 'ids',
				'meta_key'     => 'wps_sfw_renewal_order',
				'meta_value'   => 'yes',
				'status'       => array( 'wc-processing', 'wc-completed' ),
			)
		);

		if ( ! empty( $renewals ) ) {
			foreach ( $renewals as $renewal_id ) {
				$renewal = wc_get_order( $renewal_id );
				if ( $renewal ) {
					$total += (float) $renewal->get_total();
				}
			}
		}

		$subscriptions = wc_get_orders(
			array(
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'post_type'    => 'wps_subscriptions',
				'return'       => 'ids',
			)
		);

		if ( ! empty( $subscriptions ) ) {
			foreach ( $subscriptions as $subscription_id ) {
				$subscription = new WPS_Subscription( $subscription_id );
				if ( 'cancelled' === $subscription->get_meta( 'wps_subscription_status' ) ) {
					$cancel_count++;
				}
				$sub_count++;
			}
		}

		$churn_rate = $sub_count > 0 ? round( ( $cancel_count / $sub_count ) * 100, 2 ) : 0;

		wp_send_json_success(
			array(
				'churnRate' => $churn_rate,
				'arr'       => $total,
			)
		);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function subscriptions_for_woocommerce_public_hooks() {

		$sfw_plugin_public = new Subscriptions_For_Woocommerce_Public( $this->sfw_get_plugin_name(), $this->sfw_get_version() );

		if ( wps_sfw_check_plugin_enable() ) {
			$this->loader->add_action( 'wp_enqueue_scripts', $sfw_plugin_public, 'wps_sfw_public_enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $sfw_plugin_public, 'wps_sfw_public_enqueue_scripts' );

			$this->loader->add_filter( 'woocommerce_get_price_html', $sfw_plugin_public, 'wps_sfw_price_html_subscription_product', 10, 2 );
			$this->loader->add_filter( 'woocommerce_product_single_add_to_cart_text', $sfw_plugin_public, 'wps_sfw_product_add_to_cart_text', 10, 2 );
			$this->loader->add_filter( 'woocommerce_product_add_to_cart_text', $sfw_plugin_public, 'wps_sfw_product_add_to_cart_text', 10, 2 );
			$this->loader->add_filter( 'woocommerce_order_button_text', $sfw_plugin_public, 'wps_sfw_woocommerce_order_button_text' );

			$this->loader->add_filter( 'woocommerce_cart_item_price', $sfw_plugin_public, 'wps_sfw_show_subscription_price_on_cart', 99, 3 );

			$this->loader->add_action( 'woocommerce_before_calculate_totals', $sfw_plugin_public, 'wps_sfw_add_subscription_price', 999 );
			$this->loader->add_action( 'woocommerce_cart_calculate_fees', $sfw_plugin_public, 'wps_sfw_add_subscription_signup_fee', 999 );

			$this->loader->add_action( 'woocommerce_checkout_order_processed', $sfw_plugin_public, 'wps_sfw_process_checkout', 999, 2 );

			$this->loader->add_action( 'woocommerce_available_payment_gateways', $sfw_plugin_public, 'wps_sfw_unset_offline_payment_gateway_for_subscription' );

			$this->loader->add_action( 'init', $sfw_plugin_public, 'wps_sfw_add_subscription_tab_on_myaccount_page' );

			$this->loader->add_filter( 'query_vars', $sfw_plugin_public, 'wps_sfw_custom_endpoint_query_vars' );
			$this->loader->add_filter( 'woocommerce_account_menu_items', $sfw_plugin_public, 'wps_sfw_add_subscription_dashboard_on_myaccount_page' );

			$this->loader->add_action( 'woocommerce_account_wps_subscriptions_endpoint', $sfw_plugin_public, 'wps_sfw_subscription_dashboard_content' );

			$this->loader->add_action( 'woocommerce_before_checkout_form', $sfw_plugin_public, 'wps_sfw_subscription_before_checkout_form' );

			$this->loader->add_action( 'wps_sfw_display_susbcription_recerring_total_account_page', $sfw_plugin_public, 'wps_sfw_display_susbcription_recerring_total_account_page_callback' );

			$this->loader->add_action( 'woocommerce_account_show-subscription_endpoint', $sfw_plugin_public, 'wps_sfw_shwo_subscription_details' );

			$this->loader->add_action( 'init', $sfw_plugin_public, 'wps_sfw_cancel_susbcription', 99 );

			$this->loader->add_action( 'woocommerce_order_status_changed', $sfw_plugin_public, 'wps_sfw_woocommerce_order_status_changed', 99, 3 );

			$this->loader->add_action( 'after_woocommerce_pay', $sfw_plugin_public, 'wps_sfw_after_woocommerce_pay', 100 );

			$this->loader->add_action( 'wp_loaded', $sfw_plugin_public, 'wps_sfw_change_payment_method_form', 20 );

			$this->loader->add_filter( 'woocommerce_order_get_total', $sfw_plugin_public, 'wps_sfw_set_susbcription_total', 11, 2 );
			$this->loader->add_filter( 'woocommerce_is_sold_individually', $sfw_plugin_public, 'wps_sfw_hide_quantity_fields_for_subscription', 10, 2 );

			$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $sfw_plugin_public, 'wps_sfw_woocommerce_add_to_cart_validation', 10, 5 );

			$this->loader->add_filter( 'woocommerce_cart_needs_payment', $sfw_plugin_public, 'wps_sfw_woocommerce_cart_needs_payment', 99, 2 );

			$this->loader->add_action( 'woocommerce_order_status_changed', $sfw_plugin_public, 'wps_sfw__cancel_subs_woocommerce_order_status_changed', 150, 3 );

			$this->loader->add_filter( 'woocommerce_checkout_registration_required', $sfw_plugin_public, 'wps_sfw_registration_required', 900 );

			$this->loader->add_action( 'woocommerce_review_order_after_order_total', $sfw_plugin_public, 'wps_sfw_show_recurring_information', 10, 1 );

			// WC block.
			$this->loader->add_action( 'template_redirect', $sfw_plugin_public, 'wps_sfw_to_cart_and_checkout_blocks' );
			$this->loader->add_filter( 'woocommerce_get_item_data', $sfw_plugin_public, 'wps_sfw_get_subscription_meta_on_cart', 10, 2 );
			$this->loader->add_action( 'woocommerce_store_api_checkout_order_processed', $sfw_plugin_public, 'wps_sfw_process_checkout_hpos', 100 );
			$this->loader->add_action( 'woocommerce_blocks_loaded', $sfw_plugin_public, 'wsp_sfw_wps_paypal_woocommerce_block_support' );

			$this->loader->add_action( 'wps_sfw_subscription_cancel', $sfw_plugin_public, 'wps_sfw_cancel_manual_subscription', 10, 2 );

			// Change the subject, heading and content for the failed renewal order.
			$this->loader->add_action( 'woocommerce_email_before_order_table', $sfw_plugin_public, 'wps_sfw_add_custom_failed_order_section', 10, 4 );
			$this->loader->add_filter( 'woocommerce_email_subject_failed_order', $sfw_plugin_public, 'wps_sfw_custom_woocommerce_email_subject_failed_order', 10, 2 );
			$this->loader->add_filter( 'woocommerce_email_heading_failed_order', $sfw_plugin_public, 'wps_sfw_custom_woocommerce_email_heading_failed_order', 10, 2 );

			// Learnpress Compatibility.
			$this->loader->add_action( 'woocommerce_single_product_summary', $sfw_plugin_public, 'wps_sfw_course_description', 20 );
			$this->loader->add_filter( 'learnpress/course/item/can-view', $sfw_plugin_public, 'wps_sfw_course_can_view', 10, 3 );

			// Manage the zero checkout for the stripe .
			$this->loader->add_filter( 'woocommerce_order_needs_payment', $sfw_plugin_public, 'wps_sfw_woocommerce_order_needs_payment', 10, 3 );

			// subscription box.
			$this->loader->add_action( 'woocommerce_single_product_summary', $sfw_plugin_public, 'wps_sfw_subscription_box_info_above_add_to_cart', 20 );
			$this->loader->add_action( 'woocommerce_subscription_box_add_to_cart', $sfw_plugin_public, 'wps_sfw_subscription_box_create_button', 20 );
			$this->loader->add_action( 'wps_sfw_subscription_subscription_box_addtion', $sfw_plugin_public, 'wps_sfw_subscription_subscription_box_addtion_callback', 10, 3 );
			$this->loader->add_action( 'wp_ajax_wps_sfw_handle_subscription_box', $sfw_plugin_public, 'wps_sfw_handle_subscription_box' );
			$this->loader->add_action( 'wp_ajax_nopriv_wps_sfw_handle_subscription_box', $sfw_plugin_public, 'wps_sfw_handle_subscription_box' );
			$this->loader->add_action( 'woocommerce_before_calculate_totals', $sfw_plugin_public, 'wps_sfw_update_subscription_box_prices', 99 );
			$this->loader->add_filter( 'woocommerce_get_item_data', $sfw_plugin_public, 'wps_subscription_box_meta_on_cart', 10, 2 );
			$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $sfw_plugin_public, 'wps_sfw_add_order_line_item_for_subscription_box', 10, 4 );
			$this->loader->add_action( 'wp_ajax_wps_get_cart_item', $sfw_plugin_public, 'wps_get_cart_item' );
			$this->loader->add_action( 'wp_ajax_nopriv_wps_get_cart_item', $sfw_plugin_public, 'wps_get_cart_item' );
			$this->loader->add_filter( 'woocommerce_get_item_data', $sfw_plugin_public, 'wps_sfw_add_item_data_cart_block_subscription_box', 10, 2 );
			$this->loader->add_filter( 'woocommerce_cart_item_name', $sfw_plugin_public, 'wps_sfw_show_attached_product_html_subscription_box', 10, 3 );
			$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $sfw_plugin_public, 'wps_sfw_subscription_box_woocommerce_add_to_cart_validation', 10, 5 );
			$this->loader->add_filter( 'woocommerce_is_sold_individually', $sfw_plugin_public, 'wps_sfw_hide_quantity_fields_for_subscription_box', 10, 2 );

			$this->loader->add_filter( 'woocommerce_email_preview_dummy_order', $sfw_plugin_public, 'wps_sfw_woocommerce_email_preview_dummy_order_callback', 10, 2 );

			$this->loader->add_filter( 'body_class', $sfw_plugin_public, 'wps_sfw_subscription_custom_add_body_class', 10, 1 );

			$this->loader->add_filter( 'woocommerce_register_shop_order_post_statuses', $sfw_plugin_public, 'wps_sfw_register_new_order_statuses' );
			$this->loader->add_filter( 'wc_order_statuses', $sfw_plugin_public, 'wps_sfw_new_wc_order_statuses' );
			$this->loader->add_action( 'plugins_loaded', $sfw_plugin_public, 'wps_sfw_subscription_dashboard_shortcodes' );

			$this->loader->add_action( 'wp_ajax_wps_sfw_sub_box_empty_cart', $sfw_plugin_public, 'wps_sfw_sub_box_empty_cart_callback' );
			$this->loader->add_action( 'wp_ajax_nopriv_wps_sfw_sub_box_empty_cart', $sfw_plugin_public, 'wps_sfw_sub_box_empty_cart_callback' );
			$this->loader->add_action( 'woocommerce_order_status_changed', $sfw_plugin_public, 'wps_sfw_woocommerce_affiliate_commision_renewal', 99, 3 );

			// subscription box.

		}
	}

	/**
	 * The function include email class.
	 *
	 * @name wps_sfw_woocommerce_email_classes.
	 * @since 1.0.0
	 * @param Array $emails emails.
	 */
	public function wps_sfw_woocommerce_email_classes( $emails ) {
		$emails['wps_sfw_cancel_subscription'] = require_once plugin_dir_path( __DIR__ ) . 'emails/class-subscriptions-for-woocommerce-cancel-subscription-email.php';
		$emails['wps_sfw_expired_subscription'] = require_once plugin_dir_path( __DIR__ ) . 'emails/class-subscriptions-for-woocommerce-expired-subscription-email.php';
		$emails['wps_sfw_onhold_active_subscription'] = require_once plugin_dir_path( __DIR__ ) . 'emails/class-subscriptions-for-woocommerce-onhold-active-subscription-email.php';

		return apply_filters( 'wps_sfw_email_classes', $emails );
	}
	/**
	 * Register all of the hooks related to the api functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function subscriptions_for_woocommerce_api_hooks() {

		$sfw_plugin_api = new Subscriptions_For_Woocommerce_Rest_Api( $this->sfw_get_plugin_name(), $this->sfw_get_version() );

		$this->loader->add_action( 'rest_api_init', $sfw_plugin_api, 'wps_sfw_add_endpoint' );
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function sfw_run() {
		$this->loader->sfw_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function sfw_get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Subscriptions_For_Woocommerce_Loader    Orchestrates the hooks of the plugin.
	 */
	public function sfw_get_loader() {
		return $this->loader;
	}


	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Subscriptions_For_Woocommerce_Onboard    Orchestrates the hooks of the plugin.
	 */
	public function sfw_get_onboard() {
		return $this->sfw_onboard;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function sfw_get_version() {
		return $this->version;
	}

	/**
	 * Predefined default wps_sfw_plug tabs.
	 *
	 * @return  Array       An key=>value pair of Subscriptions For Woocommerce tabs.
	 */
	public function wps_sfw_plug_default_tabs() {

		$sfw_default_tabs = array();

		$sfw_default_tabs['subscriptions-for-woocommerce-overview'] = array(
			'title'       => esc_html__( 'Overview', 'subscriptions-for-woocommerce' ),
			'name'        => 'subscriptions-for-woocommerce-overview',
			'file_path'        => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);
		$sfw_default_tabs['subscriptions-for-woocommerce-general'] = array(
			'title'       => esc_html__( 'General Settings', 'subscriptions-for-woocommerce' ),
			'name'        => 'subscriptions-for-woocommerce-general',
			'file_path'        => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);
		$sfw_default_tabs = apply_filters( 'wps_sfw_sfw_plugin_standard_admin_settings_tabs', $sfw_default_tabs );

		$sfw_default_tabs['subscriptions-for-woocommerce-subscriptions-table'] = array(
			'title'       => esc_html__( 'Subscription Table', 'subscriptions-for-woocommerce' ),
			'name'        => 'subscriptions-for-woocommerce-subscriptions-table',
			'file_path'        => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);
		$sfw_default_tabs['subscription-for-woocommerce-api'] = array(
			'title'       => esc_html__( 'API Settings', 'subscriptions-for-woocommerce' ),
			'name'        => 'subscription-for-woocommerce-api',
			'file_path'       => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);

		// Subscription box.
		$sfw_default_tabs['subscription-for-woocommerce-subscription-box'] = array(
			'title'       => esc_html__( 'Subscription Box', 'subscriptions-for-woocommerce' ),
			'name'        => 'subscription-for-woocommerce-subscription-box',
			'file_path'       => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);
		// Subscription box.

		if ( ! defined( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_VERSION' ) ) {
			$sfw_default_tabs['subscriptions-for-woocommerce-subscriptions-pro-features'] = array(
				'title'       => esc_html__( 'Advanced Settings', 'subscriptions-for-woocommerce' ),
				'name'        => 'subscriptions-for-woocommerce-subscriptions-pro-features',
				'file_path'        => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
			);
		}
		if ( function_exists( 'is_plugin_active' ) && ! is_plugin_active( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php' ) ) {
			$sfw_default_tabs['subscriptions-for-woocommerce-subscriptions-free-vs-pro'] = array(
				'title'       => esc_html__( 'Free Vs Pro', 'subscriptions-for-woocommerce' ),
				'name'        => 'subscriptions-for-woocommerce-subscriptions-free-vs-pro',
				'file_path'        => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
			);
		}
		$sfw_default_tabs = apply_filters( 'wps_sfw_sfw_plugin_standard_admin_settings_tabs_before', $sfw_default_tabs );
		$sfw_default_tabs['subscriptions-for-woocommerce-system-status'] = array(
			'title'       => esc_html__( 'System Status', 'subscriptions-for-woocommerce' ),
			'name'        => 'subscriptions-for-woocommerce-system-status',
			'file_path'        => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);
		$sfw_default_tabs['subscriptions-for-woocommerce-developer'] = array(
			'title'       => esc_html__( 'Developer', 'subscriptions-for-woocommerce' ),
			'name'        => 'subscriptions-for-woocommerce-developer',
			'file_path'   => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);
		$sfw_default_tabs = apply_filters( 'wps_sfw_sfw_plugin_standard_admin_settings_tabs_end', $sfw_default_tabs );

		return $sfw_default_tabs;
	}

	/**
	 * Locate and load appropriate tempate.
	 *
	 * @since   1.0.0
	 * @param string $content_path content_path file for inclusion.
	 */
	public function wps_sfw_plug_load_template( $content_path ) {

		if ( file_exists( $content_path ) ) {

			include $content_path;
		} else {

			/* translators: %s: file path */
			$sfw_notice = sprintf( esc_html__( 'Unable to locate file at location "%s". Some features may not work properly in this plugin. Please contact us!', 'subscriptions-for-woocommerce' ), $content_path );
			$this->wps_sfw_plug_admin_notice( $sfw_notice, 'error' );
		}
	}

	/**
	 * Show admin notices.
	 *
	 * @param  string $sfw_message    Message to display.
	 * @param  string $type       notice type, accepted values - error/update/update-nag.
	 * @since  1.0.0
	 */
	public static function wps_sfw_plug_admin_notice( $sfw_message, $type = 'error' ) {

		$sfw_classes = 'notice ';

		switch ( $type ) {

			case 'update':
				$sfw_classes .= 'updated is-dismissible';
				break;

			case 'update-nag':
				$sfw_classes .= 'update-nag is-dismissible';
				break;

			case 'success':
				$sfw_classes .= 'notice-success is-dismissible';
				break;

			default:
				$sfw_classes .= 'notice-error is-dismissible';
		}

		$sfw_notice  = '<div class="' . esc_attr( $sfw_classes ) . ' wps-errorr-8">';
		$sfw_notice .= '<p>' . esc_html( $sfw_message ) . '</p>';
		$sfw_notice .= '</div>';

		echo wp_kses_post( $sfw_notice );
	}


	/**
	 * Show wordpress and server info.
	 *
	 * @return  Array $sfw_system_data       returns array of all wordpress and server related information.
	 * @since  1.0.0
	 */
	public function wps_sfw_plug_system_status() {
		global $wpdb;

		$cache_ttl = (int) apply_filters( 'wps_sfw_system_status_cache_ttl', 5 * MINUTE_IN_SECONDS );
		if ( $cache_ttl > 0 ) {
			$cached = get_transient( 'wps_sfw_system_status' );
			if ( is_array( $cached ) && ! empty( $cached ) ) {
				return $cached;
			}
		}

		$sfw_system_status = array();
		$sfw_wordpress_status = array();
		$sfw_system_data = array();

		// Get the web server.
		$sfw_system_status['web_server'] = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';

		// Get PHP version.
		$sfw_system_status['php_version'] = function_exists( 'phpversion' ) ? phpversion() : __( 'N/A (phpversion function does not exist)', 'subscriptions-for-woocommerce' );

		// Get the server's IP address.
		$sfw_system_status['server_ip'] = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';

		// Get the server's port.
		$sfw_system_status['server_port'] = isset( $_SERVER['SERVER_PORT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PORT'] ) ) : '';

		// Get the uptime.
		$sfw_system_status['uptime'] = function_exists( 'exec' ) ? @exec( 'uptime -p' ) : __( 'N/A (make sure exec function is enabled)', 'subscriptions-for-woocommerce' );

		// Get the server path.
		$sfw_system_status['server_path'] = defined( 'ABSPATH' ) ? ABSPATH : __( 'N/A (ABSPATH constant not defined)', 'subscriptions-for-woocommerce' );

		// Get the OS.
		$sfw_system_status['os'] = function_exists( 'php_uname' ) ? php_uname( 's' ) : __( 'N/A (php_uname function does not exist)', 'subscriptions-for-woocommerce' );

		// Get WordPress version.
		$sfw_wordpress_status['wp_version'] = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : __( 'N/A (get_bloginfo function does not exist)', 'subscriptions-for-woocommerce' );

		// Get and count active WordPress plugins.
		$sfw_wordpress_status['wp_active_plugins'] = function_exists( 'get_option' ) ? count( get_option( 'active_plugins' ) ) : __( 'N/A (get_option function does not exist)', 'subscriptions-for-woocommerce' );

		// See if this site is multisite or not.
		$sfw_wordpress_status['wp_multisite'] = function_exists( 'is_multisite' ) && is_multisite() ? __( 'Yes', 'subscriptions-for-woocommerce' ) : __( 'No', 'subscriptions-for-woocommerce' );

		// See if WP Debug is enabled.
		$sfw_wordpress_status['wp_debug_enabled'] = defined( 'WP_DEBUG' ) ? __( 'Yes', 'subscriptions-for-woocommerce' ) : __( 'No', 'subscriptions-for-woocommerce' );

		// See if WP Cache is enabled.
		$sfw_wordpress_status['wp_cache_enabled'] = defined( 'WP_CACHE' ) ? __( 'Yes', 'subscriptions-for-woocommerce' ) : __( 'No', 'subscriptions-for-woocommerce' );

		// Get the total number of WordPress users on the site.
		$sfw_wordpress_status['wp_users'] = function_exists( 'count_users' ) ? count_users() : __( 'N/A (count_users function does not exist)', 'subscriptions-for-woocommerce' );

		// Get the number of published WordPress posts.
		$sfw_wordpress_status['wp_posts'] = wp_count_posts()->publish >= 1 ? wp_count_posts()->publish : 0;

		// Get PHP memory limit.
		$sfw_system_status['php_memory_limit'] = function_exists( 'ini_get' ) ? (int) ini_get( 'memory_limit' ) : __( 'N/A (ini_get function does not exist)', 'subscriptions-for-woocommerce' );

		// Get the PHP error log path.
		$sfw_system_status['php_error_log_path'] = ! ini_get( 'error_log' ) ? __( 'N/A', 'subscriptions-for-woocommerce' ) : ini_get( 'error_log' );

		// Get PHP max upload size.
		$sfw_system_status['php_max_upload'] = function_exists( 'ini_get' ) ? (int) ini_get( 'upload_max_filesize' ) : __( 'N/A (ini_get function does not exist)', 'subscriptions-for-woocommerce' );

		// Get PHP max post size.
		$sfw_system_status['php_max_post'] = function_exists( 'ini_get' ) ? (int) ini_get( 'post_max_size' ) : __( 'N/A (ini_get function does not exist)', 'subscriptions-for-woocommerce' );

		// Get the PHP architecture.
		if ( PHP_INT_SIZE == 4 ) {
			$sfw_system_status['php_architecture'] = '32-bit';
		} elseif ( PHP_INT_SIZE == 8 ) {
			$sfw_system_status['php_architecture'] = '64-bit';
		} else {
			$sfw_system_status['php_architecture'] = 'N/A';
		}

		// Get server host name.
		$sfw_system_status['server_hostname'] = function_exists( 'gethostname' ) ? gethostname() : __( 'N/A (gethostname function does not exist)', 'subscriptions-for-woocommerce' );

		// Show the number of processes currently running on the server.
		$sfw_system_status['processes'] = function_exists( 'exec' ) ? @exec( 'ps aux | wc -l' ) : __( 'N/A (make sure exec is enabled)', 'subscriptions-for-woocommerce' );

		// Get the memory usage.
		$sfw_system_status['memory_usage'] = function_exists( 'memory_get_peak_usage' ) ? round( memory_get_peak_usage( true ) / 1024 / 1024, 2 ) : 0;

		// Get CPU usage.
		// Check to see if system is Windows, if so then use an alternative since sys_getloadavg() won't work.
		if ( stristr( PHP_OS, 'win' ) ) {
			$sfw_system_status['is_windows'] = true;
			$sfw_system_status['windows_cpu_usage'] = function_exists( 'exec' ) ? @exec( 'wmic cpu get loadpercentage /all' ) : __( 'N/A (make sure exec is enabled)', 'subscriptions-for-woocommerce' );
		}

		// Get the memory limit.
		$sfw_system_status['memory_limit'] = function_exists( 'ini_get' ) ? (int) ini_get( 'memory_limit' ) : __( 'N/A (ini_get function does not exist)', 'subscriptions-for-woocommerce' );

		// Get the PHP maximum execution time.
		$sfw_system_status['php_max_execution_time'] = function_exists( 'ini_get' ) ? ini_get( 'max_execution_time' ) : __( 'N/A (ini_get function does not exist)', 'subscriptions-for-woocommerce' );

		// Get outgoing IP address.
		$outgoing_ip                     = $this->wps_sfw_get_outgoing_ip_address();
		$sfw_system_status['outgoing_ip'] = '' !== $outgoing_ip ? $outgoing_ip : __( 'N/A', 'subscriptions-for-woocommerce' );

		$sfw_system_data['php'] = $sfw_system_status;
		$sfw_system_data['wp'] = $sfw_wordpress_status;

		if ( $cache_ttl > 0 ) {
			set_transient( 'wps_sfw_system_status', $sfw_system_data, $cache_ttl );
		}

		return $sfw_system_data;
	}

	/**
	 * Generate html components.
	 *
	 * @param  string $sfw_components    html to display.
	 * @since  1.0.0
	 */
	public function wps_sfw_plug_generate_html( $sfw_components = array() ) {
		if ( ! is_array( $sfw_components ) || empty( $sfw_components ) ) {
			return '';
		}

		$markup          = '';
		$is_section_open = false;

		foreach ( $sfw_components as $sfw_component ) {
			if ( empty( $sfw_component['type'] ) ) {
				continue;
			}

			$pro_group_tag = '';
			$is_pro        = false;
			$is_pro        = apply_filters( 'wsp_sfw_check_pro_plugin', $is_pro );

			if ( ! $is_pro && ! empty( $sfw_component['class'] ) && preg_match( "/\bwps_pro_settings\b/", $sfw_component['class'] ) ) {
				$pro_group_tag = 'wps_pro_settings_tag';
			}

			if ( 'section' === $sfw_component['type'] ) {
				if ( $is_section_open ) {
					$markup .= '</div></section>';
				}

				$markup         .= $this->wps_sfw_render_section_open_markup( $sfw_component );
				$is_section_open = true;
				continue;
			}

			if ( ! $is_section_open ) {
				$markup         .= '<section class="wps-sfw-settings-section"><div class="wps-sfw-settings-section__body">';
				$is_section_open = true;
			}

			if ( 'button' === $sfw_component['type'] || 'submit' === $sfw_component['type'] ) {
				$markup .= $this->wps_sfw_render_action_markup( $sfw_component );
				continue;
			}

			$markup .= $this->wps_sfw_render_field_markup( $sfw_component, $pro_group_tag );
		}

		if ( $is_section_open ) {
			$markup .= '</div></section>';
		}

		return $markup;
	}

	/**
	 * Render settings section wrapper markup.
	 *
	 * @param array $sfw_component Settings section config.
	 * @return string
	 */
	public function wps_sfw_render_section_open_markup( $sfw_component ) {
		$section_classes = 'wps-sfw-settings-section';
		$section_id      = ! empty( $sfw_component['id'] ) ? ' id="' . esc_attr( $sfw_component['id'] ) . '"' : '';

		if ( ! empty( $sfw_component['class'] ) ) {
			$section_classes .= ' ' . $sfw_component['class'];
		}

		$markup  = '<section class="' . esc_attr( $section_classes ) . '"' . $section_id . '>';
		$markup .= '<div class="wps-sfw-settings-section__head">';

		if ( ! empty( $sfw_component['eyebrow'] ) ) {
			$markup .= '<div class="wps-sfw-settings-section__eyebrow">' . esc_html( $sfw_component['eyebrow'] ) . '</div>';
		}

		if ( ! empty( $sfw_component['title'] ) ) {
			$markup .= '<h3>' . esc_html( $sfw_component['title'] ) . '</h3>';
		}

		if ( ! empty( $sfw_component['description'] ) ) {
			$markup .= '<p>' . esc_html( $sfw_component['description'] ) . '</p>';
		}

		$markup .= '</div><div class="wps-sfw-settings-section__body">';

		return $markup;
	}

	/**
	 * Render a settings field row.
	 *
	 * @param array  $sfw_component Settings field config.
	 * @param string $pro_group_tag Optional pro marker class.
	 * @return string
	 */
	public function wps_sfw_render_field_markup( $sfw_component, $pro_group_tag = '' ) {
		$type           = $sfw_component['type'];
		$field_id       = isset( $sfw_component['id'] ) ? $sfw_component['id'] : '';
		$field_name     = $this->wps_sfw_get_component_name( $sfw_component );
		$field_classes  = 'wps-sfw-setting-field wps-sfw-setting-field--' . sanitize_html_class( $type );
		$input_classes  = ! empty( $sfw_component['class'] ) ? ' ' . $sfw_component['class'] : '';
		$label_subtext  = ! empty( $sfw_component['subtitle'] ) ? $sfw_component['subtitle'] : '';
		$control_hint   = ! empty( $sfw_component['description'] ) ? $sfw_component['description'] : '';
		$control_label  = ! empty( $sfw_component['control_label'] ) ? $sfw_component['control_label'] : $control_hint;
		$value          = isset( $sfw_component['value'] ) ? $sfw_component['value'] : '';
		$attr_string    = $this->wps_sfw_get_component_attributes( $sfw_component );
		$checked        = $this->wps_sfw_component_is_checked( $sfw_component );
		$value_attr     = isset( $sfw_component['value'] ) && '' !== $sfw_component['value'] ? $sfw_component['value'] : 'on';

		if ( ! empty( $pro_group_tag ) ) {
			$field_classes .= ' ' . $pro_group_tag;
		}

		if ( 'hidden' === $type ) {
			return '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $value ) . '">';
		}

		if ( 'information' === $type ) {
			$info_classes = 'wps-sfw-settings-note';
			if ( ! empty( $sfw_component['class'] ) ) {
				$info_classes .= ' ' . $sfw_component['class'];
			}
			if ( ! empty( $pro_group_tag ) ) {
				$info_classes .= ' ' . $pro_group_tag;
			}

			return '<div id="' . esc_attr( $field_id ) . '" class="' . esc_attr( $info_classes ) . '"><p>' . esc_html( $field_name ) . '</p></div>';
		}

		$markup  = '<div class="' . esc_attr( $field_classes ) . '">';
		$markup .= '<div class="wps-sfw-setting-field__label">';
		$markup .= '<label class="wps-sfw-setting-label" for="' . esc_attr( $field_id ) . '">' . esc_html( $sfw_component['title'] ) . '</label>';

		if ( ! empty( $label_subtext ) ) {
			$markup .= '<span class="wps-sfw-setting-subtext">' . wp_kses_post( $label_subtext ) . '</span>';
		}

		$markup .= '</div>';
		$markup .= '<div class="wps-sfw-setting-field__control">';

		switch ( $type ) {
			case 'number':
			case 'email':
			case 'text':
			case 'password':
				$markup .= '<div class="wps-sfw-input-group">';
				if ( ! empty( $sfw_component['placeholder'] ) ) {
					$markup .= '<span class="wps-sfw-input-label">' . esc_html( $sfw_component['placeholder'] ) . '</span>';
				}
				$markup .= '<input class="wps-sfw-input' . esc_attr( $input_classes ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( isset( $sfw_component['placeholder'] ) ? $sfw_component['placeholder'] : '' ) . '" ' . $attr_string . '>';
				$markup .= '</div>';
				if ( ! empty( $control_hint ) ) {
					$markup .= '<span class="wps-sfw-input-hint">' . esc_html( $control_hint ) . '</span>';
				}
				break;

			case 'textarea':
				$markup .= '<div class="wps-sfw-input-group">';
				if ( ! empty( $sfw_component['placeholder'] ) ) {
					$markup .= '<span class="wps-sfw-input-label">' . esc_html( $sfw_component['placeholder'] ) . '</span>';
				}
				$markup .= '<textarea class="wps-sfw-input wps-sfw-textarea' . esc_attr( $input_classes ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" rows="4" placeholder="' . esc_attr( isset( $sfw_component['placeholder'] ) ? $sfw_component['placeholder'] : '' ) . '" ' . $attr_string . '>' . esc_textarea( $value ) . '</textarea>';
				$markup .= '</div>';
				if ( ! empty( $control_hint ) ) {
					$markup .= '<span class="wps-sfw-input-hint">' . esc_html( $control_hint ) . '</span>';
				}
				break;

			case 'select':
			case 'multiselect':
				$markup .= '<div class="wps-sfw-input-group wps-sfw-input-group--select">';
				if ( ! empty( $sfw_component['placeholder'] ) ) {
					$markup .= '<span class="wps-sfw-input-label">' . esc_html( $sfw_component['placeholder'] ) . '</span>';
				}
				$markup .= '<select class="wps-sfw-select' . esc_attr( $input_classes ) . '" name="' . esc_attr( $field_name ) . ( 'multiselect' === $type ? '[]' : '' ) . '" id="' . esc_attr( $field_id ) . '" ' . ( 'multiselect' === $type ? 'multiple="multiple"' : '' ) . ' ' . $attr_string . '>';
				if ( ! empty( $sfw_component['options'] ) && is_array( $sfw_component['options'] ) ) {
					foreach ( $sfw_component['options'] as $sfw_key => $sfw_val ) {
						$selected = '';
						if ( is_array( $value ) && in_array( (string) $sfw_key, $value, true ) ) {
							$selected = ' selected="selected"';
						} elseif ( ! is_array( $value ) && (string) $value === (string) $sfw_key ) {
							$selected = ' selected="selected"';
						}
						$markup .= '<option value="' . esc_attr( $sfw_key ) . '"' . $selected . '>' . esc_html( $sfw_val ) . '</option>';
					}
				}
				$markup .= '</select></div>';
				if ( ! empty( $control_hint ) ) {
					$markup .= '<span class="wps-sfw-input-hint">' . esc_html( $control_hint ) . '</span>';
				}
				break;

			case 'checkbox':
				$toggle_label = $checked && ! empty( $sfw_component['toggle_label_active'] ) ? $sfw_component['toggle_label_active'] : __( 'Enabled', 'subscriptions-for-woocommerce' );
				if ( ! $checked ) {
					$toggle_label = ! empty( $sfw_component['toggle_label_inactive'] ) ? $sfw_component['toggle_label_inactive'] : __( 'Disabled', 'subscriptions-for-woocommerce' );
				}
				$toggle_state = $checked && ! empty( $sfw_component['toggle_state_active'] ) ? $sfw_component['toggle_state_active'] : __( 'This setting is active.', 'subscriptions-for-woocommerce' );
				if ( ! $checked ) {
					$toggle_state = ! empty( $sfw_component['toggle_state_inactive'] ) ? $sfw_component['toggle_state_inactive'] : __( 'This setting is inactive.', 'subscriptions-for-woocommerce' );
				}

				$markup .= '<label class="wps-sfw-toggle" for="' . esc_attr( $field_id ) . '">';
				$markup .= '<input class="wps-sfw-toggle__input' . esc_attr( $input_classes ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" type="checkbox" value="' . esc_attr( $value_attr ) . '" role="switch" aria-checked="' . esc_attr( $checked ? 'true' : 'false' ) . '" ' . checked( $checked, true, false ) . ' ' . $attr_string . '>';
				$markup .= '<span class="wps-sfw-toggle__track" aria-hidden="true"><span class="wps-sfw-toggle__thumb"></span></span>';
				$markup .= '<span class="wps-sfw-toggle__text">' . esc_html( $toggle_label ) . '<span class="wps-sfw-toggle__state">' . esc_html( $toggle_state ) . '</span></span>';
				$markup .= '</label>';
				break;

			case 'radio':
				$markup .= '<div class="wps-sfw-radio-group">';
				if ( ! empty( $sfw_component['options'] ) && is_array( $sfw_component['options'] ) ) {
					foreach ( $sfw_component['options'] as $sfw_radio_key => $sfw_radio_val ) {
						$markup .= '<label class="wps-sfw-radio">';
						$markup .= '<input class="wps-sfw-radio__input' . esc_attr( $input_classes ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $sfw_radio_key ) . '" type="radio" ' . checked( $sfw_radio_key, $value, false ) . ' ' . $attr_string . '>';
						$markup .= '<span class="wps-sfw-radio__mark" aria-hidden="true"></span>';
						$markup .= '<span class="wps-sfw-radio__label">' . esc_html( $sfw_radio_val ) . '</span>';
						$markup .= '</label>';
					}
				}
				$markup .= '</div>';
				break;

			case 'radio-switch':
				$toggle_label = $checked && ! empty( $sfw_component['toggle_label_active'] ) ? $sfw_component['toggle_label_active'] : __( 'Enabled', 'subscriptions-for-woocommerce' );
				if ( ! $checked ) {
					$toggle_label = ! empty( $sfw_component['toggle_label_inactive'] ) ? $sfw_component['toggle_label_inactive'] : __( 'Disabled', 'subscriptions-for-woocommerce' );
				}
				$toggle_state = $checked && ! empty( $sfw_component['toggle_state_active'] ) ? $sfw_component['toggle_state_active'] : __( 'This setting is enabled.', 'subscriptions-for-woocommerce' );
				if ( ! $checked ) {
					$toggle_state = ! empty( $sfw_component['toggle_state_inactive'] ) ? $sfw_component['toggle_state_inactive'] : __( 'This setting is disabled.', 'subscriptions-for-woocommerce' );
				}

				$markup .= '<label class="wps-sfw-toggle" for="' . esc_attr( $field_id ) . '">';
				$markup .= '<input class="wps-sfw-toggle__input' . esc_attr( $input_classes ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" type="checkbox" value="on" role="switch" aria-checked="' . esc_attr( $checked ? 'true' : 'false' ) . '" ' . checked( $checked, true, false ) . ' ' . $attr_string . '>';
				$markup .= '<span class="wps-sfw-toggle__track" aria-hidden="true"><span class="wps-sfw-toggle__thumb"></span></span>';
				$markup .= '<span class="wps-sfw-toggle__text">' . esc_html( $toggle_label ) . '<span class="wps-sfw-toggle__state">' . esc_html( $toggle_state ) . '</span></span>';
				$markup .= '</label>';
				break;

			default:
				break;
		}

		$markup .= '</div></div>';

		return $markup;
	}

	/**
	 * Render the settings action row markup.
	 *
	 * @param array $sfw_component Settings button config.
	 * @return string
	 */
	public function wps_sfw_render_action_markup( $sfw_component ) {
		$button_id   = ! empty( $sfw_component['id'] ) ? $sfw_component['id'] : '';
		$button_name = $this->wps_sfw_get_component_name( $sfw_component );
		$button_text = ! empty( $sfw_component['button_text'] ) ? $sfw_component['button_text'] : __( 'Save Settings', 'subscriptions-for-woocommerce' );
		$classes     = 'wps-sfw-save-button';

		if ( ! empty( $sfw_component['class'] ) ) {
			$classes .= ' ' . $sfw_component['class'];
		}

		return '<div class="wps-sfw-settings-actions"><button type="submit" class="' . esc_attr( $classes ) . '" name="' . esc_attr( $button_name ) . '" id="' . esc_attr( $button_id ) . '">' . esc_html( $button_text ) . '</button></div>';
	}

	/**
	 * Get the form input name for a component.
	 *
	 * @param array $sfw_component Settings field config.
	 * @return string
	 */
	public function wps_sfw_get_component_name( $sfw_component ) {
		if ( array_key_exists( 'name', $sfw_component ) && ! empty( $sfw_component['name'] ) ) {
			return $sfw_component['name'];
		}

		return isset( $sfw_component['id'] ) ? $sfw_component['id'] : '';
	}

	/**
	 * Build common HTML attributes for a field.
	 *
	 * @param array $sfw_component Settings field config.
	 * @return string
	 */
	public function wps_sfw_get_component_attributes( $sfw_component ) {
		$attributes = array();

		if ( isset( $sfw_component['required'] ) && 'yes' === $sfw_component['required'] ) {
			$attributes[] = 'required';
		}

		if ( ! empty( $sfw_component['attr'] ) ) {
			$allowed_attributes = array( 'readonly', 'disabled', 'multiple' );
			$requested_attrs    = preg_split( '/\s+/', $sfw_component['attr'] );

			foreach ( $requested_attrs as $requested_attr ) {
				if ( in_array( $requested_attr, $allowed_attributes, true ) ) {
					$attributes[] = $requested_attr;
				}
			}
		}

		if ( ! empty( $sfw_component['custom_attributes'] ) && is_array( $sfw_component['custom_attributes'] ) ) {
			foreach ( $sfw_component['custom_attributes'] as $attribute_name => $attribute_value ) {
				$attributes[] = esc_attr( $attribute_name ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		return implode( ' ', $attributes );
	}

	/**
	 * Determine whether a checkbox-like field is active.
	 *
	 * @param array $sfw_component Settings field config.
	 * @return bool
	 */
	public function wps_sfw_component_is_checked( $sfw_component ) {
		if ( isset( $sfw_component['checked'] ) ) {
			return in_array( $sfw_component['checked'], array( 'on', 'yes', true, 1, '1' ), true );
		}

		if ( isset( $sfw_component['value'] ) ) {
			return in_array( $sfw_component['value'], array( 'on', 'yes', true, 1, '1' ), true );
		}

		return false;
	}
}
