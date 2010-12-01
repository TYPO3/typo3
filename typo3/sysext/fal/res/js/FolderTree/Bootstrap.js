Ext.ns('TYPO3.Components.filelist.FolderTree');

TYPO3.Components.filelist.FolderTree.Bootstrap = Ext.apply(new Ext.util.Observable, {
	initialize: function () {
		TYPO3.Components.filelist.ComponentRegistry.registerComponent({
			region:'west',
			component: TYPO3.Components.filelist.FolderTree
		});
	}
});
TYPO3.Components.filelist.registerBootstrap(TYPO3.Components.filelist.FolderTree.Bootstrap);
