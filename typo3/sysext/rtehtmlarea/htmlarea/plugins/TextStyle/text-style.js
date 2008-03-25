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
 * Text Style Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
/*
 * Creation of the class of TextStyle plugins
 */
TextStyle = HTMLArea.Plugin.extend({
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
			if (this.tags[tagName].allowedClasses) {
				allowedClasses = this.tags[tagName].allowedClasses.trim().split(",");
				for (var cssClass in allowedClasses) {
					if (allowedClasses.hasOwnProperty(cssClass)) {
						allowedClasses[cssClass] = allowedClasses[cssClass].trim();
					}
				}
				this.tags[tagName].allowedClasses = new RegExp( "^(" + allowedClasses.join("|") + ")$", "i");
			}
		}
		this.showTagFreeClasses = this.pageTSconfiguration.showTagFreeClasses || this.editorConfiguration.showTagFreeClasses;
		this.prefixLabelWithClassName = this.pageTSconfiguration.prefixLabelWithClassName;
		this.postfixLabelWithClassName = this.pageTSconfiguration.postfixLabelWithClassName;
		
		/*
		 * Regular expression to check if an element is an inline elment
		 */
		this.REInlineTags = /^(abbr|acronym|b|bdo|big|cite|code|del|dfn|em|i|ins|kbd|q|samp|small|span|strike|strong|sub|sup|tt|u|var)$/;
		
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
		var buttonId = "TextStyle";
		var dropDownConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId + "-Tooltip"),
			textMode	: false,
			options		: {"":""},
			action		: "onChange",
			refresh		: "generate",
			context		: null
		};
		this.registerDropDown(dropDownConfiguration);
		
		return true;
	},
	
	isInlineElement : function (el) {
		return el && (el.nodeType === 1) && this.REInlineTags.test(el.nodeName.toLowerCase());
	},
	
	/*
	 * This function gets called when some style in the drop-down list applies it to the highlighted textt
	 */
	onChange : function (editor, buttonId) {
		var select = document.getElementById(this.editor._toolbarObjects[buttonId].elementId);
		var className = select.value;
		var classNames = null;
		var fullNodeSelected = false;
		
		this.editor.focusEditor();
		var selection = this.editor._getSelection();
		var range = this.editor._createRange(selection);
		var parent = this.editor.getParentElement();
		var selectionEmpty = this.editor._selectionEmpty(selection);
		var ancestors = this.editor.getAllAncestors();
		if (HTMLArea.is_ie) {
			var bookmark = range.getBookmark();
		}
		
		if (!selectionEmpty) {
				// The selection is not empty
			for (var i = 0; i < ancestors.length; ++i) {
				fullNodeSelected = (HTMLArea.is_ie && ((this.editor._statusBarTree.selected === ancestors[i] && ancestors[i].innerText === range.text) || (!this.editor._statusBarTree.selected && ancestors[i].innerText === range.text)))
							|| (HTMLArea.is_gecko && ((this.editor._statusBarTree.selected === ancestors[i] && ancestors[i].textContent === range.toString()) || (!this.editor._statusBarTree.selected && ancestors[i].textContent === range.toString())));
				if (fullNodeSelected) {
					if (this.isInlineElement(ancestors[i])) {
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
		}
		if (!selectionEmpty && !fullNodeSelected) {
				// The selection is not empty, nor full element
			if (className !== "none") {
					// Add span element with class attribute
				if (HTMLArea.is_gecko) {
					var newElement = this.editor._doc.createElement("span");
					HTMLArea._addClass(newElement, className);
					range.surroundContents(newElement);
					newElement.normalize();
					parent.normalize();
						// Firefox sometimes inserts empty elements just outside the boundaries of the range
					var neighbour = newElement.previousSibling;
					if (neighbour && (neighbour.nodeType != 3) && !/\S/.test(neighbour.textContent)) {
						HTMLArea.removeFromParent(neighbour);
					}
					neighbour = newElement.nextSibling;
					if (neighbour && (neighbour.nodeType != 3) && !/\S/.test(neighbour.textContent)) {
						HTMLArea.removeFromParent(neighbour);
					}
					this.editor.selectNodeContents(newElement, false);
					range.detach();
				} else {
					var rangeStart = range.duplicate();
					rangeStart.collapse(true);
					var parentStart = rangeStart.parentElement();
					var rangeEnd = range.duplicate();
					rangeEnd.collapse(true);
					var parentEnd = rangeEnd.parentElement();
					var newRange = editor._createRange();
					
					var upperParentStart = parentStart;
					if (parentStart !== parent) {
						while (upperParentStart.parentNode !== parent) {
							upperParentStart = upperParentStart.parentNode;
						}
					}
						
					var newElement = editor._doc.createElement("span");
					HTMLArea._addClass(newElement, className);
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
							&& parentStart.lastChild.nodeName.toLowerCase() === "span") {
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
					try { // normalize() not available in IE5.5
						parent.normalize();
					} catch(e) { }
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
				if ((parent.nodeName.toLowerCase() === "span") && !this.hasAllowedAttributes(parent)) {
					this.removeMarkup(parent);
				}
			}
		}
	},
	
	/*
	 * This function verifies if the element has any of the allowed attributes
	 */
	hasAllowedAttributes : function(element) {
		var allowedAttributes = new Array("id", "title", "lang", "xml:lang", "dir", "class", "className");
		for (var i = 0; i < allowedAttributes.length; ++i) {
			if (element.getAttribute(allowedAttributes[i])) {
				return true;
			}
		}
		return false;
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
	 * This function gets called when the plugin is generated
	 * Get the classes configuration and initiate the parsing of the style sheets
	 */
	onGenerate : function() {
		this.generate(this.editor, "TextStyle");
	},
	
	/*
	 * This function gets called on plugin generation, on toolbar update and  on change mode
	 * Re-initiate the parsing of the style sheets, if not yet completed, and refresh our toolbar components
	 */
	generate : function(editor, dropDownId) {
		if (this.cssLoaded) {
			this.updateToolbar(dropDownId);
		} else {
			if (this.cssTimeout) {
				if (editor._iframe.contentWindow) {
					editor._iframe.contentWindow.clearTimeout(this.cssTimeout);
				} else {
					window.clearTimeout(this.cssTimeout);
				}
				this.cssTimeout = null;
			}
			if (this.classesUrl && (typeof(HTMLArea.classesLabels) === "undefined")) {
				this.getJavascriptFile(this.classesUrl);
			}
			this.buildCssArray(editor, dropDownId);
		}
	},
	
	buildCssArray : function(editor, dropDownId) {
		this.cssArray = this.parseStyleSheet();
		if (!this.cssLoaded && (this.cssParseCount < 17)) {
			var buildCssArrayLaterFunctRef = this.makeFunctionReference("buildCssArray");
			this.cssTimeout = editor._iframe.contentWindow ? editor._iframe.contentWindow.setTimeout(buildCssArrayLaterFunctRef, 200) : window.setTimeout(buildCssArrayLaterFunctRef, 200);
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
			if (HTMLArea.is_gecko) {
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
				cssElement = cssElements[k].split(".");
				tagName = cssElement[0].toLowerCase().trim();
				if (!tagName) {
					tagName = 'all';
				}
				className = cssElement[1];
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
	onUpdateToolbar : function() {
		if (this.editor.getMode() === "wysiwyg" && this.editor.isEditable()) {
			this.generate(this.editor, "TextStyle");
		}
	},
	
	/*
	* This function gets called when the drop-down list needs to be refreshed
	*/
	updateToolbar : function(dropDownId) {
		var editor = this.editor;
		if (this.editor.getMode() === "wysiwyg" && this.editor.isEditable()) {
			var tagName = false, classNames = Array(), fullNodeSelected = false;
			var selection = editor._getSelection();
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
					fullNodeSelected = (editor._statusBarTree.selected === ancestors[i])
						&& ((HTMLArea.is_gecko && ancestors[i].textContent === range.toString()) || (HTMLArea.is_ie && ancestors[i].innerText === range.text));
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
				if (!fullNodeSelected && HTMLArea.is_safari && this.editor._statusBarTree.selected && this.isInlineElement(this.editor._statusBarTree.selected) && this.editor._statusBarTree.selected.textContent === range.toString()) {
					fullNodeSelected = true;
					tagName = this.editor._statusBarTree.selected.nodeName.toLowerCase();
					if (this.editor._statusBarTree.selected.className && /\S/.test(this.editor._statusBarTree.selected.className)) {
						classNames = this.editor._statusBarTree.selected.className.trim().split(" ");
					}
				}
			}
			var selectionInInlineElement = tagName && this.REInlineTags.test(tagName);
			var disabled = !this.endPointsInSameBlock() || (fullNodeSelected && !tagName) || (selectionEmpty && !selectionInInlineElement);
			if (!disabled && !tagName) {
				tagName = "span";
			}
			
			this.updateValue(dropDownId, tagName, classNames, selectionEmpty, fullNodeSelected, disabled);
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
			return (endBlocks.start === endBlocks.end && !/^(body|table|thead|tbody|tfoot|tr)$/i.test(parent.nodeName));
		}
	},
	
	updateValue : function(dropDownId, tagName, classNames, selectionEmpty, fullNodeSelected, disabled) {
		var editor = this.editor;
		var select = document.getElementById(editor._toolbarObjects[dropDownId]["elementId"]);
		var cssArray = new Array();
		
		while(select.options.length > 0) {
			select.options[select.length-1] = null;
		}
		select.options[0] = new Option(this.localize("No style"),"none");
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
					if (cssClass == "none") {
						select.options[0] = new Option(cssArray[cssClass], cssClass);
					} else {
						select.options[select.options.length] = new Option(cssArray[cssClass], cssClass);
						if (!editor.config.disablePCexamples && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) {
							select.options[select.options.length-1].setAttribute("style", HTMLArea.classesValues[cssClass]);
						}
					}
				}
			}
			
			select.selectedIndex = 0;
			if (classNames.length && (selectionEmpty || fullNodeSelected)) {
				for (i = select.options.length; --i >= 0;) {
					if (classNames[classNames.length-1] == select.options[i].value) {
						select.options[i].selected = true;
						select.selectedIndex = i;
						select.options[0].text = this.localize("Remove style");
						break;
					}
				}
				if (select.selectedIndex == 0) {
					select.options[select.options.length] = new Option(this.localize("Unknown style"), classNames[classNames.length-1]);
					select.options[select.options.length-1].selected = true;
					select.selectedIndex = select.options.length-1;
				}
				for (i = select.options.length; --i >= 0;) {
					if (("," + classNames.join(",") + ",").indexOf("," + select.options[i].value + ",") !== -1) {
						if (select.selectedIndex != i) {
							select.options[i] = null;
						}
					}
				}
			}
		}
		select.disabled = !(select.options.length>1) || disabled;
		select.className = "";
		if (select.disabled) {
			select.className = "buttonDisabled";
		}
	},
	
	/*
	 * This function gets called when the editor has changed its mode to "wysiwyg"
	 */
	onMode : function(mode) {
		if (mode === "wysiwyg" && this.editor.isEditable()) {
			this.generate(this.editor, "TextStyle");
		}
	}
});

