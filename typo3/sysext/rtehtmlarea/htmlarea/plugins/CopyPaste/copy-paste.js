/***************************************************************
*  Copyright notice
*
*  (c) 2008-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 */
HTMLArea.CopyPaste = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		/*
		 * Setting up some properties from PageTSConfig
		 */
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.4',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: this.localize('Technische Universitat Ilmenau'),
			sponsorUrl	: 'http://www.tu-ilmenau.de/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the buttons
		 */
		Ext.iterate(this.buttonList, function (buttonId, button) {
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId.toLowerCase()),
				iconCls		: 'htmlarea-action-' + button[2],
				action		: 'onButtonPress',
				context		: button[0],
				selection	: button[3],
				hotKey		: button[1]
			};
			this.registerButton(buttonConfiguration);
		}, this);
		return true;
	},
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: {
		Copy	: [null, 'c', 'copy', true],
		Cut	: [null, 'x', 'cut', true],
		Paste	: [null, 'v', 'paste', false]
	},
	/*
	 * This function gets called when the editor is generated
	 */
	onGenerate: function () {
		this.editor.iframe.mon(Ext.get(Ext.isIE ? this.editor.document.body : this.editor.document.documentElement), 'cut', this.cutHandler, this);
		Ext.iterate(this.buttonList, function (buttonId, button) {
				// Remove button from toolbar, if command is not supported
				// Starting with Safari 5 and Chrome 6, cut and copy commands are not supported anymore by WebKit
			if (!Ext.isGecko && !this.editor.document.queryCommandSupported(buttonId)) {
				this.editor.toolbar.remove(buttonId);
			}
				// Add hot key handling if the button is not enabled in the toolbar
			if (!this.getButton(buttonId)) {
				this.editor.iframe.hotKeyMap.addBinding({
					key: button[1].toUpperCase(),
					ctrl: true,
					shift: false,
					alt: false,
					handler: this.onHotKey,
					scope: this
				});
					// Ensure the hot key can be translated
				this.editorConfiguration.hotKeyList[button[1]] = {
					id	: button[1],
					cmd	: buttonId
				};
			}
		}, this);
	},
	/*
	 * This function gets called when a button or a hotkey was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.editor.focus();
		if (!this.applyToTable(buttonId)) {
				// If we are not handling table cells
			switch (buttonId) {
				case 'Copy':
					if (buttonId == id) {
							// If we are handling a button, not a hotkey
						this.applyBrowserCommand(buttonId);
					}
					break;
				case 'Cut' :
					if (buttonId == id) {
							// If we are handling a button, not a hotkey
						this.applyBrowserCommand(buttonId);
					}
						// Opera will not trigger the onCut event
					if (Ext.isOpera) {
						this.cutHandler();
					}
					break;
				case 'Paste':
					if (buttonId == id) {
							// If we are handling a button, not a hotkey
						this.applyBrowserCommand(buttonId);
					}
						// In FF3, the paste operation will indeed trigger the onPaste event not in FF2; nor in Opera
					if (Ext.isOpera || Ext.isGecko2) {
						var cleaner = this.getButton('CleanWord');
						if (cleaner) {
							cleaner.fireEvent.defer(250, cleaner, ['click', cleaner]);
						}
					}
					break;
				default:
					break;
			}
				// Stop the event if a button was handled
			return (buttonId != id);
		} else {
				// The table case was handled, let the event be stopped.
				// No cleaning required as the pasted cells are copied from the editor.
				// However paste by Opera cannot be stopped.
				// Revert Opera's operation as it produces invalid html anyways
			if (Ext.isOpera) {
				this.editor.inhibitKeyboardInput = true;
				var bookmark = this.editor.getBookMark().get(this.editor.getSelection().createRange());
				var html = this.editor.getInnerHTML();
				this.revertPaste.defer(200, this, [html, bookmark]);
			}
			return false;
		}
	},
	/*
	 * This funcion reverts the paste operation (performed by Opera)
	 */
	revertPaste: function (html, bookmark) {
		this.editor.setHTML(html);
		this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
		this.editor.inhibitKeyboardInput = false;
	},
	/*
	 * This function applies the browser command when a button is pressed
	 * In the case of hot key, the browser does it automatically
	 */
	applyBrowserCommand: function (buttonId) {
		var success = this.editor.getSelection().execCommand(buttonId, false, null);
		if (!success && Ext.isGecko) {
			this.mozillaClipboardAccessException();
		}
	},
	/*
	 * Handler for hotkeys configured through the hotKeyMap while button not enabled in toolbar (see onGenerate above)
	 */
	onHotKey: function (key, event) {
		var hotKey = String.fromCharCode(key).toLowerCase();
			// Stop the event if it was handled here
		if (!this.onButtonPress(this, hotKey)) {
			event.stopEvent();
		}
	},
	/*
	 * This function removes any link left over by the cut operation
	 */
	cutHandler: function (event) {
		this.removeEmptyLink.defer(50, this);
	},
	/*
	 * This function unlinks any empty link left over by the cut operation
	 */
	removeEmptyLink: function() {
		var range = this.editor.getSelection().createRange();
		var parent = this.editor.getSelection().getParentElement();
		if (parent.firstChild && /^(a)$/i.test(parent.firstChild.nodeName)) {
			parent = parent.firstChild;
		}
		if (/^(a)$/i.test(parent.nodeName)) {
			parent.normalize();
			if (!parent.innerHTML || (parent.childNodes.length == 1 && /^(br)$/i.test(parent.firstChild.nodeName))) {
				if (!HTMLArea.isIEBeforeIE9) {
					var container = parent.parentNode;
					this.editor.getDomNode().removeMarkup(parent);
						// Opera does not render empty list items
					if (Ext.isOpera && /^(li)$/i.test(container.nodeName) && !container.firstChild) {
						container.innerHTML = '<br />';
						this.editor.getSelection().selectNodeContents(container, true);
					}
				} else {
					HTMLArea.DOM.removeFromParent(parent);
				}
			}
		}
		if (Ext.isWebKit) {
				// Remove Apple's span and font tags
			this.editor.getDomNode().cleanAppleStyleSpans(this.editor.document.body);
				// Reset Safari selection in order to prevent insertion of span and/or font tags on next text input
			var bookmark = this.editor.getBookMark().get(this.editor.getSelection().createRange());
			this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
		}
		this.editor.updateToolbar();
	},
	/*
	 * This function gets called when a copy/cut/paste operation is to be performed
	 * This feature allows to paste a region of table cells
	 */
	applyToTable: function (buttonId) {
		var range = this.editor.getSelection().createRange();
		var parent = this.editor.getSelection().getParentElement();
		var endBlocks = this.editor.getSelection().getEndBlocks();
		switch (buttonId) {
			case 'Copy':
			case 'Cut' :
				HTMLArea.copiedCells = null;
				if ((/^(tr)$/i.test(parent.nodeName) && !Ext.isIE) || (/^(td|th)$/i.test(endBlocks.start.nodeName) && /^(td|th)$/i.test(endBlocks.end.nodeName) && !Ext.isGecko && endBlocks.start != endBlocks.end)) {
					HTMLArea.copiedCells = this.collectCells(buttonId, endBlocks);
				}
				break;
			case 'Paste':
				if (/^(tr|td|th)$/i.test(parent.nodeName) && HTMLArea.copiedCells) {
					return this.pasteCells(endBlocks);
				}
				break;
			default:
				break;
		}
		return false;
	},
	/*
	 * This function handles pasting of a collection of table cells
	 */
	pasteCells: function (endBlocks) {
		var cell = null;
		if (Ext.isGecko) {
			var range = this.editor.getSelection().createRange();
			cell = range.startContainer.childNodes[range.startOffset];
			while (cell && !HTMLArea.DOM.isBlockElement(cell)) {
				cell = cell.parentNode;
			}
		}
		if (!cell && /^(td|th)$/i.test(endBlocks.start.nodeName)) {
			cell = endBlocks.start;
		}
		if (!cell) {
				// Let the browser do it
			return false;
		}
		var tableParts = ['thead', 'tbody', 'tfoot'];
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
	collectCells: function (operation, endBlocks) {
		var tableParts = ['thead', 'tbody', 'tfoot'];
		var tablePartsIndex = { thead : 0, tbody : 1, tfoot : 2 };
		var selection = this.editor.getSelection().get().selection;
		var range, i = 0, cell, cells = null;
		var rows = new Array();
		for (var k = tableParts.length; --k >= 0;) {
			rows[k] = [];
		}
		var row = null;
		var cutRows = [];
		if (Ext.isGecko) {
			if (selection.rangeCount == 1) { // Collect the cells in the selected row
				cells = [];
				for (var i = 0, n = endBlocks.start.cells.length; i < n; ++i) {
					cell = endBlocks.start.cells[i];
					cells.push(cell.innerHTML);
					if (operation === 'Cut') {
						cell.innerHTML = '<br />';
					}
					if (operation === 'Cut') {
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
							if (operation === 'Cut' && firstCellOfRow && lastCellOfRow) cutRows.push(row);
							row = cell.parentNode;
							cells = [];
							firstCellOfRow = false;
							lastCellOfRow = false;
						}
						cells.push(cell.innerHTML);
						if (operation === 'Cut') {
							cell.innerHTML = '<br />';
						}
						if (!cell.previousSibling) firstCellOfRow = true;
						if (!cell.nextSibling) lastCellOfRow = true;
					}
				} catch(e) {
					/* finished walking through selection */
				}
				try { rows[tablePartsIndex[row.parentNode.nodeName.toLowerCase()]].push(cells); } catch(e) { }
				if (row && operation === 'Cut' && firstCellOfRow && lastCellOfRow) {
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
					if (operation === 'Cut') {
						cell.innerHTML = '';
					}
					if (!cell.previousSibling) firstCellOfRow = true;
					if (!cell.nextSibling) lastCellOfRow = true;
					if (cell == endBlocks.end) break;
					cell = cell.nextSibling;
				}
				rows[tablePartsIndex[firstRow.parentNode.nodeName.toLowerCase()]].push(cells);
				if (operation === 'Cut' && firstCellOfRow && lastCellOfRow) cutRows.push(firstRow);
			} else { // Collect all cells on selected rows
				row = firstRow;
				while (row) {
					cells = [];
					for (var i = 0, n = row.cells.length; i < n; ++i) {
						cells.push(row.cells[i].innerHTML);
						if (operation === 'Cut') {
							row.cells[i].innerHTML = '';
						}
					}
					rows[tablePartsIndex[row.parentNode.nodeName.toLowerCase()]].push(cells);
					if (operation === 'Cut') cutRows.push(row);
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
					this.editor.getSelection().selectNodeContents(next.cells[0], true);
				} else if (tablePart.parentNode.rows.length) {
					this.editor.getSelection().selectNodeContents(tablePart.parentNode.rows[0].cells[0], true);
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
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg' && this.editor.isEditable() && button.itemId === 'Paste') {
			try {
				button.setDisabled(!this.editor.document.queryCommandEnabled(button.itemId));
			} catch(e) {
				button.setDisabled(true);
			}
		}
	},
	/*
	 * Mozilla clipboard access exception handler
	 */
	mozillaClipboardAccessException: function () {
		if (InstallTrigger && this.buttonsConfiguration.paste && this.buttonsConfiguration.paste.mozillaAllowClipboardURL) {
			TYPO3.Dialog.QuestionDialog({
				title: this.localize('Allow-Clipboard-Helper-Add-On-Title'),
				msg: this.localize('Allow-Clipboard-Helper-Extension'),
				fn: this.installAllowClipboardHelperExtension,
				scope: this
			});
		} else {
			TYPO3.Dialog.QuestionDialog({
				title: this.localize('Firefox-Security-Prefs-Question-Title'),
				msg: this.localize('Moz-Clipboard'),
				fn: function (button) {
					if (button == 'yes') {
						window.open('http://mozilla.org/editor/midasdemo/securityprefs.html');
					}
				}
			});
			if (!InstallTrigger) {
				this.appendToLog('mozillaClipboardAccessException', 'Firefox InstallTrigger was not defined.', 'warn');
			}
		}
	},
	/*
	 * Install AllowClipboardHelperExtension
	 *
	 * @param	string		button: yes or no button was clicked in the dialogue
	 *
	 * @return	void
	 */
	installAllowClipboardHelperExtension: function (button) {
		if (button == 'yes') {
			if (InstallTrigger.enabled()) {
				var self = this;
				function mozillaInstallCallback(url, returnCode) {
					if (returnCode == 0) {
						TYPO3.Dialog.InformationDialog({
							title: self.localize('Allow-Clipboard-Helper-Add-On-Title'),
							msg: self.localize('Allow-Clipboard-Helper-Extension-Success')
						});
					} else {
						TYPO3.Dialog.ErrorDialog({
							title: self.localize('Allow-Clipboard-Helper-Add-On-Title'),
							msg: self.localize('Moz-Extension-Failure')
						});
						self.appendToLog('installAllowClipboardHelperExtension', 'Mozilla install return code was: ' + returnCode + '.', 'warn');
					}
					return false;
				}
				var mozillaXpi = new Object();
				mozillaXpi['AllowClipboard Helper'] = this.buttonsConfiguration.paste.mozillaAllowClipboardURL;
				InstallTrigger.install(mozillaXpi, mozillaInstallCallback);
			} else {
				TYPO3.Dialog.ErrorDialog({
					title: this.localize('Allow-Clipboard-Helper-Add-On-Title'),
					msg: this.localize('Mozilla-Org-Install-Not-Enabled')
				});
				this.appendToLog('installAllowClipboardHelperExtension', 'Mozilla install was not enabled.', 'warn');
			}
		}
	}
});
