// User Elements Plugin for TYPO3 htmlArea RTE
// Copyright (c) 2005 Stanislas Rolland
// Sponsored by http://www.fructifor.com
//
// htmlArea v3.0 - Copyright (c) 2002 interactivetools.com, inc.
// This notice MUST stay intact for use (see license.txt).

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
	name			: "UserElements",
	version		: "1.1",
	developer		: "Stanislas Rolland",
	developer_url	: "http://www.fructifor.com/",
	c_owner		: "Stanislas Rolland",
	sponsor		: "Fructifor Inc.",
	sponsor_url		: "http://www.fructifor.com",
	license		: "htmlArea"
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
	editor._popupDialog("../../t3_popup.php" + addUrlParams + "&editorNo=" + editorNo + "&popupname=user&srcpath=" + encodeURI(rtePathUserFile), null, backreturn, 550, 350);
	return false;
};
