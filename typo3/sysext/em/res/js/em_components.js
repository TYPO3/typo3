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
Ext.ns('TYPO3.EM', 'TYPO3.EM.GridColumns', 'TYPO3.EM.ExtDirect', 'TYPO3.EMSOAP.ExtDirect');

///////////////////////////////////////////////////////
// Grid
///////////////////////////////////////////////////////

TYPO3.EM.Filters = new Ext.ux.grid.GridFilters({
	encode: true,
	local: true,
	menuFilterText: TYPO3.lang.cmd_filter,
	filters: [
		{
			type: 'string',
			dataIndex: 'title'
			}, {
			type: 'string',
			dataIndex: 'extkey'
			}, {
			type: 'string',
			dataIndex: 'author'
			}, {
			type: 'string',
			dataIndex: 'category'
			}, {
			type: 'list',
			dataIndex: 'state',
			options: [
				TYPO3.lang.state_alpha,
				TYPO3.lang.state_beta,
				TYPO3.lang.state_stable,
				TYPO3.lang.state_experimental,
				TYPO3.lang.state_test,
				TYPO3.lang.state_obsolete,
				TYPO3.lang.state_exclude_from_updates
			],
			phpMode: true
			}, {
			type: 'boolean',
			dataIndex: 'installed'
		}, {
			type: 'list',
			dataIndex: 'type',
			options: [TYPO3.lang.type_system, TYPO3.lang.type_global, TYPO3.lang.type_local],
			phpMode: true
		}
	],
	getRecordFilter: function(){
		var f = [];
		this.filters.each(function(filter){
			if(filter.active) f.push(filter);
		});
			// add custom filter
		f.push(this.grid.store);
		var len = f.length, me = this;
		return function(record){
			for(var i=0; i<len; i++)
				if(!f[i].validateRecord(record))
					return false;

			return true;
		};
	}
});

TYPO3.EM.RemoteFilters = new Ext.ux.grid.GridFilters({
	encode: true,
	local: false,
	filters: [{
		type: 'string',
		dataIndex: 'title'
		}, {
		type: 'string',
		dataIndex: 'extkey'
		}, {
		type: 'string',
		dataIndex: 'authorname'
		}, {
		type: 'list',
		dataIndex: 'statevalue',
		options: [
			[0, TYPO3.lang.state_alpha],
			[1, TYPO3.lang.state_beta],
			[2, TYPO3.lang.state_stable],
			[3, TYPO3.lang.state_experimental],
			[4, TYPO3.lang.state_test],
			[5, TYPO3.lang.state_obsolete],
			[6, TYPO3.lang.state_exclude_from_updates],
			[999, TYPO3.lang.translation_n_a]
		],
		phpMode: true
		}, {
		type: 'list',
		dataIndex: 'category',
		options: [
			[0, TYPO3.lang.category_BE],
			[1, TYPO3.lang.category_BE_modules],
			[2, TYPO3.lang.category_FE],
			[3, TYPO3.lang.category_FE_plugins],
			[4, TYPO3.lang.category_miscellanous],
			[5, TYPO3.lang.category_services],
			[6, TYPO3.lang.category_templates],
			[8, TYPO3.lang.category_documentation],
			[9, TYPO3.lang.category_examples]
		],
		phpMode: true
		}, {
		type: 'boolean',
		dataIndex: 'installed'
	}]
});

TYPO3.EM.GridColumns.DummyColumn = {
	header: '',
	width: 20,
	sortable: false,
	hideable: false,
	fixed: true,
	groupable: false,
	menuDisabled: true
};

