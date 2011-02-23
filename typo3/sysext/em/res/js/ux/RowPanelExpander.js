Ext.ns('Ext.ux.grid');

/**
 * @class Ext.ux.grid.RowPanelExpander
 * @extends Ext.util.Observable
 * Plugin (ptype = 'rowexpander') that adds the ability to have a Column in a grid which enables
 * a second row body which expands/contracts.  The expand/contract behavior is configurable to react
 * on clicking of the column, double click of the row, and/or hitting enter while a row is selected.
 *
 * @ptype rowexpander
 */
Ext.ux.grid.RowPanelExpander = Ext.extend(Ext.util.Observable, {
	/**
	 * @cfg {Boolean} expandOnEnter
	 * <tt>true</tt> to toggle selected row(s) between expanded/collapsed when the enter
	 * key is pressed (defaults to <tt>true</tt>).
	 */
	expandOnEnter : true,
	/**
	 * @cfg {Boolean} expandOnDblClick
	 * <tt>true</tt> to toggle a row between expanded/collapsed when double clicked
	 * (defaults to <tt>true</tt>).
	 */
	expandOnDblClick : true,

	header : '',
	width : 20,
	sortable : false,
	fixed : true,
	menuDisabled : true,
	dataIndex : '',
	lazyRender : true,
	enableCaching : true,

	constructor : function(config) {
		Ext.apply(this, config);

		this.addEvents( {
			/**
			 * @event beforeexpand
			 * Fires before the row expands. Have the listener return false to prevent the row from expanding.
			 * @param {Object} this RowExpander object.
			 * @param {Object} Ext.data.Record Record for the selected row.
			 * @param {Object} body body element for the secondary row.
			 * @param {Number} rowIndex The current row index.
			 */
			beforeexpand : true,
			/**
			 * @event expand
			 * Fires after the row expands.
			 * @param {Object} this RowExpander object.
			 * @param {Object} Ext.data.Record Record for the selected row.
			 * @param {Object} body body element for the secondary row.
			 * @param {Number} rowIndex The current row index.
			 */
			expand : true,
			/**
			 * @event beforecollapse
			 * Fires before the row collapses. Have the listener return false to prevent the row from collapsing.
			 * @param {Object} this RowExpander object.
			 * @param {Object} Ext.data.Record Record for the selected row.
			 * @param {Object} body body element for the secondary row.
			 * @param {Number} rowIndex The current row index.
			 */
			beforecollapse : true,
			/**
			 * @event collapse
			 * Fires after the row collapses.
			 * @param {Object} this RowExpander object.
			 * @param {Object} Ext.data.Record Record for the selected row.
			 * @param {Object} body body element for the secondary row.
			 * @param {Number} rowIndex The current row index.
			 */
			collapse : true
		});

		Ext.ux.grid.RowPanelExpander.superclass.constructor.call(this);

		if (this.tpl) {
			if (typeof this.tpl == 'string') {
				this.tpl = new Ext.Template(this.tpl);
			}
			this.tpl.compile();
		}

		this.state = {};
		this.bodyContent = {};
	},

	getRowClass : function(record, rowIndex, p, ds) {
		p.cols = p.cols - 1;
		var content = this.bodyContent[record.id];
		if (!content && !this.lazyRender) {
			content = this.getBodyContent(record, rowIndex);
		}
		if (content) {
			p.body = content;
		}
		return this.state[record.id] ? 'x-grid3-row-expanded'
				: 'x-grid3-row-collapsed';
	},

	init : function(grid) {
		this.grid = grid;

		var view = grid.getView();
		view.getRowClass = this.getRowClass
				.createDelegate(this);

		view.enableRowBody = true;

		grid.on('render', this.onRender, this);
		view.on('refresh', this.onViewRefresh, this);
		grid.on('destroy', this.onDestroy, this);
		grid.on("beforestaterestore", this.applyState, this);
		grid.on("beforestatesave", this.saveState, this);
	},

	// @private
	onRender : function() {
		var grid = this.grid;


		var mainBody = grid.getView().mainBody;
		mainBody.on('mousedown', this.onMouseDown, this, {
			delegate : '.x-grid3-row-expander'
		});

		grid.getView().on('rowremoved', this.onRowRemoved, this);
		grid.getView().on('rowupdated', this.onRowUpdated, this);

		if (this.expandOnEnter) {
			this.keyNav = new Ext.KeyNav(this.grid.getGridEl(),
					{
						'enter' : this.onEnter,
						scope : this
					});
		}
		if (this.expandOnDblClick) {
			grid.on('rowdblclick', this.onRowDblClick, this);
		}
	},

	/** @private */
	onViewRefresh: function(view) {
		var index = -1;
		for(var key in this.state){
			if (this.state[key] === true) {
				index = view.grid.getStore().indexOfId(key);
				if (index > -1) {
					this.expandRow(index);
				}
			}
		}
	},

	/** @private */
	applyState: function(grid, state){
		this.suspendStateStore = true;
		if(state[this.id]) {
			this.state = state[this.id];
		}
		this.suspendStateStore = false;
	},

	/** @private */
	saveState: function(grid, state){
		return state[this.id] = this.state;
	},

	/** @private */
	onDestroy : function() {
		if (this.keyNav) {
			this.keyNav.disable();
			delete this.keyNav;
		}
		/*
		 * A majority of the time, the plugin will be destroyed along with the grid,
		 * which means the mainBody won't be available. On the off chance that the plugin
		 * isn't destroyed with the grid, take care of removing the listener.
		 */
		var mainBody = this.grid.getView().mainBody;
		if (mainBody) {
			mainBody.un('mousedown', this.onMouseDown, this);
		}
	},

	/** @private */
	onRowDblClick : function(grid, rowIdx, e) {
		this.toggleRow(rowIdx);
	},


		// This will not get fired for an update
	onRowRemoved: function(view, row, rec) {
		var panelItemIndex = rec.id;

		if (this.expandingRowPanel && this.expandingRowPanel[panelItemIndex]) {
			this.expandingRowPanel[panelItemIndex].destroy();
			this.expandingRowPanel[panelItemIndex] = null;
		}
	},

	onRowUpdated: function(view, row, rec) {
		if (typeof row == 'number') {
			row = this.grid.view.getRow(row);
		}

		this[Ext.fly(row).hasClass('x-grid3-row-collapsed') ? 'collapseRow' : 'expandRow'](row);
	},

	getBodyContent : function(record, index) {
		// extend here
		if (!this.enableCaching) {
			return this.tpl.apply(record.data);
		}
		var content = this.bodyContent[record.id];
		if (!content) {
			if (this.tpl) {
				content = this.tpl.apply(record.data);
				this.bodyContent[record.id] = content;
			}
		}
		return content;
	},

	onMouseDown : function(e, t) {
		e.stopEvent();
		var row = e.getTarget('.x-grid3-row');
		this.toggleRow(row);
	},

	renderer : function(v, p, record) {
		p.cellAttr = 'rowspan="2"';
		return '<div class="x-grid3-row-expander">&#160;</div>';
	},

	beforeExpand : function(record, body, rowIndex) {
		if (this.fireEvent('beforeexpand', this, record, body, rowIndex) !== false) {
			if (this.tpl && this.lazyRender) {
				body.innerHTML = this.getBodyContent(record, rowIndex);
			}
			if (body.innerHTML == '' || !this.enableCaching) {
				this.createExpandingRowPanel(record, body, rowIndex);
			}
			return true;
		} else {
			return false;
		}
	},

	toggleRow : function(row) {
		if (typeof row == 'number') {
			row = this.grid.view.getRow(row);
		}
		this[Ext.fly(row).hasClass('x-grid3-row-collapsed') ? 'expandRow' : 'collapseRow'](row);
		this.grid.saveState();
	},

	expandRow : function(row) {
		if (typeof row == 'number') {
			row = this.grid.view.getRow(row);
		}
		if (row) {
			var record = this.grid.store.getAt(row.rowIndex);
			var body = Ext.DomQuery.selectNode('tr:nth(2) div.x-grid3-row-body', row);
			if (this.beforeExpand(record, body, row.rowIndex)) {
				this.state[record.id] = true;
				Ext.fly(row).replaceClass('x-grid3-row-collapsed', 'x-grid3-row-expanded');
				this.grid.saveState();
				this.fireEvent('expand', this, record, body, row.rowIndex);
			}
		}
	},

	collapseRow : function(row) {
		if (typeof row == 'number') {
			row = this.grid.view.getRow(row);
		}
		var record = this.grid.store.getAt(row.rowIndex);
		var body = Ext.fly(row).child(
				'tr:nth(1) div.x-grid3-row-body', true);
		if (this.fireEvent('beforecollapse', this, record, 	body, row.rowIndex) !== false) {
			this.state[record.id] = false;
			Ext.fly(row).replaceClass('x-grid3-row-expanded', 'x-grid3-row-collapsed');
			this.grid.saveState();
			this.fireEvent('collapse', this, record, body, row.rowIndex);
		}
	},

		// Expand all rows
	expandAll : function() {
		var aRows = this.grid.getView().getRows();
		for (var i = 0; i < aRows.length; i++) {
			this.expandRow(aRows[i]);
		}
	},

		// Collapse all rows
	collapseAll : function() {
		var aRows = this.grid.getView().getRows();
		for (var i = 0; i < aRows.length; i++) {
			this.collapseRow(aRows[i]);
		}
	},

	createExpandingRowPanel : function(record, rowBody, rowIndex) {
		// record.id is more stable than rowIndex for panel item's key; rows can be deleted.
		var panelItemIndex = record.id;
		// var panelItemIndex = rowIndex;

		// init array of expanding row panels if not already inited
		if (!this.expandingRowPanel) {
			this.expandingRowPanel = [];
		}

		// Destroy the existing panel if present
		if (this.expandingRowPanel[panelItemIndex]) {
			this.expandingRowPanel[panelItemIndex].destroy();
		}
		this.expandingRowPanel[panelItemIndex] = new Ext.Panel({
			border : false,
			bodyBorder : false,
			layout : 'form',
			renderTo : rowBody,
			items : this.createExpandingRowPanelItems(record, rowIndex)
		});

	},

	/**
	 * Override this method to put Ext form items into the expanding row panel.
	 * @return Array of panel items.
	 */
	createExpandingRowPanelItems : function(record, rowIndex) {
		var panelItems = [];

		return panelItems;
	}
});

Ext.preg('rowexpander', Ext.ux.grid.RowPanelExpander);

Ext.ux.plugins.FitWidthToParent = Ext.extend(Object, {
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
		var pos = this.getPosition(true), size = this.fitToElement.getViewSize();
		this.setWidth(size.width - pos[0]);

	}
});