/***************************************************************
*  Copyright notice
*
* (c) 2004 systemconcept.de. Authored by Holger Hees, sponsored by http://www.systemconcept.de.
* (c) 2004-2008 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Dynamic CSS Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
DynamicCSS = HTMLArea.Plugin.extend({

	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},

		/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		var editorNumber = editor._editorNumber;

			/* Registering plugin "About" information */
		var pluginInformation = {
			version		: "1.9",
			developer	: "Holger Hees & Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Holger Hees & Stanislas Rolland",
			sponsor		: "Fructifor Inc.",
			sponsorUrl	: "System Concept GmbH & Fructifor Inc.",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

			/* Registering the dropdown list */
		var buttonId = "DynamicCSS-class";
		var dropDownConfiguration = {
			id			: buttonId,
			tooltip			: this.localize("DynamicCSSStyleTooltip"),
			options			: {"":""},
			action			: "onSelect",
			refresh			: "generate",
			context			: "*",
			cssArray		: new Object(),
			parseCount		: 1,
			loaded			: false,
			timeout			: null,
			lastTag			: "",
			lastClass		: "",
			showTagFreeClasses	: RTEarea[editorNumber]["showTagFreeClasses"],
			classesUrl		: RTEarea[editorNumber]["classesUrl"],
			classesTag		: RTEarea[editorNumber]["classesTag"]
		};
		this.registerDropDown(dropDownConfiguration);

		return true;
	},

	onSelect : function(editor, buttonId) {
		var obj = this.editorConfiguration.customSelects[buttonId];
		var tbobj = editor._toolbarObjects[buttonId];
		var index = document.getElementById(tbobj.elementId).selectedIndex;
		var className = document.getElementById(tbobj.elementId).value;

		editor.focusEditor();
		var blocks = this.getSelectedBlocks(editor);
		for (var k = 0; k < blocks.length; ++k) {
			var parent = blocks[k];
			while (typeof(parent) != "undefined" && !HTMLArea.isBlockElement(parent) && parent.nodeName.toLowerCase() != "img") parent = parent.parentNode;
			if (!k) var tagName = parent.tagName.toLowerCase();
			if (parent.tagName.toLowerCase() == tagName) {
				var cls = parent.className.trim().split(" ");
				for (var i = cls.length; i > 0;) if(!HTMLArea.reservedClassNames.test(cls[--i])) HTMLArea._removeClass(parent,cls[i]);
				if(className != 'none'){
					HTMLArea._addClass(parent,className);
					obj.lastClass = className;
				}
			}
		}
	},

	getSelectedBlocks : function(editor) {
		var block, range, i = 0, blocks = [];
		if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) {
			var sel = editor._getSelection();
			try {
				while (range = sel.getRangeAt(i++)) {
					block = editor.getParentElement(sel, range);
					blocks.push(block);
				}
			} catch(e) {
				/* finished walking through selection */
			}
		} else {
			blocks.push(editor.getParentElement());
		}
		return blocks;
	},

	onGenerate : function() {
		var editor = this.editor;
		var obj = editor.config.customSelects["DynamicCSS-class"];
		if(HTMLArea.is_gecko) this.generate(editor);
	},

	onUpdateToolbar : function() {
		var editor = this.editor;
		var obj = editor.config.customSelects["DynamicCSS-class"];
		if (HTMLArea.is_gecko && editor.getMode() === "wysiwyg" && editor.isEditable()) {
			if(obj.loaded) {
				this.updateValue(editor,obj);
			} else {
				if(obj.timeout) {
					if(editor._iframe.contentWindow) { editor._iframe.contentWindow.clearTimeout(obj.timeout); } else { window.clearTimeout(obj.timeout); }
					obj.timeout = null;
				}
				this.generate(editor);
			}
		} else if (editor.getMode() === "textmode") {
			var select = document.getElementById(editor._toolbarObjects[obj.id].elementId);
			select.disabled = true;
			select.className = "buttonDisabled";
		}
	},

	generate : function(editor) {
		var obj = editor.config.customSelects["DynamicCSS-class"];
		var classesUrl = obj["classesUrl"];
		if (classesUrl && typeof(HTMLArea.classesLabels) == "undefined") {
			var classesData = HTMLArea._getScript(0, false, classesUrl);
			if (classesData) eval(classesData);
		}
			// Let us load the style sheets
		if(obj.loaded) this.updateValue(editor,obj);
			else this.getCSSArray(editor);
	},

	getCSSArray : function(editor) {
		var obj = editor.config.customSelects["DynamicCSS-class"];
		obj.cssArray = this.parseStyleSheet(editor);
		if( !obj.loaded && obj.parseCount<17 ) {
			var getCSSArrayLaterFunctRef = DynamicCSS.getCSSArrayLater(editor, this);
			obj.timeout = editor._iframe.contentWindow ? editor._iframe.contentWindow.setTimeout(getCSSArrayLaterFunctRef, 200) : window.setTimeout(getCSSArrayLaterFunctRef, 200);
			obj.parseCount++ ;
		} else {
			obj.timeout = null;
			obj.loaded = true;
			this.updateValue(editor,obj);
		}
	},

	onMode : function(mode) {
		var editor = this.editor;
		if (mode == 'wysiwyg' && editor.isEditable()) {
			var obj = editor.config.customSelects["DynamicCSS-class"];
			if (obj.loaded) {
				this.updateValue(editor,obj);
			} else {
				if(obj.timeout) {
					if (editor._iframe.contentWindow) editor._iframe.contentWindow.clearTimeout(obj.timeout);
						else window.clearTimeout(obj.timeout);
					obj.timeout = null;
				}
			this.generate(editor);
			}
		}
	},

	updateValue : function(editor,obj) {
		var cssClass, i;
		if(!obj.loaded) {
			if(obj.timeout) {
				if(editor._iframe.contentWindow) editor._iframe.contentWindow.clearTimeout(obj.timeout);
					else window.clearTimeout(obj.timeout);
				obj.timeout = null;
			}
			this.generate(editor);
		}
		var cssArray = obj.cssArray;
		var tagName = "body";
		var className = "";
		var parent = editor.getParentElement();
		while(parent && typeof(parent) != "undefined" && !HTMLArea.isBlockElement(parent) && parent.nodeName.toLowerCase() != "img") parent = parent.parentNode;
		if(parent) {
			tagName = parent.nodeName.toLowerCase();
			className = parent.className;
			if(HTMLArea.reservedClassNames.test(className)) {
				var cls = className.split(" ");
				for (var i = cls.length; i > 0;) if(!HTMLArea.reservedClassNames.test(cls[--i])) className = cls[i];
			}
		}
		if(obj.lastTag != tagName || obj.lastClass != className){
			obj.lastTag = tagName;
			obj.lastClass = className;
			var select = document.getElementById(editor._toolbarObjects[obj.id].elementId);
			while(select.options.length>0) select.options[select.length-1] = null;
			select.options[0]=new Option(this.localize("Default"),'none');
			if(cssArray){
					// style class only allowed if parent tag is not body or editor is in fullpage mode
				if(tagName != 'body' || editor.config.fullPage){
					if(cssArray[tagName]){
						for (cssClass in cssArray[tagName]){
							if (cssArray[tagName].hasOwnProperty(cssClass)) {
								if (cssClass == 'none') {
									select.options[0] = new Option(cssArray[tagName][cssClass],cssClass);
								} else {
									select.options[select.options.length] = new Option(cssArray[tagName][cssClass],cssClass);
									if (!editor.config.disablePCexamples && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) select.options[select.options.length-1].setAttribute("style", HTMLArea.classesValues[cssClass]);
								}
							}
						}
					}
					if (cssArray['all']){
						for (cssClass in cssArray['all']){
							if (cssArray['all'].hasOwnProperty(cssClass)) {
								select.options[select.options.length] = new Option(cssArray['all'][cssClass],cssClass);
								if (!editor.config.disablePCexamples && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) select.options[select.options.length-1].setAttribute("style", HTMLArea.classesValues[cssClass]);
							}
						}
					}
				} else {
					if(cssArray[tagName] && cssArray[tagName]['none']) select.options[0] = new Option(cssArray[tagName]['none'],'none');
				}
			}
			select.selectedIndex = 0;
			if (typeof(className) != "undefined" && /\S/.test(className) && !HTMLArea.reservedClassNames.test(className) ) {
				for (i = select.options.length; --i >= 0;) {
					var option = select.options[i];
					if (className == option.value) {
						select.selectedIndex = i;
						break;
					}
				}
				if (select.selectedIndex == 0) {
					select.options[select.options.length] = new Option(this.localize("Undefined"),className);
					select.selectedIndex = select.options.length-1;
				}
			}
			if (select.options.length > 1) {
				select.disabled = false;
			} else select.disabled = true;
			if(HTMLArea.is_gecko) select.removeAttribute('class');
				else select.removeAttribute('className');
			if (select.disabled) HTMLArea._addClass(select, "buttonDisabled");
		}
	},

	parseStyleSheet : function(editor) {
		var obj = editor.config.customSelects["DynamicCSS-class"];
		var iframe = editor._iframe.contentWindow ? editor._iframe.contentWindow.document : editor._iframe.contentDocument;
		var newCssArray = new Object();
		obj.loaded = true;
		for (var i = 0; i < iframe.styleSheets.length; i++) {
				// Mozilla
			if(HTMLArea.is_gecko){
				try { newCssArray = this.applyCSSRule(editor,iframe.styleSheets[i].cssRules,newCssArray); }
				catch (e) { obj.loaded = false; }
			} else {
				try{
						// @import StyleSheets (IE)
					if (iframe.styleSheets[i].imports) newCssArray = this.applyCSSIEImport(editor,iframe.styleSheets[i].imports,newCssArray);
					if (iframe.styleSheets[i].rules) newCssArray = this.applyCSSRule(editor,iframe.styleSheets[i].rules,newCssArray);
				} catch (e) { obj.loaded = false; }
			}
		}
		return newCssArray;
	},

	applyCSSRule : function(editor, cssRules, cssArray){
		var cssElements = new Array(),
			cssElement = new Array(),
			newCssArray = new Object(),
			classParts = new Array(),
			tagName, className, rule, k,
			obj = editor.config.customSelects["DynamicCSS-class"];
		newCssArray = cssArray;

		for (rule = 0; rule < cssRules.length; rule++) {
				// StyleRule
			if (cssRules[rule].selectorText) {
				if (cssRules[rule].selectorText.search(/:+/) == -1) {
						// split equal Styles e.g. head, body {border:0px}
					cssElements = cssRules[rule].selectorText.split(",");
					for (k = 0; k < cssElements.length; k++) {
						cssElement = cssElements[k].split(".");
						tagName = cssElement[0].toLowerCase().trim();
						if (!tagName) tagName = "all";
						className = cssElement[1];
						if (className) {
							classParts = className.trim().split(" ");
							className = classParts[0];
						}
						if (!HTMLArea.reservedClassNames.test(className) && ((tagName == "all" && obj["showTagFreeClasses"] == true) || (tagName != "all" && (!obj["classesTag"] || !obj["classesTag"][tagName])) || (tagName != "all" && obj["classesTag"][tagName].indexOf(className) != -1)) ) {
							if (!newCssArray[tagName]) newCssArray[tagName] = new Object();
							if (className) {
								cssName = className;
								if (HTMLArea.classesLabels) cssName = HTMLArea.classesLabels[className] ? HTMLArea.classesLabels[className] : cssName ;
								if (tagName != 'all') cssName = '<'+cssName+'>';
							} else {
								className='none';
								if (tagName=='all') cssName=this.localize("Default");
									else cssName='<'+this.localize("Default")+'>';
							}
							newCssArray[tagName][className]=cssName;
						}
					}
				}
			} else {
					// ImportRule (Mozilla)
				if (cssRules[rule].styleSheet) {
					newCssArray = this.applyCSSRule(editor, cssRules[rule].styleSheet.cssRules, newCssArray);
				}
					// MediaRule (Mozilla)
				if (cssRules[rule].cssRules) {
					newCssArray = this.applyCSSRule(editor, cssRules[rule].cssRules, newCssArray);
				}
			}
		}
		return newCssArray;
	},

	applyCSSIEImport : function(editor,cssIEImport,cssArray){
		var newCssArray = new Object();
		newCssArray = cssArray;
		for (var i=0;i<cssIEImport.length;i++){
			if(cssIEImport[i].imports){
				newCssArray = this.applyCSSIEImport(editor,cssIEImport[i].imports,newCssArray);
			}
			if(cssIEImport[i].rules){
				newCssArray = this.applyCSSRule(editor,cssIEImport[i].rules,newCssArray);
			}
		}
		return newCssArray;
	}
});

DynamicCSS.getCSSArrayLater = function(editor,instance) {
	return (function() {
		instance.getCSSArray(editor);
	});
};

