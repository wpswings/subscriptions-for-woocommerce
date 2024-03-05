<?php
/**
 * Expired Email template
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo esc_html( $email_heading ) . "\n\n"; // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<?php /* translators: %s: subscription ID */ ?>
<p><?php printf( esc_html__( 'A subscription [#%s] has been expired. Their subscription\'s details are as follows:', 'subscriptions-for-woocommerce' ), esc_html( $wps_subscription ) ); ?></p>

<?php
$wps_product_name = wps_sfw_get_meta_data( $wps_subscription, 'product_name', true );
$product_qty = wps_sfw_get_meta_data( $wps_subscription, 'product_qty', true );

?>
<table>
	<tr>
		<td><?php esc_html_e( 'Product', 'subscriptions-for-woocommerce' ); ?></td>
		<td><?php echo esc_html( $wps_product_name ); ?> </td>
	</tr>
	<tr>
		<td> <?php esc_html_e( 'Quantity', 'subscriptions-for-woocommerce' ); ?> </td>
		<td> <td><?php echo esc_html( $product_qty ); ?> </td> </td>
	</tr>
	<tr>
		<td> <?php esc_html_e( 'Price', 'subscriptions-for-woocommerce' ); ?> </td>
		<td> <?php do_action( 'wps_sfw_display_susbcription_recerring_total_account_page', $wps_subscription ); ?> </td>
	</tr>
</table>
<?php
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
