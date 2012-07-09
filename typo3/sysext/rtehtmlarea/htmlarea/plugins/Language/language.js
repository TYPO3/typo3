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
 * Language Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.Language = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		/*
		 * Setting up some properties from PageTSConfig
		 */
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		this.useAttribute = {};
		this.useAttribute.lang = (this.buttonsConfiguration.language && this.buttonsConfiguration.language.useLangAttribute) ? this.buttonsConfiguration.language.useLangAttribute : true;
		this.useAttribute.xmlLang = (this.buttonsConfiguration.language && this.buttonsConfiguration.language.useXmlLangAttribute) ? this.buttonsConfiguration.language.useXmlLangAttribute : false;
		if (!this.useAttribute.lang && !this.useAttribute.xmlLang) {
			this.useAttribute.lang = true;
		}

			// Importing list of allowed attributes
		if (this.getPluginInstance('TextStyle')) {
			this.allowedAttributes = this.getPluginInstance('TextStyle').allowedAttributes;
		}
		if (!this.allowedAttributes && this.getPluginInstance('InlineElements')) {
			this.allowedAttributes = this.getPluginInstance('InlineElements').allowedAttributes;
		}
		if (!this.allowedAttributes && this.getPluginInstance('BlockElements')) {
			this.allowedAttributes = this.getPluginInstance('BlockElements').allowedAttributes;
		}
		if (!this.allowedAttributes) {
			this.allowedAttributes = new Array('id', 'title', 'lang', 'xml:lang', 'dir', 'class');
			if (HTMLArea.isIEBeforeIE9) {
				this.allowedAttributes.push('className');
			}
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
		 * Registering the buttons
		 */
		var buttonList = this.buttonList, buttonId;
		for (var i = 0, n = buttonList.length; i < n; ++i) {
			var button = buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + '-Tooltip'),
				iconCls		: 'htmlarea-action-' + button[2],
				action		: 'onButtonPress',
				context		: button[1]
			};
			this.registerButton(buttonConfiguration);
		}
		/*
		 * Registering the dropdown list
		 */
		var buttonId = 'Language';
		if (this.buttonsConfiguration[buttonId.toLowerCase()] && this.buttonsConfiguration[buttonId.toLowerCase()].dataUrl) {
			var dropDownConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + '-Tooltip'),
				storeUrl	: this.buttonsConfiguration[buttonId.toLowerCase()].dataUrl,
				action		: 'onChange'
			};
			if (this.buttonsConfiguration.language) {
				dropDownConfiguration.width = this.buttonsConfiguration.language.width ? parseInt(this.buttonsConfiguration.language.width, 10) : 200;
				if (this.buttonsConfiguration.language.listWidth) {
					dropDownConfiguration.listWidth = parseInt(this.buttonsConfiguration.language.listWidth, 10);
				}
				if (this.buttonsConfiguration.language.maxHeight) {
					dropDownConfiguration.maxHeight = parseInt(this.buttonsConfiguration.language.maxHeight, 10);
				}
			}
			this.registerDropDown(dropDownConfiguration);
		}
		return true;
	},
	/*
	 * The list of buttons added by this plugin
	 */
	buttonList: [
		['LeftToRight', null, 'text-direction-left-to-right'],
		['RightToLeft', null, 'text-direction-right-to-left'],
		['ShowLanguageMarks', null, 'language-marks-show']
	],
	/*
	 * This function gets called when the editor is generated
	 */
	onGenerate: function () {
		var select = this.getButton('Language');
		if (select) {
			if (select.getStore().getCount() > 1) {
				this.addLanguageMarkingRules();
			} else {
					// Monitor the language combo's store being loaded
				select.mon(select.getStore(), 'load', function () {
					this.addLanguageMarkingRules();
					var selection = this.editor.getSelection(),
						selectionEmpty = selection.isEmpty(),
						ancestors = selection.getAllAncestors(),
						endPointsInSameBlock = selection.endPointsInSameBlock();
					this.onUpdateToolbar(select, this.getEditorMode(), selectionEmpty, ancestors, endPointsInSameBlock);
				}, this);
			}
		}
	},
	/*
	 * This function adds rules to the stylesheet for language mark highlighting
	 * Model: body.htmlarea-show-language-marks *[lang=en]:before { content: "en: "; }
	 * Works in IE8, but not in earlier versions of IE
	 */
	addLanguageMarkingRules: function () {
		var select = this.getButton('Language');
		if (select) {
			var styleSheet = this.editor.document.styleSheets[0];
			select.getStore().each(function (option) {
				var selector = 'body.htmlarea-show-language-marks *[' + 'lang="' + option.get('value') + '"]:before';
				var style = 'content: "' + option.get('value') + ': ";';
				var rule = selector + ' { ' + style + ' }';
				if (!HTMLArea.isIEBeforeIE9) {
					try {
						styleSheet.insertRule(rule, styleSheet.cssRules.length);
					} catch (e) {
						this.appendToLog('onGenerate', 'Error inserting css rule: ' + rule + ' Error text: ' + e, 'warn');
					}
				} else {
					styleSheet.addRule(selector, style);
				}
				return true;
			}, this);
		}
	},
	/*
	 * This function gets called when a button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;

		switch (buttonId) {
			case 'RightToLeft':
			case 'LeftToRight':
				this.setDirAttribute(buttonId);
				break;
			case 'ShowLanguageMarks':
				this.toggleLanguageMarks();
				break;
			default	:
				break;
		}
		return false;
	},
	/*
	 * Sets the dir attribute
	 *
	 * @param	string		buttonId: the button id
	 *
	 * @return	void
	 */
	setDirAttribute: function (buttonId) {
		var direction = (buttonId == 'RightToLeft') ? 'rtl' : 'ltr';
		var element = this.editor.getSelection().getParentElement();
		if (element) {
			if (/^bdo$/i.test(element.nodeName)) {
				element.dir = direction;
			} else {
				element.dir = (element.dir == direction || element.style.direction == direction) ? '' : direction;
			}
			element.style.direction = '';
		}
	 },
	/*
	 * Toggles the display of language marks
	 *
	 * @param	boolean		forceLanguageMarks: if set, language marks are displayed whatever the current state
	 *
	 * @return	void
	 */
	toggleLanguageMarks: function (forceLanguageMarks) {
		var body = this.editor.document.body;
		if (!HTMLArea.DOM.hasClass(body, 'htmlarea-show-language-marks')) {
			HTMLArea.DOM.addClass(body,'htmlarea-show-language-marks');
		} else if (!forceLanguageMarks) {
			HTMLArea.DOM.removeClass(body,'htmlarea-show-language-marks');
		}
	},
	/*
	 * This function gets called when some language was selected in the drop-down list
	 */
	onChange: function (editor, combo, record, index) {
		this.applyLanguageMark(combo.getValue());
	},
	/*
	 * This function applies the langauge mark to the selection
	 */
	applyLanguageMark: function (language) {
		var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
		var range = this.editor.getSelection().createRange();
		var parent = this.editor.getSelection().getParentElement();
		var selectionEmpty = this.editor.getSelection().isEmpty();
		var endPointsInSameBlock = this.editor.getSelection().endPointsInSameBlock();
		var fullNodeSelected = false;
		if (!selectionEmpty) {
			if (endPointsInSameBlock) {
				var ancestors = this.editor.getSelection().getAllAncestors();
				for (var i = 0; i < ancestors.length; ++i) {
					fullNodeSelected =  (!HTMLArea.isIEBeforeIE9 && ((statusBarSelection === ancestors[i] && ancestors[i].textContent === range.toString()) || (!statusBarSelection && ancestors[i].textContent === range.toString())))
						|| (HTMLArea.isIEBeforeIE9 && statusBarSelection === ancestors[i] && ((this.editor.getSelection().getType() !== 'Control' && ancestors[i].innerText === range.text) || (this.editor.getSelection().getType() === 'Control' && ancestors[i].innerText === range.item(0).text)));
					if (fullNodeSelected) {
						parent = ancestors[i];
						break;
					}
				}
					// Working around bug in Safari selectNodeContents
				if (!fullNodeSelected && Ext.isWebKit && statusBarSelection && statusBarSelection.textContent === range.toString()) {
					fullNodeSelected = true;
					parent = statusBarSelection;
				}
			}
		}
		if (selectionEmpty || fullNodeSelected) {
				// Selection is empty or parent is selected in the status bar
			if (parent) {
					// Set language attributes
				this.setLanguageAttributes(parent, language);
			}
		} else if (endPointsInSameBlock) {
				// The selection is not empty, nor full element
			if (language != 'none') {
					// Add span element with lang attribute(s)
				var newElement = this.editor.document.createElement('span');
				this.setLanguageAttributes(newElement, language);
				this.editor.getDomNode().wrapWithInlineElement(newElement, range);
				if (!HTMLArea.isIEBeforeIE9) {
					range.detach();
				}
			}
		} else {
			this.setLanguageAttributeOnBlockElements(language);
		}
	},

	/*
	 * This function gets the language attribute on the given element
	 *
	 * @param	object		element: the element from which to retrieve the attribute value
	 *
	 * @return	string		value of the lang attribute, or of the xml:lang attribute
	 */
	getLanguageAttribute: function (element) {
		var xmllang = 'none';
		try {
				// IE7 complains about xml:lang
			xmllang = element.getAttribute('xml:lang') ? element.getAttribute('xml:lang') : 'none';
		} catch(e) { }
		return element.getAttribute('lang') ? element.getAttribute('lang') : xmllang;
	},
	/*
	 * This function sets the language attributes on the given element
	 *
	 * @param	object		element: the element on which to set the value of the lang and/or xml:lang attribute
	 * @param	string		language: value of the lang attributes, or "none", in which case, the attribute(s) is(are) removed
	 *
	 * @return	void
	 */
	setLanguageAttributes: function (element, language) {
		if (element) {
			if (language == 'none') {
					// Remove language mark, if any
				element.removeAttribute('lang');
				try {
						// Do not let IE7 complain
					element.removeAttribute('xml:lang');
				} catch(e) { }
					// Remove the span tag if it has no more attribute
				if (/^span$/i.test(element.nodeName) && !HTMLArea.DOM.hasAllowedAttributes(element, this.allowedAttributes)) {
					this.editor.getDomNode().removeMarkup(element);
				}
			} else {
				if (this.useAttribute.lang) {
					element.setAttribute('lang', language);
				}
				if (this.useAttribute.xmlLang) {
					try {
							// Do not let IE7 complain
						element.setAttribute('xml:lang', language);
					} catch(e) { }
				}
			}
		}
	},
	/*
	 * This function gets the language attributes from blocks sibling of the block containing the start container of the selection
	 *
	 * @return	string		value of the lang attribute, or of the xml:lang attribute, or "none", if all blocks sibling do not have the same attribute value as the block containing the start container
	 */
	getLanguageAttributeFromBlockElements: function () {
		var endBlocks = this.editor.getSelection().getEndBlocks();
		var startAncestors = HTMLArea.DOM.getBlockAncestors(endBlocks.start);
		var endAncestors = HTMLArea.DOM.getBlockAncestors(endBlocks.end);
		var index = 0;
		while (index < startAncestors.length && index < endAncestors.length && startAncestors[index] === endAncestors[index]) {
			++index;
		}
		if (endBlocks.start === endBlocks.end) {
			--index;
		}
		var language = this.getLanguageAttribute(startAncestors[index]);
		for (var block = startAncestors[index]; block; block = block.nextSibling) {
			if (HTMLArea.DOM.isBlockElement(block)) {
				if (this.getLanguageAttribute(block) != language || this.getLanguageAttribute(block) == 'none') {
					language = 'none';
					break;
				}
			}
			if (block == endAncestors[index]) {
				break;
			}
		}
		return language;
	},
	/*
	 * This function sets the language attributes on blocks sibling of the block containing the start container of the selection
	 */
	setLanguageAttributeOnBlockElements: function (language) {
		var endBlocks = this.editor.getSelection().getEndBlocks();
		var startAncestors = HTMLArea.DOM.getBlockAncestors(endBlocks.start);
		var endAncestors = HTMLArea.DOM.getBlockAncestors(endBlocks.end);
		var index = 0;
		while (index < startAncestors.length && index < endAncestors.length && startAncestors[index] === endAncestors[index]) {
			++index;
		}
		if (endBlocks.start === endBlocks.end) {
			--index;
		}
		for (var block = startAncestors[index]; block; block = block.nextSibling) {
			if (HTMLArea.DOM.isBlockElement(block)) {
				this.setLanguageAttributes(block, language);
			}
			if (block == endAncestors[index]) {
				break;
			}
		}
	},

	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
			var range = this.editor.getSelection().createRange();
			var parent = this.editor.getSelection().getParentElement();
			switch (button.itemId) {
				case 'RightToLeft':
				case 'LeftToRight':
					if (parent) {
						var direction = (button.itemId === 'RightToLeft') ? 'rtl' : 'ltr';
						button.setInactive(parent.dir != direction && parent.style.direction != direction);
						button.setDisabled(/^body$/i.test(parent.nodeName));
					} else {
						button.setDisabled(true);
					}
					break;
				case 'ShowLanguageMarks':
					button.setInactive(!HTMLArea.DOM.hasClass(this.editor.document.body, 'htmlarea-show-language-marks'));
					break;
				case 'Language':
						// Updating the language drop-down
					var fullNodeSelected = false;
					var language = this.getLanguageAttribute(parent);
					if (!selectionEmpty) {
						if (endPointsInSameBlock) {
							for (var i = 0; i < ancestors.length; ++i) {
								fullNodeSelected =  (!HTMLArea.isIEBeforeIE9 && ((statusBarSelection === ancestors[i] && ancestors[i].textContent === range.toString()) || (!statusBarSelection && ancestors[i].textContent === range.toString())))
									|| (HTMLArea.isIEBeforeIE9 && statusBarSelection === ancestors[i] && ((this.editor.getSelection().getType() !== 'Control' && ancestors[i].innerText === range.text) || (this.editor.getSelection().getType() === 'Control' && ancestors[i].innerText === range.item(0).text)));
								if (fullNodeSelected) {
									parent = ancestors[i];
									break;
								}
							}
								// Working around bug in Safari selectNodeContents
							if (!fullNodeSelected && Ext.isWebKit && statusBarSelection && statusBarSelection.textContent === range.toString()) {
								fullNodeSelected = true;
								parent = statusBarSelection;
							}
							language = this.getLanguageAttribute(parent);
						} else {
							language = this.getLanguageAttributeFromBlockElements();
						}
					}
					this.updateValue(button, language, selectionEmpty, fullNodeSelected, endPointsInSameBlock);
					break;
				default:
					break;
			}
		}
	},
	/*
	* This function updates the language drop-down list
	*/
	updateValue: function (select, language, selectionEmpty, fullNodeSelected, endPointsInSameBlock) {
		var store = select.getStore();
		store.removeAt(0);
		if ((store.findExact('value', language) != -1) && (selectionEmpty || fullNodeSelected || !endPointsInSameBlock)) {
			select.setValue(language);
			store.insert(0, new store.recordType({
				text: this.localize('Remove language mark'),
				value: 'none'
			}));
		} else {
			store.insert(0, new store.recordType({
				text: this.localize('No language mark'),
				value: 'none'
			}));
			select.setValue('none');
		}
		select.setDisabled(!(store.getCount()>1) || (selectionEmpty && /^body$/i.test(this.editor.getSelection().getParentElement().nodeName)));
	}
});
