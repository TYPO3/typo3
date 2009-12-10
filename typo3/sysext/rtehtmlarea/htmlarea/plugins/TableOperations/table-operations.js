/***************************************************************
*  Copyright notice
*
*  (c) 2002 interactivetools.com, inc. Authored by Mihai Bazon, sponsored by http://www.bloki.com.
*  (c) 2005 Xinha, http://xinha.gogo.co.nz/ for the original toggle borders function.
*  (c) 2004-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 *
 * TYPO3 SVN ID: $Id$
 */
TableOperations = HTMLArea.Plugin.extend({
		
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		
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
			version		: "4.2",
			developer	: "Mihai Bazon & Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Mihai Bazon & Stanislas Rolland",
			sponsor		: this.localize("Technische Universitat Ilmenau") + " & Zapatec Inc.",
			sponsorUrl	: "http://www.tu-ilmenau.de/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the buttons
		 */
		var hideToggleBorders = this.editorConfiguration.hideTableOperationsInToolbar && !(this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.keepInToolbar);
		var buttonList = this.buttonList, buttonId;
		for (var i = 0, n = buttonList.length; i < n; ++i) {
			var button = buttonList[i];
			buttonId = (button[0] === "InsertTable") ? button[0] : ("TO-" + button[0]);
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize((buttonId === "InsertTable") ? "Insert Table" : buttonId),
				action		: "onButtonPress",
				hotKey		: (this.buttonsConfiguration[button[2]] ? this.buttonsConfiguration[button[2]].hotKey : null),
				context		: button[1],
				hide		: ((buttonId == "TO-toggle-borders") ? hideToggleBorders : ((button[0] === "InsertTable") ? false : this.editorConfiguration.hideTableOperationsInToolbar)),
				dialog		: button[3]
			};
			this.registerButton(buttonConfiguration);
		}
		
		return true;
	 },
	 
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList : [
		["InsertTable",		null,				"table", true],
		["toggle-borders",	null, 				"toggleborders", false],
		["table-prop",		"table",			"tableproperties", true],
		["table-restyle",	"table",			"tablerestyle", false],
		["row-prop",		"tr",				"rowproperties", true],
		["row-insert-above",	"tr",				"rowinsertabove", false],
		["row-insert-under",	"tr",				"rowinsertunder", false],
		["row-delete",		"tr",				"rowdelete", false],
		["row-split",		"td,th[rowSpan!=1]",		"rowsplit", false],
		["col-prop",		"td,th",			"columnproperties", true],
		["col-insert-before",	"td,th",			"columninsertbefore", false],
		["col-insert-after",	"td,th",			"columninsertafter", false],
		["col-delete",		"td,th",			"columndelete", false],
		["col-split",		"td,th[colSpan!=1]",		"columnsplit", false],
		["cell-prop",		"td,th",			"cellproperties", true],
		["cell-insert-before",	"td,th",			"cellinsertbefore", false],
		["cell-insert-after",	"td,th",			"cellinsertafter", false],
		["cell-delete",		"td,th",			"celldelete", false],
		["cell-merge",		"tr",				"cellmerge", false],
		["cell-split",		"td,th[colSpan!=1,rowSpan!=1]",	"cellsplit", false]
	],
	
	/*
	 * Retrieve the closest element having the specified nodeName in the list of
	 * ancestors of the current selection/caret.
	 */
	getClosest : function (nodeName) {
		var editor = this.editor;
		var ancestors = editor.getAllAncestors();
		var ret = null;
		nodeName = ("" + nodeName).toLowerCase();
		for (var i=0; i < ancestors.length; ++i) {
			var el = ancestors[i];
			if (el.nodeName.toLowerCase() == nodeName) {
				ret = el;
				break;
			}
		}
		return ret;
	},
	
	/*
	 * Open the table properties dialogue
	 */
	dialogTableProperties : function (buttonId) {
		var tablePropertiesInitFunctRef = this.makeFunctionReference("tablePropertiesInit");
		var insert = (buttonId === "InsertTable");
		var arguments = {
			buttonId	: buttonId,
			title 		: (insert ? "Insert Table" : "Table Properties"),
			initialize	: tablePropertiesInitFunctRef,
			element		: (insert ? null : this.getClosest("table"))
		};
		var dimensions = {
			width	: 860,
			height	: 620
		};
		this.dialog = this.openDialog((insert ? "InsertTable" : "TO-table-prop"), "", "tablePropertiesUpdate", arguments, dimensions);
	},
	
	/*
	 * Initialize the table insertion or table properties dialog
	 */
	tablePropertiesInit : function(dialog) {
		var doc = dialog.document;
		var content = dialog.content;
		var table = dialog.arguments.element;
		this.removedFieldsets = this.buttonsConfiguration[table?"tableproperties":"table"].removeFieldsets ? this.buttonsConfiguration[table?"tableproperties":"table"].removeFieldsets : "";
		this.properties = this.buttonsConfiguration.table.properties;
		this.removedProperties = (this.properties && this.properties.removed) ? this.properties.removed : "";
		TableOperations.buildTitle(doc, content, dialog.arguments.title);
		TableOperations.insertSpace(doc, content);
		if (this.removedFieldsets.indexOf("description") == -1) {
			TableOperations.buildDescriptionFieldset(doc, table, content, "floating");
		}
		if (this.removedFieldsets.indexOf("spacing") == -1) TableOperations.buildSpacingFieldset(doc, table, content);
		TableOperations.insertSpace(doc, content);
		this.buildSizeAndHeadersFieldset(doc, table, content, "floating");
		if (this.removedFieldsets.indexOf("style") == -1 && dialog.editor.config.customSelects.BlockStyle) {
			var blockStyle = dialog.editor.plugins.BlockStyle.instance;
			if (blockStyle && blockStyle.cssLoaded) {
				this.buildStylingFieldset(doc, table, content, null, dialog.arguments.buttonId);
				TableOperations.insertSpace(doc, content);
			}
		}
		this.buildLanguageFieldset(doc, table, content, "floating");
		if (this.removedFieldsets.indexOf("layout") == -1) this.buildLayoutFieldset(doc, table, content, "floating");
		if (this.removedFieldsets.indexOf("alignment") == -1) this.buildAlignmentFieldset(doc, table, content);
		TableOperations.insertSpace(doc, content);
		if (this.removedFieldsets.indexOf("borders") == -1) this.buildBordersFieldset(dialog.dialogWindow, doc, dialog.editor, table, content);
		if (this.removedFieldsets.indexOf("color") == -1) TableOperations.buildColorsFieldset(dialog.dialogWindow, doc, dialog.editor, table, content);
		dialog.addButtons("ok", "cancel");
	},
	
	/*
	 * Insert the table or update the table properties and close the dialogue
	 */
	tablePropertiesUpdate : function(dialog, params) {
		if (this.buttonsConfiguration.table.properties && this.buttonsConfiguration.table.properties.required) {
			if (this.buttonsConfiguration.table.properties.required.indexOf("captionOrSummary") != -1) {
				if (!/\S/.test(params.f_caption) && !/\S/.test(params.f_summary)) {
					dialog.dialogWindow.alert(this.localize("captionOrSummary" + "-required"));
					var el = dialog.document.getElementById("f_caption");
					el.focus();
					return false;
				}
			} else {
				var required = { "f_caption": "caption", "f_summary": "summary" };
				for (var i in required) {
					if (required.hasOwnProperty(i)) {
						var el = dialog.document.getElementById(i);
						if (!el.value && this.buttonsConfiguration.table.properties.required.indexOf(required[i]) != -1) {
							dialog.dialogWindow.alert(this.localize(required[i] + "-required"));
							el.focus();
							return false;
						}
					}
				}
			}
		}
		var doc = dialog.editor._doc;
		if (dialog.buttonId === "InsertTable") {
			var required = { "f_rows": "You must enter a number of rows", "f_cols": "You must enter a number of columns" };
			for (var i in required) {
				if (required.hasOwnProperty(i)) {
					var el = dialog.document.getElementById(i);
					if (!el.value) {
						dialog.dialogWindow.alert(this.localize(required[i]));
						el.focus();
						return false;
					}
				}
			}
			var table = doc.createElement("table");
			var tbody = doc.createElement("tbody");
			table.appendChild(tbody);
			for (var i = params.f_rows; --i >= 0;) {
				var tr = doc.createElement("tr");
				tbody.appendChild(tr);
				for (var j = params.f_cols; --j >= 0;) {
					var td = doc.createElement("td");
					if (HTMLArea.is_gecko) td.innerHTML = "<br />";
					tr.appendChild(td);
				}
			}
		} else {
			var table = dialog.arguments.element;
		}
		table = this.setHeaders(table, params);
		table = this.processStyle(table, params);
		table.removeAttribute("border");
		for (var i in params) {
			if (params.hasOwnProperty(i)) {
				var val = params[i];
				switch (i) {
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
					table.frame = (val != "not set") ? val : "";
					break;
				    case "f_rules":
					if (val != "not set") table.rules = val;
						else table.removeAttribute("rules");
					break;
				    case "f_st_float":
					switch (val) {
					    case "not set":
						HTMLArea._removeClass(table, this.floatRight);
						HTMLArea._removeClass(table, this.floatLeft);
						break;
					    case "right":
						HTMLArea._removeClass(table, this.floatLeft);
						HTMLArea._addClass(table, this.floatRight);
						break;
					    case "left":
						HTMLArea._removeClass(table, this.floatRight);
						HTMLArea._addClass(table, this.floatLeft);
						break;
					}
					break;
				    case "f_st_textAlign":
					if (this.editor.plugins.BlockElements) {
						this.editor.plugins.BlockElements.instance.toggleAlignmentClass(table, this.convertAlignment[val]);
						table.style.textAlign = "";
					}
					break;
				    case "f_class":
				    case "f_class_tbody":
				    case "f_class_thead":
				    case "f_class_tfoot":
					var tpart = table;
					if (i.length > 7) tpart = table.getElementsByTagName(i.substring(8,13))[0];
					if (tpart) {
						this.editor.plugins.BlockStyle.instance.applyClassChange(tpart, val);
					}
					break;
				    case "f_lang":
					this.getPluginInstance("Language").setLanguageAttributes(table, val);
					break;
				    case "f_dir":
					table.dir = (val != "not set") ? val : "";
					break;
				}
			}
		}
		if (dialog.buttonId === "InsertTable") {
			if (HTMLArea.is_gecko) {
				this.editor.insertNodeAtSelection(table);
			} else {
				table.id = "htmlarea_table_insert";
				this.editor.insertNodeAtSelection(table);
				table = this.editor._doc.getElementById(table.id);
				table.removeAttribute("id");
			}
			this.editor.selectNodeContents(table.rows[0].cells[0], true);
			if (this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.setOnTableCreation) {
				this.toggleBorders(true);
			}
		}
		dialog.close();
	},
	
	/*
	 * Open the row/column/cell properties dialogue
	 */
	dialogRowCellProperties : function (cell, column) {
			// retrieve existing values
		if (cell) {
			var element = this.getClosest("td");
			if (!element) var element = this.getClosest("th");
		} else {
			var element = this.getClosest("tr");
		}
		if (element) {
			var rowCellPropertiesInitFunctRef = this.makeFunctionReference("rowCellPropertiesInit");
			var arguments = {
				title 		: (cell ? (column ? "Column Properties" : "Cell Properties") : "Row Properties"),
				initialize	: rowCellPropertiesInitFunctRef,
				element		: element,
				cell		: cell,
				column		: column
			};
			this.dialog = this.openDialog("TO-" + (cell ? (column ? "col-prop" : "cell-prop") :"row-prop"), "", "rowCellPropertiesUpdate", arguments, { width : 660, height : 460 });
		}
	},
	
	/*
	 * Initialize the row/column/cell properties dialogue
	 */
	rowCellPropertiesInit : function(dialog) {
		var doc = dialog.document;
		var content = dialog.content;
		var element = dialog.arguments.element;
		var cell = dialog.arguments.cell;
		var column = dialog.arguments.column;
		this.removedFieldsets = this.buttonsConfiguration[cell?(column?"columnproperties":"cellproperties"):"rowproperties"].removeFieldsets ? this.buttonsConfiguration[cell?(column?"columnproperties":"cellproperties"):"rowproperties"].removeFieldsets : "";
		this.properties = this.buttonsConfiguration[(cell ||column)?"cellproperties":"rowproperties"].properties;
		this.removedProperties = (this.properties && this.properties.removed) ? this.properties.removed : "";
		TableOperations.buildTitle(doc, content, (cell ? (column ? "Column Properties" : "Cell Properties") : "Row Properties"));
		TableOperations.insertSpace(doc, content);
		if (column) {
			if (this.removedFieldsets.indexOf("columntype") == -1) this.buildCellTypeFieldset(doc, element, content, true);
		} else if (cell) {
			if (this.removedFieldsets.indexOf("celltype") == -1) this.buildCellTypeFieldset(doc, element, content, false);
		} else {
			if (this.removedFieldsets.indexOf("rowgroup") == -1) TableOperations.buildRowGroupFieldset(dialog.dialogWindow, doc, dialog.editor, element, content);
		}
		if (this.removedFieldsets.indexOf("style") == -1 && this.editor.config.customSelects.BlockStyle) {
			var blockStyle = this.editor.plugins.BlockStyle.instance;
			if (blockStyle && blockStyle.cssLoaded) {
				this.buildStylingFieldset(doc, element, content);
				TableOperations.insertSpace(doc, content);
			} else {
				TableOperations.insertSpace(doc, content);
			}
		} else {
			TableOperations.insertSpace(doc, content);
		}
		this.buildLanguageFieldset(doc, element, content, "floating");
		if (this.removedFieldsets.indexOf("layout") == -1) this.buildLayoutFieldset(doc, element, content, "floating");
		if (this.removedFieldsets.indexOf("alignment") == -1) this.buildAlignmentFieldset(doc, element, content);
		if (this.removedFieldsets.indexOf("borders") == -1) this.buildBordersFieldset(dialog.dialogWindow, doc, dialog.editor, element, content);
		if (this.removedFieldsets.indexOf("color") == -1) TableOperations.buildColorsFieldset(dialog.dialogWindow, doc, dialog.editor, element, content);
		dialog.addButtons("ok", "cancel");
	},
	
	/*
	 * Update the row/column/cell properties
	 */
	rowCellPropertiesUpdate : function(dialog, params) {
		var element = dialog.arguments.element;
		var cell = dialog.arguments.cell;
		var column = dialog.arguments.column;
		var section = (cell || column) ? element.parentNode.parentNode : element.parentNode;
		var table = section.parentNode;
		var elements = new Array();
		if (column) {
			elements = this.getColumnCells(dialog.arguments.element);
		} else {
			elements.push(dialog.arguments.element);
		}
		for (var k = elements.length; --k >= 0;) {
			var element = elements[k];
			element = this.processStyle(element, params);
			for (var i in params) {
				var val = params[i];
				switch (i) {
				    case "f_cell_type":
					if (val.substring(0,2) != element.nodeName.toLowerCase()) {
						element = this.remapCell(element, val.substring(0,2));
						this.editor.selectNodeContents(element, true);
					}
					if (val.substring(2,10) != element.scope) {
						element.scope = val.substring(2,10);
					}
					break;
				    case "f_rowgroup":
					var nodeName = section.nodeName.toLowerCase();
					if (val != nodeName) {
						var newSection = table.getElementsByTagName(val)[0];
						if (!newSection) var newSection = table.insertBefore(dialog.editor._doc.createElement(val), table.getElementsByTagName("tbody")[0]);
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
					if (this.editor.plugins.BlockElements) {
						this.editor.plugins.BlockElements.instance.toggleAlignmentClass(element, this.convertAlignment[val]);
						element.style.textAlign = "";
					}
					break;
				    case "f_class":
					this.editor.plugins.BlockStyle.instance.applyClassChange(element, val);
					break;
				    case "f_lang":
					this.getPluginInstance("Language").setLanguageAttributes(element, val);
					break;
				    case "f_dir":
					element.dir = (val != "not set") ? val : "";
					break;
				}
			}
		}
		this.reStyleTable(table);
		dialog.close();
	},
	
	/*
	 * This function gets called when the plugin is generated
	 * Set table borders if requested by configuration
	 */
	onGenerate : function() {
		if (this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.setOnRTEOpen) {
			this.toggleBorders(true);
		}
	},
	
	/*
	 * This function gets called when the toolbar is being updated
	 */
	onUpdateToolbar : function() {
		if (this.getEditorMode() === "wysiwyg" && this.editor.isEditable() && this.isButtonInToolbar("TO-toggle-borders")) {
			this.editor._toolbarObjects["TO-toggle-borders"].state("active", HTMLArea._hasClass(this.editor._doc.body, 'htmlarea-showtableborders'));
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
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		
		var mozbr = HTMLArea.is_gecko ? "<br />" : "";
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
				if (!tr) tr = td.parentNode.parentNode.appendChild(editor._doc.createElement("tr"));
				var otd = editor._doc.createElement(nodeName);
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
				var otd = editor._doc.createElement(nodeName);
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
			editor.selectNodeContents(node);
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
			var tr = this.getClosest("tr");
			if (!tr) break;
			var otr = tr.cloneNode(true);
			clearRow(otr);
			otr = tr.parentNode.insertBefore(otr, (/under/.test(buttonId) ? tr.nextSibling : tr));
			this.editor.selectNodeContents(otr.firstChild, true);
			this.reStyleTable(tr.parentNode.parentNode);
			break;
		    case "TO-row-delete":
			var tr = this.getClosest("tr");
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
			var cell = this.getClosest("td");
			if (!cell) var cell = this.getClosest("th");
			if (!cell) break;
			var sel = editor._getSelection();
			if (HTMLArea.is_gecko && !sel.isCollapsed && !HTMLArea.is_safari && !HTMLArea.is_opera) {
				var cells = getSelectedCells(sel);
				for (i = 0; i < cells.length; ++i) splitRow(cells[i]);
			} else {
				splitRow(cell);
			}
			break;
	
			// COLUMNS
		    case "TO-col-insert-before":
		    case "TO-col-insert-after":
			var cell = this.getClosest("td");
			if (!cell) var cell = this.getClosest("th");
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
							var otd = editor._doc.createElement(tr.lastChild.nodeName.toLowerCase());
							otd.innerHTML = mozbr;
							tr.appendChild(otd);
						} else {
							var otd = editor._doc.createElement(ref.nodeName.toLowerCase());
							otd.innerHTML = mozbr;
							tr.insertBefore(otd, ref);
						}
					}
				}
			}
			this.reStyleTable(table);
			break;
		    case "TO-col-split":
			var cell = this.getClosest("td");
			if (!cell) var cell = this.getClosest("th");
			if (!cell) break;
			var sel = editor._getSelection();
			if (HTMLArea.is_gecko && !sel.isCollapsed && !HTMLArea.is_safari && !HTMLArea.is_opera) {
				var cells = getSelectedCells(sel);
				for (i = 0; i < cells.length; ++i) splitCol(cells[i]);
			} else {
				splitCol(cell);
			}
			this.reStyleTable(table);
			break;
		    case "TO-col-delete":
			var cell = this.getClosest("td");
			if (!cell) var cell = this.getClosest("th");
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
			var cell = this.getClosest("td");
			if (!cell) var cell = this.getClosest("th");
			if (!cell) break;
			var sel = editor._getSelection();
			if (HTMLArea.is_gecko && !sel.isCollapsed && !HTMLArea.is_safari && !HTMLArea.is_opera) {
				var cells = getSelectedCells(sel);
				for (i = 0; i < cells.length; ++i) splitCell(cells[i]);
			} else {
				splitCell(cell);
			}
			this.reStyleTable(table);
			break;
		    case "TO-cell-insert-before":
		    case "TO-cell-insert-after":
			var cell = this.getClosest("td");
			if (!cell) var cell = this.getClosest("th");
			if (!cell) break;
			var tr = cell.parentNode;
			var otd = editor._doc.createElement(cell.nodeName.toLowerCase());
			otd.innerHTML = mozbr;
			tr.insertBefore(otd, (/after/.test(buttonId) ? cell.nextSibling : cell));
			this.reStyleTable(tr.parentNode.parentNode);
			break;
		    case "TO-cell-delete":
			var cell = this.getClosest("td");
			if (!cell) var cell = this.getClosest("th");
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
			var sel = editor._getSelection();
			var range, i = 0;
			var rows = new Array();
			for (var k = tableParts.length; --k >= 0;) rows[k] = [];
			var row = null;
			var cells = null;
			if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) {
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
				// Internet Explorer, Safari and Opera
				var cell = this.getClosest("td");
				if (!cell) var cell = this.getClosest("th");
				if (!cell) {
					alert(this.localize("Please click into some cell"));
					break;
				}
				var tr = cell.parentElement;
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
					editor.selectNodeContents(td);
				}
			}
			this.reStyleTable(table);
			break;
			
			// CREATION AND PROPERTIES
		    case "InsertTable":
		    case "TO-table-prop":
			this.dialogTableProperties(buttonId);
			break;
		    case "TO-table-restyle":
			this.reStyleTable(this.getClosest("table"));
			break;
		    case "TO-row-prop":
			this.dialogRowCellProperties(false, false);
			break;
		    case "TO-col-prop":
			this.dialogRowCellProperties(true, true);
			break;
		    case "TO-cell-prop":
			this.dialogRowCellProperties(true, false);
			break;
		    case "TO-toggle-borders":
			this.toggleBorders();
			break;
		    default:
			alert("Button [" + buttonId + "] not yet implemented");
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
		var body = this.editor._doc.body;
		if (!HTMLArea._hasClass(body, 'htmlarea-showtableborders')) {
			HTMLArea._addClass(body,'htmlarea-showtableborders');
		} else if (!forceBorders) {
			HTMLArea._removeClass(body,'htmlarea-showtableborders');
		}
	},
	
	/*
	 * Applies to rows/cells the alternating and counting classes of an alternating or counting style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 *
	 * @return	void
	 */
	reStyleTable : function (table) {
		if (table) {
			if (this.classesUrl && (typeof(HTMLArea.classesAlternating) === "undefined" || typeof(HTMLArea.classesCounting) === "undefined")) {
				this.getJavascriptFile(this.classesUrl);
			}
			var classNames = table.className.trim().split(" ");
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
	},
	
	/*
	 * Removes from rows/cells the alternating classes of an alternating style scheme
	 *
	 * @param	object		table: the table to be re-styled
	 * @param	string		removeClass: the name of the class that identifies the alternating style scheme
	 *
	 * @return	void
	 */
	removeAlternatingClasses : function (table, removeClass) {
		if (table) {
			if (this.classesUrl && typeof(HTMLArea.classesAlternating) === "undefined") {
				this.getJavascriptFile(this.classesUrl);
			}
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
				HTMLArea._removeClass(row, odd);
				HTMLArea._removeClass(row, even);
				// Check if i is even, and apply classes for both possible results
			} else if (odd && even) {
				if ((i % 2) == 0) {
					if (HTMLArea._hasClass(row, even)) {
						HTMLArea._removeClass(row, even);
					}
					HTMLArea._addClass(row, odd);
				} else {
					if (HTMLArea._hasClass(row, odd)) {
						HTMLArea._removeClass(row, odd);
					}
					HTMLArea._addClass(row, even);
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
					if (odd) HTMLArea._removeClass(cell, odd);
					if (even) HTMLArea._removeClass(cell, even);
				} else if (odd && even) {
						// Check if j+startAt is even, and apply classes for both possible results
					if ((j % 2) == 0) {
						if (HTMLArea._hasClass(cell, even)) {
							HTMLArea._removeClass(cell, even);
						}
						HTMLArea._addClass(cell, odd);
					} else{
						if (HTMLArea._hasClass(cell, odd)) {
							HTMLArea._removeClass(cell, odd);
						}
						HTMLArea._addClass(cell, even);
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
	removeCountingClasses : function (table, removeClass) {
		if (table) {
			if (this.classesUrl && typeof(HTMLArea.classesCounting) === "undefined") {
				this.getJavascriptFile(this.classesUrl);
			}
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
					HTMLArea._removeClass(row, rowClassName);
				}
				if (lastRowClassName && i == n-1) {
					HTMLArea._removeClass(row, lastRowClassName);
				}
			} else {
				if (baseClassName) {
					if (HTMLArea._hasClass(row, baseClassName, true)) {
						HTMLArea._removeClass(row, baseClassName, true);
					}
					HTMLArea._addClass(row, rowClassName);
				}
				if (lastRowClassName) {
					if (i == n-1) {
						HTMLArea._addClass(row, lastRowClassName);
					} else if (HTMLArea._hasClass(row, lastRowClassName)) {
						HTMLArea._removeClass(row, lastRowClassName);
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
						HTMLArea._removeClass(cell, columnClassName);
					}
					if (lastColumnClassName && j == n-1) {
							HTMLArea._removeClass(cell, lastColumnClassName);
					}
				} else {
					if (baseClassName) {
						if (HTMLArea._hasClass(cell, baseClassName, true)) {
							HTMLArea._removeClass(cell, baseClassName, true);
						}
						HTMLArea._addClass(cell, columnClassName);
					}
					if (lastColumnClassName) {
						if (j == n-1) {
							HTMLArea._addClass(cell, lastColumnClassName);
						} else if (HTMLArea._hasClass(cell, lastColumnClassName)) {
							HTMLArea._removeClass(cell, lastColumnClassName);
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
	 * @return	object		the modified table
	 */
	setHeaders : function (table, params) {
		var headers = params.f_headers;
		var doc = this.editor._doc;
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
			HTMLArea._removeClass(firstRow, this.useHeaderClass);
		} else {
			if (thead) {
				var rows = thead.rows;
				if (rows.length) {
					for (var i = rows.length; --i >= 0 ;) {
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
			HTMLArea._addClass(firstRow, this.useHeaderClass);
		} else if (headers != "top") {
			var firstRow = tbody.rows[0];
			HTMLArea._removeClass(firstRow, this.useHeaderClass);
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
			for (var i = rows.length; --i >= 0 ;) {
				if (i || rows[i] == firstRow) {
					if (rows[i].cells[0].nodeName.toLowerCase() != "th") {
						var th = this.remapCell(rows[i].cells[0], "th");
						th.scope = "row";
					}
				}
			}
		} else {
			var rows = tbody.rows;
			for (var i = rows.length; --i >= 0 ;) {
				if (rows[i].cells[0].nodeName.toLowerCase() != "td") {
					rows[i].cells[0].scope = "";
					var td = this.remapCell(rows[i].cells[0], "td");
				}
			}
		}
		this.reStyleTable(table);
		return table;
	},
	
	/*
	 * This function remaps the given cell to the specified node name
	 */
	remapCell : function(element, nodeName) {
		var newCell = this.editor.convertNode(element, nodeName);
		var attributes = element.attributes, attributeName, attributeValue;
		for (var i = attributes.length; --i >= 0;) {
			attributeName = attributes.item(i).nodeName;
			attributeValue = element.getAttribute(attributeName);
			if (attributeValue) newCell.setAttribute(attributeName, attributeValue);
		}
			// In IE, the above fails to update the classname and style attributes.
		if (HTMLArea.is_ie) {
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
						HTMLArea._removeClass(newCell, classNames[i]);
					}
				}
			}
		}
		return newCell;
	},
	
	remapRowCells : function (row, toType) {
		var cells = row.cells;
		if (toType === "th") {
			for (var i = cells.length; --i >= 0 ;) {
				if (cells[i].nodeName.toLowerCase() != "th") {
					var th = this.remapCell(cells[i], "th");
					th.scope = "col";
				}
			}
		} else {
			for (var i = cells.length; --i >= 0 ;) {
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
	 * @return	object		the modified element
	 */
	processStyle : function (element, params) {
		var style = element.style;
		if (HTMLArea.is_ie) {
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
					style.backgroundColor = val;
					break;
				    case "f_st_color":
					style.color = val;
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
					style.borderColor = val;
					break;
				    case "f_st_borderCollapse":
					style.borderCollapse = val ? "collapse" : "";
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
		return element;
	},
	
	/*
	 * This function creates a Size and Headers fieldset to be added to the form
	 *
	 * @param	object		doc: the dialog document
	 * @param	object		table: the table being edited
	 * @param	object		content: the content div of the dialog window
	 *
	 * @return	void
	 */
	buildSizeAndHeadersFieldset : function (doc, table, content, fieldsetClass) {
		if (!table || this.removedProperties.indexOf("headers") == -1) {
			var fieldset = doc.createElement("fieldset");
			if (fieldsetClass) fieldset.className = fieldsetClass;
			if (!table) {
				TableOperations.insertLegend(doc, fieldset, "Size and Headers");
				TableOperations.buildInput(doc, fieldset, "f_rows", "Rows:", "Number of rows", "", "5", ((this.properties && this.properties.numberOfRows && this.properties.numberOfRows.defaultValue) ? this.properties.numberOfRows.defaultValue : "2"), "fr");
				TableOperations.insertSpace(doc, fieldset);
				TableOperations.buildInput(doc, fieldset, "f_cols", "Cols:", "Number of columns", "", "5", ((this.properties && this.properties.numberOfColumns && this.properties.numberOfColumns.defaultValue) ? this.properties.numberOfColumns.defaultValue : "4"), "fr");
			} else {
				TableOperations.insertLegend(doc, fieldset, "Headers");
			}
			if (this.removedProperties.indexOf("headers") == -1) {
				var ul = doc.createElement("ul");
				fieldset.appendChild(ul);
				var li = doc.createElement("li");
				ul.appendChild(li);
				if (!table) {
					var selected = (this.properties && this.properties.headers && this.properties.headers.defaultValue) ? this.properties.headers.defaultValue : "top";
				} else {
					var selected = "none";
					var thead = table.getElementsByTagName("thead");
					var tbody = table.getElementsByTagName("tbody");
					if (thead.length && thead[0].rows.length) {
						selected = "top";
					} else if (tbody.length && tbody[0].rows.length) {
						if (HTMLArea._hasClass(tbody[0].rows[0], this.useHeaderClass)) {
							selected = "both";
						} else if (tbody[0].rows[0].cells.length && tbody[0].rows[0].cells[0].nodeName.toLowerCase() == "th") {
							selected = "left";
						}
					}
				}
				var selectHeaders = TableOperations.buildSelectField(doc, li, "f_headers", "Headers:", "fr", "floating", "Table headers", ["No header cells", "Header cells on top", "Header cells on left", "Header cells on top and left"], ["none", "top", "left", "both"],  new RegExp((selected ? selected : "top"), "i"));
				this.removeOptions(selectHeaders, "headers");
			}
			TableOperations.insertSpace(doc, fieldset);
			content.appendChild(fieldset);
		}
	},
	
	buildLayoutFieldset : function(doc, el, content, fieldsetClass) {
		var select, selected;
		var fieldset = doc.createElement("fieldset");
		if (fieldsetClass) fieldset.className = fieldsetClass;
		TableOperations.insertLegend(doc, fieldset, "Layout");
		var f_st_width = el ? TableOperations.getLength(el.style.width) : ((this.properties && this.properties.width && this.properties.width.defaultValue) ? this.properties.width.defaultValue : "");
		var f_st_height = el ? TableOperations.getLength(el.style.height) : ((this.properties && this.properties.height && this.properties.height.defaultValue) ? this.properties.height.defaultValue : "");
		var selectedWidthUnit = el ? (/%/.test(el.style.width) ? '%' : (/px/.test(el.style.width) ? 'px' : 'em')) : ((this.properties && this.properties.widthUnit &&this.properties.widthUnit.defaultValue) ? this.properties.widthUnit.defaultValue : "%");
		var selectedHeightUnit = el ? (/%/.test(el.style.height) ? '%' : (/px/.test(el.style.height) ? 'px' : 'em')) : ((this.properties && this.properties.heightUnit &&this.properties.heightUnit.defaultValue) ? this.properties.heightUnit.defaultValue : "%");
		var nodeName = el ? el.nodeName.toLowerCase() : "table";
		var ul = doc.createElement("ul");
		fieldset.appendChild(ul);
		switch(nodeName) {
			case "table" :
				var widthTitle = "Table width";
				var heightTitle = "Table height";
				break;
			case "tr" :
				var widthTitle = "Row width";
				var heightTitle = "Row height";
				break;
			case "td" :
			case "th" :
				var widthTitle = "Cell width";
				var heightTitle = "Cell height";
		}
		if (this.removedProperties.indexOf("width") == -1) {
			var li = doc.createElement("li");
			ul.appendChild(li);
			TableOperations.buildInput(doc, li, "f_st_width", "Width:", widthTitle, "", "5", f_st_width, "fr");
			select = TableOperations.buildSelectField(doc, li, "f_st_widthUnit", "", "", "", "Width unit", ["percent", "pixels", "em"], ["%", "px", "em"], new RegExp((selectedWidthUnit ? selectedWidthUnit : "%"), "i"));
			this.removeOptions(select, "widthUnit");
		}
		if (this.removedProperties.indexOf("height") == -1) {
			var li = doc.createElement("li");
			ul.appendChild(li);
			TableOperations.buildInput(doc, li, "f_st_height", "Height:", heightTitle, "", "5", f_st_height, "fr");
			select = TableOperations.buildSelectField(doc, li, "f_st_heightUnit", "", "", "", "Height unit", ["percent", "pixels", "em"], ["%", "px", "em"], new RegExp((selectedHeightUnit ? selectedHeightUnit : "%"), "i"));
			this.removeOptions(select, "heightUnit");
		}
		if (nodeName == "table" && this.removedProperties.indexOf("float") == -1) {
			selected = el ? (HTMLArea._hasClass(el, this.floatLeft) ? "left" : (HTMLArea._hasClass(el, this.floatRight) ? "right" : "not set")) : this.floatDefault;
			select = TableOperations.buildSelectField(doc, li, "f_st_float", "Float:", "", "", "Specifies where the table should float", ["Not set", "Left", "Right"], ["not set", "left", "right"], new RegExp((selected ? selected : "not set"), "i"));
			this.removeOptions(select, "float");
		}
		content.appendChild(fieldset);
	},
	
	setStyleOptions : function (doc, dropDown, el, nodeName, defaultClass) {
		if (!dropDown) return false;
		if (this.editor.config.customSelects.BlockStyle) {
			var blockStyle = this.editor.plugins.BlockStyle.instance;
			if (!blockStyle || !blockStyle.cssLoaded) return false;
			if (defaultClass) {
				var classNames = new Array();
				classNames.push(defaultClass);
			} else {
				var classNames = blockStyle.getClassNames(el);
			}
			blockStyle.buildDropDownOptions(dropDown, nodeName);
			blockStyle.setSelectedOption(dropDown, classNames, "noUnknown", defaultClass);
		}
	},
	
	buildStylingFieldset : function (doc, el, content, fieldsetClass, buttonId) {
		var nodeName = el ? el.nodeName.toLowerCase() : "table";
		var table = (nodeName == "table");
		var fieldset = doc.createElement("fieldset");
		if (fieldsetClass) fieldset.className = fieldsetClass;
		TableOperations.insertLegend(doc, fieldset, "CSS Style");
		TableOperations.insertSpace(doc, fieldset);
		var ul = doc.createElement("ul");
		ul.className = "floating";
		fieldset.appendChild(ul);
		var li = doc.createElement("li");
		ul.appendChild(li);
		var select = TableOperations.buildSelectField(doc, li, "f_class", (table ? "Table class:" : "Class:"), "fr", "", (table ? "Table class selector" : "Class selector"), new Array("undefined"), new Array("none"), new RegExp("none", "i"), "", false);
		this.setStyleOptions(doc, select, el, nodeName, (buttonId === "InsertTable") ? this.defaultClass : null);
		if (el && table) {
			var tbody = el.getElementsByTagName("tbody")[0];
			if (tbody) {
				var li = doc.createElement("li");
				ul.appendChild(li);
				var select = TableOperations.buildSelectField(doc, li, "f_class_tbody", "Table body class:", "fr", "", "Table body class selector", new Array("undefined"), new Array("none"), new RegExp("none", "i"), "", false);
				this.setStyleOptions(doc, select, tbody, "tbody");
			}
			var thead = el.getElementsByTagName("thead")[0];
			if (thead) {
				var li = doc.createElement("li");
				ul.appendChild(li);
				var select = TableOperations.buildSelectField(doc, li, "f_class_thead", "Table header class:", "fr", "", "Table header class selector", new Array("undefined"), new Array("none"), new RegExp("none", "i"), "", false);
				this.setStyleOptions(doc, select, thead, "thead");
			}
			var tfoot = el.getElementsByTagName("tfoot")[0];
			if (tfoot) {
				var li = doc.createElement("li");
				ul.appendChild(li);
				var select = TableOperations.buildSelectField(doc, li, "f_class_tfoot", "Table footer class:", "fr", "", "Table footer class selector", new Array("undefined"), new Array("none"), new RegExp("none", "i"), "", false);
				this.setStyleOptions(doc, select, tfoot, "tfoot");
			}
		}
		TableOperations.insertSpace(doc, fieldset);
		content.appendChild(fieldset);
	},
	
	buildLanguageFieldset : function (doc, el, content, fieldsetClass) {
		if (this.removedFieldsets.indexOf("language") == -1 && (this.removedProperties.indexOf("language") == -1 || this.removedProperties.indexOf("direction") == -1) && this.getPluginInstance("Language") && (this.isButtonInToolbar("Language") || this.isButtonInToolbar("LeftToRight") || this.isButtonInToolbar("RightToLeft"))) {
			var languageObject = this.getPluginInstance("Language");
			var fieldset = doc.createElement("fieldset");
			if (fieldsetClass) {
				fieldset.className = fieldsetClass;
			}
			TableOperations.insertLegend(doc, fieldset, "Language");
			var ul = doc.createElement("ul");
			fieldset.appendChild(ul);
			if (this.removedProperties.indexOf("language") == -1 && this.isButtonInToolbar("Language")) {
				var languageOptions = this.getDropDownConfiguration("Language").options;
				var select,
					selected = "",
					options = new Array(),
					values = new Array();
				for (var option in languageOptions) {
					if (languageOptions.hasOwnProperty(option)) {
						options.push(option);
						values.push(languageOptions[option]);
					}
				}
				selected = el ? languageObject.getLanguageAttribute(el) : "none";
				if (selected != "none") {
					options[0] = languageObject.localize("Remove language mark");
				}
				(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
				var li = doc.createElement("li");
				ul.appendChild(li);
				select = TableOperations.buildSelectField(doc, li, "f_lang", "Language:", "fr", "", "Language", options, values, new RegExp((selected ? selected : "none"), "i"));
			}
			if (this.removedProperties.indexOf("direction") == -1 && (this.isButtonInToolbar("LeftToRight") || this.isButtonInToolbar("RightToLeft"))) {
				var li = doc.createElement("li");
				ul.appendChild(li);
				selected = el ? el.dir : "";
				(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
				select = TableOperations.buildSelectField(doc, li, "f_dir", "Text direction:", "fr", "", "Text direction", ["Not set", "Right to left", "Left to right"], ["not set", "rtl", "ltr"], new RegExp((selected ? selected : "not set"), "i"));
			}
			content.appendChild(fieldset);
		}
	},
	
	buildCellTypeFieldset : function (doc, el, content, column, fieldsetClass) {
		var fieldset = doc.createElement("fieldset");
		if (fieldsetClass) fieldset.className = fieldsetClass;
		TableOperations.insertLegend(doc, fieldset, column ? "Type of cells" : "Cell Type and Scope");
		TableOperations.insertSpace(doc, fieldset);
		var ul = doc.createElement("ul");
		fieldset.appendChild(ul);
		var li = doc.createElement("li");
		ul.appendChild(li);
		if (column) {
			var selectType = TableOperations.buildSelectField(doc, li, "f_cell_type", "Type of cells of the column", "fl", "", "Specifies the type of cells", ["Data cells", "Headers for rows", "Headers for row groups"], ["td", "throw", "throwgroup"], new RegExp(el.nodeName.toLowerCase()+el.scope.toLowerCase()+"$", "i"));
		} else {
			var selectType = TableOperations.buildSelectField(doc, li, "f_cell_type", "Type of cell", "fr", "", "Specifies the type of cell", ["Normal", "Header for column", "Header for row", "Header for row group"], ["td", "thcol", "throw", "throwgroup"], new RegExp(el.nodeName.toLowerCase()+el.scope.toLowerCase()+"$", "i"));
		}
		var self = this;
		selectType.onchange = function() { self.setStyleOptions(doc, doc.getElementById("f_class"), el, this.value.substring(0,2)); };
		TableOperations.insertSpace(doc, fieldset);
		content.appendChild(fieldset);
	},
	
	buildAlignmentFieldset : function (doc, el, content, fieldsetClass) {
		var select;
		var nodeName = el ? el.nodeName.toLowerCase() : "table";
		var fieldset = doc.createElement("fieldset");
		if (fieldsetClass) fieldset.className = fieldsetClass;
		TableOperations.insertLegend(doc, fieldset, "Alignment");
		var options = ["Not set", "Left", "Center", "Right", "Justify"];
		var values = ["not set", "left", "center", "right", "justify"];
		var selected = "";
		if (el && this.editor.plugins.BlockElements) {
			var blockElements = this.editor.plugins.BlockElements.instance;
			for (var value in this.convertAlignment) {
				if (this.convertAlignment.hasOwnProperty(value) && HTMLArea._hasClass(el, blockElements.useClass[this.convertAlignment[value]])) {
					selected = value;
					break;
				}
			}
		} else {
			selected = el ? el.style.verticalAlign : "";
		}
		(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
		var ul = doc.createElement("ul");
		fieldset.appendChild(ul);
		var li = doc.createElement("li");
		ul.appendChild(li);
		select = TableOperations.buildSelectField(doc, li, "f_st_textAlign", "Text alignment:", "fr", "", "Horizontal alignment of text within cell", options, values, new RegExp((selected ? selected : "not set"), "i"));
		
		var li = doc.createElement("li");
		ul.appendChild(li);
		selected = el ? el.style.verticalAlign : "";
		(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
		select = TableOperations.buildSelectField(doc, li, "f_st_vertAlign", "Vertical alignment:", "fr", "", "Vertical alignment of content within cell", ["Not set", "Top", "Middle", "Bottom", "Baseline"], ["not set", "top", "middle", "bottom", "baseline"], new RegExp((selected ? selected : "not set"), "i"));
		content.appendChild(fieldset);
	},
	
	buildBordersFieldset : function (w, doc, editor, el, content, fieldsetClass) {
		var nodeName = el ? el.nodeName.toLowerCase() : "table";
		var select;
		var selected;
		var borderFields = [];
		function setBorderFieldsVisibility(value) {
			for (var i = 0; i < borderFields.length; ++i) {
				var borderFieldElement = borderFields[i];
				borderFieldElement.style.visibility = value ? "hidden" : "visible";
				if (!value && (borderFieldElement.nodeName.toLowerCase() == "input")) {
					borderFieldElement.focus();
					borderFieldElement.select();
				}
			}
		};
		var fieldset = doc.createElement("fieldset");
		fieldset.className = fieldsetClass;
		TableOperations.insertLegend(doc, fieldset, "Frame and borders");
		TableOperations.insertSpace(doc, fieldset);
			// Gecko reports "solid solid solid solid" for "border-style: solid".
			// That is, "top right bottom left" -- we only consider the first value.
		var f_st_borderWidth = el ? TableOperations.getLength(el.style.borderWidth) : ((this.properties && this.properties.borderWidth && this.properties.borderWidth.defaultValue) ? this.properties.borderWidth.defaultValue : "");
		selected = el ? el.style.borderStyle : ((this.properties && this.properties.borderWidth) ? ((this.properties.borderStyle && this.properties.borderStyle.defaultValue) ? this.properties.borderStyle.defaultValue : "solid") : "");
		(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
		selectBorderStyle = TableOperations.buildSelectField(doc, fieldset, "f_st_borderStyle", "Border style:", "fr", "floating", "Border style", ["Not set", "No border", "Dotted", "Dashed", "Solid", "Double", "Groove", "Ridge", "Inset", "Outset"], ["not set", "none", "dotted", "dashed", "solid", "double", "groove", "ridge", "inset", "outset"], new RegExp((selected ? selected : "not set"), "i"));
		selectBorderStyle.onchange = function() { setBorderFieldsVisibility(this.value == "none"); };
		this.removeOptions(selectBorderStyle, "borderStyle");
		TableOperations.buildInput(doc, fieldset, "f_st_borderWidth", "Border width:", "Border width", "pixels", "5", f_st_borderWidth, "fr", "floating", "postlabel", borderFields);
		TableOperations.insertSpace(doc, fieldset, borderFields);
		
		if (nodeName == "table") {
			TableOperations.buildColorField(w, doc, editor, fieldset, "", "Color:", "fr", "colorButton", (el ? el.style.borderColor : ""), "borderColor", borderFields);
			var label = doc.createElement("label");
			label.className = "fl-borderCollapse";
			label.htmlFor = "f_st_borderCollapse";
			label.innerHTML = "Collapsed borders";
			fieldset.appendChild(label);
			borderFields.push(label);
			var input = doc.createElement("input");
			input.className = "checkbox";
			input.type = "checkbox";
			input.name = "f_st_borderCollapse";
			input.id = "f_st_borderCollapse";
			input.defaultChecked = el ? /collapse/i.test(el.style.borderCollapse) : false;
			input.checked = input.defaultChecked;
			fieldset.appendChild(input);
			borderFields.push(input);
			TableOperations.insertSpace(doc, fieldset, borderFields);
			select = TableOperations.buildSelectField(doc, fieldset, "f_frames", "Frames:", "fr", "floating", "Specifies which sides should have a border", ["Not set", "No sides", "The top side only", "The bottom side only", "The top and bottom sides only", "The right and left sides only", "The left-hand side only", "The right-hand side only", "All four sides"], ["not set", "void", "above", "below", "hsides", "vsides", "lhs", "rhs", "box"], new RegExp(((el && el.frame) ? el.frame : "not set"), "i"), borderFields);
			TableOperations.insertSpace(doc, fieldset, borderFields);
			select = TableOperations.buildSelectField(doc, fieldset, "f_rules", "Rules:", "fr", "floating", "Specifies where rules should be displayed", ["Not set", "No rules", "Rules will appear between rows only", "Rules will appear between columns only", "Rules will appear between all rows and columns"], ["not set", "none", "rows", "cols", "all"], new RegExp(((el && el.rules) ? el.rules : "not set"), "i"), borderFields);
		} else {
			TableOperations.insertSpace(doc, fieldset, borderFields);
			TableOperations.buildColorField(w, doc, editor, fieldset, "", "Color:", "fr", "colorButton", (el ? el.style.borderColor : ""), "borderColor", borderFields);
		}
		setBorderFieldsVisibility(selectBorderStyle.value == "none");
		TableOperations.insertSpace(doc, fieldset);
		content.appendChild(fieldset);
	},
	
	removeOptions : function(select, property) {
		if (this.properties && this.properties[property] && this.properties[property].removeItems) {
			for (var i = select.options.length; --i >= 0;) {
				if (this.properties[property].removeItems.indexOf(select.options[i].value) != -1) {
					if (select.options[i].value != select.value) {
						select.options[i] = null;
					}
				}
			}
		}
	},
	
	/*
	 * This function gets called by the main editor event handler when a key was pressed.
	 * It will process the enter key for IE and Opera when buttons.table.disableEnterParagraphs is set in the editor configuration
	 */
	onKeyPress : function (ev) {
		if ((HTMLArea.is_ie || HTMLArea.is_opera) && ev.keyCode == 13 && !ev.shiftKey && this.disableEnterParagraphs) {
			var selection = this.editor._getSelection();
			var range = this.editor._createRange(selection);
			var parentElement = this.editor.getParentElement(selection, range);
			while (parentElement && !HTMLArea.isBlockElement(parentElement)) {
				parentElement = parentElement.parentNode;
			}
			if (/^(td|th)$/i.test(parentElement.nodeName)) {
				if (HTMLArea.is_ie) {
					range.pasteHTML("<br />");
				} else {
					var brNode = this.editor._doc.createElement("br");
					this.editor.insertNodeAtSelection(brNode);
					this.editor.selectNode(brNode, false);
				}
				return false;
			}
		}
		return true;
	}
});

TableOperations.getLength = function(value) {
	var len = parseInt(value);
	if (isNaN(len)) len = "";
	return len;
};

// Returns an HTML element for a widget that allows color selection.  That is,
// a button that contains the given color, if any, and when pressed will popup
// the sooner-or-later-to-be-rewritten select_color.html dialog allowing user
// to select some color.  If a color is selected, an input field with the name
// "f_st_"+name will be updated with the color value in #123456 format.
TableOperations.createColorButton = function(w, doc, editor, color, name) {
	if (!color) {
		color = "";
	} else if (!/#/.test(color)) {
		color = HTMLArea._colorToRgb(color);
	}

	var df = doc.createElement("span");
 	var field = doc.createElement("input");
	field.type = "hidden";
	df.appendChild(field);
 	field.name = "f_st_" + name;
 	field.id = "f_st_" + name;
	field.value = color;
	var button = doc.createElement("span");
	button.className = "buttonColor";
	df.appendChild(button);
	var span = doc.createElement("span");
	span.className = "chooser";
	span.style.backgroundColor = color;
	var space = doc.createTextNode("\xA0");
	span.appendChild(space);
	button.appendChild(span);
	button.onmouseover = function() { if (!this.disabled) this.className += " buttonColor-hilite"; };
	button.onmouseout = function() { if (!this.disabled) this.className = "buttonColor"; };
	span.onclick = function() {
		if (this.parentNode.disabled) return false;
		var colorPlugin = editor.plugins.TYPO3Color;
		if (colorPlugin) {
			colorPlugin.instance.dialogSelectColor("color", span, field, w);
		} else {
			colorPlugin = editor.plugins.DefaultColor;
			if (colorPlugin) {
				w.insertColor = function (color) {
					if (color) {
						span.style.backgroundColor = color;
						field.value = color;
					}
				};
				colorPlugin.instance.onButtonPress(editor, "TableOperations");
			}
		}
	};
	var span2 = doc.createElement("span");
	span2.innerHTML = "&#x00d7;";
	span2.className = "nocolor";
	span2.title = "Unset color";
	button.appendChild(span2);
	span2.onmouseover = function() { if (!this.parentNode.disabled) this.className += " nocolor-hilite"; };
	span2.onmouseout = function() { if (!this.parentNode.disabled) this.className = "nocolor"; };
	span2.onclick = function() {
		span.style.backgroundColor = "";
		field.value = "";
	};
	return df;
};
TableOperations.buildTitle = function(doc, content, title) {
	var div = doc.createElement("div");
	div.className = "title";
	div.innerHTML = title;
	content.appendChild(div);
	doc.title = title;
};
TableOperations.buildDescriptionFieldset = function(doc, el, content, fieldsetClass) {
	var fieldset = doc.createElement("fieldset");
	if (fieldsetClass) fieldset.className = fieldsetClass;
	TableOperations.insertLegend(doc, fieldset, "Description");
	TableOperations.insertSpace(doc, fieldset);
	var f_caption = "";
	if (el) {
		var capel = el.getElementsByTagName("caption")[0];
		if (capel) f_caption = capel.innerHTML;
	}
	TableOperations.buildInput(doc, fieldset, "f_caption", "Caption:", "Description of the nature of the table", "", "", f_caption, "fr", "value", "");
	TableOperations.insertSpace(doc, fieldset);
	TableOperations.buildInput(doc, fieldset, "f_summary", "Summary:", "Summary of the table purpose and structure", "", "", (el ? el.summary : ""), "fr", "value", "");
	TableOperations.insertSpace(doc, fieldset);
	content.appendChild(fieldset);
};
TableOperations.buildRowGroupFieldset = function(w, doc, editor, el, content, fieldsetClass) {
	var fieldset = doc.createElement("fieldset");
	if (fieldsetClass) fieldset.className = fieldsetClass;
	TableOperations.insertLegend(doc, fieldset, "Row group");
	TableOperations.insertSpace(doc, fieldset);
	TableOperations.insertSpace(doc, fieldset);
	selected = el.parentNode.nodeName.toLowerCase();
	var selectScope = TableOperations.buildSelectField(doc, fieldset, "f_rowgroup", "Row group:", "fr", "floating", "Table section", ["Table body", "Table header", "Table footer"], ["tbody", "thead", "tfoot"], new RegExp((selected ? selected : "tbody"), "i"));
	function displayCheckbox(current, value) {
		if (current !== "thead" && value === "thead") {
			label1.style.display = "inline";
			label2.style.display = "none";
			input.style.display = "inline";
			input.checked = true;
		} else if (current === "thead" && value !== "thead") {
			label1.style.display = "none";
			label2.style.display = "inline";
			input.style.display = "inline";
			input.checked = true;
		} else {
			label1.style.display = "none";
			label2.style.display = "none";
			input.style.display = "none";
			input.checked = false;
		}
	}
	selectScope.onchange = function() { displayCheckbox(selected, this.value); };
	var label1 = doc.createElement("label");
	label1.className = "fl";
	label1.htmlFor = "f_convertCells";
	label1.innerHTML = "Make cells header cells";
	label1.style.display = "none";
	fieldset.appendChild(label1);
	var label2 = doc.createElement("label");
	label2.className = "fl";
	label2.htmlFor = "f_convertCells";
	label2.innerHTML = "Make cells data cells";
	label2.style.display = "none";
	fieldset.appendChild(label2);
	var input = doc.createElement("input");
	input.className = "checkbox";
	input.type = "checkbox";
	input.name = "f_convertCells";
	input.id = "f_convertCells";
	input.checked = false;
	input.style.display = "none";
	fieldset.appendChild(input);
	TableOperations.insertSpace(doc, fieldset);
	content.appendChild(fieldset);
};
TableOperations.buildSpacingFieldset = function(doc, el, content) {
	var fieldset = doc.createElement("fieldset");
	TableOperations.insertLegend(doc, fieldset, "Spacing and padding");
	TableOperations.buildInput(doc, fieldset, "f_spacing", "Cell spacing:", "Space between adjacent cells", "pixels", "5", (el ? el.cellSpacing : ""), "fr", "", "postlabel");
	TableOperations.insertSpace(doc, fieldset);
	TableOperations.buildInput(doc, fieldset, "f_padding", "Cell padding:", "Space between content and border in cell", "pixels", "5", (el ? el.cellPadding : ""), "fr", "", "postlabel");
	content.appendChild(fieldset);
};
TableOperations.buildColorsFieldset = function(w, doc, editor, el, content) {
	var fieldset = doc.createElement("fieldset");
	TableOperations.insertLegend(doc, fieldset, "Background and colors");
	var ul = doc.createElement("ul");
	fieldset.appendChild(ul);
	var li = doc.createElement("li");
	ul.appendChild(li);
	TableOperations.buildColorField(w, doc, editor, li, "", "FG Color:", "fr", "colorButtonNoFloat", (el ? el.style.color : ""), "color");
	var li = doc.createElement("li");
	ul.appendChild(li);
	TableOperations.buildColorField(w, doc, editor, li, "", "Background:", "fr", "colorButtonNoFloat", (el ? el.style.backgroundColor : ""), "backgroundColor");
	var url;
	if (el && el.style.backgroundImage.match(/url\(\s*(.*?)\s*\)/)) url = RegExp.$1;
	TableOperations.buildInput(doc, li, "f_st_backgroundImage", "Image URL:", "URL of the background image", "", "", url, "", "shorter-value");
	content.appendChild(fieldset);
};
TableOperations.insertLegend = function(doc, fieldset, legend) {
	var legendNode = doc.createElement("legend");
	legendNode.innerHTML = legend;
	fieldset.appendChild(legendNode);
};
TableOperations.insertSpace =	function(doc,fieldset,fields) {
	var space = doc.createElement("div");
	space.className = "space";
	fieldset.appendChild(space);
	if(fields) fields.push(space);
};
TableOperations.buildInput = function(doc, fieldset,fieldName,fieldLabel,fieldTitle,postLabel,fieldSize,fieldValue,labelClass,inputClass,postClass,fields) {
	var label;
		// Field label
	if(fieldLabel) {
		label = doc.createElement("label");
		if(labelClass) label.className = labelClass;
		label.innerHTML = fieldLabel;
		label.htmlFor = fieldName;
		fieldset.appendChild(label);
		if(fields) fields.push(label);
	}
		// Input field
	var input = doc.createElement("input");
	input.type = "text";
	input.id = fieldName;
	input.name =  fieldName;
	if(inputClass) input.className = inputClass;
	if(fieldTitle) input.title = fieldTitle;
	if(fieldSize) input.size = fieldSize;
	if(fieldValue) input.value = fieldValue;
	fieldset.appendChild(input);
	if(fields) fields.push(input);
		// Field post label
	if(postLabel) {
		label = doc.createElement("span");
		if(postClass) label.className = postClass;
		label.innerHTML = postLabel;
		fieldset.appendChild(label);
		if(fields) fields.push(label);
	}
	return input;
};
TableOperations.buildSelectField = function(doc, fieldset,fieldName,fieldLabel,labelClass,selectClass,fieldTitle,options,values,selected,fields,translateOptions) {
	if(typeof(translateOptions) == "undefined") var translateOptions = true;
		// Field Label
	if(fieldLabel) {
		var label = doc.createElement("label");
		if(labelClass) label.className = labelClass;
		label.innerHTML = fieldLabel;
		label.htmlFor = fieldName;
		fieldset.appendChild(label);
		if(fields) fields.push(label);
	}
		// Text Alignment Select Box
	var select = doc.createElement("select");
	if (selectClass) select.className = selectClass;
	select.id = fieldName;
	select.name =  fieldName;
	select.title= fieldTitle;
	select.selectedIndex = 0;
	var option;
	for (var i = 0; i < options.length; ++i) {
		option = doc.createElement("option");
		select.appendChild(option);
		option.value = values[i];
		option.innerHTML = options[i];
		option.selected = selected.test(option.value);
	}
	if (select.options.length>1) select.disabled = false;
		else select.disabled = true;
	fieldset.appendChild(select);
	if(fields) fields.push(select);
	return select;
};
TableOperations.buildColorField = function(w, doc, editor, fieldset,fieldName,fieldLabel,labelClass, buttonClass, fieldValue,fieldType,fields) {
		// Field Label
	if(fieldLabel) {
		var label = doc.createElement("label");
		if(labelClass) label.className = labelClass;
		label.innerHTML = fieldLabel;
		fieldset.appendChild(label);
		if(fields) fields.push(label);
	}
	var colorButton = TableOperations.createColorButton(w, doc, editor, fieldValue, fieldType);
	colorButton.className = buttonClass;
	fieldset.appendChild(colorButton);
	if(fields) fields.push(colorButton);
};
