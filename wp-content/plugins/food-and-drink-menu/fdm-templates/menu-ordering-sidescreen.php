<?php 
	global $fdm_controller;
?>

<div id='fdm-ordering-sidescreen-tab' class='fdm-hidden'><span class='dashicons dashicons-cart'></span><span id='fdm-ordering-sidescreen-tab-count'></span></div>

<div class='fdm-ordering-bottom-cart-overlay'></div>

<div class='fdm-ordering-bottom-bar fdm-hidden'>
	<div class='fdm-ordering-bottom-bar-inside'>
		<div class='fdm-ordering-bottom-bar-left'>
			<div class='fdm-ordering-bottom-bar-toggle'>
				<div class='fdm-ordering-bottom-bar-toggle-inside'>
					<span class='dashicons dashicons-arrow-up-alt2'></span>
				</div>
			</div>
			<div class='fdm-ordering-bottom-bar-label'>
				<?php echo esc_html( $this->get_label( 'label-your-order' ) ); ?> (<span id='fdm-ordering-bottom-bar-quantity'>0</span>)
			</div>
		</div>
		<div class='fdm-ordering-bottom-bar-right'>
			<div class='fdm-ordering-bottom-bar-total-label'>
				<?php echo esc_html( $this->get_label( 'label-total' ) ); ?>
			</div>
			<div class='fdm-ordering-bottom-bar-total-value'>
				<?php
					echo $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'before' ? esc_html( $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) ) : '';
					echo '<span id="fdm-ordering-bottom-bar-total">0</span>';
					echo $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'after' ? esc_html( $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) ) : '' ;
				?>
			</div>
			<div class='fdm-ordering-bottom-bar-checkout-button'>
				<?php echo esc_html( $this->get_label( 'label-check-out' ) ); ?>
			</div>
		</div>
	</div>
</div>

