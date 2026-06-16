<?php
/**
 * Unit tests for Day 18 deliverables (Pro — Template restriction + teaser).
 *
 * Covers:
 *   Free:  'template' behavior + teaser fields in wps_sanitize_access_rule()
 *   Pro:   WPS_Template_Restriction::build_teaser()
 *          WPS_Template_Restriction::build_message_html()
 *          WPS_Template_Restriction::maybe_use_restricted_template() (template swap)
 *
 * The Pro plugin is not loaded by the Free test bootstrap, so the enforcement
 * class file is required directly — it depends only on Free membership functions.
 *
 * @package Subscriptions_For_Woocommerce
 * @since   2.0.0
 */

/**
 * Test suite for template-based content restriction and teaser functionality.
 *
 * @since 2.0.0
 */
class TemplateRestrictionTest extends WP_UnitTestCase {

	/** @var int */
	private $user_id;

	/**
	 * One-time setup: load the Pro restriction class when the Pro plugin is present.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$pro_dir = dirname( __DIR__, 4 ) . '/woocommerce-subscriptions-pro/';

		// The class references this constant when locating its default template;
		// the Pro bootstrap normally defines it, so stand it in for the test env.
		if ( ! defined( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_DIR_PATH' ) ) {
			define( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_DIR_PATH', $pro_dir );
		}

		$pro_class = $pro_dir . 'includes/pro/class-wps-template-restriction.php';
		if ( file_exists( $pro_class ) ) {
			require_once $pro_class;
		}
	}

	/**
	 * Per-test setup: reset access-rule options and create a subscriber user.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( WPS_ACCESS_RULES_OPTION );
		delete_option( WPS_ACCESS_RULES_INDEX_OPTION );
		wp_cache_flush();

		$this->user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Persist a single rule and (re)build the index.
	 *
	 * @param array $overrides Fields to merge over the default rule stub.
	 * @return void
	 */
	private function save_rule( array $overrides = array() ) {
		$rule = array_merge(
			array(
				'id'          => 'r_tpl',
				'target_type' => 'post_type',
				'post_type'   => 'post',
				'plans'       => array( 'gold' ),
				'behavior'    => 'template',
				'priority'    => 10,
			),
			$overrides
		);
		wps_save_access_rules( array( $rule ) );
		wp_cache_flush();
	}

