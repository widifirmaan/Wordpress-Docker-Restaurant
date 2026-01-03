<ul class="fdm-menu-item-flags">
	<?php foreach ( $this->flags as $flag ) : ?>
	<li<?php echo fdm_format_classes( $flag->classes ); ?> title="<?php echo esc_attr( $flag->name ); ?>"><?php if ( isset( $flag->text_only ) ) { echo esc_html( $flag->name ); }?></li>
	<?php endforeach; ?>
</ul>