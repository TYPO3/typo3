Ext.ns('TYPO3.Components.filelist.SelectedFilesView','TYPO3.Components.filelist.SelectedFilesDataView');
TYPO3.Components.filelist.SelectedFilesDataView = Ext.extend(Ext.DataView,{
	autoHeight: true,
	overClass: 'x-view-over',
    itemSelector: 'div.thumb-wrap',
    emptyText: 'No images to display',
    id:'typo3-components-filelist-selectedfilesview',
	initComponent: function(){
		this.store = new Ext.data.ArrayStore({
			autoDestroy: true,
			storeId: 'TYPO3.Components.filelist.SelectedFilesView.DataView.Store',
		    idIndex: 0,  
			fields: [
			         {name: 'sys_files_id'},
			         {name: 'file_name'}
			         ]
		});
		this.store.loadData([]);
		this.tpl = new Ext.XTemplate(
		    '<tpl for=".">',
		        '<div class="thumb-wrap" id="{sys_files_id}">',
		        	'<div class="thumb">{sys_files_id}</div>',
		        	'<span>{file_name}</span>',
	        	'</div>',
		    '</tpl>',
		    '<div class="x-clear"></div>'
		);
		Ext.getCmp('TYPO3.Components.filelist.elementBrowser').on('TYPO3.Components.filelist.elementBrowser.done',this.writeValuesOnWindowClose,this);
		TYPO3.Components.filelist.SelectedFilesDataView.superclass.initComponent.call(this);
	},
	addRowOnSelectionChange: function(sm, i, r){
		var newId = r.get('sys_files_id'),
			exists = this.store.getById(newId),
			isDir = r.get('file_type') === 'DIR';
		if(!exists && !isDir){
			var row = new this.store.recordType({
				sys_files_id: newId,
				file_name: r.get('file_name')
			},newId);
			this.store.add(row);
		}
	},
	writeValuesOnWindowClose: function(ebWin){
		var tceFormsField = ebWin.typo3ListField,
			tceFormsHiddenField =  ebWin.typo3HiddenField,
			selectedRowCnt = this.store.getCount(),
			r, o, e, at, v, values = tceFormsHiddenField.value.split(',');
		try {
			if(tceFormsField && selectedRowCnt > 0){
				for(i=0; i < selectedRowCnt;i++){
					r = this.store.getAt(i);
					v = r.get('sys_files_id');
					o = document.createElement('option');
					o.value = v;
					o.text =  r.get('file_name');
					at = Ext.isIE ? tceFormsField.length : null;
					tceFormsField.add(o, at);
					values.push(v);
				}
				tceFormsHiddenField.value = values.join(',');
			}
			return true;
		}catch(e){
			return false;
		}
	}
});


TYPO3.Components.filelist.SelectedFilesView = Ext.extend(Ext.Panel, {
	title: 'Selected Files',
	frame:false,
	layout: 'fit',
	id: 'TYPO3.Components.filelist.SelectedFilesView',
	initComponent: function(){
		TYPO3.Components.filelist.SelectedFilesView.superclass.initComponent.call(this);
		var dv = new TYPO3.Components.filelist.SelectedFilesDataView();
		TYPO3.Components.filelist.on('TYPO3.Components.filelist.Ui.afterAddComponents',function(){
			var fileList = Ext.getCmp('TYPO3.Components.filelist.FileList');
			this.fileListSelectionModel = fileList.getSelectionModel();
			this.fileListSelectionModel.on('rowselect', this.addRowOnSelectionChange, this);
		},dv);
		this.add(dv);
	}
});

Ext.reg('TYPO3.Components.filelist.SelectedFilesView', TYPO3.Components.filelist.SelectedFilesView);