<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html for system status.
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
// Template for showing information about system status.
global $sfw_wps_sfw_obj;
$sfw_default_status = $sfw_wps_sfw_obj->wps_sfw_plug_system_status();
$sfw_wordpress_details = is_array( $sfw_default_status['wp'] ) && ! empty( $sfw_default_status['wp'] ) ? $sfw_default_status['wp'] : array();
$sfw_php_details = is_array( $sfw_default_status['php'] ) && ! empty( $sfw_default_status['php'] ) ? $sfw_default_status['php'] : array();
?>
<div class="wps-sfw-status-grid">
	<div class="wps-sfw-data-card">
		<div class="wps-sfw-data-card__head">
			<div class="wps-sfw-data-card__eyebrow"><?php esc_html_e( 'WordPress', 'subscriptions-for-woocommerce' ); ?></div>
			<h3><?php esc_html_e( 'WP Environment', 'subscriptions-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'Review the WordPress runtime values that affect subscription workflows and admin tooling.', 'subscriptions-for-woocommerce' ); ?></p>
		</div>
		<div id="wps-sfw-table-inner-container" class="table-responsive mdc-data-table wps-sfw-data-card__table">
			<div class="mdc-data-table__table-container">
				<table class="wps-sfw-table mdc-data-table__table wps-table" id="wps-sfw-wp">
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
	<div class="wps-sfw-data-card">
		<div class="wps-sfw-data-card__head">
			<div class="wps-sfw-data-card__eyebrow"><?php esc_html_e( 'Server', 'subscriptions-for-woocommerce' ); ?></div>
			<h3><?php esc_html_e( 'System Environment', 'subscriptions-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'Inspect PHP and server-level values used by recurring orders, scheduling, and plugin health checks.', 'subscriptions-for-woocommerce' ); ?></p>
		</div>
		<div id="wps-sfw-table-inner-container" class="table-responsive mdc-data-table wps-sfw-data-card__table">
			<div class="mdc-data-table__table-container">
				<table class="wps-sfw-table mdc-data-table__table wps-table" id="wps-sfw-sys">
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
</div>
