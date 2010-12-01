/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper <info@sk-typo3.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * ExtJS for the extension manager.
 *
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage extension manager
 * @version $Id: $
 */
Ext.ns('TYPO3.EM', 'TYPO3.EM.GridColumns');

TYPO3.EM.LocalList = Ext.extend(Ext.grid.GridPanel, {
	border:false,
	stripeRows: true,

	expander: new Ext.ux.grid.RowPanelExpander({
		hideable: false,
		createExpandingRowPanelItems: function(record, rowIndex){
			var panelItems = [
				new Ext.TabPanel({
					plain: true,
					activeTab: 0,
					defaults: {
						bodyStyle: 'background:#fff;padding:10px;',
						boxMinHeight: 150
					},
					record: record,
					items:[
						{
							title: 'Info',
							autoHeight: true,
							listeners: {
								activate: function(panel) {
									TYPO3.EM.Layouts.showExtInfo(panel, panel.ownerCt.record.data);
								}
							}
						},
						{
							title:'Update',
							html: '<div class="loading-indicator">Loading...</div>',
							disabled: record.data.installed === 0,
							listeners: {
								activate: function(panel) {
									TYPO3.EM.ExtDirect.getExtensionUpdate(record.data.extkey, function(response) {
										panel.update(response);
									});
								}
							}
						},
						{
							title: 'Configuration',
							xtype: 'form',
							disabled: record.data.installed === 0,
							autoHeight: true,
							html: '<div class="loading-indicator">Loading...</div>',
							listeners: {
								activate: function(panel) {
									TYPO3.EM.ExtDirect.getExtensionConfiguration(record.data.extkey, function(response) {
										panel.update(response, true, this.readConfigForm.createDelegate(this));
									}, this);
								}
							},
							scope: this,
							readConfigForm: function() {
								var key = record.data.extkey;
								var button = Ext.select('input[type="submit"]');
								if (button) {
									button.on('click', function() {
										Ext.apply(this.form,{
										api: {
											submit: TYPO3.EM.ExtDirect.saveExtensionConfiguration
										},
										paramsAsHash: false

									});
										this.form.submit({
											waitMsg : 'Saving Settings...',
											success: function(form, action) {
												TYPO3.Flashmessage.display(TYPO3.Severity.information, 'Configuration', 'Configuration was saved', 5);
											},
											failure: function(form, action) {
												if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
													TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Error',
																			'Status:' + action.response.status + ': ' +
																			action.response.statusText, 5);
											}
											if (action.failureType === Ext.form.Action.SERVER_INVALID) {
													// server responded with success = false
												TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Invalid', action.result.errormsg, 5);
											}
										 }
									 });
									}, this);
								}
							}
						},
						{
							title: 'Files',
							xtype: 'extfilelist',
							recordData: record.data

						},
						{
							xtype: 'terupload',
							title:'Upload to TER',
							recordData: record.data,
							disabled: !TYPO3.settings.EM.hasCredentials
						},
						{
							title:'Developer Information',
							autoHeight: true,
							html: '<div class="loading-indicator">Loading...</div>',
							listeners: {
								activate: function(panel) {
									TYPO3.EM.ExtDirect.getExtensionDevelopInfo(record.data.extkey, function(response) {
										panel.update(response);
									});
								}
							}
						},
						{
							title:'Backup/Delete',
							disabled: true //record.data.installed === 0
						}
					]
				})
			];
			return panelItems;
		}
	}),

	initComponent:function() {
		var localstore = new Ext.data.GroupingStore({
			storeId: 'localstore',
			proxy: new Ext.data.DirectProxy({
				directFn: TYPO3.EM.ExtDirect.getExtensionList
			}),

			reader: new Ext.data.JsonReader({
				idProperty: 'extkey',
				root: 'data',
				totalProperty: 'length',
				fields:[
					{name:'install'},
					{name:'title'},
					{name:'extkey'},
					{name:'category'},
					{name:'version'},
					{name:'type'},
					{name:'state'},
					{name:'icon'},
					{name:'description'},
					{name:'shy'},
					{name:'installed'},
					{name:'author'},
					{name:'author_email'},
					{name:'author_company'},
					{name:'download'},
					{name:'doc'},
					{name:'typeShort'},
					{name:'nodePath'}
				]
			}),

			sortInfo:{
				field: 'title',
				direction: 'ASC'
			},
			remoteSort: false,
			groupField: 'category',
			showAction: false,
			listeners: {
				beforeload: function() {
					this.reloadButton.disable();
				},
				load: function(store, records) {
					store.filterBy(store.storeFilter);
					this.reloadButton.enable();
					if (store.showAction) {
						this.showExtension.defer(500, this);
					}
				},
				datachanged: function(store){
					Ext.getCmp('displayExtensionLabel').setText('Extensions: ' + store.data.length);
					var hasFilters = false;
					TYPO3.EM.Filters.filters.each(function (filter) {
						if (filter.active) {
							hasFilters = true;
						}
					});
					if (hasFilters) {
						this.doClearFilters.show();
					} else {
						this.doClearFilters.hide();
					}
				},
				scope: this
			},
			storeFilter: function(record,id){
				var shy = Ext.getCmp('shyFlag').getValue() ? 1 : 0;
				var installed = Ext.getCmp('installedFlag').getValue() ? 1 : 0;
				var obsolete = Ext.getCmp('obsoleteFlag').getValue() ? 1 : 0;
				var filtertext = Ext.getCmp('localSearchField').getRawValue();
				if (filtertext) {
					//filter by search string
					var re = new RegExp(Ext.escapeRe(filtertext));
					var isMatched = record.data.extkey.match(re) || record.data.title.match(re);
					if (!isMatched) {
						return false;
					}
				}
				if (obsolete && record.data.state === 'obsolete'){
					return false;
				}
				var isShy = record.data.shy == 1 || '';
				if (shy && isShy) {
					return false;
				}
				if (installed && record.data.installed === 0) {
					return false;
				}
				return true;
			}
		});

		var searchField = new Ext.ux.form.FilterField({
			store: localstore,
			id: 'localSearchField',
			width: 200
		});

		var cm = new Ext.grid.ColumnModel({
			columns: [
				this.expander,
				TYPO3.EM.GridColumns.InstallExtension,
				TYPO3.EM.GridColumns.ExtensionTitle,
				TYPO3.EM.GridColumns.ExtensionKey,
				TYPO3.EM.GridColumns.ExtensionCategory,
				TYPO3.EM.GridColumns.ExtensionAuthor,
				TYPO3.EM.GridColumns.ExtensionType,
				TYPO3.EM.GridColumns.ExtensionState
			],
			defaultSortable: true

		});



		Ext.apply(this, {
			itemId: 'em-localLocalExtensionlist',
			title: 'Local Extension List',
			loadMask: {msg: 'Loading Extensionlist ...'},
			layout: 'fit',
			store: localstore,
			cm: cm,
			plugins: [this.expander, TYPO3.EM.Filters],
			view : new Ext.grid.GroupingView({
				forceFit : true,
				groupTextTpl : '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})',
				enableRowBody: true,
				showPreview: true,
				getRowClass: this.applyRowClass,
				iconCls: 'icon-grid',
				hideGroupedColumn: true
			}),
			sm: new Ext.grid.CellSelectionModel({
				select: Ext.emptyFn
			}),
			tbar: [
				{
					xtype: 'tbtext',
					text: 'Filter:'
				},
				searchField,
				{
					iconCls: 'x-tbar-loading',
					ref: '../reloadButton',
					handler: function() {
						this.store.load();
					},
					scope: this
				}, '-', {
					text: 'Clear all Filters',
					ref: '../doClearFilters',
					handler: function() {
						TYPO3.EM.Filters.clearFilters();
					},
					scope: this,
					hidden: true
				},
				'->',
				{
					id: 'installedFlag',
					xtype: 'checkbox',
					checked: false,
					boxLabel: 'show installed only' + '&nbsp;',
					listeners: {
						check: function(checkbox, checked) {
							localstore.filterBy(localstore.storeFilter);
						}
					}
				}, {
					id: 'shyFlag',
					xtype: 'checkbox',
					checked: true,
					boxLabel: 'show shy extensions' + '&nbsp;',
					listeners: {
						check: function(checkbox, checked) {
							localstore.filterBy(localstore.storeFilter);
						}
					}
				},{
					id: 'obsoleteFlag',
					xtype: 'checkbox',
					checked: true,
					boxLabel: 'show obsolete extensions' + '&nbsp;',
					listeners: {
						check: function(checkbox, checked) {
							localstore.filterBy(localstore.storeFilter);
						}
					}
				}
			],
			bbar:[
				{
					xtype: 'tbtext',
					text: 'loading Extension list ...',
					id: 'displayExtensionLabel',
					style: {fontWeight: 'bold'}
				},
				'->',
				{
					text:'Upload Extension',
					handler : function(){
						TYPO3.EM.Tools.uploadExtension();
					}
				}, ' ', {
					text:'Clear Grouping',
					handler : function(){
						localstore.clearGrouping();
					}
				}
			]
		});

		TYPO3.EM.LocalList.superclass.initComponent.apply(this, arguments);

		/* get install / uninstall clicks */
		this.on('cellclick', function(grid, rowIndex, columnIndex, event) {
			var record = grid.getStore().getAt(rowIndex);  // Get the Record

			if (columnIndex === 1) { // column with install / remove images
				if (event.getTarget('.installExtension', 1)) {
					// install extension
				}
				if (event.getTarget('.removeExtension', 1)) {
					// remove extension
				}
			}
		}, this);

	},


	showExtension: function() {
		var store = Ext.StoreMgr.lookup('localextensionstore');
		var row = store.find('extkey', store.showAction);

		if (row) {
			this.expander.expandRow(row);
			this.getSelectionModel().selectRow(row);
			this.getView().focusRow(row);
		}
		store.showAction = false;
	},

	onRender: function() {
		TYPO3.EM.LocalList.superclass.onRender.apply(this, arguments);
	},

	afterRender: function() {
		TYPO3.EM.LocalList.superclass.afterRender.apply(this, arguments);
	}



});

Ext.reg('TYPO3.EM.LocalList', TYPO3.EM.LocalList);
