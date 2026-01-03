//OPTIONS PAGE YES/NO TOGGLE SWITCHES
jQuery(document).ready(function($){
	$('.fdm-admin-option-toggle').on('change', function() {
		var Input_Name = $(this).data('inputname'); console.log(Input_Name);
		if ($(this).is(':checked')) {
			$('input[name="' + Input_Name + '"][value="1"]').prop('checked', true).trigger('change');
			$('input[name="' + Input_Name + '"][value=""]').prop('checked', false);
		}
		else {
			$('input[name="' + Input_Name + '"][value="1"]').prop('checked', false).trigger('change');
			$('input[name="' + Input_Name + '"][value=""]').prop('checked', true);
		}
	});
});
