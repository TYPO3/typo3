/***************************************************************
*  Copyright notice
*
* (c) 2007-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Block Style Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
HTMLArea.BlockStyle = HTMLArea.Plugin.extend({
		
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		this.cssLoaded = false;
		this.cssTimeout = null;
		this.cssParseCount = 0;
		this.cssArray = new Object();
		
		this.classesUrl = this.editorConfiguration.classesUrl;
		this.pageTSconfiguration = this.editorConfiguration.buttons.blockstyle;
		this.tags = this.pageTSconfiguration.tags;
		if (!this.tags) {
			this.tags = new Object();
		}
		if (typeof(this.editorConfiguration.classesTag) !== "undefined") {
			if (this.editorConfiguration.classesTag.div) {
				if (!this.tags.div) {
					this.tags.div = new Object();
				}
				if (!this.tags.div.allowedClasses) {
					this.tags.div.allowedClasses = this.editorConfiguration.classesTag.div;
				}
			}
			if (this.editorConfiguration.classesTag.td) {
				if (!this.tags.td) {
					this.tags.td = new Object();
				}
				if (!this.tags.td.allowedClasses) {
					this.tags.td.allowedClasses = this.editorConfiguration.classesTag.td;
				}
			}
			if (this.editorConfiguration.classesTag.table) {
				if (!this.tags.table) {
					this.tags.table = new Object();
				}
				if (!this.tags.table.allowedClasses) {
					this.tags.table.allowedClasses = this.editorConfiguration.classesTag.table;
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
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.4",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: this.localize("Technische Universitat Ilmenau"),
			sponsorUrl	: "http://www.tu-ilmenau.de/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the drop-down list
		 */
		var dropDownId = 'BlockStyle';
		var fieldLabel = this.pageTSconfiguration.fieldLabel;
		if (Ext.isEmpty(fieldLabel) && this.isButtonInToolbar('I[Block style label]')) {
			fieldLabel = this.localize('Block style label');
		}
		var dropDownConfiguration = {
			id: dropDownId,
			tooltip: this.localize(dropDownId + '-Tooltip'),
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
	
	/*
	 * This function gets called when some block style was selected in the drop-down list
	 */
	onChange : function (editor, combo, record, index) {
		var className = combo.getValue();
		this.editor.focus();
		var blocks = this.getSelectedBlocks();
		for (var k = 0; k < blocks.length; ++k) {
			var parent = blocks[k];
			while (parent && !HTMLArea.isBlockElement(parent) && parent.nodeName.toLowerCase() != "img") {
				parent = parent.parentNode;
			}
			if (!k) {
				var tagName = parent.tagName.toLowerCase();
			}
			if (parent.tagName.toLowerCase() == tagName) {
				this.applyClassChange(parent, className);
			}
		}
	},
	
	/*
	 * This function applies the class change to the node
	 */
	applyClassChange : function (node, className) {
		if (className == "none") {
			var classNames = node.className.trim().split(" ");
			for (var i = classNames.length; --i >= 0;) {
				if (!HTMLArea.reservedClassNames.test(classNames[i])) {
					HTMLArea._removeClass(node, classNames[i]);
					if (node.nodeName.toLowerCase() === "table" && this.getPluginInstance('TableOperations')) {
						this.getPluginInstance('TableOperations').removeAlternatingClasses(node, classNames[i]);
						this.getPluginInstance('TableOperations').removeCountingClasses(node, classNames[i]);
					}
					break;
				}
			}
		} else {
			var nodeName = node.nodeName.toLowerCase();
			if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
				if (this.tags[nodeName].allowedClasses.test(className)) {
					HTMLArea._addClass(node, className);
				}
			} else if (this.tags && this.tags.all && this.tags.all.allowedClasses) {
				if (this.tags.all.allowedClasses.test(className)) {
					HTMLArea._addClass(node, className);
				}
			} else {
				HTMLArea._addClass(node, className);
			}
			if (nodeName === "table" && this.getPluginInstance('TableOperations')) {
				this.getPluginInstance('TableOperations').reStyleTable(node);
			}
		}
	},
	
	/*
	 * This function gets the list of selected blocks
	 */
	getSelectedBlocks : function() {
		var block, range, i = 0, blocks = [];
		var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
		if (Ext.isGecko) {
			var selection = this.editor._getSelection();
			try {
				while ((range = selection.getRangeAt(i++))) {
					block = this.editor.getParentElement(selection, range);
					blocks.push(statusBarSelection ? statusBarSelection : block);
				}
			} catch(e) {
				/* finished walking through selection */
			}
		} else {
			blocks.push(statusBarSelection ? statusBarSelection : this.editor.getParentElement());
		}
		return blocks;
	},
	/*
	 * This function gets called when the editor is generated
	 */
	onGenerate: function() {
			// Monitor editor changing mode
		this.editor.iframe.mon(this.editor, 'modeChange', this.onModeChange, this);
		if (!Ext.isIE) {
			this.generate(this.editor, 'BlockStyle');
		}
	},
	/*
	 * This function gets called when the toolbar is being updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg') {
			this.generate(this.editor, button.itemId);
		}
	},
	/*
	 * This function gets called when the editor has changed its mode to "wysiwyg"
	 */
	onModeChange: function(mode) {
		if (this.getEditorMode() === "wysiwyg") {
			this.generate(this.editor, "BlockStyle");
		}
	},
	/*
	 * This function gets called on plugin generation, on toolbar update and on change mode
	 * Re-initiate the parsing of the style sheets, if not yet completed, and refresh our toolbar components
	 */
	generate: function(editor, dropDownId) {
		if (this.cssLoaded && this.getEditorMode() === 'wysiwyg' && this.editor.isEditable()) {
			this.updateValue(dropDownId);
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
	
	/*
	 * This function updates the current value of the dropdown list
	 */
	updateValue : function(dropDownId) {
		var dropDown = this.getButton(dropDownId);
		if (dropDown) {
			var classNames = new Array();
			var tagName = null;
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
			var parent = statusBarSelection ? statusBarSelection : this.editor.getParentElement();
			while (parent && !HTMLArea.isBlockElement(parent) && parent.nodeName.toLowerCase() != "img") {
				parent = parent.parentNode;
			}
			if (parent) {
				tagName = parent.nodeName.toLowerCase();
				classNames = this.getClassNames(parent);
			}
			if (tagName && tagName !== "body"){
				this.buildDropDownOptions(dropDown, tagName);
				this.setSelectedOption(dropDown, classNames);
			} else {
				this.initializeDropDown(dropDown);
				dropDown.setDisabled(true);
			}
		}
	},
	
	/*
	 * This function returns an array containing the class names assigned to the node
	 */
	getClassNames : function (node) {
		var classNames = new Array();
		if (node) {
			if (node.className && /\S/.test(node.className)) {
				classNames = node.className.trim().split(" ");
			}
			if (HTMLArea.reservedClassNames.test(node.className)) {
				var cleanClassNames = new Array();
				var j = -1;
				for (var i = 0; i < classNames.length; ++i) {
					if (!HTMLArea.reservedClassNames.test(classNames[i])) {
						cleanClassNames[++j] = classNames[i];
					}
				}
				return cleanClassNames;
			}
		}
		return classNames;
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
	 * This function builds the options to be displayed in the dropDown box
	 */
	buildDropDownOptions : function (dropDown, tagName) {
		var store = dropDown.getStore();
		var cssArray = new Array();
		this.initializeDropDown(dropDown);
			// Get classes allowed for all tags
		if (typeof(this.cssArray.all) !== "undefined") {
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
					var style = null;
					if (!this.editor.config.disablePCexamples) {
						if (HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) {
							style = HTMLArea.classesValues[cssClass];
						} else if (/-[0-9]+$/.test(cssClass) && HTMLArea.classesValues[RegExp.leftContext + '-'])  {
							style = HTMLArea.classesValues[RegExp.leftContext + '-'];
						}
					}
					store.add(new store.recordType({
						text: cssArray[cssClass],
						value: cssClass,
						style: style
					}));
				}
			}
		}
	},
	
	/*
	 * This function sets the selected option of the dropDown box
	 */
	setSelectedOption : function (dropDown, classNames, noUnknown, defaultClass) {
		var store = dropDown.getStore();
		dropDown.setValue('none');
		if (classNames.length) {
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
				if (store.indexOf(option) != index && (',' + classNames.join(',') + ',').indexOf(',' + option.get('value') + ',') != -1) {
					store.removeAt(store.indexOf(option));
				}
				return true;
			});
		}
		dropDown.setDisabled(!(store.getCount()>1));
	},
	
	/*
	 * This function builds the main array of class selectors
	 */
	buildCssArray : function(editor, dropDownId) {
		this.cssArray = this.parseStyleSheet();
		if (!this.cssLoaded && (this.cssParseCount < 17)) {
			this.cssTimeout = this.buildCssArray.defer(200, this, [editor, dropDownId]);
			this.cssParseCount++;
		} else {
			this.cssTimeout = null;
			this.cssLoaded = true;
			this.cssArray = this.sortCssArray(this.cssArray);
			this.updateValue(dropDownId);
		}
	},
	
	/*
	 * This function parses the stylesheets
	 */
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
	
	/*
	 * This function parses IE import rules
	 */
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
	
	/*
	 * This function parses gecko css rules
	 */
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
	
	/*
	 * This function parses each selector rule
	 */
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
								className = "none";
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
	
	/*
	 * This function sorts the main array of class selectors
	 */
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
	}
});

