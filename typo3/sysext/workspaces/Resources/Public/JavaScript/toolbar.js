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

TYPO3.Workspaces.Toolbar = {};

TYPO3.Workspaces.Toolbar.search =  new Ext.app.SearchField({
	store: TYPO3.Workspaces.MainStore,
	trigger1Class : 't3-icon t3-icon-actions t3-icon-actions-input t3-icon-input-clear t3-tceforms-input-clearer',
	trigger2Class : 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-tree-search-open',
	width: 200
});

TYPO3.Workspaces.Toolbar.selectActionStore = new Ext.data.DirectStore({
	storeId : 'stagesService',
	root : 'data',
	totalProperty : 'total',
	idProperty : 'uid',
	fields : [
		{name : 'uid'},
		{name : 'title'}
	],
	listeners : {
		load : function(store, records) {
			if (records.length == 0) {
				TYPO3.Workspaces.Toolbar.selectStateActionCombo.hide();
			} else {
				TYPO3.Workspaces.Toolbar.selectStateActionCombo.show();
				TYPO3.Workspaces.WorkspaceGrid.colModel.setHidden(0, false);
			}
		}
	}
});

TYPO3.Workspaces.Toolbar.selectStateActionCombo = new Ext.form.ComboBox({
	width: 150,
	listWidth: 350,
	lazyRender: true,
	valueField: 'uid',
	displayField: 'title',
	mode: 'local',
	emptyText: TYPO3.l10n.localize('chooseAction'),
	selectOnFocus: true,
	disabled : true,
	hidden : true,	 // we hide it by default and show it in case there are any actions available
	triggerAction: 'all',
	editable: false,
	forceSelection: true,
	store: TYPO3.Workspaces.Toolbar.selectActionStore,
	listeners: {
		'select' : function () {
			var selection = TYPO3.Workspaces.WorkspaceGrid.getSelectionModel().getSelections();
			var nextStage = this.getValue();

			// Use integrity check since "publish execute" stage is effective
			if (nextStage == -20) {
				var parameters = {
					type: 'selection',
					selection: TYPO3.Workspaces.Helpers.getElementsArrayOfSelectionForIntegrityCheck(selection)
				};

				TYPO3.Workspaces.Actions.checkIntegrity(parameters, function() {
					TYPO3.Workspaces.Actions.sendToSpecificStageWindow(selection, nextStage);
				});
			} else {
				TYPO3.Workspaces.Actions.sendToSpecificStageWindow(selection, nextStage);
			}
		}
	}
});

TYPO3.Workspaces.Toolbar.selectMassActionStore = new Ext.data.DirectStore({
	storeId : 'stagesService',
	root : 'data',
	totalProperty : 'total',
	idProperty : 'action',
	fields : [
		{name : 'action'},
		{name : 'title'}
	],
	listeners : {
		load : function(store, records) {
			if (records.length == 0 || TYPO3.settings.Workspaces.singleView === '1') {
				TYPO3.Workspaces.Toolbar.selectionActionCombo.hide();
				TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.hide();
			} else {
				TYPO3.Workspaces.Toolbar.selectionActionCombo.show();
				TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.show();
			}
		}
	}
});

