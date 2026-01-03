<?php
//Delete content permissions settings.
if ( defined('ABSPATH') && defined('WP_UNINSTALL_PLUGIN') ) {
	delete_option('ame_cpe_settings');
	delete_option('ame_cpe_restricted_items');
	if ( function_exists('delete_site_option') ) {
		delete_site_option('ame_cpe_settings');
		delete_site_option('ame_cpe_restricted_items');
	}

	//Delete all post policies.
	delete_metadata('post', 0, '_ame_cpe_post_policy', null, true);
}