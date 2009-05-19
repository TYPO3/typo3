/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Ingo Renner <ingo@typo3.org>
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
 * general backend javascript functions
 */




/**
 * jump the backend to a module
 */
function jump(url, modName, mainModName) {
		// clear information about which entry in nav. tree that might have been highlighted.
	top.fsMod.navFrameHighlightedID = new Array();

	if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
		top.content.nav_frame.refresh_nav();
	}

	top.nextLoadModuleUrl = url;
	top.goToModule(modName);
}

/**
 * shortcut manager to delegate the action of creating shortcuts to the new
 * backend.php shortcut menu or the old shortcut frame depending on what is available
 */
var ShortcutManager = {

	/**
	 * central entry point to create a shortcut, delegates the call to correct endpoint
	 */
	createShortcut: function(confirmQuestion, backPath, moduleName, url) {
		if(confirm(confirmQuestion)) {
			if(typeof TYPO3BackendShortcutMenu != 'undefined') {
					// backend.php
				TYPO3BackendShortcutMenu.createShortcut('', moduleName, url);
			}

			if(top.shortcutFrame) {
					// alt_main.php
				var location = backPath + 'alt_shortcut.php?modName=' + moduleName + '&URL=' + url;
				shortcutFrame.location.href = location;
			}
		}
	}
}


/**
 * Function similar to PHPs  rawurlencode();
 */
function rawurlencode(str) {
	var output = escape(str);
	output = str_replace("*","%2A", output);
	output = str_replace("+","%2B", output);
	output = str_replace("/","%2F", output);
	output = str_replace("@","%40", output);
	return output;
}

/**
 * Function to similar to PHPs  rawurlencode() which removes TYPO3_SITE_URL;
 */
function rawurlencodeAndRemoveSiteUrl(str)	{	//
	var siteUrl = TYPO3.configuration.siteUrl;
	return rawurlencode(str_replace(siteUrl, "", str));
}

/**
 * String-replace function
 */
function str_replace(match,replace,string)	{	//
	var input = ""+string;
	var matchStr = ""+match;
	if (!matchStr)	{return string;}
	var output = "";
	var pointer=0;
	var pos = input.indexOf(matchStr);
	while (pos!=-1)	{
		output+=""+input.substr(pointer, pos-pointer)+replace;
		pointer=pos+matchStr.length;
		pos = input.indexOf(match,pos+1);
	}
	output+=""+input.substr(pointer);
	return output;
}



/**
 * TypoSetup object.
 */
function typoSetup()	{	//
	this.PATH_typo3 = TYPO3.configuration.PATH_typo3;
	this.PATH_typo3_enc = TYPO3.configuration.PATH_typo3_enc;
	this.username = TYPO3.configuration.username;
	this.uniqueID = TYPO3.configuration.uniqueID;
	this.navFrameWidth = 0;
	this.securityLevel = TYPO3.configuration.securityLevel;
}
var TS = new typoSetup();

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
				waitTitle: TYPO3.LLL.waitTitle,
				waitMsg: " ",
				params: "ajaxID=BackendLogin::login&login_status=login",
				success: function() {
					win.close();
					setTimeout(busy.startTimer(), 2000);

				},

				failure: function() {
					// TODO: add failure to notification system instead of alert
					// Ext.tip.msg("Login failed", "Username or Password incorrect!");
					Ext.Msg.alert(TYPO3.LLL.refresh_login_failed, TYPO3.LLL.refresh_login_failed_message);
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
				title: TYPO3.LLL.refresh_login_title,
				defaultType: "textfield",
				width: "100%",
				bodyStyle: "padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;",

				items: [{
						xtype: "panel",
						bodyStyle: "margin-bottom: 7px; border: none;",
						html: TYPO3.LLL.login_expired
					},{
						fieldLabel: TYPO3.LLL.refresh_login_username,
						name: "username",
						id: "login_username",
						allowBlank: false,
						width: 250
					},{
						fieldLabel: TYPO3.LLL.refresh_login_password,
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
					text: TYPO3.LLL.refresh_login_button,
					formBind: true,
					handler: submitForm
				}, {
					text: TYPO3.LLL.refresh_logout_button,
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
		title: TYPO3.LLL.please_wait,
		msg: TYPO3.LLL.be_locked,
		width: 500,
		icon: Ext.MessageBox.INFO,
		closable: false
	});
}

