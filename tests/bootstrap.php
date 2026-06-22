<?php
/**
 * PHPUnit bootstrap for the Subscriptions for WooCommerce membership layer.
 *
 * Usage:
 *   export WP_TESTS_DIR=/tmp/wordpress-tests-lib
 *   vendor/bin/phpunit
 *
 * To scaffold WP_TESTS_DIR, run:
 *   bash tests/bin/install-wp-tests.sh local root root \
 *       "/home/shivam/.config/Local/run/wCdL6ejyZ/mysql/mysqld.sock" latest true
 *
 * @package Subscriptions_For_Woocommerce
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// PHPUnit Polyfills — required by the WP test bootstrap (WP 5.9+).
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	$_polyfills_candidates = array(
		getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ),
		'/tmp/phpunit-polyfills',
		dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills',
	);
	foreach ( $_polyfills_candidates as $_candidate ) {
		if ( $_candidate && file_exists( $_candidate . '/phpunitpolyfills-autoload.php' ) ) {
			define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_candidate );
			break;
		}
	}
}

if ( ! file_exists( "$_tests_dir/includes/functions.php" ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "Could not find {$_tests_dir}/includes/functions.php." . PHP_EOL;
	echo "Run: bash tests/bin/install-wp-tests.sh <db> <user> <pass> <socket> latest true" . PHP_EOL;
	exit( 1 );
}

require_once "$_tests_dir/includes/functions.php";

/**
 * Load the plugin (and WooCommerce) before the test suite boots.
 *
 * The main plugin entry point only loads when WooCommerce is in active_plugins.
 * We simulate that here so all membership includes are registered.
 */
function _wps_membership_load_plugin() {
	// Mark WooCommerce as active so the plugin's activation guard passes.
	$active = get_option( 'active_plugins', array() );
	if ( ! in_array( 'woocommerce/woocommerce.php', $active, true ) ) {
		$active[] = 'woocommerce/woocommerce.php';
		update_option( 'active_plugins', $active );
	}

	// WooCommerce must load first (if present in this environment).
	$wc = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
	if ( file_exists( $wc ) ) {
		require_once $wc;
	}

	require dirname( __DIR__ ) . '/subscriptions-for-woocommerce.php';
}
tests_add_filter( 'muplugins_loaded', '_wps_membership_load_plugin' );

require "$_tests_dir/includes/bootstrap.php";