TYPO3.EM.GridColumns.InstallExtension = {
	header: '',
	width: 45,
	sortable: false,
	hideable: false,
	fixed: true,
	groupable: false,
	menuDisabled: true,
	xtype: 'actioncolumn',
	items: [
		{
			getClass: function(value, meta, record) {
				if (record.get('installed') == 0) {
					this.items[0].tooltip = TYPO3.lang.menu_install_extensions;
					return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-install';
				} else {
					if (record.get('required')) {
						this.items[0].tooltip = TYPO3.lang.ext_details_always_loaded;
						return 't3-icon t3-icon-extensions t3-icon-extensions-em t3-icon-em-extension-required';
					} else {
						this.items[0].tooltip = TYPO3.lang.ext_details_remove_ext;
						return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-uninstall';
					}
				}
			},
			handler: function(grid, rowIndex, colIndex) {
				var record = grid.store.getAt(rowIndex).data;
				if (!record.required) {
					var action = record.installed ? TYPO3.lang.ext_details_remove_ext : TYPO3.lang.menu_install_extensions;
					var link = TYPO3.settings.EM.scriptLink
							+ '&nodoc=1&view=info&CMD[silentMode]=1&CMD[standAlone]=1&CMD[showExt]=' + record.extkey
							+ '&CMD[' + (record.installed ? 'remove' : 'load') + ']=1&CMD[clrCmd]=1&SET[singleDetails]=info';

					TYPO3.EM.ImportWindow = new TYPO3.EM.InstallWindow({
						title: action + ': ' + record.title + ' (' + record.extkey + ') version ' + record.version,
						record: record,
						installAction: 'install',
						url: link,
						listeners: {
							close: function() {
								grid.store.reload();
								TYPO3.EM.Tools.refreshMenu(record, 'install');
							}
						}
					}).show(true, function(){
						Ext.getCmp('emInstallIframeWindow').setUrl(link);
					});
				}
			}
		},
		{
			hidden: (TYPO3.settings.EM.inlineToWindow == 0),
			getClass: function(value, meta, record) {
				meta.css += ' paddingActionIcon';
				if (TYPO3.settings.EM.inlineToWindow == 1) {
					return 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-info';
				}
				return '';
			},
			handler: function(grid, rowIndex, colIndex) {
				grid.showExtInfoInWindow(rowIndex);
			}
		}
	]
};

TYPO3.EM.GridColumns.ImportExtension = {
	header: '',
	width: 45,
	sortable: false,
	fixed: true,
	groupable: false,
	menuDisabled: true,
	xtype: 'actioncolumn',
	items: [
		{
			getClass: function(value, meta, record) {
				if (record.data.exists) {
					if (record.data.versionislower) {
						this.items[0].tooltip = String.format(TYPO3.lang.menu_update_extension, record.data.existingVersion, record.data.version);
						return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-update';
					} else {
						return '';
					}
				} else {
					this.items[0].tooltip = TYPO3.lang.menu_import_extensions;
					return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-import';
				}
			},
			handler: function(grid, rowIndex, colIndex) {
				var record = grid.store.getAt(rowIndex).data;
				var action = TYPO3.lang.menu_import_extensions;
				if (record.exists && record.versionislower) {
					action = TYPO3.lang.menu_update_extensions;
				}
				var link = TYPO3.settings.EM.scriptLink
						+ '&nodoc=1&view=info&CMD[silentMode]=1&CMD[standAlone]=1&ter_connect=1&CMD[importExt]='
						+ record.extkey  + '&CMD[extVersion]=' + record.version + '&CMD[loc]=L'


				TYPO3.EM.ImportWindow = new TYPO3.EM.InstallWindow({
				 	title: action + ': ' + record.title + ' (' + record.extkey + ') version ' + record.version,
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
		},
		{
			getClass: function(value, meta, record) {
				meta.css += ' paddingActionIcon';
				if (TYPO3.settings.EM.inlineToWindow == 1) {
					return 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-info';
				}
				return '';
			},
			handler: function(grid, rowIndex, colIndex) {
				grid.showExtInfoInWindow(rowIndex);
			}
		}
	]
};


TYPO3.EM.GridColumns.ExtensionTitle = {
	header: TYPO3.lang.tab_mod_name,
	width: 150,
	sortable: true,
	dataIndex: 'title',
	filterable: true,
	hideable: true,
	renderer:function(value, metaData, record, rowIndex, colIndex, store) {
		metaData.css += 'action-title-cell';
		var description = record.data.description;
		if (value == '') {
			value = '[no title]';
		}
		if (record.data.reviewstate < 0) {
			metaData.css += ' insecureExtension';
			description += '<br><br><strong>' + TYPO3.lang.insecureExtension + '</strong>';
		}
		if (description) {
			metaData.attr = 'ext:qtip="' + Ext.util.Format.htmlEncode(description) + '"';
		}
		value = store.highlightSearch(value);
		return record.data.icon + ' ' + value + ' (v' + record.data.version + ')';
	}
};

TYPO3.EM.GridColumns.ExtensionKey = {
	header: TYPO3.lang.tab_mod_key,
	width: 80,
	sortable: true,
	filterable: true,
	hideable: true,
	dataIndex: 'extkey',
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		return store.highlightSearch(value);
	}
};

TYPO3.EM.GridColumns.ExtensionCategory = {
	header: TYPO3.lang.list_order_category,
	width: 70,
	sortable: true,
	dataIndex: 'category',
	filterable: true,
	hideable: true,
	hidden: true
};

TYPO3.EM.GridColumns.ExtensionCategoryRemote = {
	header: TYPO3.lang.list_order_category,
	width: 70,
	sortable: true,
	hideable: true,
	dataIndex: 'category',
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		return TYPO3.EM.App.getCategoryLabel(value);
	}
};

TYPO3.EM.GridColumns.ExtensionAuthor = {
	header: TYPO3.lang.list_order_author,
	width: 120,
	sortable: true,
	hidden: true,
	hideable: true,
	dataIndex:'author',
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		metaData.attr = 'ext:qtip="' + Ext.util.Format.htmlEncode(value) + '"';
		var t = Ext.util.Format.ellipsis(value, 20);
		if (record.data.author_email) {
			return '<a class="email" href="mailto:' + record.data.author_email + '">' + t + '</a>';
		} else {
			return t;
		}
	}
};

