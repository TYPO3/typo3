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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.Actions
 *
 * Actions dedicated for the page tree
 *
 * @namespace TYPO3.Components.PageTree
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.Actions = {
	/**
	 * Evaluates a response from an ext direct call and shows a flash message
	 * if it was an exceptional result
	 *
	 * @param {Object} response
	 * @return {Boolean}
	 */
	evaluateResponse: function(response) {
		if (response.success === false) {
			TYPO3.Flashmessage.display(4, 'Exception', response.message);
			return false;
		}

		return true;
	},

	/**
	 * Releases the cut and copy mode from the context menu
	 *
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	releaseCutAndCopyModes: function(tree) {
		tree.t3ContextInfo.inCutMode = false;
		tree.t3ContextInfo.inCopyMode = false;

		if (tree.t3ContextNode) {
			tree.t3ContextNode.attributes.nodeData.t3InCutMode = false;
			tree.t3ContextNode.attributes.nodeData.t3InCopyMode = false;
			tree.t3ContextNode = null;
		}
	},

	/**
	 * Updates an existing node with the given alternative. The new tree node
	 * is returned afterwards.
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {Boolean} isExpanded
	 * @param {Object} updatedNode
	 * @param {Function} callback
	 * @return {Ext.tree.TreeNode}
	 */
	updateNode: function(node, isExpanded, updatedNode, callback) {
		if (!updatedNode) {
			return null;
		}

		updatedNode.uiProvider = node.ownerTree.uiProvider;
		var newTreeNode = new Ext.tree.TreeNode(updatedNode);

		var refreshCallback = this.restoreNodeStateAfterRefresh;
		if (callback) {
			refreshCallback = refreshCallback.createSequence(callback);
		}

		node.parentNode.replaceChild(newTreeNode, node);
		newTreeNode.ownerTree.refreshNode(newTreeNode, refreshCallback);

		return newTreeNode;
	},

	/**
	 * Restores the node state
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {Boolean} isExpanded
	 * @return {void}
	 */
	restoreNodeStateAfterRefresh: function(node, isExpanded) {
		node.parentNode.expand(false, false);
		if (isExpanded) {
			node.expand(false, false);
		} else {
			node.collapse(false, false);
		}
	},

	/**
	 * Shows deletion confirmation window
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @param {Function} callback
	 * @param {Boolean} recursiveDelete
	 * @return {void}
	 */
	confirmDelete: function(node, tree, callback, recursiveDelete) {
		callback = callback || null;

		var title = TYPO3.Components.PageTree.LLL.deleteDialogTitle,
			message = TYPO3.Components.PageTree.LLL.deleteDialogMessage;
		if (recursiveDelete) {
			message = TYPO3.Components.PageTree.LLL.recursiveDeleteDialogMessage;
		}

		Ext.Msg.show({
			title: title,
			msg: message,
			buttons: Ext.Msg.YESNO,
			fn: function (answer) {
				if (answer === 'yes') {
					TYPO3.Components.PageTree.Actions.deleteNode(node, tree, callback);
					return true;
				}
				return false;
			},
			animEl: 'elId'
		});
	},

	/**
	 * Deletes a node directly
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @param {Function} callback
	 * @return {void}
	 */
	deleteNode: function(node, tree, callback) {
		TYPO3.Components.PageTree.Commands.deleteNode(
			node.attributes.nodeData,
			function(response) {
				var succeeded = this.evaluateResponse(response);
				if (Ext.isFunction(callback)) {
					callback(node, tree, succeeded);
				}

				if (succeeded) {
						// the node may not be removed in workspace mode
					if (top.TYPO3.configuration.inWorkspace && response.id) {
						this.updateNode(node, node.isExpanded(), response);
					} else {
						node.remove();
					}
				}
			},
			this
		);
	},

	/**
	 * Removes a node either directly or first shows deletion popup
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	removeNode: function(node, tree) {
		if (TYPO3.Components.PageTree.Configuration.displayDeleteConfirmation) {
			this.confirmDelete(node);
		} else {
			this.deleteNode(node, tree);
		}
	},

	/**
	 * Restores a given node and moves it to the given destination inside the tree. Use this
	 * method if you want to add it as the first child of the destination.
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @param {Ext.tree.TreeNode} destination
	 * @return {void}
	 */
	restoreNodeToFirstChildOfDestination: function(node, tree, destination) {
		TYPO3.Components.PageTree.Commands.restoreNode(
			node.attributes.nodeData,
			destination.attributes.nodeData.id,
			function(updatedNode) {
				if (this.evaluateResponse(updatedNode)) {
					var newTreeNode = new Ext.tree.TreeNode(
						Ext.apply(node.attributes, updatedNode)
					);
					destination.insertBefore(newTreeNode, destination.firstChild);
				}
			},
			this
		);
	},

	/**
	 * Restores a given node and moves it to the given destination inside the tree. Use this
	 * method if you want to add the node after the destination node.
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @param {Ext.tree.TreeNode} destination
	 * @return {void}
	 */
	restoreNodeAfterDestination: function(node, tree, destination) {
		TYPO3.Components.PageTree.Commands.restoreNode(
			node.attributes.nodeData,
			-destination.attributes.nodeData.id,
			function(updatedNode) {
				if (this.evaluateResponse(updatedNode)) {
					var newTreeNode = new Ext.tree.TreeNode(
						Ext.apply(node.attributes, updatedNode)
					);
					destination.parentNode.insertBefore(newTreeNode, destination.nextSibling);
				}
			},
			this
		);
	},

	/**
	 * Collapses a whole tree branch
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	collapseBranch: function(node) {
		node.collapse(true);
	},

	/**
	 * Expands a whole tree branch
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	expandBranch: function(node) {
		node.expand(true);
	},

	/**
	 * Opens a popup windows for previewing the given node/page
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	viewPage: function(node) {
		var frontendWindow = window.open('', 'newTYPO3frontendWindow');
		TYPO3.Components.PageTree.Commands.getViewLink(
			node.attributes.nodeData,
			function(result) {
				frontendWindow.location = result;
				frontendWindow.focus();
			}
		);
	},

	/**
	 * Creates a temporary tree mount point
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	mountAsTreeRoot: function(node, tree) {
		TYPO3.Components.PageTree.Commands.setTemporaryMountPoint(
			node.attributes.nodeData,
			function(response) {
				if (TYPO3.Components.PageTree.Configuration.temporaryMountPoint) {
					TYPO3.Backend.NavigationContainer.PageTree.removeIndicator(
						TYPO3.Backend.NavigationContainer.PageTree.temporaryMountPointInfoIndicator
					);
				}

				TYPO3.Components.PageTree.Configuration.temporaryMountPoint = response;
				TYPO3.Backend.NavigationContainer.PageTree.addTemporaryMountPointIndicator();

				var selectedNode = TYPO3.Backend.NavigationContainer.PageTree.getSelected();
				tree.stateId = 'Pagetree' + TYPO3.Components.PageTree.Configuration.temporaryMountPoint;
				tree.refreshTree(function() {
					var nodeIsSelected = false;
					if (selectedNode) {
						nodeIsSelected = TYPO3.Backend.NavigationContainer.PageTree.select(
							selectedNode.attributes.nodeData.id
						);
					}

					var node = (nodeIsSelected ? TYPO3.Backend.NavigationContainer.PageTree.getSelected() : null);
					if (node) {
						this.singleClick(node, tree);
					} else {
						this.singleClick(tree.getRootNode().firstChild, tree);
					}
				}, this);
			},
			this
		);
	},

	/**
	 * Opens the edit page properties dialog
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	editPageProperties: function(node) {
		node.select();
		var returnUrl = TYPO3.Backend.ContentContainer.src;
		if (returnUrl.indexOf('returnUrl') !== -1) {
			returnUrl = TYPO3.Utility.getParameterFromUrl(returnUrl, 'returnUrl');
		} else {
			returnUrl = encodeURIComponent(returnUrl);
		}

		TYPO3.Backend.ContentContainer.setUrl(
			'alt_doc.php?edit[pages][' + node.attributes.nodeData.id + ']=edit&returnUrl=' + returnUrl
		);
	},

	/**
	 * Opens the new page wizard
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	newPageWizard: function(node) {
		node.select();
		TYPO3.Backend.ContentContainer.setUrl(
			'db_new.php?id=' + node.attributes.nodeData.id + '&pagesOnly=1'
		);
	},

	/**
	 * Opens the info popup
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	openInfoPopUp: function(node) {
		launchView('pages', node.attributes.nodeData.id);
	},

	/**
	 * Opens the history popup
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	openHistoryPopUp: function(node) {
		node.select();
		TYPO3.Backend.ContentContainer.setUrl(
			'show_rechis.php?element=pages:' + node.attributes.nodeData.id
		);
	},

	/**
	 * Opens the export .t3d file dialog
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	exportT3d: function(node) {
		node.select();
		TYPO3.Backend.ContentContainer.setUrl(
			'sysext/impexp/app/index.php?tx_impexp[action]=export&' +
				'id=0&tx_impexp[pagetree][id]=' + node.attributes.nodeData.id +
				'&tx_impexp[pagetree][levels]=0' +
				'&tx_impexp[pagetree][tables][]=_ALL'
		);
	},

	/**
	 * Opens the import .t3d file dialog
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	importT3d: function(node) {
		node.select();
		TYPO3.Backend.ContentContainer.setUrl(
			'sysext/impexp/app/index.php?id=' + node.attributes.nodeData.id +
				'&table=pages&tx_impexp[action]=import'
		);
	},

	/**
	 * Enables the cut mode of a node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	enableCutMode: function(node, tree) {
		this.disableCopyMode(node, tree);
		node.attributes.nodeData.t3InCutMode = true;
		tree.t3ContextInfo.inCutMode = true;
		tree.t3ContextNode = node;
	},

	/**
	 * Disables the cut mode of a node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	disableCutMode: function(node, tree) {
		this.releaseCutAndCopyModes(tree);
		node.attributes.nodeData.t3InCutMode = false;
	},

	/**
	 * Enables the copy mode of a node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	enableCopyMode: function(node, tree) {
		this.disableCutMode(node, tree);
		node.attributes.nodeData.t3InCopyMode = true;
		tree.t3ContextInfo.inCopyMode = true;
		tree.t3ContextNode = node;
	},

	/**
	 * Disables the copy mode of a node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	disableCopyMode: function(node, tree) {
		this.releaseCutAndCopyModes(tree);
		node.attributes.nodeData.t3InCopyMode = false;
	},

	/**
	 * Pastes the cut/copy context node into the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	pasteIntoNode: function(node, tree) {
		if (!tree.t3ContextNode) {
			return;
		}

		if (tree.t3ContextInfo.inCopyMode) {
				 // This is hard stuff to do. So increase the timeout for the AJAX request
			Ext.Ajax.timeout = 3600000;

			var newNode = tree.t3ContextNode = new Ext.tree.TreeNode(tree.t3ContextNode.attributes);
			newNode.id = 'fakeNode';
			node.insertBefore(newNode, node.childNodes[0]);
			node.attributes.nodeData.t3InCopyMode = false;
			this.copyNodeToFirstChildOfDestination(newNode, tree);

		} else if (tree.t3ContextInfo.inCutMode) {
			if (node.getPath().indexOf(tree.t3ContextNode.id) !== -1) {
				return;
			}

			node.appendChild(tree.t3ContextNode);
			node.attributes.nodeData.t3InCutMode = false;
			this.moveNodeToFirstChildOfDestination(node, tree);
		}
	},

	/**
	 * Pastes a cut/copy context node after the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	pasteAfterNode: function(node, tree) {
		if (!tree.t3ContextNode) {
			return;
		}

		if (tree.t3ContextInfo.inCopyMode) {
				// This is hard stuff to do. So increase the timeout for the AJAX request
			Ext.Ajax.timeout = 3600000;

			var newNode = tree.t3ContextNode = new Ext.tree.TreeNode(tree.t3ContextNode.attributes);
			newNode.id = 'fakeNode';
			node.parentNode.insertBefore(newNode, node.nextSibling);
			node.attributes.nodeData.t3InCopyMode = false;
			this.copyNodeAfterDestination(newNode, tree);

		} else if (tree.t3ContextInfo.inCutMode) {
			if (node.getPath().indexOf(tree.t3ContextNode.id) !== -1) {
				return;
			}

			node.parentNode.insertBefore(tree.t3ContextNode, node.nextSibling);
			node.attributes.nodeData.t3InCutMode = false;
			this.moveNodeAfterDestination(node, tree);
		}
	},

	/**
	 * Moves the current tree context node after the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	moveNodeAfterDestination: function(node, tree) {
		TYPO3.Components.PageTree.Commands.moveNodeAfterDestination(
			tree.t3ContextNode.attributes.nodeData,
			node.attributes.nodeData.id,
			function(response) {
				if (this.evaluateResponse(response) && tree.t3ContextNode) {
					this.updateNode(tree.t3ContextNode, tree.t3ContextNode.isExpanded(), response);
				}
				this.releaseCutAndCopyModes(tree);
			},
			this
		);
	},

	/**
	 * Moves the current tree context node as the first child of the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	moveNodeToFirstChildOfDestination: function(node, tree) {
		TYPO3.Components.PageTree.Commands.moveNodeToFirstChildOfDestination(
			tree.t3ContextNode.attributes.nodeData,
			node.attributes.nodeData.id,
			function(response) {
				if (this.evaluateResponse(response) && tree.t3ContextNode) {
					this.updateNode(tree.t3ContextNode, tree.t3ContextNode.isExpanded(), response);
				}
				this.releaseCutAndCopyModes(tree);
			},
			this
		);
	},

	/**
	 * Inserts a new node after the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	insertNodeAfterDestination: function(node, tree) {
		TYPO3.Components.PageTree.Commands.insertNodeAfterDestination(
			tree.t3ContextNode.attributes.nodeData,
			node.previousSibling.attributes.nodeData.id,
			tree.t3ContextInfo.serverNodeType,
			function(response) {
				if (this.evaluateResponse(response)) {
					this.updateNode(node, node.isExpanded(), response, function(node) {
						tree.triggerEdit(node);
					});
				}
				this.releaseCutAndCopyModes(tree);
			},
			this
		);
	},

	/**
	 * Inserts a new node as the first child of the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	insertNodeToFirstChildOfDestination: function(node, tree) {
		TYPO3.Components.PageTree.Commands.insertNodeToFirstChildOfDestination(
			tree.t3ContextNode.attributes.nodeData,
			tree.t3ContextInfo.serverNodeType,
			function(response) {
				if (this.evaluateResponse(response)) {
					this.updateNode(node, true, response, function(node) {
						tree.triggerEdit(node);
					});
				}
				this.releaseCutAndCopyModes(tree);
			},
			this
		);
	},

	/**
	 * Copies the current tree context node after the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	copyNodeAfterDestination: function(node, tree) {
		TYPO3.Components.PageTree.Commands.copyNodeAfterDestination(
			tree.t3ContextNode.attributes.nodeData,
			node.previousSibling.attributes.nodeData.id,
			function(response) {
				if (this.evaluateResponse(response)) {
					this.updateNode(node, true, response, function(node) {
						tree.triggerEdit(node);
					});
				}
				this.releaseCutAndCopyModes(tree);
			},
			this
		);
	},

	/**
	 * Copies the current tree context node as the first child of the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	copyNodeToFirstChildOfDestination: function(node, tree) {
		TYPO3.Components.PageTree.Commands.copyNodeToFirstChildOfDestination(
			tree.t3ContextNode.attributes.nodeData,
			node.parentNode.attributes.nodeData.id,
			function(response) {
				if (this.evaluateResponse(response)) {
					this.updateNode(node, true, response, function(node) {
						tree.triggerEdit(node);
					});
				}
				this.releaseCutAndCopyModes(tree);
			},
			this
		);
	},

	/**
	 * Visibilizes a page
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	enablePage: function(node) {
		TYPO3.Components.PageTree.Commands.visiblyNode(
			node.attributes.nodeData,
			function(response) {
				if (this.evaluateResponse(response)) {
					this.updateNode(node, node.isExpanded(), response);
				}
			},
			this
		);
	},

	/**
	 * Disables a page
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {void}
	 */
	disablePage: function(node) {
		TYPO3.Components.PageTree.Commands.disableNode(
			node.attributes.nodeData,
			function(response) {
				if (this.evaluateResponse(response)) {
					this.updateNode(node, node.isExpanded(), response);
				}
			},
			this
		);
	},

	/**
	 * Reloads the content frame with the current module and node id
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	singleClick: function(node, tree) {
		tree.currentSelectedNode = node;

		var separator = '?';
		if (currentSubScript.indexOf('?') !== -1) {
			separator = '&';
		}

		node.select();
		if (tree.stateHash) {
			tree.stateHash.lastSelectedNode = node.id;
		} else {
			TYPO3.Components.PageTree.Commands.addRootlineOfNodeToStateHash(
				TYPO3.Backend.NavigationContainer.PageTree.mainTree.stateId,
				node.attributes.nodeData.id, function(stateHash) {
					TYPO3.Backend.NavigationContainer.PageTree.mainTree.stateHash = stateHash;
					TYPO3.Backend.NavigationContainer.PageTree.mainTree.refreshTree();
				}
			);
		}

		fsMod.recentIds['web'] = node.attributes.nodeData.id;

		TYPO3.Backend.ContentContainer.setUrl(
			TS.PATH_typo3 + currentSubScript + separator + 'id=' + node.attributes.nodeData.id
		);
	},

	/**
	 * Opens a configured url inside the content frame
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @param {Object} contextItem
	 * @return {void}
	 */
	openCustomUrlInContentFrame: function(node, tree, contextItem) {
		if (!contextItem.customAttributes || !contextItem.customAttributes.contentUrl) {
			return;
		}

		node.select();
		TYPO3.Backend.ContentContainer.setUrl(
			contextItem.customAttributes.contentUrl.replace('###ID###', node.attributes.nodeData.id)
		);
	},

	/**
	 * Updates the title of a node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {String} newText
	 * @param {String} oldText
	 * @param {TYPO3.Components.PageTree.TreeEditor} treeEditor
	 * @return {void}
	 */
	saveTitle: function(node, newText, oldText, treeEditor) {
		this.singleClick(node.editNode, node.editNode.ownerTree);
		if (newText === oldText || newText == '') {
			treeEditor.updateNodeText(
				node,
				node.editNode.attributes.nodeData.editableText,
				Ext.util.Format.htmlEncode(oldText)
			);
			return;
		}

		TYPO3.Components.PageTree.Commands.updateLabel(
			node.editNode.attributes.nodeData,
			newText,
			function(response) {
				if (this.evaluateResponse(response)) {
					treeEditor.updateNodeText(node, response.editableText, response.updatedText);
				} else {
					treeEditor.updateNodeText(
						node,
						node.editNode.attributes.nodeData.editableText,
						Ext.util.Format.htmlEncode(oldText)
					);
				}
			},
			this
		);
	}
};
