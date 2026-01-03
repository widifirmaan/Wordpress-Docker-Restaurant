var key = fdm_stripe_payment.stripe_mode == 'test' ? fdm_stripe_payment.test_publishable_key : fdm_stripe_payment.live_publishable_key;

if ( fdm_stripe_payment.stripe_sca ) {
  var _stripe = Stripe( key );
}
else {
  Stripe.setPublishableKey( key );
}

var elements;
var paymentElement;

jQuery(document).ready(function($) {

	var form = document.getElementById( 'stripe-payment-form' );

	if ( form === null ) { return; }

  	form.addEventListener( 'submit', async (event) => {

  		event.preventDefault();

		if ( ! fdm_validate_ordering_fields() ) { return false; }

		// disable the submit button to prevent repeated clicks
		disable_payment_form();

		var form$ = jQuery( form );

		var permalink = form$.data( 'permalink' );

		if ( ! jQuery( '#fdm-order-id' ).length ) {

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
				nonce: fdm_stripe_payment.nonce,
				post_status: 'draft',
				action: 'fdm_submit_order'
			});
	
			var response = await jQuery.post( ajaxurl, data ).promise();
	
			if ( ! response.success ) {
				jQuery( '#fdm-order-submit-button' ).before( '<p>Order could not be processed. Please contact the site administrator.' );
	
				return;
			}
	
			form$.append( "<input type='hidden' id='fdm-order-id' name='order_id' value='" + response.data.order_id + "'/>" );
		}

		// send the card details to Stripe
		if ( fdm_stripe_payment.stripe_sca ) {
				
			const { error: submitError } = await elements.submit();

			if ( submitError ) {

				error_handler( submitError );
				console.log( 'FDM-Stripe error: ', submitError );

				return;
			}


			// Call your backend to create the Checkout Session
			var params = {
				'nonce': fdm_stripe_payment.nonce,
				'action': 'fdm_stripe_get_intent',
				'order_id': jQuery( '#fdm-order-id' ).val(),
				'payment_amount': jQuery( '#fdm-ordering-sidescreen-stripe-total' ).val(),
				'return_url': permalink,
			};

			$.post(ajaxurl, params, function(result) {
				result = JSON.parse(result);
				if( result.success ) {

	          		var clientSecret = result.clientSecret;
	
					_stripe.confirmPayment({ 
						elements,
						clientSecret,
						confirmParams: {
							return_url: result.redirect_url,
						},
						redirect: 'if_required',
						params: {
							billing_details: {
								name: result.name,
								email: result.email,
							}
						}
					}).then( function(result) {
						params = {
							nonce: fdm_stripe_payment.nonce,
							action: 'fdm_stripe_payment_succeed', 
							order_id: jQuery( '#fdm-order-id' ).val()
						};
	
						if (result.error) {
							// Show error to your customer (e.g., insufficient funds)
							params['success'] = false;
							params['message'] = result.error.message;
							error_handler(result.error.message);
						}
	            		else {
							var pi = result.paymentIntent;
	
							// The payment has been processed!
							if (pi.status === 'succeeded' || pi.status === 'requires_capture') {
							  params['success'] = true;
							  params['payment_amount'] = pi.amount;
							  params['payment_id'] = pi.id;
							  // params['payment_intent'] = pi;
							}
							else {
							  params['success'] = false;
							  params['message'] = 'Unknown error';
							}
						}
	
						$.post(ajaxurl, params, function (result) {
							result = JSON.parse(result);
	
							if ( true == result.success ) {
	
								var url = new URL(window.location.pathname, window.location.origin);
	
								for ( const [key, value] of Object.entries(result.urlParams) ) {
									url.searchParams.append(key, value);
								}
	
								window.location = url.href;
							}
							else {
								error_handler(result.message);
								console.log('FDM-Stripe error: ', result.message);
							}
						});
					});
        		}
        		else {
        			error_handler(result.message);
        			console.log('FDM-Stripe error: ', result.message);
        		}
			});
		}
		else {

			// send the card details to Stripe
			Stripe.createToken({
				number: $('input[data-stripe="card_number"]').val(),
				cvc: $('input[data-stripe="card_cvc"]').val(),
				exp_month: $('input[data-stripe="exp_month"]').val(),
				exp_year: $('input[data-stripe="exp_year"]').val(),
				currency: $('input[data-stripe="currency"]').val()
			}, function ( response ) {
		
				if (response.error) {
		
					error_handler( response.error.message );
				}
				else {
		
					// token contains id, last4, and card type
					var token = response['id'];
					// insert the token into the form so it gets submitted to the server
					form$.append( "<input type='hidden' name='stripeToken' value='" + token + "'/>" );
			
					form$.get(0).submit();
				}
			});
		}

		// prevent the form from submitting with the default action
		return false;
	} );

	jQuery( '#fdm-ordering-sidescreen-total-value' ).on( 'fdm_cart_total_updated', function( event, total_price ) {

		// setup payment element on cart total change
		if ( ! fdm_stripe_payment.stripe_sca ) { return; }

		total_price = fdm_stripe_payment.currency.toLowerCase() == 'jpy' ? total_price : total_price * 100;
	    
	    var options = {
	    	mode: 'payment',
	    	theme: 'flat',
	    	amount: parseInt( total_price ),
	    	currency: fdm_stripe_payment.currency.toLowerCase(),
	    	captureMethod: fdm_stripe_payment.hold ? 'manual' : 'automatic',
	    };
	
	    elements = _stripe.elements( options );
	    paymentElement = elements.create('payment');
	    paymentElement.mount('#fdm-payment-element');
	    
	    paymentElement.on( 'change', function(ev) {
	    	
	    	if ( ev.complete ) {
	    		// enable payment button
	    		enable_payment_form();
	    	}
	    	else {
	    		if ( ev.error ) {
	        		error_handler( ev.error.message );
	    		}
	    	}
	    });

	} );
});

function error_handler( msg = '' ) {

	jQuery( '.payment-errors' ).html( msg );

	enable_payment_form();
}

function disable_payment_form() {

	jQuery( '.payment-errors' ).html( '' );

	fdm_stripe_payment.stripe_sca && jQuery( '.stripe-payment-help-text' ).slideDown();

	jQuery( '#stripe-submit' ).prop( 'disabled', true );
}

function enable_payment_form() {

	fdm_stripe_payment.stripe_sca && jQuery( '.stripe-payment-help-text' ).slideUp();

	jQuery( '#stripe-submit' ).prop( 'disabled', false );
}