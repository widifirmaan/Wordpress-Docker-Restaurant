<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Controls;

use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Enum;
use YahnisElsts\AdminMenuEditor\Customizable\Settings;

class CollectionControlOption {
	public $icon = null;
	public $description = '';
	public $label;
	public $value;
	public $enabled = true;

	/**
	 * @param mixed|null $value
	 * @param string|null $label
	 * @param array $params
	 */
	public function __construct($value, $label = null, array $params = []) {
		$this->value = $value;
		$this->label = ($label !== null) ? $label : $value;
		if ( isset($params['description']) ) {
			$this->description = $params['description'];
		}
		if ( array_key_exists('enabled', $params) ) {
			$this->enabled = (bool)($params['enabled']);
		}
		if ( isset($params['icon']) ) {
			$this->icon = $params['icon'];
		}
	}

	public static function fromEnumSchema(Enum $schema): array {
		$results = array();

		foreach ($schema->getEnumValues() as $value) {
			$results[] = self::createFromValue(
				$value,
				$schema->getValueDetails($value),
				$schema->isValueEnabled($value)
			);
		}

		return $results;
	}

	/**
	 * @param Settings\EnumSetting $setting
	 * @return static[]
	 */
	public static function fromEnumSetting(Settings\EnumSetting $setting): array {
		$results = array();

		foreach ($setting->getEnumValues() as $value) {
			$results[] = static::createFromValue(
				$value,
				$setting->getChoiceDetails($value),
				$setting->isChoiceEnabled($value)
			);
		}

		return $results;
	}

	/**
	 * Try to generate a list of options from the given setting.
	 *
	 * Returns an empty array if the setting is not a valid source of options.
	 *
	 * @param Settings\AbstractSetting|null $setting
	 * @return static[]
	 */
	public static function tryGenerateFromSetting($setting): array {
		if ( $setting instanceof Settings\EnumSetting ) {
			return static::fromEnumSetting($setting);
		} elseif ( $setting instanceof Settings\WithSchema\SettingWithSchema ) {
			$schema = $setting->getSchema();
			if ( $schema instanceof Enum ) {
				return static::fromEnumSchema($schema);
			}
		}
		return [];
	}

	protected static function createFromValue($value, $details, $enabled = true) {
		if ( !empty($details) ) {
			return new static(
				$value,
				$details['label'],
				array(
					'description' => $details['description'],
					'enabled'     => $enabled,
					'icon'        => $details['icon'],
				)
			);
		} else {
			if ( $value === null ) {
				$label = 'Default';
			} else {
				$label = is_string($value) ? $value : wp_json_encode($value);
				$label = ucwords(preg_replace('/[_-]+/', ' ', $label));
			}
			return new static($value, $label, array(
				'enabled' => $enabled,
			));
		}
	}

	public function serializeForJs(): array {
		$result = [
			'value' => $this->value,
			'label' => $this->label,
		];
		if ( $this->description !== '' ) {
			$result['description'] = $this->description;
		}
		if ( !$this->enabled ) {
			$result['enabled'] = false;
		}
		if ( $this->icon !== null ) {
			$result['icon'] = $this->icon;
		}
		return $result;
	}

	public static function fromArray($array): CollectionControlOption {
		return new static(
			array_key_exists('value', $array) ? $array['value'] : null,
			array_key_exists('label', $array) ? $array['label'] : null,
			$array
		);
	}
}