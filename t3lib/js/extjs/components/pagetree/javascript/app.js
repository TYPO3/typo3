/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stefan Galinski <stefan.galinski@gmail.com>
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
/**
 * @class TYPO3.Components.PageTree.App
 *
 * Page tree main application that controls setups the components
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.panel.Panel
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
Ext.define('TYPO3.Components.PageTree.App', {
	extend: 'Ext.panel.Panel',

	/**
	 * Panel id
	 *
	 * @type {String}
	 */
	id: 'typo3-pagetree',

	/**
	 * Border
	 *
	 * @type {String}
	 */
	border: false,

	/**
	 * Layout Type
	 *
	 * @type {String}
	 */
	layout: 'border',

	/**
	 * Components defaults
	 *
	 * @type {Object}
	 */
	 defaults: {
	 	 border: false,
	 },
	
	/**
	 * Tree containe
	 *
	 * @type {String}
	 */
	 items: [{
			itemId: 'topPanelItems',
			height: 49,
			region: 'north',
			cls: 'typo3-pageTree-topPanelItems'
	 	},{
			itemId: 'treeContainer',
			autoScroll: false,
			layout: 'card',
			region: 'center'
		},{
			xtype: 'container',
			itemId: 'deletionDropZoneContainer',
			cls: 'typo3-pageTree-deletionDropZoneContainer',
			region: 'south'
	 }],

	/**
	 * Initializes the application
	 *
	 * Set's the necessary language labels, configuration options and sprite icons by an
	 * external call and initializes the needed components.
	 *
	 * @return {void}
	 */
	initComponent: function() {
		this.addListener('render', function () {
			TYPO3.Components.PageTree.DataProvider.loadResources(function(response) {
				TYPO3.Components.PageTree.LLL = response['LLL'];
				TYPO3.Components.PageTree.Configuration = response['Configuration'];
				TYPO3.Components.PageTree.Sprites = response['Sprites'];
	
				var tree = Ext.create('TYPO3.Components.PageTree.Tree', {
					id: this.getId() + '-tree',
					deletionDropZoneId: this.getId() + '-deletionDropZone',
					ddGroup: this.getId(),
					stateful: true,
					stateId: 'Pagetree' + TYPO3.Components.PageTree.Configuration.temporaryMountPoint,
					commandProvider: TYPO3.Components.PageTree.Actions,
					contextMenuProvider: TYPO3.Components.PageTree.ContextMenuDataProvider,
					treeDataProvider: TYPO3.Components.PageTree.DataProvider,
					app: this
				});
	
				var filteringTree = Ext.create('TYPO3.Components.PageTree.FilteringTree', {
					id: this.getId() + '-filteringTree',
					deletionDropZoneId: this.getId() + '-deletionDropZone',
					ddGroup: this.getId(),
					commandProvider: TYPO3.Components.PageTree.Actions,
					contextMenuProvider: TYPO3.Components.PageTree.ContextMenuDataProvider,
					stateful: false,
					treeDataProvider: TYPO3.Components.PageTree.DataProvider,
					app: this
				});
	
				var topPanelItems = this.getComponent('topPanelItems');
				topPanelItems.add([
						Ext.create('TYPO3.Components.PageTree.TopPanel', {
							border: false,
							dataProvider: TYPO3.Components.PageTree.DataProvider,
							filteringTree: filteringTree,
							ddGroup: this.getId(),
							height: 49,
							itemId: 'topPanel',
							tree: tree,
							app: this
						}),
						{
							xtype: 'container',
							border: false,
							defaultType: 'component',
							id: this.getId() + '-indicatorBar',
							itemId: 'indicatorBar'
						}
				]);
	
				var treeContainer = this.getComponent('treeContainer');
				treeContainer.add([tree, filteringTree]);
				treeContainer.addListener(
					'render',
					function (panel) { panel.getLayout().setActiveItem(0); }
				);
	
				var deletionDropZoneContainer = this.getComponent('deletionDropZoneContainer');
				deletionDropZoneContainer.add({
					xtype: 'typo3deletiondropzone',
					height: 35,
					id: this.getId() + '-deletionDropZone',
					commandProvider: TYPO3.Components.PageTree.Actions,
					ddGroup: this.getId(),
					layout: 'anchor',
					app: this
				});

				if (TYPO3.Components.PageTree.Configuration.temporaryMountPoint) {
					this.addTemporaryMountPointIndicator();
				}
	
				if (TYPO3.Components.PageTree.Configuration.indicator !== '') {
					this.addIndicatorItems();
				}
				this.doLayout();
	
				this.ownerCt.on('resize', function() {
					this.doLayout();
				}, this);
			}, this);
		}, this);
		this.callParent();
	},

	/**
	 * Adds the default indicator items
	 *
	 * @return {void}
	 */
	addIndicatorItems: function() {
		this.addIndicator({
			id: this.getId() + '-indicatorBar-indicatorTitle',
			cls: this.getId() + '-indicatorBar-item',
			html: TYPO3.Components.PageTree.Configuration.indicator
		});
	},

	/**
	 * Adds the temporary mount point indicator item
	 *
	 * @return {void}
	 */
	addTemporaryMountPointIndicator: function() {
		this.temporaryMountPointInfoIndicator = this.addIndicator({
			id: this.getId() + '-indicatorBar-temporaryMountPoint',
			cls: this.getId() + '-indicatorBar-item',

			listeners: {
				afterrender: {
					fn: function() {
						var element = Ext.get(this.getId() + '-indicatorBar-temporaryMountPoint-clear');
						element.on('click', function() {
							TYPO3.BackendUserSettings.ExtDirect.unsetKey(
								'pageTree_temporaryMountPoint',
								function() {
									TYPO3.Components.PageTree.Configuration.temporaryMountPoint = null;
									this.removeIndicator(this.temporaryMountPointInfoIndicator);
									this.getTree().refreshTree();
									this.getTree().stateId = 'Pagetree';
								},
								this
							);
						}, this);
					},
					scope: this
				}
			},
			renderData: {
				appId: this.getId(),
				spriteIconCls: TYPO3.Components.PageTree.Sprites.Info,
				label: TYPO3.Components.PageTree.LLL.temporaryMountPointIndicatorInfo,
				mountPoint: TYPO3.Components.PageTree.Configuration.temporaryMountPoint
			},
			renderTpl: Ext.create('Ext.XTemplate',
				'<p>',
				'<span id="{appId}-indicatorBar-temporaryMountPoint-info" class="{appId}-indicatorBar-item-leftIcon {spriteIconCls}">&nbsp;</span>',
				'{label}',
				'<span id="{appId}-indicatorBar-temporaryMountPoint-clear" class="{appId}-indicatorBar-item-rightIcon t3-icon t3-icon-actions t3-icon-actions-input t3-icon-input-clear t3-tceforms-input-clearer">&nbsp;</span>',
				'</p>',
				'<p>{mountPoint}',
				'</p>'
			)
		});
	},

	/**
	 * Adds an indicator item
	 *
	 * @param {Object} component
	 * @return {void}
	 */
	addIndicator: function(component) {
		if (component.listeners && component.listeners.afterrender) {
			component.listeners.afterrender.fn = Ext.Function.createSequence(component.listeners.afterrender.fn, this.afterTopPanelItemAdded, this);
		} else {
			if (!component.listeners) {
				component.listeners = {};
			}
			component.listeners.afterrender = {
				scope: this,
				fn: this.afterTopPanelItemAdded
			}
		}
		var indicatorBar = this.down('component[itemId=indicatorBar]');
		var indicator = indicatorBar.add(component);
		return indicator;
	},

	/**
	 * Recalculates the top panel items height after an indicator was added
	 *
	 * @param {Ext.Component} component
	 * @return {void}
	 */
	afterTopPanelItemAdded: function(component) {
		var topPanel = this.down('component[itemId=topPanel]');
		var indicatorBar = this.down('component[itemId=indicatorBar]');
		var height = 0;
		indicatorBar.items.each(function (item) {
			height += item.getHeight() + 3;
		});
		indicatorBar.setHeight(height);
		topPanelItems = this.down('component[itemId=topPanelItems]');
		topPanelItems.setHeight(topPanel.getHeight() + indicatorBar.getHeight());
		this.doLayout();
	},

	/**
	 * Removes an indicator item from the indicator bar
	 *
	 * @param {Ext.Component} component
	 * @return {void}
	 */
	removeIndicator: function(component) {
		var topPanelItems = this.down('component[itemId=topPanelItems]');
		topPanelItems.setHeight(topPanelItems.getHeight() - component.getHeight() - 3);
		Ext.getCmp(this.getId() + '-indicatorBar').remove(component);
		this.doLayout();
	},

	/**
	 * Compatibility method that calls refreshTree()
	 *
	 * @return {void}
	 */
	refresh: function() {
		this.refreshTree();
	},

	/**
	 * Another compatibility method that calls refreshTree()
	 *
	 * @return {void}
	 */
	refresh_nav: function() {
		this.refreshTree();
	},

	/**
	 * Refreshes the tree and selects the node defined by fsMod.recentIds['web']
	 *
	 * @return {void}
	 */
	refreshTree: function() {
		if (!isNaN(fsMod.recentIds['web']) && fsMod.recentIds['web'] !== '') {
			this.select(fsMod.recentIds['web']);
		}

		TYPO3.Components.PageTree.DataProvider.getIndicators(function(response) {
			var indicators = Ext.getCmp(this.getId() + '-indicatorBar-indicatorTitle');
			if (indicators) {
				this.removeIndicator(indicators);
			}

			if (response._COUNT > 0) {
				TYPO3.Components.PageTree.Configuration.indicator = response.html;
				this.addIndicatorItems();
			}
		}, this);

		this.getTree().refreshTree();
	},

	/**
	 * Returns the current active tree
	 *
	 * @return {TYPO3.Components.PageTree.Tree}
	 */
	getTree: function() {
		return this.down('component[itemId=treeContainer]').getLayout().getActiveItem();
	},

	/**
	 * Sets the current active tree
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {TYPO3.Components.PageTree.Tree}
	 */
	setTree: function(tree) {
		var layout = this.down('component[itemId=treeContainer]').getLayout();
		layout.setActiveItem(tree);
		return layout.getActiveItem(tree);
	},

	/**
	 * Selects a node defined by the page id.
	 *
	 * @param {int} pageId
	 * @return {Boolean}
	 */
	select: function(pageId) {
		var tree = this.getTree();
		var succeeded = false;
		var node = tree.getRootNode().findChild('realId', pageId, true);
		if (node) {
			succeeded = true;
			tree.selectPath(node.getPath());
		}
		return succeeded;
	},

	/**
	 * Returns the currently selected node
	 *
	 * @return {Ext.data.NodeInterface}
	 */
	getSelected: function() {
		var node = this.getTree().getSelectionModel().getLastSelected();
		return node ? node : null;
	}
});

/**
 * Callback method for the module menu
 *
 * @return {TYPO3.Components.PageTree.App}
 */
TYPO3.ModuleMenu.App.registerNavigationComponent('typo3-pagetree', function() {
	TYPO3.Backend.NavigationContainer.PageTree = Ext.create('TYPO3.Components.PageTree.App');

		// compatibility code
	top.nav = TYPO3.Backend.NavigationContainer.PageTree;
	top.nav_frame = TYPO3.Backend.NavigationContainer.PageTree;
	top.content.nav_frame = TYPO3.Backend.NavigationContainer.PageTree;

	return TYPO3.Backend.NavigationContainer.PageTree;
});
