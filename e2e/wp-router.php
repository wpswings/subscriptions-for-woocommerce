<?php
/**
 * Router script for PHP's built-in web server.
 *
 * Usage:
 *   php -S 127.0.0.1:8080 -t /path/to/wordpress e2e/wp-router.php
 *
 * Without a router the built-in server cannot perform WordPress's URL rewrites
 * (pretty permalinks, REST API, etc.).  Static files (CSS, JS, images) are
 * served directly; everything else is routed through WordPress's index.php or
 * the matching PHP file.
 */

$root    = $_SERVER['DOCUMENT_ROOT'];
$uri_raw = urldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$path    = '/' . ltrim( $uri_raw, '/' );
$file    = $root . $path;

// Serve real static files (CSS, JS, images, fonts, …) directly.
if ( file_exists( $file ) && ! is_dir( $file ) && pathinfo( $file, PATHINFO_EXTENSION ) !== 'php' ) {
	return false;
}

// For directories, try to find index.php.
if ( is_dir( $file ) ) {
	$index = rtrim( $file, '/' ) . '/index.php';
	if ( file_exists( $index ) ) {
		chdir( dirname( $index ) );
		include $index;
		return;
	}
}

// If a matching PHP file exists, include it.
if ( file_exists( $file ) && pathinfo( $file, PATHINFO_EXTENSION ) === 'php' ) {
	chdir( dirname( $file ) );
	include $file;
	return;
}

// Fall back to WordPress's front controller (handles all rewrites).
chdir( $root );
include $root . '/index.php';
