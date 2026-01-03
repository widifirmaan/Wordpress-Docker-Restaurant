/// <reference path="../../js/common.d.ts" />

namespace AmeContentPermissionsUi {
	const $ = jQuery;
	const _ = wsAmeLodash;

	declare const wsAmeCpeScriptData: ScriptData;

	interface ScriptData {
		translations: Record<string, Record<string, string>>;
	}

	const translations = wsAmeCpeScriptData.translations || {};

	interface EditorData {
		policy: PolicyData | null;
		applicableActions: SerializedAction[];
		requiredCapabilities: Record<string, string[]>;
		enforcementDisabled: boolean;
		adminLikeRoles: string[];
	}

	type SelectedActorObservable = ReturnType<AmeActorSelector['createActorObservable']>;

	class ContentPermissionsEditor {
		public serializedPolicy: KnockoutObservable<string>;

		public readonly tabs: { id: string, title: string }[] = [];
		public readonly activeTab: KnockoutObservable<this['tabs'][number]>;

		private readonly actorSelector: AmeActorSelector;
		public readonly visibleActors: KnockoutObservable<IAmeActor[]>;
		public readonly advancedTabActors: KnockoutObservable<IAmeActor[]>;
		public readonly selectedActor: SelectedActorObservable;

		private readonly policy: Policy;

		public readonly actionSettings: ActionPermissionSetting[] = [];
		private readonly actions: Action[] = [];
		private readonly readActions: Action[] = [];
		private readonly otherActions: Action[] = [];

		public readonly gridsByActorId: Record<string, MiniGridItem[]> = {};

		public readonly basicViewState: KnockoutComputed<BasicViewState>;
		public readonly basicActorSettings: BasicActorSetting[] = [];
		public readonly everyoneHasDefaultPermissions: KnockoutComputed<boolean>;

		public readonly enforcementDisabled: boolean = false;

