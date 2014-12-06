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

Ext.override(Ext.grid.GridView, {
	beforeColMenuShow : function() {
		var colModel = this.cm,
			colCount = colModel.getColumnCount(),
			colMenu = this.colMenu,
			i, text;

		colMenu.removeAll();

		for (i = 0; i < colCount; i++) {
			if (colModel.config[i].hideable !== false) {
				text = colModel.getColumnHeader(i);
				if (colModel.getColumnId(i) === 'wsSwapColumn') {
					text = TYPO3.l10n.localize('column.wsSwapColumn');
				}
				colMenu.add(new Ext.menu.CheckItem({
					text: text,
					itemId: 'col-' + colModel.getColumnId(i),
					checked: !colModel.isHidden(i),
					disabled: colModel.config[i].hideable === false,
					hideOnClick: false
				}));
			}
		}
	}
});

/** override mousedown for grid to select checkbox respecting singleSelect */
Ext.override(Ext.grid.CheckboxSelectionModel, {
	handleMouseDown: function(g, rowIndex, e) {
		e.stopEvent();
		if (this.isSelected(rowIndex)) {
			this.deselectRow(rowIndex);
		} else {
			this.selectRow(rowIndex, true);
			this.grid.getView().focusRow(rowIndex);
		}
	}
});

TYPO3.Workspaces.SelectionModel = new Ext.grid.CheckboxSelectionModel({
	singleSelect: false,
	hidden: true,
	listeners: {
		beforerowselect : function (selection, rowIndex, keep, rec) {
			if (rec.json.allowedAction_nextStage || rec.json.allowedAction_prevStage || rec.json.allowedAction_swap) {
				return true;
			} else {
				return false;
			}
		},
		selectionchange: function (selection) {
			var record = selection.grid.getSelectionModel().getSelections();
			if (record.length > 0) {
				TYPO3.Workspaces.Toolbar.selectStateActionCombo.setDisabled(false);
				TYPO3.Workspaces.Toolbar.selectionActionCombo.setDisabled(false);
				TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.setDisabled(true);
			} else {
				TYPO3.Workspaces.Toolbar.selectStateActionCombo.setDisabled(true);
				TYPO3.Workspaces.Toolbar.selectionActionCombo.setDisabled(true);
				TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.setDisabled(false);
			}
		}
	}
});

TYPO3.Workspaces.WorkspaceGrid = new Ext.grid.GridPanel({
	initColModel: function() {
		if (TYPO3.settings.Workspaces.isLiveWorkspace) {
			this.colModel = new Ext.grid.ColumnModel({
				columns: [
					TYPO3.Workspaces.RowExpander,
					{id: 'uid', dataIndex : 'uid', width: 40, sortable: true, header : TYPO3.l10n.localize('column.uid'), hidden: true, filterable : true },
					{id: 't3ver_oid', dataIndex : 't3ver_oid', width: 40, sortable: true, header : TYPO3.l10n.localize('column.oid'), hidden: true, filterable : true },
					{id: 'workspace_Title', dataIndex : 'workspace_Title', width: 120, sortable: true, header : TYPO3.l10n.localize('column.workspaceName'), hidden: true, filter : {type : 'string'}},
					TYPO3.Workspaces.Configuration.WsPath,
					TYPO3.Workspaces.Configuration.LivePath,
					TYPO3.Workspaces.Configuration.WsTitleWithIcon,
					TYPO3.Workspaces.Configuration.TitleWithIcon,
					TYPO3.Workspaces.Configuration.ChangeDate,
					TYPO3.Workspaces.Configuration.Integrity,
					TYPO3.Workspaces.Configuration.Language
				].concat(TYPO3.Workspaces.Helpers.getAdditionalColumnHandler()),
				listeners: {
					columnmoved: TYPO3.Workspaces.Actions.updateColModel,
					hiddenchange: TYPO3.Workspaces.Actions.updateColModel
				}
			});
		} else {
				this.colModel = new Ext.grid.ColumnModel({
				columns: [
					TYPO3.Workspaces.SelectionModel,
					TYPO3.Workspaces.RowExpander,
					{id: 'uid', dataIndex : 'uid', width: 40, sortable: true, header : TYPO3.l10n.localize('column.uid'), hidden: true, filterable : true },
					{id: 't3ver_oid', dataIndex : 't3ver_oid', width: 40, sortable: true, header : TYPO3.l10n.localize('column.oid'), hidden: true, filterable : true },
					{id: 'workspace_Title', dataIndex : 'workspace_Title', width: 120, sortable: true, header : TYPO3.l10n.localize('column.workspaceName'), hidden: true, filter : {type : 'string'}},
					TYPO3.Workspaces.Configuration.WsPath,
					TYPO3.Workspaces.Configuration.LivePath,
					TYPO3.Workspaces.Configuration.WsTitleWithIcon,
					TYPO3.Workspaces.Configuration.SwapButton,
					TYPO3.Workspaces.Configuration.TitleWithIcon,
					TYPO3.Workspaces.Configuration.ChangeDate,
					TYPO3.Workspaces.Configuration.Stage,
					TYPO3.Workspaces.Configuration.RowButtons,
					TYPO3.Workspaces.Configuration.Integrity,
					TYPO3.Workspaces.Configuration.Language
				].concat(TYPO3.Workspaces.Helpers.getAdditionalColumnHandler()),
				listeners: {
					columnmoved: TYPO3.Workspaces.Actions.updateColModel,
					hiddenchange: TYPO3.Workspaces.Actions.updateColModel
				}
			});
		}
	},
	border : true,
	store : TYPO3.Workspaces.MainStore,
	colModel : null,
	sm: TYPO3.Workspaces.SelectionModel,
	loadMask : true,
	height: 630,
	stripeRows: true,
		// below the grid we need 40px space for the legend
	heightOffset: 40,
	plugins : [
		TYPO3.Workspaces.RowExpander,
		TYPO3.Workspaces.Configuration.GridFilters,
		new Ext.ux.plugins.FitToParent()
	],
	view : new Ext.grid.GroupingView({
		forceFit: true,
		groupTextTpl : '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "' + TYPO3.l10n.localize('items') + '" : "' + TYPO3.l10n.localize('item') + '"]})',
		enableGroupingMenu: false,
  		enableNoGroups: false,
		hideGroupedColumn: true
	}),
	bbar : TYPO3.Workspaces.Toolbar.FullBottomBar,
	tbar : TYPO3.Workspaces.Toolbar.FullTopToolbar
});