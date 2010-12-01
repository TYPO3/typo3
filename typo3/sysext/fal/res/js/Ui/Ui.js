Ext.ns('TYPO3.Components.filelist');

TYPO3.Components.filelist.Ui = Ext.extend(Ext.Window, {
	// privat
	initComponent: function() {
		var defaultRegionConf = {
			north:{
				id:'typo3-filelist-north-region-container',
				stateful: true
			},
			east:{
				region:'east',
				id:'typo3-filelist-east-region-container',
				layout: 'accordion',
				width:200,
				split:true,
				collapsible:true,
				frame:true,
				stateful: true
			},
			south:{
				region:'south',
				id:'typo3-filelist-south-region-container',
				stateful: true
			},
			west:{
				region: 'west',
				layout: 'fit',
				id:'typo3-filelist-west-region-container',
				width: 300,
				minWidth: 20,
				floatable: true,
				animCollapse: false,
				split: true,
				collapsible: true,
				collapseMode: 'mini',
				hideCollapseTool: true,
				autoScroll: true,
				border: false,
				stateful: true
			}
		};
		var items = [{
			region:'center',
			id:'typo3-filelist-center-region-container',
			frame:false,
			layout:'fit'
		}];
		var cR = TYPO3.Components.filelist.ComponentRegistry.items;
		Ext.each(['north','east','south','west'],function(i){
			if(cR[i].length > 0) items.push(defaultRegionConf[i]);
		})
		var config = {
			layout:'border',
			items: items
		};
		Ext.apply(this, config);
		TYPO3.Components.filelist.Ui.superclass.initComponent.call(this);
		this.on('afterrender', this.addComponents, this);
	},

	/**
	 * equal segement the registert portlets to the columns
	 *
	 * @return {void}
	 */
	addComponents: function() {
		Ext.each(['north','east','south','west','center'],function(region){
			Ext.each(TYPO3.Components.filelist.ComponentRegistry.items[region],function(Component){
				Ext.getCmp('typo3-filelist-'+region+'-region-container').add(new Component);
			});
		});
		this.doLayout();
		TYPO3.Components.filelist.fireEvent('TYPO3.Components.filelist.Ui.afterAddComponents');
	}


});
// register class as xclass
Ext.reg('TYPO3.Components.filelist.Ui', TYPO3.Components.filelist.Ui);