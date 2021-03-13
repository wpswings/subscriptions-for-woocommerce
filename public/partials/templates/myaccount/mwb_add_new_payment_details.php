<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div>
<form id="order_review" method="post">
	<table class="shop_table mwb_sfw_order_details">
		<h3><?php esc_html_e('Subscription Order Details','subscriptions-for-woocommerce');?></h3>
		<thead>
			<tr>
				<th>
					<?php esc_html_e('Product Name','subscriptions-for-woocommerce'); ?>
				</th>
				<th>
					<?php esc_html_e('Total','subscriptions-for-woocommerce'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<?php 
						$mwb_product_name = get_post_meta( $mwb_subscription_id, 'product_name',true );
						$product_qty = get_post_meta( $mwb_subscription_id, 'product_qty',true );

						echo esc_html( $mwb_product_name ). ' x ' .esc_html( $product_qty );
				 	?>
				 	
				 </td>
				<td>
				<?php  
					do_action('mwb_sfw_display_susbcription_recerring_total_account_page', $mwb_subscription_id ); 
				?>
				</td>
			</tr>
			<?php do_action( 'mwb_sfw_order_details_html_before_cancel', $mwb_subscription_id );?>
		</tbody>
	</table>
	<div id="payment">
		<?php 
		if ( $available_gateways = WC()->payment_gateways->get_available_payment_gateways() ){
			?>
			<ul class="payment_methods methods">
				<?php

				if ( count( $available_gateways ) ) {
					current( $available_gateways )->set_current();
				}

				foreach ( $available_gateways as $gateway ) :
					
					//$supports_payment_method_changes = WC_Subscriptions_Change_Payment_Gateway::can_update_all_subscription_payment_methods( $gateway, $subscription );
					$supports_payment_method_changes = '';
					?>
					<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>">
						<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio <?php echo $supports_payment_method_changes ? 'supports-payment-method-changes' : ''; ?>" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( apply_filters( 'wcs_gateway_change_payment_button_text', $pay_order_button_text, $gateway ) ); ?>"/>
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
		}
		?>
	</div>
</form>
</div>
