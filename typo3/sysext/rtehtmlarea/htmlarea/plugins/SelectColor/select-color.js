/***************************************************************
*  Copyright notice
*
*  (c) 2004, 2005, 2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Color Select Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

SelectColor = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
	var bl = SelectColor.btnList;

		// register the toolbar buttons provided by this plugin
	for (var i = 0; i < bl.length; ++i) {
		var btn = bl[i];
		var id = "CO-" + btn[0];
		this.editor.eventHandlers[id] = SelectColor.actionHandler(this);
		cfg.registerButton(
			id, 
			SelectColor_langArray[id],
			editor.imgURL(id + ".gif", "SelectColor"),
			false,
			this.editor.eventHandlers[id],
		 	btn[1]
		);
	}
};

SelectColor.I18N = SelectColor_langArray;

SelectColor._pluginInfo = {
	name          : "SelectColor",
	version       : "1.6",
	developer     : "Stanislas Rolland",
	developer_url : "http://www.fructifor.ca/",
	c_owner       : "Stanislas Rolland",
	sponsor       : "Fructifor Inc.",
	sponsor_url   : "http://www.fructifor.ca/",
	license       : "GPL"
};

SelectColor.actionHandler = function(instance) {
	return (function(editor,id) {
		instance.buttonPress(editor, id);
	});
};

// the list of buttons added by this plugin
SelectColor.btnList = [
	["forecolor", null],
	["hilitecolor", null]
];

// This function gets called when some button from the SelectColor was pressed
SelectColor.prototype.buttonPress = function(editor,button_id) {
	this.editor = editor;
	switch (button_id) {
		case "CO-forecolor":
			this.dialogSelectColor(button_id,"","");
			break;
		case "CO-hilitecolor":
			this.dialogSelectColor(button_id,"","");
			break;
		default:
			alert("Button [" + button_id + "] not yet implemented");
	}
};

// this function requires the file PopupWin
SelectColor.prototype.dialogSelectColor = function(button_id,element,field,opener) {
	var editor = this.editor;
	var windowWidth = 470;
	var windowHeight = 245;

		// button_id's  "color" and "tag" are not registered but used to interface with the Table Operations and QuickTag plugins
	switch (button_id) {
	   case "CO-forecolor":
	   case "CO-hilitecolor":
		var selectColorInitFunctRef = SelectColor.selectColorCOInit(this, button_id);
		var setColorFunctRef = SelectColor.setColorCO(this);
		var dialog = new PopupWin(this.editor, SelectColor.I18N[button_id + "_title"], setColorFunctRef, selectColorInitFunctRef, windowWidth, windowHeight, editor._iframe.contentWindow);
		break;
	   case "color":
		var selectColorInitFunctRef = SelectColor.selectColorColorInit(this, button_id, field);
		var setColorFunctRef = SelectColor.setColorColor(this, element, field);
		var dialog = new PopupWin(this.editor, SelectColor.I18N[button_id + "_title"], setColorFunctRef, selectColorInitFunctRef, windowWidth, windowHeight, opener);
		break;
	   case "tag":
		var selectColorInitFunctRef = SelectColor.selectColorTagInit(this, button_id);
		var setColorFunctRef = SelectColor.setColorTag(this, field);
		var dialog = new PopupWin(this.editor, SelectColor.I18N["color_title"], setColorFunctRef, selectColorInitFunctRef, windowWidth, windowHeight, opener);
	}
};

/*
 * Initialize the CO-forecolor and the CO-hilitecolor select color dialogs
 */
