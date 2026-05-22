<?php
/**
 * Shared AI provider abstraction.
 *
 * @package Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles server-side calls to configured AI providers.
 */
class WPS_AI_Provider {

	/**
	 * Default API timeout in seconds.
	 */
	const DEFAULT_TIMEOUT = 15;

	/**
	 * Extended API timeout in seconds.
	 */
	const EXTENDED_TIMEOUT = 45;

	/**
	 * Singleton instance.
	 *
	 * @var WPS_AI_Provider|null
	 */
	private static $instance = null;

	/**
	 * Supported providers.
	 *
	 * @var array
	 */
	private $supported_providers = array( 'openai', 'anthropic', 'huggingface', 'custom' );

	/**
	 * Get singleton instance.
	 *
	 * @return WPS_AI_Provider
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get singleton instance.
	 *
	 * @return WPS_AI_Provider
	 */
	public static function get_instance() {
		return self::instance();
	}

	/**
	 * Complete a chat-style prompt.
	 *
	 * @param array $messages Chat messages, each with role and content.
	 * @param array $args     Optional request args.
	 * @return string|WP_Error
	 */
	public function complete( $messages, $args = array() ) {
		$messages = $this->normalize_messages( $messages );

		if ( empty( $messages ) ) {
			return new WP_Error( 'wps_ai_empty_prompt', __( 'AI prompt cannot be empty.', 'subscriptions-for-woocommerce' ) );
		}

		$config = $this->get_config();
		if ( is_wp_error( $config ) ) {
			return $config;
		}

		$rate_limit = $this->check_rate_limit();
		if ( is_wp_error( $rate_limit ) ) {
			return $rate_limit;
		}

		$args = wp_parse_args(
			$args,
			array(
				'temperature' => 0.2,
				'max_tokens'  => 1200,
			)
		);

		$request = apply_filters( 'wps_ai_provider_request', $this->build_request( $config, $messages, $args ), $config, $messages, $args );
		if ( is_wp_error( $request ) ) {
			return $request;
		}

		if ( ! is_array( $request ) || empty( $request['endpoint'] ) || empty( $request['args'] ) || ! is_array( $request['args'] ) ) {
			return new WP_Error( 'wps_ai_invalid_request', __( 'AI provider request is invalid.', 'subscriptions-for-woocommerce' ) );
		}

		$this->increment_rate_limit();

		$response = wp_remote_post( $request['endpoint'], $request['args'] );
		if ( is_wp_error( $response ) ) {
			$this->log( 'AI request failed: ' . $response->get_error_message() );
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			$message = $this->extract_error_message( $body );
			$this->log( 'AI request returned HTTP ' . $code . ': ' . $message );

			return new WP_Error(
				'wps_ai_http_error',
				$message,
				array(
					'status' => $code,
					'body'   => $body,
				)
			);
		}

		return $this->extract_completion_text( $config['provider'], $body );
	}

	/**
	 * Complete a prompt and decode the response as JSON.
	 *
	 * @param array $messages Chat messages.
	 * @param array $args     Optional request args.
	 * @return array|WP_Error
	 */
	public function complete_json( $messages, $args = array() ) {
		$text = $this->complete( $messages, $args );

		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = json_decode( $this->strip_json_fences( $text ), true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new WP_Error( 'wps_ai_invalid_json', __( 'AI response was not valid JSON.', 'subscriptions-for-woocommerce' ), array( 'response' => $text ) );
		}

		return $decoded;
	}

