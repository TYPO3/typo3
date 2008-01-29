/***************************************************************
*  Copyright notice
*
*  (c) 2004-2008 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * TYPO3 SVN ID: $Id: $
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
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.7",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "Fructifor Inc.",
			sponsorUrl	: "http://www.fructifor.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the buttons
		 */
		var buttonList = this.buttonList;
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
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function(editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		
		switch (buttonId) {
			case "ForeColor"	:
			case "HiliteColor"	:
				this.dialogSelectColor(buttonId,"","");
				break;
			default:
				break;
		}
	},
	
	// this function requires the file PopupWin
	dialogSelectColor : function (buttonId, element, field, opener) {
		var editor = this.editor;
		var windowWidth = 470;
		var windowHeight = 245;
		
			// buttonId's  "color" and "tag" are not registered but used to interface with the Table Operations and QuickTag plugins
		switch (buttonId) {
			case "ForeColor"	:
			case "HiliteColor"	:
				var selectColorInitFunctRef = TYPO3Color.selectColorCOInit(this, buttonId);
				var setColorFunctRef = TYPO3Color.setColorCO(this);
				this.dialog = new PopupWin(this.editor, this.localize(buttonId + "_title"), setColorFunctRef, selectColorInitFunctRef, windowWidth, windowHeight, editor._iframe.contentWindow);
				break;
			case "color"		:
				var selectColorInitFunctRef = TYPO3Color.selectColorColorInit(this, buttonId, field);
				var setColorFunctRef = TYPO3Color.setColorColor(this, element, field);
				this.dialog = new PopupWin(this.editor, this.localize(buttonId + "_title"), setColorFunctRef, selectColorInitFunctRef, windowWidth, windowHeight, opener);
				break;
			case "tag"		:
				var selectColorInitFunctRef = TYPO3Color.selectColorTagInit(this, buttonId);
				var setColorFunctRef = TYPO3Color.setColorTag(this, field);
				this.dialog = new PopupWin(this.editor, this.localize("color_title"), setColorFunctRef, selectColorInitFunctRef, windowWidth, windowHeight, opener);
		}
	},
	
	// Applies the style found in "params" to the given element.
	processStyle : function (dialog, params, element, field) {
		var editor = this.editor;
		for (var i in params) {
			var val = params[i];
			switch (i) {
				case "ForeColor":
					if(val) {
						editor._doc.execCommand("ForeColor", false, val);
					} else {
						var parentElement = editor.getParentElement();
						parentElement.style.color = "";
					}
					break;
				case "HiliteColor":
					if(val) {
						if(HTMLArea.is_ie || HTMLArea.is_safari) editor._doc.execCommand("BackColor", false, val);
							else editor._doc.execCommand("HiliteColor", false, val);
					} else {
						var parentElement = editor.getParentElement();
						parentElement.style.backgroundColor = "";
					}
					break;
				case "color":
					element.style.backgroundColor = val;
					field.value = val;
					break;
			}
		}
	},
	
	/**
	 * Making color selector table
	 */
	renderPopupSelectColor : function (sID,dialog,title) {
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
		sz += '<table style="width:100%"><tr><td id="HA-layout"><fieldset>';
		sz += '<input type="hidden" name="' + sID + '" id="' + sID + '" value="" />';
		sz += '<table style="width:100%;"><tr><td style="vertical-align: middle;"><span style="margin-left: 5px; height: 1em;" class="dialog buttonColor" ';
		sz += '		onMouseover="className += \' buttonColor-hilite\';" ';
		sz += '		onMouseout="className = \'buttonColor\';"> ';
		sz += '	<span id="' + szID + '" class="chooser"></span> ';
		sz += '	<span id="colorUnset" class="nocolor" title="' + this.localize("no_color") + '" ';
		sz += '		onMouseover="className += \' nocolor-hilite\';" ';
		sz += '		onMouseout="className = \'nocolor\';"';
		sz += '	>&#x00d7;</span></span></td><td>';
		sz += '<table ';
		sz += '	onMouseout="document.getElementById(\'' + szID + '\').style.backgroundColor=\'\';" ';
		sz += '	onMouseover="if(' + HTMLArea.is_ie + '){ if(event.srcElement.bgColor) document.getElementById(\'' + szID + '\').style.backgroundColor=event.srcElement.bgColor; } else { if (event.target.bgColor) document.getElementById(\'' + szID + '\').style.backgroundColor=event.target.bgColor; }" ';
		sz += '	class="colorTable" cellspacing="0" cellpadding="0" id="colorTable">';
			// Making colorPicker
		if (!this.disableColorPicker) {
			for ( var r = 0; r < iColors; r++) {
				sz+='<tr>';
				for (var g = iColors-1; g >= 0; g--) {
					for (var b=iColors-1;b>=0;b--) {
						szColor = cPick[r]+cPick[g]+cPick[b];
						sz+='<td bgcolor="#'+szColor+'" title="#'+szColor+'">&nbsp;</td>';
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
			for (var theColor = 0; theColor < iCfgColors; theColor++) {
				colorDef = cfgColors[theColor];
				szColor = colorDef[1];
				sz += '<tr>';
				sz += '<td style="width:36px;" colspan="6" bgcolor="'+szColor+'" title="'+szColor+'">&nbsp;</td>';
				sz += '<td colspan=2></td>';
				sz += '<td colspan=28><nobr>'+colorDef[0]+'</nobr></td>';
				sz += '</tr>';
			}
		}
		
		sz += '</table></td></tr></table>';
		sz += '</fieldset></td></tr><tr><td id="HA-style"></td></tr></table>';
		return sz;
	}
});

/*
 * Initialize the forecolor and the hilitecolor select color dialogs
 */
TYPO3Color.selectColorCOInit = function(instance, buttonId) {
	return (function(dialog) {
		var editor = dialog.editor;
		var doc = editor._doc;
		dialog.content.innerHTML = instance.renderPopupSelectColor(buttonId, dialog, instance.localize(buttonId + "_title"));
		var colorTable = dialog.doc.getElementById("colorTable");
		colorTable.onclick = function(e) {
			if(!e) var e = dialog.dialogWindow.event;
			var targ = e.target ? e.target : e.srcElement;
			if (targ.nodeType == 3) targ = targ.parentNode;
			dialog.doc.getElementById(buttonId).value = targ.bgColor ? targ.bgColor : "";
			dialog.callHandler();
			return false;
		};
		var colorUnset = dialog.doc.getElementById("colorUnset");
		colorUnset.onclick = function(e) {
			dialog.doc.getElementById(buttonId).value="";
			dialog.callHandler();
			return false;
		};
		try {
			with (dialog.doc.getElementById(buttonId+"Current").style) {
				switch (buttonId) {
					case "ForeColor":
						backgroundColor = HTMLArea._makeColor(doc.queryCommandValue("ForeColor"));
						break;
					case "HiliteColor":
						backgroundColor = HTMLArea._makeColor(doc.queryCommandValue(((HTMLArea.is_ie || HTMLArea.is_safari) ? "BackColor" : "HiliteColor")));
						if (/transparent/i.test(backgroundColor)) {
								// Mozilla
							backgroundColor = HTMLArea._makeColor(doc.queryCommandValue("BackColor"));
						}
						break;
				}
			}
		} catch (e) { }
		dialog.showAtElement();
	});
};

/*
 * Set the color and close the ForeColor and the HiliteColor select color dialogs
 */
TYPO3Color.setColorCO = function(instance) {
	return (function(dialog,params) {
		var editor = dialog.editor;
		instance.processStyle(dialog, params, "", "");
		dialog.releaseEvents();
		editor.focusEditor();
		editor.updateToolbar();
		dialog.close();
	});
};

/*
 * Initialize the case=color select color dialog
 * This case is used by the Table Operations plugin
 */
TYPO3Color.selectColorColorInit = function(instance,buttonId,field) {
	return (function(dialog) {
		dialog.content.innerHTML = instance.renderPopupSelectColor(buttonId, dialog, instance.localize(buttonId + "_title"));
		var colorTable = dialog.doc.getElementById("colorTable");
		colorTable.onclick = function(e) {
			if(!e) var e = dialog.dialogWindow.event;
			var targ = e.target ? e.target : e.srcElement;
			if (targ.nodeType == 3) targ = targ.parentNode;
			dialog.doc.getElementById(buttonId).value = targ.bgColor;
			dialog.callHandler();
			return false;
		};
		var colorUnset = dialog.doc.getElementById("colorUnset");
		colorUnset.onclick = function(e) {
			dialog.doc.getElementById(buttonId).value = "";
			dialog.callHandler();
			return false;
		};
		dialog.doc.getElementById(buttonId+"Current").style.backgroundColor = field.value;
		dialog.showAtElement();

	});
};

/*
 * Set the color and close the case=color select color dialog
 */
TYPO3Color.setColorColor = function(instance,element,field) {
	return (function(dialog,params) {
		instance.processStyle(dialog, params, element, field);
		dialog.releaseEvents();
		dialog.close();
	});
};

/*
 * Initialize the case=tag select color dialog
 * This is used by the QuickTag plugin
 */
TYPO3Color.selectColorTagInit = function(instance, buttonId) {
	return (function(dialog) {
		instance.dialog = dialog;
		dialog.content.innerHTML = instance.renderPopupSelectColor(buttonId, dialog, instance.localize("color_title"));
		var colorTable = dialog.doc.getElementById("colorTable");
		colorTable.onclick = function(e) {
			if(!e) var e = dialog.dialogWindow.event;
			var targ = e.target ? e.target : e.srcElement;
			if (targ.nodeType == 3) targ = targ.parentNode;
			dialog.doc.getElementById(buttonId).value = targ.bgColor;
			dialog.callHandler();
			return false;
		};
		var colorUnset = dialog.doc.getElementById("colorUnset");
		colorUnset.onclick = function(e) {
			dialog.doc.getElementById(buttonId).value = "";
			dialog.callHandler();
			return false;
		};
		dialog.doc.getElementById(buttonId+"Current").style.backgroundColor = "";
		dialog.showAtElement();
	});
};

/*
 * Set the color and close the case=color select color dialog
 */
TYPO3Color.setColorTag = function(instance,field) {
	return (function(dialog,params) {
		dialog.releaseEvents();
		field.insertColor(params["tag"]);
		dialog.close();
	});
};

