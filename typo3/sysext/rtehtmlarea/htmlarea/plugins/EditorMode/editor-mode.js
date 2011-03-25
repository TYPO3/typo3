/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * EditorMode Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.EditorMode = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
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
	/*
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
	/*
	 * This function gets called when the toolbar is updated
	 *
	 * @return	void
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		button.setInactive(mode !== button.itemId.toLowerCase());
	}
});
