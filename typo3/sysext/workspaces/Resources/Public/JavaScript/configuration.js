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
TYPO3.Workspaces.Configuration = {};

TYPO3.Workspaces.Configuration.GridFilters = new Ext.ux.grid.GridFilters({
	encode : false, // json encode the filter query
	local : true, // defaults to false (remote filtering)
	filters : [
		{
			type : 'numeric',
			dataIndex : 'uid'
		},
		{
			type : 'string',
			dataIndex : 'workspace_Title'
		},
		{
			type : 'numeric',
			dataIndex : 'languageValue'
		},
		{
			type : 'string',
			dataIndex : 'label_Live'
		},
		{
			type : 'string',
			dataIndex : 'label_Workspace'
		},
		{
			type : 'numeric',
			dataIndex : 'change'
		}
	]
});

TYPO3.Workspaces.Configuration.Integrity = new Ext.grid.Column({
	width: 24,
	hideable: true,
	sortable: false,
	header: '<span class="' + TYPO3.settings.Workspaces.icons.integrity + '">&nbsp;</span>',
	renderer: function(value, meta, record) {
		if (record.json.integrity.status !== 'success') {
			var cls = TYPO3.settings.Workspaces.icons[record.json.integrity.status] + ' t3-visible';
			var title = TYPO3.l10n.localize('status.' + record.json.integrity.status);
			var message = record.json.integrity.messages;

			return '<span class="' + cls + '" ext:qtitle="' + title + '" ext:qtip="' + message + '">&nbsp;</span>';
		}
	}
});
TYPO3.Workspaces.Configuration.WsPath = {
	id: 'path_Workspace',
	dataIndex : 'path_Workspace',
	width: 120,
	hidden: true,
	hideable: false,
	sortable: true,
	header : TYPO3.l10n.localize('column.wsPath'),
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var path = record.json.path_Workspace;
		return path;
	},
	filter : {type: 'string'}
};

TYPO3.Workspaces.Configuration.LivePath = {
	id: 'path_Live',
	dataIndex : 'path_Live',
	width: 120,
	hidden: true,
	hideable: true,
	sortable: true,
	header : TYPO3.l10n.localize('column.livePath'),
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var path = record.json.path_Live;
		return path;
	},
	filter : {type: 'string'}
};

TYPO3.Workspaces.Configuration.WsTitleWithIcon = {
	id: 'label_Workspace',
	dataIndex : 'label_Workspace',
	width: 120,
	hideable: true,
	sortable: true,
	header : TYPO3.l10n.localize('column.wsTitle'),
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var dekoClass = 'item-state-' + record.json.state_Workspace;
		value = "<span class=\"" + dekoClass + "\">" + value + "</span>";
		if (record.json.icon_Live === record.json.icon_Workspace) {
			return value;
		} else {
			return "<span class=\"" + record.json.icon_Workspace + "\">&nbsp;</span>&nbsp;" + value;
		}

	},
	filter : {type: 'string'}
};

TYPO3.Workspaces.Configuration.Language = {
	id: 'language',
	dataIndex: 'languageValue',
	width: 30,
	hideable: true,
	sortable: true,
	header: '<span class="' + TYPO3.settings.Workspaces.icons.language + '">&nbsp;</span>',
	filter: { type: 'string '},
	renderer: function(value, metaData, record) {
		return '<span class="' + record.json.language.cls + '" title="' + record.json.language.title + '">&nbsp;</span>';
	}
};

TYPO3.Workspaces.Configuration.TitleWithIcon = {
	id: 'label_Live',
	dataIndex : 'label_Live',
	width: 120,
	hideable: false,
	sortable: true,
	header : TYPO3.l10n.localize('column.liveTitle'),
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var dekoClass = '';
		if (record.json.state_Workspace == 'unhidden') {
			dekoClass = 'item-state-hidden';
		}

		value = "<span class=\"" + dekoClass + "\">" + value + "</span>";
		return "<span class=\"" + record.json.icon_Live + "\">&nbsp;</span>&nbsp;" + value;
	},
	filter : {type: 'string'}
};

