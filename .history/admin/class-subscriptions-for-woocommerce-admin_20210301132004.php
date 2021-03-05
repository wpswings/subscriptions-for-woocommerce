<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://makewebbetter.com/
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
 * @author     makewebbetter <webmaster@makewebbetter.com>
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
	public function sfw_admin_enqueue_styles( $hook ) {
		$screen = get_current_screen();
		if ( isset( $screen->id ) && 'makewebbetter_page_subscriptions_for_woocommerce_menu' == $screen->id ) {

			wp_enqueue_style( 'mwb-sfw-select2-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/subscriptions-for-woocommerce-select2.css', array(), time(), 'all' );

			wp_enqueue_style( 'mwb-sfw-meterial-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'mwb-sfw-meterial-css2', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'mwb-sfw-meterial-lite', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.css', array(), time(), 'all' );

			wp_enqueue_style( 'mwb-sfw-meterial-icons-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/icon.css', array(), time(), 'all' );

			wp_enqueue_style( $this->plugin_name . '-admin-global', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/src/scss/subscriptions-for-woocommerce-admin-global.css', array( 'mwb-sfw-meterial-icons-css' ), time(), 'all' );

			wp_enqueue_style( $this->plugin_name, SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/src/scss/subscriptions-for-woocommerce-admin.scss', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook      The plugin page slug.
	 */
	public function sfw_admin_enqueue_scripts( $hook ) {

		$screen = get_current_screen();
		if ( isset( $screen->id ) && 'makewebbetter_page_subscriptions_for_woocommerce_menu' == $screen->id ) {
			wp_enqueue_script( 'mwb-sfw-select2', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/subscriptions-for-woocommerce-select2.js', array( 'jquery' ), time(), false );

			wp_enqueue_script( 'mwb-sfw-metarial-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.js', array(), time(), false );
			wp_enqueue_script( 'mwb-sfw-metarial-js2', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.js', array(), time(), false );
			wp_enqueue_script( 'mwb-sfw-metarial-lite', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.js', array(), time(), false );

			wp_register_script( $this->plugin_name . 'admin-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/src/js/subscriptions-for-woocommerce-admin.js', array( 'jquery', 'mwb-sfw-select2', 'mwb-sfw-metarial-js', 'mwb-sfw-metarial-js2', 'mwb-sfw-metarial-lite' ), $this->version, false );

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
			wp_register_script( 'mwb-sfw-admin-single-product-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/src/js/subscription-for-woocommerce-product-edit.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'mwb-sfw-admin-single-product-js' );
		}
	}

	/**
	 * Adding settings menu for Subscriptions For Woocommerce.
	 *
	 * @since    1.0.0
	 */
	public function sfw_options_page() {
		global $submenu;
		if ( empty( $GLOBALS['admin_page_hooks']['mwb-plugins'] ) ) {
			add_menu_page( __( 'MakeWebBetter', 'subscriptions-for-woocommerce' ), __( 'MakeWebBetter', 'subscriptions-for-woocommerce' ), 'manage_options', 'mwb-plugins', array( $this, 'mwb_plugins_listing_page' ), SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/src/images/mwb-logo.png', 15 );
			$sfw_menus = apply_filters( 'mwb_add_plugins_menus_array', array() );
			if ( is_array( $sfw_menus ) && ! empty( $sfw_menus ) ) {
				foreach ( $sfw_menus as $sfw_key => $sfw_value ) {
					add_submenu_page( 'mwb-plugins', $sfw_value['name'], $sfw_value['name'], 'manage_options', $sfw_value['menu_link'], array( $sfw_value['instance'], $sfw_value['function'] ) );
				}
			}
		}
	}

	/**
	 * Removing default submenu of parent menu in backend dashboard
	 *
	 * @since   1.0.0
	 */
	public function mwb_sfw_remove_default_submenu() {
		global $submenu;
		if ( is_array( $submenu ) && array_key_exists( 'mwb-plugins', $submenu ) ) {
			if ( isset( $submenu['mwb-plugins'][0] ) ) {
				unset( $submenu['mwb-plugins'][0] );
			}
		}
	}


	/**
	 * Subscriptions For Woocommerce sfw_admin_submenu_page.
	 *
	 * @since 1.0.0
	 * @param array $menus Marketplace menus.
	 */
	public function sfw_admin_submenu_page( $menus = array() ) {
		$menus[] = array(
			'name'            => __( 'Subscriptions For Woocommerce', 'subscriptions-for-woocommerce' ),
			'slug'            => 'subscriptions_for_woocommerce_menu',
			'menu_link'       => 'subscriptions_for_woocommerce_menu',
			'instance'        => $this,
			'function'        => 'sfw_options_menu_html',
		);
		return $menus;
	}


	/**
	 * Subscriptions For Woocommerce mwb_plugins_listing_page.
	 *
	 * @since 1.0.0
	 */
	public function mwb_plugins_listing_page() {
		$active_marketplaces = apply_filters( 'mwb_add_plugins_menus_array', array() );
		if ( is_array( $active_marketplaces ) && ! empty( $active_marketplaces ) ) {
			require SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/welcome.php';
		}
	}

	/**
	 * Subscriptions For Woocommerce admin menu page.
	 *
	 * @since    1.0.0
	 */
	public function sfw_options_menu_html() {

		include_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/subscriptions-for-woocommerce-admin-dashboard.php';
	}


	/**
	 * Subscriptions For Woocommerce admin menu page.
	 *
	 * @since    1.0.0
	 * @param array $sfw_settings_general Settings fields.
	 */
	public function sfw_admin_general_settings_page( $sfw_settings_general ) {

		$sfw_settings_general = array(
			
			array(
				'title' => __( 'Enable/Disable Subscription', 'subscriptions-for-woocommerce' ),
				'type'  => 'checkbox',
				'description'  => __( 'Check this box to enable the subscription.', 'subscriptions-for-woocommerce' ),
				'id'    => 'mwb_sfw_enable_plugin',
				'class' => 'sfw-checkbox-class',
				'value' => 'on',
				'checked' => ('on' === get_option( 'mwb_sfw_enable_plugin', '' ) ? 'on' : 'off' ),
			),
			array(
				'title' => __( 'Add to cart text', 'subscriptions-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Use this option to change add to cart button text.', 'subscriptions-for-woocommerce' ),
				'id'    => 'mwb_sfw_add_to_cart_text',
				'value' => get_option( 'mwb_sfw_add_to_cart_text', ''),
				'class' => 'sfw-text-class',
				'placeholder' => __( 'Add to cart button text', 'subscriptions-for-woocommerce' ),
			),
			array(
				'title' => __( 'Place order text', 'subscriptions-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Use this option to place order button text.', 'subscriptions-for-woocommerce' ),
				'id'    => 'mwb_sfw_place_order_button_text',
				'value' => get_option( 'mwb_sfw_place_order_button_text', ''),
				'class' => 'sfw-text-class',
				'placeholder' => __( 'Place order button text', 'subscriptions-for-woocommerce' ),
			),
			array(
				'title' => __( 'Allow Customer to cancel Subscription', 'subscriptions-for-woocommerce' ),
				'type'  => 'checkbox',
				'description'  => __( 'Enable this option to allow customer to cancel subscription.', 'subscriptions-for-woocommerce' ),
				'id'    => 'mwb_sfw_cancel_subscription_for_customer',
				'value' => 'on',
				'checked' => ('on' === get_option( 'mwb_sfw_cancel_subscription_for_customer', '' ) ? 'on' : 'off' ),
				'class' => 'sfw-checkbox-class',
			),
			array(
				'type'  => 'button',
				'id'    => 'mwb_sfw_save_general_settings',
				'button_text' => __( 'Save Settings', 'subscriptions-for-woocommerce' ),
				'class' => 'sfw-button-class',
			),
		);
		return apply_filters( 'mwb_sfw_add_general_settings_fields', $sfw_settings_general );
		
	}

	/**
	 * Subscriptions For Woocommerce admin menu page.
	 *
	 * @since    1.0.0
	 * @param array $sfw_settings_template Settings fields.
	 */
	public function sfw_admin_template_settings_page( $sfw_settings_template ) {
		$sfw_settings_template = array(
			array(
				'title' => __( 'Text Field Demo', 'subscriptions-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'This is text field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_text_demo',
				'value' => '',
				'class' => 'sfw-text-class',
				'placeholder' => __( 'Text Demo', 'subscriptions-for-woocommerce' ),
			),
			array(
				'title' => __( 'Number Field Demo', 'subscriptions-for-woocommerce' ),
				'type'  => 'number',
				'description'  => __( 'This is number field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_number_demo',
				'value' => '',
				'class' => 'sfw-number-class',
				'placeholder' => '',
			),
			array(
				'title' => __( 'Password Field Demo', 'subscriptions-for-woocommerce' ),
				'type'  => 'password',
				'description'  => __( 'This is password field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_password_demo',
				'value' => '',
				'class' => 'sfw-password-class',
				'placeholder' => '',
			),
			array(
				'title' => __( 'Textarea Field Demo', 'subscriptions-for-woocommerce' ),
				'type'  => 'textarea',
				'description'  => __( 'This is textarea field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_textarea_demo',
				'value' => '',
				'class' => 'sfw-textarea-class',
				'rows' => '5',
				'cols' => '10',
				'placeholder' => __( 'Textarea Demo', 'subscriptions-for-woocommerce' ),
			),
			array(
				'title' => __( 'Select Field Demo', 'subscriptions-for-woocommerce' ),
				'type'  => 'select',
				'description'  => __( 'This is select field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_select_demo',
				'value' => '',
				'class' => 'sfw-select-class',
				'placeholder' => __( 'Select Demo', 'subscriptions-for-woocommerce' ),
				'options' => array(
					'' => __( 'Select option', 'subscriptions-for-woocommerce' ),
					'INR' => __( 'Rs.', 'subscriptions-for-woocommerce' ),
					'USD' => __( '$', 'subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Multiselect Field Demo', 'subscriptions-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'This is multiselect field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_multiselect_demo',
				'value' => '',
				'class' => 'sfw-multiselect-class mwb-defaut-multiselect',
				'placeholder' => '',
				'options' => array(
					'default' => __( 'Select currency code from options', 'subscriptions-for-woocommerce' ),
					'INR' => __( 'Rs.', 'subscriptions-for-woocommerce' ),
					'USD' => __( '$', 'subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Checkbox Field Demo', 'subscriptions-for-woocommerce' ),
				'type'  => 'checkbox',
				'description'  => __( 'This is checkbox field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_checkbox_demo',
				'value' => '',
				'class' => 'sfw-checkbox-class',
				'placeholder' => __( 'Checkbox Demo', 'subscriptions-for-woocommerce' ),
			),

			array(
				'title' => __( 'Radio Field Demo', 'subscriptions-for-woocommerce' ),
				'type'  => 'radio',
				'description'  => __( 'This is radio field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_radio_demo',
				'value' => '',
				'class' => 'sfw-radio-class',
				'placeholder' => __( 'Radio Demo', 'subscriptions-for-woocommerce' ),
				'options' => array(
					'yes' => __( 'YES', 'subscriptions-for-woocommerce' ),
					'no' => __( 'NO', 'subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable', 'subscriptions-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'This is switch field demo follow same structure for further use.', 'subscriptions-for-woocommerce' ),
				'id'    => 'sfw_radio_switch_demo',
				'value' => '',
				'class' => 'sfw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'subscriptions-for-woocommerce' ),
					'no' => __( 'NO', 'subscriptions-for-woocommerce' ),
				),
			),

			array(
				'type'  => 'button',
				'id'    => 'sfw_button_demo',
				'button_text' => __( 'Button Demo', 'subscriptions-for-woocommerce' ),
				'class' => 'sfw-button-class',
			),
		);
		return $sfw_settings_template;
	}


	/**
	 * Subscriptions For Woocommerce support page tabs.
	 *
	 * @since    1.0.0
	 * @param    Array $mwb_sfw_support Settings fields.
	 * @return   Array  $mwb_sfw_support
	 */
	public function sfw_admin_support_settings_page( $mwb_sfw_support ) {
		$mwb_sfw_support = array(
			array(
				'title' => __( 'User Guide', 'subscriptions-for-woocommerce' ),
				'description' => __( 'View the detailed guides and documentation to set up your plugin.', 'subscriptions-for-woocommerce' ),
				'link-text' => __( 'VIEW', 'subscriptions-for-woocommerce' ),
				'link' => '',
			),
			array(
				'title' => __( 'Free Support', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Please submit a ticket , our team will respond within 24 hours.', 'subscriptions-for-woocommerce' ),
				'link-text' => __( 'SUBMIT', 'subscriptions-for-woocommerce' ),
				'link' => '',
			),
		);

		return apply_filters( 'mwb_sfw_add_support_content', $mwb_sfw_support );
	}

	/**
	* Subscriptions For Woocommerce save tab settings.
	*
	* @since 1.0.0
	*/
	public function sfw_admin_save_tab_settings() {
		global $sfw_mwb_sfw_obj;
		if ( isset( $_POST['mwb_sfw_save_general_settings'] )  && isset( $_POST['mwb-sfw-general-nonce-field'] ) ) {
			$mwb_sfw_geberal_nonce = sanitize_text_field( wp_unslash( $_POST['mwb-sfw-general-nonce-field'] ) );
			if ( wp_verify_nonce( $mwb_sfw_geberal_nonce, 'mwb-sfw-general-nonce' ) ) {
				$mwb_sfw_gen_flag = false;
				$sfw_genaral_settings = apply_filters( 'sfw_general_settings_array', array() );
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
								}
								else{
									update_option( $sfw_genaral_setting['id'],'');
								}
							}else{
								$mwb_sfw_gen_flag = true;
							}
						}
					}
					if ( $mwb_sfw_gen_flag ) {
						$mwb_sfw_error_text = esc_html__( 'Id of some field is missing', 'subscriptions-for-woocommerce' );
						$sfw_mwb_sfw_obj->mwb_sfw_plug_admin_notice( $mwb_sfw_error_text, 'error' );
					}else{
						$mwb_sfw_error_text = esc_html__( 'Settings saved !', 'subscriptions-for-woocommerce' );
						$sfw_mwb_sfw_obj->mwb_sfw_plug_admin_notice( $mwb_sfw_error_text, 'success' );
					}
				}
			}
			
		}
	}

	public function mwb_sfw_create_subscription_product_type( $products_type ) {
		$products_type['mwb_sfw_product'] = array(
				'id'            => '_mwb_sfw_product',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Subscriptions', 'subscriptions-for-woocommerce' ),
				'description'   => __( 'This is the Subscriptions type product.', 'subscriptions-for-woocommerce' ),
				'default'       => 'no',
			);
		return $products_type;
			
	}

	public function mwb_sfw_save_custom_fields_for_single_products( $post_id) {

		$mwb_sfw_product = isset( $_POST['_mwb_sfw_product'] ) ? 'yes' : 'no';
    	update_post_meta( $post_id, '_mwb_sfw_product', $mwb_sfw_product );
	}

	public function mwb_sfw_custom_product_tab_for_subscription( $tabs ){
		$tabs['mwb_sfw_product'] = array(
			'label'    => __('Subscriptions Settings','subscriptions-for-woocommerce'),
			'target'   => 'mwb_sfw_product_target_section',
			'class'    => array(),
			'priority' => 80,
		);
		return $tabs;
	}

	public function mwb_sfw_subscription_period( ) {
		$subscription_interval = array(
			'day' => __( 'Days', 'subscriptions-for-woocommerce' ),
			'week' => __( 'Weeks', 'subscriptions-for-woocommerce' ),
			'month' => __( 'Months', 'subscriptions-for-woocommerce' ),
			'year' => __( 'Years', 'subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'mwb_sfw_subscription_intervals', $subscription_interval );
	}
	public function mwb_sfw_custom_product_fields_for_subscription(){
		global $post;
		$post_id = $post->ID;
		$product = wc_get_product( $post_id );

		$mwb_sfw_subscription_number = get_post_meta( $post_id,'mwb_sfw_subscription_number', true );
		$mwb_sfw_subscription_interval = get_post_meta( $post_id,'mwb_sfw_subscription_interval', true );
		$mwb_sfw_subscription_expiry_number = get_post_meta( $post_id,'mwb_sfw_subscription_expiry_number', true );
		$mwb_sfw_subscription_expiry_interval = get_post_meta( $post_id,'mwb_sfw_subscription_expiry_interval', true );
		$mwb_sfw_subscription_initial_signup_price = get_post_meta( $post_id,'mwb_sfw_subscription_initial_signup_price', true );
		$mwb_sfw_subscription_free_trial_number = get_post_meta( $post_id,'mwb_sfw_subscription_free_trial_number', true );
		$mwb_sfw_subscription_free_trial_interval = get_post_meta( $post_id,'mwb_sfw_subscription_free_trial_interval', true );
		?>
		<div id="mwb_sfw_product_target_section" class="panel woocommerce_options_panel hidden">

		<p class="form-field mwb_sfw_subscription_number_field ">
			<label for="mwb_sfw_subscription_number">
			<?php esc_html_e('Subscriptions Per Interval','subscriptions-for-woocommerce');?>
			</label>
			<input type="number" class="short wc_input_number"  name="mwb_sfw_subscription_number" id="mwb_sfw_subscription_number" value="<?php echo esc_attr( $mwb_sfw_subscription_number ); ?>" placeholder="<?php esc_html_e('Enter subscription interval','subscriptions-for-woocommerce');?>"> 
			<select id="mwb_sfw_subscription_interval" name="mwb_sfw_subscription_interval" class="mwb_sfw_subscription_interval" >
				<?php foreach ( $this->mwb_sfw_subscription_period() as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $mwb_sfw_subscription_interval, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php } ?>
				</select>
		 <?php 
		 	$description_text = __('Choose the subscriptions expiry time inteval for the product "for example 10 days"','subscriptions-for-woocommerce');
		 	echo wc_help_tip( $description_text ); 
		 ?>
		</p>
		<p class="form-field mwb_sfw_subscription_expiry_field ">
			<label for="mwb_sfw_subscription_expiry_number">
			<?php esc_html_e('Subscriptions Expiry Interval','subscriptions-for-woocommerce');?>
			</label>
			<input type="number" class="short wc_input_number"  name="mwb_sfw_subscription_expiry_number" id="mwb_sfw_subscription_expiry_number" value="<?php echo esc_attr( $mwb_sfw_subscription_expiry_number ); ?>" placeholder="<?php esc_html_e('Enter subscription expiry','subscriptions-for-woocommerce');?>"> 
			<select id="mwb_sfw_subscription_expiry_interval" name="mwb_sfw_subscription_expiry_interval" class="mwb_sfw_subscription_expiry_interval" >
				<?php foreach ( $this->mwb_sfw_subscription_period() as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $mwb_sfw_subscription_expiry_interval, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php } ?>
				</select>
		 <?php 
			$description_text = __('Choose the subscriptions expiry time inteval for the product "for example 10 days"','subscriptions-for-woocommerce');
		 	echo wc_help_tip( $description_text ); 
		 ?>
		</p>
		<p class="form-field mwb_sfw_subscription_initial_signup_field ">
			<label for="mwb_sfw_subscription_initial_signup_price">
			<?php esc_html_e('Initial Signup fee','subscriptions-for-woocommerce'); echo esc_html('('.get_woocommerce_currency_symbol().')');?>
			</label>
			<input type="text" class="short wc_input_price"  name="mwb_sfw_subscription_initial_signup_price" id="mwb_sfw_subscription_initial_signup_price" value="<?php echo esc_attr( $mwb_sfw_subscription_initial_signup_price ); ?>" placeholder="<?php esc_html_e('Enter signup fee','subscriptions-for-woocommerce');?>"> 
			
		 <?php //echo wc_help_tip('test'); ?>
		</p>
		<p class="form-field mwb_sfw_subscription_free_trial_field ">
			<label for="mwb_sfw_subscription_free_trial_number">
			<?php esc_html_e('Free trial interval','subscriptions-for-woocommerce');?>
			</label>
			<input type="number" class="short wc_input_number"  name="mwb_sfw_subscription_free_trial_number" id="mwb_sfw_subscription_free_trial_number" value="<?php echo esc_attr( $mwb_sfw_subscription_free_trial_number ); ?>" placeholder="<?php esc_html_e('Enter free trial interval','subscriptions-for-woocommerce');?>"> 
			<select id="mwb_sfw_subscription_free_trial_interval" name="mwb_sfw_subscription_free_trial_interval" class="mwb_sfw_subscription_free_trial_interval" >
				<?php foreach ( $this->mwb_sfw_subscription_period() as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $mwb_sfw_subscription_free_trial_interval, true ) ?>><?php echo esc_html( $label ); ?></option>
				<?php } ?>
				</select>
		 <?php //echo wc_help_tip('test'); ?>
		</p>
		</div>
		<?php 
	}

	public function mwb_sfw_save_custom_product_fields_data_for_subscription( $post_id, $post ) {
		$mwb_sfw_subscription_number = isset( $_POST['mwb_sfw_subscription_number'] ) ? $_POST['mwb_sfw_subscription_number'] : '';
		$mwb_sfw_subscription_interval = isset( $_POST['mwb_sfw_subscription_interval'] ) ? $_POST['mwb_sfw_subscription_interval'] : '';
		$mwb_sfw_subscription_expiry_number = isset( $_POST['mwb_sfw_subscription_expiry_number'] ) ? $_POST['mwb_sfw_subscription_expiry_number'] : '';
		$mwb_sfw_subscription_expiry_interval = isset( $_POST['mwb_sfw_subscription_expiry_interval'] ) ? $_POST['mwb_sfw_subscription_expiry_interval'] : '';
		$mwb_sfw_subscription_initial_signup_price = isset( $_POST['mwb_sfw_subscription_initial_signup_price'] ) ? $_POST['mwb_sfw_subscription_initial_signup_price'] : '';
		$mwb_sfw_subscription_free_trial_number = isset( $_POST['mwb_sfw_subscription_free_trial_number'] ) ? $_POST['mwb_sfw_subscription_free_trial_number'] : '';
		$mwb_sfw_subscription_free_trial_interval = isset( $_POST['mwb_sfw_subscription_free_trial_interval'] ) ? $_POST['mwb_sfw_subscription_free_trial_interval'] : '';

		update_post_meta( $post_id,'mwb_sfw_subscription_number', $mwb_sfw_subscription_number );
		update_post_meta( $post_id,'mwb_sfw_subscription_interval', $mwb_sfw_subscription_interval );
		update_post_meta( $post_id,'mwb_sfw_subscription_expiry_number', $mwb_sfw_subscription_expiry_number );
		update_post_meta( $post_id,'mwb_sfw_subscription_expiry_interval', $mwb_sfw_subscription_expiry_interval );
		update_post_meta( $post_id,'mwb_sfw_subscription_initial_signup_price', $mwb_sfw_subscription_initial_signup_price );
		update_post_meta( $post_id,'mwb_sfw_subscription_free_trial_number', $mwb_sfw_subscription_free_trial_number );
		update_post_meta( $post_id,'mwb_sfw_subscription_free_trial_interval', $mwb_sfw_subscription_free_trial_interval );
	}

	
}
