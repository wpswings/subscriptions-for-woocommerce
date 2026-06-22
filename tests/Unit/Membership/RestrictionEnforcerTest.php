<?php
/**
 * Unit tests for Day 13: WPS_Restriction_Enforcer
 *
 * Covers:
 *   - maybe_restrict_content: pass-through and replacement scenarios
 *   - maybe_restrict_purchasability: open and gated products
 *   - shortcode_output: member / non-member / guest / admin visibility
 *   - maybe_close_comments: option on/off, restricted/open post
 *   - build_message_html internals: rule message, global defaults, merge tag
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for WPS_Restriction_Enforcer.
 *
 * @since 2.0.0
 */
class RestrictionEnforcerTest extends WP_UnitTestCase {

	/**
	 * Restriction enforcer instance under test.
	 *
	 * @var WPS_Restriction_Enforcer
	 */
	private $enforcer;

	/**
	 * Published post that rules will target.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * User who holds an active 'gold' membership.
	 *
	 * @var int
	 */
	private $member_id;

	/**
	 * User with no membership.
	 *
	 * @var int
	 */
	private $stranger_id;

	// -----------------------------------------------------------------------
	// Lifecycle
	// -----------------------------------------------------------------------

	/**
	 * Set up test fixtures before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->enforcer = new WPS_Restriction_Enforcer();

		$this->post_id = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => 'Secret content.',
			)
		);

		$this->member_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->stranger_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Grant the member user an active 'gold' plan.
		wps_create_user_membership( $this->member_id, array( 'plan_slug' => 'gold' ) );

		$this->reset_options();
		wp_cache_flush();
	}

	/**
	 * Tear down test fixtures after each test.
	 */
	public function tearDown(): void {
		wp_delete_post( $this->post_id, true );
		if ( $this->member_id ) {
			wp_delete_user( $this->member_id );
		}
		if ( $this->stranger_id ) {
			wp_delete_user( $this->stranger_id );
		}
		$this->reset_options();
		wp_cache_flush();
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/** Delete all access-rules options and global-default options. */
	private function reset_options() {
		delete_option( WPS_ACCESS_RULES_OPTION );
		delete_option( WPS_ACCESS_RULES_INDEX_OPTION );
		delete_option( 'wps_access_default_behavior' );
		delete_option( 'wps_access_logged_out_message' );
		delete_option( 'wps_access_wrong_plan_message' );
		delete_option( 'wps_access_redirect_url' );
		delete_option( 'wps_access_restrict_comments' );
		delete_option( 'wps_access_include_in_archive' );
		delete_option( 'wps_access_show_purchase_cta' );
	}

	/**
	 * Save an object-level rule requiring the 'gold' plan for $this->post_id.
	 *
	 * @param array $overrides Keys to override in the default rule.
	 */
	private function add_gold_rule( array $overrides = array() ) {
		$rule = array_merge(
			array(
				'id'           => 'r_gold',
				'target_type'  => 'post',
				'post_type'    => '',
				'object_ids'   => array( $this->post_id ),
				'taxonomy'     => '',
				'term_ids'     => array(),
				'plans'        => array( 'gold' ),
				'behavior'     => 'message',
				'message'      => '',
				'redirect_url' => '',
				'priority'     => 10,
			),
			$overrides
		);
		wps_save_access_rules( array( $rule ) );
		wp_cache_flush();
	}

	// -----------------------------------------------------------------------
	// maybe_restrict_content — singular context required
	// -----------------------------------------------------------------------

	/** Content passes through unchanged when the view is not singular. */
	public function test_content_passes_through_outside_singular() {
		$this->add_gold_rule();
		wp_set_current_user( 0 );

		// Do NOT call go_to — is_singular() will return false.
		$result = $this->enforcer->maybe_restrict_content( 'Hello World' );

		$this->assertSame( 'Hello World', $result );
	}

	/** Content passes through unchanged when no rule matches the post. */
	public function test_content_passes_through_with_no_rules() {
		// No rules saved.
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Hello World' );

		$this->assertSame( 'Hello World', $result );
	}

	/** Restriction HTML is returned for a guest when a matching rule exists. */
	public function test_content_replaced_for_guest() {
		$this->add_gold_rule();
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'wps-restricted-content', $result );
		$this->assertStringNotContainsString( 'Secret content.', $result );
	}

