/***************************************************************
*  Copyright notice
*
*  (c) 2004-2007 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Inline CSS Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */

InlineCSS = Class.create(HTMLArea.plugin, {
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		var editorNumber = editor._editorNumber;
		
			/* Registering plugin "About" information */
		var pluginInformation = {
			version		: "1.5",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "Fructifor Inc.",
			sponsorUrl	: "http://www.fructifor.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
			/* Registering the dropdown list */
		var buttonId = "InlineCSS-class";
		var dropDownConfiguration = {
			id			: buttonId,
			tooltip			: this.localize(buttonId + "-Tooltip"),
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
			classesCharacter	: RTEarea[editorNumber]["classesTag"]["span"]
		};
		this.registerDropDown(dropDownConfiguration);
		
		return true;
	}
});

InlineCSS.parseStyleSheet = function(editor){
	var obj = editor.config.customSelects["InlineCSS-class"];
	var iframe = editor._iframe.contentWindow ? editor._iframe.contentWindow.document : editor._iframe.contentDocument;
	var newCssArray = new Object();
	obj.loaded = true;
	for(var i=0;i<iframe.styleSheets.length;i++){
			// Mozilla
            if(HTMLArea.is_gecko){
			try{ newCssArray = InlineCSS.applyCSSRule(editor,HTMLArea.I18N.InlineCSS,iframe.styleSheets[i].cssRules,newCssArray); }
			catch(e){ obj.loaded = false; }
		} else {
			try{
					// @import StyleSheets (IE)
				if(iframe.styleSheets[i].imports){
					newCssArray = InlineCSS.applyCSSIEImport(editor,HTMLArea.I18N.InlineCSS,iframe.styleSheets[i].imports,newCssArray);
				}
				if(iframe.styleSheets[i].rules){
					newCssArray = InlineCSS.applyCSSRule(editor,HTMLArea.I18N.InlineCSS,iframe.styleSheets[i].rules,newCssArray);
				}
			} catch(e) { obj.loaded = false; }
		}
	}
	return newCssArray;
};

InlineCSS.applyCSSRule = function(editor,i18n,cssRules,cssArray){
	var cssElements = new Array();
	var cssElement = new Array();
	var newCssArray = new Object();
	var tagName, className, rule, k;
	var obj = editor.config.customSelects["InlineCSS-class"];
	newCssArray = cssArray;
	for(rule=0;rule<cssRules.length;rule++){
			// StyleRule
		if(cssRules[rule].selectorText){
			if(cssRules[rule].selectorText.search(/:+/)==-1){
					// split equal Styles (Mozilla-specific) e.q. head, body {border:0px}
					// for ie not relevant. returns allways one element
				cssElements = cssRules[rule].selectorText.split(",");
				for(k=0;k<cssElements.length;k++){
					cssElement = cssElements[k].split(".");
					tagName = cssElement[0].toLowerCase().trim();
					if(!tagName) tagName = 'all';
					className = cssElement[1];
					if( (!obj["classesCharacter"] && (tagName == 'span')) || ((tagName != "all" || obj["showTagFreeClasses"] == true) && obj["classesCharacter"] && obj["classesCharacter"].indexOf(className) != -1)) {
						if(!newCssArray[tagName]) newCssArray[tagName] = new Object();
						if(className){
							cssName = className;
							if (HTMLArea.classesLabels) cssName = HTMLArea.classesLabels[className] ? HTMLArea.classesLabels[className] : cssName ;
							if (tagName != 'all') cssName = '<'+cssName+'>';
						} else {
							className = 'none';
							if(tagName == 'all') cssName = i18n["Default"];
								else cssName = '<'+i18n["Default"]+'>';
						}
						newCssArray[tagName][className] = cssName;
					}
				}
			}
		} else {
				// ImportRule (Mozilla)
			if (cssRules[rule].styleSheet) {
				newCssArray = InlineCSS.applyCSSRule(editor, i18n, cssRules[rule].styleSheet.cssRules, newCssArray);
			}
				// MediaRule (Mozilla)
			if (cssRules[rule].cssRules) {
				newCssArray = InlineCSS.applyCSSRule(editor, i18n, cssRules[rule].cssRules, newCssArray);
			}
		}
	}
	return newCssArray;
};

InlineCSS.applyCSSIEImport=function(editor,i18n,cssIEImport,cssArray){
	var newCssArray = new Object();
	newCssArray = cssArray;

	for(var i=0;i<cssIEImport.length;i++){
		if(cssIEImport[i].imports){
			newCssArray = InlineCSS.applyCSSIEImport(editor,i18n,cssIEImport[i].imports,newCssArray);
		}
		if(cssIEImport[i].rules){
			newCssArray = InlineCSS.applyCSSRule(editor,i18n,cssIEImport[i].rules,newCssArray);
		}
	}
	return newCssArray;
};

InlineCSS.getCSSArrayLater = function(editor,instance) {
	return (function() {
		instance.getCSSArray(editor);
	});
};

/*
 * Definition of additional methods
 */
