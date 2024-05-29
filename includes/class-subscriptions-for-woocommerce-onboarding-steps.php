<?php
/**
 * The admin-specific on-boarding functionality of the plugin.
 *
 * @link       https://wpswings.com
 * @since      1.0.0
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/includes
 */

/**
 * The Onboarding-specific functionality of the plugin admin side.
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/includes
 * @author      WP Swings <webmaster@wpswings.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( class_exists( 'Subscriptions_For_Woocommerce_Onboarding_Steps' ) ) {
	return;
}
/**
 * Define class and module for onboarding steps.
 */
class Subscriptions_For_Woocommerce_Onboarding_Steps {

	/**
	 * The single instance of the class.
	 *
	 * @since   1.0.0
	 * @var $_instance object of onboarding.
	 */
	protected static $_instance = null;

	/**
	 * Base url of hubspot api for subscriptions-for-woocommerce.
	 *
	 * @since 1.0.0
	 * @var string base url of API.
	 */
	private $wps_sfw_base_url = 'https://api.hsforms.com/';

	/**
	 * Portal id of hubspot api for subscriptions-for-woocommerce.
	 *
	 * @since 1.0.0
	 * @var string Portal id.
	 */
	private static $wps_sfw_portal_id = '25444144'; // Wp Swings portal.

	/**
	 * Form id of hubspot api for subscriptions-for-woocommerce.
	 *
	 * @since 1.0.0
	 * @var string Form id.
	 */
	private static $wps_sfw_onboarding_form_id = '2a2fe23c-0024-43f5-9473-cbfefdb06fe2';

	/**
	 * Form id of hubspot api for subscriptions-for-woocommerce.
	 *
	 * @since 1.0.0
	 * @var string Form id.
	 */
	private static $wps_sfw_deactivation_form_id = '67feecaa-9a93-4fda-8f85-f73168da2672';

	/**
	 * Define some variables for subscriptions-for-woocommerce.
	 *
	 * @since 1.0.0
	 * @var string $wps_sfw_plugin_name plugin name.
	 */
	private static $wps_sfw_plugin_name;

	/**
	 * Define some variables for subscriptions-for-woocommerce.
	 *
	 * @since 1.0.0
	 * @var string $wps_sfw_plugin_name_label plugin name text.
	 */
	private static $wps_sfw_plugin_name_label;

	/**
	 * Define some variables for subscriptions-for-woocommerce.
	 *
	 * @var string $wps_sfw_store_name store name.
	 * @since 1.0.0
	 */
	private static $wps_sfw_store_name;

	/**
	 * Define some variables for subscriptions-for-woocommerce.
	 *
	 * @since 1.0.0
	 * @var string $wps_sfw_store_url store url.
	 */
	private static $wps_sfw_store_url;

