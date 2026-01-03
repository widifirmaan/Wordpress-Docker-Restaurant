/**
 * Allow prices to be added and deleted on the Menu Item editing page and
 * list table
 *
 *  @since 1.5
 */
jQuery( function ( $ ) {
	var $prices = $( '.fdm-input-prices' );

	if ( !$prices.length ) {
		return;
	}

	// Open the price editing panel in the menu item list table
	$( '.fdm-item-list-price' ).click( function( e ) {
		var $target = $( e.target );

		if ( $target.hasClass( 'fdm-item-price-edit' ) ) {
			$(this).addClass( 'is-visible' );
			return false;
		}
	} );

	// Re-usable function to remove a price entry field
	function removePrice( e ) {
		priceChanged( e );
		$( this ).closest( '.fdm-input-control' ).remove();
		return false;
	}

	// Re-usable function to signal when prices have changed.
	// Only used on menu items list table.
	function priceChanged( e ) {
		var $form = $( e.target ).closest( '.fdm-item-price-form' );

		if ( !$form.length ) {
			return;
		}

		$( '.fdm-item-price-save', $form ).removeAttr( 'disabled' );
	}

	$prices.click( function( e ) { console.log(e);
		var $target = $( e.target ),
			$price_panel = $(this),
			$price_input = $price_panel.find( '.fdm-input-control' ).last(),
			$new_price_input = $price_input.clone();

		if ( !$target.hasClass( 'fdm-price-add' ) ) {
			return;
		}

		e.stopImmediatePropagation();

		$new_price_input.find( 'input[data-name="fdm_item_price"], input[name="fdm_item_price[]"]' ).val( '' );
		$price_input.after( $new_price_input );
		$new_price_input.find( 'input' ).focus();

		$( '.fdm-input-delete', $price_panel ).off()
			.click( removePrice );
		$( 'input[data-name="fdm_item_price"], input[name="fdm_item_price[]"]', $price_panel ).off()
			.keyup( priceChanged );

		return false;
	} );

	// Remove a price entry field
	$( '.fdm-input-delete', $prices ).click( removePrice );

	// Enable the update price button on the menu item list whenever a price
	// has changed.
	$( 'input[data-name="fdm_item_price"], input[name="fdm_item_price[]"]', $prices ).keyup( priceChanged );

	// Cancel button pushed
	$( '.fdm-item-price-cancel' ).on( 'click', function() {
		var $price_wrapper = $(this).closest( '.fdm-item-list-price' );

		$price_wrapper.removeClass( 'is-visible' );

		return false;
	});

	// Save price changes (only on menu item list table)
	var $submit = $( '.fdm-item-price-save' );
	if ( $submit.length ) {
		$submit.click( function( e ) {
			var $button = $(this),
				$spinner = $button.siblings( '.spinner' ),
				$price_wrapper = $button.closest( '.fdm-item-list-price' ),
				$price_summary = $price_wrapper.find( '.fdm-item-price-summary' ),
				menu_item_id = $price_wrapper.data( 'menu-item-id' ),
				$price_inputs = $price_wrapper.find( 'input[data-name="fdm_item_price"], input[name="fdm_item_price[]"]' ),
				prices = [],
				$message = $price_wrapper.find( '.fdm-item-price-message' ),
				params;

			if ( !menu_item_id ) {
				return false;
			}

			$button.attr( 'disabled', 'disabled' );
			$spinner.css( 'visibility', 'visible' );
			$message.empty();

			$price_inputs.each( function() {
				prices.push( $(this).val() );
			} );

			params = {
				id: menu_item_id,
				prices: prices,
				action: 'fdm-menu-item-price',
				nonce: fdm_settings.nonce,
			};

			// Allow third-party addons to hook in and add data
			$price_wrapper.trigger( 'save-item-price.fdm', params );

			$.post(
				ajaxurl,
				params,
				function( r ) {

					$button.removeAttr( 'disabled' );
					$spinner.css( 'visibility', 'hidden' );

					if ( typeof r === 'undefined' || typeof r.success === 'undefined' ) {
						$message.html( fdm_settings.i18n.undefined_error );
						return;
					}

					if ( r.success === false ) {
						if ( typeof r.data === 'undefined' || typeof r.data.msg === 'undefined' ) {
							$message.html( fdm_settings.i18n.undefined_error );
						} else {
							$message.html( r.data.msg );
						}
						return;
					}

					if ( typeof r.data.price_summary !== 'undefined' ) {
						$price_summary.html( r.data.price_summary );
					}
					$price_wrapper.removeClass( 'is-visible' );
				}
			);

			return false;
		} );
	}
} );

