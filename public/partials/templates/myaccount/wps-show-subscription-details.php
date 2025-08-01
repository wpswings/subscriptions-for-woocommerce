<?php
/**
 * The add new payment.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! function_exists( 'wps_sfw_cancel_url' ) ) {
	/**
	 * This function is used to cancel url.
	 *
	 * @name wps_sfw_cancel_url.
	 * @param int    $wps_subscription_id wps_subscription_id.
	 * @param String $wps_status wps_status.
	 * @since 1.0.0
	 */
	function wps_sfw_cancel_url( $wps_subscription_id, $wps_status ) {

		$wps_link = add_query_arg(
			array(
				'wps_subscription_id'        => $wps_subscription_id,
				'wps_subscription_status' => $wps_status,
			)
		);
		$wps_link = wp_nonce_url( $wps_link, $wps_subscription_id . $wps_status );

		return $wps_link;
	}
}

?>
<?php
$referer = wp_get_referer();
if ( $referer ) {
	echo '<a href="' . esc_url( $referer ) . '"class="wps_sfw_sub_detail_back"><svg xmlns="http://www.w3.org/2000/svg" width="800px" height="800px" viewBox="0 0 1024 1024"><path fill="#000000" d="M224 480h640a32 32 0 1 1 0 64H224a32 32 0 0 1 0-64z"/><path fill="#000000" d="m237.248 512 265.408 265.344a32 32 0 0 1-45.312 45.312l-288-288a32 32 0 0 1 0-45.312l288-288a32 32 0 1 1 45.312 45.312L237.248 512z"/></svg> Back</a>';
}
?>
<div class="wps_sfw_details_wrap">
	<div class="wps_sfw_account_additional_wrap">

		<h3><?php esc_html_e( 'Subscription Details', 'subscriptions-for-woocommerce' ); ?></h3>
		<table class="shop_table wps_sfw_details">
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Status', 'subscriptions-for-woocommerce' ); ?></td>
					<?php $wps_status = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_subscription_status', true ); ?>
					<td class="woocommerce-orders-table__cell-order-status <?php echo esc_html( 'wps_sfw_' . $wps_status ); ?>"><span>
					<?php
					if ( 'active' === $wps_status ) {
						$wps_status = esc_html__( 'active', 'subscriptions-for-woocommerce' );
					} elseif ( 'on-hold' === $wps_status ) {
						$wps_status = esc_html__( 'on-hold', 'subscriptions-for-woocommerce' );
					} elseif ( 'cancelled' === $wps_status ) {
						$wps_status = esc_html__( 'cancelled', 'subscriptions-for-woocommerce' );
					} elseif ( 'paused' === $wps_status ) {
						$wps_status = esc_html__( 'paused', 'subscriptions-for-woocommerce' );
					} elseif ( 'pending' === $wps_status ) {
						$wps_status = esc_html__( 'pending', 'subscriptions-for-woocommerce' );
					} elseif ( 'expired' === $wps_status ) {
						$wps_status = esc_html__( 'expired', 'subscriptions-for-woocommerce' );
					}
						echo esc_html( $wps_status );
					?>
					</span>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Created', 'subscriptions-for-woocommerce' ); ?></td>
					<td>
					<?php
						$wps_schedule_start = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_schedule_start', true );
						echo esc_html( wps_sfw_get_the_wordpress_date_format( $wps_schedule_start ) );
					?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Next Payment', 'subscriptions-for-woocommerce' ); ?></td>
					
					<td>
					<?php
						$wps_next_payment_date = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_next_payment_date', true );
					if ( 'cancelled' === $wps_status ) {
						$wps_next_payment_date = '';
						$wps_susbcription_end = '';
						$wps_recurring_total = '---';
					}
						echo esc_html( wps_sfw_get_the_wordpress_date_format( $wps_next_payment_date ) );
					?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Expiry', 'subscriptions-for-woocommerce' ); ?></td>
					<td>
					<?php
						$wps_sfw_subscription_expire_date = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_susbcription_end', true );
					if ( 0 == $wps_sfw_subscription_expire_date ) {
							$wps_sfw_subscription_expire_date = '---';
							echo esc_html( $wps_sfw_subscription_expire_date );
					} else {

						echo esc_html( wps_sfw_get_the_wordpress_date_format( $wps_sfw_subscription_expire_date ) );
					}
					?>
					</td>
				</tr>
				<?php
				$wps_trail_date = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_susbcription_trial_end', true );

				if ( ! empty( $wps_trail_date ) ) {
					?>
					<tr>
						<td><?php esc_html_e( 'Trial End Date', 'subscriptions-for-woocommerce' ); ?></td>
						<td>
						<?php
							echo esc_html( wps_sfw_get_the_wordpress_date_format( $wps_trail_date ) );
						?>
						</td>
					</tr>
					<?php
				}


				if ( 'cancel' !== $wps_status ) {
					?>
					<tr>
						<td><?php esc_html_e( 'Next Recurring', 'subscriptions-for-woocommerce' ); ?></td>
						<td>
							<?php
							$wps_next_payment_date = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_next_payment_date', true );
							if ( $wps_next_payment_date ) {
								$time_difference = (int) $wps_next_payment_date - time();

								// Convert the difference from seconds to days.
								$days_left = ceil( $time_difference / ( 60 * 60 * 24 ) );
								if ( $days_left > 1 ) {
									$day_text = esc_attr__( 'Days', 'subscriptions-for-woocommerce' );
									echo esc_attr( $days_left . ' ' . $day_text );
								} else {
									echo esc_attr__( 'Tomorrow', 'subscriptions-for-woocommerce' );
								}
							} else {
								echo esc_attr( '---' );
							}
							?>
						</td>
					</tr>
					<?php
				}
				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$subscription = new WPS_Subscription( $wps_subscription_id );
				} else {
					$subscription = wc_get_order( $wps_subscription_id );
				}
				$wps_next_payment_date = $subscription->get_payment_method();
				$get_shipping_total = $subscription->get_shipping_total();
				if ( empty( $wps_next_payment_date ) ) {
					$subscription = wc_get_order( $wps_subscription_id );
					$wps_sfw_add_payment_url = wp_nonce_url( add_query_arg( array( 'wps_add_payment_method' => $wps_subscription_id ), $subscription->get_checkout_payment_url() ) );
					?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $wps_sfw_add_payment_url ); ?>" class="button wps_sfw_add_payment_url"><?php esc_html_e( 'Add Payment Method', 'subscriptions-for-woocommerce' ); ?></a>
						</td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td><?php esc_html_e( 'Actions', 'subscriptions-for-woocommerce' ); ?></td>
					<td>
					<?php do_action( 'wps_sfw_order_details_html_before_cancel', $wps_subscription_id ); ?>
					<?php
						$wps_sfw_cancel_subscription = get_option( 'wps_sfw_cancel_subscription_for_customer', '' );
						$wps_sfw_cancel_subscription = apply_filters( 'wps_sfw_customer_cancel_button', $wps_sfw_cancel_subscription, $wps_subscription_id );
					if ( 'on' == $wps_sfw_cancel_subscription ) {

						$wps_status = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_subscription_status', true );
						if ( 'active' == $wps_status ) {
							$wps_cancel_url = wps_sfw_cancel_url( $wps_subscription_id, $wps_status );
							?>
							<a href="<?php echo esc_url( $wps_cancel_url ); ?>" class="button wps_sfw_cancel_subscription"><?php esc_html_e( 'Cancel', 'subscriptions-for-woocommerce' ); ?></a>
							<?php
						}
					}
					?>
						<?php do_action( 'wps_sfw_order_details_html_after_cancel_button', $wps_subscription_id ); ?>
						<?php do_action( 'wps_sfw_order_details_html_after_cancel', $wps_subscription_id ); ?>
					</td>
				</tr>
				<?php
				do_action( 'wps_sfw_subscription_details_html', $wps_subscription_id );
				?>
			</tbody>
		</table>
	</div>
	<div class="wps_sfw_account_additional_wrap">
		<h3><?php esc_html_e( 'Subscription Order Details', 'subscriptions-for-woocommerce' ); ?></h3>
		<table class="shop_table wps_sfw_order_details">
			<tbody>
				<tr>
					<td>
						<?php esc_html_e( 'Product Name', 'subscriptions-for-woocommerce' ); ?>
					</td>
					<td>
						<?php
							$wps_product_name = wps_sfw_get_meta_data( $wps_subscription_id, 'product_name', true );
							$product_qty = wps_sfw_get_meta_data( $wps_subscription_id, 'product_qty', true );

						if ( is_array( $wps_product_name ) ) {
							$product_name = implode( ', ', $product_name );
						}
							echo esc_html( $wps_product_name ) . ' x ' . esc_html( $product_qty );
							do_action( 'wps_sfw_product_details_html', $wps_subscription_id );
						?>
						
					</td>
				</tr>
				<tr>
					<td>
						<?php esc_html_e( 'Subtotal', 'subscriptions-for-woocommerce' ); ?>
					</td>
					<td>
					<?php
						$price = wps_sfw_get_meta_data( $wps_subscription_id, 'line_subtotal', true );
						echo wp_kses_post( wc_price( $price ) );
					?>
					</td>
				</tr>
				<?php

				$tax_total = $subscription->get_total_tax();
				if ( $tax_total > 0 ) {
					?>
					<tr>
						<td>
							<?php esc_html_e( 'Tax', 'subscriptions-for-woocommerce' ); ?>
						</td>
						<td>
						<?php
							echo wp_kses_post( wc_price( $tax_total ) );
						?>
						</td>
					</tr>
					<?php

				}
				if ( $get_shipping_total ) {
					?>
					<tr>
						<td>
							<?php esc_html_e( 'Shipping', 'subscriptions-for-woocommerce' ); ?>
						</td>
						<td>
						<?php
							echo wp_kses_post( wc_price( $get_shipping_total ) );
						?>
						</td>
					</tr>
					<?php
				}
				?>

				<tr>
					<td>
						<strong><?php esc_html_e( 'Total', 'subscriptions-for-woocommerce' ); ?></strong>
					</td>
					<td>
					<strong>
					<?php
						do_action( 'wps_sfw_display_susbcription_recerring_total_account_page', $wps_subscription_id );
					?>
					</strong>
					</td>
				</tr>
				<?php
				$wps_wsp_renewal_order_data = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_wsp_renewal_order_data', true );
				if ( $wps_wsp_renewal_order_data ) {
					$wps_sfw_points_earned = 0;
					if ( ! empty( $wps_wsp_renewal_order_data ) ) {
						foreach ( $wps_wsp_renewal_order_data as $key => $value ) {
							$wps_wpr_subscription_renewal_awarded_points = wps_sfw_get_meta_data( $value, 'wps_wpr_subscription_renewal_awarded_points', true );
							if ( $wps_wpr_subscription_renewal_awarded_points ) {
								$wps_sfw_points_earned += $wps_wpr_subscription_renewal_awarded_points;
							}
						}
						if ( $wps_sfw_points_earned ) {
							?>
							<tr>
								<td>
									<strong><?php esc_html_e( 'Total Subscription Points Collected', 'subscriptions-for-woocommerce' ); ?></strong>
								</td>
								<td>
									<?php echo esc_html( $wps_sfw_points_earned ); ?>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>
			</tbody>
		</table>
	</div>
	<?php do_action( 'wps_sfw_after_subscription_details', $wps_subscription_id ); ?>
</div>
