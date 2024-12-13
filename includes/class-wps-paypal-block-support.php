<?php
/**
 * Provide a common view for the plugin
 *
 * This file is used to markup the common aspects of the plugin.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/include
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Extend the WPS Paypal Gateway
 */
final class WPS_Paypal_Block_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WPS_Paypal_Block_Support
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'wps_paypal';

	/**
	 * Extend the WPS Paypal Gateway function
	 *
	 * @return void
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_wps_paypal_settings', array() );
		$this->gateway  = new WC_Gateway_Wps_Paypal_Integration();
	}
	/**
	 * Extend the WPS Paypal Gateway function
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Extend the WPS Paypal Gateway function
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = 'wc-block/wps-sfw-wc-blocks.js';
		$script_asset_path = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'wc-block/wps-sfw-wc-blocks-asset.php';

		$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => '1.0.0',
		);
		$script_url        = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . $script_path;

		wp_register_script(
			'wps-paypal-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		$gateway_data = get_option( 'woocommerce_wps_paypal_settings', false );
		wp_localize_script(
			'wps-paypal-blocks',
			'wps_paypal_blocks_object',
			array(
				'gateway_title' => ( ! empty( $gateway_data ) && isset( $gateway_data['title'] ) ) ? $gateway_data['title'] : esc_html__( 'WPS PayPal', 'subscriptions-for-woocommerce' ),
			)
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wps-paypal-blocks', 'subscriptions-for-woocommerce', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'languages/' );
		}

		return array( 'wps-paypal-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
		);
	}
}
