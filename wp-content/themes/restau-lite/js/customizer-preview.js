/**
 * customizer.js
 *
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes asynchronously.
 */

( function( $ ) {
	// Site title and description.
	wp.customize( 'blogname', function( value ) {
		value.bind( function( to ) {
			$( '.site-title a' ).text( to );
		} );
	} );
	wp.customize( 'blogdescription', function( value ) {
		value.bind( function( to ) {
			$( '.site-description' ).text( to );
		} );
	} );

	
	// Header text color.
	wp.customize( 'header_textcolor', function( value ) {
		value.bind( function( to ) {
			if ( 'blank' === to ) {
				$( '.site-title a, .site-description' ).css( {
					'clip': 'rect(1px, 1px, 1px, 1px)',
					'position': 'absolute'
				} );
			} else {
				$( '.site-title a, .site-description, #jqueryslidemenu a' ).css( {
					'clip': 'auto',
					'color': to,
					'position': 'relative'
				} );
			}
		} );
	} );


	/*
    Welcome Section
    =====================================================
    */
    //Background Image
	wp.customize( 'restau_lite_welcome_image', function( value ) {
		value.bind( function( to ) {
			if ( to != "" ) {
				$( '.welcome-image' ).css( 'background-image', 'url(' + to + ')' );
				console.log(to);
			}else{
				$( '.welcome-image' ).css( 'background-image', 'url(' + restau_lite_wp_customizer.theme_url + "/images/welcome.jpg)" );
			}
		} );
	} );
	//Background Image
	wp.customize( 'restau_lite_welcome_logo_image', function( value ) {
		value.bind( function( to ) {
			if ( to != "" ) {
				$( '.welcome-image img' ).attr( 'src', to );
				console.log(to);
			}else{
				$( '.welcome-image img' ).attr( 'src', restau_lite_wp_customizer.theme_url + "/images/logo_big.png" );
			}
		} );
	} );


	/*
    Colors
    =====================================================
    */
		//Featured Color
		wp.customize( 'restau_lite_hero_color', function( value ) {
			value.bind( function( to ) {
				$( '.pagination li.active a, .pagination li.active a:hover, .wpb_wrapper .products .product-category h3, .btn-ql:active, .btn-ql.alternative:hover, .btn-ql.alternative-white:hover, .btn-ql.alternative-gray:hover, .hero_bck, .ql_nav_btn:hover, .ql_nav_btn:active, .cd-popular .cd-select, .no-touch .cd-popular .cd-select:hover, .pace .pace-progress, .btn-ql::before, btn-ql:hover::before, btn-ql:active::before, btn-ql::after, btn-ql:hover::after, btn-ql:active::after, .service .service-category::before, .service .service-category span, .section-title::before, .about-section .about-text .about-service p::before, .video-text-wrap .video-text-title::before, .btn-ql-round::after, btn-ql-round:hover::after, btn-ql-round:active::after, .about-section .about-text .about-service p::after, .team-member .member-image span::before, .team-member .member-image span::after, .team-member .member-image::before, .team-member .member-image::after, .portfolio-section .portfolio-item::before, .portfolio-section .portfolio-item::after, .portfolio-section .portfolio-item span.lines::before, .portfolio-section .portfolio-item span.lines::after, .pricing-section .pricing-table::before, .pricing-section .pricing-table::after, .pricing-section .pricing-table span.lines::before, .pricing-section .pricing-table span.lines::after, .flickity-page-dots .dot.is-selected, .blog-wrap .blog-time-date::after, .contact-section .contact-submit::after' ).css( {
						'background-color': to
				} );
				$( '.btn-ql, .pagination li.active a, .pagination li.active a:hover, .btn-ql:active, .btn-ql.alternative, .btn-ql.alternative:hover, .btn-ql.alternative-white:hover, .btn-ql.alternative-gray:hover, .hero_border, .pace .pace-activity, .section-title::after, .video-text-wrap .video-text-title::after, .btn-ql-round::before, btn-ql-round:hover::before, btn-ql-round:active::before, .flickity-page-dots .dot.is-selected, .blog-wrap .blog-time-date::before, .contact-section .contact-submit::before' ).css( {
						'border-color': to 
				} );
				$( '.pagination .current, .pagination a:hover, .widget_recent_posts ul li h6 a, .widget_popular_posts ul li h6 a, .read-more, .read-more i, .btn-ql.alternative, .hero_color, .cd-popular .cd-pricing-header, .cd-popular .cd-currency, .cd-popular .cd-duration, #sidebar .widget ul li > a:hover, #sidebar .widget_recent_comments ul li a:hover' ).css( {
						'color': to
				} );
			} );
		} );

		//Text Color
		wp.customize( 'restau_lite_text_color', function( value ) {
			value.bind( function( to ) {
				$( 'body' ).css( {
						'color': to
				} );
			} );
		} );
		//Link Color
		wp.customize( 'restau_lite_link_color', function( value ) {
			value.bind( function( to ) {
				$( 'a' ).css( {
						'color': to
				} );
			} );
		} );


	/*
    Front Page Sections
    =====================================================
    */

		/*
    	Gellery
    	------------------------------ */
    	//Section Title
		wp.customize( 'restau_lite_gallery_title_serif', function( value ) {
			value.bind( function( to ) {
				$( '.gallery-section .style-title span' ).text( to );
			} );
		} );
		//Section Title
		wp.customize( 'restau_lite_gallery_title', function( value ) {
			value.bind( function( to ) {
				var title_span = $( '.gallery-section .style-title span' ).text();
				$( '.gallery-section .style-title' ).html( '<span>' + title_span + '</span>' + ' ' + to );
			} );
		} );
		//Description
		wp.customize( 'restau_lite_gallery_text', function( value ) {
			value.bind( function( to ) {
				$( '.gallery-section .gallery-content p' ).text( to );
			} );
		} );
		//Button 1
		wp.customize( 'restau_lite_gallery_link_title', function( value ) {
			value.bind( function( to ) {
				$( '.gallery-section .gallery-content .light-btn' ).text( to );
			} );
		} );
		//Link URL 1
		wp.customize( 'restau_lite_gallery_link_url', function( value ) {
			value.bind( function( to ) {
				$( '.gallery-section .gallery-content .light-btn' ).attr( 'href', to );
			} );
		} );
		//Featured Image
		wp.customize( 'restau_lite_gallery_image', function( value ) {
			value.bind( function( to ) {
				if ( to != "" ) {
					$( '.gallery-section .gallery-image' ).css( 'background-image', 'url(' + to + ')' );
					console.log(to);
				}else{
					$( '.gallery-section .gallery-image' ).css( 'background-image', 'url(' + restau_lite_wp_customizer.theme_url + "/images/gallery.jpeg)" );
				}
			} );
		} );
		//Enable/Disable Section
		wp.customize( 'restau_lite_gallery_enable', function( value ) {
			value.bind( function( to ) {
				if ( to == true ) {
					$( '.gallery-section' ).show();	
				}else{
					$( '.gallery-section' ).hide();
				}
			} );
		} );
		

		/*
    	Blog
    	------------------------------ */
    	//Section Title
		wp.customize( 'restau_lite_blog_title_serif', function( value ) {
			value.bind( function( to ) {
				$( '.blog-section .style-title span' ).text( to );
			} );
		} );
		//Section Title
		wp.customize( 'restau_lite_blog_title', function( value ) {
			value.bind( function( to ) {
				var title_span = $( '.blog-section .style-title span' ).text();
				$( '.blog-section .style-title' ).html( '<span>' + title_span + '</span>' + ' ' + to );
			} );
		} );
		//Enable/Disable Section
		wp.customize( 'restau_lite_blog_enable', function( value ) {
			value.bind( function( to ) {
				if ( to == true ) {
					$( '.blog-section' ).show();	
				}else{
					$( '.blog-section' ).hide();
				}
			} );
		} );

		/*
    	Testimonials
    	------------------------------ */
    	//Section Title
		wp.customize( 'restau_lite_testimonials_title_serif', function( value ) {
			value.bind( function( to ) {
				$( '.testimonials-section .style-title span' ).text( to );
			} );
		} );
		//Section Title
		wp.customize( 'restau_lite_testimonials_title', function( value ) {
			value.bind( function( to ) {
				var title_span = $( '.testimonials-section .style-title span' ).text();
				$( '.testimonials-section .style-title' ).html( '<span>' + title_span + '</span>' + ' ' + to );
			} );
		} );
		//Enable/Disable Section
		wp.customize( 'restau_lite_testimonials_enable', function( value ) {
			value.bind( function( to ) {
				if ( to == true ) {
					$( '.testimonials-section' ).show();	
				}else{
					$( '.testimonials-section' ).hide();
				}
			} );
		} );


	/*
    General Options
    =====================================================
    */
	//Background Image
	wp.customize( 'restau_lite_bottom_image', function( value ) {
		value.bind( function( to ) {
			if ( to != "" ) {
				$( '.bottom-image' ).css( 'background-image', 'url(' + to + ')' );
				console.log(to);
			}else{
				$( '.bottom-image' ).css( 'background-image', 'url(' + restau_lite_wp_customizer.theme_url + "/images/footer_bck.jpg)" );
			}
		} );
	} );

} )( jQuery );
