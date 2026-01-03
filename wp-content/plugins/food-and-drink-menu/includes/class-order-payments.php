<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmOrderPayments' ) ) {
/**
 * Class to handle payments made for orders made through the plugin
 *
 * @since 2.4.8
 */
class fdmOrderPayments {

	public function __construct() {

		add_action( 'fdm_menu_init', array( $this, 'payment_enqueues' ) );

		add_action( 'init', array( $this, 'setup_paypal_ipn' ), 1);

		add_action( 'fdm_menu_init', array( $this, 'process_stripe_payment' ) );

	    add_action( 'wp_ajax_fdm_stripe_get_intent', array( $this, 'create_stripe_payment_intent' ) );
	    add_action( 'wp_ajax_nopriv_fdm_stripe_get_intent', array( $this, 'create_stripe_payment_intent' ) );
	
	    add_action( 'wp_ajax_fdm_stripe_payment_succeed', array( $this, 'stripe_sca_succeed' ) );
	    add_action( 'wp_ajax_nopriv_fdm_stripe_payment_succeed', array( $this, 'stripe_sca_succeed' ) );
	
	    add_filter( 'fdm_order_metadata_defaults', array( $this, 'default_order_stripe_info' ), 30, 1 );
	    add_action( 'fdm_order_load_post_data', array( $this, 'populate_order_stripe_info' ), 30, 1 );
	    add_filter( 'fdm_insert_order_metadata', array( $this, 'save_order_stripe_info' ), 30, 2 );
	
	    /**
	     * Adding info and capability to charge the hold manually in the orders table for admin
	     */
	    add_filter( 'fdm_admin_orders_list_row_classes', array( $this, 'add_hold_class' ), 30, 2 );
	    add_filter( 'fdm_orders_table_column_details', array( $this, 'add_hold_detail' ), 30, 2 );
	    add_filter( 'fdm_orders_table_bulk_actions', array( $this, 'add_bulk_action' ), 30, 1 );
	    add_filter( 'fdm_orders_table_bulk_action', array( $this, 'charge_the_hold' ), 30, 3 );
	}

	/**
	 * Enqueues necessary payment JS and CSS files, when required
	 * @since 2.4.8
	 */
	public function payment_enqueues() {
		global $fdm_controller;

		if ( ! $fdm_controller->settings->get_setting( 'enable-payment' ) ) { return; }
	
		if ( $fdm_controller->settings->get_setting( 'ordering-payment-gateway' ) == 'paypal' ) {

			wp_enqueue_script( 'fdm-paypal-payment', FDM_PLUGIN_URL . '/assets/js/paypal-payment.js', array( 'jquery' ), FDM_VERSION, true );

			wp_localize_script(
				'fdm-paypal-payment',
				'fdm_paypal_payment',
				array(
					'nonce'                 => wp_create_nonce( 'fdm-paypal-payment' ),
				)
			);
		} 
		else {
			
			$stripe_lib_version = $fdm_controller->settings->get_setting( 'fdm-stripe-sca' ) ? 'v3' : 'v2';

			wp_enqueue_script( 'fdm-stripe', "https://js.stripe.com/{$stripe_lib_version}/", array( 'jquery' ), FDM_VERSION, true );
			wp_enqueue_script( 'fdm-stripe-payment', FDM_PLUGIN_URL . '/assets/js/stripe-payment.js', array( 'jquery', 'fdm-stripe' ), FDM_VERSION, true );
			
			wp_localize_script(
				'fdm-stripe-payment',
				'fdm_stripe_payment',
				array(
					'nonce'                 => wp_create_nonce( 'fdm-stripe-payment' ),
					'currency'              => $fdm_controller->settings->get_setting( 'ordering-currency' ),
					'stripe_mode' 			=> $fdm_controller->settings->get_setting( 'ordering-payment-mode' ),
					'hold'                  => $fdm_controller->settings->get_setting( 'fdm-stripe-hold' ),
        			'stripe_sca'            => $fdm_controller->settings->get_setting( 'fdm-stripe-sca' ),
					'live_publishable_key' 	=> $fdm_controller->settings->get_setting( 'stripe-live-publishable' ),
					'test_publishable_key' 	=> $fdm_controller->settings->get_setting( 'stripe-test-publishable' ),
				)
			);
		}
	}

