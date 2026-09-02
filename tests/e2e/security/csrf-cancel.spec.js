/**
 * Security test — CVE-2: CSRF on subscription cancellation.
 *
 * Before the fix, the _wpnonce parameter was only checked for presence, not
 * verified. Any non-empty value (e.g. "x") was accepted, allowing a CSRF
 * attacker to cancel a logged-in customer's subscription via a crafted URL.
 *
 * After the fix, wp_verify_nonce() is called with the action
 * "{subscription_id}{status}", so only a correctly-signed nonce succeeds.
 *
 * Requires:
 *   WP_CUSTOMER_USER / WP_CUSTOMER_PASS  — a customer with at least one
 *                                          active subscription.
 *   WP_TEST_SUBSCRIPTION_ID              — the ID of that subscription.
 *
 * Set these in tests/e2e/.env (copy .env.example).
 */

require('dotenv').config({ quiet: true, path: `${__dirname}/../.env` });
const { test, expect } = require('@playwright/test');
const { wpLoginCustomer, wpLogout } = require('../helpers/wp-login');

const BASE_URL         = process.env.WP_BASE_URL         || 'http://subscription-site.local';
const SUBSCRIPTION_ID  = process.env.WP_TEST_SUBSCRIPTION_ID || '';

test.describe('CVE-2 — CSRF subscription cancellation', () => {

  test.beforeEach(async ({ page }) => {
    if ( ! SUBSCRIPTION_ID ) {
      test.skip(true, 'WP_TEST_SUBSCRIPTION_ID not set in .env — skipping CSRF test');
    }
  });

  test('bogus nonce does NOT cancel subscription (unauthenticated CSRF attempt)', async ({ request }) => {
    // Fire the cancel URL as an anonymous HTTP request (no cookie session).
    // The nonce is fake, and there is no logged-in user — should be a no-op.
    const csrfUrl = `${BASE_URL}/?wps_subscription_status=active&wps_subscription_id=${SUBSCRIPTION_ID}&_wpnonce=fakecsrftoken`;
    const resp    = await request.get(csrfUrl, { maxRedirects: 5 });

    // WordPress may redirect, but the subscription status must not change.
    // We verify that by logging in as the customer and checking the detail page.
    expect([200, 301, 302, 303]).toContain(resp.status());
  });

  test('after bogus nonce request, subscription remains active', async ({ page }) => {
    // Step 1 — fire the crafted URL that would have worked before the fix.
    const csrfUrl = `${BASE_URL}/?wps_subscription_status=active&wps_subscription_id=${SUBSCRIPTION_ID}&_wpnonce=x`;
    await page.goto(csrfUrl, { waitUntil: 'commit' });

    // Step 2 — log in as the customer and visit the subscription detail page.
    await wpLoginCustomer(page);
    await page.goto(`${BASE_URL}/my-account/show-subscription/${SUBSCRIPTION_ID}/`);

    // The subscription status element should show "active", not "cancelled".
    const statusText = await page.locator('.wps_sfw_details, .wps-sfw-status, td').filter({ hasText: /active|cancelled/i }).first().textContent();
    expect(statusText?.toLowerCase()).toContain('active');
    expect(statusText?.toLowerCase()).not.toContain('cancelled');

    await wpLogout(page);
  });

  test('real cancel link (valid nonce) does cancel subscription', async ({ page }) => {
    // Log in as customer first so we can read the real nonce from the page.
    await wpLoginCustomer(page);
    await page.goto(`${BASE_URL}/my-account/show-subscription/${SUBSCRIPTION_ID}/`);

    // Find the Cancel button/link — it contains the signed nonce.
    const cancelLink = page.locator('a.wps_sfw_cancel_subscription, a[href*="wps_subscription_status"]').first();

    if ( ! await cancelLink.isVisible() ) {
      test.skip(true, 'No cancel link visible — subscription may already be cancelled or cancel is disabled');
    }

    // Capture the href so we can assert the nonce was accepted.
    const href = await cancelLink.getAttribute('href');
    expect(href).toMatch(/_wpnonce=/);

    await cancelLink.click();
    await page.waitForURL(/my-account/);

    // After clicking the real link the subscription should move to cancelled.
    await page.goto(`${BASE_URL}/my-account/show-subscription/${SUBSCRIPTION_ID}/`);
    const statusText = await page.locator('.wps_sfw_details td, .wps-sfw-status').filter({ hasText: /active|cancelled/i }).first().textContent();
    expect(statusText?.toLowerCase()).toContain('cancelled');

    await wpLogout(page);
  });
});
