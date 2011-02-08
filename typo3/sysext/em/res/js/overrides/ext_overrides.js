/**
 * ExtJS for the extension manager.
 *
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage extension manager
 * @version $Id: $
 */

/***********************************/
/*	Ext.data.Store                 */
/***********************************/
Ext.override(Ext.data.Store, {
	add : function(records) {
		records = [].concat(records);
		if (records.length < 1) {
			return;
		}
		for (var i = 0, len = records.length; i < len; i++) {
			records[i].join(this);
		}
		var index = this.data.length;
		this.data.addAll(records);
		if (this.snapshot) {
			this.snapshot.addAll(records);
		}
		this.totalLength += records.length;
		this.fireEvent('add', this, records, index);
	},
	remove : function(record) {
		if (Ext.isArray(record)) {
			Ext.each(record, function(r) {
				this.remove(r);
			}, this);
		}
		var index = this.data.indexOf(record);
		if (index > -1) {
			record.join(null);
			this.data.removeAt(index);
			if (this.pruneModifiedRecords) {
				this.modified.remove(record);
			}
			if (this.snapshot) {
				this.snapshot.remove(record);
			}
			this.totalLength--;
			this.fireEvent('remove', this, record, index);
		}
	},
	removeAll : function() {
		var items = [];
		this.each(function(rec) {
			items.push(rec);
		});
		this.clearData();
		if (this.snapshot) {
			this.snapshot.clear();
		}
		if (this.pruneModifiedRecords) {
			this.modified = [];
		}
		this.totalLength = 0;
		this.fireEvent('clear', this, items);
	},
	addField: function(field) {
		field = new Ext.data.Field(field);
		this.recordType.prototype.fields.replace(field);
		if (typeof field.defaultValue != 'undefined') {
			this.each(function(r) {
				if (typeof r.data[field.name] == 'undefined') {
					r.data[field.name] = field.defaultValue;
				}
			});
		}
	},
	removeField: function(name) {
		this.recordType.prototype.fields.removeKey(name);
		this.each(function(r) {
			delete r.data[name];
			if (r.modified) {
				delete r.modified[name];
			}
		});
	}
});
/***********************************/
/*	Ext.PagingToolbar              */
/***********************************/

Ext.override(Ext.PagingToolbar, {
	bindStore : function(store, initial) {
		var doLoad;
		if (!initial && this.store) {
			if (store !== this.store && this.store.autoDestroy) {
				this.store.destroy();
			} else {
				this.store.un('beforeload', this.beforeLoad, this);
				this.store.un('load', this.onLoad, this);
				this.store.un('exception', this.onLoadError, this);
				this.store.un('datachanged', this.onChange, this);
				this.store.un('add', this.onChange, this);
				this.store.un('remove', this.onChange, this);
				this.store.un('clear', this.onClear, this);
			}
			if (!store) {
				this.store = null;
			}
		}
		if (store) {
			store = Ext.StoreMgr.lookup(store);
			store.on({
				scope: this,
				beforeload: this.beforeLoad,
				load: this.onLoad,
				exception: this.onLoadError,
				datachanged: this.onChange,
				add: this.onChange,
				remove: this.onChange,
				clear: this.onClear
			});
			doLoad = true;
		}
		this.store = store;
		if (doLoad) {
			this.onLoad(store, null, {});
		}
	},
	onLoad : function(store, r, o) {
		if (!this.rendered) {
			this.dsLoaded = [store, r, o];
			return;
		}
		var p = this.getParams();
		this.cursor = (o.params && o.params[p.start]) ? o.params[p.start] : 0;
		this.onChange();
	},
	onChange : function() {
		if (this.rendered) {
			var d = this.getPageData(), ap = d.activePage, ps = d.pages;
			this.afterTextItem.setText(String.format(this.afterPageText, d.pages));
			this.inputItem.setValue(ap);
			this.first.setDisabled(ap == 1);
			this.prev.setDisabled(ap == 1);
			this.next.setDisabled(ap == ps);
			this.last.setDisabled(ap == ps);
			this.refresh.enable();
			this.updateInfo();
		}
		this.fireEvent('change', this, d);
	},
	onClear : function() {
		this.cursor = 0;
		this.onChange();
	}
});

