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
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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
class PageLayoutController
{
    /**
     * Page uid for which to make the listing
     *
     * @internal
     */
    public int $id;

    /**
     * Current page record
     *
     * @internal
     */
    public array $pageinfo;

    protected int $currentSelectedLanguage;
    protected array $MOD_MENU;

    /**
     * @var SiteLanguage[]
     */
    protected $availableLanguages;

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
    ) {
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();
        $languageService->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');

        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $this->moduleData = $request->getAttribute('moduleData');
        $pageInfo = BackendUtility::readPageAccess($this->id, $backendUser->getPagePermsClause(Permission::PAGE_SHOW));

        $view = $this->moduleTemplateFactory->create($request, 'typo3/cms-backend');
        if ($this->id === 0 || $pageInfo === false) {
            // Page uid 0 or no access.
            $view->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'));
            $view->assignMultiple([
                'pageId' => $this->id,
                'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? '',
            ]);
            return $view->renderResponse('PageLayout/PageModuleNoAccess');
        }

        $this->pageinfo = $pageInfo;
        $pageLayoutContext = GeneralUtility::makeInstance(PageLayoutContext::class, $this->pageinfo, GeneralUtility::makeInstance(BackendLayoutView::class)->getBackendLayoutForPage($this->id));
        $this->availableLanguages = $request->getAttribute('site')->getAvailableLanguages($backendUser, false, $this->id);
        $tsConfig = BackendUtility::getPagesTSconfig($this->id);
        $this->menuConfig($request);
        $this->currentSelectedLanguage = (int)($this->moduleData?->get('language') ?? 0);
        $this->addJavaScriptModuleInstructions($this->id, $pageLayoutContext, $this->currentSelectedLanguage);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $this->makeLanguageMenu($view);
        $this->makeActionMenu($view, $tsConfig);
        $this->makeButtons($view, $request, $tsConfig);
        $this->initializeClipboard($request);
        $event = $this->eventDispatcher->dispatch(new ModifyPageLayoutContentEvent($request, $view));

        $configuration = $pageLayoutContext->getDrawingConfiguration();
        $configuration->setDefaultLanguageBinding(!empty($tsConfig['mod.']['web_layout.']['defLangBinding']));
        $configuration->setActiveColumns($this->getActiveColumnsArray($pageLayoutContext, $tsConfig));
        $configuration->setShowHidden((bool)($this->moduleData?->get('showHidden') ?? true));
        $configuration->setLanguageColumns($this->MOD_MENU['language']);
        $configuration->setShowNewContentWizard(empty($tsConfig['mod.']['web_layout.']['disableNewContentElementWizard']));
        $configuration->setSelectedLanguageId($this->currentSelectedLanguage);
        if ((int)($this->moduleData?->get('function') ?? 0) === 2) {
            $configuration->setLanguageMode(true);
        }
        $mainLayoutHtml = $this->backendLayoutRenderer->drawContent($request, $pageLayoutContext);
        $numberOfHiddenElements = $this->getNumberOfHiddenElements($configuration->getLanguageColumns());

        $view->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'), $this->pageinfo['title']);
        $view->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        $view->assignMultiple([
            'pageId' => $this->id,
            'infoBoxes' => $this->generateMessagesForCurrentPage(),
            'isPageEditable' => $this->isPageEditable($this->currentSelectedLanguage),
            'localizedPageTitle' => $this->getLocalizedPageTitle($this->currentSelectedLanguage, $this->pageinfo),
            'eventContentHtmlTop' => $event->getHeaderContent(),
            'mainContentHtml' => $mainLayoutHtml,
            'hiddenElementsShowToggle' => ($backendUser->check('tables_select', 'tt_content') && ($numberOfHiddenElements > 0)),
            'hiddenElementsState' => (bool)($this->moduleData?->get('showHidden') ?? true),
            'hiddenElementsCount' => $numberOfHiddenElements,
            'eventContentHtmlBottom' => $event->getFooterContent(),
        ]);
        return $view->renderResponse('PageLayout/PageModule');
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
                1 => $languageService->getLL('m_function_1'),
                2 => $languageService->getLL('m_function_2'),
            ],
            'language' => [
                0 => $languageService->getLL('m_default'),
            ],
        ];

        // First, select all localized page records on the current page.
        // Each represents a possibility for a language on the page. Add these to language selector.
        if ($this->id) {
            // Compile language data for pid != 0 only. The language drop-down is not shown on pid 0
            // since pid 0 can't be localized.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));
            $statement = $queryBuilder->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                    )
                )->executeQuery();
            while ($pageTranslation = $statement->fetchAssociative()) {
                $languageId = $pageTranslation[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
                if (isset($this->availableLanguages[$languageId])) {
                    $this->MOD_MENU['language'][$languageId] = $this->availableLanguages[$languageId]->getTitle();
                }
            }
            // Override the label
            if (isset($this->availableLanguages[0])) {
                $this->MOD_MENU['language'][0] = $this->availableLanguages[0]->getTitle();
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
        if ($this->moduleData?->cleanUp($this->MOD_MENU)) {
            $backendUser->pushModuleData('web_layout', $this->moduleData->toArray());
        }
        if ($backendUser->workspace !== 0) {
            // Show all elements in draft workspaces
            $this->moduleData?->set('showHidden', true);
        }
        if ((int)($this->moduleData?->get('function') ?? 1) !== 2) {
            // Remove -1 (all) from the module menu if not "languages" mode
            unset($this->MOD_MENU['language'][-1]);
            // In case -1 (all) is still set as language, but we are no longer in
            // "languages" mode, we fall back to the default, preventing an empty grid.
            if ((int)($this->moduleData?->get('language') ?? 0) === -1) {
                $this->moduleData?->set('language', 0);
            }
        }
    }

    /**
     * This creates the dropdown menu with the different actions this module is able to provide.
     * For now, they are Columns and Languages.
     */
    protected function makeActionMenu(ModuleTemplate $view, array $tsConfig): void
    {
        $languageService = $this->getLanguageService();
        $actions = [
            1 => $languageService->getLL('m_function_1'),
        ];
        // Find if there are ANY languages at all (and if not, do not show the language option from function menu).
        // The second check is for an edge case: Only two languages in the site and the default is not allowed.
        if (count($this->availableLanguages) > 1 || (int)array_key_first($this->availableLanguages) > 0) {
            $actions[2] = $languageService->getLL('m_function_2');
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
            if ((int)($this->moduleData?->get('function') ?? 1) === $key) {
                $menuItem->setActive(true);
                $defaultKey = null;
            }
            $actionMenu->addMenuItem($menuItem);
        }
        if (isset($defaultKey)) {
            $this->moduleData?->set('function', $defaultKey);
        }
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($actionMenu);
    }

    /**
     * Return an array of various messages for the current page record,
     * such as if the page has a special doktype, that can be rendered as info boxes.
     */
    protected function generateMessagesForCurrentPage(): array
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();

        $infoBoxes = [];
        $currentDocumentType = (int)$this->pageinfo['doktype'];
        if ($currentDocumentType === PageRepository::DOKTYPE_SYSFOLDER && $this->moduleProvider->accessGranted('web_list', $backendUser)) {
            $infoBoxes[] = [
                'title' => $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModule'),
                'message' => ''
                    . '<p>' . $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModuleMessage') . '</p>'
                    . '<a class="btn btn-info" data-dispatch-action="TYPO3.ModuleMenu.showModule" data-dispatch-args-list="web_list">'
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
                // Store the current group access clause and unset it afterwards since it should
                // not be used while searching for configured shortcut pages. Actually ->getPage()
                // would allow to disable it via an argument. However, getMenu() currently does not.
                // @todo Refactor as soon as ->getMenu() allows to dynamically disable group access check
                $tempGroupAccess = $this->pageRepository->where_groupAccess;
                $this->pageRepository->where_groupAccess = '';
                switch ($shortcutMode) {
                    case PageRepository::SHORTCUT_MODE_NONE:
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage($this->pageinfo['shortcut']));
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredOrNotAccessibleInternalLinkMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:
                        $menuOfPages = $this->pageRepository->getMenu($this->pageinfo['uid'], '*', 'sorting', 'AND hidden = 0');
                        $targetPage = reset($menuOfPages) ?: [];
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredFirstSubpageMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_PARENT_PAGE:
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage($this->pageinfo['pid']));
                        $message = $targetPage === [] ? $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredParentPageMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE:
                        $possibleTargetPages = $this->pageRepository->getMenu($this->pageinfo['uid'], '*', 'sorting', 'AND hidden = 0');
                        if ($possibleTargetPages === []) {
                            $message = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredOrNotAccessibleRandomInternalLinkMessage');
                        } else {
                            $message = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsRandomInternalLinkMessage');
                            $state = InfoboxViewHelper::STATE_INFO;
                        }
                        break;
                }
                $this->pageRepository->where_groupAccess = $tempGroupAccess;
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
                'title' => $this->pageinfo['title'],
                'message' => $message,
                'state' => $state,
            ];
        }
        if ($currentDocumentType === PageRepository::DOKTYPE_LINK) {
            if (empty($this->pageinfo['url'])) {
                $infoBoxes[] = [
                    'title' => $this->pageinfo['title'],
                    'message' => $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsMisconfiguredExternalLinkMessage'),
                    'state' => InfoboxViewHelper::STATE_ERROR,
                ];
            } else {
                $externalUrl = $this->pageRepository->getExtURL($this->pageinfo);
                if (is_string($externalUrl)) {
                    $externalUrl = htmlspecialchars($externalUrl);
                    $externalUrlHtml = '<a href="' . $externalUrl . '" target="_blank" rel="noreferrer">' . $externalUrl . '</a>';
                    $infoBoxes[] = [
                        'title' => $this->pageinfo['title'],
                        'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:pageIsExternalLinkMessage'), $externalUrlHtml),
                        'state' => InfoboxViewHelper::STATE_INFO,
                    ];
                }
            }
        }
        if ($this->pageinfo['content_from_pid']) {
            // If content from different pid is displayed
            $contentPage = (array)BackendUtility::getRecord('pages', (int)$this->pageinfo['content_from_pid']);
            $linkToPid = $this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $this->pageinfo['content_from_pid']]);
            $title = BackendUtility::getRecordTitle('pages', $contentPage);
            $link = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($title) . ' (PID ' . (int)$this->pageinfo['content_from_pid'] . ')</a>';
            $infoBoxes[] = [
                'title' => $title,
                'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_from_pid_title'), $link),
                'state' => InfoboxViewHelper::STATE_INFO,
            ];
        } else {
            $links = $this->getPageLinksWhereContentIsAlsoShownOn((int)$this->pageinfo['uid']);
            if (!empty($links)) {
                $infoBoxes[] = [
                    'title' => '',
                    'message' => sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content_on_pid_title'), $links),
                    'state' => InfoboxViewHelper::STATE_INFO,
                ];
            }
        }
        return $infoBoxes;
    }

    protected function addJavaScriptModuleInstructions(int $pageId, PageLayoutContext $pageLayoutContext, int $currentSelectedLanguage): void
    {
        $pageActionsInstruction = JavaScriptModuleInstruction::create('TYPO3/CMS/Backend/PageActions.js');
        if ($pageLayoutContext->isPageEditable()) {
            $languageOverlayId = 0;
            $pageLocalizationRecord = BackendUtility::getRecordLocalization('pages', $pageId, $currentSelectedLanguage);
            if (is_array($pageLocalizationRecord)) {
                $pageLocalizationRecord = reset($pageLocalizationRecord);
            }
            if (!empty($pageLocalizationRecord['uid'])) {
                $languageOverlayId = $pageLocalizationRecord['uid'];
            }
            $pageActionsInstruction
                ->invoke('setPageId', $this->id)
                ->invoke('setLanguageOverlayId', $languageOverlayId);
        }
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($pageActionsInstruction);
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
            ->where($queryBuilder->expr()->eq('content_from_pid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)));
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
        $backendUser = $this->getBackendUser();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $backendUser->workspace));
        $localizedPage = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter($currentSelectedLanguage, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        BackendUtility::workspaceOL('pages', $localizedPage);
        return $localizedPage['title'];
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
            $pasteTitle = BackendUtility::getRecordTitle('tt_content', $pasteRecord, false, true);
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('TYPO3/CMS/Backend/LayoutModule/Paste.js')
                    ->assign([
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
        $allowedColumnPositionsByTsConfig = array_unique(GeneralUtility::intExplode(',', $tsConfig['mod.']['SHARED.']['colPos_list'] ?? '', true));
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

        // Add CSH
        $contextSensitiveHelpButton = $buttonBar->makeHelpButton()
            ->setModuleName('_MOD_web_layout')
            ->setFieldName('columns_' . $this->moduleData?->get('function'));
        $buttonBar->addButton($contextSensitiveHelpButton);

        // View page
        // Exclude sysfolders, spacers and recycler by default
        $excludeDokTypes = [
            PageRepository::DOKTYPE_RECYCLER,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_SPACER,
        ];
        // Exclude sysfolders, spacers and recycler by default, but allow custom overrides via tsConfig
        if (isset($tsConfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
            $excludeDokTypes = GeneralUtility::intExplode(',', $tsConfig['TCEMAIN.']['preview.']['disableButtonForDokType'], true);
        }
        if (
            $this->currentSelectedLanguage !== -1
            && !in_array((int)$this->pageinfo['doktype'], $excludeDokTypes, true)
            && !VersionState::cast($this->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $languageParameter = $this->currentSelectedLanguage ? ('&L=' . $this->currentSelectedLanguage) : '';
            $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageinfo['uid'])
                ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
                ->withAdditionalQueryParameters($languageParameter)
                ->buildDispatcherDataAttributes();
            $viewButton = $buttonBar->makeLinkButton()
                ->setDataAttributes($previewDataAttributes ?? [])
                ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
                ->setHref('#');
            $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_layout')
            ->setDisplayName($this->getShortcutTitle())
            ->setArguments([
                'id' => $this->id,
                'showHidden' => (bool)($this->moduleData?->get('showHidden') ?? true),
                'function' => (int)($this->moduleData?->get('function') ?? 1),
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

        // Edit page properties
        if ($this->isPageEditable(0)) {
            $url = (string)$this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        'pages' => [
                            $this->id => 'edit',
                        ],
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ]
            );
            $editPageButton = $buttonBar->makeLinkButton()
                ->setHref($url)
                ->setTitle($languageService->getLL('editPageProperties'))
                ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
            $buttonBar->addButton($editPageButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }

        // Edit page properties of page language overlay (Only when one specific language is selected)
        if ((int)($this->moduleData?->get('function') ?? 1) === 1
            && $this->currentSelectedLanguage > 0
            && $this->isPageEditable($this->currentSelectedLanguage)
        ) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
            $overlayRecord = $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($this->currentSelectedLanguage, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            BackendUtility::workspaceOL('pages', $overlayRecord, $this->getBackendUser()->workspace);
            $url = (string)$this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        'pages' => [
                            $overlayRecord['uid'] => 'edit',
                        ],
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ]
            );
            $editLanguageButton = $buttonBar->makeLinkButton()
                ->setHref($url)
                ->setTitle($languageService->getLL('editPageLanguageOverlayProperties'))
                ->setIcon($this->iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL));
            $buttonBar->addButton($editLanguageButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }
    }

    /**
     * Returns the number of hidden elements (including those hidden by start/end times)
     * on the current page (for the current site language)
     */
    protected function getNumberOfHiddenElements(array $languageColumns): int
    {
        $andWhere = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));
        $queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                )
            );
        if (!empty($languageColumns)) {
            // Multi-language view is active
            if ($this->currentSelectedLanguage > 0) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->in(
                        'sys_language_uid',
                        [0, $queryBuilder->createNamedParameter($this->currentSelectedLanguage, \PDO::PARAM_INT)]
                    )
                );
            }
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($this->currentSelectedLanguage, \PDO::PARAM_INT)
                )
            );
        }
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'])) {
            $andWhere[] = $queryBuilder->expr()->neq(
                'hidden',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
        }
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['starttime'])) {
            $andWhere[] = $queryBuilder->expr()->andX(
                $queryBuilder->expr()->neq(
                    'starttime',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'starttime',
                    $queryBuilder->createNamedParameter($GLOBALS['SIM_ACCESS_TIME'], \PDO::PARAM_INT)
                )
            );
        }
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['endtime'])) {
            $andWhere[] = $queryBuilder->expr()->andX(
                $queryBuilder->expr()->neq(
                    'endtime',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->lte(
                    'endtime',
                    $queryBuilder->createNamedParameter($GLOBALS['SIM_ACCESS_TIME'], \PDO::PARAM_INT)
                )
            );
        }
        if (!empty($andWhere)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(...$andWhere)
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
        return $this->pageinfo !== []
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
        return !$this->pageinfo['editlock']
            && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)
            && $this->getBackendUser()->check('tables_modify', 'tt_content')
            && $this->getBackendUser()->checkLanguageAccess($languageId);
    }

    /**
     * Make the LanguageMenu.
     */
    protected function makeLanguageMenu(ModuleTemplate $view): void
    {
        if (count($this->MOD_MENU['language']) > 1) {
            $languageMenu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('languageMenu');
            foreach ($this->MOD_MENU['language'] as $key => $language) {
                $menuItem = $languageMenu
                    ->makeMenuItem()
                    ->setTitle($language)
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $this->id, 'language' => $key]));
                if ($this->currentSelectedLanguage === $key) {
                    $menuItem->setActive(true);
                }
                $languageMenu->addMenuItem($menuItem);
            }
            $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        }
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

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
