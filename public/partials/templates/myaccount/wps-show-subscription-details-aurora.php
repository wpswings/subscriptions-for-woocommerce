<?php
/**
 * Aurora subscription detail template.
 *
 * @package Subscriptions_For_Woocommerce
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_labels = array(
	'active'    => __( 'Active', 'subscriptions-for-woocommerce' ),
	'on-hold'   => __( 'On hold', 'subscriptions-for-woocommerce' ),
	'paused'    => __( 'Paused', 'subscriptions-for-woocommerce' ),
	'cancelled' => __( 'Cancelled', 'subscriptions-for-woocommerce' ),
	'expired'   => __( 'Expired', 'subscriptions-for-woocommerce' ),
	'pending'   => __( 'Pending', 'subscriptions-for-woocommerce' ),
);

$subscription_status       = (string) wps_sfw_get_meta_data( $wps_subscription_id, 'wps_subscription_status', true );
$status_label              = isset( $status_labels[ $subscription_status ] ) ? $status_labels[ $subscription_status ] : ucfirst( $subscription_status );
$product_name              = (string) wps_sfw_get_meta_data( $wps_subscription_id, 'product_name', true );
$product_id                = (int) wps_sfw_get_meta_data( $wps_subscription_id, 'product_id', true );
$product_qty               = (int) wps_sfw_get_meta_data( $wps_subscription_id, 'product_qty', true );
$schedule_start            = (int) wps_sfw_get_meta_data( $wps_subscription_id, 'wps_schedule_start', true );
$next_payment_date         = (int) wps_sfw_get_meta_data( $wps_subscription_id, 'wps_next_payment_date', true );
$subscription_end          = (int) wps_sfw_get_meta_data( $wps_subscription_id, 'wps_susbcription_end', true );
$subscription_trial_end    = (int) wps_sfw_get_meta_data( $wps_subscription_id, 'wps_susbcription_trial_end', true );
$subscription_number       = (string) wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_number', true );
$subscription_interval     = (string) wps_sfw_get_meta_data( $wps_subscription_id, 'wps_sfw_subscription_interval', true );
$billing_cycle             = ( $subscription_number && $subscription_interval ) ? wps_sfw_get_time_interval_for_price( $subscription_number, $subscription_interval ) : __( 'subscription', 'subscriptions-for-woocommerce' );
$parent_order_id           = (int) wps_sfw_get_meta_data( $wps_subscription_id, 'wps_parent_order', true );
$renewal_order_ids         = wps_sfw_get_meta_data( $wps_subscription_id, 'wps_wsp_renewal_order_data', true );
$renewal_order_ids         = is_array( $renewal_order_ids ) ? $renewal_order_ids : array();
$show_back_url             = wc_get_endpoint_url( 'wps_subscriptions', '', wc_get_page_permalink( 'myaccount' ) );
$payment_method_url        = '';
$payment_method_title      = '';
$lifetime_paid             = 0;
$product_image             = '';

if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
	$subscription = new WPS_Subscription( $wps_subscription_id );
} else {
	$subscription = wc_get_order( $wps_subscription_id );
}

if ( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( $product ) {
		$product_image = $product->get_image( 'thumbnail' );
	}
}

if ( $subscription ) {
	$payment_method_title = (string) $subscription->get_payment_method_title();
	$payment_url          = $subscription->get_checkout_payment_url();
	if ( $payment_url ) {
		$payment_method_url = wp_nonce_url(
			add_query_arg(
				array(
					'wps_add_payment_method' => $wps_subscription_id,
				),
				$payment_url
			)
		);
	}
}

if ( $parent_order_id && wps_sfw_check_valid_order( $parent_order_id ) ) {
	$parent_order = wc_get_order( $parent_order_id );
	if ( $parent_order ) {
		$lifetime_paid += (float) $parent_order->get_total();
	}
}

foreach ( $renewal_order_ids as $renewal_order_id ) {
	$renewal_order = wc_get_order( $renewal_order_id );
	if ( $renewal_order ) {
		$lifetime_paid += (float) $renewal_order->get_total();
	}
}

$cancel_subscription_enabled = get_option( 'wps_sfw_cancel_subscription_for_customer', '' );
$cancel_subscription_enabled = apply_filters( 'wps_sfw_customer_cancel_button', $cancel_subscription_enabled, $wps_subscription_id );
$cancel_url                  = '';

if ( 'on' === $cancel_subscription_enabled && 'active' === $subscription_status ) {
	$cancel_url = add_query_arg(
		array(
			'wps_subscription_id'     => $wps_subscription_id,
			'wps_subscription_status' => $subscription_status,
		)
	);
	$cancel_url = wp_nonce_url( $cancel_url, $wps_subscription_id . $subscription_status );
}

ob_start();
do_action( 'wps_sfw_display_susbcription_recerring_total_account_page', $wps_subscription_id );
$recurring_total_html = trim( ob_get_clean() );

ob_start();
do_action( 'wps_sfw_order_details_html_before_cancel', $wps_subscription_id );
do_action( 'wps_sfw_order_details_html_after_cancel_button', $wps_subscription_id );
do_action( 'wps_sfw_order_details_html_after_cancel', $wps_subscription_id );
$pro_manage_actions_html = trim( ob_get_clean() );

ob_start();
do_action( 'wps_sfw_after_subscription_details', $wps_subscription_id );
$pro_after_details_html = trim( ob_get_clean() );
?>
<div class="wps-sfw-aurora-detail">
	<div class="wps-sfw-aurora-detail__back">
		<a href="<?php echo esc_url( $show_back_url ); ?>">&larr; <?php esc_html_e( 'Back to subscriptions', 'subscriptions-for-woocommerce' ); ?></a>
	</div>

	<div class="wps-sfw-aurora-detail__hero">
		<div class="wps-sfw-aurora-detail__hero-main">
			<div class="wps-sfw-aurora-detail__hero-media">
				<?php if ( $product_image ) : ?>
					<?php echo wp_kses_post( $product_image ); ?>
				<?php else : ?>
					<span class="wps-sfw-aurora-subscription-card__glyph" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="wps-sfw-aurora-detail__hero-copy">
				<div class="wps-sfw-aurora-status wps-sfw-aurora-status--<?php echo esc_attr( $subscription_status ); ?>">
					<?php echo esc_html( $status_label ); ?>
				</div>
				<h2><?php echo esc_html( $product_name ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: 1: start date, 2: quantity */
						esc_html__( 'Started %1$s . Qty %2$d', 'subscriptions-for-woocommerce' ),
						esc_html( $schedule_start ? wps_sfw_get_the_wordpress_date_format( $schedule_start ) : '---' ),
						max( 1, (int) $product_qty )
					);
					?>
				</p>
			</div>
		</div>
		<div class="wps-sfw-aurora-detail__hero-price">
			<span><?php esc_html_e( 'You pay', 'subscriptions-for-woocommerce' ); ?></span>
			<strong><?php echo wp_kses_post( $recurring_total_html ? $recurring_total_html : '---' ); ?></strong>
		</div>
	</div>

	<?php if ( $next_payment_date > 0 && ! in_array( $subscription_status, array( 'cancelled', 'expired' ), true ) ) : ?>
		<div class="wps-sfw-aurora-next-delivery wps-sfw-aurora-next-delivery--detail">
			<div class="wps-sfw-aurora-next-delivery__icon" aria-hidden="true"></div>
			<div>
				<span class="wps-sfw-aurora-next-delivery__eyebrow"><?php esc_html_e( 'Your next delivery', 'subscriptions-for-woocommerce' ); ?></span>
				<p>
					<strong><?php echo esc_html( wps_sfw_get_the_wordpress_date_format( $next_payment_date ) ); ?></strong>
					<?php esc_html_e( 'is your next recurring charge date.', 'subscriptions-for-woocommerce' ); ?>
				</p>
			</div>
		</div>
	<?php endif; ?>

	<div class="wps-sfw-aurora-detail__grid">
		<div class="wps-sfw-aurora-panel">
			<div class="wps-sfw-aurora-panel__head">
				<h3><?php esc_html_e( 'Manage your subscription', 'subscriptions-for-woocommerce' ); ?></h3>
			</div>
			<div class="wps-sfw-aurora-action-list">
				<div class="wps-sfw-aurora-action-card">
					<div class="wps-sfw-aurora-action-card__copy">
						<h4><?php esc_html_e( 'Subscription status', 'subscriptions-for-woocommerce' ); ?></h4>
						<p><?php echo esc_html( $status_label ); ?></p>
					</div>
				</div>

				<?php if ( $payment_method_title || $payment_method_url ) : ?>
					<div class="wps-sfw-aurora-action-card">
						<div class="wps-sfw-aurora-action-card__copy">
							<h4><?php esc_html_e( 'Payment method', 'subscriptions-for-woocommerce' ); ?></h4>
							<p><?php echo esc_html( $payment_method_title ? $payment_method_title : __( 'Add a payment method', 'subscriptions-for-woocommerce' ) ); ?></p>
						</div>
						<?php if ( $payment_method_url ) : ?>
							<a class="wps-sfw-aurora-action-card__button" href="<?php echo esc_url( $payment_method_url ); ?>">
								<?php echo esc_html( $payment_method_title ? __( 'Update', 'subscriptions-for-woocommerce' ) : __( 'Add', 'subscriptions-for-woocommerce' ) ); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $cancel_url ) : ?>
					<div class="wps-sfw-aurora-action-card wps-sfw-aurora-action-card--danger">
						<div class="wps-sfw-aurora-action-card__copy">
							<h4><?php esc_html_e( 'Cancel subscription', 'subscriptions-for-woocommerce' ); ?></h4>
							<p><?php esc_html_e( 'Stop all future deliveries and billing for this plan.', 'subscriptions-for-woocommerce' ); ?></p>
						</div>
						<a class="wps-sfw-aurora-action-card__button" href="<?php echo esc_url( $cancel_url ); ?>">
							<?php esc_html_e( 'Cancel', 'subscriptions-for-woocommerce' ); ?>
						</a>
					</div>
				<?php endif; ?>

				<?php if ( $pro_manage_actions_html ) : ?>
					<div class="wps-sfw-aurora-action-card wps-sfw-aurora-action-card--stack">
						<div class="wps-sfw-aurora-action-card__copy">
							<h4><?php esc_html_e( 'More actions', 'subscriptions-for-woocommerce' ); ?></h4>
							<div class="wps-sfw-aurora-pro-actions">
								<?php echo $pro_manage_actions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hook output is rendered markup from integrated plugins. ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="wps-sfw-aurora-panel">
			<div class="wps-sfw-aurora-panel__head">
				<h3><?php esc_html_e( 'Summary', 'subscriptions-for-woocommerce' ); ?></h3>
			</div>
			<div class="wps-sfw-aurora-summary">
				<div class="wps-sfw-aurora-summary__total">
					<span><?php esc_html_e( 'Paid so far', 'subscriptions-for-woocommerce' ); ?></span>
					<strong><?php echo wp_kses_post( wc_price( $lifetime_paid ) ); ?></strong>
				</div>
				<ul class="wps-sfw-aurora-summary__list">
					<li><span><?php esc_html_e( 'Started', 'subscriptions-for-woocommerce' ); ?></span><strong><?php echo esc_html( $schedule_start ? wps_sfw_get_the_wordpress_date_format( $schedule_start ) : '---' ); ?></strong></li>
					<li><span><?php esc_html_e( 'Billing cycle', 'subscriptions-for-woocommerce' ); ?></span><strong><?php echo esc_html( $billing_cycle ? sprintf( __( 'Every %s', 'subscriptions-for-woocommerce' ), $billing_cycle ) : '---' ); ?></strong></li>
					<li><span><?php esc_html_e( 'Next charge', 'subscriptions-for-woocommerce' ); ?></span><strong><?php echo esc_html( $next_payment_date ? wps_sfw_get_the_wordpress_date_format( $next_payment_date ) : '---' ); ?></strong></li>
					<li><span><?php esc_html_e( 'Next amount', 'subscriptions-for-woocommerce' ); ?></span><strong><?php echo wp_kses_post( $recurring_total_html ? $recurring_total_html : '---' ); ?></strong></li>
					<li><span><?php esc_html_e( 'Ends', 'subscriptions-for-woocommerce' ); ?></span><strong><?php echo esc_html( $subscription_end ? wps_sfw_get_the_wordpress_date_format( $subscription_end ) : __( 'No end date', 'subscriptions-for-woocommerce' ) ); ?></strong></li>
					<?php if ( $subscription_trial_end ) : ?>
						<li><span><?php esc_html_e( 'Trial ends', 'subscriptions-for-woocommerce' ); ?></span><strong><?php echo esc_html( wps_sfw_get_the_wordpress_date_format( $subscription_trial_end ) ); ?></strong></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
	</div>

	<?php if ( $pro_after_details_html ) : ?>
		<div class="wps-sfw-aurora-extensions">
			<?php echo $pro_after_details_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hook output is rendered markup from integrated plugins. ?>
		</div>
	<?php endif; ?>
</div>
