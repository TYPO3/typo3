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
 * TYPO3 Page Tree Application
 */

Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.App = Ext.extend(Ext.Panel, {
	id: 'typo3-pagetree',
	border: false,

	tree: null,
	topPanel: null,

	dataProvider: null,
	commandProvider : null,
	contextMenuDataProvider: null,

	contextMenuConfiguration: null,

	isControlPressed: false,

	initComponent: function() {
		this.dataProvider = TYPO3.Components.PageTree.DataProvider;
		this.commandProvider = TYPO3.Components.PageTree.Actions;
		this.contextMenuDataProvider = TYPO3.Components.PageTree.ContextMenuDataProvider;

		this.tree = new TYPO3.Components.PageTree.Tree({
			pageTree: this,
			plugins: new Ext.ux.plugins.TreePanelStateful()
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

	/**
	 * Initialize the inline editor for the given tree.
	 *
	 * @param tree The Ext.tree.TreePanel where the Inline Editor should be added.
	 * @internal
	 */
	addInlineEditorFeature: function(tree) {
		var treeEditor = new Ext.tree.TreeEditor(
			tree, {
				ignoreNoChange: true,
				editDelay: 250,
				shadow: false
			}
		);

      	treeEditor.on({
			beforestartedit: {
				scope: this,
				fn: function(treeEditor) {
					// @todo treeEditor.editNode.attributes.label should be used as editable label
				}
			},

			complete: {
				scope: this,
				fn: this.commandProvider.saveTitle
			}
      	});
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
				this.commandProvider.moveNodeToFirstChildOfDestination(movedNode, newParent.id);
			} else {
				var previousSibling = newParent.childNodes[position - 1];
				this.commandProvider.moveNodeAfterDestination(movedNode, previousSibling.id);
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
					this.commandProvider.insertNodeAfterDestination(de.dropNode, callback);
				} else {
					if (de.dropNode.parentNode) {
						// We do not have a previous sibling, but a parent node. Thus, we add the node as the first child
						// of the parent.
						this.commandProvider.insertNodeToFirstChildOfDestination(de.dropNode, callback);
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
						this.commandProvider.copyNodeAfterDestination(de.dropNode, callback);
					} else {
						if (de.dropNode.parentNode) {
							// We do not have a previous sibling, but a parent node. Thus, we add the node as the first child
							// of the parent.
							this.commandProvider.copyNodeToFirstChildOfDestination(de.dropNode, callback);
						} else {
							// Should not happen!
						}
					}
				}
			}
		}, this);

		// SECTION: Key Handlers	
		(new Ext.KeyMap(document, {
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
		}, 'keydown'));

		(new Ext.KeyMap(document, {
			key: Ext.EventObject.CONTROL,
			fn: function() {
				this.isControlPressed = false;
				var copyHelpDiv = Ext.fly(tree.explanationTooltip);
				if (copyHelpDiv) {
					copyHelpDiv.show();
				}
			},
			scope: this
		}, 'keyup'));
	}
});

TYPO3.ModuleMenu.App.registerNavigationComponent('typo3-pagetree', function() {
	return new TYPO3.Components.PageTree.App();
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.App', TYPO3.Components.PageTree.App);