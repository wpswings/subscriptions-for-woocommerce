<?php
/**
 * Fired during plugin activation
 *
 * @link       https://wpswing.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes
 * @author     WP Swings <webmaster@wpswings.com>
 */
class Subscriptions_For_Woocommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function subscriptions_for_woocommerce_activate() {

		wp_clear_scheduled_hook( 'wpswings_tracker_send_event' );
		wp_schedule_event( time() + 10, apply_filters( 'wpswings_tracker_event_recurrence', 'daily' ), 'wpswings_tracker_send_event' );

		self::subscriptions_for_woocommerce_upgrade_wp_postmeta();
		self::subscriptions_for_woocommerce_upgrade_wp_options();
	}
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function subscriptions_for_woocommerce_upgrade_wp_postmeta() {

		$post_meta_keys = array(
			'_mwb_sfw_product',
			'mwb_sfw_subscription_number',
			'mwb_sfw_subscription_interval',
			'mwb_sfw_subscription_expiry_number',
			'mwb_sfw_subscription_expiry_interval',
			'mwb_sfw_subscription_initial_signup_price',
			'mwb_sfw_subscription_free_trial_number',
			'mwb_sfw_subscription_free_trial_interval',
			'mwb_sfw_subscription',
			'mwb_sfw_renewal_order',
			'mwb_sfw_parent_order_id',
			'mwb_renewal_subscription_order',
			'mwb_wsp_no_of_renewal_order',
			'mwb_wsp_renewal_order_data',
			'mwb_wsp_last_renewal_order_id',
			'mwb_next_payment_date',
			'mwb_subscription_status',
			'_mwb_paypal_transaction_ids',
			'_mwb_sfw_payment_transaction_id',
			'_mwb_paypal_subscription_id',
			'mwb_upgrade_downgrade_data',
			'mwb_susbcription_trial_end',
			'mwb_susbcription_end',
			'mwb_sfw_order_has_subscription',
			'mwb_subscription_id',
			'mwb_schedule_start',
			'mwb_sfw_subscription_activated',
		);

		foreach ( $post_meta_keys as $key => $meta_keys ) {
			$products = get_posts(
				array(
					'numberposts' => -1,
					'post_status' => 'publish',
					'fields'      => 'ids', // return only ids.
					'meta_key'    => $meta_keys, //phpcs:ignore
					'post_type'   => 'product',
					'order'       => 'ASC',
				)
			);

			if ( ! empty( $products ) && is_array( $products ) ) {
				foreach ( $products as $k => $product_id ) {
					$value   = get_post_meta( $product_id, $meta_keys, true );
					$new_key = str_replace( 'mwb_', 'wps_', $meta_keys );

					if ( ! empty( get_post_meta( $product_id, $new_key, true ) ) ) {
						continue;
					}

					update_post_meta( $product_id, $new_key, $value );
				}
			}
		}
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function subscriptions_for_woocommerce_upgrade_wp_options() {
		$wp_options = array(
			'mwb_sfw_enable_tracking'                       => '',
			'mwb_sfw_enable_plugin'                         => '',
			'mwb_sfw_add_to_cart_text'                      => '',
			'mwb_sfw_place_order_button_text'               => '',
			'mwb_sfw_multistep_product_create_done'         => '',
			'mwb_sfw_multistep_done'                        => '',
			'mwb_sfw_onboarding_data_skipped'               => '',
			'mwb_sfw_onboarding_data_sent'                  => '',
		);

		foreach ( $wp_options as $key => $value ) {
			$new_key = str_replace( 'mwb_', 'wps_', $key );

			if ( ! empty( get_option( $new_key ) ) ) {
				continue;
			}

			$new_value = get_option( $key, $value );
			update_option( $new_key, $new_value );
		}
	}



}
