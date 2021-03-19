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
$sfw_support_settings = apply_filters( 'sfw_supprot_tab_settings_array', array() );
?>
<!--  template file for admin settings. -->
<div class="sfw-section-wrap">
	<?php if ( is_array( $sfw_support_settings ) && ! empty( $sfw_support_settings ) ) { ?>
		<?php foreach ( $sfw_support_settings as $sfw_support_setting ) { ?>
		<div class="mwb-col-wrap">
			<div class="mwb-shadow-panel">
				<div class="content-wrap">
					<div class="content">
						<h3><?php echo esc_html( $sfw_support_setting['title'] ); ?></h3>
						<p><?php echo esc_html( $sfw_support_setting['description'] ); ?></p>
					</div>
					<div class="mdc-button mdc-button--raised mwb-cta-btn"><span class="mdc-button__ripple"></span>
						<a href="<?php echo esc_url( $sfw_support_setting['link'] ); ?>" class="mwb-btn mwb-btn-primary"><?php echo esc_html( $sfw_support_setting['link-text'] ); ?></a>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
	<?php } ?>
</div>
