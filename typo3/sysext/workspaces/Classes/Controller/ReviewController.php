<?php

declare(strict_types=1);

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class ReviewController
{
    public function __construct(
        protected readonly WorkspaceService $workspaceService,
        protected readonly StagesService $stagesService,
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly TranslationConfigurationProvider $translationConfigurationProvider
    ) {
    }

    /**
     * Renders the review module user dependent with all workspaces.
     * The module will show all records of one workspace.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $pageUid = (int)($queryParams['id'] ?? 0);

        $icons = [
            'language' => $this->iconFactory->getIcon('flags-multiple', Icon::SIZE_SMALL)->render(),
            'integrity' => $this->iconFactory->getIcon('status-dialog-information', Icon::SIZE_SMALL)->render(),
            'success' => $this->iconFactory->getIcon('status-dialog-ok', Icon::SIZE_SMALL)->render(),
            'info' => $this->iconFactory->getIcon('status-dialog-information', Icon::SIZE_SMALL)->render(),
            'warning' => $this->iconFactory->getIcon('status-dialog-warning', Icon::SIZE_SMALL)->render(),
            'error' => $this->iconFactory->getIcon('status-dialog-error', Icon::SIZE_SMALL)->render(),
        ];
        $this->pageRenderer->addInlineSetting('Workspaces', 'icons', $icons);
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        $this->pageRenderer->addInlineSetting('Workspaces', 'id', $pageUid);
        $this->pageRenderer->addInlineSetting('WebLayout', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute(
            trim($this->getBackendUser()->getTSConfig()['options.']['overridePageModule'] ?? 'web_layout')
        ));
        $this->pageRenderer->loadJavaScriptModule('@typo3/workspaces/backend.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/multi-record-selection.js');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');

        $backendUser = $this->getBackendUser();
        $pageTitle = '';
        $pageRecord = [];
        if ($pageUid) {
            $pageRecord = BackendUtility::getRecord('pages', $pageUid);
            if ($pageRecord) {
                $pageTitle = BackendUtility::getRecordTitle('pages', $pageRecord);
            }
        } else {
            $pageTitle = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? '';
        }
        $availableWorkspaces = $this->workspaceService->getAvailableWorkspaces();
        $customWorkspaceExists = $this->customWorkspaceExists($availableWorkspaces);
        $activeWorkspace = (int)$backendUser->workspace;
        $activeWorkspaceTitle = WorkspaceService::getWorkspaceTitle($activeWorkspace);
        $workspaceSwitched = '';
        if (isset($queryParams['workspace'])) {
            $switchWs = (int)$queryParams['workspace'];
            if (array_key_exists($switchWs, $availableWorkspaces) && $activeWorkspace !== $switchWs) {
                $activeWorkspace = $switchWs;
                $backendUser->setWorkspace($activeWorkspace);
                $activeWorkspaceTitle = WorkspaceService::getWorkspaceTitle($activeWorkspace);
                $workspaceSwitched = GeneralUtility::jsonEncodeForHtmlAttribute(['id' => $activeWorkspace, 'title' => $activeWorkspaceTitle]);
            }
        }
        $workspaceIsAccessible = $backendUser->workspace !== WorkspaceService::LIVE_WORKSPACE_ID && $pageUid > 0;

        $view = $this->moduleTemplateFactory->create($request, 'typo3/cms-workspaces');
        $view->assignMultiple([
            'isAdmin' => $backendUser->isAdmin(),
            'customWorkspaceExists' => $customWorkspaceExists,
            'showGrid' => $workspaceIsAccessible,
            'showLegend' => $workspaceIsAccessible,
            'pageUid' => $pageUid,
            'pageTitle' => $pageTitle,
            'activeWorkspaceUid' => $activeWorkspace,
            'activeWorkspaceTitle' => $activeWorkspaceTitle,
            'availableLanguages' => $this->getSystemLanguages($pageUid),
            'availableStages' => $this->stagesService->getStagesForWSUser(),
            'availableSelectStages' => $this->getAvailableSelectStages(),
            'stageActions' => $this->getStageActions(),
            'selectedLanguage' => $this->getLanguageSelection(),
            'selectedDepth' => $this->getDepthSelection($pageUid),
            'selectedStage' => $this->getStageSelection(),
            'workspaceSwitched' => $workspaceSwitched,
        ]);
        $view->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab') . ' [' . $activeWorkspaceTitle . ']',
            $pageTitle
        );
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addWorkspaceSelector($view, $availableWorkspaces, $activeWorkspace, $pageUid);
        $this->addPreviewLink($view, $pageUid, $activeWorkspace);
        $this->addEditWorkspaceRecordButton($view, $pageUid, $activeWorkspace);
        $this->addShortcutButton($view, $activeWorkspaceTitle, $pageTitle, $pageUid);
        return $view->renderResponse('Review/Index');
    }

    /**
     * Create the workspace selection drop-down menu.
     */
    protected function addWorkspaceSelector(ModuleTemplate $view, array $availableWorkspaces, int $activeWorkspace, int $pageUid): void
    {
        $items = [];
        $items[] = [
            'title' => $availableWorkspaces[$activeWorkspace],
            'active' => true,
            'url' => $this->getModuleUri($pageUid),
        ];
        foreach ($availableWorkspaces as $workspaceId => $workspaceTitle) {
            if ($workspaceId === $activeWorkspace) {
                continue;
            }
            $items[] = [
                'title' => $workspaceTitle,
                'active' => false,
                'url' => $this->getModuleUri($pageUid, (int)$workspaceId),
            ];
        }
        $actionMenu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $actionMenu->setIdentifier('workspaceSelector');
        $actionMenu->setLabel('');
        foreach ($items as $workspaceData) {
            $menuItem = $actionMenu
                ->makeMenuItem()
                ->setTitle($workspaceData['title'])
                ->setHref($workspaceData['url']);
            if ($workspaceData['active']) {
                $menuItem->setActive(true);
            }
            $actionMenu->addMenuItem($menuItem);
        }
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($actionMenu);
    }

    protected function addShortcutButton(ModuleTemplate $view, string $activeWorkspaceTitle, string $pageTitle, int $pageId): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_WorkspacesWorkspaces')
            ->setDisplayName(sprintf('%s: %s [%d]', $activeWorkspaceTitle, $pageTitle, $pageId))
            ->setArguments(['id' => (int)$pageId]);
        $buttonBar->addButton($shortcutButton);
    }

    protected function addPreviewLink(ModuleTemplate $view, int $pageUid, int $activeWorkspace): void
    {
        $canCreatePreviewLink = false;
        if ($pageUid > 0 && $activeWorkspace > 0) {
            $pageRecord = BackendUtility::getRecord('pages', $pageUid);
            BackendUtility::workspaceOL('pages', $pageRecord, $activeWorkspace);
            if (!VersionState::cast($pageRecord['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                $canCreatePreviewLink = true;
            }
        }
        if ($canCreatePreviewLink) {
            $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
            $showButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setClasses('t3js-preview-link')
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:tooltip.generatePagePreview'))
                ->setIcon($this->iconFactory->getIcon('actions-version-workspaces-preview-link', Icon::SIZE_SMALL));
            $buttonBar->addButton($showButton);
        }
    }

    protected function addEditWorkspaceRecordButton(ModuleTemplate $view, int $pageUid, int $activeWorkspace): void
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin() && $activeWorkspace > 0) {
            $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
            $editWorkspaceRecordUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'sys_workspace' => [
                        $activeWorkspace => 'edit',
                    ],
                ],
                'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('web_WorkspacesWorkspaces', ['id' => $pageUid]),
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
    }

    protected function getModuleUri(int $pageUid, int $workspaceId = null): string
    {
        $parameters = [
            'id' => $pageUid,
        ];
        if ($workspaceId !== null) {
            $parameters['workspace'] = $workspaceId;
        }
        return (string)$this->uriBuilder->buildUriFromRoute('web_WorkspacesWorkspaces', $parameters);
    }

    protected function getLanguageSelection(): string
    {
        $moduleData = $this->getBackendUser()->getModuleData('workspaces') ?? [];
        return (string)($moduleData['settings']['language'] ?? 'all');
    }

    protected function getDepthSelection(int $pageId): int
    {
        $moduleData = $this->getBackendUser()->getModuleData('workspaces') ?? [];
        return (int)($moduleData['settings']['depth'] ?? ($pageId === 0 ? 999 : 1));
    }

    protected function getStageSelection(): int
    {
        $moduleData = $this->getBackendUser()->getModuleData('workspaces') ?? [];
        return (int)($moduleData['settings']['stage'] ?? -99);
    }

    /**
     * Returns true if at least one custom workspace next to live workspace exists.
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
     * Gets all available system languages.
     */
    protected function getSystemLanguages(int $pageId): array
    {
        $languages = $this->translationConfigurationProvider->getSystemLanguages($pageId);
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
        $languageService = $this->getLanguageService();
        $currentWorkspace = $this->workspaceService->getCurrentWorkspace();
        $backendUser = $this->getBackendUser();
        $actions = [];
        $massActionsEnabled = (bool)($backendUser->getTSConfig()['options.']['workspaces.']['enableMassActions'] ?? true);
        if ($massActionsEnabled) {
            $publishAccess = $backendUser->workspacePublishAccess($currentWorkspace);
            if ($publishAccess && !(($backendUser->workspaceRec['publish_access'] ?? 0) & 1)) {
                $actions[] = ['action' => 'publish', 'title' => $languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:label_doaction_publish')];
            }
            if ($currentWorkspace !== WorkspaceService::LIVE_WORKSPACE_ID) {
                $actions[] = ['action' => 'discard', 'title' => $languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:label_doaction_discard')];
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
        $languageService = $this->getLanguageService();
        $stages = $this->stagesService->getStagesForWSUser();
        return array_merge([
            [
                'uid' => -99,
                'label' => $languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod_user_ws.xlf:stage_all'),
            ],
        ], array_filter($stages, static fn (array $stage): bool => (int)($stage['uid'] ?? 0) !== -20));
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
