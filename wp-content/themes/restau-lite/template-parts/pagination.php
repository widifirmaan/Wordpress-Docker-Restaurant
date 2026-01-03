<div class="clearfix"></div>
<?php
$pagination = get_the_posts_pagination( array(
    'prev_text'          => esc_attr__( 'Previous page', 'restau-lite' ),
	'next_text'          => esc_attr__( 'Next page', 'restau-lite' )
) );
if ( $pagination ) {
	echo '<div class="pagination_wrap">';
	echo wp_kses_post( $pagination );
	echo '</div><!-- /pagination_wrap -->';
}
?>