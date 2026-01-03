<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Settings\WithSchema;

use YahnisElsts\AdminMenuEditor\Customizable\Builders\SettingFactory;
use YahnisElsts\AdminMenuEditor\Customizable\Schemas\Schema;
use YahnisElsts\AdminMenuEditor\Customizable\Schemas;
use YahnisElsts\AdminMenuEditor\Customizable\Settings;
use YahnisElsts\AdminMenuEditor\Customizable\Settings\AbstractSetting;
use YahnisElsts\AdminMenuEditor\Customizable\Storage\StorageInterface;

class StructSetting extends Settings\AbstractStructSetting implements SettingWithSchema {
	/**
	 * @var Schemas\Struct
	 */
	protected $schema;
	/**
	 * @var Schemas\Struct|null
	 */
	protected $effectiveSchema = null;

	/**
	 * @var array<string,SingularSetting>
	 */
	protected $settings = [];

	public function __construct(Schemas\Struct $schema, $id = '', ?StorageInterface $store = null, $params = []) {
		$this->schema = $schema;
		parent::__construct($id, $store, $params);

		//Create child settings for each schema field.
		SettingFactory::buildAllWithCustomFactory(
			$this->schema->getFields(),
			[$this, 'createChildWithSchema']
		);
	}

	public function createChildWithSchema(Schema $childSchema, $fieldName, $className, $params = [], ...$constructorParams) {
		$hints = $childSchema->getSettingBuilderHints();
		if ( $hints ) {
			$params = array_merge($hints->getParams(), $params);
		}

		//Note: Unlike AbstractStructSetting, we assume the child class constructor takes the schema
		//as the first parameter.
		$child = new $className(
			$childSchema,
			$this->makeChildId($fieldName),
			$this->store->buildSlot($fieldName),
			$params,
			...$constructorParams
		);

		$this->registerChild($fieldName, $child);
		return $child;
	}

	protected function registerChild($childKey, AbstractSetting $child) {
		//This class only accepts children that use schema.
		if ( !($child instanceof SettingWithSchema) ) {
			throw new \InvalidArgumentException('Child must be a setting that implements SettingWithSchema');
		}

		//Children don't necessarily use their provided schema as-is, so whenever a new child is added,
		//we need to re-calculate the effective schema.
		$this->effectiveSchema = null;

		parent::registerChild($childKey, $child);
	}

	public function getDefaultValue() {
		return $this->schema->getDefaultValue([]);
	}

	public function getSchema() {
		if ( $this->effectiveSchema === null ) {
			$this->effectiveSchema = new Schemas\Struct(
				array_map(function ($setting) {
					return $setting->getSchema();
				}, $this->settings)
			);
		}

		return $this->effectiveSchema;
	}
}