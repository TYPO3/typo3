<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Ingo Renner <ingo@typo3.org>
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
require_once('classes/class.donatewindow.php');

	// core toolbar items
require('classes/class.clearcachemenu.php');
require('classes/class.shortcutmenu.php');
require('classes/class.livesearch.php');

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
	private   $menuWidthDefault = 190; // intentionally private as nobody should modify defaults
	protected $menuWidth;
	protected $debug;

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
	 * Pagerenderer
	 *
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * constructor
	 *
	 * @return	void
	 */
	public function __construct() {
			// set debug flag for BE development only
		$this->debug = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) === 1;

			// Initializes the backend modules structure for use later.
		$this->moduleLoader = t3lib_div::makeInstance('t3lib_loadModules');
		$this->moduleLoader->load($GLOBALS['TBE_MODULES']);

		$this->moduleMenu = t3lib_div::makeInstance('ModuleMenu');

		$this->pageRenderer = $GLOBALS['TBE_TEMPLATE']->getPageRenderer();
		$this->pageRenderer->loadScriptaculous('builder,effects,controls,dragdrop');
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();

		$this->pageRenderer->addExtOnReadyCode(
			'TYPO3.Backend = new TYPO3.Viewport(TYPO3.Viewport.configuration);
			if (typeof console === "undefined") {
				console = TYPO3.Backend.DebugConsole;
			}
			TYPO3.ContextHelpWindow.init();',
			TRUE
		);
		$this->pageRenderer->addJsInlineCode(
			'consoleOverrideWithDebugPanel',
			'//already done'
		);
		$this->pageRenderer->addExtDirectCode();

			// add default BE javascript
		$this->js      = '';
		$this->jsFiles = array(
			'modernizr'             => 'contrib/modernizr/modernizr.min.js',
			'swfupload'             => 'contrib/swfupload/swfupload.js',
			'swfupload.swfobject'   => 'contrib/swfupload/plugins/swfupload.swfobject.js',
			'swfupload.cookies'     => 'contrib/swfupload/plugins/swfupload.cookies.js',
			'swfupload.queue'       => 'contrib/swfupload/plugins/swfupload.queue.js',
			'plupload'              => 'contrib/plupload/js/plupload.full.min.js',
			'md5'                   => 'md5.js',
			'common'                => 'js/common.js',
			'toolbarmanager'        => 'js/toolbarmanager.js',
			'modulemenu'            => 'js/modulemenu.js',
			'iecompatibility'       => 'js/iecompatibility.js',
			'flashupload'           => 'js/flashupload.js',
			'pluploadPanel'         => 'js/extjs/ext.ux.plupload.js',
			'pluploadWindow'        => 'js/extjs/PluploadWindow.js',
			'evalfield'             => '../t3lib/jsfunc.evalfield.js',
			'flashmessages'         => '../t3lib/js/extjs/ux/flashmessages.js',
			'tabclosemenu'          => '../t3lib/js/extjs/ux/ext.ux.tabclosemenu.js',
			'notifications'         => '../t3lib/js/extjs/notifications.js',
			'backend'               => 'js/backend.js',
			'loginrefresh'          => 'js/loginrefresh.js',
			'debugPanel'            => 'js/extjs/debugPanel.js',
			'viewport'              => 'js/extjs/viewport.js',
			'iframepanel'           => 'js/extjs/iframepanel.js',
			'viewportConfiguration' => 'js/extjs/viewportConfiguration.js',
			'util'					=> '../t3lib/js/extjs/util.js',
		);

		if ($this->debug) {
			unset($this->jsFiles['loginrefresh']);
		}

			// add default BE css
		$this->css      = '';
		$this->cssFiles = array();

		$this->toolbarItems = array();
		$this->initializeCoreToolbarItems();

		$this->menuWidth = $this->menuWidthDefault;
		if (isset($GLOBALS['TBE_STYLES']['dims']['leftMenuFrameW']) && (int) $GLOBALS['TBE_STYLES']['dims']['leftMenuFrameW'] != (int) $this->menuWidth) {
			$this->menuWidth = (int) $GLOBALS['TBE_STYLES']['dims']['leftMenuFrameW'];
		}

		$this->executeHook('constructPostProcess');
	}

	/**
	 * initializes the core toolbar items
	 *
	 * @return	void
	 */
	protected function initializeCoreToolbarItems() {

		$coreToolbarItems = array(
			'shortcuts'         => 'ShortcutMenu',
			'clearCacheActions' => 'ClearCacheMenu',
			'liveSearch'        => 'LiveSearch'
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
		$this->executeHook('renderPreProcess');

		if (t3lib_div::makeInstance('DonateWindow')->isDonateWindowAllowed()) {
			$this->pageRenderer->addJsFile('js/donate.js');
		}

			// prepare the scaffolding, at this point extension may still add javascript and css
		$logo         = t3lib_div::makeInstance('TYPO3Logo');
		$logo->setLogo('gfx/typo3logo_mini.png');



			// create backend scaffolding
		$backendScaffolding = '
		<div id="typo3-top-container" class="x-hide-display">
			<div id="typo3-logo">'.$logo->render().'</div>
			<div id="typo3-top" class="typo3-top-toolbar">' .
				$this->renderToolbar() .
			'</div>
		</div>

';

		/******************************************************
		 * now put the complete backend document together
		 ******************************************************/

		foreach($this->cssFiles as $cssFileName => $cssFile) {
			$this->pageRenderer->addCssFile($cssFile);

				// load addditional css files to overwrite existing core styles
			if(!empty($GLOBALS['TBE_STYLES']['stylesheets'][$cssFileName])) {
				$this->pageRenderer->addCssFile($GLOBALS['TBE_STYLES']['stylesheets'][$cssFileName]);
			}
		}

		if(!empty($this->css)) {
			$this->pageRenderer->addCssInlineBlock('BackendInlineCSS', $this->css);
		}

		foreach ($this->jsFiles as $jsFile) {
			$this->pageRenderer->addJsFile($jsFile);
		}


			// TYPO3.Ajax.ExtDirec is used for BE toolbar items and may be later for general services
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'] as $key => $value) {
				if (strpos($key, 'TYPO3.Ajax.ExtDirect') !== FALSE) {
					$this->pageRenderer->addJsFile('ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.Ajax.ExtDirect', NULL, FALSE);
					break;
				}
			}
		}
		$this->pageRenderer->addJsFile('ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.BackendUserSettings', NULL, FALSE);

		$this->generateJavascript();
		$this->pageRenderer->addJsInlineCode('BackendInlineJavascript', $this->js);

		$this->loadResourcesForRegisteredNavigationComponents();

			// add state provider
		$GLOBALS['TBE_TEMPLATE']->setExtDirectStateProvider();
		$states = $GLOBALS['BE_USER']->uc['BackendComponents']['States'];
			//save states in BE_USER->uc
		$extOnReadyCode = '
			Ext.state.Manager.setProvider(new TYPO3.state.ExtDirectProvider({
				key: "BackendComponents.States"
			}));
		';
		if ($states) {
		    $extOnReadyCode .= 'Ext.state.Manager.getProvider().initState(' . $states . ');';
		}
		$this->pageRenderer->addExtOnReadyCode($extOnReadyCode);


			// set document title:
		$title = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
			? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'].' [TYPO3 '.TYPO3_version.']'
			: 'TYPO3 '.TYPO3_version
		);

		$this->content = $backendScaffolding;
			// Renders the module page
		$this->content = $GLOBALS['TBE_TEMPLATE']->render(
			$title,
			$this->content
		);

		$hookConfiguration = array('content' => &$this->content);
		$this->executeHook('renderPostProcess', $hookConfiguration);

		echo $this->content;
	}

	/**
	 * Loads the css and javascript files of all registered navigation widgets
	 *
	 * @return void
	 */
	protected function loadResourcesForRegisteredNavigationComponents() {
		if (!is_array($GLOBALS['TBE_MODULES']['_navigationComponents'])) {
			return;
		}

		$loadedComponents = array();
		foreach ($GLOBALS['TBE_MODULES']['_navigationComponents'] as $module => $info) {
			if (in_array($info['componentId'], $loadedComponents)) {
				continue;
			}
			$loadedComponents[] = $info['componentId'];

			$component = strtolower(substr($info['componentId'], strrpos($info['componentId'], '-') + 1));
			$componentDirectory = 'components/' . $component . '/';

			if ($info['isCoreComponent']) {
				$absoluteComponentPath = PATH_t3lib . 'js/extjs/' . $componentDirectory;
				$relativeComponentPath = '../' . str_replace(PATH_site, '', $absoluteComponentPath);
			} else {
				$absoluteComponentPath = t3lib_extMgm::extPath($info['extKey']) . $componentDirectory;
				$relativeComponentPath = t3lib_extMgm::extRelPath($info['extKey']) . $componentDirectory;
			}

			$cssFiles = t3lib_div::getFilesInDir($absoluteComponentPath . 'css/', 'css');
			if (file_exists($absoluteComponentPath . 'css/loadorder.txt')) {
					//don't allow inclusion outside directory
				$loadOrder = str_replace('../', '', t3lib_div::getURL($absoluteComponentPath . 'css/loadorder.txt'));
				$cssFilesOrdered = t3lib_div::trimExplode(LF, $loadOrder, TRUE);
				$cssFiles = array_merge($cssFilesOrdered, $cssFiles);
			}
			foreach ($cssFiles as $cssFile) {
				$this->pageRenderer->addCssFile($relativeComponentPath . 'css/' . $cssFile);
			}

			$jsFiles = t3lib_div::getFilesInDir($absoluteComponentPath . 'javascript/', 'js');
			if (file_exists($absoluteComponentPath . 'javascript/loadorder.txt')) {
					//don't allow inclusion outside directory
				$loadOrder = str_replace('../', '', t3lib_div::getURL($absoluteComponentPath . 'javascript/loadorder.txt'));
				$jsFilesOrdered = t3lib_div::trimExplode(LF, $loadOrder, TRUE);
				$jsFiles = array_merge($jsFilesOrdered, $jsFiles);
			}

			foreach ($jsFiles as $jsFile) {
				$this->pageRenderer->addJsFile($relativeComponentPath . 'javascript/' . $jsFile);
			}

			if (is_array($info['extDirectNamespaces']) && count($info['extDirectNamespaces'])) {
				foreach ($info['extDirectNamespaces'] as $namespace) {
					$this->pageRenderer->addJsFile(
						'ajax.php?ajaxID=ExtDirect::getAPI&namespace=' . $namespace,
						NULL,
						FALSE
					);
				}
			}
		}
	}

	/**
	 * renders the items in the top toolbar
	 *
	 * @return	string	top toolbar elements as HTML
	 */
	protected function renderToolbar() {

			// move search to last position
		$search = $this->toolbarItems['liveSearch'];
		unset($this->toolbarItems['liveSearch']);
		$this->toolbarItems['liveSearch'] = $search;

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

                $icon = t3lib_iconWorks::getSpriteIcon('status-user-'. ($BE_USER->isAdmin() ? 'admin' : 'backend'));

		$label = $GLOBALS['BE_USER']->user['realName'] ?
			$BE_USER->user['realName'] . ' (' . $BE_USER->user['username'] . ')' :
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

			// If another page module was specified, replace the default Page module with the new one
		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		$pageModule    = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
		if (!$GLOBALS['BE_USER']->check('modules', $pageModule)) {
			$pageModule = '';
		}

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
			'inWorkspace' => $GLOBALS['BE_USER']->workspace !== 0 ? 1 : 0,
			'workspaceFrontendPreviewEnabled' => $GLOBALS['BE_USER']->user['workspace_preview'] ? 1 : 0,
			'veriCode' => $GLOBALS['BE_USER']->veriCode(),
			'denyFileTypes' => PHP_EXTENSIONS_DEFAULT,
			'moduleMenuWidth' => $this->menuWidth - 1,
			'topBarHeight' => (isset($GLOBALS['TBE_STYLES']['dims']['topFrameH']) ? intval($GLOBALS['TBE_STYLES']['dims']['topFrameH']) : 30),
			'showRefreshLoginPopup' => isset($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup']) ? intval($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup']) : FALSE,
			'listModulePath' => t3lib_extMgm::isLoaded('list') ? t3lib_extMgm::extRelPath('list') . 'mod1/' : '',
			'debugInWindow' => $GLOBALS['BE_USER']->uc['debugInWindow'] ? 1 : 0,
			'ContextHelpWindows' => array(
				'width' => 600,
				'height' => 400
			),
			'FileUpload' => array(
				'maxFileSize' => t3lib_div::getMaxUploadFileSize()
			)
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
			'loadingIndicator' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:loadingIndicator'),
			'be_locked' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.be_locked'),
			'refresh_login_countdown_singular' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_countdown_singular'),
			'refresh_login_countdown' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_countdown'),
			'login_about_to_expire' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.login_about_to_expire'),
			'login_about_to_expire_title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.login_about_to_expire_title'),
			'refresh_login_refresh_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_refresh_button'),
			'refresh_direct_logout_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_direct_logout_button'),
			'tabs_closeAll' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:tabs.closeAll'),
			'tabs_closeOther' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:tabs.closeOther'),
			'tabs_close' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:tabs.close'),
			'tabs_openInBrowserWindow' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:tabs.openInBrowserWindow'),
			'donateWindow_title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:donateWindow.title'),
			'donateWindow_message' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:donateWindow.message'),
			'donateWindow_button_donate' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:donateWindow.button_donate'),
			'donateWindow_button_disable' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:donateWindow.button_disable'),
			'donateWindow_button_postpone' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:donateWindow.button_postpone'),
			'csh_tooltip_loading' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:csh_tooltip_loading'),
		);
		$t3LLLfileUpload = array(
			'windowTitle' => $GLOBALS['LANG']->getLL('fileUpload_windowTitle'),
			'buttonSelectFiles' => $GLOBALS['LANG']->getLL('fileUpload_buttonSelectFiles'),
			'buttonStartUpload' => $GLOBALS['LANG']->getLL('fileUpload_buttonStartUpload'),
			'buttonCancelAll' => $GLOBALS['LANG']->getLL('fileUpload_buttonCancelAll'),
			'progressText' => $GLOBALS['LANG']->getLL('fileUpload_progressText'),
			'infoComponentMaxFileSize' => $GLOBALS['LANG']->getLL('fileUpload_infoComponentMaxFileSize'),
			'infoComponentFileUploadLimit' => $GLOBALS['LANG']->getLL('fileUpload_infoComponentFileUploadLimit'),
			'infoComponentFileTypeLimit' => $GLOBALS['LANG']->getLL('fileUpload_infoComponentFileTypeLimit'),
			'infoComponentOverrideFiles' => $GLOBALS['LANG']->getLL('fileUpload_infoComponentOverrideFiles'),
			'infoFileQueueEmpty' => $GLOBALS['LANG']->getLL('fileUpload_infoFileQueueEmpty'),
			'infoFileQueued' => $GLOBALS['LANG']->getLL('fileUpload_infoFileQueued'),
			'infoFileFinished' => $GLOBALS['LANG']->getLL('fileUpload_infoFileFinished'),
			'infoFileUploading' => $GLOBALS['LANG']->getLL('fileUpload_infoFileQueued'),
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
		$t3LLLliveSearch = array(
			'title' => $GLOBALS['LANG']->getLL('liveSearch_title'),
			'helpTitle' => $GLOBALS['LANG']->getLL('liveSearch_helpTitle'),
			'emptyText' => $GLOBALS['LANG']->getLL('liveSearch_emptyText'),
			'loadingText' => $GLOBALS['LANG']->getLL('liveSearch_loadingText'),
			'listEmptyText' => $GLOBALS['LANG']->getLL('liveSearch_listEmptyText'),
			'showAllResults' => $GLOBALS['LANG']->getLL('liveSearch_showAllResults'),
			'helpDescription' => $GLOBALS['LANG']->getLL('liveSearch_helpDescription'),
			'helpDescriptionPages' => $GLOBALS['LANG']->getLL('liveSearch_helpDescriptionPages'),
			'helpDescriptionContent' => $GLOBALS['LANG']->getLL('liveSearch_helpDescriptionContent')
		);
			// Convert labels/settings back to UTF-8 since json_encode() only works with UTF-8:
		if ($GLOBALS['LANG']->charSet !== 'utf-8') {
			$t3Configuration['username'] = $GLOBALS['LANG']->csConvObj->conv($t3Configuration['username'], $GLOBALS['LANG']->charSet, 'utf-8');
			$GLOBALS['LANG']->csConvObj->convArray($t3LLLcore, $GLOBALS['LANG']->charSet, 'utf-8');
			$GLOBALS['LANG']->csConvObj->convArray($t3LLLfileUpload, $GLOBALS['LANG']->charSet, 'utf-8');
			$GLOBALS['LANG']->csConvObj->convArray($t3LLLliveSearch, $GLOBALS['LANG']->charSet, 'utf-8');
		}

		$this->js .= '
	TYPO3.configuration = ' . json_encode($t3Configuration) . ';
	TYPO3.LLL = {
		core : ' . json_encode($t3LLLcore) . ',
		fileUpload: ' . json_encode($t3LLLfileUpload) . ',
		liveSearch: ' . json_encode($t3LLLliveSearch) . '
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
		//backwards compatibility
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

	top.goToModule = function(modName, cMR_flag, addGetVars) {
		TYPO3.ModuleMenu.App.showModule(modName, addGetVars);
	}
	' . $this->setStartupModule();

			// Check editing of page:
		$this->handlePageEditing();

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

					// "Shortcuts" have been renamed to "Bookmarks"
					// @deprecated remove shortcuts code in TYPO3 4.7
				$shortcutSetPageTree = $GLOBALS['BE_USER']->getTSConfigVal('options.shortcut_onEditId_dontSetPageTree');
				$bookmarkSetPageTree = $GLOBALS['BE_USER']->getTSConfigVal('options.bookmark_onEditId_dontSetPageTree');
				if ($shortcutSetPageTree !== '') {
					t3lib_div::deprecationLog('options.shortcut_onEditId_dontSetPageTree - since TYPO3 4.5, will be removed in TYPO3 4.7 - use options.bookmark_onEditId_dontSetPageTree instead');
				}

					// Checking page edit parameter:
				if (!$shortcutSetPageTree && !$bookmarkSetPageTree) {

					$shortcutKeepExpanded = $GLOBALS['BE_USER']->getTSConfigVal('options.shortcut_onEditId_keepExistingExpanded');
					$bookmarkKeepExpanded = $GLOBALS['BE_USER']->getTSConfigVal('options.bookmark_onEditId_keepExistingExpanded');
					$keepExpanded = ($shortcutKeepExpanded || $bookmarkKeepExpanded);

						// Expanding page tree:
					t3lib_BEfunc::openPageTree(intval($editRecord['pid']), !$keepExpanded);

					if ($shortcutKeepExpanded) {
						t3lib_div::deprecationLog('options.shortcut_onEditId_keepExistingExpanded - since TYPO3 4.5, will be removed in TYPO3 4.7 - use options.bookmark_onEditId_keepExistingExpanded instead');
					}
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
			return '
					// start in module:
				top.startInModule = [\'' . $startModule . '\', ' . t3lib_div::quoteJSvalue($moduleParameters) . '];
			';
		} else {
			return '';
		}

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

		if(empty($this->cssFiles[$cssFileName])) {
			$this->cssFiles[$cssFileName] = $cssFile;
			$cssFileAdded = true;
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

	/**
	 * Executes defined hooks functions for the given identifier.
	 *
	 * These hook identifiers are valid:
	 *	+ constructPostProcess
	 *	+ renderPreProcess
	 *	+ renderPostProcess
	 *
	 * @param string $identifier Specific hook identifier
	 * @param array $hookConfiguration Additional configuration passed to hook functions
	 * @return void
	 */
	protected function executeHook($identifier, array $hookConfiguration = array()) {
		$options =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php'];

		if(isset($options[$identifier]) && is_array($options[$identifier])) {
			foreach($options[$identifier] as $hookFunction) {
				t3lib_div::callUserFunction($hookFunction, $hookConfiguration, $this);
			}
		}
	}
}


	// include XCLASS
if(defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/backend.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/backend.php']);
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
