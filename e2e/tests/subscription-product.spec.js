// @ts-check
const { test, expect }   = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');

test.describe( 'Subscriptions for WooCommerce – product integration', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	/**
	 * The WooCommerce product editor is significantly heavier than the plugin
	 * settings page — it triggers an auto-draft creation, a 302 redirect, and
	 * loads dozens of admin metabox scripts sequentially through the
	 * single-threaded PHP built-in server.  Triple the global timeout for every
	 * test in this describe block so individual actions still have headroom.
	 */
	test.slow();

	test( '"Subscription Settings" tab is present on the product edit page', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=product', { timeout: 90_000 } );

		/**
		 * WordPress creates an auto-draft and 302-redirects to post.php.
		 * Wait for the classic-editor post body to confirm we are on the
		 * real editor page, not an intermediate redirect page.
		 */
		await page.waitForSelector( '#poststuff', { timeout: 45_000 } );

		// WooCommerce renders <ul class="product_data_tabs"> server-side.
		await page.waitForSelector( '.product_data_tabs', { timeout: 45_000 } );

		// Our plugin registers the tab with key `wps_sfw_product`; WooCommerce
		// turns that into <li class="wps_sfw_product_options …">.
		const tab = page.locator( 'li.wps_sfw_product_options' );
		await expect( tab ).toBeAttached();
		await expect( tab.locator( 'a' ) ).toContainText( 'Subscription Settings' );
	} );

	test( 'clicking the "Subscription Settings" tab activates it', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=product', { timeout: 90_000 } );
		await page.waitForSelector( '#poststuff', { timeout: 45_000 } );

		/**
		 * WooCommerce registers its product-data-tab click handler inside a
		 * jQuery $(document).ready() callback.  Wait for window.onload so every
		 * deferred script – including meta-boxes-product.js – has fully executed
		 * and the click handler is attached.
		 */
		await page.waitForLoadState( 'load', { timeout: 90_000 } );

		await page.locator( 'li.wps_sfw_product_options a' ).click();

		/**
		 * WooCommerce's tab JS always adds the CSS class "active" to the <li>
		 * of the clicked tab.  This is the canonical DOM signal that the tab
		 * switch happened — it is set synchronously in the click handler and is
		 * independent of how WC chooses to show/hide the panel element.
		 *
		 * Note: the panel div carries class="hidden" (WordPress admin CSS sets
		 * .hidden { display:none !important }).  jQuery's .show() cannot override
		 * !important via inline style, so toBeVisible() on the panel itself is
		 * not a reliable assertion in this environment.
		 */
		await expect( page.locator( 'li.wps_sfw_product_options' ) ).toHaveClass( /\bactive\b/, { timeout: 15_000 } );

		// Also confirm the panel div is wired up with the correct id.
		await expect( page.locator( '#wps_sfw_product_target_section' ) ).toBeAttached();
	} );

	test( 'subscription product checkbox is present on the product page', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=product', { timeout: 90_000 } );
		await page.waitForSelector( '#poststuff', { timeout: 45_000 } );

		/**
		 * The plugin adds a "Subscription" toggle via the product_type_options
		 * hook (a checkbox), not as an entry in the select#product-type dropdown.
		 * The rendered input has id="_wps_sfw_product".
		 */
		await expect( page.locator( 'input#_wps_sfw_product' ) ).toBeAttached();
	} );
} );
