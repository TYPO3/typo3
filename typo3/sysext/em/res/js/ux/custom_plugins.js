/**
 * ExtJS for the extension manager.
 *
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage extension manager
 * @version $Id: $
 */

Ext.ns('Ext.ux');

Ext.ux.Image = Ext.extend(Ext.BoxComponent, {
	initComponent: function() {
		Ext.ux.Image.superclass.initComponent.apply(this, arguments);
		this.addEvents('load');
	},

	onRender: function() {
		this.autoEl = {
			cn: {tag: 'img', src: this.src}
		}
		Ext.ux.Image.superclass.onRender.apply(this, arguments);
		this.positionEl = this.el;
		this.el = this.resizeEl = Ext.get(this.positionEl.dom.firstChild);
		this.el.on('load', this.onLoad, this);
	},

	onResize: function() {
		this.positionEl.setSize(this.getSize());
	},

	onLoad: function() {
		if (this.autoSize) {
			this.syncSize();
		}
		if (this.resizable && !this.resizer) {
			this.resizer = new Ext.Resizable(this.positionEl, {
				preserveRatio: true,
				handles: 'all',
				draggable: true,
				dynamic: true,
				resizeChild: true
			});
		}
		this.fireEvent('load', this);
	}
});
Ext.reg('image', Ext.ux.Image);
