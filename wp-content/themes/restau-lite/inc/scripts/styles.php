<?php

/**
 * Enqueues front-end CSS for color scheme.
 *
 * @see wp_add_inline_style()
 */
function restau_lite_custom_css() {
	$heroColor = get_theme_mod( 'restau_lite_hero_color', '#e8b932' );
	$text_color = get_theme_mod( 'restau_lite_text_color', '#616161' );
	$link_color = get_theme_mod( 'restau_lite_link_color', '#e8b932' );
	$background_color = '#' . get_background_color();

	$colors = array(
		'heroColor'            => $heroColor,
		'text_color'           => $text_color,
		'link_color'     	   => $link_color,
		'background_color'     => $background_color
	);

	$custom_css = restau_lite_get_custom_css( $colors );

	wp_add_inline_style( 'restau_lite_style', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'restau_lite_custom_css' );



/**
 * Returns CSS for the color schemes.
 *
 * @param array $colors colors.
 * @return string CSS.
 */
function restau_lite_get_custom_css( $colors ) {

	//Default colors
	$colors = wp_parse_args( $colors, array(
		'heroColor'            => '',
		'text_color'           => '',
		'link_color'           => '',
		'background_color'     => ''
	) );
	$bck_rgba = restau_lite_hex2rgba( $colors['background_color'], 1);

	$css = <<<CSS

	/* Text Color */
	body{
		color: {$colors['text_color']};
		background-color: {$colors['background_color']};

	}
	/* Link Color */
	a{
		color: {$colors['link_color']};
	}

	/* Copy background color */
	.reservation-section .tr::before,
	.reservation-section .tl::before{
		background-color: {$colors['background_color']};
	}
	.bottom-image::before{
		background: -moz-linear-gradient(top,  {$bck_rgba} 0%, rgba(0,0,0,0) 100%);
		background: -webkit-linear-gradient(top,  {$bck_rgba} 0%,rgba(0,0,0,0) 100%);
		background: linear-gradient(to bottom,  {$bck_rgba} 0%,rgba(0,0,0,0) 100%);
		filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='{$colors['background_color']}', endColorstr='#00000000',GradientType=0 );
	}


	/* Featured Color Background */
	.home-buttons .home-button.reservations-button .book-table-btn,
	.reservation-section .reservation-content .reservation-hours li span,
	.blog-wrap .blog-time-date{
		background-color: {$colors['heroColor']};
	}

	/* Featured Color Color */
	.style-title span,
	.about-us-section .location::before,
	.about-us-section .about-us-box::before,
	.menu-section .menu-slider .menu-slide ul li .menu-item-price,
	.testimonial blockquote::before, .testimonial blockquote::after{
		color: {$colors['heroColor']};
	}


CSS;

	return $css;
}


/* Convert hexdec color string to rgb(a) string */
function restau_lite_hex2rgba($color, $opacity = false) {
 
	$default = 'rgb(0,0,0)';
 
	//Return default if no color provided
	if(empty($color))
          return $default; 
 
	//Sanitize $color if "#" is provided 
        if ($color[0] == '#' ) {
        	$color = substr( $color, 1 );
        }
 
        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return $default;
        }
 
        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);
 
        //Check if opacity is set(rgba or rgb)
        if($opacity){
        	if(abs($opacity) > 1)
        		$opacity = 1.0;
        	$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
        	$output = 'rgb('.implode(",",$rgb).')';
        }
 
        //Return rgb(a) color string
        return $output;
}