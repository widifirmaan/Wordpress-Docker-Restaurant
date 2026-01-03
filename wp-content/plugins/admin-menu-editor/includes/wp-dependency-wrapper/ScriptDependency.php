<?php

namespace YahnisElsts\WpDependencyWrapper\v1;

class ScriptDependency {
	protected $handle;
	protected $url;

	/**
	 * @var array<string|self> List of handles or dependency objects.
	 */
	protected $dependencies = [];

	/**
	 * Version number.
	 *
	 * The meaning of `null` and `false` is the same as in wp_register_script().
	 *
	 * @var string|null|false
	 */
	protected $version = false;

	/**
	 * @var bool Whether to automatically generate the version number based on the file
	 * modification time.
	 */
	protected $autoDetectVersion = true;

	protected $inFooter = false;
	protected $absoluteFilePath = null;
	protected $pathDetectionDone = false;

	protected $isRegistered = false;
	protected $isEnqueued = false;

	protected $jsVariables = [];

	/**
	 * @var array<string,bool> Map of global JS variable names that have not been added to the script yet.
	 */
	protected $pendingJsVariables = [];

	protected $pendingInlineScripts = [];

	protected static $customAttributesByHandle = [];
	/**
	 * @var self[]
	 */
	protected static $instancesWithPendingInlineScripts = [];
	protected static $isTagFilterAdded = false;

	/**
	 * Hook priority for auto-registration.
	 *
	 * Register before the default hooks (priority 10) in case this dependency
	 * is used by another script or style that's registered without using this class.
	 */
	const AUTO_REGISTER_PRIORITY = 8;

	/**
	 * Hook priority for auto-enqueue.
	 *
	 * Enqueue after registration and after default hooks in case this dependency
	 * has its own dependencies that need to be registered first.
	 */
	const AUTO_ENQUEUE_PRIORITY = 12;

	/**
	 * @var array<string,self>
	 */
	protected static $pendingRegistrations = [];
	/**
	 * @var array<string,self>
	 */
	protected static $pendingEnqueues = [];
	protected static $autoRegistrationHooksAdded = false;

	protected static $generatedHandleCounter = 0;

	/**
	 * @param string $url
	 * @param string $scriptHandle
	 * @param string|null $absoluteFilePath
	 */
	public function __construct($url, $scriptHandle, $absoluteFilePath = null) {
		$this->url = $url;
		$this->handle = $scriptHandle;
		$this->absoluteFilePath = $absoluteFilePath;
	}

	/**
	 * @return string
	 */
	public function getHandle() {
		return $this->handle;
	}

	public function addJsVariable($globalVariableName, $rawPhpValue, $position = 'before') {
		return $this->internalAddJsVariable(
			$globalVariableName,
			$rawPhpValue,
			null,
			$position
		);
	}

	public function addLazyJsVariable(
		$globalVariableName,
		$callback,
		$position = 'before',
		$delayUntilTagOutput = false
	) {
		if ( empty($callback) ) {
			throw new \InvalidArgumentException('Callback must be provided.');
		}
		return $this->internalAddJsVariable($globalVariableName, null, $callback, $position, $delayUntilTagOutput);
	}

	protected function internalAddJsVariable(
		$globalVariableName,
		$value,
		$callback,
		$position,
		$delayUntilTagOutput = false
	) {
		$this->jsVariables[$globalVariableName] = [
			'value'    => $value,
			'callback' => $callback,
			'position' => $position,
			'delayed'  => $delayUntilTagOutput,
		];

		//WordPress only allows adding inline scripts after the script has been registered.
		if ( $this->isRegistered() && !$delayUntilTagOutput ) {
			wp_add_inline_script(
				$this->handle,
				$this->generateJsVariableCode($globalVariableName),
				$position
			);
		} else {
			$this->pendingJsVariables[$globalVariableName] = true;
			self::$instancesWithPendingInlineScripts[$this->handle] = $this;
			self::addTagFilter();
		}

		return $this;
	}

