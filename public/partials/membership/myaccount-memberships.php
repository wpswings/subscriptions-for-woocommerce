<?php
/**
 * My Account — Memberships tab template.
 *
 * Displays the current customer's membership records as a card grid.
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
 * Fires before the memberships card grid is output.
 *
 * @since 2.0.0
 */
do_action( 'wps_before_myaccount_memberships' );

$wps_date_format = get_option( 'date_format' );

$wps_status_map = array(
	'active'    => array(
		'label' => __( 'Active', 'subscriptions-for-woocommerce' ),
		'mod'   => 'active',
	),
	'on-hold'   => array(
		'label' => __( 'On Hold', 'subscriptions-for-woocommerce' ),
		'mod'   => 'on-hold',
	),
	'cancelled' => array(
		'label' => __( 'Cancelled', 'subscriptions-for-woocommerce' ),
		'mod'   => 'cancelled',
	),
	'expired'   => array(
		'label' => __( 'Expired', 'subscriptions-for-woocommerce' ),
		'mod'   => 'expired',
	),
	'paused'    => array(
		'label' => __( 'Paused', 'subscriptions-for-woocommerce' ),
		'mod'   => 'paused',
	),
);
?>

<h2 class="wps-myaccount-memberships__heading">
	<?php esc_html_e( 'My Memberships', 'subscriptions-for-woocommerce' ); ?>
</h2>

<?php if ( empty( $wps_memberships ) ) : ?>

	<div class="wps-myaccount-empty">
		<svg class="wps-myaccount-empty__icon" xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 24 24" fill="none" stroke="currentColor"
			stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
			aria-hidden="true">
			<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
			<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
		</svg>
		<p class="wps-myaccount-empty__text">
			<?php esc_html_e( 'You don\'t have any memberships yet.', 'subscriptions-for-woocommerce' ); ?>
		</p>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
			class="wps-myaccount-empty__cta button">
			<?php esc_html_e( 'Browse plans', 'subscriptions-for-woocommerce' ); ?>
		</a>
	</div>

