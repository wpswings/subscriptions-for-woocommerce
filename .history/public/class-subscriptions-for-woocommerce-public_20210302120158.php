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

	/**
	 * This function is used to show subscription price on single product page.
	 *
	 * @name mwb_sfw_price_html_subscription_product
	 * @param string $price product price.
	 * @param object $product Product
	 * @since    1.0.0
	 */
	public function mwb_sfw_price_html_subscription_product( $price, $product ) {
		
		if( !$this->mwb_sfw_check_product_is_subscription( $product ) ) {
			return $price;
		}
		$price = $this->mwb_sfw_subscription_product_get_price_html( $price, $product );
		return $price;
	}

	/**
	 * This function is used to check product is subscription or not.
	 *
	 * @name mwb_sfw_check_product_is_subscription
	 * @param object $product Product
	 * @since    1.0.0
	 */
	public function mwb_sfw_check_product_is_subscription( $product ) {
		
		$mwb_is_subscription = false;
		if( is_object( $product ) ) {
			$product_id = $product->get_id();
			$mwb_subscription_product = get_post_meta( $product_id, '_mwb_sfw_product', true );
			if( 'yes'=== $mwb_subscription_product ){
				$mwb_is_subscription = true;
			}
		}
		
		return $mwb_is_subscription;
	}

	public function mwb_sfw_subscription_product_get_price_html( $price, $product ) {
		
		if( is_object( $product ) ) {
			$product_id = $product->get_id();
			$mwb_sfw_subscription_number = get_post_meta( $product_id,'mwb_sfw_subscription_number', true );
			$mwb_sfw_subscription_expiry_number = get_post_meta( $product_id,'mwb_sfw_subscription_expiry_number', true );
			$mwb_sfw_subscription_interval = get_post_meta( $product_id,'mwb_sfw_subscription_interval', true );
			
			
			if( isset( $mwb_sfw_subscription_expiry_number ) && ! empty( $mwb_sfw_subscription_expiry_number ) ) {
				$mwb_sfw_subscription_expiry_interval = get_post_meta( $product_id,'mwb_sfw_subscription_expiry_interval', true );
				
				$mwb_price_html = $this->mwb_sfw_get_time_interval( $mwb_sfw_subscription_expiry_number, $mwb_sfw_subscription_expiry_interval );
				$price .= __(' For ','subscriptions-for-woocommerce') . $mwb_price_html;
				
				$price = $this->mwb_sfw_get_free_trial_period_html( $product_id, $price );
				$price = $this->mwb_sfw_get_initial_signup_fee_html( $product_id, $price );
			}
			elseif( isset( $mwb_sfw_subscription_number ) && ! empty( $mwb_sfw_subscription_number ) ) {
				$mwb_price_html = $this->mwb_sfw_get_time_interval( $mwb_sfw_subscription_number, $mwb_sfw_subscription_interval );
				$price .= ' / ' . $mwb_price_html;
				$price = $this->mwb_sfw_get_free_trial_period_html( $product_id, $price );
				$price = $this->mwb_sfw_get_initial_signup_fee_html( $product_id, $price );
				
			}
		}
		return $price;
	}

	public function mwb_sfw_get_time_interval( $mwb_sfw_subscription_number, $mwb_sfw_subscription_interval ) {
		
		switch( $mwb_sfw_subscription_interval ){
			case 'day':
				$mwb_price_html = sprintf( _n( '%s Day', '%s Days', $mwb_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'week':
				$mwb_price_html = sprintf( _n( '%s Week', '%s Weeks', $mwb_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'month':
				$mwb_price_html = sprintf( _n( '%s Month', '%s Months', $mwb_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'year':
				$mwb_price_html = sprintf( _n( '%s Year', '%s Years', $mwb_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
		}
		return $mwb_price_html;

	}

	public function mwb_sfw_get_initial_signup_fee_html( $product_id, $price ){
		$mwb_sfw_subscription_initial_signup_price = get_post_meta( $product_id,'mwb_sfw_subscription_initial_signup_price', true );
		if( isset( $mwb_sfw_subscription_initial_signup_price ) && !empty( $mwb_sfw_subscription_initial_signup_price ) ) {
			$price .= __(' and ','subscriptions-for-woocommerce') . wc_price( $mwb_sfw_subscription_initial_signup_price ) .__(' Sign up fee','subscriptions-for-woocommerce');
		}
		return $price;
	}

	public function mwb_sfw_get_free_trial_period_html( $product_id, $price ){
		
		$mwb_sfw_subscription_free_trial_number = get_post_meta( $product_id,'mwb_sfw_subscription_free_trial_number', true );
		$mwb_sfw_subscription_free_trial_interval = get_post_meta( $product_id,'mwb_sfw_subscription_free_trial_interval', true );
		if( isset( $mwb_sfw_subscription_free_trial_number ) && !empty( $mwb_sfw_subscription_free_trial_number ) ) {
			$mwb_price_html = $this->mwb_sfw_get_time_interval( $mwb_sfw_subscription_free_trial_number, $mwb_sfw_subscription_free_trial_interval );
			
			$price .= __(' and ','subscriptions-for-woocommerce') . $mwb_price_html  .__(' free trial','subscriptions-for-woocommerce');
		}
		return $price;
	}

	public function mwb_sfw_product_add_to_cart_text( $text, $product ){
		
		if( $this->mwb_sfw_check_product_is_subscription( $product ) ) {
			$mwb_add_to_cart_text = $this->$mwb_sfw_get_add_to_cart_button_text();
			if( isset( $mwb_add_to_cart_text ) && !empty( $mwb_add_to_cart_text ) ) {
				
			}
		} 
	
		return $text;
	}

	public function mwb_sfw_get_add_to_cart_button_text(){
		
		$mwb_add_to_cart_text = get_option( 'mwb_sfw_add_to_cart_text', '' );
		return $mwb_add_to_cart_text;
	}

}
