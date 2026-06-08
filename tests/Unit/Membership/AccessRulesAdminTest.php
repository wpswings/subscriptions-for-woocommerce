<?php
/**
 * Unit tests for Day 12 deliverables:
 *   admin/membership/class-wps-access-rules-admin.php
 *
 * Covers: tab registration, underlying search queries (post / term),
 * global-defaults persistence, and full rules-save round-trip via
 * reflection on the private helper methods.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Unit tests for WPS_Access_Rules_Admin.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */
class AccessRulesAdminTest extends WP_UnitTestCase {

	/**
	 * Subject under test.
	 *
	 * @var WPS_Access_Rules_Admin
	 */
	private $admin;

	/**
	 * Reset options and the object cache before each test.
	 *
	 * @since 2.0.0
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( WPS_ACCESS_RULES_OPTION );
		delete_option( WPS_ACCESS_RULES_INDEX_OPTION );
		delete_option( 'wps_access_default_behavior' );
		delete_option( 'wps_access_logged_out_message' );
		delete_option( 'wps_access_wrong_plan_message' );
		delete_option( 'wps_access_redirect_url' );
		delete_option( 'wps_access_restrict_comments' );
		delete_option( 'wps_access_include_in_archive' );
		delete_option( 'wps_access_show_purchase_cta' );
		wp_cache_flush();
		$this->admin = new WPS_Access_Rules_Admin();
	}

	// -----------------------------------------------------------------------
	// register_tab()
	// -----------------------------------------------------------------------

	/**
	 * Tab registration adds the expected array key.
	 *
	 * @since 2.0.0
	 */
	public function test_register_tab_adds_access_rules_entry() {
		$result = $this->admin->register_tab( array() );
		$this->assertArrayHasKey( 'wps-membership-access-rules', $result );
	}

	/**
	 * Tab entry contains title, name, and file_path keys.
	 *
	 * @since 2.0.0
	 */
	public function test_register_tab_has_required_keys() {
		$result = $this->admin->register_tab( array() );
		$tab    = $result['wps-membership-access-rules'];

		$this->assertArrayHasKey( 'title', $tab );
		$this->assertArrayHasKey( 'name', $tab );
		$this->assertArrayHasKey( 'file_path', $tab );
	}

	/**
	 * Pre-existing tabs are not removed when the new one is added.
	 *
	 * @since 2.0.0
	 */
	public function test_register_tab_preserves_existing_tabs() {
		$tabs   = array( 'wps-existing' => array( 'title' => 'Existing' ) );
		$result = $this->admin->register_tab( $tabs );

		$this->assertArrayHasKey( 'wps-existing', $result );
		$this->assertArrayHasKey( 'wps-membership-access-rules', $result );
	}

	// -----------------------------------------------------------------------
	// Underlying search queries (tested directly, not via the AJAX handler
	// since check_ajax_referer() / wp_send_json() call exit() in test context)
	// -----------------------------------------------------------------------

	/**
	 * A published post whose title matches the search term is returned.
	 *
	 * @since 2.0.0
	 */
	public function test_search_finds_published_posts_by_title() {
		self::factory()->post->create(
			array(
				'post_title'  => 'Members Only Article',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$results = get_posts(
			array(
				's'              => 'Members Only',
				'post_type'      => 'post',
				'posts_per_page' => 20,
				'post_status'    => 'publish',
			)
		);

		$this->assertNotEmpty( $results );
		$this->assertSame( 'Members Only Article', $results[0]->post_title );
	}

	/**
	 * Draft posts are excluded from the publish-only query.
	 *
	 * @since 2.0.0
	 */
	public function test_search_excludes_draft_posts() {
		self::factory()->post->create(
			array(
				'post_title'  => 'Hidden Draft Post',
				'post_status' => 'draft',
				'post_type'   => 'post',
			)
		);

		$results = get_posts(
			array(
				's'              => 'Hidden Draft Post',
				'post_type'      => 'post',
				'posts_per_page' => 20,
				'post_status'    => 'publish',
			)
		);

		$this->assertEmpty( $results );
	}

	/**
	 * Taxonomy terms matching a partial name are returned.
	 *
	 * @since 2.0.0
	 */
	public function test_search_finds_taxonomy_terms_by_name() {
		wp_insert_term( 'Premium Members', 'category' );

		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'name__like' => 'Premium',
				'number'     => 20,
				'hide_empty' => false,
			)
		);

		$this->assertIsArray( $terms );
		$this->assertNotEmpty( $terms );
		$names = wp_list_pluck( $terms, 'name' );
		$this->assertContains( 'Premium Members', $names );
	}

