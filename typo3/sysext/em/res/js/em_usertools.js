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
Ext.ns('TYPO3.EM', 'TYPO3.EMSOAP.ExtDirect');

TYPO3.EM.UserTools = Ext.extend(Ext.Panel, {
	border:false,

	initComponent:function() {
		var extkeysSm = new Ext.grid.CheckboxSelectionModel({
			singleSelect: true,
			header: '',
			listeners: {
				selectionchange: function(selectionModel) {
					var sel, record, transferkeybutton = Ext.getCmp('transferkeybutton'),
							deletekeybutton = Ext.getCmp('deletekeybutton');

					if (selectionModel.getCount() == 0) {
						transferkeybutton.disable();
						deletekeybutton.disable();
					} else {
						transferkeybutton.enable();
						record = selectionModel.getSelected();
						if (record.data.hasUploads === true) {
							deletekeybutton.disable();
						} else {
							deletekeybutton.enable();
						}
						}

				}
			}
		});

		var extkeysCm = new Ext.grid.ColumnModel([
			extkeysSm,
			{
				id: 'extensionkey',
				header: TYPO3.lang.tab_mod_key,
				width: .2,
				sortable: true,
				menuDisabled: true,
				dataIndex: 'extensionkey',
				hidable: false
			},
			{
				id: 'extensiontitle',
				header: TYPO3.lang.extInfoArray_title,
				width: .2,
				sortable: true,
				menuDisabled: true,
				dataIndex: 'title',
				hidable: false
			},
			{
				id: 'extensiondescription',
				header: TYPO3.lang.extInfoArray_description,
				width: .5,
				sortable: true,
				menuDisabled: true,
				dataIndex: 'description',
				hidable: false
			},
			{
				id: 'extensionuploads',
				header: TYPO3.lang.extInfoArray_uploads,
				width: .1,
				sortable: true,
				menuDisabled: true,
				dataIndex: 'uploads',
				hidable: false
			},
			{
				id: 'extensionhasuploads',
				hidden: true,
				dataIndex: 'hasUploads'
			}
		]);

		var userExtStore = new Ext.data.DirectStore({
			storeId	 : 'em-userext',
			autoLoad	: false,
			directFn	: TYPO3.EMSOAP.ExtDirect.getExtensions,
			paramsAsHash: false,
			root		: 'data',
			idProperty  : 'extensionkey',
			fields : [
				{name : 'extensionkey', type : 'string'},
				{name : 'title', type : 'string'},
				{name : 'description', type : 'string'},
				{name : 'uploads', type : 'int'},
				{name : 'hasUploads', type : 'bool'}
			],
			sortInfo:{
				field: 'extensionkey',
				direction: 'ASC'
			},
			storeFilter: function(record, id) {
				var filtertext = Ext.getCmp('myExtSearchField').getRawValue();
				if (filtertext) {
					//filter by search string
					var re = new RegExp(Ext.escapeRe(filtertext));
					var isMatched = record.data.extensionkey.match(re) || record.data.title.match(re) || record.data.description.match(re);
					if (!isMatched) {
						return false;
					}
				}
				return true;
			},
			listeners: {
				load: function(store, records) {
					Ext.getCmp('extvalidformbutton').enable();
				},
				exception: function(proxy, response, read, request, ExtDirectParams) {
					var error;

					if (!ExtDirectParams.result.raw) {
						error = TYPO3.lang.soap_error;
					} else {
						error = ExtDirectParams.result.raw.error;
					}
					TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_invalid, error, 15);
					Ext.getCmp('extvalidformbutton').disable();
				},
				scope: this
			}
		});

		var searchField = new Ext.ux.form.FilterField({
			store: userExtStore,
			id: 'myExtSearchField',
			width: 200
		});

		Ext.apply(this, {
			itemId: 'UserTools',
			layout: 'hbox',
			align: 'stretchmax',
			bodyStyle: 'padding-top: 10px;',
			border: false,
			items: [
				{
					width: 300,
					border: false,
					items: [
						{
							layout: 'form',
							xtype: 'form',
							labelWidth: 100,
							defaults: {margins: '10 0 0 0'},
							ref: '../validityCheckForm',
							items: [
								{
									xtype:'fieldset',
									title: TYPO3.lang.registerkeys_check_validity_extkey,
									defaults: {},
									defaultType: 'textfield',
									items :[
										{
											fieldLabel: TYPO3.lang.tab_mod_key,
											name: 'extkey',
											width: 170,
											allowBlank: false,
											validator: function(value) {
												if (value.length < 3) {
													return false;
												}
												return true;
											}
										},
										new Ext.Container({
											html: TYPO3.EM.Layouts.getExtensionRules(),
											xtype: 'displayfield',
											labelWidth: 1
										})
									],
									buttons: [
										{
											id: 'extvalidformbutton',
											text: TYPO3.lang.registerkeys_check_validity,
											handler: function() {
												this.validityCheckForm.getForm().submit({
													waitMsg : TYPO3.lang.registerkeys_check_validity,
													success: function(form, action) {
														TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.registerkeys_check_validity_extkey_isvalid, '', 5);

														this.registerForm.getForm().setValues({
															extkey: form.getValues().extkey,
															extkeydisplay: '<b>' + form.getValues().extkey + '</b>'
														});
														form.reset();
														this.validityCheckForm.hide()
														this.registerForm.show();
													},

													failure: function(form, action) {
														if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
															TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error,
																	TYPO3.lang.msg_status + ': ' + action.response.status + ': ' +
																			action.response.statusText, 15);
														}
														if (action.failureType === Ext.form.Action.SERVER_INVALID) {
															// server responded with success = false
															TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_invalid, action.result.message, 5);
														}
													},
													scope: this
												});
											},
											scope: this
										}
									]
								}
							]
						},
						{
							layout: 'form',
							xtype: 'form',
							hidden: true,
							labelWidth: 100,
							ref: '../registerForm',
							items: [
								{
									xtype:'fieldset',
									title: TYPO3.lang.registerkeys_registerkey,
									defaults: {},
									defaultType: 'textfield',
									items :[
										{
											xtype: 'hidden',
											name: 'extkey',
											value: ''
										},
										{
											html: '',
											xtype: 'displayfield',
											name: 'extkeydisplay',
											fieldLabel: TYPO3.lang.tab_mod_key
										},
										{
											fieldLabel: TYPO3.lang.extInfoArray_title,
											name: 'title',
											width: 170,
											allowBlank: false
										},
										{
											fieldLabel: TYPO3.lang.extInfoArray_description,
											xtype: 'textarea',
											name: 'description',
											width: 170,
											height: 80
										}
									],
									buttons: [
										{
											id: 'extregisterformbutton',
											text: TYPO3.lang.cmd_register,
											handler: function() {
												this.registerForm.getForm().submit({
													waitMsg : TYPO3.lang.registerkeys_register_extkey,
													success: function(form, action) {
														var msg = String.format(TYPO3.lang.registerkeys_register_extkey_success, this.registerForm.getForm().getValues().extkey);
														TYPO3.Flashmessage.display(TYPO3.Severity.information, msg, '', 5);
														form.reset();
														this.registerForm.hide();
														this.validityCheckForm.show();
														Ext.getCmp('em-userextgrid').store.load();
													},
													failure: function(form, action) {
														if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
															TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_error,
																TYPO3.lang.list_order_state + ':' + action.response.status + ': ' +
																action.response.statusText, 15);
														}
														if (action.failureType === Ext.form.Action.SERVER_INVALID) {
															// server responded with success = false
															TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.msg_invalid, action.result.message, 5);
														}
													},
													scope: this
												});
											},
											scope: this

										},
										{
											text: TYPO3.lang.registerkeys_cancel_register,
											handler: function() {
												this.registerForm.hide();
												this.validityCheckForm.show()
											},
											scope: this
										}
									]
								}
							]
						}
					]
				},
				{
					flex: 1,
					border: false,
					//layout: 'fit',
					items: [
						{
							xtype: 'fieldset',
							title: TYPO3.lang.myExtensions,
							items : [
								{
									xtype: 'grid',
									id: 'em-userextgrid',
									stripeRows: true,
									store: userExtStore,
									loadMask: {msg: TYPO3.lang.action_loading_extlist},
									cm: extkeysCm,
									sm: extkeysSm,
									viewConfig: {
										forceFit: true,
										autofill: true
									},
									stateId: 'userextgrid',
									stateful: true,
									tbar: [
										{
											xtype: 'tbtext',
											text: TYPO3.lang.cmd_filter + ':'
										},
										searchField,
										'->',
										{
											xtype: 'tbtext',
											text: TYPO3.lang.cmd_action + ':'
										},
										' ',
										{
											xtype: 'tbbutton',
											disabled: true,
											id: 'transferkeybutton',
											text: TYPO3.lang.cmd_transferkey,
											handler: this.transferkey,
											scope: this
										},
										' ',
										{
											xtype: 'tbbutton',
											disabled: true,
											id: 'deletekeybutton',
											text: TYPO3.lang.cmd_deletekey,
											handler: this.deletekey,
											scope: this
										}
									],
									height: 450
								}
							]
						}
					]
				}
			]
		});

		TYPO3.EM.UserTools.superclass.initComponent.apply(this, arguments);
	},

	onRender: function() {

		TYPO3.EM.UserTools.superclass.onRender.apply(this, arguments);

		Ext.apply(this.validityCheckForm.getForm(), {
			api: {
				submit: TYPO3.EMSOAP.ExtDirect.checkExtensionkey
			},
			paramsAsHash: false

		});
		Ext.apply(this.registerForm.getForm(), {
			api: {
				submit: TYPO3.EMSOAP.ExtDirect.registerExtensionkey
			},
			paramsAsHash: false

		});

	},

	transferkey: function() {
		var grid = Ext.getCmp('em-userextgrid');
		var extkey = grid.getSelectionModel().getSelected().data.extensionkey;
		this.transferWindow = new Ext.Window({
			width: 300,
			height: 170,
			modal: true,
			title: TYPO3.lang.cmd_transferkey + ' "' + extkey + '"',
			layout: 'form',
			items: [
				{
					xtype: 'form',
					labelWidth: 120,
					items: [
						new Ext.Container({
							xtype: 'displayfield',
							html: TYPO3.lang.transferkeys_info,
							labelWidth: 1,
							style: 'margin: 10px 5px;'
						}),
						{
							xtype: 'textfield',
							fieldLabel: TYPO3.lang.cmd_transferkey_to_user,
							ref: '../transfertouser',
							allowBlank: false
						}
					]
				}
			],
			buttons: [
				{
					text: TYPO3.lang.cmd_transferkey_do,
					handler: function(button) {
						var toUser = this.transferWindow.transfertouser.getRawValue();
						button.disable();
						this.transferWindow.body.mask(TYPO3.lang.cmd_transferkey);
						TYPO3.EMSOAP.ExtDirect.transferExtensionKey(extkey, toUser, function(response) {
							if (response.success) {
								TYPO3.Flashmessage.display(TYPO3.Severity.ok, String.format(TYPO3.lang.transferkeys_success, response.key, response.user), '', 5);
								Ext.getCmp('em-userextgrid').store.load();
							} else {
								TYPO3.Flashmessage.display(TYPO3.Severity.error, String.format(TYPO3.lang.transferkeys_fail, response.key, response.user),response.message, 15);
							}
							this.transferWindow.close();
						}, this)
					},
					scope: this
				}
			]
		}).show();
	},

	deletekey: function() {
		var grid = Ext.getCmp('em-userextgrid');
		var extkey = grid.getSelectionModel().getSelected().data.extensionkey;

		this.deleteWindow = new Ext.Window({
			width: 300,
			height: 130,
			modal: true,
			title: TYPO3.lang.cmd_deletekey + ' "' + extkey + '"',
			items: [
				{
					items: [
						new Ext.Container({
							xtype: 'displayfield',
							html: TYPO3.lang.deletekey_info,
							labelWidth: 1,
							style: 'margin: 10px 5px;'
						})
					]
				}
			],
			buttons: [
				{
					text: 'delete',
					handler: function(button) {
						button.disable();
						this.deleteWindow.body.mask(TYPO3.lang.cmd_deletekey);
						TYPO3.EMSOAP.ExtDirect.deleteExtensionKey(extkey, function(response) {
							if (response.success) {
								TYPO3.Flashmessage.display(TYPO3.Severity.ok, String.format(TYPO3.lang.deletekey_success, response.key), '', 5);
								Ext.getCmp('em-userextgrid').store.load();
							} else {
								TYPO3.Flashmessage.display(TYPO3.Severity.error, String.format(TYPO3.lang.deletekey_fail, response.key), response.message, 15);
							}
							this.deleteWindow.close();
						}, this)
					},
					scope: this
				}
			]
		}).show();
	}


});

Ext.reg('TYPO3.EM.UserTools', TYPO3.EM.UserTools);