	/**
	 * Make a small request to validate the selected provider configuration.
	 *
	 * @return true|WP_Error
	 */
	public function test_connection() {
		$result = $this->complete(
			array(
				array(
					'role'    => 'system',
					'content' => 'Respond with only the word connected.',
				),
				array(
					'role'    => 'user',
					'content' => 'Connection test.',
				),
			),
			array(
				'max_tokens'  => 20,
				'temperature' => 0,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Get the normalized AI provider config.
	 *
	 * @return array|WP_Error
	 */
	public function get_config() {
		$provider = sanitize_key( get_option( 'wps_ai_provider', '' ) );

		if ( empty( $provider ) ) {
			return new WP_Error( 'wps_ai_provider_missing', __( 'Please select an AI provider.', 'subscriptions-for-woocommerce' ) );
		}

		if ( ! in_array( $provider, $this->supported_providers, true ) ) {
			return new WP_Error( 'wps_ai_provider_invalid', __( 'Selected AI provider is not supported.', 'subscriptions-for-woocommerce' ) );
		}

		$api_key  = $this->get_api_key( $provider );
		$endpoint = '';

		if ( 'custom' === $provider ) {
			$endpoint = esc_url_raw( get_option( 'wps_ai_custom_endpoint', '' ) );
			if ( empty( $endpoint ) || 0 !== strpos( $endpoint, 'https://' ) ) {
				return new WP_Error( 'wps_ai_custom_endpoint_missing', __( 'Please enter a valid HTTPS custom AI endpoint.', 'subscriptions-for-woocommerce' ) );
			}
		}

		if ( empty( $api_key ) && 'custom' !== $provider ) {
			return new WP_Error( 'wps_ai_api_key_missing', __( 'Please enter the API key for the selected AI provider.', 'subscriptions-for-woocommerce' ) );
		}

		return apply_filters(
			'wps_ai_provider_config',
			array(
				'provider' => $provider,
				'api_key'  => $api_key,
				'model'    => $this->get_model( $provider ),
				'endpoint' => $endpoint,
				'timeout'  => $this->get_timeout(),
			),
			$provider
		);
	}

	/**
	 * Get supported provider labels.
	 *
	 * @return array
	 */
	public function get_supported_providers() {
		return array(
			'openai'      => __( 'ChatGPT / OpenAI', 'subscriptions-for-woocommerce' ),
			'anthropic'   => __( 'Anthropic (Claude)', 'subscriptions-for-woocommerce' ),
			'huggingface' => __( 'HuggingFace', 'subscriptions-for-woocommerce' ),
			'custom'      => __( 'Custom Endpoint', 'subscriptions-for-woocommerce' ),
		);
	}

	/**
	 * Prepare an API key before saving it to wp_options.
	 *
	 * @param string $api_key API key.
	 * @return string
	 */
	public function prepare_api_key_for_storage( $api_key ) {
		$api_key = trim( (string) $api_key );

		if ( '' === $api_key || ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'random_bytes' ) ) {
			return $api_key;
		}

		try {
			$iv = random_bytes( 16 );
		} catch ( Exception $e ) {
			return $api_key;
		}

		$key    = hash( 'sha256', wp_salt( 'auth' ), true );
		$cipher = openssl_encrypt( $api_key, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $cipher ) {
			return $api_key;
		}

		return 'wps_ai_enc:v1:' . base64_encode( $iv ) . ':' . base64_encode( $cipher );
	}

	/**
	 * Mask an API key for display.
	 *
	 * @param string $api_key API key.
	 * @return string
	 */
	public function mask_api_key( $api_key ) {
		$api_key = $this->maybe_decrypt_api_key( (string) $api_key );

		if ( '' === $api_key ) {
			return '';
		}

		$length = strlen( $api_key );
		if ( $length <= 8 ) {
			return str_repeat( '*', $length );
		}

		return substr( $api_key, 0, 4 ) . str_repeat( '*', max( 4, $length - 8 ) ) . substr( $api_key, -4 );
	}

	/**
	 * Get the configured model for a provider.
	 *
	 * @param string $provider Provider slug.
	 * @return string
	 */
	public function get_model( $provider ) {
		$defaults = array(
			'openai'      => 'gpt-4o',
			'anthropic'   => 'claude-sonnet-4-5',
			'huggingface' => 'deepseek-ai/DeepSeek-R1:fastest',
			'custom'      => '',
		);

		$provider = sanitize_key( $provider );
		$model    = sanitize_text_field( get_option( 'wps_ai_model_' . $provider, '' ) );

		if ( empty( $model ) && isset( $defaults[ $provider ] ) ) {
			$model = $defaults[ $provider ];
		}

		return $model;
	}

	/**
	 * Get the configured timeout.
	 *
	 * @return int
	 */
	public function get_timeout() {
		return '1' === (string) get_option( 'wps_ai_timeout_extended', '0' ) ? self::EXTENDED_TIMEOUT : self::DEFAULT_TIMEOUT;
	}

	/**
	 * Check whether the current daily request limit allows another call.
	 *
	 * @return true|WP_Error
	 */
	public function check_rate_limit() {
		if ( '1' !== (string) get_option( 'wps_ai_rate_limit_enabled', '0' ) ) {
			return true;
		}

		$limit = absint( get_option( 'wps_ai_rate_limit_count', 100 ) );
		if ( 0 === $limit ) {
			return true;
		}

		$count = absint( get_transient( $this->get_rate_limit_transient_key() ) );
		if ( $count >= $limit ) {
			return new WP_Error( 'wps_ai_daily_limit_reached', __( 'Daily AI limit reached. Resets at midnight.', 'subscriptions-for-woocommerce' ) );
		}

		return true;
	}

	/**
	 * Get current daily request count.
	 *
	 * @return int
	 */
	public function get_daily_request_count() {
		return absint( get_transient( $this->get_rate_limit_transient_key() ) );
	}

	/**
	 * Get selected provider API key.
	 *
	 * @param string $provider Provider slug.
	 * @return string
	 */
	private function get_api_key( $provider ) {
		$api_key = get_option( 'wps_ai_api_key_' . sanitize_key( $provider ), '' );

		if ( is_string( $api_key ) ) {
			return trim( $this->maybe_decrypt_api_key( $api_key ) );
		}

		return '';
	}

	/**
	 * Decrypt an API key when it was stored by this provider.
	 *
	 * @param string $stored_value Stored option value.
	 * @return string
	 */
	private function maybe_decrypt_api_key( $stored_value ) {
		$stored_value = trim( (string) $stored_value );

		if ( 0 !== strpos( $stored_value, 'wps_ai_enc:v1:' ) ) {
			return $stored_value;
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$parts = explode( ':', $stored_value );
		if ( 4 !== count( $parts ) ) {
			return '';
		}

		$iv     = base64_decode( $parts[2], true );
		$cipher = base64_decode( $parts[3], true );

		if ( false === $iv || false === $cipher ) {
			return '';
		}

		$key       = hash( 'sha256', wp_salt( 'auth' ), true );
		$decrypted = openssl_decrypt( $cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * Build a provider-specific HTTP request.
	 *
	 * @param array $config   Provider config.
	 * @param array $messages Normalized messages.
	 * @param array $args     Request args.
	 * @return array|WP_Error
	 */
	private function build_request( $config, $messages, $args ) {
		switch ( $config['provider'] ) {
			case 'openai':
				return $this->build_openai_request( $config, $messages, $args );

			case 'anthropic':
				return $this->build_anthropic_request( $config, $messages, $args );

			case 'huggingface':
				return $this->build_huggingface_request( $config, $messages, $args );

			case 'custom':
				return $this->build_custom_request( $config, $messages, $args );
		}

		return new WP_Error( 'wps_ai_provider_invalid', __( 'Selected AI provider is not supported.', 'subscriptions-for-woocommerce' ) );
	}

	/**
	 * Build an OpenAI request.
	 *
	 * @param array $config   Provider config.
	 * @param array $messages Normalized messages.
	 * @param array $args     Request args.
	 * @return array
	 */
	private function build_openai_request( $config, $messages, $args ) {
		return array(
			'endpoint' => 'https://api.openai.com/v1/chat/completions',
			'args'     => array(
				'timeout' => $config['timeout'],
				'headers' => array(
					'Authorization' => 'Bearer ' . $config['api_key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $config['model'],
						'messages'    => $messages,
						'temperature' => (float) $args['temperature'],
						'max_tokens'  => absint( $args['max_tokens'] ),
					)
				),
			),
		);
	}

	/**
	 * Build an Anthropic request.
	 *
	 * @param array $config   Provider config.
	 * @param array $messages Normalized messages.
	 * @param array $args     Request args.
	 * @return array
	 */
	private function build_anthropic_request( $config, $messages, $args ) {
		$system_prompt      = '';
		$anthropic_messages = array();

		foreach ( $messages as $message ) {
			if ( 'system' === $message['role'] ) {
				$system_prompt .= ( empty( $system_prompt ) ? '' : "\n\n" ) . $message['content'];
				continue;
			}

			$role = 'assistant' === $message['role'] ? 'assistant' : 'user';
			$anthropic_messages[] = array(
				'role'    => $role,
				'content' => $message['content'],
			);
		}

		$body = array(
			'model'       => $config['model'],
			'messages'    => $anthropic_messages,
			'temperature' => (float) $args['temperature'],
			'max_tokens'  => absint( $args['max_tokens'] ),
		);

		if ( ! empty( $system_prompt ) ) {
			$body['system'] = $system_prompt;
		}

		return array(
			'endpoint' => 'https://api.anthropic.com/v1/messages',
			'args'     => array(
				'timeout' => $config['timeout'],
				'headers' => array(
					'x-api-key'         => $config['api_key'],
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			),
		);
	}

	/**
	 * Build a HuggingFace inference request.
	 *
	 * @param array $config   Provider config.
	 * @param array $messages Normalized messages.
	 * @param array $args     Request args.
	 * @return array
	 */
	private function build_huggingface_request( $config, $messages, $args ) {
		return array(
			'endpoint' => 'https://router.huggingface.co/v1/chat/completions',
			'args'     => array(
				'timeout' => $config['timeout'],
				'headers' => array(
					'Authorization' => 'Bearer ' . $config['api_key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $config['model'],
						'messages'    => $messages,
						'temperature' => (float) $args['temperature'],
						'max_tokens'  => absint( $args['max_tokens'] ),
					)
				),
			),
		);
	}

	/**
	 * Build a custom endpoint request.
	 *
	 * @param array $config   Provider config.
	 * @param array $messages Normalized messages.
	 * @param array $args     Request args.
	 * @return array
	 */
	private function build_custom_request( $config, $messages, $args ) {
		$headers = array(
			'Content-Type' => 'application/json',
		);

		if ( ! empty( $config['api_key'] ) ) {
			$headers['Authorization'] = 'Bearer ' . $config['api_key'];
		}

		return array(
			'endpoint' => $config['endpoint'],
			'args'     => array(
				'timeout' => $config['timeout'],
				'headers' => $headers,
				'body'    => wp_json_encode(
					array(
						'provider'    => 'custom',
						'model'       => $config['model'],
						'messages'    => $messages,
						'prompt'      => $this->messages_to_prompt( $messages ),
						'temperature' => (float) $args['temperature'],
						'max_tokens'  => absint( $args['max_tokens'] ),
					)
				),
			),
		);
	}

	/**
	 * Extract completion text from a provider response body.
	 *
	 * @param string $provider Provider slug.
	 * @param string $body     Raw response body.
	 * @return string|WP_Error
	 */
	private function extract_completion_text( $provider, $body ) {
		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'wps_ai_invalid_response', __( 'AI provider returned invalid JSON.', 'subscriptions-for-woocommerce' ), array( 'body' => $body ) );
		}

		switch ( $provider ) {
			case 'openai':
				if ( isset( $data['choices'][0]['message']['content'] ) ) {
					return trim( (string) $data['choices'][0]['message']['content'] );
				}
				break;

			case 'anthropic':
				if ( isset( $data['content'][0]['text'] ) ) {
					return trim( (string) $data['content'][0]['text'] );
				}
				break;

			case 'huggingface':
				if ( isset( $data['choices'][0]['message']['content'] ) ) {
					return trim( (string) $data['choices'][0]['message']['content'] );
				}
				if ( isset( $data[0]['generated_text'] ) ) {
					return trim( (string) $data[0]['generated_text'] );
				}
				if ( isset( $data['generated_text'] ) ) {
					return trim( (string) $data['generated_text'] );
				}
				break;

			case 'custom':
				if ( isset( $data['content'] ) ) {
					return trim( (string) $data['content'] );
				}
				if ( isset( $data['text'] ) ) {
					return trim( (string) $data['text'] );
				}
				if ( isset( $data['message'] ) ) {
					return trim( (string) $data['message'] );
				}
				if ( isset( $data['choices'][0]['message']['content'] ) ) {
					return trim( (string) $data['choices'][0]['message']['content'] );
				}
				break;
		}

		return new WP_Error( 'wps_ai_empty_response', __( 'AI provider returned an empty response.', 'subscriptions-for-woocommerce' ), array( 'body' => $body ) );
	}

	/**
	 * Extract a useful error message from an error body.
	 *
	 * @param string $body Raw body.
	 * @return string
	 */
	private function extract_error_message( $body ) {
		$data = json_decode( (string) $body, true );

		if ( is_array( $data ) ) {
			if ( isset( $data['error']['message'] ) ) {
				return sanitize_text_field( $data['error']['message'] );
			}
			if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
				return sanitize_text_field( $data['error'] );
			}
			if ( isset( $data['message'] ) && is_string( $data['message'] ) ) {
				return sanitize_text_field( $data['message'] );
			}
		}

		return __( 'AI provider request failed.', 'subscriptions-for-woocommerce' );
	}

	/**
	 * Normalize chat messages.
	 *
	 * @param array $messages Raw messages.
	 * @return array
	 */
	private function normalize_messages( $messages ) {
		if ( ! is_array( $messages ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $messages as $message ) {
			if ( ! is_array( $message ) || empty( $message['content'] ) || ! is_scalar( $message['content'] ) ) {
				continue;
			}

			$role = isset( $message['role'] ) ? sanitize_key( $message['role'] ) : 'user';
			if ( ! in_array( $role, array( 'system', 'user', 'assistant' ), true ) ) {
				$role = 'user';
			}

			$normalized[] = array(
				'role'    => $role,
				'content' => sanitize_textarea_field( (string) $message['content'] ),
			);
		}

		return $normalized;
	}

	/**
	 * Convert messages to a plain prompt for providers without chat messages.
	 *
	 * @param array $messages Normalized messages.
	 * @return string
	 */
	private function messages_to_prompt( $messages ) {
		$parts = array();

		foreach ( $messages as $message ) {
			$parts[] = strtoupper( $message['role'] ) . ': ' . $message['content'];
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Strip common markdown fences around JSON.
	 *
	 * @param string $text AI text.
	 * @return string
	 */
	private function strip_json_fences( $text ) {
		$text = trim( (string) $text );
		$text = preg_replace( '/^```(?:json)?\s*/i', '', $text );
		$text = preg_replace( '/\s*```$/', '', $text );

		return trim( $text );
	}

	/**
	 * Increment daily request counter.
	 *
	 * @return void
	 */
	private function increment_rate_limit() {
		if ( '1' !== (string) get_option( 'wps_ai_rate_limit_enabled', '0' ) ) {
			return;
		}

		$key   = $this->get_rate_limit_transient_key();
		$count = absint( get_transient( $key ) );

		set_transient( $key, $count + 1, $this->get_seconds_until_midnight() );
	}

	/**
	 * Get daily rate limit transient key.
	 *
	 * @return string
	 */
	private function get_rate_limit_transient_key() {
		return 'wps_ai_daily_count_' . gmdate( 'Y_m_d', current_time( 'timestamp' ) );
	}

	/**
	 * Get seconds until the site's next midnight.
	 *
	 * @return int
	 */
	private function get_seconds_until_midnight() {
		$now      = current_time( 'timestamp' );
		$tomorrow = strtotime( 'tomorrow', $now );

		return max( HOUR_IN_SECONDS, $tomorrow - $now );
	}

	/**
	 * Log provider diagnostics when plugin logging is enabled.
	 *
	 * @param string $message Log message.
	 * @return void
	 */
	private function log( $message ) {
		if ( class_exists( 'Subscriptions_For_Woocommerce_Log' ) ) {
			Subscriptions_For_Woocommerce_Log::log( '[AI] ' . $message );
		}
	}
}

if ( ! function_exists( 'wps_ai_provider' ) ) {
	/**
	 * Helper for accessing the shared AI provider.
	 *
	 * @return WPS_AI_Provider
	 */
	function wps_ai_provider() {
		return WPS_AI_Provider::instance();
	}
}
