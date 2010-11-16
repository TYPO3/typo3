Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.Tree = Ext.extend(Ext.tree.TreePanel, {
	id: 'typo3-pagetree-tree',
	border: false,

	enableDD: true,
	dragConfig: {
		ddGroup: 'TreeDD'
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
			paramOrder: 'rootline',

			baseAttrs: {
				uiProvider: 't3'
			},

			uiProviders: {
				t3: TYPO3.Components.PageTree.PageTreeUI,
				rootNodeProvider: Ext.tree.TreeNodeUI
			},

			// The below method fixes a stupid bug of ExtJS / PHP JSON:
			// ExtJS expects the "expanded" attribute to be "true", and
			// it compares it with ===.
			// PHP json_encode submits a "1" if the value is true - thus,
			// the expanded property is not correctly evaluated by ExtJS.
			// Below, we do a loose type checking, and if it matches, we
			// set the JavaScript value "true". This circumvents the bug.
			createNode: function(attr) {
				if (attr.expanded) {
					attr.expanded = true;
				}
				return Ext.tree.TreeLoader.prototype.createNode.call(this, attr);
			},

			listeners: {
				// We always have to transmit the rootline to the server.
				beforeload: function(treeLoader, node) {
					treeLoader.baseParams.rootline = node.getPath();
				},
				load: function(treeLoader, node) {
					// Helper function
					var expandTransmittedNodesRecursively = function(node) {
						var numberOfSubNodes = node.childNodes.length;
						if (numberOfSubNodes > 0) {
							node.expand(false, false);
						}
						for (var i = 0; i < numberOfSubNodes; i++) {
							expandTransmittedNodesRecursively(node.childNodes[i]);
						}
					};
					expandTransmittedNodesRecursively(node);
				}
			}
		});

		TYPO3.Components.PageTree.Tree.superclass.initComponent.apply(this, arguments);
	},

	// shows the context menu and creates it if it's not already done
	openContextMenu: function(node, event) {
		node.select();
		var contextMenu = node.getOwnerTree().contextMenu;
		contextMenu.removeAll();

		var numberOfElementsInside = contextMenu.fillWithMenuItems(node, this.contextMenuConfiguration);
		if (numberOfElementsInside > 0) {
			contextMenu.showAt(event.getXY());
		}
	},

	listeners: {
		// SECTION Contextmenu
		// After rendering of the tree, we start the preloading of the context
		// menu configuration
		afterrender: {
			fn: function(tree) {
				if (tree.contextMenuConfiguration == null) {
					this.pageTree.dataProvider.getContextMenuConfiguration(
						function(result) {
							tree.contextMenuConfiguration = result;
						}
					);
				}
			}
		},

		// this event triggers the context menu
		contextmenu: {
			fn: function(node, event) {
				node.getOwnerTree().openContextMenu(node, event);
			}
		},

		// SECTION Tree State Remember
		expandnode: {
			fn: function (node) {
				this.pageTree.dataProvider.registerExpandedNode(node.getPath());
			}
		},

		collapsenode: {
			fn: function(node) {
				this.pageTree.dataProvider.registerCollapsedNode(node.getPath());
			}
		},

		// calls a given single click callback for the tree
		click: {
			fn: function (node, event) {
				if (this.doubleClickEventActive) {
					this.doubleClickEventActive = false;
					event.stopEvent();
				} else {
					eval(node.attributes.properties.clickCallback + '(node)');
				}
			},
			delay: 400
		},

		// seems to prevent some internal issues with the double-click for the tree editor
		dblclick: {
			fn: function() {
				this.doubleClickEventActive = true;
				return false;
			}
		}
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.Tree', TYPO3.Components.PageTree.Tree);