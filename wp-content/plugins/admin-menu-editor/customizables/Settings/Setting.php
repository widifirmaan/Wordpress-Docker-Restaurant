<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Settings;

use YahnisElsts\AdminMenuEditor\Customizable\Storage\StorageInterface;

class Setting extends AbstractSetting {
	protected $defaultValue = null;

	public function __construct($id, ?StorageInterface $store = null, $params = array()) {
		parent::__construct($id, $store, $params);

		if ( array_key_exists('default', $params) ) {
			$this->defaultValue = $params['default'];
		}
		$this->dataType = isset($params['type']) ? $params['type'] : $this->dataType;
	}

	public function getValue($customDefault = null) {
		$currentDefault = ($customDefault !== null) ? $customDefault : $this->defaultValue;
		if ( $this->store ) {
			return $this->store->getValue($currentDefault);
		}
		return $currentDefault;
	}

	/**
	 * Update the value of this setting.
	 *
	 * @param $validValue
	 * @return boolean
	 */
	public function update($validValue) {
		if ( $this->store ) {
			$isSuccess = $this->store->setValue($validValue);
			$this->notifyUpdated();
			return $isSuccess;
		}
		return false;
	}

	/**
	 * @param \WP_Error $errors
	 * @param $value
	 * @param bool $stopOnFirstError
	 * @return \WP_Error|mixed
	 */
	public function validate($errors, $value, $stopOnFirstError = false) {
		//Should be overridden by subclasses.
		return $value;
	}

	public function validateFormValue($errors, $value, $stopOnFirstError = false) {
		$decodedValue = $this->decodeSubmittedValue($value);
		return $this->validate($errors, $decodedValue, $stopOnFirstError);
	}

	protected function canTreatAsNull($inputValue) {
		if ( $this->isNullable() && (($inputValue === null) || ($inputValue === '')) ) {
			return true;
		}
		return false;
	}

	public function isNullable() {
		return ($this->defaultValue === null);
	}

	/**
	 * @return mixed|null
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}
}