<?php

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

namespace TYPO3\CMS\Workspaces\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class ReviewController
{
    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var int
     */
    protected $pageId;

    protected WorkspaceService $workspaceService;
    protected StagesService $stagesService;
    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        WorkspaceService $workspaceService,
        StagesService $stagesService,
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->workspaceService = $workspaceService;
        $this->stagesService = $stagesService;
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Initializes the controller before invoking an action method.
     */
    protected function initializeAction()
    {
        $icons = [
            'language' => $this->iconFactory->getIcon('flags-multiple', Icon::SIZE_SMALL)->render(),
            'integrity' => $this->iconFactory->getIcon('status-dialog-information', Icon::SIZE_SMALL)->render(),
            'success' => $this->iconFactory->getIcon('status-dialog-ok', Icon::SIZE_SMALL)->render(),
            'info' => $this->iconFactory->getIcon('status-dialog-information', Icon::SIZE_SMALL)->render(),
            'warning' => $this->iconFactory->getIcon('status-dialog-warning', Icon::SIZE_SMALL)->render(),
            'error' => $this->iconFactory->getIcon('status-dialog-error', Icon::SIZE_SMALL)->render(),
        ];
        $this->pageRenderer->addInlineSetting('Workspaces', 'icons', $icons);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Backend');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/MultiRecordSelection');
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        $this->pageRenderer->addInlineSetting('Workspaces', 'id', $this->pageId);
        $this->pageRenderer->addInlineSetting('WebLayout', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute(
            trim($this->getBackendUser()->getTSConfig()['options.']['overridePageModule'] ?? 'web_layout')
        ));
    }

    /**
     * Renders the review module user dependent with all workspaces.
     * The module will show all records of one workspace.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $queryParams = $request->getQueryParams();
        $this->pageId = (int)($queryParams['id'] ?? 0);

        $this->initializeAction();

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate('Index');
        // This is only needed for translate VH to resolve 'label only' to default locallang.xlf files
        $this->view->getRequest()->setControllerExtensionName('Workspaces');
        $this->view->setTemplateRootPaths(['EXT:workspaces/Resources/Private/Templates/Review']);
        $this->view->setPartialRootPaths(['EXT:workspaces/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:workspaces/Resources/Private/Layouts']);

        $backendUser = $this->getBackendUser();
        $pageTitle = '';

        if ($this->pageId) {
            $pageRecord = BackendUtility::getRecord('pages', $this->pageId);
            if ($pageRecord) {
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageRecord);
                $pageTitle = BackendUtility::getRecordTitle('pages', $pageRecord);
            }
        }
        $availableWorkspaces = $this->workspaceService->getAvailableWorkspaces();
        $customWorkspaceExists = $this->customWorkspaceExists($availableWorkspaces);
        $activeWorkspace = (int)$backendUser->workspace;
        $activeWorkspaceTitle = WorkspaceService::getWorkspaceTitle($activeWorkspace);
        if (isset($queryParams['workspace'])) {
            $switchWs = (int)$queryParams['workspace'];
            if (array_key_exists($switchWs, $availableWorkspaces) && $activeWorkspace !== $switchWs) {
                $activeWorkspace = $switchWs;
                $backendUser->setWorkspace($activeWorkspace);
                $activeWorkspaceTitle = WorkspaceService::getWorkspaceTitle($activeWorkspace);
                $this->view->assign('workspaceSwitched', GeneralUtility::jsonEncodeForHtmlAttribute(['id' => $activeWorkspace, 'title' => $activeWorkspaceTitle]));
            }
        }
        $workspaceIsAccessible = $backendUser->workspace !== WorkspaceService::LIVE_WORKSPACE_ID;

        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab') . ' [' . $activeWorkspaceTitle . ']',
            $pageTitle
        );

        $this->view->assignMultiple([
            'isAdmin' => $backendUser->isAdmin(),
            'customWorkspaceExists' => $customWorkspaceExists,
            'showGrid' => $workspaceIsAccessible,
            'showLegend' => $workspaceIsAccessible,
            'pageUid' => $this->pageId,
            'pageTitle' => $pageTitle,
            'activeWorkspaceUid' => $activeWorkspace,
            'activeWorkspaceTitle' => $activeWorkspaceTitle,
            'availableLanguages' => $this->getSystemLanguages($this->pageId),
            'availableStages' => $this->stagesService->getStagesForWSUser(),
            'availableSelectStages' => $this->getAvailableSelectStages(),
            'stageActions' => $this->getStageActions(),
            'selectedLanguage' => $this->getLanguageSelection(),
            'selectedDepth' => $this->getDepthSelection(),
            'selectedStage' => $this->getStageSelection(),
        ]);

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->canCreatePreviewLink($this->pageId, $activeWorkspace)) {
            $showButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setClasses('t3js-preview-link')
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:tooltip.generatePagePreview'))
                ->setIcon($this->iconFactory->getIcon('actions-version-workspaces-preview-link', Icon::SIZE_SMALL));
            $buttonBar->addButton($showButton);
        }

        if ($backendUser->isAdmin() && $activeWorkspace) {
            $editWorkspaceRecordUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'sys_workspace' => [
                        $activeWorkspace => 'edit',
                    ],
                ],
                'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('web_WorkspacesWorkspaces', ['id' => $this->pageId]),
            ]);
            $editSettingsButton = $buttonBar->makeLinkButton()
                ->setHref($editWorkspaceRecordUrl)
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:button.editWorkspaceSettings'))
                ->setIcon($this->iconFactory->getIcon('actions-cog-alt', Icon::SIZE_SMALL));
            $buttonBar->addButton(
                $editSettingsButton,
                ButtonBar::BUTTON_POSITION_LEFT,
                90
            );
        }

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_WorkspacesWorkspaces')
            ->setDisplayName(sprintf('%s: %s [%d]', $activeWorkspaceTitle, $pageTitle, $this->pageId))
            ->setArguments(['id' => (int)$this->pageId]);
        $buttonBar->addButton($shortcutButton);

        $this->makeActionMenu($this->prepareWorkspaceTabs($availableWorkspaces, $activeWorkspace));

        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * This creates the dropdown menu with the different actions this module is able to provide.
     *
     * @param array $availableWorkspaces array with the available actions
     */
    protected function makeActionMenu(array $availableWorkspaces): void
    {
        $actionMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $actionMenu->setIdentifier('workspaceSelector');
        $actionMenu->setLabel('');
        foreach ($availableWorkspaces as $workspaceData) {
            $menuItem = $actionMenu
                ->makeMenuItem()
                ->setTitle($workspaceData['title'])
                ->setHref($workspaceData['url']);
            if ($workspaceData['active']) {
                $menuItem->setActive(true);
            }
            $actionMenu->addMenuItem($menuItem);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($actionMenu);
    }

    /**
     * Prepares available workspace tabs.
     *
     * @param array $workspaceList
     * @param int $activeWorkspace
     * @return array
     */
    protected function prepareWorkspaceTabs(array $workspaceList, int $activeWorkspace)
    {
        $tabs = [];
        $tabs[] = [
            'title' => $workspaceList[$activeWorkspace],
            'itemId' => 'workspace-' . $activeWorkspace,
            'active' => true,
            'url' => $this->getModuleUri(),
        ];

        foreach ($workspaceList as $workspaceId => $workspaceTitle) {
            if ($workspaceId === $activeWorkspace) {
                continue;
            }
            $tabs[] = [
                'title' => $workspaceTitle,
                'itemId' => 'workspace-' . $workspaceId,
                'active' => false,
                'url' => $this->getModuleUri((int)$workspaceId),
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
    protected function getModuleUri(int $workspaceId = null): string
    {
        $parameters = [
            'id' => $this->pageId,
        ];
        if ($workspaceId !== null) {
            $parameters['workspace'] = $workspaceId;
        }
        return (string)$this->uriBuilder->buildUriFromRoute('web_WorkspacesWorkspaces', $parameters);
    }

    /**
     * Determine whether this page for the current
     *
     * @param int $pageUid
     * @param int $workspaceUid
     * @return bool
     */
    protected function canCreatePreviewLink(int $pageUid, int $workspaceUid): bool
    {
        if ($pageUid > 0 && $workspaceUid > 0) {
            $pageRecord = BackendUtility::getRecord('pages', $pageUid);
            BackendUtility::workspaceOL('pages', $pageRecord, $workspaceUid);
            if (VersionState::cast($pageRecord['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Gets the selected language.
     *
     * @return string
     */
    protected function getLanguageSelection(): string
    {
        $moduleData = $this->getBackendUser()->getModuleData('workspaces') ?? [];
        return (string)($moduleData['settings']['language'] ?? 'all');
    }

    protected function getDepthSelection(): int
    {
        $moduleData = $this->getBackendUser()->getModuleData('workspaces') ?? [];
        return (int)($moduleData['settings']['depth'] ?? ($this->pageId === 0 ? 999 : 1));
    }

    protected function getStageSelection(): int
    {
        $moduleData = $this->getBackendUser()->getModuleData('workspaces') ?? [];
        return (int)($moduleData['settings']['stage'] ?? -99);
    }

    /**
     * Returns true if at least one custom workspace next to live workspace exists.
     *
     * @param array $workspaceList
     * @return bool
     */
    protected function customWorkspaceExists(array $workspaceList): bool
    {
        foreach (array_keys($workspaceList) as $workspaceId) {
            if ($workspaceId > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Gets all available system languages.
     *
     * @param int $pageId
     * @return array
     */
    protected function getSystemLanguages(int $pageId): array
    {
        $languages = GeneralUtility::makeInstance(TranslationConfigurationProvider::class)->getSystemLanguages($pageId);
        if (isset($languages[-1])) {
            $languages[-1]['uid'] = 'all';
        }
        $activeLanguage = $this->getLanguageSelection();
        foreach ($languages as &$language) {
            // needs to be strict type checking as this is not possible in fluid
            if ((string)$language['uid'] === $activeLanguage) {
                $language['active'] = true;
            }
        }
        return $languages;
    }

    /**
     * Get list of available mass workspace actions.
     */
    protected function getStageActions(): array
    {
        $actions = [];
        $currentWorkspace = $this->workspaceService->getCurrentWorkspace();
        $backendUser = $this->getBackendUser();
        $massActionsEnabled = (bool)($backendUser->getTSConfig()['options.']['workspaces.']['enableMassActions'] ?? true);
        if ($massActionsEnabled) {
            $publishAccess = $backendUser->workspacePublishAccess($currentWorkspace);
            if ($publishAccess && !(($backendUser->workspaceRec['publish_access'] ?? 0) & 1)) {
                $actions[] = ['action' => 'publish', 'title' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:label_doaction_publish')];
            }
            if ($currentWorkspace !== WorkspaceService::LIVE_WORKSPACE_ID) {
                $actions[] = ['action' => 'discard', 'title' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:label_doaction_discard')];
            }
        }
        return $actions;
    }

    /**
     * Get stages to be used in the review filter. This basically
     * adds -99 (all stages) and removes -20 (publish).
     */
    protected function getAvailableSelectStages(): array
    {
        $stages = $this->stagesService->getStagesForWSUser();

        return array_merge([
            [
                'uid' => -99,
                'label' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod_user_ws.xlf:stage_all'),
            ],
        ], array_filter($stages, static fn (array $stage): bool => (int)($stage['uid'] ?? 0) !== -20));
    }
}
