/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Steffen Kamper <info@sk-typo3.de>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * AJAX login refresh box
 */
Ext.namespace('Ext.ux.TYPO3');

Ext.ux.TYPO3.loginRefresh = Ext.extend(Ext.util.Observable, {
	locked: 0,
	interval: 60,

	constructor: function(config) {
		config = config || {};
		Ext.apply(this, config);
		this.initComponents();
		this.loadingTask = {
			run: function(){
				// interval run
				Ext.Ajax.request({
					url: "ajax.php",
					params: {
						"ajaxID": "BackendLogin::isTimedOut",
						"skipSessionUpdate": 1
					},
					method: "GET",
					success: function(response, options) {
						var result = Ext.util.JSON.decode(response.responseText);
						if (result.login.locked) {
							this.locked = 1;
							Ext.MessageBox.show({
								title: TYPO3.LLL.core.please_wait,
								msg: TYPO3.LLL.core.be_locked,
								width: 500,
								icon: Ext.MessageBox.INFO,
								closable: false
							});
						} else {
							if (this.locked === 1) {
								this.locked = 0;
								Ext.MessageBox.hide();
							}
						}
						if ((result.login.timed_out || result.login.will_time_out) && Ext.getCmp("loginformWindow")) {
							Ext.getCmp("login_username").value = TYPO3.configuration.username;
							this.stopTimer();
							if (result.login.timed_out) {
								this.showLoginForm();
							} else {
								this.progressWindow.show();
							}
						}
					},
					failure: function() {

					},
					scope: this
				});
			},
			interval: this.interval * 1000,
			scope: this
		};
		this.startTimer();
		Ext.ux.TYPO3.loginRefresh.superclass.constructor.call(this, config);
	},

	initComponents: function() {
		var loginPanel = new Ext.FormPanel({
			url: "ajax.php",
			id: "loginform",
			title: TYPO3.LLL.core.refresh_login_title,
			defaultType: 'textfield',
			scope: this,
			width: "100%",
			bodyStyle: "padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;",

			items: [{
					xtype: "panel",
					bodyStyle: "margin-bottom: 7px; border: none;",
					html: TYPO3.LLL.core.login_expired
				},{
					fieldLabel: TYPO3.LLL.core.refresh_login_password,
					name: "p_field",
					width: 250,
					id: "password",
					inputType: "password"
				},{
					inputType: "hidden",
					name: "username",
					id: "login_username",
					value: ""
				},{
					inputType: "hidden",
					name: "userident",
					id: "userident",
					value: ""
				}, {
					inputType: "hidden",
					name: "challenge",
					id: "challenge",
					value: ''
				}
			],
			keys:({
				key: Ext.EventObject.ENTER,
				fn: this.triggerSubmitForm,
				scope: this
			}),
			buttons: [{
				text: TYPO3.LLL.core.refresh_login_button,
				formBind: true,
				handler: this.triggerSubmitForm
			}, {
				text: TYPO3.LLL.core.refresh_logout_button,
				formBind: true,
				handler: function() {
					top.location.href = TYPO3.configuration.siteUrl + TYPO3.configuration.TYPO3_mainDir + "logout.php";
				}
			}]
		});
		this.loginRefreshWindow = new Ext.Window({
			id: "loginformWindow",
			width: 450,
			autoHeight: true,
			closable: true,
			resizable: false,
			plain: true,
			border: false,
			modal: true,
			draggable: false,
			items: [loginPanel],
			listeners: {
				activate: function() {
					Ext.getCmp('password').focus(false, 800);
				}
			}
		});

		var progressControl = new Ext.ProgressBar({
			autoWidth: true,
			autoHeight: true,
			value: 30
		});

		this.progressWindow = new Ext.Window({
			closable: false,
			resizable: false,
			draggable: false,
			modal: true,
			id: "loginRefreshWindow",
			items: [{
					xtype: "panel",
					bodyStyle: "padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;",
					bodyBorder: false,
					autoHeight: true,
					autoWidth: true,
					html: TYPO3.LLL.core.login_about_to_expire
				},
				progressControl
			],
			title: TYPO3.LLL.core.login_about_to_expire_title,
			width: 450,

			buttons: [{
				text: TYPO3.LLL.core.refresh_login_refresh_button,
				handler: function() {
					var refresh = Ext.Ajax.request({
						url: "ajax.php",
						params: {
							"ajaxID": "BackendLogin::isTimedOut"
						},
						method: "GET",
						scope: this
					});
					TYPO3.loginRefresh.progressWindow.hide();
					progressControl.reset();
					TYPO3.loginRefresh.startTimer();
				}
			}, {
				text: TYPO3.LLL.core.refresh_direct_logout_button,
				handler: function() {
					top.location.href = TYPO3.configuration.siteUrl + TYPO3.configuration.TYPO3_mainDir + "logout.php";
				}
			}]
		});
		this.progressWindow.on('show', function(){
			progressControl.wait({
				interval: 1000,
				duration: 30000,
				increment: 32,
				text: String.format(TYPO3.LLL.core.refresh_login_countdown, '30'),
				fn: function() {
					TYPO3.loginRefresh.showLoginForm();
				}
			});

		});
		progressControl.on('update', function(control, value, text) {
			var rest = parseInt(30 - (value * 30), 10);
			if (rest === 1) {
				control.updateText(String.format(TYPO3.LLL.core.refresh_login_countdown_singular, rest));
			} else {
				control.updateText(String.format(TYPO3.LLL.core.refresh_login_countdown, rest));
			}
		});

		this.loginRefreshWindow.on('close', function(){
			TYPO3.loginRefresh.startTimer();
		});
	},

	showLoginForm: function() {
		if (TYPO3.configuration.showRefreshLoginPopup) {
			//log off for sure
			Ext.Ajax.request({
				url: "ajax.php",
				params: {
				"ajaxID": "BackendLogin::logout"
			},
			method: "GET",
			scope: this,
			success: function(response, opts) {
				TYPO3.loginRefresh.showLoginPopup();
			},
			failure: function(response, opts) {
				alert("something went wrong");
			}
			});
		} else {
			Ext.getCmp("loginRefreshWindow").hide();
			Ext.getCmp("loginformWindow").show();
		}
	},

	showLoginPopup: function() {
		Ext.getCmp("loginRefreshWindow").hide();
		var vHWin = window.open("login_frameset.php","relogin_" + TS.uniqueID,"height=450,width=700,status=0,menubar=0,location=1");
		vHWin.focus();
	},

	startTimer: function() {
		Ext.TaskMgr.start(this.loadingTask);
	},

	stopTimer: function() {
		Ext.TaskMgr.stop(this.loadingTask);
	},

	submitForm: function(challenge) {
		var form = Ext.getCmp("loginform").getForm();
		var fields = form.getValues();
		if (fields.p_field === "") {
			Ext.Msg.alert(TYPO3.LLL.core.refresh_login_failed, TYPO3.LLL.core.refresh_login_emptyPassword);
		} else {
			if (TS.securityLevel === "superchallenged") {
				fields.p_field = MD5(fields.p_field);
			}
			if (TS.securityLevel === "superchallenged" || TS.securityLevel === "challenged") {
				fields.challenge = challenge;
				fields.userident = MD5(fields.username + ":" + fields.p_field + ":" + challenge);
			} else {
				fields.userident = fields.p_field;
			}
			fields.p_field =  "";
			form.setValues(fields);

			form.submit({
				method: "POST",
				waitTitle: TYPO3.LLL.core.waitTitle,
				waitMsg: " ",
				params: {
					"ajaxID": "BackendLogin::login",
					"login_status": "login"
				},
				success: function(form, action) {
					// response object is "login" so real result will be available in failure handler
					Ext.getCmp("loginformWindow").hide();
					TYPO3.loginRefresh.startTimer();
				},
				failure: function(form, action) {
					var result = Ext.util.JSON.decode(action.response.responseText).login;
					if (result.success) {
						// User is logged in
						Ext.getCmp("loginformWindow").hide();
						TYPO3.loginRefresh.startTimer();
					} else {
						// TODO: add failure to notification system instead of alert
						Ext.Msg.alert(TYPO3.LLL.core.refresh_login_failed, TYPO3.LLL.core.refresh_login_failed_message);
					}
				}
			});
		}
	},

	triggerSubmitForm: function() {
		if (TS.securityLevel === 'superchallenged' || TS.securityLevel === 'challenged') {
			Ext.Ajax.request({
				url: 'ajax.php',
				params: {
					'ajaxID': 'BackendLogin::getChallenge',
					'skipSessionUpdate': 1
				},
				method: 'GET',
				success: function(response) {
					var result = Ext.util.JSON.decode(response.responseText);
					if (result.challenge) {
						Ext.getCmp('challenge').value = result.challenge;
						TYPO3.loginRefresh.submitForm(result.challenge);
					}
				},
				scope: this
			});
		} else {
			this.submitForm();
		}
	}
});



/**
 * Initialize login expiration warning object
 */
Ext.onReady(function() {
	TYPO3.loginRefresh = new Ext.ux.TYPO3.loginRefresh();
});
