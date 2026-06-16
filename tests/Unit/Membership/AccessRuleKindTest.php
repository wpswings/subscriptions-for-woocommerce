<?php
/**
 * Unit tests for the Access Rule "kind" split (Content vs Product).
 *
 * Covers:
 *   - wps_get_access_rule_kind() inference + explicit value.
 *   - wps_sanitize_access_rule() per-kind target/behavior constraints and
 *     field stripping.
 *   - wps_render_restriction_preview() routing for content / redirect / product.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for the rule-kind data model.
 *
 * @since 2.0.0
 */
class AccessRuleKindTest extends WP_UnitTestCase {

	/**
	 * Cleans up options and cache after each test.
	 */
	public function tearDown(): void {
		delete_option( WPS_ACCESS_RULES_OPTION );
		delete_option( WPS_ACCESS_RULES_INDEX_OPTION );
		delete_option( 'wps_access_redirect_url' );
		wp_cache_flush();
		parent::tearDown();
	}

	/**
	 * Tests for wps_get_access_rule_kind().
	 *
	 * @since 2.0.0
	 */

	/**
	 * Tests that an explicit rule_kind value is respected.
	 */
	public function test_kind_respects_explicit_value() {
		$this->assertSame( 'product', wps_get_access_rule_kind( array( 'rule_kind' => 'product' ) ) );
		$this->assertSame( 'content', wps_get_access_rule_kind( array( 'rule_kind' => 'content' ) ) );
	}

	/**
	 * Tests that kind is inferred as product from a product target_type.
	 */
	public function test_kind_inferred_product_from_product_target() {
		$this->assertSame( 'product', wps_get_access_rule_kind( array( 'target_type' => 'product' ) ) );
	}

	/**
	 * Tests that kind is inferred as product from a product_cat taxonomy.
	 */
	public function test_kind_inferred_product_from_product_category() {
		$kind = wps_get_access_rule_kind(
			array(
				'target_type' => 'taxonomy',
				'taxonomy'    => 'product_cat',
			)
		);
		$this->assertSame( 'product', $kind );
	}

	/**
	 * Tests that kind is inferred as content for a generic post_type target.
	 */
	public function test_kind_inferred_content_for_post_type() {
		$this->assertSame( 'content', wps_get_access_rule_kind( array( 'target_type' => 'post_type' ) ) );
	}

	/**
	 * Tests that kind is inferred as product when post_type is product.
	 */
	public function test_kind_inferred_product_for_whole_product_post_type() {
		$kind = wps_get_access_rule_kind(
			array(
				'target_type' => 'post_type',
				'post_type'   => 'product',
			)
		);
		$this->assertSame( 'product', $kind );
	}

	/**
	 * Tests that kind is inferred as content for a non-product taxonomy.
	 */
	public function test_kind_inferred_content_for_blog_taxonomy() {
		$kind = wps_get_access_rule_kind(
			array(
				'target_type' => 'taxonomy',
				'taxonomy'    => 'category',
			)
		);
		$this->assertSame( 'content', $kind );
	}

	/**
	 * Tests that an invalid explicit kind falls back to inference.
	 */
	public function test_invalid_explicit_kind_falls_back_to_inference() {
		$kind = wps_get_access_rule_kind(
			array(
				'rule_kind'   => 'bogus',
				'target_type' => 'product',
			)
		);
		$this->assertSame( 'product', $kind );
	}

	/**
	 * Tests for sanitizer — product kind.
	 *
	 * @since 2.0.0
	 */

	/**
	 * Tests that a product rule forces the behavior to message.
	 */
	public function test_product_rule_forces_message_behavior() {
		$rule = wps_sanitize_access_rule(
			array(
				'rule_kind'   => 'product',
				'target_type' => 'product',
				'behavior'    => 'template',
			)
		);
		$this->assertSame( 'product', $rule['rule_kind'] );
		$this->assertSame( 'message', $rule['behavior'] );
	}

