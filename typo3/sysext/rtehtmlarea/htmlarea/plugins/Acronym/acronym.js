/***************************************************************
*  Copyright notice
*
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
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Acronym plugin for htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

Acronym = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
	var actionHandlerFunctRef = Acronym.actionHandler(this);
	cfg.registerButton("Acronym",
				Acronym_langArray["Insert/Modify Acronym"], 
				editor.imgURL("ed_acronym.gif", "Acronym"), 
				false,
				actionHandlerFunctRef
	);
};
Acronym.I18N = Acronym_langArray;

Acronym._pluginInfo = {
	name		: "Acronym",
	version		: "1.4",
	developer	: "Stanislas Rolland",
	developer_url	: "http://www.fructifor.ca/",
	c_owner		: "Stanislas Rolland",
	sponsor		: "Fructifor Inc.",
	sponsor_url	: "http://www.fructifor.ca/",
	license		: "GPL"
};

Acronym.actionHandler = function(instance) {
	return (function(editor) {
		instance.buttonPress(editor);
	});
};

Acronym.prototype.buttonPress = function(editor) {
	var editorNo = editor._doc._editorNo;
	var backreturn;
	var addUrlParams = "?" + RTEarea[editorNo]["RTEtsConfigParams"];
	editor._popupDialog(RTEarea[0]["pathAcronymModule"] + addUrlParams + "&editorNo=" + editorNo, null, null, 570, 280);
	return false;
};
