<?php
/**
 * Unit tests for membership-state-aware grant card rendering.
 *
 * wps_build_membership_card_html() must adapt to the current user:
 *   - Non-member / guest → "MEMBERSHIP INCLUDED" offer state (purchase / sub copy).
 *   - Member who holds the plan → "MEMBERSHIP ACTIVE" state with a "✓ Active" badge.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for wps_build_membership_card_html() membership-state awareness.
 *
 * @since 2.0.0
 */
class MembershipCardRenderTest extends WP_UnitTestCase {

	/** @var int Member who holds the 'gold' plan. */
	private $member_id;

	/** @var int Logged-in user with no membership. */
	private $guest_id;

	public function setUp(): void {
		parent::setUp();

		$this->member_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->guest_id  = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		wps_create_user_membership( $this->member_id, array( 'plan_slug' => 'gold' ) );
		wp_cache_flush();
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		if ( $this->member_id ) {
			wp_delete_user( $this->member_id );
		}
		if ( $this->guest_id ) {
			wp_delete_user( $this->guest_id );
		}
		wp_cache_flush();
		parent::tearDown();
	}

	/**
	 * A 'gold' plan row for the card builder.
	 *
	 * @param array $overrides Keys to override.
	 * @return array
	 */
	private function gold_plan( array $overrides = array() ) {
		return array_merge(
			array(
				'slug'          => 'gold',
				'name'          => 'Gold',
				'description'   => 'Gold plan description',
				'color'         => '#123456',
				'grant_method'  => 'purchase',
				'access_length' => array(
					'type'  => 'fixed',
					'value' => 20,
					'unit'  => 'day',
				),
			),
			$overrides
		);
	}

	// -----------------------------------------------------------------------
	// Offer state (no active membership)
	// -----------------------------------------------------------------------

	public function test_offer_state_for_guest() {
		wp_set_current_user( 0 );

		$html = wps_build_membership_card_html( array( $this->gold_plan() ) );

		$this->assertStringContainsString( 'MEMBERSHIP INCLUDED', $html );
		$this->assertStringContainsString( 'Purchasing this product', $html );
		$this->assertStringContainsString( '20 Days Access', $html );
		$this->assertStringNotContainsString( 'MEMBERSHIP ACTIVE', $html );
	}

	public function test_offer_state_for_logged_in_non_member() {
		wp_set_current_user( $this->guest_id );

		$html = wps_build_membership_card_html( array( $this->gold_plan() ) );

		$this->assertStringContainsString( 'MEMBERSHIP INCLUDED', $html );
		$this->assertStringNotContainsString( 'wps-membership-included--active', $html );
	}

	public function test_subscription_offer_subtitle_and_badge() {
		wp_set_current_user( 0 );

		$html = wps_build_membership_card_html(
			array( $this->gold_plan( array( 'grant_method' => 'subscription' ) ) ),
			'subscription'
		);

		$this->assertStringContainsString( 'Active subscription grants', $html );
		$this->assertStringContainsString( 'While Subscribed', $html );
	}

	// -----------------------------------------------------------------------
	// Active state (member already holds the plan)
	// -----------------------------------------------------------------------

	public function test_active_state_for_member() {
		wp_set_current_user( $this->member_id );

		$html = wps_build_membership_card_html( array( $this->gold_plan() ) );

		$this->assertStringContainsString( 'MEMBERSHIP ACTIVE', $html );
		$this->assertStringContainsString( 'wps-membership-included--active', $html );
		$this->assertStringContainsString( 'wps-membership-included__badge--active', $html );
		$this->assertStringNotContainsString( 'Purchasing this product', $html );
	}

	public function test_active_state_even_for_subscription_grant() {
		wp_set_current_user( $this->member_id );

		$html = wps_build_membership_card_html(
			array( $this->gold_plan( array( 'grant_method' => 'subscription' ) ) ),
			'subscription'
		);

		// Active state wins over the "While Subscribed" offer badge.
		$this->assertStringContainsString( 'MEMBERSHIP ACTIVE', $html );
		$this->assertStringContainsString( 'wps-membership-included__badge--active', $html );
		$this->assertStringNotContainsString( 'While Subscribed', $html );
	}
}
