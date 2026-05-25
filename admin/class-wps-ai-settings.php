<?php
/**
 * AI settings admin tab.
 *
 * @package Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and saves WPS Subscriptions AI settings.
 */
class WPS_AI_Settings {

	/**
	 * Tab key.
	 */
	const TAB_KEY = 'wps-subscriptions-ai-settings';

	/**
	 * AJAX action for connection tests.
	 */
	const TEST_CONNECTION_ACTION = 'wps_ai_test_connection';

	/**
	 * Nonce action.
	 */
	const NONCE_ACTION = 'wps-ai-settings-nonce';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $version Plugin version.
	 */
	public function __construct( $version ) {
		$this->version = $version;
	}

	/**
	 * Add AI Settings to the free plugin tab list.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {
		$tabs[ self::TAB_KEY ] = array(
			'title'     => esc_html__( 'AI Settings', 'subscriptions-for-woocommerce' ),
			'name'      => self::TAB_KEY,
			'file_path' => SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH,
		);

		return $tabs;
	}

	/**
	 * Save AI settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( ! isset( $_POST['wps_ai_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! isset( $_POST['wps-ai-settings-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wps-ai-settings-nonce-field'] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		$main_enabled = isset( $_POST['wps_ai_main_enabled'] ) ? '1' : '0';

		$provider = sanitize_key( $this->get_posted_text( 'wps_ai_provider' ) );
		$provider = in_array( $provider, array_keys( wps_ai_provider()->get_supported_providers() ), true ) ? $provider : '';

		if ( '' === $provider ) {
			if ( '0' === $main_enabled ) {
				update_option( 'wps_ai_main_enabled', '0' );
				$GLOBALS['wps_sfw_notices'] = true;
				return;
			}

			update_option( 'wps_ai_main_enabled', '0' );
			$GLOBALS['wps_sfw_notices'] = false;
			add_action(
				'wps_sfw_notice_message',
				function() {
					Subscriptions_For_Woocommerce::wps_sfw_plug_admin_notice( __( 'Please select an AI provider before enabling AI settings.', 'subscriptions-for-woocommerce' ), 'error' );
				}
			);
			return;
		}

		update_option( 'wps_ai_provider', $provider );

		foreach ( array_keys( wps_ai_provider()->get_supported_providers() ) as $provider_key ) {
			$model_key = 'wps_ai_model_' . $provider_key;
			if ( isset( $_POST[ $model_key ] ) ) {
				$model_value = $this->get_posted_text( $model_key );
				if ( '__custom' === $model_value ) {
					$model_value = $this->get_posted_text( $model_key . '_custom' );
				}
				update_option( $model_key, $model_value );
			}

			$api_key_key = 'wps_ai_api_key_' . $provider_key;
			if ( isset( $_POST[ $api_key_key ] ) ) {
				$posted_key = trim( $this->get_posted_text( $api_key_key ) );
				if ( '' !== $posted_key ) {
					update_option( $api_key_key, wps_ai_provider()->prepare_api_key_for_storage( $posted_key ), false );
				}
			}
		}

		$custom_endpoint = isset( $_POST['wps_ai_custom_endpoint'] ) ? esc_url_raw( $this->get_posted_text( 'wps_ai_custom_endpoint' ) ) : '';
		update_option( 'wps_ai_custom_endpoint', $custom_endpoint );

if ( '1' === $main_enabled ) {
			$config = wps_ai_provider()->get_config();
			if ( is_wp_error( $config ) ) {
				update_option( 'wps_ai_main_enabled', '0' );
				$GLOBALS['wps_sfw_notices'] = false;
				add_action(
					'wps_sfw_notice_message',
					function() use ( $config ) {
						Subscriptions_For_Woocommerce::wps_sfw_plug_admin_notice( $config->get_error_message(), 'error' );
					}
				);
				return;
			}
		}

		update_option( 'wps_ai_main_enabled', $main_enabled );
		$GLOBALS['wps_sfw_notices'] = true;
	}

	/**
	 * Enqueue AI settings assets only on the AI tab.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || empty( $screen->id ) || 'wp-swings_page_subscriptions_for_woocommerce_menu' !== $screen->id ) {
			return;
		}

		$active_tab = isset( $_GET['sfw_tab'] ) ? sanitize_key( wp_unslash( $_GET['sfw_tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::TAB_KEY !== $active_tab ) {
			return;
		}

		wp_enqueue_style( 'wps-ai-settings', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-ai-settings.css', array(), $this->asset_version( 'admin/css/wps-ai-settings.css' ), 'all' );
		wp_enqueue_script( 'wps-ai-settings', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-ai-settings.js', array( 'jquery' ), $this->asset_version( 'admin/js/wps-ai-settings.js' ), true );

		wp_localize_script(
			'wps-ai-settings',
			'wpsAiSettings',
			array(
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'action'         => self::TEST_CONNECTION_ACTION,
				'nonce'          => wp_create_nonce( self::NONCE_ACTION ),
				'testingText'    => esc_html__( 'Testing...', 'subscriptions-for-woocommerce' ),
				'connectedText'  => esc_html__( 'Connected', 'subscriptions-for-woocommerce' ),
				'errorText'      => esc_html__( 'Unable to connect. Please check the provider settings.', 'subscriptions-for-woocommerce' ),
				'selectProvider' => esc_html__( 'Select a provider before testing.', 'subscriptions-for-woocommerce' ),
			)
		);
	}

	/**
	 * AJAX handler for testing provider connection.
	 *
	 * @return void
	 */
	public function test_connection_ajax() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to test AI settings.', 'subscriptions-for-woocommerce' ) ), 403 );
		}

		$provider = sanitize_key( $this->get_posted_text( 'provider' ) );
		if ( ! in_array( $provider, array_keys( wps_ai_provider()->get_supported_providers() ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a valid AI provider.', 'subscriptions-for-woocommerce' ) ), 400 );
		}

		$filters = $this->add_temporary_option_filters( $provider );
		$result  = wps_ai_provider()->test_connection();
		$this->remove_temporary_option_filters( $filters );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'Connected successfully.', 'subscriptions-for-woocommerce' ) ) );
	}

	/**
	 * Check Pro plugin state.
	 *
	 * @return bool
	 */
	public function is_pro_active() {
		return (bool) apply_filters( 'wsp_sfw_check_pro_plugin', false );
	}

	/**
	 * Get asset version.
	 *
	 * @param string $relative_path Relative asset path.
	 * @return string
	 */
	private function asset_version( $relative_path ) {
		$path = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . ltrim( $relative_path, '/' );
		if ( file_exists( $path ) ) {
			$mtime = filemtime( $path );
			if ( false !== $mtime ) {
				return (string) $mtime;
			}
		}

		return (string) $this->version;
	}

	/**
	 * Temporarily override option reads using the posted AJAX payload.
	 *
	 * @param string $provider Provider slug.
	 * @return array
	 */
	private function add_temporary_option_filters( $provider ) {
		$posted_values = array(
			'wps_ai_provider'             => $provider,
			'wps_ai_api_key_' . $provider => '' !== trim( $this->get_posted_text( 'api_key' ) ) ? $this->get_posted_text( 'api_key' ) : get_option( 'wps_ai_api_key_' . $provider, '' ),
			'wps_ai_model_' . $provider   => isset( $_POST['model'] ) ? $this->get_posted_text( 'model' ) : get_option( 'wps_ai_model_' . $provider, '' ),
			'wps_ai_custom_endpoint'      => isset( $_POST['custom_endpoint'] ) ? esc_url_raw( $this->get_posted_text( 'custom_endpoint' ) ) : get_option( 'wps_ai_custom_endpoint', '' ),
		);

		$filters = array();
		foreach ( $posted_values as $option_name => $option_value ) {
			$callback = function() use ( $option_value ) {
				return $option_value;
			};

			add_filter( 'pre_option_' . $option_name, $callback );
			$filters[] = array( $option_name, $callback );
		}

		return $filters;
	}

	/**
	 * Remove temporary option filters.
	 *
	 * @param array $filters Filters returned by add_temporary_option_filters().
	 * @return void
	 */
	private function remove_temporary_option_filters( $filters ) {
		foreach ( $filters as $filter ) {
			remove_filter( 'pre_option_' . $filter[0], $filter[1] );
		}
	}

	/**
	 * Read a scalar POST value and sanitize it as text.
	 *
	 * @param string $key     POST key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function get_posted_text( $key, $default = '' ) {
		if ( ! isset( $_POST[ $key ] ) || is_array( $_POST[ $key ] ) ) {
			return $default;
		}

		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
	}
}
