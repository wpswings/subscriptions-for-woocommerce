<?php
/**
 * AI Settings tab.
 *
 * @package Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$provider          = sanitize_key( get_option( 'wps_ai_provider', '' ) );
$providers         = wps_ai_provider()->get_supported_providers();
$is_pro_active     = (bool) apply_filters( 'wsp_sfw_check_pro_plugin', false );
$main_ai_enabled   = '1' === (string) get_option( 'wps_ai_main_enabled', '0' );
$model_options     = array(
	'openai'      => array(
		'gpt-4o'       => __( 'gpt-4o (Default)', 'subscriptions-for-woocommerce' ),
		'gpt-4o-mini'  => __( 'gpt-4o-mini', 'subscriptions-for-woocommerce' ),
		'gpt-4-turbo'  => __( 'gpt-4-turbo', 'subscriptions-for-woocommerce' ),
	),
	'anthropic'   => array(
		'claude-sonnet-4-5'        => __( 'claude-sonnet-4-5 (Default)', 'subscriptions-for-woocommerce' ),
		'claude-3-5-sonnet-latest' => __( 'claude-3-5-sonnet-latest', 'subscriptions-for-woocommerce' ),
		'claude-3-haiku-20240307'  => __( 'claude-3-haiku-20240307', 'subscriptions-for-woocommerce' ),
	),
	'huggingface' => array(
		'meta-llama/Llama-3.1-8B-Instruct:fastest' => __( 'meta-llama/Llama-3.1-8B-Instruct:fastest', 'subscriptions-for-woocommerce' ),
	),
	'custom'      => array(
		'' => __( 'Endpoint default', 'subscriptions-for-woocommerce' ),
	),
);

?>
<form method="post" class="wps-ai-settings-form">
	<?php wp_nonce_field( WPS_AI_Settings::NONCE_ACTION, 'wps-ai-settings-nonce-field' ); ?>

	<section class="wps-sfw-settings-section wps-ai-settings-section">
		<div class="wps-sfw-settings-section__head">
			<div class="wps-sfw-settings-section__eyebrow"><?php esc_html_e( 'AI', 'subscriptions-for-woocommerce' ); ?></div>
			<h3><?php esc_html_e( 'Main AI Settings', 'subscriptions-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'Enable subscription AI workflows. AI Insights is available in the free plugin; Churn Analysis, Forecasting, and Smart Cancel are Pro features.', 'subscriptions-for-woocommerce' ); ?></p>
		</div>
		<div class="wps-sfw-settings-section__body">
			<div class="wps-sfw-setting-field">
				<div class="wps-sfw-setting-field__label">
					<label class="wps-sfw-setting-label" for="wps_ai_main_enabled"><?php esc_html_e( 'Enable AI Settings', 'subscriptions-for-woocommerce' ); ?></label>
					<span class="wps-sfw-setting-subtext"><?php esc_html_e( 'When enabled, AI Insights summarizes store health, Churn Analysis highlights cancellation risk, Forecasting estimates recurring revenue trends, and Smart Cancel can prepare retention offers before cancellation.', 'subscriptions-for-woocommerce' ); ?></span>
				</div>
				<div class="wps-sfw-setting-field__control">
					<label class="wps-sfw-toggle" for="wps_ai_main_enabled">
						<input class="wps-sfw-toggle__input" name="wps_ai_main_enabled" id="wps_ai_main_enabled" type="checkbox" value="1" role="switch" <?php checked( $main_ai_enabled ); ?>>
						<span class="wps-sfw-toggle__track" aria-hidden="true"><span class="wps-sfw-toggle__thumb"></span></span>
						<span class="wps-sfw-toggle__text"><?php echo esc_html( $main_ai_enabled ? __( 'Enabled', 'subscriptions-for-woocommerce' ) : __( 'Disabled', 'subscriptions-for-woocommerce' ) ); ?></span>
					</label>
				</div>
			</div>
			<div class="wps-ai-workflow-summary">
				<div class="wps-ai-workflow-summary__item">
					<strong><?php esc_html_e( 'AI Insights', 'subscriptions-for-woocommerce' ); ?></strong>
					<span><?php esc_html_e( 'Summarizes subscription status counts and highlights store health alerts in plain language.', 'subscriptions-for-woocommerce' ); ?></span>
				</div>
				<div class="wps-ai-workflow-summary__item<?php echo $is_pro_active ? '' : ' wps_pro_settings_tag wps-ai-pro-locked'; ?>">
					<strong><?php esc_html_e( 'Churn Analysis', 'subscriptions-for-woocommerce' ); ?></strong>
					<span><?php esc_html_e( 'Reviews subscription behavior to help identify customers at risk of cancelling.', 'subscriptions-for-woocommerce' ); ?></span>
				</div>
				<div class="wps-ai-workflow-summary__item<?php echo $is_pro_active ? '' : ' wps_pro_settings_tag wps-ai-pro-locked'; ?>">
					<strong><?php esc_html_e( 'Forecasting', 'subscriptions-for-woocommerce' ); ?></strong>
					<span><?php esc_html_e( 'Estimates recurring revenue trends so store owners can understand upcoming subscription performance.', 'subscriptions-for-woocommerce' ); ?></span>
				</div>
				<div class="wps-ai-workflow-summary__item<?php echo $is_pro_active ? '' : ' wps_pro_settings_tag wps-ai-pro-locked'; ?>">
					<strong><?php esc_html_e( 'Smart Cancel', 'subscriptions-for-woocommerce' ); ?></strong>
					<span><?php esc_html_e( 'Prepares retention guidance and offer defaults before a customer cancellation is completed.', 'subscriptions-for-woocommerce' ); ?></span>
				</div>
			</div>
		</div>
	</section>

	<section class="wps-sfw-settings-section wps-ai-settings-section">
		<div class="wps-sfw-settings-section__head">
			<div class="wps-sfw-settings-section__eyebrow"><?php esc_html_e( 'Provider', 'subscriptions-for-woocommerce' ); ?></div>
			<h3><?php esc_html_e( 'AI Provider Connection', 'subscriptions-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'Choose the hosted AI service used by subscription AI workflows. API keys are used only in server-side requests.', 'subscriptions-for-woocommerce' ); ?></p>
		</div>
		<div class="wps-sfw-settings-section__body">
			<div class="wps-sfw-setting-field">
				<div class="wps-sfw-setting-field__label">
					<label class="wps-sfw-setting-label" for="wps_ai_provider"><?php esc_html_e( 'AI Provider', 'subscriptions-for-woocommerce' ); ?></label>
					<span class="wps-sfw-setting-subtext"><?php esc_html_e( 'Select one provider before saving settings.', 'subscriptions-for-woocommerce' ); ?></span>
				</div>
				<div class="wps-sfw-setting-field__control">
					<div class="wps-sfw-input-group wps-sfw-input-group--select">
						<select class="wps-sfw-select" name="wps_ai_provider" id="wps_ai_provider" data-wps-ai-provider>
							<option value=""><?php esc_html_e( 'Select a provider', 'subscriptions-for-woocommerce' ); ?></option>
							<?php foreach ( $providers as $provider_key => $provider_label ) : ?>
								<option value="<?php echo esc_attr( $provider_key ); ?>" <?php selected( $provider, $provider_key ); ?>><?php echo esc_html( $provider_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>

			<?php foreach ( $providers as $provider_key => $provider_label ) : ?>
				<?php
				$stored_key = get_option( 'wps_ai_api_key_' . $provider_key, '' );
				$masked_key = wps_ai_provider()->mask_api_key( $stored_key );
				$model      = wps_ai_provider()->get_model( $provider_key );
				$provider_model_options = isset( $model_options[ $provider_key ] ) ? $model_options[ $provider_key ] : array();
				$is_custom_model = '' !== $model && ! isset( $provider_model_options[ $model ] );
				$selected_model  = $is_custom_model ? '__custom' : $model;
				$provider_model_options['__custom'] = __( 'Custom', 'subscriptions-for-woocommerce' );
				?>
				<div class="wps-ai-provider-panel" data-wps-ai-provider-panel="<?php echo esc_attr( $provider_key ); ?>">
					<div class="wps-sfw-setting-field">
						<div class="wps-sfw-setting-field__label">
							<label class="wps-sfw-setting-label" for="wps_ai_api_key_<?php echo esc_attr( $provider_key ); ?>"><?php echo esc_html( $provider_label ); ?> <?php esc_html_e( 'API Key', 'subscriptions-for-woocommerce' ); ?></label>
							<span class="wps-sfw-setting-subtext"><?php esc_html_e( 'Leave blank to keep the saved key.', 'subscriptions-for-woocommerce' ); ?></span>
						</div>
						<div class="wps-sfw-setting-field__control">
							<div class="wps-sfw-input-group">
								<span class="wps-sfw-input-label"><?php echo $masked_key ? esc_html( $masked_key ) : esc_html__( 'No key saved', 'subscriptions-for-woocommerce' ); ?></span>
								<input class="wps-sfw-input" name="wps_ai_api_key_<?php echo esc_attr( $provider_key ); ?>" id="wps_ai_api_key_<?php echo esc_attr( $provider_key ); ?>" type="password" value="" autocomplete="new-password" data-wps-ai-api-key>
							</div>
						</div>
					</div>

					<div class="wps-sfw-setting-field">
						<div class="wps-sfw-setting-field__label">
							<label class="wps-sfw-setting-label" for="wps_ai_model_<?php echo esc_attr( $provider_key ); ?>"><?php esc_html_e( 'Model Name', 'subscriptions-for-woocommerce' ); ?></label>
							<span class="wps-sfw-setting-subtext"><?php esc_html_e( 'Choose one of the supported models for the selected provider.', 'subscriptions-for-woocommerce' ); ?></span>
						</div>
						<div class="wps-sfw-setting-field__control">
							<div class="wps-sfw-input-group wps-sfw-input-group--select">
									<span class="wps-sfw-input-label"><?php esc_html_e( 'Model', 'subscriptions-for-woocommerce' ); ?></span>
									<select class="wps-sfw-select" name="wps_ai_model_<?php echo esc_attr( $provider_key ); ?>" id="wps_ai_model_<?php echo esc_attr( $provider_key ); ?>" data-wps-ai-model>
										<?php foreach ( $provider_model_options as $model_key => $model_label ) : ?>
											<option value="<?php echo esc_attr( $model_key ); ?>" <?php selected( $selected_model, $model_key ); ?>><?php echo esc_html( $model_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="wps-sfw-input-group wps-ai-custom-model-field" data-wps-ai-custom-model-wrap>
									<span class="wps-sfw-input-label"><?php esc_html_e( 'Custom model', 'subscriptions-for-woocommerce' ); ?></span>
									<input class="wps-sfw-input" name="wps_ai_model_<?php echo esc_attr( $provider_key ); ?>_custom" id="wps_ai_model_<?php echo esc_attr( $provider_key ); ?>_custom" type="text" value="<?php echo esc_attr( $is_custom_model ? $model : '' ); ?>" placeholder="<?php esc_attr_e( 'Enter custom model name', 'subscriptions-for-woocommerce' ); ?>" data-wps-ai-custom-model>
								</div>
							</div>
						</div>
					</div>
			<?php endforeach; ?>

			<div class="wps-ai-provider-panel" data-wps-ai-provider-panel="custom">
				<div class="wps-sfw-setting-field">
					<div class="wps-sfw-setting-field__label">
						<label class="wps-sfw-setting-label" for="wps_ai_custom_endpoint"><?php esc_html_e( 'Custom Endpoint URL', 'subscriptions-for-woocommerce' ); ?></label>
						<span class="wps-sfw-setting-subtext"><?php esc_html_e( 'Must be a full HTTPS endpoint that accepts the standard WPS AI JSON payload.', 'subscriptions-for-woocommerce' ); ?></span>
					</div>
					<div class="wps-sfw-setting-field__control">
						<div class="wps-sfw-input-group">
							<span class="wps-sfw-input-label"><?php esc_html_e( 'Endpoint', 'subscriptions-for-woocommerce' ); ?></span>
							<input class="wps-sfw-input" name="wps_ai_custom_endpoint" id="wps_ai_custom_endpoint" type="url" value="<?php echo esc_attr( get_option( 'wps_ai_custom_endpoint', '' ) ); ?>" placeholder="https://example.com/ai/complete" data-wps-ai-custom-endpoint>
						</div>
					</div>
				</div>
			</div>

			<div class="wps-ai-test-row">
				<button type="button" class="wps-sfw-secondary-action" data-wps-ai-test-connection><?php esc_html_e( 'Test Connection', 'subscriptions-for-woocommerce' ); ?></button>
				<span class="wps-ai-test-status" data-wps-ai-test-status aria-live="polite"></span>
			</div>
		</div>
	</section>


	<div class="wps-ai-privacy-note">
		<strong><?php esc_html_e( 'Privacy note:', 'subscriptions-for-woocommerce' ); ?></strong>
		<?php esc_html_e( 'AI analysis data is anonymised before being sent to the AI provider. No customer names, email addresses, or payment details are transmitted.', 'subscriptions-for-woocommerce' ); ?>
	</div>

	<div class="wps-sfw-settings-actions">
		<button type="submit" class="wps-sfw-save-button" name="wps_ai_save_settings" id="wps_ai_save_settings"><?php esc_html_e( 'Save AI Settings', 'subscriptions-for-woocommerce' ); ?></button>
	</div>
</form>
