Ext.apply(Ext, {
	merge: function(o, c) {
		if (o && c && typeof c == 'object') {
			for (var p in c){
				if ((typeof o[p] == 'object') && (typeof c[p] == 'object')) {
					Ext.merge(o[p], c[p]);
				} else {
					o[p] = c[p];
				}
			}
		}
		return o;
	},
	mergeIf: function(o, c) {
		if (o && c && typeof c == 'object') {
			for (var p in c){
				if ((typeof o[p] == 'object') && (typeof c[p] == 'object')) {
					Ext.mergeIf(o[p], c[p]);
				} else if (typeof o[p] == 'undefined') {
					o[p] = c[p];
				}
			}
		}
		return o;
	}
});