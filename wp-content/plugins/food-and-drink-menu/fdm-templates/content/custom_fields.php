<?php global $fdm_controller; ?>

<div class="fdm-item-custom-fields">
	<h4><?php echo esc_html( $this->get_label( 'label-custom-fields' ) ); ?></h4>
	<?php foreach ($this->custom_fields as $field) { global $fdm_controller; ?>
		<?php if ( $fdm_controller && $fdm_controller->settings->get_setting( 'hide-blank-custom-fields' ) and ! $field->value ) { continue; } ?>
		<div class='fdm-item-custom-fields-each'>
			<div class='fdm-item-custom-field-label'><?php echo esc_html( $field->name ); ?></div>
			<div class='fdm-menu-item-custom-field-value'>
				<?php 
					if ($field->type == 'select') :
						$field_values = explode(",", $field->values);
						foreach ($field_values as $value) {
							if ( sanitize_title( $value ) == $field->value ) { echo esc_html( $value ); }
						}
					elseif ($field->type == 'checkbox') :
						$field_values = explode(",", $field->values);
						$print_value = '';
						foreach ($field_values as $value) {
							if ( is_array( $field->value ) and in_array( sanitize_title( $value ), $field->value ) ) {$print_value .= $value . ", ";}
						}
						$print_value = trim($print_value, ", "); 
						echo esc_html( $print_value );
					else :
						echo esc_html( $field->value );
					endif; 
				?>
			</div>
		</div>	
	<?php } ?>
</div>