<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for general tab.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $sfw_wps_sfw_obj;

?>
<!--  template file for admin settings. -->
<div class="sfw-section-wrap">
	<div class="wps_sfw_table_wrapper wps_sfw_overview-wrapper">
		<div class="sfw-overview__wrapper">
			<div class="sfw-overview__icons">
				<a href="https://wpswings.com/submit-query/?utm_source=wpswings-subs-support&utm_medium=subs-org-backend&utm_campaign=support">
					<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/dial.svg' ); ?>" alt="contact-us-img">
				</a>
				<a href="https://docs.wpswings.com/subscriptions-for-woocommerce/?utm_source=wpswings-subs-doc&utm_medium=subs-org-backend&utm_campaign=documentation">
					<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/doc.svg' ); ?>" alt="doc-img">
				</a>
				<?php
				// Add icon.
				do_action( 'wps_sfw_overview_icon' );
				?>
			</div>
			<div class="sfw-overview__banner-img">
				<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/org-banner.jpg' ); ?>" alt="sfw-banner-img">
			</div>
			<?php
				// Add content.
				do_action( 'wps_sfw_before_overview_content' );
			?>
			
			<div class="sfw-overview__content">
				<div class="sfw-overview__content-description">
					<h1><?php esc_html_e( 'Subscriptions for WooCommerce', 'subscriptions-for-woocommerce' ); ?></h1>
					<p> <?php esc_html_e( 'Subscriptions for WooCommerce Plugin allows the WooCommerce merchants to provide their products or services regularly through subscription programs. Thus, helping in collecting the recurring revenue of your store.', 'subscriptions-for-woocommerce' ); ?>
					</p>
					<div class="wps_sfw_video_wrapper">
						<iframe src="https://www.youtube.com/embed/2VFyxZl3l-A" frameborder="0" allow="fullscreen accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
					</div>
					<div class="sfw-overview__features">
						<h2><?php esc_html_e( 'What does Subscriptions for WooCommerce do?', 'subscriptions-for-woocommerce' ); ?>
					</h2>
					<p><?php esc_html_e( 'With our Subscriptions for WooCommerce Plugin, you can:', 'subscriptions-for-woocommerce' ); ?></p>
					<ul class="sfw-overview__features-list">
						<li><?php esc_html_e( 'Provide subscriptions on physical products, and virtual or downloadable products', 'subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Generate trouble-free recurring revenue', 'subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Sell recurring services for a set period', 'subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Convert simple product selling WooCommerce store to subscription-based', 'subscriptions-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Give free trials to your customers and loyalize them', 'subscriptions-for-woocommerce' ); ?></li>
					</ul>
					<?php
						// Add description.
						do_action( 'wps_sfw_overview_feature_description' );
					?>
					</div>
				</div>
				<?php
					// Add overview description.
					do_action( 'wps_sfw_after_overview_description' );
				?>
				<div class="sfw-overview__keywords-wrap">
				<h2> <?php esc_html_e( 'Salient Features of Subscriptions for WooCommerce Plugin', 'subscriptions-for-woocommerce' ); ?></h2>
				<div class="sfw-overview__keywords">
					<div class="sfw-overview__keywords-item">
						<div class="sfw-overview__keywords-card">
							<div class="sfw-overview__keywords-text">

								<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/feature-1.png' ); ?>" alt="feature_one" width="100px">
								<h4 class="sfw-overview__keywords-heading"><?php esc_html_e( 'Create Simple Products  As Subscription Product', 'subscriptions-for-woocommerce' ); ?> </h4>
								<p class="sfw-overview__keywords-description">
									<?php esc_html_e( 'You can easily assign a subscription product label to simple product type by simply ticking a checkbox. The product will then be available as a subscription product.', 'subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="sfw-overview__keywords-item">
						<div class="sfw-overview__keywords-card">
							<div class="sfw-overview__keywords-text">
								<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/feature-2.png' ); ?>" alt="feature_two" width="100px">
								<h4 class="sfw-overview__keywords-heading"><?php esc_html_e( 'Offer Subscription Frequency and Expiry', 'subscriptions-for-woocommerce' ); ?></h4>
								<p class="sfw-overview__keywords-description">
									
									<?php esc_html_e( 'You can set the frequency for subscribed products for the user. Recurrence can be regulated as monthly, weekly, or yearly. The recurring payments will be done according to the frequency plan. You can set the expiration date of the subscription plan. And, for extending this subscription plan can be renewed.', 'subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="sfw-overview__keywords-item">
						<div class="sfw-overview__keywords-card">
							<div class="sfw-overview__keywords-text">
								<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/feature-3.png' ); ?>" alt="feature_three" width="100px">
								<h4 class="sfw-overview__keywords-heading"><?php esc_html_e( 'Charge Initial Fee with WooCommerce Payment Integrations', 'subscriptions-for-woocommerce' ); ?></h4>
								<p class="sfw-overview__keywords-description">
									<?php esc_html_e( 'You can charge extra payment in the form of an initial fee. Stripe payment integration of WooCommerce is supported with the subscription.', 'subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="sfw-overview__keywords-item">
						<div class="sfw-overview__keywords-card">
							<div class="sfw-overview__keywords-text">
								<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/feature-4.png' ); ?>" alt="feature_four" width="100px">
								<h4 class="sfw-overview__keywords-heading"><?php esc_html_e( 'Offer Free Trial To Users With Both User and Admin Stop Subscription Option', 'subscriptions-for-woocommerce' ); ?></h4>
								<p class="sfw-overview__keywords-description">
									
									<?php esc_html_e( 'Provide free trials to the users and take payments after it is over for continued subscription plans. The flexibility of ending the subscription by the admin or the user anytime. Both can stop subscriptions of products or services in easy steps.', 'subscriptions-for-woocommerce' ); ?>
 
								</p>
							</div>
						</div>
					</div>
					<div class="sfw-overview__keywords-item">
						<div class="sfw-overview__keywords-card">
							<div class="sfw-overview__keywords-text">
								<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/feature-5.png' ); ?>" alt="feature_five" width="100px">
								<h4 class="sfw-overview__keywords-heading"><?php esc_html_e( 'Complete Subscription Reports', 'subscriptions-for-woocommerce' ); ?></h4>
								<p class="sfw-overview__keywords-description">
									<?php esc_html_e( 'With a clean subscription report module, you will get complete subscription data of all users. Find important details like active and inactive subscriptions, next payment dates, product names, subscription expiry dates, and due dates of the respective plans.', 'subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<div class="sfw-overview__keywords-item">
						<div class="sfw-overview__keywords-card">
							<div class="sfw-overview__keywords-text">
								<img src="<?php echo esc_url( SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/feature-6.png' ); ?>" alt="feature_six" width="100px">
								<h4 class="sfw-overview__keywords-heading"><?php esc_html_e( 'Subscription Details To User and Admin', 'subscriptions-for-woocommerce' ); ?></h4>
								<p class="sfw-overview__keywords-description">
									<?php esc_html_e( 'View all details of the subscription plans of every user of your store. The user can see his subscription plan details and history', 'subscriptions-for-woocommerce' ); ?>
								</p>
							</div>
						</div>
					</div>
					<?php
						// Add overview description.
						do_action( 'wps_sfw_overview_keywords_description' );
					?>
				</div>
				</div>
				<?php
					// Add overview description.
					do_action( 'wps_sfw_after_overview_keywords_wrpa' );
				?>
			</div>
		</div>
	</div>
</div>





