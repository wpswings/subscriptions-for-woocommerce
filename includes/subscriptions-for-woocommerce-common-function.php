<?php
/**
 * Exit if accessed directly
 *
 * @since      1.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wps_sfw_get_the_wordpress_date_format' ) ) {

	/**
	 * This function is used to get date format.
	 *
	 * @name wps_sfw_get_the_wordpress_date_format
	 * @since 1.0.0
	 * @param int $saved_date saved_date.
	 */
	function wps_sfw_get_the_wordpress_date_format( $saved_date ) {
		$return_date = '---';
		if ( isset( $saved_date ) && ! empty( $saved_date ) ) {

			$date_format = get_option( 'date_format', 'Y-m-d' );
			$time_format = get_option( 'time_format', 'g:i a' );
			$wp_date = date_i18n( $date_format, $saved_date );

			$return_date = $wp_date;
		}

		return $return_date;
	}
}

if ( ! function_exists( 'wps_sfw_next_payment_date' ) ) {

	/**
	 * This function is used to get next date.
	 *
	 * @name wps_sfw_next_payment_date
	 * @since 1.0.0
	 * @param int    $subscription_id subscription_id.
	 * @param int    $current_time current_time.
	 * @param string $wps_susbcription_trial_end wps_susbcription_trial_end.
	 */
	function wps_sfw_next_payment_date( $subscription_id, $current_time, $wps_susbcription_trial_end ) {

		$wps_sfw_next_pay_date = 0;
		$wps_recurring_number = (int) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_number', true );
		$wps_recurring_interval = (string) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_interval', true );

		if ( 0 != $wps_susbcription_trial_end ) {

			$wps_sfw_next_pay_date = $wps_susbcription_trial_end;
		} else {
			$wps_sfw_next_pay_date = wps_sfw_susbcription_calculate_time( $current_time, $wps_recurring_number, $wps_recurring_interval );
		}
		return $wps_sfw_next_pay_date;
	}
}

if ( ! function_exists( 'wps_sfw_susbcription_expiry_date' ) ) {

	/**
	 * This function is used to get expiry date.
	 *
	 * @name wps_sfw_susbcription_expiry_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 * @param int $trial_end trial_end.
	 */
	function wps_sfw_susbcription_expiry_date( $subscription_id, $current_time, $trial_end = 0 ) {
		$wps_sfw_expiry_date = 0;
		$expiry_number = (int) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_expiry_number', true );
		$expiry_interval = (string) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_expiry_interval', true );
		if ( isset( $expiry_number ) && ! empty( $expiry_number ) ) {
			if ( 0 != $trial_end ) {
				$wps_sfw_expiry_date = wps_sfw_susbcription_calculate_time( $trial_end, $expiry_number, $expiry_interval );
			} else {
				$wps_sfw_expiry_date = wps_sfw_susbcription_calculate_time( $current_time, $expiry_number, $expiry_interval );
			}
		}
		return $wps_sfw_expiry_date;
	}
}

if ( ! function_exists( 'wps_sfw_susbcription_trial_date' ) ) {

	/**
	 * This function is used to get trial date.
	 *
	 * @name wps_sfw_susbcription_trial_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 */
	function wps_sfw_susbcription_trial_date( $subscription_id, $current_time ) {
		$wps_sfw_trial_date = 0;
		$trial_number = (int) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_free_trial_number', true );
		$trial_interval = (string) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_free_trial_interval', true );

		if ( isset( $trial_number ) && ! empty( $trial_number ) ) {
			$wps_sfw_trial_date = wps_sfw_susbcription_calculate_time( $current_time, $trial_number, $trial_interval );

		}

		return $wps_sfw_trial_date;
	}
}

if ( ! function_exists( 'wps_sfw_susbcription_calculate_time' ) ) {

	/**
	 * This function is used to calculate time.
	 *
	 * @name wps_sfw_susbcription_calculate_time
	 * @since 1.0.0
	 * @param int    $wps_curr_time wps_curr_time.
	 * @param int    $wps_interval_count wps_interval_count.
	 * @param string $wps_interval wps_interval.
	 */
	function wps_sfw_susbcription_calculate_time( $wps_curr_time, $wps_interval_count, $wps_interval ) {

		$wps_next_date = 0;
		switch ( $wps_interval ) {
			case 'day':
				$wps_next_date = wps_sfw_get_timestamp( $wps_curr_time, $wps_interval_count );
				break;
			case 'week':
				$wps_next_date = wps_sfw_get_timestamp( $wps_curr_time, $wps_interval_count * 7 );
				break;
			case 'month':
				$wps_next_date = wps_sfw_get_timestamp( $wps_curr_time, 0, $wps_interval_count );
				break;
			case 'year':
				$wps_next_date = wps_sfw_get_timestamp( $wps_curr_time, 0, 0, $wps_interval_count );
				break;
			default:
		}

		return $wps_next_date;
	}
}

