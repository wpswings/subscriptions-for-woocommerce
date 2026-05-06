<?php
/**
 * Talk to an Expert feature for the admin dashboard.
 *
 * @package Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the Talk to an Expert modal, sanitization, and HubSpot submission.
 */
class Subscriptions_For_Woocommerce_Talk_To_Expert_Form {

	/**
	 * AJAX action.
	 */
	const AJAX_ACTION = 'wps_sfw_submit_talk_to_expert';

	/**
	 * Nonce action.
	 */
	const NONCE_ACTION = 'wps_sfw_talk_to_expert_nonce';

	/**
	 * HubSpot portal ID.
	 */
	const HUBSPOT_PORTAL_ID = '25444144';

	/**
	 * HubSpot form ID.
	 */
	const HUBSPOT_FORM_ID = 'eab973a7-5c65-4264-a31d-3b1b10b82c82';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get service options.
	 *
	 * @return array
	 */
	public function get_service_options() {
		return array(
			'seo_services'                      => __( 'SEO services', 'subscriptions-for-woocommerce' ),
			'google_ads_setup_and_ga4_setup'   => __( 'Google Ads Setup and GA4 setup', 'subscriptions-for-woocommerce' ),
			'speed_optimization'               => __( 'Speed Optimization', 'subscriptions-for-woocommerce' ),
			'woocommerce_development_services' => __( 'WooCommerce Development Services', 'subscriptions-for-woocommerce' ),
		);
	}

