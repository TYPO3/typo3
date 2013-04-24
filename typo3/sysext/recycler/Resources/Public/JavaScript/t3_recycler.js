/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Julian Kleinhans <typo3@kj187.de>
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
 * ExtJS for the 'recycler' extension.
 * Contains the Recycler functions
 *
 * @author	Julian Kleinhans <typo3@kj187.de>
 * @author  Erik Frister <erik_frister@otq-solutions.com>
 * @author  Steffen Kamper <steffen@typo3.org>
 */

Ext.ns('Recycler');

/****************************************************
 * row expander
 ****************************************************/
Recycler.Expander = new Ext.grid.RowExpander({
	tpl : new Ext.Template(
		'<dl class="recycler-table-list-entry-details">' +
			'<dt>' + TYPO3.l10n.localize('table') + ': </dt><dd>{table}</dd>' +
			'<dt>' + TYPO3.l10n.localize('crdate') + ': </dt><dd>{crdate}</dd>' +
			'<dt>' + TYPO3.l10n.localize('tstamp') + ': </dt><dd>{tstamp}</dd>' +
			'<dt>' + TYPO3.l10n.localize('owner') + ': </dt><dd>{owner} (UID: {owner_uid})</dd>' +
			'<dt>' + TYPO3.l10n.localize('path') + ': </dt><dd>{path}</dd>' +
		'</dl>'
	)
});


/****************************************************
 * Main store
 ****************************************************/
Recycler.MainStore = new Ext.data.Store({
	storeId: 'deletedRecordsStore',
	reader: new Ext.data.JsonReader({
		totalProperty: 'total',
		root: 'rows'
	}, [
		{name: 'uid', type: 'int'},
		{name: 'pid', type: 'int'},
		{name: 'record', mapping: 'title'},
		{name: 'crdate'},
		{name: 'tstamp'},
		{name: 'owner'},
		{name: 'owner_uid'},
		{name: 'tableTitle'},
		{name: 'table'},
		{name: 'path'}
	]),
	sortInfo: {
		field: 'record',
		direction: "ASC"
	},
	groupField: 'table',
	url: TYPO3.settings.Recycler.ajaxController + '&cmd=getDeletedRecords',
	baseParams: {
		depth: TYPO3.settings.Recycler.depthSelection,
		startUid: TYPO3.settings.Recycler.startUid,
		pagingSizeDefault: TYPO3.settings.Recycler.pagingSize,
		table: TYPO3.settings.Recycler.tableSelection
	}

});

/****************************************************
 * Simple table store
 ****************************************************/
Recycler.TableStore = new Ext.data.Store({
	url: TYPO3.settings.Recycler.ajaxController + '&startUid=' + TYPO3.settings.Recycler.startUid + '&cmd=getTables' + '&depth=' + TYPO3.settings.Recycler.depthSelection,
	reader: new Ext.data.ArrayReader({}, [
		{name: 'table', type: 'string'},
		{name: 'records', type: 'int'},
		{name: 'valueField', type: 'string'},
		{name: 'tableTitle', type: 'string'}
	]),
	listeners: {
		'load': {
			fn: function(store, records) {
				Ext.getCmp('tableSelector').setValue(TYPO3.settings.Recycler.tableSelection);
			},
			single: true
		}
	}
})

/****************************************************
 * Confirmation Window
 ****************************************************/
