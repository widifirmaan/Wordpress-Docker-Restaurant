<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmPatterns' ) ) {
/**
 * Class to create, edit and display block patterns for the block-based theme editor
 *
 * @since 2.3.9
 */
class fdmPatterns {

	/**
	 * Add hooks
	 * @since 2.3.9
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'fdm_add_pattern_category' ) );
		add_action( 'init', array( $this, 'fdm_add_patterns' ) );
	}

	/**
	 * Register block patterns
	 * @since 2.3.9
	 */
	public function fdm_add_patterns() {

		$block_patterns = array(
			'menu',
			'menu-no-sidebar',
			'menu-section',
			'menu-section-just-items',
			'menu-item',
			'menu-items-three',
			'menu-items-just-image-three',
			'menu-items-image-title-three',
		);
	
		foreach ( $block_patterns as $block_pattern ) {
			$pattern_file = FDM_PLUGIN_DIR . '/includes/patterns/' . $block_pattern . '.php';
	
			register_block_pattern(
				'food-and-drink-menu/' . $block_pattern,
				require $pattern_file
			);
		}
	}

	/**
	 * Create a new category of block patterns to hold our pattern(s)
	 * @since 2.3.9
	 */
	public function fdm_add_pattern_category() {
		
		register_block_pattern_category(
			'fdm-block-patterns',
			array(
				'label' => __( 'Five Star Restaurant Menu', 'food-and-drink-menu' )
			)
		);
	}
}
} // endif
