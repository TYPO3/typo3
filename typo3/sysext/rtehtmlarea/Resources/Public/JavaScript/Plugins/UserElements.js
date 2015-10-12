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
 * User Elements Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, Util) {

	var UserElements = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(UserElements, Plugin);
	Util.apply(UserElements.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.pageTSConfiguration = this.editorConfiguration.buttons.user;
			this.userModulePath = this.pageTSConfiguration.pathUserModule;

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
			 * Registering the button
			 */
			var buttonId = 'UserElements';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('Insert custom element'),
				iconCls		: 'htmlarea-action-user-element-edit',
				action		: 'onButtonPress',
				hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
				dialog		: true
			};
			this.registerButton(buttonConfiguration);
			return true;
		},

		/**
		 * This function gets called when the button was pressed
		 *
		 * @param	object		editor: the editor instance
		 * @param	string		id: the button id or the key
		 *
		 * @return	boolean		false if action is completed
		 */
		onButtonPress: function(editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			this.openContainerWindow(
				buttonId,
				'Insert custom element',
				this.getWindowDimensions(
					{
						width:	550,
						height:	350
					},
					buttonId
				),
				this.makeUrlFromModulePath(this.userModulePath)
			);
			return false;
		}
	});

	return UserElements;

});
