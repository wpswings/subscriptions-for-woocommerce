<?php
/**
 * My Account — Memberships tab template.
 *
 * Displays a table of the current customer's membership records (all statuses).
 *
 * Available variables (set by WPS_Myaccount_Memberships::render_tab()):
 *   $wps_memberships  array  Membership rows returned by wps_get_user_memberships().
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fires before the memberships table is output.
 *
 * @since 2.0.0
 */
do_action( 'wps_before_myaccount_memberships' );
?>

<h2><?php esc_html_e( 'My Memberships', 'subscriptions-for-woocommerce' ); ?></h2>

<?php if ( empty( $wps_memberships ) ) : ?>

	<div class="woocommerce-message woocommerce-message--info wps-no-memberships">
		<p>
			<?php
			esc_html_e(
				'You do not have any memberships yet.',
				'subscriptions-for-woocommerce'
			);
			?>
		</p>
	</div>

<?php else : ?>

	<table class="woocommerce-orders-table woocommerce-MyAccount-memberships wps-memberships-table shop_table shop_table_responsive">
		<thead>
			<tr>
				<th class="wps-memberships-table__header wps-memberships-table__header--plan">
					<span class="nobr"><?php esc_html_e( 'Plan', 'subscriptions-for-woocommerce' ); ?></span>
				</th>
				<th class="wps-memberships-table__header wps-memberships-table__header--status">
					<span class="nobr"><?php esc_html_e( 'Status', 'subscriptions-for-woocommerce' ); ?></span>
				</th>
				<th class="wps-memberships-table__header wps-memberships-table__header--started">
					<span class="nobr"><?php esc_html_e( 'Started', 'subscriptions-for-woocommerce' ); ?></span>
				</th>
				<th class="wps-memberships-table__header wps-memberships-table__header--expires">
					<span class="nobr"><?php esc_html_e( 'Expires', 'subscriptions-for-woocommerce' ); ?></span>
				</th>
				<th class="wps-memberships-table__header wps-memberships-table__header--source">
					<span class="nobr"><?php esc_html_e( 'Source', 'subscriptions-for-woocommerce' ); ?></span>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $wps_memberships as $wps_row ) : ?>
				<?php
				$wps_slug        = isset( $wps_row['plan_slug'] ) ? $wps_row['plan_slug'] : '';
				$wps_status      = isset( $wps_row['status'] ) ? $wps_row['status'] : 'cancelled';
				$wps_source      = isset( $wps_row['source'] ) ? $wps_row['source'] : 'manual';
				$wps_start_ts    = isset( $wps_row['start_date'] ) ? absint( $wps_row['start_date'] ) : 0;
				$wps_expiry_ts   = isset( $wps_row['expiry_date'] ) ? absint( $wps_row['expiry_date'] ) : 0;
				$wps_sub_id      = isset( $wps_row['subscription_id'] ) ? absint( $wps_row['subscription_id'] ) : 0;
				$wps_order_id    = isset( $wps_row['order_id'] ) ? absint( $wps_row['order_id'] ) : 0;

				// Resolve plan display name.
				$wps_plan        = function_exists( 'wps_get_plan_by_slug' )
					? wps_get_plan_by_slug( $wps_slug )
					: null;
				$wps_plan_name   = $wps_plan && ! empty( $wps_plan['name'] )
					? $wps_plan['name']
					: ucfirst( $wps_slug );

				// Format dates.
				$wps_date_format = get_option( 'date_format' );
				$wps_start_str   = $wps_start_ts
					? date_i18n( $wps_date_format, $wps_start_ts )
					: '—';
				$wps_expiry_str  = $wps_expiry_ts
					? date_i18n( $wps_date_format, $wps_expiry_ts )
					: __( 'Lifetime', 'subscriptions-for-woocommerce' );

				// Map status to CSS modifier and label.
				$wps_status_map = array(
					'active'    => array( 'mod' => 'active', 'label' => __( 'Active', 'subscriptions-for-woocommerce' ) ),
					'on-hold'   => array( 'mod' => 'on-hold', 'label' => __( 'On Hold', 'subscriptions-for-woocommerce' ) ),
					'cancelled' => array( 'mod' => 'cancelled', 'label' => __( 'Cancelled', 'subscriptions-for-woocommerce' ) ),
					'expired'   => array( 'mod' => 'expired', 'label' => __( 'Expired', 'subscriptions-for-woocommerce' ) ),
					'paused'    => array( 'mod' => 'paused', 'label' => __( 'Paused', 'subscriptions-for-woocommerce' ) ),
				);
				$wps_status_info = isset( $wps_status_map[ $wps_status ] )
					? $wps_status_map[ $wps_status ]
					: array( 'mod' => 'cancelled', 'label' => ucfirst( $wps_status ) );
				?>
				<tr class="wps-memberships-table__row wps-memberships-table__row--<?php echo esc_attr( $wps_status ); ?>">

					<td class="wps-memberships-table__cell wps-memberships-table__cell--plan"
						data-title="<?php esc_attr_e( 'Plan', 'subscriptions-for-woocommerce' ); ?>">
						<?php echo esc_html( $wps_plan_name ); ?>
					</td>

					<td class="wps-memberships-table__cell wps-memberships-table__cell--status"
						data-title="<?php esc_attr_e( 'Status', 'subscriptions-for-woocommerce' ); ?>">
						<mark class="membership-status status-<?php echo esc_attr( $wps_status_info['mod'] ); ?>">
							<span><?php echo esc_html( $wps_status_info['label'] ); ?></span>
						</mark>
					</td>

					<td class="wps-memberships-table__cell wps-memberships-table__cell--started"
						data-title="<?php esc_attr_e( 'Started', 'subscriptions-for-woocommerce' ); ?>">
						<?php echo esc_html( $wps_start_str ); ?>
					</td>

					<td class="wps-memberships-table__cell wps-memberships-table__cell--expires"
						data-title="<?php esc_attr_e( 'Expires', 'subscriptions-for-woocommerce' ); ?>">
						<?php echo esc_html( $wps_expiry_str ); ?>
					</td>

					<td class="wps-memberships-table__cell wps-memberships-table__cell--source"
						data-title="<?php esc_attr_e( 'Source', 'subscriptions-for-woocommerce' ); ?>">
						<?php if ( 'subscription' === $wps_source && $wps_sub_id ) : ?>
							<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'view-subscription' ) . $wps_sub_id ); ?>">
								<?php esc_html_e( 'Subscription', 'subscriptions-for-woocommerce' ); ?>
								<?php echo ' #' . esc_html( (string) $wps_sub_id ); ?>
							</a>
						<?php elseif ( 'order' === $wps_source && $wps_order_id ) : ?>
							<a href="<?php echo esc_url( wc_get_endpoint_url( 'view-order', $wps_order_id, wc_get_page_permalink( 'myaccount' ) ) ); ?>">
								<?php esc_html_e( 'Order', 'subscriptions-for-woocommerce' ); ?>
								<?php echo ' #' . esc_html( (string) $wps_order_id ); ?>
							</a>
						<?php elseif ( 'subscription' === $wps_source ) : ?>
							<?php esc_html_e( 'Subscription', 'subscriptions-for-woocommerce' ); ?>
						<?php elseif ( 'order' === $wps_source ) : ?>
							<?php esc_html_e( 'Order', 'subscriptions-for-woocommerce' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Manual', 'subscriptions-for-woocommerce' ); ?>
						<?php endif; ?>
					</td>

				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

<?php endif; ?>

<?php
/**
 * Fires after the memberships table is output.
 *
 * @since 2.0.0
 */
do_action( 'wps_after_myaccount_memberships' );
?>
