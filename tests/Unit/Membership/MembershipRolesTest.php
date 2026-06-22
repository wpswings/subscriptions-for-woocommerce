<?php
/**
 * Unit tests for membership role assignment (Day 19).
 *
 * Covers Free plan-row meta exposure and Pro WPS_Membership_Roles enforcement:
 * add on active, remove on cancel/expire, preserve existing roles, keep role
 * when another active plan grants it.
 *
 * The Pro plugin is not loaded by the Free test bootstrap, so the enforcement
 * class file is required directly — it depends only on Free membership functions.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Test suite for WPS_Membership_Roles role-assignment enforcement.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */
class MembershipRolesTest extends WP_UnitTestCase {

	/**
	 * Membership roles enforcement instance under test.
	 *
	 * @var WPS_Membership_Roles
	 */
	private $roles;

	/**
	 * WordPress user ID created for each test.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Load the Pro class file once before any test in this suite runs.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$pro_class = dirname( __DIR__, 4 )
			. '/woocommerce-subscriptions-pro/includes/pro/class-wps-membership-roles.php';
		if ( file_exists( $pro_class ) ) {
			require_once $pro_class;
		}
	}

	/**
	 * Set up test fixtures before each test method.
	 *
	 * Creates a subscriber user and registers three test plans (gold, silver, bronze)
	 * with different role and remove-on-cancel configurations.
	 *
	 * @return void
	 */
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

	/**
	 * Tear down test fixtures after each test method.
	 *
	 * Deletes the test user and flushes the object cache.
	 *
	 * @return void
	 */
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

	/**
	 * Return a fresh WP_User instance for the test subject.
	 *
	 * @return WP_User
	 */
	private function user() {
		return get_userdata( $this->user_id );
	}

	/**
	 * Tests: role added on membership activation.
	 *
	 * @group membership-roles
	 */

	/**
	 * Assert that a role is added and existing roles are preserved when a membership is created with active status.
	 *
	 * @return void
	 */
	public function test_role_added_on_membership_created_active() {
		$this->roles->on_membership_created( $this->user_id, 'gold', array( 'status' => 'active' ) );

		$roles = (array) $this->user()->roles;
		$this->assertContains( 'editor', $roles );
		$this->assertContains( 'subscriber', $roles, 'Existing roles must be preserved.' );
	}

	/**
	 * Assert that a role is NOT added when a membership is created with an inactive status.
	 *
	 * @return void
	 */
	public function test_role_not_added_when_created_inactive() {
		$this->roles->on_membership_created( $this->user_id, 'gold', array( 'status' => 'cancelled' ) );

		$this->assertNotContains( 'editor', (array) $this->user()->roles );
	}

	/**
	 * Assert that a role is added when a membership status transitions to active.
	 *
	 * @return void
	 */
	public function test_role_added_on_status_active() {
		$this->roles->on_status_changed( $this->user_id, 'gold', 'active', 'on-hold' );

		$this->assertContains( 'editor', (array) $this->user()->roles );
	}

	/**
	 * Tests: role removed on cancel or expire.
	 *
	 * @group membership-roles
	 */

	/**
	 * Assert that a role is removed on cancellation when the remove flag is set on the plan.
	 *
	 * @return void
	 */
	public function test_role_removed_on_cancel_when_flag_set() {
		$this->roles->on_status_changed( $this->user_id, 'gold', 'active', 'on-hold' );
		$this->assertContains( 'editor', (array) $this->user()->roles );

		$this->roles->on_status_changed( $this->user_id, 'gold', 'cancelled', 'active' );
		$this->assertNotContains( 'editor', (array) $this->user()->roles );
	}

	/**
	 * Assert that a role is kept after cancellation when the remove flag is not set on the plan.
	 *
	 * @return void
	 */
	public function test_role_kept_on_cancel_when_flag_not_set() {
		$this->roles->on_status_changed( $this->user_id, 'bronze', 'active', 'on-hold' );
		$this->assertContains( 'author', (array) $this->user()->roles );

		$this->roles->on_status_changed( $this->user_id, 'bronze', 'expired', 'active' );
		$this->assertContains( 'author', (array) $this->user()->roles, 'Role kept when remove flag is off.' );
	}

	/**
	 * Assert that a role is not stripped when another active plan grants the same role.
	 *
	 * @return void
	 */
	public function test_role_kept_when_another_active_plan_grants_it() {
		// User actively belongs to 'silver' (also grants editor).
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'silver' ) );
		$this->roles->on_status_changed( $this->user_id, 'gold', 'active', 'on-hold' );
		$this->assertContains( 'editor', (array) $this->user()->roles );

		// Cancelling 'gold' must NOT strip editor — 'silver' still grants it.
		$this->roles->on_status_changed( $this->user_id, 'gold', 'cancelled', 'active' );
		$this->assertContains( 'editor', (array) $this->user()->roles );
	}

	/**
	 * Assert that a role is revoked when the membership is explicitly removed.
	 *
	 * @return void
	 */
	public function test_membership_removed_revokes_role() {
		$this->roles->on_status_changed( $this->user_id, 'gold', 'active', 'on-hold' );
		$this->assertContains( 'editor', (array) $this->user()->roles );

		$this->roles->on_membership_removed( $this->user_id, 'gold', array() );
		$this->assertNotContains( 'editor', (array) $this->user()->roles );
	}

	/**
	 * Tests: Free plugin plan row exposes role meta.
	 *
	 * @group membership-roles
	 */

	/**
	 * Assert that the plan row exposes _wps_plan_user_role and _wps_plan_remove_role meta.
	 *
	 * @return void
	 */
	public function test_plan_row_exposes_role_meta() {
		$plan = wps_get_plan_by_slug( 'gold' );

		$this->assertSame( 'editor', $plan['user_role'] );
		$this->assertSame( '1', (string) $plan['remove_role'] );
	}
}
