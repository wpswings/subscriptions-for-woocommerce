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
	 * This function is used to create cancel url in subscription table.
	 *
	 * @param int    $subscription_id as id.
	 * @param string $status as status.
	 * @return string
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
	 * This function is used to create on hold url in subscription table.
	 *
	 * @param int    $subscription_id as id.
	 * @param string $status as status.
	 * @return string
	 */
	public function wps_sfw_on_hold_url( $subscription_id, $status ) {
		$wps_link = add_query_arg(
			array(
				'wps_subscription_id'               => $subscription_id,
				'wps_subscription_status_admin_reactivate'     => $status,
			)
		);

		$wps_link = wp_nonce_url( $wps_link, $subscription_id . $status );
		$actions = array(
			'wps_sfw_reactivate' => '<a href="' . $wps_link . '">' . __( 'Reactivate', 'subscriptions-for-woocommerce' ) . '</a>',
		);
		return $actions;
	}

	/**
	 * This function is used to create on hold url in subscription table.
	 *
	 * @param array  $item as item.
	 * @param string $column_name as column name.
	 * @return array
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'subscription_id':
				$actions = array();
				$wps_sfw_status = array( 'active' );
				$wps_sfw_status_on_hold = array( 'on-hold' );
				$wps_sfw_status = apply_filters( 'wps_sfw_status_array', $wps_sfw_status );
				if ( in_array( $item['status'], $wps_sfw_status ) ) {
					$actions = $this->wps_sfw_cancel_url( $item['subscription_id'], $item['status'] );
				}
				$actions = apply_filters( 'wps_sfw_add_action_details', $actions, $item['subscription_id'] );
				if ( in_array( $item['status'], $wps_sfw_status_on_hold ) ) {
					$actions = $this->wps_sfw_on_hold_url( $item['subscription_id'], $item['status'] );
				}
				return $item[ $column_name ] . $this->row_actions( $actions );

			case 'parent_order_id':
				if ( 'manual' == $item[ $column_name ] ) {
					$html = __( 'Manual', 'subscriptions-for-woocommerce' );
				} elseif ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$html = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				} else {
					$html = '<a href="' . esc_url( get_edit_post_link( $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				}
				return $html;

			case 'status':
			case 'product_name':
			case 'recurring_amount':
			case 'payment_type':
			case 'user_name':
			case 'next_payment_date':
			case 'subscriptions_expiry_date':
				return $item[ $column_name ];
			default:
				return apply_filters( 'wps_sfw_add_case_column', false, $column_name, $item );
		}
	}
	/**
	 * Perform admin bulk action setting for subscription table.
	 */
	public function process_bulk_action() {

		if ( 'bulk-delete' === $this->current_action() ) {

			if ( isset( $_REQUEST['susbcription_list_table'] ) ) {
				$susbcription_list_table = sanitize_text_field( wp_unslash( $_REQUEST['susbcription_list_table'] ) );
				if ( wp_verify_nonce( $susbcription_list_table, 'susbcription_list_table' ) ) {
					if ( isset( $_REQUEST['wps_sfw_subscriptions_ids'] ) && ! empty( $_REQUEST['wps_sfw_subscriptions_ids'] ) ) {
						$all_id = map_deep( wp_unslash( $_REQUEST['wps_sfw_subscriptions_ids'] ), 'sanitize_text_field' );
						foreach ( $all_id as $value ) {
							if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
								$subscription = new WPS_Subscription( $value );
								$subscription->delete( true );
							} else {
								wp_delete_post( $value, true );
							}
						}
						echo '<div class="notice notice-success is-dismissible"><p><strong>' .
							esc_html__( 'Subscriptions Deleted Successfully', 'subscriptions-for-woocommerce' ) .
							'</strong></p></div>';
					}
				}
			}
		} elseif ( 'bulk-cancel' === $this->current_action() ) {
			if ( isset( $_REQUEST['susbcription_list_table'] ) ) {
				$susbcription_list_table = sanitize_text_field( wp_unslash( $_REQUEST['susbcription_list_table'] ) );
				if ( wp_verify_nonce( $susbcription_list_table, 'susbcription_list_table' ) ) {
					if ( isset( $_REQUEST['wps_sfw_subscriptions_ids'] ) && ! empty( $_REQUEST['wps_sfw_subscriptions_ids'] ) ) {
						$all_id = map_deep( wp_unslash( $_REQUEST['wps_sfw_subscriptions_ids'] ), 'sanitize_text_field' );
						foreach ( $all_id as $value ) {
							do_action( 'wps_sfw_subscription_cancel', $value, 'Cancel' );
							wps_sfw_update_meta_data( $value, 'wps_subscription_cancelled_by', 'by_admin_bulk_action' );
							wps_sfw_update_meta_data( $value, 'wps_subscription_cancelled_date', time() );
						}
						echo '<div class="notice notice-success is-dismissible"><p><strong>' .
							esc_html__( 'Subscriptions Cancelled Successfully', 'subscriptions-for-woocommerce' ) .
							'</strong></p></div>';
					}
				}
			}
		}
		do_action( 'wps_sfw_process_bulk_reset_option', $this->current_action(), $_REQUEST );
	}

	/**
	 * Get bulk actions for subscription table.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'subscriptions-for-woocommerce' ),
			'bulk-cancel' => __( 'Cancel', 'subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'wps_sfw_bulk_option', $actions );
	}

	/**
	 * This function is used to create checkbox in subscription table.
	 *
	 * @return array
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
	 * This function is used to prepare items in subscription table.
	 *
	 * @since 1.0.0
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
	 * This function is used to short the data in subscription table.
	 *
	 * @param array $cloumna as cloumna.
	 * @param array $cloumnb as cloumnb.
	 * @return int
	 */
	public function wps_sfw_usort_reorder( $cloumna, $cloumnb ) {
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'subscription_id';
		$order   = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc';

		if ( is_numeric( $cloumna[ $orderby ] ) && is_numeric( $cloumnb[ $orderby ] ) ) {
			if ( $cloumna[ $orderby ] == $cloumnb[ $orderby ] ) {
				return 0;
			}
			$result = ( $cloumna[ $orderby ] < $cloumnb[ $orderby ] ) ? -1 : 1;
			return ( 'asc' === $order ) ? $result : -$result;
		} else {
			$result = strcmp( $cloumna[ $orderby ], $cloumnb[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
	}

	/**
	 * This function is used to create checkbox in subscription table.
	 *
	 * @param array $item as item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="wps_sfw_subscriptions_ids[]" value="%s" />',
			$item['subscription_id']
		);
	}

	/**
	 * Fetch subscription list (with new status filter support).
	 */
	/**
	 * This function is used to get all subscriptions list.
	 *
	 * @since 1.0.0
	 */
	public function wps_sfw_get_subscription_list() {
		global $wpdb;

		$wps_sfw_pro_plugin_activated = false;
		if ( in_array( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$wps_sfw_pro_plugin_activated = true;
		}

		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page     = 10;
		$offset       = ( $current_page - 1 ) * $per_page;
		$search_term  = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		$is_hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		$table      = $is_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
		$meta_table = $is_hpos ? $wpdb->prefix . 'wc_orders_meta' : $wpdb->prefix . 'postmeta';
		$id_field   = $is_hpos ? 'id' : 'ID';
		$order_id_field = $is_hpos ? 'order_id' : 'post_id';

		$where = '1=1';
		$search_join = '';

		$where .= $is_hpos ? " AND {$table}.type = 'wps_subscriptions'" : " AND {$table}.post_type = 'wps_subscriptions'";

		// 🔹 Filter by status dropdown.
		$status_filter = isset( $_GET['wps_sfw_filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['wps_sfw_filter_status'] ) ) : '';
		if ( ! empty( $status_filter ) ) {
			// Always append join to avoid overwriting by search join.
			$search_join .= " LEFT JOIN {$meta_table} AS meta_status ON meta_status.{$order_id_field} = {$table}.{$id_field} ";
			$where .= $wpdb->prepare(
				" AND ( meta_status.meta_key = 'wps_subscription_status' AND meta_status.meta_value = %s ) ",
				$status_filter
			);
		}

		// 🔸 Search filter.
		if ( $search_term ) {
			if ( is_numeric( $search_term ) ) {
				$search_join .= " LEFT JOIN {$meta_table} AS meta_search ON meta_search.{$order_id_field} = {$table}.{$id_field}";
				$where .= $wpdb->prepare(
					" AND ( (meta_search.meta_key = 'wps_parent_order' AND meta_search.meta_value LIKE %s) OR {$table}.{$id_field} = %d )",
					'%' . $wpdb->esc_like( $search_term ) . '%',
					$search_term
				);
			} else {
				$user = get_user_by( 'email', $search_term );
				if ( ! $user ) {
					$user = get_user_by( 'login', $search_term );
				}
				if ( $user ) {
					$user_id = $user->ID;
					$search_join .= " LEFT JOIN {$meta_table} AS meta_search_user ON meta_search_user.{$order_id_field} = {$table}.{$id_field}";
					$where .= $wpdb->prepare(
						" AND (meta_search_user.meta_key = 'wps_customer_id' AND meta_search_user.meta_value LIKE %s)",
						'%' . $wpdb->esc_like( $user_id ) . '%'
					);
				}
			}
		}

		// Fetch paginated data.
		$sql = "
		SELECT DISTINCT {$table}.{$id_field}
		FROM {$table}
		INNER JOIN {$meta_table} AS meta ON meta.{$order_id_field} = {$table}.{$id_field}
		$search_join
		WHERE meta.meta_key = 'wps_customer_id'
		AND $where
		ORDER BY {$table}.{$id_field} DESC
		LIMIT %d OFFSET %d
	";
		$wps_subscriptions = $wpdb->get_col( $wpdb->prepare( $sql, $per_page, $offset ) );

		// Get total count.
		$sql_count = "
		SELECT COUNT(DISTINCT {$table}.{$id_field})
		FROM {$table}
		INNER JOIN {$meta_table} AS meta ON meta.{$order_id_field} = {$table}.{$id_field}
		$search_join
		WHERE meta.meta_key = 'wps_customer_id'
		AND $where
	";
		$total_count = $wpdb->get_var( $sql_count );

		$wps_subscriptions_data = array();

		if ( ! empty( $wps_subscriptions ) && is_array( $wps_subscriptions ) ) {
			foreach ( $wps_subscriptions as $id ) {

				$parent_order_id = wps_sfw_get_meta_data( $id, 'wps_parent_order', true );
				if ( 'manual' != $parent_order_id && function_exists( 'wps_sfw_check_valid_order' ) && ! wps_sfw_check_valid_order( $parent_order_id ) ) {
					$total_count = --$total_count;
					continue;
				}

				$wps_subscription_status = wps_sfw_get_meta_data( $id, 'wps_subscription_status', true );
				$product_name            = wps_sfw_get_meta_data( $id, 'product_name', true );
				$wps_recurring_total     = wps_sfw_get_meta_data( $id, 'wps_recurring_total', true );
				$wps_curr_args           = array();

				if ( is_array( $product_name ) ) {
					$product_name = implode( ', ', $product_name );
				}

				$susbcription = $is_hpos ? new WPS_Subscription( $id ) : wc_get_order( $id );
				if ( $susbcription ) {
					$wps_recurring_total = $susbcription->get_total();
					$wps_curr_args = array( 'currency' => $susbcription->get_currency() );
				}

				$wps_recurring_total = wc_price( $wps_recurring_total, $wps_curr_args );
				$wps_next_payment_date = wps_sfw_get_meta_data( $id, 'wps_next_payment_date', true );
				$wps_susbcription_end  = wps_sfw_get_meta_data( $id, 'wps_susbcription_end', true );
				if ( $wps_next_payment_date === $wps_susbcription_end ) {
					$wps_next_payment_date = '';
				}

				if ( in_array( $wps_subscription_status, array( 'on-hold', 'cancelled' ), true ) ) {
					$wps_recurring_total = '---';
				}

				$wps_customer_id = wps_sfw_get_meta_data( $id, 'wps_customer_id', true );
				$user = get_user_by( 'id', $wps_customer_id );
				$parent_order = wc_get_order( $parent_order_id );
				$payment_type = $parent_order ? $parent_order->get_payment_method_title() : '';
				$user_nicename = isset( $user->user_login ) ? $user->user_login : '';

				$wps_subscriptions_data[] = array(
					'subscription_id'           => $id,
					'parent_order_id'           => $parent_order_id,
					'status'                    => $wps_subscription_status,
					'product_name'              => $product_name,
					'recurring_amount'          => $wps_recurring_total,
					'payment_type'              => $payment_type,
					'user_name'                 => $user_nicename,
					'next_payment_date'         => wps_sfw_get_the_wordpress_date_format( $wps_next_payment_date ),
					'subscriptions_expiry_date' => wps_sfw_get_the_wordpress_date_format( $wps_susbcription_end ),
				);
			}
		}

		$this->wps_total_count = $total_count;
		return $wps_subscriptions_data;
	}


	/**
	 * This function is used to create extra tablenav in subscription table.
	 *
	 * @param string $which as which.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {

			// Add this line here — build a clean base URL for the Clear button.
			$current_url = remove_query_arg(
				array( 'wps_sfw_filter_status', 'paged', '_wp_http_referer', '_wpnonce', 'susbcription_list_table' )
			);

			$selected_status = isset( $_GET['wps_sfw_filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['wps_sfw_filter_status'] ) ) : '';
			?>
		<div class="alignleft actions">
			<label for="wps_sfw_filter_status" class="screen-reader-text"><?php esc_html_e( 'Filter by Status', 'subscriptions-for-woocommerce' ); ?></label>
			<select name="wps_sfw_filter_status" id="wps_sfw_filter_status">
				<option value=""><?php esc_html_e( 'All Statuses', 'subscriptions-for-woocommerce' ); ?></option>
				<option value="active" <?php selected( $selected_status, 'active' ); ?>><?php esc_html_e( 'Active', 'subscriptions-for-woocommerce' ); ?></option>
				<option value="on-hold" <?php selected( $selected_status, 'on-hold' ); ?>><?php esc_html_e( 'On Hold', 'subscriptions-for-woocommerce' ); ?></option>
				<option value="cancelled" <?php selected( $selected_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'subscriptions-for-woocommerce' ); ?></option>
				<option value="pending" <?php selected( $selected_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'subscriptions-for-woocommerce' ); ?></option>
				<option value="paused" <?php selected( $selected_status, 'paused' ); ?>><?php esc_html_e( 'Paused', 'subscriptions-for-woocommerce' ); ?></option>
				<option value="expired" <?php selected( $selected_status, 'expired' ); ?>><?php esc_html_e( 'Expired', 'subscriptions-for-woocommerce' ); ?></option>
			</select>
			<input type="submit" id="wps_sfw_filter_submit" class="button" value="<?php esc_attr_e( 'Apply', 'subscriptions-for-woocommerce' ); ?>" />
			<?php if ( ! empty( $selected_status ) ) : ?>
				<a href="<?php echo esc_url( $current_url ); ?>" class="button"><?php esc_html_e( 'Clear', 'subscriptions-for-woocommerce' ); ?></a>
			<?php endif; ?>
		</div>
			<?php
		}
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
		<!-- <form method="post"> -->
			<!-- <form method="get"> -->
<form method="get">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>">
	<input type="hidden" name="sfw_tab" value="<?php echo isset( $_GET['sfw_tab'] ) ? esc_attr( $_GET['sfw_tab'] ) : ''; ?>">

		<!-- <input type="hidden" name="page" value="susbcription_list_table"> -->
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