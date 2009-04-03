/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * TYPO3 SVN ID: $Id: inline-elements.js $
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
		this.allowedAttributes = new Array("id", "title", "lang", "xml:lang", "dir", "class");
		if (HTMLArea.is_ie) {
			this.allowedAttributes.push("className");
		}

		if (this.editorConfiguration.buttons.textstyle) {
			this.tags = this.editorConfiguration.buttons.textstyle.tags;
		}
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.1",
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
			options		: (this.editorConfiguration.buttons[buttonId.toLowerCase()]?this.editorConfiguration.buttons[buttonId.toLowerCase()]["dropDownOptions"]:null),
			action		: "onChange",
			refresh		: null
		};
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
	 * This function gets called when some inline element button was pressed.
	 */
	onButtonPress : function (editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		var obj = editor._toolbarObjects[buttonId];
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
	onChange : function (editor, buttonId) {
		var tbobj = editor._toolbarObjects[buttonId];
		var element = document.getElementById(tbobj.elementId).value;
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
			var bookmark = range.getBookmark();
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
				// The selection is not empty.
			for (var i = 0; i < ancestors.length; ++i) {
				fullNodeSelected = (HTMLArea.is_ie && ((editor._statusBarTree.selected === ancestors[i] && ancestors[i].innerText === range.text) || (!editor._statusBarTree.selected && ancestors[i].innerText === range.text)))
							|| (HTMLArea.is_gecko && ((editor._statusBarTree.selected === ancestors[i] && ancestors[i].textContent === range.toString()) || (!editor._statusBarTree.selected && ancestors[i].textContent === range.toString())));
				if (fullNodeSelected) {
					if (!HTMLArea.isBlockElement(ancestors[i])) {
						parent = ancestors[i];
					}
					break;
				}
			}
				// Working around bug in Safari selectNodeContents
			if (!fullNodeSelected && HTMLArea.is_safari && this.editor._statusBarTree.selected && this.isInlineElement(this.editor._statusBarTree.selected) && this.editor._statusBarTree.selected.textContent === range.toString()) {
				fullNodeSelected = true;
				parent = this.editor._statusBarTree.selected;
			}
			
			var fullNodeTextSelected = (HTMLArea.is_gecko && parent.textContent === range.toString())
							|| (HTMLArea.is_ie && parent.innerText === range.text);
			if (fullNodeTextSelected && elementIsAncestor) {
				fullNodeSelected = true;
			}
			if (element !== "none" && !(fullNodeSelected && elementIsAncestor)) {
					// Add markup
				if (HTMLArea.is_gecko) {
					if (fullNodeSelected && editor._statusBarTree.selected) {
						if (HTMLArea.is_safari) {
							this.editor.selectNode(parent);
							range = this.editor._createRange(this.editor._getSelection());
						} else {
							range.selectNode(parent);
						}
					}
					var newElement = this.editor._doc.createElement(element);
					if (element === "bdo") {
						newElement.setAttribute("dir", "rtl");
					}
						// Sometimes Opera 9.25 raises a bad boundary points error
					if (HTMLArea.is_opera) {
						try {
							range.surroundContents(newElement);
						} catch(e) {
							newElement.appendChild(range.extractContents());
							range.insertNode(newElement);
						}
					} else {
						range.surroundContents(newElement);
					}
						// Sometimes Firefox inserts empty elements just outside the boundaries of the range
					var neighbour = newElement.previousSibling;
					if (neighbour && (neighbour.nodeType != 3) && !/\S/.test(neighbour.textContent)) {
						HTMLArea.removeFromParent(neighbour);
					}
					neighbour = newElement.nextSibling;
					if (neighbour && (neighbour.nodeType != 3) && !/\S/.test(neighbour.textContent)) {
						HTMLArea.removeFromParent(neighbour);
					}
					if (fullNodeSelected && editor._statusBarTree.selected && !HTMLArea.is_safari) {
						this.editor.selectNodeContents(newElement.lastChild, false);
					} else {
						this.editor.selectNodeContents(newElement, false);
					}
					range.detach();
				} else {
					var tagopen = "<" + element + ">";
					var tagclose = "</" + element + ">";
					if (fullNodeSelected) {
						if (!editor._statusBarTree.selected) {
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
						var rangeStart = range.duplicate();
						rangeStart.collapse(true);
						var parentStart = rangeStart.parentElement();
						var rangeEnd = range.duplicate();
						rangeEnd.collapse(true);
						var newRange = editor._createRange();
						
						var parentEnd = rangeEnd.parentElement();
						var upperParentStart = parentStart;
						if (parentStart !== parent) {
							while (upperParentStart.parentNode !== parent) {
								upperParentStart = upperParentStart.parentNode;
							}
						}
						
						var newElement = editor._doc.createElement(element);
						newElement.innerHTML = range.htmlText;
							// IE eats spaces on the start boundary
						if (range.htmlText.charAt(0) === "\x20") {
							newElement.innerHTML = "&nbsp;" + newElement.innerHTML;
						}
						var newElementClone = newElement.cloneNode(true);
						range.pasteHTML(newElement.outerHTML);
							// IE inserts the element as the last child of the start container
						if (parentStart !== parent
								&& parentStart.lastChild
								&& parentStart.lastChild.nodeType === 1
								&& parentStart.lastChild.nodeName.toLowerCase() === element) {
							parent.insertBefore(newElementClone, upperParentStart.nextSibling);
							parentStart.removeChild(parentStart.lastChild);
								// Sometimes an empty previous sibling was created
							if (newElementClone.previousSibling
									&& newElementClone.previousSibling.nodeType === 1
									&& !newElementClone.previousSibling.innerText) {
								parent.removeChild(newElementClone.previousSibling);
							}
								// The bookmark will not work anymore
							newRange.moveToElementText(newElementClone);
							newRange.collapse(false);
							newRange.select();
						} else {
								// Working around IE boookmark bug
							if (parentStart != parentEnd) {
								var newRange = editor._createRange();
								if (newRange.moveToBookmark(bookmark)) {
									newRange.collapse(false);
									newRange.select();
								}
							} else {
								range.collapse(false);
							}
						}
						try { // normalize() is not available in IE5.5
							parent.normalize();
						} catch(e) { }
					}
				}
			} else {
					// A complete node is selected: remove the markup
				if (fullNodeSelected) {
					if (elementIsAncestor) {
						parent = ancestors[elementAncestorIndex];
					}
					this.removeMarkup(parent);
				}
			}
		} else {
				// Remove or remap markup when the selection is collapsed
			if (parent && !HTMLArea.isBlockElement(parent)) {
				if ((element === "none") || elementIsAncestor) {
					if (elementIsAncestor) {
						parent = ancestors[elementAncestorIndex];
					}
					this.removeMarkup(parent);
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
	 * This function removes the given markup element
	 */
	removeMarkup : function(element) {
		var bookmark = this.editor.getBookmark(this.editor._createRange(this.editor._getSelection()));
		var parent = element.parentNode;
		while (element.firstChild) {
			parent.insertBefore(element.firstChild, element);
		}
		parent.removeChild(element);
		this.editor.selectRange(this.editor.moveToBookmark(bookmark));
	},
	
	/*
	* This function gets called when the toolbar is updated
	*/
	onUpdateToolbar : function () {
		var editor = this.editor;
		if (editor.getMode() === "wysiwyg" && editor.isEditable()) {
			var id, activeButton;
			var tagName = false, endPointsInSameBlock = true, fullNodeSelected = false;
			var sel = editor._getSelection();
			var range = editor._createRange(sel);
			var parent = editor.getParentElement(sel);
			if (parent && !HTMLArea.isBlockElement(parent)) {
				tagName = parent.nodeName.toLowerCase();
			}
			var selectionEmpty = editor._selectionEmpty(sel);
			if (!selectionEmpty) {
				var ancestors = editor.getAllAncestors();
				for (var i = 0; i < ancestors.length; ++i) {
					fullNodeSelected = (editor._statusBarTree.selected === ancestors[i])
						&& ((HTMLArea.is_gecko && ancestors[i].textContent === range.toString()) || (HTMLArea.is_ie && ancestors[i].innerText === range.text));
					if (fullNodeSelected) {
						if (!HTMLArea.isBlockElement(ancestors[i])) {
							tagName = ancestors[i].nodeName.toLowerCase();
						}
						break;
					}
				}
					// Working around bug in Safari selectNodeContents
				if (!fullNodeSelected && HTMLArea.is_safari && this.editor._statusBarTree.selected && this.isInlineElement(this.editor._statusBarTree.selected) && this.editor._statusBarTree.selected.textContent === range.toString()) {
					fullNodeSelected = true;
					tagName = this.editor._statusBarTree.selected.nodeName.toLowerCase();
				}
			}
			var selectionInInlineElement = tagName && this.REInlineElements.test(tagName);
			var disabled = !this.endPointsInSameBlock() || (fullNodeSelected && !tagName) || (selectionEmpty && !selectionInInlineElement);
			
			var obj = editor.config.customSelects["FormatText"];
			if ((typeof(obj) !== "undefined") && (typeof(editor._toolbarObjects[obj.id]) !== "undefined")) {
				this.updateValue(editor, obj, tagName, selectionEmpty, fullNodeSelected, disabled);
			}
			
			var ancestors = editor.getAllAncestors();
			var bl = this.buttonList;
			for (var i = 0; i < bl.length; ++i) {
				var btn = bl[i];
				id = btn[0];
				var obj = editor._toolbarObjects[id];
				if ((typeof(obj) !== "undefined")) {
					activeButton = false;
					for (var j = ancestors.length; --j >= 0;) {
						var el = ancestors[j];
						if (!el) { continue; }
						if (this.convertBtn[id] === el.nodeName.toLowerCase()) {
							activeButton = true;
						}
					}
					obj.state("active", activeButton);
					obj.state("enabled", !disabled);
				}
			}
		}
	},
	
	/*
	 * This function determines if the end poins of the current selection are within the same block
	 */
	endPointsInSameBlock : function() {
		var selection = this.editor._getSelection();
		if (this.editor._selectionEmpty(selection)) {
			return true;
		} else {
			var parent = this.editor.getParentElement(selection);
			var endBlocks = this.editor.getEndBlocks(selection);
			return (endBlocks.start === endBlocks.end && !/^(table|thead|tbody|tfoot|tr)$/i.test(parent.nodeName));
		}
	},
	
	/*
	* This function updates the drop-down list of inline elemenents
	*/
	updateValue : function (editor, obj, tagName, selectionEmpty, fullNodeSelected, disabled) {
		var select = document.getElementById(editor._toolbarObjects[obj.id]["elementId"]);
		var options = select.options;
		for (var i = options.length; --i >= 0;) {
			options[i].selected = false;
		}
		select.selectedIndex = 0;
		options[0].selected = true;
		select.options[0].text = this.localize("No markup");
		for (i = options.length; --i >= 0;) {
			if (tagName === options[i].value) {
				if (selectionEmpty || fullNodeSelected) {
					options[i].selected = true;
					select.selectedIndex = i;
					select.options[0].text = this.localize("Remove markup");
				}
				break;
			}
		}
		
		select.disabled = !(options.length>1) || disabled;
		select.className = "";
		if (select.disabled) {
			select.className = "buttonDisabled";
		}
	}
});

