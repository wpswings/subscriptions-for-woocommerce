<?php
/**
 * The file that defines the main WPS_Subscription class
 *
 * @link  https://wpswings.com/
 * @since 1.5.6
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/include
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main payment class.
 *
 * This is used to extend the WC_Order class.
 *
 * @since      1.5.7
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/include
 */
class WPS_Subscription extends WC_Order {

	/**
	 * Store the order data
	 *
	 * @public WC_Order Stores order data for the order in which the subscription was purchased (if any)
	 * @var bool
	 */
	protected $order = null;

	/**
	 * Store the order type
	 *
	 * @public string Order type
	 * @var bool
	 */
	public $order_type = 'wps_subscriptions';

	/**
	 * Store the order data
	 *
	 * @private int Stores get_payment_count when used multiple times
	 * @var bool
	 */
	private $cached_payment_count = null;


	/**
	 * Store the order data
	 *
	 * Stores the $this->is_editable() returned value in memory
	 *
	 * @var bool
	 */
	private $editable;

	/**
	 * Store the order data
	 *
	 * @private array The set of valid date types that can be set on the subscription
	 * @var bool
	 */
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
	 * Added this function to avoid the critical error.
	 *
	 * @return null
	 */
	public function get_report_customer_id() {
		return null;
	}
}
