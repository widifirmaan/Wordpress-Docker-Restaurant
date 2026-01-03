jQuery( document ).ready( function() {

  jQuery(document).on( 'click', '.fdm-helper-install-notice .notice-dismiss', function( event ) {
    var data = jQuery.param({
      action: 'fdm_hide_helper_notice',
      nonce: fdm_helper_notice.nonce
    });

    jQuery.post( ajaxurl, data, function() {} );
  });
});