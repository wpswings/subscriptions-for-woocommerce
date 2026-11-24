// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Playwright reads .env automatically (v1.47+), so BASE_URL / WP_ADMIN_USER /
 * WP_ADMIN_PASSWORD can come from either the shell environment or an .env file
 * written by the CI workflow.
 */
module.exports = defineConfig({
	testDir: './tests',
	timeout: 30_000,
	/* Retry once on CI to tolerate occasional slow page loads. */
	retries: process.env.CI ? 1 : 0,
	/* Run tests serially – the PHP built-in server is single-threaded. */
	workers: 1,
	reporter: [
		['list'],
		['html', { open: 'never', outputFolder: 'playwright-report' }],
	],
	use: {
		baseURL: process.env.BASE_URL || 'http://127.0.0.1:8080',
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
