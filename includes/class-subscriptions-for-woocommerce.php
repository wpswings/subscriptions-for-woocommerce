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

			$this->version = '1.8.0';
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
		$this->wps_sfw_init_payment_integration();
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

		$this->loader->add_action( 'admin_enqueue_scripts', $sfw_plugin_admin, 'wps_sfw_admin_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $sfw_plugin_admin, 'wps_sfw_admin_enqueue_scripts' );

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

		if ( wps_sfw_check_plugin_enable() ) {
			$this->loader->add_action( 'product_type_options', $sfw_plugin_admin, 'wps_sfw_create_subscription_product_type' );

			$this->loader->add_filter( 'woocommerce_product_data_tabs', $sfw_plugin_admin, 'wps_sfw_custom_product_tab_for_subscription' );

			$this->loader->add_action( 'woocommerce_product_data_panels', $sfw_plugin_admin, 'wps_sfw_custom_product_fields_for_subscription' );

			$this->loader->add_action( 'woocommerce_process_product_meta', $sfw_plugin_admin, 'wps_sfw_save_custom_product_fields_data_for_subscription', 10, 2 );

			$this->loader->add_action( 'init', $sfw_plugin_admin, 'wps_sfw_admin_cancel_susbcription', 99 );

			$this->loader->add_filter( 'woocommerce_register_shop_order_post_statuses', $sfw_plugin_admin, 'wps_sfw_register_new_order_statuses' );

			$this->loader->add_filter( 'wc_order_statuses', $sfw_plugin_admin, 'wps_sfw_new_wc_order_statuses' );
			// WPLM Translation.
			$this->loader->add_filter( 'wcml_js_lock_fields_ids', $sfw_plugin_admin, 'wps_sfw_add_lock_custom_fields_ids' );

			// paypal Keys Validation.
			$this->loader->add_filter( 'wp_ajax_wps_sfw_paypal_keys_validation', $sfw_plugin_admin, 'wps_sfw_paypal_keys_validation_callack' );

			// subscription box working.
			$this->loader->add_filter( 'product_type_selector', $sfw_plugin_admin, 'wsp_register_subscription_box_product_type', 10, 1 );
			$this->loader->add_filter( 'woocommerce_product_data_tabs', $sfw_plugin_admin, 'wps_sfw_custom_product_tab_for_subscription_box' );
			$this->loader->add_action( 'woocommerce_product_data_panels', $sfw_plugin_admin, 'wps_sfw_custom_product_fields_for_subscription_box' );
			$this->loader->add_action( 'woocommerce_process_product_meta', $sfw_plugin_admin, 'wps_sfw_save_subscription_box_data_for_subscription', 10, 2 );
		}

		/*cron for notification*/
		$this->loader->add_action( 'admin_init', $sfw_plugin_admin, 'wps_sfw_set_cron_for_plugin_notification' );
		$this->loader->add_action( 'wps_wgm_check_for_notification_update', $sfw_plugin_admin, 'wps_sfw_save_notice_message' );
		$this->loader->add_action( 'wp_ajax_wps_sfw_dismiss_notice_banner', $sfw_plugin_admin, 'wps_sfw_dismiss_notice_banner_callback' );

		$this->loader->add_action( 'admin_menu', $sfw_plugin_admin, 'wps_sfw_remove_subscription_custom_menu' );
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

			$this->loader->add_action( 'woocommerce_before_calculate_totals', $sfw_plugin_public, 'wps_sfw_add_subscription_price_and_sigup_fee', 999 );

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

			$this->loader->add_filter( 'woocommerce_gateway_description', $sfw_plugin_public, 'wps_sfw_change_payment_gateway_description', 10, 2 );

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
			'title'       => esc_html__( 'General Setting', 'subscriptions-for-woocommerce' ),
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

		// subscription box.
		$sfw_default_tabs['subscription-for-woocommerce-subscription-box'] = array(
			'title'       => esc_html__( 'Subscription Box', 'subscriptions-for-woocommerce' ),
			'name'        => 'subscription-for-woocommerce-subscription-box',
			'file_path'       => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);
		// subscription box.

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
		$sfw_system_status['outgoing_ip'] = function_exists( 'wps_sfw_get_file_content' ) ? wps_sfw_get_file_content( 'http://ipecho.net/plain' ) : __( 'N/A (wps_sfw_get_file_content function does not exist)', 'subscriptions-for-woocommerce' );

		$sfw_system_data['php'] = $sfw_system_status;
		$sfw_system_data['wp'] = $sfw_wordpress_status;

		return $sfw_system_data;
	}

	/**
	 * Generate html components.
	 *
	 * @param  string $sfw_components    html to display.
	 * @since  1.0.0
	 */
	public function wps_sfw_plug_generate_html( $sfw_components = array() ) {
		if ( is_array( $sfw_components ) && ! empty( $sfw_components ) ) {
			foreach ( $sfw_components as $sfw_component ) {
				$wps_sfw_name = array_key_exists( 'name', $sfw_component ) ? $sfw_component['name'] : $sfw_component['id'];

				$pro_group_tag = '';
				$is_pro = false;
				$is_pro = apply_filters( 'wsp_sfw_check_pro_plugin', $is_pro );
				if ( ! $is_pro ) {

					if ( preg_match( "/\wps_pro_settings\b/", $sfw_component['class'] ) ) :
						$pro_group_tag = 'wps_pro_settings_tag';
					endif;
				}

				switch ( $sfw_component['type'] ) {

					case 'hidden':
					case 'number':
					case 'email':
					case 'text':
						?>
					<div class="wps-form-group wps-sfw-<?php echo esc_attr( $sfw_component['type'] ); ?>">
						<div class="wps-form-group__label">
							<label for="<?php echo esc_attr( $sfw_component['id'] ); ?>" class="wps-form-label"><?php echo esc_html( $sfw_component['title'] ); // WPCS: XSS ok. ?></label>
						</div>
						<div class="wps-form-group__control">
							<label class="mdc-text-field mdc-text-field--outlined">
								<span class="mdc-notched-outline">
									<span class="mdc-notched-outline__leading"></span>
									<span class="mdc-notched-outline__notch">
										<?php if ( 'number' != $sfw_component['type'] ) { ?>
											<span class="mdc-floating-label" id="my-label-id" style=""><?php echo esc_attr( $sfw_component['placeholder'] ); ?></span>
										<?php } ?>
									</span>
									<span class="mdc-notched-outline__trailing"></span>
								</span>
								<input 
								class="mdc-text-field__input <?php echo esc_attr( $sfw_component['class'] ); ?>" 
								name="<?php echo esc_attr( $wps_sfw_name ); ?>"
								id="<?php echo esc_attr( $sfw_component['id'] ); ?>"
								type="<?php echo esc_attr( $sfw_component['type'] ); ?>"
								value="<?php echo esc_attr( $sfw_component['value'] ); ?>"
								placeholder="<?php echo esc_attr( $sfw_component['placeholder'] ); ?>"
								<?php echo ( isset( $sfw_component['required'] ) && 'yes' == $sfw_component['required'] ) ? 'required' : ''; ?>
								>
							</label>
							<div class="mdc-text-field-helper-line">
								<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"><?php echo esc_attr( $sfw_component['description'] ); ?></div>
							</div>
						</div>
					</div>
						<?php
						break;

					case 'password':
						?>
					<div class="wps-form-group">
						<div class="wps-form-group__label">
							<label for="<?php echo esc_attr( $sfw_component['id'] ); ?>" class="wps-form-label"><?php echo esc_html( $sfw_component['title'] ); // WPCS: XSS ok. ?></label>
						</div>
						<div class="wps-form-group__control">
							<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon">
								<span class="mdc-notched-outline">
									<span class="mdc-notched-outline__leading"></span>
									<span class="mdc-notched-outline__notch">
									</span>
									<span class="mdc-notched-outline__trailing"></span>
								</span>
								<input 
								class="mdc-text-field__input <?php echo esc_attr( $sfw_component['class'] ); ?> wps-form__password" 
								name="<?php echo esc_attr( $wps_sfw_name ); ?>"
								id="<?php echo esc_attr( $sfw_component['id'] ); ?>"
								type="<?php echo esc_attr( $sfw_component['type'] ); ?>"
								value="<?php echo esc_attr( $sfw_component['value'] ); ?>"
								placeholder="<?php echo esc_attr( $sfw_component['placeholder'] ); ?>"
								>
								<i class="material-icons mdc-text-field__icon mdc-text-field__icon--trailing wps-password-hidden" tabindex="0" role="button">visibility</i>
							</label>
							<div class="mdc-text-field-helper-line">
								<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"><?php echo esc_attr( $sfw_component['description'] ); ?></div>
							</div>
						</div>
					</div>
						<?php
						break;

					case 'textarea':
						?>
					<div class="wps-form-group">
						<div class="wps-form-group__label">
							<label class="wps-form-label" for="<?php echo esc_attr( $sfw_component['id'] ); ?>"><?php echo esc_attr( $sfw_component['title'] ); ?></label>
						</div>
						<div class="wps-form-group__control">
							<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea"  	for="text-field-hero-input">
								<span class="mdc-notched-outline">
									<span class="mdc-notched-outline__leading"></span>
									<span class="mdc-notched-outline__notch">
										<span class="mdc-floating-label"><?php echo esc_attr( $sfw_component['placeholder'] ); ?></span>
									</span>
									<span class="mdc-notched-outline__trailing"></span>
								</span>
								<span class="mdc-text-field__resizer">
									<textarea class="mdc-text-field__input <?php echo esc_attr( $sfw_component['class'] ); ?>" rows="2" cols="25" aria-label="Label" name="<?php echo esc_attr( $wps_sfw_name ); ?>" id="<?php echo esc_attr( $sfw_component['id'] ); ?>" placeholder="<?php echo esc_attr( $sfw_component['placeholder'] ); ?>"<?php echo ( isset( $sfw_component['required'] ) && 'yes' == $sfw_component['required'] ) ? 'required' : ''; ?>><?php echo esc_textarea( $sfw_component['value'] ); // WPCS: XSS ok. ?></textarea>
								</span>
							</label>

						</div>
					</div>

						<?php
						break;

					case 'select':
					case 'multiselect':
						?>
					<div class="wps-form-group">
						<div class="wps-form-group__label">
							<label class="wps-form-label" for="<?php echo esc_attr( $sfw_component['id'] ); ?>"><?php echo esc_html( $sfw_component['title'] ); ?></label>
						</div>
						<div class="wps-form-group__control">
							<div class="wps-form-select">
								<select name="<?php echo esc_attr( $wps_sfw_name ); ?><?php echo ( 'multiselect' === $sfw_component['type'] ) ? '[]' : ''; ?>" id="<?php echo esc_attr( $sfw_component['id'] ); ?>" class="mdl-textfield__input <?php echo esc_attr( $sfw_component['class'] ); ?>" <?php echo 'multiselect' === $sfw_component['type'] ? 'multiple="multiple"' : ''; ?> >
									<?php
									foreach ( $sfw_component['options'] as $sfw_key => $sfw_val ) {
										?>
										<option value="<?php echo esc_attr( $sfw_key ); ?>"
											<?php
											if ( is_array( $sfw_component['value'] ) ) {
												selected( in_array( (string) $sfw_key, $sfw_component['value'], true ), true );
											} else {
												selected( $sfw_component['value'], (string) $sfw_key );
											}
											?>
											>
											<?php echo esc_html( $sfw_val ); ?>
										</option>
										<?php
									}
									?>
								</select>
								<label class="mdl-textfield__label" for="octane"><?php echo esc_html( $sfw_component['description'] ); ?></label>
							</div>
						</div>
					</div>

						<?php
						break;

					case 'checkbox':
						?>
					<div class="wps-form-group">
						<div class="wps-form-group__label">
							<label for="<?php echo esc_attr( $sfw_component['id'] ); ?>" class="wps-form-label"><?php echo esc_html( $sfw_component['title'] ); ?></label>
						</div>
						<div class="wps-form-group__control wps-pl-4">
							<div class="mdc-form-field">
								<div class="mdc-checkbox">
									<input 
									name="<?php echo esc_attr( $wps_sfw_name ); ?>"
									id="<?php echo esc_attr( $sfw_component['id'] ); ?>"
									type="checkbox"
									class="mdc-checkbox__native-control <?php echo esc_attr( isset( $sfw_component['class'] ) ? $sfw_component['class'] : '' ); ?>"
									value="<?php echo esc_attr( $sfw_component['value'] ); ?>"
									<?php
									if ( 'on' === $sfw_component['checked'] ) {
										checked( $sfw_component['checked'], 'on' );
									}
									?>
									/>
									<div class="mdc-checkbox__background">
										<svg class="mdc-checkbox__checkmark" viewBox="0 0 24 24">
											<path class="mdc-checkbox__checkmark-path" fill="none" d="M1.73,12.91 8.1,19.28 22.79,4.59"/>
										</svg>
										<div class="mdc-checkbox__mixedmark"></div>
									</div>
									<div class="mdc-checkbox__ripple"></div>
								</div>
								<label for="<?php echo esc_attr( $sfw_component['id'] ); ?>"><?php echo wp_kses_post( $sfw_component['description'] ); // WPCS: XSS ok. ?></label>
							</div>
						</div>
					</div>
						<?php
						break;

					case 'radio':
						?>
					<div class="wps-form-group">
						<div class="wps-form-group__label">
							<label for="<?php echo esc_attr( $sfw_component['id'] ); ?>" class="wps-form-label"><?php echo esc_html( $sfw_component['title'] ); ?></label>
						</div>
						<div class="wps-form-group__control wps-pl-4">
							<div class="wps-flex-col">
								<?php
								foreach ( $sfw_component['options'] as $sfw_radio_key => $sfw_radio_val ) {
									?>
									<div class="mdc-form-field">
										<div class="mdc-radio">
											<input
											id = "<?php echo esc_attr( $sfw_component['id'] ); ?>"
											name="<?php echo esc_attr( $wps_sfw_name ); ?>"
											value="<?php echo esc_attr( $sfw_radio_key ); ?>"
											type="radio"
											class="mdc-radio__native-control <?php echo esc_attr( $sfw_component['class'] ); ?>"
											<?php checked( $sfw_radio_key, $sfw_component['value'] ); ?>
											<?php echo ( isset( $sfw_component['required'] ) && 'yes' == $sfw_component['required'] ) ? 'required' : ''; ?>
											>
											<div class="mdc-radio__background">
												<div class="mdc-radio__outer-circle"></div>
												<div class="mdc-radio__inner-circle"></div>
											</div>
											<div class="mdc-radio__ripple"></div>
										</div>
										<label for="radio-1"><?php echo esc_html( $sfw_radio_val ); ?></label>
									</div>	
									<?php
								}
								?>
							</div>
						</div>
					</div>
						<?php
						break;

					case 'radio-switch':
						?>

					<div class="wps-form-group <?php echo esc_attr( $pro_group_tag ); ?>">
						<div class="wps-form-group__label">
							<label for="" class="wps-form-label"><?php echo esc_html( $sfw_component['title'] ); ?></label>
						</div>
						<div class="wps-form-group__control">
							<div>
								<div class="mdc-switch">
									<div class="mdc-switch__track"></div>
									<div class="mdc-switch__thumb-underlay">
										<div class="mdc-switch__thumb"></div>
										<input name="<?php echo esc_attr( $wps_sfw_name ); ?>" type="checkbox" id="basic-switch" value="on" class="mdc-switch__native-control" role="switch" aria-checked="
																<?php
																if ( 'on' == $sfw_component['value'] ) {
																	echo 'true';
																} else {
																	echo 'false';
																}
																?>
										"
										<?php checked( $sfw_component['value'], 'on' ); ?>
										>
									</div>
								</div>
							</div>
						</div>
					</div>
						<?php
						break;

					case 'button':
						?>
					<div class="wps-form-group">
						<div class="wps-form-group__label"></div>
						<div class="wps-form-group__control">
							<button class="mdc-button mdc-button--raised" name="<?php echo esc_attr( $wps_sfw_name ); ?>"
								id="<?php echo esc_attr( $sfw_component['id'] ); ?>"> <span class="mdc-button__ripple"></span>
								<span class="mdc-button__label"><?php echo esc_attr( $sfw_component['button_text'] ); ?></span>
							</button>
						</div>
					</div>

						<?php
						break;

					case 'submit':
						?>
					<tr valign="top">
						<td scope="row">
							<input type="submit" class="button button-primary" 
							name="<?php echo esc_attr( $wps_sfw_name ); ?>"
							id="<?php echo esc_attr( $sfw_component['id'] ); ?>"
							value="<?php echo esc_attr( $sfw_component['button_text'] ); ?>"
							/>
						</td>
					</tr>
						<?php
						break;
					case 'information':
						?>
						<p id="<?php echo esc_attr( $sfw_component['id'] ); ?>" class="<?php echo esc_attr( $sfw_component['class'] ); ?>" >
						<?php echo esc_attr( $wps_sfw_name ); ?>
						</p>
						<?php
						break;
					default:
						break;

				}
			}
		}
	}
}
