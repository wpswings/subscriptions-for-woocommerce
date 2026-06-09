<?php
/**
 * Membership Layer — Members Admin Tab + User Profile Section (Day 09)
 *
 * Registers the central "Members" settings tab and the per-user profile section
 * that lets admins grant, edit, and revoke memberships.
 *
 * Handles:
 *   - Single-row GET actions (cancel / reactivate / remove) via admin_init.
 *   - CSV export via admin_init (before headers are sent).
 *   - User profile membership section (render + save).
 *   - AJAX handler for the grant form and user search.
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Members_Admin' ) ) {

	/**
	 * Central members management tab and user-profile membership section.
	 *
	 * @since 2.0.0
	 */
	class WPS_Members_Admin {

		const TAB_KEY              = 'wps-membership-members';
		const AJAX_NONCE           = 'wps_membership_admin_action';
		const PROFILE_NONCE_ACTION = 'wps_profile_membership_';

		/**
		 * Wire admin_init for row actions and CSV export.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'handle_row_actions' ) );
		}

		/**
		 * Add the "Members" tab to the plugin settings navigation.
		 *
		 * Hooked to `wps_sfw_sfw_plugin_standard_admin_settings_tabs` at priority 30.
		 *
		 * @since  2.0.0
		 * @param  array $tabs Existing settings tabs.
		 * @return array
		 */
		public function register_tab( $tabs ) {
			$tabs[ self::TAB_KEY ] = array(
				'title'     => esc_html__( 'Members', 'subscriptions-for-woocommerce' ),
				'name'      => self::TAB_KEY,
				'file_path' => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
			);
			return $tabs;
		}

		/**
		 * Process single-row GET actions and CSV export.
		 *
		 * Row action URLs are built by WPS_Members_List_Table with per-action nonces.
		 * CSV export uses a separate nonce in the URL query string.
		 * Both paths require `manage_woocommerce`.
		 *
		 * @since 2.0.0
		 */
		public function handle_row_actions() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['page'] ) || 'subscriptions_for_woocommerce_menu' !== $_GET['page'] ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$sfw_tab = isset( $_GET['sfw_tab'] ) ? sanitize_key( wp_unslash( $_GET['sfw_tab'] ) ) : '';
			if ( 'wps-membership-manage' !== $sfw_tab ) {
				return;
			}
			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				return;
			}

			$tab_url = admin_url(
				'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=wps-membership-manage&wps_mem_tab=members'
			);

			// ---- CSV export ----
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['wps_export_members'] ) ) {
				$nonce = isset( $_GET['_wpnonce'] )
					? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) )
					: '';
				if ( ! wp_verify_nonce( $nonce, 'wps_export_members' ) ) {
					wp_die( esc_html__( 'Security check failed.', 'subscriptions-for-woocommerce' ) );
				}
				$this->output_csv_export();
				exit;
			}

			// ---- Single-row action (GET) ----
			if ( ! isset( $_GET['wps_member_action'], $_GET['user_id'], $_GET['plan_slug'] ) ) {
				return;
			}

			$action    = sanitize_key( wp_unslash( $_GET['wps_member_action'] ) );
			$user_id   = absint( $_GET['user_id'] );
			$plan_slug = sanitize_key( wp_unslash( $_GET['plan_slug'] ) );
			$nonce     = isset( $_GET['_wpnonce'] )
				? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) )
				: '';

			if ( ! wp_verify_nonce( $nonce, 'wps_member_' . $action . '_' . $user_id . '_' . $plan_slug ) ) {
				wp_die( esc_html__( 'Security check failed.', 'subscriptions-for-woocommerce' ) );
			}

			switch ( $action ) {
				case 'cancel':
					wps_update_membership_status( $user_id, $plan_slug, 'cancelled' );
					break;

				case 'reactivate':
					wps_update_membership_status( $user_id, $plan_slug, 'active' );
					break;

				case 'remove':
					wps_remove_user_membership( $user_id, $plan_slug );
					break;
			}

			$redirect = $tab_url;
			// Preserve status/plan filters in redirect.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['member_status'] ) ) {
				$status   = sanitize_key( wp_unslash( $_GET['member_status'] ) );
				$redirect = add_query_arg( 'member_status', $status, $redirect );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['plan_slug_filter'] ) ) {
				$pf       = sanitize_key( wp_unslash( $_GET['plan_slug_filter'] ) );
				$redirect = add_query_arg( 'plan_slug', $pf, $redirect );
			}

			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Render the membership section on a user's profile page.
		 *
		 * Hooked to `show_user_profile` and `edit_user_profile`.
		 * $user is passed to the included partial via the local scope.
		 *
		 * @since 2.0.0
		 * @param WP_User $user The user object being edited.
		 */
		public function render_profile_section( $user ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				return;
			}
			// $user is consumed by the included partial.
			include SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH
				. 'admin/partials/membership/user-profile-memberships.php';
		}

		/**
		 * Save membership changes submitted from the user profile form.
		 *
		 * Hooked to `personal_options_update` and `edit_user_profile_update`.
		 * Handles: manual grant and per-row revoke/reactivate/remove via POST.
		 *
		 * @since 2.0.0
		 * @param int $user_id The ID of the user being updated.
		 */
		public function save_profile_section( $user_id ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				return;
			}

			if ( ! isset( $_POST['wps_profile_membership_nonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['wps_profile_membership_nonce'] ) ),
					self::PROFILE_NONCE_ACTION . $user_id
				)
			) {
				return;
			}

			// ---- Grant new membership ----
			if ( ! empty( $_POST['wps_profile_grant_plan'] ) ) {
				$plan_slug  = sanitize_key( wp_unslash( $_POST['wps_profile_grant_plan'] ) );
				$expiry_str = isset( $_POST['wps_profile_grant_expiry'] )
					? sanitize_text_field( wp_unslash( $_POST['wps_profile_grant_expiry'] ) )
					: '';

				$expiry_date = ! empty( $expiry_str ) ? strtotime( $expiry_str ) : null;
				if ( false === $expiry_date || ( null !== $expiry_date && $expiry_date <= 0 ) ) {
					$expiry_date = null;
				}

				wps_create_user_membership(
					$user_id,
					array(
						'plan_slug'   => $plan_slug,
						'source'      => 'manual',
						'expiry_date' => $expiry_date,
					)
				);
			}

			// ---- Per-row actions (revoke / reactivate / remove) ----
			if ( ! empty( $_POST['wps_profile_membership_action'] )
				&& ! empty( $_POST['wps_profile_action_plan'] )
			) {
				$row_action = sanitize_key( wp_unslash( $_POST['wps_profile_membership_action'] ) );
				$row_plan   = sanitize_key( wp_unslash( $_POST['wps_profile_action_plan'] ) );

				switch ( $row_action ) {
					case 'revoke':
						wps_update_membership_status( $user_id, $row_plan, 'cancelled' );
						break;
					case 'reactivate':
						wps_update_membership_status( $user_id, $row_plan, 'active' );
						break;
					case 'remove':
						wps_remove_user_membership( $user_id, $row_plan );
						break;
				}
			}
		}

		/**
		 * Handle AJAX membership admin actions (grant, edit, user search).
		 *
		 * Hooked to `wp_ajax_wps_membership_admin_action`.
		 * Expects POST: `nonce`, `wps_sub_action`, plus action-specific fields.
		 *
		 * @since 2.0.0
		 */
		public function handle_admin_actions() {
			check_ajax_referer( self::AJAX_NONCE, 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_send_json_error(
					array( 'message' => __( 'Permission denied.', 'subscriptions-for-woocommerce' ) )
				);
				return;
			}

			$sub_action = isset( $_POST['wps_sub_action'] )
				? sanitize_key( wp_unslash( $_POST['wps_sub_action'] ) )
				: '';

			switch ( $sub_action ) {

				// ---- Grant new membership ----
				case 'grant':
					$user_id    = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
					$plan_slug  = isset( $_POST['plan_slug'] )
						? sanitize_key( wp_unslash( $_POST['plan_slug'] ) )
						: '';
					$expiry_str = isset( $_POST['expiry_date'] )
						? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) )
						: '';

					if ( ! $user_id || empty( $plan_slug ) ) {
						wp_send_json_error(
							array( 'message' => __( 'User and plan are required.', 'subscriptions-for-woocommerce' ) )
						);
						return;
					}

					$expiry_date = ! empty( $expiry_str ) ? strtotime( $expiry_str ) : null;
					if ( false === $expiry_date || ( null !== $expiry_date && $expiry_date <= 0 ) ) {
						$expiry_date = null;
					}

					$result = wps_create_user_membership(
						$user_id,
						array(
							'plan_slug'   => $plan_slug,
							'source'      => 'manual',
							'expiry_date' => $expiry_date,
						)
					);

					if ( is_wp_error( $result ) ) {
						wp_send_json_error( array( 'message' => $result->get_error_message() ) );
					} else {
						wp_send_json_success(
							array(
								'message' => __( 'Membership granted successfully.', 'subscriptions-for-woocommerce' ),
							)
						);
					}
					break;

				// ---- Edit membership status and/or expiry ----
				case 'edit':
					$user_id    = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
					$plan_slug  = isset( $_POST['plan_slug'] )
						? sanitize_key( wp_unslash( $_POST['plan_slug'] ) )
						: '';
					$new_status = isset( $_POST['new_status'] )
						? sanitize_key( wp_unslash( $_POST['new_status'] ) )
						: '';
					$expiry_str = isset( $_POST['expiry_date'] )
						? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) )
						: '';

					if ( ! $user_id || empty( $plan_slug ) ) {
						wp_send_json_error(
							array( 'message' => __( 'Invalid data.', 'subscriptions-for-woocommerce' ) )
						);
						return;
					}

					if ( ! empty( $new_status ) && in_array( $new_status, WPS_MEMBERSHIP_STATUSES, true ) ) {
						$status_result = wps_update_membership_status( $user_id, $plan_slug, $new_status );
						if ( is_wp_error( $status_result ) ) {
							wp_send_json_error(
								array( 'message' => $status_result->get_error_message() )
							);
							return;
						}
					}

					// Update expiry only when the field was explicitly sent.
					if ( isset( $_POST['expiry_date'] ) ) {
						$expiry_date = ! empty( $expiry_str ) ? strtotime( $expiry_str ) : null;
						if ( false === $expiry_date || ( null !== $expiry_date && $expiry_date <= 0 ) ) {
							$expiry_date = null;
						}
						$expiry_result = wps_extend_membership_expiry( $user_id, $plan_slug, $expiry_date );
						if ( is_wp_error( $expiry_result ) ) {
							wp_send_json_error(
								array( 'message' => $expiry_result->get_error_message() )
							);
							return;
						}
					}

					wp_send_json_success(
						array( 'message' => __( 'Membership updated.', 'subscriptions-for-woocommerce' ) )
					);
					break;

				// ---- User search for the grant form autocomplete ----
				case 'search_users':
					$term = isset( $_POST['term'] )
						? sanitize_text_field( wp_unslash( $_POST['term'] ) )
						: '';

					if ( strlen( $term ) < 2 ) {
						wp_send_json_success( array( 'results' => array() ) );
						return;
					}

					$found_users = get_users(
						array(
							'search'         => '*' . $term . '*',
							'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
							'number'         => 10,
							'fields'         => array( 'ID', 'display_name', 'user_email' ),
						)
					);

					$results = array();
					foreach ( $found_users as $found_user ) {
						$results[] = array(
							'id'   => (int) $found_user->ID,
							'text' => sprintf(
								'%s (%s)',
								$found_user->display_name,
								$found_user->user_email
							),
						);
					}

					wp_send_json_success( array( 'results' => $results ) );
					break;

				default:
					wp_send_json_error(
						array( 'message' => __( 'Unknown action.', 'subscriptions-for-woocommerce' ) )
					);
			}

			wp_die();
		}

		// -----------------------------------------------------------------------
		// Private helpers
		// -----------------------------------------------------------------------

		/**
		 * Stream all membership rows as a CSV download.
		 *
		 * Reads every user with a `wps_memberships` meta key and flattens to
		 * one row per user × plan combination. Streams directly without buffering
		 * the full dataset in memory.
		 *
		 * @since 2.0.0
		 */
		private function output_csv_export() {
			$filename = 'members-export-' . gmdate( 'Y-m-d' ) . '.csv';

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$output = fopen( 'php://output', 'w' );

			// BOM for Excel UTF-8.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs
			fputs( $output, "\xEF\xBB\xBF" );

			fputcsv(
				$output,
				array(
					__( 'User ID', 'subscriptions-for-woocommerce' ),
					__( 'Name', 'subscriptions-for-woocommerce' ),
					__( 'Email', 'subscriptions-for-woocommerce' ),
					__( 'Plan', 'subscriptions-for-woocommerce' ),
					__( 'Status', 'subscriptions-for-woocommerce' ),
					__( 'Source', 'subscriptions-for-woocommerce' ),
					__( 'Since', 'subscriptions-for-woocommerce' ),
					__( 'Expires', 'subscriptions-for-woocommerce' ),
					__( 'Subscription ID', 'subscriptions-for-woocommerce' ),
					__( 'Order ID', 'subscriptions-for-woocommerce' ),
				)
			);

			$date_format = get_option( 'date_format' );

			$users = get_users(
				array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'    => 'wps_memberships',
					'number'      => -1,
					'count_total' => false,
					'fields'      => array( 'ID', 'display_name', 'user_email' ),
				)
			);

			foreach ( $users as $csv_user ) {
				$memberships = wps_get_user_memberships( $csv_user->ID, 'all' );
				foreach ( $memberships as $row ) {
					$plan_name = $row['plan_slug'];
					$plan      = wps_get_plan_by_slug( $row['plan_slug'] );
					if ( $plan ) {
						$plan_name = $plan['name'];
					}

					$since   = ! empty( $row['start_date'] )
						? date_i18n( $date_format, $row['start_date'] )
						: '';
					$expires = empty( $row['expiry_date'] )
						? __( 'Lifetime', 'subscriptions-for-woocommerce' )
						: date_i18n( $date_format, $row['expiry_date'] );

					fputcsv(
						$output,
						array(
							$csv_user->ID,
							$csv_user->display_name,
							$csv_user->user_email,
							$plan_name,
							$row['status'],
							$row['source'],
							$since,
							$expires,
							isset( $row['subscription_id'] ) ? $row['subscription_id'] : '',
							isset( $row['order_id'] ) ? $row['order_id'] : '',
						)
					);
				}
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $output );
		}
	}
}
