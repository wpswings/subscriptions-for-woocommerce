<?php
/**
 * Exit if accessed directly
 *
 * @since      1.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mwb_sfw_get_the_wordpress_date_format' ) ) {

	/**
	 * This function is used to get date format.
	 *
	 * @name mwb_sfw_get_the_wordpress_date_format
	 * @since 1.0.0
	 * @param int $saved_date saved_date.
	 */
	function mwb_sfw_get_the_wordpress_date_format( $saved_date ) {
		$return_date = '---';
		if ( isset( $saved_date ) && ! empty( $saved_date ) ) {

			$date_format = get_option( 'date_format', 'Y-m-d' );
			$time_format = get_option( 'time_format', 'g:i a' );
			$wp_date = date_i18n( $date_format, $saved_date );
			$wp_time = date_i18n( $time_format, $saved_date );
			$return_date = $wp_date . ' ' . $wp_time;
		}

		return $return_date;
	}
}

if ( ! function_exists( 'mwb_sfw_next_payment_date' ) ) {

	/**
	 * This function is used to get next date.
	 *
	 * @name mwb_sfw_next_payment_date
	 * @since 1.0.0
	 * @param int    $subscription_id subscription_id.
	 * @param int    $current_time current_time.
	 * @param string $mwb_susbcription_trial_end mwb_susbcription_trial_end.
	 */
	function mwb_sfw_next_payment_date( $subscription_id, $current_time, $mwb_susbcription_trial_end ) {

		$mwb_sfw_next_pay_date = 0;
		$mwb_recurring_number = get_post_meta( $subscription_id, 'mwb_sfw_subscription_number', true );
		$mwb_recurring_interval = get_post_meta( $subscription_id, 'mwb_sfw_subscription_interval', true );

		if ( 0 != $mwb_susbcription_trial_end ) {

			$mwb_sfw_next_pay_date = $mwb_susbcription_trial_end;
		} else {
			$mwb_sfw_next_pay_date = mwb_sfw_susbcription_calculate_time( $current_time, $mwb_recurring_number, $mwb_recurring_interval );
		}
		return $mwb_sfw_next_pay_date;
	}
}

if ( ! function_exists( 'mwb_sfw_susbcription_expiry_date' ) ) {

	/**
	 * This function is used to get expiry date.
	 *
	 * @name mwb_sfw_susbcription_expiry_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 * @param int $trial_end trial_end.
	 */
	function mwb_sfw_susbcription_expiry_date( $subscription_id, $current_time, $trial_end = 0 ) {
		$mwb_sfw_expiry_date = 0;
		$expiry_number = get_post_meta( $subscription_id, 'mwb_sfw_subscription_expiry_number', true );
		$expiry_interval = get_post_meta( $subscription_id, 'mwb_sfw_subscription_expiry_interval', true );

		if ( isset( $expiry_number ) && ! empty( $expiry_number ) ) {
			if ( 0 != $trial_end ) {
				$mwb_sfw_expiry_date = mwb_sfw_susbcription_calculate_time( $trial_end, $expiry_number, $expiry_interval );
			} else {
				$mwb_sfw_expiry_date = mwb_sfw_susbcription_calculate_time( $current_time, $expiry_number, $expiry_interval );
			}
		}
		return $mwb_sfw_expiry_date;
	}
}

if ( ! function_exists( 'mwb_sfw_susbcription_trial_date' ) ) {

	/**
	 * This function is used to get trial date.
	 *
	 * @name mwb_sfw_susbcription_trial_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 */
	function mwb_sfw_susbcription_trial_date( $subscription_id, $current_time ) {
		$mwb_sfw_trial_date = 0;
		$trial_number = get_post_meta( $subscription_id, 'mwb_sfw_subscription_free_trial_number', true );
		$trial_interval = get_post_meta( $subscription_id, 'mwb_sfw_subscription_free_trial_interval', true );

		if ( isset( $trial_number ) && ! empty( $trial_number ) ) {
			$mwb_sfw_trial_date = mwb_sfw_susbcription_calculate_time( $current_time, $trial_number, $trial_interval );

		}

		return $mwb_sfw_trial_date;
	}
}

