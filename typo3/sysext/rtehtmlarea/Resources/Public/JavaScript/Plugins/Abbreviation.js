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
 * Abbreviation plugin for htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, Util) {

	var Abbreviation = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(Abbreviation, Plugin);
	Util.apply(Abbreviation.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function(editor) {
			this.pageTSConfiguration = this.editorConfiguration.buttons.abbreviation;
			var removeFieldsets = (this.pageTSConfiguration && this.pageTSConfiguration.removeFieldsets) ? this.pageTSConfiguration.removeFieldsets : '';
			removeFieldsets = removeFieldsets.split(',');
			var fieldsets = ['abbreviation', 'definedAbbreviation', 'acronym' ,'definedAcronym'];
			this.enabledFieldsets = {};
			for (var i = fieldsets.length; --i >= 0;) {
				this.enabledFieldsets[fieldsets[i]] = removeFieldsets.indexOf(fieldsets[i]) === -1;
			}
			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '7.0',
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
			var buttonId = 'Abbreviation';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('Insert abbreviation'),
				action		: 'onButtonPress',
				dialog		: true,
				iconCls		: 'htmlarea-action-abbreviation-edit',
				contextMenuTitle: this.localize(buttonId + '-contextMenuTitle')
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
				mode: 'local'
			}
		},

		/**
		 * This function gets called when the button was pressed
		 *
		 * @param object editor: the editor instance
		 * @param string id: the button id or the key
		 * @return boolean false if action is completed
		 */
		onButtonPress: function(editor, id) {
				// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			var abbr = this.getCurrentAbbrElement();
			var type = typeof abbr === 'object' && abbr !== null ? abbr.nodeName.toLowerCase() : '';
			this.params = {
				abbr: abbr,
				title: typeof abbr === 'object' && abbr !== null ? abbr.title : '',
				text: typeof abbr === 'object' && abbr !== null ? abbr.innerHTML : this.editor.getSelection().getHtml()
			};
				// Open the dialogue window
			this.openDialogue(
				this.getButton(buttonId).tooltip,
				buttonId,
				this.getWindowDimensions({ width: 580}, buttonId),
				this.buildTabItemsConfig(abbr),
				this.buildButtonsConfig(abbr, this.okHandler, this.deleteHandler),
				type
			);
			return false;
		},

		/**
		 * Get the current abbr or aconym element, if any is selected
		 *
		 * @return object the element or null
		 */
		getCurrentAbbrElement: function() {
			var abbr = this.editor.getSelection().getParentElement();
			// Working around Safari issue
			if (!abbr && this.editor.statusBar && this.editor.statusBar.getSelection()) {
				abbr = this.editor.statusBar.getSelection();
			}
			if (!abbr || !/^(acronym|abbr)$/i.test(abbr.nodeName)) {
				abbr = this.editor.getSelection().getFirstAncestorOfType(['abbr', 'acronym']);
			}
			return abbr;
		},

		/**
		 * Open the dialogue window
		 *
		 * @param string title: the window title
		 * @param string buttonId: the itemId of the button that was pressed
		 * @param integer dimensions: the opening width of the window
		 * @param object tabItems: the configuration of the tabbed panel
		 * @param object buttonsConfig: the configuration of the buttons
		 * @param string activeTab: itemId of the opening tab
		 * @return	void
		 */
		openDialogue: function (title, buttonId, dimensions, tabItems, buttonsConfig, activeTab) {
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
					activeTab: activeTab ? activeTab : 0,
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

		/**
		 * Build the dialogue tab items config
		 *
		 * @param object element: the element being edited, if any
		 * @return object the tab items configuration
		 */
		buildTabItemsConfig: function (element) {
			var type = typeof element === 'object' && element !== null ? element.nodeName.toLowerCase() : '';
			var tabItems = [];
			var abbrTabItems = [];
			// abbreviation tab not shown if the current selection is an acronym
			if (type !== 'acronym') {
				// definedAbbreviation fieldset not shown if no pre-defined abbreviation exists
				if (!this.pageTSConfiguration.noAbbr && this.enabledFieldsets['definedAbbreviation']) {
					this.addConfigElement(this.buildDefinedTermFieldsetConfig((type === 'abbr') ? element : null, 'abbr'), abbrTabItems);
				}
				// abbreviation fieldset not shown if the selection is empty or not inside an abbr element
				if ((!this.editor.getSelection().isEmpty() || type === 'abbr') && this.enabledFieldsets['abbreviation']) {
					this.addConfigElement(this.buildUseTermFieldsetConfig((type === 'abbr') ? element : null, 'abbr'), abbrTabItems);
				}
			}
			if (abbrTabItems.length > 0) {
				tabItems.push({
					title: this.localize('Abbreviation'),
					itemId: 'abbr',
					items: abbrTabItems
				});
			}
			var acronymTabItems = [];
			// acronym tab not shown if the current selection is an abbreviation
			if (type !== 'abbr') {
				// definedAcronym fieldset not shown if no pre-defined acronym exists
				if (!this.pageTSConfiguration.noAcronym && this.enabledFieldsets['definedAcronym']) {
					this.addConfigElement(this.buildDefinedTermFieldsetConfig((type === 'acronym') ? element : null, 'acronym'), acronymTabItems);
				}
				// acronym fieldset not shown if the selection is empty or not inside an acronym element
				if ((!this.editor.getSelection().isEmpty() || type === 'acronym') && this.enabledFieldsets['acronym']) {
					this.addConfigElement(this.buildUseTermFieldsetConfig((type === 'acronym') ? element : null, 'acronym'), acronymTabItems);
				}
			}
			if (acronymTabItems.length > 0) {
				tabItems.push({
					title: this.localize('Acronym'),
					itemId: 'acronym',
					items: acronymTabItems
				});
			}
			return tabItems;
		},

		/**
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

		/**
		 * This function builds the configuration object for the defined Abbreviation or Acronym fieldset
		 *
		 * @param object element: the element being edited, if any
		 * @param string type: 'abbr' or 'acronym'
		 *
		 * @return object the fieldset configuration object
		 */
		buildDefinedTermFieldsetConfig: function (element, type) {
			var itemsConfig = [];
			itemsConfig.push(Util.apply({
				xtype: 'combo',
				displayField: 'term',
				valueField: 'term',
				fieldLabel: this.getHelpTip('unabridgedTerm', 'Unabridged_term'),
				itemId: 'termSelector',
				tpl: '<tpl for="."><div ext:qtip="{abbr}" style="text-align:left;font-size:11px;" class="x-combo-list-item">{term}</div></tpl>',
				store: new Ext.data.JsonStore({
					autoDestroy:  true,
					autoLoad: false,
					root: type,
					fields: [ { name: 'term'}, { name: 'abbr'},  { name: 'language'}],
					url: this.pageTSConfiguration.abbreviationUrl
				}),
				width: 350,
				listeners: {
					afterrender: {
						fn: function (combo) {
							// Ensure the store is loaded
							combo.getStore().load({
								callback: function () { this.onSelectorRender(combo); },
								scope: this
							});
						},
						scope: this
					},
					select: {
						fn: this.onTermSelect,
						scope: this
					}
				}
			}, this.configDefaults['combo']));
			itemsConfig.push(Util.apply({
				xtype: 'combo',
				displayField: 'abbr',
				valueField: 'abbr',
				tpl: '<tpl for="."><div ext:qtip="{language}" style="text-align:left;font-size:11px;" class="x-combo-list-item">{abbr}</div></tpl>',
				fieldLabel: this.getHelpTip('abridgedTerm', 'Abridged_term'),
				itemId: 'abbrSelector',
				store: new Ext.data.JsonStore({
					autoDestroy:  true,
					autoLoad: false,
					root: type,
					fields: [ { name: 'term'}, { name: 'abbr'},  { name: 'language'}],
					url: this.pageTSConfiguration.abbreviationUrl
				}),
				width: 100,
				listeners: {
					afterrender: {
						fn: function (combo) {
							// Ensure the store is loaded
							combo.getStore().load({
								callback: function () { this.onSelectorRender(combo); },
								scope: this
							});
						},
						scope: this
					},
					select: {
						fn: this.onAbbrSelect,
						scope: this
					}
				}
			}, this.configDefaults['combo']));
			var languageObject = this.getPluginInstance('Language');
			if (this.getButton('Language')) {
				var selectedLanguage = typeof element === 'object' && element !== null ? languageObject.getLanguageAttribute(element) : 'none';
				itemsConfig.push(Util.apply({
					xtype: 'combo',
					fieldLabel: this.getHelpTip('language', 'Language'),
					itemId: 'language',
					valueField: 'value',
					displayField: 'text',
					tpl: '<tpl for="."><div ext:qtip="{value}" style="text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>',
					store: new Ext.data.JsonStore({
						autoDestroy:  true,
						root: 'options',
						fields: [ { name: 'text'}, { name: 'value'} ],
						url: this.getDropDownConfiguration('Language').dataUrl,
						listeners: {
							load: {
								fn: function (store) {
									if (selectedLanguage !== 'none') {
										store.removeAt(0);
										store.insert(0, new store.recordType({
											text: languageObject.localize('Remove language mark'),
											value: 'none'
										}));
									}
								}
							}
						}
					}),
					width: 200,
					value: selectedLanguage,
					listeners: {
						beforerender: {
							fn: function (combo) {
								// Ensure the store is loaded
								combo.getStore().load({
									callback: function () { combo.setValue(selectedLanguage); }
								});
							}
						}
					}
				}, this.configDefaults['combo']));
			}
			return {
				xtype: 'fieldset',
				title: this.getHelpTip('preDefined' + ((type == 'abbr') ? 'Abbreviation' : 'Acronym'), 'Defined_' + type),
				items: itemsConfig,
				listeners: {
					render: {
						fn: this.onDefinedTermFieldsetRender,
						scope: this
					}
				}
			};
		},

		/**
		 * Handler on rendering the defined abbreviation fieldset
		 * If an abbr is selected but no term is selected, select any corresponding term with the correct language value, if any
		 */
		onDefinedTermFieldsetRender: function (fieldset) {
			var termSelector = fieldset.find('itemId', 'termSelector')[0];
			var term = termSelector.getValue();
			var abbrSelector = fieldset.find('itemId', 'abbrSelector')[0];
			var abbr = abbrSelector.getValue();
			var language = '';
			var languageSelector = fieldset.find('itemId', 'language')[0];
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
					var useTermField = fieldset.ownerCt.find('itemId', 'useTerm');
					if (useTermField.length) {
						useTermField[0].setValue(term);
					}
				}
			}
		},

		/**
		 * Filter the term and abbr selector lists
		 * Set initial values
		 * If there is already an abbr and the filtered list has only one or no element, hide the fieldset
		 */
		onSelectorRender: function (combo) {
			var store = combo.getStore();
			store.filterBy(function (record) {
				return !this.params.text || !this.params.title || this.params.text == record.get('term') || this.params.title == record.get('term') || this.params.title == record.get('abbr');
			}, this);
			// Make sure the combo list is filtered
			store.snapshot = store.data;
			var store = combo.getStore();
			// Initialize the term and abbr combos
			if (combo.getItemId() == 'termSelector') {
				if (this.params.title) {
					var index = store.findExact('term', this.params.title);
					if (index !== -1) {
						var record = store.getAt(index);
						combo.setValue(record.get('term'));
						this.onTermSelect(combo, record, index);
					}
				} else if (this.params.text) {
					var index = store.findExact('term', this.params.text);
					if (index !== -1) {
						var record = store.getAt(index);
						combo.setValue(record.get('term'));
						this.onTermSelect(combo, record, index);
					}
				}
			} else if (combo.getItemId() == 'abbrSelector' && this.params.text) {
				var index = store.findExact('abbr', this.params.text);
				if (index !== -1) {
					var record = store.getAt(index);
					combo.setValue(record.get('abbr'));
					this.onAbbrSelect(combo, record, index);
				}
			}
		},

		/**
		 * Handler when a term is selected
		 */
		onTermSelect: function (combo, record, index) {
			var fieldset = combo.findParentByType('fieldset');
			var tab = fieldset.findParentByType('container');
			var term = record.get('term');
			var abbr = record.get('abbr');
			var language = record.get('language');
			// Update the abbreviation selector
			var abbrSelector = tab.find('itemId', 'abbrSelector')[0];
			abbrSelector.setValue(abbr);
			// Update the language selector
			var languageSelector = tab.find('itemId', 'language');
			if (languageSelector.length > 0) {
				if (language) {
					languageSelector[0].setValue(language);
				} else {
					languageSelector[0].setValue('none');
				}
			}
			// Update the term to use
			var useTermField = tab.find('itemId', 'useTerm');
			if (useTermField.length) {
				useTermField[0].setValue(term);
			}
		},

		/**
		 * Handler when an abbreviation or acronym is selected
		 */
		onAbbrSelect: function (combo, record, index) {
			var fieldset = combo.findParentByType('fieldset');
			var tab = fieldset.findParentByType('container');
			var term = record.get('term');
			var language = record.get('language');
			// Update the term selector
			var termSelector = tab.find('itemId', 'termSelector')[0];
			termSelector.setValue(term);
			// Update the language selector
			var languageSelector = tab.find('itemId', 'language');
			if (languageSelector.length > 0) {
				if (language) {
					languageSelector[0].setValue(language);
				} else {
					languageSelector[0].setValue('none');
				}
			}
			// Update the term to use
			var useTermField = tab.find('itemId', 'useTerm');
			if (useTermField.length) {
				useTermField[0].setValue(term);
			}
		},

		/**
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

		/**
		 * Handler when the ok button is pressed
		 */
		okHandler: function (button, event) {
			this.restoreSelection();
			var tab = this.dialog.findByType('tabpanel')[0].getActiveTab();
			var type = tab.getItemId();
			var languageSelector = tab.find('itemId', 'language');
			var language = languageSelector && languageSelector.length > 0 ? languageSelector[0].getValue() : '';
			var termSelector = tab.find('itemId', 'termSelector');
			var term = termSelector && termSelector.length > 0 ? termSelector[0].getValue() : '';
			var abbrSelector = tab.find('itemId', 'abbrSelector');
			var useTermField = tab.find('itemId', 'useTerm');
			if (!this.params.abbr) {
				var abbr = this.editor.document.createElement(type);
				if (useTermField.length) {
					abbr.title = useTermField[0].getValue();
				} else {
					abbr.title = term;
				}
				if (term === abbr.title && abbrSelector && abbrSelector.length > 0) {
					abbr.innerHTML = abbrSelector[0].getValue();
				} else {
					abbr.innerHTML = this.params.text;
				}
				if (language) {
					this.getPluginInstance('Language').setLanguageAttributes(abbr, language);
				}
				this.editor.getSelection().insertNode(abbr);
				// Position the cursor just after the inserted abbreviation
				abbr = this.editor.getSelection().getParentElement();
				if (abbr.nextSibling) {
					this.editor.getSelection().selectNodeContents(abbr.nextSibling, true);
				} else {
					this.editor.getSelection().selectNodeContents(abbr.parentNode, false);
				}
			} else {
				var abbr = this.params.abbr;
				if (useTermField.length) {
					abbr.title = useTermField[0].getValue();
				} else {
					abbr.title = term;
				}
				if (language) {
					this.getPluginInstance('Language').setLanguageAttributes(abbr, language);
				}
				if (term === abbr.title && abbrSelector && abbrSelector.length > 0) {
					abbr.innerHTML = abbrSelector[0].getValue();
				}
			}
			this.close();
			event.stopEvent();
		},

		/**
		 * Handler when the delete button is pressed
		 */
		deleteHandler: function (button, event) {
			this.restoreSelection();
			var abbr = this.params.abbr;
			if (abbr) {
				this.editor.getDomNode().removeMarkup(abbr);
			}
			this.close();
			event.stopEvent();
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if ((mode === 'wysiwyg') && this.editor.isEditable()) {
				var el = this.getCurrentAbbrElement();
				var nodeName = typeof el === 'object' && el !== null ? el.nodeName.toLowerCase() : '';
				// Disable the button if the selection and not inside a abbr or acronym element
				button.setDisabled(
					(this.editor.getSelection().isEmpty() && nodeName !== 'abbr' && nodeName !== 'acronym')
					&& (this.pageTSConfiguration.noAbbr || !this.enabledFieldsets['definedAbbreviation'])
					&& (this.pageTSConfiguration.noAcronym || !this.enabledFieldsets['definedAcronym'])
				);
				button.setInactive(
					!(nodeName === 'abbr' && (this.enabledFieldsets['definedAbbreviation'] || this.enabledFieldsets['abbreviation']))
					&& !(nodeName === 'acronym' && (this.enabledFieldsets['definedAcronym'] || this.enabledFieldsets['acronym']))
				);
				button.setTooltip(this.localize((button.disabled || button.inactive) ? 'Insert abbreviation' : 'Edit abbreviation'));
				button.contextMenuTitle = '';
				if (this.dialog) {
					this.dialog.focus();
				}
			}
		}
	});

	return Abbreviation;

});
