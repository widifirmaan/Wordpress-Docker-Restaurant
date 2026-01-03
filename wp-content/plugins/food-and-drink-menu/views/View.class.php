<?php

/**
 * Base class for any view requested on the front end.
 *
 * @since 1.1
 */
class fdmView extends fdmBase {

	/**
	 * Post type to render
	 */
	public $post_type = null;

	/**
	 * Map types of content to the template which will render them
	 */
	public $content_map = array(
		'title'							 => 'content/title',
		'content'						 => 'content/content',
		'price'							 => 'content/price',
		'cart_price'				 => 'content/cart_price',
		'cart_header'				 => 'content/cart_header',
		'image'							 => 'content/image',
		'custom_fields' 		 => 'content/custom_fields',
		'related_items' 		 => 'content/related_items',
		'related_item_image' => 'content/related_item_image',
		'related_item_title' => 'content/related_item_title',
		'related_item_price' => 'content/related_item_price',
		'ordering' 					 => 'content/add_to_cart',
		'order_options'			 => 'content/order_options',
		'cart_quantity'			 => 'content/cart_quantity'
	);

	/*
	 * Tags/elements which can be printed
	 */
	public $allowed_tags = array();
	
	/**
	 * Default menu layout
	 */
	public $layout = 'classic';

	/**
	 * Default menu style
	 */
	public $style = 'base';

	/**
	 * Selected prostyle, if any
	 */
	public $menu_prostyle;

	// Locations that should be searched for templates
	public $template_dirs;

	// Default labels, used a fallbacks if no admin inputted label exists
	public $label_defaults = array();

	// The classes that should be added to the main form div
	public $classes;

	/**
	 * Initialize the class
	 * @since 1.1
	 */
	public function __construct( $args ) {

		// Parse the values passed
		$this->parse_args( $args );

		$this->conditional_content_map();
		
		// Filter the content map so addons can customize what and how content
		// is output. Filters are specific to each view, so for this base view
		// you would use the filter 'fdm_content_map_fdmView'
		$this->content_map = apply_filters( 'fdm_content_map_' . get_class( $this ), $this->content_map );

	}

	/**
	 * Add new item data to the content map (points to templates)
	 * @since 2.0
	 */
	function conditional_content_map() {
		global $fdm_controller;
	 	
		// Source
		$sources_permissions = $fdm_controller->permissions->check_permission( 'sources' );
		if ( ! $fdm_controller->settings->get_setting('fdm-disable-src') and $sources_permissions ) {
			$this->content_map['source'] = 'content/source';
		}
	
		// Menu Item Flags
		$flags_permissions = $fdm_controller->permissions->check_permission( 'flags' );
		if ( ! $fdm_controller->settings->get_setting('fdm-disable-menu-item-flags') && $flags_permissions ) {
			$this->content_map['flags'] = 'content/item-flags';
		}
	
		// Discounted price
		$discounts_permissions = $fdm_controller->permissions->check_permission( 'discounts' );
		if (! $fdm_controller->settings->get_setting('fdm-disable-price') && empty( $fdm_controller->settings->get_setting('fdm-disable-price-discounted') ) && $discounts_permissions ) {
			$this->content_map['price'] = 'content/price-discount';
		}
	
		// Specials
		$specials_permissions = $fdm_controller->permissions->check_permission( 'specials' );
		if ( ! $fdm_controller->settings->get_setting('fdm-disable-specials') && $specials_permissions) {
			$this->content_map['special'] = 'content/special';
		}

		// Ordering
		$ordering_permissions = $fdm_controller->permissions->check_permission( 'ordering' );
		if ( $fdm_controller->settings->get_setting('fdm-enable-ordering') && $ordering_permissions) {
			$this->content_map['ordering'] = 'content/add_to_cart';
		}
	}

	/**
	 * Render the view and enqueue required stylesheets
	 *
	 * @note This function should always be overridden by an extending class
	 * @since 1.1
	 */
	public function render() {
		$this->set_error(
			array( 
				'type'		=> 'render() called on wrong class'
			)
		);
	}

