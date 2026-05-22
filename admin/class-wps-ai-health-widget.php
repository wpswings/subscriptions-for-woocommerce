<?php
/**
 * AI Insights dashboard widget.
 *
 * @package Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the AI Insights dashboard widget.
 */
class WPS_AI_Health_Widget {

	/**
	 * Refresh AJAX action.
	 */
	const REFRESH_ACTION = 'wps_ai_health_refresh';

	/**
	 * Nonce action.
	 */
	const NONCE_ACTION = 'wps-ai-health-widget-nonce';

	/**
	 * Summary transient key.
	 */
	const TRANSIENT_KEY = 'wps_ai_health_summary';

	/**
	 * Register dashboard widget.
	 *
	 * @return void
	 */
	public function register_widget() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'wps_ai_subscription_health',
			__( 'AI Insights', 'subscriptions-for-woocommerce' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Enqueue widget assets on the WordPress dashboard.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || empty( $screen->id ) || 'dashboard' !== $screen->id ) {
			return;
		}

		wp_enqueue_style( 'wps-ai-health-widget', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-ai-health-widget.css', array(), $this->asset_version( 'admin/css/wps-ai-health-widget.css' ), 'all' );
		wp_enqueue_script( 'wps-ai-health-widget', SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-ai-health-widget.js', array( 'jquery' ), $this->asset_version( 'admin/js/wps-ai-health-widget.js' ), true );

		wp_localize_script(
			'wps-ai-health-widget',
			'wpsAiHealthWidget',
			array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'refreshAction' => self::REFRESH_ACTION,
				'nonce'         => wp_create_nonce( self::NONCE_ACTION ),
				'refreshing'    => __( 'Regenerating...', 'subscriptions-for-woocommerce' ),
				'refreshText'   => __( 'Regenerate', 'subscriptions-for-woocommerce' ),
				'errorText'     => __( 'Unable to refresh AI Insights.', 'subscriptions-for-woocommerce' ),
			)
		);
	}

	/**
	 * Render dashboard widget.
	 *
	 * @return void
	 */
	public function render_widget() {
		$state = $this->get_widget_state();
		include SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/wps-ai-health-widget.php';
	}

