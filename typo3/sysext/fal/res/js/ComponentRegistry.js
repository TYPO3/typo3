Ext.ns("TYPO3.Components.filelist");

TYPO3.Components.filelist.ComponentRegistry = Ext.apply(new Ext.util.Observable, {

	items: {
	  'north':[],
	  'east':[],
	  'south':[],
	  'west':[],
	  'center':[]
	},

	registerComponent: function(config) {
		TYPO3.Components.filelist.ComponentRegistry.items[config.region].push(config.component);
	}
});
