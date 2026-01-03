<?php

/**
 * Class for adding the ordering popup on the front end.
 *
 * @since 2.1
 */

class fdmViewOrderingPopup extends fdmView {

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
		$template = $this->find_template( 'menu-ordering-popup' );
		if ( $template ) {
			include( $template );
		}
		$output = ob_get_clean();

		return apply_filters( 'fdm_menu_ordering_popup_output', $output, $this );
	}


	/**
	 * Load section data
	 * @since 2.1
	 */
	public function load_ordering() {
		global $fdm_controller;
		
		do_action( 'fdm_load_ordering_popup', $this );
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
				'fdm-ordering-popup',
				'fdm-hidden',
				'fdm-style-' . $fdm_controller->settings->get_setting('fdm-pro-style')
			)
		);

		$this->classes = apply_filters( 'fdm_menu_ordering_popup_classes', $classes, $this );
	}

	function enqueue_assets() {
		global $fdm_controller;

		wp_enqueue_script( 'fdm-ordering-js', FDM_PLUGIN_URL . '/assets/js/fdm-ordering-js.js', array( 'jquery' ), FDM_VERSION, true );
		wp_enqueue_style( 'fdm-ordering-css', FDM_PLUGIN_URL . '/assets/css/fdm-ordering.css', array(), FDM_VERSION );

		$fdm_ordering_popup_data = array(
			'nonce'				=> wp_create_nonce( 'fdm-ordering' ),
			'price_text' 		=>  esc_html( $this->get_label( 'label-ordering-price' ) ),
			'additional_prices' => $fdm_controller->settings->get_setting( 'fdm-ordering-additional-prices' ),
		);

		wp_localize_script( 'fdm-ordering-js', 'fdm_ordering_popup_data', $fdm_ordering_popup_data );
	}

}
