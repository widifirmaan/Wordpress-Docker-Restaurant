<?php
	
	//HTML5 Shiv ==============================================
	wp_enqueue_script( 'restau_lite_html5shiv', get_template_directory_uri() . '/js/html5shiv.js', array(), '3.7.3', true );
	//=================================================================
	
	//hoverIntent Plugin ==============================================
	wp_enqueue_script( 'hoverIntent' );
	//=================================================================

	//Modernizr Plugin ================================================
	wp_enqueue_script( 'restau_lite_modernizr', get_template_directory_uri() . '/js/modernizr.custom.67069.js', '2.8.3', true );
	//=================================================================
	
	//Flickity ========================================================
	wp_enqueue_script( 'restau_lite_flickity', get_template_directory_uri() . '/js/flickity.pkgd.js', array(), '1.1.1', true );
	//=================================================================

	//Wow ========================================================
	wp_enqueue_script( 'restau_lite_wow', get_template_directory_uri() . '/js/wow.js', array(), '1.1.2', true );
	//=================================================================
	
	//Pace  ===========================================================
	wp_enqueue_script( 'restau_lite_pace', get_template_directory_uri() . '/js/pace.js', array(), '0.2.0', true);
	//=================================================================

	//PhotoSwipe  ===========================================================
	wp_enqueue_script( 'restau_lite_photo_swipe', get_template_directory_uri() . '/js/photoswipe.js', array(), '4.1.1', true);
	wp_enqueue_script( 'restau_lite_photo_swipe_default', get_template_directory_uri() . '/js/photoswipe-ui-default.js', array(), '4.1.1', true);
	//=================================================================
	
	//Bootstrap JS ========================================
	wp_enqueue_script( 'restau_lite_bootstrap', get_template_directory_uri() . '/js/bootstrap.js', array( 'jquery' ), '3.3.5', true);
	//=================================================================

	
	//Comment Reply ===================================================
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
	//=================================================================

	
	//Customs Scripts =================================================
	wp_enqueue_script( 'restau_lite_theme-custom', get_template_directory_uri() . '/js/script.js', array( 'jquery', 'restau_lite_bootstrap' ), '1.0', true );
	//=================================================================

?>