/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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

Ext.ns('TYPO3.Workspaces');

TYPO3.Workspaces.App = {

	init : function() {
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