	/**
	 * Define the onboarding functionality of the plugin.
	 *
	 * Set the plugin name and the store name and store url that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		self::$wps_sfw_store_name = get_bloginfo( 'name' );
		self::$wps_sfw_store_url = home_url();
		self::$wps_sfw_plugin_name = 'Subscriptions For WooCommerce';
		self::$wps_sfw_plugin_name_label = 'Subscriptions For WooCommerce';

		add_action( 'admin_enqueue_scripts', array( $this, 'wps_sfw_onboarding_enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wps_sfw_onboarding_enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'wps_sfw_add_onboarding_popup_screen' ) );
		add_action( 'admin_footer', array( $this, 'wps_sfw_add_deactivation_popup_screen' ) );

		add_filter( 'wps_sfw_on_boarding_form_fields', array( $this, 'wps_sfw_add_on_boarding_form_fields' ) );
		add_filter( 'wps_sfw_deactivation_form_fields', array( $this, 'wps_sfw_add_deactivation_form_fields' ) );

		// Ajax to send data.
		add_action( 'wp_ajax_wps_sfw_send_onboarding_data', array( $this, 'wps_sfw_send_onboarding_data' ) );
		add_action( 'wp_ajax_nopriv_wps_sfw_send_onboarding_data', array( $this, 'wps_sfw_send_onboarding_data' ) );

		// Ajax to Skip popup.
		add_action( 'wp_ajax_sfw_skip_onboarding_popup', array( $this, 'wps_sfw_skip_onboarding_popup' ) );
		add_action( 'wp_ajax_nopriv_sfw_skip_onboarding_popup', array( $this, 'wps_sfw_skip_onboarding_popup' ) );
	}

	/**
	 * Main Onboarding steps Instance.
	 *
	 * Ensures only one instance of Onboarding functionality is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return Onboarding Steps - Main instance.
	 */
	public static function get_instance() {

		if ( is_null( self::$_instance ) ) {

			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * This function is provided for demonstration purposes only.
	 *
	 * An instance of this class should be passed to the run() function
	 * defined in wpswing_Onboarding_Loader as all of the hooks are defined
	 * in that particular class.
	 *
	 * The wpswing_Onboarding_Loader will then create the relationship
	 * between the defined hooks and the functions defined in this
	 * class.
	 */
	public function wps_sfw_onboarding_enqueue_styles() {
		global $pagenow;
		$is_valid = false;
		if ( ! $is_valid && 'plugins.php' == $pagenow ) {
			$is_valid = true;
		}
		if ( $this->wps_sfw_valid_page_screen_check() || $is_valid ) {
			// comment the line of code Only when your plugin doesn't uses the Select2.
			wp_enqueue_style( 'wps-sfw-onboarding-select2-style', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/subscriptions-for-woocommerce-select2.css', array(), time(), 'all' );

			wp_enqueue_style( 'wps-sfw-meterial-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'wps-sfw-meterial-css2', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'wps-sfw-meterial-lite', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'wps-sfw-meterial-icons-css', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/icon.css', array(), time(), 'all' );

			wp_enqueue_style( 'wps-sfw-onboarding-style', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'onboarding/css/subscriptions-for-woocommerce-onboarding.css', array(), time(), 'all' );

		}
	}

	/**
	 * This function is provided for demonstration purposes only.
	 *
	 * An instance of this class should be passed to the run() function
	 * defined in wpswing_Onboarding_Loader as all of the hooks are defined
	 * in that particular class.
	 *
	 * The wpswing_Onboarding_Loader will then create the relationship
	 * between the defined hooks and the functions defined in this
	 * class.
	 */
	public function wps_sfw_onboarding_enqueue_scripts() {
		global $pagenow;
		$is_valid = false;
		if ( ! $is_valid && 'plugins.php' == $pagenow ) {
			$is_valid = true;
		}
		if ( ! wps_sfw_check_multistep() ) {
			return;
		}
		if ( $this->wps_sfw_valid_page_screen_check() || $is_valid ) {

			wp_enqueue_script( 'wps-sfw-onboarding-select2-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/subscriptions-for-woocommerce-select2.js', array( 'jquery' ), '1.0.0', false );

			wp_enqueue_script( 'wps-sfw-metarial-js', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.js', array(), time(), false );
			wp_enqueue_script( 'wps-sfw-metarial-js2', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.js', array(), time(), false );
			wp_enqueue_script( 'wps-sfw-metarial-lite', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.js', array(), time(), false );

			wp_enqueue_script( 'wps-sfw-onboarding-scripts', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'onboarding/js/subscriptions-for-woocommerce-onboarding.js', array( 'jquery', 'wps-sfw-onboarding-select2-js', 'wps-sfw-metarial-js', 'wps-sfw-metarial-js2', 'wps-sfw-metarial-lite' ), time(), true );

			$sfw_current_slug = ! empty( explode( '/', plugin_basename( __FILE__ ) ) ) ? explode( '/', plugin_basename( __FILE__ ) )[0] : '';
			wp_localize_script(
				'wps-sfw-onboarding-scripts',
				'wps_sfw_onboarding',
				array(
					'ajaxurl'       => admin_url( 'admin-ajax.php' ),
					'sfw_auth_nonce'    => wp_create_nonce( 'wps_sfw_onboarding_nonce' ),
					'sfw_current_screen'    => $pagenow,
					'sfw_current_supported_slug'    => apply_filters( 'wps_sfw_deactivation_supported_slug', array( $sfw_current_slug ) ),
				)
			);
		}
	}

	/**
	 * Get all valid screens to add scripts and templates for subscriptions-for-woocommerce.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_add_onboarding_popup_screen() {
		if ( $this->wps_sfw_valid_page_screen_check() && $this->wps_sfw_show_onboarding_popup_check() ) {
			require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'onboarding/templates/subscriptions-for-woocommerce-onboarding-template.php';
		}
	}

	/**
	 * Get all valid screens to add scripts and templates for subscriptions-for-woocommerce.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_add_deactivation_popup_screen() {

		global $pagenow;
		if ( ! empty( $pagenow ) && 'plugins.php' == $pagenow ) {
			require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'onboarding/templates/subscriptions-for-woocommerce-deactivation-template.php';
		}
	}

	/**
	 * Skip the popup for some days of subscriptions-for-woocommerce.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_skip_onboarding_popup() {

		$get_skipped_timstamp = update_option( 'wps_sfw_onboarding_data_skipped', time() );
		echo json_encode( 'true' );
		wp_die();
	}


	/**
	 * Add your subscriptions-for-woocommerce onboarding form fields.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_add_on_boarding_form_fields() {

		$current_user = wp_get_current_user();
		if ( ! empty( $current_user ) ) {
			$current_user_email = $current_user->user_email ? $current_user->user_email : '';
		}

		if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
			$currency_symbol = get_woocommerce_currency_symbol();
		} else {
			$currency_symbol = '$';
		}

		/**
		 * Do not repeat id index.
		 */

		$fields = array(

			/**
			 * Input field with label.
			 * Radio field with label ( select only one ).
			 * Radio field with label ( select multiple one ).
			 * Checkbox radio with label ( select only one ).
			 * Checkbox field with label ( select multiple one ).
			 * Only Label ( select multiple one ).
			 * Select field with label ( select only one ).
			 * Select2 field with label ( select multiple one ).
			 * Email field with label. ( auto filled with admin email )
			 */

			rand() => array(
				'id' => 'wps-sfw-monthly-revenue',
				'title' => esc_html__( 'What is your monthly revenue?', 'subscriptions-for-woocommerce' ),
				'type' => 'radio',
				'description' => '',
				'name' => 'monthly_revenue_',
				'value' => '',
				'multiple' => 'no',
				'placeholder' => '',
				'required' => 'yes',
				'class' => '',
				'options' => array(
					'0-500'         => $currency_symbol . '0-' . $currency_symbol . '500',
					'501-5000'          => $currency_symbol . '501-' . $currency_symbol . '5000',
					'5001-10000'        => $currency_symbol . '5001-' . $currency_symbol . '10000',
					'10000+'        => $currency_symbol . '10000+',
				),
			),

			rand() => array(
				'id' => 'wps_sfw_industry_type',
				'title' => esc_html__( 'What industry defines your business?', 'subscriptions-for-woocommerce' ),
				'type' => 'select',
				'name' => 'industry_type_',
				'value' => '',
				'description' => '',
				'multiple' => 'yes',
				'placeholder' => esc_html__( 'Industry Type', 'subscriptions-for-woocommerce' ),
				'required' => 'yes',
				'class' => '',
				'options' => array(
					'agency'                => __( 'Agency', 'subscriptions-for-woocommerce' ),
					'consumer-services'     => __( 'Consumer Services', 'subscriptions-for-woocommerce' ),
					'ecommerce'             => __( 'Ecommerce', 'subscriptions-for-woocommerce' ),
					'financial-services'    => __( 'Financial Services', 'subscriptions-for-woocommerce' ),
					'healthcare'            => __( 'Healthcare', 'subscriptions-for-woocommerce' ),
					'manufacturing'         => __( 'Manufacturing', 'subscriptions-for-woocommerce' ),
					'nonprofit-and-education' => __( 'Nonprofit and Education', 'subscriptions-for-woocommerce' ),
					'professional-services' => __( 'Professional Services', 'subscriptions-for-woocommerce' ),
					'real-estate'           => __( 'Real Estate', 'subscriptions-for-woocommerce' ),
					'software'              => __( 'Software', 'subscriptions-for-woocommerce' ),
					'startups'              => __( 'Startups', 'subscriptions-for-woocommerce' ),
					'restaurant'            => __( 'Restaurant', 'subscriptions-for-woocommerce' ),
					'fitness'               => __( 'Fitness', 'subscriptions-for-woocommerce' ),
					'jewelry'               => __( 'Jewelry', 'subscriptions-for-woocommerce' ),
					'beauty'                => __( 'Beauty', 'subscriptions-for-woocommerce' ),
					'celebrity'             => __( 'Celebrity', 'subscriptions-for-woocommerce' ),
					'gaming'                => __( 'Gaming', 'subscriptions-for-woocommerce' ),
					'government'            => __( 'Government', 'subscriptions-for-woocommerce' ),
					'sports'                => __( 'Sports', 'subscriptions-for-woocommerce' ),
					'retail-store'          => __( 'Retail Store', 'subscriptions-for-woocommerce' ),
					'travel'                => __( 'Travel', 'subscriptions-for-woocommerce' ),
					'political-campaign'    => __( 'Political Campaign', 'subscriptions-for-woocommerce' ),
				),
			),

			rand() => array(
				'id' => 'wps-sfw-onboard-email',
				'title' => esc_html__( 'What is the best email address to contact you?', 'subscriptions-for-woocommerce' ),
				'type' => 'email',
				'description' => '',
				'name' => 'email',
				'placeholder' => esc_html__( 'Email', 'subscriptions-for-woocommerce' ),
				'value' => $current_user_email,
				'required' => 'yes',
				'class' => 'sfw-text-class',
			),

			rand() => array(
				'id' => 'wps-sfw-onboard-number',
				'title' => esc_html__( 'What is your contact number?', 'subscriptions-for-woocommerce' ),
				'type' => 'number',
				'description' => '',
				'name' => 'phone',
				'value' => '',
				'placeholder' => esc_html__( 'Contact Number', 'subscriptions-for-woocommerce' ),
				'required' => 'yes',
				'class' => '',
			),

			rand() => array(
				'id' => 'wps-sfw-store-name',
				'title' => '',
				'description' => '',
				'type' => 'hidden',
				'name' => 'company',
				'placeholder' => '',
				'value' => self::$wps_sfw_store_name,
				'required' => '',
				'class' => '',
			),

			rand() => array(
				'id' => 'wps-sfw-store-url',
				'title' => '',
				'description' => '',
				'type' => 'hidden',
				'name' => 'website',
				'placeholder' => '',
				'value' => self::$wps_sfw_store_url,
				'required' => '',
				'class' => '',
			),

			rand() => array(
				'id' => 'wps-sfw-show-counter',
				'title' => '',
				'description' => '',
				'type' => 'hidden',
				'placeholder' => '',
				'name' => 'wps-sfw-show-counter',
				'value' => get_option( 'wps_sfw_onboarding_data_sent', 'not-sent' ),
				'required' => '',
				'class' => '',
			),

			rand() => array(
				'id' => 'wps-sfw-plugin-name',
				'title' => '',
				'description' => '',
				'type' => 'hidden',
				'placeholder' => '',
				'name' => 'org_plugin_name',
				'value' => self::$wps_sfw_plugin_name,
				'required' => '',
				'class' => '',
			),
		);

		return $fields;
	}


	/**
	 * Add your subscriptions-for-woocommerce deactivation form fields.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_add_deactivation_form_fields() {

		$current_user = wp_get_current_user();
		if ( ! empty( $current_user ) ) {
			$current_user_email = $current_user->user_email ? $current_user->user_email : '';
		}

		/**
		 * Do not repeat id index.
		 */

		$fields = array(

			/**
			 * Input field with label.
			 * Radio field with label ( select only one ).
			 * Radio field with label ( select multiple one ).
			 * Checkbox radio with label ( select only one ).
			 * Checkbox field with label ( select multiple one ).
			 * Only Label ( select multiple one ).
			 * Select field with label ( select only one ).
			 * Select2 field with label ( select multiple one ).
			 * Email field with label. ( auto filled with admin email )
			 */

			rand() => array(
				'id' => 'wps-sfw-deactivation-reason',
				'title' => '',
				'description' => '',
				'type' => 'radio',
				'placeholder' => '',
				'name' => 'plugin_deactivation_reason',
				'value' => '',
				'multiple' => 'no',
				'required' => 'yes',
				'class' => 'sfw-radio-class',
				'options' => array(
					'temporary-deactivation-for-debug'      => __( 'It is a temporary deactivation. I am just debugging an issue.', 'subscriptions-for-woocommerce' ),
					'site-layout-broke'         => __( 'The plugin broke my layout or some functionality.', 'subscriptions-for-woocommerce' ),
					'complicated-configuration'         => __( 'The plugin is too complicated to configure.', 'subscriptions-for-woocommerce' ),
					'no-longer-need'        => __( 'I no longer need the plugin', 'subscriptions-for-woocommerce' ),
					'found-better-plugin'   => __( 'I found a better plugin', 'subscriptions-for-woocommerce' ),
					'other'         => __( 'Other', 'subscriptions-for-woocommerce' ),
				),
			),

			rand() => array(
				'id' => 'wps-sfw-deactivation-reason-text',
				/* translators: %s: plugin name */
				'title' => sprintf( esc_html__( ' Let us know why you are deactivating %s so we can improve the plugin', 'subscriptions-for-woocommerce' ), self::$wps_sfw_plugin_name_label ),
				'type' => 'textarea',
				'description' => '',
				'name' => 'deactivation_reason_text',
				'placeholder' => esc_html__( 'Reason', 'subscriptions-for-woocommerce' ),
				'value' => '',
				'required' => '',
				'class' => 'wps-keep-hidden',
			),

			rand() => array(
				'id' => 'wps-sfw-admin-email',
				'title' => '',
				'description' => '',
				'type' => 'hidden',
				'name' => 'email',
				'placeholder' => '',
				'value' => $current_user_email,
				'required' => '',
				'class' => '',
			),

			rand() => array(
				'id' => 'wps-sfw-store-name',
				'title' => '',
				'description' => '',
				'type' => 'hidden',
				'placeholder' => '',
				'name' => 'company',
				'value' => self::$wps_sfw_store_name,
				'required' => '',
				'class' => '',
			),

			rand() => array(
				'id' => 'wps-sfw-store-url',
				'title' => '',
				'description' => '',
				'type' => 'hidden',
				'name' => 'website',
				'placeholder' => '',
				'value' => self::$wps_sfw_store_url,
				'required' => '',
				'class' => '',
			),

			rand() => array(
				'id' => 'wps-sfw-plugin-name',
				'title' => '',
				'description' => '',
				'type' => 'hidden',
				'placeholder' => '',
				'name' => 'org_plugin_name',
				'value' => self::$wps_sfw_plugin_name,
				'required' => '',
				'class' => '',
			),
		);

		return $fields;
	}


	/**
	 * Send the data to Hubspot crm.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_send_onboarding_data() {

		check_ajax_referer( 'wps_sfw_onboarding_nonce', 'nonce' );

		$form_data = ! empty( $_POST['form_data'] ) ? map_deep( json_decode( sanitize_text_field( wp_unslash( $_POST['form_data'] ) ) ), 'sanitize_text_field' ) : '';

		$formatted_data = array();

		if ( ! empty( $form_data ) && is_array( $form_data ) ) {

			foreach ( $form_data as $key => $input ) {

				if ( 'wps-sfw-show-counter' == $input->name ) {
					continue;
				}

				if ( false !== strrpos( $input->name, '[]' ) ) {

					$new_key = str_replace( '[]', '', $input->name );
					$new_key = str_replace( '"', '', $new_key );

					array_push(
						$formatted_data,
						array(
							'name'  => $new_key,
							'value' => $input->value,
						)
					);

				} else {

					$input->name = str_replace( '"', '', $input->name );

					array_push(
						$formatted_data,
						array(
							'name'  => $input->name,
							'value' => $input->value,
						)
					);
				}
			}
		}

		try {

			$found = current(
				array_filter(
					$formatted_data,
					function ( $item ) {
						return isset( $item['name'] ) && 'plugin_deactivation_reason' == $item['name'];
					}
				)
			);

			if ( ! empty( $found ) ) {
				$action_type = 'deactivation';
			} else {
				$action_type = 'onboarding';
			}

			if ( ! empty( $formatted_data ) && is_array( $formatted_data ) ) {

				unset( $formatted_data['wps-sfw-show-counter'] );

				$result = $this->wps_sfw_handle_form_submission_for_hubspot( $formatted_data, $action_type );
			}
		} catch ( Exception $e ) {

			echo json_encode( $e->getMessage() );
			wp_die();
		}

		if ( ! empty( $action_type ) && 'onboarding' == $action_type ) {
			 $get_skipped_timstamp = update_option( 'wps_sfw_onboarding_data_sent', 'sent' );
		}

		echo json_encode( $formatted_data );
		wp_die();
	}


	/**
	 * Handle subscriptions-for-woocommerce form submission.
	 *
	 * @param      bool   $submission       The resultant data of the form.
	 * @param      string $action_type      Type of action.
	 * @since    1.0.0
	 */
	protected function wps_sfw_handle_form_submission_for_hubspot( $submission = false, $action_type = 'onboarding' ) {

		if ( 'onboarding' == $action_type ) {
			array_push(
				$submission,
				array(
					'name'  => 'currency',
					'value' => get_woocommerce_currency(),
				)
			);
		}

		$result = $this->wps_sfw_hubwoo_submit_form( $submission, $action_type );

		if ( true == $result['success'] ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 *  Define subscriptions-for-woocommerce Onboarding Submission :: Get a form.
	 *
	 * @param      array  $form_data    form data.
	 * @param      string $action_type    type of action.
	 * @since       1.0.0
	 */
	protected function wps_sfw_hubwoo_submit_form( $form_data = array(), $action_type = 'onboarding' ) {

		if ( 'onboarding' == $action_type ) {
			$form_id = self::$wps_sfw_onboarding_form_id;
		} else {
			$form_id = self::$wps_sfw_deactivation_form_id;
		}

		$url = 'submissions/v3/integration/submit/' . self::$wps_sfw_portal_id . '/' . $form_id;

		$headers = 'Content-Type: application/json';

		$form_data = json_encode(
			array(
				'fields' => $form_data,
				'context'  => array(
					'pageUri' => self::$wps_sfw_store_url,
					'pageName' => self::$wps_sfw_store_name,
					'ipAddress' => $this->wps_sfw_get_client_ip(),
				),
			)
		);

		$response = $this->wps_sfw_hic_post( $url, $form_data, $headers );

		if ( 200 == $response['status_code'] ) {
			$result = json_decode( $response['response'], true );
			$result['success'] = true;
		} else {
			$result = $response;
		}

		return $result;
	}

	/**
	 * Handle Hubspot POST api calls.
	 *
	 * @since    1.0.0
	 * @param   string $endpoint   Url where the form data posted.
	 * @param   array  $post_params    form data that need to be send.
	 * @param   array  $headers    data that must be included in header for request.
	 */
	private function wps_sfw_hic_post( $endpoint, $post_params, $headers ) {

		$url = $this->wps_sfw_base_url . $endpoint;
		$request = array(
			'httpversion' => '1.0',
			'sslverify'   => false,
			'method'      => 'POST',
			'timeout'     => 45,
			'headers'     => $headers,
			'body'        => $post_params,
			'cookies'     => array(),
		);

		$response = wp_remote_post( $url, $request );

		if ( is_wp_error( $response ) ) {

			$status_code = 500;
			$response    = esc_html__( 'Unexpected Error Occured', 'subscriptions-for-woocommerce' );
			$errors      = $response;

		} else {

			$status_code = wp_remote_retrieve_response_code( $response );
			$response    = wp_remote_retrieve_body( $response );
			$errors      = $response;
		}

		return array(
			'status_code' => $status_code,
			'response'    => $response,
			'errors'      => $errors,
		);
	}


	/**
	 * Function to get the client IP address.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_get_client_ip() {
		$ipaddress = '';
		if ( getenv( 'HTTP_CLIENT_IP' ) ) {
			$ipaddress = getenv( 'HTTP_CLIENT_IP' );
		} else if ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
			$ipaddress = getenv( 'HTTP_X_FORWARDED_FOR' );
		} else if ( getenv( 'HTTP_X_FORWARDED' ) ) {
			$ipaddress = getenv( 'HTTP_X_FORWARDED' );
		} else if ( getenv( 'HTTP_FORWARDED_FOR' ) ) {
			$ipaddress = getenv( 'HTTP_FORWARDED_FOR' );
		} else if ( getenv( 'HTTP_FORWARDED' ) ) {
			$ipaddress = getenv( 'HTTP_FORWARDED' );
		} else if ( getenv( 'REMOTE_ADDR' ) ) {
			$ipaddress = getenv( 'REMOTE_ADDR' );
		} else {
			$ipaddress = 'UNKNOWN';
		}
		return $ipaddress;
	}

	/**
	 * Validate the popup to be shown on specific screen.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_valid_page_screen_check() {
		$wps_sfw_screen = get_current_screen();
		$wps_sfw_screen_ids = wps_sfw_get_page_screen();
		$wps_sfw_is_flag = false;
		if ( isset( $wps_sfw_screen->id ) && in_array( $wps_sfw_screen->id, $wps_sfw_screen_ids ) ) {
			$wps_sfw_is_flag = true;
		}

		return $wps_sfw_is_flag;
	}

	/**
	 * Show the popup based on condition.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_show_onboarding_popup_check() {

		$wps_sfw_is_already_sent = get_option( 'wps_sfw_onboarding_data_sent', false );

		// Already submitted the data.
		if ( ! empty( $wps_sfw_is_already_sent ) && 'sent' == $wps_sfw_is_already_sent ) {
			return false;
		}

		$wps_sfw_get_skipped_timstamp = get_option( 'wps_sfw_onboarding_data_skipped', false );
		if ( ! empty( $wps_sfw_get_skipped_timstamp ) ) {

			$wps_sfw_next_show = strtotime( '+2 days', $wps_sfw_get_skipped_timstamp );

			$wps_sfw_current_time = time();

			$wps_sfw_time_diff = $wps_sfw_next_show - $wps_sfw_current_time;

			if ( 0 < $wps_sfw_time_diff ) {
				return false;
			}
		}

		// By default Show.
		return true;
	}
}
