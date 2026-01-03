jQuery(document).ready(function(){

	jQuery('.fdm-menu-sidebar-section-title').attr('tabindex', '0');
	jQuery('.fdm-menu-sidebar-section-title:first-of-type').addClass('fdm-menu-sidebar-section-title-selected');
	jQuery('.fdm-menu-sidebar-section-description:nth-of-type(2)').removeClass('fdm-hidden');

	if(fdmFromSettings.sidebar_click_action == 'onlyselected'){
		jQuery('.fdm-the-menu').addClass('onlyselected');
		jQuery('.fdm-pattern-menu-no-sidebar .fdm-the-menu').removeClass('onlyselected');
		jQuery('.fdm-section').addClass('fdm-hidden');
		jQuery('.fdm-column:first-of-type .fdm-section:first-of-type').removeClass('fdm-hidden');
		jQuery('.fdm-pattern-menu-no-sidebar .fdm-section').removeClass('fdm-hidden');
	}

	jQuery('.fdm-menu-sidebar-section-title').click(function(){
		var thisSection = jQuery(this).attr('id');
		jQuery('.fdm-menu-sidebar-section-title').removeClass('fdm-menu-sidebar-section-title-selected');
		jQuery('.fdm-menu-sidebar-section-description').addClass('fdm-hidden');
		jQuery('.fdm-menu-sidebar-section-title#'+thisSection).addClass('fdm-menu-sidebar-section-title-selected');
		jQuery('.fdm-menu-sidebar-section-description#'+thisSection).removeClass('fdm-hidden');
		if(fdmFromSettings.sidebar_click_action == 'scroll'){
			jQuery('html, body').animate({
				scrollTop: jQuery('#fdm-section-header-'+thisSection).offset().top - 120
			}, 500);
		}
		if(fdmFromSettings.sidebar_click_action == 'onlyselected'){
			jQuery('.fdm-section').addClass('fdm-hidden');
			jQuery('.fdm-section-'+thisSection).removeClass('fdm-hidden');
		}
		jQuery('.fdm-image-style-image-wrapper').each(function(){
			var thisImageWrapper = jQuery(this);
			var thisImageWrapperWidth = thisImageWrapper.width();
			thisImageWrapper.css('height', thisImageWrapperWidth+'px');
		});
	});

	// HIDDEN/EXPANDABLE MOBILE SIDEBAR
	jQuery( '.fdm-sidebar-mobile-expand-button' ).on( 'click', function() {

		if ( jQuery( '.fdm-sidebar-mobile-expand-button' ).hasClass( 'open' ) ) {
			jQuery( '.fdm-sidebar' ).hide();
			jQuery( '.fdm-sidebar-mobile-expand-button' ).removeClass( 'open' );
		}
		else {
			if ( fdmFromSettings.menu_style == 'ordering' ) {
				jQuery( '.fdm-sidebar' ).css( 'display', 'flex' );
			}
			else {
				jQuery( '.fdm-sidebar' ).show();
			}
			jQuery( '.fdm-sidebar-mobile-expand-button' ).addClass( 'open' );
		}
	});

	if ( fdmFromSettings.menu_style == 'ordering' ) {
		jQuery( window ).bind( 'resize', mobileAlternateSidebarResize );
	}
	else {
		jQuery( window ).bind( 'resize', mobileSidebarResize );
	}
});

function mobileSidebarResize() {
	
	if ( jQuery( window ).width() > 568 ) {
		jQuery( '.fdm-sidebar-mobile-expand-1 .fdm-sidebar' ).show();
		jQuery( '.fdm-sidebar-mobile-expand-button' ).removeClass( 'open' );
	}
	else {
		if ( jQuery( '.fdm-sidebar-mobile-expand-button' ).hasClass( 'open' ) ) {
			jQuery( '.fdm-sidebar-mobile-expand-1 .fdm-sidebar' ).show();
		}
		else {
			jQuery( '.fdm-sidebar-mobile-expand-1 .fdm-sidebar' ).hide();
		}
	}
}

function mobileAlternateSidebarResize() {

	if ( jQuery( window ).width() > 768 ) {
		jQuery( '.fdm-sidebar-mobile-expand-1 .fdm-sidebar' ).css( 'display', 'flex' );
		jQuery( '.fdm-sidebar-mobile-expand-button' ).removeClass( 'open' );
	}
	else {
		if ( jQuery( '.fdm-sidebar-mobile-expand-button' ).hasClass( 'open' ) ) {
			jQuery( '.fdm-sidebar-mobile-expand-1 .fdm-sidebar' ).css( 'display', 'flex' );
		}
		else {
			jQuery( '.fdm-sidebar-mobile-expand-1 .fdm-sidebar' ).hide();
		}
	}
}
