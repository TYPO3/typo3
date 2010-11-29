/* plugin for resize of grid in single container */
Ext.namespace('Ext.ux.plugins');

Ext.ux.plugins.FitToParent = Ext.extend(Object, {
	constructor : function(parent) {
		this.parent = parent;
	},
	init : function(c) {
		c.on('render', function(c) {
			c.fitToElement = Ext.get(this.parent
					|| c.getPositionEl().dom.parentNode);
			if (!c.doLayout) {
				this.fitSizeToParent();
				Ext.EventManager.onWindowResize(this.fitSizeToParent, this);
			}
		}, this, {
			single : true
		});
		if (c.doLayout) {
			c.monitorResize = true;
			c.doLayout = c.doLayout.createInterceptor(this.fitSizeToParent);
		}
	},

	fitSizeToParent : function() {
			// Uses the dimension of the current viewport, but removes the document header
		var documentHeaderHeight = 0;
		var documentHeader = Ext.get('typo3-docheader');

		if (Ext.isObject(documentHeader)) {
			documentHeaderHeight = documentHeader.getHeight();
		}

		this.fitToElement.setHeight(
			Ext.lib.Dom.getViewportHeight() - this.fitToElement.getTop() - documentHeaderHeight
		);

		var pos = this.getPosition(true), size = this.fitToElement.getViewSize();
		this.setSize(size.width - pos[0], size.height - pos[1]);
		
	}
});
