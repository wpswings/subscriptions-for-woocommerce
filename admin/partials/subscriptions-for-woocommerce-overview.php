<?php
/**
 * Overview tab layout for the redesigned admin experience.
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$overview_features = array(
	array(
		'icon'        => 'dashicons-format-chat',
		'title'       => __( 'Recurring order messaging', 'subscriptions-for-woocommerce' ),
		'description' => __( 'Keep shoppers and support agents aligned across subscription renewals, pauses, upgrades, and account-level questions.', 'subscriptions-for-woocommerce' ),
	),
	array(
		'icon'        => 'dashicons-controls-repeat',
		'title'       => __( 'Flexible billing cycles', 'subscriptions-for-woocommerce' ),
		'description' => __( 'Configure weekly, monthly, or yearly recurring plans with free trials, expiry windows, and renewal logic from one place.', 'subscriptions-for-woocommerce' ),
	),
	array(
		'icon'        => 'dashicons-yes-alt',
		'title'       => __( 'Policy-based renewals', 'subscriptions-for-woocommerce' ),
		'description' => __( 'Apply customer controls, retry rules, and renewal timing logic so recurring commerce follows consistent business rules.', 'subscriptions-for-woocommerce' ),
	),
	array(
		'icon'        => 'dashicons-email-alt',
		'title'       => __( 'Subscription notifications', 'subscriptions-for-woocommerce' ),
		'description' => __( 'Keep merchants and customers updated through subscription email notifications, reminders, and renewal-related communication.', 'subscriptions-for-woocommerce' ),
	),
	array(
		'icon'        => 'dashicons-chart-bar',
		'title'       => __( 'Track subscription performance', 'subscriptions-for-woocommerce' ),
		'description' => __( 'Review active plans, upcoming renewals, expiration dates, and recurring order activity from the subscription management area.', 'subscriptions-for-woocommerce' ),
	),
	array(
		'icon'        => 'dashicons-admin-generic',
		'title'       => __( 'Manage products and renewals', 'subscriptions-for-woocommerce' ),
		'description' => __( 'Create subscription products, control storefront labels, and manage customer-facing subscription workflows without leaving the plugin.', 'subscriptions-for-woocommerce' ),
	),
);
?>

<div class="wps-sfw-overview-page">
	<section class="wps-sfw-overview-hero">
		<div class="wps-sfw-overview-hero__mark" aria-hidden="true">
			<span>Subscriptions For WooCommerce</span>
		</div>
		<p class="wps-sfw-overview-hero__eyebrow"><?php esc_html_e( 'Overview', 'subscriptions-for-woocommerce' ); ?></p>
		<h1><?php esc_html_e( 'Recurring revenue workflows built for WooCommerce teams', 'subscriptions-for-woocommerce' ); ?></h1>
		<p class="wps-sfw-overview-hero__description"><?php esc_html_e( 'Subscriptions for WooCommerce centralizes recurring products, renewal logic, customer self-service controls, and subscription management so your store can run predictable recurring commerce with fewer manual steps.', 'subscriptions-for-woocommerce' ); ?></p>
	</section>

	<section class="wps-sfw-overview-features">
		<div class="wps-sfw-overview-features__title">
			<span><?php esc_html_e( 'Top features of this plugin', 'subscriptions-for-woocommerce' ); ?></span>
		</div>

		<div class="wps-sfw-overview-grid">
			<?php foreach ( $overview_features as $overview_feature ) : ?>
				<article class="wps-sfw-overview-card">
					<div class="wps-sfw-overview-card__icon">
						<span class="dashicons <?php echo esc_attr( $overview_feature['icon'] ); ?>"></span>
					</div>
					<h3><?php echo esc_html( $overview_feature['title'] ); ?></h3>
					<p><?php echo esc_html( $overview_feature['description'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="wps-sfw-overview-cta">
		<div class="wps-sfw-overview-cta__content">
			<h3><?php esc_html_e( 'Facing issues?', 'subscriptions-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'We are ready to help you align subscription operations, recurring payments, customer communication, and renewal workflows.', 'subscriptions-for-woocommerce' ); ?></p>
		</div>
		<a class="wps-sfw-overview-cta__button" href="https://wpswings.com/submit-query/?utm_source=wpswings-subs-support&utm_medium=subs-org-backend&utm_campaign=support" target="_blank"><?php esc_html_e( 'Contact Support', 'subscriptions-for-woocommerce' ); ?></a>
	</section>
</div>