		constructor(editorData: EditorData) {
			this.tabs.push(
				{
					id: 'basic',
					title: translations.tabTitles.basic || 'Basic'
				},
				{
					id: 'advanced',
					title: translations.tabTitles.advanced || 'Advanced'
				},
				{
					id: 'protection',
					title: translations.tabTitles.protection || 'Protection'
				},
				{
					id: 'about',
					title: translations.tabTitles.about || 'About'
				}
			);

			this.actorSelector = new AmeActorSelector(AmeActors, false, false, 1);
			this.selectedActor = this.actorSelector.createActorObservable(ko);

			const genericLoggedInUser = AmeActors.getGenericLoggedInUser();
			const anonymousUser = AmeActors.getAnonymousUser();
			this.actorSelector.addSpecialActor(genericLoggedInUser);
			this.actorSelector.addSpecialActor(anonymousUser);

			{
				const allSelectableActors = ko.observable(this.actorSelector.getVisibleActors());
				this.actorSelector.onVisibleActorsChanged(allSelectableActors);
				this.visibleActors = ko.computed(() => {
					return allSelectableActors().filter(actor => !actor.isUser());
				});
			}
			this.advancedTabActors = this.visibleActors;

			//Select the first actor in the list.
			const tempActors = this.advancedTabActors();
			if (tempActors.length > 0) {
				this.selectedActor(tempActors[0]);
			}

			this.actions = (editorData.applicableActions || []).map(Action.fromJSON);
			for (const action of this.actions) {
				if ((action.name === 'read') || (action.name === 'view_in_lists')) {
					this.readActions.push(action);
				} else {
					this.otherActions.push(action);
				}
			}

			this.policy = new Policy(
				editorData.policy || {},
				this.advancedTabActors(),
				this.actions,
				genericLoggedInUser,
				editorData.requiredCapabilities
			);

			this.serializedPolicy = ko.observable(this.getSerializedPolicy());

			//Update the serialized policy string when the policy changes. It would be nicer
			//to only do this when the post is saved, but I'm not sure we can catch that event
			//in all the different post editors.
			ko.computed(() => {
				//Establish a dependency on the number of statements in the policy.
				//This way the serialized string will be updated when statements are added or
				//removed, not just when existing statements are modified.
				this.policy.statementCount();
				return this.getSerializedPolicy();
			}).extend({
				//Rate-limited to avoid excessive updates.
				rateLimit: {
					timeout: 1000,
					method: 'notifyWhenChangesStop'
				}
			}).subscribe(this.serializedPolicy);

			//Select the "Advanced" tab if the policy has the "preferAdvancedMode" flag set,
			//or the "Basic" tab otherwise.
			const advancedTab = this.tabs.find(tab => tab.id === 'advanced');
			this.activeTab = ko.observable(
				(this.policy.preferAdvancedMode() && advancedTab) ? advancedTab : this.tabs[0]
			);

			//Initialize action settings and preview grids.
			//This needs to happen *after* the policy and actions have been loaded because the selected
			//option for each setting and the color of each grid cell depend on the policy.

			//When the user changes a setting in the "Advanced" tab, make "Advanced" the preferred tab.
			const settingChangeListenerForAdvancedTab = () => {
				if (this.activeTab().id === 'advanced') {
					this.policy.preferAdvancedMode(true);
				}
			}
			for (const action of this.actions) {
				this.actionSettings.push(new ActionPermissionSetting(
					action,
					this.policy,
					this.selectedActor,
					settingChangeListenerForAdvancedTab
				));
			}

			for (const actor of this.advancedTabActors()) {
				this.gridsByActorId[actor.getId()] = this.actions
					.map(action => new MiniGridItem(actor, action, this.policy));
			}

			//Initialize "Basic" tab settings.
			for (const actor of this.visibleActors()) {
				if (actor.getId().startsWith('role:')) {
					this.basicActorSettings.push(new BasicActorSetting(
						actor,
						this.policy,
						this.generateBasicPermissions(actor, true),
						this.generateBasicPermissions(actor, false),
						this.readActions
					));
				}
			}

			this.everyoneHasDefaultPermissions = ko.pureComputed(() => {
				return this.visibleActors().every(actor => {
					for (const action of this.actions) {
						if (action.isVisibleFor(actor)) {
							const state = this.policy.getActorPermission(actor, action);
							if (state !== null) {
								return false;
							}
						}
					}
					return true;
				})
			});

			const presets = {
				//"Logged In Users" = read permissions on for logged-in users, off for logged-out users.
				loggedIn: this.generateBasicPermissions(genericLoggedInUser, true).concat(
					this.generateBasicPermissions(anonymousUser, false)
				),
				//"Logged Out Users" = the opposite of the above, except read permissions are explicitly
				//enabled for admins (below) so that they can still see hidden posts.
				loggedOut: this.generateBasicPermissions(anonymousUser, true).concat(
					this.generateBasicPermissions(genericLoggedInUser, false)
				)
			};
			//Enable read permissions for admin-like roles when selecting "Logged Out Users".
			//We also need to remember which actors are in the "Logged Out Users" preset so that
			//we can determine if it's selected later.
			const loggedOutPresetActors = new Set<IAmeActor>([genericLoggedInUser, anonymousUser]);
			for (const roleId of editorData.adminLikeRoles) {
				const role = AmeActors.getActor('role:' + roleId);
				if (role) {
					loggedOutPresetActors.add(role);
					for (const action of this.readActions) {
						presets.loggedOut.push([role, action, true]);
					}
				}
			}

			this.basicViewState = ko.computed({
				read: (): BasicViewState => {
					if (this.policy.preferAdvancedMode()) {
						return 'advanced';
					}

					//"Everyone" = all actors have their default permissions.
					if (this.everyoneHasDefaultPermissions()) {
						return 'everyone';
					}

					//"Logged Out Users" = the preset matches, and everyone else has default permissions.
					if (this.policy.matchesMultiplePermissions(presets.loggedOut)) {
						const othersHaveDefaults = this.visibleActors().every(actor => {
							return loggedOutPresetActors.has(actor) || !this.policy.actorHasAnyCustomPermissions(actor);
						});
						if (othersHaveDefaults) {
							return 'loggedOut';
						}
					}

					//"Logged In Users" = the preset matches, and all the roles are in one of their
					//predefined states.
					if (this.policy.matchesMultiplePermissions(presets.loggedIn)) {
						if (this.basicActorSettings.every(s => s.isPredefinedState())) {
							return 'loggedIn';
						}
					}

					//Any other mix of settings is considered "Advanced".
					return 'advanced';
				},
				write: (value: BasicViewState): void => {
					if (value === 'advanced') {
						this.policy.preferAdvancedMode(true);
						//Manually selecting "Advanced" does not change any permissions.
						//The user should do that in the advanced tab.
						return;
					}

					this.resetAllPermissions();
					this.policy.preferAdvancedMode(false);

					switch (value) {
						case 'everyone':
							//"Everyone" resets all settings to default, which we already did above.
							break;
						case 'loggedIn':
							this.policy.setMultiplePermissions(presets.loggedIn);
							break;
						case 'loggedOut':
							this.policy.setMultiplePermissions(presets.loggedOut);
							break;
					}
				}
			});

			if (editorData.enforcementDisabled) {
				this.enforcementDisabled = true;
			}
		}

		/**
		 * Apply dynamic CSS classes to options in the action permission dropdowns.
		 *
		 * This can't be done in the template because the options are generated using the "options"
		 * binding which doesn't have a parameter for classes.
		 */
		addOptionClasses(optionElement: HTMLElement, option: ActionPermissionOption) {
			if (option && ko.isObservable(option.cssClass)) { //Sanity check.
				ko.applyBindingsToNode(optionElement, {class: option.cssClass});
			}
		}

		setSelectedActorPermission(action: Action, effect: RawPermissionValue): void {
			const targetActor = this.selectedActor();
			if (!targetActor) {
				return;
			}
			this.policy.setActorPermission(targetActor, action, effect);
		}

		getSerializedPolicy() {
			return JSON.stringify(this.policy.toJSON());
		}

		updateSerializedPolicy() {
			this.serializedPolicy(this.getSerializedPolicy());
		}

		getReplacementContent() {
			return this.policy.replacementContent();
		}

		setReplacementContent(content: string) {
			this.policy.replacementContent(content);
		}

