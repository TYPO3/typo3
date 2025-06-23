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
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownToggle;
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
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * The Web > Page module.
 */
#[AsController]
class PageLayoutController
{
    /**
     * Page uid for which to make the listing
     */
    protected int $id = 0;

    /**
     * Current page record
     */
    protected array|bool $pageinfo = false;

    protected int $currentSelectedLanguage;
    protected array $MOD_MENU;
    protected ?TcaSchema $schema = null;

    /**
     * @var SiteLanguage[]
     */
    protected array $availableLanguages = [];

    protected ?ModuleData $moduleData = null;

    public function __construct(
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
    ) {}

    protected function initialize(ServerRequestInterface $request): void
    {
        $backendUser = $this->getBackendUser();
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $this->moduleData = $request->getAttribute('moduleData');
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        $this->availableLanguages = $request->getAttribute('site')->getAvailableLanguages($backendUser, false, $this->id);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $this->schema = $this->tcaSchemaFactory->get('pages');
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initialize($request);
        $languageService = $this->getLanguageService();

        $view = $this->moduleTemplateFactory->create($request);
        if ($this->id === 0 || $this->pageinfo === false) {
            // Page uid 0 or no access.
            $view->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'));
            $view->assignMultiple([
                'pageId' => $this->id,
                'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? '',
            ]);
            return $view->renderResponse('PageLayout/PageModuleNoAccess');
        }

        $tsConfig = BackendUtility::getPagesTSconfig($this->id);
        $this->menuConfig();
        $this->currentSelectedLanguage = (int)$this->moduleData->get('language');
        $this->addJavaScriptModuleInstructions();
        $this->makeActionMenu($view, $tsConfig);
        $this->makeButtons($view, $request, $tsConfig);
        $this->initializeClipboard($request);
        $event = $this->eventDispatcher->dispatch(new ModifyPageLayoutContentEvent($request, $view));

        $pageLayoutContext = $this->createPageLayoutContext($request, $tsConfig);
        $mainLayoutHtml = $this->backendLayoutRenderer->drawContent($request, $pageLayoutContext);
        $pageLocalizationRecord = $this->getLocalizedPageRecord($this->currentSelectedLanguage);

        $view->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'), $this->pageinfo['title']);
        $view->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        $view->assignMultiple([
            'pageId' => $this->id,
            'localizedPageId' => $pageLocalizationRecord['uid'] ?? 0,
            'pageLayoutContext' => $pageLayoutContext,
            'infoBoxes' => $this->generateMessagesForCurrentPage($request),
            'isPageEditable' => $this->isPageEditable($this->currentSelectedLanguage),
            'localizedPageTitle' => $pageLocalizationRecord['title'] ?? $this->pageinfo['title'] ?? '',
            'eventContentHtmlTop' => $event->getHeaderContent(),
            'mainContentHtml' => $mainLayoutHtml,
            'eventContentHtmlBottom' => $event->getFooterContent(),
        ]);
        return $view->renderResponse('PageLayout/PageModule');
    }

    protected function createPageLayoutContext(ServerRequestInterface $request, array $tsConfig): PageLayoutContext
    {
        $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($this->id);
        $viewMode = (int)$this->moduleData->get('function') === 2 ? PageViewMode::LanguageComparisonView : PageViewMode::LayoutView;
        $configuration = DrawingConfiguration::create($backendLayout, $tsConfig, $viewMode);
        $configuration->setShowHidden((bool)$this->moduleData->get('showHidden'));
        $configuration->setLanguageColumns($this->MOD_MENU['language']);
        $configuration->setSelectedLanguageId($this->currentSelectedLanguage);
        return GeneralUtility::makeInstance(PageLayoutContext::class, $this->pageinfo, $backendLayout, $request->getAttribute('site'), $configuration, $request);
    }

    /**
     * Initialize menu array
     */
    protected function menuConfig(): void
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();
        $translations = [];

        // MENU-ITEMS:
        $this->MOD_MENU = [
            'function' => [
                1 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.layout'),
            ],
            'language' => [
                0 => isset($this->availableLanguages[0]) ? $this->availableLanguages[0]->getTitle() : $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:m_default'),
            ],
        ];