<div <?php echo fdm_format_classes( $this->classes ); ?>>
	
	<h3 id='fdm-ordering-sidescreen-header'>
		<div id='fdm-ordering-sidescreen-close'>
			<span class='<?php echo ( $fdm_controller->settings->get_setting( 'fdm-order-cart-location' ) == 'side' ? 'dashicons dashicons-arrow-right-alt' : 'dashicons dashicons-dismiss' ); ?>'></span>
		</div>
		<?php echo esc_html( $this->get_label( 'label-order-summary' ) ); ?>
	</h3>

	<div id='fdm-ordering-sidescreen-items-and-button'>

		<div id='fdm-ordering-sidescreen-items'>
			<?php 
				$cart_items = $fdm_controller->cart->get_cart_items();
				foreach ( $cart_items as $cart_item ) {
					$item = new fdmViewItem( (array) $cart_item );
					$item->load_item();

					echo $item->cart_render();
				}
			?>
		</div>

		<div class='clearfix'></div>

		<a class='fdm-continue-shopping-button'><?php echo esc_html( $this->get_label( 'label-add-another-item' ) ); ?></a>

	</div> <!-- fdm-ordering-sidescreen-items-and-button -->

	<div id='fdm-ordering-sidescreen-totals'>

		<div id='fdm-ordering-sidescreen-quantity'>
			<div id='fdm-ordering-sidescreen-quantity-items'>
				<span id='fdm-ordering-sidescreen-quantity-number'>0</span> <span id='fdm-ordering-sidescreen-quantity-text'><?php echo esc_html( $this->get_label( 'label-item-s-in-cart' ) ); ?></span>
			</div>
			<div class='fdm-clear-cart-button'>
				<?php echo esc_html( $this->get_label( 'label-clear' ) ); ?>
			</div>
		</div>

		<div id='fdm-ordering-sidescreen-tax'<?php echo $this->maybe_add_hidden_to_tax_row_item(); ?>>
			<div id='fdm-ordering-sidescreen-tax-label'>
				<?php echo esc_html( $this->get_label( 'label-tax' ) ); ?>
			</div>
			<div id='fdm-ordering-sidescreen-tax-amount-container'>
				<?php
					echo $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'before' ? esc_html( $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) ) : '';
					echo '<span id="fdm-ordering-sidescreen-tax-amount-value">0</span>';
					echo $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'after' ? esc_html( $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) ) : '' ;
				?>
			</div>
		</div>

		<div id='fdm-ordering-sidescreen-total'>
			<div id='fdm-ordering-sidescreen-total-label'>
				<?php echo esc_html( $this->get_label( 'label-total' ) ); ?>
			</div>
			<div id='fdm-ordering-sidescreen-total-value-container'>
				<?php
					echo $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'before' ? esc_html( $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) ) : '';
					echo '<span id="fdm-ordering-sidescreen-total-value">0</span>';
					echo $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'after' ? esc_html( $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) ) : '' ;
				?>
			</div>
		</div>

	</div> <!-- fdm-ordering-sidescreen-totals -->
	
	<?php $required_fields = is_array( $fdm_controller->settings->get_setting( 'fdm-ordering-required-fields' ) ) ? $fdm_controller->settings->get_setting( 'fdm-ordering-required-fields' ) : array(); ?>
	
	<div id='fdm-ordering-contact-details'>

		<h3 id='fdm-ordering-sidescreen-contact-header'>
			<?php echo esc_html( $this->get_label( 'label-check-out' ) ); ?>
		</h3>
		<div class='fdm-ordering-contact-item'>
			<div class='fdm-ordering-contact-label <?php echo in_array( 'name', $required_fields )  ? 'fdm-ordering-required' : ''; ?>'><?php echo esc_html( $this->get_label( 'label-name' ) ); ?>:</div>
			<div class='fdm-ordering-contact-field'><input type='text' name='fdm_ordering_name' <?php echo in_array( 'name', $required_fields )  ? 'required' : ''; ?> /></div>
		</div>
		<div class='fdm-ordering-contact-item'>
			<div class='fdm-ordering-contact-label <?php echo in_array( 'email', $required_fields )  ? 'fdm-ordering-required' : ''; ?>'><?php echo esc_html( $this->get_label( 'label-email' ) ); ?>:</div>
			<div class='fdm-ordering-contact-field'><input type='email' name='fdm_ordering_email' <?php echo in_array( 'email', $required_fields )  ? 'required' : ''; ?> /></div>
		</div>
		<div class='fdm-ordering-contact-item'>
			<div class='fdm-ordering-contact-label <?php echo in_array( 'phone', $required_fields )  ? 'fdm-ordering-required' : ''; ?>'><?php echo esc_html( $this->get_label( 'label-phone' ) ); ?>:</div>
			<div class='fdm-ordering-contact-field'><input type='tel' name='fdm_ordering_phone' <?php echo in_array( 'phone', $required_fields )  ? 'required' : ''; ?> /></div>
		</div>
		<div class='fdm-ordering-contact-item'>
			<div class='fdm-ordering-contact-label'><?php echo esc_html( $this->get_label( 'label-order-note' ) ); ?>:</div>
			<div class='fdm-ordering-contact-field'><textarea name='fdm_ordering_note' ></textarea></div>
		</div>

		<?php $fields = $fdm_controller->settings->get_ordering_custom_fields(); ?>

		<div class='fdm-ordering-custom-fields'>
		
			<?php foreach ( $fields as $field ) { ?>
	
				<div class='fdm-ordering-custom-field'>
	
				<div class='fdm-ordering-custom-field-label'>
					<?php echo esc_html( $field->name ); ?>
				</div>
	
				<div class='fdm-ordering-custom-field-input'>
	
					<?php if ( $field->type == 'section' ) : ?>
	
					<?php elseif ( $field->type == 'text' ) : ?>
	
						<input type='text' name='<?php echo esc_attr( $field->slug ); ?>' />
	
					<?php elseif ( $field->type == 'textarea' ) : ?>
	
						<textarea name='<?php echo esc_attr( $field->slug ); ?>'></textarea>
	
					<?php elseif ( $field->type == 'select' ) : ?>
	
						<select name='<?php echo esc_attr( $field->slug ); ?>'>
	
							<?php $field_values = explode( ",", $field->values ); ?>
	
							<?php foreach ( $field_values as $value ) { ?>
								<option value='<?php echo esc_attr( sanitize_title( $value ) ); ?>' ><?php echo esc_html( $value ); ?></option>
							<?php } ?>
	
						</select>
	
					<?php elseif ( $field->type == 'checkbox' ) : ?>
	
						<?php $field_values = explode( ",", $field->values ); ?>
	
						<?php foreach ( $field_values as $value ) { ?>
							<input type='checkbox' name='<?php echo esc_attr( $field->slug ); ?>[]' value='<?php echo esc_attr( sanitize_title( $value ) ); ?>' data-slug="<?php echo esc_attr( $field->slug ); ?>" /><?php echo esc_html( $value );?><br />
						<?php } ?>
	
					<?php endif; ?>
	
				</div>

			</div>	
	
			<?php } ?>

		</div>

	</div>

	<?php if ( $fdm_controller->settings->get_setting( 'enable-payment' ) and $fdm_controller->settings->get_setting( 'payment-optional' ) ) { ?>
		<div id='fdm-order-payment-toggle'>
			<div class='fdm-order-payment-toggle-option'>
				<input type='radio' name='fdm-payment-type-toggle' class='fdm-payment-type-toggle' value='pay-in-store' checked /><?php echo esc_html( $this->get_label( 'label-pay-in-store' ) ); ?>
			</div>
			<div class='fdm-order-payment-toggle-option'>
				<input type='radio' name='fdm-payment-type-toggle' class='fdm-payment-type-toggle' value='pay-online' /><?php echo esc_html( $this->get_label( 'label-pay-online' ) ); ?>
			</div>
		</div>
	<?php } ?>

	<?php if ( ! $fdm_controller->settings->get_setting( 'enable-payment' ) or $fdm_controller->settings->get_setting( 'payment-optional' ) ) { ?>
		<div id='fdm-order-submit'>
			<button id='fdm-order-submit-button' data-permalink='<?php echo get_permalink(); ?>'><?php echo esc_html( $this->get_label( 'label-submit-order' ) ); ?></button>
		</div>
	<?php } ?>

	<?php if ( $fdm_controller->settings->get_setting( 'enable-payment' ) ) { ?>

		<div id='fdm-order-payment-form-div' class='<?php echo $fdm_controller->settings->get_setting( 'payment-optional' ) ? 'fdm-hidden' : ''; ?>'>

			<div class='payment-errors'></div>

			<?php  if ( $fdm_controller->settings->get_setting( 'ordering-payment-gateway' ) == 'paypal' ) { ?>

				<?php $form_action = $fdm_controller->settings->get_setting( 'ordering-payment-mode' ) == 'test' ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr'; ?>
				<form action='<?php echo esc_attr( $form_action ); ?>' method='post' class='standard-form' id='paypal-payment-form'>
					<input type='hidden' name='item_name_1' value='<?php echo esc_attr( substr( get_bloginfo( 'name' ), 0, 100) ); ?> Order Payment' />
					<input type='hidden' name='custom' value='' />
					<input type='hidden' name='quantity_1' value='1' />
					<input type='hidden' name='amount_1' id='fdm-ordering-sidescreen-paypal-total' value='0' />
					<input type='hidden' name='cmd' value='_cart' />
					<input type='hidden' name='upload' value='1' />
					<input type='hidden' name='business' value='<?php echo esc_attr( $fdm_controller->settings->get_setting( 'paypal-email' ) ); ?>' />
					<input type='hidden' name='currency_code' value='<?php echo esc_attr( $fdm_controller->settings->get_setting( 'ordering-currency' ) ); ?>' />
					<input type='hidden' name='return' value='<?php echo get_permalink(); ?>' />
					<input type='hidden' name='notify_url' value='<?php echo get_site_url(); ?>' />			
					<input type='submit' id='paypal-submit' class='submit-button' data-permalink='<?php echo get_permalink(); ?>' value='<?php echo esc_attr( $this->get_label( 'label-pay-via-paypal' ) ); ?>' />
				</form>
			<?php } else { ?>

				<form action='#' method='POST' id='stripe-payment-form' data-permalink='<?php echo get_permalink(); ?>'>

					<?php if ( $fdm_controller->settings->get_setting( 'fdm-stripe-hold' ) ) { ?>

						<div class='form-row'>
        					<p><?php echo esc_html( $this->get_label( 'label-deposit-placing-hold' ) ); ?></p>
        				</div>
        			<?php } ?>

					<?php if ( $fdm_controller->settings->get_setting( 'fdm-stripe-sca' ) ) { ?>

						<div class='form-row'>
        					<span id="fdm-payment-element"></span>
        				</div>
					<?php } else { ?>

						<div class='form-row'>
							<label><?php echo esc_html( $this->get_label( 'label-card-number' ) ); ?></label>
							<input type='text' size='20' autocomplete='off' data-stripe='card_number'/>
						</div>
						<div class='form-row'>
							<label><?php echo esc_html( $this->get_label( 'label-cvc' ) ); ?></label>
							<input type='text' size='4' autocomplete='off' data-stripe='card_cvc'/>
						</div>
						<div class='form-row'>
							<label><?php echo esc_html( $this->get_label( 'label-expiration' ) ); ?></label>
							<input type='text' size='2' data-stripe='exp_month'/>
							<span> / </span>
							<input type='text' size='4' data-stripe='exp_year'/>
						</div>
						<input type='hidden' name='action' value='fdm_stripe_booking_payment'/>
						<input type='hidden' name='currency' value='<?php echo esc_attr( $fdm_controller->settings->get_setting( 'ordering-currency' ) ); ?>' data-stripe='currency' />
					<?php } ?>

					<p class="stripe-payment-help-text">
						<?php echo esc_html( $fdm_controller->settings->get_setting( 'label-please-wait' ) ); ?>
					</p>

					<input type='hidden' name='payment_amount' id='fdm-ordering-sidescreen-stripe-total' value='0' />
					
					<button type='submit' id='stripe-submit' <?php echo ( $fdm_controller->settings->get_setting( 'fdm-stripe-sca' ) ? "disabled='disabled'" : '' ); ?>>
						<?php echo esc_html( $this->get_label( 'label-pay-now' ) ); ?>
					</button>
				</form>
			<?php } ?>
		</div>
	<?php } ?>

</div>