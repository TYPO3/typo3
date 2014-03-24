Ext.ns('TYPO3.Workspaces.Configuration');

TYPO3.Workspaces.Configuration.StoreFieldArray = [
	{name : 'Workspaces_Collection', type : 'int'},
	{name : 'Workspaces_CollectionLevel', type : 'int'},
	{name : 'Workspaces_CollectionParent'},
	{name : 'Workspaces_CollectionCurrent'},
	{name : 'Workspaces_CollectionChildren', type : 'int'},
	{name : 'table'},
	{name : 'uid', type : 'int'},
	{name : 't3ver_oid', type : 'int'},
	{name : 't3ver_wsid', type : 'int'},
	{name : 'livepid', type : 'int'},
	{name : 'stage', type: 'int'},
	{name : 'change',type : 'int'},
	{name : 'languageValue'},
	{name : 'language'},
	{name : 'integrity'},
	{name : 'label_Live'},
	{name : 'label_Workspace'},
	{name : 'label_Stage'},
	{name : 'label_nextStage'},
	{name : 'label_prevStage'},
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
].concat(TYPO3.settings.Workspaces.extension.AdditionalColumn.Definition);

TYPO3.Workspaces.MainStore = new Ext.data.GroupingStore({
	storeId : 'workspacesMainStore',
	reader : new Ext.data.JsonReader({
		idProperty : 'id',
		root : 'data',
		totalProperty : 'total'
	}, TYPO3.Workspaces.Configuration.StoreFieldArray),
	groupField: 'path_Workspace',
	paramsAsHash : true,
	sortInfo : {
		field : 'label_Live',
		direction : "ASC"
	},
	remoteSort : true,
	baseParams: {
		depth : 990,
		id: TYPO3.settings.Workspaces.id,
		language: TYPO3.settings.Workspaces.language,
		query: '',
		start: 0,
		limit: 30
	},

	showAction : false,
	listeners : {
		beforeload : function() {},
		load : function(store, records) {
			var defaultColumn = TYPO3.Workspaces.WorkspaceGrid.colModel.getColumnById('label_Workspace');
			if (defaultColumn) {
				defaultColumn.width = defaultColumn.defaultWidth + this.getMaximumCollectionLevel() * defaultColumn.levelWidth;
			}
		},
		datachanged : function(store) {}
	},
	getMaximumCollectionLevel: function() {
		var maximumCollectionLevel = 0;
		Ext.each(this.data.items, function(item) {
			if (item.json.Workspaces_CollectionLevel > maximumCollectionLevel) {
				maximumCollectionLevel = item.json.Workspaces_CollectionLevel;
			}
		});
		return maximumCollectionLevel;
	}
});