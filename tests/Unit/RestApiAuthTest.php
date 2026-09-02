<?php
/**
 * Unit tests for CVE-1: REST API authentication bypass.
 *
 * Covers wps_sfw_validate_secretkey() in
 * package/rest-api/class-subscriptions-for-woocommerce-rest-api.php.
 *
 * Before the fix, a whitespace-only consumer_secret passed the '' == $key
 * emptiness check and then trim($key) === trim('') returned true when the
 * stored key was empty (API feature toggle off).
 *
 * After the fix:
 *   - Both values are trimmed first.
 *   - Rejection if either trims to ''.
 *   - Comparison via hash_equals().
 *
 * @since   2.0.2
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for Subscriptions_For_Woocommerce_Rest_Api::wps_sfw_validate_secretkey().
 */
class RestApiAuthTest extends WP_UnitTestCase {

	/** @var Subscriptions_For_Woocommerce_Rest_Api */
	private $api;

	public function setUp(): void {
		parent::setUp();

		require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
			. 'package/rest-api/class-subscriptions-for-woocommerce-rest-api.php';

		$this->api = new Subscriptions_For_Woocommerce_Rest_Api(
			'subscriptions-for-woocommerce',
			SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
		);

		// Ensure the API feature is OFF (stored key defaults to '').
		update_option( 'wps_sfw_enable_api', 'off' );
		delete_option( 'wps_sfw_secret_key' );
	}

	public function tearDown(): void {
		delete_option( 'wps_sfw_enable_api' );
		delete_option( 'wps_sfw_secret_key' );
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// API feature toggle OFF (stored key == '')
	// -------------------------------------------------------------------------

	/** Empty supplied key must be rejected. */
	public function test_empty_key_rejected_when_api_off() {
		$this->assertFalse( $this->api->wps_sfw_validate_secretkey( '' ) );
	}

	/** Whitespace-only key must be rejected (was accepted before fix). */
	public function test_space_key_rejected_when_api_off() {
		$this->assertFalse(
			$this->api->wps_sfw_validate_secretkey( ' ' ),
			'A space-only key must not bypass auth when stored key is empty'
		);
	}

	/** Tab-only key must be rejected. */
	public function test_tab_key_rejected_when_api_off() {
		$this->assertFalse( $this->api->wps_sfw_validate_secretkey( "\t" ) );
	}

	/** Newline-only key must be rejected. */
	public function test_newline_key_rejected_when_api_off() {
		$this->assertFalse( $this->api->wps_sfw_validate_secretkey( "\n" ) );
	}

	/** Any non-empty wrong key must be rejected. */
	public function test_wrong_key_rejected_when_api_off() {
		$this->assertFalse( $this->api->wps_sfw_validate_secretkey( 'wrong-key' ) );
	}

	// -------------------------------------------------------------------------
	// API feature toggle ON with a real stored key
	// -------------------------------------------------------------------------

	/** Correct key must be accepted when API is on. */
	public function test_correct_key_accepted_when_api_on() {
		update_option( 'wps_sfw_enable_api', 'on' );
		update_option( 'wps_sfw_secret_key', 'super-secret' );

		$this->assertTrue( $this->api->wps_sfw_validate_secretkey( 'super-secret' ) );
	}

	/** Wrong key must be rejected when API is on. */
	public function test_wrong_key_rejected_when_api_on() {
		update_option( 'wps_sfw_enable_api', 'on' );
		update_option( 'wps_sfw_secret_key', 'super-secret' );

		$this->assertFalse( $this->api->wps_sfw_validate_secretkey( 'not-the-secret' ) );
	}

	/** Empty key must be rejected even when API is on with a real key. */
	public function test_empty_supplied_key_rejected_when_api_on() {
		update_option( 'wps_sfw_enable_api', 'on' );
		update_option( 'wps_sfw_secret_key', 'super-secret' );

		$this->assertFalse( $this->api->wps_sfw_validate_secretkey( '' ) );
	}

	/** Whitespace-only key must be rejected even when API is on with a real key. */
	public function test_whitespace_key_rejected_when_api_on_with_real_key() {
		update_option( 'wps_sfw_enable_api', 'on' );
		update_option( 'wps_sfw_secret_key', 'super-secret' );

		$this->assertFalse( $this->api->wps_sfw_validate_secretkey( '   ' ) );
	}

	/** Key with surrounding whitespace is treated as its trimmed value. */
	public function test_key_with_surrounding_whitespace_matches_trimmed_stored_key() {
		update_option( 'wps_sfw_enable_api', 'on' );
		update_option( 'wps_sfw_secret_key', 'super-secret' );

		// Supplied key has trailing space — should match because both are trimmed.
		$this->assertTrue( $this->api->wps_sfw_validate_secretkey( 'super-secret ' ) );
	}
}
