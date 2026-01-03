<?php
/**
 * Menu
 */
return array(
    'title'       =>	__( 'Menu', 'food-and-drink-menu' ),
    'description' =>	_x( 'Adds a restaurant menu.', 'Block pattern description', 'food-and-drink-menu' ),
    'categories'  =>	array( 'fdm-block-patterns' ),
    'content'     =>	'<!-- wp:group {"className":"fdm-pattern-menu"} -->
                        <div class="wp-block-group fdm-pattern-menu"><!-- wp:food-and-drink-menu/menu /--></div>
                        <!-- /wp:group -->',
);
