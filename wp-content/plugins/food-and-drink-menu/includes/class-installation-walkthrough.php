<?php

/**
 * Class to handle everything related to the walk-through that runs on plugin activation
 */

if ( !defined( 'ABSPATH' ) )
	exit;

class fdmInstallationWalkthrough {

	public function __construct() {
		
		add_action( 'admin_menu', array($this, 'register_install_screen' ));
		add_action( 'admin_head', array($this, 'hide_install_screen_menu_item' ));
		add_action( 'admin_init', array($this, 'redirect'), 9999);

		add_action('admin_head', array($this, 'admin_enqueue'));

		add_action('wp_ajax_fdm_welcome_add_section', array($this, 'add_section'));
		add_action('wp_ajax_fdm_welcome_add_menu_item', array($this, 'add_menu_item'));
		add_action('wp_ajax_fdm_welcome_create_menu', array($this, 'add_menu'));
		add_action('wp_ajax_fdm_welcome_add_menu_page', array($this, 'add_menu_page'));
	}

	public function redirect() {

		if ( ! get_transient( 'fdm-getting-started' ) ) 
			return;

		delete_transient( 'fdm-getting-started' );

		if ( is_network_admin() || isset( $_GET['activate-multi'] ) )
			return;

		$menu_items = get_posts(array('post_type' => 'fdm-menu-item'));
		if (!empty($menu_items)) {
			set_transient('fdm-admin-install-notice', true, 5);
			return;
		}

		wp_safe_redirect( admin_url( 'index.php?page=fdm-getting-started' ) );
		exit;
	}

	public function register_install_screen() {

		add_dashboard_page(
			esc_html__( 'Five-Star Restaurant Menu - Welcome!', 'food-and-drink-menu' ),
			esc_html__( 'Five-Star Restaurant Menu - Welcome!', 'food-and-drink-menu' ),
			'manage_options',
			'fdm-getting-started',
			array($this, 'display_install_screen')
		);
	}

	public function hide_install_screen_menu_item() {

		remove_submenu_page( 'index.php', 'fdm-getting-started' );
	}

	public function add_section() {

		// Authenticate request
		if ( ! check_ajax_referer( 'fdm-getting-started', 'nonce' ) || ! current_user_can( 'edit_posts' ) ) {

			fdmHelper::admin_nopriv_ajax();
		}

		$section_name = (isset($_POST['section_name']) ? sanitize_text_field( $_POST['section_name'] ) : '');
	    $section_description = (isset($_POST['section_description']) ? sanitize_text_field( $_POST['section_description'] ) : '');
	
	    $section_term_ids = wp_insert_term( $section_name, 'fdm-menu-section', array('description' => $section_description) );
	
	    echo json_encode(array('section_name' => $section_name, 'section_id' => $section_term_ids['term_id']));
	
	    exit();
	}

	public function add_menu_item() {

		// Authenticate request
		if ( ! check_ajax_referer( 'fdm-getting-started', 'nonce' ) || ! current_user_can( 'edit_posts' ) ) {

			fdmHelper::admin_nopriv_ajax();
		}

		$menu_item_post_id = wp_insert_post(array(
	        'post_title' => isset( $_POST['item_name'] ) ? sanitize_text_field( $_POST['item_name'] ) : '',
	        'post_content' => isset( $_POST['item_description'] ) ? sanitize_text_field( $_POST['item_description'] ) : '',
	        'post_status' => 'publish',
	        'post_type' => FDM_MENUITEM_POST_TYPE
	    ));

	    update_post_meta ($menu_item_post_id, 'fdm_item_price', sanitize_text_field( $_POST['item_price'] ) );

	    if ( isset($_POST['item_image']) and $_POST['item_image'] ) {

	    	set_post_thumbnail( $menu_item_post_id, attachment_url_to_postid( $_POST['item_image'] ) );
		}
	
	    if ( isset( $_POST['item_section'] ) and $_POST['item_section'] ) {

	    	$section = get_term( sanitize_text_field( $_POST['item_section'] ), 'fdm-menu-section');
	        wp_set_post_terms($menu_item_post_id, $section->name, 'fdm-menu-section');
	    }
	
	    exit();
	}

