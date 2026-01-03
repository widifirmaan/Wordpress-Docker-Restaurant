<?php
/**
 * Restau Lite Theme Customizer.
 *
 * @package Restau Lite
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function restau_lite_customize_register( $wp_customize ) {

	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';



	/*
    Colors
    ===================================================== */
    	/*
		Featured
		------------------------------ */
		$wp_customize->add_setting( 'restau_lite_hero_color', array( 'default' => '#e8b932', 'transport' => 'postMessage', 'sanitize_callback' => 'sanitize_hex_color', ) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'restau_lite_hero_color', array(
			'label'        => esc_attr__( 'Featured Color', 'restau-lite' ),
			'section'    => 'colors',
		) ) );

		/*
		Text
		------------------------------ */
		$wp_customize->add_setting( 'restau_lite_text_color', array( 'default' => '#616161', 'transport' => 'postMessage', 'sanitize_callback' => 'sanitize_hex_color', ) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'restau_lite_text_color', array(
			'label'        => esc_attr__( 'Text Color', 'restau-lite' ),
			'section'    => 'colors',
		) ) );

		/*
		Link
		------------------------------ */
		$wp_customize->add_setting( 'restau_lite_link_color', array( 'default' => '#e8b932', 'transport' => 'postMessage', 'sanitize_callback' => 'sanitize_hex_color', ) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'restau_lite_link_color', array(
			'label'        => esc_attr__( 'Link Color', 'restau-lite' ),
			'section'    => 'colors',
		) ) );


	/*
	Get Sections order
	------------------------------ */
	$sections_items = get_option( 'restau_lite_sortable_items' );
	$sections_sorted = array();
	if ( ! empty( $sections_items ) ) {
		foreach ( $sections_items as $key => $value ) {
			$sections_sorted[$value] = ( $key + 1 ) * 10;
		}
	}else{
		//Default order
	    $sections_sorted['restau_lite_about_section'] = 20;
	    $sections_sorted['restau_lite_gallery_section'] = 30;
	    $sections_sorted['restau_lite_reservation_section'] = 40;
	    $sections_sorted['restau_lite_menu_section'] = 50;
	    $sections_sorted['restau_lite_blog_section'] = 60;
	    $sections_sorted['restau_lite_testimonials_section'] = 70;
	}



	/*
	Header Options
	------------------------------ */
	$wp_customize->add_section( 'restau_lite_header_options', array(
		'title' => esc_attr__( 'Header Options', 'restau-lite' ),
		'description' => esc_attr__( 'Choose a layout for your blog.', 'restau-lite' ),
		'priority' => 140
	) );

	$wp_customize->add_setting( 'restau_lite_header_options_layout', array( 'default' => '1', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
	$wp_customize->add_control( 'restau_lite_header_options_layout', array(
		'type' => 'radio',
		'choices'    => array(
            '1' => 'Default',
            '2' => 'Logo & Menu'
        ),
		'section' => 'restau_lite_header_options', // Required, core or custom.
		'label' => esc_attr__( 'Layout', 'restau-lite' ),
	) );




	/*
	Welcome Section
	------------------------------ */
	$wp_customize->add_panel( 'restau_lite_welcome_panel', array(
		'title' => esc_attr__( 'Welcome Section', 'restau-lite' ),
		'description' => '',
		'priority' => 160,
		'active_callback' => 'is_front_page',
	) );

		$wp_customize->add_section( 'restau_lite_welcome_section', array(
			'title' => esc_attr__( 'Main Banner', 'restau-lite' ),
			'panel' => 'restau_lite_welcome_panel',
			'priority' => 150
		) );

			$wp_customize->add_setting( 'restau_lite_welcome_image', array( 'default' => '', 'transport' => 'postMessage', 'sanitize_callback' => 'attachment_url_to_postid', ) );
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'restau_lite_welcome_image', array(
		        'label'    => esc_attr__( 'Background Image', 'restau-lite' ),
		        'section'  => 'restau_lite_welcome_section',
		        'settings' => 'restau_lite_welcome_image',
			) ) );

			$wp_customize->add_setting( 'restau_lite_welcome_logo_image', array( 'default' => '', 'transport' => 'postMessage', 'sanitize_callback' => 'attachment_url_to_postid', ) );
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'restau_lite_welcome_logo_image', array(
		        'label'    => esc_attr__( 'Big Logo Image', 'restau-lite' ),
		        'section'  => 'restau_lite_welcome_section',
		        'settings' => 'restau_lite_welcome_logo_image',
			) ) );


	/*
    Sections
    ===================================================== */
    $wp_customize->add_panel( 'restau_lite_front_page_sections', array(
		'title' => esc_attr__( 'Front Page Sections', 'restau-lite' ),
		'description' => '', // Include html tags such as <p>.
		'priority' => 160,
		'active_callback' => 'is_front_page',
	) );

		/*
    	Gallery
    	------------------------------ */
		$wp_customize->add_section( 'restau_lite_gallery_section', array(
			'title' => esc_attr__( 'Gallery', 'restau-lite' ),
			'description' => esc_attr__( 'Display an image and link to the gallery.', 'restau-lite' ),
			'panel' => 'restau_lite_front_page_sections',
			'priority' => $sections_sorted['restau_lite_gallery_section'],
		) );

		$wp_customize->add_setting( 'restau_lite_gallery_title_serif', array( 'default' => esc_attr__( 'Our', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
		$wp_customize->add_control( 'restau_lite_gallery_title_serif', array(
			'type' => 'text',
			'section' => 'restau_lite_gallery_section', // Required, core or custom.
			'label' => esc_attr__( 'Section Serif Title', 'restau-lite' ),
		) );

		$wp_customize->add_setting( 'restau_lite_gallery_title', array( 'default' => esc_attr__( 'Space', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
		$wp_customize->add_control( 'restau_lite_gallery_title', array(
			'type' => 'text',
			'section' => 'restau_lite_gallery_section', // Required, core or custom.
			'label' => esc_attr__( 'Section Title', 'restau-lite' ),
		) );

		$wp_customize->add_setting( 'restau_lite_gallery_text', array( 'default' => esc_html__( 'Learn more about our restaurant', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_textarea', ) );
		$wp_customize->add_control( 'restau_lite_gallery_text', array(
			'type' => 'textarea',
			'section' => 'restau_lite_gallery_section', // Required, core or custom.
			'label' => esc_attr__( 'Description', 'restau-lite' ),
		) );

		$wp_customize->add_setting( 'restau_lite_gallery_link_title', array( 'default' => esc_html__( 'Gallery', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
		$wp_customize->add_control( 'restau_lite_gallery_link_title', array(
			'type' => 'text',
			'section' => 'restau_lite_gallery_section', // Required, core or custom.
			'label' => esc_attr__( "Link Title", 'restau-lite' ),
		) );

		$wp_customize->add_setting( 'restau_lite_gallery_link_url', array( 'default' => '#', 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_url', ) );
		$wp_customize->add_control( 'restau_lite_gallery_link_url', array(
			'type' => 'url',
			'section' => 'restau_lite_gallery_section', // Required, core or custom.
			'label' => esc_attr__( "Link URL", 'restau-lite' ),
		) );

		$wp_customize->add_setting( 'restau_lite_gallery_image', array( 'default' => '', 'transport' => 'postMessage', 'sanitize_callback' => 'attachment_url_to_postid', ) );
		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'restau_lite_gallery_image', array(
	        'label'    => esc_attr__( 'Featured Image', 'restau-lite' ),
	        'section'  => 'restau_lite_gallery_section',
	        'settings' => 'restau_lite_gallery_image',
		) ) );

		$wp_customize->add_setting( 'restau_lite_gallery_enable', array( 'default' => true, 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_bool', ) );
	    $wp_customize->add_control( 'restau_lite_gallery_enable', array(
			'section' => 'restau_lite_gallery_section', // Required, core or custom.
			'label' => esc_attr__( "Use this section?", 'restau-lite' ),
			'type'    => 'checkbox',
		) );



	    /*
    	Menu
    	------------------------------ */
		$wp_customize->add_section( 'restau_lite_menu_section', array(
			'title' => esc_attr__( 'Menu', 'restau-lite' ),
			'description' => esc_attr__( 'Display menu items.', 'restau-lite' ),
			'panel' => 'restau_lite_front_page_sections',
			'priority' => $sections_sorted['restau_lite_menu_section'],
		) );

		$wp_customize->add_setting( 'restau_lite_menu_title_serif', array( 'default' => esc_attr__( 'Our', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
		$wp_customize->add_control( 'restau_lite_menu_title_serif', array(
			'type' => 'text',
			'section' => 'restau_lite_menu_section', // Required, core or custom.
			'label' => esc_attr__( 'Section Serif Title', 'restau-lite' ),
		) );

		$wp_customize->add_setting( 'restau_lite_menu_title', array( 'default' => esc_attr__( 'Menu', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
		$wp_customize->add_control( 'restau_lite_menu_title', array(
			'type' => 'text',
			'section' => 'restau_lite_menu_section', // Required, core or custom.
			'label' => esc_attr__( 'Section Title', 'restau-lite' ),
		) );

		if ( class_exists( 'fdmFoodAndDrinkMenu' ) ) :

			$wp_customize->add_setting( 'restau_lite_menu_menu', array( 'default' => '', 'sanitize_callback' => 'sanitize_key', ) );
		    $wp_customize->add_control( 'restau_lite_menu_menu', array(
				'type' => 'url',
				'section' => 'restau_lite_menu_section', // Required, core or custom.
				'label' => esc_attr__( "Food Menu", 'restau-lite' ),
				'description' => esc_attr__( "Select your menu to display.", 'restau-lite' ),
				'type'    => 'select',
		        'choices'    => restau_lite_get_food_menus()
			) );
		else:
			$wp_customize->add_setting( 'restau_lite_menu_text', array( 'default' => '', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
			$wp_customize->add_control( new restau_lite_Display_Text_Control( $wp_customize, 'restau_lite_menu_text', array(
				'section' => 'restau_lite_menu_section', // Required, core or custom.
				'label' => __( 'To use a food menu you have to install the <a href="https://wordpress.org/plugins/food-and-drink-menu/" target="_blank">Food & Drink plugin</a>', 'restau-lite' ),
			) ) );
		endif;

		$wp_customize->add_setting( 'restau_lite_menu_enable', array( 'default' => true, 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_bool', ) );
	    $wp_customize->add_control( 'restau_lite_menu_enable', array(
			'section' => 'restau_lite_menu_section', // Required, core or custom.
			'label' => esc_attr__( "Use this section?", 'restau-lite' ),
			'type'    => 'checkbox',
		) );



		/*
    	Blog
    	------------------------------ */
		$wp_customize->add_section( 'restau_lite_blog_section', array(
			'title' => esc_attr__( 'Blog', 'restau-lite' ),
			'description' => esc_attr__( "Display blog posts.", 'restau-lite' ),
			'panel' => 'restau_lite_front_page_sections',
			'priority' => $sections_sorted['restau_lite_blog_section'],
		) );

		$wp_customize->add_setting( 'restau_lite_blog_title_serif', array( 'default' => esc_attr__( 'From', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
		$wp_customize->add_control( 'restau_lite_blog_title_serif', array(
			'type' => 'text',
			'section' => 'restau_lite_blog_section', // Required, core or custom.
			'label' => esc_attr__( 'Section Serif Title', 'restau-lite' ),
		) );

		$wp_customize->add_setting( 'restau_lite_blog_title', array( 'default' => esc_attr__( 'The Blog', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
		$wp_customize->add_control( 'restau_lite_blog_title', array(
			'type' => 'text',
			'section' => 'restau_lite_blog_section', // Required, core or custom.
			'label' => esc_attr__( 'Section Title', 'restau-lite' ),
		) );

		$wp_customize->add_setting( 'restau_lite_blog_enable', array( 'default' => true, 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_bool', ) );
	    $wp_customize->add_control( 'restau_lite_blog_enable', array(
			'section' => 'restau_lite_blog_section', // Required, core or custom.
			'label' => esc_attr__( "Use this section?", 'restau-lite' ),
			'type'    => 'checkbox',
		) );


		/*
    	Testimonial
    	------------------------------ */
    	if ( class_exists( 'Jetpack' ) ){

			$wp_customize->add_section( 'restau_lite_testimonials_section', array(
				'title' => esc_attr__( 'Testimonials', 'restau-lite' ),
				'description' => sprintf( __( 'To create a testimonial go to your <a href="%s">Admin Panel > Testimonials > Add New</a>.', 'restau-lite' ), get_admin_url( null, 'post-new.php?post_type=jetpack-testimonial' ) ),
				'panel' => 'restau_lite_front_page_sections',
				'priority' => $sections_sorted['restau_lite_testimonials_section'],
			) );

			$wp_customize->add_setting( 'restau_lite_testimonials_title_serif', array( 'default' => esc_attr__( 'Customers', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
			$wp_customize->add_control( 'restau_lite_testimonials_title_serif', array(
				'type' => 'text',
				'section' => 'restau_lite_testimonials_section', // Required, core or custom.
				'label' => esc_attr__( 'Section Serif Title', 'restau-lite' ),
			) );

			$wp_customize->add_setting( 'restau_lite_testimonials_title', array( 'default' => esc_attr__( 'Testimonials', 'restau-lite' ), 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
			$wp_customize->add_control( 'restau_lite_testimonials_title', array(
				'type' => 'text',
				'section' => 'restau_lite_testimonials_section', // Required, core or custom.
				'label' => esc_attr__( 'Section Title', 'restau-lite' ),
			) );

		    $wp_customize->add_setting( 'restau_lite_testimonial_enable', array( 'default' => true, 'transport' => 'postMessage', 'sanitize_callback' => 'restau_lite_sanitize_bool', ) );
		    $wp_customize->add_control( 'restau_lite_testimonial_enable', array(
				'section' => 'restau_lite_testimonials_section', // Required, core or custom.
				'label' => esc_attr__( "Use this section?", 'restau-lite' ),
				'type'    => 'checkbox',
			) );

		}


	/*
	General Options
	------------------------------ */
	$wp_customize->add_section( 'restau_lite_general_options_section', array(
		'title' => esc_attr__( 'General Options', 'restau-lite' ),
		'priority' => 140
	) );

	$wp_customize->add_setting( 'restau_lite_general_options_animations', array( 'default' => '1', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
	$wp_customize->add_control( 'restau_lite_general_options_animations', array(
		'type' => 'radio',
		'choices'    => array(
            '1' => 'Enable',
            '2' => 'Disable'
        ),
		'section' => 'restau_lite_general_options_section', // Required, core or custom.
		'label' => esc_attr__( 'CSS Animations', 'restau-lite' ),
		'description' => esc_attr__( 'Enable/Disable CSS animation on your site.', 'restau-lite' ),
	) );

	$wp_customize->add_setting( 'restau_lite_bottom_image', array( 'default' => '', 'transport' => 'postMessage', 'sanitize_callback' => 'attachment_url_to_postid', ) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'restau_lite_bottom_image', array(
        'label'    => esc_attr__( 'Bottom Page Image', 'restau-lite' ),
        'section'  => 'restau_lite_general_options_section',
        'settings' => 'restau_lite_bottom_image',
	) ) );


	/*
	PRO Version
	------------------------------ */
	$wp_customize->add_section( 'restau_lite_pro_section', array(
		'title' => esc_attr__( 'PRO version', 'restau-lite' ),
		'priority' => 5,
	) );
	$wp_customize->add_setting( 'restau_lite_probtn', array( 'default' => '', 'sanitize_callback' => 'restau_lite_sanitize_text', ) );
	$wp_customize->add_control( new restau_lite_Display_Text_Control( $wp_customize, 'restau_lite_probtn', array(
		'section' => 'restau_lite_pro_section', // Required, core or custom.
		'label' => sprintf( __( 'Check out the PRO version for more features. %1$s View PRO version %2$s', 'restau-lite' ), '<a target="_blank" class="button" href="https://www.quemalabs.com/theme/restau/" style="width: 80%; margin: 10px auto; display: block; text-align: center;">', '</a>' ),
	) ) );


		

		






}
add_action( 'customize_register', 'restau_lite_customize_register' );











/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function restau_lite_customize_preview_js() {
	
	wp_register_script( 'restau_lite_customizer_preview', get_template_directory_uri() . '/js/customizer-preview.js', array( 'customize-preview' ), '20151024', true );
	wp_localize_script( 'restau_lite_customizer_preview', 'restau_lite_wp_customizer', array(
		'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
		'theme_url' => get_template_directory_uri(),
		'site_name' => get_bloginfo( 'name' )
	));
	wp_enqueue_script( 'restau_lite_customizer_preview' );

}
add_action( 'customize_preview_init', 'restau_lite_customize_preview_js' );


/**
 * Load scripts on the Customizer not the Previewer (iframe)
 */
function restau_lite_customize_js() {

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	
	wp_register_script( 'restau_lite_customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-controls', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), '20151024', true );
	wp_localize_script( 'restau_lite_customizer', 'restau_lite_wp_customizer_admin', array(
		'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
		'theme_url' => get_template_directory_uri(),
		'admin_url' => get_admin_url()
	));
	wp_enqueue_script( 'restau_lite_customizer' );

}
add_action( 'customize_controls_enqueue_scripts', 'restau_lite_customize_js' );










/*
Sanitize Callbacks
*/

/**
 * Sanitize for post's categories
 */
function restau_lite_sanitize_categories( $value ) {
    if ( ! array_key_exists( $value, restau_lite_categories_ar() ) )
        $value = '';
    return $value;
}

/**
 * Sanitize return an non-negative Integer
 */
function restau_lite_sanitize_integer( $value ) {
    return absint( $value );
}

/**
 * Sanitize Any
 */
function restau_lite_sanitize_any( $input ) {
    return $input;
}

/**
 * Sanitize Text
 */
function restau_lite_sanitize_text( $str ) {
	return sanitize_text_field( $str );
} 

/**
 * Sanitize Textarea
 */
function restau_lite_sanitize_textarea( $text ) {
	return wp_filter_post_kses( $text );
}

/**
 * Sanitize URL
 */
function restau_lite_sanitize_url( $url ) {
	return esc_url_raw( $url );
}

/**
 * Sanitize Boolean
 */
function restau_lite_sanitize_bool( $string ) {
	return (bool)$string;
} 

/**
 * Sanitize Text with html
 */
function restau_lite_sanitize_text_html( $str ) {
	$args = array(
			    'a' => array(
			        'href' => array(),
			        'title' => array()
			    ),
			    'br' => array(),
			    'em' => array(),
			    'strong' => array(),
			    'span' => array(),
			);
	return wp_kses( $str, $args );
}

/**
 * Sanitize OpenTable code
 */
function restau_lite_sanitize_ot_code( $str ) {
	$args = array(
			    'script' => array(
			        'type' => array(),
			        'src' => array()
			    )
			);
	return wp_kses( $str, $args );
}

/**
 * Sanitize GPS Latitude and Longitud
 * http://stackoverflow.com/a/22007205
 */
function restau_lite_sanitize_lat_long( $coords ) {
	if ( preg_match( '/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?),[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $coords ) ) {
	    return $coords;
	} else {
	    return 'error';
	}
} 



/**
 * Display Text Control
 * Custom Control to display text
 */
if ( class_exists( 'WP_Customize_Control' ) ) {
	class restau_lite_Display_Text_Control extends WP_Customize_Control {
		/**
		* Render the control's content.
		*/
		public function render_content() {

	        $wp_kses_args = array(
			    'a' => array(
			        'href' => array(),
			        'title' => array(),
			        'data-section' => array(),
			    ),
			    'br' => array(),
			    'em' => array(),
			    'strong' => array(),
			    'span' => array(),
			);
			$label = wp_kses( $this->label, $wp_kses_args );
	        ?>
			<p><?php echo $label; ?></p>		
		<?php
		}
	}
}



/*
* AJAX call to retreive an image URI by its ID
*/
add_action( 'wp_ajax_nopriv_restau_lite_get_image_src', 'restau_lite_get_image_src' );
add_action( 'wp_ajax_restau_lite_get_image_src', 'restau_lite_get_image_src' );

function restau_lite_get_image_src() {
	$image_id = $_POST['image_id'];
	$image = wp_get_attachment_image_src( absint( $image_id ), 'full' );
	$image = $image[0];
	echo $image;
	die();
}
