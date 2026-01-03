<?php
/**
 * Jetpack Compatibility File.
 *
 * @link https://jetpack.me/
 *
 * @package Restau Lite
 */

/**
 * Add theme support for Infinite Scroll.
 * See: https://jetpack.me/support/infinite-scroll/
 */
function restau_lite_jetpack_setup() {
	add_theme_support( 'infinite-scroll', array(
		'container' => 'main',
		'render'    => 'restau_lite_infinite_scroll_render',
		'footer'    => 'page',
	) );

	if ( class_exists( 'Jetpack' ) ) {
		//Enable Custom CSS
        Jetpack::activate_module( 'custom-css', false, false );
        //Enable Contact Form
        Jetpack::activate_module( 'contact-form', false, false );
        //Enable Tiled Galleries
        Jetpack::activate_module( 'tiled-gallery', false, false );
        //Enable Testimonials CPT
		add_theme_support( 'jetpack-testimonial' );
		Jetpack::activate_module( 'custom-content-types', false, false );
    }

} // end function restau_lite_jetpack_setup
add_action( 'after_setup_theme', 'restau_lite_jetpack_setup' );

/**
 * Custom render function for Infinite Scroll.
 */
function restau_lite_infinite_scroll_render() {
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content', get_post_format() );
	}
} // end function restau_lite_infinite_scroll_render
