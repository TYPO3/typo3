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
 * Remove Format Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Notification',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, Util, $, Modal, Notification, Severity) {

	var RemoveFormat = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(RemoveFormat, Plugin);
	Util.apply(RemoveFormat.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.4',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL',
				hasHelp		: true
			};
			this.registerPluginInformation(pluginInformation);

			/**
			 * Registering the button
			 */
			var buttonId = 'RemoveFormat';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + 'Tooltip'),
				iconCls		: 'htmlarea-action-remove-format',
				action		: 'onButtonPress',
				dialog		: true
			};
			this.registerButton(buttonConfiguration);
			return true;
		},

		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 *
		 * @return {Boolean} False if action is completed
		 */
		onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			// Open dialogue window
			this.openDialogue(buttonId, 'Remove formatting');
			return false;
		},
		/**
		 * Open the dialogue window
		 *
		 * @param {String} buttonId The button id
		 * @param {String} title The window title
		 */
		openDialogue: function (buttonId, title) {
			this.dialog = Modal.show(this.localize(title), this.generateDialogContent(), Severity.notice, [
				this.buildButtonConfig('Cancel', $.proxy(this.onCancel, this), false),
				this.buildButtonConfig('OK', $.proxy(this.onOK, this), true, Severity.notice)
			]);
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},
		/**
		 * Generates the content for the dialog window
		 *
		 * @returns {Object}
		 */
		generateDialogContent: function() {
			var $cleaningArea = $('<fieldset />', {'class': 'form-section'}),
				$cleaningOptions = $('<fieldset />', {'class': 'form-section'});

			$cleaningArea.append(
				$('<h4 />', {'class': 'form-section-headline'}).html(this.getHelpTip('area', 'Cleaning Area')),
				$('<div />', {'class': 'form-group col-sm-12'}).append(
					$('<div />', {'class': 'radio'}).append(
						$('<label />').html(this.getHelpTip('selection', 'Selection')).prepend(
							$('<input />', {type: 'radio', name: 'htmlarea-removeFormat-area', value: 'selection'})
						)
					),
					$('<div />', {'class': 'radio'}).append(
						$('<label />').html(this.getHelpTip('all', 'All')).prepend(
							$('<input />', {type: 'radio', name: 'htmlarea-removeFormat-area', value: 'allContent'}).prop('checked', true)
						)
					)
				)
			);

			$cleaningOptions.append(
				$('<h4 />', {'class': 'form-section-headline'}).html(this.getHelpTip('options', 'Cleaning options')),
				$('<div />', {'class': 'form-group col-sm-12'}).append(
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').html(this.getHelpTip('htmlFormat', 'Formatting:')).prepend(
							$('<input />', {type: 'checkbox', name: 'formatting', value: 'formatting'})
						)
					),
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').html(this.getHelpTip('msWordFormat', 'MS Word Formatting:')).prepend(
							$('<input />', {type: 'checkbox', name: 'msWordFormatting', value: 'msWordFormatting'}).prop('checked', true)
						)
					),
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').html(this.getHelpTip('typographicalPunctuation', 'Typographical punctuation:')).prepend(
							$('<input />', {type: 'checkbox', name: 'typographical', value: 'typographical'})
						)
					),
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').html(this.getHelpTip('nonBreakingSpace', 'Spaces')).prepend(
							$('<input />', {type: 'checkbox', name: 'spaces', value: 'spaces'})
						)
					),
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').html(this.getHelpTip('images', 'Images:')).prepend(
							$('<input />', {type: 'checkbox', name: 'images', value: 'images'})
						)
					),
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').html(this.getHelpTip('allHtml', 'All HTML:')).prepend(
							$('<input />', {type: 'checkbox', name: 'allHtml', value: 'allHtml'})
						)
					)
				)
			);

			return $('<form />', {'class': 'form-horizontal'}).append($cleaningArea, $cleaningOptions);
		},
		/**
		 * Handler when the OK button is pressed
		 */
		onOK: function () {
			var fields = [
				'selection',
				'allContent',
				'formatting',
				'msWordFormatting',
				'typographical',
				'spaces',
				'images',
				'allHtml'
			], field;
			var params = {};
			for (var i = fields.length; --i >= 0;) {
				field = fields[i];
				params[field] = this.dialog.find('input[value="' + field + '"]').prop('checked');
			}
			if (params['allHtml'] || params['formatting'] || params['spaces'] || params['images'] || params['msWordFormatting'] || params['typographical']) {
				this.applyRequest(params);
				this.close();
			} else {
				Notification.warning(
					this.getButton('RemoveFormat').tooltip.title,
					this.localize('Select the type of formatting you wish to remove.'),
					10
				);
			}
			return false;
		},
		/**
		 * Perform the cleaning request
		 *
		 * @param {Object} params The values of the form fields
		 */
		applyRequest: function (params) {
			var editor = this.editor,
				html = '';
			this.restoreSelection();
			if (params['allContent']) {
				html = editor.getInnerHTML();
			} else {
				html = editor.getSelection().getHtml();
			}
			if (html) {
				if (params['allHtml']) {
					html = html.replace(/<[\!]*?[^<>]*?>/g, "");
				}
				if (params['formatting']) {
					// Remove font, b, strong, i, em, u, strike, span and other inline tags
					html = html.replace(/<\/?(abbr|acronym|b|big|cite|code|em|font|i|q|s|samp|small|span|strike|strong|sub|sup|tt|u|var)(>|[^>a-zA-Z][^>]*>)/gi, '');
					// Keep tags, strip attributes
					html = html.replace(/[ \t\n\r]+(style|class|align|cellpadding|cellspacing|frame|bgcolor)=\"[^>\"]*\"/gi, "");
				}
				if (params['spaces']) {
					// Replace non-breaking spaces by normal spaces
					html = html.replace(/&nbsp;/g, " ");
				}
				if (params['images']) {
					// remove any IMG tag
					html = html.replace(/<\/?(img|imagedata)(>|[^>a-zA-Z][^>]*>)/gi, "");
				}
				if (params['msWordFormatting']) {
					// Make one line
					html = html.replace(/[ \r\n\t]+/g, " ");
					// Clean up tags
					html = html.replace(/<(b|strong|i|em|p|li|ul) [^>]*>/gi, "<$1>");
					// Keep tags, strip attributes
					html = html.replace(/ (style|class|align)=\"[^>\"]*\"/gi, "");
					// Remove unwanted tags: div, link, meta, span, ?xml:, [a-z]+:
					html = html.replace(/<\/?(div|link|meta|span)(>|[^>a-zA-Z][^>]*>)/gi, "");
					html = html.replace(/<\?xml:[^>]*>/gi, "").replace(/<\/?[a-z]+:[^>]*>/g, "");
					// Remove images
					html = html.replace(/<\/?(img|imagedata)(>|[^>a-zA-Z][^>]*>)/gi, "");
					// Remove MS-specific tags
					html = html.replace(/<\/?(f|formulas|lock|path|shape|shapetype|stroke)(>|[^>a-zA-Z][^>]*>)/gi, "");
					// Remove unwanted tags and their contents: style, title
					html = html.replace(/<style[^>]*>.*?<\/style[^>]*>/gi, "").replace(/<title[^>]*>.*<\/title[^>]*>/gi, "");
					// Remove comments
					html = html.replace(/<!--[^>]*>/gi, "");
					// Remove xml tags
					html = html.replace(/<xml.[^>]*>/gi, "");
					// Remove inline elements resets
					html = html.replace(/<\/(b[^a-zA-Z]|big|i[^a-zA-Z]|s[^a-zA-Z]|small|strike|tt|u[^a-zA-Z])><\1>/gi, "");
					// Remove double tags
					var oldlen = html.length + 1;
					while(oldlen > html.length) {
						oldlen = html.length;
						// Remove double opening tags
						html = html.replace(/<([a-z][a-z]*)> *<\/\1>/gi, " ").replace(/<([a-z][a-z]*)> *<\/?([a-z][^>]*)> *<\/\1>/gi, "<$2>");
						// Remove double closing tags
						html = html.replace(/<([a-z][a-z]*)><\1>/gi, "<$1>").replace(/<\/([a-z][a-z]*)><\/\1>/gi, "<\/$1>");
						// Remove multiple spaces
						html = html.replace(/[\x20]+/gi, " ");
					}
				}
				if (params['typographical']) {
					// Remove typographical punctuation
					// Search pattern stored here
					var SrcCd;
					// Replace horizontal ellipsis with three periods
					SrcCd = String.fromCharCode(8230);
					html = html.replace(new RegExp(SrcCd, 'g'), '...');
					// Replace en-dash and  em-dash with hyphen
					SrcCd = String.fromCharCode(8211) + '|' + String.fromCharCode(8212);
					html = html.replace(new RegExp(SrcCd, 'g'), '-');
					html = html.replace(new RegExp(SrcCd, 'g'), "'");
					// Replace double low-9 / left double / right double quotation mark with double quote
					SrcCd = String.fromCharCode(8222) + '|' + String.fromCharCode(8220) + '|' + String.fromCharCode(8221);
					html = html.replace(new RegExp(SrcCd, 'g'), '"');
					// Replace left single / right single / single low-9 quotation mark with single quote
					SrcCd = String.fromCharCode(8216) + '|' + String.fromCharCode(8217) + '|' + String.fromCharCode(8218);
					html = html.replace(new RegExp(SrcCd, 'g'), "'");
					// Replace single left/right-pointing angle quotation mark with single quote
					SrcCd = String.fromCharCode(8249) + '|' + String.fromCharCode(8250);
					html = html.replace(new RegExp(SrcCd, 'g'), "'");
					// Replace left/right-pointing double angle quotation mark (left/right pointing guillemet) with double quote
					SrcCd = String.fromCharCode(171) + '|' + String.fromCharCode(187);
					html = html.replace(new RegExp(SrcCd, 'g'), '"');
					// Replace grave accent (spacing grave) and acute accent (spacing acute) with apostrophe (single quote)
					SrcCd = String.fromCharCode(96) + '|' + String.fromCharCode(180);
					html = html.replace(new RegExp(SrcCd, 'g'), '"');
				}
				if (params['allContent']) {
					editor.setHTML(html);
				} else {
					editor.getSelection().insertHtml(html);
				}
			}
		}
	});

	return RemoveFormat;
});
