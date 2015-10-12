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
 * Default Inline Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, Util) {

	var DefaultInline = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(DefaultInline, Plugin);
	Util.apply(DefaultInline.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '1.3',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);

			/**
			 * Registering the buttons
			 */
			var button, buttonId;
			for (var i = 0, n = this.buttonList.length; i < n; i++) {
				button = this.buttonList[i];
				buttonId = button[0];
				var buttonConfiguration = {
					id		: buttonId,
					tooltip		: this.localize(buttonId + '-Tooltip'),
					iconCls		: 'htmlarea-action-' + button[2],
					textMode	: false,
					action		: 'onButtonPress',
					context		: button[1],
					hotKey		: (this.editorConfiguration.buttons[buttonId.toLowerCase()]?this.editorConfiguration.buttons[buttonId.toLowerCase()].hotKey:null)
				};
				this.registerButton(buttonConfiguration);
			}
			return true;
		},
		/*
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['Bold', null, 'bold'],
			['Italic', null, 'italic'],
			['StrikeThrough', null, 'strike-through'],
			['Subscript', null, 'subscript'],
			['Superscript', null, 'superscript'],
			['Underline', null, 'underline']
		],
		/*
		 * This function gets called when some inline element button was pressed.
		 */
		onButtonPress: function (editor, id) {
				// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			try {
				editor.getSelection().execCommand(buttonId, false, null);
			}
			catch(e) {
				this.appendToLog('onButtonPress', e + '\n\nby execCommand(' + buttonId + ');', 'error');
			}
			return false;
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				var commandState = false;
				try {
					commandState = this.editor.document.queryCommandState(button.itemId);
				} catch(e) {
					commandState = false;
				}
				button.setInactive(!commandState);
			}
		}
	});

	return DefaultInline;

});
