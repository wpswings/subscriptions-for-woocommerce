<?php
/**
 * Unit tests for Day 19 (Pro — Role assignment per plan):
 *   Free:  _wps_plan_user_role / _wps_plan_remove_role exposed on the plan row.
 *   Pro:   WPS_Membership_Roles enforcement (add on active, remove on cancel/expire,
 *          preserve existing roles, keep role when another active plan grants it).
 *
 * The Pro plugin is not loaded by the Free test bootstrap, so the enforcement
 * class file is required directly — it depends only on Free membership functions.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class MembershipRolesTest extends WP_UnitTestCase {

	/** @var WPS_Membership_Roles */
	private $roles;

	/** @var int */
	private $user_id;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$pro_class = dirname( __DIR__, 4 )
			. '/woocommerce-subscriptions-pro/includes/pro/class-wps-membership-roles.php';
		if ( file_exists( $pro_class ) ) {
			require_once $pro_class;
		}
	}

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WPS_Membership_Roles' ) ) {
			$this->markTestSkipped( 'Pro WPS_Membership_Roles class not available.' );
		}

		$this->roles   = new WPS_Membership_Roles();
		$this->user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// 'gold' grants editor and removes it on cancel/expire.
		$this->make_plan( 'Gold', 'gold', 'editor', '1' );
		// 'silver' also grants editor (used for the "kept by another plan" case).
		$this->make_plan( 'Silver', 'silver', 'editor', '1' );
		// 'bronze' grants author but does NOT remove it.
		$this->make_plan( 'Bronze', 'bronze', 'author', '0' );

		wp_cache_flush();
	}

	public function tearDown(): void {
		if ( $this->user_id ) {
			wp_delete_user( $this->user_id );
		}
		wp_cache_flush();
		parent::tearDown();
	}

	/**
	 * Create a plan with a role config.
	 *
	 * @param string $name   Plan name.
	 * @param string $slug   Plan slug.
	 * @param string $role   Role slug to grant.
	 * @param string $remove '1' to remove on cancel/expire.
	 */
	private function make_plan( $name, $slug, $role, $remove ) {
		$plan_id = wps_create_plan(
			array(
				'name'   => $name,
				'slug'   => $slug,
				'status' => 'active',
			)
		);
		update_post_meta( $plan_id, '_wps_plan_user_role', $role );
		update_post_meta( $plan_id, '_wps_plan_remove_role', $remove );
	}

	/** Fresh WP_User for the test subject. */
	private function user() {
		return get_userdata( $this->user_id );
	}

	// -----------------------------------------------------------------------
	// Add on activation
	// -----------------------------------------------------------------------

	public function test_role_added_on_membership_created_active() {
		$this->roles->on_membership_created( $this->user_id, 'gold', array( 'status' => 'active' ) );

		$roles = (array) $this->user()->roles;
		$this->assertContains( 'editor', $roles );
		$this->assertContains( 'subscriber', $roles, 'Existing roles must be preserved.' );
	}

	public function test_role_not_added_when_created_inactive() {
		$this->roles->on_membership_created( $this->user_id, 'gold', array( 'status' => 'cancelled' ) );

		$this->assertNotContains( 'editor', (array) $this->user()->roles );
	}

	public function test_role_added_on_status_active() {
		$this->roles->on_status_changed( $this->user_id, 'gold', 'active', 'on-hold' );

		$this->assertContains( 'editor', (array) $this->user()->roles );
	}

	// -----------------------------------------------------------------------
	// Remove on cancel / expire
	// -----------------------------------------------------------------------

	public function test_role_removed_on_cancel_when_flag_set() {
		$this->roles->on_status_changed( $this->user_id, 'gold', 'active', 'on-hold' );
		$this->assertContains( 'editor', (array) $this->user()->roles );

		$this->roles->on_status_changed( $this->user_id, 'gold', 'cancelled', 'active' );
		$this->assertNotContains( 'editor', (array) $this->user()->roles );
	}

	public function test_role_kept_on_cancel_when_flag_not_set() {
		$this->roles->on_status_changed( $this->user_id, 'bronze', 'active', 'on-hold' );
		$this->assertContains( 'author', (array) $this->user()->roles );

		$this->roles->on_status_changed( $this->user_id, 'bronze', 'expired', 'active' );
		$this->assertContains( 'author', (array) $this->user()->roles, 'Role kept when remove flag is off.' );
	}

	public function test_role_kept_when_another_active_plan_grants_it() {
		// User actively belongs to 'silver' (also grants editor).
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'silver' ) );
		$this->roles->on_status_changed( $this->user_id, 'gold', 'active', 'on-hold' );
		$this->assertContains( 'editor', (array) $this->user()->roles );

		// Cancelling 'gold' must NOT strip editor — 'silver' still grants it.
		$this->roles->on_status_changed( $this->user_id, 'gold', 'cancelled', 'active' );
		$this->assertContains( 'editor', (array) $this->user()->roles );
	}

	public function test_membership_removed_revokes_role() {
		$this->roles->on_status_changed( $this->user_id, 'gold', 'active', 'on-hold' );
		$this->assertContains( 'editor', (array) $this->user()->roles );

		$this->roles->on_membership_removed( $this->user_id, 'gold', array() );
		$this->assertNotContains( 'editor', (array) $this->user()->roles );
	}

	// -----------------------------------------------------------------------
	// Free: plan row exposes the meta
	// -----------------------------------------------------------------------

	public function test_plan_row_exposes_role_meta() {
		$plan = wps_get_plan_by_slug( 'gold' );

		$this->assertSame( 'editor', $plan['user_role'] );
		$this->assertSame( '1', (string) $plan['remove_role'] );
	}
}