	protected function generateJsVariableCode($globalVariableName) {
		if ( isset($this->jsVariables[$globalVariableName]['callback']) ) {
			$rawPhpValue = call_user_func($this->jsVariables[$globalVariableName]['callback']);
		} else {
			$rawPhpValue = $this->jsVariables[$globalVariableName]['value'];
		}
		return sprintf(
			'window.%s = (%s);',
			$globalVariableName,
			wp_json_encode($rawPhpValue, JSON_HEX_TAG)
		);
	}

	/**
	 * @param string $jsCode
	 * @param string $position
	 * @return $this
	 */
	public function addInlineScript($jsCode, $position = 'after') {
		return $this->internalAddInlineScript($jsCode, null, $position);
	}

	/**
	 * @param callable $callback
	 * @param string $position
	 * @param bool $delayUntilTagOutput
	 * @return $this
	 */
	public function addLazyInlineScript($callback, $position = 'after', $delayUntilTagOutput = false) {
		if ( empty($callback) ) {
			throw new \InvalidArgumentException('Callback must be provided.');
		}
		return $this->internalAddInlineScript(null, $callback, $position, $delayUntilTagOutput);
	}

	protected function internalAddInlineScript($jsCode, $callback, $position, $delayUntilTagOutput = false) {
		if ( $this->isRegistered() && !$delayUntilTagOutput ) {
			if ( !empty($callback) ) {
				$jsCode = call_user_func($callback);
			}
			wp_add_inline_script($this->handle, $jsCode, $position);
		} else {
			$this->pendingInlineScripts[] = [
				'code'     => $jsCode,
				'callback' => $callback,
				'position' => $position,
				'delayed'  => $delayUntilTagOutput,
			];
			self::$instancesWithPendingInlineScripts[$this->handle] = $this;
			self::addTagFilter();
		}
		return $this;
	}

	protected function getInlineScriptCode($scriptProperties) {
		if ( isset($scriptProperties['callback']) ) {
			$jsCode = call_user_func($scriptProperties['callback']);
		} else {
			$jsCode = $scriptProperties['code'];
		}
		return $jsCode;
	}

	/**
	 * @param string|self|null ...$scriptHandles
	 * @return $this
	 */
	public function addDependencies(...$scriptHandles) {
		$this->dependencies = array_merge($this->dependencies, $scriptHandles);
		return $this;
	}

	/**
	 * Set the version number.
	 *
	 * The meaning of `false` and `null` is the same as in `wp_register_script()`.
	 *
	 * @param string|false|null $version
	 * @return $this
	 */
	public function setVersion($version) {
		$this->version = $version;
		$this->autoDetectVersion = false;
		return $this;
	}

	/**
	 * Add the dependency to the footer instead of the header.
	 *
	 * @return $this
	 */
	public function setInFooter() {
		$this->inFooter = true;
		return $this;
	}

	/**
	 * Add a custom attribute to the script tag.
	 *
	 * @param string $attribute
	 * @param string|bool $value If the value is `true`, the attribute will be added without a value.
	 * @return $this
	 */
	public function addAttribute($attribute, $value) {
		if ( !isset(self::$customAttributesByHandle[$this->handle]) ) {
			self::$customAttributesByHandle[$this->handle] = [];
		}
		self::$customAttributesByHandle[$this->handle][$attribute] = $value;

		self::addTagFilter();
		return $this;
	}

	public function removeAttribute($attribute) {
		if ( array_key_exists($attribute, self::$customAttributesByHandle[$this->handle]) ) {
			unset(self::$customAttributesByHandle[$this->handle][$attribute]);
		}
		return $this;
	}

	protected static function addTagFilter() {
		if ( !self::$isTagFilterAdded ) {
			add_filter('script_loader_tag', [self::class, 'filterScriptTag'], 10, 2);
			self::$isTagFilterAdded = true;
		}
	}

