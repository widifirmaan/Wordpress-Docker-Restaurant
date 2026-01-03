<?php

/**
 * Class to display an order add/edit form in the admin.
 *
 * @since 2.4.1
 */
class fdmAdminOrderFormView extends fdmView {

	// Pointer to the order being displayed
	public $order;
	
	// Pointer to the order item being rendered
	public $current_order_item;

	/**
	 * Render the view and enqueue required stylesheets
	 * @since 2.4.1
	 */
	public function render() {
		global $fdm_controller;

		ob_start();

		$template = $this->find_template( 'admin-order-form' );
		
		if ( $template ) {
			include( $template );
		}

		$output = ob_get_clean();

		return apply_filters( 'fdm_admin_order_output', $output, $this );
	}

	/**
	 * Render a single item in the admin orders table
	 * The items are rendered individually so that when an item is added
	 * to an order by the admin, the output can be more easily created
	 * @since 2.4.1
	 */
	public function render_admin_order_item( $order_item ) {
		global $fdm_controller;

		$this->current_order_item = $order_item;

		ob_start();

		$template = $this->find_template( 'admin-order-table-item' );
		
		if ( $template ) {
			include( $template );
		}

		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Return the URL to delete the current menu item
	 * @since 2.4.1
	 */
	public function get_admin_item_delete_url() {

		$args = array(
			'action' 	=> 'delete_order_item',
			'id'		=> $this->current_order_item->id,
			'item_id'	=> $this->current_order_item->item_identifier,
		);

		return add_query_arg( $args );
	}

	/**
	 * Print the selected options for an order item, with the item
	 * price displayed in brackets
	 * @since 2.4.1
	 */
	public function print_selected_order_option() {

		$ordering_options = get_post_meta( $this->current_order_item->id, '_fdm_ordering_options', true );
		$ordering_options = is_array( $ordering_options ) ? $ordering_options : array();

		foreach( $this->current_order_item->selected_options as $selected_option ) {

			if ( ! array_key_exists( $selected_option, $ordering_options ) ) { continue; }

			echo '<li>' . esc_html( $ordering_options[ $selected_option ]['name'] ) . ' (' . fdm_format_price( $ordering_options[ $selected_option ]['cost'] ) . ')' . '</li>';
		}
	}
}
