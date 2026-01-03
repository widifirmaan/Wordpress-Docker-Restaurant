<?php
/**
 * Menu items just image three
 */
return array(
    'title'       =>	__( 'Three Menu Items - Image and Title', 'food-and-drink-menu' ),
    'description' =>	_x( 'Adds three menu items. You can choose which three to display. Displays only the image and title of the item.', 'Block pattern description', 'food-and-drink-menu' ),
    'categories'  =>	array( 'fdm-block-patterns' ),
    'content'     =>	'<!-- wp:group {"className":"fdm-pattern-item fdm-pattern-items-3 fdm-pattern-items-just-image fdm-pattern-items-image-title"} -->
                        <div class="wp-block-group fdm-pattern-item fdm-pattern-items-3 fdm-pattern-items-just-image fdm-pattern-items-image-title"><!-- wp:food-and-drink-menu/menu-item /-->
                        <!-- wp:food-and-drink-menu/menu-item /-->
                        <!-- wp:food-and-drink-menu/menu-item /--></div>
                        <!-- /wp:group -->',
);