	/**
	 * @param string $tag
	 * @param string $handle
	 * @return string
	 * @internal
	 */
	public static function filterScriptTag($tag, $handle) {
		//Add custom attributes.
		if ( !empty(self::$customAttributesByHandle[$handle]) ) {
			$attributePairs = [];
			foreach (self::$customAttributesByHandle[$handle] as $attribute => $value) {
				if ( $value === true ) {
					$attributePairs[] = $attribute;
				} else {
					$attributePairs[] = $attribute . '="' . esc_attr($value) . '"';
				}
			}
			if ( !empty($attributePairs) ) {
				//The $tag can actually contain multiple <script> tags if the script has
				//any inline scripts or translations. We need to find the one that has
				//the "src" attribute.
				$tag = preg_replace(
					'/(<script)([^>]+src=[^>]+>)/i',
					'$1 ' . implode(' ', $attributePairs) . ' $2',
					$tag
				);
			}
		}

		//Add pending inline scripts.
		if ( !empty(self::$instancesWithPendingInlineScripts[$handle]) ) {
			$script = self::$instancesWithPendingInlineScripts[$handle];

			$inlineScripts = ['before' => '', 'after' => ''];
			//Variables first.
			foreach ($script->pendingJsVariables as $varName => $unused) {
				$inlineScripts[$script->jsVariables[$varName]['position']] .=
					$script->generateJsVariableCode($varName) . "\n";
			}
			$script->pendingJsVariables = [];

			//Inline scripts after variables.
			foreach ($script->pendingInlineScripts as $scriptProperties) {
				$jsCode = $script->getInlineScriptCode($scriptProperties);
				$inlineScripts[$scriptProperties['position']] .= $jsCode . "\n";
			}
			$script->pendingInlineScripts = [];

			if ( !empty($inlineScripts['before']) ) {
				$tag = self::generateInlineScriptTag(
						$inlineScripts['before'],
						$script->handle,
						'before'
					) . $tag;
			}
			if ( !empty($inlineScripts['after']) ) {
				$tag = $tag . self::generateInlineScriptTag(
						$inlineScripts['after'],
						$script->handle,
						'after'
					);
			}

			unset(self::$instancesWithPendingInlineScripts[$handle]);
		}

		return $tag;
	}

	protected static function generateInlineScriptTag($code, $handle, $position) {
		$id = $handle . '-ws-sc-' . $position;
		/** @noinspection BadExpressionStatementJS -- "%s" is a sprintf() placeholder, not actual JS. */
		return sprintf(
			"<script id=\"%s\">\n%s\n</script>\n",
			esc_attr($id),
			$code
		);
	}

	/**
	 * Set the "type" attribute of the script tag.
	 *
	 * @param string $scriptType Typically either "text/javascript" or "module". If the script
	 *                           is not a module, the "type" attribute can be omitted.
	 * @return $this
	 */
	public function setType($scriptType) {
		if ( ($scriptType === '') || !is_string($scriptType) ) {
			return $this->removeAttribute('type');
		}
		return $this->addAttribute('type', $scriptType);
	}

	/**
	 * Set the "type" attribute of the script tag to "module".
	 * This is a shortcut for `setType('module')`.
	 *
	 * @return $this
	 */
	public function setTypeToModule() {
		return $this->setType('module');
	}

	public function setAsync() {
		return $this->addAttribute('async', true);
	}

	/**
	 * Get the specified or auto-detected version number.
	 *
	 * @return string|null|false
	 */
	public function getEffectiveVersion() {
		if ( $this->autoDetectVersion ) {
			$fileName = $this->getAbsoluteFilePath();
			if ( !empty($fileName) && is_file($fileName) ) {
				$mtime = filemtime($fileName);
				if ( $mtime !== false ) {
					$this->version = (string)$mtime;
				}
			}
			$this->autoDetectVersion = false; //Already detected.
		}
		return $this->version;
	}

	/**
	 * @return string|null
	 */
	public function getAbsoluteFilePath() {
		if ( ($this->absoluteFilePath !== null) || $this->pathDetectionDone ) {
			return $this->absoluteFilePath;
		}

		$this->absoluteFilePath = self::guessFilePathFromUrl($this->url);
		$this->pathDetectionDone = true;

		return $this->absoluteFilePath;
	}

