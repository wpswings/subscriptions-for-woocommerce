<?php
/**
 * Cancelled Email template
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<?php /* translators: %s: subscription ID */ ?>
<p><?php printf( esc_html__( 'A subscription [#%s] has been cancelled. Their subscription\'s details are as follows:', 'subscriptions-for-woocommerce' ), esc_html( $wps_subscription ) ); ?></p>

<?php
wps_sfw_email_subscriptions_details( $wps_subscription );

do_action( 'woocommerce_email_footer', $email );
