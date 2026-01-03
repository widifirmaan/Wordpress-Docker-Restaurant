<div class="fdm-item-price-wrapper" data-min_price='<?php echo esc_attr($this->min_price); ?>'
	data-max_price='<?php echo esc_attr($this->max_price); ?>'>
	<?php foreach ($this->prices as $price): ?>
		<div class="fdm-item-price"><?php echo str_replace('$', '', esc_html($price)); ?></div>
	<?php endforeach; ?>
</div>