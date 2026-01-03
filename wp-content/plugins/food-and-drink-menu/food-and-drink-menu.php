<?php
/**
 * Plugin Name: Five Star Restaurant Menu and Food Ordering
 * Plugin URI: https://www.fivestarplugins.com/plugins/five-star-restaurant-menu/
 * Description: Restaurant menu and food ordering system that is easy to set up and integrates with any theme. Includes restaurant menu blocks and patterns.
 * Version: 2.4.22
 * Requires at least: 6.0
 * Author: Five Star Plugins
 * Author URI: https://www.fivestarplugins.com/
 * Text Domain: food-and-drink-menu
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

class fdmFoodAndDrinkMenu {

	// pointers to classes used by the plugin, where needed
	public $cart;
	public $cpts;
	public $orders;
	public $permissions;
	public $settings;

	// overall styling options
	public $styles = array();
	public $prostyles = array();

	// settings
	public $show_sidebar;

	// schema data to output
	public $schema_menu_data = array();

	/**
	 * Initialize the plugin and register hooks
	 */
	public function __construct() {
		// Common strings
		define( 'FDM_DOMAIN', 'food-and-drink-menu' );
		define( 'FDM_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'FDM_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'FDM_PLUGIN_FNAME', plugin_basename( __FILE__ ) );
		define( 'FDM_UPGRADE_URL', 'https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1' );
		define( 'FDM_TEMPLATE_DIR', 'fdm-templates' );
		define( 'FDM_VERSION', '2.4.22' );
		define( 'FDM_MENU_POST_TYPE', 'fdm-menu' );
		define( 'FDM_MENUITEM_POST_TYPE', 'fdm-menu-item' );
		define( 'FDM_ORDER_POST_TYPE', 'fdm-order' );

		// Load helper class
		require_once( FDM_PLUGIN_DIR . '/includes/class-helper.php' );

		// Load About Us class
		require_once( FDM_PLUGIN_DIR . '/includes/class-about-us.php' );
		new fdmAboutUs();

		// Load Admin Orders class
		//require_once( FDM_PLUGIN_DIR . '/includes/class-admin-orders.php' );
		//new fdmAdminOrders();

		// Load permissions and handle combination
		require_once( FDM_PLUGIN_DIR . '/includes/class-permissions.php' );
		$this->permissions = new fdmPermissions();
		$this->handle_combination();

		// Load template functions
		require_once( FDM_PLUGIN_DIR . '/includes/template-functions.php' );

		// Call when plugin is initialized on every page load
		add_action( 'plugins_loaded', array( $this, 'plugin_loaded_action_hook' ) );
		add_action( 'init', array( $this, 'load_config' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Load custom post types
		require_once( FDM_PLUGIN_DIR . '/includes/class-custom-post-types.php' );
		$this->cpts = new fdmCustomPostTypes();

		// Load deactivation survey
		require_once( FDM_PLUGIN_DIR . '/includes/class-deactivation-survey.php' );
		new fdmDeactivationSurvey();

		// Load review ask
		require_once( FDM_PLUGIN_DIR . '/includes/class-review-ask.php' );
		new fdmReviewAsk();

		// Load walk-through
		require_once( FDM_PLUGIN_DIR . '/includes/class-installation-walkthrough.php' );
		new fdmInstallationWalkthrough();

		// Load plugin dashboard
		require_once( FDM_PLUGIN_DIR . '/includes/class-dashboard.php' );
		new fdmDashboard();

		// Load settings
		require_once( FDM_PLUGIN_DIR . '/includes/class-settings.php' );
		$this->settings = new fdmSettings();

		// Load import/export
		require_once( FDM_PLUGIN_DIR . '/includes/class-export.php' );
		require_once( FDM_PLUGIN_DIR . '/includes/class-import.php' );
		new fdmExport();
		new fdmImport();

		// Load ordering if necessary
		if ( $this->settings->get_setting( 'fdm-enable-ordering' ) ) {
			// Add custom roles and capabilities
			add_action( 'init', array( $this, 'add_roles' ) );

			require_once( FDM_PLUGIN_DIR . '/includes/class-cart-item.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/class-order-item.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/class-order-notification.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/class-order-notification-email.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/class-order-notification-sms.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/class-cart-manager.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/class-order-manager.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/class-order-payments.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/class-admin-orders.php' );
			require_once( FDM_PLUGIN_DIR . '/includes/WP_List_Table.OrdersTable.class.php' );

			new fdmAdminOrders();

			$this->cart = new fdmCartManager();
			$this->orders = new fdmOrderManager();
			new fdmOrderPayments();
		}

		// Load AJAX handlers
		require_once( FDM_PLUGIN_DIR . '/includes/class-ajax.php' );
		new fdmAjax();

		// Load compatibility sections
		require_once( FDM_PLUGIN_DIR . '/includes/class-backwards-compatibility.php' );
		new fdmBackwardsCompatibility();

		// Load integrations with other plugins
		require_once( FDM_PLUGIN_DIR . '/includes/integrations/business-profile.php' );
		require_once( FDM_PLUGIN_DIR . '/includes/integrations/wordpress-seo.php' );

		// Call when the plugin is activated
		register_activation_hook( __FILE__, array( $this, 'rewrite_flush' ) );
		register_activation_hook( __FILE__, array( $this, 'run_walkthrough' ) );
		register_activation_hook( __FILE__, array( $this, 'set_update_transient_on_plugin_activation' ) );

		// Load admin assets
		add_action( 'admin_notices', array($this, 'display_header_area'), 99);
		// add_action( 'admin_print_scripts-post-new.php', array( $this, 'enqueue_admin_assets' ) );
		// add_action( 'admin_print_scripts-post.php', array( $this, 'enqueue_admin_assets' ) );
		// add_action( 'admin_print_scripts-edit.php', array( $this, 'enqueue_admin_assets' ) );
		// add_action( 'admin_print_scripts-edit-tags.php', array( $this, 'enqueue_admin_assets' ) );
		// add_action( 'admin_print_scripts-term.php', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_admin_assets' ) );

		// Handle the helper notice
		add_action( 'admin_notices', array( $this, 'maybe_display_helper_notice' ) );
		add_action( 'wp_ajax_fdm_hide_helper_notice', array( $this, 'hide_helper_notice' ) );

		// New Plugin Notice
		add_action( 'admin_notices', array( $this, 'maybe_display_new_plugin_notice' ) );
		add_action( 'wp_ajax_fdm_hide_new_plugin_notice', array( $this, 'hide_new_plugin_notice' ) );

		// Load Gutenberg blocks
		require_once( FDM_PLUGIN_DIR . '/includes/class-blocks.php' );
		if ( function_exists( 'register_block_type_from_metadata' ) ) { new fdmBlocks(); }

		// Load Gutenberg block patterns
		require_once( FDM_PLUGIN_DIR . '/includes/class-patterns.php' );
		if ( function_exists( 'register_block_pattern' ) ) { new fdmPatterns(); }

		// Register the widget
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		// Order the menu items by menu order in the admin interface
		add_filter( 'pre_get_posts', array( $this, 'admin_order_posts' ) );

		// Append menu and menu item content to a post's $content variable
		add_filter( 'the_content', array( $this, 'append_to_content' ) );

		// Add in structured data about the restaurant menu, if any is set
		add_action( 'wp_footer', array( $this, 'output_ld_json_content' ) );

		// Add links to plugin listing
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2);

		// Backwards compatibility for new taxonomy term splitting
		// in 4.2
		// https://make.wordpress.org/core/2015/02/16/taxonomy-term-splitting-in-4-2-a-developer-guide/
		add_action( 'split_shared_term', array( $this, 'compat_split_shared_term' ), 10, 4 );

		add_action( 'upgrader_process_complete', array( $this, 'set_plugin_update_transient' ), 10, 2 );

		// Show/hide sidebar
		$this->show_sidebar = $this->settings->get_setting( 'fdm-sidebar' );
	}

	/**
	 * Flush the rewrite rules when this plugin is activated to update with
	 * custom post types
	 * @since 1.1
	 */
	public function rewrite_flush() {
		$this->cpts->load_cpts();
		flush_rewrite_rules();
	}

	/**
	 * Set a transient so that the walk-through gets run
	 * @since 2.0
	 */
	public function run_walkthrough() {
		set_transient('fdm-getting-started', true, 30);
	} 

	/**
	 * Allow third-party plugins to interact with the plugin, if necessary
	 * @since 2.3.0
	 */
	public function plugin_loaded_action_hook() {

		do_action( 'fdm_initialized' );
	}

	/**
	 * Load the plugin's configuration settings and default content
	 * @since 1.1
	 */
	public function load_config() {
		global $fdm_controller;

		// Add a thumbnail size for menu items
		if ( !$fdm_config_thumb_width = $fdm_controller->settings->get_setting('fdm-item-thumb-width') ) {
			$fdm_config_thumb_width = 600;
		}
		if ( !$fdm_config_thumb_height = $fdm_controller->settings->get_setting('fdm-item-thumb-height') ) {
			$fdm_config_thumb_height = 600;
		}
		add_image_size( 'fdm-item-thumb', intval( $fdm_config_thumb_width ), intval( $fdm_config_thumb_height ), true );

		// Define supported styles
		fdm_load_view_files();
		$this->styles = array(
			'classic' => new fdmStyle(
				array(
					// This style refers to prostyles ahead, internally in View.class.php
					'id'	=> 'prostyles',
					'label'	=> __( 'Apply Menu Styles', 'food-and-drink-menu' ),
					'css'	=> array( ),
					'js'	=> array( )
				)
			),
			'base' => new fdmStyle(
				array(
					'id'	=> 'base',
					'label'	=> __( 'Base Formatting Only', 'food-and-drink-menu' ),
					'css'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/css/base.css'
					),
					'js'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/js/base.js'
					)
				)
			),
			'none' => new fdmStyle(
				array(
					'id'	=> 'none',
					'label'	=> __( 'Don\'t Load any CSS Styles', 'food-and-drink-menu' ),
					'css'	=> array( ),
					'js'	=> array( )
				)
			),
		);
		$this->styles = apply_filters( 'fdm_styles', $this->styles );

		$this->prostyles = array(
			'classic' => new fdmStyle(
				array(
					'id'	=> 'classic',
					'label'	=> __( 'Classic Style', 'food-and-drink-menu' ),
					'css'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/css/base.css',
						'classic' => FDM_PLUGIN_URL . '/assets/css/classic.css'
					),
					'js'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/js/base.js'
					)
				)
			),
			'refined' => new fdmStyle(
				array(
					'id'	=> 'refined',
					'label'	=> __( 'Refined', 'food-and-drink-menu' ),
					'css'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/css/base.css',
						'refined' => FDM_PLUGIN_URL . '/assets/css/refined.css'
					),
					'js'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/js/base.js'
					)
				)
			),
			'luxe' => new fdmStyle(
				array(
					'id'	=> 'luxe',
					'label'	=> __( 'Luxe', 'food-and-drink-menu' ),
					'css'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/css/base.css',
						'luxe' => FDM_PLUGIN_URL . '/assets/css/luxe.css'
					),
					'js'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/js/base.js',
						'luxe' => FDM_PLUGIN_URL . '/assets/js/luxe.js'
					)
				)
			),
			'image' => new fdmStyle(
				array(
					'id'	=> 'image',
					'label'	=> __( 'Image Style', 'food-and-drink-menu' ),
					'css'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/css/base.css',
						'image' => FDM_PLUGIN_URL . '/assets/css/image-style.css'
					),
					'js'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/js/base.js',
						'image' => FDM_PLUGIN_URL . '/assets/js/image-style.js'
					)
				)
			),
			'ordering' => new fdmStyle(
				array(
					'id'	=> 'ordering',
					'label'	=> __( 'Ordering', 'food-and-drink-menu' ),
					'css'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/css/base.css',
						'ordering' => FDM_PLUGIN_URL . '/assets/css/ordering-style.css'
					),
					'js'	=> array(
						'base' => FDM_PLUGIN_URL . '/assets/js/base.js'
					)
				)
			),
		);
		$this->prostyles = apply_filters( 'fdm_styles', $this->prostyles );

	}

	/**
	 * Load the plugin textdomain for localistion
	 * @since 1.1
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'food-and-drink-menu', false, plugin_basename( dirname( __FILE__ ) ) . "/languages/" );
	}

	/**
	 * Register the widgets
	 * @since 1.1
	 */
	public function register_widgets() {
		require_once( FDM_PLUGIN_DIR . '/widgets/WidgetMenu.class.php' );
		register_widget( 'fdmWidgetMenu' );
		require_once( FDM_PLUGIN_DIR . '/widgets/WidgetMenuItem.class.php' );
		register_widget( 'fdmWidgetMenuItem' );
	}

	/**
	 * Run the backwards compatibility function when the plugin is reactivated, 
	 * to ensure settings are in the correct format
	 * @since 2.4.1
	 */
	public function set_update_transient_on_plugin_activation() {

		set_transient( 'fdm-plugin-updated', true, 30 );

		wp_remote_get( site_url() );
	}

	/**
	 * Run the backwards compatibility function when the plugin is updated
	 * to ensure settings are in the correct format
	 *
	 * @since  2.4.1
	 * @return void
	 */
	public function set_plugin_update_transient( $upgrader, $options ) {

		if ( empty( $options['action'] ) or $options['action'] != 'update' ) { return; }

		if ( empty( $options['type'] ) or $options['type'] != 'plugin' ) { return; }

		foreach ( $options['plugins'] as $plugin ) {

			if ( $plugin == FDM_PLUGIN_FNAME ) { 

				set_transient( 'fdm-plugin-updated', true, 30 );

				wp_remote_get( site_url() );
			}
		}
	}

	/**
	 * Print the menu on menu post type pages
	 * @since 1.1
	 */
	function append_to_content( $content ) {
		global $post;

		if ( !is_main_query() || !in_the_loop() || ( FDM_MENU_POST_TYPE !== $post->post_type && FDM_MENUITEM_POST_TYPE !== $post->post_type ) ) {
			return $content;
		}

		// We must disable this filter while we're rendering the menu in order to
		// prevent it from falling into a recursive loop with each menu item's
		// content.
		remove_action( 'the_content', array( $this, 'append_to_content' ) );

		fdm_load_view_files();

		$args = array(
			'id'	=> $post->ID,
			'show_content'	=> true
		);
		if ( FDM_MENUITEM_POST_TYPE == $post->post_type ) {
			$args['singular'] = true;
		}
		$args = apply_filters( 'fdm_menu_args', $args );

		// Initialize and render the view
		if ( FDM_MENU_POST_TYPE == $post->post_type ) {
			$menu = new fdmViewMenu( $args );
		} else {
			$menu = new fdmViewItem( $args );
		}
		$content = $menu->render();

		// Restore this filter
		add_action( 'the_content', array( $this, 'append_to_content' ) );

		return $content;

	}

	/**
	 * Output any Menu schema data, if enabled
	 * @since 1.1
	 */
	public function output_ld_json_content() {

		if ( empty( $this->schema_menu_data ) ) { return; }

		if ( $this->settings->get_setting( 'fdm-disable-microdata' ) ) { return; }

		$ld_json_ouptut = apply_filters( 'fdm_ld_json_output', $this->schema_menu_data );

		echo '<script type="application/ld+json" class="fsp-fdm-ld-json-data">';
		echo wp_json_encode( $ld_json_ouptut );
		echo '</script>';
	}

	/**
	 * Displays the header menu
	 * @since 2.0
	 */
	public function display_header_area() {
		global $fdm_controller, $post;

		$screen = get_current_screen();

		if ( ( !isset( $_GET['post_type'] ) or ( $_GET['post_type'] != FDM_MENU_POST_TYPE and $_GET['post_type'] != FDM_MENUITEM_POST_TYPE ) ) and 
			( !is_object( $post ) or ( $post->post_type != FDM_MENU_POST_TYPE and $post->post_type != FDM_MENUITEM_POST_TYPE ) ) and $screen->id != 'admin_page_fdm-add-edit-order' ) { return; }

		if ( ! $fdm_controller->permissions->check_permission( 'styling' ) || get_option("FDM_Trial_Happening") == "Yes" ) {
			?>
			<div class="fdm-dashboard-new-upgrade-banner">
				<div class="fdm-dashboard-banner-icon"></div>
				<div class="fdm-dashboard-banner-buttons">
					<a class="fdm-dashboard-new-upgrade-button" href="https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1&utm_source=fdm_admin&utm_content=banner" target="_blank">UPGRADE NOW</a>
				</div>
				<div class="fdm-dashboard-banner-text">
					<div class="fdm-dashboard-banner-title">
						GET FULL ACCESS WITH OUR PREMIUM VERSION
					</div>
					<div class="fdm-dashboard-banner-brief">
						New layouts, custom fields, filtering and more!
					</div>
				</div>
			</div>
			<?php
		}

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( get_option( 'fdm-pro-was-active' ) > time() - 7*24*3600 ) {
			echo "<div class='fdm-deactivate-pro'>";
			echo "<p>We've combined the code base for the free and pro versions into one plugin file for easier management.</p>";
			echo "<p>You still have access to the premium features you purchased, and you can read more about why we've combined them <a href='http://www.fivestarplugins.com/2019/10/02/five-star-restaurant-menu-adding-options-simplifying-our-codebase/'>on our blog</a></p>";
			echo "</div>";
		}
		
		$screen = get_current_screen();
		$screenID = $screen->id;
		?>
		<div class="fdm-admin-header-menu">
			<h2 class="nav-tab-wrapper">
			<a id="fdm-dash-mobile-menu-open" href="#" class="menu-tab nav-tab"><?php _e("MENU", 'food-and-drink-menu'); ?><span id="fdm-dash-mobile-menu-down-caret">&nbsp;&nbsp;&#9660;</span><span id="fdm-dash-mobile-menu-up-caret">&nbsp;&nbsp;&#9650;</span></a>
			<a id="dashboard-menu" href='edit.php?post_type=fdm-menu&page=fdm-dashboard' class="menu-tab nav-tab <?php if ($screenID == 'fdm-menu_page_fdm-dashboard') {echo 'nav-tab-active';}?>"><?php _e("Dashboard", 'food-and-drink-menu'); ?></a>
			<?php if ($fdm_controller->settings->get_setting( 'fdm-enable-ordering' ) ) { ?><a id="orders-menu" href='edit.php?post_type=fdm-menu&page=fdm-orders' class="menu-tab nav-tab <?php if (isset($_GET['page']) and $_GET['page'] == 'fdm-orders') {echo 'nav-tab-active';}?>"><?php _e("Orders", 'food-and-drink-menu'); ?></a><?php } ?>
			<a id="menus-menu" href='edit.php?post_type=fdm-menu' class="menu-tab nav-tab <?php if ($screenID == 'fdm-menu' || $screenID == 'edit-fdm-menu') {echo 'nav-tab-active';}?>"><?php _e("Menus", 'food-and-drink-menu'); ?></a>
			<a id="menu-items-menu" href='edit.php?post_type=fdm-menu-item' class="menu-tab nav-tab <?php if ($screenID == 'fdm-menu-item' || $screenID == 'edit-fdm-menu-item') {echo 'nav-tab-active';}?>"><?php _e("Menu Items", 'food-and-drink-menu'); ?></a>
			<a id="sections-menu" href='edit-tags.php?taxonomy=fdm-menu-section&post_type=fdm-menu-item' class="menu-tab nav-tab <?php if ($screenID == 'fdm-menu-section' || $screenID == 'edit-fdm-menu-section') {echo 'nav-tab-active';}?>"><?php _e("Sections", 'food-and-drink-menu'); ?></a>
			<?php if ( $fdm_controller->permissions->check_permission( 'flags' ) and empty( $fdm_controller->settings->get_setting( 'fdm-disable-menu-item-flags' ) ) ) { ?><a id="flags-menu" href='edit-tags.php?taxonomy=fdm-menu-item-flag&post_type=fdm-menu-item' class="menu-tab nav-tab <?php if ($screenID == 'edit-fdm-menu-item-flag') {echo 'nav-tab-active';}?>"><?php _e("Item Icons", 'food-and-drink-menu'); ?></a><?php } ?>
			<a id="export-menu" href='edit.php?post_type=fdm-menu&page=fdm-export' class="menu-tab nav-tab <?php if (isset($_GET['page']) and $_GET['page'] == 'fdm-export') {echo 'nav-tab-active';}?>"><?php _e("Export", 'food-and-drink-menu'); ?></a>
			<a id="import-menu" href='edit.php?post_type=fdm-menu&page=fdm-import' class="menu-tab nav-tab <?php if (isset($_GET['page']) and $_GET['page'] == 'fdm-import') {echo 'nav-tab-active';}?>"><?php _e("Import", 'food-and-drink-menu'); ?></a>
			<a id="options-menu" href='edit.php?post_type=fdm-menu&page=food-and-drink-menu-settings' class="menu-tab nav-tab <?php if ($screenID == 'fdm-menu_page_food-and-drink-menu-settings') {echo 'nav-tab-active';}?>"><?php _e("Settings", 'food-and-drink-menu'); ?></a>
			</h2>
		</div>
		<?php
	}

	/**
	 * Enqueue the admin-only CSS and Javascript
	 * @since 1.0
	 */
	public function enqueue_admin_assets() {
		global $post_type;
		global $fdm_controller;

		$screen = get_current_screen();

		wp_enqueue_script( 'fdm-helper-notice', FDM_PLUGIN_URL . '/assets/js/helper-install-notice.js', array( 'jquery' ), FDM_VERSION, true );
		wp_localize_script(
			'fdm-helper-notice',
			'fdm_helper_notice',
			array( 'nonce' => wp_create_nonce( 'fdm-helper-notice' ) )
		);

		wp_enqueue_style( 'fdm-helper-notice', FDM_PLUGIN_URL . '/assets/css/helper-install-notice.css', array(), FDM_VERSION );

		if ( $post_type != FDM_MENU_POST_TYPE && $post_type != FDM_MENUITEM_POST_TYPE && ( ! isset($_GET['post_type'] ) || $_GET['post_type'] != FDM_MENU_POST_TYPE ) and $screen->id != 'admin_page_fdm-add-edit-order' ) { return; }

		$settings = get_option( 'food-and-drink-menu-settings' );
		$settings['nonce'] = wp_create_nonce( 'fdm-admin' );
		$settings['i18n'] = array(
			'undefined_error' => esc_html( 'An unexpected error occurred. Please reload the page and try again.', 'food-and-drink-menu' ),
		);

		wp_enqueue_style( 'fdm-admin', FDM_PLUGIN_URL . '/assets/css/admin.css', array(), FDM_VERSION );
		wp_enqueue_style( 'fdmp-base', FDM_PLUGIN_URL . '/assets/css/base.css', FDM_VERSION );
		wp_enqueue_script( 'fdm-admin', FDM_PLUGIN_URL . '/assets/js/admin.js', array( 'jquery' ), FDM_VERSION, true );
		wp_localize_script( 'fdm-admin', 'fdm_settings', $settings );

		add_action( 'admin_footer', array( $this, 'print_modals' ) );

		// Adds the front-end ordering JS to the admin edit order page, to allow adding items to orders by the admin
		if ( $screen->id != 'admin_page_fdm-add-edit-order' ) { return; }

		wp_enqueue_script( 'fdm-admin-ordering', FDM_PLUGIN_URL . '/assets/js/fdm-ordering-js.js', array( 'jquery' ), FDM_VERSION );

		$admin_ordering_data = array(
			'nonce'				=> wp_create_nonce( 'fdm-ordering' ),
			'price_text' 		=> __( 'Price', 'food-and-drink-menu' ),
			'additional_prices' => true,
		);

		wp_localize_script( 'fdm-admin-ordering', 'fdm_ordering_popup_data', $admin_ordering_data );

		$price_prefix = ( $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'before' ? $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) : '' );
		$price_suffix = ( $fdm_controller->settings->get_setting( 'fdm-currency-symbol-location' ) == 'after' ? $fdm_controller->settings->get_setting( 'fdm-currency-symbol' ) : '' );
		
		$fdm_ordering_data = array(
			'singular_text' => 'N/A',
			'plural_text' 	=> 'N/A',
			'price_prefix' 	=> $price_prefix,
			'price_suffix'	=> $price_suffix,
			'minimum_order' => 0,
			'tax_rate' 		=> $fdm_controller->settings->get_setting( 'ordering-tax-rate' ),
		);
		wp_localize_script( 'fdm-admin-ordering', 'fdm_ordering_data', $fdm_ordering_data );
	}

	/**
	 * Print modals used in the menu editing screens
	 *
	 * @since 1.5
	 */
	public function print_modals() {
		?>

		<div id="fdm-menu-section-modal" class="fdm-modal">
			<div class="fdm-modal-content">
				<div class="field">
					<label for="fdm-menu-section-modal-name">
						<?php esc_html_e( 'Section Name', 'food-and-drink-menu' ); ?>
					</label>
					<input type="text" id="fdm-menu-section-modal-name">
				</div>
				<p class="description">
					<?php
						printf(
							esc_html( 'Enter a unique name for this section when it appears in this menu. The name entered here will only be used for this menu. To change the name of the section in all menus, visit the %sMenu Sections%s list.', 'food-and-drink-menu' ),
							'<a href="' . esc_url( admin_url( '/edit-tags.php?taxonomy=fdm-menu-section&post_type=fdm-menu-item' ) ) . '">',
							'</a>'
						);
					?>
				</p>
				<div class="buttons">
					<a id="fdm-menu-section-modal-save" href="#" class="fdm-save button">
						<?php esc_html_e( 'Update Section Name', 'food-and-drink-menu' ); ?>
					</a>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Order the menu items by menu order in the admin interface
	 * @since 1.0
	 */
	public function admin_order_posts( $query ) {

		// Check that we're on the right screen
		if( ( is_admin() && $query->is_admin ) && $query->get( 'post_type' ) == 'fdm-menu-item' ) {

			// Don't override an existing orderby setting. This prevents other
			// orderby options from breaking.
			if ( !$query->get ( 'orderby' ) ) {
				$query->set( 'orderby', array( 'menu_order' => 'ASC', 'post_date' => 'DESC' ) );
			}
		}

		return $query;
	}

	/**
	 * Add links to the plugin listing on the installed plugins page
	 * @since 1.0
	 */
	public function plugin_action_links( $links, $plugin ) {
		global $fdm_controller;

		if ( $plugin == FDM_PLUGIN_FNAME ) {

			if ( ! $fdm_controller->permissions->check_permission( 'premium' ) ) {

				array_unshift( $links, '<a class="fdm-plugin-page-upgrade-link" href="https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1&utm_source=wp_admin_plugins_page" title="' . __( 'Try Premium', 'food-and-drink-menu' ) . '" target="_blank">' . __( 'Try Premium', 'food-and-drink-menu' ) . '</a>' );
			}

			$links['help'] = '<a href="http://doc.fivestarplugins.com/plugins/food-and-drink-menu?utm_source=Plugin&utm_medium=Plugin%20Help&utm_campaign=Food%20and%20Drink%20Menu" title="' . __( 'View the help documentation for Food and Drink Menu', 'food-and-drink-menu' ) . '">' . __( 'Help', 'food-and-drink-menu' ) . '</a>';
		}
		return $links;
	}


	/**
	 * Add the capability to edit order for Editors,
	 * Administrators and Super Admins
	 * @since 2.1.0
	 */
	public function add_roles() {

		$manage_orders_roles = apply_filters(
			'fdm_manage_orders_roles',
			array(
				'administrator',
				'editor',
			)
		);

		global $wp_roles;
		foreach ( $manage_orders_roles as $role ) {
			$wp_roles->add_cap( $role, 'manage_fdm_orders' );
		}
	}


	/**
	 * Update menu section term ids when shared terms are split
	 *
	 * Backwards compatibility for new taxonomy term splitting
	 * introduced in 4.2. Shared terms in different taxonomies
	 * were created in versions prior to 4.1 and will be
	 * automatically split in 4.2, with their term ids being
	 * updated. This function will update the term ids used to
	 * link menu sections to menus.
	 *
	 * https://make.wordpress.org/core/2015/02/16/taxonomy-term-splitting-in-4-2-a-developer-guide/
	 *
	 * @since 1.4.3
	 */
	public function compat_split_shared_term( $old_term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {

		if ( $taxonomy !== 'fdm-menu-section' ) {
			return;
		}

		$posts = new WP_Query( array(
			'post_type' => 'fdm-menu',
			'posts_per_page'	=> 1000,
		) );

		$cols = array( 'one', 'two' );
		while( $posts->have_posts() ) {
			$posts->the_post();

			foreach( $cols as $col ) {
				$updated = false;
				$menu_sections = get_post_meta( get_the_ID(), 'fdm_menu_column_' . $col, true );

				if ( !empty( $menu_sections ) ) {
					$term_ids = explode( ',', $menu_sections );
					foreach( $term_ids as $key => $term_id ) {
						if ( $term_id == $old_term_id ) {
							$term_ids[ $key ] = $new_term_id;
							$updated = true;
						}
					}
				}

				if ( $updated ) {
					update_post_meta( get_the_ID(), 'fdm_menu_column_' . $col, join( ',', $term_ids ) );
				}
			}
		}
	}

	public function handle_combination() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( is_plugin_active( "food-and-drink-menu-pro/food-and-drink-menu-pro.php" ) ) {
			update_option('fdm-pro-was-active', time());
			deactivate_plugins("food-and-drink-menu-pro/food-and-drink-menu-pro.php");
		}
	}

	public function maybe_display_helper_notice() {
		global $fdm_controller;
		
		if ( empty( $fdm_controller->permissions->check_permission( 'premium' ) ) ) { return; }
		
		if ( is_plugin_active( 'fsp-premium-helper/fsp-premium-helper.php' ) ) { return; }
		
		if ( get_transient( 'fsp-helper-notice-dismissed' ) ) { return; }
		
		?>
	
		<div class='notice notice-error is-dismissible fdm-helper-install-notice'>
				
			<div class='fdm-helper-install-notice-img'>
				<img src='<?php echo FDM_PLUGIN_URL . '/lib/simple-admin-pages/img/options-asset-exclamation.png' ; ?>' />
			</div>
	
			<div class='fdm-helper-install-notice-txt'>
				<?php _e( 'You\'re using the Five-Star Restaurant Menu premium version, but the premium helper plugin is not active.', 'food-and-drink-menu' ); ?>
				<br />
				<?php echo sprintf( __( 'Please re-activate the helper plugin, or <a target=\'_blank\' href=\'%s\'>download and install it</a> if the plugin is no longer installed to ensure continued access to the premium features of the plugin.', 'food-and-drink-menu' ), 'https://www.fivestarplugins.com/2021/12/23/requiring-premium-helper-plugin/' ); ?>
			</div>
	
			<div class='fdm-clear'></div>
	
		</div>
	
		<?php 
	}
	
	public function hide_helper_notice() {
	
		// Authenticate request
		if ( ! check_ajax_referer( 'fdm-helper-notice', 'nonce' ) or ! current_user_can( 'manage_options' ) ) {
				
			wp_send_json_error(
				array(
					'error' => 'loggedout',
					'msg' => sprintf( __( 'You have been logged out. Please %slogin again%s.', 'food-and-drink-menu' ), '<a href="' . wp_login_url( admin_url( 'admin.php?page=fdm-dashboard' ) ) . '">', '</a>' ),
				)
			);
		}
	
		set_transient( 'fsp-helper-notice-dismissed', true, 3600*24*7 );
	
		die();
	}

	public function maybe_display_new_plugin_notice() {

		$screen = get_current_screen();
	    if (!isset($screen->id) || strpos($screen->id, 'fdm-menu_page') === false) { return; }
	
		if ( get_transient( 'fdm-ait-iat-plugin-notice-dismissed' ) ) { return; }
	
		// October 17th, 2025
		if ( time() > 1760759940 ) { return; }
	
		?>
	
		<div class='notice notice-error is-dismissible ait-iat-new-plugin-notice'>
				
			<div class='fdm-new-plugin-notice-img'>
				<img src='<?php echo FDM_PLUGIN_URL . '/assets/img/ait-iat-plugin-icon.png' ; ?>' />
			</div>
	
			<div class='fdm-new-plugin-notice-txt'>
				<p><?php _e( 'Want to improve your search rankings? Try our new <strong>AI Image Alt Text</strong> plugin!', 'food-and-drink-menu' ); ?></p>
				<p><?php echo sprintf( __( 'As a thank you to our customers, for a limited time you can get a <strong>free pro license</strong>! Try the <a target=\'_blank\' href=\'%s\'>free version</a> today or use code <code>early_adopter_pro</code> to <a target=\'_blank\' href=\'%s\'>get your pro version license</a>!', 'food-and-drink-menu' ), admin_url( 'plugin-install.php?tab=plugin-information&plugin=ai-image-alt-text' ), 'https://www.wpaiplugins.dev/wordpress-image-alt-text-ai-plugin/' ); ?></p>
			</div>
	
			<div class='fdm-clear'></div>
	
		</div>
	
		<?php 
	}
	
	public function hide_new_plugin_notice() {
		global $fdm_controller;
	
		// Authenticate request
		if (
			! check_ajax_referer( 'fdm-admin', 'nonce' )
			||
			! current_user_can( 'manage_options' )
		) {
			wp_send_json_error(
				array(
					'error' => 'loggedout',
					'msg' => sprintf( __( 'You have been logged out. Please %slogin again%s.', 'food-and-drink-menu' ), '<a href="' . wp_login_url( admin_url( 'admin.php?page=fdm-dashboard' ) ) . '">', '</a>' ),
				)
			);
	
		}
	
		set_transient( 'fdm-ait-iat-plugin-notice-dismissed', true, 3600*24*7 );
	
		die();
	}

}

global $fdm_controller;
$fdm_controller = new fdmFoodAndDrinkMenu();