<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmOrderNotification' ) ) {
/**
 * Class for any order notification that needs to go out from the plugin
 *
 * @since 2.4.0
 */
abstract class fdmOrderNotification {

	/**
	 * The order this notification is being sent for
	 * @since 2.4.0
	 */
	public $order;

	/**
	 * Target of the notification (who/what will receive it)
	 * @since 2.4.0
	 */
	public $target;

	public function __construct( $args ) {

		// Parse the values passed
		$this->parse_args( $args );
	}

	/**
	 * Prepare and validate notification data
	 *
	 * @return boolean if the data is valid and ready for transport
	 * @since 2.4.0
	 */
	abstract public function prepare_notification();

	/**
	 * Send notification
	 * @since 2.4.0
	 */
	abstract public function send_notification();

	/**
	 * Parse the arguments passed in the construction and assign them to
	 * internal variables.
	 * @since 2.4.0
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

	/**
	 * Process a template and insert booking details
	 * @since 2.4.0
	 */
	public function process_template( $message ) {
		global $fdm_controller;

		$accept_order_url = add_query_arg(
			array(
				'fdm_action' 	=> 'update_status', 
				'status' 		=> 'fdm_order_accepted',
				'order_id' 		=> $this->order->id
			),
			$this->order->permalink
		);

		$accept_link = ( get_class( $this ) == 'fdmOrderNotificationSMS' ) ? $accept_order_url : '<a href="' . esc_attr( $accept_order_url ) . '">' . __( 'Accept Order', 'food-and-drink-menu' ) . '</a>';
		
		$site_link = ( get_class( $this ) == 'fdmOrderNotificationSMS' ) ? home_url( '/' ) : '<a href="' . home_url( '/' ) . '">' . get_bloginfo( 'name' ) . '</a>';

		$order_items_HTML = $this->get_order_items_html();

		$custom_fields_HTML = $this->get_custom_fields_html();
		
		$template_tags = array(
			'{order_number}'    => $this->order->ID,
			'{email}'           => $this->order->email,
			'{name}'            => $this->order->name,
			'{note}'            => $this->order->note,
			'{phone}'           => $this->order->phone,
			'{eta}'           	=> $this->order->estimated_time,
			'{custom_fields}'	=> $custom_fields_HTML,
			'{payment_amount}'  => fdm_format_price( $this->order->get_order_total_tax_in() ),
			'{order_items}'     => $order_items_HTML,
			'{accept_link}'     => $accept_link,
			'{site_name}'       => get_bloginfo( 'name' ),
			'{site_link}'       => $site_link,
			'{current_time}'    => date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ) . ' ' . date_i18n( get_option( 'time_format' ), current_time( 'timestamp' ) ),
		);

		$template_tags = apply_filters( 'fdm_notification_template_tags', $template_tags, $this );

		return str_replace( array_keys( $template_tags ), array_values( $template_tags ), $message );
	}

	/**
	 * Returns an HTML table of items in an order. Can only be included in emails, not SMS
	 * @since 2.4.1
	 */
	public function get_order_items_html() {

		if ( get_class( $this ) == 'fdmOrderNotificationSMS' ) { return ''; } 

		$order_items = $this->order->get_order_items();

		$counter = 0;

		ob_start();

		?>

		<style>
			table, thead, tbody, th, td {
				border: 1px solid black;
				border-collapse: collapse;
			}
			th, td {
				padding: 5px;
			}
		</style>

		<table>
			<thead>
				<tr>
					<th>##</th>
					<th>Title (ID)</th>
					<th>Option</th>
					<th>Note</th>
					<th>Quantity</th>
					<th>Price</th>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>';

			<?php foreach ( $order_items as $order_item ) {

				$counter++;

				$ordering_options = get_post_meta( $order_item->id, '_fdm_ordering_options', true );
				$ordering_options = is_array( $ordering_options ) ? $ordering_options :  array();

				$selected_options = is_array( $order_item->selected_options ) ? $order_item->selected_options : array();

				$item_price = fdm_calculate_admin_price( $order_item );

				?>
			
				<tr>

					<td><?php echo esc_html( $counter ); ?></td>
			
					<td><?php echo esc_html( get_the_title( $order_item->id ) ); ?> (<?php echo esc_html( $order_item->id );?>)</td>

					<td>
						<?php foreach ( $selected_options as $selected_option ) {
							$ordering_options[ $selected_option ]['name'];
						} ?>
					</td>

					<td>
						<?php echo ( $order_item->note != '' ? esc_html( htmlspecialchars( $order_item->note ) ) : '' ); ?>
					</td>

					<td>
						<?php echo ( property_exists( $order_item, 'quantity' ) ? $order_item->quantity : '' ); ?>
					</td>

					<td><?php echo esc_html( $order_item->selected_price ); ?></td>

					<td><?php echo esc_html( fdm_format_price( $item_price ) ); ?></td>

				</tr>
			<?php } ?>

			<tbody>

		</table>

		<?php

		$order_items_HTML = ob_get_clean();

		return $order_items_HTML;
	}

	/**
	 * Returns a name/value set of custom field pairs in an order
	 * @since 2.4.1
	 */
	public function get_custom_fields_html() {
		global $fdm_controller;
		
		$custom_fields_HTML = '';

		$custom_fields = $fdm_controller->settings->get_ordering_custom_fields();

		foreach ( $custom_fields as $custom_field ) {

			if ( empty( $this->order->custom_fields[ $custom_field->slug ] ) ) { continue; }

			$custom_fields_HTML .= $custom_field->name . ': ' . ( is_array( $this->order->custom_fields[ $custom_field->slug ] ) ? implode( ',', $this->order->custom_fields[ $custom_field->slug ] ) : esc_html( $this->order->custom_fields[ $custom_field->slug ] ) ) . "\n";
		}

		return $custom_fields_HTML;
	}

	/**
	 * Set a property ($key) to a certain $value
	 * @since 2.4.0
	 */
	public function set( $key, $value ) {

		$this->{$key} = $value;
	}
}
}

?>