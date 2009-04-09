/***************************************************************
*  Copyright notice
*
*  (c) 2004 Bernhard Pfeifer novocaine@gmx.net
*  (c) 2004 systemconcept.de. Authored by Holger Hees based on HTMLArea XTD 1.5 (http://mosforge.net/projects/htmlarea3xtd/).
*  (c) 2005-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
CharacterMap = HTMLArea.Plugin.extend({

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
			version		: "1.3",
			developer	: "Holger Hees, Bernhard Pfeifer, Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Holger Hees, Bernhard Pfeifer, Stanislas Rolland",
			sponsor		: "System Concept GmbH, Bernhard Pfeifer, Fructifor Inc.",
			sponsorUrl	: "http://www.fructifor.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the button
		 */
		var buttonId = "InsertCharacter";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId + "-Tooltip"),
			action		: "onButtonPress",
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
		this.dialog = this.openDialog("InsertCharacter", this.makeUrlFromPopupName("select_character"), "insertCharacter", null, {width:485, height:330});
		return false;
	},

	/*
	 * Insert the selected entity
	 *
	 * @param	object		entity: the chosen entity
	 *
	 * @return	boolean		false
	 */
	insertCharacter : function(entity) {
		if (typeof(entity) != "undefined") {
			this.editor.insertHTML(entity);
			this.dialog.focus();
		}
		return false;
	},

	/*
	 * This function gets called when the toolbar is updated
	 *
	 * @return	void
	 */
	onUpdateToolbar : function () {
			// Reclaim focus
		if (this.dialog) {
			this.dialog.focus();
		}
	}
});