	/**
	 * A term search with no match returns an empty array.
	 *
	 * @since 2.0.0
	 */
	public function test_search_returns_no_terms_for_nonexistent_name() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'name__like' => 'zzz_nonexistent_xyz',
				'number'     => 20,
				'hide_empty' => false,
			)
		);

		$this->assertEmpty( $terms );
	}

	// -----------------------------------------------------------------------
	// persist_global_defaults() — via reflection (nonce verified by caller)
	// -----------------------------------------------------------------------

	/**
	 * All seven default options are written from POST data.
	 *
	 * @since 2.0.0
	 */
	public function test_global_defaults_persist_message_behavior() {
		$_POST['wps_access_default_behavior']   = 'message';
		$_POST['wps_access_logged_out_message'] = 'Log in to read.';
		$_POST['wps_access_wrong_plan_message'] = 'Upgrade your plan.';
		$_POST['wps_access_redirect_url']       = 'https://example.com/plans';
		$_POST['wps_access_restrict_comments']  = '1';
		$_POST['wps_access_include_in_archive'] = '1';
		$_POST['wps_access_show_purchase_cta']  = '1';

		$this->invoke_private( 'persist_global_defaults' );

		$this->assertSame( 'message', get_option( 'wps_access_default_behavior' ) );
		$this->assertSame( 'Log in to read.', get_option( 'wps_access_logged_out_message' ) );
		$this->assertSame( 'Upgrade your plan.', get_option( 'wps_access_wrong_plan_message' ) );
		$this->assertSame( 'https://example.com/plans', get_option( 'wps_access_redirect_url' ) );
		$this->assertSame( '1', get_option( 'wps_access_restrict_comments' ) );
		$this->assertSame( '1', get_option( 'wps_access_include_in_archive' ) );
		$this->assertSame( '1', get_option( 'wps_access_show_purchase_cta' ) );
	}

	/**
	 * Redirect behavior is stored verbatim when submitted.
	 *
	 * @since 2.0.0
	 */
	public function test_global_defaults_persist_redirect_behavior() {
		$_POST['wps_access_default_behavior'] = 'redirect';

		$this->invoke_private( 'persist_global_defaults' );

		$this->assertSame( 'redirect', get_option( 'wps_access_default_behavior' ) );
	}

	/**
	 * Absent checkbox keys default to '0' (unchecked).
	 *
	 * @since 2.0.0
	 */
	public function test_global_defaults_toggles_default_to_off_when_absent() {
		unset(
			$_POST['wps_access_restrict_comments'],
			$_POST['wps_access_include_in_archive'],
			$_POST['wps_access_show_purchase_cta']
		);

		$this->invoke_private( 'persist_global_defaults' );

		$this->assertSame( '0', get_option( 'wps_access_restrict_comments' ) );
		$this->assertSame( '0', get_option( 'wps_access_include_in_archive' ) );
		$this->assertSame( '0', get_option( 'wps_access_show_purchase_cta' ) );
	}

	/**
	 * JavaScript: redirect URLs are stripped by esc_url_raw().
	 *
	 * @since 2.0.0
	 */
	public function test_global_defaults_strips_javascript_from_redirect_url() {
		$_POST['wps_access_redirect_url'] = 'javascript:alert(1)';

		$this->invoke_private( 'persist_global_defaults' );

		$this->assertSame( '', get_option( 'wps_access_redirect_url' ) );
	}

	// -----------------------------------------------------------------------
	// persist_rules() — via reflection
	// -----------------------------------------------------------------------

	/**
	 * A single rule submitted via POST is stored in the option.
	 *
	 * @since 2.0.0
	 */
	public function test_persist_rules_saves_rule_to_option() {
		$_POST['wps_rules'] = array(
			array(
				'id'           => '',
				'target_type'  => 'post_type',
				'post_type'    => 'post',
				'plans'        => array( 'gold' ),
				'behavior'     => 'message',
				'message'      => '',
				'redirect_url' => '',
				'priority'     => '10',
			),
		);

		$this->invoke_private( 'persist_rules' );

		$rules = wps_get_access_rules();
		$this->assertCount( 1, $rules );
		$this->assertSame( 'post_type', $rules[0]['target_type'] );
		$this->assertSame( 'post', $rules[0]['post_type'] );
		$this->assertSame( array( 'gold' ), $rules[0]['plans'] );
	}

	/**
	 * A row with an empty id gets an auto-generated non-empty id.
	 *
	 * @since 2.0.0
	 */
	public function test_persist_rules_auto_generates_id_for_new_row() {
		$_POST['wps_rules'] = array(
			array(
				'id'          => '',
				'target_type' => 'post_type',
				'post_type'   => 'page',
				'plans'       => array( 'any' ),
				'behavior'    => 'message',
				'priority'    => '5',
			),
		);

		$this->invoke_private( 'persist_rules' );

		$rules = wps_get_access_rules();
		$this->assertNotEmpty( $rules[0]['id'] );
	}

	/**
	 * A row that already carries an id has that id preserved on save.
	 *
	 * @since 2.0.0
	 */
	public function test_persist_rules_preserves_existing_id() {
		$_POST['wps_rules'] = array(
			array(
				'id'          => 'r_keep_me',
				'target_type' => 'post_type',
				'post_type'   => 'post',
				'plans'       => array( 'any' ),
				'behavior'    => 'message',
				'priority'    => '10',
			),
		);

		$this->invoke_private( 'persist_rules' );

		$rules = wps_get_access_rules();
		$this->assertSame( 'r_keep_me', $rules[0]['id'] );
	}

	/**
	 * Multiple new rows each receive a distinct auto-generated id.
	 *
	 * @since 2.0.0
	 */
	public function test_persist_rules_generates_unique_ids_for_multiple_new_rows() {
		$_POST['wps_rules'] = array(
			array(
				'id'          => '',
				'target_type' => 'post_type',
				'post_type'   => 'post',
				'plans'       => array( 'any' ),
				'behavior'    => 'message',
				'priority'    => '10',
			),
			array(
				'id'          => '',
				'target_type' => 'post_type',
				'post_type'   => 'page',
				'plans'       => array( 'any' ),
				'behavior'    => 'message',
				'priority'    => '20',
			),
		);

		$this->invoke_private( 'persist_rules' );

		$rules = wps_get_access_rules();
		$this->assertCount( 2, $rules );
		$this->assertNotSame( $rules[0]['id'], $rules[1]['id'] );
	}

	/**
	 * Saving rules triggers an index rebuild so enforcement stays in sync.
	 *
	 * @since 2.0.0
	 */
	public function test_persist_rules_rebuilds_index() {
		$_POST['wps_rules'] = array(
			array(
				'id'          => 'r_idx_rebuild',
				'target_type' => 'post_type',
				'post_type'   => 'post',
				'plans'       => array( 'any' ),
				'behavior'    => 'message',
				'priority'    => '10',
			),
		);

		$this->invoke_private( 'persist_rules' );

		$index = get_option( WPS_ACCESS_RULES_INDEX_OPTION );
		$this->assertArrayHasKey( 'post', $index['post_type'] );
		$this->assertContains( 'r_idx_rebuild', $index['post_type']['post'] );
	}

	/**
	 * Saving an empty rules array removes all previously stored rules.
	 *
	 * @since 2.0.0
	 */
	public function test_persist_rules_empty_post_clears_all_rules() {
		wps_add_access_rule(
			array(
				'target_type' => 'post_type',
				'post_type'   => 'post',
				'plans'       => array( 'any' ),
				'behavior'    => 'message',
				'priority'    => 10,
			)
		);
		$this->assertCount( 1, wps_get_access_rules() );

		$_POST['wps_rules'] = array();
		$this->invoke_private( 'persist_rules' );

		$this->assertSame( array(), wps_get_access_rules() );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Invoke a private method on the admin instance via reflection.
	 *
	 * @since 2.0.0
	 * @param string $method_name Method to invoke.
	 */
	private function invoke_private( $method_name ) {
		$method = new ReflectionMethod( WPS_Access_Rules_Admin::class, $method_name );
		$method->setAccessible( true );
		$method->invoke( $this->admin );
	}
}
