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
Ext.ns('TYPO3.EM', 'TYPO3.EM.GridColumns', 'TYPO3.EM.ExtDirect');

TYPO3.EM.RepositoryList = Ext.extend(Ext.grid.GridPanel, {
	border:false,
	stripeRows: true,
	stateful: true,
	stateId: 'RepositoryList',
	stateEvents: ['columnmove', 'columnresize', 'sortchange', 'groupchange', 'expand', 'collapse'],

	expander: new Ext.ux.grid.RowPanelExpander({
		createExpandingRowPanelItems: function(record, rowIndex){
			var panelItems = [
				new Ext.TabPanel({
					plain: true,
					activeTab: 0,
					defaults: {
						autoHeight: true
					},
					record: record,
					items:[
						{
							title: TYPO3.lang.details_info,
							listeners: {
								activate: function(panel) {
									panel.update(TYPO3.EM.Layouts.remoteExtensionInfo().applyTemplate(panel.ownerCt.record.data));
								}
							}
						}
					]
				})
			];
			return panelItems;
		}
	}),

	listeners: { /*
		beforestaterestore: function(grid, state) {
			console.log('restore:', state);
			return true;
		},
		statesave: function(grid, state) {
			console.log('save:', state);
		},
		beforestatesave: function(grid, state) {
			console.log('beforesave:', state);
			return true;
		}  */
	},

	initComponent:function() {
		this.repositoryListStore = new Ext.data.DirectStore({
			storeId: 'repositoryliststore',
			directFn: TYPO3.EM.ExtDirect.getRemoteExtensionList,
			//paramsAsHash: false,
			idProperty: 'extkey',
			root: 'data',
			totalProperty: 'length',
			fields:[
				{name:'install'},
				{name:'title'},
				{name:'extkey'},
				{name:'categoryvalue'},
				{name:'version'},
				{name:'alldownloadcounter', type: 'int'},
				{name:'statevalue'},
				{name:'state'},
				{name:'icon'},
				{name:'description'},
				{name:'lastuploaddate'},
				{name:'author', mapping: 'authorname'},
				{name:'author_email', mapping: 'authoremail'},
				{name:'versions', type: 'int'}
			],
			paramNames: {
				start : 'start',
				limit : 'limit',
				sort : 'sort',
				dir : 'dir',
				query: 'query'
			},
			baseParams: {
				query: '*',
				repository: 1,
				start: 0,
				limit: 50

			},
			remoteSort: true,
			sortInfo:{
				field:'title',
				direction:"ASC"
			},
			listeners: {
				beforeload: function(store, records){
					store.setBaseParam('rep', Ext.getCmp('repCombo').getValue());
				},
				load: function(store, records){
					var hasFilters = false;
					TYPO3.EM.RemoteFilters.filters.each(function (filter) {
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
			}

		});

		this.repositoryStore = new Ext.data.DirectStore({
			storeId: 'repositories',
			idProperty: 'uid',
			directFn: TYPO3.EM.ExtDirect.getRepositories,
			root: 'data',
			totalProperty: 'length',
			fields : ['title', 'uid', 'updated', 'count', 'selected'],
			paramsAsHash: true
		});

		var searchField = new Ext.ux.form.SearchField({
			id: 'rsearchField',
			store: this.repositoryListStore,
			width: 200
		});

		var cm = new Ext.grid.ColumnModel({
			columns: [
				this.expander,
				TYPO3.EM.GridColumns.ImportExtension,
				TYPO3.EM.GridColumns.ExtensionTitle,
				TYPO3.EM.GridColumns.ExtensionKey,
				TYPO3.EM.GridColumns.ExtensionCategoryRemote,
				TYPO3.EM.GridColumns.ExtensionAuthor,
				TYPO3.EM.GridColumns.ExtensionType,
				TYPO3.EM.GridColumns.ExtensionStateValue
			],
			defaults: {
				sortable: true,
				hideable:false
			}

		});

		Ext.apply(this, {
			loadMask: {msg: TYPO3.lang.action_loadingRepositoryExtlist},
			store: this.repositoryListStore,
			cm: cm,
			plugins: [this.expander, TYPO3.EM.RemoteFilters],
			viewConfig: {
				forceFit: true,
				enableRowBody: true,
				showPreview: true,
				getRowClass: this.applyRowClass,
				iconCls: 'icon-grid'
			},
			sm: new Ext.grid.CellSelectionModel({
				select: Ext.emptyFn
			}),
			tbar: [
				{
					xtype: 'tbtext',
					text: TYPO3.lang.cmd_filter + ':',
					qTip: TYPO3.lang.help_remoteFilter
				},
				searchField, ' ', {
					text: TYPO3.lang.cmd_ClearAllFilters,
					ref: '../doClearFilters',
					handler: function() {
						TYPO3.EM.RemoteFilters.clearFilters();
					},
					scope: this,
					hidden: true
				},
				' ', '-',
				{
					xtype: 'tbtext',
					text: TYPO3.lang.repository + ': '
				},
				TYPO3.EM.RepositoryCombo,
				{
					xtype: 'container',
					id: 'repInfo',
					html: ''
				},
				' ',
				{
					xtype: 'button',
					text: TYPO3.lang.cmd_RetrieveUpdate,
					scope: this,
					handler: this.repositoryUpdate
				}

			],
			bbar:[
				'->', {
					id: 'rresultPaging',
					xtype: 'paging',
					store: this.repositoryListStore,
					pageSize: 50,
					displayInfo: true,
					emptyMsg: TYPO3.lang.action_searching
				}
			]
		});

		TYPO3.EM.RepositoryList.superclass.initComponent.apply(this, arguments);
	},

	onRender:function() {
		TYPO3.EM.RepositoryCombo.store = this.repositoryStore;
		TYPO3.EM.RepositoryCombo.on('select', function(comboBox, newValue, oldValue) {
            Ext.getCmp('repInfo').update(TYPO3.EM.Layouts.repositoryInfo().applyTemplate(newValue.data));
			this.repositoryListStore.reload({ params: {repository: newValue.data.uid} });
		}, this);
		this.repositoryStore.load({
			callback: function() {
				if (this.getCount() == 0) {
					TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error, TYPO3.lang.repository_notfound, 15);
				} else {
					var rec = this.getById(TYPO3.settings.EM.selectedRepository);
					TYPO3.EM.RepositoryCombo.setValue(TYPO3.settings.EM.selectedRepository);
					Ext.getCmp('repInfo').update(TYPO3.EM.Layouts.repositoryInfo().applyTemplate(rec.data));
				}
			}

		});
		TYPO3.EM.RepositoryList.superclass.onRender.apply(this, arguments);
	},

	repositoryUpdate: function() {
		var m = Ext.MessageBox.wait(TYPO3.lang.msg_longwait, TYPO3.lang.repository_update);
		TYPO3.EM.ExtDirect.repositoryUpdate(1, function(response) {
			if (!response.success) {
				if (response.rep == 0) {
					TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error, response.errormsg, 15);
				} else {
					TYPO3.Flashmessage.display(TYPO3.Severity.notice, TYPO3.lang.repository_update_not_needed, response.errormsg, 5);
				}
			} else {
				TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.repository_updated, String.format(TYPO3.lang.repository_extensions_count, response.data.count), 10);
				this.repositoryListStore.load();
			}
			m.hide();
		}, this);

	}
});

Ext.reg('remoteextlist', TYPO3.EM.RepositoryList);