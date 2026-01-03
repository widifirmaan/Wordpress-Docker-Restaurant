<?php

namespace YahnisElsts\AjaxActionWrapper\v2;

abstract class ConfigFields {
	/**
	 * @var string
	 */
	protected $action;
	/**
	 * @var callable
	 */
	protected $callback;
	/**
	 * @var array<string, array>
	 */
	protected $params = [];
	/**
	 * @var callable|null
	 */
	protected $customParamParser = null;
	/**
	 * @var string|null
	 */
	protected $httpMethod = null;

	/**
	 * @var string|null
	 */
	protected $requiredCapability = null;
	/**
	 * @var bool
	 */
	protected $mustBeLoggedIn = false;
	/**
	 * @var bool
	 */
	protected $nonceCheckEnabled = true;
	/**
	 * @var callable|null
	 */
	protected $permissionCheckCallback = null;
	/**
	 * @var bool
	 */
	protected $jsAutoExposeEnabled = true;
}