SelectColor.selectColorCOInit = function(instance,button_id) {
	return (function(dialog) {
		var editor = dialog.editor;
		var doc = editor._doc;
		dialog.content.innerHTML = instance.renderPopupSelectColor(button_id, dialog, SelectColor.I18N[button_id + "_title"]);
		var colorTable = dialog.doc.getElementById("colorTable");
		colorTable.onclick = function(e) {
			if(!e) var e = dialog.dialogWindow.event;
			var targ = e.target ? e.target : e.srcElement;
			if (targ.nodeType == 3) targ = targ.parentNode;
			dialog.doc.getElementById(button_id).value = targ.bgColor ? targ.bgColor : "";
			dialog.callHandler();
			return false;
		};
		var colorUnset = dialog.doc.getElementById("colorUnset");
		colorUnset.onclick = function(e) {
			dialog.doc.getElementById(button_id).value="";
			dialog.callHandler();
			return false;
		};
		try {
			with (dialog.doc.getElementById(button_id+"Current").style) {
				switch (button_id) {
					case "CO-forecolor":
						backgroundColor = HTMLArea._makeColor(doc.queryCommandValue("ForeColor"));
						break;
					case "CO-hilitecolor":
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
 * Set the color and close the CO-forecolor and the CO-hilitecolor select color dialogs
 */
SelectColor.setColorCO = function(instance) {
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
SelectColor.selectColorColorInit = function(instance,button_id,field) {
	return (function(dialog) {
		dialog.content.innerHTML = instance.renderPopupSelectColor(button_id, dialog, SelectColor.I18N[button_id + "_title"]);
		var colorTable = dialog.doc.getElementById("colorTable");
		colorTable.onclick = function(e) {
			if(!e) var e = dialog.dialogWindow.event;
			var targ = e.target ? e.target : e.srcElement;
			if (targ.nodeType == 3) targ = targ.parentNode;
			dialog.doc.getElementById(button_id).value = targ.bgColor;
			dialog.callHandler();
			return false;
		};
		var colorUnset = dialog.doc.getElementById("colorUnset");
		colorUnset.onclick = function(e) {
			dialog.doc.getElementById(button_id).value = "";
			dialog.callHandler();
			return false;
		};
		dialog.doc.getElementById(button_id+"Current").style.backgroundColor = field.value;
		dialog.showAtElement();

	});
};

/*
 * Set the color and close the case=color select color dialog
 */
SelectColor.setColorColor = function(instance,element,field) {
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
SelectColor.selectColorTagInit = function(instance, button_id) {
	return (function(dialog) {
		instance.dialog = dialog;
		dialog.content.innerHTML = instance.renderPopupSelectColor(button_id, dialog, SelectColor.I18N["color_title"]);
		var colorTable = dialog.doc.getElementById("colorTable");
		colorTable.onclick = function(e) {
			if(!e) var e = dialog.dialogWindow.event;
			var targ = e.target ? e.target : e.srcElement;
			if (targ.nodeType == 3) targ = targ.parentNode;
			dialog.doc.getElementById(button_id).value = targ.bgColor;
			dialog.callHandler();
			return false;
		};
		var colorUnset = dialog.doc.getElementById("colorUnset");
		colorUnset.onclick = function(e) {
			dialog.doc.getElementById(button_id).value = "";
			dialog.callHandler();
			return false;
		};
		dialog.doc.getElementById(button_id+"Current").style.backgroundColor = "";
		dialog.showAtElement();
	});
};

/*
 * Set the color and close the case=color select color dialog
 */
SelectColor.setColorTag = function(instance,field) {
	return (function(dialog,params) {
		dialog.releaseEvents();
		field._return(params["tag"]);
		dialog.close();
		field.dialog = null;
	});
};

// Applies the style found in "params" to the given element.
SelectColor.prototype.processStyle = function(dialog, params, element, field) {
	var editor = this.editor;
	for (var i in params) {
		var val = params[i];
		switch (i) {
		    	case "CO-forecolor":
				if(val) {
					editor._doc.execCommand("ForeColor", false, val);
				} else {
					var parentElement = editor.getParentElement();
					parentElement.style.color = "";
				}
				break;
			case "CO-hilitecolor":
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
};

/**
 * Making color selector table
 */
SelectColor.prototype.renderPopupSelectColor = function(sID,dialog,title) {
	var editor = this.editor;
	var cfg = editor.config;
	var cfgColors = cfg.colors;
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
	sz += '	<span id="colorUnset" class="nocolor" title="' + SelectColor.I18N["no_color"] + '" ';
	sz += '		onMouseover="className += \' nocolor-hilite\';" ';
	sz += '		onMouseout="className = \'nocolor\';"';
	sz += '	>&#x00d7;</span></span></td><td>';
	sz += '<table ';
	sz += '	onMouseout="document.getElementById(\'' + szID + '\').style.backgroundColor=\'\';" ';
	sz += '	onMouseover="if(' + HTMLArea.is_ie + '){ if(event.srcElement.bgColor) document.getElementById(\'' + szID + '\').style.backgroundColor=event.srcElement.bgColor; } else { if (event.target.bgColor) document.getElementById(\'' + szID + '\').style.backgroundColor=event.target.bgColor; }" ';
	sz += '	class="colorTable" cellspacing="0" cellpadding="0" id="colorTable">';
		// Making colorPicker
	if (!cfg.disableColorPicker) {
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
		if (iCfgColors && !cfg.disableColorPicker) {
			sz += '<tr><td colspan="36"></td></tr>';
		}
		for ( var theColor = 0; theColor < iCfgColors; theColor++) {
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
};
