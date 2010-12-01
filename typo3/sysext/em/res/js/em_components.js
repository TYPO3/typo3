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
	filters: [{
		type: 'string',
		dataIndex: 'author'
		}, {
		type: 'string',
		dataIndex: 'category'
		}, {
		type: 'list',
		dataIndex: 'state',
		options: ['alpha', 'beta', 'stable', 'experimental', 'test', 'obsolete', 'excludeFromUpdates'],
		phpMode: true
		}, {
		type: 'boolean',
		dataIndex: 'installed'
	}]
});

TYPO3.EM.GridColumns.InstallExtension = {
	header: '',
	width: 30,
	sortable: false,
	fixed: true,
	groupable: false,
	hideable: false,
	menuDisabled: true,
	xtype: 'actioncolumn',
	items: [
		{
			getClass: function(value, meta, record) {
				if (record.get('installed') == 0) {
					this.items[0].tooltip = TYPO3.lang.menu_install_extensions;
					return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-install';
				} else {
					this.items[0].tooltip = TYPO3.lang.ext_details_remove_ext;
					return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-uninstall';
				}
			},
			handler: function(grid, rowIndex, colIndex) {
				var record = grid.store.getAt(rowIndex).data;
				var action = record.installed ? TYPO3.lang.ext_details_remove_ext : TYPO3.lang.menu_install_extensions;
				var link = TYPO3.settings.EM.scriptLink
						+ '&nodoc=1&view=installed_list&CMD[showExt]=' + record.extkey
						+ '&CMD[' + (record.installed ? 'remove' : 'load') + ']=1&CMD[clrCmd]=1&SET[singleDetails]=info';

				var w = new TYPO3.EM.InstallWindow({
				 	title: action + ': ' + record.title + ' (' + record.extkey + ') version ' + record.version
				}).show(true, function(){
					Ext.getCmp('emInstallIframeWindow').setUrl(link);
				});
			}
		}
	]
}

TYPO3.EM.GridColumns.ImportExtension = {
	header: '',
	width: 30,
	sortable: false,
	fixed: true,
	groupable: false,
	hideable: false,
	menuDisabled: true,
	xtype: 'actioncolumn',
	items: [
		{
			getClass: function(value, meta, record) {
				if (record.get('installed') == 0) {
					this.items[0].tooltip = TYPO3.lang.menu_install_extensions;
					return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-install';
				} else {
					this.items[0].tooltip = TYPO3.lang.ext_details_remove_ext;
					return 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-import';
				}
			},
			handler: function(grid, rowIndex, colIndex) {
				var record = grid.store.getAt(rowIndex).data;
				var action = record.installed ? TYPO3.lang.ext_details_remove_ext : TYPO3.lang.menu_install_extensions;
				//http://intro.local.com/typo3/mod.php?M=tools_em&ter_connect=1&ter_search=templavoila&CMD[importExt]=ics_templavoila_migration_tool&CMD[extVersion]=1.0.3&CMD[loc]=L
				var link = TYPO3.settings.EM.scriptLink
						+ '&nodoc=1&view=installed_list&ter_connect=1&CMD[importExt]=' + record.extkey  + '&CMD[extVersion]=' + record.version + '&CMD[loc]=L'


				var w = new TYPO3.EM.InstallWindow({
				 	title: action + ': ' + record.title + ' (' + record.extkey + ') version ' + record.version
				}).show(true, function(){
					Ext.getCmp('emInstallIframeWindow').setUrl(link);
				});
			}
		}
	]
}

TYPO3.EM.GridColumns.ExtensionTitle = {
	header: TYPO3.lang.tab_mod_name,
	width: 150,
	sortable: true,
	dataIndex: 'title',
	renderer:function(value, metaData, record, rowIndex, colIndex, store) {
		if (value == '') {
			value = '[no title]';
		}
		if (record.data.description) {
			metaData.attr = 'ext:qtip="' + record.data.description + '"';
		}
		return record.data.icon + ' ' + value + ' (v' + record.data.version + ')';
	}
}

TYPO3.EM.GridColumns.ExtensionKey = {
	header: TYPO3.lang.tab_mod_key,
	width: 80,
	sortable: true,
	dataIndex: 'extkey'
}

TYPO3.EM.GridColumns.ExtensionCategory = {
	header: TYPO3.lang.list_order_category,
	width: 70,
	sortable: true,
	dataIndex: 'category',
	filterable: true,
	hidden: true
}

