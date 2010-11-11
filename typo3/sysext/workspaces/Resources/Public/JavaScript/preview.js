Ext.onReady(function() {
	var viewport = new Ext.Viewport({
		layout: 'border',
		items: [
		{
			xtype: 'tabpanel',
			region: 'center', // a center region is ALWAYS required for border layout
			id: 'preview',
			activeTab: 0,
			items: [
				{
					title: 'Workspace preview',
					id: 'workspaceRegion',
					layout: 'fit',
					items: [{
						xtype: 'iframePanel',
						id: 'wsPanel',
						doMask: false,
						src: wsUrl
					}]
				}, {
					title: 'Live Workspace',
					id: 'liveRegion',
					layout: 'fit',
					items: [{
						xtype: 'iframePanel',
						id: 'livePanel',
						doMask: false,
						src: liveUrl
					}]
				}
			]
		},  {
			region: 'south',
			title: 'Workspace settings',
			id: 'wsSettings',
			collapsible: true,
			split: true,
			collapseMode: 'mini',
			hideCollapseTool: true,
			height: 200,
			minSize: 200,
			maxSize: 500,
			margins: '0 0 0 0',
			layout: 'fit',
			items:  [{
				xtype: 'iframePanel',
				id: 'settingsPanel',
				doMask: false,
				src: wsSettingsUrl
			}]
		}]
	});
});