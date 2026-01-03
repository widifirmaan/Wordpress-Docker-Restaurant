<?php

use YahnisElsts\WpDependencyWrapper\v1\ScriptDependency;

class ameActorSelector extends ameModule {
	const ajaxUpdateAction = 'ws_ame_set_visible_users';

	public function __construct($menuEditor) {
		parent::__construct($menuEditor);

		add_filter('admin_menu_editor-base_scripts', array($this, 'addBaseScript'), 11);
		add_action('wp_ajax_' . self::ajaxUpdateAction, array($this, 'ajaxSetVisibleUsers'));
		add_filter('admin_menu_editor-users_to_load', array($this, 'addVisibleUsersToLoginList'));
	}

	public function addBaseScript($baseDeps) {
		$isProVersion = apply_filters('admin_menu_editor_is_pro', false);
		$dependencies = array($baseDeps['ame-actor-manager'], $baseDeps['ame-lodash'], 'jquery');
		if ( isset($baseDeps['ame-visible-users']) ) {
			$dependencies[] = $baseDeps['ame-visible-users'];
		} else if ( $isProVersion || wp_script_is('ame-visible-users', 'registered') ) {
			$dependencies[] = 'ame-visible-users';
		}

		$actorSelectorScript = ScriptDependency::create(
			plugins_url('modules/actor-selector/actor-selector.js', $this->menuEditor->plugin_file),
			'ame-actor-selector',
			AME_ROOT_DIR . '/modules/actor-selector/actor-selector.js'
		)->addDependencies(...$dependencies);

		$actorSelectorScript->addLazyJsVariable(
			'wsAmeActorSelectorData',
			function() use ($isProVersion) {
				$currentUser = wp_get_current_user();
				return array(
					'visibleUsers' => $this->menuEditor->get_plugin_option('visible_users'),
					'currentUserLogin' => $currentUser->get('user_login'),
					'isProVersion' => $isProVersion,

					'ajaxUpdateAction' => self::ajaxUpdateAction,
					'ajaxUpdateNonce' => wp_create_nonce(self::ajaxUpdateAction),
					'adminAjaxUrl' => admin_url('admin-ajax.php'),
				);
			}
		);

		$baseDeps[$actorSelectorScript->getHandle()] = $actorSelectorScript;

		return $baseDeps;
	}

	public function ajaxSetVisibleUsers() {
		if ( !check_ajax_referer(self::ajaxUpdateAction, false, false) ){
			die(esc_html__("Access denied. Invalid nonce.", 'admin-menu-editor'));
		}
		if ( !$this->menuEditor->current_user_can_edit_menu() ) {
			die(esc_html__("You don't have permission to use Admin Menu Editor Pro.", 'admin-menu-editor'));
		}

		$post = $this->menuEditor->get_post_params();
		$visibleUsers = json_decode(strval($post['visible_users']));
		$visibleUsers = array_unique(array_map('strval', $visibleUsers));

		$this->menuEditor->set_plugin_option('visible_users', $visibleUsers);
		die('OK');
	}

	public function addVisibleUsersToLoginList($userLogins) {
		$visibleUsers = $this->menuEditor->get_plugin_option('visible_users');
		if ( is_array($visibleUsers) ) {
			$userLogins = array_merge($userLogins, $visibleUsers);
		}
		return $userLogins;
	}
}