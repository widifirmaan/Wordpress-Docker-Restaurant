"use strict";
var AmeKoFreeExtensions;
(function (AmeKoFreeExtensions) {
    class SaveSettingsForm {
        constructor(config) {
            this.isSaving = ko.observable(false);
            this.submitUrl = config.submitUrl;
            this.action = config.action;
            this.actionNonce = config.actionNonce;
            this.referer = config.referer || '';
            this.settingsGetter = config.settingsGetter;
            this.settingsJson = ko.observable('');
            this.saveButtonText = config.saveButtonText || 'Save Changes';
            this.wrapButton = (typeof config.wrapButton !== 'undefined') ? config.wrapButton : true;
            let formClasses;
            if (config.formClasses) {
                formClasses = ko.isObservable(config.formClasses)
                    ? config.formClasses
                    : ko.observable(config.formClasses || []);
            }
            else {
                formClasses = ko.observable([]);
            }
            this.formClassString = ko.pureComputed(() => formClasses().join(' '));
            this.includesSelectedActor = !!config.selectedActor;
            this.selectedActorId = ko.pureComputed(() => {
                if (config.selectedActor) {
                    const actor = config.selectedActor();
                    return actor ? actor.getId() : '';
                }
                return '';
            });
            this.extraFields = config.extraFields || [];
        }
        saveChanges() {
            const settings = this.settingsGetter();
            this.settingsJson(JSON.stringify(settings));
            this.isSaving(true);
            return true;
        }
    }
    AmeKoFreeExtensions.SaveSettingsForm = SaveSettingsForm;
    ko.components.register('ame-save-settings-form', {
        viewModel: {
            createViewModel: function (params) {
                if (params.form instanceof SaveSettingsForm) {
                    return params.form;
                }
                throw new Error('Invalid form parameter');
            }
        },
        template: `
			<form method="post" class="ame-ko-save-form" 
				  data-bind="submit: saveChanges, class: formClassString, attr: {action: submitUrl}">
				   
				<input type="hidden" name="settings" data-bind="value: settingsJson">
				<input type="hidden" name="action" data-bind="value: action">
				<input type="hidden" name="_wpnonce" data-bind="value: actionNonce">
				<!-- ko if: referer -->
					<input type="hidden" name="_wp_http_referer" data-bind="value: referer">
				<!-- /ko -->
				
				<!-- ko if: includesSelectedActor -->
					<input type="hidden" name="selectedActor" data-bind="value: selectedActorId">
				<!-- /ko -->
				
				<!-- ko foreach: extraFields -->
					<!-- ko if: $data[0] -->
						<input type="hidden" data-bind="attr: {name: $data[0]}, value: $data[1]">
					<!-- /ko -->
				<!-- /ko -->
				
				<!-- ko if: wrapButton -->
				<p class="submit">
					<input type="submit" class="button button-primary" disabled="disabled" 
						   data-bind="disable: isSaving(), value: saveButtonText" />
				</p>	
				<!-- /ko -->
				<!-- ko ifnot: wrapButton -->
					<input type="submit" class="button button-primary" disabled="disabled" 
						   data-bind="disable: isSaving(), value: saveButtonText" />
				<!-- /ko -->
			</form>
		`
    });
})(AmeKoFreeExtensions || (AmeKoFreeExtensions = {}));
//# sourceMappingURL=free-ko-extensions.js.map