TYPO3.Workspaces.Toolbar.actionComboLabel = new Ext.form.Label({
	html: 'Action',
	hidden: true	// initially hidden and shown in case one of the combos gets active
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
TYPO3.Workspaces.Toolbar.selectStateMassActionCombo = new Ext.form.ComboBox({
	width: 150,
	lazyRender: true,
	valueField: 'action',
	displayField: 'title',
	mode: 'local',
	emptyText: 'Choose Mass Action',
	selectOnFocus: true,
	triggerAction: 'all',
	editable: false,
	hidden : true,	 // we hide it by default and show it in case there are any actions available
	forceSelection: true,
	store: TYPO3.Workspaces.Toolbar.selectMassActionStore,
	listeners: {
		beforeshow : function() {
			TYPO3.Workspaces.Toolbar.actionComboLabel.show();
		},
		'select' : function (combo, record) {
			var label = '';
			switch (record.data.action) {
				case 'publish':
					label = 'Really publish entire workspace?';
					break;
				case 'swap':
					label = 'Really swap entire workspace?';
					break;
				case 'release':
					label = 'Really release entire workspace?';
					break;
			}
			var dialog = top.TYPO3.Windows.showWindow({
				id: 'executeMassActionWindow',
				title: 'Prepare to start mass action',
				items: [
					{
						xtype: 'form',
						id: 'executeMassActionForm',
						width: '100%',
						html: label,
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
						text: 'OK',
						disabled:false,
						handler: function(event) {
							TYPO3.Workspaces.Actions.triggerMassAction(event.data.action);
						}
					},
					{
						id: 'executeMassActionCancleButton',
						scope: this,
						text: 'Cancel',
						handler: function() {
							top.TYPO3.Windows.close('executeMassActionWindow');
							// if clicks during action - this also interrupts the running process -- not the nices way but efficient
							top.TYPO3.ModuleMenu.App.reloadFrames();
						}
					}
				]
			});
		}
	}
});

TYPO3.Workspaces.Toolbar.selectPagerSizePerPage = new Ext.form.NumberField({
	width: 50,
	allowDecimals: false,
	allowNegative: false,
	value:10,
	disabled : false,
	editable: true,
	listeners: {
		blur: function(event) {
			TYPO3.Workspaces.Toolbar.Pager.pageSize = this.getValue();
		}
	}
});


TYPO3.Workspaces.Toolbar.selectStateActionCombo = new Ext.form.ComboBox({
	width: 150,
	lazyRender: true,
	valueField: 'uid',
	displayField: 'title',
	mode: 'local',
	emptyText: 'Choose Actions',
	selectOnFocus: true,
	disabled : true,
	hidden : true,	 // we hide it by default and show it in case there are any actions available
	triggerAction: 'all',
	editable: false,
	forceSelection: true,
	store: TYPO3.Workspaces.Toolbar.selectActionStore,
	listeners: {
		beforeshow : function() {
			TYPO3.Workspaces.Toolbar.actionComboLabel.show();
		},
		'select' : function () {
			var selection = TYPO3.Workspaces.WorkspaceGrid.getSelectionModel().getSelections();
			TYPO3.Workspaces.Actions.sendToSpecificStageWindow(selection, this.getValue());
		}
	}
});

TYPO3.Workspaces.Toolbar.Pager = new Ext.PagingToolbar({
	store :  TYPO3.Workspaces.MainStore,
	pageSize : TYPO3.Workspaces.Toolbar.selectPagerSizePerPage.getValue(),
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
	emptyText: TYPO3.lang.depth,
	selectOnFocus: true,
	triggerAction: 'all',
	editable: false,
	forceSelection: true,
	hidden: TYPO3.lang.showDepthMenu,
	store: new Ext.data.SimpleStore({
		autoLoad: true,
		fields: ['depth','label'],
		data : [
			['0', TYPO3.lang.depth_0],
			['1', TYPO3.lang.depth_1],
			['2', TYPO3.lang.depth_2],
			['3', TYPO3.lang.depth_3],
			['4', TYPO3.lang.depth_4],
			['999', TYPO3.lang.depth_infi]
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

TYPO3.Workspaces.Toolbar.FullTopToolbar = [
	TYPO3.Workspaces.Toolbar.actionComboLabel,
	TYPO3.Workspaces.Toolbar.selectStateActionCombo,
	TYPO3.Workspaces.Toolbar.selectStateMassActionCombo,
	{xtype: 'tbfill'},
	TYPO3.Workspaces.Toolbar.search,
	'-',
	TYPO3.Workspaces.Toolbar.depthFilter
];


TYPO3.Workspaces.WorkspaceGrid = new Ext.grid.GridPanel({
	border : true,
	store : TYPO3.Workspaces.MainStore,
	colModel : new Ext.grid.ColumnModel({
		columns: [
			TYPO3.Workspaces.SelectionModel,
			TYPO3.Workspaces.RowExpander,
			{id: 'uid', dataIndex : 'uid', width: 20, sortable: true, header : 'WS-Id', hidden: true, filterable : true },
			{id: 't3ver_oid', dataIndex : 't3ver_oid', width: 20, sortable: true, header : 'Live-Id', hidden: true, filterable : true },
			{id: 'workspace_Title', dataIndex : 'workspace_Title', width: 120, sortable: true, header : 'Workspace', hidden: true, filter : {type : 'string'}},
			TYPO3.Workspaces.Configuration.WsPath,
			TYPO3.Workspaces.Configuration.LivePath,
			TYPO3.Workspaces.Configuration.WsTitleWithIcon,
			TYPO3.Workspaces.Configuration.SwapButton,
			TYPO3.Workspaces.Configuration.TitleWithIcon,
			TYPO3.Workspaces.Configuration.ChangeDate,
			TYPO3.Workspaces.Configuration.ChangeState,
			//TYPO3.Workspaces.Configuration.SendToPrevStageButton,
			TYPO3.Workspaces.Configuration.Stage,
			//TYPO3.Workspaces.Configuration.SendToNextStageButton,
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
		TYPO3.Workspaces.RowExpander
		,TYPO3.Workspaces.Configuration.GridFilters
		,new Ext.ux.plugins.FitToParent()],
	view : new Ext.grid.GroupingView({
		forceFit: true,
		groupTextTpl : '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
	}),
	bbar : [
		{
			xtype: 'label'
			,html: 'Records to display'
		},
		TYPO3.Workspaces.Toolbar.selectPagerSizePerPage,
		TYPO3.Workspaces.Toolbar.Pager
	],
	tbar : TYPO3.Workspaces.Toolbar.FullTopToolbar
});
