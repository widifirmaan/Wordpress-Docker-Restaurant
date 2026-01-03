<div <?php echo fdm_format_classes( $this->classes ); ?>>

	<h3 class='fdm-filtering-header'>
		<?php echo esc_html( $this->get_label( 'label-filtering' ) ); ?>
	</h3>

	<?php if ( ! empty( $this->text_search ) ) : ?>
		<div class='fdm-filtering-section fdm-filtering-text-section'>
			<label class='fdm-filtering-label fdm-filtering-text-label'>
				<?php echo esc_html( $this->get_label( 'label-search' ) ); ?>
			</label> 
			<input type='text' class='fdm-filtering-text-input' value='' placeholder='<?php echo esc_attr( $this->get_label( 'label-search-items' ) ); ?>' data-search='<?php echo esc_attr( implode(",", $this->text_search) ); ?>' />
		</div>
	<?php endif; ?>

	<?php if ( $this->enable_price_filtering ) : ?>
		<div class='fdm-filtering-section fdm-filtering-price-section'>
			<label class='fdm-filtering-label fdm-filtering-price-label'>
				<?php echo esc_html( $this->get_label( 'label-filtering-price' ) ); ?>
			</label> 
			<?php if ( $this->price_filtering_type == 'textbox' ) : ?>
				<div class='fdm-filtering-price-input-container'>
					<input type='text' class='fdm-filtering-min-price-input' placeholder='0' />
					<span class='fdm-filtering-price-separator'> - </span> 
					<input type='text' class='fdm-filtering-max-price-input' placeholder='1000' />
				</div>
			<?php endif; ?>
			<?php if ( $this->price_filtering_type == 'slider' ) : ?>
				<div class='fdm-filtering-price-input-container fdm-filtering-price-slider-price-display'>
					<span class='fdm-filtering-min-price-display'>0</span>
					<span class='fdm-filtering-price-separator'> - </span> 
					<span class='fdm-filtering-max-price-display'>1000</span>
					<div id='fdm-filtering-price-slider'></div>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $this->enable_sorting ) : ?>
		<div class='fdm-filtering-section fdm-filtering-sorting-section'>
			<label class='fdm-filtering-label fdm-filtering-sorting-label'>
				<?php echo esc_html( $this->get_label( 'label-sorting' ) ); ?>
			</label> 
		
			<select class='fdm-filtering-sorting-input' >
				<option value=''></option>
				<?php if ( in_array('name', $this->sorting_types ) ) : ?>
					<option value='name_asc' ><?php echo esc_html( $this->get_label( 'label-name-asc' ) ); ?></option>
					<option value='name_desc' ><?php echo esc_html( $this->get_label( 'label-name-desc' ) ); ?></option>
				<?php endif; ?>
				<?php if ( in_array('price', $this->sorting_types ) ) : ?>
					<option value='price_asc' ><?php echo esc_html( $this->get_label( 'label-price-asc' ) ); ?></option>
					<option value='price_desc' ><?php echo esc_html( $this->get_label( 'label-price-desc' ) ); ?></option>
				<?php endif; ?>
				<?php if ( in_array('date_added', $this->sorting_types ) ) : ?>
					<option value='date_asc' ><?php echo esc_html( $this->get_label( 'label-date-added-asc' ) ); ?></option>
					<option value='date_desc' ><?php echo esc_html( $this->get_label( 'label-date-added-desc' ) ); ?></option>
				<?php endif; ?>
				<?php if ( in_array('name', $this->sorting_types ) ) : ?>
					<option value='section_asc' ><?php echo esc_html( $this->get_label( 'label-section-asc' ) ); ?></option>
					<option value='section_desc' ><?php echo esc_html( $this->get_label( 'label-section-desc' ) ); ?></option>
				<?php endif; ?>
			</select>

		</div>
	<?php endif; ?>

</div>