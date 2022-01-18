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

/**
 * This is construct of class where all susbcriptions listed.
 *
 * @name Subscriptions_For_Woocommerce_Admin_Subscription_List
 * @since      1.0.0
 * @category Class
 * @author makewebbetter<ticket@makewebbetter.com>
 * @link https://www.makewebbetter.com/
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
	 * @var array $mwb_total_count variable for total count.
	 */
	public $mwb_total_count;


	/**
	 * This construct colomns in susbcription table.
	 *
	 * @name get_columns.
	 * @since      1.0.0
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	public function get_columns() {

		$columns = array(
			'cb'                            => '<input type="checkbox" />',
			'subscription_id'               => __( 'Subscription ID', 'subscriptions-for-woocommerce' ),
			'parent_order_id'               => __( 'Parent Order ID', 'subscriptions-for-woocommerce' ),
			'status'                        => __( 'Status', 'subscriptions-for-woocommerce' ),
			'product_name'                  => __( 'Product Name', 'subscriptions-for-woocommerce' ),
			'recurring_amount'              => __( 'Recurring Amount', 'subscriptions-for-woocommerce' ),
			'user_name'                     => __( 'User Name', 'subscriptions-for-woocommerce' ),
			'next_payment_date'             => __( 'Next Payment Date', 'subscriptions-for-woocommerce' ),
			'subscriptions_expiry_date'     => __( 'Subscription Expiry Date', 'subscriptions-for-woocommerce' ),

		);
		return apply_filters( 'mwb_sfw_column_subscription_table', $columns );
	}

	/**
	 * Get Cancel url.
	 *
	 * @name mwb_sfw_cancel_url.
	 * @since      1.0.0
	 * @param int    $subscription_id subscription_id.
	 * @param String $status status.
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	public function mwb_sfw_cancel_url( $subscription_id, $status ) {
		$mwb_link = add_query_arg(
			array(
				'mwb_subscription_id'               => $subscription_id,
				'mwb_subscription_status_admin'     => $status,
			)
		);

		$mwb_link = wp_nonce_url( $mwb_link, $subscription_id . $status );
		$actions = array(
			'mwb_sfw_cancel' => '<a href="' . $mwb_link . '">' . __( 'Cancel', 'subscriptions-for-woocommerce' ) . '</a>',

		);
		return $actions;
	}
	/**
	 * This show susbcriptions table list.
	 *
	 * @name column_default.
	 * @since      1.0.0
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 * @param array  $item  array of the items.
	 * @param string $column_name name of the colmn.
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'subscription_id':
				$actions = array();
				$mwb_sfw_status = array( 'active' );
				$mwb_sfw_status = apply_filters( 'mwb_sfw_status_array', $mwb_sfw_status );
				if ( in_array( $item['status'], $mwb_sfw_status ) ) {
					$actions = $this->mwb_sfw_cancel_url( $item['subscription_id'], $item['status'] );
				}
				$actions = apply_filters( 'mwb_sfw_add_action_details', $actions, $item['subscription_id'] );
				return $item[ $column_name ] . $this->row_actions( $actions );
			case 'parent_order_id':
				$html = '<a href="' . esc_url( get_edit_post_link( $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				return $html;
			case 'status':
				return $item[ $column_name ];
			case 'product_name':
				return $item[ $column_name ];
			case 'recurring_amount':
				return $item[ $column_name ];
			case 'user_name':
				return $item[ $column_name ];
			case 'next_payment_date':
				return $item[ $column_name ];
			case 'subscriptions_expiry_date':
				return $item[ $column_name ];
			default:
				return apply_filters( 'mwb_sfw_add_case_column', false, $column_name, $item );
		}
	}

	/**
	 * Perform admin bulk action setting for susbcription table.
	 *
	 * @name process_bulk_action.
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	public function process_bulk_action() {

		if ( 'bulk-delete' === $this->current_action() ) {

			if ( isset( $_POST['susbcription_list_table'] ) ) {
				$susbcription_list_table = sanitize_text_field( wp_unslash( $_POST['susbcription_list_table'] ) );
				if ( wp_verify_nonce( $susbcription_list_table, 'susbcription_list_table' ) ) {
					if ( isset( $_POST['mwb_sfw_subscriptions_ids'] ) && ! empty( $_POST['mwb_sfw_subscriptions_ids'] ) ) {
						$all_id = map_deep( wp_unslash( $_POST['mwb_sfw_subscriptions_ids'] ), 'sanitize_text_field' );
						foreach ( $all_id as $key => $value ) {
							 wp_delete_post( $value, true );
						}
					}
				}
			}
			?>
			<div class="notice notice-success is-dismissible"> 
				<p><strong><?php esc_html_e( 'Subscriptions Deleted Successfully', 'subscriptions-for-woocommerce' ); ?></strong></p>
			</div>
			<?php
		}
		do_action( 'mwb_sfw_process_bulk_reset_option', $this->current_action(), $_POST );

	}
	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @name process_bulk_action.
	 * @since      1.0.0
	 * @return array
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'mwb_sfw_bulk_option', $actions );
	}

	/**
	 * Returns an associative array containing the bulk action for sorting.
	 *
	 * @name get_sortable_columns.
	 * @since      1.0.0
	 * @return array
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
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
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	public function prepare_items() {
		$per_page              = 10;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		$current_page = $this->get_pagenum();

		$this->example_data = $this->mwb_sfw_get_subscription_list();
		$data               = $this->example_data;
		usort( $data, array( $this, 'mwb_sfw_usort_reorder' ) );
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$total_items = $this->mwb_total_count;
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
	 * @name mwb_sfw_usort_reorder.
	 * @since      1.0.0
	 * @return array
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 * @param array $cloumna column of the susbcriptions.
	 * @param array $cloumnb column of the susbcriptions.
	 */
	public function mwb_sfw_usort_reorder( $cloumna, $cloumnb ) {

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
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 * @param array $item array of the items.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="mwb_sfw_subscriptions_ids[]" value="%s" />',
			$item['subscription_id']
		);
	}


	/**
	 * This function used to get all susbcriptions list.
	 *
	 * @name mwb_sfw_get_subscription_list.
	 * @since      1.0.0
	 * @return array
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	public function mwb_sfw_get_subscription_list() {
		$mwb_sfw_pro_plugin_activated = false;
		if ( in_array( 'woocommerce-subscriptions-pro/woocommerce-subscriptions-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$mwb_sfw_pro_plugin_activated = true;
		}

		$args = array(
			'numberposts' => -1,
			'post_type'   => 'mwb_subscriptions',
			'post_status' => 'wc-mwb_renewal',
			'meta_query' => array(
				array(
					'key'   => 'mwb_customer_id',
					'compare' => 'EXISTS',
				),
			),
		);

		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$data           = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
			$args['meta_query'] = array(
				array(
					'key'   => 'mwb_parent_order',
					'value' => $data,
					'compare' => 'LIKE',
				),
			);
		}

		$mwb_subscriptions = get_posts( $args );

		$total_count = count( $mwb_subscriptions );

		$mwb_subscriptions_data = array();

		if ( isset( $mwb_subscriptions ) && ! empty( $mwb_subscriptions ) && is_array( $mwb_subscriptions ) ) {
			foreach ( $mwb_subscriptions as $key => $value ) {

				$parent_order_id   = get_post_meta( $value->ID, 'mwb_parent_order', true );

				if ( function_exists( 'mwb_sfw_check_valid_order' ) && ! mwb_sfw_check_valid_order( $parent_order_id ) ) {
					$total_count = --$total_count;
					continue;
				}
				$mwb_subscription_status   = get_post_meta( $value->ID, 'mwb_subscription_status', true );
				$product_name   = get_post_meta( $value->ID, 'product_name', true );
				$mwb_recurring_total   = get_post_meta( $value->ID, 'mwb_recurring_total', true );
				$mwb_curr_args = array();
				$susbcription = wc_get_order( $value->ID );
				if ( isset( $susbcription ) && ! empty( $susbcription ) ) {
					$mwb_recurring_total = $susbcription->get_total();
					$mwb_curr_args = array(
						'currency' => $susbcription->get_currency(),
					);
				}

				$mwb_recurring_total = mwb_sfw_recerring_total_price_list_table_callback( wc_price( $mwb_recurring_total, $mwb_curr_args ), $value->ID );

				$mwb_recurring_total = apply_filters( 'mwb_sfw_recerring_total_price_list_table', $mwb_recurring_total, $value->ID );

				$mwb_next_payment_date   = get_post_meta( $value->ID, 'mwb_next_payment_date', true );
				$mwb_susbcription_end   = get_post_meta( $value->ID, 'mwb_susbcription_end', true );
				if ( $mwb_next_payment_date === $mwb_susbcription_end ) {
					$mwb_next_payment_date = '';
				}

				if ( 'on-hold' === $mwb_subscription_status ) {
					$mwb_next_payment_date = '';
					$mwb_recurring_total = '---';
				}
				$mwb_customer_id   = get_post_meta( $value->ID, 'mwb_customer_id', true );
				$user = get_user_by( 'id', $mwb_customer_id );

				if ( ! $mwb_sfw_pro_plugin_activated ) {
					$subp_id = get_post_meta( $value->ID, 'product_id', true );
					$check_variable = get_post_meta( $subp_id, 'mwb_sfw_variable_product', true );
					if ( 'yes' === $check_variable ) {
						continue;
					}
				}

				$user_nicename = isset( $user->user_nicename ) ? $user->user_nicename : '';
				$mwb_subscriptions_data[] = apply_filters(
					'mwb_sfw_subs_table_data',
					array(
						'subscription_id'           => $value->ID,
						'parent_order_id'           => $parent_order_id,
						'status'                    => $mwb_subscription_status,
						'product_name'              => $product_name,
						'recurring_amount'          => $mwb_recurring_total,
						'user_name'                 => $user_nicename,
						'next_payment_date'         => mwb_sfw_get_the_wordpress_date_format( $mwb_next_payment_date ),
						'subscriptions_expiry_date' => mwb_sfw_get_the_wordpress_date_format( $mwb_susbcription_end ),
					)
				);
			}
		}

		$this->mwb_total_count = $total_count;
		return $mwb_subscriptions_data;
	}

	/**
	 * Create the extra table option.
	 *
	 * @name extra_tablenav.
	 * @since      1.0.0
	 * @param string $which which.
	 * @author makewebbetter<ticket@makewebbetter.com>
	 * @link https://www.makewebbetter.com/
	 */
	public function extra_tablenav( $which ) {
		// Add list option.
		do_action( 'mwb_sfw_extra_tablenav_html', $which );
	}

}

