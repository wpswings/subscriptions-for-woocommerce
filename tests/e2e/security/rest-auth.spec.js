/**
 * Security test — CVE-1: REST API authentication bypass.
 *
 * Before the fix, a whitespace-only consumer_secret was accepted when the API
 * feature toggle was off (stored key == ""). After the fix, any key that trims
 * to empty, or a stored key that is empty, must be rejected with 401.
 *
 * The endpoint is: GET /wp-json/wsp-route/v1/wsp-view-subscription/
 */

require('dotenv').config({ quiet: true, path: `${__dirname}/../.env` });
const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.WP_BASE_URL || 'http://subscription-site.local';
const ENDPOINT = `${BASE_URL}/wp-json/wsp-route/v1/wsp-view-subscription/`;

test.describe('CVE-1 — REST API authentication bypass', () => {

  test('no consumer_secret → 401', async ({ request }) => {
    const resp = await request.get(ENDPOINT);
    expect(resp.status(), 'endpoint must reject unauthenticated requests').toBe(401);
  });

  test('empty consumer_secret → 401', async ({ request }) => {
    const resp = await request.get(`${ENDPOINT}?consumer_secret=`);
    expect(resp.status(), 'empty key must be rejected').toBe(401);
  });

  test('space-only consumer_secret → 401 (was 200 before fix)', async ({ request }) => {
    const resp = await request.get(`${ENDPOINT}?consumer_secret=%20`);
    expect(
      resp.status(),
      'whitespace-only key must not bypass auth (regression: was 200 before fix)'
    ).toBe(401);
  });

  test('tab-only consumer_secret → 401', async ({ request }) => {
    const resp = await request.get(`${ENDPOINT}?consumer_secret=%09`);
    expect(resp.status(), 'tab-only key must be rejected').toBe(401);
  });

  test('multiple spaces consumer_secret → 401', async ({ request }) => {
    const resp = await request.get(`${ENDPOINT}?consumer_secret=+++`);
    expect(resp.status(), 'plus-encoded spaces must be rejected').toBe(401);
  });

  test('wrong consumer_secret → 401', async ({ request }) => {
    const resp = await request.get(`${ENDPOINT}?consumer_secret=definitely-wrong-key`);
    expect(resp.status(), 'wrong key must be rejected').toBe(401);
  });
});
