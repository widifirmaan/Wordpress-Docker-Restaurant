<?php

/**
 * Class for any item view requested on the front end.
 *
 * @since 1.1
 */
class fdmViewItem extends fdmView {

	public $section = '';

	/**
	 * Post type to render
	 */
	public $post_type = FDM_MENUITEM_POST_TYPE;

	/**
	 * Which content elements to display for this item
	 */
	public $elements = array( 'title' );

	public $elements_order = array(
		'special',
		'image',
		'title',
		'price',
		'content',
		'ordering',
		'flags',
		'custom_fields',
		'source',
		'related_items'
	);

	/**
	 * Whether or not we're rendering this item on its own or as part of
	 * a menu.
	 */
	public $singular = false;

	/**
	 * Custom fields for this item with their values
	 */
	public $custom_fields = array();

	/**
	 * Holds all of the different prices available for the current item
	 */
	public $prices = array();

	public $min_price = 1000000; 
	public $max_price = 0;

	// Item content
	public $post;
	public $title;
	public $content;
	public $image;
	public $plain_price;
	public $price;
	public $source_name;
	public $flags;
	public $price_discount;
	public $special;
	public $ordering_available;
	public $ordering_options;
	public $special_title;

	// Item order content
	public $selected_options;
	public $note;
	public $quantity;
	public $item_identifier;
	public $selected_price;
	public $order_price;

	public $cart_elements = array( 'title' );
	
	public $cart_elements_order = array(
			'cart_header',
			'image',
			'title',
			'order_options',
			'cart_quantity',
			'cart_price'
	);

	/**
	 * Render the view and enqueue required stylesheets
	 *
	 * @since 1.1
	 */
	public function render() {
		global $fdm_controller;

		if ( !isset( $this->id ) ) {
			return;
		}

		add_filter( 'fdm_menu_item_classes', array( $this, 'fdmp_add_item_classes' ), 10, 2 );

		// Gather data if it's not already set
		if ( !isset( $this->title ) ) {
			$this->load_item();
		}

		// Define css classes to add to this menu item
		$classes = array( 'fdm-item' );

		// 1 is legacy value for lightbox
		if ( in_array( $fdm_controller->settings->get_setting('fdm-details-lightbox'), array( 'lightbox', '1' ) ) ) { 
			$classes[] = 'fdm-item-ajax-open';
		}
		elseif ( 'permalink' == $fdm_controller->settings->get_setting('fdm-details-lightbox') ) { 
			$classes[] = 'fdm-item-newpage-open';
		}

		// Register elements to display
		// Each element is referenced by its variable name (key) and location
		// in the menu item where we want to print it (header, body or footer)
		$elements['title'] = 'body';
		if ( $this->content ) {
			$elements['content'] = 'body';
		}
		if ( isset( $this->image ) ) {
			$elements['image'] = 'body';
			$classes[] = 'fdm-item-has-image';
		}
		if ( isset( $this->price ) && $this->price ) {
			$elements['price'] = 'body';
			$classes[] = 'fdm-item-has-price';
		}
		if ( isset( $this->custom_fields ) and ! empty( $this->custom_fields ) and $this->is_singular()) {
			$elements['custom_fields'] = 'body';
		}
		if ( isset( $this->source_name ) && $this->source_name ) {
			$elements['source'] = 'footer';
		}
		if ( isset( $this->related_items ) and ! empty( $this->related_items ) and $this->is_singular() ) {
			$elements['related_items'] = 'footer';
		}
		if ( isset( $this->special ) && $this->special && $fdm_controller->settings->get_setting('fdm-pro-style') != 'luxe' ) {
			$elements['special'] = 'header';
		}
		if ( isset( $this->special ) && $this->special && $fdm_controller->settings->get_setting('fdm-pro-style') == 'luxe' ) {
			$elements['special'] = 'body';
		}
		if ( isset( $this->price_discount ) && $this->price_discount ) {
			$elements['price'] = 'body';
		}
		if ( isset( $this->flags ) && $this->flags ) {
			$elements['flags'] = 'body';
		}
		if ( isset( $this->ordering_available ) && $this->ordering_available && $fdm_controller->orders->is_open_for_ordering() ) {
			$elements['ordering'] = 'body';
		}

		// Filter the elements and classes
		$this->elements = apply_filters( 'fdm_menu_item_elements', $elements, $this );
		$this->elements_order = apply_filters( 'fdm_menu_item_elements_order', $this->elements_order, $this );
		$this->classes = apply_filters( 'fdm_menu_item_classes', $classes, $this );

		$this->set_allowed_tags();

		// Add any dependent stylesheets or javascript
		$this->enqueue_assets();
		
		// Capture output
		ob_start();
		if ( $this->singular ) {$this->add_custom_styling();}
		$template = $this->find_template( 'menu-item' );
		if ( $template ) {
			include( $template );
		}
		$output = ob_get_clean();

		return apply_filters( 'fdm_menu_item_output', $output, $this );

	}