if ( ! function_exists( 'mwb_sfw_susbcription_calculate_time' ) ) {

	/**
	 * This function is used to calculate time.
	 *
	 * @name mwb_sfw_susbcription_calculate_time
	 * @since 1.0.0
	 * @param int    $mwb_curr_time mwb_curr_time.
	 * @param int    $mwb_interval_count mwb_interval_count.
	 * @param string $mwb_interval mwb_interval.
	 */
	function mwb_sfw_susbcription_calculate_time( $mwb_curr_time, $mwb_interval_count, $mwb_interval ) {

		$mwb_next_date = 0;
		switch ( $mwb_interval ) {
			case 'day':
				$mwb_next_date = mwb_sfw_get_timestamp( $mwb_curr_time, intval( $mwb_interval_count ) );
				break;
			case 'week':
				$mwb_next_date = mwb_sfw_get_timestamp( $mwb_curr_time, intval( $mwb_interval_count ) * 7 );
				break;
			case 'month':
				$mwb_next_date = mwb_sfw_get_timestamp( $mwb_curr_time, 0, intval( $mwb_interval_count ) );
				break;
			case 'year':
				$mwb_next_date = mwb_sfw_get_timestamp( $mwb_curr_time, 0, 0, intval( $mwb_interval_count ) );
				break;
			default:
		}

		return $mwb_next_date;
	}
}

if ( ! function_exists( 'mwb_sfw_get_timestamp' ) ) {
	/**
	 * This function is used to get timestamp.
	 *
	 * @name mwb_sfw_get_timestamp
	 * @since 1.0.0
	 * @param int $mwb_curr_time mwb_curr_time.
	 * @param int $mwb_days mwb_days.
	 * @param int $mwb_months mwb_months.
	 * @param int $mwb_years mwb_years.
	 */
	function mwb_sfw_get_timestamp( $mwb_curr_time, $mwb_days = 0, $mwb_months = 0, $mwb_years = 0 ) {

		$mwb_curr_time = strtotime( '+' . $mwb_days . ' days', $mwb_curr_time );
		$mwb_curr_time = strtotime( '+' . $mwb_months . ' month', $mwb_curr_time );
		$mwb_curr_time = strtotime( '+' . $mwb_years . ' year', $mwb_curr_time );
		return $mwb_curr_time;
	}
}

if ( ! function_exists( 'mwb_sfw_check_valid_subscription' ) ) {
	/**
	 * This function is used to check susbcription post type.
	 *
	 * @name mwb_sfw_check_valid_subscription
	 * @since 1.0.0
	 * @param int $mwb_subscription_id mwb_subscription_id.
	 */
	function mwb_sfw_check_valid_subscription( $mwb_subscription_id ) {
		$mwb_is_subscription = false;

		if ( isset( $mwb_subscription_id ) && ! empty( $mwb_subscription_id ) ) {
			if ( 'mwb_subscriptions' == get_post_type( absint( $mwb_subscription_id ) ) ) {
				$mwb_is_subscription = true;
			}
		}
		return $mwb_is_subscription;
	}
}

if ( ! function_exists( 'mwb_sfw_update_meta_key_for_susbcription' ) ) {
	/**
	 * This function is used to check susbcription post type.
	 *
	 * @name mwb_sfw_update_meta_key_for_susbcription
	 * @since 1.0.0
	 * @param int   $subscription_id subscription_id.
	 * @param array $mwb_args mwb_args.
	 */
	function mwb_sfw_update_meta_key_for_susbcription( $subscription_id, $mwb_args ) {
		if ( isset( $mwb_args ) && ! empty( $mwb_args ) && is_array( $mwb_args ) ) {
			foreach ( $mwb_args as $key => $value ) {
				update_post_meta( $subscription_id, $key, $value );
			}
		}
	}
}

