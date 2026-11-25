/**
 * Shared authentication helper for e2e tests.
 *
 * Uses credentials from environment variables written by the CI workflow:
 *   WP_ADMIN_USER     – WordPress admin username  (default: admin)
 *   WP_ADMIN_PASSWORD – WordPress admin password  (default: admin_password)
 */

/**
 * Log in to the WordPress admin dashboard.
 *
 * Uses 'domcontentloaded' so we don't block waiting for all WooCommerce
 * assets to finish loading through the single-threaded PHP built-in server.
 *
 * @param {import('@playwright/test').Page} page
 */
async function loginAsAdmin( page ) {
	const user     = process.env.WP_ADMIN_USER     || 'admin';
	const password = process.env.WP_ADMIN_PASSWORD || 'admin_password';

	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', password );
	await page.click( '#wp-submit' );
	// Wait until the browser lands on any wp-admin page.
	await page.waitForURL( /\/wp-admin\//, { waitUntil: 'domcontentloaded' } );
}

module.exports = { loginAsAdmin };
