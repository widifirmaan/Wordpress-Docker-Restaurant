jQuery(document).ready(function($){
 
    var custom_uploader;
 
    jQuery('#Welcome_Item_Image_button').click(function(e) {
 
        e.preventDefault();
 
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
 
        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });
 
        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            jQuery('input[name="menu_item_image_url"]').val(attachment.url);
            jQuery('.fdm-welcome-screen-image-preview img').attr('src', attachment.url);
            jQuery('.fdm-welcome-screen-image-preview').removeClass('fdm-hidden');
        });
 
        //Open the uploader dialog
        custom_uploader.open();
 
    });
});

jQuery(document).ready(function($){
 
    var custom_uploader;
 
    jQuery('#fdm_menu_section_image_button').click(function(e) {
 
        e.preventDefault();
 
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
 
        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });
 
        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            jQuery('input[name="fdm_menu_section_image"]').val(attachment.url);
            jQuery('.fdm-edit-menu-section-image-preview').attr('src', attachment.url);
        });
 
        //Open the uploader dialog
        custom_uploader.open();
 
    });
});