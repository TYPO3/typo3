Ext.ux.grid.SingleSelectCheckColumn = Ext.extend(Ext.ux.grid.CheckColumn, {
	onMouseDown : function(e, t){
		if(Ext.fly(t).hasClass('x-grid3-cc-'+this.id)){
			e.stopEvent();
			var index = this.grid.getView().findRowIndex(t),
				dataIndex = this.dataIndex;
			this.grid.store.each(function(record, i){
				var value = (i == index && record.get(dataIndex) != true);
				if(value != record.get(dataIndex)){
					record.set(dataIndex, value);
				}
			});
		}
	}
});