/**
 * Handle the Menu Organizer on the Menu editing page
 *
 *  @since 1.5
 */

var fdmMenuOrganizer = fdmMenuOrganizer || {};

jQuery( function ( $ ) {

	if ( !$( '#fdm-menu-organizer' ).length ) {
		return;
	}

	/**
	 * Column slugs used by the menu organizer
	 *
	 * @param array
	 * @since 1.5
	 */
	fdmMenuOrganizer.columns = ['fdm_menu_column_one', 'fdm_menu_column_two'];

	/**
	 * Modal for editing menu section names
	 *
	 * @param jQuery object
	 * @since 1.5
	 */
	fdmMenuOrganizer.$menu_section_modal = $( '#fdm-menu-section-modal' );

	/**
	 * Initialize the menu organizer
	 *
	 * @since 1.5
	 */
	fdmMenuOrganizer.init = function() {

		$( '.fdm-sortable-sections', '#fdm-menu-organizer' ).sortable( {
			connectWith: '.fdm-sortable-sections',
			placeholder: 'fdm-menu-sections-placeholder',
			delay: 150,
			handle: '.fdm-title',
			update: fdmMenuOrganizer.sectionsUpdated,
		} );

		for ( var i in fdmMenuOrganizer.columns ) {
			var column = fdmMenuOrganizer.columns[i];
			var ids = $( '#' + column ).val();
			if ( ids ) {
				ids = ids.split(',').filter(Boolean);
				for ( var s in ids ) {
					$( '#fdm-menu-sections-list > [data-term-id="' + ids[s] + '"]' )
						.appendTo( '#' + column + '_list' );
				}
			}
		}

		$( '#fdm-menu-section-modal-save' ).click( fdmMenuOrganizer.saveMenuSectionModal );

		$( '#fdm-menu-organizer' ).click( function( e ) {
			var $target = $( e.target ),
				section_id,
				section_title;

			if ( !$target.hasClass( 'fdm-edit-section-name' ) ) {
				return;
			}

			section_title = $target.siblings( '.fdm-title' ).find( '.fdm-term-name' ).text();
			section_id = $target.parent().data( 'term-id' );

			fdmMenuOrganizer.openMenuSectionModal( section_id, section_title );

			return false;
		} );

		fdmMenuOrganizer.$menu_section_modal.click( function( e ) {
			if ( $( e.target ).is ( fdmMenuOrganizer.$menu_section_modal ) ) {
				fdmMenuOrganizer.closeMenuSectionModal();
			}
		} );

		$( document ).keyup( function( e ) {
			if ( e.which == '27' ) {
				fdmMenuOrganizer.closeMenuSectionModal();
			}
		} );

	};

	/**
	 * Update the sections values
	 *
	 * @since 1.5
	 */
	fdmMenuOrganizer.sectionsUpdated = function( event, ui ) {

		function getIds( $list ) {
			var ids = [];
			$list.each( function() {
				ids.push( $(this).data( 'term-id' ) );
			} );
			return ids;
		}

		for ( var i in fdmMenuOrganizer.columns ) {
			var column = fdmMenuOrganizer.columns[i];
			$( '#' + column ).val( getIds( $( '#' + column + '_list > li' ) ) );
		}
	};

	/**
	 * Open the Menu Section title editing modal
	 *
	 * @param int id Section id
	 * @param string title Section title
	 * @since 1.5
	 */
	fdmMenuOrganizer.openMenuSectionModal = function( id, title ) {
		var $modal = fdmMenuOrganizer.$menu_section_modal;

		$modal.find( '#fdm-menu-section-modal-name' ).val( title );
		$modal.find( '#fdm-menu-section-modal-save' ).data( 'section-id', id );
		$modal.addClass( 'is-visible' );
	};

	/**
	 * Close the Menu Section title editing modal
	 *
	 * @since 1.5
	 */
	fdmMenuOrganizer.closeMenuSectionModal = function() {
		var $modal = fdmMenuOrganizer.$menu_section_modal;

		$modal.find( '#fdm-menu-section-modal-name' ).val( '' );
		$modal.find( '#fdm-menu-section-modal-save' ).data( 'section-id', '' );
		$modal.removeClass( 'is-visible' );
	};

	/**
	 * Save changes to the Menu Section title
	 *
	 * @since 1.5
	 */
	fdmMenuOrganizer.saveMenuSectionModal = function() {
		var $modal = fdmMenuOrganizer.$menu_section_modal,
			section_id,
			section_title,
			$section_input;

		section_title = $modal.find( '#fdm-menu-section-modal-name' ).val();
		section_id = $modal.find( '#fdm-menu-section-modal-save' ).data( 'section-id' );

		$section_input = $( '#fdm_menu_column_one' ).siblings( '#fdm_menu_section_' + section_id );
		if ( !$section_input.length ) {
			$( '#fdm_menu_column_one' ).after ( $( '<input type="hidden" name="fdm_menu_section_' + section_id + '" id="fdm_menu_section_' + section_id + '" value="' + section_title + '">' ) );
		} else {
			$section_input.val( section_title );
		}

		$( '[data-term-id="' + section_id + '"] .fdm-term-name', '#fdm-menu-organizer' ).text( section_title );

		fdmMenuOrganizer.closeMenuSectionModal();

		return false;
	};

	fdmMenuOrganizer.init();
} );


