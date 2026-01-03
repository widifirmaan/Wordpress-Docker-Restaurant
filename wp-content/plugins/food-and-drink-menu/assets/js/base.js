jQuery( document ).ready( function() {

	fdm_section_image_text_position();
	fdm_image_style_image_height();

	jQuery(window).resize( function() {

		fdm_section_image_text_position();
		fdm_image_style_image_height();
	});

	// run 3 seconds after page load, for the WordPress editor preview area
	setTimeout( fdm_section_image_text_position, 3000 );
	setTimeout( fdm_image_style_image_height, 3000 );
});

function fdm_section_image_text_position() {

	jQuery('.fdm-section-background-image .fdm-section-header-image-area').each(function(){
		var this_fdm_section = jQuery(this);
		var fdm_section_image_height = this_fdm_section.height();
		var fdm_section_h3_height = this_fdm_section.find('.h3-on-image').height();
		var fdm_section_h3_top = ( (fdm_section_image_height / 2) - (fdm_section_h3_height / 2) );
		this_fdm_section.find('.h3-on-image').css('top', fdm_section_h3_top+'px');
	});
}

function fdm_image_style_image_height() {

	jQuery('.fdm-image-style-image-wrapper').each(function(){
		var thisImageWrapper = jQuery(this);
		var thisImageWrapperWidth = thisImageWrapper.width();
		thisImageWrapper.css('height', thisImageWrapperWidth+'px');
	});
	var maxHeight = -1;
	jQuery('.fdm-menu-image .fdm-item').each(function(){
		maxHeight = maxHeight > jQuery(this).height() ? maxHeight : jQuery(this).height();
	});
	jQuery('.fdm-menu-image .fdm-item').each(function(){
		jQuery(this).height(maxHeight);
	});
}

jQuery(document).ready(function($){
	jQuery('.fdm-item .fdm-item-title').on('click', function() {
		let $this = jQuery(this).parents('.fdm-item').eq(0);
		
		// Load Lightbox
		if($this.hasClass('fdm-item-ajax-open')) {
			var post_id = $this.data('postid');

			if ( post_id == '' ) { return; }

			loadLighbox(post_id);
		}

		if($this.hasClass('fdm-item-newpage-open')) {
			var permalink = $this.data('permalink');

			if ( permalink == '' ) { return; }

			// Open new tab/window with this permalink
			var new_win = window.open(permalink, '_blank', 'noopener');
		}
	});

	jQuery(document)
		.on(
			'submit', 
			'.fdm-details-div .fdm-item-ajax-open .grfwp-submit-review form',
			function (ev) {
				var _form = jQuery(this);
				var fdm_item = _form.serializeArray().find(x => 'fdm_menu_item_id' == x.name ? x.value : false);
				var data = _form.serialize() + '&action=fdm_grfwp_handle_submitted_review';
				
				jQuery.post(ajaxurl, data, function(response) {
					_form.find('h5.grfwp-fdm-lb-alert.error').remove();

					response.message = response.data.message;
					if(response && response.success) {
						loadLighbox(fdm_item.value, function () {
							jQuery('.fdm-details-div .fdm-item-ajax-open .grfwp-submit-review form')
								.prepend(`<span class="grfwp-fdm-lb-alert success">${response.message}</span>`);

							jQuery('.fdm-details-div .fdm-details-div-inside')
								.animate({scrollTop: jQuery(".fdm-details-div .fdm-item-panel .fdm-reviews-wrapper")[0].offsetHeight}, 750);
						});
					}
					else {
						_form.prepend(`<h5 class="grfwp-fdm-lb-alert error">${response.message}</h5>`);
					}
				});

				return false;
			}
		);

	jQuery( '.fdm-details-div-exit, .fdm-details-div' ).on( 'click', function() {
		
		jQuery( '.fdm-details-div' ).addClass( 'fdm-hidden' );
	});

	jQuery( '.fdm-details-div-inside' ).on( 'click', function( event ) {

		event.stopPropagation();
	} );
});

function loadLighbox(post_id, callback = undefined) {
	jQuery('.fdm-details-div').removeClass('fdm-hidden');
	jQuery('.fdm-details-div-content').html('Loading...');

	var data = 'post_id=' + post_id + '&action=fdm_menu_item_details';
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('.fdm-details-div-content').html(response);
		callback && callback();
	});
}