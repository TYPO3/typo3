/***************************************************************
*  Copyright notice
*
*  (c) 2009 Steffen Kamper <info@sk-typo3.de>
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

/**
 * Functions for session-expiry detection:
 */
function busy()	{	//
	this.loginRefreshed = busy_loginRefreshed;
	this.openRefreshWindow = busy_OpenRefreshWindow;
	this.openLockedWaitWindow = busy_openLockedWaitWindow;
	this.busyloadTime=0;
	this.openRefreshW=0;
	this.reloginCancelled=0;
	this.earlyRelogin=0;
	this.locked=0;

	// starts the timer and resets the earlyRelogin variable so that
	// the countdown works properly.
	this.startTimer = function() {
		this.earlyRelogin = 0;
		this.timer.start();
	}

	this.stopTimer = function() {
		this.timer.stop();
	}

	// simple timer that polls the server to determine imminent timeout.
	this.timer = new Ajax.PeriodicalUpdater("","ajax.php", {
		method: "get",
		frequency: 60,
		decay: 1,
		parameters: "ajaxID=BackendLogin::isTimedOut&skipSessionUpdate=1",
		onSuccess: function(e) {
			var login = e.responseJSON.login.evalJSON();
			if(login.locked) {
				busy.locked = 1;
				busy.openLockedWaitWindow();
			} else if(login.timed_out) {
 				busy.openRefreshWindow();
 			}
			if (busy.locked && !login.locked && !login.timed_out) {
				busy.locked = 0;
				Ext.MessageBox.hide();
			}
		}
	});

	// this function runs the countdown and opens the login window
	// as soon as the countdown expires.
	this.countDown = function(progressControl, progressTextFormatPlural, progressTextFormatSingular, secondsRemaining, totalSeconds) {

		if(busy.earlyRelogin == 0) {
			if(secondsRemaining > 1) {
				progressControl.updateText(String.format(progressTextFormatPlural, secondsRemaining));
				progressControl.updateProgress(secondsRemaining/(1.0*totalSeconds));
				setTimeout(function () {
						busy.countDown(progressControl, progressTextFormatPlural, progressTextFormatSingular,secondsRemaining - 1, totalSeconds);
					}, 1000);
			} else if(secondsRemaining > 0) {
				progressControl.updateText(String.format(progressTextFormatSingular, secondsRemaining));
				progressControl.updateProgress(secondsRemaining/(1.0*totalSeconds));
				setTimeout(function () {
						busy.countDown(progressControl, progressTextFormatPlural, progressTextFormatSingular,secondsRemaining - 1, totalSeconds);
					}, 1000);
			} else {
				busy.openRefreshW = 1;
				busy.openLogin();
			}
		}
	};

	// Closes the countdown window and opens a new one with a login form.
	this.openLogin = function() {
		var login;
		doChallengeResponse = function(superchallenged) {
			password = $$("#loginform form")[0].p_field.value;

			if (password)	{
				if (superchallenged)	{
					password = MD5(password);	// this makes it superchallenged!!
				}
				str = $("login_username").value+":"+password+":"+$("challenge").value;
				$("userident").value = MD5(str);
				$("password").value = "";

				return true;
			}
		}

		submitForm = function() {
			if(TS.securityLevel == "superchallenged") {
				doChallengeResponse(1);
			} else if (TS.securityLevel == "challenged") {
				doChallengeResponse(0);
			} else {
				$("userident").value = $$("#loginform form")[0].p_field.value;
				$("password").value= "";
			}

			login.getForm().submit({
				method: "post",
				waitTitle: TYPO3.LLL.core.waitTitle,
				waitMsg: " ",
				params: "ajaxID=BackendLogin::login&login_status=login",
				success: function() {
					win.close();
					setTimeout("busy.startTimer()", 2000);

				},

				failure: function() {
					// TODO: add failure to notification system instead of alert
					// Ext.tip.msg("Login failed", "Username or Password incorrect!");
					Ext.Msg.alert(TYPO3.LLL.core.refresh_login_failed, TYPO3.LLL.core.refresh_login_failed_message);
				}
			});
		}

		logout = new Ajax.Request("ajax.php", {
			method: "get",
			parameters: "ajaxID=BackendLogin::logout"
		});

		Ext.onReady(function(){
			login = new Ext.FormPanel({
				url: "ajax.php",
				id: "loginform",
				title: TYPO3.LLL.core.refresh_login_title,
				defaultType: "textfield",
				width: "100%",
				bodyStyle: "padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;",

				items: [{
						xtype: "panel",
						bodyStyle: "margin-bottom: 7px; border: none;",
						html: TYPO3.LLL.core.login_expired
					},{
						fieldLabel: TYPO3.LLL.core.refresh_login_username,
						name: "username",
						id: "login_username",
						allowBlank: false,
						width: 250
					},{
						fieldLabel: TYPO3.LLL.core.refresh_login_password,
						name: "p_field",
						width: 250,
						id: "password",
						inputType: "password"
					},{
						xtype: "hidden",
						name: "userident",
						id: "userident",
						value: ""
					}, {
						xtype: "hidden",
						name: "challenge",
						id: "challenge",
						value: TYPO3.configuration.challenge
					}
				],
				keys:({
					key: Ext.EventObject.ENTER,
					fn: submitForm,
					scope: this
				}),
				buttons: [{
					text: TYPO3.LLL.core.refresh_login_button,
					formBind: true,
					handler: submitForm
				}, {
					text: TYPO3.LLL.core.refresh_logout_button,
					formBind: true,
					handler: function() {
						top.location.href = TYPO3.configuration.siteUrl + TYPO3.configuration.TYPO3_mainDir;
					}
				}]
			});
			win.close();
			win = new Ext.Window({
				width: 450,
				autoHeight: true,
				closable: false,
				resizable: false,
				plain: true,
				border: false,
				modal: true,
				draggable: false,
				items: [login]
			});
			win.show();
		});
	}
}