TYPO3.EM.GridColumns.ExtensionRemoteAuthor = {
	header: TYPO3.lang.list_order_author,
	width: 120,
	sortable: true,
	hidden: true,
	hideable: true,
	dataIndex:'authorname',
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		metaData.attr = 'ext:qtip="' + Ext.util.Format.htmlEncode(value) + '"';
		var t = Ext.util.Format.ellipsis(value, 20);
		if (record.data.authoremail) {
			return '<a class="email" href="mailto:' + record.data.authoremail + '">' + t + '</a>';
		} else {
			return t;
		}
	}
};

TYPO3.EM.GridColumns.ExtensionType = {
	header: TYPO3.lang.list_order_type,
	width:50,
	sortable:true,
	dataIndex:'type',
	hideable: true,
	filterable: true,
	hidden: true,
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		if (record.data.doubleInstallShort && record.data.doubleInstallShort.length > 1) {
			var temp = record.data.doubleInstall.split('/');
			value = '<strong>' + record.data.doubleInstall + '</strong>';
			metaData.attr = 'ext:qtip="' + String.format(TYPO3.lang.double_inclusion_js, temp[1], temp[0]) + '"';
		}
		return value;
	}
};

TYPO3.EM.GridColumns.ExtensionState = {
	header: TYPO3.lang.list_order_state,
	align: 'center',
	width: 100,
	sortable: true,
	resizable: false,
	fixed: true,
	hideable: true,
	filterable: true,
	dataIndex:'state',
	renderer: function(value, metaData, record, rowIndex, colIndex, store){
		metaData.css += record.data.stateCls + ' ';
		return value;
	}
};

TYPO3.EM.GridColumns.ExtensionStateValue = {
	header: TYPO3.lang.list_order_state,
	align: 'center',
	width: 100,
	sortable: true,
	resizable: false,
	fixed: true,
	hideable: true,
	dataIndex:'statevalue',
	renderer: function(value, metaData, record, rowIndex, colIndex, store){
		metaData.css += record.data.stateCls + ' ';
		var label = TYPO3.lang.translation_n_a;
		switch (parseInt(value, 10)) {
			case 0:
				label = TYPO3.lang.state_alpha;
			break;
			case 1:
				label = TYPO3.lang.state_beta;
			break;
			case 2:
				label = TYPO3.lang.state_stable;
			break;
			case 3:
				label = TYPO3.lang.state_experimental;
			break;
			case 4:
				label = TYPO3.lang.state_test;
			break;
			case 5:
				label = TYPO3.lang.state_obsolete;
			break;
			case 6:
				label = TYPO3.lang.state_exclude_from_updates;
			break;
		}
		return label;
	},
	filterable: true
};

