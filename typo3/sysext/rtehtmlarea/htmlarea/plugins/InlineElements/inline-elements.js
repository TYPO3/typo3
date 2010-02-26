/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Inline Elements Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
/*
 * Creation of the class of InlineElements plugins
 */
InlineElements = HTMLArea.Plugin.extend({
	/*
	 * Let the base class do some initialization work
	 */
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the base constructor
	 */
	configurePlugin : function (editor) {

			// Setting the array of allowed attributes on inline elements
		if (this.editor.plugins.TextStyle && this.editor.plugins.TextStyle.instance) {
			this.allowedAttributes = this.editor.plugins.TextStyle.instance.allowedAttributes;
		} else {
			this.allowedAttributes = new Array("id", "title", "lang", "xml:lang", "dir", "class");
			if (HTMLArea.is_ie) {
				this.addAllowedAttribute("className");
			}
		}
			// Getting tags configuration for inline elements
		if (this.editorConfiguration.buttons.textstyle) {
			this.tags = this.editorConfiguration.buttons.textstyle.tags;
		}
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "2.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: this.localize("Technische Universitat Ilmenau"),
			sponsorUrl	: "http://www.tu-ilmenau.de/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the dropdown list
		 */
		var buttonId = "FormatText";
		var dropDownConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId + "-Tooltip"),
			options		: (this.editorConfiguration.buttons[buttonId.toLowerCase()] ? this.editorConfiguration.buttons[buttonId.toLowerCase()].options : []),
			action		: "onChange"
		};
		if (this.editorConfiguration.buttons.formattext) {
			if (this.editorConfiguration.buttons.formattext.width) {
				dropDownConfiguration.listWidth = parseInt(this.editorConfiguration.buttons.formattext.width, 10);
			}
			if (this.editorConfiguration.buttons.formattext.listWidth) {
				dropDownConfiguration.listWidth = parseInt(this.editorConfiguration.buttons.formattext.listWidth, 10);
			}
			if (this.editorConfiguration.buttons.formattext.maxHeight) {
				dropDownConfiguration.maxHeight = parseInt(this.editorConfiguration.buttons.formattext.maxHeight, 10);
			}
		}
		this.registerDropDown(dropDownConfiguration);
		
		/*
		 * Registering the buttons
		 */
		var n = this.buttonList.length;
		for (var i = 0; i < n; ++i) {
			var button = this.buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + "-Tooltip"),
				action		: "onButtonPress",
				context		: button[1],
				hide		: false,
				selection	: false
			};
			this.registerButton(buttonConfiguration);
		}
	},
	
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList : [
		["BiDiOverride", null],
		["Big", null],
		["Bold", null],
		["Citation", null],
		["Code", null],
		["Definition", null],
		["DeletedText", null],
		["Emphasis", null],
		["InsertedText", null],
		["Italic", null],
		["Keyboard", null],
		//["Label", null],
		["MonoSpaced", null],
		["Quotation", null],
		["Sample", null],
		["Small", null],
		["Span", null],
		["StrikeThrough", null],
		["Strong", null],
		["Subscript", null],
		["Superscript", null],
		["Underline", null],
		["Variable", null]
	],
	
	/*
	 * Conversion object: button names to corresponding tag names
	 */
	convertBtn : {
		BiDiOverride	: "bdo",
		Big		: "big",
		Bold		: "b",
		Citation	: "cite",
		Code		: "code",
		Definition	: "dfn",
		DeletedText	: "del",
		Emphasis	: "em",
		InsertedText	: "ins",
		Italic		: "i",
		Keyboard	: "kbd",
		//Label		: "label",
		MonoSpaced	: "tt",
		Quotation	: "q",
		Sample		: "samp",
		Small		: "small",
		Span		: "span",
		StrikeThrough	: "strike",
		Strong		: "strong",
		Subscript	: "sub",
		Superscript	: "sup",
		Underline	: "u",
		Variable	: "var"
	 },
	
	/*
	 * Regular expression to check if an element is an inline elment
	 */
	REInlineElements : /^(b|bdo|big|cite|code|del|dfn|em|i|ins|kbd|label|q|samp|small|span|strike|strong|sub|sup|tt|u|var)$/,
	
	/*
	 * Function to check if an element is an inline elment
	 */
	isInlineElement : function (el) {
		return el && (el.nodeType === 1) && this.REInlineElements.test(el.nodeName.toLowerCase());
	},
	
	/*
	 * This function adds an attribute to the array of allowed attributes on inline elements
	 *
	 * @param	string	attribute: the name of the attribute to be added to the array
	 *
	 * @return	void
	 */
	addAllowedAttribute : function (attribute) {
		this.allowedAttributes.push(attribute);
	},
	
	/*
	 * This function gets called when some inline element button was pressed.
	 */
	onButtonPress : function (editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		var element = this.convertBtn[buttonId];
		if (element) {
			this.applyInlineElement(editor, element);
			return false;
		} else {
			this.appendToLog("onButtonPress", "No element corresponding to button: " + buttonId);
		}
	},
	
	/*
	 * This function gets called when some inline element was selected in the drop-down list
	 */
	onChange : function (editor, combo, record, index) {
		var element = combo.getValue();
		this.applyInlineElement(editor, element, false);
	},
	
	/*
	 * This function applies to the selection the markup chosen in the drop-down list or corresponding to the button pressed
	 */
	applyInlineElement : function (editor, element) {
		editor.focusEditor();
		var selection = editor._getSelection();
		var range = editor._createRange(selection);
		var parent = editor.getParentElement(selection, range);
		var ancestors = editor.getAllAncestors();
		var elementIsAncestor = false;
		var selectionEmpty = editor._selectionEmpty(selection);
		if (HTMLArea.is_ie) {
			var bookmark = editor.getBookmark(range);
		}
			// Check if the chosen element is among the ancestors
		for (var i = 0; i < ancestors.length; ++i) {
			if ((ancestors[i].nodeType == 1) && (ancestors[i].nodeName.toLowerCase() == element)) {
				elementIsAncestor = true;
				var elementAncestorIndex = i;
				break;
			}
		}
		if (!selectionEmpty) {
			var statusBarSelection = (editor.statusBar ? editor.statusBar.getSelection() : null);
				// The selection is not empty.
			for (var i = 0; i < ancestors.length; ++i) {
				fullNodeSelected = (HTMLArea.is_ie && ((selection.type !== "Control" && ancestors[i].innerText === range.text) || (selection.type === "Control" && ancestors[i].innerText === range.item(0).text)))
							|| (HTMLArea.is_gecko && ((statusBarSelection === ancestors[i] && ancestors[i].textContent === range.toString()) || (!statusBarSelection && ancestors[i].textContent === range.toString())));
				if (fullNodeSelected) {
					if (!HTMLArea.isBlockElement(ancestors[i])) {
						parent = ancestors[i];
					}
					break;
				}
			}
				// Working around bug in Safari selectNodeContents
			if (!fullNodeSelected && HTMLArea.is_safari && statusBarSelection && this.isInlineElement(statusBarSelection) && statusBarSelection.textContent === range.toString()) {
				fullNodeSelected = true;
				parent = statusBarSelection;
			}
			
			var fullNodeTextSelected = (HTMLArea.is_gecko && parent.textContent === range.toString())
							|| (HTMLArea.is_ie && parent.innerText === range.text);
			if (fullNodeTextSelected && elementIsAncestor) {
				fullNodeSelected = true;
			}
			if (element !== "none" && !(fullNodeSelected && elementIsAncestor)) {
					// Add markup
				var newElement = editor._doc.createElement(element);
				if (element === "bdo") {
					newElement.setAttribute("dir", "rtl");
				}
				if (HTMLArea.is_gecko) {
					if (fullNodeSelected && statusBarSelection) {
						if (HTMLArea.is_safari) {
							editor.selectNode(parent);
							selection = editor._getSelection();
							range = editor._createRange(selection);
						} else {
							range.selectNode(parent);
						}
					}
					editor.wrapWithInlineElement(newElement, selection, range);
					if (fullNodeSelected && statusBarSelection && !HTMLArea.is_safari) {
						editor.selectNodeContents(newElement.lastChild, false);
					}
					range.detach();
				} else {
					var tagopen = "<" + element + ">";
					var tagclose = "</" + element + ">";
					if (fullNodeSelected) {
						if (!statusBarSelection) {
							parent.innerHTML = tagopen + parent.innerHTML + tagclose;
							if (element === "bdo") {
								parent.firstChild.setAttribute("dir", "rtl");
							}
							editor.selectNodeContents(parent, false);
						} else {
							var content = parent.outerHTML;
							var newElement = this.remapMarkup(parent, element);
							newElement.innerHTML = content;
							editor.selectNodeContents(newElement, false);
						}
					} else {
						editor.wrapWithInlineElement(newElement, selection, range);
					}
				}
			} else {
					// A complete node is selected: remove the markup
				if (fullNodeSelected) {
					if (elementIsAncestor) {
						parent = ancestors[elementAncestorIndex];
					}
					editor.removeMarkup(parent);
				}
			}
		} else {
				// Remove or remap markup when the selection is collapsed
			if (parent && !HTMLArea.isBlockElement(parent)) {
				if ((element === "none") || elementIsAncestor) {
					if (elementIsAncestor) {
						parent = ancestors[elementAncestorIndex];
					}
					editor.removeMarkup(parent);
				} else {
					var bookmark = this.editor.getBookmark(range);
					var newElement = this.remapMarkup(parent, element);
					this.editor.selectRange(this.editor.moveToBookmark(bookmark));
				}
			}
		}
	},
	
	/*
	 * This function remaps the given element to the specified tagname
	 */
	remapMarkup : function(element, tagName) {
		var attributeValue;
		var newElement = this.editor.convertNode(element, tagName);
		if (tagName === "bdo") {
			newElement.setAttribute("dir", "ltr");
		}
		for (var i = 0; i < this.allowedAttributes.length; ++i) {
			if (attributeValue = element.getAttribute(this.allowedAttributes[i])) {
				newElement.setAttribute(this.allowedAttributes[i], attributeValue);
			}
		}
			// In IE, the above fails to update the class and style attributes.
		if (HTMLArea.is_ie) {
			if (element.style.cssText) {
				newElement.style.cssText = element.style.cssText;
			}
			if (element.className) {
				newElement.setAttribute("class", element.className);
				if (!newElement.className) {
						// IE before IE8
					newElement.setAttribute("className", element.className);
				}
			} else {
				newElement.removeAttribute("class");
					// IE before IE8
				newElement.removeAttribute("className");
			}
		}
		
		if (this.tags && this.tags[tagName] && this.tags[tagName].allowedClasses) {
			if (newElement.className && /\S/.test(newElement.className)) {
				var allowedClasses = this.tags[tagName].allowedClasses;
				classNames = newElement.className.trim().split(" ");
				for (var i = 0; i < classNames.length; ++i) {
					if (!allowedClasses.test(classNames[i])) {
						HTMLArea._removeClass(newElement, classNames[i]);
					}
				}
			}
		}
		return newElement;
	},
	
	/*
	* This function gets called when the toolbar is updated
	*/
	onUpdateToolbar : function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		var editor = this.editor;
		if (mode === "wysiwyg" && editor.isEditable()) {
			var tagName = false, fullNodeSelected = false;
			var sel = editor._getSelection();
			var range = editor._createRange(sel);
			var parent = editor.getParentElement(sel);
			if (parent && !HTMLArea.isBlockElement(parent)) {
				tagName = parent.nodeName.toLowerCase();
			}
			if (!selectionEmpty) {
				var statusBarSelection = editor.statusBar ? editor.statusBar.getSelection() : null;
				for (var i = 0, n = ancestors.length; i < n; ++i) {
					fullNodeSelected = (statusBarSelection === ancestors[i])
						&& ((HTMLArea.is_gecko && ancestors[i].textContent === range.toString()) || (HTMLArea.is_ie && ((sel.type !== "Control" && ancestors[i].innerText === range.text) || (sel.type === "Control" && ancestors[i].innerText === range.item(0).text))));
					if (fullNodeSelected) {
						if (!HTMLArea.isBlockElement(ancestors[i])) {
							tagName = ancestors[i].nodeName.toLowerCase();
						}
						break;
					}
				}
					// Working around bug in Safari selectNodeContents
				if (!fullNodeSelected && HTMLArea.is_safari && statusBarSelection && this.isInlineElement(statusBarSelection) && statusBarSelection.textContent === range.toString()) {
					fullNodeSelected = true;
					tagName = statusBarSelection.nodeName.toLowerCase();
				}
			}
			var selectionInInlineElement = tagName && this.REInlineElements.test(tagName);
			var disabled = !endPointsInSameBlock || (fullNodeSelected && !tagName) || (selectionEmpty && !selectionInInlineElement);
			switch (button.itemId) {
				case 'FormatText':
					this.updateValue(editor, button, tagName, selectionEmpty, fullNodeSelected, disabled);
					break;
				default:
					var activeButton = false;
					Ext.each(ancestors, function (ancestor) {
						if (ancestor && this.convertBtn[button.itemId] === ancestor.nodeName.toLowerCase()) {
							activeButton = true;
							return false;
						} else {
							return true;
						}
					}, this);
					button.setInactive(!activeButton);
					button.setDisabled(disabled);
					break;
			}
		}
	},
	
	/*
	* This function updates the drop-down list of inline elemenents
	*/
	updateValue: function (editor, select, tagName, selectionEmpty, fullNodeSelected, disabled) {
		var store = select.getStore();
		store.removeAt(0);
		if ((store.findExact('value', tagName) != -1) && (selectionEmpty || fullNodeSelected)) {
			select.setValue(tagName);
			store.insert(0, new store.recordType({
				text: this.localize('Remove markup'),
				value: 'none'
			}));
		} else {
			store.insert(0, new store.recordType({
				text: this.localize('No markup'),
				value: 'none'
			}));
			select.setValue('none');
		}
		select.setDisabled(!(store.getCount()>1) || disabled);
	}
});

