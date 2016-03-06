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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Review controller.
 */
class ReviewController extends AbstractController
{
    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $this->registerButtons();
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
    }

    /**
     * Registers the DocHeader buttons
     */
    protected function registerButtons()
    {
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();
        $extensionName = $currentRequest->getControllerExtensionName();
        if (count($getVars) === 0) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = array('id', 'M', $modulePrefix);
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Renders the review module user dependent with all workspaces.
     * The module will show all records of one workspace.
     *
     * @return void
     */
    public function indexAction()
    {
        $backendUser = $this->getBackendUser();
        $moduleTemplate = $this->view->getModuleTemplate();

        /** @var WorkspaceService $wsService */
        $wsService = GeneralUtility::makeInstance(WorkspaceService::class);
        if (GeneralUtility::_GP('id')) {
            $pageRecord = BackendUtility::getRecord('pages', GeneralUtility::_GP('id'));
            if ($pageRecord) {
                $moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageRecord);
                $this->view->assign('pageTitle', BackendUtility::getRecordTitle('pages', $pageRecord));
            }
        }
        $wsList = $wsService->getAvailableWorkspaces();
        $activeWorkspace = $backendUser->workspace;
        $performWorkspaceSwitch = false;
        // Only admins see multiple tabs, we decided to use it this
        // way for usability reasons. Regular users might be confused
        // by switching workspaces with the tabs in a module.
        if (!$backendUser->isAdmin()) {
            $wsCur = array($activeWorkspace => true);
            $wsList = array_intersect_key($wsList, $wsCur);
        } else {
            if ((string)GeneralUtility::_GP('workspace') !== '') {
                $switchWs = (int)GeneralUtility::_GP('workspace');
                if (in_array($switchWs, array_keys($wsList)) && $activeWorkspace != $switchWs) {
                    $activeWorkspace = $switchWs;
                    $backendUser->setWorkspace($activeWorkspace);
                    $performWorkspaceSwitch = true;
                    BackendUtility::setUpdateSignal('updatePageTree');
                } elseif ($switchWs == WorkspaceService::SELECT_ALL_WORKSPACES) {
                    $this->redirect('fullIndex');
                }
            }
        }
        $this->pageRenderer->addInlineSetting('Workspaces', 'isLiveWorkspace', (int)$backendUser->workspace === 0);
        $this->pageRenderer->addInlineSetting('Workspaces', 'workspaceTabs', $this->prepareWorkspaceTabs($wsList, $activeWorkspace));
        $this->pageRenderer->addInlineSetting('Workspaces', 'activeWorkspaceId', $activeWorkspace);
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', BackendUtility::getModuleUrl('record_edit'));
        $workspaceIsAccessible = !($backendUser->workspace === 0 && !$backendUser->isAdmin());
        $this->view->assignMultiple([
            'showGrid' => $workspaceIsAccessible,
            'showLegend' => $workspaceIsAccessible,
            'pageUid' => (int)GeneralUtility::_GP('id'),
            'performWorkspaceSwitch' => $performWorkspaceSwitch,
            'workspaceList' => $this->prepareWorkspaceTabs($wsList, $activeWorkspace),
            'activeWorkspaceUid' => $activeWorkspace,
            'activeWorkspaceTitle' => WorkspaceService::getWorkspaceTitle($activeWorkspace),
            'showPreviewLink' => $wsService->canCreatePreviewLink(GeneralUtility::_GP('id'), $activeWorkspace)
        ]);

        if ($wsService->canCreatePreviewLink(GeneralUtility::_GP('id'), $activeWorkspace)) {
            $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
            $iconFactory = $moduleTemplate->getIconFactory();
            $showButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setClasses('t3js-preview-link')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:tooltip.generatePagePreview'))
                ->setIcon($iconFactory->getIcon('module-workspaces-action-preview-link', Icon::SIZE_SMALL));
            $buttonBar->addButton($showButton);
        }
        $backendUser->setAndSaveSessionData('tx_workspace_activeWorkspace', $activeWorkspace);
    }

    /**
     * Renders the review module user dependent.
     * The module will show all records of all workspaces.
     *
     * @return void
     */
    public function fullIndexAction()
    {
        $wsService = GeneralUtility::makeInstance(WorkspaceService::class);
        $wsList = $wsService->getAvailableWorkspaces();

        $activeWorkspace = $this->getBackendUser()->workspace;
        if (!$this->getBackendUser()->isAdmin()) {
            $wsCur = array($activeWorkspace => true);
            $wsList = array_intersect_key($wsList, $wsCur);
        }

        $this->pageRenderer->addInlineSetting('Workspaces', 'workspaceTabs', $this->prepareWorkspaceTabs($wsList, WorkspaceService::SELECT_ALL_WORKSPACES));
        $this->pageRenderer->addInlineSetting('Workspaces', 'activeWorkspaceId', WorkspaceService::SELECT_ALL_WORKSPACES);
        $this->view->assignMultiple([
            'pageUid' => (int)GeneralUtility::_GP('id'),
            'showGrid' => true,
            'showLegend' => true,
            'workspaceList' => $this->prepareWorkspaceTabs($wsList, $activeWorkspace),
            'activeWorkspaceUid' => WorkspaceService::SELECT_ALL_WORKSPACES,
            'showPreviewLink', false
        ]);
        $this->getBackendUser()->setAndSaveSessionData('tx_workspace_activeWorkspace', WorkspaceService::SELECT_ALL_WORKSPACES);
        // set flag for javascript
        $this->pageRenderer->addInlineSetting('Workspaces', 'allView', '1');
    }

    /**
     * Renders the review module for a single page. This is used within the
     * workspace-preview frame.
     *
     * @return void
     */
    public function singleIndexAction()
    {
        $wsService = GeneralUtility::makeInstance(WorkspaceService::class);
        $wsList = $wsService->getAvailableWorkspaces();
        $activeWorkspace = $this->getBackendUser()->workspace;
        $wsCur = array($activeWorkspace => true);
        $wsList = array_intersect_key($wsList, $wsCur);
        $backendDomain = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        $this->view->assignMultiple([
            'pageUid' => (int)GeneralUtility::_GP('id'),
            'showGrid' => true,
            'workspaceList' => $this->prepareWorkspaceTabs($wsList, $activeWorkspace, false),
            'activeWorkspaceUid' => $activeWorkspace,
            'backendDomain' => $backendDomain
        ]);
        // Setting the document.domain early before JavScript
        // libraries are loaded, try to access top frame reference
        // and possibly run into some CORS issue
        $this->pageRenderer->setMetaCharsetTag(
            $this->pageRenderer->getMetaCharsetTag() . LF
            . GeneralUtility::wrapJS('document.domain = ' . GeneralUtility::quoteJSvalue($backendDomain) . ';')
        );
        $this->pageRenderer->addInlineSetting('Workspaces', 'singleView', '1');
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
        $this->pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/ExtDirect.StateProvider.js');
        if (WorkspaceService::isOldStyleWorkspaceUsed()) {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:warning.oldStyleWorkspaceInUser'), '', FlashMessage::WARNING);
            /** @var $flashMessageService FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        $this->pageRenderer->loadExtJS(false, false);
        $states = $this->getBackendUser()->uc['moduleData']['Workspaces']['States'];
        $this->pageRenderer->addInlineSetting('Workspaces', 'States', $states);
        // Load  JavaScript:
        $this->pageRenderer->addExtDirectCode(array(
            'TYPO3.Workspaces'
        ));

        foreach ($this->getAdditionalResourceService()->getLocalizationResources() as $localizationResource) {
            $this->pageRenderer->addInlineLanguageLabelFile($localizationResource);
        }
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Backend');
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', BackendUtility::getModuleUrl('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', BackendUtility::getModuleUrl('record_history'));
        $this->pageRenderer->addInlineSetting('Workspaces', 'token', FormProtectionFactory::get('backend')->generateToken('extDirect'));
        $this->pageRenderer->addInlineSetting('Workspaces', 'id', (int)GeneralUtility::_GP('id'));
    }

    /**
     * Prepares available workspace tabs.
     *
     * @param array $workspaceList
     * @param int $activeWorkspace
     * @param bool $showAllWorkspaceTab
     * @return array
     */
    protected function prepareWorkspaceTabs(array $workspaceList, $activeWorkspace, $showAllWorkspaceTab = true)
    {
        $tabs = array();

        if ($activeWorkspace !== WorkspaceService::SELECT_ALL_WORKSPACES) {
            $tabs[] = array(
                'title' => $workspaceList[$activeWorkspace],
                'itemId' => 'workspace-' . $activeWorkspace,
                'workspaceId' => $activeWorkspace,
                'triggerUrl' => $this->getModuleUri($activeWorkspace),
            );
        }

        if ($showAllWorkspaceTab) {
            $tabs[] = array(
                'title' => 'All workspaces',
                'itemId' => 'workspace-' . WorkspaceService::SELECT_ALL_WORKSPACES,
                'workspaceId' => WorkspaceService::SELECT_ALL_WORKSPACES,
                'triggerUrl' => $this->getModuleUri(WorkspaceService::SELECT_ALL_WORKSPACES),
            );
        }

        foreach ($workspaceList as $workspaceId => $workspaceTitle) {
            if ($workspaceId === $activeWorkspace) {
                continue;
            }
            $tabs[] = array(
                'title' => $workspaceTitle,
                'itemId' => 'workspace-' . $workspaceId,
                'workspaceId' => $workspaceId,
                'triggerUrl' => $this->getModuleUri($workspaceId),
            );
        }

        return $tabs;
    }

    /**
     * Gets the module URI.
     *
     * @param int $workspaceId
     * @return string
     */
    protected function getModuleUri($workspaceId)
    {
        $parameters = array(
            'id' => (int)$this->pageId,
            'workspace' => (int)$workspaceId,
        );
        // The "all workspaces" tab is handled in fullIndexAction
        // which is required as additional GET parameter in the URI then
        if ($workspaceId === WorkspaceService::SELECT_ALL_WORKSPACES) {
            $this->uriBuilder->reset()->uriFor('fullIndex');
            $parameters = array_merge($parameters, $this->uriBuilder->getArguments());
        }
        return BackendUtility::getModuleUrl('web_WorkspacesWorkspaces', $parameters);
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
