<?php
$restau_lite_enable_section = get_theme_mod( 'restau_lite_gallery_enable', true );
if ( $restau_lite_enable_section || is_customize_preview() ) :
?>
<div class="gallery-section" id="gallery-section" <?php if( false == $restau_lite_enable_section ): echo 'style="display: none;"'; endif ?>>

    <div class="gallery-content wow zoomIn" data-wow-delay="300ms">
        <?php
        $restau_lite_gallery_title_serif = get_theme_mod( 'restau_lite_gallery_title_serif', esc_html__( 'Our', 'restau-lite' ) );
        $restau_lite_gallery_title = get_theme_mod( 'restau_lite_gallery_title', esc_html__( 'Space', 'restau-lite' ) );
        $restau_lite_gallery_text = get_theme_mod( 'restau_lite_gallery_text', esc_html__( 'Learn more about our restaurant', 'restau-lite' ) );
        $restau_lite_gallery_link_title = get_theme_mod( 'restau_lite_gallery_link_title', esc_html__( 'Gallery', 'restau-lite' ) );
        $restau_lite_gallery_link_url = get_theme_mod( 'restau_lite_gallery_link_url', esc_html__( '#', 'restau-lite' ) );
        ?>
        <h3 class="style-title"><span><?php echo esc_html( $restau_lite_gallery_title_serif ); ?></span> <?php echo esc_html( $restau_lite_gallery_title ); ?></h3>
        <p><?php echo wp_kses_post( $restau_lite_gallery_text ); ?></p>
        <a href="<?php echo esc_url( $restau_lite_gallery_link_url ); ?>" class="light-btn"><?php echo esc_html( $restau_lite_gallery_link_title ); ?></a>
    </div>
    <?php
    $restau_lite_gallery_image = wp_get_attachment_image_src( absint( get_theme_mod( 'restau_lite_gallery_image' ) ), 'full' );
    $restau_lite_gallery_image = $restau_lite_gallery_image[0];
    if ( empty( $restau_lite_gallery_image ) ) {
        $restau_lite_gallery_image = get_template_directory_uri() . '/images/gallery.jpeg';
    }
    ?>
    <div class="gallery-image wow zoomIn" style="background-image: url(<?php echo esc_url( $restau_lite_gallery_image ); ?>);"></div>
    <div class="clearfix"></div>

</div><!-- /gallery -->
<?php endif ?>