/**
 * Javascript functions for the admin interface for Food and Drink Menu Pro
 *
 * @package Food and Drink Menu Pro
 */

/*
 * When the page loads
 */
jQuery(document).ready(function ($) {

	/**
	 * Load the map in the menu item details if an address exists
	 * @since 2.0
	 */
	if ($('#fdm_item_source_address').val() !== '') {
		fdm_update_source_map();
	}

	/**
	 * Bind an event to load the map in the menu item details when the source
	 * name, address or zoom level changes
	 * @since 2.0
	 */
	$('#fdm_item_source_address, #fdm_item_source_zoom').change( function() {
		fdm_update_source_map();
	});

	/**
	 * Open Menu Item Flag selection
	 * @since 2.0
	 */
	$('.fdm_menu_item_flag_selector').click(function() {
		$('#fdm_menu_item_flag_selection_popup').fadeIn();
		$('.fdm_menu_item_flag_preview_panel i').hide();
		$('#fdm_menu_item_flag_selection_popup li').each(function() {
			$(this).addClass('fdm-icon-' + $(this).data('id'));
			$(this).attr('title', $(this).data('id'));
		});

		return false;
	});

	/**
	 * Select Menu Item Flag and close options panel
	 * @since 2.0
	 */
	$('#fdm_menu_item_flag_selection_popup li').click(function() {
		$('#fdm_menu_item_flag_selection_popup').hide();
		$('#fdm_menu_item_flag_icon_field').val($(this).data('id'));
		$('.fdm_menu_item_flag_preview_panel span').remove();
		$('.fdm_menu_item_flag_preview_panel').prepend('<span class="fdm-icon fdm-icon-' + $(this).data('id') + '"></span>');

		return false;
	});

	/**
	 * Show current Menu Item Flag when page loads
	 * @since 2.0
	 */
	if ($('#fdm_menu_item_flag_icon_field').val() !== '') {
		$('.fdm_menu_item_flag_preview_panel span').remove();
		$('.fdm_menu_item_flag_preview_panel').prepend('<span class="fdm-icon fdm-icon-' + $('#fdm_menu_item_flag_icon_field').val() + '"></span>');
	}

	/**
	 * Show Menu Item Flag id slug when mouse hovers
	 * @since 2.0
	 */
	$('#fdm_menu_item_flag_selection_popup li').hover(
		function() {
			$('#fdm_menu_item_notice span').addClass('fdm-icon-' + $(this).data('id'));
		}, function() {
			$('#fdm_menu_item_notice span').attr('class', function(i, c) {
				return c.replace(/\bfdm-icon-\S+/g, '').trim();
			});
		}
	);

	/**
	 * Bind an event to the menu item flag save button when adding a new flag
	 * that will clear the icon when the rest of the data clears.
	 * @since 2.0
	 */
	$('#addtag #submit').click(function () {

		// Check that the form is actually submitting and not hung up by invalid
		// input
		if (!$('#addtag .form-invalid').length) {
			$('#fdm_menu_item_flag_selection_popup').hide();
			$('.fdm_menu_item_flag_preview_panel i').remove();
			$('#fdm_menu_item_flag_icon_field').val('');
		}

	});

	/**
	 * Handle the price editing tool in the menu item list table
	 *
	 * @since 2.0
	 */
	var $menu_item_list_prices = $( '.fdm-item-list-price' );

	// Send the discounted price when the prices are saved
	$menu_item_list_prices.on( 'save-item-price.fdm', function( e, data ) {
		var $price_panel = $( e.target ),
			$discount_input = $( 'input[name="fdm_item_price_discount"], input[data-name="fdm_item_price_discount"]', $price_panel );

		if ( !$discount_input.length ) {
			return;
		}

		data.discount_price = $discount_input.val();
	} );

	// Enable the price update button in the menu item list when discounted
	// price is changed
	$( 'input[name="fdm_item_price_discount"], input[data-name="fdm_item_price_discount"]', $menu_item_list_prices ).keyup( function( e ) {
		var $form = $( e.target ).closest( '.fdm-item-price-form' );

		if ( !$form.length ) {
			return;
		}

		$( '.fdm-item-price-save', $form ).removeAttr( 'disabled' );
	} );

});


