<ul <?php echo fdm_format_classes( $this->classes ); ?>>
	<li class="fdm-section-header<?php echo ($this->background_image_placement == 'background' ? ' fdm-section-background-image' : ''); ?>" id="fdm-section-header-<?php echo esc_attr( $this->slug ); ?>">

		<?php if ( ( $this->background_image_placement == 'background' ) and $this->image_url ) : ?>
			<div class="fdm-section-header-image-area" style="background-image: url(<?php echo esc_attr( $this->image_url ); ?>); background-repeat: no-repeat; background-size: cover;">
				<h3 class='<?php echo esc_attr( $this->title_class ); ?> h3-on-image'><?php echo esc_html( $this->title ); ?></h3>
			</div>
		<?php endif; ?>
		
		<?php if ( ( $this->background_image_placement == 'above' ) and $this->image_url ) : ?>
			<div class="fdm-section-header-image-area" style="background-image: url(<?php echo esc_attr( $this->image_url ); ?>); background-repeat: no-repeat; background-size: cover;"></div>
		<?php endif; ?>

		<?php if ( ( $this->background_image_placement == 'background' and $this->image_url == '' ) or $this->background_image_placement != 'background' ) : ?>
			<h3 class='<?php echo esc_attr( $this->title_class ); ?>'><?php echo esc_html( $this->title ); ?></h3>
		<?php endif; ?>

		<?php if ( $this->background_image_placement == 'below' and $this->image_url ) : ?>
			<div class="fdm-section-header-image-area" style="background-image: url(<?php echo esc_attr( $this->image_url ); ?>); background-repeat: no-repeat; background-size: cover;"></div>
		<?php endif; ?>

		<?php if ( $this->description ) : ?>
		<p><?php echo wp_kses_post( $this->description ); ?></p>
		<?php endif; ?>

	</li>
	<?php echo wp_kses( $this->print_items(), $this->allowed_tags ); ?>
</ul>