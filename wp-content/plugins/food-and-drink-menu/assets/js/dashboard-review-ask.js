jQuery(document).ready(function() {
	jQuery('.fdm-main-dashboard-review-ask').css('display', 'block');

  jQuery(document).on('click', '.fdm-main-dashboard-review-ask .notice-dismiss', function(event) {

  	var params = {
			ask_review_time: '7',
			nonce: fdm_review_ask.nonce,
			action: 'fdm_hide_review_ask'
		};

		var data = jQuery.param( params );

    jQuery.post(ajaxurl, data, function() {});
  });

	jQuery('.fdm-review-ask-yes').on('click', function() {
		jQuery('.fdm-review-ask-feedback-text').removeClass('fdm-hidden');
		jQuery('.fdm-review-ask-starting-text').addClass('fdm-hidden');

		jQuery('.fdm-review-ask-no-thanks').removeClass('fdm-hidden');
		jQuery('.fdm-review-ask-review').removeClass('fdm-hidden');

		jQuery('.fdm-review-ask-not-really').addClass('fdm-hidden');
		jQuery('.fdm-review-ask-yes').addClass('fdm-hidden');

		var params = {
			ask_review_time: '7',
			nonce: fdm_review_ask.nonce,
			action: 'fdm_hide_review_ask'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function() {});
	});

	jQuery('.fdm-review-ask-not-really').on('click', function() {

		jQuery('.fdm-review-ask-review-text').removeClass('fdm-hidden');
		jQuery('.fdm-review-ask-starting-text').addClass('fdm-hidden');

		jQuery('.fdm-review-ask-feedback-form').removeClass('fdm-hidden');
		jQuery('.fdm-review-ask-actions').addClass('fdm-hidden');

		var params = {
			ask_review_time: '1000',
			nonce: fdm_review_ask.nonce,
			action: 'fdm_hide_review_ask'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function() {});
	});

	jQuery('.fdm-review-ask-no-thanks').on('click', function() {
		
		var params = {
			ask_review_time: '1000',
			nonce: fdm_review_ask.nonce,
			action: 'fdm_hide_review_ask'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function() {});

    jQuery('.fdm-main-dashboard-review-ask').css('display', 'none');
	});

	jQuery('.fdm-review-ask-review').on('click', function() {

		jQuery('.fdm-review-ask-feedback-text').addClass('fdm-hidden');
		jQuery('.fdm-review-ask-thank-you-text').removeClass('fdm-hidden');

		var params = {
			ask_review_time: '1000',
			nonce: fdm_review_ask.nonce,
			action: 'fdm_hide_review_ask'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function() {});
	});

	jQuery('.fdm-review-ask-send-feedback').on('click', function() {

		var feedback = jQuery('.fdm-review-ask-feedback-explanation textarea').val();
		var email_address = jQuery('.fdm-review-ask-feedback-explanation input[name="feedback_email_address"]').val();
		
    var params = {
			feedback: feedback,
			email_address: email_address,
			nonce: fdm_review_ask.nonce,
			action: 'fdm_send_feedback'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function() {});

    var params = {
			ask_review_time: '1000',
			nonce: fdm_review_ask.nonce,
			action: 'fdm_hide_review_ask'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function() {});

    jQuery('.fdm-review-ask-feedback-form').addClass('fdm-hidden');
    jQuery('.fdm-review-ask-review-text').addClass('fdm-hidden');
    jQuery('.fdm-review-ask-thank-you-text').removeClass('fdm-hidden');
	});
});