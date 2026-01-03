<?php

namespace YahnisElsts\AdminMenuEditor\ContentPermissions\Policy;

use YahnisElsts\AdminMenuEditor\Actors\Actor;

class ContentItemPolicy implements \JsonSerializable {
	const EFFECT_ALLOW = 'allow';
	const EFFECT_DENY = 'deny';

	private $actorAccess;
	/**
	 * @var string
	 */
	private $replacementContent;
	/**
	 * @var AccessProtectionSettings
	 */
	private $accessProtectionSettings;
	/**
	 * @var bool
	 */
	private $preferAdvancedMode;

	public function __construct(
		$actorAccess,
		$replacementContent = '',
		?AccessProtectionSettings $accessProtectionSettings = null,
		$preferAdvancedMode = false
	) {
		$this->actorAccess = $actorAccess;
		$this->replacementContent = $replacementContent;
		$this->preferAdvancedMode = $preferAdvancedMode;
		if ( $accessProtectionSettings ) {
			$this->accessProtectionSettings = $accessProtectionSettings;
		} else {
			$this->accessProtectionSettings = new AccessProtectionSettings();
		}
	}

	/**
	 * @param Actor $actor
	 * @param Action $action
	 * @param ContentItemPolicy|null $childObjectPolicy
	 * @param mixed $objectId ID of the object that this policy is evaluated for. Usually a post ID.
	 * @return EvaluationResult|null
	 */
	public function evaluate(Actor $actor, Action $action, ?ContentItemPolicy $childObjectPolicy = null, $objectId = null) {
		$bestMatchEffect = null;
		$bestMatchPriority = -1;

		$actionName = $action->getName();
		$flattedActors = $actor->flatten();
		//flatten() returns actors sorted by priority, from highest to lowest.

		foreach ($flattedActors as $componentActor) {
			$actorId = $componentActor->getId();
			$priority = $componentActor->getPriority();

			//If we already have a match with higher priority than this, we can skip the rest.
			if ( $bestMatchEffect && ($priority < $bestMatchPriority) ) {
				break;
			}

			if ( isset($this->actorAccess[$actorId][$actionName]) ) {
				$effect = $this->actorAccess[$actorId][$actionName] ? self::EFFECT_ALLOW : self::EFFECT_DENY;
				//Prefer "allow" over "deny". An actor with a higher priority could override an "allow"
				//with a "deny", but if we already got this far, we don't have a match with a higher priority.
				if ( $effect === self::EFFECT_ALLOW ) {
					$bestMatchEffect = self::EFFECT_ALLOW;
					break;
				}

				$bestMatchEffect = $effect;
				$bestMatchPriority = $priority;
			}
		}

		if ( $bestMatchEffect === null ) {
			return null;
		}

		return new EvaluationResult(
			$bestMatchEffect,
			$actor,
			$action,
			$this,
			$childObjectPolicy,
			$objectId
		);
	}

	public function getReplacementContent() {
		if ( $this->replacementContent !== '' ) {
			return $this->replacementContent;
		}
		return '<p>' . static::getDefaultReplacementText() . '</p>';
	}

	public static function getDefaultReplacementText() {
		return _x(
			"You don't have access to this content.",
			'content permissions: default replacement text',
			'admin-menu-editor'
		);
	}

	/**
	 * @return AccessProtection|null
	 */
	public function getDirectAccessProtection() {
		return $this->accessProtectionSettings->getActiveProtection();
	}

	/**
	 * Iterates over all entries in the policy that match one of the given actions and
	 * calls the callback for each entry.
	 *
	 * The callback receives the following parameters:
	 * - string $actorId
	 * - string $actionName
	 * - bool $isAllowed
	 *
	 * @param Action[] $actions
	 * @param callable $callback
	 * @return void
	 */
	public function forEachActionEntry($actions, $callback) {
		if ( !isset($this->actorAccess) ) {
			return;
		}

		foreach ($actions as $action) {
			$actionName = $action->getName();
			foreach ($this->actorAccess as $actorId => $actionSettings) {
				if ( isset($actionSettings[$actionName]) ) {
					$callback($actorId, $actionName, $actionSettings[$actionName]);
				}
			}
		}
	}