	/** Restriction HTML is returned for a logged-in user without the plan. */
	public function test_content_replaced_for_non_member() {
		$this->add_gold_rule();
		wp_set_current_user( $this->stranger_id );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'wps-restricted-content', $result );
	}

	/** Content passes through for a user who holds the required plan. */
	public function test_content_passes_through_for_member() {
		$this->add_gold_rule();
		wp_set_current_user( $this->member_id );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertSame( 'Secret content.', $result );
	}

	/**
	 * Content passes through when behavior is redirect and a URL is configured —
	 * maybe_redirect() would already have exited in a real request.
	 */
	public function test_content_passes_through_for_redirect_with_url() {
		$this->add_gold_rule(
			array(
				'behavior'     => 'redirect',
				'redirect_url' => 'https://example.com/subscribe',
			)
		);
		update_option( 'wps_access_redirect_url', 'https://example.com/subscribe' );
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertSame( 'Secret content.', $result );
	}

	/**
	 * Content is replaced with restriction HTML when behavior is redirect but
	 * no URL is configured (redirect has nowhere to go).
	 */
	public function test_content_replaced_for_redirect_without_url() {
		$this->add_gold_rule( array( 'behavior' => 'redirect', 'redirect_url' => '' ) );
		delete_option( 'wps_access_redirect_url' );
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'wps-restricted-content', $result );
	}

	// -----------------------------------------------------------------------
	// Message resolution
	// -----------------------------------------------------------------------

	/** Rule-specific message appears in the restriction HTML. */
	public function test_rule_message_is_used_when_set() {
		$this->add_gold_rule( array( 'message' => 'Subscriber-only area.' ) );
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'Subscriber-only area.', $result );
	}

	/** Global logged-out message is used for guests when rule has no message. */
	public function test_global_logged_out_message_used_for_guest() {
		$this->add_gold_rule();
		update_option( 'wps_access_logged_out_message', 'Please log in first.' );
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'Please log in first.', $result );
	}

	/** Global wrong-plan message is used for logged-in non-members when rule has no message. */
	public function test_global_wrong_plan_message_used_for_logged_in_non_member() {
		$this->add_gold_rule();
		update_option( 'wps_access_wrong_plan_message', 'Upgrade your plan.' );
		wp_set_current_user( $this->stranger_id );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'Upgrade your plan.', $result );
	}

	/** Hardcoded fallback appears when no message is configured anywhere. */
	public function test_fallback_message_for_guest_when_no_defaults_set() {
		$this->add_gold_rule();
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'logged in', $result );
	}

	/** {purchase_options} in rule message is replaced (even when CTA HTML is empty). */
	public function test_purchase_options_tag_is_replaced_in_rule_message() {
		$this->add_gold_rule( array( 'message' => 'Subscribe: {purchase_options}' ) );
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringNotContainsString( '{purchase_options}', $result );
	}

	/**
	 * A custom rule message renders on its own — no "Members Only" card chrome
	 * and no auto-appended CTA, even when the global show-CTA option is on.
	 */
	public function test_custom_message_has_no_card_or_auto_cta() {
		$this->add_gold_rule( array( 'message' => 'Just my words.' ) );
		update_option( 'wps_access_show_purchase_cta', '1' );
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'Just my words.', $result );
		$this->assertStringNotContainsString( 'wps-restricted-content__head', $result );
		$this->assertStringNotContainsString( 'wps-plan-purchase-cta', $result );
	}

	/** The default (no custom message) path keeps the shared "Members Only" card. */
	public function test_default_message_keeps_card_chrome() {
		$this->add_gold_rule(); // empty message → default path.
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->post_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'wps-restricted-content__head', $result );
	}

	// -----------------------------------------------------------------------
	// Behavior is consistent across post types & target configurations
	// (not limited to blog posts)
	// -----------------------------------------------------------------------

	/**
	 * Create a published object of a given post type and a rule that restricts it,
	 * then return its ID. Visits the object as a guest.
	 *
	 * @param string $post_type Post type slug.
	 * @param array  $rule      Rule overrides (target/message/etc.).
	 * @return int Object ID.
	 */
	private function restricted_object( $post_type, array $rule ) {
		$id = $this->factory->post->create(
			array(
				'post_type'    => $post_type,
				'post_status'  => 'publish',
				'post_content' => 'Secret content.',
			)
		);

		$base = array(
			'id'       => 'r_' . $post_type,
			'plans'    => array( 'gold' ),
			'behavior' => 'message',
			'priority' => 10,
		);
		wps_save_access_rules( array( array_merge( $base, $rule ) ) );
		wp_cache_flush();

		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $id ) );
		wp_cache_flush();

		return $id;
	}

	/** Custom message renders raw on a PAGE (post_type target). */
	public function test_custom_message_renders_on_a_page() {
		$this->restricted_object(
			'page',
			array(
				'target_type' => 'post_type',
				'post_type'   => 'page',
				'message'     => 'Page members only.',
			)
		);

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'Page members only.', $result );
		$this->assertStringNotContainsString( 'Secret content.', $result );
		$this->assertStringNotContainsString( 'wps-restricted-content__head', $result );
	}

	/**
	 * A whole-product rule is a product-kind rule: it gates purchase but must
	 * NOT replace the_content. The product page stays intact (the message only
	 * surfaces in the add-to-cart notice, tested in RestrictionEnforcerKindTest).
	 */
	public function test_product_rule_leaves_product_content_intact() {
		if ( ! post_type_exists( 'product' ) ) {
			$this->markTestSkipped( 'WooCommerce product post type not registered.' );
		}

		$this->restricted_object(
			'product',
			array(
				'target_type' => 'post_type',
				'post_type'   => 'product',
				'message'     => 'Product members only.',
			)
		);

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertSame( 'Secret content.', $result );
	}

	/** Custom message renders raw when the rule targets a TAXONOMY term. */
	public function test_custom_message_renders_for_taxonomy_target() {
		$term_id = $this->factory->term->create(
			array( 'taxonomy' => 'category', 'name' => 'Premium' )
		);
		$post_id = $this->factory->post->create(
			array( 'post_status' => 'publish', 'post_content' => 'Secret content.' )
		);
		wp_set_object_terms( $post_id, array( (int) $term_id ), 'category' );

		wps_save_access_rules(
			array(
				array(
					'id'          => 'r_tax',
					'target_type' => 'taxonomy',
					'taxonomy'    => 'category',
					'term_ids'    => array( (int) $term_id ),
					'plans'       => array( 'gold' ),
					'behavior'    => 'message',
					'message'     => 'Category members only.',
					'priority'    => 10,
				),
			)
		);
		wp_cache_flush();

		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $post_id ) );
		wp_cache_flush();

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'Category members only.', $result );
		$this->assertStringNotContainsString( 'wps-restricted-content__head', $result );
	}

	/** Custom message renders raw for a "Specific Page" (object-ID target). */
	public function test_custom_message_renders_for_specific_page_target() {
		$id = $this->factory->post->create(
			array( 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => 'Secret content.' )
		);

		wps_save_access_rules(
			array(
				array(
					'id'          => 'r_page_obj',
					'target_type' => 'page',
					'object_ids'  => array( (int) $id ),
					'plans'       => array( 'gold' ),
					'behavior'    => 'message',
					'message'     => 'Specific page locked.',
					'priority'    => 10,
				),
			)
		);
		wp_cache_flush();

		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $id ) );
		wp_cache_flush();

		$result = $this->enforcer->maybe_restrict_content( 'Secret content.' );

		$this->assertStringContainsString( 'Specific page locked.', $result );
		$this->assertStringNotContainsString( 'wps-restricted-content__head', $result );
	}

	// -----------------------------------------------------------------------
	// maybe_restrict_purchasability
	// -----------------------------------------------------------------------

	/** Purchasability passes through (true) for an unrestricted post. */
	public function test_purchasability_unchanged_for_unrestricted_post() {
		// No rules — post is open.
		wp_set_current_user( 0 );
		$mock = $this->createMock( WC_Product::class );
		$mock->method( 'get_id' )->willReturn( $this->post_id );

		$result = $this->enforcer->maybe_restrict_purchasability( true, $mock );

		$this->assertTrue( $result );
	}

	/** Purchasability returns false for a restricted product when user is guest. */
	public function test_purchasability_false_for_restricted_post() {
		// Purchase gating requires a product-kind rule (content rules never gate).
		$this->add_gold_rule(
			array(
				'rule_kind'   => 'product',
				'target_type' => 'product',
			)
		);
		wp_set_current_user( 0 );
		$mock = $this->createMock( WC_Product::class );
		$mock->method( 'get_id' )->willReturn( $this->post_id );

		$result = $this->enforcer->maybe_restrict_purchasability( true, $mock );

		$this->assertFalse( $result );
	}

	/** Already-false purchasability passes through without additional checks. */
	public function test_purchasability_false_input_is_returned_immediately() {
		$mock = $this->createMock( WC_Product::class );
		$mock->expects( $this->never() )->method( 'get_id' );

		$result = $this->enforcer->maybe_restrict_purchasability( false, $mock );

		$this->assertFalse( $result );
	}

	/** Purchasability passes through for a member who holds the plan. */
	public function test_purchasability_true_for_member() {
		$this->add_gold_rule(
			array(
				'rule_kind'   => 'product',
				'target_type' => 'product',
			)
		);
		wp_set_current_user( $this->member_id );
		$mock = $this->createMock( WC_Product::class );
		$mock->method( 'get_id' )->willReturn( $this->post_id );

		$result = $this->enforcer->maybe_restrict_purchasability( true, $mock );

		$this->assertTrue( $result );
	}

	// -----------------------------------------------------------------------
	// shortcode_output
	// -----------------------------------------------------------------------

	/** Member with the required plan sees the enclosed content. */
	public function test_shortcode_shows_content_to_member() {
		wp_set_current_user( $this->member_id );

		$result = $this->enforcer->shortcode_output(
			array( 'plans' => 'gold' ),
			'Member-only text.'
		);

		$this->assertStringContainsString( 'Member-only text.', $result );
	}

	/** Non-member user gets an empty string. */
	public function test_shortcode_hides_content_from_non_member() {
		wp_set_current_user( $this->stranger_id );

		$result = $this->enforcer->shortcode_output(
			array( 'plans' => 'gold' ),
			'Member-only text.'
		);

		$this->assertSame( '', $result );
	}

	/** Guest (logged out) gets an empty string. */
	public function test_shortcode_hides_content_from_guest() {
		wp_set_current_user( 0 );

		$result = $this->enforcer->shortcode_output(
			array( 'plans' => 'gold' ),
			'Member-only text.'
		);

		$this->assertSame( '', $result );
	}

	/** Administrator always sees shortcode content regardless of plan. */
	public function test_shortcode_shows_content_to_admin() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$result = $this->enforcer->shortcode_output(
			array( 'plans' => 'gold' ),
			'Admin text.'
		);

		$this->assertStringContainsString( 'Admin text.', $result );
		wp_delete_user( $admin_id );
	}

	/** 'any' plan attribute shows content to any active member. */
	public function test_shortcode_any_plan_shows_content_to_any_member() {
		// member_id has 'gold' — should pass 'any'.
		wp_set_current_user( $this->member_id );

		$result = $this->enforcer->shortcode_output(
			array( 'plans' => 'any' ),
			'Any-member text.'
		);

		$this->assertStringContainsString( 'Any-member text.', $result );
	}

	// -----------------------------------------------------------------------
	// maybe_close_comments
	// -----------------------------------------------------------------------

	/** Comments stay open when the global restrict-comments option is off. */
	public function test_comments_open_when_option_disabled() {
		$this->add_gold_rule();
		delete_option( 'wps_access_restrict_comments' );
		wp_set_current_user( 0 );

		$result = $this->enforcer->maybe_close_comments( true, $this->post_id );

		$this->assertTrue( $result );
	}

	/** Comments are closed for a guest when option is on and post is restricted. */
	public function test_comments_closed_for_guest_when_option_on() {
		$this->add_gold_rule();
		update_option( 'wps_access_restrict_comments', '1' );
		wp_set_current_user( 0 );

		$result = $this->enforcer->maybe_close_comments( true, $this->post_id );

		$this->assertFalse( $result );
	}

	/** Comments stay open when option is on but the post has no matching rule. */
	public function test_comments_open_when_no_rule_matches() {
		// No rules saved.
		update_option( 'wps_access_restrict_comments', '1' );
		wp_set_current_user( 0 );

		$result = $this->enforcer->maybe_close_comments( true, $this->post_id );

		$this->assertTrue( $result );
	}

	/** Already-closed comments are never re-opened by this filter. */
	public function test_closed_comments_are_not_reopened() {
		delete_option( 'wps_access_restrict_comments' );

		$result = $this->enforcer->maybe_close_comments( false, $this->post_id );

		$this->assertFalse( $result );
	}

	/** Comments stay open for a member even when option is on. */
	public function test_comments_open_for_member_when_option_on() {
		$this->add_gold_rule();
		update_option( 'wps_access_restrict_comments', '1' );
		wp_set_current_user( $this->member_id );

		$result = $this->enforcer->maybe_close_comments( true, $this->post_id );

		$this->assertTrue( $result );
	}
}
