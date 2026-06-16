<?php
/**
 * Unit tests for WPS_Product_Badge::render_product_page_plans()
 *
 * Verifies that the "MEMBERSHIP REQUIRED" gate panel is suppressed for users
 * who already hold a plan that satisfies every access rule on the product.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for WPS_Product_Badge gate-panel visibility.
 *
 * @since 2.0.0
 */
class ProductBadgeTest extends WP_UnitTestCase {

	/**
	 * The badge instance under test.
	 *
	 * @var WPS_Product_Badge
	 */
	private $badge;

	/**
	 * WC product post ID used as the restricted product.
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * User who holds the required plan.
	 *
	 * @var int
	 */
	private $member_id;

	/**
	 * User with no membership.
	 *
	 * @var int
	 */
	private $guest_id;

	// -----------------------------------------------------------------------
	// Lifecycle
	// -----------------------------------------------------------------------

	/**
	 * Sets up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->badge = new WPS_Product_Badge();

		// Create a plain WC product (simple post of type 'product').
		$this->product_id = $this->factory->post->create(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => 'Restricted Product',
			)
		);
		// Make WooCommerce treat it as a simple product.
		update_post_meta( $this->product_id, '_visibility', 'visible' );
		update_post_meta( $this->product_id, '_stock_status', 'instock' );

		$this->member_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->guest_id  = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		wps_create_user_membership( $this->member_id, array( 'plan_slug' => 'gate-plan' ) );

		// Save an access rule that requires 'gate-plan' for our product.
		wps_save_access_rules(
			array(
				array(
					'id'           => 'r_gate_test',
					'target_type'  => 'product',
					'object_ids'   => array( $this->product_id ),
					'taxonomy'     => '',
					'term_ids'     => array(),
					'post_type'    => '',
					'plans'        => array( 'gate-plan' ),
					'behavior'     => 'message',
					'message'      => '',
					'redirect_url' => '',
					'priority'     => 10,
					'enabled'      => '1',
				),
			)
		);
		wp_cache_flush();
	}

	/**
	 * Tears down test fixtures.
	 */
	public function tearDown(): void {
		wp_delete_post( $this->product_id, true );
		if ( $this->member_id ) {
			wp_delete_user( $this->member_id );
		}
		if ( $this->guest_id ) {
			wp_delete_user( $this->guest_id );
		}
		delete_option( WPS_ACCESS_RULES_OPTION );
		delete_option( WPS_ACCESS_RULES_INDEX_OPTION );
		wp_cache_flush();
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Capture the output of render_product_page_plans() with a given product
	 * set as the global WC product and the specified user active.
	 *
	 * @param  int $user_id  0 for guest.
	 * @return string Captured HTML output.
	 */
	private function capture_gate( $user_id ) {
		global $product;

		wp_set_current_user( $user_id );

		// Simulate the WC global $product.
		$wc_product = wc_get_product( $this->product_id );
		if ( ! $wc_product ) {
			// Fallback: create a stub so the method can run in test context.
			$wc_product = new WC_Product_Simple( $this->product_id );
		}
		$product = $wc_product;

		ob_start();
		$this->badge->render_product_page_plans();
		$html = ob_get_clean();

		$product = null;
		return $html;
	}

	// -----------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------

	/** Guest sees the MEMBERSHIP REQUIRED gate panel. */
	public function test_gate_shown_for_guest() {
		$html = $this->capture_gate( 0 );
		$this->assertStringContainsString( 'wps-membership-gate', $html );
	}

	/** Logged-in user without the plan sees the gate panel. */
	public function test_gate_shown_for_non_member() {
		$html = $this->capture_gate( $this->guest_id );
		$this->assertStringContainsString( 'wps-membership-gate', $html );
	}

	/** User who already holds the required plan does NOT see the gate panel. */
	public function test_gate_hidden_for_member_who_has_the_plan() {
		$html = $this->capture_gate( $this->member_id );
		$this->assertStringNotContainsString( 'wps-membership-gate', $html );
	}
}
