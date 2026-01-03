<?php
$restau_lite_enable_section = get_theme_mod( 'restau_lite_menu_enable', true );
if ( $restau_lite_enable_section || is_customize_preview() ) :

    $restau_lite_menu_image = wp_get_attachment_image_src( absint( get_theme_mod( 'restau_lite_menu_image' ) ), 'full' );
    $restau_lite_menu_image = $restau_lite_menu_image[0];
    if ( empty( $restau_lite_menu_image ) ) {
        $restau_lite_menu_image = get_template_directory_uri() . '/images/reservation_bck.jpg';
    }

?>

    <div class="menu-section" id="menu-section" <?php if( false == $restau_lite_enable_section ): echo 'style="display: none;"'; endif ?>>
        <?php
        $restau_lite_menu_title_serif = get_theme_mod( 'restau_lite_menu_title_serif', esc_html__( 'Our', 'restau-lite' ) );
        $restau_lite_menu_title = get_theme_mod( 'restau_lite_menu_title', esc_html__( 'Menu', 'restau-lite' ) );
        ?>
        <h3 class="style-title"><span><?php echo esc_html( $restau_lite_menu_title_serif ); ?></span> <?php echo esc_html( $restau_lite_menu_title ); ?></h3>
        

        <div class="menu-slider wow fadeInUp">
            


            <?php
            $restau_lite_menu_menu = get_theme_mod( 'restau_lite_menu_menu' );
            if ( $restau_lite_menu_menu  ) {
                
                $args = array(
                    'post_type' => 'fdm-menu',
                    'p' => $restau_lite_menu_menu
                );
                
                $the_query = new WP_Query( $args );
                if ( $the_query->have_posts() ) :
                ?>

                    <?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
                        
                        <?php 
                        $column_one = get_post_meta( $post->ID, 'fdm_menu_column_one', true );
                        $column_one = explode( ',', $column_one );
                        $column_two = get_post_meta( $post->ID, 'fdm_menu_column_two', true );
                        $column_two = explode( ',', $column_two );

                        $sections = array_merge( $column_one, $column_two );

                        foreach ( $sections as $section => $value ) {
                        
                            // Make sure the section has posts before we load the data.
                            $items = new WP_Query( array(
                                'post_type'         => 'fdm-menu-item',
                                'posts_per_page'    => -1,
                                'order'             => 'ASC',
                                'orderby'           => 'menu_order',
                                'tax_query'         => array(
                                    array(
                                        'taxonomy' => 'fdm-menu-section',
                                        'field'    => 'term_id',
                                        'terms'    => $value,
                                    ),
                                ),
                            ));
                           
                            if ( $items->have_posts() ) :
                                $section_object = get_term_by( 'id', $value, 'fdm-menu-section');
                                ?>
                            <div class="menu-slide">
                                <h4 class="menu-slide-title"><?php echo esc_html( $section_object->name ); ?></h4>
                                <ul>

                                <?php while ( $items->have_posts() ) : $items->the_post();
                                        $item_price = get_post_meta( $items->post->ID, 'fdm_item_price' );
                                        $item_price = $item_price[0];
                                        $item_price = explode( '.', $item_price );
                                        ?>
                                        <li>
                                            <h5 class="menu-item-title"><?php the_title(); ?></h5>
                                            <p class="menu-item-desc"><?php echo get_the_content(); ?></p>
                                            <span class="menu-item-price"><?php echo esc_html__( '$', 'restau-lite' ) . esc_html( $item_price[0] ); ?><?php echo (array_key_exists(1, $item_price) ) ? '.<i>' . $item_price[1] . '</i>' : ''; ?></span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                        <?php }//foreach ?>

                    <?php endwhile; ?>
                    
                <?php endif; ?>
            <?php }//if $restau_lite_menu_menu  not empty ?>


        </div><!-- /menu-slider -->
    </div><!-- /menu-section -->

<?php endif ?>