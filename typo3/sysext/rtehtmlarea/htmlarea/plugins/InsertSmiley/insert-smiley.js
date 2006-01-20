/***************************************************************
*  Copyright notice
*
*  (c) 2004  Ki Master George <kimastergeorge@gmail.com>
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
 * Insert Smiley Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

var HTMLAreaeditor;

InsertSmiley = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
	var actionHandlerFunctRef = InsertSmiley.actionHandler(this);
	cfg.registerButton("InsertSmiley", InsertSmiley_langArray["Insert Smiley"],  editor.imgURL("ed_smiley.gif", "InsertSmiley"), false, actionHandlerFunctRef);
};

InsertSmiley.I18N = InsertSmiley_langArray;

InsertSmiley.actionHandler = function(instance) {
	return (function(editor) {
		instance.buttonPress(editor);
	});
};

InsertSmiley.prototype.buttonPress = function(editor) { 
	var sel = editor.getSelectedHTML().replace(/(<[^>]*>|&nbsp;|\n|\r)/g,""); 
	var param = new Object();
	param.editor = editor;
	param.editor_url = _typo3_host_url + _editor_url;
	if(param.editor_url == "../") {
		param.editor_url = document.URL;
		param.editor_url = param.editor_url.replace(/^(.*\/).*\/.*$/g, "$1");
	}
	var setTagHandlerFunctRef = InsertSmiley.setTagHandler(this);
  	editor._popupDialog("plugin://InsertSmiley/insertsmiley", setTagHandlerFunctRef, param, 250, 220);
};

InsertSmiley.setTagHandler = function(instance) {
	return (function(param) {
		if(param && typeof(param.imgURL) != "undefined") {
			instance.editor.focusEditor();
			instance.editor.insertHTML("<img src=\"" + param.imgURL + "\" alt=\"Smiley\" />");
		}
	});
};

InsertSmiley._pluginInfo = {
	name          : "InsertSmiley",
	version       : "1.1",
	developer     : "Ki Master George & Stanislas Rolland",
	developer_url : "http://www.fructifor.ca/",
	c_owner       : "Ki Master George & Stanislas Rolland",
	sponsor       : "Ki Master George & Fructifor Inc.",
	sponsor_url   : "http://www.fructifor.ca/",
	license       : "GPL"
};
