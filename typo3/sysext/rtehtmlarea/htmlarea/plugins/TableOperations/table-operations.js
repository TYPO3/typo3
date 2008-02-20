/***************************************************************
*  Copyright notice
*
*  (c) 2002 interactivetools.com, inc. Authored by Mihai Bazon, sponsored by http://www.bloki.com.
*  (c) 2005 Xinha, http://xinha.gogo.co.nz/ for the original toggle borders function.
*  (c) 2004-2008 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
		
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "3.7",
			developer	: "Mihai Bazon & Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Mihai Bazon & Stanislas Rolland",
			sponsor		: "Zapatec Inc. & Fructifor Inc.",
			sponsorUrl	: "http://www.fructifor.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the buttons
		 */
		var hideToggleBorders = this.editorConfiguration.hideTableOperationsInToolbar && !(this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.keepInToolbar);
		var buttonList = this.buttonList;
		for (var i = 0, n = buttonList.length; i < n; ++i) {
			var button = buttonList[i];
			buttonId = (button[0] === "InsertTable") ? button[0] : ("TO-" + button[0]);
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize((buttonId === "InsertTable") ? "Insert Table" : buttonId),
				action		: "onButtonPress",
				hotKey		: (this.buttonsConfiguration[button[2]] ? this.buttonsConfiguration[button[2]].hotKey : null),
				context		: button[1],
				hide		: ((buttonId == "TO-toggle-borders") ? hideToggleBorders : ((button[0] === "InsertTable") ? false : this.editorConfiguration.hideTableOperationsInToolbar))
			};
			this.registerButton(buttonConfiguration);
		}
		
		return true;
	 },
	 
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList : [
		["InsertTable",		null,				"table"],
		["toggle-borders",	null, 				"toggleborders"],
		["table-prop",		"table",			"tableproperties"],
		["row-prop",		"tr",				"rowproperties"],
		["row-insert-above",	"tr",				"rowinsertabove"],
		["row-insert-under",	"tr",				"rowinsertunder"],
		["row-delete",		"tr",				"rowdelete"],
		["row-split",		"td,th[rowSpan!=1]",		"rowsplit"],
		["col-insert-before",	"td,th",			"columninsertbefore"],
		["col-insert-after",	"td,th",			"columninsertafter"],
		["col-delete",		"td,th",			"columndelete"],
		["col-split",		"td,th[colSpan!=1]",		"columnsplit"],
		["cell-prop",		"td,th",			"cellproperties"],
		["cell-insert-before",	"td,th",			"cellinsertbefore"],
		["cell-insert-after",	"td,th",			"cellinsertafter"],
		["cell-delete",		"td,th",			"celldelete"],
		["cell-merge",		"tr",				"cellmerge"],
		["cell-split",		"td,th[colSpan!=1,rowSpan!=1]",	"cellsplit"]
	],
	
	/************************
	 * UTILITIES
	 ************************/
	/*
	 * Retrieve the closest element having the specified tagName in the list of
	 * ancestors of the current selection/caret.
	 */
	getClosest : function (tagName) {
		var editor = this.editor;
		var ancestors = editor.getAllAncestors();
		var ret = null;
		tagName = ("" + tagName).toLowerCase();
		for (var i=0; i < ancestors.length; ++i) {
			var el = ancestors[i];
			if (el.tagName.toLowerCase() == tagName) {
				ret = el;
				break;
			}
		}
		return ret;
	},
	
	/*
	 * Open the table properties dialog.
	 */
	dialogTableProperties : function () {
			// retrieve existing values
		var table = this.getClosest("table");
		var tablePropertiesInitFunctRef = TableOperations.tablePropertiesInit(table);
		var tablePropertiesUpdateFunctRef = TableOperations.tablePropertiesUpdate(table);
		var dialog = new PopupWin(this.editor, this.localize("Table Properties"), tablePropertiesUpdateFunctRef, tablePropertiesInitFunctRef, 570, 600);
	},
	
	/*
	 * Open the row/cell properties dialog.
	 * This function requires the file PopupWin to be loaded.
	 */
	dialogRowCellProperties : function (cell) {
			// retrieve existing values
		if (cell) {
			var element = this.getClosest("td");
			if (!element) var element = this.getClosest("th");
		} else {
			var element = this.getClosest("tr");
		}
		if(element) {
			var rowCellPropertiesInitFunctRef = TableOperations.rowCellPropertiesInit(element, cell);
			var rowCellPropertiesUpdateFunctRef = TableOperations.rowCellPropertiesUpdate(element);
			var dialog = new PopupWin(this.editor, this.localize(cell ? "Cell Properties" : "Row Properties"), rowCellPropertiesUpdateFunctRef, rowCellPropertiesInitFunctRef, 700, 425);
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
			var tagName = td.tagName.toLowerCase();
			td.rowSpan = 1;
			var tr = td.parentNode;
			var sectionRowIndex = tr.sectionRowIndex;
			var rows = tr.parentNode.rows;
			var index = td.cellIndex;
			while (--n > 0) {
				tr = rows[++sectionRowIndex];
					// Last row
				if (!tr) tr = td.parentNode.parentNode.appendChild(editor._doc.createElement("tr"));
				var otd = editor._doc.createElement(tagName);
				otd.colSpan = colSpan;
				otd.innerHTML = mozbr;
				tr.insertBefore(otd, tr.cells[index]);
			}
		};
	
		function splitCol(td) {
			var nc = parseInt("" + td.colSpan);
			var tagName = td.tagName.toLowerCase();
			td.colSpan = 1;
			var tr = td.parentNode;
			var ref = td.nextSibling;
			while (--nc > 0) {
				var otd = editor._doc.createElement(tagName);
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
					while (!/^(td|th|body)$/.test(cell.tagName.toLowerCase())) cell = cell.parentNode;
					if (/^(td|th)$/.test(cell.tagName.toLowerCase())) cells.push(cell);
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
			editor.forceRedraw();
			editor.focusEditor();
			editor.updateToolbar();
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
			editor.forceRedraw();
			editor.updateToolbar();
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
							var otd = editor._doc.createElement(tr.lastChild.tagName.toLowerCase());
							otd.innerHTML = mozbr;
							tr.appendChild(otd);
						} else {
							var otd = editor._doc.createElement(ref.tagName.toLowerCase());
							otd.innerHTML = mozbr;
							tr.insertBefore(otd, ref);
						}
					}
				}
			}
			editor.focusEditor();
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
			editor.forceRedraw();
			editor.updateToolbar();
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
			editor.forceRedraw();
			editor.focusEditor();
			editor.updateToolbar();
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
			editor.forceRedraw();
			editor.updateToolbar();
			break;
		    case "TO-cell-insert-before":
		    case "TO-cell-insert-after":
			var cell = this.getClosest("td");
			if (!cell) var cell = this.getClosest("th");
			if (!cell) break;
			var tr = cell.parentNode;
			var otd = editor._doc.createElement(cell.tagName.toLowerCase());
			otd.innerHTML = mozbr;
			tr.insertBefore(otd, (/after/.test(buttonId) ? cell.nextSibling : cell));
			editor.forceRedraw();
			editor.focusEditor();
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
			editor.forceRedraw();
			editor.focusEditor();
			editor.updateToolbar();
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
				rows[tablePartsIndex[row.parentNode.nodeName.toLowerCase()]].push(cells);
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
			break;
				// CREATION AND PROPERTIES
			case "InsertTable":
				this.dialogInsertTable();
				break;
			case "TO-table-prop":
				this.dialogTableProperties();
				break;
			case "TO-row-prop":
				this.dialogRowCellProperties(false);
				break;
			case "TO-cell-prop":
				this.dialogRowCellProperties(true);
				break;
			case "TO-toggle-borders":
				this.toggleBorders();
				break;
			default:
				alert("Button [" + buttonId + "] not yet implemented");
		}
	},
	
	/*
	 * Open insert table request
	 */
	dialogInsertTable : function () {
		this.dialog = this.openDialog("InsertTable", this.makeUrlFromPopupName("insert_table"), "insertTable", null, {width:520, height:230});
		return false;
	},
	
	/*
	* Get the insert table action function
	*/
	insertTable : function(param) {
		var editor = this.editor;
		if (!param) return false;
		var doc = editor._doc;
		var table = doc.createElement("table");
		for (var field in param) {
			if (param.hasOwnProperty(field)) {
				var value = param[field];
				if (value) {
					switch (field) {
						case "f_width"   : 
							if(value != "") {
								table.style.width = parseInt(value) + param["f_unit"];
								break;
							}
						case "f_align"   :
							table.style.textAlign = value;
							break;
						case "f_border"  :
							if(value != "") {
								table.style.borderWidth	 = parseInt(value)+"px";
								table.style.borderStyle = "solid";
							}
							break;
						case "f_spacing" :
							if(value != "") {
								table.cellSpacing = parseInt(value);
								break;
							}
						case "f_padding" :
							if(value != "") {
								table.cellPadding = parseInt(value);
								break;
							}
						case "f_float"   :
							if (HTMLArea.is_ie) {
								table.style.styleFloat = ((value != "not set") ? value : "");
							} else {
								table.style.cssFloat = ((value != "not set") ? value : "");
							}
							break;
					}
				}
			}
		}
		var cellwidth = 0;
		if(param.f_fixed) cellwidth = Math.floor(100 / parseInt(param.f_cols));
		var tbody = doc.createElement("tbody");
		table.appendChild(tbody);
		for (var i = param["f_rows"]; i > 0; i--) {
			var tr = doc.createElement("tr");
			tbody.appendChild(tr);
			for (var j = param["f_cols"]; j > 0; j--) {
				var td = doc.createElement("td");
				if (cellwidth) td.style.width = cellwidth + "%";
				if (HTMLArea.is_opera) { td.innerHTML = '&nbsp;'; }
				tr.appendChild(td);
			}
		}
		editor.focusEditor();
		editor.insertNodeAtSelection(table);
		if (this.buttonsConfiguration.toggleborders && this.buttonsConfiguration.toggleborders.setOnTableCreation) {
			this.toggleBorders();
		}
		return true;
	},
	
	toggleBorders : function () {
		var tables = this.editor._doc.getElementsByTagName("table");
		if (tables.length != 0) {
			this.editor.borders = true;
			for (var ix=0; ix < tables.length; ix++) this.editor.borders = this.editor.borders && /htmlarea-showtableborders/.test(tables[ix].className);
			for (ix=0; ix < tables.length; ix++) {
				if (!this.editor.borders) HTMLArea._addClass(tables[ix],'htmlarea-showtableborders');
					else HTMLArea._removeClass(tables[ix],'htmlarea-showtableborders');
			}
		}
			// The only way to get Firefox to show these borders...
		if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) this.editor.setMode("wysiwyg");
	}
});

