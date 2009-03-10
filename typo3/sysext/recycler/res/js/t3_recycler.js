/***************************************************************
*  Copyright notice
*
*  (c) 2009 Julian Kleinhans <typo3@kj187.de>
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
 * @package	TYPO3
 * @subpackage	tx_recycler
 * @version $Id: t3_recycler.js 17680 2009-03-10 18:02:21Z ohader $
 */
Ext.onReady(function() {
	//Quicktips initialisieren
	Ext.QuickTips.init();

	// @todo: description
	// Ext.form.Field.prototype.msgTarget = 'side';

	// disable loadindicator
	Ext.UpdateManager.defaults.showLoadIndicator = false;

	// fire recycler grid
	new Recycler.grid.init();
});

Recycler.grid = {
	/**
	 * Initializes the grid
	 *
	 * @return void
	 **/
	init: function() {
		/****************************************************
		 * row expander
		 ****************************************************/

		var expander = new Ext.grid.RowExpander({
			tpl : new Ext.Template(
				'<br/>' +
				'<p style="margin-left:45px;"><b>' + Recycler.lang.table + ':</b> {table}</p>' +
				'<p style="margin-left:45px;"><b>' + Recycler.lang.crdate + ':</b> {crdate}</p>' +
				'<p style="margin-left:45px;"><b>' + Recycler.lang.tstamp + ':</b> {tstamp}</p>' +
				'<p style="margin-left:45px;"><b>' + Recycler.lang.owner + ':</b> {owner} (UID: {owner_uid})</p>' +
				'<p style="margin-left:45px;"><b>' + Recycler.lang.path + ':</b> {path}</p>' +
				'<br/>'
			)
		});

		/****************************************************
		 * pluggable renderer
		 ****************************************************/

		var renderTopic = function (value, p, record) {
			return String.format('{0}', value, record.data.table, record.data.uid, record.data.pid);
		};

		/****************************************************
		 * row checkbox
		 ****************************************************/

		var sm = new Ext.grid.CheckboxSelectionModel({
			singleSelect: false
		});

		/****************************************************
		 * filter grid
		 ****************************************************/

		var filterGrid = function(grid, cmp) {
			var filterText = cmp.getValue();

			addParameters('filterTxt', filterText);

			// load the datastore
			grid.getStore().load({
				params: {
					start: 0
				}
			});
		};

		/****************************************************
		 * grid datastore
		 ****************************************************/
		var gridDs = new Ext.data.Store({
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
			url: Recycler.statics.ajaxController + '&depth=' + Recycler.statics.depthSelection + '&startUid=' + Recycler.statics.startUid + '&cmd=getDeletedRecords&pagingSizeDefault=' + Recycler.statics.pagingSize + '&table=' + Recycler.statics.tableSelection

		});

		/****************************************************
		 * add param to grid store GET
		 ****************************************************/
		var addParameters = function(key, value) {
			var grid = tabs.getComponent(0).getComponent(0);

			var url = grid.getStore().proxy.conn.url;
			var urlParts = url.split('?');

			var params = urlParts[1].split('&');
			var newParams = [];
			var k = 0;	// used to specify offset if we set a value

			// add our new key / value to the new params if value is not ''
			if ('' !== value) {
				newParams[0] = key + '=' + value;
				k = 1;
			}

			// find the key and remove it
			var l = params.length;

			for (var i = 0; i < l; i ++) {
				if (params[i].indexOf(key + '=') != -1) {
					k -= 1;
					continue;
				} else {
					newParams[i + k] = params[i];
				}
			}

			// make new url from http + params
			url = urlParts[0] + '?' + newParams.join('&');


			// set the new url for the store
			grid.getStore().proxy.conn.url = url;
		};

		/****************************************************
		 * permanent deleting function
		 ****************************************************/

		var function_delete = function(ob) {
			rowAction(ob, Recycler.lang.cmd_doDelete_confirmText, 'doDelete', Recycler.lang.title_delete, Recycler.lang.text_delete);
		};

		/****************************************************
		 * Undeleting function
		 ****************************************************/

		var function_undelete = function(ob) {
			rowAction(ob, Recycler.lang.sure, 'doUndelete', Recycler.lang.title_undelete, Recycler.lang.text_undelete);
		};

		/****************************************************
		 * Row action function   ( deleted or undeleted )
		 ****************************************************/

		var rowAction = function(ob, confirmQuestion, cmd, confirmTitle, confirmText) {
				// get the 'undeleted records' grid object
			var grid = tabs.getComponent(0).getComponent(0);
			recArray = grid.getSelectionModel().getSelections();

			if (recArray.length > 0) {

					// check if a page is checked
				var recursiveCheckbox = false;
				var arePagesAffected = false;
				var tables = [];
				var hideRecursive = ('doDelete' == cmd);
				
				for (iterator=0; iterator < recArray.length; iterator++) {
					if (tables.indexOf(recArray[iterator].data.table) < 0) {
						tables.push(recArray[iterator].data.table);
					}
					if (cmd == 'doUndelete' && recArray[iterator].data.table == 'pages' ) {
						recursiveCheckbox = true;
						arePagesAffected = true;
					}
				}

				var frmConfirm = new Ext.Window({
					xtype: 'form',
					width: 300,
					height: 200, 
					modal: true,
					title: confirmTitle,
					items: [
						{
							xtype: 'label',
							text: confirmText + tables.join(', ')
						},{
							xtype: 'label',
							text:  confirmQuestion
						},{
							xtype: 'checkbox',
							boxLabel: Recycler.lang.boxLabel_undelete_recursive,
							name: 'recursiveCheckbox',
							disabled: !recursiveCheckbox,
							id: 'recursiveCheckbox',
							hidden: hideRecursive // hide the checkbox when frm is used to permanently delete
						}
					],
					buttons: [
						{
							text: Recycler.lang.yes,
							handler: function(cmp, e) {
								tcemainData = new Array();

								for (iterator=0; iterator < recArray.length; iterator++) {
									tcemainData[iterator] = [recArray[iterator].data.table, recArray[iterator].data.uid];
								}

								Ext.Ajax.request({
									url: Recycler.statics.ajaxController + '&cmd=' + cmd,
									callback: function(options, success, response) {
										if (response.responseText === "1") {
											// reload the records and the table selector
											grid.getStore().reload();
											Ext.getCmp('tableSelector').store.reload();
											if (arePagesAffected) {
												Recycler.utility.updatePageTree();
											}
										}else{
											alert('ERROR: '+response.responseText);
										}
									},
									params: {'data': Ext.encode(tcemainData), 'recursive':frmConfirm.getComponent('recursiveCheckbox').getValue() }
								});

								frmConfirm.destroy();
							}
						},{
							text: Recycler.lang.no,
							handler: function(cmp, e) {
								frmConfirm.destroy();
							}
						}
					]
				});
				frmConfirm.show();

			} else {
					// no row selected
				Ext.MessageBox.show({
					title: Recycler.lang.error_NoSelectedRows_title,
					msg: Recycler.lang.error_NoSelectedRows_msg,
					buttons: Ext.MessageBox.OK,
					minWidth: 300,
					minHeight: 200,
					icon: Ext.MessageBox.INFO
				});
			}
		};

		/****************************************************
		 * tab container
		 ****************************************************/

		var tabs = new Ext.TabPanel({
			renderTo: Recycler.statics.renderTo,
			layoutOnTabChange: true,
			activeTab: 0,
			width: '99%',
			height: 600,
			frame: true,
			border: false,
			defaults: {autoScroll: true},
			plain: true,
			buttons: [{

					/****************************************************
					 * Delete button
					 ****************************************************/

					id: 'deleteButton',
					text: Recycler.lang.deleteButton_text,
					tooltip: Recycler.lang.deleteButton_tooltip,
					iconCls: 'delete',
					disabled: Recycler.statics.deleteDisable,
					handler: function_delete
				},{

					/****************************************************
					 * Undelete button
					 ****************************************************/

					id: 'undeleteButton',
					text: Recycler.lang.undeleteButton_text,
					tooltip: Recycler.lang.undeleteButton_tooltip,
					iconCls: 'undelete',
					handler: function_undelete
				}
			],
			buttonAlign:'left',
			items:[
				{

					/****************************************************
					 * Deleted records Tab
					 ****************************************************/

					id: 'delRecordId',
					title: Recycler.lang.deletedTab,
					items: [
						{

							/****************************************************
							 * Grid
							 ****************************************************/

							xtype: 'grid',
							loadMask: true,
							store: gridDs,
							cm: new Ext.grid.ColumnModel([
								sm,
								expander,
								{header: "UID", width: 10, sortable: true, dataIndex: 'uid'},
								{header: "PID", width: 10, sortable: true, dataIndex: 'pid'},
								{id:'record',header: "Records", width: 60, sortable: true, dataIndex: 'record', renderer: renderTopic},
								{header: "Table", width: 20, sortable: true, dataIndex: 'tableTitle'}
							]),

							view: new Ext.grid.GridView({
								forceFit:true
							}),

							bbar: [
								{

									/****************************************************
									 * Paging toolbar
									 ****************************************************/
									id: 'recordPaging',
									xtype: 'paging',
									store: 'deletedRecordsStore',
									pageSize: Recycler.statics.pagingSize,
									displayInfo: true,
									displayMsg: Recycler.lang.pagingMessage,
									emptyMsg: Recycler.lang.pagingEmpty
								}
							],

							tbar: [
									Recycler.lang.search, ' ',
										new Ext.app.SearchField({
										store: gridDs,
										width: 200
									}),
									'->', {

									/****************************************************
									 * Depth menu
									 ****************************************************/

									xtype: 'combo',
									lazyRender: true,
									valueField: 'depth',
									displayField: 'label',
									id: 'depthSelector',
									mode: 'local',
									emptyText: Recycler.lang.depth,
									selectOnFocus: true,
									readOnly: true,
									triggerAction: 'all',
									editable: false,
									forceSelection: true,
									hidden: Recycler.lang.showDepthMenu,
									store: new Ext.data.SimpleStore({
										autoLoad: true,
										fields: ['depth','label'],
										data : [
											['0', Recycler.lang.depth_0],
											['1', Recycler.lang.depth_1],
											['2', Recycler.lang.depth_2],
											['3', Recycler.lang.depth_3],
											['4', Recycler.lang.depth_4],
											['999', Recycler.lang.depth_infi]
										]
									}),
									value: Recycler.statics.depthSelection,
									listeners: {
										'select': {
											fn: function(cmp, rec, index) {
												var store = tabs.getComponent(0).getComponent(0).getStore();
												var depth = rec.get('depth');

												addParameters('depth', depth);
												store.load();

												Ext.getCmp('tableSelector').store.load({
													params: {
														depth: depth
													}
												});
											}
										}
									}
								},'->',{

									/****************************************************
									 * Table menu
									 ****************************************************/

									xtype: 'combo',
									lazyRender: true,
									valueField: 'valueField',
									displayField: 'tableTitle',
									id: 'tableSelector',
									mode: 'local',
									emptyText: Recycler.lang.tableMenu_emptyText,
									selectOnFocus: true,
									readOnly: true,
									triggerAction: 'all',
									editable: false,
									forceSelection: true,

									store: new Ext.data.Store({
										autoLoad: true,
										url: Recycler.statics.ajaxController + '&startUid=' + Recycler.statics.startUid + '&cmd=getTables' + '&depth=' + Recycler.statics.depthSelection,
										reader: new Ext.data.ArrayReader({}, [
											{name: 'table', type: 'string'},
											{name: 'records', type: 'int'},
											{name: 'valueField', type: 'string'},
											{name: 'tableTitle', type: 'string'}
										]),
										listeners: {
											'load': {
												fn: function(store, records) {
													Ext.getCmp('tableSelector').setValue(Recycler.statics.tableSelection);
												},
												single: true
											}
										}
									}),
									valueNotFoundText: String.format(Recycler.lang.noValueFound, Recycler.statics.tableSelection),
									tpl: '<tpl for="."><tpl if="records &gt; 0"><div ext:qtip="{table} ({records})" class="x-combo-list-item">{tableTitle} ({records}) </div></tpl><tpl if="records &lt; 1"><div ext:qtip="{table} ({records})" class="x-combo-list-item x-item-disabled">{tableTitle} ({records}) </div></tpl></tpl>',
									listeners: {
										'select': {
											fn: function(cmp, rec, index) {
												var store = tabs.getComponent(0).getComponent(0).getStore();
												var table = rec.get('valueField');

												// do not reload if the table selected has no deleted records - hide all records
												if (rec.get('records') <= 0) {
													store.filter('uid', '-1'); // never true
													return false;
												}

												addParameters('table', table);

												store.load({
													params: {
														start: 0
													}
												});
											}
										}
									}
								}
							],

							sm: sm,
							plugins: expander,
							loadMask: true,
							stripeRows: true,
							width: '100%',
							height: 530,
							collapsible: false,
							animCollapse: false,
							frame: false,
							border: false,
							listeners: {
								'render': {
									fn: function(cmp) {
										cmp.getStore().load();
									},
									single: true
								}
							}
						}
					]
				}//,{

					/****************************************************
					 * Lost and found Tab
					 ****************************************************/
					/*
					id: 'lostAndFoundId',
					title: Recycler.staticsRecycler.statics.lostFoundTab,
					html:'Later'
				}	*/
			]
		});
	}
};


Recycler.utility = {
	updatePageTree: function() {
		if (top && top.content && top.content.nav_frame && top.content.nav_frame.Tree) {
			top.content.nav_frame.Tree.refresh();
		}
	}
};