<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Builders;

use YahnisElsts\AdminMenuEditor\Customizable\Schemas;
use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Schema;
use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Struct;
use YahnisElsts\AdminMenuEditor\Customizable\Settings;
use YahnisElsts\AdminMenuEditor\Customizable\Storage\StorageInterface;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\Borders;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\BorderStyle;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\BoxShadow;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\CssColorSetting;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\CssEnumSetting;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\CssLengthSetting;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\Font;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\IndividualBorder;
use YahnisElsts\AdminMenuEditor\ProCustomizable\Settings\Spacing;

class SettingFactory {
	/**
	 * @var StorageInterface
	 */
	protected $store;
	/**
	 * @var array<string,mixed>
	 */
	protected $defaults;

	protected $idPrefix;

	/**
	 * @var bool Whether to enable postMessage for all settings created by this factory.
	 */
	protected $enablePostMessageForAll = false;

	protected $tagsToApply = array();

	public function __construct(StorageInterface $store, array $defaults = array(), $idPrefix = '') {
		$this->store = $store;
		$this->defaults = $defaults;
		$this->idPrefix = $idPrefix;
	}

	/**
	 * @param $path
	 * @param $label
	 * @param $params
	 * @return array
	 */
	protected function prepareParams($path, $label, $params) {
		if ( !array_key_exists('default', $params) && array_key_exists($path, $this->defaults) ) {
			$params['default'] = $this->defaults[$path];
		}
		if ( isset($label) ) {
			$params['label'] = $label;
		}
		if ( $this->enablePostMessageForAll ) {
			$params['supportsPostMessage'] = true;
		}
		if ( !empty($this->tagsToApply) ) {
			$params['tags'] = $this->tagsToApply;
		}
		return $params;
	}

	protected function idFrom($path) {
		return $this->idPrefix . str_replace('.', '-', $path);
	}

	protected function slotFor($path) {
		return $this->store->buildSlot($path);
	}

