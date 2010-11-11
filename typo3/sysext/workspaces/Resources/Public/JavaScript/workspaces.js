TYPO3.Workspaces.App = {

	init : function() {
		TYPO3.Workspaces.MainStore.load();
		TYPO3.Workspaces.Toolbar.selectActionStore.load();
		TYPO3.Workspaces.Toolbar.selectMassActionStore.load();
		TYPO3.Workspaces.WorkspaceGrid.render('workspacegrid');
	}
};

Ext.onReady(function() {
	Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
	// Quicktips initialisieren
	Ext.QuickTips.init();

	// rearrange columns in grid
	TYPO3.Workspaces.Actions.loadColModel(TYPO3.Workspaces.WorkspaceGrid);

	// late binding of ExtDirect
	TYPO3.Workspaces.Toolbar.selectMassActionStore.proxy = new Ext.data.DirectProxy({
		directFn : TYPO3.Workspaces.ExtDirectMassActions.getMassStageActions
	});
	// late binding of ExtDirect
	TYPO3.Workspaces.Toolbar.selectActionStore.proxy = new Ext.data.DirectProxy({
		directFn : TYPO3.Workspaces.ExtDirect.getStageActions
	});

	TYPO3.Workspaces.RowDetail.rowDataStore.proxy = new Ext.data.DirectProxy({
		directFn: TYPO3.Workspaces.ExtDirect.getRowDetails
	});
	// late binding of ExtDirect
	TYPO3.Workspaces.MainStore.proxy = new Ext.data.DirectProxy({
		directFn : TYPO3.Workspaces.ExtDirect.getWorkspaceInfos
	});
	// fire grid
	var WS = new TYPO3.Workspaces.App.init();


});
