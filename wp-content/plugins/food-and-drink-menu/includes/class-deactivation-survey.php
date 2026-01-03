<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmDeactivationSurvey' ) ) {
/**
 * Class to handle plugin deactivation survey
 *
 * @since 2.0.7
 */
class fdmDeactivationSurvey {

	public function __construct() {
		add_action( 'current_screen', array( $this, 'maybe_add_survey' ) );
	}

	public function maybe_add_survey() {
		if ( in_array( get_current_screen()->id, array( 'plugins', 'plugins-network' ), true) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_deactivation_scripts') );
			add_action( 'admin_footer', array( $this, 'add_deactivation_html') );
		}
	}

	public function enqueue_deactivation_scripts() {
		wp_enqueue_style( 'fdm-deactivation-css', FDM_PLUGIN_URL . '/assets/css/plugin-deactivation.css', array(), FDM_VERSION );
		wp_enqueue_script( 'fdm-deactivation-js', FDM_PLUGIN_URL . '/assets/js/plugin-deactivation.js', array( 'jquery' ), FDM_VERSION);

		wp_localize_script( 'fdm-deactivation-js', 'fdm_deactivation_data', array( 'site_url' => site_url() ) );
	}

	public function add_deactivation_html() {

		$install_time = get_option( 'fdm-installation-time' );

		$options = array(
			1 => array(
				'title'   => esc_html__( 'I no longer need the plugin', 'food-and-drink-menu' ),
			),
			2 => array(
				'title'   => esc_html__( 'I\'m switching to a different plugin', 'food-and-drink-menu' ),
				'details' => esc_html__( 'Please share which plugin', 'food-and-drink-menu' ),
			),
			3 => array(
				'title'   => esc_html__( 'I couldn\'t get the plugin to work', 'food-and-drink-menu' ),
				'details' => esc_html__( 'Please share what wasn\'t working', 'food-and-drink-menu' ),
			),
			4 => array(
				'title'   => esc_html__( 'It\'s a temporary deactivation', 'food-and-drink-menu' ),
			),
			5 => array(
				'title'   => esc_html__( 'Other', 'food-and-drink-menu' ),
				'details' => esc_html__( 'Please share the reason', 'food-and-drink-menu' ),
			),
		);
		?>
		<div class="fdm-deactivate-survey-modal" id="fdm-deactivate-survey-restaurant-menu">
			<div class="fdm-deactivate-survey-wrap">
				<form class="fdm-deactivate-survey" method="post" data-installtime="<?php echo $install_time; ?>">
					<span class="fdm-deactivate-survey-title"><span class="dashicons dashicons-testimonial"></span><?php echo ' ' . __( 'Quick Feedback', 'food-and-drink-menu' ); ?></span>
					<span class="fdm-deactivate-survey-desc"><?php echo __('If you have a moment, please share why you are deactivating Five-Star Restaurant Menu:', 'food-and-drink-menu' ); ?></span>
					<div class="fdm-deactivate-survey-options">
						<?php foreach ( $options as $id => $option ) : ?>
							<div class="fdm-deactivate-survey-option">
								<label for="fdm-deactivate-survey-option-restaurant-menu-<?php echo $id; ?>" class="fdm-deactivate-survey-option-label">
									<input id="fdm-deactivate-survey-option-restaurant-menu-<?php echo $id; ?>" class="fdm-deactivate-survey-option-input" type="radio" name="code" value="<?php echo $id; ?>" />
									<span class="fdm-deactivate-survey-option-reason"><?php echo $option['title']; ?></span>
								</label>
								<?php if ( ! empty( $option['details'] ) ) : ?>
									<input class="fdm-deactivate-survey-option-details" type="text" placeholder="<?php echo $option['details']; ?>" />
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="fdm-deactivate-survey-footer">
						<button type="submit" class="fdm-deactivate-survey-submit button button-primary button-large"><?php _e('Submit and Deactivate', 'food-and-drink-menu' ); ?></button>
						<a href="#" class="fdm-deactivate-survey-deactivate"><?php _e('Skip and Deactivate', 'food-and-drink-menu' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}

}