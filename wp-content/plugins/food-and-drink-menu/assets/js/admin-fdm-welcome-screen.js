jQuery(document).ready(function() {
	jQuery('.fdm-welcome-screen-box h2').on('click', function() {
		var section = jQuery(this).parent().data('screen');
		fdm_toggle_section(section);
	});

	jQuery('.fdm-welcome-screen-next-button').on('click', function() {
		var section = jQuery(this).data('nextaction');
		fdm_toggle_section(section);
	});

	jQuery('.fdm-welcome-screen-previous-button').on('click', function() {
		var section = jQuery(this).data('previousaction');
		fdm_toggle_section(section);
	});

	jQuery('.fdm-welcome-screen-add-section-button').on('click', function() {

		jQuery('.fdm-welcome-screen-show-created-sections').show();

		var section_name = jQuery('.fdm-welcome-screen-add-section-name input').val();
		var section_description = jQuery('.fdm-welcome-screen-add-section-description textarea').val();

		jQuery('.fdm-welcome-screen-add-section-name input').val('');
		jQuery('.fdm-welcome-screen-add-section-description textarea').val('');

		var params = {
			section_name: section_name,
			section_description: section_description,
			nonce: fdm_getting_started.nonce,
			action: 'fdm_welcome_add_section'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function(response) {

			var HTML = '<div class="fdm-welcome-screen-section">';
			HTML += '<div class="fdm-welcome-screen-section-name">' + section_name + '</div>';
			HTML += '<div class="fdm-welcome-screen-section-description">' + section_description + '</div>';
			HTML += '</div>';

			jQuery('.fdm-welcome-screen-show-created-sections').append(HTML);

			var section = JSON.parse(response); 
			jQuery('.fdm-welcome-screen-add-create_menu-sections').append('<input type="checkbox" value="' + section.section_id + '" checked /> ' + section.section_name + '<br />');
			jQuery('.fdm-welcome-screen-add-menu_item-section select').append('<option value="' + section.section_id + '">' + section.section_name + '</option>');
		});
	});

	jQuery('.fdm-welcome-screen-add-create_menu-button').on('click', function() {

		var menu_name = jQuery('.fdm-welcome-screen-add-create_menu-name input').val();

		var sections = [];
		jQuery('.fdm-welcome-screen-add-create_menu-sections input').each(function() {
			sections.push(jQuery(this).val());
		});

		jQuery('.fdm-welcome-screen-add-create_menu-name input').val('');

		var params = {
			menu_name: menu_name,
			sections: JSON.stringify( sections ),
			nonce: fdm_getting_started.nonce,
			action: 'fdm_welcome_create_menu'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function(response) {});

		fdm_toggle_section('display_menu');
	});

	jQuery('.fdm-welcome-screen-add-menu_item-button').on('click', function() {

		jQuery('.fdm-welcome-screen-show-created-menu_items').show();

		var item_name = jQuery('.fdm-welcome-screen-add-menu_item-name input').val();
		var item_image = jQuery('.fdm-welcome-screen-add-menu_item-image input[name="menu_item_image_url"]').val();
		var item_description = jQuery('.fdm-welcome-screen-add-menu_item-description textarea').val();
		var item_section = jQuery('.fdm-welcome-screen-add-menu_item-section select').val();
		var item_price = jQuery('.fdm-welcome-screen-add-menu_item-price input').val();

		jQuery('.fdm-welcome-screen-add-menu_item-name input').val('');
		jQuery('.fdm-welcome-screen-image-preview').addClass('fdm-hidden');
		jQuery('.fdm-welcome-screen-add-menu_item-image input[name="menu_item_image_url"]').val('');
		jQuery('.fdm-welcome-screen-add-menu_item-description textarea').val('');
		jQuery('.fdm-welcome-screen-add-menu_item-price input').val('');

		var params = {
			item_name: item_name,
			item_image: item_image,
			item_description: item_description,
			item_section: item_section,
			item_price: item_price,	
			nonce: fdm_getting_started.nonce,
			action: 'fdm_welcome_add_menu_item'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function(response) {
			var HTML = '<div class="fdm-welcome-screen-menu_item">';
			HTML += '<div class="fdm-welcome-screen-menu_item-image"><img src="' + item_image + '" /></div>';
			HTML += '<div class="fdm-welcome-screen-menu_item-name">' + item_name + '</div>';
			HTML += '<div class="fdm-welcome-screen-menu_item-description">' + item_description + '</div>';
			HTML += '<div class="fdm-welcome-screen-menu_item-price">' + item_price + '</div>';
			HTML += '</div>';

			jQuery('.fdm-welcome-screen-show-created-menu_items').append(HTML);
		});
	});

	jQuery('.fdm-welcome-screen-add-menu-page-button').on('click', function() {
		var menu_page_title = jQuery('.fdm-welcome-screen-add-menu-page-name input').val();

		var data = 'menu_page_title=' + menu_page_title + '&action=fdm_welcome_add_menu_page';

		var params = {
			menu_page_title: menu_page_title,
			nonce: fdm_getting_started.nonce,
			action: 'fdm_welcome_add_menu_page'
		};

		var data = jQuery.param( params );

		jQuery.post(ajaxurl, data, function(response) {
			var admin_url = window.location.href.substr(0, window.location.href.lastIndexOf("/"));

			window.location = admin_url + '/edit.php?post_type=fdm-menu&page=fdm-dashboard';
		});
	});
});

function fdm_toggle_section(page) {
	jQuery('.fdm-welcome-screen-box').removeClass('fdm-welcome-screen-open');
	jQuery('.fdm-welcome-screen-' + page).addClass('fdm-welcome-screen-open');
}