<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Settings\WithSchema;

use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Schema;

interface SettingWithSchema {
	/**
	 * @return Schema
	 */
	public function getSchema();
}