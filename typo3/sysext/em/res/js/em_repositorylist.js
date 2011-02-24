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
	stateEvents: ['columnmove', 'columnresize', 'sortchange', 'groupchange'],
	bodyStyle: 'padding: 10px;',
	showInstalledOnly: false,

	expander: new Ext.ux.grid.RowPanelExpander({
		id: 'RepositoryListExpander',
		createExpandingRowPanelItems: function(record, rowIndex){
			var panelItems = [
				new Ext.TabPanel({
					plain: true,
					activeTab: 0,
					defaults: {
						autoHeight: true,
						autoWidth: true
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

	initComponent:function() {
		this.repositoryListStore = new Ext.data.DirectStore({
			storeId: 'repositoryliststore',
			directFn: TYPO3.EM.ExtDirect.getRemoteExtensionList,
			idProperty: 'extkey',
			root: 'data',
			totalProperty: 'length',
			fields:[
				{name:'install'},
				{name:'title'},
				{name:'extkey'},
				{name:'category', type: 'int'},
				{name:'version'},
				{name:'alldownloadcounter', type: 'int'},
				{name:'downloadcounter', type: 'int'},
				{name:'statevalue'},
				{name:'state'},
				{name:'stateCls'},
				{name:'icon'},
				{name:'description'},
				{name:'lastuploaddate', type: 'date', dateFormat: 'timestamp'},
				{name:'authorname'},
				{name:'authoremail'},
				{name:'versions', type: 'int'},
				{name:'installed', type: 'int'},
				{name:'versionislower', type: 'bool'},
				{name:'existingVersion'},
				{name:'exists', type: 'int'},
				{name:'relevance', type: 'int'}
			],
			paramNames: {
				start : 'start',
				limit : 'limit',
				sort : 'sort',
				dir : 'dir',
				query: 'query'
			},
			baseParams: {
				query: '',
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
					var control = Ext.getCmp('rsearchField');
					if (control.getValue == '') {
						return false;
					}
					store.setBaseParam('rep', Ext.getCmp('repCombo').getValue());
					store.setBaseParam('installedOnly', this.showInstalledOnly);
					if (!this.showInstalledOnly) {
						this.filterMenuButton.removeClass('bold');
					} else {
						this.filterMenuButton.addClass('bold');
					}

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
					if (records.length === 0) {

					} else {

					}
				},
				scope: this
			},
			highlightSearch: function(value) {
				var control = Ext.getCmp('rsearchField');
				if (control) {
					var filtertext = control.getRawValue();
					if (filtertext) {
						var re = new RegExp(Ext.escapeRe(filtertext), 'gi');
						var result = re.exec(value) || [];
						if (result.length) {
							return value.replace(result[0], '<span class="filteringList-highlight">' + result[0] + '</span>');
						}
					}
				}
				return value;
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
			width: 260,
			specialKeyOnly: true,
			emptyText: TYPO3.lang.msg_startTyping
		});

		var cm = new Ext.grid.ColumnModel({
			columns: [
				TYPO3.settings.EM.inlineToWindow == 1 ? TYPO3.EM.GridColumns.DummyColumn : this.expander,
				TYPO3.EM.GridColumns.ImportExtension,
				TYPO3.EM.GridColumns.ExtensionTitle,
				TYPO3.EM.GridColumns.ExtensionKey,
				TYPO3.EM.GridColumns.ExtensionCategoryRemote,
				TYPO3.EM.GridColumns.ExtensionRemoteAuthor,
				TYPO3.EM.GridColumns.ExtensionType,
				TYPO3.EM.GridColumns.Relevance,
				TYPO3.EM.GridColumns.ExtensionDownloads,
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
			plugins: TYPO3.settings.EM.inlineToWindow == 1 ? [TYPO3.EM.RemoteFilters] : [this.expander, TYPO3.EM.RemoteFilters],
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
				' ',
				{
					text: TYPO3.lang.cmd_filter,
					tooltip: TYPO3.lang.help_remoteFilter,
					scale: 'small',
					iconAlign: 'right',
					ref: '../filterMenuButton',
					menu : {
						items: [
							{
								checked: true,
								group: 'installFilter',
								text: TYPO3.lang.display_all,
								handler: function(item, event) {
									this.showInstalledOnly = 0;
									this.store.reload();
								},
								scope: this
							}, {
								checked: false,
								group: 'installFilter',
								text: TYPO3.lang.display_installedOnly,
								handler: function(item, event) {
									this.showInstalledOnly = 1;
									this.store.reload();
								},
								scope: this
							}, {
								checked: false,
								group: 'installFilter',
								text: TYPO3.lang.display_updatesOnly,
								handler: function(item, event) {
									this.showInstalledOnly = 2;
									this.store.reload();
								},
								scope: this
							}
						]
					}
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
				'->',
				{
					xtype: 'tbtext',
					text: TYPO3.lang.repository + ': '
				},
				TYPO3.EM.RepositoryCombo,
				{
					iconCls: 'x-btn-repupdate',
					handler: this.repositoryUpdate,
					tooltip: TYPO3.lang.cmd_RetrieveUpdate,
					scope: this,
					hidden: !TYPO3.settings.EM.allowRepositoryUpdate
				},
				{
					xtype: 'container',
					id: 'repListInfo',
					html: ''
				},
				' '

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
			var info = TYPO3.EM.Layouts.repositoryInfo().applyTemplate(newValue.data);
            Ext.getCmp('repListInfo').update(info);
			this.repositoryListStore.reload({ params: {repository: newValue.data.uid} });
		}, this);
		this.repositoryStore.load({
			callback: function() {
				if (this.getCount() == 0) {
					TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error, TYPO3.lang.repository_notfound, 15);
				} else {
					var rec = this.getById(TYPO3.settings.EM.selectedRepository);
					if (!rec) {
						TYPO3.settings.EM.selectedRepository = 1;
						rec = this.getById(TYPO3.settings.EM.selectedRepository);
					}
					TYPO3.EM.RepositoryCombo.setValue(TYPO3.settings.EM.selectedRepository);
					Ext.getCmp('repListInfo').update(TYPO3.EM.Layouts.repositoryInfo().applyTemplate(rec.data));
				}
			}

		});
		TYPO3.EM.RepositoryList.superclass.onRender.apply(this, arguments);

		this.on('rowcontextmenu', function(grid, rowIndex, event) {
			if (event.button === 2) {
				var record = grid.store.getAt(rowIndex);
				if (record.data.versions > 1) {
					var menu = new Ext.menu.Menu({
						record: record,
						items: [{
							text: String.format(TYPO3.lang.ext_import_versions, record.data.title)
								+ ' (' + String.format(TYPO3.lang.ext_import_versions_available, record.data.versions) + ')',
							iconCls: 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-import',
							handler: function() {
								var record = this.ownerCt.record.data;
								var link = TYPO3.settings.EM.scriptLink
									+ '&nodoc=1&ter_connect=1&ter_search=' + record.extkey  +'&CMD[importExtInfo]=' + record.extkey;
								TYPO3.EM.ImportWindow = new TYPO3.EM.InstallWindow({
									title: String.format(TYPO3.lang.ext_import_versions, record.title) + ' (' + record.extkey + ')',
									record: record,
									installAction: 'import',
									listeners: {
										close: function() {
											TYPO3.EM.Tools.refreshMenu(record, 'import');
										}
									}
								}).show(true, function(){
									Ext.getCmp('emInstallIframeWindow').setUrl(link);
								});
							}
						}]
					}).showAt(event.getXY());
				}
    			event.stopEvent();
			}
		});

		this.on('rowdblclick',function(grid, rowIndex, event) {
			if (TYPO3.settings.EM.inlineToWindow == 1) {
				this.showExtInfoInWindow(rowIndex);
			}
		});
		this.on('cellclick',function(grid, rowIndex, columnIndex, event) {
			if (TYPO3.settings.EM.inlineToWindow == 1 && columnIndex == 2) {
				this.showExtInfoInWindow(rowIndex);
			}
		});
	},

	repositoryUpdate: function() {
		var m = Ext.MessageBox.wait(TYPO3.lang.msg_longwait, TYPO3.lang.repository_update);
		var index = TYPO3.EM.RepositoryCombo.getValue();
		if (!index) {
			return;
		}
		var record = this.repositoryStore.getAt(index - 1);
		TYPO3.EM.ExtDirect.repositoryUpdate(index, function(response) {
			if (!response.success) {
				if (response.rep == 0) {
					TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error, response.errormsg, 15);
				} else {
					TYPO3.Flashmessage.display(TYPO3.Severity.notice, TYPO3.lang.repository_update_not_needed, response.errormsg, 5);
				}
			} else {
				TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.repository_updated, String.format(TYPO3.lang.repository_extensions_count, response.data.count), 10);
				record.set('count', response.data.count);
				record.set('updated', response.data.updated);
				Ext.getCmp('repListInfo').update(TYPO3.EM.Layouts.repositoryInfo().applyTemplate(record.data));
			}
			m.hide();
		}, this);
	},

	showExtInfoInWindow: function(index) {
		var record = this.store.getAt(index);
		var id = 'window-extinfo-' + record.data.extkey;
		if (Ext.WindowMgr.get(id)) {
			Ext.WindowMgr.bringToFront(id);
		} else {
			new Ext.Window({
				title: TYPO3.EM.Tools.renderExtensionTitle(record),
				layout: 'fit',
				width: 600,
				height: 350,
				items : this.expander.createExpandingRowPanelItems(record,index),
				id: id
			}).show();
		}
	}
});

Ext.reg('remoteextlist', TYPO3.EM.RepositoryList);