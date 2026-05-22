<?php
/**
 * AI Insights dashboard widget markup.
 *
 * @package Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$counts    = isset( $state['counts'] ) && is_array( $state['counts'] ) ? $state['counts'] : array();
$statuses  = isset( $counts['statuses'] ) && is_array( $counts['statuses'] ) ? $counts['statuses'] : array();
$result          = isset( $state['result'] ) && is_array( $state['result'] ) ? $state['result'] : array();
$groups          = isset( $result['groups'] ) && is_array( $result['groups'] ) ? $result['groups'] : array();
$generated_label = isset( $result['generated_label'] ) ? $result['generated_label'] : __( 'Generated time unavailable', 'subscriptions-for-woocommerce' );
$recommendation_count = 0;
foreach ( $groups as $group ) {
	$recommendation_count += isset( $group['items'] ) && is_array( $group['items'] ) ? count( $group['items'] ) : 0;
}
?>
<div class="wps-ai-health-widget" data-wps-ai-health-widget>
	<div class="wps-ai-health-header">
		<div class="wps-ai-health-header__main">
			<span class="wps-ai-health-spark" aria-hidden="true">✦</span>
			<strong><?php esc_html_e( 'AI Insights', 'subscriptions-for-woocommerce' ); ?></strong>
			<span class="wps-ai-health-badge" data-wps-ai-health-recommendation-count>
				<?php
				printf(
					/* translators: %d: recommendation count */
					esc_html__( '%d recommendations', 'subscriptions-for-woocommerce' ),
					$recommendation_count
				);
				?>
			</span>
			<span class="wps-ai-health-generated" data-wps-ai-health-generated><?php echo esc_html( $generated_label ); ?></span>
		</div>
		<button type="button" class="button button-secondary" data-wps-ai-health-refresh <?php disabled( empty( $state['ready'] ) ); ?>><?php esc_html_e( 'Regenerate', 'subscriptions-for-woocommerce' ); ?></button>
	</div>
	<p class="wps-ai-health-subtitle"><?php esc_html_e( 'Short recommendations from aggregated subscription KPIs.', 'subscriptions-for-woocommerce' ); ?></p>

	<?php if ( empty( $state['ready'] ) ) : ?>
		<div class="wps-ai-health-notice">
			<strong><?php esc_html_e( 'Setup required', 'subscriptions-for-woocommerce' ); ?></strong>
			<span><?php echo esc_html( isset( $state['message'] ) ? $state['message'] : __( 'Complete AI Settings to enable AI Insights.', 'subscriptions-for-woocommerce' ) ); ?></span>
			<a href="<?php echo esc_url( $state['settings_url'] ); ?>"><?php esc_html_e( 'Open AI Settings', 'subscriptions-for-woocommerce' ); ?></a>
		</div>
	<?php elseif ( ! empty( $state['error'] ) ) : ?>
		<div class="wps-ai-health-notice wps-ai-health-notice--error">
			<strong><?php esc_html_e( 'AI Insights unavailable', 'subscriptions-for-woocommerce' ); ?></strong>
			<span><?php echo esc_html( $state['message'] ); ?></span>
		</div>
	<?php endif; ?>

	<div class="wps-ai-health-kpis">
		<div class="wps-ai-health-kpi">
			<span><?php esc_html_e( 'Active', 'subscriptions-for-woocommerce' ); ?></span>
			<strong data-wps-ai-health-count="active"><?php echo esc_html( isset( $statuses['active'] ) ? absint( $statuses['active'] ) : 0 ); ?></strong>
		</div>
		<div class="wps-ai-health-kpi">
			<span><?php esc_html_e( 'On Hold', 'subscriptions-for-woocommerce' ); ?></span>
			<strong data-wps-ai-health-count="on-hold"><?php echo esc_html( isset( $statuses['on-hold'] ) ? absint( $statuses['on-hold'] ) : 0 ); ?></strong>
		</div>
		<div class="wps-ai-health-kpi">
			<span><?php esc_html_e( 'New This Week', 'subscriptions-for-woocommerce' ); ?></span>
			<strong data-wps-ai-health-count="new_this_week"><?php echo esc_html( isset( $counts['new_this_week'] ) ? absint( $counts['new_this_week'] ) : 0 ); ?></strong>
		</div>
		<div class="wps-ai-health-kpi">
			<span><?php esc_html_e( 'Expiring Soon', 'subscriptions-for-woocommerce' ); ?></span>
			<strong data-wps-ai-health-count="expiring_soon"><?php echo esc_html( isset( $counts['expiring_soon'] ) ? absint( $counts['expiring_soon'] ) : 0 ); ?></strong>
		</div>
		<div class="wps-ai-health-kpi">
			<span><?php esc_html_e( 'Upcoming Renewal', 'subscriptions-for-woocommerce' ); ?></span>
			<strong data-wps-ai-health-count="upcoming_renewal"><?php echo esc_html( isset( $counts['upcoming_billings_30_days'] ) ? absint( $counts['upcoming_billings_30_days'] ) : 0 ); ?></strong>
		</div>
	</div>

	<div class="wps-ai-health-insights" data-wps-ai-health-groups>
		<?php foreach ( $groups as $group ) : ?>
			<?php
			$key   = isset( $group['key'] ) ? sanitize_key( $group['key'] ) : 'actions';
			$items = isset( $group['items'] ) && is_array( $group['items'] ) ? $group['items'] : array();
			?>
			<div class="wps-ai-health-insight-card wps-ai-health-insight-card--<?php echo esc_attr( $key ); ?>">
				<div class="wps-ai-health-insight-card__head">
					<span class="wps-ai-health-insight-card__dot" aria-hidden="true"></span>
					<strong><?php echo esc_html( isset( $group['title'] ) ? $group['title'] : '' ); ?></strong>
					<span><?php echo esc_html( count( $items ) ); ?></span>
				</div>
				<p><?php echo esc_html( isset( $group['description'] ) ? $group['description'] : '' ); ?></p>
				<ul>
					<?php foreach ( $items as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="wps-ai-health-footer">
		<span><?php esc_html_e( 'Cached until regenerated. Only aggregated KPIs are sent to your AI provider.', 'subscriptions-for-woocommerce' ); ?></span>
	</div>
</div>