InlineCSS.addMethods({
	onSelect : function(editor, buttonId) {
		var obj = this.editorConfiguration.customSelects[buttonId];
		var tbobj = editor._toolbarObjects[buttonId];
		var index = document.getElementById(tbobj.elementId).selectedIndex;
		var className = document.getElementById(tbobj.elementId).value;
		var selTrimmed;
		
		editor.focusEditor();
		var selectedHTML = editor.getSelectedHTMLContents();
		if (selectedHTML) {
			selTrimmed = selectedHTML.replace(/(<[^>]*>|&nbsp;|\n|\r)/g,"");
		}
		var parent = editor.getParentElement();
		if( (HTMLArea.is_gecko && /\w/.test(selTrimmed) == true) || (HTMLArea.is_ie && /\S/.test(selTrimmed) == true) ) {
			var sel = editor._getSelection();
			var range = editor._createRange(sel);
			if( className != 'none' ) {
				obj.lastClass = className;
				if(parent && !HTMLArea.isBlockElement(parent) && selectedHTML.replace(/^\s*|\s*$/g,"") == parent.innerHTML.replace(/^\s*|\s*$/g,"") ) {
					parent.className = className;
				} else {
					if(HTMLArea.is_gecko) {
						var rangeClone = range.cloneRange();
						var span = editor._doc.createElement("span");
						span.className = className;
						span.appendChild(range.extractContents());
						range.insertNode(span);
						if(HTMLArea.is_safari) {
							sel.empty();
							sel.setBaseAndExtent(rangeClone.startContainer,rangeClone.startOffset,rangeClone.endContainer,rangeClone.endOffset);
						} else {
							sel.removeRange(range);
							sel.addRange(rangeClone);
						}
						range.detach();
					} else {
						var tagopen = '<span class="' + className + '">';
						var tagclose = "</span>";
						editor.surroundHTML(tagopen,tagclose);
					}
				}
			} else {
				if (parent && !HTMLArea.isBlockElement(parent)) {
					if (HTMLArea.is_gecko) {
						parent.removeAttribute('class');
					} else {
						parent.removeAttribute('className');
					}
					if (parent.tagName.toLowerCase() == "span") {
						p = parent.parentNode;
						while (parent.firstChild) {
							p.insertBefore(parent.firstChild, parent);
						}
						p.removeChild(parent);
					}
				}
			}
			editor.updateToolbar();
		} else {
			editor.updateToolbar();
			alert(HTMLArea.I18N.InlineCSS['You have to select some text']);
		}
	},
	
	onGenerate : function() {
		var editor = this.editor;
		var obj = editor.config.customSelects["InlineCSS-class"];
		if (HTMLArea.is_gecko) {
			this.generate(editor);
		}
	},
	
	onUpdateToolbar : function() {
		var editor = this.editor;
		var obj = editor.config.customSelects["InlineCSS-class"];
		if (HTMLArea.is_gecko && editor._editMode != "textmode") {
			if (obj.loaded) { 
				this.updateValue(editor,obj);
			} else {
				if (obj.timeout) {
					if (editor._iframe.contentWindow) {
						editor._iframe.contentWindow.clearTimeout(obj.timeout);
					} else {
						window.clearTimeout(obj.timeout);
					}
					obj.timeout = null;
				}
				this.generate(editor);
			}
		}
	},
	
	generate : function(editor) {
		var obj = editor.config.customSelects["InlineCSS-class"];
		var classesUrl = obj["classesUrl"];
		if (classesUrl && typeof(HTMLArea.classesLabels) === "undefined") {
			this.getJavascriptFile(classesUrl);
		}
			// Let us load the style sheets
		if (obj.loaded) {
			this.updateValue(editor,obj);
		} else {
			this.getCSSArray(editor);
		}
	},
	
	getCSSArray : function(editor) {
		var obj = editor.config.customSelects["InlineCSS-class"];
		obj.cssArray = InlineCSS.parseStyleSheet(editor);
		if ( !obj.loaded && obj.parseCount<17 ) {
			var getCSSArrayLaterFunctRef = InlineCSS.getCSSArrayLater(editor, this);
			obj.timeout = editor._iframe.contentWindow ? editor._iframe.contentWindow.setTimeout(getCSSArrayLaterFunctRef, 200) : window.setTimeout(getCSSArrayLaterFunctRef, 200);
			obj.parseCount++ ;
		} else {
			obj.timeout = null;
			obj.loaded = true;
			this.updateValue(editor, obj);
		}
	},
	
	onMode : function(mode) {
		var editor = this.editor;
		if (mode=='wysiwyg'){
			var obj = editor.config.customSelects["InlineCSS-class"];
			if (obj.loaded) { 
				this.updateValue(editor,obj);
			} else {
				if (obj.timeout) {
					if (editor._iframe.contentWindow) {
						editor._iframe.contentWindow.clearTimeout(obj.timeout);
					} else {
						window.clearTimeout(obj.timeout);
					}
					obj.timeout = null;
				}
				this.generate(editor);
			}
		}
	},
	
	updateValue : function(editor,obj) {
		var cssClass, i;
		
		if (!obj.loaded) {
			if (obj.timeout) {
				if (editor._iframe.contentWindow) {
					editor._iframe.contentWindow.clearTimeout(obj.timeout);
				} else {
					window.clearTimeout(obj.timeout);
				}
				obj.timeout = null;
			}
			this.generate(editor);
		}
		
		var cssArray = obj.cssArray;
		var tagName = "body";
		var className = "";
		var parent = editor.getParentElement();
		if (parent) {
			tagName = parent.nodeName.toLowerCase();
			className = parent.className;
		}
		
		var selTrimmed = editor.getSelectedHTMLContents();
		if(selTrimmed) {
			selTrimmed = selTrimmed.replace(/(<[^>]*>|&nbsp;|\n|\r)/g,"");
		}
		
		var endPointsInSameBlock = false;
		if ( (HTMLArea.is_gecko && /\w/.test(selTrimmed) == true) || (HTMLArea.is_ie && /\S/.test(selTrimmed) == true) ) {
			var sel = editor._getSelection();
			var range = editor._createRange(sel);
			if (HTMLArea.is_gecko) {
				if (sel.rangeCount == 1 || HTMLArea.is_safari) {
					var parentStart = range.startContainer;
					var parentEnd = range.endContainer;
					if ( !(parentStart.nodeType == 1 && parentStart.tagName.toLowerCase() == "tr") ) {
						while (parentStart && !HTMLArea.isBlockElement(parentStart)) {
							parentStart = parentStart.parentNode;
						}
						while (parentEnd && !HTMLArea.isBlockElement(parentEnd)) {
							parentEnd = parentEnd.parentNode;
						}
						endPointsInSameBlock = (parentStart == parentEnd) && (parent.tagName.toLowerCase() != "body") && (parent.tagName.toLowerCase() != "table") && (parent.tagName.toLowerCase() != "tbody") && (parent.tagName.toLowerCase() != "tr");
					}
				}
			} else {
				if (sel.type != "Control" ) {
					var rangeStart = range.duplicate();
					rangeStart.collapse(true);
					var rangeEnd = range.duplicate();
					rangeEnd.collapse(false);
					var parentStart = rangeStart.parentElement();
					var parentEnd = rangeEnd.parentElement();
					while (parentStart && !HTMLArea.isBlockElement(parentStart)) {
						parentStart = parentStart.parentNode;
					}
					while (parentEnd && !HTMLArea.isBlockElement(parentEnd)) {
						parentEnd = parentEnd.parentNode;
					}
					endPointsInSameBlock = (parentStart == parentEnd) && (parent.tagName.toLowerCase() != "body")  ;
				}
			}
		}
		
		var select = document.getElementById(editor._toolbarObjects[obj.id].elementId);
		select.disabled = !(/\w/.test(selTrimmed)) || !(endPointsInSameBlock);
		
		obj.lastTag = tagName;
		obj.lastClass = className;
		while(select.options.length>0) {
			select.options[select.length-1] = null;
		}
		select.options[0]=new Option(HTMLArea.I18N.InlineCSS["Default"],'none');
		if (cssArray){
				// we are in span and 'all' tags only
			if (cssArray['span']) {
				for (cssClass in cssArray['span']) {
					if (cssClass == 'none') {
						select.options[0] = new Option(cssArray['span'][cssClass],cssClass);
					} else {
						select.options[select.options.length] = new Option(cssArray['span'][cssClass],cssClass);
						if (!editor.config.disablePCexamples && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) {
							select.options[select.options.length-1].setAttribute("style", HTMLArea.classesValues[cssClass]);
						}
					}
				}
			}
			if (cssArray['all']){
				for (cssClass in cssArray['all']) {
					select.options[select.options.length] = new Option(cssArray['all'][cssClass],cssClass);
					if (!editor.config.disablePCexamples && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) {
						select.options[select.options.length-1].setAttribute("style", HTMLArea.classesValues[cssClass]);
					}
				}
			}
		}
		select.selectedIndex = 0;
		//var selected = false;
		//select.multiple = true;
		//select.size = 2;
		if (typeof className != "undefined" && /\S/.test(className) && !HTMLArea.reservedClassNames.test(className)) {
			for (i = select.options.length; --i >= 0;) {
				var option = select.options[i];
				if (className == option.value) {
					option.selected = true;
					//selected = true;
					select.selectedIndex = i;
					break;
				}
			}
			if (select.selectedIndex == 0) {
				//if(!selected){
				select.options[select.options.length] = new Option(HTMLArea.I18N.InlineCSS["Undefined"],className);
				select.selectedIndex = select.options.length-1;
				//select.options[select.options.length-1].selected = true;
			}
		}
		select.disabled = !(select.options.length>1) || !endPointsInSameBlock || !((HTMLArea.is_gecko && /\w/.test(selTrimmed) == true) || (HTMLArea.is_ie && /\S/.test(selTrimmed) == true)) ;
		select.className = "";
		if (select.disabled) {
			select.className = "buttonDisabled";
		}
	}
});
