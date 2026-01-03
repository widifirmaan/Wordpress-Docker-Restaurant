<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Settings;

use YahnisElsts\AdminMenuEditor\Customizable\Controls\ChoiceControlOption;
use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Enum;
use YahnisElsts\AdminMenuEditor\Customizable\Settings\WithSchema\SingularSetting;
use YahnisElsts\AdminMenuEditor\Customizable\Storage\StorageInterface;

/**
 * A wrapper for a setting with an enum schema.
 */
class EnumSetting extends SingularSetting {
	public function __construct($id, StorageInterface $store, $enumValues, $params = array()) {
		if ( empty($enumValues) ) {
			throw new \InvalidArgumentException('Enum must have at least one possible value');
		}

		$schema = (new Enum())->values(array_values($enumValues));
		if ( array_key_exists('default', $params) ) {
			$schema->defaultValue($params['default']);
		}

		parent::__construct($schema, $id, $store, $params);
	}

	public function isChoiceEnabled($value) {
		$schema = $this->getSchema();
		if ( $schema instanceof Enum ) {
			return $schema->isValueEnabled($value);
		}
		return true; //Should never happen, but just in case.
	}

	/**
	 * @param mixed $value
	 * @param string|null $label
	 * @param string|null $description
	 * @param bool|callable|null $state
	 * @param string|null $icon
	 * @return $this
	 */
	public function describeChoice($value, $label, $description = '', $state = null, $icon = null) {
		$schema = $this->getSchema();
		if ( $schema instanceof Enum ) {
			$schema->describeValue($value, $label, $description, $state, $icon);
		}
		return $this;
	}

	/**
	 * Automatically generate dropdown/radio/etc options from the setting's
	 * possible values.
	 *
	 * Will use custom labels/descriptions if available.
	 *
	 * @return ChoiceControlOption[]
	 */
	public function generateChoiceOptions() {
		return ChoiceControlOption::fromEnumSetting($this);
	}

	/**
	 * @return array
	 */
	public function getEnumValues() {
		$schema = $this->getSchema();
		if ( $schema instanceof Enum ) {
			return $schema->getEnumValues();
		}
		return [];
	}

	public function getChoiceDetails($value) {
		$schema = $this->getSchema();
		if ( $schema instanceof Enum ) {
			return $schema->getValueDetails($value);
		}
		return null;
	}
}