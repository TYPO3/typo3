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
 * Inline Elements Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, UserAgent, Dom, Util) {

	var InlineElements = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(InlineElements, Plugin);
	Util.apply(InlineElements.prototype, {

		/**
		 * This function gets called by the base constructor
		 */
		configurePlugin: function (editor) {
			this.buttonsConfiguration = this.editorConfiguration.buttons;
			// Setting the array of allowed attributes on inline elements
			if (this.getPluginInstance('TextStyle')) {
				this.allowedAttributes = this.getPluginInstance('TextStyle').allowedAttributes;
			} else {
				this.allowedAttributes = new Array('id', 'title', 'lang', 'xml:lang', 'dir', 'class', 'itemscope', 'itemtype', 'itemprop');
			}
			// Getting tags configuration for inline elements
			if (this.buttonsConfiguration.textstyle) {
				this.tags = this.buttonsConfiguration.textstyle.tags;
			}

			/**
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

			/**
			 * Registering the dropdown list
			 */
			var buttonId = 'FormatText';
			// Wrap the options text in the corresponding inline element
			var options = this.buttonsConfiguration[buttonId.toLowerCase()] && this.buttonsConfiguration[buttonId.toLowerCase()].options ? this.buttonsConfiguration[buttonId.toLowerCase()].options : [];
			for (var i = 0, n = options.length; i < n; i++) {
				options[i][0] = '<' + options[i][1] + '>' + options[i][0] + '</' + options[i][1] + '>';
			}
			var dropDownConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + '-Tooltip'),
				options		: options,
				action		: 'onChange'
			};
			if (this.buttonsConfiguration.formattext) {
				if (this.buttonsConfiguration.formattext.width) {
					dropDownConfiguration.width = parseInt(this.buttonsConfiguration.formattext.width, 10);
				}
				if (this.buttonsConfiguration.formattext.listWidth) {
					dropDownConfiguration.listWidth = parseInt(this.buttonsConfiguration.formattext.listWidth, 10);
				}
				if (this.buttonsConfiguration.formattext.maxHeight) {
					dropDownConfiguration.maxHeight = parseInt(this.buttonsConfiguration.formattext.maxHeight, 10);
				}
			}
			this.registerDropDown(dropDownConfiguration);

			/**
			 * Registering the buttons
			 */
			var buttonList = this.buttonList, button, buttonId;
			for (var i = 0, n = buttonList.length; i < n; ++i) {
				button = buttonList[i];
				buttonId = button[0];
				var buttonConfiguration = {
					id		: buttonId,
					tooltip		: this.localize(buttonId + '-Tooltip'),
					contextMenuTitle: this.localize(buttonId + '-contextMenuTitle'),
					helpText	: this.localize(buttonId + '-helpText'),
					action		: 'onButtonPress',
					context		: button[1],
					hide		: false,
					selection	: false,
					iconCls		: 'htmlarea-action-' + button[2]
				};
				this.registerButton(buttonConfiguration);
			}
			return true;
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
			return el && (el.nodeType === Dom.ELEMENT_NODE) && this.REInlineElements.test(el.nodeName.toLowerCase());
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

		/**
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

		/**
		 * This function gets called when some inline element was selected in the drop-down list
		 */
		onChange: function (editor, select) {
			var element = select.getValue();
			this.applyInlineElement(editor, element, false);
		},

		/**
		 * This function applies to the selection the markup chosen in the drop-down list or corresponding to the button pressed
		 */
		applyInlineElement: function (editor, element) {
			var range = editor.getSelection().createRange();
			var parent = editor.getSelection().getParentElement();
			var ancestors = editor.getSelection().getAllAncestors();
			var elementIsAncestor = false;
			var fullNodeSelected = false;
			// Check if the chosen element is among the ancestors
			for (var i = 0; i < ancestors.length; ++i) {
				if ((ancestors[i].nodeType === Dom.ELEMENT_NODE) && (ancestors[i].nodeName.toLowerCase() == element)) {
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
					if (fullNodeSelected && statusBarSelection) {
						if (UserAgent.isWebKit) {
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
						// A complete node is selected: remove the markup
					if (fullNodeSelected) {
						if (elementIsAncestor) {
							parent = ancestors[elementAncestorIndex];
						}
						var parentElement = parent.parentNode;
						editor.getDomNode().removeMarkup(parent);
						if (UserAgent.isWebKit && this.isInlineElement(parentElement)) {
							editor.getSelection().selectNodeContents(parentElement, false);
						}
					}
				}
			} else {
					// Remove or remap markup when the selection is collapsed
				if (parent && !Dom.isBlockElement(parent)) {
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

		/**
		 * This function remaps the given element to the specified tagname
		 */
		remapMarkup: function (element, tagName) {
			var attributeValue;
			var newElement = Dom.convertNode(element, tagName);
			if (tagName === 'bdo') {
				newElement.setAttribute('dir', 'ltr');
			}
			for (var i = 0; i < this.allowedAttributes.length; ++i) {
				if (attributeValue = element.getAttribute(this.allowedAttributes[i])) {
					newElement.setAttribute(this.allowedAttributes[i], attributeValue);
				}
			}
			if (this.tags && this.tags[tagName] && this.tags[tagName].allowedClasses) {
				if (newElement.className && /\S/.test(newElement.className)) {
					var allowedClasses = this.tags[tagName].allowedClasses;
					classNames = newElement.className.trim().split(" ");
					for (var i = 0; i < classNames.length; ++i) {
						if (!allowedClasses.test(classNames[i])) {
							Dom.removeClass(newElement, classNames[i]);
						}
					}
				}
			}
			return newElement;
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
			var editor = this.editor;
			if (mode === 'wysiwyg' && editor.isEditable()) {
				var 	tagName = false,
					fullNodeSelected = false;
				var range = editor.getSelection().createRange();
				var parent = editor.getSelection().getParentElement();
				if (parent && !Dom.isBlockElement(parent)) {
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
						for (var i = ancestors.length; --i >= 0;) {
							var ancestor = ancestors[i];
							if (ancestor && this.convertBtn[button.itemId] === ancestor.nodeName.toLowerCase()) {
								activeButton = true;
								break;
							}
						}
						button.setInactive(!activeButton && this.convertBtn[button.itemId] !== tagName);
						button.setDisabled(disabled);
						break;
				}
			}
		},

		/**
		 * This function updates the drop-down list of inline elemenents
		 */
		updateValue: function (editor, select, tagName, selectionEmpty, fullNodeSelected, disabled) {
			if ((select.findValue(tagName) !== -1) && (selectionEmpty || fullNodeSelected)) {
				var text = this.localize('Remove markup');
				select.setFirstOption(text, 'none', text);
				select.setValue(tagName);
			} else {
				var text = this.localize('No markup');
				select.setFirstOption(text, 'none', text);
				select.setValueByIndex(0);
			}
			select.setDisabled(!(select.getCount() > 1) || disabled);
		}
	});

	return InlineElements;

});
