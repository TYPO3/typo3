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

/**
 * TYPO3 Page Tree Panel
 */

Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.Tree = Ext.extend(Ext.tree.TreePanel, {
	id: 'typo3-pagetree-tree',
	border: false,

	enableDD: true,
	dragConfig: {
		ddGroup: 'TreeDD'
	},

	t3ContextNode: null,
	t3ContextInfo: {
		inCopyMode: false,
		inCutMode: false
	},

	rootVisible: false,
	pageTree: null,
	contextMenuConfiguration: null,

	initComponent: function() {
		this.contextMenu = new TYPO3.Components.PageTree.ContextMenu({});

		this.root = new Ext.tree.AsyncTreeNode({
			expanded: true,
			id: 'root'
		});

		this.loader = new Ext.tree.TreeLoader({
			directFn: this.pageTree.dataProvider.getNextTreeLevel,
			paramOrder: 'attributes',

			baseAttrs: {
				uiProvider: 't3'
			},

			uiProviders: {
				t3: TYPO3.Components.PageTree.PageTreeUI,
				rootNodeProvider: Ext.tree.TreeNodeUI
			},
			
			createNode: function(attr) {
				if (attr.id == 0) {
					attr.id = 'siteRootNode';
				}

				return Ext.tree.TreeLoader.prototype.createNode.call(this, attr);
			},

			listeners: {
				beforeload: function(treeLoader, node) {
					treeLoader.baseParams.attributes = node.attributes;
				},

				load: {
					scope: this,
					fn: function(treeLoader, node) {
						this.restoreState(node.getPath());
					}
				}
			}
		});

		TYPO3.Components.PageTree.Tree.superclass.initComponent.apply(this, arguments);
	},

	// shows the context menu and creates it if it's not already done
	openContextMenu: function(node, event) {
		node.select();

		var attributes = { t3ContextInfo: node.ownerTree.t3ContextInfo };
		attributes = Ext.apply(node.attributes.nodeData, attributes);

		this.pageTree.contextMenuDataProvider.getActionsForNodeArray(
			attributes,
			function(result) {
				var contextMenu = node.getOwnerTree().contextMenu;
				contextMenu.removeAll();

				var numberOfElementsInside = contextMenu.fillWithMenuItems(node, this.pageTree, result);
				if (numberOfElementsInside > 0) {
					contextMenu.showAt(event.getXY());
				}
			},
			this
		);
	},

	refreshTree: function() {
		this.getLoader().load(this.root);
	},

	refreshNode: function(node) {
		this.getLoader().load(node);
	},

	listeners: {
		// this event triggers the context menu
		contextmenu: {
			scope: this,
			fn: function(node, event) {
				node.getOwnerTree().openContextMenu(node, event);
			}
		},

			// calls a given single click callback for the tree
		click: {
			fn: function(node, event) {
				if (this.clicksRegistered === 2) {
					this.clicksRegistered = 0;
					event.stopEvent();
					return false;
				}

				this.clicksRegistered = 0;
				this.pageTree.commandProvider.singleClick(node);
			},
			delay: 400
		},

			// needed or the label edit will never work
		beforedblclick: {
			fn: function() {
				return false;
			}
		},

		beforeclick: {
			fn: function(node, event) {
				if (!this.clicksRegistered && this.getSelectionModel().isSelected(node)) {
					node.fireEvent('click', node, event);
					++this.clicksRegistered;
					return false;
				}
				++this.clicksRegistered;
			}
		}
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.Tree', TYPO3.Components.PageTree.Tree);