	/**
	 * Skip a Pro-only test cleanly when the Pro plugin is absent.
	 *
	 * @return void
	 */
	private function require_pro() {
		if ( ! class_exists( 'WPS_Template_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}
	}

	// -----------------------------------------------------------------------
	// Free — sanitizer
	// -----------------------------------------------------------------------

	/**
	 * Test that the sanitizer accepts the 'template' behavior value.
	 *
	 * @return void
	 */
	public function test_sanitizer_accepts_template_behavior() {
		$clean = wps_sanitize_access_rule(
			array(
				'target_type' => 'post_type',
				'post_type'   => 'post',
				'behavior'    => 'template',
			)
		);
		$this->assertSame( 'template', $clean['behavior'] );
	}

	/**
	 * Test that the sanitizer persists teaser_mode and teaser_words fields.
	 *
	 * @return void
	 */
	public function test_sanitizer_persists_teaser_fields() {
		$clean = wps_sanitize_access_rule(
			array(
				'target_type'  => 'post_type',
				'behavior'     => 'template',
				'teaser_mode'  => 'words',
				'teaser_words' => '40',
			)
		);
		$this->assertSame( 'words', $clean['teaser_mode'] );
		$this->assertSame( 40, $clean['teaser_words'] );
	}

	/**
	 * Test that the sanitizer rejects an invalid teaser_mode value.
	 *
	 * @return void
	 */
	public function test_sanitizer_rejects_invalid_teaser_mode() {
		$clean = wps_sanitize_access_rule(
			array(
				'target_type' => 'post_type',
				'teaser_mode' => 'bogus',
			)
		);
		$this->assertSame( 'none', $clean['teaser_mode'] );
	}

	/**
	 * Test that the sanitizer defaults teaser fields when they are absent.
	 *
	 * @return void
	 */
	public function test_sanitizer_defaults_teaser_fields_when_absent() {
		$clean = wps_sanitize_access_rule( array( 'target_type' => 'post_type' ) );
		$this->assertSame( 'none', $clean['teaser_mode'] );
		$this->assertSame( 0, $clean['teaser_words'] );
	}

	/**
	 * Test that the sanitizer coerces a negative teaser_words value to positive.
	 *
	 * @return void
	 */
	public function test_sanitizer_coerces_negative_teaser_words() {
		$clean = wps_sanitize_access_rule(
			array(
				'target_type'  => 'post_type',
				'teaser_words' => '-5',
			)
		);
		$this->assertSame( 5, $clean['teaser_words'] );
	}

	// -----------------------------------------------------------------------
	// Pro — teaser building
	// -----------------------------------------------------------------------

	/**
	 * Test that build_teaser() returns an empty string when teaser_mode is 'none'.
	 *
	 * @return void
	 */
	public function test_teaser_none_returns_empty() {
		$this->require_pro();
		$post = get_post(
			$this->factory->post->create( array( 'post_content' => 'One two three four five.' ) )
		);
		$pro  = new WPS_Template_Restriction();

		$this->assertSame( '', $pro->build_teaser( $post, array( 'teaser_mode' => 'none' ) ) );
	}

	/**
	 * Test that build_teaser() trims content to the requested word count.
	 *
	 * @return void
	 */
	public function test_teaser_words_trims_to_count() {
		$this->require_pro();
		$post = get_post(
			$this->factory->post->create(
				array( 'post_content' => 'alpha beta gamma delta epsilon zeta eta theta' )
			)
		);
		$pro  = new WPS_Template_Restriction();

		$out = $pro->build_teaser(
			$post,
			array( 'teaser_mode' => 'words', 'teaser_words' => 3 )
		);

		$this->assertStringContainsString( 'alpha', $out );
		$this->assertStringContainsString( 'gamma', $out );
		$this->assertStringNotContainsString( 'delta', $out );
		// wp_trim_words appends the ellipsis we pass in.
		$this->assertStringContainsString( '&hellip;', $out );
	}

	/**
	 * Test that build_teaser() returns an empty string when teaser_words is zero.
	 *
	 * @return void
	 */
	public function test_teaser_words_zero_count_returns_empty() {
		$this->require_pro();
		$post = get_post(
			$this->factory->post->create( array( 'post_content' => 'alpha beta gamma' ) )
		);
		$pro  = new WPS_Template_Restriction();

		$this->assertSame(
			'',
			$pro->build_teaser( $post, array( 'teaser_mode' => 'words', 'teaser_words' => 0 ) )
		);
	}

	/**
	 * Test that build_teaser() returns an empty string when post content is blank.
	 *
	 * @return void
	 */
	public function test_teaser_empty_content_returns_empty() {
		$this->require_pro();
		$post = get_post( $this->factory->post->create( array( 'post_content' => '   ' ) ) );
		$pro  = new WPS_Template_Restriction();

		$this->assertSame(
			'',
			$pro->build_teaser( $post, array( 'teaser_mode' => 'words', 'teaser_words' => 10 ) )
		);
	}

	// -----------------------------------------------------------------------
	// Pro — restriction message
	// -----------------------------------------------------------------------

	/**
	 * Test that build_message_html() outputs the shared card markup.
	 *
	 * @return void
	 */
	public function test_message_uses_shared_card_markup() {
		$this->require_pro();
		$post = get_post( $this->factory->post->create() );
		$pro  = new WPS_Template_Restriction();

		$out = $pro->build_message_html(
			array( 'plans' => array( 'gold' ), 'behavior' => 'template' ),
			$post,
			0
		);

		$this->assertStringContainsString( 'wps-restricted-content__head', $out );
		$this->assertStringContainsString( 'wps-restricted-content__msg', $out );
		$this->assertStringContainsString( 'wps-restricted-template', $out );
	}

	/**
	 * Test that build_message_html() replaces the {purchase_options} merge tag.
	 *
	 * @return void
	 */
	public function test_message_replaces_purchase_options_merge_tag() {
		$this->require_pro();
		update_option( 'wps_access_wrong_plan_message', 'Upgrade now {purchase_options}' );

		$post = get_post( $this->factory->post->create() );
		$pro  = new WPS_Template_Restriction();

		$out = $pro->build_message_html(
			array( 'plans' => array( 'gold' ), 'behavior' => 'template' ),
			$post,
			$this->user_id
		);

		$this->assertStringNotContainsString( '{purchase_options}', $out );
		$this->assertStringContainsString( 'Upgrade now', $out );

		delete_option( 'wps_access_wrong_plan_message' );
	}

	/**
	 * Test that build_message_html() uses rule-specific message text when provided.
	 *
	 * @return void
	 */
	public function test_message_uses_rule_specific_text() {
		$this->require_pro();
		$post = get_post( $this->factory->post->create() );
		$pro  = new WPS_Template_Restriction();

		$out = $pro->build_message_html(
			array(
				'plans'    => array( 'gold' ),
				'behavior' => 'template',
				'message'  => 'Bespoke locked notice.',
			),
			$post,
			$this->user_id
		);

		$this->assertStringContainsString( 'Bespoke locked notice.', $out );
	}

	/**
	 * Test that a custom message suppresses card chrome and the auto-CTA.
	 *
	 * @return void
	 */
	public function test_custom_message_renders_without_card_or_auto_cta() {
		$this->require_pro();
		// Global auto-CTA on: a custom message must still suppress both the
		// "Members Only" card chrome and the auto-appended purchase CTA.
		update_option( 'wps_access_show_purchase_cta', '1' );

		$post = get_post( $this->factory->post->create() );
		$pro  = new WPS_Template_Restriction();

		$out = $pro->build_message_html(
			array(
				'plans'    => array( 'gold' ),
				'behavior' => 'template',
				'message'  => 'Just my words.',
			),
			$post,
			0
		);

		$this->assertStringContainsString( 'Just my words.', $out );
		$this->assertStringNotContainsString( 'wps-restricted-content__head', $out );
		$this->assertStringNotContainsString( 'Members Only', $out );
		$this->assertStringNotContainsString( 'wps-plan-purchase-cta', $out );

		delete_option( 'wps_access_show_purchase_cta' );
	}

	/**
	 * Test that a custom message still expands the {purchase_options} tag.
	 *
	 * @return void
	 */
	public function test_custom_message_still_expands_purchase_options_tag() {
		$this->require_pro();
		$post = get_post( $this->factory->post->create() );
		$pro  = new WPS_Template_Restriction();

		$out = $pro->build_message_html(
			array(
				'plans'    => array( 'gold' ),
				'behavior' => 'template',
				'message'  => 'Buy here: {purchase_options}',
			),
			$post,
			0
		);

		// The literal tag must never reach the page even in a custom message.
		$this->assertStringContainsString( 'Buy here:', $out );
		$this->assertStringNotContainsString( '{purchase_options}', $out );
	}

	// -----------------------------------------------------------------------
	// Pro — template_include swap
	// -----------------------------------------------------------------------

	/**
	 * Test that the restricted template is swapped in for a blocked guest user.
	 *
	 * @return void
	 */
	public function test_template_swapped_for_blocked_guest() {
		$this->require_pro();
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->save_rule( array( 'behavior' => 'template' ) );

		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $post_id ) );
		wp_cache_flush();

		$pro = new WPS_Template_Restriction();
		$out = $pro->maybe_use_restricted_template( '/theme/single.php' );

		$this->assertStringEndsWith( 'restricted-content.php', $out );

		$ctx = WPS_Template_Restriction::context();
		$this->assertIsArray( $ctx );
		$this->assertSame( (int) $post_id, (int) $ctx['post']->ID );
	}

