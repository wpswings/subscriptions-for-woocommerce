<?php
/**
 * Exit if accessed directly
 *
 * @since      1.0.0
 * @package    subscriptions-for-woocommerce
 * @subpackage subscriptions-for-woocommercee/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mwb_sfw_get_the_wordpress_date_format' ) ) { 

	/**
	 * This function is used to get date format.
	 * @name mwb_sfw_get_the_wordpress_date_format
	 * @since 1.0.0
	 * @param int $saved_date saved_date.
	 */
	function mwb_sfw_get_the_wordpress_date_format( $saved_date ) {
		$return_date = '---';
		if ( isset( $saved_date ) && !empty( $saved_date ) ) {
			
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
	 * @name mwb_sfw_next_payment_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 * @param string $mwb_susbcription_trial_end mwb_susbcription_trial_end. 
	 */
	function mwb_sfw_next_payment_date( $subscription_id, $current_time, $mwb_susbcription_trial_end ) {

		$mwb_sfw_next_pay_date = 0;
		$mwb_recurring_number = get_post_meta( $subscription_id, 'mwb_sfw_subscription_number',true );
		$mwb_recurring_interval = get_post_meta( $subscription_id, 'mwb_sfw_subscription_interval',true );

		if ( $mwb_susbcription_trial_end != 0 ) {
			
			$mwb_sfw_next_pay_date = $mwb_susbcription_trial_end;
		}
		else{
			$mwb_sfw_next_pay_date = mwb_sfw_susbcription_calculate_time( $current_time, $mwb_recurring_number, $mwb_recurring_interval );
		}
		return $mwb_sfw_next_pay_date;
	}
}

if ( ! function_exists( 'mwb_sfw_susbcription_expiry_date' ) ) {

	/**
	 * This function is used to get expiry date.
	 * @name mwb_sfw_susbcription_expiry_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 */
	function mwb_sfw_susbcription_expiry_date( $subscription_id, $current_time ) {
		$mwb_sfw_expiry_date = 0;
		$expiry_number = get_post_meta( $subscription_id, 'mwb_sfw_subscription_expiry_number',true );
		$expiry_interval = get_post_meta( $subscription_id, 'mwb_sfw_subscription_expiry_interval',true );

		if ( isset( $expiry_number ) && !empty( $expiry_number ) ) {
			$mwb_sfw_expiry_date = mwb_sfw_susbcription_calculate_time( $current_time, $expiry_number, $expiry_interval );
			
		}
		return $mwb_sfw_expiry_date;
	}
}

if ( ! function_exists( 'mwb_sfw_susbcription_trial_date' ) ) {

	/**
	 * This function is used to get trial date.
	 * @name mwb_sfw_susbcription_trial_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 */
	function mwb_sfw_susbcription_trial_date( $subscription_id, $current_time ) {
		$mwb_sfw_trial_date = 0;
		$trial_number = get_post_meta( $subscription_id, 'mwb_sfw_subscription_free_trial_number',true );
		$trial_interval = get_post_meta( $subscription_id, 'mwb_sfw_subscription_free_trial_interval',true );

		if ( isset( $trial_number ) && !empty( $trial_number ) ) {
			$mwb_sfw_trial_date = mwb_sfw_susbcription_calculate_time( $current_time, $trial_number, $trial_interval );
			
		}

		return $mwb_sfw_trial_date;
	}
}

if ( ! function_exists( 'mwb_sfw_susbcription_calculate_time' ) ) {

	/**
	 * This function is used to calculate time.
	 * @name mwb_sfw_susbcription_calculate_time
	 * @since 1.0.0
	 * @param int $mwb_curr_time mwb_curr_time.
	 * @param int $mwb_interval_count mwb_interval_count.
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
	 * @name mwb_sfw_check_valid_subscription
	 * @since 1.0.0
	 * @param int $mwb_subscription_id mwb_subscription_id.
	 */
	function mwb_sfw_check_valid_subscription( $mwb_subscription_id ) {
		$mwb_is_subscription = false;

		if ( isset( $mwb_subscription_id ) && !empty( $mwb_subscription_id ) ) {
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
	 * @name mwb_sfw_update_meta_key_for_susbcription
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param array $mwb_args mwb_args.
	 */
	function mwb_sfw_update_meta_key_for_susbcription( $subscription_id, $mwb_args ) {
		if ( isset( $mwb_args ) && !empty( $mwb_args ) && is_array( $mwb_args ) ) {
			foreach ( $mwb_args as $key => $value ) {
				update_post_meta( $subscription_id, $key, $value );
			}
		}
	}
}

if ( ! function_exists( 'mwb_sfw_send_email_for_renewal_susbcription' ) ) { 
	/**
	 * This function is used to send renewal email.
	 * @name mwb_sfw_send_email_for_renewal_susbcription
	 * @since 1.0.0
	 * @param int $order_id order_id.
	 */
	function mwb_sfw_send_email_for_renewal_susbcription( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( isset( $order ) && is_object( $order ) ) {
			$mailer = WC()->mailer()->get_emails();
		    // Send the "processing" notification
		    if ( isset($mailer['WC_Email_New_Order']) ) {
		    	 $mailer['WC_Email_New_Order']->trigger( $order_id );
		    }
		    if ( $order->get_status() == 'processing' ) {
		    	if ( isset($mailer['WC_Email_Customer_Processing_Order']) ) {
			    	 $mailer['WC_Email_Customer_Processing_Order']->trigger( $order_id );

			    }
		    }
		    
		}
	}
}

						    

