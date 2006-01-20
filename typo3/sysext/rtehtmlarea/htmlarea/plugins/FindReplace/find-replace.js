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
 * Find and Replace Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

FindReplace = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
	var actionHandlerFunctRef = FindReplace.actionHandler(this);

	cfg.registerButton("FindReplace",
		FindReplace_langArray["Find and Replace"],
		editor.imgURL("ed_find.gif", "FindReplace"),
		false,
		actionHandlerFunctRef
	);
	
	this.popupWidth = 455;
	this.popupHeight = 235;
};

FindReplace.I18N = FindReplace_langArray;

FindReplace.actionHandler = function(instance) {
	return (function(editor) {
		instance.buttonPress(editor);
	});
};

FindReplace.prototype.buttonPress = function(editor) { 
	FindReplace.editor = editor;
	var sel = editor.getSelectedHTML(), param = null;
	if (/\w/.test(sel)) {
		sel = sel.replace(/<[^>]*>/g,"");
		sel = sel.replace(/&nbsp;/g,"");
	}
	if (/\w/.test(sel)) param = { fr_pattern: sel };
	editor._popupDialog("plugin://FindReplace/find_replace", null, param, this.popupWidth, this.popupHeight);
};

FindReplace._pluginInfo = {
  name          : "FindReplace",
  version       : "1.1",
  developer     : "Cau Guanabara & Stanislas Rolland",
  developer_url : "mailto:caugb@ibest.com.br",
  c_owner       : "Cau Guanabara & Stanislas Rolland",
  sponsor       : "Independent production & Fructifor Inc.",
  sponsor_url   : "http://www.netflash.com.br/gb/HA3-rc1/examples/find-replace.html",
  license       : "GPL"
};
