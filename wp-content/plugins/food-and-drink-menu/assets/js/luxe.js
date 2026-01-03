jQuery( document ).ready( function() {

	jQuery( '.fdm-item-panel' ).each( function() {

		var thisItemPanel = jQuery( this );
		var thisItemTitle = thisItemPanel.find( '.fdm-item-title' );
		var thisItemTitleWidth = thisItemTitle.width();
		var thisItemTitleWidthPlusPadding = thisItemTitleWidth + 16;
		var thisItemSpecial = thisItemPanel.find( '.fdm-item-special' );
		thisItemSpecial.css( 'position', 'absolute');
		thisItemSpecial.css( 'top', '0px');
		thisItemSpecial.css( 'left', thisItemTitleWidthPlusPadding+'px');
	});
});