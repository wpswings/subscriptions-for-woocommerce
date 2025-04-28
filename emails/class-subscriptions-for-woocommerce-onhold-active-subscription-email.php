<?php
/**
 * On-Hold Active Email template
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Subscriptions_For_Woocommerce
 * @subpackage Subscriptions_For_Woocommerce/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Subscriptions_For_Woocommerce_Onhold_Active_Subscription_Email' ) ) {

	/**
	 * On-Hold ActiveEmail template Class
	 *
	 * @link       https://wpswings.com/
	 * @since      1.0.0
	 *
	 * @package    Subscriptions_For_Woocommerce
	 * @subpackage Subscriptions_For_Woocommerce/email
	 */
	class Subscriptions_For_Woocommerce_Onhold_Active_Subscription_Email extends WC_Email {
		/**
		 * Create class for email notification.
		 *
		 * @access public
		 */
		public function __construct() {

			$this->id          = 'wps_sfw_onhold_active_subscription';
			$this->title       = __( 'On-Hold Active Subscription Email Notification', 'subscriptions-for-woocommerce' );

			$this->description = __( 'This Email Notification Send if any subscription is Active From On-Hold', 'subscriptions-for-woocommerce' );

			$this->template_html  = 'wps-sfw-onhold-active-subscription-email-template.php';
			$this->template_plain = 'plain/wps-sfw-onhold-active-subscription-email-template.php';
			$this->template_base  = SUBSCRIPTIONS_FOR_WOOCOMMERCE_DIR_PATH . 'emails/templates/';
            $this->customer_email = true;
			parent::__construct();
			
		}

		/**
		 * Get email subject.
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'On-Hold Active Susbcription Email {site_title}', 'subscriptions-for-woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Subscription On-Hold Active', 'subscriptions-for-woocommerce' );
		}

		/**
		 * This function is used to trigger for email.
		 *
		 * @since  1.0.0
		 * @param int $wps_subscription wps_subscription.
		 * @access public
		 * @return void
		 */
		public function trigger( $wps_subscription ) {

			$user_email = '';

			if ( $wps_subscription ) {

				$this->object = $wps_subscription;
				$wps_parent_order_id = wps_sfw_get_meta_data( $wps_subscription, 'wps_parent_order', true );
				$wps_parent_order = wc_get_order( $wps_parent_order_id );
				if ( ! empty( $wps_parent_order ) ) {
					$user_email = $wps_parent_order->get_billing_email();
                    $this->recipient = $user_email;
				}
			}

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		/**
		 * Get_content_html function.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'wps_subscription'       => $this->object,
					'email_heading'      => $this->get_heading(),
					'sent_to_admin'      => true,
					'plain_text'         => false,
					'email' => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Get_content_plain function.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'wps_subscription'       => $this->object,
					'email_heading'      => $this->get_heading(),
					'sent_to_admin'      => true,
					'plain_text'         => true,
					'email' => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Initialise Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'    => array(
					'title'   => __( 'Enable/Disable', 'subscriptions-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'subscriptions-for-woocommerce' ),
					'default' => 'no',
				),
				'subject'    => array(
					'title'       => __( 'Subject', 'subscriptions-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Enter the email subject', 'subscriptions-for-woocommerce' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'heading'    => array(
					'title'       => __( 'Email Heading', 'subscriptions-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Email Heading', 'subscriptions-for-woocommerce' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'email_type' => array(
					'title'       => __( 'Email type', 'subscriptions-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'subscriptions-for-woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}

}

return new Subscriptions_For_Woocommerce_Onhold_Active_Subscription_Email();
