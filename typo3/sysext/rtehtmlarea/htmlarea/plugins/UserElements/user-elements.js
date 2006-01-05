/*
 * User Elements Plugin for TYPO3 htmlArea RTE
 *
 * @author	Stanislas Rolland, sponsored by Fructifor Inc.
 * (c) 2005-2006, Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 * Distributed under the terms of GPL.
 * This notice MUST stay intact for use.
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
	version		: "1.2",
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
