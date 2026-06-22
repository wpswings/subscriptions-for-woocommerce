<?php
/**
 * Unit tests for kind-aware enforcement in WPS_Restriction_Enforcer.
 *
 * Verifies the Content/Product render paths stay separate:
 *   - A product-kind rule never alters the_content (the product page stays intact).
 *   - A product-kind rule gates purchasability for non-members only.
 *   - A content-kind rule never gates product purchasability.
 *   - The product-page members-only notice renders for blocked visitors.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for product vs content enforcement separation.
 *
 * @since 2.0.0
 */
class RestrictionEnforcerKindTest extends WP_UnitTestCase {

	/**
	 * Enforcer under test.
	 *
	 * @var WPS_Restriction_Enforcer
	 */
	private $enforcer;

	/**
	 * Product post that a product rule targets.
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * User holding the required plan.
	 *
	 * @var int
	 */
	private $member_id;

	/**
	 * User with no membership.
	 *
	 * @var int
	 */
	private $stranger_id;

	/**
	 * Set up test fixtures before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->enforcer = new WPS_Restriction_Enforcer();

		$this->product_id = $this->factory->post->create(
			array(
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_title'   => 'Gated Product',
				'post_content' => 'Full product description.',
			)
		);
		update_post_meta( $this->product_id, '_visibility', 'visible' );
		update_post_meta( $this->product_id, '_stock_status', 'instock' );
		update_post_meta( $this->product_id, '_price', '10' );
		update_post_meta( $this->product_id, '_regular_price', '10' );

		$this->member_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->stranger_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		wps_create_user_membership( $this->member_id, array( 'plan_slug' => 'gold' ) );

		$this->add_product_rule();
		wp_cache_flush();
	}

	/**
	 * Tear down test fixtures after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_delete_post( $this->product_id, true );
		if ( $this->member_id ) {
			wp_delete_user( $this->member_id );
		}
		if ( $this->stranger_id ) {
			wp_delete_user( $this->stranger_id );
		}
		delete_option( WPS_ACCESS_RULES_OPTION );
		delete_option( WPS_ACCESS_RULES_INDEX_OPTION );
		wp_cache_flush();
		parent::tearDown();
	}

	/**
	 * Save a product-kind rule requiring the 'gold' plan for the product.
	 *
	 * @param array $overrides Optional rule field overrides.
	 * @return void
	 */
	private function add_product_rule( array $overrides = array() ) {
		$rule = array_merge(
			array(
				'id'          => 'r_prod',
				'rule_kind'   => 'product',
				'target_type' => 'product',
				'object_ids'  => array( $this->product_id ),
				'plans'       => array( 'gold' ),
				'message'     => 'Members can buy this.',
				'priority'    => 10,
			),
			$overrides
		);
		wps_save_access_rules( array( $rule ) );
		wp_cache_flush();
	}

	/**
	 * Verify a product rule does not replace post content for a guest user.
	 *
	 * @return void
	 */
	public function test_product_rule_does_not_replace_content_for_guest() {
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->product_id ) );

		$result = $this->enforcer->maybe_restrict_content( 'Full product description.' );

		$this->assertSame( 'Full product description.', $result );
	}

	/**
	 * Verify a product rule does not trigger a redirect.
	 *
	 * @return void
	 */
	public function test_product_rule_does_not_redirect() {
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $this->product_id ) );

		// maybe_redirect() must not exit/redirect for a product rule. If it does
		// not act, execution returns here normally.
		$this->enforcer->maybe_redirect();
		$this->assertTrue( true );
	}

	/**
	 * Verify a product rule blocks purchase for a non-member.
	 *
	 * @return void
	 */
	public function test_product_rule_blocks_purchase_for_non_member() {
		wp_set_current_user( $this->stranger_id );
		$product = wc_get_product( $this->product_id );

		$this->assertFalse( $this->enforcer->maybe_restrict_purchasability( true, $product ) );
	}

	/**
	 * Verify a product rule allows purchase for an active member.
	 *
	 * @return void
	 */
	public function test_product_rule_allows_purchase_for_member() {
		wp_set_current_user( $this->member_id );
		$product = wc_get_product( $this->product_id );

		$this->assertTrue( $this->enforcer->maybe_restrict_purchasability( true, $product ) );
	}

	/**
	 * Reproduces the reported scenario: an "All Products" rule
	 * (target_type=post_type, post_type=product) blocks a member whose membership
	 * has been CANCELLED — cancelled is not active, so access ends.
	 */
	public function test_all_products_rule_blocks_cancelled_member() {
		$this->add_product_rule(
			array(
				'id'          => 'r_all_products',
				'target_type' => 'post_type',
				'post_type'   => 'product',
				'object_ids'  => array(),
			)
		);
		wps_update_membership_status( $this->member_id, 'gold', 'cancelled' );
		wp_cache_flush();

		wp_set_current_user( $this->member_id );
		$product = wc_get_product( $this->product_id );

		$this->assertFalse( $this->enforcer->maybe_restrict_purchasability( true, $product ) );
	}

	/**
	 * Admins (manage_options) bypass every access rule by design — purchase is
	 * never blocked for them. This is why testing as admin shows no restriction.
	 */
	public function test_admin_bypasses_product_rule() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$product = wc_get_product( $this->product_id );

		$this->assertTrue( $this->enforcer->maybe_restrict_purchasability( true, $product ) );

		wp_delete_user( $admin_id );
	}

	/**
	 * Verify a content-kind rule does not block product purchase.
	 *
	 * @return void
	 */
	public function test_content_rule_does_not_block_product_purchase() {
		// Replace the product rule with a content-kind rule that (incorrectly)
		// references the product ID. Content rules must never gate purchase.
		wps_save_access_rules(
			array(
				array(
					'id'          => 'r_content',
					'rule_kind'   => 'content',
					'target_type' => 'post',
					'object_ids'  => array( $this->product_id ),
					'plans'       => array( 'gold' ),
					'behavior'    => 'message',
					'priority'    => 10,
				),
			)
		);
		wp_cache_flush();

		wp_set_current_user( $this->stranger_id );
		$product = wc_get_product( $this->product_id );

		$this->assertTrue( $this->enforcer->maybe_restrict_purchasability( true, $product ) );
	}

	/**
	 * Verify the product gate notice contains the rule message and wrapper element.
	 *
	 * @return void
	 */
	public function test_product_gate_notice_contains_message_and_card() {
		$rule = array(
			'rule_kind'   => 'product',
			'target_type' => 'product',
			'object_ids'  => array( $this->product_id ),
			'plans'       => array( 'gold' ),
			'message'     => 'Members can buy this.',
		);

		$html = $this->enforcer->render_product_gate_notice( $rule );

		$this->assertStringContainsString( 'Members can buy this.', $html );
		$this->assertStringContainsString( 'wps-restricted-content', $html );
	}
}
