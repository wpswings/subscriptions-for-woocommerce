// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Playwright reads .env automatically (v1.47+), so BASE_URL / WP_ADMIN_USER /
 * WP_ADMIN_PASSWORD can come from either the shell environment or an .env file
 * written by the CI workflow.
 */
module.exports = defineConfig({
	testDir: './tests',

	/**
	 * Individual test timeout.  WordPress + WooCommerce pages are heavy; allow
	 * extra time for the PHP built-in server to respond.
	 */
	timeout: 60_000,

	/**
	 * Retry once on CI to absorb transient PHP server slowdowns.
	 */
	retries: process.env.CI ? 1 : 0,

	/**
	 * Serial execution — the PHP built-in server is single-threaded.
	 */
	workers: 1,

	reporter: [
		['list'],
		['html', { open: 'never', outputFolder: 'playwright-report' }],
	],

	use: {
		baseURL: process.env.BASE_URL || 'http://127.0.0.1:8080',

		/**
		 * Wait only for DOMContentLoaded, not for all assets to finish loading.
		 *
		 * The PHP built-in server is single-threaded: it handles one request at
		 * a time.  A WordPress admin page with WooCommerce links dozens of CSS/JS
		 * files; waiting for every asset ('load') causes the navigation to time
		 * out even when the page itself is working correctly.  Stopping at
		 * 'domcontentloaded' (HTML parsed) is sufficient to interact with the DOM.
		 */
		waitUntil: 'domcontentloaded',

		/** Navigation-specific timeout (page.goto, page.reload, …). */
		navigationTimeout: 60_000,

		/** Timeout for individual actions (click, fill, waitForSelector, …). */
		actionTimeout: 15_000,

		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
		trace: 'retain-on-failure',
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],
});
