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

		if ( ! $this->mwb_sfw_check_product_is_subscription( $product ) ) {
			return $price;
		}
		$price = $this->mwb_sfw_subscription_product_get_price_html( $price, $product );
		return $price;
	}

	/**
	 * This function is used to check product is subscription or not.
	 *
	 * @name mwb_sfw_check_product_is_subscription
	 * @param object $product Product.
	 * @since    1.0.0
	 */
	public function mwb_sfw_check_product_is_subscription( $product ) {

		$mwb_is_subscription = false;
		if ( is_object( $product ) ) {
			$product_id = $product->get_id();
			$mwb_subscription_product = get_post_meta( $product_id, '_mwb_sfw_product', true );
			if ( 'yes' === $mwb_subscription_product ) {
				$mwb_is_subscription = true;
			}
		}

		return $mwb_is_subscription;
	}

	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name mwb_sfw_subscription_product_get_price_html
	 * @param object $product Product.
	 * @param string $price Product price.
	 * @since    1.0.0
	 */
	public function mwb_sfw_subscription_product_get_price_html( $price, $product ) {

		if ( is_object( $product ) ) {
			$product_id = $product->get_id();
			$mwb_sfw_subscription_number = get_post_meta( $product_id, 'mwb_sfw_subscription_number', true );
			$mwb_sfw_subscription_expiry_number = get_post_meta( $product_id, 'mwb_sfw_subscription_expiry_number', true );
			$mwb_sfw_subscription_interval = get_post_meta( $product_id, 'mwb_sfw_subscription_interval', true );

			if ( isset( $mwb_sfw_subscription_expiry_number ) && ! empty( $mwb_sfw_subscription_expiry_number ) ) {
				$mwb_sfw_subscription_expiry_interval = get_post_meta( $product_id, 'mwb_sfw_subscription_expiry_interval', true );

				$mwb_price_html = $this->mwb_sfw_get_time_interval( $mwb_sfw_subscription_expiry_number, $mwb_sfw_subscription_expiry_interval );

				$price .= sprintf( esc_html__( ' For %s ', 'subscriptions-for-woocommerce' ), $mwb_price_html );

				$price = $this->mwb_sfw_get_free_trial_period_html( $product_id, $price );
				$price = $this->mwb_sfw_get_initial_signup_fee_html( $product_id, $price );
			} elseif ( isset( $mwb_sfw_subscription_number ) && ! empty( $mwb_sfw_subscription_number ) ) {
				$mwb_price_html = $this->mwb_sfw_get_time_interval( $mwb_sfw_subscription_number, $mwb_sfw_subscription_interval );
				$price .= sprintf( esc_html__( ' / %s ', 'subscriptions-for-woocommerce' ), $mwb_price_html );
				$price = $this->mwb_sfw_get_free_trial_period_html( $product_id, $price );
				$price = $this->mwb_sfw_get_initial_signup_fee_html( $product_id, $price );

			}
		}
		return $price;
	}

	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name mwb_sfw_get_time_interval
	 * @param int    $mwb_sfw_subscription_number Subscription inteval number.
	 * @param string $mwb_sfw_subscription_interval Subscription Interval .
	 * @since    1.0.0
	 */
	public function mwb_sfw_get_time_interval( $mwb_sfw_subscription_number, $mwb_sfw_subscription_interval ) {
		if ( $mwb_sfw_subscription_number == 1 ) {
			$mwb_sfw_subscription_number = ' ';
		}
		switch ( $mwb_sfw_subscription_interval ) {
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

	/**
	 * This function is used to show initial signup fee on subscription product page.
	 *
	 * @name mwb_sfw_get_initial_signup_fee_html
	 * @param int    $product_id Product ID.
	 * @param string $price Product Price.
	 * @since    1.0.0
	 */
	public function mwb_sfw_get_initial_signup_fee_html( $product_id, $price ) {
		$mwb_sfw_subscription_initial_signup_price = get_post_meta( $product_id, 'mwb_sfw_subscription_initial_signup_price', true );
		if ( isset( $mwb_sfw_subscription_initial_signup_price ) && ! empty( $mwb_sfw_subscription_initial_signup_price ) ) {
			$price .= sprintf( esc_html__( ' and %s  Sign up fee', 'subscriptions-for-woocommerce' ), wc_price( $mwb_sfw_subscription_initial_signup_price ) );
		}
		return $price;
	}

	/**
	 * This function is used to show free trial period on subscription product page.
	 *
	 * @name mwb_sfw_get_free_trial_period_html
	 * @param int    $product_id Product ID.
	 * @param string $price Product Price.
	 * @since    1.0.0
	 */
	public function mwb_sfw_get_free_trial_period_html( $product_id, $price ) {

		$mwb_sfw_subscription_free_trial_number = get_post_meta( $product_id, 'mwb_sfw_subscription_free_trial_number', true );
		$mwb_sfw_subscription_free_trial_interval = get_post_meta( $product_id, 'mwb_sfw_subscription_free_trial_interval', true );
		if ( isset( $mwb_sfw_subscription_free_trial_number ) && ! empty( $mwb_sfw_subscription_free_trial_number ) ) {
			$mwb_price_html = $this->mwb_sfw_get_time_interval( $mwb_sfw_subscription_free_trial_number, $mwb_sfw_subscription_free_trial_interval );
			$price .= sprintf( esc_html__( ' and %s  free trial', 'subscriptions-for-woocommerce' ), $mwb_price_html );
		}
		return $price;
	}

	/**
	 * This function is used to change Add to cart button text.
	 *
	 * @name mwb_sfw_product_add_to_cart_text
	 * @param object $product Product.
	 * @param string $text Add to cart text.
	 * @since    1.0.0
	 */
	public function mwb_sfw_product_add_to_cart_text( $text, $product ) {

		if ( $this->mwb_sfw_check_product_is_subscription( $product ) ) {
			$mwb_add_to_cart_text = $this->mwb_sfw_get_add_to_cart_button_text();

			if ( isset( $mwb_add_to_cart_text ) && ! empty( $mwb_add_to_cart_text ) ) {
				$text = $mwb_add_to_cart_text;
			}
		}

		return $text;
	}

	/**
	 * This function is used to get add to cart button text.
	 *
	 * @name mwb_sfw_get_add_to_cart_button_text
	 * @since    1.0.0
	 */
	public function mwb_sfw_get_add_to_cart_button_text() {

		$mwb_add_to_cart_text = get_option( 'mwb_sfw_add_to_cart_text', '' );
		return $mwb_add_to_cart_text;
	}

	/**
	 * This function is used to change place order button text.
	 *
	 * @name mwb_sfw_woocommerce_order_button_text
	 * @param string $text Place order text.
	 * @since    1.0.0
	 */
	public function mwb_sfw_woocommerce_order_button_text( $text ) {
		$mwb_sfw_place_order_button_text = $this->mwb_sfw_get_add_to_cart_button_text();
		if ( isset( $mwb_sfw_place_order_button_text ) && ! empty( $mwb_sfw_place_order_button_text ) && $this->mwb_sfw_check_cart_has_subscription_product() ) {
			$text = $mwb_sfw_place_order_button_text;
		}

		return $text;
	}

	/**
	 * This function is used to get order button text.
	 *
	 * @name mwb_sfw_get_place_order_button_text
	 * @since    1.0.0
	 */
	public function mwb_sfw_get_place_order_button_text() {

		$mwb_sfw_place_order_button_text = get_option( 'mwb_sfw_place_order_button_text', '' );
		return $mwb_sfw_place_order_button_text;
	}

	/**
	 * This function is used to check cart have subscription product.
	 *
	 * @name mwb_sfw_check_cart_has_subscription_product
	 * @since    1.0.0
	 */
	public function mwb_sfw_check_cart_has_subscription_product() {
		$mwb_has_subscription = false;

		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				if ( $this->mwb_sfw_check_product_is_subscription( $cart_item['data'] ) ) {
					$mwb_has_subscription = true;
					break;
				}
			}
		}
		return $mwb_has_subscription;
	}

	/**
	 * This function is used to subscription price on in cart.
	 *
	 * @name mwb_sfw_show_subscription_price_on_cart
	 * @param string $product_price Product price.
	 * @param object $cart_item cart item.
	 * @param int    $cart_item_key cart_item_key.
	 * @since    1.0.0
	 */
	public function mwb_sfw_show_subscription_price_on_cart( $product_price, $cart_item, $cart_item_key ) {

		if ( $this->mwb_sfw_check_product_is_subscription( $cart_item['data'] ) ) {

			if ( $cart_item['data']->is_on_sale() ) {
				$price = $cart_item['data']->get_sale_price();
			} else {
				$price = $cart_item['data']->get_regular_price();
			}

			$product_price = wc_price( wc_get_price_to_display( $cart_item['data'], array( 'price' => $price ) ) );
			$product_price = $this->mwb_sfw_subscription_product_get_price_html( $product_price, $cart_item['data'] );
		}
		return $product_price;
	}

	/**
	 * This function is used to subscription subtotal price on view order.
	 *
	 * @name mwb_sfw_show_subscription_subtotal_on_view_order
	 * @param string $subtotal subtotal price.
	 * @param array  $item item.
	 * @param object $order order.
	 * @since    1.0.0
	 */
	public function mwb_sfw_show_subscription_subtotal_on_view_order( $subtotal, $item, $order ) {

		if ( isset( $item['product_id'] ) && ! empty( $item['product_id'] ) ) {
			$product = wc_get_product( $item['product_id'] );
			if ( $this->mwb_sfw_check_product_is_subscription( $product ) ) {
				$subtotal = $this->mwb_sfw_subscription_product_get_price_html( $subtotal, $product );
				return $subtotal;
			}
		}
		return $subtotal;
	}

	public function mwb_sfw_add_subscription_price_and_sigup_fee( $cart ) {

		if ( isset( $cart ) && ! empty( $cart ) ) {

			foreach ( $cart->cart_contents as $key => $cart_data ) {
				if ( $this->mwb_sfw_check_product_is_subscription( $cart_data['data'] ) ) {
					$product_id = $cart_data['data']->get_id();
					$mwb_sfw_free_trial_number = $this->mwb_sfw_get_subscription_trial_period_number( $product_id );

					$mwb_sfw_signup_fee = $this->mwb_sfw_get_subscription_initial_signup_price( $product_id );
					$mwb_sfw_signup_fee = is_numeric( $mwb_sfw_signup_fee ) ? (float) $mwb_sfw_signup_fee : 0;

					if ( isset( $mwb_sfw_free_trial_number ) && ! empty( $mwb_sfw_free_trial_number ) ) {
						if ( $mwb_sfw_signup_fee != 0 ) {
							$cart_data['data']->set_price( $mwb_sfw_signup_fee );
						} else {
							$cart_data['data']->set_price( 0 );
						}
					} else {
						$product_price = $cart_data['data']->get_price();
						$product_price += $mwb_sfw_signup_fee;
						$cart_data['data']->set_price( $product_price );
					}
				}
			}
		}

	}

	public function mwb_sfw_get_subscription_trial_period_number( $product_id ) {
		$mwb_sfw_subscription_free_trial_number = get_post_meta( $product_id, 'mwb_sfw_subscription_free_trial_number', true );
		return $mwb_sfw_subscription_free_trial_number;
	}

	public function mwb_sfw_get_subscription_initial_signup_price( $product_id ) {
		$mwb_sfw_subscription_initial_signup_price = get_post_meta( $product_id, 'mwb_sfw_subscription_initial_signup_price', true );
		return $mwb_sfw_subscription_initial_signup_price;
	}

	public function mwb_sfw_process_checkout( $order_id, $posted_data ) {

		$order = wc_get_order( $order_id );

		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {

				if ( $this->mwb_sfw_check_product_is_subscription( $cart_item['data'] ) ) {

					if ( $cart_item['data']->is_on_sale() ) {
						$price = $cart_item['data']->get_sale_price();
					} else {
						$price = $cart_item['data']->get_regular_price();
					}
					$mwb_recurring_total = $price * $cart_item['quantity'];

					$product_id = $cart_item['data']->get_id();

					$mwb_recurring_data = $this->mwb_sfw_get_subscription_recurring_data( $product_id );
					$mwb_recurring_data['mwb_recurring_total'] = $mwb_recurring_total;
					$mwb_recurring_data['product_id'] = $product_id;
					$mwb_recurring_data['product_name'] = $cart_item['data']->get_name();
					$mwb_recurring_data['product_qty'] = $cart_item['quantity'];

					$mwb_recurring_data['line_tax_data'] = $cart_item['line_tax_data'];
					$mwb_recurring_data['line_subtotal'] = $cart_item['line_subtotal'];
					$mwb_recurring_data['line_subtotal_tax'] = $cart_item['line_subtotal_tax'];
					$mwb_recurring_data['line_total'] = $cart_item['line_total'];
					$mwb_recurring_data['line_tax'] = $cart_item['line_tax'];

					$mwb_recurring_data = apply_filters( 'mwb_sfw_cart_data_for_susbcription', $mwb_recurring_data, $cart_item );
					$subscription = $this->mwb_sfw_create_subscription( $order, $posted_data, $mwb_recurring_data );
					if ( is_wp_error( $subscription ) ) {
						throw new Exception( $subscription->get_error_message() );
					} else {
						$mwb_has_susbcription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );
						if ( $mwb_has_susbcription != 'yes' ) {
							if ( isset( $_POST['payment_method'] ) && 'stripe' == $_POST['payment_method'] ) {
								if ( isset( $_POST['wc-stripe-payment-token'] ) && 'new' == $_POST['wc-stripe-payment-token'] ) {
										$available_gateways  = WC()->payment_gateways->get_available_payment_gateways();
										$stripe_class = $available_gateways['stripe'];

										$payment_result = $stripe_class->process_payment( $order_id, false, true );
										update_post_meta( $order_id, 'mwb_sfw_order_has_subscription', 'yes' );
										return $payment_result;
								}
							}
						}
					}
				}
			}
		}

	}

	/**
	 * This function is used to get ruccuring data.
	 *
	 * @name mwb_sfw_get_subscription_recurring_data
	 * @param int $product_id product_id.
	 * @since    1.0.0
	 */
	public function mwb_sfw_get_subscription_recurring_data( $product_id ) {

		$mwb_recurring_data = array();

		$mwb_sfw_subscription_number = get_post_meta( $product_id, 'mwb_sfw_subscription_number', true );
		$mwb_sfw_subscription_interval = get_post_meta( $product_id, 'mwb_sfw_subscription_interval', true );
		$mwb_recurring_data['mwb_sfw_subscription_number'] = $mwb_sfw_subscription_number;
		$mwb_recurring_data['mwb_sfw_subscription_interval'] = $mwb_sfw_subscription_interval;
		$mwb_sfw_subscription_expiry_number = get_post_meta( $product_id, 'mwb_sfw_subscription_expiry_number', true );
		if ( isset( $mwb_sfw_subscription_expiry_number ) && ! empty( $mwb_sfw_subscription_expiry_number ) ) {
			$mwb_recurring_data['mwb_sfw_subscription_expiry_number'] = $mwb_sfw_subscription_expiry_number;
		}

		$mwb_sfw_subscription_expiry_interval = get_post_meta( $product_id, 'mwb_sfw_subscription_expiry_interval', true );

		if ( isset( $mwb_sfw_subscription_expiry_interval ) && ! empty( $mwb_sfw_subscription_expiry_interval ) ) {
			$mwb_recurring_data['mwb_sfw_subscription_expiry_interval'] = $mwb_sfw_subscription_expiry_interval;
		}
		$mwb_sfw_subscription_initial_signup_price = get_post_meta( $product_id, 'mwb_sfw_subscription_initial_signup_price', true );

		if ( isset( $mwb_sfw_subscription_expiry_interval ) && ! empty( $mwb_sfw_subscription_expiry_interval ) ) {
			$mwb_recurring_data['mwb_sfw_subscription_initial_signup_price'] = $mwb_sfw_subscription_initial_signup_price;
		}

		$mwb_sfw_subscription_free_trial_number = get_post_meta( $product_id, 'mwb_sfw_subscription_free_trial_number', true );

		if ( isset( $mwb_sfw_subscription_free_trial_number ) && ! empty( $mwb_sfw_subscription_free_trial_number ) ) {
			$mwb_recurring_data['mwb_sfw_subscription_free_trial_number'] = $mwb_sfw_subscription_free_trial_number;
		}
		$mwb_sfw_subscription_free_trial_interval = get_post_meta( $product_id, 'mwb_sfw_subscription_free_trial_interval', true );
		if ( isset( $mwb_sfw_subscription_free_trial_interval ) && ! empty( $mwb_sfw_subscription_free_trial_interval ) ) {
			$mwb_recurring_data['mwb_sfw_subscription_free_trial_interval'] = $mwb_sfw_subscription_free_trial_interval;
		}
		$mwb_recurring_data = apply_filters( 'mwb_sfw_recurring_data', $mwb_recurring_data, $product_id );
		return $mwb_recurring_data;
	}


	/**
	 * This function is used to create susbcription post.
	 *
	 * @name mwb_sfw_create_subscription
	 * @param object $order order.
	 * @since    1.0.0
	 */
	public function mwb_sfw_create_subscription( $order, $posted_data, $mwb_recurring_data ) {
		if ( ! empty( $order ) ) {
			$order_id = $order->get_id();
			$current_date  = current_time( 'timestamp' );

			$mwb_default_args = array(
				'mwb_parent_order'   => $order_id,
				'mwb_customer_id'    => $order->get_user_id(),
				'mwb_schedule_start' => $current_date,
			);

			$mwb_args              = wp_parse_args( $mwb_recurring_data, $mwb_default_args );
			if ( isset( $posted_data['payment_method'] ) && $posted_data['payment_method'] ) {
				$mwb_enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();

				if ( isset( $mwb_enabled_gateways[ $posted_data['payment_method'] ] ) ) {
					$mwb_payment_method = $mwb_enabled_gateways[ $posted_data['payment_method'] ];
					$mwb_payment_method->validate_fields();
					$mwb_args['payment_method']       = $mwb_payment_method->id;
					$mwb_args['payment_method_title'] = $mwb_payment_method->get_title();
				}
			}
			$mwb_args['mwb_order_currency'] = $order->get_currency();
			$mwb_args['mwb_subscription_status'] = 'pending';

			$mwb_args = apply_filters( 'mwb_sfw_new_subscriptions_data', $mwb_args );

			$post_title_date = strftime( _x( '%1$b %2$d, %Y @ %I:%M %p', 'subscription post title. "Subscriptions order - <this>"', 'subscriptions-for-woocommerce' ) );

			$mwb_subscription_data = array();
			$mwb_subscription_data['post_type']     = 'mwb_subscriptions';
			$mwb_subscription_data['post_status']   = 'publish';
			$mwb_subscription_data['post_author']   = 1;
			$mwb_subscription_data['post_parent']   = $order_id;
			$mwb_subscription_data['post_title']    = sprintf( _x( 'MWB Subscription &ndash; %s', 'Subscription post title', 'subscriptions-for-woocommerce' ), $post_title_date );
			$mwb_subscription_data['post_date_gmt'] = $order->get_date_created()->date( 'Y-m-d H:i:s' );
			$mwb_subscription_data['post_date_gmt'] = $order->get_date_created()->date( 'Y-m-d H:i:s' );
			$mwb_subscription_data['post_password'] = uniqid( 'order_' );

			$subscription_id = wp_insert_post( $mwb_subscription_data, true );
			if ( is_wp_error( $subscription_id ) ) {
				return $subscription_id;
			}
			update_post_meta( $subscription_id, 'mwb_susbcription_trial_end', '' );
			update_post_meta( $subscription_id, 'mwb_susbcription_end', '' );
			update_post_meta( $subscription_id, 'mwb_next_payment_date', '' );

			/*update meta value*/
			mwb_sfw_update_meta_key_for_susbcription( $subscription_id, $mwb_args );

			return $subscription_id;

		}

	}

	/**
	 * This function is used to hide offline payment gateway for subscription product.
	 *
	 * @name mwb_sfw_unset_offline_payment_gateway_for_subscription
	 * @param array $available_gateways available_gateways.
	 * @since    1.0.0
	 */
	public function mwb_sfw_unset_offline_payment_gateway_for_subscription( $available_gateways ) {
		if ( is_admin() || ! is_checkout() ) {
			return $available_gateways;
		}

		$mwb_has_subscription = false;

		foreach ( WC()->cart->get_cart_contents() as $key => $values ) {

			if ( $this->mwb_sfw_check_product_is_subscription( $values['data'] ) ) {
				$mwb_has_subscription = true;
				break;
			}
		}
		if ( $mwb_has_subscription ) {
			if ( isset( $available_gateways ) && ! empty( $available_gateways ) && is_array( $available_gateways ) ) {
				foreach ( $available_gateways as $key => $gateways ) {

					if ( 'stripe' != $key && 'paypal' != $key ) {
						unset( $available_gateways[ $key ] );
					}
				}
			}
		}
		return $available_gateways;
	}

	public function mwb_sfw_add_subscription_tab_on_myaccount_page() {
		add_rewrite_endpoint( 'mwb_subscriptions', EP_PAGES );
		add_rewrite_endpoint( 'show-subscription', EP_PAGES );
		add_rewrite_endpoint( 'mwb-add-payment-method', EP_PAGES );
	}

	/**
	 * Register the endpoints on my_account page.
	 *
	 * @name mwb_sfw_custom_endpoint_query_vars
	 * @since    1.0.0
	 */
	public function mwb_sfw_custom_endpoint_query_vars( $vars ) {
		$vars[] = 'mwb_subscriptions';
		$vars[] = 'show-subscription';
		$vars[] = 'mwb-add-payment-method';
		return $vars;
	}

	/**
	 * This function is used to add MWb susbcriptions Tab in MY ACCOUNT Page
	 *
	 * @name mwb_sfw_add_subscription_dashboard_on_myaccount_page
	 * @since 1.0.0
	 * @param $items items.
	 */
	public function mwb_sfw_add_subscription_dashboard_on_myaccount_page( $items ) {

		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );
		$items['mwb_subscriptions'] = __( 'MWB Subscriptions', 'subscriptions-for-woocommerce' );
		$items['customer-logout'] = $logout;

		return $items;
	}

	/**
	 * This function is used to add my account page template
	 *
	 * @name mwb_sfw_subscription_dashboard_content
	 * @since 1.0.0
	 */
	public function mwb_sfw_subscription_dashboard_content() {

		wc_get_template( 'myaccount/mwb_susbcrptions.php', array( 'current_page' => 1 ), '', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/partials/templates/' );

	}

	/**
	 * This function is used to restrict guest user for subscription product.
	 *
	 * @name mwb_sfw_subscription_before_checkout_form
	 * @since 1.0.0
	 * @param $checkout checkout.
	 */
	public function mwb_sfw_subscription_before_checkout_form( $checkout = '' ) {

		if ( ! is_user_logged_in() ) {
			if ( $this->mwb_sfw_check_cart_has_subscription_product() ) {
				if ( true === $checkout->enable_guest_checkout ) {
					$checkout->enable_guest_checkout = false;
				}
			}
		}
	}

	/**
	 * This function is used to show recurring price on account page.
	 *
	 * @name mwb_sfw_display_susbcription_recerring_total_account_page_callback
	 * @since 1.0.0
	 * @param $subscription_id subscription_id.
	 */
	public function mwb_sfw_display_susbcription_recerring_total_account_page_callback( $subscription_id ) {
		$mwb_recurring_total = get_post_meta( $subscription_id, 'mwb_recurring_total', true );
		$mwb_recurring_number = get_post_meta( $subscription_id, 'mwb_sfw_subscription_number', true );
		$mwb_recurring_interval = get_post_meta( $subscription_id, 'mwb_sfw_subscription_interval', true );
		$mwb_price_html = $this->mwb_sfw_get_time_interval( $mwb_recurring_number, $mwb_recurring_interval );
		$price = wc_price( $mwb_recurring_total );
		$price .= sprintf( esc_html__( ' / %s ', 'subscriptions-for-woocommerce' ), $mwb_price_html );
		echo wp_kses_post( $price );
	}


	/**
	 * This function is used to include subscription details template on account page.
	 *
	 * @name mwb_sfw_shwo_subscription_details
	 * @since 1.0.0
	 * @param $mwb_subscription_id mwb_subscription_id.
	 */
	public function mwb_sfw_shwo_subscription_details( $mwb_subscription_id ) {

		if ( ! mwb_sfw_check_valid_subscription( $mwb_subscription_id ) ) {
			echo '<div class="woocommerce-error mwb_sfw_invalid_subscription">' . esc_html__( 'Not a valid subscription', 'subscriptions-for-woocommerce' ) . '</div>';
			return;
		}

		wc_get_template( 'myaccount/mwb_show_subscription_details.php', array( 'mwb_subscription_id' => $mwb_subscription_id ), '', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/partials/templates/' );

	}


	/**
	 * This function is used to cancel susbcription.
	 *
	 * @name mwb_sfw_cancel_susbcription
	 * @since 1.0.0
	 */
	public function mwb_sfw_cancel_susbcription() {

		if ( isset( $_GET['mwb_subscription_status'] ) && isset( $_GET['mwb_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$user_id      = get_current_user_id();

			$mwb_status   = wc_clean( $_GET['mwb_subscription_status'] );
			$mwb_subscription_id = $_GET['mwb_subscription_id'];
			if ( mwb_sfw_check_valid_subscription( $mwb_subscription_id ) ) {
				$this->mwb_sfw_cancel_susbcription_order_by_customer( $mwb_subscription_id, $mwb_status, $user_id );
			}
		}
	}

	/**
	 * This function is used to cancel susbcription.
	 *
	 * @name mwb_sfw_cancel_susbcription_order_by_customer
	 * @param $mwb_subscription_id mwb_subscription_id.
	 * @param $mwb_status mwb_status.
	 * @param $user_id user_id.
	 * @since 1.0.0
	 */
	public function mwb_sfw_cancel_susbcription_order_by_customer( $mwb_subscription_id, $mwb_status, $user_id ) {

		$mwb_customer_id = get_post_meta( $mwb_subscription_id, 'mwb_customer_id', true );
		if ( $mwb_status == 'active' && $mwb_customer_id == $user_id ) {

			update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
			$redirect_url = wc_get_endpoint_url( 'show-subscription', $mwb_subscription_id, wc_get_page_permalink( 'myaccount' ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * This function is used to update susbcription.
	 *
	 * @name mwb_sfw_woocommerce_order_status_changed
	 * @param $order_id order_id.
	 * @param $old_status old_status.
	 * @param $new_status new_status.
	 * @since 1.0.0
	 */
	public function mwb_sfw_woocommerce_order_status_changed( $order_id, $old_status, $new_status ) {

		$is_activated = get_post_meta( $order_id, 'mwb_sfw_subscription_activated', true );
		if ( $is_activated == 'yes' ) {
			return;
		}
		if ( $old_status != $new_status ) {
			if ( 'completed' == $new_status || 'processing' == $new_status ) {
				$mwb_has_susbcription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );
				if ( $mwb_has_susbcription = 'yes' ) {
					$args = array(
						'numberposts' => -1,
						'post_type'   => 'mwb_subscriptions',
						'post_status'   => 'publish',
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'key'   => 'mwb_parent_order',
								'value' => $order_id,
							),
							array(
								'key'   => 'mwb_subscription_status',
								'value' => 'pending',
							),

						),
					);
					$mwb_subscriptions = get_posts( $args );
					if ( isset( $mwb_subscriptions ) && ! empty( $mwb_subscriptions ) && is_array( $mwb_subscriptions ) ) {
						foreach ( $mwb_subscriptions as $key => $value ) {
							$current_time = current_time( 'timestamp' );

							update_post_meta( $value->ID, 'mwb_subscription_status', 'active' );
							update_post_meta( $value->ID, 'mwb_schedule_start', $current_time );
							$mwb_susbcription_end = mwb_sfw_susbcription_expiry_date( $value->ID, $current_time );
							update_post_meta( $value->ID, 'mwb_susbcription_end', $mwb_susbcription_end );
							$mwb_susbcription_trial_end = mwb_sfw_susbcription_trial_date( $value->ID, $current_time );
							update_post_meta( $value->ID, 'mwb_susbcription_trial_end', $mwb_susbcription_trial_end );

							$mwb_next_payment_date = mwb_sfw_next_payment_date( $value->ID, $current_time, $mwb_susbcription_trial_end );

							update_post_meta( $value->ID, 'mwb_next_payment_date', $mwb_next_payment_date );
						}
						update_post_meta( $order_id, 'mwb_sfw_subscription_activated', 'yes' );
					}
				}
			}
		}

	}

	/**
	 * This function is used to add payment method.
	 *
	 * @name mwb_sfw_mwb_add_payment_method
	 * @since 1.0.0
	 */
	public function mwb_sfw_mwb_add_payment_method( $mwb_subscription_id ) {
		if ( ! mwb_sfw_check_valid_subscription( $mwb_subscription_id ) ) {
			echo '<div class="woocommerce-error mwb_sfw_invalid_subscription">' . esc_html__( 'Not a valid subscription', 'subscriptions-for-woocommerce' ) . '</div>';
			return;
		}

		wc_get_template( 'myaccount/mwb_add_new_payment_details.php', array( 'mwb_subscription_id' => $mwb_subscription_id ), '', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'public/partials/templates/' );
	}

}
