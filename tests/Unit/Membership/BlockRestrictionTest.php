<?php
/**
 * Unit tests for Day 17 deliverables (Pro — Gutenberg block restriction).
 *
 * Covers:
 *   Free:  WPS_Membership_Block_Editor::add_block_attributes()
 *   Pro:   WPS_Block_Restriction::filter_render_block()
 *
 * The Pro plugin is not loaded by the Free test bootstrap, so the enforcement
 * class file is required directly — it depends only on Free membership functions.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for Gutenberg block restriction (Free attribute registration + Pro enforcement).
 */
class BlockRestrictionTest extends WP_UnitTestCase {

	/** @var int */
	private $user_id;

	/**
	 * Load the Pro block-restriction class once before any test in this suite runs.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$pro_class = dirname( __DIR__, 4 )
			. '/woocommerce-subscriptions-pro/includes/pro/class-wps-block-restriction.php';
		if ( file_exists( $pro_class ) ) {
			require_once $pro_class;
		}
	}

	/**
	 * Create a fresh subscriber user before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		wp_cache_flush();
		$this->user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Build a parsed-block array with the given membership attributes.
	 *
	 * @param array $attrs Block attributes to embed.
	 * @return array
	 */
	private function make_block( array $attrs = array() ) {
		return array(
			'blockName'    => 'core/paragraph',
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '<p>Secret</p>',
			'innerContent' => array( '<p>Secret</p>' ),
		);
	}

	/**
	 * Render a block through the Pro filter for the current user.
	 *
	 * @param array $attrs Block attributes to pass to the restriction filter.
	 * @return string
	 */
	private function render( array $attrs ) {
		$pro = new WPS_Block_Restriction();
		return $pro->filter_render_block( '<p>Secret</p>', $this->make_block( $attrs ) );
	}

	// -----------------------------------------------------------------------
	// Free — attribute registration
	// -----------------------------------------------------------------------

	/**
	 * Free plugin registers wpsRestricted and wpsRequiredPlans attributes on blocks.
	 *
	 * @return void
	 */
	public function test_editor_adds_membership_attributes() {
		$editor = new WPS_Membership_Block_Editor();
		$args   = $editor->add_block_attributes( array(), 'core/paragraph' );

		$this->assertArrayHasKey( 'wpsRestricted', $args['attributes'] );
		$this->assertArrayHasKey( 'wpsRequiredPlans', $args['attributes'] );
		$this->assertSame( 'boolean', $args['attributes']['wpsRestricted']['type'] );
		$this->assertFalse( $args['attributes']['wpsRestricted']['default'] );
		$this->assertSame( 'array', $args['attributes']['wpsRequiredPlans']['type'] );
	}

	/**
	 * Free plugin preserves any attributes the block already declares.
	 *
	 * @return void
	 */
	public function test_editor_preserves_existing_attributes() {
		$editor = new WPS_Membership_Block_Editor();
		$args   = $editor->add_block_attributes(
			array( 'attributes' => array( 'content' => array( 'type' => 'string' ) ) ),
			'core/paragraph'
		);

		$this->assertArrayHasKey( 'content', $args['attributes'] );
		$this->assertArrayHasKey( 'wpsRestricted', $args['attributes'] );
	}

	// -----------------------------------------------------------------------
	// Pro — render_block enforcement
	// -----------------------------------------------------------------------