Recycler.ConfirmWindow = Ext.extend(Ext.Window, {

	width: 300,
	height: 200,

	title: '',
	confirmText: '',
	confirmQuestion: '',
	records: [],
	hideRecursive: false,
	showRecursiveCheckbox: false,
	arePagesAffected: false,
	command: '',
	template: new Ext.XTemplate(
			'<ul class="recycler-table-list">',
			'<tpl for=".">',
				'<li>{[values]}</li>',
			'</tpl>',
			'</ul>'
	),
	initComponent:function() {
		Ext.apply(this, {
			xtype: 'form',
			bodyCssClass: 'recycler-messagebox',
			modal: true,

			items: [
				{
					xtype: 'label',
					text: this.confirmText
				}, {
					xtype: 'displayfield',
					tpl:  this.template,
					data: this.tables
				}, {
					xtype: 'label',
					text:  this.confirmQuestion
				}, {
					xtype: 'checkbox',
					boxLabel: TYPO3.l10n.localize('boxLabel_undelete_recursive'),
					name: 'recursiveCheckbox',
					disabled: !this.showRecursiveCheckbox,
					itemId: 'recursiveCheck',
					hidden: this.hideRecursive // hide the checkbox when frm is used to permanently delete
				}
			],
			buttons: [
				{
					text: TYPO3.l10n.localize('yes'),
					scope: this,
					handler: function(button, event) {
						var tcemainData = [];

						for (var i=0; i < this.records.length; i++) {
							tcemainData[i] = [this.records[i].data.table, this.records[i].data.uid];
						}
						Ext.Ajax.request({
							url: TYPO3.settings.Recycler.ajaxController + '&cmd=' + this.command,
							params: {
								'data': Ext.encode(tcemainData),
								'recursive': this.getComponent('recursiveCheck').getValue()
							},
							callback: function(options, success, response) {
								if (response.responseText === "1") {
									// reload the records and the table selector
									Recycler.MainStore.reload();
									Recycler.TableStore.reload();
									if (this.arePagesAffected) {
										Recycler.Utility.updatePageTree();
									}
								} else {
									Ext.MessageBox.show({
										title: 'ERROR',
										msg: response.responseText,
										buttons: Ext.MessageBox.OK,
										icon: Ext.MessageBox.ERROR
									});
								}
							}
						});

						this.close();
					}
				},{
					text: TYPO3.l10n.localize('no'),
					scope: this,
					handler: function(button, event) {
						this.close();
					}
				}
			]
		});
		Recycler.ConfirmWindow.superclass.initComponent.apply(this, arguments);
	}
});

/****************************************************
 * Utility functions
 ****************************************************/
Recycler.Utility = {
	updatePageTree: function() {
		if (top && top.content && top.content.nav_frame && top.content.nav_frame.Tree) {
			top.content.nav_frame.Tree.refresh();
		}
	},

	// not used?
	filterGrid: function(grid, component) {
		var filterText = component.getValue();

		Recycler.MainStore.setBaseParam('filterTxt', filterText);
		// load the datastore
		Recycler.MainStore.load({
			params: {
				start: 0
			}
		});
	},

	/****************************************************
	 * permanent deleting function
	 ****************************************************/

	function_delete: function(button, event) {
		Recycler.Utility.rowAction(
			'doDelete',
			TYPO3.l10n.localize('cmd_doDelete_confirmText'),
			TYPO3.l10n.localize('title_delete'),
			TYPO3.l10n.localize('text_delete')
		);
	},

	/****************************************************
	 * Undeleting function
	 ****************************************************/

	function_undelete: function(button, event) {
		Recycler.Utility.rowAction(
			'doUndelete',
			TYPO3.l10n.localize('sure'),
			TYPO3.l10n.localize('title_undelete'),
			TYPO3.l10n.localize('text_undelete')
		);
	},

	/****************************************************
	 * Row action function   ( deleted or undeleted )
	 ****************************************************/

	rowAction: function(command, confirmQuestion, confirmTitle, confirmText) {
			// get the 'undeleted records' grid object
		var records = Recycler.Grid.getSelectionModel().getSelections();

		if (records.length > 0) {

				// check if a page is checked
			var recursiveCheckbox = false;
			var arePagesAffected = false;
			var tables = [];
			var hideRecursive = ('doDelete' == command);

			for (iterator=0; iterator < records.length; iterator++) {
				if (tables.indexOf(records[iterator].data.table) < 0) {
					tables.push(records[iterator].data.table);
				}
				if (command == 'doUndelete' && records[iterator].data.table == 'pages' ) {
					recursiveCheckbox = true;
					arePagesAffected = true;
				}
			}

			var frmConfirm = new Recycler.ConfirmWindow({
				title: confirmTitle,
				records: records,
				tables: tables,
				confirmText: confirmText,
				confirmQuestion: confirmQuestion,
				hideRecursive: hideRecursive,
				recursiveCheckbox: recursiveCheckbox,
				arePagesAffected: arePagesAffected,
				command: command
			}).show();

		} else {
				// no row selected
			Ext.MessageBox.show({
				title: TYPO3.l10n.localize('error_NoSelectedRows_title'),
				msg: TYPO3.l10n.localize('error_NoSelectedRows_msg'),
				buttons: Ext.MessageBox.OK,
				minWidth: 300,
				minHeight: 200,
				icon: Ext.MessageBox.ERROR
			});
		}
	},

	/****************************************************
	 * pluggable renderer
	 ****************************************************/

	renderTopic: function (value, p, record) {
		return String.format('{0}', value, record.data.table, record.data.uid, record.data.pid);
	}
};