/**
 * Update map in Menu Item source preview
 * @since 2.0
 */
function fdm_update_source_map() {

	// Hide the map panel if no source is input
	if ( jQuery('#fdm_item_source_address').val() === '' ) {
		jQuery('.fdm-source-map').hide();

	// Show the map panel and update map
	} else if ( typeof fdm_settings !== 'undefined' ) {
		jQuery('.fdm-source-map').empty();
		var map_src = '//maps.google.com/maps/api/staticmap?markers=size:normal|color:blue|' + encodeURI(jQuery('#fdm_item_source_address').val()) + '&amp;zoom=' + encodeURI(jQuery('#fdm_item_source_zoom').val()) + '&amp;size=300x300&amp;scale=2&amp;sensor=false&amp;visual_refresh=true';
		if (typeof fdm_settings['fdm-google-map-api-key'] !== 'undefined' && fdm_settings['fdm-google-map-api-key'] !== '' ) {
			map_src += '&amp;key=' + encodeURI(fdm_settings['fdm-google-map-api-key']);
		}
		jQuery('.fdm-source-map').append('<img src="' + map_src + '">');
		jQuery('.fdm-source-map').fadeIn('fast');
	}
}


/**
 * Update map in Menu Item source preview
 * @since 2.1
 */
jQuery(document).ready(function() {
	jQuery( '.fdm-menu-item-add-ordering-option' ).on( 'click', function() {
		var key = jQuery( this ).data( 'nextkey' );

		var html = '<tr>';
		html += '<td class="fdm-menu-item-ordering-option-delete">Delete</td>';
		html += '<td><input type="text" name="ordering_option[' + key + '][name]" /></td>';
		html += '<td><input type="checkbox" name="ordering_option[' + key + '][default]" value="true" /></td>';
		html += '<td><input type="number" name="ordering_option[' + key + '][cost]" /></td>';
		html += '</tr>';

		jQuery( this ).parent().before( html );

		jQuery( this ).data( 'nextkey', key + 1 );

		fdm_menu_item_ordering_option_delete_handlers();

	});

	fdm_menu_item_ordering_option_delete_handlers();
});

function fdm_menu_item_ordering_option_delete_handlers() {
	jQuery( '.fdm-menu-item-ordering-option-delete' ).off( 'click' );
	jQuery( '.fdm-menu-item-ordering-option-delete' ).on( 'click', function() {
		jQuery( this ).parent().remove();
	});
}


jQuery(document).ready(function() {
	jQuery('.fdm-custom-fields-add-nutrional-information').on('click', function() {
		var nutritionalInformation = [
			{name: "Nutritional Information", slug: "nutritional information", type: "section"},
			{name: "Calories", slug: "calories", type: "text"},
			{name: "Fat", slug: "fat", type: "text"},
			{name: "Carbohydrates", slug: "carbohydrates", type: "text"},
			{name: "Protein", slug: "protein", type: "text"},
			{name: "Sodium", slug: "sodium", type: "text"}
		];

		jQuery(nutritionalInformation).each(function(index, element) {
			var rowID = addCustomFieldRow(); console.log(rowID); console.log(index); console.log(element);

			jQuery('input[name="name_' + rowID + '"]').val(element.name);
			jQuery('input[name="slug_' + rowID + '"]').val(element.slug);
			jQuery('select[name="type_' + rowID + '"]').val(element.type);
		});
	});
});

