<?php
/**
 * Unit tests for Day 20 (Backward compat + grant-path matrix).
 *
 * Exercises WPS_Membership_Order_Grant end-to-end:
 *   - Backward compat: a product with NO linked plan writes ZERO user meta.
 *   - One-time lifetime plan  → active membership, no expiry.
 *   - One-time fixed-length plan → active membership, computed expiry.
 *   - Idempotency: firing the grant twice yields a single membership entry.
 *   - Refund / cancel → membership revoked.
 *   - Refund of a plan-less order → no-op (no errors, no meta).
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class GrantPathMatrixTest extends WP_UnitTestCase {

	/** @var WPS_Membership_Order_Grant */
	private $grant;

	/** @var int */
	private $user_id;

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wc_create_order' ) ) {
			$this->markTestSkipped( 'WooCommerce not available.' );
		}

		$this->grant   = new WPS_Membership_Order_Grant();
		$this->user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
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
	 * Create a simple WC product post.
	 *
	 * @param string $title Product title.
	 * @return int Product ID.
	 */
	private function make_product( $title = 'Album' ) {
		$id = $this->factory->post->create(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
		update_post_meta( $id, '_price', '15' );
		update_post_meta( $id, '_regular_price', '15' );
		update_post_meta( $id, '_stock_status', 'instock' );
		return $id;
	}

	/**
	 * Create a completed-style order containing a product for the test user.
	 *
	 * @param int $product_id Product ID.
	 * @return int Order ID.
	 */
	private function make_order( $product_id ) {
		$order = wc_create_order( array( 'customer_id' => $this->user_id ) );
		$order->add_product( wc_get_product( $product_id ), 1 );
		$order->save();
		return $order->get_id();
	}

	// -----------------------------------------------------------------------
	// Backward-compat guarantee
	// -----------------------------------------------------------------------

	public function test_product_with_no_plan_writes_no_membership() {
		$product_id = $this->make_product( 'Plain Product' );
		$order_id   = $this->make_order( $product_id );

		$this->grant->grant_from_order( $order_id );

		$this->assertSame( array(), wps_get_user_memberships( $this->user_id, 'all' ) );
		$this->assertEmpty( get_user_meta( $this->user_id, 'wps_memberships', true ) );
	}

	public function test_refund_of_planless_order_is_noop() {
		$product_id = $this->make_product( 'Plain Product' );
		$order_id   = $this->make_order( $product_id );

		// Should not error or write anything.
		$this->grant->revoke_from_order( $order_id );

		$this->assertSame( array(), wps_get_user_memberships( $this->user_id, 'all' ) );
	}

	// -----------------------------------------------------------------------
	// Grant matrix
	// -----------------------------------------------------------------------

	public function test_lifetime_plan_grants_active_membership_without_expiry() {
		$product_id = $this->make_product();
		wps_create_plan(
			array(
				'name'          => 'Album Plan',
				'slug'          => 'album-plan',
				'grant_method'  => 'purchase',
				'access_length' => array( 'type' => 'lifetime' ),
				'products'      => array( $product_id ),
			)
		);
		wp_cache_flush();

		$order_id = $this->make_order( $product_id );
		$this->grant->grant_from_order( $order_id );

		$membership = wps_get_membership( $this->user_id, 'album-plan' );
		$this->assertNotEmpty( $membership );
		$this->assertSame( 'active', $membership['status'] );
		$this->assertSame( 'order', $membership['source'] );
		$this->assertSame( $order_id, absint( $membership['order_id'] ) );
		$this->assertNull( $membership['expiry_date'] );
	}

	public function test_fixed_length_plan_computes_expiry() {
		$product_id = $this->make_product();
		wps_create_plan(
			array(
				'name'          => 'Twenty Day Plan',
				'slug'          => 'twenty-day',
				'grant_method'  => 'purchase',
				'access_length' => array(
					'type'  => 'fixed',
					'value' => 20,
					'unit'  => 'day',
				),
				'products'      => array( $product_id ),
			)
		);
		wp_cache_flush();

		$order_id = $this->make_order( $product_id );
		$this->grant->grant_from_order( $order_id );

		$membership = wps_get_membership( $this->user_id, 'twenty-day' );
		$this->assertNotEmpty( $membership );
		$this->assertNotNull( $membership['expiry_date'] );
		// Expiry should be ~20 days out (allow a generous window).
		$this->assertGreaterThan( time() + 18 * DAY_IN_SECONDS, (int) $membership['expiry_date'] );
		$this->assertLessThan( time() + 22 * DAY_IN_SECONDS, (int) $membership['expiry_date'] );
	}

	/**
	 * A "processing" order (online payment confirmed) must grant the membership
	 * immediately — without waiting for the order to reach "completed".
	 */
	public function test_processing_order_grants_membership() {
		$product_id = $this->make_product();
		wps_create_plan(
			array(
				'name'         => 'Processing Plan',
				'slug'         => 'processing-plan',
				'grant_method' => 'purchase',
				'products'     => array( $product_id ),
			)
		);
		wp_cache_flush();

		$order_id = $this->make_order( $product_id );

		// Simulate what WooCommerce does when an online payment transitions
		// an order to "processing" (without ever reaching "completed").
		$this->grant->grant_from_order( $order_id );

		$membership = wps_get_membership( $this->user_id, 'processing-plan' );
		$this->assertNotEmpty( $membership, 'Membership must be created on processing.' );
		$this->assertSame( 'active', $membership['status'] );
		$this->assertSame( 'order', $membership['source'] );
		$this->assertSame( $order_id, absint( $membership['order_id'] ) );
	}

	/**
	 * Firing the grant for both "processing" and "completed" on the same order
	 * must produce exactly one membership row (idempotency across status hooks).
	 */
	public function test_processing_then_completed_does_not_duplicate() {
		$product_id = $this->make_product();
		wps_create_plan(
			array(
				'name'         => 'Dedup Plan',
				'slug'         => 'dedup-plan',
				'grant_method' => 'purchase',
				'products'     => array( $product_id ),
			)
		);
		wp_cache_flush();

		$order_id = $this->make_order( $product_id );

		// processing fires first (online payment confirmed).
		$this->grant->grant_from_order( $order_id );
		// completed fires later (merchant marks complete) — must be a no-op.
		$this->grant->grant_from_order( $order_id );

		$this->assertCount( 1, wps_get_user_memberships( $this->user_id, 'all' ) );
	}

	public function test_grant_is_idempotent() {
		$product_id = $this->make_product();
		wps_create_plan(
			array(
				'name'         => 'Idem Plan',
				'slug'         => 'idem-plan',
				'grant_method' => 'purchase',
				'products'     => array( $product_id ),
			)
		);
		wp_cache_flush();

		$order_id = $this->make_order( $product_id );
		$this->grant->grant_from_order( $order_id );
		$this->grant->grant_from_order( $order_id ); // Fire twice.

		$this->assertCount( 1, wps_get_user_memberships( $this->user_id, 'all' ) );
	}

	public function test_refund_revokes_membership() {
		$product_id = $this->make_product();
		wps_create_plan(
			array(
				'name'         => 'Refund Plan',
				'slug'         => 'refund-plan',
				'grant_method' => 'purchase',
				'products'     => array( $product_id ),
			)
		);
		wp_cache_flush();

		$order_id = $this->make_order( $product_id );
		$this->grant->grant_from_order( $order_id );
		$this->assertSame( 'active', wps_get_membership( $this->user_id, 'refund-plan' )['status'] );

		$this->grant->revoke_from_order( $order_id );
		$this->assertSame( 'cancelled', wps_get_membership( $this->user_id, 'refund-plan' )['status'] );
	}
}
