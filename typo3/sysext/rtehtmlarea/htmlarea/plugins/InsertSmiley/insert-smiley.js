/***************************************************************
*  Copyright notice
*
*  (c) 2004 Ki Master George <kimastergeorge@gmail.com>
*  (c) 2005-2009 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Insert Smiley Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */

InsertSmiley = HTMLArea.Plugin.extend({

	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},

	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {

		this.pageTSConfiguration = this.editorConfiguration.buttons.emoticon;

		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.2",
			developer	: "Ki Master George & Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Ki Master George & Stanislas Rolland",
			sponsor		: "Ki Master George & Fructifor Inc.",
			sponsorUrl	: "http://www.fructifor.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the button
		 */
		var buttonId = "InsertSmiley";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize("Insert Smiley"),
			action		: "onButtonPress",
			hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
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
	onButtonPress : function (editor, id) {

		var sel = this.editor.getSelectedHTML().replace(/(<[^>]*>|&nbsp;|\n|\r)/g,"");
		var param = new Object();
		param.editor_url = _typo3_host_url + _editor_url;
		if (param.editor_url == "../") {
			param.editor_url = document.URL;
			param.editor_url = param.editor_url.replace(/^(.*\/).*\/.*$/g, "$1");
		}
		this.dialog = this.openDialog("InsertSmiley", this.makeUrlFromPopupName("insertsmiley"), "insertImageTag", param, {width:250, height:230});
	},

	/*
	 * Insert the selected smiley
	 *
	 * @param	object		param: the selected smiley
	 *
	 * @return	boolean		false
	 */
	insertImageTag : function (param) {
		if (param && typeof(param.imgURL) != "undefined") {
			this.editor.focusEditor();
			this.editor.insertHTML('<img src="' + param.imgURL + '" alt="Smiley" />');
		}
		return false;
	}
});