	public function enum($path, $enumValues, $label = null, $params = array()) {
		return new Settings\EnumSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$enumValues,
			$this->prepareParams($path, $label, $params)
		);
	}

	public function stringEnum($path, $enumValues, $label = null, $params = array()) {
		return new Settings\StringEnumSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$enumValues,
			$this->prepareParams($path, $label, $params)
		);
	}

	public function boolean($path, $label = null, $params = array()) {
		return $this->schemaToSetting(
			new Schemas\Boolean($label),
			$path,
			Settings\WithSchema\SingularSetting::class,
			$params
		);
	}

	public function url($path, $label = null, $params = array()) {
		return new Settings\UrlSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function string($path, $label = null, $params = array()) {
		return new Settings\StringSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function userSanitizedString(
		$path,
		$mode = Settings\UserSanitizedStringSetting::SANITIZE_STRIP_HTML,
		$label = null,
		$params = array()
	) {
		$params['sanitizationMode'] = $mode;
		return new Settings\UserSanitizedStringSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	/**
	 * Plain text (no tags) for regular users, arbitrary content for users with
	 * the "unfiltered_html" capability.
	 *
	 * HTML entities are allowed in either case.
	 *
	 * @param $path
	 * @param $label
	 * @param $params
	 * @return \YahnisElsts\AdminMenuEditor\Customizable\Settings\UserSanitizedStringSetting
	 */
	public function userText($path, $label = null, $params = array()) {
		return $this->userSanitizedString(
			$path,
			Settings\UserSanitizedStringSetting::SANITIZE_STRIP_HTML,
			$label,
			$params
		);
	}

	public function userHtml($path, $label = null, $params = array()) {
		return $this->userSanitizedString(
			$path,
			Settings\UserSanitizedStringSetting::SANITIZE_POST_HTML,
			$label,
			$params
		);
	}

	public function plainText($path, $label = null, $params = array()) {
		return new Settings\PlainTextSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function integer($path, $label = null, $params = array()) {
		return new Settings\IntegerSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssLength($path, $label = null, $cssProperty = '', $params = array()) {
		return new CssLengthSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$cssProperty,
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssColor($path, $cssProperty, $label = null, $params = array()) {
		return new CssColorSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$cssProperty,
			$this->prepareParams($path, $label, $params)
		);
	}

	public function image($path, $label = null, $params = array()) {
		return new Settings\ImageSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssBoxShadow($path, $label = null, $params = array()) {
		return new BoxShadow(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssFont($path, $label = null, $params = array()) {
		return new Font(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssSpacing($path, $label = null, $params = array()) {
		return new Spacing(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssEnum($path, $cssProperty, $enumValues, $label = null, $params = array()) {
		return new CssEnumSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			$cssProperty,
			$enumValues,
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssBorders($path, $label = null, $params = array()) {
		return new Borders(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssIndividualBorder($path, $label = null, $params = array()) {
		return new IndividualBorder(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, $label, $params)
		);
	}

	public function cssBorderStyle($path, $cssProperty = 'border-style', $label = null, $params = array()) {
		return new BorderStyle(
			$this->idFrom($path),
			$this->slotFor($path),
			$cssProperty,
			$this->prepareParams($path, $label, $params)
		);
	}

	/**
	 * @param string $path
	 * @param string $dataType
	 * @param callable $validationCallback
	 * @param $label
	 * @param array $params
	 * @return \YahnisElsts\AdminMenuEditor\Customizable\Settings\UserDefinedSetting
	 */
	public function custom(
		$path,
		$dataType,
		$validationCallback,
		$label = null,
		$params = array()
	) {
		return new Settings\UserDefinedSetting(
			$this->idFrom($path),
			$this->slotFor($path),
			array_merge(
				$this->prepareParams($path, $label, $params),
				array(
					'validationCallback' => $validationCallback,
					'type'               => $dataType,
				)
			)
		);
	}

	/**
	 * @param string|array $path
	 * @param callable|null $childGeneratorCallback
	 * @param array $params
	 * @return \YahnisElsts\AdminMenuEditor\Customizable\Settings\UserDefinedStruct
	 */
	public function customStruct(
		$path,
		$childGeneratorCallback = null,
		$params = array()
	) {
		if ( isset($childGeneratorCallback) ) {
			$params['childGenerator'] = $childGeneratorCallback;
		}

		return new Settings\UserDefinedStruct(
			$this->idFrom($path),
			$this->slotFor($path),
			$this->prepareParams($path, '', $params)
		);
	}

	/**
	 * @param class-string<\YahnisElsts\AdminMenuEditor\Customizable\Settings\AbstractSetting> $settingClass
	 * @param string|array<string> $path
	 * @param string|null $label
	 * @param $params
	 * @param ...$otherConstructorArgs
	 * @return \YahnisElsts\AdminMenuEditor\Customizable\Settings\AbstractSetting
	 */
	public function create($settingClass, $path, $label = null, $params = array(), ...$otherConstructorArgs) {
		//$params is always the last constructor argument.
		$otherConstructorArgs[] = $this->prepareParams($path, $label, $params);

		return new $settingClass(
			$this->idFrom($path),
			$this->slotFor($path),
			...$otherConstructorArgs
		);
	}

	/**
	 * @param array<string|int, Schema|Settings\AbstractSetting> $items
	 * @param callable $settingFromSchema
	 * @return Settings\AbstractSetting[]
	 */
	public static function buildAllWithCustomFactory(array $items, $settingFromSchema) {
		$instances = [];
		$building = [];
		foreach ($items as $key => $item) {
			if ( $item instanceof Settings\AbstractSetting ) {
				//This is already a setting, just add it to the list.
				$instances[$key] = $item;
			} else if ( $item instanceof Schema ) {
				$instances[$key] = static::buildSettingFromSchema(
					$items,
					$key,
					$settingFromSchema,
					$instances,
					$building
				);
			} else {
				throw new \InvalidArgumentException(
					esc_html('Invalid item type for a setting builder: ' . gettype($item))
				);
			}
		}

		return $instances;
	}

	protected static function buildSettingFromSchema(array $schemas, $key, $settingFromSchema, &$instances, &$building) {
		if ( isset($instances[$key]) ) {
			return $instances[$key];
		}

		if ( array_key_exists($key, $building) ) {
			throw new \RuntimeException('Circular reference detected: ' . esc_html($key));
		}

		$building[$key] = true;

		$thisSchema = $schemas[$key];
		$hints = $thisSchema->getSettingBuilderHints();

		//Note that we don't use the params from the hints here. It's up to the factory callback
		//or the setting class to decide how to use them. This is because the existing callbacks
		//can also be called directly, without preprocessing by this factory.

		//On the other hand, setting references can only be resolved/built when you have the full
		//list of relevant schemas, so we do that here.

		$params = [];
		if ( $hints ) {
			//Resolve setting references, recursively building them if necessary.
			foreach ($hints->getSettingReferences() as $paramName => $referenceKey) {
				$params[$paramName] = static::buildSettingFromSchema(
					$schemas,
					$referenceKey,
					$settingFromSchema,
					$instances,
					$building
				);
			}
		}

		$suggestedClassName = static::chooseSettingClassForSchema($thisSchema);

		$instance = call_user_func_array(
			$settingFromSchema,
			[$thisSchema, $key, $suggestedClassName, $params]
		);
		$instances[$key] = $instance;

		unset($building[$key]);

		return $instance;
	}

	protected static function chooseSettingClassForSchema(Schema $schema) {
		$hints = $schema->getSettingBuilderHints();
		if ( $hints ) {
			$className = $hints->getClassHint();
			if ( !empty($className) ) {
				return $className;
			}
		}

		if ( $schema instanceof Struct ) {
			return Settings\WithSchema\StructSetting::class;
		}
		return Settings\WithSchema\SingularSetting::class;
	}

	public function schemaToSetting(Schema $schema, $path, $className = null, $params = []) {
		//Override the schema default if we have a specific default value for this setting.
		if ( array_key_exists('default', $params) ) {
			$schema = $schema->defaultValue($params['default']);
		} elseif ( array_key_exists($path, $this->defaults) ) {
			$schema = $schema->defaultValue($this->defaults[$path]);
		}

		$id = $this->idFrom($path);
		$slot = $this->slotFor($path);

		$hints = $schema->getSettingBuilderHints();
		if ( $hints ) {
			$params = array_merge($hints->getParams(), $params);
		}
		$params = $this->prepareParams($path, null, $params);

		if ( empty($className) ) {
			$className = static::chooseSettingClassForSchema($schema);
		}

		return new $className($schema, $id, $slot, $params);
	}

	/**
	 * @param array<string|int, Schema|Settings\AbstractSetting> $items
	 * @return array<Settings\AbstractSetting>
	 */
	public function buildSettings(array $items) {
		return array_values(static::buildAllWithCustomFactory(
			$items,
			[$this, 'schemaToSetting']
		));
	}

	/**
	 * @return string
	 */
	public function getIdPrefix() {
		return $this->idPrefix;
	}

	/**
	 * Tell the factory to automatically enable postMessage support for all settings
	 * that it creates.
	 *
	 * @return void
	 */
	public function enablePostMessageSupport() {
		$this->enablePostMessageForAll = true;
	}

	public function disablePostMessage() {
		$this->enablePostMessageForAll = false;
	}

	/**
	 * Tell the factory to add the specified tags to all settings that it creates.
	 *
	 * @param string[] $tags
	 * @return void
	 */
	public function setTags(...$tags) {
		$this->tagsToApply = $tags;
	}
}