		checkAllBasicActors() {
			this.toggleAllBasicSettings(true);
		}

		checkNoneBasicActors() {
			this.toggleAllBasicSettings(false);
		}

		private resetAllPermissions() {
			for (const actor of this.visibleActors()) {
				for (const action of this.actions) {
					this.policy.setActorPermission(actor, action, null);
				}
			}
		}

		private generateBasicPermissions(actor: IAmeActor, isAllowed: boolean): PermissionsList {
			const permissions: PermissionsList = [];
			if (isAllowed) {
				//Allow reading.
				for (const action of this.readActions) {
					if (!action.isVisibleFor(actor)) {
						continue;
					}

					//Don't explicitly set the permission to allowed unless necessary.
					//Most roles can read content by default, so resetting to default
					//is usually enough.
					const predictedDefault = this.policy.getPredictedDefaultPermission(actor, action);
					const newSetting = (predictedDefault === isAllowed) ? null : isAllowed;
					permissions.push([actor, action, newSetting]);

				}
				//Reset other permissions.
				for (const action of this.otherActions) {
					if (action.isVisibleFor(actor)) {
						permissions.push([actor, action, null]);
					}
				}
			} else {
				//Deny all permissions.
				for (const action of this.actions) {
					if (action.isVisibleFor(actor)) {
						permissions.push([actor, action, false]);
					}
				}
			}
			return permissions;
		}

		private toggleAllBasicSettings(state: boolean) {
			if (this.basicViewState() !== 'loggedIn') {
				return;
			}
			for (const actorSetting of this.basicActorSettings) {
				actorSetting.isChecked(state);
			}
		}

		private permissionsBeforeReset: PermissionsList | null = null;
		public readonly undoResetActionVisible: KnockoutObservable<boolean> = ko.observable(false);

		uiResetPermissionsToDefaults() {
			const currentPermissions: PermissionsList = [];
			let foundCustomPermissions = false;
			for (const actor of this.visibleActors()) {
				const permissions: PermissionsList = [];
				for (const action of this.actions) {
					const effect = this.policy.getActorPermission(actor, action);
					if (effect !== null) {
						permissions.push([actor, action, effect]);
						foundCustomPermissions = true;
					}
				}
			}

			if (!foundCustomPermissions) {
				alert(translations.general.noCustomPermissionsReset || 'No custom permissions to reset.');
				return; //Nothing to reset.
			}

			this.permissionsBeforeReset = currentPermissions;
			this.resetAllPermissions();
			this.undoResetActionVisible(true);
		}

		undoLastPermissionsReset() {
			if (!this.permissionsBeforeReset) {
				return;
			}

			this.resetAllPermissions();
			this.policy.setMultiplePermissions(this.permissionsBeforeReset);

			this.permissionsBeforeReset = null;
			this.undoResetActionVisible(false);
		}
	}

	interface SerializedAction {
		name: string;
		label: string;
		description?: string;
	}

	class Action {
		constructor(
			public readonly name: string,
			public readonly label: string,
			public readonly description: string = ''
		) {
		}

		isVisibleFor(actor: IAmeActor | null): boolean {
			if (actor === null) {
				return false;
			}

			//The special logged-in and anonymous actors only have settings for reading permissions.
			if ((actor === AmeActors.getGenericLoggedInUser()) || (actor === AmeActors.getAnonymousUser())) {
				return (this.name === 'read') || (this.name === 'view_in_lists');
			}

			return true;
		}

		static fromJSON(data: SerializedAction): Action {
			const instance = new Action(data.name, data.label, data.description || '');
			return instance;
		}
	}

	type RawPermissionValue = boolean | null;

	class VirtualStatement {
		public readonly actor: IAmeActor;
		public readonly action: Action;
		public readonly effect: KnockoutObservable<RawPermissionValue>;

		constructor(actor: IAmeActor, action: Action, effect: RawPermissionValue = null) {
			this.actor = actor;
			this.action = action;
			this.effect = ko.observable(effect);
		}
	}

	interface PolicyActorAccessData {
		[actorId: string]: {
			[actionName: string]: boolean;
		}
	}

	interface RedirectAccessProtectionData {
		tag: 'redirect';
		targetUrl?: string;
		redirectCode?: number;
		shortcodesEnabled?: boolean;
	}

	interface ErrorMessageProtectionData {
		tag: 'errorMessage';
		errorMessage?: string;
	}

	interface NotFoundProtectionData {
		tag: 'notFound';
	}

	interface ContentReplacementProtectionData {
		tag: 'replace';
	}

	type AccessProtectionData =
		RedirectAccessProtectionData
		| ErrorMessageProtectionData
		| NotFoundProtectionData
		| ContentReplacementProtectionData;

	interface AccessProtectionSettingsData {
		active?: AccessProtectionData['tag'];
		protections?: {
			[K in AccessProtectionData['tag']]?: Extract<AccessProtectionData, { tag: K }>;
		}
	}

	abstract class AccessProtection implements AmeJsonSerializable<AccessProtectionData> {
		abstract readonly tag: AccessProtectionData['tag'];

		abstract toJSON(): AccessProtectionData;

		getLabel(): string {
			return _.get(translations, ['protectionLabels', this.tag], this.tag);
		}
	}

