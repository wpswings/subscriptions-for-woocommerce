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



