<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to list all the hooks and filter with their descriptions.
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
$sfw_developer_admin_hooks =
// desc - filter for trial.
apply_filters( 'sfw_developer_admin_hooks_array', array() );
$count_admin                = filtered_array( $sfw_developer_admin_hooks );
$sfw_developer_public_hooks =
// desc - filter for trial.
apply_filters( 'sfw_developer_public_hooks_array', array() );
$count_public = filtered_array( $sfw_developer_public_hooks );
?>
<!--  template file for admin settings. -->
<div class="sfw-section-wrap">
	<?php
	global $sfw_mwb_sfw_obj;
	// Tracking settings.
	$mwb_sfw_tracking_fields_array = apply_filters(
		'mwb_sfw_tracking_fields_array',
		array(
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
				'button_text' => __( 'Save', 'subscriptions-for-woocommerce' ),
				'class' => 'sfw-button-class',
			),
		)
	);
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
	<div class="mwb-col-wrap">

		<div id="admin-hooks-listing" class="table-responsive mdc-data-table">
			<table class="mwb-sfw-table mdc-data-table__table mwb-table"  id="mwb-sfw-wp">
				<thead>
				<tr><th class="mdc-data-table__header-cell"><?php esc_html_e( 'Admin Hooks', 'subscriptions-for-woocommerce' ); ?></th></tr>
				<tr>
					<th class="mdc-data-table__header-cell"><?php esc_html_e( 'Type of Hook', 'subscriptions-for-woocommerce' ); ?></th>
					<th class="mdc-data-table__header-cell"><?php esc_html_e( 'Hooks', 'subscriptions-for-woocommerce' ); ?></th>
					<th class="mdc-data-table__header-cell"><?php esc_html_e( 'Hooks description', 'subscriptions-for-woocommerce' ); ?></th>
				</tr>
				</thead>
				<tbody class="mdc-data-table__content">
				<?php
				if ( ! empty( $count_admin ) ) {
					foreach ( $count_admin as $k => $v ) {
						if ( isset( $v['action_hook'] ) ) {
							?>
						<tr class="mdc-data-table__row"><td class="mdc-data-table__cell"><?php esc_html_e( 'Action Hook', 'subscriptions-for-woocommerce' ); ?></td><td class="mdc-data-table__cell"><?php echo esc_html( $v['action_hook'] ); ?></td><td class="mdc-data-table__cell"><?php echo esc_html( $v['desc'] ); ?></td></tr>
							<?php
						} else {
							?>
							<tr class="mdc-data-table__row"><td class="mdc-data-table__cell"><?php esc_html_e( 'Filter Hook', 'subscriptions-for-woocommerce' ); ?></td><td class="mdc-data-table__cell"><?php echo esc_html( $v['filter_hook'] ); ?></td><td class="mdc-data-table__cell"><?php echo esc_html( $v['desc'] ); ?></td></tr>
							<?php
						}
					}
				} else {
					?>
					<tr class="mdc-data-table__row"><td><?php esc_html_e( 'No Hooks Found', 'subscriptions-for-woocommerce' ); ?><td></tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="mwb-col-wrap">
		<div id="public-hooks-listing" class="table-responsive mdc-data-table">
			<table class="mwb-sfw-table mdc-data-table__table mwb-table" id="mwb-sfw-sys">
				<thead>
				<tr><th class="mdc-data-table__header-cell"><?php esc_html_e( 'Public Hooks', 'subscriptions-for-woocommerce' ); ?></th></tr>
				<tr>
					<th class="mdc-data-table__header-cell"><?php esc_html_e( 'Type of Hook', 'subscriptions-for-woocommerce' ); ?></th>
					<th class="mdc-data-table__header-cell"><?php esc_html_e( 'Hooks', 'subscriptions-for-woocommerce' ); ?></th>
					<th class="mdc-data-table__header-cell"><?php esc_html_e( 'Hooks description', 'subscriptions-for-woocommerce' ); ?></th>
				</tr>
				</thead>
				<tbody class="mdc-data-table__content">
				<?php
				if ( ! empty( $count_public ) ) {
					foreach ( $count_public as $k => $v ) {
						if ( isset( $v['action_hook'] ) ) {
							?>
						<tr class="mdc-data-table__row"><td class="mdc-data-table__cell"><?php esc_html_e( 'Action Hook', 'subscriptions-for-woocommerce' ); ?></td><td class="mdc-data-table__cell"><?php echo esc_html( $v['action_hook'] ); ?></td><td class="mdc-data-table__cell"><?php echo esc_html( $v['desc'] ); ?></td></tr>
							<?php
						} else {
							?>
							<tr class="mdc-data-table__row"><td class="mdc-data-table__cell"><?php esc_html_e( 'Filter Hook', 'subscriptions-for-woocommerce' ); ?></td><td class="mdc-data-table__cell"><?php echo esc_html( $v['filter_hook'] ); ?></td><td class="mdc-data-table__cell"><?php echo esc_html( $v['desc'] ); ?></td></tr>
							<?php
						}
					}
				} else {
					?>
					<tr class="mdc-data-table__row"><td><?php esc_html_e( 'No Hooks Found', 'subscriptions-for-woocommerce' ); ?><td></tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php
/**
 * Function use for filteration of array.
 *
 * @param [type] $argu of the array.
 * @return array
 */
function filtered_array( $argu ) {
	$count_admin = array();
	foreach ( $argu as $key => $value ) {
		foreach ( $value as $k => $originvalue ) {
			if ( isset( $originvalue['action_hook'] ) ) {
				$val                            = str_replace( ' ', '', $originvalue['action_hook'] );
				$val                            = str_replace( "do_action('", '', $val );
				$val                            = str_replace( "');", '', $val );
				$count_admin[ $k ]['action_hook'] = $val;
			}
			if ( isset( $originvalue['filter_hook'] ) ) {
				$val                            = str_replace( ' ', '', $originvalue['filter_hook'] );
				$val                            = str_replace( "apply_filters('", '', $val );
				$val                            = str_replace( "',array());", '', $val );
				$count_admin[ $k ]['filter_hook'] = $val;
			}
			$vale                    = str_replace( '//desc - ', '', $originvalue['desc'] );
			$count_admin[ $k ]['desc'] = $vale;
		}
	}
	return $count_admin;
}