	/**
	 * @param Actor $actor
	 * @param Action $action
	 * @param bool|null $isAllowed
	 * @return void
	 */
	public function setActorPermission(Actor $actor, Action $action, $isAllowed) {
		$actorId = $actor->getId();
		$actionName = $action->getName();

		if ( $isAllowed === null ) {
			unset($this->actorAccess[$actorId][$actionName]);
			if ( empty($this->actorAccess[$actorId]) ) {
				unset($this->actorAccess[$actorId]);
			}
			return;
		}

		if ( !isset($this->actorAccess[$actorId]) ) {
			$this->actorAccess[$actorId] = [];
		}
		$this->actorAccess[$actorId][$actionName] = $isAllowed;
	}

	/**
	 * Check if the policy has a custom permission setting for the given actor and action.
	 *
	 * @param Actor $actor
	 * @param Action $action
	 * @return bool
	 */
	public function hasPermissionSettingFor(Actor $actor, Action $action) {
		return isset($this->actorAccess[$actor->getId()][$action->getName()]);
	}

	/** @noinspection PhpLanguageLevelInspection */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		$data = [];

		//Only serialize non-empty arrays.
		$actorAccess = array_filter($this->actorAccess, function ($actions) {
			return !empty($actions);
		});
		if ( !empty($actorAccess) ) {
			$data['actorAccess'] = $actorAccess;
		}

		if ( $this->replacementContent !== '' ) {
			$data['replacementContent'] = $this->replacementContent;
		}

		if ( $this->accessProtectionSettings ) {
			$serializedProtection = $this->accessProtectionSettings->jsonSerialize();
			if ( !empty($serializedProtection) ) {
				$data['accessProtection'] = $serializedProtection;
			}
		}

		if ( $this->preferAdvancedMode ) {
			$data['preferAdvancedMode'] = true;
		}

		return $data;
	}

	public static function fromArray($properties) {
		$actorAccess = [];
		if ( isset($properties['actorAccess']) && is_array($properties['actorAccess']) ) {
			$actorAccess = $properties['actorAccess'];
		}

		$replacementContent = isset($properties['replacementContent']) ? strval($properties['replacementContent']) : '';

		$accessProtectionSettings = null;
		if ( isset($properties['accessProtection']) ) {
			$accessProtectionSettings = AccessProtectionSettings::fromArray($properties['accessProtection']);
		}

		$preferAdvancedMode = isset($properties['preferAdvancedMode']) && $properties['preferAdvancedMode'];

		return new static($actorAccess, $replacementContent, $accessProtectionSettings, $preferAdvancedMode);
	}
}

class PolicyStore {
	const POST_POLICY_META_KEY = '_ame_cpe_post_policy';
	const HIDDEN_ITEM_LOOKUP_OPTION = 'ame_cpe_restricted_items';

	/**
	 * @var RestrictedPostLookup|null
	 */
	private $restrictedPostLookup = null;

	/**
	 * @var ActionRegistry
	 */
	private $actionRegistry;

	/**
	 * @var array Composite key => array of post IDs.
	 */
	private $hiddenListItemCache = [];

	public function __construct(ActionRegistry $actionRegistry) {
		$this->actionRegistry = $actionRegistry;

		//Remove deleted posts from the lookup.
		add_action('deleted_post', [$this, 'forgetDeletedPost']);
	}

	/**
	 * @param int $postId
	 * @return ContentItemPolicy|null
	 */
	public function getPostPolicy($postId) {
		$serializedPolicy = get_post_meta($postId, self::POST_POLICY_META_KEY, true);
		if ( !empty($serializedPolicy) ) {
			$properties = json_decode($serializedPolicy, true);
			if ( is_array($properties) ) {
				return ContentItemPolicy::fromArray($properties);
			}
		}
		return null;
	}

