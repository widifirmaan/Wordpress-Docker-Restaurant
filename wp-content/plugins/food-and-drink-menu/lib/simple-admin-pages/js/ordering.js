/**
 * Javascript functions for Ordering Table
 *
 * @package Simple Admin Pages
 */

jQuery(document).ready(function ($) {

  // process fields
  $('.sap-parent-form').on('submit', function (ev) {
    var _form = $(this), ignore;

    $('.sap-ordering-table').each( function() {

      var main_input = $(this).find('#sap-ordering-table-main-input');

      var main_input_val = {};

      $(this).find('table tbody tr').each((idx_tr, tr) => {
  
        $(tr).find('td').each((idx_td, td) => {
          let elm = $(td).find('input');
  
          main_input_val[elm.val()] = $(td).find('span').html();
        });
  
      });

      if ( main_input_val.length ) { main_input.val( JSON.stringify( main_input_val ) ); }
    });
  });

  $('.sap-ordering-table table tbody').sortable({
    axis: 'y'
  });

  //reset field to default
  $( '.sap-ordering-table-restore-default' ).on( 'click', function() {

    var table_div = jQuery( this ).closest( '.sap-ordering-table' );

    table_div.find( '#sap-ordering-table-main-input' ).first().val( '' );

    table_div.find( 'table tbody tr' ).remove();

    table_div.find( 'table' ).append( '<tfoot><tr><td>Save the page to restore the default order</td></tr></tfoot>' );
  } );

})