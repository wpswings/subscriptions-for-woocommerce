<?php
/**
 * Membership Email — Cancelled
 *
 * Sent when a membership is cancelled (subscription cancel, order refund, or
 * admin action). Triggered by `wps_membership_status_changed` (new_status = cancelled).
 *
 * @since      2.0.0
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_Membership_Cancelled_Email' ) ) {

	/**
	 * Membership Cancelled email notification.
	 *
	 * @since 2.0.0
	 */
	class WPS_Membership_Cancelled_Email extends WC_Email {

		/**
		 * Set up email properties and wire action hooks.
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			$this->id             = 'wps_membership_cancelled';
			$this->title          = __( 'Membership Cancelled', 'subscriptions-for-woocommerce' );
			$this->description    = __(
				'Sent when a membership is cancelled.',
				'subscriptions-for-woocommerce'
			);
			$this->template_html  = 'wps-membership-cancelled-email.php';
			$this->template_plain = 'plain/wps-membership-cancelled-email.php';
			$this->template_base  = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'emails/templates/';

			parent::__construct();

			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );

			// Priority 20 ensures cache is busted before we read it.
			add_action( 'wps_membership_status_changed', array( $this, 'maybe_trigger' ), 20, 4 );
		}

		/**
		 * Fire only when the new status is "cancelled".
		 *
		 * @since 2.0.0
		 * @param int    $user_id    WordPress user ID.
		 * @param string $plan_slug  Plan slug.
		 * @param string $new_status New membership status.
		 * @param string $old_status Previous membership status.
		 */
		public function maybe_trigger( $user_id, $plan_slug, $new_status, $old_status ) {
			if ( 'cancelled' !== $new_status ) {
				return;
			}
			$memberships = wps_read_user_memberships_meta( $user_id );
			$row         = isset( $memberships[ $plan_slug ] ) ? $memberships[ $plan_slug ] : array( 'status' => $new_status );
			$this->trigger( $user_id, $plan_slug, $row );
		}

		/**
		 * Trigger the email.
		 *
		 * @since 2.0.0
		 * @param int    $user_id   WordPress user ID.
		 * @param string $plan_slug Plan slug.
		 * @param array  $row       Membership row data.
		 */
		public function trigger( $user_id, $plan_slug, $row ) {
			$this->setup_locale();

			$user = get_userdata( absint( $user_id ) );
			if ( ! $user ) {
				$this->restore_locale();
				return;
			}

			$plan = function_exists( 'wps_get_plan_by_slug' ) ? wps_get_plan_by_slug( $plan_slug ) : array();

			$this->object = array(
				'user_id'   => $user_id,
				'plan_slug' => $plan_slug,
				'row'       => $row,
				'plan'      => $plan,
				'user'      => $user,
			);

			$this->recipient = $user->user_email;

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				$this->restore_locale();
				return;
			}

			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);

			$this->restore_locale();
		}

		/**
		 * Default email subject.
		 *
		 * @since  2.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your membership has been cancelled — {site_title}', 'subscriptions-for-woocommerce' );
		}

		/**
		 * Default email heading.
		 *
		 * @since  2.0.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Membership Cancelled', 'subscriptions-for-woocommerce' );
		}

		/**
		 * HTML email content.
		 *
		 * @since  2.0.0
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'membership_data' => $this->get_object_or_preview(),
					'email_heading'   => $this->get_heading(),
					'sent_to_admin'   => false,
					'plain_text'      => false,
					'email'           => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Plain-text email content.
		 *
		 * @since  2.0.0
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'membership_data' => $this->get_object_or_preview(),
					'email_heading'   => $this->get_heading(),
					'sent_to_admin'   => false,
					'plain_text'      => true,
					'email'           => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Returns $this->object, or a safe preview stub when trigger() hasn't been called.
		 *
		 * @since  2.0.0
		 * @return array
		 */
		private function get_object_or_preview() {
			if ( is_array( $this->object ) ) {
				return $this->object;
			}
			return array(
				'user_id'   => 0,
				'plan_slug' => 'example-plan',
				'row'       => array(
					'status'     => 'cancelled',
					'start_date' => time(),
					'end_date'   => 0,
				),
				'plan'      => array( 'name' => __( 'Example Plan', 'subscriptions-for-woocommerce' ) ),
				'user'      => (object) array(
					'display_name' => __( 'Member Name', 'subscriptions-for-woocommerce' ),
				),
			);
		}

		/**
		 * Initialise settings form fields.
		 *
		 * @since 2.0.0
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'    => array(
					'title'   => __( 'Enable/Disable', 'subscriptions-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'subscriptions-for-woocommerce' ),
					'default' => 'yes',
				),
				'subject'    => array(
					'title'       => __( 'Subject', 'subscriptions-for-woocommerce' ),
					'type'        => 'text',
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'heading'    => array(
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

return new WPS_Membership_Cancelled_Email();
