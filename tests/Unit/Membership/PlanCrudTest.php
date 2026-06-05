<?php
/**
 * Unit tests for includes/membership/functions-membership-plan.php
 *
 * Covers Day 02 deliverables:
 *   - Plan CRUD (create, read, update, delete)
 *   - Plan ↔ Product link helpers
 *   - Purchase-CTA helpers (purchasable-product filter)
 *   - wps_sanitize_access_length()
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class PlanCrudTest extends WP_UnitTestCase {

	// -----------------------------------------------------------------------
	// wps_create_plan()
	// -----------------------------------------------------------------------

	public function test_create_plan_returns_wp_error_when_name_is_empty() {
		$result = wps_create_plan( array( 'name' => '' ) );
		$this->assertWPError( $result );
		$this->assertSame( 'wps_plan_no_name', $result->get_error_code() );
	}

	public function test_create_plan_returns_integer_post_id_on_success() {
		$plan_id = wps_create_plan( array( 'name' => 'Gold Plan' ) );
		$this->assertIsInt( $plan_id );
		$this->assertGreaterThan( 0, $plan_id );
	}

	public function test_create_plan_stores_slug_meta() {
		$plan_id = wps_create_plan( array( 'name' => 'Silver Plan' ) );
		$this->assertSame( 'silver-plan', get_post_meta( $plan_id, '_wps_plan_slug', true ) );
	}

	public function test_create_plan_accepts_explicit_slug() {
		$plan_id = wps_create_plan( array( 'name' => 'Any Name', 'slug' => 'my-custom-slug' ) );
		$this->assertSame( 'my-custom-slug', get_post_meta( $plan_id, '_wps_plan_slug', true ) );
	}

	public function test_create_plan_auto_generates_unique_slug_on_collision() {
		$id1 = wps_create_plan( array( 'name' => 'Bronze' ) );
		$id2 = wps_create_plan( array( 'name' => 'Bronze' ) );
		$slug1 = get_post_meta( $id1, '_wps_plan_slug', true );
		$slug2 = get_post_meta( $id2, '_wps_plan_slug', true );
		$this->assertNotSame( $slug1, $slug2 );
	}

	public function test_create_plan_defaults_to_active_status() {
		$plan_id = wps_create_plan( array( 'name' => 'Status Test' ) );
		$this->assertSame( 'active', get_post_meta( $plan_id, '_wps_plan_status', true ) );
	}

	public function test_create_plan_stores_inactive_status() {
		$plan_id = wps_create_plan( array( 'name' => 'Inactive Plan', 'status' => 'inactive' ) );
		$this->assertSame( 'inactive', get_post_meta( $plan_id, '_wps_plan_status', true ) );
	}

	public function test_create_plan_stores_fixed_access_length() {
		$plan_id = wps_create_plan(
			array(
				'name'          => 'Fixed Plan',
				'access_length' => array( 'type' => 'fixed', 'value' => 30, 'unit' => 'day' ),
			)
		);
		$length = get_post_meta( $plan_id, '_wps_plan_access_length', true );
		$this->assertSame( 'fixed', $length['type'] );
		$this->assertSame( 30, $length['value'] );
		$this->assertSame( 'day', $length['unit'] );
	}

	public function test_create_plan_stores_product_ids() {
		$plan_id = wps_create_plan( array( 'name' => 'Product Plan', 'products' => array( 5, 6 ) ) );
		$stored  = get_post_meta( $plan_id, '_wps_plan_products', true );
		$this->assertContains( 5, $stored );
		$this->assertContains( 6, $stored );
	}

	public function test_create_plan_fires_wps_plan_created_hook() {
		$fired = false;
		add_action(
			'wps_plan_created',
			function () use ( &$fired ) {
				$fired = true;
			}
		);
		wps_create_plan( array( 'name' => 'Hook Test' ) );
		$this->assertTrue( $fired );
	}

	public function test_create_plan_rebuilds_product_plan_map() {
		$plan_id = wps_create_plan( array( 'name' => 'Map Test', 'products' => array( 42 ) ) );
		$map     = get_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertIsArray( $map );
		$this->assertArrayHasKey( 42, $map );
	}

	// -----------------------------------------------------------------------
	// wps_get_plan()
	// -----------------------------------------------------------------------

	public function test_get_plan_returns_null_for_missing_id() {
		$this->assertNull( wps_get_plan( 99999 ) );
	}

	public function test_get_plan_returns_null_for_wrong_post_type() {
		$page_id = wp_insert_post( array( 'post_title' => 'A Page', 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->assertNull( wps_get_plan( $page_id ) );
	}

	public function test_get_plan_returns_plan_array() {
		$plan_id = wps_create_plan( array( 'name' => 'Readable Plan' ) );
		$plan    = wps_get_plan( $plan_id );

		$this->assertIsArray( $plan );
		$this->assertSame( $plan_id, $plan['id'] );
		$this->assertSame( 'Readable Plan', $plan['name'] );
		$this->assertArrayHasKey( 'slug', $plan );
		$this->assertArrayHasKey( 'status', $plan );
		$this->assertArrayHasKey( 'products', $plan );
		$this->assertArrayHasKey( 'access_length', $plan );
	}

	// -----------------------------------------------------------------------
	// wps_get_plan_by_slug()
	// -----------------------------------------------------------------------

	public function test_get_plan_by_slug_returns_null_for_empty_slug() {
		$this->assertNull( wps_get_plan_by_slug( '' ) );
	}

	public function test_get_plan_by_slug_returns_null_for_unknown_slug() {
		$this->assertNull( wps_get_plan_by_slug( 'does-not-exist' ) );
	}

	public function test_get_plan_by_slug_returns_correct_plan() {
		$plan_id = wps_create_plan( array( 'name' => 'Enterprise', 'slug' => 'enterprise' ) );
		$plan    = wps_get_plan_by_slug( 'enterprise' );

		$this->assertIsArray( $plan );
		$this->assertSame( $plan_id, $plan['id'] );
		$this->assertSame( 'enterprise', $plan['slug'] );
	}

	// -----------------------------------------------------------------------
	// wps_get_all_plans()
	// -----------------------------------------------------------------------

	public function test_get_all_plans_returns_only_plans_of_given_status() {
		$active_id   = wps_create_plan( array( 'name' => 'Active One', 'status' => 'active' ) );
		$inactive_id = wps_create_plan( array( 'name' => 'Inactive One', 'status' => 'inactive' ) );

		$active_plans   = wps_get_all_plans( 'active' );
		$inactive_plans = wps_get_all_plans( 'inactive' );

		$this->assertCount( 1, $active_plans );
		$this->assertCount( 1, $inactive_plans );
		$this->assertSame( $active_id, $active_plans[0]['id'] );
		$this->assertSame( $inactive_id, $inactive_plans[0]['id'] );
	}

	public function test_get_all_plans_returns_all_when_status_is_all() {
		wps_create_plan( array( 'name' => 'All A', 'status' => 'active' ) );
		wps_create_plan( array( 'name' => 'All B', 'status' => 'inactive' ) );

		$all = wps_get_all_plans( 'all' );
		$this->assertGreaterThanOrEqual( 2, count( $all ) );
	}

	// -----------------------------------------------------------------------
	// wps_update_plan()
	// -----------------------------------------------------------------------

	public function test_update_plan_returns_wp_error_for_missing_plan() {
		$result = wps_update_plan( 99999, array( 'name' => 'X' ) );
		$this->assertWPError( $result );
		$this->assertSame( 'wps_plan_not_found', $result->get_error_code() );
	}

	public function test_update_plan_changes_name() {
		$plan_id = wps_create_plan( array( 'name' => 'Old Name' ) );
		wps_update_plan( $plan_id, array( 'name' => 'New Name' ) );
		$plan = wps_get_plan( $plan_id );
		$this->assertSame( 'New Name', $plan['name'] );
	}

	public function test_update_plan_changes_status() {
		$plan_id = wps_create_plan( array( 'name' => 'Toggle Plan', 'status' => 'active' ) );
		wps_update_plan( $plan_id, array( 'status' => 'inactive' ) );
		$plan = wps_get_plan( $plan_id );
		$this->assertSame( 'inactive', $plan['status'] );
	}

	public function test_update_plan_updates_products_and_rebuilds_map() {
		$plan_id = wps_create_plan( array( 'name' => 'Map Update Plan', 'products' => array( 100 ) ) );
		wps_update_plan( $plan_id, array( 'products' => array( 200, 300 ) ) );

		$map = get_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertArrayHasKey( 200, $map );
		$this->assertArrayHasKey( 300, $map );
		$this->assertArrayNotHasKey( 100, $map );
	}

	public function test_update_plan_fires_wps_plan_updated_hook() {
		$plan_id = wps_create_plan( array( 'name' => 'Hook Plan' ) );
		$fired   = false;
		add_action( 'wps_plan_updated', function () use ( &$fired ) { $fired = true; } );
		wps_update_plan( $plan_id, array( 'name' => 'Updated Hook Plan' ) );
		$this->assertTrue( $fired );
	}

	public function test_update_plan_omitted_keys_are_unchanged() {
		$plan_id = wps_create_plan( array( 'name' => 'Partial Update', 'status' => 'inactive' ) );
		wps_update_plan( $plan_id, array( 'name' => 'Partial Update 2' ) );
		$plan = wps_get_plan( $plan_id );
		$this->assertSame( 'inactive', $plan['status'] );
	}

	// -----------------------------------------------------------------------
	// wps_delete_plan()
	// -----------------------------------------------------------------------

	public function test_delete_plan_returns_wp_error_for_missing_plan() {
		$result = wps_delete_plan( 99999 );
		$this->assertWPError( $result );
	}

	public function test_delete_plan_removes_post_from_database() {
		$plan_id = wps_create_plan( array( 'name' => 'Delete Me' ) );
		wps_delete_plan( $plan_id );
		$this->assertNull( get_post( $plan_id ) );
	}

	public function test_delete_plan_fires_wps_plan_deleted_hook() {
		$plan_id = wps_create_plan( array( 'name' => 'Delete Hook Plan' ) );
		$fired   = false;
		add_action( 'wps_plan_deleted', function () use ( &$fired ) { $fired = true; } );
		wps_delete_plan( $plan_id );
		$this->assertTrue( $fired );
	}

	public function test_delete_plan_clears_products_from_map() {
		$plan_id = wps_create_plan( array( 'name' => 'Map Clear Plan', 'products' => array( 55 ) ) );
		wps_delete_plan( $plan_id );
		$map = get_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertArrayNotHasKey( 55, (array) $map );
	}

	// -----------------------------------------------------------------------
	// wps_link_product_to_plan() / wps_unlink_product_from_plan()
	// -----------------------------------------------------------------------

	public function test_link_product_to_plan_returns_true_on_new_link() {
		$plan_id = wps_create_plan( array( 'name' => 'Link Test' ) );
		$plan    = wps_get_plan( $plan_id );
		$result  = wps_link_product_to_plan( 77, $plan['slug'] );
		$this->assertTrue( $result );
	}

	public function test_link_product_to_plan_is_idempotent() {
		$plan_id = wps_create_plan( array( 'name' => 'Idempotent Link', 'products' => array( 88 ) ) );
		$plan    = wps_get_plan( $plan_id );
		$result  = wps_link_product_to_plan( 88, $plan['slug'] );
		$this->assertFalse( $result );
	}

	public function test_link_product_to_plan_returns_false_for_unknown_slug() {
		$this->assertFalse( wps_link_product_to_plan( 99, 'non-existent-plan' ) );
	}

	public function test_unlink_product_from_plan_removes_product() {
		$plan_id = wps_create_plan( array( 'name' => 'Unlink Test', 'products' => array( 66 ) ) );
		$plan    = wps_get_plan( $plan_id );
		wps_unlink_product_from_plan( 66, $plan['slug'] );
		$this->assertEmpty( wps_get_plan_products( $plan['slug'] ) );
	}

	public function test_unlink_product_from_plan_returns_false_when_not_linked() {
		$plan_id = wps_create_plan( array( 'name' => 'No Link Plan' ) );
		$plan    = wps_get_plan( $plan_id );
		$this->assertFalse( wps_unlink_product_from_plan( 999, $plan['slug'] ) );
	}

	// -----------------------------------------------------------------------
	// wps_get_plan_products() / wps_get_plan_by_product()
	// -----------------------------------------------------------------------

	public function test_get_plan_products_returns_empty_for_unknown_slug() {
		$this->assertEmpty( wps_get_plan_products( 'no-such-plan' ) );
	}

	public function test_get_plan_products_returns_linked_ids() {
		$plan_id = wps_create_plan( array( 'name' => 'Product List Plan', 'products' => array( 1, 2, 3 ) ) );
		$plan    = wps_get_plan( $plan_id );
		$ids     = wps_get_plan_products( $plan['slug'] );
		$this->assertContains( 1, $ids );
		$this->assertContains( 2, $ids );
		$this->assertContains( 3, $ids );
	}

	public function test_get_plan_by_product_returns_null_for_unmapped_product() {
		delete_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertNull( wps_get_plan_by_product( 99999 ) );
	}

	public function test_get_plan_by_product_returns_slug_for_mapped_product() {
		wps_create_plan( array( 'name' => 'By Product Plan', 'slug' => 'by-product', 'products' => array( 50 ) ) );
		$slug = wps_get_plan_by_product( 50 );
		$this->assertSame( 'by-product', $slug );
	}

	// -----------------------------------------------------------------------
	// wps_sanitize_access_length()
	// -----------------------------------------------------------------------

	public function test_sanitize_access_length_returns_defaults_for_non_array() {
		$result = wps_sanitize_access_length( 'invalid' );
		$this->assertSame( 'lifetime', $result['type'] );
		$this->assertSame( 0, $result['value'] );
		$this->assertSame( 'day', $result['unit'] );
	}

	public function test_sanitize_access_length_preserves_fixed_type() {
		$result = wps_sanitize_access_length( array( 'type' => 'fixed', 'value' => 12, 'unit' => 'month' ) );
		$this->assertSame( 'fixed', $result['type'] );
		$this->assertSame( 12, $result['value'] );
		$this->assertSame( 'month', $result['unit'] );
	}

	public function test_sanitize_access_length_rejects_invalid_unit() {
		$result = wps_sanitize_access_length( array( 'type' => 'fixed', 'value' => 5, 'unit' => 'century' ) );
		$this->assertSame( 'day', $result['unit'] );
	}

	public function test_sanitize_access_length_rejects_invalid_type() {
		$result = wps_sanitize_access_length( array( 'type' => 'unknown' ) );
		$this->assertSame( 'lifetime', $result['type'] );
	}

	public function test_sanitize_access_length_casts_value_to_positive_int() {
		$result = wps_sanitize_access_length( array( 'type' => 'fixed', 'value' => '-7', 'unit' => 'year' ) );
		// absint( '-7' ) = 7.
		$this->assertSame( 7, $result['value'] );
	}

	// -----------------------------------------------------------------------
	// Teardown
	// -----------------------------------------------------------------------

	public function tearDown(): void {
		delete_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		parent::tearDown();
	}
}
