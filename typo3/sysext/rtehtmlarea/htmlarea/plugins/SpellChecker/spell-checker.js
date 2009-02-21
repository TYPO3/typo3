/***************************************************************
*  Copyright notice
*
*  (c) 2003 dynarch.com. Authored by Mihai Bazon, sponsored by www.americanbible.org.
*  (c) 2004-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Spell Checker Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
SpellChecker = HTMLArea.Plugin.extend({

	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},

	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {

		this.pageTSconfiguration = this.editorConfiguration.buttons.spellcheck;
		this.contentISOLanguage = this.pageTSconfiguration.contentISOLanguage;
		this.contentCharset = this.pageTSconfiguration.contentCharset;
		this.spellCheckerMode = this.pageTSconfiguration.spellCheckerMode;
		this.enablePersonalDicts = this.pageTSconfiguration.enablePersonalDicts;
		this.userUid = this.editorConfiguration.userUid;
		this.defaultDictionary = (this.pageTSconfiguration.dictionaries && this.pageTSconfiguration.dictionaries[this.contentISOLanguage] && this.pageTSconfiguration.dictionaries[this.contentISOLanguage].defaultValue) ? this.pageTSconfiguration.dictionaries[this.contentISOLanguage].defaultValue : "";
		this.showDictionaries = (this.pageTSconfiguration.dictionaries && this.pageTSconfiguration.dictionaries.items) ? this.pageTSconfiguration.dictionaries.items : "";
		this.restrictToDictionaries = (this.pageTSconfiguration.dictionaries && this.pageTSconfiguration.dictionaries.restrictToItems) ? this.pageTSconfiguration.dictionaries.restrictToItems : "";

		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "2.2",
			developer	: "Mihai Bazon & Stanislas Rolland",
			developerUrl	: "http://dynarch.com/mishoo/",
			copyrightOwner	: "Mihai Bazon & Stanislas Rolland",
			sponsor		: "American Bible Society & SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the button
		 */
		var buttonId = "SpellCheck";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize("SC-spell-check"),
			action		: "onButtonPress",
			dialog		: true
		};
		this.registerButton(buttonConfiguration);
	},

	/*
	 * This function gets called when the button was pressed.
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

		var editorNumber = editor._editorNumber;
		switch (buttonId) {
			case "SpellCheck":
				var charset = (this.contentCharset.toLowerCase() == 'iso-8859-1') ? "-iso-8859-1" : "";
				this.dialog = this.openDialog(buttonId, this.makeUrlFromPopupName("spell-check-ui" + charset), null, null, {width:710, height:600});
				break;
		}
		return false;
	}
});

