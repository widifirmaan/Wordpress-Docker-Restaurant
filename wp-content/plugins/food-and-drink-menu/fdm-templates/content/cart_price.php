<div class="fdm-cart-item-price-wrapper">
	<?php if ( $this->order_price ) : ?>
		<?php $cart_price = fdm_calculate_cart_price ( $this ); ?>
		<div class="fdm-cart-item-price" data-price="<?php echo esc_attr( $cart_price ); ?>"><?php echo esc_html( fdm_format_price( $cart_price ) ); ?></div>
	<?php endif; ?>
</div>