function busy_loginRefreshed()	{	//
	this.openRefreshW=0;
	this.earlyRelogin=0;
}

function busy_openLockedWaitWindow() {
	Ext.MessageBox.show({
		title: TYPO3.LLL.core.please_wait,
		msg: TYPO3.LLL.core.be_locked,
		width: 500,
		icon: Ext.MessageBox.INFO,
		closable: false
	});
}

function busy_OpenRefreshWindow() {
	this.openRefreshW = 1;

	busy.stopTimer();

	var seconds = 30;
	var progressTextFormatSingular = TYPO3.LLL.core.refresh_login_countdown_singular;
	var progressTextFormatPlural = TYPO3.LLL.core.refresh_login_countdown;
	var progressText = String.format(progressTextFormatPlural, seconds);
	var progressControl = new Ext.ProgressBar({
		autoWidth: true,
		autoHeight: true,
		value: 1,
		text: progressText
	});

	win = new Ext.Window({
		closable: false,
		resizable: false,
		draggable: false,
		modal: true,
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
				refresh = new Ajax.Request("ajax.php", {
					method: "get",
					parameters: "ajaxID=BackendLogin::refreshLogin"
				});
				win.close();
				busy.earlyRelogin = 1;
				setTimeout("busy.startTimer()", 2000);
			}
		}, {
			text: TYPO3.LLL.core.refresh_direct_logout_button,
			handler: function() {
				top.location.href = TYPO3.configuration.siteUrl + TYPO3.configuration.TYPO3_mainDir + "logout.php";
			}
		}]
	});
	win.show();
	busy.countDown(progressControl, progressTextFormatPlural, progressTextFormatSingular, seconds, seconds);
}

/**
 * Initialize login expiration warning object
 */
var busy = new busy();
busy.loginRefreshed();
