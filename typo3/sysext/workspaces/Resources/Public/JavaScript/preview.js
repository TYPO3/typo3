/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

Ext.onReady(function() {
	var viewport = new Ext.Viewport({
		layout: 'border',
		items: [
		{
			xtype: 'tabpanel',
			region: 'center', // a center region is ALWAYS required for border layout
			id: 'preview',
			activeTab: 0,
			items: [
				{
					title: 'Workspace preview',
					id: 'workspaceRegion',
					layout: 'fit',
					items: [{
						xtype: 'iframePanel',
						id: 'wsPanel',
						doMask: false,
						src: wsUrl
					}]
				}, {
					title: 'Live Workspace',
					id: 'liveRegion',
					layout: 'fit',
					items: [{
						xtype: 'iframePanel',
						id: 'livePanel',
						doMask: false,
						src: liveUrl
					}]
				},{
					title: 'List view',
					id: 'wsSettings',
					layout: 'fit',
					items:  [{
						xtype: 'iframePanel',
						id: 'settingsPanel',
						doMask: false,
						src: wsSettingsUrl
					}]
				},{
					title: 'Help',
					id: 'wsHelp',
					layout: 'fit',
					items:  [{
						xtype: 'iframePanel',
						id: 'settingsPanel',
						doMask: false,
						src: wsHelpUrl
					}]
				}
			]
		}]
	});
});