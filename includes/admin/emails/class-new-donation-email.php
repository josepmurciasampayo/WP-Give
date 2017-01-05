<?php
/**
 * New Donation Email
 *
 * This class handles all email notification settings.
 *
 * @package     Give
 * @subpackage  Classes/Emails
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.9
 */

// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Give_New_Donation_Email' ) ) :

	/**
	 * Give_New_Donation_Email
	 *
	 * @abstract
	 * @since       1.9
	 */
	class Give_New_Donation_Email extends Give_Email_Notification {

		/**
		 * Create a class instance.
		 *
		 * @param   mixed[] $objects
		 *
		 * @access  public
		 * @since   1.9
		 */
		public function __construct( $objects = array() ) {
			$this->id          = 'new-donation';
			$this->label       = __( 'New Donation', 'give' );
			$this->description = __( 'Donation Notification will be sent to recipient(s) when new donation received except offline donation.', 'give' );

			$this->has_recipient_field = true;
			$this->notification_status = 'enabled';

			parent::__construct();

			add_action( 'give_complete_donation', array( $this, 'setup_email_notification' ) );
		}

		/**
		 * Get default email subject.
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		public function get_default_email_subject() {
			return esc_attr__( 'New Donation - #{payment_id}', 'give' );
		}


		/**
		 * Get default email message.
		 *
		 * @since  1.9
		 * @access public
		 *
		 * @return string
		 */
		public function get_default_email_message() {
			$message = esc_html__( 'Hello', 'give' ) . "\n\n";
			$message .= esc_html__( 'A donation has been made.', 'give' ) . "\n\n";
			$message .= esc_html__( 'Donation:', 'give' ) . "\n\n";
			$message .= esc_html__( 'Donor:', 'give' ) . ' {fullname}' . "\n";
			$message .= esc_html__( 'Amount:', 'give' ) . ' {payment_total}' . "\n";
			$message .= esc_html__( 'Payment Method:', 'give' ) . ' {payment_method}' . "\n\n";
			$message .= esc_html__( 'Thank you', 'give' );


			/**
			 * Filter the new donation email message
			 *
			 * @since 1.9
			 *
			 * @param string $message
			 */
			return apply_filters( 'give_default_new_donation_email', $message );
		}

		/**
		 * Setup email notification.
		 *
		 * @since  1.9
		 * @access public
		 *
		 * @param int $payment_id
		 */
		public function setup_email_notification( $payment_id ) {
			// Send email.
			$this->send_email_notification( array( 'payment_id' => $payment_id ) );
		}
	}

endif; // End class_exists check

return new Give_New_Donation_Email();