/***************************************************************
*  Copyright notice
*
*  (c) 2004 Cau guanabara <caugb@ibest.com.br>
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
 * Quick Tag Editor Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
QuickTag = HTMLArea.Plugin.extend({

	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},

	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {

		this.pageTSConfiguration = this.editorConfiguration.buttons.inserttag;
		this.tags = (this.pageTSConfiguration && this.pageTSConfiguration.tags) ? this.pageTSConfiguration.tags : null;
		this.denyTags = (this.pageTSConfiguration && this.pageTSConfiguration.denyTags) ? this.pageTSConfiguration.denyTags : null;
		this.allowedAttribs =  (this.pageTSConfiguration && this.pageTSConfiguration.allowedAttribs) ? this.pageTSConfiguration.allowedAttribs : null;

		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.3",
			developer	: "Cau Guanabara & Stanislas Rolland",
			developerUrl	: "mailto:caugb@ibest.com.br",
			copyrightOwner	: "Cau Guanabara & Stanislas Rolland",
			sponsor		: "Independent production & SJBR",
			sponsorUrl	: "http://www.netflash.com.br/gb/HA3-rc1/examples/quick-tag.html",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the button
		 */
		var buttonId = "InsertTag";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize("Quick Tag Editor"),
			action		: "onButtonPress",
			selection	: true,
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
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function(editor, id, target) {
		this.dialog = this.openDialog("InsertTag", this.makeUrlFromPopupName("quicktag"), "setTag", null, {width:470, height:115});
	},

	/*
	 * Insert the tag
	 *
	 * @param	object		param: the constructed tag
	 *
	 * @return	boolean		false
	 */
	setTag : function(param) {
		if(param && typeof(param.tagopen) != "undefined") {
			this.editor.focusEditor();
			this.editor.surroundHTML(param.tagopen, param.tagclose);
		}
	}
});