	/**
	 * @param int $postId
	 * @param ContentItemPolicy $policy
	 * @return void
	 */
	public function setPostPolicy($postId, ContentItemPolicy $policy) {
		update_post_meta($postId, self::POST_POLICY_META_KEY, wp_slash(wp_json_encode($policy)));

		//Clear policy related caches.
		$this->hiddenListItemCache = [];

		//Update the central lookup that stores hidden posts.
		$lookup = $this->getRestrictedPostLookup();
		$lookup->forgetPost($postId);

		$policy->forEachActionEntry(
			[
				$this->actionRegistry->getAction(ActionRegistry::ACTION_READ),
				$this->actionRegistry->getAction(ActionRegistry::ACTION_VIEW_IN_LISTS),
			],
			function ($actorId, $actionName, $isAllowed) use ($lookup, $postId) {
				$action = $this->actionRegistry->getAction($actionName);
				$lookup->addEntry($actorId, $action, Action::OBJECT_TYPE_POST, $postId, $isAllowed);
			}
		);

		if ( $lookup->isModified() ) {
			$this->saveRestrictedPostLookup($lookup);
		}
	}

	/**
	 * @param Actor $actor
	 * @param string[]|null $postTypes
	 * @return int[]
	 */
	public function getPostsHiddenFromLists($actor, $postTypes = null) {
		if ( is_array($postTypes) && empty($postTypes) ) {
			return [];
		}

		$cacheKey = $this->getHiddenListItemCacheKey(Action::OBJECT_TYPE_POST, $actor, $postTypes);
		if ( isset($this->hiddenListItemCache[$cacheKey]) ) {
			return $this->hiddenListItemCache[$cacheKey];
		}

		$lookup = $this->getRestrictedPostLookup();
		$restrictedPosts = $lookup->getRestrictedPosts(
			$actor,
			$this->actionRegistry->getAction(ActionRegistry::ACTION_VIEW_IN_LISTS),
			$postTypes
		);

		$this->hiddenListItemCache[$cacheKey] = $restrictedPosts;
		return $restrictedPosts;
	}

	/**
	 * Check if there might be *any* posts that the current user isn't allowed to read or view in lists.
	 *
	 * @return bool
	 */
	public function mightHaveHiddenPosts(Actor $actor) {
		$lookup = $this->getRestrictedPostLookup();
		$actions = [
			$this->actionRegistry->getAction(ActionRegistry::ACTION_READ),
			$this->actionRegistry->getAction(ActionRegistry::ACTION_VIEW_IN_LISTS),
		];
		return $lookup->hasPotentialRestrictions($actor, $actions);
	}

	private function getRestrictedPostLookup() {
		if ( $this->restrictedPostLookup === null ) {
			$storedData = get_option(self::HIDDEN_ITEM_LOOKUP_OPTION, '{}');
			$parsed = json_decode((string)$storedData, true);

			if ( !empty($parsed) && is_array($parsed) ) {
				$this->restrictedPostLookup = new RestrictedPostLookup($parsed);
			} else {
				$this->restrictedPostLookup = new RestrictedPostLookup();
			}
		}
		return $this->restrictedPostLookup;
	}

	private function saveRestrictedPostLookup(RestrictedPostLookup $lookup) {
		update_option(self::HIDDEN_ITEM_LOOKUP_OPTION, wp_json_encode($lookup));
	}

	/**
	 * Remove a post from the restricted post lookup when it's deleted.
	 *
	 * This is a hook callback and should not be called directly.
	 *
	 * @param int $postId
	 * @return void
	 * @internal
	 */
	public function forgetDeletedPost($postId) {
		if ( !is_numeric($postId) || ($postId <= 0) ) {
			return;
		}

		$lookup = $this->getRestrictedPostLookup();
		$lookup->forgetPost($postId);
		if ( $lookup->isModified() ) {
			$this->saveRestrictedPostLookup($lookup);
		}
	}

	/**
	 * @param string $objectType
	 * @param Actor $actor
	 * @param array|null $objectWpTypes
	 * @return string
	 */
	private function getHiddenListItemCacheKey($objectType, Actor $actor, $objectWpTypes) {
		$key = $objectType . '|' . $actor->getId() . '|';
		if ( $objectWpTypes ) {
			$key .= implode(',', $objectWpTypes);
		}
		return $key;
	}
}

class Action implements \JsonSerializable {
	protected $name;

