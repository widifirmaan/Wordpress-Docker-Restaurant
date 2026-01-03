<?php

/**
 * Register, display and save an option with multiple checkboxes.
 *
 * This setting accepts the following arguments in its constructor function.
 *
 * $args = array(
 *		'id'			=> 'setting_id', 	// Unique id
 *		'title'			=> 'My Setting', 	// Title or label for the setting
 *		'add_label'		=> '+ ADD', 		// Text for the "Add Row" button
 *		'del_label'		=> 'Delete'			// Text for the "Delete" button
 *		'description'	=> 'Description', 	// Help text description
 *		'fields'		=> array(
 *		   'field' => array(
 * 				'type' => 'text' //text, select
 * 				'label' => 'Name'
 * 				'required' => false,
 *				'options' => array()
 * 			)
 *		) 		// The attributes and labels for the fields
 * );
 *
 * @since 2.0
 * @package Simple Admin Pages
 */

class sapAdminPageSettingInfiniteTable_2_6_19 extends sapAdminPageSetting_2_6_19 {

	public $has_editor = false; // Whether an editor field is included in the table columns
	public $add_label = '+ ADD'; // Label applied to the add row button
	public $del_label = 'Delete'; // Label applied to the delete row button
	public $fields = array(); // Settings that can be set for each row in the table

	public $sanitize_callback = 'sap_sanitize_infinite_table';

	/**
	 * Add in the JS requried for rows to be added and the values to be stored
	 * @since 2.0
	 */
	public $scripts = array(
		'sap-infinite-table' => array(
			'path'			=> 'js/infinite_table.js',
			'dependencies'	=> array( 'jquery' ),
			'version'		=> SAP_VERSION,
			'footer'		=> true,
		),
	);

	/**
	 * Add in the CSS requried for rows to be displayed correctly
	 * @since 2.0
	 */
	public $styles = array(
		'sap-infinite-table' => array(
			'path'			=> 'css/infinite_table.css',
			'dependencies'	=> array( ),
			'version'		=> SAP_VERSION,
			'media'			=> 'all',
		),
	);

