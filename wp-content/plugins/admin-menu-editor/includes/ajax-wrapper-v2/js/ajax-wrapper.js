"use strict";
var AjawV2;
(function (AjawV2) {
    class AjaxAction {
        constructor(action, ajaxUrl, nonce = null, requiredMethod = null) {
            this.action = action;
            this.ajaxUrl = ajaxUrl;
            this.nonce = nonce;
            this.requiredMethod = requiredMethod;
        }
        request(params = {}, success, error, method) {
            return this.ajax({
                method: method,
                data: params,
                success: function (data, textStatus, jqXHR) {
                    if (success) {
                        success(data, textStatus, jqXHR);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (error) {
                        let data = jqXHR.responseText;
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
        ajax(settings) {
            settings = {
                method: this.requiredMethod || 'POST',
                ...settings
            };
            if (this.requiredMethod && this.requiredMethod !== settings.method) {
                throw new Error(`Unsupported request method. This action requires ${this.requiredMethod}, got ${settings.method}`);
            }
            const defaultParams = {
                'action': this.action
            };
            if (this.nonce) {
                defaultParams['_ajax_nonce'] = this.nonce;
            }
            settings.data = settings.data ? { ...defaultParams, ...settings.data } : defaultParams;
            return jQuery.ajax(this.ajaxUrl, settings);
        }
        post(params, success, error) {
            return this.request(params, success, error, 'POST');
        }
        get(params, success, error) {
            return this.request(params, success, error, 'GET');
        }
    }
    AjawV2.AjaxAction = AjaxAction;
    class WpJsonError {
        constructor(errors, status, response = null) {
            this.errors = errors;
            this.status = status;
            this.response = response;
        }
        static tryParse(jqXHR) {
            if (typeof jqXHR['responseJSON'] !== 'undefined') {
                const response = jqXHR['responseJSON'];
                //Check if the response looks like something produced by wp_send_json_error().
                if ((typeof response['success'] !== 'undefined')
                    && (response['success'] === false)
                    && (typeof response['data'] !== 'undefined')
                    && Array.isArray(response['data'])) {
                    const errors = [];
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
    AjawV2.WpJsonError = WpJsonError;
    function createAction(config, backupAjaxUrl) {
        let ajaxUrl;
        if (typeof config.ajaxUrl !== 'undefined') {
            ajaxUrl = config.ajaxUrl;
        }
        else if (typeof backupAjaxUrl !== 'undefined') {
            ajaxUrl = backupAjaxUrl;
        }
        else {
            throw new Error('Invalid createAction call. Please provide a backup ajaxUrl or set it in the action config.');
        }
        return new AjaxAction(config.action, ajaxUrl, config.nonce, config.requiredMethod);
    }
    AjawV2.createAction = createAction;
    function createActionMap(config) {
        const actionMap = {};
        for (const actionAlias in config.actions) {
            if (config.actions.hasOwnProperty(actionAlias)) {
                actionMap[actionAlias] = createAction(config.actions[actionAlias], config.ajaxUrl);
            }
        }
        return actionMap;
    }
    AjawV2.createActionMap = createActionMap;
    function createStrictActionMap(config, keys) {
        const actionMap = {};
        for (const key of keys) {
            if (config.actions.hasOwnProperty(key)) {
                actionMap[key] = createAction(config.actions[key], config.ajaxUrl);
            }
            else {
                throw new Error(`Action "${key}" is not defined in the action map configuration.`);
            }
        }
        return actionMap;
    }
    AjawV2.createStrictActionMap = createStrictActionMap;
    const registeredActions = {};
    function registerActions(collection) {
        for (const actionConfig of collection.actions) {
            registeredActions[actionConfig.action] = createAction(actionConfig, collection.ajaxUrl);
        }
    }
    AjawV2.registerActions = registerActions;
    function getAction(action) {
        if (registeredActions.hasOwnProperty(action)) {
            return registeredActions[action];
        }
        throw new Error(`AJAX action "${action}" is not registered.`);
    }
    AjawV2.getAction = getAction;
    function hasAction(action) {
        return registeredActions.hasOwnProperty(action);
    }
    AjawV2.hasAction = hasAction;
})(AjawV2 || (AjawV2 = {}));
//# sourceMappingURL=ajax-wrapper.js.map