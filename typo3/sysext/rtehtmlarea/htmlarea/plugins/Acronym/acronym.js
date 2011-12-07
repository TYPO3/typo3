/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Acronym plugin for htmlArea RTE
 */
/*
 * Define data model for abbreviation data
 */
Ext.define('HTMLArea.model.Abbreviation', {
	extend: 'Ext.data.Model',
	fields: [{
			name: 'term',
			type: 'string'
		},{
			name: 'abbr',
			type: 'string'
		},{
			name: 'language',
			type: 'string'
	}]
});
/*
 * Define Acronym plugin
 */
Ext.define('HTMLArea.Acronym', {
	extend: 'HTMLArea.Plugin',
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		this.pageTSConfiguration = this.editorConfiguration.buttons.acronym;
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '3.0',
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
		var buttonId = 'Acronym';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize('Insert/Modify Acronym'),
			action		: 'onButtonPress',
			hide		: (this.pageTSConfiguration.noAcronym && this.pageTSConfiguration.noAbbr),
			dialog		: true,
			iconCls		: 'htmlarea-action-abbreviation-edit',
			contextMenuTitle: this.localize(buttonId + '-contextMenuTitle')
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
			editable: true,
			forceSelection: true,
			queryMode: 'local',
			selectOnFocus: true,
			triggerAction: 'all',
			typeAhead: true,
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
		var selection = editor._getSelection();
		var abbr = editor._activeElement(selection);
			// Working around Safari issue
		if (!abbr && this.editor.statusBar && this.editor.statusBar.getSelection()) {
			abbr = this.editor.statusBar.getSelection();
		}
		if (!abbr || !/^(acronym|abbr)$/i.test(abbr.nodeName)) {
			abbr = editor._getFirstAncestor(selection, ['acronym', 'abbr']);
		}
		var type = !Ext.isEmpty(abbr) ? abbr.nodeName.toLowerCase() : '';
		this.params = {
			abbr: abbr,
			title: !Ext.isEmpty(abbr) ? abbr.title : '',
			text: !Ext.isEmpty(abbr) ? abbr.innerHTML : this.editor.getSelectedHTML()
		};
			// Open the dialogue window
		this.openDialogue(
			this.getButton(buttonId).tooltip.text,
			buttonId,
			this.getWindowDimensions({ width: 580}, buttonId),
			this.buildTabItemsConfig(abbr),
			this.buildButtonsConfig(abbr, this.okHandler, this.deleteHandler),
			(type == 'acronym') ? 1 : 0
		);
		return false;
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		title: the window title
	 * @param	string		buttonId: the itemId of the button that was pressed
	 * @param	integer		dimensions: the opening width of the window
	 * @param	object		tabItems: the configuration of the tabbed panel
	 * @param	object		buttonsConfig: the configuration of the buttons
	 * @param	number		activeTab: index of the opening tab
	 *
	 * @return	void
	 */
	openDialogue: function (title, buttonId, dimensions, tabItems, buttonsConfig, activeTab) {
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
			items: [{
				xtype: 'tabpanel',
				activeTab: activeTab ? activeTab : 0,
				defaults: {
					xtype: 'container',
					layout: 'anchor',
					defaults: {
						labelWidth: 150
					}
				},
				items: tabItems
			}],
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
		var type = !Ext.isEmpty(element) ? element.nodeName.toLowerCase() : '';
		var tabItems = [];
		var abbrTabItems = [];
			// abbr tab not shown if the current selection is an acronym
		if (type !== 'acronym') {
			if (!this.pageTSConfiguration.noAbbr) {
				this.addConfigElement(this.buildDefinedTermFieldsetConfig((type == 'abbr') ? element : null, 'abbr'), abbrTabItems);
			}
			this.addConfigElement(this.buildUseTermFieldsetConfig((type == 'abbr') ? element : null, 'abbr'), abbrTabItems);
		}
		if (!Ext.isEmpty(abbrTabItems)) {
			tabItems.push({
				title: this.localize('Abbreviation'),
				itemId: 'abbr',
				items: abbrTabItems
			});
		}
		var acronymTabItems = [];
			// acronym tab not shown if the current selection is an abbr
		if (type !== 'abbr') {
			if (!this.pageTSConfiguration.noAcronym) {
				this.addConfigElement(this.buildDefinedTermFieldsetConfig((type == 'acronym') ? element : null, 'acronym'), acronymTabItems);
			}
			this.addConfigElement(this.buildUseTermFieldsetConfig((type == 'abbr') ? element : null, 'abbr'), acronymTabItems);
		}
		if (!Ext.isEmpty(acronymTabItems)) {
			tabItems.push({
				title: this.localize('Acronym'),
				itemId: 'acronym',
				items: acronymTabItems
			});
		}
		return tabItems;
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
	 * This function builds the configuration object for the defined Abbreviation or Acronym fieldset
	 *
	 * @param	object		element: the element being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildDefinedTermFieldsetConfig: function (element, type) {
			// Create global abbreviation store if it does not exist already
		var abbreviationStore = Ext.data.StoreManager.lookup(this.editorId + '-store-' + this.name);
		if (!abbreviationStore) {
			this.abbreviationStore = Ext.create('Ext.data.Store', {
				autoLoad: true,
				model: 'HTMLArea.model.Abbreviation',
				proxy: {
					type: 'ajax',
					url: this.pageTSConfiguration.acronymUrl,
					reader: {
						type: 'json',
						root: type
					}
				},
				storeId: this.editorId + '-store-' + this.name
			});
		}
		var itemsConfig = [];
		itemsConfig.push(Ext.applyIf({
			displayField: 'term',
			fieldLabel: this.getHelpTip('unabridgedTerm', 'Unabridged_term'),
			listConfig: {
				cls: 'htmlarea-combo-list',
				getInnerTpl: function () {
					return '<div data-qtip="{abbr}" class="htmlarea-combo-list-item">{term}</div>';
				},
			},
			itemId: 'termSelector',
			listeners: {
				select: {
					fn: this.onTermSelect,
					scope: this
				}
			},
			store: this.abbreviationStore,
			valueField: 'term',
			width: 350
		}, this.configDefaults['combobox']));
		itemsConfig.push(Ext.applyIf({
			displayField: 'abbr',
			fieldLabel: this.getHelpTip('abridgedTerm', 'Abridged_term'),
			listConfig: {
				cls: 'htmlarea-combo-list',
				getInnerTpl: function () {
					return '<div data-qtip="{language}" class="htmlarea-combo-list-item">{abbr}</div>';
				}
			},
			itemId: 'abbrSelector',
			listeners: {
				select: {
					fn: this.onAbbrSelect,
					scope: this
				}
			},
			store: this.abbreviationStore,
			valueField: 'abbr',
			width: 200
		}, this.configDefaults['combobox']));
		var languagePlugin = this.getPluginInstance('Language');
		if (this.getButton('Language') && languagePlugin) {
			var selectedLanguage = !Ext.isEmpty(element) ? languagePlugin.getLanguageAttribute(element) : 'none';
				// Create global language store if it does not exist already
			var languageStore = Ext.data.StoreManager.lookup(this.editorId + '-store-' + languagePlugin.name);
			if (!languageStore) {
				languageStore = Ext.create('Ext.data.Store', {
					autoLoad: true,
					model: 'HTMLArea.model.default',
					proxy: {
						type: 'ajax',
						url: this.getDropDownConfiguration('Language').dataUrl,
						reader: {
							type: 'json',
							root: 'options',
						}
					},
					storeId: this.editorId + '-store-' + languagePlugin.name
				});
			}
			itemsConfig.push(Ext.apply({
				displayField: 'text',
				fieldLabel: this.getHelpTip('language', 'Language'),
				itemId: 'language',
				listConfig: {
					cls: 'htmlarea-combo-list',
					getInnerTpl: function () {
						return '<div data-qtip="{value}" class="htmlarea-combo-list-item">{text}</div>';
					}
				},
				listeners: {
					afterrender: {
						fn: function (combo) {
								// Somehow getStore method got lost...
							if (!Ext.isFunction(combo.getStore)) {
								combo.getStore = function () {
									return combo.store;
								};
							}
							var store = combo.getStore();
							store.removeAt(0);
							if (selectedLanguage !== 'none') {
								store.insert(0, {
									text: languagePlugin.localize('Remove language mark'),
									value: 'none'
								});
							} else {
								store.insert(0, {
									text: languagePlugin.localize('No language mark'),
									value: 'none'
								});
							}
							combo.setValue(selectedLanguage);
						},
						scope: this
					}
				},
				store: languageStore,
				valueField: 'value',
				width: 350
			}, this.configDefaults['combobox']));
		}
		return {
			xtype: 'fieldset',
			title: this.getHelpTip('preDefined' + ((type == 'abbr') ? 'Abbreviation' : 'Acronym'), 'Defined_' + type),
			items: itemsConfig,
			listeners: {
				afterrender: {
					fn: this.onDefinedTermFieldsetRender,
					scope: this
				}
			}
		};
	},
	/*
	 * Handler on rendering the defined abbreviation fieldset
	 */
	onDefinedTermFieldsetRender: function (fieldset) {
			// Make sure the store is loaded
		if (!this.abbreviationStore.getCount()) {
			this.abbreviationStore.load({
				callback: function (records) {
					this.abbreviationStore.savedSnapshot = this.abbreviationStore.data.clone();
					this.initializeFieldset(fieldset);
				},
				scope: this
			});
		} else {
				// Refresh the store
			this.abbreviationStore.snapshot = this.abbreviationStore.savedSnapshot.clone();
			this.initializeFieldset(fieldset);
		}
	},
	/*
	 * Initialize fieldset
	 * If an abbr is selected but no term is selected, select any corresponding term with the correct language value, if any
	 */
	initializeFieldset: function (fieldset) {
		var termSelector = fieldset.getComponent('termSelector');
		var term = termSelector.getValue();
		var abbrSelector = fieldset.getComponent('abbrSelector');
		var abbr = abbrSelector.getValue();
		var language = '';
		var languageSelector = fieldset.down('combobox[itemId=language]');
		if (languageSelector) {
			var language = languageSelector.getValue();
			if (language == 'none') {
				language = '';
			}
		}
		if (abbr && !term) {
			var abbrStore = abbrSelector.getStore();
			var index = abbrStore.findBy(function (record) {
				return record.get('abbr') == abbr && (!languageSelector || record.get('language') == language);
			}, this);
			if (index !== -1) {
				term = abbrStore.getAt(index).get('term');
				termSelector.setValue(term);
				fieldset.ownerCt.down('component[itemId=useTerm]').setValue(term);
			}
		}
			// Filter the abbreviation store
		this.abbreviationStore.filterBy(function (record) {
			return !this.params.text
				|| !this.params.title
				|| this.params.text == record.get('term')
				|| this.params.text == record.get('abbr')
				|| this.params.title == record.get('term')
				|| this.params.title == record.get('abbr');
		}, this);
			// Make sure the combo lists are filtered
		this.abbreviationStore.snapshot = this.abbreviationStore.data;
		if (this.abbreviationStore.getCount()) {
				// Initialize combos
			this.initializeCombo(termSelector);
			this.initializeCombo(abbrSelector);
		} else {
			fieldset.hide();
		}
	},
	/*
	 * Set initial values
	 * If there is already an abbr and the filtered list has only one or no element, hide the fieldset
	 */
	initializeCombo: function (combo) {
		var store = this.abbreviationStore;
			// Initialize the term and abbr combos
		if (combo.getItemId() == 'termSelector') {
			if (this.params.title) {
				var index = store.findExact('term', this.params.title);
				if (index !== -1) {
					var record = store.getAt(index);
					combo.setValue(record.get('term'));
					this.onTermSelect(combo, [record], false);
				}
			} else if (this.params.text) {
				var index = store.findExact('term', this.params.text);
				if (index !== -1) {
					var record = store.getAt(index);
					combo.setValue(record.get('term'));
					this.onTermSelect(combo, [record], false);
				}
			}
		} else if (combo.getItemId() == 'abbrSelector' && this.params.text) {
			var index = store.findExact('abbr', this.params.text);
			if (index !== -1) {
				var record = store.getAt(index);
				combo.setValue(record.get('abbr'));
				this.onAbbrSelect(combo, [record], false);
			}
		}
	},
	/*
	 * Handler when a term is selected
	 */
	onTermSelect: function (combo, records, options) {
		var record = records[0];
		var fieldset = combo.findParentByType('fieldset');
		var tab = fieldset.findParentByType('container');
		var term = record.get('term');
		var abbr = record.get('abbr');
		var language = record.get('language');
			// Update the abbreviation selector
		tab.down('component[itemId=abbrSelector]').setValue(abbr);
			// Update the language selector
		var languageSelector = tab.down('combobox[itemId=language]');
		if (!Ext.isEmpty(languageSelector) && !Ext.isBoolean(options)) {
			languageSelector.setValue(language ? language : 'none');
		}
			// Update the term to use
		if (!Ext.isBoolean(options)) {
			tab.down('component[itemId=useTerm]').setValue(term);
		}
	},
	/*
	 * Handler when an abbreviation or acronym is selected
	 */
	onAbbrSelect: function (combo, records, options) {
		var record = records[0];
		var fieldset = combo.findParentByType('fieldset');
		var tab = fieldset.findParentByType('container');
		var term = record.get('term');
		var language = record.get('language');
			// Update the term selector
		tab.down('component[itemId=termSelector]').setValue(term);
			// Update the language selector
		var languageSelector = tab.down('combobox[itemId=language]');
		if (!Ext.isEmpty(languageSelector) && !Ext.isBoolean(options)) {
			languageSelector.setValue(language ? language : 'none');
		}
			// Update the term to use
		if (!Ext.isBoolean(options)) {
			tab.down('component[itemId=useTerm]').setValue(term);
		}
	},
	/*
	 * This function builds the configuration object for the Abbreviation or Acronym to use fieldset
	 *
	 * @param	object		element: the element being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildUseTermFieldsetConfig: function (element, type) {
		var itemsConfig = [];
		itemsConfig.push({
			fieldLabel: this.getHelpTip('useThisTerm', 'Use_this_term'),
			labelSeparator: '',
			itemId: 'useTerm',
			value: element ? element.title : '',
			width: 300
		});
		return {
			xtype: 'fieldset',
			title: this.getHelpTip('termToAbridge', 'Term_to_abridge'),
			defaultType: 'textfield',
			items: itemsConfig
		};
	},
	/*
	 * Handler when the ok button is pressed
	 */
	okHandler: function (button, event) {
		this.restoreSelection();
		var tab = this.dialog.down('tabpanel').getActiveTab();
		var type = tab.getItemId();
		var languageSelector = tab.down('component[itemId=language]');
		var language = !Ext.isEmpty(languageSelector) ? languageSelector.getValue() : '';
		var term = tab.down('component[itemId=termSelector]').getValue();
		if (!this.params.abbr) {
			var abbr = this.editor.document.createElement(type);
			abbr.title = tab.down('component[itemId=useTerm]').getValue();
			if (term == abbr.title) {
				abbr.innerHTML = tab.down('component[itemId=abbrSelector]').getValue();
			} else {
				abbr.innerHTML = this.params.text;
			}
			if (language) {
				this.getPluginInstance('Language').setLanguageAttributes(abbr, language);
			}
			this.editor.insertNodeAtSelection(abbr);
		} else {
			var abbr = this.params.abbr;
			abbr.title = tab.down('component[itemId=useTerm]').getValue();
			if (language) {
				this.getPluginInstance('Language').setLanguageAttributes(abbr, language);
			}
			if (term == abbr.title) {
				abbr.innerHTML = tab.down('component[itemId=abbrSelector]').getValue();
			}
		}
		this.close();
		event.stopEvent();
	},
	/*
	 * Handler when the delete button is pressed
	 */
	deleteHandler: function (button, event) {
		this.restoreSelection();
		var abbr = this.params.abbr;
		if (abbr) {
			this.editor.removeMarkup(abbr);
		}
		this.close();
		event.stopEvent();
	},
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if ((mode === 'wysiwyg') && this.editor.isEditable()) {
			var el = this.editor.getParentElement();
			if (el) {
				button.setDisabled(((el.nodeName.toLowerCase() == 'acronym' && this.pageTSConfiguration.noAcronym) || (el.nodeName.toLowerCase() == 'abbr' && this.pageTSConfiguration.noAbbr)));
				button.setInactive(!(el.nodeName.toLowerCase() == 'acronym' && !this.pageTSConfiguration.noAcronym) && !(el.nodeName.toLowerCase() == 'abbr' && !this.pageTSConfiguration.noAbbr));
			}
			button.setTooltip({
				text: this.localize((button.disabled || button.inactive) ? 'Insert abbreviation' : 'Edit abbreviation')
			});
			button.contextMenuTitle = '';
			if (this.dialog) {
				this.dialog.focus();
			}
		}
	}
});