/***********************************/
/*	Ext.grid.ColumnModel           */
/***********************************/

Ext.override(Ext.grid.ColumnModel, {
	addColumn: function(column, colIndex) {
		if (typeof column == 'string') {
			column = {header: column, dataIndex: column};
		}
		var config = this.config;
		this.config = [];
		if (typeof colIndex == 'number') {
			config.splice(colIndex, 0, column);
		} else {
			colIndex = config.push(column);
		}
		this.setConfig(config);
		return colIndex;
	},
	removeColumn: function(colIndex) {
		var config = this.config;
		this.config = [config[colIndex]];
		config.splice(colIndex, 1);
		this.setConfig(config);
	}
});
Ext.override(Ext.grid.GridPanel, {
	addColumn: function(field, column, colIndex) {
		if (!column) {
			if (field.dataIndex) {
				column = field;
				field = field.dataIndex;
			} else {
				column = field.name || field;
			}
		}
		this.store.addField(field);
		return this.colModel.addColumn(column, colIndex);
	},
	removeColumn: function(name, colIndex) {
		this.store.removeField(name);
		if (typeof colIndex != 'number') {
			colIndex = this.colModel.findColumnIndex(name);
		}
		if (colIndex >= 0) {
			this.colModel.removeColumn(colIndex);
		}
	},
	applyState : function(state) {
		var cm = this.colModel,
				cs = state.columns,
				store = this.store,
				s,
				c,
				colIndex;

		if (cs) {
			for (var i = 0, len = cs.length; i < len; i++) {
				s = cs[i];
				c = cm.getColumnById(s.id);
				if (c) {
					colIndex = cm.getIndexById(s.id);
					cm.setState(colIndex, {
						hidden: s.hidden || false,
						width: s.width,
						sortable: s.sortable,
						hideable: cm.config[colIndex].hideable
					});
					if (colIndex != i) {
						cm.moveColumn(colIndex, i);
					}
				}
			}
		}
		if (store) {
			s = state.sort;
			if (s) {
				store[store.remoteSort ? 'setDefaultSort' : 'sort'](s.field, s.direction);
			}
			s = state.group;
			if (store.groupBy) {
				if (s) {
					store.groupBy(s);
				} else {
					store.clearGrouping();
				}
			}

		}
		var o = Ext.apply({}, state);
		delete o.columns;
		delete o.sort;
		Ext.grid.GridPanel.superclass.applyState.call(this, o);
	}
});

Ext.override(Ext.form.ComboBox, {
	setValue : function(v) {
		var text = v;
		if (this.valueField) {
			if (this.mode == 'remote' && !Ext.isDefined(this.store.totalLength)) {
				this.store.on('load', this.setValue.createDelegate(this, arguments), null, {single: true});
				if (this.store.lastOptions === null) {
					var params;
					if (this.valueParam) {
						params = {};
						params[this.valueParam] = v;
					} else {
						var q = this.allQuery;
						this.lastQuery = q;
						this.store.setBaseParam(this.queryParam, q);
						params = this.getParams(q);
					}
					this.store.load({params: params});
				}
				return;
			}
			var r = this.findRecord(this.valueField, v);
			if (r) {
				text = r.data[this.displayField];
			} else if (this.valueNotFoundText !== undefined) {
				text = this.valueNotFoundText;
			}
		}
		this.lastSelectionText = text;
		if (this.hiddenField) {
			this.hiddenField.value = v;
		}
		Ext.form.ComboBox.superclass.setValue.call(this, text);
		this.value = v;
	}
});