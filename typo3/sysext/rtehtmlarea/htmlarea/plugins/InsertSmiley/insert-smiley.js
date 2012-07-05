/***************************************************************
*  Copyright notice
*
*  (c) 2004 Ki Master George <kimastergeorge@gmail.com>
*  (c) 2005-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 */
HTMLArea.InsertSmiley = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		this.pageTSConfiguration = this.editorConfiguration.buttons.emoticon;
			// Default set of imoticons from Mozilla Thunderbird
		this.icons = [
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_smile' + '.png', alt: ':-)', title: this.localize('mozilla_smile')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_frown' + '.png', alt: ':-(', title: this.localize('mozilla_frown')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_wink' + '.png', alt: ';-)', title: this.localize('mozilla_wink')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_tongueout' + '.png', alt: ':-P', title: this.localize('mozilla_tongueout')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_laughing' + '.png', alt: ':-D', title: this.localize('mozilla_laughing')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_embarassed' + '.png', alt: ':-[', title: this.localize('mozilla_embarassed')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_undecided' + '.png', alt: ':-\\', title: this.localize('mozilla_undecided')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_surprised' + '.png', alt: '=-O', title: this.localize('mozilla_surprised')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_kiss' + '.png', alt: ':-*', title: this.localize('mozilla_kiss')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_yell' + '.png', alt: '>:o', title: this.localize('mozilla_yell')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_cool' + '.png', alt: '8-)', title: this.localize('mozilla_cool')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_moneyinmouth' + '.png', alt: ':-$', title: this.localize('mozilla_moneyinmouth')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_footinmouth' + '.png', alt: ':-!', title: this.localize('mozilla_footinmouth')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_innocent' + '.png', alt: 'O:-)', title: this.localize('mozilla_innocent')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_cry' + '.png', alt: ':\'(', title: this.localize('mozilla_cry')},
			{ file: HTMLArea.editorUrl + 'plugins/InsertSmiley/smileys/' + 'mozilla_sealed' + '.png', alt: ':-X', title: this.localize('mozilla_sealed')}
		 ];
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.2',
			developer	: 'Ki Master George & Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Ki Master George & Stanislas Rolland',
			sponsor		: 'Ki Master George & SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the button
		 */
		var buttonId = 'InsertSmiley';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize('Insert Smiley'),
			iconCls		: 'htmlarea-action-smiley-insert',
			action		: 'onButtonPress',
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
		var dimensions = this.getWindowDimensions({width:216, height:230}, buttonId);
		this.dialog = new Ext.Window({
			title: this.localize('Insert Smiley'),
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
			iconCls: this.getButton(buttonId).iconCls,
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
					'<tpl for="."><a href="#" class="emoticon" hidefocus="on" ext:qtitle="{alt}" ext:qtip="{title}"><img src="{file}" /></a></tpl>'
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
		event.stopEvent();
		this.restoreSelection();
		var icon = Ext.get(target).first();
		var imgTag = this.editor.document.createElement('img');
		imgTag.setAttribute('src', icon.getAttribute('src'));
		imgTag.setAttribute('alt', target.getAttribute('ext:qtitle'));
		imgTag.setAttribute('title', target.getAttribute('ext:qtip'));
		this.editor.getSelection().insertNode(imgTag);
		if (!HTMLArea.isIEBeforeIE9) {
			this.editor.getSelection().selectNode(imgTag, false);
		}
		this.close();
		return false;
	}
});