	/**
	 * Load a template file for views
	 *
	 * First, it looks in the current theme's /fdm-templates/ directory. Then it
	 * will check a parent theme's /fdm-templates/ directory. If nothing is found
	 * there, it will retrieve the template from the plugin directory.

	 * @since 1.1
	 * @param string template Type of template to load (eg - menu, menu-item)
	 */
	function find_template( $template ) {

		$this->template_dirs = array(
			get_stylesheet_directory() . '/' . FDM_TEMPLATE_DIR . '/',
			get_template_directory() . '/' . FDM_TEMPLATE_DIR . '/',
			FDM_PLUGIN_DIR . '/' . FDM_TEMPLATE_DIR . '/'
		);
		
		$this->template_dirs = apply_filters( 'fdm_template_directories', $this->template_dirs );

		if ( isset( $this->layout ) && $this->layout != 'classic' ) {
			$template .= '-' . $this->layout;
		}

		foreach ( $this->template_dirs as $dir ) {
			if ( file_exists( $dir . $template . '.php' ) ) {
				return $dir . $template . '.php';
			}
		}

		return false;
	}

	/**
	 * Enqueue stylesheets
	 */
	public function enqueue_assets() {

		global $fdm_controller;

		if ( $fdm_controller->settings->get_setting( 'fdm-style' ) == 'none' ) { return; }

		$enqueued = false;
		if ( $this->style == 'prostyles' ) {
			foreach ( $fdm_controller->prostyles as $style ) {
				if ( $fdm_controller->settings->get_setting('fdm-pro-style') == $style->id ) {
					$style->enqueue_assets();
					$enqueued = true;
				}
			}
		}
		else {
			foreach ( $fdm_controller->styles as $style ) {
				if ( $this->style == $style->id ) {
					$style->enqueue_assets();
					$enqueued = true;
				}
			}
		}
		
		// Fallback to basic style if the selected style does not exist
		// This can happen if they have a custom style defined in a theme, then
		// they switch themes. The setting will still be the custom style, but
		// no entry in $fdm_controller->styles will exist for that style.
		if ( !$enqueued && isset( $fdm_controller->styles['base'] ) ) {
			$fdm_controller->styles['base']->enqueue_assets();
		}
	}

	/**
	 * Retrieve a post in a filter-friendly way
	 *
	 * This function stands in for the get_post() function with a query
	 * that can be filtered by plugins like WPML. It also resets a
	 * view's ID after making the call so that any further requests for
	 * post meta point to the appropriate post.
	 */
	public function get_this_post() {

		if ( empty( $this->id ) || empty( $this->post_type ) ) {
			return;
		}

		$p = new WP_Query(
			array(
				'p' => $this->id,
				'post_type' => $this->post_type
			)
		);

		while ( $p->have_posts() ) {
			$p->next_post();
			$this->post = $p->post;
		}

		wp_reset_postdata();

		// Update the ID if it's been modified by WPML or
		// other query-modifying plugins
		if ( ! empty( $this->post) and ! empty( $this->post->ID ) and $this->post->ID !== $this->id ) {
			$this->id = $this->post->ID;
		}

	}

	/**
	 * Sets the tags/elements which can be printed in this view,
	 * uses the wp_kses function to filter content
	 * @since 2.3.1
	 */
	public function set_allowed_tags() {

		$allowed_tags = wp_kses_allowed_html( 'post' );

		$allowed_atts = array(
			'id' => true,
			'name' => true,
			'type' => true,
			'class' => true,
			'style' => true,
			'value' => true,
			'method' => true,
			'action' => true,
			'data-*' => true,
			'min' => true,
			'max' => true,
			'step' => true,
			'list' => true,
			'required' => true,
			'readonly' => true,
			'placeholder' => true,
		);

		$allowed_tags = array_merge(
			$allowed_tags,
			array(
				'form'  	=> $allowed_atts,
				'input' 	=> $allowed_atts,
				'select'	=> $allowed_atts,
				'option'	=> $allowed_atts,
				'button'	=> $allowed_atts,
				'style'		=> $allowed_atts,
			)
		);

		$this->allowed_tags = apply_filters( 'fdm_kses_allowed_tags', $allowed_tags );
	}

	/**
	 * Returns a specified plugin option
	 * @since 2.3.1
	 */
	public function get_option( $option_name ) {
		global $fdm_controller;

		return $fdm_controller->settings->get_setting( $option_name );
	}

	public function get_label( $label_name ) {
		global $fdm_controller;

		if ( empty( $this->label_defaults ) ) { $this->set_label_defaults(); }

		return ! empty( $fdm_controller->settings->get_setting( $label_name ) ) ? $fdm_controller->settings->get_setting( $label_name ) : $this->label_defaults[ $label_name ];
	}

