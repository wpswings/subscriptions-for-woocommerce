<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for general tab.
 *
 * @link       https://makewebbetter.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $sfw_mwb_sfw_obj;

?>
<!--  template file for admin settings. -->
<div class="sfw-section-wrap">
	<div class="mwb_wgm_table_wrapper mwb_wgm_overview-wrapper">
		<div class="mwb_wgm_overview_content">
			<h3 class="mwb_wgm_overview_heading"><?php esc_html_e( 'Connect With Us and Explore More About Ultimate Gift Cards For WooCommerce', 'woo-gift-cards-lite' ); ?></h3>
			<p><?php esc_html_e( 'Ultimate Gift Cards For WooCommerce is the plugin that allows merchants (admin) to manage store with digital gifting solutions like this. Here the merchant can create gifts cards according to his desires and wishes after selection of the price selection. This digital certificate e-solution comes with ample number benefits like capable to increase sales, encourage an easy and desire gifting solution for your customers, initiate e-gifting via emails. ', 'woo-gift-cards-lite' ); ?></p>
		</div>
		<div class="mwb_wgm_video_wrapper">
			<iframe height="411" src="https://www.youtube.com/embed/YgPLO8HDGtc" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
		</div>
	</div>
	<?php

	?>
</div>