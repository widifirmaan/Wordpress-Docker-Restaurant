<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Settings;

use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Color;
use YahnisElsts\AdminMenuEditor\Customizable\Storage\StorageInterface;

class ColorSetting extends WithSchema\SingularSetting {
	protected $label = 'Color';
	protected $dataType = 'color';

	public function __construct($id, ?StorageInterface $store = null, $params = array()) {
		$schema = new Color();
		if ( array_key_exists('default', $params) ) {
			$schema->defaultValue($params['default']);
		}

		parent::__construct($schema, $id, $store, $params);
	}
}