	public function add_menu() {

		// Authenticate request
		if ( ! check_ajax_referer( 'fdm-getting-started', 'nonce' ) || ! current_user_can( 'edit_posts' ) ) {

			fdmHelper::admin_nopriv_ajax();
		}

		$menu_post_id = wp_insert_post(array(
	        'post_title' => isset( $_POST['menu_name'] ) ? sanitize_text_field( $_POST['menu_name'] ) : '',
	        'post_content' => isset( $_POST['menu_description'] ) ? sanitize_text_field( $_POST['menu_description'] ) : '',
	        'post_status' => 'publish',
	        'post_type' => FDM_MENU_POST_TYPE
	    ));

	    $sections = isset( $_POST['sections'] ) ? json_decode( sanitize_text_field( $_POST['sections'] ) ) : array();
	    
	    if ( empty( $sections ) ) {
	    	$section_objects = get_terms( array( 'hide_empty' => false, 'taxonomy' => 'fdm-menu-section' ) );

	    	$sections_string = '';

	    	foreach ( $section_objects as $section_object ) { $sections_string .= ( strlen($sections_string) ? ',' : '' ) . $section_object->term_id; }
	    }
	    else {
	    	$sections_string = implode(",", $sections);
	    }

	    update_post_meta( $menu_post_id, 'fdm_menu_column_one', $sections_string);
	
	    exit();
	}

	public function add_menu_page() {

		// Authenticate request
		if ( ! check_ajax_referer( 'fdm-getting-started', 'nonce' ) || ! current_user_can( 'edit_posts' ) ) {

			fdmHelper::admin_nopriv_ajax();
		}

		$menu_posts = get_posts( array( 'post_type' => FDM_MENU_POST_TYPE ) );
		$menu = reset($menu_posts);
	
		$post_content = $menu ? "<!-- wp:paragraph --><p> [fdm-menu id='" . $menu->ID . "'] </p><!-- /wp:paragraph -->" : '';
	
		wp_insert_post(array(
			'post_title' => isset( $_POST['menu_page_title'] ) ? sanitize_text_field( $_POST['menu_page_title'] ) : '',
			'post_content' => $post_content,
			'post_status' => 'publish',
			'post_type' => 'page'
		));
	
		exit();
	}

	function admin_enqueue() {

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'fdm-getting-started' ) {

			wp_enqueue_style( 'fdm-welcome-screen', FDM_PLUGIN_URL . '/assets/css/admin-fdm-welcome-screen.css', array(), FDM_VERSION );
		}

		wp_enqueue_media();
		
		wp_enqueue_script( 'fdm-getting-started', FDM_PLUGIN_URL . '/assets/js/admin-fdm-welcome-screen.js', array('jquery'), FDM_VERSION );
		wp_enqueue_script( 'fdm-uploader', FDM_PLUGIN_URL . '/assets/js/admin-wp-fdm-uploader.js', array('jquery'), FDM_VERSION );