	/**
	 * Set up buffers and process an IPN request, if one exists
	 * @since 2.4.8
	 */
	public function setup_paypal_ipn() {
		global $fdm_controller;

		if ( ! isset($_POST['ipn_track_id']) ) { return; }

		if ( ! $fdm_controller->settings->get_setting( 'enable-payment' ) or $fdm_controller->settings->get_setting( 'ordering-payment-gateway' ) != 'paypal' ) { return; }
	
		add_action(	'init', array( $this, 'add_ob_start' ) );
		add_action(	'shutdown', array( $this, 'flush_ob_end' ) );
	
		// CONFIG: Enable debug mode. This means we'll log requests into 'ipn.log' in the same directory.
		// Especially useful if you encounter network errors or other intermittent problems with IPN (validation).
		// Set this to 0 once you go live or don't require logging.
		define("FDM_DEBUG", 0);
		// Set to 0 once you're ready to go live
		define("FDM_USE_SANDBOX", $fdm_controller->settings->get_setting( 'ordering-payment-mode' ) == 'test' ? true : 0 );
		define("FDM_LOG_FILE", "./ipn.log");
		// Read POST data
		// reading posted data directly from $_POST causes serialization
		// issues with array data in POST. Reading raw POST data from input stream instead.
		$raw_post_data = file_get_contents('php://input');
		$raw_post_array = explode('&', $raw_post_data);
		$myPost = array();
		foreach ($raw_post_array as $keyval) {
			$keyval = explode ('=', $keyval);
			if (count($keyval) == 2)
				$myPost[$keyval[0]] = urldecode($keyval[1]);
		}
		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';
		if(function_exists('get_magic_quotes_gpc')) {
			$get_magic_quotes_exists = true;
		}
		foreach ($myPost as $key => $value) {
			if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
				$value = urlencode(stripslashes($value));
			} else {
				$value = urlencode($value);
			}
			$req .= "&$key=$value";
		}
		// Post IPN data back to PayPal to validate the IPN data is genuine
		// Without this step anyone can fake IPN data
		if(FDM_USE_SANDBOX == true) {
			$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		} else {
			$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
		}
	
		$response = wp_remote_post($paypal_url, array(
			'method' => 'POST',
			'body' => $req,
			'timeout' => 30
		));
	
