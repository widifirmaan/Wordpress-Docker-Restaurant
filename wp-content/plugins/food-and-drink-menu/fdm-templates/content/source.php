<div<?php echo fdm_format_classes( $this->source_classes ); ?>>
	<div class="fdm-item-src-name">
		<p class="src-title"><?php echo esc_html( $this->source_name ); ?></p>

		<?php if ( isset( $this->source_desc ) ) : ?>
			<p class="fdm-item-src-desc"><?php echo esc_html( $this->source_desc ); ?></p>
		<?php endif; ?>

	</div>

	<?php if ( isset( $this->source_address ) && $this->source_address ) : global $fdm_controller; ?>
		<img class="fdm-item-src-map" src="//maps.google.com/maps/api/staticmap?markers=size:normal|color:blue|<?php echo esc_attr( urlencode( $this->source_address ) ); if ( isset( $this->source_zoom ) ) : ?>&amp;zoom=<?php echo (int) $this->source_zoom; endif; ?>&amp;size=300x300&amp;scale=2&amp;sensor=false&amp;visual_refresh=true<?php if ( !empty( $fdm_controller->settings->get_setting('fdm-google-map-api-key') ) ) : echo '&amp;key=' . esc_attr( urlencode( $fdm_controller->settings->get_setting('fdm-google-map-api-key') ) ); endif; ?>" alt="<?php echo esc_attr( $this->source_name ); ?>">
	<?php endif; ?>

	<div class="clearfix"></div>

</div>
