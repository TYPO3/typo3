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

	Ext.state.Manager.setProvider(new TYPO3.state.ExtDirectProvider({
		key: 'moduleData.Workspaces.States',
		autoRead: false
	}));

	if (Ext.isObject(TYPO3.settings.Workspaces.States)) {
		Ext.state.Manager.getProvider().initState(TYPO3.settings.Workspaces.States);
	}


	var iconClsChecked = 't3-icon t3-icon-status t3-icon-status-status t3-icon-status-checked';
	var iconClsEmpty = 't3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty';
	var viewMode = 0;


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
						width: 460,
						items: [{
							xtype: 'panel',
							id: 'slider',
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
						}]
					},
					{
						id: 'visual-mode-toolbar',
						items: [{
							xtype: 'button',
							iconCls: 'x-btn-icon t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-options-view',
							id: 'visual-mode-options',
							menu: {
								id: 'visual-mode-selector',
								stateful: true,
								stateId: 'WorkspacePreviewModeSelect',
								stateEvents: ['itemclick'],
								items: [{
									text: TYPO3.LLL.Workspaces.modeSlider,
									id: 'visual-mode-selector-slider',
									checked: false,
									group: 'mode',
									hidden: TYPO3.settings.Workspaces.SplitPreviewModes.indexOf('slider') == -1,
									checkHandler: modeChange
								},{
									text: TYPO3.LLL.Workspaces.modeVbox,
									id: 'visual-mode-selector-vbox',
									checked: false,
									group: 'mode',
									hidden: TYPO3.settings.Workspaces.SplitPreviewModes.indexOf('vbox') == -1,
									checkHandler: modeChange

								},{
									text: TYPO3.LLL.Workspaces.modeHbox,
									id: 'visual-mode-selector-hbox',
									checked: false,
									group: 'mode',
									hidden: TYPO3.settings.Workspaces.SplitPreviewModes.indexOf('hbox') == -1,
									checkHandler: modeChange
								}],
								getState:function() {
									return {viewMode: viewMode};
								},
								applyState: function(state) {
									viewMode = state.viewMode;
									modeChange(null, true, viewMode);
								}
							}
						}]
					}
				]
			}],
			items: [{
				title: TYPO3.LLL.Workspaces.visualPreview,
				id: 'wsVisual',
				layout: 'fit',
				anchor: '100% 100%',
				listeners: {
					activate: function () {
						if (Ext.isObject(top.Ext.getCmp('slider'))) {
							top.Ext.getCmp('slider').show();
							top.Ext.getCmp('visual-mode-options').show();
						}
					}
				},
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
				listeners: {
					activate: function () {
						top.Ext.getCmp('slider').hide();
						top.Ext.getCmp('visual-mode-options').hide();
					}
				},
				items:  [{
					xtype: 'iframePanel',
					id: 'settingsPanel',
					doMask: false,
					src: wsSettingsUrl
				}]
			}]
		}]


	});

	function modeChange(item, checked, mode) {
		if (checked) {
			var id ,
				ids = ['visual-mode-selector-slider', 'visual-mode-selector-hbox', 'visual-mode-selector-vbox'],
				panelHbox = Ext.getCmp('visualPanel-hbox'),
				panelVbox = Ext.getCmp('visualPanel-vbox'),
				panelSlider = Ext.getCmp('visualPanel'),
				slider = Ext.getCmp('slider'),
				itemSlider = Ext.getCmp('visual-mode-selector-slider'),
				itemHbox = Ext.getCmp('visual-mode-selector-hbox'),
				itemVbox = Ext.getCmp('visual-mode-selector-vbox');

			if (item) {
				mode = ids.indexOf(item.id);
			}

			Ext.select('#visual-mode-selector ul li a img.t3-icon-status-checked').removeClass(iconClsChecked.split(" "));

			var splitPreviewModes = TYPO3.settings.Workspaces.SplitPreviewModes;
			if (splitPreviewModes.length == 1) {
				Ext.getCmp('visual-mode-options').hide();
			}

			if (splitPreviewModes.indexOf('vbox') == -1 && mode === 2) {
				mode = 0
			}
			if (splitPreviewModes.indexOf('slider') == -1 && mode === 0) {
				mode = 1
			}
			if (splitPreviewModes.indexOf('hbox') == -1 && mode === 1) {
				mode = 2
			}

			if (mode === 0) {
				panelHbox.hide();
				panelVbox.hide();
				panelSlider.show();
				slider.show();
				viewMode = 0;
				itemSlider.setIconClass(iconClsChecked);
				itemHbox.setIconClass(iconClsEmpty);
				itemVbox.setIconClass(iconClsEmpty);
			} else if (mode === 1) {
				panelHbox.show();
				panelVbox.hide();
				panelSlider.hide();
				slider.hide();
				viewMode = 1;
				itemSlider.setIconClass(iconClsEmpty);
				itemHbox.setIconClass(iconClsChecked);
				itemVbox.setIconClass(iconClsEmpty);
			} else if (mode === 2) {
				panelHbox.hide();
				panelVbox.show();
				panelSlider.hide();
				slider.hide();
				viewMode = 2;
				itemSlider.setIconClass(iconClsEmpty);
				itemHbox.setIconClass(iconClsEmpty);
				itemVbox.setIconClass(iconClsChecked);
			}

		}
	}
});