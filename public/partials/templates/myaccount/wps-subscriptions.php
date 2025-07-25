<?php
/**
 * The add show susbcription page.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/public
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
	<div class="wps_sfw_account_wrap wps_sfw_account_additional_wrap">
		<?php
		if ( ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
			?>
				<table>
					<thead>
						<tr>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr"><?php esc_html_e( 'ID', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr"><?php esc_html_e( 'Parent ID', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr"><?php esc_html_e( 'Status', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr"><?php echo esc_html_e( 'Next Payment', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr"><?php echo esc_html_e( 'Total', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><?php esc_html_e( 'Action', 'subscriptions-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $wps_subscriptions as $key => $wps_subscription ) {
						if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
							$subcription_id = $wps_subscription;
						} else {
							$subcription_id = $wps_subscription->ID;
						}
						$parent_order_id   = wps_sfw_get_meta_data( $subcription_id, 'wps_parent_order', true );
						$wps_wsfw_is_order = false;
						if ( function_exists( 'wps_sfw_check_valid_order' ) && ! wps_sfw_check_valid_order( $parent_order_id ) ) {
							$wps_wsfw_is_order = apply_filters( 'wps_wsfw_check_parent_order', $wps_wsfw_is_order, $parent_order_id );
							if ( false == $wps_wsfw_is_order ) {
								continue;
							}
						}
						?>
						<tr class="wps_sfw_account_row woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
							<td class="wps_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number">
								<?php echo esc_html( $subcription_id ); ?>
							</td>
							<td class="wps_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-parent-id">
								<?php
									$parent_order_id   = wps_sfw_get_meta_data( $subcription_id, 'wps_parent_order', true );
									$parent_order = wc_get_order( $parent_order_id );
								?>
									<a target="_blank" href="<?php echo esc_url( $parent_order->get_view_order_url() ); ?>">
									<?php echo '#' . esc_html( $parent_order_id ); ?>
									</a>
							</td>
							<?php $wps_status = wps_sfw_get_meta_data( $subcription_id, 'wps_subscription_status', true ); ?>
							<td class="wps_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status wps_sfw_<?php echo esc_html( $wps_status ); ?>"><span>
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
							<td class="wps_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date">
						<?php
							$wps_next_payment_date = wps_sfw_get_meta_data( $subcription_id, 'wps_next_payment_date', true );
						if ( 'cancelled' === $wps_status ) {
							$wps_next_payment_date = '';
						}
							echo esc_html( wps_sfw_get_the_wordpress_date_format( $wps_next_payment_date ) );
						?>
							</td>
							<td class="wps_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total">
							<?php
							do_action( 'wps_sfw_display_susbcription_recerring_total_account_page', $subcription_id );
							?>
							</td>
							</td>
							<td class="wps_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions">
								<span class="wps_sfw_account_show_subscription">
									<a href="
							<?php
							global $post;

							if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'wps-subscription-dashboard' ) ) {
								$url = add_query_arg(
									array(
										'wps-show-subscription' => $subcription_id,
									),
									''
								);
								echo esc_url( $url );
							} else {
								echo esc_url( wc_get_endpoint_url( 'show-subscription', $subcription_id, wc_get_page_permalink( 'myaccount' ) ) );

							}
							?>
									">
							<?php
							esc_html_e( 'Show', 'subscriptions-for-woocommerce' );
							?>
									</a>
								</span>
							</td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
				<?php
				if ( 1 < $wps_num_pages ) {
					?>
			<div class="wps_sfw_pagination woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
					<?php if ( 1 !== $wps_current_page ) { ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( 'wps_subscriptions', $wps_current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'subscriptions-for-woocommerce' ); ?></a>
			<?php } ?>

					<?php if ( intval( $wps_num_pages ) !== $wps_current_page ) { ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( 'wps_subscriptions', $wps_current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'subscriptions-for-woocommerce' ); ?></a>
			<?php } ?>
			</div>
		<?php } ?>
			<?php
		} else {
			esc_html_e( 'You do not have any active subscription(s).', 'subscriptions-for-woocommerce' );
		}
		?>
	</div>
