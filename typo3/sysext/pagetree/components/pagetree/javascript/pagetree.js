Ext.namespace('TYPO3.Components.PageTree');

/**
 * This is the TreeNodeUI for the FilteringTree. This is the class which renders
 * the tree nodes.
 * The below modifications add another span tag around the icon for better skinning,
 * and a prefix text which is displayed in front of the real "text" contents.
 * Because the "text" property can be edited inline, the "prefixText" is used to
 * prepend the editable text.
 */
TYPO3.Components.PageTree.PageTreeUI = function() {
	TYPO3.Components.PageTree.PageTreeUI.superclass.constructor.apply(this, arguments);
};
Ext.extend(TYPO3.Components.PageTree.PageTreeUI, Ext.tree.TreeNodeUI, {
	// private
	// This method is taken from ExtJS sources. Modifications are marked with // START TYPO3-MODIFICATION
	renderElements : function(n, a, targetNode, bulkRender) {
		// add some indent caching, this helps performance when rendering a large tree
		this.indentMarkup = n.parentNode ? n.parentNode.ui.getChildIndent() : '';
		var cb = typeof a.checked == 'boolean';
		var href = a.href ? a.href : Ext.isGecko ? "" : "#";
		var buf = ['<li class="x-tree-node"><div ext:tree-node-id="',n.id,'" class="x-tree-node-el x-tree-node-leaf x-unselectable ', a.cls,'" unselectable="on">',
			'<span class="x-tree-node-indent">',this.indentMarkup,"</span>",
			'<img src="', this.emptyIcon, '" class="x-tree-ec-icon x-tree-elbow" />',
			// START TYPO3-MODIFICATION
			a.spriteIconCode,
			'<span class="prefixText">', a.prefixText, '</span>',
			// END TYPO3-MODIFICATION
			cb ? ('<input class="x-tree-node-cb" type="checkbox" ' + (a.checked ? 'checked="checked" />' : '/>')) : '',
			'<a hidefocus="on" class="x-tree-node-anchor" href="',href,'" tabIndex="1" ',
			a.hrefTarget ? ' target="' + a.hrefTarget + '"' : "", '><span unselectable="on">',n.text,"</span></a></div>",
			'<ul class="x-tree-node-ct" style="display:none;"></ul>',
			"</li>"].join('');

		var nel;
		if (bulkRender !== true && n.nextSibling && (nel = n.nextSibling.ui.getEl())) {
			this.wrap = Ext.DomHelper.insertHtml("beforeBegin", nel, buf);
		} else {
			this.wrap = Ext.DomHelper.insertHtml("beforeEnd", targetNode, buf);
		}

		this.elNode = this.wrap.childNodes[0];
		this.ctNode = this.wrap.childNodes[1];
		var cs = this.elNode.childNodes;
		this.indentNode = cs[0];
		this.ecNode = cs[1];
		this.iconNode = cs[2];
		// START TYPO3-MODIFICATION
		Ext.fly(this.iconNode).on('click', function(event) {
			this.getOwnerTree().openContextMenu(this, event); // calling the context-menu event doesn't work!'
			event.stopEvent();
		}, n);
		// Index from 3 to 4 incremented!
		var index = 4;
		// STOP TYPO3-MODIFICATION
		if (cb) {
			this.checkbox = cs[3];
			// fix for IE6
			this.checkbox.defaultChecked = this.checkbox.checked;
			index++;
		}
		this.anchor = cs[index];
		this.textNode = cs[index].firstChild;
	},

	// private
	// Overwriting the double click event, because we don't want to collapse or expand nodes
	// by this event
	onDblClick : function(e) {
		if (this.disabled) {
			return;
		}

		if (this.fireEvent('beforedblclick', this.node, e) !== false) {
			this.fireEvent('dblclick', this.node, e);
		}
	}
});

