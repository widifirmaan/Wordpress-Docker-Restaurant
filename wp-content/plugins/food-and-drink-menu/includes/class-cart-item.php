<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmCartItem' ) ) {
/**
 * Class for any item added to the ordering cart
 *
 * @since 2.1.0
 */
class fdmCartItem {

	// The ID for the post corresponding to this item
	public $id;

	// The name of the item
	public $title;

	// Unique ID for the item in the cart, in case multiple are added separately
	public $item_identifier;

	// The price selected for this item
	public $selected_price;

	// The options (cheese, lettuce, tomato, etc.) for this item, w/ price of option
	public $selected_options = array();

	// The customer's note about this item
	public $note;

	// Item quantity
	public $quantity;

	public function __construct( $args ) {
		
		// Parse the values passed
		$this->parse_args( $args );
	}

	public function update( $args ) {

		if ( ! is_array( $args ) ) { return; }

		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Parse the arguments passed in the construction and assign them to
	 * internal variables.
	 * @since 2.1
	 */
	public function parse_args( $args ) {
		foreach ( $args as $key => $val ) {
			switch ( $key ) {

				case 'id' :
					$this->{$key} = esc_attr( $val );

				default :
					$this->{$key} = $val;

			}
		}
	}

}
}

?>