	/**
	 * Get card service metadata.
	 *
	 * @return array
	 */
	private function get_service_cards() {
		return array(
			array(
				'value'       => 'seo_services',
				'label'       => __( 'SEO Services', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Improve rankings & organic traffic', 'subscriptions-for-woocommerce' ),
				'icon'        => 'dashicons-search',
				'accent'      => 'gold',
			),
			array(
				'value'       => 'google_ads_setup_and_ga4_setup',
				'label'       => __( 'Google Ads Setup And G4 Setup', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Run profitable ad campaigns', 'subscriptions-for-woocommerce' ),
				'icon'        => 'dashicons-chart-line',
				'accent'      => 'violet',
			),
			array(
				'value'       => 'speed_optimization',
				'label'       => __( 'Speed Optimization', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Faster store, happier customers', 'subscriptions-for-woocommerce' ),
				'icon'        => 'dashicons-dashboard',
				'accent'      => 'violet',
			),
			array(
				'value'       => 'woocommerce_development_services',
				'label'       => __( 'WooCommerce Development Services', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Custom Solution For your store needs', 'subscriptions-for-woocommerce' ),
				'icon'        => 'dashicons-store',
				'accent'      => 'violet',
			),
		);
	}

	/**
	 * Get budget options.
	 *
	 * @return array
	 */
	public function get_budget_options() {
		return array(
			''                => __( 'Please Select', 'subscriptions-for-woocommerce' ),
			'$500 - $1000'    => '$500 - $1000',
			'$1001 - $5000'   => '$1001 - $5000',
			'$5001 - $10000'  => '$5001 - $10000',
			'$10001 - $15000' => '$10001 - $15000',
		);
	}

	/**
	 * Get plugin label for HubSpot context.
	 *
	 * @return string
	 */
	public static function wps_sfw_get_plugin_label() {
		return __( 'Subscriptions For WooCommerce', 'subscriptions-for-woocommerce' );
	}

	/**
	 * Render the sidebar card CTA.
	 *
	 * @return void
	 */
	public function render_sidebar_card() {
		$services = $this->get_service_cards();
		$service_url = 'https://wpswings.com/woocommerce-services/?utm_source=wpswings-rma-services&utm_medium=rma-org-backend&utm_campaign=woocommerce-services';
		?>
		<div class="wps-sfw-sidebar-card wps-sfw-sidebar-card--expert">
			<div class="wps-sfw-expert-card__head">
				<div class="wps-sfw-expert-card__intro">
					<h3><?php esc_html_e( 'Grow Your Store With WP Swings', 'subscriptions-for-woocommerce' ); ?></h3>
					<p><?php esc_html_e( "Expert solutions to boost your store's performance.", 'subscriptions-for-woocommerce' ); ?></p>
				</div>
				<div class="wps-sfw-expert-card__badge" aria-hidden="true">
					<span class="dashicons dashicons-star-filled"></span>
				</div>
			</div>

			<div class="wps-sfw-expert-card__services">
				<?php foreach ( $services as $service ) : ?>
					<a class="wps-sfw-expert-card__service wps-sfw-expert-card__service--<?php echo esc_attr( $service['accent'] ); ?>" href="<?php echo esc_url( $service_url ); ?>" target="_blank" rel="noopener noreferrer">
						<div class="wps-sfw-expert-card__service-icon" aria-hidden="true">
							<span class="dashicons <?php echo esc_attr( $service['icon'] ); ?>"></span>
						</div>
						<div class="wps-sfw-expert-card__service-copy">
							<h4><?php echo esc_html( $service['label'] ); ?></h4>
							<p><?php echo esc_html( $service['description'] ); ?></p>
						</div>
						<div class="wps-sfw-expert-card__service-arrow" aria-hidden="true">
							<span class="dashicons dashicons-arrow-right-alt2"></span>
						</div>
					</a>
				<?php endforeach; ?>
			</div>

			<button type="button" class="wps-sfw-primary-action wps-sfw-expert-card__button" data-wps-sfw-open-expert-modal>
				<?php esc_html_e( 'Talk to an Expert', 'subscriptions-for-woocommerce' ); ?>
			</button>
			<p class="wps-sfw-expert-card__footer"><?php esc_html_e( 'Services by WP Swings', 'subscriptions-for-woocommerce' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render modal markup.
	 *
	 * @return void
	 */
	public function render_modal() {
		$user    = wp_get_current_user();
		$prefill = $this->get_prefill_data( $user );
		$budgets = $this->get_budget_options();
		$services = $this->get_service_options();
		?>
		<div class="wps-sfw-expert-modal" data-wps-sfw-expert-modal aria-hidden="true">
			<div class="wps-sfw-expert-modal__backdrop" data-wps-sfw-close-expert-modal></div>
			<div class="wps-sfw-expert-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="wps-sfw-expert-modal-title">
				<button type="button" class="wps-sfw-expert-modal__close" data-wps-sfw-close-expert-modal aria-label="<?php esc_attr_e( 'Close expert form', 'subscriptions-for-woocommerce' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>

				<div class="wps-sfw-expert-modal__header">
					<p class="wps-sfw-expert-modal__eyebrow"><?php esc_html_e( 'Talk To An Expert', 'subscriptions-for-woocommerce' ); ?></p>
					<h2 id="wps-sfw-expert-modal-title"><?php esc_html_e( 'Tell us what you need help with', 'subscriptions-for-woocommerce' ); ?></h2>
					<p><?php esc_html_e( 'Share a few details and our team will reach out with the right service guidance for your store.', 'subscriptions-for-woocommerce' ); ?></p>
				</div>

				<div class="wps-sfw-expert-modal__body">
					<form class="wps-sfw-expert-form" data-wps-sfw-expert-form novalidate>
						<div class="wps-sfw-expert-form__error" data-wps-sfw-expert-error hidden></div>

						<div class="wps-sfw-expert-form__grid">
							<div class="wps-sfw-expert-form__field">
								<label for="wps-sfw-expert-firstname"><?php esc_html_e( 'First name', 'subscriptions-for-woocommerce' ); ?></label>
								<input type="text" id="wps-sfw-expert-firstname" name="firstname" value="<?php echo esc_attr( $prefill['firstname'] ); ?>" placeholder="<?php esc_attr_e( 'First name', 'subscriptions-for-woocommerce' ); ?>">
							</div>
							<div class="wps-sfw-expert-form__field">
								<label for="wps-sfw-expert-lastname"><?php esc_html_e( 'Last name', 'subscriptions-for-woocommerce' ); ?></label>
								<input type="text" id="wps-sfw-expert-lastname" name="lastname" value="<?php echo esc_attr( $prefill['lastname'] ); ?>" placeholder="<?php esc_attr_e( 'Last name', 'subscriptions-for-woocommerce' ); ?>">
							</div>
						</div>

						<div class="wps-sfw-expert-form__grid">
							<div class="wps-sfw-expert-form__field">
								<label for="wps-sfw-expert-email"><?php esc_html_e( 'Email', 'subscriptions-for-woocommerce' ); ?></label>
								<input type="email" id="wps-sfw-expert-email" name="email" value="<?php echo esc_attr( $prefill['email'] ); ?>" placeholder="<?php esc_attr_e( 'Email', 'subscriptions-for-woocommerce' ); ?>" required>
							</div>
							<div class="wps-sfw-expert-form__field">
								<label for="wps-sfw-expert-phone"><?php esc_html_e( 'Phone', 'subscriptions-for-woocommerce' ); ?></label>
								<input type="text" id="wps-sfw-expert-phone" name="phone" value="" placeholder="<?php esc_attr_e( 'Phone number', 'subscriptions-for-woocommerce' ); ?>">
							</div>
						</div>

						<fieldset class="wps-sfw-expert-form__fieldset">
							<legend><?php esc_html_e( 'What services do you need help with?', 'subscriptions-for-woocommerce' ); ?></legend>
							<div class="wps-sfw-expert-form__checks">
								<?php foreach ( $services as $service_value => $service_label ) : ?>
									<label class="wps-sfw-expert-form__check">
										<input type="checkbox" name="what_services_do_you_need_help_with[]" value="<?php echo esc_attr( $service_value ); ?>">
										<span><?php echo esc_html( $service_label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<div class="wps-sfw-expert-form__field">
							<label for="wps-sfw-expert-budget"><?php esc_html_e( 'Budget', 'subscriptions-for-woocommerce' ); ?></label>
							<select id="wps-sfw-expert-budget" name="budget">
								<?php foreach ( $budgets as $budget_value => $budget_label ) : ?>
									<option value="<?php echo esc_attr( $budget_value ); ?>" <?php disabled( '' === $budget_value ); ?> <?php selected( '', $budget_value ); ?>>
										<?php echo esc_html( $budget_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="wps-sfw-expert-form__field">
							<label for="wps-sfw-expert-message"><?php esc_html_e( 'Message', 'subscriptions-for-woocommerce' ); ?></label>
							<textarea id="wps-sfw-expert-message" name="message" rows="4" placeholder="<?php esc_attr_e( 'Tell us about your store, goals, or the service outcome you want.', 'subscriptions-for-woocommerce' ); ?>"></textarea>
						</div>

						<div class="wps-sfw-expert-form__actions">
							<button type="submit" class="wps-sfw-primary-action" data-wps-sfw-expert-submit data-default-label="<?php esc_attr_e( 'Submit Request', 'subscriptions-for-woocommerce' ); ?>" data-loading-label="<?php esc_attr_e( 'Submitting...', 'subscriptions-for-woocommerce' ); ?>">
								<?php esc_html_e( 'Submit Request', 'subscriptions-for-woocommerce' ); ?>
							</button>
						</div>
					</form>

					<div class="wps-sfw-expert-success" data-wps-sfw-expert-success hidden>
						<div class="wps-sfw-expert-success__icon">
							<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						</div>
						<h3><?php esc_html_e( 'Request submitted', 'subscriptions-for-woocommerce' ); ?></h3>
						<p data-wps-sfw-expert-success-message><?php esc_html_e( 'Thank you for submitting your request.', 'subscriptions-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for form submission.
	 *
	 * @return void
	 */
	public function submit_form_ajax() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to submit this request.', 'subscriptions-for-woocommerce' ),
				),
				403
			);
		}

		$form_data = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$decoded   = json_decode( $form_data, true );

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid form submission.', 'subscriptions-for-woocommerce' ),
				),
				400
			);
		}

		$sanitized = $this->sanitize_submission( $decoded );
		$response  = $this->submit_to_hubspot( $sanitized );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => $response->get_error_message(),
				),
				500
			);
		}

		$body            = json_decode( wp_remote_retrieve_body( $response ), true );
		$success_message = ! empty( $body['inlineMessage'] ) ? $this->cleanup_success_message( $body['inlineMessage'] ) : __( 'Thank you for submitting your request.', 'subscriptions-for-woocommerce' );
		$status_code     = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_message = ! empty( $body['message'] ) ? sanitize_text_field( $body['message'] ) : __( 'Unable to submit the request right now. Please try again.', 'subscriptions-for-woocommerce' );
			wp_send_json_error(
				array(
					'message' => $error_message,
				),
				$status_code ? $status_code : 500
			);
		}

