/**
 * This is a library of callback actions for the page tree.
 */

Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.PageActions = {
	singleClick: function(node) {
		TYPO3.Backend.ContentContainer.setUrl(
			TS.PATH_typo3 + currentSubScript + '?id=' + node.attributes.properties.realId
		);
	},

	saveTitle: function(node, newText, oldText) {
		if (newText == oldText) {
			return;
		}

		node = node.editNode;
		TYPO3.Components.PageTree.DataProvider.setPageTitle(
			node.id,
			newText,
			node.attributes.properties.textSourceField,
			Ext.emptyFn
		);
	}
};