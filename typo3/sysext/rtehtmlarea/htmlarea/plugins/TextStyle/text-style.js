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
 * Text Style Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
/*
 * Creation of the class of TextStyle plugins
 */
HTMLArea.TextStyle = HTMLArea.Plugin.extend({
	/*
	 * Let the base class do some initialization work
	 */
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		
		this.cssLoaded = false;
		this.cssTimeout = null;
		this.cssParseCount = 0;
		this.cssArray = new Object();
		
		this.classesUrl = this.editorConfiguration.classesUrl;
		this.pageTSconfiguration = this.editorConfiguration.buttons.textstyle;
		this.tags = this.pageTSconfiguration.tags;
		if (!this.tags) {
			this.tags = new Object();
		}
		if (typeof(this.editorConfiguration.classesTag) !== "undefined") {
			if (this.editorConfiguration.classesTag.span) {
				if (!this.tags.span) {
					this.tags.span = new Object();
				}
				if (!this.tags.span.allowedClasses) {
					this.tags.span.allowedClasses = this.editorConfiguration.classesTag.span;
				}
			}
		}
		var allowedClasses;
		for (var tagName in this.tags) {
			if (this.tags.hasOwnProperty(tagName)) {
				if (this.tags[tagName].allowedClasses) {
					allowedClasses = this.tags[tagName].allowedClasses.trim().split(",");
					for (var cssClass in allowedClasses) {
						if (allowedClasses.hasOwnProperty(cssClass)) {
							allowedClasses[cssClass] = allowedClasses[cssClass].trim().replace(/\*/g, ".*");
						}
					}
					this.tags[tagName].allowedClasses = new RegExp( "^(" + allowedClasses.join("|") + ")$", "i");
				}
			}
		}
		this.showTagFreeClasses = this.pageTSconfiguration.showTagFreeClasses || this.editorConfiguration.showTagFreeClasses;
		this.prefixLabelWithClassName = this.pageTSconfiguration.prefixLabelWithClassName;
		this.postfixLabelWithClassName = this.pageTSconfiguration.postfixLabelWithClassName;
		
		/*
		 * Regular expression to check if an element is an inline elment
		 */
		this.REInlineTags = /^(abbr|acronym|b|bdo|big|cite|code|del|dfn|em|i|ins|kbd|q|samp|small|span|strike|strong|sub|sup|tt|u|var)$/;
		
			// Allowed attributes on inline elements
		this.allowedAttributes = new Array("id", "title", "lang", "xml:lang", "dir", "class");
		if (Ext.isIE) {
			this.addAllowedAttribute("className");
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
		var buttonId = 'TextStyle';
		var fieldLabel = this.pageTSconfiguration.fieldLabel;
		if (Ext.isEmpty(fieldLabel) && this.isButtonInToolbar('I[text_style]')) {
			fieldLabel = this.localize('text_style');
		}
		var dropDownConfiguration = {
			id: buttonId,
			tooltip: this.localize(buttonId + '-Tooltip'),
			fieldLabel: fieldLabel,
			options: [[this.localize('No style'), 'none']],
			action: 'onChange',
			storeFields: [ { name: 'text'}, { name: 'value'}, { name: 'style'} ],
			tpl: '<tpl for="."><div ext:qtip="{value}" style="{style}text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>'
		};
		if (this.pageTSconfiguration.width) {
			dropDownConfiguration.width = parseInt(this.pageTSconfiguration.width, 10);
		}
		if (this.pageTSconfiguration.listWidth) {
			dropDownConfiguration.listWidth = parseInt(this.pageTSconfiguration.listWidth, 10);
		}
		if (this.pageTSconfiguration.maxHeight) {
			dropDownConfiguration.maxHeight = parseInt(this.pageTSconfiguration.maxHeight, 10);
		}
		this.registerDropDown(dropDownConfiguration);
		return true;
	},
	
	isInlineElement : function (el) {
		return el && (el.nodeType === 1) && this.REInlineTags.test(el.nodeName.toLowerCase());
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
	 * This function gets called when some style in the drop-down list applies it to the highlighted textt
	 */
	onChange : function (editor, combo, record, index) {
		var className = combo.getValue();
		var classNames = null;
		var fullNodeSelected = false;
		
		this.editor.focus();
		var selection = this.editor._getSelection();
		var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
		var range = this.editor._createRange(selection);
		var parent = this.editor.getParentElement();
		var selectionEmpty = this.editor._selectionEmpty(selection);
		var ancestors = this.editor.getAllAncestors();
		if (Ext.isIE) {
			var bookmark = range.getBookmark();
		}
		
		if (!selectionEmpty) {
				// The selection is not empty
			for (var i = 0; i < ancestors.length; ++i) {
				fullNodeSelected = (Ext.isIE && ((statusBarSelection === ancestors[i] && ancestors[i].innerText === range.text) || (!statusBarSelection && ancestors[i].innerText === range.text)))
							|| (!Ext.isIE && ((statusBarSelection === ancestors[i] && ancestors[i].textContent === range.toString()) || (!statusBarSelection && ancestors[i].textContent === range.toString())));
				if (fullNodeSelected) {
					if (this.isInlineElement(ancestors[i])) {
						parent = ancestors[i];
					}
					break;
				}
			}
				// Working around bug in Safari selectNodeContents
			if (!fullNodeSelected && Ext.isWebKit && statusBarSelection && this.isInlineElement(statusBarSelection) && statusBarSelection.textContent === range.toString()) {
				fullNodeSelected = true;
				parent = statusBarSelection;
			}
		}
		if (!selectionEmpty && !fullNodeSelected || (!selectionEmpty && fullNodeSelected && parent && HTMLArea.isBlockElement(parent))) {
				// The selection is not empty, nor full element, or the selection is full block element
			if (className !== "none") {
					// Add span element with class attribute
				var newElement = editor._doc.createElement("span");
				HTMLArea._addClass(newElement, className);
				editor.wrapWithInlineElement(newElement, selection, range);
				if (!Ext.isIE) {
					range.detach();
				}
			}
		} else {
				// Add or remove class
			if (parent && !HTMLArea.isBlockElement(parent)) {
				if (className === "none" && parent.className && /\S/.test(parent.className)) {
					classNames = parent.className.trim().split(" ");
					HTMLArea._removeClass(parent, classNames[classNames.length-1]);
				}
				if (className !== "none") {
					HTMLArea._addClass(parent, className);
				}
					// Remove the span tag if it has no more attribute
				if ((parent.nodeName.toLowerCase() === "span") && !HTMLArea.hasAllowedAttributes(parent, this.allowedAttributes)) {
					editor.removeMarkup(parent);
				}
			}
		}
	},

	/*
	 * This function gets called when the plugin is generated
	 * Get the classes configuration and initiate the parsing of the style sheets
	 */
	onGenerate: function() {
			// Monitor editor changing mode
		this.editor.iframe.mon(this.editor, 'modeChange', this.onModeChange, this);
		this.generate(this.editor, "TextStyle");
	},
	
	/*
	 * This function gets called on plugin generation, on toolbar update and  on change mode
	 * Re-initiate the parsing of the style sheets, if not yet completed, and refresh our toolbar components
	 */
	generate: function (editor, dropDownId) {
		if (this.cssLoaded) {
			this.updateToolbar(dropDownId);
		} else {
			if (this.cssTimeout) {
				window.clearTimeout(this.cssTimeout);
				this.cssTimeout = null;
			}
			if (this.classesUrl && (typeof(HTMLArea.classesLabels) === 'undefined')) {
				this.getJavascriptFile(this.classesUrl, function (options, success, response) {
					if (success) {
						try {
							if (typeof(HTMLArea.classesLabels) === 'undefined') {
								eval(response.responseText);
								this.appendToLog('generate', 'Javascript file successfully evaluated: ' + this.classesUrl);
							}
						} catch(e) {
							this.appendToLog('generate', 'Error evaluating contents of Javascript file: ' + this.classesUrl);
						}
					}
					this.buildCssArray(this.editor, dropDownId);
				});
			} else {
				this.buildCssArray(this.editor, dropDownId);
			}
		}
	},
	
	buildCssArray : function(editor, dropDownId) {
		this.cssArray = this.parseStyleSheet();
		if (!this.cssLoaded && (this.cssParseCount < 17)) {
			this.cssTimeout = this.buildCssArray.defer(200, this, [editor, dropDownId]);
			this.cssParseCount++;
		} else {
			this.cssTimeout = null;
			this.cssLoaded = true;
			this.cssArray = this.sortCssArray(this.cssArray);
			this.updateToolbar(dropDownId);
		}
	},
	
	parseStyleSheet : function() {
		var iframe = this.editor._iframe.contentWindow ? this.editor._iframe.contentWindow.document : this.editor._iframe.contentDocument;
		var newCssArray = new Object();
		this.cssLoaded = true;
		for (var i = 0; i < iframe.styleSheets.length; i++) {
			if (!Ext.isIE) {
				try {
					newCssArray = this.parseCssRule(iframe.styleSheets[i].cssRules, newCssArray);
				} catch(e) {
					this.cssLoaded = false;
				}
			} else {
				try{
						// @import StyleSheets (IE)
					if (iframe.styleSheets[i].imports) {
						newCssArray = this.parseCssIEImport(iframe.styleSheets[i].imports, newCssArray);
					}
					if (iframe.styleSheets[i].rules) {
						newCssArray = this.parseCssRule(iframe.styleSheets[i].rules, newCssArray);
					}
				} catch(e) {
					this.cssLoaded = false;
				}
			}
		}
		return newCssArray;
	},
	
	parseCssIEImport : function(cssIEImport, cssArray) {
		var newCssArray = new Object();
		newCssArray = cssArray;
		for (var i=0; i < cssIEImport.length; i++) {
			if (cssIEImport[i].imports) {
				newCssArray = this.parseCssIEImport(cssIEImport[i].imports, newCssArray);
			}
			if (cssIEImport[i].rules) {
				newCssArray = this.parseCssRule(cssIEImport[i].rules, newCssArray);
			}
		}
		return newCssArray;
	},
	
	parseCssRule : function(cssRules, cssArray) {
		var newCssArray = new Object();
		newCssArray = cssArray;
		for (var rule = 0; rule < cssRules.length; rule++) {
				// StyleRule
			if (cssRules[rule].selectorText) {
				newCssArray = this.parseSelectorText(cssRules[rule].selectorText, newCssArray);
			} else {
					// ImportRule (Mozilla)
				if (cssRules[rule].styleSheet) {
					newCssArray = this.parseCssRule(cssRules[rule].styleSheet.cssRules, newCssArray);
				}
					// MediaRule (Mozilla)
				if (cssRules[rule].cssRules) {
					newCssArray = this.parseCssRule(cssRules[rule].cssRules, newCssArray);
				}
			}
		}
		return newCssArray;
	},
	
	parseSelectorText : function(selectorText, cssArray) {
		var cssElements = new Array();
		var cssElement = new Array();
		var tagName, className;
		var newCssArray = new Object();
		newCssArray = cssArray;
		if (selectorText.search(/:+/) == -1) {
				// split equal Styles (Mozilla-specific) e.q. head, body {border:0px}
				// for ie not relevant. returns allways one element
			cssElements = selectorText.split(",");
			for (var k = 0; k < cssElements.length; k++) {
					// Match ALL classes (<element name (optional)>.<class name>) in selector rule
				var s = cssElements[k],
					pattern = /(\S*)\.(\S+)/,
					index;
				while ((index = s.search(pattern)) > -1) {
					var match = pattern.exec(s.substring(index));
					s = s.substring(index+match[0].length);

					tagName = (match[1] && (match[1] != '*')) ? match[1].toLowerCase().trim() : "all";
					className = match[2];

					if (className && !HTMLArea.reservedClassNames.test(className)) {
						if (((tagName != "all") && (!this.tags || !this.tags[tagName]))
							|| ((tagName == "all") && (!this.tags || !this.tags[tagName]) && this.showTagFreeClasses)
							|| (this.tags && this.tags[tagName] && this.tags[tagName].allowedClasses && this.tags[tagName].allowedClasses.test(className))) {
							if (!newCssArray[tagName]) {
								newCssArray[tagName] = new Object();
							}
							if (className) {
								cssName = className;
								if (HTMLArea.classesLabels && HTMLArea.classesLabels[className]) {
									cssName = this.prefixLabelWithClassName ? (className + " - " + HTMLArea.classesLabels[className]) : HTMLArea.classesLabels[className];
									cssName = this.postfixLabelWithClassName ? (cssName + " - " + className) : cssName;
								}
							} else {
								className = 'none';
								cssName = this.localize("Element style");
							}
							newCssArray[tagName][className] = cssName;
						}
					}
				}
			}
		}
		return newCssArray;
	},
	
	sortCssArray : function(cssArray) {
		var newCssArray = new Object();
		for (var tagName in cssArray) {
			if (cssArray.hasOwnProperty(tagName)) {
				newCssArray[tagName] = new Object();
				var tagArrayKeys = new Array();
				for (var cssClass in cssArray[tagName]) {
					if (cssArray[tagName].hasOwnProperty(cssClass)) {
						tagArrayKeys.push(cssClass);
					}
				}
				function compare(a, b) {
					x = cssArray[tagName][a];
					y = cssArray[tagName][b];
					return ((x < y) ? -1 : ((x > y) ? 1 : 0));
				}
				tagArrayKeys = tagArrayKeys.sort(compare);
				for (var i = 0; i < tagArrayKeys.length; ++i) {
					newCssArray[tagName][tagArrayKeys[i]] = cssArray[tagName][tagArrayKeys[i]];
				}
			}
		}
		return newCssArray;
	},
	
	/*
	 * This function gets called when the toolbar is being updated
	 */
	onUpdateToolbar: function(button, mode, selectionEmpty, ancestors) {
		if (mode === "wysiwyg" && this.editor.isEditable()) {
			this.generate(this.editor, button.itemId);
		}
	},
	
	/*
	* This function gets called when the drop-down list needs to be refreshed
	*/
	updateToolbar : function(dropDownId) {
		var editor = this.editor;
		if (this.getEditorMode() === "wysiwyg" && this.editor.isEditable()) {
			var tagName = false, classNames = Array(), fullNodeSelected = false;
			var selection = editor._getSelection();
			var statusBarSelection = editor.statusBar ? editor.statusBar.getSelection() : null;
			var range = editor._createRange(selection);
			var parent = editor.getParentElement(selection);
			var ancestors = editor.getAllAncestors();
			if (parent && !HTMLArea.isBlockElement(parent)) {
				tagName = parent.nodeName.toLowerCase();
				if (parent.className && /\S/.test(parent.className)) {
					classNames = parent.className.trim().split(" ");
				}
			}
			var selectionEmpty = editor._selectionEmpty(selection);
			if (!selectionEmpty) {
				for (var i = 0; i < ancestors.length; ++i) {
					fullNodeSelected = (statusBarSelection === ancestors[i])
						&& ((!Ext.isIE && ancestors[i].textContent === range.toString()) || (Ext.isIE && ancestors[i].innerText === range.text));
					if (fullNodeSelected) {
						if (!HTMLArea.isBlockElement(ancestors[i])) {
							tagName = ancestors[i].nodeName.toLowerCase();
							if (ancestors[i].className && /\S/.test(ancestors[i].className)) {
								classNames = ancestors[i].className.trim().split(" ");
							}
						}
						break;
					}
				}
					// Working around bug in Safari selectNodeContents
				if (!fullNodeSelected && Ext.isWebKit && statusBarSelection && this.isInlineElement(statusBarSelection) && statusBarSelection.textContent === range.toString()) {
					fullNodeSelected = true;
					tagName = statusBarSelection.nodeName.toLowerCase();
					if (statusBarSelection.className && /\S/.test(statusBarSelection.className)) {
						classNames = statusBarSelection.className.trim().split(" ");
					}
				}
			}
			var selectionInInlineElement = tagName && this.REInlineTags.test(tagName);
			var disabled = !editor.endPointsInSameBlock() || (fullNodeSelected && !tagName) || (selectionEmpty && !selectionInInlineElement);
			if (!disabled && !tagName) {
				tagName = "span";
			}
			this.updateValue(dropDownId, tagName, classNames, selectionEmpty, fullNodeSelected, disabled);
		} else {
			var dropDown = this.getButton(dropDownId);
			if (dropDown) {
				dropDown.setDisabled(!dropDown.textMode);
			}
		}
	},

	/*
	 * This function reinitializes the options of the dropdown
	 */
	initializeDropDown : function (dropDown) {
		var store = dropDown.getStore();
		store.removeAll(false);
		store.insert(0, new store.recordType({
			text: this.localize('No style'),
			value: 'none'
		}));
		dropDown.setValue('none');
	},

	/*
	 * This function sets the selected option of the dropDown box
	 */
	setSelectedOption : function (dropDown, classNames, noUnknown, defaultClass) {
		var store = dropDown.getStore();
		var index = store.findExact('value', classNames[classNames.length-1]);
		if (index != -1) {
			dropDown.setValue(classNames[classNames.length-1]);
			if (!defaultClass) {
				store.getAt(0).set('text', this.localize('Remove style'));
			}
		}
		if (index == -1 && !noUnknown) {
			store.add(new store.recordType({
				text: this.localize('Unknown style'),
				value: classNames[classNames.length-1]
			}));
			index = store.getCount()-1;
			dropDown.setValue(classNames[classNames.length-1]);
			if (!defaultClass) {
				store.getAt(0).set('text', this.localize('Remove style'));
			}
		}
		store.each(function (option) {
			if (("," + classNames.join(",") + ",").indexOf("," + option.get('value') + ",") != -1 && store.indexOf(option) != index) {
				store.removeAt(store.indexOf(option));
			}
			return true;
		});
	},

	updateValue : function(dropDownId, tagName, classNames, selectionEmpty, fullNodeSelected, disabled) {
		var editor = this.editor;
		var dropDown = this.getButton(dropDownId);
		if (dropDown) {
			var store = dropDown.getStore();
			var cssArray = new Array();
			this.initializeDropDown(dropDown);
			if (this.REInlineTags.test(tagName)) {
					// Get classes allowed for all tags
				if (typeof(this.cssArray["all"]) !== "undefined") {
					var cssArrayAll = this.cssArray.all;
					if (this.tags && this.tags[tagName] && this.tags[tagName].allowedClasses) {
						var allowedClasses = this.tags[tagName].allowedClasses;
						for (var cssClass in cssArrayAll) {
							if (cssArrayAll.hasOwnProperty(cssClass) && allowedClasses.test(cssClass)) {
								cssArray[cssClass] = cssArrayAll[cssClass];
							}
						}
					} else {
						for (var cssClass in cssArrayAll) {
							if (cssArrayAll.hasOwnProperty(cssClass)) {
								cssArray[cssClass] = cssArrayAll[cssClass];
							}
						}
					}
				}
					// Merge classes allowed for tagName and sort the array
				if (typeof(this.cssArray[tagName]) !== "undefined") {
					var cssArrayTagName = this.cssArray[tagName];
					if (this.tags && this.tags[tagName] && this.tags[tagName].allowedClasses) {
						var allowedClasses = this.tags[tagName].allowedClasses;
						for (var cssClass in cssArrayTagName) {
							if (cssArrayTagName.hasOwnProperty(cssClass) && allowedClasses.test(cssClass)) {
								cssArray[cssClass] = cssArrayTagName[cssClass];
							}
						}
					} else {
						for (var cssClass in cssArrayTagName) {
							if (cssArrayTagName.hasOwnProperty(cssClass)) {
								cssArray[cssClass] = cssArrayTagName[cssClass];
							}
						}
					}
					var sortedCssArray = new Object();
					var cssArrayKeys = new Array();
					for (var cssClass in cssArray) {
						if (cssArray.hasOwnProperty(cssClass)) {
							cssArrayKeys.push(cssClass);
						}
					}
					function compare(a, b) {
						x = cssArray[a];
						y = cssArray[b];
						return ((x < y) ? -1 : ((x > y) ? 1 : 0));
					}
					cssArrayKeys = cssArrayKeys.sort(compare);
					for (var i = 0; i < cssArrayKeys.length; ++i) {
						sortedCssArray[cssArrayKeys[i]] = cssArray[cssArrayKeys[i]];
					}
					cssArray = sortedCssArray;
				}
				for (var cssClass in cssArray) {
					if (cssArray.hasOwnProperty(cssClass) && cssArray[cssClass]) {
						if (cssClass == 'none') {
							store.getAt(0).set('text', cssArray[cssClass]);
						} else {
							store.add(new store.recordType({
								text: cssArray[cssClass],
								value: cssClass,
								style: (!this.editor.config.disablePCexamples && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) ? HTMLArea.classesValues[cssClass] : null
							}));
						}
					}
				}
				if (classNames.length && (selectionEmpty || fullNodeSelected)) {
					this.setSelectedOption(dropDown, classNames);
				}
			}
			dropDown.setDisabled(!(store.getCount()>1) || disabled);
		}
	},
	/*
	 * This function gets called when the editor has changed its mode to "wysiwyg"
	 */
	onModeChange: function(mode) {
		if (mode === "wysiwyg" && this.editor.isEditable()) {
			this.generate(this.editor, "TextStyle");
		}
	}
});