		// Inspect IPN validation result and act accordingly
		// Split response headers and payload, a better way for strcmp
		$tokens = explode("\r\n\r\n", trim($response['body']));
		$res = trim(end($tokens));
		if (strcmp ($res, "VERIFIED") == 0) {
			
			$paypal_receipt_number = sanitize_text_field( $_POST['txn_id'] );
			$payment_amount = sanitize_text_field( $_POST['mc_gross'] );
			
			parse_str($_POST['custom'], $custom_vars); 
			$order_id = intval( $custom_vars['order_id'] );
	
			$order = new fdmOrderItem();
			$order->load( $order_id );
	
			if ( ! $order ) { return; }
	
			$order->receipt_id = sanitize_text_field( $paypal_receipt_number );
			$order->payment_amount = sanitize_text_field( $payment_amount );
			$order->post_status = 'fdm_order_received';
	
			$order->save_order_post();
			
			if ( FDM_DEBUG == true ) {
				error_log(date('[Y-m-d H:i e] '). "Verified IPN: $req ". PHP_EOL, 3, FDM_LOG_FILE);
			}
		}
	}

	/**
	 * Opens a buffer when a PayPal payment is being processed
	 * @since 2.4.8
	 */
	public function add_ob_start() {

		ob_start();
	}

	/**
	 * Closes a buffer when a PayPal payment is being processed, if one exists
	 * @since 2.4.8
	 */
	public function flush_ob_end() {

		if ( ob_get_length() ) { ob_end_clean(); }
	}

	/**
	 * Process a Stripe payment using the legacy (non-SCA) token method
	 * @since 2.4.8
	 */
	public function process_stripe_payment() {
		global $fdm_controller;

		if ( ! $fdm_controller->settings->get_setting( 'enable-payment' ) or $fdm_controller->settings->get_setting( 'ordering-payment-gateway' ) != 'stripe' ) { return; }

		if ( ! isset( $_POST['stripeToken'] ) or ! isset( $_POST['order_id'] ) ) { return; }
	
		$order = new fdmOrderItem();
		$order->load( $order_id );	
	 
		// load the stripe libraries
		require_once( FDM_PLUGIN_DIR . '/lib/stripe/init.php');
			
		// retrieve the token generated by stripe.js
		$token = $_POST['stripeToken'];
	
		$payment_amount = ( $fdm_controller->settings->get_setting( 'ordering-currency' ) != "JPY" ? intval( $_POST['payment_amount'] ) : intval( $_POST['payment_amount'] ) / 100 );
	
		try {
	
			\Stripe\Stripe::setApiKey( $this->get_secret() );
			$charge = \Stripe\Charge::create(array(
					'amount' 	=> $payment_amount, 
					'currency' 	=> strtolower( $fdm_controller->settings->get_setting( 'ordering-currency' ) ),
					'card' 		=> $token,
					'metadata' 	=> array(
						'Name'	=> $order->name,
						'Email'	=> $order->email,
						'Phone'	=> $order->phone,
						'Note'	=> $order->note
					)
				)
			);
	
			$order->post_status = 'fdm_order_received';
			$order->payment_amount = ( $fdm_controller->settings->get_setting( 'ordering-currency' ) != "JPY" ? $payment_amount / 100 : $payment_amount );
			$order->receipt_id = $charge->id;
	
			$order->save_order_post();
	
			echo '<div class="fdm-order-payment-message fdm-order-payment-successful">' . sprintf( $fdm_controller->settings->get_setting( 'label-order-payment-success' ), fdm_format_price( $order->payment_amount ) ) . '</div>';
		 
		} catch (Exception $e) {
	
			echo '<div class="fdm-order-payment-message fdm-order-payment-failed">' . sprintf( $fdm_controller->settings->get_setting( 'label-order-payment-failed' ), $e->getDeclineCode() ) . '</div>';
		}
	}

	/**
	 * Create Stripe payment intent for order payments
	 * Respond to AJAX/XHR request
	 * 
	 * @since 2.4.8
	 */
	public function create_stripe_payment_intent() {
    	global $fdm_controller;

    	if ( !check_ajax_referer( 'fdm-stripe-payment', 'nonce' ) ) { $this->response_message( false, __( 'The request has been rejected because it does not appear to have come from this site.', 'food-and-drink-menu' ) ); }

    	if ( ! isset( $_POST['order_id'] ) ) { $this->response_message( false, __( 'Invalid order.', 'food-and-drink-menu' ) ); }

    	$order = new fdmOrderItem();
    	$order->load( absint( $_POST['order_id'] ) );

    	$return_url = ! empty( $_POST['return_url'] ) ? sanitize_url( $_POST['return_url'] ) : get_permalink();

    	$payment_amount = ( $fdm_controller->settings->get_setting( 'ordering-currency' ) != "JPY" ? intval( $_POST['payment_amount'] ) : intval( $_POST['payment_amount'] ) / 100 );

    	// load the stripe libraries
    	require_once( FDM_PLUGIN_DIR . '/lib/stripe/init.php' );

    	try {

      		\Stripe\Stripe::setApiKey( $this->get_secret() );

    		$metadata = array_filter(
    			array(
					'Name'	=> $order->name,
					'Email'	=> $order->email,
					'Phone'	=> $order->phone,
					'Note'	=> $order->note
				)
    		);

    		$description = __( 'Order', 'food-and-drink-menu' ) . ' - ' . $order->id . ' ; ' . get_bloginfo( 'name' );
    		$statement_description = substr( get_bloginfo( 'name' ) . ' ; ' . __( 'Order', 'food-and-drink-menu' ) . ' - ' . $order->id, 0, 22 );

    		$intent_data = array(
    		  'amount'                      => $payment_amount,
    		  'currency'                    => strtolower( $fdm_controller->settings->get_setting( 'ordering-currency' ) ),
    		  'automatic_payment_methods'   => array(
    		    'enabled'                     => true,
    		  ),
    		  'description'                 => apply_filters( 'fdm-stripe-payment-description', $description ),
    		  'statement_descriptor'        => apply_filters( 'fdm-stripe-payment-statement-description', $statement_description ),
    		  'metadata'                    => $metadata,
    		);

    		if ( is_email( $order->email ) ) {

    			$intent_data['receipt_email'] = $order->email;
    		}

      		$order->stripe_payment_hold_status = 'not-placed';

    		if ( $fdm_controller->settings->get_setting( 'fdm-stripe-hold' ) ) {

        		$order->stripe_payment_hold_status = 'hold-placed';
        		$intent_data['capture_method'] = 'manual';
      		}

    		$intent = \Stripe\PaymentIntent::create( $intent_data );

      		// Used this for verification of two step payment processing under SCA
      		$order->stripe_payment_intent_id = $intent->id;

      		$order->save_order_post();

    		$args = array(
    		  'clientSecret'  => $intent->client_secret,
    		  'name'          => $order->name,
    		  'email'         => $order->email,
    		  'redirect_url'  => $return_url,
    		);

      		$this->response_message( true, __( 'Payment Intent generated succsssfully', 'food-and-drink-menu' ), $args );
    	}
    	catch( Exception $ex ) {

      		$this->response_message( false, 'Please try again.', array( 'error' => $ex->getError() ) );
    	}
  	}

	/**
	 * Stripe SCA payment's final status for order payments
	 * Respond to AJAX/XHR request
	 * 
	 * @since 2.4.8
	 */
	public function stripe_sca_succeed() {
    	global $fdm_controller;

    	if ( !check_ajax_referer( 'fdm-stripe-payment', 'nonce' ) ) {

      		$this->response_message( false, __( 'The request has been rejected because it does not appear to have come from this site.', 'food-and-drink-menu' ) );
    	}

    	// Response variables with fallback defaults
    	$success = false;
    	$url_params = '';
    	$data = array();

    	if ( ! isset( $_POST['order_id'] ) ) { $this->sca_response( $success, $url_params, $data ); }

    	$order = new fdmOrderItem();
    	$loaded = $order->load( intval( $_POST['order_id'] ) );

    	try {

      		if ( isset( $_POST['success'] ) && 'false' != sanitize_text_field( $_POST['success'] ) ) {

        		if ( ! $loaded or ! $this->valid_payment( $order ) ) {

          			throw new Exception( __( 'Invalid submission. Please contact admin', 'food-and-drink-menu' ) );
        		}

        		$order->post_status = 'fdm_order_received';

        		$order->payment_amount = $fdm_controller->settings->get_setting( 'ordering-currency' ) != 'JPY' ? intval( $_POST['payment_amount'] ) / 100 : intval( $_POST['payment_amount'] );

        		$order->receipt_id = sanitize_text_field( $_POST['payment_id'] );

        		// Not needed anymore
        		unset( $order->stripe_payment_intent_id );
        		$order->save_order_post();

        		// url_params on successful payment
        		$success = true;

        		$url_params = array(
        		  'payment'    => 'paid',
        		  'order_id' => intval( $order->id )
        		);

      		}
      		else {

        		$payment_failure_message = ! empty( $_POST['message'] ) 
        		  ? sanitize_text_field( $_POST['message'] ) 
        		  : __( 'Payment charge failed. Please try again', 'food-and-drink-menu' );

        		throw new Exception( $payment_failure_message );
      		}
    	}
    	catch(Exception $ex) {

      		$loaded && $order->payment_failed( $ex->getMessage() );
      		$data['message'] = $ex->getMessage();
    	}

    	$this->sca_response( $success, $url_params, $data );
  	}

	/**
	 * Validate the payment success request by verifing the payment_intent ID
	 * 
	 * @return bool true on valid else false
	 */
	public function valid_payment( $order ) {

    	return sanitize_text_field( $_POST['payment_id'] ) == $order->stripe_payment_intent_id;
  	}

	/**
	 * Repopulate $order with stripe meta information
	 * 
	 * @param fdmOrderItem $order
	 */
	public function populate_order_stripe_info( $order ) {

   		$meta = get_post_meta( $order->ID, 'order_data', true );

    	if ( is_array( $meta ) && isset( $meta['stripe_customer_id'] ) ) {

      		$order->stripe_customer_id = $meta['stripe_customer_id'];
    	}

    	if ( is_array( $meta ) && isset( $meta['stripe_payment_intent_id'] ) ) {

      		$order->stripe_payment_intent_id = $meta['stripe_payment_intent_id'];
    	}

    	if ( is_array( $meta ) && isset( $meta['stripe_payment_hold_status'] ) ) {

      		$order->stripe_payment_hold_status = $meta['stripe_payment_hold_status'];
    	}
  	}

	/**
	 * Set $order's default stripe meta information
	 * 
	 * @param array $meta
	 */
	public function default_order_stripe_info( $meta ) {

    	$meta['stripe_customer_id'] = '';

    	return $meta;
  	}

	/**
	 * Store permanently $order's default stripe meta information
	 * 
	 * @param arrray $meta
	 * @param fdmOrderItem $order
	 */
	public function save_order_stripe_info( $meta, $order ) {

    	if ( isset( $order->stripe_customer_id ) && !empty( $order->stripe_customer_id ) ) {

      		$meta['stripe_customer_id'] = $order->stripe_customer_id;
    	}

    	if ( isset( $order->stripe_payment_intent_id ) && !empty( $order->stripe_payment_intent_id ) ) {

      		$meta['stripe_payment_intent_id'] = $order->stripe_payment_intent_id;
    	}

    	if ( isset( $order->stripe_payment_hold_status ) ) {

      		$meta['stripe_payment_hold_status'] = $order->stripe_payment_hold_status;
    	}

    	return $meta;
  	}

	/**
	 * Add the CSS class to admin order listing
	 * @param array $row_class_list css classes for the row
	 * @param fdmOrderItem $order
	 */
	public function add_hold_class( $row_class_list, $order ) {

    	if ( $this->is_payment_on_hold( $order ) ) {

      		$row_class_list[] = 'payment-on-hold';
    	}

    	return $row_class_list;
  	}

	/**
	 * Add hold information in the details popup of the order for admin
	 * @param array $details Label/value item array
	 * @param fdmOrderItem $order
	 */
	public function add_hold_detail( $details, $order ) {

    	if ( ! is_array( $details ) ) { return; }

    	if ( $this->is_payment_on_hold( $order ) ) {

    		$details[] = array(
    			'label' => __( 'Payment on Hold', 'food-and-drink-menu' ),
    			'value' => __( 'Payment has been held on the card, but not charged yet.', 'food-and-drink-menu' )
    		);
    	}
    	else if ( $this->is_payment_hold_captured( $order ) ) {

    		$details[] = array(
    			'label' => __( 'Held Payment Captured', 'food-and-drink-menu' ),
    			'value' => __( 'Payment has been captured.', 'food-and-drink-menu' )
    		);
    	}

    	return $details;
	}

	/**
	 * Add Bulk action to order page for admin to charge the hold manually
	 * @param array $actions
	 */
	public function add_bulk_action( $actions ) {

    	$actions['capture-payment'] = __( 'Charge Payment on Hold',  'food-and-drink-menu' );

    	return $actions;
	}

	/**
	 * Complete the hold and charge the customer
	 * @param  array $result Array with order ID as key and value as message
	 * @param  int $id order-id
	 * @param  string $action bulk action
	 * @return $result result array with result if we processed the order
	 */
	public function charge_the_hold( $result, $id, $action ) {

    	if ( 'capture-payment' !== $action ) {

    		return $result;
    	}

    	$order = new fdmOrderItem();

    	if ( $order->load( intval( $id ) ) ) {

      		if ( $this->is_payment_on_hold( $order ) ) {

        		try {

          			// load the stripe libraries
          			require_once( FDM_PLUGIN_DIR . '/lib/stripe/init.php' );

          			\Stripe\Stripe::setApiKey( $this->get_secret() );

          			$intent = \Stripe\PaymentIntent::retrieve( $order->receipt_id );
          			$intent->capture();

          			if ( 'succeeded' == $intent->status ) {

            			$this->hold_captured( $order );
            			// Payment has been captured successfully
            			$result[$id] = true;
          			}
         			else {

            			$result[ $id ] = false;

            			if( defined('WP_DEBUG') and WP_DEBUG ) {
              				error_log(sprintf( __( 'Five Star FDM: Stripe Payment capture failed. Reason: %s', 'food-and-drink-menu' ), $intent->status ));
            			}
          			}
        		}
        		catch( Exception $ex ) {

          			$result[ $id ] = false;

          			if( defined('WP_DEBUG') and WP_DEBUG ) {
            			error_log( sprintf( __( 'Five Star FDM: Stripe Payment capture failed. Reason: %s', 'food-and-drink-menu' ), $ex->getMessage() ) );
          			}
        		}
      		}
      		else {

        		// We do not have a hold for this order
        		// $result[$id] = true;
      		}
    	}
    	else {

    		$result[ $id ] = false;

      		if ( defined('WP_DEBUG') and WP_DEBUG ) {

        		error_log( sprintf( __( 'Unable to find the Order for ID %s', 'food-and-drink-menu' ), $id ) );
      		}
    	}

    	return $result;
	}

	/**
	 * Sends a response with success/failure of payment
	 * @param  boolean $success
	 * @param  string $msg
	 * @param  array $data
	 * @return null
	 */
	public function response_message( $success, $msg, $data = array() ) {

    	echo json_encode(
    		array_merge(
    			array(
    				'success' => $success,
    				'message' => $msg
    			), 
    			$data
    		)
    	);

    	exit(0);
	}

	/**
	 * Sends a final response after checking whether payment intent was successfully processed
	 * @param  boolean $success
	 * @param  mixed $url_params
	 * @param  array $data
	 * @return null
	 */
	public function sca_response( $success, $url_params, $data = array() ) {

    	echo json_encode(
    		array_merge(
    			array(
    				'success' => $success,
    				'urlParams' => $url_params
    			), 
    			$data
    		)
    	);

    	exit(0);
	}

	/**
	 * Check whether the payment is on hold or not
	 * @param fdmOrderItem $order
	 * @return boolean
	 */
	public function is_payment_on_hold( $order ) {

    	return isset( $order->stripe_payment_hold_status ) && 'hold-placed' == $order->stripe_payment_hold_status;
	}

  	/**
  	 * Check whether the payment is on hold or not
  	 * @param fdmOrderItem $order
  	 * @return boolean
  	 */
  	public function is_payment_hold_captured( $order ) {

    	return isset( $order->stripe_payment_hold_status ) && 'hold-captured' == $order->stripe_payment_hold_status;
	}

	/**
	 * Mark the Payment Hold for the order as Captured
	 * @param  fdmOrderItem $order
	 * @return fdmOrderPayments
	 */
	public function hold_captured( $order ) {

    	$order->stripe_payment_hold_status = 'hold-captured';
    	$order->save_order_post();

    	return $this;
	}

	/**
	 * Get Stripe secret
	 * @return string
	 */
	public function get_secret() {
    	global $fdm_controller;

    	return 'test' == $fdm_controller->settings->get_setting( 'ordering-payment-mode' ) ? $fdm_controller->settings->get_setting( 'stripe-test-secret' ) : $fdm_controller->settings->get_setting( 'stripe-live-secret' );
	}
}
} // endif
