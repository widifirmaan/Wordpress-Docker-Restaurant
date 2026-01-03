<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmReviewAsk' ) ) {
/**
 * Class to handle plugin review ask
 *
 * @since 2.0.7
 */
class fdmReviewAsk {

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_add_review_ask' ) );

		add_action( 'wp_ajax_fdm_hide_review_ask', array( $this, 'hide_review_ask' ) );
		add_action( 'wp_ajax_fdm_send_feedback', array( $this, 'send_feedback' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_review_ask_scripts') );
	}

	public function maybe_add_review_ask() {
		$ask_review_time = get_option( 'fdm-review-ask-time' );

		$install_time = get_option( 'fdm-installation-time' );
		if ( ! $install_time ) { update_option( 'fdm-installation-time', time() ); }

		$ask_review_time = $ask_review_time != '' ? $ask_review_time : $install_time + 3600*24*4;

		if ($ask_review_time < time() and $install_time != '' and $install_time < time() - 3600*24*4) {
			
			global $pagenow;

			if ( $pagenow != 'post.php' && $pagenow != 'post-new.php' ) { ?>
	
				<div class='notice notice-info is-dismissible fdm-main-dashboard-review-ask' style='display:none'>
					<div class='fdm-review-ask-plugin-icon'></div>
					<div class='fdm-review-ask-text'>
						<p class='fdm-review-ask-starting-text'>Enjoying using the Five-Star Restaurant Menu?</p>
						<p class='fdm-review-ask-feedback-text fdm-hidden'>Help us make the plugin better! Please take a minute to rate the plugin. Thanks!</p>
						<p class='fdm-review-ask-review-text fdm-hidden'>Please let us know what we could do to make the plugin better!<br /><span>(If you would like a response, please include your email address.)</span></p>
						<p class='fdm-review-ask-thank-you-text fdm-hidden'>Thank you for taking the time to help us!</p>
					</div>
					<div class='fdm-review-ask-actions'>
						<div class='fdm-review-ask-action fdm-review-ask-not-really fdm-review-ask-white'>Not Really</div>
						<div class='fdm-review-ask-action fdm-review-ask-yes fdm-review-ask-green'>Yes!</div>
						<div class='fdm-review-ask-action fdm-review-ask-no-thanks fdm-review-ask-white fdm-hidden'>No Thanks</div>
						<a href='https://wordpress.org/support/plugin/food-and-drink-menu/reviews/' target='_blank'>
							<div class='fdm-review-ask-action fdm-review-ask-review fdm-review-ask-green fdm-hidden'>OK, Sure</div>
						</a>
					</div>
					<div class='fdm-review-ask-feedback-form fdm-hidden'>
						<div class='fdm-review-ask-feedback-explanation'>
							<textarea></textarea>
							<br>
							<input type="email" name="feedback_email_address" placeholder="<?php _e('Email Address', 'restaurant-reservations'); ?>">
						</div>
						<div class='fdm-review-ask-send-feedback fdm-review-ask-action fdm-review-ask-green'>Send Feedback</div>
					</div>
					<div class='fdm-clear'></div>
				</div>

			<?php
			}
		}
		else {
			wp_dequeue_script( 'fdm-review-ask-js' );
		}
	}

	public function enqueue_review_ask_scripts() {

		wp_enqueue_style( 'fdm-review-ask-css', FDM_PLUGIN_URL . '/assets/css/dashboard-review-ask.css', array(), FDM_VERSION );
		wp_enqueue_script( 'fdm-review-ask-js', FDM_PLUGIN_URL . '/assets/js/dashboard-review-ask.js', array( 'jquery' ), FDM_VERSION, true  );

		wp_localize_script(
			'fdm-review-ask-js',
			'fdm_review_ask',
			array(
				'nonce' => wp_create_nonce( 'fdm-review-ask-js' )
			)
		);
	}

	public function hide_review_ask() {

		// Authenticate request
		if ( ! check_ajax_referer( 'fdm-review-ask-js', 'nonce' ) ) {
			
			fdmHelper::admin_nopriv_ajax();
		}

		$ask_review_time = sanitize_text_field($_POST['ask_review_time']);

    	if ( get_option( 'fdm-review-ask-time' ) < time() + 3600*24 * $ask_review_time ) {
    		update_option( 'fdm-review-ask-time', time() + 3600*24 * $ask_review_time );
    	}

    	die();
	}

	public function send_feedback() {

		// Authenticate request
		if ( ! check_ajax_referer( 'fdm-review-ask-js', 'nonce' ) ) {
			
			fdmHelper::admin_nopriv_ajax();
		}
		
		$headers = 'Content-type: text/html;charset=utf-8' . "\r\n";  
	    $feedback = sanitize_text_field($_POST['feedback']);
 		$feedback .= '<br /><br />Email Address: ';
    	$feedback .= sanitize_text_field($_POST['email_address']);

    	wp_mail('contact@fivestarplugins.com', 'FDM Feedback - Dashboard Form', $feedback, $headers);

    	die();
	} 
}

}