/*
 * Set the language file for the plugin
 */
TableOperations.I18N = TableOperations_langArray;

/*
 * Initialize the table properties dialog
 */
TableOperations.tablePropertiesInit = function(table) {
	return (function (dialog) {
		var doc = dialog.doc;
		var content = dialog.content;
		var i18n = TableOperations.I18N;
		TableOperations.buildTitle(doc, i18n, content, "Table Properties");
		TableOperations.buildDescriptionFieldset(doc, table, i18n, content);
		var obj = dialog.editor.config.customSelects.BlockStyle ? dialog.editor.plugins.BlockStyle.instance : dialog.editor.config.customSelects["DynamicCSS-class"];
		if (obj && (obj.loaded || obj.cssLoaded)) TableOperations.buildStylingFieldset(doc, table, i18n, content, obj.cssArray);
		if (!dialog.editor.config.disableLayoutFieldsetInTableOperations) TableOperations.buildLayoutFieldset(doc, table, i18n, content);
		if (!dialog.editor.config.disableAlignmentFieldsetInTableOperations) TableOperations.buildAlignmentFieldset(doc, table, i18n, content, "floating");
		if (!dialog.editor.config.disableSpacingFieldsetInTableOperations) TableOperations.buildSpacingFieldset(doc, table, i18n, content);
		if (!dialog.editor.config.disableBordersFieldsetInTableOperations) TableOperations.buildBordersFieldset(dialog.dialogWindow, doc, dialog.editor, table, i18n, content);
		if (!dialog.editor.config.disableColorFieldsetInTableOperations) TableOperations.buildColorsFieldset(dialog.dialogWindow, doc, dialog.editor, table, i18n, content);
		dialog.modal = true;
		dialog.addButtons("ok", "cancel");
		dialog.showAtElement();
	});
};