		wp_send_json_success(
			array(
				'message' => $success_message,
			)
		);
	}

	/**
	 * Build prefill data for the modal.
	 *
	 * @param WP_User $user Current user.
	 * @return array
	 */
	private function get_prefill_data( $user ) {
		$firstname   = '';
		$lastname    = '';
		$email       = $user instanceof WP_User ? $user->user_email : '';
		$display_name = $user instanceof WP_User ? trim( (string) $user->display_name ) : '';

		if ( $user instanceof WP_User ) {
			$firstname = trim( (string) $user->user_firstname );
			$lastname  = trim( (string) $user->user_lastname );
		}

		if ( '' === $firstname && '' !== $display_name ) {
			$name_parts = preg_split( '/\s+/', $display_name );
			$firstname  = ! empty( $name_parts[0] ) ? $name_parts[0] : '';
			if ( empty( $lastname ) && count( $name_parts ) > 1 ) {
				$lastname = trim( implode( ' ', array_slice( $name_parts, 1 ) ) );
			}
		}

		return array(
			'firstname' => $firstname,
			'lastname'  => $lastname,
			'email'     => $email,
		);
	}

	/**
	 * Sanitize incoming submission.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	private function sanitize_submission( $data ) {
		$service_options = $this->get_service_options();
		$budget_options  = $this->get_budget_options();
		$services        = array();
		$raw_services    = isset( $data['what_services_do_you_need_help_with'] ) ? $data['what_services_do_you_need_help_with'] : array();

		if ( ! is_array( $raw_services ) ) {
			$raw_services = array( $raw_services );
		}

		foreach ( $raw_services as $service ) {
			$service = sanitize_text_field( (string) $service );
			if ( array_key_exists( $service, $service_options ) ) {
				$services[] = $service;
			}
		}

		$budget = isset( $data['budget'] ) ? sanitize_text_field( (string) $data['budget'] ) : '';
		if ( ! array_key_exists( $budget, $budget_options ) ) {
			$budget = '';
		}

		return array(
			'firstname'                           => isset( $data['firstname'] ) ? sanitize_text_field( (string) $data['firstname'] ) : '',
			'lastname'                            => isset( $data['lastname'] ) ? sanitize_text_field( (string) $data['lastname'] ) : '',
			'email'                               => isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '',
			'phone'                               => isset( $data['phone'] ) ? sanitize_text_field( (string) $data['phone'] ) : '',
			'what_services_do_you_need_help_with' => array_values( array_unique( $services ) ),
			'budget'                              => '' === $budget ? '' : $budget,
			'message'                             => isset( $data['message'] ) ? sanitize_textarea_field( (string) $data['message'] ) : '',
		);
	}

	/**
	 * Submit payload to HubSpot.
	 *
	 * @param array $sanitized Sanitized form data.
	 * @return array|WP_Error
	 */
	private function submit_to_hubspot( $sanitized ) {
		$endpoint = sprintf(
			'https://api.hsforms.com/submissions/v3/integration/submit/%1$s/%2$s',
			rawurlencode( self::HUBSPOT_PORTAL_ID ),
			rawurlencode( self::HUBSPOT_FORM_ID )
		);

		$fields = array(
			array(
				'name'  => 'firstname',
				'value' => $sanitized['firstname'],
			),
			array(
				'name'  => 'lastname',
				'value' => $sanitized['lastname'],
			),
			array(
				'name'  => 'email',
				'value' => $sanitized['email'],
			),
			array(
				'name'  => 'phone',
				'value' => $sanitized['phone'],
			),
			array(
				'name'  => 'what_services_do_you_need_help_with',
				'value' => $sanitized['what_services_do_you_need_help_with'],
			),
			array(
				'name'  => 'budget',
				'value' => $sanitized['budget'],
			),
			array(
				'name'  => 'message',
				'value' => $sanitized['message'],
			),
			array(
				'name'  => 'currency',
				'value' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			),
			array(
				'name'  => 'org_plugin_name',
				'value' => self::wps_sfw_get_plugin_label(),
			),
			array(
				'name'  => 'company',
				'value' => get_bloginfo( 'name' ),
			),
			array(
				'name'  => 'website',
				'value' => home_url(),
			),
			array(
				'name'  => 'country',
				'value' => $this->get_store_country(),
			),
			array(
				'name'  => 'annualrevenue',
				'value' => $this->get_annual_revenue(),
			),
		);

		$fields = array_values(
			array_filter(
				array_map(
					static function( $field ) {
						if ( is_array( $field['value'] ) ) {
							$field['value'] = implode( ';', array_filter( $field['value'] ) );
						}

						if ( null === $field['value'] || '' === $field['value'] ) {
							return null;
						}

						return $field;
					},
					$fields
				)
			)
		);

		$request = array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
			'body'        => wp_json_encode(
				array(
					'fields'  => $fields,
					'context' => array(
						'pageUri'   => admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu' ),
						'pageName'  => self::wps_sfw_get_plugin_label(),
						'ipAddress' => $this->get_client_ip(),
					),
				)
			),
			'cookies'     => array(),
		);

		return wp_remote_post( $endpoint, $request );
	}

	/**
	 * Get store country.
	 *
	 * @return string
	 */
	private function get_store_country() {
		if ( ! function_exists( 'wc_get_base_location' ) ) {
			return '';
		}

		$base_location = wc_get_base_location();
		$country_code  = ! empty( $base_location['country'] ) ? $base_location['country'] : '';

		if ( '' === $country_code || ! function_exists( 'WC' ) || empty( WC()->countries->countries[ $country_code ] ) ) {
			return $country_code;
		}

		return WC()->countries->countries[ $country_code ];
	}

	/**
	 * Get annual revenue for the store.
	 *
	 * @return string
	 */
	private function get_annual_revenue() {
		global $wpdb;

		$annual_revenue = 0.0;
		$paid_statuses  = array( 'wc-processing', 'wc-completed' );
		$from_date      = gmdate( 'Y-m-d H:i:s', strtotime( '-365 days' ) );
		$table_name     = $wpdb->prefix . 'wc_order_stats';

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $table_exists === $table_name ) {
			$status_placeholders = implode( ',', array_fill( 0, count( $paid_statuses ), '%s' ) );
			$query               = $wpdb->prepare(
				"SELECT SUM(total_sales) FROM {$table_name} WHERE status IN ({$status_placeholders}) AND date_created >= %s",
				array_merge( $paid_statuses, array( $from_date ) )
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$stats_total         = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! empty( $stats_total ) ) {
				$annual_revenue = (float) $stats_total;
			}
		}

		if ( $annual_revenue <= 0 && function_exists( 'wc_get_orders' ) ) {
			$orders = wc_get_orders(
				array(
					'limit'        => -1,
					'status'       => $paid_statuses,
					'date_created' => '>' . strtotime( '-365 days' ),
					'return'       => 'objects',
				)
			);

			if ( ! empty( $orders ) ) {
				foreach ( $orders as $order ) {
					if ( $order instanceof WC_Order ) {
						$annual_revenue += (float) $order->get_total();
					}
				}
			}
		}

		return number_format( $annual_revenue, 2, '.', '' );
	}

	/**
	 * Resolve client IP.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}

			$raw_value = wp_unslash( $_SERVER[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$parts     = array_map( 'trim', explode( ',', $raw_value ) );

			foreach ( $parts as $part ) {
				if ( filter_var( $part, FILTER_VALIDATE_IP ) ) {
					return $part;
				}
			}
		}

		return '';
	}

	/**
	 * Cleanup HubSpot success message.
	 *
	 * @param string $message Raw message.
	 * @return string
	 */
	private function cleanup_success_message( $message ) {
		$message = wp_strip_all_tags( (string) $message );
		$message = html_entity_decode( $message, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$message = preg_replace( '/\s+/', ' ', $message );

		return trim( (string) $message );
	}
}
