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
 * DefinitionList Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
DefinitionList = BlockElements.extend({
		
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
		var parentPlugin = this.editor.plugins.BlockElements.instance;
		this.tags = parentPlugin.tags;
		this.useClass = parentPlugin.useClass;
		this.useBlockquote = parentPlugin.useBlockquote;
		this.useAlignAttribute = parentPlugin.useAlignAttribute;
		this.allowedBlockElements = parentPlugin.allowedBlockElements;
		this.indentedList = null;
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "0.4",
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
					tooltip		: this.localize(buttonId + "-Tooltip"),
					action		: "onButtonPress",
					context		: button[0],
					hotKey		: ((this.buttonsConfiguration[button[2]] && this.buttonsConfiguration[button[2]].hotKey) ? this.buttonsConfiguration[button[2]].hotKey : (button[1] ? button[1] : null))
				};
				this.registerButton(buttonConfiguration);
			}
		}
		
		return true;
	 },
	 
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList : {
		Indent			: [null, "TAB", "indent"],
		Outdent			: [null, "SHIFT-TAB", "outdent"],
		DefinitionList		: [null, null, "definitionlist"],
		DefinitionItem		: ["dd,dt", null, "definitionitem"]
	},
	
	/*
	 * This function gets called when a button was pressed.
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
		var selection = editor._getSelection();
		var range = editor._createRange(selection);
		var statusBarSelection = editor.getPluginInstance("StatusBar") ? editor.getPluginInstance("StatusBar").getSelection() : null;
		var parentElement = statusBarSelection ? statusBarSelection : this.editor.getParentElement(selection, range);
		if (target) {
			parentElement = target;
		}
		while (parentElement && (!HTMLArea.isBlockElement(parentElement) || /^(li)$/i.test(parentElement.nodeName))) {
			parentElement = parentElement.parentNode;
		}
		
		switch (buttonId) {
			case "Indent" :
				if (/^(dd|dt)$/i.test(parentElement.nodeName) && this.indentDefinitionList(parentElement, range)) {
					break;
				} else {
					this.base(editor, id, target);
				}
				break;
			case "Outdent" :
				if (/^(dt)$/i.test(parentElement.nodeName) && this.outdentDefinitionList(selection, range)) {
					break;
				} else {
					this.base(editor, id, target);
				}
				break;
			case "DefinitionList":
				var bookmark = this.editor.getBookmark(range);
				this.insertDefinitionList();
				this.editor.selectRange(this.editor.moveToBookmark(bookmark));
				break;
			case "DefinitionItem":
				var bookmark = this.editor.getBookmark(range);
				this.remapNode(parentElement, (parentElement.nodeName.toLowerCase() === "dt") ? "dd" : "dt");
				this.editor.selectRange(this.editor.moveToBookmark(bookmark));
				break;
			default:
				this.base(editor, id, target);
		}
		return false;
	},
	
	/*
	 * This function remaps a node to the specified node name
	 */
	remapNode : function(node, nodeName) {
		var newNode = this.editor.convertNode(node, nodeName);
		var attributes = node.attributes, attributeName, attributeValue;
		for (var i = attributes.length; --i >= 0;) {
			attributeName = attributes.item(i).nodeName;
			attributeValue = node.getAttribute(attributeName);
			if (attributeValue) newNode.setAttribute(attributeName, attributeValue);
		}
			// In IE, the above fails to update the classname and style attributes.
		if (HTMLArea.is_ie) {
			if (node.style.cssText) {
				newNode.style.cssText = node.style.cssText;
			}
			if (node.className) {
				newNode.setAttribute("class", node.className);
				if (!newNode.className) {
						// IE before IE8
					newNode.setAttribute("className", node.className);
				}
			} else {
				newNode.removeAttribute("class");
				newNode.removeAttribute("className");
			}
		}
		
		if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
			if (newNode.className && /\S/.test(newNode.className)) {
				var allowedClasses = this.tags[nodeName].allowedClasses;
				var classNames = newNode.className.trim().split(" ");
				for (var i = classNames.length; --i >= 0;) {
					if (!allowedClasses.test(classNames[i])) {
						HTMLArea._removeClass(newNode, classNames[i]);
					}
				}
			}
		}
		return newNode;
	},
	
	/*
	 * Insert a definition list
	 */
	insertDefinitionList : function () {
		var selection = this.editor._getSelection();
		var endBlocks = this.editor.getEndBlocks(selection);
		var list = null;
		if (this.editor._selectionEmpty(selection)) {
			if (/^(body|div|address|pre|blockquote|li|td|dd)$/i.test(endBlocks.start.nodeName)) {
				list = this.editor._doc.createElement("dl");
				var term = list.appendChild(this.editor._doc.createElement("dt"));
				while (endBlocks.start.firstChild) {
					term.appendChild(endBlocks.start.firstChild);
				}
				list = endBlocks.start.appendChild(list);
			} else if (/^(p|h[1-6])$/i.test(endBlocks.start.nodeName)) {
				var list = endBlocks.start.parentNode.insertBefore(this.editor._doc.createElement("dl"), endBlocks.start);
				endBlocks.start = list.appendChild(endBlocks.start);
				endBlocks.start = this.remapNode(endBlocks.start, "dt");
			}
		} else if (endBlocks.start != endBlocks.end && /^(p|h[1-6])$/i.test(endBlocks.start.nodeName)) {
				// We wrap the selected elements in a dl element
			var paragraphs = endBlocks.start.nodeName.toLowerCase() === "p";
			list = this.wrapSelectionInBlockElement("dl");
			var items = list.childNodes;
			for (var i = 0, n = items.length; i < n; ++i) {
				var paragraphItem = items[i].nodeName.toLowerCase() === "p";
				this.remapNode(items[i],  paragraphs ? ((i % 2) ? "dd" : "dt") : (paragraphItem ? "dd" : "dt"));
			}
		}
		return list;
	},
	
	/*
	 * Indent a definition list
	 */
	indentDefinitionList : function (parentElement, range) {
		var selection = this.editor._getSelection();
		var endBlocks = this.editor.getEndBlocks(selection);
		if (this.editor._selectionEmpty(selection) && /^dd$/i.test(parentElement.nodeName)) {
			var list = parentElement.appendChild(this.editor._doc.createElement("dl"));
			var term = list.appendChild(this.editor._doc.createElement("dt"));
			if (HTMLArea.is_gecko) {
				if (HTMLArea.is_safari) {
					term.innerHTML = "<br />";
				} else {
					term.appendChild(this.editor._doc.createTextNode(""));
				}
			} else {
				term.innerHTML = "\x20";
			}
			this.editor.selectNodeContents(term, false);
			return true;
		} else if (endBlocks.start && /^dt$/i.test(endBlocks.start.nodeName) && endBlocks.start.previousSibling) {
			var sibling = endBlocks.start.previousSibling;
			var bookmark = this.editor.getBookmark(range);
			if (/^dd$/i.test(sibling.nodeName)) {
				var list = this.wrapSelectionInBlockElement("dl");
				list = sibling.appendChild(list);
					// May need to merge the list if it has a previous sibling
				if (list.previousSibling && /^dl$/i.test(list.previousSibling.nodeName)) {
					while (list.firstChild) {
						list.previousSibling.appendChild(list.firstChild);
					}
					HTMLArea.removeFromParent(list);
				}
			} else if (/^dt$/i.test(sibling.nodeName)) {
				var definition = this.editor._doc.createElement("dd");
				definition.appendChild(this.wrapSelectionInBlockElement("dl"));
				sibling.parentNode.insertBefore(definition, sibling.nextSibling);
			}
			this.editor.selectRange(this.editor.moveToBookmark(bookmark));
			return true;
		}
		return false;
	},
	
	/*
	 * Outdent a definition list
	 */
	outdentDefinitionList : function (selection, range) {
		var endBlocks = this.editor.getEndBlocks(selection);
		if (/^dt$/i.test(endBlocks.start.nodeName)
				&& /^dl$/i.test(endBlocks.start.parentNode.nodeName)
				&& /^dd$/i.test(endBlocks.start.parentNode.parentNode.nodeName)
				&& !endBlocks.end.nextSibling) {
			var bookmark = this.editor.getBookmark(range);
			var dl = endBlocks.start.parentNode;
			var dd = dl.parentNode;
			if (this.editor._selectionEmpty(selection)) {
				dd.parentNode.insertBefore(endBlocks.start, dd.nextSibling);
			} else {
				var selected = this.wrapSelectionInBlockElement("dl");
				while (selected.lastChild) {
					dd.parentNode.insertBefore(selected.lastChild, dd.nextSibling);
				}
				selected.parentNode.removeChild(selected);
			}
				// We may have outdented all the child nodes of a list
			if (!dl.hasChildNodes()) {
				dd.removeChild(dl);
				if (!dd.hasChildNodes()) {
					dd.parentNode.removeChild(dd);
				}
			}
			this.editor.selectRange(this.editor.moveToBookmark(bookmark));
			return true;
		}
		return false;
	},
	
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar : function () {
		if (this.getEditorMode() === "textmode" || !this.editor.isEditable()) {
			return false;
		}
		var statusBarSelection = this.editor.getPluginInstance("StatusBar") ? this.editor.getPluginInstance("StatusBar").getSelection() : null;
		var parentElement = statusBarSelection ? statusBarSelection : this.editor.getParentElement();
		if (parentElement.nodeName.toLowerCase() === "body") {
				// The selection is not contained in any block
			for (var buttonId in this.buttonList) {
				if (this.buttonList.hasOwnProperty(buttonId) && this.isButtonInToolbar(buttonId)) {
					switch (buttonId) {
						case 'Outdent':
							this.base();
							break;
					}
				}
			}
			return false;
		}
		while (parentElement && (!HTMLArea.isBlockElement(parentElement) || /^(li)$/i.test(parentElement.nodeName))) {
			parentElement = parentElement.parentNode;
		}
		var blockAncestors = this.getBlockAncestors(parentElement);
		var selection = this.editor._getSelection();
		var endBlocks = this.editor.getEndBlocks(selection);
		for (var buttonId in this.buttonList) {
			commandState = false;
			if (this.buttonList.hasOwnProperty(buttonId) && this.isButtonInToolbar(buttonId)) {
				switch (buttonId) {
					case "Outdent" :
						if (/^(dt)$/i.test(endBlocks.start.nodeName)
								&& /^(dl)$/i.test(endBlocks.start.parentNode.nodeName)
								&& /^(dd)$/i.test(endBlocks.start.parentNode.parentNode.nodeName)
								&& !endBlocks.end.nextSibling) {
							this.editor._toolbarObjects[buttonId].state("enabled", true);
						} else {
							this.base();
						}
						break;
					case "DefinitionList" :
						this.editor._toolbarObjects[buttonId].state("enabled", (this.editor._selectionEmpty() && /^(p|div|address|pre|blockquote|h[1-6]|li|td|dd)$/i.test(endBlocks.start.nodeName))
													|| (endBlocks.start != endBlocks.end && /^(p|h[1-6])$/i.test(endBlocks.start.nodeName)));
						break;
				}
			}
		}
	}
});