	/**
	 * Attempt to determine the full path to a file that's part of a plugin, theme,
	 * or WordPress core based on its URL.
	 *
	 * This function does not check if the file actually exists. It also might not
	 * work when complex rewrite rules are used.
	 *
	 * @param string $dependencyUrl
	 * @return string|null
	 */
	protected static function guessFilePathFromUrl($dependencyUrl) {
		static $baseUrlMap = null;
		if ( $baseUrlMap === null ) {
			$baseUrlMap = [
				plugins_url()                               => WP_PLUGIN_DIR,
				plugins_url('', WPMU_PLUGIN_DIR . '/dummy') => WPMU_PLUGIN_DIR,
				get_stylesheet_directory_uri()              => get_stylesheet_directory(),
				get_template_directory_uri()                => get_template_directory(),
				content_url()                               => WP_CONTENT_DIR,
				site_url('/' . WPINC)                       => ABSPATH . WPINC,
			];
		}

		$fileName = null;
		foreach ($baseUrlMap as $baseUrl => $directory) {
			$baseUrlLength = strlen($baseUrl);
			if ( substr($dependencyUrl, 0, $baseUrlLength) === $baseUrl ) {
				$fileName = $directory . '/' . substr($dependencyUrl, $baseUrlLength);
				//Remove the query string.
				list($fileName,) = explode('?', $fileName, 2);
				break;
			}
		}

		return $fileName;
	}

	public function getUrl() {
		return $this->url;
	}

	/**
	 * Register the dependency with WordPress immediately.
	 *
	 * You might not need to call this method directly. The dependency will be
	 * registered automatically when one of the following happens:
	 * - enqueue() is called.
	 * - This object has been added to another dependency using addDependency(),
	 *   and that dependency gets registered or enqueued.
	 *
	 * It's safe to call this method multiple times. It will only register
	 * the dependency once (unless it gets deregistered in the meantime).
	 *
	 * @return $this
	 */
	public function register() {
		if ( $this->isRegistered() ) {
			return $this;
		}

		//Prepare and register dependencies where needed.
		$preparedDependencies = [];
		foreach ($this->dependencies as $dependency) {
			if ( $dependency instanceof ScriptDependency ) {
				$dependency->register();
				$preparedDependencies[] = $dependency->handle;
			} else if ( !empty($dependency) ) {
				$preparedDependencies[] = $dependency;
			}
		}

		wp_register_script(
			$this->handle,
			$this->getUrl(),
			$preparedDependencies,
			$this->getEffectiveVersion(),
			$this->inFooter
		);
		$this->isRegistered = true;

		//Add pending JS variables and mark them as no longer pending.
		foreach ($this->pendingJsVariables as $variableName => $unused) {
			if ( !empty($this->jsVariables[$variableName]['delayed']) ) {
				//This variable will wait for the script tag filter.
				continue;
			}

			wp_add_inline_script(
				$this->handle,
				$this->generateJsVariableCode($variableName),
				$this->jsVariables[$variableName]['position']
			);
			unset($this->pendingJsVariables[$variableName]);
		}

		//Add pending inline scripts.
		foreach ($this->pendingInlineScripts as $key => $scriptProperties) {
			if ( !empty($scriptProperties['delayed']) ) {
				continue;
			}

			$jsCode = $this->getInlineScriptCode($scriptProperties);
			wp_add_inline_script($this->handle, $jsCode, $scriptProperties['position']);
			unset($this->pendingInlineScripts[$key]);
		}

		if ( empty($this->pendingInlineScripts) && empty($this->pendingJsVariables) ) {
			unset(self::$instancesWithPendingInlineScripts[$this->handle]);
		}

		return $this;
	}

	protected function canRegisterDepsNow() {
		//If you register scripts or styles too early, or even *check* if they are registered
		//too early, WordPress will complain that you're "doing it wrong". So we have to wait
		//until the appropriate action has been fired.
		//Optimization: Once the required action has been fired, we don't need to check again.
		static $thresholdReached = false;
		if ( $thresholdReached ) {
			return true;
		}

		$thresholdReached = (
			did_action('init') || did_action('wp_enqueue_scripts')
			|| did_action('admin_enqueue_scripts') || did_action('login_enqueue_scripts')
		);
		return $thresholdReached;
	}

	/**
	 * Check if the dependency has been registered with WordPress.
	 *
	 * @return bool
	 */
	public function isRegistered() {
		if ( !$this->canRegisterDepsNow() ) {
			return $this->isRegistered;
		}

		$this->isRegistered = wp_script_is($this->handle, 'registered');
		return $this->isRegistered;
	}