	/**
	 * A block without the wpsRestricted flag passes through unchanged.
	 *
	 * @return void
	 */
	public function test_unrestricted_block_passes_through() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wp_set_current_user( $this->user_id );
		$out = $this->render( array() );
		$this->assertSame( '<p>Secret</p>', $out );
	}

	/**
	 * A restricted block is hidden for unauthenticated (guest) visitors.
	 *
	 * @return void
	 */
	public function test_restricted_block_blocked_for_guest() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wp_set_current_user( 0 );
		$out = $this->render( array( 'wpsRestricted' => true ) );
		$this->assertStringNotContainsString( 'Secret', $out );
		$this->assertStringContainsString( 'wps-restricted-block', $out );
	}

	/**
	 * A restricted block is hidden for a logged-in user who holds no membership.
	 *
	 * @return void
	 */
	public function test_restricted_block_blocked_for_non_member() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wp_set_current_user( $this->user_id );
		$out = $this->render( array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold' ) ) );
		$this->assertStringNotContainsString( 'Secret', $out );
	}

	/**
	 * A restricted block is visible to a member who holds the required plan.
	 *
	 * @return void
	 */
	public function test_restricted_block_visible_to_member_with_plan() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		wp_set_current_user( $this->user_id );

		$out = $this->render( array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold' ) ) );
		$this->assertSame( '<p>Secret</p>', $out );
	}

	/**
	 * A member who holds a different plan than required is still blocked.
	 *
	 * @return void
	 */
	public function test_restricted_block_member_with_wrong_plan_blocked() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'silver' ) );
		wp_set_current_user( $this->user_id );

		$out = $this->render( array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold' ) ) );
		$this->assertStringNotContainsString( 'Secret', $out );
	}

	/**
	 * When no required plans are set, any active membership grants access.
	 *
	 * @return void
	 */
	public function test_any_plan_grants_member_with_any_membership() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'bronze' ) );
		wp_set_current_user( $this->user_id );

		// No required plans → "any active membership".
		$out = $this->render( array( 'wpsRestricted' => true ) );
		$this->assertSame( '<p>Secret</p>', $out );
	}

	/**
	 * The {purchase_options} merge tag in the access message is replaced before output.
	 *
	 * @return void
	 */
	public function test_notice_replaces_purchase_options_merge_tag() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		update_option( 'wps_access_wrong_plan_message', 'Upgrade now {purchase_options}' );
		wp_set_current_user( 0 );

		$out = $this->render( array( 'wpsRestricted' => true ) );

		// The literal merge tag must never reach the page.
		$this->assertStringNotContainsString( '{purchase_options}', $out );
		$this->assertStringContainsString( 'Upgrade now', $out );

		delete_option( 'wps_access_wrong_plan_message' );
	}

	/**
	 * The restriction notice uses the shared card markup CSS classes.
	 *
	 * @return void
	 */
	public function test_notice_uses_shared_card_markup() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wp_set_current_user( 0 );
		$out = $this->render( array( 'wpsRestricted' => true ) );

		$this->assertStringContainsString( 'wps-restricted-content__head', $out );
		$this->assertStringContainsString( 'wps-restricted-content__msg', $out );
		$this->assertStringContainsString( 'wps-restricted-block', $out );
	}

	// -----------------------------------------------------------------------
	// Pro — multiple required plans (OR / match-any semantics)
	// -----------------------------------------------------------------------

	/**
	 * A member matching the first of multiple required plans is granted access.
	 *
	 * @return void
	 */
	public function test_multi_plan_member_with_first_plan_granted() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		wp_set_current_user( $this->user_id );

		$out = $this->render(
			array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold', 'silver' ) )
		);
		$this->assertSame( '<p>Secret</p>', $out );
	}

	/**
	 * A member matching the second of multiple required plans is granted access.
	 *
	 * @return void
	 */
	public function test_multi_plan_member_with_second_plan_granted() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'silver' ) );
		wp_set_current_user( $this->user_id );

		$out = $this->render(
			array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold', 'silver' ) )
		);
		$this->assertSame( '<p>Secret</p>', $out );
	}

	/**
	 * A member who holds all of the required plans is granted access.
	 *
	 * @return void
	 */
	public function test_multi_plan_member_with_all_plans_granted() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'gold' ) );
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'silver' ) );
		wp_set_current_user( $this->user_id );

		$out = $this->render(
			array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold', 'silver' ) )
		);
		$this->assertSame( '<p>Secret</p>', $out );
	}

	/**
	 * A member whose plan is not in the required list is blocked.
	 *
	 * @return void
	 */
	public function test_multi_plan_member_with_unrelated_plan_blocked() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		wps_create_user_membership( $this->user_id, array( 'plan_slug' => 'bronze' ) );
		wp_set_current_user( $this->user_id );

		$out = $this->render(
			array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold', 'silver' ) )
		);
		$this->assertStringNotContainsString( 'Secret', $out );
	}

	/**
	 * A member whose required plan has expired is blocked, not granted access.
	 *
	 * @return void
	 */
	public function test_multi_plan_member_with_expired_required_plan_blocked() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		// Holds a required plan, but it has expired → must be blocked.
		wps_create_user_membership(
			$this->user_id,
			array(
				'plan_slug'   => 'gold',
				'expiry_date' => time() - DAY_IN_SECONDS,
			)
		);
		wp_set_current_user( $this->user_id );

		$out = $this->render(
			array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold', 'silver' ) )
		);
		$this->assertStringNotContainsString( 'Secret', $out );
	}

	/**
	 * Administrators bypass block restriction and always see the content.
	 *
	 * @return void
	 */
	public function test_admin_bypasses_block_restriction() {
		if ( ! class_exists( 'WPS_Block_Restriction' ) ) {
			$this->markTestSkipped( 'Pro plugin not present.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$out = $this->render( array( 'wpsRestricted' => true, 'wpsRequiredPlans' => array( 'gold' ) ) );
		$this->assertSame( '<p>Secret</p>', $out );
	}
}
