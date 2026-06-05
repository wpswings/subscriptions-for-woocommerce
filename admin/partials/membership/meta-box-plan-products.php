<?php
/**
 * Meta box partial — Linked Products (Select2 / wc-product-search).
 *
 * Variables available from WPS_Membership_Plan_CPT::render_products_meta_box():
 *   $post     WP_Post  Current plan post.
 *   $wps_plan array    Normalised plan data from wps_get_plan(), or null for a new post.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wps_linked = $wps_plan ? array_map( 'absint', (array) $wps_plan['products'] ) : array();
?>
<div class="wps-plan-products-wrap">

	<select
		class="wc-product-search"
		multiple="multiple"
		style="width:100%;"
		id="wps_plan_products"
		name="_wps_plan_products[]"
		data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'subscriptions-for-woocommerce' ); ?>"
		data-action="woocommerce_json_search_products_and_variations"
	>
		<?php foreach ( $wps_linked as $wps_product_id ) : ?>
			<?php $wps_product = wc_get_product( $wps_product_id ); ?>
			<?php if ( $wps_product ) : ?>
				<option value="<?php echo absint( $wps_product_id ); ?>" selected="selected">
					<?php
					echo esc_html( wp_strip_all_tags( $wps_product->get_formatted_name() ) );
					?>
				</option>
			<?php endif; ?>
		<?php endforeach; ?>
	</select>

	<p class="description" style="margin-top:6px;">
		<?php esc_html_e( 'Purchasing any selected product will grant this membership plan to the buyer.', 'subscriptions-for-woocommerce' ); ?>
	</p>

</div>
