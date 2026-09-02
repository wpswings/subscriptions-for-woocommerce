/**
 * Security test — CVE-3: Membership content restriction bypass.
 *
 * Before the fix, maybe_restrict_content() returned early when ! is_singular(),
 * so restricted content was returned in full via:
 *   - WP REST API  (/wp-json/wp/v2/posts/<id>)
 *   - WP REST API collection (/wp-json/wp/v2/posts)
 *   - RSS feed
 *   - excerpt field
 *
 * After the fix, the is_singular() guard is removed and the_excerpt is also
 * filtered, so all these paths must return empty/restricted content.
 *
 * Requires:
 *   WP_RESTRICTED_POST_ID — ID of a published post that has a membership
 *                           access rule applied to it.
 *                           Create one in WP admin → Subscriptions → Access Rules,
 *                           then put its ID in tests/e2e/.env.
 */

require('dotenv').config({ quiet: true, path: `${__dirname}/../.env` });
const { test, expect } = require('@playwright/test');
const { wpLoginAdmin, wpLogout } = require('../helpers/wp-login');

const BASE_URL          = process.env.WP_BASE_URL          || 'http://subscription-site.local';
const RESTRICTED_POST   = process.env.WP_RESTRICTED_POST_ID || '';
const SECRET_MARKER     = 'SECRET-PAYWALLED-CONTENT';    // must appear in that post's content

test.describe('CVE-3 — Membership content restriction bypass', () => {

  test.beforeEach(() => {
    if ( ! RESTRICTED_POST ) {
      test.skip(true, 'WP_RESTRICTED_POST_ID not set in .env — skipping membership bypass tests');
    }
  });

  // -------------------------------------------------------------------------
  // Singular front-end view (should already be gated before the fix)
  // -------------------------------------------------------------------------

  test('restricted content is hidden on front-end singular view (baseline)', async ({ page }) => {
    await page.goto(`${BASE_URL}/?p=${RESTRICTED_POST}`, { waitUntil: 'domcontentloaded' });
    const body = await page.content();
    expect(body).not.toContain(SECRET_MARKER);
  });

  // -------------------------------------------------------------------------
  // WP REST API — single post
  // -------------------------------------------------------------------------

  test('REST API single post: content.rendered must not contain restricted body', async ({ request }) => {
    const resp = await request.get(`${BASE_URL}/wp-json/wp/v2/posts/${RESTRICTED_POST}`);
    if (resp.status() === 404) {
      // Could be a page, not a post
      const respPage = await request.get(`${BASE_URL}/wp-json/wp/v2/pages/${RESTRICTED_POST}`);
      expect(respPage.status()).toBe(200);
      const json = await respPage.json();
      expect(json?.content?.rendered ?? '').not.toContain(SECRET_MARKER);
      return;
    }
    expect(resp.status()).toBe(200);
    const json = await resp.json();
    expect(json?.content?.rendered ?? '').not.toContain(
      SECRET_MARKER,
      'REST API must not return restricted content (regression: was visible before fix)'
    );
  });

  test('REST API single post: excerpt.rendered must not contain restricted body', async ({ request }) => {
    const resp = await request.get(`${BASE_URL}/wp-json/wp/v2/posts/${RESTRICTED_POST}`);
    if (resp.status() === 404) {
      const respPage = await request.get(`${BASE_URL}/wp-json/wp/v2/pages/${RESTRICTED_POST}`);
      const json = await respPage.json();
      expect(json?.excerpt?.rendered ?? '').not.toContain(SECRET_MARKER);
      return;
    }
    expect(resp.status()).toBe(200);
    const json = await resp.json();
    expect(json?.excerpt?.rendered ?? '').not.toContain(
      SECRET_MARKER,
      'REST API excerpt must not leak restricted content'
    );
  });

  // -------------------------------------------------------------------------
  // WP REST API — collection route (no ID needed by attacker)
  // -------------------------------------------------------------------------

  test('REST API collection: no restricted body in any returned post', async ({ request }) => {
    const resp = await request.get(`${BASE_URL}/wp-json/wp/v2/posts?per_page=100`);
    expect(resp.status()).toBe(200);
    const posts = await resp.json();
    for (const post of posts) {
      expect(post?.content?.rendered ?? '').not.toContain(
        SECRET_MARKER,
        `Post #${post.id} content must not expose restricted text`
      );
      expect(post?.excerpt?.rendered ?? '').not.toContain(
        SECRET_MARKER,
        `Post #${post.id} excerpt must not expose restricted text`
      );
    }
  });

  // -------------------------------------------------------------------------
  // RSS / Atom feed
  // -------------------------------------------------------------------------

  test('RSS feed must not contain restricted body', async ({ request }) => {
    const resp = await request.get(`${BASE_URL}/feed/`);
    expect(resp.status()).toBe(200);
    const body = await resp.text();
    expect(body).not.toContain(SECRET_MARKER);
  });

  // -------------------------------------------------------------------------
  // Admins bypass restriction (sanity check — must still see content)
  // -------------------------------------------------------------------------

  test('admin sees restricted content on the front end', async ({ page }) => {
    await wpLoginAdmin(page);
    await page.goto(`${BASE_URL}/?p=${RESTRICTED_POST}`, { waitUntil: 'domcontentloaded' });
    const body = await page.content();
    expect(body).toContain(SECRET_MARKER);
    await wpLogout(page);
  });
});
