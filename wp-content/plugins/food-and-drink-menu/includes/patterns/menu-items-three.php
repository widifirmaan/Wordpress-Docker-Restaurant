<?php
/**
 * Menu items three
 */
return array(
    'title'       =>	__( 'Three Menu Items', 'food-and-drink-menu' ),
    'description' =>	_x( 'Adds three menu items. You can choose which three to display. Displays only the image (if applicable), title and description.', 'Block pattern description', 'food-and-drink-menu' ),
    'categories'  =>	array( 'fdm-block-patterns' ),
    'content'     =>	'<!-- wp:group {"className":"fdm-pattern-item fdm-pattern-items-3"} -->
                        <div class="wp-block-group fdm-pattern-item fdm-pattern-items-3"><!-- wp:food-and-drink-menu/menu-item /-->
                        <!-- wp:food-and-drink-menu/menu-item /-->
                        <!-- wp:food-and-drink-menu/menu-item /--></div>
                        <!-- /wp:group -->',
);
