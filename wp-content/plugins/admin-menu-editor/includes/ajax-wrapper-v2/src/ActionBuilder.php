<?php

namespace YahnisElsts\AjaxActionWrapper\v2;

class ActionBuilder extends ConfigFields {
	public function __construct($action) {
		if ( !is_string($action) || empty($action) ) {
			throw new \InvalidArgumentException('Action name must be a non-empty string.');
		}
		$this->action = $action;
	}

	/**
	 * @param callable $callback
	 * @return $this
	 */
	public function handler(callable $callback) {
		$this->callback = $callback;
		return $this;
	}

	/**
	 * @param string $name
	 * @param callable ...$parsers
	 * @return $this
	 */
	public function requiredParam($name, callable ...$parsers) {
		return $this->addParam($name, true, null, $parsers);
	}

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @param callable ...$parsers
	 * @return $this
	 */
	public function optionalParam($name, $defaultValue, callable ...$parsers) {
		return $this->addParam($name, false, $defaultValue, $parsers);
	}

	/**
	 * @param string $name
	 * @param bool $isRequired
	 * @param mixed $defaultValue
	 * @param callable[] $parsers
	 * @return $this
	 */
	private function addParam($name, $isRequired, $defaultValue, array $parsers) {
		if ( !is_string($name) || empty($name) ) {
			throw new \InvalidArgumentException('Parameter name must be a non-empty string.');
		}
		$this->params[$name] = [
			'required' => $isRequired,
			'default'  => $defaultValue,
			'parsers'  => $parsers,
		];
		return $this;

	}

	/**
	 * Set a custom parser for the request parameters.
	 *
	 * The parser will be called with the request parameters as an array. It should
	 * return an associative array of parsed parameters or a WP_Error object on failure.
	 *
	 * If set, the custom parser will run *before* the default parameter parsing and
	 * validation.
	 *
	 * @param callable $parser (array $requestParams) => array|WP_Error
	 * @return $this
	 */
	public function paramParser(callable $parser) {
		$this->customParamParser = $parser;
		return $this;
	}

	/**
	 * @param string|null $httpMethod
	 * @return $this
	 */
	public function method($httpMethod) {
		$this->httpMethod = (is_string($httpMethod) && ($httpMethod !== '')) ? strtoupper($httpMethod) : null;
		return $this;
	}

	public function requiredCap($capability) {
		$this->requiredCapability = $capability;
		return $this;
	}

	public function permissionCallback($callback) {
		$this->permissionCheckCallback = $callback;
		return $this;
	}

	public function allowUnprivilegedUsers() {
		$this->mustBeLoggedIn = false;
		return $this;
	}

	public function withoutNonce() {
		$this->nonceCheckEnabled = false;
		return $this;
	}

	/**
	 * Disable the automatic exposure of the action to JavaScript.
	 *
	 * By default, registered actions are automatically exported to JavaScript and become
	 * available via the `AjawV2.getAction()` method. Use `skipAutoExpose()` to disable that
	 * behaviour for specific actions.
	 *
	 * This doesn't actively prevent the action from being used in JavaScript. It only
	 * means that you will have to explicitly serialize the action metadata and pass it
	 * to your JavaScript code.
	 *
	 * @return $this
	 */
	public function skipAutoExpose() {
		$this->jsAutoExposeEnabled = false;
		return $this;
	}

	public function build() {
		if ( !is_callable($this->callback) ) {
			throw new \LogicException('Callback must be set before building the action.');
		}
		return new Action($this);
	}

	public function register() {
		return $this->build()->register();
	}

	public static function create($action) {
		return new static($action);
	}
}