<?php else : ?>

	<div class="wps-membership-cards">
		<?php foreach ( $wps_memberships as $wps_row ) : ?>
			<?php
			$wps_slug      = isset( $wps_row['plan_slug'] ) ? $wps_row['plan_slug'] : '';
			$wps_status    = isset( $wps_row['status'] ) ? $wps_row['status'] : 'cancelled';
			$wps_source    = isset( $wps_row['source'] ) ? $wps_row['source'] : 'manual';
			$wps_start_ts  = isset( $wps_row['start_date'] ) ? absint( $wps_row['start_date'] ) : 0;
			$wps_expiry_ts = isset( $wps_row['expiry_date'] ) ? absint( $wps_row['expiry_date'] ) : 0;
			$wps_sub_id    = isset( $wps_row['subscription_id'] ) ? absint( $wps_row['subscription_id'] ) : 0;
			$wps_order_id  = isset( $wps_row['order_id'] ) ? absint( $wps_row['order_id'] ) : 0;

			$wps_plan      = function_exists( 'wps_get_plan_by_slug' )
				? wps_get_plan_by_slug( $wps_slug )
				: null;
			$wps_plan_name = $wps_plan && ! empty( $wps_plan['name'] )
				? $wps_plan['name']
				: ucfirst( $wps_slug );
			$wps_plan_desc = $wps_plan && ! empty( $wps_plan['description'] )
				? wp_strip_all_tags( $wps_plan['description'] )
				: '';
			$wps_color     = $wps_plan && ! empty( $wps_plan['color'] )
				? sanitize_hex_color( $wps_plan['color'] )
				: '#2271b1';
			$wps_method    = $wps_plan && isset( $wps_plan['grant_method'] )
				? $wps_plan['grant_method']
				: 'purchase';
			$wps_al        = $wps_plan && isset( $wps_plan['access_length'] )
				? $wps_plan['access_length']
				: array( 'type' => 'lifetime' );

			// Access duration label.
			if ( 'subscription' === $wps_method ) {
				$wps_duration = __( 'While subscribed', 'subscriptions-for-woocommerce' );
			} elseif ( isset( $wps_al['type'] ) && 'fixed' === $wps_al['type'] && ! empty( $wps_al['value'] ) ) {
				$wps_val       = absint( $wps_al['value'] );
				$wps_unit      = isset( $wps_al['unit'] ) ? $wps_al['unit'] : 'month';
				$wps_unit_lbls = array(
					'day'   => _n( 'day', 'days', $wps_val, 'subscriptions-for-woocommerce' ),
					'month' => _n( 'month', 'months', $wps_val, 'subscriptions-for-woocommerce' ),
					'year'  => _n( 'year', 'years', $wps_val, 'subscriptions-for-woocommerce' ),
				);
				$wps_unit_lbl  = isset( $wps_unit_lbls[ $wps_unit ] ) ? $wps_unit_lbls[ $wps_unit ] : $wps_unit;
				$wps_duration  = sprintf(
					/* translators: 1: number, 2: unit (day/days/month/months etc.) */
					__( '%1$d %2$s', 'subscriptions-for-woocommerce' ),
					$wps_val,
					$wps_unit_lbl
				);
			} else {
				$wps_duration = __( 'Lifetime', 'subscriptions-for-woocommerce' );
			}

			// Expiry display.
			if ( $wps_expiry_ts ) {
				$wps_expiry_str = date_i18n( $wps_date_format, $wps_expiry_ts );
				$wps_is_soon    = ( $wps_expiry_ts - time() ) < 30 * DAY_IN_SECONDS
					&& $wps_expiry_ts > time();
			} else {
				$wps_expiry_str = __( 'Never', 'subscriptions-for-woocommerce' );
				$wps_is_soon    = false;
			}

			$wps_start_str   = $wps_start_ts ? date_i18n( $wps_date_format, $wps_start_ts ) : '—';
			$wps_status_info = isset( $wps_status_map[ $wps_status ] )
				? $wps_status_map[ $wps_status ]
				: array(
					'label' => ucfirst( $wps_status ),
					'mod'   => 'cancelled',
				);

			$wps_is_active = in_array( $wps_status, array( 'active', 'on-hold' ), true );

			// Plan monogram — carries the plan color as the card's visual anchor.
			$wps_initial = function_exists( 'mb_substr' )
				? mb_strtoupper( mb_substr( $wps_plan_name, 0, 1 ) )
				: strtoupper( substr( $wps_plan_name, 0, 1 ) );

			// Access-type subtitle shown beneath the plan name.
			if ( 'subscription' === $wps_method ) {
				$wps_kind = __( 'Recurring access', 'subscriptions-for-woocommerce' );
			} elseif ( isset( $wps_al['type'] ) && 'fixed' === $wps_al['type'] ) {
				$wps_kind = sprintf(
					/* translators: %s: access duration, e.g. "20 days" */
					__( '%s access', 'subscriptions-for-woocommerce' ),
					$wps_duration
				);
			} else {
				$wps_kind = __( 'Lifetime access', 'subscriptions-for-woocommerce' );
			}

			// Time-remaining bar — only for fixed-term memberships still running.
			$wps_show_bar  = false;
			$wps_remaining = 0;
			$wps_days_left = 0;
			if ( $wps_is_active && $wps_start_ts && $wps_expiry_ts && $wps_expiry_ts > $wps_start_ts ) {
				$wps_now       = time();
				$wps_span      = $wps_expiry_ts - $wps_start_ts;
				$wps_left_secs = max( 0, $wps_expiry_ts - $wps_now );
				$wps_remaining = (int) round( min( $wps_span, $wps_left_secs ) / $wps_span * 100 );
				$wps_days_left = (int) ceil( $wps_left_secs / DAY_IN_SECONDS );
				$wps_show_bar  = true;
			}
			?>

			<article class="wps-mcard wps-mcard--<?php echo esc_attr( $wps_status_info['mod'] ); ?>"
				style="--plan-color: <?php echo esc_attr( $wps_color ); ?>">

				<div class="wps-mcard__head">
					<span class="wps-mcard__token" aria-hidden="true"><?php echo esc_html( $wps_initial ); ?></span>
					<div class="wps-mcard__id">
						<h3 class="wps-mcard__name"><?php echo esc_html( $wps_plan_name ); ?></h3>
						<span class="wps-mcard__kind"><?php echo esc_html( $wps_kind ); ?></span>
					</div>
					<span class="wps-mcard__status
						wps-mcard__status--<?php echo esc_attr( $wps_status_info['mod'] ); ?>">
						<?php echo esc_html( $wps_status_info['label'] ); ?>
					</span>
				</div>

				<?php if ( $wps_plan_desc ) : ?>
					<p class="wps-mcard__desc"><?php echo esc_html( $wps_plan_desc ); ?></p>
				<?php endif; ?>

				<?php if ( $wps_show_bar ) : ?>
					<?php
					$wps_left_lbl = sprintf(
						/* translators: %d: number of days remaining */
						_n( '%d day left', '%d days left', $wps_days_left, 'subscriptions-for-woocommerce' ),
						$wps_days_left
					);
					?>
					<div class="wps-mcard__bar<?php echo esc_attr( $wps_is_soon ? ' wps-mcard__bar--soon' : '' ); ?>">
						<div class="wps-mcard__bar-info">
							<span class="wps-mcard__bar-left"><?php echo esc_html( $wps_left_lbl ); ?></span>
							<span class="wps-mcard__bar-date">
								<?php
								printf(
									/* translators: %s: expiry date */
									esc_html__( 'Expires %s', 'subscriptions-for-woocommerce' ),
									esc_html( $wps_expiry_str )
								);
								?>
							</span>
						</div>
						<div class="wps-mcard__track">
							<span class="wps-mcard__fill"
								style="width: <?php echo esc_attr( $wps_remaining ); ?>%"></span>
						</div>
					</div>
				<?php endif; ?>

				<dl class="wps-mcard__facts">

					<div class="wps-mcard__fact">
						<dt><?php esc_html_e( 'Started', 'subscriptions-for-woocommerce' ); ?></dt>
						<dd><?php echo esc_html( $wps_start_str ); ?></dd>
					</div>

					<?php if ( ! $wps_show_bar ) : ?>
						<div class="wps-mcard__fact">
							<dt><?php esc_html_e( 'Expires', 'subscriptions-for-woocommerce' ); ?></dt>
							<dd class="<?php echo $wps_is_soon ? 'wps-mcard__fact--soon' : ''; ?>">
								<?php echo esc_html( $wps_expiry_str ); ?>
								<?php if ( $wps_is_soon ) : ?>
									<span class="wps-mcard__soon">
										<?php esc_html_e( 'Soon', 'subscriptions-for-woocommerce' ); ?>
									</span>
								<?php endif; ?>
							</dd>
						</div>
					<?php endif; ?>

					<div class="wps-mcard__fact">
						<dt><?php esc_html_e( 'Source', 'subscriptions-for-woocommerce' ); ?></dt>
						<dd>
							<?php if ( 'subscription' === $wps_source && $wps_sub_id ) : ?>
								<?php
								$wps_src_url = wc_get_endpoint_url(
									'show-subscription',
									$wps_sub_id,
									wc_get_page_permalink( 'myaccount' )
								);
								$wps_src_lbl = sprintf(
									/* translators: %d: subscription ID */
									__( 'Subscription #%d', 'subscriptions-for-woocommerce' ),
									$wps_sub_id
								);
								?>
								<a href="<?php echo esc_url( $wps_src_url ); ?>">
									<?php echo esc_html( $wps_src_lbl ); ?>
								</a>
							<?php elseif ( 'order' === $wps_source && $wps_order_id ) : ?>
								<?php
								$wps_src_url = wc_get_endpoint_url(
									'view-order',
									$wps_order_id,
									wc_get_page_permalink( 'myaccount' )
								);
								$wps_src_lbl = sprintf(
									/* translators: %d: order ID */
									__( 'Order #%d', 'subscriptions-for-woocommerce' ),
									$wps_order_id
								);
								?>
								<a href="<?php echo esc_url( $wps_src_url ); ?>">
									<?php echo esc_html( $wps_src_lbl ); ?>
								</a>
							<?php elseif ( 'subscription' === $wps_source ) : ?>
								<?php esc_html_e( 'Subscription', 'subscriptions-for-woocommerce' ); ?>
							<?php elseif ( 'order' === $wps_source ) : ?>
								<?php esc_html_e( 'Order', 'subscriptions-for-woocommerce' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Manual grant', 'subscriptions-for-woocommerce' ); ?>
							<?php endif; ?>
						</dd>
					</div>

				</dl>

			</article>

		<?php endforeach; ?>
	</div>

<?php endif; ?>

<?php
/**
 * Fires after the memberships card grid is output.
 *
 * @since 2.0.0
 */
do_action( 'wps_after_myaccount_memberships' );
?>