if ( ! function_exists( 'mwb_sfw_send_email_for_renewal_susbcription' ) ) {
	/**
	 * This function is used to send renewal email.
	 *
	 * @name mwb_sfw_send_email_for_renewal_susbcription
	 * @since 1.0.0
	 * @param int $order_id order_id.
	 */
	function mwb_sfw_send_email_for_renewal_susbcription( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( isset( $order ) && is_object( $order ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "processing" notification.
			if ( isset( $mailer['WC_Email_New_Order'] ) ) {
				 $mailer['WC_Email_New_Order']->trigger( $order_id );
			}
			if ( $order->get_status() == 'processing' ) {
				if ( isset( $mailer['WC_Email_Customer_Processing_Order'] ) ) {
					 $mailer['WC_Email_Customer_Processing_Order']->trigger( $order_id );

				}
			}
		}
	}
}

if ( ! function_exists( 'mwb_sfw_send_email_for_cancel_susbcription' ) ) {
	/**
	 * This function is used to send cancel email.
	 *
	 * @name mwb_sfw_send_email_for_cancel_susbcription
	 * @since 1.0.0
	 * @param int $mwb_subscription_id mwb_subscription_id.
	 */
	function mwb_sfw_send_email_for_cancel_susbcription( $mwb_subscription_id ) {

		if ( isset( $mwb_subscription_id ) && ! empty( $mwb_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "cancel" notification.
			if ( isset( $mailer['mwb_sfw_cancel_subscription'] ) ) {
				 $mailer['mwb_sfw_cancel_subscription']->trigger( $mwb_subscription_id );
			}
		}
	}
}

if ( ! function_exists( 'mwb_sfw_send_email_for_expired_susbcription' ) ) {
	/**
	 * This function is used to send expired email.
	 *
	 * @name mwb_sfw_send_email_for_expired_susbcription
	 * @since 1.0.0
	 * @param int $mwb_subscription_id mwb_subscription_id.
	 */
	function mwb_sfw_send_email_for_expired_susbcription( $mwb_subscription_id ) {

		if ( isset( $mwb_subscription_id ) && ! empty( $mwb_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "expired" notification.
			if ( isset( $mailer['mwb_sfw_expired_subscription'] ) ) {
				 $mailer['mwb_sfw_expired_subscription']->trigger( $mwb_subscription_id );
			}
		}
	}
}


if ( ! function_exists( 'mwb_sfw_email_subscriptions_details' ) ) {
	/**
	 * This function is used to create html for susbcription details.
	 *
	 * @name mwb_sfw_email_subscriptions_details
	 * @since 1.0.0
	 * @param int $mwb_subscription_id mwb_subscription_id.
	 */
	function mwb_sfw_email_subscriptions_details( $mwb_subscription_id ) {
		$mwb_text_align = is_rtl() ? 'right' : 'left';

		?>
		<div style="margin-bottom: 40px;">
			<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
				<thead>
					<tr>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $mwb_text_align ); ?>;"><?php esc_html_e( 'Product', 'subscriptions-for-woocommerce' ); ?></th>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $mwb_text_align ); ?>;"><?php esc_html_e( 'Quantity', 'subscriptions-for-woocommerce' ); ?></th>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $mwb_text_align ); ?>;"><?php esc_html_e( 'Price', 'subscriptions-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<?php
								$mwb_product_name = get_post_meta( $mwb_subscription_id, 'product_name', true );
								echo esc_html( $mwb_product_name );
							?>
						 </td>
						<td>
							<?php
							$product_qty = get_post_meta( $mwb_subscription_id, 'product_qty', true );
							echo esc_html( $product_qty );
							?>
						</td>
						<td>
						<?php
							do_action( 'mwb_sfw_display_susbcription_recerring_total_account_page', $mwb_subscription_id );
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}

if ( ! function_exists( 'mwb_sfw_check_plugin_enable' ) ) {
	/**
	 * This function is used to check plugin is enable.
	 *
	 * @name mwb_sfw_check_plugin_enable
	 * @since 1.0.0
	 */
	function mwb_sfw_check_plugin_enable() {
		$is_enable = false;
		$mwb_sfw_enable_plugin = get_option( 'mwb_sfw_enable_plugin', '' );
		if ( 'on' == $mwb_sfw_enable_plugin ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'mwb_sfw_validate_payment_request' ) ) {
	/**
	 * This function is used to check plugin is enable.
	 *
	 * @name mwb_sfw_check_plugin_enable
	 * @param Object $mwb_subscription mwb_subscription.
	 * @since 1.0.0
	 */
	function mwb_sfw_validate_payment_request( $mwb_subscription ) {
		$result = true;
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$mwb_nonce = isset( $_GET['_mwb_sfw_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_mwb_sfw_nonce'] ) ) : '';
		if ( wp_verify_nonce( $mwb_nonce ) === false ) {
			$result = false;
			wc_add_notice( __( 'There was an error with your request.', 'subscriptions-for-woocommerce' ), 'error' );
		} elseif ( empty( $mwb_subscription ) ) {
			$result = false;
			wc_add_notice( __( 'Invalid Subscription.', 'subscriptions-for-woocommerce' ), 'error' );
		} elseif ( $mwb_subscription->get_order_key() !== $order_key ) {
			$result = false;
			wc_add_notice( __( 'Invalid susbcription order.', 'subscriptions-for-woocommerce' ), 'error' );
		}
		return $result;
	}
}

if ( ! function_exists( 'mwb_sfw_get_page_screen' ) ) {
	/**
	 * This function is used to get current screen.
	 *
	 * @name mwb_sfw_get_page_screen
	 * @since 1.0.0
	 */
	function mwb_sfw_get_page_screen() {

		$mwb_screen_id = sanitize_title( 'MakeWebBetter' );
		$screen_ids   = array(
			'toplevel_page_' . $mwb_screen_id,
			$mwb_screen_id . '_page_subscriptions_for_woocommerce_menu',
		);

		return apply_filters( 'mwb_sfw_page_screen', $screen_ids );
	}
}

if ( ! function_exists( 'mwb_sfw_check_product_is_subscription' ) ) {
	/**
	 * This function is used to check susbcripton product.
	 *
	 * @name mwb_sfw_check_product_is_subscription
	 * @since 1.0.0
	 */
	function mwb_sfw_check_product_is_subscription( $product ) {

		$mwb_is_subscription = false;
		if ( is_object( $product ) ) {
			$product_id = $product->get_id();
			$mwb_subscription_product = get_post_meta( $product_id, '_mwb_sfw_product', true );
			if ( 'yes' === $mwb_subscription_product ) {
				$mwb_is_subscription = true;
			}
		}

		return apply_filters( 'mwb_sfw_check_subscription_product_type', $mwb_is_subscription, $product );
	}
}

if ( ! function_exists( 'mwb_sfw_get_reccuring_time_interval_for_paypal' ) ) {
	/**
	 * This function is used to get time interval for paypal.
	 *
	 * @name mwb_sfw_get_reccuring_time_interval_for_paypal
	 * @since 1.0.0
	 */
	function mwb_sfw_get_reccuring_time_interval_for_paypal( $mwb_reccuring_period ) {
		$mwb_converted_period = 'D';
		switch ( strtolower( $mwb_reccuring_period ) ) {
			case 'day':
				$mwb_converted_period = 'D';
				break;
			case 'week':
				$mwb_converted_period = 'W';
				break;
			case 'month':
				$mwb_converted_period = 'M';
				break;
			case 'year':
			default:
				$mwb_converted_period = 'Y';
				break;
		}

		return $mwb_converted_period;
	}
}

if ( ! function_exists( 'mwb_sfw_create_renewal_order_for_paypal' ) ) { 
	/**
	 * This function is used to create renewal order.
	 *
	 * @name mwb_sfw_create_renewal_order_for_paypal
	 * @since 1.0.0
	 */
	function mwb_sfw_create_renewal_order_for_paypal( $subscription_id ) {
		$mwb_renew_order = false;
		if ( mwb_sfw_check_valid_subscription( $susbcription_id ) ) {
			$current_time = current_time( 'timestamp' );
			$subscription = get_post( $susbcription_id );
			$parent_order_id  = $subscription->mwb_parent_order;
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
			update_post_meta( $order_id, '_payment_method', $payment_method );
			update_post_meta( $order_id, '_payment_method_title', $payment_method_title );

			$mwb_new_order->set_address( $billing_details, 'billing' );
			$mwb_new_order->set_address( $shipping_details, 'shipping' );
			update_post_meta( $order_id, 'mwb_sfw_renewal_order', 'yes' );
			update_post_meta( $order_id, 'mwb_sfw_subscription', $susbcription_id );
			update_post_meta( $order_id, 'mwb_sfw_parent_order_id', $parent_order_id );

			/*if trial period enable*/
			if ( '' == $mwb_old_payment_method ) {
				$parent_order_id = $susbcription_id;
			}
			/*update next payment date*/
			$mwb_next_payment_date = mwb_sfw_next_payment_date( $susbcription_id, $current_time, 0 );

			update_post_meta( $susbcription_id, 'mwb_next_payment_date', $mwb_next_payment_date );

			return $mwb_new_order;
		}

		return $mwb_renew_order;
	}
}
if ( ! function_exists( 'mwb_sfw_subscription_period' ) ) {

/**
	 * This function is used to add subscription intervals.
	 *
	 * @name mwb_sfw_subscription_period
	 * @since    1.0.0
	 * @return   Array  $subscription_interval
	 */
	function mwb_sfw_subscription_period() {
		$subscription_interval = array(
			'day' => __( 'Days', 'subscriptions-for-woocommerce' ),
			'week' => __( 'Weeks', 'subscriptions-for-woocommerce' ),
			'month' => __( 'Months', 'subscriptions-for-woocommerce' ),
			'year' => __( 'Years', 'subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'mwb_sfw_subscription_intervals', $subscription_interval );
	}
}

if ( ! function_exists( 'mwb_sfw_subscription_expiry_period' ) ) {

	/**
	 * This function is used to add subscription intervals for expiry.
	 *
	 * @name mwb_sfw_subscription_expiry_period
	 * @since    1.0.0
	 * @param   string $mwb_sfw_subscription_interval mwb_sfw_subscription_interval.
	 */
	function mwb_sfw_subscription_expiry_period( $mwb_sfw_subscription_interval ) {

		$subscription_interval = array(
			'day' => __( 'Days', 'subscriptions-for-woocommerce' ),
			'week' => __( 'Weeks', 'subscriptions-for-woocommerce' ),
			'month' => __( 'Months', 'subscriptions-for-woocommerce' ),
			'year' => __( 'Years', 'subscriptions-for-woocommerce' ),
		);
		if ('day' == $mwb_sfw_subscription_interval ) {
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['month'] );
			unset( $subscription_interval['year'] );
		}
		elseif ( 'week' == $mwb_sfw_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['month'] );
			unset( $subscription_interval['year'] );

		} elseif ( 'month' == $mwb_sfw_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['year'] );

		} elseif ( 'year' == $mwb_sfw_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['month'] );
		}
		return apply_filters( 'mwb_sfw_subscription_expiry_intervals', $subscription_interval );
	}
}



