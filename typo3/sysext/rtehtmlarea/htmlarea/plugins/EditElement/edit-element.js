/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * EditElement plugin for htmlArea RTE
 */
Ext.define('HTMLArea.EditElement', {
	extend: 'HTMLArea.Plugin',
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		this.pageTSConfiguration = this.editorConfiguration.buttons.editelement;
		this.removedFieldsets = (this.pageTSConfiguration && this.pageTSConfiguration.removeFieldsets) ? this.pageTSConfiguration.removeFieldsets : '';
		this.properties = (this.pageTSConfiguration && this.pageTSConfiguration.properties) ? this.pageTSConfiguration.properties : '';
		this.removedProperties = (this.properties && this.properties.removed) ? this.properties.removed : '';
		/*
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
		/*
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
		combobox: {
			cls: 'htmlarea-combo',
			displayField: 'text',
			listConfig: {
				cls: 'htmlarea-combo-list',
				getInnerTpl: function () {
					return '<div data-qtip="{value}" class="htmlarea-combo-list-item">{text}</div>';
				}
			},
			editable: true,
			forceSelection: true,
			helpIcon: true,
			queryMode: 'local',
			selectOnFocus: true,
			triggerAction: 'all',
			typeAhead: true,
			valueField: 'value',
			xtype: 'combobox'
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
		this.element = this.editor.getParentElement();
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
		this.dialog = Ext.create('Ext.window.Window', {
			title: this.getHelpTip('', title),
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			layout: 'anchor',
			resizable: true,
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
					layout: 'anchor',
					defaults: {
						labelWidth: 150
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
			this.stylePlugin = this.getPluginInstance(HTMLArea.isBlockElement(element) ? 'BlockStyle' : 'TextStyle');
			if (this.stylePlugin) {
				this.addConfigElement(this.buildClassFieldsetConfig(element), generalTabItemConfig);
			}
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
			// Create global style store if it does not exist already
		var styleStore = Ext.data.StoreManager.lookup(this.editorId + '-store-' + this.stylePlugin.name);
		if (!styleStore) {
			styleStore = Ext.create('Ext.data.ArrayStore', {
				model: 'HTMLArea.model.' + this.stylePlugin.name,
				storeId: this.editorId + '-store-' + this.stylePlugin.name,
				data: []
			});
		}
		function initStyleCombo (combo) {
			var nodeName = element.nodeName.toLowerCase();
			var classNames = HTMLArea.DOM.getClassNames(element);
				// Somehow getStore method got lost...
			if (!Ext.isFunction(combo.getStore)) {
				combo.getStore = function () {
					return combo.store;
				};
			}
			this.stylePlugin.buildDropDownOptions(combo, nodeName);
			this.stylePlugin.setSelectedOption(combo, classNames, 'noUnknown');
		}
		itemsConfig.push(Ext.applyIf(
			{
				fieldLabel: this.getHelpTip('className', 'className'),
				listConfig: {
					cls: 'htmlarea-combo-list',
					getInnerTpl: function () {
						return '<div data-qtip="{value}" class="htmlarea-combo-list-item">{text}</div>';
					}
				},
				itemId: 'className',
				store: styleStore,
				width: ((this.properties['className'] && this.properties['className'].width) ? this.properties['className'].width : 300),
				listeners: {
					afterrender: {
						fn: initStyleCombo,
						scope: this
					}
				}
			},
			this.configDefaults['combobox']
		));
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
			var selectedLanguage = !Ext.isEmpty(element) ? languagePlugin.getLanguageAttribute(element) : 'none';
			function initLanguageStore (store) {
				if (selectedLanguage !== 'none') {
					store.removeAt(0);
					store.insert(0, {
						text: languagePlugin.localize('Remove language mark'),
						value: 'none'
					});
				}
			}
				// Create global language store if it does not exist already
			var languageStore = Ext.data.StoreManager.lookup(this.editorId + '-store-' + languagePlugin.name);
			if (languageStore) {
				initLanguageStore(languageStore);
			} else {
				languageStore = Ext.create('Ext.data.Store', {
					autoLoad: true,
					model: 'HTMLArea.model.default',
					listeners: {
						load: initLanguageStore
					},
					proxy: {
						type: 'ajax',
						url: languageConfigurationUrl,
						reader: {
							type: 'json',
							root: 'options'
						}
					},
					storeId: this.editorId + '-store-' + languagePlugin.name
				});
			}
			itemsConfig.push(Ext.applyIf(
				{
					fieldLabel: languagePlugin.getHelpTip('languageCombo', 'Language'),
					itemId: 'lang',
					store: languageStore,
					width: ((this.properties['language'] && this.properties['language'].width) ? this.properties['language'].width : 300),
					value: selectedLanguage
				},
				this.configDefaults['combobox']
			));
		}
		if (this.removedProperties.indexOf('direction') == -1) {
				// Create direction options global store
			var directionStore = Ext.data.StoreManager.lookup('HTMLArea' + '-store-' + languagePlugin.name + '-direction');
			if (!directionStore) {
				directionStore = Ext.create('Ext.data.ArrayStore', {
					model: 'HTMLArea.model.Default',
					storeId: 'HTMLArea' + '-store-' + languagePlugin.name + '-direction'
				});
				directionStore.loadData([
					{
						text: languagePlugin.localize('Not set'),
						value: 'not set'
					},{
						text: languagePlugin.localize('RightToLeft'),
						value: 'rtl'
					},{
						text: languagePlugin.localize('LeftToRight'),
						value: 'ltr'
					}
				]);
			}
			itemsConfig.push(Ext.applyIf(
				{
					fieldLabel: languagePlugin.getHelpTip('directionCombo', 'Text direction'),
					itemId: 'dir',
					store: directionStore,
					value: !Ext.isEmpty(element) && element.dir ? element.dir : 'not set',
					width: ((this.properties['direction'] && this.properties['dirrection'].width) ? this.properties['direction'].width : 300)
				},
				this.configDefaults['combobox']
			));
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
			Ext.each(events, function (event) {
				if (this.removedProperties.indexOf(event) == -1) {
					itemsConfig.push({
						itemId: event,
						fieldLabel: this.getHelpTip(event, event),
						value: element ? element.getAttribute(event) : ''
					});
				}
			}, this);
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
		var textFields = this.dialog.query('textfield');
		Ext.each(textFields, function (field) {
			this.element.setAttribute(field.getItemId(), field.getValue());
		}, this);
		var comboFields = this.dialog.query('combobox');
		Ext.each(comboFields, function (field) {
			var itemId = field.getItemId();
			var value = field.getValue();
			switch (itemId) {
				case 'className':
					this.stylePlugin.applyClassChange(this.element, value);
					break;
				case 'lang':
					this.getPluginInstance('Language').setLanguageAttributes(this.element, value);
					break;
				case 'dir':
					this.element.setAttribute(itemId, (value == 'not set') ? '' : value);
					break;
			}
		}, this);
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
			HTMLArea.removeFromParent(this.element);
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