	/**
	 * Tests that a product rule strips content-only fields.
	 */
	public function test_product_rule_strips_content_only_fields() {
		$rule = wps_sanitize_access_rule(
			array(
				'rule_kind'         => 'product',
				'target_type'       => 'product',
				'redirect_url'      => 'https://example.com/go',
				'restrict_comments' => '1',
				'include_archive'   => '1',
				'drip_mode'         => 'days',
				'drip_days'         => 5,
				'teaser_mode'       => 'words',
				'teaser_words'      => 50,
				'exclude_ids'       => array( 9 ),
			)
		);

		$this->assertSame( '', $rule['redirect_url'] );
		$this->assertSame( '0', $rule['restrict_comments'] );
		$this->assertSame( '0', $rule['include_archive'] );
		$this->assertSame( 'none', $rule['drip_mode'] );
		$this->assertSame( 0, $rule['drip_days'] );
		$this->assertSame( 'none', $rule['teaser_mode'] );
		$this->assertSame( 0, $rule['teaser_words'] );
		$this->assertSame( array(), $rule['exclude_ids'] );
	}

	/**
	 * Tests that a product rule keeps message text and target object IDs.
	 */
	public function test_product_rule_keeps_message_and_targets() {
		$rule = wps_sanitize_access_rule(
			array(
				'rule_kind'   => 'product',
				'target_type' => 'product',
				'object_ids'  => array( '7', '8' ),
				'message'     => 'Members only.',
				'show_cta'    => '1',
			)
		);
		$this->assertSame( array( 7, 8 ), $rule['object_ids'] );
		$this->assertSame( 'Members only.', $rule['message'] );
		$this->assertSame( '1', $rule['show_cta'] );
	}

	/**
	 * Tests that a product rule rejects a non-product target type.
	 */
	public function test_product_rule_rejects_non_product_target() {
		$rule = wps_sanitize_access_rule(
			array(
				'rule_kind'   => 'product',
				'target_type' => 'post',
			)
		);
		// 'post' isn't valid for a product rule → defaults to 'product'.
		$this->assertSame( 'product', $rule['target_type'] );
	}

	/**
	 * Tests for sanitizer — content kind.
	 *
	 * @since 2.0.0
	 */

	/**
	 * Tests that a content rule rejects a product target type.
	 */
	public function test_content_rule_rejects_product_target() {
		$rule = wps_sanitize_access_rule(
			array(
				'rule_kind'   => 'content',
				'target_type' => 'product',
			)
		);
		// 'product' isn't valid for a content rule → defaults to 'post_type'.
		$this->assertSame( 'content', $rule['rule_kind'] );
		$this->assertSame( 'post_type', $rule['target_type'] );
	}

	/**
	 * Tests that a content rule keeps redirect behavior and URL.
	 */
	public function test_content_rule_keeps_redirect_behavior() {
		$rule = wps_sanitize_access_rule(
			array(
				'rule_kind'    => 'content',
				'target_type'  => 'post',
				'behavior'     => 'redirect',
				'redirect_url' => 'https://example.com/pricing',
			)
		);
		$this->assertSame( 'redirect', $rule['behavior'] );
		$this->assertSame( 'https://example.com/pricing', $rule['redirect_url'] );
	}

	/**
	 * Tests for wps_render_restriction_preview().
	 *
	 * @since 2.0.0
	 */

	/**
	 * Tests that a content message rule renders the message HTML.
	 */
	public function test_preview_content_message_renders_message() {
		$html = wps_render_restriction_preview(
			array(
				'rule_kind'   => 'content',
				'target_type' => 'post',
				'behavior'    => 'message',
				'message'     => 'You need the gold plan.',
			)
		);
		$this->assertStringContainsString( 'You need the gold plan.', $html );
	}

	/**
	 * Tests that a content redirect rule renders the redirect URL.
	 */
	public function test_preview_content_redirect_shows_url() {
		$html = wps_render_restriction_preview(
			array(
				'rule_kind'    => 'content',
				'target_type'  => 'post',
				'behavior'     => 'redirect',
				'redirect_url' => 'https://example.com/pricing',
			)
		);
		$this->assertStringContainsString( 'https://example.com/pricing', $html );
		$this->assertStringContainsString( 'wps-preview-redirect', $html );
	}

	/**
	 * Tests that a product rule renders the restricted-content notice.
	 */
	public function test_preview_product_renders_notice() {
		$html = wps_render_restriction_preview(
			array(
				'rule_kind'   => 'product',
				'target_type' => 'product',
				'message'     => 'Buy a plan to purchase this.',
			)
		);
		$this->assertStringContainsString( 'Buy a plan to purchase this.', $html );
		$this->assertStringContainsString( 'wps-restricted-content', $html );
	}
}
