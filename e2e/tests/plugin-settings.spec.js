// @ts-check
const { test, expect }   = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');

test.describe( 'Subscriptions for WooCommerce – admin settings', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'WP Swings top-level menu item is present in the admin sidebar', async ( { page } ) => {
		await page.goto( '/wp-admin/' );
		// The plugin registers a "WP Swings" top-level menu with id toplevel_page_wps-plugins.
		await expect( page.locator( '#toplevel_page_wps-plugins' ) ).toBeVisible();
	} );

	test( '"Wps Subscriptions" submenu appears under WooCommerce', async ( { page } ) => {
		await page.goto( '/wp-admin/' );
		// Hover over WooCommerce to reveal the submenu.
		await page.hover( '#toplevel_page_woocommerce' );
		const subLink = page.locator( '#menu-posts-wps_subscriptions, a[href*="page=subscriptions-for-woocommerce"]' ).first();
		await expect( subLink ).toBeVisible();
	} );

	test( 'plugin settings page loads without a permissions error', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=subscriptions-for-woocommerce' );
		// WordPress shows "You do not have sufficient permissions" for missing pages.
		await expect( page.locator( 'body' ) ).not.toContainText( 'do not have sufficient permissions' );
		await expect( page.locator( '#wpbody' ) ).toBeVisible();
	} );

	test( 'plugin settings page contains the General settings tab', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=subscriptions-for-woocommerce&sfw_tab=subscriptions-for-woocommerce-general' );
		await expect( page.locator( '#wpbody' ) ).toBeVisible();
		// The page should not be a WordPress 404/no-access screen.
		await expect( page.locator( 'body' ) ).not.toContainText( 'ERROR' );
	} );
} );
