<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmOrderNotificationEmail' ) ) {
/**
 * Class for order notification emails that needs to go out from the plugin
 *
 * @since 2.4.0
 */
class fdmOrderNotificationEmail extends fdmOrderNotification {

	/**
	 * Recipient email
	 * @since 2.4.0
	 */
	public $to_email;

	/**
	 * From email
	 * @since 2.4.0
	 */
	public $from_email;

	/**
	 * From name
	 * @since 2.4.0
	 */
	public $from_name;

	/**
	 * Email subject
	 * @since 2.4.0
	 */
	public $subject;

	/**
	 * Email message body
	 * @since 2.4.0
	 */
	public $message;

	/**
	 * Email headers
	 * @since 2.4.0
	 */
	public $headers;

	/**
	 * Prepare and validate notification data
	 *
	 * @return boolean if the data is valid and ready for transport
	 * @since 2.4.0
	 */
	public function prepare_notification() {

		$this->set_to_email();
		$this->set_from_email();
		$this->set_subject();
		$this->set_headers();
		$this->set_message();

		// Return false if we're missing any of the required information
		if ( 	empty( $this->to_email) ||
				empty( $this->from_email) ||
				empty( $this->from_name) ||
				empty( $this->subject) ||
				empty( $this->headers) ||
				empty( $this->message) ) {
			return false;
		}

		return true;
	}

	public function set_to_email() {
		global $fdm_controller;

		if ( $this->target == 'user' ) {
			$to_email = empty( $this->order->email ) ? null : $this->order->email;

		} else {
			$to_email = $fdm_controller->settings->get_setting( 'fdm-ordering-notification-email' );
		}

		$this->to_email = apply_filters( 'fdm_notification_email_to_email', $to_email, $this );
	}

	public function set_from_email() {
		global $fdm_controller;

		if ( $this->target == 'user' ) {
			$from_email = $fdm_controller->settings->get_setting( 'fdm-ordering-reply-to-address' );
			$from_name = $fdm_controller->settings->get_setting( 'fdm-ordering-reply-to-name' );
		} else {
			$from_email = $this->order->email;
			$from_name = $this->order->name;
		}

		$this->from_email = apply_filters( 'fdm_notification_email_from_email', $from_email, $this );
		$this->from_name = apply_filters( 'fdm_notification_email_from_name', $from_name, $this );

	}

	public function set_subject() {
		
		$this->subject = empty( $this->subject ) ? '' : $this->subject;

		$this->subject = apply_filters( 'fdm_notification_email_subject', $this->subject, $this );
	}

	public function set_headers( $headers = null ) {

		global $fdm_controller;

		$from_email = apply_filters( 'fdm_notification_email_header_from_email', $fdm_controller->settings->get_setting( 'fdm-ordering-reply-to-address' ) );

		$headers = "From: " . stripslashes_deep( html_entity_decode( $fdm_controller->settings->get_setting( 'fdm-ordering-reply-to-name' ), ENT_COMPAT, 'UTF-8' ) ) . " <" . $from_email . ">\r\n";
		$headers .= "Reply-To: =?utf-8?Q?" . quoted_printable_encode( $this->from_name ) . "?= <" . $this->from_email . ">\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";

		$this->headers = apply_filters( 'fdm_notification_email_headers', $headers, $this );
	}

	public function set_message() {
		
		$this->message = empty( $this->message ) ? '' : $this->message;

		$this->message = apply_filters( 'fdm_notification_email_message', wpautop( $this->process_template( $this->message ) ), $this );
	}

	/**
	 * Send notification
	 * @since 2.4.0
	 */
	public function send_notification() {

		return wp_mail( $this->to_email, $this->subject, $this->message, $this->headers, apply_filters( 'fdm_notification_email_attachments', array(), $this ) );
	}
}
}

?>