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
TYPO3.Workspaces.Configuration.StoreFieldArray = [
	{name : 'table'},
	{name : 'uid', type : 'int'},
	{name : 't3ver_oid', type : 'int'},
	{name : 'livepid', type : 'int'},
	{name : 'stage', type: 'int'},
	{name : 'change',type : 'int'},
	{name : 'label_Live'},
	{name : 'label_Workspace'},
	{name : 'label_Stage'},
	{name : 'workspace_Title'},
	{name : 'actions'},
	{name : 'icon_Workspace'},
	{name : 'icon_Live'},
	{name : 'path_Live'},
	{name : 'path_Workspace'},
	{name : 'state_Workspace'},
	{name : 'workspace_Tstamp'},
	{name : 'workspace_Formated_Tstamp'},
	{name : 'allowedAction_nextStage'},
	{name : 'allowedAction_prevStage'},
	{name : 'allowedAction_swap'},
	{name : 'allowedAction_delete'},
	{name : 'allowedAction_edit'},
	{name : 'allowedAction_editVersionedPage'},
	{name : 'allowedAction_view'}
	
];

TYPO3.Workspaces.Configuration.WsPath = {
	id: 'path_Workspace'
	,dataIndex : 'path_Workspace'
	,width: 120
	,hidden: true
	,hideable: true
	,sortable: true
	,header : 'Path'
	,renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var path = record.json.path_Workspace;
		return path;
	}
	,filter : {type: 'string'}
};
TYPO3.Workspaces.Configuration.LivePath = {
	id: 'path_Live'
	,dataIndex : 'path_Live'
	,width: 120
	,hidden: true
	,hideable: true
	,sortable: false
	,header : 'Live Path'
	,renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var path = record.json.path_Live;
		return path;
	}
	,filter : {type: 'string'}
};
TYPO3.Workspaces.Configuration.WsTitleWithIcon = {
	id: 'label_Workspace'
	,dataIndex : 'label_Workspace'
	,width: 120
	,hideable: false
	,sortable: true
	,header : 'Change'
	,renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var dekoClass = 'item-state-' + record.json.state_Workspace;
		value = "<span class=\"" + dekoClass + "\">" + value + "</span>";
		if (record.json.icon_Live === record.json.icon_Workspace) {
			return value;
		} else {
			return "<span class=\"" + record.json.icon_Workspace + "\">&nbsp;</span>&nbsp;" + value;
		}

	}
	,filter : {type: 'string'}
};
TYPO3.Workspaces.Configuration.TitleWithIcon = {
	id: 'label_Live'
	,dataIndex : 'label_Live'
	,width: 120
	,hideable: false
	,sortable: true
	,header : 'Live'
	,renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		if (record.json.state_Workspace == 'unhidden') {
			var dekoClass = 'item-state-hidden';
		} else {
			var dekoClass = '';
		}

		value = "<span class=\"" + dekoClass + "\">" + value + "</span>";
		return "<span class=\"" + record.json.icon_Live + "\">&nbsp;</span>&nbsp;" + value;
	}
	,filter : {type: 'string'}
};
TYPO3.Workspaces.Configuration.ChangeState = {
	id: 'state-change'
	,dataIndex : 'change'
	,width: 80
	,sortable: true
	,header : 'Difference'
	,renderer: function(value, metaData) {
		return value + "%";
	}
	,filter : {type: 'numeric'}
};
TYPO3.Workspaces.Configuration.ChangeDate = {
	id: 'workspace_Tstamp'
	,dataIndex : 'workspace_Tstamp'
	,width: 120
	,sortable: true
	,header : 'Datetime'
	,renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		return record.json.workspace_Formated_Tstamp;
	}
	,hidden: true
	,filter : {type : 'string'}
};

TYPO3.Workspaces.Configuration.SendToPrevStageButton = {
	xtype: 'actioncolumn'
	,header:''
	,width: 18
	,items:[
		{
			iconCls: 't3-icon t3-icon-extensions t3-icon-extensions-workspaces t3-icon-workspaces-sendtoprevstage'
			,tooltip: 'Send to prev stage'
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				TYPO3.Workspaces.Actions.sendToPrevStageWindow(record.json.table, record.json.uid);
			}
		}
	]
};

