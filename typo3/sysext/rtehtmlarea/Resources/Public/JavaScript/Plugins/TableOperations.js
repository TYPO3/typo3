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
 * Table Operations Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Color',
	'TYPO3/CMS/Rtehtmlarea/Plugins/BlockStyle',
	'TYPO3/CMS/Rtehtmlarea/Plugins/BlockElements',
	'TYPO3/CMS/Rtehtmlarea/Plugins/Language',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Components/Select',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Notification',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, UserAgent, Util, Dom, Event, Color, BlockStyle, BlockElements, Language, Select, $, Modal, Notification, Severity) {

	var TableOperations = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(TableOperations, Plugin);
	Util.apply(TableOperations.prototype, {

		/**
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
			/**
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
			/**
			 * Registering the buttons
			 */
			var hideToggleBorders = this.editorConfiguration.hideTableOperationsInToolbar && !(this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.keepInToolbar);
			var buttonList = this.buttonList, button, buttonId;
			for (var i = 0, n = buttonList.length; i < n; ++i) {
				button = buttonList[i];
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
			['InsertTable',        null,                               'table',              true,  'table-insert'],
			['toggle-borders',     null,                               'toggleborders',      false, 'table-show-borders'],
			['table-prop',         'table',                            'tableproperties',    true,  'table-edit-properties'],
			['table-restyle',      'table',                            'tablerestyle',       false, 'table-restyle'],
			['row-prop',           'tr',                               'rowproperties',      true,  'row-edit-properties'],
			['row-insert-above',   'tr',                               'rowinsertabove',     false, 'row-insert-above'],
			['row-insert-under',   'tr',                               'rowinsertunder',     false, 'row-insert-under'],
			['row-delete',         'tr',                               'rowdelete',          false, 'row-delete'],
			['row-split',          'td,th[rowSpan!=1]',                'rowsplit',           false, 'row-split'],
			['col-prop',           'td,th',                            'columnproperties',   true,  'column-edit-properties'],
			['col-insert-before',  'td,th',                            'columninsertbefore', false, 'column-insert-before'],
			['col-insert-after',   'td,th',                            'columninsertafter',  false, 'column-insert-after'],
			['col-delete',         'td,th',                            'columndelete',       false, 'column-delete'],
			['col-split',          'td,th[colSpan!=1]',                'columnsplit',        false, 'column-split'],
			['cell-prop',          'td,th',                            'cellproperties',     true,  'cell-edit-properties'],
			['cell-insert-before', 'td,th',                            'cellinsertbefore',   false, 'cell-insert-before'],
			['cell-insert-after',  'td,th',                            'cellinsertafter',    false, 'cell-insert-after'],
			['cell-delete',        'td,th',                            'celldelete',         false, 'cell-delete'],
			['cell-merge',         UserAgent.isGecko ? 'tr' : 'td,th', 'cellmerge',          false, 'cell-merge'],
			['cell-split',         'td,th[colSpan!=1,rowSpan!=1]',     'cellsplit',          false, 'cell-split']
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
		/**
		 * Get the integer value of a string or '' if the string is not a number
		 *
		 * @param {String} string The input value
		 *
		 * @return {Number|String} A number or ''
		 */
		getLength: function (string) {
			var length = parseInt(string);
			if (isNaN(length)) {
				length = '';
			}
			return length;
		},
		/**
		 * Open properties dialogue
		 *
		 * @param {String} type 'cell', 'column', 'row' or 'table'
		 * @param {String} buttonId The buttonId of the button that was pressed
		 */
		openPropertiesDialogue: function (type, buttonId) {
			var element,
				title;
			// Retrieve the element being edited and set configuration according to type
			switch (type) {
				case 'cell':
				case 'column':
					element = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
					this.properties = (this.buttonsConfiguration.cellproperties && this.buttonsConfiguration.cellproperties.properties) ? this.buttonsConfiguration.cellproperties.properties : {};
					title = (type === 'column') ? 'Column Properties' : 'Cell Properties';
					break;
				case 'row':
					element = this.editor.getSelection().getFirstAncestorOfType('tr');
					this.properties = (this.buttonsConfiguration.rowproperties && this.buttonsConfiguration.rowproperties.properties) ? this.buttonsConfiguration.rowproperties.properties : {};
					title = 'Row Properties';
					break;
				case 'table':
					var insert = (buttonId === 'InsertTable');
					element = insert ? null : this.editor.getSelection().getFirstAncestorOfType('table');
					this.properties = (this.buttonsConfiguration.table && this.buttonsConfiguration.table.properties) ? this.buttonsConfiguration.table.properties : {};
					title = insert ? 'Insert Table' : 'Table Properties';
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
					cell: type === 'cell',
					column: type === 'column',
					buttonId: buttonId
				},
				this.buildTabItemsConfig(element, type, buttonId),
				type === 'table' ? this.tablePropertiesUpdate : this.rowCellPropertiesUpdate
			);
		},
		/**
		 * Build the dialogue tab items config
		 *
		 * @param {Object} element The element being edited, if any
		 * @param {String} type 'cell', 'column', 'row' or 'table'
		 * @param {String} buttonId The buttonId of the button that was pressed
		 *
		 * @return {Object} The tab items configuration
		 */
		buildTabItemsConfig: function (element, type, buttonId) {
			var $tabs = $('<ul />', {'class': 'nav nav-tabs', role: 'tablist'}),
				$tabContent = $('<div />', {'class': 'tab-content'}),
				$finalMarkup,
				generalTabItems = [],
				layoutTabItems = [],
				languageTabItems = [],
				alignmentAndBordersTabItems = [],
				colorTabItems = [];

			switch (type) {
				case 'table':
					if (this.removedFieldsets.indexOf('description') === -1) {
						this.addConfigElement(this.buildDescriptionFieldsetConfig(element), generalTabItems);
					}
					if (typeof element !== 'object' || element === null || this.removedProperties.indexOf('headers') === -1) {
						this.addConfigElement(this.buildSizeAndHeadersFieldsetConfig(element), generalTabItems);
					}
					break;
				case 'column':
					if (this.removedFieldsets.indexOf('columntype') === -1) {
						this.addConfigElement(this.buildCellTypeFieldsetConfig(element, true), generalTabItems);
					}
					break;
				case 'cell':
					if (this.removedFieldsets.indexOf('celltype') === -1) {
						this.addConfigElement(this.buildCellTypeFieldsetConfig(element, false), generalTabItems);
					}
					break;
				case 'row':
					if (this.removedFieldsets.indexOf('rowgroup') === -1) {
						this.addConfigElement(this.buildRowGroupFieldsetConfig(element), generalTabItems);
					}
					break;
			}
			if (this.removedFieldsets.indexOf('style') === -1 && this.getPluginInstance('BlockStyle')) {
				this.addConfigElement(this.buildStylingFieldsetConfig(element, buttonId), generalTabItems);
			}
			if (generalTabItems.length > 0) {
				this.buildTabMarkup($tabs, $tabContent, 'general', generalTabItems, this.localize('General'));
			}
			if (type === 'table' && this.removedFieldsets.indexOf('spacing') === -1) {
				this.addConfigElement(this.buildSpacingFieldsetConfig(element), layoutTabItems);
			}
			if (this.removedFieldsets.indexOf('layout') === -1) {
				this.addConfigElement(this.buildLayoutFieldsetConfig(element), layoutTabItems);
			}
			if (layoutTabItems.length > 0) {
				this.buildTabMarkup($tabs, $tabContent, 'layout', layoutTabItems, this.localize('Layout'));
			}
			if (this.getButton('Language') && this.removedFieldsets.indexOf('language') === -1 && (this.removedProperties.indexOf('language') === -1 || this.removedProperties.indexOf('direction') === -1) && (this.getButton('Language') || this.getButton('LeftToRight') || this.getButton('RightToLeft'))) {
				this.addConfigElement(this.buildLanguageFieldsetConfig(element), languageTabItems);
			}
			if (languageTabItems.length > 0) {
				this.buildTabMarkup($tabs, $tabContent, 'language', languageTabItems, this.localize('Language'));
			}
			if (this.removedFieldsets.indexOf('alignment') === -1) {
				this.addConfigElement(this.buildAlignmentFieldsetConfig(element), alignmentAndBordersTabItems);
			}
			if (this.removedFieldsets.indexOf('borders') === -1) {
				this.addConfigElement(this.buildBordersFieldsetConfig(element), alignmentAndBordersTabItems);
			}
			if (alignmentAndBordersTabItems.length > 0) {
				this.buildTabMarkup($tabs, $tabContent, 'alignment', alignmentAndBordersTabItems, this.localize('Alignment') + '/' + this.localize('Border'));
			}
			if (this.removedFieldsets.indexOf('color') === -1) {
				this.addConfigElement(this.buildColorsFieldsetConfig(element), colorTabItems);
			}
			if (colorTabItems.length > 0) {
				this.buildTabMarkup($tabs, $tabContent, 'color', colorTabItems, this.localize('Background and colors'));
			}

			$tabs.find('li:first').addClass('active');
			$tabContent.find('.tab-pane:first').addClass('active');

			$finalMarkup = $('<form />', {'class': 'form-horizontal'}).append($tabs, $tabContent);

			return $finalMarkup;
		},

		/**
		 * Open the dialogue window
		 *
		 * @param {String} title The window title
		 * @param {Object} arguments Some arguments for the handler
		 * @param {Object} $tabItems The configuration of the tabbed panel
		 * @param {Function} handler Handler when the OK button if clicked
		 */
		openDialogue: function (title, arguments, $tabItems, handler) {
			this.dialog = Modal.show(this.localize(title), $tabItems, Severity.notice, [
				this.buildButtonConfig('Cancel', $.proxy(this.onCancel, this), true),
				this.buildButtonConfig('OK', $.proxy(handler, this), false, Severity.notice)
			]);
			this.dialog.arguments = arguments;
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},
		/**
		 * Insert the table or update the table properties and close the dialogue
		 */
		tablePropertiesUpdate: function () {
			this.restoreSelection();
			var params = {};

			this.dialog.find(':input').each(function() {
				var $field = $(this);
				params[$field.attr('name')] = $field.val();
			});

			var errorFlag = false,
				field,
				tab;
			if (this.properties.required) {
				if (this.properties.required.indexOf('captionOrSummary') !== -1) {
					if (!/\S/.test(params.f_caption) && !/\S/.test(params.f_summary)) {
						Notification.error(
							this.getButton(this.dialog.arguments.buttonId).tooltip,
							this.localize('captionOrSummary' + '-required'),
							5
						);
						field = this.dialog.find('[name="f_caption"]');
						tab = field.closest('[role="tabpanel"]').attr('id');
						this.dialog.find('[role="tablist"] a[href="#' + tab + '"]').tab('show');
						field.focus();
						return false;
					}
				} else {
					required = {
						f_caption: 'caption',
						f_summary: 'summary'
					};
					for (var item in required) {
						if (required.hasOwnProperty(item) && !params[item] && this.properties.required.indexOf(required[item]) !== -1) {
							Notification.error(
								this.getButton(this.dialog.arguments.buttonId).tooltip,
								this.this.localize(required[item] + '-required'),
								5
							);
							field = this.dialog.find('[name="' + item + '"]');
							tab = field.closest('[role="tabpanel"]').attr('id');
							this.dialog.find('[role="tablist"] a[href="#' + tab + '"]').tab('show');
							field.focus();
							errorFlag = true;
							break;
						}
					}
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
				for (var item in required) {
					if (required.hasOwnProperty(item) && !params[item]) {
						Notification.error(
							this.getButton(this.dialog.arguments.buttonId).tooltip,
							this.localize(required[item]),
							5
						);

						field = this.dialog.find('[name="' + item + '"]');
						tab = field.closest('[role="tabpanel"]').attr('id');
						this.dialog.find('[role="tablist"] a[href="#' + tab + '"]').tab('show');
						field.focus();
						errorFlag = true;
						break;
					}
				}
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
						td.innerHTML = '<br />';
						tr.appendChild(td);
					}
				}
			} else {
				table = this.dialog.arguments.element;
			}
			this.setHeaders(table, params);
			this.processStyle(table, params);
			table.removeAttribute('border');
			for (var item in params) {
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
							caption = doc.createElement("caption");
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
								Dom.removeClass(table, this.floatRight);
								Dom.removeClass(table, this.floatLeft);
								break;
							case "right":
								Dom.removeClass(table, this.floatLeft);
								Dom.addClass(table, this.floatRight);
								break;
							case "left":
								Dom.removeClass(table, this.floatRight);
								Dom.addClass(table, this.floatLeft);
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
						table.dir = (val !== "not set") ? val : "";
						break;
				}
			}
			if (this.dialog.arguments.buttonId === "InsertTable") {
				this.editor.getSelection().insertNode(table);
				this.editor.getSelection().selectNodeContents(table.rows[0].cells[0], true);
				if (this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.setOnTableCreation) {
					this.toggleBorders(true);
				}
			}
			Modal.currentModal.trigger('modal-dismiss');
		},
		/**
		 * Update the row/column/cell properties
		 */
		rowCellPropertiesUpdate: function() {
			this.restoreSelection();
			// Collect values from each form field
			var params = {};
			this.dialog.find(':input').each(function() {
				var $field = $(this);
				params[$field.attr('name')] = $field.val();
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
			for (var i = elements.length; --i >= 0;) {
				var element = elements[i];
				this.processStyle(element, params);
				for (var item in params) {
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
								element.abbr = (element.nodeName.toLowerCase() === 'td') ? '' : val;
							}
							break;
						case "f_rowgroup":
							var nodeName = section.nodeName.toLowerCase();
							if (val != nodeName) {
								var newSection = table.getElementsByTagName(val)[0];
								if (!newSection) {
									newSection = table.insertBefore(this.editor.document.createElement(val), table.getElementsByTagName("tbody")[0]);
								}
								var newElement;
								if (nodeName === "thead" && val === "tbody") {
									newElement = newSection.insertBefore(element, newSection.firstChild);
								} else {
									newElement = newSection.appendChild(element);
								}
								if (!section.hasChildNodes()) {
									table.removeChild(section);
								}
							}
							if (params.f_convertCells) {
								if (val === "thead") {
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
							element.dir = (val !== "not set") ? val : "";
							break;
					}
				}
			}
			this.reStyleTable(table);
			Modal.currentModal.trigger('modal-dismiss');
		},

		/**
		 * This function gets called when the plugin is generated
		 */
		onGenerate: function () {
			// Set table borders if requested by configuration
			if (this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.setOnRTEOpen) {
				this.toggleBorders(true);
			}
			// Register handler for the enter key for IE and Opera when buttons.table.disableEnterParagraphs is set in the editor configuration
			if ((UserAgent.isIE || UserAgent.isOpera) && this.disableEnterParagraphs) {
				var self = this;
				this.editor.iframe.keyMap.addBinding({
					key: Event.ENTER,
					shift: false,
					handler: function (event) { return self.onKey(event); }
				});
			}
		},

		/**
		 * This function gets called when the toolbar is being updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				switch (button.itemId) {
					case 'TO-toggle-borders':
						button.setInactive(!Dom.hasClass(this.editor.document.body, 'htmlarea-showtableborders'));
						break;
					case 'TO-cell-merge':
						if (UserAgent.isGecko) {
							var selection = this.editor.getSelection().get().selection;
							button.setDisabled(button.disabled || selection.rangeCount < 2);
						}
						break;
				}
			}
		},
		/**
		 * This function gets called when a Table Operations button was pressed.
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 *
		 * @return {Boolean} False if action is completed
		 */
		onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;

			var mozbr = '<br />';
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
				var ths = tr.getElementsByTagName("th");
				for (var i = ths.length; --i >= 0;) {
					var th = ths[i];
					th.rowSpan = 1;
					th.innerHTML = mozbr;
				}
				delete tds;
				delete ths;
			}

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
			}

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
			}

			function splitCell(td) {
				var nc = parseInt("" + td.colSpan);
				splitCol(td);
				var cells = td.parentNode.cells;
				var index = td.cellIndex;
				while (nc-- > 0) {
					splitRow(cells[index++]);
				}
			}

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
			}

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
			}

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
			}

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
								if (typeof matrix[rowIndex] === 'undefined') { matrix[rowIndex] = []; }
								// Find first available column in the first row
								for (var k=0; k<matrix[rowIndex].length+1; k++) {
									if (typeof matrix[rowIndex][k] === 'undefined') {
										firstAvailCol = k;
										break;
									}
								}
								lookup[cellId] = firstAvailCol;
								for (var k=rowIndex; k<rowIndex+rowSpan; k++) {
									if (typeof matrix[k] === 'undefined') { matrix[k] = []; }
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
			}

			function getActualCellIndex(cell, lookup) {
				return lookup[cell.parentNode.parentNode.nodeName.toLowerCase()+"-"+cell.parentNode.rowIndex+"-"+cell.cellIndex];
			}

			var tr, part, table, cell, sel, cells, rows, tablePart, index, otd;
			switch (buttonId) {
				// ROWS
				case "TO-row-insert-above":
				case "TO-row-insert-under":
					tr = this.editor.getSelection().getFirstAncestorOfType("tr");
					if (!tr) {
						break;
					}
					var otr = tr.cloneNode(true);
					clearRow(otr);
					otr = tr.parentNode.insertBefore(otr, (/under/.test(buttonId) ? tr.nextSibling : tr));
					this.editor.getSelection().selectNodeContents(otr.firstChild, true);
					this.reStyleTable(tr.parentNode.parentNode);
					break;
				case "TO-row-delete":
					tr = this.editor.getSelection().getFirstAncestorOfType("tr");
					if (!tr) {
						break;
					}
					part = tr.parentNode;
					table = part.parentNode;
					if(part.rows.length === 1) {  // this the last row, delete the whole table part
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
					cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
					if (!cell) {
						break;
					}
					sel = editor.getSelection().get().selection;
					if (UserAgent.isGecko && !sel.isCollapsed) {
						cells = getSelectedCells(sel);
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
					cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
					if (!cell) {
						break;
					}
					index = cell.cellIndex;
					table = cell.parentNode.parentNode.parentNode;
					for (var j = tableParts.length; --j >= 0;) {
						tablePart = table.getElementsByTagName(tableParts[j])[0];
						if (tablePart) {
							rows = tablePart.rows;
							for (var i = rows.length; --i >= 0;) {
								tr = rows[i];
								var ref = tr.cells[index + (/after/.test(buttonId) ? 1 : 0)];
								if (!ref) {
									otd = editor.document.createElement(tr.lastChild.nodeName.toLowerCase());
									otd.innerHTML = mozbr;
									tr.appendChild(otd);
								} else {
									otd = editor.document.createElement(ref.nodeName.toLowerCase());
									otd.innerHTML = mozbr;
									tr.insertBefore(otd, ref);
								}
							}
						}
					}
					this.reStyleTable(table);
					break;
				case "TO-col-split":
					cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
					if (!cell) {
						break;
					}
					sel = this.editor.getSelection().get().selection;
					if (UserAgent.isGecko && !sel.isCollapsed) {
						cells = getSelectedCells(sel);
						for (i = 0; i < cells.length; ++i) {
							splitCol(cells[i]);
						}
					} else {
						splitCol(cell);
					}
					this.reStyleTable(table);
					break;
				case "TO-col-delete":
					cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
					if (!cell) {
						break;
					}
					index = cell.cellIndex;
					part = cell.parentNode.parentNode;
					table = part.parentNode;
					var lastPart = true;
					for (var j = tableParts.length; --j >= 0;) {
						tablePart = table.getElementsByTagName(tableParts[j])[0];
						if (tablePart) {
							rows = tablePart.rows;
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
								if (part === tablePart) selectNextNode(cell);
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
					cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
					if (!cell) {
						break;
					}
					sel = this.editor.getSelection().get().selection;
					if (UserAgent.isGecko && !sel.isCollapsed) {
						cells = getSelectedCells(sel);
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
					cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
					if (!cell) {
						break;
					}
					tr = cell.parentNode;
					otd = editor.document.createElement(cell.nodeName.toLowerCase());
					otd.innerHTML = mozbr;
					tr.insertBefore(otd, (/after/.test(buttonId) ? cell.nextSibling : cell));
					this.reStyleTable(tr.parentNode.parentNode);
					break;
				case "TO-cell-delete":
					cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
					if (!cell) {
						break;
					}
					row = cell.parentNode;
					if(row.cells.length == 1) {  // this is the only cell in the row, delete the row
						part = row.parentNode;
						table = part.parentNode;
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
					sel = this.editor.getSelection().get().selection;
					var range, i = 0;
					rows = [];
					for (var k = tableParts.length; --k >= 0;) {
						rows[k] = [];
					}
					row = null;
					cells = null;
					if (UserAgent.isGecko) {
						try {
							while (range = sel.getRangeAt(i++)) {
								td = range.startContainer.childNodes[range.startOffset];
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
						cell = this.editor.getSelection().getFirstAncestorOfType(['td', 'th']);
						if (!cell) {
							Notification.info(
								this.getButton('TO-cell-merge').tooltip,
								this.localize('Please click into some cell')
							);
							break;
						}
						tr = cell.parentNode;
						var no_cols = parseInt(prompt(this.localize("How many columns would you like to merge?"), 2));
						if (!no_cols) {
							break;
						}
						var no_rows = parseInt(prompt(this.localize("How many rows would you like to merge?"), 2));
						if (!no_rows) {
							break;
						}
						var lookup = computeCellIndexes(cell.parentNode.parentNode.parentNode);
						var first_index = getActualCellIndex(cell, lookup);
							// Collect cells on first row
						td = cell;
						cells = [];
						for (var i = no_cols; --i >= 0;) {
							if (!td) {
								break;
							}
							cells.push(td);
							var last_index = getActualCellIndex(td, lookup);
							td = td.nextSibling;
						}
						rows[tablePartsIndex[tr.parentNode.nodeName.toLowerCase()]].push(cells);
							// Collect cells on following rows
						var first_index_found;
						for (var j = 1; j < no_rows; ++j) {
							tr = tr.nextSibling;
							if (!tr) break;
							cells = [];
							first_index_found = false;
							for (var i = 0; i < tr.cells.length; ++i) {
								td = tr.cells[i];
								if (!td) break;
								index = getActualCellIndex(td, lookup);
								if (index > last_index) {
									break;
								}
								if (index == first_index) {
									first_index_found = true;
								}
								if (index >= first_index) {
									cells.push(td);
								}
							}
								// If not rectangle, we quit!
							if (!first_index_found) break;
							rows[tablePartsIndex[tr.parentNode.nodeName.toLowerCase()]].push(cells);
						}
					}
					for (var k = tableParts.length; --k >= 0;) {
						cell, row;
						var cellHTML = "";
						var cellRowSpan = 0;
						var maxCellColSpan = 0;
						if (rows[k] && rows[k][0]) {
							for (var i = 0; i < rows[k].length; ++i) {
								cells = rows[k][i];
								var cellColSpan = 0;
								if (!cells) {
									continue;
								}
								cellRowSpan += cells[0].rowSpan ? cells[0].rowSpan : 1;
								for (var j = 0; j < cells.length; ++j) {
									cell = cells[j];
									var row = cell.parentNode;
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
			}
		},
		/**
		 * Returns an array of all cells in the column containing the given cell
		 *
		 * @param {Object} cell The cell serving as reference point for the column
		 *
		 * @return {Array} The array of cells of the column
		 */
		getColumnCells : function (cell) {
			var cells = [];
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
		/**
		 * Toggles the display of borders on tables and table cells
		 *
		 * @param {Boolean} forceBorders If set, borders are displayed whatever the current state
		 */
		toggleBorders : function (forceBorders) {
			var body = this.editor.document.body;
			if (!Dom.hasClass(body, 'htmlarea-showtableborders')) {
				Dom.addClass(body,'htmlarea-showtableborders');
			} else if (!forceBorders) {
				Dom.removeClass(body,'htmlarea-showtableborders');
			}
		},
		/**
		 * Applies to rows/cells the alternating and counting classes of an alternating or counting style scheme
		 *
		 * @param {Object} table The table to be re-styled
		 */
		reStyleTable: function (table) {
			if (table) {
				if (this.classesUrl && (typeof HTMLArea.classesAlternating === 'undefined' || typeof HTMLArea.classesCounting === 'undefined')) {
					this.getJavascriptFile(this.classesUrl, function (options, success, response) {
						if (success) {
							try {
								if (typeof HTMLArea.classesAlternating === 'undefined' || typeof HTMLArea.classesCounting === 'undefined') {
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
		/**
		 * Removes from rows/cells the alternating classes of an alternating style scheme
		 *
		 * @param {Object} table The table to be re-styled
		 * @param {String} removeClass The name of the class that identifies the alternating style scheme
		 */
		removeAlternatingClasses: function (table, removeClass) {
			if (table) {
				if (this.classesUrl && typeof HTMLArea.classesAlternating === 'undefined') {
					this.getJavascriptFile(this.classesUrl, function (options, success, response) {
						if (success) {
							try {
								if (typeof HTMLArea.classesAlternating === 'undefined') {
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
		/**
		 * Applies/removes the alternating classes of an alternating rows style scheme
		 *
		 * @param {Object} table The table to be re-styled
		 * @param {Object} classConfiguration The alternating sub-array of the configuration of the class
		 * @param {Boolean} remove If true, the classes are removed
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
				type = (row.parentNode.nodeName.toLowerCase() === "thead") ? "thead" : "tbody";
				odd = oddClass[type];
				even = evenClass[type];
				if (remove) {
					Dom.removeClass(row, odd);
					Dom.removeClass(row, even);
					// Check if i is even, and apply classes for both possible results
				} else if (odd && even) {
					if ((i % 2) == 0) {
						if (Dom.hasClass(row, even)) {
							Dom.removeClass(row, even);
						}
						Dom.addClass(row, odd);
					} else {
						if (Dom.hasClass(row, odd)) {
							Dom.removeClass(row, odd);
						}
						Dom.addClass(row, even);
					}
				}
			}
		},
		/**
		 * Applies/removes the alternating classes of an alternating columns style scheme
		 *
		 * @param {Object} table The table to be re-styled
		 * @param {Object} classConfiguration The alternating sub-array of the configuration of the class
		 * @param {Boolean} remove If true, the classes are removed
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
						if (odd) Dom.removeClass(cell, odd);
						if (even) Dom.removeClass(cell, even);
					} else if (odd && even) {
							// Check if j+startAt is even, and apply classes for both possible results
						if ((j % 2) == 0) {
							if (Dom.hasClass(cell, even)) {
								Dom.removeClass(cell, even);
							}
							Dom.addClass(cell, odd);
						} else{
							if (Dom.hasClass(cell, odd)) {
								Dom.removeClass(cell, odd);
							}
							Dom.addClass(cell, even);
						}
					}
				}
			}
		},
		/**
		 * Removes from rows/cells the counting classes of an counting style scheme
		 *
		 * @param {Object} table The table to be re-styled
		 * @param {String} removeClass The name of the class that identifies the counting style scheme
		 */
		removeCountingClasses: function (table, removeClass) {
			if (table) {
				if (this.classesUrl && typeof HTMLArea.classesCounting === 'undefined') {
					this.getJavascriptFile(this.classesUrl, function (options, success, response) {
						if (success) {
							try {
								if (typeof HTMLArea.classesCounting === 'undefined') {
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
		/**
		 * Applies/removes the counting classes of an counting rows style scheme
		 *
		 * @param {Object} table The table to be re-styled
		 * @param {Object} classConfiguration The counting sub-array of the configuration of the class
		 * @param {Boolean} remove If true, the classes are removed
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
						Dom.removeClass(row, rowClassName);
					}
					if (lastRowClassName && i == n-1) {
						Dom.removeClass(row, lastRowClassName);
					}
				} else {
					if (baseClassName) {
						if (Dom.hasClass(row, baseClassName, true)) {
							Dom.removeClass(row, baseClassName, true);
						}
						Dom.addClass(row, rowClassName);
					}
					if (lastRowClassName) {
						if (i == n-1) {
							Dom.addClass(row, lastRowClassName);
						} else if (Dom.hasClass(row, lastRowClassName)) {
							Dom.removeClass(row, lastRowClassName);
						}
					}
				}
			}
		},
		/**
		 * Applies/removes the counting classes of a counting columns style scheme
		 *
		 * @param {Object} table The table to be re-styled
		 * @param {Object} classConfiguration The counting sub-array of the configuration of the class
		 * @param {Boolean} remove If true, the classes are removed
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
							Dom.removeClass(cell, columnClassName);
						}
						if (lastColumnClassName && j == n-1) {
							Dom.removeClass(cell, lastColumnClassName);
						}
					} else {
						if (baseClassName) {
							if (Dom.hasClass(cell, baseClassName, true)) {
								Dom.removeClass(cell, baseClassName, true);
							}
							Dom.addClass(cell, columnClassName);
						}
						if (lastColumnClassName) {
							if (j == n-1) {
								Dom.addClass(cell, lastColumnClassName);
							} else if (Dom.hasClass(cell, lastColumnClassName)) {
								Dom.removeClass(cell, lastColumnClassName);
							}
						}
					}
				}
			}
		},
		/**
		 * This function sets the headers cells on the table (top, left, both or none)
		 *
		 * @param {Object} table The table being edited
		 * @param {Object} params The field values entered in the form
		 */
		setHeaders: function (table, params) {
			var headers = params.f_headers;
			var doc = this.editor.document;
			var tbody = table.tBodies[0];
			var thead = table.tHead;
			var firstRow;
			var rows;
			if (thead && !thead.rows.length && !tbody.rows.length) {
				 // Table is degenerate
				return table;
			}
			if (headers == "top") {
				if (!thead) {
					thead = doc.createElement("thead");
					thead = table.insertBefore(thead, tbody);
				}
				if (!thead.rows.length) {
					firstRow = thead.appendChild(tbody.rows[0]);
				} else {
					firstRow = thead.rows[0];
				}
				Dom.removeClass(firstRow, this.useHeaderClass);
			} else {
				if (thead) {
					rows = thead.rows;
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
				firstRow = tbody.rows[0];
				Dom.addClass(firstRow, this.useHeaderClass);
			} else if (headers != "top") {
				firstRow = tbody.rows[0];
				Dom.removeClass(firstRow, this.useHeaderClass);
				this.remapRowCells(firstRow, "td");
			}
			if (headers == "top" || headers == "both") {
				this.remapRowCells(firstRow, "th");
			}
			if (headers == "left") {
				firstRow = tbody.rows[0];
			}
			if (headers == "left" || headers == "both") {
				rows = tbody.rows;
				for (var i = rows.length; --i >= 0;) {
					if (i || rows[i] == firstRow) {
						if (rows[i].cells[0].nodeName.toLowerCase() != "th") {
							var th = this.remapCell(rows[i].cells[0], "th");
							th.scope = "row";
						}
					}
				}
			} else {
				rows = tbody.rows;
				for (var i = rows.length; --i >= 0;) {
					if (rows[i].cells[0].nodeName.toLowerCase() != "td") {
						rows[i].cells[0].scope = "";
						var td = this.remapCell(rows[i].cells[0], "td");
					}
				}
			}
			this.reStyleTable(table);
		},

		/**
		 * This function remaps the given cell to the specified node name
		 */
		remapCell: function(element, nodeName) {
			var newCell = Dom.convertNode(element, nodeName);
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
			if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
				if (newCell.className && /\S/.test(newCell.className)) {
					var allowedClasses = this.tags[nodeName].allowedClasses;
					var classNames = newCell.className.trim().split(" ");
					for (var i = classNames.length; --i >= 0;) {
						if (!allowedClasses.test(classNames[i])) {
							Dom.removeClass(newCell, classNames[i]);
						}
					}
				}
			}
			return newCell;
		},

		remapRowCells: function (row, toType) {
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

		/**
		 * This function applies the style properties found in params to the given element
		 *
		 * @param {Object} element The element
		 * @param {Object} params The properties
		 */
		processStyle: function (element, params) {
			var style = element.style;
			style.cssFloat = '';
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
							if (params.f_st_borderStyle === "none") {
								style.borderWidth = "0px";
							}
							if (params.f_st_borderStyle === "not set") {
								style.borderWidth = "";
							}
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
							style.textAlign = (val !== "not set") ? val : "";
							break;
						case "f_st_vertAlign":
							style.verticalAlign = (val !== "not set") ? val : "";
							break;
					}
				}
			}
		},
		/**
		 * This function builds the configuration object for the table Description fieldset
		 *
		 * @param {Object} table The table being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildDescriptionFieldsetConfig: function (table) {
			var $fieldset = $('<fieldset />'),
				caption = null;

			if (typeof table === 'object' && table !== null) {
				caption = table.getElementsByTagName('caption')[0];
			}

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Description')),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('caption', 'Caption:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'f_caption', 'class': 'form-control', value: (caption !== null ? caption.innerHTML : '')})
					)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('summary', 'Summary:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'f_summary', 'class': 'form-control', value: (typeof table === 'object' && table !== null ? table.summary : '')})
					)
				)
			);

			return $fieldset;
		},
		/**
		 * This function builds the configuration object for the table Size and Headers fieldset
		 *
		 * @param {Object} table The table being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildSizeAndHeadersFieldsetConfig: function (table) {
			var $fieldset = $('<fieldset />'),
				isUndefinedTable = typeof table !== 'object' || table === null;

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize(isUndefinedTable ? 'Size and Headers' : 'Headers'))
			);

			if (isUndefinedTable) {
				$fieldset.append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('numberOfRows', 'Number of rows')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<input />', {name: 'f_rows', type: 'number', min: 1, 'class': 'form-control', value: (this.properties.numberOfRows && this.properties.numberOfRows.defaultValue ? this.properties.numberOfRows.defaultValue : '2')})
						)
					),
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('numberOfColumns', 'Number of columns')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<input />', {name: 'f_cols', type: 'number', min: 1, 'class': 'form-control', value: (this.properties.numberOfColumns && this.properties.numberOfColumns.defaultValue ? this.properties.numberOfColumns.defaultValue : '4')})
						)
					)
				);
			}

			if (this.removedProperties.indexOf('headers') === -1) {
				var selectedValue;

				if (isUndefinedTable) {
					selectedValue = (this.properties.headers && this.properties.headers.defaultValue) ? this.properties.headers.defaultValue : 'top';
				} else {
					selectedValue = 'none';
					var thead = table.getElementsByTagName('thead');
					var tbody = table.getElementsByTagName('tbody');
					if (thead.length && thead[0].rows.length) {
						selectedValue = 'top';
					} else if (tbody.length && tbody[0].rows.length) {
						if (Dom.hasClass(tbody[0].rows[0], this.useHeaderClass)) {
							selectedValue = 'both';
						} else if (tbody[0].rows[0].cells.length && tbody[0].rows[0].cells[0].nodeName.toLowerCase() == 'th') {
							selectedValue = 'left';
						}
					}
				}

				$fieldset = this.attachSelectMarkup(
					$fieldset,
					this.getHelpTip('tableHeaders', 'Headers:'),
					'typeof',
					[
						[this.localize('No header cells'), 'none'],
						[this.localize('Header cells on top'), 'top'],
						[this.localize('Header cells on left'), 'left'],
						[this.localize('Header cells on top and left'), 'both']
					],
					selectedValue
				);
			}

			return $fieldset;
		},
		/**
		 * This function builds the configuration object for the Style fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @param {String} buttonId The id of the button that was pressed
		 * @return {Object} The fieldset configuration object
		 */
		buildStylingFieldsetConfig: function (element, buttonId) {
			var $fieldset = $('<fieldset />'),
				nodeName = element ? element.nodeName.toLowerCase() : 'table',
				isTable = (nodeName === 'table');

			var cssStyleSelect = new Select(this.buildStylingFieldConfig('f_class', (isTable ? 'Table class:' : 'Class:'), (isTable ? 'Table class selector' : 'Class selector')));
			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).html(isTable ? this.getHelpTip('tableStyle', 'CSS Style') : this.localize('CSS Style'))
			);
			cssStyleSelect.render($fieldset[0]);
			this.setStyleOptions(cssStyleSelect, element, nodeName, (buttonId === 'InsertTable') ? this.defaultClass : null);

			if (element && isTable) {
				var tbody = element.getElementsByTagName('tbody')[0];
				if (tbody) {
					var tableBodySelect = new Select(this.buildStylingFieldConfig('f_class_tbody', 'Table body class:', 'Table body class selector'));
					tableBodySelect.render($fieldset[0]);
					this.setStyleOptions(tableBodySelect, tbody, 'tbody');
				}
				var thead = element.getElementsByTagName('thead')[0];
				if (thead) {
					var theadStyleSelect = new Select(this.buildStylingFieldConfig('f_class_thead', 'Table header class:', 'Table header class selector'));
					theadStyleSelect.render($fieldset[0]);
					this.setStyleOptions(theadStyleSelect, thead, 'thead');
				}
				var tfoot = element.getElementsByTagName('tfoot')[0];
				if (tfoot) {
					var tfootStyleSelect = this.buildStylingFieldConfig('f_class_tfoot', 'Table footer class:', 'Table footer class selector');
					tfootStyleSelect.render($fieldset[0]);
					this.setStyleOptions(tfootStyleSelect, tfoot, 'tfoot');
				}
			}
			return $fieldset;
		},
		/** This function builds a style selection field
		 *
		 * @param {String} fieldName The name of the field
		 * @param {String} fieldLabel The label for the field
		 * @param {String} fieldTitle The title for the field tooltip
		 * @return {Object} The style selection field object
		 */
		buildStylingFieldConfig: function(fieldName, fieldLabel, fieldTitle) {
			// This is a nasty hack to fake ExtJS object configuration
			return Util.apply(
				{
					xtype: 'htmlareaselect',
					itemId: fieldName,
					fieldLabel: this.getHelpTip(fieldTitle, fieldLabel),
					helpTitle: typeof TYPO3.ContextHelp !== 'undefined' ? '' : this.localize(fieldTitle),
					width: (this.properties['style'] && this.properties['style'].width) ? this.properties['style'].width : 300
				},
				this.configDefaults['combo']
			);
		},
		/**
		 * This function populates the style store and sets the selected option
		 *
		 * @param {Object} dropDown The combobox object
		 * @param {Object} element The element being edited, if any
		 * @param {String} nodeName The type of element ('table' on table insertion)
		 * @param {String} defaultClass Default class, if any is configured
		 * @return {Object} The fieldset configuration object
		 */
		setStyleOptions: function (dropDown, element, nodeName, defaultClass) {
			var blockStyle = this.getPluginInstance('BlockStyle');
			if (dropDown && blockStyle) {
				var classNames;
				if (defaultClass) {
					classNames = [];
					classNames.push(defaultClass);
				} else {
					classNames = Dom.getClassNames(element);
				}
				blockStyle.buildDropDownOptions(dropDown, nodeName);
				blockStyle.setSelectedOption(dropDown, classNames, false, defaultClass);
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
				languageObject = this.getPluginInstance('Language');

			if (this.removedProperties.indexOf('language') === -1 && this.getButton('Language')) {
				var selectedLanguage = typeof element === 'object' && element !== null ? languageObject.getLanguageAttribute(element) : 'none';
			}

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(self.localize('Language')),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(self.getHelpTip('languageCombo', 'Language', 'Language')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<select />', {name: 'f_lang', 'class': 'form-control'})
					)
				)
			);

			$.ajax({
				url: this.getDropDownConfiguration('Language').dataUrl,
				dataType: 'json',
				success: function(response) {
					var $select = $fieldset.find('select[name="f_lang"]');

					for (var language in response.options) {
						if (response.options.hasOwnProperty(language)) {
							if (language === 0 && selectedLanguage !== 'none') {
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

			if (this.removedProperties.indexOf('direction') === -1 && (this.getButton('LeftToRight') || this.getButton('RightToLeft'))) {
				$fieldset = this.attachSelectMarkup(
					$fieldset,
					self.getHelpTip('directionCombo', 'Text direction', 'Language'),
					'f_dir',
					[
						[this.localize('Not set'), 'not set'],
						[this.localize('RightToLeft'), 'rtl'],
						[this.localize('LeftToRight'), 'ltr']
					],
					selectedLanguage
				);
			}

			return $fieldset;
		},
		/**
		 * This function builds the configuration object for the spacing fieldset
		 *
		 * @param {Object} table The table being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildSpacingFieldsetConfig: function (table) {
			var $fieldset = $('<fieldset />'),
				isTable = typeof table === 'object' && table !== null;

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Spacing and padding')),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('cellSpacing', 'Cell spacing:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'f_spacing', 'class': 'form-control', type: 'number', min: 0, value: isTable ? table.cellSpacing : ''})
					)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('cellPadding', 'Cell padding:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'f_padding', 'class': 'form-control', type: 'number', min: 0, value: isTable ? table.cellPadding : ''})
					)
				)
			);

			return $fieldset;
		},

		/**
		 * This function builds the configuration object for the Layout fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildLayoutFieldsetConfig: function(element) {
			var $fieldset = $('<fieldset />'),
				nodeName = element ? element.nodeName.toLowerCase() : 'table',
				widthTitle = '',
				heightTitle = '',
				availableUnitOptions = [
					[this.localize('percent'), '%'],
					[this.localize('pixels'), 'px'],
					[this.localize('em'), 'em']
				];

			switch(nodeName) {
				case 'table' :
					widthTitle = 'Table width';
					heightTitle = 'Table height';
					break;
				case 'tr' :
					widthTitle = 'Row width';
					heightTitle = 'Row height';
					break;
				case 'td' :
				case 'th' :
					widthTitle = 'Cell width';
					heightTitle = 'Cell height';
			}
			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Layout'))
			);

			if (this.removedProperties.indexOf('width') === -1) {
				$fieldset.append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip(widthTitle, 'Width:')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<input />', {type: 'number', name: 'f_st_width', 'class': 'form-control'}).val(element ? this.getLength(element.style.width) : ((this.properties.width && this.properties.width.defaultValue) ? this.properties.width.defaultValue : ''))
						)
					)
				);

				$fieldset = this.attachSelectMarkup(
					$fieldset,
					this.getHelpTip('Width unit', 'Width unit:'),
					'f_st_widthUnit',
					availableUnitOptions,
					element ? (/%/.test(element.style.width) ? '%' : (/px/.test(element.style.width) ? 'px' : 'em')) : ((this.properties.widthUnit && this.properties.widthUnit.defaultValue) ? this.properties.widthUnit.defaultValue : '%')
				);
			}
			if (this.removedProperties.indexOf('height') === -1) {
				$fieldset.append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip(heightTitle, 'Height:')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<input />', {type: 'number', name: 'f_st_height', 'class': 'form-control'}).val(element ? (/%/.test(element.style.height) ? '%' : (/px/.test(element.style.height) ? 'px' : 'em')) : ((this.properties.heightUnit && this.properties.heightUnit.defaultValue) ? this.properties.heightUnit.defaultValue : '%'))
						)
					)
				);

				$fieldset = this.attachSelectMarkup(
					$fieldset,
					this.getHelpTip('Height unit', 'Height unit:'),
					'f_st_heightUnit',
					availableUnitOptions,
					element ? (/%/.test(element.style.height) ? '%' : (/px/.test(element.style.height) ? 'px' : 'em')) : ((this.properties.heightUnit && this.properties.heightUnit.defaultValue) ? this.properties.heightUnit.defaultValue : '%')
				);
			}

			if (nodeName === 'table' && this.removedProperties.indexOf('float') === -1) {
				$fieldset = this.attachSelectMarkup(
					$fieldset,
					this.getHelpTip('tableFloat', 'Float:'),
					'f_st_float',
					[
						[this.localize('Not set'), 'not set'],
						[this.localize('Left'), 'left'],
						[this.localize('Right'), 'right']
					],
					element ? (Dom.hasClass(element, this.floatLeft) ? 'left' : (Dom.hasClass(element, this.floatRight) ? 'right' : 'not set')) : this.floatDefault
				);
			}

			return $fieldset;
		},

		/**
		 * This function builds the configuration object for the Layout fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildAlignmentFieldsetConfig: function (element) {
			var $fieldset = $('<fieldset />'),
				selectedTextAlign = 'not set',
				blockElements = this.getPluginInstance('BlockElements');

			// Text alignment
			if (element && blockElements) {
				for (var value in this.convertAlignment) {
					if (this.convertAlignment.hasOwnProperty(value) && Dom.hasClass(element, blockElements.useClass[this.convertAlignment[value]])) {
						selectedTextAlign = value;
						break;
					}
				}
			} else {
				selectedTextAlign = (element && element.style.textAlign) ? element.style.textAlign : 'not set';
			}

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Alignment'))
			);

			$fieldset = this.attachSelectMarkup(
				$fieldset,
				this.getHelpTip('textAlignment', 'Text alignment:'),
				'f_st_textAlign',
				[
					[this.localize('Not set'), 'not set'],
					[this.localize('Left'), 'left'],
					[this.localize('Center'), 'center'],
					[this.localize('Right'), 'right'],
					[this.localize('Justify'), 'justify']
				],
				selectedTextAlign
			);

			// Vertical alignment
			$fieldset = this.attachSelectMarkup(
				$fieldset,
				this.getHelpTip('verticalAlignment', 'Vertical alignment:'),
				'f_st_vertAlign',
				[
					[this.localize('Not set'), 'not set'],
					[this.localize('Top'), 'top'],
					[this.localize('Middle'), 'middle'],
					[this.localize('Bottom'), 'bottom'],
					[this.localize('Baseline'), 'baseline']
				],
				(element && element.style.verticalAlign) ? element.style.verticalAlign : 'not set'
			);

			return $fieldset;
		},
		/**
		 * This function builds the configuration object for the Borders fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildBordersFieldsetConfig: function (element) {
			var $fieldset = $('<fieldset />'),
				nodeName = element ? element.nodeName.toLowerCase() : 'table',
				selectedBorderStyle = element && element.style.borderStyle ? element.style.borderStyle : ((this.properties.borderWidth) ? ((this.properties.borderStyle && this.properties.borderStyle.defaultValue) ? this.properties.borderStyle.defaultValue : 'solid') : 'not set');

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Frame and borders'))
			);

			$fieldset = this.attachSelectMarkup(
				$fieldset,
				this.getHelpTip('borderStyle', 'Border style:'),
				'f_st_borderStyle',
				[
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
				],
				selectedBorderStyle,
				this.setBorderFieldsDisabled
			);

			$fieldset.append(
				// Border width
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('borderWidth', 'Border width:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'f_st_borderWidth', type: 'number', min: 0, 'class': 'form-control', value: element ? this.getLength(element.style.borderWidth) : ((this.properties.borderWidth && this.properties.borderWidth.defaultValue) ? this.properties.borderWidth.defaultValue : '')})
							.prop('disabled', selectedBorderStyle === 'none')
					)
				)
			);
			// Border color
			var $input = $('<input />', {name: 'f_st_borderColor', 'class': 'form-control t3js-color-input', value: Color.colorToHex(element && element.style.borderColor ? element.style.borderColor : ((this.properties.borderColor && this.properties.borderColor.defaultValue) ? this.properties.borderColor.defaultValue : ''))})
				.prop('disabled', selectedBorderStyle === 'none');

			require(['TYPO3/CMS/Core/Contrib/jquery.minicolors'], function () {
				$input.minicolors({
					theme: 'bootstrap',
					format: 'hex',
					position: 'bottom left'
				});
			});
			$fieldset.append(
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('borderColor', 'Color:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$input
					)
				)
			);

			if (nodeName === 'table') {
				// Collapsed borders
				$fieldset = this.attachSelectMarkup(
					$fieldset,
					this.getHelpTip('collapsedBorders', 'Collapsed borders:'),
					'f_st_borderCollapse',
					[
						[this.localize('Not set'), 'not set'],
						[this.localize('Collapsed borders'), 'collapse'],
						[this.localize('Detached borders'), 'separate']
					],
					element && element.style.borderCollapse ? element.style.borderCollapse : 'not set',
					null,
					selectedBorderStyle === 'none'
				);

				// Frame
				$fieldset = this.attachSelectMarkup(
					$fieldset,
					this.getHelpTip('frames', 'Frames:'),
					'f_frames',
					[
						[this.localize('Not set'), 'not set'],
						[this.localize('No sides'), 'void'],
						[this.localize('The top side only'), 'above'],
						[this.localize('The bottom side only'), 'below'],
						[this.localize('The top and bottom sides only'), 'hsides'],
						[this.localize('The right and left sides only'), 'vsides'],
						[this.localize('The left-hand side only'), 'lhs'],
						[this.localize('The right-hand side only'), 'rhs'],
						[this.localize('All four sides'), 'box']
					],
					element && element.frame ? element.frame : 'not set',
					null,
					selectedBorderStyle === 'none'
				);

				// Fules
				$fieldset = this.attachSelectMarkup(
					$fieldset,
					this.getHelpTip('rules', 'Rules:'),
					'f_rules',
					[
						[this.localize('Not set'), 'not set'],
						[this.localize('No rules'), 'none'],
						[this.localize('Rules will appear between rows only'), 'rows'],
						[this.localize('Rules will appear between columns only'), 'cols'],
						[this.localize('Rules will appear between all rows and columns'), 'all']
					],
					element && element.rules ? element.rules : 'not set'
				);
			}

			return $fieldset;
		},
		/**
		 * onChange handler: enable/disable other fields of the same fieldset
		 *
		 * @param {Event} e
		 */
		setBorderFieldsDisabled: function (e) {
			var $me = $(e.currentTarget),
				value = $me.val(),
				$parent = $me.closest('fieldset');

			$parent.find(':input').each(function() {
				var $field = $(this),
					name = $field.attr('name');

				if (name === 'f_st_borderStyle' || name === 'f_rules') {
					return true;
				} else {
					if (value === 'none') {
						if ($field.attr('type') === 'number') {
							$field.val('0');
						} else if ($field.hasClass('t3js-color-input')) {
							$field.val('');
						} else {
							$field.val('not set');
						}

						$field.prop('disabled', true);
					} else {
						$field.prop('disabled', false);
					}
				}
			});
		},
		/**
		 * This function builds the configuration object for the Colors fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildColorsFieldsetConfig: function (element) {
			var $fieldset = $('<fieldset />'),
				$textColorInput = $('<input />', {name: 'f_st_color', 'class': 'form-control t3js-color-input', value: Color.colorToHex(element && element.style.color ? element.style.color : ((this.properties.color && this.properties.color.defaultValue) ? this.properties.color.defaultValue : ''))}),
				$backgroundColorInput = $('<input />', {name: 'f_st_backgroundColor', 'class': 'form-control t3js-color-input', value: Color.colorToHex(element && element.style.backgroundColor ? element.style.backgroundColor : ((this.properties.color && this.properties.backgroundColor.defaultValue) ? this.properties.backgroundColor.defaultValue : ''))});

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Background and colors'))
			);

			require(['TYPO3/CMS/Core/Contrib/jquery.minicolors'], function () {
				$textColorInput.minicolors({
					theme: 'bootstrap',
					format: 'hex',
					position: 'bottom left'
				});
				$backgroundColorInput.minicolors({
					theme: 'bootstrap',
					format: 'hex',
					position: 'bottom left'
				});
			});
			$fieldset.append(
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('textColor', 'FG Color:')),
					$('<div />', {'class': 'col-sm-10'}).append($textColorInput)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('backgroundColor', 'Background:')),
					$('<div />', {'class': 'col-sm-10'}).append($backgroundColorInput)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('backgroundImage', 'Image URL:')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'f_st_backgroundImage', 'class': 'form-control', value: element && element.style.backgroundImage.match(/url\(\s*(.*?)\s*\)/) ? RegExp.$1 : ''})
					)
				)
			);

			return $fieldset;
		},
		/**
		 * This function builds the configuration object for the Cell Type fieldset
		 *
		 * @param {Object} element: the element being edited, if any
		 * @param {Boolean} column: true if the element is a column, false if the element is a cell
		 * @return {Object} the fieldset configuration object
		 */
		buildCellTypeFieldsetConfig: function (element, column) {
			var $fieldset = $('<fieldset />'),
				self = this,
				selected = element.nodeName.toLowerCase() + element.scope.toLowerCase(),
				data;
			if (column) {
				data = [
					[this.localize('Data cells'), 'td'],
					[this.localize('Headers for rows'), 'throw'],
					[this.localize('Headers for row groups'), 'throwgroup']
				];
			} else {
				data = [
					[this.localize('Normal'), 'td'],
					[this.localize('Header for column'), 'thcol'],
					[this.localize('Header for row'), 'throw'],
					[this.localize('Header for row group'), 'throwgroup']
				];
			}

			/**
			 * onChange handler: reset the CSS class dropdown and show/hide abbr field when the cell type changes
			 *
			 * @param {Event} e
			 * @param {Object} record The selected record
			 */
			function cellTypeChange(e) {
				var $me = $(e.currentTarget),
					value = $me.val();

				var styleCombo = self.dialog.find('[name="f_class"]')[0];
				if (styleCombo) {
					self.setStyleOptions(styleCombo, element, value.substring(0,2));
				}
				// abbr field present only for single cell, not for column
				var abbrField = self.dialog.find('[name="f_cell_abbr"]');
				if (abbrField) {
					abbrField.closest('.form-group').toggle(value !== 'td');
				}
			}

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize(column ? 'Type of cells' : 'Cell Type and Scope'))
			);

			$fieldset = this.attachSelectMarkup(
				$fieldset,
				column ? this.getHelpTip('columnCellsType', 'Type of cells of the column') : this.getHelpTip('cellType', 'Type of cell'),
				'f_cell_type',
				data,
				(column && selected == 'thcol') ? 'td' : selected,
				$.proxy(cellTypeChange, this)
			);

			if (!column) {
				$fieldset.append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('cellAbbreviation', 'Abbreviation')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<input />', {name: 'f_cell_abbr', 'class': 'form-control', value: element.abbr})
						)
					).toggle(selected !== 'td')
				);
			}

			return $fieldset;
		},
		/**
		 * This function builds the configuration object for the Row Group fieldset
		 *
		 * @param {Object} element: the row being edited, if any
		 * @return {Object} the fieldset configuration object
		 */
		buildRowGroupFieldsetConfig: function (element) {
			var $fieldset = $('<fieldset />'),
				current = element.parentNode.nodeName.toLowerCase();

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Row group'))
			);

			/**
			 * onChange handler: show/hide cell conversion checkbox with appropriate label
			 *
			 * @param {Event} e
			 */
			function displayCheckbox(e) {
				var $field = $(e.currentTarget),
					$checkbox = $field.closest('fieldset').find('[name="f_convertCells"]'),
					$group = $checkbox.closest('.form-group'),
					value = $field.val();

				if (current !== value && (current === 'thead' || value === 'thead')) {
					$checkbox.closest('label').find('span').text(
						value === 'thead' ? this.localize('Make cells header cells') : this.localize('Make cells data cells')
					);
					$group.show();
					$checkbox.prop('checked', true);
				} else {
					$group.hide();
					$checkbox.prop('checked', false);
				}
			}

			$fieldset = this.attachSelectMarkup(
				$fieldset,
				this.getHelpTip('rowGroup', 'Row group:'),
				'f_rowgroup',
				[
					[this.localize('Table body'), 'tbody'],
					[this.localize('Table header'), 'thead'],
					[this.localize('Table footer'), 'tfoot']
				],
				current,
				$.proxy(displayCheckbox, this)
			);

			// Cell conversion checkbox
			$fieldset.append(
				$('<div />', {'class': 'form-group col-sm-12'}).append(
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').append(
							$('<span />').text(this.localize('Make cells header cells'))
						).prepend(
							$('<input />', {type: 'checkbox', name: 'f_convertCells'})
						)
					)
				).toggle(false)
			);

			return $fieldset;
		},

		/**
		 * This function gets called by the editor key map when a key was pressed.
		 * It will process the enter key for IE and Opera when buttons.table.disableEnterParagraphs is set in the editor configuration
		 *
		 * @param {Event} event The jQuery event object (keydown)
		 * @return {Boolean} False, if the event was taken care of
		 */
		onKey: function (event) {
			this.editor.getSelection().createRange();
			var parentElement = this.editor.getSelection().getParentElement();
			while (parentElement && !Dom.isBlockElement(parentElement)) {
				parentElement = parentElement.parentNode;
			}
			if (/^(td|th)$/i.test(parentElement.nodeName)) {
				var brNode = this.editor.document.createElement('br');
				this.editor.getSelection().insertNode(brNode);
				if (brNode.nextSibling) {
					this.editor.getSelection().selectNodeContents(brNode.nextSibling, true);
				} else {
					this.editor.getSelection().selectNodeContents(brNode, false);
				}
				Event.stopEvent(event);
				return false;
			}
			return true;
		}
	});

	return TableOperations;
});
