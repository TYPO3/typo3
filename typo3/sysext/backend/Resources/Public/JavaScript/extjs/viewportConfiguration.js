/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

Ext.ns('TYPO3');

/**
 * The Cards Configuration for the BE Module Cards
 *
 * New items need to be appended here
 * cards id needs to be prepended with typo3-card- the rest of the id is the
 * be module name as passed it is normally in TYPO3
 * Cards shouldn't be simple iframes for performance reasons
 */

TYPO3.Viewport.ContentCards = {
		// Add a card to either the config or if already rendered to the wrapper
	addContentCard: function(name,config) {
		config.id = 'typo3-card-' + name;
		if (Ext.ready) {
			Ext.getCmp('typo3-contentContainerWrapper').add(config);
		} else {
			this.cards.push(config);
		}
	},
	cards: [
			// add the old card to be compatible
		{
			id: 'typo3-contentContainer',
			border: false,
			xtype: 'backendContentIframePanel',
			name: 'content'
		}
	]
};

/**
 * The backend viewport configuration
 */
TYPO3.Viewport.configuration = {
	layout: 'border',
	id: 'typo3-viewport',
	renderTo: Ext.getBody(),
	border: false,
	items: [
		{
			layout: 'absolute',
			region: 'north',
			id: 'typo3-topbar',
			height: 45,
			contentEl: 'typo3-top-container',
			border: false
		},
		{
			layout: 'fit',
			region: 'west',
			id: 'typo3-module-menu',
			contentEl: 'typo3-menu',
			collapsible: false,
			collapseMode: null,
			floatable: true,
			minWidth: 50,
			maxWidth: 250,
			hideCollapseTool: true,
			split: true,
			useSplitTips: true,
			splitTip: top.TYPO3.LLL.viewPort.tooltipModuleMenuSplit,
			enableChildSplit: true,
			border: false,
			autoScroll: true,
			listeners: {
				resize: function(cmp, adjWidth, adjHeight, rawWidth, rawHeight) {
					var containerWidth = adjWidth,
						moduleMenuWidth = document.getElementById('typo3-menu').clientWidth,
						moduleMenuMinWidth = 100,
						moduleMenuSnappedWidth = 50,
						moduleMenuSnappingClass = 'typo3-module-menu-snapped',
						forceSnapMode = (containerWidth <= moduleMenuMinWidth);
					if (forceSnapMode){
						cmp.addClass(moduleMenuSnappingClass);
						snappedWidth =  moduleMenuSnappedWidth + containerWidth - moduleMenuWidth;
						cmp.setWidth(snappedWidth);
						if(snappedWidth !== containerWidth && TYPO3.Backend){
							TYPO3.Backend.syncSize();
						}
					} else{
						this.removeClass(moduleMenuSnappingClass);
					}
				}
			}
		},
		{
			region: 'center',
			layout: 'border',
			border: false,
			items: [
				{
					region: 'west',
					layout: 'fit',
					id: 'typo3-navigationContainer',
					width: 300,
					minWidth: 250,
					maxWidth: 500,
					floatable: true,
					animCollapse: false,
					split: true,
					enableChildSplit: true,
					collapsible: true,
					collapseMode: 'mini',
					useSplitTips: true,
					collapsibleSplitTip: top.TYPO3.LLL.viewPort.tooltipNavigationContainerSplitDrag,
					collapsibleSplitClickTip: top.TYPO3.LLL.viewPort.tooltipNavigationContainerSplitClick,
					hideCollapseTool: true,
					hidden: true,
					border: false,
					name: 'navigation',
					autoScroll: true,
					items: [
						{
							id: 'typo3-navigationIframe',
							border: false,
							hidden: true,
							xtype: 'iframePanel',
							name: 'navigation'
						}
					]
				},
				{
					id: 'typo3-contentContainerWrapper',
					name: 'content',
					border: false,
					xtype: 'panel',
					region: 'center',
					layout: 'card',
					items: TYPO3.Viewport.ContentCards.cards
				}
			]
		}
	]
};
