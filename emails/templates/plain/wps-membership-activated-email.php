<?php
/**
 * Membership Activated — plain-text email template.
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
$plan_name = isset( $plan['name'] ) ? $plan['name'] : $membership_data['plan_slug'];
$start_ts  = isset( $row['start_date'] ) ? absint( $row['start_date'] ) : 0;
$end_ts    = isset( $row['end_date'] ) ? absint( $row['end_date'] ) : 0;

echo esc_html( $email_heading ) . "\n\n";

printf(
	/* translators: %s: customer display name */
	esc_html__( 'Hi %s,', 'subscriptions-for-woocommerce' ),
	esc_html( $user ? $user->display_name : '' )
);
echo "\n\n";

esc_html_e( 'Your membership has been activated. Here are the details:', 'subscriptions-for-woocommerce' );
echo "\n\n";

/* translators: %s: plan name */
printf( esc_html__( 'Plan: %s', 'subscriptions-for-woocommerce' ), esc_html( $plan_name ) );
echo "\n";

esc_html_e( 'Status: Active', 'subscriptions-for-woocommerce' );
echo "\n";

if ( $start_ts ) {
	/* translators: %s: formatted date */
	printf( esc_html__( 'Start Date: %s', 'subscriptions-for-woocommerce' ), esc_html( date_i18n( get_option( 'date_format' ), $start_ts ) ) );
	echo "\n";
}

if ( $end_ts ) {
	/* translators: %s: formatted date */
	printf( esc_html__( 'Access Until: %s', 'subscriptions-for-woocommerce' ), esc_html( date_i18n( get_option( 'date_format' ), $end_ts ) ) );
	echo "\n";
}

echo "\n";
do_action( 'wps_membership_activated_email_details', $membership_data, $email );

echo "\n" . wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