        // Add language comparison mode for sites with multiple languages
        if (count($this->availableLanguages) > 0) {
            // Add all possible languages first for the cleanup to make sure we keep the selected language
            // when the user switches between pages with/without translations
            foreach ($this->availableLanguages as $language) {
                $this->MOD_MENU['language'][$language->getLanguageId()] = $language->getTitle();
            }

            // First, select all localized page records on the current page.
            // Each represents a possibility for a language on the page. Add these to the language selector.
            if ($this->id) {
                // Compile language data for pid != 0 only. The language drop-down is not shown on pid 0
                // since pid 0 can't be localized.
                $pageTranslations = BackendUtility::getExistingPageTranslations($this->id);
                $languageField = $this->schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
                foreach ($pageTranslations as $pageTranslation) {
                    $languageId = $pageTranslation[$languageField];
                    if (isset($this->availableLanguages[$languageId])) {
                        $translations[] = $languageId;
                    }
                }
            }

            // Add language comparison mode if translations are possible
            $this->MOD_MENU['function'][2] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.language_comparison');
            $this->MOD_MENU['language'][-1] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages');
        }

        // Cleanup settings
        if ($this->moduleData->cleanUp($this->MOD_MENU)) {
            $backendUser->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
        }

        // Remove all languages from MOD_MENU, which have no page translations after cleanup
        foreach ($this->MOD_MENU['language'] as $languageId => $language) {
            if ($languageId > 0 && !in_array($languageId, $translations, true)) {
                unset($this->MOD_MENU['language'][$languageId]);
            }
        }

        if ($translations === []) {
            // Remove -1 if we have no translations
            unset($this->MOD_MENU['language'][-1]);

            // No translations -> set module data for the current request to default language
            $this->moduleData->set('language', 0);
        }

