/*
 * Ext JS Library 2.0
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 *
 * http://extjs.com/license
 *
 * MODIFIED: SGB [12.12.07]
 * Added support for a new config option, remoteDataMethod,
 * including getter and setter functions, and minor mods
 * to the beforeExpand and expandRow functions
 */

Ext.grid.RowExpander = function(config) {
	Ext.apply(this, config);
	Ext.grid.RowExpander.superclass.constructor.call(this);

	if (this.tpl) {
		if (typeof this.tpl == 'string') {
			this.tpl = new Ext.Template(this.tpl);
		}
		this.tpl.compile();
	}

	this.state = {};
	this.bodyContent = {};

	this.addEvents({
		beforeexpand : true,
		expand: true,
		beforecollapse: true,
		collapse: true
	});
};

Ext.extend(Ext.grid.RowExpander, Ext.util.Observable, {
	header: "",
	width: 20,
	sortable: false,
	fixed:true,
	dataIndex: '',
	id: 'expander',
	lazyRender : true,
	enableCaching: true,

	getRowClass : function(record, rowIndex, p, ds) {
		p.cols = p.cols-1;
		var content = this.bodyContent[record.id];
		if (!content && !this.lazyRender) {
			content = this.getBodyContent(record, rowIndex);
		}
		if (content) {
			p.body = content;
		}
		return this.state[record.id] ? 'x-grid3-row-expanded' : 'x-grid3-row-collapsed';
	},

	init : function(grid) {
		this.grid = grid;

		var view = grid.getView();
		view.getRowClass = this.getRowClass.createDelegate(this);

		view.enableRowBody = true;

		grid.on('render', function() {
			view.mainBody.on('mousedown', this.onMouseDown, this);
		}, this);

		grid.store.on('load', this.onStoreLoaded, this);
		grid.on("beforestaterestore", this.applyState, this);
		grid.on("beforestatesave", this.saveState, this);
	},

	/** @private */
	onStoreLoaded: function(store, records, options) {
		var index = -1;
		for(var key in this.state){
			if (this.state[key] === true) {
				index = store.indexOfId(key);
				if (index > -1) {
					this.expandRow(index);
				}
			}
		}
	},

	/** @private */
	applyState: function(grid, state){
		this.suspendStateStore = true;
		if(state.expander) {
			this.state = state.expander;
		}
		this.suspendStateStore = false;
	},

	/** @private */
	saveState: function(grid, state){
		return state.expander = this.state;
	},

	getBodyContent : function(record, index) {
		if (!this.enableCaching) {
			return this.tpl.apply(record.data);
		}
		var content = this.bodyContent[record.id];
		if (!content) {
			content = this.tpl.apply(record.data);
			this.bodyContent[record.id] = content;
		}
		return content;
	},
	// Setter and Getter methods for the remoteDataMethod property
	setRemoteDataMethod : function (fn) {
		this.remoteDataMethod = fn;
	},

	getRemoteDataMethod : function (record, index) {
		if (!this.remoteDataMethod) {
			return;
		}
			return this.remoteDataMethod.call(this,record,index);
	},

	onMouseDown : function(e, t) {
		if (t.className == 'x-grid3-row-expander') {
			e.stopEvent();
			var row = e.getTarget('.x-grid3-row');
			this.toggleRow(row);
		}
	},

	renderer : function(v, p, record) {
		p.cellAttr = 'rowspan="2"';
		return '<div class="x-grid3-row-expander">&#160;</div>';
	},

	beforeExpand : function(record, body, rowIndex) {
		if (this.fireEvent('beforexpand', this, record, body, rowIndex) !== false) {
			// If remoteDataMethod is defined then we'll need a div, with a unique ID,
			//  to place the content
			if (this.remoteDataMethod) {
				this.tpl = new Ext.Template("<div id=\"remData" + rowIndex + "\" class=\"rem-data-expand\"><\div>");
			}
			if (this.tpl && this.lazyRender) {
				body.innerHTML = this.getBodyContent(record, rowIndex);
			}

			return true;
		}else{
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
		var record = this.grid.store.getAt(row.rowIndex);
		var body = Ext.DomQuery.selectNode('tr:nth(2) div.x-grid3-row-body', row);
		if (this.beforeExpand(record, body, row.rowIndex)) {
			this.state[record.id] = true;
			Ext.fly(row).replaceClass('x-grid3-row-collapsed', 'x-grid3-row-expanded');
			this.grid.saveState();
		   	if (this.fireEvent('expand', this, record, body, row.rowIndex) !== false) {
				//  If the expand event is successful then get the remoteDataMethod
				this.getRemoteDataMethod(record,row.rowIndex);
			}
		}
	},

	collapseRow : function(row) {
		if (typeof row == 'number') {
			row = this.grid.view.getRow(row);
		}
		var record = this.grid.store.getAt(row.rowIndex);
		var body = Ext.fly(row).child('tr:nth(1) div.x-grid3-row-body', true);
		if (this.fireEvent('beforcollapse', this, record, body, row.rowIndex) !== false) {
			this.state[record.id] = false;
			Ext.fly(row).replaceClass('x-grid3-row-expanded', 'x-grid3-row-collapsed');
			this.grid.saveState();
			this.fireEvent('collapse', this, record, body, row.rowIndex);
		}
	}
});
