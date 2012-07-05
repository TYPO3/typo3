/***************************************************************
*  Copyright notice
*
*  (c) 2002 interactivetools.com, inc. Authored by Mihai Bazon, sponsored by http://www.bloki.com.
*  (c) 2005 Xinha, http://xinha.gogo.co.nz/ for the original toggle borders function.
*  (c) 2004-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Table Operations Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.TableOperations = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {

		this.classesUrl = this.editorConfiguration.classesUrl;
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		this.disableEnterParagraphs = this.buttonsConfiguration.table ? this.buttonsConfiguration.table.disableEnterParagraphs : false;
		this.floatLeft = "float-left";
		this.floatRight = "float-right";
		this.floatDefault = "not set";
		this.useHeaderClass = "thead";
		if (this.buttonsConfiguration.table && this.buttonsConfiguration.table.properties) {
			if (this.buttonsConfiguration.table.properties["float"]) {
				var floatConfiguration = this.buttonsConfiguration.table.properties["float"];
				this.floatLeft = (floatConfiguration.left && floatConfiguration.left.useClass) ? floatConfiguration.left.useClass : "float-left";
				this.floatRight = (floatConfiguration.right && floatConfiguration.right.useClass) ? floatConfiguration.right.useClass : "float-right";
				this.floatDefault = (floatConfiguration.defaultValue) ?  floatConfiguration.defaultValue : "not set";
			}
			if (this.buttonsConfiguration.table.properties.headers && this.buttonsConfiguration.table.properties.headers.both
					&& this.buttonsConfiguration.table.properties.headers.both.useHeaderClass) {
				this.useHeaderClass = this.buttonsConfiguration.table.properties.headers.both.useHeaderClass;
			}
			if (this.buttonsConfiguration.table.properties.tableClass) {
				this.defaultClass = this.buttonsConfiguration.table.properties.tableClass.defaultValue;
			}
		}
		if (this.buttonsConfiguration.blockstyle) {
			this.tags = this.editorConfiguration.buttons.blockstyle.tags;
		}
		this.tableParts = ["tfoot", "thead", "tbody"];
		this.convertAlignment = { "not set" : "none", "left" : "JustifyLeft", "center" : "JustifyCenter", "right" : "JustifyRight", "justify" : "JustifyFull" };
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '5.3',
			developer	: 'Mihai Bazon & Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Mihai Bazon & Stanislas Rolland',
			sponsor		: this.localize('Technische Universitat Ilmenau') + ' & Zapatec Inc.',
			sponsorUrl	: 'http://www.tu-ilmenau.de/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the buttons
		 */
		var hideToggleBorders = this.editorConfiguration.hideTableOperationsInToolbar && !(this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.keepInToolbar);
		var buttonList = this.buttonList, buttonId;
		for (var i = 0, n = buttonList.length; i < n; ++i) {
			var button = buttonList[i];
			buttonId = (button[0] === 'InsertTable') ? button[0] : ('TO-' + button[0]);
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize((buttonId === 'InsertTable') ? 'Insert Table' : buttonId),
				iconCls		: 'htmlarea-action-' + button[4],
				action		: 'onButtonPress',
				hotKey		: (this.buttonsConfiguration[button[2]] ? this.buttonsConfiguration[button[2]].hotKey : null),
				context		: button[1],
				hide		: ((buttonId == 'TO-toggle-borders') ? hideToggleBorders : ((button[0] === 'InsertTable') ? false : this.editorConfiguration.hideTableOperationsInToolbar)),
				dialog		: button[3]
			};
			this.registerButton(buttonConfiguration);
		}
		return true;
	 },
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: [
		['InsertTable',		null,				'table', true, 'table-insert'],
		['toggle-borders',	null, 				'toggleborders', false, 'table-show-borders'],
		['table-prop',		'table',			'tableproperties', true, 'table-edit-properties'],
		['table-restyle',	'table',			'tablerestyle', false, 'table-restyle'],
		['row-prop',		'tr',				'rowproperties', true, 'row-edit-properties'],
		['row-insert-above',	'tr',				'rowinsertabove', false, 'row-insert-above'],
		['row-insert-under',	'tr',				'rowinsertunder', false, 'row-insert-under'],
		['row-delete',		'tr',				'rowdelete', false, 'row-delete'],
		['row-split',		'td,th[rowSpan!=1]',		'rowsplit', false, 'row-split'],
		['col-prop',		'td,th',			'columnproperties', true, 'column-edit-properties'],
		['col-insert-before',	'td,th',			'columninsertbefore', false, 'column-insert-before'],
		['col-insert-after',	'td,th',			'columninsertafter', false, 'column-insert-after'],
		['col-delete',		'td,th',			'columndelete', false, 'column-delete'],
		['col-split',		'td,th[colSpan!=1]',		'columnsplit', false, 'column-split'],
		['cell-prop',		'td,th',			'cellproperties', true, 'cell-edit-properties'],
		['cell-insert-before',	'td,th',			'cellinsertbefore', false, 'cell-insert-before'],
		['cell-insert-after',	'td,th',			'cellinsertafter', false, 'cell-insert-after'],
		['cell-delete',		'td,th',			'celldelete', false, 'cell-delete'],
		['cell-merge',		Ext.isGecko? 'tr' : 'td,th',	'cellmerge', false, 'cell-merge'],
		['cell-split',		'td,th[colSpan!=1,rowSpan!=1]',	'cellsplit', false, 'cell-split']
	],
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
	 * Get the integer value of a string or '' if the string is not a number
	 *
	 * @param	string		string: the input value
	 *
	 * @return	mixed		a number or ''
	 */
	getLength: function (string) {
		var length = parseInt(string);
		if (isNaN(length)) {
			length = '';
		}
		return length;
	},
	/*
	 * Open properties dialogue
	 *
	 * @param	string		type: 'cell', 'column', 'row' or 'table'
	 * @param	string		buttonId: the buttonId of the button that was pressed
	 *
	 * @return 	void
	 */
	openPropertiesDialogue: function (type, buttonId) {
			// Retrieve the element being edited and set configuration according to type
		switch (type) {
			case 'cell':
			case 'column':
				var element = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
				this.properties = (this.buttonsConfiguration.cellproperties && this.buttonsConfiguration.cellproperties.properties) ? this.buttonsConfiguration.cellproperties.properties : {};
				var title = (type == 'column') ? 'Column Properties' : 'Cell Properties';
				break;
			case 'row':
				var element = this.editor.getSelection().getFirstAncestorOfType('tr');
				this.properties = (this.buttonsConfiguration.rowproperties && this.buttonsConfiguration.rowproperties.properties) ? this.buttonsConfiguration.rowproperties.properties : {};
				var title = 'Row Properties';
				break;
			case 'table':
				var insert = (buttonId === 'InsertTable');
				var element = insert ? null : this.editor.getSelection().getFirstAncestorOfType('table');
				this.properties = (this.buttonsConfiguration.table && this.buttonsConfiguration.table.properties) ? this.buttonsConfiguration.table.properties : {};
				var title = insert ? 'Insert Table' : 'Table Properties';
				break;
		}
		var propertySet = element ? type + 'properties' : 'table';
		this.removedFieldsets = (this.buttonsConfiguration[propertySet] && this.buttonsConfiguration[propertySet].removeFieldsets) ? this.buttonsConfiguration[propertySet].removeFieldsets : '';
		this.removedProperties = this.properties.removed ? this.properties.removed : '';
			// Open the dialogue window
		this.openDialogue(
			title,
			{
				element: element,
				cell: type == 'cell',
				column: type == 'column',
				buttonId: buttonId
			},
			type == 'table' ? this.getWindowDimensions({ width: 600}, buttonId) : this.getWindowDimensions({ width: 600}, buttonId),
			this.buildTabItemsConfig(element, type, buttonId),
			type == 'table' ? this.tablePropertiesUpdate : this.rowCellPropertiesUpdate
		);
	},
	/*
	 * Build the dialogue tab items config
	 *
	 * @param	object		element: the element being edited, if any
	 * @param	string		type: 'cell', 'column', 'row' or 'table'
	 * @param	string		buttonId: the buttonId of the button that was pressed
	 *
	 * @return	object		the tab items configuration
	 */
	buildTabItemsConfig: function (element, type, buttonId) {
		var tabItems = [];
		var generalTabItems = [];
		switch (type) {
			case 'table':
				if (this.removedFieldsets.indexOf('description') === -1) {
					this.addConfigElement(this.buildDescriptionFieldsetConfig(element), generalTabItems);
				}
				if (Ext.isEmpty(element) || this.removedProperties.indexOf('headers') === -1) {
					this.addConfigElement(this.buildSizeAndHeadersFieldsetConfig(element), generalTabItems);
				}
				break;
			case 'column':
				if (this.removedFieldsets.indexOf('columntype') == -1) {
					this.addConfigElement(this.buildCellTypeFieldsetConfig(element, true), generalTabItems);
				}
				break;
			case 'cell':
				if (this.removedFieldsets.indexOf('celltype') == -1) {
					this.addConfigElement(this.buildCellTypeFieldsetConfig(element, false), generalTabItems);
				}
				break;
			case 'row':
				if (this.removedFieldsets.indexOf('rowgroup') == -1) {
					this.addConfigElement(this.buildRowGroupFieldsetConfig(element), generalTabItems);
				}
				break;
		}
		if (this.removedFieldsets.indexOf('style') == -1 && this.getPluginInstance('BlockStyle')) {
			this.addConfigElement(this.buildStylingFieldsetConfig(element, buttonId), generalTabItems);
		}
		if (!Ext.isEmpty(generalTabItems)) {
			tabItems.push({
				title: this.localize('General'),
				items: generalTabItems
			});
		}
		var layoutTabItems = [];
		if (type === 'table' && this.removedFieldsets.indexOf('spacing') === -1) {
			this.addConfigElement(this.buildSpacingFieldsetConfig(element), layoutTabItems);
		}
		if (this.removedFieldsets.indexOf('layout') == -1) {
			this.addConfigElement(this.buildLayoutFieldsetConfig(element), layoutTabItems);
		}
		if (!Ext.isEmpty(layoutTabItems)) {
			tabItems.push({
				title: this.localize('Layout'),
				items: layoutTabItems
			});
		}
		var languageTabItems = [];
		if (this.removedFieldsets.indexOf('language') === -1 && (this.removedProperties.indexOf('language') === -1 || this.removedProperties.indexOf('direction') === -1) && (this.getButton('Language') || this.getButton('LeftToRight') || this.getButton('RightToLeft'))) {
			this.addConfigElement(this.buildLanguageFieldsetConfig(element), languageTabItems);
		}
		if (!Ext.isEmpty(languageTabItems)) {
			tabItems.push({
				title: this.localize('Language'),
				items: languageTabItems
			});
		}
		var alignmentAndBordersTabItems = [];
		if (this.removedFieldsets.indexOf('alignment') === -1) {
			this.addConfigElement(this.buildAlignmentFieldsetConfig(element), alignmentAndBordersTabItems);
		}
		if (this.removedFieldsets.indexOf('borders') === -1) {
			this.addConfigElement(this.buildBordersFieldsetConfig(element), alignmentAndBordersTabItems);
		}
		if (!Ext.isEmpty(alignmentAndBordersTabItems)) {
			tabItems.push({
				title: this.localize('Alignment') + '/' + this.localize('Border'),
				items: alignmentAndBordersTabItems
			});
		}
		var colorTabItems = [];
		if (this.removedFieldsets.indexOf('color') === -1) {
			this.addConfigElement(this.buildColorsFieldsetConfig(element), colorTabItems);
		}
		if (!Ext.isEmpty(colorTabItems)) {
			tabItems.push({
				title: this.localize('Background and colors'),
				items: colorTabItems
			});
		}
		return tabItems;
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		title: the window title
	 * @param	object		arguments: some arguments for the handler
	 * @param	integer		dimensions: the opening width of the window
	 * @param	object		tabItems: the configuration of the tabbed panel
	 * @param	function	handler: handler when the OK button if clicked
	 *
	 * @return	void
	 */
	openDialogue: function (title, arguments, dimensions, tabItems, handler) {
		if (this.dialog) {
			this.dialog.close();
		}
		this.dialog = new Ext.Window({
			title: this.getHelpTip(arguments.buttonId, title),
			arguments: arguments,
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
			iconCls: this.getButton(arguments.buttonId).iconCls,
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
						fn: this.syncHeight,
						scope: this
					}
				},
				items: tabItems
			},
			buttons: [
				this.buildButtonConfig('OK', handler),
				this.buildButtonConfig('Cancel', this.onCancel)
			]
		});
		this.show();
	},
	/*
	 * Insert the table or update the table properties and close the dialogue
	 */
	tablePropertiesUpdate: function () {
		this.restoreSelection()
		var params = {};
		var fieldTypes = ['combo', 'textfield', 'numberfield', 'checkbox', 'colorpalettefield'];
		this.dialog.findBy(function (item) {
			if (fieldTypes.indexOf(item.getXType()) !== -1) {
				params[item.getItemId()] = item.getValue();
				return true;
			}
			return false;
		});
		var errorFlag = false;
		if (this.properties.required) {
			if (this.properties.required.indexOf('captionOrSummary') != -1) {
				if (!/\S/.test(params.f_caption) && !/\S/.test(params.f_summary)) {
					TYPO3.Dialog.ErrorDialog({
						title: this.getButton(this.dialog.arguments.buttonId).tooltip.title,
						msg: this.localize('captionOrSummary' + '-required')
					});
					var field = this.dialog.find('itemId', 'f_caption')[0];
					var tab = field.findParentByType('container');
					tab.ownerCt.activate(tab);
					field.focus();
					return false;
				}
			} else {
				var required = {
					f_caption: 'caption',
					f_summary: 'summary'
				};
				Ext.iterate(required, function (item) {
					if (!params[item] && this.properties.required.indexOf(required[item]) != -1) {
						TYPO3.Dialog.ErrorDialog({
							title: this.getButton(this.dialog.arguments.buttonId).tooltip.title,
							msg: this.localize(required[item] + '-required')
						});
						var field = this.dialog.find('itemId', item)[0];
						var tab = field.findParentByType('container');
						tab.ownerCt.activate(tab);
						field.focus();
						errorFlag = true;
						return false;
					}
				}, this);
				if (errorFlag) {
					return false;
				}
			}
		}
		var doc = this.editor.document;
		if (this.dialog.arguments.buttonId === 'InsertTable') {
			var required = {
				f_rows: 'You must enter a number of rows',
				f_cols: 'You must enter a number of columns'
			};
			Ext.iterate(required, function (item) {
				if (!params[item]) {
					TYPO3.Dialog.ErrorDialog({
						title: this.getButton(this.dialog.arguments.buttonId).tooltip.title,
						msg: this.localize(required[item])
					});
					var field = this.dialog.find('itemId', item)[0];
					var tab = field.findParentByType('container');
					tab.ownerCt.activate(tab);
					field.focus();
					errorFlag = true;
					return false;
				}
			}, this);
			if (errorFlag) {
				return false;
			}
			var table = doc.createElement('table');
			var tbody = doc.createElement('tbody');
			table.appendChild(tbody);
			for (var i = params.f_rows; --i >= 0;) {
				var tr = doc.createElement('tr');
				tbody.appendChild(tr);
				for (var j = params.f_cols; --j >= 0;) {
					var td = doc.createElement('td');
					if (!HTMLArea.isIEBeforeIE9) {
						td.innerHTML = '<br />';
					}
					tr.appendChild(td);
				}
			}
		} else {
			var table = this.dialog.arguments.element;
		}
		this.setHeaders(table, params);
		this.processStyle(table, params);
		table.removeAttribute('border');
		Ext.iterate(params, function (item) {
			var val = params[item];
			switch (item) {
			    case "f_caption":
				if (/\S/.test(val)) {
					// contains non white-space characters
					var caption = table.getElementsByTagName("caption");
					if (caption) {
						caption = caption[0];
					}
					if (!caption) {
						var caption = doc.createElement("caption");
						table.insertBefore(caption, table.firstChild);
					}
					caption.innerHTML = val;
				} else {
					// delete the caption if found
					if (table.caption) table.deleteCaption();
				}
				break;
			    case "f_summary":
				table.summary = val;
				break;
			    case "f_width":
				table.style.width = ("" + val) + params.f_unit;
				break;
			    case "f_align":
				table.align = val;
				break;
			    case "f_spacing":
				table.cellSpacing = val;
				break;
			    case "f_padding":
				table.cellPadding = val;
				break;
			    case "f_frames":
			    	    if (val !== 'not set' && table.style.borderStyle !== 'none') {
			    	    	    table.frame = val;
			    	    } else {
			    	    	    table.removeAttribute('rules');
			    	    }
				break;
			    case "f_rules":
			    	    if (val !== 'not set') {
			    	    	    table.rules = val;
			    	    } else {
			    	    	    table.removeAttribute('rules');
			    	    }
				break;
			    case "f_st_float":
				switch (val) {
				    case "not set":
					HTMLArea.DOM.removeClass(table, this.floatRight);
					HTMLArea.DOM.removeClass(table, this.floatLeft);
					break;
				    case "right":
					HTMLArea.DOM.removeClass(table, this.floatLeft);
					HTMLArea.DOM.addClass(table, this.floatRight);
					break;
				    case "left":
					HTMLArea.DOM.removeClass(table, this.floatRight);
					HTMLArea.DOM.addClass(table, this.floatLeft);
					break;
				}
				break;
			    case "f_st_textAlign":
				if (this.getPluginInstance('BlockElements')) {
					this.getPluginInstance('BlockElements').toggleAlignmentClass(table, this.convertAlignment[val]);
					table.style.textAlign = "";
				}
				break;
			    case "f_class":
			    case "f_class_tbody":
			    case "f_class_thead":
			    case "f_class_tfoot":
				var tpart = table;
				if (item.length > 7) {
					tpart = table.getElementsByTagName(item.substring(8,13))[0];
				}
				if (tpart) {
					this.getPluginInstance('BlockStyle').applyClassChange(tpart, val);
				}
				break;
			    case "f_lang":
				this.getPluginInstance('Language').setLanguageAttributes(table, val);
				break;
			    case "f_dir":
				table.dir = (val != "not set") ? val : "";
				break;
			}
		}, this);
		if (this.dialog.arguments.buttonId === "InsertTable") {
			if (!HTMLArea.isIEBeforeIE9) {
				this.editor.getSelection().insertNode(table);
			} else {
				table.id = "htmlarea_table_insert";
				this.editor.getSelection().insertNode(table);
				table = this.editor.document.getElementById(table.id);
				table.removeAttribute("id");
			}
			this.editor.getSelection().selectNodeContents(table.rows[0].cells[0], true);
			if (this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.setOnTableCreation) {
				this.toggleBorders(true);
			}
		}
		this.close();
	},
	/*
	 * Update the row/column/cell properties
	 */
	rowCellPropertiesUpdate: function() {
		this.restoreSelection()
			// Collect values from each form field
		var params = {};
		var fieldTypes = ['combo', 'textfield', 'numberfield', 'checkbox', 'colorpalettefield'];
		this.dialog.findBy(function (item) {
			if (fieldTypes.indexOf(item.getXType()) !== -1) {
				params[item.getItemId()] = item.getValue();
				return true;
			}
			return false;
		});
		var cell = this.dialog.arguments.cell;
		var column = this.dialog.arguments.column;
		var section = (cell || column) ? this.dialog.arguments.element.parentNode.parentNode : this.dialog.arguments.element.parentNode;
		var table = section.parentNode;
		var elements = [];
		if (column) {
			elements = this.getColumnCells(this.dialog.arguments.element);
		} else {
			elements.push(this.dialog.arguments.element);
		}
		Ext.each(elements, function (element) {
			this.processStyle(element, params);
			Ext.iterate(params, function (item) {
				var val = params[item];
				switch (item) {
				    case "f_cell_type":
					if (val.substring(0,2) != element.nodeName.toLowerCase()) {
						element = this.remapCell(element, val.substring(0,2));
						this.editor.getSelection().selectNodeContents(element, true);
					}
					if (val.substring(2,10) != element.scope) {
						element.scope = val.substring(2,10);
					}
					break;
				    case "f_cell_abbr":
					if (!column) {
					    	element.abbr = (element.nodeName.toLowerCase() == 'td') ? '' : val;
					}
					break;
				    case "f_rowgroup":
					var nodeName = section.nodeName.toLowerCase();
					if (val != nodeName) {
						var newSection = table.getElementsByTagName(val)[0];
						if (!newSection) var newSection = table.insertBefore(this.editor.document.createElement(val), table.getElementsByTagName("tbody")[0]);
						if (nodeName == "thead" && val == "tbody") var newElement = newSection.insertBefore(element, newSection.firstChild);
							else var newElement = newSection.appendChild(element);
						if (!section.hasChildNodes()) table.removeChild(section);
					}
					if (params.f_convertCells) {
						if (val == "thead") {
							this.remapRowCells(element, "th");
						} else {
							this.remapRowCells(element, "td");
						}
					}
					break;
				    case "f_st_textAlign":
					if (this.getPluginInstance('BlockElements')) {
						this.getPluginInstance('BlockElements').toggleAlignmentClass(element, this.convertAlignment[val]);
						element.style.textAlign = "";
					}
					break;
				    case "f_class":
					this.getPluginInstance('BlockStyle').applyClassChange(element, val);
					break;
				    case "f_lang":
					this.getPluginInstance('Language').setLanguageAttributes(element, val);
					break;
				    case "f_dir":
					element.dir = (val != "not set") ? val : "";
					break;
				}
			}, this);
		}, this);
		this.reStyleTable(table);
		this.close();
	},
	/*
	 * This function gets called when the plugin is generated
	 */
	onGenerate: function () {
			// Set table borders if requested by configuration
		if (this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.setOnRTEOpen) {
			this.toggleBorders(true);
		}
			// Register handler for the enter key for IE and Opera when buttons.table.disableEnterParagraphs is set in the editor configuration
		if ((Ext.isIE || Ext.isOpera) && this.disableEnterParagraphs) {
			this.editor.iframe.keyMap.addBinding({
				key: Ext.EventObject.ENTER,
				shift: false,
				handler: this.onKey,
				scope: this
			});
		}
	},
	/*
	 * This function gets called when the toolbar is being updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			switch (button.itemId) {
				case 'TO-toggle-borders':
					button.setInactive(!HTMLArea.DOM.hasClass(this.editor.document.body, 'htmlarea-showtableborders'));
					break;
				case 'TO-cell-merge':
					if (Ext.isGecko) {
						var selection = this.editor.getSelection().get().selection;
						button.setDisabled(button.disabled || selection.rangeCount < 2);
					}
					break;
			}
		}
	},
	/*
	 * This function gets called when a Table Operations button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;

		var mozbr = !HTMLArea.isIEBeforeIE9 ? "<br />" : "";
		var tableParts = ["tfoot", "thead", "tbody"];
		var tablePartsIndex = { tfoot : 0, thead : 1, tbody : 2 };

		// helper function that clears the content in a table row
		function clearRow(tr) {
			var tds = tr.getElementsByTagName("td");
			for (var i = tds.length; --i >= 0;) {
				var td = tds[i];
				td.rowSpan = 1;
				td.innerHTML = mozbr;
			}
			var tds = tr.getElementsByTagName("th");
			for (var i = tds.length; --i >= 0;) {
				var td = tds[i];
				td.rowSpan = 1;
				td.innerHTML = mozbr;
			}
		};

		function splitRow(td) {
			var n = parseInt("" + td.rowSpan);
			var colSpan = td.colSpan;
			var nodeName = td.nodeName.toLowerCase();
			td.rowSpan = 1;
			var tr = td.parentNode;
			var sectionRowIndex = tr.sectionRowIndex;
			var rows = tr.parentNode.rows;
			var index = td.cellIndex;
			while (--n > 0) {
				tr = rows[++sectionRowIndex];
					// Last row
				if (!tr) tr = td.parentNode.parentNode.appendChild(editor.document.createElement("tr"));
				var otd = editor.document.createElement(nodeName);
				otd.colSpan = colSpan;
				otd.innerHTML = mozbr;
				tr.insertBefore(otd, tr.cells[index]);
			}
		};

		function splitCol(td) {
			var nc = parseInt("" + td.colSpan);
			var nodeName = td.nodeName.toLowerCase();
			td.colSpan = 1;
			var tr = td.parentNode;
			var ref = td.nextSibling;
			while (--nc > 0) {
				var otd = editor.document.createElement(nodeName);
				otd.rowSpan = td.rowSpan;
				otd.innerHTML = mozbr;
				tr.insertBefore(otd, ref);
			}
		};

		function splitCell(td) {
			var nc = parseInt("" + td.colSpan);
			splitCol(td);
			var cells = td.parentNode.cells;
			var index = td.cellIndex;
			while (nc-- > 0) {
				splitRow(cells[index++]);
			}
		};

		function selectNextNode(el) {
			var node = el.nextSibling;
			while (node && node.nodeType != 1) {
				node = node.nextSibling;
			}
			if (!node) {
				node = el.previousSibling;
				while (node && node.nodeType != 1) {
					node = node.previousSibling;
				}
			}
			if (!node) node = el.parentNode;
			editor.getSelection().selectNodeContents(node);
		};

		function getSelectedCells(sel) {
			var cell, range, i = 0, cells = [];
			try {
				while (range = sel.getRangeAt(i++)) {
					cell = range.startContainer.childNodes[range.startOffset];
					while (!/^(td|th|body)$/.test(cell.nodeName.toLowerCase())) cell = cell.parentNode;
					if (/^(td|th)$/.test(cell.nodeName.toLowerCase())) cells.push(cell);
				}
			} catch(e) {
			/* finished walking through selection */
			}
			return cells;
		};

		function deleteEmptyTable(table) {
			var lastPart = true;
			for (var j = tableParts.length; --j >= 0;) {
				var tablePart = table.getElementsByTagName(tableParts[j])[0];
				if (tablePart) lastPart = false;
			}
			if (lastPart) {
				selectNextNode(table);
				table.parentNode.removeChild(table);
			}
		};

		function computeCellIndexes(table) {
			var matrix = [];
			var lookup = {};
			for (var m = tableParts.length; --m >= 0;) {
				var tablePart = table.getElementsByTagName(tableParts[m])[0];
				if (tablePart) {
					var rows = tablePart.rows;
					for (var i = 0, n = rows.length; i < n; i++) {
						var cells = rows[i].cells;
						for (var j=0; j< cells.length; j++) {
							var cell = cells[j];
							var rowIndex = cell.parentNode.rowIndex;
							var cellId = tableParts[m]+"-"+rowIndex+"-"+cell.cellIndex;
							var rowSpan = cell.rowSpan || 1;
							var colSpan = cell.colSpan || 1;
							var firstAvailCol;
							if(typeof(matrix[rowIndex])=="undefined") { matrix[rowIndex] = []; }
							// Find first available column in the first row
							for (var k=0; k<matrix[rowIndex].length+1; k++) {
								if (typeof(matrix[rowIndex][k])=="undefined") {
									firstAvailCol = k;
									break;
								}
							}
							lookup[cellId] = firstAvailCol;
							for (var k=rowIndex; k<rowIndex+rowSpan; k++) {
								if (typeof(matrix[k])=="undefined") { matrix[k] = []; }
								var matrixrow = matrix[k];
								for (var l=firstAvailCol; l<firstAvailCol+colSpan; l++) {
									matrixrow[l] = "x";
								}
							}
						}
					}
				}
			}
			return lookup;
		};

		function getActualCellIndex(cell, lookup) {
			return lookup[cell.parentNode.parentNode.nodeName.toLowerCase()+"-"+cell.parentNode.rowIndex+"-"+cell.cellIndex];
		};

		switch (buttonId) {
			// ROWS
		    case "TO-row-insert-above":
		    case "TO-row-insert-under":
			var tr = this.editor.getSelection().getFirstAncestorOfType("tr");
			if (!tr) break;
			var otr = tr.cloneNode(true);
			clearRow(otr);
			otr = tr.parentNode.insertBefore(otr, (/under/.test(buttonId) ? tr.nextSibling : tr));
			this.editor.getSelection().selectNodeContents(otr.firstChild, true);
			this.reStyleTable(tr.parentNode.parentNode);
			break;
		    case "TO-row-delete":
			var tr = this.editor.getSelection().getFirstAncestorOfType("tr");
			if (!tr) break;
			var part = tr.parentNode;
			var table = part.parentNode;
			if(part.rows.length == 1) {  // this the last row, delete the whole table part
				selectNextNode(part);
				table.removeChild(part);
				deleteEmptyTable(table);
			} else {
					// set the caret first to a position that doesn't disappear.
				selectNextNode(tr);
				part.removeChild(tr);
			}
			this.reStyleTable(table);
			break;
		    case "TO-row-split":
			var cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
			if (!cell) break;
			var sel = editor.getSelection().get().selection;
			if (Ext.isGecko && !sel.isCollapsed) {
				var cells = getSelectedCells(sel);
				for (i = 0; i < cells.length; ++i) {
					splitRow(cells[i]);
				}
			} else {
				splitRow(cell);
			}
			break;

			// COLUMNS
		    case "TO-col-insert-before":
		    case "TO-col-insert-after":
			var cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
			if (!cell) break;
			var index = cell.cellIndex;
			var table = cell.parentNode.parentNode.parentNode;
			for (var j = tableParts.length; --j >= 0;) {
				var tablePart = table.getElementsByTagName(tableParts[j])[0];
				if (tablePart) {
					var rows = tablePart.rows;
					for (var i = rows.length; --i >= 0;) {
						var tr = rows[i];
						var ref = tr.cells[index + (/after/.test(buttonId) ? 1 : 0)];
						if (!ref) {
							var otd = editor.document.createElement(tr.lastChild.nodeName.toLowerCase());
							otd.innerHTML = mozbr;
							tr.appendChild(otd);
						} else {
							var otd = editor.document.createElement(ref.nodeName.toLowerCase());
							otd.innerHTML = mozbr;
							tr.insertBefore(otd, ref);
						}
					}
				}
			}
			this.reStyleTable(table);
			break;
		    case "TO-col-split":
		    	var cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
			if (!cell) break;
			var sel = this.editor.getSelection().get().selection;
			if (Ext.isGecko && !sel.isCollapsed) {
				var cells = getSelectedCells(sel);
				for (i = 0; i < cells.length; ++i) {
					splitCol(cells[i]);
				}
			} else {
				splitCol(cell);
			}
			this.reStyleTable(table);
			break;
		    case "TO-col-delete":
			var cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
			if (!cell) break;
			var index = cell.cellIndex;
			var part = cell.parentNode.parentNode;
			var table = part.parentNode;
			var lastPart = true;
			for (var j = tableParts.length; --j >= 0;) {
				var tablePart = table.getElementsByTagName(tableParts[j])[0];
				if (tablePart) {
					var rows = tablePart.rows;
					var lastColumn = true;
					for (var i = rows.length; --i >= 0;) {
						if(rows[i].cells.length > 1) lastColumn = false;
					}
					if (lastColumn) {
							// this is the last column, delete the whole tablepart
							// set the caret first to a position that doesn't disappear
						selectNextNode(tablePart);
						table.removeChild(tablePart);
					} else {
							// set the caret first to a position that doesn't disappear
						if (part == tablePart) selectNextNode(cell);
						for (var i = rows.length; --i >= 0;) {
							if(rows[i].cells[index]) rows[i].removeChild(rows[i].cells[index]);
						}
						lastPart = false;
					}
				}
			}
			if (lastPart) {
					// the last table section was deleted: delete the whole table
					// set the caret first to a position that doesn't disappear
				selectNextNode(table);
				table.parentNode.removeChild(table);
			}
			this.reStyleTable(table);
			break;

			// CELLS
		    case "TO-cell-split":
			var cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
			if (!cell) break;
			var sel = this.editor.getSelection().get().selection;
			if (Ext.isGecko && !sel.isCollapsed) {
				var cells = getSelectedCells(sel);
				for (i = 0; i < cells.length; ++i) {
					splitCell(cells[i]);
				}
			} else {
				splitCell(cell);
			}
			this.reStyleTable(table);
			break;
		    case "TO-cell-insert-before":
		    case "TO-cell-insert-after":
			var cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
			if (!cell) break;
			var tr = cell.parentNode;
			var otd = editor.document.createElement(cell.nodeName.toLowerCase());
			otd.innerHTML = mozbr;
			tr.insertBefore(otd, (/after/.test(buttonId) ? cell.nextSibling : cell));
			this.reStyleTable(tr.parentNode.parentNode);
			break;
		    case "TO-cell-delete":
			var cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
			if (!cell) break;
			var row = cell.parentNode;
			if(row.cells.length == 1) {  // this is the only cell in the row, delete the row
				var part = row.parentNode;
				var table = part.parentNode;
				if (part.rows.length == 1) {  // this the last row, delete the whole table part
					selectNextNode(part);
					table.removeChild(part);
					deleteEmptyTable(table);
				} else {
					selectNextNode(row);
					part.removeChild(row);
				}
			} else {
					// set the caret first to a position that doesn't disappear
				selectNextNode(cell);
				row.removeChild(cell);
			}
			this.reStyleTable(table);
			break;
		    case "TO-cell-merge":
			var sel = this.editor.getSelection().get().selection;
			var range, i = 0;
			var rows = new Array();
			for (var k = tableParts.length; --k >= 0;) rows[k] = [];
			var row = null;
			var cells = null;
			if (Ext.isGecko) {
				try {
					while (range = sel.getRangeAt(i++)) {
						var td = range.startContainer.childNodes[range.startOffset];
						if (td.parentNode != row) {
							(cells) && rows[tablePartsIndex[row.parentNode.nodeName.toLowerCase()]].push(cells);
							row = td.parentNode;
							cells = [];
						}
						cells.push(td);
					}
				} catch(e) {
					/* finished walking through selection */
				}
				try { rows[tablePartsIndex[row.parentNode.nodeName.toLowerCase()]].push(cells); } catch(e) { }
			} else {
				// Internet Explorer, WebKit and Opera
				var cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
				if (!cell) {
					TYPO3.Dialog.InformationDialog({
						title: this.getButton('TO-cell-merge').tooltip.title,
						msg: this.localize('Please click into some cell')
					});
					break;
				}
				var tr = cell.parentNode;
				var no_cols = parseInt(prompt(this.localize("How many columns would you like to merge?"), 2));
				if (!no_cols) break;
				var no_rows = parseInt(prompt(this.localize("How many rows would you like to merge?"), 2));
				if (!no_rows) break;
				var lookup = computeCellIndexes(cell.parentNode.parentNode.parentNode);
				var first_index = getActualCellIndex(cell, lookup);
					// Collect cells on first row
				var td = cell, colspan = 0;
				cells = [];
				for (var i = no_cols; --i >= 0;) {
					if (!td) break;
					cells.push(td);
					var last_index = getActualCellIndex(td, lookup);
					td = td.nextSibling;
				}
				rows[tablePartsIndex[tr.parentNode.nodeName.toLowerCase()]].push(cells);
					// Collect cells on following rows
				var index, first_index_found, last_index_found;
				for (var j = 1; j < no_rows; ++j) {
					tr = tr.nextSibling;
					if (!tr) break;
					cells = [];
					first_index_found = false;
					for (var i = 0; i < tr.cells.length; ++i) {
						td = tr.cells[i];
						if (!td) break;
						index = getActualCellIndex(td, lookup);
						if (index > last_index) break;
						if (index == first_index) first_index_found = true;
						if (index >= first_index) cells.push(td);
					}
						// If not rectangle, we quit!
					if (!first_index_found) break;
					rows[tablePartsIndex[tr.parentNode.nodeName.toLowerCase()]].push(cells);
				}
			}
			for (var k = tableParts.length; --k >= 0;) {
				var cell, row;
				var cellHTML = "";
				var cellRowSpan = 0;
				var cellColSpan, maxCellColSpan = 0;
				if (rows[k] && rows[k][0]) {
					for (var i = 0; i < rows[k].length; ++i) {
						var cells = rows[k][i];
						var cellColSpan = 0;
						if (!cells) continue;
						cellRowSpan += cells[0].rowSpan ? cells[0].rowSpan : 1;
						for (var j = 0; j < cells.length; ++j) {
							cell = cells[j];
							row = cell.parentNode;
							cellHTML += cell.innerHTML;
							cellColSpan += cell.colSpan ? cell.colSpan : 1;
							if (i || j) {
								cell.parentNode.removeChild(cell);
								if(!row.cells.length) row.parentNode.removeChild(row);
							}
						}
						if (maxCellColSpan < cellColSpan) {
							maxCellColSpan = cellColSpan;
						}
					}
					var td = rows[k][0][0];
					td.innerHTML = cellHTML;
					td.rowSpan = cellRowSpan;
					td.colSpan = maxCellColSpan;
					editor.getSelection().selectNodeContents(td);
				}
			}
			this.reStyleTable(table);
			break;

			// CREATION AND PROPERTIES
		    case "InsertTable":
		    case "TO-table-prop":
		    	this.openPropertiesDialogue('table', buttonId);
			break;
		    case "TO-table-restyle":
			this.reStyleTable(this.editor.getSelection().getFirstAncestorOfType('table'));
			break;
		    case "TO-row-prop":
		    	this.openPropertiesDialogue('row', buttonId);
			break;
		    case "TO-col-prop":
		    	this.openPropertiesDialogue('column', buttonId);
			break;
		    case "TO-cell-prop":
		    	this.openPropertiesDialogue('cell', buttonId);
			break;
		    case "TO-toggle-borders":
			this.toggleBorders();
			break;
		    default:
			break;
		}
	},
	/*
	 * Returns an array of all cells in the column containing the given cell
	 *
	 * @param	object		cell: the cell serving as reference point for the column
	 *
	 * @return	array		the array of cells of the column
	 */
	getColumnCells : function (cell) {
		var cells = new Array();
		var index = cell.cellIndex;
		var table = cell.parentNode.parentNode.parentNode;
		for (var j = this.tableParts.length; --j >= 0;) {
			var tablePart = table.getElementsByTagName(this.tableParts[j])[0];
			if (tablePart) {
				var rows = tablePart.rows;
				for (var i = rows.length; --i >= 0;) {
					if(rows[i].cells.length > index) {
						cells.push(rows[i].cells[index]);
					}
				}
			}
		}
		return cells;
	},
	/*
	 * Toggles the display of borders on tables and table cells
	 *
	 * @param	boolean		forceBorders: if set, borders are displayed whatever the current state
	 *
	 * @return	void
	 */
	toggleBorders : function (forceBorders) {
		var body = this.editor.document.body;
		if (!HTMLArea.DOM.hasClass(body, 'htmlarea-showtableborders')) {
			HTMLArea.DOM.addClass(body,'htmlarea-showtableborders');
		} else if (!forceBorders) {
			HTMLArea.DOM.removeClass(body,'htmlarea-showtableborders');
		}
	},
	/*
	 * Applies to rows/cells the alternating and counting classes of an alternating or counting style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 *
	 * @return	void
	 */
	reStyleTable: function (table) {
		if (table) {
			if (this.classesUrl && (typeof(HTMLArea.classesAlternating) === 'undefined' || typeof(HTMLArea.classesCounting) === 'undefined')) {
				this.getJavascriptFile(this.classesUrl, function (options, success, response) {
					if (success) {
						try {
							if (typeof(HTMLArea.classesAlternating) === 'undefined' || typeof(HTMLArea.classesCounting) === 'undefined') {
								eval(response.responseText);
							}
							this.reStyleTable(table);
						} catch(e) {
							this.appendToLog('reStyleTable', 'Error evaluating contents of Javascript file: ' + this.classesUrl, 'error');
						}
					}
				});
			} else {
				var classNames = table.className.trim().split(' ');
				for (var i = classNames.length; --i >= 0;) {
					var classConfiguration = HTMLArea.classesAlternating[classNames[i]];
					if (classConfiguration && classConfiguration.rows) {
						if (classConfiguration.rows.oddClass && classConfiguration.rows.evenClass) {
							this.alternateRows(table, classConfiguration);
						}
					}
					if (classConfiguration && classConfiguration.columns) {
						if (classConfiguration.columns.oddClass && classConfiguration.columns.evenClass) {
							this.alternateColumns(table, classConfiguration);
						}
					}
					classConfiguration = HTMLArea.classesCounting[classNames[i]];
					if (classConfiguration && classConfiguration.rows) {
						if (classConfiguration.rows.rowClass) {
							this.countRows(table, classConfiguration);
						}
					}
					if (classConfiguration && classConfiguration.columns) {
						if (classConfiguration.columns.columnClass) {
							this.countColumns(table, classConfiguration);
						}
					}
				}
			}
		}
	},
	/*
	 * Removes from rows/cells the alternating classes of an alternating style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 * @param	string		removeClass: the name of the class that identifies the alternating style scheme
	 *
	 * @return	void
	 */
	removeAlternatingClasses: function (table, removeClass) {
		if (table) {
			if (this.classesUrl && typeof(HTMLArea.classesAlternating) === 'undefined') {
				this.getJavascriptFile(this.classesUrl, function (options, success, response) {
					if (success) {
						try {
							if (typeof(HTMLArea.classesAlternating) === 'undefined') {
								eval(response.responseText);
							}
							this.removeAlternatingClasses(table, removeClass);
						} catch(e) {
							this.appendToLog('removeAlternatingClasses', 'Error evaluating contents of Javascript file: ' + this.classesUrl, 'error');
						}
					}
				});
			} else {
				var classConfiguration = HTMLArea.classesAlternating[removeClass];
				if (classConfiguration) {
					if (classConfiguration.rows && classConfiguration.rows.oddClass && classConfiguration.rows.evenClass) {
						this.alternateRows(table, classConfiguration, true);
					}
					if (classConfiguration.columns && classConfiguration.columns.oddClass && classConfiguration.columns.evenClass) {
						this.alternateColumns(table, classConfiguration, true);
					}
				}
			}
		}
	},
	/*
	 * Applies/removes the alternating classes of an alternating rows style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 * @param	object		classConfifuration: the alternating sub-array of the configuration of the class
	 * @param	boolean		remove: if true, the classes are removed
	 *
	 * @return	void
	 */
	alternateRows : function (table, classConfiguration, remove) {
		var oddClass = { tbody : classConfiguration.rows.oddClass, thead : classConfiguration.rows.oddHeaderClass };
		var evenClass = { tbody : classConfiguration.rows.evenClass, thead : classConfiguration.rows.evenHeaderClass };
		var startAt = parseInt(classConfiguration.rows.startAt);
		startAt = remove ? 1 : (startAt ? startAt : 1);
		var rows = table.rows, type, odd, even;
			// Loop through the rows
		for (var i = startAt-1, n = rows.length; i < n; i++) {
			var row = rows[i];
			type = (row.parentNode.nodeName.toLowerCase() == "thead") ? "thead" : "tbody";
			odd = oddClass[type];
			even = evenClass[type];
			if (remove) {
				HTMLArea.DOM.removeClass(row, odd);
				HTMLArea.DOM.removeClass(row, even);
				// Check if i is even, and apply classes for both possible results
			} else if (odd && even) {
				if ((i % 2) == 0) {
					if (HTMLArea.DOM.hasClass(row, even)) {
						HTMLArea.DOM.removeClass(row, even);
					}
					HTMLArea.DOM.addClass(row, odd);
				} else {
					if (HTMLArea.DOM.hasClass(row, odd)) {
						HTMLArea.DOM.removeClass(row, odd);
					}
					HTMLArea.DOM.addClass(row, even);
				}
			}
		}
	},
	/*
	 * Applies/removes the alternating classes of an alternating columns style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 * @param	object		classConfifuration: the alternating sub-array of the configuration of the class
	 * @param	boolean		remove: if true, the classes are removed
	 *
	 * @return	void
	 */
	alternateColumns : function (table, classConfiguration, remove) {
		var oddClass = { td : classConfiguration.columns.oddClass, th : classConfiguration.columns.oddHeaderClass };
		var evenClass = { td : classConfiguration.columns.evenClass, th : classConfiguration.columns.evenHeaderClass };
		var startAt = parseInt(classConfiguration.columns.startAt);
		startAt = remove ? 1 : (startAt ? startAt : 1);
		var rows = table.rows, type, odd, even;
			// Loop through the rows of the table
		for (var i = rows.length; --i >= 0;) {
				// Loop through the cells
			var cells = rows[i].cells;
			for (var j = startAt-1, n = cells.length; j < n; j++) {
				var cell = cells[j];
				type = cell.nodeName.toLowerCase();
				odd = oddClass[type];
				even = evenClass[type];
				if (remove) {
					if (odd) HTMLArea.DOM.removeClass(cell, odd);
					if (even) HTMLArea.DOM.removeClass(cell, even);
				} else if (odd && even) {
						// Check if j+startAt is even, and apply classes for both possible results
					if ((j % 2) == 0) {
						if (HTMLArea.DOM.hasClass(cell, even)) {
							HTMLArea.DOM.removeClass(cell, even);
						}
						HTMLArea.DOM.addClass(cell, odd);
					} else{
						if (HTMLArea.DOM.hasClass(cell, odd)) {
							HTMLArea.DOM.removeClass(cell, odd);
						}
						HTMLArea.DOM.addClass(cell, even);
					}
				}
			}
		}
	},
	/*
	 * Removes from rows/cells the counting classes of an counting style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 * @param	string		removeClass: the name of the class that identifies the counting style scheme
	 *
	 * @return	void
	 */
	removeCountingClasses: function (table, removeClass) {
		if (table) {
			if (this.classesUrl && typeof(HTMLArea.classesCounting) === 'undefined') {
				this.getJavascriptFile(this.classesUrl, function (options, success, response) {
					if (success) {
						try {
							if (typeof(HTMLArea.classesCounting) === 'undefined') {
								eval(response.responseText);
							}
							this.removeCountingClasses(table, removeClass);
						} catch(e) {
							this.appendToLog('removeCountingClasses', 'Error evaluating contents of Javascript file: ' + this.classesUrl, 'error');
						}
					}
				});
			} else {
				var classConfiguration = HTMLArea.classesCounting[removeClass];
				if (classConfiguration) {
					if (classConfiguration.rows && classConfiguration.rows.rowClass) {
						this.countRows(table, classConfiguration, true);
					}
					if (classConfiguration.columns && classConfiguration.columns.columnClass) {
						this.countColumns(table, classConfiguration, true);
					}
				}
			}
		}
	},
	/*
	 * Applies/removes the counting classes of an counting rows style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 * @param	object		classConfifuration: the counting sub-array of the configuration of the class
	 * @param	boolean		remove: if true, the classes are removed
	 *
	 * @return	void
	 */
	countRows : function (table, classConfiguration, remove) {
		var rowClass = { tbody : classConfiguration.rows.rowClass, thead : classConfiguration.rows.rowHeaderClass };
		var rowLastClass = { tbody : classConfiguration.rows.rowLastClass, thead : classConfiguration.rows.rowHeaderLastClass };
		var startAt = parseInt(classConfiguration.rows.startAt);
		startAt = remove ? 1 : (startAt ? startAt : 1);
		var rows = table.rows, type, baseClassName, rowClassName, lastRowClassName;
			// Loop through the rows
		for (var i = startAt-1, n = rows.length; i < n; i++) {
			var row = rows[i];
			type = (row.parentNode.nodeName.toLowerCase() == "thead") ? "thead" : "tbody";
			baseClassName = rowClass[type];
			rowClassName = baseClassName + (i+1);
			lastRowClassName = rowLastClass[type];
			if (remove) {
				if (baseClassName) {
					HTMLArea.DOM.removeClass(row, rowClassName);
				}
				if (lastRowClassName && i == n-1) {
					HTMLArea.DOM.removeClass(row, lastRowClassName);
				}
			} else {
				if (baseClassName) {
					if (HTMLArea.DOM.hasClass(row, baseClassName, true)) {
						HTMLArea.DOM.removeClass(row, baseClassName, true);
					}
					HTMLArea.DOM.addClass(row, rowClassName);
				}
				if (lastRowClassName) {
					if (i == n-1) {
						HTMLArea.DOM.addClass(row, lastRowClassName);
					} else if (HTMLArea.DOM.hasClass(row, lastRowClassName)) {
						HTMLArea.DOM.removeClass(row, lastRowClassName);
					}
				}
			}
		}
	},
	/*
	 * Applies/removes the counting classes of a counting columns style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 * @param	object		classConfifuration: the counting sub-array of the configuration of the class
	 * @param	boolean		remove: if true, the classes are removed
	 *
	 * @return	void
	 */
	countColumns : function (table, classConfiguration, remove) {
		var columnClass = { td : classConfiguration.columns.columnClass, th : classConfiguration.columns.columnHeaderClass };
		var columnLastClass = { td : classConfiguration.columns.columnLastClass, th : classConfiguration.columns.columnHeaderLastClass };
		var startAt = parseInt(classConfiguration.columns.startAt);
		startAt = remove ? 1 : (startAt ? startAt : 1);
		var rows = table.rows, type, baseClassName, columnClassName, lastColumnClassName;
			// Loop through the rows of the table
		for (var i = rows.length; --i >= 0;) {
				// Loop through the cells
			var cells = rows[i].cells;
			for (var j = startAt-1, n = cells.length; j < n; j++) {
				var cell = cells[j];
				type = cell.nodeName.toLowerCase();
				baseClassName = columnClass[type];
				columnClassName = baseClassName + (j+1);
				lastColumnClassName = columnLastClass[type];
				if (remove) {
					if (baseClassName) {
						HTMLArea.DOM.removeClass(cell, columnClassName);
					}
					if (lastColumnClassName && j == n-1) {
							HTMLArea.DOM.removeClass(cell, lastColumnClassName);
					}
				} else {
					if (baseClassName) {
						if (HTMLArea.DOM.hasClass(cell, baseClassName, true)) {
							HTMLArea.DOM.removeClass(cell, baseClassName, true);
						}
						HTMLArea.DOM.addClass(cell, columnClassName);
					}
					if (lastColumnClassName) {
						if (j == n-1) {
							HTMLArea.DOM.addClass(cell, lastColumnClassName);
						} else if (HTMLArea.DOM.hasClass(cell, lastColumnClassName)) {
							HTMLArea.DOM.removeClass(cell, lastColumnClassName);
						}
					}
				}
			}
		}
	},
	/*
	 * This function sets the headers cells on the table (top, left, both or none)
	 *
	 * @param	object		table: the table being edited
	 * @param	object		params: the field values entered in the form
	 *
	 * @return	void
	 */
	setHeaders: function (table, params) {
		var headers = params.f_headers;
		var doc = this.editor.document;
		var tbody = table.tBodies[0];
		var thead = table.tHead;
		if (thead && !thead.rows.length && !tbody.rows.length) {
			 // Table is degenerate
			return table;
		}
		if (headers == "top") {
			if (!thead) {
				var thead = doc.createElement("thead");
				thead = table.insertBefore(thead, tbody);
			}
			if (!thead.rows.length) {
				var firstRow = thead.appendChild(tbody.rows[0]);
			} else {
				var firstRow = thead.rows[0];
			}
			HTMLArea.DOM.removeClass(firstRow, this.useHeaderClass);
		} else {
			if (thead) {
				var rows = thead.rows;
				if (rows.length) {
					for (var i = rows.length; --i >= 0;) {
						this.remapRowCells(rows[i], "td");
						if (tbody.rows.length) {
							tbody.insertBefore(rows[i], tbody.rows[0]);
						} else {
							tbody.appendChild(rows[i]);
						}
					}
				}
				table.removeChild(thead);
			}
		}
		if (headers == "both") {
			var firstRow = tbody.rows[0];
			HTMLArea.DOM.addClass(firstRow, this.useHeaderClass);
		} else if (headers != "top") {
			var firstRow = tbody.rows[0];
			HTMLArea.DOM.removeClass(firstRow, this.useHeaderClass);
			this.remapRowCells(firstRow, "td");
		}
		if (headers == "top" || headers == "both") {
			this.remapRowCells(firstRow, "th");
		}
		if (headers == "left") {
			var firstRow = tbody.rows[0];
		}
		if (headers == "left" || headers == "both") {
			var rows = tbody.rows;
			for (var i = rows.length; --i >= 0;) {
				if (i || rows[i] == firstRow) {
					if (rows[i].cells[0].nodeName.toLowerCase() != "th") {
						var th = this.remapCell(rows[i].cells[0], "th");
						th.scope = "row";
					}
				}
			}
		} else {
			var rows = tbody.rows;
			for (var i = rows.length; --i >= 0;) {
				if (rows[i].cells[0].nodeName.toLowerCase() != "td") {
					rows[i].cells[0].scope = "";
					var td = this.remapCell(rows[i].cells[0], "td");
				}
			}
		}
		this.reStyleTable(table);
	},

	/*
	 * This function remaps the given cell to the specified node name
	 */
	remapCell : function(element, nodeName) {
		var newCell = HTMLArea.DOM.convertNode(element, nodeName);
		var attributes = element.attributes, attributeName, attributeValue;
		for (var i = attributes.length; --i >= 0;) {
			attributeName = attributes.item(i).nodeName;
			if (nodeName != 'td' || (attributeName != 'scope' && attributeName != 'abbr')) {
				attributeValue = element.getAttribute(attributeName);
				if (attributeValue) {
					newCell.setAttribute(attributeName, attributeValue);
				}
			}
		}
			// In IE before IE9, the above fails to update the classname and style attributes.
		if (HTMLArea.isIEBeforeIE9) {
			if (element.style.cssText) {
				newCell.style.cssText = element.style.cssText;
			}
			if (element.className) {
				newCell.setAttribute("class", element.className);
				if (!newCell.className) {
						// IE before IE8
					newCell.setAttribute("className", element.className);
				}
			} else {
				newCell.removeAttribute("class");
					// IE before IE8
				newCell.removeAttribute("className");
			}
		}

		if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
			if (newCell.className && /\S/.test(newCell.className)) {
				var allowedClasses = this.tags[nodeName].allowedClasses;
				var classNames = newCell.className.trim().split(" ");
				for (var i = classNames.length; --i >= 0;) {
					if (!allowedClasses.test(classNames[i])) {
						HTMLArea.DOM.removeClass(newCell, classNames[i]);
					}
				}
			}
		}
		return newCell;
	},

	remapRowCells : function (row, toType) {
		var cells = row.cells;
		if (toType === "th") {
			for (var i = cells.length; --i >= 0;) {
				if (cells[i].nodeName.toLowerCase() != "th") {
					var th = this.remapCell(cells[i], "th");
					th.scope = "col";
				}
			}
		} else {
			for (var i = cells.length; --i >= 0;) {
				if (cells[i].nodeName.toLowerCase() != "td") {
					var td = this.remapCell(cells[i], "td");
					td.scope = "";
				}
			}
		}
	},

	/*
	 * This function applies the style properties found in params to the given element
	 *
	 * @param	object		element: the element
	 * @param	object		params: the properties
	 *
	 * @return	void
	 */
	processStyle: function (element, params) {
		var style = element.style;
		if (HTMLArea.isIEBeforeIE9) {
			style.styleFloat = "";
		} else {
			style.cssFloat = "";
		}
		style.textAlign = "";
		for (var i in params) {
			if (params.hasOwnProperty(i)) {
				var val = params[i];
				switch (i) {
				    case "f_st_backgroundColor":
				    	if (/\S/.test(val)) {
				    		style.backgroundColor = ((val.charAt(0) === '#') ? '' : '#') + val;
				    	} else {
				    		style.backgroundColor = '';
				    	}
					break;
				    case "f_st_color":
				    	if (/\S/.test(val)) {
				    		style.color = ((val.charAt(0) === '#') ? '' : '#') + val;
				    	} else {
				    		style.color = '';
				    	}
					break;
				    case "f_st_backgroundImage":
					if (/\S/.test(val)) {
						style.backgroundImage = "url(" + val + ")";
					} else {
						style.backgroundImage = "";
					}
					break;
				    case "f_st_borderWidth":
					if (/\S/.test(val)) {
						style.borderWidth = val + "px";
					} else {
						style.borderWidth = "";
					}
					if (params.f_st_borderStyle == "none") style.borderWidth = "0px";
					if (params.f_st_borderStyle == "not set") style.borderWidth = "";
					break;
				    case "f_st_borderStyle":
					style.borderStyle = (val != "not set") ? val : "";
					break;
				    case "f_st_borderColor":
				    	if (/\S/.test(val)) {
				    		style.borderColor = ((val.charAt(0) === '#') ? '' : '#') + val;
					} else {
						style.borderColor = '';
					}
					if (params.f_st_borderStyle === 'none') {
						style.borderColor = '';
					}
					break;
				    case "f_st_borderCollapse":
					style.borderCollapse = (val !== 'not set') ? val : '';
					if (params.f_st_borderStyle === 'none') {
						style.borderCollapse = '';
					}
					break;
				    case "f_st_width":
					if (/\S/.test(val)) {
						style.width = val + params.f_st_widthUnit;
					} else {
						style.width = "";
					}
					break;
				    case "f_st_height":
					if (/\S/.test(val)) {
						style.height = val + params.f_st_heightUnit;
					} else {
						style.height = "";
					}
					break;
				    case "f_st_textAlign":
					style.textAlign = (val != "not set") ? val : "";
					break;
				    case "f_st_vertAlign":
					style.verticalAlign = (val != "not set") ? val : "";
					break;
				}
			}
		}
	},
	/*
	 * This function builds the configuration object for the table Description fieldset
	 *
	 * @param	object		table: the table being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildDescriptionFieldsetConfig: function (table) {
		if (!Ext.isEmpty(table)) {
			var caption = table.getElementsByTagName('caption')[0];
		}
		return {
			xtype: 'fieldset',
			title: this.localize('Description'),
			defaultType: 'textfield',
			defaults: {
				labelSeparator: '',
				helpIcon: true
			},
			items: [{
				fieldLabel: this.getHelpTip('caption', 'Caption:'),
				itemId: 'f_caption',
				value: Ext.isDefined(caption) ? caption.innerHTML : '',
				width: 300,
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Description of the nature of the table')
			    	},{
				fieldLabel: this.getHelpTip('summary', 'Summary:'),
				itemId: 'f_summary',
				value: !Ext.isEmpty(table) ? table.summary : '',
				width: 300,
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Summary of the table purpose and structure')
			}]
		};
	},
	/*
	 * This function builds the configuration object for the table Size and Headers fieldset
	 *
	 * @param	object		table: the table being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildSizeAndHeadersFieldsetConfig: function (table) {
		var itemsConfig = [];
		if (Ext.isEmpty(table)) {
			itemsConfig.push({
				fieldLabel: this.getHelpTip('numberOfRows', 'Number of rows'),
				labelSeparator: ':',
				itemId: 'f_rows',
				value: (this.properties.numberOfRows && this.properties.numberOfRows.defaultValue) ? this.properties.numberOfRows.defaultValue : '2',
				width: 30,
				minValue: 1,
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Number of rows')
			});
			itemsConfig.push({
				fieldLabel: this.getHelpTip('numberOfColumns', 'Number of columns'),
				labelSeparator: ':',
				itemId: 'f_cols',
				value: (this.properties.numberOfColumns && this.properties.numberOfColumns.defaultValue) ? this.properties.numberOfColumns.defaultValue : '4',
				width: 30,
				minValue: 1,
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Number of columns')
			});
		}
		if (this.removedProperties.indexOf('headers') == -1) {
				// Create combo store
			var store = new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}],
				data: [
					[this.localize('No header cells'), 'none'],
					[this.localize('Header cells on top'), 'top'],
					[this.localize('Header cells on left'), 'left'],
					[this.localize('Header cells on top and left'), 'both']
				]
			});
			this.removeOptions(store, 'headers');
			if (Ext.isEmpty(table)) {
				var selected = (this.properties.headers && this.properties.headers.defaultValue) ? this.properties.headers.defaultValue : 'top';
			} else {
				var selected = 'none';
				var thead = table.getElementsByTagName('thead');
				var tbody = table.getElementsByTagName('tbody');
				if (thead.length && thead[0].rows.length) {
					selected = 'top';
				} else if (tbody.length && tbody[0].rows.length) {
					if (HTMLArea.DOM.hasClass(tbody[0].rows[0], this.useHeaderClass)) {
						selected = 'both';
					} else if (tbody[0].rows[0].cells.length && tbody[0].rows[0].cells[0].nodeName.toLowerCase() == 'th') {
						selected = 'left';
					}
				}
			}
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('tableHeaders', 'Headers:'),
				labelSeparator: '',
				itemId: 'f_headers',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Table headers'),
				store: store,
				width: (this.properties['headers'] && this.properties['headers'].width) ? this.properties['headers'].width : 200,
				value: selected
			}, this.configDefaults['combo']));
		}
		return {
			xtype: 'fieldset',
			title: this.localize(Ext.isEmpty(table) ? 'Size and Headers' : 'Headers'),
			defaultType: 'numberfield',
			defaults: {
				helpIcon: true
			},
			items: itemsConfig
		};
	},
	/*
	 * This function builds the configuration object for the Style fieldset
	 *
	 * @param	object		element: the element being edited, if any
	 * @param	string		buttonId: the id of the button that was pressed
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildStylingFieldsetConfig: function (element, buttonId) {
		var itemsConfig = [];
		var nodeName = element ? element.nodeName.toLowerCase() : 'table';
		var table = (nodeName == 'table');
		var select = this.buildStylingFieldConfig('f_class', (table ? 'Table class:' : 'Class:'), (table ? 'Table class selector' : 'Class selector'));
		this.setStyleOptions(select, element, nodeName, (buttonId === 'InsertTable') ? this.defaultClass : null);
		itemsConfig.push(select);
		if (element && table) {
			var tbody = element.getElementsByTagName('tbody')[0];
			if (tbody) {
				var tbodyStyleSelect = this.buildStylingFieldConfig('f_class_tbody', 'Table body class:', 'Table body class selector');
				this.setStyleOptions(tbodyStyleSelect, tbody, 'tbody');
				itemsConfig.push(tbodyStyleSelect);
			}
			var thead = element.getElementsByTagName('thead')[0];
			if (thead) {
				var theadStyleSelect = this.buildStylingFieldConfig('f_class_thead', 'Table header class:', 'Table header class selector');
				this.setStyleOptions(theadStyleSelect, thead, 'thead');
				itemsConfig.push(theadStyleSelect);
			}
			var tfoot = element.getElementsByTagName('tfoot')[0];
			if (tfoot) {
				var tfootStyleSelect = this.buildStylingFieldConfig('f_class_tfoot', 'Table footer class:', 'Table footer class selector');
				this.setStyleOptions(tfootStyleSelect, tfoot, 'tfoot');
				itemsConfig.push(tfootStyleSelect);
			}
		}
		return {
			xtype: 'fieldset',
			defaults: {
				labelSeparator: ''
			},
			title: table ? this.getHelpTip('tableStyle', 'CSS Style') : this.localize('CSS Style'),
			items: itemsConfig
		};
	},
	/*
	 * This function builds a style selection field
	 *
	 * @param	string		fieldName: the name of the field
	 * @param	string		fieldLabel: the label for the field
	 * @param	string		fieldTitle: the title for the field tooltip
	 *
	 * @return	object		the style selection field object
	 */
	buildStylingFieldConfig: function(fieldName, fieldLabel, fieldTitle) {
		return new Ext.form.ComboBox(Ext.apply({
			xtype: 'combo',
			itemId: fieldName,
			fieldLabel: this.getHelpTip(fieldTitle, fieldLabel),
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize(fieldTitle),
			width: (this.properties['style'] && this.properties['style'].width) ? this.properties['style'].width : 300,
			store: new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}, { name: 'style'} ],
				data: [[this.localize('No block style'), 'none']]
			})
			}, {
			tpl: '<tpl for="."><div ext:qtip="{value}" style="{style}text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>'
			}, this.configDefaults['combo']
		));
	},
	/*
	 * This function populates the style store and sets the selected option
	 *
	 * @param	object:		dropDown: the combobox object
	 * @param	object		element: the element being edited, if any
	 * @param	string		nodeName: the type of element ('table' on table insertion)
	 * @param	string		defaultClass: default class, if any is configured
	 *
	 * @return	object		the fieldset configuration object
	 */
	setStyleOptions: function (dropDown, element, nodeName, defaultClass) {
		var blockStyle = this.getPluginInstance('BlockStyle');
		if (dropDown && blockStyle) {
			if (defaultClass) {
				var classNames = new Array();
				classNames.push(defaultClass);
			} else {
				var classNames = HTMLArea.DOM.getClassNames(element);
			}
			blockStyle.buildDropDownOptions(dropDown, nodeName);
			blockStyle.setSelectedOption(dropDown, classNames, 'noUnknown', defaultClass);
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
		var languageObject = this.getPluginInstance('Language');
		if (this.removedProperties.indexOf('language') == -1 && this.getButton('Language')) {
			var selectedLanguage = !Ext.isEmpty(element) ? languageObject.getLanguageAttribute(element) : 'none';
			function initLanguageStore (store) {
				if (selectedLanguage !== 'none') {
					store.removeAt(0);
					store.insert(0, new store.recordType({
						text: languageObject.localize('Remove language mark'),
						value: 'none'
					}));
				}
			}
			var languageStore = new Ext.data.JsonStore({
				autoDestroy:  true,
				autoLoad: true,
				root: 'options',
				fields: [ { name: 'text'}, { name: 'value'} ],
				url: this.getDropDownConfiguration('Language').dataUrl,
				listeners: {
					load: initLanguageStore
				}
			});
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('languageCombo', 'Language', 'Language'),
				itemId: 'f_lang',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Language'),
				store: languageStore,
				width: (this.properties['language'] && this.properties['language'].width) ? this.properties['language'].width : 200,
				value: selectedLanguage
			}, this.configDefaults['combo']));
		}
		if (this.removedProperties.indexOf('direction') == -1 && (this.getButton('LeftToRight') || this.getButton('RightToLeft'))) {
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('directionCombo', 'Text direction', 'Language'),
				itemId: 'f_dir',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Text direction'),
				store: new Ext.data.ArrayStore({
					autoDestroy:  true,
					fields: [ { name: 'text'}, { name: 'value'}],
					data: [
						[this.localize('Not set'), 'not set'],
						[this.localize('RightToLeft'), 'rtl'],
						[this.localize('LeftToRight'), 'ltr']
					]
				}),
				width: (this.properties['direction'] && this.properties['dirrection'].width) ? this.properties['direction'].width : 200,
				value: !Ext.isEmpty(element) && element.dir ? element.dir : 'not set'
			}, this.configDefaults['combo']));
		}
		return {
			xtype: 'fieldset',
			title: this.localize('Language'),
			items: itemsConfig
		};
	},
	/*
	 * This function builds the configuration object for the spacing fieldset
	 *
	 * @param	object		table: the table being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildSpacingFieldsetConfig: function (table) {
		return {
			xtype: 'fieldset',
			title: this.localize('Spacing and padding'),
			defaultType: 'numberfield',
			defaults: {
				labelSeparator: '',
				helpIcon: true
			},
			items: [{
				fieldLabel: this.getHelpTip('cellSpacing', 'Cell spacing:'),
				itemId: 'f_spacing',
				value: !Ext.isEmpty(table) ? table.cellSpacing : '',
				width: 30,
				minValue: 0,
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Space between adjacent cells')
				},{
				fieldLabel: this.getHelpTip('cellPadding', 'Cell padding:'),
				itemId: 'f_padding',
				value: !Ext.isEmpty(table) ? table.cellPadding : '',
				width: 30,
				minValue: 0,
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Space between content and border in cell')
			}]
		};
	},
	/*
	 * This function builds the configuration object for the Layout fieldset
	 *
	 * @param	object		table: the element being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildLayoutFieldsetConfig: function(element) {
		var itemsConfig = [];
		var nodeName = element ? element.nodeName.toLowerCase() : 'table';
		switch(nodeName) {
			case 'table' :
				var widthTitle = 'Table width';
				var heightTitle = 'Table height';
				break;
			case 'tr' :
				var widthTitle = 'Row width';
				var heightTitle = 'Row height';
				break;
			case 'td' :
			case 'th' :
				var widthTitle = 'Cell width';
				var heightTitle = 'Cell height';
		}
		if (this.removedProperties.indexOf('width') === -1) {
			var widthUnitStore = new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}],
				data: [
					[this.localize('percent'), '%'],
					[this.localize('pixels'), 'px'],
					[this.localize('em'), 'em']
				]
			});
			this.removeOptions(widthUnitStore, 'widthUnit');
			itemsConfig.push({
				fieldLabel: this.getHelpTip(widthTitle, 'Width:'),
				labelSeparator: '',
				itemId: 'f_st_width',
				value: element ? this.getLength(element.style.width) : ((this.properties.width && this.properties.width.defaultValue) ? this.properties.width.defaultValue : ''),
				width: 30,
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize(widthTitle)
			});
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('Width unit', 'Width unit'),
				itemId: 'f_st_widthUnit',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Width unit'),
				store: widthUnitStore,
				width: (this.properties['widthUnit'] && this.properties['widthUnit'].width) ? this.properties['widthUnit'].width : 70,
				value: element ? (/%/.test(element.style.width) ? '%' : (/px/.test(element.style.width) ? 'px' : 'em')) : ((this.properties.widthUnit && this.properties.widthUnit.defaultValue) ? this.properties.widthUnit.defaultValue : '%')
			}, this.configDefaults['combo']));
		}
		if (this.removedProperties.indexOf('height') === -1) {
			var heightUnitStore = new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}],
				data: [
					[this.localize('percent'), '%'],
					[this.localize('pixels'), 'px'],
					[this.localize('em'), 'em']
				]
			});
			this.removeOptions(heightUnitStore, 'heightUnit');
			itemsConfig.push({
				fieldLabel: this.getHelpTip(heightTitle, 'Height:'),
				labelSeparator: '',
				itemId: 'f_st_height',
				value: element ? this.getLength(element.style.height) : ((this.properties.height && this.properties.height.defaultValue) ? this.properties.height.defaultValue : ''),
				width: 30,
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize(heightTitle)
			});
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('Height unit', 'Height unit'),
				itemId: 'f_st_heightUnit',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Height unit'),
				store: heightUnitStore,
				width: (this.properties['heightUnit'] && this.properties['heightUnit'].width) ? this.properties['heightUnit'].width : 70,
				value: element ? (/%/.test(element.style.height) ? '%' : (/px/.test(element.style.height) ? 'px' : 'em')) : ((this.properties.heightUnit && this.properties.heightUnit.defaultValue) ? this.properties.heightUnit.defaultValue : '%')
			}, this.configDefaults['combo']));
		}
		if (nodeName == 'table' && this.removedProperties.indexOf('float') === -1) {
			var floatStore = new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}],
				data: [
					[this.localize('Not set'), 'not set'],
					[this.localize('Left'), 'left'],
					[this.localize('Right'), 'right']
				]
			});
			this.removeOptions(floatStore, 'float');
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('tableFloat', 'Float:'),
				labelSeparator: '',
				itemId: 'f_st_float',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Specifies where the table should float'),
				store: floatStore,
				width: (this.properties['float'] && this.properties['float'].width) ? this.properties['float'].width : 120,
				value: element ? (Ext.get(element).hasClass(this.floatLeft) ? 'left' : (Ext.get(element).hasClass(this.floatRight) ? 'right' : 'not set')) : this.floatDefault
			}, this.configDefaults['combo']));
		}
		return {
			xtype: 'fieldset',
			title: this.localize('Layout'),
			defaultType: 'numberfield',
			defaults: {
				helpIcon: true
			},
			items: itemsConfig
		};
	},
	/*
	 * This function builds the configuration object for the Layout fieldset
	 *
	 * @param	object		element: the element being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildAlignmentFieldsetConfig: function (element) {
		var itemsConfig = [];
			// Text alignment
		var selectedTextAlign = 'not set';
		var blockElements = this.getPluginInstance('BlockElements');
		if (element && blockElements) {
			Ext.iterate(this.convertAlignment, function (value) {
				if (Ext.get(element).hasClass(blockElements.useClass[this.convertAlignment[value]])) {
					selectedTextAlign = value;
					return false;
				}
				return true;
			}, this);
		} else {
			selectedTextAlign = (element && element.style.textAlign) ? element.style.textAlign : 'not set';
		}
		itemsConfig.push(Ext.apply({
			xtype: 'combo',
			fieldLabel: this.getHelpTip('textAlignment', 'Text alignment:'),
			itemId: 'f_st_textAlign',
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Horizontal alignment of text within cell'),
			store: new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}],
				data: [
					[this.localize('Not set'), 'not set'],
					[this.localize('Left'), 'left'],
					[this.localize('Center'), 'center'],
					[this.localize('Right'), 'right'],
					[this.localize('Justify'), 'justify']
				]
			}),
			width: (this.properties['textAlign'] && this.properties['textAlign'].width) ? this.properties['textAlign'].width : 100,
			value: selectedTextAlign
		}, this.configDefaults['combo']));
			// Vertical alignment
		itemsConfig.push(Ext.apply({
			xtype: 'combo',
			fieldLabel: this.getHelpTip('verticalAlignment', 'Vertical alignment:'),
			itemId: 'f_st_vertAlign',
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Vertical alignment of content within cell'),
			store: new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}],
				data: [
					[this.localize('Not set'), 'not set'],
					[this.localize('Top'), 'top'],
					[this.localize('Middle'), 'middle'],
					[this.localize('Bottom'), 'bottom'],
					[this.localize('Baseline'), 'baseline']
				]
			}),
			width: (this.properties['verticalAlign'] && this.properties['verticalAlign'].width) ? this.properties['verticalAlign'].width : 100,
			value: (element && element.style.verticalAlign) ? element.style.verticalAlign : 'not set'
		}, this.configDefaults['combo']));
		return {
			xtype: 'fieldset',
			title: this.localize('Alignment'),
			defaults: {
				labelSeparator: ''
			},
			items: itemsConfig
		};
	},
	/*
	 * This function builds the configuration object for the Borders fieldset
	 *
	 * @param	object		element: the element being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildBordersFieldsetConfig: function (element) {
		var itemsConfig = [];
		var nodeName = element ? element.nodeName.toLowerCase() : 'table';
			// Border style
		var borderStyleStore = new Ext.data.ArrayStore({
			autoDestroy:  true,
			fields: [ { name: 'text'}, { name: 'value'}],
			data: [
				[this.localize('Not set'), 'not set'],
				[this.localize('No border'), 'none'],
				[this.localize('Dotted'), 'dotted'],
				[this.localize('Dashed'), 'dashed'],
				[this.localize('Solid'), 'solid'],
				[this.localize('Double'), 'double'],
				[this.localize('Groove'), 'groove'],
				[this.localize('Ridge'), 'ridge'],
				[this.localize('Inset'), 'inset'],
				[this.localize('Outset'), 'outset']
			]
		});
		this.removeOptions(borderStyleStore, 'borderStyle');
			// Gecko reports "solid solid solid solid" for "border-style: solid".
			// That is, "top right bottom left" -- we only consider the first value.
		var selectedBorderStyle = element && element.style.borderStyle ? element.style.borderStyle : ((this.properties.borderWidth) ? ((this.properties.borderStyle && this.properties.borderStyle.defaultValue) ? this.properties.borderStyle.defaultValue : 'solid') : 'not set');
		itemsConfig.push(Ext.apply({
			xtype: 'combo',
			fieldLabel: this.getHelpTip('borderStyle', 'Border style:'),
			itemId: 'f_st_borderStyle',
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Border style'),
			store: borderStyleStore,
			width: (this.properties.borderStyle && this.properties.borderStyle.width) ? this.properties.borderStyle.width : 150,
			value: selectedBorderStyle,
			listeners: {
				change: {
					fn: this.setBorderFieldsDisabled
				}
			}
		}, this.configDefaults['combo']));
			// Border width
		itemsConfig.push({
			fieldLabel: this.getHelpTip('borderWidth', 'Border width:'),
			itemId: 'f_st_borderWidth',
			value: element ? this.getLength(element.style.borderWidth) : ((this.properties.borderWidth && this.properties.borderWidth.defaultValue) ? this.properties.borderWidth.defaultValue : ''),
			width: 30,
			minValue: 0,
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Border width'),
			disabled: (selectedBorderStyle === 'none')
		});
			// Border color
		itemsConfig.push({
			xtype: 'colorpalettefield',
			fieldLabel: this.getHelpTip('borderColor', 'Color:'),
			itemId: 'f_st_borderColor',
			colors: this.editorConfiguration.disableColorPicker ? [] : null,
			colorsConfiguration: this.editorConfiguration.colors,
			value: HTMLArea.util.Color.colorToHex(element && element.style.borderColor ? element.style.borderColor : ((this.properties.borderColor && this.properties.borderColor.defaultValue) ? this.properties.borderColor.defaultValue : '')).substr(1, 6),
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Border color'),
			disabled: (selectedBorderStyle === 'none')
		});
		if (nodeName === 'table') {
				// Collapsed borders
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('collapsedBorders', 'Collapsed borders'),
				labelSeparator: ':',
				itemId: 'f_st_borderCollapse',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Collapsed borders'),
				store: new Ext.data.ArrayStore({
					autoDestroy:  true,
					fields: [ { name: 'text'}, { name: 'value'}],
					data: [
						[this.localize('Not set'), 'not set'],
						[this.localize('Collapsed borders'), 'collapse'],
						[this.localize('Detached borders'), 'separate']
					]
				}),
				width: (this.properties.borderCollapse && this.properties.borderCollapse.width) ? this.properties.borderCollapse.width : 150,
				value: element && element.style.borderCollapse ? element.style.borderCollapse : 'not set',
				disabled: (selectedBorderStyle === 'none')
			}, this.configDefaults['combo']));
				// Frame
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('frames', 'Frames:'),
				itemId: 'f_frames',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Specifies which sides should have a border'),
				store: new Ext.data.ArrayStore({
					autoDestroy:  true,
					fields: [ { name: 'text'}, { name: 'value'}],
					data: [
						[this.localize('Not set'), 'not set'],
						[this.localize('No sides'), 'void'],
						[this.localize('The top side only'), 'above'],
						[this.localize('The bottom side only'), 'below'],
						[this.localize('The top and bottom sides only'), 'hsides'],
						[this.localize('The right and left sides only'), 'vsides'],
						[this.localize('The left-hand side only'), 'lhs'],
						[this.localize('The right-hand side only'), 'rhs'],
						[this.localize('All four sides'), 'box']
					]
				}),
				width: (this.properties.frame && this.properties.frame.width) ? this.properties.frame.width : 250,
				value: (element && element.frame) ? element.frame : 'not set',
				disabled: (selectedBorderStyle === 'none')
			}, this.configDefaults['combo']));
				// Rules
			itemsConfig.push(Ext.apply({
				xtype: 'combo',
				fieldLabel: this.getHelpTip('rules', 'Rules:'),
				itemId: 'f_rules',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Specifies where rules should be displayed'),
				store: new Ext.data.ArrayStore({
					autoDestroy:  true,
					fields: [ { name: 'text'}, { name: 'value'}],
					data: [
						[this.localize('Not set'), 'not set'],
						[this.localize('No rules'), 'none'],
						[this.localize('Rules will appear between rows only'), 'rows'],
						[this.localize('Rules will appear between columns only'), 'cols'],
						[this.localize('Rules will appear between all rows and columns'), 'all']
					]
				}),
				width: (this.properties.rules && this.properties.rules.width) ? this.properties.rules.width : 360,
				value: (element && element.rules) ? element.rules : 'not set'
			}, this.configDefaults['combo']));
		}
		return {
			xtype: 'fieldset',
			title: this.localize('Frame and borders'),
			defaultType: 'numberfield',
			defaults: {
				labelSeparator: '',
				helpIcon: true
			},
			items: itemsConfig
		};
	},
	/*
	 * onChange handler: enable/disable other fields of the same fieldset
	 */
	setBorderFieldsDisabled: function (field, value) {
		field.ownerCt.findBy(function (item) {
			var itemId = item.getItemId();
			if (itemId == 'f_st_borderStyle' || itemId == 'f_rules') {
				return false;
			} else if (value === 'none') {
				switch (item.getXType()) {
					case 'numberfield':
						item.setValue(0);
						break;
					case 'colorpalettefield':
						item.setValue('');
						break;
					default:
						item.setValue('not set');
						break;
				}
				item.setDisabled(true);
			} else {
				item.setDisabled(false);
			}
			return true;
		});
	},
	/*
	 * This function builds the configuration object for the Colors fieldset
	 *
	 * @param	object		element: the element being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildColorsFieldsetConfig: function (element) {
		var itemsConfig = [];
			// Text color
		itemsConfig.push({
			xtype: 'colorpalettefield',
			fieldLabel: this.getHelpTip('textColor', 'FG Color:'),
			itemId: 'f_st_color',
			colors: this.editorConfiguration.disableColorPicker ? [] : null,
			colorsConfiguration: this.editorConfiguration.colors,
			value: HTMLArea.util.Color.colorToHex(element && element.style.color ? element.style.color : ((this.properties.color && this.properties.color.defaultValue) ? this.properties.color.defaultValue : '')).substr(1, 6)
		});
			// Background color
		itemsConfig.push({
			xtype: 'colorpalettefield',
			fieldLabel: this.getHelpTip('backgroundColor', 'Background:'),
			itemId: 'f_st_backgroundColor',
			colors: this.editorConfiguration.disableColorPicker ? [] : null,
			colorsConfiguration: this.editorConfiguration.colors,
			value: HTMLArea.util.Color.colorToHex(element && element.style.backgroundColor ? element.style.backgroundColor : ((this.properties.backgroundColor && this.properties.backgroundColor.defaultValue) ? this.properties.backgroundColor.defaultValue : '')).substr(1, 6)
		});
			// Background image
		itemsConfig.push({
			fieldLabel: this.getHelpTip('backgroundImage', 'Image URL:'),
			itemId: 'f_st_backgroundImage',
			value: element && element.style.backgroundImage.match(/url\(\s*(.*?)\s*\)/) ? RegExp.$1 : '',
			width: (this.properties.backgroundImage && this.properties.backgroundImage.width) ? this.properties.backgroundImage.width : 300,
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('URL of the background image'),
			helpIcon: true
		});
		return {
			xtype: 'fieldset',
			title: this.localize('Background and colors'),
			defaultType: 'textfield',
			defaults: {
				labelSeparator: ''
			},
			items: itemsConfig
		};
	},
	/*
	 * This function builds the configuration object for the Cell Type fieldset
	 *
	 * @param	object		element: the element being edited, if any
	 * @param	boolean		column: true if the element is a column, false if the element is a cell
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildCellTypeFieldsetConfig: function (element, column) {
		var itemsConfig = [];
		if (column) {
			var data = [
				[this.localize('Data cells'), 'td'],
				[this.localize('Headers for rows'), 'throw'],
				[this.localize('Headers for row groups'), 'throwgroup']
			];
		} else {
			var data = [
				[this.localize('Normal'), 'td'],
				[this.localize('Header for column'), 'thcol'],
				[this.localize('Header for row'), 'throw'],
				[this.localize('Header for row group'), 'throwgroup']
			];
		}
			// onChange handler: reset the CSS class dropdown and show/hide abbr field when the cell type changes
			// @param	object		cellTypeField: the combo object
			// @param	object		record: the selected record
			// @return	void
		var self = this;
		function cellTypeChange(cellTypeField, record) {
			var value = record.get('value');
			var styleCombo = self.dialog.find('itemId', 'f_class')[0];
			if (styleCombo) {
				self.setStyleOptions(styleCombo, element, value.substring(0,2));
			}
				// abbr field present only for single cell, not for column
			var abbrField = self.dialog.find('itemId', 'f_cell_abbr')[0];
			if (abbrField) {
				abbrField.setVisible(value != 'td');
				abbrField.label.setVisible(value != 'td');
			}
		}
		var selected = element.nodeName.toLowerCase() + element.scope.toLowerCase();
		itemsConfig.push(Ext.apply({
			xtype: 'combo',
			fieldLabel: column ? this.getHelpTip('columnCellsType', 'Type of cells of the column') : this.getHelpTip('cellType', 'Type of cell'),
			itemId: 'f_cell_type',
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize(column ? 'Specifies the type of cells' : 'Specifies the type of cell'),
			store: new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}],
				data: data
			}),
			width: (this.properties.cellType && this.properties.cellType.width) ? this.properties.cellType.width : 250,
			value: (column && selected == 'thcol') ? 'td' : selected,
			listeners: {
				select: {
					fn: cellTypeChange,
					scope: this
				}
			}
		}, this.configDefaults['combo']));
		if (!column) {
			itemsConfig.push({
				xtype: 'textfield',
				fieldLabel: this.getHelpTip('cellAbbreviation', 'Abbreviation'),
				labelSeparator: ':',
				itemId: 'f_cell_abbr',
				helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Header abbreviation'),
				width: 300,
				value: element.abbr,
				hideMode: 'visibility',
				hidden: (selected == 'td'),
				hideLabel: (selected == 'td')
			});
		}
		return {
			xtype: 'fieldset',
			title: this.localize(column ? 'Type of cells' : 'Cell Type and Scope'),
			defaults: {
				labelSeparator: '',
				helpIcon: true
			},
			items: itemsConfig
		};
	},
	/*
	 * This function builds the configuration object for the Row Group fieldset
	 *
	 * @param	object		element: the row being edited, if any
	 *
	 * @return	object		the fieldset configuration object
	 */
	buildRowGroupFieldsetConfig: function (element) {
		var itemsConfig = [];
		var current = element.parentNode.nodeName.toLowerCase();
			// onChange handler: show/hide cell conversion checkbox with appropriate label
			// @param	object		field: the combo object
			// @param	object		record: the selected record
			// @return	void
		function displayCheckbox(field, record, index) {
			var checkBox = field.ownerCt.getComponent('f_convertCells');
			var value = record.get('value');
			if (current !== value && (current === 'thead' || value === 'thead')) {
				checkBox.label.dom.innerHTML = (value === 'thead') ? this.localize('Make cells header cells') : this.localize('Make cells data cells');
				checkBox.show();
				checkBox.label.show();
				checkBox.setValue(true);
			} else {
				checkBox.setValue(false);
				checkBox.hide();
				checkBox.label.hide();
			}
		}
		itemsConfig.push(Ext.apply({
			xtype: 'combo',
			fieldLabel: this.getHelpTip('rowGroup', 'Row group:'),
			itemId: 'f_rowgroup',
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Table section'),
			store: new Ext.data.ArrayStore({
				autoDestroy:  true,
				fields: [ { name: 'text'}, { name: 'value'}],
				data: [
					[this.localize('Table body'), 'tbody'],
					[this.localize('Table header'), 'thead'],
					[this.localize('Table footer'), 'tfoot']
				]
			}),
			width: (this.properties.rowGroup && this.properties.rowGroup.width) ? this.properties.rowGroup.width : 150,
			value: current,
			labelSeparator: '',
			listeners: {
				select: {
					fn: displayCheckbox,
					scope: this
				}
			}
		}, this.configDefaults['combo']));
			// Cell conversion checkbox
		itemsConfig.push({
			xtype: 'checkbox',
			fieldLabel: this.localize('Make cells header cells'),
			labelSeparator: ':',
			itemId: 'f_convertCells',
			helpTitle: Ext.isDefined(TYPO3.ContextHelp) ? '' : this.localize('Make cells header cells'),
			value: false,
			hideMode: 'visibility',
			hidden: true,
			hideLabel: true
		});
		return {
			xtype: 'fieldset',
			title: this.localize('Row group'),
			defaults: {
				helpIcon: true
			},
			items: itemsConfig
		};
	},
	/*
	 * This function removes some items from a data store for the specified property
	 *
	 */
	removeOptions: function (store, property) {
		if (this.properties[property] && this.properties[property].removeItems) {
			var items = this.properties[property].removeItems.split(',');
			var index = -1;
			Ext.each(items, function (item) {
				index = store.findExact('value', item.trim());
				if (index !== -1) {
					store.removeAt(index);
				}
				return true;
			});
		}
	},
	/*
	 * This function gets called by the editor key map when a key was pressed.
	 * It will process the enter key for IE and Opera when buttons.table.disableEnterParagraphs is set in the editor configuration
	 *
	 * @param	string		key: the key code
	 * @param	object		event: the Ext event object (keydown)
	 *
	 * @return	boolean		false, if the event was taken care of
	 */
	onKey: function (key, event) {
		var range = this.editor.getSelection().createRange();
		var parentElement = this.editor.getSelection().getParentElement();
		while (parentElement && !HTMLArea.DOM.isBlockElement(parentElement)) {
			parentElement = parentElement.parentNode;
		}
		if (/^(td|th)$/i.test(parentElement.nodeName)) {
			if (HTMLArea.isIEBeforeIE9) {
				range.pasteHTML('<br />');
				range.select();
			} else {
				var brNode = this.editor.document.createElement('br');
				this.editor.getSelection().insertNode(brNode);
				if (brNode.nextSibling) {
					this.editor.getSelection().selectNodeContents(brNode.nextSibling, true);
				} else {
					this.editor.getSelection().selectNodeContents(brNode, false);
				}
			}
			event.stopEvent();
			return false;
		}
		return true;
	}
});
