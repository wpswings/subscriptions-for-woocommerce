<?php
/**
 * Unit tests for Day 16 deliverables (Pro — Advanced Access Rules):
 *   Free:  drip + exclusion fields in wps_sanitize_access_rule()
 *          the `wps_object_is_restricted` filter hook in the resolver
 *   Pro:   WPS_Advanced_Rules enforcement (drip scheduling + rule exclusions)
 *
 * The Pro plugin is not loaded by the Free test bootstrap, so the enforcement
 * class file is required directly — it depends only on Free membership functions.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class AdvancedRulesTest extends WP_UnitTestCase {

	/** @var int */
	private $user_id;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$pro_class = dirname( __DIR__, 4 )
			. '/woocommerce-subscriptions-pro/includes/pro/class-wps-advanced-rules.php';
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
				'id'          => 'r_adv',
				'target_type' => 'post_type',
				'post_type'   => 'post',
				'plans'       => array( 'gold' ),
				'behavior'    => 'message',
				'priority'    => 10,
			),
			$overrides
		);
		wps_save_access_rules( array( $rule ) );
		wp_cache_flush();
	}

	/** Hook the Pro enforcement class onto the resolver filter. */
	private function enable_pro_enforcement() {
		$advanced = new WPS_Advanced_Rules();
		add_filter( 'wps_object_is_restricted', array( $advanced, 'filter_object_restriction' ), 10, 4 );
		return $advanced;
	}

	/** Resolve restriction for a fresh post, bypassing the per-request cache. */
	private function resolve( $post_id ) {
		wp_cache_flush();
		return wps_object_is_restricted( get_post( $post_id ), $this->user_id );
	}

	// -----------------------------------------------------------------------
	// Free — sanitizer
	// -----------------------------------------------------------------------

	public function test_sanitizer_persists_drip_and_exclusion_fields() {
		$clean = wps_sanitize_access_rule(
			array(
				'id'          => 'r1',
				'target_type' => 'post_type',
				'post_type'   => 'post',
				'drip_mode'   => 'days',
				'drip_days'   => '7',
				'drip_date'   => '2026-07-01',
				'exclude_ids' => array( '42', 0, '108' ),
			)
		);

		$this->assertSame( 'days', $clean['drip_mode'] );
		$this->assertSame( 7, $clean['drip_days'] );
		$this->assertSame( '2026-07-01', $clean['drip_date'] );
		$this->assertSame( array( 42, 108 ), $clean['exclude_ids'] );
	}

	public function test_sanitizer_parses_csv_exclusions() {
		$clean = wps_sanitize_access_rule(
			array(
				'target_type' => 'post_type',
				'exclude_ids' => '42, 108, abc, 0, 42',
			)
		);

		// CSV parsed, non-numeric/zero dropped, duplicates removed.
		$this->assertSame( array( 42, 108 ), $clean['exclude_ids'] );
	}

	public function test_sanitizer_rejects_invalid_drip_mode() {
		$clean = wps_sanitize_access_rule(
			array(
				'target_type' => 'post_type',
				'drip_mode'   => 'bogus',
			)
		);
		$this->assertSame( 'none', $clean['drip_mode'] );
	}

	public function test_sanitizer_rejects_malformed_drip_date() {
		$clean = wps_sanitize_access_rule(
			array(
				'target_type' => 'post_type',
				'drip_date'   => 'not-a-date',
			)
		);
		$this->assertSame( '', $clean['drip_date'] );
	}

	public function test_sanitizer_defaults_when_advanced_fields_absent() {
		$clean = wps_sanitize_access_rule( array( 'target_type' => 'post_type' ) );
		$this->assertSame( 'none', $clean['drip_mode'] );
		$this->assertSame( 0, $clean['drip_days'] );
		$this->assertSame( '', $clean['drip_date'] );
		$this->assertSame( array(), $clean['exclude_ids'] );
	}

	// -----------------------------------------------------------------------
	// Free — resolver filter contract
	// -----------------------------------------------------------------------

	public function test_resolver_applies_wps_object_is_restricted_filter() {
		$post_id = $this->factory->post->create();
		$this->save_rule(); // user lacks 'gold' → base decision is restricted.

		// Filter forces "granted" regardless of the base decision.
		add_filter( 'wps_object_is_restricted', '__return_null' );

		$this->assertNull( $this->resolve( $post_id ) );
	}

	public function test_resolver_filter_can_force_restriction() {
		$post_id = $this->factory->post->create();
		// No rules at all → base decision would be null (granted) and the
		// resolver short-circuits before the filter, so add a matching rule
		// the user satisfies, then have the filter override to restricted.
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		$this->save_rule();

		$forced = array( 'id' => 'forced', 'plans' => array( 'gold' ) );
		add_filter(
			'wps_object_is_restricted',
			function () use ( $forced ) {
				return $forced;
			}
		);

		$this->assertSame( $forced, $this->resolve( $post_id ) );
	}

	// -----------------------------------------------------------------------
	// Pro — exclusions
	// -----------------------------------------------------------------------

	public function test_exclusion_grants_access_to_listed_post() {
		if ( ! class_exists( 'WPS_Advanced_Rules' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		$post_id = $this->factory->post->create();
		$this->save_rule( array( 'exclude_ids' => array( $post_id ) ) );
		$this->enable_pro_enforcement();

		// User lacks 'gold', but this post is excluded → access granted.
		$this->assertNull( $this->resolve( $post_id ) );
	}

	public function test_non_excluded_post_still_restricted() {
		if ( ! class_exists( 'WPS_Advanced_Rules' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		$post_id = $this->factory->post->create();
		$this->save_rule( array( 'exclude_ids' => array( $post_id + 999 ) ) );
		$this->enable_pro_enforcement();

		$this->assertNotNull( $this->resolve( $post_id ) );
	}

	// -----------------------------------------------------------------------
	// Pro — drip (fixed date)
	// -----------------------------------------------------------------------

	public function test_drip_future_date_blocks_plan_holder() {
		if ( ! class_exists( 'WPS_Advanced_Rules' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		$post_id = $this->factory->post->create();
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		$future  = gmdate( 'Y-m-d', time() + ( 30 * DAY_IN_SECONDS ) );
		$this->save_rule( array( 'drip_mode' => 'date', 'drip_date' => $future ) );
		$this->enable_pro_enforcement();

		// Plan holder, but the unlock date is in the future → restricted.
		$this->assertNotNull( $this->resolve( $post_id ) );
	}

	public function test_drip_past_date_grants_plan_holder() {
		if ( ! class_exists( 'WPS_Advanced_Rules' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		$post_id = $this->factory->post->create();
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		$past    = gmdate( 'Y-m-d', time() - ( 2 * DAY_IN_SECONDS ) );
		$this->save_rule( array( 'drip_mode' => 'date', 'drip_date' => $past ) );
		$this->enable_pro_enforcement();

		$this->assertNull( $this->resolve( $post_id ) );
	}

	// -----------------------------------------------------------------------
	// Pro — drip (days after membership start)
	// -----------------------------------------------------------------------

	public function test_drip_days_not_elapsed_blocks_plan_holder() {
		if ( ! class_exists( 'WPS_Advanced_Rules' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		$post_id = $this->factory->post->create();
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'gold', 'start_date' => time() )
		);
		$this->save_rule( array( 'drip_mode' => 'days', 'drip_days' => 7 ) );
		$this->enable_pro_enforcement();

		// Started today, 7-day drip → still locked.
		$this->assertNotNull( $this->resolve( $post_id ) );
	}

	public function test_drip_days_elapsed_grants_plan_holder() {
		if ( ! class_exists( 'WPS_Advanced_Rules' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		$post_id = $this->factory->post->create();
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'gold', 'start_date' => time() - ( 10 * DAY_IN_SECONDS ) )
		);
		$this->save_rule( array( 'drip_mode' => 'days', 'drip_days' => 7 ) );
		$this->enable_pro_enforcement();

		// Started 10 days ago, 7-day drip → unlocked.
		$this->assertNull( $this->resolve( $post_id ) );
	}

	public function test_drip_does_not_grant_when_user_lacks_plan() {
		if ( ! class_exists( 'WPS_Advanced_Rules' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		$post_id = $this->factory->post->create();
		// No membership at all; drip is irrelevant — must stay restricted.
		$this->save_rule( array( 'drip_mode' => 'days', 'drip_days' => 7 ) );
		$this->enable_pro_enforcement();

		$this->assertNotNull( $this->resolve( $post_id ) );
	}
}
