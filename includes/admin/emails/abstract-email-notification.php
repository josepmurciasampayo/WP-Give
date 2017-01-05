<?php
/**
 * Email Notification
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

if ( ! class_exists( 'Give_Email_Notification' ) ) :

	/**
	 * Give_Email_Notification
	 *
	 * @abstract
	 * @since       1.9
	 */
	abstract class Give_Email_Notification {

		/**
		 * @var     string $id The email's unique identifier.
		 */
		protected $id = '';

		/**
		 * @var     string $label Name of the email.
		 * @access  protected
		 * @since   1.9
		 */
		protected $label = '';

		/**
		 * @var     string $label Name of the email.
		 * @access  protected
		 * @since   1.9
		 */
		protected $description = '';

		/**
		 * @var     Give_Emails $email Mailer.
		 * @access  protected
		 * @since   1.9
		 */
		protected $email;

		/**
		 * @var     bool $has_preview Flag to check if email notification has preview setting field.
		 * @access  protected
		 * @since   1.9
		 */
		protected $has_preview = true;

		/**
		 * @var     bool $has_preview Flag to check if email notification has preview header.
		 * @access  protected
		 * @since   1.9
		 */
		protected $has_preview_header = true;

		/**
		 * @var     bool $preview_email_tags_values Default value to replace email template tags in preview email.
		 * @access  protected
		 * @since   1.9
		 */
		protected $preview_email_tags_values = true;

		/**
		 * @var     bool $has_recipient_field Flag to check if email notification has recipient setting field.
		 * @access  protected
		 * @since   1.9
		 */
		protected $has_recipient_field = false;

		/**
		 * @var     string $notification_status Flag to check if email notification enabled or not.
		 * @access  protected
		 * @since   1.9
		 */
		protected $notification_status = 'disabled';

		/**
		 * @var     string|array $email_tag_context List of template tags which we can add to email notification.
		 * @access  protected
		 * @since   1.9
		 */
		protected $email_tag_context = 'all';

		/**
		 * @var     string $recipient_email Donor email.
		 * @access  protected
		 * @since   1.9
		 */
		protected $recipient_email = '';

		/**
		 * @var     string $recipient_group_name Categories single or group of recipient.
		 * @access  protected
		 * @since   1.9
		 */
		protected $recipient_group_name = '';

		/**
		 * Create a class instance.
		 *
		 * @param   mixed[] $objects
		 *
		 * @access  public
		 * @since   1.9
		 */
		public function __construct( $objects = array() ) {
			// Setup email class.
			$this->email = new Give_Emails();

			// Set email preview header status.
			$this->has_preview_header = $this->has_preview && $this->has_preview_header ? true : false;

			// setup filters.
			$this->setup_filters();
		}


		/**
		 * Set key value.
		 *
		 * @since  1.9
		 * @access public
		 *
		 * @param $key
		 * @param $value
		 */
		public function __set( $key, $value ) {
			$this->$key = $value;
		}


		/**
		 * Setup filters.
		 *
		 * @since  1.9
		 * @access public
		 */
		private function setup_filters() {
			// Apply filter only for current email notification section.
			if ( give_get_current_setting_section() === $this->id ) {
				// Initialize email context for email notification.
				$this->email_tag_context = apply_filters(
					"give_{$this->id}_email_tag_context",
					$this->email_tag_context,
					$this
				);
			}

			// Setup setting fields.
			add_filter( 'give_get_settings_emails', array( $this, 'add_setting_fields' ), 10, 2 );
		}

		/**
		 * Register email settings.
		 *
		 * @since  1.9
		 * @access public
		 *
		 * @param   array $settings
		 *
		 * @return  array
		 */
		public function add_setting_fields( $settings ) {
			if ( $this->id === give_get_current_setting_section() ) {
				$settings = Give_Email_Setting_Field::get_setting_fields( $this );
			}

			return $settings;
		}


		/**
		 * Get extra setting field.
		 *
		 * @since  1.9
		 * @access public
		 * @return array
		 */
		public function get_extra_setting_fields() {
			return array();
		}

		/**
		 * Get id.
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get label.
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		public function get_label() {
			return $this->label;
		}

		/**
		 * Get description.
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		public function get_description() {
			return $this->description;
		}

		/**
		 * Get recipient(s).
		 *
		 * Note: in case of admin notification this fx will return array of emails otherwise empty string or email of donor.
		 *
		 * @since  1.9
		 * @access public
		 * @return string|array
		 */
		public function get_recipient() {
			$recipient = give_check_variable( $this->recipient_email, 'empty', $this->email->get_from_address() );

			if ( ! $recipient && $this->has_recipient_field ) {
				$recipient = give_get_option( "{$this->id}_recipient" );
			}

			/**
			 * Filter the recipients
			 *
			 * @since 1.9
			 */
			return apply_filters( 'give_get_recipients', $recipient, $this );
		}

		/**
		 * Get recipient(s) group name.
		 **
		 * @since  1.9
		 * @access public
		 * @return string|array
		 */
		public function get_recipient_group_name() {
			return $this->recipient_group_name;
		}

		/**
		 * Get notification status.
		 *
		 * @since  1.9
		 * @access public
		 * @return bool
		 */
		public function get_notification_status() {

			/**
			 * Filter the notification status.
			 *
			 * @since 1.8
			 */
			return apply_filters( 'give_get_notification_status', give_get_option( "{$this->id}_notification", $this->notification_status ), $this );
		}

		/**
		 * Get email subject.
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		function get_email_subject() {
			return wp_strip_all_tags( give_get_option( "{$this->id}_email_subject", $this->get_default_email_subject() ) );
		}

		/**
		 * Get email message.
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		public function get_email_message() {
			return give_get_option( "{$this->id}_email_message", $this->get_default_email_message() );
		}


		/**
		 * Get email message field description
		 *
		 * @since 1.9
		 * @acess public
		 * @return string
		 */
		public function get_email_message_field_description() {
			$desc = esc_html__( 'Enter the email message.', 'give' );

			if ( $email_tag_list = $this->get_emails_tags_list_html() ) {
				$desc = sprintf(
					esc_html__( 'Enter the email that is sent to users after completing a successful donation. HTML is accepted. Available template tags: %s', 'give' ),
					$email_tag_list
				);

			}

			return $desc;
		}

		/**
		 * Get a formatted HTML list of all available email tags
		 *
		 * @since 1.0
		 *
		 * @return string
		 */
		function get_emails_tags_list_html() {

			// Get all email tags.
			$email_tags = $this->get_allowed_email_tags();

			ob_start();
			if ( count( $email_tags ) > 0 ) : ?>
				<div class="give-email-tags-wrap">
					<?php foreach ( $email_tags as $email_tag ) : ?>
						<span class="give_<?php echo $email_tag['tag']; ?>_tag">
					<code>{<?php echo $email_tag['tag']; ?>}</code> - <?php echo $email_tag['description']; ?>
				</span>
					<?php endforeach; ?>
				</div>
			<?php endif;

			// Return the list.
			return ob_get_clean();
		}


		/**
		 * Get allowed email tags for current email notification.
		 *
		 * @since  1.9
		 * @access private
		 * @return array
		 */
		private function get_allowed_email_tags() {
			// Get all email tags.
			$email_tags = Give()->email_tags->get_tags();

			// Skip if all email template tags context setup exit.
			if ( $this->email_tag_context && 'all' !== $this->email_tag_context ) {
				if ( is_array( $this->email_tag_context ) ) {
					foreach ( $email_tags as $index => $email_tag ) {
						if ( in_array( $email_tag['context'], $this->email_tag_context ) ) {
							continue;
						}

						unset( $email_tags[ $index ] );
					}

				} else {
					foreach ( $email_tags as $index => $email_tag ) {
						if ( $this->email_tag_context === $email_tag['context'] ) {
							continue;
						}

						unset( $email_tags[ $index ] );
					}
				}
			}

			return $email_tags;
		}

		/**
		 * Get default email subject.
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		function get_default_email_subject() {
			return '';
		}

		/**
		 * Get default email message.
		 *
		 * @since  1.9
		 * @access public
		 *
		 * @return string
		 */
		function get_default_email_message() {
			return '';
		}


		/**
		 * Get preview email recipients.
		 *
		 * @since  1.9
		 * @access public
		 * @return array|string
		 */
		public function get_preview_email_recipient() {
			$recipients = $this->get_recipient();

			/**
			 * Filter the preview email recipients.
			 *
			 * @since 1.9
			 *
			 * @param string|array            $recipients List of recipients.
			 * @param Give_Email_Notification $this
			 */
			$recipients = apply_filters( 'give_get_preview_email_recipient', $recipients, $this );

			return $recipients;
		}

		/**
		 * Get the recipient attachments.
		 *
		 * @since  1.9
		 * @access public
		 * @return array
		 */
		public function get_email_attachments() {
			return apply_filters( "give_get_email_attachments", array(), $this );
		}


		/**
		 * Get email content type
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		public function get_email_type() {
			return $this->email->get_content_type();
		}

		/**
		 * Check email active or not.
		 *
		 * @since  1.9
		 * @access public
		 * @return string
		 */
		public function is_email_notification_active() {
			return give_is_setting_enabled( $this->get_notification_status() );
		}

		/**
		 * Check email preview header active or not.
		 *
		 * @since  1.9
		 * @access public
		 * @return bool
		 */
		public function is_email_preview() {
			return $this->has_preview;
		}

		/**
		 * Check email preview header active or not.
		 *
		 * @since  1.9
		 * @access public
		 * @return bool
		 */
		public function is_email_preview_has_header() {
			return $this->has_preview_header;
		}

		/**
		 * Check if notification has recipient field or not.
		 *
		 * @since  1.9
		 * @access public
		 * @return bool
		 */
		public function has_recipient_field() {
			return $this->has_recipient_field;
		}

		/**
		 * Check if notification has preview field or not.
		 *
		 * @since  1.9
		 * @access public
		 * @return bool
		 */
		public function has_preview() {
			return $this->has_preview;
		}

		/**
		 * Send preview email.
		 *
		 * @since  1.9
		 * @access public
		 */
		public function send_preview_email() {
			$attachments = $this->get_email_attachments();
			$message     = $this->preview_email_template_tags( $this->get_email_message() );
			$subject     = $this->preview_email_template_tags( $this->get_email_subject() );

			if ( 'text/html' === $this->email->get_content_type() ) {
				$message = wpautop( $message );
			}

			$this->email->send( $this->get_preview_email_recipient(), $subject, $message, $attachments );
		}

		/**
		 * Send email notification
		 *
		 * @since  1.9
		 * @access public
		 *
		 * @param array $email_tag_args Arguments which helps to decode email template tags.
		 */
		public function send_email_notification( $email_tag_args = array() ) {
			/**
			 * Fire action after before email send.
			 *
			 * @since 1.9
			 */
			do_action( "give_{$this->id}_email_send_before", $this );

			// Do not send email if notification is disable.
			if ( ! give_is_setting_enabled( $this->get_notification_status() ) ) {
				return;
			}

			$attachments = $this->get_email_attachments();
			$message     = give_do_email_tags( $this->get_email_message(), $email_tag_args );
			$subject     = give_do_email_tags( $this->get_email_subject(), $email_tag_args );

			if ( 'text/html' === $this->email->get_content_type() ) {
				$message = wpautop( $message );
			}

			// Send email.
			$email_status = $this->email->send( $this->get_recipient(), $subject, $message, $attachments );

			/**
			 * Fire action after after email send.
			 *
			 * @since 1.9
			 */
			do_action( "give_{$this->id}_email_send_after", $email_status, $this );
		}


		/**
		 * Decode preview email template tags.
		 *
		 * @since 1.9
		 *
		 * @param string $message
		 *
		 * @return string
		 */
		public function preview_email_template_tags( $message ) {
			$user       = wp_get_current_user();
			$receipt_id = strtolower( md5( uniqid() ) );

			$receipt_link_url = esc_url( add_query_arg( array(
				'payment_key' => $receipt_id,
				'give_action' => 'view_receipt',
			), home_url() ) );

			$receipt_link = sprintf(
				'<a href="%1$s">%2$s</a>',
				$receipt_link_url,
				esc_html__( 'View the receipt in your browser &raquo;', 'give' )
			);


			$this->preview_email_tags_values = wp_parse_args(
				$this->preview_email_tags_values,
				array(
					'payment_total'    => give_currency_filter( give_format_amount( 10.50 ) ),
					'payment_method'   => 'Paypal',
					'receipt_id'       => $receipt_id,
					'payment_id'       => rand( 2000, 2050 ),
					'receipt_link_url' => $receipt_link_url,
					'receipt_link'     => $receipt_link,
					'user'             => $user,
					'date'             => date( give_date_format(), current_time( 'timestamp' ) ),
					'donation'         => esc_html__( 'Sample Donation Form Title', 'give' ),
					'form_title'       => esc_html__( 'Sample Donation Form Title - Sample Donation Level', 'give' ),
					'sitename'         => get_bloginfo( 'name' ),
					'pdf_receipt'      => '<a href="#">Download Receipt</a>',
					'billing_address'  => '',
				)
			);


			$message = str_replace( '{name}', $this->preview_email_tags_values['user']->display_name, $message );
			$message = str_replace( '{fullname}', $this->preview_email_tags_values['user']->display_name, $message );
			$message = str_replace( '{username}', $this->preview_email_tags_values['user']->user_login, $message );
			$message = str_replace( '{user_email}', $this->preview_email_tags_values['user']->user_email, $message );
			$message = str_replace( '{date}', $this->preview_email_tags_values['date'], $message );
			$message = str_replace( '{amount}', $this->preview_email_tags_values['payment_total'], $message );
			$message = str_replace( '{price}', $this->preview_email_tags_values['payment_total'], $message );
			$message = str_replace( '{payment_total}', $this->preview_email_tags_values['payment_total'], $message );
			$message = str_replace( '{donation}', $this->preview_email_tags_values['donation'], $message );
			$message = str_replace( '{form_title}', $this->preview_email_tags_values['form_title'], $message );
			$message = str_replace( '{receipt_id}', $this->preview_email_tags_values['receipt_id'], $message );
			$message = str_replace( '{payment_method}', $this->preview_email_tags_values['payment_method'], $message );
			$message = str_replace( '{sitename}', $this->preview_email_tags_values['sitename'], $message );
			$message = str_replace( '{payment_id}', $this->preview_email_tags_values['payment_id'], $message );
			$message = str_replace( '{receipt_link}', $this->preview_email_tags_values['receipt_link'], $message );
			$message = str_replace( '{receipt_link_url}', $this->preview_email_tags_values['receipt_link_url'], $message );
			$message = str_replace( '{pdf_receipt}', $this->preview_email_tags_values['pdf_receipt'], $message );

			return apply_filters( 'give_email_preview_template_tags', $message );
		}
	}

endif; // End class_exists check
