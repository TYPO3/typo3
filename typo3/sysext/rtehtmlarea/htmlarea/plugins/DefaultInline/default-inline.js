/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 *
 * TYPO3 SVN ID: $Id$
 */
DefaultInline = HTMLArea.Plugin.extend({
		
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "Fructifor Inc.",
			sponsorUrl	: "http://www.fructifor.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the buttons
		 */
		var buttonList = DefaultInline.buttonList, buttonId;
		var n = buttonList.length;
		for (var i = 0; i < n; ++i) {
			var button = buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + "-Tooltip"),
				textMode	: false,
				action		: "onButtonPress",
				context		: button[1],
				hotKey		: (this.editorConfiguration.buttons[buttonId.toLowerCase()]?this.editorConfiguration.buttons[buttonId.toLowerCase()].hotKey:null)
			};
			this.registerButton(buttonConfiguration);
		}
		
		return true;
	 },
	 
	/*
	 * This function gets called when some inline element button was pressed.
	 */
	onButtonPress : function (editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		editor.focusEditor();
		try {
			editor._doc.execCommand(buttonId, false, null);
		}
		catch(e) {
			this.appendToLog("onButtonPress", e + "\n\nby execCommand(" + buttonId + ");");
		}
		return false;
	},
	
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar : function () {
		var editor = this.editor;
		var buttonList = DefaultInline.buttonList;
		var buttonId, n = buttonList.length, commandState;
		if (editor.getMode() === "wysiwyg" && editor.isEditable()) {
			for (var i = 0; i < n; ++i) {
				buttonId = buttonList[i][0];
				if (this.isButtonInToolbar(buttonId)) {
					commandState = false;
					try {
						commandState = editor._doc.queryCommandState(buttonId);
					} catch(e) {
						commandState = false;
					}
					editor._toolbarObjects[buttonId].state("active", commandState);
				}
			}
		}
	}
});

/* The list of buttons added by this plugin */
DefaultInline.buttonList = [
	["Bold", null],
	["Italic", null],
	["StrikeThrough", null],
	["Subscript", null],
	["Superscript", null],
	["Underline", null]
];

