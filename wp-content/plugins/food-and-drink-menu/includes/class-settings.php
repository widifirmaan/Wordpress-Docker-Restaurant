<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Class to register all settings in the settings panel
 */
class fdmSettings {

	/**
	 * Default values for settings
	 * @since 2.0.0
	 */
	public $defaults = array();

	/**
	 * Stores the legacy premium settings
	 * @since 2.0.0
	 */
	public $premium_settings = array();

	/**
	 * Stored values for settings
	 * @since 2.0.0
	 */
	public $settings = array();

	/**
	 * Currencies accepted for deposits
	 */
	public $currency_options = array(
		'AUD' => 'Australian Dollar',
		'BRL' => 'Brazilian Real',
		'CAD' => 'Canadian Dollar',
		'CZK' => 'Czech Koruna',
		'DKK' => 'Danish Krone',
		'EUR' => 'Euro',
		'HKD' => 'Hong Kong Dollar',
		'HUF' => 'Hungarian Forint',
		'ILS' => 'Israeli New Sheqel',
		'JPY' => 'Japanese Yen',
		'MYR' => 'Malaysian Ringgit',
		'MXN' => 'Mexican Peso',
		'NOK' => 'Norwegian Krone',
		'NZD' => 'New Zealand Dollar',
		'PHP' => 'Philippine Peso',
		'PLN' => 'Polish Zloty',
		'GBP' => 'Pound Sterling',
		'RUB' => 'Russian Ruble',
		'SGD' => 'Singapore Dollar',
		'SEK' => 'Swedish Krona',
		'CHF' => 'Swiss Franc',
		'TWD' => 'Taiwan New Dollar',
		'THB' => 'Thai Baht',
		'TRY' => 'Turkish Lira',
		'USD' => 'U.S. Dollar',			
	);

	public function __construct() {

		add_action( 'init', array( $this, 'set_defaults' ) );

		// Call when plugin is initialized on every page load
		add_action( 'init', array( $this, 'load_settings_panel' ) );

		// Add filters on the menu style so we can apply the setting option
		add_filter( 'fdm_menu_args', array( $this, 'set_style' ) );
		add_filter( 'fdm_shortcode_menu_atts', array( $this, 'set_style' ) );
		add_filter( 'fdm_shortcode_menu_section_atts', array( $this, 'set_style' ) );
		add_filter( 'fdm_shortcode_menu_item_atts', array( $this, 'set_style' ) );

	}

