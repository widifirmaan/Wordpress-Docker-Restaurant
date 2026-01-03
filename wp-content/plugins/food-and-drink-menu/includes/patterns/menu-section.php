<?php
/**
 * Menu section
 */
return array(
    'title'       =>	__( 'Menu Section', 'food-and-drink-menu' ),
    'description' =>	_x( 'Adds a restaurant menu section.', 'Block pattern description', 'food-and-drink-menu' ),
    'categories'  =>	array( 'fdm-block-patterns' ),
    'content'     =>	'<!-- wp:group {"className":"fdm-pattern-section"} -->
                        <div class="wp-block-group fdm-pattern-section"><!-- wp:food-and-drink-menu/menu-section /--></div>
                        <!-- /wp:group -->',
);
