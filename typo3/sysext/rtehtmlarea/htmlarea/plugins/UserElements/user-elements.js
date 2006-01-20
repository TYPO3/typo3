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
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * User Elements Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

UserElements = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
	var self = this;
	var actionHandlerFunctRef = UserElements.actionHandler(this);
	cfg.registerButton("UserElements",
		UserElements_langArray["Insert custom element"], 
		editor.imgURL("ed_user.gif", "UserElements"), 
		false,
		actionHandlerFunctRef
	);
};

UserElements.I18N = UserElements_langArray;

UserElements._pluginInfo = {
	name		: "UserElements",
	version		: "1.3",
	developer	: "Stanislas Rolland",
	developer_url	: "http://www.fructifor.ca/",
	c_owner		: "Stanislas Rolland",
	sponsor		: "Fructifor Inc.",
	sponsor_url	: "http://www.fructifor.ca/",
	license		: "GPL"
};

UserElements.actionHandler = function(instance) {
	return (function(editor) {
		instance.buttonPress(editor);
	});
};

UserElements.prototype.buttonPress = function(editor) {
	var editorNo = editor._doc._editorNo;
	var backreturn;
	var addUrlParams = "?" + conf_RTEtsConfigParams;
	editor._popupDialog("../../mod1/popup.php" + addUrlParams + "&editorNo=" + editorNo + "&popupname=user&srcpath=" + encodeURI(rtePathUserFile), null, backreturn, 550, 350);
	return false;
};
