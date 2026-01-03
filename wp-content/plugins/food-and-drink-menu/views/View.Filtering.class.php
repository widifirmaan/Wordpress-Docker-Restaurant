<?php

/**
 * Class for adding the filtering on the front end.
 *
 * @since 2.0
 */

class fdmViewFiltering extends fdmView {

	public $text_search = array();
	public $enable_price_filtering = false;
	public $price_filtering_type = 'textbox'; 
	public $enable_sorting = false;
	public $sorting_types = array();

	// the menu being displayed
	public $menu;

	/**
	 * Initialize the class
	 * @since 2.0
	 */
	public function __construct( $args ) {

		// Parse the values passed
		$this->parse_args( $args );
	}

	/**
	 * Render the view and enqueue required stylesheets
	 * @since 2.0
	 */
	public function render() {
		global $fdm_controller;

		// Gather data if it's not already set
		$this->load_filtering();

		if ( empty( $this->text_search ) and ! $this->enable_price_filtering and ! $this->enable_sorting )  {
			return;
		}

		// Add any dependent stylesheets or javascript
		$this->enqueue_assets();

		// Define the classes for this section
		$this->set_classes();

		// Capture output
		ob_start();
		$template = $this->find_template( 'menu-filtering' );
		if ( $template ) {
			include( $template );
		}
		$output = ob_get_clean();

		return apply_filters( 'fdm_menu_filtering_output', $output, $this );
	}


	/**
	 * Load section data
	 * @since 2.0
	 */
	public function load_filtering() {
		global $fdm_controller;

		$this->text_search = $fdm_controller->settings->get_setting( 'fdm-text-search' );
		$this->enable_price_filtering = $fdm_controller->settings->get_setting( 'fdm-enable-price-filtering' );
		$this->price_filtering_type = $fdm_controller->settings->get_setting( 'fdm-price-filtering-type' );
		$this->enable_sorting = $fdm_controller->settings->get_setting( 'fdm-enable-sorting' );
		$this->sorting_types = $fdm_controller->settings->get_setting( 'fdm-item-sorting' );
		
		do_action( 'fdm_load_filtering', $this );
	}

	/**
	 * Set the menu section css classes
	 * @since 2.0
	 */
	public function set_classes( $classes = array() ) {
		
		$classes = array_merge(
			$classes,
			array(
				'fdm-filtering'
			)
		);

		$this->classes = apply_filters( 'fdm_menu_filtering_classes', $classes, $this );
	}

	function enqueue_assets() {
		global $fdm_controller;

		$deps = array( 'jquery' );

		if ( $fdm_controller->settings->get_setting( 'fdm-price-filtering-type' ) == 'slider' ) { 

			wp_enqueue_style( 'ewd-urp-jquery-ui', FDM_PLUGIN_URL . '/assets/css/jquery-ui.min.css', FDM_VERSION );
			
			wp_enqueue_script( 'jquery-ui-slider' );

			$deps[] = 'jquery-ui-slider'; 
		}

		wp_enqueue_script( 'fdm-filtering-js', FDM_PLUGIN_URL . '/assets/js/fdm-filtering-js.js', array( 'jquery' ), FDM_VERSION, true );
	}

}
