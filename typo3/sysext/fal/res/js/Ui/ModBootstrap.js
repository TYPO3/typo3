Ext.ns("TYPO3.Components.filelist.Ui");
TYPO3.Components.filelist.Ui.Bootstrap = Ext.apply(new Ext.util.Observable, {
	initialize: function () {
		TYPO3.Components.filelist.FileListSelectionModel = new Ext.grid.RowSelectionModel({
			moveEditorOnEnter: false
		});
		TYPO3.Components.filelist.FileListColumns = [
		    {header: 'Filename', dataIndex:'file_name', editor: new Ext.form.TextField({allowBlank:false})},
		    {header: 'Type', dataIndex:'file_type', hidden: true},
		    {header: 'Date', dataIndex:'file_mtime', renderer: function(t){ if(t){ var d = new Date(t*1000); return d.format('d.m.Y H:i'); }}},
		    {header: 'Size', dataIndex:'file_size', renderer: function(s){if(s) return Ext.util.Format.fileSize(s);}, align: 'right'}
	    ];
		TYPO3.Components.filelist.on('TYPO3.Components.filelist.afterBootstrap',function(){
			Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
			new TYPO3.Components.filelist.Ui({
				maximized: true,
				header: true,
				unstyled: true,
				closable: false
			}).show();
		});
	}
});
TYPO3.Components.filelist.registerBootstrap(TYPO3.Components.filelist.Ui.Bootstrap);