/***************************************************************
*  Copyright notice
*
*  (c) 2004 Cau guanabara <caugb@ibest.com.br>
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
 * Find and Replace Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
FindReplace = HTMLArea.Plugin.extend({

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
			version		: "1.2",
			developer	: "Cau Guanabara & Stanislas Rolland",
			developerUrl	: "mailto:caugb@ibest.com.br",
			copyrightOwner	: "Cau Guanabara & Stanislas Rolland",
			sponsor		: "Independent production & SJBR",
			sponsorUrl	: "http://www.netflash.com.br/gb/HA3-rc1/examples/find-replace.html",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the button
		 */
		var buttonId = "FindReplace";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize("Find and Replace"),
			action		: "onButtonPress",
			dialog		: true
		};
		this.registerButton(buttonConfiguration);

		this.popupWidth = 400;
		this.popupHeight = 360;

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
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;

		var sel = this.editor.getSelectedHTML(), param = null;
		if (/\w/.test(sel)) {
			sel = sel.replace(/<[^>]*>/g,"");
			sel = sel.replace(/&nbsp;/g,"");
		}
		if (/\w/.test(sel)) {
			param = { fr_pattern: sel };
		}
		if (HTMLArea.is_opera) {
			this.cleanUpFunctionReference = this.makeFunctionReference("cleanUp");
			this.cleanUpRegularExpression = /(<span\s+[^>]*id=.?frmark[^>]*>)([^<>]*)(<\/span>)/gi;
			this.editor._iframe.contentWindow.setTimeout(this.cleanUpFunctionReference, 200);
		}
		this.dialog = this.openDialog("FindReplace", this.makeUrlFromPopupName("find_replace"), null, param, {width:this.popupWidth, height:this.popupHeight});
		return false;
	},

	/*
	 * This function cleans up any span tag left by Opera if the window was closed with the close handle in which case the unload event is not fired by Opera
	 *
	 * @return	void
	 */
	cleanUp : function () {
		if (this.dialog && (!this.dialog.dialogWindow || (this.dialog.dialogWindow && this.dialog.dialogWindow.closed))) {
			this.getPluginInstance("EditorMode").setHTML(this.getPluginInstance("EditorMode").getInnerHTML().replace(this.cleanUpRegularExpression,"$2"));
			this.dialog.close();
		} else {
			this.editor._iframe.contentWindow.setTimeout(this.cleanUpFunctionReference, 200);
		}
	}
});
