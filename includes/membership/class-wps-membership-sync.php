<?php
/**
 * Membership Layer — Subscription Sync / Normalization Shim (Day 04)
 *
 * The Free plugin has no single `wps_subscription_status_changed` hook.
 * Status transitions are signalled by scattered, inconsistently-named hooks.
 * This class subscribes to every real hook and translates each into one
 * internal call to `process_subscription()`, which writes (or updates) the
 * user membership via the Day-03 CRUD layer.
 *
 * Hook → normalized status map (verified against codebase):
 *
 *   wps_wsp_after_subscription_active  → active   (fires before meta write)
 *   wps_sfw_order_status_changed       → active   (fires after meta write — reactivation)
 *   wps_sfw_subscription_cancel        → cancelled
 *   wps_sfw_cancel_susbcription        → cancelled
 *   wps_sfw_expire_subscription_scheduler → expired
 *   wps_sfw_after_renewal_payment      → active + extend expiry
 *   wps_sfw_recurring_payment_success  → active + extend expiry (gateway-specific)
 *   updated_post_meta (backstop)       → reads wps_subscription_status for
 *                                        any state change not caught above
 *
 * Backward-compat guarantee: if the subscription's product has no linked plan
 * (`wps_get_plan_by_product()` returns null) every method returns immediately
 * without writing any user meta — existing subscriptions are untouched.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Membership_Sync' ) ) {

	/**
	 * Translates scattered subscription status hooks into membership CRUD calls.
	 *
	 * @since 2.0.0
	 */
	class WPS_Membership_Sync {

		/**
		 * Register all real-hook listeners.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			// Initial activation — fires before wps_next_payment_date meta is written.
			add_action( 'wps_wsp_after_subscription_active', array( $this, 'on_subscription_active' ), 10, 2 );

			// Reactivation / completion — fires after all activation meta is written.
			add_action( 'wps_sfw_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 2 );

			// Cancellation.
			add_action( 'wps_sfw_subscription_cancel', array( $this, 'on_subscription_cancelled' ), 10, 2 );
			add_action( 'wps_sfw_cancel_susbcription', array( $this, 'on_subscription_cancelled_user' ), 10, 2 );

			// Expiry.
			add_action( 'wps_sfw_expire_subscription_scheduler', array( $this, 'on_subscription_expired' ), 10, 1 );

			// Renewal payment — scheduler path (passes WC_Order object + subscription ID).
			add_action( 'wps_sfw_after_renewal_payment', array( $this, 'on_renewal_payment' ), 10, 3 );

			// Renewal payment — gateway-specific path (passes renewal order ID only).
			add_action( 'wps_sfw_recurring_payment_success', array( $this, 'on_recurring_payment_success' ), 10, 1 );

			// Catch-all backstop: any wps_subscription_status meta write not caught above.
			// Covers on-hold (set directly in public.php without a dedicated hook) and
			// any future status values added to the plugin.
			add_action( 'updated_post_meta', array( $this, 'on_subscription_status_meta_updated' ), 10, 4 );
		}

		// -----------------------------------------------------------------------
		// Hook callbacks
		// -----------------------------------------------------------------------

		/**
		 * Handle initial subscription activation.
		 *
		 * Fires before `wps_next_payment_date` is written to meta, so expiry is
		 * computed directly from the subscription's interval/number metas.
		 *
		 * @since 2.0.0
		 * @param string $status          The status string ('active' or filtered value).
		 * @param int    $subscription_id Subscription post / order ID.
		 */
		public function on_subscription_active( $status, $subscription_id ) {
			if ( 'active' !== $status ) {
				return;
			}
			$expiry = $this->compute_expiry_from_subscription( absint( $subscription_id ) );
			$this->process_subscription( absint( $subscription_id ), 'active', $expiry );
		}

		/**
		 * Handle reactivation / completion (fires after all meta has been written).
		 *
		 * Reads the already-persisted `wps_next_payment_date` so the expiry is
		 * accurate; merges with any entry written by on_subscription_active().
		 *
		 * @since 2.0.0
		 * @param int $order_id        Parent / renewal order ID.
		 * @param int $subscription_id Subscription post / order ID.
		 */
		public function on_order_status_changed( $order_id, $subscription_id ) {
			$subscription_id = absint( $subscription_id );
			$expiry          = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_next_payment_date', true ) );
			$this->process_subscription( $subscription_id, 'active', $expiry ? $expiry : null );
		}

		/**
		 * Handle admin / system cancellation.
		 *
		 * @since 2.0.0
		 * @param int $subscription_id Subscription post / order ID.
		 */
		public function on_subscription_cancelled( $subscription_id ) {
			$this->process_subscription( absint( $subscription_id ), 'cancelled' );
		}

		/**
		 * Handle user-initiated cancellation.
		 *
		 * @since 2.0.0
		 * @param int $subscription_id Subscription post / order ID.
		 */
		public function on_subscription_cancelled_user( $subscription_id ) {
			$this->process_subscription( absint( $subscription_id ), 'cancelled' );
		}

		/**
		 * Handle subscription expiry (fired by the scheduler cron).
		 *
		 * @since 2.0.0
		 * @param int $subscription_id Subscription post / order ID.
		 */
		public function on_subscription_expired( $subscription_id ) {
			$this->process_subscription( absint( $subscription_id ), 'expired' );
		}

		/**
		 * Handle a successful renewal payment (scheduler path).
		 *
		 * `wps_next_payment_date` has already been updated on the subscription by
		 * the time this hook fires.
		 *
		 * @since 2.0.0
		 * @param WC_Order $renewal_order   The newly created renewal order object.
		 * @param int      $subscription_id Subscription post / order ID.
		 */
		public function on_renewal_payment( $renewal_order, $subscription_id ) {
			$subscription_id = absint( $subscription_id );
			$expiry          = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_next_payment_date', true ) );
			$this->process_subscription( $subscription_id, 'active', $expiry ? $expiry : null );
		}

		/**
		 * Handle a successful renewal payment (gateway-specific path).
		 *
		 * Some gateways (PayPal, Woocybs) fire this hook with only the renewal
		 * order ID. The subscription ID is resolved via the order's
		 * `wps_sfw_subscription` meta.
		 *
		 * @since 2.0.0
		 * @param int $order_id Renewal order ID.
		 */
		public function on_recurring_payment_success( $order_id ) {
			$order_id        = absint( $order_id );
			$subscription_id = absint( wps_sfw_get_meta_data( $order_id, 'wps_sfw_subscription', true ) );

			if ( ! $subscription_id ) {
				return;
			}

			$expiry = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_next_payment_date', true ) );
			$this->process_subscription( $subscription_id, 'active', $expiry ? $expiry : null );
		}

		/**
		 * Catch-all backstop: sync any wps_subscription_status meta write.
		 *
		 * Covers the on-hold path (set directly in public.php without a dedicated
		 * hook) and any future status values not covered by the named hooks above.
		 * Only runs for posts of type `wps_subscriptions`.
		 *
		 * @since 2.0.0
		 * @param int    $meta_id   Meta record ID (unused).
		 * @param int    $post_id   Post ID of the updated post.
		 * @param string $meta_key  The meta key that was updated.
		 * @param mixed  $meta_value The new meta value.
		 */
		public function on_subscription_status_meta_updated( $meta_id, $post_id, $meta_key, $meta_value ) {
			if ( 'wps_subscription_status' !== $meta_key ) {
				return;
			}

			// Only handle wps_subscriptions post type (legacy mode).
			if ( 'wps_subscriptions' !== get_post_type( $post_id ) ) {
				return;
			}

			$meta_value = sanitize_key( $meta_value );

			// Only handle statuses not already covered by dedicated hooks above
			// to avoid double-processing active/cancelled/expired transitions.
			if ( in_array( $meta_value, array( 'on-hold', 'paused' ), true ) ) {
				$this->process_subscription( absint( $post_id ), $meta_value );
			}
		}

		// -----------------------------------------------------------------------
		// Core processing
		// -----------------------------------------------------------------------

		/**
		 * Resolve subscription → user + plan, then write or update the membership.
		 *
		 * This is the single point through which all subscription events pass.
		 * Returns immediately (no-op) when the subscription's product has no
		 * linked plan — preserving full backward compatibility.
		 *
		 * @since 2.0.0
		 * @param int      $subscription_id  Subscription post / order ID.
		 * @param string   $normalized_status Target membership status.
		 * @param int|null $expiry_date       Unix timestamp for expiry, or null for lifetime.
		 */
		private function process_subscription( $subscription_id, $normalized_status, $expiry_date = null ) {
			$subscription_id   = absint( $subscription_id );
			$normalized_status = sanitize_key( $normalized_status );

			if ( ! $subscription_id ) {
				return;
			}

			// Resolve product → plan (O(1) map lookup).
			$product_id = absint( wps_sfw_get_meta_data( $subscription_id, 'product_id', true ) );
			if ( ! $product_id ) {
				return;
			}

			$plan_slug = wps_get_plan_by_product( $product_id );
			if ( ! $plan_slug ) {
				// No linked plan — backward-compat guarantee: touch nothing.
				return;
			}

			// Respect the "Active Subscription" enabled toggle.
			$plan_data = function_exists( 'wps_get_plan_by_slug' )
				? wps_get_plan_by_slug( $plan_slug )
				: null;
			if ( $plan_data && ! $plan_data['subscription_enabled'] ) {
				return;
			}

			$user_id = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_customer_id', true ) );
			if ( ! $user_id ) {
				return;
			}

			if ( 'active' === $normalized_status ) {
				// Grant or reactivate — wps_create_user_membership merges correctly
				// when a membership already exists (keeps longer expiry, higher source).
				wps_create_user_membership(
					$user_id,
					array(
						'plan_slug'       => $plan_slug,
						'status'          => 'active',
						'source'          => 'subscription',
						'subscription_id' => $subscription_id,
						'expiry_date'     => $expiry_date,
					)
				);
			} else {
				// Any non-active status: only update if membership exists.
				$existing = wps_get_membership( $user_id, $plan_slug );
				if ( ! $existing ) {
					return;
				}

				// Only update if the existing membership is sourced from this
				// subscription — don't overwrite a manual or order-sourced membership.
				if ( 'subscription' === $existing['source']
					&& absint( $existing['subscription_id'] ) === $subscription_id ) {
					wps_update_membership_status( $user_id, $plan_slug, $normalized_status );
				}
			}
		}

		// -----------------------------------------------------------------------
		// Helpers
		// -----------------------------------------------------------------------

		/**
		 * Compute the next-payment expiry timestamp from subscription metas.
		 *
		 * Used when `wps_next_payment_date` hasn't been persisted yet (i.e. during
		 * `wps_wsp_after_subscription_active` which fires before the meta write).
		 *
		 * @since  2.0.0
		 * @param  int $subscription_id Subscription post / order ID.
		 * @return int|null Unix timestamp of the next payment, or null on failure.
		 */
		private function compute_expiry_from_subscription( $subscription_id ) {
			$current_time = time();

			$trial_end = (int) wps_sfw_get_meta_data(
				$subscription_id,
				'wps_susbcription_trial_end',
				true
			);

			$next_payment = (int) wps_sfw_next_payment_date(
				$subscription_id,
				$current_time,
				$trial_end
			);

			return $next_payment > 0 ? $next_payment : null;
		}
	}
}
