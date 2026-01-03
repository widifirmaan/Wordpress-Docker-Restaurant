<?php

namespace YahnisElsts\AjaxActionWrapper\v2;

use JsonSerializable;
use WP_Error;

class Action extends ConfigFields implements JsonSerializable {
	const PARSE_STRING = [self::class, 'parseString'];
	const PARSE_INT = [self::class, 'parseInt'];
	const PARSE_FLOAT = [self::class, 'parseFloat'];
	const PARSE_BOOLEAN = [self::class, 'parseBoolean'];

	protected $isActionRegistered = false;

	public function __construct(ConfigFields $configOrBuilder) {
		$this->action = $configOrBuilder->action;
		$this->callback = $configOrBuilder->callback;
		$this->httpMethod = $configOrBuilder->httpMethod;
		$this->params = $configOrBuilder->params;
		$this->customParamParser = $configOrBuilder->customParamParser;

		$this->requiredCapability = $configOrBuilder->requiredCapability;
		$this->permissionCheckCallback = $configOrBuilder->permissionCheckCallback;
		$this->mustBeLoggedIn = $configOrBuilder->mustBeLoggedIn;
		$this->nonceCheckEnabled = $configOrBuilder->nonceCheckEnabled;
	}

	public function register() {
		if ( $this->isActionRegistered ) {
			return $this;
		}
		$this->isActionRegistered = true;

		//Register the AJAX handler(s).
		$hookNames = ['wp_ajax_' . $this->action];
		if ( !$this->mustBeLoggedIn ) {
			$hookNames[] = 'wp_ajax_nopriv_' . $this->action;
		}

		foreach ($hookNames as $hook) {
			if ( has_action($hook) ) {
				throw new \RuntimeException(sprintf(
					'The AJAX action name "%s" is already in use.',
					$this->action
				));
			}
			add_action($hook, [$this, 'processAjaxRequest']);
		}

		if ( $this->jsAutoExposeEnabled ) {
			$this->registerForJsUse();
		}

		return $this;
	}

	public function processAjaxRequest() {
		$result = $this->handleAction();

		if ( is_wp_error($result) ) {
			$statusCode = $this->getStatusFromError($result);
			wp_send_json_error($result, $statusCode);
		} else {
			//Just send it as-is. We're not enforcing the response structure of wp_send_json_success()
			//here because the user may want to send a custom response.
			wp_send_json($result);
		}
		exit;
	}

	protected function handleAction() {
		$method = $this->getRequestMethod();
		if ( isset($this->method) && ($method !== $this->method) ) {
			return new WP_Error(
				'http_method_not_allowed',
				'The HTTP method is not supported by the request handler.',
				['status' => 405]
			);
		}

		$isAuthorized = $this->checkAuthorization();
		if ( is_wp_error($isAuthorized) ) {
			return $isAuthorized;
		} else if ( $isAuthorized !== true ) {
			return new WP_Error('authorization_failed', 'Authorization failed.', ['status' => 403]);
		}

		$params = $this->parseParameters();
		if ( is_wp_error($params) ) {
			return $params;
		}

		//Call the user-specified action handler.
		if ( is_callable($this->callback) ) {
			return call_user_func($this->callback, $params);
		} else {
			return new WP_Error(
				'missing_ajax_handler',
				sprintf(
					'There is no request handler assigned to the "%1$s" action. '
					. 'Either pass a valid callback to $builder->request() or override the %2$s::%3$s method.',
					$this->action,
					__CLASS__,
					__METHOD__
				),
				['status' => 500]
			);
		}
	}

	private function getStatusFromError(WP_Error $error) {
		$explicitStatus = null;
		$dataStatus = null;
		foreach ($error->get_all_error_data() as $data) {
			if ( is_array($data) && isset($data['status']) && is_int($data['status']) ) {
				$explicitStatus = $data['status'];
			} else if ( is_int($data) ) {
				$dataStatus = $data;
			}
		}

		if ( isset($explicitStatus) ) {
			return $explicitStatus;
		} else if ( isset($dataStatus) ) {
			return $dataStatus;
		} else {
			return 500;
		}
	}

	protected function getRequestMethod() {
		$httpMethod = filter_var(
			isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null,
			FILTER_VALIDATE_REGEXP,
			['options' => ['regexp' => '/^[a-z]{3,20}$/i']]
		);
		if ( is_string($httpMethod) ) {
			return strtoupper($httpMethod);
		}
		return $httpMethod;
	}

