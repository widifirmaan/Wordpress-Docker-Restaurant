<?php
/**
 * Template part for displaying posts.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Restau Lite
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="row">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php $blog_layout = get_theme_mod( 'restau_lite_blog_layout', '2' ); ?>
			<?php $blog_layout_class = ( $blog_layout == '1' ? 'col-md-6' : 'col-md-12' ); ?>
			<?php echo '<div class="post-image ' . $blog_layout_class . '">'; ?>
	            <a href="<?php echo esc_url( get_permalink() ); ?>" class="ql_thumbnail_hover" rel="bookmark">
	                <?php the_post_thumbnail( ( $blog_layout == '1' ? 'restau_lite_post_square' : 'restau_lite_post' ) ); ?>
	            </a>
	        </div><!-- /post-image -->
        <?php endif; ?>

        <div class="post-content <?php if ( has_post_thumbnail() && $blog_layout == '1' ) : echo 'col-md-6'; else: echo 'col-md-12'; endif;?> ">

			<header class="entry-header">
        		<?php the_title( sprintf( '<h2 class="post-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
        	</header><!-- .entry-header -->

        	<div class="entry-content">
				<?php
					the_content();
				?>

				<?php
					wp_link_pages( array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'restau-lite' ),
						'after'  => '</div>',
					) );
				?>
			</div><!-- .entry-content -->

			<div class="clearfix"></div>
			
			<?php if ( 'post' === get_post_type() ) : ?>
			<footer class="entry-footer">
				<div class="metadata">
	                <?php restau_lite_metadata(); ?>
	                <div class="clearfix"></div>
	            </div><!-- /metadata -->
            </footer><!-- .entry-footer -->
            <?php endif; ?>


		</div><!-- /post_content -->
	</div><!-- /row -->
</article><!-- #post-## -->
