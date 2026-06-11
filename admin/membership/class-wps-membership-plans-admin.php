<?php
/**
 * Membership Layer — Plans Admin Tab (Day 06)
 *
 * Registers the "Membership Plans" settings tab via
 * `wps_sfw_sfw_plugin_standard_admin_settings_tabs` and handles
 * single-row and bulk actions (activate / deactivate / delete).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/admin/membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Membership_Plans_Admin' ) ) {

	/**
	 * Registers and renders the Membership Plans settings tab.
	 *
	 * @since 2.0.0
	 */
	class WPS_Membership_Plans_Admin {

		const TAB_KEY      = 'wps-membership-manage';
		const NONCE_ACTION = 'wps-membership-plans-bulk';
		const NONCE_FIELD  = 'wps_membership_plans_nonce';

		/**
		 * Register the admin_init hook to process plan actions.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'handle_actions' ) );
		}

		/**
		 * Enqueue the Manage Membership stylesheet.
		 *
		 * Hooked to `admin_enqueue_scripts`.
		 *
		 * @since 2.0.0
		 */
		public function enqueue_scripts() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab = isset( $_GET['sfw_tab'] ) ? sanitize_key( wp_unslash( $_GET['sfw_tab'] ) ) : '';
			if ( 'wps-membership-manage' !== $tab ) {
				return;
			}
			wp_enqueue_style(
				'wps-membership-manage',
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-membership-manage.css',
				array(),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
			);

			// Analytics styles — shared by the org-build teaser and the Pro
			// plugin's live dashboard (both render inside the Manage tab).
			wp_enqueue_style(
				'wps-membership-analytics',
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-membership-analytics.css',
				array(),
				SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION
			);
		}

		/**
		 * Add the "Manage Membership" tab to the plugin settings navigation.
		 *
		 * Hooked to `wps_sfw_sfw_plugin_standard_admin_settings_tabs` at priority 25.
		 *
		 * @since  2.0.0
		 * @param  array $tabs Existing settings tabs.
		 * @return array
		 */
		public function register_tab( $tabs ) {
			$tabs[ self::TAB_KEY ] = array(
				'title'     => esc_html__( 'Manage Membership', 'subscriptions-for-woocommerce' ),
				'name'      => self::TAB_KEY,
				'file_path' => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
			);
			return $tabs;
		}

		/**
		 * Process single-row and bulk plan actions on admin_init.
		 *
		 * Single-row actions arrive via GET (activate/deactivate/delete + per-action nonce).
		 * Bulk actions arrive via POST (bulk-action select + plan_ids array + nonce).
		 * Both require `manage_woocommerce` capability.
		 *
		 * @since 2.0.0
		 */
		public function handle_actions() {
			if ( ! isset( $_GET['page'] ) || 'subscriptions_for_woocommerce_menu' !== $_GET['page'] ) {
				return;
			}
			if ( ! isset( $_GET['sfw_tab'] ) || self::TAB_KEY !== $_GET['sfw_tab'] ) {
				return;
			}
			if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				return;
			}

			$tab_url = admin_url(
				'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=' . self::TAB_KEY . '&wps_mem_tab=plans'
			);

			// ---- single-row action (GET) ----
			if ( isset( $_GET['wps_plan_action'], $_GET['plan_id'] ) ) {
				$action  = sanitize_key( wp_unslash( $_GET['wps_plan_action'] ) );
				$plan_id = absint( $_GET['plan_id'] );
				$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

				if ( ! wp_verify_nonce( $nonce, 'wps_plan_' . $action . '_' . $plan_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'subscriptions-for-woocommerce' ) );
				}

				switch ( $action ) {
					case 'activate':
						wps_update_plan( $plan_id, array( 'status' => 'active' ) );
						break;
					case 'deactivate':
						wps_update_plan( $plan_id, array( 'status' => 'inactive' ) );
						break;
					case 'delete':
						wps_delete_plan( $plan_id );
						break;
				}

				$redirect = $tab_url;
				if ( isset( $_GET['status'] ) ) {
					$redirect = add_query_arg( 'status', sanitize_key( wp_unslash( $_GET['status'] ) ), $redirect );
				}
				wp_safe_redirect( $redirect );
				exit;
			}

			// ---- bulk action (POST) ----
			if ( isset( $_POST['wps_bulk_action'], $_POST['wps_plan_ids'] ) ) {
				if ( ! isset( $_POST[ self::NONCE_FIELD ] )
					|| ! wp_verify_nonce(
						sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ),
						self::NONCE_ACTION
					)
				) {
					wp_die( esc_html__( 'Security check failed.', 'subscriptions-for-woocommerce' ) );
				}

				$bulk_action = sanitize_key( wp_unslash( $_POST['wps_bulk_action'] ) );
				$plan_ids    = array_map( 'absint', (array) $_POST['wps_plan_ids'] );

				foreach ( $plan_ids as $plan_id ) {
					switch ( $bulk_action ) {
						case 'activate':
							wps_update_plan( $plan_id, array( 'status' => 'active' ) );
							break;
						case 'deactivate':
							wps_update_plan( $plan_id, array( 'status' => 'inactive' ) );
							break;
						case 'delete':
							wps_delete_plan( $plan_id );
							break;
					}
				}

				$redirect = $tab_url;
				if ( isset( $_GET['status'] ) ) {
					$redirect = add_query_arg( 'status', sanitize_key( wp_unslash( $_GET['status'] ) ), $redirect );
				}
				wp_safe_redirect( $redirect );
				exit;
			}
		}

		/**
		 * Return the active member count for a plan slug using the flat user-meta index.
		 *
		 * @since 2.0.0
		 *
		 * @param  string $slug Plan slug.
		 * @return int
		 */
		public static function get_active_member_count( $slug ) {
			$query = new WP_User_Query(
				array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'    => 'wps_member_of_' . sanitize_key( $slug ),
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'meta_value'  => 'active',
					'count_total' => true,
					'number'      => 1,
					'fields'      => 'ID',
				)
			);
			return (int) $query->get_total();
		}
	}
}
