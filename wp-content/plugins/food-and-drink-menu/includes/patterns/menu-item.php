<?php
/**
 * Menu item
 */
return array(
    'title'       =>	__( 'Menu Item', 'food-and-drink-menu' ),
    'description' =>	_x( 'Adds a single restaurant menu item.', 'Block pattern description', 'food-and-drink-menu' ),
    'categories'  =>	array( 'fdm-block-patterns' ),
    'content'     =>	'<!-- wp:group {"className":"fdm-pattern-item"} -->
                        <div class="wp-block-group fdm-pattern-item"><!-- wp:food-and-drink-menu/menu-item /--></div>
                        <!-- /wp:group -->',
);
