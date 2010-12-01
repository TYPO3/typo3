Ext.ns('TYPO3.Components.filelist.SelectedFilesView');

TYPO3.Components.filelist.SelectedFilesView.Bootstrap = Ext.apply(new Ext.util.Observable, {
	initialize: function () {
		TYPO3.Components.filelist.ComponentRegistry.registerComponent({
			region:'east',
			component: TYPO3.Components.filelist.SelectedFilesView
		});
	}
});
TYPO3.Components.filelist.registerBootstrap(TYPO3.Components.filelist.SelectedFilesView.Bootstrap);
