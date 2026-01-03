<?php

use YahnisElsts\AdminMenuEditor\ContentPermissions\Policy\ContentItemPolicy;
use YahnisElsts\AdminMenuEditor\ContentPermissions\Policy\ErrorMessageProtection;
use YahnisElsts\AdminMenuEditor\ContentPermissions\UserInterface\ContentPermissionsMetaBox;

/**
 * @var array $editorData Provided by the method that includes this template.
 * @var string $cpeSettingsUrl URL to the content permissions section on the settings page.
 * @var string $cpeModulesUrl URL to the "Modules" section on the settings page.
 */

$basicOptions = [
	'everyone'  => _x('Everyone', 'content permissions: basic preset', 'admin-menu-editor'),
	'loggedIn'  => _x('Logged In Users', 'content permissions: basic preset', 'admin-menu-editor'),
	'loggedOut' => _x('Logged Out Users', 'content permissions: basic preset', 'admin-menu-editor'),
	'advanced'  => _x('Advanced', 'content permissions: basic preset', 'admin-menu-editor'),
];
?>
<div id="ame-cpe-permissions-editor-root" data-cpe-editor-data="<?php echo esc_attr(wp_json_encode($editorData)); ?>">
	<?php
	printf(
		'<input type="hidden" name="%1$s" id="%1$s" value="" data-bind="value: serializedPolicy">',
		esc_attr(ContentPermissionsMetaBox::POLICY_FIELD_NAME)
	);
	?>
	<div id="ame-cpe-loading-message" data-bind="visible: false">
		<p><?php
			echo esc_html_x(
				'Loading...',
				'Loading message in the content permissions meta box',
				'admin-menu-editor'
			);
			?></p>
		<p><?php
			echo esc_html_x(
				"If this message doesn't disappear after a few seconds, there may be a JavaScript error.",
				'Loading message in the content permissions meta box',
				'admin-menu-editor'
			);
			?></p>
	</div>
	<div id="ame-cpe-enforcement-disabled-notice" data-bind="visible: enforcementDisabled" style="display: none">
		<p>
			<span class="dashicons dashicons-warning"></span>
			<?php
			//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			// -- Output contains a link, so the whole string can't be HTML-escaped. The link URL is escaped.
			printf(
			/* translators: %s = URL pointing to the "Settings" tab on the "Menu Editor" page, specifically the "Content permissions" section */
				__('Enforcement disabled. You can still edit the permissions, but they won\'t take effect until you <a href="%s">update plugin settings</a>.',
					'admin-menu-editor'
				),
				esc_url($cpeSettingsUrl)
			);
			//phpcs:enable
			?></p>
	</div>
	<div id="ame-cpe-permissions-editor-container" style="display: none" data-bind="visible: true">
		<div class="ame-cpe-main-tabs">
			<ul class="ame-cpe-tab-nav" data-bind="foreach: tabs">
				<li data-bind="css: {'current': $root.activeTab() === $data}">
					<a href="#" data-bind="click: $root.activeTab.bind($data)">
						<span data-bind="text: title"></span>
					</a>
				</li>
			</ul>
		</div>
		<div class="ame-cpe-main-tab-container">
			<div class="ame-cpe-tab ame-cpe-basic-tab" data-bind="visible: activeTab().id === 'basic'">
				<label for="ame-cpe-basic-view-state">
					<?php _e('Who can view this content:', 'admin-menu-editor'); ?>
				</label><br>
				<select data-bind="value: basicViewState" id="ame-cpe-basic-view-state">
					<?php
					foreach ($basicOptions as $value => $label) {
						printf(
							'<option value="%s">%s</option>',
							esc_attr($value),
							esc_html($label)
						);
					}
					?>
				</select>

				<fieldset class="ame-cpe-basic-actor-container" data-bind="disable: (basicViewState() !== 'loggedIn')">
					<span>Only selected roles:</span><br>
					<div data-bind="foreach: basicActorSettings" class="ame-cpe-basic-actor-settings">
						<label class="ame-cpe-basic-actor-setting">
							<input type="checkbox" data-bind="checked: isChecked">
							<span data-bind="text: actor.getDisplayName()"></span>
						</label>
					</div>
					<span id="ame-cpe-basic-actor-shortcuts">
				Check
				<a href="#" data-bind="click: checkAllBasicActors">All</a> |
				<a href="#" data-bind="click: checkNoneBasicActors">None</a>
			</span>
				</fieldset>
			</div>

			<div class="ame-cpe-tab ame-cpe-advanced-tab" data-bind="visible: activeTab().id === 'advanced'">
				<div class="ame-cpe-actor-nav-container">
					<div class="ame-cpe-actor-nav">
						<ol class="ame-cpe-actor-nav-list" data-bind="foreach: advancedTabActors">
							<li class="ame-cpe-actor-nav-item"
							    data-bind="css: {'current': $root.selectedActor() === $data}">
								<a href="#" data-bind="click: $root.selectedActor.bind($data)">
									<span class="ame-cpe-actor-name" data-bind="text: $data.getDisplayName()"></span>
									<!-- ko if: $root.gridsByActorId[$data.getId()] -->
									<span class="ame-cpe-mini-grid"
									      data-bind="foreach: $root.gridsByActorId[$data.getId()]">
								<span class="ame-cpe-mini-grid-item"
								      data-bind="class: cssClass"></span>
							</span>
									<!-- /ko -->
								</a>
							</li>
						</ol>
					</div>
					<div class="row-actions ame-cpe-actor-nav-actions">
				<span class="trash">
					<a href="#"
					   data-bind="
						click: uiResetPermissionsToDefaults,
						css: {'ame-cpe-action-not-applicable' : everyoneHasDefaultPermissions}"><?php
						echo esc_html_x('Reset to defaults', 'Content permissions', 'admin-menu-editor');
						?></a>
				</span>
						<span data-bind="if: undoResetActionVisible">|
					<a href="#" data-bind="click: undoLastPermissionsReset"><?php
						echo esc_html_x('Undo reset', 'Content permissions', 'admin-menu-editor');
						?></a>
				</span>
					</div>
				</div>

				<div class="ame-cpe-actor-settings-container">
					<table class="ame-cpe-actor-settings">
						<tbody data-bind="foreach: actionSettings">
						<tr data-bind="visible: isVisible">
							<th scope="row">
								<label
									data-bind="text: action.label,
							attr: { 'for': 'ame-cpe-permission-input-' + $index() }">
								</label>
							</th>
							<td>
								<div class="ame-cpe-actor-action-setting">
									<!--suppress HtmlUnknownTag -->
									<ame-cpe-permission-options-bar params="setting: $data">
									</ame-cpe-permission-options-bar>

									<a class="ame-cpe-tooltip-trigger"
									   data-bind="if: action.description, attr: {'title': action.description}">
										<span class="dashicons dashicons-info"></span>
									</a>
								</div>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div class="ame-cpe-tab ame-cpe-protection-tab" data-bind="visible: activeTab().id === 'protection'">
				<fieldset>
					<legend>
						<?php _e('Replacement Content', 'admin-menu-editor'); ?>
						<a class="ame-cpe-tooltip-trigger"
						   title="<?php
						   echo esc_attr(sprintf(
						   /* translators: %s = default replacement text for restricted posts */
							   __(
								   'The post content will be replaced with this message when the user doesn\'t have read permissions. Defaults to "%s". If you hide the post from archive pages and also disable direct access, this setting will not be used.',
								   'admin-menu-editor'
							   ),
							   esc_html(ContentItemPolicy::getDefaultReplacementText())
						   )); ?>">
							<span class="dashicons dashicons-info"></span>
						</a>
					</legend>
					<?php
					wp_editor(
						'',
						'ame-cpe-replacement-content-editor',
						[
							'textarea_name' => 'ame-cpe-replacement-content',
							'textarea_rows' => 6,
							'teeny'         => false,
							'editor_height' => 150,
						]
					);
					?>
				</fieldset>

				<fieldset>
					<legend><?php _e('Direct Access Protection', 'admin-menu-editor'); ?></legend>
					<!-- ko using: policy.accessProtection, as: 'accessProtection' -->
					<div data-bind="foreach: accessProtection.protections" class="ame-cpe-protection-options">
						<div class="ame-cpe-protection-type"
						     data-bind="css: {'ame-cpe-is-active-protection': (accessProtection.active() === $data)}">
							<div class="ame-cpe-radio-option">
								<div class="ame-cpe-radio-toggle-column">
									<!--suppress HtmlFormInputWithoutLabel -->
									<input type="radio" name="ame-cpe-active-protection" id=""
									       data-bind="checkedValue: $data, checked: accessProtection.active,
									attr: {'id': 'ame-cpe-protection-input-' + $data.tag}">
								</div>
								<div class="ame-cpe-radio-content-column">
									<label data-bind="
								text: $data.getLabel(),
								attr: {'for': 'ame-cpe-protection-input-' + $data.tag}"></label>
									<div class="ame-cpe-protection-type-settings"
									     data-bind="template: ('ame-cpe-protection-template-' + $data.tag )">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /ko -->
				</fieldset>
			</div>

			<div class="ame-cpe-tab ame-cpe-about-tab" data-bind="visible: activeTab().id === 'about'">
				<ul>
					<li><?php
						echo esc_html_x(
							'The "Content Permissions (AME)" box is part of the Admin Menu Editor plugin.',
							'content permissions - about tab',
							'admin-menu-editor'
						);
						?></li>
					<li><?php
						echo esc_html_x(
							'Normally, only users who can access the menu editor can see this box.',
							'content permissions - about tab',
							'admin-menu-editor'
						)
						?></li>
					<li><?php
						//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
						printf(
						/* translators: %1$s = URL to the "Modules" section on the plugin settings page, %2$s = module name */
							__(
								'If necessary, you can disable this box by going to the <a href="%1$s">Settings</a> tab and unchecking the "%2$s" option in the "Modules" section.',
								'admin-menu-editor'
							),
							esc_url($cpeModulesUrl),
							'Content Permissions' //Module names are currently not translatable.
						);
						//phpcs:enable
						?></li>
				</ul>
			</div>
		</div>
	</div>
