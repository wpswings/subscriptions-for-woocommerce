<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpswings.com/
 * @since      1.5.8
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/public
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 * namespace subscriptions_for_woocommerce_public.
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/public
 * @author     WP Swings <webmaster@wpswings.com>
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
	public function wps_sfw_public_enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'public/css/subscriptions-for-woocommerce-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_public_enqueue_scripts() {

		wp_register_script( $this->plugin_name, SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'public/js/subscriptions-for-woocommerce-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'sfw_public_param',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'cart_url' => wc_get_cart_url(),
				'sfw_public_nonce'    => wp_create_nonce( 'wps_sfw_public_nonce' ),
			)
		);

		wp_enqueue_script( $this->plugin_name );

		if ( is_cart() || is_checkout() ) {
			wp_register_script( 'wps_sfw_wc_blocks', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'wc-block/cart-line-items.js', array( 'jquery', 'wp-data' ), $this->version, true );
			$text = null;
			$wps_sfw_place_order_button_text = $this->wps_sfw_get_place_order_button_text();
			if ( isset( $wps_sfw_place_order_button_text ) && ! empty( $wps_sfw_place_order_button_text ) && $this->wps_sfw_check_cart_has_subscription_product() ) {
				$text = $wps_sfw_place_order_button_text;
			}
			if ( ! empty( WC()->cart->cart_contents ) ) {
				foreach ( WC()->cart->cart_contents as $cart_item ) {
					if ( $cart_item['data']->get_type() == 'subscription_box' ) {
						$wps_sfw_subscription_box_place_order_button_text = get_option( 'wps_sfw_subscription_box_place_order_button_text', '' );
						$text = $wps_sfw_subscription_box_place_order_button_text;
					}
				}
			}
			wp_localize_script(
				'wps_sfw_wc_blocks',
				'sfw_public_block',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'place_order_button_text' => $text,
					'sfw_public_nonce'    => wp_create_nonce( 'wps_sfw_public_nonce' ),
				)
			);
			wp_enqueue_script( 'wps_sfw_wc_blocks' );
		}
	}

	/**
	 * This function is used to show subscription price on single product page.
	 *
	 * @name wps_sfw_price_html_subscription_product
	 * @param string $price product price.
	 * @param object $product Product.
	 * @since    1.0.0
	 */
	public function wps_sfw_price_html_subscription_product( $price, $product ) {
		if ( ! wps_sfw_check_product_is_subscription( $product ) || ! $price ) {
			return $price;
		}
		$product_type = $product->get_type();
		$product_id = $product->get_id();
		$wps_sfw_manage_subscription_box_price = wps_sfw_get_meta_data( $product_id, 'wps_sfw_manage_subscription_box_price', true );
		$is_pro = apply_filters( 'wsp_sfw_check_pro_plugin', false );
		if ( 'subscription_box' == $product_type && 'on' == $wps_sfw_manage_subscription_box_price && $is_pro ) {
			return '';
		}
		$price = apply_filters( 'wps_rbpfw_price', $price, $product );
		$price = $this->wps_sfw_subscription_product_get_price_html( $price, $product );
		do_action( 'wps_sfw_show_start_date_frontend', $product );
		return $price;
	}

	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name wps_sfw_subscription_product_get_price_html
	 * @param object $price price.
	 * @param string $product product.
	 * @param array  $cart_item cart_item.
	 * @since    1.0.0
	 */
	public function wps_sfw_subscription_product_get_price_html( $price, $product, $cart_item = array() ) {

		if ( is_object( $product ) ) {
			$product_id = $product->get_id();
			$wps_sfw_subscription_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_number', true );
			$wps_sfw_subscription_expiry_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_expiry_number', true );
			$wps_sfw_subscription_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_interval', true );

			if ( isset( $wps_sfw_subscription_number ) && ! empty( $wps_sfw_subscription_number ) ) {
				$wps_price_html = wps_sfw_get_time_interval_for_price( $wps_sfw_subscription_number, $wps_sfw_subscription_interval );

				/* translators: %s: susbcription interval */
				$wps_sfw_price_html = '<span class="wps_sfw_interval">' . sprintf( esc_html( ' / %s ' ), $wps_price_html ) . '</span>';

				$price .= apply_filters( 'wps_sfw_show_sync_interval', $wps_sfw_price_html, $product_id );
			}
			if ( isset( $wps_sfw_subscription_expiry_number ) && ! empty( $wps_sfw_subscription_expiry_number ) ) {
				$wps_sfw_subscription_expiry_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_expiry_interval', true );

				$wps_price_expiry_html = wps_sfw_get_time_interval( $wps_sfw_subscription_expiry_number, $wps_sfw_subscription_expiry_interval );
				/* translators: %s: susbcription expiry interval */
				$price .= '<span class="wps_sfw_expiry_interval">' . sprintf( esc_html__( ' For %s ', 'subscriptions-for-woocommerce' ), $wps_price_expiry_html ) . '</span>';
			}
			if ( ! is_checkout() ) {
				// Add free trial html.
				$price = $this->wps_sfw_get_free_trial_period_html( $product_id, $price );
				// Add initial signup fee html.
				$price = $this->wps_sfw_get_initial_signup_fee_html( $product_id, $price );
			}
			// Hook to handle the one-time.
			$price = apply_filters( 'wps_sfw_show_one_time_subscription_price', $price, $product_id, $cart_item );
		}
		return apply_filters( 'wps_sfw_price_html', $price, $wps_price_html, $product_id, $cart_item );
	}

	/**
	 * This function is used to show initial signup fee on subscription product page.
	 *
	 * @name wps_sfw_get_initial_signup_fee_html
	 * @param int    $product_id Product ID.
	 * @param string $price Product Price.
	 * @since    1.0.0
	 */
	public function wps_sfw_get_initial_signup_fee_html( $product_id, $price ) {
		$wps_sfw_subscription_initial_signup_price = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_initial_signup_price', true );
		if ( isset( $wps_sfw_subscription_initial_signup_price ) && ! empty( $wps_sfw_subscription_initial_signup_price ) ) {
			/*
			 translators: %s: signup fee */
			// $price .= sprintf( esc_html__( ' and %s  Sign up fee', 'subscriptions-for-woocommerce' ), wc_price( $wps_sfw_subscription_initial_signup_price ) );

			$price .= sprintf( esc_html__( ' and %1$1s%2$2s sign-up fee', 'subscriptions-for-woocommerce' ), get_woocommerce_currency_symbol(), $wps_sfw_subscription_initial_signup_price );

		}
		return $price;
	}

	/**
	 * This function is used to show free trial period on subscription product page.
	 *
	 * @name wps_sfw_get_free_trial_period_html
	 * @param int    $product_id Product ID.
	 * @param string $price Product Price.
	 * @since    1.0.0
	 */
	public function wps_sfw_get_free_trial_period_html( $product_id, $price ) {

		$wps_sfw_subscription_free_trial_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_number', true );
		$wps_sfw_subscription_free_trial_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_interval', true );
		if ( isset( $wps_sfw_subscription_free_trial_number ) && ! empty( $wps_sfw_subscription_free_trial_number ) ) {
			$wps_price_html = wps_sfw_get_time_interval( $wps_sfw_subscription_free_trial_number, $wps_sfw_subscription_free_trial_interval );
			/* translators: %s: free trial number */
			$price .= sprintf( esc_html__( ' and %s free trial', 'subscriptions-for-woocommerce' ), $wps_price_html );
		}
		return $price;
	}

	/**
	 * This function is used to change Add to cart button text.
	 *
	 * @name wps_sfw_product_add_to_cart_text
	 * @param object $text Add to cart text.
	 * @param string $product Product..
	 * @since    1.0.0
	 */
	public function wps_sfw_product_add_to_cart_text( $text, $product ) {

		if ( wps_sfw_check_product_is_subscription( $product ) ) {
			$wps_add_to_cart_text = $this->wps_sfw_get_add_to_cart_button_text();

			if ( isset( $wps_add_to_cart_text ) && ! empty( $wps_add_to_cart_text ) ) {
				$text = $wps_add_to_cart_text;
			}
		}

		// Subscription box button rename.
		$product_type = $product->get_type();
		if ( 'subscription_box' == $product_type ) {
			$wps_sfw_subscription_box_add_to_cart_text = get_option( 'wps_sfw_subscription_box_add_to_cart_text' );
			if ( $wps_sfw_subscription_box_add_to_cart_text ) {
				$text = $wps_sfw_subscription_box_add_to_cart_text;
			} else {
				$text = esc_attr__( 'Create Subscription Box', 'subscriptions-for-woocommerce' );
			}
		}
		// Subscription box button rename.
		return $text;
	}

	/**
	 * This function is used to get add to cart button text.
	 *
	 * @name wps_sfw_get_add_to_cart_button_text
	 * @since    1.0.0
	 */
	public function wps_sfw_get_add_to_cart_button_text() {

		$wps_add_to_cart_text = get_option( 'wps_sfw_add_to_cart_text', '' );
		return $wps_add_to_cart_text;
	}

	/**
	 * This function is used to change place order button text.
	 *
	 * @name wps_sfw_woocommerce_order_button_text
	 * @param string $text Place order text.
	 * @since    1.0.0
	 */
	public function wps_sfw_woocommerce_order_button_text( $text ) {
		$wps_sfw_place_order_button_text = $this->wps_sfw_get_place_order_button_text();

		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				if ( $cart_item['data']->get_type() == 'subscription_box' ) {
					$wps_sfw_subscription_box_place_order_button_text = get_option( 'wps_sfw_subscription_box_place_order_button_text', '' );
					$text = $wps_sfw_subscription_box_place_order_button_text;
					return $text;
				}
			}
		}
		if ( isset( $wps_sfw_place_order_button_text ) && ! empty( $wps_sfw_place_order_button_text ) && $this->wps_sfw_check_cart_has_subscription_product() ) {
			$text = $wps_sfw_place_order_button_text;
		}

		return $text;
	}

	/**
	 * This function is used to get order button text.
	 *
	 * @name wps_sfw_get_place_order_button_text
	 * @since    1.0.0
	 */
	public function wps_sfw_get_place_order_button_text() {

		$wps_sfw_place_order_button_text = get_option( 'wps_sfw_place_order_button_text', '' );
		return $wps_sfw_place_order_button_text;
	}

	/**
	 * This function is used to check cart have subscription product.
	 *
	 * @name wps_sfw_check_cart_has_subscription_product
	 * @since    1.0.0
	 */
	public function wps_sfw_check_cart_has_subscription_product() {
		$wps_has_subscription = false;

		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				if ( wps_sfw_check_product_is_subscription( $cart_item['data'] ) ) {
					$wps_has_subscription = true;
					break;
				}
			}
		}
		return $wps_has_subscription;
	}

	/**
	 * This function is used to subscription price on in cart.
	 *
	 * @param string $product_price as  Product price .
	 * @param object $cart_item cart as item .
	 * @param int    $cart_item_key as cart_item_key .
	 * @since    1.0.0
	 */
	public function wps_sfw_show_subscription_price_on_cart( $product_price, $cart_item, $cart_item_key ) {

		if ( isset( $cart_item['data'] ) && wps_sfw_check_product_is_subscription( $cart_item['data'] ) ) {
			$product_id = $cart_item['product_id'];

			$line_data = $this->wps_sfw_calculate_recurring_price( $cart_item, false );
			$price = $line_data['line_total'];
			$include_tax = get_option( 'woocommerce_prices_include_tax' );
			if ( 'yes' === $include_tax ) {
				$price = $price + $line_data['line_tax'];
			}
			$price = $price / $cart_item['quantity'];

			$price = apply_filters( 'wps_sfw_recurring_price_info', $price, $cart_item, $product_id );
			$product_price = wc_price( wc_get_price_to_display( $cart_item['data'], array( 'price' => $price ) ) );
			// Use for role base pricing.
			$product_price = apply_filters( 'wps_rbpfw_cart_price', $product_price, $cart_item );
			$product_price = $this->wps_sfw_subscription_product_get_price_html( $product_price, $cart_item['data'], $cart_item );
		} else {
			// subscription box.
			$product_id = $cart_item['data']->get_id();
			$product = wc_get_product( $product_id );
			if ( $product && $product->get_type() == 'subscription_box' ) {

				$line_data = $this->wps_sfw_calculate_recurring_price( $cart_item, true );
				$renewal_amount = $line_data['line_total'] + $line_data['line_tax'] + $line_data['shipping_fee'];

				$product_price = wc_price( wc_get_price_to_display( $cart_item['data'], array( 'price' => $renewal_amount ) ) );

				$subscription_number = isset( $cart_item['wps_sfw_subscription_number'] ) ? $cart_item['wps_sfw_subscription_number'] : '';
				$subscription_expiry_number = isset( $cart_item['wps_sfw_subscription_expiry_number'] ) ? $cart_item['wps_sfw_subscription_expiry_number'] : '';
				$subscription_interval = isset( $cart_item['wps_sfw_subscription_interval'] ) ? $cart_item['wps_sfw_subscription_interval'] : '';
				$subscription_expiry_interval = isset( $cart_item['wps_sfw_subscription_expiry_interval'] ) ? $cart_item['wps_sfw_subscription_expiry_interval'] : '';

				$wps_price = wps_sfw_get_time_interval_for_price( $subscription_number, $subscription_interval );

				$subscription_box_renewal_amount = wps_sfw_get_time_interval( $subscription_expiry_number, $subscription_expiry_interval );

				$product_price = $product_price . '/' . $wps_price . ' For ' . $subscription_box_renewal_amount;
			}
			// subscription box.
		}
		return $product_price;
	}

	/**
	 * This function is used to add susbcription price.
	 *
	 * @name wps_sfw_add_subscription_price
	 * @param object $cart cart.
	 * @since    1.0.0
	 */
	public function wps_sfw_add_subscription_price( $cart ) {

		// This is necessary for WC 3.0+.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Avoiding hook repetition (when using price calculations for example | optional).
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		if ( isset( $cart ) && ! empty( $cart ) ) {

			foreach ( $cart->cart_contents as $key => $cart_data ) {
				if ( wps_sfw_check_product_is_subscription( $cart_data['data'] ) ) {

					$product_id = $cart_data['data']->get_id();
					$wps_sfw_free_trial_number = $this->wps_sfw_get_subscription_trial_period_number( $product_id );

					if ( isset( $wps_sfw_free_trial_number ) && ! empty( $wps_sfw_free_trial_number ) ) {
						$product_price = 0;
					} else {
						$product_price = $cart_data['data']->get_price();
					}
					// Cart price.
					$wps_cart_price = apply_filters( 'wps_sfw_cart_price_subscription', $product_price, $cart_data );

					$cart_data['data']->set_price( $wps_cart_price );
				}
			}
		}
		do_action( 'wps_sfw_did_woocommerce_before_calculate_totals', $cart );
	}

	/**
	 * This function is used to add subscription signup fee.
	 *
	 * @name  wps_sfw_add_subscription_signup_fee
	 * @param object $cart cart.
	 * @since 1.8.7
	 */
	public function wps_sfw_add_subscription_signup_fee( $cart ) {
		// Only add the fee if it's not already added.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$wps_sfw_signup_fee = 0;
		if ( isset( $cart ) && ! empty( $cart ) ) {
			foreach ( $cart->cart_contents as $key => $cart_data ) {
				if ( wps_sfw_check_product_is_subscription( $cart_data['data'] ) ) {

					$product_id = $cart_data['data']->get_id();
					// Add signup fee.
					$product_id = isset( $cart_data['variation_id'] ) && $cart_data['variation_id'] ? $cart_data['variation_id'] : $cart_data['product_id'];
					$wps_sfw_get_signup_fee = $this->wps_sfw_get_subscription_initial_signup_price( $product_id );
					$wps_sfw_get_signup_fee = is_numeric( $wps_sfw_get_signup_fee ) ? (float) $wps_sfw_get_signup_fee : 0;
					if ( $wps_sfw_get_signup_fee && apply_filters( 'wps_sfw_allow_signup_fee', true, $cart_data ) ) {
						$wps_sfw_signup_fee += $wps_sfw_get_signup_fee;
					}
				}
			}
		}
		if ( $wps_sfw_signup_fee ) {
			$cart->add_fee( esc_html__( 'Initial Signup Fee', 'subscriptions-for-woocommerce' ), $wps_sfw_signup_fee, false );
		}
	}

	/**
	 * This function is used to add susbcription price.
	 *
	 * @name wps_sfw_get_subscription_trial_period_number
	 * @param int $product_id product_id.
	 * @since    1.0.0
	 */
	public function wps_sfw_get_subscription_trial_period_number( $product_id ) {
		$wps_sfw_subscription_free_trial_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_number', true );
		return $wps_sfw_subscription_free_trial_number;
	}

	/**
	 * This function is used to add initial singup price.
	 *
	 * @name wps_sfw_get_subscription_initial_signup_price
	 * @param int $product_id product_id.
	 * @since    1.0.0
	 */
	public function wps_sfw_get_subscription_initial_signup_price( $product_id ) {
		$wps_sfw_subscription_initial_signup_price = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_initial_signup_price', true );
		return $wps_sfw_subscription_initial_signup_price;
	}

	/**
	 * This function is used to process checkout.
	 *
	 * @name wps_sfw_process_checkout
	 * @param int   $order_id order_id.
	 * @param array $posted_data posted_data.
	 * @since    1.0.0
	 * @throws \Exception Return error.
	 */
	public function wps_sfw_process_checkout( $order_id, $posted_data ) {
		global $woocommerce;
		$order = wc_get_order( $order_id );

		if ( ! $this->wps_sfw_check_cart_has_subscription_product() ) {
			return;
		}
		/*delete failed order subscription*/
		wps_sfw_delete_failed_subscription( $order->get_id() );

		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				$wps_skip_creating_subscription = apply_filters( 'wps_skip_creating_subscription', true, $cart_item );
				if ( wps_sfw_check_product_is_subscription( $cart_item['data'] ) && $wps_skip_creating_subscription ) {

					if ( $cart_item['data']->is_on_sale() ) {
						$price = $cart_item['data']->get_sale_price();
					} else {
						$price = $cart_item['data']->get_regular_price();
					}
					$product_id = $cart_item['data']->get_id();

					$wps_recurring_data = $this->wps_sfw_get_subscription_recurring_data( $product_id, $cart_item );

					$line_data = $this->wps_sfw_calculate_recurring_price( $cart_item, false );

					$show_price = $line_data['line_total'] + $line_data['line_tax'];

					$wps_recurring_data['wps_recurring_total'] = $show_price;
					$wps_recurring_data['wps_show_recurring_total'] = $show_price;
					$wps_recurring_data['product_id'] = $product_id;
					$wps_recurring_data['product_name'] = $cart_item['data']->get_name();
					$wps_recurring_data['product_qty'] = $cart_item['quantity'];

					$wps_recurring_data['line_tax_data'] = $line_data['line_tax_data'];
					$wps_recurring_data['line_subtotal'] = $line_data['line_subtotal'];
					$wps_recurring_data['line_subtotal_tax'] = $line_data['line_subtotal_tax'];
					$wps_recurring_data['line_total'] = $line_data['line_total'];
					$wps_recurring_data['line_tax'] = $line_data['line_tax'];

					$wps_recurring_data['cart_price'] = $price;

					$wps_recurring_data = apply_filters( 'wps_sfw_cart_data_for_susbcription', $wps_recurring_data, $cart_item );

					if ( apply_filters( 'wps_sfw_is_upgrade_downgrade_order', false, $wps_recurring_data, $order, $posted_data, $cart_item ) ) {
						return;
					}
					$subscription = $this->wps_sfw_create_subscription( $order, $posted_data, $wps_recurring_data, $cart_item );
					if ( is_wp_error( $subscription ) ) {
						throw new Exception( esc_html( $subscription->get_error_message() ) );
					} else {
						$wps_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_order_has_subscription', true );
						if ( 'yes' != $wps_has_susbcription ) {
							wps_sfw_update_meta_data( $order_id, 'wps_sfw_order_has_subscription', 'yes' );
						}
						do_action( 'wps_sfw_subscription_process_checkout', $order_id, $posted_data, $subscription );
					}
				}
			}
			$wps_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_order_has_subscription', true );

			if ( 'yes' == $wps_has_susbcription ) {
				// phpcs:disable WordPress.Security.NonceVerification.Missing
				// After process checkout.
				do_action( 'wps_sfw_subscription_process_checkout_payment_method', $order_id, $posted_data );
				// phpcs:enable WordPress.Security.NonceVerification.Missing
			}
		}
	}

	/**
	 * This function is used to get ruccuring data.
	 *
	 * @name wps_sfw_get_subscription_recurring_data
	 * @param int   $product_id product_id.
	 * @param array $cart_item  cart_item.
	 *
	 * @since    1.0.0
	 */
	public function wps_sfw_get_subscription_recurring_data( $product_id, $cart_item ) {

		$wps_recurring_data = array();

		$wps_sfw_subscription_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_number', true );
		$wps_sfw_subscription_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_interval', true );

		$wps_recurring_data['wps_sfw_subscription_number'] = $wps_sfw_subscription_number;
		$wps_recurring_data['wps_sfw_subscription_interval'] = $wps_sfw_subscription_interval;
		$wps_sfw_subscription_expiry_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_expiry_number', true );

		if ( isset( $wps_sfw_subscription_expiry_number ) && ! empty( $wps_sfw_subscription_expiry_number ) ) {
			$wps_recurring_data['wps_sfw_subscription_expiry_number'] = $wps_sfw_subscription_expiry_number;
		}

		$wps_sfw_subscription_expiry_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_expiry_interval', true );

		if ( isset( $wps_sfw_subscription_expiry_interval ) && ! empty( $wps_sfw_subscription_expiry_interval ) ) {
			$wps_recurring_data['wps_sfw_subscription_expiry_interval'] = $wps_sfw_subscription_expiry_interval;
		}
		$wps_sfw_subscription_initial_signup_price = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_initial_signup_price', true );

		if ( isset( $wps_sfw_subscription_expiry_interval ) && ! empty( $wps_sfw_subscription_expiry_interval ) ) {
			$wps_recurring_data['wps_sfw_subscription_initial_signup_price'] = $wps_sfw_subscription_initial_signup_price;
		}

		$wps_sfw_subscription_free_trial_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_number', true );

		if ( isset( $wps_sfw_subscription_free_trial_number ) && ! empty( $wps_sfw_subscription_free_trial_number ) ) {
			$wps_recurring_data['wps_sfw_subscription_free_trial_number'] = $wps_sfw_subscription_free_trial_number;
		}
		$wps_sfw_subscription_free_trial_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_interval', true );
		if ( isset( $wps_sfw_subscription_free_trial_interval ) && ! empty( $wps_sfw_subscription_free_trial_interval ) ) {
			$wps_recurring_data['wps_sfw_subscription_free_trial_interval'] = $wps_sfw_subscription_free_trial_interval;
		}
		$wps_recurring_data = apply_filters( 'wps_sfw_recurring_data', $wps_recurring_data, $product_id, $cart_item );
		return $wps_recurring_data;
	}


	/**
	 * This function is used to create susbcription post.
	 *
	 * @name wps_sfw_create_subscription
	 * @param object $order order.
	 * @param array  $posted_data posted_data.
	 * @param array  $wps_recurring_data wps_recurring_data.
	 * @param array  $cart_item cart items.
	 * @since    1.0.0
	 */
	public function wps_sfw_create_subscription( $order, $posted_data, $wps_recurring_data, $cart_item ) {
		if ( ! empty( $order ) ) {
			$order_id = $order->get_id();
			$current_date  = current_time( 'timestamp' );

			// subscription box.
			wps_sfw_add_attached_product_for_subscription_box( $order_id );
			// subscription box.

			$wps_default_args = array(
				'wps_parent_order'   => $order_id,
				'wps_customer_id'    => $order->get_user_id(),
				'wps_schedule_start' => $current_date,
			);

			$wps_args = wp_parse_args( $wps_recurring_data, $wps_default_args );
			$get_wps_payment_method = null;
			$get_wps_payment_method_title = null;
			if ( isset( $posted_data['payment_method'] ) && $posted_data['payment_method'] ) {
				$wps_enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();

				if ( isset( $wps_enabled_gateways[ $posted_data['payment_method'] ] ) ) {
					$wps_payment_method = $wps_enabled_gateways[ $posted_data['payment_method'] ];
					$wps_payment_method->validate_fields();
					$wps_args['_payment_method']       = $wps_payment_method->id;
					$wps_args['_payment_method_title'] = $wps_payment_method->get_title();
				}
			}
			$wps_args['wps_order_currency'] = $order->get_currency();
			$wps_args['wps_subscription_status'] = 'pending';

			$wps_args = apply_filters( 'wps_sfw_new_subscriptions_data', $wps_args );
			// translators: post title date parsed by strftime.
			$post_title_date = gmdate( _x( '%1$b %2$d, %Y @ %I:%M %p', 'subscription post title. "Subscriptions order - <this>"', 'subscriptions-for-woocommerce' ) );
			$wps_subscription_data = array();
			$wps_subscription_data['post_type']     = 'wps_subscriptions';

			$wps_subscription_data['post_status']   = 'wc-wps_renewal';
			$wps_subscription_data['post_author']   = 1;
			$wps_subscription_data['post_parent']   = $order_id;
			/* translators: %s: post title date */
			$wps_subscription_data['post_title']    = sprintf( _x( 'WPS Subscription &ndash; %s', 'Subscription post title', 'subscriptions-for-woocommerce' ), $post_title_date );
			$wps_subscription_data['post_date_gmt'] = $order->get_date_created()->date( 'Y-m-d H:i:s' );
			$wps_subscription_data['post_date_gmt'] = $order->get_date_created()->date( 'Y-m-d H:i:s' );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {

				$subscription_order = wps_create_subscription();
				$subscription_id    = $subscription_order->get_id();

				$subscription_order->set_customer_id( $order->get_user_id() );

				$new_order = new WPS_Subscription( $subscription_id );
				$new_order->update_status( 'wc-wps_renewal' );
			} else {
				$subscription_id = wp_insert_post( $wps_subscription_data, true );

				$new_order = wc_get_order( $subscription_id );
			}
			if ( is_wp_error( $subscription_id ) ) {
				return $subscription_id;
			}

			wps_sfw_update_meta_data( $order_id, 'wps_subscription_id', $subscription_id );
			wps_sfw_update_meta_data( $subscription_id, 'wps_susbcription_trial_end', 0 );
			wps_sfw_update_meta_data( $subscription_id, 'wps_susbcription_end', 0 );
			wps_sfw_update_meta_data( $subscription_id, 'wps_next_payment_date', 0 );

			$attach_courses = get_post_meta( $wps_recurring_data['product_id'], 'wps_learnpress_course', true );
			wps_sfw_update_meta_data( $subscription_id, 'wps_learnpress_course', $attach_courses );

			/*if free trial*/
			$_product = $cart_item['data'];

			$billing_details = $order->get_address( 'billing' );
			$shipping_details = $order->get_address( 'shipping' );

			$new_order->set_address( $billing_details, 'billing' );
			$new_order->set_address( $shipping_details, 'shipping' );

			$new_order->set_payment_method( $order->get_payment_method() );
			$new_order->set_payment_method_title( $order->get_payment_method_title() );

			$new_order->set_currency( $order->get_currency() );
			$new_order->set_order_key( wc_generate_order_key() );

			$line_subtotal   = $wps_args['line_subtotal'];
			$line_total      = $wps_args['line_total'];
			$total_taxes     = $wps_args['line_tax'];
			$substotal_taxes = $wps_args['line_subtotal_tax'];

			$wps_pro_args = array(
				'variation' => array(),
				'totals'    => array(
					'subtotal'     => $line_subtotal,
					'subtotal_tax' => $substotal_taxes,
					'total'        => $line_total,
					'tax'          => $total_taxes,
					'tax_data'     => array(
						'subtotal' => array( $substotal_taxes ),
						'total'    => array( $total_taxes ),
					),
				),
			);

			$wps_pro_args = apply_filters( 'wps_product_args_for_order', $wps_pro_args );

			$wps_args = apply_filters( 'wps_product_args_for_renewal_order_propate_amount', $wps_args, $cart_item );

			$item_id = $new_order->add_product(
				$_product,
				$wps_args['product_qty'],
				$wps_pro_args
			);
			$new_order->update_taxes();
			$new_order->calculate_totals();
			$new_order->save();
			/* translators: %s: subscription id */
			$order->add_order_note( sprintf( __( 'A new Subscription #%s is created', 'subscriptions-for-woocommerce' ), '<a href="' . admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table&wps_order_type=subscription&id=' . $subscription_id ) . '">' . $subscription_id . '</a>' ) );

			do_action( 'wps_sfw_subscription_bundle_addition', $subscription_id, $order_id, $_product );

			do_action( 'wps_sfw_subscription_subscription_box_addtion', $subscription_id, $order_id, $_product, $cart_item );

			// After susbcription order created.
			do_action( 'wps_sfw_subscription_order', $new_order, $order_id );

			// new subscription meta from the version  1.5.8.
			wps_sfw_update_meta_data( $subscription_id, 'wps_sfw_new_sub', 'yes' );

			wps_sfw_update_meta_key_for_susbcription( $subscription_id, $wps_args );
			// After susbcription order created.
			do_action( 'wps_sfw_after_created_subscription', $subscription_id, $order_id );
			// After susbcription created.

			return apply_filters( 'wps_sfw_created_subscription', $subscription_id, $order_id );
		}
	}

	/**
	 * This function is used to add payment method form.
	 *
	 * @name wps_sfw_after_woocommerce_pay
	 * @since    1.0.0
	 */
	public function wps_sfw_after_woocommerce_pay() {
		global $wp;
		$wps_valid_request = false;

		if ( ! isset( $wp->query_vars['order-pay'] ) || ! wps_sfw_check_valid_subscription( absint( $wp->query_vars['order-pay'] ) ) ) {
			return;
		}
		if ( ! isset( $_GET['wps_add_payment_method'] ) && empty( $_GET['wps_add_payment_method'] ) ) {
			return;
		}
		$wps_subscription  = wc_get_order( absint( $_GET['wps_add_payment_method'] ) );
		$wps_valid_request = wps_sfw_validate_payment_request( $wps_subscription );

		if ( $wps_valid_request ) {
			ob_clean();
			$this->wps_sfw_set_customer_address( $wps_subscription );
			wc_get_template( 'myaccount/wps-add-new-payment-details.php', array( 'wps_subscription' => $wps_subscription ), '', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/partials/templates/' );
			ob_end_flush();
		}
	}

	/**
	 * This function is used to set customer address.
	 *
	 * @name wps_sfw_set_customer_address
	 * @param object $wps_subscription wps_subscription.
	 * @since    1.0.1
	 */
	public function wps_sfw_set_customer_address( $wps_subscription ) {
		$wps_sfw_billing_country = $wps_subscription->get_billing_country();
		$wps_sfw_billing_state = $wps_subscription->get_billing_state();
		$wps_sfw_billing_postcode = $wps_subscription->get_billing_postcode();
		if ( $wps_sfw_billing_country ) {
			WC()->customer->set_billing_country( $wps_sfw_billing_country );
		}
		if ( $wps_sfw_billing_state ) {
			WC()->customer->set_billing_state( $wps_sfw_billing_state );
		}
		if ( $wps_sfw_billing_postcode ) {
			WC()->customer->set_billing_postcode( $wps_sfw_billing_postcode );
		}
	}

	/**
	 * This function is used to set customer address.
	 *
	 * @name wps_sfw_set_customer_address_for_payment
	 * @param object $wps_subscription wps_subscription.
	 * @since    1.0.1
	 */
	public function wps_sfw_set_customer_address_for_payment( $wps_subscription ) {
		$wps_subscription_billing_country  = $wps_subscription->get_billing_country();
		$wps_subscription_billing_state  = $wps_subscription->get_billing_state();
		$wps_subscription_billing_postcode = $wps_subscription->get_billing_postcode();
		$wps_subscription_billing_city     = $wps_subscription->get_billing_postcode();

		if ( $wps_subscription_billing_country ) {
			WC()->customer->set_billing_country( $wps_subscription_billing_country );
		}
		if ( $wps_subscription_billing_state ) {
			WC()->customer->set_billing_state( $wps_subscription_billing_state );
		}
		if ( $wps_subscription_billing_postcode ) {
			WC()->customer->set_billing_postcode( $wps_subscription_billing_postcode );
		}
		if ( $wps_subscription_billing_city ) {
			WC()->customer->set_billing_city( $wps_subscription_billing_city );
		}
	}

	/**
	 * This function is used to process payment method form.
	 *
	 * @name wps_sfw_change_payment_method_form
	 * @since    1.0.0
	 */
	public function wps_sfw_change_payment_method_form() {
		if ( ! isset( $_POST['_wps_sfw_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wps_sfw_nonce'] ) ), 'wps_sfw__change_payment_method' ) ) {
			return;
		}

		if ( ! isset( $_POST['wps_change_change_payment'] ) && empty( $_POST['wps_change_change_payment'] ) ) {
			return;
		}
		$subscription_id = absint( $_POST['wps_change_change_payment'] );
		$wps_subscription = wc_get_order( $subscription_id );

		ob_start();
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		if ( $wps_subscription->get_order_key() == $order_key ) {

			$this->wps_sfw_set_customer_address_for_payment( $wps_subscription );
			// Update payment method.
			$new_payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
			if ( empty( $new_payment_method ) ) {

				$wps_notice = __( 'Please enable payment method', 'subscriptions-for-woocommerce' );
				wc_add_notice( $wps_notice, 'error' );
				$result_redirect = wc_get_endpoint_url( 'show-subscription', $wps_subscription->get_id(), wc_get_page_permalink( 'myaccount' ) );
				wp_safe_redirect( $result_redirect );
				exit;
			}
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

			$available_gateways[ $new_payment_method ]->validate_fields();
			$payment_method_title = $available_gateways[ $new_payment_method ]->get_title();

			if ( wc_notice_count( 'error' ) == 0 ) {

				$result = $available_gateways[ $new_payment_method ]->process_payment( $wps_subscription->get_id(), false, true );

				if ( 'success' == $result['result'] ) {
					$result['redirect'] = wc_get_endpoint_url( 'show-subscription', $wps_subscription->get_id(), wc_get_page_permalink( 'myaccount' ) );
					$wps_subscription->set_payment_method( $new_payment_method );
					$wps_subscription->set_payment_method_title( $payment_method_title );
				}

				if ( 'success' != $result['result'] ) {
					return;
				}
				$wps_subscription->save();

				$wps_notice = __( 'Payment Method Added Successfully', 'subscriptions-for-woocommerce' );
				wc_add_notice( $wps_notice );
				wp_safe_redirect( $result['redirect'] );
				exit;
			}
		}
		ob_get_clean();
	}

	/**
	 * This function is used to process payment method form.
	 *
	 * @name wps_sfw_set_susbcription_total.
	 * @param int    $total total.
	 * @param object $wps_subscription wps_subscription.
	 * @since    1.0.0
	 */
	public function wps_sfw_set_susbcription_total( $total, $wps_subscription ) {

		return $total;

		if ( $wps_subscription && 'wps_paypal_subscription' === $wps_subscription->get_payment_method() ) {
			return $total;
		}
		global $wp;
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		if ( ! empty( $_POST['_wps_sfw_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wps_sfw_nonce'] ) ), 'wps_sfw__change_payment_method' ) && isset( $_POST['wps_change_change_payment'] ) && $wps_subscription->get_order_key() == $order_key && $wps_subscription->get_id() == absint( $_POST['wps_change_change_payment'] ) ) {
			$total = 0;
		} elseif ( isset( $wp->query_vars['order-pay'] ) && wps_sfw_check_valid_subscription( absint( $wp->query_vars['order-pay'] ) ) ) {

			$total = 0;
		}

		return $total;
	}

	/**
	 * This function is used to hide offline payment gateway for subscription product.
	 *
	 * @name wps_sfw_unset_offline_payment_gateway_for_subscription
	 * @param array $available_gateways available_gateways.
	 * @since    1.0.0
	 */
	public function wps_sfw_unset_offline_payment_gateway_for_subscription( $available_gateways ) {
		if ( is_admin() ) {
			return $available_gateways;
		}
		$wps_has_subscription = false;

		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->get_cart_contents() as $key => $values ) {

				if ( wps_sfw_check_product_is_subscription( $values['data'] ) ) {
					$wps_has_subscription = true;
					break;
				}
			}
		}
		if ( $wps_has_subscription ) {
			if ( isset( $available_gateways ) && ! empty( $available_gateways ) && is_array( $available_gateways ) ) {
				foreach ( $available_gateways as $key => $gateways ) {
					$wps_supported_method = array( 'stripe', 'stripe_sepa', 'stripe_sepa_debit' );
					// Supported paymnet gateway.
					$wps_payment_method = apply_filters( 'wps_sfw_supported_payment_gateway_for_woocommerce', $wps_supported_method, $key );

					if ( ! in_array( $key, $wps_payment_method ) ) {
						unset( $available_gateways[ $key ] );
					}
				}
			}
		}
		return $available_gateways;
	}

	/**
	 * Register the endpoints on my_account page.
	 *
	 * @name wps_sfw_add_subscription_tab_on_myaccount_page
	 * @since    1.0.0
	 */
	public function wps_sfw_add_subscription_tab_on_myaccount_page() {
		add_rewrite_endpoint( 'wps_subscriptions', EP_PAGES );
		add_rewrite_endpoint( 'show-subscription', EP_PAGES );
		add_rewrite_endpoint( 'wps-add-payment-method', EP_PAGES );
	}

	/**
	 * Register the endpoints on my_account page.
	 *
	 * @name wps_sfw_custom_endpoint_query_vars.
	 * @param array $vars vars.
	 * @since    1.0.0
	 */
	public function wps_sfw_custom_endpoint_query_vars( $vars ) {
		$vars[] = 'wps_subscriptions';
		$vars[] = 'show-subscription';
		$vars[] = 'wps-add-payment-method';
		return $vars;
	}

	/**
	 * This function is used to add Wps susbcriptions Tab in MY ACCOUNT Page
	 *
	 * @name wps_sfw_add_subscription_dashboard_on_myaccount_page
	 * @since 1.0.0
	 * @param array $items items.
	 */
	public function wps_sfw_add_subscription_dashboard_on_myaccount_page( $items ) {

		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );
		$items['wps_subscriptions'] = __( 'Subscriptions', 'subscriptions-for-woocommerce' );
		$items['customer-logout'] = $logout;

		return $items;
	}

	/**
	 * This function is used to add my account page template
	 *
	 * @name wps_sfw_subscription_dashboard_content.
	 * @param int $wps_current_page current page.
	 * @since 1.0.0
	 */
	public function wps_sfw_subscription_dashboard_content( $wps_current_page = 1 ) {

		global $wpdb;
		$user_id = get_current_user_id();
		$user_id          = absint( $user_id );
		$is_hpos          = OrderUtil::custom_orders_table_usage_is_enabled();
		$wps_per_page     = get_option( 'posts_per_page', 10 );
		$wps_current_page = empty( $wps_current_page ) ? 1 : absint( $wps_current_page );
		$offset           = ( $wps_current_page - 1 ) * $wps_per_page;

		if ( $is_hpos ) {
			$table      = $wpdb->prefix . 'wc_orders';
			$meta_table = $wpdb->prefix . 'wc_orders_meta';
			$id_field   = 'id';
			$type_field = 'type';
			$order_id_field = 'order_id';
		} else {
			$table      = $wpdb->prefix . 'posts';
			$meta_table = $wpdb->prefix . 'postmeta';
			$id_field   = 'ID';
			$type_field = 'post_type';
			$order_id_field = 'post_id';
		}

		// Total count (for pagination).
		$sql_count = "
			SELECT COUNT(DISTINCT {$table}.{$id_field})
			FROM {$table}
			INNER JOIN {$meta_table} AS meta ON meta.{$order_id_field} = {$table}.{$id_field}
			WHERE meta.meta_key = 'wps_customer_id'
			AND meta.meta_value = %s
			AND {$table}.{$type_field} = 'wps_subscriptions'
		";

		$total_count = $wpdb->get_var( $wpdb->prepare( $sql_count, $user_id ) );
		$wps_num_pages = ceil( $total_count / $wps_per_page );

		// Paginated query.
		$sql = "
			SELECT DISTINCT {$table}.{$id_field}
			FROM {$table}
			INNER JOIN {$meta_table} AS meta ON meta.{$order_id_field} = {$table}.{$id_field}
			WHERE meta.meta_key = 'wps_customer_id'
			AND meta.meta_value = %s
			AND {$table}.{$type_field} = 'wps_subscriptions'
		";

		$sql .= " ORDER BY {$table}.{$id_field} DESC LIMIT %d OFFSET %d";

		$subscription_ids = $wpdb->get_col(
			$wpdb->prepare( $sql, $user_id, $wps_per_page, $offset )
		);
		wc_get_template(
			'myaccount/wps-subscriptions.php',
			array(
				'wps_subscriptions' => $subscription_ids,
				'wps_current_page'  => $wps_current_page,
				'wps_num_pages' => $wps_num_pages,
				'paginate'      => true,
			),
			'',
			SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/partials/templates/'
		);
	}

	/**
	 * This function is used to restrict guest user for subscription product.
	 *
	 * @name wps_sfw_subscription_before_checkout_form
	 * @since 1.0.0
	 * @param object $checkout checkout.
	 */
	public function wps_sfw_subscription_before_checkout_form( $checkout = '' ) {

		if ( ! is_user_logged_in() ) {
			if ( $this->wps_sfw_check_cart_has_subscription_product() ) {
				if ( true === $checkout->enable_guest_checkout ) {
					$checkout->enable_guest_checkout = false;
				}
			}
		}
	}

	/**
	 * This function is used to show recurring price on account page.
	 *
	 * @name wps_sfw_display_susbcription_recerring_total_account_page_callback
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 */
	public function wps_sfw_display_susbcription_recerring_total_account_page_callback( $subscription_id ) {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$susbcription = new WPS_Subscription( $subscription_id );
		} else {
			$susbcription = wc_get_order( $subscription_id );
		}

		if ( isset( $susbcription ) && ! empty( $susbcription ) ) {
			$price = $susbcription->get_total();
			$wps_curr_args = array(
				'currency' => $susbcription->get_currency(),
			);
		} else {
			$price = wps_sfw_get_meta_data( $subscription_id, 'wps_recurring_total', true );
		}
		$wps_curr_args = array();

		$price = apply_filters( 'wps_sfw_sub_recurring_total_my_account_page', $price, $subscription_id );

		$price = wc_price( $price, $wps_curr_args );
		$wps_recurring_number = wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_number', true );
		$wps_recurring_interval = wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_interval', true );
		$wps_price_html = wps_sfw_get_time_interval_for_price( $wps_recurring_number, $wps_recurring_interval );

		/* translators: %s: subscription interval */
		$price .= sprintf( esc_html( ' / %s ' ), $wps_price_html );
		$wps_subscription_status = wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true );
		if ( 'cancelled' === $wps_subscription_status ) {
			$price = '---';
		}
		echo wp_kses_post( $price );
	}


	/**
	 * This function is used to include subscription details template on account page.
	 *
	 * @name wps_sfw_shwo_subscription_details
	 * @since 1.0.0
	 * @param int $wps_subscription_id wps_subscription_id.
	 */
	public function wps_sfw_shwo_subscription_details( $wps_subscription_id ) {

		if ( ! wps_sfw_check_valid_subscription( $wps_subscription_id ) ) {
			echo '<div class="woocommerce-error wps_sfw_invalid_subscription">' . esc_html__( 'Not a valid subscription', 'subscriptions-for-woocommerce' ) . '</div>';
			return;
		}

		wc_get_template( 'myaccount/wps-show-subscription-details.php', array( 'wps_subscription_id' => $wps_subscription_id ), '', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/partials/templates/' );
	}

	/**
	 * This function is used to cancel susbcription.
	 *
	 * @name wps_sfw_cancel_susbcription
	 * @since 1.0.0
	 */
	public function wps_sfw_cancel_susbcription() {

		if ( isset( $_GET['wps_subscription_status'] ) && isset( $_GET['wps_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$user_id      = get_current_user_id();

			$wps_status   = sanitize_text_field( wp_unslash( $_GET['wps_subscription_status'] ) );
			$wps_subscription_id = sanitize_text_field( wp_unslash( $_GET['wps_subscription_id'] ) );
			if ( wps_sfw_check_valid_subscription( $wps_subscription_id ) ) {
				$this->wps_sfw_cancel_susbcription_order_by_customer( $wps_subscription_id, $wps_status, $user_id );
			}
		}
	}

	/**
	 * This function is used to cancel susbcription.
	 *
	 * @name wps_sfw_cancel_susbcription_order_by_customer
	 * @param int    $wps_subscription_id wps_subscription_id.
	 * @param string $wps_status wps_status.
	 * @param int    $user_id user_id.
	 * @since 1.0.0
	 */
	public function wps_sfw_cancel_susbcription_order_by_customer( $wps_subscription_id, $wps_status, $user_id ) {

		$wps_customer_id = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_customer_id', true );
		if ( 'active' == $wps_status && $wps_customer_id == $user_id ) {

			$wps_wsp_payment_type = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_wsp_payment_type', true );
			if ( 'wps_wsp_manual_method' == $wps_wsp_payment_type ) {
				wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
			}
			do_action( 'wps_sfw_subscription_cancel', $wps_subscription_id, 'Cancel' );

			do_action( 'wps_sfw_cancel_susbcription', $wps_subscription_id, $user_id );

			wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_cancelled_by', 'by_user' );
			wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_cancelled_date', time() );

			wc_add_notice( __( 'Subscription Cancelled Successfully', 'subscriptions-for-woocommerce' ), 'success' );
			$redirect_url = wc_get_endpoint_url( 'show-subscription', $wps_subscription_id, wc_get_page_permalink( 'myaccount' ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * This function is used to update susbcription.
	 *
	 * @name wps_sfw_woocommerce_order_status_changed
	 * @param int    $order_id order_id.
	 * @param string $old_status old_status.
	 * @param string $new_status new_status.
	 * @since 1.0.0
	 */
	public function wps_sfw_woocommerce_order_status_changed( $order_id, $old_status, $new_status ) {

		if ( $old_status != $new_status ) {
			if ( 'completed' == $new_status || 'processing' == $new_status ) {
				$wps_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_order_has_subscription', true );
				$is_activated = wps_sfw_get_meta_data( $order_id, 'wps_sfw_subscription_activated', true );

				if ( 'yes' == $wps_has_susbcription && 'yes' !== $is_activated ) {

					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$args = array(
							'number' => 1,
							'return' => 'ids',
							'type'   => 'wps_subscriptions',
							'meta_query' => array(
								'relation' => 'AND',
								array(
									'key'   => 'wps_parent_order',
									'value' => $order_id,
								),
								array(
									'key'   => 'wps_subscription_status',
									'value' => 'pending',
								),
							),
						);
						$wps_subscriptions = wc_get_orders( $args );
					} else {
						$args = array(
							'numberposts' => -1,
							'post_type'   => 'wps_subscriptions',
							'post_status'   => 'wc-wps_renewal',
							'meta_query' => array(
								'relation' => 'AND',
								array(
									'key'   => 'wps_parent_order',
									'value' => $order_id,
								),
								array(
									'key'   => 'wps_subscription_status',
									'value' => 'pending',
								),
							),
						);
						$wps_subscriptions = get_posts( $args );
					}
					if ( isset( $wps_subscriptions ) && ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
						foreach ( $wps_subscriptions as $key => $subscription ) {

							if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
								$subscription_id = $subscription;
							} else {
								$subscription_id = $subscription->ID;
							}

							$status = 'active';
							$status = apply_filters( 'wps_sfw_set_subscription_status', $status, $subscription_id );

							do_action( 'wps_wsp_after_subscription_active', $status, $subscription_id );

							wps_sfw_update_meta_data( $subscription_id, 'wps_subscription_status', $status );
							$current_time = apply_filters( 'wps_sfw_subs_curent_time', current_time( 'timestamp' ), $subscription_id );
							wps_sfw_update_meta_data( $subscription_id, 'wps_schedule_start', $current_time );

							$wps_susbcription_trial_end = wps_sfw_susbcription_trial_date( $subscription_id, $current_time );
							wps_sfw_update_meta_data( $subscription_id, 'wps_susbcription_trial_end', $wps_susbcription_trial_end );

							$wps_next_payment_date = wps_sfw_next_payment_date( $subscription_id, $current_time, $wps_susbcription_trial_end );

							$wps_next_payment_date = apply_filters( 'wps_sfw_next_payment_date', $wps_next_payment_date, $subscription_id );

							wps_sfw_update_meta_data( $subscription_id, 'wps_next_payment_date', $wps_next_payment_date );

							$wps_susbcription_end = wps_sfw_susbcription_expiry_date( $subscription_id, $current_time, $wps_susbcription_trial_end );
							$wps_susbcription_end = apply_filters( 'wps_sfw_susbcription_end_date', $wps_susbcription_end, $subscription_id );
							wps_sfw_update_meta_data( $subscription_id, 'wps_susbcription_end', $wps_susbcription_end );

							// Set billing id.
							$billing_agreement_id = wps_sfw_get_meta_data( $order_id, '_ppec_billing_agreement_id', true );
							if ( isset( $billing_agreement_id ) && ! empty( $billing_agreement_id ) ) {
								wps_sfw_update_meta_data( $subscription_id, '_wps_paypal_subscription_id', $billing_agreement_id );
							}
							do_action( 'wps_sfw_order_status_changed', $order_id, $subscription_id );

							$mailer = WC()->mailer()->get_emails();
							// Send the "reactivate" notification.
							if ( isset( $mailer['wps_wsp_new_subscription'] ) ) {
								$mailer['wps_wsp_new_subscription']->trigger( $subscription_id );
							}
						}
						wps_sfw_update_meta_data( $order_id, 'wps_sfw_subscription_activated', 'yes' );
					}
				}
			} elseif ( 'failed' == $new_status || 'pending' == $new_status || 'wps_renewal' == $new_status ) {
				// Renewal order handling.
				$subscription_id = wps_sfw_get_meta_data( $order_id, 'wps_sfw_subscription', true );
				$renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
				if ( $subscription_id && 'yes' == $renewal_order ) {
					wps_sfw_update_meta_data( $subscription_id, 'wps_subscription_status', 'on-hold' );
				}
			}
		}
	}

	/**
	 * This function is used to set next payment date.
	 *
	 * @name wps_sfw_check_next_payment_date
	 * @param int    $subscription_id subscription_id.
	 * @param string $wps_next_payment_date wps_next_payment_date.
	 * @since 1.0.0
	 */
	public function wps_sfw_check_next_payment_date( $subscription_id, $wps_next_payment_date ) {
		$wps_sfw_subscription_number = wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_number', true );
		$wps_sfw_subscription_expiry_number = wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_expiry_number', true );
		$wps_sfw_subscription_free_trial_number = wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_free_trial_number', true );

		if ( empty( $wps_sfw_subscription_free_trial_number ) ) {
			if ( ! empty( $wps_sfw_subscription_number ) && ! empty( $wps_sfw_subscription_expiry_number ) ) {
				if ( $wps_sfw_subscription_number == $wps_sfw_subscription_expiry_number ) {
					$wps_next_payment_date = 0;
				}
			}
		}
		return $wps_next_payment_date;
	}
	/**
	 * This function is used to set single quantity for susbcription product.
	 *
	 * @name wps_sfw_hide_quantity_fields_for_subscription
	 * @param bool   $return return.
	 * @param object $product product.
	 * @since 1.0.0
	 */
	public function wps_sfw_hide_quantity_fields_for_subscription( $return, $product ) {

		if ( wps_sfw_check_plugin_enable() && wps_sfw_check_product_is_subscription( $product ) ) {
			$return = true;
		}
		return apply_filters( 'wps_sfw_show_quantity_fields_for_susbcriptions', $return, $product );
	}

	/**
	 * This function is used to restrict guest user susbcription product.
	 *
	 * @name wps_sfw_woocommerce_add_to_cart_validation
	 * @param bool $validate validate.
	 * @param int  $product_id product_id.
	 * @param int  $quantity quantity.
	 * @param int  $variation_id as variation_id.
	 * @param bool $variations as variations.
	 * @since 1.0.0
	 */
	public function wps_sfw_woocommerce_add_to_cart_validation( $validate, $product_id, $quantity, $variation_id = 0, $variations = null ) {

		$product = wc_get_product( $product_id );
		if ( is_object( $product ) && 'variable' === $product->get_type() ) {
			$product    = wc_get_product( $variation_id );
			$product_id = $variation_id;
		}
		if ( $this->wps_sfw_check_cart_has_subscription_product() && wps_sfw_check_product_is_subscription( $product ) ) {

			$validate = apply_filters( 'wps_sfw_add_to_cart_validation', false, $product_id, $quantity );

			if ( ! $validate ) {
				wc_add_notice( __( 'You can not add multiple subscription products in cart', 'subscriptions-for-woocommerce' ), 'error' );
			}
		}
		return apply_filters( 'wps_sfw_expiry_add_to_cart_validation', $validate, $product_id, $quantity );
	}

	/**
	 * This function is used to set payment options.
	 *
	 * @name wps_sfw_woocommerce_cart_needs_payment
	 * @param bool   $wps_needs_payment wps_needs_payment.
	 * @param object $cart cart.
	 * @since 1.0.0
	 */
	public function wps_sfw_woocommerce_cart_needs_payment( $wps_needs_payment, $cart ) {
		$wps_is_payment = false;
		$wps_cart_has_subscription = false;
		if ( $wps_needs_payment ) {
			return $wps_needs_payment;
		}

		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {

				if ( wps_sfw_check_product_is_subscription( $cart_item['data'] ) ) {
					$wps_cart_has_subscription = true;
					$product_id = $cart_item['data']->get_id();
					$wps_free_trial_length = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_number', true );
					if ( $wps_free_trial_length > 0 ) {
						$wps_is_payment = true;
						break;
					}
				}
			}
		}
		if ( $wps_is_payment && 0 == $cart->total ) {
			$wps_needs_payment = true;
		} elseif ( $wps_cart_has_subscription && 0 == $cart->total ) {
			$wps_needs_payment = true;
		}

		return apply_filters( 'wps_sfw_needs_payment', $wps_needs_payment, $cart );
	}

	/**
	 * This function is used to update susbcription.
	 *
	 * @name wps_sfw_woocommerce_order_status_changed
	 * @param int    $order_id order_id.
	 * @param string $old_status old_status.
	 * @param string $new_status new_status.
	 * @since 1.0.0
	 */
	public function wps_sfw__cancel_subs_woocommerce_order_status_changed( $order_id, $old_status, $new_status ) {

		if ( $old_status != $new_status ) {

			if ( 'cancelled' === $new_status ) {
				$wps_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_order_has_subscription', true );

				if ( 'yes' == $wps_has_susbcription ) {

					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$args = array(
							'return' => 'ids',
							'type'   => 'wps_subscriptions',
							'meta_query' => array(
								'relation' => 'AND',
								array(
									'key'   => 'wps_parent_order',
									'value' => $order_id,
								),
								array(
									'key'   => 'wps_subscription_status',
									'value' => array( 'active', 'pending' ),
								),
							),
						);
						$wps_subscriptions = wc_get_orders( $args );
					} else {
						$args = array(
							'numberposts' => -1,
							'post_type'   => 'wps_subscriptions',
							'post_status'   => 'wc-wps_renewal',
							'meta_query' => array(
								'relation' => 'AND',
								array(
									'key'   => 'wps_parent_order',
									'value' => $order_id,
								),
								array(
									'key'   => 'wps_subscription_status',
									'value' => array( 'active', 'pending' ),
								),
							),
						);
						$wps_subscriptions = get_posts( $args );
					}
					if ( isset( $wps_subscriptions ) && ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
						foreach ( $wps_subscriptions as $key => $subscription ) {
							if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
								wps_sfw_send_email_for_cancel_susbcription( $subscription );
								wps_sfw_update_meta_data( $subscription, 'wps_subscription_status', 'cancelled' );
							} else {
								wps_sfw_send_email_for_cancel_susbcription( $subscription->ID );
								wps_sfw_update_meta_data( $subscription->ID, 'wps_subscription_status', 'cancelled' );
							}
						}
					}
				}
			} elseif ( 'failed' === $new_status ) {
				$mailer = WC()->mailer()->get_emails();
				if ( isset( $mailer['WC_Email_Failed_Order'] ) ) {
					$mailer['WC_Email_Failed_Order']->trigger( $order_id );
				}
			} elseif ( 'completed' == $new_status || 'processing' == $new_status ) {
				$this->wps_sfw_active_after_on_hold( $order_id );
			}
		}
	}

	/**
	 * This function is used to activate subscription after on hold.
	 *
	 * @param int $order_id order_id.
	 * @return void
	 */
	public function wps_sfw_active_after_on_hold( $order_id ) {

		$wps_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
		if ( 'yes' == $wps_has_susbcription ) {
			$parent_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_parent_order_id', true );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$args = array(
					'return' => 'ids',
					'type'   => 'wps_subscriptions',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'   => 'wps_parent_order',
							'value' => $parent_order,
						),
						array(
							'key'   => 'wps_subscription_status',
							'value' => 'on-hold',
						),
					),
				);
				$wps_subscriptions = wc_get_orders( $args );
			} else {
				$args = array(
					'numberposts' => -1,
					'post_type'   => 'wps_subscriptions',
					'post_status'   => 'wc-wps_renewal',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'   => 'wps_parent_order',
							'value' => $parent_order,
						),
						array(
							'key'   => 'wps_subscription_status',
							'value' => 'on-hold',
						),
					),
				);
				$wps_subscriptions = get_posts( $args );
			}
			if ( isset( $wps_subscriptions ) && ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
				foreach ( $wps_subscriptions as $key => $subscription ) {
					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$subscription_id = $subscription;
					} else {
						$subscription_id = $subscription->ID;
					}
					$wps_next_payment_date = wps_sfw_next_payment_date( $subscription_id, current_time( 'timestamp' ), 0 );
					wps_sfw_update_meta_data( $subscription_id, 'wps_subscription_status', 'active' );
					wps_sfw_update_meta_data( $subscription_id, 'wps_next_payment_date', $wps_next_payment_date );
					do_action( 'wps_sfw_subscription_active_renewal', $subscription_id );
				}
			}
		}
	}

	/**
	 * Registration required if have subscription products for guest user.
	 *
	 * @param boolean $registration_required .
	 */
	public function wps_sfw_registration_required( $registration_required ) {
		$wps_has_subscription = wps_sfw_is_cart_has_subscription_product();
		if ( $wps_has_subscription && ! $registration_required ) {
			$registration_required = true;
		}
		return $registration_required;
	}

	/**
	 * Display the recurring
	 *
	 * @return void
	 */
	public function wps_sfw_show_recurring_information() {
		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				if ( wps_sfw_check_product_is_subscription( $cart_item['data'] ) ) {
					$product_id = $cart_item['data']->get_id();

					$wps_skip_creating_subscription = apply_filters( 'wps_skip_creating_subscription', true, $cart_item );
					if ( ! $wps_skip_creating_subscription ) {
						continue;
					}
					$line_data = $this->wps_sfw_calculate_recurring_price( $cart_item, true );
					$renewal_amount = $line_data['line_total'] + $line_data['line_tax'] + $line_data['shipping_fee'];
					$product_price  = wc_price( wc_get_price_to_display( $cart_item['data'], array( 'price' => $renewal_amount ) ) );
					$renewal_amount = $this->wps_sfw_subscription_product_get_price_html( $product_price, $cart_item['data'], $cart_item );

					$product_permalink = $cart_item['data']->get_permalink();

					$product_name = '<a href="' . esc_url( $product_permalink ) . '" target="_blank">' . esc_html( $cart_item['data']->get_name() ) . '</a><br>';
					$product_name = apply_filters( 'wps_sfw_product_name_modification_checkout', $product_name, $cart_item );
					?>
					<tr class="wps_recurring_bifurcation_wrapper">
						<th>
							<h4><?php echo esc_attr__( 'Renewal For', 'subscriptions-for-woocommerce' ) . ' ' . wp_kses_post( $product_name ); ?></h4>
						</th>
						<td>
							<ul>
								<li><label><?php esc_html_e( 'Subtotal', 'subscriptions-for-woocommerce' ); ?>:</label> <span><?php echo wp_kses_post( wc_price( $line_data['line_subtotal'] ) ); ?></span></li>
								<?php if ( isset( $line_data['line_tax'] ) && ! empty( $line_data['line_tax'] ) ) : ?>
									<li><label><?php esc_html_e( 'Tax', 'subscriptions-for-woocommerce' ); ?>:</label><span><?php echo wp_kses_post( wc_price( $line_data['line_tax'] ) ); ?></span></li>
								<?php endif; ?>
								<?php if ( isset( $line_data['shipping_fee'] ) && ! empty( $line_data['shipping_fee'] ) ) : ?>
									<li><label><?php esc_html_e( 'Shipping', 'subscriptions-for-woocommerce' ); ?>:</label><span><?php echo wp_kses_post( wc_price( $line_data['shipping_fee'] ) ); ?></span></li>
								<?php endif; ?>
								<li><label><?php esc_html_e( 'Total', 'subscriptions-for-woocommerce' ); ?>:</label><span><?php echo wp_kses_post( $renewal_amount ); ?></span></li>
							</ul>
						</td>
					<tr>
						<?php
				} else {
					// subscription box.
					$product_id = $cart_item['data']->get_id();
					$product = wc_get_product( $product_id );
					if ( $product && $product->get_type() == 'subscription_box' ) {

						$line_data = $this->wps_sfw_calculate_recurring_price( $cart_item, true );
						$renewal_amount = $line_data['line_total'] + $line_data['line_tax'] + $line_data['shipping_fee'];

						$product_price = wc_price( wc_get_price_to_display( $cart_item['data'], array( 'price' => $renewal_amount ) ) );

						$subscription_number = isset( $cart_item['wps_sfw_subscription_number'] ) ? $cart_item['wps_sfw_subscription_number'] : '';
						$subscription_expiry_number = isset( $cart_item['wps_sfw_subscription_expiry_number'] ) ? $cart_item['wps_sfw_subscription_expiry_number'] : '';
						$subscription_interval = isset( $cart_item['wps_sfw_subscription_interval'] ) ? $cart_item['wps_sfw_subscription_interval'] : '';
						$subscription_expiry_interval = isset( $cart_item['wps_sfw_subscription_expiry_interval'] ) ? $cart_item['wps_sfw_subscription_expiry_interval'] : '';

						$wps_price = wps_sfw_get_time_interval_for_price( $subscription_number, $subscription_interval );

						$subscription_box_renewal_amount = wps_sfw_get_time_interval( $subscription_expiry_number, $subscription_expiry_interval );

						$renewal_amount = $product_price . '/' . $wps_price . ' For ' . $subscription_box_renewal_amount;

						$product_name = esc_html( $cart_item['data']->get_name() ) . '<br>';
						$product_name = apply_filters( 'wps_sfw_product_name_modification_checkout', $product_name, $cart_item );
						?>
					<tr class="wps_recurring_bifurcation_wrapper">
						<th>
							<h4><?php echo esc_attr__( 'Renewal For', 'subscriptions-for-woocommerce' ) . ' ' . wp_kses_post( $product_name ); ?></h4>
						</th>
						<td>
							<ul>
								<li><label><?php esc_html_e( 'Subtotal', 'subscriptions-for-woocommerce' ); ?>:</label> <span><?php echo wp_kses_post( wc_price( $line_data['line_subtotal'] ) ); ?></span></li>
							<?php if ( isset( $line_data['line_tax'] ) && ! empty( $line_data['line_tax'] ) ) : ?>
									<li><label><?php esc_html_e( 'Tax', 'subscriptions-for-woocommerce' ); ?>:</label><span><?php echo wp_kses_post( wc_price( $line_data['line_tax'] ) ); ?></span></li>
								<?php endif; ?>
							<?php if ( isset( $line_data['shipping_fee'] ) && ! empty( $line_data['shipping_fee'] ) ) : ?>
									<li><label><?php esc_html_e( 'Shipping', 'subscriptions-for-woocommerce' ); ?>:</label><span><?php echo wp_kses_post( wc_price( $line_data['shipping_fee'] ) ); ?></span></li>
								<?php endif; ?>
								<li><label><?php esc_html_e( 'Total', 'subscriptions-for-woocommerce' ); ?>:</label><span><?php echo wp_kses_post( $renewal_amount ); ?></span></li>
							</ul>
						</td>
					<tr>
						<?php
					}
					// subscription box.
				}
			}
		}
	}

		/**
		 * Calculating correct recurring price.
		 *
		 * @param array() $cart_item .
		 * @param bool    $bool will decide to show or create subscription.
		 */
	public function wps_sfw_calculate_recurring_price( $cart_item, $bool ) {
		$product_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];

		$_product    = wc_get_product( $product_id );
		$include_tax = get_option( 'woocommerce_prices_include_tax' );

		$line_subtotal_tax = 0;
		$line_tax = 0;

		// Get the only item price from the cart.
		if ( 'yes' === $include_tax ) {
			$line_subtotal = $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
			$line_total    = $cart_item['line_total'] + $cart_item['line_tax'];
		} else {
			$line_subtotal = $cart_item['line_subtotal'];
			$line_total    = $cart_item['line_total'];
		}

		$qty = $cart_item['quantity'];

		// Make sure you have correct line data if coupon applied on the cart.
		$is_coupon_applied = false;
		$is_initital_discount = false;
		$is_recurring_coupon_applied = false;

		$coupons = WC()->cart->get_applied_coupons();
		if ( ! empty( $coupons ) ) {
			foreach ( $coupons as $coupon_code ) {
				$coupon        = new WC_Coupon( $coupon_code );
				$discount_type = $coupon->get_discount_type();
				$allow_dis_sub = array( 'recurring_product_percent_discount', 'recurring_product_discount' );
				if ( ! in_array( $discount_type, $allow_dis_sub, true ) ) {
					$is_coupon_applied = true;
				}
				if ( in_array( $discount_type, $allow_dis_sub, true ) ) {
					$is_recurring_coupon_applied = true;
				}
				if ( 'initial_fee_percent_discount' == $discount_type || 'initial_fee_discount' == $discount_type ) {
					$is_initital_discount = true;
				}
			}
		}

		// Get the item price from product object if free trial valid.
		$wps_sfw_subscription_free_trial_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_number', true );
		if ( ! empty( $wps_sfw_subscription_free_trial_number ) && ! $is_recurring_coupon_applied ) {
			$product       = wc_get_product( $product_id );
			$price         = $product->get_price() * $cart_item['quantity'];
			$line_subtotal = $price;
			$line_total    = $price;

			$get_membershipprice = wps_sfw_get_meta_data( $product_id, 'wps_membership_plan_price', true );
			if ( ! empty( $get_membershipprice ) ) {
				$line_subtotal = $get_membershipprice * $cart_item['quantity'];
				$line_total = $get_membershipprice * $cart_item['quantity'];
			}
		}

		// Manage the line item during the upgrade/downgrade process.
		$line_total    = apply_filters( 'wps_sfw_manage_line_total_for_plan_switch', $line_total, $cart_item, $bool );
		$line_subtotal = apply_filters( 'wps_sfw_manage_line_total_for_plan_switch', $line_subtotal, $cart_item, $bool );

		$product   = $cart_item['data'];
		$tax_class = $product->get_tax_class();
		$tax_rates = WC_Tax::get_rates( $tax_class );
		if ( function_exists( 'wps_sfw_is_woocommerce_tax_enabled' ) && wps_sfw_is_woocommerce_tax_enabled() ) {
			if ( 'yes' === $include_tax ) {
				$line_subtotal_tax = WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $line_subtotal, $tax_rates ) );
				$line_tax     = WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $line_total, $tax_rates ) );

				$line_total    = $line_total - $line_tax;
				$line_subtotal = $line_subtotal - $line_subtotal_tax;
			} else {
				$line_subtotal_tax = WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $line_subtotal, $tax_rates ) );
				$line_tax     = WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $line_total, $tax_rates ) );
			}
		}

		if ( $is_coupon_applied ) {
			$line_total = $line_subtotal;
			$line_tax = $line_subtotal_tax;
		}

		// Prepare the line data.
		$line_tax_data = array(
			'subtotal' => array( $line_subtotal_tax ),
			'total'    => array( $line_tax ),
		);
		$line_data = array(
			'line_total'        => $line_total,
			'line_subtotal'     => $line_subtotal,
			'line_tax'          => $line_tax,
			'line_subtotal_tax' => $line_subtotal_tax,
			'line_tax_data'     => $line_tax_data,
			'qty'               => $qty,
			'shipping_fee'      => 0,
		);
		return apply_filters( 'wps_sfw_modify_cart_item_data', $line_data, $cart_item, $bool );
	}

		/**
		 * Add filter for Cart and Checkout Blocks Integration.
		 *
		 * @since 1.5.8
		 */
	public function wps_sfw_to_cart_and_checkout_blocks() {
		if ( has_block( 'woocommerce/checkout-totals-block' ) ) {
			add_filter( 'render_block_woocommerce/checkout-order-summary-block', array( $this, 'wps_sfw_add_subscription_totals_on_cart_block' ), 999 );
		}
	}

		/**
		 * Show the recurring totals.
		 *
		 * @since 1.5.8
		 * @param string $content Current content.
		 * @return string
		 */
	public function wps_sfw_add_subscription_totals_on_cart_block( $content ) {
		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				if ( wps_sfw_check_product_is_subscription( $cart_item['data'] ) ) {
					$product_id = $cart_item['data']->get_id();

					$wps_skip_creating_subscription = apply_filters( 'wps_skip_creating_subscription', true, $cart_item );
					if ( ! $wps_skip_creating_subscription ) {
						continue;
					}
					$line_data = $this->wps_sfw_calculate_recurring_price( $cart_item, true );
					$renewal_amount = $line_data['line_total'] + $line_data['line_tax'] + $line_data['shipping_fee'];
					$product_price = wc_price( wc_get_price_to_display( $cart_item['data'], array( 'price' => $renewal_amount ) ) );

					$renewal_amount = $this->wps_sfw_subscription_product_get_price_html( $product_price, $cart_item['data'], $cart_item );

					$product_permalink = $cart_item['data']->get_permalink();

					$product_name = '<a href="' . esc_url( $product_permalink ) . '" target="_blank">' . esc_html( $cart_item['data']->get_name() ) . '</a><br>';
					$product_name = apply_filters( 'wps_sfw_product_name_modification_checkout', $product_name, $cart_item );
					$content .=
						'<div class="wps_recurring_bifurcation_wrapper"><tr class="order-total wps_wsp_recurring_total_tr">
						<th><h4>' . esc_attr__( 'Renewal For', 'subscriptions-for-woocommerce' ) . ' ' . wp_kses_post( $product_name ) . '</h4></th>
						<td>
						<ul>
							<li><label>' . esc_html__( 'Subtotal', 'subscriptions-for-woocommerce' ) . ':</label> <span>' . wp_kses_post( wc_price( $line_data['line_subtotal'] ) ) . '</span></li>';
					if ( isset( $line_data['line_tax'] ) && ! empty( $line_data['line_tax'] ) ) {
						$content .= '<li><label>' . esc_html__( 'Tax', 'subscriptions-for-woocommerce' ) . ':</label><span> ' . wp_kses_post( wc_price( $line_data['line_tax'] ) ) . '</span></li>';
					}
					if ( isset( $line_data['shipping_fee'] ) && ! empty( $line_data['shipping_fee'] ) ) {
						$content .= '<li><label>' . esc_html__( 'Shipping', 'subscriptions-for-woocommerce' ) . ':</label><span> ' . wp_kses_post( wc_price( $line_data['shipping_fee'] ) ) . '</span></li>';
					}
					$content .= '<li><label>' . esc_html__( 'Total', 'subscriptions-for-woocommerce' ) . ':</label><span> ' . wp_kses_post( $renewal_amount ) . '</span></li></ul></td><tr></div>';
				} else {
					// subscription box.
					$product_id = $cart_item['data']->get_id();
					$product = wc_get_product( $product_id );
					if ( $product && $product->get_type() == 'subscription_box' ) {
						$line_data = $this->wps_sfw_calculate_recurring_price( $cart_item, true );
						$renewal_amount = $line_data['line_total'] + $line_data['line_tax'] + $line_data['shipping_fee'];

						$product_price = wc_price( wc_get_price_to_display( $cart_item['data'], array( 'price' => $renewal_amount ) ) );

						$subscription_number = isset( $cart_item['wps_sfw_subscription_number'] ) ? $cart_item['wps_sfw_subscription_number'] : '';
						$subscription_expiry_number = isset( $cart_item['wps_sfw_subscription_expiry_number'] ) ? $cart_item['wps_sfw_subscription_expiry_number'] : '';
						$subscription_interval = isset( $cart_item['wps_sfw_subscription_interval'] ) ? $cart_item['wps_sfw_subscription_interval'] : '';
						$subscription_expiry_interval = isset( $cart_item['wps_sfw_subscription_expiry_interval'] ) ? $cart_item['wps_sfw_subscription_expiry_interval'] : '';

						$wps_price = wps_sfw_get_time_interval_for_price( $subscription_number, $subscription_interval );

						$subscription_box_renewal_amount = wps_sfw_get_time_interval( $subscription_expiry_number, $subscription_expiry_interval );

						$renewal_amount = $product_price . '/' . $wps_price . ' For ' . $subscription_box_renewal_amount;

						$product_name = esc_html( $cart_item['data']->get_name() ) . '<br>';
						$product_name = apply_filters( 'wps_sfw_product_name_modification_checkout', $product_name, $cart_item );
						$content .=
							'<div class="wps_recurring_bifurcation_wrapper"><tr class="order-total wps_wsp_recurring_total_tr">
						<th><h4>' . esc_attr__( 'Renewal For', 'subscriptions-for-woocommerce' ) . ' ' . wp_kses_post( $product_name ) . '</h4></th>
						<td>
						<ul>
							<li><label>' . esc_html__( 'Subtotal', 'subscriptions-for-woocommerce' ) . ':</label> <span>' . wp_kses_post( wc_price( $line_data['line_subtotal'] ) ) . '</span></li>';
						if ( isset( $line_data['line_tax'] ) && ! empty( $line_data['line_tax'] ) ) {
							$content .= '<li><label>' . esc_html__( 'Tax', 'subscriptions-for-woocommerce' ) . ':</label><span> ' . wp_kses_post( wc_price( $line_data['line_tax'] ) ) . '</span></li>';
						}
						if ( isset( $line_data['shipping_fee'] ) && ! empty( $line_data['shipping_fee'] ) ) {
							$content .= '<li><label>' . esc_html__( 'Shipping', 'subscriptions-for-woocommerce' ) . ':</label><span> ' . wp_kses_post( wc_price( $line_data['shipping_fee'] ) ) . '</span></li>';
						}
						$content .= '<li><label>' . esc_html__( 'Total', 'subscriptions-for-woocommerce' ) . ':</label><span> ' . wp_kses_post( $renewal_amount ) . '</span></li></ul></td><tr></div>';
					}
					// subscription box.
				}
			}
		}
		return $content;
	}

		/**
		 * Add additional cart item data to the subscription products.
		 *
		 * @param array $data Cart item data.
		 * @param array $cart_item .
		 * @return array
		 */
	public function wps_sfw_get_subscription_meta_on_cart( $data = array(), $cart_item = array() ) {

		if ( isset( $cart_item['variation_id'] ) && ! empty( $cart_item['variation_id'] ) ) {
			$wps_subscription_product = wps_sfw_get_meta_data( $cart_item['variation_id'], 'wps_sfw_variable_product', true );
		} else {
			$wps_subscription_product = wps_sfw_get_meta_data( $cart_item['product_id'], '_wps_sfw_product', true );
		}
		if ( 'yes' !== $wps_subscription_product || ! wps_sfw_check_product_is_subscription( $cart_item['data'] ) ) {
			return $data;
		}
		$price   = null;
		$product = $cart_item['data'];
		if ( is_object( $product ) ) {
			$product_id = $product->get_id();
			$wps_sfw_subscription_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_number', true );
			$wps_sfw_subscription_expiry_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_expiry_number', true );
			$wps_sfw_subscription_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_interval', true );
			$wps_sfw_subscription_free_trial_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_free_trial_number', true );

			if ( ! empty( $wps_sfw_subscription_free_trial_number ) && ! $cart_item['data']->get_price() && $product_id && wc_get_product( $product_id ) ) {
				// $price =  wc_get_product( $product_id )->get_price() ;
				$price = sprintf( esc_html__( ' and %1$1s%2$2s', 'subscriptions-for-woocommerce' ), get_woocommerce_currency_symbol(), wc_get_product( $product_id )->get_price() );
			}

			$wps_sfw_subscription_initial_signup_price = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_initial_signup_price', true );

			if ( isset( $wps_sfw_subscription_number ) && ! empty( $wps_sfw_subscription_number ) ) {
				$wps_price_html = wps_sfw_get_time_interval_for_price( $wps_sfw_subscription_number, $wps_sfw_subscription_interval );

				/* translators: %s: susbcription interval */
				$wps_sfw_price_html = sprintf( esc_html( ' / %s ' ), $wps_price_html );

				$price .= apply_filters( 'wps_sfw_show_sync_interval', $wps_sfw_price_html, $product_id );
			}
			if ( isset( $wps_sfw_subscription_expiry_number ) && ! empty( $wps_sfw_subscription_expiry_number ) ) {
				$wps_sfw_subscription_expiry_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_expiry_interval', true );

				$wps_price_expiry_html = wps_sfw_get_time_interval( $wps_sfw_subscription_expiry_number, $wps_sfw_subscription_expiry_interval );
				/* translators: %s: susbcription expiry interval */
				$price .= sprintf( esc_html__( ' For %s ', 'subscriptions-for-woocommerce' ), $wps_price_expiry_html );
			}
			// Add free trial html.
			$price = $this->wps_sfw_get_free_trial_period_html( $product_id, $price );
			// Add initial signup fee html.
			$price = $this->wps_sfw_get_initial_signup_fee_html( $product_id, $price );
			// return correct price format.
			$price = apply_filters( 'wps_sfw_show_one_time_subscription_price_block', $price, $product_id, $cart_item );

			// Do not allow subscription price for the one-time product.
			if ( $price ) {
				$data[] = apply_filters(
					'wps_sfw_block_cart_price',
					array(
						'name'   => 'wps-sfw-price-html',
						'hidden' => true,
						'value'  => html_entity_decode( $price ),
					),
					$cart_item
				);
			}
		}
		return $data;
	}

		/**
		 * Subscription creation from the submission from the WC checkout block
		 *
		 * @param object $order .
		 * @throws \Exception Return error.
		 */
	public function wps_sfw_process_checkout_hpos( $order ) {

		/*delete failed order subscription*/
		wps_sfw_delete_failed_subscription( $order->get_id() );

		if ( ! wps_sfw_is_cart_has_subscription_product() || isset( $_REQUEST['cancel_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$order_id = $order->get_id();
		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				$wps_skip_creating_subscription = apply_filters( 'wps_skip_creating_subscription', true, $cart_item );
				if ( wps_sfw_check_product_is_subscription( $cart_item['data'] ) && $wps_skip_creating_subscription ) {
					if ( $cart_item['data']->is_on_sale() ) {
						$price = $cart_item['data']->get_sale_price();
					} else {
						$price = $cart_item['data']->get_regular_price();
					}
					// $wps_recurring_total = $price * $cart_item['quantity'];

					$product_id = $cart_item['data']->get_id();

					$wps_recurring_data = $this->wps_sfw_get_subscription_recurring_data( $product_id, $cart_item );

					$line_data = $this->wps_sfw_calculate_recurring_price( $cart_item, false );

					$show_price = $line_data['line_total'] + $line_data['line_tax'];

					$wps_recurring_data['wps_recurring_total'] = $show_price;
					$wps_recurring_data['wps_show_recurring_total'] = $show_price;
					$wps_recurring_data['product_id'] = $product_id;
					$wps_recurring_data['product_name'] = $cart_item['data']->get_name();
					$wps_recurring_data['product_qty'] = $cart_item['quantity'];

					$wps_recurring_data['line_tax_data'] = $line_data['line_tax_data'];
					$wps_recurring_data['line_subtotal'] = $line_data['line_subtotal'];
					$wps_recurring_data['line_subtotal_tax'] = $line_data['line_subtotal_tax'];
					$wps_recurring_data['line_total'] = $line_data['line_total'];
					$wps_recurring_data['line_tax'] = $line_data['line_tax'];

					$wps_recurring_data['cart_price'] = $line_data['line_total'] + $line_data['line_tax'];

					$wps_recurring_data = apply_filters( 'wps_sfw_cart_data_for_susbcription', $wps_recurring_data, $cart_item );

					if ( apply_filters( 'wps_sfw_is_upgrade_downgrade_order', false, $wps_recurring_data, $order, array(), $cart_item ) ) {
						return;
					}

					$subscription = $this->wps_sfw_create_subscription( $order, array(), $wps_recurring_data, $cart_item );
					if ( is_wp_error( $subscription ) ) {
						throw new Exception( esc_html( $subscription->get_error_message() ) );
					} else {
						$wps_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_order_has_subscription', true );
						if ( 'yes' != $wps_has_susbcription ) {
							wps_sfw_update_meta_data( $order_id, 'wps_sfw_order_has_subscription', 'yes' );
						}
						do_action( 'wps_sfw_subscription_process_checkout', $order_id, $wps_recurring_data, $subscription );
					}
				}
			}
			$wps_has_susbcription = wps_sfw_get_meta_data( $order_id, 'wps_sfw_order_has_subscription', true );

			if ( 'yes' === $wps_has_susbcription ) {
				// phpcs:disable WordPress.Security.NonceVerification.Missing
				// After process checkout.
				do_action( 'wps_sfw_subscription_process_checkout_payment_method', $order_id, $wps_recurring_data );
				// phpcs:enable WordPress.Security.NonceVerification.Missing
			}
		}
	}

		/**
		 * WPS Paypal support during wc checkout block
		 */
	public function wsp_sfw_wps_paypal_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-wps-paypal-block-support.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WPS_Paypal_Block_Support() );
				}
			);
		}
	}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name wps_sfw_cancel_stripe_subscription
		 * @param int    $wps_subscription_id wps_subscription_id.
		 * @param string $status status.
		 */
	public function wps_sfw_cancel_manual_subscription( $wps_subscription_id, $status ) {

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$subscription = new WPS_Subscription( $wps_subscription_id );
			$wps_payment_method = $subscription->get_payment_method();
		} else {
			$wps_payment_method = get_post_meta( $wps_subscription_id, '_payment_method', true );
		}
		if ( 'Cancel' == $status ) {
			if ( ( 'cod' == $wps_payment_method ) || ( 'bacs' == $wps_payment_method ) || ( 'cheque' == $wps_payment_method ) ) {
				wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
				wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
			}
		}
	}

		/**
		 * Add custom_failed_order_section
		 *
		 * @param mixed $order .
		 * @param mixed $sent_to_admin .
		 * @param mixed $plain_text .
		 * @param mixed $email .
		 * @return void
		 */
	public function wps_sfw_add_custom_failed_order_section( $order, $sent_to_admin, $plain_text, $email ) {

		if ( 'failed_order' === $email->id && 'yes' === $order->get_meta( 'wps_sfw_renewal_order' ) ) {
			$subscription_id = $order->get_meta( 'wps_sfw_subscription' );
			/* translators: %s: subscription id */
			$notice = sprintf( __( 'This renewal order belongs to Subscription #%s', 'subscriptions-for-woocommerce' ), $subscription_id );
			?>
			<h2><?php esc_attr_e( 'Important Information', 'subscriptions-for-woocommerce' ); ?></h2>
			<p><?php echo esc_html( $notice ); ?></p>
			<?php
		}
	}
		/**
		 * Add custom_woocommerce_email_subject_failed_order
		 *
		 * @param mixed $subject .
		 * @param mixed $order .
		 * @return mixed
		 */
	public function wps_sfw_custom_woocommerce_email_subject_failed_order( $subject, $order ) {
		if ( 'yes' === $order->get_meta( 'wps_sfw_renewal_order' ) ) {
			$subject = str_replace( 'Order', esc_attr__( 'Renewal Order', 'subscriptions-for-woocommerce' ), $subject );
			return $subject;
		}
		return $subject;
	}
		/**
		 * Add custom_woocommerce_email_heading_failed_order
		 *
		 * @param mixed $heading .
		 * @param mixed $order .
		 * @return mixed
		 */
	public function wps_sfw_custom_woocommerce_email_heading_failed_order( $heading, $order ) {
		if ( 'yes' === $order->get_meta( 'wps_sfw_renewal_order' ) ) {
			$heading = str_replace( 'Order', esc_attr__( 'Renewal Order', 'subscriptions-for-woocommerce' ), $heading );
			return $heading;
		}
		return $heading;
	}

		/**
		 * Show the product course description for the simple product.
		 */
	public function wps_sfw_course_description() {
		if ( ! function_exists( 'learn_press_get_course' ) ) {
			return;
		}
		global $product;

		// Get the product ID.
		$product_id = $product->get_id();

		// Check if product is simple.
		if ( $product->is_type( 'simple' ) ) {

			$saved_courses = get_post_meta( $product_id, 'wps_learnpress_course', true ) ? get_post_meta( $product_id, 'wps_learnpress_course', true ) : array();

			$course_name = null;
			if ( ! empty( $saved_courses ) ) {
				foreach ( $saved_courses as $course_id ) {
					$course        = learn_press_get_course( $course_id );
					$course_name[] = $course->get_title();
				}
				?>
				<div class="wps-product-notice"><?php esc_attr_e( 'You will be subscribing to', 'subscriptions-for-woocommerce' ); ?> <?php echo esc_attr( implode( ', ', $course_name ) ); ?></div>
				<?php
			}
		}
		do_action( 'wps_sfw_product_summary', $product );
	}

		/**
		 * Override the default learnpress courses's lessions and quiz view
		 *
		 * @param mixed $view .
		 * @param mixed $item .
		 * @param mixed $user .
		 */
	public function wps_sfw_course_can_view( $view, $item, $user ) {
		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) || is_admin() || ! class_exists( 'LP_Model_User_Can_View_Course_Item' ) ) {
			return $view;
		}
		$course = learn_press_get_the_course();
		$current_course_id = $course->get_id();
		$all_attached_courses = get_option( 'wps_learnpress_course', array() );
		$attached_product_ids = array();
		if ( empty( array_filter( $all_attached_courses, fn( $values ) => is_array( $values ) && in_array( $current_course_id, $values ) ) ) ) {
			return $view;
		} else {
			// Make only attached courses to be checked.
			$all_attached_courses = array_filter( $all_attached_courses, fn( $values ) => is_array( $values ) && in_array( $current_course_id, $values ) );
			$attached_product_ids = array_keys( $all_attached_courses );
		}
		$user_id = $user->get_id();
		$hpos_enabled = OrderUtil::custom_orders_table_usage_is_enabled();

		$args = array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'   => 'wps_customer_id',
					'value' => $user->get_id(),
				),
			),
		);
		if ( $hpos_enabled ) {
			$args['type'] = 'wps_subscriptions';
			$args['return'] = 'ids';
			$wps_subscriptions = wc_get_orders( $args );
		} else {
			$args['post_type'] = 'wps_subscriptions';
			$args['post_status'] = 'wc-wps_renewal';
			$args['numberposts'] = -1;
			$wps_subscriptions = get_posts( $args );
		}
		$message = '';
		if ( ! empty( $wps_subscriptions ) ) {
			foreach ( $wps_subscriptions as $wps_subscription ) {
				if ( $hpos_enabled ) {
					$subcription_id = $wps_subscription;
				} else {
					$subcription_id = $wps_subscription->ID;
				}
				$courses = wps_sfw_get_meta_data( $subcription_id, 'wps_learnpress_course', true );
				if ( empty( $courses ) ) {
					$courses = array();
				}
				$status  = wps_sfw_get_meta_data( $subcription_id, 'wps_subscription_status', true );
				if ( is_array( $courses ) && in_array( $current_course_id, $courses ) && 'active' !== $status ) {
					$message = '<div class="lp-warning" style="padding: 10px; background: #ffcccc; border-left: 5px solid red; margin-bottom: 15px;">';
					$message .= '<strong>' . esc_attr__( 'Your subscription is not active', 'subscriptions-for-woocommerce' ) . '.</strong> ' . esc_attr__( 'Please renew or purchase a subscription to access this course', 'subscriptions-for-woocommerce' ) . ':<br>';
					$subscription_link = home_url( "/my-account/show-subscription/$subcription_id/" );
					$message .= ' <a target="__blank" href="' . esc_url( $subscription_link ) . '" style="display: block; margin-top: 5px; font-weight: bold;">' . esc_html( '#' . $subcription_id ) . '</a>';
					$message .= '</div>';
				} elseif ( is_array( $courses ) && ! in_array( $current_course_id, $courses ) ) {
					$message = '<div class="lp-warning wps-sfw-learnpress-message" style="padding: 10px; background: #ffcccc; border-left: 5px solid red; margin-bottom: 15px;">';
					$message .= '<strong>' . esc_attr__( 'You have not purchased the attached course subscription yet', 'subscriptions-for-woocommerce' ) . '.</strong> ' . esc_attr__( 'Please purchase one of the following subscriptions to access this course', 'subscriptions-for-woocommerce' ) . ':<br>';
					foreach ( $attached_product_ids as $product_id ) {
						$product_link = get_permalink( $product_id );
						$product_name = get_the_title( $product_id );
						$message .= ' <a target="__blank" href="' . esc_url( $product_link ) . '" style="display: block; margin-top: 5px; font-weight: bold;">' . esc_html( $product_name ) . '</a>';
					}
					$message .= '</div>';
				} else {
					break;
				}
			}
		} else {
			$message = '<div class="lp-warning wps-sfw-learnpress-message" style="padding: 10px; background: #ffcccc; border-left: 5px solid red; margin-bottom: 15px;">';
			$message .= '<strong>' . esc_attr__( 'You have not purchased the subscription yet', 'subscriptions-for-woocommerce' ) . '.</strong> ' . esc_attr__( 'Please purchase one of the following subscriptions to access this course', 'subscriptions-for-woocommerce' ) . ':<br>';
			foreach ( $attached_product_ids as $product_id ) {
				$product_link = get_permalink( $product_id );
				$product_name = get_the_title( $product_id );
				$message .= ' <a target="__blank" href="' . esc_url( $product_link ) . '" style="display: block; margin-top: 5px; font-weight: bold;">' . esc_html( $product_name ) . '</a>';
			}
			$message .= '</div>';
		}
		if ( $message ) {
			// Assuming you have the object.
			$view = new LP_Model_User_Can_View_Course_Item();
			// Override properties.
			$view->flag = null;
			$view->key = '';
			$view->message = $message;
		}
		return $view;
	}

		/**
		 * Allow the zero checkout for the stripe
		 *
		 * @param mixed $needs_payment .
		 * @param mixed $order .
		 * @param mixed $valid_order_statuses .
		 *
		 * @return mixed
		 */
	public function wps_sfw_woocommerce_order_needs_payment( $needs_payment, $order, $valid_order_statuses ) {
		// Skips checks if the order already needs payment.
		if ( $needs_payment ) {
			return $needs_payment;
		}
		// Skip checks if order doesn't contain a subscription product.
		if ( 0 >= $order->get_total() && wps_sfw_order_has_subscription( $order->get_id() ) && ! $order->has_status( array( 'processing', 'completed' ) ) ) {
			return true;
		}
		return $needs_payment;
	}






	/**
	 * Show the subscription box product "Create Subscription Box" button on the single product page.
	 */
	public function wps_sfw_subscription_box_create_button() {
		global $product;

		if ( ! is_product() || ! $product ) {
			return;
		}

		$product_type = $product->get_type();
		$product_id   = $product->get_id();

		// Only for subscription box product.
		if ( 'subscription_box' !== $product_type ) {
			return;
		}

		// Button text.
		$btn_text_opt = get_option( 'wps_sfw_subscription_box_add_to_cart_text' );
		$button_text  = $btn_text_opt ? $btn_text_opt : __( 'Create Subscription Box', 'subscriptions-for-woocommerce' );

		// ---- Plan/pricing meta ----.
		$wps_sfw_subscription_box_number   = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_number', true );
		$wps_sfw_subscription_box_number   = $wps_sfw_subscription_box_number ? $wps_sfw_subscription_box_number : 1;

		$wps_sfw_subscription_box_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_interval', true );
		$wps_sfw_subscription_box_interval = $wps_sfw_subscription_box_interval ? $wps_sfw_subscription_box_interval : 'day';

		$wps_sfw_subscription_box_expiry_number   = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_box_expiry_number', true );
		$wps_sfw_subscription_box_expiry_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_box_expiry_interval', true );

		$wps_sfw_subscription_box_price = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_box_price', true );

		// PRO gating (if managed by Pro, price is 0).
		$wps_sfw_manage_subscription_box_price = wps_sfw_get_meta_data( $product_id, 'wps_sfw_manage_subscription_box_price', true );
		$is_pro = false;
		$is_pro = apply_filters( 'wsp_sfw_check_pro_plugin', $is_pro );
		if ( 'on' === $wps_sfw_manage_subscription_box_price && $is_pro ) {
			$wps_sfw_subscription_box_price = 0;
		}

		// ---- Helper: normalize category identifiers to term IDs ----.
		$resolve_term_ids = function ( $maybe_terms ) {
			static $cache = array();
			$out = array();

			foreach ( (array) $maybe_terms as $val ) {
				if ( '' === $val || null === $val ) {
					continue;
				}
				if ( is_numeric( $val ) ) {
					$out[] = (int) $val;
					continue;
				}
				$key = 'cat:' . strtolower( trim( (string) $val ) );
				if ( isset( $cache[ $key ] ) ) {
					if ( $cache[ $key ] ) {
						$out[] = $cache[ $key ];
					}
					continue;
				}
				$term = get_term_by( 'slug', $val, 'product_cat' );
				if ( ! $term ) {
					$term = get_term_by( 'name', $val, 'product_cat' );
				}
				if ( $term && ! is_wp_error( $term ) ) {
					$cache[ $key ] = (int) $term->term_id;
					$out[]         = (int) $term->term_id;
				} else {
					$cache[ $key ] = 0;
				}
			}

			return array_values( array_unique( array_filter( array_map( 'absint', $out ) ) ) );
		};

			// ---- Load step settings ----
			$steps = get_post_meta( $product_id, 'wps_sfw_step_settings', true );
			$steps = is_array( $steps ) ? $steps : array();

			// Legacy fallback.
		if ( empty( $steps ) ) {
			$legacy_type       = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_box_setup', true );
			$legacy_products   = (array) wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_box_products', true );
			$legacy_categories = (array) wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_box_categories', true );
			$legacy_label      = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_box_step_label', true );

			if ( $legacy_type || $legacy_label || $legacy_products || $legacy_categories ) {
				$steps = array(
					'step1' => array(
						'type'         => $legacy_type ? $legacy_type : 'specific_products',
						'product_ids'  => array_map( 'absint', (array) $legacy_products ),
						'category_ids' => $resolve_term_ids( $legacy_categories ),
						'label'        => $legacy_label ? $legacy_label : '',
					),
				);
			}
		}

			// Normalize order + sanitize IDs.
		if ( ! empty( $steps ) ) {
				uksort(
					$steps,
					function ( $a, $b ) {
						$ia = (int) preg_replace( '/\D+/', '', (string) $a );
						$ib = (int) preg_replace( '/\D+/', '', (string) $b );
						return $ia <=> $ib;
					}
				);
			foreach ( $steps as $k => $st ) {
				$steps[ $k ]['product_ids']  = isset( $st['product_ids'] ) ? array_values( array_unique( array_map( 'absint', (array) $st['product_ids'] ) ) ) : array();
				$steps[ $k ]['category_ids'] = isset( $st['category_ids'] ) ? $resolve_term_ids( $st['category_ids'] ) : array();
				// Leave min_num/max_num untouched here; handled in render.
			}
		}

		if ( empty( $steps ) ) {
			return;
		}

			// ---- Pro/Free behavior ----
			$render_steps = $steps;
		if ( ! $is_pro ) {
			// FREE: only first non-empty step.
			$first_nonempty = null;
			foreach ( $steps as $s ) {
				if ( ! empty( $s['product_ids'] ) || ! empty( $s['category_ids'] ) ) {
					$first_nonempty = $s;
					break;
				}
			}
			if ( ! $first_nonempty ) {
				return;
			}
			$label = ! empty( $first_nonempty['label'] ) ? $first_nonempty['label'] : __( 'Step 1', 'subscriptions-for-woocommerce' );
			$render_steps = array(
				'step1' => array(
					'type'         => isset( $first_nonempty['type'] ) ? $first_nonempty['type'] : 'specific_products',
					'product_ids'  => isset( $first_nonempty['product_ids'] ) ? array_map( 'absint', (array) $first_nonempty['product_ids'] ) : array(),
					'category_ids' => isset( $first_nonempty['category_ids'] ) ? $resolve_term_ids( $first_nonempty['category_ids'] ) : array(),
					'label'        => $label,
				),
			);
		}

			// ---- Helpers to render grids ----
			$render_products_list = function ( $product_ids, $is_pro_local ) {
				$product_ids = array_values( array_unique( array_map( 'absint', (array) $product_ids ) ) );
				if ( empty( $product_ids ) ) {
					echo '<p>' . esc_html__( 'No products available for this step.', 'subscriptions-for-woocommerce' ) . '</p>';
					return;
				}
				echo '<div class="wps_sfw_sub_box_prod_container">';
				foreach ( $product_ids as $pid ) {
					$_p = wc_get_product( $pid );
					if ( ! $_p ) {
						continue;
					}
					$name  = $_p->get_name();
					$img   = wp_get_attachment_image( $_p->get_image_id(), 'medium' );
					$price = $_p->get_price();

					echo '<div class="wps_sfw_sub_box_prod_item">';
					echo '<div class="wps_sfw_sub_box_prod_image">' . wp_kses_post( $img ) . '</div>';
					echo '<div class="wps_sfw_sub_box_prod_name">' . esc_html( $name ) . '</div>';
					echo '<div class="wps_sfw_sub_box_prod_qty">';
					echo '<button class="wps_sfw_sub_box_prod_minus_btn" style="display:none;" data-product-id="' . esc_attr( $pid ) . '">-</button>';
					if ( $is_pro_local ) {
						echo '<input type="number" class="wps_sfw_sub_box_prod_count" data-wps_sfw_sub_box_price="' . esc_attr( $price ) . '" value="0" style="display:none; width:40px; text-align:center;" data-product-id="' . esc_attr( $pid ) . '">';
					} else {
						echo '<input type="number" class="wps_sfw_sub_box_prod_count" data-wps_sfw_sub_box_price="' . esc_attr( $price ) . '" value="0" min="0" max="1" style="display:none; width:40px; text-align:center;" data-product-id="' . esc_attr( $pid ) . '">';
					}
					echo '<button class="wps_sfw_sub_box_prod_add_btn" data-product-id="' . esc_attr( $pid ) . '">+</button>';
					echo '</div>';
					echo '</div>';
				}
				echo '</div>';
			};

			$render_categories_grid = function ( $category_ids, $is_pro_local ) use ( $render_products_list, $resolve_term_ids ) {
				$category_ids = $resolve_term_ids( $category_ids );
				if ( empty( $category_ids ) ) {
					echo '<p>' . esc_html__( 'No categories configured for this step.', 'subscriptions-for-woocommerce' ) . '</p>';
					return;
				}
				$q = new WP_Query(
					array(
						'post_type'      => 'product',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'tax_query'      => array(
							array(
								'taxonomy' => 'product_cat',
								'field'    => 'term_id',
								'terms'    => $category_ids,
								'operator' => 'IN',
							),
						),
					)
				);
				$ids = $q->have_posts() ? (array) $q->posts : array();
				wp_reset_postdata();

				$render_products_list( $ids, $is_pro_local );
			};

			// ---- Trigger button ----
			echo '<a href="#" class="button wps_sfw_subs_box-button" data-product-id="' . esc_attr( $product_id ) . '" data-product-type="' . esc_attr( $product_type ) . '">' . esc_html( $button_text ) . '</a>';

			// ---- Popup (once per page) ----
			static $popup_added = false;
		if ( $popup_added ) {
			return;
		}
		?>
		<div id="wps_sfw_subs_box-popup" class="wps_sfw_subs_box-overlay">
			<div class="wps_sfw_subs_box-content">
				<span class="wps_sfw_subs_box-close">&times;</span>
				<form id="wps_sfw_subs_box-form">


				<?php
				$total_steps = count( $render_steps );
				$step_index  = 1;

				foreach ( $render_steps as $step_key => $step ) :
					$type         = isset( $step['type'] ) ? $step['type'] : 'specific_products';
					$label        = isset( $step['label'] ) && '' !== $step['label'] ? $step['label'] : sprintf( __( 'Step %d', 'subscriptions-for-woocommerce' ), $step_index );
					$product_ids  = isset( $step['product_ids'] ) ? (array) $step['product_ids'] : array();
					$category_ids = isset( $step['category_ids'] ) ? (array) $step['category_ids'] : array();
					$min_num      = ( $is_pro && isset( $step['min_num'] ) ) ? absint( $step['min_num'] ) : 0;
					$max_num      = ( $is_pro && isset( $step['max_num'] ) ) ? absint( $step['max_num'] ) : 0;

					$display_attr = ( 1 === $step_index ) ? '' : 'style="display:none"';

					echo '<div class="wps_sfw-sb-step" 
					        data-step-index="' . esc_attr( $step_index ) . '" 
					        data-min-num="' . esc_attr( $min_num ) . '" 
					        data-max-num="' . esc_attr( $max_num ) . '" 
					        ' . $display_attr . '>';
					?>
						<div class="wps_sfw-sb-title">
							<p class="wps_sfw_subscription_box_error_notice" style="display:none; color:red;"></p>
							<h2><?php esc_html_e( 'Customize Your Subscription Box', 'subscriptions-for-woocommerce' ); ?>


						<?php
						echo '<span class="wps_sfw-sb-step-label">' . esc_html( $label ) . '</span></h2></div>';

						if ( 'specific_products' === $type ) {
							$render_products_list( $product_ids, $is_pro );
						} else {
							$render_categories_grid( $category_ids, $is_pro );
						}
						echo '</div>';

						$step_index++;
						endforeach;
				?>

							<div class="wps_sfw-sb-cta">
								<div class="wps_sfw-sb-cta-total" data-wps_sfw_subscription_box_price="<?php echo esc_attr( $wps_sfw_subscription_box_price ); ?>">
									<strong><?php esc_html_e( 'Total', 'subscriptions-for-woocommerce' ); ?>:</strong>
								<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
									<span><?php echo esc_html( $wps_sfw_subscription_box_price ); ?></span>
								</div>
								<div class="wps_sfw-sb-step-actions" style="display:flex; gap:8px; justify-content:flex-end;">
								<?php
								if ( $is_pro && $total_steps > 1 ) {
									echo '<button type="button" class="button wps_sfw-sb-prev" style="display:none;">' . esc_html__( 'Back', 'subscriptions-for-woocommerce' ) . '</button>';
									echo '<button type="button" class="button wps_sfw-empty-cart" id="wps_sfw-empty-cart" style="display:none;">' . esc_html__( 'Empty Cart', 'subscriptions-for-woocommerce' ) . '</button>';
									echo '<button type="button" class="button button-primary wps_sfw-sb-next">' . esc_html__( 'Next', 'subscriptions-for-woocommerce' ) . '</button>';
									echo '<button type="submit" class="button button-primary wps_sfw_subscription_product_id" data-subscription-box-id="' . esc_attr( $product_id ) . '" style="display:none;">' . esc_html__( 'Add to Basket', 'subscr	iptions-for-woocommerce' ) . '</button>';
								} else {
									echo '<button type="button" class="button wps_sfw-empty-cart" id="wps_sfw-empty-cart" style="display:none;">' . esc_html__( 'Empty Cart', 'subscriptions-for-woocommerce' ) . '</button>';
									echo '<button type="submit" class="button button-primary wps_sfw_subscription_product_id" data-subscription-box-id="' . esc_attr( $product_id ) . '">' . esc_html__( 'Add to Basket', 'subscriptions-for-woocommerce' ) . '</button>';
								}
								?>
								</div>
							</div>

							<input type="hidden" name="wps_sfw_subscription_box_number" value="<?php echo esc_attr( $wps_sfw_subscription_box_number ); ?>">
							<input type="hidden" name="wps_sfw_subscription_box_interval" value="<?php echo esc_attr( $wps_sfw_subscription_box_interval ); ?>">
							<?php if ( $wps_sfw_subscription_box_expiry_number && $wps_sfw_subscription_box_expiry_interval ) : ?>
								<input type="hidden" name="wps_sfw_subscription_box_expiry_number" value="<?php echo esc_attr( $wps_sfw_subscription_box_expiry_number ); ?>">
								<input type="hidden" name="wps_sfw_subscription_box_expiry_interval" value="<?php echo esc_attr( $wps_sfw_subscription_box_expiry_interval ); ?>">
							<?php endif; ?>
				</form>
			</div>
		</div>
		<?php
		$popup_added = true;
	}
	/**
	 * Function to Add attached subscription box info into subscription and renewal.
	 *
	 * @param object $new_order_id as subscription or renewal order id.
	 * @param object $wps_old_id as parent order id.
	 * @param object $product as product.
	 * @return void
	 */
	public function wps_sfw_subscription_subscription_box_addtion_callback( $new_order_id, $wps_old_id, $product ) {
		$order = wc_get_order( $wps_old_id );
		if ( empty( $order ) || empty( $product ) ) {
			return;
		}

		$order_items = $order->get_items();
		$temp = 0;
		$product_type = $product->get_type();

		// Check if the cart item is a subscription_box.

		foreach ( $order_items as $items_key => $items_value ) {
			if ( 0 != $temp && 'subscription_box' == $product_type ) {
				$order_item_id = wc_add_order_item(
					$new_order_id,
					array(
						'order_item_name' => $items_value['name'], // may differ from the product name.
						'order_item_type' => 'line_item', // product.
					)
				);
				if ( $order_item_id ) {
					// Provide its meta information.
					wc_add_order_item_meta( $order_item_id, '_qty', $items_value['qty'], true ); // Quantity.
					wc_add_order_item_meta( $order_item_id, '_product_id', $items_value['product_id'], true ); // ID of the product.
				}
			}
			$temp++;
		}
	}

	/**
	 * Cart page handling for subscription box.
	 *
	 * @return void
	 */
	public function wps_sfw_handle_subscription_box() {

		check_ajax_referer( 'wps_sfw_public_nonce', 'nonce' );

		if ( empty( $_POST['subscription_data'] ) ) {
			wp_send_json( 'Missing subscription data.' );
		}

		$subscription_data = json_decode( stripslashes( $_POST['subscription_data'] ), true );

		if ( ! is_array( $subscription_data ) ) {
			wp_send_json( 'Invalid JSON data.' );
		}

		// -------------------------
		// REQUIRED INPUTS
		// -------------------------
		$subscription_product_id = isset( $subscription_data['wps_sfw_subscription_product_id'] )
			? absint( $subscription_data['wps_sfw_subscription_product_id'] )
			: 0;

		$products = isset( $subscription_data['products'] ) && is_array( $subscription_data['products'] )
			? $subscription_data['products']
			: array();

		// Client supplied total (KEEPING YOUR LOGIC)
		$client_total = isset( $subscription_data['total'] )
			? floatval( sanitize_text_field( $subscription_data['total'] ) )
			: 0;

		if ( ! $subscription_product_id || empty( $products ) ) {
			wp_send_json( 'Invalid subscription request.' );
		}

		if ( $client_total <= 0 ) {
			wp_send_json( 'Invalid total amount.' );
		}

		$cart = WC()->cart->get_cart();
		$has_subscription = false;
		$has_other_items  = false;

		foreach ( $cart as $item ) {
			if ( ! empty( $item['is_subscription_main'] ) ) {
				$has_subscription = true;
			} else {
				$has_other_items = true;
			}
		}

		if ( $has_other_items ) {
			wp_send_json( 'You cannot add a subscription box when other products are in the cart.' );
		}

		if ( $has_subscription ) {
			wp_send_json( 'A subscription box already exists in the cart.' );
		}

		$subscription_product = wc_get_product( $subscription_product_id );

		if ( ! $subscription_product ) {
			wp_send_json( 'Subscription product not found.' );
		}

		$server_total        = 0;
		$attached_products   = array();

		foreach ( $products as $product ) {

			$product_id = isset( $product['product_id'] ) ? absint( $product['product_id'] ) : 0;
			$quantity   = isset( $product['quantity'] ) ? max( 1, absint( $product['quantity'] ) ) : 1;

			if ( ! $product_id ) {
				continue;
			}

			$product_obj = wc_get_product( $product_id );

			if ( ! $product_obj ) {
				continue;
			}

			$price = (float) $product_obj->get_price();

			if ( $price <= 0 ) {
				wp_send_json( 'Invalid product price.' );
			}

			$server_total += $price * $quantity;

			$attached_products[] = array(
				'product_id' => $product_id,
				'name'       => $product_obj->get_name(),
				'image'      => wp_get_attachment_image_url( $product_obj->get_image_id(), 'thumbnail' ),
				'quantity'   => $quantity,
			);
		}

		// Base subscription price
		$base_price   = (float) $subscription_product->get_price();
		$server_total += $base_price;

		$wps_sfw_manage_subscription_box_price = wps_sfw_get_meta_data( $subscription_product_id, 'wps_sfw_manage_subscription_box_price', true );
		$is_pro = false;
		$is_pro = apply_filters( 'wsp_sfw_check_pro_plugin', $is_pro );
		if ( 'on' === $wps_sfw_manage_subscription_box_price && $is_pro ) {

			$server_total = $server_total - $base_price;

			$final_total = $server_total;
		} else {

			$wps_sfw_subscription_box_price = wps_sfw_get_meta_data(
				$subscription_product_id,
				'wps_sfw_subscription_box_price',
				true
			);

			$wps_sfw_subscription_box_price = floatval( $wps_sfw_subscription_box_price );

			$final_total = $wps_sfw_subscription_box_price;

		}

		if ( $final_total <= 0 ) {
			wp_send_json_error( 'Invalid subscription total.' );
		}

		$cart_item_data = array(
			'is_subscription_main'           => true,
			'wps_sfw_subscription_box_price' => wc_format_decimal( $final_total ),
			'wps_sfw_attached_products'      => $attached_products,

			'wps_sfw_subscription_number'          => wps_sfw_get_meta_data( $subscription_product_id, 'wps_sfw_subscription_number', true ),
			'wps_sfw_subscription_interval'        => wps_sfw_get_meta_data( $subscription_product_id, 'wps_sfw_subscription_interval', true ),
			'wps_sfw_subscription_expiry_number'   => wps_sfw_get_meta_data( $subscription_product_id, 'wps_sfw_subscription_expiry_number', true ),
			'wps_sfw_subscription_expiry_interval' => wps_sfw_get_meta_data( $subscription_product_id, 'wps_sfw_subscription_expiry_interval', true ),
		);

		WC()->cart->add_to_cart(
			$subscription_product_id,
			1,
			0,
			array(),
			$cart_item_data
		);

		wp_send_json(
			array(
				'message' => 'Subscription added to cart!',
				'total' => $final_total,
				'products' => $products,
			)
		);
	}




	/**
	 * Function to handle subscription main and attached product.
	 *
	 * @param array $cart as cart.
	 * @return void
	 */
	public function wps_sfw_update_subscription_box_prices( $cart ) {
		// Only run on frontend/AJAX.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Prevent multiple recalculations.
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		// First, loop through cart items to record main subscription products.
		$subscription_main_ids = array();
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['is_subscription_main'] ) && $cart_item['is_subscription_main'] ) {
				$main_product_id = $cart_item['data']->get_id();
				$subscription_main_ids[ $main_product_id ] = true;
				$total = (float) $cart_item['wps_sfw_subscription_box_price'];
				$cart_item['data']->set_price( $total );
			}
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! empty( $cart_item['wps_sfw_subscription_box_attached_product'] ) && true === $cart_item['wps_sfw_subscription_box_attached_product'] ) {
				if ( isset( $cart_item['wps_sfw_subscription_box_id'] ) ) {
					$main_id = intval( $cart_item['wps_sfw_subscription_box_id'] );
					if ( isset( $subscription_main_ids[ $main_id ] ) ) {
						// Override the price of the attached product to 0.
						$cart_item['data']->set_price( 0 );
					}
				}
			}
		}
	}

		/**
		 * Update subsciption box info on cart.
		 *
		 * @param array $data as data.
		 * @param array $cart_item as cart item.
		 * @return array
		 */
	public function wps_subscription_box_meta_on_cart( $data = array(), $cart_item = array() ) {
		
		// Retrieve subscription box data from cart item meta.
		$subscription_box_data = isset( $cart_item['is_subscription_main'] ) ? $cart_item['is_subscription_main'] : '';

		// Ensure it applies only to the main subscription box product.
		if ( empty( $subscription_box_data ) ) {
			return $data;
		}

		$price = null;
		$subscription_number = isset( $cart_item['wps_sfw_subscription_number'] ) ? $cart_item['wps_sfw_subscription_number'] : '';
		$subscription_expiry_number = isset( $cart_item['wps_sfw_subscription_expiry_number'] ) ? $cart_item['wps_sfw_subscription_expiry_number'] : '';
		$subscription_interval = isset( $cart_item['wps_sfw_subscription_interval'] ) ? $cart_item['wps_sfw_subscription_interval'] : '';

		$product_id = isset( $cart_item['product_id'] ) ? $cart_item['product_id'] : '';

		// Ensure only the main subscription box product is targeted.
		if ( empty( $product_id ) ) {
			return $data;
		}

		// Get product type.
		$product = wc_get_product( $product_id );
		if ( ! $product || $product->get_type() !== 'subscription_box' ) {
			return $data; // Exit if product is not a subscription box.
		}

		// Generate subscription interval price string.
		$subscription_price_html = '<span class="wps_subscription_box_interval">' . sprintf( esc_html( ' / %s ' ), wps_sfw_get_time_interval_for_price( $subscription_number, $subscription_interval ) ) . '</span>';
		$price = apply_filters( 'wps_sfw_show_sync_interval', $subscription_price_html, $cart_item );

		if ( ! is_checkout() ) {
			$cart_price = $cart_item['data']->get_price();
			$wps_price = wps_sfw_get_time_interval_for_price( $subscription_number, $subscription_interval );

			/* translators: %s: subscription interval */
			$wps_sfw_price_html =  sprintf( esc_html( ' / %s ' ), $wps_price );
			$price = apply_filters( 'wps_sfw_show_sync_interval', $wps_sfw_price_html, '' );
		}

		// Expiry interval logic.
		if ( ! empty( $subscription_expiry_number ) ) {
			$subscription_expiry_interval = isset( $cart_item['wps_sfw_subscription_expiry_interval'] ) ? $cart_item['wps_sfw_subscription_expiry_interval'] : '';
			$expiry_price_html = wps_sfw_get_time_interval( $subscription_expiry_number, $subscription_expiry_interval );
			$expiry_price_html = sprintf(
				/* translators: %s: subscription expiry interval */
				esc_html__( ' For %s ', 'subscriptions-for-woocommerce' ),
				$expiry_price_html
			);

			$price .= $expiry_price_html;
		}

		// Apply final price format.
		$price = apply_filters( 'wps_subscription_box_show_one_time_subscription_price_block', $price, $cart_item );

		// Do not allow subscription price for one-time product.
		if ( $price ) {
			$data[] = apply_filters(
				'wps_subscription_box_block_cart_price',
				array(
					'name'   => 'wps-sfw-price-html',
					'hidden' => true,
					'value'  => html_entity_decode( $price ),
				),
				$cart_item
			);
		}

		return $data;
	}

		/**
		 * Function to add order line item for subscription box.
		 *
		 * @param object $item as item.
		 * @param array  $cart_item_key as cart item key.
		 * @param array  $values as value.
		 * @param object $order as order.
		 * @return void
		 */
	public function wps_sfw_add_order_line_item_for_subscription_box( $item, $cart_item_key, $values, $order ) {
		if ( ! empty( $values['wps_sfw_attached_products'] ) ) {
			$item->add_meta_data( 'wps_sfw_attached_products', $values['wps_sfw_attached_products'], true );
		}
	}

		/**
		 * Hanle ajax functionality for subscription box.
		 *
		 * @return void
		 */
	public function wps_get_cart_item() {
		check_ajax_referer( 'wps_sfw_public_nonce', 'nonce' );
		$cart_key = ! empty( $_POST['cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) : '';
		if ( ! isset( $cart_key ) ) {
			wp_send_json_error( array( 'message' => 'Missing cart key' ) );
		}

		$cart = WC()->cart->get_cart();

		if ( ! isset( $cart[ $cart_key ] ) ) {
			wp_send_json_error( array( 'message' => 'Cart item not found' ) );
		}

		$cart_item = $cart[ $cart_key ];
		$attached_products = isset( $cart_item['wps_sfw_attached_products'] ) ? $cart_item['wps_sfw_attached_products'] : array();

		wp_send_json_success(
			array(
				'cart_item' => $cart_item,
				'attached_products' => $attached_products,
			)
		);
	}

		/**
		 * Function to manage subscription box on block cart.
		 *
		 * @param object $cart_item_data as data.
		 * @param object $cart_item as cart item.
		 * @return object
		 */
	public function wps_sfw_add_item_data_cart_block_subscription_box( $cart_item_data, $cart_item ) {
		if ( ! WC()->cart || empty( WC()->cart->get_cart() ) ) {
			return $cart_item_data;
		}

		$cart = WC()->cart->get_cart();

		// Ensure cart item has a valid key.
		if ( ! isset( $cart_item['key'] ) ) {
			return $cart_item_data;
		}

		$array_keys = array_keys( $cart );

		$index = array_search( $cart_item['key'], $array_keys, true );

		if ( false !== $index ) {

			$cart_item_data[] = array(
				'name'   => 'wps_sfw_subscription_box_cart_index',
				'hidden' => true,
				'value'  => $index,
			);
		}

		if ( isset( $cart_item['wps_sfw_attached_products'] ) && ! empty( $cart_item['wps_sfw_attached_products'] ) ) {
			$cart_item_data[] = array(
				'name'   => 'wps_sfw_subscription_box_cart_key',
				'hidden' => true,
				'value'  => $cart_item['key'],
			);
		}

		return $cart_item_data;
	}

		/**
		 * Function to show attached product html.
		 *
		 * @param array  $name as name.
		 * @param object $cart_item as cart tiem.
		 * @param object $cart_item_key as cart item key.
		 * @return array
		 */
	public function wps_sfw_show_attached_product_html_subscription_box( $name, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['wps_sfw_attached_products'] ) && ! empty( $cart_item['wps_sfw_attached_products'] ) && ( is_cart() || is_checkout() ) ) {
			$attached_products_html = '<div class="wps-attached-products-popup"><strong>Attached Products:</strong><ul>';
			foreach ( $cart_item['wps_sfw_attached_products'] as $attached_product ) {
				$attached_products_html .= '<li>
					<img src="' . esc_url( $attached_product['image'] ) . '" width="40" height="40" />
					' . esc_html( $attached_product['name'] ) . ' x ' . esc_html( $attached_product['quantity'] ) . '
				</li>';
			}
			$attached_products_html .= '</ul><span class="wps_sfw_customer_close_popup">&times;</span></div>';

			// Append the popup trigger.
			$name .= '<a href="#" class="wps_show_customer_subscription_box_popup">' . esc_attr__( 'View Attached Products', 'subscriptions-for-woocommerce' ) . '</a>' . $attached_products_html;
		}
		return $name;
	}


		/**
		 * Function to add to cart validation for subscription box.
		 *
		 * @param bool  $valid as valid.
		 * @param int   $product_id as product id.
		 * @param int   $quantity as quantity.
		 * @param int   $variation_id as variation id.
		 * @param array $variations as variation.
		 * @return bool
		 */
	public function wps_sfw_subscription_box_woocommerce_add_to_cart_validation( $valid, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return $valid;
		}

		// Define the subscription box product type (Modify this as per your setup).
		$subscription_box_type = 'subscription_box';
		$is_subscription_box = $product->get_type() === $subscription_box_type;

		$cart = WC()->cart->get_cart();
		$cart_has_subscription_box = false;
		$cart_has_other_products = false;

		// Check existing cart items.
		foreach ( $cart as $cart_item ) {
			$cart_product = wc_get_product( $cart_item['product_id'] );

			if ( ! $cart_product ) {
				continue;
			}

			if ( $cart_product->get_type() === $subscription_box_type ) {
				$cart_has_subscription_box = true;
			} else {
				$cart_has_other_products = true;
			}
		}

		// Prevent adding different product types together.
		if ( $cart_has_subscription_box && ! $is_subscription_box ) {

			wc_add_notice(
				__( 'You cannot add other products while a Subscription Box is in the cart. Please remove it first.', 'subscriptions-for-woocommerce' ) .
					' <button type="button" class="button wps_sfw_product_page-empty-cart" id="wps_sfw_product_page-empty-cart">Empty Cart</button>',
				'error'
			);
			return false;
		}

		if ( $cart_has_other_products && $is_subscription_box ) {
			wc_add_notice(
				__( 'You cannot add a Subscription Box when other products are in the cart. Please remove them first.', 'subscriptions-for-woocommerce' ) .
					' <button type="button" class="button wps_sfw_product_page-empty-cart" id="wps_sfw_product_page-empty-cart">Empty Cart</button>',
				'error'
			);
			return false;
		}

		return $valid;
	}

		/**
		 * AJAX callback to empty the cart.
		 */
	public function wps_sfw_sub_box_empty_cart_callback() {
		check_ajax_referer( 'wps_sfw_public_nonce', 'nonce' );
		WC()->cart->empty_cart();
		wp_send_json_success( array( 'message' => 'Your cart has been emptied!' ) );
	}

		/**
		 * Function to hide quantity field for subscription box.
		 *
		 * @param bool   $return as check.
		 * @param object $product as product.
		 * @return bool
		 */
	public function wps_sfw_hide_quantity_fields_for_subscription_box( $return, $product ) {

		$is_subscription_box = $product->get_type();

		if ( 'subscription_box' == $is_subscription_box ) {
			$return = true;
		}
		return $return;
	}

		/**
		 * Function to show subscription box information on prodcut page.
		 *
		 * @return void
		 */
	public function wps_sfw_subscription_box_info_above_add_to_cart() {
		global $product;

		if ( ! $product ) {
			return;
		}
		$product_type = $product->get_type();
		$product_id = $product->get_id();
		if ( 'subscription_box' == $product_type ) {
			$wps_sfw_subscription_box_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_number', true );
			if ( empty( $wps_sfw_subscription_box_number ) ) {
				$wps_sfw_subscription_box_number = 1;
			}
			$wps_sfw_subscription_box_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_interval', true );
			if ( empty( $wps_sfw_subscription_box_interval ) ) {
				$wps_sfw_subscription_box_interval = 'day';
			}
			$wps_sfw_subscription_box_expiry_number = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_expiry_number', true );
			$wps_sfw_subscription_box_expiry_interval = wps_sfw_get_meta_data( $product_id, 'wps_sfw_subscription_expiry_interval', true );

			?>
			<div class="wps_sfw_subscription_box_info">
			<?php
			if ( $wps_sfw_subscription_box_number ) {
				?>
					<div><strong><?php echo esc_attr_e( 'Subscription Interval: ', 'subscriptions-for-woocommerce' ); ?></strong><?php echo esc_attr( $wps_sfw_subscription_box_number ); ?><?php echo esc_attr( $wps_sfw_subscription_box_interval ); ?></div>
				<?php
			}

			if ( $wps_sfw_subscription_box_expiry_number ) {
				?>
					<div><strong><?php echo esc_attr_e( 'Subscription Expiry: ', 'subscriptions-for-woocommerce' ); ?></strong><?php echo esc_attr( $wps_sfw_subscription_box_expiry_number ); ?><?php echo esc_attr( $wps_sfw_subscription_box_expiry_interval ); ?></div>
				<?php
			} else {
				?>
					<div><strong><?php echo esc_attr_e( 'Subscription Expiry: ', 'subscriptions-for-woocommerce' ); ?></strong><?php echo esc_attr_e( 'Unlimited', 'subscriptions-for-woocommerce' ); ?></div>
				<?php
			}
			?>
			</div>
			<?php
		}
	}

		/**
		 * Wps_sfw_woocommerce_email_preview_dummy_order_callback function
		 *
		 * @param object $order as order.
		 * @param string $email_type as email type.
		 * @return object
		 */
	public function wps_sfw_woocommerce_email_preview_dummy_order_callback( $order, $email_type ) {
		if ( 'Subscriptions_For_Woocommerce_Cancel_Subscription_Email' == $email_type || 'Subscriptions_For_Woocommerce_Expired_Subscription_Email' == $email_type || 'Subscriptions_For_Woocommerce_Onhold_Active_Subscription_Email' == $email_type || 'Woocommerce_Subscription_Pro_Pause_Subscription_Email' == $email_type || 'Woocommerce_Subscription_Pro_Reactivate_Subscription_Email' == $email_type || 'Woocommerce_Subscription_Pro_Plan_Going_To_Expire_Email' == $email_type || 'Woocommerce_Subscription_Pro_Reminder_Email' == $email_type || 'Woocommerce_Subscription_Pro_Expired_Subscription_Email' == $email_type || 'Woocommerce_Subscription_Pro_New_Subscription_Email' == $email_type ) {

			$order = wc_get_order( 12345 );
		}
		return $order;
	}

		/**
		 * Function to add custom body class for subscription page.
		 *
		 * @param array $classes as classes.
		 *
		 * @return array
		 */
	public function wps_sfw_subscription_custom_add_body_class( $classes ) {

		$current_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

		if ( strpos( $current_uri, '/my-account/show-subscription/' ) !== false ) {
			$classes[] = 'wps_sfw_show-subscription-page';
		}

		return $classes;
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
		 * Function to register subscription dashboard shortcodes.
		 *
		 * @return void
		 */
	public function wps_sfw_subscription_dashboard_shortcodes() {
		add_shortcode( 'wps-subscription-dashboard', array( $this, 'wps_sfw_subscription_dashboard_shortcode_layout' ) );
	}

		/**
		 * Function to render subscription dashboard content.
		 *
		 * @return string
		 */
	public function wps_sfw_subscription_dashboard_shortcode_layout() {
		ob_start();

		if ( isset( $_GET['wps-show-subscription'] ) ) {
			$wps_subscription_id = isset( $_GET['wps-show-subscription'] ) ? intval( $_GET['wps-show-subscription'] ) : 0;
			wc_get_template( 'myaccount/wps-show-subscription-details.php', array( 'wps_subscription_id' => $wps_subscription_id ), '', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/partials/templates/' );
		} else {
			$this->wps_sfw_subscription_dashboard_content();
		}
		return ob_get_clean();
	}

	/**
	 * Function to handle affiliate commision on subscription renewal.
	 *
	 * @param int    $order_id as order id.
	 * @param string $old_status as old status.
	 * @param string $new_status as new status.
	 * @return void
	 */
	public function wps_sfw_woocommerce_affiliate_commision_renewal( $order_id, $old_status, $new_status ) {
		if ( ! class_exists( 'WPAM_Commission_Tracking' ) ) {
			return;
		}
		if ( $old_status != $new_status ) {
			if ( 'completed' == $new_status || 'processing' == $new_status ) {
					$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
				if ( 'yes' === $wps_sfw_renewal_order ) {
					$parent_order_id = wps_sfw_get_meta_data( $order_id, 'wps_sfw_parent_order_id', true );
					if ( $parent_order_id ) {
						$parent_order = wc_get_order( $parent_order_id );

						$order = wc_get_order( $order_id );
						$total = $order->get_total();
						$shipping = $order->get_total_shipping();
						$tax = $order->get_total_tax();
						$buyer_email = $order->get_billing_email();
						$total_fee = 0;
						$order_fee_items = $order->get_fees();
						if ( ! is_array( $order_fee_items ) ) {
							return $total_fee;
						}

						foreach ( $order_fee_items as $fee_item ) {
							$total_fee += $fee_item['line_total'];
						}
						$purchaseAmount = $total - $shipping - $tax - $total_fee;

						$wpam_refkey = $parent_order->get_meta( '_wpam_refkey' );
						$wpam_id = $parent_order->get_meta( '_wpam_id' );
						if ( ! empty( $wpam_id ) ) {
							$wpam_refkey = $wpam_id;
						}
						$args = array();
						$args['txn_id'] = $order_id;
						$args['amount'] = $purchaseAmount;
						$args['aff_id'] = $wpam_refkey;
						$args['email'] = $buyer_email;
						WPAM_Commission_Tracking::award_commission( $args );

					}
				}
			}
		}
	}
}
