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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Service\PageLinkMessageProvider;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\LanguageSelectorBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\LanguageSelectorMode;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Backend\View\PageViewMode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * The Content > Layout module.
 *
 * @internal This class is not part of the TYPO3 API.
 */
#[AsController]
class PageLayoutController
{
    protected PageContext $pageContext;
    protected ?TcaSchema $schema = null;
    protected ?ModuleData $moduleData = null;

    public function __construct(
        protected readonly ComponentFactory $componentFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly PageRepository $pageRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly BackendLayoutRenderer $backendLayoutRenderer,
        protected readonly BackendLayoutView $backendLayoutView,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly ConnectionPool $connectionPool,
        protected readonly LanguageSelectorBuilder $languageSelectorBuilder,
        protected readonly PageLinkMessageProvider $pageLinkMessageProvider,
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $pageContext = $request->getAttribute('pageContext');
        if (!$pageContext instanceof PageContext) {
            throw new \RuntimeException('Required PageContext not available', 1731415237);
        }
        $this->pageContext = $pageContext;

        $languageService = $this->getLanguageService();
        $view = $this->moduleTemplateFactory->create($request);
        if (!$this->pageContext->isAccessible() || $this->pageContext->pageId === 0) {
            // In case page could not be resolved or we are on pid=0, show info to select a valid page in the tree
            $view->setTitle($languageService->translate('title', 'backend.modules.layout'));
            $view->assignMultiple([
                'pageId' => $this->pageContext->pageId,
                'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? '',
            ]);
            $view->getDocHeaderComponent()->disableAutomaticReloadButton();
            return $view->renderResponse('PageLayout/PageModuleNoAccess');
        }

        $this->moduleData = $request->getAttribute('moduleData');
        $this->schema = $this->tcaSchemaFactory->get('pages');

        $pageLayoutContext = $this->createPageLayoutContext($request);

        $this->updateModuleData();
        $this->createViewModeSelection($view);
        $this->addButtonsToButtonBar($view, $request);
        $this->initializeClipboard($request);
        $event = $this->eventDispatcher->dispatch(new ModifyPageLayoutContentEvent($request, $view));

        $mainLayoutHtml = $this->backendLayoutRenderer->drawContent($request, $pageLayoutContext);
        $primaryLanguageId = $this->pageContext->getPrimaryLanguageId();
        $pageLocalizationRecord = $this->pageContext->languageInformation->getTranslationRecord($primaryLanguageId);

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/page-actions.js')
        );

