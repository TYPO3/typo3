Ext.ns('TYPO3.Components.filelist.Components');

TYPO3.Components.filelist.FolderTree = Ext.extend(Ext.Panel, {
	header: true,
	title:'Files & Folders',
	initComponent: function(){
		var fileTree = new Ext.tree.TreePanel ({
			id: 'TYPO3.Components.filelist.FolderTree',
			unstyled: true,
			autoScroll: true,
			containerScroll: true,
			margins: '0 0 0 0',
			cmargins: '0 0 0 0',
			useArrows: true,
			root: {
				text: 'fileadmin/',
				itemId: 'fileroot',
				expanded: true,
				id:'FILE_MOUNTS'
			},
			rootVisible: false,
			loader: {
				directFn: TYPO3.FILELIST.ExtDirect.getExtFileTree,
				paramsAsHash: true
			},
			enableDrop: true,
			ddGroup:'TYPO3.Components.filelist.ddList2Tree',
			listeners:{
				nodedragover:{
					fn:function(dE){
						//disallows files dragged between nodes!
						return dE.point === 'append';
					}
				}
			}
		});
		TYPO3.Components.filelist.FolderTree.superclass.initComponent.call(this);
		this.add(fileTree);
	}

});
Ext.reg('TYPO3.Components.filelist.FolderTree', TYPO3.Components.filelist.FolderTree);