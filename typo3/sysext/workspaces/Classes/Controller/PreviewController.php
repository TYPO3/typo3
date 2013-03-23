<?php
namespace TYPO3\CMS\Workspaces\Controller;
use TYPO3\CMS\Core\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 */
class PreviewController extends \TYPO3\CMS\Workspaces\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Workspaces\Service\StagesService
	 */
	protected $stageService;

	/**
	 * @var \TYPO3\CMS\Workspaces\Service\WorkspaceService
	 */
	protected $workspaceService;

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * @return void
	 */
	protected function initializeAction() {
		parent::initializeAction();
		$this->stageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\StagesService');
		$this->workspaceService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$this->template->setExtDirectStateProvider();
		$resourcePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/StyleSheet/preview.css';
		$GLOBALS['TBE_STYLES']['extJS']['theme'] = $resourcePath;
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();
		// Load  JavaScript:
		$this->pageRenderer->addExtDirectCode(array(
			'TYPO3.Workspaces',
			'TYPO3.ExtDirectStateProvider'
		));
		$states = $GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['States'];
		$this->pageRenderer->addInlineSetting('Workspaces', 'States', $states);
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/notifications.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/flashmessages.js');
		$this->pageRenderer->addJsFile($this->backPath . 'js/extjs/iframepanel.js');
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/notifications.js');
		$resourcePathJavaScript = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/JavaScript/';
		$jsFiles = array(
			'Ext.ux.plugins.TabStripContainer.js',
			'Store/mainstore.js',
			'helpers.js',
			'actions.js'
		);
		foreach ($jsFiles as $jsFile) {
			$this->pageRenderer->addJsFile($resourcePathJavaScript . $jsFile);
		}
		// todo this part should be done with inlineLocallanglabels
		$this->pageRenderer->addJsInlineCode('workspace-inline-code', $this->generateJavascript());
	}

	/**
	 * Basically makes sure that the workspace preview is rendered.
	 * The preview itself consists of three frames, so there are
	 * only the frames-urls we've to generate here
	 *
	 * @param integer $previewWS
	 * @return void
	 */
	public function indexAction($previewWS = NULL) {
		// @todo language doesn't always come throught the L parameter
		// @todo Evaluate how the intval() call can be used with Extbase validators/filters
		$language = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L'));
		// fetch the next and previous stage
		$workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $this->pageId, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
		list(, $nextStage) = $this->stageService->getNextStageForElementCollection($workspaceItemsArray);
		list(, $previousStage) = $this->stageService->getPreviousStageForElementCollection($workspaceItemsArray);
		/** @var $wsService \TYPO3\CMS\Workspaces\Service\WorkspaceService */
		$wsService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$wsList = $wsService->getAvailableWorkspaces();
		$activeWorkspace = $GLOBALS['BE_USER']->workspace;
		if (!is_null($previewWS)) {
			if (in_array($previewWS, array_keys($wsList)) && $activeWorkspace != $previewWS) {
				$activeWorkspace = $previewWS;
				$GLOBALS['BE_USER']->setWorkspace($activeWorkspace);
				\TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal('updatePageTree');
			}
		}
		/** @var $uriBuilder \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder */
		$uriBuilder = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		$wsSettingsPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/';
		$wsSettingsUri = $uriBuilder->uriFor('singleIndex', array(), 'TYPO3\\CMS\\Workspaces\\Controller\\ReviewController', 'workspaces', 'web_workspacesworkspaces');
		$wsSettingsParams = '&tx_workspaces_web_workspacesworkspaces[controller]=Review';
		$wsSettingsUrl = $wsSettingsPath . $wsSettingsUri . $wsSettingsParams;
		$viewDomain = \TYPO3\CMS\Backend\Utility\BackendUtility::getViewDomain($this->pageId);
		$wsBaseUrl = $viewDomain . '/index.php?id=' . $this->pageId . '&L=' . $language;
		// @todo - handle new pages here
		// branchpoints are not handled anymore because this feature is not supposed anymore
		if (\TYPO3\CMS\Workspaces\Service\WorkspaceService::isNewPage($this->pageId)) {
			$wsNewPageUri = $uriBuilder->uriFor('newPage', array(), 'TYPO3\\CMS\\Workspaces\\Controller\\PreviewController', 'workspaces', 'web_workspacesworkspaces');
			$wsNewPageParams = '&tx_workspaces_web_workspacesworkspaces[controller]=Preview';
			$this->view->assign('liveUrl', $wsSettingsPath . $wsNewPageUri . $wsNewPageParams);
		} else {
			$this->view->assign('liveUrl', $wsBaseUrl . '&ADMCMD_noBeUser=1');
		}
		$this->view->assign('wsUrl', $wsBaseUrl . '&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS=' . $GLOBALS['BE_USER']->workspace);
		$this->view->assign('wsSettingsUrl', $wsSettingsUrl);
		$this->view->assign('backendDomain', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
		$splitPreviewTsConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->pageId, 'workspaces.splitPreviewModes');
		$splitPreviewModes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $splitPreviewTsConfig['value']);
		$allPreviewModes = array('slider', 'vbox', 'hbox');
		if (!array_intersect($splitPreviewModes, $allPreviewModes)) {
			$splitPreviewModes = $allPreviewModes;
		}
		$this->pageRenderer->addInlineSetting('Workspaces', 'SplitPreviewModes', $splitPreviewModes);
		$GLOBALS['BE_USER']->setAndSaveSessionData('workspaces.backend_domain', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
		$this->pageRenderer->addInlineSetting('Workspaces', 'disableNextStageButton', $this->isInvalidStage($nextStage));
		$this->pageRenderer->addInlineSetting('Workspaces', 'disablePreviousStageButton', $this->isInvalidStage($previousStage));
		$this->pageRenderer->addInlineSetting('Workspaces', 'disableDiscardStageButton', $this->isInvalidStage($nextStage) && $this->isInvalidStage($previousStage));
		$resourcePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('lang') . 'res/js/be/';
		$this->pageRenderer->addJsFile($resourcePath . 'typo3lang.js');
		$this->pageRenderer->addJsInlineCode('workspaces.preview.lll', '
		TYPO3.lang = {
			visualPreview: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.visualPreview', TRUE)) . ',
			listView: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.listView', TRUE)) . ',
			livePreview: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.livePreview', TRUE)) . ',
			livePreviewDetail: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.livePreviewDetail', TRUE)) . ',
			workspacePreview: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.workspacePreview', TRUE)) . ',
			workspacePreviewDetail: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.workspacePreviewDetail', TRUE)) . ',
			modeSlider: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeSlider', TRUE)) . ',
			modeVbox: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeVbox', TRUE)) . ',
			modeHbox: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:preview.modeHbox', TRUE)) . ',
			discard: ' . Utility\GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:label_doaction_discard', TRUE)) . ',
			nextStage: ' . Utility\GeneralUtility::quoteJSvalue($nextStage['title']) . ',
			previousStage: ' . Utility\GeneralUtility::quoteJSvalue($previousStage['title']) . '
		};TYPO3.l10n.initialize();