        if ($backendUser->workspace !== 0) {
            // Show all elements in draft workspaces
            $this->moduleData->set('showHidden', true);
        }
    }

    protected function getLocalizedPageRecord(int $languageId): ?array
    {
        if ($languageId === 0) {
            return null;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        $languageCapability = $this->schema->getCapability(TcaSchemaCapability::Language);
        $overlayRecord = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $languageCapability->getTranslationOriginPointerField()->getName(),
                    $queryBuilder->createNamedParameter($this->id, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $languageCapability->getLanguageField()->getName(),
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if ($overlayRecord) {
            BackendUtility::workspaceOL('pages', $overlayRecord, $this->getBackendUser()->workspace);
        }
        return is_array($overlayRecord) ? $overlayRecord : null;
    }

    /**
     * This creates the dropdown menu with the different actions this module is able to provide.
     * For now, they are Columns and Languages.
     */
    protected function makeActionMenu(ModuleTemplate $view, array $tsConfig): void
    {
        $languageService = $this->getLanguageService();
        $actions = [
            1 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.layout'),
        ];
        // Find if there are ANY languages at all (and if not, do not show the language option from function menu).
        // The second check is for an edge case: Only two languages in the site and the default is not allowed.
        if (count($this->availableLanguages) > 1 || (int)array_key_first($this->availableLanguages) > 0) {
            $actions[2] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.language_comparison');
        }
        // Page / user TSconfig blinding of menu-items
        $blindActions = $tsConfig['mod.']['web_layout.']['menu.']['functions.'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $actions)) {
                unset($actions[$key]);
            }
        }

        $actionMenu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $actionMenu->setIdentifier('actionMenu');
        $actionMenu->setLabel(
            $languageService->sL(
                'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:pagelayout.moduleMenu.dropdown.label'
            )
        );
        $defaultKey = null;
        $foundDefaultKey = false;
        foreach ($actions as $key => $action) {
            $menuItem = $actionMenu
                ->makeMenuItem()
                ->setTitle($action)
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $this->id, 'function' => $key]));
            if (!$foundDefaultKey) {
                $defaultKey = $key;
                $foundDefaultKey = true;
            }
            if ((int)$this->moduleData->get('function') === $key) {
                $menuItem->setActive(true);
                $defaultKey = null;
            }
            $actionMenu->addMenuItem($menuItem);
        }
        if (isset($defaultKey)) {
            $this->moduleData->set('function', $defaultKey);
        }
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($actionMenu);
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
        $currentDocumentType = (int)$this->pageinfo['doktype'];
        if ($currentDocumentType === PageRepository::DOKTYPE_SYSFOLDER && $this->moduleProvider->accessGranted('web_list', $backendUser)) {
            $infoBoxes[] = [
                'title' => $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModule'),
                'message' => '<p>' . $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModuleMessage') . '</p>'
                    . '<button type="button" class="btn btn-primary" data-dispatch-action="TYPO3.ModuleMenu.showModule" data-dispatch-args-list="web_list">'
                        . $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModule')
                    . '</button>',
                'state' => InfoboxViewHelper::STATE_INFO,
            ];
        }
        if ($currentDocumentType === PageRepository::DOKTYPE_SHORTCUT) {
            $shortcutMode = (int)$this->pageinfo['shortcut_mode'];
            $targetPage = [];
            $message = '';
            $state = InfoboxViewHelper::STATE_ERROR;
            if ($shortcutMode || $this->pageinfo['shortcut']) {
                switch ($shortcutMode) {
                    case PageRepository::SHORTCUT_MODE_NONE:
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage((int)$this->pageinfo['shortcut'], true));
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredOrNotAccessibleInternalLinkMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:
                        $menuOfPages = $this->pageRepository->getMenu($this->pageinfo['uid'], '*', 'sorting', 'AND hidden = 0', true, true);
                        $targetPage = reset($menuOfPages) ?: [];
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredFirstSubpageMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_PARENT_PAGE:
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage((int)$this->pageinfo['pid'], true));
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredParentPageMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE:
                        $possibleTargetPages = $this->pageRepository->getMenu($this->pageinfo['uid'], '*', 'sorting', 'AND hidden = 0', true, true);
                        if ($possibleTargetPages === []) {
                            $message = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredOrNotAccessibleRandomInternalLinkMessage');
                        } else {
                            $message = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsRandomInternalLinkMessage');
                            $state = InfoboxViewHelper::STATE_INFO;
                        }
                        break;
                }
                $message = htmlspecialchars($message);
                if ($targetPage !== [] && $shortcutMode !== PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE) {
                    $linkToPid = $this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $targetPage['uid']]);
                    $path = BackendUtility::getRecordPath($targetPage['uid'], $backendUser->getPagePermsClause(Permission::PAGE_SHOW), 1000);
                    $linkedPath = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($path) . '</a>';
                    $message .= sprintf(htmlspecialchars($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsInternalLinkMessage')), $linkedPath);
                    $message .= ' (' . htmlspecialchars($languageService->sL(BackendUtility::getLabelFromItemlist('pages', 'shortcut_mode', (string)$shortcutMode, $this->pageinfo))) . ')';
                    $state = InfoboxViewHelper::STATE_INFO;
                }
            } else {
                $message = htmlspecialchars($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredInternalLinkMessage'));
                $state = InfoboxViewHelper::STATE_ERROR;
            }
            $infoBoxes[] = [
                'message' => $message,
                'state' => $state,
            ];
        }
        if ($currentDocumentType === PageRepository::DOKTYPE_LINK) {
            if (empty($this->pageinfo['url'])) {
                $infoBoxes[] = [
                    'message' => $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredExternalLinkMessage'),
                    'state' => InfoboxViewHelper::STATE_ERROR,
                ];
            } else {
                $externalUrl = $this->resolveExternalUrl($this->pageinfo, $request);
                if ($externalUrl !== '') {
                    $externalUrl = htmlspecialchars($externalUrl);
                    $externalUrlHtml = '<a href="' . $externalUrl . '" target="_blank" rel="noreferrer">' . $externalUrl . '</a>';
                    $infoBoxes[] = [
                        'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsExternalLinkMessage'), $externalUrlHtml),
                        'state' => InfoboxViewHelper::STATE_INFO,
                    ];
                }
            }
        }
        if ($this->pageinfo['content_from_pid']) {
            // If content from different pid is displayed
            $contentPage = BackendUtility::getRecord('pages', (int)$this->pageinfo['content_from_pid']);
            if ($contentPage === null) {
                $infoBoxes[] = [
                    'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_from_pid_invalid_title'), $this->pageinfo['content_from_pid']),
                    'state' => InfoboxViewHelper::STATE_ERROR,
                ];
            } else {
                $linkToPid = $this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $this->pageinfo['content_from_pid']]);
                $title = BackendUtility::getRecordTitle('pages', $contentPage);
                $link = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($title) . ' (PID ' . (int)$this->pageinfo['content_from_pid'] . ')</a>';
                $infoBoxes[] = [
                    'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_from_pid_title'), $link),
                    'state' => InfoboxViewHelper::STATE_INFO,
                ];
            }
        } else {
            $links = $this->getPageLinksWhereContentIsAlsoShownOn((int)$this->pageinfo['uid']);
            if (!empty($links)) {
                $infoBoxes[] = [
                    'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_on_pid_title'), $links),
                    'state' => InfoboxViewHelper::STATE_INFO,
                ];
            }
        }
        return $infoBoxes;
    }

    protected function addJavaScriptModuleInstructions(): void
    {
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/page-actions.js')
        );
    }

    /**
     * Get all pages with links where the content of a page $pageId is also shown on.
     */
    protected function getPageLinksWhereContentIsAlsoShownOn(int $pageId): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
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

    /**
     * Initializes the clipboard for generating paste links dynamically via JavaScript after each "+ Content" symbol
     */
    protected function initializeClipboard(ServerRequestInterface $request): void
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard($request);
        $clipboard->lockToNormal();
        $clipboard->cleanCurrent();
        $clipboard->endClipboard();
        $elFromTable = $clipboard->elFromTable('tt_content');
        if (!empty($elFromTable) && $this->isContentEditable($this->currentSelectedLanguage)) {
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
     * This creates the buttons for the modules
     */
    protected function makeButtons(ModuleTemplate $view, ServerRequestInterface $request, array $tsConfig): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        // Close button (show only if returnUrl is set)
        $returnUrl = $request->getQueryParams()['returnUrl'] ?? '';
        if ($returnUrl && ($closeButton = $this->makeCloseButton($buttonBar, $returnUrl))) {
            // use button group -1 so that close button is to the left of other buttons
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, -1);
        }

        // Language
        if ($languageButton = $this->makeLanguageSwitchButton($buttonBar)) {
            $buttonBar->addButton($languageButton, ButtonBar::BUTTON_POSITION_LEFT, 0);
        }

        // View
        if ($viewButton = $this->makeViewButton($buttonBar, $tsConfig)) {
            $buttonBar->addButton($viewButton);
        }

        // Edit
        if ($editButton = $this->makeEditButton($buttonBar, $request)) {
            $buttonBar->addButton($editButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        }

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_layout')
            ->setDisplayName($this->getShortcutTitle())
            ->setArguments([
                'id' => $this->id,
                'showHidden' => (bool)$this->moduleData->get('showHidden'),
                'function' => (int)$this->moduleData->get('function'),
                'language' => $this->currentSelectedLanguage,
            ]);
        $buttonBar->addButton($shortcutButton);

        // Cache
        $clearCacheButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes(['id' => $this->pageinfo['uid']])
            ->setClasses('t3js-clear-page-cache')
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
            ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', IconSize::SMALL));
        $buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);

        // ViewMode
        $viewModeItems = [];
        $pageLayoutContext = $this->createPageLayoutContext($request, $tsConfig);
        $hiddenElementsShowToggle = $this->getBackendUser()->check('tables_select', 'tt_content');
        if ($hiddenElementsShowToggle) {
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownToggle::class)
                ->setTag('button')
                ->setActive((bool)$this->moduleData->get('showHidden'))
                ->setLabel($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:hiddenCE') . ' (' . $this->getNumberOfHiddenElements($pageLayoutContext->getDrawingConfiguration()) . ')')
                ->setIcon($this->iconFactory->getIcon('actions-eye'))
                ->setAttributes([
                    'id' => 'pageLayoutToggleShowHidden',
                    'type' => 'button',
                    'data-pageaction-showhidden' => (bool)$this->moduleData->get('showHidden') ? '1' : '0',
                ]);
        }
        if (!empty($viewModeItems)) {
            $viewModeButton = $buttonBar->makeDropDownButton()
                ->setLabel($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view'))
                ->setShowLabelText(true);
            foreach ($viewModeItems as $viewModeItem) {
                /** @var DropDownItemInterface $viewModeItem */
                $viewModeButton->addItem($viewModeItem);
            }
            $buttonBar->addButton($viewModeButton, ButtonBar::BUTTON_POSITION_RIGHT, 3);
        }

        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * View Button
     */
    protected function makeViewButton(ButtonBar $buttonBar, array $tsConfig): ?ButtonInterface
    {
        // Do not create a "View webpage" button if
        // * "All languages" is selected
        // * not in "Columns" view,
        // * record is a placeholder
        if (
            $this->currentSelectedLanguage === -1
            || (int)$this->moduleData->get('function') !== 1
            || VersionState::tryFrom($this->pageinfo['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER
        ) {
            return null;
        }

        $previewUriBuilder = PreviewUriBuilder::create($this->pageinfo);
        if (!$previewUriBuilder->isPreviewable()) {
            return null;
        }

        $previewDataAttributes = $previewUriBuilder
            ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
            ->withLanguage($this->currentSelectedLanguage)
            ->buildDispatcherDataAttributes();

        return $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes($previewDataAttributes ?? [])
            ->setDisabled(!$previewDataAttributes)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->iconFactory->getIcon('actions-view-page', IconSize::SMALL))
            ->setShowLabelText(true);
    }

    /**
     * Edit Button
     */
    protected function makeEditButton(ButtonBar $buttonBar, ServerRequestInterface $request): ?ButtonInterface
    {
        if ((int)$this->moduleData->get('function') !== 1
            || !$this->isPageEditable($this->currentSelectedLanguage)
        ) {
            return null;
        }

        $pageUid = $this->id;
        if ($this->currentSelectedLanguage > 0) {
            $overlayRecord = $this->getLocalizedPageRecord($this->currentSelectedLanguage);
            $pageUid = $overlayRecord['uid'];
        }
        $params = [
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            'edit' => [
                'pages' => [
                    $pageUid => 'edit',
                ],
            ],
        ];

        return $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('record_edit', $params))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editPageProperties'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-page-open', IconSize::SMALL));
    }

    protected function makeCloseButton(ButtonBar $buttonBar, string $returnUrl): ?ButtonInterface
    {
        return $buttonBar->makeLinkButton()
            ->setHref($returnUrl)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.close') ?: 'Close')
            ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL))
            ->setShowLabelText(true);
    }

    /**
     * Language Switch
     */
    protected function makeLanguageSwitchButton(ButtonBar $buttonbar): ?ButtonInterface
    {
        // Early return if no translation exist
        if (array_filter($this->MOD_MENU['language'], static fn($language): bool => $language > 0, ARRAY_FILTER_USE_KEY) === []) {
            return null;
        }

        $languageService = $this->getLanguageService();

        $languageDropDownButton = $buttonbar->makeDropDownButton()
            ->setLabel($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.language'))
            ->setShowLabelText(true);

        foreach ($this->MOD_MENU['language'] as $key => $language) {
            $siteLanguage = $this->availableLanguages[$key] ?? null;
            if (!$siteLanguage instanceof SiteLanguage) {
                // Skip invalid language keys, e.g. "-1" for "all languages"
                continue;
            }
            /** @var DropDownItemInterface $languageItem */
            $languageItem = GeneralUtility::makeInstance(DropDownRadio::class)
                ->setActive($this->currentSelectedLanguage === $siteLanguage->getLanguageId())
                ->setIcon($this->iconFactory->getIcon($siteLanguage->getFlagIdentifier()))
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_layout', [
                    'id' => $this->id,
                    'function' => (int)$this->moduleData->get('function'),
                    'language' => $siteLanguage->getLanguageId(),
                ]))
                ->setLabel($siteLanguage->getTitle());
            $languageDropDownButton->addItem($languageItem);
        }

        if ((int)$this->moduleData->get('function') !== 1) {
            /** @var DropDownItemInterface $allLanguagesItem */
            $allLanguagesItem = GeneralUtility::makeInstance(DropDownRadio::class)
                ->setActive($this->currentSelectedLanguage === -1)
                ->setIcon($this->iconFactory->getIcon('flags-multiple'))
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_layout', [
                    'id' => $this->id,
                    'function' => (int)$this->moduleData->get('function'),
                    'language' => -1,
                ]))
                ->setLabel($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages'));
            $languageDropDownButton->addItem($allLanguagesItem);
        }

        return $languageDropDownButton;
    }

    /**
     * Returns the number of hidden elements (including those hidden by start/end times)
     * on the current page (for the current site language)
     */
    protected function getNumberOfHiddenElements(DrawingConfiguration $drawingConfiguration): int
    {
        $isLanguageComparisonModeActive = $drawingConfiguration->isLanguageComparisonMode();
        $andWhere = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
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
                    $queryBuilder->createNamedParameter($this->id, Connection::PARAM_INT)
                )
            );

        $languageField = $this->schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        if ($this->currentSelectedLanguage === 0) {
            // Default language is active (in columns or language mode) - consider "all languages" and the default
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $languageField,
                    [-1, 0]
                )
            );
        } elseif ($isLanguageComparisonModeActive && $this->currentSelectedLanguage !== -1) {
            // Multi-language view with any translation is active -
            // consider "all languages", the default and the translation
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $languageField,
                    [-1, 0, $queryBuilder->createNamedParameter($this->currentSelectedLanguage, Connection::PARAM_INT)]
                )
            );
        } elseif ($this->currentSelectedLanguage > 0) {
            // Columns mode with any translation is active - consider "all languages" and the translation
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $languageField,
                    [-1, $queryBuilder->createNamedParameter($this->currentSelectedLanguage, Connection::PARAM_INT)]
                )
            );
        }

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
        if ($this->pageinfo === false || $this->pageinfo === []) {
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
            $isEditLocked = $this->pageinfo[$this->schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false;
        }
        if ($isEditLocked) {
            return false;
        }
        return $backendUser->doesUserHaveAccess($this->pageinfo, Permission::PAGE_EDIT)
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
            $isEditLocked = $this->pageinfo[$this->schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false;
        }
        if ($isEditLocked) {
            return false;
        }
        return $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)
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

    /**
     * Returns the shortcut title for the current page
     */
    protected function getShortcutTitle(): string
    {
        return sprintf(
            '%s: %s [%d]',
            $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf:mlang_labels_tablabel'),
            BackendUtility::getRecordTitle('pages', (array)$this->pageinfo),
            $this->id
        );
    }

    /**
     * Returns the redirect URL for the input page row IF the doktype is set to 3.
     */
    protected function resolveExternalUrl(array $pagerow, ServerRequestInterface $request): string
    {
        $redirectTo = (string)($pagerow['url'] ?? '');
        if ($redirectTo === '') {
            return '';
        }
        $urlInformation = parse_url($redirectTo);
        // If relative path, prefix Site URL
        // If it's a valid email without protocol, add "mailto:"
        if (!($urlInformation['scheme'] ?? false)) {
            if (GeneralUtility::validEmail($redirectTo)) {
                $redirectTo = 'mailto:' . $redirectTo;
            } elseif ($redirectTo[0] !== '/') {
                $redirectTo = $request->getAttribute('normalizedParams')->getSiteUrl() . $redirectTo;
            }
        }
        return $redirectTo;
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
