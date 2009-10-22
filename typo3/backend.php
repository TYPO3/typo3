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
			'modulemenu'          => 'css/modulemenu.css'
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

			// set doctype
		$GLOBALS['TBE_TEMPLATE']->docType = 'xhtml_trans';

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
			$menu = $toolbarItem->render();
			if ($menu) {
				$additionalAttributes = $toolbarItem->getAdditionalAttributes();   
				$toolbar .= '<li' . $additionalAttributes . '>' .$menu. '</li>';
			}
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

		$this->js .= '
	/**
	 * Function similar to PHPs  rawurlencode();
	 */
	function rawurlencode(str) {	//
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
		this.username = "'.htmlspecialchars($GLOBALS['BE_USER']->user['username']).'";
		this.uniqueID = "'.t3lib_div::shortMD5(uniqid('')).'";
		this.navFrameWidth = 0;
	}
	var TS = new typoSetup();

	/**
	 * Functions for session-expiry detection:
	 */
	function busy()	{	//
		this.loginRefreshed = busy_loginRefreshed;
		this.checkLoginTimeout = busy_checkLoginTimeout;
		this.openRefreshWindow = busy_OpenRefreshWindow;
		this.busyloadTime=0;
		this.openRefreshW=0;
		this.reloginCancelled=0;
	}
	function busy_loginRefreshed()	{	//
		var date = new Date();
		this.busyloadTime = Math.floor(date.getTime()/1000);
		this.openRefreshW=0;
	}
	function busy_checkLoginTimeout()	{	//
		var date = new Date();
		var theTime = Math.floor(date.getTime()/1000);
		if (theTime > this.busyloadTime+'.intval($GLOBALS['BE_USER']->auth_timeout_field).'-30)	{
			return true;
		}
	}
	function busy_OpenRefreshWindow()	{	//
		vHWin=window.open("login_frameset.php","relogin_"+TS.uniqueID,"height=350,width=700,status=0,menubar=0,location=1");
		vHWin.focus();
		this.openRefreshW=1;
	}
	function busy_checkLoginTimeout_timer()	{	//
		if (busy.checkLoginTimeout() && !busy.reloginCancelled && !busy.openRefreshW)	{
			if (confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login')).'))	{
				busy.openRefreshWindow();
			} else	{
				busy.reloginCancelled = 1;
			}
		}
		window.setTimeout("busy_checkLoginTimeout_timer();",2*1000);	// Each 2nd second is enough for checking. The popup will be triggered 10 seconds before the login expires (see above, busy_checkLoginTimeout())

			// Detecting the frameset module navigation frame widths (do this AFTER setting new timeout so that any errors in the code below does not prevent another time to be set!)
		if (top && top.content && top.content.nav_frame && top.content.nav_frame.document && top.content.nav_frame.document.body)	{
			TS.navFrameWidth = (top.content.nav_frame.document.documentElement && top.content.nav_frame.document.documentElement.clientWidth) ? top.content.nav_frame.document.documentElement.clientWidth : top.content.nav_frame.document.body.clientWidth;
		}
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
	busy_checkLoginTimeout_timer();

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

		startInModule(\''.$startModule.'\', false, '.t3lib_div::quoteJSvalue($moduleParameters).');
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