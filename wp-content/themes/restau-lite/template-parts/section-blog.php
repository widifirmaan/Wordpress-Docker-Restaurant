<?php
$restau_lite_enable_section = get_theme_mod( 'restau_lite_blog_enable', true );
if ( $restau_lite_enable_section || is_customize_preview() ) :
?>
        </div><!-- /col-md-12 --> 
    </div><!-- /row -->
</div><!-- /container -->
<div id="blog-section" class="blog-section" <?php if( false == $restau_lite_enable_section ): echo 'style="display: none;"'; endif ?>>
    <?php 
    $blog_url = '';
    if( get_option( 'show_on_front' ) == 'page' ){
        $blog_url = get_permalink( get_option( 'page_for_posts' ) );
    }else{ 
        $blog_url = home_url();
    }
    $restau_lite_blog_title_serif = get_theme_mod( 'restau_lite_blog_title_serif', esc_html__( 'From', 'restau-lite' ) );
    $restau_lite_blog_title = get_theme_mod( 'restau_lite_blog_title', esc_html__( 'The Blog', 'restau-lite' ) );
?>
    <h3 class="style-title wow fadeIn"><a href="<?php echo esc_url( $blog_url ); ?>"><span><?php echo esc_html( $restau_lite_blog_title_serif ); ?></span> <?php echo esc_html( $restau_lite_blog_title ); ?></a></h3>


    	<?php
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 5
        );
        
        $the_query = new WP_Query( $args );
        if ( $the_query->have_posts() ) :
    	?>

            

		<div class="blog-wrap js-flickity" data-flickity-options='{ "cellAlign": "left", "contain": true, "prevNextButtons": false, "pageDots": true }'>

		<?php /* Start the Loop */ ?>
		<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
			<?php $blog_image = ''; ?>
            <?php if ( has_post_thumbnail() ) {
                $blog_image = 'style="background-image: url(' . esc_url( get_the_post_thumbnail_url( $the_query->post->ID, 'restau_lite_blog-section' ) ) . ');"';
            } ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class('blog-item wow fadeIn');  echo $blog_image; ?>>
                <a href="<?php echo esc_url( get_permalink() ) ?>" class="blog-item-link"></a>
                <div class="blog-item-entry">

                    <time class="blog-time-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd M' ) ) ?></time>
                    <?php the_title( sprintf( '<h2 class="blog-item-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
                    <?php
                    $byline = sprintf(
                    esc_html_x( 'by %s', 'post author', 'restau-lite' ),
                    '<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>' );
                    
                    echo '<p>' . $byline . ' ';
                    if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
                        esc_html_e( 'with ', 'restau-lite' );
                        comments_popup_link( esc_html__( 'No comments', 'restau-lite' ), esc_html__( '1 Comment', 'restau-lite' ), esc_html__( '% Comments', 'restau-lite' ) );
                    }
                    echo '</p>';
                    ?>
                </div>

            </article>

		<?php endwhile; ?>

		</div><!-- .blog-wrap -->

	<?php endif; ?>


</div><!-- blog-section -->
<div class="container">
    <div class="row">
        <div class="col-md-12">
<?php endif ?>