');
		$resourcePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/';
		$this->pageRenderer->addJsFile($resourcePath . 'JavaScript/preview.js');
	}

	/**
	 * Evaluate the activate state based on given $stageArray.
	 *
	 * @param array $stageArray
	 * @return boolean
	 * @author Michael Klapper <development@morphodo.com>
	 */
	protected function isInvalidStage($stageArray) {
		return !(is_array($stageArray) && count($stageArray) > 0);
	}

	/**
	 * @return void
	 */
	public function newPageAction() {
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:info.newpage.detail'), $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:info.newpage'), \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
		/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
		$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		$defaultFlashMessageQueue->enqueue($flashMessage);
	}

	/**
	 * Generates the JavaScript code for the backend,
	 * and since we're loading a backend module outside of the actual backend
	 * this copies parts of the backend.php
	 *
	 * @return 	string
	 */
	protected function generateJavascript() {
		$pathTYPO3 = \TYPO3\CMS\Core\Utility\GeneralUtility::dirname(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME')) . '/';
		// If another page module was specified, replace the default Page module with the new one
		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		$pageModule = \TYPO3\CMS\Backend\Utility\BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
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
			'siteUrl' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'),
			'PATH_typo3' => $pathTYPO3,
			'PATH_typo3_enc' => rawurlencode($pathTYPO3),
			'username' => htmlspecialchars($GLOBALS['BE_USER']->user['username']),
			'uniqueID' => \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(uniqid('')),
			'securityLevel' => $this->loginSecurityLevel,
			'TYPO3_mainDir' => TYPO3_mainDir,
			'pageModule' => $pageModule,
			'condensedMode' => $GLOBALS['BE_USER']->uc['condensedMode'] ? 1 : 0,
			'inWorkspace' => $GLOBALS['BE_USER']->workspace !== 0 ? 1 : 0,
			'workspaceFrontendPreviewEnabled' => $GLOBALS['BE_USER']->user['workspace_preview'] ? 1 : 0,
			'veriCode' => $GLOBALS['BE_USER']->veriCode(),
			'denyFileTypes' => PHP_EXTENSIONS_DEFAULT,
			'moduleMenuWidth' => $this->menuWidth - 1,
			'topBarHeight' => isset($GLOBALS['TBE_STYLES']['dims']['topFrameH']) ? intval($GLOBALS['TBE_STYLES']['dims']['topFrameH']) : 30,
			'showRefreshLoginPopup' => isset($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup']) ? intval($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup']) : FALSE,
			'listModulePath' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('recordlist') ? \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('recordlist') . 'mod1/' : '',
			'debugInWindow' => $GLOBALS['BE_USER']->uc['debugInWindow'] ? 1 : 0,
			'ContextHelpWindows' => array(
				'width' => 600,
				'height' => 400
			)
		);
		$t3LLLcore = array(
			'waitTitle' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:mess.refresh_login_logging_in'),
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
			'donateWindow_button_postpone' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:donateWindow.button_postpone')
		);
		$js = '
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


?>