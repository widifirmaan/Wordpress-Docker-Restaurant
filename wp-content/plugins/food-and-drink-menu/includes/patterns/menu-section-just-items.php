<?php
/**
 * Menu section just items
 */
return array(
    'title'       =>	__( 'Menu Section Just Items', 'food-and-drink-menu' ),
    'description' =>	_x( 'Adds a restaurant menu section with no title or description, only the menu items.', 'Block pattern description', 'food-and-drink-menu' ),
    'categories'  =>	array( 'fdm-block-patterns' ),
    'content'     =>	'<!-- wp:group {"className":"fdm-pattern-section fdm-pattern-section-just-items"} -->
                        <div class="wp-block-group fdm-pattern-section fdm-pattern-section-just-items"><!-- wp:food-and-drink-menu/menu-section /--></div>
                        <!-- /wp:group -->',
);
