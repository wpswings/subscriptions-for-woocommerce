<?php
/**
 * Test suite for Access Rules.
 *
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Unit tests for Day 11 deliverables:
 *   includes/membership/functions-access-rules.php
 *   includes/membership/class-wps-access-rules-engine.php
 *
 * Covers: CRUD (get/save/add/delete), index builder, and resolver
 * (get_rules_for_object + wps_object_is_restricted).
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */
class AccessRulesTest extends WP_UnitTestCase {

	/**
	 * Sets up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( WPS_ACCESS_RULES_OPTION );
		delete_option( WPS_ACCESS_RULES_INDEX_OPTION );
		wp_cache_flush();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Return a minimal valid rule array with the given overrides.
	 *
	 * @param array $overrides Optional overrides.
	 * @return array
	 */
	private function make_rule( array $overrides = array() ) {
		return array_merge(
			array(
				'id'           => 'r_test',
				'target_type'  => 'post_type',
				'post_type'    => 'post',
				'object_ids'   => array(),
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
	}

	// -----------------------------------------------------------------------
	// wps_get_access_rules()
	// -----------------------------------------------------------------------

	/**
	 * Tests that get access rules returns empty array when option is absent.
	 */
	public function test_get_access_rules_returns_empty_array_when_option_absent() {
		$this->assertSame( array(), wps_get_access_rules() );
	}

	/**
	 * Tests that get access rules returns stored rules.
	 */
	public function test_get_access_rules_returns_stored_rules() {
		$rule = $this->make_rule( array( 'id' => 'r_stored' ) );
		update_option( WPS_ACCESS_RULES_OPTION, array( $rule ) );
		wp_cache_delete( 'all_rules', WPS_ACCESS_RULES_CACHE_GROUP );

		$rules = wps_get_access_rules();
		$this->assertCount( 1, $rules );
		$this->assertSame( 'r_stored', $rules[0]['id'] );
	}

	/**
	 * Tests that get access rules returns cached result on second call.
	 */
	public function test_get_access_rules_returns_cached_result_on_second_call() {
		wp_cache_set( 'all_rules', array( array( 'id' => 'from_cache' ) ), WPS_ACCESS_RULES_CACHE_GROUP );
		$rules = wps_get_access_rules();
		$this->assertSame( 'from_cache', $rules[0]['id'] );
	}

	// -----------------------------------------------------------------------
	// wps_sanitize_access_rule()
	// -----------------------------------------------------------------------

	/**
	 * Tests that sanitize rule enforces valid target type.
	 */
	public function test_sanitize_rule_enforces_valid_target_type() {
		$rule = wps_sanitize_access_rule( array( 'target_type' => 'INVALID' ) );
		$this->assertSame( 'post_type', $rule['target_type'] );
	}

	/**
	 * Tests that sanitize rule enforces valid behavior.
	 */
	public function test_sanitize_rule_enforces_valid_behavior() {
		$rule = wps_sanitize_access_rule( array( 'behavior' => 'evil_script' ) );
		$this->assertSame( 'message', $rule['behavior'] );
	}

	/**
	 * Tests that sanitize rule defaults plans to any when empty.
	 */
	public function test_sanitize_rule_defaults_plans_to_any_when_empty() {
		$rule = wps_sanitize_access_rule( array( 'plans' => array() ) );
		$this->assertSame( array( 'any' ), $rule['plans'] );
	}

	/**
	 * Tests that sanitize rule slugifies plan names.
	 */
	public function test_sanitize_rule_slugifies_plan_names() {
		$rule = wps_sanitize_access_rule( array( 'plans' => array( 'Gold Plan!' ) ) );
		// sanitize_key lowercases and strips non [a-z0-9_-] chars, including spaces.
		$this->assertContains( 'goldplan', $rule['plans'] );
	}

	/**
	 * Tests that sanitize rule absinths object ids.
	 */
	public function test_sanitize_rule_absinths_object_ids() {
		$rule = wps_sanitize_access_rule( array( 'object_ids' => array( '-5', '42', 'abc' ) ) );
		// absint('-5') = 5 (not 0); 'abc' → 0 filtered; '42' → 42.
		$this->assertSame( array( 5, 42 ), $rule['object_ids'] );
	}

	/**
	 * Tests that sanitize rule strips disallowed redirect URL.
	 */
	public function test_sanitize_rule_strips_disallowed_redirect_url() {
		$rule = wps_sanitize_access_rule( array( 'redirect_url' => 'javascript:alert(1)' ) );
		$this->assertSame( '', $rule['redirect_url'] );
	}

	// -----------------------------------------------------------------------
	// wps_save_access_rules()
	// -----------------------------------------------------------------------

	/**
	 * Tests that save access rules persists to option.
	 */
	public function test_save_access_rules_persists_to_option() {
		wps_save_access_rules( array( $this->make_rule() ) );
		$stored = get_option( WPS_ACCESS_RULES_OPTION );
		$this->assertIsArray( $stored );
		$this->assertCount( 1, $stored );
	}

	/**
	 * Tests that save access rules busts object cache.
	 */
	public function test_save_access_rules_busts_object_cache() {
		wp_cache_set( 'all_rules', array( 'stale' ), WPS_ACCESS_RULES_CACHE_GROUP );
		wps_save_access_rules( array() );
		// save deletes the cache entry; the next read re-warms it.
		$cached = wp_cache_get( 'all_rules', WPS_ACCESS_RULES_CACHE_GROUP );
		$this->assertFalse( $cached );
	}

	/**
	 * Tests that save access rules rebuilds index.
	 */
	public function test_save_access_rules_rebuilds_index() {
		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'        => 'r_idx',
						'post_type' => 'page',
					)
				),
			)
		);
		$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION );
		$this->assertIsArray( $index );
		$this->assertArrayHasKey( 'page', $index['post_type'] );
	}

	/**
	 * Tests that save access rules skips non-array entries.
	 */
	public function test_save_access_rules_skips_non_array_entries() {
		wps_save_access_rules( array( $this->make_rule(), 'not_an_array', 42 ) );
		$stored = get_option( WPS_ACCESS_RULES_OPTION );
		$this->assertCount( 1, $stored );
	}

	// -----------------------------------------------------------------------
	// wps_add_access_rule()
	// -----------------------------------------------------------------------

	/**
	 * Tests that add access rule returns string id.
	 */
	public function test_add_access_rule_returns_string_id() {
		$id = wps_add_access_rule( $this->make_rule() );
		$this->assertIsString( $id );
		$this->assertNotEmpty( $id );
	}

	/**
	 * Tests that add access rule appends to existing rules.
	 */
	public function test_add_access_rule_appends_to_existing_rules() {
		wps_save_access_rules( array( $this->make_rule( array( 'id' => 'r_first' ) ) ) );
		wps_add_access_rule( $this->make_rule( array( 'post_type' => 'page' ) ) );

		$rules = wps_get_access_rules();
		$this->assertCount( 2, $rules );
	}

	/**
	 * Tests that add access rule ignores caller supplied id.
	 */
	public function test_add_access_rule_ignores_caller_supplied_id() {
		$id = wps_add_access_rule( $this->make_rule( array( 'id' => 'caller_id' ) ) );
		// The returned ID is generated, never the caller-supplied one.
		$this->assertNotSame( 'caller_id', $id );
	}

	/**
	 * Tests that add access rule generates unique ids on repeated calls.
	 */
	public function test_add_access_rule_generates_unique_ids_on_repeated_calls() {
		$id1 = wps_add_access_rule( $this->make_rule( array( 'post_type' => 'post' ) ) );
		$id2 = wps_add_access_rule( $this->make_rule( array( 'post_type' => 'page' ) ) );
		$this->assertNotSame( $id1, $id2 );
	}

	// -----------------------------------------------------------------------
	// wps_delete_access_rule()
	// -----------------------------------------------------------------------

	/**
	 * Tests that delete access rule returns false for nonexistent id.
	 */
	public function test_delete_access_rule_returns_false_for_nonexistent_id() {
		$this->assertFalse( wps_delete_access_rule( 'does_not_exist' ) );
	}

	/**
	 * Tests that delete access rule removes rule and returns true.
	 */
	public function test_delete_access_rule_removes_rule_and_returns_true() {
		$id = wps_add_access_rule( $this->make_rule() );
		$this->assertTrue( wps_delete_access_rule( $id ) );

		$ids = array_column( wps_get_access_rules(), 'id' );
		$this->assertNotContains( $id, $ids );
	}

	/**
	 * Tests that delete access rule leaves other rules intact.
	 */
	public function test_delete_access_rule_leaves_other_rules_intact() {
		$id1 = wps_add_access_rule( $this->make_rule( array( 'post_type' => 'post' ) ) );
		$id2 = wps_add_access_rule( $this->make_rule( array( 'post_type' => 'page' ) ) );

		wps_delete_access_rule( $id1 );

		$ids = array_column( wps_get_access_rules(), 'id' );
		$this->assertContains( $id2, $ids );
		$this->assertNotContains( $id1, $ids );
	}

	// -----------------------------------------------------------------------
	// Index builder — wps_rebuild_access_rules_index() / WPS_Access_Rules_Engine
	// -----------------------------------------------------------------------

	/**
	 * Tests that index post type bucket is populated for post type rule.
	 */
	public function test_index_post_type_bucket_populated_for_post_type_rule() {
		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'          => 'r_pt',
						'target_type' => 'post_type',
						'post_type'   => 'post',
					)
				),
			)
		);

		$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION );
		$this->assertContains( 'r_pt', $index['post_type']['post'] );
	}

	/**
	 * Tests that index object bucket is populated for specific ids.
	 */
	public function test_index_object_bucket_populated_for_specific_ids() {
		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'          => 'r_obj',
						'target_type' => 'product',
						'object_ids'  => array( 55, 56 ),
					)
				),
			)
		);

		$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION );
		$this->assertContains( 'r_obj', $index['object'][55] );
		$this->assertContains( 'r_obj', $index['object'][56] );
	}

	/**
	 * Tests that index falls back to post type bucket when object ids empty.
	 */
	public function test_index_falls_back_to_post_type_bucket_when_object_ids_empty() {
		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'          => 'r_fallback',
						'target_type' => 'product',
						'object_ids'  => array(),
					)
				),
			)
		);

		$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION );
		$this->assertContains( 'r_fallback', $index['post_type']['product'] );
	}

	/**
	 * Tests that index term bucket is populated for taxonomy rule.
	 */
	public function test_index_term_bucket_populated_for_taxonomy_rule() {
		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'          => 'r_term',
						'target_type' => 'taxonomy',
						'taxonomy'    => 'category',
						'term_ids'    => array( 12 ),
						'object_ids'  => array(),
					)
				),
			)
		);

		$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION );
		$this->assertContains( 'r_term', $index['term']['category:12'] );
	}

	/**
	 * Tests that index is empty when no rules exist.
	 */
	public function test_index_is_empty_when_no_rules_exist() {
		wps_save_access_rules( array() );
		$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION );
		$this->assertEmpty( $index['object'] );
		$this->assertEmpty( $index['term'] );
		$this->assertEmpty( $index['post_type'] );
	}

	// -----------------------------------------------------------------------
	// wps_get_rules_for_object()
	// -----------------------------------------------------------------------

	/**
	 * Tests that get rules for object returns empty when no rules.
	 */
	public function test_get_rules_for_object_returns_empty_when_no_rules() {
		wps_save_access_rules( array() );
		$post = get_post( self::factory()->post->create() );
		$this->assertSame( array(), wps_get_rules_for_object( $post ) );
	}

	/**
	 * Tests that get rules for object matches by post type.
	 */
	public function test_get_rules_for_object_matches_by_post_type() {
		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'        => 'r_pt_match',
						'post_type' => 'post',
					)
				),
			)
		);

		$post  = get_post( self::factory()->post->create( array( 'post_type' => 'post' ) ) );
		$rules = wps_get_rules_for_object( $post );

		$this->assertCount( 1, $rules );
		$this->assertSame( 'r_pt_match', $rules[0]['id'] );
	}

	/**
	 * Tests that get rules for object does not match different post type.
	 */
	public function test_get_rules_for_object_does_not_match_different_post_type() {
		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'        => 'r_page_rule',
						'post_type' => 'page',
					)
				),
			)
		);

		$post  = get_post( self::factory()->post->create( array( 'post_type' => 'post' ) ) );
		$rules = wps_get_rules_for_object( $post );

		$this->assertSame( array(), $rules );
	}

	/**
	 * Tests that get rules for object matches by specific id.
	 */
	public function test_get_rules_for_object_matches_by_specific_id() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'          => 'r_id_match',
						'target_type' => 'post',
						'post_type'   => '',
						'object_ids'  => array( $post_id ),
					)
				),
			)
		);

		$post  = get_post( $post_id );
		$rules = wps_get_rules_for_object( $post );

		$this->assertCount( 1, $rules );
		$this->assertSame( 'r_id_match', $rules[0]['id'] );
	}

	/**
	 * Tests that get rules for object matches by term.
	 */
	public function test_get_rules_for_object_matches_by_term() {
		$term    = wp_insert_term( 'Members Only', 'category' );
		$term_id = $term['term_id'];

		$post_id = self::factory()->post->create(
			array(
				'post_type'     => 'post',
				'post_category' => array( $term_id ),
			)
		);

		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'          => 'r_term_match',
						'target_type' => 'taxonomy',
						'taxonomy'    => 'category',
						'term_ids'    => array( $term_id ),
						'object_ids'  => array(),
						'post_type'   => '',
					)
				),
			)
		);

		$post  = get_post( $post_id );
		$rules = wps_get_rules_for_object( $post );

		$this->assertCount( 1, $rules );
		$this->assertSame( 'r_term_match', $rules[0]['id'] );
	}

	/**
	 * Tests that get rules for object deduplicates matched rule ids.
	 */
	public function test_get_rules_for_object_deduplicates_matched_rule_ids() {
		// A rule that matches both by ID and by post type should appear only once.
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'          => 'r_dual',
						'target_type' => 'post',
						'post_type'   => 'post', // also index under post_type bucket (fallback not triggered here).
						'object_ids'  => array( $post_id ),
					)
				),
			)
		);

		// Also add a post_type rule with the same target so both buckets fire.
		wps_add_access_rule(
			$this->make_rule(
				array(
					'target_type' => 'post_type',
					'post_type'   => 'post',
					'plans'       => array( 'silver' ),
				)
			)
		);

		$post  = get_post( $post_id );
		$rules = wps_get_rules_for_object( $post );
		$ids   = array_column( $rules, 'id' );

		// No duplicate IDs in the result.
		$this->assertSame( $ids, array_unique( $ids ) );
	}

	/**
	 * Tests that get rules for object sorted ascending by priority.
	 */
	public function test_get_rules_for_object_sorted_ascending_by_priority() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'        => 'r_low',
						'priority'  => 20,
						'post_type' => 'post',
					)
				),
				$this->make_rule(
					array(
						'id'          => 'r_high',
						'priority'    => 5,
						'target_type' => 'post',
						'post_type'   => '',
						'object_ids'  => array( $post_id ),
					)
				),
			)
		);

		$post  = get_post( $post_id );
		$rules = wps_get_rules_for_object( $post );

		$this->assertGreaterThanOrEqual( 2, count( $rules ) );
		$this->assertSame( 'r_high', $rules[0]['id'] );
	}

	// -----------------------------------------------------------------------
	// wps_object_is_restricted()
	// -----------------------------------------------------------------------

	/**
	 * Tests that object is restricted returns null when no rules.
	 */
	public function test_object_is_restricted_returns_null_when_no_rules() {
		wps_save_access_rules( array() );
		$post = get_post( self::factory()->post->create() );
		$this->assertNull( wps_object_is_restricted( $post, 0 ) );
	}

	/**
	 * Tests that object is restricted returns rule for logged out user.
	 */
	public function test_object_is_restricted_returns_rule_for_logged_out_user() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'        => 'r_guest',
						'post_type' => 'post',
					)
				),
			)
		);

		$post   = get_post( $post_id );
		$result = wps_object_is_restricted( $post, 0 );

		$this->assertIsArray( $result );
		$this->assertSame( 'r_guest', $result['id'] );
	}

	/**
	 * Tests that object is restricted returns null for administrator.
	 */
	public function test_object_is_restricted_returns_null_for_administrator() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$post_id  = self::factory()->post->create( array( 'post_type' => 'post' ) );

		wps_save_access_rules(
			array(
				$this->make_rule(
					array(
						'id'        => 'r_bypass',
						'post_type' => 'post',
					)
				),
			)
		);

		$post = get_post( $post_id );
		$this->assertNull( wps_object_is_restricted( $post, $admin_id ) );
	}

	/**
	 * Tests that object is restricted caches result per post user pair.
	 */
	public function test_object_is_restricted_caches_result_per_post_user_pair() {
		wps_save_access_rules( array() );
		$post = get_post( self::factory()->post->create() );

		// Prime the cache with a sentinel 'granted'.
		wps_object_is_restricted( $post, 0 );

		// Now inject a rule that would restrict — the cached result should win.
		update_option( WPS_ACCESS_RULES_OPTION, array( $this->make_rule() ) );
		update_option(
			WPS_ACCESS_RULES_INDEX_OPTION,
			array(
				'object'    => array(),
				'term'      => array(),
				'post_type' => array( 'post' => array( 'r_test' ) ),
			)
		);

		// Still null because the per-request cache says 'granted'.
		$this->assertNull( wps_object_is_restricted( $post, 0 ) );
	}
}
