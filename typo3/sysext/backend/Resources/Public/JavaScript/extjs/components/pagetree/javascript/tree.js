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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.Tree
 *
 * Generic Tree Panel
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.tree.TreePanel
 */
TYPO3.Components.PageTree.Tree = Ext.extend(Ext.tree.TreePanel, {
	/**
	 * Border
	 *
	 * @type {Boolean}
	 */
	border: false,

	/**
	 * Indicates if the root node is visible
	 *
	 * @type {Boolean}
	 */
	rootVisible: false,

	/**
	 * Tree Editor Instance (Inline Edit)
	 *
	 * @type {TYPO3.Components.PageTree.TreeEditor}
	 */
	treeEditor: null,

	/**
	 * Currently Selected Node
	 *
	 * @type {Ext.tree.TreeNode}
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
	 * Indicates if the label should be editable
	 *
	 * @cfg {Boolean}
	 */
	labelEdit: true,

	/**
	 * User Interface Provider
	 *
	 * @cfg {Ext.tree.TreeNodeUI}
	 */
	uiProvider: null,

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
	commandProvider : null,

	/**
	 * Context menu provider
	 *
	 * @cfg {Object}
	 */
	contextMenuProvider: null,

	/**
	 * Id of the deletion drop zone if any
	 *
	 * @cfg {String}
	 */
	deletionDropZoneId: '',

	/**
	 * Main applicaton
	 *
	 * @cfg {TYPO3.Components.PageTree.App}
	 */
	app: null,

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
	 * Indicator if the control key is pressed
	 *
	 * @type {Boolean}
	 */
	isControlPressed: false,

	/**
	 * Context Node
	 *
	 * @type {Ext.tree.TreeNode}
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
	 * Registered clicks for the double click feature
	 *
	 * @type {int}
	 */
	clicksRegistered: 0,

	/**
	 * Indicator if the control key was pressed
	 *
	 * @type {Boolean}
	 */
	controlKeyPressed: false,

	/**
	 * Listeners
	 *
	 * Event handlers that handle click events and synchronizes the label edit,
	 * double click and single click events in a useful way.
	 */
	listeners: {
			// single click handler that only triggers after a delay to let the double click event
			// a possibility to be executed (needed for label edit)
		click: {
			fn: function(node, event) {
				if (this.clicksRegistered === 2) {
					this.clicksRegistered = 0;
					event.stopEvent();
					return false;
				}

				this.clicksRegistered = 0;
				if (this.commandProvider.singleClick) {
					this.commandProvider.singleClick(node, this);
				}
			},
			delay: 400
		},

			// prevent the expanding / collapsing on double click
		beforedblclick: {
			fn: function() {
				return false;
			}
		},

			// prevents label edit on a selected node
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
	},

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function() {
		if (!this.uiProvider) {
			this.uiProvider = TYPO3.Components.PageTree.PageTreeNodeUI;
		}
		Ext.dd.DragDropMgr.useCache = false;
		this.root = new Ext.tree.AsyncTreeNode(this.rootNodeConfig);
		this.addTreeLoader();

		if (this.labelEdit) {
			this.enableInlineEditor();
		}

		if (this.enableDD) {
			this.dragConfig = {ddGroup: this.ddGroup};
			this.enableDragAndDrop();
		}

		if (this.contextMenuProvider) {
			this.enableContextMenu();
		}

		TYPO3.Components.PageTree.Tree.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Refreshes the tree
	 *
	 * @param {Function} callback
	 * @param {Object} scope
	 * return {void}
	 */
	refreshTree: function(callback, scope) {
			// remove readable rootline elements while refreshing
		if (!this.inRefreshingMode) {
			var rootlineElements = Ext.select('.x-tree-node-readableRootline');
			if (rootlineElements) {
				rootlineElements.each(function(element) {
					element.remove();
				});
			}
		}

		this.refreshNode(this.root, callback, scope);
	},

	/**
	 * Refreshes a given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {Function} callback
	 * @param {Object} scope
	 * return {void}
	 */
	refreshNode: function(node, callback, scope) {
		if (this.inRefreshingMode) {
			return;
		}

		scope = scope || node;
		this.inRefreshingMode = true;
		var loadCallback = function(node) {
			node.ownerTree.inRefreshingMode = false;
			if (node.ownerTree.restoreState) {
				node.ownerTree.restoreState(node.getPath());
			}
		};

		if (callback) {
			loadCallback = callback.createSequence(loadCallback);
		}

		this.getLoader().load(node, loadCallback, scope);
	},

	/**
	 * Adds a tree loader implementation that uses the directFn feature
	 *
	 * return {void}
	 */
	addTreeLoader: function() {
		this.loader = new Ext.tree.TreeLoader({
			directFn: this.treeDataProvider.getNextTreeLevel,
			paramOrder: 'nodeId,attributes',
			nodeParameter: 'nodeId',
			baseAttrs: {
				uiProvider: this.uiProvider
			},

				// an id can never be zero in ExtJS, but this is needed
				// for the root line feature or it will never be working!
			createNode: function(attr) {
				if (attr.id == 0) {
					attr.id = 'siteRootNode';
				}

				return Ext.tree.TreeLoader.prototype.createNode.call(this, attr);
			},

			listeners: {
				beforeload: function(treeLoader, node) {
					treeLoader.baseParams.nodeId = node.id;
					treeLoader.baseParams.attributes = node.attributes.nodeData;
				}
			}
		});
	},

	/**
	 * Enables the context menu feature
	 *
	 * return {void}
	 */
	enableContextMenu: function() {
		this.contextMenu = new TYPO3.Components.PageTree.ContextMenu();

		this.on('contextmenu', function(node, event) {
			this.openContextMenu(node, event);
		});
	},

	/**
	 * Open a context menu for the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {Ext.EventObject} event
	 * return {void}
	 */
	openContextMenu: function(node, event) {
		var attributes = Ext.apply(node.attributes.nodeData, {
			t3ContextInfo: node.ownerTree.t3ContextInfo
		});

		this.contextMenuProvider.getActionsForNodeArray(
			attributes,
			function(configuration) {
				this.contextMenu.removeAll();
				this.contextMenu.fill(node, this, configuration);
				if (this.contextMenu.items.length) {
					this.contextMenu.showAt(event.getXY());

				}
			},
			this
		);
	},

	/**
	 * Initialize the inline editor for the given tree.
	 *
	 * @return {void}
	 */
	enableInlineEditor: function() {
		this.treeEditor = new TYPO3.Components.PageTree.TreeEditor(this);
	},

	/**
	 * Triggers the editing of the node if the tree editor is available
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	triggerEdit: function(node) {
		if (this.treeEditor) {
			this.treeEditor.triggerEdit(node);
		}
	},

	/**
	 * Enables the drag and drop feature
	 *
	 * return {void}
	 */
	enableDragAndDrop: function() {
			// init proxy element
		this.on('startdrag', this.initDd, this);
		this.on('enddrag', this.stopDd, this);
		this.on('nodedragover', this.nodeDragOver, this);

			// node is moved
		this.on('movenode', this.moveNode, this);

			// new node is created/copied
		this.on('beforenodedrop', this.beforeDropNode, this);
		this.on('nodedrop', this.dropNode, this);

			// listens on the ctrl key to toggle the copy mode
		(new Ext.KeyMap(document, {
			key: Ext.EventObject.CONTROL,
			scope: this,
			buffer: 250,
			fn: function() {
				if (!this.controlKeyPressed && this.dragZone.dragging && this.copyHint) {
					if (this.shouldCopyNode) {
						this.copyHint.show();
					} else {
						this.copyHint.hide();
					}

					this.shouldCopyNode = !this.shouldCopyNode;
					this.dragZone.proxy.el.toggleClass('typo3-pagetree-copy');
				}
				this.controlKeyPressed = true;
			}
		}, 'keydown'));

		(new Ext.KeyMap(document, {
			key: Ext.EventObject.CONTROL,
			scope: this,
			fn: function() {
				this.controlKeyPressed = false;
			}
		}, 'keyup'));

			// listens on the escape key to stop the dragging
		(new Ext.KeyMap(document, {
			key: Ext.EventObject.ESC,
			scope: this,
			buffer: 250,
			fn: function(event) {
				if (this.dragZone.dragging) {
					Ext.dd.DragDropMgr.stopDrag(event);
					this.dragZone.onInvalidDrop(event);
				}
			}
		}, 'keydown'));
	},

	/**
	 * Disables the deletion drop zone if configured
	 *
	 * @return {void}
	 */
	stopDd: function() {
		if (this.deletionDropZoneId) {
			Ext.getCmp(this.deletionDropZoneId).hide();
			this.app.doLayout();
		}
	},

	/**
	 * Enables the deletion drop zone if configured. Also it creates the
	 * shown dd proxy element.
	 *
	 * @param {TYPO3.Components.PageTree.Tree} treePanel
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	initDd: function(treePanel, node) {
		var nodeHasChildNodes = (node.hasChildNodes() || node.isExpandable());
		if (this.deletionDropZoneId &&
			(!nodeHasChildNodes ||
			(nodeHasChildNodes && TYPO3.Components.PageTree.Configuration.canDeleteRecursivly)
		)) {
			Ext.getCmp(this.deletionDropZoneId).show();
			this.app.doLayout();
		}
		this.initDDProxyElement();
	},

	/**
	 * Adds the copy hint to the proxy element
	 *
	 * @return {void}
	 */
	initDDProxyElement: function() {
		this.shouldCopyNode = false;
		this.copyHint = new Ext.Element(document.createElement('div')).addClass(this.id + '-copy');
		this.copyHint.dom.appendChild(document.createTextNode(TYPO3.Components.PageTree.LLL.copyHint));
		this.copyHint.setVisibilityMode(Ext.Element.DISPLAY);
		this.dragZone.proxy.el.shadow = false;
		this.dragZone.proxy.ghost.dom.appendChild(this.copyHint.dom);
	},

	/**
	 * Cancels the drop possibility for the position above and below a mount page
	 *
	 * @param {Object} event
	 * @return {void}
	 */
	nodeDragOver: function(event) {
		var isMountPage = (event.target.attributes.realId == 0 || event.target.attributes.nodeData.isMountPoint);
		return !((event.point === 'above' || event.point === 'below') && isMountPage);
	},

	/**
	 * Creates a Fake Node
	 *
	 * This must be done to prevent the calling of the moveNode event.
	 *
	 * @param {Object} dragElement
	 */
	beforeDropNode: function(dragElement) {
		if (dragElement.data && dragElement.data.item && dragElement.data.item.shouldCreateNewNode) {
			this.t3ContextInfo.serverNodeType = dragElement.data.item.nodeType;
			dragElement.dropNode = new Ext.tree.TreeNode({
				text: TYPO3.Components.PageTree.LLL.fakeNodeHint,
				leaf: true,
				isInsertedNode: true
			});

				// fix incorrect cancel value
			dragElement.cancel = false;

		} else if (this.shouldCopyNode) {
			dragElement.dropNode.ui.onOut();
			var attributes = dragElement.dropNode.attributes;
			attributes.isCopiedNode = true;
			attributes.id = 'fakeNode';
			dragElement.dropNode = new Ext.tree.TreeNode(attributes);
		}

		return true;
	},

	/**
	 * Differentiate between the copy and insert event
	 *
	 * @param {Ext.tree.TreeDropZone} dragElement
	 * return {void}
	 */
	dropNode: function(dragElement) {
		this.controlKeyPressed = false;
		if (dragElement.dropNode.attributes.isInsertedNode) {
			dragElement.dropNode.attributes.isInsertedNode = false;
			this.insertNode(dragElement.dropNode);
		} else if (dragElement.dropNode.attributes.isCopiedNode) {
			dragElement.dropNode.attributes.isCopiedNode = false;
			this.copyNode(dragElement.dropNode)
		}
	},

	/**
	 * Moves a node
	 *
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @param {Ext.tree.TreeNode} movedNode
	 * @param {Ext.tree.TreeNode} oldParent
	 * @param {Ext.tree.TreeNode} newParent
	 * @param {int} position
	 * return {void}
	 */
	moveNode: function(tree, movedNode, oldParent, newParent, position) {
		this.controlKeyPressed = false;
		tree.t3ContextNode = movedNode;

		if (position === 0) {
			this.commandProvider.moveNodeToFirstChildOfDestination(newParent, tree);
		} else {
			var previousSiblingNode = newParent.childNodes[position - 1];
			this.commandProvider.moveNodeAfterDestination(previousSiblingNode, tree);
		}
	},

	/**
	 * Inserts a node
	 *
	 * @param {Ext.tree.TreeNode} movedNode
	 * return {void}
	 */
	insertNode: function(movedNode) {
		this.t3ContextNode = movedNode.parentNode;

		movedNode.disable();
		if (movedNode.previousSibling) {
			this.commandProvider.insertNodeAfterDestination(movedNode, this);
		} else {
			this.commandProvider.insertNodeToFirstChildOfDestination(movedNode, this);
		}
	},

	/**
	 * Copies a node
	 *
	 * @param {Ext.tree.TreeNode} movedNode
	 * return {void}
	 */
	copyNode: function(movedNode) {
		this.t3ContextNode = movedNode;

		movedNode.disable();

			// This is hard stuff to do. So increase the timeout for the AJAX request
		Ext.Ajax.timeout = 3600000;

		if (movedNode.previousSibling) {
			this.commandProvider.copyNodeAfterDestination(movedNode, this);
		} else {
			this.commandProvider.copyNodeToFirstChildOfDestination(movedNode, this);
		}
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.Tree', TYPO3.Components.PageTree.Tree);
