<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Controls;

use YahnisElsts\AdminMenuEditor\Customizable\HtmlHelper;
use YahnisElsts\AdminMenuEditor\Customizable\Settings;

class ChoiceControlOption extends CollectionControlOption {
	/**
	 * @param CollectionControlOption[] $options
	 * @param mixed $selectedValue
	 * @param Settings\AbstractSetting $setting
	 * @return array
	 */
	public static function generateSelectOptions(array $options, $selectedValue, Settings\AbstractSetting $setting): array {
		$htmlLines = [];

		foreach ($options as $option) {
			$htmlLines[] = HtmlHelper::tag(
				'option',
				[
					'value'    => $setting->encodeForForm($option->value),
					'selected' => ($selectedValue === $option->value),
					'disabled' => !$option->enabled,

				],
				$option->label
			);
		}

		$koOptionData = self::generateKoOptions($options);
		$optionBindings = array_map('wp_json_encode', $koOptionData);

		return [implode("\n", $htmlLines), $optionBindings];
	}

	/**
	 * @param CollectionControlOption[] $choiceOptions
	 * @return array{options: array, optionsText: string, optionsValue: string}
	 */
	public static function generateKoOptions(array $choiceOptions): array {
		$koOptions = [];
		foreach ($choiceOptions as $option) {
			$koOptions[] = [
				'value'    => $option->value,
				'label'    => $option->label,
				'disabled' => !$option->enabled,
			];
		}

		return [
			'options'      => $koOptions,
			'optionsText'  => 'label',
			'optionsValue' => 'value',
		];
	}
}