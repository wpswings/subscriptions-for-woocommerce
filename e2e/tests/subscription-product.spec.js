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

	test( 'clicking the "Subscription Settings" tab reveals the settings panel', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=product', { timeout: 90_000 } );
		await page.waitForSelector( '#poststuff', { timeout: 45_000 } );

		/**
		 * WooCommerce registers its product-data-tab click handler inside a
		 * jQuery $(document).ready() callback.  With the single-threaded PHP
		 * built-in server, some scripts may still be downloading when
		 * DOMContentLoaded fires, so jQuery.isReady alone is not sufficient.
		 *
		 * waitForLoadState('load') waits for window.onload, which fires only
		 * after every resource referenced in the page has been fetched. Because
		 * the wp-router.php serves static files (CSS/JS) directly (return false),
		 * they are served quickly and load typically completes within 30–60 s.
		 * At that point ALL $(document).ready() callbacks – including WooCommerce's
		 * tab-switching handler – have definitely run.
		 */
		await page.waitForLoadState( 'load', { timeout: 90_000 } );

		await page.locator( 'li.wps_sfw_product_options a' ).click();

		// After the click WooCommerce calls $(target).show(), which sets an
		// inline style="display:block" that overrides the CSS hidden class.
		await expect( page.locator( '#wps_sfw_product_target_section' ) ).toBeVisible( { timeout: 15_000 } );
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
