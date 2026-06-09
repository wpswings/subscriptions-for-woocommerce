<?php
/**
 * Membership Plans list table (Day 06).
 *
 * Extends WP_List_Table to render the plans list inside the
 * "Membership Plans" settings tab.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/partials/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'WPS_Membership_Plans_List_Table' ) ) {

	/**
	 * List table for the Membership Plans admin tab.
	 *
	 * @since 2.0.0
	 */
	class WPS_Membership_Plans_List_Table extends WP_List_Table {

		/**
		 * Tab base URL (page + tab query args only).
		 *
		 * @var string
		 */
		private $tab_url;

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			parent::__construct(
				array(
					'singular' => 'plan',
					'plural'   => 'plans',
					'ajax'     => false,
				)
			);
			$this->tab_url = admin_url(
				'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=wps-membership-manage&wps_mem_tab=plans'
			);
		}

		/**
		 * Define table columns.
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_columns() {
			return array(
				'cb'              => '<input type="checkbox" />',
				'name'            => __( 'Name', 'subscriptions-for-woocommerce' ),
				'access_duration' => __( 'Access Duration', 'subscriptions-for-woocommerce' ),
				'products'        => __( 'Products', 'subscriptions-for-woocommerce' ),
				'active_members'  => __( 'Active Members', 'subscriptions-for-woocommerce' ),
				'status'          => __( 'Status', 'subscriptions-for-woocommerce' ),
			);
		}

		/**
		 * Sortable columns.
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_sortable_columns() {
			return array(
				'name'   => array( 'name', false ),
				'status' => array( 'status', false ),
			);
		}

		/**
		 * Bulk actions available for plans.
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_bulk_actions() {
			return array(
				'bulk-activate'   => __( 'Activate', 'subscriptions-for-woocommerce' ),
				'bulk-deactivate' => __( 'Deactivate', 'subscriptions-for-woocommerce' ),
				'bulk-delete'     => __( 'Delete', 'subscriptions-for-woocommerce' ),
			);
		}

		/**
		 * Status filter tabs (All / Active / Inactive).
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_views() {
			$current        = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$all_count      = count( wps_get_all_plans( 'all' ) );
			$active_count   = count( wps_get_all_plans( 'active' ) );
			$inactive_count = count( wps_get_all_plans( 'inactive' ) );

			return array(
				'all'      => sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					esc_url( $this->tab_url ),
					'all' === $current ? 'current' : '',
					esc_html__( 'All', 'subscriptions-for-woocommerce' ),
					$all_count
				),
				'active'   => sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					esc_url( add_query_arg( 'status', 'active', $this->tab_url ) ),
					'active' === $current ? 'current' : '',
					esc_html__( 'Active', 'subscriptions-for-woocommerce' ),
					$active_count
				),
				'inactive' => sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					esc_url( add_query_arg( 'status', 'inactive', $this->tab_url ) ),
					'inactive' === $current ? 'current' : '',
					esc_html__( 'Inactive', 'subscriptions-for-woocommerce' ),
					$inactive_count
				),
			);
		}

		/**
		 * Checkbox column.
		 *
		 * @since 2.0.0
		 * @param array $item Plan row data.
		 * @return string
		 */
		public function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="wps_plan_ids[]" value="%d" />',
				absint( $item['id'] )
			);
		}

		/**
		 * Name column with row actions.
		 *
		 * @since 2.0.0
		 * @param array $item Plan row data.
		 * @return string
		 */
		public function column_name( $item ) {
			$edit_url = admin_url( 'post.php?post=' . absint( $item['id'] ) . '&action=edit' );

			$activate_url   = wp_nonce_url(
				add_query_arg(
					array(
						'wps_plan_action' => 'activate',
						'plan_id'         => $item['id'],
					),
					$this->tab_url
				),
				'wps_plan_activate_' . $item['id']
			);
			$deactivate_url = wp_nonce_url(
				add_query_arg(
					array(
						'wps_plan_action' => 'deactivate',
						'plan_id'         => $item['id'],
					),
					$this->tab_url
				),
				'wps_plan_deactivate_' . $item['id']
			);
			$delete_url     = wp_nonce_url(
				add_query_arg(
					array(
						'wps_plan_action' => 'delete',
						'plan_id'         => $item['id'],
					),
					$this->tab_url
				),
				'wps_plan_delete_' . $item['id']
			);

			$actions = array(
				'edit' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( $edit_url ),
					esc_html__( 'Edit', 'subscriptions-for-woocommerce' )
				),
			);

			if ( 'active' === $item['status'] ) {
				$actions['deactivate'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $deactivate_url ),
					esc_html__( 'Deactivate', 'subscriptions-for-woocommerce' )
				);
			} else {
				$actions['activate'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $activate_url ),
					esc_html__( 'Activate', 'subscriptions-for-woocommerce' )
				);
			}

			$actions['delete'] = sprintf(
				'<a href="%s" class="wps-plan-delete" data-plan="%s" data-members="%d" onclick="return wps_confirm_plan_delete(this);">%s</a>',
				esc_url( $delete_url ),
				esc_attr( $item['name'] ),
				absint( $item['active_members'] ),
				esc_html__( 'Delete', 'subscriptions-for-woocommerce' )
			);

			$title = sprintf(
				'<strong><a href="%s" class="row-title">%s</a></strong>',
				esc_url( $edit_url ),
				esc_html( $item['name'] )
			);

			if ( ! empty( $item['color'] ) ) {
				$title .= '<span style="display:inline-block;width:10px;height:10px;border-radius:50%'
					. ';background:' . esc_attr( $item['color'] )
					. ';margin-left:6px;vertical-align:middle;"></span>';
			}

			return $title . $this->row_actions( $actions );
		}

		/**
		 * Default column renderer.
		 *
		 * @since 2.0.0
		 * @param array  $item        Plan row data.
		 * @param string $column_name Column slug.
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'access_duration':
					$wps_method = isset( $item['grant_method'] ) ? $item['grant_method'] : 'purchase';
					if ( 'subscription' === $wps_method ) {
						return esc_html__( 'Follows Subscription', 'subscriptions-for-woocommerce' );
					}
					if ( 'auto_enroll' === $wps_method ) {
						return esc_html__( 'Immediate', 'subscriptions-for-woocommerce' );
					}
					$wps_len  = isset( $item['access_length'] ) ? $item['access_length'] : array();
					$wps_type = isset( $wps_len['type'] ) ? $wps_len['type'] : 'lifetime';
					if ( 'fixed' === $wps_type ) {
						$wps_val         = isset( $wps_len['value'] ) ? absint( $wps_len['value'] ) : 1;
						$wps_unit        = isset( $wps_len['unit'] ) ? $wps_len['unit'] : 'month';
						$wps_unit_labels = array(
							'day'   => _n( 'Day', 'Days', $wps_val, 'subscriptions-for-woocommerce' ),
							'month' => _n( 'Month', 'Months', $wps_val, 'subscriptions-for-woocommerce' ),
							'year'  => _n( 'Year', 'Years', $wps_val, 'subscriptions-for-woocommerce' ),
						);
						$wps_unit_lbl    = isset( $wps_unit_labels[ $wps_unit ] )
							? $wps_unit_labels[ $wps_unit ]
							: $wps_unit;
						return absint( $wps_val ) . ' ' . esc_html( $wps_unit_lbl );
					}
					return esc_html__( 'Lifetime', 'subscriptions-for-woocommerce' );

				case 'products':
					if ( empty( $item['products'] ) ) {
						return '—';
					}
					$wps_names = array();
					$wps_show  = array_slice( $item['products'], 0, 2 );
					foreach ( $wps_show as $wps_pid ) {
						$wps_product = wc_get_product( $wps_pid );
						if ( $wps_product ) {
							$wps_names[] = '<a href="'
								. esc_url( get_edit_post_link( $wps_pid ) )
								. '">'
								. esc_html( $wps_product->get_name() )
								. '</a>';
						}
					}
					$wps_extra = count( $item['products'] ) - 2;
					$wps_out   = implode( ', ', $wps_names );
					if ( $wps_extra > 0 ) {
						$wps_out .= ' <span class="description">+'
							. absint( $wps_extra )
							. ' '
							. esc_html__( 'more', 'subscriptions-for-woocommerce' )
							. '</span>';
					}
					return $wps_out;

				case 'active_members':
					return absint( $item['active_members'] );

				case 'status':
					$status_key   = sanitize_html_class( $item['status'] );
					$status_label = 'active' === $item['status']
						? __( 'Active', 'subscriptions-for-woocommerce' )
						: __( 'Inactive', 'subscriptions-for-woocommerce' );
					return '<mark class="order-status status-' . esc_attr( $status_key ) . '"><span>'
						. esc_html( $status_label ) . '</span></mark>';

				default:
					return '';
			}
		}

		/**
		 * Message when no plans exist.
		 *
		 * @since 2.0.0
		 */
		public function no_items() {
			$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $search ) ) {
				esc_html_e( 'No plans match your search.', 'subscriptions-for-woocommerce' );
			} else {
				printf(
					wp_kses(
						/* translators: %s: link to create new plan */
						__( 'No membership plans yet. <a href="%s">Create your first plan</a>.', 'subscriptions-for-woocommerce' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'post-new.php?post_type=wps_membership_plan' ) )
				);
			}
		}

		/**
		 * Process bulk actions submitted via the list table form.
		 *
		 * @since 2.0.0
		 */
		public function process_bulk_action() {
			$action = $this->current_action();

			if ( ! in_array( $action, array( 'bulk-activate', 'bulk-deactivate', 'bulk-delete' ), true ) ) {
				return;
			}

			if ( ! isset( $_REQUEST['_wpnonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ),
					'bulk-plans'
				)
			) {
				wp_die( esc_html__( 'Security check failed.', 'subscriptions-for-woocommerce' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'subscriptions-for-woocommerce' ) );
			}

			if ( empty( $_REQUEST['wps_plan_ids'] ) ) {
				return;
			}

			$plan_ids = array_map( 'absint', (array) $_REQUEST['wps_plan_ids'] );

			foreach ( $plan_ids as $plan_id ) {
				switch ( $action ) {
					case 'bulk-activate':
						wps_update_plan( $plan_id, array( 'status' => 'active' ) );
						break;
					case 'bulk-deactivate':
						wps_update_plan( $plan_id, array( 'status' => 'inactive' ) );
						break;
					case 'bulk-delete':
						wps_delete_plan( $plan_id );
						break;
				}
			}

			$redirect = $this->tab_url;
			if ( isset( $_GET['status'] ) ) {
				$redirect = add_query_arg( 'status', sanitize_key( wp_unslash( $_GET['status'] ) ), $redirect );
			}
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Fetch and prepare the plan rows.
		 *
		 * @since 2.0.0
		 */
		public function prepare_items() {
			$this->_column_headers = array(
				$this->get_columns(),
				array(),
				$this->get_sortable_columns(),
			);

			$this->process_bulk_action();

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'all';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

			$plans = wps_get_all_plans( $status_filter );

			if ( ! empty( $search ) ) {
				$plans = array_values(
					array_filter(
						$plans,
						function ( $plan ) use ( $search ) {
							return false !== stripos( $plan['name'], $search )
								|| false !== stripos( $plan['slug'], $search );
						}
					)
				);
			}

			// Attach counts.
			foreach ( $plans as &$plan ) {
				$plan['product_count']  = count( $plan['products'] );
				$plan['active_members'] = WPS_Membership_Plans_Admin::get_active_member_count( $plan['slug'] );
			}
			unset( $plan );

			// Sort.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'name';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order = isset( $_GET['order'] ) && 'asc' === $_GET['order'] ? 'asc' : 'desc';

			if ( in_array( $orderby, array( 'name', 'status' ), true ) ) {
				usort(
					$plans,
					function ( $a, $b ) use ( $orderby, $order ) {
						$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
						return 'asc' === $order ? $result : -$result;
					}
				);
			}

			$per_page     = 20;
			$current_page = $this->get_pagenum();
			$total_items  = count( $plans );

			$this->items = array_slice( $plans, ( $current_page - 1 ) * $per_page, $per_page );

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total_items / $per_page ),
				)
			);
		}
	}
}
