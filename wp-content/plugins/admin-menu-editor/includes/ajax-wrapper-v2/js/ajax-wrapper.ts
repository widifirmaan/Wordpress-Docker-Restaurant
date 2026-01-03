namespace AjawV2 {
	type SuccessCallback = (response: unknown, textStatus: string, jqXHR: JQueryXHR) => void;
	type ErrorCallback = (
		error: unknown | WpJsonError,
		textStatus: string,
		jqXHR: JQueryXHR,
		errorThrown: string
	) => void;
	type RequestMethod = 'GET' | 'POST' | 'PUT' | 'DELETE';
	type RequestParams = Record<string, unknown>;

	export class AjaxAction {
		constructor(
			public readonly action: string,
			public readonly ajaxUrl: string,
			public readonly nonce: string | null = null,
			public readonly requiredMethod: RequestMethod | null = null,
		) {
		}

		public request(
			params: RequestParams = {},
			success?: SuccessCallback,
			error?: ErrorCallback,
			method?: RequestMethod
		): JQueryXHR {
			return this.ajax({
				method: method,
				data: params,
				success: function (data, textStatus, jqXHR) {
					if (success) {
						success(data, textStatus, jqXHR);
					}
				},
				error: function (jqXHR: JQueryXHR, textStatus, errorThrown) {
					if (error) {
						let data: unknown = jqXHR.responseText;
						if (typeof jqXHR['responseJSON'] !== 'undefined') {
							data = jqXHR['responseJSON'];
						}

						const parsedError = WpJsonError.tryParse(jqXHR);
						if (parsedError) {
							data = parsedError;
						}

						error(data, textStatus, jqXHR, errorThrown);
					}
				}
			});
		}

		public ajax(settings?: JQueryAjaxSettings): JQueryXHR {
			settings = {
				method: this.requiredMethod || 'POST',
				...settings
			};
			if (this.requiredMethod && this.requiredMethod !== settings.method) {
				throw new Error(`Unsupported request method. This action requires ${this.requiredMethod}, got ${settings.method}`);
			}

			const defaultParams: RequestParams = {
				'action': this.action
			};
			if (this.nonce) {
				defaultParams['_ajax_nonce'] = this.nonce;
			}
			settings.data = settings.data ? {...defaultParams, ...settings.data} : defaultParams;

			return jQuery.ajax(this.ajaxUrl, settings);
		}

		public post(params: RequestParams, success?: SuccessCallback, error?: ErrorCallback): JQueryXHR {
			return this.request(params, success, error, 'POST');
		}

		public get(params: RequestParams, success?: SuccessCallback, error?: ErrorCallback): JQueryXHR {
			return this.request(params, success, error, 'GET');
		}
	}

	interface ErrorItem {
		code: string | number;
		message: string;
	}

	export class WpJsonError {
		constructor(
			public readonly errors: ErrorItem[],
			public readonly status: number,
			public readonly response: unknown = null
		) {
		}

		static tryParse(jqXHR: JQueryXHR): WpJsonError | null {
			if (typeof jqXHR['responseJSON'] !== 'undefined') {
				const response = jqXHR['responseJSON'];
				//Check if the response looks like something produced by wp_send_json_error().
				if (
					(typeof response['success'] !== 'undefined')
					&& (response['success'] === false)
					&& (typeof response['data'] !== 'undefined')
					&& Array.isArray(response['data'])
				) {
					const errors: ErrorItem[] = [];
					for (const error of response['data']) {
						if ((typeof error['code'] !== 'undefined') && (typeof error['message'] === 'string')) {
							errors.push({
								code: error['code'],
								message: error['message']
							});
						}
					}
					if (errors.length === 0) {
						//No errors found, probably not the expected response format.
						return null;
					}
					return new WpJsonError(errors, jqXHR.status, response);
				}
			}
			return null;
		}
	}

	export interface ActionConfig {
		action: string;
		ajaxUrl: string;
		nonce?: string | null;
		requiredMethod?: RequestMethod | null;
	}

	export type PartialActionConfig = Omit<ActionConfig, 'ajaxUrl'> & Partial<Pick<ActionConfig, 'ajaxUrl'>>;

	export interface ActionMapConfig {
		ajaxUrl: string;
		actions: Record<string, ActionConfig>;
	}

	export function createAction(config: ActionConfig): AjaxAction;
	export function createAction(config: PartialActionConfig, backupAjaxUrl: string): AjaxAction;
	export function createAction(config: ActionConfig | PartialActionConfig, backupAjaxUrl?: string): AjaxAction {
		let ajaxUrl: string;
		if (typeof config.ajaxUrl !== 'undefined') {
			ajaxUrl = config.ajaxUrl;
		} else if (typeof backupAjaxUrl !== 'undefined') {
			ajaxUrl = backupAjaxUrl;
		} else {
			throw new Error('Invalid createAction call. Please provide a backup ajaxUrl or set it in the action config.');
		}

		return new AjaxAction(
			config.action,
			ajaxUrl,
			config.nonce,
			config.requiredMethod
		);
	}

	export function createActionMap(config: ActionMapConfig): Record<string, AjaxAction> {
		const actionMap: Record<string, AjaxAction> = {};
		for (const actionAlias in config.actions) {
			if (config.actions.hasOwnProperty(actionAlias)) {
				actionMap[actionAlias] = createAction(config.actions[actionAlias], config.ajaxUrl);
			}
		}
		return actionMap;
	}

	export function createStrictActionMap<T extends readonly string[]>(
		config: ActionMapConfig,
		keys: T
	): { [K in T[number]]: AjaxAction } {
		const actionMap = {} as { [K in T[number]]: AjaxAction };
		for (const key of keys) {
			if (config.actions.hasOwnProperty(key)) {
				actionMap[key as T[number]] = createAction(config.actions[key], config.ajaxUrl);
			} else {
				throw new Error(`Action "${key}" is not defined in the action map configuration.`);
			}
		}
		return actionMap;
	}

	const registeredActions: Record<string, AjaxAction> = {};

	interface ActionCollectionConfig {
		ajaxUrl: string;
		actions: PartialActionConfig[];
	}

	export function registerActions(collection: ActionCollectionConfig) {
		for (const actionConfig of collection.actions) {
			registeredActions[actionConfig.action] = createAction(actionConfig, collection.ajaxUrl);
		}
	}

	export function getAction(action: string): AjaxAction {
		if (registeredActions.hasOwnProperty(action)) {
			return registeredActions[action];
		}
		throw new Error(`AJAX action "${action}" is not registered.`);
	}

	export function hasAction(action: string): boolean {
		return registeredActions.hasOwnProperty(action);
	}
}