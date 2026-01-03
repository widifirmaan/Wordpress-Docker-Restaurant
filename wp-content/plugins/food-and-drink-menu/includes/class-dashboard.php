<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmDashboard' ) ) {
/**
 * Class to handle plugin permissions
 *
 * @since 2.0.0
 */
class fdmDashboard {

	public $message;
	public $status = true;

	private $plugin_permissions;
	private $permission_level;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_dashboard_to_menu' ), 99 );

		if ( isset($_POST['wt_restart']) ) { add_action( 'init', array( $this, 'rerun_walkthrough' ) ); }

		add_action( 'wp_ajax_fdm_hide_upgrade_box', array($this, 'hide_upgrade_box') );
		add_action( 'wp_ajax_fdm_display_upgrade_box', array($this, 'display_upgrade_box') );
	}

	public function add_dashboard_to_menu() {
		global $menu, $submenu;

		add_submenu_page( 
			'edit.php?post_type=fdm-menu', 
			'Dashboard', 
			'Dashboard', 
			'manage_options', 
			'fdm-dashboard', 
			array($this, 'display_dashboard_screen') 
		);

		//Find the dashboard page and move it to the top of the submenu
		if ( ! isset( $submenu['edit.php?post_type=fdm-menu'] ) or ! is_array( $submenu['edit.php?post_type=fdm-menu'] ) ) { return; }

		foreach ( $submenu['edit.php?post_type=fdm-menu'] as $key => $sub_item ) {
			if ( $sub_item[0] == 'Dashboard' ) { $dashboard_key = $key; }
		}
		
		if ( isset( $dashboard_key ) ) {
			$submenu['edit.php?post_type=fdm-menu'][1] = $submenu['edit.php?post_type=fdm-menu'][$dashboard_key];
			unset($submenu['edit.php?post_type=fdm-menu'][$dashboard_key]);
		}
		ksort($submenu['edit.php?post_type=fdm-menu']);

		//Re-assign the submenu menu in preparation for changing the main menu link
		$submenu['edit.php?post_type=fdm-menu&page=fdm-dashboard'] = $submenu['edit.php?post_type=fdm-menu'];

		//Re-assign the menu page link to the dashboard submenu-page
		foreach ( $menu as $key => $menu_item ) {
			if ( $menu_item[2] == 'edit.php?post_type=fdm-menu' ) { $menu[$key][2] = 'edit.php?post_type=fdm-menu&page=fdm-dashboard'; }
		}

		//Change all of the submenu links so that they contain full URLs
		foreach ( $submenu['edit.php?post_type=fdm-menu&page=fdm-dashboard'] as $key => $sub_item ) {
			if ( strpos( $sub_item[2], '?' ) === false ) { $submenu['edit.php?post_type=fdm-menu&page=fdm-dashboard'][$key][2] = 'edit.php?post_type=fdm-menu&page=' . $submenu['edit.php?post_type=fdm-menu&page=fdm-dashboard'][$key][2]; }
		}
	}

	function rerun_walkthrough() {
		set_transient('fdm-getting-started', true, 30);
	}

	public function display_dashboard_screen() { 
		global $fdm_controller;

		$permission = $fdm_controller->permissions->check_permission( 'styling' );
		$ultimate = $fdm_controller->permissions->check_permission( 'ordering' );

		$args = array(
			'post_type' => FDM_MENU_POST_TYPE,
			'posts_per_page' => 10
		);
		
		$menu_query = new WP_Query($args);
		$menus = $menu_query->get_posts();

		if ( sizeOf($menus) == 0 ) {
			$args = array(
				'post_type' => FDM_MENUITEM_POST_TYPE,
				'posts_per_page' => 10
			);
		
			$menu_item_query = new WP_Query($args);
			$menu_items = $menu_query->get_posts();
		}

		?>
		<div id="fdm-dashboard-content-area">

			<div id="fdm-dashboard-content-left">

				<?php if ( sizeOf( $menus ) == 0 and sizeOf( $menu_items ) == 0 ) { ?>
					<div class="fdm-dashboard-new-widget-box fsp-widget-box-full fdm-admin-closeable-widget-box" id="fdm-dashboard-restart-walkthrough-widget-box">
						<div class="fdm-dashboard-new-widget-box-top"><?php _e('Restart Walk-Through', 'food-and-drink-menu'); ?><span id="fdm-dashboard-restart-walkthrough-down-caret">&nbsp;&nbsp;&#9660;</span><span id="fdm-dashboard-restart-walkthrough-up-caret">&nbsp;&nbsp;&#9650;</span></div>
						<div class="fdm-dashboard-new-widget-box-bottom">
								<div class='fdm-need-help-box'>
								<div class='fdm-need-help-text'><?php _e('Click the button below to restart the plugin walk-through', 'food-and-drink-menu'); ?></div>
								<form method="post" action="edit.php?post_type=fdm-menu&page=fdm-dashboard">
									<input class="fdm-need-help-button" name="wt_restart" type="submit" value="<?php _e('Restart', 'food-and-drink-menu'); ?>" />
								</form>								
							</div>
						</div>
					</div>
				<?php } ?>

				<?php if ( ! $permission or ! $ultimate or get_option("FDM_Trial_Happening") == "Yes" or get_option("FDMU_Trial_Happening") == "Yes" ) {
					$premium_info = '<div class="fdm-dashboard-new-widget-box fsp-widget-box-full">';
					$premium_info .= '<div class="fdm-dashboard-new-widget-box-top">';
					$premium_info .= sprintf( __( '<a href="%s" target="_blank">Visit our website</a> to learn how to upgrade to premium.', 'food-and-drink-menu' ), 'https://www.fivestarplugins.com/premium-upgrade-instructions/?utm_source=fdm_dashboard&utm_content=visit_our_site_link' );
					$premium_info .= '</div>';
					$premium_info .= '</div>';

					$premium_info = apply_filters( 'fsp_dashboard_top', $premium_info, 'FDM', 'https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1' );

					if ( $permission and get_option("FDMU_Trial_Happening") != "Yes" ) {
						$ultimate_premium_notice = '<div class="fdm-ultimate-notification">';
						$ultimate_premium_notice .= __( 'Thanks for being a premium user! <strong>If you\'re looking to upgrade to our ultimate version, enter your new product key below.</strong>', 'food-and-drink-menu'  );
						$ultimate_premium_notice .= '</div>';
						$ultimate_premium_notice .= '<div class="fdm-ultimate-upgrade-dismiss"></div>';

						$premium_info = str_replace('<div class="fsp-premium-helper-dashboard-new-widget-box-top">', '<div class="fsp-premium-helper-dashboard-new-widget-box-top">' . $ultimate_premium_notice, $premium_info);
					}

					echo $premium_info;
				} ?>
		
				<div class="fdm-dashboard-new-widget-box fsp-widget-box-full" id="fdm-dashboard-support-widget-box">
					<div class="fdm-dashboard-new-widget-box-top">Get Support<span id="fdm-dash-mobile-support-down-caret">&nbsp;&nbsp;&#9660;</span><span id="fdm-dash-mobile-support-up-caret">&nbsp;&nbsp;&#9650;</span></div>
					<div class="fdm-dashboard-new-widget-box-bottom">
						<ul class="fdm-dashboard-support-widgets">
							<li>
								<a href="https://www.youtube.com/watch?v=C_ctjUDaY14&list=PLEndQUuhlvSqy2KjpKfGpd-vAUJLfdYMI" target="_blank">
									<img src="<?php echo plugins_url( '../assets/img/ewd-support-icon-youtube.png', __FILE__ ); ?>">
									<div class="fdm-dashboard-support-widgets-text">YouTube Tutorials</div>
								</a>
							</li>
							<li>
								<a href="https://wordpress.org/plugins/food-and-drink-menu/#faq" target="_blank">
									<img src="<?php echo plugins_url( '../assets/img/ewd-support-icon-faqs.png', __FILE__ ); ?>">
									<div class="fdm-dashboard-support-widgets-text">Plugin FAQs</div>
								</a>
							</li>
							<li>
								<a href="http://doc.fivestarplugins.com/plugins/food-and-drink-menu/?utm_source=fdm_dashboard&utm_content=icons_documentation" target="_blank">
									<img src="<?php echo plugins_url( '../assets/img/ewd-support-icon-documentation.png', __FILE__ ); ?>">
									<div class="fdm-dashboard-support-widgets-text">Documentation</div>
								</a>
							</li>
							<li>
								<a href="https://www.fivestarplugins.com/support-center/?utm_source=fdm_dashboard&utm_content=icons_get_support" target="_blank">
									<img src="<?php echo plugins_url( '../assets/img/ewd-support-icon-forum.png', __FILE__ ); ?>">
									<div class="fdm-dashboard-support-widgets-text">Get Support</div>
								</a>
							</li>
						</ul>
					</div>
				</div>
		
				<div class="fdm-dashboard-new-widget-box fsp-widget-box-full" id="fdm-dashboard-optional-table">
					<div class="fdm-dashboard-new-widget-box-top">Menus Summary<span id="fdm-dash-optional-table-down-caret">&nbsp;&nbsp;&#9660;</span><span id="fdm-dash-optional-table-up-caret">&nbsp;&nbsp;&#9650;</span></div>
					<div class="fdm-dashboard-new-widget-box-bottom">
						<table class='fdm-overview-table wp-list-table widefat fixed striped posts'>
							<thead>
								<tr>
									<th><?php _e("Title", 'EWD_ABCO'); ?></th>
									<th><?php _e("Sections", 'EWD_ABCO'); ?></th>
									<th><?php _e("Date", 'EWD_ABCO'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
									if (sizeOf($menus) == 0) {echo "<tr><td colspan='3'>" . __("No menus to display yet. Create an menu for it to be displayed here.", 'food-and-drink-menu') . "</td></tr>";}
									else {
										foreach ($menus as $menu) { 
											$post_meta = get_post_meta( $menu->ID );

											$col1 = !empty( $post_meta['fdm_menu_column_one'] ) ? array_filter( explode( ',', $post_meta['fdm_menu_column_one'][0] ) ) : array();
											$col2 = !empty( $post_meta['fdm_menu_column_two'] ) ? array_filter( explode( ',', $post_meta['fdm_menu_column_two'][0] ) ) : array();

										?>

											<tr>
												<td><a href='post.php?post=<?php echo $menu->ID;?>&action=edit'><?php echo $menu->post_title; ?></a></td>
												<td><?php 
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
										
													<?php endif;?>
												</td>
												<td><?php echo $menu->post_date; ?></td>
											</tr>
										<?php }
									}
								?>
							</tbody>
						</table>
					</div>
				</div>
		
				<?php /*<div class="fdm-dashboard-new-widget-box fsp-widget-box-full">
					<div class="fdm-dashboard-new-widget-box-top">What People Are Saying</div>
					<div class="fdm-dashboard-new-widget-box-bottom">
						<ul class="fdm-dashboard-testimonials">
							<?php $randomTestimonial = rand(0,2);
							if($randomTestimonial == 0){ ?>
								<li id="fdm-dashboard-testimonial-one">
									<img src="<?php echo plugins_url( '../assets/img/dash-asset-stars.png', __FILE__ ); ?>">
									<div class="fdm-dashboard-testimonial-title">"Awesome. Just Awesome."</div>
									<div class="fdm-dashboard-testimonial-author">- @shizart</div>
									<div class="fdm-dashboard-testimonial-text">Thanks for this very well-made plugin. This works so well out of the box, I barely had to do ANYTHING to create an amazing FAQ accordion display... <a href="https://wordpress.org/support/topic/awesome-just-awesome-11/" target="_blank">read more</a></div>
								</li>
							<?php }
							if($randomTestimonial == 1){ ?>
								<li id="fdm-dashboard-testimonial-two">
									<img src="<?php echo plugins_url( '../assets/img/dash-asset-stars.png', __FILE__ ); ?>">
									<div class="fdm-dashboard-testimonial-title">"Absolutely perfect with great support"</div>
									<div class="fdm-dashboard-testimonial-author">- @isaac85</div>
									<div class="fdm-dashboard-testimonial-text">I tried several different FAQ plugins and this is by far the prettiest and easiest to use... <a href="https://wordpress.org/support/topic/absolutely-perfect-with-great-support/" target="_blank">read more</a></div>
								</li>
							<?php }
							if($randomTestimonial == 2){ ?>
								<li id="fdm-dashboard-testimonial-three">
									<img src="<?php echo plugins_url( '../assets/img/dash-asset-stars.png', __FILE__ ); ?>">
									<div class="fdm-dashboard-testimonial-title">"Perfect FAQ Plugin"</div>
									<div class="fdm-dashboard-testimonial-author">- @muti-wp</div>
									<div class="fdm-dashboard-testimonial-text">Works great! Easy to configure and to use. Thanks! <a href="https://wordpress.org/support/topic/perfect-faq-plugin/" target="_blank">read more</a></div>
								</li>
							<?php } ?>
						</ul>
					</div>
				</div> */ ?>
		
				<?php /* if($hideReview != 'Yes' and $Ask_Review_Date < time()){ ?>
					<div class="fdm-dashboard-new-widget-box fsp-widget-box-one-third">
						<div class="fdm-dashboard-new-widget-box-top">Leave a review</div>
						<div class="fdm-dashboard-new-widget-box-bottom">
							<div class="fdm-dashboard-review-ask">
								<img src="<?php echo plugins_url( '../assets/img/dash-asset-stars.png', __FILE__ ); ?>">
								<div class="fdm-dashboard-review-ask-text">If you enjoy this plugin and have a minute, please consider leaving a 5-star review. Thank you!</div>
								<a href="https://wordpress.org/plugins/ultimate-faqs/#reviews" class="fdm-dashboard-review-ask-button">LEAVE A REVIEW</a>
								<form action="admin.php?page=EWD-UFAQ-Options" method="post">
									<input type="hidden" name="hide_ufaq_review_box_hidden" value="Yes">
									<input type="submit" name="hide_ufaq_review_box_submit" class="fdm-dashboard-review-ask-dismiss" value="I've already left a review">
								</form>
							</div>
						</div>
					</div>
				<?php } */ ?>
		
				<?php if ( ! $permission or get_option("FDM_Trial_Happening") == "Yes" or get_option("FDMU_Trial_Happening") == "Yes" ) { ?>
					<div class="fdm-dashboard-new-widget-box fsp-widget-box-full" id="fdm-dashboard-guarantee-widget-box">
						<div class="fdm-dashboard-new-widget-box-top">
							<div class="fdm-dashboard-guarantee">
								<div class="fdm-dashboard-guarantee-title">14-Day 100% Money-Back Guarantee</div>
								<div class="fdm-dashboard-guarantee-text">If you're not 100% satisfied with the premium version of our plugin - no problem. You have 14 days to receive a FULL REFUND. We're certain you won't need it, though.</div>
							</div>
						</div>
					</div>
				<?php } ?>
		
			</div> <!-- left -->
		
			<div id="fdm-dashboard-content-right">
		
				<?php if ( ! $permission or get_option("FDM_Trial_Happening") == "Yes" or get_option("FDMU_Trial_Happening") == "Yes" ) { ?>
					<div class="fdm-dashboard-new-widget-box fsp-widget-box-full" id="fdm-dashboard-get-premium-widget-box">
						<div class="fdm-dashboard-new-widget-box-top">Get Premium</div>
						
						<?php if ( get_option( "RTB_Trial_Happening" ) == "Yes" ) { do_action( 'fsp_trial_happening', 'FDM' ); } ?>
						<?php if ( get_option( "RTU_Trial_Happening" ) == "Yes" ) { do_action( 'fsp_trial_happening', 'FDMU' ); } ?>

						<div class="fdm-dashboard-new-widget-box-bottom">
							<div class="fdm-dashboard-get-premium-widget-features-title"<?php echo ( ( get_option("FDM_Trial_Happening") == "Yes" or get_option("FDMU_Trial_Happening") == "Yes" ) ? "style='padding-top: 20px;'" : ""); ?>>GET FULL ACCESS WITH OUR PREMIUM VERSION AND GET:</div>
							
							<ul class="fdm-dashboard-get-premium-widget-features">
								<li>Multiple Layouts</li>
								<li>Custom Fields</li>
								<li>Dietary Icons</li>
								<li>Advanced Styling Options</li>
								<li>+ More</li>
							</ul>

							<a href="https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1&utm_source=fdm_dashboard&utm_content=sidebar_upgraqde" class="fdm-dashboard-get-premium-widget-button" target="_blank">UPGRADE NOW</a>
							
							<?php if ( ! get_option("FDM_Trial_Happening") and ! get_option( "FDMU_Trial_Happening" ) ) { 
								$trial_info = sprintf( __( '<a href="%s" target="_blank">Visit our website</a> to learn how to get a free 7-day trial of the premium plugin.'), 'https://www.fivestarplugins.com/premium-upgrade-instructions/?utm_source=fdm_dashboard&utm_content=sidebar_visit_our_site_link' );
								
								$version_select_modal = '<div class="fdm-trial-version-select-modal-background fdm-hidden"></div>';
								$version_select_modal .= '<div class="fdm-trial-version-select-modal fdm-hidden">';
								$version_select_modal .= '<div class="fdm-trial-version-select-modal-title">' . __( 'Select version to trial', 'food-and-drink-menu' ) . '</div>';
								$version_select_modal .= '<div class="fdm-trial-version-select-modal-option"><input type="radio" value="premium" name="fdm-trial-version" checked /> ' . __( 'Premium', 'food-and-drink-menu' ) . '</div>';
								$version_select_modal .= '<div class="fdm-trial-version-select-modal-option"><input type="radio" value="ultimate" name="fdm-trial-version" /> ' . __( 'Ultimate', 'food-and-drink-menu' ) . '</div>';
								$version_select_modal .= '<div class="fdm-trial-version-select-modal-explanation">' . __( 'SMS messaging will not work in the ultimate version trial.', 'food-and-drink-menu' ) . '</div>';
								$version_select_modal .= '<div class="fdm-trial-version-select-modal-submit">' . __( 'Select', 'food-and-drink-menu' ) . '</div>';
								$version_select_modal .= '</div>';

								$trial_info = apply_filters( 'fsp_trial_button', $trial_info, 'FDM' );

								$trial_info = str_replace( '</form>', '</form>' . $version_select_modal, $trial_info );

								echo $trial_info;
							} ?>
				</div>
					</div>
				<?php } ?>

				<div class="fdm-dashboard-new-widget-box fsp-widget-box-full">
					<div class="fdm-dashboard-new-widget-box-top">Other Plugins by Five Star</div>
					<div class="fdm-dashboard-new-widget-box-bottom">
						<ul class="fdm-dashboard-other-plugins">
							<li>
								<a href="https://wordpress.org/plugins/restaurant-reservations/" target="_blank"><img src="<?php echo plugins_url( '../assets/img/fdm-icon.png', __FILE__ ); ?>"></a>
								<div class="fdm-dashboard-other-plugins-text">
									<div class="fdm-dashboard-other-plugins-title">Restaurant Reservations</div>
									<div class="fdm-dashboard-other-plugins-blurb">Quickly set up and display a responsive booking form on your site</div>
								</div>
							</li>
							<li>
								<a href="https://wordpress.org/plugins/business-profile/" target="_blank"><img src="<?php echo plugins_url( '../assets/img/bpfwp-icon.png', __FILE__ ); ?>"></a>
								<div class="fdm-dashboard-other-plugins-text">
									<div class="fdm-dashboard-other-plugins-title">Business Profile and Schema</div>
									<div class="fdm-dashboard-other-plugins-blurb">Easily add schema strutured data to any page on your site, and also create a contact card</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
		
			</div> <!-- right -->	
		
		</div> <!-- fdm-dashboard-content-area -->
		
		<?php if ( ! $permission or get_option("FDM_Trial_Happening") == "Yes" ) { ?>
			<div id="fdm-dashboard-new-footer-one">
				<div class="fdm-dashboard-new-footer-one-inside">
					<div class="fdm-dashboard-new-footer-one-left">
						<div class="fdm-dashboard-new-footer-one-title">What's Included in Our Premium Version?</div>
						<ul class="fdm-dashboard-new-footer-one-benefits">
							<li>Advanced Menu Layouts</li>
							<li>Custom Menu Fields</li>
							<li>Sorting and Filtering</li>
							<li>Dietary Icons</li>
							<li>Featured Item Flag</li>
							<li>Special/Discount Pricing</li>
							<li>Google Map Integration</li>
							<li>Free Lifetime Updates</li>
							<li>Advanced Styling Options</li>
						</ul>
					</div>
					<div class="fdm-dashboard-new-footer-one-buttons">
						<a class="fdm-dashboard-new-upgrade-button" href="https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1&utm_source=fdm_dashboard&utm_content=footer_upgrade" target="_blank">UPGRADE NOW</a>
					</div>
				</div>
			</div> <!-- fdm-dashboard-new-footer-one -->
		<?php } ?>	
		<div id="fdm-dashboard-new-footer-two">
			<div class="fdm-dashboard-new-footer-two-inside">
				<img src="<?php echo plugins_url( '../assets/img/fivestartextlogowithstar.png', __FILE__ ); ?>" class="fdm-dashboard-new-footer-two-icon">
				<div class="fdm-dashboard-new-footer-two-blurb">
					At Five Star Plugins, we build powerful, easy-to-use WordPress plugins with a focus on the restaurant, hospitality and business industries. With a modern, responsive look and a highly-customizable feature set, Five Star Plugins can be used as out-of-the-box solutions and can also be adapted to your specific requirements.
				</div>
				<ul class="fdm-dashboard-new-footer-two-menu">
					<li>SOCIAL</li>
					<li><a href="https://www.facebook.com/fivestarplugins/" target="_blank">Facebook</a></li>
					<li><a href="https://twitter.com/fivestarplugins" target="_blank">Twitter</a></li>
					<li><a href="https://www.fivestarplugins.com/category/blog/?utm_source=fdm_dashboard&utm_content=footer_blog" target="_blank">Blog</a></li>
				</ul>
				<ul class="fdm-dashboard-new-footer-two-menu">
					<li>SUPPORT</li>
					<li><a href="https://www.youtube.com/watch?v=C_ctjUDaY14&list=PLEndQUuhlvSqy2KjpKfGpd-vAUJLfdYMI" target="_blank">YouTube Tutorials</a></li>
					<li><a href="http://doc.fivestarplugins.com/plugins/food-and-drink-menu/?utm_source=fdm_dashboard&utm_content=footer_documentation" target="_blank">Documentation</a></li>
					<li><a href="https://www.fivestarplugins.com/support-center/?utm_source=fdm_dashboard&utm_content=footer_get_support" target="_blank">Get Support</a></li>
					<li><a href="https://wordpress.org/plugins/food-and-drink-menu/#faq" target="_blank">FAQs</a></li>
				</ul>
			</div>
		</div> <!-- fdm-dashboard-new-footer-two -->
		
	<?php }

	public function get_term_from_array($terms, $term_id) {
		foreach ($terms as $term) {if ($term->term_id == $term_id) {return $term;}}

		return array();
	}

	public function display_notice() {
		if ( $this->status ) {
			echo "<div class='updated'><p>" . $this->message . "</p></div>";
		}
		else {
			echo "<div class='error'><p>" . $this->message . "</p></div>";
		}
	}

	public function hide_upgrade_box() {

		if ( !check_ajax_referer( 'fdm-admin', 'nonce' ) ) {
			rtbHelper::admin_nopriv_ajax();
		}

		update_option( 'fdm-hide-upgrade-box', true );
	}

	public function display_upgrade_box() {

		if ( !check_ajax_referer( 'fdm-admin', 'nonce' ) ) {
			rtbHelper::admin_nopriv_ajax();
		}

		update_option( 'fdm-hide-upgrade-box', false );
	}
}
} // endif
