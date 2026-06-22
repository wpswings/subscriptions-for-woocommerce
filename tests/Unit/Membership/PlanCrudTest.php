<?php
/**
 * Test suite for Plan CRUD operations.
 *
 * @package Subscriptions_For_Woocommerce
 */

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

	/**
	 * Test that create plan returns WP_Error when name is empty.
	 */
	public function test_create_plan_returns_wp_error_when_name_is_empty() {
		$result = wps_create_plan( array( 'name' => '' ) );
		$this->assertWPError( $result );
		$this->assertSame( 'wps_plan_no_name', $result->get_error_code() );
	}

	/**
	 * Test that create plan returns integer post ID on success.
	 */
	public function test_create_plan_returns_integer_post_id_on_success() {
		$plan_id = wps_create_plan( array( 'name' => 'Gold Plan' ) );
		$this->assertIsInt( $plan_id );
		$this->assertGreaterThan( 0, $plan_id );
	}

	/**
	 * Test that create plan stores slug meta.
	 */
	public function test_create_plan_stores_slug_meta() {
		$plan_id = wps_create_plan( array( 'name' => 'Silver Plan' ) );
		$this->assertSame( 'silver-plan', get_post_meta( $plan_id, '_wps_plan_slug', true ) );
	}

	/**
	 * Test that create plan accepts explicit slug.
	 */
	public function test_create_plan_accepts_explicit_slug() {
		$plan_id = wps_create_plan( array( 'name' => 'Any Name', 'slug' => 'my-custom-slug' ) );
		$this->assertSame( 'my-custom-slug', get_post_meta( $plan_id, '_wps_plan_slug', true ) );
	}

	/**
	 * Test that create plan auto generates unique slug on collision.
	 */
	public function test_create_plan_auto_generates_unique_slug_on_collision() {
		$id1 = wps_create_plan( array( 'name' => 'Bronze' ) );
		$id2 = wps_create_plan( array( 'name' => 'Bronze' ) );
		$slug1 = get_post_meta( $id1, '_wps_plan_slug', true );
		$slug2 = get_post_meta( $id2, '_wps_plan_slug', true );
		$this->assertNotSame( $slug1, $slug2 );
	}

	/**
	 * Test that create plan defaults to active status.
	 */
	public function test_create_plan_defaults_to_active_status() {
		$plan_id = wps_create_plan( array( 'name' => 'Status Test' ) );
		$this->assertSame( 'active', get_post_meta( $plan_id, '_wps_plan_status', true ) );
	}

	/**
	 * Test that create plan stores inactive status.
	 */
	public function test_create_plan_stores_inactive_status() {
		$plan_id = wps_create_plan( array( 'name' => 'Inactive Plan', 'status' => 'inactive' ) );
		$this->assertSame( 'inactive', get_post_meta( $plan_id, '_wps_plan_status', true ) );
	}

	/**
	 * Test that create plan stores fixed access length.
	 */
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

	/**
	 * Test that create plan stores product IDs.
	 */
	public function test_create_plan_stores_product_ids() {
		$plan_id = wps_create_plan( array( 'name' => 'Product Plan', 'products' => array( 5, 6 ) ) );
		$stored  = get_post_meta( $plan_id, '_wps_plan_products', true );
		$this->assertContains( 5, $stored );
		$this->assertContains( 6, $stored );
	}

	/**
	 * Test that create plan fires wps_plan_created hook.
	 */
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

	/**
	 * Test that create plan rebuilds product plan map.
	 */
	public function test_create_plan_rebuilds_product_plan_map() {
		$plan_id = wps_create_plan( array( 'name' => 'Map Test', 'products' => array( 42 ) ) );
		$map     = get_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertIsArray( $map );
		$this->assertArrayHasKey( 42, $map );
	}

	/**
	 * Grant method defaults to purchase when not supplied.
	 *
	 * @covers wps_create_plan
	 */
	public function test_create_plan_defaults_grant_method_to_purchase() {
		$plan_id = wps_create_plan( array( 'name' => 'Default Grant Plan' ) );
		$this->assertSame( 'purchase', get_post_meta( $plan_id, '_wps_plan_grant_method', true ) );
	}

	/**
	 * Auto-enroll grant method is persisted to post meta.
	 *
	 * @covers wps_create_plan
	 */
	public function test_create_plan_stores_auto_enroll_grant_method() {
		$plan_id = wps_create_plan(
			array(
				'name'         => 'Auto Enroll Plan',
				'grant_method' => 'auto_enroll',
			)
		);
		$this->assertSame( 'auto_enroll', get_post_meta( $plan_id, '_wps_plan_grant_method', true ) );
	}

	/**
	 * Subscription grant method is persisted to post meta.
	 *
	 * @covers wps_create_plan
	 */
	public function test_create_plan_stores_subscription_grant_method() {
		$plan_id = wps_create_plan(
			array(
				'name'         => 'Sub Grant Plan',
				'grant_method' => 'subscription',
			)
		);
		$this->assertSame( 'subscription', get_post_meta( $plan_id, '_wps_plan_grant_method', true ) );
	}

	/**
	 * An unrecognised grant method falls back to purchase.
	 *
	 * @covers wps_create_plan
	 */
	public function test_create_plan_rejects_invalid_grant_method_and_defaults_to_purchase() {
		$plan_id = wps_create_plan(
			array(
				'name'         => 'Bad Method Plan',
				'grant_method' => 'magic',
			)
		);
		$this->assertSame( 'purchase', get_post_meta( $plan_id, '_wps_plan_grant_method', true ) );
	}

	// -----------------------------------------------------------------------
	// wps_get_plan()
	// -----------------------------------------------------------------------

	/**
	 * Test that get plan returns null for missing ID.
	 */
	public function test_get_plan_returns_null_for_missing_id() {
		$this->assertNull( wps_get_plan( 99999 ) );
	}

	/**
	 * Test that get plan returns null for wrong post type.
	 */
	public function test_get_plan_returns_null_for_wrong_post_type() {
		$page_id = wp_insert_post( array( 'post_title' => 'A Page', 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->assertNull( wps_get_plan( $page_id ) );
	}

	/**
	 * Test that get plan returns plan array.
	 */
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

	/**
	 * Test that get plan by slug returns null for empty slug.
	 */
	public function test_get_plan_by_slug_returns_null_for_empty_slug() {
		$this->assertNull( wps_get_plan_by_slug( '' ) );
	}

	/**
	 * Test that get plan by slug returns null for unknown slug.
	 */
	public function test_get_plan_by_slug_returns_null_for_unknown_slug() {
		$this->assertNull( wps_get_plan_by_slug( 'does-not-exist' ) );
	}

	/**
	 * Test that get plan by slug returns correct plan.
	 */
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

	/**
	 * Test that get all plans returns only plans of given status.
	 */
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

	/**
	 * Plans saved without _wps_plan_status meta are treated as active.
	 *
	 * @covers wps_get_all_plans
	 */
	public function test_get_all_plans_active_includes_plans_with_no_status_meta() {
		$plan_id = wps_create_plan( array( 'name' => 'No Status Plan' ) );
		delete_post_meta( $plan_id, '_wps_plan_status' );

		$active_plans = wps_get_all_plans( 'active' );
		$ids          = array_column( $active_plans, 'id' );
		$this->assertContains( $plan_id, $ids );
	}

	/**
	 * Plans without _wps_plan_status meta are not returned for inactive query.
	 *
	 * @covers wps_get_all_plans
	 */
	public function test_get_all_plans_inactive_excludes_plans_with_no_status_meta() {
		$plan_id = wps_create_plan( array( 'name' => 'No Status Plan 2' ) );
		delete_post_meta( $plan_id, '_wps_plan_status' );

		$inactive_plans = wps_get_all_plans( 'inactive' );
		$ids            = array_column( $inactive_plans, 'id' );
		$this->assertNotContains( $plan_id, $ids );
	}

	/**
	 * Test that get all plans returns all when status is all.
	 */
	public function test_get_all_plans_returns_all_when_status_is_all() {
		wps_create_plan( array( 'name' => 'All A', 'status' => 'active' ) );
		wps_create_plan( array( 'name' => 'All B', 'status' => 'inactive' ) );

		$all = wps_get_all_plans( 'all' );
		$this->assertGreaterThanOrEqual( 2, count( $all ) );
	}

	// -----------------------------------------------------------------------
	// wps_update_plan()
	// -----------------------------------------------------------------------

	/**
	 * Test that update plan returns WP_Error for missing plan.
	 */
	public function test_update_plan_returns_wp_error_for_missing_plan() {
		$result = wps_update_plan( 99999, array( 'name' => 'X' ) );
		$this->assertWPError( $result );
		$this->assertSame( 'wps_plan_not_found', $result->get_error_code() );
	}

	/**
	 * Test that update plan changes name.
	 */
	public function test_update_plan_changes_name() {
		$plan_id = wps_create_plan( array( 'name' => 'Old Name' ) );
		wps_update_plan( $plan_id, array( 'name' => 'New Name' ) );
		$plan = wps_get_plan( $plan_id );
		$this->assertSame( 'New Name', $plan['name'] );
	}

	/**
	 * Test that update plan changes status.
	 */
	public function test_update_plan_changes_status() {
		$plan_id = wps_create_plan( array( 'name' => 'Toggle Plan', 'status' => 'active' ) );
		wps_update_plan( $plan_id, array( 'status' => 'inactive' ) );
		$plan = wps_get_plan( $plan_id );
		$this->assertSame( 'inactive', $plan['status'] );
	}

	/**
	 * Test that update plan updates products and rebuilds map.
	 */
	public function test_update_plan_updates_products_and_rebuilds_map() {
		$plan_id = wps_create_plan( array( 'name' => 'Map Update Plan', 'products' => array( 100 ) ) );
		wps_update_plan( $plan_id, array( 'products' => array( 200, 300 ) ) );

		$map = get_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertArrayHasKey( 200, $map );
		$this->assertArrayHasKey( 300, $map );
		$this->assertArrayNotHasKey( 100, $map );
	}

	/**
	 * Test that update plan fires wps_plan_updated hook.
	 */
	public function test_update_plan_fires_wps_plan_updated_hook() {
		$plan_id = wps_create_plan( array( 'name' => 'Hook Plan' ) );
		$fired   = false;
		add_action( 'wps_plan_updated', function () use ( &$fired ) { $fired = true; } );
		wps_update_plan( $plan_id, array( 'name' => 'Updated Hook Plan' ) );
		$this->assertTrue( $fired );
	}

	/**
	 * Test that update plan omitted keys are unchanged.
	 */
	public function test_update_plan_omitted_keys_are_unchanged() {
		$plan_id = wps_create_plan( array( 'name' => 'Partial Update', 'status' => 'inactive' ) );
		wps_update_plan( $plan_id, array( 'name' => 'Partial Update 2' ) );
		$plan = wps_get_plan( $plan_id );
		$this->assertSame( 'inactive', $plan['status'] );
	}

	// -----------------------------------------------------------------------
	// wps_delete_plan()
	// -----------------------------------------------------------------------

	/**
	 * Test that delete plan returns WP_Error for missing plan.
	 */
	public function test_delete_plan_returns_wp_error_for_missing_plan() {
		$result = wps_delete_plan( 99999 );
		$this->assertWPError( $result );
	}

	/**
	 * Test that delete plan removes post from database.
	 */
	public function test_delete_plan_removes_post_from_database() {
		$plan_id = wps_create_plan( array( 'name' => 'Delete Me' ) );
		wps_delete_plan( $plan_id );
		$this->assertNull( get_post( $plan_id ) );
	}

	/**
	 * Test that delete plan fires wps_plan_deleted hook.
	 */
	public function test_delete_plan_fires_wps_plan_deleted_hook() {
		$plan_id = wps_create_plan( array( 'name' => 'Delete Hook Plan' ) );
		$fired   = false;
		add_action( 'wps_plan_deleted', function () use ( &$fired ) { $fired = true; } );
		wps_delete_plan( $plan_id );
		$this->assertTrue( $fired );
	}

	/**
	 * Test that delete plan clears products from map.
	 */
	public function test_delete_plan_clears_products_from_map() {
		$plan_id = wps_create_plan( array( 'name' => 'Map Clear Plan', 'products' => array( 55 ) ) );
		wps_delete_plan( $plan_id );
		$map = get_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertArrayNotHasKey( 55, (array) $map );
	}

	// -----------------------------------------------------------------------
	// wps_link_product_to_plan() / wps_unlink_product_from_plan()
	// -----------------------------------------------------------------------

	/**
	 * Test that link product to plan returns true on new link.
	 */
	public function test_link_product_to_plan_returns_true_on_new_link() {
		$plan_id = wps_create_plan( array( 'name' => 'Link Test' ) );
		$plan    = wps_get_plan( $plan_id );
		$result  = wps_link_product_to_plan( 77, $plan['slug'] );
		$this->assertTrue( $result );
	}

	/**
	 * Test that link product to plan is idempotent.
	 */
	public function test_link_product_to_plan_is_idempotent() {
		$plan_id = wps_create_plan( array( 'name' => 'Idempotent Link', 'products' => array( 88 ) ) );
		$plan    = wps_get_plan( $plan_id );
		$result  = wps_link_product_to_plan( 88, $plan['slug'] );
		$this->assertFalse( $result );
	}

	/**
	 * Test that link product to plan returns false for unknown slug.
	 */
	public function test_link_product_to_plan_returns_false_for_unknown_slug() {
		$this->assertFalse( wps_link_product_to_plan( 99, 'non-existent-plan' ) );
	}

	/**
	 * Test that unlink product from plan removes product.
	 */
	public function test_unlink_product_from_plan_removes_product() {
		$plan_id = wps_create_plan( array( 'name' => 'Unlink Test', 'products' => array( 66 ) ) );
		$plan    = wps_get_plan( $plan_id );
		wps_unlink_product_from_plan( 66, $plan['slug'] );
		$this->assertEmpty( wps_get_plan_products( $plan['slug'] ) );
	}

	/**
	 * Test that unlink product from plan returns false when not linked.
	 */
	public function test_unlink_product_from_plan_returns_false_when_not_linked() {
		$plan_id = wps_create_plan( array( 'name' => 'No Link Plan' ) );
		$plan    = wps_get_plan( $plan_id );
		$this->assertFalse( wps_unlink_product_from_plan( 999, $plan['slug'] ) );
	}

	// -----------------------------------------------------------------------
	// wps_get_plan_products() / wps_get_plan_by_product()
	// -----------------------------------------------------------------------

	/**
	 * Test that get plan products returns empty for unknown slug.
	 */
	public function test_get_plan_products_returns_empty_for_unknown_slug() {
		$this->assertEmpty( wps_get_plan_products( 'no-such-plan' ) );
	}

	/**
	 * Test that get plan products returns linked IDs.
	 */
	public function test_get_plan_products_returns_linked_ids() {
		$plan_id = wps_create_plan( array( 'name' => 'Product List Plan', 'products' => array( 1, 2, 3 ) ) );
		$plan    = wps_get_plan( $plan_id );
		$ids     = wps_get_plan_products( $plan['slug'] );
		$this->assertContains( 1, $ids );
		$this->assertContains( 2, $ids );
		$this->assertContains( 3, $ids );
	}

	/**
	 * Test that get plan by product returns null for unmapped product.
	 */
	public function test_get_plan_by_product_returns_null_for_unmapped_product() {
		delete_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		$this->assertNull( wps_get_plan_by_product( 99999 ) );
	}

	/**
	 * Test that get plan by product returns slug for mapped product.
	 */
	public function test_get_plan_by_product_returns_slug_for_mapped_product() {
		wps_create_plan( array( 'name' => 'By Product Plan', 'slug' => 'by-product', 'products' => array( 50 ) ) );
		$slug = wps_get_plan_by_product( 50 );
		$this->assertSame( 'by-product', $slug );
	}

	// -----------------------------------------------------------------------
	// wps_sanitize_access_length()
	// -----------------------------------------------------------------------

	/**
	 * Test that sanitize access length returns defaults for non array.
	 */
	public function test_sanitize_access_length_returns_defaults_for_non_array() {
		$result = wps_sanitize_access_length( 'invalid' );
		$this->assertSame( 'lifetime', $result['type'] );
		$this->assertSame( 0, $result['value'] );
		$this->assertSame( 'day', $result['unit'] );
	}

	/**
	 * Test that sanitize access length preserves fixed type.
	 */
	public function test_sanitize_access_length_preserves_fixed_type() {
		$result = wps_sanitize_access_length( array( 'type' => 'fixed', 'value' => 12, 'unit' => 'month' ) );
		$this->assertSame( 'fixed', $result['type'] );
		$this->assertSame( 12, $result['value'] );
		$this->assertSame( 'month', $result['unit'] );
	}

	/**
	 * Test that sanitize access length rejects invalid unit.
	 */
	public function test_sanitize_access_length_rejects_invalid_unit() {
		$result = wps_sanitize_access_length( array( 'type' => 'fixed', 'value' => 5, 'unit' => 'century' ) );
		$this->assertSame( 'day', $result['unit'] );
	}

	/**
	 * Test that sanitize access length rejects invalid type.
	 */
	public function test_sanitize_access_length_rejects_invalid_type() {
		$result = wps_sanitize_access_length( array( 'type' => 'unknown' ) );
		$this->assertSame( 'lifetime', $result['type'] );
	}

	/**
	 * Test that sanitize access length casts value to positive int.
	 */
	public function test_sanitize_access_length_casts_value_to_positive_int() {
		$result = wps_sanitize_access_length( array( 'type' => 'fixed', 'value' => '-7', 'unit' => 'year' ) );
		// absint( '-7' ) = 7.
		$this->assertSame( 7, $result['value'] );
	}

	// -----------------------------------------------------------------------
	// wps_get_plan_access_label()
	// -----------------------------------------------------------------------

	/**
	 * Test that access label is empty for non array.
	 */
	public function test_access_label_empty_for_non_array() {
		$this->assertSame( '', wps_get_plan_access_label( null ) );
	}

	/**
	 * Test that access label subscription is while subscribed.
	 */
	public function test_access_label_subscription_is_while_subscribed() {
		$plan = array( 'grant_method' => 'subscription' );
		$this->assertSame( 'Access while subscribed', wps_get_plan_access_label( $plan ) );
	}

	/**
	 * Test that access label lifetime when not fixed.
	 */
	public function test_access_label_lifetime_when_not_fixed() {
		$plan = array(
			'grant_method'  => 'purchase',
			'access_length' => array( 'type' => 'lifetime' ),
		);
		$this->assertSame( 'Lifetime access', wps_get_plan_access_label( $plan ) );
	}

	/**
	 * Test that access label fixed duration is pluralised.
	 */
	public function test_access_label_fixed_duration_is_pluralised() {
		$plan = array(
			'grant_method'  => 'purchase',
			'access_length' => array(
				'type'  => 'fixed',
				'value' => 30,
				'unit'  => 'day',
			),
		);
		$this->assertSame( '30 days of access', wps_get_plan_access_label( $plan ) );
	}

	/**
	 * Test that access label fixed singular unit.
	 */
	public function test_access_label_fixed_singular_unit() {
		$plan = array(
			'grant_method'  => 'purchase',
			'access_length' => array(
				'type'  => 'fixed',
				'value' => 1,
				'unit'  => 'year',
			),
		);
		$this->assertSame( '1 year of access', wps_get_plan_access_label( $plan ) );
	}

	/**
	 * Test that access label fixed with zero value falls back to lifetime.
	 */
	public function test_access_label_fixed_with_zero_value_falls_back_to_lifetime() {
		$plan = array(
			'grant_method'  => 'purchase',
			'access_length' => array(
				'type'  => 'fixed',
				'value' => 0,
				'unit'  => 'day',
			),
		);
		$this->assertSame( 'Lifetime access', wps_get_plan_access_label( $plan ) );
	}

	// -----------------------------------------------------------------------
	// Teardown
	// -----------------------------------------------------------------------

	/**
	 * Tear down test fixtures after each test.
	 */
	public function tearDown(): void {
		delete_option( WPS_MEMBERSHIP_PLAN_MAP_OPTION );
		parent::tearDown();
	}
}
