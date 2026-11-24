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

		await expect( page ).toHaveURL( /\/wp-admin\// );
		// The admin toolbar is the most reliable landmark on the dashboard.
		await expect( page.locator( '#wpadminbar' ) ).toBeVisible();
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
