<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Settings;

use YahnisElsts\AdminMenuEditor\Customizable\Storage\StorageInterface;
use YahnisElsts\AdminMenuEditor\Customizable\Validation\StringValidator;

class StringSetting extends Setting {
	protected $dataType = 'string';

	protected $minLength = 0;
	protected $maxLength = null;
	protected $validators = [];

	public function __construct($id, ?StorageInterface $store = null, $params = []) {
		parent::__construct($id, $store, $params);

		if ( array_key_exists('minlength', $params) ) {
			$this->minLength = ($params['minlength'] === null) ? null : (int)$params['minlength'];
		}
		if ( array_key_exists('maxlength', $params) ) {
			$this->maxLength = ($params['maxlength'] === null) ? null : (int)$params['maxlength'];
		}

		$this->validators[] = new StringValidator(
			$this->minLength,
			$this->maxLength,
			false,
			isset($params['regex']) ? $params['regex'] : null,
			array_key_exists('trimmed', $params) && $params['trimmed']
		);

		if ( array_key_exists('customValidators', $params) ) {
			$this->validators = array_merge($this->validators, $params['customValidators']);
		}
	}

	public function validate($errors, $value, $stopOnFirstError = false) {
		if ( $this->canTreatAsNull($value) ) {
			return null;
		}

		return self::applyValidators($this->validators, $value, $errors, $stopOnFirstError);
	}
}