TYPO3.Workspaces.Toolbar.selectionActionCombo = new Ext.form.ComboBox({
	width: 150,
	lazyRender: true,
	valueField: 'action',
	displayField: 'title',
	mode: 'local',
	emptyText: 'choose selection action',
	selectOnFocus: true,
	triggerAction: 'all',
	editable: false,
	disabled : true, // disabled per default, enabled if selections are done in the grid
	hidden : true, // hidden per default, shown if actions are available
	forceSelection: true,
	store: TYPO3.Workspaces.Toolbar.selectMassActionStore,
	listeners: {
		'select' : function(combo, record) {
			var label;
			var checkIntegrity = false;
			var selection = TYPO3.Workspaces.Helpers.getElementsArrayOfSelectionForIntegrityCheck(
				TYPO3.Workspaces.WorkspaceGrid.getSelectionModel().getSelections()
			);

			switch (record.data.action) {
				case 'publish':
					label = TYPO3.l10n.localize('tooltip.publishSelected');
					checkIntegrity = true;
					break;
				case 'swap':
					label = TYPO3.l10n.localize('tooltip.swapSelected');
					checkIntegrity = true;
					break;
				case 'discard':
					label = TYPO3.l10n.localize('tooltip.discardSelected');
					break;
			}

			top.TYPO3.Windows.close('executeSelectionActionWindow');

			var configuration = {
				id: 'executeSelectionActionWindow',
				title: TYPO3.l10n.localize('window.selectionAction.title'),
				items: [
					{
						xtype: 'form',
						id: 'executeSelectionActionForm',
						width: '100%',
						html: label,
						bodyStyle: 'padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;'
					}
				],
				buttons: [
					{
						id: 'executeSelectionActionOkButton',
						data: { action: record.data.action, selection: selection },
						scope: this,
						text: TYPO3.l10n.localize('ok'),
						disabled:false,
						handler: function(event) {
							top.Ext.getCmp('executeSelectionActionForm').update('Working...');
							top.Ext.getCmp('executeSelectionActionOkButton').disable();
							TYPO3.Workspaces.ExtDirectActions.executeSelectionAction(event.data, function(response) {
								top.Ext.getCmp('executeSelectionActionOkButton').hide();
								top.Ext.getCmp('executeSelectionActionCancelButton').setText(TYPO3.lang.close);
								if (response.error) {
									top.Ext.getCmp('executeSelectionActionForm').update('<strong>' + TYPO3.l10n.localize('status.error') + ':</strong> ' + response.error);
								} else {
									top.Ext.getCmp('executeSelectionActionForm').update(TYPO3.l10n.localize('runMassAction.done').replace('%d', response.total));
									top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
								}
							});
						}
					},
					{
						id: 'executeSelectionActionCancelButton',
						scope: this,
						text: TYPO3.l10n.localize('cancel'),
						handler: function() {
							top.TYPO3.Windows.close('executeSelectionActionWindow');
							top.TYPO3.ModuleMenu.App.reloadFrames();
						}
					}
				]
			};

			if (checkIntegrity) {
				var parameters = {
					type: 'selection',
					selection: selection
				};

				TYPO3.Workspaces.Actions.checkIntegrity(parameters, function() {
					top.TYPO3.Windows.showWindow(configuration);
				});
			} else {
				top.TYPO3.Windows.showWindow(configuration);
			}
		}
	}
});

TYPO3.Workspaces.Toolbar.selectStateMassActionCombo = new Ext.form.ComboBox({
	width: 150,
	lazyRender: true,
	valueField: 'action',
	displayField: 'title',
	mode: 'local',
	emptyText: TYPO3.l10n.localize('chooseMassAction'),
	selectOnFocus: true,
	triggerAction: 'all',
	editable: false,
	hidden : true,	 // we hide it by default and show it in case there are any actions available
	forceSelection: true,
	store: TYPO3.Workspaces.Toolbar.selectMassActionStore,
	listeners: {
		'select' : function (combo, record) {
			var label = '';
			var affectWholeWorkspaceWarning = TYPO3.l10n.localize('tooltip.affectWholeWorkspace');
			var language = TYPO3.Workspaces.MainStore.baseParams.language;
			var checkIntegrity = false;

			switch (record.data.action) {
				case 'publish':
					label = TYPO3.l10n.localize('tooltip.publishAll');
					checkIntegrity = true;
					break;
				case 'swap':
					label = TYPO3.l10n.localize('tooltip.swapAll');
					checkIntegrity = true;
					break;
				case 'discard':
					label = TYPO3.l10n.localize('tooltip.discardAll');
					break;
			}
			top.TYPO3.Windows.close('executeMassActionWindow');

			var configuration = {
				id: 'executeMassActionWindow',
				title: TYPO3.l10n.localize('window.massAction.title'),
				items: [
					{
						xtype: 'form',
						id: 'executeMassActionForm',
						width: '100%',
						html: label + '<br /><br />' + affectWholeWorkspaceWarning,
						bodyStyle: 'padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;'
					},
					{
						xtype: 'progress',
						id: 'executeMassActionProgressBar',
						autoWidth: true,
						autoHeight: true,
						hidden: true,
						value: 0
					}
				],
				buttons: [
					{
						id: 'executeMassActionOkButton',
						data: record.data,
						scope: this,
						text: TYPO3.l10n.localize('ok'),
						disabled:false,
						handler: function(event) {
							TYPO3.Workspaces.Actions.triggerMassAction(
								event.data.action,
								language
							);
						}
					},
					{
						id: 'executeMassActionCancelButton',
						scope: this,
						text: TYPO3.l10n.localize('cancel'),
						handler: function() {
							top.TYPO3.Windows.close('executeMassActionWindow');
							// if clicks during action - this also interrupts the running process -- not the nices way but efficient
							top.TYPO3.ModuleMenu.App.reloadFrames();
						}
					}
				]
			};

			if (checkIntegrity && language != 'all') {
				var parameters = {
					type: 'all',
					language: language
				};
				TYPO3.Workspaces.Actions.checkIntegrity(parameters, function() {
					top.TYPO3.Windows.showWindow(configuration);
				});
			} else {
				top.TYPO3.Windows.showWindow(configuration);
			}
		}
	}
});