TYPO3.Workspaces.Configuration.ChangeDate = {
	id: 'workspace_Tstamp',
	dataIndex : 'workspace_Tstamp',
	width: 120,
	sortable: true,
	header : TYPO3.l10n.localize('column.changeDate'),
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		return record.json.workspace_Formated_Tstamp;
	},
	hidden: true,
	filter : {type : 'string'}
};

TYPO3.Workspaces.Configuration.SendToPrevStageButton = {
	xtype: 'actioncolumn',
	header:'',
	width: 18,
	hidden: (TYPO3.settings.Workspaces.allView === '1'),
	items:[
		{
			iconCls: 't3-icon t3-icon-extensions t3-icon-extensions-workspaces t3-icon-workspaces-sendtoprevstage',
			tooltip: TYPO3.l10n.localize('tooltip.sendToPrevStage'),
			handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				TYPO3.Workspaces.Actions.sendToPrevStageWindow(record.json.table, record.json.uid);
			}
		}
	]
};

TYPO3.Workspaces.Configuration.SendToNextStageButton = {
	xtype: 'actioncolumn',
	header:'',
	width: 18,
	hidden: (TYPO3.settings.Workspaces.allView === '1'),
	items: [
		{},{	// empty dummy important!!!!
			iconCls: 't3-icon t3-icon-extensions t3-icon-extensions-workspaces t3-icon-workspaces-sendtonextstage',
			tooltip: TYPO3.l10n.localize('tooltip.sendToNextStage'),
			handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				TYPO3.Workspaces.Actions.sendToNextStageWindow(record.json.table, record.json.uid, record.json.t3ver_oid);
			}
		}
	]
};

TYPO3.Workspaces.Configuration.Stage = {
	id: 'label_Stage',
	dataIndex : 'label_Stage',
	width: 80,
	sortable: true,
	header : TYPO3.l10n.localize('column.stage'),
	hidden: false,
	filter : {
		type : 'string'
	},
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var returnCode = '';
		if (record.json.allowedAction_prevStage && TYPO3.settings.Workspaces.allView !== '1') {
			var tempTooltip = TYPO3.Workspaces.Configuration.SendToPrevStageButton.items[0].tooltip;
			TYPO3.Workspaces.Configuration.SendToPrevStageButton.items[0].tooltip += ' &quot;'+ record.json.label_prevStage + '&quot;';
			var prevButton = new Ext.grid.ActionColumn(TYPO3.Workspaces.Configuration.SendToPrevStageButton);
			returnCode += prevButton.renderer(1, metaData, record, rowIndex, 1, store);
			TYPO3.Workspaces.Configuration.SendToPrevStageButton.items[0].tooltip = tempTooltip;
		} else {
			returnCode += "<span class=\"t3-icon t3-icon-empty t3-icon-empty-empty\">&nbsp;</span>";
		}
		returnCode += record.json.label_Stage;
		if (record.json.allowedAction_nextStage && TYPO3.settings.Workspaces.allView !== '1') {
			var tempTooltip = TYPO3.Workspaces.Configuration.SendToNextStageButton.items[1].tooltip;
			TYPO3.Workspaces.Configuration.SendToNextStageButton.items[1].tooltip += ' &quot;'+ record.json.label_nextStage + '&quot;';
			var nextButton = new Ext.grid.ActionColumn(TYPO3.Workspaces.Configuration.SendToNextStageButton);
			returnCode += nextButton.renderer(2, metaData, record, rowIndex, 2, store);
			TYPO3.Workspaces.Configuration.SendToNextStageButton.items[1].tooltip = tempTooltip;
		} else {
			returnCode += "<span class=\"t3-icon t3-icon-empty t3-icon-empty-empty\">&nbsp;</span>";
		}
		return returnCode;
	},
	processEvent : function(name, e, grid, rowIndex, colIndex){
		var m = e.getTarget().className.match(/x-action-col-(\d+)/);
		if(m && m[1] == 0) {
			TYPO3.Workspaces.Configuration.SendToPrevStageButton.items[0].handler(grid, rowIndex, colIndex);
			return false;
		} else if (m && m[1] == 1 ) {
			TYPO3.Workspaces.Configuration.SendToNextStageButton.items[1].handler(grid, rowIndex, colIndex);
			return false;
		}
		return Ext.grid.ActionColumn.superclass.processEvent.apply(this, arguments);
	}
}

