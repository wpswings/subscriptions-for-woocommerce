<?php
/**
 * Unit tests for CVE-2: subscription cancellation via CSRF / missing ownership check.
 *
 * The handler wps_sfw_cancel_susbcription() runs on `init` and previously:
 *   1. Accepted any request where _wpnonce was merely present (token not verified).
 *   2. Never called the ownership helper, relying only on the inner cancel
 *      function's loose `$wps_customer_id == $user_id` check.
 *
 * After the fix:
 *   1. wp_verify_nonce() is called; a token that is merely present but invalid
 *      is rejected.
 *   2. wps_sfw_current_user_can_view_subscription() gates the cancel path so
 *      a user who does not own the subscription cannot cancel it, even with a
 *      cryptographically valid nonce for the right action.
 *
 * @since   2.0.2
 * @package Subscriptions_For_Woocommerce
 */

/**
 * Tests for the customer-facing subscription cancellation handler.
 */
class CancelSubscriptionTest extends WP_UnitTestCase {

	/**
	 * The public class under test.
	 *
	 * @var Subscriptions_For_Woocommerce_Public
	 */
	private $public_obj;

	/**
	 * Post ID of the fake subscription owned by $this->owner_id.
	 *
	 * @var int
	 */
	private $subscription_id;

	/**
	 * User ID of the subscription owner.
	 *
	 * @var int
	 */
	private $owner_id;

	/**
	 * User ID of a different logged-in user who does not own the subscription.
	 *
	 * @var int
	 */
	private $stranger_id;

	/**
	 * Set up fixtures before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		require_once SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
			. 'public/class-subscriptions-for-woocommerce-public.php';

		$this->public_obj = new Subscriptions_For_Woocommerce_Public(
			'subscriptions-for-woocommerce',
			SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
		);

		$this->owner_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$this->stranger_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Create a minimal subscription post so wps_sfw_check_valid_subscription() passes.
		$this->subscription_id = $this->factory->post->create(
			array(
				'post_type'   => 'wps_subscriptions',
				'post_status' => 'publish',
			)
		);

		// Tag the subscription with the owner's user ID (mirrors what the plugin writes).
		wps_sfw_update_meta_data( $this->subscription_id, 'wps_customer_id', $this->owner_id );
		wps_sfw_update_meta_data( $this->subscription_id, 'wps_subscription_status', 'active' );
	}

	/**
	 * Tear down fixtures after each test.
	 */
	public function tearDown(): void {
		unset( $_GET['wps_subscription_id'], $_GET['wps_subscription_status'], $_GET['_wpnonce'] );
		wp_delete_post( $this->subscription_id, true );
		wp_delete_user( $this->owner_id );
		wp_delete_user( $this->stranger_id );
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Populate $_GET with the cancel parameters including a genuine WP nonce
	 * created for the current user's session.
	 *
	 * @param int    $subscription_id Subscription post ID.
	 * @param string $status          wps_subscription_status value.
	 * @param string $nonce_override  Pass a custom nonce string to simulate an invalid token.
	 */
	private function set_cancel_request( $subscription_id, $status = 'active', $nonce_override = null ) {
		$_GET['wps_subscription_id']     = (string) $subscription_id;
		$_GET['wps_subscription_status'] = $status;
		$_GET['_wpnonce']                = $nonce_override ?? wp_create_nonce( $subscription_id . $status );
	}

	/**
	 * Return the stored subscription status directly from meta.
	 *
	 * @param int $subscription_id Subscription post ID.
	 * @return string
	 */
	private function get_status( $subscription_id ) {
		return (string) wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true );
	}

	// -------------------------------------------------------------------------
	// Missing parameters
	// -------------------------------------------------------------------------

	/** Handler returns early when _wpnonce is absent. */
	public function test_missing_nonce_is_rejected() {
		wp_set_current_user( $this->owner_id );
		$_GET['wps_subscription_id']     = (string) $this->subscription_id;
		$_GET['wps_subscription_status'] = 'active';
		// Deliberately omit _wpnonce.

		$this->public_obj->wps_sfw_cancel_susbcription();

		$this->assertSame( 'active', $this->get_status( $this->subscription_id ) );
	}

	// -------------------------------------------------------------------------
	// Nonce verification (CVE-2 part 1)
	// -------------------------------------------------------------------------

	/** A token that is merely present but cryptographically invalid is rejected. */
	public function test_invalid_nonce_is_rejected() {
		wp_set_current_user( $this->owner_id );
		$this->set_cancel_request( $this->subscription_id, 'active', 'x' );

		$this->public_obj->wps_sfw_cancel_susbcription();

		$this->assertSame( 'active', $this->get_status( $this->subscription_id ) );
	}

	/** Whitespace-only nonce is rejected. */
	public function test_whitespace_nonce_is_rejected() {
		wp_set_current_user( $this->owner_id );
		$this->set_cancel_request( $this->subscription_id, 'active', '   ' );

		$this->public_obj->wps_sfw_cancel_susbcription();

		$this->assertSame( 'active', $this->get_status( $this->subscription_id ) );
	}

	// -------------------------------------------------------------------------
	// Ownership check (CVE-2 part 2)
	// -------------------------------------------------------------------------

	/**
	 * A logged-in user with a valid nonce cannot cancel a subscription they
	 * do not own.
	 *
	 * Before the fix the handler skipped the ownership check entirely; the
	 * inner function's loose equality check was the only barrier, and in some
	 * edge cases (e.g. empty stored customer ID) it could be bypassed.
	 */
	public function test_non_owner_cannot_cancel_with_valid_nonce() {
		wp_set_current_user( $this->stranger_id );
		// Create a nonce for the stranger's session — it is cryptographically
		// valid for this user, but they do not own the subscription.
		$this->set_cancel_request( $this->subscription_id );

		$this->public_obj->wps_sfw_cancel_susbcription();

		$this->assertSame(
			'active',
			$this->get_status( $this->subscription_id ),
			'A non-owner must not be able to cancel a subscription they do not own'
		);
	}

	/** Guest (user ID 0) cannot cancel even with a parameter set. */
	public function test_guest_cannot_cancel() {
		wp_set_current_user( 0 );
		$this->set_cancel_request( $this->subscription_id, 'active', 'anything' );

		$this->public_obj->wps_sfw_cancel_susbcription();

		$this->assertSame( 'active', $this->get_status( $this->subscription_id ) );
	}

	// -------------------------------------------------------------------------
	// Happy path
	// -------------------------------------------------------------------------

	/**
	 * Owner with a valid nonce and an 'active' subscription status can cancel.
	 *
	 * We stub wp_safe_redirect/exit via output buffering; the test catches the
	 * redirect exception (some test suites wrap wp_safe_redirect + exit to
	 * throw WPDieException) or simply checks the meta after the call.
	 */
	public function test_owner_can_cancel_with_valid_nonce() {
		wp_set_current_user( $this->owner_id );
		$this->set_cancel_request( $this->subscription_id );

		try {
			$this->public_obj->wps_sfw_cancel_susbcription();
		} catch ( WPDieException $e ) {
			// wp_safe_redirect + exit was called — that is the expected path.
		}

		$this->assertSame(
			'cancelled',
			$this->get_status( $this->subscription_id ),
			'The owner with a valid nonce must be able to cancel their own subscription'
		);
	}
}
