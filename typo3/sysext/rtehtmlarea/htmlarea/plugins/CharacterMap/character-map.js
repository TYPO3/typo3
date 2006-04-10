/***************************************************************
*  Copyright notice
*
*  (c) 2004 Bernhard Pfeifer novocaine@gmx.net
*  (c) 2004 systemconcept.de. Authored by Holger Hees based on HTMLArea XTD 1.5 (http://mosforge.net/projects/htmlarea3xtd/).
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
 * Character Map Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

CharacterMap = function(editor) {
	this.editor = editor;
	var cfg = this.editor.config;
	var actionHandlerFunctRef = CharacterMap.actionHandler(this);
	cfg.registerButton({
		id		: "InsertCharacter",
		tooltip		: CharacterMap_langArray["CharacterMapTooltip"],
		image		: editor.imgURL("ed_charmap.gif", "CharacterMap"),
		textMode	: false,
		action		: actionHandlerFunctRef
	});
};

CharacterMap.I18N = CharacterMap_langArray;

CharacterMap._pluginInfo = {
	name		: "CharacterMap",
	version		: "1.2",
	developer	: "Holger Hees, Bernhard Pfeifer, Stanislas Rolland",
	developer_url	: "http://www.fructifor.ca/",
	c_owner		: "Holger Hees, Bernhard Pfeifer, Stanislas Rolland",
	sponsor		: "System Concept GmbH, Bernhard Pfeifer, Fructifor Inc.",
	sponsor_url	: "http://www.fructifor.ca/",
	license		: "GPL"
};

CharacterMap.actionHandler = function(instance) {
	return (function(editor) {
		instance.buttonPress(editor);
	});
};

CharacterMap.prototype.buttonPress = function(editor) {
	var self = this;
	var param = new Object();
	param.editor = editor;
	var insertCharHandlerFunctRef = CharacterMap.insertCharHandler(this);
	editor._popupDialog( "plugin://CharacterMap/select_character", insertCharHandlerFunctRef, param, 485, 330);
};

CharacterMap.insertCharHandler = function(instance) {
	return (function(entity) {
		if (typeof(entity) != "undefined") {
			instance.editor.focusEditor();
			instance.editor.insertHTML(entity);
		}
	});
};