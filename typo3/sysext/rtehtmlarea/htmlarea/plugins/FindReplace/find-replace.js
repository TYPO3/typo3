/*
 * Find and Replace Plugin for TYPO3 htmlArea RTE
 *
 * @author	Cau guanabara
 * @author	Stanislas Rolland. Sponsored by Fructifor Inc.
 * Copyright (c) 2004 Cau guanabara <caugb@ibest.com.br>
 * Copyright (c) 2005 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 * Distributed under the same terms as HTMLArea itself.
 * This notice MUST stay intact for use.
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
	editor._popupDialog("plugin://FindReplace/find_replace", null, param, 420, 220);
};

FindReplace._pluginInfo = {
  name          : "FindReplace",
  version       : "1.0",
  developer     : "Cau Guanabara",
  developer_url : "mailto:caugb@ibest.com.br",
  c_owner       : "Cau Guanabara",
  sponsor       : "Independent production",
  sponsor_url   : "http://www.netflash.com.br/gb/HA3-rc1/examples/find-replace.html",
  license       : "htmlArea"
};
