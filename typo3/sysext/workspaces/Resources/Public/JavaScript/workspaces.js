/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

Ext.ns('TYPO3.Workspaces');

TYPO3.Workspaces.App = {

	init : function() {
		// With a large amount of unpublished changes, the workspace grid requires a longer Ajax timeout
		Ext.Ajax.timeout = 120000;

		TYPO3.Workspaces.WorkspaceGrid.initColModel();
		TYPO3.Workspaces.WorkspaceGrid.render('workspacegrid');

		TYPO3.Workspaces.MainStore.load();
		TYPO3.Workspaces.Toolbar.selectActionStore.load();
		TYPO3.Workspaces.Toolbar.selectMassActionStore.load();
		TYPO3.Workspaces.Toolbar.LanguageSelector.getStore().load();
	}
};

Ext.onReady(function() {
	Ext.state.Manager.setProvider(new TYPO3.state.ExtDirectProvider({
		key: 'moduleData.Workspaces.States',
		autoRead: false
	}));

	if (Ext.isObject(TYPO3.settings.Workspaces.States)) {
		Ext.state.Manager.getProvider().initState(TYPO3.settings.Workspaces.States);
	}

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
	// late binding of ExtDirect
	TYPO3.Workspaces.Toolbar.LanguageSelector.getStore().proxy = new Ext.data.DirectProxy({
		directFn : TYPO3.Workspaces.ExtDirect.getSystemLanguages
	});

	TYPO3.Workspaces.RowExpander.detailStore.proxy = new Ext.data.DirectProxy({
		directFn: TYPO3.Workspaces.ExtDirect.getRowDetails
	});
	// late binding of ExtDirect
	TYPO3.Workspaces.MainStore.proxy = new Ext.data.DirectProxy({
		directFn : TYPO3.Workspaces.ExtDirect.getWorkspaceInfos
	});

	// Workspace Tabs are not used in
	// frontend list preview
	if (Ext.get('workspacetabs')) {
		TYPO3.Workspaces.Tabs = new Ext.Panel({
			renderTo: 'workspacetabs',
			autoWidth: true,
			layout: 'fit',
			items: [
				{
					xtype: 'WorkspacesTabPanel',
					unstyled: true,
					items: TYPO3.settings.Workspaces.workspaceTabs,
					activeTab: 'workspace-' + TYPO3.settings.Workspaces.activeWorkspaceId
				}
			]
		});
	}

	// fire grid
	var WS = new TYPO3.Workspaces.App.init();

});
