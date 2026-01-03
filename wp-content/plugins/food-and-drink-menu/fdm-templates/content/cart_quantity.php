<div class="fdm-item-quantity-wrapper">
  <label><?php echo esc_html( $this->get_label( 'label-quantity' ) ); ?>:</label>
  <input type="number" required min="1" max="10000" class="" name="<?php echo esc_attr( $this->id ); ?>-quantity" value="<?php echo esc_attr( $this->quantity ); ?>">
</div>