<div <?php echo fdm_format_classes( $this->classes ); ?>>
	<div class='fdm-sidebar-mobile-expand-area'>
		<div class='fdm-sidebar-mobile-expand-button'><?php echo $this->get_label( 'label-sidebar-expand-button' ); ?></div>
	</div>
	<div class='fdm-sidebar'>
		<?php echo $this->print_sidebar_sections(); ?>
	</div>
</div>