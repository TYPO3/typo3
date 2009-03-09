/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Character Map Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
AboutEditor = HTMLArea.Plugin.extend({

	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},

	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {

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
		 * Registering the button
		 */
		var buttonId = "About";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId.toLowerCase()),
			action		: "onButtonPress",
			textMode	: true,
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
	onButtonPress : function(editor, id) {

					// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;

		this.dialog = this.openDialog("About", this.makeUrlFromPopupName("about"), null, null, {width:475, height:350});
		return false;
	}
});

