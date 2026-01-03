<div class="fdm-item-price-wrapper"  data-min_price='<?php echo esc_attr( $this->min_price ); ?>' data-max_price='<?php echo esc_attr( $this->max_price ); ?>'>
	<?php if ( isset( $this->price_discount ) ) : ?>
	<div class="fdm-item-price-discount"><?php echo esc_html( $this->price_discount ); ?></div>
	<?php endif; ?>
	<?php if ( isset( $this->prices ) ) : ?>
		<?php foreach ( $this->prices as $price ) : ?>
			<div class="fdm-item-price"><?php echo esc_html( $price ); ?></div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
