<?php global $fdm_controller; ?>
<div class="fdm-cart-delete-wrapper">
	<div class="fdm-cart-delete-item" data-itemidentifier="<?php echo esc_attr( $this->item_identifier ); ?>">
		<?php 
		if ( $fdm_controller->settings->get_setting( 'fdm-order-cart-location' ) == 'bottom' and $fdm_controller->settings->get_setting( 'fdm-order-cart-style' ) == 'alt' ) {

			echo '<span class="dashicons dashicons-dismiss"></span>';
		}
		else {
			
			echo esc_html( $this->get_label( 'label-remove' ) );
		}
		?>
	</div>
</div>