/****************************************************
 * Grid SelectionModel
 ****************************************************/
Recycler.SelectionModel = new Ext.grid.CheckboxSelectionModel({
	singleSelect: false
});

/****************************************************
 * Grid container
 ****************************************************/
Recycler.GridContainer = Ext.extend(Ext.grid.GridPanel, {
	layout: 'fit',
	renderTo: TYPO3.settings.Recycler.renderTo,
	width: '98%',
	frame: true,
	border: false,
	defaults: {autoScroll: false},
	plain: true,

	initComponent : function() {
		Ext.apply(this, {
			id: 'delRecordId',
			stateful: true,
			stateId: 'recyclerGrid',
			stateEvents: ['columnmove', 'columnresize', 'sortchange', 'expand', 'collapse'],
			loadMask: true,
			stripeRows: true,
			collapsible: false,
			animCollapse: false,
			store: Recycler.MainStore,
			cm: new Ext.grid.ColumnModel([
				Recycler.SelectionModel,
				Recycler.Expander,
				{header: "UID", width: 10, sortable: true, dataIndex: 'uid'},
				{header: "PID", width: 10, sortable: true, dataIndex: 'pid'},
				{id: 'record', header: TYPO3.l10n.localize('records'), width: 60, sortable: true, dataIndex: 'record', renderer: Recycler.Utility.renderTopic},
				{id: 'table', header: TYPO3.l10n.localize('table'), width: 20, sortable: true, dataIndex: 'tableTitle'}
			]),
			viewConfig: {
				forceFit: true
			},
			sm: Recycler.SelectionModel,
			plugins: [Recycler.Expander, new Ext.ux.plugins.FitToParent()],
			bbar: [
				{

					/****************************************************
					 * Paging toolbar
					 ****************************************************/
					id: 'recordPaging',
					xtype: 'paging',
					store: Recycler.MainStore,
					pageSize: TYPO3.settings.Recycler.pagingSize,
					displayInfo: true,
					displayMsg: TYPO3.l10n.localize('pagingMessage'),
					emptyMsg: TYPO3.l10n.localize('pagingEmpty')
				}, '-', {
					/****************************************************
					 * Delete button
					 ****************************************************/
					xtype: 'button',
					width: 80,
					id: 'deleteButton',
					text: TYPO3.l10n.localize('deleteButton_text'),
					tooltip: TYPO3.l10n.localize('deleteButton_tooltip'),
					iconCls: 'delete',
					disabled: TYPO3.settings.Recycler.deleteDisable,
					handler: Recycler.Utility.function_delete
				}, {
					/****************************************************
					 * Undelete button
					 ****************************************************/
					xtype: 'button',
					width: 80,
					id: 'undeleteButton',
					text: TYPO3.l10n.localize('undeleteButton_text'),
					tooltip: TYPO3.l10n.localize('undeleteButton_tooltip'),
					iconCls: 'undelete',
					handler: Recycler.Utility.function_undelete
				}
			],

			tbar: [
				TYPO3.l10n.localize('search'), ' ',
					new Ext.app.SearchField({
					store: Recycler.MainStore,
					width: 200
				}),
				'-', {
					xtype: 'tbtext',
					text: TYPO3.l10n.localize('depth') + ':'
				},{

					/****************************************************
					 * Depth menu
					 ****************************************************/

					xtype: 'combo',
					stateful: true,
					stateId: 'depthCombo',
					stateEvents: ['select'],
					width: 150,
					lazyRender: true,
					valueField: 'depth',
					displayField: 'label',
					id: 'depthSelector',
					mode: 'local',
					emptyText: TYPO3.l10n.localize('depth'),
					selectOnFocus: true,
					triggerAction: 'all',
					editable: false,
					forceSelection: true,
					hidden: TYPO3.l10n.localize('showDepthMenu'),
					store: new Ext.data.SimpleStore({
						autoLoad: true,
						fields: ['depth','label'],
						data : [
							['0', TYPO3.l10n.localize('depth_0')],
							['1', TYPO3.l10n.localize('depth_1')],
							['2', TYPO3.l10n.localize('depth_2')],
							['3', TYPO3.l10n.localize('depth_3')],
							['4', TYPO3.l10n.localize('depth_4')],
							['999', TYPO3.l10n.localize('depth_infi')]
						]
					}),
					value: TYPO3.settings.Recycler.depthSelection,
					listeners: {
						'select': {
							fn: function(cmp, rec, index) {
								var depth = rec.get('depth');
								Recycler.MainStore.setBaseParam('depth', depth);
								Recycler.MainStore.load({
									params: {
										start: 0
									}
								});

								Ext.getCmp('tableSelector').store.load({
									params: {
										depth: depth
									}
								});
							}
						}
					}
				},'-',{
					xtype: 'tbtext',
					text: TYPO3.l10n.localize('tableMenu_label')
				},{

					/****************************************************
					 * Table menu
					 ****************************************************/

					xtype: 'combo',
					lazyRender: true,
					stateful: true,
					stateId: 'tableCombo',
					stateEvents: ['select'],
					valueField: 'valueField',
					displayField: 'tableTitle',
					id: 'tableSelector',
					width: 220,
					mode: 'local',
					emptyText: TYPO3.l10n.localize('tableMenu_emptyText'),
					selectOnFocus: true,
					triggerAction: 'all',
					editable: false,
					forceSelection: true,

					store: Recycler.TableStore,
					valueNotFoundText: String.format(TYPO3.l10n.localize('noValueFound'), TYPO3.settings.Recycler.tableSelection),
					tpl: '<tpl for="."><tpl if="records &gt; 0"><div ext:qtip="{table} ({records})" class="x-combo-list-item">{tableTitle} ({records}) </div></tpl><tpl if="records &lt; 1"><div ext:qtip="{table} ({records})" class="x-combo-list-item x-item-disabled">{tableTitle} ({records}) </div></tpl></tpl>',
					listeners: {
						'select': {
							fn: function(component, record, index) {
								var table = record.get('valueField');

								// do not reload if the table selected has no deleted records - hide all records
								if (record.get('records') <= 0) {
									Recycler.MainStore.filter('uid', '-1'); // never true
									return false;
								}
								Recycler.MainStore.setBaseParam('table', table);
								Recycler.MainStore.load({
									params: {
										start: 0
									}
								});
							}
						}
					}
				}
			]
		});
		Recycler.GridContainer.superclass.initComponent.apply(this, arguments);
		Recycler.TableStore.load();
	}
});

Recycler.App = {
	/**
	 * Initializes the recycler
	 *
	 * @return void
	 **/
	init: function() {
		Recycler.Grid = new Recycler.GridContainer();
		Recycler.MainStore.load();
	}
};

Ext.onReady(function(){

		//save states in BE_USER->uc
	Ext.state.Manager.setProvider(new TYPO3.state.ExtDirectProvider({
		key: 'moduleData.web_recycler.States'
	}));

	if (Ext.isObject(TYPO3.settings.Recycler.States)) {
		Ext.state.Manager.getProvider().initState(TYPO3.settings.Recycler.States);
	}

	// disable loadindicator
	Ext.UpdateManager.defaults.showLoadIndicator = false;
	// fire recycler grid
	Recycler.App.init();
});
