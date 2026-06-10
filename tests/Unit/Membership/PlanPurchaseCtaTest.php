<?php
/**
 * Unit tests for the plan purchase-CTA product resolution.
 *
 * Covers wps_get_plan_purchasable_products() across all grant methods
 * (purchase / subscription / auto_enroll) and the rendered CTA — ensuring
 * subscription-granted plans surface their products, not just one-time plans.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class PlanPurchaseCtaTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			$this->markTestSkipped( 'WooCommerce not available.' );
		}
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/** Create a published, in-stock simple product and return its ID. */
	private function make_product( $name, $price ) {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( (string) $price );
		$product->set_status( 'publish' );
		$product->set_stock_status( 'instock' );
		return $product->save();
	}

	/** Create a plan with the given grant method + linked product IDs. */
	private function make_plan( $slug, $grant_method, array $product_ids ) {
		$plan_id = wps_create_plan(
			array(
				'name'         => ucfirst( $slug ),
				'slug'         => $slug,
				'grant_method' => $grant_method,
			)
		);

		if ( 'subscription' === $grant_method ) {
			update_post_meta( $plan_id, '_wps_plan_sub_products', array_map( 'absint', $product_ids ) );
		} else {
			update_post_meta( $plan_id, '_wps_plan_products', array_map( 'absint', $product_ids ) );
		}

		return $plan_id;
	}

	// -----------------------------------------------------------------------
	// wps_get_plan_purchasable_products()
	// -----------------------------------------------------------------------

	public function test_subscription_plan_surfaces_subscription_products() {
		$pid = $this->make_product( 'Subscription Test', 100 );
		$this->make_plan( 'sub-plan', 'subscription', array( $pid ) );

		$products = wps_get_plan_purchasable_products( 'sub-plan' );

		$this->assertCount( 1, $products );
		$this->assertSame( $pid, $products[0]->get_id() );
	}

	public function test_purchase_plan_surfaces_one_time_products() {
		$pid = $this->make_product( 'Album', 15 );
		$this->make_plan( 'buy-plan', 'purchase', array( $pid ) );

		$products = wps_get_plan_purchasable_products( 'buy-plan' );

		$this->assertCount( 1, $products );
		$this->assertSame( $pid, $products[0]->get_id() );
	}

	public function test_auto_enroll_plan_has_no_purchasable_products() {
		$this->make_plan( 'auto-plan', 'auto_enroll', array() );
		$this->assertSame( array(), wps_get_plan_purchasable_products( 'auto-plan' ) );
	}

	public function test_unpublished_product_is_excluded() {
		$pid     = $this->make_product( 'Draft Sub', 50 );
		$product = wc_get_product( $pid );
		$product->set_status( 'draft' );
		$product->save();

		$this->make_plan( 'draft-plan', 'subscription', array( $pid ) );

		$this->assertSame( array(), wps_get_plan_purchasable_products( 'draft-plan' ) );
	}

	// -----------------------------------------------------------------------
	// wps_render_plan_purchase_cta()
	// -----------------------------------------------------------------------

	public function test_cta_renders_subscription_plan_product() {
		$pid = $this->make_product( 'Subscription Test', 100 );
		$this->make_plan( 'sub-plan', 'subscription', array( $pid ) );

		$html = wps_render_plan_purchase_cta( array( 'sub-plan' ) );

		$this->assertNotSame( '', $html );
		$this->assertStringContainsString( 'Subscription Test', $html );
	}

	public function test_cta_renders_multiple_plans_across_methods() {
		$sub = $this->make_product( 'Subscription Test', 100 );
		$buy = $this->make_product( 'Album', 15 );
		$this->make_plan( 'sub-plan', 'subscription', array( $sub ) );
		$this->make_plan( 'buy-plan', 'purchase', array( $buy ) );

		$html = wps_render_plan_purchase_cta( array( 'sub-plan', 'buy-plan' ) );

		$this->assertStringContainsString( 'Subscription Test', $html );
		$this->assertStringContainsString( 'Album', $html );
	}
}