TYPO3.Workspaces.Toolbar.Pager = new Ext.PagingToolbar({
	store :  TYPO3.Workspaces.MainStore,
	pageSize : 30,
	displayInfo: false,
	plugins : [ TYPO3.Workspaces.Configuration.GridFilters ]
});

/****************************************************
 * Depth menu
 ****************************************************/
TYPO3.Workspaces.Toolbar.depthFilter = new Ext.form.ComboBox({
	width: 150,
	lazyRender: true,
	valueField: 'depth',
	displayField: 'label',
	id: 'depthSelector',
	mode: 'local',
	emptyText: TYPO3.l10n.localize('depth'),
	selectOnFocus: true,
	triggerAction: 'all',
	editable: false,
	forceSelection: true,
	hidden: (TYPO3.settings.Workspaces.singleView === '1'),
	store: new Ext.data.SimpleStore({
		autoLoad: true,
		fields: ['depth','label'],
		data : [
			['0', TYPO3.l10n.localize('depth_0')],
			['1', TYPO3.l10n.localize('depth_1')],
			['2', TYPO3.l10n.localize('depth_2')],
			['3', TYPO3.l10n.localize('depth_3')],
			['4', TYPO3.l10n.localize('depth_4')],
			['999', TYPO3.l10n.localize('depth_infi')]
		]
	}),
	value: 999,
	listeners: {
		'select': {
			fn: function(cmp, rec, index) {
				var depth = rec.get('depth');
				TYPO3.Workspaces.MainStore.setBaseParam('depth', depth);
				TYPO3.Workspaces.MainStore.load({
					params: {
						wsId: 0
					}
				});
			}
		}
	}
});

TYPO3.Workspaces.Toolbar.LanguageSelector = new Ext.form.ComboBox({
	width: 150,
	listWidth: 350,
	lazyRender: true,
	valueField: 'uid',
	displayField: 'title',
	mode: 'local',
	emptyText: TYPO3.l10n.localize('language.selectLanguage'),
	selectOnFocus: true,
	triggerAction: 'all',
	editable: false,
	forceSelection: true,
	tpl: '<tpl for="."><div class="x-combo-list-item">{icon} {title}</div></tpl>',
	store: new Ext.data.DirectStore({
		storeId: 'languages',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'uid',
		fields: [
			{name : 'uid'},
			{name : 'title'},
			{name : 'icon'}
		],
		listeners: {
			load: function() {
				TYPO3.Workspaces.Toolbar.LanguageSelector.setValue(TYPO3.settings.Workspaces.language);
			}
		}
	}),
	listeners: {
		select: function (comboBox, record, index) {
			TYPO3.Workspaces.ExtDirectActions.saveLanguageSelection(this.getValue());
			TYPO3.Workspaces.MainStore.setBaseParam('language', this.getValue());
			TYPO3.Workspaces.MainStore.load();
		}
	}
});

TYPO3.Workspaces.Toolbar.FullTopToolbar = [
	TYPO3.Workspaces.Toolbar.depthFilter,
	'-',
	TYPO3.Workspaces.Toolbar.LanguageSelector,
	{xtype: 'tbfill'},
	TYPO3.Workspaces.Toolbar.search
];

TYPO3.Workspaces.Toolbar.FullBottomBar = [
	(TYPO3.settings.Workspaces.isLiveWorkspace == true || TYPO3.settings.Workspaces.allView) ? {hidden: true} : TYPO3.Workspaces.Toolbar.selectStateActionCombo,
	(TYPO3.settings.Workspaces.isLiveWorkspace == true || TYPO3.settings.Workspaces.allView) ? {hidden: true} : '-',
	(TYPO3.settings.Workspaces.isLiveWorkspace == true || TYPO3.settings.Workspaces.allView) ? {hidden: true} : TYPO3.Workspaces.Toolbar.selectionActionCombo,
	(TYPO3.settings.Workspaces.isLiveWorkspace == true || TYPO3.settings.Workspaces.allView) ? {hidden: true} : '-',
	(TYPO3.settings.Workspaces.isLiveWorkspace == true || TYPO3.settings.Workspaces.allView) ? {hidden: true} : TYPO3.Workspaces.Toolbar.selectStateMassActionCombo,
	{xtype: 'tbfill'},
	TYPO3.Workspaces.Toolbar.Pager
];
