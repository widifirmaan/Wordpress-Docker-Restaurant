<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmPermissions' ) ) {
/**
 * Class to handle plugin permissions
 *
 * @since 2.0.0
 */
class fdmPermissions {

	private $plugin_permissions;
	private $permission_level;

	public function __construct() {

		if ( ! is_array( get_option( 'fdm-permission-level' ) ) ) { $this->set_permissions(); } 

		$permission_array = get_option( 'fdm-permission-level' );

		$this->permission_level = is_array( $permission_array ) ? reset( $permission_array ) : $permission_array;

		$this->plugin_permissions = array(
			"premium" => 2,
			"advanced" => 2,
			"labelling" => 2,
			"styling" => 2,
			"filtering" => 2,
			"flags" => 2,
			"specials" => 2,
			"sources" => 2,
			"discounts" => 2,
			"related_items" => 2,
			"import" => 2,
			"export" => 2,
			"ordering" => 3,
			"custom_fields" => 2,
			"api_usage"	=> 3
		);
	}

	public function set_permissions() {

		if ( is_array( get_option( 'fdm-permission-level' ) ) ) { return; }

		if ( ! empty( get_option( 'fdm-permission-level' ) ) ) { 

			update_option( 'fdm-permission-level', array( get_option( 'fdm-permission-level' ) ) );

			return;
		}

		$menu_objects = get_posts( array( 'post_type' => array( FDM_MENU_POST_TYPE, FDM_MENUITEM_POST_TYPE ) ) );

		$this->permission_level = ( get_option("fdmp_license_key") ? 2 : ( ! empty($menu_objects) ? 1 : 0 ) );

		update_option( 'fdm-permission-level', array( $this->permission_level ) );
	}

	public function check_permission($permission_type = '') {
		return ( array_key_exists( $permission_type, $this->plugin_permissions ) ? ( $this->permission_level >= $this->plugin_permissions[$permission_type] ? true : false ) : false );
	}

	public function update_permissions() {
		$this->permission_level = get_option( "fdm-permission-level" );
	}
}
} // endif
