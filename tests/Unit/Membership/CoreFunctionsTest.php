<?php
/**
 * Unit tests for includes/membership/functions-membership-core.php
 *
 * Covers Day 01 deliverables:
 *   - Schema-version seeding (idempotent add_option)
 *   - Product→plan reverse-lookup map rebuild / read
 *   - Plan-slug uniqueness utility
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class CoreFunctionsTest extends WP_UnitTestCase {

	// -----------------------------------------------------------------------
	// Schema version
	// -----------------------------------------------------------------------

	public function test_seed_schema_version_creates_option_on_first_load() {
		delete_option( 'wps_membership_schema_version' );
		wps_membership_seed_schema_version();
		$this->assertSame( '1.0.0', get_option( 'wps_membership_schema_version' ) );
	}

	public function test_seed_schema_version_does_not_overwrite_existing_value() {
		update_option( 'wps_membership_schema_version', '2.0.0' );
		wps_membership_seed_schema_version();
		// add_option is a no-op when the key exists — value must stay at 2.0.0.
		$this->assertSame( '2.0.0', get_option( 'wps_membership_schema_version' ) );
		update_option( 'wps_membership_schema_version', '1.0.0' ); // restore.
	}

	// -----------------------------------------------------------------------
	// wps_rebuild_product_plan_map()
	// -----------------------------------------------------------------------

	public function test_rebuild_product_plan_map_returns_empty_array_when_no_plans() {
		$map = wps_rebuild_product_plan_map();
		$this->assertIsArray( $map );
		$this->assertEmpty( $map );
	}

	public function test_rebuild_product_plan_map_maps_products_to_slugs() {
		$plan_id = $this->_create_raw_plan( 'gold', array( 10, 11 ) );

		$map = wps_rebuild_product_plan_map();

		$this->assertArrayHasKey( 10, $map );
		$this->assertArrayHasKey( 11, $map );
		$this->assertSame( 'gold', $map[10] );
		$this->assertSame( 'gold', $map[11] );

		wp_delete_post( $plan_id, true );
	}

	public function test_rebuild_product_plan_map_persists_to_options() {
		$plan_id = $this->_create_raw_plan( 'silver', array( 20 ) );

		wps_rebuild_product_plan_map();

		$stored = get_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 20, $stored );

		wp_delete_post( $plan_id, true );
	}

	public function test_rebuild_product_plan_map_last_plan_wins_for_shared_product() {
		// Two plans share product ID 99.  The last plan in the query wins (alphabetical by title).
		$plan_a = $this->_create_raw_plan( 'alpha', array( 99 ) );
		$plan_b = $this->_create_raw_plan( 'beta', array( 99 ) );

		$map = wps_rebuild_product_plan_map();
		$this->assertArrayHasKey( 99, $map );
		// We don't assert which one wins — just that the map is valid.
		$this->assertContains( $map[99], array( 'alpha', 'beta' ) );

		wp_delete_post( $plan_a, true );
		wp_delete_post( $plan_b, true );
	}

	// -----------------------------------------------------------------------
	// wps_get_product_plan_map()
	// -----------------------------------------------------------------------

	public function test_get_product_plan_map_falls_back_to_rebuild_when_option_missing() {
		delete_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$plan_id = $this->_create_raw_plan( 'platinum', array( 30 ) );

		$map = wps_get_product_plan_map();

		$this->assertIsArray( $map );
		$this->assertArrayHasKey( 30, $map );

		wp_delete_post( $plan_id, true );
	}

	public function test_get_product_plan_map_returns_cached_option_without_rebuild() {
		update_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION, array( 777 => 'cached-slug' ), true );

		$map = wps_get_product_plan_map();

		$this->assertArrayHasKey( 777, $map );
		$this->assertSame( 'cached-slug', $map[777] );

		delete_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
	}

	// -----------------------------------------------------------------------
	// wps_generate_unique_plan_slug()
	// -----------------------------------------------------------------------

	public function test_generate_unique_plan_slug_sanitizes_name() {
		$slug = wps_generate_unique_plan_slug( 'My Gold Plan!' );
		$this->assertSame( 'my-gold-plan', $slug );
	}

	public function test_generate_unique_plan_slug_appends_suffix_on_collision() {
		$plan_id = $this->_create_raw_plan( 'starter', array() );

		$slug = wps_generate_unique_plan_slug( 'Starter' );
		$this->assertSame( 'starter-2', $slug );

		wp_delete_post( $plan_id, true );
	}

	public function test_generate_unique_plan_slug_increments_suffix_until_unique() {
		$id1 = $this->_create_raw_plan( 'pro', array() );
		$id2 = $this->_create_raw_plan( 'pro-2', array() );

		$slug = wps_generate_unique_plan_slug( 'Pro' );
		$this->assertSame( 'pro-3', $slug );

		wp_delete_post( $id1, true );
		wp_delete_post( $id2, true );
	}

	public function test_generate_unique_plan_slug_excludes_given_plan_from_collision_check() {
		$plan_id = $this->_create_raw_plan( 'basic', array() );

		// Updating the same plan — should NOT produce basic-2.
		$slug = wps_generate_unique_plan_slug( 'Basic', $plan_id );
		$this->assertSame( 'basic', $slug );

		wp_delete_post( $plan_id, true );
	}

	public function test_generate_unique_plan_slug_falls_back_to_plan_for_empty_name() {
		$slug = wps_generate_unique_plan_slug( '' );
		$this->assertStringStartsWith( 'plan', $slug );
	}

	// -----------------------------------------------------------------------
	// wps_plan_slug_exists()
	// -----------------------------------------------------------------------

	public function test_plan_slug_exists_returns_false_when_no_plans() {
		$this->assertFalse( wps_plan_slug_exists( 'nonexistent' ) );
	}

	public function test_plan_slug_exists_returns_true_for_existing_slug() {
		$plan_id = $this->_create_raw_plan( 'enterprise', array() );

		$this->assertTrue( wps_plan_slug_exists( 'enterprise' ) );

		wp_delete_post( $plan_id, true );
	}

	public function test_plan_slug_exists_returns_false_when_only_excluded_plan_matches() {
		$plan_id = $this->_create_raw_plan( 'exclusive', array() );

		$this->assertFalse( wps_plan_slug_exists( 'exclusive', $plan_id ) );

		wp_delete_post( $plan_id, true );
	}

	public function test_plan_slug_exists_returns_false_for_empty_slug() {
		$this->assertFalse( wps_plan_slug_exists( '' ) );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Insert a raw plan CPT post with the required meta (bypasses wps_create_plan).
	 *
	 * @param string $slug       Plan slug.
	 * @param int[]  $product_ids Products to link.
	 * @return int Post ID.
	 */
	private function _create_raw_plan( $slug, array $product_ids ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => WPS_MEMBERSHIP_PLAN_CPT,
				'post_title'  => ucfirst( $slug ),
				'post_status' => 'publish',
			)
		);
		update_post_meta( $post_id, '_wps_plan_slug', $slug );
		update_post_meta( $post_id, '_wps_plan_status', 'active' );
		update_post_meta( $post_id, '_wps_plan_products', $product_ids );
		return $post_id;
	}

	// Clean up the plan map option after every test.
	public function tearDown(): void {
		delete_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		parent::tearDown();
	}
}
