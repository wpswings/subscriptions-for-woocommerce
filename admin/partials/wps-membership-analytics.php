<?php
/**
 * Membership Analytics — locked teaser (org build).
 *
 * Ships in the free (wordpress.org) plugin so the Pro Analytics feature is
 * always discoverable. Renders the real layout with illustrative sample data,
 * dimmed and non-interactive, behind an "Upgrade to Pro" call to action. When
 * the Pro plugin is active it overrides the sub-tab partial via
 * `wps_membership_sub_tab_partials` and this teaser is never reached.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
	wp_die( esc_html__( 'You do not have permission to view this page.', 'subscriptions-for-woocommerce' ) );
}

$wps_upgrade_url = 'https://wpswings.com/product/subscriptions-for-woocommerce-pro'
	. '?utm_source=wpswings-subs-pro&utm_medium=subs-org-backend&utm_campaign=go-pro';

// Illustrative figures shown only in the locked preview.
$wps_sample_cards = array(
	array(
		'label' => esc_html__( 'New Members', 'subscriptions-for-woocommerce' ),
		'value' => '128',
		'delta' => '+24.0%',
	),
	array(
		'label' => esc_html__( 'Active Members', 'subscriptions-for-woocommerce' ),
		'value' => '1,042',
		'delta' => '+6.0%',
	),
	array(
		'label' => esc_html__( 'Cancellations', 'subscriptions-for-woocommerce' ),
		'value' => '17',
		'delta' => '-11.0%',
	),
	array(
		'label' => esc_html__( 'Membership Revenue', 'subscriptions-for-woocommerce' ),
		'value' => '12,480',
		'delta' => '+18.0%',
	),
);

$wps_sample_rows = array(
	array(
		'name'  => esc_html__( 'Gold', 'subscriptions-for-woocommerce' ),
		'color' => '#e0b020',
		'cols'  => array( '64', '512', '6', '6,200' ),
		'pct'   => 50,
	),
	array(
		'name'  => esc_html__( 'Silver', 'subscriptions-for-woocommerce' ),
		'color' => '#9aa0a6',
		'cols'  => array( '41', '388', '7', '4,100' ),
		'pct'   => 32,
	),
	array(
		'name'  => esc_html__( 'Bronze', 'subscriptions-for-woocommerce' ),
		'color' => '#b06a36',
		'cols'  => array( '23', '142', '4', '2,180' ),
		'pct'   => 18,
	),
);
?>

<div class="wps-an-wrap wps-an-locked">

	<div class="wps-an-upsell">
		<div class="wps-an-upsell__text">
			<span class="wps-an-upsell__badge"><?php esc_html_e( 'PRO', 'subscriptions-for-woocommerce' ); ?></span>
			<h3><?php esc_html_e( 'Membership Analytics', 'subscriptions-for-woocommerce' ); ?></h3>
			<p>
				<?php
				esc_html_e(
					'Track new members, active members, cancellations and revenue across any date range, and compare two periods side by side. Upgrade to Subscriptions for WooCommerce Pro to unlock the live dashboard.',
					'subscriptions-for-woocommerce'
				);
				?>
			</p>
		</div>
		<a class="button button-primary wps-an-upsell__cta" href="<?php echo esc_url( $wps_upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Upgrade to Pro', 'subscriptions-for-woocommerce' ); ?>
		</a>
	</div>

	<div class="wps-ai-pro-locked" aria-hidden="true">

		<div class="wps-an-filters">
			<div class="wps-an-field">
				<label><?php esc_html_e( 'From', 'subscriptions-for-woocommerce' ); ?></label>
				<input type="date" disabled />
			</div>
			<div class="wps-an-field">
				<label><?php esc_html_e( 'To', 'subscriptions-for-woocommerce' ); ?></label>
				<input type="date" disabled />
			</div>
			<div class="wps-an-field">
				<label><?php esc_html_e( 'Compare', 'subscriptions-for-woocommerce' ); ?></label>
				<select disabled>
					<option><?php esc_html_e( 'Previous period', 'subscriptions-for-woocommerce' ); ?></option>
				</select>
			</div>
			<div class="wps-an-field wps-an-field--action">
				<button type="button" class="button button-primary" disabled>
					<?php esc_html_e( 'Apply', 'subscriptions-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

		<div class="wps-an-cards">
			<?php foreach ( $wps_sample_cards as $wps_card ) : ?>
				<div class="wps-an-card">
					<span class="wps-an-card__label"><?php echo esc_html( $wps_card['label'] ); ?></span>
					<span class="wps-an-card__value"><?php echo esc_html( $wps_card['value'] ); ?></span>
					<span class="wps-an-card__delta wps-an-delta--flat"><?php echo esc_html( $wps_card['delta'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wps-an-chart-card">
			<svg class="wps-an-chart-sample" viewBox="0 0 600 220" preserveAspectRatio="none" aria-hidden="true">
				<polyline fill="none" stroke="#8c8f94" stroke-width="2.5" stroke-dasharray="5 4"
					points="0,160 100,150 200,158 300,120 400,128 500,96 600,104" />
				<polyline fill="none" stroke="#2271b1" stroke-width="2.5"
					points="0,150 100,120 200,134 300,86 400,92 500,54 600,40" />
			</svg>
		</div>

		<h3 class="wps-an-section-title"><?php esc_html_e( 'Breakdown by plan', 'subscriptions-for-woocommerce' ); ?></h3>
		<table class="wps-an-table">
			<thead>
				<tr>
					<th class="wps-an-col-plan"><?php esc_html_e( 'Plan', 'subscriptions-for-woocommerce' ); ?></th>
					<th class="wps-an-col-num"><?php esc_html_e( 'New', 'subscriptions-for-woocommerce' ); ?></th>
					<th class="wps-an-col-num"><?php esc_html_e( 'Active', 'subscriptions-for-woocommerce' ); ?></th>
					<th class="wps-an-col-num"><?php esc_html_e( 'Cancelled', 'subscriptions-for-woocommerce' ); ?></th>
					<th class="wps-an-col-rev"><?php esc_html_e( 'Revenue', 'subscriptions-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $wps_sample_rows as $wps_row ) : ?>
					<tr>
						<td class="wps-an-col-plan">
							<span class="wps-an-dot" style="background:<?php echo esc_attr( $wps_row['color'] ); ?>;"></span>
							<?php echo esc_html( $wps_row['name'] ); ?>
						</td>
						<td class="wps-an-col-num"><?php echo esc_html( $wps_row['cols'][0] ); ?></td>
						<td class="wps-an-col-num"><?php echo esc_html( $wps_row['cols'][1] ); ?></td>
						<td class="wps-an-col-num"><?php echo esc_html( $wps_row['cols'][2] ); ?></td>
						<td class="wps-an-col-rev">
							<span class="wps-an-rev-head">
								<span class="wps-an-rev-value"><?php echo esc_html( $wps_row['cols'][3] ); ?></span>
								<span class="wps-an-rev-pct"><?php echo esc_html( $wps_row['pct'] ); ?>%</span>
							</span>
							<span class="wps-an-rev-bar" aria-hidden="true">
								<span class="wps-an-rev-bar__fill"
									style="width:<?php echo esc_attr( $wps_row['pct'] ); ?>%;background:<?php echo esc_attr( $wps_row['color'] ); ?>;"></span>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	</div>

</div>
