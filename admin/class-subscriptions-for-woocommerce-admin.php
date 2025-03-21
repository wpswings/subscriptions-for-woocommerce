<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin
 * @author     WP Swings <webmaster@wpswings.com>
 */
class Subscriptions_For_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook      The plugin page slug.
	 */
	public function wps_sfw_admin_enqueue_styles( $hook ) {

		$wps_sfw_screen_ids = wps_sfw_get_page_screen();
		$screen = get_current_screen();
		if ( isset( $screen->id ) ) {
			$pagescreen = $screen->id;
		}

		if ( isset( $screen->id ) && ( in_array( $screen->id, $wps_sfw_screen_ids ) || ( 'wp-swings_page_home' == $screen->id ) ) ) {
			// Multistep form css.
			if ( ! wps_sfw_check_multistep() ) {
				$style_url        = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'build/style-index.css';
				wp_enqueue_style(
					'wps-sfw-admin-react-styles',
					$style_url,
					array(),
					time(),
					false
				);
				return;
			}
			wp_enqueue_style( 'wps-sfw-select2-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/subscriptions-for-woocommerce-select2.css', array(), time(), 'all' );

			wp_enqueue_style( 'wps-sfw-meterial-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'wps-sfw-meterial-css2', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'wps-sfw-meterial-lite', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.css', array(), time(), 'all' );

			wp_enqueue_style( 'wps-sfw-meterial-icons-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/icon.css', array(), time(), 'all' );

			wp_enqueue_style( $this->plugin_name . '-admin-global', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/subscriptions-for-woocommerce-admin-global.css', array( 'wps-sfw-meterial-icons-css' ), time(), 'all' );

			wp_enqueue_style( $this->plugin_name, SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/subscriptions-for-woocommerce-admin.css', array(), time(), 'all' );
		}

		if ( ( isset( $pagescreen ) && 'plugins' === $pagescreen ) ) {

			wp_enqueue_style( $this->plugin_name, SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/subscriptions-for-woocommerce-admin.css', array(), time(), 'all' );
		}

		if ( isset( $screen->id ) && ( 'product' == $screen->id || 'wp-swings_page_home' == $screen->id ) ) {
			wp_enqueue_style( 'subscription-for-woocommerce-product-edit', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/subscription-for-woocommerce-product-edit.css', array(), time(), 'all' );

		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook      The plugin page slug.
	 */
	public function wps_sfw_admin_enqueue_scripts( $hook ) {

		$wps_sfw_screen_ids = wps_sfw_get_page_screen();
		$screen = get_current_screen();

		$wps_sfw_branner_notice = array(
			'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'wps_sfw_nonce' => wp_create_nonce( 'wps-sfw-verify-notice-nonce' ),
		);
		wp_register_script( $this->plugin_name . 'admin-notice', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-sfw-subscription-card-notices.js', array( 'jquery' ), $this->version, false );

		wp_localize_script( $this->plugin_name . 'admin-notice', 'wps_sfw_branner_notice', $wps_sfw_branner_notice );
		wp_enqueue_script( $this->plugin_name . 'admin-notice' );

		if ( isset( $screen->id ) && ( in_array( $screen->id, $wps_sfw_screen_ids ) || 'wp-swings_page_home' == $screen->id || 'woocommerce_page_wc-settings' == $screen->id || 'wps_subscriptions' == $screen->id ) ) {

			if ( ! wps_sfw_check_multistep() ) {

				// Js for the multistep from.
				$script_asset_path = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'build/index-asset.php';
				$script_asset      = file_exists( $script_asset_path )
				? require $script_asset_path
				: array(
					'dependencies' => array(
						'wp-hooks',
						'wp-element',
						'wp-i18n',
						'wc-components',
					),
					'version'      => 'c18eb6767e641a7522507a86d9c71475',
				);
				$script_url        = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'build/index.js';
				wp_register_script(
					'wps-sfw-react-app-block',
					$script_url,
					$script_asset['dependencies'],
					$script_asset['version'],
					true
				);
				wp_enqueue_script( 'wps-sfw-react-app-block' );
				wp_localize_script(
					'wps-sfw-react-app-block',
					'frontend_ajax_object',
					array(
						'ajaxurl'            => admin_url( 'admin-ajax.php' ),
						'wps_sfw_react_nonce' => wp_create_nonce( 'ajax-nonce' ),
						'redirect_url' => admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu' ),
						'disable_track_url' => admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-developer' ),
						'supported_gateway' => wps_sfw_get_subscription_supported_payment_method(),
						'wps_build_in_paypal_setup_url' => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wps_paypal' ),
					)
				);
				return;
			}
			wp_enqueue_script( 'wps-sfw-select2', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/subscriptions-for-woocommerce-select2.js', array( 'jquery' ), time(), false );

			wp_enqueue_script( 'wps-sfw-metarial-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.js', array(), time(), false );
			wp_enqueue_script( 'wps-sfw-metarial-js2', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.js', array(), time(), false );
			wp_enqueue_script( 'wps-sfw-metarial-lite', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.js', array(), time(), false );

			wp_register_script( $this->plugin_name . 'admin-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/subscriptions-for-woocommerce-admin.js', array( 'jquery', 'wps-sfw-select2', 'wps-sfw-metarial-js', 'wps-sfw-metarial-js2', 'wps-sfw-metarial-lite' ), $this->version, false );

			wp_localize_script(
				$this->plugin_name . 'admin-js',
				'sfw_admin_param',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'reloadurl' => admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu' ),
					'sfw_gen_tab_enable' => get_option( 'sfw_radio_switch_demo' ),
					'sfw_auth_nonce'    => wp_create_nonce( 'wps_sfw_admin_nonce' ),
					'empty_fields'    => esc_html__( 'Make Sure, You have filled the Client ID and Client secret keys', 'subscriptions-for-woocommerce' ),
				)
			);

			wp_enqueue_script( $this->plugin_name . 'admin-js' );
		}

		if ( ( isset( $screen->id ) && 'product' == $screen->id ) || 'wps_subscriptions' == $screen->id ) {
			wp_register_script( 'wps-sfw-admin-single-product-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/subscriptions-for-woocommerce-product-edit.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'wps-sfw-admin-single-product-js' );

			$wps_sfw_data = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'reloadurl' => admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu' ),
				'day' => __( 'Days', 'subscriptions-for-woocommerce' ),
				'week' => __( 'Weeks', 'subscriptions-for-woocommerce' ),
				'month' => __( 'Months', 'subscriptions-for-woocommerce' ),
				'year' => __( 'Years', 'subscriptions-for-woocommerce' ),
				'expiry_notice' => __( 'Expiry Interval must be greater than subscription interval', 'subscriptions-for-woocommerce' ),
				'expiry_days_notice' => __( 'Expiry Interval must not be greater than 90 Days', 'subscriptions-for-woocommerce' ),
				'expiry_week_notice' => __( 'Expiry Interval must not be greater than 52 Weeks', 'subscriptions-for-woocommerce' ),
				'expiry_month_notice' => __( 'Expiry Interval must not be greater than 24 Months', 'subscriptions-for-woocommerce' ),
				'expiry_year_notice' => __( 'Expiry Interval must not be greater than 5 Years', 'subscriptions-for-woocommerce' ),
				'trial_days_notice' => __( 'Trial period must not be greater than 90 Days', 'subscriptions-for-woocommerce' ),
				'trial_week_notice' => __( 'Trial period must not be greater than 52 Weeks', 'subscriptions-for-woocommerce' ),
				'trial_month_notice' => __( 'Trial period must not be greater than 24 Months', 'subscriptions-for-woocommerce' ),
				'trial_year_notice' => __( 'Trial period must not be greater than 5 Years', 'subscriptions-for-woocommerce' ),
				'is_pro_active'     => apply_filters( 'wsp_sfw_check_pro_plugin', false ),
				'fist_subscription_box_id' => get_option( 'wps_sfw_first_subscription_box_id', false ),
			);
			wp_localize_script(
				'wps-sfw-admin-single-product-js',
				'sfw_product_param',
				$wps_sfw_data
			);
			wp_enqueue_script( 'jquery-ui-datepicker' );

		}
	}
	/**
	 * Adding settings menu for Subscriptions For Woocommerce.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_options_page() {
		global $submenu;
		$is_home = false;
		if ( empty( $GLOBALS['admin_page_hooks']['wps-plugins'] ) ) {

			add_menu_page( 'WP Swings', 'WP Swings', 'manage_woocommerce', 'wps-plugins', array( $this, 'wps_plugins_listing_page' ), SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/wpswings_logo.png', 15 );
			// Add menus.
			if ( wps_sfw_check_multistep() ) {
				add_submenu_page( 'wps-plugins', 'Home', 'Home', 'manage_woocommerce', 'home', array( $this, 'wps_sfw_welcome_callback_function' ) );
			}
			$sfw_menus = apply_filters( 'wps_add_plugins_menus_array', array() );
			if ( is_array( $sfw_menus ) && ! empty( $sfw_menus ) ) {
				foreach ( $sfw_menus as $sfw_key => $sfw_value ) {
					add_submenu_page( 'wps-plugins', $sfw_value['name'], $sfw_value['name'], 'manage_woocommerce', $sfw_value['menu_link'], array( $sfw_value['instance'], $sfw_value['function'] ) );
				}
				$is_home = false;
			}
		} elseif ( ! empty( $submenu['wps-plugins'] ) ) {
			foreach ( $submenu['wps-plugins'] as $key => $value ) {
				if ( 'Home' === $value[0] ) {
					$is_home = true;
				}
			}
			if ( ! $is_home ) {
				if ( wps_sfw_check_multistep() ) {
					add_submenu_page( 'wps-plugins', 'Home', 'Home', 'manage_woocommerce', 'home', array( $this, 'wps_sfw_welcome_callback_function' ), 1 );
				}
			}
		}
		add_submenu_page( 'woocommerce', __( 'Wps Subscriptions', 'subscriptions-for-woocommerce' ), __( 'Wps Subscriptions', 'subscriptions-for-woocommerce' ), 'manage_woocommerce', 'subscriptions-for-woocommerce', array( $this, 'wps_sfw_addsubmenu_woocommerce' ) );
	}

	/**
	 * This function is used to add submenu of subscription inside woocommerce.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function wps_sfw_addsubmenu_woocommerce() {
		$permalink = admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table' );
		wp_safe_redirect( $permalink );
		exit;
	}

	/**
	 *
	 * Adding the default menu into the wordpress menu
	 *
	 * @name wpswings_callback_function
	 * @since 1.0.0
	 */
	public function wps_sfw_welcome_callback_function() {
		include SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/subscriptions-for-woocommerce-welcome.php';
	}

	/**
	 * Removing default submenu of parent menu in backend dashboard
	 *
	 * @since   1.0.0
	 */
	public function wps_sfw_remove_default_submenu() {
		global $submenu;
		if ( is_array( $submenu ) && array_key_exists( 'wps-plugins', $submenu ) ) {
			if ( isset( $submenu['wps-plugins'][0] ) ) {
				unset( $submenu['wps-plugins'][0] );
			}
		}
	}


	/**
	 * Subscriptions For Woocommerce wps_sfw_admin_submenu_page.
	 *
	 * @since 1.0.0
	 * @param array $menus Marketplace menus.
	 */
	public function wps_sfw_admin_submenu_page( $menus = array() ) {
		$menus[] = array(
			'name'            => __( 'Subscriptions For WooCommerce', 'subscriptions-for-woocommerce' ),
			'slug'            => 'subscriptions_for_woocommerce_menu',
			'menu_link'       => 'subscriptions_for_woocommerce_menu',
			'instance'        => $this,
			'function'        => 'wps_sfw_options_menu_html',
		);
		return $menus;
	}


	/**
	 * Subscriptions For Woocommerce wps_plugins_listing_page.
	 *
	 * @since 1.0.0
	 */
	public function wps_plugins_listing_page() {
		// Add menus.
		$active_marketplaces = apply_filters( 'wps_add_plugins_menus_array', array() );
		if ( is_array( $active_marketplaces ) && ! empty( $active_marketplaces ) ) {
			require SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/welcome.php';
		}
	}

	/**
	 * Subscriptions For Woocommerce admin menu page.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_options_menu_html() {

		include_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/subscriptions-for-woocommerce-admin-dashboard.php';
	}


	/**
	 * Subscriptions For Woocommerce admin menu page.
	 *
	 * @since    1.0.0
	 * @param array $sfw_settings_general Settings fields.
	 */
	public function wps_sfw_admin_general_settings_page( $sfw_settings_general ) {

		$sfw_settings_general = array(
			array(
				'title' => __( 'Enable/Disable Subscription', 'subscriptions-for-woocommerce' ),
				'type'  => 'checkbox',
				'description'  => __( 'Check this box to enable the subscription.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wps_sfw_enable_plugin',
				'class' => 'sfw-checkbox-class',
				'value' => 'on',
				'checked' => ( 'on' === get_option( 'wps_sfw_enable_plugin', '' ) ? 'on' : 'off' ),
			),
			array(
				'title' => __( 'Add to cart text', 'subscriptions-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Use this option to change add to cart button text.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wps_sfw_add_to_cart_text',
				'value' => get_option( 'wps_sfw_add_to_cart_text', '' ),
				'class' => 'sfw-text-class',
				'placeholder' => __( 'Add to cart button text', 'subscriptions-for-woocommerce' ),
			),
			array(
				'title' => __( 'Place order text', 'subscriptions-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Use this option to change place order button text.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wps_sfw_place_order_button_text',
				'value' => get_option( 'wps_sfw_place_order_button_text', '' ),
				'class' => 'sfw-text-class',
				'placeholder' => __( 'Place order button text', 'subscriptions-for-woocommerce' ),
			),
			array(
				'title' => __( 'Allow Customer to cancel Subscription', 'subscriptions-for-woocommerce' ),
				'type'  => 'checkbox',
				'description'  => __( 'Enable this option to allow the customer to cancel the subscription.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wps_sfw_cancel_subscription_for_customer',
				'checked' => ( 'on' === get_option( 'wps_sfw_cancel_subscription_for_customer', '' ) ? 'on' : 'off' ),
				'value' => 'on',
				'class' => 'sfw-checkbox-class',
			),
			array(
				'title' => __( 'Enable Log', 'subscriptions-for-woocommerce' ),
				'type'  => 'checkbox',
				'description'  => __( 'Enable Log.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wps_sfw_enable_subscription_log',
				'value' => 'on',
				'checked' => ( 'on' === get_option( 'wps_sfw_enable_subscription_log', '' ) ? 'on' : 'off' ),
				'class' => 'sfw-checkbox-class',
			),
			array(
				'title' => __( 'Enable Paypal Standard', 'subscriptions-for-woocommerce' ),
				'type'  => 'checkbox',
				/* translators: %1s: links */
				'description'  => sprintf( __( 'You will see the %1s in the Woocommerce Payments section.', 'subscriptions-for-woocommerce' ), '<a target="__blank" href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paypal' ) . '">' . __( 'Paypal Standard Gateway', 'subscriptions-for-woocommerce' ) . '</a>' ) . '<br/>' . sprintf( __( 'Please click %1s to know that, How to get the API Credentials for the setup', 'subscriptions-for-woocommerce' ), '<a target="__blank" href="https://developer.paypal.com/api/nvp-soap/apiCredentials/#link-apisignatures" />' . __( 'Here', 'subscriptions-for-woocommerce' ) . '</a>' ),
				'id'    => 'wps_sfw_enable_paypal_standard',
				'value' => 'on',
				'checked' => ( 'on' === get_option( 'wps_sfw_enable_paypal_standard', '' ) ? 'on' : 'off' ),
				'class' => 'sfw-checkbox-class',
			),
			array(
				'type'  => 'button',
				'id'    => 'wps_sfw_save_general_settings',
				'button_text' => __( 'Save Settings', 'subscriptions-for-woocommerce' ),
				'class' => 'sfw-button-class',
			),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$woocommerce_version = WC()->version;
			if ( version_compare( $woocommerce_version, '8.8.3', '>' ) ) {
				unset( $sfw_settings_general[5] );
			}
		}
		// Add general settings.
		return apply_filters( 'wps_sfw_add_general_settings_fields', $sfw_settings_general );
	}

	/**
	 * Api settings fields.
	 *
	 * @since    1.0.0
	 * @param array $wsp_api_settings Api fields.
	 */
	public function wps_sfw_admin_api_settings_fields( $wsp_api_settings ) {

		$wsp_api_secret_key = get_option( 'wsp_api_secret_key', '' );

		$wps_wsp_btn_txt = ! empty( $wsp_api_secret_key ) ? __( 'Save Settings', 'subscriptions-for-woocommerce' ) : __( 'Generate & Save', 'subscriptions-for-woocommerce' );

		$wsp_api_settings = array(
			array(
				'title' => __( 'Enable API Features', 'subscriptions-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Enable this to allow API functionality to view subscription.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wsp_enable_api_features',
				'value' => get_option( 'wsp_enable_api_features' ),
				'class' => 'wsp-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'subscriptions-for-woocommerce' ),
					'no' => __( 'NO', 'subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'API secret key', 'subscriptions-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'API secret key.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wsp_api_secret_key',
				'value' => get_option( 'wsp_api_secret_key', '' ),
				'class' => 'wsp-text-class',
				'placeholder' => __( 'API secret key', 'subscriptions-for-woocommerce' ),
			),
		);

		$wsp_api_settings[] = array(
			'type'  => 'button',
			'id'    => 'wps_sfw_save_api_settings',
			'button_text' => $wps_wsp_btn_txt,
			'class' => 'sfw-button-class',
		);
		$wsp_api_settings = array_merge( $wsp_api_settings );

		return $wsp_api_settings;
	}


	/**
	 * Subscriptions For Woocommerce save tab settings.
	 *
	 * @name sfw_admin_save_tab_settings.
	 * @since 1.0.0
	 */
	public function sfw_admin_save_tab_settings() {
		global $sfw_wps_sfw_obj;
		global $wps_sfw_notices;
		if ( isset( $_POST['wps_sfw_save_general_settings'] ) && isset( $_POST['wps-sfw-general-nonce-field'] ) ) {
			$wps_sfw_geberal_nonce = sanitize_text_field( wp_unslash( $_POST['wps-sfw-general-nonce-field'] ) );
			if ( wp_verify_nonce( $wps_sfw_geberal_nonce, 'wps-sfw-general-nonce' ) ) {
				$wps_sfw_gen_flag = false;
				// General settings.
				$sfw_genaral_settings = apply_filters( 'wps_sfw_general_settings_array', array() );
				$sfw_button_index = array_search( 'submit', array_column( $sfw_genaral_settings, 'type' ) );
				if ( isset( $sfw_button_index ) && ( null == $sfw_button_index || '' == $sfw_button_index ) ) {
					$sfw_button_index = array_search( 'button', array_column( $sfw_genaral_settings, 'type' ) );
				}
				if ( isset( $sfw_button_index ) && '' !== $sfw_button_index ) {

					unset( $sfw_genaral_settings[ $sfw_button_index ] );
					if ( is_array( $sfw_genaral_settings ) && ! empty( $sfw_genaral_settings ) ) {
						foreach ( $sfw_genaral_settings as $sfw_genaral_setting ) {
							if ( isset( $sfw_genaral_setting['id'] ) && '' !== $sfw_genaral_setting['id'] ) {

								if ( isset( $_POST[ $sfw_genaral_setting['id'] ] ) && ! empty( $_POST[ $sfw_genaral_setting['id'] ] ) ) {

									$posted_value = sanitize_text_field( wp_unslash( $_POST[ $sfw_genaral_setting['id'] ] ) );
									update_option( $sfw_genaral_setting['id'], $posted_value );
								} else {
									update_option( $sfw_genaral_setting['id'], '' );
								}
							} else {
								$wps_sfw_gen_flag = true;
							}
						}
					}
					if ( $wps_sfw_gen_flag ) {
						$wps_sfw_error_text = esc_html__( 'Id of some field is missing', 'subscriptions-for-woocommerce' );
						$sfw_wps_sfw_obj->wps_sfw_plug_admin_notice( $wps_sfw_error_text, 'error' );
					} else {
						$wps_sfw_notices = true;
					}
				}
			}
		} elseif ( isset( $_POST['wps_sfw_save_api_settings'] ) && isset( $_POST['wps-sfw-api-nonce-field'] ) ) {
			if ( ! isset( $_POST['wps-sfw-api-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wps-sfw-api-nonce-field'] ) ), 'wps-sfw-api-nonce' ) ) {
				return;
			}
			$wps_sfw_gen_flag = false;
			$wsp_api_settings = apply_filters( 'wps_sfw_api_settings_array', array() );
			$wsp_button_index = array_search( 'submit', array_column( $wsp_api_settings, 'type' ) );
			if ( isset( $wsp_button_index ) && ( null == $wsp_button_index || '' == $wsp_button_index ) ) {
				$wsp_button_index = array_search( 'button', array_column( $wsp_api_settings, 'type' ) );
			}
			if ( isset( $wsp_button_index ) && '' !== $wsp_button_index ) {
				unset( $wsp_api_settings[ $wsp_button_index ] );
				if ( is_array( $wsp_api_settings ) && ! empty( $wsp_api_settings ) ) {
					foreach ( $wsp_api_settings as $wsp_api_setting ) {
						if ( isset( $wsp_api_setting['id'] ) && '' !== $wsp_api_setting['id'] ) {
							if ( 'wsp_api_secret_key' == $wsp_api_setting['id'] && empty( $_POST[ $wsp_api_setting['id'] ] ) ) {
								$_POST[ $wsp_api_setting['id'] ] = 'wps_' . wc_rand_hash();
							}
							if ( isset( $_POST[ $wsp_api_setting['id'] ] ) ) {
								$posted_value = sanitize_text_field( wp_unslash( $_POST[ $wsp_api_setting['id'] ] ) );
								update_option( $wsp_api_setting['id'], $posted_value );
							} else {
								update_option( $wsp_api_setting['id'], '' );
							}
						} else {
							$wps_sfw_gen_flag = true;
						}
					}
				}
				if ( $wps_sfw_gen_flag ) {
					$wps_sfw_error_text = esc_html__( 'Id of some field is missing', 'subscriptions-for-woocommerce' );
					$sfw_wps_sfw_obj->wps_sfw_plug_admin_notice( $wps_sfw_error_text, 'error' );
				} else {
					$wps_sfw_notices = true;
				}
			}
		} elseif ( isset( $_POST['wps_sfw_save_subscription_box_settings'] ) && isset( $_POST['wps-sfw-subscription-box-nonce-field'] ) ) {
			$wps_sfw_subscription_box_nonce = sanitize_text_field( wp_unslash( $_POST['wps-sfw-subscription-box-nonce-field'] ) );
			if ( wp_verify_nonce( $wps_sfw_subscription_box_nonce, 'wps-sfw-subscription-box-nonce' ) ) {
				$wps_sfw_sub_box_flag = false;
				// General settings.
				$sfw_subscription_box_settings = apply_filters( 'wps_sfw_subscription_box_settings_array', array() );

				$sfw_button_index = array_search( 'submit', array_column( $sfw_subscription_box_settings, 'type' ) );
				if ( isset( $sfw_button_index ) && ( null == $sfw_button_index || '' == $sfw_button_index ) ) {
					$sfw_button_index = array_search( 'button', array_column( $sfw_subscription_box_settings, 'type' ) );
				}
				if ( isset( $sfw_button_index ) && '' !== $sfw_button_index ) {

					unset( $sfw_subscription_box_settings[ $sfw_button_index ] );
					if ( is_array( $sfw_subscription_box_settings ) && ! empty( $sfw_subscription_box_settings ) ) {
						foreach ( $sfw_subscription_box_settings as $sfw_subscription_box_setting ) {
							if ( isset( $sfw_subscription_box_setting['id'] ) && '' !== $sfw_subscription_box_setting['id'] ) {

								if ( isset( $_POST[ $sfw_subscription_box_setting['id'] ] ) && ! empty( $_POST[ $sfw_subscription_box_setting['id'] ] ) ) {

									$posted_value = sanitize_text_field( wp_unslash( $_POST[ $sfw_subscription_box_setting['id'] ] ) );
									update_option( $sfw_subscription_box_setting['id'], $posted_value );
								} else {
									update_option( $sfw_subscription_box_setting['id'], '' );
								}
							} else {

								$wps_sfw_sub_box_flag = true;
							}
						}
					}

					if ( $wps_sfw_sub_box_flag ) {
						$wps_sfw_error_text = esc_html__( 'Id of some field is missing', 'subscriptions-for-woocommerce' );
						$sfw_wps_sfw_obj->wps_sfw_plug_admin_notice( $wps_sfw_error_text, 'error' );
					} else {
						$wps_sfw_notices = true;
					}
				}
			}
		}
		if ( isset( $_POST['sfw_track_button'] ) && isset( $_POST['wps-sfw-general-nonce-field'] ) ) {
			$wps_sfw_geberal_nonce = sanitize_text_field( wp_unslash( $_POST['wps-sfw-general-nonce-field'] ) );
			if ( wp_verify_nonce( $wps_sfw_geberal_nonce, 'wps-sfw-general-nonce' ) ) {

				if ( isset( $_POST['wps_sfw_enable_tracking'] ) && '' !== $_POST['wps_sfw_enable_tracking'] ) {
					$posted_value = sanitize_text_field( wp_unslash( $_POST['wps_sfw_enable_tracking'] ) );
					update_option( 'wps_sfw_enable_tracking', $posted_value );
				} else {
					update_option( 'wps_sfw_enable_tracking', '' );
				}
				$wps_sfw_notices = true;

			}
		}
	}

	/**
	 * This function is used Subscription type checkobox for simple products
	 *
	 * @name wps_sfw_create_subscription_product_type
	 * @since    1.0.0
	 * @param    Array $products_type Products type.
	 * @return   Array  $products_type.
	 */
	public function wps_sfw_create_subscription_product_type( $products_type ) {
		$products_type['wps_sfw_product'] = array(
			'id'            => '_wps_sfw_product',
			'wrapper_class' => 'show_if_simple show_if_mwb_booking',
			'label'         => __( 'Subscription', 'subscriptions-for-woocommerce' ),
			'description'   => __( 'This is the Subscriptions type product.', 'subscriptions-for-woocommerce' ),
			'default'       => 'no',
		);
		return $products_type;
	}


	/**
	 * This function is used to add subscription settings for product.
	 *
	 * @name wps_sfw_custom_product_tab_for_subscription
	 * @since    1.0.0
	 * @param    Array $tabs Products tabs array.
	 * @return   Array  $tabs
	 */
	public function wps_sfw_custom_product_tab_for_subscription( $tabs ) {
		$tabs['wps_sfw_product'] = array(
			'label'    => __( 'Subscription Settings', 'subscriptions-for-woocommerce' ),
			'target'   => 'wps_sfw_product_target_section',
			// Add class for product.
			'class'    => apply_filters( 'wps_swf_settings_tabs_class', array() ),
			'priority' => 80,
		);
		// Add tb for product.
		return apply_filters( 'wps_swf_settings_tabs', $tabs );
	}



	/**
	 * This function is used to add custom fileds for subscription products.
	 *
	 * @name wps_sfw_custom_product_fields_for_subscription
	 * @since    1.0.0
	 */
	public function wps_sfw_custom_product_fields_for_subscription() {
		global $post;
		$post_id = $post->ID;
		$product = wc_get_product( $post_id );

		$wps_sfw_subscription_number = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_number', true );
		if ( empty( $wps_sfw_subscription_number ) ) {
			$wps_sfw_subscription_number = 1;
		}
		$wps_sfw_subscription_interval = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_interval', true );
		if ( empty( $wps_sfw_subscription_interval ) ) {
			$wps_sfw_subscription_interval = 'day';
		}

		$wps_sfw_subscription_expiry_number = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_expiry_number', true );
		$wps_sfw_subscription_expiry_interval = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_expiry_interval', true );
		$wps_sfw_subscription_initial_signup_price = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_initial_signup_price', true );
		$wps_sfw_subscription_free_trial_number = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_free_trial_number', true );
		$wps_sfw_subscription_free_trial_interval = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_free_trial_interval', true );
		?>
		<div id="wps_sfw_product_target_section" class="panel woocommerce_options_panel hidden">

			<p class="form-field wps_sfw_subscription_number_field ">
				<label for="wps_sfw_subscription_number">
				<?php esc_html_e( 'Subscriptions Per Interval', 'subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" required name="wps_sfw_subscription_number" id="wps_sfw_subscription_number" value="<?php echo esc_attr( $wps_sfw_subscription_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription interval', 'subscriptions-for-woocommerce' ); ?>"> 
				<select id="wps_sfw_subscription_interval" name="wps_sfw_subscription_interval" class="wps_sfw_subscription_interval" >
					<?php foreach ( wps_sfw_subscription_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $wps_sfw_subscription_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the subscriptions time interval for the product "for example 10 days"', 'subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<p class="form-field wps_sfw_subscription_expiry_field ">
				<label for="wps_sfw_subscription_expiry_number">
				<?php esc_html_e( 'Subscriptions Expiry Interval', 'subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" name="wps_sfw_subscription_expiry_number" id="wps_sfw_subscription_expiry_number" value="<?php echo esc_attr( $wps_sfw_subscription_expiry_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription expiry', 'subscriptions-for-woocommerce' ); ?>"> 
				<select id="wps_sfw_subscription_expiry_interval" name="wps_sfw_subscription_expiry_interval" class="wps_sfw_subscription_expiry_interval" >
					<?php foreach ( wps_sfw_subscription_expiry_period( $wps_sfw_subscription_interval ) as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $wps_sfw_subscription_expiry_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the subscriptions expiry time interval for the product "leave empty for unlimited"', 'subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<p class="form-field wps_sfw_subscription_initial_signup_field ">
				<label for="wps_sfw_subscription_initial_signup_price">
				<?php
				esc_html_e( 'Initial Signup fee', 'subscriptions-for-woocommerce' );
				echo esc_html( '(' . get_woocommerce_currency_symbol() . ')' );
				?>
				</label>
				<input type="number" class="short wc_input_price"  min="0" step="any" name="wps_sfw_subscription_initial_signup_price" id="wps_sfw_subscription_initial_signup_price" value="<?php echo esc_attr( $wps_sfw_subscription_initial_signup_price ); ?>" placeholder="<?php esc_html_e( 'Enter signup fee', 'subscriptions-for-woocommerce' ); ?>"> 
				
			<?php
				$description_text = __( 'Choose the subscriptions initial fee for the product "leave empty for no initial fee"', 'subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<p class="form-field wps_sfw_subscription_free_trial_field">
				<label for="wps_sfw_subscription_free_trial_number">
				<?php esc_html_e( 'Free trial interval', 'subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" name="wps_sfw_subscription_free_trial_number" id="wps_sfw_subscription_free_trial_number" value="<?php echo esc_attr( $wps_sfw_subscription_free_trial_number ); ?>" placeholder="<?php esc_html_e( 'Enter free trial interval', 'subscriptions-for-woocommerce' ); ?>"> 
				<select id="wps_sfw_subscription_free_trial_interval" name="wps_sfw_subscription_free_trial_interval" class="wps_sfw_subscription_free_trial_interval" >
					<?php foreach ( wps_sfw_subscription_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $wps_sfw_subscription_free_trial_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the trial period for subscription "leave empty for no trial period"', 'subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<?php
			wp_nonce_field( 'wps_sfw_edit_nonce', 'wps_sfw_edit_nonce_filed' );
			// Add filed on product edit page.
			do_action( 'wps_sfw_product_edit_field', $post_id );
			if ( function_exists( 'learn_press_get_all_courses' ) ) {
				$courses       = learn_press_get_all_courses();
				$saved_courses = get_post_meta( $post_id, 'wps_learnpress_course', true ) ? get_post_meta( $post_id, 'wps_learnpress_course', true ) : array();
				?>
				<p class="form-field wps_learnpress_course_field">
					<?php
					if ( ! empty( $courses ) && is_array( $courses ) ) {
						?>
						<label for="wps_learnpress_course">
							<?php esc_html_e( 'Attach LearnPress Courses', 'subscriptions-for-woocommerce' ); ?>
						</label>
						<select id="wps_learnpress_course" class="wps_learnpress_course" name="wps_learnpress_course[]" multiple>
						<?php
						foreach ( $courses as $course_id ) {
							$course = learn_press_get_course( $course_id );
							?>
							<option value="<?php echo esc_attr( $course_id ); ?>" <?php selected( true, in_array( $course_id, $saved_courses ) ); ?> ><?php echo esc_attr( $course->get_title() ); ?></option>
							<?php
						}
						?>
						</select>
						<?php
					}
					?>
				</p>
				<?php
			}
			?>
		</div>
		<?php
	}


	/**
	 * This function is used to save custom fields for subscription products.
	 *
	 * @name wps_sfw_save_custom_product_fields_data_for_subscription
	 * @since    1.0.0
	 * @param    int    $post_id Post ID.
	 * @param    object $post post.
	 */
	public function wps_sfw_save_custom_product_fields_data_for_subscription( $post_id, $post ) {

		if ( ! isset( $_POST['wps_sfw_edit_nonce_filed'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wps_sfw_edit_nonce_filed'] ) ), 'wps_sfw_edit_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}
		$wps_sfw_product = isset( $_POST['_wps_sfw_product'] ) ? 'yes' : 'no';
		wps_sfw_update_meta_data( $post_id, '_wps_sfw_product', $wps_sfw_product );
		if ( isset( $_POST['_wps_sfw_product'] ) && ! empty( $_POST['_wps_sfw_product'] ) ) {

			$wps_sfw_subscription_number = isset( $_POST['wps_sfw_subscription_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_number'] ) ) : '';
			$wps_sfw_subscription_interval = isset( $_POST['wps_sfw_subscription_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_interval'] ) ) : '';
			$wps_sfw_subscription_expiry_number = isset( $_POST['wps_sfw_subscription_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_expiry_number'] ) ) : '';
			$wps_sfw_subscription_expiry_interval = isset( $_POST['wps_sfw_subscription_expiry_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_expiry_interval'] ) ) : '';
			$wps_sfw_subscription_initial_signup_price = isset( $_POST['wps_sfw_subscription_initial_signup_price'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_initial_signup_price'] ) ) : '';
			$wps_sfw_subscription_free_trial_number = isset( $_POST['wps_sfw_subscription_free_trial_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_free_trial_number'] ) ) : '';
			$wps_sfw_subscription_free_trial_interval = isset( $_POST['wps_sfw_subscription_free_trial_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_free_trial_interval'] ) ) : '';

			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_number', $wps_sfw_subscription_number );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_interval', $wps_sfw_subscription_interval );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_expiry_number', $wps_sfw_subscription_expiry_number );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_expiry_interval', $wps_sfw_subscription_expiry_interval );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_initial_signup_price', $wps_sfw_subscription_initial_signup_price );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_free_trial_number', $wps_sfw_subscription_free_trial_number );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_free_trial_interval', $wps_sfw_subscription_free_trial_interval );

			$learnpress_courses = isset( $_POST['wps_learnpress_course'] ) ? wp_unslash( $_POST['wps_learnpress_course'] ) : ''; //phpcs:ignore
			if ( is_array( $learnpress_courses ) ) {
				$learnpress_courses = array_map( 'sanitize_text_field', $learnpress_courses );
			} else {
				$learnpress_courses = sanitize_text_field( $learnpress_courses );
			}
			$all_attached_courses = get_option( 'wps_learnpress_course', array() );
			$all_attached_courses[$post_id] = $learnpress_courses;
			update_option( 'wps_learnpress_course', $all_attached_courses );
			wps_sfw_update_meta_data( $post_id, 'wps_learnpress_course', $learnpress_courses );

			do_action( 'wps_sfw_save_simple_subscription_field', $post_id, $_POST );
		}
	}

	/**
	 * This function is used to cancel susbcription.
	 *
	 * @name wps_sfw_admin_cancel_susbcription
	 * @since 1.0.0
	 */
	public function wps_sfw_admin_cancel_susbcription() {

		if ( isset( $_GET['wps_subscription_status_admin'] ) && isset( $_GET['wps_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$wps_status   = sanitize_text_field( wp_unslash( $_GET['wps_subscription_status_admin'] ) );
			$wps_subscription_id = sanitize_text_field( wp_unslash( $_GET['wps_subscription_id'] ) );
			if ( wps_sfw_check_valid_subscription( $wps_subscription_id ) ) {
				// Cancel subscription.
				$wps_wsp_payment_type = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_wsp_payment_type', true );
				if ( 'wps_wsp_manual_method' == $wps_wsp_payment_type ) {
					wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
					wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_cancelled_by', 'by_admin' );
					wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_cancelled_date', time() );

				} else {

					do_action( 'wps_sfw_subscription_cancel', $wps_subscription_id, 'Cancel' );
					wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_cancelled_by', 'by_admin' );
					wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_cancelled_date', time() );
				}
				$redirect_url = admin_url() . 'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table';
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * This function is used to custom order status for susbcription.
	 *
	 * @name wps_sfw_register_new_order_statuses
	 * @param array $order_status order_status.
	 * @since 1.0.0
	 */
	public function wps_sfw_register_new_order_statuses( $order_status ) {

		$order_status['wc-wps_renewal'] = array(
			'label'                     => _x( 'Wps Renewal', 'Order status', 'subscriptions-for-woocommerce' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count'               => _n_noop( 'Wps Renewal <span class="count">(%s)</span>', 'Wps Renewal <span class="count">(%s)</span>', 'subscriptions-for-woocommerce' ),
		);
		return $order_status;
	}

	/**
	 * This function is used to custom order status for susbcription.
	 *
	 * @name wps_sfw_new_wc_order_statuses.
	 * @since 1.0.0
	 * @param array $order_statuses order_statuses.
	 */
	public function wps_sfw_new_wc_order_statuses( $order_statuses ) {
		$order_statuses['wc-wps_renewal'] = _x( 'Wps Renewal', 'Order status', 'subscriptions-for-woocommerce' );

		return $order_statuses;
	}

	/**
	 * This function is used to custom field compatibility with WPML.
	 *
	 * @name wps_sfw_add_lock_custom_fields_ids.
	 * @since 1.0.3
	 * @param array $ids ids.
	 */
	public function wps_sfw_add_lock_custom_fields_ids( $ids ) {

		$ids[] = '_wps_sfw_product';
		$ids[] = 'wps_sfw_subscription_number';
		$ids[] = 'wps_sfw_subscription_interval';
		$ids[] = 'wps_sfw_subscription_expiry_number';
		$ids[] = 'wps_sfw_subscription_expiry_interval';
		$ids[] = 'wps_sfw_subscription_initial_signup_price';
		$ids[] = 'wps_sfw_subscription_free_trial_number';
		$ids[] = 'wps_sfw_subscription_free_trial_interval';

		return apply_filters( 'wps_sfw_add_lock_fields_ids_pro', $ids );
	}

	/**
	 * Update the option for settings from the multistep form.
	 *
	 * @name wps_sfw_save_settings_filter
	 * @since 1.0.0
	 */
	public function wps_sfw_save_settings_filter() {

		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$term_accpted = ! empty( $_POST['consetCheck'] ) ? sanitize_text_field( wp_unslash( $_POST['consetCheck'] ) ) : ' ';
		if ( ! empty( $term_accpted ) && 'yes' == $term_accpted ) {
			update_option( 'wps_sfw_enable_tracking', 'on' );
		}
		// settings fields.
		$enable_plugin = ! empty( $_POST['EnablePlugin'] ) ? sanitize_text_field( wp_unslash( $_POST['EnablePlugin'] ) ) : '';
		$add_to_cart_text = ! empty( $_POST['AddToCartText'] ) ? sanitize_text_field( wp_unslash( $_POST['AddToCartText'] ) ) : '';
		$place_order_text = ! empty( $_POST['PlaceOrderText'] ) ? sanitize_text_field( wp_unslash( $_POST['PlaceOrderText'] ) ) : '';

		$product_name = ! empty( $_POST['ProductName'] ) ? sanitize_text_field( wp_unslash( $_POST['ProductName'] ) ) : 'Subscription';
		$product_description = ! empty( $_POST['ProductDescription'] ) ? sanitize_text_field( wp_unslash( $_POST['ProductDescription'] ) ) : 'This is Subscription';
		$short_description = ! empty( $_POST['ProductShortDescription'] ) ? sanitize_text_field( wp_unslash( $_POST['ProductShortDescription'] ) ) : 'This is Subscription Product';

		$product_price = ! empty( $_POST['ProductPrice'] ) ? sanitize_text_field( wp_unslash( $_POST['ProductPrice'] ) ) : '';

		$subscription_number = ! empty( $_POST['SubscriptionNumber'] ) ? sanitize_text_field( wp_unslash( $_POST['SubscriptionNumber'] ) ) : '';

		$subscription_interval = ! empty( $_POST['SubscriptionInterval'] ) ? sanitize_text_field( wp_unslash( $_POST['SubscriptionInterval'] ) ) : '';

		// Update settings.
		if ( 'true' == $enable_plugin ) {
			update_option( 'wps_sfw_enable_plugin ', 'on' );
			update_option( 'wps_sfw_add_to_cart_text ', $add_to_cart_text );
			update_option( 'wps_sfw_place_order_button_text ', $place_order_text );
		}
		$allready_created = get_option( 'wps_sfw_multistep_product_create_done', 'no' );
		// Create products.
		if ( $enable_plugin && 'yes' !== $allready_created ) {
			$post_id = wp_insert_post(
				array(
					'post_title' => $product_name,
					'post_type' => 'product',
					'post_content' => $product_description,
					'post_excerpt' => $short_description,
					'post_status' => 'publish',
				)
			);

			wp_set_object_terms( $post_id, 'simple', 'product_type' );
			wps_sfw_update_meta_data( $post_id, '_visibility', 'visible' );
			wps_sfw_update_meta_data( $post_id, '_stock_status', 'instock' );

			wps_sfw_update_meta_data( $post_id, '_wps_sfw_product', 'yes' );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_number', $subscription_number );

			wps_sfw_update_meta_data( $post_id, '_regular_price', $product_price );
			wps_sfw_update_meta_data( $post_id, '_sale_price', '' );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_interval', $subscription_interval );
			wps_sfw_update_meta_data( $post_id, '_price', $product_price );
			$product = wc_get_product( $post_id );

			$product->save();
			update_option( 'wps_sfw_multistep_product_create_done', 'yes' );
		}

		if ( isset( $_POST['EnableWpsPaypal'] ) ) {
			$wps_paypal_settings = get_option( 'woocommerce_wps_paypal_settings', array() );
			$wps_paypal_settings['enabled']  = ! empty( $_POST['EnableWpsPaypal'] ) ? 'yes' : 'no';
			$wps_paypal_settings['testmode'] = ! empty( $_POST['EnableWpsPaypalTestmode'] ) ? 'yes' : 'no';

			$wps_paypal_settings['client_id']     = ! empty( $_POST['WpsPaypalClientId'] ) ? sanitize_text_field( wp_unslash( $_POST['WpsPaypalClientId'] ) ) : '';
			$wps_paypal_settings['client_secret'] = ! empty( $_POST['WpsPaypalClientSecret'] ) ? sanitize_text_field( wp_unslash( $_POST['WpsPaypalClientSecret'] ) ) : '';

			update_option( 'woocommerce_wps_paypal_settings', $wps_paypal_settings );
		}
		update_option( 'wps_sfw_multistep_done', 'yes' );

		wp_send_json( 'yes' );
	}

	/**
	 * Update the option for settings from the multistep form.
	 *
	 * @name wps_sfw_save_settings_filter
	 * @since 1.0.0
	 */
	public function wps_sfw_install_plugin_configuration() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$wps_plugin_name = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		$response = false;
		if ( ! empty( $wps_plugin_name ) ) {
			$wps_plugin_file_path = $wps_plugin_name . '/' . $wps_plugin_name . '.php';

			if ( file_exists( WP_PLUGIN_DIR . '/' . $wps_plugin_file_path ) && ! is_plugin_active( $wps_plugin_file_path ) ) {
				activate_plugin( $wps_plugin_file_path );
				$response = true;
			} else {

				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

				$wps_plugin_api    = plugins_api(
					'plugin_information',
					array(
						'slug' => $wps_plugin_name,
						'fields' => array( 'sections' => false ),
					)
				);
				if ( isset( $wps_plugin_api->download_link ) ) {
					$wps_ajax_obj = new WP_Ajax_Upgrader_Skin();
					$wps_obj = new Plugin_Upgrader( $wps_ajax_obj );
					$wps_install = $wps_obj->install( $wps_plugin_api->download_link );
					activate_plugin( $wps_plugin_file_path );
					$response = true;
				}
			}
		}
		wp_send_json( $response );
	}

	/**
	 * Developer_admin_hooks_listing
	 *
	 * @name wps_developer_admin_hooks_listing
	 */
	public function wps_developer_admin_hooks_listing() {
		$admin_hooks = array();
		$val         = self::wps_developer_hooks_function( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/' );
		if ( ! empty( $val['hooks'] ) ) {
			$admin_hooks[] = $val['hooks'];
			unset( $val['hooks'] );
		}
		$data = array();
		foreach ( $val['files'] as $v ) {
			if ( 'css' !== $v && 'js' !== $v && 'images' !== $v ) {
				$helo = self::wps_developer_hooks_function( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/' . $v . '/' );
				if ( ! empty( $helo['hooks'] ) ) {
					$admin_hooks[] = $helo['hooks'];
					unset( $helo['hooks'] );
				}
				if ( ! empty( $helo ) ) {
					$data[] = $helo;
				}
			}
		}

		return $admin_hooks;
	}

	/**
	 * Developer_public_hooks_listing
	 */
	public function wps_developer_public_hooks_listing() {

		$public_hooks = array();
		$val          = self::wps_developer_hooks_function( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/' );

		if ( ! empty( $val['hooks'] ) ) {
			$public_hooks[] = $val['hooks'];
			unset( $val['hooks'] );
		}
		$data = array();
		foreach ( $val['files'] as $v ) {
			if ( 'css' !== $v && 'js' !== $v && 'images' !== $v ) {
				$helo = self::wps_developer_hooks_function( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/' . $v . '/' );
				if ( ! empty( $helo['hooks'] ) ) {
					$public_hooks[] = $helo['hooks'];
					unset( $helo['hooks'] );
				}
				if ( ! empty( $helo ) ) {
					$data[] = $helo;
				}
			}
		}
		return $public_hooks;
	}

	/**
	 * Developer_hooks_function.
	 *
	 * @name wps_developer_hooks_function.
	 * @param string $path Path of the file.
	 */
	public function wps_developer_hooks_function( $path ) {
		$all_hooks = array();
		$scan      = scandir( $path );
		$response  = array();
		foreach ( $scan as $file ) {
			if ( strpos( $file, '.php' ) ) {
				$myfile = file( $path . $file );
				foreach ( $myfile as $key => $lines ) {
					if ( preg_match( '/do_action/i', $lines ) && ! strpos( $lines, 'str_replace' ) && ! strpos( $lines, 'preg_match' ) ) {
						$all_hooks[ $key ]['action_hook'] = $lines;
						$all_hooks[ $key ]['desc']        = $myfile[ $key - 1 ];
					}
					if ( preg_match( '/apply_filters/i', $lines ) && ! strpos( $lines, 'str_replace' ) && ! strpos( $lines, 'preg_match' ) ) {
						$all_hooks[ $key ]['filter_hook'] = $lines;
						$all_hooks[ $key ]['desc']        = $myfile[ $key - 1 ];
					}
				}
			} elseif ( strpos( $file, '.' ) == '' && strpos( $file, '.' ) !== 0 ) {
				$response['files'][] = $file;
			}
		}
		if ( ! empty( $all_hooks ) ) {
			$response['hooks'] = $all_hooks;
		}
		return $response;
	}
	/**
	 * Get Count
	 *
	 * @param string  $status .
	 * @param string  $action .
	 * @param boolean $type .
	 * @return $result .
	 */
	public function wps_sfw_get_count( $status = 'all', $action = 'count', $type = false ) {
		return 0;
	}

	/**
	 * Wps_sfw_paypal_keys_validation_callack function
	 *
	 * @return void
	 */
	public function wps_sfw_paypal_keys_validation_callack() {
		check_ajax_referer( 'wps_sfw_admin_nonce', 'nonce' );

		$test_mode = isset( $_POST['testMode'] ) ? sanitize_text_field( wp_unslash( $_POST['testMode'] ) ) : '';
		$client_id = isset( $_POST['clientID'] ) ? sanitize_text_field( wp_unslash( $_POST['clientID'] ) ) : '';
		$client_secret = isset( $_POST['clientSecret'] ) ? sanitize_text_field( wp_unslash( $_POST['clientSecret'] ) ) : '';

		$endpoint = ( 'true' === $test_mode ) ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

		$response = wp_remote_post(
			$endpoint . '/v1/oauth2/token',
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'Accept' => 'application/json',
					'Accept-Language' => 'en_US',
					'Authorization'   => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
				),
				'body' => array(
					'grant_type' => 'client_credentials',
				),
			)
		);

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 200 == $response_code ) {
			$response = array(
				'msg' => esc_html__( 'Verification Successful', 'subscriptions-for-woocommerce' ),
				'status' => 'success',
				'code' => 200,
			);
		} else {
			$response = array(
				'msg' => $response_data->error_description,
				'status' => 'error',
				'code' => $response_code,
			);
		}
		echo json_encode( $response );
		wp_die();
	}

	/**
	 * Function To Remove Extra Dashboard From Subscription.
	 *
	 * @return void
	 */
	public function wps_sfw_remove_subscription_custom_menu() {
		remove_menu_page( 'edit.php?post_type=wps_subscriptions' );
	}

	/**
	 * Function to set Cron for branner image function.
	 *
	 * @return void
	 */
	public function wps_sfw_set_cron_for_plugin_notification() {
		$wps_sfw_offset = get_option( 'gmt_offset' );
		$wps_sfw_time   = time() + $wps_sfw_offset * 60 * 60;
		if ( ! wp_next_scheduled( 'wps_wgm_check_for_notification_update' ) ) {
			wp_schedule_event( $wps_sfw_time, 'daily', 'wps_wgm_check_for_notification_update' );
		}
	}

	/**
	 * Function to save response from server in terms of banner function.
	 *
	 * @return void
	 */
	public function wps_sfw_save_notice_message() {
		$wps_notification_data = $this->wps_sfw_get_update_notification_data();
		if ( is_array( $wps_notification_data ) && ! empty( $wps_notification_data ) ) {
			$banner_id      = array_key_exists( 'notification_id', $wps_notification_data[0] ) ? $wps_notification_data[0]['wps_banner_id'] : '';
			$banner_image = array_key_exists( 'notification_message', $wps_notification_data[0] ) ? $wps_notification_data[0]['wps_banner_image'] : '';
			$banner_url = array_key_exists( 'notification_message', $wps_notification_data[0] ) ? $wps_notification_data[0]['wps_banner_url'] : '';
			$banner_type = array_key_exists( 'notification_message', $wps_notification_data[0] ) ? $wps_notification_data[0]['wps_banner_type'] : '';
			update_option( 'wps_wgm_notify_new_banner_id', $banner_id );
			update_option( 'wps_wgm_notify_new_banner_image', $banner_image );
			update_option( 'wps_wgm_notify_new_banner_url', $banner_url );
			if ( 'regular' == $banner_type ) {
				update_option( 'wps_wgm_notify_hide_baneer_notification', '' );
			}
		}
	}

	/**
	 * This function is used to get notification data from server.
	 *
	 * @since    2.0.0
	 * @author WP Swings <webmaster@wpswings.com>
	 * @link https://www.wpswings.com/
	 */
	public function wps_sfw_get_update_notification_data() {
		$wps_notification_data = array();
		$url                   = 'https://demo.wpswings.com/client-notification/woo-gift-cards-lite/wps-client-notify.php';
		$attr                  = array(
			'action'         => 'wps_notification_fetch',
			'plugin_version' => SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION,
		);
		$query                 = esc_url_raw( add_query_arg( $attr, $url ) );
		$response              = wp_remote_get(
			$query,
			array(
				'timeout'   => 20,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo '<p><strong>Something went wrong: ' . esc_html( stripslashes( $error_message ) ) . '</strong></p>';
		} else {
			$wps_notification_data = json_decode( wp_remote_retrieve_body( $response ), true );
		}
		return $wps_notification_data;
	}

	/**
	 * Ajax callback to hide banner image.
	 *
	 * @return void
	 */
	public function wps_sfw_dismiss_notice_banner_callback() {
		if ( isset( $_REQUEST['wps_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wps_nonce'] ) ), 'wps-sfw-verify-notice-nonce' ) ) {

			$banner_id = get_option( 'wps_wgm_notify_new_banner_id', false );

			if ( isset( $banner_id ) && '' != $banner_id ) {
				update_option( 'wps_wgm_notify_hide_baneer_notification', $banner_id );
			}

			wp_send_json_success();
		}
	}

	/**
	 * Api settings fields.
	 *
	 * @since    1.0.0
	 * @param array $wsp_subscription_box_settings Api fields.
	 */
	public function wps_sfw_subscription_box_settings_fields( $wsp_subscription_box_settings ) {

		$is_pro = false;
		$is_pro = apply_filters( 'wsp_sfw_check_pro_plugin', $is_pro );
		$pro_group_tag = '';
		if ( ! $is_pro ) {
				$pro_group_tag = 'wps_pro_settings_tag';
		}
		$wsp_subscription_box_settings = array(
			array(
				'title' => __( 'Enable Subscription Box Feature', 'subscriptions-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Enable this to Create and Sell Subscription Box', 'subscriptions-for-woocommerce' ),
				'id'    => 'wsp_enable_subscription_box_features',
				'value' => get_option( 'wsp_enable_subscription_box_features' ),
				'class' => 'wsp-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'subscriptions-for-woocommerce' ),
					'no' => __( 'NO', 'subscriptions-for-woocommerce' ),
				),
			),

			array(
				'title' => __( 'Add to cart text For Subscription Box', 'subscriptions-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Use this option to change add to cart button text For Subscription Box Product.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wps_sfw_subscription_box_add_to_cart_text',
				'value' => get_option( 'wps_sfw_subscription_box_add_to_cart_text', '' ),
				'class' => 'sfw-text-class',
				'placeholder' => __( 'Subsscription Box Add to cart button text', 'subscriptions-for-woocommerce' ),
			),
			array(
				'title' => __( 'Place order text For Subscription Box', 'subscriptions-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Use this option to change place order button text For Subscription Box Product.', 'subscriptions-for-woocommerce' ),
				'id'    => 'wps_sfw_subscription_box_place_order_button_text',
				'value' => get_option( 'wps_sfw_subscription_box_place_order_button_text', '' ),
				'class' => 'sfw-text-class',
				'placeholder' => __( 'Subscription Box Place order button text', 'subscriptions-for-woocommerce' ),
			),

			array(
				'name' => __( 'To Create Multiple Subscription Box Feature Need Use Pro Version', 'subscriptions-for-woocommerce' ),
				'type'  => 'information',
				'id'    => 'wsp_enable_subscription_box_muti_features',
				'class' => 'wsp-sfw_information-class ' . $pro_group_tag,

			),

			array(
				'type'  => 'button',
				'id'    => 'wps_sfw_save_subscription_box_settings',
				'button_text' => esc_html__( 'Save Settings', 'subscriptions-for-woocommerce' ),
				'class' => 'sfw-button-class',
			),
		);

		return $wsp_subscription_box_settings;
	}


	/**
	 * Register Subscription box product type in product dropdown.
	 *
	 * @param array $types as type.
	 * @return array
	 */
	public function wsp_register_subscription_box_product_type( $types ) {
		$enable_subscription_box = get_option( 'wsp_enable_subscription_box_features' );
		if ( 'on' == $enable_subscription_box ) {
			$types['subscription_box'] = esc_html__( 'Subscription Box', 'subscriptions-for-woocommerce' );
		}
		return $types;
	}




	/**
	 * This function is used to add subscription box settings for product.
	 *
	 * @name wps_sfw_custom_product_tab_for_subscription
	 * @since    1.0.0
	 * @param    Array $tabs Products tabs array.
	 * @return   Array  $tabs
	 */
	public function wps_sfw_custom_product_tab_for_subscription_box( $tabs ) {

		$tabs['wps_sfw_subscription_box_product'] = array(
			'label'    => __( 'Subscription Box Settings', 'subscriptions-for-woocommerce' ),
			'target'   => 'wps_subscription_box_product_target_section',
			'class'    => '',
			'priority' => 80,
		);

		return $tabs;
	}

	/**
	 * Function to show subscription box html.
	 *
	 * @return void
	 */
	public function wps_sfw_custom_product_fields_for_subscription_box() {
		global $post;
		$post_id = $post->ID;
		$product = wc_get_product( $post_id );

		$wps_sfw_subscription_box_number = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_number', true );
		if ( empty( $wps_sfw_subscription_box_number ) ) {
			$wps_sfw_subscription_box_number = 1;
		}
		$wps_sfw_subscription_box_interval = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_interval', true );
		if ( empty( $wps_sfw_subscription_box_interval ) ) {
			$wps_sfw_subscription_box_interval = 'day';
		}

		$wps_sfw_subscription_box_expiry_number = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_expiry_number', true );
		$wps_sfw_subscription_box_expiry_interval = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_expiry_interval', true );

		$wps_sfw_subscription_box_price = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_box_price', true );

		$wps_sfw_subscription_box_setup = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_box_setup', true );
		$wps_sfw_subscription_box_products = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_box_products', true );

		$wps_sfw_subscription_box_categories = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_box_categories', true );

		$wps_sfw_manage_subscription_box_price = wps_sfw_get_meta_data( $post_id, 'wps_sfw_manage_subscription_box_price', true );

		// Ensure it's an array.
		$wps_sfw_subscription_box_categories = is_array( $wps_sfw_subscription_box_categories ) ? $wps_sfw_subscription_box_categories : array();

		$selected_category_ids = array();

		// Convert slugs to term IDs.
		if ( ! empty( $wps_sfw_subscription_box_categories ) ) {
			foreach ( $wps_sfw_subscription_box_categories as $slug ) {
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( $term ) {
					$selected_category_ids[] = $term->name; // Store term IDs.
				}
			}
		}

		$categories = get_terms(
			array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false,
			)
		);

		$wps_sfw_subscription_box_step_label = wps_sfw_get_meta_data( $post_id, 'wps_sfw_subscription_box_step_label', true );

		$is_pro = false;
		$is_pro = apply_filters( 'wsp_sfw_check_pro_plugin', $is_pro );
		if ( ! $is_pro ) {
				$pro_group_tag = 'wps_pro_settings';
		}
		?>
		<div id="wps_subscription_box_product_target_section" class="panel woocommerce_options_panel hidden">

			<strong><?php esc_html_e( 'Subscriptions Setting For Box', 'subscriptions-for-woocommerce' ); ?></strong>

			<p class="form-field wps_sfw_subscription_box_price_field ">
					<label for="wps_sfw_subscription_box_price">
					<?php esc_html_e( 'Subscriptions Box Price', 'subscriptions-for-woocommerce' ); ?>
					</label>
					<input type="number" class="short wc_input_number"  min="1"  name="wps_sfw_subscription_box_price" id="wps_sfw_subscription_box_price" value="<?php echo esc_attr( $wps_sfw_subscription_box_price ); ?>" placeholder="<?php esc_html_e( 'Enter subscription Box price', 'subscriptions-for-woocommerce' ); ?>"> 
				
			</p>
			<p class="form-field wps_sfw_manage_subscription_box_price_field wps_sfw_subscription_box_price_field_pro <?php echo esc_attr( $pro_group_tag ); ?>">
				<label for="wps_sfw_manage_subscription_box_price"><?php esc_html_e( 'Manage subscription box Price through all selected products', 'subscriptions-for-woocommerce' ); ?></label>
				<input type="checkbox" id="wps_sfw_manage_subscription_box_price" name="wps_sfw_manage_subscription_box_price" value="on"  <?php echo esc_attr( ( 'on' === $wps_sfw_manage_subscription_box_price ) ? 'checked' : null ); ?> />
			</p>
			<p class="form-field wps_sfw_subscription_box_number_field ">
				<label for="wps_sfw_subscription_box_number">
				<?php esc_html_e( 'Subscriptions Per Interval', 'subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" required name="wps_sfw_subscription_box_number" id="wps_sfw_subscription_box_number" value="<?php echo esc_attr( $wps_sfw_subscription_box_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription interval', 'subscriptions-for-woocommerce' ); ?>"> 
				<select id="wps_sfw_subscription_box_interval" name="wps_sfw_subscription_box_interval" class="wps_sfw_subscription_box_interval" >
					<?php foreach ( wps_sfw_subscription_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $wps_sfw_subscription_box_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the subscriptions time interval for the product "for example 10 days"', 'subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<p class="form-field wps_sfw_subscription_box_expiry_field ">
				<label for="wps_sfw_subscription_box_expiry_number">
				<?php esc_html_e( 'Subscriptions Expiry Interval', 'subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" name="wps_sfw_subscription_box_expiry_number" id="wps_sfw_subscription_box_expiry_number" value="<?php echo esc_attr( $wps_sfw_subscription_box_expiry_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription expiry', 'subscriptions-for-woocommerce' ); ?>"> 
				<select id="wps_sfw_subscription_box_expiry_interval" name="wps_sfw_subscription_box_expiry_interval" class="wps_sfw_subscription_box_expiry_interval" >
					<?php foreach ( wps_sfw_subscription_expiry_period( $wps_sfw_subscription_box_interval ) as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $wps_sfw_subscription_box_expiry_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the subscriptions expiry time interval for the product "leave empty for unlimited"', 'subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>

			<strong><?php esc_html_e( 'Setup For Subscription Box', 'subscriptions-for-woocommerce' ); ?></strong>
				<p class="form-field wps_sfw_subscription_box_setup_field ">
					<label for="wps_sfw_subscription_box_setup">
						<?php esc_html_e( 'Apply Subscription Box To', 'subscriptions-for-woocommerce' ); ?>
					</label>
					<select id="wps_sfw_subscription_box_setup" name="wps_sfw_subscription_box_setup">
						<option value="specific_products" <?php selected( $wps_sfw_subscription_box_setup, 'specific_products' ); ?>><?php esc_html_e( 'Specific Products', 'subscriptions-for-woocommerce' ); ?></option>
						<option value="specific_categories" <?php selected( $wps_sfw_subscription_box_setup, 'specific_categories' ); ?>><?php esc_html_e( 'Specific Categories', 'subscriptions-for-woocommerce' ); ?></option>
					</select>
				</p>

				<p class="form-field wps_sfw_subscription_box_products_field" style="display: none;">
					<label for="wps_sfw_subscription_box_products">
						<?php esc_html_e( 'Select Products', 'subscriptions-for-woocommerce' ); ?>
					</label>
					<select id="wps_sfw_subscription_box_products" name="wps_sfw_subscription_box_products[]" class="wc-product-search" multiple="multiple" style="width: 100%;"
						data-placeholder="<?php esc_attr_e( 'Search for a product...', 'subscriptions-for-woocommerce' ); ?>"
						data-action="woocommerce_json_search_products_and_variations">
						<?php
						if ( ! empty( $wps_sfw_subscription_box_products ) ) {
							foreach ( $wps_sfw_subscription_box_products as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( $product ) {
									echo '<option value="' . esc_attr( $product_id ) . '" selected>' . esc_html( $product->get_name() ) . '</option>';
								}
							}
						}
						?>
					</select>
				</p>

				<p class="form-field wps_sfw_subscription_box_categories_field" style="display: none;">
					<label for="wps_sfw_subscription_box_categories">
						<?php esc_html_e( 'Select Categories', 'subscriptions-for-woocommerce' ); ?>
					</label>
					<select id="wps_sfw_subscription_box_categories" name="wps_sfw_subscription_box_categories[]" class="wc-category-search" multiple="multiple" style="width: 100%;"data-placeholder="<?php esc_attr_e( 'Search for categories...', 'subscriptions-for-woocommerce' ); ?>"data-action="woocommerce_json_search_categories"> 
						
						<?php
						if ( ! empty( $categories ) ) {
							foreach ( $categories as $category ) {
								if ( in_array( $category->name, $selected_category_ids ) ) {

									$selected = in_array( (int) $category->name, $selected_category_ids ) ? 'selected="selected"' : '';
									echo '<option value="' . esc_attr( $category->name ) . '" ' . esc_html( $selected ) . '>' . esc_html( $category->name ) . '</option>';
								}
							}
						}
						?>

					</select>
				</p>
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
					</div>
				<!-- Go pro popup main end. -->
				<p class="form-field wps_sfw_subscription_box_setup">
					<label for="wps_sfw_subscription_box_step_label">
					<?php esc_html_e( 'Box Step Label', 'subscriptions-for-woocommerce' ); ?>
					</label>
					<input type="text" class="short" name="wps_sfw_subscription_box_step_label" id="wps_sfw_subscription_box_step_label" value="<?php echo esc_attr( $wps_sfw_subscription_box_step_label ); ?>" placeholder="<?php esc_html_e( 'Enter step label', 'subscriptions-for-woocommerce' ); ?>">
				</p>
				
			</p>
			<?php
			wp_nonce_field( 'wps_sfw_edit_nonce', 'wps_sfw_edit_nonce_filed' );
			// Add filed on product edit page.
			do_action( 'wps_sfw_subscription_box_product_edit_field', $post_id );

			?>
		</div>
		<?php
	}

	/**
	 * Function to save subscription box settings.
	 *
	 * @param int    $post_id as post id.
	 * @param object $post as post.
	 * @return void
	 */
	public function wps_sfw_save_subscription_box_data_for_subscription( $post_id, $post ) {
		if ( ! isset( $_POST['wps_sfw_edit_nonce_filed'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wps_sfw_edit_nonce_filed'] ) ), 'wps_sfw_edit_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}
		$product = wc_get_product( $post_id );
		$product_type = isset( $_POST['product-type'] ) ? sanitize_text_field( wp_unslash( $_POST['product-type'] ) ) : '';

		if ( 'subscription_box' == $product_type ) {

			$wps_sfw_subscription_box_price = isset( $_POST['wps_sfw_subscription_box_price'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_box_price'] ) ) : '';

			$wps_sfw_subscription_box_number = isset( $_POST['wps_sfw_subscription_box_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_box_number'] ) ) : '';

			$wps_sfw_subscription_box_interval = isset( $_POST['wps_sfw_subscription_box_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_box_interval'] ) ) : '';

			$wps_sfw_subscription_box_expiry_number = isset( $_POST['wps_sfw_subscription_box_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_box_expiry_number'] ) ) : '';

			$wps_sfw_subscription_box_expiry_interval = isset( $_POST['wps_sfw_subscription_box_expiry_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_box_expiry_interval'] ) ) : '';

			$wps_sfw_subscription_box_setup = isset( $_POST['wps_sfw_subscription_box_setup'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_box_setup'] ) ) : '';

			$wps_sfw_subscription_box_step_label = isset( $_POST['wps_sfw_subscription_box_step_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_box_step_label'] ) ) : '';

			$wps_sfw_subscription_box_products = isset( $_POST['wps_sfw_subscription_box_products'] ) ? $_POST['wps_sfw_subscription_box_products'] : array();

			$wps_sfw_manage_subscription_box_price = isset( $_POST['wps_sfw_manage_subscription_box_price'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_manage_subscription_box_price'] ) ) : '';

			if ( $wps_sfw_subscription_box_products ) {
				wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_box_products', $wps_sfw_subscription_box_products );
			}
			$selected_categories = isset( $_POST['wps_sfw_subscription_box_categories'] ) ? $_POST['wps_sfw_subscription_box_categories'] : array();
			if ( $selected_categories ) {
				wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_box_categories', $selected_categories );
			}

			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_box_price', $wps_sfw_subscription_box_price );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_number', $wps_sfw_subscription_box_number );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_interval', $wps_sfw_subscription_box_interval );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_expiry_number', $wps_sfw_subscription_box_expiry_number );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_expiry_interval', $wps_sfw_subscription_box_expiry_interval );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_box_setup', $wps_sfw_subscription_box_setup );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_box_step_label', $wps_sfw_subscription_box_step_label );
			wps_sfw_update_meta_data( $post_id, 'wps_sfw_subscription_box_products', $wps_sfw_subscription_box_products );

			if ( 'on' == $wps_sfw_manage_subscription_box_price ) {
				wps_sfw_update_meta_data( $post_id, 'wps_sfw_manage_subscription_box_price', $wps_sfw_manage_subscription_box_price );
			} else {
				wps_sfw_update_meta_data( $post_id, 'wps_sfw_manage_subscription_box_price', '' );
			}
			wps_sfw_update_meta_data( $post_id, '_price', $wps_sfw_subscription_box_price );

			if ( ! get_option( 'wps_sfw_first_subscription_box_id', false ) ) {
				update_option( 'wps_sfw_first_subscription_box_id', $post_id );
			}
		}
	}
}

