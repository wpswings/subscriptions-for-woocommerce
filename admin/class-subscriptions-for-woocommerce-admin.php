<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpswing.com/
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

		if ( isset( $screen->id ) && in_array( $screen->id, $wps_sfw_screen_ids ) ) {

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

		if ( isset( $screen->id ) && 'product' == $screen->id ) {
			wp_enqueue_style( 'wps-sfw-admin-single-product-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/subscription-for-woocommerce-product-edit.css', array(), time(), 'all' );

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

		if ( isset( $screen->id ) && in_array( $screen->id, $wps_sfw_screen_ids ) ) {

			if ( ! wps_sfw_check_multistep() ) {

				// Js for the multistep from.
				$script_path       = '../../build/index.js';
				$script_asset_path = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'build/index.asset.php';
				$script_asset      = file_exists( $script_asset_path )
					? require $script_asset_path
					: array(
						'dependencies' => array(
							'wp-hooks',
							'wp-element',
							'wp-i18n',
							'wc-components',
						),
						'version'      => filemtime( $script_path ),
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
				)
			);

			wp_enqueue_script( $this->plugin_name . 'admin-js' );
		}

		if ( isset( $screen->id ) && 'product' == $screen->id ) {
			wp_register_script( 'wps-sfw-admin-single-product-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/subscription-for-woocommerce-product-edit.js', array( 'jquery' ), $this->version, false );
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
			);
			wp_localize_script(
				'wps-sfw-admin-single-product-js',
				'sfw_product_param',
				$wps_sfw_data
			);
			wp_enqueue_script( 'jquery-ui-datepicker' );

		}
		if ( 'wp-swings_page_subscriptions_for_woocommerce_menu' === $screen->id ) {

			wp_register_script( $this->plugin_name . 'admin-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/subscriptions-for-woocommerce-admin.js', array( 'jquery' ), $this->version, false );

			wp_localize_script(
				$this->plugin_name . 'admin-js',
				'sfw_admin_param',
				array(
					'ajaxurl'          => admin_url( 'admin-ajax.php' ),
					'wps_sfw_react_nonce'            => wp_create_nonce( 'ajax-nonce' ),
					'wps_sfw_callback'               => 'wps_sfw_ajax_callbacks',
					'wps_sfw_pending_product'        => $this->wps_sfw_get_count( 'pending', 'result', 'products' ),
					'wps_sfw_pending_product_count'  => $this->wps_sfw_get_count( 'pending', 'count', 'products' ),
					'wps_sfw_pending_orders'         => $this->wps_sfw_get_count( 'pending', 'result', 'mwb_renewal_orders' ),
					'wps_sfw_pending_orders_count'   => $this->wps_sfw_get_count( 'pending', 'count', 'mwb_renewal_orders' ),
					'wps_sfw_pending_subs'           => $this->wps_sfw_get_count( 'pending', 'result', 'post_type_subscription' ),
					'wps_sfw_pending_subs_count'     => $this->wps_sfw_get_count( 'pending', 'count', 'post_type_subscription' ),

				)
			);
			wp_enqueue_script( $this->plugin_name . 'admin-js' );
			wp_enqueue_script( $this->plugin_name . 'sfw-swal.js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/sfw-swal.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . 'sfw-swall.js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/sfw-swall.js', array( 'jquery' ), $this->version, false );
		}
	}
	/**
	 * Adding settings menu for Subscriptions For Woocommerce.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_options_page() {
		global $submenu;
		if ( empty( $GLOBALS['admin_page_hooks']['wps-plugins'] ) ) {

			add_menu_page( 'WP Swings', 'WP Swings', 'manage_options', 'wps-plugins', array( $this, 'wps_plugins_listing_page' ), SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/wpswings_logo.png', 15 );
			// Add menus.
			if ( wps_sfw_check_multistep() ) {
				add_submenu_page( 'wps-plugins', 'Home', 'Home', 'manage_options', 'home', array( $this, 'wps_sfw_welcome_callback_function' ) );
			}
			$sfw_menus = apply_filters( 'wps_add_plugins_menus_array', array() );
			if ( is_array( $sfw_menus ) && ! empty( $sfw_menus ) ) {
				foreach ( $sfw_menus as $sfw_key => $sfw_value ) {
					add_submenu_page( 'wps-plugins', $sfw_value['name'], $sfw_value['name'], 'manage_options', $sfw_value['menu_link'], array( $sfw_value['instance'], $sfw_value['function'] ) );
				}
			}
		} else {
			$is_home = false;
			if ( ! empty( $submenu['wps-plugins'] ) ) {
				foreach ( $submenu['wps-plugins'] as $key => $value ) {
					if ( 'Home' === $value[0] ) {
						$is_home = true;
					}
				}
				if ( ! $is_home ) {
					if ( wps_sfw_check_multistep() ) {
						add_submenu_page( 'wps-plugins', 'Home', 'Home', 'manage_options', 'home', array( $this, 'wps_sfw_welcome_callback_function' ), 1 );
					}
				}
			}
		}
		add_submenu_page( 'woocommerce', __( 'Wps Subscriptions', 'subscriptions-for-woocommerce' ), __( 'Wps Subscriptions', 'subscriptions-for-woocommerce' ), 'manage_options', 'subscriptions-for-woocommerce', array( $this, 'wps_sfw_addsubmenu_woocommerce' ) );

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
				'value' => 'on',
				'checked' => ( 'on' === get_option( 'wps_sfw_cancel_subscription_for_customer', '' ) ? 'on' : 'off' ),
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
				'type'  => 'button',
				'id'    => 'wps_sfw_save_general_settings',
				'button_text' => __( 'Save Settings', 'subscriptions-for-woocommerce' ),
				'class' => 'sfw-button-class',
			),
		);
		// Add general settings.
		return apply_filters( 'wps_sfw_add_general_settings_fields', $sfw_settings_general );

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
			'wrapper_class' => 'show_if_simple',
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

		$wps_sfw_subscription_number = get_post_meta( $post_id, 'wps_sfw_subscription_number', true );
		if ( empty( $wps_sfw_subscription_number ) ) {
			$wps_sfw_subscription_number = 1;
		}
		$wps_sfw_subscription_interval = get_post_meta( $post_id, 'wps_sfw_subscription_interval', true );
		if ( empty( $wps_sfw_subscription_interval ) ) {
			$wps_sfw_subscription_interval = 'day';
		}

		$wps_sfw_subscription_expiry_number = get_post_meta( $post_id, 'wps_sfw_subscription_expiry_number', true );
		$wps_sfw_subscription_expiry_interval = get_post_meta( $post_id, 'wps_sfw_subscription_expiry_interval', true );
		$wps_sfw_subscription_initial_signup_price = get_post_meta( $post_id, 'wps_sfw_subscription_initial_signup_price', true );
		$wps_sfw_subscription_free_trial_number = get_post_meta( $post_id, 'wps_sfw_subscription_free_trial_number', true );
		$wps_sfw_subscription_free_trial_interval = get_post_meta( $post_id, 'wps_sfw_subscription_free_trial_interval', true );
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
			<input type="number" class="short wc_input_price"  min="1" step="any" name="wps_sfw_subscription_initial_signup_price" id="wps_sfw_subscription_initial_signup_price" value="<?php echo esc_attr( $wps_sfw_subscription_initial_signup_price ); ?>" placeholder="<?php esc_html_e( 'Enter signup fee', 'subscriptions-for-woocommerce' ); ?>"> 
			
		 <?php
			$description_text = __( 'Choose the subscriptions initial fee for the product "leave empty for no initial fee"', 'subscriptions-for-woocommerce' );
			echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
		</p>
		<p class="form-field wps_sfw_subscription_free_trial_field ">
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
		update_post_meta( $post_id, '_wps_sfw_product', $wps_sfw_product );
		if ( isset( $_POST['_wps_sfw_product'] ) && ! empty( $_POST['_wps_sfw_product'] ) ) {

			$wps_sfw_subscription_number = isset( $_POST['wps_sfw_subscription_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_number'] ) ) : '';
			$wps_sfw_subscription_interval = isset( $_POST['wps_sfw_subscription_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_interval'] ) ) : '';
			$wps_sfw_subscription_expiry_number = isset( $_POST['wps_sfw_subscription_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_expiry_number'] ) ) : '';
			$wps_sfw_subscription_expiry_interval = isset( $_POST['wps_sfw_subscription_expiry_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_expiry_interval'] ) ) : '';
			$wps_sfw_subscription_initial_signup_price = isset( $_POST['wps_sfw_subscription_initial_signup_price'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_initial_signup_price'] ) ) : '';
			$wps_sfw_subscription_free_trial_number = isset( $_POST['wps_sfw_subscription_free_trial_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_free_trial_number'] ) ) : '';
			$wps_sfw_subscription_free_trial_interval = isset( $_POST['wps_sfw_subscription_free_trial_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_sfw_subscription_free_trial_interval'] ) ) : '';

			update_post_meta( $post_id, 'wps_sfw_subscription_number', $wps_sfw_subscription_number );
			update_post_meta( $post_id, 'wps_sfw_subscription_interval', $wps_sfw_subscription_interval );
			update_post_meta( $post_id, 'wps_sfw_subscription_expiry_number', $wps_sfw_subscription_expiry_number );
			update_post_meta( $post_id, 'wps_sfw_subscription_expiry_interval', $wps_sfw_subscription_expiry_interval );
			update_post_meta( $post_id, 'wps_sfw_subscription_initial_signup_price', $wps_sfw_subscription_initial_signup_price );
			update_post_meta( $post_id, 'wps_sfw_subscription_free_trial_number', $wps_sfw_subscription_free_trial_number );
			update_post_meta( $post_id, 'wps_sfw_subscription_free_trial_interval', $wps_sfw_subscription_free_trial_interval );

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
				do_action( 'wps_sfw_subscription_cancel', $wps_subscription_id, 'Cancel' );
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
		if ( $enable_plugin && 'no' == $allready_created ) {
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
			update_post_meta( $post_id, '_visibility', 'visible' );
			update_post_meta( $post_id, '_stock_status', 'instock' );

			update_post_meta( $post_id, '_wps_sfw_product', 'yes' );
			update_post_meta( $post_id, 'wps_sfw_subscription_number', $subscription_number );
			update_post_meta( $post_id, 'wps_sfw_subscription_interval', $subscription_interval );

			update_post_meta( $post_id, '_regular_price', $product_price );
			update_post_meta( $post_id, '_sale_price', '' );
			update_post_meta( $post_id, '_price', $product_price );
			$product = wc_get_product( $post_id );

			$product->save();
			update_option( 'wps_sfw_multistep_product_create_done', 'yes' );
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
	 * Ajax Call back.
	 */
	public function wps_sfw_ajax_callbacks() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$event = ! empty( $_POST['event'] ) ? sanitize_text_field( wp_unslash( $_POST['event'] ) ) : '';
		if ( method_exists( $this, $event ) ) {
			$data = $this->$event( $_POST );
		} else {
			$data = esc_html__( 'method not found', 'subscriptions-for-woocommerce' );
		}
		echo wp_json_encode( $data );
		wp_die();
	}
	/**
	 * Import product callback.
	 *
	 * @param array $product_data The $_POST data.
	 */
	public function wps_sfw_import_single_product( $product_data = array() ) {
		$products = ! empty( $product_data['products'] ) ? $product_data['products'] : array();

		if ( empty( $products ) ) {
			return array();
		}

		// Remove this product from request.
		foreach ( $products as $key => $product ) {
			$product_id = ! empty( $product['post_id'] ) ? $product['post_id'] : false;
			unset( $products[ $key ] );
			break;
		}

		// Attempt for one product.
		if ( ! empty( $product_id ) ) {

			try {

				$post_meta_keys = array(
					'_mwb_sfw_product',
					'mwb_sfw_subscription_number',
					'mwb_sfw_subscription_interval',
					'mwb_sfw_subscription_expiry_number',
					'mwb_sfw_subscription_expiry_interval',
					'mwb_sfw_subscription_initial_signup_price',
					'mwb_sfw_subscription_free_trial_number',
					'mwb_sfw_subscription_free_trial_interval',
					'mwb_sfw_variable_product',
					'mwb_wsp_enbale_certain_month',
					'mwb_wsp_week_sync',
					'mwb_wsp_month_sync',
					'mwb_wsp_year_sync',
					'mwb_wsp_year_number',
					'mwb_sfw_subscription_start_date',

					'mwb_sfw_renewal_order',
					'mwb_sfw_subscription',
					'mwb_sfw_parent_order_id',
					'mwb_order_currency',
					'mwb_wsp_first_payment_date',
					'mwb_sfw_paypal_transaction_id',
					'mwb_sfw_paypal_subscriber_id',
					'mwb_subscriber_payment_type',
					'mwb_subscriber_address',
					'mwb_subscriber_last_name',
					'mwb_subscriber_first_name',
					'mwb_subscriber_id',
					'mwb_susbcription_end',
					'mwb_parent_order',
					'mwb_sfw_order_has_subscription',
					'mwb_subscription_id',
					'_mwb_is_renewal_success',
					'mwb_wsp_manual_renewal_order',
					'mwb_upgrade_downgrade_order_succes',
					'mwb_wps_gc_coupon_updated',
					'mwb_wsp_no_of_retry_attempt',
					'mwb_upgrade_downgrade_order',

					'mwb_upgrade_downgrade_data',
					'mwb_renewal_subscription_order',
					'mwb_wsp_no_of_renewal_order',
					'mwb_wsp_renewal_order_data',
					'mwb_wsp_last_renewal_order_id',
					'mwb_next_payment_date',
					'mwb_subscription_status',
					'_mwb_paypal_transaction_ids',
					'_mwb_sfw_payment_transaction_id',
					'_mwb_paypal_subscription_id',
					'mwb_susbcription_trial_end',
					'mwb_sfw_order_has_subscription',
					'mwb_subscription_id',
					'mwb_schedule_start',
					'mwb_sfw_subscription_activated',
					'mwb_parent_order',
					'mwb_recurring_total',
					'mwb_customer_id',
					'mwb_order_currency',
					'mwb_wsp_first_payment_date',
					'mwb_sfw_paypal_transaction_id',
					'mwb_sfw_paypal_subscriber_id',
					'mwb_subscriber_payment_type',
					'mwb_subscriber_address',
					'mwb_subscriber_last_name',
					'mwb_subscriber_first_name',
					'mwb_subscriber_id',
					'mwb_wsp_failed_attemp_for_subscription',
					'mwb_wsp_failed_order_for_subscription',
					'mwb_wsf_manage_prorate_negativ_amount_date',
					'mwb_wsf_manage_prorate_negativ_amount_wallet',
					'mwb_wsp_switch_order_data',
					'mwb_wsp_last_switch_order_id',
					'mwb_wsp_first_payment_date',
					'mwb_wgm_giftcard_coupon',
					'mwb_sfw_multisafepay_recurring_reminder_sent',
					'mwb_wsp_plan_expire_notice_send',
					'mwb_subscription_reactive_time',
					'mwb_subscription_pause_time',
					'mwb_wsp_manual_renewal_order',
					'mwb_upgrade_downgrade_order',
					'mwb_upgrade_downgrade_order_succes',
				);
				foreach ( $post_meta_keys as $key => $meta_keys ) {
					$value   = get_post_meta( $product_id, $meta_keys, true );
					$new_key = str_replace( 'mwb_', 'wps_', $meta_keys );

					if ( ! empty( get_post_meta( $product_id, $new_key, true ) ) ) {
						continue;
					}
					update_post_meta( $product_id, $new_key, $value );
					delete_post_meta( $product_id, $meta_keys );
				}
				update_post_meta( $product_id, 'wps_sfw_migrated', true );
			} catch ( \Throwable $th ) {
				wp_die( esc_html( $th->getMessage() ) );
			}
		}
		return compact( 'products' );
	}
	/**
	 * Import product callback.
	 *
	 * @param array $order_data The $_POST data.
	 */
	public function wps_sfw_import_single_renewal( $order_data = array() ) {
		$orders = ! empty( $order_data['orders'] ) ? $order_data['orders'] : array();

		if ( empty( $orders ) ) {
			return array();
		}

		// Remove this product from request.
		foreach ( $orders as $key => $order ) {
			$order_id = ! empty( $order['post_id'] ) ? $order['post_id'] : false;
			unset( $orders[ $key ] );
			break;
		}

		// Attempt for one product.
		if ( ! empty( $order_id ) ) {

			try {
				$post_meta_keys = array(
					'_mwb_sfw_product',
					'mwb_sfw_subscription_number',
					'mwb_sfw_subscription_interval',
					'mwb_sfw_subscription_expiry_number',
					'mwb_sfw_subscription_expiry_interval',
					'mwb_sfw_subscription_initial_signup_price',
					'mwb_sfw_subscription_free_trial_number',
					'mwb_sfw_subscription_free_trial_interval',
					'mwb_sfw_variable_product',
					'mwb_wsp_enbale_certain_month',
					'mwb_wsp_week_sync',
					'mwb_wsp_month_sync',
					'mwb_wsp_year_sync',
					'mwb_wsp_year_number',
					'mwb_sfw_subscription_start_date',

					'mwb_sfw_renewal_order',
					'mwb_sfw_subscription',
					'mwb_sfw_parent_order_id',
					'mwb_order_currency',
					'mwb_wsp_first_payment_date',
					'mwb_sfw_paypal_transaction_id',
					'mwb_sfw_paypal_subscriber_id',
					'mwb_subscriber_payment_type',
					'mwb_subscriber_address',
					'mwb_subscriber_last_name',
					'mwb_subscriber_first_name',
					'mwb_subscriber_id',
					'mwb_susbcription_end',
					'mwb_parent_order',
					'mwb_sfw_order_has_subscription',
					'mwb_subscription_id',
					'_mwb_is_renewal_success',
					'mwb_wsp_manual_renewal_order',
					'mwb_upgrade_downgrade_order_succes',
					'mwb_wps_gc_coupon_updated',
					'mwb_wsp_no_of_retry_attempt',
					'mwb_upgrade_downgrade_order',

					'mwb_upgrade_downgrade_data',
					'mwb_renewal_subscription_order',
					'mwb_wsp_no_of_renewal_order',
					'mwb_wsp_renewal_order_data',
					'mwb_wsp_last_renewal_order_id',
					'mwb_next_payment_date',
					'mwb_subscription_status',
					'_mwb_paypal_transaction_ids',
					'_mwb_sfw_payment_transaction_id',
					'_mwb_paypal_subscription_id',
					'mwb_susbcription_trial_end',
					'mwb_sfw_order_has_subscription',
					'mwb_subscription_id',
					'mwb_schedule_start',
					'mwb_sfw_subscription_activated',
					'mwb_parent_order',
					'mwb_recurring_total',
					'mwb_customer_id',
					'mwb_order_currency',
					'mwb_wsp_first_payment_date',
					'mwb_sfw_paypal_transaction_id',
					'mwb_sfw_paypal_subscriber_id',
					'mwb_subscriber_payment_type',
					'mwb_subscriber_address',
					'mwb_subscriber_last_name',
					'mwb_subscriber_first_name',
					'mwb_subscriber_id',
					'mwb_wsp_failed_attemp_for_subscription',
					'mwb_wsp_failed_order_for_subscription',
					'mwb_wsf_manage_prorate_negativ_amount_date',
					'mwb_wsf_manage_prorate_negativ_amount_wallet',
					'mwb_wsp_switch_order_data',
					'mwb_wsp_last_switch_order_id',
					'mwb_wsp_first_payment_date',
					'mwb_wgm_giftcard_coupon',
					'mwb_sfw_multisafepay_recurring_reminder_sent',
					'mwb_wsp_plan_expire_notice_send',
					'mwb_subscription_reactive_time',
					'mwb_subscription_pause_time',
					'mwb_wsp_manual_renewal_order',
					'mwb_upgrade_downgrade_order',
					'mwb_upgrade_downgrade_order_succes',
				);
				foreach ( $post_meta_keys as $key => $meta_keys ) {
					$value   = get_post_meta( $order_id, $meta_keys, true );
					$new_key = str_replace( 'mwb_', 'wps_', $meta_keys );

					if ( ! empty( get_post_meta( $order_id, $new_key, true ) ) ) {
						continue;
					}
					update_post_meta( $order_id, $new_key, $value );
					if ( 'wps_susbcription_end' == $new_key && get_post_meta( $order_id, $new_key, true ) == '' ) {
						update_post_meta( $order_id, 'wps_susbcription_end', 0 );
					}
					delete_post_meta( $order_id, $meta_keys );
				}

				$wps_get_post = get_post( $order_id );
				$args         = array();
				if ( ! empty( $wps_get_post ) ) {
					foreach ( $wps_get_post as $key => $value ) {
						if ( 'post_status' === $key || 'post_type' === $key || 'post_name' === $key ) {
							$value        = str_replace( 'MWB', 'WPS', $value );
							$value        = str_replace( 'mwb', 'wps', $value );
							$args[ $key ] = $value;
						} else {
							$args[ $key ] = $value;
						}
					}
					wp_update_post( $args );
				}
				update_post_meta( $order_id, 'wps_sfw_migrated', true );
			} catch ( \Throwable $th ) {
				wp_die( esc_html( $th->getMessage() ) );
			}
		}
		return compact( 'orders' );
	}
	/**
	 * Import subscription callback.
	 *
	 * @param array $subscriptions_data The $_POST data.
	 */
	public function wps_sfw_import_single_subscription( $subscriptions_data = array() ) {
		$subscriptions = ! empty( $subscriptions_data['subscriptions'] ) ? $subscriptions_data['subscriptions'] : array();

		if ( empty( $subscriptions ) ) {
			return array();
		}

		// Remove this product from request.
		foreach ( $subscriptions as $key => $subscription ) {
			$subscription_id = ! empty( $subscription['post_id'] ) ? $subscription['post_id'] : false;
			unset( $subscriptions[ $key ] );
			break;
		}

		// Attempt for one product.
		if ( ! empty( $subscription_id ) ) {

			try {

				$post_meta_keys = array(
					'_mwb_sfw_product',
					'mwb_sfw_subscription_number',
					'mwb_sfw_subscription_interval',
					'mwb_sfw_subscription_expiry_number',
					'mwb_sfw_subscription_expiry_interval',
					'mwb_sfw_subscription_initial_signup_price',
					'mwb_sfw_subscription_free_trial_number',
					'mwb_sfw_subscription_free_trial_interval',
					'mwb_sfw_variable_product',
					'mwb_wsp_enbale_certain_month',
					'mwb_wsp_week_sync',
					'mwb_wsp_month_sync',
					'mwb_wsp_year_sync',
					'mwb_wsp_year_number',
					'mwb_sfw_subscription_start_date',

					'mwb_sfw_renewal_order',
					'mwb_sfw_subscription',
					'mwb_sfw_parent_order_id',
					'mwb_order_currency',
					'mwb_wsp_first_payment_date',
					'mwb_sfw_paypal_transaction_id',
					'mwb_sfw_paypal_subscriber_id',
					'mwb_subscriber_payment_type',
					'mwb_subscriber_address',
					'mwb_subscriber_last_name',
					'mwb_subscriber_first_name',
					'mwb_subscriber_id',
					'mwb_susbcription_end',
					'mwb_parent_order',
					'mwb_sfw_order_has_subscription',
					'mwb_subscription_id',
					'_mwb_is_renewal_success',
					'mwb_wsp_manual_renewal_order',
					'mwb_upgrade_downgrade_order_succes',
					'mwb_wps_gc_coupon_updated',
					'mwb_wsp_no_of_retry_attempt',
					'mwb_upgrade_downgrade_order',

					'mwb_upgrade_downgrade_data',
					'mwb_renewal_subscription_order',
					'mwb_wsp_no_of_renewal_order',
					'mwb_wsp_renewal_order_data',
					'mwb_wsp_last_renewal_order_id',
					'mwb_next_payment_date',
					'mwb_subscription_status',
					'_mwb_paypal_transaction_ids',
					'_mwb_sfw_payment_transaction_id',
					'_mwb_paypal_subscription_id',
					'mwb_susbcription_trial_end',
					'mwb_sfw_order_has_subscription',
					'mwb_subscription_id',
					'mwb_schedule_start',
					'mwb_sfw_subscription_activated',
					'mwb_parent_order',
					'mwb_recurring_total',
					'mwb_customer_id',
					'mwb_order_currency',
					'mwb_wsp_first_payment_date',
					'mwb_sfw_paypal_transaction_id',
					'mwb_sfw_paypal_subscriber_id',
					'mwb_subscriber_payment_type',
					'mwb_subscriber_address',
					'mwb_subscriber_last_name',
					'mwb_subscriber_first_name',
					'mwb_subscriber_id',
					'mwb_wsp_failed_attemp_for_subscription',
					'mwb_wsp_failed_order_for_subscription',
					'mwb_wsf_manage_prorate_negativ_amount_date',
					'mwb_wsf_manage_prorate_negativ_amount_wallet',
					'mwb_wsp_switch_order_data',
					'mwb_wsp_last_switch_order_id',
					'mwb_wsp_first_payment_date',
					'mwb_wgm_giftcard_coupon',
					'mwb_sfw_multisafepay_recurring_reminder_sent',
					'mwb_wsp_plan_expire_notice_send',
					'mwb_subscription_reactive_time',
					'mwb_subscription_pause_time',
					'mwb_wsp_manual_renewal_order',
					'mwb_upgrade_downgrade_order',
					'mwb_upgrade_downgrade_order_succes',
				);
				foreach ( $post_meta_keys as $key => $meta_keys ) {
					$value   = get_post_meta( $subscription_id, $meta_keys, true );
					$new_key = str_replace( 'mwb_', 'wps_', $meta_keys );

					if ( ! empty( get_post_meta( $subscription_id, $new_key, true ) ) ) {
						continue;
					}
					update_post_meta( $subscription_id, $new_key, $value );
					delete_post_meta( $subscription_id, $meta_keys );
				}

				$wps_get_post = get_post( $subscription_id );
				$args         = array();
				if ( ! empty( $wps_get_post ) ) {
					foreach ( $wps_get_post as $key => $value ) {
						if ( 'post_status' === $key || 'post_type' === $key || 'post_name' === $key ) {
							$value        = str_replace( 'MWB', 'WPS', $value );
							$value        = str_replace( 'mwb', 'wps', $value );
							$args[ $key ] = $value;
						} else {
							$args[ $key ] = $value;
						}
					}
					wp_update_post( $args );
				}
				update_post_meta( $subscription_id, 'wps_sfw_migrated', true );
			} catch ( \Throwable $th ) {
				wp_die( esc_html( $th->getMessage() ) );
			}
		}
		return compact( 'subscriptions' );
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

		global $wpdb;
		$table = $wpdb->prefix . 'postmeta';
		$post_meta_keys = array(
			'_mwb_sfw_product',
			'mwb_sfw_subscription_number',
			'mwb_sfw_subscription_interval',
			'mwb_sfw_subscription_expiry_number',
			'mwb_sfw_subscription_expiry_interval',
			'mwb_sfw_subscription_initial_signup_price',
			'mwb_sfw_subscription_free_trial_number',
			'mwb_sfw_subscription_free_trial_interval',
			'mwb_sfw_variable_product',
			'mwb_wsp_enbale_certain_month',
			'mwb_wsp_week_sync',
			'mwb_wsp_month_sync',
			'mwb_wsp_year_sync',
			'mwb_wsp_year_number',
			'mwb_sfw_subscription_start_date',

			'mwb_sfw_renewal_order',
			'mwb_sfw_subscription',
			'mwb_sfw_parent_order_id',
			'mwb_order_currency',
			'mwb_wsp_first_payment_date',
			'mwb_sfw_paypal_transaction_id',
			'mwb_sfw_paypal_subscriber_id',
			'mwb_subscriber_payment_type',
			'mwb_subscriber_address',
			'mwb_subscriber_last_name',
			'mwb_subscriber_first_name',
			'mwb_subscriber_id',
			'mwb_susbcription_end',
			'mwb_parent_order',
			'mwb_sfw_order_has_subscription',
			'mwb_subscription_id',
			'_mwb_is_renewal_success',
			'mwb_wsp_manual_renewal_order',
			'mwb_upgrade_downgrade_order_succes',
			'mwb_wps_gc_coupon_updated',
			'mwb_wsp_no_of_retry_attempt',
			'mwb_upgrade_downgrade_order',

			'mwb_upgrade_downgrade_data',
			'mwb_renewal_subscription_order',
			'mwb_wsp_no_of_renewal_order',
			'mwb_wsp_renewal_order_data',
			'mwb_wsp_last_renewal_order_id',
			'mwb_next_payment_date',
			'mwb_subscription_status',
			'_mwb_paypal_transaction_ids',
			'_mwb_sfw_payment_transaction_id',
			'_mwb_paypal_subscription_id',
			'mwb_susbcription_trial_end',
			'mwb_sfw_order_has_subscription',
			'mwb_subscription_id',
			'mwb_schedule_start',
			'mwb_sfw_subscription_activated',
			'mwb_parent_order',
			'mwb_recurring_total',
			'mwb_customer_id',
			'mwb_order_currency',
			'mwb_wsp_first_payment_date',
			'mwb_sfw_paypal_transaction_id',
			'mwb_sfw_paypal_subscriber_id',
			'mwb_subscriber_payment_type',
			'mwb_subscriber_address',
			'mwb_subscriber_last_name',
			'mwb_subscriber_first_name',
			'mwb_subscriber_id',
			'mwb_wsp_failed_attemp_for_subscription',
			'mwb_wsp_failed_order_for_subscription',
			'mwb_wsf_manage_prorate_negativ_amount_date',
			'mwb_wsf_manage_prorate_negativ_amount_wallet',
			'mwb_wsp_switch_order_data',
			'mwb_wsp_last_switch_order_id',
			'mwb_wsp_first_payment_date',
			'mwb_wgm_giftcard_coupon',
			'mwb_sfw_multisafepay_recurring_reminder_sent',
			'mwb_wsp_plan_expire_notice_send',
			'mwb_subscription_reactive_time',
			'mwb_subscription_pause_time',
			'mwb_wsp_manual_renewal_order',
			'mwb_upgrade_downgrade_order',
			'mwb_upgrade_downgrade_order_succes',
		);
		if ( 'products' === $type ) {
			switch ( $status ) {
				case 'pending':
					$result = get_posts(
						array(
							'post_type' => 'product',
							'meta_key' => $post_meta_keys,
							'post_status'    => array( 'publish', 'draft', 'trash', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed' ),
							'numberposts'       => -1,
							'fields'         => 'ids',
						)
					);
						$temp2 = get_posts(
							array(
								'post_type' => 'product_variation',
								'post_status'    => array( 'publish', 'draft', 'trash', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed' ),
								'numberposts'       => -1,
								'meta_key' => $post_meta_keys,
								'fields'         => 'ids',
							)
						);
					if ( empty( $result ) ) {
						$result = array();
					}
					if ( empty( $temp2 ) ) {
						$temp2 = array();
					}
					if ( is_array( $result ) && is_array( $temp2 ) ) {

						$result = array_merge( $result, $temp2 );
					}
						$final_result = array();
					foreach ( $result as $key => $value ) {
						$final_result[]['post_id'] = $value;
					}
					break;
				default:
					$sql = false;
					break;
			}
		} elseif ( 'post_type_subscription' === $type ) {
			switch ( $status ) {
				case 'pending':
					$result = get_posts(
						array(
							'post_type' => 'mwb_subscriptions',
							'post_status' => 'wc-mwb_renewal',
							'meta_key' => $post_meta_keys,
							'numberposts'       => -1,
							'fields'         => 'ids',
						)
					);
						$final_result = array();
					foreach ( $result as $key => $value ) {
						$final_result[]['post_id'] = $value;
					}
					break;
				default:
					$sql = false;
					break;
			}
		} elseif ( 'mwb_renewal_orders' === $type ) {
			switch ( $status ) {
				case 'pending':
					$result = wc_get_orders(
						array(
							'type'     => 'shop_order',
							'numberposts'       => -1,
							'meta_key' => $post_meta_keys, // phpcs:ignore
							'return'   => 'ids',
						)
					);
					$final_result = array();
					foreach ( $result as $key => $value ) {
						$final_result[]['post_id'] = $value;
					}
					break;
				default:
					$sql = false;
					break;
			}
		}

		if ( 'count' === $action ) {
			$final_result = ! empty( $final_result ) ? count( $final_result ) : 0;
		}

		return $final_result;
	}
}

