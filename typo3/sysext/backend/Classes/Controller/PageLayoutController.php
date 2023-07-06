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
use TYPO3\CMS\Backend\Attribute\Controller;
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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * The Web > Page module.
 */
#[Controller]
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
    ) {
    }

    protected function initialize(ServerRequestInterface $request): void
    {
        $backendUser = $this->getBackendUser();
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $this->moduleData = $request->getAttribute('moduleData');
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        $this->availableLanguages = $request->getAttribute('site')->getAvailableLanguages($backendUser, false, $this->id);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
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
        $this->menuConfig($request);
        $this->currentSelectedLanguage = (int)$this->moduleData->get('language');
        $this->addJavaScriptModuleInstructions();
        $this->makeActionMenu($view, $tsConfig);
        $this->makeButtons($view, $request, $tsConfig);
        $this->initializeClipboard($request);
        $event = $this->eventDispatcher->dispatch(new ModifyPageLayoutContentEvent($request, $view));

        $pageLayoutContext = $this->createPageLayoutContext();
        $mainLayoutHtml = $this->backendLayoutRenderer->drawContent($request, $pageLayoutContext);
        $numberOfHiddenElements = $this->getNumberOfHiddenElements(
            $pageLayoutContext->getDrawingConfiguration()->getLanguageMode()
        );

        $pageLocalizationRecord = $this->getLocalizedPageRecord($this->currentSelectedLanguage);

        $view->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'), $this->pageinfo['title']);
        $view->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        $view->assignMultiple([
            'pageId' => $this->id,
            'localizedPageId' => $pageLocalizationRecord['uid'] ?? 0,
            'infoBoxes' => $this->generateMessagesForCurrentPage($request),
            'isPageEditable' => $this->isPageEditable($this->currentSelectedLanguage),
            'localizedPageTitle' => $this->getLocalizedPageTitle($this->currentSelectedLanguage, $this->pageinfo),
            'eventContentHtmlTop' => $event->getHeaderContent(),
            'mainContentHtml' => $mainLayoutHtml,
            'hiddenElementsShowToggle' => ($this->getBackendUser()->check('tables_select', 'tt_content') && ($numberOfHiddenElements > 0)),
            'hiddenElementsState' => (bool)$this->moduleData->get('showHidden'),
            'hiddenElementsCount' => $numberOfHiddenElements,
            'eventContentHtmlBottom' => $event->getFooterContent(),
        ]);
        return $view->renderResponse('PageLayout/PageModule');
    }

    protected function createPageLayoutContext(): PageLayoutContext
    {
        $tsConfig = BackendUtility::getPagesTSconfig($this->id);
        $pageLayoutContext = GeneralUtility::makeInstance(PageLayoutContext::class, $this->pageinfo, $this->backendLayoutView->getBackendLayoutForPage($this->id));
        $configuration = $pageLayoutContext->getDrawingConfiguration();
        $configuration->setDefaultLanguageBinding(!empty($tsConfig['mod.']['web_layout.']['defLangBinding']));
        $configuration->setActiveColumns($this->getActiveColumnsArray($pageLayoutContext, $tsConfig));
        $configuration->setShowHidden((bool)$this->moduleData->get('showHidden'));
        $configuration->setLanguageColumns($this->MOD_MENU['language']);
        $configuration->setSelectedLanguageId($this->currentSelectedLanguage);
        $configuration->setAllowInconsistentLanguageHandling((bool)($tsConfig['mod.']['web_layout.']['allowInconsistentLanguageHandling'] ?? false));
        if ((int)$this->moduleData->get('function') === 2) {
            $configuration->setLanguageMode(true);
        }
        return $pageLayoutContext;
    }

    /**
     * Initialize menu array
     */
    protected function menuConfig(ServerRequestInterface $request): void
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        // MENU-ITEMS:
        $this->MOD_MENU = [
            'function' => [
                1 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.layout'),
                2 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.language_comparison'),
            ],
            'language' => [
                0 => isset($this->availableLanguages[0]) ? $this->availableLanguages[0]->getTitle() : $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:m_default'),
            ],
        ];

        // First, select all localized page records on the current page.
        // Each represents a possibility for a language on the page. Add these to language selector.
        if ($this->id) {
            // Compile language data for pid != 0 only. The language drop-down is not shown on pid 0
            // since pid 0 can't be localized.
            $pageTranslations = $this->getExistingPageTranslations();
            foreach ($pageTranslations as $pageTranslation) {
                $languageId = $pageTranslation[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
                if (isset($this->availableLanguages[$languageId])) {
                    $this->MOD_MENU['language'][$languageId] = $this->availableLanguages[$languageId]->getTitle();
                }
            }

            // Add special "-1" in case translations of the current page exist
            if (count($this->MOD_MENU['language']) > 1) {
                // We need to add -1 (all) here so a possible -1 value will be allowed when calling
                // moduleData->cleanUp(). Actually, this is only relevant if we are dealing with the
                // "languages" mode, which however can only be safely determined, after the moduleData
                // have been cleaned up => chicken and egg problem. We therefore remove the -1 item from
                // the menu again, as soon as we are able to determine the requested mode.
                // @todo Replace the whole "mode" handling with some more robust solution
                $this->MOD_MENU['language'][-1] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages');
            }
        }
        // Clean up settings
        if ($this->moduleData->cleanUp($this->MOD_MENU)) {
            $backendUser->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
        }
        if ($backendUser->workspace !== 0) {
            // Show all elements in draft workspaces
            $this->moduleData->set('showHidden', true);
        }
        if ((int)$this->moduleData->get('function') !== 2) {
            // Remove -1 (all) from the module menu if not "languages" mode
            unset($this->MOD_MENU['language'][-1]);
            // In case -1 (all) is still set as language, but we are no longer in
            // "languages" mode, we fall back to the default, preventing an empty grid.
            if ((int)$this->moduleData->get('language') === -1) {
                $this->moduleData->set('language', 0);
            }
        }
    }

    /**
     * Fetch all records of the current page ID.
     * Does not do workspace overlays, and also does not check permissions
     */
    protected function getExistingPageTranslations(): array
    {
        if ($this->id === 0) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        return $queryBuilder
            ->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($this->id, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
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
        $overlayRecord = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($this->id, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
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
        $actionMenu->setLabel('');
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
                    . '<a class="btn btn-primary" data-dispatch-action="TYPO3.ModuleMenu.showModule" data-dispatch-args-list="web_list">'
                        . $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModule')
                    . '</a>',
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
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage($this->pageinfo['shortcut'], true));
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredOrNotAccessibleInternalLinkMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:
                        $menuOfPages = $this->pageRepository->getMenu($this->pageinfo['uid'], '*', 'sorting', 'AND hidden = 0', true, true);
                        $targetPage = reset($menuOfPages) ?: [];
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredFirstSubpageMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_PARENT_PAGE:
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage($this->pageinfo['pid'], true));
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
                    $message .= ' (' . htmlspecialchars($languageService->sL(BackendUtility::getLabelFromItemlist('pages', 'shortcut_mode', (string)$shortcutMode))) . ')';
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

    protected function getLocalizedPageTitle(int $currentSelectedLanguage, array $pageInfo): string
    {
        if ($currentSelectedLanguage <= 0) {
            return $pageInfo['title'];
        }
        $pageLocalizationRecord = $this->getLocalizedPageRecord($currentSelectedLanguage);
        if (!is_array($pageLocalizationRecord)) {
            return $pageInfo['title'];
        }
        return $pageLocalizationRecord['title'] ?? '';
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

    protected function getActiveColumnsArray(PageLayoutContext $pageLayoutContext, array $tsConfig): array
    {
        $availableColumnPositionsFromBackendLayout = array_unique($pageLayoutContext->getBackendLayout()->getColumnPositionNumbers());
        $allowedColumnPositionsByTsConfig = array_unique(GeneralUtility::intExplode(',', (string)($tsConfig['mod.']['SHARED.']['colPos_list'] ?? ''), true));
        $activeColumns = $availableColumnPositionsFromBackendLayout;
        if (!empty($allowedColumnPositionsByTsConfig)) {
            // If there is no tsConfig colPos_list, no restriction. Else create intersection of available and allowed.
            $activeColumns = array_intersect($availableColumnPositionsFromBackendLayout, $allowedColumnPositionsByTsConfig);
        }
        return $activeColumns;
    }

    /**
     * This creates the buttons for the modules
     */
    protected function makeButtons(ModuleTemplate $view, ServerRequestInterface $request, array $tsConfig): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

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
            ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
        $buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);

        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
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
            || VersionState::cast($this->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            return null;
        }

        if (isset($tsConfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
            // Exclude doktypes, set via tsConfig
            $excludeDokTypes = GeneralUtility::intExplode(',', (string)($tsConfig['TCEMAIN.']['preview.']['disableButtonForDokType'] ?? ''), true);
        } else {
            // Exclude default doktypes: sysfolders, spacers and recycler
            $excludeDokTypes = [
                PageRepository::DOKTYPE_RECYCLER,
                PageRepository::DOKTYPE_SYSFOLDER,
                PageRepository::DOKTYPE_SPACER,
            ];
        }

        if (in_array((int)$this->pageinfo['doktype'], $excludeDokTypes, true)) {
            return null;
        }

        $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageinfo['uid'])
            ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
            ->withLanguage($this->currentSelectedLanguage)
            ->buildDispatcherDataAttributes();

        return $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes($previewDataAttributes ?? [])
            ->setDisabled(!$previewDataAttributes)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
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
            ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
    }

    /**
     * Language Switch
     */
    protected function makeLanguageSwitchButton(ButtonBar $buttonbar): ?ButtonInterface
    {
        // Early return if less than 2 languages are available
        if (count($this->MOD_MENU['language']) < 2) {
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
    protected function getNumberOfHiddenElements(bool $isLanguageModeActive): int
    {
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

        if ($this->currentSelectedLanguage === 0) {
            // Default language is active (in columns or language mode) - consider "all languages" and the default
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $GLOBALS['TCA']['tt_content']['ctrl']['languageField'],
                    [-1, 0]
                )
            );
        } elseif ($isLanguageModeActive && $this->currentSelectedLanguage !== -1) {
            // Multi-language view with any translation is active -
            // consider "all languages", the default and the translation
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $GLOBALS['TCA']['tt_content']['ctrl']['languageField'],
                    [-1, 0, $queryBuilder->createNamedParameter($this->currentSelectedLanguage, Connection::PARAM_INT)]
                )
            );
        } elseif ($this->currentSelectedLanguage > 0) {
            // Columns mode with any translation is active - consider "all languages" and the translation
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $GLOBALS['TCA']['tt_content']['ctrl']['languageField'],
                    [-1, $queryBuilder->createNamedParameter($this->currentSelectedLanguage, Connection::PARAM_INT)]
                )
            );
        }

        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'])) {
            $andWhere[] = $queryBuilder->expr()->neq(
                $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'],
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            );
        }
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['starttime'])) {
            $starttimeField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['starttime'];
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
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['endtime'])) {
            $endtimeField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['endtime'];
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
        if (!empty($andWhere)) {
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
        if ($GLOBALS['TCA']['pages']['ctrl']['readOnly'] ?? false) {
            return false;
        }
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }
        if ($GLOBALS['TCA']['pages']['ctrl']['adminOnly'] ?? false) {
            return false;
        }
        return is_array($this->pageinfo)
            && $this->pageinfo !== []
            && !(bool)($this->pageinfo[$GLOBALS['TCA']['pages']['ctrl']['editlock'] ?? null] ?? false)
            && $backendUser->doesUserHaveAccess($this->pageinfo, Permission::PAGE_EDIT)
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
        return !($this->pageinfo['editlock'] ?? false)
            && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)
            && $this->getBackendUser()->check('tables_modify', 'tt_content')
            && $this->getBackendUser()->checkLanguageAccess($languageId);
    }

    /**
     * Returns the target page if visible
     */
    protected function getTargetPageIfVisible(array $targetPage): array
    {
        return !(bool)($targetPage['hidden'] ?? false) ? $targetPage : [];
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
