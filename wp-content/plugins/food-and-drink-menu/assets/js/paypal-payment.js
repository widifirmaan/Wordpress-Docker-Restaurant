jQuery(document).ready(function($) {
	$("#paypal-payment-form").submit(function(event) {

		// check for blank required fields
		if ( 
				(jQuery( 'input[name="fdm_ordering_name"]' ).is( '[required]') && jQuery( 'input[name="fdm_ordering_name"]' ).val() == '') || 
				(jQuery( 'input[name="fdm_ordering_email"]' ).is( '[required]') && jQuery( 'input[name="fdm_ordering_email"]' ).val() == '') || 
				(jQuery( 'input[name="fdm_ordering_phone"]' ).is( '[required]') && jQuery( 'input[name="fdm_ordering_phone"]' ).val() == '') 
			) {

			jQuery( '<p class="fdm-message">Please make sure all required fields have been filled in before submitting</p>' ).insertBefore( this ).delay( 6000 ).queue( function() { jQuery( '.fdm-message').remove(); } );

			return false;
		}

		if ( fdm_ordering_data.minimum_order && parseFloat( jQuery( '#fdm-ordering-sidescreen-total-value' ).html() ) < fdm_ordering_data.minimum_order ) {

			jQuery( '<p class="fdm-message">There is a minimum of ' + fdm_ordering_data.price_prefix + fdm_ordering_data.minimum_order + fdm_ordering_data.price_suffix + ' to place an order.</p>' ).insertBefore( this ).delay( 6000 ).queue( function() { jQuery( '.fdm-message').remove(); } );

			return false;
		}

		// disable the submit button to prevent repeated clicks
		$('#paypal-submit').attr("disabled", "disabled");

		var form$ = jQuery("#paypal-payment-form");

		var permalink = jQuery( '#paypal-submit' ).data( 'permalink' );

		var name = jQuery( 'input[name="fdm_ordering_name"]' ).val();
		var email = jQuery( 'input[name="fdm_ordering_email"]' ).val();
		var phone = jQuery( 'input[name="fdm_ordering_phone"]' ).val();
		var note = jQuery( 'textarea[name="fdm_ordering_note"]' ).val();

		var custom_fields = {};
		jQuery( '.fdm-ordering-custom-fields' ).find( 'input, textarea, select' ).each( function() {
			custom_fields[ this.name ] = jQuery( this ).val(); 
		});
		jQuery( '.fdm-ordering-custom-fields' ).find( 'input:checked' ).each( function() {
			let index = jQuery( this ).data( 'slug' );
			custom_fields[ index ] = Array.isArray( custom_fields[ index ] ) ? custom_fields[ index ] : [];
			custom_fields[ index ].push( jQuery( this ).val() );
		}).get();

		var data = jQuery.param({
			permalink: permalink,
			name: name,
			email: email,
			phone: phone,
			note: note,
			custom_fields: custom_fields,
			post_status: 'draft',
			nonce: fdm_paypal_payment.nonce,
			action: 'fdm_submit_order'
		});

		jQuery.post( ajaxurl, data, function( response ) {

			if ( ! response.success ) { 
				jQuery( '#fdm-order-submit-button' ).before( '<p>Order could not be processed. Please contact the site administrator.' );

				return;
			}

			form$.append("<input type='hidden' name='custom' value='order_id=" + response.data.order_id + "'/>");

			// submit form
			form$.get(0).submit();
		});

		// prevent the form from submitting with the default action
		return false;
	});
});