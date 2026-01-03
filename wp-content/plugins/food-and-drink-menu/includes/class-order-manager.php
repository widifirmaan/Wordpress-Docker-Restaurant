<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmOrderManager' ) ) {
/**
 * Class to handle the orders made through the plugin
 *
 * @since 2.1.0
 */
class fdmOrderManager {

	// whether or not the restaurant is accepting orders
	public $is_open_for_ordering;

	public function __construct() {

		add_action( 'admin_init', array( $this, 'maybe_delete_old_orders' ) );

		add_action( 'transition_post_status', array( $this, 'handle_order_post_status_notifications' ), 10, 3 );
	}

	public function maybe_delete_old_orders() {
		global $fdm_controller;

		if ( get_option( 'fdm-delete-order-check' ) + 3600 > time() ) { return; }

		$order_statuses = fdm_get_order_statuses();

		$args = array( 
			'numberposts' 	=> -1, 
			'post_type' 	=> FDM_ORDER_POST_TYPE,
			'post_status'	=> array_keys( $order_statuses ),
			'date_query' 	=> array(
				array(
					'before' => date('Y-m-d h:i:s', time() - $fdm_controller->settings->get_setting( 'fdm-ordering-order-delete-time' ) * 24*3600 )
				)
			)
		);

		$query = new WP_Query( $args );

		$delete_posts = $query->get_posts();

		foreach ( $delete_posts as $delete_post ) {
			wp_delete_post( $delete_post->ID, true );
		}

		update_option( 'fdm-delete-order-check', time() );
	}

	public function submit_order( $args ) {
		
		global $fdm_controller;

		$args['order_items'] 			= $fdm_controller->cart->get_cart_items();
		$args['edit_confirmation_code']	= $this->return_random_string();

		if ( ! isset( $args['post_status'] ) ) { $args['post_status'] = 'fdm_order_received'; }

		$order = new fdmOrderItem( $args );

		$order->save_order_post(); 

		$fdm_controller->cart->clear_cart();

		// Email will be triggerd by the action set on post_status transition

		$this->set_recent_order_cookie( $order->id );

		return $order->id;
	}

	/**
	 * Handle any notifications that need to be sent on order status change
	 * @since 2.4.1
	 */
	public function handle_order_post_status_notifications( $new_status, $old_status, $post ) {
		global $fdm_controller;

		if ( $post->post_type != FDM_ORDER_POST_TYPE ) { return; }

		if ( $new_status == $old_status ) { return; }

		$order = new fdmOrderItem();

		$order->load( $post ); 

		$notifications = fdm_decode_infinite_table_setting( $fdm_controller->settings->get_setting( 'order-notifications' ) );

		foreach ( $notifications as $notification ) {

			if ( ! $notification->enabled ) { continue; }

			if ( $notification->status != $new_status ) { continue; }

			$args = array( 
				'order'		=> $order, 
				'target'	=> $notification->target,
			);
			
			$order_notification = $notification->type == 'sms' ? new fdmOrderNotificationSMS( $args ) : new fdmOrderNotificationEmail( $args );

			$order_notification->set( 'message', $notification->message );
			$order_notification->set( 'subject', $notification->subject );

			$order_notification->prepare_notification();
			$order_notification->send_notification();
		}
	}

	public function set_recent_order_cookie( $id ) {

		setcookie( 'fdm_recent_order', $id, time() + 2*3600, '/' );
	}

	public function get_recent_order_id() {

		return isset( $_COOKIE['fdm_recent_order'] ) ? intval( $_COOKIE['fdm_recent_order'] ) : 0;
	}

