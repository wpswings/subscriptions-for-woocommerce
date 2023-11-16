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
	<th style="width:50%">Features</th>
	<th>Basic</th>
	<th>Pro</th>
  </tr>
  <tr>
	<td>Subscription for Simple Products</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  <td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Subscription Frequency</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Subscription Plan Expiry</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Charge Initial/Signup Fee</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Offer Free Trial</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Allow User to Cancel Subscription</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Subscription Report for Admin</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Edit Add-to-cart Text</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Change Place Order Text</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>WooCommerce Stripe Payment Gateway Compatible</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>WPS PayPal Payment Gateway</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>WooCommerce PayPal Standard Payment Gateway Compatible</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>PayPal Express Checkout Payment Gateway Compatible</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>WooCommerce Integration with Authorize.net Compatible</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>WPML Compatible</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Change Place Order Text</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Subscription Renewal Order Refund</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Canceled Subscriptions email Notification</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Expired Subscriptions email Notifications</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Compatible with WooCommerce Eway Gateway</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Subscription Renewal Order Refund</td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Manual Subscription Payment Option</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Manage Proration Amount as Wallet Credit</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Extend Next Payment Date for Prorated Amount</td>
   <td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
  <td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Stop Downgrade Proration of Subscription Plans</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Compatible With Mollie Payments for WooCommerce</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Compatible With Multi Safe Payment Gateway</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Allow Multiple Quantities on Subscription Products</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Subscription on Variable Products</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Handle Proration</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Set Start Date (Admin)</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Allow Users to Switch Subscription Plans Within Same Interval</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Manual Subscription Payment</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Pause WooCommerce Subscription Plans</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Automated WooCommerce Subscription Cancellation</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Resume WooCommerce Subscription Plans</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>View All Subscriptions Renewal Orders</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Email for Payment Received</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Email for Plan Expiration</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Emails for Subscriptions On Hold/ Pause</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Emails for Subscription Plan Resumed</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Set Start Date (Admin)</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Upgrade Downgrade Variable Subscription Plan</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>Alter Upgrade Downgrade Button Text</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>
  <tr>
	<td>One Time Subscription</td>
	<td><img class="cross" src="<?php echo esc_attr( $cross ); ?>"></td>
	<td><img class="tick" src="<?php echo esc_attr( $tick ); ?>"></td>
  </tr>

</table>
