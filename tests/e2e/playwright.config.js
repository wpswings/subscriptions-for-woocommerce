// @ts-check
// Playwright 1.40+ loads .env automatically from the config directory.
// dotenv is kept here as a fallback for older runner versions.
require('dotenv').config({ path: `${__dirname}/.env`, quiet: true });

const { defineConfig, devices } = require('@playwright/test');

const BASE_URL = process.env.WP_BASE_URL || 'http://subscription-site.local';

module.exports = defineConfig({
  testDir: '.',
  testMatch: '**/*.spec.js',
  timeout: 30_000,
  retries: 0,
  workers: 1, // run serially — WP state is shared
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],

  use: {
    baseURL: BASE_URL,
    ignoreHTTPSErrors: true,
    screenshot: 'only-on-failure',
    video: 'off',
  },

  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
});
