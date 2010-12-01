Ext.ns("TYPO3.Components.filelist.Ui");

TYPO3.Components.filelist.FileListSelectionModel = new Ext.grid.RowSelectionModel({
	moveEditorOnEnter: false
});
TYPO3.Components.filelist.loadElementBrowser = function(listfield,hiddenfield){
	var win = new TYPO3.Components.filelist.Ui({
		width: '80%',
		height: Ext.getBody().getHeight()/100*60,
		modal:true,
		bbar:['->',{
			text:'Done',
			handler: function(){
				var eb = Ext.getCmp('TYPO3.Components.filelist.elementBrowser');
				if(eb.fireEvent('TYPO3.Components.filelist.elementBrowser.done', eb)){
					eb.close();
				}
			}
		}],
		header: true,
		title:'Element Browser',
		unstyled: false,
		closable: true,
		maximizable: true,
		stateId: 'TYPO3.Components.filelist.elementBrowser.state',
		id: 'TYPO3.Components.filelist.elementBrowser',
		stateful:true,
		typo3ListField: listfield,
		typo3HiddenField: hiddenfield
	}).show();
};
TYPO3.Components.filelist.FileListColumns = [
                                 			{header: 'Filename', dataIndex:'file_name', editor: new Ext.form.TextField({allowBlank:false})},
                                 			{header: 'Type', dataIndex:'file_type', hidden: true}
                                 			/*,
                                 			{header: 'Date', dataIndex:'file_mtime', renderer: function(t){ if(t){ var d = new Date(t*1000); return d.format('d.m.Y H:i'); }}},
                                 			{header: 'Size', dataIndex:'file_size', renderer: function(s){if(s) return Ext.util.Format.fileSize(s);}, align: 'right'}
                                 			*/
                                 		];

TYPO3.Components.filelist.Ui.Bootstrap = Ext.apply(new Ext.util.Observable, {
	initialize: function () {
	}
});
TYPO3.Components.filelist.registerBootstrap(TYPO3.Components.filelist.Ui.Bootstrap);