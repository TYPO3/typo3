/***************************************************************
*  Copyright notice
*
*  (c) 2005-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Remove Format Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
HTMLArea.RemoveFormat = HTMLArea.Plugin.extend({
	constructor: function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.1',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: 'SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
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
	/*
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
			title: this.localize(title),
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
				// As of ExtJS 3.1, JS error with IE when the window is resizable
			resizable: !Ext.isIE,
			iconCls: this.getButton(buttonId).iconCls,
			listeners: {
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: [{
					xtype: 'fieldset',
					title: this.localize('Cleaning Area'),
					defaultType: 'radio',
					labelWidth: 150,
					defaults: {
						labelSeparator: ''
					},
					items: [{
							itemId: 'selection',
							fieldLabel: this.localize('Selection'),
							name: 'htmlarea-removeFormat-area'
						},{
							itemId: 'allContent',
							fieldLabel: this.localize('All'),
							checked: true,
							name: 'htmlarea-removeFormat-area'
						}
					]
				},{
					xtype: 'fieldset',
					defaultType: 'checkbox',
					title: this.localize('Cleaning options'),
					labelWidth: 150,
					defaults: {
						labelSeparator: ''
					},
					items: [{
							itemId: 'formatting',
							fieldLabel: this.localize('Formatting:')
						},{
							itemId: 'msWordFormatting',
							fieldLabel: this.localize('MS Word Formatting:'),
							checked: true
						},{
							itemId: 'spaces',
							fieldLabel: this.localize('Spaces')
						},{
							itemId: 'images',
							fieldLabel: this.localize('Images:')
						},{
							itemId: 'allHtml',
							fieldLabel: this.localize('All HTML:')
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
			'spaces',
			'images',
			'allHtml'
		];
		var params = {};
		Ext.each(fields, function (field) {
			params[field] = this.dialog.find('itemId', field)[0].getValue();
		}, this);
		if (params['allHtml'] || params['formatting'] || params['spaces'] || params['images'] || params['msWordFormatting']) {
			this.applyRequest(params);
			this.close();
		} else {
			Ext.MessageBox.alert('', this.localize('Select the type of formatting you wish to remove.'));
		}
		return false;
	},
	/*
	 * Perform the cleaning request
	 * @param	object		params: the values of the form fields
	 *
	 * @return	void
	 */
	applyRequest: function(params) {
		var editor = this.editor;
		editor.focus();
		this.restoreSelection();
		if (params['allContent']) {
			var html = editor.getInnerHTML();
		} else {
			var html = editor.getSelectedHTML();
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
				html = html.replace(/<\/?img[^>]*>/gi, "");
			}
			if (params['msWordFormatting']) {
					// Make one line
				html = html.replace(/[ \r\n\t]+/g, " ");
					// Clean up tags
				html = html.replace(/<(b|strong|i|em|p|li|ul) [^>]*>/gi, "<$1>");
					// Keep tags, strip attributes
				html = html.replace(/ (style|class|align)=\"[^>\"]*\"/gi, "");
					// kill unwanted tags: span, div, ?xml:, st1:, [a-z]:, meta, link
				html = html.replace(/<\/?span[^>]*>/gi, "").
					replace(/<\/?div[^>]*>/gi, "").
					replace(/<\?xml:[^>]*>/gi, "").
					replace(/<\/?st1:[^>]*>/gi, "").
					replace(/<\/?[a-z]:[^>]*>/g, "").
					replace(/<\/?meta[^>]*>/g, "").
					replace(/<\/?link[^>]*>/g, "");
					// remove unwanted tags and their contents: style, title
				html = html.replace(/<style[^>]*>.*<\/style[^>]*>/gi, "").
					replace(/<title[^>]*>.*<\/title[^>]*>/gi, "");
					// remove comments
				html = html.replace(/<!--[^>]*>/gi, "");
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
			if (params['allContent']) {
				editor.setHTML(html);
			} else {
				editor.insertHTML(html);
			}
		}
	}
});
