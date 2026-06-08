<?php
/**
 * Unit tests for Day 10 membership email classes.
 *
 * Covers:
 *   - All four WPS_Membership_*_Email classes register the correct WC_Email metadata.
 *   - trigger() bails early for an invalid user.
 *   - trigger() bails early when the email is disabled.
 *   - Activated email trigger() is wired to wps_membership_created.
 *   - Activated email trigger_on_reactivate() only fires for new_status = active.
 *   - Status-change emails (cancelled, expired, on-hold) only fire for their status.
 *
 * @since   2.0.0
 * @package Subscriptions_For_Woocommerce
 */

class MembershipEmailTest extends WP_UnitTestCase {

	/** @var int A WordPress user created for the test run. */
	private $user_id;

	/** @var string A plan slug used throughout the tests. */
	private $plan_slug = 'gold';

	public function setUp(): void {
		parent::setUp();
		$this->user_id = $this->factory->user->create(
			array(
				'user_email'   => 'member@example.com',
				'display_name' => 'Test Member',
			)
		);

		// Stub wps_get_plan_by_slug so email tests don't need a real CPT post.
		if ( ! function_exists( 'wps_get_plan_by_slug' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
			function wps_get_plan_by_slug( $slug ) {
				return array(
					'slug' => $slug,
					'name' => ucfirst( $slug ) . ' Plan',
				);
			}
		}

		// Pre-load email classes (normally loaded via woocommerce_email_classes filter).
		$emails_dir = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'emails/';
		if ( ! class_exists( 'WPS_Membership_Activated_Email' ) ) {
			require_once $emails_dir . 'class-wps-membership-activated-email.php';
		}
		if ( ! class_exists( 'WPS_Membership_Cancelled_Email' ) ) {
			require_once $emails_dir . 'class-wps-membership-cancelled-email.php';
		}
		if ( ! class_exists( 'WPS_Membership_Expired_Email' ) ) {
			require_once $emails_dir . 'class-wps-membership-expired-email.php';
		}
		if ( ! class_exists( 'WPS_Membership_Onhold_Email' ) ) {
			require_once $emails_dir . 'class-wps-membership-onhold-email.php';
		}
	}

	// -----------------------------------------------------------------------
	// Email metadata
	// -----------------------------------------------------------------------

	public function test_activated_email_has_correct_id() {
		$email = new WPS_Membership_Activated_Email();
		$this->assertSame( 'wps_membership_activated', $email->id );
	}

	public function test_cancelled_email_has_correct_id() {
		$email = new WPS_Membership_Cancelled_Email();
		$this->assertSame( 'wps_membership_cancelled', $email->id );
	}

	public function test_expired_email_has_correct_id() {
		$email = new WPS_Membership_Expired_Email();
		$this->assertSame( 'wps_membership_expired', $email->id );
	}

	public function test_onhold_email_has_correct_id() {
		$email = new WPS_Membership_Onhold_Email();
		$this->assertSame( 'wps_membership_onhold', $email->id );
	}

	public function test_activated_email_default_subject_contains_site_title_placeholder() {
		$email = new WPS_Membership_Activated_Email();
		$this->assertStringContainsString( '{site_title}', $email->get_default_subject() );
	}

	public function test_all_emails_have_html_template_path_set() {
		foreach ( array( 'Activated', 'Cancelled', 'Expired', 'Onhold' ) as $type ) {
			$class = 'WPS_Membership_' . $type . '_Email';
			$email = new $class();
			$this->assertNotEmpty( $email->template_html, "template_html should be set for {$type}" );
		}
	}

	public function test_all_emails_have_plain_template_path_set() {
		foreach ( array( 'Activated', 'Cancelled', 'Expired', 'Onhold' ) as $type ) {
			$class = 'WPS_Membership_' . $type . '_Email';
			$email = new $class();
			$this->assertNotEmpty( $email->template_plain, "template_plain should be set for {$type}" );
		}
	}

	// -----------------------------------------------------------------------
	// trigger() guard: invalid user
	// -----------------------------------------------------------------------

	public function test_activated_trigger_bails_for_invalid_user() {
		$email        = new WPS_Membership_Activated_Email();
		$sent_before  = did_action( 'woocommerce_email_sent' );
		$email->trigger( 0, $this->plan_slug, array() );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	public function test_cancelled_trigger_bails_for_invalid_user() {
		$email       = new WPS_Membership_Cancelled_Email();
		$sent_before = did_action( 'woocommerce_email_sent' );
		$email->trigger( 0, $this->plan_slug, array() );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	public function test_expired_trigger_bails_for_invalid_user() {
		$email       = new WPS_Membership_Expired_Email();
		$sent_before = did_action( 'woocommerce_email_sent' );
		$email->trigger( 0, $this->plan_slug, array() );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	public function test_onhold_trigger_bails_for_invalid_user() {
		$email       = new WPS_Membership_Onhold_Email();
		$sent_before = did_action( 'woocommerce_email_sent' );
		$email->trigger( 0, $this->plan_slug, array() );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	// -----------------------------------------------------------------------
	// trigger() guard: email disabled
	// -----------------------------------------------------------------------

	public function test_activated_trigger_bails_when_disabled() {
		$email = new WPS_Membership_Activated_Email();
		update_option( $email->get_option_key(), array( 'enabled' => 'no' ) );
		$email->init_settings();

		$sent_before = did_action( 'woocommerce_email_sent' );
		$email->trigger( $this->user_id, $this->plan_slug, array() );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	// -----------------------------------------------------------------------
	// trigger() populates $this->object
	// -----------------------------------------------------------------------

	public function test_activated_trigger_sets_object_with_user() {
		$email = new WPS_Membership_Activated_Email();
		// Disable sending to avoid actual mail; object is set before the send guard.
		update_option( $email->get_option_key(), array( 'enabled' => 'no' ) );
		$email->init_settings();

		$row = array( 'plan_slug' => $this->plan_slug, 'status' => 'active', 'start_date' => time() );
		$email->trigger( $this->user_id, $this->plan_slug, $row );

		$this->assertIsArray( $email->object );
		$this->assertSame( $this->user_id, $email->object['user_id'] );
		$this->assertSame( $this->plan_slug, $email->object['plan_slug'] );
		$this->assertInstanceOf( 'WP_User', $email->object['user'] );
	}

	// -----------------------------------------------------------------------
	// trigger_on_reactivate() status filter
	// -----------------------------------------------------------------------

	public function test_trigger_on_reactivate_ignores_non_active_status() {
		$email = new WPS_Membership_Activated_Email();
		update_option( $email->get_option_key(), array( 'enabled' => 'yes' ) );
		$email->init_settings();

		// Provide a real user so the guard doesn't trip on user lookup.
		wps_create_user_membership( $this->user_id, array( 'plan_slug' => $this->plan_slug ) );

		$sent_before = did_action( 'woocommerce_email_sent' );
		$email->trigger_on_reactivate( $this->user_id, $this->plan_slug, 'cancelled', 'active' );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	// -----------------------------------------------------------------------
	// maybe_trigger() status filters
	// -----------------------------------------------------------------------

	public function test_cancelled_maybe_trigger_ignores_non_cancelled_status() {
		$email       = new WPS_Membership_Cancelled_Email();
		$sent_before = did_action( 'woocommerce_email_sent' );
		$email->maybe_trigger( $this->user_id, $this->plan_slug, 'active', '' );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	public function test_expired_maybe_trigger_ignores_non_expired_status() {
		$email       = new WPS_Membership_Expired_Email();
		$sent_before = did_action( 'woocommerce_email_sent' );
		$email->maybe_trigger( $this->user_id, $this->plan_slug, 'active', '' );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	public function test_onhold_maybe_trigger_ignores_non_onhold_status() {
		$email       = new WPS_Membership_Onhold_Email();
		$sent_before = did_action( 'woocommerce_email_sent' );
		$email->maybe_trigger( $this->user_id, $this->plan_slug, 'active', '' );
		$this->assertSame( $sent_before, did_action( 'woocommerce_email_sent' ) );
	}

	// -----------------------------------------------------------------------
	// Hook wiring
	// -----------------------------------------------------------------------

	public function test_activated_email_is_hooked_to_wps_membership_created() {
		$email = new WPS_Membership_Activated_Email();
		$this->assertGreaterThan(
			0,
			has_action( 'wps_membership_created', array( $email, 'trigger' ) )
		);
	}

	public function test_activated_email_is_hooked_to_wps_membership_status_changed() {
		$email = new WPS_Membership_Activated_Email();
		$this->assertGreaterThan(
			0,
			has_action( 'wps_membership_status_changed', array( $email, 'trigger_on_reactivate' ) )
		);
	}

	public function test_cancelled_email_is_hooked_to_wps_membership_status_changed() {
		$email = new WPS_Membership_Cancelled_Email();
		$this->assertGreaterThan(
			0,
			has_action( 'wps_membership_status_changed', array( $email, 'maybe_trigger' ) )
		);
	}

	public function test_expired_email_is_hooked_to_wps_membership_status_changed() {
		$email = new WPS_Membership_Expired_Email();
		$this->assertGreaterThan(
			0,
			has_action( 'wps_membership_status_changed', array( $email, 'maybe_trigger' ) )
		);
	}

	public function test_onhold_email_is_hooked_to_wps_membership_status_changed() {
		$email = new WPS_Membership_Onhold_Email();
		$this->assertGreaterThan(
			0,
			has_action( 'wps_membership_status_changed', array( $email, 'maybe_trigger' ) )
		);
	}
}