	/**
	 * Set the default values for different settings in the plugin
	 *
	 * @since 1.5
	 */
	public function set_defaults() {

		$this->defaults = array(
			'fdm-pro-style' 						=> 'classic',
			'fdm-sidebar-click-action' 				=> 'onlyselected',
			'fdm-menu-section-image-placement' 		=> 'hidden',
			'fdm-details-lightbox'					=> 'lightbox',
			'fdm-related-items' 					=> 'none',	
			'fdm-currency-symbol-location' 			=> 'before',
			'fdm-currency-symbol' 					=> '',
			'fdm-image-style-columns' 				=> 'four',
			'fdm-refined-style-columns' 			=> 'one',
			'fdm-luxe-style-columns' 			=> 'one',
			'fdm-ordering-style-columns' 			=> 'five',
			'fdm-item-flag-icon-size' 				=> '32',
			'fdm-price-filtering-type' 				=> 'textbox',
			'time-format' 							=> _x( 'h:i A', 'Default time format for display. Must match formatting rules at http://amsul.ca/pickadate.js/time/#formats', 'food-and-drink-menu' ),
			'date-format' 							=> _x( 'mmmm d, yyyy', 'Default date format for display. Must match formatting rules at http://amsul.ca/pickadate.js/date/#formats', 'food-and-drink-menu' ),

			'fdm-order-cart-location'		 		=> 'side',
			'fdm-order-cart-style'		 			=> 'default',

			'fdm-enable-ordering-progress-display' 	=> false,
			'fdm-ordering-order-delete-time'		=> 7,
			'ordering-tax-rate'						=> 0,
			'ordering-payment-gateway'				=> 'paypal',
			'ordering-payment-mode'					=> 'live',
			'fdm-ordering-reply-to-address'			=> get_option( 'admin_email' ),
			'fdm-ordering-reply-to-name'			=> get_option( 'blogname' ),

			'label-order-failed'					=> __( 'Order not successfully created', 'food-and-drink-menu' ),
			'label-order-success'					=> __( 'Order was successfully created', 'food-and-drink-menu' ),
			'label-order-payment-success'			=> __( 'You have successfully made a payment of %s', 'food-and-drink-menu' ),
			'label-order-payment-failed'			=> __( 'Your payment was declined with the following error code %s', 'food-and-drink-menu' ),

			// Payment defaults
			'paypal-email'							=> get_option( 'admin_email' ),
			'ordering-currency'						=> 'USD',
			'ordering-payment-mode'					=> 'live',

			'customer-notification-type'				=> 'email',
			'ultimate-purchase-email'					=> get_option( 'admin_email' ),
			'customer-email-subject' 					=> _x( 'Your order has been accepted', 'The subject for the email sent to the customer when their order is accepted.', 'food-and-drink-menu' ),
			'customer-email-template' 					=> _x( 'Your order with {site_name} has been accepted:

Order Number: {order_number}

Name: {name}
Email: {email}
Phone: {phone}
Note: {note}
Payment Amount: {payment_amount}
{custom_fields}

Items ordered:

{order_items}

&nbsp;

<em>This message was sent by {site_link} on {current_time}.</em>',
				'Default email sent to the customer when a new order is received. The tags in {brackets} will be replaced by the appropriate content and should be left in place. HTML is allowed, but be aware that many email clients do not handle HTML very well.',
				'food-and-drink-menu'
			),

			'admin-email-subject' 					=> _x( 'New Order Submitted', 'The subject for the email sent to the admin when a new order is received.', 'food-and-drink-menu' ),
			'admin-email-template' 					=> _x( 'A new order has been submitted at {site_name}:

Name: {name}
Email: {email}
Phone: {phone}
Note: {note}
Payment Amount: {payment_amount}
{custom_fields}

Items ordered:

{order_items}

Click on the following link to accept the order: {accept_link}

&nbsp;

<em>This message was sent by {site_link} on {current_time}.</em>',
				'Default email sent to the admin when a new order is received. The tags in {brackets} will be replaced by the appropriate content and should be left in place. HTML is allowed, but be aware that many email clients do not handle HTML very well.',
				'food-and-drink-menu'
			),
		);

		$this->defaults = apply_filters( 'fdm_defaults', $this->defaults, $this );
	}

	/**
	 * Get the theme supports options for this plugin
	 *
	 * This mimics the core get_theme_support function, except it automatically
	 * looks up this plugin's feature set and searches for features within
	 * those settings.
	 *
	 * @param string $feature The feature support to check
	 * @since 1.5
	 */
	public function get_theme_support( $feature ) {

		$theme_support = get_theme_support( 'food-and-drink-menu' );

		if ( !is_array( $theme_support ) ) {
			return apply_filters( 'fdm_get_theme_support_' . $feature, false, $theme_support );
		}

		$theme_support = $theme_support[0];

		if ( isset( $theme_support[$feature] ) ) {
			return apply_filters( 'fdm_get_theme_support_' . $feature, $theme_support[$feature], $theme_support );
		}

		return apply_filters( 'fdm_get_theme_support_' . $feature, false, $theme_support );
	}

	/**
	 * Get a setting's value or fallback to a default if one exists
	 * @since 2.0.0
	 */
	public function get_setting( $setting ) {

		if ( empty( $this->settings ) ) {
			$this->settings = get_option( 'food-and-drink-menu-settings' );
		}

		if ( !empty( $this->settings[ $setting ] ) ) {
			return apply_filters( 'fdm-setting-' . $setting, $this->settings[ $setting ] );
		}

		if ( empty( $this->premium_settings ) ) {
			$this->premium_settings = get_option( 'food-and-drink-menu-extra-settings' );
		}

		if ( !empty( $this->premium_settings[ $setting ] ) ) {
			return apply_filters( 'fdm-setting-' . $setting, $this->premium_settings[ $setting ] );
		}

		if ( !empty( $this->defaults[ $setting ] ) ) {
			return apply_filters( 'fdm-setting-' . $setting, $this->defaults[ $setting ] );
		}

		return apply_filters( 'fdm-setting-' . $setting, isset( $this->settings[ $setting ] ) ? $this->settings[ $setting ] : null );
	}

