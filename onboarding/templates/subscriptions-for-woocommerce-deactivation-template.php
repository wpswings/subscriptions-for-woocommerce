<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpswings.com
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/onboarding
 */

global $pagenow, $sfw_wps_sfw_obj;
if ( empty( $pagenow ) || 'plugins.php' != $pagenow ) {
	return false;
}

$sfw_onboarding_form_deactivate = apply_filters( 'wps_sfw_deactivation_form_fields', array() );
?>
<?php if ( ! empty( $sfw_onboarding_form_deactivate ) ) : ?>
	<div class="mdc-dialog mdc-dialog--scrollable wps-sfw-on-boarding-dialog">
		<div class="wps-sfw-on-boarding-wrapper-background mdc-dialog__container">
			<div class="wps-sfw-on-boarding-wrapper mdc-dialog__surface" role="alertdialog" aria-modal="true" aria-labelledby="my-dialog-title" aria-describedby="my-dialog-content">
				<div class="mdc-dialog__content">
					<div class="wps-sfw-on-boarding-close-btn">
						<a href="#">
							<span class="sfw-close-form material-icons wps-sfw-close-icon mdc-dialog__button" data-mdc-dialog-action="close">clear</span>
						</a>
					</div>

					<h3 class="wps-sfw-on-boarding-heading mdc-dialog__title"></h3>
					<p class="wps-sfw-on-boarding-desc"><?php esc_html_e( 'May we have a little info about why you are deactivating?', 'subscriptions-for-woocommerce' ); ?></p>
					<form action="#" method="post" class="wps-sfw-on-boarding-form">
						<?php
						$sfw_onboarding_deactive_html = $sfw_wps_sfw_obj->wps_sfw_plug_generate_html( $sfw_onboarding_form_deactivate );
						echo esc_html( $sfw_onboarding_deactive_html );
						?>
						<div class="wps-sfw-on-boarding-form-btn__wrapper mdc-dialog__actions">
							<div class="wps-sfw-on-boarding-form-submit wps-sfw-on-boarding-form-verify ">
								<input type="submit" class="wps-sfw-on-boarding-submit wps-on-boarding-verify mdc-button mdc-button--raised" value="Send Us">
							</div>
							<div class="wps-sfw-on-boarding-form-no_thanks">
								<a href="#" class="wps-sfw-deactivation-no_thanks mdc-button"><?php esc_html_e( 'Skip and Deactivate Now', 'subscriptions-for-woocommerce' ); ?></a>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="mdc-dialog__scrim"></div>
	</div>
<?php endif; ?>
