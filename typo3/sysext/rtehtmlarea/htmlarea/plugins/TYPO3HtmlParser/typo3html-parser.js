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
/**
 * TYPO3HtmlParser Plugin for TYPO3 htmlArea RTE
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
	version		: "1.6",
	developer	: "Stanislas Rolland",
	developer_url	: "http://www.fructifor.ca/",
	c_owner		: "Stanislas Rolland",
	sponsor		: "Fructifor Inc.",
	sponsor_url	: "http://www.fructifor.ca/",
	license		: "GPL"
};

HTMLArea._wordClean = function(editor, body) {
	var editorNo = editor._editorNumber;
	var url = RTEarea[0]["pathParseHtmlModule"];
	var addParams = RTEarea[editorNo]["RTEtsConfigParams"];
	HTMLArea._postback(url, {'editorNo' : editorNo, 'content' : body.innerHTML },
		function(javascriptResponse) { editor.setHTML(javascriptResponse) }, addParams, RTEarea[editor._editorNumber]["typo3ContentCharset"]);
	return true;
};