	/**
	 * Render the item for cart view
	 *
	 * @since 2.1
	 */
	public function cart_render() {
		
		if ( !isset( $this->id ) ) {
			return;
		}

		// Gather data if it's not already set
		if ( ! isset( $this->title ) ) {
			$this->load_item();
		}

		// Gather data if it's not already set
		if ( ! isset( $this->order_price ) ) {
			$this->load_determine_order_price();
		}

		$elements['cart_header'] = 'body';
		$elements['title'] = 'body';
		$elements['order_options'] = 'body';
		if ( isset( $this->image ) ) {
			$elements['image'] = 'body';
			$classes[] = 'fdm-item-has-image';
		}
		if ( isset( $this->price ) && $this->price ) {
			$elements['cart_price'] = 'body';
			$classes[] = 'fdm-item-has-price';
		}
		$elements['cart_quantity'] = 'body';

		$this->cart_elements = apply_filters( 'fdm_cart_menu_item_elements', $elements, $this );
		$this->cart_elements_order = apply_filters( 'fdm_cart_menu_item_elements_order', $this->cart_elements_order, $this );

		$this->set_allowed_tags();

		// Capture output
		ob_start();
		$template = $this->find_template( 'cart-menu-item' );
		if ( $template ) {
			include( $template );
		}
		$output = ob_get_clean();

		return apply_filters( 'fdm_cart_menu_item_output', $output, $this );
	}

	/**
	 * Print each of the menu item elements in the defined order
	 *
	 * @note This function just provides us with a cleaner template
	 * @since 1.1
	 */
	public function print_elements( $location ) {

		$output = '';

		foreach( $this->elements_order as $element ) {
			if ( isset( $this->elements[$element] ) && $this->elements[$element] == $location ) {

				// Load the template for this content type
				$template = $this->find_template( $this->content_map[$element] );

				ob_start();
				if ( $template ) {
					include( $template );
				}
				$element_output = ob_get_clean();

				$output .= apply_filters( 'fdm_element_output_' . $element, $element_output, $this );
			}
		}
		return $output;
	}

/**
	 * Print each of the menu item elements for the menu cart in the defined order
	 *
	 * @note This function just provides us with a cleaner template
	 * @since 2.1
	 */
	public function print_cart_elements( $location ) {

		$output = '';

		foreach( $this->cart_elements_order as $element ) {
			if ( isset( $this->cart_elements[$element] ) && $this->cart_elements[$element] == $location ) {

				// Load the template for this content type
				$template = $this->find_template( $this->content_map[$element] );

				ob_start();
				if ( $template ) {
					include( $template );
				}
				$element_output = ob_get_clean();

				$output .= apply_filters( 'fdm_element_output_cart_' . $element, $element_output, $this );
			}
		}
		return $output;
	}

