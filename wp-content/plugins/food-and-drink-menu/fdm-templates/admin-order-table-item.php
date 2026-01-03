<tr>
	<td>
		<a href='<?php echo esc_attr( $this->get_admin_item_delete_url() ); ?>'><?php _e( 'Delete?', 'food-and-drink-menu' ); ?></a>
	</td> 
	<td><?php echo esc_html( get_the_title( $this->current_order_item->id ) ); ?></td>
	<td><?php echo esc_html( $this->current_order_item->quantity ); ?></td>
	<td><?php echo esc_html( fdm_calculate_admin_price( $this->current_order_item ) ); ?></td>
	<td>
		<?php if ( ! empty( $this->current_order_item->note ) ) {

			echo 'Note: ' . esc_html( $this->current_order_item->note ) . '<br/>'; 
		} ?>

		<?php if ( ! empty( $this->current_order_item->selected_options ) ) {

			echo 'Options:<br/>';

			echo '<ul>';

			$this->print_selected_order_option();

			echo '</ul>';
		} ?>

	</td>
</tr>