TYPO3.EM.GridColumns.ExtensionCategoryRemote = {
	header: TYPO3.lang.list_order_category,
	width: 70,
	sortable: true,
	dataIndex: 'category',
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		var cats = ['be','module','fe','plugin','misc','services','templates','example','doc'];
		return cats[value];
	}
}

TYPO3.EM.GridColumns.ExtensionAuthor = {
	header: TYPO3.lang.list_order_author,
	width: 120,
	sortable: true,
	hidden: true,
	dataIndex:'author',
	renderer: function(value, metaData, record, rowIndex, colIndex, store) {
		metaData.attr='ext:qtip="'+value+'"';
		var t = Ext.util.Format.ellipsis(value, 20);
		if (record.data.author_email) {
			return '<a href="mailto:' + record.data.author_email + '">' + t + '</a>';
		} else {
			return t;
		}
	}
}

TYPO3.EM.GridColumns.ExtensionType = {
	header: TYPO3.lang.list_order_type,
	width:50,
	sortable:true,
	dataIndex:'type',
	hidden: true
}

TYPO3.EM.GridColumns.ExtensionState = {
	header: TYPO3.lang.list_order_state,
	align: 'center',
	width: 100,
	sortable: true,
	resizable: false,
	fixed: true,
	dataIndex:'state',
	renderer: function(value, metaData, record, rowIndex, colIndex, store){
		metaData.css += 'state-' + value + ' ';
		return value;
	},
	filterable: true
}

TYPO3.EM.GridColumns.ExtensionDownloads = {
	header: TYPO3.lang.list_order_downloads,
	width: 40,
	sortable: true,
	dataIndex:'alldownloadcounter'
}

TYPO3.EM.GridColumns.ExtensionVersion = {
	header: TYPO3.lang.extInfoArray_version,
	width:40,
	sortable: true,
	dataIndex:'version'
}


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
	//iconCls: 'x-btn-drive',
	id: 'em-local-extensions',
	listeners: {
		activate: function(panel) {
			TYPO3.EM.ExtDirect.saveSetting('mainTab', 0);
			var store = Ext.StoreMgr.get('localstore');
			if (!store.getCount()) {
				store.load();
			}

		},
		scope: this
	}
};
TYPO3.EM.RepositoryListTab = {
	title : TYPO3.lang.remoteRepository,
	//iconCls: 'x-btn-server',
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
	id: 'em-remote-extensions',
	listeners: {
		activate: function(panel) {
			TYPO3.EM.ExtDirect.saveSetting('mainTab', 1);
			var store = Ext.StoreMgr.get('repositoryliststore');
			if (!store.getCount()) {
				store.load();
			}
		},
		scope: this
	}
};
TYPO3.EM.LanguageTab = {
	title: TYPO3.lang.menu_translation_handling,
	xtype: 'extlanguages',
	id: 'em-translations',
	//iconCls: 'x-btn-flag',
	listeners: {
		activate: function(panel) {
			TYPO3.EM.ExtDirect.saveSetting('mainTab', 2);
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
	xtype: 'extsettings',
	//iconCls: 'x-btn-settings',
	listeners: {
		activate: function(panel) {
			TYPO3.EM.ExtDirect.saveSetting('mainTab', 3);
		},
		scope: this
	}
};

TYPO3.EM.UserTab = {
	title: TYPO3.lang.myExtensions,
	xtype: 'TYPO3.EM.UserTools',
	//disabled: !TYPO3.settings.EM.hasCredentials,
	//iconCls: 'x-btn-tools',
	listeners: {
		activate: function(panel) {
			var store = Ext.StoreMgr.get('em-userext');
			if (!TYPO3.settings.EM.hasCredentials) {
				TYPO3.Flashmessage.display(TYPO3.Severity.error,'Settings', 'No user+password specified. Please enter your credentials in "Settings" tab! ', 15);
				store.removeAll();
				Ext.getCmp('extvalidformbutton').disable();
			} else {
				TYPO3.EM.ExtDirect.saveSetting('mainTab', 4);
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
	height: 40,
	id: 'LanguagesActionPanel',
	layoutConfig: {
		align: 'stretch'
	},
	defaults: {
		border:false,
		flex: 1,
		margins: '10 10 10 10'
	},
	items: [{
		xtype: 'button',
		text: TYPO3.lang.translation_check_status_button,
		id: 'lang-checkbutton',
		disabled: true
	}, {
		xtype: 'button',
		text: TYPO3.lang.translation_update_button,
		id: 'lang-updatebutton',
		disabled: true
	}]
};


TYPO3.EM.InstallWindow = Ext.extend(Ext.Window, {
	width: 800,
	height: 600,
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
						waitMsg : 'Sending data...',
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
