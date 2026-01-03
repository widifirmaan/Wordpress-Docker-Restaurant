<?php
/**
 * Template tags and shortcodes for use with Food and Drink Menu
 */


/**
 * Create a shortcode to display a menu
 * @since 1.0
 */
function fdm_menu_shortcode( $atts ) {
	global $fdm_controller;

	// Define shortcode attributes
	$menu_atts = array(
		'id' => null,
		'layout' => 'classic',
		'menu_prostyle' => '',
		'show_title' => false,
		'show_content' => false,
		'hide_ordering' => '',
		'sidebar' => ''
	);

	// Create filter so addons can modify the accepted attributes
	$menu_atts = apply_filters( 'fdm_shortcode_menu_atts', $menu_atts );

	extract( shortcode_atts( $menu_atts, $atts ) ); 

	if ( $menu_prostyle and $fdm_controller->permissions->check_permission( 'styling' ) ) {
		
		add_filter( 'fdm-setting-fdm-pro-style', function() use ( $menu_prostyle ) { return $menu_prostyle; } );
	}

	if ( $sidebar == 'no' ) {
		
		add_filter( 'fdm-sidebar', function() { return '0'; } );
	}

	if ( $hide_ordering == 'yes' ) {
		
		add_filter( 'body_class', 'fdm_hide_ordering_features' );
	}

	// Extract the shortcode attributes
	$args = shortcode_atts( $menu_atts, $atts, 'fdm-menu' );

	do_action( 'fdm_menu_init', $args );

	if ( ! empty( $_GET['order_success'] ) ) {

		echo '<div class="fdm-order-payment-message fdm-order-payment-successful">' . esc_html( $fdm_controller->settings->get_setting( 'label-order-success' ) ) . '</div>';
	}

	fdm_possible_order_status_update();

	// Render menu
	fdm_load_view_files();
	$menu = new fdmViewMenu( $args );

	return $menu->render();
}
add_shortcode( 'fdm-menu', 'fdm_menu_shortcode' );

/**
 * Create a shortcode to display a menu section
 * @since 1.0
 */
function fdm_menu_section_shortcode( $atts ) {

	// Define shortcode attributes
	$menu_section_atts = array(
		'id' => null,
		'stand_alone' => true,
	);

	// Create filter so addons can modify the accepted attributes
	$menu_section_atts = apply_filters( 'fdm_shortcode_menu_section_atts', $menu_section_atts );

	// Extract the shortcode attributes
	$args = shortcode_atts( $menu_section_atts, $atts, 'fdm-menu-section' );

	// Render menu
	fdm_load_view_files();
	$menu = new fdmViewSection( $args );

	return $menu->render();
}
add_shortcode( 'fdm-menu-section', 'fdm_menu_section_shortcode' );

/**
 * Create a shortcode to display a menu item
 * @since 1.1
 */
function fdm_menu_item_shortcode( $atts ) {

	// Define shortcode attributes
	$menu_item_atts = array(
		'id' => null,
		'layout' => 'classic',
		'singular' => true
	);

	// Create filter so addons can modify the accepted attributes
	$menu_item_atts = apply_filters( 'fdm_shortcode_menu_item_atts', $menu_item_atts );

	// Extract the shortcode attributes
	$args = shortcode_atts( $menu_item_atts, $atts, 'fdm-menu-item' );

	// Render menu
	fdm_load_view_files();
	$menuitem = new fdmViewItem( $args );

	return $menuitem->render();
}
add_shortcode( 'fdm-menu-item', 'fdm_menu_item_shortcode' );

/**
 * Load files needed for views
 * @since 1.1
 * @note Can be filtered to add new classes as needed
 */
function fdm_load_view_files() {

	$files = array(
		FDM_PLUGIN_DIR . '/views/Base.class.php' // This will load all default classes
	);

	$files = apply_filters( 'fdm_load_view_files', $files );

	foreach( $files as $file ) {
		require_once( $file );
	}

}

/*
 * Assign a globally unique id for each displayed menu
 */
$globally_unique_id = 0;
function fdm_global_unique_id() {
	global $globally_unique_id;
	$globally_unique_id++;
	return 'fdm-menu-' . $globally_unique_id;
}

/**
 * Transform an array of CSS classes into an HTML attribute
 * @since 1.0
 */
function fdm_format_classes($classes) {
	if (count($classes)) {
		return ' class="' . join(" ", $classes) . '"';
	}
}

/**
 * Format the item prices based on the currency symbol settings
 * @since 2.1
 */
function fdm_format_price( $price ) {
	global $fdm_controller;

	$prefix = ( $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'before' ? $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) : '' );
	$suffix = ( $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'after' ? $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) : '' );

	$decimals = $fdm_controller->settings->get_setting( 'fdm-disable-price-decimals' ) ? 0 : 2;

	$price = $prefix . ( is_numeric( $price ) ? number_format( (float) $price, $decimals ) : $price ) . $suffix;

	return $price;
}

/**
 * Adds the currently entered tax rate to the price
 * @since 2.4.1
 */
function fdm_add_tax_to_price( $price ) {
	global $fdm_controller;

	$tax_rate = floatval( preg_replace( '/[^0-9.-]/', '', $fdm_controller->settings->get_setting( 'ordering-tax-rate' ) ) ) / 100;

	return round( $price * ( 1 + $tax_rate ), 2 );
}

/**
 * Return the price total based on the size and options selected
 * @since 2.1
 */
