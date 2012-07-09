/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 */
/*
 * Creation of the class of InlineElements plugins
 */
HTMLArea.InlineElements = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the base constructor
	 */
	configurePlugin: function (editor) {
			// Setting the array of allowed attributes on inline elements
		if (this.getPluginInstance('TextStyle')) {
			this.allowedAttributes = this.getPluginInstance('TextStyle').allowedAttributes;
		} else {
			this.allowedAttributes = new Array('id', 'title', 'lang', 'xml:lang', 'dir', 'class', 'itemscope', 'itemtype', 'itemprop');
			if (HTMLArea.isIEBeforeIE9) {
				this.addAllowedAttribute('className');
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
			version		: '2.2',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: this.localize('Technische Universitat Ilmenau'),
			sponsorUrl	: 'http://www.tu-ilmenau.de/',
			license		: 'GPL'
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
				contextMenuTitle: this.localize(buttonId + '-contextMenuTitle'),
				helpText	: this.localize(buttonId + '-helpText'),
				action		: "onButtonPress",
				context		: button[1],
				hide		: false,
				selection	: false,
				iconCls		: 'htmlarea-action-' + button[2]
			};
			this.registerButton(buttonConfiguration);
		}
	},
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: [
		['BiDiOverride', null, 'bidi-override'],
		['Big', null, 'big'],
		['Bold', null, 'bold'],
		['Citation', null, 'citation'],
		['Code', null, 'code'],
		['Definition', null, 'definition'],
		['DeletedText', null, 'deleted-text'],
		['Emphasis', null, 'emphasis'],
		['InsertedText', null, 'inserted-text'],
		['Italic', null, 'italic'],
		['Keyboard', null, 'keyboard'],
		//['Label', null, 'Label'],
		['MonoSpaced', null, 'mono-spaced'],
		['Quotation', null, 'quotation'],
		['Sample', null, 'sample'],
		['Small', null, 'small'],
		['Span', null, 'span'],
		['StrikeThrough', null, 'strike-through'],
		['Strong', null, 'strong'],
		['Subscript', null, 'subscript'],
		['Superscript', null, 'superscript'],
		['Underline', null, 'underline'],
		['Variable', null, 'variable']
	],
	/*
	 * Conversion object: button names to corresponding tag names
	 */
	convertBtn: {
		BiDiOverride	: 'bdo',
		Big		: 'big',
		Bold		: 'b',
		Citation	: 'cite',
		Code		: 'code',
		Definition	: 'dfn',
		DeletedText	: 'del',
		Emphasis	: 'em',
		InsertedText	: 'ins',
		Italic		: 'i',
		Keyboard	: 'kbd',
		//Label		: 'label',
		MonoSpaced	: 'tt',
		Quotation	: 'q',
		Sample		: 'samp',
		Small		: 'small',
		Span		: 'span',
		StrikeThrough	: 'strike',
		Strong		: 'strong',
		Subscript	: 'sub',
		Superscript	: 'sup',
		Underline	: 'u',
		Variable	: 'var'
	 },
	/*
	 * Regular expression to check if an element is an inline elment
	 */
	REInlineElements: /^(b|bdo|big|cite|code|del|dfn|em|i|ins|kbd|label|q|samp|small|span|strike|strong|sub|sup|tt|u|var)$/,
	/*
	 * Function to check if an element is an inline elment
	 */
	isInlineElement: function (el) {
		return el && (el.nodeType === HTMLArea.DOM.ELEMENT_NODE) && this.REInlineElements.test(el.nodeName.toLowerCase());
	},
	/*
	 * This function adds an attribute to the array of allowed attributes on inline elements
	 *
	 * @param	string	attribute: the name of the attribute to be added to the array
	 *
	 * @return	void
	 */
	addAllowedAttribute: function (attribute) {
		this.allowedAttributes.push(attribute);
	},
	/*
	 * This function gets called when some inline element button was pressed.
	 */
	onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		var element = this.convertBtn[buttonId];
		if (element) {
			this.applyInlineElement(editor, element);
			return false;
		} else {
			this.appendToLog('onButtonPress', 'No element corresponding to button: ' + buttonId, 'warn');
		}
	},
	/*
	 * This function gets called when some inline element was selected in the drop-down list
	 */
	onChange: function (editor, combo, record, index) {
		var element = combo.getValue();
		this.applyInlineElement(editor, element, false);
	},
	/*
	 * This function applies to the selection the markup chosen in the drop-down list or corresponding to the button pressed
	 */
	applyInlineElement: function (editor, element) {
		var range = editor.getSelection().createRange();
		var parent = editor.getSelection().getParentElement();
		var ancestors = editor.getSelection().getAllAncestors();
		var elementIsAncestor = false;
		var fullNodeSelected = false;
		if (HTMLArea.isIEBeforeIE9) {
			var bookmark = editor.getBookMark().get(range);
		}
			// Check if the chosen element is among the ancestors
		for (var i = 0; i < ancestors.length; ++i) {
			if ((ancestors[i].nodeType === HTMLArea.DOM.ELEMENT_NODE) && (ancestors[i].nodeName.toLowerCase() == element)) {
				elementIsAncestor = true;
				var elementAncestorIndex = i;
				break;
			}
		}
		if (!editor.getSelection().isEmpty()) {
			var fullySelectedNode = editor.getSelection().getFullySelectedNode();
			fullNodeSelected = this.isInlineElement(fullySelectedNode);
			if (fullNodeSelected) {
				parent = fullySelectedNode;
			}
			var statusBarSelection = (editor.statusBar ? editor.statusBar.getSelection() : null);
			if (element !== "none" && !(fullNodeSelected && elementIsAncestor)) {
					// Add markup
				var newElement = editor.document.createElement(element);
				if (element === "bdo") {
					newElement.setAttribute("dir", "rtl");
				}
				if (!HTMLArea.isIEBeforeIE9) {
					if (fullNodeSelected && statusBarSelection) {
						if (Ext.isWebKit) {
							newElement = parent.parentNode.insertBefore(newElement, statusBarSelection);
							newElement.appendChild(statusBarSelection);
							newElement.normalize();
						} else {
							range.selectNode(parent);
							editor.getDomNode().wrapWithInlineElement(newElement, range);
						}
						editor.getSelection().selectNodeContents(newElement.lastChild, false);
					} else {
						editor.getDomNode().wrapWithInlineElement(newElement, range);
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
							editor.getSelection().selectNodeContents(parent, false);
						} else {
							var content = parent.outerHTML;
							var newElement = this.remapMarkup(parent, element);
							newElement.innerHTML = content;
							editor.getSelection().selectNodeContents(newElement, false);
						}
					} else {
						editor.getDomNode().wrapWithInlineElement(newElement, range);
					}
				}
			} else {
					// A complete node is selected: remove the markup
				if (fullNodeSelected) {
					if (elementIsAncestor) {
						parent = ancestors[elementAncestorIndex];
					}
					var parentElement = parent.parentNode;
					editor.getDomNode().removeMarkup(parent);
					if (Ext.isWebKit && this.isInlineElement(parentElement)) {
						editor.getSelection().selectNodeContents(parentElement, false);
					}
				}
			}
		} else {
				// Remove or remap markup when the selection is collapsed
			if (parent && !HTMLArea.DOM.isBlockElement(parent)) {
				if ((element === 'none') || elementIsAncestor) {
					if (elementIsAncestor) {
						parent = ancestors[elementAncestorIndex];
					}
					editor.getDomNode().removeMarkup(parent);
				} else {
					var bookmark = this.editor.getBookMark().get(range);
					var newElement = this.remapMarkup(parent, element);
					this.editor.getSelection().selectRange(this.editor.getBookMark().moveTo(bookmark));
				}
			}
		}
	},
	/*
	 * This function remaps the given element to the specified tagname
	 */
	remapMarkup: function (element, tagName) {
		var attributeValue;
		var newElement = HTMLArea.DOM.convertNode(element, tagName);
		if (tagName === 'bdo') {
			newElement.setAttribute('dir', 'ltr');
		}
		for (var i = 0; i < this.allowedAttributes.length; ++i) {
			if (attributeValue = element.getAttribute(this.allowedAttributes[i])) {
				newElement.setAttribute(this.allowedAttributes[i], attributeValue);
			}
		}
			// In IE before IE9, the above fails to update the class and style attributes.
		if (HTMLArea.isIEBeforeIE9) {
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
						HTMLArea.DOM.removeClass(newElement, classNames[i]);
					}
				}
			}
		}
		return newElement;
	},
	/*
	* This function gets called when the toolbar is updated
	*/
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		var editor = this.editor;
		if (mode === 'wysiwyg' && editor.isEditable()) {
			var 	tagName = false,
				fullNodeSelected = false;
			var range = editor.getSelection().createRange();
			var parent = editor.getSelection().getParentElement();
			if (parent && !HTMLArea.DOM.isBlockElement(parent)) {
				tagName = parent.nodeName.toLowerCase();
			}
			if (!selectionEmpty) {
				var fullySelectedNode = editor.getSelection().getFullySelectedNode();
				fullNodeSelected = this.isInlineElement(fullySelectedNode);
				if (fullNodeSelected) {
					tagName = fullySelectedNode.nodeName.toLowerCase();
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
					button.setInactive(!activeButton && this.convertBtn[button.itemId] !== tagName);
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

