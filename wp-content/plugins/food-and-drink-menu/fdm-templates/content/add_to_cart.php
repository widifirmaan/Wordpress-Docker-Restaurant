<?php
global $fdm_controller;

$prices = ! empty( $this->price_discount ) ? array_merge( array( esc_html( $this->get_label( 'label-discount' ) ) . $this->price_discount ), $this->prices ) : $this->prices;

?>

<div class="<?php echo $fdm_controller->settings->get_setting( 'fdm-enable-ordering-options' ) ? 'fdm-options-add-to-cart-button' : 'fdm-add-to-cart-button'; ?>" data-postid="<?php echo esc_attr( $this->id ); ?>" data-options='<?php echo htmlspecialchars( json_encode( $this->ordering_options ), ENT_QUOTES, 'UTF-8' ); ?>' data-prices='<?php echo htmlspecialchars( json_encode( $prices ), ENT_QUOTES, 'UTF-8' ); ?>'>
	<?php echo esc_html( $this->get_label( 'label-add-to-cart' ) ); ?>
</div>