	/**
	 * @var string|null
	 */
	protected $label = null;
	/**
	 * @var null|callable
	 */
	protected $labelGenerator = null;

	/**
	 * @var string|null
	 */
	protected $description = null;
	/**
	 * @var null|callable
	 */
	protected $descriptionGenerator = null;

	const OBJECT_TYPE_POST = 'post';
	const OBJECT_TYPE_TERM = 'term';

	protected $supportedObjectTypes = [];
	/**
	 * @var string|null
	 */
	private $postMetaCap;

	/**
	 * @param string $name
	 * @param array $supportedObjectTypes
	 * @param string|callable $label
	 * @param string|callable $description
	 * @param string|null $postMetaCap
	 */
	public function __construct(
		$name,
		array $supportedObjectTypes,
		$label,
		$description = '',
		$postMetaCap = null
	) {
		$this->name = $name;
		$this->supportedObjectTypes = $supportedObjectTypes;
		$this->postMetaCap = $postMetaCap;

		if ( is_string($label) ) {
			$this->label = $label;
		} else if ( $label !== null ) {
			$this->labelGenerator = $label;
		}

		if ( is_string($description) ) {
			$this->description = $description;
		} else if ( $description !== null ) {
			$this->descriptionGenerator = $description;
		}
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		if ( ($this->label === null) && is_callable($this->labelGenerator) ) {
			$this->label = call_user_func($this->labelGenerator);
		}
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		if ( ($this->description === null) && is_callable($this->descriptionGenerator) ) {
			$this->description = call_user_func($this->descriptionGenerator);
		}
		return $this->description;
	}

	/**
	 * Checks if the given object type is supported by this action.
	 *
	 * @param string $objectType
	 * @param mixed $nativeObject The specific instance of the object type. This can be a post object, term object, etc.
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection ($objectInstance can be used in subclasses)
	 */
	public function appliesToObject($objectType, $nativeObject) {
		return in_array($objectType, $this->supportedObjectTypes, true);
	}

	/**
	 * @return string|null
	 */
	public function getPostMetaCap() {
		return $this->postMetaCap;
	}

	/** @noinspection PhpLanguageLevelInspection */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'name'        => $this->name,
			'label'       => $this->getLabel(),
			'description' => $this->getDescription(),
		];
	}
}

class ActionRegistry {
	const ACTION_EDIT = 'edit';
	const ACTION_DELETE = 'delete';
	const ACTION_READ = 'read';
	const ACTION_VIEW_IN_LISTS = 'view_in_lists';
	const ACTION_PUBLISH = 'publish';

	/**
	 * @var Action[]
	 */
	protected $actions = [];

	public function __construct() {
		$this->addAction(new Action(
			self::ACTION_READ,
			[Action::OBJECT_TYPE_POST, Action::OBJECT_TYPE_TERM,],
			function () {
				return _x('Read', 'content permissions: action name', 'admin-menu-editor');
			},
			'',
			'read_post'
		));
		$this->addAction(new Action(
			self::ACTION_VIEW_IN_LISTS,
			[Action::OBJECT_TYPE_POST, Action::OBJECT_TYPE_TERM],
			function () {
				return _x('View in lists', 'content permissions: action name', 'admin-menu-editor');
			},
			function () {
				return __(
					'Applies to places like the post list in the admin dashboard and archive pages on the front end.',
					'admin-menu-editor'
				);
			},
			'read_post'
		));
		$this->addAction(new Action(
			self::ACTION_EDIT,
			[Action::OBJECT_TYPE_POST, Action::OBJECT_TYPE_TERM],
			function () {
				return _x('Edit', 'content permissions: action name', 'admin-menu-editor');
			},
			'',
			'edit_post'
		));
		$this->addAction(new Action(
			self::ACTION_DELETE,
			[Action::OBJECT_TYPE_POST, Action::OBJECT_TYPE_TERM],
			function () {
				return _x('Delete', 'content permissions: action name', 'admin-menu-editor');
			},
			'',
			'delete_post'
		));
		$this->addAction(new Action(
			self::ACTION_PUBLISH,
			[Action::OBJECT_TYPE_POST],
			function () {
				return _x('Publish', 'content permissions: action name', 'admin-menu-editor');
			},
			'',
			'publish_post'
		));
	}