TYPO3.EM.GridColumns.ExtensionDownloads = {
	header: TYPO3.lang.list_order_downloads,
	width: 40,
	sortable: true,
	hideable: true,
	dataIndex:'alldownloadcounter',
	renderer: function(value, metaData, record, rowIndex, colIndex, store){
		return record.data.alldownloadcounter + ' / ' + record.data.downloadcounter;
	},
	filterable: true
};

TYPO3.EM.GridColumns.ExtensionVersion = {
	header: TYPO3.lang.extInfoArray_version,
	width:40,
	sortable: true,
	hideable: true,
	hidden: true,
	dataIndex:'version'
};

TYPO3.EM.GridColumns.Relevance = {
	header: TYPO3.lang.extInfoArray_relevance,
	width:40,
	sortable: true,
	hideable: true,
	dataIndex:'relevance'
};


///////////////////////////////////////////////////////
// Stores
///////////////////////////////////////////////////////

TYPO3.EM.LocationStore = new Ext.data.JsonStore({
	fields : ['name', 'value'],
	data   : [
		{name : 'Local (../typo3conf/ext/)',   value: 'L'}
	]
});


///////////////////////////////////////////////////////
// Panels + Tabs
///////////////////////////////////////////////////////

TYPO3.EM.LocalListTab = {
	title : TYPO3.lang.localExtensions,
	xtype: 'TYPO3.EM.LocalList',
	id: 'em-local-extensions',
	listeners: {
		activate: function(panel) {
			if (TYPO3.EM.App.refreshLocalList) {
				Ext.StoreMgr.get('localstore').load();
			}
		},
		scope: this
	}
};

TYPO3.EM.RepositoryListTab = {
	title : TYPO3.lang.remoteRepository,
	layout: 'fit',
	items: [/*{
		xtype: 'repository',
		region: 'north'
	},*/{
		xtype: 'remoteextlist',
		flex: 1,
		region: 'center',
		margins: '0 0 0 0'
	}],
	id: 'em-remote-extensions'
};

TYPO3.EM.LanguageTab = {
	title: TYPO3.lang.menu_language_packges,
	xtype: 'extlanguages',
	id: 'em-translations',
	listeners: {
		activate: function(panel) {
			var store = Ext.StoreMgr.get('em-language-store');
			if (!store.getCount()) {
				store.load();
			}
		},
		afterrender: function(panel) {

		},
		scope: this
	}
};

TYPO3.EM.SettingsTab = {
	title: TYPO3.lang.menu_settings,
	xtype: 'extsettings'
};

TYPO3.EM.UserTab = {
	title: TYPO3.lang.myExtensions,
	xtype: 'TYPO3.EM.UserTools',
	disabled: !TYPO3.settings.EM.hasCredentials,
	listeners: {
		activate: function(panel) {
			var store = Ext.StoreMgr.get('em-userext');
			if (!TYPO3.settings.EM.hasCredentials) {
				TYPO3.Flashmessage.display(TYPO3.Severity.error,'Settings', 'No user+password specified. Please enter your credentials in "Settings" tab! ', 15);
				store.removeAll();
				Ext.getCmp('extvalidformbutton').disable();
			} else {
				store.load();
			}
		},
		scope: this
	}
};


TYPO3.EM.UploadLocationCombo = new Ext.form.ComboBox({
	mode: 'local',
	value: 'L',
	triggerAction: 'all',
	forceSelection: true,
	editable: false,
	name: 'loc',
	hiddenName:     'loc',
	displayField:   'name',
	valueField:     'value',
	store: TYPO3.EM.LocationStore,
	width: 250,
	fieldLabel: '...to location'
});

TYPO3.EM.RepositoryCombo = new Ext.form.ComboBox({
	id: 'repCombo',
	mode: 'local',
	triggerAction: 'all',
	forceSelection: true,
	editable: false,
	name: 'selectedRepository',
	hiddenName: 'selectedRepository',
	displayField: 'title',
	valueField: 'uid',
	store: null,
	width: 250
});

