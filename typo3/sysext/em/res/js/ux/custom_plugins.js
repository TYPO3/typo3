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

Ext.grid.DynamicColumnModelForLanguages = function(store){
	var cols = [];
	var recordType = store.recordType;
	var fields = recordType.prototype.fields;

	for (var i = 0; i < fields.keys.length; i++) {
		var fieldName = fields.keys[i];
		var field = recordType.getField(fieldName);

		if (i === 0) {
			cols[i] = {
				header: 'Extension',
				dataIndex: field.name,
				width:180,
				fixed: true,
				sortable: false,
				hidable: false,
				menuDisabled: true,
				renderer:function(value, metaData, record, rowIndex, colIndex, store){
					return record.data.icon + ' ' + value;
				}
			};
		} else if (i === 1 || i === 2 || i === 3) {
			//bypass
		} else {
			cols[i-3] = {
				header: field.name,
				dataIndex: field.name,
				width:100,
				hidden: true,
				fixed: true,
				sortable: false,
				hidable: false,
				menuDisabled: true
			};

		}
	}
	Ext.grid.DynamicColumnModelForLanguages.superclass.constructor.call(this, cols);
};
Ext.extend(Ext.grid.DynamicColumnModelForLanguages, Ext.grid.ColumnModel, {});