	public function addAction(Action $action) {
		$this->actions[$action->getName()] = $action;
	}

	/**
	 * @param string $name
	 * @return Action|null
	 */
	public function getAction($name) {
		return isset($this->actions[$name]) ? $this->actions[$name] : null;
	}

	/**
	 * @param string $objectType
	 * @param mixed $nativeObject
	 * @return Action[]
	 */
	public function getApplicableActions($objectType, $nativeObject = null) {
		$actions = [];
		foreach ($this->actions as $action) {
			if ( $action->appliesToObject($objectType, $nativeObject) ) {
				$actions[] = $action;
			}
		}
		return $actions;
	}
}

class EvaluationResult {
	protected $effect;
	protected $actor;
	protected $action;
	protected $objectId;

	/**
	 * @var ContentItemPolicy $usedAccessPolicy The policy that produced this result.
	 * This can be different from $objectPolicy if the policy was inherited from a parent object.
	 */
	protected $usedAccessPolicy;

	/**
	 * @var ContentItemPolicy|null $objectPolicy The policy directly associated with the object.
	 * This can be NULL if the object doesn't have a policy of its own.
	 */
	protected $objectPolicy;


	public function __construct(
		$effect, Actor $actor, Action $action,
		ContentItemPolicy $policy, ?ContentItemPolicy $objectPolicy = null, $objectId = null
	) {
		$this->effect = $effect;
		$this->actor = $actor;
		$this->action = $action;

		$this->usedAccessPolicy = $policy;
		$this->objectPolicy = $objectPolicy;
		$this->objectId = $objectId;
	}

	public function getEffect() {
		return $this->effect;
	}

	/**
	 * @return Actor
	 */
	public function getActor() {
		return $this->actor;
	}

	/**
	 * @return Action
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @return ContentItemPolicy
	 */
	public function getPolicy() {
		if ( $this->objectPolicy ) {
			return $this->objectPolicy;
		}
		return $this->usedAccessPolicy;
	}

	/**
	 * @return ContentItemPolicy
	 */
	public function getUsedAccessPolicy() {
		return $this->usedAccessPolicy;
	}

	/**
	 * @return ContentItemPolicy
	 */
	public function getObjectPolicy() {
		return $this->objectPolicy;
	}

	/**
	 * @return mixed|null
	 */
	public function getObjectId() {
		return $this->objectId;
	}

	public function isDenied() {
		return $this->effect === ContentItemPolicy::EFFECT_DENY;
	}

	public function isAllowed() {
		return $this->effect === ContentItemPolicy::EFFECT_ALLOW;
	}
}

abstract class AccessProtection implements \JsonSerializable {
	/**
	 * @param \WP_Post $post Current post.
	 * @param \WP_Post[] $posts List of posts for the current query.
	 * @param \WP_Query|null $query The current query, if applicable.
	 * @return \WP_Post[]|null Filtered list of posts, or NULL to leave the list unchanged.
	 */
	abstract public function enforce($post, $posts = [], $query = null);

	abstract protected function toArray();

	/**
	 * @param array $properties
	 * @param ContentItemPolicy|null $ownerPolicy The policy that owns the protection that's being deserialized.
	 * @return AccessProtection
	 */
	public static function fromArray($properties, ?ContentItemPolicy $ownerPolicy = null) {
		if ( !isset($properties['tag']) ) {
			throw new \InvalidArgumentException('Invalid properties array for an access protection object');
		}

		$tag = $properties['tag'];
		switch ($tag) {
			case RedirectProtection::$tag:
				return RedirectProtection::deserializeFromArray($properties);
			case ErrorMessageProtection::$tag:
				//If the error message isn't set, it defaults to the replacement content
				//set for the policy that owns this protection.
				$messageCallback = null;
				if ( $ownerPolicy ) {
					$messageCallback = [$ownerPolicy, 'getReplacementContent'];
				}
				return ErrorMessageProtection::deserializeFromArray($properties, $messageCallback);
			case NotFoundProtection::$tag:
				return new NotFoundProtection();
			case ContentReplacementProtection::$tag:
				return new ContentReplacementProtection();
			default:
				throw new \InvalidArgumentException('Unknown access protection tag: ' . esc_html($tag));
		}
	}

