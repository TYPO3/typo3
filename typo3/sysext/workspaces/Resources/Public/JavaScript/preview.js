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
	var iconClsChecked = 't3-icon t3-icon-status t3-icon-status-status t3-icon-status-checked';
	var iconClsEmpty = 't3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty';
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
				width: 600,
				items: [
						{
							xtype: 'panel',
							id: 'slider',
							width: 460,
							layout: 'hbox',
							items: [
								{
									xtype: 'button',
									id: 'sizeSliderButtonLive',
									cls: 'sliderButton',
									text: TYPO3.LLL.Workspaces.livePreview,
									tooltip: TYPO3.LLL.Workspaces.livePreviewDetail,
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
									width: 200,
									flex: 1,
									listeners: {
										change: {
											fn: function resizeFromValue(slider, newValue, thumb) {
												var height = Ext.getCmp('wsPanel').getHeight();
												Ext.getCmp('liveContainer').setHeight(height * (100 - newValue) / 100);
												//Ext.getCmp('visualPanel').setHeight(height);
											}
										}
									}
								},
								{
									xtype: 'button',
									id: 'sizeSliderButtonWorkspace',
									cls: 'sliderButton',
									text: TYPO3.LLL.Workspaces.workspacePreview,
									tooltip: TYPO3.LLL.Workspaces.workspacePreviewDetail,
									width: 100,
									listeners: {
										click: {
											fn: function () {
												Ext.getCmp('sizeSlider').setValue(100);
											}
										}
									}
								}
							]
						},
						{
							xtype: 'toolbar',
							id: 'visual-mode-toolbar',
							items: [{
								iconCls: 'x-btn-icon t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-options-view',
								id: 'visual-mode-options',
								menu: {
									id: 'visual-mode-selector',
									items: [{
										text: TYPO3.LLL.Workspaces.modeSlider,
										id: 'visual-mode-selector-slider',
										iconCls: iconClsChecked,
										handler: function(){
											Ext.getCmp('visualPanel-hbox').hide();
											Ext.getCmp('visualPanel-vbox').hide();
											Ext.getCmp('visualPanel').show();
											Ext.getCmp('slider').show();
											Ext.select('#visual-mode-selector ul li a img.t3-icon-status-checked').removeClass(iconClsChecked.split(" "))
											Ext.getCmp('visual-mode-selector-slider').setIconClass(iconClsChecked);
											Ext.getCmp('visual-mode-selector-hbox').setIconClass(iconClsEmpty);
											Ext.getCmp('visual-mode-selector-vbox').setIconClass(iconClsEmpty);
										}
									},{
										text: TYPO3.LLL.Workspaces.modeVbox,
										id: 'visual-mode-selector-vbox',
										iconCls: iconClsEmpty,
										handler: function() {
											Ext.getCmp('visualPanel-hbox').hide();
											Ext.getCmp('visualPanel-vbox').show();
											Ext.getCmp('visualPanel').hide();
											Ext.getCmp('slider').hide();
											Ext.select('#visual-mode-selector ul li a img.t3-icon-status-checked').removeClass(iconClsChecked.split(" "))
											Ext.getCmp('visual-mode-selector-slider').setIconClass(iconClsEmpty);
											Ext.getCmp('visual-mode-selector-vbox').setIconClass(iconClsChecked)
											Ext.getCmp('visual-mode-selector-hbox').setIconClass(iconClsEmpty);
										}
									},{
										text: TYPO3.LLL.Workspaces.modeHbox,
										id: 'visual-mode-selector-hbox',
										iconCls: iconClsEmpty,
										handler: function(){
											Ext.getCmp('visualPanel-hbox').show();
											Ext.getCmp('visualPanel-vbox').hide();
											Ext.getCmp('visualPanel').hide();
											Ext.getCmp('slider').hide();
											Ext.select('#visual-mode-selector ul li a img.t3-icon-status-checked').removeClass(iconClsChecked.split(" "))
											Ext.getCmp('visual-mode-selector-slider').setIconClass(iconClsEmpty);
											Ext.getCmp('visual-mode-selector-vbox').setIconClass(iconClsEmpty);
											Ext.getCmp('visual-mode-selector-hbox').setIconClass(iconClsChecked);
										}
									}]
								}
							}]
						}]
			}],
			items: [{
				title: TYPO3.LLL.Workspaces.visualPreview,
				id: 'wsVisual',
				layout: 'fit',
				anchor: '100% 100%',
				items: [{
					layout: 'absolute',
					anchor: '100% 100%',
					x: 0, y:0,
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
						},{
							layout: 'hbox',
							hidden: true,
							x: 0, y:0,
							anchor: '100% 100%',
							layoutConfig: {
								align : 'stretch',
								pack  : 'start'
							},
							id: 'visualPanel-hbox',
							items: [{
								xtype: 'iframePanel',
								x: 0, y:0,
								id: 'wsPanel-hbox',
								doMask: false,
								src: wsUrl,
								autoScroll: false,
								flex: 1
							},{
								xtype: 'iframePanel',
								x: 0, y:0,
								id: 'livePanel-hbox',
								doMask: false,
								src: liveUrl,
								autoScroll: false,
								flex: 1
							}]
					},{
							layout: 'vbox',
							hidden: true,
							x: 0, y:0,
							anchor: '100% 100%',
							layoutConfig: {
								align : 'stretch',
								pack  : 'start'
							},
							id: 'visualPanel-vbox',
							items: [{
								xtype: 'iframePanel',
								x: 0, y:0,
								id: 'wsPanel-vbox',
								doMask: false,
								src: wsUrl,
								autoScroll: false,
								flex: 1
							},{
								xtype: 'iframePanel',
								x: 0, y:0,
								id: 'livePanel-vbox',
								doMask: false,
								src: liveUrl,
								autoScroll: false,
								flex: 1
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
			}]
		}]
	});
});