<?php

/**
 * Class for adding the ordering on the front end.
 *
 * @since 2.1
 */

class fdmViewOrderingSidescreen extends fdmView {

	/**
	 * Initialize the class
	 * @since 2.1
	 */
	public function __construct( $args ) {

		// Parse the values passed
		$this->parse_args( $args );
	}

	/**
	 * Render the view and enqueue required stylesheets
	 * @since 2.1
	 */
	public function render() {
		global $fdm_controller;

		// Gather data if it's not already set
		$this->load_ordering();

		// Add any dependent stylesheets or javascript
		$this->enqueue_assets();

		// Define the classes for this section
		$this->set_classes();

		// Capture output
		ob_start();
		$this->add_custom_sidescreen_styling();
		$template = $this->find_template( 'menu-ordering-sidescreen' );
		if ( $template ) {
			include( $template );
		}
		$output = ob_get_clean();

		return apply_filters( 'fdm_menu_ordering_sidescreen_output', $output, $this );
	}


	/**
	 * Load section data
	 * @since 2.1
	 */
	public function load_ordering() {
		global $fdm_controller;

		// figure out when ordering ends here
		
		do_action( 'fdm_load_ordering_sidescreen', $this );
	}

	/**
	 * Add class 'fdm-hidden' if no tax rate is set
	 * @since 2.5.1
	 */
	public function maybe_add_hidden_to_tax_row_item() {
		global $fdm_controller;

		return empty( $fdm_controller->settings->get_setting( 'ordering-tax-rate' ) ) ? ' class="fdm-hidden"' : '';
	}

	/**
	 * Set the menu section css classes
	 * @since 2.1
	 */
	public function set_classes( $classes = array() ) {
		global $fdm_controller;
		
		$classes = array_merge(
			$classes,
			array(
				'fdm-ordering-sidescreen',
				'fdm-hidden',
				'cart-location-' . $fdm_controller->settings->get_setting( 'fdm-order-cart-location' ),
				'cart-style-' . $fdm_controller->settings->get_setting( 'fdm-order-cart-style' ),
				'fdm-style-' . $fdm_controller->settings->get_setting('fdm-pro-style')
			)
		);

		$this->classes = apply_filters( 'fdm_menu_ordering_sidescreen_classes', $classes, $this );
	}