TYPO3.EM.LanguagesActionPanel = {
	xtype: 'container',
	layout: 'hbox',
	height: 30,
	id: 'LanguagesActionPanel',
	layoutConfig: {
		align: 'middle'
	},
	defaults: {
		border:false,
		flex: 1
	},
	items: [{
		xtype: 'button',
		text: TYPO3.lang.translation_check_status_button,
		id: 'lang-checkbutton',
		margins: '0 10 10 0'
	}, {
		xtype: 'button',
		text: TYPO3.lang.translation_update_button,
		id: 'lang-updatebutton',
		margins: '0 0 10 10'
	}]
};

TYPO3.EM.LanguagesProgressBar = new Ext.ProgressBar ({
	id:  'langpb',
	cls: 'left-align',
	style: 'margin: 0 0 20px 0',
	animate: true
});

TYPO3.EM.LanguagesProgressPanel = {
	xtype: 'container',
	layout: 'hbox',
	height: 40,
	id: 'LanguagesProgressPanel',
	hidden: true,
	layoutConfig: {
		align: 'middle'
	},
	defaults: {
		border: false,
		flex: 1,
		height: 20
	},
	items: [
		TYPO3.EM.LanguagesProgressBar,
		{
			xtype: 'button',
			text: 'cancel', //TYPO3.lang.cancel,
			id: 'lang-cancelbutton',
			margins: '0 0 10 10',
			width: 80
		}
	]
};

TYPO3.EM.InstallWindow = Ext.extend(Ext.Window, {
	width: 500,
	height: 400,
	closable: true,
	resizable: true,
	plain: true,
	border: false,
	modal: true,
	draggable: true,
	layout: 'anchor',
	constructor: function(config) {
		config = config || {};
		Ext.apply(this, config, {
			items: [{
				xtype: 'iframePanel',
				anchor: '100% 100%',
				border: false,
				id: 'emInstallIframeWindow'
			}]
		});
		TYPO3.EM.InstallWindow.superclass.constructor.call(this, config);
	}
});

TYPO3.EM.ImportWindow = Ext.extend(Ext.Window, {
	width: 500,
	height: 400,
	closable: true,
	resizable: true,
	plain: true,
	border: false,
	modal: true,
	draggable: true,
	layout: 'anchor',
	constructor: function(config) {
		config = config || {};
		Ext.apply(this, config, {
			items: [{
				xtype: 'iframePanel',
				anchor: '100% 100%',
				border: false,
				id: 'emImportIframeWindow'
			}]
		});
		TYPO3.EM.InstallWindow.superclass.constructor.call(this, config);
	}
});

