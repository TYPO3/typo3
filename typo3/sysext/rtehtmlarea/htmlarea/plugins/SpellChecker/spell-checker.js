// Spell Checker Plugin for HTMLArea-3.0
// Sponsored by www.americanbible.org
// Implementation by Mihai Bazon, http://dynarch.com/mishoo/
// (c) dynarch.com 2003.
// (c) 2004-2005, Stanislas Rolland <stanislas.rolland@fructifor.ca>
// Modified to use the standard dialog API
// Distributed under the same terms as HTMLArea itself.
// This notice MUST stay intact for use (see license.txt).
//

SpellChecker = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
	var actionHandlerFunctRef = SpellChecker.actionHandler(this);

	cfg.registerButton("SpellCheck", 
		SpellChecker_langArray["SC-spell-check"],
		editor.imgURL("spell-check.gif", "SpellChecker"),
		false,
		actionHandlerFunctRef
	);
};

SpellChecker.I18N = SpellChecker_langArray;

SpellChecker._pluginInfo = {
	name 		: "SpellChecker",
	version 	: "2.0",
	developer 	: "Mihai Bazon & Stanislas Rolland",
	developer_url 	: "http://dynarch.com/mishoo/",
	c_owner 	: "Mihai Bazon & Stanislas Rolland",
	sponsor 	: "American Bible Society & Fructifor Inc.",
	sponsor_url 	: "http://www.fructifor.ca/",
	license 	: "htmlArea"
};

SpellChecker.actionHandler = function(instance) {
	return (function(editor,id) {
		instance.buttonPress(editor, id);
	});
};

SpellChecker.prototype.buttonPress = function(editor, id) {
	var editorNumber = editor._editorNumber;
	switch (id) {
	    case "SpellCheck":
		SpellChecker.editor = editor;
		SpellChecker.init = true;
		SpellChecker.f_dictionary = _spellChecker_lang;
		SpellChecker.f_charset = _spellChecker_charset;
		SpellChecker.f_pspell_mode = _spellChecker_mode;
		SpellChecker.enablePersonalDicts = RTEarea[editorNumber]["enablePersonalDicts"];
		SpellChecker.userUid = RTEarea[editorNumber]["userUid"];
		var param = new Object();
		param.editor = editor;
		param.HTMLArea = HTMLArea;
		if (SpellChecker.f_charset.toLowerCase() == 'iso-8859-1') editor._popupDialog("plugin://SpellChecker/spell-check-ui-iso-8859-1", null, param, 670, 500);
    			else editor._popupDialog("plugin://SpellChecker/spell-check-ui", null, param, 670, 500);
		break;
	}
};

// this needs to be global, it's accessed from spell-check-ui.html
SpellChecker.editor = null;
