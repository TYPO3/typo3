Ext.ns('TYPO3.Components.filelist.FileList');

TYPO3.Components.filelist.FileList.Bootstrap = Ext.apply(new Ext.util.Observable, {
	initialize: function () {
		TYPO3.Components.filelist.ComponentRegistry.registerComponent({
			region:'center',
			component: TYPO3.Components.filelist.FileList
		});
	}
});
TYPO3.Components.filelist.registerBootstrap(TYPO3.Components.filelist.FileList.Bootstrap);
