<div class="fdm-item-related-items">

	<div class='fdm-item-related-items-label'>
		<?php echo esc_html( $this->get_label( 'label-related-items' ) ); ?>
	</div>

	<?php foreach ($this->related_items as $related_item) { ?>
		<div class='fdm-menu-item-related-item'>
			<?php

				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $related_item->ID ), 'fdm-item-thumb' );
				if ( isset( $image[0] ) ) {
					$related_item->image = $image[0];
				}

				$related_item->prices = (array) get_post_meta( $related_item->ID, 'fdm_item_price' );
				array_walk( $related_item->prices, function(&$item) {
					global $fdm_controller;
	
					$prefix = ( $fdm_controller->settings->get_setting('fdm-currency-symbol-location') == 'before' ? $fdm_controller->settings->get_setting('fdm-currency-symbol') : '' );
					$suffix = ( $fdm_controller->settings->get_setting('fdm-currency-symbol-location') == 'after' ? $fdm_controller->settings->get_setting('fdm-currency-symbol') : '' );
	
					$item = $prefix . $item . $suffix;
				} );

				$related_item->price = join(
					apply_filters( 'fdm_prices_separator', _x( '/', 'Separator between multiple prices.', 'food-and-drink-menu' ) ),
					$related_item->prices
				);

				$elements = array(
					'related_item_image',
					'related_item_title',
					'related_item_price'
				);

				if ( !isset($related_item->image) ) { $elements = array_diff( $elements, array( 'related_item_image' ) ); }

				foreach ($elements as $element) {
					$template = $this->find_template( $this->content_map[$element] );

					if ( $template ) {
						include( $template );
					}
				}
			?>
		</div>
	<?php } ?>
</div>