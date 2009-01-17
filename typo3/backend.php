<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Ingo Renner <ingo@typo3.org>
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

require_once('init.php');
require_once('template.php');
require_once('interfaces/interface.backend_toolbaritem.php');

require('classes/class.typo3logo.php');
require('classes/class.modulemenu.php');

	// core toolbar items
require('classes/class.workspaceselector.php');
require('classes/class.clearcachemenu.php');
require('classes/class.shortcutmenu.php');
require('classes/class.backendsearchmenu.php');

require_once(PATH_t3lib.'class.t3lib_loadmodules.php');
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');
require_once('class.alt_menu_functions.inc');
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xml');


/**
 * Class for rendering the TYPO3 backend version 4.2+
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class TYPO3backend {

	protected $content;
	protected $css;
	protected $cssFiles;
	protected $js;
	protected $jsFiles;
	protected $toolbarItems;
	private   $menuWidthDefault = 160; // intentionally private as nobody should modify defaults
	protected $menuWidth;

	/**
	 * Object for loading backend modules
	 *
	 * @var t3lib_loadModules
	 */
	protected $moduleLoader;

	/**
	 * module menu generating object
	 *
	 * @var ModuleMenu
	 */
	protected $moduleMenu;

	/**
	 * constructor
	 *
	 * @return	void
	 */
	public function __construct() {

			// Initializes the backend modules structure for use later.
		$this->moduleLoader = t3lib_div::makeInstance('t3lib_loadModules');
		$this->moduleLoader->load($GLOBALS['TBE_MODULES']);

		$this->moduleMenu = t3lib_div::makeInstance('ModuleMenu');

			// add default BE javascript
		$this->js      = '';
		$this->jsFiles = array(
			'contrib/prototype/prototype.js',
			'contrib/scriptaculous/scriptaculous.js?load=builder,effects,controls,dragdrop',
			'contrib/extjs/adapter/prototype/ext-prototype-adapter.js',
			'contrib/extjs/ext-all.js',
			'md5.js',
			'js/backend.js',
			'js/common.js',
			'js/sizemanager.js',
			'js/toolbarmanager.js',
			'js/modulemenu.js',
			'js/iecompatibility.js',
			'../t3lib/jsfunc.evalfield.js'
		);

			// add default BE css
		$this->css      = '';
		$this->cssFiles = array(
			'backend-scaffolding' => 'css/backend-scaffolding.css',
			'backend-style'       => 'css/backend-style.css',
			'modulemenu'          => 'css/modulemenu.css',
			'extJS'				  => 'contrib/extjs/resources/css/ext-all.css',
			'extJS-gray'		  => 'contrib/extjs/resources/css/xtheme-gray.css'
		);

		$this->toolbarItems = array();
		$this->initializeCoreToolbarItems();

		$this->menuWidth = $this->menuWidthDefault;
		if (isset($GLOBALS['TBE_STYLES']['dims']['leftMenuFrameW']) && (int) $GLOBALS['TBE_STYLES']['dims']['leftMenuFrameW'] != (int) $this->menuWidth) {
			$this->menuWidth = (int) $GLOBALS['TBE_STYLES']['dims']['leftMenuFrameW'];
		}
	}

	/**
	 * initializes the core toolbar items
	 *
	 * @return	void
	 */
	protected function initializeCoreToolbarItems() {

		$coreToolbarItems = array(
			'workspaceSelector' => 'WorkspaceSelector',
			'shortcuts'         => 'ShortcutMenu',
			'clearCacheActions' => 'ClearCacheMenu',
			'backendSearch'     => 'BackendSearchMenu'
		);

		foreach($coreToolbarItems as $toolbarItemName => $toolbarItemClassName) {
				// Get name of XCLASS (if any):
			$toolbarItemClassName = t3lib_div::makeInstanceClassName($toolbarItemClassName);
			$toolbarItem = new $toolbarItemClassName($this);

			if(!($toolbarItem instanceof backend_toolbarItem)) {
				throw new UnexpectedValueException('$toolbarItem "'.$toolbarItemName.'" must implement interface backend_toolbarItem', 1195126772);
			}

			if($toolbarItem->checkAccess()) {
				$this->toolbarItems[$toolbarItemName] = $toolbarItem;
			} else {
				unset($toolbarItem);
			}
		}
	}

	/**
	 * main function generating the BE scaffolding
	 *
	 * @return	void
	 */
	public function render()	{

			// prepare the scaffolding, at this point extension may still add javascript and css
		$logo         = t3lib_div::makeInstance('TYPO3Logo');
		$logo->setLogo('gfx/typo3logo_mini.png');

		$menu         = $this->moduleMenu->render();

		if ($this->menuWidth != $this->menuWidthDefault) {
			$this->css .= '
				#typo3-logo,
				#typo3-side-menu {
					width: ' . ($this->menuWidth - 1) . 'px;
				}

				#typo3-top,
				#typo3-content {
					margin-left: ' . $this->menuWidth . 'px;
				}
			';
		}

			// create backend scaffolding
		$backendScaffolding = '
	<div id="typo3-backend">
		<div id="typo3-top-container">
			<div id="typo3-logo">'.$logo->render().'</div>
			<div id="typo3-top" class="typo3-top-toolbar">'
				.$this->renderToolbar()
			.'</div>
		</div>
		<div id="typo3-main-container">
			<div id="typo3-side-menu">
				'.$menu.'
			</div>
			<div id="typo3-content">
				<iframe src="alt_intro.php" name="content" id="content" marginwidth="0" marginheight="0" frameborder="0"  scrolling="auto" noresize="noresize"></iframe>
			</div>
		</div>
	</div>