	/**
	 * Display this setting
	 * @since 2.0
	 */
	public function display_setting() {

		$input_name = $this->get_input_name();
		$values = json_decode( html_entity_decode( $this->value ) );
		
		$this->fields = array_filter( $this->fields );

		if ( ! is_array( $values ) ) {

			$values = $this->get_default_setting();
		}

		$field_ids = implode( ',', array_keys( $this->fields ) );

		$this->set_field_class_string();
		
		?>

		<fieldset <?php $this->print_conditional_data(); ?>>
			<div class='sap-infinite-table <?php echo ( $this->disabled ? 'disabled' : ''); ?>' data-fieldids='<?php echo esc_attr( $field_ids ); ?>'>
				<input type='hidden' id="sap-infinite-table-main-input" name='<?php echo esc_attr( $input_name ); ?>' value='<?php echo $this->value; ?>' />
				<table>
					<thead>
						<tr>
							<?php foreach ($this->fields as $field) { ?>
								<th class='<?php echo esc_attr( $field['class_string'] ); ?>'><?php echo esc_html( $field['label'] ); ?></th>
								<?php if ($field['type'] == 'editor') { $this->has_editor = true; } ?>
							<?php } ?>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($values as $row_id => $row) { ?>
							<tr class='sap-infinite-table-row' data-row_id='<?php echo esc_attr( $row_id ); ?>'>
								<?php foreach ($this->fields as $field_id => $field) { ?>
									<td data-field-type="<?php echo esc_attr( $field['type'] ); ?>" class='<?php echo esc_attr( $field['class_string'] ); ?>'>
										<span class='sap-infinite-table-td-content <?php echo $this->get_conditional_display( $field, $row ); ?>' <?php $this->print_field_conditional_data( $field ); ?>>
											<?php if ($field['type'] == 'id') : ?>
												<span class='sap-infinite-table-id-html'><?php echo esc_html( $row->$field_id ); ?></span>
												<input type='hidden' data-name='<?php echo esc_attr( $field_id ); ?>' value='<?php echo esc_attr( $row->$field_id ); ?>' />
											<?php endif; ?>
											<?php if ($field['type'] == 'text') : ?>
												<input type='text' data-name='<?php echo esc_attr( $field_id ); ?>' value='<?php echo esc_attr( $row->$field_id ); ?>' />
											<?php endif; ?>
											<?php if ($field['type'] == 'textarea') : ?>
												<textarea data-name='<?php echo esc_attr( $field_id ); ?>'><?php echo esc_textarea( $row->$field_id ); ?></textarea>
											<?php endif; ?>
											<?php if ($field['type'] == 'editor') : ?>
												<span class='sap-infinite-table-editor-value' data-name='<?php echo esc_attr( $field_id ); ?>'><?php echo esc_html( $this->get_editor_row_preview( $row->$field_id ) ); ?></span>
												<input class='sap-infinite-table-editor-input' type='hidden' data-name='<?php echo esc_attr( $field_id ); ?>' value='<?php echo esc_attr( $row->$field_id ); ?>' />
											<?php endif; ?>
											<?php if ($field['type'] == 'number') : ?>
												<input type='number' data-name='<?php echo esc_attr( $field_id ); ?>' value='<?php echo esc_attr( $row->$field_id ); ?>' />
											<?php endif; ?>
											<?php if ($field['type'] == 'hidden') : ?>
												<span class='sap-infinite-table-hidden-value'><?php echo esc_html( $row->$field_id ); ?></span>
												<input type='hidden' data-name='<?php echo esc_attr( $field_id ); ?>' value='<?php echo esc_attr( $row->$field_id ); ?>' />
											<?php endif; ?>
											<?php if ($field['type'] == 'select') : ?>
												<select data-name='<?php echo esc_attr( $field_id ); ?>'>
													<?php if ( ! empty( $field['blank_option'] ) ) { ?><option></option><?php } ?>
													<?php $this->print_options( $field['options'], $row, $field_id ); ?>
												</select>
											<?php endif; ?>
											<?php if ($field['type'] == 'toggle') : ?>
												<label class="sap-admin-switch">
													<input type="checkbox" class="sap-admin-option-toggle" data-name="<?php echo esc_attr( $field_id ); ?>" <?php if( $row->$field_id == '1' ) {echo "checked='checked'";} ?> >
													<span class="sap-admin-switch-slider round"></span>
												</label>
											<?php endif; ?>
										</span>
									</td>
								<?php } ?>
								<td class='sap-infinite-table-row-delete'><?php echo esc_html( $this->del_label ); ?></td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr class='sap-infinite-table-row-template sap-hidden'>
							<?php foreach ($this->fields as $field_id => $field) { ?>
								<td data-field-type="<?php echo esc_attr( $field['type'] ); ?>" class='<?php echo esc_attr( $field['class_string'] ); ?>'>
									<?php if ($field['type'] == 'id') : ?>
										<span class='sap-infinite-table-id-html'></span>
										<input type='hidden' data-name='<?php echo esc_attr( $field_id ); ?>' value='' />
									<?php endif; ?>
									<?php if ($field['type'] == 'text') : ?>
										<input type='text' data-name='<?php echo esc_attr( $field_id ); ?>' value='' />
									<?php endif; ?>
									<?php if ($field['type'] == 'textarea') : ?>
										<textarea data-name='<?php echo esc_attr( $field_id ); ?>'></textarea>
									<?php endif; ?>
									<?php if ($field['type'] == 'editor') : ?>
										<span class='sap-infinite-table-editor-value' data-name='<?php echo esc_attr( $field_id ); ?>'>Open Editor</span>
										<input class='sap-infinite-table-editor-input' type='hidden' data-name='<?php echo esc_attr( $field_id ); ?>' />
									<?php endif; ?>
									<?php if ($field['type'] == 'number') : ?>
										<input type='number' data-name='<?php echo esc_attr( $field_id ); ?>' value='' />
									<?php endif; ?>
									<?php if ($field['type'] == 'hidden') : ?>
										<span class='sap-infinite-table-hidden-value'></span>
										<input type='hidden' data-name='<?php echo esc_attr( $field_id ); ?>' value='' />
									<?php endif; ?>
									<?php if ($field['type'] == 'select') : ?>
										<select data-name='<?php echo esc_attr( $field_id ); ?>'>
											<?php if ( ! empty( $field['blank_option'] ) ) { ?><option></option><?php } ?>
											<?php $this->print_options( $field['options'] ); ?>
										</select>
									<?php endif; ?>
									<?php if ($field['type'] == 'toggle') : ?>
										<label class="sap-admin-switch">
											<input type="checkbox" class="sap-admin-option-toggle" data-name="<?php echo esc_attr( $field_id ); ?>" checked >
											<span class="sap-admin-switch-slider round"></span>
										</label>
									<?php endif; ?>
								</td>
							<?php } ?>
							<td class='sap-infinite-table-row-delete'>
								<?php echo wp_kses_post( $this->del_label ); ?>
							</td>
						</tr>
						<tr class='sap-infinite-table-add-row'>
							<td colspan="<?php echo count( $this->fields ) ?>">
								<a class="sap-new-admin-add-button">
									<?php echo wp_kses_post( $this->add_label ); ?>
								</a>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>

			<?php if ( $this->has_editor ) { ?>

				<div class='sap-hidden sap-infinite-table-editor-container' data-editor_id='<?php echo esc_attr( preg_replace( '/[^\da-z]/i', '', $this->id ) ); ?>'>
					<div class='sap-infinite-table-editor-container-inside'>
						<div class='sap-infinite-table-editor-container-inside-scroll'>
							<?php wp_editor( '', preg_replace( '/[^\da-z]/i', '', $this->id ) ); ?>
							<div class='sap-infinite-table-editor-buttons'>
								<div class='sap-infinite-table-editor-cancel'><?php _e( 'Cancel', 'simple-admin-pages' ); ?></div>
								<div class='sap-infinite-table-editor-save'><?php _e( 'Save', 'simple-admin-pages' ); ?></div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>

			<?php $this->display_disabled(); ?>
		</fieldset>

		<?php

		$this->display_description();

	}

	/**
	 * Recursively print out select options
	 * @since 2.5.3
	 */
	public function print_options( $options, $row = false, $field_id = 0 ) {

		foreach ( $options as $option_value => $option_name ) {

			if ( is_array( $option_name ) ) { ?>

				<optgroup label='<?php echo esc_attr( $option_value ); ?>'>
					<?php $this->print_options( $option_name, $row, $field_id ); ?>
				</optgroup>

				<?php

				continue;
			}

			$selected_value = ( $row and isset( $row->$field_id ) ) ? $row->$field_id : false;

			?>

			<option value='<?php echo esc_attr( $option_value ); ?>' <?php echo ($selected_value == $option_value ? 'selected="selected"' : ''); ?>>
				<?php echo esc_html( $option_name ); ?>
			</option>
		
			<?php 
		}
	}

	/**
	 * Get the default value for a setting if value is currently empty
	 * Uses a fallback value rather than the default $this->value, used in the main class
	 *
	 * @since 2.6.4
	 */
	public function get_default_setting( $fallback_value = array() ) {

		return ! empty( $this->default ) ? $this->default : $fallback_value;
	}

	/**
	 * Get the preview text for an editor input type
	 *
	 * @since 2.6.14
	 */
	public function get_editor_row_preview( $value, $preview_length = 60 ) {

		return substr( strip_tags( $value ), 0, $preview_length ) . ( strlen( $value ) > $preview_length ? '...' : '' );
	}

	/**
	 * Adds a 'class_string' property to each field
	 *
	 * @since 2.6.15
	 */
	public function set_field_class_string() {

		foreach ( $this->fields as $field_id => $field ) {

			$this->fields[ $field_id ]['class_string'] = implode( ',', !empty( $field['classes'] ) ? $field['classes'] : array() );
		}
	}

	/**
	 * Determines whether a field in a row should be displayed, based on its
	 * conditional conditions, if any.
	 *
	 * @since 2.6.15
	 */
	public function get_conditional_display( $field, $row ) {

		if ( empty( $field['conditional_on'] ) ) { return; }

		$conditional_on_value = is_array( $field['conditional_on_value'] ) ? $field['conditional_on_value'] : explode( ',', $field['conditional_on_value'] );

		return ! in_array( $row->{$field['conditional_on']}, $conditional_on_value ) ? 'sap-hidden' : '';
	}

	/**
	 * Prints conditional data tags within the input element if necessary
	 *
	 * @since 2.6.15
	 */
	public function print_field_conditional_data( $field ) {

		if ( empty( $field['conditional_on'] ) ) { return; }

		$conditional_on_value = is_array( $field['conditional_on_value'] ) ? implode( ',', $field['conditional_on_value'] ) : $field['conditional_on_value'];

		echo 'data-conditional_on="' . esc_attr( $field['conditional_on'] ) . '"';
		echo 'data-conditional_on_value="' . esc_attr( $conditional_on_value ) . '"';
	}
}


if ( ! function_exists( 'sap_sanitize_infinite_table' ) ) {
function sap_sanitize_infinite_table( $value ) {

    $values = json_decode( $value, true );

    if ( $values === null) {

        return null;
    }
    
    array_walk_recursive( $values, function ( &$item_value, $key ) {
        
        $item_value = wp_kses_post( $item_value );
    });

    return json_encode( $values );
}
}