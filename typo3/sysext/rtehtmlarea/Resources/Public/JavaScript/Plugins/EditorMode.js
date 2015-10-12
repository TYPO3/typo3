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
 * EditorMode Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, Util) {

	var EditorMode = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(EditorMode, Plugin);
	Util.apply(EditorMode.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			/**
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

			/**
			 * Registering the buttons
			 */
			var buttonList = this.buttonList, buttonId;
			for (var i = 0, n = buttonList.length; i < n; ++i) {
				var button = buttonList[i];
				buttonId = button[0];
				var buttonConfiguration = {
					id		: buttonId,
					tooltip		: this.localize(buttonId + '-Tooltip'),
					iconCls		: 'htmlarea-action-editor-toggle-mode',
					action		: 'onButtonPress',
					context		: button[1],
					textMode	: (buttonId == 'TextMode')
				};
				this.registerButton(buttonConfiguration);
			}
			return true;
		},

		/**
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['TextMode', null]
		],

		/**
		 * This function gets called when a button was pressed.
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
			this.editor.setMode((this.editor.getMode() == buttonId.toLowerCase()) ? 'wysiwyg' : buttonId.toLowerCase());
			return false;
		},

		/**
		 * This function gets called when the toolbar is updated
		 *
		 * @return	void
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			button.setInactive(mode !== button.itemId.toLowerCase());
		}
	});

	return EditorMode;

});
