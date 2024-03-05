<?php
/**
 * Log plugin functionality.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Log all things!
 *
 * @since 1.0.0
 */
class Subscriptions_For_Woocommerce_Log {
	/**
	 * The logger.
	 *
	 * @since   1.0.0
	 * @var $logger logger.
	 */
	public static $logger;

	const WC_LOG_FILENAME = 'wps-sfw-log';

	/**
	 * Utilize WC logger class
	 *
	 * @param string $message message.
	 * @since 1.0.0
	 */
	public static function log( $message ) {
		if ( ! class_exists( 'WC_Logger' ) ) {
			return;
		}
		$enable_log = get_option( 'wps_sfw_enable_subscription_log', 'off' );
		if ( 'on' != $enable_log ) {
			return;
		}

		if ( apply_filters( 'wps_sfw_logging', true, $message ) ) {
			if ( empty( self::$logger ) ) {
				self::$logger = wc_get_logger();
			}

			$log_entry  = "\n" . '====Log Details: ===' . "\n";
			$log_entry .= '====Start Log====' . "\n" . $message . "\n" . '====End Log====' . "\n\n";

			self::$logger->debug( $log_entry, array( 'source' => self::WC_LOG_FILENAME ) );
		}
	}
}
