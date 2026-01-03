<?php

/**
 * Class for adding the filtering on the front end.
 *
 * @since 2.0
 */

class fdmViewSidebar extends fdmView {

	public $groups;

	// the menu being displayed
	public $menu;

	// pointers
	public $current_section;

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
		$this->load_sidebar();

		// Add any dependent stylesheets or javascript
		$this->enqueue_assets();

		// Define the classes for the sidebar
		$this->set_classes();

		// Capture output
		ob_start();
		$template = $this->find_template( 'menu-sidebar' );
		if ( $template ) {
			include( $template );
		}
		$output = ob_get_clean();

		return apply_filters( 'fdm_menu_sidebar_output', $output, $this );
	}


	/**
	 * Print individual sidebar sections
	 * @since 2.0
	 */
	public function print_sidebar_sections() {
		$output = '';

		foreach ( $this->groups as $group ) {

			foreach ( $group as $section ) {
				
				if ( ! isset( $section->items ) or ! is_array( $section->items ) ) { continue; }

				$this->current_section = $section;

				ob_start();
				$template = $this->find_template( 'menu-sidebar-section' );
				if ( $template ) {
					include( $template );
				}
				$output .= apply_filters( 'fdm_menu_sidebar_section_output', ob_get_clean(), $this->current_section );
			}
		}

		return $output;
	}

	/**
	 * Load section data
	 * @since 2.0
	 */
	public function load_sidebar() {
		
		do_action( 'fdm_load_sidebar', $this );
	}

	/**
	 * Set the menu section css classes
	 * @since 2.0
	 */
	public function set_classes( $classes = array() ) {
		global $fdm_controller;

		$classes = array_merge(
			$classes,
			array(
				'fdm-menu-sidebar-div',
				'fdm-sidebar-display-' . $fdm_controller->settings->get_setting( 'fdm-sidebar' ),
				'fdm-sidebar-mobile-expand-' . $fdm_controller->settings->get_setting('fdm-sidebar-mobile-expand'),
				'fdm-sidebar-menu-style-' . $fdm_controller->settings->get_setting('fdm-pro-style')
			)
		);

		$this->classes = apply_filters( 'fdm_menu_sidebar_classes', $classes, $this );
	}

	function enqueue_assets() {
		global $fdm_controller;

		if ( $fdm_controller->settings->get_setting( 'fdm-sidebar' ) != '1' ) :
			return;
		endif;

		wp_enqueue_style( 'fdm-sidebar-css', FDM_PLUGIN_URL . '/assets/css/sidebar.css', array(), FDM_VERSION );
		wp_enqueue_script( 'fdm-sidebar-js', FDM_PLUGIN_URL . '/assets/js/fdm-sidebar-js.js', array( 'jquery' ), FDM_VERSION, true );
		wp_localize_script(
			'fdm-sidebar-js',
			'fdmFromSettings',
			array(
				'sidebar_click_action' => $fdm_controller->settings->get_setting('fdm-sidebar-click-action'),
				'menu_style' => $fdm_controller->settings->get_setting('fdm-pro-style')
			)
		);
	}

}