if ( isset( $_GET['mwb_subscription_view_renewal_order'] ) && isset( $_GET['mwb_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$mwb_status   = sanitize_text_field( wp_unslash( $_GET['mwb_subscription_view_renewal_order'] ) );
			$subscription_id = sanitize_text_field( wp_unslash( $_GET['mwb_subscription_id'] ) );
	if ( mwb_sfw_check_valid_subscription( $subscription_id ) ) {
		global $mwb_subscription_id;
		$mwb_subscription_id = $subscription_id;
		require_once WOOCOMMERCE_SUBSCRIPTIONS_PRO_DIR_PATH . 'admin/partials/class-woocommerce-subscriptions-pro-view-renewal-list.php';
	}
} else {
	?>
	<h3 class="wp-heading-inline" id="mwb_sfw_heading"><?php esc_html_e( 'Subscriptions', 'subscriptions-for-woocommerce' ); ?></h3>
		<form method="post">
		<input type="hidden" name="page" value="susbcription_list_table">
		<?php wp_nonce_field( 'susbcription_list_table', 'susbcription_list_table' ); ?>
		<div class="mwb_sfw_list_table">
			<?php
			$mylisttable = new Subscriptions_For_Woocommerce_Admin_Subscription_List();
			$mylisttable->prepare_items();
			$mylisttable->search_box( __( 'Search Order', 'subscriptions-for-woocommerce' ), 'mwb-sfw-order' );
			$mylisttable->display();
			?>
		</div>
	</form>
	<?php
}
