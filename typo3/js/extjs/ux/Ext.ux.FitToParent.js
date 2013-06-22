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
			// initial is the height of the TYPO3 Topbar which i 42. If Topbar is not rendered, set the height as default
		var documentHeaderHeight = 42 || top.TYPO3.Backend.Topbar.getHeight();
		var documentHeader = Ext.get('typo3-docheader');

		if (Ext.isObject(documentHeader)) {
				// use 5px bottom margin
			documentHeaderHeight -= documentHeader.getHeight() + 5;
		}

		if (this.heightOffset && Ext.isNumber(this.heightOffset)) {
			documentHeaderHeight -= parseInt(this.heightOffset, 10);
		}

		this.fitToElement.setHeight(
			Ext.lib.Dom.getViewportHeight() - this.fitToElement.getTop() + documentHeaderHeight
		);

		var pos = this.getPosition(true), size = this.fitToElement.getViewSize();
		this.setSize(size.width - pos[0], size.height - pos[1]);

	}
});
