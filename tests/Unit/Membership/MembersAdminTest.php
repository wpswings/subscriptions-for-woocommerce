<?php
/**
 * Unit tests for Day 09 deliverables:
 *   - wps_remove_user_membership() — hard-delete removes both meta keys and pointer
 *   - WPS_Members_Admin::save_profile_section() — grant, revoke, reactivate, remove via POST
 *   - WPS_Members_Admin::handle_row_actions() — cancel/reactivate/remove via GET (admin_init)
 *   - WPS_Members_Admin::handle_admin_actions() — AJAX grant / edit / search_users
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class MembersAdminTest extends WP_UnitTestCase {

	/** @var int A WordPress user created per test. */
	private $user_id;

	/** @var WPS_Members_Admin */
	private $admin;

	public function setUp(): void {
		parent::setUp();
		$this->user_id = $this->factory->user->create();
		$this->admin   = new WPS_Members_Admin();
	}

	// -----------------------------------------------------------------------
	// wps_remove_user_membership()
	// -----------------------------------------------------------------------

	public function test_remove_returns_false_for_nonexistent_membership() {
		$result = wps_remove_user_membership( $this->user_id, 'ghost-plan' );
		$this->assertFalse( $result );
	}

	public function test_remove_returns_true_when_record_exists() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		$result = wps_remove_user_membership( $this->user_id, 'gold' );
		$this->assertTrue( $result );
	}

	public function test_remove_deletes_from_wps_memberships_array() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'silver' ) );
		wps_remove_user_membership( $this->user_id, 'silver' );

		$meta = get_user_meta( $this->user_id, 'wps_memberships', true );
		$this->assertFalse( isset( $meta['silver'] ) );
	}

	public function test_remove_deletes_flat_queryable_key() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'bronze' ) );
		wps_remove_user_membership( $this->user_id, 'bronze' );

		$flat = get_user_meta( $this->user_id, 'wps_member_of_bronze', true );
		$this->assertEmpty( $flat );
	}

	public function test_remove_deletes_subscription_pointer() {
		wps_create_user_membership(
			$this->user_id,
			array(
				'plan_slug'       => 'premium',
				'source'          => 'subscription',
				'subscription_id' => 42,
			)
		);
		wps_remove_user_membership( $this->user_id, 'premium' );

		$pointer = get_user_meta( $this->user_id, 'wps_sub_membership_42', true );
		$this->assertEmpty( $pointer );
	}

	public function test_remove_leaves_other_plan_memberships_intact() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'plan-a' ) );
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'plan-b' ) );

		wps_remove_user_membership( $this->user_id, 'plan-a' );

		$remaining = wps_get_membership( $this->user_id, 'plan-b' );
		$this->assertNotNull( $remaining );
		$this->assertSame( 'plan-b', $remaining['plan_slug'] );
	}

	public function test_remove_fires_wps_membership_removed_hook() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'hook-plan' ) );

		$fired = false;
		add_action( 'wps_membership_removed', function () use ( &$fired ) {
			$fired = true;
		} );

		wps_remove_user_membership( $this->user_id, 'hook-plan' );
		$this->assertTrue( $fired );
	}

	public function test_remove_busts_object_cache() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'cache-plan' ) );
		// Prime the cache.
		wps_user_has_plan( $this->user_id, 'cache-plan' );

		wps_remove_user_membership( $this->user_id, 'cache-plan' );

		// After removal the access check must return false, not a stale true.
		$this->assertFalse( wps_user_has_plan( $this->user_id, 'cache-plan' ) );
	}

	public function test_remove_returns_false_for_invalid_user_id() {
		$result = wps_remove_user_membership( 0, 'gold' );
		$this->assertFalse( $result );
	}

	// -----------------------------------------------------------------------
	// save_profile_section() — grant path
	// -----------------------------------------------------------------------

	public function test_save_profile_section_grants_membership_with_manual_source() {
		$plan_id = wps_create_plan( array( 'name' => 'Profile Plan' ) );
		$plan    = wps_get_plan( $plan_id );

		$_POST = array(
			'wps_profile_membership_nonce' => wp_create_nonce(
				'wps_profile_membership_' . $this->user_id
			),
			'wps_profile_grant_plan'       => $plan['slug'],
		);

		// Must run as a user with manage_woocommerce.
		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$this->admin->save_profile_section( $this->user_id );

		$membership = wps_get_membership( $this->user_id, $plan['slug'] );
		$this->assertNotNull( $membership );
		$this->assertSame( 'manual', $membership['source'] );
		$this->assertSame( 'active', $membership['status'] );
	}

	public function test_save_profile_section_revokes_membership() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'revoke-plan' ) );

		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$_POST = array(
			'wps_profile_membership_nonce'   => wp_create_nonce(
				'wps_profile_membership_' . $this->user_id
			),
			'wps_profile_membership_action'  => 'revoke',
			'wps_profile_action_plan'        => 'revoke-plan',
		);

		$this->admin->save_profile_section( $this->user_id );

		$membership = wps_get_membership( $this->user_id, 'revoke-plan' );
		$this->assertSame( 'cancelled', $membership['status'] );
	}

	public function test_save_profile_section_reactivates_membership() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'react-plan' ) );
		wps_update_membership_status( $this->user_id, 'react-plan', 'cancelled' );

		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$_POST = array(
			'wps_profile_membership_nonce'   => wp_create_nonce(
				'wps_profile_membership_' . $this->user_id
			),
			'wps_profile_membership_action'  => 'reactivate',
			'wps_profile_action_plan'        => 'react-plan',
		);

		$this->admin->save_profile_section( $this->user_id );

		$membership = wps_get_membership( $this->user_id, 'react-plan' );
		$this->assertSame( 'active', $membership['status'] );
	}

	public function test_save_profile_section_removes_membership() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'rm-plan' ) );

		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$_POST = array(
			'wps_profile_membership_nonce'   => wp_create_nonce(
				'wps_profile_membership_' . $this->user_id
			),
			'wps_profile_membership_action'  => 'remove',
			'wps_profile_action_plan'        => 'rm-plan',
		);

		$this->admin->save_profile_section( $this->user_id );

		$membership = wps_get_membership( $this->user_id, 'rm-plan' );
		$this->assertNull( $membership );
	}

	public function test_save_profile_section_ignores_bad_nonce() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'nonce-plan' ) );

		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$_POST = array(
			'wps_profile_membership_nonce'   => 'bad-nonce',
			'wps_profile_membership_action'  => 'revoke',
			'wps_profile_action_plan'        => 'nonce-plan',
		);

		$this->admin->save_profile_section( $this->user_id );

		// Bad nonce — status must be unchanged.
		$membership = wps_get_membership( $this->user_id, 'nonce-plan' );
		$this->assertSame( 'active', $membership['status'] );
	}

	// -----------------------------------------------------------------------
	// handle_row_actions() — GET row actions
	// -----------------------------------------------------------------------

	public function test_handle_row_actions_cancels_membership() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'row-plan' ) );

		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$_GET = array(
			'page'              => 'subscriptions_for_woocommerce_menu',
			'sfw_tab'           => 'wps-membership-members',
			'wps_member_action' => 'cancel',
			'user_id'           => $this->user_id,
			'plan_slug'         => 'row-plan',
			'_wpnonce'          => wp_create_nonce( 'wps_member_cancel_' . $this->user_id . '_row-plan' ),
		);

		// handle_row_actions calls wp_safe_redirect + exit on success — catch it.
		try {
			$this->admin->handle_row_actions();
		} catch ( Exception $e ) {
			// wp_safe_redirect may throw in test context; that is fine.
		}

		$membership = wps_get_membership( $this->user_id, 'row-plan' );
		$this->assertSame( 'cancelled', $membership['status'] );
	}

	public function test_handle_row_actions_removes_membership() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'del-plan' ) );

		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$_GET = array(
			'page'              => 'subscriptions_for_woocommerce_menu',
			'sfw_tab'           => 'wps-membership-members',
			'wps_member_action' => 'remove',
			'user_id'           => $this->user_id,
			'plan_slug'         => 'del-plan',
			'_wpnonce'          => wp_create_nonce( 'wps_member_remove_' . $this->user_id . '_del-plan' ),
		);

		try {
			$this->admin->handle_row_actions();
		} catch ( Exception $e ) {
			// redirect + exit in test context.
		}

		$membership = wps_get_membership( $this->user_id, 'del-plan' );
		$this->assertNull( $membership );
	}

	public function test_handle_row_actions_does_nothing_outside_the_tab() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'safe-plan' ) );

		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$_GET = array(
			'page'              => 'subscriptions_for_woocommerce_menu',
			'sfw_tab'           => 'some-other-tab',
			'wps_member_action' => 'cancel',
			'user_id'           => $this->user_id,
			'plan_slug'         => 'safe-plan',
			'_wpnonce'          => wp_create_nonce( 'wps_member_cancel_' . $this->user_id . '_safe-plan' ),
		);

		$this->admin->handle_row_actions();

		$membership = wps_get_membership( $this->user_id, 'safe-plan' );
		$this->assertSame( 'active', $membership['status'] );
	}

	public function tearDown(): void {
		$_POST = array();
		$_GET  = array();
		parent::tearDown();
	}
}