TYPO3.Workspaces.Configuration.RowButtons = {
	xtype: 'actioncolumn',
	header: TYPO3.l10n.localize('column.actions'),
	width: 70,
	hideable: false,
	hidden: (TYPO3.settings.Workspaces.allView === '1'),
	menuDisabled: true,
	items: [
		{
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-version t3-icon-version-workspace-preview'
			,tooltip: TYPO3.l10n.localize('tooltip.viewElementAction')
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				TYPO3.Workspaces.Actions.viewSingleRecord(record.json.table, record.json.uid);
			},
			getClass: function(v, meta, rec) {
				if(!rec.json.allowedAction_view) {
					return 'icon-hidden';
				} else {
					return '';
				}
			}
		},
		{
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-open',
			tooltip: TYPO3.l10n.localize('tooltip.editElementAction'),
			handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				var newUrl = 'alt_doc.php?returnUrl=' + encodeURIComponent(document.location.href) + '&id=' + TYPO3.settings.Workspaces.id + '&edit[' + record.json.table + '][' + record.json.uid + ']=edit';
				window.location.href = newUrl;
			},
			getClass: function(v, meta, rec) {
				if(!rec.json.allowedAction_edit) {
					return 'icon-hidden';
				} else {
					return '';
				}
			}
		},
		{
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-pagemodule-open',
			tooltip: TYPO3.l10n.localize('tooltip.openPage'),
			handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				if (record.json.table == 'pages') {
					top.loadEditId(record.json.t3ver_oid);
				} else {
					top.loadEditId(record.json.livepid);
				}
			},
			getClass: function(v, meta, rec) {
				if(!rec.json.allowedAction_editVersionedPage || !top.TYPO3.configuration.pageModule) {
					return 'icon-hidden';
				} else {
					return '';
				}
			}
		},
		{
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-version t3-icon-version-document-remove',
			tooltip: TYPO3.l10n.localize('tooltip.discardVersion'),
			handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				var configuration = {
					title: TYPO3.l10n.localize('window.discard.title'),
					msg: TYPO3.l10n.localize('window.discard.message'),
					fn: function(result) {
						if (result == 'yes') {
							TYPO3.Workspaces.Actions.deleteSingleRecord(record.json.table, record.json.uid);
						}
					}
				};

				top.TYPO3.Dialog.QuestionDialog(configuration);
			},
			getClass: function(v, meta, rec) {
				if(!rec.json.allowedAction_delete) {
					return 'icon-hidden';
				} else {
					return '';
				}
			}
		},
		{
			iconCls: 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-history-open',
			tooltip: TYPO3.l10n.localize('tooltip.showHistory'),
			handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				TYPO3.Workspaces.Helpers.getHistoryWindow({
					table: record.json.table,
					liveId: record.json.t3ver_oid,
					versionId: record.json.uid
				});
			}
		}
	]
};

TYPO3.Workspaces.Configuration.SwapButton = {
	xtype: 'actioncolumn',
	header: '',
	id: 'wsSwapColumn',
	width: 18,
	menuDisabled: true,
	sortable: false,
	hidden: (TYPO3.settings.Workspaces.allView === '1'),
	items: [
		{
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-version t3-icon-version-swap-workspace'
			,tooltip: TYPO3.l10n.localize('tooltip.swap')
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				var parameters = {
					type: 'selection',
					selection: [{
						table: record.json.table,
						versionId: record.json.uid,
						liveId: record.json.t3ver_oid
					}]
				};

				var configuration = {
					title: TYPO3.l10n.localize('window.swap.title'),
					msg: TYPO3.l10n.localize('window.swap.message'),
					fn: function(result) {
						if (result == 'yes') {
							TYPO3.Workspaces.Actions.swapSingleRecord(record.json.table, record.json.t3ver_oid, record.json.uid);
						}
					}
				};

				TYPO3.Workspaces.Actions.checkIntegrity(parameters, function() {
					top.TYPO3.Dialog.QuestionDialog(configuration);
				});
			},
			getClass: function(v, meta, rec) {
				if(!rec.json.allowedAction_swap) {
					return 'icon-hidden';
				} else {
					return '';
				}
			}
		}
	]
};