	public function getTag() {
		return static::$tag;
	}

	/** @noinspection PhpLanguageLevelInspection */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->toArray();
	}
}

class RedirectProtection extends AccessProtection {
	protected static $tag = 'redirect';

	const REDIRECT_ACTION = 'template_redirect';
	const DEFAULT_REDIRECT_CODE = 307;

	/**
	 * @var string $targetUrl
	 */
	private $targetUrl;

	private $shortcodesEnabled;
	private $redirectCode;

	public function __construct($targetUrl, $redirectCode = self::DEFAULT_REDIRECT_CODE, $shortcodesEnabled = false) {
		$this->targetUrl = is_string($targetUrl) ? $targetUrl : '';
		$this->shortcodesEnabled = boolval($shortcodesEnabled);

		$code = intval($redirectCode);
		if ( ($code >= 300) && ($code <= 399) ) {
			$this->redirectCode = $code;
		} else {
			$this->redirectCode = self::DEFAULT_REDIRECT_CODE;
		}
	}

	public function enforce($post, $posts = [], $query = null) {
		if ( did_action(self::REDIRECT_ACTION) ) {
			$this->performRedirect();
			return [];
		} else {
			add_action(self::REDIRECT_ACTION, [$this, 'performRedirect']);
		}

		return [];
	}

	public function performRedirect() {
		$url = $this->targetUrl;
		if ( empty($url) ) {
			$url = $this->getFallbackUrl();
		}

		$parsed = wp_parse_url($url);
		if ( empty($parsed) ) {
			$url = $this->getFallbackUrl();
		}

		if ( $this->shortcodesEnabled ) {
			$url = do_shortcode($url);
		}

		//The user provides the redirect URL, and they can choose an external URL if they want.
		//phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		if ( wp_redirect($url, $this->redirectCode) ) {
			exit();
		} else {
			wp_die('Error: Redirect failed', 500);
		}
	}

	private function getFallbackUrl() {
		return home_url();
	}

	protected function toArray() {
		$result = ['tag' => static::$tag];
		if ( $this->redirectCode !== self::DEFAULT_REDIRECT_CODE ) {
			$result['redirectCode'] = $this->redirectCode;
		}
		if ( $this->shortcodesEnabled ) {
			$result['shortcodesEnabled'] = true;
		}
		if ( !empty($this->targetUrl) ) {
			$result['targetUrl'] = $this->targetUrl;
		}
		return $result;
	}

	protected static function deserializeFromArray($properties) {
		return new static(
			isset($properties['targetUrl']) ? strval($properties['targetUrl']) : '',
			isset($properties['redirectCode']) ? intval($properties['redirectCode']) : self::DEFAULT_REDIRECT_CODE,
			!empty($properties['shortcodesEnabled'])
		);
	}
}

class ErrorMessageProtection extends AccessProtection {
	protected static $tag = 'errorMessage';

	const FALLBACK_MESSAGE = 'Access denied';

	/**
	 * @var string
	 */
	private $errorMessage;
	/**
	 * @var callable|null
	 */
	private $backupMessageCallback;

	public function __construct($errorMessage = '', $backupMessageCallback = null) {
		$this->errorMessage = $errorMessage;
		$this->backupMessageCallback = $backupMessageCallback;
	}

	public function enforce($post, $posts = [], $query = null) {
		$message = '';
		if ( !empty($this->errorMessage) ) {
			$message = $this->errorMessage;
		} else if ( !empty($this->backupMessageCallback) ) {
			$message = call_user_func($this->backupMessageCallback);
		}

		if ( empty($message) ) {
			$message = self::FALLBACK_MESSAGE;
		}

		wp_die(wp_kses_post($message), 403);
	}

	protected function toArray() {
		$result = ['tag' => static::$tag];
		if ( !empty($this->errorMessage) ) {
			$result['errorMessage'] = $this->errorMessage;
		}
		return $result;
	}

