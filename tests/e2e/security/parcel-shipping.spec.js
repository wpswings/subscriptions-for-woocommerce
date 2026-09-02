/**
 * E2E test — Parcel machine shipping copied to renewal orders.
 *
 * Verifies that when a renewal order is created for a subscription, the
 * shipping line items (including parcel-machine terminal selection stored in
 * item meta) are copied from the parent order rather than being dropped.
 *
 * This test checks the admin order detail page for the presence of the
 * shipping method name from the original order.
 *
 * Requires:
 *   WP_ADMIN_USER / WP_ADMIN_PASS    — admin credentials.
 *   WP_TEST_SUBSCRIPTION_ID          — a subscription that has at least one
 *                                      renewal order.
 *
 * Set these in tests/e2e/.env (copy .env.example).
 */

require('dotenv').config({ quiet: true, path: `${__dirname}/../.env` });
const { test, expect } = require('@playwright/test');
const { wpLoginAdmin, wpLogout } = require('../helpers/wp-login');

const BASE_URL        = process.env.WP_BASE_URL          || 'http://subscription-site.local';
const SUBSCRIPTION_ID = process.env.WP_TEST_SUBSCRIPTION_ID || '';

test.describe('Parcel-machine shipping copy to renewal orders', () => {

  test.beforeEach(() => {
    if ( ! SUBSCRIPTION_ID ) {
      test.skip(true, 'WP_TEST_SUBSCRIPTION_ID not set in .env — skipping parcel shipping tests');
    }
  });

  test('renewal order carries the same shipping method as the parent order', async ({ page }) => {
    await wpLoginAdmin(page);

    // -------------------------------------------------------------------
    // Step 1: Read the shipping method from the parent (subscription) order.
    // -------------------------------------------------------------------
    const subDetailUrl = `${BASE_URL}/wp-admin/admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table&wps_subscription_view_renewal_order=pending&wps_subscription_id=${SUBSCRIPTION_ID}`;
    await page.goto(subDetailUrl, { waitUntil: 'domcontentloaded' });

    // Grab the parent order link from the subscription table.
    const parentOrderLink = page.locator('a[href*="post.php?post="], a[href*="wc-orders&action=edit"]').first();
    if ( ! await parentOrderLink.isVisible({ timeout: 5000 }).catch(() => false) ) {
      // Fall back to the subscription list page
      await page.goto(`${BASE_URL}/wp-admin/admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table`);
    }

    // Navigate directly to the parent order from meta.
    // We use the WP REST API to get the parent order ID from subscription meta.
    const subscriptionApiUrl = `${BASE_URL}/wp-json/wsp-route/v1/wsp-view-subscription/`;
    // (The API may be off — fall back to page-based approach.)

    // -------------------------------------------------------------------
    // Step 2: Visit the WP admin subscription view and get renewal order IDs.
    // -------------------------------------------------------------------
    await page.goto(
      `${BASE_URL}/wp-admin/admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table`,
      { waitUntil: 'domcontentloaded' }
    );

    // Find our subscription row.
    const subRow = page.locator(`tr`).filter({ hasText: SUBSCRIPTION_ID }).first();
    await expect(subRow).toBeVisible({ timeout: 10000 });

    // Click the parent order link to read its shipping method.
    const parentLink = subRow.locator('a[href*="post.php?post="], a[href*="wc-orders&action=edit"]').first();
    const parentHref = await parentLink.getAttribute('href');
    expect(parentHref).toBeTruthy();

    await page.goto(parentHref, { waitUntil: 'domcontentloaded' });

    // Read shipping method label from the parent order.
    const shippingRow = page.locator('.wc-order-totals .shipping, .woocommerce-order-overview__shipping, #order_shipping_line_items .shipping-title, .wc-order-item-name').first();
    const shippingText = (await shippingRow.textContent().catch(() => '')).trim();
    expect(shippingText.length, 'Parent order must have a shipping method').toBeGreaterThan(0);

    // -------------------------------------------------------------------
    // Step 3: Find the most recent renewal order and verify it has the
    //          same shipping method.
    // -------------------------------------------------------------------
    await page.goto(
      `${BASE_URL}/wp-admin/admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table`,
      { waitUntil: 'domcontentloaded' }
    );

    // Open renewal orders view for this subscription.
    const renewalViewUrl = `${BASE_URL}/wp-admin/admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table&wps_subscription_view_renewal_order=pending&wps_subscription_id=${SUBSCRIPTION_ID}&_wpnonce=`;
    // We need a real nonce — navigate via the table link instead.
    const renewalLink = page.locator(`tr`).filter({ hasText: SUBSCRIPTION_ID }).locator('a[href*="wps_subscription_view_renewal_order"]').first();
    if ( await renewalLink.isVisible({ timeout: 5000 }).catch(() => false) ) {
      await renewalLink.click();
      await page.waitForLoadState('domcontentloaded');
    }

    // Get the first renewal order link.
    const renewalOrderLink = page.locator('td a[href*="post.php?post="], td a[href*="wc-orders&action=edit"]').first();
    if ( ! await renewalOrderLink.isVisible({ timeout: 5000 }).catch(() => false) ) {
      test.skip(true, 'No renewal orders found for this subscription — run the subscription scheduler first');
    }

    const renewalHref = await renewalOrderLink.getAttribute('href');
    await page.goto(renewalHref, { waitUntil: 'domcontentloaded' });

    // Verify the renewal order also has the same shipping method.
    const renewalShipping = page.locator('.wc-order-totals .shipping, .woocommerce-order-overview__shipping, #order_shipping_line_items .shipping-title, .wc-order-item-name').first();
    const renewalShippingText = (await renewalShipping.textContent().catch(() => '')).trim();

    expect(renewalShippingText).toBeTruthy();
    expect(renewalShippingText).toContain(
      shippingText.split('\n')[0].trim(),
      'Renewal order must carry the same shipping method as the parent order'
    );

    await wpLogout(page);
  });
});
