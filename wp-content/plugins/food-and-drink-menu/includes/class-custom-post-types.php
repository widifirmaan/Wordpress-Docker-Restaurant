<?php
/**
 * Class to handle all custom post type definitions for Food and Drink Menu
 */

if ( !defined( 'ABSPATH' ) )
	exit;

class fdmCustomPostTypes {

	/** @var string Nonce used for security */
	public $nonce = '';

	/**
	 * Array of menu item taxonomies
	 *
	 * @param array
	 * @since 1.5
	 */
	public $menu_item_taxonomies = array();

	public function __construct() {

		// Call when plugin is initialized on every page load
		add_action( 'admin_init', array( $this, 'create_nonce' ) );
		add_action( 'init', array( $this, 'load_cpts' ) );
		add_action( 'admin_menu', array( $this, 'load_cpt_admin_menu' ) );
		add_filter( 'fdm_menu_item_taxonomies', array( $this, 'fdmp_add_item_flag_taxonomy' ) );

		// Handle metaboxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_filter( 'fdm_save_meta', array( $this, 'fdmp_save_meta' ) );

		// Add columns and filters to the admin list of menu items
		add_filter( 'manage_fdm-menu-item_posts_columns', array( $this, 'menu_item_posts_columns' ) );
		add_filter( 'manage_edit-fdm-menu-item_sortable_columns', array( $this, 'menu_item_posts_sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'menu_item_posts_orderby' ) );
		add_action( 'manage_fdm-menu-item_posts_custom_column', array( $this, 'menu_item_posts_columns_content' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'menu_item_posts_filters' ) );
		add_filter( 'parse_query', array( $this, 'menu_item_posts_filter_query' ) );
		add_action( 'fdm_show_item_price', array( $this, 'fdmp_show_item_discounted_price' ) );
		add_filter( 'post_row_actions', array( $this, 'menu_item_row_actions' ), 10, 2 );

		// Add columns and filters to the admin list of menus
		add_filter( 'manage_fdm-menu_posts_columns', array( $this, 'menu_posts_columns' ) );
		add_action( 'manage_fdm-menu_posts_custom_column', array( $this, 'menu_posts_columns_content' ), 10, 2 );

		// Optionally add an image for each section
		add_action( 'fdm-menu-section_add_form_fields' , array($this, 'add_section_image'));
		add_action( 'fdm-menu-section_edit_form_fields' , array($this, 'edit_section_image'));
		add_action( 'create_fdm-menu-section', array($this, 'save_section_image'));
		add_action( 'edit_fdm-menu-section', array($this, 'save_section_image'));

		add_action( 'fdm-menu-item-flag_add_form_fields', array($this, 'fdmp_item_flag_new_icon_field' ), 10, 2 );
		add_action( 'fdm-menu-item-flag_edit_form_fields', array($this, 'fdmp_item_flag_edit_icon_field' ), 10, 2 );
		add_action( 'edited_fdm-menu-item-flag', array($this, 'fdmp_item_flag_save_icon_field' ), 10, 2 );
		add_action( 'create_fdm-menu-item-flag', array($this, 'fdmp_item_flag_save_icon_field' ), 10, 2 );

		// Process price changes from the menu item list table
		add_action( 'wp_ajax_nopriv_fdm-menu-item-price' , array( $this , 'ajax_nopriv' ) );
		add_action( 'wp_ajax_fdm-menu-item-price', array( $this, 'ajax_menu_item_price' ) );
		add_filter( 'fdm_ajax_menu_item_price', array( $this, 'fdmp_ajax_menu_item_price' ), 10, 3 );

		// Allow menus to opt for a page template if desired
		add_filter( 'theme_' . FDM_MENU_POST_TYPE . '_templates', array( $this, 'add_menu_templates' ), 10, 3 );
		add_filter( 'template_include', array( $this, 'load_menu_template' ), 99 );

		// Handle menu item duplication
		add_action( 'init' , array( $this , 'maybe_duplicate_item' ) );
	}

	/**
	 * Generate a nonce for secure saving of metadata
	 *
	 * @since 1.6.1
	 */
	public function create_nonce() {
		$this->nonce = wp_create_nonce( basename( __FILE__ ) );
	}

	/**
	 * Initialize custom post types
	 * @since 1.1
	 */
	public function load_cpts() {

		global $fdm_controller;

		// Define the menu taxonomies
		$menu_taxonomies = array();

		// Create filter so addons can modify the taxonomies
		$menu_taxonomies = apply_filters( 'fdm_menu_taxonomies', $menu_taxonomies );

		// Define the menu custom post type
		$args = array(
			'labels' => array(
				'name' => __( 'Menus', 'food-and-drink-menu' ),
				'singular_name' => __( 'Menu', 'food-and-drink-menu' ),
				'add_new' => __( 'Add Menu', 'food-and-drink-menu' ),
				'add_new_item' => __( 'Add New Menu', 'food-and-drink-menu' ),
				'edit' => __( 'Edit', 'food-and-drink-menu' ),
				'edit_item' => __( 'Edit Menu', 'food-and-drink-menu' ),
				'new_item' => __( 'New Menu', 'food-and-drink-menu' ),
				'view' => __( 'View', 'food-and-drink-menu' ),
				'view_item' => __( 'View Menu', 'food-and-drink-menu' ),
				'search_items' => __( 'Search Menus', 'food-and-drink-menu' ),
				'not_found' => __( 'No Menu found', 'food-and-drink-menu' ),
				'not_found_in_trash' => __( 'No Menu found in Trash', 'food-and-drink-menu' ),
				'parent' => __( 'Parent Menu', 'food-and-drink-menu' )
			),
			'menu_icon' => 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgdmlld0JveD0iMCAwIDMyIDMyIj4KPHBhdGggZmlsbD0icmdiYSgyNDAsMjQ1LDI1MCwwLjYpIiBkPSJNNyAwYy0zLjMxNCAwLTYgMy4xMzQtNiA3IDAgMy4zMSAxLjk2OSA2LjA4MyA0LjYxNiA2LjgxMmwtMC45OTMgMTYuMTkxYy0wLjA2NyAxLjA5OCAwLjc3OCAxLjk5NiAxLjg3OCAxLjk5NmgxYzEuMSAwIDEuOTQ1LTAuODk4IDEuODc4LTEuOTk2bC0wLjk5My0xNi4xOTFjMi42NDYtMC43MjkgNC42MTYtMy41MDIgNC42MTYtNi44MTIgMC0zLjg2Ni0yLjY4Ni03LTYtN3pNMjcuMTY3IDBsLTEuNjY3IDEwaC0xLjI1bC0wLjgzMy0xMGgtMC44MzNsLTAuODMzIDEwaC0xLjI1bC0xLjY2Ny0xMGgtMC44MzN2MTNjMCAwLjU1MiAwLjQ0OCAxIDEgMWgyLjYwNGwtMC45ODIgMTYuMDA0Yy0wLjA2NyAxLjA5OCAwLjc3OCAxLjk5NiAxLjg3OCAxLjk5NmgxYzEuMSAwIDEuOTQ1LTAuODk4IDEuODc4LTEuOTk2bC0wLjk4Mi0xNi4wMDRoMi42MDRjMC41NTIgMCAxLTAuNDQ4IDEtMXYtMTNoLTAuODMzeiI+PC9wYXRoPgo8L3N2Zz4=',
			'public' => true,
			'rewrite' => array( 'slug' => 'menu' ),
			'supports' => array(
				'title',
				'editor',
				'revisions',
			),
			'taxonomies' => array_keys( $menu_taxonomies ),
			'show_in_rest' => true
		);

		// Create filter so addons can modify the arguments
		$args = apply_filters( 'fdm_menu_args', $args );

		// Add an action so addons can hook in before the menu is registered
		do_action( 'fdm_menu_pre_register' );

		// Register the menu
		register_post_type( 'fdm-menu', $args );

		// Add an action so addons can hook in after the menu is registered
		do_action( 'fdm_menu_post_register' );

		// Create menu sections (desserts, entrees, etc)
		$this->menu_item_taxonomies['fdm-menu-section'] = array(
			'labels' 	=> array(
				'name' => __( 'Menu Sections', 'food-and-drink-menu' ),
				'singular_name' => __( 'Menu Section', 'food-and-drink-menu' ),
				'search_items' => __( 'Search Menu Sections', 'food-and-drink-menu' ),
				'all_items' => __( 'All Menu Sections', 'food-and-drink-menu' ),
				'parent_item' => __( 'Menu Section', 'food-and-drink-menu' ),
				'parent_item_colon' => __( 'Menu Section:', 'food-and-drink-menu' ),
				'edit_item' => __( 'Edit Menu Section', 'food-and-drink-menu' ),
				'update_item' => __( 'Update Menu Section', 'food-and-drink-menu' ),
				'add_new_item' => __( 'Add New Menu Section', 'food-and-drink-menu' ),
				'new_item_name' => __( 'Menu Section', 'food-and-drink-menu' ),
				'no_terms' => __( 'No menu sections', 'food-and-drink-menu' ),
				'items_list_navigation' => __( 'Menu sections list navigation', 'food-and-drink-menu' ),
				'items_list' => __( 'Menu sections list', 'food-and-drink-menu' ),
				'archives' => __( 'Menu Archives', 'food-and-drink-menu' ),
				'insert_into_item' => __( 'Insert into menu', 'food-and-drink-menu' ),
				'uploaded_to_this_item' => __( 'Uploaded to this menu', 'food-and-drink-menu' ),
				'filter_items_list' => __( 'Filter menu list', 'food-and-drink-menu' ),
				'item_list_navigation' => __( 'Menu list navigation', 'food-and-drink-menu' ),
				'items_list' => __( 'Menu list', 'food-and-drink-menu' ),
			),
			'show_in_rest' => true,
		);

		// Create filter so addons can modify the taxonomies
		$this->menu_item_taxonomies = apply_filters( 'fdm_menu_item_taxonomies', $this->menu_item_taxonomies );

		// Register taxonomies
		foreach( $this->menu_item_taxonomies as $id => $taxonomy ) {
			register_taxonomy(
				$id,
				'',
				$taxonomy
			);
		}

		// Define the Menu Item custom post type
		$args = array(
			'labels' => array(
				'name' => __( 'Menu Items', 'food-and-drink-menu' ),
				'singular_name' => __( 'Menu Item', 'food-and-drink-menu' ),
				'add_new' => __( 'Add Menu Item', 'food-and-drink-menu' ),
				'add_new_item' => __( 'Add New Menu Item', 'food-and-drink-menu' ),
				'edit' => __( 'Edit', 'food-and-drink-menu' ),
				'edit_item' => __( 'Edit Menu Item', 'food-and-drink-menu' ),
				'new_item' => __( 'New Menu Item', 'food-and-drink-menu' ),
				'view' => __( 'View', 'food-and-drink-menu' ),
				'view_item' => __( 'View Menu Item', 'food-and-drink-menu' ),
				'search_items' => __( 'Search Menu Items', 'food-and-drink-menu' ),
				'not_found' => __( 'No Menu Item found', 'food-and-drink-menu' ),
				'not_found_in_trash' => __( 'No Menu Item found in Trash', 'food-and-drink-menu' ),
				'parent' => __( 'Parent Menu Item', 'food-and-drink-menu' ),
				'featured_image' => __( 'Item Photo', 'food-and-drink-menu' ),
				'set_featured_image' => __( 'Set item photo', 'food-and-drink-menu' ),
				'remove_featured_image' => __( 'Remove item photo', 'food-and-drink-menu' ),
				'use_featured_image' => __( 'Use as item photo', 'food-and-drink-menu' ),
				'archives' => __( 'Menu Item Archives', 'food-and-drink-menu' ),
				'insert_into_item' => __( 'Insert into menu item', 'food-and-drink-menu' ),
				'uploaded_to_this_item' => __( 'Uploaded to this menu item', 'food-and-drink-menu' ),
				'filter_items_list' => __( 'Filter menu items list', 'food-and-drink-menu' ),
				'item_list_navigation' => __( 'Menu items list navigation', 'food-and-drink-menu' ),
				'items_list' => __( 'Menu items list', 'food-and-drink-menu' ),
			),
			'menu_position' => 5,
			'show_in_menu' => 'edit.php?post_type=' . FDM_MENU_POST_TYPE,
			'public' => true,
			'rewrite' => array( 'slug' => 'menu-item' ),
			'supports' => array(
				'title',
				'editor',
				'thumbnail',
				'revisions',
				'page-attributes'
			),
			'taxonomies' => array_keys( $this->menu_item_taxonomies ),
			'show_in_rest' => true
		);

		// Create filter so addons can modify the arguments
		$args = apply_filters( 'fdm_menu_item_args', $args );

		// Add an action so addons can hook in before the menu is registered
		do_action( 'fdm_menu_item_pre_register' );

		// Register the menu item post type
		register_post_type( 'fdm-menu-item', $args );

		// Add an action so addons can hook in after the menu is registered
		do_action( 'fdm_menu_item_post_register' );

		if ( $fdm_controller->settings->get_setting( 'fdm-enable-ordering' ) ) {
			// Define the field custom post type
			$args = array(
				'labels' => array(
					'name'               => __( 'Order',                    'food-and-drink-menu' ),
					'singular_name'      => __( 'Order',                    'food-and-drink-menu' ),
					'menu_name'          => __( 'Orders',                   'food-and-drink-menu' ),
					'name_admin_bar'     => __( 'Orders',                   'food-and-drink-menu' ),
					'add_new'            => __( 'Add Order',                'food-and-drink-menu' ),
					'add_new_item'       => __( 'Add New Order',            'food-and-drink-menu' ),
					'edit_item'          => __( 'Edit Order',               'food-and-drink-menu' ),
					'new_item'           => __( 'New Order',                'food-and-drink-menu' ),
					'view_item'          => __( 'View Order',               'food-and-drink-menu' ),
					'search_items'       => __( 'Search Orders',            'food-and-drink-menu' ),
					'not_found'          => __( 'No order found',           'food-and-drink-menu' ),
					'not_found_in_trash' => __( 'No orders found in trash', 'food-and-drink-menu' ),
					'all_items'          => __( 'All Orders',               'food-and-drink-menu' ),
				),
				'public' => false
			);
	
			$args = apply_filters( 'fdm_order_args', $args );
	
			register_post_type( FDM_ORDER_POST_TYPE, $args );

			$order_statuses = fdm_get_order_statuses();

			foreach ( $order_statuses as $status => $args ) {
				register_post_status( $status, $args );
			}
		}

	}

	/**
	 * Add submenu items to the menu
	 *
	 * @since 1.5
	 */
	public function load_cpt_admin_menu() {

		// Remove the Add Menu item
		remove_submenu_page(
			'edit.php?post_type=' . FDM_MENU_POST_TYPE,
			'post-new.php?post_type=' . FDM_MENU_POST_TYPE
		);

		// Add any menu item taxonomies
		foreach( $this->menu_item_taxonomies as $id => $taxonomy ) {
			add_submenu_page(
				'edit.php?post_type=' . FDM_MENU_POST_TYPE,
				$taxonomy['labels']['name'],
				$taxonomy['labels']['name'],
				isset( $taxonomy['capabilities'] ) ? $taxonomy['capabilities']['edit_terms'] : 'edit_posts',
				'edit-tags.php?taxonomy=' . $id . '&post_type=' . FDM_MENUITEM_POST_TYPE
			);
		}
	}

	/**
	 * Add metaboxes to specify custom post type data
	 * @since 1.0
	 */
	public function add_meta_boxes() {
		global $fdm_controller;

		$meta_boxes = array(

			// Add a menu organizer
			'fdm_menu_layout' => array (
				'id'		=>	'fdm_menu_layout',
				'title'		=> esc_html__( 'Menu Layout', 'food-and-drink-menu' ),
				'callback'	=> array( $this, 'show_menu_organizer' ),
				'post_type'	=> 'fdm-menu',
				'context'	=> 'normal',
				'priority'	=> 'core'
			),

			// Add a menu footer WYSIWYG editor
			'fdm_menu_footer' => array (
				'id'		=>	'fdm_menu_footer',
				'title'		=> esc_html__( 'Menu Footer', 'food-and-drink-menu' ),
				'callback'	=> array( $this, 'show_menu_footer' ),
				'post_type'	=> 'fdm-menu',
				'context'	=> 'normal',
				'priority'	=> 'default'
			),

			// Add custom fields for menu items
			'fdm_menu_item_custom_fields' => array (
				'id'		=>	'fdm_menu_item_custom_fields',
				'title'		=> esc_html__( 'Custom Fields', 'food-and-drink-menu' ),
				'callback'	=> array( $this, 'show_menu_item_custom_fields' ),
				'post_type'	=> 'fdm-menu-item',
				'context'	=> 'normal',
				'priority'	=> 'core'
			),
		);

		// Add ordering options if enabled
		if ( $fdm_controller->settings->get_setting( 'fdm-enable-ordering-options' ) and $fdm_controller->settings->get_setting( 'fdm-enable-ordering' ) ) { 
			$meta_boxes['fdm_menu_item_related_items'] = array (
				'id'		=> 'fdm_menu_item_order_options',
				'title'		=> esc_html__( 'Ordering Options', 'food-and-drink-menu' ),
				'callback'	=> array( $this, 'show_menu_item_ordering_options' ),
				'post_type'	=> 'fdm-menu-item',
				'context'	=> 'normal',
				'priority'	=> 'core'
			);
		}

		// Add menu item price metabox
		if ( ! $fdm_controller->settings->get_setting( 'fdm-disable-price' ) ) {
			$meta_boxes['fdm_menu_item_price'] = array (
				'id'		=>	'fdm_item_price',
				'title'		=> esc_html__( 'Price', 'food-and-drink-menu' ),
				'callback'	=> array( $this, 'show_item_price' ),
				'post_type'	=> 'fdm-menu-item',
				'context'	=> 'side',
				'priority'	=> 'default'
			);
		}

		// Add related items area if manual related items selected
		if ( $fdm_controller->settings->get_setting('fdm-related-items') == 'manual') { 
			$meta_boxes['fdm_menu_item_related_items'] = array (
				'id'		=> 'fdm_menu_item_related_items',
				'title'		=> esc_html__( 'Related Items', 'food-and-drink-menu' ),
				'callback'	=> array( $this, 'show_menu_item_related_items' ),
				'post_type'	=> 'fdm-menu-item',
				'context'	=> 'normal',
				'priority'	=> 'core'
			);
		}

		// Add a page template metabox for WP versions before 4.7 when custom
		// post type templates were introduced
		if ( version_compare( get_bloginfo( 'version' ), '4.7-beta-1', '<' ) ) {
			$meta_boxes['fdm_menu_template'] = array(
				'id' => 'fdm_menu_template',
				'title' => esc_html__( 'Page Template', 'food-and-drink-menu' ),
				'callback' => array( $this, 'show_page_template' ),
				'post_type' => 'fdm-menu',
				'context' => 'side',
				'priority' => 'default',
			);
		}

		$specials_permission = $fdm_controller->permissions->check_permission('specials');
		if ( ! $fdm_controller->settings->get_setting('fdm-disable-specials') and $specials_permission ) {
			$meta_boxes['fdm_menu_item_specials'] = array(
				'id'		=>	'fdm_item_special',
				'title'		=> __( 'Specials', 'food-and-drink-menu-pro' ),
				'callback'	=> array( $this, 'fdmp_show_item_special' ),
				'post_type'	=> 'fdm-menu-item',
				'context'	=> 'side',
				'priority'	=> 'default'
			);
		}

		$sources_permission = $fdm_controller->permissions->check_permission('sources');
		if ( ! $fdm_controller->settings->get_setting('fdm-disable-src') and $sources_permission ) {
			$meta_boxes['fdm_menu_item_source'] = array(
				'id'		=>	'fdm_item_source',
				'title'		=> __( 'Source', 'food-and-drink-menu-pro' ),
				'callback'	=> array( $this, 'fdmp_show_item_source' ),
				'post_type'	=> 'fdm-menu-item',
				'context'	=> 'normal',
				'priority'	=> 'default'
			);
		}

		// Create filter so addons can modify the metaboxes
		$meta_boxes = apply_filters( 'fdm_meta_boxes', $meta_boxes );

		// Create the metaboxes
		foreach ( $meta_boxes as $meta_box ) {
			add_meta_box(
				$meta_box['id'],
				$meta_box['title'],
				$meta_box['callback'],
				$meta_box['post_type'],
				$meta_box['context'],
				$meta_box['priority']
			);
		}
	}


	/**
	 * Print the Menu Item price metabox HTML
	 *
	 * @param WP_Post|null $singular_post Only appears on the menu item editing screen
	 * @since 1.0
	 */
	public function show_item_price( $singular_post = null ) {

		// Retrieve values for this if it exists
		global $post;
		$prices = (array) get_post_meta( $post->ID, 'fdm_item_price' );

		// Always add at least one price input field
		if ( empty( $prices ) ) {
			$prices = array( '' );
		}

		// If we don't have a $post, this is being printed in the quick edit
		// panel. Input fields can't have name attributes because it messes with
		// the menu item filter forms.
		// @see https://github.com/NateWr/food-and-drink-menu/issues/35
		$input_attrs = '';
		if ( !is_null( $singular_post ) && is_a( $singular_post, 'WP_POST' ) ) {
			$input_attrs = ' name="fdm_item_price[]"';
		} else {
			$input_attrs = ' data-name="fdm_item_price"';
		}

		?>

			<input type="hidden" name="fdm_nonce" value="<?php echo $this->nonce; ?>">
			<div class="fdm-input-controls fdm-input-side-panel" data-menu-item-id="<?php echo $post->ID; ?>">
				<div class="fdm-input-prices fdm-input-group">
					<?php foreach( $prices as $key => $price ) : ?>
						<div class="fdm-input-control">
							<label for="fdm_item_price" class="screen-reader-text">
								<?php echo __( 'Price', 'food-and-drink-menu' ); ?>
							</label>
							<input type="text"<?php echo $input_attrs; ?> value="<?php echo esc_attr( $price ); ?>">
							<a href="#" class="fdm-input-delete">
								<?php esc_html_e( 'Remove this price' ); ?>
							</a>
						</div>
					<?php endforeach; ?>
					<div class="fdm-input-group-add">
						<a href="#" class="fdm-price-add">
							<?php esc_html_e( 'Add Price' ); ?>
						</a>
					</div>
				</div>

				<?php do_action( 'fdm_show_item_price' ); ?>

			</div>

		<?php
	}

	/**
	 * Print the discounted price input for the Menu Item price metabox
	 *
	 * @since 2.0
	 */
	public function fdmp_show_item_discounted_price() {
		global $fdm_controller, $post;

		if ( ! $fdm_controller->permissions->check_permission( 'discounts' ) ) { return; }
	
		// Retrieve values for this if it exists
		$price_discount = get_post_meta( $post->ID, 'fdm_item_price_discount', true );
	
		// If we don't have a $post, this is being printed in the quick edit
		// panel. Input fields can't have name attributes because it messes with
		// the menu item filter forms.
		// @see https://github.com/NateWr/food-and-drink-menu/issues/35
		$input_attrs = '';
		$screen = get_current_screen();
		if ( is_object( $screen ) && $screen->base === 'post' ) {
			$input_attrs = ' name="fdm_item_price_discount"';
		} else {
			$input_attrs = ' data-name="fdm_item_price_discount"';
		}
	
	
		if ( empty( $fdm_controller->settings->get_setting('fdm-disable-price-discounted') ) ) :
			?>
	
				<div class="fdm-input-control fdm-input-price-discount">
					<label for="fdm_item_price_discount"><?php echo __( 'Discounted Price', 'food-and-drink-menu-pro' ); ?></label>
					<input type="text"<?php echo $input_attrs; ?> id="fdm_item_price_discount" value="<?php echo esc_attr( $price_discount ); ?>">
					<p class="description"><?php echo __( 'Enter a special discounted price if you want the regular price to appear crossed out.', 'food-and-drink-menu-pro' ); ?></p>
				</div>
	
			<?php
		endif;
	}

	/**
	 * Print the Menu footer HTML
	 * @since 2.0.7
	 */
	public function menu_item_row_actions( $actions, $post ) {

		if ( $post->post_type != FDM_MENUITEM_POST_TYPE ) { return $actions; }

		$url = admin_url( 'admin.php?page=' . FDM_MENUITEM_POST_TYPE . '&post=' . $post->ID . '&action=duplicate&fdm_nonce=' . $this->nonce );

		$actions['duplicate'] = '<a href="' . $url . '">' . __( 'Duplicate', 'food-and-drink-menu' ) . '</a>'; 

		return $actions;
	}

	/**
	 * Print the Menu footer HTML
	 * @since 1.0
	 */
	public function show_menu_footer() {

		// Retrieve existing settings
		global $post;
		$footer = get_post_meta( $post->ID, 'fdm_menu_footer_content', true );

		wp_editor(
			$footer,
			'fdm_menu_footer_content',
			array(
				'textarea_rows' => 5
			)
		);
	}

	/**
	 * Print Menu Item custom fields HTML
	 * @since 2.0
	 */
	public function show_menu_item_custom_fields() {
		global $post, $fdm_controller;

		$fields = $fdm_controller->settings->get_menu_item_custom_fields();

		$values = get_post_meta( $post->ID, '_fdm_menu_item_custom_fields', true );
		if ( ! is_array($values ) ) { $values = array(); } 

		if ( ! empty( $fields ) ) { ?>
			<div class='fdm-menu-item-custom-fields-container'>
				<p><?php _e('Custom Fields', 'food-and-drink-menu'); ?></p>
				<div class='fdm-menu-item-custom-fields'>
					<?php foreach ( $fields as $field ) {?>
						<div class='fdm-menu-item-custom-field-name'><?php echo $field->name; ?></div>
						<div class='fdm-menu-item-custom-field-input'>
							<?php if ( $field->type == 'section' ) : ?>
							<?php elseif ( $field->type == 'text' ) : ?>
								<input type='text' name='<?php echo $field->slug; ?>' value='<?php echo isset( $values[$field->slug] ) ? $values[$field->slug] : ''; ?>' />
							<?php elseif ( $field->type == 'textarea' ) : ?>
								<textarea name='<?php echo $field->slug; ?>'><?php echo isset( $values[$field->slug] ) ? $values[$field->slug] : ''; ?></textarea>
							<?php elseif ( $field->type == 'select' ) : ?>
								<select name='<?php echo $field->slug; ?>'>
									<?php $field_values = explode(",", $field->values); ?>
									<?php foreach ( $field_values as $value ) { ?>
										<option value='<?php echo sanitize_title($value); ?>' <?php echo ( isset( $values[$field->slug] ) and $values[$field->slug] == sanitize_title($value) ) ? 'selected="selected"' : ''; ?> ><?php echo $value; ?></option>
									<?php } ?>
								</select>
							<?php elseif ( $field->type == 'checkbox' ) : ?>
								<?php $field_values = explode(",", $field->values); ?>
								<?php foreach ( $field_values as $value ) { ?>
									<input type='checkbox' name='<?php echo $field->slug; ?>[]' value='<?php echo sanitize_title($value); ?>' <?php echo ( isset( $values[$field->slug] ) and is_array( $values[$field->slug] ) and in_array( sanitize_title( $value ), $values[$field->slug] ) ) ? 'checked="checked"' : ''; ?> /><?php echo $value;?><br />
								<?php } ?>
							<?php endif; ?>
						</div>
					<?php } ?>
				</div>
			</div>
		<?php }
	}

	/**
	 * Print Menu Item ordering options HTML
	 * @since 2.1
	 */
	public function show_menu_item_ordering_options() {
		global $post, $fdm_controller;

		$ordering_options = get_post_meta( $post->ID, '_fdm_ordering_options', true );
		if ( ! is_array( $ordering_options ) ) { $ordering_options = array(); }

		$max_key = sizeOf( $ordering_options );

		?>

		<table class="fdm-menu-item-ordering-options">

			<thead>
				<tr>
					<th></th>
					<th><?php _e( 'Name', 'food-and-drink-menu' ); ?></th>
					<th><?php _e( 'Default?', 'food-and-drink-menu' ); ?></th>
					<th><?php _e( 'Extra Cost', 'food-and-drink-menu' ); ?></th>
				</tr>
			</thead>

			<tbody>

			<?php foreach ( $ordering_options as $key => $ordering_option ) { ?>

				<tr>
					<td class='fdm-menu-item-ordering-option-delete'><?php _e( 'Delete', 'food-and-drink-menu' ); ?></td>
					<td><input type='text' name='ordering_option[<?php echo $key; ?>][name]' value='<?php echo $ordering_option['name']; ?>' /></td>
					<td><input type='checkbox' name='ordering_option[<?php echo $key; ?>][default]' value='true' <?php echo ( isset( $ordering_option['default'] ) and $ordering_option['default'] ) ? 'checked' : ''; ?> /></td>
					<td><input type='number' name='ordering_option[<?php echo $key; ?>][cost]' value='<?php echo $ordering_option['cost']; ?>' /></td>
				</tr>

				<?php $max_key = max( $max_key, $key ); ?>

			<?php } ?>

				<tr>
					<td colspan='4' class='fdm-menu-item-add-ordering-option' data-nextkey='<?php echo $max_key + 1; ?>'><?php _e( 'Add&nbsp;&plus;', 'food-and-drink-menu' ); ?></td>
				</tr>
			</tbody>

		</table>

		<?php
	}

	/**
	 * Print Menu Item related items HTML
	 * @since 2.0
	 */
	public function show_menu_item_related_items() {
		global $post, $fdm_controller;

		if ( $fdm_controller->settings->get_setting('fdm-related-items') != 'manual') { return; }

		$related_items = get_post_meta( $post->ID, '_fdm_related_items', true );
		if ( ! is_array($related_items) ) { $related_items = array(); }

		$menu_items = get_posts( array(
			'post_type' 		=> 'fdm-menu-item',
			'posts_per_page' 	=> -1,
			'post__not_in'		=> array( $post->ID )
		) );
		?>

		<div class='fdm-menu-item-related-items-container'>
			<p><?php _e('Related Items', 'food-and-drink-menu'); ?></p>
			<div class='fdm-menu-item-related-items'>
				<?php for ( $i=1; $i<=4; $i++ ) { ?>
					<div class='fdm-menu-item-related-item-label'><?php echo __('Related Item ', 'food-and-drink-menu') . $i; ?></div>
					<div class='fdm-menu-item-related-item-input'>
						<select name='related_item_<?php echo $i; ?>'>
							<option value=''></option>
							<?php
								foreach ( $menu_items as $menu_item ) { 
									if ( $menu_item->ID != $post->ID ) { echo "<option value='" . $menu_item->ID . "' " . ( $menu_item->ID == $related_items[$i] ? 'selected="selected"' : '' ) . ">" . $menu_item->post_title . "</option>"; }
								}
							?>
						</select>
					</div>
				<?php } ?>
			</div>
		</div>

		<?php 
	}

	/**
	 * Print the Menu Item specials metabox HTML
	 * @since 2.0
	 */
	public function fdmp_show_item_special() {
	
		global $post;
		global $fdm_controller;
	
		// Retrieve values for this if it exists
		$special_selected = get_post_meta( $post->ID, 'fdm_item_special', true );

		$special_options = array(
			'none'     => __( 'None', 'food-and-drink-menu-pro' ),
			'sale'     => __( 'On Sale', 'food-and-drink-menu-pro' ),
			'offer'    => __( 'Special Offer', 'food-and-drink-menu-pro' ),
			'featured' => __( 'Featured', 'food-and-drink-menu-pro' ),
			'sold_out' => __( 'Sold Out', 'food-and-drink-menu-pro' )
		);

		$options = apply_filters( 'fdm_meta_boxe_specials_options', $special_options );

		?>

		<input type="hidden" name="fdm_nonce" value="<?php echo $fdm_controller->cpts->nonce; ?>">
		<p class="description">Select a special notice to display with this item.</p>
		<div class="fdm-source-input fdm-input-controls">

		<?php

		foreach ($options as $option => $label)
		{
			$value = 'none' == $option ? '' : $option;
			$checked = empty( $option ) || $option == $special_selected 
				? 'checked="checked"' 
				: '';
		?>
			<div class="fdm-input-control fdm-radio-control">
				<input 
					type="radio" 
					name="fdm_item_special" 
					id="fdm_item_special_<?php echo $option; ?>" 
					value="<?php echo $value; ?>" 
					<?php echo $checked; ?>>
				<label for="fdm_item_special_<?php echo $option; ?>">
					<?php echo $label; ?>
				</label>
			</div>
		<?php
		} //foreach ends
		?>

		</div>

		<?php
	}

	/**
	 * Print the Menu Item source location metabox HTML
	 * @since 2.0
	 */
	public function fdmp_show_item_source() {
	
		// Retrieve value for this if it exists
		global $post;
		$source_name = get_post_meta( $post->ID, 'fdm_item_source_name', true );
		$source_desc = get_post_meta( $post->ID, 'fdm_item_source_description', true );
		$source_address = get_post_meta( $post->ID, 'fdm_item_source_address', true );
		$source_zoom = get_post_meta( $post->ID, 'fdm_item_source_zoom', true );
	
		// Set default zoom level
		if ( !$source_zoom ) {
			$source_zoom = 5;
		}
	
		global $fdm_controller;
	
		?>
	
		<input type="hidden" name="fdm_nonce" value="<?php echo $fdm_controller->cpts->nonce; ?>">
		<div class="fdm-source">
	
			<div class="fdm-source-input fdm-input-controls">
				<div class="fdm-input-control">
					<label for="fdm_item_source_name"><?php echo __( 'Source Name', 'food-and-drink-menu-pro' ); ?></label>
					<input type="text" name="fdm_item_source_name" id="fdm_item_source_name" value="<?php echo esc_attr( $source_name ); ?>">
				</div>
				<div class="fdm-input-control">
					<label for="fdm_item_source_description"><?php echo __( 'Source Description', 'food-and-drink-menu-pro' ); ?></label>
					<textarea class="small-text" name="fdm_item_source_description" id="fdm_item_source_description"><?php echo esc_attr( $source_desc ); ?></textarea>
				</div>
	
				<?php if ( empty( $fdm_controller->settings->get_setting('fdm-disable-src-map') ) ) : ?>
	
					<div class="fdm-input-control">
						<label for="fdm_item_source_address"><?php echo __( 'Source Address', 'food-and-drink-menu-pro' ); ?></label>
						<input type="text" name="fdm_item_source_address" id="fdm_item_source_address" value="<?php echo esc_attr( $source_address ); ?>">
						<p class="description"><?php echo __( 'Enter the address to use in a Google Map.', 'food-and-drink-menu-pro' ); ?></p>
					</div>
					<div class="fdm-input-control">
						<label for="fdm_item_source_zoom"><?php echo __( 'Map Zoom Level', 'food-and-drink-menu-pro' ); ?></label>
						<input type="number" step="1" min="1" max="20" name="fdm_item_source_zoom" id="fdm_item_source_zoom" value="<?php echo esc_attr( $source_zoom ); ?>">
					</div>
	
				<?php endif; ?>
	
			</div>
	
			<?php if ( empty( $fdm_controller->settings->get_setting('fdm-disable-src-map') ) ) : ?>
	
				<div class="fdm-source-map">
				</div>
	
			<?php endif; ?>
	
			<div class="clearfix"></div>
	
		</div>
	
		<?php
	}

	/**
	 * Print the add image field for sections
	 * @since 2.0
	 */
	public function add_section_image() { ?>
		<div class='form-field fdm-menu-section-image'>
			<label for="tag-image"><?php _e('Image', 'food-and-drink-menu'); ?></label>
			<input id="fdm_menu_section_image" type="text" size="36" name="fdm_menu_section_image" value="http://" />
			<input id="fdm_menu_section_image_button" class="button" type="button" value="Select Image" />
			<p><?php _e("Upload an image of the for this section:", 'food-and-drink-menu'); ?></p>
		</div>
	<?php }

	/**
	 * Print the edit image field for sections
	 * @since 2.0
	 */
	public function edit_section_image( $tag ) { 
		$image_id = get_term_meta( $tag->term_id, '_fdm_menu_section_image', true);
		?>

		<tr class='form-field fdm-edit-menu-section-image'>
			<th scope='row'>
				<label for="tag-image"><?php _e('Image', 'food-and-drink-menu'); ?></label>
			</th>
			<td>
				<?php if($image_id) { ?>
					<img class='fdm-edit-menu-section-image-preview' src='<?php echo wp_get_attachment_url($image_id); ?>' />
					<p class="description"><?php _e("Clear the box and Update to remove the image.", 'food-and-drink-menu'); ?></p>
					<br>
				<?php } ?>
				<input id="fdm_menu_section_image" class="regular-text" type="text" size="36" name="fdm_menu_section_image" value="<?php echo ($image_id ? wp_get_attachment_url($image_id) : ''); ?>" />
				<input id="fdm_menu_section_image_button" class="button" type="button" value="Select Image" />
				<p class="description"><?php _e("Upload an image of the for this section.", 'food-and-drink-menu'); ?></p>
		</div>
	<?php }

	public function save_section_image( $term_id ) {

		$current_image_id = get_term_meta( $tag->term_id, '_fdm_menu_section_image', true );
		$new_image_id = attachment_url_to_postid( esc_url_raw( $_POST['fdm_menu_section_image'] ) );

		// Update image
		if ( $current_image_id != $new_image_id ) {

			update_term_meta( $term_id, '_fdm_menu_section_image', $new_image_id );
		}

		// Remove image
		if ( empty( $_POST['fdm_menu_section_image'] ) ) {

			update_term_meta( $term_id, '_fdm_menu_section_image', '' );
		}
	}

	/**
	 * Print the Menu organizer HTML
	 * @since 1.0
	 */
	public function show_menu_organizer() {

		// Retrieve existing settings
		global $post;
		$column_one = get_post_meta( $post->ID, 'fdm_menu_column_one', true );
		$column_two = get_post_meta( $post->ID, 'fdm_menu_column_two', true );

		// Retrieve sections and store in HTML lists
		$sections = get_terms( 'fdm-menu-section', array( 'hide_empty' => false ) );
		foreach( $sections as $section ) {
			$alt_title = get_post_meta( $post->ID, 'fdm_menu_section_' . $section->term_id, true );
			if ( $alt_title ) {
				$section->name = $alt_title;
			}
		}
		?>

			<input type="hidden" name="fdm_nonce" value="<?php echo $this->nonce; ?>">
			<input type="hidden" id="fdm_menu_column_one" name="fdm_menu_column_one" value="<?php esc_attr_e( $column_one ); ?>">
			<input type="hidden" id="fdm_menu_column_two" name="fdm_menu_column_two" value="<?php esc_attr_e( $column_two ); ?>">

			<p><?php echo __( 'Drag-and-drop Menu Sections into columns on your menu.', 'food-and-drink-menu' ); ?></p>

			<div id="fdm-menu-organizer">
				<div class="fdm-column">
					<h3>
						<?php esc_html_e( 'Available Sections', 'food-and-drink-menu' ); ?>
					</h3>
					<?php if ( empty( $sections ) ) : ?>
						<div class="fdm-no-sections">
							<?php
								printf(
									__( "You don't have any Menu Sections yet. When you create Menu Items, you should %sassign them to Menu Sections%s.", 'food-and-drink-menu' ),
									'<a href="' . esc_url( 'https://www.fivestarplugins.com/support-center/?Plugin=FDM' ) . '" target="_blank">',
									'</a>'
								);
							?>
						</div>

					<?php else : ?>
						<ul id="fdm-menu-sections-list" class="fdm-sortable-sections">
							<?php foreach( $sections as $section ) : ?>
								<li data-term-id="<?php esc_attr_e( $section->term_id ); ?>">
									<div class="fdm-title">
										<span class="fdm-term-count"><?php esc_html_e( $section->count ); ?></span>
										<span class="fdm-term-name"><?php esc_html_e( $section->name ); ?></span>
									</div>
									<a href="#" class="fdm-edit-section-name">
										<span class="screen-reader-text">
											<?php esc_html_e( 'Edit Section Name', 'food-and-drink-menu' ); ?>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="fdm-column">
					<h3>
						<?php esc_html_e( 'First Column', 'food-and-drink-menu' ); ?>
					</h3>
					<ul id="fdm_menu_column_one_list" class="fdm-sortable-sections fdm-sections-added"></ul>
				</div>
				<div class="fdm-column">
					<h3>
						<?php esc_html_e( 'Second Column', 'food-and-drink-menu' ); ?>
					</h3>
					<ul id="fdm_menu_column_two_list" class="fdm-sortable-sections fdm-sections-added"></ul>
				</div>

				<p class="description">
					<?php esc_html_e( 'Hint: Leave the second column empty to display the menu in a single column.', 'food-and-drink-menu' ); ?>
				</p>
			</div>

		<?php

	}

	/**
	 * Print the metabox for selecting a page template for menus
	 *
	 * @since 1.5
	 */
	public function show_page_template() {

		$templates = array(
			'' => esc_html__( 'Default Menu Template', 'food-and-drink-menu' ),
		);

		$templates = $this->add_menu_templates( $templates );

		global $post;
		$selected = get_post_meta( $post->ID, '_wp_page_template', true );
		?>

			<select name="_wp_page_template">
				<?php foreach( $templates as $val => $label ) : ?>
					<option value="<?php esc_attr_e( $val ); ?>"<?php if ( $selected == $val ) : ?> selected<?php endif; ?>><?php esc_html_e( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p>
				<?php esc_html_e( 'Choose any of your theme templates to display your menu.', 'food-and-drink-menu' ); ?>
			</p>

		<?php
	}

	/**
	 * Save the metabox data from menu items and menus
	 * @since 1.0
	 */
	public function save_meta( $post_id ) {
		global $fdm_controller;

		// Verify nonce
		if ( !isset( $_POST['fdm_nonce'] ) || !wp_verify_nonce( $_POST['fdm_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check permissions
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Array of values to fetch and store
		$meta_ids = array();

		global $post;

		// Define Menu Item data
		if ( FDM_MENUITEM_POST_TYPE == $post->post_type ) {

			$meta_ids['fdm_item_price'] = 'sanitize_text_field';

			$fields = $fdm_controller->settings->get_menu_item_custom_fields();

			if ( isset( $_POST['ordering_option'] ) ) { 
				foreach ( $_POST['ordering_option'] as $key => $ordering_option ) { 
					array_map( 'sanitize_text_field', $_POST['ordering_option'][$key] );
				}
				update_post_meta( $post->ID, '_fdm_ordering_options', $_POST[ 'ordering_option'] );
			}

			$values = array(); 
			foreach ($fields as $field) {
				if ( $field->type == 'section') :
				else :
					$values[$field->slug] = ( isset( $_POST[$field->slug] ) and is_array( $_POST[$field->slug] ) ) ? array_map( 'sanitize_text_field' , $_POST[$field->slug] ) : sanitize_text_field( $_POST[$field->slug] );
				endif;
			}

			update_post_meta( $post->ID, '_fdm_menu_item_custom_fields', $values );

			$related_items = array(
				1 => ( isset($_POST['related_item_1']) ? intval( $_POST['related_item_1'] ) : '' ),
				2 => ( isset($_POST['related_item_2']) ? intval( $_POST['related_item_2'] ) : '' ),
				3 => ( isset($_POST['related_item_3']) ? intval( $_POST['related_item_3'] ) : '' ),
				4 => ( isset($_POST['related_item_4']) ? intval( $_POST['related_item_4'] ) : '' )
			);
			update_post_meta( $post->ID, '_fdm_related_items', $related_items );
		}

		// Define Menu organizer metadata
		if ( FDM_MENU_POST_TYPE == $post->post_type ) {

			$meta_ids['fdm_menu_column_one'] = 'sanitize_text_field';
			$meta_ids['fdm_menu_column_two'] = 'sanitize_text_field';
			$meta_ids['fdm_menu_footer_content'] = 'wp_kses_post';

			// Custom section names for each menu
			$sections = array_filter(
				array_merge(
					isset( $_POST['fdm_menu_column_one'] ) ? explode( ',', sanitize_text_field( $_POST['fdm_menu_column_one'] ) ) : array(),
					isset( $_POST['fdm_menu_column_two'] ) ? explode( ',', sanitize_text_field( $_POST['fdm_menu_column_two'] ) ) : array()
				)
			);
			foreach( $sections as $section_id ) {
				if ( isset( $_POST['fdm_menu_section_' . absint( $section_id )] ) ) {
					$meta_ids['fdm_menu_section_' . absint( $section_id )] = 'sanitize_text_field';
				}
			}

			// Save menu templates for WP versions before 4.7
			if ( version_compare( get_bloginfo( 'version' ), '4.7-beta-1', '<' ) ) {
				$meta_ids['_wp_page_template'] = 'sanitize_file_name';
			}
		}

		// Create filter so addons can add new data
		$meta_ids = apply_filters( 'fdm_save_meta', $meta_ids );

		// Save the metadata
		foreach ($meta_ids as $meta_id => $sanitize_callback) {

			if ( $meta_id == 'fdm_item_price' ) {
				// If an out-of-date copy of Food and Drink Menu Pro is running,
				// we need to coerce the $POST value into an array.
				if ( isset( $_POST[$meta_id] ) && !is_array( $_POST[$meta_id] ) ) {
					$_POST[$meta_id] = array( $_POST[$meta_id] );
				}

				delete_post_meta( $post_id, $meta_id );
				$new = isset( $_POST[$meta_id] ) ? array_map( $sanitize_callback, $_POST[$meta_id] ) : array();
				foreach( $new as $new_entry ) {
					if ( $new_entry !== '' ) {
						add_post_meta( $post_id, $meta_id, $new_entry );
					}
				}
			} else {
				$cur = get_post_meta( $post_id, $meta_id, true );
				$new = isset( $_POST[$meta_id] ) ? call_user_func( $sanitize_callback, $_POST[$meta_id] ) : '';
				if ( $new && $new != $cur ) {
					update_post_meta( $post_id, $meta_id, $new );
				} elseif ( $new == '' && $cur ) {
					delete_post_meta( $post_id, $meta_id, $cur );
				}
			}
		}
	}

	/**
	 * Save meta data
	 * @since 2.0
	 */
	public function fdmp_save_meta( $meta_ids ) {
	
		global $post;
	
		// Define Menu Item data
		if ( FDM_MENUITEM_POST_TYPE == $post->post_type ) {
	
			$meta_ids['fdm_item_price_discount'] = 'sanitize_text_field';
			$meta_ids['fdm_item_special'] = 'sanitize_text_field';
			$meta_ids['fdm_item_source_name'] = 'sanitize_text_field';
			$meta_ids['fdm_item_source_description'] = 'sanitize_text_field';
			$meta_ids['fdm_item_source_address'] = 'sanitize_text_field';
			$meta_ids['fdm_item_source_zoom'] = 'sanitize_text_field';
		}
	
		return $meta_ids;
	}

	/**
	 * Add a field for icon to new Menu Item Flag page
	 * @since 2.0
	 */
	public function fdmp_item_flag_new_icon_field() {
		global $fdm_controller;

		// Handle menu item flags
		$flag_permissions = $fdm_controller->permissions->check_permission( 'flags' );
		if ( $fdm_controller->settings->get_setting('fdm-disable-menu-item-flags') or ! $flag_permissions ) { return; }
	
		?>
	
		<div class="form-field">
			<label for="fdm_menu_item_flag_meta[fdm_menu_item_flag_icon]"><?php echo __( 'Item Icon', 'food-and-drink-menu-pro' ); ?></label>
			<?php $this->fdmp_item_flag_select_icon_field_html(); ?>
		</div>
	
		<?php
	
	}
	
	/**
	 * Add a field for icon to the edit interface for Menu Item Flags
	 * @since 2.0
	 */
	public function fdmp_item_flag_edit_icon_field( $term ) {
		global $fdm_controller;

		// Handle menu item flags
		$flag_permissions = $fdm_controller->permissions->check_permission( 'flags' );
		if ( $fdm_controller->settings->get_setting('fdm-disable-menu-item-flags') or ! $flag_permissions ) { return; }
	
		// Retrieve the existing value
		$term_meta = get_option( "fdm_menu_item_flag_icon_" . $term->term_id );
	
		?>
	
		<tr class="form-field">
		<th scope="row" valign="top"><label for="fdm_menu_item_flag_meta[fdm_menu_item_flag_icon]"><?php echo __( 'Item Icon', 'food-and-drink-menu-pro' ); ?></label></th>
			<td>
				<?php $this->fdmp_item_flag_select_icon_field_html( $term_meta ); ?>
			</td>
		</tr>
	
		<?php
	
	}
	
	/**
	 * Output the Menu Item Flag icon selection code
	 * @sa fdm_item_flag_new_icon_field(), fdm_item_flag_edit_icon_field()
	 * @since 2.0
	 */
	public function fdmp_item_flag_select_icon_field_html( $term_meta = null ) {
	
		// Retrieve an existing icon if it exists
		$item_flag_icon = '';
		if ( is_array( $term_meta ) && isset( $term_meta['fdm_menu_item_flag_icon'] ) ) {
			$item_flag_icon = esc_attr( $term_meta['fdm_menu_item_flag_icon'] );
		}
	
		?>
	
			<div id="fdm_menu_item_flag_selection_field">
				<input type="hidden" name="fdm_menu_item_flag_meta[fdm_menu_item_flag_icon]" id="fdm_menu_item_flag_icon_field" value="<?php echo $item_flag_icon; ?>">
				<div id="fdm_menu_item_flag_selection_popup">
					<p id="fdm_menu_item_notice">
						<span class="fdm-icon"></span>
					</p>
					<p class="description">General</p>
					<ul>
						<li class="fdm-icon" data-id="chili"></li>
						<li class="fdm-icon" data-id="chili-2"></li>
						<li class="fdm-icon" data-id="chili-3"></li>
						<li class="fdm-icon" data-id="decaf"></li>
					</ul>
					<p class="description">Ethical Preferences</p>
					<ul>
						<li class="fdm-icon" data-id="fair-trade"></li>
						<li class="fdm-icon" data-id="free-range"></li>
						<li class="fdm-icon" data-id="halal"></li>
						<li class="fdm-icon" data-id="kosher"></li>
						<li class="fdm-icon" data-id="local"></li>
						<li class="fdm-icon" data-id="organic"></li>
						<li class="fdm-icon" data-id="vegan"></li>
						<li class="fdm-icon" data-id="vegetarian"></li>
						<li class="fdm-icon" data-id="vegetarian-2"></li>
					</ul>
					<p class="description">Health Concerns</p>
					<ul>
						<li class="fdm-icon" data-id="has-dairy"></li>
						<li class="fdm-icon" data-id="has-peanuts"></li>
						<li class="fdm-icon" data-id="has-sesame"></li>
						<li class="fdm-icon" data-id="has-shellfish"></li>
						<li class="fdm-icon" data-id="heart-healthy-1"></li>
						<li class="fdm-icon" data-id="heart-healthy-2"></li>
						<li class="fdm-icon" data-id="low-sodium"></li>
						<li class="fdm-icon" data-id="no-dairy"></li>
						<li class="fdm-icon" data-id="no-gluten"></li>
						<li class="fdm-icon" data-id="no-gluten-2"></li>
						<li class="fdm-icon" data-id="no-gm"></li>
						<li class="fdm-icon" data-id="no-peanuts"></li>
						<li class="fdm-icon" data-id="no-sesame"></li>
						<li class="fdm-icon" data-id="no-shellfish"></li>
						<li class="fdm-icon" data-id="no-sugar"></li>
						<li class="fdm-icon" data-id="superfood"></li>
						<li class="fdm-icon" data-id="superfood-2"></li>
						<li class="fdm-icon" data-id="wholegrain"></li>
						<li class="fdm-icon" data-id="antibiotic-hormone-free-1"></li>
						<li class="fdm-icon" data-id="antibiotic-hormone-free-2"></li>
					</ul>
				</div>
				<div class="fdm_menu_item_flag_preview_panel">
					<p><a href="#" class="fdm_menu_item_flag_selector"><?php echo __( 'Select an icon', 'food-and-drink-menu-pro' ); ?></a></p>
					<p class="description"><?php echo __( 'The icon you wish to display for this item flag.', 'food-and-drink-menu-pro' ); ?></p>
				</div>
			</div>
	
		<?php
	
	}
	
	/**
	 * Save icon field for Menu Item Flag taxonomy
	 * @since 2.0
	 */
	public function fdmp_item_flag_save_icon_field( $term_id ) {
		global $fdm_controller;

		// Handle menu item flags
		$flag_permissions = $fdm_controller->permissions->check_permission( 'flags' );
		if ( $fdm_controller->settings->get_setting('fdm-disable-menu-item-flags') or ! $flag_permissions ) { return; }
	
		if ( isset( $_POST['fdm_menu_item_flag_meta'] ) ) {
	
			$term_meta = get_option( "fdm_menu_item_flag_icon_" . $term_id );
			$cat_keys = array_keys( $_POST['fdm_menu_item_flag_meta'] );
			foreach ( $cat_keys as $key ) {
				if ( isset ( $_POST['fdm_menu_item_flag_meta'][$key] ) ) {
					$term_meta[$key] = sanitize_text_field( $_POST['fdm_menu_item_flag_meta'][$key] );
				}
			}
	
			// Save the option array.
			update_option( "fdm_menu_item_flag_icon_" . $term_id, $term_meta );
	
		}
	}

	/**
	 * Add the menu section column header to the admin list of menu items
	 * @since 1.4
	 */
	public function menu_item_posts_columns( $columns ) {
		return array(
			'cb'		=> '<input type="checkbox" />',
			'title'		=> __( 'Title' ),
			'price'		=> __( 'Price', 'food-and-drink-menu' ),
			'sections'	=> __( 'Sections', 'food-and-drink-menu' ),
			'date'		=> __( 'Date' ),
		);
	}

	/**
	 * Make new column headers sortable
	 * @since 1.4
	 */
	public function menu_item_posts_sortable_columns( $columns ) {
		$columns['price'] = 'price';

		return $columns;
	}

	/**
	 * Modify query rules to sort on new columns
	 * @since 1.4
	 */
	public function menu_item_posts_orderby( $query ) {

		if ( !is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( $orderby == 'price' ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', 'fdm_item_price' );
		}

	}

	/**
	 * Add the menu sections to the admin list of menu items
	 * @since 1.4
	 */
	public function menu_item_posts_columns_content( $column, $post ) {

		if ( $column == 'price' ) {
			$prices = (array) get_post_meta( $post, 'fdm_item_price' );
			if ( !empty( $prices ) ) {
				?>
				<div class="fdm-item-list-price" data-menu-item-id="<?php echo absint( $post ); ?>">
					<div class="fdm-item-price-summary">
						<?php
							echo join(
								apply_filters( 'fdm_prices_separator', _x( '/', 'Separator between multiple prices.', 'food-and-drink-menu' ) ),
								$prices
							);
						?>
					</div>
					<div class="fdm-item-price-actions">
						<a href="#" class="fdm-item-price-edit">
							<?php esc_html_e( 'Edit Price', 'food-and-drink-menu' ); ?>
						</a>
					</div>
					<div class="fdm-item-price-form">
						<?php $this->show_item_price(); ?>
						<div class="fdm-item-price-buttons">
							<button class="fdm-item-price-cancel button">
								<?php esc_html_e( 'Cancel', 'food-and-drink-menu' ); ?>
							</button>
							<span class="spinner"></span>
							<button class="fdm-item-price-save button" disabled="disabled">
								<?php esc_html_e( 'Update Price', 'food-and-drink-menu' ); ?>
							</button>
						</div>
						<div class="fdm-item-price-message"></div>
					</div>
				</div>
				<?php
			}
		}

		if ( $column == 'sections' ) {
			$terms = wp_get_post_terms( $post, 'fdm-menu-section' );
			$output = array();
			foreach( $terms as $term ) {
				$output[] = '<a href="' . admin_url( 'edit-tags.php?action=edit&taxonomy=fdm-menu-section&tag_ID=' . $term->term_taxonomy_id . '&post_type=fdm-menu-item' ) . '">' . $term->name . '</a>';
			}

			echo join( __( ', ', 'Separator in list of Menu Sections', 'food-and-drink-menu' ), $output );
		}
	}

	/**
	 * Add a filter to view by menu section on the admin list of menu items
	 * @since 1.4
	 */
	public function menu_item_posts_filters() {

		if ( !is_admin() ) {
			return;
		}

		$screen = get_current_screen();
		if ( is_object( $screen ) && $screen->post_type == 'fdm-menu-item' ) {

			$terms = get_terms( 'fdm-menu-section' );

			if ( !empty( $terms ) ) : ?>
				<select name="section">
					<option value=""><?php _e( 'All sections', 'food-and-drink-menu' ); ?></option>

					<?php foreach( $terms as $term ) : ?>
					<option value="<?php echo esc_attr( $term->term_id ); ?>"<?php if( !empty( $_GET['section'] ) && $_GET['section'] == $term->term_id ) : ?> selected="selected"<?php endif; ?>><?php echo esc_attr( $term->name ); ?></option>
					<?php endforeach; ?>

					<option value="-1"><?php _e( 'Unassigned items', 'food-and-drink-menu' ); ?></option>
				</select>
			<?php endif;
		}
	}

	/**
	 * Apply selected filters to the admin list of menu items
	 * @since 1.4
	 */
	public function menu_item_posts_filter_query( $query ) {

		if ( !is_admin() || ( !empty( $query->query['post_type'] ) && $query->query['post_type'] !== FDM_MENUITEM_POST_TYPE ) || !function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( is_object( $screen ) && $screen->post_type == FDM_MENUITEM_POST_TYPE && !empty( $_GET['section'] ) ) {
			$section = intval( $_GET['section'] );

			// Get menu items not assigned to any section
			if ( $section === -1 ) {
				$terms = get_terms( 'fdm-menu-section', array( 'fields' => 'ids' ) );
				$query->query_vars['tax_query'] = array(
					array(
						'taxonomy'	=> 'fdm-menu-section',
						'field'		=> 'id',
						'terms'		=> $terms,
						'operator'	=> 'NOT IN',
					)
				);

			// Get menu items from a specific section
			} else {
				$query->query_vars['tax_query'] = array(
					array(
						'taxonomy'	=> 'fdm-menu-section',
						'field'		=> 'id',
						'terms'		=> $section,
					)
				);
			}
		}
	}

	/**
	 * Add the menu sections column header to the admin list of menus
	 * @since 1.4
	 */
	public function menu_posts_columns( $columns ) {
		return array(
			'cb'		=> '<input type="checkbox" />',
			'title'		=> __( 'Title' ),
			'shortcode'	=> __( 'Shortcode' ),
			'sections'	=> __( 'Sections', 'food-and-drink-menu' ),
			'date'		=> __( 'Date' ),
		);
	}

	/**
	 * Add the shortcode and menu sections to the admin list of menu items
	 * @since 1.4
	 */
	public function menu_posts_columns_content( $column, $post ) {

		if ( $column == 'sections' ) {
			$post_meta = get_post_meta( $post );

			$col1 = !empty( $post_meta['fdm_menu_column_one'] ) ? array_filter( explode( ',', $post_meta['fdm_menu_column_one'][0] ) ) : array();
			$col2 = !empty( $post_meta['fdm_menu_column_two'] ) ? array_filter( explode( ',', $post_meta['fdm_menu_column_two'][0] ) ) : array();

			if ( !empty( $col1 ) || !empty( $col2 ) ) :
				$terms = get_terms( 'fdm-menu-section', array( 'include' => array_merge( $col1, $col2 ), 'hide_empty' => false ) );
				?>

				<div class="fdm-cols">
					<div class="fdm-col">
						<?php foreach( $col1 as $id ) : ?>
							<?php $term = $this->get_term_from_array($terms, $id); ?>
							<?php if ( !empty( $term ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=fdm-menu-section&tag_ID=' . $id . '&post_type=' . FDM_MENUITEM_POST_TYPE ) ); ?>">
									<span class="fdm-term-count"><?php esc_html_e( $term->count ); ?></span>
									<?php echo $term->name; ?>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<div class="fdm-col">
						<?php foreach( $col2 as $id ) : ?>
							<?php $term = $this->get_term_from_array($terms, $id); ?>
							<?php if ( !empty( $term ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=fdm-menu-section&tag_ID=' . $id . '&post_type=' . FDM_MENUITEM_POST_TYPE ) ); ?>">
									<span class="fdm-term-count"><?php esc_html_e( $term->count ); ?></span>
									<?php echo $term->name; ?>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>

			<?php endif;
		}

		if ( $column == 'shortcode' ) { ?>

			<div class="fdm-shortocde">
				[fdm-menu id='<?php echo esc_html( $post ); ?>']
			</div>
		<?php }
	}

	public function get_term_from_array($terms, $term_id) {
		foreach ($terms as $term) {if ($term->term_id == $term_id) {return $term;}}

		return array();
	}

	/**
	 * Respond to unauthenticated ajax requests
	 *
	 * @since 1.5
	 */
	public function ajax_nopriv() {

		wp_send_json_error(
			array(
				'error' => 'loggedout',
				'msg' => sprintf( __( 'You have been logged out. Please %slogin again%s.', 'food-and-drink-menu' ), '<a href="' . wp_login_url( admin_url( 'edit.php?post_type=' . FDM_MENUITEM_POST_TYPE ) ) . '">', '</a>' ),
			)
		);
	}

	/**
	 * Respond to ajax requests with updated menu item prices
	 *
	 * @since 1.5
	 */
	public function ajax_menu_item_price() {

		// Authenticate request
		if ( !check_ajax_referer( 'fdm-admin', 'nonce' ) || !current_user_can( 'edit_posts' ) ) {
			$this->ajax_nopriv();
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( !get_post_type( $id ) ) {
			wp_send_json_error(
				array(
					'error'		=> 'menu_item_not_found',
					'msg'		=> __( 'This menu item could not be found. Please reload the page and try again.', 'food-and-drink-menu' ),
				)
			);
		}

		$prices = isset( $_POST['prices'] ) && is_array( $_POST['prices'] ) ? array_filter( array_map( 'sanitize_text_field', $_POST['prices'] ) ) : array();

		delete_post_meta( $id, 'fdm_item_price' );
		foreach( $prices as $price ) {
			add_post_meta( $id, 'fdm_item_price', $price );
		}

		// WPML does not sync post on `delete_post_meta` and `add_post_meta`
		// See: https://wpml.org/forums/topic/why-does-wpml-not-listen-to-wp-api-functions-delete_post_meta-add_post_meta/#post-1500291
		do_action( 'wpml_sync_custom_field', $id, 'fdm_item_price' );

		$response = array(
			'id' => $id,
			'prices' => $prices,
			'price_summary' => join(
				apply_filters( 'fdm_prices_separator', _x( '/', 'Separator between multiple prices.', 'food-and-drink-menu' ) ),
				$prices
			),
		);

		$response = apply_filters( 'fdm_ajax_menu_item_price', $response, $id, $prices );

		wp_send_json_success( $response );
	}

	/**
	 * Save discount price when saved from menu item list
	 *
	 * @param array $response The response to the ajax request
	 * @param int $id The post ID
	 * @param array $prices Prices for this menu item
	 * @since 1.4
	 */
	public function fdmp_ajax_menu_item_price( $response, $id, $prices ) {
	
		$discount_price = isset( $_POST['discount_price'] ) ? sanitize_text_field( $_POST['discount_price'] ) : null;
		if ( is_null( $discount_price ) ) {
			return $response;
		}
	
		delete_post_meta( $id, 'fdm_item_price_discount' );
		if ( !empty( $discount_price ) || $discount_price == '0' ) {
			add_post_meta( $id, 'fdm_item_price_discount', $discount_price );
		}
	
		return $response;
	}

	/**
	 * Add page templates to the list of available templates
	 *
	 * @param array $post_templates
	 * @param WP_Theme $wp_theme
	 * @param WP_Post|null $post
	 * @since 1.5
	 */
	public function add_menu_templates( $post_templates, $wp_theme = null, $post = null ) {
		include_once ABSPATH . 'wp-admin/includes/theme.php';

		$page_template = get_page_template();
		if ( !empty( $page_template )  ) {
			$post_templates[$page_template] = esc_html__( 'Default Page Template', 'food-and-drink-menu' );
		}

		$page_templates = get_page_templates();
		if ( $page_templates ) {
			$page_templates = array_flip( $page_templates );
			foreach( $page_templates as $file => $label ) {
				$post_templates[$file] = sprintf(
					esc_html__( 'Page Template: %s', 'food-and-drink-menu' ),
					$label
				);
			}
		}

		return $post_templates;
	}


	/**
	 * Optionally load a page template instead of the singular menu template
	 *
	 * @param string $template Requested file
	 * @since 1.5
	 */
	public function load_menu_template( $template ) {

		if ( is_singular( FDM_MENU_POST_TYPE ) ) {
			$new_template = get_post_meta( get_the_ID(), '_wp_page_template', true );
			$new_template_file = locate_template( $new_template );
			if ( $new_template_file  ) {
				return $new_template_file;
			}
		}

		return $template;
	}

	/**
	 * Add Menu Item Flag taxonomy
	 * @since 2.0
	 */
	function fdmp_add_item_flag_taxonomy( $menu_item_taxonomies ) {
		global $fdm_controller;

		$flag_permissions = $fdm_controller->permissions->check_permission( 'flags' );
	
		if ( empty( $fdm_controller->settings->get_setting('fdm-disable-menu-item-flags') ) and $flag_permissions ) {
			$menu_item_taxonomies['fdm-menu-item-flag'] = array(
				'labels'	=> array(
					'name' => _x( 'Item Icons', 'taxonomy general name', 'food-and-drink-menu-pro' ),
					'singular_name' => _x( 'Item Icon', 'taxonomy singular name', 'food-and-drink-menu-pro' ),
					'search_items' => __( 'Search Item Icons', 'food-and-drink-menu-pro' ),
					'all_items' => __( 'All Item Icons', 'food-and-drink-menu-pro' ),
					'parent_item' => __( 'Item Icon', 'food-and-drink-menu-pro' ),
					'parent_item_colon' => __( 'Item Icon:', 'food-and-drink-menu-pro' ),
					'edit_item' => __( 'Edit Item Icon', 'food-and-drink-menu-pro' ),
					'update_item' => __( 'Update Item Icon', 'food-and-drink-menu-pro' ),
					'add_new_item' => __( 'Add New Item Icon', 'food-and-drink-menu-pro' ),
					'new_item_name' => __( 'Item Icon', 'food-and-drink-menu-pro' ),
				),
				'show_in_rest' => true,
			);
		}
	
		return $menu_item_taxonomies;
	}

	/**
	 * Determine whether a menu item post should be duplicated and handle the process
	 * @since 2.0.7
	 */
	public function maybe_duplicate_item() {
		
		// Exit if not a duplicating action
		if ( ! isset($_GET['page']) or $_GET['page'] != FDM_MENUITEM_POST_TYPE or ! isset( $_GET['action'] ) or $_GET['action'] != 'duplicate' ) { return; }

		// Verify nonce
		if ( !isset( $_GET['fdm_nonce'] ) || !wp_verify_nonce( $_GET['fdm_nonce'], basename( __FILE__ ) ) ) {
			return;
		}
			
		$post_id = intval( $_GET['post'] );

		$new_post_id = $this->duplicate_post( $post_id );

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . FDM_MENUITEM_POST_TYPE ) );

		exit;
	}

	/**
	 * Duplicate a menu item post
	 * @since 2.0.7
	 */
	function duplicate_post( $post_id ) {

		$post = get_post( $post_id );

		$new_post = array(
			'post_title' 	=> $post->post_title,
			'post_content' 	=> $post->post_content,
			'post_type'		=> $post->post_type,
			'post_author'	=> $post->post_author,
			'post_status'	=> $post->post_status,
		);

		$new_post_id = wp_insert_post( $new_post );

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		set_post_thumbnail( $new_post_id, $thumbnail_id );

		$section_terms = get_the_terms( $post_id, 'fdm-menu-section' );
		$section_ids = wp_list_pluck( $section_terms, 'term_id' );
		wp_set_post_terms( $new_post_id, $section_ids, 'fdm-menu-section' );

		$taxonomy_terms = get_the_terms( $post_id, 'fdm-menu-item-flag' );
		$taxonomy_ids = wp_list_pluck( $taxonomy_terms, 'term_id' );
		wp_set_post_terms( $new_post_id, $taxonomy_ids, 'fdm-menu-item-flag' );
		
		$prices = get_post_meta( $post_id, 'fdm_item_price' );
		foreach ( $prices as $price ) { add_post_meta( $new_post_id, 'fdm_item_price', $price ); }

		update_post_meta( $new_post_id, 'fdm_item_price_discount', get_post_meta( $post->ID, 'fdm_item_price_discount', true ) );
		
		update_post_meta( $new_post_id, 'fdm_item_source_name', get_post_meta( $post->ID, 'fdm_item_source_name', true ) );
		update_post_meta( $new_post_id, 'fdm_item_source_description', get_post_meta( $post->ID, 'fdm_item_source_description', true ) );
		update_post_meta( $new_post_id, 'fdm_item_source_address', get_post_meta( $post->ID, 'fdm_item_source_address', true ) );
		update_post_meta( $new_post_id, 'fdm_item_source_zoom', get_post_meta( $post->ID, 'fdm_item_source_zoom', true ) );
		
		update_post_meta( $new_post_id, 'fdm_item_special', get_post_meta( $post->ID, 'fdm_item_special', true ) );
		
		update_post_meta( $new_post_id, '_fdm_related_items', get_post_meta( $post->ID, '_fdm_related_items', true ) );
		
		update_post_meta( $new_post_id, '_fdm_menu_item_custom_fields', get_post_meta( $post->ID, '_fdm_menu_item_custom_fields', true ) );

		return $new_post_id;
	}
}