	protected static function deserializeFromArray($properties, $messageCallback = null) {
		return new static(
			isset($properties['errorMessage']) ? strval($properties['errorMessage']) : '',
			$messageCallback
		);
	}
}

class NotFoundProtection extends AccessProtection {
	protected static $tag = 'notFound';

	public function enforce($post, $posts = [], $query = null) {
		if ( $query instanceof \WP_Query ) {
			$query->set_404();
		}
		return [];
	}

	protected function toArray() {
		return ['tag' => static::$tag];
	}
}

class ContentReplacementProtection extends AccessProtection {
	protected static $tag = 'replace';

	public function enforce($post, $posts = [], $query = null) {
		//The actual replacement is handled by the ContentPermissionsEnforcer class which
		//covers both direct access and indirect access (e.g. via category archives). This
		//class is a placeholder for the protection type, and helps with serialization.
		return null;
	}

	protected function toArray() {
		return ['tag' => static::$tag];
	}
}

class AccessProtectionSettings implements \JsonSerializable {
	/**
	 * @var AccessProtection|null
	 */
	private $activeProtection;
	/**
	 * @var array<string,AccessProtection>
	 */
	private $configuredProtectionTypes;

	public function __construct(?AccessProtection $activeProtection = null, $configuredProtectionTypes = []) {
		$this->activeProtection = $activeProtection;
		$this->configuredProtectionTypes = $configuredProtectionTypes;

		if ( $this->activeProtection ) {
			$this->configuredProtectionTypes[$activeProtection->getTag()] = $activeProtection;
		}
	}

	/**
	 * @return AccessProtection|null
	 */
	public function getActiveProtection() {
		return $this->activeProtection;
	}

	/**
	 * @return array<string,AccessProtection>
	 */
	public function getConfiguredProtectionTypes() {
		return $this->configuredProtectionTypes;
	}

	public static function fromArray($data) {
		$configuredProtectionTypes = [];
		if ( isset($data['protections']) && is_array($data['protections']) ) {
			foreach ($data['protections'] as $protectionData) {
				$protection = AccessProtection::fromArray($protectionData);
				$configuredProtectionTypes[$protection->getTag()] = $protection;
			}
		}

		$activeProtection = null;
		if ( isset($data['active']) ) {
			$activeTag = $data['active'];
			if ( isset($configuredProtectionTypes[$activeTag]) ) {
				$activeProtection = $configuredProtectionTypes[$activeTag];
			} else {
				$activeProtection = AccessProtection::fromArray(['tag' => $activeTag]);
			}
		}

		return new static($activeProtection, $configuredProtectionTypes);
	}

	/** @noinspection PhpLanguageLevelInspection */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		$data = [];
		if ( $this->activeProtection ) {
			$data['active'] = $this->activeProtection->getTag();
		}

		//We want to preserve protection settings (e.g. redirect URL) even if the corresponding
		//protection type isn't currently active. So we store all protection types that have any
		//serialized data (minus the tag).
		if ( !empty($this->configuredProtectionTypes) ) {
			$protections = [];
			foreach ($this->configuredProtectionTypes as $protection) {
				$serialized = $protection->jsonSerialize();
				if ( count($serialized) > 1 ) {
					$protections[$protection->getTag()] = $serialized;
				}
			}

			if ( !empty($protections) ) {
				$data['protections'] = $protections;
			}
		}
		return $data;
	}
}

class RestrictedPostLookup implements \JsonSerializable {
	/**
	 * @var array<string,array>
	 */
	protected $postAccess;

	private $wasModified = false;

	public function __construct($properties = []) {
		if ( isset($properties['posts']) ) {
			$this->postAccess = $properties['posts'];
		} else {
			$this->postAccess = [];
		}
	}

