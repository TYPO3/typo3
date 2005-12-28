/*
 * TYPO3HtmlParser Plugin for TYPO3 htmlArea RTE
 *
 * @author	Stanislas Rolland. Sponsored by Fructifor Inc.
 * Copyright (c) 2005 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 * This notice MUST stay intact for use.
 *
 * TYPO3 CVS ID: $Id$
 */

TYPO3HtmlParser = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
};

TYPO3HtmlParser.I18N = TYPO3HtmlParser_langArray;

TYPO3HtmlParser._pluginInfo = {
	name		: "TYPO3HtmlParser",
	version		: "1.0",
	developer	: "Stanislas Rolland",
	developer_url	: "http://www.fructifor.ca",
	c_owner		: "Stanislas Rolland",
	sponsor		: "Fructifor Inc.",
	sponsor_url	: "http://www.fructifor.ca",
	license		: "GPL"
};

HTMLArea._wordClean = function(editor, body) {
	var url = '../../../..' + rtePathParseHtmlFile;
	var addParams = conf_RTEtsConfigParams;
	HTMLArea._postback(url, {'editorNo' : editor._editorNumber, 'content' : body.innerHTML },
		function(javascriptResponse) { eval(javascriptResponse) }, addParams);
	return true;
};

