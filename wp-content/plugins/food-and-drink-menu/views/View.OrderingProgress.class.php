<?php

/**
 * Class for adding the ordering progress on the front end.
 *
 * @since 2.1
 */

class fdmViewOrderingProgress extends fdmView {

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
		$template = $this->find_template( 'menu-ordering-progress' );
		if ( $template ) {
			include( $template );
		}
		$output = ob_get_clean();

		return apply_filters( 'fdm_menu_ordering_progress_output', $output, $this );
	}


	/**
	 * Load section data
	 * @since 2.1
	 */
	public function load_ordering() {
		global $fdm_controller;
		
		do_action( 'fdm_load_ordering_progress', $this );
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
				'fdm-ordering-progress',
				$fdm_controller->orders->get_recent_order_id() ? '' : 'fdm-hidden',
				'fdm-style-' . $fdm_controller->settings->get_setting('fdm-pro-style')
			)
		);

		$this->classes = apply_filters( 'fdm_menu_ordering_progress_classes', $classes, $this );
	}

	function enqueue_assets() {
		wp_enqueue_script( 'fdm-ordering-js', FDM_PLUGIN_URL . '/assets/js/fdm-ordering-js.js', array( 'jquery' ), FDM_VERSION, true );
		wp_enqueue_style( 'fdm-ordering-css', FDM_PLUGIN_URL . '/assets/css/fdm-ordering.css', array(), FDM_VERSION );
	}

}
