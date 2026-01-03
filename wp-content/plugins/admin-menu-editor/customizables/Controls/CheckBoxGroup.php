<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Controls;

use YahnisElsts\AdminMenuEditor\Customizable\HtmlHelper;
use YahnisElsts\AdminMenuEditor\Customizable\Rendering\Renderer;
use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Enum;
use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Record;
use YahnisElsts\AdminMenuEditor\Customizable\Settings\WithSchema\SingularSetting;

class CheckBoxGroup extends ClassicControl {
	/**
	 * @var CollectionControlOption[]|null
	 */
	protected $cachedOptions = null;

	public function renderContent(Renderer $renderer) {
		$currentValue = $this->mainSetting->getValue();
		if ( !is_array($currentValue) ) {
			$currentValue = [];
		}
		$defaultState = $this->getDefaultOptionState();

		$classes = $this->classes;
		$beforeOption = '<p>';
		$afterOption = '</p>';

		//buildTag() is safe.
		//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->buildTag(
			'fieldset',
			[
				'class'     => $classes,
				'style'     => $this->styles,
				'disabled'  => !$this->isEnabled(),
				'data-bind' => $this->makeKoDataBind($this->getKoEnableBinding()),
			]
		);
		foreach ($this->getOptions() as $option) {
			if ( isset($currentValue[$option->value]) ) {
				$isChecked = (bool)$currentValue[$option->value];
			} else {
				$isChecked = $defaultState;
			}
			$fieldName = $this->getFieldName(strval($option->value));

			echo $beforeOption;

			//This hidden field ensures that a value is sent even if the checkbox is unchecked.
			echo HtmlHelper::tag(
				'input',
				[
					'type'  => 'hidden',
					'name'  => $fieldName,
					'value' => '0',
					'class' => 'ame-cg-checkbox-alternative',
				]
			);

			$labelClasses = ['ame-cg-option-label'];
			echo $this->buildTag('label', ['class' => $labelClasses]);

			echo $this->buildTag(
				'input',
				array_merge([
					'type'     => 'checkbox',
					'name'     => $fieldName,
					'value'    => '1',
					'class'    => $this->inputClasses,
					'checked'  => $isChecked,
					'disabled' => !$option->enabled,
				], $this->inputAttributes)
			);
			echo ' ', $option->label;

			echo '</label>';
			echo $afterOption;
		}
		echo '</fieldset>';
		//phpcs:enable

		static::enqueueDependencies();
	}

	protected function getOptions(): array {
		if ( $this->cachedOptions !== null ) {
			return $this->cachedOptions;
		}

		$options = [];

		if ( $this->mainSetting instanceof SingularSetting ) {
			$schema = $this->mainSetting->getSchema();
			if ( $schema instanceof Record ) {
				$keySchema = $schema->getKeySchema();
				if ( $keySchema instanceof Enum ) {
					$options = CollectionControlOption::fromEnumSchema($keySchema);
				}
			}
		}

		$this->cachedOptions = $options;
		return $this->cachedOptions;
	}

	protected function getDefaultOptionState(): bool {
		if ( $this->mainSetting instanceof SingularSetting ) {
			$schema = $this->mainSetting->getSchema();
			if ( $schema instanceof Record ) {
				$itemSchema = $schema->getItemSchema();
				return (bool)$itemSchema->getDefaultValue(false);
			}
		}

		return false;
	}
}