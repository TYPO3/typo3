Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.ContextMenuActions = {
	removeNode: function(node) {
		if (node.parentNode) {
			node.remove();
		}
	},

	stub: function() {
		alert('Just a Stub! Don\'t Panic!');
	},

	viewPage: function(node) {
		TYPO3.Components.PageTree.DataProvider.getViewLink(
				node.id,
				(TYPO3.configuration.workspaceFrontendPreviewEnabled && TYPO3.configuration.currentWorkspace != 0),
				  function(result) {
					  openUrlInWindow(result, 'typo3-contextMenu-view');
				  }
				);
	},

	editPageProperties: function(node) {
		TYPO3.Backend.ContentContainer.setUrl(
				'alt_doc.php?edit[pages][' + node.attributes.properties.realId + ']=edit'
				);
	},

	newPageWizard: function(node) {
		TYPO3.Backend.ContentContainer.setUrl(
				'db_new.php?id=' + node.attributes.properties.realId + '&pagesOnly=1'
				);
	},

	openInfoPopUp: function(node) {
		launchView('pages', node.attributes.properties.realId);
	},

	openHistoryPopUp: function(node) {
		TYPO3.Backend.ContentContainer.setUrl(
				'show_rechis.php?element=pages:' + node.attributes.properties.realId
				);
	},

	enablePage: function(node) {
		this.tooglePageVisibility(node, false);
	},

	disablePage: function(node) {
		this.tooglePageVisibility(node, true);
	},

	tooglePageVisibility: function(node, enabled) {
		TYPO3.Components.PageTree.DataProvider.tooglePageVisibility(
				node.id,
				enabled,
				   function(updatedNodeFromServer) {
					   updatedNodeFromServer.uiProvider = TYPO3.Components.PageTree.PageTreeUI;
					   var newTreeNode = new Ext.tree.TreeNode(updatedNodeFromServer);
					   node.parentNode.replaceChild(newTreeNode, node);
				   }
				);
	}
};