        $view->setTitle($languageService->translate('title', 'backend.modules.layout'), $this->pageContext->getPageTitle());
        $view->getDocHeaderComponent()->setPageBreadcrumb($this->pageContext->pageRecord);
        $view->assignMultiple([
            'pageId' => $this->pageContext->pageId,
            'localizedPageId' => $pageLocalizationRecord['uid'] ?? 0,
            'pageLayoutContext' => $pageLayoutContext,
            'infoBoxes' => $this->generateMessagesForCurrentPage($request),
            'isPageEditable' => $this->isPageEditable($primaryLanguageId),
            'localizedPageTitle' => $this->pageContext->getPageTitle($primaryLanguageId),
            'eventContentHtmlTop' => $event->getHeaderContent(),
            'mainContentHtml' => $mainLayoutHtml,
            'eventContentHtmlBottom' => $event->getFooterContent(),
        ]);
        return $view->renderResponse('PageLayout/PageModule');
    }

    protected function updateModuleData(): void
    {
        $backendUser = $this->getBackendUser();
        if (PageViewMode::tryFrom((int)$this->moduleData->get('viewMode')) === null) {
            // Invalid function, reset to default
            $this->moduleData->set('viewMode', PageViewMode::LayoutView->value);
        }
        if ($backendUser->workspace !== 0) {
            // In draft workspaces, always show all elements (including hidden)
            $this->moduleData->set('showHidden', true);
        }

        // Store selected languages in module data for persistence
        $this->moduleData->set('languages', $this->pageContext->selectedLanguageIds);

        // Write module data
        $backendUser->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
    }

    /**
     * Creates the menu dropdown for switching between view modes.
     *
     * Available actions:
     * - LayoutView: Single-language content editing (always available)
     * - LanguageComparisonView: Multi-language comparison (only if translations exist)
     *
     * Smart Language Selection:
     * When building URLs for mode switching, this method implements smart language selection:
     * - Switching to layout mode with 1 translation selected → keep that translation
     * - Switching to layout mode with 2+ translations selected → show default
     */
    protected function createViewModeSelection(ModuleTemplate $view): void
    {
        $languageService = $this->getLanguageService();
        $modes = [
            PageViewMode::LayoutView->value => $languageService->sL(PageViewMode::LayoutView->getLabel()),
        ];

        // Only show comparison mode if page has translations
        if (!empty($this->pageContext->languageInformation->existingTranslations)) {
            $modes[PageViewMode::LanguageComparisonView->value] = $languageService->sL(PageViewMode::LanguageComparisonView->getLabel());
        }

        // Apply TSconfig blinding
        $moduleTsConfig = $this->pageContext->getModuleTsConfig('web_layout');
        $blindActions = $moduleTsConfig['menu']['functions'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $modes)) {
                unset($modes[$key]);
            }
        }

        // Only create menu if there are multiple actions to choose from
        if (count($modes) <= 1) {
            if (count($modes) === 1) {
                $this->moduleData->set('viewMode', array_key_first($modes));
            }
            return;
        }

        $selectedMode = (int)$this->moduleData->get('viewMode');
        if (!array_key_exists($selectedMode, $modes)) {
            // Current function is not in available modes - reset to first available mode
            $this->moduleData->set('viewMode', array_key_first($modes));
            $selectedMode = (int)array_key_first($modes);
        }

        $actionMenu = $this->componentFactory->createMenu();
        $actionMenu->setIdentifier('actionMenu');
        $actionMenu->setLabel(
            $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:pagelayout.moduleMenu.dropdown.label')
        );

        foreach ($modes as $modeValue => $label) {
            $urlParams = $this->buildViewModeSwitchParams($modeValue);
            $menuItem = $this->componentFactory->createMenuItem()
                ->setTitle($label)
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_layout', $urlParams));

            if ($selectedMode === $modeValue) {
                $menuItem->setActive(true);
            }
            $actionMenu->addMenuItem($menuItem);
        }
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($actionMenu);
    }

    /**
     * Build URL parameters for switching to a specific view mode.
     *
     * Implements smart language selection when switching modes:
     *
     * FROM comparison TO layout:
     * - 1 translation selected → keep that translation (user focused on it)
     * - 2+ translations selected → show default language
     *
     * FROM layout TO comparison:
     * - Keep the current language selection to preserve context
     *
     * @return array{id: int, viewMode: int, languages?: int[]}
     */
    private function buildViewModeSwitchParams(int $targetModeValue): array
    {
        $params = ['id' => $this->pageContext->pageId, 'viewMode' => $targetModeValue];
        $targetMode = PageViewMode::tryFrom($targetModeValue);

        // Smart language selection when switching to layout mode FROM comparison mode
        if ($targetMode === PageViewMode::LayoutView && $this->pageContext->hasMultipleLanguagesSelected()) {
            $nonDefaultLanguages = array_filter($this->pageContext->selectedLanguageIds, static fn($id) => $id > 0);
            $params['languages'] = [
                count($nonDefaultLanguages) === 1
                    ? reset($nonDefaultLanguages) // Single translation: keep it
                    : 0, // Multiple: show default
            ];
        }

        // When switching to comparison mode, preserve current language selection
        // The comparison mode will auto-add default language if needed
        if ($targetMode === PageViewMode::LanguageComparisonView) {
            $params['languages'] = $this->pageContext->selectedLanguageIds;
        }

        return $params;
    }

    protected function createPageLayoutContext(
        ServerRequestInterface $request
    ): PageLayoutContext {
        $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($this->pageContext->pageId);
        $viewMode = count($this->pageContext->languageInformation->availableLanguages) > 1
            ? PageViewMode::tryFrom((int)$this->moduleData->get('viewMode')) ?? PageViewMode::LayoutView
            : PageViewMode::LayoutView;
        $configuration = DrawingConfiguration::create($backendLayout, $this->pageContext->pageTsConfig, $viewMode);
        $configuration->setShowHidden((bool)$this->moduleData->get('showHidden'));

        // Build language columns map from available languages
        $languageColumns = [];
        foreach ($this->pageContext->languageInformation->availableLanguages as $language) {
            $languageColumns[$language->getLanguageId()] = $language->getTitle();
        }
        // @todo Check if this is still used at all.
        $configuration->setLanguageColumns($languageColumns);

        $configuration->setSelectedLanguageIds($this->pageContext->selectedLanguageIds);
        return GeneralUtility::makeInstance(PageLayoutContext::class, $this->pageContext, $backendLayout, $configuration, $request);
    }

    /**
     * Return an array of various messages for the current page record,
     * such as if the page has a special doktype, that can be rendered as info boxes.
     */
    protected function generateMessagesForCurrentPage(ServerRequestInterface $request): array
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();

        $infoBoxes = [];
        $currentDocumentType = (int)($this->pageContext->pageRecord['doktype'] ?? 0);
        if ($currentDocumentType === PageRepository::DOKTYPE_SYSFOLDER && $this->moduleProvider->accessGranted('records', $backendUser)) {
            $infoBoxes[] = [
                'title' => $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModule'),
                'message' => '<p>' . $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModuleMessage') . '</p>'
                    . '<button type="button" class="btn btn-primary" data-dispatch-action="TYPO3.ModuleMenu.showModule" data-dispatch-args-list="records">'
                        . $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModule')
                    . '</button>',
                'state' => ContextualFeedbackSeverity::INFO,
            ];
        }
        if ($currentDocumentType === PageRepository::DOKTYPE_SHORTCUT) {
            $shortcutMode = (int)($this->pageContext->pageRecord['shortcut_mode'] ?? 0);
            $targetPage = [];
            $state = ContextualFeedbackSeverity::ERROR;
            if ($shortcutMode || ($this->pageContext->pageRecord['shortcut'] ?? false)) {
                switch ($shortcutMode) {
                    case PageRepository::SHORTCUT_MODE_NONE:
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage((int)($this->pageContext->pageRecord['shortcut'] ?? 0), true));
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredOrNotAccessibleInternalLinkMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:
                        $menuOfPages = $this->pageRepository->getMenu((int)($this->pageContext->pageRecord['uid'] ?? 0), '*', 'sorting', 'AND hidden = 0', true, true);
                        $targetPage = reset($menuOfPages) ?: [];
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredFirstSubpageMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_PARENT_PAGE:
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage((int)($this->pageContext->pageRecord['pid'] ?? 0), true));
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredParentPageMessage') : '';
                        break;
                    default:
                        $message = htmlspecialchars($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredInternalLinkMessage'));
                        break;
                }
                $message = htmlspecialchars($message);
                if ($targetPage !== []) {
                    $linkToPid = $this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $targetPage['uid']]);
                    $path = BackendUtility::getRecordPath($targetPage['uid'], $backendUser->getPagePermsClause(Permission::PAGE_SHOW), 1000);
                    $linkedPath = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($path) . '</a>';
                    $message .= sprintf(htmlspecialchars($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsInternalLinkMessage')), $linkedPath);
                    $message .= ' (' . htmlspecialchars($languageService->sL(BackendUtility::getLabelFromItemlist('pages', 'shortcut_mode', (string)$shortcutMode, $this->pageContext->pageRecord))) . ')';
                    $state = ContextualFeedbackSeverity::INFO;
                }
            } else {
                $message = htmlspecialchars($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredInternalLinkMessage'));
            }
            $infoBoxes[] = [
                'message' => $message,
                'state' => $state,
            ];
        }
        if ($currentDocumentType === PageRepository::DOKTYPE_LINK) {
            $primaryLanguageId = $this->pageContext->getPrimaryLanguageId();
            $pageRecord = ($primaryLanguageId > 0 && ($overlayRecord = $this->pageContext->languageInformation->existingTranslations[$primaryLanguageId] ?? []) !== [])
                ? $overlayRecord
                : $this->pageContext->pageRecord;
            $infoBoxes[] = $this->pageLinkMessageProvider->generateMessagesForPageTypeLink($pageRecord, $request);
        }
        if ($this->pageContext->pageRecord['content_from_pid'] ?? false) {
            // If content from different pid is displayed
            $contentPage = BackendUtility::getRecord('pages', (int)$this->pageContext->pageRecord['content_from_pid']);
            if ($contentPage === null) {
                $infoBoxes[] = [
                    'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_from_pid_invalid_title'), $this->pageContext->pageRecord['content_from_pid']),
                    'state' => ContextualFeedbackSeverity::ERROR,
                ];
            } else {
                $linkToPid = $this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $this->pageContext->pageRecord['content_from_pid']]);
                $title = BackendUtility::getRecordTitle('pages', $contentPage);
                $link = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($title) . ' (PID ' . (int)$this->pageContext->pageRecord['content_from_pid'] . ')</a>';
                $infoBoxes[] = [
                    'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_from_pid_title'), $link),
                    'state' => ContextualFeedbackSeverity::INFO,
                ];
            }
        } elseif ($this->pageContext->pageId > 0) {
            $links = $this->getPageLinksWhereContentIsAlsoShownOn($this->pageContext->pageId);
            if (!empty($links)) {
                $infoBoxes[] = [
                    'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_on_pid_title'), $links),
                    'state' => ContextualFeedbackSeverity::INFO,
                ];
            }
        }
        return $infoBoxes;
    }

    /**
     * Get all pages with links where the content of a page $pageId is also shown on.
     */
    protected function getPageLinksWhereContentIsAlsoShownOn(int $pageId): string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('content_from_pid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)));
        $links = [];
        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $linkToPid = $this->uriBuilder->buildUriFromRoute('web_layout', ['id' =>  $row['uid']]);
                $title = BackendUtility::getRecordTitle('pages', $row);
                $link = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($title) . ' (PID ' . (int)$row['uid'] . ')</a>';
                $links[] = $link;
            }
        }
        return implode(', ', $links);
    }

    protected function addButtonsToButtonBar(ModuleTemplate $view, ServerRequestInterface $request): void
    {
        $languageService = $this->getLanguageService();

        // Close button (show only if returnUrl is set)
        $returnUrl = $request->getQueryParams()['returnUrl'] ?? '';
        if ($returnUrl) {
            // use button group -1 so that close button is to the left of other buttons
            $view->addButtonToButtonBar($this->componentFactory->createCloseButton($returnUrl), ButtonBar::BUTTON_POSITION_LEFT, -1);
        }

        // Language selector
        $this->createLanguageSelector($view);

        // View
        if ($viewButton = $this->makeViewButton()) {
            $view->addButtonToButtonBar($viewButton);
        }

        // QR Code
        if ($qrCodeButton = $this->makeQrCodeButton()) {
            $view->addButtonToButtonBar($qrCodeButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        }

        // Edit
        if ($editButton = $this->makeEditButton($request)) {
            $view->addButtonToButtonBar($editButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }

        // Cache
        $clearCacheButton = $this->componentFactory->createLinkButton()
            ->setHref('#')
            ->setDataAttributes(['id' => $this->pageContext->pageRecord['uid'] ?? 0])
            ->setClasses('t3js-clear-page-cache')
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
            ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', IconSize::SMALL));
        $view->addButtonToButtonBar($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);

        // View settings
        if ($this->getBackendUser()->check('tables_select', 'tt_content')) {
            $viewSettingsButton = $this->componentFactory->createDropDownButton()
                ->setLabel($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view'))
                ->setShowLabelText(true);
            $viewSettingsButton->addItem(
                $this->componentFactory->createDropDownToggle()
                    ->setTag('button')
                    ->setActive((bool)$this->moduleData->get('showHidden'))
                    ->setLabel($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:hiddenCE') . ' (' . $this->getNumberOfHiddenElements() . ')')
                    ->setIcon($this->iconFactory->getIcon('actions-eye'))
                    ->setAttributes([
                        'id' => 'pageLayoutToggleShowHidden',
                        'type' => 'button',
                        'data-pageaction-showhidden' => $this->moduleData->get('showHidden') ? '1' : '0',
                    ])
            );
            $view->addButtonToButtonBar($viewSettingsButton, ButtonBar::BUTTON_POSITION_RIGHT, 0);
        }

        // Shortcut
        $view->getDocHeaderComponent()->setShortcutContext(
            routeIdentifier: 'web_layout',
            displayName: sprintf(
                '%s: %s [%d]',
                $this->getLanguageService()->translate('short_description', 'backend.modules.layout'),
                $this->pageContext->getPageTitle(),
                $this->pageContext->pageId
            ),
            arguments: [
                'id' => $this->pageContext->pageId,
                'showHidden' => (bool)$this->moduleData->get('showHidden'),
                'viewMode' => (int)$this->moduleData->get('viewMode'),
                'languages' => $this->pageContext->selectedLanguageIds,
            ]
        );
    }

    protected function initializeClipboard(ServerRequestInterface $request): void
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard($request);
        $clipboard->lockToNormal();
        $clipboard->cleanCurrent();
        $clipboard->endClipboard();
        $elFromTable = $clipboard->elFromTable('tt_content');
        if (!empty($elFromTable) && $this->isContentEditable($this->pageContext->getPrimaryLanguageId())) {
            $pasteItem = (int)substr((string)key($elFromTable), 11);
            $pasteRecord = BackendUtility::getRecordWSOL('tt_content', $pasteItem);
            $pasteTitle = BackendUtility::getRecordTitle('tt_content', $pasteRecord);
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@typo3/backend/layout-module/paste.js')
                    ->instance([
                        'itemOnClipboardUid' => $pasteItem,
                        'itemOnClipboardTitle' => $pasteTitle,
                        'copyMode' => $clipboard->clipData['normal']['mode'] ?? '',
                    ])
            );
        }
    }

    /**
     * View Button
     */
    protected function makeViewButton(): ?ButtonInterface
    {
        // Do not create a "View webpage" button if
        // * Multiple languages are selected
        // * record is a placeholder
        // * not in "Columns" view,
        if (
            $this->pageContext->hasMultipleLanguagesSelected()
            || VersionState::tryFrom($this->pageContext->pageRecord['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER
            || PageViewMode::tryFrom((int)$this->moduleData->get('viewMode')) !== PageViewMode::LayoutView
        ) {
            return null;
        }

        $previewUriBuilder = PreviewUriBuilder::create($this->pageContext->pageRecord);
        if (!$previewUriBuilder->isPreviewable()) {
            return null;
        }

        return $this->componentFactory->createViewButton($previewUriBuilder
            ->withRootLine($this->pageContext->rootLine)
            ->withLanguage($this->pageContext->getPrimaryLanguageId())
            ->buildDispatcherDataAttributes() ?? []);
    }

    /**
     * QR Code Button - displays a QR code modal for the frontend preview URL
     */
    protected function makeQrCodeButton(): ?ButtonInterface
    {
        if (
            $this->pageContext->hasMultipleLanguagesSelected()
            || VersionState::tryFrom($this->pageContext->pageRecord['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER
            || PageViewMode::tryFrom((int)$this->moduleData->get('viewMode')) !== PageViewMode::LayoutView
        ) {
            return null;
        }

        $previewUriBuilder = PreviewUriBuilder::create($this->pageContext->pageRecord);
        if (!$previewUriBuilder->isPreviewable()) {
            return null;
        }

        $fallbackUri = $previewUriBuilder
            ->withRootLine($this->pageContext->rootLine)
            ->withLanguage($this->pageContext->getPrimaryLanguageId())
            ->buildUri();

        $previewUri = $this->componentFactory->getPreviewUrlForQrCode(
            $this->pageContext->pageId,
            $this->pageContext->getPrimaryLanguageId(),
            $fallbackUri
        );

        if ($previewUri === null) {
            return null;
        }

        return $this->componentFactory->createQrCodeButton($previewUri);
    }

    /**
     * Edit Button
     */
    protected function makeEditButton(ServerRequestInterface $request): ?ButtonInterface
    {
        $primaryLanguageId = $this->pageContext->getPrimaryLanguageId();
        if (!$this->isPageEditable($primaryLanguageId)
            || PageViewMode::tryFrom((int)$this->moduleData->get('viewMode')) !== PageViewMode::LayoutView
        ) {
            return null;
        }

        $pageUid = $this->pageContext->pageId;
        if ($primaryLanguageId > 0 && ($overlayRecord = $this->pageContext->languageInformation->getTranslationRecord($primaryLanguageId)) !== null) {
            $pageUid = $overlayRecord['uid'];
        }
        $params = [
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            'module' => 'web_layout',
            'edit' => [
                'pages' => [
                    $pageUid => 'edit',
                ],
            ],
        ];

        return $this->componentFactory->createLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('record_edit', $params))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editPageProperties'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-page-open', IconSize::SMALL));
    }

    /**
     * Creates the language selector dropdown in the module toolbar.
     */
    protected function createLanguageSelector(ModuleTemplate $view): void
    {
        if (count($this->pageContext->languageInformation->availableLanguages) <= 1) {
            return;
        }

        $viewMode = PageViewMode::tryFrom((int)$this->moduleData->get('viewMode')) ?? PageViewMode::LayoutView;
        $isComparisonMode = $viewMode === PageViewMode::LanguageComparisonView;
        $mode = $isComparisonMode ? LanguageSelectorMode::MULTI_SELECT : LanguageSelectorMode::SINGLE_SELECT;

        $languageSelector = $this->languageSelectorBuilder->build(
            $this->pageContext,
            $mode,
            fn(array $languageIds): string => (string)$this->uriBuilder->buildUriFromRoute('web_layout', [
                'id' => $this->pageContext->pageId,
                'viewMode' => $viewMode->value,
                'languages' => $languageIds,
            ]),
            $isComparisonMode && !empty($this->pageContext->languageInformation->existingTranslations)
        );

        $view->getDocHeaderComponent()->setLanguageSelector($languageSelector);
    }

    /**
     * Returns the number of hidden elements (including those hidden by start/end times)
     * on the current page (for the current site language)
     */
    protected function getNumberOfHiddenElements(): int
    {
        $isComparisonView = (PageViewMode::tryFrom((int)$this->moduleData->get('viewMode')) ?? PageViewMode::LayoutView) === PageViewMode::LanguageComparisonView;
        $andWhere = [];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->pageContext->pageId, Connection::PARAM_INT)
                )
            );

        $languageField = $this->schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();

        // Build list of language IDs to include: always include -1 (all languages) and all selected languages
        $languageIds = [-1];
        foreach ($this->pageContext->selectedLanguageIds as $languageId) {
            $languageIds[] = $languageId;
            // In comparison mode, also include default language (0) if not already selected
            if ($isComparisonView && $languageId > 0 && !$this->pageContext->isDefaultLanguageSelected()) {
                $languageIds[] = 0;
            }
        }
        $languageIds = array_unique($languageIds);

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                $languageField,
                $queryBuilder->createNamedParameter($languageIds, Connection::PARAM_INT_ARRAY)
            )
        );

        if ($this->schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
            $andWhere[] = $queryBuilder->expr()->neq(
                $this->schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName(),
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            );
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
            $starttimeField = $this->schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName();
            $andWhere[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->neq(
                    $starttimeField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    $starttimeField,
                    $queryBuilder->createNamedParameter($GLOBALS['SIM_ACCESS_TIME'], Connection::PARAM_INT)
                )
            );
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::RestrictionEndTime)) {
            $endtimeField = $this->schema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName();
            $andWhere[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->neq(
                    $endtimeField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->lte(
                    $endtimeField,
                    $queryBuilder->createNamedParameter($GLOBALS['SIM_ACCESS_TIME'], Connection::PARAM_INT)
                )
            );
        }
        if ($andWhere !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(...$andWhere)
            );
        }
        $count = $queryBuilder
            ->executeQuery()
            ->fetchOne();
        return (int)$count;
    }

    /**
     * Check if page can be edited by current user.
     */
    protected function isPageEditable(int $languageId): bool
    {
        if (empty($this->pageContext->pageRecord)) {
            return false;
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::AccessReadOnly)) {
            return false;
        }
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)) {
            return false;
        }
        $isEditLocked = false;
        if ($this->schema->hasCapability(TcaSchemaCapability::EditLock)) {
            $isEditLocked = $this->pageContext->pageRecord[$this->schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false;
        }
        if ($isEditLocked) {
            return false;
        }
        return $backendUser->doesUserHaveAccess($this->pageContext->pageRecord, Permission::PAGE_EDIT)
            && $backendUser->checkLanguageAccess($languageId)
            && $backendUser->check('tables_modify', 'pages');
    }

    /**
     * Check if content can be edited by current user
     */
    protected function isContentEditable(int $languageId): bool
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        $isEditLocked = false;
        if ($this->schema->hasCapability(TcaSchemaCapability::EditLock)) {
            $isEditLocked = $this->pageContext->pageRecord[$this->schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false;
        }
        if ($isEditLocked) {
            return false;
        }
        return $this->getBackendUser()->doesUserHaveAccess($this->pageContext->pageRecord, Permission::CONTENT_EDIT)
            && $this->getBackendUser()->check('tables_modify', 'tt_content')
            && $this->getBackendUser()->checkLanguageAccess($languageId);
    }

    /**
     * Returns the target page if visible
     */
    protected function getTargetPageIfVisible(array $targetPage): array
    {
        $fieldName = $this->schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();
        return !($targetPage[$fieldName] ?? false) ? $targetPage : [];
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
