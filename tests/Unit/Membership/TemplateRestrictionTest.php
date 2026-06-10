<?php
/**
 * Unit tests for Day 18 deliverables (Pro — Template restriction + teaser):
 *   Free:  'template' behavior + teaser fields in wps_sanitize_access_rule()
 *   Pro:   WPS_Template_Restriction::build_teaser()
 *          WPS_Template_Restriction::build_message_html()
 *          WPS_Template_Restriction::maybe_use_restricted_template() (template swap)
 *
 * The Pro plugin is not loaded by the Free test bootstrap, so the enforcement
 * class file is required directly — it depends only on Free membership functions.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class TemplateRestrictionTest extends WP_UnitTestCase {

	/** @var int */
	private $user_id;

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

	/** Persist a single rule and (re)build the index. */
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

	/** Skip a Pro-only test cleanly when the Pro plugin is absent. */
	private function require_pro() {
		if ( ! class_exists( 'WPS_Template_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}
	}

	// -----------------------------------------------------------------------
	// Free — sanitizer
	// -----------------------------------------------------------------------

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

	public function test_sanitizer_rejects_invalid_teaser_mode() {
		$clean = wps_sanitize_access_rule(
			array(
				'target_type' => 'post_type',
				'teaser_mode' => 'bogus',
			)
		);
		$this->assertSame( 'none', $clean['teaser_mode'] );
	}

	public function test_sanitizer_defaults_teaser_fields_when_absent() {
		$clean = wps_sanitize_access_rule( array( 'target_type' => 'post_type' ) );
		$this->assertSame( 'none', $clean['teaser_mode'] );
		$this->assertSame( 0, $clean['teaser_words'] );
	}

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

	public function test_teaser_none_returns_empty() {
		$this->require_pro();
		$post = get_post(
			$this->factory->post->create( array( 'post_content' => 'One two three four five.' ) )
		);
		$pro  = new WPS_Template_Restriction();

		$this->assertSame( '', $pro->build_teaser( $post, array( 'teaser_mode' => 'none' ) ) );
	}

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

	// -----------------------------------------------------------------------
	// Pro — template_include swap
	// -----------------------------------------------------------------------

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