	class RedirectAccessProtection extends AccessProtection {
		public readonly tag = 'redirect';
		public readonly targetUrl: KnockoutObservable<string>;
		public readonly redirectCode: KnockoutObservable<number>;
		public readonly shortcodesEnabled: KnockoutObservable<boolean>;

		constructor(data: RedirectAccessProtectionData) {
			super();
			this.targetUrl = ko.observable(data.targetUrl ?? '');
			this.redirectCode = ko.observable(data.redirectCode ?? 307);
			this.shortcodesEnabled = ko.observable(data.shortcodesEnabled ?? false);
		}

		toJSON(): RedirectAccessProtectionData {
			return {
				tag: this.tag,
				targetUrl: this.targetUrl(),
				redirectCode: this.redirectCode(),
				shortcodesEnabled: this.shortcodesEnabled()
			};
		}
	}

	class ErrorMessageProtection extends AccessProtection {
		public readonly tag = 'errorMessage';
		public readonly errorMessage: KnockoutObservable<string>;

		constructor(data: ErrorMessageProtectionData) {
			super();
			this.errorMessage = ko.observable(data.errorMessage ?? '');
		}

		toJSON(): ErrorMessageProtectionData {
			return {
				tag: 'errorMessage',
				errorMessage: this.errorMessage()
			};
		}
	}

	class NotFoundProtection extends AccessProtection {
		public readonly tag = 'notFound';

		constructor(_: NotFoundProtectionData) {
			super();
		}

		toJSON(): NotFoundProtectionData {
			return {tag: 'notFound'};
		}
	}

	class ContentReplacementProtection extends AccessProtection {
		public readonly tag = 'replace';

		constructor(_: ContentReplacementProtectionData) {
			super();
		}

		toJSON(): ContentReplacementProtectionData {
			return {tag: 'replace'};
		}
	}

	function deserializeAccessProtection(data: AccessProtectionData): AccessProtection {
		switch (data.tag) {
			case 'redirect':
				return new RedirectAccessProtection(data);
			case 'errorMessage':
				return new ErrorMessageProtection(data);
			case 'notFound':
				return new NotFoundProtection(data);
			case 'replace':
				return new ContentReplacementProtection(data);
		}
	}

	function createDefaultAccessProtection(tag: AccessProtectionData['tag']): AccessProtection {
		switch (tag) {
			case 'redirect':
				return new RedirectAccessProtection({
					tag: 'redirect',
					targetUrl: '',
					redirectCode: 307,
					shortcodesEnabled: false
				});
			case 'errorMessage':
				return new ErrorMessageProtection({
					tag: 'errorMessage',
					errorMessage: ''
				});
			case 'notFound':
				return new NotFoundProtection({tag: 'notFound'});
			case 'replace':
				return new ContentReplacementProtection({tag: 'replace'});
		}
	}

	const allProtectionTags: AccessProtectionData['tag'][] = ['replace', 'notFound', 'errorMessage', 'redirect'];

	class AccessProtectionSettings implements AmeJsonSerializable<AccessProtectionSettingsData> {
		public readonly active: KnockoutObservable<AccessProtection>;
		public readonly protections: AccessProtection[] = [];

		constructor(data: AccessProtectionSettingsData) {
			let activeInstance: AccessProtection | null = null;

			for (const tag of allProtectionTags) {
				let instance: AccessProtection;
				const serializedProtection = this.getProtectionFromSettings(data, tag);
				if (serializedProtection) {
					instance = deserializeAccessProtection(serializedProtection);
				} else {
					instance = createDefaultAccessProtection(tag);
				}
				this.protections.push(instance);

				if (tag === data.active) {
					activeInstance = instance;
				}
			}

			this.active = ko.observable(activeInstance || this.protections[0]);
		}

		private getProtectionFromSettings(
			settings: AccessProtectionSettingsData,
			tag: AccessProtectionData['tag']
		): AccessProtectionData | null {
			if (settings.protections) {
				const temp = settings.protections[tag];
				if (temp) {
					return temp;
				}
			}
			return null;
		}

		toJSON(): AccessProtectionSettingsData {
			return {
				active: this.active().tag,
				protections: this.protections.reduce((result, protection) => {
					const serialized = protection.toJSON();
					result[serialized.tag] = serialized;
					return result;
				}, {} as Record<string, AccessProtectionData>)
			} satisfies AccessProtectionSettingsData;
		}
	}

	interface PolicyData {
		actorAccess?: PolicyActorAccessData;
		replacementContent?: string;
		accessProtection?: AccessProtectionSettingsData;
		preferAdvancedMode?: boolean;
	}

	type PermissionsList = Array<[IAmeActor, Action, RawPermissionValue]>;

	class Policy implements AmeJsonSerializable<PolicyData> {
		public readonly replacementContent: KnockoutObservable<string>;
		public readonly accessProtection: AccessProtectionSettings;
		private readonly statements: InvertedIndex<VirtualStatement>;

		public readonly statementCount: KnockoutComputed<number>;

		public readonly preferAdvancedMode: KnockoutObservable<boolean>;