	protected function checkAuthorization() {
		if ( $this->mustBeLoggedIn && !is_user_logged_in() ) {
			return new WP_Error('login_required', 'You must be logged in to perform this action.', ['status' => 403]);
		}

		if ( $this->nonceCheckEnabled && !check_ajax_referer($this->action, false, false) ) {
			return new WP_Error('nonce_check_failed', 'Invalid or missing nonce.', ['status' => 403]);
		}

		if ( isset($this->requiredCap) && !current_user_can($this->requiredCap) ) {
			return new WP_Error('capability_missing', 'You don\'t have permission to perform this action.', ['status' => 403]);
		}

		if ( isset($this->permissionCallback) ) {
			$result = call_user_func($this->permissionCallback);
			if ( $result === false ) {
				return new WP_Error(
					'permission_callback_failed',
					'You don\'t have permission to perform this action.',
					['status' => 403]
				);
			} else if ( is_wp_error($result) ) {
				return $result;
			}
		}

		return true;
	}

	protected function parseParameters() {
		$method = $this->getRequestMethod();

		//phpcs:disable WordPress.Security.NonceVerification -- checkAuthorization() is where nonce verification happens.
		//Retrieve request parameters.
		if ( $method === 'GET' ) {
			$rawParams = $_GET;
		} else if ( $method === 'POST' ) {
			$rawParams = $_POST;
		} else {
			$rawParams = $_REQUEST;
		}
		//phpcs:enable

		//Remove magic quotes. WordPress applies them in wp-settings.php.
		//There's no hook for wp_magic_quotes, so we use one that's closest in execution order.
		if ( did_action('sanitize_comment_cookies') && function_exists('wp_magic_quotes') ) {
			$rawParams = wp_unslash($rawParams);
		}

		if ( $this->customParamParser ) {
			$inputParams = call_user_func($this->customParamParser, $rawParams);
			if ( is_wp_error($inputParams) ) {
				return $inputParams;
			}
		} else {
			$inputParams = $rawParams;
		}

		//Apply and parameter parsers and validators.
		//Empty strings are treated as missing parameters.
		foreach ($this->params as $name => $settings) {
			if ( isset($inputParams[$name]) && ($inputParams[$name] !== '') ) {
				//The parameter is present. Apply all parsers.
				$value = $inputParams[$name];
				foreach ($settings['parsers'] as $parser) {
					$value = call_user_func($parser, $value);
					if ( is_wp_error($value) ) {
						$message = $name . ': ' . $value->get_error_message();
						$errorCode = $value->get_error_code();
						if ( !empty($errorCode) ) {
							$message = $message . ' [' . $errorCode . ']';
						}
						return new WP_Error('invalid_parameter_value', $message, ['status' => 400]);
					}
				}
				$inputParams[$name] = $value;
			} else {
				//The parameter is missing or empty.
				if ( empty($settings['required']) ) {
					//It's an optional parameter. Use the default value.
					$inputParams[$name] = $settings['default'];
				} else {
					return new WP_Error(
						'missing_required_parameter',
						sprintf('Required parameter is missing or empty: "%s".', $name),
						['status' => 400]
					);
				}
			}
		}

		return $inputParams;
	}

	/**
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	public function isNonceCheckEnabled() {
		return $this->nonceCheckEnabled;
	}

	/**
	 * @return string|null
	 */
	public function getRequiredRequestMethod() {
		return $this->httpMethod;
	}

	/**
	 * @param string $actionName
	 * @return ActionBuilder
	 */
	public static function builder($actionName) {
		return new ActionBuilder($actionName);
	}

	//region Built-in parsers
	public static function parseInt($value) {
		$result = filter_var($value, FILTER_VALIDATE_INT);
		if ( $result === false ) {
			return new WP_Error('invalid_integer', 'Must be an integer.');
		}
		return $result;
	}

	public static function parseFloat($value) {
		$result = filter_var($value, FILTER_VALIDATE_FLOAT);
		if ( $result === false ) {
			return new WP_Error('invalid_float', 'Must be a floating point number.');
		}
		return $result;
	}

	public static function parseBoolean($value) {
		$result = filter_var($value, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);
		if ( $result === null ) {
			return new WP_Error('invalid_boolean', 'Must be a boolean value (true/false, 1/0, on/off).');
		}
		return $result;
	}

	public static function parseString($value) {
		if ( !is_string($value) ) {
			return new WP_Error('invalid_string', 'Must be a string, not ' . gettype($value) . '.');
		}
		return $value;
	}
	//endregion

	//region JavaScript API and serialization
	protected static $scriptHandle = 'ajaw-v2-ajax-action-wrapper';
	protected static $scriptRegistered = false;
	/**
	 * @var array<string,Action>
	 */
	protected static $pendingActionsForJs = [];

