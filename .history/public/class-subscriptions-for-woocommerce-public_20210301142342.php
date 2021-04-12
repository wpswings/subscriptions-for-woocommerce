<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://makewebbetter.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 * namespace subscriptions_for_woocommerce_public.
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/public
 * @author     makewebbetter <webmaster@makewebbetter.com>
 */
class Subscriptions_For_Woocommerce_Public {

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
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function sfw_public_enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'public/src/scss/subscriptions-for-woocommerce-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function sfw_public_enqueue_scripts() {

		wp_register_script( $this->plugin_name, SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'public/src/js/subscriptions-for-woocommerce-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'sfw_public_param', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( $this->plugin_name );

	}

	public function mwb_sfw_price_html_subscription_product( $price, $product ) {
		
		$this->mwb_sfw_check_product_is_subscription( $product );

		return $price;
	}

	public function mwb_sfw_check_product_is_subscription( $product ) {
		$mwb_is_subscription = false;
		
		$product_id = $product->get_id();
		$mwb_subscription_product = get_post_meta( $product_id, '_mwb_sfw_product', true );
		print_r($mwb_subscription_product);
		//print_r($product);
		die('-->>');
	}

}
