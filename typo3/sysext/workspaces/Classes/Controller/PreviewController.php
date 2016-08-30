<?php
namespace TYPO3\CMS\Workspaces\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Implements the preview controller of the workspace module.
 */
class PreviewController extends AbstractController
{
    /**
     * @var \TYPO3\CMS\Workspaces\Service\StagesService
     */
    protected $stageService;

    /**
     * @var \TYPO3\CMS\Workspaces\Service\WorkspaceService
     */
    protected $workspaceService;

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            /** @var BackendTemplateView $view */
            parent::initializeView($view);
            $view->getModuleTemplate()->getDocHeaderComponent()->disable();
            $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        }
    }

    /**
     * Initializes the controller before invoking an action method.
     *
     * @return void
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        $backendRelPath = ExtensionManagementUtility::extRelPath('backend');
        $workspacesRelPath = ExtensionManagementUtility::extRelPath('workspaces');
        $this->stageService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\StagesService::class);
        $this->workspaceService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        $this->pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/ExtDirect.StateProvider.js');
        $resourcePath = $workspacesRelPath . 'Resources/Public/Css/preview.css';
        $GLOBALS['TBE_STYLES']['extJS']['theme'] = $resourcePath;
        $this->pageRenderer->loadExtJS();
        // Load  JavaScript:
        $this->pageRenderer->addExtDirectCode([
            'TYPO3.Workspaces',
            'TYPO3.ExtDirectStateProvider'
        ]);
        $states = $GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['States'];
        $this->pageRenderer->addInlineSetting('Workspaces', 'States', $states);
        $this->pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/notifications.js');
        $this->pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/iframepanel.js');
        $resourcePathJavaScript = $workspacesRelPath . 'Resources/Public/JavaScript/';
        $jsFiles = [
            'Ext.ux.plugins.TabStripContainer.js',
            'Store/mainstore.js',
            'helpers.js',
            'actions.js'
        ];
        foreach ($jsFiles as $jsFile) {
            $this->pageRenderer->addJsFile($resourcePathJavaScript . $jsFile);
        }
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', BackendUtility::getModuleUrl('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', BackendUtility::getModuleUrl('record_history'));
        // @todo this part should be done with inlineLocallanglabels
        $this->pageRenderer->addJsInlineCode('workspace-inline-code', $this->generateJavascript());
    }

    /**
     * Basically makes sure that the workspace preview is rendered.
     * The preview itself consists of three frames, so there are
     * only the frames-urls we've to generate here
     *
     * @param int $previewWS
     * @return void
     */
    public function indexAction($previewWS = null)
    {
        // Get all the GET parameters to pass them on to the frames
        $queryParameters = GeneralUtility::_GET();
            // Remove the GET parameters related to the workspaces module and the page id
        unset($queryParameters['tx_workspaces_web_workspacesworkspaces']);
        unset($queryParameters['M']);
        unset($queryParameters['id']);
            // Assemble a query string from the retrieved parameters
        $queryString = GeneralUtility::implodeArrayForUrl('', $queryParameters);

        // fetch the next and previous stage
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $this->pageId, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
        list(, $nextStage) = $this->stageService->getNextStageForElementCollection($workspaceItemsArray);
        list(, $previousStage) = $this->stageService->getPreviousStageForElementCollection($workspaceItemsArray);
        /** @var $wsService \TYPO3\CMS\Workspaces\Service\WorkspaceService */
        $wsService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        $wsList = $wsService->getAvailableWorkspaces();
        $activeWorkspace = $GLOBALS['BE_USER']->workspace;
        if (!is_null($previewWS)) {
            if (in_array($previewWS, array_keys($wsList)) && $activeWorkspace != $previewWS) {
                $activeWorkspace = $previewWS;
                $GLOBALS['BE_USER']->setWorkspace($activeWorkspace);
                BackendUtility::setUpdateSignal('updatePageTree');
            }
        }
        /** @var $uriBuilder \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder */
        $uriBuilder = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $wsSettingsPath = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $wsSettingsUri = $uriBuilder->uriFor('singleIndex', [], \TYPO3\CMS\Workspaces\Controller\ReviewController::class, 'workspaces', 'web_workspacesworkspaces');
        $wsSettingsParams = '&tx_workspaces_web_workspacesworkspaces[controller]=Review';
        $wsSettingsUrl = $wsSettingsPath . $wsSettingsUri . $wsSettingsParams;
        $viewDomain = BackendUtility::getViewDomain($this->pageId);
        $wsBaseUrl = $viewDomain . '/index.php?id=' . $this->pageId . $queryString;
        // @todo - handle new pages here
        // branchpoints are not handled anymore because this feature is not supposed anymore
        if (\TYPO3\CMS\Workspaces\Service\WorkspaceService::isNewPage($this->pageId)) {
            $wsNewPageUri = $uriBuilder->uriFor('newPage', [], \TYPO3\CMS\Workspaces\Controller\PreviewController::class, 'workspaces', 'web_workspacesworkspaces');
            $wsNewPageParams = '&tx_workspaces_web_workspacesworkspaces[controller]=Preview';
            $this->view->assign('liveUrl', $wsSettingsPath . $wsNewPageUri . $wsNewPageParams . '&ADMCMD_prev=IGNORE');
        } else {
            $this->view->assign('liveUrl', $wsBaseUrl . '&ADMCMD_noBeUser=1&ADMCMD_prev=IGNORE');
        }
        $this->view->assign('wsUrl', $wsBaseUrl . '&ADMCMD_prev=IGNORE&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS=' . $GLOBALS['BE_USER']->workspace);
        $this->view->assign('wsSettingsUrl', $wsSettingsUrl);
        $this->view->assign('backendDomain', GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
        $splitPreviewTsConfig = BackendUtility::getModTSconfig($this->pageId, 'workspaces.splitPreviewModes');
        $splitPreviewModes = GeneralUtility::trimExplode(',', $splitPreviewTsConfig['value']);
        $allPreviewModes = ['slider', 'vbox', 'hbox'];
        if (!array_intersect($splitPreviewModes, $allPreviewModes)) {
            $splitPreviewModes = $allPreviewModes;
        }
        $this->pageRenderer->addInlineSetting('Workspaces', 'SplitPreviewModes', $splitPreviewModes);
        $GLOBALS['BE_USER']->setAndSaveSessionData('workspaces.backend_domain', GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
        $this->pageRenderer->addInlineSetting('Workspaces', 'disableNextStageButton', $this->isInvalidStage($nextStage));
        $this->pageRenderer->addInlineSetting('Workspaces', 'disablePreviousStageButton', $this->isInvalidStage($previousStage));
        $this->pageRenderer->addInlineSetting('Workspaces', 'disableDiscardStageButton', $this->isInvalidStage($nextStage) && $this->isInvalidStage($previousStage));
        $resourcePath = ExtensionManagementUtility::extRelPath('lang') . 'Resources/Public/JavaScript/';
        $this->pageRenderer->addJsFile($resourcePath . 'Typo3Lang.js');
        $this->pageRenderer->addJsInlineCode('workspaces.preview.lll', '
		TYPO3.lang = {
			visualPreview: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.visualPreview', true)) . ',
			listView: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.listView', true)) . ',
			livePreview: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.livePreview', true)) . ',
			livePreviewDetail: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.livePreviewDetail', true)) . ',
			workspacePreview: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.workspacePreview', true)) . ',
			workspacePreviewDetail: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.workspacePreviewDetail', true)) . ',
			modeSlider: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.modeSlider', true)) . ',
			modeVbox: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.modeVbox', true)) . ',
			modeHbox: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:preview.modeHbox', true)) . ',
			discard: ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:label_doaction_discard', true)) . ',
			nextStage: ' . GeneralUtility::quoteJSvalue($nextStage['title']) . ',
			previousStage: ' . GeneralUtility::quoteJSvalue($previousStage['title']) . '
		};TYPO3.l10n.initialize();
');
        $resourcePath = ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/';
        $this->pageRenderer->addJsFile($resourcePath . 'JavaScript/preview.js');
    }

    /**
     * Evaluate the activate state based on given $stageArray.
     *
     * @param array $stageArray
     * @return bool
     */
    protected function isInvalidStage($stageArray)
    {
        return !(is_array($stageArray) && !empty($stageArray));
    }

    /**
     * @return void
     */
    public function newPageAction()
    {
        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:info.newpage.detail'), $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:info.newpage'), \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Generates the JavaScript code for the backend,
     * and since we're loading a backend module outside of the actual backend
     * this copies parts of the index.php?M=main module
     *
     * @return string
     */
    protected function generateJavascript()
    {
        $pathTYPO3 = GeneralUtility::dirname(GeneralUtility::getIndpEnv('SCRIPT_NAME')) . '/';
        // If another page module was specified, replace the default Page module with the new one
        $newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
        $pageModule = BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
        if (!$GLOBALS['BE_USER']->check('modules', $pageModule)) {
            $pageModule = '';
        }
        $t3Configuration = [
            'siteUrl' => GeneralUtility::getIndpEnv('TYPO3_SITE_URL'),
            'PATH_typo3' => $pathTYPO3,
            'PATH_typo3_enc' => rawurlencode($pathTYPO3),
            'username' => htmlspecialchars($GLOBALS['BE_USER']->user['username']),
            'uniqueID' => GeneralUtility::shortMD5(uniqid('', true)),
            'securityLevel' => trim($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) ?: 'normal',
            'TYPO3_mainDir' => TYPO3_mainDir,
            'pageModule' => $pageModule,
            'inWorkspace' => $GLOBALS['BE_USER']->workspace !== 0,
            'workspaceFrontendPreviewEnabled' => $GLOBALS['BE_USER']->user['workspace_preview'] ? 1 : 0,
            'veriCode' => $GLOBALS['BE_USER']->veriCode(),
            'denyFileTypes' => PHP_EXTENSIONS_DEFAULT,
            'moduleMenuWidth' => $this->menuWidth - 1,
            'topBarHeight' => isset($GLOBALS['TBE_STYLES']['dims']['topFrameH']) ? (int)$GLOBALS['TBE_STYLES']['dims']['topFrameH'] : 30,
            'showRefreshLoginPopup' => isset($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup']) ? (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] : false,
            'debugInWindow' => $GLOBALS['BE_USER']->uc['debugInWindow'] ? 1 : 0,
            'ContextHelpWindows' => [
                'width' => 600,
                'height' => 400
            ]
        ];
        $t3LLLcore = [
            'waitTitle' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_logging_in'),
            'refresh_login_failed' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_failed'),
            'refresh_login_failed_message' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_failed_message'),
            'refresh_login_title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_title'),
            'login_expired' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.login_expired'),
            'refresh_login_username' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_username'),
            'refresh_login_password' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_password'),
            'refresh_login_emptyPassword' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_emptyPassword'),
            'refresh_login_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_button'),
            'refresh_logout_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_logout_button'),
            'refresh_exit_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_exit_button'),
            'please_wait' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.please_wait'),
            'loadingIndicator' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:loadingIndicator'),
            'be_locked' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.be_locked'),
            'refresh_login_countdown_singular' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_countdown_singular'),
            'refresh_login_countdown' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_countdown'),
            'login_about_to_expire' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.login_about_to_expire'),
            'login_about_to_expire_title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.login_about_to_expire_title'),
            'refresh_login_refresh_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login_refresh_button'),
            'refresh_direct_logout_button' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_direct_logout_button'),
            'tabs_closeAll' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:tabs.closeAll'),
            'tabs_closeOther' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:tabs.closeOther'),
            'tabs_close' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:tabs.close'),
            'tabs_openInBrowserWindow' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:tabs.openInBrowserWindow'),
            'donateWindow_title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:donateWindow.title'),
            'donateWindow_message' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:donateWindow.message'),
            'donateWindow_button_donate' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:donateWindow.button_donate'),
            'donateWindow_button_disable' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:donateWindow.button_disable'),
            'donateWindow_button_postpone' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:donateWindow.button_postpone')
        ];
        return '
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
			this.securityLevel = TYPO3.configuration.securityLevel;
			this.veriCode = TYPO3.configuration.veriCode;
			this.denyFileTypes = TYPO3.configuration.denyFileTypes;
		}
		var TS = new typoSetup();
			//backwards compatibility
		';
    }
}
