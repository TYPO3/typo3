/*
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
 * Module: @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/HtmlArea
 * Initialization script of TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Configuration/Config',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/Editor'],
	function (UserAgent, Util, Config, Editor) {

		/**
		 *
		 * @type {{RE_htmlTag: RegExp, RE_tagName: RegExp, RE_head: RegExp, RE_body: RegExp, reservedClassNames: RegExp, RE_email: RegExp, RE_url: RegExp, RE_numberOrPunctuation: RegExp, init: Function, initEditor: Function, localize: Function, appendToLog: Function}}
		 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/HtmlArea
		 */
		var HtmlArea = {

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
			 * INITIALIZATION                                  *
			 ***************************************************/
			init: function () {
				if (!HTMLArea.isReady) {
					// Apply global configuration settings
					Util.apply(HtmlArea, RTEarea[0]);
					HTMLArea.isReady = true;
					HtmlArea.appendToLog('', 'HTMLArea', 'init', 'Editor url set to: ' + HtmlArea.editorUrl, 'info');
					HtmlArea.appendToLog('', 'HTMLArea', 'init', 'Editor content skin CSS set to: ' + HtmlArea.editedContentCSS, 'info');

					Util.apply(HTMLArea, HtmlArea);
				}
			},

			/**
			 * Create an editor when HTMLArea is loaded and when Ext is ready
			 *
			 * @param	string		editorId: the id of the editor
			 * @return 	boolean		false if successful
			 */
			initEditor: function (editorId) {
				if (document.getElementById('pleasewait' + editorId)) {
					if (UserAgent.isSupported()) {
						document.getElementById('pleasewait' + editorId).style.display = 'block';
						document.getElementById('editorWrap' + editorId).style.visibility = 'hidden';
						if (!HTMLArea.isReady) {
							var self = this;
							window.setTimeout(function () {
								return self.initEditor(editorId);
							}, 150);
						} else {
							// Create an editor for the textarea
							var editor = new Editor(Util.apply(new Config(editorId), RTEarea[editorId]));
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
			 * LOGGING                                         *
			 ***************************************************/
			/**
			 * Write message to JavaScript console
			 *
			 * @param	string		editorId: the id of the editor issuing the message
			 * @param	string		objectName: the name of the object issuing the message
			 * @param	string		functionName: the name of the function issuing the message
			 * @param	string		text: the text of the message
			 * @param	string		type: the type of message: 'log', 'info', 'warn' or 'error'
			 * @return	void
			 */
			appendToLog: function (editorId, objectName, functionName, text, type) {
				var str = 'RTE[' + editorId + '][' + objectName + '::' + functionName + ']: ' + text;
				if (typeof type === 'undefined') {
					var type = 'info';
				}
				// IE may not have any console
				if (typeof console === 'object' && console !== null && typeof console[type] !== 'undefined') {
					console[type](str);
				}
			}
		};

		return Util.apply(HTMLArea, HtmlArea);
});
