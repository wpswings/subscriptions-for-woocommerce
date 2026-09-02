/**
 * WordPress login helpers for Playwright tests.
 */

const BASE_URL = process.env.WP_BASE_URL || 'http://subscription-site.local';

/**
 * Log in to WordPress as the given user.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} username
 * @param {string} password
 */
async function wpLogin(page, username, password) {
  await page.goto(`${BASE_URL}/wp-login.php`);
  await page.fill('#user_login', username);
  await page.fill('#user_pass', password);
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin|\/my-account/);
}

/**
 * Log in as admin.
 *
 * @param {import('@playwright/test').Page} page
 */
async function wpLoginAdmin(page) {
  await wpLogin(
    page,
    process.env.WP_ADMIN_USER || 'admin',
    process.env.WP_ADMIN_PASS || 'password'
  );
}

/**
 * Log in as test customer.
 *
 * @param {import('@playwright/test').Page} page
 */
async function wpLoginCustomer(page) {
  await wpLogin(
    page,
    process.env.WP_CUSTOMER_USER || 'customer',
    process.env.WP_CUSTOMER_PASS || 'password'
  );
}

/**
 * Log out of WordPress.
 *
 * @param {import('@playwright/test').Page} page
 */
async function wpLogout(page) {
  await page.goto(`${BASE_URL}/wp-login.php?action=logout`);
  const confirm = page.locator('a[href*="action=logout"]');
  if (await confirm.isVisible()) {
    await confirm.click();
  }
}

module.exports = { wpLogin, wpLoginAdmin, wpLoginCustomer, wpLogout };
