<?php
/**
 * Exit if accessed directly
 *
 * @since      1.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * This is construct of class where all susbcriptions listed.
 *
 * @name Subscriptions_For_Woocommerce_Admin_Subscription_List
 * @since      1.0.0
 * @category Class
 * @author WP Swings<ticket@wpswings.com>
 * @link https://www.wpswing.com/
 */
class Subscriptions_For_Woocommerce_Admin_Subscription_List extends WP_List_Table {
	/**
	 * This is variable which is used for the store all the data.
	 *
	 * @var array $example_data variable for store data.
	 */
	public $example_data;

	/**
	 * This is variable which is used for the total count.
	 *
	 * @var array $wps_total_count variable for total count.
	 */
	public $wps_total_count;


	/**
	 * This construct colomns in susbcription table.
	 *
	 * @name get_columns.
	 * @since      1.0.0
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	public function get_columns() {

		$columns = array(
			'cb'                            => '<input type="checkbox" />',
			'subscription_id'               => __( 'Subscription ID', 'subscriptions-for-woocommerce' ),
			'parent_order_id'               => __( 'Parent Order ID', 'subscriptions-for-woocommerce' ),
			'status'                        => __( 'Status', 'subscriptions-for-woocommerce' ),
			'product_name'                  => __( 'Product Name', 'subscriptions-for-woocommerce' ),
			'recurring_amount'              => __( 'Recurring Amount', 'subscriptions-for-woocommerce' ),
			'payment_type'                  => __( 'Payment Method', 'subscriptions-for-woocommerce' ),
			'user_name'                     => __( 'User Name', 'subscriptions-for-woocommerce' ),
			'next_payment_date'             => __( 'Next Payment Date', 'subscriptions-for-woocommerce' ),
			'subscriptions_expiry_date'     => __( 'Subscription Expiry Date', 'subscriptions-for-woocommerce' ),

		);
		return apply_filters( 'wps_sfw_column_subscription_table', $columns );
	}

	/**
	 * Get Cancel url.
	 *
	 * @name wps_sfw_cancel_url.
	 * @since      1.0.0
	 * @param int    $subscription_id subscription_id.
	 * @param String $status status.
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	public function wps_sfw_cancel_url( $subscription_id, $status ) {
		$wps_link = add_query_arg(
			array(
				'wps_subscription_id'               => $subscription_id,
				'wps_subscription_status_admin'     => $status,
			)
		);

		$wps_link = wp_nonce_url( $wps_link, $subscription_id . $status );
		$actions = array(
			'wps_sfw_cancel' => '<a href="' . $wps_link . '">' . __( 'Cancel', 'subscriptions-for-woocommerce' ) . '</a>',

		);
		return $actions;
	}
	/**
	 * This show susbcriptions table list.
	 *
	 * @name column_default.
	 * @since      1.0.0
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 * @param array  $item  array of the items.
	 * @param string $column_name name of the colmn.
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'subscription_id':
				$actions = array();
				$wps_sfw_status = array( 'active' );
				$wps_sfw_status = apply_filters( 'wps_sfw_status_array', $wps_sfw_status );
				if ( in_array( $item['status'], $wps_sfw_status ) ) {
					$actions = $this->wps_sfw_cancel_url( $item['subscription_id'], $item['status'] );
				}
				$actions = apply_filters( 'wps_sfw_add_action_details', $actions, $item['subscription_id'] );
				return $item[ $column_name ] . $this->row_actions( $actions );
			case 'parent_order_id':
				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$html = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				} else {
					$html = '<a href="' . esc_url( get_edit_post_link( $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				}
				return $html;
			case 'status':
				return $item[ $column_name ];
			case 'product_name':
				return $item[ $column_name ];
			case 'recurring_amount':
				return $item[ $column_name ];
			case 'payment_type':
				return $item[ $column_name ];
			case 'user_name':
				return $item[ $column_name ];
			case 'next_payment_date':
				return $item[ $column_name ];
			case 'subscriptions_expiry_date':
				return $item[ $column_name ];
			default:
				return apply_filters( 'wps_sfw_add_case_column', false, $column_name, $item );
		}
	}

	/**
	 * Perform admin bulk action setting for susbcription table.
	 *
	 * @name process_bulk_action.
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	public function process_bulk_action() {

		if ( 'bulk-delete' === $this->current_action() ) {

			if ( isset( $_POST['susbcription_list_table'] ) ) {
				$susbcription_list_table = sanitize_text_field( wp_unslash( $_POST['susbcription_list_table'] ) );
				if ( wp_verify_nonce( $susbcription_list_table, 'susbcription_list_table' ) ) {
					if ( isset( $_POST['wps_sfw_subscriptions_ids'] ) && ! empty( $_POST['wps_sfw_subscriptions_ids'] ) ) {
						$all_id = map_deep( wp_unslash( $_POST['wps_sfw_subscriptions_ids'] ), 'sanitize_text_field' );
						foreach ( $all_id as $key => $value ) {
							if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
								$subscription = new WPS_Subscription( $value );
								$subscription->delete( true );
							} else {
								wp_delete_post( $value, true );
							}
						}
						?>
							<div class="notice notice-success is-dismissible"> 
								<p><strong><?php esc_html_e( 'Subscriptions Deleted Successfully', 'subscriptions-for-woocommerce' ); ?></strong></p>
							</div>
						<?php
					}
				}
			}
		} elseif ( 'bulk-cancel' === $this->current_action() ) {
			if ( isset( $_POST['susbcription_list_table'] ) ) {
				$susbcription_list_table = sanitize_text_field( wp_unslash( $_POST['susbcription_list_table'] ) );
				if ( wp_verify_nonce( $susbcription_list_table, 'susbcription_list_table' ) ) {
					if ( isset( $_POST['wps_sfw_subscriptions_ids'] ) && ! empty( $_POST['wps_sfw_subscriptions_ids'] ) ) {
						$all_id = map_deep( wp_unslash( $_POST['wps_sfw_subscriptions_ids'] ), 'sanitize_text_field' );
						foreach ( $all_id as $key => $value ) {
							do_action( 'wps_sfw_subscription_cancel', $value, 'Cancel' );
							wps_sfw_update_meta_data( $value, 'wps_subscription_cancelled_by', 'by_admin_bulk_action' );
							wps_sfw_update_meta_data( $value, 'wps_subscription_cancelled_date', time() );
						}
						?>
							<div class="notice notice-success is-dismissible"> 
								<p><strong><?php esc_html_e( 'Subscriptions Cancelled Successfully', 'subscriptions-for-woocommerce' ); ?></strong></p>
							</div>
						<?php
					}
				}
			}
		}
		do_action( 'wps_sfw_process_bulk_reset_option', $this->current_action(), $_POST );
	}
	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @name process_bulk_action.
	 * @since      1.0.0
	 * @return array
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'subscriptions-for-woocommerce' ),
			'bulk-cancel' => __( 'Cancel', 'subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'wps_sfw_bulk_option', $actions );
	}

	/**
	 * Returns an associative array containing the bulk action for sorting.
	 *
	 * @name get_sortable_columns.
	 * @since      1.0.0
	 * @return array
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'subscription_id'   => array( 'subscription_id', false ),
			'parent_order_id'  => array( 'parent_order_id', false ),
			'status' => array( 'status', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Prepare items for sorting.
	 *
	 * @name prepare_items.
	 * @since      1.0.0
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	public function prepare_items() {
		$per_page              = 10;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		$current_page = $this->get_pagenum();

		$this->example_data = $this->wps_sfw_get_subscription_list();
		$data               = $this->example_data;
		usort( $data, array( $this, 'wps_sfw_usort_reorder' ) );
		$total_items = $this->wps_total_count;
		$this->items  = $data;
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}



	/**
	 * Return sorted associative array.
	 *
	 * @name wps_sfw_usort_reorder.
	 * @since      1.0.0
	 * @return array
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 * @param array $cloumna column of the susbcriptions.
	 * @param array $cloumnb column of the susbcriptions.
	 */
	public function wps_sfw_usort_reorder( $cloumna, $cloumnb ) {

		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'subscription_id';
		$order   = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc';

		if ( is_numeric( $cloumna[ $orderby ] ) && is_numeric( $cloumnb[ $orderby ] ) ) {
			if ( $cloumna[ $orderby ] == $cloumnb[ $orderby ] ) {
				return 0;
			} elseif ( $cloumna[ $orderby ] < $cloumnb[ $orderby ] ) {
				$result = -1;
				return ( 'asc' === $order ) ? $result : -$result;
			} elseif ( $cloumna[ $orderby ] > $cloumnb[ $orderby ] ) {
				$result = 1;
				return ( 'asc' === $order ) ? $result : -$result;
			}
		} else {
			$result = strcmp( $cloumna[ $orderby ], $cloumnb[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
	}

	/**
	 * THis function is used for the add the checkbox.
	 *
	 * @name column_cb.
	 * @since      1.0.0
	 * @return array
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 * @param array $item array of the items.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="wps_sfw_subscriptions_ids[]" value="%s" />',
			$item['subscription_id']
		);
	}


	/**
	 * This function used to get all susbcriptions list.
	 *
	 * @name wps_sfw_get_subscription_list.
	 * @since      1.0.0
	 * @return array
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	public function wps_sfw_get_subscription_list() {
		$wps_sfw_pro_plugin_activated = false;
		if ( in_array( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$wps_sfw_pro_plugin_activated = true;
		}

		$current_page = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : 1;

		// get the data by pagination.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$offset = ( $current_page - 1 ) * 10;
			$args = array(
				'number' => 10,
				'offset' => $offset,
				'return' => 'ids',
				'type'   => 'wps_subscriptions',
				'meta_query' => array(
					'key'   => 'wps_customer_id',
					'compare' => 'EXISTS',
				),
			);
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				// Logic to fetch subscription using subscription id or parent id.
				$maybe_subscription_or_parent_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				$sub_id = wps_sfw_get_meta_data( $maybe_subscription_or_parent_id, 'wps_parent_order', true );
				if ( $sub_id ) {
					$maybe_subscription_or_parent_id = $sub_id;
				}
				if ( $maybe_subscription_or_parent_id ) {
					$args['meta_query'] = array(
						array(
							'key'   => 'wps_parent_order',
							'value' => $maybe_subscription_or_parent_id,
							'compare' => 'LIKE',
						),
					);
				} else {
					$username_or_email = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					// Logic to fetch subscription using username or email.

					$user = get_user_by( 'email', $username_or_email );

					// If no user is found by email, try to get by username.
					if ( ! $user ) {
						$user = get_user_by( 'login', $username_or_email );
					}
					$customer_id = $user ? $user->ID : false;

					$args['meta_query'] = array(
						array(
							'key'   => 'wps_customer_id',
							'value' => $customer_id,
							'compare' => 'LIKE',
						),
					);
				}
			}
			$wps_subscriptions = wc_get_orders( $args );
		} else {

			$args = array(
				'posts_per_page' => 10,
				'paged' => $current_page,
				'post_type'   => 'wps_subscriptions',
				'post_status' => 'wc-wps_renewal',
				'meta_query' => array(
					array(
						'key'   => 'wps_customer_id',
						'compare' => 'EXISTS',
					),
				),
				'fields' => 'ids',
			);

			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				// Logic to fetch subscription using subscription id or parent id.
				$maybe_subscription_or_parent_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				$sub_id = wps_sfw_get_meta_data( $maybe_subscription_or_parent_id, 'wps_parent_order', true );
				if ( $sub_id ) {
					$maybe_subscription_or_parent_id = $sub_id;
				}
				if ( $maybe_subscription_or_parent_id ) {
					$args['meta_query'] = array(
						array(
							'key'   => 'wps_parent_order',
							'value' => $maybe_subscription_or_parent_id,
							'compare' => 'LIKE',
						),
					);
				} else {
					$username_or_email = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					// Logic to fetch subscription using username or email.

					$user = get_user_by( 'email', $username_or_email );

					// If no user is found by email, try to get by username.
					if ( ! $user ) {
						$user = get_user_by( 'login', $username_or_email );
					}
					$customer_id = $user ? $user->ID : false;

					$args['meta_query'] = array(
						array(
							'key'   => 'wps_customer_id',
							'value' => $customer_id,
							'compare' => 'LIKE',
						),
					);
				}
			}
			$wps_subscriptions = get_posts( $args );
		}

		// Code to get the total item count.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$args2 = array(
				'type'   => 'wps_subscriptions',
				'limit'  => -1,
				'meta_query' => array(
					array(
						'key'   => 'wps_customer_id',
						'compare' => 'EXISTS',
					),
				),
				'return' => 'ids',
			);
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				// Logic to fetch subscription using subscription id or parent id.
				$maybe_subscription_or_parent_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				$sub_id = wps_sfw_get_meta_data( $maybe_subscription_or_parent_id, 'wps_parent_order', true );
				if ( $sub_id ) {
					$maybe_subscription_or_parent_id = $sub_id;
				}
				if ( $maybe_subscription_or_parent_id ) {
					$args2['meta_query'] = array(
						array(
							'key'   => 'wps_parent_order',
							'value' => $maybe_subscription_or_parent_id,
							'compare' => 'LIKE',
						),
					);
				} else {
					$username_or_email = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					// Logic to fetch subscription using username or email.

					$user = get_user_by( 'email', $username_or_email );

					// If no user is found by email, try to get by username.
					if ( ! $user ) {
						$user = get_user_by( 'login', $username_or_email );
					}
					$customer_id = $user ? $user->ID : false;

					$args2['meta_query'] = array(
						array(
							'key'   => 'wps_customer_id',
							'value' => $customer_id,
							'compare' => 'LIKE',
						),
					);
				}
			}
			$wps_subscriptions2 = wc_get_orders( $args2 );

		} else {
			$args2 = array(
				'numberposts' => -1,
				'post_type'   => 'wps_subscriptions',
				'post_status' => 'wc-wps_renewal',
				'meta_query' => array(
					array(
						'key'   => 'wps_customer_id',
						'compare' => 'EXISTS',
					),
				),
				'fields' => 'ids',
			);
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				// Logic to fetch subscription using subscription id or parent id.
				$maybe_subscription_or_parent_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				$sub_id = wps_sfw_get_meta_data( $maybe_subscription_or_parent_id, 'wps_parent_order', true );
				if ( $sub_id ) {
					$maybe_subscription_or_parent_id = $sub_id;
				}
				if ( $maybe_subscription_or_parent_id ) {
					$args2['meta_query'] = array(
						array(
							'key'   => 'wps_parent_order',
							'value' => $maybe_subscription_or_parent_id,
							'compare' => 'LIKE',
						),
					);
				} else {
					$username_or_email = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					// Logic to fetch subscription using username or email.

					$user = get_user_by( 'email', $username_or_email );

					// If no user is found by email, try to get by username.
					if ( ! $user ) {
						$user = get_user_by( 'login', $username_or_email );
					}
					$customer_id = $user ? $user->ID : false;

					$args2['meta_query'] = array(
						array(
							'key'   => 'wps_customer_id',
							'value' => $customer_id,
							'compare' => 'LIKE',
						),
					);
				}
			}
			$wps_subscriptions2 = get_posts( $args2 );
		}
		$total_count = count( $wps_subscriptions2 );

		// redirection from order edit page link to specific subscription .
		if ( isset( $_GET['wps_order_type'] ) && 'subscription' == $_GET['wps_order_type'] ) {
			$order_id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : 0;
			$wps_subs_id = wps_sfw_get_meta_data( $order_id, 'wps_parent_order', true );
			$args2['meta_query'] = array(
				array(
					'key'   => 'wps_parent_order',
					'value' => $wps_subs_id,
					'compare' => 'LIKE',
				),
			);

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$wps_subscriptions = wc_get_orders( $args2 );
			} else {
				$wps_subscriptions = get_posts( $args2 );
			}
		}

		$wps_subscriptions_data = array();

		if ( isset( $wps_subscriptions ) && ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
			foreach ( $wps_subscriptions as $id ) {

				$parent_order_id   = wps_sfw_get_meta_data( $id, 'wps_parent_order', true );
				if ( function_exists( 'wps_sfw_check_valid_order' ) && ! wps_sfw_check_valid_order( $parent_order_id ) ) {
					$total_count = --$total_count;
					continue;
				}
				$wps_subscription_status = wps_sfw_get_meta_data( $id, 'wps_subscription_status', true );
				$product_name            = wps_sfw_get_meta_data( $id, 'product_name', true );
				$wps_recurring_total     = wps_sfw_get_meta_data( $id, 'wps_recurring_total', true );
				$wps_curr_args           = array();

				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$susbcription = new WPS_Subscription( $id );
				} else {
					$susbcription = wc_get_order( $id );
				}
				if ( isset( $susbcription ) && ! empty( $susbcription ) ) {
					$wps_recurring_total = $susbcription->get_total();
					$wps_curr_args = array(
						'currency' => $susbcription->get_currency(),
					);
				}
				$wps_recurring_total = wps_sfw_recerring_total_price_list_table_callback( wc_price( $wps_recurring_total, $wps_curr_args ), $id );

				$wps_recurring_total = apply_filters( 'wps_sfw_recerring_total_price_list_table', $wps_recurring_total, $id );
				$wps_next_payment_date   = wps_sfw_get_meta_data( $id, 'wps_next_payment_date', true );
				$wps_susbcription_end   = wps_sfw_get_meta_data( $id, 'wps_susbcription_end', true );
				if ( $wps_next_payment_date === $wps_susbcription_end ) {
					$wps_next_payment_date = '';
				}

				if ( 'on-hold' === $wps_subscription_status ) {
					$wps_next_payment_date = '';
					$wps_recurring_total = '---';
				}
				if ( 'cancelled' === $wps_subscription_status ) {
					$wps_next_payment_date = '';
					$wps_susbcription_end = '';
					$wps_recurring_total = '---';
				}
				$wps_customer_id   = wps_sfw_get_meta_data( $id, 'wps_customer_id', true );
				$user = get_user_by( 'id', $wps_customer_id );

				if ( ! $wps_sfw_pro_plugin_activated ) {
					$subp_id = wps_sfw_get_meta_data( $id, 'product_id', true );
					$check_variable = wps_sfw_get_meta_data( $subp_id, 'wps_sfw_variable_product', true );
					if ( 'yes' === $check_variable ) {
						continue;
					}
				}

				$is_payment_manual = wps_sfw_get_meta_data( $id, 'wps_wsp_payment_type', true );
				
				$parent_order = wc_get_order( $parent_order_id );
				$payment_type = $parent_order ? $parent_order->get_payment_method_title() : null;
				if ( $is_payment_manual ) {
					$payment_type = $payment_type . ' Via Manual Method';
				}
				$user_nicename = isset( $user->user_login ) ? $user->user_login : '';

				if ( 'active' === $wps_subscription_status ) {
					$wps_subscription_status = esc_html__( 'active', 'subscriptions-for-woocommerce' );
				} elseif ( 'on-hold' === $wps_subscription_status ) {
					$wps_subscription_status = esc_html__( 'on-hold', 'subscriptions-for-woocommerce' );
				} elseif ( 'cancelled' === $wps_subscription_status ) {
					$wps_subscription_status = esc_html__( 'cancelled', 'subscriptions-for-woocommerce' );
				} elseif ( 'paused' === $wps_subscription_status ) {
					$wps_subscription_status = esc_html__( 'paused', 'subscriptions-for-woocommerce' );
				} elseif ( 'pending' === $wps_subscription_status ) {
					$wps_subscription_status = esc_html__( 'pending', 'subscriptions-for-woocommerce' );
				} elseif ( 'expired' === $wps_subscription_status ) {
					$wps_subscription_status = esc_html__( 'expired', 'subscriptions-for-woocommerce' );
				}
				$wps_subscriptions_data[] = apply_filters(
					'wps_sfw_subs_table_data',
					array(
						'subscription_id'           => $id,
						'parent_order_id'           => $parent_order_id,
						'status'                    => $wps_subscription_status,
						'product_name'              => $product_name,
						'recurring_amount'          => apply_filters( 'wps_sfw_display_recurring_price', $wps_recurring_total, $id ),
						'payment_type'              => $payment_type,
						'user_name'                 => $user_nicename,
						'next_payment_date'         => wps_sfw_get_the_wordpress_date_format( $wps_next_payment_date ),
						'subscriptions_expiry_date' => wps_sfw_get_the_wordpress_date_format( $wps_susbcription_end ),
					)
				);
			}
		}
		$this->wps_total_count = $total_count;
		return $wps_subscriptions_data;
	}

	/**
	 * Create the extra table option.
	 *
	 * @name extra_tablenav.
	 * @since      1.0.0
	 * @param string $which which.
	 * @author WP Swings<ticket@wpswings.com>
	 * @link https://www.wpswing.com/
	 */
	public function extra_tablenav( $which ) {
		// Add list option.
		do_action( 'wps_sfw_extra_tablenav_html', $which );
	}
}

if ( isset( $_GET['wps_subscription_view_renewal_order'] ) && isset( $_GET['wps_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) && defined( 'WOOCOMMERCE_SUBSCRIPTIONS_PRO_DIR_PATH' ) ) {
	$wps_status   = sanitize_text_field( wp_unslash( $_GET['wps_subscription_view_renewal_order'] ) );
	$subscription_id = sanitize_text_field( wp_unslash( $_GET['wps_subscription_id'] ) );
	if ( wps_sfw_check_valid_subscription( $subscription_id ) ) {
		global $wps_subscription_id;
		$wps_subscription_id = $subscription_id;
		require_once WOOCOMMERCE_SUBSCRIPTIONS_PRO_DIR_PATH . 'admin/partials/class-woocommerce-subscriptions-pro-view-renewal-list.php';
	}
} else {
	?>
	<div class="wps_sfw_subscription_table_inner_wrap">
	<h3 class="wp-heading-inline" id="wps_sfw_heading"><?php esc_html_e( 'Subscriptions', 'subscriptions-for-woocommerce' ); ?></h3>
	<?php do_action( 'wps_sfw_add_button_manual_subscription' ); ?>
	</div>
		<form method="post">
		<input type="hidden" name="page" value="susbcription_list_table">
		<?php wp_nonce_field( 'susbcription_list_table', 'susbcription_list_table' ); ?>
		<div class="wps_sfw_list_table">
			<?php
			$mylisttable = new Subscriptions_For_Woocommerce_Admin_Subscription_List();
			$mylisttable->prepare_items();
			$mylisttable->search_box( __( 'Search Order', 'subscriptions-for-woocommerce' ), 'wps-sfw-order' );
			$mylisttable->display();
			?>
		</div>
	</form>
	<?php
}