	/**
	 * AJAX refresh handler.
	 *
	 * @return void
	 */
	public function refresh_ajax() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to refresh AI Insights.', 'subscriptions-for-woocommerce' ) ), 403 );
		}

		delete_transient( self::TRANSIENT_KEY );
		$state = $this->get_widget_state( true );

		if ( ! empty( $state['error'] ) ) {
			wp_send_json_error( array( 'message' => $state['message'], 'state' => $state ), 400 );
		}

		wp_send_json_success( array( 'state' => $state ) );
	}

	/**
	 * Refresh cached summary when cron runs.
	 *
	 * @return void
	 */
	public function refresh_cached_summary() {
		if ( get_transient( self::TRANSIENT_KEY ) ) {
			return;
		}

		$this->get_widget_state( true );
	}

	/**
	 * Get widget state.
	 *
	 * @param bool $force Whether to force AI refresh.
	 * @return array
	 */
	private function get_widget_state( $force = false ) {
		$counts = $this->get_subscription_counts();
		$state  = array(
			'enabled'   => $this->is_ai_enabled(),
			'ready'     => false,
			'error'     => false,
			'message'   => '',
			'counts'    => $counts,
			'result'    => $this->get_fallback_result( $counts ),
			'settings_url' => admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=wps-subscriptions-ai-settings' ),
		);

		if ( ! $state['enabled'] ) {
			$state['message'] = __( 'Enable AI Settings before using AI Insights.', 'subscriptions-for-woocommerce' );
			return $state;
		}

		$config = wps_ai_provider()->get_config();
		if ( is_wp_error( $config ) ) {
			$state['message'] = $config->get_error_message();
			return $state;
		}

		$state['ready'] = true;
		$cached         = $force ? false : get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			$state['result'] = $this->normalize_ai_result( $cached, $counts );
			return $state;
		}

		$result = $this->generate_ai_summary( $counts );
		if ( is_wp_error( $result ) ) {
			$state['error']   = true;
			$state['message'] = $result->get_error_message();
			return $state;
		}

		$state['result'] = $result;
		set_transient( self::TRANSIENT_KEY, $result, DAY_IN_SECONDS );

		return $state;
	}

	/**
	 * Check master AI switch.
	 *
	 * @return bool
	 */
	private function is_ai_enabled() {
		return '1' === (string) get_option( 'wps_ai_main_enabled', '0' );
	}

	/**
	 * Prepare anonymized aggregate data for AI.
	 *
	 * @param array $counts Counts.
	 * @return array
	 */
	private function get_ai_prompt_data( $counts ) {
		return array(
			'currency'                    => isset( $counts['currency'] ) ? $counts['currency'] : get_woocommerce_currency(),
			'statuses'                    => isset( $counts['statuses'] ) ? $counts['statuses'] : array(),
			'total_subscriptions'         => isset( $counts['total'] ) ? (int) $counts['total'] : 0,
			'active_mrr'                  => isset( $counts['active_mrr'] ) ? (float) $counts['active_mrr'] : 0,
			'active_aov'                  => isset( $counts['active_aov'] ) ? (float) $counts['active_aov'] : 0,
			'new_this_week'               => isset( $counts['new_this_week'] ) ? (int) $counts['new_this_week'] : 0,
			'new_mrr_this_week'           => isset( $counts['new_mrr_this_week'] ) ? (float) $counts['new_mrr_this_week'] : 0,
			'renewals_this_week'          => isset( $counts['renewals_this_week'] ) ? (int) $counts['renewals_this_week'] : 0,
			'renewal_revenue_this_week'   => isset( $counts['renewal_revenue_this_week'] ) ? (float) $counts['renewal_revenue_this_week'] : 0,
			'failed_payments_this_week'   => isset( $counts['failed_this_week'] ) ? (int) $counts['failed_this_week'] : 0,
			'expiring_in_7_days'          => isset( $counts['expiring_soon'] ) ? (int) $counts['expiring_soon'] : 0,
			'upcoming_billings_30_days'   => isset( $counts['upcoming_billings_30_days'] ) ? (int) $counts['upcoming_billings_30_days'] : 0,
			'upcoming_billing_value_30_days' => isset( $counts['upcoming_billing_value_30_days'] ) ? (float) $counts['upcoming_billing_value_30_days'] : 0,
			'new_current_month'           => isset( $counts['new_current_month'] ) ? (int) $counts['new_current_month'] : 0,
			'new_previous_month'          => isset( $counts['new_previous_month'] ) ? (int) $counts['new_previous_month'] : 0,
			'cancelled_current_month'     => isset( $counts['cancelled_current_month'] ) ? (int) $counts['cancelled_current_month'] : 0,
			'cancelled_previous_month'    => isset( $counts['cancelled_previous_month'] ) ? (int) $counts['cancelled_previous_month'] : 0,
			'new_mrr_current_month'       => isset( $counts['new_mrr_current_month'] ) ? (float) $counts['new_mrr_current_month'] : 0,
			'new_mrr_previous_month'      => isset( $counts['new_mrr_previous_month'] ) ? (float) $counts['new_mrr_previous_month'] : 0,
			'plan_mix'                    => isset( $counts['plan_mix'] ) ? $counts['plan_mix'] : array(),
		);
	}

	/**
	 * Generate AI Insights.
	 *
	 * @param array $counts Subscription counts.
	 * @return array|WP_Error
	 */
	private function generate_ai_summary( $counts ) {
		$prompt_data = $this->get_ai_prompt_data( $counts );
		$messages = apply_filters(
			'wps_ai_before_health_prompt',
			array(
				array(
					'role'    => 'system',
					'content' => 'You are a WooCommerce subscription analytics assistant. Given aggregated subscription KPIs only, generate concise operator insights. Respond ONLY with valid JSON and nothing else. Required schema: summary (string, 1 short sentence), trend ("growing"|"stable"|"declining"), groups (array of exactly 6 objects). Each group must use one of these keys: risks, opportunities, actions, retention, pricing_plans, forecast. Each group must include title, description, and items. description must be under 12 words. items must be exactly 2 short bullet strings, each under 16 words. Do not invent customer names or private data. Tie every point to the supplied metrics.',
				),
				array(
					'role'    => 'user',
					'content' => 'Generate AI Insights for this WooCommerce subscription store. Aggregated data: ' . wp_json_encode( $prompt_data ),
				),
			),
			$counts
		);

		$result = wps_ai_provider()->complete_json( $messages, array( 'max_tokens' => 1000 ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result['generated_at'] = current_time( 'timestamp' );
		$result = $this->normalize_ai_result( $result, $counts );
		return apply_filters( 'wps_ai_health_result', $result, $counts );
	}

	/**
	 * Normalize AI result for rendering.
	 *
	 * @param array $result AI result.
	 * @param array $counts Counts.
	 * @return array
	 */
	private function normalize_ai_result( $result, $counts ) {
		$summary = isset( $result['summary'] ) ? sanitize_textarea_field( $result['summary'] ) : '';
		$trend   = isset( $result['trend'] ) ? sanitize_key( $result['trend'] ) : 'stable';
		if ( ! in_array( $trend, array( 'growing', 'stable', 'declining' ), true ) ) {
			$trend = 'stable';
		}

		$groups       = $this->normalize_insight_groups( isset( $result['groups'] ) && is_array( $result['groups'] ) ? $result['groups'] : array(), $counts );
		$generated_at = isset( $result['generated_at'] ) ? absint( $result['generated_at'] ) : 0;

		return array(
			'summary'         => $summary ? $summary : $this->get_fallback_result( $counts )['summary'],
			'trend'           => $trend,
			'groups'          => $groups,
			'generated_at'    => $generated_at,
			'generated_label' => $this->get_generated_label( $generated_at ),
		);
	}

	/**
	 * Normalize insight groups for rendering.
	 *
	 * @param array $groups AI groups.
	 * @param array $counts Counts.
	 * @return array
	 */
	private function normalize_insight_groups( $groups, $counts ) {
		$fallback = $this->get_fallback_groups( $counts );
		$by_key   = array();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$key = isset( $group['key'] ) ? sanitize_key( $group['key'] ) : '';
			if ( ! isset( $fallback[ $key ] ) ) {
				continue;
			}

			$items = array();
			if ( ! empty( $group['items'] ) && is_array( $group['items'] ) ) {
				foreach ( $group['items'] as $item ) {
					$item = sanitize_textarea_field( $item );
					if ( '' !== $item ) {
						$items[] = $item;
					}
				}
			}

			$items = ! empty( $items ) ? $items : $fallback[ $key ]['items'];
			if ( count( $items ) < 2 ) {
				foreach ( $fallback[ $key ]['items'] as $fallback_item ) {
					if ( count( $items ) >= 2 ) {
						break;
					}
					if ( ! in_array( $fallback_item, $items, true ) ) {
						$items[] = $fallback_item;
					}
				}
			}

			$by_key[ $key ] = array(
				'key'         => $key,
				'title'       => isset( $group['title'] ) && '' !== $group['title'] ? sanitize_text_field( $group['title'] ) : $fallback[ $key ]['title'],
				'description' => isset( $group['description'] ) && '' !== $group['description'] ? sanitize_text_field( $group['description'] ) : $fallback[ $key ]['description'],
				'items'       => array_slice( $items, 0, 2 ),
			);
		}

		$normalized = array();
		foreach ( array_keys( $fallback ) as $key ) {
			$normalized[] = isset( $by_key[ $key ] ) ? $by_key[ $key ] : $fallback[ $key ];
		}

		return $normalized;
	}

	/**
	 * Get fallback result before AI has run.
	 *
	 * @param array $counts Counts.
	 * @return array
	 */
	private function get_fallback_result( $counts ) {
		return array(
			'summary' => sprintf(
				/* translators: 1: active subscriptions, 2: cancelled subscriptions */
				__( 'Your store currently has %1$d active subscriptions and %2$d cancelled subscriptions. Configure AI settings to generate richer AI Insights.', 'subscriptions-for-woocommerce' ),
				(int) $counts['statuses']['active'],
				(int) $counts['statuses']['cancelled']
			),
			'trend'   => 'stable',
			'groups'  => array_values( $this->get_fallback_groups( $counts ) ),
			'generated_at' => 0,
			'generated_label' => $this->get_generated_label( 0 ),
		);
	}

	/**
	 * Get generated timestamp label.
	 *
	 * @param int $generated_at Generated timestamp.
	 * @return string
	 */
	private function get_generated_label( $generated_at ) {
		if ( empty( $generated_at ) ) {
			return __( 'Generated time unavailable', 'subscriptions-for-woocommerce' );
		}

		$now = current_time( 'timestamp' );
		if ( $generated_at >= ( $now - MINUTE_IN_SECONDS ) ) {
			return __( 'Generated just now', 'subscriptions-for-woocommerce' );
		}

		return sprintf(
			/* translators: %s: human-readable time difference */
			__( 'Generated %s ago', 'subscriptions-for-woocommerce' ),
			human_time_diff( $generated_at, $now )
		);
	}

	/**
	 * Get deterministic fallback insight groups.
	 *
	 * @param array $counts Counts.
	 * @return array
	 */
	private function get_fallback_groups( $counts ) {
		$active       = isset( $counts['statuses']['active'] ) ? (int) $counts['statuses']['active'] : 0;
		$on_hold      = isset( $counts['statuses']['on-hold'] ) ? (int) $counts['statuses']['on-hold'] : 0;
		$cancelled    = isset( $counts['statuses']['cancelled'] ) ? (int) $counts['statuses']['cancelled'] : 0;
		$failed       = isset( $counts['failed_this_week'] ) ? (int) $counts['failed_this_week'] : 0;
		$new_week     = isset( $counts['new_this_week'] ) ? (int) $counts['new_this_week'] : 0;
		$renewals     = isset( $counts['renewals_this_week'] ) ? (int) $counts['renewals_this_week'] : 0;
		$expiring     = isset( $counts['expiring_soon'] ) ? (int) $counts['expiring_soon'] : 0;
		$mrr          = isset( $counts['active_mrr'] ) ? wp_strip_all_tags( wc_price( (float) $counts['active_mrr'] ) ) : wp_strip_all_tags( wc_price( 0 ) );
		$billing_30   = isset( $counts['upcoming_billings_30_days'] ) ? (int) $counts['upcoming_billings_30_days'] : 0;
		$billing_value = isset( $counts['upcoming_billing_value_30_days'] ) ? wp_strip_all_tags( wc_price( (float) $counts['upcoming_billing_value_30_days'] ) ) : wp_strip_all_tags( wc_price( 0 ) );
		$top_plan     = ! empty( $counts['plan_mix'][0]['name'] ) ? $counts['plan_mix'][0]['name'] : __( 'your leading plan', 'subscriptions-for-woocommerce' );

		return array(
			'risks'         => array(
				'key'         => 'risks',
				'title'       => __( 'Risks', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Revenue or retention issues to watch.', 'subscriptions-for-woocommerce' ),
				'items'       => array(
					sprintf(
						/* translators: 1: on hold count, 2: failed count */
						__( '%1$d subscriptions are on hold and %2$d failed-payment signals need review.', 'subscriptions-for-woocommerce' ),
						$on_hold,
						$failed
					),
					sprintf(
						/* translators: %d: expiring subscriptions */
						__( '%d subscriptions are expiring in the next 7 days.', 'subscriptions-for-woocommerce' ),
						$expiring
					),
				),
			),
			'opportunities' => array(
				'key'         => 'opportunities',
				'title'       => __( 'Opportunities', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Signals that can expand MRR.', 'subscriptions-for-woocommerce' ),
				'items'       => array(
					sprintf(
						/* translators: 1: active count, 2: MRR */
						__( '%1$d active subscriptions are contributing about %2$s in normalized MRR.', 'subscriptions-for-woocommerce' ),
						$active,
						$mrr
					),
					sprintf(
						/* translators: %d: new subscriptions */
						__( '%d new subscriptions were created this week.', 'subscriptions-for-woocommerce' ),
						$new_week
					),
				),
			),
			'actions'       => array(
				'key'         => 'actions',
				'title'       => __( 'Actions', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Next steps for this week.', 'subscriptions-for-woocommerce' ),
				'items'       => array(
					__( 'Review on-hold and failed subscriptions before the next renewal cycle.', 'subscriptions-for-woocommerce' ),
					__( 'Check upcoming billings and contact customers with payment or expiry risk.', 'subscriptions-for-woocommerce' ),
				),
			),
			'retention'     => array(
				'key'         => 'retention',
				'title'       => __( 'Retention', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Churn and pause prevention.', 'subscriptions-for-woocommerce' ),
				'items'       => array(
					sprintf(
						/* translators: %d: on hold subscriptions */
						__( 'Monitor %d on-hold subscriptions closely to prevent future churn.', 'subscriptions-for-woocommerce' ),
						$on_hold
					),
					sprintf(
						/* translators: %d: expiring subscriptions */
						__( 'Prepare renewal reminders for %d subscriptions expiring within 7 days.', 'subscriptions-for-woocommerce' ),
						$expiring
					),
				),
			),
			'pricing_plans' => array(
				'key'         => 'pricing_plans',
				'title'       => __( 'Pricing & plans', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Plan mix and revenue quality.', 'subscriptions-for-woocommerce' ),
				'items'       => array(
					sprintf(
						/* translators: %s: plan name */
						__( 'Evaluate the price and value of %s against your other active plans.', 'subscriptions-for-woocommerce' ),
						$top_plan
					),
					sprintf(
						/* translators: %s: MRR */
						__( 'Use the current %s MRR baseline before changing plan prices.', 'subscriptions-for-woocommerce' ),
						$mrr
					),
				),
			),
			'forecast'      => array(
				'key'         => 'forecast',
				'title'       => __( 'Forecast', 'subscriptions-for-woocommerce' ),
				'description' => __( 'Upcoming billing and cash-flow signals.', 'subscriptions-for-woocommerce' ),
				'items'       => array(
					sprintf(
						/* translators: 1: billing count, 2: billing value */
						__( '%1$d upcoming billings in 30 days represent about %2$s in scheduled value.', 'subscriptions-for-woocommerce' ),
						$billing_30,
						$billing_value
					),
					sprintf(
						/* translators: %d: renewal count */
						__( '%d renewals completed this week, giving an early signal for recurring cash flow.', 'subscriptions-for-woocommerce' ),
						$renewals
					),
				),
			),
		);
	}

	/**
	 * Get subscription counts for the dashboard.
	 *
	 * @return array
	 */
	private function get_subscription_counts() {
		$statuses = array(
			'active'    => 0,
			'cancelled' => 0,
			'expired'   => 0,
			'on-hold'   => 0,
			'pending'   => 0,
			'failed'    => 0,
		);

		$now              = current_time( 'timestamp' );
		$week_ago         = $now - WEEK_IN_SECONDS;
		$next_week        = $now + WEEK_IN_SECONDS;
		$next_month       = $now + ( 30 * DAY_IN_SECONDS );
		$current_month    = strtotime( gmdate( 'Y-m-01 00:00:00', $now ) );
		$previous_month   = strtotime( '-1 month', $current_month );
		$new_this_week    = 0;
		$renewals_week    = 0;
		$failed_this_week = 0;
		$expiring_soon    = 0;
		$active_mrr       = 0.0;
		$active_value     = 0.0;
		$active_count     = 0;
		$new_mrr_week     = 0.0;
		$new_current_month = 0;
		$new_previous_month = 0;
		$new_mrr_current_month = 0.0;
		$new_mrr_previous_month = 0.0;
		$cancelled_current_month = 0;
		$cancelled_previous_month = 0;
		$renewal_revenue_week = 0.0;
		$upcoming_billings_30 = 0;
		$upcoming_billing_value_30 = 0.0;
		$plan_mix         = array();
		$currency         = get_woocommerce_currency();

		foreach ( $this->get_subscription_ids() as $subscription_id ) {
			$status = sanitize_key( (string) wps_sfw_get_meta_data( $subscription_id, 'wps_subscription_status', true ) );
			if ( isset( $statuses[ $status ] ) ) {
				$statuses[ $status ]++;
			}

			$created = $this->get_subscription_created_timestamp( $subscription_id );
			$modified = $this->get_subscription_modified_timestamp( $subscription_id );
			$total   = $this->get_subscription_total( $subscription_id );
			$monthly_value = $this->get_subscription_monthly_value( $subscription_id, $total );
			$currency = $this->get_subscription_currency( $subscription_id, $currency );
			if ( $created >= $week_ago ) {
				$new_this_week++;
				$new_mrr_week += $monthly_value;
			}

			if ( $created >= $current_month ) {
				$new_current_month++;
				$new_mrr_current_month += $monthly_value;
			} elseif ( $created >= $previous_month && $created < $current_month ) {
				$new_previous_month++;
				$new_mrr_previous_month += $monthly_value;
			}

			$renewals = wps_sfw_get_meta_data( $subscription_id, 'wps_wsp_renewal_order_data', true );
			if ( is_array( $renewals ) ) {
				foreach ( $renewals as $renewal_id ) {
					$renewal_created = $this->get_order_created_timestamp( absint( $renewal_id ) );
					if ( $renewal_created >= $week_ago ) {
						$renewals_week++;
						$renewal_revenue_week += $this->get_order_total( absint( $renewal_id ) );
					}
				}
			}

			$end = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_susbcription_end', true ) );
			if ( $end > $now && $end <= $next_week ) {
				$expiring_soon++;
			}

			$next_payment = absint( wps_sfw_get_meta_data( $subscription_id, 'wps_next_payment_date', true ) );
			if ( 'active' === $status && $next_payment > $now && $next_payment <= $next_month ) {
				$upcoming_billings_30++;
				$upcoming_billing_value_30 += $total;
			}

			if ( 'active' === $status ) {
				$active_count++;
				$active_value += $total;
				$active_mrr += $monthly_value;
				$product_name = $this->get_subscription_product_name( $subscription_id );
				if ( ! isset( $plan_mix[ $product_name ] ) ) {
					$plan_mix[ $product_name ] = array(
						'name'         => $product_name,
						'active_count' => 0,
						'mrr'          => 0.0,
					);
				}
				$plan_mix[ $product_name ]['active_count']++;
				$plan_mix[ $product_name ]['mrr'] += $monthly_value;
			}

			if ( 'cancelled' === $status ) {
				if ( $modified >= $current_month ) {
					$cancelled_current_month++;
				} elseif ( $modified >= $previous_month && $modified < $current_month ) {
					$cancelled_previous_month++;
				}
			}

			if ( 'failed' === $status && $modified >= $week_ago ) {
				$failed_this_week++;
			}
		}

		$plan_mix = array_values( $plan_mix );
		usort(
			$plan_mix,
			function( $first, $second ) {
				return $second['active_count'] <=> $first['active_count'];
			}
		);

		return array(
			'statuses'           => $statuses,
			'total'              => array_sum( $statuses ),
			'new_this_week'      => $new_this_week,
			'new_mrr_this_week'  => round( $new_mrr_week, 2 ),
			'renewals_this_week' => $renewals_week,
			'renewal_revenue_this_week' => round( $renewal_revenue_week, 2 ),
			'failed_this_week'   => $failed_this_week,
			'expiring_soon'      => $expiring_soon,
			'active_mrr'         => round( $active_mrr, 2 ),
			'active_aov'         => $active_count ? round( $active_value / $active_count, 2 ) : 0,
			'currency'           => $currency,
			'upcoming_billings_30_days' => $upcoming_billings_30,
			'upcoming_billing_value_30_days' => round( $upcoming_billing_value_30, 2 ),
			'new_current_month'  => $new_current_month,
			'new_previous_month' => $new_previous_month,
			'new_mrr_current_month' => round( $new_mrr_current_month, 2 ),
			'new_mrr_previous_month' => round( $new_mrr_previous_month, 2 ),
			'cancelled_current_month' => $cancelled_current_month,
			'cancelled_previous_month' => $cancelled_previous_month,
			'plan_mix'           => array_slice( $plan_mix, 0, 3 ),
		);
	}

	/**
	 * Get subscription IDs.
	 *
	 * @return array
	 */
	private function get_subscription_ids() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return array_map(
				'absint',
				(array) wc_get_orders(
					array(
						'type'   => 'wps_subscriptions',
						'return' => 'ids',
						'limit'  => -1,
					)
				)
			);
		}

		return array_map(
			'absint',
			(array) get_posts(
				array(
					'fields'         => 'ids',
					'numberposts'    => -1,
					'post_type'      => 'wps_subscriptions',
					'post_status'    => 'any',
					'suppress_filters' => false,
				)
			)
		);
	}

	/**
	 * Get subscription created timestamp.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return int
	 */
	private function get_subscription_created_timestamp( $subscription_id ) {
		if ( class_exists( 'WPS_Subscription' ) ) {
			$subscription = new WPS_Subscription( $subscription_id );
			if ( method_exists( $subscription, 'get_date_created' ) ) {
				$date = $subscription->get_date_created();
				if ( $date ) {
					return (int) $date->getTimestamp();
				}
			}
		}

		$post = get_post( $subscription_id );
		return $post ? (int) get_post_time( 'U', true, $post ) : 0;
	}

	/**
	 * Get subscription modified timestamp.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return int
	 */
	private function get_subscription_modified_timestamp( $subscription_id ) {
		if ( class_exists( 'WPS_Subscription' ) ) {
			$subscription = new WPS_Subscription( $subscription_id );
			if ( method_exists( $subscription, 'get_date_modified' ) ) {
				$date = $subscription->get_date_modified();
				if ( $date ) {
					return (int) $date->getTimestamp();
				}
			}
		}

		$post = get_post( $subscription_id );
		return $post ? (int) get_post_modified_time( 'U', true, $post ) : 0;
	}

	/**
	 * Get subscription total.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return float
	 */
	private function get_subscription_total( $subscription_id ) {
		$total = 0;
		if ( class_exists( 'WPS_Subscription' ) ) {
			$subscription = new WPS_Subscription( $subscription_id );
			if ( method_exists( $subscription, 'get_total' ) ) {
				$total = $subscription->get_total();
			}
		}

		if ( ! $total ) {
			$order = wc_get_order( $subscription_id );
			if ( $order ) {
				$total = $order->get_total();
			}
		}

		if ( ! $total ) {
			$total = wps_sfw_get_meta_data( $subscription_id, 'wps_recurring_total', true );
		}

		if ( ! $total ) {
			$total = wps_sfw_get_meta_data( $subscription_id, 'line_total', true );
		}

		return (float) $total;
	}

	/**
	 * Get subscription currency.
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param string $fallback Fallback currency.
	 * @return string
	 */
	private function get_subscription_currency( $subscription_id, $fallback ) {
		if ( class_exists( 'WPS_Subscription' ) ) {
			$subscription = new WPS_Subscription( $subscription_id );
			if ( method_exists( $subscription, 'get_currency' ) ) {
				$currency = $subscription->get_currency();
				if ( $currency ) {
					return $currency;
				}
			}
		}

		$order = wc_get_order( $subscription_id );
		if ( $order ) {
			$currency = $order->get_currency();
			if ( $currency ) {
				return $currency;
			}
		}

		return $fallback;
	}

	/**
	 * Get subscription product name.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return string
	 */
	private function get_subscription_product_name( $subscription_id ) {
		$product_name = wps_sfw_get_meta_data( $subscription_id, 'product_name', true );
		if ( is_array( $product_name ) ) {
			$product_name = implode( ', ', array_map( 'sanitize_text_field', $product_name ) );
		}

		if ( $product_name ) {
			return sanitize_text_field( $product_name );
		}

		$product_id = absint( wps_sfw_get_meta_data( $subscription_id, 'product_id', true ) );
		$product    = $product_id ? wc_get_product( $product_id ) : false;
		if ( $product ) {
			return sanitize_text_field( $product->get_name() );
		}

		return __( 'Unknown plan', 'subscriptions-for-woocommerce' );
	}

	/**
	 * Normalize subscription value to monthly recurring value.
	 *
	 * @param int   $subscription_id Subscription ID.
	 * @param float $total Subscription total.
	 * @return float
	 */
	private function get_subscription_monthly_value( $subscription_id, $total ) {
		$number   = max( 1, absint( wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_number', true ) ) );
		$interval = sanitize_key( (string) wps_sfw_get_meta_data( $subscription_id, 'wps_sfw_subscription_interval', true ) );

		switch ( $interval ) {
			case 'day':
				$months = $number / 30.4375;
				break;
			case 'week':
				$months = $number / 4.345;
				break;
			case 'year':
				$months = $number * 12;
				break;
			case 'month':
			default:
				$months = $number;
				break;
		}

		return $months > 0 ? (float) $total / $months : (float) $total;
	}

	/**
	 * Get order created timestamp.
	 *
	 * @param int $order_id Order ID.
	 * @return int
	 */
	private function get_order_created_timestamp( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && method_exists( $order, 'get_date_created' ) ) {
			$date = $order->get_date_created();
			if ( $date ) {
				return (int) $date->getTimestamp();
			}
		}

		$post = get_post( $order_id );
		return $post ? (int) get_post_time( 'U', true, $post ) : 0;
	}

	/**
	 * Get order total.
	 *
	 * @param int $order_id Order ID.
	 * @return float
	 */
	private function get_order_total( $order_id ) {
		$order = wc_get_order( $order_id );
		return $order ? (float) $order->get_total() : 0.0;
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

		return defined( 'SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION' ) ? (string) SUBSCRIPTIONS_FOR_WOOCOMMERCE_VERSION : '1.0.0';
	}
}
