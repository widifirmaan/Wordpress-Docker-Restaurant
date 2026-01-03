<?php 
global $fdm_controller;
if ( $this->is_singular() ) : ?>
<div id="<?php echo esc_attr( fdm_global_unique_id() ); ?>" class="fdm-menu fdm-menu-item">
<?php endif; ?>

	<?php if ( $this->is_singular() ) : ?>
	<div<?php echo fdm_format_classes( $this->classes ); ?>>
	<?php else : ?>
	<li<?php echo fdm_format_classes( $this->classes ); ?> data-postid="<?php echo esc_attr( $this->id ); ?>" data-section="<?php echo esc_attr( $this->section ); ?>" data-timeadded="<?php echo esc_attr( strtotime( $this->post->post_date ) ); ?>" <?php if ( in_array( 'fdm-item-newpage-open', $this->classes ) ) { echo 'data-permalink="' . get_permalink( $this->id ) . '"'; } ?>>
	<?php endif; ?>

		<?php 
		if ( $fdm_controller->settings->get_setting('fdm-pro-style') != 'luxe' ) {

			echo wp_kses( $this->print_elements( 'header' ), $this->allowed_tags );
		}
		?>

		<div class="fdm-item-panel" data-price="<?php echo esc_attr( $this->plain_price ) ?? ''; ?>">

			<?php echo wp_kses( $this->print_elements( 'body' ), $this->allowed_tags ); ?>

			<div class="clearfix"></div>
		</div>

		<?php echo wp_kses( $this->print_elements( 'footer' ), $this->allowed_tags ); ?>

	<?php if ( $this->is_singular() ) : ?>
	</div>
	<?php else : ?>
	</li>
	<?php endif; ?>


<?php if ( $this->is_singular() ) : ?>
</div>
<?php endif; ?>
