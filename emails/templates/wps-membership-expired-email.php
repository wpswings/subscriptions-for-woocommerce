<?php
/**
 * Membership Expired — HTML email template.
 *
 * Variables available:
 *   $membership_data  array  Keys: user_id, plan_slug, row, plan, user (WP_User).
 *   $email_heading    string Localised heading text.
 *   $plain_text       bool   Always false for this template.
 *   $email            object WC_Email instance.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user      = isset( $membership_data['user'] ) ? $membership_data['user'] : null;
$plan      = isset( $membership_data['plan'] ) ? $membership_data['plan'] : array();
$row       = isset( $membership_data['row'] ) ? $membership_data['row'] : array();
$plan_name = isset( $plan['name'] ) ? $plan['name'] : esc_html( $membership_data['plan_slug'] );
$end_ts    = isset( $row['end_date'] ) ? absint( $row['end_date'] ) : 0;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %s: customer display name */
		esc_html__( 'Hi %s,', 'subscriptions-for-woocommerce' ),
		esc_html( $user ? $user->display_name : '' )
	);
	?>
</p>
<p><?php esc_html_e( 'Your membership has expired. Here are the details:', 'subscriptions-for-woocommerce' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width:100%;border-collapse:collapse;" border="1">
	<tr>
		<th style="text-align:left;padding:8px;background:#f5f5f5;"><?php esc_html_e( 'Plan', 'subscriptions-for-woocommerce' ); ?></th>
		<td style="padding:8px;"><?php echo esc_html( $plan_name ); ?></td>
	</tr>
	<tr>
		<th style="text-align:left;padding:8px;background:#f5f5f5;"><?php esc_html_e( 'Status', 'subscriptions-for-woocommerce' ); ?></th>
		<td style="padding:8px;"><?php esc_html_e( 'Expired', 'subscriptions-for-woocommerce' ); ?></td>
	</tr>
	<?php if ( $end_ts ) : ?>
	<tr>
		<th style="text-align:left;padding:8px;background:#f5f5f5;"><?php esc_html_e( 'Expired On', 'subscriptions-for-woocommerce' ); ?></th>
		<td style="padding:8px;"><?php echo esc_html( date_i18n( get_option( 'date_format' ), $end_ts ) ); ?></td>
	</tr>
	<?php endif; ?>
</table>

<p>
	<?php
	printf(
		/* translators: %s: shop URL */
		wp_kses_post( __( 'To renew your membership, please visit our <a href="%s">shop</a>.', 'subscriptions-for-woocommerce' ) ),
		esc_url( wc_get_page_permalink( 'shop' ) )
	);
	?>
</p>

<?php do_action( 'wps_membership_expired_email_details', $membership_data, $email ); ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
