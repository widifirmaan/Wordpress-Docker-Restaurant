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

jQuery( document ).ready( function() {

	fdm_image_style_image_height();

	jQuery(window).resize( function() {

		fdm_image_style_image_height();
	});

	// jQuery('.fdm-menu-image .fdm-item-content p').text(function(index, currentText) {
	// 	return currentText.substr(0,85);
	// });
});