		constructor(
			properties: PolicyData,
			validActors: IAmeActor[],
			validActions: Action[],
			private readonly genericLoggedInUser: IAmeActor,
			private readonly requiredCapsByAction: Record<string, string[]> = {}
		) {
			this.replacementContent = ko.observable(properties.replacementContent || '');
			this.accessProtection = new AccessProtectionSettings(properties.accessProtection || {});
			this.preferAdvancedMode = ko.observable(properties.preferAdvancedMode || false);

			this.statements = new InvertedIndex(
				(statement: VirtualStatement) => `${statement.actor.getId()}/${statement.action.name}`,
				['actor', 'action']
			);

			for (const actor of validActors) {
				for (const action of validActions) {
					const effect = _.get(properties.actorAccess, [actor.getId(), action.name], null);
					if (effect !== null) {
						this.statements.add(new VirtualStatement(actor, action, effect));
					}
				}
			}

			this.statementCount = ko.pureComputed(() => this.statements.size());
		}

		getActorPermission(actor: IAmeActor, action: Action): RawPermissionValue {
			return this.getOrCreateStatement(actor, action).effect();
		}

		setActorPermission(actor: IAmeActor, action: Action, isAllowed: RawPermissionValue): void {
			const statement = this.getOrCreateStatement(actor, action);
			statement.effect(isAllowed);
		}

		/**
		 * Like setActorPermission(), but if the predicted default matches the given value,
		 * the permission reset to the default instead.
		 */
		setActorPermissonOrDefault(actor: IAmeActor, action: Action, isAllowed: RawPermissionValue): void {
			const predictedDefault = this.getPredictedDefaultPermission(actor, action);
			if (predictedDefault === isAllowed) {
				this.setActorPermission(actor, action, null);
			} else {
				this.setActorPermission(actor, action, isAllowed);
			}
		}

		getPredictedDefaultPermission(actor: IAmeActor, action: Action): RawPermissionValue {
			//Roles inherit the permissions of the "Logged In Users" actor if it has a custom setting.
			if (actor.getId().startsWith('role:') && action.isVisibleFor(this.genericLoggedInUser)) {
				const inheritedEffect = this.getActorPermission(this.genericLoggedInUser, action);
				if (inheritedEffect !== null) {
					return inheritedEffect;
				}
			}

			const caps = this.requiredCapsByAction[action.name];
			if (caps && (caps.length > 0)) {
				//Check if the actor has all the required capabilities.

				//Note: We assume everyone has the "read" capability. This is not always true, but
				//it leads to more accurate predictions in most cases because, usually, everyone
				//can read content (even users who are not logged in).
				return caps.every(cap => (actor.hasOwnCap(cap) || (cap === 'read')));
			}
			return null;
		}

		getPermissionState(actor: IAmeActor | null, action: Action): PermissionCheckResult {
			if (actor === null) {
				return new PermissionCheckResult(null, null);
			}

			return new PermissionCheckResult(
				this.getActorPermission(actor, action),
				this.getPredictedDefaultPermission(actor, action)
			);
		}

		setMultiplePermissions(permissions: PermissionsList): void {
			for (const [actor, action, effect] of permissions) {
				this.setActorPermission(actor, action, effect);
			}
		}

		matchesMultiplePermissions(permissions: PermissionsList): boolean {
			for (const [actor, action, effect] of permissions) {
				if (this.getActorPermission(actor, action) !== effect) {
					return false;
				}
			}
			return true;
		}

		actorHasAnyCustomPermissions(actor: IAmeActor): boolean {
			if (this.statements.size() === 0) {
				return false;
			}

			const statement = this.statements.findFirst({actor});
			if (statement) {
				return (statement.effect() !== null);
			}
			return false;
		}

		private getOrCreateStatement(actor: IAmeActor, action: Action): VirtualStatement {
			const foundStatement = this.statements.findFirst({actor, action});
			if (foundStatement) {
				return foundStatement;
			}

			const statement = new VirtualStatement(actor, action, null);
			this.statements.add(statement);
			return statement;
		}

		toJSON(): PolicyData {
			const data: PolicyData = {};

			const actorAcess: PolicyActorAccessData = {};
			for (const statement of this.statements.getAll()) {
				const effect = statement.effect();
				if (effect !== null) {
					_.set(actorAcess, [statement.actor.getId(), statement.action.name], effect);
				}
			}
			if (!_.isEmpty(actorAcess)) {
				data.actorAccess = actorAcess;
			}

			const replacementContent = this.replacementContent();
			if (replacementContent !== '') {
				data.replacementContent = replacementContent;
			}

			const accessProtection = this.accessProtection.toJSON();
			if (!_.isEmpty(accessProtection)) {
				data.accessProtection = accessProtection;
			}

			if (this.preferAdvancedMode()) {
				data.preferAdvancedMode = true;
			}

			return data;
		}
	}

	class PermissionCheckResult {
		constructor(
			public readonly setting: RawPermissionValue,
			public readonly predictedDefault: RawPermissionValue
		) {
		}

		get effectivePermission(): RawPermissionValue {
			return this.setting ?? this.predictedDefault;
		}

		get isDefault(): boolean {
			return this.setting === null;
		}
	}

