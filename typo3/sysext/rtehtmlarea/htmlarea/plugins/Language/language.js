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
 * Language Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
Language = HTMLArea.Plugin.extend({

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
		this.useAttribute = {};
		this.useAttribute.lang = (this.buttonsConfiguration.language && this.buttonsConfiguration.language.useLangAttribute) ? this.buttonsConfiguration.language.useLangAttribute : true;
		this.useAttribute.xmlLang = (this.buttonsConfiguration.language && this.buttonsConfiguration.language.useXmlLangAttribute) ? this.buttonsConfiguration.language.useXmlLangAttribute : false;
		if (!this.useAttribute.lang && !this.useAttribute.xmlLang) {
			this.useAttribute.lang = true;
		}

			// Importing list of allowed attributes
		if (this.editor.getPluginInstance("TextStyle")) {
			this.allowedAttributes = this.editor.getPluginInstance("TextStyle").allowedAttributes;
		}			
		if (!this.allowedAttributes && this.editor.getPluginInstance("InlineElements")) {
			this.allowedAttributes = this.editor.getPluginInstance("InlineElements").allowedAttributes;
		}
		if (!this.allowedAttributes && this.editor.getPluginInstance("BlockElements")) {
			this.allowedAttributes = this.editor.getPluginInstance("BlockElements").allowedAttributes;
		}
		if (!this.allowedAttributes) {
			this.allowedAttributes = new Array("id", "title", "lang", "xml:lang", "dir", "class");
			if (HTMLArea.is_ie) {
				this.allowedAttributes.push("className");
			}
		}

		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "0.1",
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
		var buttonList = this.buttonList, buttonId;
		for (var i = 0, n = buttonList.length; i < n; ++i) {
			var button = buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + "-Tooltip"),
				action		: "onButtonPress",
				context		: button[1]
			};
			this.registerButton(buttonConfiguration);
		}

		/*
		 * Registering the dropdown list
		 */
		if (this.buttonsConfiguration.language && this.buttonsConfiguration.language.languagesUrl) {
				// Load the options into HTMLArea.languageOptions
			var languagesData = this.getJavascriptFile(this.buttonsConfiguration.language.languagesUrl, "noEval");
			if (languagesData) {
				eval(languagesData);
			}
		}
		var buttonId = "Language";
		var dropDownConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId + "-Tooltip"),
			options		: (HTMLArea.languageOptions ? HTMLArea.languageOptions : null),
			action		: "onChange",
			refresh		: null,
			context		: null
		};
		this.registerDropDown(dropDownConfiguration);

		return true;
	 },

	/* The list of buttons added by this plugin */
	buttonList : [
		["LeftToRight", null],
		["RightToLeft", null],
		["ShowLanguageMarks", null]
	],

	/*
	 * This function gets called when the editor is generated
	 */
	onGenerate : function () {
			// Add rules to the stylesheet for language mark highlighting
			// Model: body.htmlarea-show-language-marks *[lang=en]:before { content: "en: "; }
			// Works in IE8, but not in earlier versions of IE
		var obj = this.getDropDownConfiguration("Language");
		if ((typeof(obj) !== "undefined") && (typeof(this.editor._toolbarObjects[obj.id]) !== "undefined")) {
			var styleSheet = this.editor._doc.styleSheets[0];
			var select = document.getElementById(this.editor._toolbarObjects[obj.id].elementId);
			var options = select.options;
			var rule, selector, style;
			for (var i = options.length; --i >= 0;) {
				selector = 'body.htmlarea-show-language-marks *[' + 'lang="' + options[i].value + '"]:before';
				style = 'content: "' + options[i].value + ': ";';
				rule = selector + ' { ' + style + ' }';
				if (HTMLArea.is_gecko) {
					try {
						styleSheet.insertRule(rule, styleSheet.cssRules.length);
					} catch (e) {
						this.appendToLog("onGenerate", "Error inserting css rule: " + rule + " Error text: " + e);
					}
				} else {
					styleSheet.addRule(selector, style);
				}
			}
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
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		
		switch (buttonId) {
			case "RightToLeft" :
			case "LeftToRight" :
				this.setDirAttribute(buttonId);
				break;
			case "ShowLanguageMarks":
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
	setDirAttribute : function (buttonId) {
		var direction = (buttonId == "RightToLeft") ? "rtl" : "ltr";
		var element = this.editor.getParentElement();
		if (element) {
			if (element.nodeName.toLowerCase() === "bdo") {
				element.dir = direction;
			} else {
				element.dir = (element.dir == direction || element.style.direction == direction) ? "" : direction;
			}
			element.style.direction = "";
		}
	 },
	
	/*
	 * Toggles the display of language marks
	 *
	 * @param	boolean		forceLanguageMarks: if set, language marks are displayed whatever the current state
	 *
	 * @return	void
	 */
	toggleLanguageMarks : function (forceLanguageMarks) {
		var body = this.editor._doc.body;
		if (!HTMLArea._hasClass(body, 'htmlarea-show-language-marks')) {
			HTMLArea._addClass(body,'htmlarea-show-language-marks');
		} else if (!forceLanguageMarks) {
			HTMLArea._removeClass(body,'htmlarea-show-language-marks');
		}
	},

	/*
	 * This function gets called when some language was selected in the drop-down list
	 */
	onChange : function (editor, buttonId) {
		var tbobj = this.editor._toolbarObjects[buttonId];
		var language = document.getElementById(tbobj.elementId).value;
		this.applyLanguageMark(language);
	},

	/*
	 * This function applies the langauge mark to the selection
	 */
	applyLanguageMark : function (language) {
		var selection = this.editor._getSelection();
		var statusBarSelection = this.editor.getPluginInstance("StatusBar") ? this.editor.getPluginInstance("StatusBar").getSelection() : null;
		var range = this.editor._createRange(selection);
		var parent = this.editor.getParentElement(selection, range);
		var selectionEmpty = this.editor._selectionEmpty(selection);
		var endPointsInSameBlock = this.editor.endPointsInSameBlock();
		var fullNodeSelected = false;
		if (!selectionEmpty) {
			if (endPointsInSameBlock) {
				var ancestors = this.editor.getAllAncestors();
				for (var i = 0; i < ancestors.length; ++i) {
					fullNodeSelected = (statusBarSelection === ancestors[i])
						&& ((HTMLArea.is_gecko && ancestors[i].textContent === range.toString()) || (HTMLArea.is_ie && ((selection.type !== "Control" && ancestors[i].innerText === range.text) || (selection.type === "Control" && ancestors[i].innerText === range.item(0).text))));
					if (fullNodeSelected) {
						parent = ancestors[i];
						break;
					}
				}
					// Working around bug in Safari selectNodeContents
				if (!fullNodeSelected && HTMLArea.is_safari && statusBarSelection && statusBarSelection.textContent === range.toString()) {
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
			if (language != "none") {
					// Add span element with lang attribute(s)
				var newElement = this.editor._doc.createElement("span");
				this.setLanguageAttributes(newElement, language);
				this.editor.wrapWithInlineElement(newElement, selection, range);
				if (HTMLArea.is_gecko) {
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
	getLanguageAttribute : function (element) {
		var xmllang = "none";
		try {
				// IE7 complains about xml:lang
			xmllang = element.getAttribute("xml:lang") ? element.getAttribute("xml:lang") : "none";
		} catch(e) { }
		return element.getAttribute("lang") ? element.getAttribute("lang") : xmllang;
	},
	
	/*
	 * This function sets the language attributes on the given element
	 *
	 * @param	object		element: the element on which to set the value of the lang and/or xml:lang attribute
	 * @param	string		language: value of the lang attributes, or "none", in which case, the attribute(s) is(are) removed
	 *
	 * @return	void
	 */
	setLanguageAttributes : function (element, language) {
		if (language == "none") {
				// Remove language mark, if any
			element.removeAttribute("lang");
			try {
					// Do not let IE7 complain
				element.removeAttribute("xml:lang");
			} catch(e) { }
				// Remove the span tag if it has no more attribute
			if ((element.nodeName.toLowerCase() == "span") && !HTMLArea.hasAllowedAttributes(element, this.allowedAttributes)) {
				this.editor.removeMarkup(element);
			}
		} else {
			if (this.useAttribute.lang) {
				element.setAttribute("lang", language);
			}
			if (this.useAttribute.xmlLang) {
				try {
						// Do not let IE7 complain
					element.setAttribute("xml:lang", language);
				} catch(e) { }
			}
		}
	},

	/*
	 * This function gets the language attributes from blocks sibling of the block containing the start container of the selection
	 *
	 * @return	string		value of the lang attribute, or of the xml:lang attribute, or "none", if all blocks sibling do not have the same attribute value as the block containing the start container
	 */
	getLanguageAttributeFromBlockElements : function() {
		var selection = this.editor._getSelection();
		var endBlocks = this.editor.getEndBlocks(selection);
		var startAncestors = this.editor.getBlockAncestors(endBlocks.start);
		var endAncestors = this.editor.getBlockAncestors(endBlocks.end);
		var index = 0;
		while (index < startAncestors.length && index < endAncestors.length && startAncestors[index] === endAncestors[index]) {
			++index;
		}
		if (endBlocks.start === endBlocks.end) {
			--index;
		}
		var language = this.getLanguageAttribute(startAncestors[index]);
		for (var block = startAncestors[index]; block; block = block.nextSibling) {
			if (HTMLArea.isBlockElement(block)) {
				if (this.getLanguageAttribute(block) != language || this.getLanguageAttribute(block) == "none") {
					language = "none";
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
	setLanguageAttributeOnBlockElements : function(language) {
		var selection = this.editor._getSelection();
		var endBlocks = this.editor.getEndBlocks(selection);
		var startAncestors = this.editor.getBlockAncestors(endBlocks.start);
		var endAncestors = this.editor.getBlockAncestors(endBlocks.end);
		var index = 0;
		while (index < startAncestors.length && index < endAncestors.length && startAncestors[index] === endAncestors[index]) {
			++index;
		}
		if (endBlocks.start === endBlocks.end) {
			--index;
		}
		for (var block = startAncestors[index]; block; block = block.nextSibling) {
			if (HTMLArea.isBlockElement(block)) {
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
	onUpdateToolbar : function () {
		if (this.getEditorMode() === "wysiwyg" && this.editor.isEditable()) {
			var selection = this.editor._getSelection();
			var statusBarSelection = this.editor.getPluginInstance("StatusBar") ? this.editor.getPluginInstance("StatusBar").getSelection() : null;
			var range = this.editor._createRange(selection);
			var parent = this.editor.getParentElement(selection);
				// Updating the direction buttons
			var buttonList = this.buttonList, buttonId;
			for (var i = 0, n = buttonList.length; i < n; ++i) {
				buttonId = buttonList[i][0];
				if (this.isButtonInToolbar(buttonId)) {
					switch (buttonId) {
						case "RightToLeft" :
						case "LeftToRight" :
							if (parent) {
								var direction = (buttonId === "RightToLeft") ? "rtl" : "ltr";
								this.editor._toolbarObjects[buttonId].state("active",(parent.dir == direction || parent.style.direction == direction));
								this.editor._toolbarObjects[buttonId].state("enabled", !/^body$/i.test(parent.nodeName));
							} else {
								this.editor._toolbarObjects[buttonId].state("enabled", false);
							}
							break;
						case "ShowLanguageMarks":
							this.editor._toolbarObjects[buttonId].state("active", HTMLArea._hasClass(this.editor._doc.body, 'htmlarea-show-language-marks'));
							break;
						default	:
							break;
					}
				}
			}
				// Updating the language drop-down
			var fullNodeSelected = false;
			var language = this.getLanguageAttribute(parent);
			var selectionEmpty = this.editor._selectionEmpty(selection);
			var endPointsInSameBlock = this.editor.endPointsInSameBlock();
			if (!selectionEmpty) {
				if (endPointsInSameBlock) {
					var ancestors = this.editor.getAllAncestors();
					for (var i = 0; i < ancestors.length; ++i) {
						fullNodeSelected = (statusBarSelection === ancestors[i])
							&& ((HTMLArea.is_gecko && ancestors[i].textContent === range.toString()) || (HTMLArea.is_ie && ((selection.type !== "Control" && ancestors[i].innerText === range.text) || (selection.type === "Control" && ancestors[i].innerText === range.item(0).text))));
						if (fullNodeSelected) {
							parent = ancestors[i];
							break;
						}
					}
						// Working around bug in Safari selectNodeContents
					if (!fullNodeSelected && HTMLArea.is_safari && statusBarSelection && statusBarSelection.textContent === range.toString()) {
						fullNodeSelected = true;
						parent = statusBarSelection;
					}
					language = this.getLanguageAttribute(parent);
				} else {
					language = this.getLanguageAttributeFromBlockElements();
				}
			}
			var obj = this.getDropDownConfiguration("Language");
			if ((typeof(obj) !== "undefined") && (typeof(this.editor._toolbarObjects[obj.id]) !== "undefined")) {
				this.updateValue(obj, language, selectionEmpty, fullNodeSelected, endPointsInSameBlock);
			}
		}
	},

	/*
	* This function updates the language drop-down list
	*/
	updateValue : function (obj, language, selectionEmpty, fullNodeSelected, endPointsInSameBlock) {
		var select = document.getElementById(this.editor._toolbarObjects[obj.id]["elementId"]);
		var options = select.options;
		for (var i = options.length; --i >= 0;) {
			options[i].selected = false;
		}
		select.selectedIndex = 0;
		options[0].selected = true;
		select.options[0].text = this.localize("No language mark");
		if (language != "none") {
			for (i = options.length; --i >= 0;) {
				if (language == options[i].value) {
					if (selectionEmpty || fullNodeSelected || !endPointsInSameBlock) {
						options[i].selected = true;
						select.selectedIndex = i;
						select.options[0].text = this.localize("Remove language mark");
					}
					break;
				}
			}
		}
		select.disabled = !(options.length>1) || (selectionEmpty && this.editor.getParentElement().nodeName.toLowerCase() === "body");
		select.className = "";
		if (select.disabled) {
			select.className = "buttonDisabled";
		}
	}
});
