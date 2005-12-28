/*
 * Character Map Plugin for TYPO3 htmlArea RTE
 *
 * @author	Bernhard Pfeifer novocaine@gmx.net
 * @author	Holger Hees based on HTMLArea XTD 1.5 (http://mosforge.net/projects/htmlarea3xtd/). Sponsored by http://www.systemconcept.de
 * @author	Stanislas Rolland. Sponsored by Fructifor Inc.
 * Copyright (c) 2004 systemconcept.de
 * Copyright (c) 2005 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 * Distributed under the same terms as HTMLArea itself.
 * This notice MUST stay intact for use.
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
	version		: "1.1",
	developer	: "Holger Hees & Bernhard Pfeifer",
	developer_url	: "http://www.systemconcept.de/",
	c_owner		: "Holger Hees & Bernhard Pfeifer",
	sponsor		: "System Concept GmbH & Bernhard Pfeifer",
	sponsor_url	: "http://www.systemconcept.de/",
	license		: "htmlArea"
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