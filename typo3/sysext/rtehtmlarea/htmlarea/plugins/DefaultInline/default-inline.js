/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Default Inline Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.DefaultInline = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		/*
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
		/*
		 * Registering the buttons
		 */
		Ext.each(this.buttonList, function (button) {
			var buttonId = button[0];
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
			return true;
		}, this);
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

	/*
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