	public function add_custom_sidescreen_styling() {
		global $fdm_controller;

		echo '<style>';
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-background-color' ) != '' ) { echo '.fdm-add-to-cart-button, .fdm-options-add-to-cart-button { border-color: ' . $fdm_controller->settings->get_setting('fdm-styling-add-to-cart-background-color') . ' !important; color: ' . $fdm_controller->settings->get_setting('fdm-styling-add-to-cart-background-color') . ' !important; } .fdm-add-to-cart-button:hover, .fdm-options-add-to-cart-button:hover { background: ' . $fdm_controller->settings->get_setting('fdm-styling-add-to-cart-background-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-background-color' ) != '' ) { echo '.fdm-menu-ordering .fdm-add-to-cart-button, .fdm-menu-ordering .fdm-options-add-to-cart-button { background: ' . $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-background-color' ) . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-background-hover-color' ) != '' ) { echo '.fdm-menu-ordering .fdm-add-to-cart-button:hover, .fdm-menu-ordering .fdm-options-add-to-cart-button:hover { background: ' . $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-background-hover-color' ) . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-text-non-hover-color' ) != '' ) { echo '.fdm-menu-ordering .fdm-add-to-cart-button, .fdm-menu-ordering .fdm-options-add-to-cart-button { color: ' . $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-text-non-hover-color' ) . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-text-color' ) != '' ) { echo '.fdm-add-to-cart-button:hover, .fdm-options-add-to-cart-button:hover { color: ' . $fdm_controller->settings->get_setting('fdm-styling-add-to-cart-text-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-text-color' ) != '' ) { echo '.fdm-menu-ordering .fdm-add-to-cart-button:hover, .fdm-menu-ordering .fdm-options-add-to-cart-button:hover { color: ' . $fdm_controller->settings->get_setting( 'fdm-styling-add-to-cart-text-color' ) . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-shopping-cart-accent-color' ) != '' ) { echo '#fdm-ordering-sidescreen-header { background: ' . $fdm_controller->settings->get_setting('fdm-styling-shopping-cart-accent-color') . ' !important; } .fdm-clear-cart-button { border-color: ' . $fdm_controller->settings->get_setting('fdm-styling-shopping-cart-accent-color') . ' !important; color: ' . $fdm_controller->settings->get_setting('fdm-styling-shopping-cart-accent-color') . ' !important; } .fdm-clear-cart-button:hover { background: ' . $fdm_controller->settings->get_setting('fdm-styling-shopping-cart-accent-color') . ' !important; color: #fff !important; }'; }
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-order-progress-color' ) != '' ) { echo '.fdm-order-progress-status[data-value="25"] { background: linear-gradient(90deg, ' . $fdm_controller->settings->get_setting('fdm-styling-order-progress-color') . ' 25%, transparent 25%) !important; } .fdm-order-progress-status[data-value="50"] { background: linear-gradient(90deg, ' . $fdm_controller->settings->get_setting('fdm-styling-order-progress-color') . ' 50%, transparent 50%) !important; } .fdm-order-progress-status[data-value="75"] { background: linear-gradient(90deg, ' . $fdm_controller->settings->get_setting('fdm-styling-order-progress-color') . ' 75%, transparent 75%) !important; } .fdm-order-progress-status[data-value="100"] { background: ' . $fdm_controller->settings->get_setting('fdm-styling-order-progress-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting( 'fdm-styling-order-progress-border-color' ) != '' ) { echo '.fdm-order-progress-status { border-color: ' . $fdm_controller->settings->get_setting('fdm-styling-order-progress-border-color') . ' !important; }'; }
		echo  '</style>';
	}

	function enqueue_assets() {
		global $fdm_controller;

		wp_enqueue_script( 'fdm-ordering-js', FDM_PLUGIN_URL . '/assets/js/fdm-ordering-js.js', array( 'jquery' ), FDM_VERSION, true );

		$singular_text 	= esc_html( $this->get_label( 'label-item-in-cart' ) );
		$plural_text 	= esc_html( $this->get_label( 'label-items-in-cart' ) );

		$price_prefix = ( $fdm_controller->settings->get_setting('fdm-currency-symbol-location') == 'before' ? $fdm_controller->settings->get_setting('fdm-currency-symbol') : '' );
		$price_suffix = ( $fdm_controller->settings->get_setting('fdm-currency-symbol-location') == 'after' ? $fdm_controller->settings->get_setting('fdm-currency-symbol') : '' );
		
		$fdm_ordering_data = array(
			'singular_text' 	=> $singular_text,
			'plural_text' 		=> $plural_text,
			'price_prefix' 		=> $price_prefix,
			'price_suffix'		=> $price_suffix,
			'minimum_order' 	=> $fdm_controller->settings->get_setting( 'fdm-ordering-minimum-order' ),
			'tax_rate' 			=> $fdm_controller->settings->get_setting( 'ordering-tax-rate' ),
			'cart_location'		=> $fdm_controller->settings->get_setting( 'fdm-order-cart-location' ),
			'enable_payment'		=> $fdm_controller->settings->get_setting( 'enable-payment' ),
			'payment_optional'	=> $fdm_controller->settings->get_setting( 'payment-optional' )
		);
		wp_localize_script( 'fdm-ordering-js', 'fdm_ordering_data', $fdm_ordering_data );

		wp_enqueue_style('dashicons');

		wp_enqueue_style( 'fdm-ordering-css', FDM_PLUGIN_URL . '/assets/css/fdm-ordering.css', array(), FDM_VERSION );
	}

}
