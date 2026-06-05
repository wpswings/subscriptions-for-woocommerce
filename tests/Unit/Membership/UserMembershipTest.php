<?php
/**
 * Unit tests for includes/membership/functions-user-membership.php
 *
 * Covers Day 03 deliverables:
 *   - Membership CRUD (create, read, update status, extend expiry, bulk-cancel)
 *   - Merge logic (subscription > order > manual, lifetime expiry wins)
 *   - Access API (wps_user_has_plan, wps_current_user_has_plan, wps_user_is_member)
 *   - Cache invalidation (flat key sync, object-cache bust)
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class UserMembershipTest extends WP_UnitTestCase {

	/** @var int A WordPress user created once per test. */
	private $user_id;

	public function setUp(): void {
		parent::setUp();
		$this->user_id = $this->factory->user->create();
	}

	// -----------------------------------------------------------------------
	// wps_create_user_membership()
	// -----------------------------------------------------------------------

	public function test_create_user_membership_returns_wp_error_for_invalid_user() {
		$result = wps_create_user_membership( 0, array( 'plan_slug' => 'gold' ) );
		$this->assertWPError( $result );
		$this->assertSame( 'wps_membership_invalid_user', $result->get_error_code() );
	}

	public function test_create_user_membership_returns_wp_error_for_missing_slug() {
		$result = wps_create_user_membership( $this->user_id, array() );
		$this->assertWPError( $result );
		$this->assertSame( 'wps_membership_no_plan', $result->get_error_code() );
	}

	public function test_create_user_membership_returns_true_on_success() {
		$result = wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		$this->assertTrue( $result );
	}

	public function test_create_user_membership_writes_wps_memberships_meta() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'silver' ) );
		$meta = get_user_meta( $this->user_id, 'wps_memberships', true );
		$this->assertIsArray( $meta );
		$this->assertArrayHasKey( 'silver', $meta );
	}

	public function test_create_user_membership_writes_flat_queryable_key() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'bronze' ) );
		$flat = get_user_meta( $this->user_id, 'wps_member_of_bronze', true );
		$this->assertSame( 'active', $flat );
	}

	public function test_create_user_membership_stores_subscription_pointer() {
		wps_create_user_membership(
			$this->user_id,
			array(
				'plan_slug'       => 'premium',
				'source'          => 'subscription',
				'subscription_id' => 123,
			)
		);
		$pointer = get_user_meta( $this->user_id, 'wps_sub_membership_123', true );
		$this->assertSame( 'premium', $pointer );
	}

	public function test_create_user_membership_fires_wps_membership_created_hook() {
		$fired = false;
		add_action( 'wps_membership_created', function () use ( &$fired ) { $fired = true; } );
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'hook-plan' ) );
		$this->assertTrue( $fired );
	}

	public function test_create_user_membership_stores_lifetime_when_expiry_null() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'lifetime-plan', 'expiry_date' => null )
		);
		$row = wps_get_membership( $this->user_id, 'lifetime-plan' );
		$this->assertNull( $row['expiry_date'] );
	}

	public function test_create_user_membership_stores_expiry_timestamp() {
		$future = time() + DAY_IN_SECONDS * 30;
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'expiring-plan', 'expiry_date' => $future )
		);
		$row = wps_get_membership( $this->user_id, 'expiring-plan' );
		$this->assertSame( $future, $row['expiry_date'] );
	}

	// -----------------------------------------------------------------------
	// Merge logic (same plan bought multiple ways)
	// -----------------------------------------------------------------------

	public function test_duplicate_create_merges_rather_than_duplicates() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merged', 'source' => 'order', 'order_id' => 10 )
		);
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merged', 'source' => 'subscription', 'subscription_id' => 20 )
		);
		$memberships = get_user_meta( $this->user_id, 'wps_memberships', true );
		// Only one entry per plan.
		$this->assertCount( 1, $memberships );
	}

	public function test_merge_keeps_subscription_source_over_order() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merge-src', 'source' => 'order' )
		);
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merge-src', 'source' => 'subscription', 'subscription_id' => 50 )
		);
		$row = wps_get_membership( $this->user_id, 'merge-src' );
		$this->assertSame( 'subscription', $row['source'] );
	}

	public function test_merge_keeps_subscription_source_over_manual() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merge-manual', 'source' => 'manual' )
		);
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merge-manual', 'source' => 'subscription', 'subscription_id' => 51 )
		);
		$row = wps_get_membership( $this->user_id, 'merge-manual' );
		$this->assertSame( 'subscription', $row['source'] );
	}

	public function test_merge_lifetime_expiry_wins_over_timestamp() {
		$future = time() + DAY_IN_SECONDS * 365;
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merge-expiry', 'expiry_date' => $future )
		);
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merge-expiry', 'expiry_date' => null )
		);
		$row = wps_get_membership( $this->user_id, 'merge-expiry' );
		$this->assertNull( $row['expiry_date'] );
	}

	public function test_merge_keeps_later_expiry_when_both_are_timestamps() {
		$near   = time() + DAY_IN_SECONDS * 10;
		$far    = time() + DAY_IN_SECONDS * 100;
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merge-ts', 'expiry_date' => $near )
		);
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'merge-ts', 'expiry_date' => $far )
		);
		$row = wps_get_membership( $this->user_id, 'merge-ts' );
		$this->assertSame( $far, $row['expiry_date'] );
	}

	// -----------------------------------------------------------------------
	// wps_get_membership()
	// -----------------------------------------------------------------------

	public function test_get_membership_returns_null_when_not_a_member() {
		$this->assertNull( wps_get_membership( $this->user_id, 'unknown-plan' ) );
	}

	public function test_get_membership_returns_row_after_create() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'readable' ) );
		$row = wps_get_membership( $this->user_id, 'readable' );
		$this->assertIsArray( $row );
		$this->assertSame( 'readable', $row['plan_slug'] );
	}

	// -----------------------------------------------------------------------
	// wps_get_user_memberships()
	// -----------------------------------------------------------------------

	public function test_get_user_memberships_returns_empty_when_no_memberships() {
		$this->assertEmpty( wps_get_user_memberships( $this->user_id ) );
	}

	public function test_get_user_memberships_returns_all_plans() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'plan-a' ) );
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'plan-b' ) );
		$all = wps_get_user_memberships( $this->user_id, 'all' );
		$this->assertCount( 2, $all );
	}

	public function test_get_user_memberships_filters_by_status() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'active-plan', 'status' => 'active' ) );
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'hold-plan', 'status' => 'on-hold' ) );
		$active = wps_get_user_memberships( $this->user_id, 'active' );
		$this->assertCount( 1, $active );
		$this->assertSame( 'active-plan', $active[0]['plan_slug'] );
	}

	// -----------------------------------------------------------------------
	// wps_update_membership_status()
	// -----------------------------------------------------------------------

	public function test_update_membership_status_returns_wp_error_for_invalid_status() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'status-test' ) );
		$result = wps_update_membership_status( $this->user_id, 'status-test', 'not-a-status' );
		$this->assertWPError( $result );
		$this->assertSame( 'wps_membership_invalid_status', $result->get_error_code() );
	}

	public function test_update_membership_status_returns_wp_error_when_not_a_member() {
		$result = wps_update_membership_status( $this->user_id, 'no-such-plan', 'cancelled' );
		$this->assertWPError( $result );
		$this->assertSame( 'wps_membership_not_found', $result->get_error_code() );
	}

	public function test_update_membership_status_persists_new_status() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'to-cancel' ) );
		wps_update_membership_status( $this->user_id, 'to-cancel', 'cancelled' );
		$row = wps_get_membership( $this->user_id, 'to-cancel' );
		$this->assertSame( 'cancelled', $row['status'] );
	}

	public function test_update_membership_status_updates_flat_key() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'flat-key-plan' ) );
		wps_update_membership_status( $this->user_id, 'flat-key-plan', 'on-hold' );
		$flat = get_user_meta( $this->user_id, 'wps_member_of_flat-key-plan', true );
		$this->assertSame( 'on-hold', $flat );
	}

	public function test_update_membership_status_fires_hook() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'hook-status' ) );
		$fired = false;
		add_action(
			'wps_membership_status_changed',
			function () use ( &$fired ) { $fired = true; }
		);
		wps_update_membership_status( $this->user_id, 'hook-status', 'paused' );
		$this->assertTrue( $fired );
	}

	public function test_update_membership_status_passes_old_and_new_status_to_hook() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'transition-plan', 'status' => 'active' ) );
		$received = array();
		add_action(
			'wps_membership_status_changed',
			function ( $uid, $slug, $new, $old ) use ( &$received ) {
				$received = compact( 'new', 'old' );
			},
			10, 4
		);
		wps_update_membership_status( $this->user_id, 'transition-plan', 'cancelled' );
		$this->assertSame( 'cancelled', $received['new'] );
		$this->assertSame( 'active', $received['old'] );
	}

	public function test_update_membership_status_fires_hook_even_when_status_unchanged() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'same-status', 'status' => 'active' ) );
		$count = 0;
		add_action( 'wps_membership_status_changed', function () use ( &$count ) { $count++; } );
		wps_update_membership_status( $this->user_id, 'same-status', 'active' );
		$this->assertGreaterThanOrEqual( 1, $count );
	}

	// -----------------------------------------------------------------------
	// wps_extend_membership_expiry()
	// -----------------------------------------------------------------------

	public function test_extend_membership_expiry_returns_wp_error_when_not_a_member() {
		$result = wps_extend_membership_expiry( $this->user_id, 'ghost-plan', time() + 1000 );
		$this->assertWPError( $result );
	}

	public function test_extend_membership_expiry_updates_to_new_timestamp() {
		$new_expiry = time() + DAY_IN_SECONDS * 60;
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'expiry-plan' ) );
		wps_extend_membership_expiry( $this->user_id, 'expiry-plan', $new_expiry );
		$row = wps_get_membership( $this->user_id, 'expiry-plan' );
		$this->assertSame( $new_expiry, $row['expiry_date'] );
	}

	public function test_extend_membership_expiry_null_grants_lifetime() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'to-lifetime', 'expiry_date' => time() + 100 )
		);
		wps_extend_membership_expiry( $this->user_id, 'to-lifetime', null );
		$row = wps_get_membership( $this->user_id, 'to-lifetime' );
		$this->assertNull( $row['expiry_date'] );
	}

	public function test_extend_membership_expiry_fires_hook() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'expiry-hook' ) );
		$fired = false;
		add_action( 'wps_membership_expiry_updated', function () use ( &$fired ) { $fired = true; } );
		wps_extend_membership_expiry( $this->user_id, 'expiry-hook', time() + 999 );
		$this->assertTrue( $fired );
	}

	// -----------------------------------------------------------------------
	// wps_cancel_all_memberships_for_plan()
	// -----------------------------------------------------------------------

	public function test_cancel_all_memberships_for_plan_cancels_every_member() {
		$uid1 = $this->factory->user->create();
		$uid2 = $this->factory->user->create();

		wps_create_user_membership( $uid1, array( 'plan_slug' => 'mass-cancel' ) );
		wps_create_user_membership( $uid2, array( 'plan_slug' => 'mass-cancel' ) );

		wps_cancel_all_memberships_for_plan( 'mass-cancel' );

		$this->assertSame( 'cancelled', wps_get_membership( $uid1, 'mass-cancel' )['status'] );
		$this->assertSame( 'cancelled', wps_get_membership( $uid2, 'mass-cancel' )['status'] );
	}

	public function test_cancel_all_memberships_for_plan_ignores_empty_slug() {
		// Must not throw — guard against empty input.
		wps_cancel_all_memberships_for_plan( '' );
		$this->assertTrue( true );
	}

	// -----------------------------------------------------------------------
	// Access API — wps_membership_row_is_active()
	// -----------------------------------------------------------------------

	public function test_membership_row_is_active_returns_true_for_active_with_null_expiry() {
		$row = array( 'status' => 'active', 'expiry_date' => null );
		$this->assertTrue( wps_membership_row_is_active( $row, time() ) );
	}

	public function test_membership_row_is_active_returns_true_for_active_future_expiry() {
		$row = array( 'status' => 'active', 'expiry_date' => time() + 1000 );
		$this->assertTrue( wps_membership_row_is_active( $row, time() ) );
	}

	public function test_membership_row_is_active_returns_false_when_expired() {
		$row = array( 'status' => 'active', 'expiry_date' => time() - 1 );
		$this->assertFalse( wps_membership_row_is_active( $row, time() ) );
	}

	public function test_membership_row_is_active_returns_false_for_non_active_status() {
		foreach ( array( 'on-hold', 'cancelled', 'expired', 'paused' ) as $status ) {
			$row = array( 'status' => $status, 'expiry_date' => null );
			$this->assertFalse( wps_membership_row_is_active( $row, time() ), "Status '$status' should not be active." );
		}
	}

	// -----------------------------------------------------------------------
	// Access API — wps_user_has_plan()
	// -----------------------------------------------------------------------

	public function test_user_has_plan_returns_false_when_no_memberships() {
		$this->assertFalse( wps_user_has_plan( $this->user_id, 'gold' ) );
	}

	public function test_user_has_plan_returns_true_for_active_member() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'gold', 'status' => 'active', 'expiry_date' => null )
		);
		$this->assertTrue( wps_user_has_plan( $this->user_id, 'gold' ) );
	}

	public function test_user_has_plan_returns_false_for_cancelled_member() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold', 'status' => 'cancelled' ) );
		$this->assertFalse( wps_user_has_plan( $this->user_id, 'gold' ) );
	}

	public function test_user_has_plan_returns_false_for_expired_membership() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'gold', 'status' => 'active', 'expiry_date' => time() - 1 )
		);
		$this->assertFalse( wps_user_has_plan( $this->user_id, 'gold' ) );
	}

	public function test_user_has_plan_accepts_array_of_slugs() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'silver', 'status' => 'active', 'expiry_date' => null )
		);
		$this->assertTrue( wps_user_has_plan( $this->user_id, array( 'gold', 'silver' ) ) );
	}

	public function test_user_has_plan_returns_false_when_no_slug_in_array_matches() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'silver', 'status' => 'active', 'expiry_date' => null )
		);
		$this->assertFalse( wps_user_has_plan( $this->user_id, array( 'gold', 'platinum' ) ) );
	}

	public function test_user_has_plan_any_returns_true_when_one_active_plan_exists() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'bronze', 'status' => 'active', 'expiry_date' => null )
		);
		$this->assertTrue( wps_user_has_plan( $this->user_id, 'any' ) );
	}

	public function test_user_has_plan_any_returns_false_when_only_cancelled_plans_exist() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'bronze', 'status' => 'cancelled' ) );
		$this->assertFalse( wps_user_has_plan( $this->user_id, 'any' ) );
	}

	// -----------------------------------------------------------------------
	// Access API — wps_current_user_has_plan()
	// -----------------------------------------------------------------------

	public function test_current_user_has_plan_returns_false_when_not_logged_in() {
		wp_set_current_user( 0 );
		$this->assertFalse( wps_current_user_has_plan( 'gold' ) );
	}

	public function test_current_user_has_plan_returns_true_for_logged_in_active_member() {
		wp_set_current_user( $this->user_id );
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'current-plan', 'status' => 'active', 'expiry_date' => null )
		);
		$this->assertTrue( wps_current_user_has_plan( 'current-plan' ) );
	}

	// -----------------------------------------------------------------------
	// Access API — wps_user_is_member()
	// -----------------------------------------------------------------------

	public function test_user_is_member_returns_false_when_no_memberships() {
		$this->assertFalse( wps_user_is_member( $this->user_id ) );
	}

	public function test_user_is_member_returns_true_when_active_in_any_plan() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'any-plan', 'status' => 'active', 'expiry_date' => null )
		);
		$this->assertTrue( wps_user_is_member( $this->user_id ) );
	}

	public function test_user_is_member_returns_false_when_all_memberships_cancelled() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'c1', 'status' => 'cancelled' ) );
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'c2', 'status' => 'expired' ) );
		$this->assertFalse( wps_user_is_member( $this->user_id ) );
	}

	// -----------------------------------------------------------------------
	// Cache behaviour
	// -----------------------------------------------------------------------

	public function test_object_cache_is_busted_after_status_update() {
		wps_create_user_membership(
			$this->user_id,
			array( 'plan_slug' => 'cache-plan', 'status' => 'active', 'expiry_date' => null )
		);
		// Prime cache.
		$this->assertTrue( wps_user_has_plan( $this->user_id, 'cache-plan' ) );

		// Change status via CRUD (must bust cache).
		wps_update_membership_status( $this->user_id, 'cache-plan', 'cancelled' );

		// Must reflect the new status on the very next call.
		$this->assertFalse( wps_user_has_plan( $this->user_id, 'cache-plan' ) );
	}

	// -----------------------------------------------------------------------
	// wps_merge_membership_rows() — unit tests on the pure function
	// -----------------------------------------------------------------------

	public function test_merge_rows_incoming_order_beats_existing_manual() {
		$existing = array(
			'plan_slug' => 'p', 'status' => 'active', 'source' => 'manual',
			'subscription_id' => null, 'order_id' => null,
			'start_date' => 1000, 'expiry_date' => null, 'updated_at' => 1000,
		);
		$incoming = array(
			'plan_slug' => 'p', 'status' => 'active', 'source' => 'order',
			'subscription_id' => null, 'order_id' => 99,
			'start_date' => 2000, 'expiry_date' => null, 'updated_at' => 2000,
		);
		$merged = wps_merge_membership_rows( $existing, $incoming );
		$this->assertSame( 'order', $merged['source'] );
		$this->assertSame( 99, $merged['order_id'] );
	}

	public function test_merge_rows_existing_subscription_beats_incoming_order() {
		$existing = array(
			'plan_slug' => 'p', 'status' => 'active', 'source' => 'subscription',
			'subscription_id' => 77, 'order_id' => null,
			'start_date' => 1000, 'expiry_date' => null, 'updated_at' => 1000,
		);
		$incoming = array(
			'plan_slug' => 'p', 'status' => 'on-hold', 'source' => 'order',
			'subscription_id' => null, 'order_id' => 88,
			'start_date' => 2000, 'expiry_date' => null, 'updated_at' => 2000,
		);
		$merged = wps_merge_membership_rows( $existing, $incoming );
		// Subscription > order: existing source wins.
		$this->assertSame( 'subscription', $merged['source'] );
		$this->assertSame( 77, $merged['subscription_id'] );
		// order_id from incoming should still be preserved.
		$this->assertSame( 88, $merged['order_id'] );
		// Status comes from incoming row.
		$this->assertSame( 'on-hold', $merged['status'] );
	}

	public function test_merge_rows_start_date_comes_from_existing() {
		$existing = array(
			'plan_slug' => 'p', 'status' => 'active', 'source' => 'manual',
			'subscription_id' => null, 'order_id' => null,
			'start_date' => 500, 'expiry_date' => null, 'updated_at' => 500,
		);
		$incoming = array(
			'plan_slug' => 'p', 'status' => 'active', 'source' => 'order',
			'subscription_id' => null, 'order_id' => 1,
			'start_date' => 9999, 'expiry_date' => null, 'updated_at' => 9999,
		);
		$merged = wps_merge_membership_rows( $existing, $incoming );
		$this->assertSame( 500, $merged['start_date'] );
	}
}