	interface ActionPermissionOption {
		label: KnockoutObservable<string>;
		effect: RawPermissionValue;
		cssClass: KnockoutComputed<string>;
		dashicon: string;
	}

	const allowOption: ActionPermissionOption = {
		label: ko.observable(translations.permissionOptions.allow || 'Allow'),
		effect: true,
		cssClass: ko.pureComputed(() => 'ame-cpe-option-allow'),
		dashicon: 'yes'
	}

	const denyOption: ActionPermissionOption = {
		label: ko.observable(translations.permissionOptions.deny || 'Deny'),
		effect: false,
		cssClass: ko.pureComputed(() => 'ame-cpe-option-deny'),
		dashicon: 'no'
	}

	class DefaultPermissionOption implements ActionPermissionOption {
		public readonly label: KnockoutObservable<string>;
		public readonly effect: RawPermissionValue = null;
		public readonly cssClass: KnockoutComputed<string>;
		public readonly dashicon: string = '';

		private readonly predictedEffect: KnockoutComputed<RawPermissionValue>;

		constructor(action: Action, policy: Policy, selectedActorObservable: SelectedActorObservable) {
			this.predictedEffect = ko.pureComputed(() => {
				const actor = selectedActorObservable();
				if (actor === null) {
					return null;
				}
				return policy.getPredictedDefaultPermission(actor, action);
			});

			this.label = ko.pureComputed(() => {
				const effect = this.predictedEffect();
				if (effect === null) {
					return translations.permissionOptions.default || '(Default)';
				}

				return (effect
					? (translations.permissionOptions.defaultAllow || '(Default: Allow)')
					: (translations.permissionOptions.defaultDeny || '(Default: Deny)'));
			});

			this.cssClass = ko.pureComputed(() => {
				const classes = ['ame-cpe-option-default'];
				const effect = this.predictedEffect();

				if (effect === true) {
					classes.push('ame-cpe-option-allow');
				} else if (effect === false) {
					classes.push('ame-cpe-option-deny');
				}

				return classes.join(' ');
			});
		}
	}

	class ActionPermissionSetting {
		public readonly options: ActionPermissionOption[] = [allowOption, denyOption];
		public readonly selectedOption: KnockoutComputed<ActionPermissionOption>;
		public readonly isVisible: KnockoutObservable<boolean>;
		public readonly cssClass: KnockoutComputed<string>;

		constructor(
			public readonly action: Action,
			policy: Policy,
			selectedActorObservable: SelectedActorObservable,
			changeSubscription?: () => void
		) {
			const defaultOption = new DefaultPermissionOption(action, policy, selectedActorObservable);
			this.options.unshift(defaultOption);

			this.selectedOption = ko.computed({
				read: () => {
					const actor = selectedActorObservable();
					if (!actor) {
						return defaultOption;
					}

					const isAllowed = policy.getActorPermission(actor, action);
					if (isAllowed === null) {
						return defaultOption;
					}
					return isAllowed ? allowOption : denyOption;
				},
				write: (option: ActionPermissionOption): void => {
					const actor = selectedActorObservable();
					if (!actor) {
						return;
					}

					const effect = option.effect;
					policy.setActorPermission(actor, action, effect);

					if (changeSubscription) {
						changeSubscription();
					}
				}
			});

			this.cssClass = ko.computed(() => {
				return this.selectedOption().cssClass();
			});

			this.isVisible = ko.pureComputed(() => action.isVisibleFor(selectedActorObservable()));
		}
	}

	class PermissionOptionsComponent {
		public readonly setting: ActionPermissionSetting;

		constructor(params: Record<string, unknown>) {
			this.setting = ko.unwrap(params.setting) as ActionPermissionSetting;
		}
	}

	class PermissionOptionsDropdownComponent extends PermissionOptionsComponent {
		public readonly selectId: string = '';

		constructor(params: Record<string, unknown>) {
			super(params);

			const selectId = ko.unwrap(params.suggestedId);
			if (typeof selectId === 'string') {
				this.selectId = selectId;
			}
		}
	}

	class PermissionOptionsBarComponent extends PermissionOptionsComponent {
		constructor(params: Record<string, unknown>) {
			super(params);
		}
	}

	type BasicViewState = 'everyone' | 'loggedIn' | 'loggedOut' | 'advanced';

	class BasicActorSetting {
		public readonly isChecked: KnockoutObservable<boolean>;
		public readonly isPredefinedState: KnockoutComputed<boolean>;

		constructor(
			public readonly actor: IAmeActor,
			policy: Policy,
			checkedState: PermissionsList,
			uncheckedState: PermissionsList,
			readActions: Action[],
		) {
			this.isChecked = ko.computed({
				read: (): boolean => {
					return readActions.every(
						action => policy.getPermissionState(actor, action).effectivePermission
					);
				},
				write: (value: boolean): void => {
					if (value) {
						policy.setMultiplePermissions(checkedState);
					} else {
						policy.setMultiplePermissions(uncheckedState);
					}
				}
			});

			this.isPredefinedState = ko.pureComputed(() => {
				const expectedState = this.isChecked() ? checkedState : uncheckedState;
				return policy.matchesMultiplePermissions(expectedState);
			});
		}
	}

