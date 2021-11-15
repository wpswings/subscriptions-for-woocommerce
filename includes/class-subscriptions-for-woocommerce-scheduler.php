<?php
/**
 * The admin-specific cron functionality of the plugin.
 *
 * @link       https://makewebbetter.com
 * @since      1.0.0
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 */

/**
 * The cron-specific functionality of the plugin admin side.
 *
 * @package     Subscriptions_For_Woocommerce
 * @subpackage  Subscriptions_For_Woocommerce/package
 * @author      makewebbetter <webmaster@makewebbetter.com>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Subscriptions_For_Woocommerce_Scheduler' ) ) {

	/**
	 * Define class and module for cron.
	 */
	class Subscriptions_For_Woocommerce_Scheduler {
		/**
		 * Constructor
		 */
		public function __construct() {
			if ( mwb_sfw_check_plugin_enable() ) {
				add_action( 'init', array( $this, 'mwb_sfw_admin_create_order_scheduler' ) );
				add_action( 'mwb_sfw_create_renewal_order_schedule', array( $this, 'mwb_sfw_renewal_order_on_scheduler' ) );
				add_action( 'mwb_sfw_expired_renewal_subscription', array( $this, 'mwb_sfw_expired_renewal_subscription_callback' ) );

				if ( mwb_sfw_is_enable_usage_tracking() ) {
					add_action( 'makewebbetter_tracker_send_event', array( $this, 'mwb_sfw_makewebbetter_tracker_send_event' ) );
				}
			}
		}


		/**
		 * This function is used to create renewal order on scheduler.
		 *
		 * @name mwb_sfw_renewal_order_on_scheduler
		 * @since 1.0.0
		 */
		public function mwb_sfw_renewal_order_on_scheduler() {

			$mwb_sfw_pro_plugin_activated = false;
			if ( in_array( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$mwb_sfw_pro_plugin_activated = true;
			}
			$current_time = current_time( 'timestamp' );

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'mwb_subscriptions',
				'post_status'   => 'wc-mwb_renewal',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => 'mwb_subscription_status',
						'value' => 'active',
					),
					array(
						'relation' => 'AND',
						array(
							'key'   => 'mwb_parent_order',
							'compare' => 'EXISTS',
						),
						array(
							'key'   => 'mwb_next_payment_date',
							'value' => $current_time,
							'compare' => '<',
						),
					),
				),
			);
			$mwb_subscriptions = get_posts( $args );
			Subscriptions_For_Woocommerce_Log::log( 'MWB Renewal Subscriptions: ' . wc_print_r( $mwb_subscriptions, true ) );
			if ( isset( $mwb_subscriptions ) && ! empty( $mwb_subscriptions ) && is_array( $mwb_subscriptions ) ) {
				foreach ( $mwb_subscriptions as $key => $value ) {
					$subscription_id = $value->ID;

					if ( mwb_sfw_check_valid_subscription( $subscription_id ) ) {

						$subscription = get_post( $subscription_id );
						$parent_order_id  = $subscription->mwb_parent_order;
						if ( function_exists( 'mwb_sfw_check_valid_order' ) && ! mwb_sfw_check_valid_order( $parent_order_id ) ) {
							continue;
						}
						if ( apply_filters( 'mwb_sfw_stop_creating_renewal_multisafepay', false, $subscription_id ) ) {
							continue;
						}
						if ( ! $mwb_sfw_pro_plugin_activated ) {
							$subp_id = get_post_meta( $value->ID, 'product_id', true );
							$check_variable = get_post_meta( $subp_id, 'mwb_sfw_variable_product', true );
							if ( 'yes' === $check_variable ) {
								continue;
							}
						}
						$parent_order = wc_get_order( $parent_order_id );
						$billing_details = $parent_order->get_address( 'billing' );
						$shipping_details = $parent_order->get_address( 'shipping' );
						$parent_order_currency = $parent_order->get_currency();
						$new_status = 'mwb_renewal';
						$user_id = $subscription->mwb_customer_id;
						$product_id = $subscription->product_id;
						$product_qty = $subscription->product_qty;
						$payment_method = $subscription->_payment_method;
						$payment_method_title = $subscription->_payment_method_title;

						$mwb_old_payment_method = get_post_meta( $parent_order_id, '_payment_method', true );
						$args = array(
							'status'      => $new_status,
							'customer_id' => $user_id,
						);
						$mwb_new_order = wc_create_order( $args );
						$mwb_new_order->set_currency( $parent_order_currency );

						$line_subtotal = $subscription->line_subtotal;
						$line_total = $subscription->line_total;

						$_product = wc_get_product( $product_id );

						$mwb_args = array(
							'variation' => array(),
							'totals'    => array(
								'subtotal'     => $line_subtotal,
								'subtotal_tax' => $subscription->line_subtotal_tax,
								'total'        => $line_total,
								'tax'          => $subscription->line_tax,
								'tax_data'     => maybe_unserialize( $subscription->line_tax_data ),
							),
						);
						$mwb_pro_args = apply_filters( 'mwb_product_args_for_order', $mwb_args );

						$item_id = $mwb_new_order->add_product(
							$_product,
							$product_qty,
							$mwb_pro_args
						);

						$mwb_new_order->update_taxes();
						$mwb_new_order->calculate_totals();
						$mwb_new_order->save();

						$order_id = $mwb_new_order->get_id();
						Subscriptions_For_Woocommerce_Log::log( 'MWB Renewal Order ID: ' . wc_print_r( $order_id, true ) );
						update_post_meta( $order_id, '_payment_method', $payment_method );
						update_post_meta( $order_id, '_payment_method_title', $payment_method_title );

						$mwb_new_order->set_address( $billing_details, 'billing' );
						$mwb_new_order->set_address( $shipping_details, 'shipping' );
						update_post_meta( $order_id, 'mwb_sfw_renewal_order', 'yes' );
						update_post_meta( $order_id, 'mwb_sfw_subscription', $subscription_id );
						update_post_meta( $order_id, 'mwb_sfw_parent_order_id', $parent_order_id );
						update_post_meta( $subscription_id, 'mwb_renewal_subscription_order', $order_id );

						// Renewal info.
						$mwb_no_of_order = get_post_meta( $subscription_id, 'mwb_wsp_no_of_renewal_order', true );
						if ( empty( $mwb_no_of_order ) ) {
							$mwb_no_of_order = 1;
							update_post_meta( $subscription_id, 'mwb_wsp_no_of_renewal_order', $mwb_no_of_order );
						} else {
							$mwb_no_of_order = (int) $mwb_no_of_order + 1;
							update_post_meta( $subscription_id, 'mwb_wsp_no_of_renewal_order', $mwb_no_of_order );
						}
						$mwb_renewal_order_data = get_post_meta( $subscription_id, 'mwb_wsp_renewal_order_data', true );
						if ( empty( $mwb_renewal_order_data ) ) {
							$mwb_renewal_order_data = array( $order_id );
							update_post_meta( $subscription_id, 'mwb_wsp_renewal_order_data', $mwb_renewal_order_data );
						} else {
							$mwb_renewal_order_data[] = $order_id;
							update_post_meta( $subscription_id, 'mwb_wsp_renewal_order_data', $mwb_renewal_order_data );
						}
						update_post_meta( $subscription_id, 'mwb_wsp_last_renewal_order_id', $order_id );

						do_action( 'mwb_sfw_renewal_order_creation', $mwb_new_order, $subscription_id );

						/*if trial period enable*/
						if ( '' == $mwb_old_payment_method ) {
							$parent_order_id = $subscription_id;
						}
						/*update next payment date*/
						$mwb_next_payment_date = mwb_sfw_next_payment_date( $subscription_id, $current_time, 0 );

						update_post_meta( $subscription_id, 'mwb_next_payment_date', $mwb_next_payment_date );
						if ( 'stripe' == $payment_method ) {
							if ( class_exists( 'Subscriptions_For_Woocommerce_Stripe' ) ) {
								$mwb_stripe = new Subscriptions_For_Woocommerce_Stripe();
								$result = $mwb_stripe->mwb_sfw_process_renewal_payment( $order_id, $parent_order_id );
								do_action( 'mwb_sfw_cancel_failed_susbcription', $result, $order_id, $subscription_id );
								mwb_sfw_send_email_for_renewal_susbcription( $order_id );
							}
						}

						do_action( 'mwb_sfw_other_payment_gateway_renewal', $mwb_new_order, $subscription_id, $payment_method );

					}
				}
			}
		}

		/**
		 * This function is used to  scheduler.
		 *
		 * @name mwb_sfw_admin_create_order_scheduler
		 * @since 1.0.0
		 */
		public function mwb_sfw_admin_create_order_scheduler() {
			if ( class_exists( 'ActionScheduler' ) ) {
				if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'mwb_sfw_create_renewal_order_schedule' ) ) {
					as_schedule_recurring_action( strtotime( 'hourly' ), 3600, 'mwb_sfw_create_renewal_order_schedule' );
				}
				if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'mwb_sfw_expired_renewal_subscription' ) ) {
					as_schedule_recurring_action( strtotime( 'hourly' ), 3600, 'mwb_sfw_expired_renewal_subscription' );
				}

				do_action( 'mwb_sfw_create_admin_scheduler' );
			}
		}

		/**
		 * This function is used to  expired susbcription.
		 *
		 * @name mwb_sfw_expired_renewal_subscription_callback
		 * @since 1.0.0
		 */
		public function mwb_sfw_expired_renewal_subscription_callback() {
			$current_time = current_time( 'timestamp' );

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'mwb_subscriptions',
				'post_status'   => 'wc-mwb_renewal',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => 'mwb_subscription_status',
						'value' => array( 'active', 'pending' ),
					),
					array(
						'relation' => 'AND',
						array(
							'key'   => 'mwb_parent_order',
							'compare' => 'EXISTS',
						),
						array(
							'relation' => 'AND',
							array(
								'key'   => 'mwb_susbcription_end',
								'value' => $current_time,
								'compare' => '<',
							),
							array(
								'key'   => 'mwb_susbcription_end',
								'value' => 0,
								'compare' => '!=',
							),
						),
					),
				),
			);
			$mwb_subscriptions = get_posts( $args );
			Subscriptions_For_Woocommerce_Log::log( 'MWB Expired Subscriptions: ' . wc_print_r( $mwb_subscriptions, true ) );
			if ( isset( $mwb_subscriptions ) && ! empty( $mwb_subscriptions ) && is_array( $mwb_subscriptions ) ) {
				foreach ( $mwb_subscriptions as $key => $value ) {
					$susbcription_id = $value->ID;

					if ( mwb_sfw_check_valid_subscription( $susbcription_id ) ) {
						// Send expired email notification.
						mwb_sfw_send_email_for_expired_susbcription( $susbcription_id );
						update_post_meta( $susbcription_id, 'mwb_subscription_status', 'expired' );
						update_post_meta( $susbcription_id, 'mwb_next_payment_date', '' );
						do_action( 'mwb_sfw_expire_subscription_scheduler', $susbcription_id );
					}
				}
			}
		}

		/**
		 * Function is used for the sending the track data
		 *
		 * @name mwb_sfw_makewebbetter_tracker_send_event
		 * @since 1.0.0
		 */
		public function mwb_sfw_makewebbetter_tracker_send_event() {

			require WC()->plugin_path() . '/includes/class-wc-tracker.php';

			$last_send = get_option( 'makewebbetter_tracker_last_send' );
			if ( ! apply_filters( 'makewebbetter_tracker_send_override', false ) ) {

				// Send a maximum of once per week by default.
				$last_send = $this->mwb_sfw_last_send_time();
				if ( $last_send && $last_send > apply_filters( 'makewebbetter_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {

					return;
				}
			} else {

				// Make sure there is at least a 1 hour delay between override sends, we don't want duplicate calls due to double clicking links.
				$last_send = $this->mwb_sfw_last_send_time();
				if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {

					return;
				}
			}
			// Update time first before sending to ensure it is set.
			update_option( 'makewebbetter_tracker_last_send', time() );
			$params = WC_Tracker::get_tracking_data();
			$params['extensions']['subscriptions_for_woocommerce'] = array(
				'version' => SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION,
				'site_url' => home_url(),
				'subscriptions_details' => $this->mwb_get_subscriptions_counts(),
				'subscription_products' => $this->mwb_get_subscriptions_products(),
			);

			$params = apply_filters( 'makewebbetter_tracker_params', $params );

			$api_url = 'https://tracking.makewebbetter.com/wp-json/mps-route/v1/mps-testing-data/';
			$sucess = wp_safe_remote_post(
				$api_url,
				array(
					'method'      => 'POST',
					'body'        => wp_json_encode( $params ),
				)
			);

		}

		/**
		 * Function is used get subscriptions details
		 *
		 * @name mwb_get_subscriptions_counts
		 * @since 1.0.0
		 */
		public function mwb_get_subscriptions_counts() {
			$subscriptions_details = array();
			$args = array(
				'numberposts' => -1,
				'post_type'   => 'mwb_subscriptions',
				'post_status' => 'wc-mwb_renewal',
				'meta_query' => array(
					array(
						'key'   => 'mwb_customer_id',
						'compare' => 'EXISTS',
					),
				),
			);
			$mwb_subscriptions = get_posts( $args );
			$total_subscriptions = count( $mwb_subscriptions );
			$subscriptions_details['no_of_subscriptions'] = $total_subscriptions;

			$active_count = 0;
			$pending_count = 0;
			$cancel_count = 0;
			$expired_count = 0;
			$paused_count = 0;

			$subscriptions_details['active_subscriptions'] = $active_count;
			$subscriptions_details['pending_subscriptions'] = $pending_count;
			$subscriptions_details['cancelled_subscriptions'] = $cancel_count;
			$subscriptions_details['expired_subscriptions'] = $expired_count;
			$subscriptions_details['paused_subscriptions'] = $paused_count;

			if ( isset( $mwb_subscriptions ) && ! empty( $mwb_subscriptions ) && is_array( $mwb_subscriptions ) ) {
				foreach ( $mwb_subscriptions as $key => $subscription ) {
					$status = get_post_meta( $subscription->ID, 'mwb_subscription_status', true );
					if ( 'active' == $status ) {
						$active_count++;
						$subscriptions_details['active_subscriptions'] = $active_count;
					} elseif ( 'pending' == $status ) {
						$pending_count++;
						$subscriptions_details['pending_subscriptions'] = $pending_count;
					} elseif ( 'cancelled' == $status ) {
						$cancel_count++;
						$subscriptions_details['cancelled_subscriptions'] = $cancel_count;
					} elseif ( 'expired' == $status ) {
						$expired_count++;
						$subscriptions_details['expired_subscriptions'] = $expired_count;
					} elseif ( 'paused' == $status ) {
						$paused_count++;
						$subscriptions_details['paused_subscriptions'] = $paused_count;
					}
				}
			}
			return $subscriptions_details;
		}

		/**
		 * Function is used get subscriptions products
		 *
		 * @name mwb_get_subscriptions_products
		 * @since 1.0.0
		 */
		public function mwb_get_subscriptions_products() {

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'product',
				'post_status' => 'publish',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'   => '_mwb_sfw_product',
						'value' => 'yes',
					),
					array(
						'key'   => 'mwb_sfw_variable_product',
						'value' => 'yes',
					),
				),
			);
			$mwb_subscriptions = get_posts( $args );

			$total_subscriptions = count( $mwb_subscriptions );
			return $total_subscriptions;
		}

		/**
		 * Get the updated time.
		 *
		 * @name mwb_sfw_last_send_time
		 *
		 * @since 1.0.0
		 */
		public function mwb_sfw_last_send_time() {
			return apply_filters( 'makewebbetter_tracker_last_send_time', get_option( 'makewebbetter_tracker_last_send', false ) );
		}
	}
}
return new Subscriptions_For_Woocommerce_Scheduler();
