/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * About Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.AboutEditor = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function(editor) {
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.1',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: 'SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the button
		 */
		var buttonId = 'About';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId.toLowerCase()),
			action		: 'onButtonPress',
			textMode	: true,
			dialog		: true,
			iconCls		: 'htmlarea-action-editor-show-about'
		};
		this.registerButton(buttonConfiguration);
		return true;
	 },
	/*
	 * Supported browsers
	 */
	browsers: [
	 	 'Firefox 1.5+',
	 	 'Google Chrome 1.0+',
	 	 'Internet Explorer 6.0+',
	 	 'Opera 9.62+',
	 	 'Safari 3.0.4+',
	 	 'SeaMonkey 1.0+'
	],
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
		this.openDialogue(
			buttonId,
			'About HTMLArea',
			this.getWindowDimensions({width:450, height:350}, buttonId),
			this.buildTabItems()
		);
		return false;
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		buttonId: the button id
	 * @param	string		title: the window title
	 * @param	integer		dimensions: the opening width of the window
	 * @param	object		tabItems: the configuration of the tabbed panel
	 *
	 * @return	void
	 */
	openDialogue: function (buttonId, title, dimensions, tabItems) {
		this.dialog = new Ext.Window({
			title: this.localize(title),
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
				xtype: 'tabpanel',
				activeTab: 0,
				listeners: {
					activate: {
						fn: this.resetFocus,
						scope: this
					},
					tabchange: {
						fn: this.syncHeight,
						scope: this
					}
				},
				items: tabItems
			},
			buttons: [
				this.buildButtonConfig('Close', this.onCancel)
			]
		});
		this.show();
	},
	/*
	 * Build the configuration of the the tab items
	 *
	 * @return	array	the configuration array of tab items
	 */
	buildTabItems: function () {
		var tabItems = [];
			// About tab
		tabItems.push({
			xtype: 'panel',
			cls: 'about',
			title: this.localize('About'),
			html: '<h1 id="version">htmlArea RTE ' +  RTEarea[0].version + '</h1>'
				+ '<p>' + this.localize('free_editor').replace('<', '&lt;').replace('>', '&gt;') + '</p>'
				+ '<p><br />' + this.localize('Browser support') + ': ' + this.browsers.join(', ') + '.</p>'
				+ '<p><br />' + this.localize('product_documentation') + '&nbsp;<a href="http://typo3.org/extensions/repository/view/rtehtmlarea_manual/current/">typo3.org</a></p>'
				+ '<p style="text-align: center;">'
					+ '<br />'
					+ '&copy; 2002-2004 <a href="http://interactivetools.com" target="_blank">interactivetools.com, inc.</a><br />'
					+ '&copy; 2003-2004 <a href="http://dynarch.com" target="_blank">dynarch.com LLC.</a><br />'
					+ '&copy; 2004-2010 <a href="http://www.sjbr.ca" target="_blank">Stanislas Rolland</a><br />'
					+ this.localize('All rights reserved.')
				+ '</p>'
		});
			// Plugins tab
		if (!this.store) {
			this.store = new Ext.data.ArrayStore({
				fields: [{ name: 'name'}, { name: 'developer'},  { name: 'sponsor'}],
				sortInfo: {
					field: 'name',
					direction: 'ASC'
				},
				data: this.getPluginsInfo()
			});
		}
		tabItems.push({
			xtype: 'panel',
			cls: 'about-plugins',
			height: 200,
			title: this.localize('Plugins'),
			autoScroll: true,
			items: {
				xtype: 'listview',
				store: this.store,
				reserveScrollOffset: true,
				columns: [{
					header: this.localize('Name'),
					dataIndex: 'name',
					width: .33
				    },{
					header: this.localize('Developer'),
					dataIndex: 'developer',
					width: .33
				    },{
					header: this.localize('Sponsored by'),
					dataIndex: 'sponsor'
				}]
			}
		});
		return tabItems;
	},
	/*
	 * Format an arry of information on each configured plugin
	 *
	 * @return	array		array of data objects
	 */
	getPluginsInfo: function () {
		var pluginsInfo = [];
		Ext.iterate(this.editor.plugins, function (pluginId, plugin) {
			pluginsInfo.push([
				plugin.name + ' ' + plugin.version,
				'<a href="' + plugin.developerUrl + '" target="_blank">' + plugin.developer + '</a>',
				'<a href="' + plugin.sponsorUrl + '" target="_blank">' + plugin.sponsor + '</a>'
			]);
		}, this);
		return pluginsInfo;
	}
});
