<?php
/**
 * Members list table (Day 08).
 *
 * Extends WP_List_Table to render all user memberships inside the
 * "Members" settings tab — one row per user × plan combination.
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

if ( ! class_exists( 'WPS_Members_List_Table' ) ) {

	/**
	 * List table for the Members admin tab.
	 *
	 * Each row represents one user × plan membership. A single user
	 * can appear on multiple rows if they belong to multiple plans.
	 *
	 * @since 2.0.0
	 */
	class WPS_Members_List_Table extends WP_List_Table {

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
					'singular' => 'member',
					'plural'   => 'members',
					'ajax'     => false,
				)
			);
			$this->tab_url = admin_url(
				'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=wps-membership-manage&wps_mem_tab=members'
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
				'cb'     => '<input type="checkbox" />',
				'user'   => __( 'Member', 'subscriptions-for-woocommerce' ),
				'plan'   => __( 'Plan', 'subscriptions-for-woocommerce' ),
				'status' => __( 'Status', 'subscriptions-for-woocommerce' ),
				'expiry' => __( 'Expires', 'subscriptions-for-woocommerce' ),
				'source' => __( 'Source', 'subscriptions-for-woocommerce' ),
				'since'  => __( 'Since', 'subscriptions-for-woocommerce' ),
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
				'user'   => array( 'user', false ),
				'plan'   => array( 'plan', false ),
				'status' => array( 'status', false ),
				'since'  => array( 'since', false ),
			);
		}

		/**
		 * Bulk actions available on the members table.
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_bulk_actions() {
			return array(
				'bulk-cancel'     => __( 'Cancel', 'subscriptions-for-woocommerce' ),
				'bulk-reactivate' => __( 'Reactivate', 'subscriptions-for-woocommerce' ),
			);
		}

		/**
		 * Status filter tabs (All / Active / On Hold / Cancelled / Expired).
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_views() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current = isset( $_GET['member_status'] ) ? sanitize_key( wp_unslash( $_GET['member_status'] ) ) : 'all';

			$statuses = array_merge( array( 'all' => null ), array_fill_keys( WPS_MEMBERSHIP_STATUSES, null ) );
			$views    = array();

			foreach ( $statuses as $key => $unused ) {
				$count = $this->count_by_status( $key );
				$url   = 'all' === $key
					? $this->tab_url
					: add_query_arg( 'member_status', $key, $this->tab_url );

				$label = $this->status_label( $key );
				/* translators: 1: status label 2: count */
				$views[ $key ] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					esc_url( $url ),
					$key === $current ? 'current' : '',
					esc_html( $label ),
					absint( $count )
				);
			}

			return $views;
		}

		/**
		 * Checkbox column.
		 *
		 * @since 2.0.0
		 * @param array $item Row data.
		 * @return string
		 */
		public function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="wps_member_rows[]" value="%d:%s" />',
				absint( $item['user_id'] ),
				esc_attr( $item['plan_slug'] )
			);
		}

		/**
		 * Member column with row actions.
		 *
		 * @since 2.0.0
		 * @param array $item Row data.
		 * @return string
		 */
		public function column_user( $item ) {
			$user         = get_userdata( $item['user_id'] );
			$profile_url  = admin_url( 'user-edit.php?user_id=' . absint( $item['user_id'] ) );
			$display_name = $user ? esc_html( $user->display_name ) : esc_html__( '(deleted)', 'subscriptions-for-woocommerce' );
			$email        = $user ? '<br><span class="description">' . esc_html( $user->user_email ) . '</span>' : '';

			$cancel_url = wp_nonce_url(
				add_query_arg(
					array(
						'wps_member_action' => 'cancel',
						'user_id'           => $item['user_id'],
						'plan_slug'         => $item['plan_slug'],
					),
					$this->tab_url
				),
				'wps_member_cancel_' . $item['user_id'] . '_' . $item['plan_slug']
			);

			$reactivate_url = wp_nonce_url(
				add_query_arg(
					array(
						'wps_member_action' => 'reactivate',
						'user_id'           => $item['user_id'],
						'plan_slug'         => $item['plan_slug'],
					),
					$this->tab_url
				),
				'wps_member_reactivate_' . $item['user_id'] . '_' . $item['plan_slug']
			);

			$remove_url = wp_nonce_url(
				add_query_arg(
					array(
						'wps_member_action' => 'remove',
						'user_id'           => $item['user_id'],
						'plan_slug'         => $item['plan_slug'],
					),
					$this->tab_url
				),
				'wps_member_remove_' . $item['user_id'] . '_' . $item['plan_slug']
			);

			$actions = array(
				'profile' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( $profile_url ),
					esc_html__( 'View Profile', 'subscriptions-for-woocommerce' )
				),
			);

			if ( 'active' === $item['status'] || 'on-hold' === $item['status'] ) {
				$actions['cancel'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $cancel_url ),
					esc_html__( 'Cancel', 'subscriptions-for-woocommerce' )
				);
			} else {
				$actions['reactivate'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $reactivate_url ),
					esc_html__( 'Reactivate', 'subscriptions-for-woocommerce' )
				);
			}

			$actions['remove'] = sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $remove_url ),
				esc_js( __( 'Permanently remove this membership record? This cannot be undone.', 'subscriptions-for-woocommerce' ) ),
				esc_html__( 'Remove', 'subscriptions-for-woocommerce' )
			);

			return '<strong><a href="' . esc_url( $profile_url ) . '">' . $display_name . '</a></strong>'
				. $email
				. $this->row_actions( $actions );
		}

		/**
		 * Default column renderer.
		 *
		 * @since 2.0.0
		 * @param array  $item        Row data.
		 * @param string $column_name Column slug.
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'plan':
					$plan = wps_get_plan_by_slug( $item['plan_slug'] );
					if ( ! $plan ) {
						return '<code>' . esc_html( $item['plan_slug'] ) . '</code>';
					}
					return sprintf(
						'<a href="%s">%s</a>',
						esc_url( get_edit_post_link( $plan['id'] ) ),
						esc_html( $plan['name'] )
					);

				case 'status':
					$key   = sanitize_html_class( $item['status'] );
					return '<mark class="order-status status-' . esc_attr( $key ) . '"><span>'
						. esc_html( $this->status_label( $item['status'] ) ) . '</span></mark>';

				case 'expiry':
					if ( empty( $item['expiry_date'] ) ) {
						return esc_html__( 'Lifetime', 'subscriptions-for-woocommerce' );
					}
					return esc_html( date_i18n( get_option( 'date_format' ), $item['expiry_date'] ) );

				case 'source':
					return $this->render_source( $item );

				case 'since':
					if ( empty( $item['start_date'] ) ) {
						return '—';
					}
					return esc_html( date_i18n( get_option( 'date_format' ), $item['start_date'] ) );

				default:
					return '';
			}
		}

		/**
		 * Empty state message.
		 *
		 * @since 2.0.0
		 */
		public function no_items() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			if ( ! empty( $search ) ) {
				esc_html_e( 'No members match your search.', 'subscriptions-for-woocommerce' );
			} else {
				esc_html_e( 'No memberships found.', 'subscriptions-for-woocommerce' );
			}
		}

		/**
		 * Process bulk actions submitted via the list table form.
		 *
		 * @since 2.0.0
		 */
		public function process_bulk_action() {
			$action = $this->current_action();

			if ( ! in_array( $action, array( 'bulk-cancel', 'bulk-reactivate' ), true ) ) {
				return;
			}

			if ( ! isset( $_REQUEST['_wpnonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ),
					'bulk-members'
				)
			) {
				wp_die( esc_html__( 'Security check failed.', 'subscriptions-for-woocommerce' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'subscriptions-for-woocommerce' ) );
			}

			if ( empty( $_REQUEST['wps_member_rows'] ) ) {
				return;
			}

			$new_status = 'bulk-cancel' === $action ? 'cancelled' : 'active';
			$rows       = array_map( 'sanitize_text_field', (array) wp_unslash( $_REQUEST['wps_member_rows'] ) );

			foreach ( $rows as $row_key ) {
				$parts = explode( ':', $row_key, 2 );
				if ( 2 !== count( $parts ) ) {
					continue;
				}
				wps_update_membership_status( absint( $parts[0] ), sanitize_key( $parts[1] ), $new_status );
			}

			wp_safe_redirect( $this->tab_url );
			exit;
		}

		/**
		 * Fetch and prepare the member rows.
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
			$status_filter = isset( $_GET['member_status'] )
				? sanitize_key( wp_unslash( $_GET['member_status'] ) )
				: 'all';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$plan_filter = isset( $_GET['plan_slug'] )
				? sanitize_key( wp_unslash( $_GET['plan_slug'] ) )
				: '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = isset( $_GET['s'] )
				? sanitize_text_field( wp_unslash( $_GET['s'] ) )
				: '';

			$rows = $this->fetch_all_membership_rows( $status_filter, $plan_filter, $search );

			// Sort.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'since';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order = isset( $_GET['order'] ) && 'asc' === $_GET['order'] ? 'asc' : 'desc';

			usort(
				$rows,
				function ( $a, $b ) use ( $orderby, $order ) {
					switch ( $orderby ) {
						case 'user':
							$av = isset( $a['display_name'] ) ? $a['display_name'] : '';
							$bv = isset( $b['display_name'] ) ? $b['display_name'] : '';
							break;
						case 'plan':
							$av = $a['plan_slug'];
							$bv = $b['plan_slug'];
							break;
						case 'status':
							$av = $a['status'];
							$bv = $b['status'];
							break;
						default:
							$av = isset( $a['start_date'] ) ? $a['start_date'] : 0;
							$bv = isset( $b['start_date'] ) ? $b['start_date'] : 0;
					}
					$result = is_numeric( $av ) ? ( $av - $bv ) : strcmp( (string) $av, (string) $bv );
					return 'asc' === $order ? $result : -$result;
				}
			);

			$per_page     = 20;
			$current_page = $this->get_pagenum();
			$total_items  = count( $rows );

			$this->items = array_slice( $rows, ( $current_page - 1 ) * $per_page, $per_page );

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => (int) ceil( $total_items / $per_page ),
				)
			);
		}

		// -----------------------------------------------------------------------
		// Private helpers
		// -----------------------------------------------------------------------

		/**
		 * Build a flat list of membership rows across all users.
		 *
		 * Queries users who have the `wps_memberships` meta key, then reads each
		 * user's membership array and flattens it into individual rows for display.
		 *
		 * @since  2.0.0
		 * @param  string $status_filter 'all' or a valid WPS_MEMBERSHIP_STATUSES value.
		 * @param  string $plan_filter   Plan slug to restrict results to, or ''.
		 * @param  string $search        Free-text search against name / email.
		 * @return array Flat array of row arrays.
		 */
		private function fetch_all_membership_rows( $status_filter, $plan_filter, $search ) {
			$query_args = array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key' => 'wps_memberships',
				'number'   => -1,
				'fields'   => array( 'ID', 'display_name', 'user_email' ),
			);

			if ( ! empty( $search ) ) {
				$query_args['search']         = '*' . $search . '*';
				$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
			}

			$users = get_users( $query_args );
			$rows  = array();

			foreach ( $users as $user ) {
				$memberships = wps_get_user_memberships( $user->ID, $status_filter );

				foreach ( $memberships as $row ) {
					if ( ! empty( $plan_filter ) && $plan_filter !== $row['plan_slug'] ) {
						continue;
					}
					$row['user_id']      = $user->ID;
					$row['display_name'] = $user->display_name;
					$rows[]              = $row;
				}
			}

			return $rows;
		}

		/**
		 * Count all membership rows matching a given status.
		 *
		 * @since  2.0.0
		 * @param  string $status Status key or 'all'.
		 * @return int
		 */
		private function count_by_status( $status ) {
			return count( $this->fetch_all_membership_rows( $status, '', '' ) );
		}

		/**
		 * Human-readable label for a membership status.
		 *
		 * @since  2.0.0
		 * @param  string $status Status key.
		 * @return string
		 */
		private function status_label( $status ) {
			$labels = array(
				'all'       => __( 'All', 'subscriptions-for-woocommerce' ),
				'active'    => __( 'Active', 'subscriptions-for-woocommerce' ),
				'on-hold'   => __( 'On Hold', 'subscriptions-for-woocommerce' ),
				'cancelled' => __( 'Cancelled', 'subscriptions-for-woocommerce' ),
				'expired'   => __( 'Expired', 'subscriptions-for-woocommerce' ),
				'paused'    => __( 'Paused', 'subscriptions-for-woocommerce' ),
			);
			return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
		}

		/**
		 * Render the Source column value.
		 *
		 * @since  2.0.0
		 * @param  array $item Row data.
		 * @return string
		 */
		private function render_source( $item ) {
			switch ( $item['source'] ) {
				case 'subscription':
					if ( ! empty( $item['subscription_id'] ) ) {
						return sprintf(
							'<a href="%s">%s #%d</a>',
							esc_url( get_edit_post_link( $item['subscription_id'] ) ),
							esc_html__( 'Subscription', 'subscriptions-for-woocommerce' ),
							absint( $item['subscription_id'] )
						);
					}
					return esc_html__( 'Subscription', 'subscriptions-for-woocommerce' );

				case 'order':
					if ( ! empty( $item['order_id'] ) ) {
						return sprintf(
							'<a href="%s">%s #%d</a>',
							esc_url( get_edit_post_link( $item['order_id'] ) ),
							esc_html__( 'Order', 'subscriptions-for-woocommerce' ),
							absint( $item['order_id'] )
						);
					}
					return esc_html__( 'Order', 'subscriptions-for-woocommerce' );

				default:
					return esc_html__( 'Manual', 'subscriptions-for-woocommerce' );
			}
		}
	}
}
