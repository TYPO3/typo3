Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.Actions = {
	evaluateResponse: function(response) {
		if (response.success === false) {
			TYPO3.Flashmessage.display(4, 'Exception', response.message);
			return false;
		}

		return true;
	},

	removeNode: function(node) {
		TYPO3.Components.PageTree.Commands.deleteNode(
			node.attributes.nodeData,
			function(response) {
				if (this.evaluateResponse(response)) {
					node.remove();
				}
			},
			this
		);
	},

	restoreNode: function(node) {
		TYPO3.Components.PageTree.Commands.restoreNode(node.attributes.nodeData);
	},

	stub: function() {
		alert('Just a Stub! Don\'t Panic!');
	},

	viewPage: function(node) {
		TYPO3.Components.PageTree.Commands.getViewLink(
			node.attributes.nodeData,
			(TYPO3.configuration.workspaceFrontendPreviewEnabled && TYPO3.configuration.currentWorkspace != 0),
			function(result) {
				openUrlInWindow(result, 'typo3-contextMenu-view');
			}
		);
	},

	editPageProperties: function(node) {
		TYPO3.Backend.ContentContainer.setUrl(
			'alt_doc.php?edit[pages][' + node.attributes.id + ']=edit'
		);
	},

	newPageWizard: function(node) {
		TYPO3.Backend.ContentContainer.setUrl(
			'db_new.php?id=' + node.attributes.id + '&pagesOnly=1'
		);
	},

	openInfoPopUp: function(node) {
		launchView('pages', node.attributes.id);
	},

	openHistoryPopUp: function(node) {
		TYPO3.Backend.ContentContainer.setUrl(
			'show_rechis.php?element=pages:' + node.attributes.id
		);
	},

	enableCutMode: function(node, pageTree) {
		this.disableCopyMode(node, pageTree);
		node.attributes.nodeData.t3InCutMode = true;
		pageTree.tree.t3ContextInfo.inCutMode = true;
		pageTree.tree.t3ContextNode = node;
	},

	disableCutMode: function(node, pageTree) {
		this.releaseCutAndCopyModes(pageTree);
		node.attributes.nodeData.t3InCutMode = false;
	},

	enableCopyMode: function(node, pageTree) {
		this.disableCutMode(node, pageTree);
		node.attributes.nodeData.t3InCopyMode = true;
		pageTree.tree.t3ContextInfo.inCopyMode = true;
		pageTree.tree.t3ContextNode = node;
	},

	disableCopyMode: function(node, pageTree) {
		this.releaseCutAndCopyModes(pageTree);
		node.attributes.nodeData.t3InCopyMode = false;
	},

	pasteIntoNode: function(node, pageTree) {
		this.releaseCutAndCopyModes(pageTree);
		this.stub();
	},

	pasteAfterNode: function(node, pageTree) {
		this.releaseCutAndCopyModes(pageTree);
		this.stub();
	},

	releaseCutAndCopyModes: function(pageTree) {
		pageTree.tree.t3ContextInfo.inCutMode = false;
		pageTree.tree.t3ContextInfo.inCopyMode = false;

		if (pageTree.tree.t3ContextNode) {
			pageTree.tree.t3ContextNode.attributes.nodeData.t3InCutMode = false;
			pageTree.tree.t3ContextNode.attributes.nodeData.t3InCopyMode = false;
			pageTree.tree.t3ContextNode = null;
		}
	},

	moveNodeToFirstChildOfDestination: function(node, newParent) {
		TYPO3.Components.PageTree.Commands.moveNodeToFirstChildOfDestination(
			node.attributes.nodeData,
			newParent
		);
	},

	moveNodeAfterDestination: function(node, newParent) {
		TYPO3.Components.PageTree.Commands.moveNodeAfterDestination(
			node.attributes.nodeData,
			newParent
		);
	},

	insertNodeAfterDestination: function(node, callback) {
		TYPO3.Components.PageTree.Commands.insertNodeAfterDestination(
			node.parentNode.attributes.nodeData,
			node.previousSibling.id,
			node.serverNodeType,
			callback.createDelegate(node)
		);
	},

	insertNodeToFirstChildOfDestination: function(node, callback) {
		TYPO3.Components.PageTree.Commands.insertNodeToFirstChildOfDestination(
			node.parentNode.attributes.nodeData,
			node.serverNodeType,
			callback.createDelegate(node)
		);
	},

	copyNodeToFirstChildOfDestination: function(node, callback) {
		TYPO3.Components.PageTree.Commands.copyNodeToFirstChildOfDestination(
			node.attributes.nodeData,
			node.parentNode.id,
			callback.createDelegate(node)
		);
	},

	copyNodeAfterDestination: function(node, callback) {
		TYPO3.Components.PageTree.Commands.copyNodeAfterDestination(
			node.attributes.nodeData,
			node.previousSibling.id,
			callback.createDelegate(node)
		);
	},

	enablePage: function(node) {
		TYPO3.Components.PageTree.Commands.visiblyNode(
			node.attributes.nodeData,
			function(response) {
				if (this.evaluateResponse(response)) {
					this.updateNode(node, response);
				}
			},
			this
		);
	},

	disablePage: function(node) {
		TYPO3.Components.PageTree.Commands.disableNode(
			node.attributes.nodeData,
			function(response) {
				if (this.evaluateResponse(response)) {
					this.updateNode(node, response);
				}
			},
			this
		);
	},

	updateNode: function(node, updatedNode) {
		updatedNode.uiProvider = TYPO3.Components.PageTree.PageTreeUI;
		var newTreeNode = new Ext.tree.TreeNode(updatedNode);
		node.parentNode.replaceChild(newTreeNode, node);
		newTreeNode.getOwnerTree().refreshNode(newTreeNode);
	},

	singleClick: function(node) {
		TYPO3.Backend.ContentContainer.setUrl(
			TS.PATH_typo3 + currentSubScript + '?id=' + node.attributes.id
		);
	},

	saveTitle: function(node, newText, oldText) {
		if (newText === oldText) {
			return;
		}

		TYPO3.Components.PageTree.Commands.updateLabel(
			node.editNode.attributes.nodeData,
			newText,
			this.evaluateResponse
		);
	}
};