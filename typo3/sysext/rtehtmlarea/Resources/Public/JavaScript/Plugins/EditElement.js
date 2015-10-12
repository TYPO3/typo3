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
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/Plugins/MicrodataSchema',
	'TYPO3/CMS/Rtehtmlarea/Plugins/Language',
	'TYPO3/CMS/Rtehtmlarea/Plugins/BlockStyle',
	'TYPO3/CMS/Rtehtmlarea/Plugins/TextStyle'],
	function (Plugin, Util, Dom, MicrodataSchema, Language, BlockStyle, TextStyle) {

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
		/*
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
		/*
		 * This function gets called when the button was pressed
		 *
		 * @param	object		editor: the editor instance
		 * @param	string		id: the button id or the key
		 *
		 * @return	boolean		false if action is completed
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
					this.getWindowDimensions(
						{
							width: 450
						},
						buttonId
					),
					this.buildTabItemsConfig(this.element),
					this.buildButtonsConfig(this.element, this.okHandler, this.deleteHandler)
				);
			}
			return false;
		},
		/*
		 * Open the dialogue window
		 *
		 * @param	string		buttonId: the button id
		 * @param	string		title: the window title
		 * @param	object		dimensions: the opening dimensions of the window
		 * @param	object		tabItems: the configuration of the tabbed panel
		 * @param	object		buttonsConfig: the configuration of the buttons
		 *
		 * @return	void
		 */
		openDialogue: function (buttonId, title, dimensions, tabItems, buttonsConfig) {
			this.dialog = new Ext.Window({
				title: this.getHelpTip('', title),
				cls: 'htmlarea-window',
				border: false,
				width: dimensions.width,
				height: 'auto',
				iconCls: this.getButton(buttonId).iconCls,
				listeners: {
					close: {
						fn: this.onClose,
						scope: this
					}
				},
				items: {
					xtype: 'tabpanel',
					activeTab: 0,
					defaults: {
						xtype: 'container',
						layout: 'form',
						defaults: {
							labelWidth: 150
						}
					},
					listeners: {
						tabchange: {
							fn: function (tabpanel, tab) {
								this.setTabPanelHeight(tabpanel, tab);
								this.syncHeight(tabpanel, tab);
							},
							scope: this
						}
					},
					items: tabItems
				},
				buttons: buttonsConfig
			});
			this.show();
		},
		/*
		 * Build the dialogue tab items config
		 *
		 * @param	object		element: the element being edited, if any
		 *
		 * @return	object		the tab items configuration
		 */
		buildTabItemsConfig: function (element) {
			var tabItems = [];
			var generalTabItemConfig = [];
			if (this.removedFieldsets.indexOf('identification') == -1) {
				this.addConfigElement(this.buildIdentificationFieldsetConfig(element), generalTabItemConfig);
			}
			if (this.removedFieldsets.indexOf('style') == -1 && this.removedProperties.indexOf('className') == -1) {
				this.addConfigElement(this.buildClassFieldsetConfig(element), generalTabItemConfig);
			}
			tabItems.push({
				title: this.localize('general'),
				itemId: 'general',
				items: generalTabItemConfig
			});
			if (this.removedFieldsets.indexOf('language') == -1 && this.getPluginInstance('Language')) {
				var languageTabItemConfig = [];
				this.addConfigElement(this.buildLanguageFieldsetConfig(element), languageTabItemConfig);
				tabItems.push({
					title: this.localize('Language'),
					itemId: 'language',
					items: languageTabItemConfig
				});
			}
			if (this.removedFieldsets.indexOf('microdata') == -1 && this.getPluginInstance('MicrodataSchema')) {
				var microdataTabItemConfig = [];
				this.addConfigElement(this.getPluginInstance('MicrodataSchema').buildMicrodataFieldsetConfig(element, this.properties), microdataTabItemConfig);
				tabItems.push({
					title: this.getPluginInstance('MicrodataSchema').localize('microdata'),
					itemId: 'microdata',
					items: microdataTabItemConfig
				});
			}
			if (this.removedFieldsets.indexOf('events') == -1) {
				var eventsTabItemConfig = [];
				this.addConfigElement(this.buildEventsFieldsetConfig(element), eventsTabItemConfig);
				tabItems.push({
					title: this.localize('events'),
					itemId: 'events',
					items: eventsTabItemConfig
				});
			}
			return tabItems;
		},
		/*
		 * This function builds the configuration object for the Identification fieldset
		 *
		 * @param	object		element: the element being edited, if any
		 *
		 * @return	object		the fieldset configuration object
		 */
		buildIdentificationFieldsetConfig: function (element) {
			var itemsConfig = [];
			if (this.removedProperties.indexOf('id') == -1) {
				itemsConfig.push({
					itemId: 'id',
					fieldLabel: this.getHelpTip('id', 'id'),
					value: element ? element.getAttribute('id') : '',
					width: ((this.properties['id'] && this.properties['id'].width) ? this.properties['id'].width : 300)
				});
			}
			if (this.removedProperties.indexOf('title') == -1) {
				itemsConfig.push({
					itemId: 'title',
					fieldLabel: this.getHelpTip('title', 'title'),
					value: element ? element.getAttribute('title') : '',
					width: ((this.properties['title'] && this.properties['title'].width) ? this.properties['title'].width : 300)
				});
			}
			return {
				xtype: 'fieldset',
				title: this.localize('identification'),
				defaultType: 'textfield',
				labelWidth: 100,
				defaults: {
					labelSeparator: ':'
				},
				items: itemsConfig
			};
		},
		/*
		 * This function builds the configuration object for the CSS Class fieldset
		 *
		 * @param	object		element: the element being edited, if any
		 *
		 * @return	object		the fieldset configuration object
		 */
		buildClassFieldsetConfig: function (element) {
			var itemsConfig = [];
			var stylingCombo = this.buildStylingField('className', 'className', 'className');
			this.setStyleOptions(stylingCombo, element);
			itemsConfig.push(stylingCombo);
			return {
				xtype: 'fieldset',
				title: this.localize('className'),
				labelWidth: 100,
				defaults: {
					labelSeparator: ':'
				},
				items: itemsConfig
			};
		},
		/*
		 * This function builds a style selection field
		 *
		 * @param	string		fieldName: the name of the field
		 * @param	string		fieldLabel: the label for the field
		 * @param	string		cshKey: the csh key
		 *
		 * @return	object		the style selection field object
		 */
		buildStylingField: function (fieldName, fieldLabel, cshKey) {
			return new Ext.form.ComboBox(Util.apply({
				xtype: 'combo',
				itemId: fieldName,
				fieldLabel: this.getHelpTip(fieldLabel, cshKey),
				width: ((this.properties['className'] && this.properties['className'].width) ? this.properties['className'].width : 300),
				store: new Ext.data.ArrayStore({
					autoDestroy:  true,
					fields: [ { name: 'text'}, { name: 'value'}, { name: 'style'} ],
					data: [[this.localize('No style'), 'none']]
				})
				}, {
				tpl: '<tpl for="."><div ext:qtip="{value}" style="{style}text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>'
				}, this.configDefaults['combo']
			));
		},
		/*
		 * This function populates the class store and sets the selected option
		 *
		 * @param	object:		comboBox: the combobox object
		 * @param	object		element: the element being edited, if any
		 *
		 * @return	object		the fieldset configuration object
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
		/*
		 * This function builds the configuration object for the Language fieldset
		 *
		 * @param	object		element: the element being edited, if any
		 *
		 * @return	object		the fieldset configuration object
		 */
		buildLanguageFieldsetConfig: function (element) {
			var itemsConfig = [];
			var languagePlugin = this.getPluginInstance('Language');
			var languageConfigurationUrl;
			if (this.editorConfiguration.buttons && this.editorConfiguration.buttons.language && this.editorConfiguration.buttons.language.dataUrl) {
				languageConfigurationUrl = this.editorConfiguration.buttons.language.dataUrl;
			}
			if (languagePlugin && languageConfigurationUrl && this.removedProperties.indexOf('language') == -1) {
				var selectedLanguage = typeof element === 'object' && element !== null ? languagePlugin.getLanguageAttribute(element) : 'none';
				function initLanguageStore (store) {
					if (selectedLanguage !== 'none') {
						store.removeAt(0);
						store.insert(0, new store.recordType({
							text: languagePlugin.localize('Remove language mark'),
							value: 'none'
						}));
					}
				}
				var languageStore = new Ext.data.JsonStore({
					autoDestroy:  true,
					autoLoad: true,
					root: 'options',
					fields: [ { name: 'text'}, { name: 'value'} ],
					url: languageConfigurationUrl,
					listeners: {
						load: initLanguageStore
					}
				});
				itemsConfig.push(Util.apply({
					xtype: 'combo',
					fieldLabel: languagePlugin.getHelpTip('languageCombo', 'Language'),
					itemId: 'lang',
					store: languageStore,
					width: ((this.properties['language'] && this.properties['language'].width) ? this.properties['language'].width : 200),
					value: selectedLanguage
				}, this.configDefaults['combo']));
			}
			if (this.removedProperties.indexOf('direction') == -1) {
				itemsConfig.push(Util.apply({
					xtype: 'combo',
					fieldLabel: languagePlugin.getHelpTip('directionCombo', 'Text direction'),
					itemId: 'dir',
					store: new Ext.data.ArrayStore({
						autoDestroy:  true,
						fields: [ { name: 'text'}, { name: 'value'}],
						data: [
							[languagePlugin.localize('Not set'), 'not set'],
							[languagePlugin.localize('RightToLeft'), 'rtl'],
							[languagePlugin.localize('LeftToRight'), 'ltr']
						]
					}),
					width: ((this.properties['direction'] && this.properties['dirrection'].width) ? this.properties['direction'].width : 200),
					value: typeof element === 'object' && element !== null && element.dir ? element.dir : 'not set'
				}, this.configDefaults['combo']));
			}
			return {
				xtype: 'fieldset',
				title: this.localize('Language'),
				labelWidth: 100,
				defaults: {
					labelSeparator: ':'
				},
				items: itemsConfig
			};
		},
		/*
		 * This function builds the configuration object for the Events fieldset
		 *
		 * @param	object		element: the element being edited, if any
		 *
		 * @return	object		the fieldset configuration object
		 */
		buildEventsFieldsetConfig: function (element) {
			var itemsConfig = [];
			var events = ['onkeydown', 'onkeypress', 'onkeyup', 'onclick', 'ondblclick', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup'];
			if (!/^(base|bdo|br|frame|frameset|head|html|iframe|meta|param|script|style|title)$/i.test(element.nodeName)) {
				var event;
				for (var i = 0, n = events.length; i < n; i++) {
					event = events[i];
					if (this.removedProperties.indexOf(event) == -1) {
						itemsConfig.push({
							itemId: event,
							fieldLabel: this.getHelpTip(event, event),
							value: element ? element.getAttribute(event) : ''
						});
					}
				}
			}
			return itemsConfig.length ? {
				xtype: 'fieldset',
				title: this.getHelpTip('events', 'events'),
				defaultType: 'textfield',
				labelWidth: 100,
				defaults: {
					labelSeparator: ':',
					width: ((this.properties['event'] && this.properties['event'].width) ? this.properties['event'].width : 300)
				},
				items: itemsConfig
			} : null;
		},
		/*
		 * Build the dialogue buttons config
		 *
		 * @param	object		element: the element being edited, if any
		 * @param	function	okHandler: the handler for the ok button
		 * @param	function	deleteHandler: the handler for the delete button
		 *
		 * @return	object		the buttons configuration
		 */
		buildButtonsConfig: function (element, okHandler, deleteHandler) {
			var buttonsConfig = [this.buildButtonConfig('OK', okHandler)];
			if (element) {
				buttonsConfig.push(this.buildButtonConfig('Delete', deleteHandler));
			}
			buttonsConfig.push(this.buildButtonConfig('Cancel', this.onCancel));
			return buttonsConfig;
		},
		/*
		 * Handler when the ok button is pressed
		 */
		okHandler: function (button, event) {
			this.restoreSelection();
			var textFields = this.dialog.findByType('textfield');
			for (var i = textFields.length; --i >= 0;) {
				var field = textFields[i];
				if (field.getXType() !== 'combo') {
					if (field.getValue()) {
						this.element.setAttribute(field.getItemId(), field.getValue());
					} else {
						this.element.removeAttribute(field.getItemId());
					}
				}
			}
			var comboFields = this.dialog.findByType('combo');
			var languageCombo = this.dialog.find('itemId', 'lang')[0];
			for (var i = comboFields.length; --i >= 0;) {
				var field = comboFields[i];
				var itemId = field.getItemId();
				var value = field.getValue();
				switch (itemId) {
					case 'className':
						if (Dom.isBlockElement(this.element)) {
							this.stylePlugin.applyClassChange(this.element, value);
						} else {
								// Do not remove the span element if the language attribute is to be removed
							this.stylePlugin.applyClassChange(this.element, value, languageCombo && (languageCombo.getValue() === 'none'));
						}
						break;
					case 'dir':
						this.element.setAttribute(itemId, (value === 'not set') ? '' : value);
						break;
				}
			}
			var microdataTab = this.dialog.find('itemId', 'microdata')[0];
			if (microdataTab) {
				this.getPluginInstance('MicrodataSchema').setMicrodataAttributes(this.element);
			}
			if (languageCombo) {
				this.getPluginInstance('Language').setLanguageAttributes(this.element, languageCombo.getValue());
			}
			this.close();
			event.stopEvent();
		},
		/*
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
		/*
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if ((mode === 'wysiwyg') && this.editor.isEditable()) {
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
