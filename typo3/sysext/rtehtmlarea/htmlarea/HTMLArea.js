/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Main script of TYPO3 htmlArea RTE
 */
Ext.namespace('HTMLArea.CSS', 'HTMLArea.util.TYPO3', 'HTMLArea.util.Tips', 'HTMLArea.util.Color', 'Ext.ux.form', 'Ext.ux.menu', 'Ext.ux.Toolbar');
Ext.apply(HTMLArea, {
	/***************************************************
	 * COMPILED REGULAR EXPRESSIONS                    *
	 ***************************************************/
	RE_htmlTag		: /<.[^<>]*?>/g,
	RE_tagName		: /(<\/|<)\s*([^ \t\n>]+)/ig,
	RE_head			: /<head>((.|\n)*?)<\/head>/i,
	RE_body			: /<body>((.|\n)*?)<\/body>/i,
	reservedClassNames	: /htmlarea/,
	RE_email		: /([0-9a-z]+([a-z0-9_-]*[0-9a-z])*){1}(\.[0-9a-z]+([a-z0-9_-]*[0-9a-z])*)*@([0-9a-z]+([a-z0-9_-]*[0-9a-z])*\.)+[a-z]{2,9}/i,
	RE_url			: /(([^:/?#]+):\/\/)?(([a-z0-9_]+:[a-z0-9_]+@)?[a-z0-9_-]{2,}(\.[a-z0-9_-]{2,})+\.[a-z]{2,5}(:[0-9]+)?(\/\S+)*\/?)/i,
	RE_numberOrPunctuation	: /[0-9.(),;:!¡?¿%#$'"_+=\\\/-]*/g,
	/***************************************************
	 * BROWSER IDENTIFICATION                          *
	 ***************************************************/
	isIEBeforeIE9: Ext.isIE6 || Ext.isIE7 || Ext.isIE8 || (Ext.isIE && typeof(document.documentMode) !== 'undefined' && document.documentMode < 9),
	/***************************************************
	 * LOCALIZATION                                    *
	 ***************************************************/
	localize: function (label, plural) {
		var i = plural || 0;
		var localized = HTMLArea.I18N.dialogs[label] || HTMLArea.I18N.tooltips[label] || HTMLArea.I18N.msg[label] || '';
		if (typeof localized === 'object' && localized !== null && typeof localized[i] !== 'undefined') {
			localized = localized[i]['target'];
		}
		return localized;
	},
	/***************************************************
	 * INITIALIZATION                                  *
	 ***************************************************/
	init: function () {
		if (!HTMLArea.isReady) {
				// Apply global configuration settings
			Ext.apply(HTMLArea, RTEarea[0]);
			Ext.applyIf(HTMLArea, {
				editorSkin	: HTMLArea.editorUrl + 'skins/default/',
				editorCSS	: HTMLArea.editorUrl + 'skins/default/htmlarea.css'
			});
			if (typeof HTMLArea.editedContentCSS !== 'string' || HTMLArea.editedContentCSS === '') {
				HTMLArea.editedContentCSS = HTMLArea.editorSkin + 'htmlarea-edited-content.css';
			}
			HTMLArea.isReady = true;
			HTMLArea.appendToLog('', 'HTMLArea', 'init', 'Editor url set to: ' + HTMLArea.editorUrl, 'info');
			HTMLArea.appendToLog('', 'HTMLArea', 'init', 'Editor skin CSS set to: ' + HTMLArea.editorCSS, 'info');
			HTMLArea.appendToLog('', 'HTMLArea', 'init', 'Editor content skin CSS set to: ' + HTMLArea.editedContentCSS, 'info');
		}
	},
	/*
	 * Create an editor when HTMLArea is loaded and when Ext is ready
	 *
	 * @param	string		editorId: the id of the editor
	 *
	 * @return 	boolean		false if successful
	 */
	initEditor: function (editorId) {
		if (document.getElementById('pleasewait' + editorId)) {
			if (HTMLArea.checkSupportedBrowser()) {
				document.getElementById('pleasewait' + editorId).style.display = 'block';
				document.getElementById('editorWrap' + editorId).style.visibility = 'hidden';
				if (!HTMLArea.isReady) {
					HTMLArea.initEditor.defer(150, null, [editorId]);
				} else {
						// Create an editor for the textarea
					var editor = new HTMLArea.Editor(Ext.apply(new HTMLArea.Config(editorId), RTEarea[editorId]));
					editor.generate();
					return false;
				}
			} else {
				document.getElementById('pleasewait' + editorId).style.display = 'none';
				document.getElementById('editorWrap' + editorId).style.visibility = 'visible';
			}
		}
		return true;
	},
	/*
	 * Check if the client agent is supported
	 *
	 * @return	boolean		true if the client is supported
	 */
	checkSupportedBrowser: function () {
		return Ext.isGecko || Ext.isWebKit || Ext.isOpera || Ext.isIE;
	},
	/*
	 * Write message to JavaScript console
	 *
	 * @param	string		editorId: the id of the editor issuing the message
	 * @param	string		objectName: the name of the object issuing the message
	 * @param	string		functionName: the name of the function issuing the message
	 * @param	string		text: the text of the message
	 * @param	string		type: the type of message: 'log', 'info', 'warn' or 'error'
	 *
	 * @return	void
	 */
	appendToLog: function (editorId, objectName, functionName, text, type) {
		var str = 'RTE[' + editorId + '][' + objectName + '::' + functionName + ']: ' + text;
		if (typeof type === 'undefined') {
			var type = 'info';
		}
		if (typeof console === 'object' && console !== null) {
			// If console is TYPO3.Backend.DebugConsole, write only error messages
			if (typeof console.addTab === 'function') {
				if (type === 'error') {
					console[type](str);
				}
			// IE may not have any console
			} else if (typeof console[type] !== 'undefined') {
				console[type](str);
			}
		}
	}
});
