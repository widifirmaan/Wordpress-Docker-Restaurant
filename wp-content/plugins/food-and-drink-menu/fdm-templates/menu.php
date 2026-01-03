<?php global $fdm_controller; ?>
<?php if ( $this->title ) : ?>
<h3 class="fdm-menu-title"><?php echo esc_html( $this->title ); ?></h3>
<?php endif; ?>
<?php if ( $this->content ) : ?>
<div class="fdm-menu-content">
	<?php echo wp_kses_post( $this->content ); ?>
</div>
<?php endif; ?>

<?php
if ( $this->ordering_display_progress() and $fdm_controller->orders->is_open_for_ordering() ) :
	echo wp_kses( $this->print_order_progress(), $this->allowed_tags );
endif;
?>

<?php
if ( $this->ordering_enabled() and $fdm_controller->orders->is_open_for_ordering() ) :
	echo wp_kses( $this->print_order_popup(), $this->allowed_tags );
endif;
?>

<div class="fdm-menu-filtering">
	<?php echo wp_kses( $this->print_filtering_options(), $this->allowed_tags ); ?>
</div>

<div class="fdm-the-menu">

<?php 
if ($this->has_sidebar()) :
	echo wp_kses( $this->print_sidebar(), $this->allowed_tags );
endif;
?>

<?php 
if ($this->ordering_enabled() and $fdm_controller->orders->is_open_for_ordering() ) :
	echo wp_kses( $this->print_order_sidescreen(), $this->allowed_tags );
endif;
?>

<ul id="<?php echo esc_attr( fdm_global_unique_id() ); ?>"<?php echo fdm_format_classes( $this->classes ); ?>>

<?php foreach ( $this->groups as $group ) :	?>

	<li<?php echo fdm_format_classes( $this->column_classes() ); ?>>

	<?php echo wp_kses( $this->print_group_section( $group ), $this->allowed_tags ); ?>

	</li>

<?php endforeach; ?>

</ul>
<?php if ( $this->footer ) : ?>
<div class="fdm-menu-footer clearfix">
	<?php echo wp_kses_post( $this->footer ); ?>
</div>
<?php endif; ?>

<?php if ( $fdm_controller->settings->get_setting( 'fdm-enable-price-filtering' ) ) { ?>
	<div id='fdm-pricing-info' data-min_price='<?php echo esc_attr( $this->min_price ); ?>' data-max_price='<?php echo esc_attr( $this->max_price ); ?>' data-currency_symbol_location='<?php echo esc_attr( $this->get_option( 'fdm-currency-symbol-location' ) ); ?>' data-currency_symbol='<?php echo esc_attr( $this->get_option( 'fdm-currency-symbol' ) ); ?>'></div>
<?php } ?>

</div> <!-- fdm-the-menu -->

<div class="clearfix"></div>

<div class='fdm-details-div fdm-hidden'>
	<div class='fdm-details-div-inside'>
		<div class='fdm-details-div-exit'>
			<div class='fdm-details-div-exit-inside'><span class="dashicons dashicons-no-alt"></span></div>
		</div>
		<div class='fdm-details-div-content'></div>
	</div>
</div>
<div class='fdm-details-background-div fdm-hidden'></div>