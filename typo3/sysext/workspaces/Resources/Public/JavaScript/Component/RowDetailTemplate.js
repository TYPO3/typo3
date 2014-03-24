Ext.ns('TYPO3.Workspaces.Component');

TYPO3.Workspaces.Component.RowDetailTemplate = Ext.extend(Ext.XTemplate, {
	exists: function(o, name) {
		return typeof o != 'undefined' && o != null && o!='';
	},
	hasComments: function(comments){
		return comments.length>0;
	}
});