		wp_localize_script(
			'fdm-getting-started',
			'fdm_getting_started',
			array(
				'nonce' => wp_create_nonce( 'fdm-getting-started' )
			)
		);
	}

	public function display_install_screen() { ?>
		<div class='fdm-welcome-screen'>
			<?php  if ( ! isset( $_GET['exclude'] ) ) { ?>
			<div class='fdm-welcome-screen-header'>
				<h1><?php _e('Welcome to the Five-Star Restaurant Menu Plugin', 'food-and-drink-menu'); ?></h1>
				<p><?php _e('Thanks for choosing the Five-Star Restaurant Menu! The following will help you get started with the setup of your menu by creating your first sections, menu items and menu page, as well as configuring a few key options.', 'food-and-drink-menu'); ?></p>
			</div>
			<?php } ?>
		
			<div class='fdm-welcome-screen-box fdm-welcome-screen-sections fdm-welcome-screen-open' data-screen='sections'>
				<h2><?php _e('1. Create Sections', 'food-and-drink-menu'); ?></h2>
				<div class='fdm-welcome-screen-box-content'>
					<p><?php _e('Sections let you organize your menu in a way that is easy for you - and your customers - to find (like "Drinks", "Appetizers", etc.).', 'food-and-drink-menu'); ?></p>
					<div class='fdm-welcome-screen-created-sections'>
						<div class='fdm-welcome-screen-add-section-name fdm-welcome-screen-box-content-divs'><label><?php _e('Section Name:', 'food-and-drink-menu'); ?></label><input type='text' /></div>
						<div class='fdm-welcome-screen-add-section-description fdm-welcome-screen-box-content-divs'><label><?php _e('Section Description:', 'food-and-drink-menu'); ?></label><textarea></textarea></div>
						<div class='fdm-welcome-screen-add-section-button'><?php _e('Add Section', 'food-and-drink-menu'); ?></div>
						<div class="fdm-welcome-clear"></div>
						<div class="fdm-welcome-screen-show-created-sections">
							<h3><?php _e('Created Sections:', 'food-and-drink-menu'); ?></h3>
							<div class="fdm-welcome-screen-show-created-sections-name"><?php _e('Name', 'food-and-drink-menu'); ?></div>
							<div class="fdm-welcome-screen-show-created-sections-description"><?php _e('Description', 'food-and-drink-menu'); ?></div>
						</div>
					</div>
					<div class='fdm-welcome-screen-next-button fdm-welcome-screen-next-button-not-top-margin' data-nextaction='menu_items'><?php _e('Next Step', 'food-and-drink-menu'); ?></div>
					<div class='clear'></div>
				</div>
			</div>
			
			<div class='fdm-welcome-screen-box fdm-welcome-screen-menu_items' data-screen='menu_items'>
				<h2><?php _e('2. Add Menu Items', 'food-and-drink-menu'); ?></h2>
				<div class='fdm-welcome-screen-box-content'>
					<div class='fdm-welcome-screen-created-menu_items'>
						<div class='fdm-welcome-screen-add-menu_item-image fdm-welcome-screen-box-content-divs'>
							<label><?php _e('Image:', 'food-and-drink-menu'); ?></label>
							<div class='fdm-welcome-screen-image-preview-container'>
								<div class='fdm-hidden fdm-welcome-screen-image-preview'>
									<img />
								</div>
								<input type='hidden' name='menu_item_image_url' />
								<input id="Welcome_Item_Image_button" class="button" type="button" value="Upload Image" />
							</div>
						</div>
						<div class='fdm-welcome-screen-add-menu_item-name fdm-welcome-screen-box-content-divs'><label><?php _e('Name:', 'food-and-drink-menu'); ?></label><input type='text' /></div>
						<div class='fdm-welcome-screen-add-menu_item-description fdm-welcome-screen-box-content-divs'><label><?php _e('Description:', 'food-and-drink-menu'); ?></label><textarea></textarea></div>
						<div class='fdm-welcome-screen-add-menu_item-section fdm-welcome-screen-box-content-divs'><label><?php _e('Section:', 'food-and-drink-menu'); ?></label><select></select></div>
						<div class='fdm-welcome-screen-add-menu_item-price fdm-welcome-screen-box-content-divs'><label><?php _e('Price:', 'food-and-drink-menu'); ?></label><input type='text' /></div>
						<div class='fdm-welcome-screen-add-menu_item-button'><?php _e('Add Item', 'food-and-drink-menu'); ?></div>
						<div class="fdm-welcome-clear"></div>
						<div class="fdm-welcome-screen-show-created-menu_items">
							<h3><?php _e('Created Items:', 'food-and-drink-menu'); ?></h3>
							<div class="fdm-welcome-screen-show-created-menu_items-image"><?php _e('Image', 'food-and-drink-menu'); ?></div>
							<div class="fdm-welcome-screen-show-created-menu_items-name"><?php _e('Name', 'food-and-drink-menu'); ?></div>
							<div class="fdm-welcome-screen-show-created-menu_items-description"><?php _e('Description', 'food-and-drink-menu'); ?></div>
							<div class="fdm-welcome-screen-show-created-menu_items-price"><?php _e('Price', 'food-and-drink-menu'); ?></div>
						</div>
					</div>
					<div class="fdm-welcome-clear"></div>
					<?php  if ( ! isset( $_GET['exclude'] ) ) { ?>
						<div class='fdm-welcome-screen-next-button' data-nextaction='create_menu'><?php _e('Next Step', 'food-and-drink-menu'); ?></div>
					<?php } else { ?>
						<div class='fdm-welcome-screen-finish-button' data-link='edit.php?post_type=fdm-menu'><?php _e('Save and Continue', 'food-and-drink-menu'); ?></div>
					<?php } ?>
					<div class='fdm-welcome-screen-previous-button' data-previousaction='sections'><?php _e('Previous Step', 'food-and-drink-menu'); ?></div>
					<div class='clear'></div>
				</div>
			</div>

		<?php  if ( ! isset( $_GET['exclude'] ) ) { ?>

			<div class='fdm-welcome-screen-box fdm-welcome-screen-create_menu' data-screen='create_menu'>
				<h2><?php _e('3. Create a Menu', 'food-and-drink-menu'); ?></h2>
				<div class='fdm-welcome-screen-box-content'>
					<p><?php _e('You can make multiple menus, but one menu with all of your sections is a great place to start.', 'food-and-drink-menu'); ?></p>
					<div class='fdm-welcome-screen-create_menu'>
						<div class='fdm-welcome-screen-add-create_menu-name fdm-welcome-screen-box-content-divs'><label><?php _e('Menu Name:', 'food-and-drink-menu'); ?></label><input type='text' /></div>
						<div class='fdm-welcome-screen-add-create_menu-sections'><h3><?php _e('Sections:', 'food-and-drink-menu'); ?></h3><br /></div>
						<div class='fdm-welcome-screen-add-create_menu-button'><?php _e('Create Menu', 'food-and-drink-menu'); ?></div>
					</div>
					<div class="fdm-welcome-clear"></div>
					<div class='fdm-welcome-screen-next-button' data-nextaction='display_menu'><?php _e('Next Step', 'food-and-drink-menu'); ?></div>
					<div class='fdm-welcome-screen-previous-button' data-previousaction='menu_items'><?php _e('Previous Step', 'food-and-drink-menu'); ?></div>
					<div class='clear'></div>
				</div>
			</div>

			<div class='fdm-welcome-screen-box fdm-welcome-screen-display_menu' data-screen='display_menu'>
				<h2><?php _e('4. Add a Menu Page', 'food-and-drink-menu'); ?></h2>
				<div class='fdm-welcome-screen-box-content'>
					<p><?php _e('You can create a dedicated menu page below, or skip this step and add your menu to a page you\'ve already created manually.', 'food-and-drink-menu'); ?></p>
					<div class='fdm-welcome-screen-menu-page'>
						<div class='fdm-welcome-screen-add-menu-page-name fdm-welcome-screen-box-content-divs'><label><?php _e('Page Title:', 'food-and-drink-menu'); ?></label><input type='text' value='Menu' /></div>
						<div class='fdm-welcome-screen-add-menu-page-button'><?php _e('Create Page', 'food-and-drink-menu'); ?></div>
					</div>
					<div class="fdm-welcome-clear"></div>
					<div class='fdm-welcome-screen-previous-button' data-previousaction='create_menu'><?php _e('Previous Step', 'food-and-drink-menu'); ?></div>
					<div class='fdm-welcome-screen-finish-button'><a href='edit.php?post_type=fdm-menu'><?php _e('Finish', 'food-and-drink-menu'); ?></a></div>
					<div class='clear'></div>
				</div>
			</div>
		
			<div class='fdm-welcome-screen-skip-container'>
				<a href='edit.php?post_type=fdm-menu'><div class='fdm-welcome-screen-skip-button'><?php _e('Skip Setup', 'food-and-drink-menu'); ?></div></a>
			</div>

		<?php } ?>

		</div>
	<?php }
}


?>