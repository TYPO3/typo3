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
		items: [{
			xtype: 'tabpanel',
			region: 'center', // a center region is ALWAYS required for border layout
			id: 'preview',
			activeTab: 0,
			plugins : [{
				ptype : 'Ext.ux.plugins.TabStripContainer',
				id: 'controls',
				width: 400,
				items: [{
					xtype: 'button',
					id: 'sizeSliderButtonLive',
					cls: 'sliderButton',
					text: TYPO3.LLL.Workspaces.livePreview,
					width: 100,
					listeners: {
						click: {
							fn: function () {
								Ext.getCmp('sizeSlider').setValue(0);
							}
						}
					}
				},
				{
					xtype: 'slider',
					id: 'sizeSlider',
					margins: '0 10 0 10',
					maxValue: 100,
					minValue: 0,
					value: 100,
					flex: 1,
					listeners: {
						change: {
							fn: function resizeFromValue(slider, newValue, thumb) {
								var height = Ext.getCmp('wsPanel').getHeight();
								Ext.getCmp('liveContainer').setHeight(height * (100 - newValue) / 100);
								Ext.getCmp('visualPanel').setHeight(height);
							}
						}
					}
				},
				{
					xtype: 'button',
					id: 'sizeSliderButtonWorkspace',
					cls: 'sliderButton',
					text: TYPO3.LLL.Workspaces.workspacePreview,
					width: 100,
					listeners: {
						click: {
							fn: function () {
								Ext.getCmp('sizeSlider').setValue(100);
							}
						}
					}
				}]
			}],
			items: [{
				title: TYPO3.LLL.Workspaces.visualPreview,
				id: 'wsVisual',
				layout: 'fit',
				items: [{
					layout: 'fit',
					x: 0, y:0,
					anchor: '100% 100%',
					autoScroll: true,
					items: [{
						layout: 'absolute',
						id: 'visualPanel',
						items: [{
							x: 0, y:0,
							anchor: '100% 100%',
							id: 'wsContainer',
							layout: 'absolute',
							autoScroll: false,
							items:[{
								xtype: 'iframePanel',
								x: 0, y:0,
								id: 'wsPanel',
								doMask: false,
								src: wsUrl,
								autoScroll: false
							}]
						},{
							x: 0, y:0,
							anchor: '100% 0%',
							id: 'liveContainer',
							layout: 'absolute',
							bodyStyle: 'height:0px;border-bottom: 2px solid red;',
							autoScroll: false,
							items:[{
								xtype: 'iframePanel',
								x: 0, y:0,
								id: 'livePanel',
								doMask: false,
								src: liveUrl,
								autoScroll: false
							}]
						}]
					}]
				}]
			},{
				title: TYPO3.LLL.Workspaces.listView,
				id: 'wsSettings',
				layout: 'fit',
				items:  [{
					xtype: 'iframePanel',
					id: 'settingsPanel',
					doMask: false,
					src: wsSettingsUrl
				}]
			},{
				title: TYPO3.LLL.Workspaces.helpView,
				id: 'wsHelp',
				layout: 'fit',
				items:  [{
					xtype: 'iframePanel',
					id: 'settingsPanel',
					doMask: false,
					src: wsHelpUrl
				}]
			}]
		}]
	});
});