TYPO3.Components.PageTree.App = Ext.extend(Ext.Panel, {
	id: 'typo3-pagetree',
	border: false,

	tree: null,
	topPanel: null,

	dataProvider: null,

	contextMenuConfiguration: null,

	isControlPressed: false,

	initComponent: function() {
		this.dataProvider = TYPO3.Components.PageTree.DataProvider;

		this.tree = new TYPO3.Components.PageTree.Tree({
			pageTree: this
		});

		this.topPanel = new TYPO3.Components.PageTree.FeaturePanel({
			pageTree: this
		});

		this.deletionDropZone = new TYPO3.Components.PageTree.DeletionDropZone({
			pageTree: this
		});

		this.addInlineEditorFeature(this.tree);
		this.addNodeCopyPasteFeature(this.tree);

		this.items = [
			this.topPanel, {
				border: false,
				id: 'typo3-pagetree-treeContainer',
				items: [
					this.tree,
					this.topPanel.filterTree
				]
			},
			this.deletionDropZone
		];

		TYPO3.Components.PageTree.App.superclass.initComponent.apply(this, arguments);
	},

	refreshTree: function() {
		this.tree.root.reload();
	},

	/**
	 * Initialize the inline editor for the given tree.
	 *
	 * @param tree The Ext.tree.TreePanel where the Inline Editor should be added.
	 * @internal
	 */
	addInlineEditorFeature: function(tree) {
		var treeEditor = new Ext.tree.TreeEditor(
				tree, {
			cancelOnEsc: true,
			completeOnEnter: true,
			ignoreNoChange: true,
			editDelay: 250,
			shadow: false
		}
				);

		treeEditor.addListener('complete', TYPO3.Components.PageTree.PageActions.saveTitle, this);
	},

	addNodeCopyPasteFeature: function(tree) {
		// When dragging starts, we need to add the explanation to the tool-tip
		tree.addListener('startdrag', function(tree) {
			var explanationNode = document.createElement('div');
			Ext.fly(explanationNode).addClass('copyHelp');
			explanationNode.appendChild(document.createTextNode('Press Ctrl to copy.'));

			tree.explanationTooltip = explanationNode;
			tree.dragZone.proxy.ghost.dom.appendChild(explanationNode);
			this.deletionDropZone.setHeight(30);
			this.doLayout();
		}, this);

		// SECTION: move
		// When a node has been moved via drag and drop, this is called.
		// This event is ONLY called on move, NOT on copy, insert or delete.
		tree.addListener('movenode', function(tree, movedNode, oldParent, newParent, position) {
			if (position == 0) {
				this.dataProvider.moveNodeToFirstChildOfDestination(movedNode.id, newParent.id);
			} else {
				var previousSibling = newParent.childNodes[position - 1];
				this.dataProvider.moveNodeAfterDestination(movedNode.id, previousSibling.id);
			}
		}, this);

		// SECTION: copy / create
		// The following two event handlers deal with the node copying.
		// The first one is called because we need to copy the node, and replace it by a dummy,
		// and the second one disables the node, does the ajax request and un-hides the node again.
		tree.addListener('beforenodedrop', function(de) {
			/*this.deletionDropZone.setHeight(0);
			 this.doLayout();*/

			if (de.data && de.data.item && de.data.item.shouldCreateNewNode) {
				// Insertion - part 1
				var nodeType = de.data.item.nodeType;
				de.dropNode = new Ext.tree.TreeNode({
					text: 'New...',
					leaf: true
				});
				de.cancel = false; // Somehow, "cancel" is currently set to "true" - but do not know why.
				de.dropNode.uiProvider = TYPO3.Components.PageTree.PageTreeUI;
				de.dropNode.isInsertedNode = true;
				de.dropNode.serverNodeType = nodeType;
			} else {
				if (this.isControlPressed) {
					// Copying - part 1
					de.dropNode = new Ext.tree.TreeNode(de.dropNode.attributes);
					de.dropNode.uiProvider = TYPO3.Components.PageTree.PageTreeUI;
					de.dropNode.isCopiedNode = true;
				}
			}
			return true;
		}, this);

		tree.addListener('nodedrop', function(de) {
			// This callback method replaces the current node with the
			// one transmitted from the server.
			var callback = function(updatedNodeFromServer) {
				// We need to make sure that the UI Provider is correctly set, so that the rendering works for the new node.
				updatedNodeFromServer.uiProvider = TYPO3.Components.PageTree.PageTreeUI;
				var newTreeNode = new Ext.tree.TreeNode(updatedNodeFromServer);
				this.parentNode.replaceChild(newTreeNode, this);
			};

			if (de.dropNode.isInsertedNode) {
				// Insertion: - part 2
				de.dropNode.disable();
				if (de.dropNode.previousSibling) {
					// We have previous sibling, so we want to add the record AFTER the previous sibling
					this.dataProvider.insertNodeAfterDestination(de.dropNode.parentNode.id, de.dropNode.previousSibling.id, de.dropNode.serverNodeType, callback.createDelegate(de.dropNode));
				} else {
					if (de.dropNode.parentNode) {
						// We do not have a previous sibling, but a parent node. Thus, we add the node as the first child
						// of the parent.
						this.dataProvider.insertNodeToFirstChildOfDestination(de.dropNode.parentNode.id, de.dropNode.serverNodeType, callback.createDelegate(de.dropNode));
					} else {
						// Should not happen!
					}
				}
			} else {
				if (de.dropNode.isCopiedNode) {
					// Copying - part 2
					de.dropNode.disable();
					if (de.dropNode.previousSibling) {
						// We have previous sibling, so we want to add the record AFTER the previous sibling
						this.dataProvider.copyNodeAfterDestination(de.dropNode.id, de.dropNode.previousSibling.id, callback.createDelegate(de.dropNode));
					} else {
						if (de.dropNode.parentNode) {
							// We do not have a previous sibling, but a parent node. Thus, we add the node as the first child
							// of the parent.
							this.dataProvider.copyNodeToFirstChildOfDestination(de.dropNode.id, de.dropNode.parentNode, callback.createDelegate(de.dropNode));
						} else {
							// Should not happen!
						}
					}
				}
			}
		}, this);

		// SECTION: Key Handlers
		new Ext.KeyMap(document, {
			key: Ext.EventObject.CONTROL,
			fn: function() {
				this.isControlPressed = true;
				var copyHelpDiv = Ext.fly(tree.explanationTooltip);
				if (copyHelpDiv) {
					copyHelpDiv.setVisibilityMode(Ext.Element.DISPLAY);
					copyHelpDiv.hide();
				}
			},
			scope: this
		}, 'keydown');

		new Ext.KeyMap(document, {
			key: Ext.EventObject.CONTROL,
			fn: function() {
				this.isControlPressed = false;
				var copyHelpDiv = Ext.fly(tree.explanationTooltip);
				if (copyHelpDiv) {
					copyHelpDiv.show();
				}
			},
			scope: this
		}, 'keyup');
	}
});

TYPO3.ModuleMenu.App.registerNavigationComponent('typo3-pagetree', function() {
	return new TYPO3.Components.PageTree.App();
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.App', TYPO3.Components.PageTree.App);