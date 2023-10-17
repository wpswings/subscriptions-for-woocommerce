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
}