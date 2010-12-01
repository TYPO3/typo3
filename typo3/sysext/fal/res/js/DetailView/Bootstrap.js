Ext.ns('TYPO3.Components.filelist.DetailView');

TYPO3.Components.filelist.DetailView.Bootstrap = Ext.apply(new Ext.util.Observable, {
	initialize: function () {
		TYPO3.Components.filelist.ComponentRegistry.registerComponent({
			region:'east',
			component: TYPO3.Components.filelist.DetailView
		});
	}
});
TYPO3.Components.filelist.registerBootstrap(TYPO3.Components.filelist.DetailView.Bootstrap);
