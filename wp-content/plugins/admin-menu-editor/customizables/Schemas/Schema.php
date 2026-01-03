<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Schemas;

use YahnisElsts\AdminMenuEditor\Customizable\Settings;
use YahnisElsts\AdminMenuEditor\Options\Option;

abstract class Schema {
	protected $defaultValueOption;
	protected $_nullable = false;
	protected $convertEmptyStringsToNull = false;

	public function __construct($label = null) {
		$this->defaultValueOption = Option::none();

		if ( $label !== null ) {
			$this->settingParams(['label' => $label]);
		}
	}

	/**
	 * @param mixed $value
	 * @param \WP_Error|null $errors
	 * @param bool $stopOnFirstError
	 * @return mixed|\WP_Error
	 */
	abstract public function parse($value, $errors = null, $stopOnFirstError = false);

	public function __invoke($value) {
		return $this->parse($value);
	}

	public function defaultValue($defaultValue) {
		$this->defaultValueOption = Option::some($defaultValue);
		if ( $defaultValue === null ) {
			$this->_nullable = true;
		}
		return $this;
	}

	public function hasDefaultValue() {
		return $this->defaultValueOption && $this->defaultValueOption->isDefined();
	}

	public function getDefaultValue($fallback = null) {
		return $this->defaultValueOption->getOrElse($fallback);
	}

	public function nullable() {
		$this->_nullable = true;
		return $this;
	}

	public function notNullable() {
		$this->_nullable = false;
		return $this;
	}

	public function isNullable() {
		return $this->_nullable;
	}

	protected function checkForNull($value, $errors) {
		if ( $value === null ) {
			if ( $this->isNullable() ) {
				return null;
			} else {
				return self::addError($errors, 'not_nullable', 'Value cannot be null');
			}
		}

		if ( ($value === '') && $this->convertEmptyStringsToNull && $this->isNullable() ) {
			return null;
		}

		return $value;
	}

	/**
	 * If you convert a value matching this schema to a string, can the schema safely parse it back?
	 *
	 * @return bool
	 */
	public function isStringConversionSafe() {
		return false;
	}

	/**
	 * Add an error to a WP_Error instance, or create a new instance if it's not provided.
	 *
	 * @param \WP_Error|null $errorObject
	 * @param string $code
	 * @param string $message
	 * @return \WP_Error
	 */
	protected static function addError($errorObject, $code, $message, $customParams = null) {
		if ( !($errorObject instanceof \WP_Error) ) {
			$errorObject = new \WP_Error();
		}
		$errorObject->add(
			isset($customParams['errorCode']) ? $customParams['errorCode'] : $code,
			isset($customParams['errorMessage']) ? $customParams['errorMessage'] : $message
		);
		return $errorObject;
	}

	/**
	 * Serialize validation rules for JavaScript.
	 *
	 * @return array|null
	 */
	public function serializeValidationRules() {
		return null;
	}

	/**
	 * @return string
	 */
	public function getSimplifiedDataType() {
		return '';
	}

	//region Setting helpers

	/**
	 * @var SettingBuilderHints|null
	 */
	protected $_settingHints = null;

	protected function getOrCreateSettingHints() {
		if ( $this->_settingHints === null ) {
			$this->_settingHints = new SettingBuilderHints();
		}
		return $this->_settingHints;
	}

	public function getSettingBuilderHints() {
		return $this->_settingHints;
	}

	/**
	 * Set the class name and optional parameters for settings that may be created from this schema.
	 *
	 * Shortcut for settingClassHint() and settingParams().
	 *
	 * @param class-string<Settings\AbstractSetting>|null $className
	 * @param array<string,mixed>|null $params
	 * @return $this
	 */
	public function s($className, $params = null) {
		if ( $className ) {
			$this->settingClassHint($className);
		}
		if ( is_array($params) ) {
			$this->settingParams($params);
		}
		return $this;
	}

	/**
	 * Add parameters to be used when creating a setting from this schema.
	 *
	 * These parameters don't affect the schema itself. This is just a way to pass additional
	 * information through the schema to a setting builder.
	 *
	 * @param array<string,mixed> $params
	 * @return $this
	 */
	public function settingParams(array $params) {
		$this->getOrCreateSettingHints()->addParams($params);
		return $this;
	}

	/**
	 * Specify a setting class suitable for settings that may be created from this schema.
	 *
	 * For example, there might be many types of settings that essentially hold a single number.
	 * This method lets you provide a hint when defining a schema that a specific setting class
	 * should be used.
	 *
	 * {@link SettingFactory} will use the hint when creating settings, but other code may ignore it.
	 *
	 * @param class-string<Settings\AbstractSetting> $className
	 * @return $this
	 */
	public function settingClassHint($className) {
		$this->getOrCreateSettingHints()->setClassHint($className);
		return $this;
	}

	/**
	 * Alias for settingClassHint()
	 *
	 * @param class-string<Settings\AbstractSetting> $className
	 * @return $this
	 */
	public function sc($className) {
		return $this->settingClassHint($className);
	}

	/**
	 * Add a hint for the setting builder that an item in the $params array should be set to
	 * the sibling setting that has the specified key.
	 *
	 * This is useful for settings that are related to each other, e.g. child settings being
	 * generated from the same schema.
	 *
	 * @param string $paramName
	 * @param string $siblingSettingKey
	 * @return $this
	 */
	public function settingReference($paramName, $siblingSettingKey) {
		$this->getOrCreateSettingHints()->addSettingReference($paramName, $siblingSettingKey);
		return $this;
	}
	//endregion
}