if ( ! function_exists( 'wps_sfw_get_timestamp' ) ) {
	/**
	 * This function is used to get timestamp.
	 *
	 * @name wps_sfw_get_timestamp
	 * @since 1.0.0
	 * @param int $wps_curr_time wps_curr_time.
	 * @param int $wps_days wps_days.
	 * @param int $wps_months wps_months.
	 * @param int $wps_years wps_years.
	 */
	function wps_sfw_get_timestamp( $wps_curr_time, $wps_days = 0, $wps_months = 0, $wps_years = 0 ) {

		if ( $wps_days ) {
			$wps_curr_time = strtotime( '+' . (int) $wps_days . ' days', $wps_curr_time );
		} elseif ( $wps_months ) {
			$wps_curr_time = strtotime( '+' . (int) $wps_months . ' month', $wps_curr_time );
		} elseif ( $wps_years ) {
			$wps_curr_time = strtotime( '+' . (int) $wps_years . ' year', $wps_curr_time );
		}
		return $wps_curr_time;
	}
}

if ( ! function_exists( 'wps_sfw_check_valid_subscription' ) ) {
	/**
	 * This function is used to check susbcription post type.
	 *
	 * @name wps_sfw_check_valid_subscription
	 * @since 1.0.0
	 * @param int $wps_subscription_id wps_subscription_id.
	 */
	function wps_sfw_check_valid_subscription( $wps_subscription_id ) {
		$wps_is_subscription = false;

		if ( isset( $wps_subscription_id ) && ! empty( $wps_subscription_id ) ) {
			if ( 'shop_order_placehold' === get_post_type( absint( $wps_subscription_id ) ) || 'wps_subscriptions' == get_post_type( absint( $wps_subscription_id ) ) ) {
				$wps_is_subscription = true;
			}
		}
		return $wps_is_subscription;
	}
}

if ( ! function_exists( 'wps_sfw_update_meta_key_for_susbcription' ) ) {
	/**
	 * This function is used to check susbcription post type.
	 *
	 * @name wps_sfw_update_meta_key_for_susbcription
	 * @since 1.0.0
	 * @param int   $subscription_id subscription_id.
	 * @param array $wps_args wps_args.
	 */
	function wps_sfw_update_meta_key_for_susbcription( $subscription_id, $wps_args ) {
		if ( isset( $wps_args ) && ! empty( $wps_args ) && is_array( $wps_args ) ) {
			foreach ( $wps_args as $key => $value ) {
				wps_sfw_update_meta_data( $subscription_id, $key, $value );
			}
		}
	}
}

