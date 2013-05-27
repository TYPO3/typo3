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
				TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.hide();
			} else {
				TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.show();
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
						id: 'executeMassActionCancleButton',
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
	tpl: '<tpl for="."><div class="x-combo-list-item"><span class="{cls}">&nbsp;</span> {title}</div></tpl>',
	store: new Ext.data.DirectStore({
		storeId: 'languages',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'uid',
		fields: [
			{name : 'uid'},
			{name : 'title'},
			{name : 'cls'}
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
	(TYPO3.settings.Workspaces.isLiveWorkspace == true) ? {hidden: true} : TYPO3.Workspaces.Toolbar.selectStateActionCombo,
	(TYPO3.settings.Workspaces.isLiveWorkspace == true) ? {hidden: true} : '-',
	(TYPO3.settings.Workspaces.isLiveWorkspace == true) ? {hidden: true} : TYPO3.Workspaces.Toolbar.selectStateMassActionCombo,
	{xtype: 'tbfill'},
	TYPO3.Workspaces.Toolbar.Pager
];
