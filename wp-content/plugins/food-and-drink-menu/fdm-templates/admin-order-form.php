<div id="fdm-new-edit-order-screen">

	<?php if ( ! empty( $this->update_message ) ) { ?>

		<div class='fdm-update'>
			<?php echo esc_html( $this->update_message ); ?>
		</div>
	<?php } ?>

	<form action='#' method='post' class='fdm-admin-form' enctype='multipart/form-data'>

		<?php wp_nonce_field( 'fdm-admin-nonce', 'fdm-admin-nonce' );  ?>

		<?php echo ( ! empty( $this->order ) ? '<input type="hidden" name="fdm_order_id" value="' . esc_attr( $this->order->id ) . '">' : '' ); ?>

		<div class='fdm-admin-add-edit-order-content'>

			<div class="fdm-dashboard-new-widget-box fsp-widget-box-full fdm-admin-edit-product-left-full-widget-box" id="fdm-admin-edit-order-details-widget-box">

				<div class="fdm-dashboard-new-widget-box-top"><?php _e( 'Customer Details', 'food-and-drink-menu' ); ?></div>
				
				<div class="fdm-dashboard-new-widget-box-bottom">

					<div class='fdm-field'>

						<div class='fdm-admin-label'>

							<label for="fdm_customer_name">
								<?php _e( 'Customer Name', 'food-and-drink-menu' ); ?>
							</label>

						</div>

						<div class='fdm-admin-input'>
							<input type='text' name="fdm_customer_name" value="<?php echo ( ! empty( $this->order->name ) ? esc_attr( $this->order->name ) : '' ); ?>" />
						</div>

					</div>

					<div class='fdm-field'>

						<div class='fdm-admin-label'>

							<label for="fdm_customer_phone">
								<?php _e( 'Customer Phone', 'food-and-drink-menu' ); ?>
							</label>

						</div>

						<div class='fdm-admin-input'>
							<input type='text' name="fdm_customer_phone" value="<?php echo ( ! empty( $this->order->phone ) ? esc_attr( $this->order->phone ) : '' ); ?>" />
						</div>

					</div>

					<div class='fdm-field'>

						<div class='fdm-admin-label'>

							<label for="fdm_customer_email">
								<?php _e( 'Customer Email', 'food-and-drink-menu' ); ?>
							</label>

						</div>

						<div class='fdm-admin-input'>
							<input type='text' name="fdm_customer_email" value="<?php echo ( ! empty( $this->order->email ) ? esc_attr( $this->order->email ) : '' ); ?>" />
						</div>

					</div>

				</div>

			</div>

			<div class="fdm-dashboard-new-widget-box fsp-widget-box-full fdm-admin-edit-product-left-full-widget-box" id="fdm-admin-edit-customer-details-widget-box">
	
				<div class="fdm-dashboard-new-widget-box-top"><?php _e( 'Order Items', 'food-and-drink-menu' ); ?></div>
					
				<div class="fdm-dashboard-new-widget-box-bottom">
	
					<table class='wp-list-table widefat tags sorttable fields-list fdm-admin-order-items-table'>
	
						<thead>
	
							<tr>
								<th><?php _e( 'Delete?', 'food-and-drink-menu' ); ?></th>
								<th><?php _e( 'Item', 'food-and-drink-menu' ); ?></th>
								<th><?php _e( 'Quantity', 'food-and-drink-menu' ); ?></th>
								<th><?php _e( 'Price', 'food-and-drink-menu' ); ?></th>
								<th><?php _e( 'Additional Info', 'food-and-drink-menu' ); ?></th>
							</tr>
	
						</thead>
	
						<tbody>

							<?php foreach ( $this->order->get_order_items() as $order_item ) {
									
									echo $this->render_admin_order_item( $order_item );
	
								} ?>

							<tr>
								<td class='fdm-admin-add-item-to-order' colspan="5">
									<?php _e( 'Add an item', 'food-and-drink-menu' ); ?>
								</td>
							</tr>
	
						</tbody>
	
					</table>

					<div class='fdm-admin-add-item-to-order-items-table fdm-hidden'>

						<div class='fdm-admin-add-item-to-order-cancel'>
							<?php _e( 'Back', 'food-and-drink-menu' ); ?>
						</div>

						<?php 

							$args = array(
								'post_type'			=> FDM_MENUITEM_POST_TYPE,
								'posts_per_page'	=> -1,
								'post_status'		=> 'publish',
								'orderby'			=> 'title',
								'order'				=> 'ASC',
							);

							$menu_items = get_posts( $args );

						?>

						<?php foreach ( $menu_items as $menu_item ) { 

							$price_discount = get_post_meta( $menu_item->ID, 'fdm_item_price_discount', true );

							$prices = (array) get_post_meta( $menu_item->ID, 'fdm_item_price' );

							$all_prices = ! empty( $price_discount ) ? array_merge( array( esc_html( $this->get_label( 'label-discount' ) ) . $price_discount ), $prices ) : $prices;

							$ordering_options = is_array( get_post_meta( $menu_item->ID, '_fdm_ordering_options', true ) ) ? get_post_meta( $menu_item->ID, '_fdm_ordering_options', true ) : array(); 

							?>

							<div class='fdm-admin-add-item-to-order-item fdm-options-add-to-cart-button' data-postid='<?php echo esc_attr( $menu_item->ID ); ?>' data-options='<?php echo htmlspecialchars( json_encode( $ordering_options ), ENT_QUOTES, 'UTF-8' ); ?>' data-prices='<?php echo htmlspecialchars( json_encode( $all_prices ), ENT_QUOTES, 'UTF-8' ); ?>'>

								<span class='fdm-admin-add-item-to-order-item-plus-sign'>+</span>

								<?php echo esc_html( $menu_item->post_title ); ?>

							</div>

						<?php } ?>

						<div class='fdm-admin-add-item-to-order-modal fdm-ordering-popup fdm-hidden'>

							<div class='fdm-admin-add-item-to-order-modal-inner'>

								<div class='fdm-admin-add-item-title'>
									<?php _e( 'Item Details', 'food-and-drink-menu' ); ?>
								</div>

								<div class='fdm-admin-add-item-field'>

									<div class='fdm-admin-add-item-field-label'>
										<?php _e( 'Quantity', 'food-and-drink-menu' ); ?>:
									</div>

									<div class='fdm-admin-add-item-field-input'>
										<input class='fdm-admin-add-item-quantity' type='number' value='1' />
									</div>

								</div>

								<div class='fdm-admin-add-item-field'>

									<div class='fdm-admin-add-item-field-label'>
										<?php _e( 'Options', 'food-and-drink-menu' ); ?>:
									</div>

									<div class='fdm-admin-add-item-field-input' id='fdm-ordering-popup-options'></div>

								</div>

								<div class='fdm-admin-add-item-buttons'>

									<div class='fdm-admin-add-item-cancel'>
										<?php _e( 'Cancel', 'food-and-drink-menu' ); ?>
									</div>

									<div class='fdm-admin-add-item-submit' id='fdm-ordering-popup-submit'>

										<button>
											<?php _e( 'Add Item', 'food-and-drink-menu' ); ?>
										</button>

									</div>
								
								</div>

							</div>

						</div>

						<div class='fdm-admin-add-item-to-order-modal-background fdm-ordering-popup-background fdm-hidden'></div>

					</div>
	
				</div>
	
			</div>
	
		</div>

		<div class='fdm-admin-add-edit-order-sidebar'>
	
			<input type='submit' class='button-primary fdm-admin-edit-product-save-button' name='fdm_admin_order_submit' value='<?php echo ( empty( $this->order->id ) ? __( 'Save Order', 'food-and-drink-menu' ) : __( 'Update Order', 'food-and-drink-menu' ) ); ?>' />

			<div class="fdm-dashboard-new-widget-box fsp-widget-box-full" id="fdm-admin-edit-order-custom-fields-widget-box">
					
				<div class="fdm-dashboard-new-widget-box-top"><?php _e( 'Order Status', 'food-and-drink-menu' ); ?></div>
					
				<div class="fdm-dashboard-new-widget-box-bottom">
					
					<div class='fdm-field'>

						<div class='fdm-admin-label'>

							<label for="fdm_order_status">
								<?php _e( 'Order Status', 'food-and-drink-menu' ); ?>
							</label>

						</div>

						<div class='fdm-admin-input'>

							<?php $order_statuses = fdm_get_order_statuses(); ?>

							<select name='fdm_order_status'>

								<?php foreach ( $order_statuses as $status_slug => $status ) { ?>

									<option value='<?php echo esc_attr( $status_slug ); ?>' <?php echo ( (! empty( $this->order->post_status ) and $this->order->post_status == $status_slug ) ? 'selected' : '' ); ?>>
										<?php echo esc_html( $status['label'] ); ?>
									</option>

								<?php } ?>

							</select>

						</div>

					</div>

					<?php if ( ! empty( $this->get_option( 'fdm-enable-ordering-eta' ) ) ) { ?>

						<div class='fdm-field'>

							<div class='fdm-admin-label'>
	
								<label for="fdm_order_eta">
									<?php _e( 'Order ETA', 'food-and-drink-menu' ); ?>
								</label>
	
							</div>
	
							<div class='fdm-admin-input'>
								<input type='text' name="fdm_order_eta" value="<?php echo ( ! empty( $this->order->estimated_time ) ? esc_attr( $this->order->estimated_time ) : '' ); ?>" />
							</div>
	
						</div>

					<?php } ?>

					<div class='fdm-field'>

						<div class='fdm-admin-label'>

							<label for="fdm_order_total">
								<?php _e( 'Order Total', 'food-and-drink-menu' ); ?>
							</label>

						</div>

						<div class='fdm-admin-input'>
							<span id='fdm_order_total'><?php echo esc_html( fdm_format_price( $this->order->get_order_total_tax_in() ) ); ?></span>
						</div>

					</div>

					<div class='fdm-field'>

						<div class='fdm-admin-label'>

							<label for="fdm_payment_amount">
								<?php _e( 'Payment Amount', 'food-and-drink-menu' ); ?>
							</label>

						</div>

						<div class='fdm-admin-input'>
							<input type='text' name="fdm_payment_amount" value="<?php echo ( ! empty( $this->order->payment_amount ) ? esc_attr( $this->order->payment_amount ) : '' ); ?>" />
						</div>

					</div>

					<?php if ( ! empty( $this->order->receipt_id ) ) { ?>

						<div class='fdm-field'>

							<div class='fdm-admin-label'>
	
								<label for="fdm_receipt_id">
									<?php _e( 'Receipt ID', 'food-and-drink-menu' ); ?>
								</label>
	
							</div>
	
							<div class='fdm-admin-input'>
								<span id='fdm_receipt_id'><?php echo esc_html( $this->order->receipt_id ); ?></span>
							</div>
	
						</div>

					<?php } ?>
	
				</div>
	
			</div>

			<?php $fields = $fdm_controller->settings->get_ordering_custom_fields(); ?>

			<?php if ( ! empty( $fields ) ) { ?>

				<div class="fdm-dashboard-new-widget-box fsp-widget-box-full" id="fdm-admin-edit-order-custom-fields-widget-box">
					
					<div class="fdm-dashboard-new-widget-box-top"><?php _e('Custom Fields', 'food-and-drink-menu'); ?></div>
					
					<div class="fdm-dashboard-new-widget-box-bottom">
	
						<?php foreach ( $fields as $field ) { ?>

							<?php $field_value = ! empty( $this->order->custom_fields[ $field->slug ] ) ? $this->order->custom_fields[ $field->slug ]  : ''; ?>

							<div class='fdm-ordering-custom-field'>
	
								<div class='fdm-ordering-custom-field-label'>
									<?php echo esc_html( $field->name ); ?>
								</div>
					
								<div class='fdm-ordering-custom-field-input'>
					
									<?php if ( $field->type == 'section' ) : ?>
					
									<?php elseif ( $field->type == 'textarea' ) : ?>
					
										<textarea name='<?php echo esc_attr( $field->slug ); ?>'><?php echo esc_html( $field_value ); ?></textarea>
					
									<?php elseif ( $field->type == 'select' ) : ?>
					
										<select name='<?php echo esc_attr( $field->slug ); ?>'>
					
											<?php $field_values = explode( ",", $field->values ); ?>
					
											<?php foreach ( $field_values as $value ) { ?>
												<option <?php echo ( sanitize_title( $value ) == $field_value ? 'selected' : '' ); ?> value='<?php echo esc_attr( sanitize_title( $value ) ); ?>' ><?php echo esc_html( $value ); ?></option>
											<?php } ?>
					
										</select>
					
									<?php elseif ( $field->type == 'checkbox' ) : ?>
					
										<?php $field_values = explode( ",", $field->values ); ?>

										<?php $field_value = is_array( $field_value ) ? $field_value : array(); ?>
					
										<?php foreach ( $field_values as $value ) { ?>
											<input type='checkbox' <?php echo ( in_array( $value, $field_value ) ? 'selected' : '' ); ?> name='<?php echo esc_attr( $field->slug ); ?>[]' value='<?php echo esc_attr( sanitize_title( $value ) ); ?>' /><?php echo esc_html( $value );?><br />
										<?php } ?>

									<?php else : ?>
					
										<input type='text' name='<?php echo esc_attr( $field->slug ); ?>' value='<?php echo esc_html( $field_value ); ?>' />
					
									<?php endif; ?>
					
								</div>
				
							</div>
	
						<?php } ?>
	
					</div>
	
				</div>

			<?php } ?>

		</div>

	</form>

</div>