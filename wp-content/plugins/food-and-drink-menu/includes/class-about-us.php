<?php
/**
 * Class to create the 'About Us' submenu
 */

if ( !defined( 'ABSPATH' ) )
	exit;

if ( !class_exists( 'fdmAboutUs' ) ) {
class fdmAboutUs {

	public function __construct() {

		add_action( 'wp_ajax_fdm_send_feature_suggestion', array( $this, 'send_feature_suggestion' ) );

		add_action( 'admin_menu', array( $this, 'register_menu_screen' ), 11 );
	}

	/**
	 * Adds About Us submenu page
	 * @since 2.4.0
	 */
	public function register_menu_screen() {
		global $fdm_controller;

		add_submenu_page(
			'edit.php?post_type=fdm-menu', 
			esc_html__( 'About Us', 'food-and-drink-menu' ),
			esc_html__( 'About Us', 'food-and-drink-menu' ),
			'manage_options', 
			'fdm-about-us',
			array( $this, 'display_admin_screen' )
		);
	}

	/**
	 * Displays the About Us page
	 * @since 2.4.0
	 */
	public function display_admin_screen() { ?>

		<div class='fdm-about-us-logo'>
			<img src='<?php echo plugins_url( "../assets/img/fsplogo.png", __FILE__ ); ?>'>
		</div>

		<div class='fdm-about-us-tabs'>

			<ul id='fdm-about-us-tabs-menu'>

				<li class='fdm-about-us-tab-menu-item fdm-tab-selected' data-tab='who_we_are'>
					<?php _e( 'Who We Are', 'food-and-drink-menu' ); ?>
				</li>

				<li class='fdm-about-us-tab-menu-item' data-tab='lite_vs_premium'>
					<?php _e( 'Lite vs. Premium vs. Ultimate', 'food-and-drink-menu' ); ?>
				</li>

				<li class='fdm-about-us-tab-menu-item' data-tab='getting_started'>
					<?php _e( 'Getting Started', 'food-and-drink-menu' ); ?>
				</li>

				<li class='fdm-about-us-tab-menu-item' data-tab='suggest_feature'>
					<?php _e( 'Suggest a Feature', 'food-and-drink-menu' ); ?>
				</li>

			</ul>

			<div class='fdm-about-us-tab' data-tab='who_we_are'>

				<p>
					<strong>Five Star Plugins focuses on creating high-quality, easy-to-use WordPress plugins centered around the restaurant, hospitality and business industries.</strong> With over <strong>50,000 active users worldwide</strong>, our plugins bring a great amount of value to many websites and business owners every day, by offering them solutions that are simple to implement and that provide powerful functionality necessary for their operations. Our <a href='https://www.fivestarplugins.com/plugins/five-star-food-and-drink-menu/?utm_source=fdm_admin_about_us' target='_blank'>WordPress restaurant reservations plugin</a> and <a href='https://www.fivestarplugins.com/plugins/five-star-restaurant-menu/?utm_source=fdm_admin_about_us' target='_blank'>WordPress restaurant menu plugin</a> are both rich in features, responsive and highly customizable. Our <a href='https://www.fivestarplugins.com/plugins/five-star-food-and-drink-menu/?utm_source=fdm_admin_about_us' target='_blank'>business profile WordPress plugin</a> and <a href='https://www.fivestarplugins.com/plugins/five-star-restaurant-reviews/?utm_source=fdm_admin_about_us' target='_blank'>WordPress restaurant reviews plugin</a> allow you to extend the functionality of your site and offer a full WordPress restaurant solution.
				</p>

				<p>
					<strong>On top of this, we pride ourselves on offering great and timely support and customer service.</strong>
				</p>

				<p>
					Our team is made up of developers, graphic designers, marketing associates and support specialists. Our partnership with <a href='https://www.etoilewebdesign.com/?utm_source=fdm_admin_about_us' target='_blank'>Etoile Web Design</a> gives us access to their fantastic support team and allows us to offer unparalleled customer service and technical support via multiple channels.
				</p>

			</div>

			<div class='fdm-about-us-tab fdm-hidden' data-tab='lite_vs_premium'>

				<p><?php _e( 'The premium version includes several features that let you extend the functionality of the plugin and offer a great experience to your customers, including multiple layout options, custom fields, sorting and filtering options, and much more!', 'food-and-drink-menu' ); ?></p>

				<p><?php _e( 'The ultimate version allows you to add a full food ordering system to your site. No more paying commissions to third-party services. Customers can add menu items to a cart, check out and pay directly on your site.', 'food-and-drink-menu' ); ?></p>

				<p><?php _e( 'The ultimate version also syncs with the <strong>Five Star Restaurant Manager mobile app</strong>, so you can manage your orders directly from your phone or tablet.', 'food-and-drink-menu' ); ?></p>

				<p><em><?php _e( 'The following table provides a comparison of the lite, premium and ultimate versions.', 'food-and-drink-menu' ); ?></em></p>

				<div class='fdm-about-us-premium-table'>
					<div class='fdm-about-us-premium-table-head'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Feature', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Lite Version', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Premium Version', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Ultimate Version', 'food-and-drink-menu' ); ?></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Unlimited restaurant menus and menu items', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Responsive layout that looks great on all devices', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Gutenberg blocks, patterns and shortcodes to display menus and items', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Optional sidebar to display sections, for quick navigation.', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Full Menu structured data automatically integrated', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Create a QR code that links to your online menu', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'WPML compatible', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Multiple prices for a single item', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Advanced template system for layout customization', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Multiple layout options', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Custom fields for menu items', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Menu sorting, filtering and search options', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Icons to indicate dietary and ethical requirements', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Badges for item features, specials and sales', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Assign special or discount pricing to items', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Google maps to show off local suppliers or ethical sourcing programs', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Show related menu items', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Advanced styling options', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Advanced labelling options', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Enable online ordering for your menu', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Accept or require payment for food orders', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Configure unlimited order status notifications', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Send SMS updates to customers', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
					<div class='fdm-about-us-premium-table-body'>
						<div class='fdm-about-us-premium-table-cell'><?php _e( 'Syncs with Five Star Restaurant Manager mobile app to manage orders', 'food-and-drink-menu' ); ?></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'></div>
						<div class='fdm-about-us-premium-table-cell'><img src="<?php echo plugins_url( '../assets/img/dash-asset-checkmark.png', __FILE__ ); ?>"></div>
					</div>
				</div>

				<div class='fdm-about-us-tab-buttons'>
					<?php printf( __( '<a href="%s" target="_blank" class="fdm-about-us-tab-button fdm-about-us-tab-button-purchase-alternate">Buy Premium Version</a>', 'food-and-drink-menu' ), 'https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1&utm_source=admin_about_us' ); ?>
					<?php printf( __( '<a href="%s" target="_blank" class="fdm-about-us-tab-button fdm-about-us-tab-button-purchase">Buy Ultimate Version</a>', 'food-and-drink-menu' ), 'https://www.fivestarplugins.com/license-payment/?Selected=FDMU&Quantity=12&utm_source=admin_about_us' ); ?>
				</div>
				
			</div>

			<div class='fdm-about-us-tab fdm-hidden' data-tab='getting_started'>

				<p><?php _e( 'The walk-though that ran when you first activated the plugin offers a quick way to get started with setting it up. If you would like to run through it again, just click the button below', 'food-and-drink-menu' ); ?></p>

				<?php printf( __( '<a href="%s" class="fdm-about-us-tab-button fdm-about-us-tab-button-walkthrough">Re-Run Walk-Through</a>', 'food-and-drink-menu' ), admin_url( '?page=fdm-getting-started' ) ); ?>

				<p><?php _e( 'We also have a series of video tutorials that cover the available settings as well as key features of the plugin.', 'food-and-drink-menu' ); ?></p>

				<?php printf( __( '<a href="%s" target="_blank" class="fdm-about-us-tab-button fdm-about-us-tab-button-youtube">YouTube Playlist</a>', 'food-and-drink-menu' ), 'https://www.youtube.com/playlist?list=PLEndQUuhlvSqy2KjpKfGpd-vAUJLfdYMI' ); ?>

				
			</div>

			<div class='fdm-about-us-tab fdm-hidden' data-tab='suggest_feature'>

				<div class='fdm-about-us-feature-suggestion'>

					<p><?php _e( 'You can use the form below to let us know about a feature suggestion you might have.', 'food-and-drink-menu' ); ?></p>

					<textarea placeholder="<?php _e( 'Please describe your feature idea...', 'food-and-drink-menu' ); ?>"></textarea>
					
					<br>
					
					<input type="email" name="feature_suggestion_email_address" placeholder="<?php _e( 'Email Address', 'food-and-drink-menu' ); ?>">
				
				</div>
				
				<div class='fdm-about-us-tab-button fdm-about-us-send-feature-suggestion'>Send Feature Suggestion</div>
				
			</div>

		</div>

	<?php }

	/**
	 * Sends the feature suggestions submitted via the About Us page
	 * @since 2.4.0
	 */
	public function send_feature_suggestion() {
		global $fdm_controller;
		
		if (
			! check_ajax_referer( 'fdm-admin', 'nonce' ) 
			|| 
			! current_user_can( 'manage_options' )
		) {
			fdmHelper::admin_nopriv_ajax();
		}

		$headers = 'Content-type: text/html;charset=utf-8' . "\r\n";  
	    $feedback = sanitize_text_field( $_POST['feature_suggestion'] );
		$feedback .= '<br /><br />Email Address: ';
	  	$feedback .=  sanitize_email( $_POST['email_address'] );
	
	  	wp_mail( 'contact@fivestarplugins.com', 'FDM Feature Suggestion', $feedback, $headers );
	
	  	die();
	} 

}
} // endif;