	/**
	 * Delete an order (or send to trash)
	 *
	 * @since 2.1.0
	 */
	public function delete_order( $id ) {

		$id = absint( $id );
		if ( ! current_user_can( 'manage_fdm_orders' ) ) {
			return false;
		}

		$order = get_post( $id );

		if ( ! $this->is_valid_order_post_object( $order ) ) {
			return false;
		}

		// If we're already looking at trashed posts, delete it for good.
		// Otherwise, just send it to trash.
		if ( !empty( $_GET['status'] ) && $_GET['status'] == 'trash' ) {
			$screen = get_current_screen();
			if ( $screen->base == 'menu-posts-fdm-menu' ) {
				$result = wp_delete_post( $id, true );
			}
		} else {
			$result = wp_trash_post( $id );
		}

		if ( $result === false ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Update an order status.
	 * @since 2.1.0
	 */
	function update_order_status( $id, $status ) {

		$id = absint( $id );
		if ( ! current_user_can( 'manage_fdm_orders' ) ) {
			return false;
		}

		if ( !$this->is_valid_order_status( $status ) ) {
			return false;
		}

		$order = get_post( $id );

		if ( !$this->is_valid_order_post_object( $order ) ) {
			return false;
		}

		if ( $order->post_status === $status ) {
			return null;
		}

		$result = wp_update_post(
			array(
				'ID'			=> $id,
				'post_status'	=> $status,
				'edit_date'		=> current_time( 'mysql' ),
			)
		);

		return $result ? true : false;
	}

	/**
	 * Check if status is valid for orders
	 * @since 2.1.0
	 */
	public function is_valid_order_status( $status ) {
		$order_statuses = fdm_get_order_statuses();

		return isset( $order_statuses[ $status ] ) ? true : false;
	}

	/**
	 * Check if order is a valid Post object with the correct post type
	 * @since 2.1.0
	 */
	public function is_valid_order_post_object( $order ) {
		return ! is_wp_error( $order ) && is_object( $order ) && $order->post_type == FDM_ORDER_POST_TYPE;
	}

	public function is_open_for_ordering() {
		global $fdm_controller;

		if ( ! empty( $fdm_controller->settings->get_setting( 'fdm-ordering-pause-ordering' ) ) ) { return false; }

		$args = array(
			'post_type'			=> FDM_ORDER_POST_TYPE,
			'post_status'		=> 'fdm_order_received',
			'posts_per_page'	=> -1,
		);

		$orders = get_posts( $args );
		
		if ( ! empty( $fdm_controller->settings->get_setting( 'fdm-ordering-maximum-received-orders' ) ) and sizeOf( $orders ) >= $fdm_controller->settings->get_setting( 'fdm-ordering-maximum-received-orders' ) ) { return false; }

		if ( empty( $this->is_open_for_ordering ) ) { $this->is_open_for_ordering = $this->determine_open_for_ordering(); }

		return $this->is_open_for_ordering;
	}

	public function determine_open_for_ordering() {
		global $fdm_controller;

		$schedule_open 	= is_array( $fdm_controller->settings->get_setting( 'schedule-open' ) ) ? $fdm_controller->settings->get_setting( 'schedule-open' ) : array();
		$exceptions 	= is_array( $fdm_controller->settings->get_setting( 'schedule-closed' ) ) ? $fdm_controller->settings->get_setting( 'schedule-closed' ) : array();

		if ( empty( $schedule_open ) ) { return true; }

		$timezone = wp_timezone(); 
		$gmt_seconds_offset = $timezone->getOffset( new DateTime );

		$today_weekday = strtolower( date( 'l', time() + $gmt_seconds_offset ) ); 
		$today_date = date( 'Y-m-d ', time() + $gmt_seconds_offset ); 

		$ordering_open = false;

		foreach ( $schedule_open as $rule ) {
			if ( !empty( $rule['weekdays'] ) ) {
				foreach ( $rule['weekdays'] as $weekday => $value ) { 
					
					if ( $weekday == $today_weekday and $value ) {
						if ( empty( $rule['time'] ) ) { $ordering_open = true; }						
						else { 
							if ( time() + $gmt_seconds_offset  > strtotime( $today_date . $rule['time']['start']) and time() + $gmt_seconds_offset  < strtotime( $today_date . $rule['time']['end'])) { $ordering_open = true; }
						}
					}

				}
			}
		}

		if ( ! is_array( $exceptions ) ) { return $ordering_open; }

		foreach ( $exceptions as $exception ) {
			if ( !empty( $exception['date'] ) and date('Y-m-d ', strtotime( $exception['date'] ) ) == $today_date ) {
				
				if ( empty( $exception['time'] ) ) { $ordering_open = false; }						
				else {
					if ( time() + $gmt_seconds_offset  > strtotime( $today_date . $exception['time']['start'] ) and time() + $gmt_seconds_offset  < strtotime( $today_date . $exception['time']['end'] ) ) { $ordering_open = true; }
				}
			}
		}

		return $ordering_open;
	}

	public function return_random_string( $length = 4 ) {
       
    	$random_string     = '';
    	$characters         = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    	$characters_length = strlen( $characters );
    
   		for ( $i = 0; $i < $length; $i++ ) {
    		$random_string .= substr( $characters, rand( 0, $characters_length ), 1 );
    	}
    
    	return $random_string;
    }
}
} // endif
