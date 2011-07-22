Ext.apply(Ext, {
	isEmptyObject: function(o) {
		for(var p in o) {
			return false;
		};
		return true;
	}
});