if ( ! function_exists( 'mwb_sfw_get_time_interval' ) ) {
	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name mwb_sfw_get_time_interval
	 * @param int    $mwb_sfw_subscription_number Subscription inteval number.
	 * @param string $mwb_sfw_subscription_interval Subscription Interval .
	 * @since    1.0.0
	 */
	function mwb_sfw_get_time_interval( $mwb_sfw_subscription_number, $mwb_sfw_subscription_interval ) {

		$mwb_price_html = '';
		switch ( $mwb_sfw_subscription_interval ) {
			case 'day':
				/* translators: %s: search term */
				$mwb_price_html = sprintf( _n( '%s Day', '%s Days', $mwb_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'week':
				/* translators: %s: search term */
				$mwb_price_html = sprintf( _n( '%s Week', '%s Weeks', $mwb_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'month':
				/* translators: %s: search term */
				$mwb_price_html = sprintf( _n( '%s Month', '%s Months', $mwb_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'year':
				/* translators: %s: search term */
				$mwb_price_html = sprintf( _n( '%s Year', '%s Years', $mwb_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
		}
		return apply_filters( 'mwb_sfw_display_time_interval', $mwb_price_html );

	}
}
if ( ! function_exists( 'mwb_sfw_get_time_interval_for_price' ) ) {
	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name mwb_sfw_get_time_interval_for_price
	 * @param int    $mwb_sfw_subscription_number Subscription inteval number.
	 * @param string $mwb_sfw_subscription_interval Subscription Interval .
	 * @since    1.0.0
	 */
	function mwb_sfw_get_time_interval_for_price( $mwb_sfw_subscription_number, $mwb_sfw_subscription_interval ) {
		$mwb_number = $mwb_sfw_subscription_number;
		if ( 1 == $mwb_sfw_subscription_number ) {
			$mwb_sfw_subscription_number = '';
		}

		$mwb_price_html = '';
		switch ( $mwb_sfw_subscription_interval ) {
			case 'day':
				/* translators: %s: search term */
				$mwb_price_html = sprintf( _n( '%s Day', '%s Days', $mwb_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'week':
				/* translators: %s: search term */
				$mwb_price_html = sprintf( _n( '%s Week', '%s Weeks', $mwb_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'month':
				/* translators: %s: search term */
				$mwb_price_html = sprintf( _n( '%s Month', '%s Months', $mwb_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
			case 'year':
				/* translators: %s: search term */
				$mwb_price_html = sprintf( _n( '%s Year', '%s Years', $mwb_number, 'subscriptions-for-woocommerce' ), $mwb_sfw_subscription_number );
				break;
		}
		return $mwb_price_html;

	}
}