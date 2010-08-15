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
		// and an addtional margin of 40 pixels (e.g. Safari needs this addition)
		
		this.fitToElement.setHeight(document.viewport.getHeight() - this.fitToElement.getTop() - 40);
		var pos = this.getPosition(true), size = this.fitToElement.getViewSize();
		this.setSize(size.width - pos[0], size.height - pos[1]);
		
	}
});