/*
 * Update the table properties and close the dialog
 */
TableOperations.tablePropertiesUpdate = function(table) {
	return (function (dialog,params) {
		dialog.editor.focusEditor();
		TableOperations.processStyle(params, table);
		table.removeAttribute("border");
		for (var i in params) {
			var val = params[i];
			switch (i) {
			    case "f_caption":
				if (/\S/.test(val)) {
					// contains non white-space characters
					var caption = table.getElementsByTagName("caption")[0];
					if (!caption) {
						caption = dialog.editor._doc.createElement("caption");
						table.insertBefore(caption, table.firstChild);
					}
					caption.innerHTML = val;
				} else {
					// search for caption and delete it if found
					var caption = table.getElementsByTagName("caption")[0];
					if (caption) caption.parentNode.removeChild(caption);
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
			    case "f_class":
			    case "f_class_tbody":
			    case "f_class_thead":
			    case "f_class_tfoot":
			    	var tpart = table;
			    	if (i.length > 7) tpart = table.getElementsByTagName(i.substring(8,13))[0];
				var cls = tpart.className.trim().split(" ");
				for (var j = cls.length;j > 0;) {
					if (!HTMLArea.reservedClassNames.test(cls[--j])) HTMLArea._removeClass(tpart,cls[j]);
				}
				if (val != 'none') HTMLArea._addClass(tpart,val);
				break;
			}
		}
		dialog.editor.focusEditor();
		dialog.editor.updateToolbar();
	});
};

/*
 * Initialize the row/cell properties dialog
 */
TableOperations.rowCellPropertiesInit = function(element,cell) {
	return (function (dialog) {
		var doc = dialog.doc;
		var content = dialog.content;
		var i18n = TableOperations.I18N;
		TableOperations.buildTitle(doc, i18n, content, (cell ? "Cell Properties" : "Row Properties"));
		if (cell) TableOperations.buildCellTypeFieldset(dialog.dialogWindow, doc, dialog.editor, element, i18n, content);
			else TableOperations.buildRowGroupFieldset(dialog.dialogWindow, doc, dialog.editor, element, i18n, content);
		var obj = dialog.editor.config.customSelects.BlockStyle ? dialog.editor.plugins.BlockStyle.instance : dialog.editor.config.customSelects["DynamicCSS-class"];
		if (obj && (obj.loaded || obj.cssLoaded))  TableOperations.buildStylingFieldset(doc, element, i18n, content, obj.cssArray);
			else TableOperations.insertSpace(doc, content);
		if (!dialog.editor.config.disableLayoutFieldsetInTableOperations) TableOperations.buildLayoutFieldset(doc, element, i18n, content, "floating");
		if (!dialog.editor.config.disableAlignmentFieldsetInTableOperations) TableOperations.buildAlignmentFieldset(doc, element, i18n, content);
		if (!dialog.editor.config.disableBordersFieldsetInTableOperations) TableOperations.buildBordersFieldset(dialog.dialogWindow, doc, dialog.editor, element, i18n, content);
		if (!dialog.editor.config.disableColorFieldsetInTableOperations) TableOperations.buildColorsFieldset(dialog.dialogWindow, doc, dialog.editor, element, i18n, content);
		dialog.modal = true;
		dialog.addButtons("ok", "cancel");
		dialog.showAtElement();
	});
};

/*
 * Update the row/cell properties and close the dialog
 */
TableOperations.rowCellPropertiesUpdate = function(element) {
	return (function (dialog,params) {
		dialog.editor.focusEditor();
		TableOperations.processStyle(params, element);
		var convertCellType = false;
		for (var i in params) {
			var val = params[i];
			switch (i) {
			    case "f_scope":
			    	if (val != "not set") element.scope = val;
			    		else element.removeAttribute('scope');
				break;
			    case "f_cell_type":
			    		// Set all cell attributes before cloning it with a new tag
			    	if (val != element.tagName.toLowerCase()) {
					var newCellType = val;
					convertCellType = true;
				}
				break;
			    case "f_rowgroup":
			   	var section = element.parentNode;
				var tagName = section.tagName.toLowerCase();
				if (val != tagName) {
					var table = section.parentNode;
					var newSection = table.getElementsByTagName(val)[0];
					if (!newSection) var newSection = table.insertBefore(dialog.editor._doc.createElement(val), table.getElementsByTagName("tbody")[0]);
					if (tagName == "thead" && val == "tbody") var newElement = newSection.insertBefore(element, newSection.firstChild);
						else var newElement = newSection.appendChild(element);
					if (!section.hasChildNodes()) table.removeChild(section);
				}
				break;
			    case "f_char":
				element.ch = val;
				break;
			    case "f_class":
				var cls = element.className.trim().split(" ");
				for (var j = cls.length;j > 0;) {
					if (!HTMLArea.reservedClassNames.test(cls[--j])) HTMLArea._removeClass(element,cls[j]);
				}
				if (val != 'none') HTMLArea._addClass(element,val);
				break;
			}
		}
		if (convertCellType) {
			var newCell = dialog.editor._doc.createElement(newCellType), p = element.parentNode, a, attrName, name;
			var attrs = element.attributes;
			for (var i = attrs.length; --i >= 0 ;) {
				a = attrs.item(i);
				attrName = a.nodeName;
				name = attrName.toLowerCase();
					// IE5.5 reports wrong values. For this reason we extract the values directly from the root node.
				if (typeof(element[attrName]) != "undefined" && name != "style" && !/^on/.test(name)) {
					if (element[attrName]) newCell.setAttribute(attrName, element[attrName]);
				} else {
					if (a.nodeValue) newCell.setAttribute(attrName, a.nodeValue);
				}
			}
				// In IE, the above fails to update the classname and style attributes.
			if (HTMLArea.is_ie) {
				if (element.style.cssText) newCell.style.cssText = element.style.cssText;
				if (element.className) {
					newCell.setAttribute("className", element.className);
				} else { 
					newCell.className = element.className;
					newCell.removeAttribute("className");
				}
			}
			while (element.firstChild) newCell.appendChild(element.firstChild);
			p.insertBefore(newCell, element);
			p.removeChild(element);
			dialog.editor.selectNodeContents(newCell, false);
		}
		dialog.editor.updateToolbar();
	});
};

TableOperations.getLength = function(value) {
	var len = parseInt(value);
	if (isNaN(len)) len = "";
	return len;
};

// Applies the style found in "params" to the given element.
TableOperations.processStyle = function(params,element) {
	var style = element.style;
	for (var i in params) {
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
			if (params["f_st_borderStyle"] == "none") style.borderWidth = "0px";
			if (params["f_st_borderStyle"] == "not set") style.borderWidth = "";
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
				style.width = val + params["f_st_widthUnit"];
			} else {
				style.width = "";
			}
			break;
		    case "f_st_height":
			if (/\S/.test(val)) {
				style.height = val + params["f_st_heightUnit"];
			} else {
				style.height = "";
			}
			break;
		    case "f_st_textAlign":
			if (val == "character") {
				var ch = params["f_st_textAlignChar"];
				if (ch == '"') {
					ch = '\\"';
				}
				style.textAlign = '"' + ch + '"';
			} else {
				style.textAlign = (val != "not set") ? val : "";
			}
			break;
		    case "f_st_vertAlign":
			style.verticalAlign = (val != "not set") ? val : "";
			break;
		    case "f_st_float":
			if (HTMLArea.is_ie) { 
				style.styleFloat = (val != "not set") ? val : "";
			} else { 
				style.cssFloat = (val != "not set") ? val : "";
			}
			break;
// 		    case "f_st_margin":
// 			style.margin = val + "px";
// 			break;
// 		    case "f_st_padding":
// 			style.padding = val + "px";
// 			break;
		}
	}
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
	button.appendChild(span);
	button.onmouseover = function() { if (!this.disabled) this.className += " buttonColor-hilite"; };
	button.onmouseout = function() { if (!this.disabled) this.className = "buttonColor"; };
	span.onclick = function() {
		if (this.parentNode.disabled) return false;
		var typo3ColorPlugin = editor.plugins.TYPO3Color;
		if (typo3ColorPlugin) {
			typo3ColorPlugin.instance.dialogSelectColor("color", span, field, w);
		} else { 
			editor._popupDialog("select_color.html", function(color) {
				if (color) {
					span.style.backgroundColor = "#" + color;
					field.value = "#" + color;
				}
			}, color, 200, 182, w);
		}
	};
	var span2 = doc.createElement("span");
	span2.innerHTML = "&#x00d7;";
	span2.className = "nocolor";
	span2.title = TableOperations.I18N["Unset color"];
	button.appendChild(span2);
	span2.onmouseover = function() { if (!this.parentNode.disabled) this.className += " nocolor-hilite"; };
	span2.onmouseout = function() { if (!this.parentNode.disabled) this.className = "nocolor"; };
	span2.onclick = function() {
		span.style.backgroundColor = "";
		field.value = "";
	};
	return df;
};
TableOperations.buildTitle = function(doc,i18n,content,title) {
	var div = doc.createElement("div");
	div.className = "title";
	div.innerHTML = i18n[title];
	content.appendChild(div);
	doc.title = i18n[title];
};
TableOperations.buildDescriptionFieldset = function(doc,el,i18n,content) {
	var fieldset = doc.createElement("fieldset");
	TableOperations.insertLegend(doc, i18n, fieldset, "Description");
	TableOperations.insertSpace(doc, fieldset);
	var f_caption = "";
	var capel = el.getElementsByTagName("caption")[0];
	if (capel) f_caption = capel.innerHTML;
	TableOperations.buildInput(doc, el, i18n, fieldset, "f_caption", "Caption:", "Description of the nature of the table", "", "", f_caption, "fr", "value", "");
	TableOperations.insertSpace(doc, fieldset);
	TableOperations.buildInput(doc, el, i18n, fieldset, "f_summary", "Summary:", "Summary of the table purpose and structure", "", "", el.summary, "fr", "value", "");
	TableOperations.insertSpace(doc, fieldset);
	content.appendChild(fieldset);
};
TableOperations.buildRowGroupFieldset = function(w,doc,editor,el,i18n,content) {
	var fieldset = doc.createElement("fieldset");
	TableOperations.insertLegend(doc, i18n, fieldset, "Row group");
	TableOperations.insertSpace(doc, fieldset);
	selected = el.parentNode.tagName.toLowerCase();
	var selectScope = TableOperations.buildSelectField(doc, el, i18n, fieldset, "f_rowgroup", "Row group:", "fr", "", "Table section", ["Table body", "Table header", "Table footer"], ["tbody", "thead", "tfoot"], new RegExp((selected ? selected : "tbody"), "i"));
	TableOperations.insertSpace(doc, fieldset);
	content.appendChild(fieldset);
};
TableOperations.buildCellTypeFieldset = function(w,doc,editor,el,i18n,content) {
	var fieldset = doc.createElement("fieldset");
	TableOperations.insertLegend(doc, i18n, fieldset, "Cell Type and Scope");
	TableOperations.insertSpace(doc, fieldset);
	var ul = doc.createElement("ul");
	fieldset.appendChild(ul);
	var li = doc.createElement("li");
	ul.appendChild(li);
	var selectType = TableOperations.buildSelectField(doc, el, i18n, li, "f_cell_type", "Type of cell", "fr", "", "Specifies the type of cell", ["Normal", "Header"], ["td", "th"], new RegExp(el.tagName.toLowerCase(), "i"));
	selectType.onchange = function() { TableOperations.setStyleOptions(doc, editor, el, i18n, this); };
	var li = doc.createElement("li");
	ul.appendChild(li);
	selected = el.scope.toLowerCase();
	(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
	var selectScope = TableOperations.buildSelectField(doc, el, i18n, li, "f_scope", "Scope", "fr", "", "Scope of header cell", ["Not set", "scope_row", "scope_column", "scope_rowgroup"], ["not set", "row", "col", "rowgroup"], new RegExp((selected ? selected : "not set"), "i"));
	TableOperations.insertSpace(doc, fieldset);
	content.appendChild(fieldset);
};
TableOperations.getCssLabelsClasses = function(cssArray,i18n,tagName,selectedIn) {
	var cssLabels = new Array();
	var cssClasses = new Array();
	cssLabels[0] = i18n["Default"];
	cssClasses[0] = "none";
	var selected = selectedIn;
	var cls = selected.split(" ");
	var nonReservedClassName = false;
	for (var ia = cls.length; ia > 0;) {
		if(!HTMLArea.reservedClassNames.test(cls[--ia])) {
			selected = cls[ia];
			nonReservedClassName = true;
			break;
		}
	}
	var found = false, i = 1, cssClass;
	if(cssArray[tagName]) {
		for(cssClass in cssArray[tagName]){
			if(cssClass != "none") {
				cssLabels[i] = cssArray[tagName][cssClass];
				cssClasses[i] = cssClass;
				if(cssClass == selected) found = true;
				i++;
			} else {
				cssLabels[0] = cssArray[tagName][cssClass];
			}
		}
	}
	if(cssArray['all']){
		for(cssClass in cssArray['all']){
			cssLabels[i] = cssArray['all'][cssClass];
			cssClasses[i] = cssClass;
			if(cssClass == selected) found = true;
			i++;
		}
	}
	if(selected && nonReservedClassName && !found) {
		cssLabels[i] = i18n["Undefined"];
		cssClasses[i] = selected;
	}
	return [cssLabels, cssClasses, selected];
};
TableOperations.setStyleOptions = function(doc,editor,el,i18n,typeSelect) {
	var tagName = typeSelect.value;
	var select = doc.getElementById("f_class");
	if (!select) return false;
	var obj = dialog.editor.config.customSelects.BlockStyle ? dialog.editor.plugins.BlockStyle.instance : dialog.editor.config.customSelects["DynamicCSS-class"];
	if (obj && (obj.loaded || obj.cssLoaded)) var cssArray = obj.cssArray;
		else return false;
	var cssLabelsClasses = TableOperations.getCssLabelsClasses(cssArray,i18n,tagName,el.className);
	var options = cssLabelsClasses[0];
	var values = cssLabelsClasses[1];
	var selected = cssLabelsClasses[2];
	var selectedReg = new RegExp((selected ? selected : "none"), "i");
	while(select.options.length>0) select.options[select.length-1] = null;
	select.selectedIndex = 0;
	var option;
	for (var i = 0; i < options.length; ++i) {
		option = doc.createElement("option");
		select.appendChild(option);
		option.value = values[i];
		option.appendChild(doc.createTextNode(options[i]));
		option.selected = selectedReg.test(values[i]);
	}
	if(select.options.length>1) select.disabled = false;
		else select.disabled = true;
};
TableOperations.buildStylingFieldset = function(doc,el,i18n,content,cssArray) {
	var tagName = el.tagName.toLowerCase();
	var table = (tagName == "table");
	var cssLabelsClasses = TableOperations.getCssLabelsClasses(cssArray,i18n,tagName,el.className);
	var cssLabels = cssLabelsClasses[0];
	var cssClasses = cssLabelsClasses[1];
	var selected = cssLabelsClasses[2];
	var fieldset = doc.createElement("fieldset");
	TableOperations.insertLegend(doc, i18n, fieldset, "CSS Style");
	TableOperations.insertSpace(doc, fieldset);
	var ul = doc.createElement("ul");
	ul.className = "floating";
	fieldset.appendChild(ul);
	var li = doc.createElement("li");
	ul.appendChild(li);
	TableOperations.buildSelectField(doc, el, i18n, li, "f_class", (table ? "Table class:" : "Class:"), "fr", "", (table ? "Table class selector" : "Class selector"), cssLabels, cssClasses, new RegExp((selected ? selected : "none"), "i"), "", false);
	if (table) {
		var tbody = el.getElementsByTagName("tbody")[0];
		if (tbody) {
			var li = doc.createElement("li");
			ul.appendChild(li);
			cssLabelsClasses = TableOperations.getCssLabelsClasses(cssArray, i18n, "tbody", tbody.className);
			cssLabels = cssLabelsClasses[0];
			cssClasses = cssLabelsClasses[1];
			selected = cssLabelsClasses[2];
			TableOperations.buildSelectField(doc, el, i18n, li, "f_class_tbody", "Table body class:", "fr", "", "Table body class selector", cssLabels, cssClasses, new RegExp((selected ? selected : "none"), "i"), "", false);
		}
		ul = null;
		var thead = el.getElementsByTagName("thead")[0];
		if (thead) {
			var ul = doc.createElement("ul");
			fieldset.appendChild(ul);
			var li = doc.createElement("li");
			ul.appendChild(li);
			cssLabelsClasses = TableOperations.getCssLabelsClasses(cssArray, i18n, "thead", thead.className);
			cssLabels = cssLabelsClasses[0];
			cssClasses = cssLabelsClasses[1];
			selected = cssLabelsClasses[2];
			TableOperations.buildSelectField(doc, el, i18n, li, "f_class_thead", "Table header class:", "fr", "", "Table header class selector", cssLabels, cssClasses, new RegExp((selected ? selected : "none"), "i"), "", false);
		}
		var tfoot = el.getElementsByTagName("tfoot")[0];
		if (tfoot) {
			if (!ul) {
				var ul = doc.createElement("ul");
				fieldset.appendChild(ul);
			}
			var li = doc.createElement("li");
			ul.appendChild(li);
			cssLabelsClasses = TableOperations.getCssLabelsClasses(cssArray, i18n, "tfoot", tfoot.className);
			cssLabels = cssLabelsClasses[0];
			cssClasses = cssLabelsClasses[1];
			selected = cssLabelsClasses[2];
			TableOperations.buildSelectField(doc, el, i18n, li, "f_class_tfoot", "Table footer class:", "fr", "", "Table footer class selector", cssLabels, cssClasses, new RegExp((selected ? selected : "none"), "i"), "", false);
		}
	}
	TableOperations.insertSpace(doc, fieldset);
	content.appendChild(fieldset);
};
TableOperations.buildLayoutFieldset = function(doc,el,i18n,content,fieldsetClass) {
	var select, selected;
	var fieldset = doc.createElement("fieldset");
	if(fieldsetClass) fieldset.className = fieldsetClass;
	TableOperations.insertLegend(doc, i18n, fieldset, "Layout");
	var f_st_width = TableOperations.getLength(el.style.width);
	var f_st_height = TableOperations.getLength(el.style.height);
	var selectedWidthUnit = /%/.test(el.style.width) ? '%' : (/px/.test(el.style.width) ? 'px' : 'em');	
	var selectedHeightUnit = /%/.test(el.style.height) ? '%' : (/px/.test(el.style.height) ? 'px' : 'em');
	var tag = el.tagName.toLowerCase();
	var ul = doc.createElement("ul");
	fieldset.appendChild(ul);
	switch(tag) {
		case "table" :
			var li = doc.createElement("li");
			ul.appendChild(li);
			TableOperations.buildInput(doc, el, i18n, li, "f_st_width", "Width:", "Table width", "", "5", f_st_width, "fr");
			select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_widthUnit", "", "", "", "Width unit", ["percent", "pixels", "em"], ["%", "px", "em"], new RegExp((f_st_width ? selectedWidthUnit : "%"), "i"));
			var li = doc.createElement("li");
			ul.appendChild(li);
			TableOperations.buildInput(doc, el, i18n, li, "f_st_height", "Height:", "Table height", "", "5", f_st_height, "fr");
			select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_heightUnit", "", "", "", "Height unit", ["percent", "pixels", "em"], ["%", "px", "em"], new RegExp((f_st_height ? selectedHeightUnit : "%"), "i"));
			selected = (HTMLArea._is_ie) ? el.style.styleFloat : el.style.cssFloat;
			select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_float", "Float:", "", "", "Specifies where the table should float", ["Not set", "Non-floating", "Left", "Right"], ["not set", "none", "left", "right"], new RegExp((selected ? selected : "not set"), "i"));
			break;
		case "tr" :
			var li = doc.createElement("li");
			ul.appendChild(li);
			TableOperations.buildInput(doc, el, i18n, li, "f_st_width", "Width:", "Row width", "", "5", f_st_width, "fr");
			select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_widthUnit", "", "", "", "Width unit", ["percent", "pixels", "em"], ["%", "px", "em"], new RegExp((f_st_width ? selectedWidthUnit : "%"), "i"));
			var li = doc.createElement("li");
			ul.appendChild(li);
			TableOperations.buildInput(doc, el, i18n, li, "f_st_height", "Height:", "Row height", "", "5", f_st_height, "fr");
			select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_heightUnit", "", "", "", "Height unit", ["percent", "pixels", "em"], ["%", "px", "em"], new RegExp((f_st_height ? selectedHeightUnit : "%"), "i"));
			break;
		case "td" :
		case "th" :
			var li = doc.createElement("li");
			ul.appendChild(li);
			TableOperations.buildInput(doc, el, i18n, li, "f_st_width", "Width:", "Cell width", "", "5", f_st_width, "fr");
			select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_widthUnit", "", "", "", "Width unit", ["percent", "pixels", "em"], ["%", "px", "em"], new RegExp((f_st_width ? selectedWidthUnit : "%"), "i"));
			var li = doc.createElement("li");
			ul.appendChild(li);
			TableOperations.buildInput(doc, el, i18n, li, "f_st_height", "Height:", "Cell height", "", "5", f_st_height, "fr");
			select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_heightUnit", "", "", "", "Height unit", ["percent", "pixels", "em"], ["%", "px", "em"], new RegExp((f_st_height ? selectedHeightUnit : "%"), "i"));		
	}
	content.appendChild(fieldset);
};
TableOperations.buildAlignmentFieldset = function(doc,el,i18n,content,fieldsetClass) {
	var select;
	var tag = el.tagName.toLowerCase();
	var fieldset = doc.createElement("fieldset");
	if(fieldsetClass) fieldset.className = fieldsetClass;
	TableOperations.insertLegend(doc, i18n, fieldset, "Alignment");
	var options = ["Not set", "Left", "Center", "Right", "Justify"];
	var values = ["not set", "left", "center", "right", "justify"];
	var selected = el.style.textAlign;
	(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
/*
	if (tag == "td") {
		options.push("Character");
		values.push("character");
		if(f_st_textAlign.charAt(0) == '"') {
			var splitArray = f_st_textAlign.split('"');
			var f_st_textAlignChar = splitArray[0];
			f_st_textAlign = "character";
		}
	}
*/
	var ul = doc.createElement("ul");
	fieldset.appendChild(ul);
	var li = doc.createElement("li");
	ul.appendChild(li);
	select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_textAlign", "Text alignment:", "fl", "", "Horizontal alignment of text within cell", options, values, new RegExp((selected ? selected : "not set"), "i"));
/*
	if (tag == "td") {
		var characterFields = [];
		TableOperations.buildInput(doc, el, i18n, fieldset, "f_st_textAlignChar", "", "Align on this character", "", "1", f_st_textAlignChar, "", "floating", "", characterFields);
		function setCharVisibility(value) {
			for (var i = 0; i < characterFields.length; ++i) {
				var characterFieldElement = characterFields[i];
				characterFieldElement.style.visibility = value ? "visible" : "hidden";
				if (value && (characterFieldElement.tagName.toLowerCase() == "input" )) {
					characterFieldElement.focus();
					characterFieldElement.select();
				}
			}
		};
		select.onchange = function() { setCharVisibility(this.value == "character"); };
		setCharVisibility(select.value == "character");
	}
*/
	var li = doc.createElement("li");
	ul.appendChild(li);
	selected = el.style.verticalAlign;
	(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
	select = TableOperations.buildSelectField(doc, el, i18n, li, "f_st_vertAlign", "Vertical alignment:", "fl", "", "Vertical alignment of content within cell", ["Not set", "Top", "Middle", "Bottom", "Baseline"], ["not set", "top", "middle", "bottom", "baseline"], new RegExp((selected ? selected : "not set"), "i"));
	content.appendChild(fieldset);
};
TableOperations.buildSpacingFieldset = function(doc,el,i18n,content) {
	var fieldset = doc.createElement("fieldset");
	TableOperations.insertLegend(doc, i18n, fieldset, "Spacing and padding");
	var ul = doc.createElement("ul");
	fieldset.appendChild(ul);
	var li = doc.createElement("li");
	ul.appendChild(li);
	TableOperations.buildInput(doc, el, i18n, li, "f_spacing", "Cell spacing:", "Space between adjacent cells", "pixels", "5", el.cellSpacing, "fr", "", "postlabel");
	var li = doc.createElement("li");
	ul.appendChild(li);
	TableOperations.buildInput(doc, el, i18n, li, "f_padding", "Cell padding:", "Space between content and border in cell", "pixels", "5", el.cellPadding, "fr", "", "postlabel");
	content.appendChild(fieldset);
};
TableOperations.buildBordersFieldset = function(w,doc,editor,el,i18n,content,fieldsetClass) {
	var select;
	var selected;
	var borderFields = [];
	function setBorderFieldsVisibility(value) {
		for (var i = 0; i < borderFields.length; ++i) {
			var borderFieldElement = borderFields[i];
			borderFieldElement.style.visibility = value ? "hidden" : "visible";
			if (!value && (borderFieldElement.tagName.toLowerCase() == "input")) {
				borderFieldElement.focus();
				borderFieldElement.select();
			}
		}
	};
	var fieldset = doc.createElement("fieldset");
	fieldset.className = fieldsetClass;
	TableOperations.insertLegend(doc, i18n, fieldset, "Frame and borders");
	TableOperations.insertSpace(doc, fieldset);
		// Gecko reports "solid solid solid solid" for "border-style: solid".
		// That is, "top right bottom left" -- we only consider the first value.
	selected = el.style.borderStyle;
	(selected.match(/([^\s]*)\s/)) && (selected = RegExp.$1);
	selectBorderStyle = TableOperations.buildSelectField(doc, el, i18n, fieldset, "f_st_borderStyle", "Border style:", "fr", "floating", "Border style", ["Not set", "No border", "Dotted", "Dashed", "Solid", "Double", "Groove", "Ridge", "Inset", "Outset"], ["not set", "none", "dotted", "dashed", "solid", "double", "groove", "ridge", "inset", "outset"], new RegExp((selected ? selected : "not set"), "i"));
	selectBorderStyle.onchange = function() { setBorderFieldsVisibility(this.value == "none"); };
	TableOperations.buildInput(doc, el, i18n, fieldset, "f_st_borderWidth", "Border width:", "Border width", "pixels", "5", TableOperations.getLength(el.style.borderWidth), "fr", "floating", "postlabel", borderFields);
	TableOperations.insertSpace(doc, fieldset, borderFields);

	if (el.tagName.toLowerCase() == "table") {
		TableOperations.buildColorField(w, doc, editor, el, i18n, fieldset, "", "Color:", "fr", "colorButton", el.style.borderColor, "borderColor", borderFields);
		var label = doc.createElement("label");
		label.className = "fl-borderCollapse";
		label.htmlFor = "f_st_borderCollapse";
		label.innerHTML = i18n["Collapsed borders"];
		fieldset.appendChild(label);
		borderFields.push(label);
		var input = doc.createElement("input");
		input.className = "checkbox";
		input.type = "checkbox";
		input.name = "f_st_borderCollapse";
		input.id = "f_st_borderCollapse";
		input.defaultChecked = /collapse/i.test(el.style.borderCollapse);
		input.checked = input.defaultChecked;
		fieldset.appendChild(input);
		borderFields.push(input);
		TableOperations.insertSpace(doc, fieldset, borderFields);
		select = TableOperations.buildSelectField(doc, el, i18n, fieldset, "f_frames", "Frames:", "fr", "floating", "Specifies which sides should have a border", ["Not set", "No sides", "The top side only", "The bottom side only", "The top and bottom sides only", "The right and left sides only", "The left-hand side only", "The right-hand side only", "All four sides"], ["not set", "void", "above", "below", "hsides", "vsides", "lhs", "rhs", "box"], new RegExp((el.frame ? el.frame : "not set"), "i"), borderFields);
		TableOperations.insertSpace(doc, fieldset, borderFields);
		select = TableOperations.buildSelectField(doc, el, i18n, fieldset, "f_rules", "Rules:", "fr", "floating", "Specifies where rules should be displayed", ["Not set", "No rules", "Rules will appear between rows only", "Rules will appear between columns only", "Rules will appear between all rows and columns"], ["not set", "none", "rows", "cols", "all"], new RegExp((el.rules ? el.rules : "not set"), "i"), borderFields);
	} else {
		TableOperations.insertSpace(doc, fieldset, borderFields);
		TableOperations.buildColorField(w, doc, editor, el, i18n, fieldset, "", "Color:", "fr", "colorButton", el.style.borderColor, "borderColor", borderFields);
	}
	setBorderFieldsVisibility(selectBorderStyle.value == "none");
	TableOperations.insertSpace(doc, fieldset);
	content.appendChild(fieldset);
};
TableOperations.buildColorsFieldset = function(w,doc,editor,el,i18n,content) {
	var fieldset = doc.createElement("fieldset");
	TableOperations.insertLegend(doc, i18n, fieldset, "Background and colors");
	var ul = doc.createElement("ul");
	fieldset.appendChild(ul);
	var li = doc.createElement("li");
	ul.appendChild(li);
	TableOperations.buildColorField(w, doc, editor, el, i18n, li, "", "FG Color:", "fr", "colorButtonNoFloat", el.style.color, "color");
	var li = doc.createElement("li");
	ul.appendChild(li);
	TableOperations.buildColorField(w, doc, editor, el, i18n, li, "", "Background:", "fr", "colorButtonNoFloat", el.style.backgroundColor, "backgroundColor");
	var url;
	if (el.style.backgroundImage.match(/url\(\s*(.*?)\s*\)/)) url = RegExp.$1;
	TableOperations.buildInput(doc, el, i18n, li, "f_st_backgroundImage", "Image URL:", "URL of the background image", "", "", url, "", "shorter-value");
	content.appendChild(fieldset);
};
TableOperations.insertLegend = function(doc,i18n, fieldset,legend) {
	var legendNode = doc.createElement("legend");
	legendNode.innerHTML = i18n[legend];
	fieldset.appendChild(legendNode);
};
TableOperations.insertSpace =	function(doc,fieldset,fields) {
	var space = doc.createElement("div");
	space.className = "space";
	fieldset.appendChild(space);
	if(fields) fields.push(space);
};
TableOperations.buildInput = function(doc,el,i18n,fieldset,fieldName,fieldLabel,fieldTitle,postLabel,fieldSize,fieldValue,labelClass,inputClass,postClass,fields) {
	var label;
		// Field label
	if(fieldLabel) {
		label = doc.createElement("label");
		if(labelClass) label.className = labelClass;
		label.innerHTML = i18n[fieldLabel];
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
	if(fieldTitle) input.title = i18n[fieldTitle];
	if(fieldSize) input.size = fieldSize;
	if(fieldValue) input.value = fieldValue;
	fieldset.appendChild(input);
	if(fields) fields.push(input);
		// Field post label
	if(postLabel) {
		label = doc.createElement("span");
		if(postClass) label.className = postClass;
		label.innerHTML = i18n[postLabel];
		fieldset.appendChild(label);
		if(fields) fields.push(label);
	}
};
TableOperations.buildSelectField = function(doc,el,i18n,fieldset,fieldName,fieldLabel,labelClass,selectClass,fieldTitle,options,values,selected,fields,translateOptions) {
	if(typeof(translateOptions) == "undefined") var translateOptions = true;
		// Field Label
	if(fieldLabel) {
		var label = doc.createElement("label");
		if(labelClass) label.className = labelClass;
		label.innerHTML = i18n[fieldLabel];
		label.htmlFor = fieldName;
		fieldset.appendChild(label);
		if(fields) fields.push(label);
	}
		// Text Alignment Select Box
	var select = doc.createElement("select");
	if (selectClass) select.className = selectClass;
	select.id = fieldName;
	select.name =  fieldName;
	select.title= i18n[fieldTitle];
	select.selectedIndex = 0;
	var option;
	for (var i = 0; i < options.length; ++i) {
		option = doc.createElement("option");
		select.appendChild(option);
		option.value = values[i];
		if(translateOptions) option.appendChild(doc.createTextNode(i18n[options[i]]));
			else option.appendChild(doc.createTextNode(options[i]));
		option.selected = selected.test(option.value);
	}
	if (select.options.length>1) select.disabled = false;
		else select.disabled = true;
	fieldset.appendChild(select);
	if(fields) fields.push(select);
	return select;
};
TableOperations.buildColorField = function(w,doc,editor,el,i18n,fieldset,fieldName,fieldLabel,labelClass, buttonClass, fieldValue,fieldType,fields) {
		// Field Label
	if(fieldLabel) {
		var label = doc.createElement("label");
		if(labelClass) label.className = labelClass;
		label.innerHTML = i18n[fieldLabel];
		fieldset.appendChild(label);
		if(fields) fields.push(label);
	}
	var colorButton = TableOperations.createColorButton(w, doc, editor, fieldValue, fieldType);
	colorButton.className = buttonClass;
	fieldset.appendChild(colorButton);
	if(fields) fields.push(colorButton);
};