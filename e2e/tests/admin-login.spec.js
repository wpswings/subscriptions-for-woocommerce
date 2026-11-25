// @ts-check
const { test, expect } = require('@playwright/test');

test.describe( 'WordPress Admin – login', () => {
	test( 'login page is accessible', async ( { page } ) => {
		const response = await page.goto( '/wp-login.php' );
		expect( response.status() ).toBeLessThan( 400 );
		await expect( page.locator( '#user_login' ) ).toBeVisible();
		await expect( page.locator( '#user_pass' ) ).toBeVisible();
		await expect( page.locator( '#wp-submit' ) ).toBeVisible();
	} );

	test( 'admin can log in and reach the dashboard', async ( { page } ) => {
		const user     = process.env.WP_ADMIN_USER     || 'admin';
		const password = process.env.WP_ADMIN_PASSWORD || 'admin_password';

		await page.goto( '/wp-login.php' );
		await page.fill( '#user_login', user );
		await page.fill( '#user_pass', password );
		await page.click( '#wp-submit' );

		// WordPress redirects to wp-admin after login. WooCommerce may further
		// redirect to the Core Profiler (page=wc-admin); both URLs contain
		// /wp-admin/ so the URL assertion is reliable either way.
		await expect( page ).toHaveURL( /\/wp-admin\// );

		// Navigate explicitly to the dashboard. By this point the one-shot
		// _wc_activation_redirect transient has been consumed on the first
		// wp-admin load, so this second navigation loads the real dashboard.
		await page.goto( '/wp-admin/index.php' );

		// WordPress always adds class "wp-admin" to <body> on every admin page
		// (including WooCommerce admin pages).  Checking this class is resilient
		// to any WC onboarding redirect that may hide #wpadminbar via CSS.
		await expect( page.locator( 'body.wp-admin' ) ).toBeAttached();
	} );

	test( 'wrong credentials show an error', async ( { page } ) => {
		await page.goto( '/wp-login.php' );
		await page.fill( '#user_login', 'nobody' );
		await page.fill( '#user_pass', 'wrongpassword' );
		await page.click( '#wp-submit' );

		// WordPress renders login errors inside #login_error.
		await expect( page.locator( '#login_error' ) ).toBeVisible();
	} );
} );