	/**
	 * Test that the restricted template is swapped in for a blocked 'page' post type.
	 *
	 * @return void
	 */
	public function test_template_swapped_for_page_post_type() {
		$this->require_pro();
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		// Rule targets the whole 'page' post type with template behavior.
		wps_save_access_rules(
			array(
				array(
					'id'          => 'r_page_tpl',
					'target_type' => 'post_type',
					'post_type'   => 'page',
					'plans'       => array( 'gold' ),
					'behavior'    => 'template',
					'priority'    => 10,
				),
			)
		);
		wp_cache_flush();

		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $page_id ) );
		wp_cache_flush();

		$pro = new WPS_Template_Restriction();
		$out = $pro->maybe_use_restricted_template( '/theme/page.php' );

		$this->assertStringEndsWith( 'restricted-content.php', $out );
	}

	/**
	 * Test that the template is not swapped when the rule behavior is 'message'.
	 *
	 * @return void
	 */
	public function test_template_not_swapped_for_message_behavior() {
		$this->require_pro();
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->save_rule( array( 'behavior' => 'message' ) );

		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $post_id ) );
		wp_cache_flush();

		$pro = new WPS_Template_Restriction();
		$out = $pro->maybe_use_restricted_template( '/theme/single.php' );

		$this->assertSame( '/theme/single.php', $out );
	}

	/**
	 * Test that the template is not swapped for a member who holds the required plan.
	 *
	 * @return void
	 */
	public function test_template_not_swapped_for_member_with_plan() {
		$this->require_pro();
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->save_rule( array( 'behavior' => 'template' ) );

		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		wp_set_current_user( $this->user_id );
		$this->go_to( get_permalink( $post_id ) );
		wp_cache_flush();

		$pro = new WPS_Template_Restriction();
		$out = $pro->maybe_use_restricted_template( '/theme/single.php' );

		$this->assertSame( '/theme/single.php', $out );
	}

	/**
	 * Test that administrators bypass the template swap entirely.
	 *
	 * @return void
	 */
	public function test_admin_bypasses_template_swap() {
		$this->require_pro();
		$admin   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->save_rule( array( 'behavior' => 'template' ) );

		wp_set_current_user( $admin );
		$this->go_to( get_permalink( $post_id ) );
		wp_cache_flush();

		$pro = new WPS_Template_Restriction();
		$out = $pro->maybe_use_restricted_template( '/theme/single.php' );

		$this->assertSame( '/theme/single.php', $out );
	}
}
