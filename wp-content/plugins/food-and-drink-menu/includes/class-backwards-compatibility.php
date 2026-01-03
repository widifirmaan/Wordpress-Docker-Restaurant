<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'fdmBackwardsCompatibility' ) ) {
/**
 * Class to handle transforming any settings when updating versions
 *
 * @since 2.4.1
 */
class fdmBackwardsCompatibility {

	public function __construct() {

		if ( get_transient( 'fdm-plugin-updated' ) ) { 
			
			add_action( 'plugins_loaded', array( $this, 'convert_notifications_to_table' ) );
		}
	} 
	
	public function convert_notifications_to_table() {
		global $fdm_controller;

		if ( ! empty( $fdm_controller->settings->get_setting( 'order-notifications' ) ) ) { return; }

		$new_notification_settings = array(
			array(
				'enabled'	=> true,
				'status'	=> 'fdm_order_received',
				'type'		=> 'email',
				'target'	=> 'admin',
				'subject'	=> $fdm_controller->settings->get_setting( 'admin-email-subject' ),
				'message'	=> $fdm_controller->settings->get_setting( 'admin-email-template' ),
			),
			array(
				'enabled'	=> $fdm_controller->settings->get_setting( 'customer-notification-type' ) == 'none' ? false : true,
				'status'	=> 'fdm_order_accepted',
				'type'		=> $fdm_controller->settings->get_setting( 'customer-notification-type' ) == 'sms' ? 'sms' : 'email',
				'target'	=> 'admin',
				'subject'	=> $fdm_controller->settings->get_setting( 'customer-email-subject' ),
				'message'	=> $fdm_controller->settings->get_setting( 'customer-email-template' ),
			),
		);

		$fdm_controller->settings->set_setting( 'order-notifications', json_encode( $new_notification_settings ) );

		$fdm_controller->settings->save_settings();
	}
}

}