	/**
	 * Check if the dependency has been enqueued.
	 *
	 * @return bool
	 */
	public function isEnqueued() {
		if ( !$this->canRegisterDepsNow() ) {
			return $this->isEnqueued;
		}

		/** @noinspection PhpRedundantOptionalArgumentInspection -- Let's make it explicit. */
		$this->isEnqueued = wp_script_is($this->handle, 'enqueued');
		return $this->isEnqueued;
	}


	/**
	 * Register the dependency during the appropriate action, or immediately if the action
	 * has already been fired.
	 *
	 * Note that this may never actually happen if the current request is not one
	 * that usually loads scripts and styles (e.g. a REST API request). If you still
	 * want to register a dependency in situations like that, add your own hook
	 * callback and use register() instead.
	 *
	 * @return $this
	 */
	public function autoRegister() {
		$properAction = self::getRegistrationAction();
		if ( did_action($properAction) ) {
			$this->register();
		} else {
			self::$pendingRegistrations[$this->handle] = $this;
			self::addAutoRegistrationHooks();
		}
		return $this;
	}

	protected static function getRegistrationAction() {
		if ( is_admin() ) {
			return 'admin_enqueue_scripts';
		} else if (
			(function_exists('is_login') && is_login())
			|| (isset($GLOBALS['pagenow']) && ($GLOBALS['pagenow'] === 'wp-login.php'))
		) {
			return 'login_enqueue_scripts';
		} else {
			return 'wp_enqueue_scripts';
		}
	}

	protected static function addAutoRegistrationHooks() {
		if ( self::$autoRegistrationHooksAdded ) {
			return;
		}

		$targetAction = self::getRegistrationAction();
		add_action($targetAction, [__CLASS__, 'registerPendingDependencies'], self::AUTO_REGISTER_PRIORITY);
		add_action($targetAction, [__CLASS__, 'enqueuePendingDependencies'], self::AUTO_ENQUEUE_PRIORITY);
		self::$autoRegistrationHooksAdded = true;
	}

	/**
	 * @return void
	 * @internal
	 */
	public static function registerPendingDependencies() {
		foreach (self::$pendingRegistrations as $dependency) {
			$dependency->register();
		}
		self::$pendingRegistrations = [];
	}

	/**
	 * @return void
	 * @internal
	 */
	public static function enqueuePendingDependencies() {
		foreach (self::$pendingEnqueues as $dependency) {
			$dependency->enqueue();
		}
		self::$pendingEnqueues = [];
	}

	/**
	 * Enqueue the dependency immediately.
	 *
	 * @return $this
	 */
	public function enqueue() {
		$this->register();
		wp_enqueue_script($this->handle);
		$this->isEnqueued = true;
		return $this;
	}

	/**
	 * Enqueue the dependency during the appropriate action, or immediately if the action
	 * has already been fired.
	 *
	 * @return $this
	 */
	public function autoEnqueue() {
		$properAction = self::getRegistrationAction();
		if ( did_action($properAction) ) {
			$this->enqueue();
		} else {
			self::$pendingEnqueues[$this->handle] = $this;
			self::addAutoRegistrationHooks();
		}
		return $this;
	}

	/**
	 * @param string $url
	 * @param string|null $handle Optional. If omitted, a handle will be generated automatically.
	 * @param string|null $fullPath
	 * @param array<string|ScriptDependency> $initialDeps
	 * @return static
	 */
	public static function create($url, $handle = null, $fullPath = null, $initialDeps = []) {
		if ( $handle === null ) {
			$handle = self::generateHandle($url);
		}
		$instance = new static($url, $handle, $fullPath);
		$instance->addDependencies(...$initialDeps);
		return $instance;
	}

	protected static function generateHandle($url) {
		self::$generatedHandleCounter++;
		$handle = 'wsdep-ah' . self::$generatedHandleCounter;

		$parsedUrl = wp_parse_url($url);
		if ( empty($parsedUrl) || empty($parsedUrl['path']) ) {
			return $handle;
		}

		$path = $parsedUrl['path'];
		$suffix = sanitize_key(pathinfo($path, PATHINFO_FILENAME));
		if ( !empty($suffix) ) {
			$handle .= '-' . $suffix;
		}
		return $handle;
	}
}