	/**
	 * Set a setting to a particular value
	 * @since 2.0.5
	 */
	public function set_setting( $setting, $value ) {

		if ( empty( $this->settings ) ) {
			$this->settings = get_option( 'food-and-drink-menu-settings' );
		}

		if ( $setting ) {
			$this->settings[ $setting ] = $value; 
		}
	}

	/**
	 * Save all settings, to be used with set_setting
	 * @since 2.0.5
	 */
	public function save_settings() {
		
		update_option( 'food-and-drink-menu-settings', $this->settings );
	}

	/**
	 * Load the admin settings page
	 * @since 1.1
	 * @sa https://github.com/NateWr/simple-admin-pages
	 */
	public function load_settings_panel() {
		global $fdm_controller;

		require_once( FDM_PLUGIN_DIR . '/lib/simple-admin-pages/simple-admin-pages.php' );

		// Insantiate the Simple Admin Library so that we can add a settings page
		$sap = sap_initialize_library(
			array(
				'version'		=> '2.6.19', // Version of the library
				'lib_url'		=> FDM_PLUGIN_URL . '/lib/simple-admin-pages/', // URL path to sap library
				'theme'			=> 'blue',
			)
		);

		// Create a page for the options under the Settings (options) menu
		$sap->add_page(
			'submenu', 				// Admin menu which this page should be added to
			array(					// Array of key/value pairs matching the AdminPage class constructor variables
				'id'			=> 'food-and-drink-menu-settings',
				'title'			=> __( 'Settings', 'food-and-drink-menu' ),
				'menu_title'	=> __( 'Settings', 'food-and-drink-menu' ),
				'description'	=> '',
				'capability'	=> 'manage_options',
				'parent_menu'	=> 'edit.php?post_type=fdm-menu',
				'default_tab'	=> 'fdm-basic-settings'
			)
		);

		// Create a tab for basic settings
		$sap->add_section(
			'food-and-drink-menu-settings',	// Page to add this section to
			array(								// Array of key/value pairs matching the AdminPageSection class constructor variables
				'id'				=> 'fdm-basic-settings',
				'title'				=> __( 'Basic', 'food-and-drink-menu' ),
				'is_tab'			=> true,
				'tutorial_yt_id'	=> 'H2F8tEshWKw',
			)
		);

		// Create a section to choose a default style
		$sap->add_section(
			'food-and-drink-menu-settings',	// Page to add this section to
			array(								// Array of key/value pairs matching the AdminPageSection class constructor variables
				'id'			=> 'fdm-style-settings',
				'title'			=> __( 'Style', 'food-and-drink-menu' ),
				'description'	=> __( 'Choose what style you would like to use for your menu.', 'food-and-drink-menu' ),
				'tab'			=> 'fdm-basic-settings'
			)
		);

		$options = array();
		foreach( $fdm_controller->styles as $style ) {
			$options[$style->id] = $style->label;
		}
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-style-settings',
			'select',
			array(
				'id'			=> 'fdm-style',
				'title'			=> __( 'Menu Formatting', 'food-and-drink-menu' ),
				'description'	=> __( 'Choose the formatting for your menus.', 'food-and-drink-menu' ),
				'blank_option'	=> false,
				'options'		=> $options
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-style-settings',
			'toggle',
			array(
				'id'			=> 'fdm-sidebar',
				'title'			=> __( 'Enable Sidebar', 'food-and-drink-menu' ),
				'description'	=> __( 'Display a sidebar for your menu that allows visitors to choose what section they want to view.', 'food-and-drink-menu' )
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-style-settings',
			'select',
			array(
				'id'			=> 'fdm-sidebar-click-action',
				'title'			=> __( 'Sidebar Click Action', 'food-and-drink-menu' ),
				'description'	=> __( 'Choose what happens when you click a section in the menu sidebar. Only Selected will display only the chosen section, with no scrolling feature. Scroll displays all the sections and then scrolls to the chosen one.', 'food-and-drink-menu' ),
				'blank_option'	=> false,
				'options'		=> array(
					'onlyselected' 	=> 'Only Selected',
					'scroll' 	=> 'Scroll',
				),
				'conditional_on'		=> 'fdm-sidebar',
				'conditional_on_value'	=> true
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-style-settings',
			'toggle',
			array(
				'id'			=> 'fdm-sidebar-mobile-expand',
				'title'			=> __( 'Expandable Mobile Sidebar', 'food-and-drink-menu' ),
				'description'	=> __( 'Enabling this will make it so that, on smaller devices, the sidebar is hidden by default and a button is displayed to show/expand the sidebar.', 'food-and-drink-menu' ),
				'conditional_on'		=> 'fdm-sidebar',
				'conditional_on_value'	=> true
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-style-settings',
			'select',
			array(
				'id'			=> 'fdm-menu-section-image-placement',
				'title'			=> __( 'Section Images', 'food-and-drink-menu' ),
				'description'	=> __( 'Choose the location, if any, for your section images relative to the title.', 'food-and-drink-menu' ),
				'blank_option'	=> false,
				'options'		=> array(
					'hidden' 		=> 'Hidden',
					'background' 	=> 'Background',
					'above' 		=> 'Above',
					'below' 		=> 'Below'
				)
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-style-settings',
			'toggle',
			array(
				'id'			=> 'fdm-display-section-descriptions',
				'title'			=> __( 'Display Section Descriptions', 'food-and-drink-menu' ),
				'description'	=> __( 'Enable this if you want to display the section descriptions in the main menu area.', 'food-and-drink-menu' ),
			)
		);

		// Create a section to enable/disable specific features
		$sap->add_section(
			'food-and-drink-menu-settings',	// Page to add this section to
			array(								// Array of key/value pairs matching the AdminPageSection class constructor variables
				'id'			=> 'fdm-enable-settings',
				'title'			=> __( 'Functionality', 'food-and-drink-menu' ),
				'description'	=> __( 'Choose what features of the menu items you wish to enable or disable.', 'food-and-drink-menu' ),
				'tab'			=> 'fdm-basic-settings'
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-enable-settings',
			'toggle',
			array(
				'id'			=> 'fdm-disable-price',
				'title'			=> __( 'Disable Price', 'food-and-drink-menu' ),
				'description'	=> __( 'Disable all pricing options for menu items.', 'food-and-drink-menu' )
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-enable-settings',
			'toggle',
			array(
				'id'			=> 'fdm-disable-price-decimals',
				'title'			=> __( 'Disable Price Decimals', 'food-and-drink-menu' ),
				'description'	=> __( 'Don\'t display decimal places in the menu item prices. This setting only works if there is no text in your prices.', 'food-and-drink-menu' )
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-enable-settings',
			'toggle',
			array(
				'id'			=> 'fdm-disable-microdata',
				'title'			=> __( 'Disable Microdata', 'food-and-drink-menu' ),
				'description'	=> __( 'Disable the structured data that is automatically added to the menu page. Structured data is used by search engines and other services to intepret your menu.', 'food-and-drink-menu' )
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-enable-settings',
			'radio',
			array(
				'id'			=> 'fdm-details-lightbox',
				'title'			=> __( 'Product Details', 'food-and-drink-menu' ),
				'description'	=> __( 'Should visitors be able to click on menu items to view more details about them (custom fields, related items, etc.)? If so, should that display in a lightbox or redirect to the permalink page?', 'food-and-drink-menu' ),
				'options'		=> array(
					'disabled'		=> 'Disabled',
					'lightbox'		=> 'Lightbox',
					'permalink'		=> 'Permalink Page'
				),
				'default'		=> $this->defaults['fdm-details-lightbox']
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-enable-settings',
			'text',
			array(
				'id'			=> 'fdm-currency-symbol',
				'title'			=> __( 'Currency Symbol', 'food-and-drink-menu' ),
				'description'	=> __( 'The symbol added either before or after menu prices.', 'food-and-drink-menu' ),
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-enable-settings',
			'select',
			array(
				'id'			=> 'fdm-currency-symbol-location',
				'title'			=> __( 'Currency Symbol Location', 'food-and-drink-menu' ),
				'description'	=> __( 'Choose whether the currency symbol be displayed before or after the price.', 'food-and-drink-menu' ),
				'blank_option'	=> false,
				'options'		=> array(
					'before' 	=> 'Before',
					'after' 	=> 'After'
				)
			)
		);

		// Adds in a section to handle the source map options
		$sap->add_section(
			'food-and-drink-menu-settings',
			array(
				'id'    => 'fdm-google-map',
				'title' => __( 'Google Map', 'food-and-drink-menu' ),
				'tab'	=> 'fdm-basic-settings'
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-google-map',
			'toggle',
			array(
				'id'			=> 'fdm-disable-src-map',
				'title'			=> __( 'Disable Source Map', 'food-and-drink-menu' ),
				'description'	=> __( 'Disable the source map.', 'food-and-drink-menu' )
			)
		);
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-google-map',
			'text',
			array(
				'id'          => 'fdm-google-map-api-key',
				'title'       => __( 'Google Maps API Key', 'food-and-drink-menu' ),
				'description' => sprintf(
					__( 'Google requires an API key to use their maps. %sGet an API key%s. A full walk-through is available in the %sdocumentation%s.', 'food-and-drink-menu' ),
					'<a href="https://developers.google.com/maps/documentation/javascript/get-api-key">',
					'</a>',
					'<a href="http://doc.fivestarplugins.com/plugins/food-and-drink-menu/user/settings/google-maps-api-key">',
					'</a>'
				),
				'conditional_on'		=> 'fdm-disable-src-map',
				'conditional_on_value'	=> false
			)
		);

		// Adds in a section for the QR code
		$sap->add_section(
			'food-and-drink-menu-settings',
			array(
				'id'    => 'fdm-qr-code',
				'title' => __( 'QR Code', 'food-and-drink-menu' ),
				'tab'	=> 'fdm-basic-settings'
			)
		);

		$qr_code = $fdm_controller->settings->get_setting('fdm-qr-code-url');
		$qr_code_description = __( 'Enter the URL of your menu/ordering page here and it will generate a QR code, which you can then use at your restaurant to let people view a contactless menu.', 'food-and-drink-menu' );
		if ( $qr_code != '' ) {
			$qr_code_description .= '<br><br>';
			$qr_code_description .= '<a href="https://api.qrserver.com/v1/create-qr-code/?data=' . $qr_code . '&size=600x600&format=png" target="_blank"><img class="fsp-fdm-admin-qr-code" src="https://api.qrserver.com/v1/create-qr-code/?data=' . $qr_code . '&size=600x600&format=png"></a>';
		}
		
		$sap->add_setting(
			'food-and-drink-menu-settings',
			'fdm-qr-code',
			'text',
			array(
				'id'          	=> 'fdm-qr-code-url',
				'title'       	=> __( 'QR Code', 'food-and-drink-menu' ),
				'description'	=> $qr_code_description
			)
		);

		// Create a section for advanced options
		// $sap->add_section(
		// 	'food-and-drink-menu-settings',	// Page to add this section to
		// 	array(								// Array of key/value pairs matching the AdminPageSection class constructor variables
		// 		'id'			=> 'fdm-advanced-settings',
		// 		'title'			=> __( 'Advanced Options', 'food-and-drink-menu' )
		// 	)
		// );
		// $sap->add_setting(
		// 	'food-and-drink-menu-settings',
		// 	'fdm-advanced-settings',
		// 	'text',
		// 	array(
		// 		'id'			=> 'fdm-item-thumb-width',
		// 		'title'			=> __( 'Menu Item Photo Width', 'food-and-drink-menu' ),
		// 		'description'	=> sprintf(
		// 			esc_html__( 'The width in pixels of menu item thumbnails. Leave this field empty to preserve the default (600x600). After changing this setting, you may need to %sregenerate your thumbnails%s.', 'food-and-drink-menu' ),
		// 			'<a href="http://doc.themeofthecrop.com/plugins/food-and-drink-menu/user/faq#image-sizes">',
		// 			'</a>'
		// 		),
		// 	)
		// );
		// $sap->add_setting(
		// 	'food-and-drink-menu-settings',
		// 	'fdm-advanced-settings',
		// 	'text',
		// 	array(
		// 		'id'			=> 'fdm-item-thumb-height',
		// 		'title'			=> __( 'Menu Item Photo Height', 'food-and-drink-menu' ),
		// 		'description'	=> sprintf(
		// 			esc_html__( 'The height in pixels of menu item thumbnails. Leave this field empty to preserve the default (600x600). After changing this setting, you may need to %sregenerate your thumbnails%s.', 'food-and-drink-menu' ),
		// 			'<a href="http://doc.themeofthecrop.com/plugins/food-and-drink-menu/user/faq#image-sizes">',
		// 			'</a>'
		// 		),
		// 	)
		// );

		/**
	     * Premium options preview only
	     */
	    // "Advanced" Tab
	    $sap->add_section(
	      'food-and-drink-menu-settings',
	      array(
	        'id'     				=> 'fdm-advanced-tab',
	        'title'  				=> __( 'Advanced', 'food-and-drink-menu' ),
	        'is_tab' 				=> true,
	        'tutorial_yt_id'		=> 'lWXlDd-eQcM',
	        'show_submit_button' 	=> $this->show_submit_button( 'advanced' )
	      )
	    );
	    $sap->add_section(
	      'food-and-drink-menu-settings',
	      array(
	        'id'       => 'fdm-advanced-tab-body',
	        'tab'      => 'fdm-advanced-tab',
	        'callback' => $this->premium_info( 'advanced' )
	      )
	    );
	
	    // "Ordering" Tab
	    $sap->add_section(
	      'food-and-drink-menu-settings',
	      array(
	        'id'     				=> 'fdm-ordering-tab',
	        'title'  				=> __( 'Ordering', 'food-and-drink-menu' ),
	        'is_tab' 				=> true,
	        'tutorial_yt_id'		=> 'sqag_bMMOeo',
	        'show_submit_button' 	=> $this->show_submit_button( 'ordering' )
	      )
	    );
	    $sap->add_section(
	      'food-and-drink-menu-settings',
	      array(
	        'id'       => 'fdm-ordering-tab-body',
	        'tab'      => 'fdm-ordering-tab',
	        'callback' => $this->premium_info( 'ordering' )
	      )
	    );

	    // "Custom Fields" Tab
	    $sap->add_section(
	      'food-and-drink-menu-settings',
	      array(
	        'id'     				=> 'fdm-custom-fields-tab',
	        'title'  				=> __( 'Custom Fields', 'food-and-drink-menu' ),
	        'is_tab' 				=> true,
	        'tutorial_yt_id'		=> 'j3-KDFeUlX0',
	        'show_submit_button' 	=> $this->show_submit_button( 'custom_fields' )
	      )
	    );
	    $sap->add_section(
	      'food-and-drink-menu-settings',
	      array(
	        'id'       => 'fdm-custom-fields-tab-body',
	        'tab'      => 'fdm-custom-fields-tab',
	        'callback' => $this->premium_info( 'custom_fields' )
	      )
	    );

	    // "Labelling" Tab
		$sap->add_section(
		  'food-and-drink-menu-settings',
		  array(
		    'id'     				=> 'fdm-labelling-tab',
		    'title'  				=> __( 'Labelling', 'food-and-drink-menu' ),
		    'is_tab' 				=> true,
	        'tutorial_yt_id'		=> 'VUhWNXoXLPY',
		    'show_submit_button' 	=> $this->show_submit_button( 'labelling' )
		  )
		);
		$sap->add_section(
		  'food-and-drink-menu-settings',
		  array(
		    'id'       => 'fdm-labelling-tab-body',
		    'tab'      => 'fdm-labelling-tab',
		    'callback' => $this->premium_info( 'labelling' )
		  )
		);
	
	    // "Styling" Tab
	    $sap->add_section(
	      'food-and-drink-menu-settings',
	      array(
	        'id'     				=> 'fdm-styling-tab',
	        'title'  				=> __( 'Styling', 'food-and-drink-menu' ),
	        'is_tab' 				=> true,
	        'tutorial_yt_id'		=> 'QqQsKaGTdUY',
	        'show_submit_button' 	=> $this->show_submit_button( 'styling' )
	      )
	    );
	    $sap->add_section(
	      'food-and-drink-menu-settings',
	      array(
	        'id'       => 'fdm-styling-tab-body',
	        'tab'      => 'fdm-styling-tab',
	        'callback' => $this->premium_info( 'styling' )
	      )
	    );

		// Create filter so addons can modify the settings page or add new pages
		$sap = apply_filters( 'fdm_settings_page', $sap, $this );

		// Backwards compatibility when the sap library went to version 2
		$sap->port_data(2);

		// Register all admin pages and settings with WordPress
		$sap->add_admin_menus();
	}

	public function show_submit_button( $permission_type = '' ) {
		global $fdm_controller;

		if ( $fdm_controller->permissions->check_permission( $permission_type ) ) {
			return true;
		}

		return false;
	}
	
	public function premium_info( $section_and_perm_type ) {
		global $fdm_controller;

		$is_premium_user = $fdm_controller->permissions->check_permission( $section_and_perm_type );
		$is_helper_installed = defined( 'FSPPH_PLUGIN_FNAME' ) && is_plugin_active( FSPPH_PLUGIN_FNAME );

		if ( $is_premium_user || $is_helper_installed ) {
			return false;
		}

		$content = '';

		$also_gives_access_to = ( $section_and_perm_type == 'ordering' ? __( 'The ultimate version also gives you access to all of the premium features, including:', 'food-and-drink-menu' ) : __( 'The premium version also gives you access to the following features:', 'food-and-drink-menu' ) );

		$premium_features = '
			<p><strong>' . $also_gives_access_to . '</strong></p>
			<ul class="fdm-dashboard-new-footer-one-benefits">
				<li>' . __( 'Advanced Menu Layouts', 'food-and-drink-menu' ) . '</li>
				<li>' . __( 'Custom Menu Fields', 'food-and-drink-menu' ) . '</li>
				<li>' . __( 'Sorting and Filtering', 'food-and-drink-menu' ) . '</li>
				<li>' . __( 'Dietary Icons', 'food-and-drink-menu' ) . '</li>
				<li>' . __( 'Featured Item Flag', 'food-and-drink-menu' ) . '</li>
				<li>' . __( 'Special/Discount Pricing', 'food-and-drink-menu' ) . '</li>
				<li>' . __( 'Advanced Styling Options', 'food-and-drink-menu' ) . '</li>
				<li>' . __( 'Advanced Labelling Options', 'food-and-drink-menu' ) . '</li>
				<li>' . __( 'Email Support', 'food-and-drink-menu' ) . '</li>
			</ul>
			<div class="fdm-dashboard-new-footer-one-buttons">
				<a class="fdm-dashboard-new-upgrade-button" href="https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1&utm_source=fdm_settings&utm_content=' . $section_and_perm_type . '" target="_blank">' . __( 'UPGRADE NOW', 'food-and-drink-menu' ) . '</a>
			</div>
		';

		switch ( $section_and_perm_type ) {

			case 'advanced':

				$content = '
					<div class="fdm-settings-preview">
						<h2>' . __( 'Advanced', 'food-and-drink-menu' ) . '<span>' . __( 'Premium', 'food-and-drink-menu' ) . '</span></h2>
						<p>' . __( 'The advanced options let you enable menu item search, sorting and price filtering, as well as configure the related menu items, menu item flags, specials and discounted prices.', 'food-and-drink-menu' ) . '</p>
						<div class="fdm-settings-preview-images">
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/advanced1.png" alt="FDM advanced screenshot one">
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/advanced2.png" alt="FDM advanced screenshot two">
						</div>
						' . $premium_features . '
					</div>
				';

				break;

			case 'ordering':

				$content = '
					<div class="fdm-settings-preview">
						<h2>' . __( 'Ordering', 'food-and-drink-menu' ) . '<span>' . __( 'Ultimate', 'food-and-drink-menu' ) . '</span></h2>
						<p>' . __( 'You can allow your customers to place orders directly from your menu. There are options to require payment (PayPal or Stripe), set a minimum amount for ordering, set a schedule for when ordering should be available and more!', 'food-and-drink-menu' ) . '</p>
						<p>' . __( 'You can also enable <strong>SMS</strong> or email notifications for your customers. And it syncs with our <strong>Five Star Star Restaurant Manager mobile app</strong>, so you can manage your orders either from the kitchen or on the go!', 'food-and-drink-menu' ) . '</p>
						<div class="fdm-settings-preview-images">
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/ordering1.png" alt="FDM ordering screenshot one">
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/ordering2.png" alt="FDM ordering screenshot two">
						</div>
						' . $premium_features . '
					</div>
				';

				break;

			case 'custom_fields':

				$content = '
					<div class="fdm-settings-preview">
						<h2>' . __( 'Custom Fields', 'food-and-drink-menu' ) . '<span>' . __( 'Premium', 'food-and-drink-menu' ) . '</span></h2>
						<p>' . __( 'You can add extra custom fields to your menu items or to the order form. These can be used to display additional info (toppings, allergies, etc.) or to request info from the customer when ordering.', 'food-and-drink-menu' ) . '</p>
						<div class="fdm-settings-preview-images">
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/customfields.png" alt="FDM custom fields screenshot">
						</div>
						' . $premium_features . '
					</div>
				';

				break;

			case 'labelling':
	
				$content = '
					<div class="fdm-settings-preview">
						<h2>' . __( 'Labelling', 'food-and-drink-menu' ) . '<span>' . __( 'Premium', 'food-and-drink-menu' ) . '</span></h2>
						<p>' . __( 'The labelling options let you change the wording of the different labels that appear on the front end of the plugin. You can use this to translate them, customize the wording for your purpose, etc.', 'food-and-drink-menu' ) . '</p>
						<div class="fdm-settings-preview-images">
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/labelling1.png" alt="FDM labelling screenshot one" />
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/labelling2.png" alt="FDM labelling screenshot two" />
						</div>
						' . $premium_features . '
					</div>
				';
	
				break;

			case 'styling':

				$content = '
					<div class="fdm-settings-preview">
						<h2>' . __( 'Styling', 'food-and-drink-menu' ) . '<span>' . __( 'Premium', 'food-and-drink-menu' ) . '</span></h2>
						<p>' . __( 'The styling options let you choose a menu layout and menu item flag icon size as well as modify the colors, font family, font size and borders of the various elements found in your menus.', 'food-and-drink-menu' ) . '</p>
						<div class="fdm-settings-preview-images">
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/styling1.png" alt="FDM styling screenshot one">
							<img src="' . FDM_PLUGIN_URL . '/assets/img/premium-screenshots/styling2.png" alt="FDM styling screenshot two">
						</div>
						' . $premium_features . '
					</div>
				';

				break;
		}

		return function() use ( $content ) {

			echo wp_kses_post( $content );
		};
	}

	/**
	 * Set the style of a menu or menu item before rendering
	 * @since 1.1
	 */
	public function set_style( $args ) {
		global $fdm_controller;

		if ( !$fdm_controller->settings->get_setting('fdm-style') ) {
			$args['style'] = 'base';
		} else {
			$args['style'] = $fdm_controller->settings->get_setting('fdm-style');
		}

		return $args;
	}

	/**
	 * Returns custom fields that should be displayed for menu items
	 * @since 2.2
	 */
	public function get_menu_item_custom_fields() {

		$fields = json_decode( html_entity_decode( $this->get_setting('fdm-custom-fields') ) );

		$fields = is_array( $fields ) ? $fields : array();

		$menu_item_fields = array();

		foreach ( $fields as $field ) {

			if ( empty( $field->applicable ) or $field->applicable == 'menu_item' ) { 

				$menu_item_fields[] = $field;
			}
		}

		return $menu_item_fields;
	}

	/**
	 * Returns custom fields that should be displayed for ordering
	 * @since 2.2
	 */
	public function get_ordering_custom_fields() {

		$fields = json_decode( html_entity_decode( $this->get_setting('fdm-custom-fields') ) );

		$fields = is_array( $fields ) ? $fields : array();

		$ordering_fields = array();

		foreach ( $fields as $field ) {

			if ( empty( $field->applicable ) or $field->applicable != 'order' ) { continue; }

			$ordering_fields[] = $field;
		}

		return $ordering_fields;
	}

}
