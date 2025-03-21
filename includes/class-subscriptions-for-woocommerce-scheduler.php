<?php
/**
 * The admin-specific cron functionality of the plugin.
 *
 * @link       https://wpswings.com
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
 * @author      WP Swings <webmaster@wpswings.com>
 */
use Automattic\WooCommerce\Utilities\OrderUtil;

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

			if ( wps_sfw_check_plugin_enable() ) {
				add_action( 'init', array( $this, 'wps_sfw_admin_create_order_scheduler' ) );

				add_action( 'wps_sfw_create_renewal_order_schedule', array( $this, 'wps_sfw_renewal_order_on_scheduler_callback' ) );

				add_action( 'wps_sfw_expired_renewal_subscription', array( $this, 'wps_sfw_expired_renewal_subscription_callback' ) );

				if ( wps_sfw_is_enable_usage_tracking() ) {
					add_action( 'wpswings_tracker_send_event', array( $this, 'wps_sfw_wpswings_tracker_send_event' ) );
				}
			}
		}


		/**
		 * This function is used to create renewal order on scheduler.
		 *
		 * @name wps_sfw_renewal_order_on_scheduler
		 * @since 1.0.0
		 */
		public function wps_sfw_renewal_order_on_scheduler() {

			$wps_sfw_pro_plugin_activated = false;
			if ( in_array( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$wps_sfw_pro_plugin_activated = true;
			}
			$current_time = current_time( 'timestamp' );

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'wps_subscriptions',
				'post_status'   => 'wc-wps_renewal',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => 'wps_subscription_status',
						'value' => 'active',
					),
					array(
						'relation' => 'AND',
						array(
							'key'   => 'wps_parent_order',
							'compare' => 'EXISTS',
						),
						array(
							'key'   => 'wps_next_payment_date',
							'value' => $current_time,
							'compare' => '<',
						),
					),
				),
			);
			$wps_subscriptions = get_posts( $args );

			Subscriptions_For_Woocommerce_Log::log( 'WPS Renewal Subscriptions: ' . wc_print_r( $wps_subscriptions, true ) );
			if ( isset( $wps_subscriptions ) && ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {

				foreach ( $wps_subscriptions as $key => $value ) {
					$subscription_id = $value->ID;

					if ( wps_sfw_check_valid_subscription( $subscription_id ) ) {

						if ( apply_filters( 'wps_sfw_recurring_allow_on_scheduler', false, $subscription_id ) ) {
							return;
						}

						$subscription = get_post( $subscription_id );

						$parent_order_id  = $subscription->wps_parent_order;
						if ( function_exists( 'wps_sfw_check_valid_order' ) && ! wps_sfw_check_valid_order( $parent_order_id ) ) {
							continue;
						}

						if ( apply_filters( 'wps_sfw_stop_creating_renewal_multisafepay', false, $subscription_id ) ) {
							continue;
						}

						if ( ! $wps_sfw_pro_plugin_activated ) {
							$subp_id = wps_sfw_get_meta_data( $value->ID, 'product_id', true );
							$check_variable = wps_sfw_get_meta_data( $subp_id, 'wps_sfw_variable_product', true );
							if ( 'yes' === $check_variable ) {
								continue;
							}
						}

						$parent_order = wc_get_order( $parent_order_id );
						$billing_details = $parent_order->get_address( 'billing' );
						$shipping_details = $parent_order->get_address( 'shipping' );
						$parent_order_currency = $parent_order->get_currency();
						$new_status = 'wc-wps_renewal';
						$user_id = $subscription->wps_customer_id;
						$product_id = $subscription->product_id;
						$product_qty = $subscription->product_qty;
						$payment_method = $subscription->_payment_method;
						$payment_method_title = $subscription->_payment_method_title;

						$wps_old_payment_method = wps_sfw_get_meta_data( $parent_order_id, '_payment_method', true );
						$args = array(
							'status'      => $new_status,
							'customer_id' => $user_id,
						);
						$wps_new_order = wc_create_order( $args );
						$wps_new_order->set_currency( $parent_order_currency );

						$line_subtotal = $subscription->line_subtotal;
						$line_total = $subscription->line_total;

						$_product = wc_get_product( $product_id );

						// Check for manual subscription.
						$payment_type = wps_sfw_get_meta_data( $subscription_id, 'wps_wsp_payment_type', true );

						// this code will run from the 1.5.8.
						$new_sub = wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_new_sub', true );

						$variation_data = array();
						// Handle variation products separately.
						if ($_product && $_product->is_type('variable') && $product_id) {
							$variation_data = wc_get_product_variation_attributes($product_id);
						}

						if ( 'yes' === $new_sub ) {
							$wps_args = array(
								'variation' => $variation_data,
								'totals'    => array(
									'subtotal'     => $line_subtotal,
									'subtotal_tax' => $subscription->line_subtotal_tax,
									'total'        => $line_total,
									'tax'          => $subscription->line_tax,
									'tax_data'     => maybe_unserialize( $subscription->line_tax_data ),
								),
							);
						} else {
							$include = get_option( 'woocommerce_prices_include_tax' );

							// check for manual subscription.
							if ( 'yes' == $include && empty( $payment_type ) ) {
								$wps_args = array(
									'variation' => $variation_data,
									'totals'    => array(
										'subtotal'     => $line_subtotal - $subscription->line_subtotal_tax,
										'subtotal_tax' => $subscription->line_subtotal_tax,
										'total'        => $line_total - $subscription->line_subtotal_tax,
										'tax'          => $subscription->line_tax,
										'tax_data'     => maybe_unserialize( $subscription->line_tax_data ),
									),
								);
							} else {
								$wps_args = array(
									'variation' => $variation_data,
									'totals'    => array(
										'subtotal'     => $line_subtotal,
										'subtotal_tax' => $subscription->line_subtotal_tax,
										'total'        => $line_total,
										'tax'          => $subscription->line_tax,
										'tax_data'     => maybe_unserialize( $subscription->line_tax_data ),
									),
								);
							}
						}
						$wps_pro_args = apply_filters( 'wps_product_args_for_order', $wps_args );

						if ( 'wps_wsp_manual_method' == $payment_type ) {
							// hook to add product for renewal manual subscription order.
							do_action( 'wps_sfw_add_new_product_for_manual_subscription', $wps_new_order->get_id(), $subscription_id );

						} else {

							$new_item_id = $wps_new_order->add_product(
								$_product,
								$product_qty,
								$wps_pro_args
							);
							// Fetch all item meta data correctly.
							foreach ( $subscription->get_items() as $item_id => $item ) {
								$get_product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
								if ( $get_product_id == $product_id ) {
									 // Fetch all item meta data correctly.
									$item_meta = wc_get_order_item_meta($item_id, '', false); // Correct way to get all meta.

									if (!empty($item_meta)) {
										foreach ($item_meta as $meta_key => $meta_values) {
											if (is_array($meta_values)) {
												foreach ($meta_values as $meta_value) {
													wc_add_order_item_meta($new_item_id, $meta_key, $meta_value);
												}
											} else {
												wc_add_order_item_meta($new_item_id, $meta_key, $meta_values);
											}
										}
									}
								}
							}
						}


						$order_id = $wps_new_order->get_id();

						Subscriptions_For_Woocommerce_Log::log( 'WPS Renewal Order ID: ' . wc_print_r( $order_id, true ) );

						$wps_new_order->set_payment_method( $payment_method );
						$wps_new_order->set_payment_method_title( $payment_method_title );

						$wps_new_order->set_address( $billing_details, 'billing' );
						$wps_new_order->set_address( $shipping_details, 'shipping' );
						wps_sfw_update_meta_data( $order_id, 'wps_sfw_renewal_order', 'yes' );
						wps_sfw_update_meta_data( $order_id, 'wps_sfw_subscription', $subscription_id );
						wps_sfw_update_meta_data( $order_id, 'wps_sfw_parent_order_id', $parent_order_id );
						wps_sfw_update_meta_data( $subscription_id, 'wps_renewal_subscription_order', $order_id );

						// Billing phone number added.
						$billing_address = wps_sfw_get_meta_data( $parent_order_id, '_billing_address_index', true );
						wps_sfw_update_meta_data( $order_id, '_billing_address_index', $billing_address );

						// Renewal info.
						$wps_no_of_order = wps_sfw_get_meta_data( $subscription_id, 'wps_wsp_no_of_renewal_order', true );
						if ( empty( $wps_no_of_order ) ) {
							$wps_no_of_order = 1;
							wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_no_of_renewal_order', $wps_no_of_order );
						} else {
							$wps_no_of_order = (int) $wps_no_of_order + 1;
							wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_no_of_renewal_order', $wps_no_of_order );
						}
						$wps_renewal_order_data = wps_sfw_get_meta_data( $subscription_id, 'wps_wsp_renewal_order_data', true );
						if ( empty( $wps_renewal_order_data ) ) {
							$wps_renewal_order_data = array( $order_id );
							wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_renewal_order_data', $wps_renewal_order_data );
						} else {
							$wps_renewal_order_data[] = $order_id;
							wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_renewal_order_data', $wps_renewal_order_data );
						}
						wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_last_renewal_order_id', $order_id );

						$wps_new_order->update_taxes();
						$wps_new_order->calculate_totals();
						$wps_new_order->save();

						do_action( 'wps_sfw_renewal_order_creation', $wps_new_order, $subscription_id );

						$wps_sfw_status = 'pending';
						$wps_link = add_query_arg(
							array(
								'wps_subscription_id'               => $subscription_id,
								'wps_subscription_view_renewal_order'     => $wps_sfw_status,
							),
							admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table' )
						);
						$wps_link = wp_nonce_url( $wps_link, $subscription_id . $wps_sfw_status );
						/* translators: %s: subscription name */
						$wps_new_order->add_order_note( sprintf( __( 'This renewal order belongs to Subscription #%s', 'subscriptions-for-woocommerce' ), '<a href="' . $wps_link . '">' . $subscription_id . '</a>' ) );

						do_action( 'wps_sfw_subscription_bundle_addition', $order_id, $subscription_id, $_product );

						do_action( 'wps_sfw_subscription_subscription_box_addtion', $order_id, $subscription_id, $_product );

						// custom hook for addon.
						do_action( 'wps_sfw_renewal_bundle_addition', $order_id, $subscription_id, $_product );
						do_action( 'wps_sfw_add_addon_for_renewal', $order_id, $subscription_id );

						// if trial period enable.
						if ( '' == $wps_old_payment_method ) {
							$parent_order_id = $subscription_id;
						}

						// update next payment date.
						$wps_next_payment_date = wps_sfw_next_payment_date( $subscription_id, $current_time, 0 );

						wps_sfw_update_meta_data( $subscription_id, 'wps_next_payment_date', $wps_next_payment_date );

						// custom filter.
						if ( apply_filters( 'wps_sfw_stop_recurring_payment_incase_manual', false, $parent_order_id ) ) {
							return;
						}
						$wps_new_order = wc_get_order( $wps_new_order->get_id() ); // recalucate when shipping fee applied.

						// custom filter.
						do_action( 'wps_sfw_other_payment_gateway_renewal', $wps_new_order, $subscription_id, $payment_method );

						if ( $wps_new_order->get_status() == 'processing' ) {
							$virtual_order = false;
							foreach ( $wps_new_order->get_items() as $item ) {
								$product = $item->get_product();
								if ( $product->is_virtual() || $product->is_downloadable() ) {
									if ( 'mwb_booking' === $product->get_type() ) {
										$virtual_order = false;
										break;
									} else {
										$virtual_order = true;
										break;
									}
								}
							}

							// If the order only contains virtual or downloadable products, mark it as complete.
							if ( $virtual_order ) {
								$wps_new_order->update_status( 'completed' );
							}
						}
						do_action( 'wps_sfw_after_renewal_payment', $wps_new_order, $subscription_id, $payment_method );

						// hook for par plugin compatible .
						do_action( 'wps_sfw_compatible_points_and_rewards', $order_id );
					}
				}
			}
		}

		/**
		 * This function is used to  scheduler.
		 *
		 * @name wps_sfw_admin_create_order_scheduler
		 * @since 1.0.0
		 */
		public function wps_sfw_admin_create_order_scheduler() {
			if ( class_exists( 'ActionScheduler' ) ) {
				if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'wps_sfw_create_renewal_order_schedule' ) ) {
					as_schedule_recurring_action( strtotime( 'hourly' ), 1800, 'wps_sfw_create_renewal_order_schedule' );
				}
				if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'wps_sfw_expired_renewal_subscription' ) ) {
					as_schedule_recurring_action( strtotime( 'hourly' ), 1800, 'wps_sfw_expired_renewal_subscription' );
				}

				do_action( 'wps_sfw_create_admin_scheduler' );
			}
		}

		/**
		 * This function is used to  expired susbcription.
		 *
		 * @name wps_sfw_expired_renewal_subscription_callback
		 * @since 1.0.0
		 */
		public function wps_sfw_expired_renewal_subscription_callback() {
			$current_time = current_time( 'timestamp' );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$args = array(
					'return'   => 'ids',
					'type'   => 'wps_subscriptions',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'   => 'wps_subscription_status',
							'value' => array( 'active', 'pending' ),
						),
						array(
							'relation' => 'AND',
							array(
								'key'   => 'wps_parent_order',
								'compare' => 'EXISTS',
							),
							array(
								'relation' => 'AND',
								array(
									'key'   => 'wps_susbcription_end',
									'value' => $current_time,
									'compare' => '<',
								),
								array(
									'key'   => 'wps_susbcription_end',
									'value' => 0,
									'compare' => '!=',
								),
							),
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
							'key'   => 'wps_subscription_status',
							'value' => array( 'active', 'pending' ),
						),
						array(
							'relation' => 'AND',
							array(
								'key'   => 'wps_parent_order',
								'compare' => 'EXISTS',
							),
							array(
								'relation' => 'AND',
								array(
									'key'   => 'wps_susbcription_end',
									'value' => $current_time,
									'compare' => '<',
								),
								array(
									'key'   => 'wps_susbcription_end',
									'value' => 0,
									'compare' => '!=',
								),
							),
						),
					),
				);
				$wps_subscriptions = get_posts( $args );

			}
			Subscriptions_For_Woocommerce_Log::log( 'WPS Expired Subscriptions: ' . wc_print_r( $wps_subscriptions, true ) );
			if ( isset( $wps_subscriptions ) && ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
				foreach ( $wps_subscriptions as $key => $value ) {

					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$susbcription_id = $value;
					} else {
						$susbcription_id = $value->ID;
					}

					if ( wps_sfw_check_valid_subscription( $susbcription_id ) ) {
						// Send expired email notification.
						wps_sfw_send_email_for_expired_susbcription( $susbcription_id );
						wps_sfw_update_meta_data( $susbcription_id, 'wps_subscription_status', 'expired' );
						wps_sfw_update_meta_data( $susbcription_id, 'wps_next_payment_date', '' );
						do_action( 'wps_sfw_expire_subscription_scheduler', $susbcription_id );
					}
				}
			}
		}

		/**
		 * Function is used for the sending the track data
		 *
		 * @name wps_sfw_wpswings_tracker_send_event
		 * @since 1.0.0
		 */
		public function wps_sfw_wpswings_tracker_send_event() {

			require WC()->plugin_path() . '/includes/class-wc-tracker.php';

			$last_send = get_option( 'wpswings_tracker_last_send' );
			if ( ! apply_filters( 'wpswings_tracker_send_override', false ) ) {

				// Send a maximum of once per week by default.
				$last_send = $this->wps_sfw_last_send_time();
				if ( $last_send && $last_send > apply_filters( 'wpswings_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {

					return;
				}
			} else {

				// Make sure there is at least a 1 hour delay between override sends, we don't want duplicate calls due to double clicking links.
				$last_send = $this->wps_sfw_last_send_time();
				if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {

					return;
				}
			}
			// Update time first before sending to ensure it is set.
			update_option( 'wpswings_tracker_last_send', time() );
			$params = WC_Tracker::get_tracking_data();
			$params['extensions']['subscriptions_for_woocommerce'] = array(
				'version' => SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION,
				'site_url' => home_url(),
				'subscriptions_details' => $this->wps_get_subscriptions_counts(),
				'subscription_products' => $this->wps_get_subscriptions_products(),
			);

			$params = apply_filters( 'wpswings_tracker_params', $params );
			$api_url = 'https://tracking.wpswings.com/wp-json/mps-route/v1/mps-testing-data/';
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
		 * @name wps_get_subscriptions_counts
		 * @since 1.0.0
		 */
		public function wps_get_subscriptions_counts() {
			$subscriptions_details = array();

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$args = array(
					'return' => 'ids',
					'limit' => -1,
					'type'   => 'wps_subscriptions',
					'meta_query' => array(
						array(
							'key'   => 'wps_customer_id',
							'compare' => 'EXISTS',
						),
					),
				);
				$wps_subscriptions = wc_get_orders( $args );
			} else {
				$args = array(
					'numberposts' => -1,
					'post_type'   => 'wps_subscriptions',
					'post_status' => 'wc-wps_renewal',
					'meta_query' => array(
						array(
							'key'   => 'wps_customer_id',
							'compare' => 'EXISTS',
						),
					),
				);
				$wps_subscriptions = get_posts( $args );
			}
			$total_subscriptions = count( $wps_subscriptions );
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

			if ( isset( $wps_subscriptions ) && ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
				foreach ( $wps_subscriptions as $key => $subscription ) {
					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$subscription_id = $subscription;
					} else {
						$subscription_id = $subscription->ID;
					}
					$status = wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true );
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
		 * @name wps_get_subscriptions_products
		 * @since 1.0.0
		 */
		public function wps_get_subscriptions_products() {

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'product',
				'post_status' => 'publish',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'   => '_wps_sfw_product',
						'value' => 'yes',
					),
					array(
						'key'   => 'wps_sfw_variable_product',
						'value' => 'yes',
					),
				),
			);
			$wps_subscriptions = get_posts( $args );

			$total_subscriptions = count( $wps_subscriptions );
			return $total_subscriptions;
		}

		/**
		 * Get the updated time.
		 *
		 * @name wps_sfw_last_send_time
		 *
		 * @since 1.0.0
		 */
		public function wps_sfw_last_send_time() {
			return apply_filters( 'wpswings_tracker_last_send_time', get_option( 'wpswings_tracker_last_send', false ) );
		}

		/**
		 * This function is used to create renewal order on scheduler.
		 *
		 * @name wps_sfw_renewal_order_on_scheduler
		 * @since 1.0.0
		 */
		public function wps_sfw_renewal_order_on_scheduler_hpos() {

			$wps_sfw_pro_plugin_activated = false;
			if ( in_array( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$wps_sfw_pro_plugin_activated = true;
			}
			$current_time = current_time( 'timestamp' );

			$args = array(
				'return'   => 'ids',
				'type'   => 'wps_subscriptions',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => 'wps_subscription_status',
						'value' => 'active',
					),
					array(
						'relation' => 'AND',
						array(
							'key'   => 'wps_parent_order',
							'compare' => 'EXISTS',
						),
						array(
							'key'   => 'wps_next_payment_date',
							'value' => $current_time,
							'compare' => '<',
						),
					),
				),
			);
			$wps_subscriptions = wc_get_orders( $args );

			Subscriptions_For_Woocommerce_Log::log( 'WPS Renewal Subscriptions: ' . wc_print_r( $wps_subscriptions, true ) );
			if ( isset( $wps_subscriptions ) && ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {

				foreach ( $wps_subscriptions as $key => $value ) {
					$subscription_id = $value;

					if ( wps_sfw_check_valid_subscription( $subscription_id ) ) {

						if ( apply_filters( 'wps_sfw_recurring_allow_on_scheduler', false, $subscription_id ) ) {
							return;
						}

						$subscription = new WPS_Subscription( $subscription_id );

						$parent_order_id = wps_sfw_get_meta_data( $subscription_id, 'wps_parent_order', true );
						if ( function_exists( 'wps_sfw_check_valid_order' ) && ! wps_sfw_check_valid_order( $parent_order_id ) ) {
							continue;
						}

						if ( apply_filters( 'wps_sfw_stop_creating_renewal_multisafepay', false, $subscription_id ) ) {
							continue;
						}

						if ( ! $wps_sfw_pro_plugin_activated ) {
							$subp_id = wps_sfw_get_meta_data( $subscription_id, 'product_id', true );
							$check_variable = wps_sfw_get_meta_data( $subp_id, 'wps_sfw_variable_product', true );
							if ( 'yes' === $check_variable ) {
								continue;
							}
						}

						$parent_order = wc_get_order( $parent_order_id );
						$billing_details = $parent_order->get_address( 'billing' );
						$shipping_details = $parent_order->get_address( 'shipping' );
						$parent_order_currency = $parent_order->get_currency();
						$new_status = 'wc-wps_renewal';

						$user_id = wps_sfw_get_meta_data( $subscription_id, 'wps_customer_id', true );
						$product_id = wps_sfw_get_meta_data( $subscription_id, 'product_id', true );
						$product_qty = wps_sfw_get_meta_data( $subscription_id, 'product_qty', true );

						$payment_method = $subscription->get_payment_method();
						$payment_method_title = $subscription->get_payment_method_title();

						$wps_old_payment_method = $parent_order->get_payment_method();
						$args = array(
							'status'      => $new_status,
							'customer_id' => $user_id,
						);
						$wps_new_order = wc_create_order( $args );
						$wps_new_order->set_currency( $parent_order_currency );

						$line_subtotal = wps_sfw_get_meta_data( $subscription_id, 'line_subtotal', true );
						$line_total = wps_sfw_get_meta_data( $subscription_id, 'line_total', true );

						$line_subtotal_tax = wps_sfw_get_meta_data( $subscription_id, 'line_subtotal_tax', true );
						$line_tax = wps_sfw_get_meta_data( $subscription_id, 'line_tax', true );
						$line_tax_data = wps_sfw_get_meta_data( $subscription_id, 'line_tax_data', true );

						$_product = wc_get_product( $product_id );

						// check for manual subscription.
						$payment_type = wps_sfw_get_meta_data( $subscription_id, 'wps_wsp_payment_type', true );

						// this code will run from the 1.5.8.
						$new_sub = wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_new_sub', true );

						// Initialize variation array.
						$variation_data = [];

						// Handle variation products separately.
						if ($_product && $_product->is_type('variable') && $product_id) {
							$variation_data = wc_get_product_variation_attributes($product_id);
						}

						if ( 'yes' === $new_sub ) {
							$wps_args = array(
								'variation' => $variation_data,
								'totals'    => array(
									'subtotal'     => $line_subtotal,
									'subtotal_tax' => $line_subtotal_tax,
									'total'        => $line_total,
									'tax'          => $line_tax,
									'tax_data'     => maybe_unserialize( $line_tax_data ),
								),
							);
						} else {
							$include = get_option( 'woocommerce_prices_include_tax' );

							if ( 'yes' == $include && empty( $payment_type ) ) {
								$wps_args = array(
									'variation' => $variation_data,
									'totals'    => array(
										'subtotal'     => $line_subtotal - $line_subtotal_tax,
										'subtotal_tax' => $line_subtotal_tax,
										'total'        => $line_total - $line_subtotal_tax,
										'tax'          => $line_tax,
										'tax_data'     => maybe_unserialize( $line_tax_data ),
									),
								);
							} else {
								$wps_args = array(
									'variation' => $variation_data,
									'totals'    => array(
										'subtotal'     => $line_subtotal,
										'subtotal_tax' => $line_subtotal_tax,
										'total'        => $line_total,
										'tax'          => $line_tax,
										'tax_data'     => maybe_unserialize( $line_tax_data ),
									),
								);
							}
						}
						$wps_pro_args = apply_filters( 'wps_product_args_for_order', $wps_args );

						if ( 'wps_wsp_manual_method' == $payment_type ) {
							// Hook to add product for renewal manual subscription order.
							do_action( 'wps_sfw_add_new_product_for_manual_subscription', $wps_new_order->get_id(), $subscription_id );

						} else {
							$new_item_id = $wps_new_order->add_product(
								$_product,
								$product_qty,
								$wps_pro_args
							);
							// Fetch all item meta data correctly.
							foreach ( $subscription->get_items() as $item_id => $item ) {
								$get_product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
								if ( $get_product_id == $product_id ) {
									$item_meta = wc_get_order_item_meta($item_id, '', false); // Correct way to get all meta.

									if (!empty($item_meta)) {
										foreach ($item_meta as $meta_key => $meta_values) {
											if (is_array($meta_values)) {
												foreach ($meta_values as $meta_value) {
													wc_add_order_item_meta($new_item_id, $meta_key, $meta_value);
												}
											} else {
												wc_add_order_item_meta($new_item_id, $meta_key, $meta_values);
											}
										}
									}
								}
							}
						}

						$order_id = $wps_new_order->get_id();

						Subscriptions_For_Woocommerce_Log::log( 'WPS Renewal Order ID: ' . wc_print_r( $order_id, true ) );

						$wps_new_order->set_payment_method( $payment_method );
						$wps_new_order->set_payment_method_title( $payment_method_title );

						$wps_new_order->set_address( $billing_details, 'billing' );
						$wps_new_order->set_address( $shipping_details, 'shipping' );

						wps_sfw_update_meta_data( $order_id, 'wps_sfw_renewal_order', 'yes' );
						wps_sfw_update_meta_data( $order_id, 'wps_sfw_subscription', $subscription_id );
						wps_sfw_update_meta_data( $order_id, 'wps_sfw_parent_order_id', $parent_order_id );
						wps_sfw_update_meta_data( $subscription_id, 'wps_renewal_subscription_order', $order_id );

						// Billing phone number added.
						$billing_address = wps_sfw_get_meta_data( $parent_order_id, '_billing_address_index', true );
						wps_sfw_update_meta_data( $order_id, '_billing_address_index', $billing_address );

						// Renewal info.
						$wps_no_of_order = wps_sfw_get_meta_data( $subscription_id, 'wps_wsp_no_of_renewal_order', true );
						if ( empty( $wps_no_of_order ) ) {
							$wps_no_of_order = 1;
							wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_no_of_renewal_order', $wps_no_of_order );
						} else {
							$wps_no_of_order = (int) $wps_no_of_order + 1;
							wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_no_of_renewal_order', $wps_no_of_order );
						}
						$wps_renewal_order_data = wps_sfw_get_meta_data( $subscription_id, 'wps_wsp_renewal_order_data', true );
						if ( empty( $wps_renewal_order_data ) ) {
							$wps_renewal_order_data = array( $order_id );
							wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_renewal_order_data', $wps_renewal_order_data );
						} else {
							$wps_renewal_order_data[] = $order_id;
							wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_renewal_order_data', $wps_renewal_order_data );
						}
						wps_sfw_update_meta_data( $subscription_id, 'wps_wsp_last_renewal_order_id', $order_id );

						$wps_new_order->update_taxes();
						$wps_new_order->calculate_totals();
						$wps_new_order->save();

						do_action( 'wps_sfw_renewal_order_creation', $wps_new_order, $subscription_id );

						$wps_sfw_status = 'pending';
						$wps_link = add_query_arg(
							array(
								'wps_subscription_id'               => $subscription_id,
								'wps_subscription_view_renewal_order'     => $wps_sfw_status,
							),
							admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table' )
						);
						$wps_link = wp_nonce_url( $wps_link, $subscription_id . $wps_sfw_status );
						/* translators: %s: subscription name */
						$wps_new_order->add_order_note( sprintf( __( 'This renewal order belongs to Subscription #%s', 'subscriptions-for-woocommerce' ), '<a href="' . $wps_link . '">' . $subscription_id . '</a>' ) );

						do_action( 'wps_sfw_subscription_bundle_addition', $order_id, $subscription_id, $_product );

						do_action( 'wps_sfw_subscription_subscription_box_addtion', $order_id, $subscription_id, $_product );

						// custom hook for addon.
						do_action( 'wps_sfw_renewal_bundle_addition', $order_id, $subscription_id, $_product );
						do_action( 'wps_sfw_add_addon_for_renewal', $order_id, $subscription_id );

						// if trial period enable.
						if ( '' == $wps_old_payment_method ) {
							$parent_order_id = $subscription_id;
						}
						// update next payment date.
						$wps_next_payment_date = wps_sfw_next_payment_date( $subscription_id, $current_time, 0 );

						wps_sfw_update_meta_data( $subscription_id, 'wps_next_payment_date', $wps_next_payment_date );

						// custom filter.
						if ( apply_filters( 'wps_sfw_stop_recurring_payment_incase_manual', false, $parent_order_id ) ) {
							return;
						}
						$wps_new_order = wc_get_order( $wps_new_order->get_id() ); // recalucate when shipping fee applied
						// custom filter.

						do_action( 'wps_sfw_other_payment_gateway_renewal', $wps_new_order, $subscription_id, $payment_method );

						if ( $wps_new_order->get_status() == 'processing' ) {
							$virtual_order = false;
							foreach ( $wps_new_order->get_items() as $item ) {
								$product = $item->get_product();
								if ( $product->is_virtual() || $product->is_downloadable() ) {
									if ( 'mwb_booking' === $product->get_type() ) {
										$virtual_order = false;
										break;
									} else {
										$virtual_order = true;
										break;
									}
								}
							}

							// If the order only contains virtual or downloadable products, mark it as complete.
							if ( $virtual_order ) {
								$wps_new_order->update_status( 'completed' );
							}
						}
						do_action( 'wps_sfw_after_renewal_payment', $wps_new_order, $subscription_id, $payment_method );

						// hook for par plugin compatible .
						do_action( 'wps_sfw_compatible_points_and_rewards', $order_id );
					}
				}
			}
		}

		/**
		 * Will call the appropriate function for the renewal creation.
		 */
		public function wps_sfw_renewal_order_on_scheduler_callback() {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$this->wps_sfw_renewal_order_on_scheduler_hpos();
			} else {
				$this->wps_sfw_renewal_order_on_scheduler();
			}
		}
	}
}
return new Subscriptions_For_Woocommerce_Scheduler();
