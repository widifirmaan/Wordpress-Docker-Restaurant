<?php
/**
 * Custom functions that act independently of the theme templates.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Restau Lite
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function restau_lite_body_classes( $classes ) {

    $restau_lite_theme_data = wp_get_theme();

    $classes[] = sanitize_title( $restau_lite_theme_data['Name'] );
    $classes[] = 'ver-' . $restau_lite_theme_data['Version'];

    $restau_lite_general_options_animations = get_theme_mod( 'restau_lite_general_options_animations', '1' );
    if ( '1' == $restau_lite_general_options_animations ) {
        $classes[] = 'ql_animations';
    }

	return $classes;
}
add_filter( 'body_class', 'restau_lite_body_classes' );




/**
 * Extract YouTube ID from several URL structures
 * https://gist.github.com/simplethemes/7591414
 */
if ( ! function_exists( 'restau_lite_extract_youtube_id' ) ){
	function restau_lite_extract_youtube_id( $video_url ) {
		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', (string)$video_url, $match)) {
            $video_id = $match[1];
            return $video_id;
        }else{
        	return 'error';
        }
    }
}// end function_exists

/**
 * Extract Vimeo ID from several URL structures
 * http://stackoverflow.com/questions/10488943/easy-way-to-get-vimeo-id-from-a-vimeo-url
 */
if ( ! function_exists( 'restau_lite_extract_vimeo_id' ) ){
	function restau_lite_extract_vimeo_id( $video_url ) {
		if (preg_match('#(?:https?://)?(?:www.)?(?:player.)?vimeo.com/(?:[a-z]*/)*([0-9]{6,11})[?]?.*#', (string)$video_url, $match)) {
            $video_id = $match[1];
            return $video_id;
        }else{
        	return 'error';
        }
    }
}// end function_exists

/**
 * Get all portfolio categories
 */
if ( ! function_exists( 'restau_lite_get_portfolio_categories' ) ){
    function restau_lite_get_portfolio_categories() {

        if ( taxonomy_exists( 'jetpack-portfolio-type' ) ){
            
            $categories = get_terms( 'jetpack-portfolio-type' );
            $cat_ar = array();
            if ( $categories ) {
                foreach ( $categories as $key ) {
                    $cat_ar[$key->slug] = $key->name;
                }
            }
            return $cat_ar;
        }else{ 
            return false;
        }
        
    }
}// end function_exists


if ( ! function_exists( 'restau_lite_new_content_more' ) ){
    function restau_lite_new_content_more($more) {
           global $post;
           return ' <br><a href="' . esc_url( get_permalink() ) . '" class="more-link read-more">' . esc_html__( 'Read more', 'restau-lite' ) . ' <i class="fa fa-angle-right"></i></a>';
    }   
}// end function_exists
    add_filter( 'the_content_more_link', 'restau_lite_new_content_more' );


//Get All menus
if ( ! function_exists( 'restau_lite_get_menus' ) ){
    function restau_lite_get_menus() {
        $restau_lite_all_menu_objects = get_terms( 'nav_menu', array( 'hide_empty' => true ) );
        $restau_lite_all_menus = array();
        foreach ( $restau_lite_all_menu_objects as $menu ) {
            $restau_lite_all_menus[$menu->slug] = $menu->name;
        }
        return $restau_lite_all_menus;
    }   
}// end function_exists


if ( ! function_exists( 'restau_lite_get_food_menus' ) ){
    function restau_lite_get_food_menus() {
        //Food plugin Menu var
        if ( class_exists( 'fdmFoodAndDrinkMenu' ) ) {
            $args = array(
                'post_type' => 'fdm-menu',
                'posts_per_page' => -1
            );
            $food_dring_menus = array();
            $the_query = new WP_Query( $args );
            if ( $the_query->have_posts() ) :
                while ( $the_query->have_posts() ) : $the_query->the_post();
                    $food_dring_menus[get_the_id()] = get_the_title();
                endwhile;            
            endif;
            wp_reset_postdata();
            return $food_dring_menus;
        }
    }
}// end function_exists



/**
 * Adds swipePhoto to default Galleries
 */
function restau_lite_gallery_shortcode_photoswipe( $output = '', $atts, $instance ) {

    if ( $atts['link'] == 'file' && ! is_admin() ) {
        $attachment_ids = explode(",", $atts['ids'] );
        $attach_count = count( $attachment_ids );
        echo '<script type="text/javascript">';
        echo "jQuery(document).ready(function($) {";
        echo "var items = [\n";
        foreach ( $attachment_ids as $key => $attachment_id ) {
            $attach_image = wp_get_attachment_image_src( absint( $attachment_id ), 'full' );
            $attach_post = get_post( $attachment_id );
            $title = $attach_post->post_excerpt;
            echo "{\n";
                echo "src: '" . $attach_image[0] . "',\n";
                if ( $title ) {
                    echo "title: '" . esc_attr( $title ) . "',\n";
                }
                echo 'w: ' . $attach_image[1] . ",\n";
                echo 'h: ' . $attach_image[2] . "\n";
            echo "}";
            echo ( $key == $attach_count - 1 ) ? "" : ',' ;
            echo "\n";
        }
        echo "];\n";
        echo "initPhotoSwipe_fromVar(items);";
        echo "});";
        echo '</script>';
    }

    return $output;
}
add_filter( 'post_gallery', 'restau_lite_gallery_shortcode_photoswipe', 10, 3 );
