/**
 * @author schiesser
 */
Ext.ns('Ext.ux.grid');

Ext.ux.grid.ItemDeleter = Ext.extend(Ext.grid.RowSelectionModel, {
	width: 25,
	sortable: false,
	dataIndex: 0, // this is needed, otherwise there will be an error

	menuDisabled: true,
	fixed: true,
	id: 'deleter',

	initEvents: function(){
		Ext.ux.grid.ItemDeleter.superclass.initEvents.call(this);
		this.grid.on('cellclick', function(grid, rowIndex, columnIndex, e){
			if(columnIndex==grid.getColumnModel().getIndexById('deleter')) {
				var record = grid.getStore().getAt(rowIndex);
				grid.getStore().remove(record);
				grid.getView().refresh();
			}
		});
	},

	renderer: function(v, p, record, rowIndex){
		return '<div class="remove">&nbsp;</div>';
	}
});
