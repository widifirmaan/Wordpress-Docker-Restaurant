jQuery(document).ready(function($) {

	if ( $("body").hasClass('ql_animations' ) ) {
		new WOW().init();	
	}

	$("body").on('click', '#primary-menu > li > a[href^="#"], .home-buttons a[href^="#"]', function(event) {
    	event.preventDefault();
    	/* Act on the event */
    	$("html, body").animate({
	    	scrollTop: $( $(this).attr('href') ).offset().top
	    }, 1000);
    });

	$('.menu-slider').flickity({
	  cellAlign: 'left',
	  contain: true
	});

	$(".ql_scroll_top").click(function() {
	  $("html, body").animate({ scrollTop: 0 }, "slow");
	  return false;
	});

	$('.dropdown-toggle').dropdown();
	$('*[data-toggle="tooltip"]').tooltip();
			
});