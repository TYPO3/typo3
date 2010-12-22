/***************************************************************
*  Copyright notice
*
*  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.App
 *
 * Page tree main application that controls setups the components
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.Panel
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.App = Ext.extend(Ext.Panel, {
	/**
	 * Panel id
	 *
	 * @type {String}
	 */
	id: 'typo3-pagetree',

	/**
	 * Border
	 *
	 * @type {Boolean}
	 */
	border: false,

	/**
	 * Layout Type
	 *
	 * @type {String}
	 */
	layout: 'anchor',

	/**
	 * Listeners
	 *
	 * The afterlayout wizard relayoutes the navigation container to fix some nasty
	 * scrollbar issues.
	 *
	 * @type {Object}
	 */
	listeners: {
		afterlayout: {
			fn: function() {
				this.ownerCt.doLayout();
			},
			buffer: 250
		}
	},

	/**
	 * Initializes the application
	 *
	 * Set's the necessary language labels, configuration options and sprite icons by an
	 * external call and initializes the needed components.
	 *
	 * @return {void}
	 */
	initComponent: function() {
		TYPO3.Components.PageTree.DataProvider.loadResources(function(response) {
			TYPO3.Components.PageTree.LLL = response['LLL'];
			TYPO3.Components.PageTree.Configuration = response['Configuration'];
			TYPO3.Components.PageTree.Sprites = response['Sprites'];

			var tree = new TYPO3.Components.PageTree.Tree({
				id: this.id + '-tree',
				ddGroup: this.id,
				stateful: true,
				stateId: 'Pagetree' + TYPO3.Components.PageTree.Configuration.temporaryMountPoint,
				stateEvents: [],
				autoScroll: true,
				plugins: new Ext.ux.state.TreePanel(),
				commandProvider: TYPO3.Components.PageTree.Actions,
				contextMenuProvider: TYPO3.Components.PageTree.ContextMenuDataProvider,
				treeDataProvider: TYPO3.Components.PageTree.DataProvider
			});

			var filteringTree = new TYPO3.Components.PageTree.FilteringTree({
				id: this.id + '-filteringTree',
				ddGroup: this.id,
				autoScroll: true,
				commandProvider: TYPO3.Components.PageTree.Actions,
				contextMenuProvider: TYPO3.Components.PageTree.ContextMenuDataProvider,
				treeDataProvider: TYPO3.Components.PageTree.DataProvider
			});

			var topPanel = new TYPO3.Components.PageTree.TopPanel({
				dataProvider: TYPO3.Components.PageTree.DataProvider,
				filteringTree: filteringTree,
				ddGroup: this.id,
				tree: tree
			});

			var deletionDropZone = new TYPO3.Components.PageTree.DeletionDropZone({
				commandProvider: TYPO3.Components.PageTree.Actions,
				ddGroup: this.id,
				tree: tree
			});

			this.add(
				topPanel, {
					border: false,
					id: this.id + '-indicatorBar'
				}, {
					border: false,
					id: this.id + '-treeContainer',
					items: [tree, filteringTree]
				},
				deletionDropZone
			);

			if (TYPO3.Components.PageTree.Configuration.temporaryMountPoint) {
				this.addTemporaryMountPointIndicator();
			}

			if (TYPO3.Components.PageTree.Configuration.indicator !== '') {
				this.addIndicatorItems();
			}
		}, this);

		TYPO3.Components.PageTree.App.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Adds the default indicator items
	 *
	 * @return {void}
	 */
	addIndicatorItems: function() {
		this.addIndicator({
			border: false,
			id: this.id + '-indicatorBar-indicatorTitle',
			cls: this.id + '-indicatorBar-item',
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
			border: false,
			id: this.id + '-indicatorBar-temporaryMountPoint',
			cls: this.id + '-indicatorBar-item',
			html: '<p>' +
					'<span id="' + this.id + '-indicatorBar-temporaryMountPoint-info' + '" ' +
						'class="' + this.id + '-indicatorBar-item-leftIcon ' +
							TYPO3.Components.PageTree.Sprites.Info + '">' + '&nbsp;' +
					'</span>' +
					'<span id="' + this.id + '-indicatorBar-temporaryMountPoint-clear' + '" ' +
						'class="' + this.id + '-indicatorBar-item-rightIcon ' +
							TYPO3.Components.PageTree.Sprites.InputClear + '">' + '&nbsp;' +
					'</span>' +
					TYPO3.Components.PageTree.LLL.temporaryMountPointIndicatorInfo + '<br />' +
						TYPO3.Components.PageTree.Configuration.temporaryMountPoint +
				'</p>'
		});

		this.temporaryMountPointInfoIndicator.on('afterrender', function() {
			var element = Ext.fly(this.id + '-indicatorBar-temporaryMountPoint-clear');
			element.on('click', function() {
				TYPO3.BackendUserSettings.ExtDirect.unsetKey(
					'pageTree_temporaryMountPoint',
					function() {
						this.removeIndicator(this.temporaryMountPointInfoIndicator);
						this.getTree().refreshTree();
						this.getTree().stateId = 'Pagetree';
					},
					this
				);
			}, this);
		}, this);
	},

	/**
	 * Adds an indicator item
	 *
	 * @param {Ext.Component} component
	 * @return {void}
	 */
	addIndicator: function(component) {
		return Ext.getCmp(this.id + '-indicatorBar').add(component);
	},

	/**
	 * Removes an indicator item from the indicator bar
	 *
	 * @param {Ext.Component} component
	 * @return {void}
	 */
	removeIndicator: function(component) {
		Ext.getCmp(this.id + '-indicatorBar').remove(component);
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
			this.select(fsMod.recentIds['web'], true);
		}

		TYPO3.Components.PageTree.DataProvider.getIndicators(function(response) {
			this.removeIndicator(Ext.getCmp(this.id + '-indicatorBar-indicatorTitle'));
			TYPO3.Components.PageTree.Configuration.indicator = response;
			this.addIndicatorItems();
		}, this);

		this.items.items[0].activeTree.refreshTree();
	},

	/**
	 * Returns the current active tree
	 *
	 * @return {TYPO3.Components.PageTree.Tree}
	 */
	getTree: function() {
		return this.items.items[0].activeTree;
	},

	/**
	 * Selects a node defined by the page id. If the second parameter is set, we
	 * store the new location into the state hash.
	 *
	 * @param {int} pageId
	 * @param {Boolean} saveState
	 * @return {Boolean}
	 */
	select: function(pageId, saveState) {
		if (saveState !== false) {
			saveState = true;
		}

		var tree = this.getTree();
		var succeeded = false;
		var node = tree.getRootNode().findChild('realId', pageId, true);
		if (node) {
			succeeded = true;
			tree.selectPath(node.getPath());
			if (!!saveState) {
				tree.stateHash['lastSelectedNode'] = node.id;
			}
		}

		return succeeded;
	},

	/**
	 * Returns the currently selected node
	 *
	 * @return {Ext.tree.TreeNode}
	 */
	getSelected: function() {
		var node = this.getTree().getSelectionModel().getSelectedNode();
		return node ? node : null;
	}
});

/**
 * Callback method for the module menu
 *
 * @return {TYPO3.Components.PageTree.App}
 */
TYPO3.ModuleMenu.App.registerNavigationComponent('typo3-pagetree', function() {
	TYPO3.Backend.NavigationContainer.PageTree = new TYPO3.Components.PageTree.App();

		// compatibility code
    top.nav = TYPO3.Backend.NavigationContainer.PageTree;
    top.nav_frame = TYPO3.Backend.NavigationContainer.PageTree;
    top.content.nav_frame = TYPO3.Backend.NavigationContainer.PageTree;

	return TYPO3.Backend.NavigationContainer.PageTree;
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.App', TYPO3.Components.PageTree.App);
