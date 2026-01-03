<?php
/**
 * Include the TGM_Plugin_Activation class.
 */
require get_template_directory() . '/inc/theme-functions/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'restau_lite_register_required_plugins' );
/**
 * Register the required plugins for this theme.
 *
 * In this example, we register two plugins - one included with the TGMPA library
 * and one from the .org repo.
 *
 * The variable passed to tgmpa_register_plugins() should be an array of plugin
 * arrays.
 *
 * This function is hooked into tgmpa_init, which is fired within the
 * TGM_Plugin_Activation class constructor.
 */
function restau_lite_register_required_plugins() {

    /**
     * Array of plugin arrays. Required keys are name and slug.
     * If the source is NOT from the .org repo, then source is also required.
     */
    $plugins = array(
        // This is an example of how to include a plugin pre-packaged with a theme
        array(
            'name'               => 'Jetpack',
            'slug'               => 'jetpack',
            'version'           => '4.4.1',
            'required'           => false,
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'               => 'Food and Drink Menu',
            'slug'               => 'food-and-drink-menu',
            'version'           => '1.5',
            'required'           => false,
            'force_activation'   => false,
            'force_deactivation' => false,
        )
    );

    $config = array(
        'id'           => 'restau-lite',                 // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '',                      // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins', // Menu slug.
        'has_notices'  => true,                    // Show admin notices or not.
        'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => false,                   // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table.
    );


    tgmpa( $plugins, $config );

}