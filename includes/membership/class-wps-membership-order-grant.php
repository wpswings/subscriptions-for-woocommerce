<?php
/**
 * Membership Layer — One-Time Order Grant (Day 04)
 *
 * Handles the second grant path: a non-subscription product linked to a plan
 * is purchased as a regular WooCommerce order.
 *
 * On order completion / payment: for each line item whose product is linked to
 * a plan (via `wps_get_plan_by_product`) and is NOT a subscription product,
 * grant the user a membership with source='order'. Expiry is computed from the
 * plan's `_wps_plan_access_length` meta (lifetime → null; fixed → start + length).
 *
 * On order refund / cancellation: revoke the membership if and only if this
 * order originally granted it (`order_id` match on the membership row).
 *
 * Idempotency: re-firing `woocommerce_order_status_completed` or
 * `woocommerce_payment_complete` for the same order is a no-op — the grant is
 * skipped when a membership for the same plan already carries this order's ID.
 *
 * Backward-compat guarantee: products with no linked plan produce zero new user
 * meta. Subscription products are always skipped (owned by WPS_Membership_Sync).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/includes/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Membership_Order_Grant' ) ) {

	/**
	 * Grants and revokes memberships based on WooCommerce order events.
	 *
	 * @since 2.0.0
	 */
	class WPS_Membership_Order_Grant {

		/**
		 * Register order event listeners.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			add_action( 'woocommerce_order_status_completed', array( $this, 'grant_from_order' ), 10, 1 );
			add_action( 'woocommerce_payment_complete', array( $this, 'grant_from_order' ), 10, 1 );
			add_action( 'woocommerce_order_status_refunded', array( $this, 'revoke_from_order' ), 10, 1 );
			add_action( 'woocommerce_order_status_cancelled', array( $this, 'revoke_from_order' ), 10, 1 );
		}

		// -----------------------------------------------------------------------
		// Grant
		// -----------------------------------------------------------------------

		/**
		 * Process a completed order and grant memberships for linked products.
		 *
		 * Called on both `woocommerce_order_status_completed` and
		 * `woocommerce_payment_complete` — the method is idempotent so firing
		 * twice for the same order is safe.
		 *
		 * @since 2.0.0
		 * @param int $order_id WooCommerce order ID.
		 */
		public function grant_from_order( $order_id ) {
			$order_id = absint( $order_id );
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			$user_id    = (int) $order->get_customer_id();
			$start_date = (int) current_time( 'timestamp' );

			if ( ! $user_id ) {
				return;
			}

			foreach ( $order->get_items() as $item ) {
				$product_id = absint( $item->get_product_id() );
				if ( ! $product_id ) {
					continue;
				}

				$product = wc_get_product( $product_id );
				if ( ! $product ) {
					continue;
				}

				// Skip subscription products — those are owned by WPS_Membership_Sync.
				if ( function_exists( 'wps_sfw_check_product_is_subscription' )
					&& wps_sfw_check_product_is_subscription( $product ) ) {
					continue;
				}

				// No linked plan → backward-compat: touch nothing.
				$plan_slug = wps_get_plan_by_product( $product_id );
				if ( ! $plan_slug ) {
					continue;
				}

				// Idempotency: skip if this order already granted this membership.
				$existing = wps_get_membership( $user_id, $plan_slug );
				if ( $existing
					&& 'order' === $existing['source']
					&& absint( $existing['order_id'] ) === $order_id ) {
					continue;
				}

				$expiry = $this->compute_expiry_from_plan( $plan_slug, $start_date );

				wps_create_user_membership(
					$user_id,
					array(
						'plan_slug'   => $plan_slug,
						'status'      => 'active',
						'source'      => 'order',
						'order_id'    => $order_id,
						'start_date'  => $start_date,
						'expiry_date' => $expiry,
					)
				);
			}
		}

		// -----------------------------------------------------------------------
		// Revoke
		// -----------------------------------------------------------------------

		/**
		 * Revoke memberships granted by a refunded or cancelled order.
		 *
		 * Only cancels a membership when its `order_id` matches this order — a
		 * membership sourced from a subscription or a different order is left alone.
		 *
		 * @since 2.0.0
		 * @param int $order_id WooCommerce order ID.
		 */
		public function revoke_from_order( $order_id ) {
			$order_id = absint( $order_id );
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			$user_id = (int) $order->get_customer_id();
			if ( ! $user_id ) {
				return;
			}

			foreach ( $order->get_items() as $item ) {
				$product_id = absint( $item->get_product_id() );
				if ( ! $product_id ) {
					continue;
				}

				$product = wc_get_product( $product_id );
				if ( ! $product ) {
					continue;
				}

				// Subscription products are never revoked here.
				if ( function_exists( 'wps_sfw_check_product_is_subscription' )
					&& wps_sfw_check_product_is_subscription( $product ) ) {
					continue;
				}

				$plan_slug = wps_get_plan_by_product( $product_id );
				if ( ! $plan_slug ) {
					continue;
				}

				$existing = wps_get_membership( $user_id, $plan_slug );

				// Only revoke when this order granted the membership.
				if ( $existing
					&& 'order' === $existing['source']
					&& absint( $existing['order_id'] ) === $order_id ) {
					wps_update_membership_status( $user_id, $plan_slug, 'cancelled' );
				}
			}
		}

		// -----------------------------------------------------------------------
		// Helpers
		// -----------------------------------------------------------------------

		/**
		 * Compute the membership expiry timestamp from a plan's access-length setting.
		 *
		 * Returns null (lifetime) when the plan's type is 'lifetime'.
		 * Returns a Unix timestamp when the type is 'fixed', calculated as
		 * $start_date + the configured duration.
		 *
		 * @since  2.0.0
		 * @param  string $plan_slug  Plan slug.
		 * @param  int    $start_date Unix timestamp for the start of the membership.
		 * @return int|null Expiry timestamp, or null for lifetime access.
		 */
		private function compute_expiry_from_plan( $plan_slug, $start_date ) {
			$plan = wps_get_plan_by_slug( sanitize_key( $plan_slug ) );

			if ( ! $plan ) {
				return null;
			}

			$length = $plan['access_length'];

			if ( ! is_array( $length ) || 'fixed' !== $length['type'] ) {
				return null; // Lifetime.
			}

			$value = absint( $length['value'] );
			$unit  = isset( $length['unit'] ) ? $length['unit'] : 'day';

			if ( $value <= 0 ) {
				return null;
			}

			$unit_map = array(
				'day'   => 'days',
				'month' => 'months',
				'year'  => 'years',
			);

			$unit_str = isset( $unit_map[ $unit ] ) ? $unit_map[ $unit ] : 'days';

			// Use strtotime for calendar-accurate month/year arithmetic.
			$expiry = strtotime( '+' . $value . ' ' . $unit_str, $start_date );

			return $expiry ? (int) $expiry : null;
		}
	}
}
