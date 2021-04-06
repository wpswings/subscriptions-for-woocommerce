<?php
/**
 * The add show susbcription page.
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


$user_id = get_current_user_id();

	$args = array(
		'numberposts' => -1,
		'post_type'   => 'mwb_subscriptions',
		'post_status' => 'wc-mwb_renewal',
		'meta_query' => array(
			array(
				'key'   => 'mwb_customer_id',
				'value' => $user_id,
			),
		),

	);

	$mwb_subscriptions = get_posts( $args );


	?>
	<div class="mwb_sfw_account_wrap">
		<?php
		if ( ! empty( $mwb_subscriptions ) && is_array( $mwb_subscriptions ) ) {
			?>
				<table>
					<thead>
						<tr>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr"><?php esc_html_e( 'ID', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr"><?php esc_html_e( 'Status', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr"><?php echo esc_html_e( 'Next payment date', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr"><?php echo esc_html_e( 'Recurring Total', 'subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><?php esc_html_e( 'Action', 'subscriptions-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $mwb_subscriptions as $key => $mwb_subscription ) {
						?>
								<tr class="mwb_sfw_account_row woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
									<td class="mwb_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number">
								<?php echo esc_html( $mwb_subscription->ID ); ?>
									</td>
									<td class="mwb_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status">
								<?php
									$mwb_status = get_post_meta( $mwb_subscription->ID, 'mwb_subscription_status', true );
									echo esc_html( $mwb_status );
								?>
									</td>
									<td class="mwb_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date">
								<?php
									$mwb_next_payment_date = get_post_meta( $mwb_subscription->ID, 'mwb_next_payment_date', true );
									echo esc_html( mwb_sfw_get_the_wordpress_date_format( $mwb_next_payment_date ) );
								?>
									</td>
									<td class="mwb_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total">
									<?php
									do_action( 'mwb_sfw_display_susbcription_recerring_total_account_page', $mwb_subscription->ID );
									?>
									</td>
									<td class="mwb_sfw_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions">
										<span class="mwb_sfw_account_show_subscription">
											<a href="
									<?php
									echo esc_url( wc_get_endpoint_url( 'show-subscription', $mwb_subscription->ID, wc_get_page_permalink( 'myaccount' ) ) );
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
		} else {
			esc_html_e( 'You have not any active subscriptions.', 'subscriptions-for-woocommerce' );
		}
		?>
	</div>
