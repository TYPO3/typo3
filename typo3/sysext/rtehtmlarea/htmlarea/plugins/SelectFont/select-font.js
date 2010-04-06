/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * SelectFont Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
SelectFont = HTMLArea.Plugin.extend({
		
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {

		this.options = new Object();
		this.defaultValue = new Object();
		if (this.editorConfiguration.buttons.fontstyle) {
			this.options.FontName = this.editorConfiguration.buttons.fontstyle.options;
			if (this.editorConfiguration.buttons.fontstyle.defaultItem) {
				this.defaultValue.FontName = this.editorConfiguration.buttons.fontstyle.options[this.editorConfiguration.buttons.fontstyle.defaultItem];
			}
		}
		if (this.editorConfiguration.buttons.fontsize) {
			this.options.FontSize = this.editorConfiguration.buttons.fontsize.options;
			if (this.editorConfiguration.buttons.fontsize.defaultItem) {
				this.defaultValue.FontSize = this.editorConfiguration.buttons.fontsize.options[this.editorConfiguration.buttons.fontsize.defaultItem];
			}
		}
		this.disablePCexamples = this.editorConfiguration.disablePCexamples;

			// Font formating will use the style attribute
		if (this.editor.getPluginInstance("TextStyle")) {
			this.editor.getPluginInstance("TextStyle").addAllowedAttribute("style");
			this.allowedAttributes = this.editor.getPluginInstance("TextStyle").allowedAttributes;
		}
		if (this.editor.getPluginInstance("InlineElements")) {
			this.editor.getPluginInstance("InlineElements").addAllowedAttribute("style");
			if (!this.allowedAllowedAttributes) {
				this.allowedAttributes = this.editor.getPluginInstance("InlineElements").allowedAttributes;
			}
		}
		if (this.editor.getPluginInstance("BlockElements")) {
			this.editor.getPluginInstance("BlockElements").addAllowedAttribute("style");
		}
		if (!this.allowedAttributes) {
			this.allowedAttributes = new Array("id", "title", "lang", "xml:lang", "dir", "class", "style");
			if (HTMLArea.is_ie) {
				this.allowedAttributes.push("className");
			}
		}

		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the dropdowns
		 */
		var buttonId;
		for (var i = 0, n = this.dropDownList.length; i < n; ++i) {
			var dropDown = this.dropDownList[i];
			buttonId = dropDown[0];
			var dropDownConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId.toLowerCase()),
				options		: this.options[buttonId],
				action		: "onChange",
				context		: null
			};
			this.registerDropDown(dropDownConfiguration);
		}
		return true;
	 },
	 
	/*
	 * The list of buttons added by this plugin
	 */
	dropDownList : [
		["FontName", null],
		["FontSize", null]
	],
	
	/*
	 * Conversion object: button name to corresponding style property name
	 */
	styleProperty : {
		FontName	: "fontFamily",
		FontSize	: "fontSize"
	},
	
	/*
	 * Conversion object: button name to corresponding css property name
	 */
	cssProperty : {
		FontName	: "font-family",
		FontSize	: "font-size"
	},
	 
	/*
	 * This function gets called when some font style or font size was selected from the dropdown lists
	 */
	onChange : function (editor, buttonId) {
		var select = document.getElementById(this.editor._toolbarObjects[buttonId].elementId),
			param = select.value;
		if (!select.selectedIndex) {
			param = "";
		}
		editor.focusEditor();
		var element, fullNodeSelected = false;
		var selection = editor._getSelection();
		var range = editor._createRange(selection);
		var parent = editor.getParentElement(selection, range);
		var ancestors = editor.getAllAncestors();
		var selectionEmpty = editor._selectionEmpty(selection);
		var statusBarSelection = editor.getPluginInstance("StatusBar") ? editor.getPluginInstance("StatusBar").getSelection() : null;
		if (!selectionEmpty) {
				// The selection is not empty.
			for (var i = 0; i < ancestors.length; ++i) {
				fullNodeSelected = (HTMLArea.is_ie && ((selection.type !== "Control" && ancestors[i].innerText === range.text) || (selection.type === "Control" && ancestors[i].innerText === range.item(0).text)))
							|| (HTMLArea.is_gecko && ((statusBarSelection === ancestors[i] && ancestors[i].textContent === range.toString()) || (!statusBarSelection && ancestors[i].textContent === range.toString())));
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
			fullNodeSelected = (HTMLArea.is_gecko && parent.textContent === range.toString())
							|| (HTMLArea.is_ie && parent.innerText === range.text);
		}
		if (selectionEmpty || fullNodeSelected) {
			element = parent;
				// Set the style attribute
			this.setStyle(element, buttonId, param);
				// Remove the span tag if it has no more attribute
			if ((element.nodeName.toLowerCase() === "span") && !HTMLArea.hasAllowedAttributes(element, this.allowedAttributes)) {
				editor.removeMarkup(element);
			}
		} else if (statusBarSelection) {
			element = statusBarSelection;
				// Set the style attribute
			this.setStyle(element, buttonId, param);
				// Remove the span tag if it has no more attribute
			if ((element.nodeName.toLowerCase() === "span") && !HTMLArea.hasAllowedAttributes(element, this.allowedAttributes)) {
				editor.removeMarkup(element);
			}
		} else if (editor.endPointsInSameBlock()) {
			element = editor._doc.createElement("span");
				// Set the style attribute
			this.setStyle(element, buttonId, param);
				// Wrap the selection with span tag with the style attribute
			editor.wrapWithInlineElement(element, selection, range);
			if (HTMLArea.is_gecko) {
				range.detach();
			}
		}
		return false;
	},

	/*
	 * This function sets the style attribute on the element
	 *
	 * @param	object	element: the element on which the style attribute is to be set
	 * @param	string	buttonId: the button being processed
	 * @param	string	value: the value to be assigned
	 *
	 * @return	void
	 */
	setStyle : function (element, buttonId, value) {
		element.style[this.styleProperty[buttonId]] = value;
			// In IE, we need to remove the empty attribute in order to unset it
		if (HTMLArea.is_ie && !value) {
			element.style.removeAttribute(this.styleProperty[buttonId], false);
		}
		if (HTMLArea.is_opera) {
				// Opera 9.60 replaces single quotes with double quotes
			element.style.cssText = element.style.cssText.replace(/\"/g, "\'");
				// Opera 9.60 removes from the list of fonts any fonts that are not installed on the client system
				// If the fontFamily property becomes empty, it is broken and cannot be reset/unset
				// We remove it using cssText
			if (!/\S/.test(element.style[this.styleProperty[buttonId]])) {
				element.style.cssText = element.style.cssText.replace(/font-family: /gi, "");
			}
		}
	},

	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar : function () {
		var editor = this.editor;
		if (editor.getMode() === "wysiwyg" && this.editor.isEditable()) {
			var statusBarSelection = editor.getPluginInstance("StatusBar") ? editor.getPluginInstance("StatusBar").getSelection() : null;
			var parentElement = statusBarSelection ? statusBarSelection : editor.getParentElement(),
				enabled = editor.endPointsInSameBlock() && !(editor._selectionEmpty(editor._getSelection()) && parentElement.nodeName.toLowerCase() == "body"),
				buttonId, value, k;
			for (var i = this.dropDownList.length; --i >= 0;) {
				buttonId = this.dropDownList[i][0];
				if (this.isButtonInToolbar(buttonId)) {
					var select = document.getElementById(editor._toolbarObjects[buttonId].elementId);
					value = parentElement.style[this.styleProperty[buttonId]];
					if (!value) {
						if (HTMLArea.is_gecko) {
							if (editor._doc.defaultView.getComputedStyle(parentElement, null)) {
								value = editor._doc.defaultView.getComputedStyle(parentElement, null).getPropertyValue(this.cssProperty[buttonId]);
							}
						} else {
							value = parentElement.currentStyle[this.styleProperty[buttonId]];
						}
					}
					select.selectedIndex = 0;
					if (value) {
						var options = this.options[buttonId];
						k = 0;
						for (var option in options) {
							if (options.hasOwnProperty(option)) {
								if (options[option] == value
										|| options[option].replace(/[\'\"]/g, "") == value.replace(/, /g, ",").replace(/[\'\"]/g, "")) {
									select.selectedIndex = k;
									break;
								}
								++k;
							}
						}
					}
					select.disabled = !enabled;
					select.className = "";
					if (select.disabled) {
						select.className = "buttonDisabled";
					}
				}
			}
		}
	},
	
	/*
	 * This function gets called when the plugin is generated
	 */
	onGenerate : function () {
		if (!this.disablePCexamples && this.isButtonInToolbar("FontName")) {
			var select = document.getElementById(this.editor._toolbarObjects.FontName.elementId);
			for (var i = select.options.length; --i >= 0;) {
				if (HTMLArea.is_gecko) select.options[i].setAttribute("style", "font-family:" + select.options[i].value + ";");
					else select.options[i].style.cssText = "font-family:" + select.options[i].value + ";";
			}
		}
	}
});
