<?php
/**
 * The admin-specific payment integration functionality of the plugin.
 *
 * @link       https://wpswings.com
 * @since      1.6.2
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 */

/**
 * The Payment-specific functionality of the plugin admin side.
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 * @author      WP Swings <webmaster@wpswings.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! class_exists( 'Wps_Subscriptions_Payment_Stripe_Main' ) ) {
	/**
	 * Define class to handle the recurring and cancellation.
	 */
	class Wps_Subscriptions_Payment_Stripe_Main {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wps_sfw_other_payment_gateway_renewal', array( $this, 'wps_sfw_process_stripe_renewal_payment_callback' ), 10, 3 );

			add_action( 'wps_sfw_subscription_cancel', array( $this, 'wps_sfw_cancel_stripe_subscription' ), 10, 2 );

			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'wps_sfw_add_stripe_order_statuses_for_payment_complete' ), 10, 2 );

			add_filter( 'wc_stripe_display_save_payment_method_checkbox', array( $this, 'wps_sfw_display_save_payment_method_checkbox' ) );

			add_filter( 'wc_stripe_generate_create_intent_request', array( $this, 'wps_sfw_add_setup_future_usage_for_parent_order' ), 50, 4 );

			add_filter( 'wc_stripe_is_adaptive_pricing_supported', array( $this, 'wps_sfw_disable_adaptive_pricing_for_subscriptions' ), 10, 2 ); // Fix for checkout optimised suite with Adaptive Pricing.

			// Fix for checkout optimised suite: make the Payment Element save the card for subscription carts.
			add_filter( 'wc_stripe_upe_params', array( $this, 'wps_sfw_flag_subscription_cart_for_stripe_js' ) ); // Classic checkout.
			add_filter( 'wc_stripe_params', array( $this, 'wps_sfw_flag_subscription_cart_for_stripe_js' ) ); // Block checkout.

			add_action( 'woocommerce_payment_complete', array( $this, 'wps_sfw_attach_stripe_pm_to_customer_for_renewal' ), 20 ); // Fix for checkout optimised suite.

			add_action( 'woocommerce_payment_complete', array( $this, 'wps_sfw_update_parent_stripe_pm_after_renewal_payment' ), 25 );

			// A card added, or made default, in My Account > Payment methods becomes the card for the customer's subscriptions.
			add_action( 'woocommerce_stripe_add_payment_method', array( $this, 'wps_sfw_update_subscriptions_stripe_pm_on_card_added' ), 20, 2 );
			add_action( 'woocommerce_payment_token_set_default', array( $this, 'wps_sfw_update_subscriptions_stripe_pm_on_default_set' ), 20, 2 );

			// Path to Stripe's main plugin file.
			$stripe_main_file = WP_PLUGIN_DIR . '/woocommerce-gateway-stripe/woocommerce-gateway-stripe.php';

			$version = null;
			if ( file_exists( $stripe_main_file ) ) {
				$plugin_data = get_file_data(
					$stripe_main_file,
					array( 'Version' => 'Version' ),
					'plugin'
				);
				$version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : null;
			}
			if ( $version ) {
				if ( version_compare( $version, '9.6.0', '>=' ) ) {
					// New filter (≥ 9.6.0) — no deprecation.
					add_filter( 'wc_stripe_force_save_payment_method', array( $this, 'wps_sfw_wc_stripe_force_save_source_new' ), 10, 2 );
				} else {
					// Old filter (< 9.6.0).
					add_filter( 'wc_stripe_force_save_source', array( $this, 'wps_sfw_wc_stripe_force_save_source_old' ), 10, 2 );
				}
			}
		}

		/**
		 * Process subscription payment.
		 *
		 * @name wps_sfw_process_stripe_renewal_payment.
		 * @param object $renewal_order renewal order.
		 * @param int    $subscription_id subscription_id.
		 * @param string $payment_method payment_method.
		 */
		public function wps_sfw_process_stripe_renewal_payment_callback( $renewal_order, $subscription_id, $payment_method ) {
			if ( class_exists( 'Wps_Subscriptions_Payment_Stripe' ) ) {
				$obj = new Wps_Subscriptions_Payment_Stripe();
				$obj->wps_sfw_process_stripe_renewal_payment( $renewal_order, $subscription_id, $payment_method );
			}
		}

		/**
		 * This function is used to cancel subscriptions status.
		 *
		 * @name wps_sfw_cancel_stripe_subscription
		 * @param int    $wps_subscription_id wps_subscription_id.
		 * @param string $status status.
		 */
		public function wps_sfw_cancel_stripe_subscription( $wps_subscription_id, $status ) {

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$subscription = new WPS_Subscription( $wps_subscription_id );
				$wps_payment_method = $subscription->get_payment_method();
			} else {
				$wps_payment_method = get_post_meta( $wps_subscription_id, '_payment_method', true );
			}
			
			if ( 'stripe' == $wps_payment_method && 'Cancel' == $status ) {
				wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id );
				wps_sfw_update_meta_data( $wps_subscription_id, 'wps_subscription_status', 'cancelled' );
			}
		}

		/**
		 * This function is add subscription order status.
		 *
		 * @name wps_sfw_add_stripe_order_statuses_for_payment_complete
		 * @param array  $order_status order_status.
		 * @param object $order order.
		 */
		public function wps_sfw_add_stripe_order_statuses_for_payment_complete( $order_status, $order ) {
			if ( $order && is_object( $order ) ) {
				$order_id = $order->get_id();

				$payment_method = $order->get_payment_method();

				$wps_sfw_renewal_order = wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
				if ( 'stripe' == $payment_method && 'yes' == $wps_sfw_renewal_order ) {
					$order_status[] = 'wps_renewal';
				}
			}
			return apply_filters( 'wps_sfw_add_subscription_order_statuses_for_payment_complete', $order_status, $order );
		}

		/**
		 * Display save payment method checkbox.
		 *
		 * @param bool $display_save_payment_method_checkbox display_save_payment_method_checkbox.
		 */
		public function wps_sfw_display_save_payment_method_checkbox( $display_save_payment_method_checkbox ) {
			if ( wps_sfw_is_cart_has_subscription_product() ) {
				return false;
			}
			return $display_save_payment_method_checkbox;
		}

		/**
		 * Keep subscription payments out of Stripe Adaptive Pricing.
		 *
		 * With Optimized Checkout Suite and Adaptive Pricing enabled (turned on by default for most
		 * merchants since Stripe 10.8), the payment runs through a Stripe Checkout Session confirmed in
		 * the browser. That flow never applies `wc_stripe_generate_create_intent_request`, and it only
		 * saves the card when the "Save payment information" checkbox is ticked, which is hidden for
		 * subscription carts. The card is charged once without being attached to the Stripe customer,
		 * so every off-session renewal fails. Stripe already opts WooCommerce Subscriptions products out
		 * of Adaptive Pricing; this does the same for our subscription products, so the payment goes
		 * through the PaymentIntent flow that sets `setup_future_usage: off_session`.
		 *
		 * @param bool         $is_supported Whether Adaptive Pricing is supported for the current cart.
		 * @param WC_Cart|null $cart         The current cart, or null if unavailable.
		 *
		 * @return bool
		 */
		public function wps_sfw_disable_adaptive_pricing_for_subscriptions( $is_supported, $cart = null ) {
			if ( ! $is_supported ) {
				return $is_supported;
			}

			if ( $this->wps_sfw_is_subscription_payment_context( $cart ) ) {
				return false;
			}

			return $is_supported;
		}

		/**
		 * Tell the Stripe checkout script that the cart holds a subscription.
		 *
		 * With Optimized Checkout Suite the card is collected by the Payment Element and sent to the
		 * server as a ConfirmationToken. The token only carries `setup_future_usage: off_session` when
		 * the Payment Element was set up with it, and Stripe's script only does that when
		 * `cartContainsSubscription` is true. Stripe sets that flag for WooCommerce Subscriptions
		 * carts only, so for our subscription carts the card was charged once and never saved for
		 * off-session renewals.
		 *
		 * @param array $params Stripe checkout script parameters.
		 *
		 * @return array
		 */
		public function wps_sfw_flag_subscription_cart_for_stripe_js( $params ) {
			if ( ! is_array( $params ) || ! empty( $params['cartContainsSubscription'] ) ) {
				return $params;
			}

			if ( $this->wps_sfw_is_subscription_payment_context() ) {
				$params['cartContainsSubscription'] = true;
			}

			return $params;
		}

		/**
		 * Whether the current checkout or order-pay page is paying for one of our subscriptions.
		 *
		 * @param WC_Cart|null $cart The cart to check. Defaults to the current cart.
		 *
		 * @return bool
		 */
		private function wps_sfw_is_subscription_payment_context( $cart = null ) {
			if ( null === $cart && function_exists( 'WC' ) ) {
				$cart = WC()->cart;
			}

			if ( $cart instanceof WC_Cart && ! $cart->is_empty() && function_exists( 'wps_sfw_is_cart_has_subscription_product' ) && wps_sfw_is_cart_has_subscription_product() ) {
				return true;
			}

			// Paying a subscription or renewal order from the order-pay page.
			$order_id = absint( get_query_var( 'order-pay' ) );
			if ( $order_id && function_exists( 'wps_sfw_order_has_subscription' ) && wc_get_order( $order_id ) ) {
				$is_renewal = 'yes' === wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
				if ( $is_renewal || wps_sfw_order_has_subscription( $order_id ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * After a subscription's initial payment completes via Stripe (including Optimized Checkout /
		 * Adaptive Pricing), ensure the payment method is attached to the Stripe customer.
		 *
		 * The Checkout Sessions (Optimized Checkout) flow does not set `setup_future_usage: off_session`
		 * on the PaymentIntent, so the payment method is charged once but never attached to the Stripe
		 * customer. Off-session renewals then fail with "The provided PaymentMethod was previously used
		 * ... you must attach it to a Customer first." This hook fires after payment_complete(), at which
		 * point `_stripe_source_id` is already saved on the order, and attaches the payment method to
		 * the customer so that `prepare_order_source()` can charge it off-session on renewal.
		 *
		 * @param int $order_id The WooCommerce order ID.
		 */
		public function wps_sfw_attach_stripe_pm_to_customer_for_renewal( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return;
			}

			// Only act on Stripe orders.
			if ( 'stripe' !== $order->get_payment_method() ) {
				return;
			}

			// Skip renewal orders — only the parent/initial order needs this.
			if ( 'yes' === wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true ) ) {
				return;
			}

			// Only act when the order has a subscription.
			if ( ! function_exists( 'wps_sfw_order_has_subscription' ) || ! wps_sfw_order_has_subscription( $order_id ) ) {
				return;
			}

			if ( ! class_exists( 'WC_Stripe_API' ) || ! class_exists( 'WC_Stripe_Order_Helper' ) ) {
				return;
			}

			$order_helper      = WC_Stripe_Order_Helper::get_instance();
			$payment_method_id = $order_helper->get_stripe_source_id( $order );

			// Only PaymentMethod objects (pm_) support the /attach endpoint.
			if ( empty( $payment_method_id ) || 0 !== strpos( $payment_method_id, 'pm_' ) ) {
				return;
			}

			// Resolve the Stripe customer ID from the order meta, then fall back to user meta.
			$customer_id = $order_helper->get_stripe_customer_id( $order );
			if ( empty( $customer_id ) && $order->get_user_id() && class_exists( 'WC_Stripe_Customer' ) ) {
				$stripe_customer = new WC_Stripe_Customer( $order->get_user_id() );
				$customer_id     = $stripe_customer->get_id();
			}

			if ( empty( $customer_id ) ) {
				return;
			}

			// Persist the customer ID on the order so prepare_order_source() finds it directly on renewal.
			if ( ! $order_helper->get_stripe_customer_id( $order ) ) {
				$order_helper->update_stripe_customer_id( $order, $customer_id );
				$order->save();
			}

			$this->wps_sfw_ensure_stripe_pm_attached( $payment_method_id, $customer_id );
		}

		/**
		 * Point future renewals at the card used to pay a renewal order.
		 *
		 * Renewals always charge the payment method saved on the parent order. When that card stops
		 * working (expired, detached, or consumed without a customer attachment) and the customer pays
		 * the failed renewal manually with a new card, the parent order still holds the old card, so the
		 * next renewal fails again. This copies the new card and its Stripe customer onto the parent
		 * order once the renewal is paid, provided the card is attached to a customer.
		 *
		 * @param int $order_id The WooCommerce order ID.
		 */
		public function wps_sfw_update_parent_stripe_pm_after_renewal_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order || 'stripe' !== $order->get_payment_method() ) {
				return;
			}

			// Only renewal orders.
			if ( 'yes' !== wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true ) ) {
				return;
			}

			if ( ! class_exists( 'WC_Stripe_API' ) || ! class_exists( 'WC_Stripe_Order_Helper' ) ) {
				return;
			}

			$subscription_id = wps_sfw_get_meta_data( $order_id, 'wps_sfw_subscription', true );
			if ( empty( $subscription_id ) ) {
				return;
			}

			$parent_order = wc_get_order( wps_sfw_get_meta_data( $subscription_id, 'wps_parent_order', true ) );
			if ( ! $parent_order instanceof WC_Order || 'stripe' !== $parent_order->get_payment_method() ) {
				return;
			}

			$order_helper      = WC_Stripe_Order_Helper::get_instance();
			$payment_method_id = $order_helper->get_stripe_source_id( $order );

			// Only PaymentMethod objects (pm_) support the /attach endpoint.
			if ( empty( $payment_method_id ) || 0 !== strpos( $payment_method_id, 'pm_' ) ) {
				return;
			}

			// Automatic renewals charge the parent's card, so this only acts when a different card was used.
			if ( $payment_method_id === $order_helper->get_stripe_source_id( $parent_order ) ) {
				return;
			}

			$customer_id = $order_helper->get_stripe_customer_id( $order );
			if ( empty( $customer_id ) ) {
				$customer_id = $order_helper->get_stripe_customer_id( $parent_order );
			}
			if ( empty( $customer_id ) && $order->get_user_id() && class_exists( 'WC_Stripe_Customer' ) ) {
				$stripe_customer = new WC_Stripe_Customer( $order->get_user_id() );
				$customer_id     = $stripe_customer->get_id();
			}

			// A card that cannot be attached to a customer cannot be charged off-session, so keep the parent as is.
			$attached_customer_id = $this->wps_sfw_ensure_stripe_pm_attached( $payment_method_id, $customer_id );
			if ( empty( $attached_customer_id ) ) {
				return;
			}

			$order_helper->update_stripe_source_id( $parent_order, $payment_method_id );
			$order_helper->update_stripe_customer_id( $parent_order, $attached_customer_id );
			$parent_order->save();

			/* translators: 1: Stripe payment method ID, 2: renewal order number */
			$parent_order->add_order_note( sprintf( __( 'Future subscription renewals will be charged to Stripe payment method %1$s, used to pay renewal order #%2$s.', 'subscriptions-for-woocommerce' ), $payment_method_id, $order->get_order_number() ) );
		}

		/**
		 * Use a card added in My Account > Payment methods for the customer's Stripe subscriptions.
		 *
		 * The Stripe plugin fires this action for checkout payments too, so it only acts on the My Account pages.
		 * Changing the card of one subscription (`wps_subscription_id` in the request) is handled by the Pro plugin.
		 *
		 * @param int    $user_id               The user ID.
		 * @param object $payment_method_object The Stripe PaymentMethod object.
		 */
		public function wps_sfw_update_subscriptions_stripe_pm_on_card_added( $user_id, $payment_method_object ) {
			// phpcs:disable WordPress.Security.NonceVerification -- Read-only checks; the Stripe plugin has already verified the request.
			if ( isset( $_REQUEST['wps_subscription_id'] ) ) {
				return;
			}
			$is_my_account_request = isset( $_POST['woocommerce_add_payment_method'] ) || is_add_payment_method_page() || is_wc_endpoint_url( 'payment-methods' );
			// phpcs:enable WordPress.Security.NonceVerification
			if ( ! $is_my_account_request ) {
				return;
			}

			if ( ! is_object( $payment_method_object ) || empty( $payment_method_object->id ) || ( isset( $payment_method_object->type ) && 'card' !== $payment_method_object->type ) ) {
				return;
			}

			$customer_id = isset( $payment_method_object->customer ) ? (string) $payment_method_object->customer : '';
			$this->wps_sfw_update_user_stripe_subscriptions_pm( $user_id, $payment_method_object->id, $customer_id );
		}

		/**
		 * Use a Stripe card made default in My Account > Payment methods for the customer's Stripe subscriptions.
		 *
		 * @param int              $token_id The token ID.
		 * @param WC_Payment_Token $token    The token.
		 */
		public function wps_sfw_update_subscriptions_stripe_pm_on_default_set( $token_id, $token = null ) {
			global $wp;

			// Only when the customer clicks "Make default", not when WooCommerce picks a default itself.
			if ( empty( $wp->query_vars['set-default-payment-method'] ) ) {
				return;
			}

			if ( ! $token instanceof WC_Payment_Token ) {
				$token = WC_Payment_Tokens::get( $token_id );
			}
			if ( ! $token || 'stripe' !== $token->get_gateway_id() || 'CC' !== $token->get_type() ) {
				return;
			}

			$this->wps_sfw_update_user_stripe_subscriptions_pm( $token->get_user_id(), $token->get_token(), '' );
		}

		/**
		 * Point the parent order of each of a user's live Stripe subscriptions at a new payment method.
		 *
		 * Renewals charge the card saved on the parent order, so that is where the new card is stored.
		 *
		 * @param int    $user_id           The user ID.
		 * @param string $payment_method_id The Stripe payment method ID (pm_).
		 * @param string $customer_id       The Stripe customer the payment method is attached to, if known.
		 */
		private function wps_sfw_update_user_stripe_subscriptions_pm( $user_id, $payment_method_id, $customer_id ) {
			if ( ! $user_id || 0 !== strpos( (string) $payment_method_id, 'pm_' ) || ! class_exists( 'WC_Stripe_API' ) || ! class_exists( 'WC_Stripe_Order_Helper' ) ) {
				return;
			}

			// Cards added in My Account are already attached; this also resolves the customer when it is not known yet.
			if ( empty( $customer_id ) && class_exists( 'WC_Stripe_Customer' ) ) {
				$stripe_customer = new WC_Stripe_Customer( $user_id );
				$customer_id     = $stripe_customer->get_id();
			}
			$customer_id = $this->wps_sfw_ensure_stripe_pm_attached( $payment_method_id, $customer_id );
			if ( empty( $customer_id ) ) {
				return;
			}

			$meta_query = array(
				array(
					'key'   => 'wps_customer_id',
					'value' => $user_id,
				),
			);
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$subscription_ids = wc_get_orders(
					array(
						'return'     => 'ids',
						'type'       => 'wps_subscriptions',
						'limit'      => -1,
						'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					)
				);
			} else {
				$subscription_ids = get_posts(
					array(
						'fields'      => 'ids',
						'numberposts' => -1,
						'post_type'   => 'wps_subscriptions',
						'post_status' => 'any',
						'meta_query'  => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					)
				);
			}

			$order_helper = WC_Stripe_Order_Helper::get_instance();

			foreach ( (array) $subscription_ids as $subscription_id ) {
				$status = wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true );
				if ( in_array( $status, array( 'cancelled', 'expired' ), true ) ) {
					continue;
				}

				$parent_order = wc_get_order( wps_sfw_get_meta_data( $subscription_id, 'wps_parent_order', true ) );
				if ( ! $parent_order instanceof WC_Order || 'stripe' !== $parent_order->get_payment_method() ) {
					continue;
				}

				if ( $payment_method_id === $order_helper->get_stripe_source_id( $parent_order ) ) {
					continue;
				}

				$order_helper->update_stripe_source_id( $parent_order, $payment_method_id );
				$order_helper->update_stripe_customer_id( $parent_order, $customer_id );
				$parent_order->save();

				/* translators: 1: Stripe payment method ID, 2: subscription ID */
				$parent_order->add_order_note( sprintf( __( 'Future renewals of subscription #%2$s will be charged to Stripe payment method %1$s, saved by the customer in My Account.', 'subscriptions-for-woocommerce' ), $payment_method_id, $subscription_id ) );
			}
		}

		/**
		 * Make sure a Stripe payment method is attached to a customer so it can be charged off-session.
		 *
		 * @param string $payment_method_id The Stripe payment method ID (pm_).
		 * @param string $customer_id       The Stripe customer ID to attach to when the payment method is unattached.
		 *
		 * @return string The ID of the customer the payment method is attached to, or an empty string on failure.
		 */
		private function wps_sfw_ensure_stripe_pm_attached( $payment_method_id, $customer_id ) {
			try {
				// Retrieve the payment method to check whether it is already attached.
				$pm_object = WC_Stripe_API::retrieve( 'payment_methods/' . $payment_method_id );
				if ( is_wp_error( $pm_object ) || ! empty( $pm_object->error ) ) {
					return '';
				}

				$attached_to = isset( $pm_object->customer ) ? (string) $pm_object->customer : '';

				// Already attached — Stripe does not allow moving it to another customer.
				if ( ! empty( $attached_to ) ) {
					return $attached_to;
				}

				if ( empty( $customer_id ) ) {
					return '';
				}

				// Attach to the Stripe customer so off-session renewals can charge it.
				$response = WC_Stripe_API::attach_payment_method_to_customer( $customer_id, $payment_method_id );
				if ( is_wp_error( $response ) || ! empty( $response->error ) ) {
					$error_message = ( ! is_wp_error( $response ) && isset( $response->error->message ) ) ? $response->error->message : '';
					$this->wps_sfw_stripe_log( 'WPS Subscriptions: could not attach payment method ' . $payment_method_id . ' to customer ' . $customer_id . ': ' . $error_message );
					return '';
				}

				return $customer_id;

			} catch ( Exception $e ) {
				// Non-fatal: log and continue. The renewal will surface any actual charge error.
				$this->wps_sfw_stripe_log( 'WPS Subscriptions: could not attach payment method ' . $payment_method_id . ' to customer ' . $customer_id . ': ' . $e->getMessage() );
				return '';
			}
		}

		/**
		 * Add setup future usage for parent order if subscription exist in order and setup future usage is missing in request.
		 *
		 * This forces Stripe to attach the payment method to the customer during the initial subscription
		 * payment, or when the customer pays a renewal order manually, so it can be charged off-session on renewals.
		 *
		 * @param array    $request         The request array.
		 * @param WC_Order $order           The order object.
		 * @param array    $prepared_source The prepared source array.
		 * @param bool     $is_setup_intent Whether it's a setup intent or not.
		 *
		 * @return array The modified request array.
		 */
		public function wps_sfw_add_setup_future_usage_for_parent_order( $request, $order, $prepared_source, $is_setup_intent = false ) {
			if ( ! $order instanceof WC_Order ) {
				return $request;
			}

			// SetupIntents (e.g. $0 trial checkout) reject `setup_future_usage`; they are off_session by default.
			if ( $is_setup_intent ) {
				return $request;
			}

			$order_id = $order->get_id();

			$is_renewal      = 'yes' === wps_sfw_get_meta_data( $order_id, 'wps_sfw_renewal_order', true );
			$has_subscription = function_exists( 'wps_sfw_order_has_subscription' ) && wps_sfw_order_has_subscription( $order_id );

			$is_parent_order = $has_subscription && ! $is_renewal;

			// A renewal paid by the customer (not the off-session automatic charge) saves its card for future renewals.
			$is_manual_renewal = $is_renewal && empty( $request['off_session'] );

			if ( ! $is_parent_order && ! $is_manual_renewal ) {
				return $request;
			}

			// Optimized Checkout Suite confirms the intent with a ConfirmationToken. Stripe does not apply a
			// top-level `setup_future_usage` to those intents, so it must go under the payment method type,
			// the same way the Stripe plugin does it for WooCommerce Subscriptions orders.
			if ( ! empty( $request['confirmation_token'] ) ) {
				$payment_type = $this->wps_sfw_get_reusable_stripe_payment_type( $request, $order );
				if ( $payment_type && empty( $request['payment_method_options'][ $payment_type ]['setup_future_usage'] ) ) {
					$request['payment_method_options'][ $payment_type ]['setup_future_usage'] = 'off_session';
				}
				return $request;
			}

			if ( empty( $request['setup_future_usage'] ) ) {
				$request['setup_future_usage'] = 'off_session';
			}

			return $request;
		}

		/**
		 * Get the Stripe payment method type being used to pay an order, if it can be saved for renewals.
		 *
		 * @param array    $request The intent request.
		 * @param WC_Order $order   The order being paid.
		 *
		 * @return string The payment method type (e.g. `card`), or an empty string when unknown or not reusable.
		 */
		private function wps_sfw_get_reusable_stripe_payment_type( $request, $order ) {
			$payment_type = '';

			// The Stripe plugin stores the selected type on the order before it creates the intent.
			if ( class_exists( 'WC_Stripe_Order_Helper' ) && method_exists( 'WC_Stripe_Order_Helper', 'get_stripe_upe_payment_type' ) ) {
				$payment_type = (string) WC_Stripe_Order_Helper::get_instance()->get_stripe_upe_payment_type( $order );
			}

			if ( '' === $payment_type && ! empty( $request['payment_method_types'] ) && 1 === count( (array) $request['payment_method_types'] ) ) {
				$payment_type = (string) current( (array) $request['payment_method_types'] );
			}

			if ( '' === $payment_type || ! class_exists( 'WC_Stripe' ) ) {
				return '';
			}

			// Stripe rejects `setup_future_usage` for payment methods that cannot be reused.
			$gateway = WC_Stripe::get_instance()->get_main_stripe_gateway();
			if ( ! is_object( $gateway ) || ! isset( $gateway->payment_methods[ $payment_type ] ) || ! $gateway->payment_methods[ $payment_type ]->is_reusable() ) {
				return '';
			}

			return $payment_type;
		}

		/**
		 * Write to the Stripe gateway log.
		 *
		 * WC_Stripe_Logger::log() was removed from the Stripe plugin, so calling it now causes a fatal error.
		 *
		 * @param string $message The message to log.
		 */
		private function wps_sfw_stripe_log( $message ) {
			if ( ! class_exists( 'WC_Stripe_Logger' ) ) {
				return;
			}

			if ( method_exists( 'WC_Stripe_Logger', 'error' ) ) {
				WC_Stripe_Logger::error( $message );
			} elseif ( method_exists( 'WC_Stripe_Logger', 'log' ) ) {
				WC_Stripe_Logger::log( $message );
			}
		}

		/**
		 * Force stripe to Save payment information to my account for future purchases.
		 *
		 * @param bool  $force_save_source Should we force save payment source.
		 * @param array $customer as customer.
		 */
		public function wps_sfw_wc_stripe_force_save_source_old( $force_save_source, $customer = null ) {
			if ( wps_sfw_is_cart_has_subscription_product() ) {
				return true;
			}
			return $force_save_source;
		}

		/**
		 * Force stripe to Save payment information to my account for future purchases.
		 *
		 * @param bool  $force_save_source Should we force save payment source.
		 * @param int   $order_id Order ID.
		 */
		public function wps_sfw_wc_stripe_force_save_source_new( $force_save_source, $order_id ) {
			if ( wps_sfw_is_cart_has_subscription_product() || ( $order_id && wps_sfw_order_has_subscription( $order_id ) ) ) {
				return true;
			}
			return $force_save_source;
		}
	}
}
new Wps_Subscriptions_Payment_Stripe_Main();