function busy_OpenRefreshWindow() {
	this.openRefreshW = 1;

	busy.stopTimer();

	var seconds = 30;
	var progressTextFormatSingular = TYPO3.LLL.refresh_login_countdown_singular;
	var progressTextFormatPlural = TYPO3.LLL.refresh_login_countdown;
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
				html: TYPO3.LLL.login_about_to_expire
			},
			progressControl
		],
		title: TYPO3.LLL.login_about_to_expire_title,
		width: 450,

		buttons: [{
			text: TYPO3.LLL.refresh_login_refresh_button,
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
			text: TYPO3.LLL.refresh_direct_logout_button,
			handler: function() {
				top.location.href = TYPO3.configuration.siteUrl + TYPO3.configuration.TYPO3_mainDir + "logout.php";
			}
		}]
	});
	win.show();
	busy.countDown(progressControl, progressTextFormatPlural, progressTextFormatSingular, seconds, seconds);
}

/**
 * Launcing information window for records/files (fileref as "table" argument)
 */
function launchView(table,uid,bP)	{	//
	var backPath= bP ? bP : "";
	var thePreviewWindow="";
	thePreviewWindow = window.open(TS.PATH_typo3+"show_item.php?table="+encodeURIComponent(table)+"&uid="+encodeURIComponent(uid),"ShowItem"+TS.uniqueID,"height=400,width=550,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
	if (thePreviewWindow && thePreviewWindow.focus)	{
		thePreviewWindow.focus();
	}
}

/**
 * Opens plain window with url
 */
function openUrlInWindow(url,windowName)	{	//
	regularWindow = window.open(url,windowName,"status=1,menubar=1,resizable=1,location=1,directories=0,scrollbars=1,toolbar=1");
	regularWindow.focus();
	return false;
}

/**
 * Loads a page id for editing in the page edit module:
 */
function loadEditId(id,addGetVars)	{	//
	top.fsMod.recentIds["web"]=id;
	top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_0";		// For highlighting

	if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
		top.content.nav_frame.refresh_nav();
	}

	top.goToModule(TYPO3.configuration.pageModule, 0, addGetVars?addGetVars:"");
}

/**
 * Returns incoming URL (to a module) unless nextLoadModuleUrl is set. If that is the case nextLoadModuleUrl is returned (and cleared)
 * Used by the shortcut frame to set a "intermediate URL"
 */
var nextLoadModuleUrl="";
function getModuleUrl(inUrl)	{	//
	var nMU;
	if (top.nextLoadModuleUrl)	{
		nMU=top.nextLoadModuleUrl;
		top.nextLoadModuleUrl="";
		return nMU;
	} else {
		return inUrl;
	}
}

/**
 * Print properties of an object
 */
function debugObj(obj,name)	{	//
	var acc;
	for (i in obj) {
		if (obj[i])	{
			acc+=i+":  "+obj[i]+"\n";
		}
	}
	alert("Object: "+name+"\n\n"+acc);
}

/**
 * Initialize login expiration warning object
 */
var busy = new busy();
busy.loginRefreshed();




	// Used by Frameset Modules
var condensedMode = TYPO3.configuration.condensedMode;
var currentSubScript = "";
var currentSubNavScript = "";

	// Used for tab-panels:
var DTM_currentTabs = new Array();

	// status of WS FE preview
var WorkspaceFrontendPreviewEnabled = TYPO3.configuration.workspaceFrontendPreviewEnabled;
	
