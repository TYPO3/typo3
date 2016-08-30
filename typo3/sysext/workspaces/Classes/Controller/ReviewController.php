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
use TYPO3\CMS\Core\Imaging\Icon;
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
            $getVars = ['id', 'M', $modulePrefix];
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
        /** @var WorkspaceService $wsService */
        $wsService = GeneralUtility::makeInstance(WorkspaceService::class);
        $this->view->assign('showGrid', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));
        $this->view->assign('showAllWorkspaceTab', true);
        $this->view->assign('pageUid', GeneralUtility::_GP('id'));
        if (GeneralUtility::_GP('id')) {
            $pageRecord = BackendUtility::getRecord('pages', GeneralUtility::_GP('id'));
            if ($pageRecord) {
                $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($pageRecord);
                $this->view->assign('pageTitle', BackendUtility::getRecordTitle('pages', $pageRecord));
            }
        }
        $this->view->assign('showLegend', !($GLOBALS['BE_USER']->workspace === 0 && !$GLOBALS['BE_USER']->isAdmin()));
        $wsList = $wsService->getAvailableWorkspaces();
        $activeWorkspace = $GLOBALS['BE_USER']->workspace;
        $performWorkspaceSwitch = false;
        // Only admins see multiple tabs, we decided to use it this
        // way for usability reasons. Regular users might be confused
        // by switching workspaces with the tabs in a module.
        if (!$GLOBALS['BE_USER']->isAdmin()) {
            $wsCur = [$activeWorkspace => true];
            $wsList = array_intersect_key($wsList, $wsCur);
        } else {
            if ((string)GeneralUtility::_GP('workspace') !== '') {
                $switchWs = (int)GeneralUtility::_GP('workspace');
                if (in_array($switchWs, array_keys($wsList)) && $activeWorkspace != $switchWs) {
                    $activeWorkspace = $switchWs;
                    $GLOBALS['BE_USER']->setWorkspace($activeWorkspace);
                    $performWorkspaceSwitch = true;
                    BackendUtility::setUpdateSignal('updatePageTree');
                } elseif ($switchWs == WorkspaceService::SELECT_ALL_WORKSPACES) {
                    $this->redirect('fullIndex');
                }
            }
        }
        $this->pageRenderer->addInlineSetting('Workspaces', 'isLiveWorkspace', (int)$GLOBALS['BE_USER']->workspace === 0);
        $this->pageRenderer->addInlineSetting('Workspaces', 'workspaceTabs', $this->prepareWorkspaceTabs($wsList, $activeWorkspace));
        $this->pageRenderer->addInlineSetting('Workspaces', 'activeWorkspaceId', $activeWorkspace);
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', BackendUtility::getModuleUrl('record_edit'));
        $this->view->assign('performWorkspaceSwitch', $performWorkspaceSwitch);
        $this->view->assign('workspaceList', $wsList);
        $this->view->assign('activeWorkspaceUid', $activeWorkspace);
        $this->view->assign('activeWorkspaceTitle', WorkspaceService::getWorkspaceTitle($activeWorkspace));
        if ($wsService->canCreatePreviewLink(GeneralUtility::_GP('id'), $activeWorkspace)) {
            $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
            $iconFactory = $this->view->getModuleTemplate()->getIconFactory();
            $showButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setOnClick('TYPO3.Workspaces.Actions.generateWorkspacePreviewLinksForAllLanguages();return false;')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:tooltip.generatePagePreview'))
                ->setIcon($iconFactory->getIcon('module-workspaces-action-preview-link', Icon::SIZE_SMALL));
            $buttonBar->addButton($showButton);
        }
        $this->view->assign('showPreviewLink', $wsService->canCreatePreviewLink(GeneralUtility::_GP('id'), $activeWorkspace));
        $GLOBALS['BE_USER']->setAndSaveSessionData('tx_workspace_activeWorkspace', $activeWorkspace);
    }

    /**
     * Renders the review module user dependent.
     * The module will show all records of all workspaces.
     *
     * @return void
     */
    public function fullIndexAction()
    {
        $wsService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        $wsList = $wsService->getAvailableWorkspaces();

        if (!$GLOBALS['BE_USER']->isAdmin()) {
            $activeWorkspace = $GLOBALS['BE_USER']->workspace;
            $wsCur = [$activeWorkspace => true];
            $wsList = array_intersect_key($wsList, $wsCur);
        }

        $this->pageRenderer->addInlineSetting('Workspaces', 'workspaceTabs', $this->prepareWorkspaceTabs($wsList, WorkspaceService::SELECT_ALL_WORKSPACES));
        $this->pageRenderer->addInlineSetting('Workspaces', 'activeWorkspaceId', WorkspaceService::SELECT_ALL_WORKSPACES);
        $this->view->assign('pageUid', GeneralUtility::_GP('id'));
        $this->view->assign('showGrid', true);
        $this->view->assign('showLegend', true);
        $this->view->assign('showAllWorkspaceTab', true);
        $this->view->assign('workspaceList', $wsList);
        $this->view->assign('activeWorkspaceUid', WorkspaceService::SELECT_ALL_WORKSPACES);
        $this->view->assign('showPreviewLink', false);
        $GLOBALS['BE_USER']->setAndSaveSessionData('tx_workspace_activeWorkspace', WorkspaceService::SELECT_ALL_WORKSPACES);
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
        $wsService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        $wsList = $wsService->getAvailableWorkspaces();
        $activeWorkspace = $GLOBALS['BE_USER']->workspace;
        $wsCur = [$activeWorkspace => true];
        $wsList = array_intersect_key($wsList, $wsCur);
        $backendDomain = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        $this->view->assign('pageUid', GeneralUtility::_GP('id'));
        $this->view->assign('showGrid', true);
        $this->view->assign('showAllWorkspaceTab', false);
        $this->view->assign('workspaceList', $wsList);
        $this->view->assign('backendDomain', $backendDomain);
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
            $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:warning.oldStyleWorkspaceInUser'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        $this->pageRenderer->loadExtJS();
        $states = $GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['States'];
        $this->pageRenderer->addInlineSetting('Workspaces', 'States', $states);
        // Load  JavaScript:
        $this->pageRenderer->addExtDirectCode([
            'TYPO3.Workspaces'
        ]);
        $this->pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/extjs/ux/Ext.grid.RowExpander.js');
        $this->pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/extjs/ux/Ext.app.SearchField.js');
        $this->pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/extjs/ux/Ext.ux.FitToParent.js');
        $resourcePath = ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/JavaScript/';

        // @todo Integrate additional stylesheet resources
        $this->pageRenderer->addCssFile($resourcePath . 'gridfilters/css/GridFilters.css');
        $this->pageRenderer->addCssFile($resourcePath . 'gridfilters/css/RangeMenu.css');

        $filters = [
            $resourcePath . 'gridfilters/menu/RangeMenu.js',
            $resourcePath . 'gridfilters/menu/ListMenu.js',
            $resourcePath . 'gridfilters/GridFilters.js',
            $resourcePath . 'gridfilters/filter/Filter.js',
            $resourcePath . 'gridfilters/filter/StringFilter.js',
            $resourcePath . 'gridfilters/filter/DateFilter.js',
            $resourcePath . 'gridfilters/filter/ListFilter.js',
            $resourcePath . 'gridfilters/filter/NumericFilter.js',
            $resourcePath . 'gridfilters/filter/BooleanFilter.js',
            $resourcePath . 'gridfilters/filter/BooleanFilter.js',
        ];

        $custom = $this->getAdditionalResourceService()->getJavaScriptResources();

        $resources = [
            $resourcePath . 'Component/RowDetailTemplate.js',
            $resourcePath . 'Component/RowExpander.js',
            $resourcePath . 'Component/TabPanel.js',
            $resourcePath . 'Store/mainstore.js',
            $resourcePath . 'configuration.js',
            $resourcePath . 'helpers.js',
            $resourcePath . 'actions.js',
            $resourcePath . 'component.js',
            $resourcePath . 'toolbar.js',
            $resourcePath . 'grid.js',
            $resourcePath . 'workspaces.js'
        ];

        $javaScriptFiles = array_merge($filters, $custom, $resources);

        foreach ($javaScriptFiles as $javaScriptFile) {
            $this->pageRenderer->addJsFile($javaScriptFile);
        }
        foreach ($this->getAdditionalResourceService()->getLocalizationResources() as $localizationResource) {
            $this->pageRenderer->addInlineLanguageLabelFile($localizationResource);
        }
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', BackendUtility::getModuleUrl('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', BackendUtility::getModuleUrl('record_history'));
    }

    /**
     * Prepares available workspace tabs.
     *
     * @param array $workspaceList
     * @param int $activeWorkspace
     * @return array
     */
    protected function prepareWorkspaceTabs(array $workspaceList, $activeWorkspace)
    {
        $tabs = [];

        if ($activeWorkspace !== WorkspaceService::SELECT_ALL_WORKSPACES) {
            $tabs[] = [
                'title' => $workspaceList[$activeWorkspace],
                'itemId' => 'workspace-' . $activeWorkspace,
                'workspaceId' => $activeWorkspace,
                'triggerUrl' => $this->getModuleUri($activeWorkspace),
            ];
        }

        $tabs[] = [
            'title' => 'All workspaces',
            'itemId' => 'workspace-' . WorkspaceService::SELECT_ALL_WORKSPACES,
            'workspaceId' => WorkspaceService::SELECT_ALL_WORKSPACES,
            'triggerUrl' => $this->getModuleUri(WorkspaceService::SELECT_ALL_WORKSPACES),
        ];

        foreach ($workspaceList as $workspaceId => $workspaceTitle) {
            if ($workspaceId === $activeWorkspace) {
                continue;
            }
            $tabs[] = [
                'title' => $workspaceTitle,
                'itemId' => 'workspace-' . $workspaceId,
                'workspaceId' => $workspaceId,
                'triggerUrl' => $this->getModuleUri($workspaceId),
            ];
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
        $parameters = [
            'id' => (int)$this->pageId,
            'workspace' => (int)$workspaceId,
        ];
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
}