TYPO3.EM.TerUpload = Ext.extend(Ext.form.FormPanel, {
	border:false,
	recordData: null,

	initComponent:function() {



		Ext.apply(this, {
			itemId: 'extUploadForm',
			height: 340,
			defaultType: 'textfield',

			defaults: {width: 350},
			items: [{
				fieldLabel: TYPO3.lang.repositoryUploadForm_username,
				name: 'fe_u'
			}, {
				fieldLabel: TYPO3.lang.repositoryUploadForm_password,
				inputType: 'password',
				name: 'fe_p'
			}, {
				fieldLabel: TYPO3.lang.repositoryUploadForm_changelog,
				xtype: 'textarea',
				height: 150,
				name: 'uploadcomment'
			}, {
				xtype: 'radiogroup',
				fieldLabel: TYPO3.lang.repositoryUploadForm_new_version,
				itemCls: 'x-check-group-alt',
				columns: 1,
				items: [
					{
						boxLabel: TYPO3.lang.repositoryUploadForm_new_bugfix.replace('%s', 'x.x.<strong><span class="typo3-red">x+1</span></strong>'),
						name: 'newversion',
						inputValue: 'new_dev',
						checked: true
					},
					{
						boxLabel: TYPO3.lang.repositoryUploadForm_new_sub_version.replace('%s', 'x.<strong><span class="typo3-red">x+1</span></strong>.0'),
						name: 'newversion',
						inputValue: 'new_sub'
					},
					{
						boxLabel: TYPO3.lang.repositoryUploadForm_new_main_version.replace('%s', '<strong><span class="typo3-red">x+1</span></strong>.0.0'),
						name: 'newversion',
						inputValue: 'new_main'
					}
				]
			}, {
				xtype: 'button',
				text: TYPO3.lang.repositoryUploadForm_upload,
				scope: this,
				handler: function() {
					this.form.submit({
						waitMsg : TYPO3.lang.action_sending_data,
						success: function(form, action) {
							TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.cmd_terupload, TYPO3.lang.msg_terupload, 5);
							form.reset();
						},
						failure: function(form, action) {
							if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
								TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error,
										TYPO3.lang.list_order_state + ':' + action.response.status + ': ' +
										action.response.statusText, 15);
							}
							if (action.failureType === Ext.form.Action.SERVER_INVALID){
								// server responded with success = false
								TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_invalid, action.result.errormsg, 5);
							}
						}
					});
				}
			}],
			listeners: {

				activate: function(panel) {


				}
			},
			scope: this
		});

		TYPO3.EM.TerUpload.superclass.initComponent.apply(this, arguments);
	},

	onRender: function() {


		TYPO3.EM.TerUpload.superclass.onRender.apply(this, arguments);

		Ext.apply(this.getForm(),{
			api: {
				load: TYPO3.EM.ExtDirect.loadUploadExtToTer,
				submit: TYPO3.EM.ExtDirect.uploadExtToTer
			},
			paramsAsHash: false

		});
		this.form.load();
	}


});

Ext.reg('terupload', TYPO3.EM.TerUpload);

TYPO3.EM.ExtensionUploadWindowInstance = null;
TYPO3.EM.ExtensionUploadWindow = Ext.extend(Ext.Window, {
	title: 'Upload extension file directly (.t3x)',
	modal: true,
	closable: true,
	closeAction: 'hide',
	plain: true,
	width: 400,
	height: 160,
	layout: 'fit',

	constructor: function(config) {
		config = config || {};
		Ext.apply(this, config, {
			items: [
				{
					xtype: 'form',
					itemId: 'uploadForm',
					fileUpload: true,
					api: {
						submit: TYPO3.EM.ExtDirect.uploadExtension
					},
					items: [
						{
							xtype: 'fileuploadfield',
							id: 'form-file',
							emptyText: TYPO3.lang.upload_selectExtension,
							fieldLabel: 'Extension',
							name: 'extupload-path',
							buttonText: '...',
							width: 250,
							validator: function(value) {
								if (value) {
									return value.split('.').pop().toLowerCase() === 't3x';
								}
								return false;
							}
						},
						TYPO3.EM.UploadLocationCombo,
						{
							xtype: 'checkbox',
							fieldLabel: TYPO3.lang.overwrite_ext,
							name: 'uploadOverwrite',
							labelWidth: 250
						},
						{
							xtype: 'button',
							text: TYPO3.lang.upload_ext_from,
							id: 'uploadSubmitButton',
							width: 420,
							scope: this,
							handler: function() {
								var form = this.getComponent('uploadForm').getForm();
								if (form.isValid()) {
									form.submit({
										waitMsg : TYPO3.lang.action_sending_data,
										success: function(form, action) {
											form.reset();
											TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.upload_ext, String.format(TYPO3.lang.msg_uploaded, action.result.extKey), 5);
											TYPO3.EM.ExtensionUploadWindowInstance.hide();
											TYPO3.EM.Tools.displayLocalExtension(action.result.extKey, true);
										},
										failure: function(form, action) {
											if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
												TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error,
														TYPO3.lang.msg_status + ': ' + action.response.status + ': ' +
																action.response.statusText, 15);
											}
											if (action.failureType === Ext.form.Action.SERVER_INVALID) {
												// server responded with success = false
												TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_invalid, action.result.error, 5);
											}
										}
									});
								}
							}
						}
					]
				}
			]
		});
		TYPO3.EM.ExtensionUploadWindow.superclass.constructor.call(this, config);
	}
});