	/**
	 * Load item data
	 * @since 1.1
	 */
	public function load_item() {
		global $fdm_controller;

		if ( empty( $this->id ) ) {
			return;
		}

		// If no title is set, we need to gather the core post data first
		if ( empty( $this->title ) ) {
			$this->get_data_from_post();
		}

		if ( empty( $this->image ) ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->id ), 'fdm-item-thumb' );
			if ( isset( $image[0] ) ) {
				$this->image = $image[0];
			}
		}

		if ( ! $fdm_controller->settings->get_setting('fdm-disable-price') ) {
			$this->prices = (array) get_post_meta( $this->id, 'fdm_item_price' );

			if ( $fdm_controller->settings->get_setting( 'fdm-enable-price-filtering' ) ) {
				array_walk( $this->prices,  function( $price ) {

					$this->min_price = min( $this->min_price, $price );
					$this->max_price = max( $this->max_price, $price );
				} );
			}

			$this->plain_price = 1 > count( $this->prices ) ? 0 : max( $this->prices );

			array_walk( $this->prices,  function( &$item ) {
                $item = fdm_format_price( $item );
            } );

			// Load a single price string to be compatible with custom templates
			// created before v1.5.
			$this->price = join(
				apply_filters( 'fdm_prices_separator', _x( '/', 'Separator between multiple prices.', 'food-and-drink-menu' ) ),
				$this->prices
			);
		}

		if ( $fdm_controller->settings->get_setting('fdm-related-items') == 'manual') {
			$this->related_items = $this->get_manual_related_items();
		}

		if ( $fdm_controller->settings->get_setting('fdm-related-items') == 'automatic') {
			$this->related_items = $this->get_automatic_related_items();
		}

		$fields = $fdm_controller->settings->get_menu_item_custom_fields();

		$values = get_post_meta( $this->id, '_fdm_menu_item_custom_fields', true );
		if ( ! is_array($values ) ) { $values = array(); }

		if ( ! empty($values) ) {
			foreach ($fields as $field) {
				$field->value = isset( $values[$field->slug] ) ? $values[$field->slug] : '';
				$this->custom_fields[] = $field;
			}
		}

		$sources_permission = $fdm_controller->permissions->check_permission( 'sources' );
	
		// Source
		if ( empty( $fdm_controller->settings->get_setting('fdm-disable-src') ) && $sources_permission && $this->source_name = get_post_meta( $this->id, 'fdm_item_source_name', true ) ) {
			$this->source_desc = get_post_meta( $this->id, 'fdm_item_source_description', true );
			if ( empty( $fdm_controller->settings->get_setting('fdm-disable-src-map') ) && $this->source_address = get_post_meta( $this->id, 'fdm_item_source_address', true ) ) {
				$this->source_zoom = get_post_meta( $this->id, 'fdm_item_source_zoom', true );
			}
	
			if ( isset( $this->source_name ) && $this->source_name ) {
				$this->source_classes = array( 'fdm-src-panel' );
	
				if ( isset( $this->source_address ) && $this->source_address ) {
					$this->source_classes[] = 'fdm-src-has-map';
				}
			}
		}

		$flag_permissions = $fdm_controller->permissions->check_permission( 'flags' );
	
		// Menu Item Flags
		if ( empty( $fdm_controller->settings->get_setting('fdm-disable-menu-item-flags') ) && $flag_permissions ) {
			$this->flags = wp_get_post_terms( $this->id, 'fdm-menu-item-flag', array( "fields" => "all" ) );
			foreach( $this->flags as $flag ) {
				$flag_meta = get_option( "fdm_menu_item_flag_icon_" . $flag->term_id );
				$flag->classes = array( 'fdm-flag', 'fdm-flag-' . esc_attr( $flag->slug ) );
				if ( esc_attr( $flag_meta['fdm_menu_item_flag_icon'] ) != '' ) {
					$flag->classes[] = 'fdm-icon';
					$flag->classes[] = 'fdm-icon-' . esc_attr( $flag_meta['fdm_menu_item_flag_icon'] );
				} else {
					$flag->classes[] = 'fdm-item-flag-text';
					$flag->text_only = true;
				}
			}
		}

		$discount_permissions = $fdm_controller->permissions->check_permission( 'discounts' );
	
		// Discounted price
		if ( empty( $fdm_controller->settings->get_setting('fdm-disable-price') ) && $discount_permissions && empty( $fdm_controller->settings->get_setting('fdm-disable-price-discounted') ) ) {
			$this->price_discount = get_post_meta( $this->id, 'fdm_item_price_discount', true );

			if ( $this->price_discount ) {

				$price = str_replace( ',', '.', preg_replace( '/[^0-9,.]+/', '', $this->price_discount ) );

				$this->min_price = min( $this->min_price, $price );
				$this->max_price = max( $this->max_price, $price );
			}

			if( ! empty($this->price_discount) ) {
				$this->price_discount = fdm_format_price( $this->price_discount );
			}
		}

		$specials_permissions = $fdm_controller->permissions->check_permission( 'specials' );
	
		// Specials
		if ( empty( $fdm_controller->settings->get_setting('fdm-disable-specials') ) && $specials_permissions ) {
			$this->special = get_post_meta( $this->id, 'fdm_item_special', true );
			if ( $this->special == 'none' || !$this->special ) {
				$this->special = false;
			} elseif ( $this->special == 'sold_out' ) {
				$this->special_title = $this->get_label( 'label-sold-out' );
			} elseif ( $this->special == 'sale' ) {
				$this->special_title = $this->get_label( 'label-on-sale' );
			} elseif ( $this->special == 'offer' ) {
				$this->special_title = $this->get_label( 'label-special-offer' );
			} elseif ( $this->special == 'featured' ) {
				$this->special_title = $this->get_label( 'label-featured' );
			}
		}
		else {
			$this->special = false;
		}

		// Ordering
		if ( $fdm_controller->settings->get_setting('fdm-enable-ordering') ) {
			$this->ordering_available = $this->special == 'sold_out' ? false : true; //update once the schedulers are working

			$ordering_options = get_post_meta( $this->id, '_fdm_ordering_options', true );
			$this->ordering_options = is_array( $ordering_options ) ? $ordering_options : array();
		}

		do_action( 'fdm_load_item', $this );
	}

	/**
	 * Retrieves data from a post object if it exists or calls the db for it if
	 * not.
	 *
	 * @note This only retrieves core post data, not metadata. @sa load_item()
	 * @since 1.1
	 */
	public function get_data_from_post() {

		// Get the post data. Use WP_Query() and not get_post()
		// to improve compatibility with WPML
		if ( empty( $this->post ) ) {
			$this->get_this_post();
		}

		if ( !empty( $this->post ) ) {
			$this->title = $this->post->post_title;
			$this->content = do_shortcode( wpautop($this->post->post_content) );

			// Update the ID in case it's been modified by WPML or
			// other query-modifying plugins
			$this->id = $this->post->ID;
		}
	}

	/**
	 * Set if this view is of a single item
	 * @since 2.0
	 */
	public function set_singular($singular) {
		$this->singular = $singular;
	}

	/**
	 * Check if this view is of a single item
	 * @since 1.1
	 */
	public function is_singular() {
		if ( isset( $this->singular ) && $this->singular === true ) {
			return true;
		}
		return false;
	}


	/**
	 * Get random items related to the current item
	 * @since 2.0
	 */
	public function get_automatic_related_items() {
		$sections = wp_get_post_terms( $this->id, 'fdm-menu-section' );

		$section_ids = array();
		foreach ($sections as $section) {
			$section_ids[] = $section->term_id;
		}
		$tax_query = array(
			array(
				'taxonomy' 	=> 'fdm-menu-section',
				'field'		=> 'id',
				'terms'		=> $section_ids
			)
		);

		$section_query = new WP_Query( array(
			'post__not_in' 		=> array($this->id),
			'posts_per_page'	=> '4',
			'tax_query' 		=> $tax_query,
			'orderby'			=> 'rand'
		) );

		return $section_query->posts;
	}

	/**
	 * Get set items related to the current item
	 * @since 2.0
	 */
	public function get_manual_related_items() {
		$related_items = get_post_meta( $this->id, '_fdm_related_items', true );
		if ( ! is_array($related_items) ) { $related_items = array(); }

		$related_posts = array();
		foreach ( $related_items as $related_item ) { 
			if ( ! $related_item ) { continue; }

			$related_posts[] = get_post($related_item); 
		}

		return $related_posts;
	}

	/**
	 * Add CSS classes if new data exists
	 * @since 2.0
	 */
	public function fdmp_add_item_classes( $classes, $item ) {
		global $fdm_controller;
	
		if ( isset( $item->price_discount ) && $item->price_discount ) {
			$classes[] = 'fdm-item-has-price-discount';
		}
	
		if ( !empty( $fdm_controller->settings->get_setting('fdm-item-flag-icon-size') ) && $fdm_controller->settings->get_setting('fdm-item-flag-icon-size') == '64' ) {
			$classes[] = 'fdm-icon-64';
		}
	
		return $classes;
	
	}

	/**
	 * Determine the display price for an item being added to the cart
	 * @since 2.1
	 */
	public function load_determine_order_price() {
		global $fdm_controller;
		
		if ( ! empty( $this->price_discount ) and 
			( empty( $fdm_controller->settings->get_setting( 'fdm-ordering-additional-prices' ) ) or 
			( isset( $this->selected_price ) and substr( $this->selected_price, 0, strlen( $this->get_label( 'label-discount' ) ) ) == $this->get_label( 'label-discount' ) ) )
		   ) { 
			
			$this->order_price = $this->price_discount;

			return;
		}

		if ( isset( $this->selected_price ) and $this->selected_price ) {
			
			foreach ( $this->prices as $price ) {
				
				if ( $this->selected_price == $price ) {

					$this->order_price = $price;
					
					return;
				}
			}
		}
		
		$this->order_price = reset( $this->prices );
	}
}
 
?>