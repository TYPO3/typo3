Ext.ns('TYPO3.Components.filelist.Components');
TYPO3.Components.filelist.FileList = Ext.extend(Ext.grid.EditorGridPanel, {
	border:false,
	enableDragDrop: true,
	enableColumnHide: true,
	loadMask:true,
	ddGroup:'TYPO3.Components.filelist.ddList2Tree',
	id:'TYPO3.Components.filelist.FileList',
	tbar:[{
		iconCls: 't3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-upload',
		handler: function(){
			var uploader = top.TYPO3.FileUploadWindow,
				fl = Ext.getCmp('TYPO3.Components.filelist.FileList');
			if(top.TYPO3.configuration.FileUpload){
				uploader.addListener('hide',fl.reloadPostUpload,fl);
				uploader.showFilelistUploadDialog(top.TYPO3.configuration.FileUpload.targetDirectory);
			}else{
				Ext.Msg.alert('missing target folder','Please select a target for the files from the folder tree.');
			}
		}
	}],
	unstyled: true,
	initComponent: function(){
		this.currentPath = top.TYPO3.configuration.FileUpload ? top.TYPO3.configuration.FileUpload.targetDirectory : '';
		this.contextMenu = new Ext.menu.Menu({
			items: [{
				text: 'rename',
				iconCls: 'edit',
				handler: function(){
					var g = this.parentMenu.grid,
						r = g.getSelectionModel().getSelected(),
						s = g.getStore(),
						i = s.indexOf(r);
					g.startEditing(i,0);
				}
			},{
				text: 'delete',
				iconCls: 'edit',
				handler: function(){
					var g = this.parentMenu.grid,
					r = g.getSelectionModel().getSelections(),
					s = g.getStore(),
					i = s.indexOf(r);
					Ext.each(r,function(row){
						s.remove(row);
					});
					s.save();
				}
			},{
				text: 'cut',
				iconCls: 'edit'
			},{
				text: 'copy',
				iconCls: 'edit'
			},{
				text: 'paste',
				iconCls: 'edit',
				hidden: true
			}]
		});
		this.on('rowcontextmenu',function(g,i,e){
			var store = g.getStore(), 
			sel = g.getSelectionModel(),
			selCnt = sel.getCount(),
			cm = this.contextMenu;
			if(selCnt === 0){
				sel.selectRow(i);
				selCnt = 1;
			}
			cm.grid = g;
			//hide rename for multiple selections:
			cm.items.items[0].setVisible(selCnt === 1);
			cm.showAt(e.getXY());
			e.stopEvent();
		});
		this.on('celldblclick',function(){
			//@todo check if the double clicked cell
			//belongs to a DIR and change to there if so
			
			//disables renaming on dbl click:
			return false;
		});
		this.on('afteredit', function(e){
			e.record.store.save();
		}, this);
		this.colModel = new Ext.grid.ColumnModel({
			defaults:{
				width:120,
				sortable:true
			},
			columns: TYPO3.Components.filelist.FileListColumns
		});
		this.sm = TYPO3.Components.filelist.FileListSelectionModel;
		this.view = new Ext.grid.GridView({
			forceFit:true
		});
		
		var proxy = new Ext.data.DirectProxy({
			api: {
				create:  TYPO3.FILELIST.ExtDirect.getAllInPath,
				read: TYPO3.FILELIST.ExtDirect.getAllInPath,
				update: TYPO3.FILELIST.ExtDirect.updateFile,
				destroy: TYPO3.FILELIST.ExtDirect.deleteFile
			}
		});
		var writer = new Ext.data.JsonWriter({
		    encode: false,
		    writeAllFields: false
		});
		var reader = new Ext.data.JsonReader({
			root:'data',
			idProperty:'sys_files_id',
			fields:['sys_files_id','file_name','file_path','file_type','file_mtime','file_size']
		});
		this.store = new Ext.data.Store({
			storeId:'TYPO3.Components.filelist.FileList.Store',
			autoDestroy: true,
			autoSave: false,
			proxy: proxy,
			writer: writer,
			reader: reader
		});
		
		TYPO3.Components.filelist.FileList.superclass.initComponent.call(this);
		Ext.getCmp('TYPO3.Components.filelist.FolderTree').on('click',this.getAllInPathOnNodeClick,this);
		Ext.getCmp('TYPO3.Components.filelist.FolderTree').on('beforenodedrop',this.onDragDropFilesToTree,this);
		/*
		var uploader = new plupload.Uploader({
			runtimes : 'html5',
			max_file_size : '10mb',
			url: TYPO3.configuration.PATH_typo3 + 'ajax.php',
			flash_swf_url : TYPO3.configuration.PATH_typo3 + 'contrib/plupload/js/plupload.flash.swf',
			silverlight_xap_url : TYPO3.configuration.PATH_typo3 + 'typo3/contrib/plupload/js/plupload.silverlight.xap',
			filters : [
				{title : "Image files", extensions : "jpg,gif,png"},
				{title : "Zip files", extensions : "zip"}
			]
		});
		*/
					
	},
	getAllInPathOnNodeClick: function(node){
		this.setCurrentPath(node.id);
		this.store.load({params:{
			path:node.id
		}})
	},
	setCurrentPath: function(path){
		top.TYPO3.configuration.FileUpload = {targetDirectory: path};
	},
	onDragDropFilesToTree: function(dropEvent){
		var g = dropEvent.data.grid,
			s = g.getStore(),
			sourceFolder = s.lastOptions.params.path,
			targetFolder = dropEvent.target.id;
		if (sourceFolder === targetFolder){
			Ext.Msg.alert('Move file','The Source- and Targetfilename are identical.');
		}else{
			if('moveondrop' === 'moveondrop'){
				Ext.each(g.getSelectionModel().getSelections(),function(record){
					record.set('file_path',targetFolder);
				});
			}else{ 
				//@todo implement dd-copy with ctrl button pressed
			}
			s.save();
		}
	},
	reloadPostUpload: function(){
		this.getStore().reload();
		top.TYPO3.FileUploadWindow.removeListener('hide',this.reloadPostUpload);
	}
});

Ext.reg('TYPO3.Components.filelist.FileList', TYPO3.Components.filelist.FileList);