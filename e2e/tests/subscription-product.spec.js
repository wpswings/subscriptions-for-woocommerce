// @ts-check
const { test, expect }   = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');

test.describe( 'Subscriptions for WooCommerce – product integration', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( '"Subscription Settings" tab is visible on a new product page', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=product' );

		// Wait for WooCommerce to render the product data panel.
		await page.waitForSelector( '.product_data_tabs', { timeout: 15_000 } );

		// Our plugin registers the tab with key `wps_sfw_product`; WooCommerce
		// turns that into an <li class="wps_sfw_product_options ..."> element.
		const tab = page.locator( 'li.wps_sfw_product_options' );
		await expect( tab ).toBeAttached();
		await expect( tab.locator( 'a' ) ).toContainText( 'Subscription Settings' );
	} );

	test( 'clicking the "Subscription Settings" tab reveals the settings panel', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=product' );
		await page.waitForSelector( '.product_data_tabs', { timeout: 15_000 } );

		// Click the tab link.
		await page.locator( 'li.wps_sfw_product_options a' ).click();

		// The target panel should become visible.
		const panel = page.locator( '#wps_sfw_product_target_section' );
		await expect( panel ).toBeVisible( { timeout: 5_000 } );
	} );

	test( 'subscription product type is available in the product-type dropdown', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=product' );
		await page.waitForSelector( 'select#product-type', { timeout: 15_000 } );

		const dropdown = page.locator( 'select#product-type' );
		// The plugin registers 'wps_sfw_product' as a product type option.
		await expect( dropdown.locator( 'option[value="wps_sfw_product"]' ) ).toBeAttached();
	} );
} );
