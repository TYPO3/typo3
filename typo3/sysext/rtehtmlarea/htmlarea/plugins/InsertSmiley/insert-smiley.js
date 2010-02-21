/***************************************************************
*  Copyright notice
*
*  (c) 2004 Ki Master George <kimastergeorge@gmail.com>
*  (c) 2005-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
		this.editor_url = _typo3_host_url + _editor_url;
		if (this.editor_url == '../') {
			this.editor_url = document.URL.replace(/^(.*\/).*\/.*$/g, "$1");
		}
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "2.0",
			developer	: "Ki Master George & Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Ki Master George & Stanislas Rolland",
			sponsor		: "Ki Master George & SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
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
	onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		var dimensions = this.getWindowDimensions({width:175, height:230}, buttonId);
		this.dialog = new Ext.Window({
			title: this.localize('Insert Smiley'),
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
			iconCls: buttonId,
			listeners: {
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: {
				xtype: 'box',
				cls: 'emoticon-array',
				tpl: new Ext.XTemplate(
					'<tpl for="."><a href="#" class="emoticon" hidefocus="on"><img alt="" title="" src="{.}" /></a></tpl>'
				),
				listeners: {
					render: {
						fn: this.render,
						scope: this
					}
				}
			},
			buttons: [this.buildButtonConfig('Cancel', this.onCancel)]
		});
		this.show();
	},
	/*
	 * Render the array of emoticon
	 *
	 * @param	object		component: the box containing the emoticons
	 *
	 * @return	void
	 */
	render: function (component) {
		this.icons = [];
		var numberOfIcons = 20, inum;
		for (var i = 1; i <= numberOfIcons; i++) {
			inum = i;
			if (i < 10) {
				inum = '000' + i;
			} else if (i < 100) {
				inum = '00' + i;
			} else if (i < 1000) {
				inum = '0' + i;
			}
			this.icons.push(this.editor_url + 'plugins/InsertSmiley/smileys/' + inum + '.gif');
		}
		component.tpl.overwrite(component.el, this.icons);
		component.mon(component.el, 'click', this.insertImageTag, this, {delegate: 'a'});
	},
	/*
	 * Insert the selected emoticon
	 *
	 * @param	object		event: the Ext event
	 * @param	HTMLelement	target: the html element target
	 *
	 * @return	void
	 */
	insertImageTag: function (event, target) {
		this.editor.focus();
		this.restoreSelection();
		this.editor.insertHTML('<img src="' + Ext.get(target).first().getAttribute('src') + '" alt="" />');
		this.close();
	}
});
