<?php
/**
 * The file that defines the core plugin api class
 *
 * A class definition that includes api's endpoints and functions used across the plugin
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/package/rest-api/version1
 */

/**
 * The core plugin  api class.
 *
 * This is used to define internationalization, api-specific hooks, and
 * endpoints for plugin.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/package/rest-api/version1
 * @author     WP Swings <webmaster@wpswings.com>
 */
class Subscriptions_For_Woocommerce_Rest_Api {

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
	 * Define the core functionality of the plugin api.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the merthods, and set the hooks for the api and
	 *
	 * @since    1.0.0
	 * @param   string $plugin_name    Name of the plugin.
	 * @param   string $version        Version of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}


	/**
	 * Define endpoints for the plugin.
	 *
	 * Uses the Subscriptions_For_Woocommerce_Rest_Api class in order to create the endpoint
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function wps_sfw_add_endpoint() {
		register_rest_route(
			'wsp-route/v1',
			'/wsp-view-subscription/',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'wps_wsp_view_susbcription_callback' ),
				'permission_callback' => array( $this, 'wps_wsp_subscription_permission_check' ),
			)
		);
	}


	/**
	 * Begins validation process of api endpoint.
	 *
	 * @param   Array $request    All information related with the api request containing in this array.
	 * @return  Array   $result   return rest response to server from where the endpoint hits.
	 * @since    1.0.0
	 */
	public function wps_wsp_subscription_permission_check( $request ) {

		$request_params = $request->get_params();
		$wps_secretkey = isset( $request_params['consumer_secret'] ) ? $request_params['consumer_secret'] : '';

		$result = $this->wps_wsp_validate_secretkey( $wps_secretkey );

		return $result;
	}

	/**
	 * Valiadte secret key.
	 *
	 * @name wps_wsp_validate_secretkey
	 * @param   string $wps_secretkey  wps_secretkey.
	 * @since    1.0.0
	 */
	public function wps_wsp_validate_secretkey( $wps_secretkey ) {
		$wps_secret_code = '';

		if ( wps_wsp_check_api_enable() ) {
			$wps_secret_code = wps_wsp_api_get_secret_key();
		}

		if ( '' == $wps_secretkey ) {
			return false;
		} elseif ( trim( $wps_secret_code ) === trim( $wps_secretkey ) ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Begins execution of api endpoint.
	 *
	 * @param   Array $request    All information related with the api request containing in this array.
	 * @return  Array   $wps_sfw_response   return rest response to server from where the endpoint hits.
	 * @since    1.0.0
	 */
	public function wps_wsp_view_susbcription_callback( $request ) {

		require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'package/rest-api/version1/class-subscriptions-for-woocommerce-api-process.php';
		$wps_sfw_api_obj = new Subscriptions_For_Woocommerce_Api_Process();
		$wps_sfw_resultsdata = $wps_sfw_api_obj->wps_sfw_default_process( $request );
		if ( is_array( $wps_sfw_resultsdata ) && isset( $wps_sfw_resultsdata['code'] ) && 200 == $wps_sfw_resultsdata['code'] ) {

			$wps_wsp_response = new WP_REST_Response( $wps_sfw_resultsdata );
		} else {
			$wps_wsp_resultsdata = array(
				'status' => 'error',
				'code'   => 404,
				'message' => __( 'Data not found', 'subscriptions-for-woocommerce' ),

			);
			$wps_wsp_response = new WP_REST_Response( $wps_wsp_resultsdata );
		}
		return $wps_wsp_response;
	}
}
