<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div>
<form id="order_review" method="post" class="mwb_add_pay_form">
	<table class="shop_table">
		<thead>
			<tr>
				<th class="product-name"><?php esc_html_e( 'Product', 'subscriptions-for-woocommerce' ); ?></th>
				<th class="product-quantity"><?php esc_html_e( 'Quantity', 'subscriptions-for-woocommerce' ); ?></th>
				<th class="product-total"><?php esc_html_e( 'Totals', 'subscriptions-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tfoot>
		<?php foreach ( $mwb_subscription->get_order_item_totals() as $total ) : ?>
			<tr>
				<th scope="row" colspan="2"><?php echo esc_html( $total['label'] ); ?></th>
				<td class="product-total"><?php echo wp_kses_post( $total['value'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tfoot>
		<tbody>
		<?php foreach ( $mwb_subscription->get_items() as $item ) : ?>
			<tr>
				<td class="product-name"><?php echo esc_html( $item['name'] ); ?></td>
				<td class="product-quantity"><?php echo esc_html( $item['qty'] ); ?></td>
				<td class="product-subtotal"><?php echo wp_kses_post( $mwb_subscription->get_formatted_line_subtotal( $item ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<div id="payment">
		<?php 
		$button_text = __( 'Add payment method', 'subscriptions-for-woocommerce' );
		if ( $available_gateways = WC()->payment_gateways->get_available_payment_gateways() ){
			?>
			<ul class="payment_methods methods">
				<?php

				if ( count( $available_gateways ) ) {
					current( $available_gateways )->set_current();
				}

				foreach ( $available_gateways as $key => $gateway ) :
					
					if ('stripe' != $key && 'paypal' != $key ) {
	    				continue;
	    			}

					?>
					<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>">
						<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( $button_text ); ?>"/>
						<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>"><?php echo esc_html( $gateway->get_title() ); ?><?php echo wp_kses_post( $gateway->get_icon() ); ?></label>
						<?php
						if ( $gateway->has_fields() || $gateway->get_description() ) {
							echo '<div class="payment_box payment_method_' . esc_attr( $gateway->id ) . '" style="display:none;">';
							$gateway->payment_fields();
							echo '</div>';
						}
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php
		?>
			<div class="form-row mwb_sfw_from_row">
			<?php wp_nonce_field( 'mwb_sfw__change_payment_method', '_mwb_sfw_nonce', true, true ); ?>
			<input type="submit" class="button alt" id="place_order" value="<?php echo esc_attr( $button_text );?>">
			<input type="hidden" name="mwb_change_change_payment" value="<?php echo esc_attr( $mwb_subscription->get_id() ); ?>" />
		</div>
		<?php
		}
		?>
	</div>
</form>
</div>
