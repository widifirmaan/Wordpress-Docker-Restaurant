<?php

namespace YahnisElsts\AdminMenuEditor\Actors;

abstract class Actor {
	const ROLE_PREFIX = 'role:';
	const USER_PREFIX = 'user:';

	abstract public function getId();

	/**
	 * Get all actors that this actor represents (including itself), in descending order of priority.
	 *
	 * In most cases, this will return an array with a single element - the actor itself. For users,
	 * this will return an array with the user itself, the "Super Admin" actor if the user is a super
	 * admin, the user's roles, etc.
	 *
	 * @return Actor[]
	 */
	public function flatten() {
		return [$this];
	}

	/**
	 * @return int
	 */
	abstract public function getPriority();

	public function __toString() {
		return $this->getId();
	}
}

class Role extends Actor {
	private $actorId;
	private $roleId;

	public function __construct($roleId) {
		$this->roleId = $roleId;
		$this->actorId = self::ROLE_PREFIX . $roleId;
	}

	public function getId() {
		return $this->actorId;
	}

	public function getPriority() {
		return 2;
	}

	public function getRoleId() {
		return $this->roleId;
	}
}

abstract class SpecialActor extends Actor {
	protected static $permanentActorId;
	protected static $priority;

	public function getId() {
		return static::$permanentActorId;
	}

	public function getPriority() {
		return static::$priority;
	}
}


class SuperAdmin extends SpecialActor {
	const PERMANENT_ACTOR_ID = 'special:super_admin';
	protected static $permanentActorId = self::PERMANENT_ACTOR_ID;
	protected static $priority = 3;
}

class LoggedInUser extends SpecialActor {
	const PERMANENT_ACTOR_ID = 'special:logged_in_user';
	protected static $permanentActorId = self::PERMANENT_ACTOR_ID;
	protected static $priority = 1;
}

class AnonymousUser extends SpecialActor {
	const PERMANENT_ACTOR_ID = 'special:anonymous_user';
	protected static $permanentActorId = self::PERMANENT_ACTOR_ID;
	protected static $priority = 0;
}


class User extends Actor {
	private $actorId;
	/**
	 * @var Role[]
	 */
	private $roles;
	/**
	 * @var SuperAdmin|null
	 */
	private $superAdmin;
	/**
	 * @var LoggedInUser|null
	 */
	private $loggedInUser;

	/**
	 * @var null|Actor[]
	 */
	private $flattenedActors = null;

	public function __construct(
		$userLogin,
		$roles = [],
		?SuperAdmin $superAdmin = null,
		?LoggedInUser $loggedInUser = null
	) {
		$this->actorId = self::USER_PREFIX . $userLogin;
		$this->roles = $roles;
		$this->superAdmin = $superAdmin;
		$this->loggedInUser = $loggedInUser;
	}

	public function getId() {
		return $this->actorId;
	}

	public function getPriority() {
		return 10;
	}

	public function flatten() {
		if ( $this->flattenedActors !== null ) {
			return $this->flattenedActors;
		}

		$actors = [$this];
		if ( $this->superAdmin ) {
			$actors[] = $this->superAdmin;
		}

		foreach ($this->roles as $role) {
			$actors[] = $role;
		}

		if ( $this->loggedInUser ) {
			$actors[] = $this->loggedInUser;
		}

		usort($actors, function (Actor $a, Actor $b) {
			return $b->getPriority() - $a->getPriority();
		});

		$this->flattenedActors = $actors;
		return $actors;
	}

	/**
	 * Get the roles that this user has.
	 *
	 * Note that this returns *role IDs*, not *actor IDs*. In this context, a role ID is the internal
	 * role name or slug used by WordPress. For example, "administrator" instead of "role:administrator".
	 *
	 * @return string[]
	 */
	public function getRoleIds() {
		$roleNames = [];
		foreach ($this->roles as $role) {
			$roleNames[] = $role->getRoleId();
		}
		return $roleNames;
	}
}

class ActorManager {
	private $actorInstances = [];
	/**
	 * @var SuperAdmin|null
	 */
	private $superAdminInstance;
	/**
	 * @var AnonymousUser|null
	 */
	private $anonymousUserInstance = null;
	/**
	 * @var LoggedInUser|null
	 */
	private $loggedInUserInstance = null;
	/**
	 * @var User|null
	 */
	private $cachedCurrentUser = null;

	private $menuEditor;

	public function __construct(\WPMenuEditor $menuEditor) {
		$this->menuEditor = $menuEditor;
		$this->superAdminInstance = new SuperAdmin();
	}

	public function getRole($roleId) {
		$actorId = Actor::ROLE_PREFIX . $roleId;

		if ( !isset($this->actorInstances[$actorId]) ) {
			$this->actorInstances[$actorId] = new Role($roleId);
		}
		return $this->actorInstances[$actorId];
	}

	public function getUserActorByUserId($userId) {
		if ( $userId <= 0 ) {
			return null;
		}

		if ( $userId === get_current_user_id() ) {
			return $this->getCurrentUserActor();
		}

		$user = get_user_by('id', $userId);
		if ( !$user ) {
			return null;
		}

		return $this->getActorFromWpUser($user);
	}

	public function getCurrentUserActor() {
		if ( $this->cachedCurrentUser !== null ) {
			return $this->cachedCurrentUser;
		}
		$user = wp_get_current_user();
		if ( !$user || !$user->exists() ) {
			return null;
		}

		$this->cachedCurrentUser = $this->getActorFromWpUser($user, true);
		return $this->cachedCurrentUser;
	}

	protected function getActorFromWpUser(\WP_User $user, $isLoggedIn = false) {
		$expectedActorId = Actor::USER_PREFIX . $user->user_login;
		if ( isset($this->actorInstances[$expectedActorId]) ) {
			return $this->actorInstances[$expectedActorId];
		}

		$roleIds = $this->menuEditor->get_user_roles($user);
		$roleActors = [];
		foreach ($roleIds as $roleId) {
			$roleActors[] = $this->getRole($roleId);
		}

		if ( is_multisite() && is_super_admin($user->ID) ) {
			$superAdmin = $this->superAdminInstance;
		} else {
			$superAdmin = null;
		}

		$actor = new User(
			$user->user_login,
			$roleActors,
			$superAdmin,
			$isLoggedIn ? $this->getGenericLoggedInUser() : null
		);
		$this->actorInstances[$actor->getId()] = $actor;
		return $actor;
	}

	private function getAnonymousUser() {
		if ( $this->anonymousUserInstance === null ) {
			$this->anonymousUserInstance = new AnonymousUser();
		}
		return $this->anonymousUserInstance;
	}

	private function getGenericLoggedInUser() {
		if ( $this->loggedInUserInstance === null ) {
			$this->loggedInUserInstance = new LoggedInUser();
		}
		return $this->loggedInUserInstance;
	}

	public function getCurrentActor() {
		$currentUser = $this->getCurrentUserActor();
		if ( $currentUser ) {
			return $currentUser;
		} else {
			return $this->getAnonymousUser();
		}
	}
}