<?php
/**
 * Fired during plugin activation
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/package/rest-api/version1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Subscriptions_For_Woocommerce_Api_Process' ) ) {

	/**
	 * The plugin API class.
	 *
	 * This is used to define the functions and data manipulation for custom endpoints.
	 *
	 * @since      1.0.0
	 * @package    Subscriptions_For_Woocommerce
	 * @subpackage Subscriptions_For_Woocommerce/package/rest-api/version1
	 * @author     WP Swings <wpswings.com>
	 */
	class Subscriptions_For_Woocommerce_Api_Process {

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {

		}

		/**
		 * Define the function to process data for custom endpoint.
		 *
		 * @since    1.0.0
		 * @param   Array $sfw_request  data of requesting headers and other information.
		 * @return  Array $wps_sfw_rest_response    returns processed data and status of operations.
		 */
		public function wps_sfw_default_process( $sfw_request ) {
			$wps_sfw_rest_response = array();

			// Write your custom code here.

			$wps_sfw_rest_response['status'] = 200;
			$wps_sfw_rest_response['data'] = $sfw_request->get_headers();
			return $wps_sfw_rest_response;
		}
	}
}