function fdm_calculate_cart_price( $menu_item ) {

	$ordering_options = get_post_meta( $menu_item->id, '_fdm_ordering_options', true );
	if ( ! is_array( $ordering_options ) ) { $ordering_options = array(); }

	$selected_options = is_array( $menu_item->selected_options ) ? $menu_item->selected_options : array();

	$price = str_replace( ',', '.', preg_replace( '/[^0-9,.]+/', '', $menu_item->order_price ) );

	foreach ( $selected_options as $selected_option ) { 

		$option_price = is_numeric( str_replace( ',', '.', $ordering_options[ $selected_option ]['cost'] ) ) ? str_replace( ',', '.', $ordering_options[ $selected_option ]['cost'] ) : 0;
		$price += $option_price; 
	}

	return apply_filters( 'fdm_cart_price_value', $price, $menu_item );
}

/**
 * Return the price total based on the size and options selected for the admin table
 * @since 2.1.11
 */
function fdm_calculate_admin_price( $order_item ) {

	$ordering_options = get_post_meta( $order_item->id, '_fdm_ordering_options', true );
	if ( ! is_array( $ordering_options ) ) { $ordering_options = array(); }
	
	$selected_options = is_array( $order_item->selected_options ) ? $order_item->selected_options : array();

	$talk = '';

	$sanitized_price = str_replace( ',', '.', preg_replace( '/[^0-9,.]+/', '', $order_item->selected_price ) );
	$price = ( ! empty( $sanitized_price ) ? $sanitized_price : 0 ) * ( isset( $order_item->quantity ) ? intval( $order_item->quantity ) : 1 );

	foreach ( $selected_options as $selected_option ) { 

		$option_price = is_numeric( str_replace( ',', '.', $ordering_options[ $selected_option ]['cost'] ) ) ? str_replace( ',', '.', $ordering_options[ $selected_option ]['cost'] ) : 0;
		$price += $option_price * ( isset( $order_item->quantity ) ? intval( $order_item->quantity) : 1 ); 
	}

	return apply_filters( 'fdm_admin_price_value', $price, $order_item );
}


/**
 * Check to see whether an order's status should be updated when the shortcode loads
 * @since 2.1
 */
function fdm_possible_order_status_update() {

	if ( isset( $_GET['fdm_action'] ) and $_GET['fdm_action'] == 'update_status' and current_user_can( 'manage_fdm_orders' ) ) {
		
		$id 		= intval( $_GET['order_id'] );
		$status 	= sanitize_text_field( $_GET['status'] );

		$order_statuses = fdm_get_order_statuses();

		$order_data = get_post_meta( $id, 'order_data', true );

		if ( array_key_exists( $status, $order_statuses ) ) {
			$post_id = wp_update_post( array( 'ID' => $id, 'post_status' => $status ) );

			if ( $post_id ) {
				echo '<div class="fdm-post-status-update">';
				echo __( 'Order status has been set to ', 'food-and-drink-menu' ) . $order_statuses[ $status ]['label'];
				echo '</div>';
			}
		}
		else {
			echo '<div class="fdm-post-status-update">';
			echo __( 'Order status could not be updated. Please make sure you\'re logged in and that the status exists.', 'food-and-drink-menu' );
			echo '</div>';
		}	
	}
	elseif ( isset( $_GET['fdm_action'] ) and $_GET['fdm_action'] == 'update_status' ) {
		echo '<div class="fdm-post-status-update">';
		echo __( 'You do not have permission to update the order\'s status. Please make sure you\'re logged in.', 'food-and-drink-menu' );
		echo '</div>';
	}
}

/**
 * Creates a set of filterable order statuses for orders created by the plugin
 * @since 2.1
 */
function fdm_get_order_statuses() {

	$order_statuses = array( 
		'fdm_order_received' => array(
			'label' => __( 'Received', 'food-and-drink-menu' ),
			'value' => 25,
		),
		'fdm_order_accepted' => array(
			'label' => __( 'Accepted', 'food-and-drink-menu' ),
			'value' => 50,
		),
		'fdm_order_preparing' => array(
			'label' => __( 'Preparing', 'food-and-drink-menu' ),
			'value' => 75,
		),
		'fdm_order_ready' => array(
			'label' => __( 'Ready', 'food-and-drink-menu' ),
			'value' => 100,
		)
	);

	return apply_filters( 'fdm_order_statuses', $order_statuses );
}

if ( ! function_exists( 'fdm_decode_infinite_table_setting' ) ) {
function fdm_decode_infinite_table_setting( $values ) {

	if ( empty( $values ) ) { return array(); }
	
	return is_array( json_decode( html_entity_decode( $values ) ) ) ? json_decode( html_entity_decode( $values ) ) : array();
}
}

// Temporary addition, so that versions of WP before 5.3.0 are supported
if ( ! function_exists( 'wp_timezone') ) {
	function wp_timezone() {
		$timezone_string = get_option( 'timezone_string' );
 
    	if ( ! $timezone_string ) {
        	$offset  = (float) get_option( 'gmt_offset' );
    		$hours   = (int) $offset;
    		$minutes = ( $offset - $hours );

    		$sign      = ( $offset < 0 ) ? '-' : '+';
    		$abs_hour  = abs( $hours );
    		$abs_mins  = abs( $minutes * 60 );
    		$timezone_string = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
    	}

    	return new DateTimeZone( $timezone_string );
	}
}

/**
 * Hide add to cart buttons and the cart
 * @since 2.4.15
 */
function fdm_hide_ordering_features( $classes ) {

	$classes[] = 'fdm-hide-ordering-features';

	return $classes;
}