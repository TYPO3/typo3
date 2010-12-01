Ext.ns('TYPO3.Components.filelist.Components');

TYPO3.Components.filelist.DetailView = Ext.extend(Ext.Panel, {
	loadMask:{},
	title: 'Details',
	tplMarkup: [
	   'Filename: {file_name}'
	],
	initComponent: function(){
		this.tpl = new Ext.Template(this.tplMarkup);
		TYPO3.Components.filelist.on('TYPO3.Components.filelist.Ui.afterAddComponents',function(){
			var sm = Ext.getCmp('TYPO3.Components.filelist.FileList').getSelectionModel();
			sm.on('selectionchange', this.checkRenderPreview, this);
		},this);
		TYPO3.Components.filelist.DetailView.superclass.initComponent.call(this);
	},
	checkRenderPreview: function(sm){
		var s = sm.getSelections();
		if(s.length === 1){
			TYPO3.FILELIST.ExtDirect.getDetails(s[0].data, this.renderPreview.createDelegate(this));
		}else{
			this.hide();
		}
	},
	renderPreview: function(r,e){
		this.tpl.overwrite(this.body,r);
		this.show();
	}
});
Ext.reg('TYPO3.Components.filelist.DetailView', TYPO3.Components.filelist.DetailView);