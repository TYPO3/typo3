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
	border: false,
	plain: true,
	stripeRows: true,
	stateful: true,
	stateId: 'LocalList',
	stateEvents: ['columnmove', 'columnresize', 'sortchange', 'groupchange'],
	bodyStyle: 'padding: 10px;',

	rowExpander: new Ext.ux.grid.RowPanelExpander({
		hideable: false,
		id: 'LocalListExpander',
		createExpandingRowPanelItems: function(record, rowIndex) {
			var panelItems = [
				new Ext.TabPanel({
							plain: true,
							activeTab: 0,
							defaults: {
								cls: 'gridrowpanel',
								height: 250
							},
							record: record,
							items:[
								{
									title: TYPO3.lang.msg_info,
									html: TYPO3.EM.Layouts.showExtInfo(record.data),
									listeners: {
										activate: function(panel) {
											var updateScriptLink = Ext.fly('update-check-' + record.data.extkey);
											if (updateScriptLink) {
												updateScriptLink.on('click', function() {
													panel.getEl().mask('loading');
													TYPO3.EM.ExtDirect.getExtensionUpdateScript(record.data.extkey, function(response) {
														panel.getEl().unmask();
														if (response.success) {
															var w = new Ext.Window({
																		width: 500,
																		height: 500,
																		layout: 'fit',
																		title: TYPO3.lang.ext_details_updateScript + ' (' + record.data.extkey + ')',
																		items: [
																			{
																				xtype: 'iframePanel',
																				id: 'updateScript-' + record.data.extkey
																			}
																		]
																	}).show(true, function() {
																Ext.getCmp('updateScript-' + record.data.extkey).setUrl('mod.php?M=tools_em&nodoc=1&CMD[showExt]=' + record.data.extkey + '&SET[singleDetails]=updateModule');
															});
														} else {
															TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.cmd_update, TYPO3.lang.repository_update_not_needed, 5);
														}
													});
												}, this);
											}

										}
									}
								},
								{
									title: TYPO3.lang.msg_dbupdate,
									html: TYPO3.EM.App.loadingIndicor,
									xtype: 'form',
									disabled: record.data.installed === 0,
									listeners: {
										activate: function(panel) {
											panel.update(TYPO3.EM.App.loadingIndicor);
											TYPO3.EM.ExtDirect.getExtensionUpdate(record.data.extkey, function(response) {
												panel.update(response, true, this.readUpdateForm.createDelegate(this));
											}, this);
										}
									},
									scope: this,
									readUpdateForm: function() {
										var button = Ext.get('update-submit-' + record.data.extkey);
										Ext.apply(this.form, {
													api: {
														submit: TYPO3.EM.ExtDirect.saveExtensionConfiguration
													},
													paramsAsHash: false
												});
										if (button) {
											button.on('click', function() {
												this.doUpdate(false);
											}, this);
										}
									},
									doUpdate: function(noSave) {
										this.form.submit({
													waitMsg : noSave ? ' ' : TYPO3.lang.action_updateDatabase,
													params: {
														extkey: record.data.extkey,
														exttype: record.data.typeShort,
														noSave: noSave
													},
													success: function(form, action) {
														this.ownerCt.activeTab.update(TYPO3.EM.App.loadingIndicor);
														TYPO3.EM.ExtDirect.getExtensionUpdate(record.data.extkey, function(response) {
															this.ownerCt.activeTab.update(response, true, this.readUpdateForm.createDelegate(this));
														}, this);
													},
													failure: function(form, action) {
														if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
															TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error,
																	TYPO3.lang.msg_error + ':' + action.response.status + ': ' +
																			action.response.statusText, 5);
														}
														if (action.failureType === Ext.form.Action.SERVER_INVALID) {
															// server responded with success = false
															TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.invalid, action.result.errormsg, 5);
														}
													},
													scope: this
												});
									}
								},
								{
									title: TYPO3.lang.msg_configuration,
									xtype: 'form',
									disabled: record.data.installed === 0,
									html: TYPO3.EM.App.loadingIndicor,
									listeners: {
										activate: function(panel) {
											panel.update(TYPO3.EM.App.loadingIndicor);
											TYPO3.EM.ExtDirect.getExtensionConfiguration(record.data.extkey, function(response) {
												panel.update(response, true, this.readConfigForm.createDelegate(this));
											}, this);
										}
									},
									scope: this,
									readConfigForm: function() {
										var button = Ext.get('configuration-submit-' + record.data.extkey);
										if (!button) {
											var button = Ext.get('update-submit-' + record.data.extkey);
										}
										var select = Ext.select('.mod-menu-template-select');
										Ext.apply(this.form, {
													api: {
														submit: TYPO3.EM.ExtDirect.saveExtensionConfiguration
													},
													paramsAsHash: false
												});
										if (select) {
											var converted = new Ext.form.ComboBox({
														transform: select.elements[0],
														typeAhead: true,
														listeners: {
															beforequery: function(o) {
																o.forceAll = true;
															},
															select: function() {
																this.doSubmit(true);
															},
															scope: this
														}
													});
										}
										if (button) {
											button.on('click', function() {
												this.doSubmit(false);
											}, this);
										}
									},
									doSubmit: function(noSave) {
										this.form.submit({
													waitMsg : noSave ? ' ' : TYPO3.lang.action_saving_settings,
													params: {
														extkey: record.data.extkey,
														exttype: record.data.typeShort,
														noSave: noSave
													},
													success: function(form, action) {
														if (action.result.html) {
															this.ownerCt.activeTab.update(action.result.html, true, this.readConfigForm.createDelegate(this));
														} else {
															TYPO3.Flashmessage.display(TYPO3.Severity.ok, TYPO3.lang.msg_configuration, TYPO3.lang.configurationSaved, 5);
														}
													},
													failure: function(form, action) {
														if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
															TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error,
																	TYPO3.lang.msg_error + ':' + action.response.status + ': ' +
																			action.response.statusText, 5);
														}
														if (action.failureType === Ext.form.Action.SERVER_INVALID) {
															// server responded with success = false
															TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.invalid, action.result.errormsg, 5);
														}
													},
													scope: this
												});
									}
								},
								{
									title: TYPO3.lang.msg_files,
									xtype: 'extfilelist',
									recordData: record.data,
									cls: 'gridrowfilespanel'
								},
								{
									xtype: 'terupload',
									title: TYPO3.lang.cmd_terupload,
									recordData: record.data,
									disabled: !TYPO3.settings.EM.hasCredentials
								},
								{
									title: TYPO3.lang.msg_developerinformation,
									html: '<div class="loading-indicator">' + TYPO3.lang.action_loading + '</div>',
									listeners: {
										activate: function(panel) {
											TYPO3.EM.ExtDirect.getExtensionDevelopInfo(record.data.extkey, function(response) {
												panel.update(response);
											});
										}
									}
								},
								{
									title: TYPO3.lang.details_maintenance,
									//disabled: record.data.installed === 0,
									html: TYPO3.EM.App.loadingIndicor,
									listeners: {
										activate: function(panel) {
											TYPO3.EM.ExtDirect.getExtensionBackupDelete(record.data.extkey, function(response) {
												panel.update(response, true, this.readBackupDeleteLinks.createDelegate(this));
											}, this);
										}
									},
									scope: this,
									readBackupDeleteLinks: function() {
										var emconflink = Ext.select('a.emconfLink');
										if (emconflink.elements.length) {
											var link = emconflink.elements[0];
											link.removeAttribute('onclick');
											Ext.get(link).on('click', function() {
												this.waitBox = Ext.Msg.wait(TYPO3.lang.ext_details_update_em_conf, record.data.extkey);
												TYPO3.EM.ExtDirect.cleanEmConf(record.data.extkey, function(response) {
													this.waitBox.hide();
													TYPO3.Flashmessage.display(TYPO3.Severity.ok, TYPO3.lang.ext_details_update_em_conf, response.result, 5);
												}, this);
											});
										}
										var deletelink = Ext.select('a.deleteLink');
										if (deletelink.elements.length) {
											var link = deletelink.elements[0];
											link.removeAttribute('onclick');
											Ext.get(link).on('click', function() {
												this.waitBox = Ext.Msg.wait(TYPO3.lang.extDelete_from_server, record.data.extkey);
												TYPO3.EM.ExtDirect.deleteExtension(record.data.extkey, function(response) {
													this.waitBox.hide();
													if (response.success) {
														TYPO3.Flashmessage.display(TYPO3.Severity.ok, TYPO3.lang.msg_extkexDeletedSuccess, response.result, 5);
														Ext.StoreMgr.get('localstore').remove(record);
													} else {
														TYPO3.Flashmessage.display(TYPO3.Severity.error, response.error, response.result, 5);
													}
												}, this);
											});
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
		this.localstore = new Ext.data.GroupingStore({
			storeId: 'localstore',
			proxy: new Ext.data.DirectProxy({
						directFn: TYPO3.EM.ExtDirect.getExtensionList
					}),
			autoLoad: false,
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
							{name:'nodePath'},
							{name:'reviewstate'},
							{name:'required'},
							{name:'doubleInstall'},
							{name:'doubleInstallShort'},
							{name:'updateModule'},
							{name:'doNotLoadInFE'},
							{name:'depends'},
							{name:'conflicts'},
							{name:'suggests'}
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

				},
				datachanged: function(store) {
					Ext.getCmp('displayExtensionLabel').setText(TYPO3.lang.extensions + ' ' + store.data.length);
					var hasFilters = false;
					TYPO3.EM.Filters.filters.each(function (filter) {
						if (filter.active) {
							hasFilters = true;
						}
					});
					if (hasFilters) {
						this.doClearFilters.show();
						this.doClearFiltersSeperator.show();
					} else {
						this.doClearFilters.hide();
						this.doClearFiltersSeperator.hide();
					}
					if (!TYPO3.settings.EM.hide_obsolete && !TYPO3.settings.EM.hide_shy && !TYPO3.settings.EM.display_installed) {
						this.filterMenuButton.removeClass('bold');
					} else {
						this.filterMenuButton.addClass('bold');
					}
				},
				load: function(store) {
					TYPO3.EM.App.refreshLocalList = false;
					if (store.showAction) {
						this.showExtension.defer(500, this);
					}
				},

				scope: this
			},
			validateRecord: function(record) {
				var control = Ext.getCmp('localSearchField');
				if (control) {
					var filtertext = control.getRawValue();
					if (filtertext) {
						//filter by search string
						var re = new RegExp(Ext.escapeRe(filtertext), 'gi');
						var isMatched = record.data.extkey.match(re) || record.data.title.match(re);
						if (!isMatched) {
							return false;
						}
					}
				}
				if (TYPO3.settings.EM.hide_obsolete == 1 && record.data.state === 'obsolete') {
					return false;
				}
				if (TYPO3.settings.EM.hide_shy == 1 && record.data.shy == 1) {
					return false;
				}
				if (TYPO3.settings.EM.display_installed == 1 && record.data.installed == 0) {
					return false;
				}

				return true;
			},

			highlightSearch: function(value) {
				var control = Ext.getCmp('localSearchField');
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

		var searchField = new Ext.ux.form.SearchField({
			store: this.localstore,
			filterFunction: this.filterRecords,
			id: 'localSearchField',
			width: 200
		});

		var cols = [
			TYPO3.settings.EM.inlineToWindow == 1 ? TYPO3.EM.GridColumns.DummyColumn : this.rowExpander,
			TYPO3.EM.GridColumns.InstallExtension,
			TYPO3.EM.GridColumns.ExtensionTitle,
			TYPO3.EM.GridColumns.ExtensionKey,
			TYPO3.EM.GridColumns.ExtensionCategory,
			TYPO3.EM.GridColumns.ExtensionAuthor,
			TYPO3.EM.GridColumns.ExtensionType,
			TYPO3.EM.GridColumns.ExtensionState
		];

		var cm = new Ext.grid.ColumnModel({
			columns: cols,
			defaults: {
				sortable: true
			}
		});

		var sm = Ext.emptyFn();
		if (TYPO3.settings.EM.inlineToWindow == 1) {
			sm = new Ext.grid.RowSelectionModel({
				singleSelect: true
			});
		}

		Ext.apply(this, {
			itemId: 'em-localLocalExtensionlist',
			title: TYPO3.lang.localExtensionList,
			loadMask: {msg: TYPO3.lang.action_loading_extlist},
			layout: 'fit',
			store: this.localstore,
			cm: cm,
			sm: sm,
			plugins: TYPO3.settings.EM.inlineToWindow == 1 ? [TYPO3.EM.Filters] : [this.rowExpander, TYPO3.EM.Filters],
			view : new Ext.grid.GroupingView({
						forceFit : true,
						groupTextTpl : '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "' + TYPO3.lang.msg_items + '" : "' + TYPO3.lang.msg_item + '"]})',
						enableRowBody: true,
						showPreview: true,
						getRowClass: this.applyRowClass,
						iconCls: 'icon-grid',
						hideGroupedColumn: true
					}),

			tbar: [
				' ',
				{
					text: TYPO3.lang.cmd_filter,
					tooltip: TYPO3.lang.help_localFilter,
					tooltipType : 'qtip',
					scale: 'small',
					ref: '../filterMenuButton',
					iconAlign: 'right',
					menu : {
						items: [
							{
								checked: TYPO3.settings.EM.display_installed ? true : false,
								text: TYPO3.lang.display_installedOnly,
								handler: function(item, event) {
									TYPO3.settings.EM.display_installed = item.checked ? 0 : 1;
									TYPO3.EM.ExtDirect.saveSetting('display_installed', TYPO3.settings.EM.display_installed);
									this.filterRecords();
								},
								scope: this
							},
							{
								checked: TYPO3.settings.EM.hide_shy ? true : false,
								text: TYPO3.lang.hide_shy,
								handler: function(item, event) {
									TYPO3.settings.EM.hide_shy = item.checked ? 0 : 1;
									TYPO3.EM.ExtDirect.saveSetting('hide_shy', TYPO3.settings.EM.hide_shy);
									this.filterRecords();
								},
								scope: this
							},
							{
								checked: TYPO3.settings.EM.hide_obsolete ? true : false,
								text: TYPO3.lang.hide_obsolete,
								handler: function(item, event) {
									TYPO3.settings.EM.hide_obsolete = item.checked ? 0 : 1;
									TYPO3.EM.ExtDirect.saveSetting('hide_obsolete', TYPO3.settings.EM.hide_obsolete);
									this.filterRecords();
								} ,
								scope: this
							}
						]
					}
				},
				searchField,
				{
					xtype: 'tbseparator',
					ref: '../doClearFiltersSeperator',
					hidden: true
				}, {
					text: TYPO3.lang.cmd_ClearAllFilters,
					ref: '../doClearFilters',
					handler: function() {
						TYPO3.EM.Filters.clearFilters();
					},
					scope: this,
					hidden: true
				},
				'-',
				{
					iconCls: 't3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-upload',
					tooltip: TYPO3.lang.upload_ext_directly,
					ref: '../uploadButton',
					handler: function() {
						TYPO3.EM.Tools.uploadExtension();
					},
					scope: this
				},
				'->',
				{
					xtype: 'tbtext',
					text: TYPO3.lang.action_loading_extlist,
					id: 'displayExtensionLabel',
					style: {fontWeight: 'bold'}
				},
				' '
			]
		});

		TYPO3.EM.LocalList.superclass.initComponent.apply(this, arguments);
	},


	showExtension: function() {
		var row = this.store.find('extkey', this.store.showAction);
		if (row) {
			if (TYPO3.settings.EM.inlineToWindow == 1) {

			} else {
				this.rowExpander.expandRow(row);
			}
			this.getSelectionModel().selectRow(row);
			this.getView().focusRow(row);
		}
		this.store.showAction = false;
	},

	onRender: function() {
		TYPO3.EM.LocalList.superclass.onRender.apply(this, arguments);
		if (this.localstore.getCount() == 0) {
			this.localstore.load();
		}

		this.on('rowdblclick', function(grid, rowIndex, event) {
			if (TYPO3.settings.EM.inlineToWindow == 1) {
				this.showExtInfoInWindow(rowIndex);
			}
		});

		this.on('cellclick', function(grid, rowIndex, columnIndex, event) {
			if (TYPO3.settings.EM.inlineToWindow == 1 && columnIndex == 2) {
				this.showExtInfoInWindow(rowIndex);
			}
		});

	},

	afterRender: function() {
		TYPO3.EM.LocalList.superclass.afterRender.apply(this, arguments);
	},

	showExtInfoInWindow: function(index) {
		var record = this.store.getAt(index);
		var id = 'window-extinfo-' + record.data.extkey;
		var tabs = this.rowExpander.createExpandingRowPanelItems(record, index);

		Ext.apply(tabs, {
					height: 'auto'
				});

		if (Ext.WindowMgr.get(id)) {
			Ext.WindowMgr.bringToFront(id);
		} else {
			new Ext.Window({
				title: TYPO3.EM.Tools.renderExtensionTitle(record),
				width: 720,
				height: 400,
				layout: 'fit',
				items : tabs,
				id: id
			}).show();
		}
	},

	filterRecords: function() {
		Ext.StoreMgr.get('localstore').filterBy(TYPO3.EM.Filters.getRecordFilter());
	}

});

Ext.reg('TYPO3.EM.LocalList', TYPO3.EM.LocalList);