TYPO3.Workspaces.Configuration.SendToNextStageButton = {
	xtype: 'actioncolumn'
	,header:''
	,width: 18
	,items:[
		{},{	// empty dummy important!!!!
			iconCls: 't3-icon t3-icon-extensions t3-icon-extensions-workspaces t3-icon-workspaces-sendtonextstage'
			,tooltip:'Send to next stage'
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				TYPO3.Workspaces.Actions.sendToNextStageWindow(record.json.table, record.json.uid, record.json.t3ver_oid);
			}
		}
	]
};

TYPO3.Workspaces.Configuration.Stage = {
	id: 'label_Stage'
	,dataIndex : 'label_Stage'
	,width: 80
	,sortable: true
	,header : 'Stage'
	,hidden: false
	,filter : {
		type : 'string'
	}
	,renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var returnCode = '';
		if (record.json.allowedAction_prevStage) {
			var prevButton = new Ext.grid.ActionColumn(TYPO3.Workspaces.Configuration.SendToPrevStageButton);
			returnCode += prevButton.renderer(1, metaData, record, rowIndex, 1, store);
		} else {
			returnCode += "<span class=\"t3-icon t3-icon-empty t3-icon-empty-empty\">&nbsp;</span>";
		}
		returnCode += record.json.label_Stage;
		if (record.json.allowedAction_nextStage) {
			var nextButton = new Ext.grid.ActionColumn(TYPO3.Workspaces.Configuration.SendToNextStageButton);
			returnCode += nextButton.renderer(2, metaData, record, rowIndex, 2, store);
		} else {
			returnCode += "<span class=\"t3-icon t3-icon-empty t3-icon-empty-empty\">&nbsp;</span>";
		}
		return returnCode;
	}
	,processEvent : function(name, e, grid, rowIndex, colIndex){
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
	header: 'Actions',
	width: 50,
	items: [
		{
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-version t3-icon-version-workspace-preview'
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				if (record.json.table == 'pages') {
					TYPO3.Workspaces.Actions.viewSingleRecord(record.json.t3ver_oid);
				} else {
					TYPO3.Workspaces.Actions.viewSingleRecord(record.json.livepid);
				}
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
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-open'
			,tooltip:'Edit element'
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				window.location.href = 'alt_doc.php?returnUrl=' + Ext.urlEncode({}, document.location.href) + '&id=' + TYPO3.settings.Workspaces.id + '&edit[' + record.json.table + '][' + record.json.uid + ']=edit';
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
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-pagemodule-open'
			,tooltip:'Open version of page'
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				if (record.json.table == 'pages') {
					top.loadEditId(record.json.t3ver_oid);
				} else {
					top.loadEditId(record.json.realpid);
				}
			},
			getClass: function(v, meta, rec) {
				if(!rec.json.allowedAction_editVersionedPage) {
					return 'icon-hidden';
				} else {
					return '';
				}
			}
		},
		{
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-version t3-icon-version-document-remove'
			,tooltip:'Remove version document'
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				var configuration = {
					title: 'Remove version from workspace',
					msg: 'Do you really want to remove this version from workspace?',
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
		}
	]
};

TYPO3.Workspaces.Configuration.SwapButton = {
	xtype: 'actioncolumn'
	,header: ''
	,width: 18
	,items: [
		{
			iconCls:'t3-icon t3-icon-actions t3-icon-actions-version t3-icon-version-swap-workspace'
			,tooltip:'Swap live and workspace versions of record.'
			,handler: function(grid, rowIndex, colIndex) {
				var record = TYPO3.Workspaces.MainStore.getAt(rowIndex);
				var configuration = {
					title: 'Swap version',
					msg: 'Do you really want to swap this version?',
					fn: function(result) {
						if (result == 'yes') {
							TYPO3.Workspaces.Actions.swapSingleRecord(record.json.table, record.json.wsversion, record.json.uid);
						}
					}
				};

				top.TYPO3.Dialog.QuestionDialog(configuration);
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