	class MiniGridItem {
		public readonly cssClass: KnockoutComputed<string>;

		constructor(actor: IAmeActor, action: Action, policy: Policy) {
			this.cssClass = ko.computed(() => {
				const classes: string[] = [];

				if (!action.isVisibleFor(actor)) {
					classes.push('ame-cpe-cell-not-applicable');
					return classes.join(' ');
				}

				let effect = policy.getActorPermission(actor, action);
				if (effect === null) {
					classes.push('ame-cpe-cell-default');
					effect = policy.getPredictedDefaultPermission(actor, action);
				}

				if (effect === true) {
					classes.push('ame-cpe-cell-allow');
				} else if (effect === false) {
					classes.push('ame-cpe-cell-deny');
				}

				if (!action.isVisibleFor(actor)) {
					classes.push('ame-cpe-cell-not-applicable');
				}

				return classes.join(' ');
			});
		}
	}

	//region Inverted Index
	type PropertyKey = string | number | symbol;
	type PropertyValues<T> = { [K in keyof T]: T[K] };

	class InvertedIndex<T extends Record<PropertyKey, any>> {
		private records = new Map<string, T>();
		private indexes = new Map<keyof T, Map<any, Set<T>>>();
		private keyFunction: (item: T) => string;

		private indexedProperties: Set<keyof T>;
		private lazyInitIndexes: boolean;

		public readonly size: KnockoutComputed<number>;
		private readonly internalSize: KnockoutObservable<number> = ko.observable(0);

		constructor(
			keyFunction: (item: T) => string,
			indexedProperties?: Array<keyof T>
		) {
			this.keyFunction = keyFunction;
			this.indexedProperties = new Set(indexedProperties);

			if (this.indexedProperties.size > 0) {
				//We know which properties will be indexed, so we can initialize the indexes immediately.
				this.lazyInitIndexes = false;
				for (const prop of this.indexedProperties.values()) {
					this.indexes.set(prop, new Map<any, Set<T>>());
				}
			} else {
				this.lazyInitIndexes = true;
			}

			this.size = ko.pureComputed(() => this.internalSize());
		}

		private shouldIndex(prop: keyof T): boolean {
			//If no properties were specified for indexing, index everything.
			return (this.indexedProperties.size === 0) || this.indexedProperties.has(prop);
		}

		private initializeLazyIndexes(record: T) {
			//Initialize index maps only for properties we want to index.
			for (const prop in record) {
				if (this.shouldIndex(prop) && !this.indexes.has(prop)) {
					this.indexes.set(prop, new Map<any, Set<T>>());
				}
			}
		}

		add(record: T): void {
			const key = this.keyFunction(record);

			//Remove the old record if it exists.
			if (this.records.has(key)) {
				this.remove(this.records.get(key)!);
			}

			//Initialize indexes as needed.
			if (this.lazyInitIndexes) {
				this.initializeLazyIndexes(record);
			}

			//Store the record.
			this.records.set(key, record);
			this.internalSize(this.records.size);

			//Add to property indexes (only for indexed properties).
			for (const [prop, value] of Object.entries(record)) {
				if (!this.shouldIndex(prop as keyof T)) {
					continue;
				}

				const propIndex = this.indexes.get(prop as keyof T)!;
				if (!propIndex.has(value)) {
					propIndex.set(value, new Set<T>());
				}
				propIndex.get(value)!.add(record);
			}
		}

		remove(record: T): void {
			const key = this.keyFunction(record);
			if (!this.records.has(key)) {
				return;
			}

			//Remove from property indexes.
			for (const [prop, value] of Object.entries(record)) {
				if (!this.shouldIndex(prop as keyof T)) {
					continue;
				}

				const propIndex = this.indexes.get(prop as keyof T);
				if (propIndex) {
					const valueSet = propIndex.get(value);
					if (valueSet) {
						valueSet.delete(record);
						if (valueSet.size === 0) {
							propIndex.delete(value);
						}
					}
				}
			}

			//Remove from records.
			this.records.delete(key);
			this.internalSize(this.records.size);
		}

		getByKey(key: string): T | undefined {
			return this.records.get(key);
		}

		find(criteria: Partial<PropertyValues<T>>): Set<T> {
			let result: Set<T> | null = null;

			for (const [prop, value] of Object.entries(criteria)) {
				const propKey = prop as keyof T;

				//If we're searching by a non-indexed property, we need to do a full scan.
				if (!this.shouldIndex(propKey)) {
					const matches = new Set(
						Array.from(this.records.values()).filter(
							record => record[propKey] === value
						)
					);

					if (result === null) {
						result = matches;
					} else {
						result = new Set([...result].filter((x: T) => matches.has(x)));
					}

					continue;
				}

				const propIndex = this.indexes.get(propKey);
				if (!propIndex) {
					//This can happen if indexes are lazy-initialized and no records with this
					//property have been added yet.
					continue;
				}

				const matches = propIndex.get(value) || new Set<T>();
				if (result === null) {
					result = new Set(matches);
				} else {
					//Intersect with previous results.
					result = new Set([...result].filter(x => matches.has(x)));
				}

				//Short circuit if we have no matches.
				if (result.size === 0) {
					break;
				}
			}

			return result || new Set<T>();
		}

