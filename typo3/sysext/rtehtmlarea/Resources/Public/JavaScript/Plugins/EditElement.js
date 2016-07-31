/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * EditElement plugin for htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/Plugins/MicrodataSchema',
	'TYPO3/CMS/Rtehtmlarea/Plugins/Language',
	'TYPO3/CMS/Rtehtmlarea/Plugins/BlockStyle',
	'TYPO3/CMS/Rtehtmlarea/Plugins/TextStyle',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Components/Select',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, Util, Dom, MicrodataSchema, Language, BlockStyle, TextStyle, Select, $, Modal, Severity) {

	var EditElement = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(EditElement, Plugin);
	Util.apply(EditElement.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.pageTSConfiguration = this.editorConfiguration.buttons.editelement;
			this.removedFieldsets = (this.pageTSConfiguration && this.pageTSConfiguration.removeFieldsets) ? this.pageTSConfiguration.removeFieldsets : '';
			this.properties = (this.pageTSConfiguration && this.pageTSConfiguration.properties) ? this.pageTSConfiguration.properties : '';
			this.removedProperties = (this.properties && this.properties.removed) ? this.properties.removed : '';

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.0',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);

			/**
			 * Registering the button
			 */
			var buttonId = 'EditElement';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('editElement'),
				action		: 'onButtonPress',
				dialog		: true,
				iconCls		: 'htmlarea-action-element-edit'
			};
			this.registerButton(buttonConfiguration);
			return true;
		},
		/**
		 * Sets of default configuration values for dialogue form fields
		 */
		configDefaults: {
			combo: {
				editable: true,
				selectOnFocus: true,
				typeAhead: true,
				triggerAction: 'all',
				forceSelection: true,
				mode: 'local',
				valueField: 'value',
				displayField: 'text',
				helpIcon: true,
				tpl: '<tpl for="."><div ext:qtip="{value}" style="text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>'
			}
		},
		/**
		 * This function gets called when the button was pressed
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 * @return {Boolean} False if action is completed
		 */
		onButtonPress: function(editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			// Get the parent element of the current selection
			this.element = this.editor.getSelection().getParentElement();
			if (this.element && !/^body$/i.test(this.element.nodeName)) {
					// Open the dialogue window
				this.openDialogue(
					buttonId,
					'editElement',
					this.buildTabItemsConfig(this.element),
					this.buildButtonsConfig(this.element, this.okHandler, this.deleteHandler)
				);
			}
			return false;
		},
		/**
		 * Open the dialogue window
		 *
		 * @param {String} buttonId The button id
		 * @param {String} title The window title
		 * @param {Object} $tabItems The configuration of the tabbed panel
		 * @param {Object} buttonsConfig The configuration of the buttons
		 */
		openDialogue: function (buttonId, title, $tabItems, buttonsConfig) {
			this.dialog = Modal.show(this.localize(title), $tabItems, Severity.notice, buttonsConfig);
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},
		/**
		 * Build the dialogue tab items config
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The tab items configuration
		 */
		buildTabItemsConfig: function (element) {
			var $tabs = $('<ul />', {'class': 'nav nav-tabs', role: 'tablist'}),
				$tabContent = $('<div />', {'class': 'tab-content'}),
				$finalMarkup,
				generalTabItemConfig = [],
				languageTabItemConfig = [],
				microdataTabItemConfig = [],
				eventsTabItemConfig = [];

			if (this.removedFieldsets.indexOf('identification') === -1) {
				this.addConfigElement(this.buildIdentificationFieldsetConfig(element), generalTabItemConfig);
			}
			if (this.removedFieldsets.indexOf('style') === -1 && this.removedProperties.indexOf('className') === -1) {
				this.addConfigElement(this.buildClassFieldsetConfig(element), generalTabItemConfig);
			}

			if (generalTabItemConfig.length > 0) {
				this.buildTabMarkup($tabs, $tabContent, 'general', generalTabItemConfig, this.localize('general'));
			}
			if (this.getButton('Language') && this.removedFieldsets.indexOf('language') === -1 && this.getPluginInstance('Language')) {
				this.addConfigElement(this.buildLanguageFieldsetConfig(element), languageTabItemConfig);
				this.buildTabMarkup($tabs, $tabContent, 'language', languageTabItemConfig, this.localize('Language'));
			}
			if (this.removedFieldsets.indexOf('microdata') === -1 && this.getPluginInstance('MicrodataSchema')) {
				this.addConfigElement(this.getPluginInstance('MicrodataSchema').buildMicrodataFieldsetConfig(element, this.properties), microdataTabItemConfig);
				this.buildTabMarkup($tabs, $tabContent, 'microdata', microdataTabItemConfig, this.getPluginInstance('MicrodataSchema').localize('microdata'));
			}
			if (this.removedFieldsets.indexOf('events') === -1) {
				this.addConfigElement(this.buildEventsFieldsetConfig(element), eventsTabItemConfig);
				this.buildTabMarkup($tabs, $tabContent, 'events', eventsTabItemConfig, this.localize('events'));
			}

			$tabs.find('li:first').addClass('active');
			$tabContent.find('.tab-pane:first').addClass('active');

			$finalMarkup = $('<form />', {'class': 'form-horizontal'}).append($tabs, $tabContent);

			return $finalMarkup;
		},
		/**
		 * This function builds the configuration object for the Identification fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildIdentificationFieldsetConfig: function (element) {
			var $fieldset = $('<fieldset />');

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('identification'))
			);

			if (this.removedProperties.indexOf('id') === -1) {
				$fieldset.append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('id', 'id')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<input />', {name: 'id', 'class': 'form-control', value: element ? element.getAttribute('id') : ''})
						)
					)
				);
			}
			if (this.removedProperties.indexOf('title') === -1) {
				$fieldset.append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('title', 'title')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<input />', {name: 'title', 'class': 'form-control', value: element ? element.getAttribute('title') : ''})
						)
					)
				);
			}

			return $fieldset;
		},
		/**
		 * This function builds the configuration object for the CSS Class fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildClassFieldsetConfig: function (element) {
			var $fieldset = $('<fieldset />'),
				stylingCombo = new Select(this.buildStylingField('className', 'className', 'className'));

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('className'))
			);

			stylingCombo.render($fieldset[0]);
			this.setStyleOptions(stylingCombo, element);

			return $fieldset;
		},
		/**
		 * This function builds a style selection field
		 *
		 * @param {String} fieldName The name of the field
		 * @param {String} fieldLabel The label for the field
		 * @param {String} cshKey The csh key
		 * @return {Object} The style selection field object
		 */
		buildStylingField: function (fieldName, fieldLabel, cshKey) {
			// This is a nasty hack to fake ExtJS object configuration
			return Util.apply(
				{
					xtype: 'htmlareaselect',
					itemId: fieldName,
					fieldLabel: this.getHelpTip(fieldLabel, cshKey),
					helpTitle: typeof TYPO3.ContextHelp !== 'undefined' ? '' : this.localize(fieldTitle),
					width: ((this.properties['className'] && this.properties['className'].width) ? this.properties['className'].width : 300)
				},
				this.configDefaults['combo']
			);
		},
		/**
		 * This function populates the class store and sets the selected option
		 *
		 * @param {Object} comboBox The combobox object
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		setStyleOptions: function (comboBox, element) {
			var nodeName = element.nodeName.toLowerCase();
			this.stylePlugin = this.getPluginInstance(Dom.isBlockElement(element) ? 'BlockStyle' : 'TextStyle');
			if (comboBox && this.stylePlugin) {
				var classNames = Dom.getClassNames(element);
				this.stylePlugin.buildDropDownOptions(comboBox, nodeName);
				this.stylePlugin.setSelectedOption(comboBox, classNames);
			}
		},
		/**
		 * This function builds the configuration object for the Language fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildLanguageFieldsetConfig: function (element) {
			var self = this,
				$fieldset = $('<fieldset />', {id: 'languageFieldset'}),
				languagePlugin = this.getPluginInstance('Language'),
				languageConfigurationUrl;

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(self.localize('Language'))
			);

			if (this.editorConfiguration.buttons && this.editorConfiguration.buttons.language && this.editorConfiguration.buttons.language.dataUrl) {
				languageConfigurationUrl = this.editorConfiguration.buttons.language.dataUrl;
			}
			if (languagePlugin && languageConfigurationUrl && this.removedProperties.indexOf('language') === -1) {
				var selectedLanguage = typeof element === 'object' && element !== null ? languagePlugin.getLanguageAttribute(element) : 'none';

				$fieldset.append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(languagePlugin.getHelpTip('languageCombo', 'Language')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<select />', {name: 'lang', 'class': 'form-control'})
						)
					)
				);

				$.ajax({
					url: this.getDropDownConfiguration('Language').dataUrl,
					dataType: 'json',
					success: function (response) {
						var $select = $fieldset.find('select[name="lang"]');

						for (var language in response.options) {
							if (response.options.hasOwnProperty(language)) {
								if (selectedLanguage !== 'none') {
									response.options[language].value = 'none';
									response.options[language].text = languageObject.localize('Remove language mark');
								}
								var attributeConfiguration = {value: response.options[language].value};
								if (selectedLanguage === response.options[language].value) {
									attributeConfiguration.selected = 'selected';
								}
								$select.append(
									$('<option />', attributeConfiguration).text(response.options[language].text)
								);
							}
						}
					}
				});
			}
			if (this.removedProperties.indexOf('direction') === -1) {
				$fieldset = this.attachSelectMarkup(
					$fieldset,
					languagePlugin.getHelpTip('directionCombo', 'Text direction'),
					'dir',
					[
						[languagePlugin.localize('Not set'), 'not set'],
						[languagePlugin.localize('RightToLeft'), 'rtl'],
						[languagePlugin.localize('LeftToRight'), 'ltr']
					],
					typeof element === 'object' && element !== null && element.dir ? element.dir : 'not set'
				);
			}
			return $fieldset;
		},
		/**
		 * This function builds the configuration object for the Events fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 *
		 * @return {Object} The fieldset configuration object
		 */
		buildEventsFieldsetConfig: function (element) {
			var $fieldset = $('<fieldset />');
			var events = ['onkeydown', 'onkeypress', 'onkeyup', 'onclick', 'ondblclick', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup'];
			if (!/^(base|bdo|br|frame|frameset|head|html|iframe|meta|param|script|style|title)$/i.test(element.nodeName)) {
				var event;
				for (var i = 0, n = events.length; i < n; i++) {
					event = events[i];
					if (this.removedProperties.indexOf(event) === -1) {
						$fieldset.append(
							$('<div />', {'class': 'form-group'}).append(
								$('<label />', {'class': 'col-sm-3'}).html(this.getHelpTip(event, event)),
								$('<div />', {'class': 'col-sm-9'}).append(
									$('<input />', {name: event, 'class': 'form-control', value: element ? element.getAttribute(event) : ''})
								)
							)
						);
					}
				}
			}

			return $fieldset;
		},
		/**
		 * Build the dialogue buttons config
		 *
		 * @param {Object} element The element being edited, if any
		 * @param {Function} okHandler The handler for the ok button
		 * @param {Function} deleteHandler The handler for the delete button
		 * @return {Object} The buttons configuration
		 */
		buildButtonsConfig: function (element, okHandler, deleteHandler) {
			var buttonsConfig = [];
			buttonsConfig.push(this.buildButtonConfig('Cancel', $.proxy(this.onCancel, this), true));
			if (element) {
				buttonsConfig.push(this.buildButtonConfig('Delete', $.proxy(deleteHandler, this)));
			}
			buttonsConfig.push(this.buildButtonConfig('OK', $.proxy(okHandler, this), false, Severity.notice));
			return buttonsConfig;
		},
		/**
		 * Handler when the ok button is pressed
		 *
		 * @param {Event} e
		 */
		okHandler: function (e) {
			this.restoreSelection();
			var textFields = this.dialog.find('input');
			for (var i = textFields.length; --i >= 0;) {
				var field = $(textFields[i]),
					value = field.val();
				if (value) {
					this.element.setAttribute(field.attr('name'), value);
				} else {
					this.element.removeAttribute(field.attr('name'));
				}
			}
			var comboFields = this.dialog.find('select');
			var languageCombo = this.dialog.find('[name="lang"]'),
				languageComboValue = languageCombo.val();
			for (var i = comboFields.length; --i >= 0;) {
				var field = $(comboFields[i]),
					itemId = field.attr('name'),
					value = field.val();
				switch (itemId) {
					case 'className':
						if (Dom.isBlockElement(this.element)) {
							this.stylePlugin.applyClassChange(this.element, value);
						} else {
							// Do not remove the span element if the language attribute is to be removed
							this.stylePlugin.applyClassChange(this.element, value, languageCombo && languageComboValue === 'none');
						}
						break;
					case 'dir':
						this.element.setAttribute(itemId, value === 'not set' ? '' : value);
						break;
				}
			}
			var microdataTab = this.dialog.find('[role="tab"] [href="#microdata"]');
			if (microdataTab) {
				this.getPluginInstance('MicrodataSchema').setMicrodataAttributes(this.element);
			}
			if (languageCombo) {
				this.getPluginInstance('Language').setLanguageAttributes(this.element, languageComboValue);
			}
			Modal.currentModal.trigger('modal-dismiss');
			e.stopImmediatePropagation();
		},
		/**
		 * Handler when the delete button is pressed
		 */
		deleteHandler: function (button, event) {
			this.restoreSelection();
			if (this.element) {
				// Delete the element
				Dom.removeFromParent(this.element);
			}
			this.close();
			event.stopEvent();
		},
		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				// Disable the button if the first ancestor is the document body
				button.setDisabled(!ancestors.length || /^body$/i.test(ancestors[0].nodeName));
				if (this.dialog) {
					this.dialog.focus();
				}
			}
		}
	});

	return EditElement;
});