function addCustomFieldRow() {
	var max_row = findInfiniteTableMaxRow();

	jQuery('.sap-inifite-table-row-template').clone().appendTo('.sap-infinite-table tbody');

	jQuery('.sap-infinite-table tbody .sap-inifite-table-row-template').removeClass('sap-inifite-table-row-template').addClass('sap-inifinite-table-row').addClass('sap-new-infinite-row');
	jQuery('.sap-new-infinite-row').data('rowid', max_row + 1);
	jQuery('.sap-new-infinite-row input, .sap-new-infinite-row select').each(function() {
		jQuery(this).attr('name', jQuery(this).attr('name') + '_' + (max_row + 1));
	});
	jQuery('.sap-new-infinite-row').removeClass('sap-new-infinite-row').removeClass('sap-hidden');

	setInfiniteTableDeleteHandlers();
	setInfiniteTableUpdateHandlers();

	return max_row + 1;
}


/* Hacky way of opening the right submenu */
jQuery(document).ready(function($) {
	jQuery('#menu-posts-fdm-menu').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu');
	
	var admin_url = /[^/]*$/.exec(window.location.href)[0];
	jQuery('#menu-posts-fdm-menu li a').each(function() {
		if ( jQuery(this).attr('href') == admin_url ) {
			jQuery(this).parent().addClass('current');
		}
	});
});

/* Hacky way of changing the post_type for the bulk actions on the order form */
jQuery(document).ready( function( $ ) {
	jQuery( '#fdm-orders-table input[name="post_type"]' ).val( 'fdm-menu' );
});

/* Allow items to be added to an order in the admin area */
jQuery( document ).ready( function( $ ) {

	jQuery( '.fdm-admin-add-item-to-order, .fdm-admin-add-item-to-order-cancel' ).on( 'click', function() {
		jQuery( '.fdm-admin-add-item-to-order-items-table, .fdm-admin-order-items-table' ).toggleClass( 'fdm-hidden' );
	} );

	jQuery( '.fdm-admin-add-item-cancel, .fdm-admin-add-item-to-order-modal-background, .fdm-admin-add-item-to-order-modal' ).on( 'click', function() {

		jQuery( '.fdm-admin-add-item-to-order-modal, .fdm-admin-add-item-to-order-modal-background' ).addClass( 'fdm-hidden' );

		jQuery( '#fdm-ordering-popup-options > div' ).remove();
	} );

	jQuery( '.fdm-admin-add-item-to-order-modal-inner' ).on( 'click', function( event ) {

		event.stopPropagation();
	} );
} );

/*NEW DASHBOARD MOBILE MENU AND WIDGET TOGGLING*/
jQuery(document).ready(function($){
	$('#fdm-dash-mobile-menu-open').click(function(){
		$('.fdm-admin-header-menu .nav-tab:nth-of-type(1n+2)').toggle();
		$('#fdm-dash-mobile-menu-up-caret').toggle();
		$('#fdm-dash-mobile-menu-down-caret').toggle();
		return false;
	});
	$(function(){
		$(window).resize(function(){
			if($(window).width() > 800){
				$('.fdm-admin-header-menu .nav-tab:nth-of-type(1n+2)').show();
			}
			else{
				$('.fdm-admin-header-menu .nav-tab:nth-of-type(1n+2)').hide();
				$('#fdm-dash-mobile-menu-up-caret').hide();
				$('#fdm-dash-mobile-menu-down-caret').show();
			}
		}).resize();
	});	
	$('#fdm-dashboard-support-widget-box .fdm-dashboard-new-widget-box-top').click(function(){
		$('#fdm-dashboard-support-widget-box .fdm-dashboard-new-widget-box-bottom').toggle();
		$('#fdm-dash-mobile-support-up-caret').toggle();
		$('#fdm-dash-mobile-support-down-caret').toggle();
	});
	$('#fdm-dashboard-optional-table .fdm-dashboard-new-widget-box-top').click(function(){
		$('#fdm-dashboard-optional-table .fdm-dashboard-new-widget-box-bottom').toggle();
		$('#fdm-dash-optional-table-up-caret').toggle();
		$('#fdm-dash-optional-table-down-caret').toggle();
	});
	$('#fdm-dashboard-restart-walkthrough-widget-box .fdm-dashboard-new-widget-box-top').click(function(){
		$('#fdm-dashboard-restart-walkthrough-widget-box .fdm-dashboard-new-widget-box-bottom').toggle();
		$('#fdm-dashboard-restart-walkthrough-up-caret').toggle();
		$('#fdm-dashboard-restart-walkthrough-down-caret').toggle();
	});
});