	public function set_label_defaults() {

		$this->label_defaults = array(
			'label-custom-fields'					=> __( 'Custom Fields', 'food-and-drink-menu' ),
			'label-related-items'					=> __( 'Related Items', 'food-and-drink-menu' ),
			'label-on-sale'							=> __( 'On Sale', 'food-and-drink-menu' ),
			'label-special-offer'					=> __( 'Special Offer', 'food-and-drink-menu' ),
			'label-featured'						=> __( 'Featured', 'food-and-drink-menu' ),
			'label-sold-out'						=> __( 'Sold Out', 'food-and-drink-menu' ),
			'label-sidebar-expand-button'			=> __( 'View Sections', 'food-and-drink-menu' ),

			'label-filtering'						=> __( 'Filtering', 'food-and-drink-menu' ),
			'label-search'							=> __( 'Search', 'food-and-drink-menu' ),
			'label-search-items'					=> __( 'Search Items...', 'food-and-drink-menu' ),
			'label-filtering-price'					=> __( 'Price', 'food-and-drink-menu' ),
			'label-sorting'							=> __( 'Sorting', 'food-and-drink-menu' ),
			'label-name-asc'						=> __( 'Name (A -> Z)', 'food-and-drink-menu' ),
			'label-name-desc'						=> __( 'Name (Z -> A)', 'food-and-drink-menu' ),
			'label-price-asc'						=> __( 'Price (Ascending)', 'food-and-drink-menu' ),
			'label-price-desc'						=> __( 'Price (Descending)', 'food-and-drink-menu' ),
			'label-date-added-asc'					=> __( 'Date Added (Ascending)', 'food-and-drink-menu' ),
			'label-date-added-desc'					=> __( 'Date Added (Descending)', 'food-and-drink-menu' ),
			'label-section-asc'						=> __( 'Section (Ascending)', 'food-and-drink-menu' ),
			'label-section-desc'					=> __( 'Section (Descending)', 'food-and-drink-menu' ),

			'label-add-to-cart'						=> __( 'Add to Cart', 'food-and-drink-menu' ),
			'label-discount'						=> __( 'Discount:', 'food-and-drink-menu' ),
			'label-remove'							=> __( 'Remove', 'food-and-drink-menu' ),
			'label-ordering-price'					=> __( 'Price:', 'food-and-drink-menu' ),
			'label-order-item-details'				=> __( 'Order Item Details', 'food-and-drink-menu' ),
			'label-item-note'						=> __( 'Item Note', 'food-and-drink-menu' ),
			'label-confirm-details'					=> __( 'Confirm Details', 'food-and-drink-menu' ),
			'label-order-progress'					=> __( 'Order Progress', 'food-and-drink-menu' ),
			'label-order-summary'					=> __( 'Order Summary', 'food-and-drink-menu' ),
			'label-your-order'						=> __( 'Your Order', 'food-and-drink-menu' ),
			'label-item-in-cart'					=> __( 'Item in Your Cart', 'food-and-drink-menu' ),
			'label-items-in-cart'					=> __( 'Items in Your Cart', 'food-and-drink-menu' ),
			'label-item-s-in-cart'					=> __( 'Item(s) in Your Cart', 'food-and-drink-menu' ),
			'label-quantity'						=> __( 'Quantity', 'food-and-drink-menu' ),
			'label-clear'							=> __( 'Clear', 'food-and-drink-menu' ),
			'label-tax'								=> __( 'Tax', 'food-and-drink-menu' ),
			'label-total'							=> __( 'Total', 'food-and-drink-menu' ),
			'label-check-out'						=> __( 'Check Out', 'food-and-drink-menu' ),
			'label-name'							=> __( 'Name', 'food-and-drink-menu' ),
			'label-email'							=> __( 'Email', 'food-and-drink-menu' ),
			'label-phone'							=> __( 'Phone', 'food-and-drink-menu' ),
			'label-order-note'						=> __( 'Order Note', 'food-and-drink-menu' ),
			'label-pay-in-store'					=> __( 'Pay in Store', 'food-and-drink-menu' ),
			'label-pay-online'						=> __( 'Pay Online', 'food-and-drink-menu' ),
			'label-submit-order'					=> __( 'Submit Order', 'food-and-drink-menu' ),
			'label-add-another-item'				=> __( '+ Add another item', 'food-and-drink-menu' ),
			'label-pay-via-paypal'					=> __( 'Pay via PayPal', 'food-and-drink-menu' ),
			'label-deposit-placing-hold'			=> __( 'We are only placing a hold for the above amount on your payment instrument. You will be charged later.', 'food-and-drink-menu' ),
			'label-card-number'						=> __( 'Card Number', 'food-and-drink-menu' ),
			'label-cvc'								=> __( 'CVC', 'food-and-drink-menu' ),
			'label-expiration'						=> __( 'Expiration (MM/YYYY)', 'food-and-drink-menu' ),
			'label-pay-now'							=> __( 'Pay Now', 'food-and-drink-menu' ),
		);
	}

