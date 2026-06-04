<?php
/**
 * Membership Email — On Hold (Day 10 stub)
 *
 * Sent when a membership is placed on hold (subscription payment failed or
 * admin action). Triggered by `wps_membership_status_changed` (new_status = on-hold).
 * Full implementation lands on Day 10 (June 17).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Membership_Onhold_Email' ) ) {

	/**
	 * Membership On Hold email notification.
	 *
	 * @since 2.0.0
	 */
	class WPS_Membership_Onhold_Email extends WC_Email {

		/**
		 * Set up email properties.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			$this->id             = 'wps_membership_onhold';
			$this->title          = __( 'Membership On Hold', 'subscriptions-for-woocommerce' );
			$this->description    = __( 'Sent when a membership is placed on hold.', 'subscriptions-for-woocommerce' );
			$this->template_html  = 'wps-membership-onhold-email.php';
			$this->template_plain = 'plain/wps-membership-onhold-email.php';
			$this->template_base  = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'emails/templates/';

			parent::__construct();

			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Default email subject.
		 *
		 * @since  2.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your membership is on hold — {site_title}', 'subscriptions-for-woocommerce' );
		}

		/**
		 * Default email heading.
		 *
		 * @since  2.0.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Membership On Hold', 'subscriptions-for-woocommerce' );
		}

		/**
		 * Trigger the email.
		 *
		 * Full implementation: Day 10.
		 *
		 * @since 2.0.0
		 * @param int    $user_id   WordPress user ID.
		 * @param string $plan_slug Plan slug.
		 * @param array  $row       Membership row data.
		 */
		public function trigger( $user_id, $plan_slug, $row ) {
			// Day 10: resolve merge tags, send to member.
		}

		/**
		 * HTML email content.
		 *
		 * @since  2.0.0
		 * @return string
		 */
		public function get_content_html() {
			return '';
		}

		/**
		 * Plain-text email content.
		 *
		 * @since  2.0.0
		 * @return string
		 */
		public function get_content_plain() {
			return '';
		}

		/**
		 * Initialise settings form fields.
		 *
		 * @since 2.0.0
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'subscriptions-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'subscriptions-for-woocommerce' ),
					'default' => 'yes',
				),
				'subject' => array(
					'title'       => __( 'Subject', 'subscriptions-for-woocommerce' ),
					'type'        => 'text',
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'heading' => array(
					'title'       => __( 'Email Heading', 'subscriptions-for-woocommerce' ),
					'type'        => 'text',
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'email_type' => array(
					'title'   => __( 'Email type', 'subscriptions-for-woocommerce' ),
					'type'    => 'select',
					'default' => 'html',
					'class'   => 'email_type wc-enhanced-select',
					'options' => $this->get_email_type_options(),
				),
			);
		}
	}
}

return new WPS_Membership_Onhold_Email();
