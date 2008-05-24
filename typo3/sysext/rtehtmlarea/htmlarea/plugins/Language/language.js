/***************************************************************
*  Copyright notice
*
*  (c) 2008 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Language Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id: language.js 2862 2008-01-05 19:32:58Z stanrolland $
 */
Language = HTMLArea.Plugin.extend({
		
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
		var buttonList = this.buttonList, buttonId;
		for (var i = 0, n = buttonList.length; i < n; ++i) {
			var button = buttonList[i];
			buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + "-Tooltip"),
				action		: "onButtonPress",
				context		: button[1]
			};
			this.registerButton(buttonConfiguration);
		}
		
		return true;
	 },
	 
	/* The list of buttons added by this plugin */
	buttonList : [
		["LeftToRight", null],
		["RightToLeft", null]
	],
	 
	/*
	 * This function gets called when a button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		
		var direction = (buttonId == "RightToLeft") ? "rtl" : "ltr";
		var el = this.editor.getParentElement();
		if (el) {
			if (el.nodeName.toLowerCase() === "bdo") {
				el.dir = direction;
			} else {
				el.dir = (el.dir == direction || el.style.direction == direction) ? "" : direction;
			}
			el.style.direction = "";
		}
		
		return false;
	},
	
	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar : function () {
		if (this.editor.getMode() === "wysiwyg" && this.editor.isEditable()) {
			var buttonList = this.buttonList, buttonId;
			for (var i = 0, n = buttonList.length; i < n; ++i) {
				buttonId = buttonList[i][0];
				if (this.isButtonInToolbar(buttonId)) {
					var el = this.editor.getParentElement();
					if (el) {
						var direction = (buttonId === "RightToLeft") ? "rtl" : "ltr";
						this.editor._toolbarObjects[buttonId].state("active",(el.dir == direction || el.style.direction == direction));
					}
				}
			}
		}
	}
});

