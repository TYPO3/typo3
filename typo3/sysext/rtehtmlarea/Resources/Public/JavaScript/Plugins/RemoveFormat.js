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
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, Util) {

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
		 * @param	object		editor: the editor instance
		 * @param	string		id: the button id or the key
		 *
		 * @return	boolean		false if action is completed
		 */
		onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			// Open dialogue window
			this.openDialogue(
				buttonId,
				'Remove formatting',
				this.getWindowDimensions(
					{
						width: 260,
						height:260
					},
					buttonId
				)
			);
			return false;
		},
		/*
		 * Open the dialogue window
		 *
		 * @param	string		buttonId: the button id
		 * @param	string		title: the window title
		 * @param	object		dimensions: the opening dimensions of the window
		 *
		 * @return	void
		 */
		openDialogue: function (buttonId, title, dimensions) {
			this.dialog = new Ext.Window({
				title: this.getHelpTip('', title),
				cls: 'htmlarea-window',
				border: false,
				width: dimensions.width,
				height: 'auto',
				iconCls: this.getButton(buttonId).iconCls,
				listeners: {
					close: {
						fn: this.onClose,
						scope: this
					}
				},
				items: [{
						xtype: 'fieldset',
						title: this.getHelpTip('area', 'Cleaning Area'),
						defaultType: 'radio',
						labelWidth: 140,
						defaults: {
							labelSeparator: ''
						},
						items: [{
								itemId: 'selection',
								fieldLabel: this.getHelpTip('selection', 'Selection'),
								name: 'htmlarea-removeFormat-area'
							},{
								itemId: 'allContent',
								fieldLabel: this.getHelpTip('all', 'All'),
								checked: true,
								name: 'htmlarea-removeFormat-area'
							}
						]
					},{
						xtype: 'fieldset',
						defaultType: 'checkbox',
						title: this.getHelpTip('options', 'Cleaning options'),
						labelWidth: 170,
						defaults: {
							labelSeparator: ''
						},
						items: [{
								itemId: 'formatting',
								fieldLabel: this.getHelpTip('htmlFormat', 'Formatting:')
							},{
								itemId: 'msWordFormatting',
								fieldLabel: this.getHelpTip('msWordFormat', 'MS Word Formatting:'),
								checked: true
							},{
								itemId: 'typographical',
								fieldLabel: this.getHelpTip('typographicalPunctuation', 'Typographical punctuation:')
							},{
								itemId: 'spaces',
								fieldLabel: this.getHelpTip('nonBreakingSpace', 'Spaces')
							},{
								itemId: 'images',
								fieldLabel: this.getHelpTip('images', 'Images:')
							},{
								itemId: 'allHtml',
								fieldLabel: this.getHelpTip('allHtml', 'All HTML:')
							}
						]
					}
				],
				buttons: [
					this.buildButtonConfig('OK', this.onOK),
					this.buildButtonConfig('Cancel', this.onCancel)
				]
			});
			this.show();
		},
		/*
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
				params[field] = this.dialog.find('itemId', field)[0].getValue();
			}
			if (params['allHtml'] || params['formatting'] || params['spaces'] || params['images'] || params['msWordFormatting'] || params['typographical']) {
				this.applyRequest(params);
				this.close();
			} else {
				TYPO3.Dialog.InformationDialog({
					title: this.getButton('RemoveFormat').tooltip.title,
					msg: this.localize('Select the type of formatting you wish to remove.')
				});
			}
			return false;
		},
		/*
		 * Perform the cleaning request
		 * @param	object		params: the values of the form fields
		 *
		 * @return	void
		 */
		applyRequest: function (params) {
			var editor = this.editor;
			this.restoreSelection();
			if (params['allContent']) {
				var html = editor.getInnerHTML();
			} else {
				var html = editor.getSelection().getHtml();
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
					html = html.replace(/<style[^>]*>.*?<\/style[^>]*>/gi, "").
						replace(/<title[^>]*>.*<\/title[^>]*>/gi, "");
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