	public function add_custom_styling() {
		global $fdm_controller;

		echo '<style>';
			if ( $fdm_controller->settings->get_setting('fdm-ordering-style-accent-color') != '' ) { echo '.fdm-menu-ordering .fdm-add-to-cart-button, .fdm-menu-ordering .fdm-options-add-to-cart-button, .fdm-style-ordering #fdm-ordering-popup-submit button, .cart-location-bottom .fdm-continue-shopping-button, .cart-location-bottom .fdm-clear-cart-button, .cart-location-bottom #fdm-order-submit-button, .cart-location-bottom #fdm-order-payment-form-div #paypal-submit, .cart-location-bottom #fdm-order-payment-form-div #stripe-submit, .fdm-ordering-bottom-bar-checkout-button { background: ' . $fdm_controller->settings->get_setting('fdm-ordering-style-accent-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-ordering-style-accent-color') != '' ) { echo '.fdm-ordering-bottom-bar-toggle-inside { border-color: ' . $fdm_controller->settings->get_setting('fdm-ordering-style-accent-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-ordering-style-accent-color') != '' ) { echo '.fdm-ordering-bottom-bar-toggle .dashicons { color: ' . $fdm_controller->settings->get_setting('fdm-ordering-style-accent-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-ordering-style-accent-hover-color') != '' ) { echo '.fdm-menu-ordering .fdm-add-to-cart-button:hover, .fdm-menu-ordering .fdm-options-add-to-cart-button:hover, .fdm-style-ordering #fdm-ordering-popup-submit button:hover, .cart-location-bottom .fdm-continue-shopping-button:hover, .cart-location-bottom .fdm-clear-cart-button:hover, .cart-location-bottom #fdm-order-submit-button:hover, .cart-location-bottom #fdm-order-payment-form-div #paypal-submit:hover, .cart-location-bottom #fdm-order-payment-form-div #stripe-submit:hover, .fdm-ordering-bottom-bar-checkout-button:hover { background: ' . $fdm_controller->settings->get_setting('fdm-ordering-style-accent-hover-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-section-title-font-family') != '' ) { echo '.fdm-section-header h3 { font-family: \'' . $fdm_controller->settings->get_setting('fdm-styling-section-title-font-family') . '\' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-section-title-font-size') != '' ) { echo '.fdm-section-header h3 { font-size: ' . $fdm_controller->settings->get_setting('fdm-styling-section-title-font-size') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-section-title-color') != '' ) { echo '.fdm-section-header h3 { color: ' . $fdm_controller->settings->get_setting('fdm-styling-section-title-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-name-font-family') != '' ) { echo '.fdm-item-title { font-family: \'' . $fdm_controller->settings->get_setting('fdm-styling-item-name-font-family') . '\' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-name-font-size') != '' ) { echo '.fdm-item-title { font-size: ' . $fdm_controller->settings->get_setting('fdm-styling-item-name-font-size') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-name-color') != '' ) { echo '.fdm-item-title { color: ' . $fdm_controller->settings->get_setting('fdm-styling-item-name-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-description-font-family') != '' ) { echo '.fdm-item-content p, .fdm-item-price { font-family: \'' . $fdm_controller->settings->get_setting('fdm-styling-item-description-font-family') . '\' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-description-font-size') != '' ) { echo '.fdm-item-content p { font-size: ' . $fdm_controller->settings->get_setting('fdm-styling-item-description-font-size') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-description-color') != '' ) { echo '.fdm-item-content p { color: ' . $fdm_controller->settings->get_setting('fdm-styling-item-description-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-price-font-size') != '' ) { echo '.fdm-item-price { font-size: ' . $fdm_controller->settings->get_setting('fdm-styling-item-price-font-size') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-price-color') != '' ) { echo '.fdm-item-price { color: ' . $fdm_controller->settings->get_setting('fdm-styling-item-price-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-image-width') != '' ) { echo '.fdm-item-image { width: ' . $fdm_controller->settings->get_setting('fdm-styling-image-width') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-image-width') != '' ) { echo '.fdm-item-has-image .fdm-item-panel p { padding-left: calc(' . $fdm_controller->settings->get_setting('fdm-styling-image-width') . ' + 2%) !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-image-border-size') != '' ) { echo '.fdm-item-image { border-width: ' . $fdm_controller->settings->get_setting('fdm-styling-image-border-size') . 'px !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-image-border-color') != '' ) { echo '.fdm-item-image { border-color: ' . $fdm_controller->settings->get_setting('fdm-styling-image-border-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-separating-line-size') != '' ) { echo '.fdm-section-header { border-bottom-width: ' . $fdm_controller->settings->get_setting('fdm-styling-separating-line-size') . 'px !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-separating-line-color') != '' ) { echo '.fdm-section-header { border-bottom-color: ' . $fdm_controller->settings->get_setting('fdm-styling-separating-line-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-filtering-font-family') != '' ) { echo 'h3.fdm-filtering-header, .fdm-filtering-label { font-family: \'' . $fdm_controller->settings->get_setting('fdm-styling-filtering-font-family') . '\' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-filtering-title-font-size') != '' ) { echo 'h3.fdm-filtering-header { font-size: ' . $fdm_controller->settings->get_setting('fdm-styling-filtering-title-font-size') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-filtering-title-color') != '' ) { echo 'h3.fdm-filtering-header { color: ' . $fdm_controller->settings->get_setting('fdm-styling-filtering-title-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-filtering-labels-font-size') != '' ) { echo '.fdm-filtering-label { font-size: ' . $fdm_controller->settings->get_setting('fdm-styling-filtering-labels-font-size') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-filtering-labels-color') != '' ) { echo '.fdm-filtering-label { color: ' . $fdm_controller->settings->get_setting('fdm-styling-filtering-labels-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-font-family') != '' ) { echo '.fdm-menu-sidebar-section-title, .fdm-menu-sidebar-section-description { font-family: \'' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-font-family') . '\' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-title-font-size') != '' ) { echo '.fdm-menu-sidebar-section-title { font-size: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-title-font-size') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-title-color') != '' ) { echo '.fdm-menu-sidebar-section-title { color: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-title-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-title-color') != '' ) { echo '.fdm-sidebar-menu-style-ordering .fdm-menu-sidebar-section-title-selected { border-color: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-title-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-description-font-size') != '' ) { echo '.fdm-menu-sidebar-section-description { font-size: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-description-font-size') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-description-color') != '' ) { echo '.fdm-menu-sidebar-section-description { color: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-description-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-expand-button-background-color') != '' ) { echo '.fdm-sidebar-mobile-expand-button { background: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-expand-button-background-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-expand-button-text-color') != '' ) { echo '.fdm-sidebar-mobile-expand-button { color: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-expand-button-text-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-expand-button-hover-background-color') != '' ) { echo '.fdm-sidebar-mobile-expand-button:hover { background: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-expand-button-hover-background-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-sidebar-expand-button-hover-text-color') != '' ) { echo '.fdm-sidebar-mobile-expand-button:hover { color: ' . $fdm_controller->settings->get_setting('fdm-styling-sidebar-expand-button-hover-text-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-separating-line-size') != '' ) { echo '.fdm-section-header:after { border-bottom-width: ' . $fdm_controller->settings->get_setting('fdm-styling-separating-line-size') . 'px !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-separating-line-color') != '' ) { echo '.fdm-section-header:after { border-bottom-color: ' . $fdm_controller->settings->get_setting('fdm-styling-separating-line-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-display-section-descriptions') != '1' ) { echo '.fdm-section-header p { display: none; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-styling-item-icon-color') != '' ) { echo '.fdm-icon { color: ' . $fdm_controller->settings->get_setting('fdm-styling-item-icon-color') . ' !important; }'; }
			if ( $fdm_controller->settings->get_setting('fdm-details-lightbox') != 'disabled' && $fdm_controller->settings->get_setting('fdm-details-lightbox') != '' ) { echo '.fdm-item-title { cursor: pointer; }'; }
		echo  '</style>';
	}

}
