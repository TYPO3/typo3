<?php
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
	protected $jsFilesAfterInline;
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
			'contrib/swfupload/swfupload.js',
			'contrib/swfupload/plugins/swfupload.swfobject.js',
			'contrib/swfupload/plugins/swfupload.cookies.js',
			'contrib/swfupload/plugins/swfupload.queue.js',
			'md5.js',
			'js/common.js',
			'js/sizemanager.js',
			'js/toolbarmanager.js',
			'js/modulemenu.js',
			'js/iecompatibility.js',
			'js/flashupload.js',
			'../t3lib/jsfunc.evalfield.js'
		);
		$this->jsFilesAfterInline = array(
			'js/backend.js',
				'js/loginrefresh.js',
		);
			// add default BE css
		$this->css      = '';
		$this->cssFiles = array(
			'backend-scaffolding' => 'css/backend-scaffolding.css',
			'backend-style'       => 'css/backend-style.css',
			'modulemenu'          => 'css/modulemenu.css',
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
			$toolbarItem = t3lib_div::makeInstance($toolbarItemClassName, $this);

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
				<iframe src="alt_intro.php" name="content" id="content" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto"></iframe>
			</div>
		</div>
	</div>
';

		/******************************************************
		 * now put the complete backend document together
		 ******************************************************/

		/** @var $pageRenderer t3lib_PageRenderer */
		$pageRenderer = $GLOBALS['TBE_TEMPLATE']->getPageRenderer();
		$pageRenderer->loadScriptaculous('builder,effects,controls,dragdrop');
		$pageRenderer->loadExtJS();

			// remove duplicate entries
		$this->jsFiles = array_unique($this->jsFiles);

			// add javascript
		foreach($this->jsFiles as $jsFile) {
			$GLOBALS['TBE_TEMPLATE']->loadJavascriptLib($jsFile);
		}
		$GLOBALS['TBE_TEMPLATE']->JScode .= chr(10);
		$this->generateJavascript();
		$GLOBALS['TBE_TEMPLATE']->JScode .= $GLOBALS['TBE_TEMPLATE']->wrapScriptTags($this->js) . chr(10);

		foreach($this->jsFilesAfterInline as $jsFile) {
			$GLOBALS['TBE_TEMPLATE']->JScode .= '
			<script type="text/javascript" src="' . $jsFile . '"></script>';
		}


			// FIXME abusing the JS container to add CSS, need to fix template.php
		foreach($this->cssFiles as $cssFileName => $cssFile) {
			$GLOBALS['TBE_TEMPLATE']->addStyleSheet($cssFileName, $cssFile);

				// load addditional css files to overwrite existing core styles
			if(!empty($GLOBALS['TBE_STYLES']['stylesheets'][$cssFileName])) {
				$GLOBALS['TBE_TEMPLATE']->addStyleSheet($cssFileName . 'TBE_STYLES', $GLOBALS['TBE_STYLES']['stylesheets'][$cssFileName]);
			}
		}

		if(!empty($this->css)) {
			$GLOBALS['TBE_TEMPLATE']->inDocStylesArray['backend.php'] = $this->css;
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
	 * Gets the label of the BE user currently logged in
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
			'<span title="' . $GLOBALS['LANG']->getLL('switchtouser') . '">' .
			$GLOBALS['LANG']->getLL('switchtousershort') . ' </span>' .
			'<span>' . htmlspecialchars($label) . '</span>';
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

		// determine security level from conf vars and default to super challenged
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) {
			$this->loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'];
		} else {
			$this->loginSecurityLevel = 'superchallenged';
		}

		$t3Configuration = array(
			'siteUrl' => t3lib_div::getIndpEnv('TYPO3_SITE_URL'),
			'PATH_typo3' => $pathTYPO3,
			'PATH_typo3_enc' => rawurlencode($pathTYPO3),
			'username' => htmlspecialchars($GLOBALS['BE_USER']->user['username']),
			'uniqueID' => t3lib_div::shortMD5(uniqid('')),
			'securityLevel' => $this->loginSecurityLevel,
			'TYPO3_mainDir' => TYPO3_mainDir,
			'pageModule' => $pageModule,
			'condensedMode' => $GLOBALS['BE_USER']->uc['condensedMode'] ? 1 : 0 ,
			'workspaceFrontendPreviewEnabled' => $GLOBALS['BE_USER']->workspace != 0 && !$GLOBALS['BE_USER']->user['workspace_preview'] ? 0 : 1,
			'veriCode' => $GLOBALS['BE_USER']->veriCode(),
			'denyFileTypes' => PHP_EXTENSIONS_DEFAULT,
			'showRefreshLoginPopup' => isset($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup']) ? intval($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup']) : FALSE,
		);
		$t3LLLcore = array(
			'waitTitle' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_logging_in') ,
			'refresh_login_failed' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_failed'),
			'refresh_login_failed_message' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_failed_message'),
			'refresh_login_title' => sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_title'), htmlspecialchars($GLOBALS['BE_USER']->user['username'])),
			'login_expired' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.login_expired'),
			'refresh_login_username' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_username'),
			'refresh_login_password' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_password'),
			'refresh_login_emptyPassword' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_emptyPassword'),
			'refresh_login_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_button'),
			'refresh_logout_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_logout_button'),
			'please_wait' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.please_wait'),
			'be_locked' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.be_locked'),
			'refresh_login_countdown_singular' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_countdown_singular'),
			'refresh_login_countdown' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_countdown'),
			'login_about_to_expire' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.login_about_to_expire'),
			'login_about_to_expire_title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.login_about_to_expire_title'),
			'refresh_login_refresh_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_refresh_button'),
			'refresh_direct_logout_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_direct_logout_button'),
		);
		$t3LLLfileUpload = array(
			'windowTitle' => $GLOBALS['LANG']->getLL('fileUpload_windowTitle'),
			'buttonSelectFiles' => $GLOBALS['LANG']->getLL('fileUpload_buttonSelectFiles'),
			'buttonCancelAll' => $GLOBALS['LANG']->getLL('fileUpload_buttonCancelAll'),
			'infoComponentMaxFileSize' => $GLOBALS['LANG']->getLL('fileUpload_infoComponentMaxFileSize'),
			'infoComponentFileUploadLimit' => $GLOBALS['LANG']->getLL('fileUpload_infoComponentFileUploadLimit'),
			'infoComponentFileTypeLimit' => $GLOBALS['LANG']->getLL('fileUpload_infoComponentFileTypeLimit'),
			'infoComponentOverrideFiles' => $GLOBALS['LANG']->getLL('fileUpload_infoComponentOverrideFiles'),
	 		'processRunning' => $GLOBALS['LANG']->getLL('fileUpload_processRunning'),
			'uploadWait' => $GLOBALS['LANG']->getLL('fileUpload_uploadWait'),
			'uploadStarting' => $GLOBALS['LANG']->getLL('fileUpload_uploadStarting'),
			'uploadProgress' => $GLOBALS['LANG']->getLL('fileUpload_uploadProgress'),
			'uploadSuccess' => $GLOBALS['LANG']->getLL('fileUpload_uploadSuccess'),
			'errorQueueLimitExceeded' => $GLOBALS['LANG']->getLL('fileUpload_errorQueueLimitExceeded'),
			'errorQueueFileSizeLimit' => $GLOBALS['LANG']->getLL('fileUpload_errorQueueFileSizeLimit'),
			'errorQueueZeroByteFile' =>  $GLOBALS['LANG']->getLL('fileUpload_errorQueueZeroByteFile'),
			'errorQueueInvalidFiletype' => $GLOBALS['LANG']->getLL('fileUpload_errorQueueInvalidFiletype'),
			'errorUploadHttp' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadHttpError'),
			'errorUploadMissingUrl' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadMissingUrl'),
			'errorUploadIO' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadIO'),
			'errorUploadSecurityError' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadSecurityError'),
			'errorUploadLimit' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadLimit'),
			'errorUploadFailed' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadFailed'),
			'errorUploadFileIDNotFound' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadFileIDNotFound'),
			'errorUploadFileValidation' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadFileValidation'),
			'errorUploadFileCancelled' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadFileCancelled'),
			'errorUploadStopped' => $GLOBALS['LANG']->getLL('fileUpload_errorUploadStopped'),
			'allErrorMessageTitle' => $GLOBALS['LANG']->getLL('fileUpload_allErrorMessageTitle'),
			'allErrorMessageText' => $GLOBALS['LANG']->getLL('fileUpload_allErrorMessageText'),
			'allError401' => $GLOBALS['LANG']->getLL('fileUpload_allError401'),
			'allError2038' => $GLOBALS['LANG']->getLL('fileUpload_allError2038'),
		);

			// Convert labels/settings back to UTF-8 since json_encode() only works with UTF-8:
		if ($GLOBALS['LANG']->charSet !== 'utf-8') {
			$t3Configuration['username'] = $GLOBALS['LANG']->csConvObj->conv($t3Configuration['username'], $GLOBALS['LANG']->charSet, 'utf-8');
			$GLOBALS['LANG']->csConvObj->convArray($t3LLLcore, $GLOBALS['LANG']->charSet, 'utf-8');
			$GLOBALS['LANG']->csConvObj->convArray($t3LLLfileUpload, $GLOBALS['LANG']->charSet, 'utf-8');
		}

		$this->js .= '
	TYPO3.configuration = ' . json_encode($t3Configuration) . ';
	TYPO3.LLL = {
		core : ' . json_encode($t3LLLcore) . ',
		fileUpload: ' . json_encode($t3LLLfileUpload) . '
	};

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
		this.veriCode = TYPO3.configuration.veriCode;
		this.denyFileTypes = TYPO3.configuration.denyFileTypes;
	}
	var TS = new typoSetup();

	var currentModuleLoaded = "";
	var goToModule = ' . $goToModuleSwitch . ';

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
	var fsMod = new fsModules();' . $moduleFramesHelper . ';';



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
		$toolbarItem = t3lib_div::makeInstance($toolbarItemClassName, $this);

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
