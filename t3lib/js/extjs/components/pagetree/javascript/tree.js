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
 * @class TYPO3.Components.PageTree.Tree
 *
 * Generic Tree Panel
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.tree.Panel
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
Ext.define('TYPO3.Components.PageTree.Model', {
	extend: 'Ext.data.Model',
	fields: [{
		name: 'id',
		type: 'string',
		defaultValue: 'root'
	},{
		name: 'realId',
		type: 'string'
	},{
		name: 'text',
		type: 'string'
	},{
		name: 'depth',
		type: 'int'
	},{
		name: 'root',
		type: 'boolean'
	},{
		name: 'leaf',
		type: 'boolean'
	},{
		name: 'parentId',
		type: 'string'
	},{
		name: 'isFirst',
		type: 'boolean'
	},{
		name: 'index',
		type: 'int'
	},{
		name: 'isLast',
		type: 'boolean'
	},{
		name: 'isExpandable',
		type: 'boolean'
	},{
		name: 'isInsertedNode',
		type: 'boolean'
	},{
		name: 'nodeData',
		type: 'object'
	}],
	hasMany: {
		name: 'children',
		associationKey: 'children',
		model: 'TYPO3.Components.PageTree.Model'
	},
		// Set method for nodeData fields
	setNodeData: function (field, value) {
		var nodeData = this.get('nodeData');
		nodeData[field] = value;
		this.set('nodeData', Ext.merge(this.get('nodeData'), nodeData));
	},
		// Get method for nodeData fields
	getNodeData: function (field) {
		return this.get('nodeData')[field];
	}
});
Ext.define('TYPO3.Components.PageTree.Tree', {
	extend: 'Ext.tree.Panel',
	/**
	 * Use extended stateful mixin
	 *
	 * @type {Object}
	 */
	mixins: {
		state: 'Ext.ux.state.TreePanel'
	},

	/**
	 * View configuration
	 *
	 * @type {Object}
	 */
	viewConfig: {
		autoScroll: false,
		border: false,
		toggleOnDblClick: false
	},

	/**
	 * Columns
	 *
	 * @type {TYPO3.Components.PageTree.Column}[]
	 */
	columns: [{
	 		xtype: 'pagetreecolumn',
	 	 	dataIndex: 'text',
	 	 	flex: 1,
	 	 	editor: {
	 	 		xtype: 'textfield',
	 	 	 	allowBlank: false
	 	 	}
	}],

	/**
	 * Header
	 *
	 * @type {Boolean}
	 */
	hideHeaders: true,
	preventHeader: true,

	/**
	 * Border
	 *
	 * @type {Boolean}
	 */
	autoScroll: false,
	border: false,

	/**
	 * Body css
	 *
	 * @type {String}
	 */
	bodyCls: 'typo3-pagetree',

	/**
	 * Indicates if the root node is visible
	 *
	 * @type {Boolean}
	 */
	rootVisible: false,

	/**
	 * Currently Selected Node
	 *
	 * @type {TYPO3.Components.PageTree.Model}
	 */
	currentSelectedNode: null,

	/**
	 * Enable the drag and drop feature
	 *
	 * @cfg {Boolean}
	 */
	enableDD: true,

	/**
	 * Drag and Drop Group
	 *
	 * @cfg {String}
	 */
	ddGroup: '',

	/**
	 * Id of deletionDropZone
	 *
	 * @cfg {String}
	 */
	deletionDropZoneId: '',

	/**
	 * Indicates if the label should be editable
	 *
	 * @cfg {Boolean}
	 */
	labelEdit: true,

	/**
	 * Data Provider
	 *
	 * @cfg {Object}
	 */
	treeDataProvider: null,

	/**
	 * Command Provider
	 *
	 * @cfg {Object}
	 */
	commandProvider: null,

	/**
	 * Context menu provider
	 *
	 * @cfg {Object}
	 */
	contextMenuProvider: null,

	/**
	 * Main applicaton
	 *
	 * @cfg {TYPO3.Components.PageTree.App}
	 */
	app: null,

	/**
	 * Page Tree Store
	 *
	 * @type {Object}
	 */
	store: null,

	/**
	 * Root Node Configuration
	 *
	 * @type {Object}
	 */
	rootNodeConfig: {
		id: 'root',
		expanded: true,
		nodeData: {
			id: 'root'
		}
	},

	/**
	 * Context Node
	 *
	 * @type {TYPO3.Components.PageTree.Model}
	 */
	t3ContextNode: null,

	/**
	 * Context Information
	 *
	 * @type {Object}
	 */
	 t3ContextInfo: {
		inCopyMode: false,
		inCutMode: false
	},

	/**
	 * Number of clicks to ignore for the label edit on dblclick feature
	 * Will be set to 2 by the tree editor
	 *
	 * @type {int}
	 */
	inhibitClicks: 0,

	/**
	 * Constructor
	 * Plugins are built by the parent constructor
	 *
	 * @param {Object} config
	 * @return {void}
	 */
	constructor: function (config) {
			// Inline label editing feature
		this.labelEdit = config.labelEdit || this.labelEdit;
		if (this.labelEdit ) {
			var plugins = config.plugins || [];
			config.plugins = plugins.concat(
				Ext.create('TYPO3.Components.PageTree.TreeEditor', {
					clicksToEdit: 2,
					pluginId: 'treeEditor'
				})
			);
		}
			// Drag & drop feature
		if (this.enableDD) {
			config.viewConfig = Ext.applyIf(config.viewConfig || {}, this.viewConfig);
			var plugins = config.viewConfig.plugins || [];
			config.viewConfig.plugins = plugins.concat(
				Ext.create('TYPO3.Components.PageTree.plugin.TreeViewDragDrop', {
					ddGroup: config.ddGroup,
					pluginId: 'treeViewDragDrop'
				})
			);
			config.viewConfig.allowCopy = true;
		}
			// Call parent constructor
		this.callParent([config]);
	},

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function () {
			// Add the tree store
		this.addStore();
			// Add single click handler that only triggers after a delay to let the double click event
			// a possibility to be executed (needed for label edit)
		this.addListener('itemclick', this.onItemSingleClick, null, { delay: 400 });
			// Init component
		this.callParent();
			// Drag & drop feature
		if (this.enableDD) {
			this.getView().addListener('afterrender', this.enableDragAndDrop, this);
		}
			// Context menu feature
		if (this.contextMenuProvider) {
			this.enableContextMenu();
		}
	},
	 
	/**
	 * Adds the store to the tree
	 *
	 * @return {void}
	 */
	addStore: function () {
		this.store = Ext.data.StoreManager.lookup(this.getId() + 'PageTreeStore');
		if (!this.store) {
			this.store = Ext.create('Ext.data.TreeStore', {
				clearOnLoad: false,
				listeners: {
						// Remove nodes and add params to read operation
					beforeload: {
						fn: function (store, operation, options) {
							if (operation.node) {
								var node = operation.node;
								node.removeAll();
								node.commit();
								operation.params = {
									nodeId: node.getNodeData('id'),
									nodeData: node.get('nodeData')
									
								};
							}
						}
					},
						// Restore state on initial load
					load: {
						fn: function (store, node, records, successful) {
							if (successful) {
								this.restoreState();
							}
						},
						scope: this
					}
				},
				model: 'TYPO3.Components.PageTree.Model',
				nodeParam: 'nodeId',
				proxy: {
					type: 'direct',
					directFn: this.treeDataProvider.getNextTreeLevel,
					paramOrder: ['nodeId', 'nodeData'],
					reader: {
					    type: 'json'
					}
				},
				root: this.rootNodeConfig,
				storeId: this.getId() + 'PageTreeStore'
			});
		}
	},

	/**
	 * Refreshes the tree
	 *
	 * @param {Function} callback
	 * @param {Object} scope
	 * return {void}
	 */
	refreshTree: function (callback, scope) {
			// Remove readable rootline elements while refreshing
		if (!this.store.isLoading()) {
			var rootlineElements = Ext.select('.x-tree-node-readableRootline');
			if (rootlineElements) {
				rootlineElements.each(function(element) {
					element.remove();
				});
			}
		}
		this.refreshNode(this.getRootNode(), callback, scope);
	},

	/**
	 * Refreshes a given node
	 *
	 * @param {TYPO3.Components.PageTree.Model} node
	 * @param {Function} callback
	 * @param {Object} scope
	 * return {void}
	 */
	refreshNode: function (node, callback, scope) {
		this.store.load({
			node: node,
			callback: callback || Ext.emptyFn,
			scope: scope || this
		});
	},

	/**
	 * Handles singe click on tree item
	 *
	 * return {Boolean}
	 */
	onItemSingleClick: function (view, node, item, index, event) {
		var tree = view.panel;
			// Check if the tree editor was triggered by dblclick
			// If so, stop the next two clicks
		if (tree.inhibitClicks) {
			--tree.inhibitClicks;
			event.stopEvent();
			return false;
		}

		if (tree.commandProvider.singleClick) {
			tree.commandProvider.singleClick(node, tree);
		}
			// Fire the context menu on a single click on the node icon (Beware of drag&drop!)
		if (!TYPO3.Components.PageTree.Configuration.disableIconLinkToContextmenu
			|| TYPO3.Components.PageTree.Configuration.disableIconLinkToContextmenu === '0'
		) {
			var target = event.getTarget('span.t3-icon-apps-pagetree');
			if (target) {
				view.fireEvent('itemcontextmenu', view, node, item, index, event);
				event.stopEvent();
			}
		}
		return true;
	},

	/**
	 * Enables the context menu feature
	 *
	 * return {void}
	 */
	enableContextMenu: function() {
		this.contextMenu = Ext.create('TYPO3.Components.PageTree.ContextMenu', { pageTree: this });
		this.getView().on('itemcontextmenu', function (view, node, item, index, event) {
			view.panel.openContextMenu(view, node, item, index, event);
		});
	},

	/**
	 * Open a context menu for the given node
	 *
	 * @param {TYPO3.Components.PageTree.Model} node
	 * @param {Ext.EventObject} event
	 * return {void}
	 */
	openContextMenu: function(view, node, item, index, event) {
		var tree = view.panel;
		node.setNodeData('t3ContextInfo', tree.t3ContextInfo);
		tree.contextMenuProvider.getActionsForNodeArray(
			node.get('nodeData'),
			function (configuration) {
				tree.contextMenu.removeAll();
				tree.contextMenu.fill(node, tree, configuration);
				if (tree.contextMenu.items.length) {
					tree.contextMenu.showAt(event.getXY());
				}
			}
		);
		event.stopEvent();
	},

	/**
	 * Enables the drag and drop feature
	 *
	 * return {void}
	 */
	enableDragAndDrop: function() {
		var view = this.getView();
		var dragZone = view.getPlugin('treeViewDragDrop').dragZone;

			// Show drop zone before drag, otherwise the proxy is never notified
		dragZone.onBeforeDrag = Ext.Function.bind(this.startDeletionDropZone, view);
			// Hide the drop zone after the drag completes
		dragZone.onMouseUp = Ext.Function.bind(this.stopDeletionDropZone, view);
		dragZone.endDrag = Ext.Function.bind(this.stopDeletionDropZone, view);
		dragZone.afterInvalidDrop = Ext.Function.bind(this.stopDeletionDropZone, view, [true]);

			// Node is moved
		this.on('itemmove', this.moveNode, this);

			// New node is created/copied
		view.on('beforedrop', this.beforeDropNode, this);
		view.on('drop', this.dropNode, this);
	},

	/**
	 * Enables the deletion drop zone if configured
	 *
	 * @return {void}
	 */
	startDeletionDropZone: function (dragData, event) {
		var view = dragData.view,
			tree = view.panel,
			node = view.getRecord(dragData.item),
			nodeHasChildNodes = (node.hasChildNodes() || node.isExpandable());
		var tree = this.panel;
		if (tree.deletionDropZoneId &&
			(!nodeHasChildNodes ||
			(nodeHasChildNodes && TYPO3.Components.PageTree.Configuration.canDeleteRecursivly)
		)) {
			Ext.getCmp(tree.deletionDropZoneId).show();
		}
	},

	/**
	 * Disables the deletion drop zone if configured
	 *
	 * @return {void}
	 */
	stopDeletionDropZone: function (forceStop) {
		var tree = this.panel;
		if (tree.deletionDropZoneId && (!this.getPlugin('treeViewDragDrop').dragZone.dragging || forceStop)) {
			Ext.getCmp(tree.deletionDropZoneId).hide();
		}
	},

	/**
	 * Creates a place holder node when a new node is about to be dropped
	 *
	 * @param {HTMLElement node} node
	 * @param {object} dragData
	 * @param {TYPO3.Components.PageTree.Model} overNode
	 * @param {string} dropPosition
	 * @return {boolean}
	 */
	beforeDropNode: function (node, dragData, overNode, dropPosition) {
		if (dragData && dragData.item && dragData.item.shouldCreateNewNode) {
				// Inserting a new node of the type that was selected in the top panel
			this.t3ContextInfo.serverNodeType = dragData.item.nodeType;
			dragData.dropNode = Ext.create('TYPO3.Components.PageTree.Model', {
				text: TYPO3.Components.PageTree.LLL.fakeNodeHint,
				leaf: true,
				isInsertedNode: true
			});
			dragData.records = [dragData.dropNode];
		}
		return true;
	},

	/**
	 * Handle the copy and insert events
	 *
	 * @param {HTMLElement node} node
	 * @param {object} dragData
	 * @param {TYPO3.Components.PageTree.Model} overNode
	 * @param {string} dropPosition
	 * return {void}
	 */
	dropNode: function (node, dragData, overNode, dropPosition) {
		if (dragData.dropNode) {
			if (dragData.dropNode.get('isInsertedNode')) {
				dragData.dropNode.set('isInsertedNode', false);
				this.insertNode(dragData.dropNode);
			}
		} else if (dragData.copy) {
			this.copyNode(dragData.records[0]);
		}
	},

	/**
	 * Moves a node
	 *
	 * @param {TYPO3.Components.PageTree.Model} movedNode
	 * @param {TYPO3.Components.PageTree.Model} oldParent
	 * @param {TYPO3.Components.PageTree.Model} newParent
	 * @param {int} position
	 * return {void}
	 */
	moveNode: function (movedNode, oldParent, newParent, position) {
		this.t3ContextNode = movedNode;
		if (position === 0) {
			this.commandProvider.moveNodeToFirstChildOfDestination(newParent, this);
		} else {
			var previousSiblingNode = newParent.childNodes[position - 1];
			this.commandProvider.moveNodeAfterDestination(previousSiblingNode, this);
		}
	},

	/**
	 * Inserts a node
	 *
	 * @param {TYPO3.Components.PageTree.Model} node
	 * return {void}
	 */
	insertNode: function (node) {
		this.t3ContextNode = node.parentNode;
		if (node.previousSibling) {
			this.commandProvider.insertNodeAfterDestination(node, this);
		} else {
			this.commandProvider.insertNodeToFirstChildOfDestination(node, this);
		}
	},

	/**
	 * Copies a node
	 *
	 * @param {TYPO3.Components.PageTree.Model} movedNode
	 * return {void}
	 */
	copyNode: function (node) {
		this.t3ContextNode = node;
		if (node.previousSibling) {
			this.commandProvider.copyNodeAfterDestination(node, this);
		} else {
			this.commandProvider.copyNodeToFirstChildOfDestination(node, this);
		}
	}
});
