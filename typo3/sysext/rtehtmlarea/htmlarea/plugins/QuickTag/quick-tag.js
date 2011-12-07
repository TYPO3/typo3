/***************************************************************
*  Copyright notice
*
*  (c) 2004 Cau guanabara <caugb@ibest.com.br>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Quick Tag Editor Plugin for TYPO3 htmlArea RTE
 */
/*
 * Define data model for tag attributes store
 */
Ext.define('HTMLArea.model.TagAttribute', {
	extend: 'Ext.data.Model',
	fields: [{
			name: 'text',
			type: 'string'
		},{
			name: 'value',
			type: 'string'
		},{
			name: 'tag',
			type: 'string'
	}]
});
/*
 * Define data model for attribute values store
 */
Ext.define('HTMLArea.model.AttributeValue', {
	extend: 'Ext.data.Model',
	fields: [{
			name: 'text',
			type: 'string'
		},{
			name: 'value',
			type: 'string'
		},{
			name: 'attribute',
			type: 'string'
	}]
});
/*
 * Define QuiskTag plugin
 */
Ext.define('HTMLArea.QuickTag', {
	extend: 'HTMLArea.Plugin',
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		this.pageTSConfiguration = this.editorConfiguration.buttons.inserttag;
		this.allowedTags = (this.pageTSConfiguration && this.pageTSConfiguration.tags) ? this.pageTSConfiguration.tags : null;
		this.denyTags = (this.pageTSConfiguration && this.pageTSConfiguration.denyTags) ? this.pageTSConfiguration.denyTags : null;
		this.allowedAttribs =  (this.pageTSConfiguration && this.pageTSConfiguration.allowedAttribs) ? this.pageTSConfiguration.allowedAttribs : null;
		this.quotes = new RegExp('^\w+\s*([a-zA-Z_0-9:;]+=\"[^\"]*\"\s*|[a-zA-Z_0-9:;]+=\'[^\']*\'\s*)*$');
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.2',
			developer	: 'Cau Guanabara & Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca',
			copyrightOwner	: 'Cau Guanabara & Stanislas Rolland',
			sponsor		: 'Independent production & SJBR',
			sponsorUrl	: 'http://www.sjbr.ca',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the button
		 */
		var buttonId = 'InsertTag';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize('Quick Tag Editor'),
			iconCls		: 'htmlarea-action-tag-insert',
			action		: 'onButtonPress',
			selection	: true,
			dialog		: true
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
			editable: true,
			forceSelection: true,
			helpIcon: true,
			listConfig: {
				cls: 'htmlarea-combo-list',
				getInnerTpl: function () {
					return '<div data-qtip="{value}" class="htmlarea-combo-list-item">{text}</div>';
				}
			},
			queryMode: 'local',
			triggerAction: 'all',
			typeAhead: true,
			valueField: 'value',
			xtype: 'combobox'
		}
	},
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function(editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.openDialogue(
			'Quick Tag Editor',
			{
				buttonId: buttonId
			},
			this.getWindowDimensions({ width: 570}, buttonId),
			this.buildItemsConfig(),
			this.setTag
		);
		this.insertedTag = this.dialog.down('component[itemId=insertedTag]');
		this.tagCombo = this.dialog.down('component[itemId=tags]');
		this.attributeCombo = this.dialog.down('component[itemId=attributes]');
		this.valueCombo = this.dialog.down('component[itemId=values]');
		this.colorCombo = this.dialog.down('component[itemId=colors]');
	},
	/*
	 * Build the window items config
	 */
	buildItemsConfig: function (element, buttonId) {
			// Create tag store
		this.tagStore = Ext.data.StoreManager.lookup(this.editorId + '-store-' + this.name + '-tag');
		if (!this.tagStore) {
			this.tagStore = Ext.create('Ext.data.ArrayStore', {
				model: 'HTMLArea.model.Default',
				storeId: this.editorId + '-store-' + this.name + '-tag'
			});
			this.tagStore.loadData(this.tags);
			if (this.denyTags) {
				var denyTags = new RegExp('^(' + this.denyTags.split(',').join('|').replace(/ /g, '') + ')$', 'i');
				this.tagStore.filterBy(function (record) {
					return !denyTags.test(record.get('value'));
				});
					// Make sure the combo is filtered
				this.tagStore.snapshot = this.tagStore.data;
			}
		}
			// Create attribute store
		this.attributeStore = Ext.data.StoreManager.lookup(this.editorId + '-store-' + this.name + '-attribute');
		if (!this.attributeStore) {
			this.attributeStore = Ext.create('Ext.data.ArrayStore', {
				model: 'HTMLArea.model.TagAttribute',
				storeId: this.editorId + '-store-' + this.name + '-attribute'
			});
			this.attributeStore.loadData(this.attributes);
		}
			// Create value store
		this.valueStore = Ext.data.StoreManager.lookup(this.editorId + '-store-' + this.name + '-value');
		if (!this.valueStore) {
			this.valueStore = Ext.create('Ext.data.ArrayStore', {
				model: 'HTMLArea.model.AttributeValue',
				storeId: this.editorId + '-store-' + this.name + '--value'
			});
			this.valueStore.loadData(this.values);
			this.captureClasses(this.valueStore);
		}
		var itemsConfig = [{
				xtype: 'textarea',
				itemId: 'tagopen',
				width: 400,
				itemId: 'insertedTag',
				fieldLabel: '<',
				labelSeparator: '',
				grow: true,
				listeners: {
					change: {
						fn: this.filterAttributes,
						scope: this
					},
					focus: {
						fn: this.filterAttributes,
						scope: this
					}
				}
			},{
				xtype: 'displayfield',
				text: '>'
			}, Ext.applyIf({
				itemId: 'tags',
				fieldLabel: this.localize('TAGs'),
				store: this.tagStore,
				listeners: {
					select: {
						fn: this.onTagSelect,
						scope: this
					}
				}
			}, this.configDefaults['combobox'])
			, Ext.applyIf({
				itemId: 'attributes',
				fieldLabel: this.localize('ATTRIBUTES'),
				store: this.attributeStore,
				hidden: true,
				listeners: {
					select: {
						fn: this.onAttributeSelect,
						scope: this
					}
				}
			}, this.configDefaults['combobox'])
			, Ext.applyIf({
				itemId: 'values',
				fieldLabel: this.localize('OPTIONS'),
				store: this.valueStore,
				hidden: true,
				listeners: {
					select: {
						fn: this.onValueSelect,
						scope: this
					}
				}
			}, this.configDefaults['combobox'])
			,{
				xtype: 'colorpalettefield',
				fieldLabel: this.localize('Colors'),
				itemId: 'colors',
				colors: this.editorConfiguration.disableColorPicker ? [] : null,
				colorsConfiguration: this.editorConfiguration.colors,
				hidden: true,
				listeners: {
					select: {
						fn: this.onColorSelect,
						scope: this
					}
				}
			}
		];
	 	return {
			xtype: 'fieldset',
			title: this.localize('Quick Tag Editor'),
			defaultType: 'textfield',
			labelWidth: 100,
			defaults: {
				helpIcon: true
			},
			items: itemsConfig
		};
	},
	/*
	 * Add a record for each class selector found in the stylesheets
	 */
	captureClasses: function (valueStore) {
		this.parseCssRule(this.editor.document.styleSheets, valueStore);
	},
	parseCssRule: function (rules, valueStore) {
		Ext.each(rules, function (rule) {
			if (rule.selectorText) {
				if (/^(\w*)\.(\w+)$/.test(rule.selectorText)) {
					valueStore.add({
						attribute: 'class',
						text: rule.selectorText,
						value: RegExp.$2 + '"'
					});
				}
			} else {
					// ImportRule (Mozilla)
				if (rule.styleSheet) {
					this.parseCssRule(rule.styleSheet.cssRules, valueStore);
				}
					// MediaRule (Mozilla)
				if (rule.cssRules) {
					this.parseCssRule(rule.cssRules, valueStore);
				}
					// IE imports
				if (rule.imports) {
					this.parseCssRule(rule.imports, valueStore);
				}
				if (rule.rules) {
					this.parseCssRule(rule.rules, valueStore);
				}
			}
		}, this);
	},
	/*
	 * Handler invoked when a tag is selected
	 * Update the attributes combo and the inserted tag field
	 */
	onTagSelect: function (tagCombo, tagRecords) {
		var tagRecord = tagRecords[0];
		var tag = tagRecord.get('value');
		this.filterAttributes();
		var attributeCombo = this.dialog.down('combobox[itemId=attributes]');
		attributeCombo.clearValue();
		attributeCombo.show();
		this.dialog.down('combobox[itemId=values]').hide();
		this.insertedTag.setValue(tag);
		this.insertedTag.focus(false, 50);
	},
	/*
	 * Filter out attributes not applicable to the tag, already present in the tag or not allowed
	 */
	filterAttributes: function () {
		var tag = this.dialog.down('combobox[itemId=tags]').getValue();
		var insertedTag = this.insertedTag.getValue();
		var allowedAttribs = '';
		if (this.allowedAttribs) {
			allowedAttribs = this.allowedAttribs.split(',').join('|').replace(/ /g, '');
		}
		if (this.allowedTags && this.allowedTags[tag] && this.allowedTags[tag].allowedAttribs) {
			allowedAttribs += allowedAttribs ? '|' : '';
			allowedAttribs += this.allowedTags[tag].allowedAttribs.split(',').join('|').replace(/ /g, '');
		}
		if (allowedAttribs) {
			var allowedAttribs = new RegExp('^(' + allowedAttribs + ')$');
		}
			// Refresh the store
		this.attributeStore.loadData(this.attributes);
			// Apply filter
		this.attributeStore.filterBy(function (attributeRecord) {
				// Filter out attributes already used in the tag, not applicable to tag or not allowed
			var testAttrib = new RegExp('(' + attributeRecord.get('value') + ')', 'ig');
			var tagValue = attributeRecord.get('tag');
			return (tagValue == 'all' || tagValue == tag) && !testAttrib.test(insertedTag) && (!allowedAttribs || allowedAttribs.test(attributeRecord.get('text')));
		});
			// Make sure the combo is filtered
		this.attributeStore.snapshot = this.attributeStore.data;
	},
	/*
	 * Filter out not applicable to the attribute or style values already present in the tag
	 * Filter out classes not applicable to the current tag
	 */
	filterValues: function (attribute) {
		var tag = this.dialog.down('combobox[itemId=tags]').getValue();
		var insertedTag = this.insertedTag.getValue();
		var expr = new RegExp('(^' + tag + '[\.])|(^[\.])', 'i');
			// Refresh the store
		this.valueStore.loadData(this.values);
		this.captureClasses(this.valueStore);
			// Apply filter
		this.valueStore.filterBy(function (valueRecord) {
			var value = valueRecord.get('value');
			if (attribute === 'style') {
				expr = new RegExp('(' + ((value.charAt(0) == '+' || value.charAt(0) == '-') ? '\\' : '') + value + ')', 'ig');
			}
			return valueRecord.get('attribute') == attribute && (attribute !== 'style' || !expr.test(insertedTag)) && (attribute !== 'class' || expr.test(valueRecord.get('text')));
		});
			// Make sure the combo is filtered
		this.valueStore.snapshot = this.valueStore.data;
		this.dialog.down('combobox[itemId=values]').setVisible(this.valueStore.getCount() ? true : false);
	},
	/*
	 * Handler invoked when an attribute is selected
	 * Update the values combo and the inserted tag field
	 */
	onAttributeSelect: function (attributeCombo, attributeRecords) {
		var attributeRecord = attributeRecords[0];
		var insertedTag = this.insertedTag.getValue();
		var attribute = attributeRecord.get('text');
		var valueCombo = this.dialog.down('combobox[itemId=values]');
		valueCombo.clearValue();
		if (/color/.test(attribute)) {
			valueCombo.hide();
			this.dialog.down('colorpalettefield[itemId=colors]').show();
		} else {
			this.filterValues(attribute);
		}
		this.insertedTag.setValue(insertedTag + ((/\"/.test(insertedTag) && (!/\"$/.test(insertedTag) || /=\"$/.test(insertedTag))) ? '" ' : ' ') + attributeRecord.get('value'));
		this.insertedTag.focus(false, 50);
	},
	/*
	 * Handler invoked when a value is selected
	 * Update the inserted tag field
	 */
	onValueSelect: function (combo, records) {
		var record = records[0];
		var attributeCombo = this.dialog.down('combobox[itemId=attributes]');
		var style = attributeCombo.getValue() === 'style="';
		this.insertedTag.setValue(this.insertedTag.getValue() + (style && !/="$/.test(this.insertedTag.getValue()) ? '; ' : '') + combo.getValue());
		this.insertedTag.focus(false, 50);
		combo.clearValue();
		if (style) {
			if (/color/.test(record.get('text'))) {
				this.dialog.down('colorpalettefield[itemId=colors]').show();
			}
		} else {
			combo.hide();
			attributeCombo.clearValue();
		}
	},
	/*
	 * Handler invoked when a color is selected
	 * Update the inserted tag field
	 */
	onColorSelect: function (combo, records) {
		var record = records[0];
		var attributeCombo = this.dialog.down('combobox[itemId=attributes]');
		var style = attributeCombo.getValue() === 'style="';
		this.insertedTag.setValue(this.insertedTag.getValue() + '#' + combo.getValue() + (style ? '' : '"'));
		this.insertedTag.focus(false, 50);
		combo.setValue('');
		combo.hide();
		if (!style) {
			attributeCombo.clearValue();
		}
	},
	/*
	 * Handler invoked when a OK button is pressed
	 */
	setTag: function (button, event) {
		this.restoreSelection();
		var insertedTag = this.insertedTag.getValue();
		var currentTag = this.dialog.down('combobox[itemId=tags]').getValue();
		if (!insertedTag) {
			TYPO3.Dialog.InformationDialog({
				title: this.getButton('InsertTag').tooltip.text,
				msg: this.localize('Enter the TAG you want to insert'),
				fn: function () { this.insertedTag.focus(); },
				scope: this
			});
			event.stopEvent();
			return false;
		}
		if (this.quotes.test(insertedTag)) {
			if (this.quotes.test(insertedTag + '"')) {
				TYPO3.Dialog.InformationDialog({
					title: this.getButton('InsertTag').tooltip.text,
					msg: this.localize('There are some unclosed quote'),
					fn: function () { this.insertedTag.focus(); this.insertedTag.select(); },
					scope: this
				});
				event.stopEvent();
				return false;
			} else {
				this.insertedTag.setValue(insertedTag + '"');
			}
		}
		insertedTag = insertedTag.replace(/(<|>)/g, '');
		var tagOpen = '<' + insertedTag + '>';
		var tagClose = tagOpen.replace(/^<(\w+) ?.*>/, '</$1>');
		var subTags = this.subTags[currentTag];
		if (subTags) {
			tagOpen = tagOpen + this.subTags.open;
			tagClose = this.subTags.close + tagClose;
		}
		this.editor.surroundHTML(tagOpen, tagClose);
		this.close();
		event.stopEvent();
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		title: the window title
	 * @param	object		arguments: some arguments for the handler
	 * @param	integer		dimensions: the opening dimensions of the window
	 * @param	object		items: the configuration of the window items
	 * @param	function	handler: handler when the OK button if clicked
	 *
	 * @return	void
	 */
	openDialogue: function (title, arguments, dimensions, items, handler) {
		if (this.dialog) {
			this.dialog.close();
		}
		this.dialog = Ext.create('Ext.window.Window', {
			title: this.localize(title),
			arguments: arguments,
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			layout: 'anchor',
			resizable: true,
			iconCls: this.getButton(arguments.buttonId).iconCls,
			listeners: {
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: {
				xtype: 'container',
				layout: 'form',
				defaults: {
					labelWidth: 150
				},
				items: items
			},
			buttons: [
				this.buildButtonConfig('OK', handler),
				this.buildButtonConfig('Cancel', this.onCancel)
			]
		});
		this.show();
	},
	tags: [
		{ text: 'a', value: 'a'},
		{ text: 'abbr', value: 'abbr'},
		{ text: 'acronym', value: 'acronym'},
		{ text: 'address', value: 'address'},
		{ text: 'b', value: 'b'},
		{ text: 'big', value: 'big'},
		{ text: 'blockquote', value: 'blockquote'},
		{ text: 'cite', value: 'cite'},
		{ text: 'code', value: 'code'},
		{ text: 'div', value: 'div'},
		{ text: 'em', value: 'em'},
		{ text: 'fieldset', value: 'fieldset'},
		{ text: 'font', value: 'font'},
		{ text: 'h1', value: 'h1'},
		{ text: 'h2', value: 'h2'},
		{ text: 'h3', value: 'h3'},
		{ text: 'h4', value: 'h4'},
		{ text: 'h5', value: 'h5'},
		{ text: 'h6', value: 'h6'},
		{ text: 'i', value: 'i'},
		{ text: 'legend', value: 'legend'},
		{ text: 'li', value: 'li'},
		{ text: 'ol', value: 'ol'},
		{ text: 'ul', value: 'ul'},
		{ text: 'p', value: 'p'},
		{ text: 'pre', value: 'pre'},
		{ text: 'q', value: 'q'},
		{ text: 'small', value: 'small'},
		{ text: 'span', value: 'span'},
		{ text: 'strike', value: 'strike'},
		{ text: 'strong', value: 'strong'},
		{ text: 'sub', value: 'sub'},
		{ text: 'sup', value: 'sup'},
		{ text: 'table', value: 'table'},
		{ text: 'tt', value: 'tt'},
		{ text: 'u', value: 'u'}
	],
	attributes: [
		{ tag: 'all', text: 'class', value: 'class="'},
		{ tag: 'all', text: 'dir', value: 'dir="'},
		{ tag: 'all', text: 'id', value: 'id="'},
		{ tag: 'all', text: 'lang', value: 'lang="'},
		{ tag: 'all', text: 'onFocus', value: 'onFocus="'},
		{ tag: 'all', text: 'onBlur', value: 'onBlur="'},
		{ tag: 'all', text: 'onClick', value: 'onClick="'},
		{ tag: 'all', text: 'onDblClick', value: 'onDblClick="'},
		{ tag: 'all', text: 'onMouseDown', value: 'onMouseDown="'},
		{ tag: 'all', text: 'onMouseUp', value: 'onMouseUp="'},
		{ tag: 'all', text: 'onMouseOver', value: 'onMouseOver="'},
		{ tag: 'all', text: 'onMouseMove', value: 'onMouseMove="'},
		{ tag: 'all', text: 'onMouseOut', value: 'onMouseOut="'},
		{ tag: 'all', text: 'onKeyPress', value: 'onKeyPress="'},
		{ tag: 'all', text: 'onKeyDown', value: 'onKeyDown="'},
		{ tag: 'all', text: 'onKeyUp', value: 'onKeyUp="'},
		{ tag: 'all', text: 'style', value: 'style="'},
		{ tag: 'all', text: 'title', value: 'title="'},
		{ tag: 'all', text: 'xml:lang', value: 'xml:lang="'},
		{ tag: 'a', text: 'href', value: 'href="'},
		{ tag: 'a', text: 'name', value: 'name="'},
		{ tag: 'a', text: 'target', value: 'target="'},
		{ tag: 'font', text: 'face', value: 'face="'},
		{ tag: 'font', text: 'size', value: 'size="'},
		{ tag: 'font', text: 'color', value: 'color="'},
		{ tag: 'div', text: 'align', value: 'align="'},
		{ tag: 'h1', text: 'align', value: 'align="'},
		{ tag: 'h2', text: 'align', value: 'align="'},
		{ tag: 'h3', text: 'align', value: 'align="'},
		{ tag: 'h4', text: 'align', value: 'align="'},
		{ tag: 'h5', text: 'align', value: 'align="'},
		{ tag: 'h6', text: 'align', value: 'align="'},
		{ tag: 'p', text: 'align', value: 'align="'},
		{ tag: 'table', text: 'align', value: 'align="'},
		{ tag: 'table', text: 'width', value: 'width="'},
		{ tag: 'table', text: 'height', value: 'height="'},
		{ tag: 'table', text: 'cellpadding', value: 'cellpadding="'},
		{ tag: 'table', text: 'cellspacing', value: 'cellspacing="'},
		{ tag: 'table', text: 'background', value: 'background="'},
		{ tag: 'table', text: 'bgcolor', value: 'bgcolor="'},
		{ tag: 'table', text: 'border', value: 'border="'},
		{ tag: 'table', text: 'bordercolor', value: 'bordercolor="'}
	],
	values: [
		{ attribute: 'href', text: 'http://', value: 'http://'},
		{ attribute: 'href', text: 'https://', value: 'https://'},
		{ attribute: 'href', text: 'ftp://', value: 'ftp://'},
		{ attribute: 'href', text: 'mailto:', value: 'mailto:'},
		{ attribute: 'href', text: '#', value: '#"'},
		{ attribute: 'target', text: '_top', value: '_top"'},
		{ attribute: 'target', text: '_self', value: '_self"'},
		{ attribute: 'target', text: '_parent', value: '_parent"'},
		{ attribute: 'target', text: '_blank', value: '_blank"'},
		{ attribute: 'face', text: 'Verdana', value: 'Verdana"'},
		{ attribute: 'face', text: 'Arial', value: 'Arial"'},
		{ attribute: 'face', text: 'Tahoma', value: 'Tahoma"'},
		{ attribute: 'face', text: 'Courier New', value: 'Courier New"'},
		{ attribute: 'face', text: 'Times New Roman', value: 'Times New Roman"'},
		{ attribute: 'size', text: '1', value: '1"'},
		{ attribute: 'size', text: '2', value: '2"'},
		{ attribute: 'size', text: '3', value: '3"'},
		{ attribute: 'size', text: '4', value: '4"'},
		{ attribute: 'size', text: '5', value: '5"'},
		{ attribute: 'size', text: '6', value: '6"'},
		{ attribute: 'size', text: '+1', value: '+1"'},
		{ attribute: 'size', text: '+2', value: '+2"'},
		{ attribute: 'size', text: '+3', value: '+3"'},
		{ attribute: 'size', text: '+4', value: '+4"'},
		{ attribute: 'size', text: '+5', value: '+5"'},
		{ attribute: 'size', text: '+6', value: '+6"'},
		{ attribute: 'size', text: '-1', value: '-1"'},
		{ attribute: 'size', text: '-2', value: '-2"'},
		{ attribute: 'size', text: '-3', value: '-3"'},
		{ attribute: 'size', text: '-4', value: '-4"'},
		{ attribute: 'size', text: '-5', value: '-5"'},
		{ attribute: 'size', text: '-6', value: '-6"'},
		{ attribute: 'align', text: 'center', value: 'center"'},
		{ attribute: 'align', text: 'left', value: 'left"'},
		{ attribute: 'align', text: 'right', value: 'right"'},
		{ attribute: 'align', text: 'justify', value: 'justify"'},
		{ attribute: 'dir', text: 'rtl', value: 'rtl"'},
		{ attribute: 'dir', text: 'ltr', value: 'ltr"'},
		{ attribute: 'lang', text: 'Afrikaans ', value: 'af"'},
		{ attribute: 'lang', text: 'Albanian ', value: 'sq"'},
		{ attribute: 'lang', text: 'Arabic ', value: 'ar"'},
		{ attribute: 'lang', text: 'Basque ', value: 'eu"'},
		{ attribute: 'lang', text: 'Breton ', value: 'br"'},
		{ attribute: 'lang', text: 'Bulgarian ', value: 'bg"'},
		{ attribute: 'lang', text: 'Belarusian ', value: 'be"'},
		{ attribute: 'lang', text: 'Catalan ', value: 'ca"'},
		{ attribute: 'lang', text: 'Chinese ', value: 'zh"'},
		{ attribute: 'lang', text: 'Croatian ', value: 'hr"'},
		{ attribute: 'lang', text: 'Czech ', value: 'cs"'},
		{ attribute: 'lang', text: 'Danish ', value: 'da"'},
		{ attribute: 'lang', text: 'Dutch ', value: 'nl"'},
		{ attribute: 'lang', text: 'English ', value: 'en"'},
		{ attribute: 'lang', text: 'Estonian ', value: 'et"'},
		{ attribute: 'lang', text: 'Faeroese ', value: 'fo"'},
		{ attribute: 'lang', text: 'Farsi ', value: 'fa"'},
		{ attribute: 'lang', text: 'Finnish ', value: 'fi"'},
		{ attribute: 'lang', text: 'French ', value: 'fr"'},
		{ attribute: 'lang', text: 'Gaelic ', value: 'gd"'},
		{ attribute: 'lang', text: 'German ', value: 'de"'},
		{ attribute: 'lang', text: 'Greek ', value: 'el"'},
		{ attribute: 'lang', text: 'Hebrew ', value: 'he"'},
		{ attribute: 'lang', text: 'Hindi ', value: 'hi"'},
		{ attribute: 'lang', text: 'Hungarian ', value: 'hu"'},
		{ attribute: 'lang', text: 'Icelandic ', value: 'is"'},
		{ attribute: 'lang', text: 'Indonesian ', value:  'id"'},
		{ attribute: 'lang', text: 'Italian ', value:  'it"'},
		{ attribute: 'lang', text: 'Japanese ', value:  'ja"'},
		{ attribute: 'lang', text: 'Korean ', value:  'ko"'},
		{ attribute: 'lang', text: 'Latvian ', value:  'lv"'},
		{ attribute: 'lang', text: 'Lithuanian ', value:  'lt"'},
		{ attribute: 'lang', text: 'Macedonian ', value:  'mk"'},
		{ attribute: 'lang', text: 'Malaysian ', value:  'ms"'},
		{ attribute: 'lang', text: 'Maltese ', value:  'mt"'},
		{ attribute: 'lang', text: 'Norwegian ', value:  'no"'},
		{ attribute: 'lang', text: 'Polish ', value:  'pl"'},
		{ attribute: 'lang', text: 'Portuguese ', value:  'pt"'},
		{ attribute: 'lang', text: 'Rhaeto-Romanic ', value:  'rm"'},
		{ attribute: 'lang', text: 'Romanian ', value:  'ro"'},
		{ attribute: 'lang', text: 'Russian ', value:  'ru"'},
		{ attribute: 'lang', text: 'Sami ', value:  'sz"'},
		{ attribute: 'lang', text: 'Serbian ', value:  'sr"'},
		{ attribute: 'lang', text: 'Setswana ', value:  'tn"'},
		{ attribute: 'lang', text: 'Slovak ', value:  'sk"'},
		{ attribute: 'lang', text: 'Slovenian ', value: 'sl"'},
		{ attribute: 'lang', text: 'Spanish ', value: 'es"'},
		{ attribute: 'lang', text: 'Sutu ', value: 'sx"'},
		{ attribute: 'lang', text: 'Swedish ', value: 'sv"'},
		{ attribute: 'lang', text: 'Thai ', value: 'th"'},
		{ attribute: 'lang', text: 'Tsonga ', value: 'ts"'},
		{ attribute: 'lang', text: 'Turkish ', value: 'tr"'},
		{ attribute: 'lang', text: 'Ukrainian ', value: 'uk"'},
		{ attribute: 'lang', text: 'Urdu ', value: 'ur"'},
		{ attribute: 'lang', text: 'Vietnamese ', value: 'vi"'},
		{ attribute: 'lang', text: 'Xhosa ', value: 'xh"'},
		{ attribute: 'lang', text: 'Yiddish ', value: 'yi"'},
		{ attribute: 'lang', text: 'Zulu', value: 'zu"'},
		{ attribute: 'style', text: 'azimuth', value: 'azimuth: '},
		{ attribute: 'style', text: 'background', value: 'background: '},
		{ attribute: 'style', text: 'background-attachment', value: 'background-attachment: '},
		{ attribute: 'style', text: 'background-color', value: 'background-color: '},
		{ attribute: 'style', text: 'background-image', value: 'background-image: '},
		{ attribute: 'style', text: 'background-position', value: 'background-position: '},
		{ attribute: 'style', text: 'background-repeat', value: 'background-repeat: '},
		{ attribute: 'style', text: 'border', value: 'border: '},
		{ attribute: 'style', text: 'border-bottom', value: 'border-bottom: '},
		{ attribute: 'style', text: 'border-left', value: 'border-left: '},
		{ attribute: 'style', text: 'border-right', value: 'border-right: '},
		{ attribute: 'style', text: 'border-top', value: 'border-top: '},
		{ attribute: 'style', text: 'border-bottom-color', value: 'border-bottom-color: '},
		{ attribute: 'style', text: 'border-left-color', value: 'border-left-color: '},
		{ attribute: 'style', text: 'border-right-color', value: 'border-right-color: '},
		{ attribute: 'style', text: 'border-top-color', value: 'border-top-color: '},
		{ attribute: 'style', text: 'border-bottom-style', value: 'border-bottom-style: '},
		{ attribute: 'style', text: 'border-left-style', value: 'border-left-style: '},
		{ attribute: 'style', text: 'border-right-style', value: 'border-right-style: '},
		{ attribute: 'style', text: 'border-top-style', value: 'border-top-style: '},
		{ attribute: 'style', text: 'border-bottom-width', value: 'border-bottom-width: '},
		{ attribute: 'style', text: 'border-left-width', value: 'border-left-width: '},
		{ attribute: 'style', text: 'border-right-width', value: 'border-right-width: '},
		{ attribute: 'style', text: 'border-top-width', value: 'border-top-width: '},
		{ attribute: 'style', text: 'border-collapse', value: 'border-collapse: '},
		{ attribute: 'style', text: 'border-color', value: 'border-color: '},
		{ attribute: 'style', text: 'border-style', value: 'border-style: '},
		{ attribute: 'style', text: 'border-width', value: 'border-width: '},
		{ attribute: 'style', text: 'bottom', value: 'bottom: '},
		{ attribute: 'style', text: 'caption-side', value: 'caption-side: '},
		{ attribute: 'style', text: 'cell-spacing', value: 'cell-spacing: '},
		{ attribute: 'style', text: 'clear', value: 'clear: '},
		{ attribute: 'style', text: 'clip', value: 'clip: '},
		{ attribute: 'style', text: 'color', value: 'color: '},
		{ attribute: 'style', text: 'column-span', value: 'column-span: '},
		{ attribute: 'style', text: 'content', value: 'content: '},
		{ attribute: 'style', text: 'cue', value: 'cue: '},
		{ attribute: 'style', text: 'cue-after', value: 'cue-after: '},
		{ attribute: 'style', text: 'cue-before', value: 'cue-before: '},
		{ attribute: 'style', text: 'cursor', value: 'cursor: '},
		{ attribute: 'style', text: 'direction', value: 'direction: '},
		{ attribute: 'style', text: 'display', value: 'display: '},
		{ attribute: 'style', text: 'elevation', value: 'elevation: '},
		{ attribute: 'style', text: 'filter', value: 'filter: '},
		{ attribute: 'style', text: 'float', value: 'float: '},
		{ attribute: 'style', text: 'font-family', value: 'font-family: '},
		{ attribute: 'style', text: 'font-size', value: 'font-size: '},
		{ attribute: 'style', text: 'font-size-adjust', value: 'font-size-adjust: '},
		{ attribute: 'style', text: 'font-style', value: 'font-style: '},
		{ attribute: 'style', text: 'font-variant', value: 'font-variant: '},
		{ attribute: 'style', text: 'font-weight', value: 'font-weight: '},
		{ attribute: 'style', text: 'height', value: 'height: '},
		{ attribute: 'style', text: '!important', value: '!important: '},
		{ attribute: 'style', text: 'left', value: 'left: '},
		{ attribute: 'style', text: 'letter-spacing', value: 'letter-spacing: '},
		{ attribute: 'style', text: 'line-height', value: 'line-height: '},
		{ attribute: 'style', text: 'list-style', value: 'list-style: '},
		{ attribute: 'style', text: 'list-style-image', value: 'list-style-image: '},
		{ attribute: 'style', text: 'list-style-position', value: 'list-style-position: '},
		{ attribute: 'style', text: 'list-style-type', value: 'list-style-type: '},
		{ attribute: 'style', text: 'margin', value: 'margin: '},
		{ attribute: 'style', text: 'margin-bottom', value: 'margin-bottom: '},
		{ attribute: 'style', text: 'margin-left', value: 'margin-left: '},
		{ attribute: 'style', text: 'margin-right', value: 'margin-right: '},
		{ attribute: 'style', text: 'margin-top', value: 'margin-top: '},
		{ attribute: 'style', text: 'marks', value: 'marks: '},
		{ attribute: 'style', text: 'max-height', value: 'max-height: '},
		{ attribute: 'style', text: 'min-height', value: 'min-height: '},
		{ attribute: 'style', text: 'max-width', value: 'max-width: '},
		{ attribute: 'style', text: 'min-width', value: 'min-width: '},
		{ attribute: 'style', text: 'orphans', value: 'orphans: '},
		{ attribute: 'style', text: 'overflow', value: 'overflow: '},
		{ attribute: 'style', text: 'padding', value: 'padding: '},
		{ attribute: 'style', text: 'padding-bottom', value: 'padding-bottom: '},
		{ attribute: 'style', text: 'padding-left', value: 'padding-left: '},
		{ attribute: 'style', text: 'padding-right', value: 'padding-right: '},
		{ attribute: 'style', text: 'padding-top', value: 'padding-top: '},
		{ attribute: 'style', text: 'page-break-after', value: 'page-break-after: '},
		{ attribute: 'style', text: 'page-break-before', value: 'page-break-before: '},
		{ attribute: 'style', text: 'pause', value: 'pause: '},
		{ attribute: 'style', text: 'pause-after', value: 'pause-after: '},
		{ attribute: 'style', text: 'pause-before', value: 'pause-before: '},
		{ attribute: 'style', text: 'pitch', value: 'pitch: '},
		{ attribute: 'style', text: 'pitch-range', value: 'pitch-range: '},
		{ attribute: 'style', text: 'play-during', value: 'play-during: '},
		{ attribute: 'style', text: 'position', value: 'position: '},
		{ attribute: 'style', text: 'richness', value: 'richness: '},
		{ attribute: 'style', text: 'right', value: 'right: '},
		{ attribute: 'style', text: 'row-span', value: 'row-span: '},
		{ attribute: 'style', text: 'size', value: 'size: '},
		{ attribute: 'style', text: 'speak', value: 'speak: '},
		{ attribute: 'style', text: 'speak-date', value: 'speak-date: '},
		{ attribute: 'style', text: 'speak-header', value: 'speak-header: '},
		{ attribute: 'style', text: 'speak-numeral', value: 'speak-numeral: '},
		{ attribute: 'style', text: 'speak-punctuation', value: 'speak-punctuation: '},
		{ attribute: 'style', text: 'speak-time', value: 'speak-time: '},
		{ attribute: 'style', text: 'speech-rate', value: 'speech-rate: '},
		{ attribute: 'style', text: 'stress', value: 'stress: '},
		{ attribute: 'style', text: 'table-layout', value: 'table-layout: '},
		{ attribute: 'style', text: 'text-align', value: 'text-align: '},
		{ attribute: 'style', text: 'text-decoration', value: 'text-decoration: '},
		{ attribute: 'style', text: 'text-indent', value: 'text-indent: '},
		{ attribute: 'style', text: 'text-shadow', value: 'text-shadow: '},
		{ attribute: 'style', text: 'text-transform', value: 'text-transform: '},
		{ attribute: 'style', text: 'top', value: 'top: '},
		{ attribute: 'style', text: 'vertical-align', value: 'vertical-align: '},
		{ attribute: 'style', text: 'visibility', value: 'visibility: '},
		{ attribute: 'style', text: 'voice-family', value: 'voice-family: '},
		{ attribute: 'style', text: 'volume', value: 'volume: '},
		{ attribute: 'style', text: 'white-space', value: 'white-space: '},
		{ attribute: 'style', text: 'widows', value: 'widows: '},
		{ attribute: 'style', text: 'width', value: 'width: '},
		{ attribute: 'style', text: 'word-spacing', value: 'word-spacing: '},
		{ attribute: 'style', text: 'z-index', value: 'z-index: '}
	],
	subTags: {
		'table': {
			'open': '<tbody><tr><td>',
			'close': '</td></tr></tbody>'
		}
	}
});