</div>
<!-- End of the main meta box content. -->

<template id="ame-cpe-protection-template-replace" style="display: none">
	<span class="description"><?php
		esc_html_e(
			'Displays the post as usual but replaces the content with the specified message.',
			'admin-menu-editor'
		);
		?></span>
</template>

<template id="ame-cpe-protection-template-notFound" style="display: none">
	<span class="description"><?php
		echo esc_html_x(
			'Displays a 404 error page as if the post didn\'t exist.',
			'content permissions',
			'admin-menu-editor'
		);
		?></span>
</template>

<template id="ame-cpe-protection-template-errorMessage" style="display: none">
	<span class="description"><?php
		echo esc_html_x(
			'Displays a plain error page.',
			'content permissions',
			'admin-menu-editor'
		);
		?></span><br>

	<label for="ame-cpe-protection-error-message">
		<?php echo esc_html_x(
			'Error message:',
			'content permissions: label for the custom message field for the "Block access" option',
			'admin-menu-editor'
		); ?>
	</label>
	<textarea data-bind="value: errorMessage" id="ame-cpe-protection-error-message"
	          placeholder="<?php echo esc_attr(ErrorMessageProtection::FALLBACK_MESSAGE); ?>"></textarea>
</template>

<template id="ame-cpe-protection-template-redirect" style="display: none">
	<span class="description"><?php
		echo esc_html_x(
			'Redirects the user to a different location.',
			'content permissions',
			'admin-menu-editor'
		);
		?></span>

	<label for="ame-cpe-protection-redirect-url">
		<?php echo esc_html_x(
			'URL:',
			'content permissions: redirect URL field label',
			'admin-menu-editor'
		); ?>
	</label>
	<input type="text" data-bind="value: targetUrl" id="ame-cpe-protection-redirect-url"
	       placeholder="<?php echo esc_url(home_url()); ?>">

	<label for="ame-cpe-protection-redirect-status">
		<?php echo esc_html_x(
			'Redirect type:',
			'content permissions: redirect status code field label',
			'admin-menu-editor'
		); ?>
	</label>
	<select data-bind="value: redirectCode" id="ame-cpe-protection-redirect-status">
		<option value="301" data-bind="value: 301">301 Moved Permanently</option>
		<option value="302" data-bind="value: 302">302 Found</option>
		<option value="307" data-bind="value: 307">307 Temporary Redirect</option>
	</select>

	<label>
		<input type="checkbox" data-bind="checked: shortcodesEnabled">
		<?php echo esc_html_x(
			'Process shortcodes in the URL',
			'content permissions: shortcode setting for the redirect URL',
			'admin-menu-editor'
		); ?>
	</label>
</template>

<template id="ame-cpe-permission-dropdown-component">
	<!--suppress HtmlFormInputWithoutLabel -->
	<select class="ame-cpe-permission-options"
	        data-bind="options: setting.options, optionsText: 'label', value: setting.selectedOption,
							        optionsAfterRender: $root.addOptionClasses,
							        class: setting.cssClass,
							        attr: { id: selectId }">
	</select>
</template>

<template id="ame-cpe-permission-bar-component">
	<fieldset class="ame-radio-button-bar-control ame-cpe-permission-bar" data-bind="foreach: setting.options">
		<label class="ame-radio-bar-item"
		       data-bind="class: cssClass, css: {'ame-cpe-rb-selected': ($parent.setting.selectedOption() === $data)}">
			<input type="radio"
			       data-bind="checked: $parent.setting.selectedOption, checkedValue: $data">
			<span class="button ame-radio-bar-button ame-rb-has-label">
				<!-- ko if: (dashicon !== '') -->
				<span class="dashicons" data-bind="class: ('dashicons-' + dashicon)"></span>
				<!-- /ko -->
				<span data-bind="text: label, attr: {'data-label': label}" class="ame-cpe-rb-label"></span>
			</span>
		</label>
	</fieldset>
</template>