jQuery('.fdm-ultimate-upgrade-dismiss').on('click', function() {
	jQuery('#fdm-dashboard-upgrade-box').addClass('rtb-hidden');
	jQuery('#fdm-dashboard-show-upgrade-box-link').removeClass('rtb-hidden');

	var params = {
		action: 'fdm_hide_upgrade_box',
		nonce: fdm_settings.nonce
	};

	var data = jQuery.param( params );
	jQuery.post( ajaxurl, data );
});

/* Handle Trial Type Selection */
jQuery(document).ready(function($) {
	
	jQuery( '.fsp-premium-helper-dashboard-new-trial-button' ).on('click', function() {

		jQuery( '.fdm-trial-version-select-modal-background , .fdm-trial-version-select-modal' ).removeClass( 'fdm-hidden' );

		return false;
	});

	jQuery( '.fdm-trial-version-select-modal-submit' ).on( 'click', function() {

		var selected_version = jQuery( 'input[name="fdm-trial-version"]:checked' ).val();

		if ( selected_version == 'ultimate' ) { jQuery( 'input[name="plugin_name"]').val( 'FDMU' ); }
		
		jQuery( '#fsp-trial-form' ).submit();
	});
});

// About Us Page
jQuery( document ).ready( function( $ ) {

	jQuery( '.fdm-about-us-tab-menu-item' ).on( 'click', function() {

		jQuery( '.fdm-about-us-tab-menu-item' ).removeClass( 'fdm-tab-selected' );
		jQuery( '.fdm-about-us-tab' ).addClass( 'fdm-hidden' );

		var tab = jQuery( this ).data( 'tab' );

		jQuery( this ).addClass( 'fdm-tab-selected' );
		jQuery( '.fdm-about-us-tab[data-tab="' + tab + '"]' ).removeClass( 'fdm-hidden' );
	} );

	jQuery( '.fdm-about-us-send-feature-suggestion' ).on( 'click', function() {

		var feature_suggestion = jQuery( '.fdm-about-us-feature-suggestion textarea' ).val();
		var email_address = jQuery( '.fdm-about-us-feature-suggestion input[name="feature_suggestion_email_address"]' ).val();
	
		var params = {};

		params.nonce  				= fdm_settings.nonce;
		params.action 				= 'fdm_send_feature_suggestion';
		params.feature_suggestion	= feature_suggestion;
		params.email_address 		= email_address;

		var data = jQuery.param( params );
		jQuery.post( ajaxurl, data, function() {} );

		jQuery( '.fdm-about-us-feature-suggestion' ).prepend( '<p>Thank you, your feature suggestion has been submitted.' );
	} );
} );

//SETTINGS PREVIEW SCREENS

jQuery( document ).ready( function() {

	jQuery( '.fdm-settings-preview' ).prevAll( 'h2' ).hide();
	jQuery( '.fdm-settings-preview' ).prevAll( '.sap-tutorial-toggle' ).hide();
	jQuery( '.fdm-settings-preview .sap-tutorial-toggle' ).hide();
});

// NEW PLUGIN NOTICE

jQuery( document ).ready( function( $ ) {

  jQuery(document).on( 'click', '.ait-iat-new-plugin-notice .notice-dismiss', function( event ) {
    var data = jQuery.param({
      action: 'fdm_hide_new_plugin_notice',
      plugin: 'ait_iat',
      nonce: fdm_settings.nonce
    });

    jQuery.post( ajaxurl, data, function() {} );
  });
});