<?php
/**
 * Subscription Object
 *
 * Extends WC_Order because the Edit Order/Subscription interface requires some of the refund related methods
 * from WC_Order that don't exist in WC_Abstract_Order (which would seem the more appropriate choice)
 *
 * @class    WPS_Subscription
 * @version  1.0.0 - Migrated from WooCommerce Subscriptions v2.0
 * @package  WooCommerce Subscriptions/Classes
 * @category Class
 * @author   Brent Shepherd
 */

class WPS_Subscription extends WC_Order {

	/** @public WC_Order Stores order data for the order in which the subscription was purchased (if any) */
	protected $order = null;

	/** @public string Order type */
	public $order_type = 'wps_subscriptions';

	/** @private int Stores get_payment_count when used multiple times */
	private $cached_payment_count = null;

	/**
	 * Which data store to load. WC 3.0+ property.
	 *
	 * @var string
	 */
	// protected $data_store_name = 'subscription';

	/**
	 * This is the name of this object type. WC 3.0+ property.
	 *
	 * @var string
	 */
	// protected $object_type = 'subscription';

	/**
	 * Stores the $this->is_editable() returned value in memory
	 *
	 * @var bool
	 */
	private $editable;

	/** @private array The set of valid date types that can be set on the subscription */
	protected $valid_date_types = array();


	/**
	 * Initializes a specific subscription if the ID is passed, otherwise a new and empty instance of a subscription.
	 *
	 * This class should NOT be instantiated, instead the functions wcs_create_subscription() and wcs_get_subscription()
	 * should be used.
	 *
	 * @param int|WPS_Subscription $subscription Subscription to read.
	 */
	public function __construct( $subscription = 0 ) {

		parent::__construct( $subscription );
		$this->order_type = 'wps_subscriptions';
	}

    /**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'wps_subscriptions';
	}

    /**
	 * Updates status of the subscription
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @param string $note (default: '') Optional note to add
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {

		if ( ! $this->get_id() ) {
			return;
		}

		// Standardise status names.
		$new_status     = ( 'wc-' === substr( $new_status, 0, 3 ) ) ? substr( $new_status, 3 ) : $new_status;
		$new_status_key = 'wc-' . $new_status;
		$old_status     = ( 'wc-' === substr( $this->get_status(), 0, 3 ) ) ? substr( $this->get_status(), 3 ) : $this->get_status();
		$old_status_key = 'wc-' . $old_status;

		if ( $new_status !== $old_status ) {

			try {
				var_dump($new_status);die;

				$this->set_status( $new_status, $note, $manual );

				// Make sure status is saved when WC 3.0+ is active, similar to WC_Order::update_status() with WC 3.0+ - set_status() can be used to avoid saving.
				$this->save();

			} catch ( Exception $e ) {
	
				// Make sure the old status is restored
				$this->set_status( $old_status, $note, $manual );

				// There is no status transition
				$this->status_transition = false;

				// translators: 1: subscription status, 2: error message.
				$this->add_order_note( sprintf( __( 'Unable to change subscription status to "%1$s". Exception: %2$s', 'woocommerce-subscriptions' ), $new_status, $e->getMessage() ) );

				// Make sure status is saved when WC 3.0+ is active, similar to WC_Order::update_status() with WC 3.0+ - set_status() can be used to avoid saving.
				$this->save();

				throw $e;
			}
		}
	}

    /**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		// Use local copy of status transition value.
		$status_transition = $this->status_transition;

		// If we're not currently in the midst of a status transition, bail early.
		if ( ! $status_transition ) {
			return;
		}

		try {
			if ( ! empty( $status_transition['from'] ) ) {
				$transition_note = sprintf(
					/* translators: 1: old subscription status 2: new subscription status */
					__( 'Status changed from %1$s to %2$s.', 'woocommerce-subscriptions' ),
					$status_transition['from'],
					$status_transition['to']
				);
			} else {
				/* translators: %s: new order status */
				$transition_note = sprintf( __( 'Status set to %s.', 'woocommerce-subscriptions' ), $status_transition['to'] );
			}

			// Note the transition occurred.
			$this->add_order_note( trim( "{$status_transition['note']} {$transition_note}" ), 0, $status_transition['manual'] );
		} catch ( Exception $e ) {
			$this->add_order_note( __( 'Error during subscription status transition.', 'woocommerce-subscriptions' ) . ' ' . $e->getMessage() );
		}

		// This has run, so reset status transition variable
		$this->status_transition = false;
	}

    /**
	 * Sets the subscription status.
	 *
	 * Overrides the WC Order set_status() function to handle 'draft' and 'auto-draft' statuses for a subscription.
	 *
	 * 'draft' and 'auto-draft' statuses are WP statuses applied to the post when a subscription is created via admin. When
	 * a subscription is being read from the database, and the status is set to the post's 'draft' or 'auto-draft' status, the
	 * subscription status is treated as the default status - 'pending'.
	 *
	 * @since 5.1.0
	 *
	 * @param string $new_status The new status.
	 * @param string $note       Optional. The note to add to the subscription.
	 * @param bool   $manual     Optional. Is the status change triggered manually? Default is false.
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		if ( ! $this->object_read && in_array( $new_status, [ 'draft', 'auto-draft' ], true ) ) {
			$new_status = 'pending';
		}

		return parent::set_status( $new_status, $note, $manual_update );
	}
}