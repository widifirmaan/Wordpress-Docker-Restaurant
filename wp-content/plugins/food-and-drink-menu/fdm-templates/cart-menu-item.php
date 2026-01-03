<div 
  class="fdm-menu-cart fdm-cart-menu-item" 
  data-postid="<?php echo esc_attr( $this->id ); ?>"
  data-item_identifier="<?php echo esc_attr( $this->item_identifier ); ?>" >

	<div class="fdm-cart-item-panel">

		<?php echo wp_kses( $this->print_cart_elements( 'body' ), $this->allowed_tags ); ?>

		<div class="clearfix"></div>
	</div>

</div>
