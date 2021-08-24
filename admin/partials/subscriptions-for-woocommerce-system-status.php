<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html for system status.
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
// Template for showing information about system status.
global $sfw_mwb_sfw_obj;
$sfw_default_status = $sfw_mwb_sfw_obj->mwb_sfw_plug_system_status();
$sfw_wordpress_details = is_array( $sfw_default_status['wp'] ) && ! empty( $sfw_default_status['wp'] ) ? $sfw_default_status['wp'] : array();
$sfw_php_details = is_array( $sfw_default_status['php'] ) && ! empty( $sfw_default_status['php'] ) ? $sfw_default_status['php'] : array();
?>
<div class="mwb-sfw-table-wrap">
	<div class="mwb-col-wrap">
		<div id="mwb-sfw-table-inner-container" class="table-responsive mdc-data-table">
			<div class="mdc-data-table__table-container">
				<table class="mwb-sfw-table mdc-data-table__table mwb-table" id="mwb-sfw-wp">
					<thead>
						<tr>
							<th class="mdc-data-table__header-cell"><?php esc_html_e( 'WP Variables', 'subscriptions-for-woocommerce' ); ?></th>
							<th class="mdc-data-table__header-cell"><?php esc_html_e( 'WP Values', 'subscriptions-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody class="mdc-data-table__content">
						<?php if ( is_array( $sfw_wordpress_details ) && ! empty( $sfw_wordpress_details ) ) { ?>
							<?php foreach ( $sfw_wordpress_details as $wp_key => $wp_value ) { ?>
								<?php if ( isset( $wp_key ) && 'wp_users' != $wp_key ) { ?>
									<tr class="mdc-data-table__row">
										<td class="mdc-data-table__cell"><?php echo esc_html( $wp_key ); ?></td>
										<td class="mdc-data-table__cell"><?php echo esc_html( $wp_value ); ?></td>
									</tr>
								<?php } ?>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="mwb-col-wrap">
		<div id="mwb-sfw-table-inner-container" class="table-responsive mdc-data-table">
			<div class="mdc-data-table__table-container">
				<table class="mwb-sfw-table mdc-data-table__table mwb-table" id="mwb-sfw-sys">
					<thead>
						<tr>
							<th class="mdc-data-table__header-cell"><?php esc_html_e( 'System Variables', 'subscriptions-for-woocommerce' ); ?></th>
							<th class="mdc-data-table__header-cell"><?php esc_html_e( 'System Values', 'subscriptions-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody class="mdc-data-table__content">
						<?php if ( is_array( $sfw_php_details ) && ! empty( $sfw_php_details ) ) { ?>
							<?php foreach ( $sfw_php_details as $php_key => $php_value ) { ?>
								<tr class="mdc-data-table__row">
									<td class="mdc-data-table__cell"><?php echo esc_html( $php_key ); ?></td>
									<td class="mdc-data-table__cell"><?php echo esc_html( $php_value ); ?></td>
								</tr>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php 
	global $sfw_mwb_sfw_obj;
	$mwb_sfw_tracking_fields_array = apply_filters( 'mwb_sfw_tracking_fields_array' , array(
		array(
			'title' => __( 'Enable Tracking', 'subscriptions-for-woocommerce' ),
			'type'  => 'radio-switch',
			'description'  => __( 'Allow usage of this plugin to be tracked', 'subscriptions-for-woocommerce' ),
			'id'    => 'mwb_sfw_enable_tracking',
			'value' => get_option( 'mwb_sfw_enable_tracking' ),
			'class' => 'sfw-radio-switch-class',
			'options' => array(
				'yes' => __( 'YES', 'subscriptions-for-woocommerce' ),
				'no' => __( 'NO', 'subscriptions-for-woocommerce' ),
			),
		),
		array(
			'type'  => 'button',
			'id'    => 'sfw_track_button',
			'button_text' => __('Save', 'subscriptions-for-woocommerce'),
			'class' => 'sfw-button-class',
		),
	) );
	?>
	<form action="" method="POST" class="mwb-sfw-gen-section-form">
		<div class="sfw-secion-wrap">
			<?php
			$mps_tracking_html = $sfw_mwb_sfw_obj->mwb_sfw_plug_generate_html( $mwb_sfw_tracking_fields_array );
			echo esc_html( $mps_tracking_html );
			wp_nonce_field( 'mwb-sfw-general-nonce', 'mwb-sfw-general-nonce-field' );
			?>
		</div>
	</form>
</div>
