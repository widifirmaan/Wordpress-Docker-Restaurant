<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Schemas;

class Placeholder extends Schema {
	public function parse($value, $errors = null, $stopOnFirstError = false) {
		throw new \Exception('Placeholder schema should not be used for parsing');
	}
}