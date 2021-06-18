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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'MWB_Sfw_PayPal_IPN_Handler' ) ) {

	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Defines the paypal functionality
	 *
	 * @package    Subscriptions_For_Woocommerce
	 * @subpackage Subscriptions_For_Woocommerce/public
	 * @author     makewebbetter <webmaster@makewebbetter.com>
	 */
	class MWB_Sfw_PayPal_IPN_Handler extends WC_Gateway_Paypal_IPN_Handler {

			/**
			 * The transaction type of paypal.
			 *
			 * @since    1.0.0
			 * @access   private
			 * @var      string    $mwb_transaction_types    transaction type of paypal.
			 */
			private $mwb_transaction_types = array(
				'subscr_signup',
				'subscr_payment',
				'subscr_cancel',
				'subscr_eot',
				'subscr_failed',
				'recurring_payment_suspended_due_to_max_failed_payment',
			);

			/**
			 * Constructor
			 *
			 * Initialize plugin and registers actions and filters to be used
			 *
			 * @since  1.0.0
			 * @author     makewebbetter <webmaster@makewebbetter.com>
			 *
			 * @param bool   $sandbox sandbox.
			 * @param string $receiver_email receiver_email.
			 */
			public function __construct( $sandbox = false, $receiver_email = '' ) {

				$this->receiver_email = $receiver_email;
				$this->sandbox        = $sandbox;
			}

			/**
			 * This function is used to check valid response.
			 *
			 * @name mwb_sfw_valid_response
			 * @param string $mwb_transaction_details mwb_transaction_details.
			 * @since    1.0.1
			 */
			public function mwb_sfw_valid_response( $mwb_transaction_details ) {
				global $wpdb;

				$mwb_transaction_details = stripslashes_deep( $mwb_transaction_details );

				if ( ! $this->mwb_validate_transaction_type( $mwb_transaction_details['txn_type'] ) ) {
					return;
				}

				if ( ! empty( $mwb_transaction_details['custom'] ) ) {
					$order = $this->get_paypal_order( $mwb_transaction_details['custom'] );
				} elseif ( ! empty( $mwb_transaction_details['invoice'] ) ) {
					$order = wc_get_order( substr( $mwb_transaction_details['invoice'], strrpos( $mwb_transaction_details['invoice'], '-' ) + 1 ) );
				}

				if ( isset( $order ) ) {

					$order_id = $order->get_id();
					WC_Gateway_Paypal::log( 'MWB - Found order #' . $order_id );

					$mwb_transaction_details['payment_status'] = strtolower( $mwb_transaction_details['payment_status'] );

					WC_Gateway_Paypal::log( 'MWB - Txn Type: ' . $mwb_transaction_details['txn_type'] );

					$this->mwb_sfw_process_ipn_request( $order, $mwb_transaction_details );

				} else {
					WC_Gateway_Paypal::log( 'MWB - Order Not Found.' );
				}
			}

			/**
			 * This function is used to validate transaction type.
			 *
			 * @name mwb_validate_transaction_type
			 * @param string $txn_type txn_type.
			 * @since    1.0.1
			 */
			private function mwb_validate_transaction_type( $txn_type ) {
				if ( in_array( strtolower( $txn_type ), $this->mwb_get_transaction_types() ) ) {
					return true;
				} else {
					return false;
				}
			}

			/**
			 * This function is used to get transaction type.
			 *
			 * @name mwb_get_transaction_types
			 * @since    1.0.1
			 */
			private function mwb_get_transaction_types() {
				return $this->mwb_transaction_types;
			}

			/**
			 * This function is used to process paypal response.
			 *
			 * @name mwb_sfw_process_ipn_request
			 * @param object $order order.
			 * @param array  $mwb_transaction_details mwb_transaction_details.
			 * @since    1.0.1
			 */
			private function mwb_sfw_process_ipn_request( $order, $mwb_transaction_details ) {

				if ( isset( $mwb_transaction_details['mc_currency'] ) ) {
					$this->validate_currency( $order, $mwb_transaction_details['mc_currency'] );
				}
				WC_Gateway_Paypal::log( 'MWB - currency validation successfull' );
				if ( isset( $mwb_transaction_details['receiver_email'] ) ) {
					$this->validate_receiver_email( $order, $mwb_transaction_details['receiver_email'] );
				}

				WC_Gateway_Paypal::log( 'MWB - Email validation successfull' );
				$this->save_paypal_meta_data( $order, $mwb_transaction_details );
				$this->mwb_paypal_ipn_request( $order, $mwb_transaction_details );
			}

			/**
			 * This function is used to process paypal inp request.
			 *
			 * @name mwb_paypal_ipn_request
			 * @param object $order order.
			 * @param array  $mwb_transaction_details mwb_transaction_details.
			 * @since    1.0.1
			 */
			private function mwb_paypal_ipn_request( $order, $mwb_transaction_details ) {
				// show the data in log file.
				WC_Gateway_Paypal::log( 'MWB - Transaction log payment:' . wc_print_r( $mwb_transaction_details, true ) );
				$mwb_order_statuses = array( 'on-hold', 'pending', 'failed', 'cancelled', 'wc-mwb_renewal' );
				$mwb_order_info = $this->mwb_sfw_get_order_info( $mwb_transaction_details );
				if ( $order->get_order_key() != $mwb_order_info['order_key'] ) {
					WC_Gateway_Paypal::log( 'MWB - Order keys not matching' );
					return;
				}
				$order_id = $order->get_id();
				// check if the transaction has been processed.
				$mwb_order_transaction_id = get_post_meta( $order_id, '_mwb_paypal_transaction_ids', true );
				$mwb_order_transactions   = $this->mwb_sfw_validate_transaction( $mwb_order_transaction_id, $mwb_transaction_details );

				if ( $mwb_order_transactions ) {
					update_post_meta( $order_id, '_mwb_paypal_transaction_ids', $order_transactions );
				} else {
					WC_Gateway_Paypal::log( 'MWB - Transaction ID already processed' );
					return;
				}

				$mwb_order_has_susbcription = get_post_meta( $order_id, 'mwb_sfw_order_has_subscription', true );

				if ( 'yes' != $mwb_order_has_susbcription ) {
					WC_Gateway_Paypal::log( 'MWB - Not a valid Subscription' );
					return;
				}

				$mwb_subscription_id = get_post_meta( $order_id, 'mwb_subscription_id', true );

				if ( empty( $mwb_subscription_id ) ) {

					WC_Gateway_Paypal::log( 'MWB - IPN subscription payment error - ' . $order_id . ' haven\'t subscriptions' );
					return;

				}
				/*check for valid subscription*/
				if ( ! mwb_sfw_check_valid_subscription( $mwb_subscription_id ) ) {
					WC_Gateway_Paypal::log( 'MWB - IPN subscription payment error - ' . $order_id . ' haven\'t valid subscriptions' );
					return;
				}

				$subscription = wc_get_order( $mwb_subscription_id );

				switch ( $mwb_transaction_details['txn_type'] ) {
					case 'subscr_signup':
						$args = array(
							'mwb_subscriber_id'         => $mwb_transaction_details['subscr_id'],
							'mwb_subscriber_first_name' => $mwb_transaction_details['first_name'],
							'mwb_subscriber_last_name'  => $mwb_transaction_details['last_name'],
							'mwb_subscriber_address'    => $mwb_transaction_details['payer_email'],
						);
						$this->mwb_sfw_save_post_data( $order->get_id(), $args );
						$order->add_order_note( __( 'IPN subscription started', 'subscriptions-for-woocommerce' ) );

						if ( isset( $mwb_transaction_details['mc_amount1'] ) && 0 == $mwb_transaction_details['mc_amount1'] ) {
							$order->payment_complete( $mwb_transaction_details['txn_id'] );
						}

						$args = array(
							'mwb_sfw_paypal_transaction_id'        => $mwb_transaction_details['txn_id'],
							'mwb_sfw_paypal_subscriber_id'         => $mwb_transaction_details['subscr_id'],

						);
						$this->mwb_sfw_save_post_data( $mwb_subscription_id, $args );

						break;
					case 'subscr_payment':
						// show the data in log file.
						WC_Gateway_Paypal::log( 'MWB - Transaction log for subscr_payment:' . wc_print_r( $mwb_transaction_details, true ) );
						if ( 'completed' == strtolower( $mwb_transaction_details['payment_status'] ) ) {

							$mwb_order_transactions = get_post_meta( $mwb_subscription_id, '_mwb_paypal_transaction_ids', true );
							$mwb_order_transactions    = $this->mwb_sfw_validate_transaction( $mwb_order_transactions, $mwb_transaction_details );
							if ( $mwb_order_transactions ) {
								update_post_meta( $mwb_subscription_id, '_mwb_paypal_transaction_ids', $mwb_order_transactions );
							} else {
								WC_Gateway_Paypal::log( 'MWB - Transaction ID Error' );
								return;
							}
							$mwb_pending_order    = false;
							$mwb_renewal_order = $subscription->mwb_renewal_subscription_order;
							WC_Gateway_Paypal::log( 'MWB - Renewal Order ID:' . $mwb_renewal_order );
							if ( intval( $mwb_renewal_order ) ) {
								$mwb_pending_order = wc_get_order( $mwb_renewal_order );
							}

							if ( isset( $mwb_transaction_details['mc_gross'] ) ) {
								if ( $mwb_pending_order ) {
									$this->mwb_paypal_validate_amount( $mwb_pending_order, $mwb_transaction_details['mc_gross'] );
								} elseif ( $order->has_status( $mwb_order_statuses ) ) {
									$this->mwb_paypal_validate_amount( $order, $mwb_transaction_details['mc_gross'] );
								}
							}
							if ( isset( $mwb_transaction_details['subscr_id'] ) ) {
								$mwb_sub_id = $mwb_transaction_details['subscr_id'];
							} elseif ( isset( $mwb_transaction_details['recurring_payment_id'] ) ) {
								$mwb_sub_id = $mwb_transaction_details['recurring_payment_id'];
							}

							WC_Gateway_Paypal::log( 'MWB - Subscription Status:' . ( $subscription->mwb_subscription_status ) );

							if ( 'pending' == $subscription->mwb_subscription_status || ( ! $mwb_pending_order && $order->has_status( $mwb_order_statuses ) ) ) {

								$args = array(
									'mwb_subscriber_id'        => $mwb_sub_id,
									'mwb_subscriber_first_name' => $mwb_transaction_details['first_name'],
									'mwb_subscriber_last_name' => $mwb_transaction_details['last_name'],
									'mwb_subscriber_address'   => $mwb_transaction_details['payer_email'],
									'mwb_subscriber_payment_type' => wc_clean( $mwb_transaction_details['payment_type'] ),
								);

								$this->mwb_sfw_save_post_data( $order->get_id(), $args );
								$order->add_order_note( __( 'IPN subscription payment completed.', 'subscriptions-for-woocommerce' ) );
								$order->payment_complete( $mwb_transaction_details['txn_id'] );

							} elseif ( $mwb_pending_order ) {
									$args = array(
										'mwb_subscriber_id'        => $mwb_sub_id,
										'mwb_subscriber_first_name' => $mwb_transaction_details['first_name'],
										'mwb_subscriber_last_name' => $mwb_transaction_details['last_name'],
										'mwb_subscriber_address'   => $mwb_transaction_details['payer_email'],
										'mwb_subscriber_payment_type' => wc_clean( $mwb_transaction_details['payment_type'] ),
									);

									$this->mwb_sfw_save_post_data( $mwb_pending_order->get_id(), $args );

									$mwb_pending_order->add_order_note( __( 'IPN subscription payment completed.', 'subscriptions-for-woocommerce' ) );
									$mwb_pending_order->payment_complete( $mwb_transaction_details['txn_id'] );

							} else {

								$mwb_renewal_order = $this->mwb_sfw_create_renewal_order_for_paypal( $mwb_subscription_id );

								if ( ! $mwb_renewal_order ) {
									WC_Gateway_Paypal::log( 'MWB - Renewal Order Creation failed' );
									return;
								}

								if ( isset( $mwb_transaction_details['mc_gross'] ) ) {
									$this->mwb_paypal_validate_amount( $mwb_renewal_order, $mwb_transaction_details['mc_gross'] );
								}

								$args = array(
									'mwb_subscriber_id'        => $mwb_sub_id,
									'mwb_subscriber_first_name' => $mwb_transaction_details['first_name'],
									'mwb_subscriber_last_name' => $mwb_transaction_details['last_name'],
									'mwb_subscriber_address'   => $mwb_transaction_details['payer_email'],
									'mwb_subscriber_payment_type' => wc_clean( $mwb_transaction_details['payment_type'] ),
								);

								$this->mwb_sfw_save_post_data( $mwb_renewal_order->get_id(), $args );

								$mwb_renewal_order->add_order_note( __( 'IPN subscription payment completed.', 'subscriptions-for-woocommerce' ) );
								$mwb_renewal_order->payment_complete( $mwb_transaction_details['txn_id'] );

							}
								$args = array(
									'mwb_sfw_paypal_transaction_id'        => $mwb_transaction_details['txn_id'],
									'mwb_sfw_paypal_subscriber_id'         => $mwb_sub_id,

								);
								$this->mwb_sfw_save_post_data( $mwb_subscription_id, $args );

								WC_Gateway_Paypal::log( 'MWB - Subscription successfull' );
						}

						break;
					case 'subscr_cancel':
						$mwb_subscriber_id = $this->mwb_sfw_get_paypal_susbcriber_id( $mwb_subscription_id );
						if ( $mwb_subscriber_id != $mwb_transaction_details['subscr_id'] ) {
							WC_Gateway_Paypal::log( 'IPN subscription cancellation request ignored ' . $mwb_subscription_id );
						} else {
							update_post_meta( $mwb_subscription_id, 'mwb_subscription_status', 'cancelled' );
							$order->add_order_note( __( 'MWB-IPN subscription cancelled for this order.', 'subscriptions-for-woocommerce' ) );
							WC_Gateway_Paypal::log( 'IPN subscription cancelled for subscription ' . $mwb_subscription_id );

						}
						break;
					case 'subscr_eot': // Subscription ended.
						WC_Gateway_Paypal::log( 'MWB-IPN EOT request ignored for subscription ' . $mwb_subscription_id );
						break;
					case 'subscr_failed': // Subscription sign up failed.
					case 'recurring_payment_suspended_due_to_max_failed_payment':
						$mwb_subscriber_id = $this->mwb_sfw_get_paypal_susbcriber_id( $mwb_subscription_id );
						if ( $mwb_subscriber_id != $mwb_transaction_details['subscr_id'] ) {
							WC_Gateway_Paypal::log( 'IPN subscription cancellation request ignored ' . $mwb_subscription_id );
						} else {

							$mwb_order_transactions = get_post_meta( $mwb_subscription_id, '_mwb_paypal_transaction_ids', true );
							$mwb_order_transactions    = $this->mwb_sfw_validate_transaction( $mwb_order_transactions, $mwb_transaction_details );
							if ( $mwb_order_transactions ) {
								update_post_meta( $mwb_subscription_id, '_mwb_paypal_transaction_ids', $mwb_order_transactions );
							} else {
								WC_Gateway_Paypal::log( 'MWB - Transaction ID Error' );
								return;
							}

							$mwb_pending_order    = false;
							$mwb_renewal_order = $subscription->mwb_renewal_subscription_order;
							WC_Gateway_Paypal::log( 'MWB - Renewal Order ID:' . $mwb_renewal_order );
							if ( intval( $mwb_renewal_order ) ) {
								$mwb_pending_order = wc_get_order( $mwb_renewal_order );
							}
							if ( $mwb_pending_order ) {

								$mwb_pending_order->add_order_note( sprintf( __( 'MWB - IPN Failed payment for  %$ ', 'subscriptions-for-woocommerce' ), $mwb_subscription_id ) );

							} else {
								$mwb_renewal_order = $this->mwb_sfw_create_renewal_order_for_paypal( $mwb_subscription_id );
								if ( ! $mwb_renewal_order ) {
									WC_Gateway_Paypal::log( 'MWB - Renewal Order Creation failed' );
									return;
								}
								$mwb_renewal_order->add_order_note( sprintf( __( 'MWB - IPN Failed payment for  %$ ', 'subscriptions-for-woocommerce' ), $mwb_subscription_id ) );
							}
							$subscription->add_order_note( __( 'MWB - IPN Failed payment', 'subscriptions-for-woocommerce' ) );
							WC_Gateway_Paypal::log( 'MWB - IPN Failed payment:' . $subscription );

							break;
						}
				}
			}

			/**
			 * This function is used to validate transaction.
			 *
			 * @name mwb_sfw_validate_transaction
			 * @param array $mwb_transaction_ids mwb_transaction_ids.
			 * @param array $mwb_transaction_details mwb_transaction_details.
			 * @since    1.0.1
			 */
			private function mwb_sfw_validate_transaction( $mwb_transaction_ids, $mwb_transaction_details ) {

				$mwb_transaction_ids = empty( $mwb_transaction_ids ) ? array() : $mwb_transaction_ids;
				// check if the ipn request as been processed.
				if ( isset( $mwb_transaction_details['txn_id'] ) ) {
					$transaction_id = $mwb_transaction_details['txn_id'] . '-' . $mwb_transaction_details['txn_type'];

					if ( isset( $mwb_transaction_details['payment_status'] ) ) {
						$transaction_id .= '-' . $mwb_transaction_details['payment_status'];
					}
					if ( empty( $mwb_transaction_ids ) || ! in_array( $transaction_id, $mwb_transaction_ids ) ) {
						$mwb_transaction_ids[] = $transaction_id;
					} else {

						WC_Gateway_Paypal::log( 'paypal', 'MWB - Subscription IPN Error: IPN ' . $transaction_id . ' message has already been correctly handled.' );
						return false;
					}
				} elseif ( isset( $mwb_transaction_details['ipn_track_id'] ) ) {
					$track_id = $mwb_transaction_details['txn_type'] . '-' . $mwb_transaction_details['ipn_track_id'];
					if ( empty( $mwb_transaction_ids ) || ! in_array( $track_id, $mwb_transaction_ids ) ) {
						$mwb_transaction_ids[] = $track_id;
					} else {

						WC_Gateway_Paypal::log( 'paypal', 'MWB - Subscription IPN Error: IPN ' . $track_id . ' message has already been correctly handled.' );
						return false;
					}
				}

				return $mwb_transaction_ids;

			}

			/**
			 * This function is used to save data.
			 *
			 * @name mwb_sfw_save_post_data
			 * @param int   $order_id order_id.
			 * @param array $args args.
			 * @since    1.0.1
			 */
			private function mwb_sfw_save_post_data( $order_id, $args ) {
				if ( isset( $order_id ) && ! empty( $order_id ) && ! empty( $args ) && is_array( $args ) ) {
					foreach ( $args as $key => $value ) {
						update_post_meta( $order_id, $key, $value );
					}
				}
			}

			/**
			 * This function is used to get order info.
			 *
			 * @name mwb_sfw_get_order_info
			 * @param array $args args.
			 * @since    1.0.1
			 */
			private function mwb_sfw_get_order_info( $args ) {
				return isset( $args['custom'] ) ? json_decode( $args['custom'], true ) : false;
			}

			/**
			 * This function is used to validate amount.
			 *
			 * @name mwb_paypal_validate_amount
			 * @param object $mwb_order mwb_order.
			 * @param int    $mwb_amount mwb_amount.
			 * @since    1.0.1
			 */
			private function mwb_paypal_validate_amount( $mwb_order, $mwb_amount ) {
				if ( wc_format_decimal( $mwb_order->get_total(), 2 ) != wc_format_decimal( $mwb_amount, 2 ) ) {
					WC_Gateway_Paypal::log( 'Amounts not matching: ' . $mwb_amount );
					$mwb_order->update_status( 'on-hold' );
					return;
				}
			}

			/**
			 * This function is used to get subscriber id.
			 *
			 * @name mwb_sfw_get_paypal_susbcriber_id
			 * @param int $subscription_id subscription_id.
			 * @since    1.0.1
			 */
			private function mwb_sfw_get_paypal_susbcriber_id( $subscription_id ) {
				$mwb_subscriber_id = '';
				if ( isset( $subscription_id ) && ! empty( $subscription_id ) ) {
					$mwb_subscriber_id = get_post_meta( $subscription_id, 'mwb_sfw_paypal_subscriber_id', true );
				}
				return $mwb_subscriber_id;
			}

			/**
			 * This function is used to create renewal order.
			 *
			 * @name mwb_sfw_create_renewal_order_for_paypal
			 * @param int $subscription_id subscription_id.
			 * @since    1.0.1
			 */
			private function mwb_sfw_create_renewal_order_for_paypal( $subscription_id ) {
				$mwb_renew_order = false;
				WC_Gateway_Paypal::log( 'MWB - Renewal Order result' . $subscription_id );
				if ( mwb_sfw_check_valid_subscription( $subscription_id ) ) {
					WC_Gateway_Paypal::log( 'MWB - Renewal Order result1:' . $subscription_id );
					$current_time = current_time( 'timestamp' );
					$subscription = get_post( $subscription_id );
					// show the data in log file.
					WC_Gateway_Paypal::log( 'MWB - Renewal Order subscription1:' . wc_print_r( $subscription, true ) );
					// show the data in log file.
					WC_Gateway_Paypal::log( 'MWB - Renewal Order subscription2:' . wc_print_r( $subscription, true ) );
					$parent_order_id  = $subscription->mwb_parent_order;
					WC_Gateway_Paypal::log( 'MWB - Renewal Order result parent order:' . $parent_order_id );
					$parent_order = wc_get_order( $parent_order_id );
					$billing_details = $parent_order->get_address( 'billing' );
					$shipping_details = $parent_order->get_address( 'shipping' );

					$new_status = 'wc-mwb_renewal';

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

					$_product = wc_get_product( $product_id );

					$total = 0;
					$tax_total = 0;
					$variations = array();

					$item_id = $mwb_new_order->add_product(
						$_product,
						$product_qty
					);
					$mwb_new_order->update_taxes();
					$mwb_new_order->calculate_totals();
					$order_id = $mwb_new_order->get_id();
					WC_Gateway_Paypal::log( 'MWB - Renewal Order result order_id:' . $order_id );
					update_post_meta( $order_id, '_payment_method', $payment_method );
					update_post_meta( $order_id, '_payment_method_title', $payment_method_title );

					$mwb_new_order->set_address( $billing_details, 'billing' );
					$mwb_new_order->set_address( $shipping_details, 'shipping' );
					update_post_meta( $order_id, 'mwb_sfw_renewal_order', 'yes' );
					update_post_meta( $order_id, 'mwb_sfw_subscription', $subscription_id );
					update_post_meta( $order_id, 'mwb_sfw_parent_order_id', $parent_order_id );

					do_action( 'mwb_sfw_renewal_order_creation', $mwb_new_order, $subscription_id );

					/*if trial period enable*/
					if ( '' == $mwb_old_payment_method ) {
						$parent_order_id = $subscription_id;
					}
					/*update next payment date*/
					$mwb_next_payment_date = mwb_sfw_next_payment_date( $subscription_id, $current_time, 0 );

					update_post_meta( $subscription_id, 'mwb_next_payment_date', $mwb_next_payment_date );
					do_action( 'mwb_sfw_other_payment_gateway_renewal', $mwb_new_order, $susbcription_id, $payment_method );
					return $mwb_new_order;
				}
				WC_Gateway_Paypal::log( 'MWB - Renewal Order result  failed:' . $subscription_id );
				return $mwb_renew_order;
			}
	}
}