';

		/******************************************************
		 * now put the complete backend document together
		 ******************************************************/

			// remove duplicate entries
		$this->jsFiles = array_unique($this->jsFiles);

			// add javascript
		foreach($this->jsFiles as $jsFile) {
			$GLOBALS['TBE_TEMPLATE']->JScode .= '
			<script type="text/javascript" src="'.$jsFile.'"></script>';
		}
		$GLOBALS['TBE_TEMPLATE']->JScode .= chr(10);
		$this->generateJavascript();
		$GLOBALS['TBE_TEMPLATE']->JScode .= $GLOBALS['TBE_TEMPLATE']->wrapScriptTags($this->js);

			// FIXME abusing the JS container to add CSS, need to fix template.php
		foreach($this->cssFiles as $cssFileName => $cssFile) {
			$GLOBALS['TBE_TEMPLATE']->JScode .= '
			<link rel="stylesheet" type="text/css" href="'.$cssFile.'" />
			';

				// load addditional css files to overwrite existing core styles
			if(!empty($GLOBALS['TBE_STYLES']['stylesheets'][$cssFileName])) {
				$GLOBALS['TBE_TEMPLATE']->JScode .= '
			<link rel="stylesheet" type="text/css" href="'.$GLOBALS['TBE_STYLES']['stylesheets'][$cssFileName].'" />
				';
			}
		}

		if(!empty($this->css)) {
			$GLOBALS['TBE_TEMPLATE']->JScode .= '
			<style type="text/css" id="internalStyle">
				'.$this->css.'
			</style>';
		}

			// set document title:
		$title = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
			? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'].' [TYPO3 '.TYPO3_version.']'
			: 'TYPO3 '.TYPO3_version
		);

			// start page header:
		$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage($title);
		$this->content .= $backendScaffolding;
		$this->content .= $GLOBALS['TBE_TEMPLATE']->endPage();

		echo $this->content;
	}

	/**
	 * renders the items in the top toolbar
	 *
	 * @return	string	top toolbar elements as HTML
	 */
	protected function renderToolbar() {

			// move search to last position
		$search = $this->toolbarItems['backendSearch'];
		unset($this->toolbarItems['backendSearch']);
		$this->toolbarItems['backendSearch'] = $search;

		$toolbar = '<ul id="typo3-toolbar">';
		$toolbar.= '<li>'.$this->getLoggedInUserLabel().'</li>
					<li><div id="logout-button" class="toolbar-item no-separator">'.$this->moduleMenu->renderLogoutButton().'</div></li>';

		foreach($this->toolbarItems as $toolbarItem) {
			$additionalAttributes = $toolbarItem->getAdditionalAttributes();

			$toolbar .= '<li'.$additionalAttributes.'>'.$toolbarItem->render().'</li>';
		}

		return $toolbar.'</ul>';
	}

	/**
	 * gets the label of the currently loged in BE user
	 *
	 * @return	string		html code snippet displaying the currently logged in user
	 */
	protected function getLoggedInUserLabel() {
		global $BE_USER, $BACK_PATH;

		$icon = '<img'.t3lib_iconWorks::skinImg(
			'',
			$BE_USER->isAdmin() ?
				'gfx/i/be_users_admin.gif' :
				'gfx/i/be_users.gif',
			'width="18" height="16"'
		)
		.' title="" alt="" />';

		$label = $GLOBALS['BE_USER']->user['realName'] ?
			$BE_USER->user['realName'].' ['.$BE_USER->user['username'].']' :
			$BE_USER->user['username'];

			// Link to user setup if it's loaded and user has access
		$link = '';
		if (t3lib_extMgm::isLoaded('setup') && $BE_USER->check('modules','user_setup')) {
			$link = '<a href="#" onclick="top.goToModule(\'user_setup\');this.blur();return false;">';
		}

		$username = '">'.$link.$icon.'<span>'.htmlspecialchars($label).'</span>'.($link?'</a>':'');

			// superuser mode
		if($BE_USER->user['ses_backuserid']) {
			$username   = ' su-user">'.$icon.
			'<span title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:switchtouser').'">SU: </span>'.
			'<span>'.htmlspecialchars($label).'</span>';
		}

		return '<div id="username" class="toolbar-item no-separator'.$username.'</div>';
	}

	/**
	 * Generates the JavaScript code for the backend.
	 *
	 * @return	void
	 */
	protected function generateJavascript() {

		$pathTYPO3          = t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')).'/';
		$goToModuleSwitch   = $this->moduleMenu->getGotoModuleJavascript();
		$moduleFramesHelper = implode(chr(10), $this->moduleMenu->getFsMod());

			// If another page module was specified, replace the default Page module with the new one
		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		$pageModule    = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

		$menuFrameName = 'menu';
		if($GLOBALS['BE_USER']->uc['noMenuMode'] === 'icons') {
			$menuFrameName = 'topmenuFrame';
		}

		// create challenge for the (re)login form and save it in the session.
		$challenge = md5(uniqid('').getmypid());
		session_start();
		$_SESSION['login_challenge'] = $challenge;

		// determine security level from conf vars and default to super challenged
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) {
			$this->loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'];
		} else {
			$this->loginSecurityLevel = 'superchallenged';
		}

		$this->js .= '
	Ext.BLANK_IMAGE_URL = "' .
				// t3lib_div::locationHeaderUrl() will include '/typo3/' in the URL
				htmlspecialchars(t3lib_div::locationHeaderUrl('gfx/clear.gif')) .
				'";

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
		var siteUrl = "' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . '";
		return rawurlencode(str_replace(siteUrl, \'\', str));
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
		this.PATH_typo3 = "'.$pathTYPO3.'";
		this.PATH_typo3_enc = "'.rawurlencode($pathTYPO3).'";
		this.username = "'.$GLOBALS['BE_USER']->user['username'].'";
		this.uniqueID = "'.t3lib_div::shortMD5(uniqid('')).'";
		this.navFrameWidth = 0;
		this.securityLevel = "'.$this->loginSecurityLevel.'";
	}
	var TS = new typoSetup();

	/**
	 * Functions for session-expiry detection:
	 */
	function busy()	{	//
		this.loginRefreshed = busy_loginRefreshed;
		this.openRefreshWindow = busy_OpenRefreshWindow;
		this.busyloadTime=0;
		this.openRefreshW=0;
		this.reloginCancelled=0;
		this.earlyRelogin=0;

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
			parameters: "ajaxID=BackendLogin::isTimedOut&skipSessionUpdate=1",
			onSuccess: function(e) {
				var login = e.responseJSON.login.evalJSON();
				if(login.timed_out) {
					busy.openRefreshWindow();
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
					waitTitle: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_logging_in') . '",
					waitMsg: " ",
					params: "ajaxID=BackendLogin::login&login_status=login",
					success: function() {
						win.close();
						setTimeout("busy.startTimer()", 2000);

					},

					failure: function() {
						// TODO: add failure to notification system instead of alert
						// Ext.tip.msg("Login failed", "Username or Password incorrect!");
						Ext.Msg.alert("' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_failed') . '", "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_failed_message') . '");
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
					title: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_title') . '",
					defaultType: "textfield",
					width: "100%",
					bodyStyle: "padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;",

					items: [{
							xtype: "panel",
							bodyStyle: "margin-bottom: 7px; border: none;",
							html: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.login_expired') . '"
						},{
							fieldLabel: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_username') . '",
							name: "username",
							id: "login_username",
							allowBlank: false,
							width: 250
						},{
							fieldLabel: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_password') . '",
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
							value: "' . $challenge . '"
						}
					],
					keys:({
						key: Ext.EventObject.ENTER,
						fn: submitForm,
						scope: this
					}),
					buttons: [{
						text: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_button') . '",
						formBind: true,
						handler: submitForm
					}, {
						text: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_logout_button') . '",
						formBind: true,
						handler: function() {
							top.location.href = "' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . '";
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

	function busy_OpenRefreshWindow() {
		this.openRefreshW = 1;

		busy.stopTimer();

		var seconds = 30;
		var progressTextFormatSingular = "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_countdown_singular') . '";
		var progressTextFormatPlural = "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_countdown') . '";
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
					html: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.login_about_to_expire') . '"
				},
				progressControl
			],
			title: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.login_about_to_expire_title') . '",
			width: 450,

			buttons: [{
				text: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login_refresh_button') . '",
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
				text: "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_direct_logout_button') . '",
				handler: function() {
					top.location.href = "' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'logout.php";
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

		top.goToModule("'.$pageModule.'", 0, addGetVars?addGetVars:"");
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

	/**
	 * Function used to switch modules
	 */
	var currentModuleLoaded = "";
	var goToModule = '.$goToModuleSwitch.'

	/**
	 * Frameset Module object
	 *
	 * Used in main modules with a frameset for submodules to keep the ID between modules
	 * Typically that is set by something like this in a Web>* sub module:
	 *		if (top.fsMod) top.fsMod.recentIds["web"] = "\'.intval($this->id).\'";
	 * 		if (top.fsMod) top.fsMod.recentIds["file"] = "...(file reference/string)...";
	 */
	function fsModules()	{	//
		this.recentIds=new Array();					// used by frameset modules to track the most recent used id for list frame.
		this.navFrameHighlightedID=new Array();		// used by navigation frames to track which row id was highlighted last time
		this.currentMainLoaded="";
		this.currentBank="0";
	}
	var fsMod = new fsModules();
	'.$moduleFramesHelper.'

		// Used by Frameset Modules
	var condensedMode = '.($GLOBALS['BE_USER']->uc['condensedMode']?1:0).';
	var currentSubScript = "";
	var currentSubNavScript = "";

		// Used for tab-panels:
	var DTM_currentTabs = new Array();

		// status of WS FE preview
	var WorkspaceFrontendPreviewEnabled = ' . (($GLOBALS['BE_USER']->workspace != 0 && !$GLOBALS['BE_USER']->user['workspace_preview']) ? 'false' : 'true') . ';
		';

			// Check editing of page:
		$this->handlePageEditing();
		$this->setStartupModule();
	}

	/**
	 * Checking if the "&edit" variable was sent so we can open it for editing the page.
	 * Code based on code from "alt_shortcut.php"
	 *
	 * @return	void
	 */
	protected function handlePageEditing()	{

		if(!t3lib_extMgm::isLoaded('cms'))	{
			return;
		}

			// EDIT page:
		$editId     = preg_replace('/[^[:alnum:]_]/', '', t3lib_div::_GET('edit'));
		$editRecord = '';

		if($editId)	{

				// Looking up the page to edit, checking permissions:
			$where = ' AND ('.$GLOBALS['BE_USER']->getPagePermsClause(2)
					.' OR '.$GLOBALS['BE_USER']->getPagePermsClause(16).')';

			if(t3lib_div::testInt($editId))	{
				$editRecord = t3lib_BEfunc::getRecordWSOL('pages', $editId, '*', $where);
			} else {
				$records = t3lib_BEfunc::getRecordsByField('pages', 'alias', $editId, $where);

				if(is_array($records))	{
					reset($records);
					$editRecord = current($records);
					t3lib_BEfunc::workspaceOL('pages', $editRecord);
				}
			}

				// If the page was accessible, then let the user edit it.
			if(is_array($editRecord) && $GLOBALS['BE_USER']->isInWebMount($editRecord['uid']))	{
					// Setting JS code to open editing:
				$this->js .= '
		// Load page to edit:
	window.setTimeout("top.loadEditId('.intval($editRecord['uid']).');", 500);
			';
					// Checking page edit parameter:
				if(!$GLOBALS['BE_USER']->getTSConfigVal('options.shortcut_onEditId_dontSetPageTree')) {

						// Expanding page tree:
					t3lib_BEfunc::openPageTree(intval($editRecord['pid']), !$GLOBALS['BE_USER']->getTSConfigVal('options.shortcut_onEditId_keepExistingExpanded'));
				}
			} else {
				$this->js .= '
		// Warning about page editing:
	alert('.$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->getLL('noEditPage'), $editId)).');
			';
			}
		}
	}

	/**
	 * Sets the startup module from either GETvars module and mpdParams or user configuration.
	 *
	 * @return	void
	 */
	protected function setStartupModule() {
		$startModule = preg_replace('/[^[:alnum:]_]/', '', t3lib_div::_GET('module'));

		if(!$startModule)	{
			if ($GLOBALS['BE_USER']->uc['startModule'])	{
				$startModule = $GLOBALS['BE_USER']->uc['startModule'];
			} else if($GLOBALS['BE_USER']->uc['startInTaskCenter'])	{
				$startModule = 'user_task';
			}
		}

		$moduleParameters = t3lib_div::_GET('modParams');
		if($startModule) {
			$this->js .= '
			// start in module:
		function startInModule(modName, cMR_flag, addGetVars)	{
			Event.observe(document, \'dom:loaded\', function() {
				top.goToModule(modName, cMR_flag, addGetVars);
			});
		}

		startInModule(\''.$startModule.'\', false, \''.$moduleParameters.'\');
			';
		}
	}

	/**
	 * generates the code for the TYPO3 logo, either the default TYPO3 logo or a custom one
	 *
	 * @return	string	HTML code snippet to display the TYPO3 logo
	 */
	protected function getLogo() {
		$logo = '<a href="http://www.typo3.com/" target="_blank" onclick="'.$GLOBALS['TBE_TEMPLATE']->thisBlur().'">'.
				'<img'.t3lib_iconWorks::skinImg('','gfx/alt_backend_logo.gif','width="117" height="32"').' title="TYPO3 Content Management Framework" alt="" />'.
				'</a>';

			// overwrite with custom logo
		if($GLOBALS['TBE_STYLES']['logo'])	{
			if(substr($GLOBALS['TBE_STYLES']['logo'], 0, 3) == '../')	{
				$imgInfo = @getimagesize(PATH_site.substr($GLOBALS['TBE_STYLES']['logo'], 3));
			}
			$logo = '<a href="http://www.typo3.com/" target="_blank" onclick="'.$GLOBALS['TBE_TEMPLATE']->thisBlur().'">'.
				'<img src="'.$GLOBALS['TBE_STYLES']['logo'].'" '.$imgInfo[3].' title="TYPO3 Content Management Framework" alt="" />'.
				'</a>';
		}

		return $logo;
	}

	/**
	 * adds a javascript snippet to the backend
	 *
	 * @param	string	javascript snippet
	 * @return	void
	 */
	public function addJavascript($javascript) {
			// TODO do we need more checks?
		if(!is_string($javascript)) {
			throw new InvalidArgumentException('parameter $javascript must be of type string', 1195129553);
		}

		$this->js .= $javascript;
	}

	/**
	 * adds a javscript file to the backend after it has been checked that it exists
	 *
	 * @param	string	javascript file reference
	 * @return	boolean	true if the javascript file was successfully added, false otherwise
	 */
	public function addJavascriptFile($javascriptFile) {
		$jsFileAdded = false;

			//TODO add more checks if neccessary
		if(file_exists(t3lib_div::resolveBackPath(PATH_typo3.$javascriptFile))) {
			$this->jsFiles[] = $javascriptFile;
			$jsFileAdded     = true;
		}

		return $jsFileAdded;
	}

	/**
	 * adds a css snippet to the backend
	 *
	 * @param	string	css snippet
	 * @return	void
	 */
	public function addCss($css) {
		if(!is_string($css)) {
			throw new InvalidArgumentException('parameter $css must be of type string', 1195129642);
		}

		$this->css .= $css;
	}

	/**
	 * adds a css file to the backend after it has been checked that it exists
	 *
	 * @param	string	the css file's name with out the .css ending
	 * @param	string	css file reference
	 * @return	boolean	true if the css file was added, false otherwise
	 */
	public function addCssFile($cssFileName, $cssFile) {
		$cssFileAdded = false;

			//TODO add more checks if neccessary
		if(file_exists(t3lib_div::resolveBackPath(PATH_typo3.$cssFile))) {
				// prevent overwriting existing css files
			if(empty($this->cssFiles[$cssFileName])) {
				$this->cssFiles[$cssFileName] = $cssFile;
				$cssFileAdded = true;
			}
		}

		return $cssFileAdded;
	}

	/**
	 * adds an item to the toolbar, the class file for the toolbar item must be loaded at this point
	 *
	 * @param	string	toolbar item name, f.e. tx_toolbarExtension_coolItem
	 * @param	string	toolbar item class name, f.e. tx_toolbarExtension_coolItem
	 * @return	void
	 */
	public function addToolbarItem($toolbarItemName, $toolbarItemClassName) {
		$toolbarItemResolvedClassName = t3lib_div::makeInstanceClassName($toolbarItemClassName);
		$toolbarItem                  = new $toolbarItemResolvedClassName($this);

		if(!($toolbarItem instanceof backend_toolbarItem)) {
			throw new UnexpectedValueException('$toolbarItem "'.$toolbarItemName.'" must implement interface backend_toolbarItem', 1195125501);
		}

		if($toolbarItem->checkAccess()) {
			$this->toolbarItems[$toolbarItemName] = $toolbarItem;
		} else {
			unset($toolbarItem);
		}
	}
}


	// include XCLASS
if(defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/backend.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/backend.php']);
}


	// document generation
$TYPO3backend = t3lib_div::makeInstance('TYPO3backend');

	// include extensions which may add css, javascript or toolbar items
if(is_array($GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'])) {
	foreach($GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'] as $additionalBackendItem) {
		include_once($additionalBackendItem);
	}
}

$TYPO3backend->render();

?>