	protected function registerForJsUse() {
		self::$pendingActionsForJs[$this->action] = $this;
	}

	public static function registerScript() {
		if ( self::$scriptRegistered ) {
			return;
		}
		self::$scriptRegistered = true;

		//There could be multiple instances of this class, but we only need to register the script once.
		$handle = self::$scriptHandle;
		if ( !wp_script_is($handle, 'registered') ) {
			wp_register_script(
				$handle,
				plugins_url('../js/ajax-wrapper.js', __FILE__),
				['jquery'],
				'20250527'
			);
		}

		$script = sprintf('var wshAjawV2AjaxUrl = (%s);', wp_json_encode(admin_url('admin-ajax.php')));
		$script .= self::generateActionJs();
		wp_add_inline_script($handle, $script, 'after');

		self::$pendingActionsForJs = [];

		//Add a backup hook in case someone registers additional AJAX actions after the script
		//is already registered.
		add_action('admin_print_scripts', [self::class, 'outputRemainingActionsForJs'], 1000);
	}

	/**
	 * Get the handle of the JS script that provides the AjawV2 global object and
	 * easy access to the registered AJAX actions.
	 *
	 * The script is registered on the first call to this method.
	 *
	 * @return string
	 */
	public function getRegisteredScriptHandle() {
		if ( !self::$scriptRegistered ) {
			self::registerScript();
		}
		return self::$scriptHandle;
	}

	/**
	 * Alias for getRegisteredScriptHandle().
	 *
	 * @return string
	 * @deprecated Use getRegisteredScriptHandle() instead as it makes it clear that the script
	 *             gets registered on the first call.
	 *
	 * @noinspection PhpUnused -- Normally won't be used since it's deprecated.
	 */
	public function getScriptHandle() {
		return $this->getRegisteredScriptHandle();
	}

	protected static function generateActionJs() {
		$actions = [];
		foreach (self::$pendingActionsForJs as $action) {
			$actions[] = $action->serializeForJs(false);
		}

		$collection = [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'actions' => $actions,
		];

		return sprintf(
			'if (AjawV2 && AjawV2.registerActions) { AjawV2.registerActions(%s); };',
			wp_json_encode($collection)
		);
	}

	protected function serializeForJs($includeAjaxUrl = true) {
		$config = ['action' => $this->getAction()];
		if ( $this->isNonceCheckEnabled() ) {
			$config['nonce'] = wp_create_nonce($this->getAction());
		}
		$requiredMethod = $this->getRequiredRequestMethod();
		if ( $requiredMethod !== null ) {
			$config['requiredMethod'] = $requiredMethod;
		}

		if ( $includeAjaxUrl ) {
			$config['ajaxUrl'] = admin_url('admin-ajax.php');
		}
		return $config;
	}

	/**
	 * @internal Hook callbacks must be public but shouldn't be called directly.
	 */
	public static function outputRemainingActionsForJs() {
		if ( empty(self::$pendingActionsForJs) || !self::$scriptRegistered ) {
			return;
		}

		if ( wp_script_is(self::$scriptHandle, 'done') ) {
			echo '<script type="text/javascript">';
			//Generated JS should be safe and cannot be HTML-escaped anyway.
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::generateActionJs();
			echo '</script>';
		} else {
			//The script is registered but not yet printed. For example, it could be queued for
			//the footer. We can still add an inline script to it.
			wp_add_inline_script(self::$scriptHandle, self::generateActionJs(), 'after');
		}

		self::$pendingActionsForJs = [];
	}

	/**
	 * Serialize an associative array of Action objects in a form that can be JSON-encoded
	 * and passed to JavaScript.
	 *
	 * You can then use AjawV2.createActionMap() to deserialize that data into a JavaScript
	 * object with the same keys as the input array (or, optionally, with a subset of the keys
	 * if you use AjawV2.createStrictActionMap()). Each value in the output object will be
	 * an AjaxAction object that can be used to perform the action.
	 *
	 * @param array<string,Action> $actions
	 * @return array
	 */
	public static function serializeActionMap($actions) {
		$actionSettings = [];
		foreach ($actions as $key => $action) {
			if ( !($action instanceof Action) ) {
				throw new \InvalidArgumentException(sprintf(
					'Expected an instance of %s, got %s.',
					self::class,
					is_object($action) ? get_class($action) : gettype($action)
				));
			}
			$actionSettings[$key] = $action->serializeForJs(false);
		}
		return [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'actions' => $actionSettings,
		];
	}

	/** @noinspection PhpLanguageLevelInspection */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->serializeForJs(true);
	}
	//endregion
}