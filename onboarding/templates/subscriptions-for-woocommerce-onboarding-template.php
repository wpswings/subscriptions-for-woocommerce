<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://makewebbetter.com
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/onboarding
 */

global $sfw_mwb_sfw_obj;
$sfw_onboarding_form_fields = apply_filters( 'mwb_sfw_on_boarding_form_fields', array() );
?>

<?php if ( ! empty( $sfw_onboarding_form_fields ) ) : ?>
	<div class="mdc-dialog mdc-dialog--scrollable mwb-sfw-on-boarding-dialog">
		<div class="mwb-sfw-on-boarding-wrapper-background mdc-dialog__container">
			<div class="mwb-sfw-on-boarding-wrapper mdc-dialog__surface" role="alertdialog" aria-modal="true" aria-labelledby="my-dialog-title" aria-describedby="my-dialog-content">
				<div class="mdc-dialog__content">
					<div class="mwb-sfw-on-boarding-close-btn">
						<a href="#"><span class="sfw-close-form material-icons mwb-sfw-close-icon mdc-dialog__button" data-mdc-dialog-action="close">clear</span></a>
					</div>

					<h3 class="mwb-sfw-on-boarding-heading mdc-dialog__title"><?php esc_html_e( 'Welcome to MakeWebBetter', 'subscriptions-for-woocommerce' ); ?> </h3>
					<p class="mwb-sfw-on-boarding-desc"><?php esc_html_e( 'We love making new friends! Subscribe below and we promise to keep you up-to-date with our latest new plugins, updates, awesome deals and a few special offers.', 'subscriptions-for-woocommerce' ); ?></p>

					<form action="#" method="post" class="mwb-sfw-on-boarding-form">
						<?php
						$sfw_onboarding_html = $sfw_mwb_sfw_obj->mwb_sfw_plug_generate_html( $sfw_onboarding_form_fields );
						echo esc_html( $sfw_onboarding_html );
						?>
						<div class="mwb-sfw-on-boarding-form-btn__wrapper mdc-dialog__actions">
							<div class="mwb-sfw-on-boarding-form-submit mwb-sfw-on-boarding-form-verify ">
								<input type="submit" class="mwb-sfw-on-boarding-submit mwb-on-boarding-verify mdc-button mdc-button--raised" value="Send Us">
							</div>
							<div class="mwb-sfw-on-boarding-form-no_thanks">
								<a href="#" class="mwb-sfw-on-boarding-no_thanks mdc-button" data-mdc-dialog-action="discard"><?php esc_html_e( 'Skip For Now', 'subscriptions-for-woocommerce' ); ?></a>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="mdc-dialog__scrim"></div>
	</div>
<?php endif; ?>