		findFirst(criteria: Partial<PropertyValues<T>>): T | undefined {
			const result = this.find(criteria);
			return result.values().next().value;
		}

		getAll(): T[] {
			return Array.from(this.records.values());
		}
	}

	//endregion

	jQuery(function () {
		const $metaBox = $('#ame-cpe-content-permissions');
		const $editor = $metaBox.find('.inside #ame-cpe-permissions-editor-root').first();
		const editorData = $editor.data('cpe-editor-data');

		if (($metaBox.length !== 1) || (!editorData)) {
			return;
		}

		ko.components.register(
			'ame-cpe-permission-options-dropdown',
			{
				viewModel: PermissionOptionsDropdownComponent,
				template: {element: 'ame-cpe-permission-dropdown-component'}
			}
		);

		ko.components.register(
			'ame-cpe-permission-options-bar',
			{
				viewModel: PermissionOptionsBarComponent,
				template: {element: 'ame-cpe-permission-bar-component'}
			}
		);

		const permissionsEditor = new ContentPermissionsEditor(editorData);
		ko.applyBindings(permissionsEditor, $editor[0]);

		//Clear the data attribute to free up memory. It contains a long JSON string which is
		//no longer needed after the editor is initialized.
		$editor.data('cpe-editor-data', '');

		//Enable tooltips in the editor.
		$editor.tooltip({
			items: '.ame-cpe-tooltip-trigger[title]',
			classes: {
				'ui-tooltip': 'ws-ame-tooltip'
			},
			position: {
				my: "left+10 center",
				at: "right center",
				collision: "flipfit"
			},
			//No animation.
			show: false,
			hide: false
		});

		//region Replacement Content Editor

		//One-off event handling for the replacement content editor. Probably not worth creating
		//a custom Knockout binding for one field.
		const replacementContentEditorId = 'ame-cpe-replacement-content-editor';
		const $replacementContentTextarea = $('textarea#' + replacementContentEditorId);
		const initialContent = permissionsEditor.getReplacementContent();

		function updateReplacementContentFromEditor() {
			//Prefer the WP API because it handles automatic paragraph conversion,
			//and also works in text mode.
			if (wp.editor.getContent) {
				const newContent = wp.editor.getContent(replacementContentEditorId);
				if (typeof newContent === 'string') {
					permissionsEditor.setReplacementContent(newContent);
				}
			} else if ($replacementContentTextarea.length > 0) {
				//Fallback: Read the textarea.
				permissionsEditor.setReplacementContent($replacementContentTextarea.val() || '');
			}
		}

		//Throttle updates to the observable. Probably don't need cascading updates on every keypress.
		const throttledContentUpdate = _.throttle(
			updateReplacementContentFromEditor,
			10000,
			{leading: true, trailing: true}
		);

		//First, the plain textarea fallback. This is relevant when the post editor is in
		//text mode or when the user has disabled the visual editor.
		$replacementContentTextarea.val(initialContent);
		$replacementContentTextarea.on('change input', function () {
			throttledContentUpdate();
		});

		//Then the visual editor.
		if (tinymce) {
			function addMceChangeHandler(editor: Editor) {
				//Update the observable when the editor content changes.
				editor.on('change', () => throttledContentUpdate());
			}

			const editor = tinymce.get('ame-cpe-replacement-content-editor');
			if (editor) {
				//Set initial content.
				let content = initialContent;
				//Content is saved with line breaks, let's convert them to paragraphs. WP does this
				//automatically when initializing the editor, but now we're changing the content
				//after the editor already exists, so we need to do it explicitly.
				if (wp.editor.autop) {
					content = wp.editor.autop(content);
				}
				editor.setContent(content);

				addMceChangeHandler(editor);
			} else {
				//Wait for the editor to be initialized.
				const $document = $(document);
				$document.on('tinymce-editor-init', function mceInitListener(_, editor) {
					if (editor.id === replacementContentEditorId) {
						addMceChangeHandler(editor);
						$document.off('tinymce-editor-init', mceInitListener);
					}
				});
			}
		}

		//endregion

		//Update the serialized policy field before the post is saved. This isn't guaranteed to
		//happen by default because some of the observables are rate-limited.
		setTimeout(() => {
			function updatePolicyNow() {
				//Push the latest replacement content to the observable.
				updateReplacementContentFromEditor();
				//Update the serialized policy string.
				permissionsEditor.updateSerializedPolicy();
			}

			//Classic editor.
			$editor.closest('form').on('submit', updatePolicyNow);

			//Block editor. There is a way to do this more cleanly with wp.data.subscribe(), but
			//that gets triggered very often and might not be good for performance.
			//See: https://wordpress.stackexchange.com/questions/319054/trigger-javascript-on-gutenberg-block-editor-save/331317#331317
			const editorButtonSelectors = [
				'#submitpost #publish',
				'#editor .editor-post-save-draft',
				'#editor .editor-header__settings button.is-primary'
			];
			$(editorButtonSelectors.join(', ')).on('click', updatePolicyNow);
		}, 500);
	});
}