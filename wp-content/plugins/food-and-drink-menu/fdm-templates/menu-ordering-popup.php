<div class='fdm-ordering-popup-background fdm-hidden'></div>

<div <?php echo fdm_format_classes( $this->classes ); ?>>

	<div class='fdm-ordering-popup-inside'>

		<div class='fdm-ordering-popup-close'>
			<div class='fdm-ordering-popup-close-inside'><span class="dashicons dashicons-no-alt"></span></div>
		</div>

		<div class='fdm-ordering-popup-item-title' id='fdm-ordering-popup-header'>
			<?php echo esc_html( $this->get_label( 'label-order-item-details' ) ); ?>
		</div>

		<div id='fdm-ordering-popup-options'></div>

		<div id='fdm-ordering-popup-note'>
			<div class='fdm-ordering-popup-note-title'>
				<?php echo esc_html( $this->get_label( 'label-item-note' ) ); ?>
			</div>
			<textarea name='fdm-ordering-popup-note'></textarea>
		</div>

		<div id='fdm-ordering-popup-submit'>
			<button>
				<?php echo esc_html( $this->get_label( 'label-confirm-details' ) ); ?>
			</button>
		</div>
	</div>
</div>