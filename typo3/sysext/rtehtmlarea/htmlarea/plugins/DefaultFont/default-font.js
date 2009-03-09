/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Default Font Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
DefaultFont = HTMLArea.Plugin.extend({
		
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		
		this.options = new Object();
		this.options.FontName = this.editorConfiguration.buttons.fontstyle ? this.editorConfiguration.buttons.fontstyle.options : null;
		this.options.FontSize = this.editorConfiguration.buttons.fontsize ? this.editorConfiguration.buttons.fontsize.options : null;
		this.disablePCexamples = this.editorConfiguration.disablePCexamples
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "Fructifor Inc.",
			sponsorUrl	: "http://www.fructifor.ca/",
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
	 * This function gets called when some font style or font size was selected from the dropdown lists
	 */
	onChange : function (editor, buttonId) {
		var select = document.getElementById(this.editor._toolbarObjects[buttonId].elementId);
		var param = select.value;
		editor.focusEditor();
		
	    	if (param) {
			this.editor._doc.execCommand(buttonId, false, param);
		} else {
			var selection = this.editor._getSelection();
				// Find font node and select it
			if (HTMLArea.is_gecko && selection.isCollapsed) {
				var fontNode = this.editor._getFirstAncestor(selection, "font");
				if (fontNode != null) {
					this.editor.selectNode(fontNode);
				}
			}
				// Remove format
			this.editor._doc.execCommand("RemoveFormat", false, null);
				// Collapse range if font was found
			if (HTMLArea.is_gecko && fontNode != null) {
				selection = this.editor._getSelection();
				var range = this.editor._createRange(selection).cloneRange();
				range.collapse(false);
				this.editor.emptySelection(selection);
				this.editor.addRangeToSelection(selection, range);
			}
		}
		return false;
	},
	
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar : function () {
		var editor = this.editor;
		var buttonId, k;
		if (editor.getMode() === "wysiwyg" && this.editor.isEditable()) {
			for (var i = 0, n = this.dropDownList.length; i < n; ++i) {
				buttonId = this.dropDownList[i][0];
				if (this.isButtonInToolbar(buttonId)) {
					var select = document.getElementById(editor._toolbarObjects[buttonId].elementId);
					select.selectedIndex = 0;
					try {
						var value = ("" + editor._doc.queryCommandValue(buttonId)).trim().toLowerCase().replace(/\'/g, "");
					} catch(e) {
						value = null;
					}
					if (value) {
						var options = this.options[buttonId];
						k = 0;
						for (var j in options) {
							if (options.hasOwnProperty(j)) {
								if ((j.toLowerCase().indexOf(value) !== -1)
										|| (options[j].trim().substr(0, value.length).toLowerCase() == value)
										|| ((buttonId === "FontName") && (options[j].toLowerCase().indexOf(value) !== -1))) {
									select.selectedIndex = k;
									break;
								}
								++k;
							}
						}
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

