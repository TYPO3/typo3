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
					text = TYPO3.lang["column.wsSwapColumn"];
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

TYPO3.Workspaces.SelectionModel = new Ext.grid.CheckboxSelectionModel({
	singleSelect: false,
	hidden: true,
	listeners: {
		selectionchange: function (selection) {
			var record = selection.grid.getSelectionModel().getSelections();
			if (record.length > 0) {
				TYPO3.Workspaces.Toolbar.selectStateActionCombo.setDisabled(false);
				TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.setDisabled(true);
			} else {
				TYPO3.Workspaces.Toolbar.selectStateActionCombo.setDisabled(true);
				TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.setDisabled(false);
			}
		}
	}
});

TYPO3.Workspaces.WorkspaceGrid = new Ext.grid.GridPanel({
	border : true,
	store : TYPO3.Workspaces.MainStore,
	colModel : new Ext.grid.ColumnModel({
		columns: [
			TYPO3.Workspaces.SelectionModel,
			TYPO3.Workspaces.RowExpander,
			{id: 'uid', dataIndex : 'uid', width: 40, sortable: true, header : TYPO3.lang["column.uid"], hidden: true, filterable : true },
			{id: 't3ver_oid', dataIndex : 't3ver_oid', width: 40, sortable: true, header : TYPO3.lang["column.oid"], hidden: true, filterable : true },
			{id: 'workspace_Title', dataIndex : 'workspace_Title', width: 120, sortable: true, header : TYPO3.lang["column.workspaceName"], hidden: true, filter : {type : 'string'}},
			TYPO3.Workspaces.Configuration.WsPath,
			TYPO3.Workspaces.Configuration.LivePath,
			TYPO3.Workspaces.Configuration.WsTitleWithIcon,
			TYPO3.Workspaces.Configuration.SwapButton,
			TYPO3.Workspaces.Configuration.TitleWithIcon,
			TYPO3.Workspaces.Configuration.ChangeDate,
			TYPO3.Workspaces.Configuration.ChangeState,
			TYPO3.Workspaces.Configuration.Stage,
			TYPO3.Workspaces.Configuration.RowButtons
		],
		listeners: {
			columnmoved: function(colModel) {
				TYPO3.Workspaces.Actions.updateColModel(colModel);
			},
			hiddenchange: function(colModel) {
				TYPO3.Workspaces.Actions.updateColModel(colModel);
			}
		}
	}),
	sm: TYPO3.Workspaces.SelectionModel,
	loadMask : true,
	height: 630,
	stripeRows: true,
	plugins : [
		TYPO3.Workspaces.RowExpander,
		TYPO3.Workspaces.Configuration.GridFilters,
		new Ext.ux.plugins.FitToParent()
	],
	view : new Ext.grid.GroupingView({
		forceFit: true,
		groupTextTpl : '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "' + TYPO3.lang["items"] + '" : "' + TYPO3.lang["item"] + '"]})',
		enableGroupingMenu: false,
  		enableNoGroups: false,
		hideGroupedColumn: true
	}),
	bbar : TYPO3.Workspaces.Toolbar.FullBottomBar,
	tbar : TYPO3.Workspaces.Toolbar.FullTopToolbar
});