if ( ! function_exists( 'wps_sfw_send_email_for_renewal_susbcription' ) ) {
	/**
	 * This function is used to send renewal email.
	 *
	 * @name wps_sfw_send_email_for_renewal_susbcription
	 * @since 1.0.0
	 * @param int $order_id order_id.
	 */
	function wps_sfw_send_email_for_renewal_susbcription( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( isset( $order ) && is_object( $order ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "processing" notification.
			if ( isset( $mailer['WC_Email_New_Order'] ) ) {
				 $mailer['WC_Email_New_Order']->trigger( $order_id );
			}
			do_action( 'wps_sfw_renewal_email_notification', $order, $mailer );
		}
	}
}

if ( ! function_exists( 'wps_sfw_send_email_for_cancel_susbcription' ) ) {
	/**
	 * This function is used to send cancel email.
	 *
	 * @name wps_sfw_send_email_for_cancel_susbcription
	 * @since 1.0.0
	 * @param int $wps_subscription_id wps_subscription_id.
	 */
	function wps_sfw_send_email_for_cancel_susbcription( $wps_subscription_id ) {

		if ( isset( $wps_subscription_id ) && ! empty( $wps_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "cancel" notification.
			if ( isset( $mailer['wps_sfw_cancel_subscription'] ) ) {
				 $mailer['wps_sfw_cancel_subscription']->trigger( $wps_subscription_id );
			}
		}
	}
}

if ( ! function_exists( 'wps_sfw_send_email_for_expired_susbcription' ) ) {
	/**
	 * This function is used to send expired email.
	 *
	 * @name wps_sfw_send_email_for_expired_susbcription
	 * @since 1.0.0
	 * @param int $wps_subscription_id wps_subscription_id.
	 */
	function wps_sfw_send_email_for_expired_susbcription( $wps_subscription_id ) {

		if ( isset( $wps_subscription_id ) && ! empty( $wps_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "expired" notification.
			if ( isset( $mailer['wps_sfw_expired_subscription'] ) ) {
				 $mailer['wps_sfw_expired_subscription']->trigger( $wps_subscription_id );
			}
		}
	}
}


if ( ! function_exists( 'wps_sfw_email_subscriptions_details' ) ) {
	/**
	 * This function is used to create html for susbcription details.
	 *
	 * @name wps_sfw_email_subscriptions_details
	 * @since 1.0.0
	 * @param int $wps_subscription_id wps_subscription_id.
	 */
	function wps_sfw_email_subscriptions_details( $wps_subscription_id ) {
		$wps_text_align = is_rtl() ? 'right' : 'left';

		?>
		<div style="margin-bottom: 40px;">
			<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
				<thead>
					<tr>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $wps_text_align ); ?>;"><?php esc_html_e( 'Product', 'subscriptions-for-woocommerce' ); ?></th>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $wps_text_align ); ?>;"><?php esc_html_e( 'Quantity', 'subscriptions-for-woocommerce' ); ?></th>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $wps_text_align ); ?>;"><?php esc_html_e( 'Price', 'subscriptions-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<?php
								$wps_product_name = wps_sfw_get_meta_data( $wps_subscription_id, 'product_name', true );
								echo esc_html( $wps_product_name );
							?>
						 </td>
						<td>
							<?php
							$product_qty = wps_sfw_get_meta_data( $wps_subscription_id, 'product_qty', true );
							echo esc_html( $product_qty );
							?>
						</td>
						<td>
						<?php
							do_action( 'wps_sfw_display_susbcription_recerring_total_account_page', $wps_subscription_id );
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}

if ( ! function_exists( 'wps_sfw_check_plugin_enable' ) ) {
	/**
	 * This function is used to check plugin is enable.
	 *
	 * @name wps_sfw_check_plugin_enable
	 * @since 1.0.0
	 */
	function wps_sfw_check_plugin_enable() {
		$is_enable = false;
		$wps_sfw_enable_plugin = get_option( 'wps_sfw_enable_plugin', '' );
		if ( 'on' == $wps_sfw_enable_plugin ) {
			$is_enable = true;
		}
		return $is_enable;
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
		$wps_sfw_enable_plugin = get_option( 'wps_sfw_enable_plugin', '' );
		if ( 'on' == $wps_sfw_enable_plugin ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}
if ( ! function_exists( 'wps_sfw_validate_payment_request' ) ) {
	/**
	 * This function is used to check plugin is enable.
	 *
	 * @name wps_sfw_check_plugin_enable
	 * @param Object $wps_subscription wps_subscription.
	 * @since 1.0.0
	 */
	function wps_sfw_validate_payment_request( $wps_subscription ) {
		$result = true;
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$wps_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( wp_verify_nonce( $wps_nonce ) === false ) {
			$result = false;
			wc_add_notice( __( 'There was an error with your request.', 'subscriptions-for-woocommerce' ), 'error' );
		} elseif ( empty( $wps_subscription ) ) {
			$result = false;
			wc_add_notice( __( 'Invalid Subscription.', 'subscriptions-for-woocommerce' ), 'error' );
		} elseif ( $wps_subscription->get_order_key() !== $order_key ) {
			$result = false;
			wc_add_notice( __( 'Invalid subscription order.', 'subscriptions-for-woocommerce' ), 'error' );
		}
		return $result;
	}
}

if ( ! function_exists( 'wps_sfw_get_page_screen' ) ) {
	/**
	 * This function is used to get current screen.
	 *
	 * @name wps_sfw_get_page_screen
	 * @since 1.0.0
	 */
	function wps_sfw_get_page_screen() {

		$wps_screen_id = sanitize_title( 'WP Swings' );
		$screen_ids   = array(
			'toplevel_page_' . $wps_screen_id,
			$wps_screen_id . '_page_subscriptions_for_woocommerce_menu',
		);

		return apply_filters( 'wps_sfw_page_screen', $screen_ids );
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

		$wps_screen_id = sanitize_title( 'WP Swings' );
		$screen_ids   = array(
			'toplevel_page_' . $wps_screen_id,
			$wps_screen_id . '_page_subscriptions_for_woocommerce_menu',
		);

		return apply_filters( 'wps_sfw_page_screen', $screen_ids );
	}
}

if ( ! function_exists( 'wps_sfw_check_product_is_subscription' ) ) {
	/**
	 * This function is used to check susbcripton product.
	 *
	 * @name wps_sfw_check_product_is_subscription
	 * @param Object $product product.
	 * @since 1.0.0
	 */
	function wps_sfw_check_product_is_subscription( $product ) {

		$wps_is_subscription = false;
		if ( is_object( $product ) ) {
			$product_id = $product->get_id();
			$wps_subscription_product = wps_sfw_get_meta_data( $product_id, '_wps_sfw_product', true );
			if ( 'yes' === $wps_subscription_product ) {
				$wps_is_subscription = true;
			} else if ( $product->get_type() == 'subscription_box' ) {
				// subscription box.
				$wps_is_subscription = true;

				// subscription box.
			}
		}

		return apply_filters( 'wps_sfw_check_subscription_product_type', $wps_is_subscription, $product );
	}
}

if ( ! function_exists( 'wps_sfw_subscription_period' ) ) {

	/**
	 * This function is used to add subscription intervals.
	 *
	 * @name wps_sfw_subscription_period
	 * @since    1.0.0
	 * @return   Array  $subscription_interval
	 */
	function wps_sfw_subscription_period() {
		$subscription_interval = array(
			'day' => __( 'Days', 'subscriptions-for-woocommerce' ),
			'week' => __( 'Weeks', 'subscriptions-for-woocommerce' ),
			'month' => __( 'Months', 'subscriptions-for-woocommerce' ),
			'year' => __( 'Years', 'subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'wps_sfw_subscription_intervals', $subscription_interval );
	}
}

if ( ! function_exists( 'wps_sfw_subscription_expiry_period' ) ) {

	/**
	 * This function is used to add subscription intervals for expiry.
	 *
	 * @name wps_sfw_subscription_expiry_period
	 * @since    1.0.0
	 * @param   string $wps_sfw_subscription_interval wps_sfw_subscription_interval.
	 */
	function wps_sfw_subscription_expiry_period( $wps_sfw_subscription_interval ) {

		$subscription_interval = array(
			'day' => __( 'Days', 'subscriptions-for-woocommerce' ),
			'week' => __( 'Weeks', 'subscriptions-for-woocommerce' ),
			'month' => __( 'Months', 'subscriptions-for-woocommerce' ),
			'year' => __( 'Years', 'subscriptions-for-woocommerce' ),
		);
		if ( 'day' == $wps_sfw_subscription_interval ) {
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['month'] );
			unset( $subscription_interval['year'] );
		} elseif ( 'week' == $wps_sfw_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['month'] );
			unset( $subscription_interval['year'] );

		} elseif ( 'month' == $wps_sfw_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['year'] );

		} elseif ( 'year' == $wps_sfw_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['month'] );
		}
		return apply_filters( 'wps_sfw_subscription_expiry_intervals', $subscription_interval );
	}
}



if ( ! function_exists( 'wps_sfw_get_time_interval' ) ) {
	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name wps_sfw_get_time_interval
	 * @param int    $wps_sfw_subscription_number Subscription inteval number.
	 * @param string $wps_sfw_subscription_interval Subscription Interval .
	 * @since    1.0.0
	 */
	function wps_sfw_get_time_interval( $wps_sfw_subscription_number, $wps_sfw_subscription_interval ) {
		$wps_sfw_subscription_number = (int) $wps_sfw_subscription_number;
		$wps_price_html = '';
		switch ( $wps_sfw_subscription_interval ) {
			case 'day':
				/* translators: %s: Day,%s: Days */
				$wps_price_html = sprintf( _n( '%s Day', '%s Days', $wps_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $wps_sfw_subscription_number );
				break;
			case 'week':
				/* translators: %s: Week,%s: Weeks */
				$wps_price_html = sprintf( _n( '%s Week', '%s Weeks', $wps_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $wps_sfw_subscription_number );
				break;
			case 'month':
				/* translators: %s: Month,%s: Months */
				$wps_price_html = sprintf( _n( '%s Month', '%s Months', $wps_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $wps_sfw_subscription_number );
				break;
			case 'year':
				/* translators: %s: Year,%s: Years */
				$wps_price_html = sprintf( _n( '%s Year', '%s Years', $wps_sfw_subscription_number, 'subscriptions-for-woocommerce' ), $wps_sfw_subscription_number );
				break;
		}
		return apply_filters( 'wps_sfw_display_time_interval', $wps_price_html );
	}
}
if ( ! function_exists( 'wps_sfw_get_time_interval_for_price' ) ) {
	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name wps_sfw_get_time_interval_for_price
	 * @param int    $wps_sfw_subscription_number Subscription inteval number.
	 * @param string $wps_sfw_subscription_interval Subscription Interval .
	 * @since    1.0.0
	 */
	function wps_sfw_get_time_interval_for_price( $wps_sfw_subscription_number, $wps_sfw_subscription_interval ) {
		$wps_number = (int) $wps_sfw_subscription_number;
		if ( 1 == $wps_sfw_subscription_number ) {
			$wps_sfw_subscription_number = '';
		}

		$wps_price_html = '';
		switch ( $wps_sfw_subscription_interval ) {
			case 'day':
				/* translators: %s: Day,%s: Days */
				$wps_price_html = sprintf( _n( '%s Day', '%s Days', $wps_number, 'subscriptions-for-woocommerce' ), $wps_sfw_subscription_number );
				break;
			case 'week':
				/* translators: %s: Week,%s: Weeks */
				$wps_price_html = sprintf( _n( '%s Week', '%s Weeks', $wps_number, 'subscriptions-for-woocommerce' ), $wps_sfw_subscription_number );
				break;
			case 'month':
				/* translators: %s: Month,%s: Months */
				$wps_price_html = sprintf( _n( '%s Month', '%s Months', $wps_number, 'subscriptions-for-woocommerce' ), $wps_sfw_subscription_number );
				break;
			case 'year':
				/* translators: %s: Year,%s: Years */
				$wps_price_html = sprintf( _n( '%s Year', '%s Years', $wps_number, 'subscriptions-for-woocommerce' ), $wps_sfw_subscription_number );
				break;
		}
		return $wps_price_html;
	}
}

if ( ! function_exists( 'wps_sfw_pro_active' ) ) {
	/**
	 * This function is used to check if premium plugin is activated.
	 *
	 * @since 1.0.0
	 * @name wps_sfw_pro_active
	 * @return boolean
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	function wps_sfw_pro_active() {
		return apply_filters( 'wps_wsp_pro_active', false );
	}
}

if ( ! function_exists( 'wps_sfw_delete_failed_subscription' ) ) {
	/**
	 * This function is used to delete faild subscription.
	 *
	 * @since 1.0.0
	 * @name wps_sfw_delete_failed_subscription
	 * @param int $order_id order_id.
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	function wps_sfw_delete_failed_subscription( $order_id ) {
		if ( isset( $order_id ) && ! empty( $order_id ) ) {

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
							'value' => array( 'pending', 'on-hold', 'failed' ),
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
							'value' => array( 'pending', 'on-hold', 'failed' ),
						),
					),
				);
				$wps_subscriptions = get_posts( $args );
			}

			if ( ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
				foreach ( $wps_subscriptions as $key => $value ) {
					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$subscription = new WPS_Subscription( $value );
						$subscription->delete( true );
					} else {
						wp_delete_post( $value->ID, true );
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'wps_sfw_include_process_directory' ) ) {
	/**
	 * This function is used to include payment file.
	 *
	 * @since 1.0.0
	 * @name wps_sfw_include_process_directory
	 * @param string $wps_sfw_dir wps_sfw_dir.
	 * @param string $wps_selected_dir wps_selected_dir.
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	function wps_sfw_include_process_directory( $wps_sfw_dir, $wps_selected_dir = '' ) {

		if ( is_dir( $wps_sfw_dir ) ) {
			$wps_dh = opendir( $wps_sfw_dir );

			if ( $wps_dh ) {

				while ( ( $wps_file = readdir( $wps_dh ) ) !== false ) {

					if ( '.' == $wps_file[0] ) {
						continue; // skip dirs . and .. by first char test.
					}

					if ( is_dir( $wps_sfw_dir . '/' . $wps_file ) ) {

						wps_sfw_include_process_directory( $wps_sfw_dir . '/' . $wps_file, $wps_file );

					} elseif ( 'class-wps-subscriptions-payment-' . $wps_selected_dir . '-main.php' == $wps_file ) {
						include $wps_sfw_dir . '/' . $wps_file;
					}
				}
				closedir( $wps_dh );
			}
		}
	}
}
if ( ! function_exists( 'mwb_sfw_include_process_directory' ) ) {
	/**
	 * This function is used to include payment file.
	 *
	 * @since 1.0.0
	 * @name mwb_sfw_include_process_directory
	 * @param string $wps_sfw_dir wps_sfw_dir.
	 * @param string $wps_selected_dir wps_selected_dir.
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	function mwb_sfw_include_process_directory( $wps_sfw_dir, $wps_selected_dir = '' ) {

		if ( is_dir( $wps_sfw_dir ) ) {
			$wps_dh = opendir( $wps_sfw_dir );
			if ( $wps_dh ) {

				while ( ( $wps_file = readdir( $wps_dh ) ) !== false ) {

					if ( '.' == $wps_file[0] ) {
						continue; // skip dirs . and .. by first char test.
					}

					if ( is_dir( $wps_sfw_dir . '/' . $wps_file ) ) {

						wps_sfw_include_process_directory( $wps_sfw_dir . '/' . $wps_file, $wps_file );

					} elseif ( 'class-wps-subscriptions-payment-' . $wps_selected_dir . '-main.php' == $wps_file ) {

						include $wps_sfw_dir . '/' . $wps_file;
					}
				}
				closedir( $wps_dh );
			}
		}
	}
}
if ( ! function_exists( 'wps_sfw_recerring_total_price_list_table_callback' ) ) {
	/**
	 * This function is used show recuring interval on list.
	 *
	 * @name wps_sfw_recerring_total_price_list_table_callback
	 * @param string $wps_price wps_price.
	 * @param int    $wps_subscription_id wps_subscription_id.
	 * @since 1.0.0
	 */
	function wps_sfw_recerring_total_price_list_table_callback( $wps_price, $wps_subscription_id ) {
		if ( wps_sfw_check_valid_subscription( $wps_subscription_id ) ) {
			$wps_recurring_number = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_number', true );
			$wps_recurring_interval = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_interval', true );
			$wps_price_html = wps_sfw_get_time_interval_for_price( $wps_recurring_number, $wps_recurring_interval );

			/* translators: %s: frequency interval. */
			$wps_price .= sprintf( esc_html( ' / %s ' ), $wps_price_html );
		}
		return $wps_price;
	}
}
if ( ! function_exists( 'wps_sfw_get_file_content' ) ) {
	/**
	 * This function is used to get file content.
	 *
	 * @name wps_sfw_get_file_content
	 * @param string $wps_file_path wps_file_path.
	 * @since 1.0.1
	 */
	function wps_sfw_get_file_content( $wps_file_path ) {
		global $wp_filesystem;

		WP_Filesystem();
		$wps_file_content = $wp_filesystem->get_contents( $wps_file_path );
		return $wps_file_content;
	}
}
if ( ! function_exists( 'wps_sfw_is_cart_has_subscription_product' ) ) {
	/**
	 * This function is used to check susbcripton product in cart.
	 *
	 * @name wps_sfw_is_cart_has_subscription_product
	 * @since 1.0.2
	 */
	function wps_sfw_is_cart_has_subscription_product() {
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
}

if ( ! function_exists( 'wps_sfw_get_subscription_supported_payment_method' ) ) {
	/**
	 * This function is used to get supported payment gateway.
	 *
	 * @name wps_sfw_get_subscription_supported_payment_method
	 * @since 1.0.2
	 */
	function wps_sfw_get_subscription_supported_payment_method() {

		$gateway =
			array(
				array(
					'id' => 'stripe',
					'name' => __( 'WooCommerce Stripe Gateway', 'subscriptions-for-woocommerce' ),
					'url' => 'https://wordpress.org/plugins/woocommerce-gateway-stripe/',
					'slug' => 'woocommerce-gateway-stripe',
					'is_activated' => ! empty( is_plugin_active( 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php' ) ) ? true : false,
				),
			);

		$gateway = apply_filters( 'wps_sfw_supported_data_payment_for_configuration', $gateway );
		return $gateway;
	}
}

if ( ! function_exists( 'wps_sfw_is_enable_usage_tracking' ) ) {
	/**
	 * This function is used to check tracking enable.
	 *
	 * @name wps_sfw_is_enable_usage_tracking
	 * @since 1.0.2
	 */
	function wps_sfw_is_enable_usage_tracking() {
		$is_enable = false;
		$wps_wps_enable = get_option( 'wps_sfw_enable_tracking', '' );
		if ( 'on' == $wps_wps_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'wps_sfw_check_valid_order' ) ) {
	/**
	 * This function is used to check valid order.
	 *
	 * @name wps_sfw_check_valid_order
	 * @param string $order_id order_id.
	 * @since 1.0.2
	 */
	function wps_sfw_check_valid_order( $order_id ) {
		$valid = true;
		if ( empty( $order_id ) ) {
			$valid = false;
		} else {
			$status = get_post_status( $order_id );
			$order = wc_get_order( $order_id );
			if ( 'trash' == $status ) {
				$valid = false;
			} elseif ( ! $order ) {
				$valid = false;
			}
		}

		return $valid;
	}


}
if ( ! function_exists( 'wps_sfw_is_woocommerce_tax_enabled' ) ) {
	/**
	 * Check if WooCommerce taxes are enabled.
	 *
	 * @return bool
	 */
	function wps_sfw_is_woocommerce_tax_enabled() {
		// Check if WooCommerce is active.
		if ( class_exists( 'WooCommerce' ) ) {
			// Get the tax options.
			$tax_options = get_option( 'woocommerce_calc_taxes' );

			// Check if taxes are enabled.
			if ( 'yes' === $tax_options ) {
				return true; // Taxes are enabled.
			}
		}
		return false; // Taxes are not enabled or WooCommerce is not active.
	}
}
if ( ! function_exists( 'wps_sfw_order_has_subscription' ) ) {
	/**
	 * Check if order contain subscrption product.
	 *
	 * @param string $order_id order_id.
	 * @return bool
	 */
	function wps_sfw_order_has_subscription( $order_id ) {

		$wps_has_subscription = false;

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( $item->get_variation_id() ) {
				$product_id = $item->get_variation_id();
			}
			$product = wc_get_product( $product_id );
			if ( wps_sfw_check_product_is_subscription( $product ) ) {
				$wps_has_subscription = true;
				break;
			}
		}
		return $wps_has_subscription;
	}
}

if ( ! function_exists( 'wps_wsp_check_api_enable' ) ) {
	/**
	 * This function is used to check api enbale.
	 *
	 * @name wps_wsp_check_api_enable
	 * @since 1.6.8
	 */
	function wps_wsp_check_api_enable() {
		$is_enable = false;
		$wps_wps_enable = get_option( 'wsp_enable_api_features', '' );
		if ( 'on' == $wps_wps_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}
if ( ! function_exists( 'wps_wsp_api_get_secret_key' ) ) {
	/**
	 * This function is used to check api enbale.
	 *
	 * @name wps_wsp_api_get_secret_key
	 * @since 1.6.8
	 */
	function wps_wsp_api_get_secret_key() {

		$wsp_api_secret_key = get_option( 'wsp_api_secret_key', '' );
		return $wsp_api_secret_key;
	}
}
if ( ! function_exists( 'wps_sfw_add_attached_product_for_subscription_box' ) ) {

	/**
	 * Function to attached product into subscrpition order.
	 *
	 * @param object $order_id as order id.
	 * @return void
	 */
	function wps_sfw_add_attached_product_for_subscription_box( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item_id => $item ) {
			$attached_products = wc_get_order_item_meta( $item_id, 'wps_sfw_attached_products', true );

			if ( ! empty( $attached_products ) ) {
				foreach ( $attached_products as $attached_product ) {
					$product_id = $attached_product['product_id'];
					$product = wc_get_product( $product_id );

					// Add attached product as a new order item with WooCommerce functions.
					$attached_item = new WC_Order_Item_Product();
					$attached_item->set_product_id( $product_id );
					$attached_item->set_name( $product->get_name() );
					$attached_item->set_quantity( $attached_product['quantity'] );
					$attached_item->set_subtotal( 0 );
					$attached_item->set_total( 0 );

					// Add custom meta.
					$attached_item->add_meta_data( '_is_attached_product', 'yes', true );

					// Add the item to the order.
					$order->add_item( $attached_item );
				}
			}
		}

		// Save the order to update all items properly.
		$order->save();
	}
}
