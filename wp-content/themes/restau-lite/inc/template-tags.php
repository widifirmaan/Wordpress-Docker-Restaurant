<?php
/**
 * Custom template tags for this theme.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Restau Lite
 */

if ( ! function_exists( 'restau_lite_metadata' ) ) :
/**
 * Prints HTML with meta information for the current post-date/time and author.
 */
function restau_lite_metadata() {

	echo '<ul>';

	$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
	}

	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( 'c' ) ),
		esc_html( get_the_modified_date() )
	);

	echo '<li class="meta_date">' . $time_string . '</li>';

	$byline = sprintf(
		esc_html_x( 'by %s', 'post author', 'restau-lite' ),
		'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
	);

	echo '<li class="meta_comments">' . $byline . '</li>';

	if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
		echo '<li class="meta_date"><span class="comments-link">';
		comments_popup_link( esc_html__( 'Leave a comment', 'restau-lite' ), esc_html__( '1 Comment', 'restau-lite' ), esc_html__( '% Comments', 'restau-lite' ) );
		echo '</span></li>';
	}

	// Hide category and tag text for pages.
	if ( 'post' === get_post_type() && is_single() ) {
		/* translators: used between list items, there is a space after the comma */
		$categories_list = get_the_category_list( esc_html__( ', ', 'restau-lite' ) );
		if ( $categories_list && restau_lite_categorized_blog() ) {
			printf( '<li class="meta_categories"><span class="cat-links">' . esc_html__( 'Posted in %1$s', 'restau-lite' ) . '</span></li>', $categories_list ); // WPCS: XSS OK.
		}
		/* translators: used between list items, there is a space after the comma */
		$tags_list = get_the_tag_list( '', esc_html__( ', ', 'restau-lite' ) );
		if ( $tags_list ) {
			printf( '<li class="meta_tags"><span class="tags-links">' . esc_html__( 'Tagged %1$s', 'restau-lite' ) . '</span></li>', $tags_list ); // WPCS: XSS OK.
		}
	}

	echo '</ul>';


}
endif;



if ( ! function_exists( 'restau_lite_metadata_portfolio' ) ) :
/**
 * Prints HTML with meta information for portfolio posts (Jetpack)
 */
function restau_lite_metadata_portfolio( $post ) {
	$term_list = wp_get_post_terms( $post->ID, 'jetpack-portfolio-type', array( 'fields' => 'all' ) );
	if ( $term_list ) {
		echo '<ul>';
		echo '<li class="meta_categories"><i class="fa fa-folder-open-o"></i> ';
		foreach ( $term_list as $key => $term ) {
		    // The $term is an object, so we don't need to specify the $taxonomy.
		    $term_link = get_term_link( $term );
		    // If there was an error, continue to the next term.
		    if ( is_wp_error( $term_link ) ) {
		        continue;
		    }
		    // We successfully got a link. Print it out.
		    if ( ( $key + 1 ) == count( $term_list ) ) {
		    	echo '<a href="' . esc_url( $term_link ) . '">' . $term->name . '</a>';
		    }else{
		    	echo '<a href="' . esc_url( $term_link ) . '">' . $term->name . '</a>, ';
		    }
		}
		echo '</li>';
		echo '</ul>';
	}
	$term_list = wp_get_post_terms( $post->ID, 'jetpack-portfolio-tag', array( 'fields' => 'all' ) );
	if ( $term_list ) {
		echo '<ul>';
		echo '<li class="meta_categories"><i class="fa fa-tag"></i> ';

		foreach ( $term_list as $key => $term ) {
		    // The $term is an object, so we don't need to specify the $taxonomy.
		    $term_link = get_term_link( $term );
		    // If there was an error, continue to the next term.
		    if ( is_wp_error( $term_link ) ) {
		        continue;
		    }
		    // We successfully got a link. Print it out.
		    if ( ( $key + 1 ) == count( $term_list ) ) {
		    	echo '<a href="' . esc_url( $term_link ) . '">' . $term->name . '</a>';
		    }else{
		    	echo '<a href="' . esc_url( $term_link ) . '">' . $term->name . '</a>, ';
		    }
		}
		echo '</li>';
		echo '</ul>';
	}
}
endif;



/**
 * Returns true if a blog has more than 1 category.
 *
 * @return bool
 */
function restau_lite_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'restau_lite_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts.
		$all_the_cool_cats = get_categories( array(
			'fields'     => 'ids',
			'hide_empty' => 1,

			// We only need to know if there is more than one category.
			'number'     => 2,
		) );

		// Count the number of categories that are attached to the posts.
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'restau_lite_categories', $all_the_cool_cats );
	}

	if ( $all_the_cool_cats > 1 ) {
		// This blog has more than 1 category so restau_lite_categorized_blog should return true.
		return true;
	} else {
		// This blog has only 1 category so restau_lite_categorized_blog should return false.
		return false;
	}
}

/**
 * Flush out the transients used in restau_lite_categorized_blog.
 */
function restau_lite_category_transient_flusher() {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	// Like, beat it. Dig?
	delete_transient( 'restau_lite_categories' );
}
add_action( 'edit_category', 'restau_lite_category_transient_flusher' );
add_action( 'save_post',     'restau_lite_category_transient_flusher' );
