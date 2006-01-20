/***************************************************************
*  Copyright notice
*
*  (c) 2004 Cau guanabara <caugb@ibest.com.br>
*  (c) 2005, 2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Quick Tag Editor Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

QuickTag = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
	var actionHandlerFunctRef = QuickTag.actionHandler(this);
	cfg.registerButton({
		id		: "InsertTag",
		tooltip	: QuickTag_langArray["Quick Tag Editor"],
		image		: editor.imgURL("ed_quicktag.gif", "QuickTag"),
		textMode	: false,
  		action	: actionHandlerFunctRef,
		context	: null,
		hide		: false,
		selection	: true
		});
};

QuickTag.I18N = QuickTag_langArray;

QuickTag.actionHandler = function(instance) {
	return (function(editor) {
		instance.buttonPress(editor);
	});
};

QuickTag.prototype.buttonPress = function(editor) {
	var sel = editor.getSelectedHTML().replace(/(<[^>]*>|&nbsp;|\n|\r)/g,""); 
	var param = new Object();
	param.editor = editor;

  	if(/\w/.test(sel)) {
		var setTagHandlerFunctRef = QuickTag.setTagHandler(this);
    		editor._popupDialog("plugin://QuickTag/quicktag", setTagHandlerFunctRef, param, 450, 108);
  	} else {
		alert(QuickTag.I18N['You have to select some text']);
	}
};

QuickTag.setTagHandler = function(instance) {
	return (function(param) {
		if(param && typeof(param.tagopen) != "undefined") {
			instance.editor.focusEditor();
			instance.editor.surroundHTML(param.tagopen,param.tagclose);
		}
	});
};

QuickTag._pluginInfo = {
	name          : "QuickTag",
	version       : "1.1",
	developer     : "Cau Guanabara & Stanislas Rolland",
	developer_url : "mailto:caugb@ibest.com.br",
	c_owner       : "Cau Guanabara & Stanislas Rolland",
	sponsor       : "Independent production & Fructifor Inc.",
	sponsor_url   : "http://www.netflash.com.br/gb/HA3-rc1/examples/quick-tag.html",
	license       : "GPL"
};
