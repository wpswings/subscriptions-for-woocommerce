<?php
/**
 * Exit if accessed directly
 *
 * @since      1.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

$tick = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/accept.png';
$cross = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/images/cross.png';
?>
<style>
table {
  border-collapse: collapse;
  border-spacing: 0;
  width: 100%;
  border: 1px solid #ddd;
}

th, td {
  text-align: center;
  padding: 16px;
}

th:first-child, td:first-child {
  text-align: left;
}

tr:nth-child(even) {
  background-color: #f2f2f2
}

.fa-check {
  color: green;
}

.fa-remove {
  color: red;
}
.tick, .cross {
  width: 20px;
}
</style>
<table>
  <tr>
	<th style="width:50%"><?php esc_html_e( 'Features', 'subscriptions-for-woocommerce' ); ?></th>
	<th><?php esc_html_e( 'Basic', 'subscriptions-for-woocommerce' ); ?></th>
	<th><?php esc_html_e( 'Pro', 'subscriptions-for-woocommerce' ); ?></th>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Subscription for Simple Products', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  <td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Subscription Frequency', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Subscription Plan Expiry', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Charge Initial/Signup Fee', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Offer Free Trial', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Allow User to Cancel Subscription', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Subscription Report for Admin', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Edit Add-to-cart Text', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Change Place Order Text', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'WooCommerce Stripe Payment Gateway Compatible', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'WPS PayPal Payment Gateway', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'WooCommerce PayPal Standard Payment Gateway Compatible', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'PayPal Express Checkout Payment Gateway Compatible', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'WooCommerce Integration with Authorize.net Compatible', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'WPML Compatible', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Change Place Order Text', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Subscription Renewal Order Refund', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Canceled Subscriptions email Notification', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Expired Subscriptions email Notifications', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Compatible with WooCommerce Eway Gateway', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Subscription Renewal Order Refund', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Manual Subscription Payment Option', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Manage Proration Amount as Wallet Credit', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Extend Next Payment Date for Prorated Amount', 'subscriptions-for-woocommerce' ); ?></td>
   <td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
  <td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Stop Downgrade Proration of Subscription Plans', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Compatible With Mollie Payments for WooCommerce', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Compatible With Multi Safe Payment Gateway', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Allow Multiple Quantities on Subscription Products', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Subscription on Variable Products', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Handle Proration', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Set Start Date (Admin)', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Allow Users to Switch Subscription Plans Within Same Interval', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Manual Subscription Payment', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Pause WooCommerce Subscription Plans', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Automated WooCommerce Subscription Cancellation', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Resume WooCommerce Subscription Plans', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'View All Subscriptions Renewal Orders', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Email for Payment Received', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Email for Plan Expiration', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Emails for Subscriptions On Hold/ Pause', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Emails for Subscription Plan Resumed', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Set Start Date (Admin)', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Upgrade Downgrade Variable Subscription Plan', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'Alter Upgrade Downgrade Button Text', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td><?php esc_html_e( 'One Time Subscription', 'subscriptions-for-woocommerce' ); ?></td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>

</table>
