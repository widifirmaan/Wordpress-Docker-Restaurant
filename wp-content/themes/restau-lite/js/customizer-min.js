jQuery(window).load(function (){
	( function( $ ) {

		//Scroll to section
		$('body').on('click', '#sub-accordion-panel-restau_lite_front_page_sections .control-subsection .accordion-section-title', function(event) {

			var section_id = $(this).parent('.control-subsection').attr('id');
			scrollToSection( section_id );
			
		});

		function scrollToSection( section_id ){
			var section_class = "welcome-section";
			var $contents = $('#customize-preview iframe').contents();
			switch ( section_id ) {
				case 'accordion-section-welcome':
			        section_class = "welcome-section";
			        break;
			    case 'accordion-section-restau_lite_about_section':
			        section_class = "about-us-section";
			        break;
			    case 'accordion-section-restau_lite_gallery_section':
			        section_class = "gallery-section";
			        break;
			    case 'accordion-section-restau_lite_reservation_section':
			        section_class = "reservation-section";
			        break;
			    case 'accordion-section-restau_lite_menu_section':
			        section_class = "menu-section";
			        break;
			    case 'accordion-section-restau_lite_blog_section':
			        section_class = "blog-section";
			        break;
			    case 'accordion-section-restau_lite_testimonials_section':
			        section_class = "testimonials-section";
			        break;
			}
			$contents.find("html, body").animate({
		    	scrollTop: $contents.find( "." + section_class ).offset().top - 30
		    }, 1000);

		}



		/*
		 * Links to different sections in the Customizer
		 * Just create a link like this: <a href="#" data-section="section-id">link</a>
		 */
		$('body').on('click', 'a[data-section]', function(event) {
			wp.customize.section( $(this).attr( 'data-section' ) ).focus();
		});

	} )( jQuery );
});

