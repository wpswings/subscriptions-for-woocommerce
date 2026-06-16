<?php
/**
 * Unit tests for Day 14: WPS_Myaccount_Memberships
 *
 * Covers:
 *   - add_query_var: 'memberships' is appended to the vars array
 *   - add_menu_item: item inserted after 'wps_subscriptions'
 *   - add_menu_item: fallback insertion before 'customer-logout'
 *   - add_menu_item: 'customer-logout' remains last after insertion
 *   - render_tab: outputs membership table rows for a user with memberships
 *   - render_tab: outputs empty-state message for a user with no memberships
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for WPS_Myaccount_Memberships.
 *
 * @since 2.0.0
 */
class MyaccountMembershipsTest extends WP_UnitTestCase {

	/**
	 * Instance of the myaccount memberships tab under test.
	 *
	 * @var WPS_Myaccount_Memberships
	 */
	private $tab;

	/**
	 * ID of the test subscriber user.
	 *
	 * @var int
	 */
	private $user_id;

	// -----------------------------------------------------------------------
	// Lifecycle
	// -----------------------------------------------------------------------

	/**
	 * Sets up test fixtures before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->tab     = new WPS_Myaccount_Memberships();
		$this->user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_cache_flush();
	}

	/**
	 * Tears down test fixtures after each test.
	 */
	public function tearDown(): void {
		wp_delete_user( $this->user_id );
		wp_cache_flush();
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// add_query_var
	// -----------------------------------------------------------------------

	/** 'memberships' is appended to the provided vars array. */
	public function test_add_query_var_appends_memberships() {
		$result = $this->tab->add_query_var( array( 'existing' ) );

		$this->assertContains( 'wps_memberships', $result );
	}

	/** The original vars are preserved. */
	public function test_add_query_var_preserves_existing_vars() {
		$result = $this->tab->add_query_var( array( 'foo', 'bar' ) );

		$this->assertContains( 'foo', $result );
		$this->assertContains( 'bar', $result );
	}

	// -----------------------------------------------------------------------
	// add_menu_item
	// -----------------------------------------------------------------------

	/** 'memberships' is inserted immediately after 'wps_subscriptions'. */
	public function test_menu_item_inserted_after_wps_subscriptions() {
		$items = array(
			'dashboard'        => 'Dashboard',
			'wps_subscriptions' => 'Subscriptions',
			'orders'           => 'Orders',
			'customer-logout'  => 'Logout',
		);

		$result = $this->tab->add_menu_item( $items );
		$keys   = array_keys( $result );

		$sub_pos  = array_search( 'wps_subscriptions', $keys, true );
		$mem_pos  = array_search( 'wps_memberships', $keys, true );

		$this->assertNotFalse( $mem_pos, "'memberships' key must exist in result." );
		$this->assertSame( $sub_pos + 1, $mem_pos, "'memberships' must follow 'wps_subscriptions'." );
	}

	/** 'memberships' is inserted before 'customer-logout' when 'wps_subscriptions' is absent. */
	public function test_menu_item_fallback_before_customer_logout() {
		$items = array(
			'dashboard'       => 'Dashboard',
			'orders'          => 'Orders',
			'customer-logout' => 'Logout',
		);

		$result = $this->tab->add_menu_item( $items );
		$keys   = array_keys( $result );

		$mem_pos    = array_search( 'wps_memberships', $keys, true );
		$logout_pos = array_search( 'customer-logout', $keys, true );

		$this->assertNotFalse( $mem_pos );
		$this->assertSame( $mem_pos + 1, $logout_pos, "'customer-logout' must follow 'memberships'." );
	}

	/** 'customer-logout' is always the last item after the insert. */
	public function test_customer_logout_remains_last() {
		$items = array(
			'wps_subscriptions' => 'Subscriptions',
			'customer-logout'   => 'Logout',
		);

		$result = $this->tab->add_menu_item( $items );
		$keys   = array_keys( $result );

		$this->assertSame( 'customer-logout', end( $keys ) );
	}

	/** The label for the new item is the translated string 'Memberships'. */
	public function test_menu_item_label_is_memberships() {
		$items  = array( 'customer-logout' => 'Logout' );
		$result = $this->tab->add_menu_item( $items );

		$this->assertArrayHasKey( 'wps_memberships', $result );
		$this->assertSame( 'Memberships', $result['wps_memberships'] );
	}

	/** Existing items are not removed or reordered (beyond the insertion). */
	public function test_existing_items_are_preserved() {
		$items = array(
			'dashboard'        => 'Dashboard',
			'wps_subscriptions' => 'Subscriptions',
			'customer-logout'  => 'Logout',
		);

		$result = $this->tab->add_menu_item( $items );

		$this->assertArrayHasKey( 'dashboard', $result );
		$this->assertArrayHasKey( 'wps_subscriptions', $result );
		$this->assertArrayHasKey( 'customer-logout', $result );
	}

	// -----------------------------------------------------------------------
	// render_tab
	// -----------------------------------------------------------------------

	/** Empty-state notice is shown when the user has no memberships. */
	public function test_render_tab_shows_empty_state_for_user_with_no_memberships() {
		wp_set_current_user( $this->user_id );

		ob_start();
		$this->tab->render_tab();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'wps-myaccount-empty', $html );
		$this->assertStringNotContainsString( '<table', $html );
	}

	/** Membership table is rendered for a user who has memberships. */
	public function test_render_tab_shows_table_for_user_with_memberships() {
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'silver' ) );
		wp_set_current_user( $this->user_id );

		ob_start();
		$this->tab->render_tab();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'wps-membership-cards', $html );
		$this->assertStringNotContainsString( 'wps-myaccount-empty', $html );
	}

	/** Each membership row contains the plan slug (or name) and status. */
	public function test_render_tab_row_contains_plan_and_status() {
		wps_create_user_membership(
			$this->user_id,
			array(
				'plan_slug' => 'gold',
				'status'    => 'active',
			)
		);
		wp_set_current_user( $this->user_id );

		ob_start();
		$this->tab->render_tab();
		$html = ob_get_clean();

		// Plan slug/name and active status badge should be present.
		$this->assertStringContainsString( 'Gold', $html );
		$this->assertStringContainsString( 'Active', $html );
	}

	/** A membership with no expiry date shows "Lifetime". */
	public function test_render_tab_shows_lifetime_when_no_expiry() {
		wps_create_user_membership(
			$this->user_id,
			array(
				'plan_slug'   => 'platinum',
				'expiry_date' => null,
			)
		);
		wp_set_current_user( $this->user_id );

		ob_start();
		$this->tab->render_tab();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Lifetime', $html );
	}

	/** Manually-granted membership shows "Manual" as source. */
	public function test_render_tab_shows_manual_source() {
		wps_create_user_membership(
			$this->user_id,
			array(
				'plan_slug' => 'bronze',
				'source'    => 'manual',
			)
		);
		wp_set_current_user( $this->user_id );

		ob_start();
		$this->tab->render_tab();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Manual', $html );
	}
}
