/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Copy Paste for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
CopyPaste = HTMLArea.Plugin.extend({
		
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		
		/*
		 * Setting up some properties from PageTSConfig
		 */
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: this.localize("Technische Universitat Ilmenau"),
			sponsorUrl	: "http://www.tu-ilmenau.de/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the buttons
		 */
		for (var buttonId in this.buttonList) {
			if (this.buttonList.hasOwnProperty(buttonId)) {
				var button = this.buttonList[buttonId];
				var buttonConfiguration = {
					id		: buttonId,
					tooltip		: this.localize(buttonId.toLowerCase()),
					action		: "onButtonPress",
					context		: button[0],
					hotKey		: ((this.buttonsConfiguration[button[2]] && this.buttonsConfiguration[button[2]].hotKey) ? this.buttonsConfiguration[button[2]].hotKey : (button[1] ? button[1] : null))
				};
				this.registerButton(buttonConfiguration);
				if (!this.isButtonInToolbar(buttonId)) {
					var hotKeyConfiguration = {
						id	: buttonConfiguration.hotKey,
						cmd	: buttonConfiguration.id,
						action	: buttonConfiguration.action
					};
					this.registerHotKey(hotKeyConfiguration);
				}
			}
		}
		return true;
	 },
	 
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList : {
		Copy	: [null, "c", "copy"],
		Cut	: [null, "x", "cut"],
		Paste	: [null, "v", "paste"]
	},
	
	/*
	 * This function gets called when a button or a hotkey was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.editor.focusEditor();
		if (!this.applyToTable(buttonId, target)) {
				// If we are not handling table cells
			switch (buttonId) {
				case "Copy":
				case "Cut" :
					if (buttonId == id) {
							// If we are handling a button, not a hotkey
						this.applyBrowserCommand(buttonId);
					} else if (buttonId == "Cut") {
							// If we are handling the cut hotkey
						var removeEmpyLinkLaterFunctRef = this.makeFunctionReference("removeEmptyLinkLater");
						window.setTimeout(removeEmpyLinkLaterFunctRef, 50);
					}
					break;
				case "Paste":
					if (buttonId == id) {
							// If we are handling a button, not a hotkey
						this.applyBrowserCommand(buttonId);
					}
						// In FF3, the paste operation will indeed trigger the onPaste event not in FF2; nor in Opera
					if (HTMLArea.is_opera || (HTMLArea.is_gecko && navigator.productSub < 20080514) || HTMLArea.is_safari) {
						var cleanLaterFunctRef = this.getPluginInstance("DefaultClean") ? this.getPluginInstance("DefaultClean").cleanLaterFunctRef : (this.getPluginInstance("TYPO3HtmlParser") ? this.getPluginInstance("TYPO3HtmlParser").cleanLaterFunctRef : null);
						if (cleanLaterFunctRef) {
							window.setTimeout(cleanLaterFunctRef, 50);
						}
					}
					break;
				default:
					break;
			}
			return (buttonId != id);
		} else {
				// We handled the table case
			return false;
		}
	},
	
	applyBrowserCommand : function (buttonId) {
		try {
			this.editor._doc.execCommand(buttonId, false, null);
		} catch (e) {
			if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) {
				this.mozillaClipboardAccessException();
			}
		}
		if (buttonId == "Cut") {
			this.removeEmptyLink();
		}
	},
	
	/*
	 * This function unlinks any empty link left over by the cut operation
	 */
	removeEmptyLink : function() {
		var selection = this.editor._getSelection();
		var range = this.editor._createRange(selection);
		var parent = this.editor.getParentElement(selection, range);
		if (parent.firstChild && /^(a)$/i.test(parent.firstChild.nodeName)) {
			parent = parent.firstChild;
		}
		if (/^(a)$/i.test(parent.nodeName)) {
			parent.normalize();
			if (!parent.innerHTML || (parent.childNodes.length == 1 && /^(br)$/i.test(parent.firstChild.nodeName))) {
				if (HTMLArea.is_gecko) {
					var container = parent.parentNode;
					this.editor.removeMarkup(parent);
						// Opera does not render empty list items
					if (HTMLArea.is_opera && /^(li)$/i.test(container.nodeName) && !container.firstChild) {
						container.innerHTML = "<br />";
						this.editor.selectNodeContents(container, true);
					}
				} else {
					HTMLArea.removeFromParent(parent);
				}
			}
		}
		if (HTMLArea.is_safari) {
				// Remove Apple's span and font tags
			this.editor.cleanAppleStyleSpans(this.editor._doc.body);
				// Reset Safari selection in order to prevent insertion of span and/or font tags on next text input
			var bookmark = this.editor.getBookmark(this.editor._createRange(this.editor._getSelection()));
			this.editor.selectRange(this.editor.moveToBookmark(bookmark));
		}
	},
	
	/*
	 * This function removes any link left over by the cut operation triggered by hotkey
	 */
	removeEmptyLinkLater : function() {
		this.removeEmptyLink();
		this.editor.updateToolbar();
	},
	
	/*
	 * This function gets called by the main editor when a copy/cut/paste operation is to be performed
	 */
	applyToTable : function (buttonId, target) {
		var selection = this.editor._getSelection();
		var range = this.editor._createRange(selection);
		var parent = this.editor.getParentElement(selection, range);
		var endBlocks = this.editor.getEndBlocks(selection);
		switch (buttonId) {
			case "Copy":
			case "Cut" :
				HTMLArea.copiedCells = null;
				var endBlocks = this.editor.getEndBlocks(selection);
				if ((/^(tr)$/i.test(parent.nodeName) && HTMLArea.is_gecko) || (/^(td|th)$/i.test(endBlocks.start.nodeName) && /^(td|th)$/i.test(endBlocks.end.nodeName) && (HTMLArea.is_ie || HTMLArea.is_safari || HTMLArea.is_opera) && endBlocks.start != endBlocks.end)) {
					HTMLArea.copiedCells = this.collectCells(buttonId, selection, endBlocks);
					if (buttonId === "Cut") return true;
				}
				break;
			case "Paste":
				if (/^(tr|td|th)$/i.test(parent.nodeName) && HTMLArea.copiedCells) {
					return this.pasteCells(selection, endBlocks);
				}
				break;
		}
		return false;
	},
	
	pasteCells : function (selection, endBlocks) {
		var cell = null;
		if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) {
			range = selection.getRangeAt(0);
			cell = range.startContainer.childNodes[range.startOffset];
			while (cell && !HTMLArea.isBlockElement(cell)) {
				cell = cell.parentNode;
			}
		}
		if (!cell && /^(td|th)$/i.test(endBlocks.start.nodeName)) {
			cell = endBlocks.start;
		}
		if (!cell) return false;
		var tableParts = ["thead", "tbody", "tfoot"];
		var tablePartsIndex = { thead : 0, tbody : 1, tfoot : 2 };
		var tablePart = cell.parentNode.parentNode;
		var tablePartIndex = tablePartsIndex[tablePart.nodeName.toLowerCase()]
		var rows = HTMLArea.copiedCells[tablePartIndex];
		if (rows && rows[0]) {
			for (var i = 0, rowIndex = cell.parentNode.sectionRowIndex-1; i < rows.length && ++rowIndex < tablePart.rows.length; ++i) {
				var cells = rows[i];
				if (!cells) break;
				var row = tablePart.rows[rowIndex];
				for (var j = 0, cellIndex = cell.cellIndex-1; j < cells.length && ++cellIndex < row.cells.length; ++j) {
					row.cells[cellIndex].innerHTML = cells[j];
				}
			}
		}
		var table = tablePart.parentNode;
		for (var k = tablePartIndex +1; k < 3; ++k) {
			tablePart = table.getElementsByTagName(tableParts[k])[0];
			if (tablePart) {
				var rows = HTMLArea.copiedCells[k];
				for (var i = 0; i < rows.length && i < tablePart.rows.length; ++i) {
					var cells = rows[i];
					if (!cells) break;
					var row = tablePart.rows[i];
					for (var j = 0, cellIndex = cell.cellIndex-1; j < cells.length && ++cellIndex < row.cells.length; ++j) {
						row.cells[cellIndex].innerHTML = cells[j];
					}
				}
			}
		}
		return true;
	},
	
	/*
	 * This function collects the selected table cells for copy/cut operations
	 */
	collectCells : function (operation, selection, endBlocks) {
		var tableParts = ["thead", "tbody", "tfoot"];
		var tablePartsIndex = { thead : 0, tbody : 1, tfoot : 2 };
		var selection = this.editor._getSelection();
		var range, i = 0, cell, cells = null;
		var rows = new Array();
		for (var k = tableParts.length; --k >= 0;) {
			rows[k] = [];
		}
		var row = null;
		var cutRows = [];
		if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) {  // Firefox
			if (selection.rangeCount == 1) { // Collect the cells in the selected row
				cells = [];
				for (var i = 0, n = endBlocks.start.cells.length; i < n; ++i) {
					cell = endBlocks.start.cells[i];
					cells.push(cell.innerHTML);
					if (operation === "Cut") {
						cell.innerHTML = "<br />";
					}
					if (operation === "Cut") {
						cutRows.push(endBlocks.start);
					}
				}
				rows[tablePartsIndex[endBlocks.start.parentNode.nodeName.toLowerCase()]].push(cells);
			} else {
				try { // Collect the cells in some region of the table
					var firstCellOfRow = false;
					var lastCellOfRow = false;
					while (range = selection.getRangeAt(i++)) {
						cell = range.startContainer.childNodes[range.startOffset];
						if (cell.parentNode != row) {
							(cells) && rows[tablePartsIndex[row.parentNode.nodeName.toLowerCase()]].push(cells);
							if (operation === "Cut" && firstCellOfRow && lastCellOfRow) cutRows.push(row);
							row = cell.parentNode;
							cells = [];
							firstCellOfRow = false;
							lastCellOfRow = false;
						}
						cells.push(cell.innerHTML);
						if (operation === "Cut") {
							cell.innerHTML = "<br />";
						}
						if (!cell.previousSibling) firstCellOfRow = true;
						if (!cell.nextSibling) lastCellOfRow = true;
					}
				} catch(e) {
					/* finished walking through selection */
				}
				try { rows[tablePartsIndex[row.parentNode.nodeName.toLowerCase()]].push(cells); } catch(e) { }
				if (row && operation === "Cut" && firstCellOfRow && lastCellOfRow) {
					cutRows.push(row);
				}
			}
		} else { // Internet Explorer, Safari and Opera
			var firstRow = endBlocks.start.parentNode;
			var lastRow = endBlocks.end.parentNode;
			cells = [];
			var firstCellOfRow = false;
			var lastCellOfRow = false;
			if (firstRow == lastRow) { // Collect the selected cells on the row
				cell = endBlocks.start;
				while (cell) {
					cells.push(cell.innerHTML);
					if (operation === "Cut") {
						cell.innerHTML = "";
					}
					if (!cell.previousSibling) firstCellOfRow = true;
					if (!cell.nextSibling) lastCellOfRow = true;
					if (cell == endBlocks.end) break;
					cell = cell.nextSibling;
				}
				rows[tablePartsIndex[firstRow.parentNode.nodeName.toLowerCase()]].push(cells);
				if (operation === "Cut" && firstCellOfRow && lastCellOfRow) cutRows.push(firstRow);
			} else { // Collect all cells on selected rows
				row = firstRow;
				while (row) {
					cells = [];
					for (var i = 0, n = row.cells.length; i < n ; ++i) {
						cells.push(row.cells[i].innerHTML);
						if (operation === "Cut") {
							row.cells[i].innerHTML = "";
						}
					}
					rows[tablePartsIndex[row.parentNode.nodeName.toLowerCase()]].push(cells);
					if (operation === "Cut") cutRows.push(row);
					if (row == lastRow) break;
					row = row.nextSibling;
				}
			}
		}
		for (var i = 0, n = cutRows.length; i < n; ++i) {
			if (i == n-1) {
				var tablePart = cutRows[i].parentNode;
				var next = cutRows[i].nextSibling;
				cutRows[i].parentNode.removeChild(cutRows[i]);
				if (next) {
					this.editor.selectNodeContents(next.cells[0], true);
				} else if (tablePart.parentNode.rows.length) {
					this.editor.selectNodeContents(tablePart.parentNode.rows[0].cells[0], true);
				}
			} else {
				cutRows[i].parentNode.removeChild(cutRows[i]);
			}
		}
		return rows;
	},
	
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar : function () {
		if (this.getEditorMode() === "wysiwyg" || this.editor.isEditable()) {
			var buttonId = "Paste";
			if (typeof(this.editor._toolbarObjects[buttonId]) !== "undefined") {
				try {
					this.editor._toolbarObjects[buttonId].state("enabled", this.editor._doc.queryCommandEnabled(buttonId));
				} catch(e) {
					this.editor._toolbarObjects[buttonId].state("enabled", false);
				}
			}
		}
	},

	/*
	 * Mozilla clipboard access exception handler
	 */
	mozillaClipboardAccessException : function () {
		if (this.buttonsConfiguration.paste && this.buttonsConfiguration.paste.mozillaAllowClipboardURL) {
			if (confirm(this.localize("Allow-Clipboard-Helper-Extension"))) {
				if (InstallTrigger.enabled()) {
					var mozillaXpi = new Object();
					mozillaXpi["AllowClipboard Helper"] = this.buttonsConfiguration.paste.mozillaAllowClipboardURL;
					var mozillaInstallCallback = this.makeFunctionReference("mozillaInstallCallback");
					InstallTrigger.install(mozillaXpi, mozillaInstallCallback);
				} else {
					alert(this.localize("Mozilla-Org-Install-Not-Enabled"));
					this.appendToLog("mozillaClipboardAccessException", "Mozilla install was not enabled.");
					return;
				}
			}
		} else if (confirm(this.localize("Moz-Clipboard"))) {
			window.open("http://mozilla.org/editor/midasdemo/securityprefs.html");
		}
	},
	
	/*
	 * Mozilla Add-on installer call back
	 */
	mozillaInstallCallback : function (url, returnCode) {
		if (returnCode == 0) {
			alert(this.localize("Allow-Clipboard-Helper-Extension-Success"));
		} else {
			alert(this.localize("Moz-Extension-Failure"));
			this.appendToLog("mozillaInstallCallback", "Mozilla install return code was: " + returnCode + ".");
		}
		return;
	}
});

