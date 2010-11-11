TYPO3.Workspaces.Component = {};

TYPO3.Workspaces.Toolbar = {
	search : new Ext.app.SearchField({
		store: TYPO3.Workspaces.MainStore,
		width: 200
	}),
	selectActionStore : new Ext.data.DirectStore({
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
	}),
	selectMassActionStore : new Ext.data.DirectStore({
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
				if (records.length == 0) {
					TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.hide();
				} else {
					TYPO3.Workspaces.Toolbar.selectStateMassActionCombo.show();
				}
			}
		}
	})
};


TYPO3.Workspaces.RowDetail = {};
TYPO3.Workspaces.RowDetail.rowDataStore = new Ext.data.DirectStore({
	storeId : 'rowDetailService',
	root : 'data',
	totalProperty : 'total',
	idProperty : 'uid',
	fields : [
		{name : 'uid'},
		{name : 't3ver_oid'},
		{name : 'table'},
		{name : 'stage'},
		{name : 'diff'},
		{name : 'path_Live'},
		{name : 'label_Stage'},
		{name : 'stage_position'},
		{name : 'stage_count'},
		{name : 'live_record'},
		{name : 'comments'}
	]
});
TYPO3.Workspaces.RowDetail.rowDetailTemplate = new Ext.XTemplate(
	'<tpl for=".">',
		'<tpl>',
			'<table class="char_select_template" width="100%" cellpadding="0" cellspacing="0" border="0">',
				'<tr>',
					'<td class="char_select_profile_title">',
						'Live Workspace',
					'</td>',
					'<td class="char_select_profile_title">',
						'Workspace Version',
					'</td>',
				'</tr>',
				'<tr>',
					'<td class="char_select_profile_title">',
						'Path: {path_Live}',
					'</td>',
					'<td class="char_select_profile_title">',
						'Current stage step: {label_Stage} ({stage_position}/{stage_count})',
					'</td>',
				'</tr>',
				'<tr>',
					'<td class="char_select_profile_title">',
						'{live_record}<br>',
					'</td>',
					'<td class="char_select_profile_stats">',
						'{diff}<br>',
					'</td>',
				'</tr>',
				'<tr>',
					'<td class="char_select_profile_title">',
						'<br>',
					'</td>',
					'<td class="char_select_profile_stats">',
						'<tpl for="comments">',
							'{user_username} {tstamp} {user_comment} {stage_title}<br />',
						'</tpl>',
					'</td>',
				'</tr>',
			'</table>',
		'</tpl>',
	'</tpl>',
	'<div class="x-clear"></div>'
);
TYPO3.Workspaces.RowDetail.rowDataView = new Ext.DataView({
	store: TYPO3.Workspaces.RowDetail.rowDataStore,
	tpl: TYPO3.Workspaces.RowDetail.rowDetailTemplate
});
Ext.ns('Ext.ux.TYPO3.Workspace');
Ext.ux.TYPO3.Workspace.RowPanel = Ext.extend(Ext.Panel, {
	constructor: function(config) {
		config = config || {
			frame:true,
			width:'100%',
			autoHeight:true,
			layout:'fit',
			title: 'Row details...'
		};
		Ext.apply(this, config);
		Ext.ux.TYPO3.Workspace.RowPanel.superclass.constructor.call(this, config);
	}
});

TYPO3.Workspaces.RowExpander = new Ext.grid.RowExpander({
	remoteDataMethod : function (record, index) {
		TYPO3.Workspaces.RowDetail.rowDataStore.baseParams = {
			uid: record.json.uid,
			table: record.json.table,
			stage: record.json.stage,
			t3ver_oid: record.json.t3ver_oid,
			path_Live: record.json.path_Live,
			label_Stage: record.json.label_Stage
		}
		TYPO3.Workspaces.RowDetail.rowDataStore.load();
		new Ext.ux.TYPO3.Workspace.RowPanel({renderTo: 'remData' + index, items: TYPO3.Workspaces.RowDetail.rowDataView});
	},
	onMouseDown : function(e, t) {
		tObject = Ext.get(t);
		if (tObject.hasClass('x-grid3-row-expander-action')) {
			e.stopEvent();
			if (tObject.hasClass('t3-icon-view-list-expand')) {
				t.className = 'x-grid3-row-expander-action t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-list-collapse';
			} else {
				t.className = 'x-grid3-row-expander-action t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-list-expand';
			}
			var row = e.getTarget('.x-grid3-row');
			this.toggleRow(row);
		}
	},
	renderer : function(v, p, record) {
		p.cellAttr = 'rowspan="2"';
		return '<div class="x-grid3-row-expander-action t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-list-expand">&#160;</div>';
	},
	tpl : new Ext.Template('<h3>Path: {path}</h3>', '<h3>Table: {table}')
});


TYPO3.Workspaces.MainStore = new Ext.data.GroupingStore({
	storeId : 'workspacesMainStore',
	reader : new Ext.data.JsonReader({
		idProperty : 'uid',
		root : 'data',
		totalProperty : 'total'
	}, TYPO3.Workspaces.Configuration.StoreFieldArray),
	groupField: 'path_Workspace',
	remoteGroup: false,
	paramsAsHash : true,
	sortInfo : {
		field : 'label_Live',
		direction : "ASC"
	},
	remoteSort : true,
	baseParams: {
		depth : 990,
		id: TYPO3.settings.Workspaces.id,
		query: '',
		start: 0,
		limit: 10
	},

	showAction : false,
	listeners : {
		beforeload : function() {
		},
		load : function(store, records) {
		},
		datachanged : function(store) {
		},
		scope : this
	}
});
