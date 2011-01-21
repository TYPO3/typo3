<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * Implements the preview controller of the workspace module.
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage Controller
 */
class Tx_Workspaces_Controller_PreviewController extends Tx_Workspaces_Controller_AbstractController {

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * @return void
	 */
	protected function initializeAction() {
		parent::initializeAction();

		$resourcePath = t3lib_extMgm::extRelPath('workspaces') . 'Resources/Public/';
		$GLOBALS['TBE_STYLES']['extJS']['theme'] = $resourcePath . 'StyleSheet/preview.css';
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();
		$this->pageRenderer->enableExtJsDebug();

			// Load  JavaScript:
		$this->pageRenderer->addExtDirectCode();
		$this->pageRenderer->addJsFile(
			$this->backPath . 'ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.Workspaces&' . TYPO3_version,
			NULL,
			FALSE
		);

		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/flashmessages.js');
		$this->pageRenderer->addJsFile($this->backPath . 'js/extjs/iframepanel.js');

		$this->pageRenderer->addJsFile($resourcePath . 'JavaScript/Ext.ux.plugins.TabStripContainer.js');
		$this->pageRenderer->addJsFile($resourcePath . 'JavaScript/preview.js');

			// todo this part should be done with inlineLocallanglabels
		$this->pageRenderer->addJsInlineCode('workspace-inline-code', $this->generateJavascript());
	}

	/**
	 * Basically makes sure that the workspace preview is rendered.
	 * The preview itself consists of three frames, so there are
	 * only the frames-urls we've to generate here
	 *
	 * @return void
	 */
	public function indexAction() {
		// @todo language doesn't always come throught the L parameter
		// @todo Evaluate how the intval() call can be used with Extbase validators/filters
		$language = intval(t3lib_div::_GP('L'));

		$controller = t3lib_div::makeInstance('Tx_Workspaces_Controller_ReviewController', TRUE);
		$uriBuilder = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Routing_UriBuilder');

		$wsSettingsPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . 'typo3/';
		$wsSettingsUri = $uriBuilder->uriFor('singleIndex', array(), $controller, 'workspaces', 'web_workspacesworkspaces');
		$wsSettingsParams = '&tx_workspaces_web_workspacesworkspaces[controller]=Review';
		$wsSettingsUrl = $wsSettingsPath . $wsSettingsUri . $wsSettingsParams;

		$viewDomain = t3lib_BEfunc::getViewDomain($this->pageId);
		$wsBaseUrl =  $viewDomain . '/index.php?id=' . $this->pageId . '&L=' . $language;

		// @todo - handle new pages here
		// branchpoints are not handled anymore because this feature is not supposed anymore
		if (tx_Workspaces_Service_Workspaces::isNewPage($this->pageId)) {
			$wsNewPageUri = $uriBuilder->uriFor('newPage', array(), $this, 'workspaces', 'web_workspacesworkspaces');
			$wsNewPageParams = '&tx_workspaces_web_workspacesworkspaces[controller]=Preview';
			$this->view->assign('liveUrl', $wsSettingsPath . $wsNewPageUri . $wsNewPageParams);
		} else {
			$this->view->assign('liveUrl', $wsBaseUrl . '&ADMCMD_noBeUser=1');
		}
		$this->view->assign('wsUrl', $wsBaseUrl . '&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS=' . $GLOBALS['BE_USER']->workspace);
		$this->view->assign('wsSettingsUrl', $wsSettingsUrl);
		$this->view->assign('backendDomain', t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'));
		$GLOBALS['BE_USER']->setAndSaveSessionData('workspaces.backend_domain', t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'));
		$this->pageRenderer->addJsInlineCode("workspaces.preview.lll" , "TYPO3.LLL.Workspaces = {
			visualPreview: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.visualPreview', true) . "',
			listView: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.listView', true) . "',
			livePreview: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.livePreview', true) . "',
			livePreviewDetail: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.livePreviewDetail', true) . "',
			workspacePreview: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.workspacePreview', true) . "',
			workspacePreviewDetail: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.workspacePreviewDetail', true) . "',
			modeSlider: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeSlider', true) . "',
			modeVbox: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeVbox', true) . "',
			modeHbox: '" . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeHbox', true) . "'
		};\n");
	}

	/**
	 * @return void
	 */
	public function newPageAction() {
		$message = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:info.newpage.detail'),
			$GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:info.newpage'),
			t3lib_FlashMessage::INFO
		);
		t3lib_FlashMessageQueue::addMessage($message);
	}

	/**
	 * Generates the JavaScript code for the backend,
	 * and since we're loading a backend module outside of the actual backend
	 * this copies parts of the backend.php
	 *
	 * @return	void
	 */
	protected function generateJavascript() {
		$pathTYPO3 = t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')) . '/';

			// If another page module was specified, replace the default Page module with the new one
		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
		if (!$GLOBALS['BE_USER']->check('modules', $pageModule)) {
			$pageModule = '';
		}

		$menuFrameName = 'menu';
		if ($GLOBALS['BE_USER']->uc['noMenuMode'] === 'icons') {
			$menuFrameName = 'topmenuFrame';
		}

			// determine security level from conf vars and default to super challenged
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) {
			$loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'];
		} else {
			$loginSecurityLevel = 'superchallenged';
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
			'listModulePath' => t3lib_extMgm::isLoaded('recordlist') ? t3lib_extMgm::extRelPath('recordlist') . 'mod1/' : '',
			'debugInWindow' => $GLOBALS['BE_USER']->uc['debugInWindow'] ? 1 : 0,
			'ContextHelpWindows' => array(
				'width' => 600,
				'height' => 400
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
		);

			// Convert labels/settings back to UTF-8 since json_encode() only works with UTF-8:
		if ($GLOBALS['LANG']->charSet !== 'utf-8') {
			$t3Configuration['username'] = $GLOBALS['LANG']->csConvObj->conv($t3Configuration['username'], $GLOBALS['LANG']->charSet, 'utf-8');
			$GLOBALS['LANG']->csConvObj->convArray($t3LLLcore, $GLOBALS['LANG']->charSet, 'utf-8');
		}

		$js .= '
		TYPO3.configuration = ' . json_encode($t3Configuration) . ';
		TYPO3.LLL = {
			core : ' . json_encode($t3LLLcore) . '
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
		';
		return $js;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Controller/PreviewController.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Controller/PreviewController.php']);
}
?>