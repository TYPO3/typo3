/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * TYPO3 Color Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
TYPO3Color = HTMLArea.Plugin.extend({
	
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		this.colorsConfiguration = this.editorConfiguration.colors;
		this.disableColorPicker = this.editorConfiguration.disableColorPicker;

			// Coloring will use the style attribute
		if (this.editor.plugins.TextStyle && this.editor.plugins.TextStyle.instance) {
			this.editor.plugins.TextStyle.instance.addAllowedAttribute("style");
			this.allowedAttributes = this.editor.plugins.TextStyle.instance.allowedAttributes;
		}			
		if (this.editor.plugins.InlineElements && this.editor.plugins.InlineElements.instance) {
			this.editor.plugins.InlineElements.instance.addAllowedAttribute("style");
			if (!this.allowedAllowedAttributes) {
				this.allowedAttributes = this.editor.plugins.InlineElements.instance.allowedAttributes;
			}
		}
		if (this.editor.plugins.BlockElements && this.editor.plugins.BlockElements.instance) {
			this.editor.plugins.BlockElements.instance.addAllowedAttribute("style");
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
			version		: "3.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the buttons
		 */
		var buttonList = this.buttonList, buttonId;
		for (var i = 0; i < buttonList.length; ++i) {
			var button = buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId),
				action		: "onButtonPress",
				hotKey		: (this.buttonsConfiguration[button[1]] ? this.buttonsConfiguration[button[1]].hotKey : null),
				dialog		: true
			};
			this.registerButton(buttonConfiguration);
		}
		
		return true;
	 },

	/*
	 * The list of buttons added by this plugin
	 */
	buttonList : [
		["ForeColor", "textcolor"],
		["HiliteColor", "bgcolor"]
	],

	/*
	 * Conversion object: button name to corresponding style property name
	 */
	styleProperty : {
		ForeColor	: "color",
		HiliteColor	: "backgroundColor"
	},

	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.dialogSelectColor(buttonId,"","");
	},

	dialogSelectColor : function (buttonId, element, field, dialogOpener) {
		var dimensions = {
			width	: 440,
			height	: 300
		};
		var arguments = {
			title 		: buttonId + "_title",
			buttonId	: buttonId,
			element		: element,
			field		: field
		};
			// buttonId's  "color" and "tag" are not registered but used to interface with the Table Operations and QuickTag plugins
		switch (buttonId) {
			case "ForeColor"	:
			case "HiliteColor"	:
				var selectColorWithButtonInitFunctRef = this.makeFunctionReference("selectColorWithButtonInit");
				arguments.initialize = selectColorWithButtonInitFunctRef;
				this.dialog = this.openDialog(buttonId, "", "setColor", arguments, dimensions, "yes");
				break;
			case "color"		:
				var selectColorCodeInitFunctRef = this.makeFunctionReference("selectColorCodeInit");
				arguments.initialize = selectColorCodeInitFunctRef;
				this.dialog = this.openDialog(buttonId, "", "setColor", arguments, dimensions, "yes", dialogOpener);
				break;
			case "tag"		:
				var selectColorCodeInitFunctRef = this.makeFunctionReference("selectColorCodeInit");
				arguments.initialize = selectColorCodeInitFunctRef;
				arguments.title = "color_title";
				this.dialog = this.openDialog(buttonId, "", "setColorInTag", arguments, dimensions, "yes", dialogOpener);
		}
	},
	
	/*
	 * Initialize the forecolor and the hilitecolor select color dialogues
	 */
	selectColorWithButtonInit : function(dialog) {
		var editor = dialog.editor;
		var doc = editor._doc;
		var buttonId = dialog.arguments.buttonId;
		var initialValue;
		var parentElement = editor.getParentElement();
		initialValue = HTMLArea._colorToRgb(parentElement.style[this.styleProperty[buttonId]]);
		dialog.content.innerHTML = this.renderPopupSelectColor(buttonId, dialog, dialog.arguments.title, initialValue);
		var colorTable = dialog.document.getElementById("colorTable");
		colorTable.onclick = function(e) {
			dialog.callFormInputHandler();
			return false;
		};
		var colorForm = dialog.document.getElementById("HA-color-select-form");
		colorForm.onsubmit = function (e) {
			dialog.callFormInputHandler();
			return false;
		};
		var colorUnset = dialog.document.getElementById("colorUnset");
		colorUnset.onclick = function(e) {
			dialog.document.getElementById(buttonId).value="";
			dialog.callFormInputHandler();
			return false;
		};
		dialog.document.getElementById(buttonId+"Current").style.backgroundColor = initialValue;
		dialog.addButtons("ok", "cancel");
	},
	
	/*
	 * Set the color and close the ForeColor and the HiliteColor select color dialogues
	 */
	setColor : function(dialog, params) {
		var editor = this.editor, element;
		switch (dialog.arguments.buttonId) {
			case "ForeColor":
			case "HiliteColor":
				var fullNodeSelected = false;
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
					var fullNodeSelected = (HTMLArea.is_gecko && parent.textContent === range.toString())
									|| (HTMLArea.is_ie && parent.innerText === range.text);
				}
				if (selectionEmpty || fullNodeSelected) {
					element = parent;
						// Set the color in the style attribute
					this.processStyle(dialog, params, element, dialog.arguments.field);
						// Remove the span tag if it has no more attribute
					if ((element.nodeName.toLowerCase() === "span") && !HTMLArea.hasAllowedAttributes(element, this.allowedAttributes)) {
						editor.removeMarkup(element);
					}
				} else if (statusBarSelection) {
					element = statusBarSelection;
						// Set the color in the style attribute
					this.processStyle(dialog, params, element, dialog.arguments.field);
						// Remove the span tag if it has no more attribute
					if ((element.nodeName.toLowerCase() === "span") && !HTMLArea.hasAllowedAttributes(element, this.allowedAttributes)) {
						editor.removeMarkup(element);
					}
				} else if (editor.endPointsInSameBlock()) {
					element = editor._doc.createElement("span");
						// Set the color in the style attribute
					this.processStyle(dialog, params, element, dialog.arguments.field);
					editor.wrapWithInlineElement(element, selection, range);
					if (HTMLArea.is_gecko) {
						range.detach();
					}
				}
				break;
			case "color":
			default:
				element = dialog.arguments.element;
					// Set the color in the style attribute
				this.processStyle(dialog, params, element, dialog.arguments.field);
				break;
		}
		dialog.close();
	},
	
	/*
	 * Initialize the case=color select color dialogue
	 * This case is used by the Table Operations and QuickTag plugins
	 */
	selectColorCodeInit : function(dialog) {
		var buttonId = dialog.arguments.buttonId;
		var field = dialog.arguments.field;
		dialog.content.innerHTML = this.renderPopupSelectColor(buttonId, dialog, this.localize(dialog.arguments.title), (field.value ? field.value : ""));
		var colorTable = dialog.document.getElementById("colorTable");
		colorTable.onclick = function(e) {
			if(!e) var e = dialog.dialogWindow.event;
			var target = e.target ? e.target : e.srcElement;
			if (target.nodeType == 3) target = target.parentNode;
			dialog.document.getElementById(buttonId).value = target.bgColor;
			dialog.callFormInputHandler();
			return false;
		};
		var colorUnset = dialog.document.getElementById("colorUnset");
		colorUnset.onclick = function(e) {
			dialog.document.getElementById(buttonId).value = "";
			dialog.callFormInputHandler();
			return false;
		};
		var colorForm = dialog.document.getElementById("HA-color-select-form");
		colorForm.onsubmit = function(e) {
			dialog.callFormInputHandler();
			return false;
		};
		if (buttonId === "color") {
			dialog.document.getElementById(buttonId+"Current").style.backgroundColor = field.value;
		} else if (buttonId === "tag"){
			dialog.document.getElementById(buttonId+"Current").style.backgroundColor = "";
		}
		dialog.addButtons("ok", "cancel");
	},
	
	/*
	* Sets the color
	*/
	setColorInTag : function(dialog, params) {
		dialog.arguments.field.insertColor(params.tag);
		dialog.close();
	},
	
	/*
	* Applies the style found in "params" to the given element
	*/
	processStyle : function (dialog, params, element, field) {
		if (element) {
			for (var i in params) {
				var val = params[i];
				if (val && val.charAt(0) != "#") {
					val = "#" + val;
				}
				switch (i) {
					case "ForeColor":
					case "HiliteColor":
						element.style[this.styleProperty[i]] = val;
						break;
					case "color":
						element.style.backgroundColor = val;
						field.value = val;
						break;
				}
			}
		}
	},
	
	/**
	 * Making color selector table
	 */
	renderPopupSelectColor : function (sID,dialog,title,initialValue) {
		var editor = this.editor;
		var cfgColors = this.colorsConfiguration;
		var colorDef;
		var szID = sID + "Current";
		var sz;
		var cPick = new Array("00","33","66","99","CC","FF");
		var iColors = cPick.length;
		var szColor = "";
		var szColorId = "";
		
		sz = '<div class="title">' + title + '</div>';
		sz += '<form id="HA-color-select-form"><fieldset>';
		sz += '<div class="colorTableWrapper"><table class="colorTable" cellspacing="0" cellpadding="0" id="colorTable">';
		var onMouseOut = ' onMouseout="document.getElementById(\'' + szID + '\').style.backgroundColor=\'\'; document.getElementById(\'' + sID + '\').value=\'\';"';
		var onMouseOver = ' onMouseover="if(' + HTMLArea.is_ie + '){ if (event.srcElement.bgColor) { document.getElementById(\'' + szID + '\').style.backgroundColor = event.srcElement.bgColor; document.getElementById(\'' + sID + '\').value = event.srcElement.bgColor;} } else { if (event.target.bgColor) { document.getElementById(\'' + szID + '\').style.backgroundColor=event.target.bgColor; document.getElementById(\'' + sID + '\').value=event.target.bgColor;} };" ';
			// Making colorPicker
		if (!this.disableColorPicker) {
			for ( var r = 0; r < iColors; r++) {
				sz+='<tr>';
				for (var g = iColors-1; g >= 0; g--) {
					for (var b=iColors-1;b>=0;b--) {
						szColor = cPick[r]+cPick[g]+cPick[b];
						sz+='<td bgcolor="#'+szColor+'" title="#'+szColor+'"' + onMouseOut + onMouseOver +'>&nbsp;</td>';
					}
				}
				sz+='</tr>';
			}
		}
		
			// Making specific color selector:
		if (cfgColors) {
			var iCfgColors = cfgColors.length;
			if (iCfgColors && !this.disableColorPicker) {
				sz += '<tr><td colspan="36"></td></tr>';
			}
			onMouseOverTitle = ' onMouseover="if(' + HTMLArea.is_ie + '){ if (document.getElementById(event.srcElement.id+\'Value\')) { document.getElementById(\'' + szID + '\').style.backgroundColor = document.getElementById(event.srcElement.id+\'Value\').bgColor; document.getElementById(\'' + sID + '\').value = document.getElementById(event.srcElement.id+\'Value\').bgColor;} } else { if (document.getElementById(event.target.id+\'Value\')) { document.getElementById(\'' + szID + '\').style.backgroundColor=document.getElementById(event.target.id+\'Value\').bgColor; document.getElementById(\'' + sID + '\').value=document.getElementById(event.target.id+\'Value\').bgColor;} };" ';
			for (var theColor = 0; theColor < iCfgColors; theColor++) {
				colorDef = cfgColors[theColor];
				szColor = colorDef[1];
				sz += '<tr' + onMouseOut + '>';
				sz += '<td id="colorDef' + theColor + 'Value"' + onMouseOver + ' style="width:36px;" colspan="6" bgcolor="'+szColor+'" title="'+szColor+'">&nbsp;</td>';
				sz += '<td class="colorTitle" id="colorDef' + theColor + '"title="' + szColor + '"' + onMouseOverTitle + 'colspan="30">'+colorDef[0]+'</td>';
				sz += '</tr>';
			}
		}
		sz += '</table>';
		sz += '<div class="space"></div>';
		sz += '<label for="' + sID + '" class="fr">Color:</label>';
		sz += '<div class="buttonColor" ';
		sz += '		onMouseover="className += \' buttonColor-hilite\';" ';
		sz += '		onMouseout="className = \'buttonColor\';">';
		sz += '	<span id="' + szID + '" class="chooser">&nbsp;</span>';
		sz += '	<span id="colorUnset" class="nocolor" title="' + "no_color" + '" ';
		sz += '		onMouseover="className += \' nocolor-hilite\';" ';
		sz += '		onMouseout="className = \'nocolor\';"';
		sz += '	>&#x00d7;</span></div>';
		sz += '<input type="text" name="' + sID + '" id="' + sID + '" value="' + initialValue + '" />';
		sz += '</fieldset></form>';
		return sz;
	},
	
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar : function () {
		var editor = this.editor;
		if (this.getEditorMode() === "wysiwyg" && editor.isEditable()) {
			var buttonId;
			var statusBarSelection = editor.getPluginInstance("StatusBar") ? editor.getPluginInstance("StatusBar").getSelection() : null;
			var parentElement = statusBarSelection ? statusBarSelection : editor.getParentElement();
			var enabled = editor.endPointsInSameBlock() && !(editor._selectionEmpty(editor._getSelection()) && parentElement.nodeName.toLowerCase() == "body");
			for (var i = 0, n = this.buttonList.length; i < n; ++i) {
				buttonId = this.buttonList[i][0];
				var obj = editor._toolbarObjects[buttonId];
				if ((typeof(obj) !== "undefined")) {
					obj.state("active", parentElement.style[this.styleProperty[buttonId]]);
					obj.state("enabled", enabled);
				}
			}
		}
	}
});

