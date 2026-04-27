<?php
/**
 * Aurora subscriptions dashboard template.
 *
 * @package Subscriptions_For_Woocommerce
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$all_subscription_ids = isset( $wps_all_subscription_ids ) && is_array( $wps_all_subscription_ids ) ? $wps_all_subscription_ids : $wps_subscriptions;
$status_labels        = array(
	'active'    => __( 'Active', 'subscriptions-for-woocommerce' ),
	'on-hold'   => __( 'On hold', 'subscriptions-for-woocommerce' ),
	'paused'    => __( 'Paused', 'subscriptions-for-woocommerce' ),
	'cancelled' => __( 'Cancelled', 'subscriptions-for-woocommerce' ),
	'expired'   => __( 'Expired', 'subscriptions-for-woocommerce' ),
	'pending'   => __( 'Pending', 'subscriptions-for-woocommerce' ),
);
$status_counts        = array_fill_keys( array_keys( $status_labels ), 0 );
$deliveries_received  = 0;
$lifetime_spend       = 0;
$longest_running_days = 0;
$next_delivery        = null;

foreach ( $all_subscription_ids as $all_subscription_id ) {
	$current_status = (string) wps_sfw_get_meta_data( $all_subscription_id, 'wps_subscription_status', true );
	if ( isset( $status_counts[ $current_status ] ) ) {
		$status_counts[ $current_status ]++;
	}

	$started_at = (int) wps_sfw_get_meta_data( $all_subscription_id, 'wps_schedule_start', true );
	if ( $started_at > 0 ) {
		$longest_running_days = max( $longest_running_days, (int) floor( ( current_time( 'timestamp' ) - $started_at ) / DAY_IN_SECONDS ) );
	}

	$parent_order_id = (int) wps_sfw_get_meta_data( $all_subscription_id, 'wps_parent_order', true );
	if ( $parent_order_id && wps_sfw_check_valid_order( $parent_order_id ) ) {
		$parent_order = wc_get_order( $parent_order_id );
		if ( $parent_order ) {
			$lifetime_spend += (float) $parent_order->get_total();
		}
	}

	$renewal_order_ids = wps_sfw_get_meta_data( $all_subscription_id, 'wps_wsp_renewal_order_data', true );
	if ( is_array( $renewal_order_ids ) ) {
		$deliveries_received += count( $renewal_order_ids );
		foreach ( $renewal_order_ids as $renewal_order_id ) {
			$renewal_order = wc_get_order( $renewal_order_id );
			if ( $renewal_order ) {
				$lifetime_spend += (float) $renewal_order->get_total();
			}
		}
	}

	$next_payment_timestamp = (int) wps_sfw_get_meta_data( $all_subscription_id, 'wps_next_payment_date', true );
	if ( $next_payment_timestamp > current_time( 'timestamp' ) && ! in_array( $current_status, array( 'cancelled', 'expired' ), true ) ) {
		if ( null === $next_delivery || $next_payment_timestamp < $next_delivery['timestamp'] ) {
			$next_delivery = array(
				'id'        => $all_subscription_id,
				'timestamp' => $next_payment_timestamp,
				'name'      => (string) wps_sfw_get_meta_data( $all_subscription_id, 'product_name', true ),
			);
		}
	}
}

$active_count = isset( $status_counts['active'] ) ? (int) $status_counts['active'] : 0;
$showing_count = is_array( $wps_subscriptions ) ? count( $wps_subscriptions ) : 0;
$dashboard_base_url = wc_get_endpoint_url( 'wps_subscriptions', 1, wc_get_page_permalink( 'myaccount' ) );
?>
<div class="wps-sfw-aurora-account">
	<div class="wps-sfw-aurora-account__header">
		<div>
			<h2><?php esc_html_e( 'Your subscriptions', 'subscriptions-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'Manage deliveries, pause anytime, or update your payment method.', 'subscriptions-for-woocommerce' ); ?></p>
		</div>
	</div>

	<div class="wps-sfw-aurora-metrics">
		<div class="wps-sfw-aurora-metric">
			<span class="wps-sfw-aurora-metric__label"><?php esc_html_e( 'Active subscriptions', 'subscriptions-for-woocommerce' ); ?></span>
			<strong><?php echo esc_html( $active_count ); ?></strong>
			<span class="wps-sfw-aurora-metric__meta">
				<?php
				printf(
					/* translators: %d: total subscriptions */
					esc_html__( '%d total', 'subscriptions-for-woocommerce' ),
					(int) count( $all_subscription_ids )
				);
				?>
			</span>
		</div>
		<div class="wps-sfw-aurora-metric">
			<span class="wps-sfw-aurora-metric__label"><?php esc_html_e( 'Deliveries received', 'subscriptions-for-woocommerce' ); ?></span>
			<strong><?php echo esc_html( $deliveries_received ); ?></strong>
			<span class="wps-sfw-aurora-metric__meta"><?php esc_html_e( 'Renewal orders to date', 'subscriptions-for-woocommerce' ); ?></span>
		</div>
		<div class="wps-sfw-aurora-metric">
			<span class="wps-sfw-aurora-metric__label"><?php esc_html_e( 'Lifetime spend', 'subscriptions-for-woocommerce' ); ?></span>
			<strong><?php echo wp_kses_post( wc_price( $lifetime_spend ) ); ?></strong>
			<span class="wps-sfw-aurora-metric__meta"><?php esc_html_e( 'Across all plans', 'subscriptions-for-woocommerce' ); ?></span>
		</div>
		<div class="wps-sfw-aurora-metric">
			<span class="wps-sfw-aurora-metric__label"><?php esc_html_e( 'Longest running', 'subscriptions-for-woocommerce' ); ?></span>
			<strong><?php echo esc_html( $longest_running_days ); ?> <?php esc_html_e( 'days', 'subscriptions-for-woocommerce' ); ?></strong>
			<span class="wps-sfw-aurora-metric__meta"><?php esc_html_e( 'Your oldest subscription', 'subscriptions-for-woocommerce' ); ?></span>
		</div>
	</div>

	<?php if ( $next_delivery ) : ?>
		<div class="wps-sfw-aurora-next-delivery">
			<div class="wps-sfw-aurora-next-delivery__icon" aria-hidden="true"></div>
			<div>
				<span class="wps-sfw-aurora-next-delivery__eyebrow"><?php esc_html_e( 'Your next delivery', 'subscriptions-for-woocommerce' ); ?></span>
				<p>
					<strong><?php echo esc_html( $next_delivery['name'] ); ?></strong>
					<?php
					printf(
						/* translators: %s: next delivery date */
						esc_html__( 'ships on %s', 'subscriptions-for-woocommerce' ),
						esc_html( wps_sfw_get_the_wordpress_date_format( $next_delivery['timestamp'] ) )
					);
					?>
				</p>
			</div>
		</div>
	<?php endif; ?>

	<div class="wps-sfw-aurora-filterbar">
		<div class="wps-sfw-aurora-filters" data-default-filter="all">
			<button type="button" class="wps-sfw-aurora-filter is-active" data-filter="all">
				<?php esc_html_e( 'All', 'subscriptions-for-woocommerce' ); ?>
				<span><?php echo esc_html( count( $all_subscription_ids ) ); ?></span>
			</button>
			<?php foreach ( $status_labels as $status_key => $status_label ) : ?>
				<button type="button" class="wps-sfw-aurora-filter" data-filter="<?php echo esc_attr( $status_key ); ?>">
					<?php echo esc_html( $status_label ); ?>
					<span><?php echo esc_html( isset( $status_counts[ $status_key ] ) ? $status_counts[ $status_key ] : 0 ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
		<div class="wps-sfw-aurora-filterbar__meta">
			<?php
			printf(
				/* translators: 1: currently shown items, 2: total items */
				esc_html__( 'Showing %1$d of %2$d', 'subscriptions-for-woocommerce' ),
				(int) $showing_count,
				(int) ( isset( $wps_total_count ) ? $wps_total_count : count( $all_subscription_ids ) )
			);
			?>
		</div>
	</div>

	<div class="wps-sfw-aurora-subscription-list">
		<?php if ( ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) : ?>
			<?php foreach ( $wps_subscriptions as $subscription_id ) : ?>
				<?php
				$product_name          = (string) wps_sfw_get_meta_data( $subscription_id, 'product_name', true );
				$product_id            = (int) wps_sfw_get_meta_data( $subscription_id, 'product_id', true );
				$product_qty           = (int) wps_sfw_get_meta_data( $subscription_id, 'product_qty', true );
				$parent_order_id       = (int) wps_sfw_get_meta_data( $subscription_id, 'wps_parent_order', true );
				$started_timestamp     = (int) wps_sfw_get_meta_data( $subscription_id, 'wps_schedule_start', true );
				$next_payment_date     = (int) wps_sfw_get_meta_data( $subscription_id, 'wps_next_payment_date', true );
				$subscription_status   = (string) wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true );
				$subscription_number   = (string) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_number', true );
				$subscription_interval = (string) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_interval', true );
				$status_label          = isset( $status_labels[ $subscription_status ] ) ? $status_labels[ $subscription_status ] : ucfirst( $subscription_status );
				$interval_label        = ( $subscription_number && $subscription_interval ) ? wps_sfw_get_time_interval_for_price( $subscription_number, $subscription_interval ) : __( 'subscription', 'subscriptions-for-woocommerce' );
				$show_url              = wc_get_endpoint_url( 'show-subscription', $subscription_id, wc_get_page_permalink( 'myaccount' ) );

				global $post;
				if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'wps-subscription-dashboard' ) ) {
					$show_url = add_query_arg(
						array(
							'wps-show-subscription' => $subscription_id,
						),
						''
					);
				}

				$product_image = '';
				if ( $product_id ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$product_image = $product->get_image( 'thumbnail' );
					}
				}

				$next_delivery_text = __( 'Awaiting schedule', 'subscriptions-for-woocommerce' );
				if ( 'paused' === $subscription_status ) {
					$next_delivery_text = __( 'Paused — resume anytime', 'subscriptions-for-woocommerce' );
				} elseif ( 'cancelled' === $subscription_status ) {
					$next_delivery_text = __( 'Cancelled', 'subscriptions-for-woocommerce' );
				} elseif ( 'expired' === $subscription_status ) {
					$next_delivery_text = __( 'Ended', 'subscriptions-for-woocommerce' );
				} elseif ( $next_payment_date > 0 ) {
					$next_delivery_text = wps_sfw_get_the_wordpress_date_format( $next_payment_date );
				}

				ob_start();
				do_action( 'wps_sfw_display_susbcription_recerring_total_account_page', $subscription_id );
				$recurring_total_html = trim( ob_get_clean() );
				?>
				<article class="wps-sfw-aurora-subscription-card" data-status="<?php echo esc_attr( $subscription_status ); ?>">
					<div class="wps-sfw-aurora-subscription-card__product">
						<div class="wps-sfw-aurora-subscription-card__media">
							<?php if ( $product_image ) : ?>
								<?php echo wp_kses_post( $product_image ); ?>
							<?php else : ?>
								<span class="wps-sfw-aurora-subscription-card__glyph" aria-hidden="true"></span>
							<?php endif; ?>
						</div>
						<div class="wps-sfw-aurora-subscription-card__copy">
							<h3><?php echo esc_html( $product_name ); ?></h3>
							<div class="wps-sfw-aurora-status wps-sfw-aurora-status--<?php echo esc_attr( $subscription_status ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</div>
							<p>
								<?php
								printf(
									/* translators: 1: start date, 2: interval label */
									esc_html__( 'Started %1$s . Every %2$s', 'subscriptions-for-woocommerce' ),
									esc_html( $started_timestamp ? wps_sfw_get_the_wordpress_date_format( $started_timestamp ) : '---' ),
									esc_html( $interval_label )
								);
								?>
							</p>
							<div class="wps-sfw-aurora-subscription-card__ids">
								<span>
									<?php
									printf(
										/* translators: %d: subscription id */
										esc_html__( 'Subscription #%d', 'subscriptions-for-woocommerce' ),
										(int) $subscription_id
									);
									?>
								</span>
								<?php if ( $parent_order_id ) : ?>
									<span>
										<?php
										printf(
											/* translators: %d: parent order id */
											esc_html__( 'Parent #%d', 'subscriptions-for-woocommerce' ),
											(int) $parent_order_id
										);
										?>
									</span>
								<?php endif; ?>
							</div>
							<?php if ( $product_qty > 1 ) : ?>
								<span class="wps-sfw-aurora-subscription-card__meta">
									<?php
									printf(
										/* translators: %d: quantity */
										esc_html__( 'Qty: %d', 'subscriptions-for-woocommerce' ),
										(int) $product_qty
									);
									?>
								</span>
							<?php endif; ?>
						</div>
					</div>
					<div class="wps-sfw-aurora-subscription-card__schedule">
						<span class="wps-sfw-aurora-subscription-card__eyebrow"><?php esc_html_e( 'Next delivery', 'subscriptions-for-woocommerce' ); ?></span>
						<strong><?php echo esc_html( $next_delivery_text ); ?></strong>
						<span class="wps-sfw-aurora-subscription-card__meta">
							<?php echo esc_html( 'active' === $subscription_status && $next_payment_date > current_time( 'timestamp' ) && ( $next_payment_date - current_time( 'timestamp' ) ) <= DAY_IN_SECONDS ? __( 'Tomorrow', 'subscriptions-for-woocommerce' ) : '' ); ?>
						</span>
					</div>
					<div class="wps-sfw-aurora-subscription-card__price">
						<span class="wps-sfw-aurora-subscription-card__eyebrow"><?php esc_html_e( 'Amount', 'subscriptions-for-woocommerce' ); ?></span>
						<strong><?php echo wp_kses_post( $recurring_total_html ? $recurring_total_html : '---' ); ?></strong>
					</div>
					<div class="wps-sfw-aurora-subscription-card__actions">
						<a class="wps-sfw-aurora-manage-button" href="<?php echo esc_url( $show_url ); ?>">
							<?php esc_html_e( 'Manage', 'subscriptions-for-woocommerce' ); ?>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="wps-sfw-aurora-empty-state">
				<h3><?php esc_html_e( 'No subscriptions yet', 'subscriptions-for-woocommerce' ); ?></h3>
				<p><?php esc_html_e( 'You do not have any active subscription(s).', 'subscriptions-for-woocommerce' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) && 1 < $wps_num_pages ) : ?>
		<div class="wps-sfw-aurora-pagination">
			<?php if ( 1 !== (int) $wps_current_page ) : ?>
				<a class="wps-sfw-aurora-pagination__button" href="<?php echo esc_url( wc_get_endpoint_url( 'wps_subscriptions', $wps_current_page - 1, wc_get_page_permalink( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'Previous', 'subscriptions-for-woocommerce' ); ?></a>
			<?php endif; ?>

			<span class="wps-sfw-aurora-pagination__meta">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages */
					esc_html__( 'Page %1$d of %2$d', 'subscriptions-for-woocommerce' ),
					(int) $wps_current_page,
					(int) $wps_num_pages
				);
				?>
			</span>

			<?php if ( (int) $wps_num_pages !== (int) $wps_current_page ) : ?>
				<a class="wps-sfw-aurora-pagination__button" href="<?php echo esc_url( wc_get_endpoint_url( 'wps_subscriptions', $wps_current_page + 1, wc_get_page_permalink( 'myaccount' ) ) ); ?>"><?php esc_html_e( 'Next', 'subscriptions-for-woocommerce' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
