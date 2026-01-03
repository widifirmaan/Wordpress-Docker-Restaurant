<?php
/**
 * Menu no sidebar
 */
return array(
    'title'       =>	__( 'Menu with No Sidebar', 'food-and-drink-menu' ),
    'description' =>	_x( 'Adds a restaurant menu with no sidebar.', 'Block pattern description', 'food-and-drink-menu' ),
    'categories'  =>	array( 'fdm-block-patterns' ),
    'content'     =>	'<!-- wp:group {"className":"fdm-pattern-menu fdm-pattern-menu-no-sidebar"} -->
                        <div class="wp-block-group fdm-pattern-menu fdm-pattern-menu-no-sidebar"><!-- wp:food-and-drink-menu/menu /--></div>
                        <!-- /wp:group -->',
);