	/**
	 * @param Actor $actor
	 * @param Action $action
	 * @param array $postTypes
	 * @return int[]
	 */
	public function getRestrictedPosts(Actor $actor, Action $action, $postTypes = null) {
		$actionName = $action->getName();

		$allowed = [];
		$denied = [];

		foreach ($actor->flatten() as $componentActor) {
			$actorId = $componentActor->getId();
			$priority = $componentActor->getPriority();

			$actorSettings = \ameMultiDictionary::get($this->postAccess, [$actorId, $actionName], []);
			if ( $postTypes === null ) {
				$relevantPostTypes = $actorSettings;
			} else {
				$relevantPostTypes = [];
				foreach ($postTypes as $postType) {
					if ( isset($actorSettings[$postType]) ) {
						$relevantPostTypes[$postType] = $actorSettings[$postType];
					}
				}
			}

			foreach ($relevantPostTypes as $postIds) {
				foreach ($postIds as $postId => $isAllowed) {
					if ( isset($allowed[$postId]) ) {
						//Already allowed for another actor of the same or higher priority.
						continue;
					}

					if ( $isAllowed ) {
						//Allow unless it was denied for an actor with a higher priority.
						//For example, a "deny" for a user could override an "allow" for a role,
						//but not vice versa.
						$hasPriorityDeny = isset($denied[$postId]) && ($denied[$postId] > $priority);
						if ( !$hasPriorityDeny ) {
							$allowed[$postId] = $priority;
							unset($denied[$postId]);
						}
					} else {
						//Due to the earlier "isset($allowed[$postId])" check, we only get here
						//if there's no higher-priority actor that would override this setting.
						$denied[$postId] = $priority;
					}
				}
			}
		}

		return array_keys($denied);
	}

	/**
	 * @param Actor $actor
	 * @param Action[] $actions
	 * @return bool
	 */
	public function hasPotentialRestrictions(Actor $actor, $actions) {
		foreach ($actor->flatten() as $componentActor) {
			$actorId = $componentActor->getId();
			foreach ($actions as $action) {
				$actionName = $action->getName();
				if ( !empty($this->postAccess[$actorId][$actionName]) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param string $actorId
	 * @param Action $action
	 * @param string $postType
	 * @param int $postId
	 * @param bool $isAllowed
	 * @return void
	 */
	public function addEntry($actorId, Action $action, $postType, $postId, $isAllowed) {
		$actionName = $action->getName();
		$isAllowed = boolval($isAllowed);

		$path = [$actorId, $actionName, $postType, $postId];
		$oldValue = \ameMultiDictionary::get($this->postAccess, $path);
		if ( $oldValue === $isAllowed ) {
			return;
		}

		\ameMultiDictionary::set($this->postAccess, $path, $isAllowed);
		$this->wasModified = true;
	}

	public function removeEntry($actorId, Action $action, $postType, $postId) {
		$actionName = $action->getName();

		if ( isset($this->postAccess[$actorId][$actionName][$postType][$postId]) ) {
			unset($this->postAccess[$actorId][$actionName][$postType][$postId]);
			$this->wasModified = true;
		}
	}

	public function forgetPost($postId) {
		foreach ($this->postAccess as $actorId => $actions) {
			foreach ($actions as $actionName => $postTypes) {
				foreach ($postTypes as $postType => $postIds) {
					if ( isset($postIds[$postId]) ) {
						unset($this->postAccess[$actorId][$actionName][$postType][$postId]);
						$this->wasModified = true;
					}
				}
			}
		}
	}

	public function isModified() {
		return $this->wasModified;
	}

	public function clearModifiedFlag() {
		$this->wasModified = false;
	}

	public function toArray() {
		//Optimization: Keep only non-empty arrays.
		$filtered = [];
		foreach ($this->postAccess as $actorId => $actions) {
			foreach ($actions as $actionName => $postTypes) {
				foreach ($postTypes as $postType => $postIds) {
					if ( !empty($postIds) ) {
						\ameMultiDictionary::set($filtered, [$actorId, $actionName, $postType], $postIds);
					}
				}
			}
		}
		return [
			'posts' => $filtered,
		];
	}

	public static function deserializeFromArray($properties) {
		$restrictedPosts = [];
		if ( isset($properties['posts']) && is_array($properties['posts']) ) {
			$restrictedPosts = $properties['posts'];
		}
		return new static($restrictedPosts);
	}

	/** @noinspection PhpLanguageLevelInspection */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->toArray();
	}
}