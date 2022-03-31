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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Domain\Model\Element\ImmediateActionElement;
use TYPO3\CMS\Backend\Module\ModuleLoader;
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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Script Class for Web > Layout module
 */
class PageLayoutController
{
    /**
     * Page Id for which to make the listing
     *
     * @var int
     * @internal
     */
    public $id;

    /**
     * Module TSconfig
     *
     * @var array
     */
    protected $modTSconfig = [];

    /**
     * Module shared TSconfig
     *
     * @var array
     */
    protected $modSharedTSconfig = [];

    /**
     * Current ids page record
     *
     * @var array|bool
     * @internal
     */
    public $pageinfo;

    /**
     * List of column-integers to edit. Is set from TSconfig, default is "1,0,2,3"
     *
     * @var string
     */
    protected $colPosList = '';

    /**
     * Currently selected language for editing content elements
     *
     * @var int
     */
    protected $current_sys_language;

    /**
     * Menu configuration
     *
     * @var array
     */
    protected $MOD_MENU = [];

    /**
     * Module settings (session variable)
     *
     * @var array
     * @internal
     */
    public $MOD_SETTINGS = [];

    /**
     * List of column-integers accessible to the current BE user.
     * Is set from TSconfig, default is $colPosList
     *
     * @var string
     */
    protected $activeColPosList;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_layout';

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var ButtonBar
     */
    protected $buttonBar;

    /**
     * @var string
     */
    protected $searchContent;

    /**
     * @var SiteLanguage[]
     */
    protected $availableLanguages;

    /**
     * @var PageLayoutContext|null
     */
    protected $context;

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected PageRepository $pageRepository;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        PageRepository $pageRepository,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->pageRepository = $pageRepository;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }
    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        // Setting module configuration / page select clause
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);

        // Load page info array
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if ($this->pageinfo !== false) {
            // If page info is not resolved, user has no access or the ID parameter was malformed.
            $this->context = GeneralUtility::makeInstance(
                PageLayoutContext::class,
                $this->pageinfo,
                GeneralUtility::makeInstance(BackendLayoutView::class)->getBackendLayoutForPage($this->id)
            );
        }

        /** @var SiteInterface $currentSite */
        $currentSite = $request->getAttribute('site');
        $this->availableLanguages = $currentSite->getAvailableLanguages($this->getBackendUser(), false, $this->id);
        // initialize page/be_user TSconfig settings
        $pageTsConfig = BackendUtility::getPagesTSconfig($this->id);
        $this->modSharedTSconfig['properties'] = $pageTsConfig['mod.']['SHARED.'] ?? [];
        $this->modTSconfig['properties'] = $pageTsConfig['mod.']['web_layout.'] ?? [];

        // Initialize menu
        $this->menuConfig($request);
        // Setting sys language from session var
        $this->current_sys_language = (int)$this->MOD_SETTINGS['language'];
        // Create LanguageMenu
        $this->makeLanguageMenu();
        // Make action menu from available actions
        $this->makeActionMenu();

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/ClearCache');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/NewContentElementWizardButton');

        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $this->pageinfo['title'] ?? ''
        );

        $this->main($request);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Initialize menu array
     * @param ServerRequestInterface $request
     */
    protected function menuConfig(ServerRequestInterface $request): void
    {
        // MENU-ITEMS:
        $this->MOD_MENU = [
            'tt_content_showHidden' => '',
            'function' => [
                1 => $this->getLanguageService()->getLL('m_function_1'),
                2 => $this->getLanguageService()->getLL('m_function_2'),
            ],
            'language' => [
                0 => $this->getLanguageService()->getLL('m_default'),
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
                // We need to add -1 (all) here so a possible -1 value in &SET['language'] will be respected
                // by BackendUtility::getModuleData. Actually, this is only relevant if we are dealing with the
                // "languages" mode, which however can only be determined, after the MOD_SETTINGS have been calculated
                // by BackendUtility::getModuleData => chicken and egg problem. We therefore remove the -1 item from
                // the menu again, as soon as we are able to determine the requested mode.
                // @todo Replace the whole "mode" handling with some more robust solution
                $this->MOD_MENU['language'][-1] = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages');
            }
        }
        // Clean up settings
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $request->getParsedBody()['SET'] ?? $request->getQueryParams()['SET'] ?? [], $this->moduleName);
        // For all elements to be shown in draft workspaces & to also show hidden elements by default if user hasn't disabled the option
        if ($this->getBackendUser()->workspace != 0
            || !isset($this->MOD_SETTINGS['tt_content_showHidden'])
            || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0'
        ) {
            $this->MOD_SETTINGS['tt_content_showHidden'] = 1;
        }
        if ((int)$this->MOD_SETTINGS['function'] !== 2) {
            // Remove -1 (all) from the module menu if not "languages" mode
            unset($this->MOD_MENU['language'][-1]);
            // In case -1 (all) is still set as language, but we are no longer in
            // "languages" mode, we fall back to the default, preventing an empty grid.
            if ((int)$this->MOD_SETTINGS['language'] === -1) {
                $this->MOD_SETTINGS['language'] = 0;
            }
        }
    }

    /**
     * Initializes the available actions this module provides
     *
     * @return array the available actions
     */
    protected function initActions(): array
    {
        $actions = [
            1 => $this->getLanguageService()->getLL('m_function_1'),
        ];
        // Find if there are ANY languages at all (and if not, do not show the language option from function menu).
        // The second check is for an edge case: Only two languages in the site and the default is not allowed.
        if (count($this->availableLanguages) > 1 || (int)array_key_first($this->availableLanguages) > 0) {
            $actions[2] = $this->getLanguageService()->getLL('m_function_2');
        }
        // Page / user TSconfig blinding of menu-items
        $blindActions = $this->modTSconfig['properties']['menu.']['functions.'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $actions)) {
                unset($actions[$key]);
            }
        }

        return $actions;
    }

    /**
     * This creates the dropdown menu with the different actions this module is able to provide.
     * For now they are Columns and Languages.
     */
    protected function makeActionMenu(): void
    {
        $actions = $this->initActions();
        $actionMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $actionMenu->setIdentifier('actionMenu');
        $actionMenu->setLabel('');

        $defaultKey = null;
        $foundDefaultKey = false;
        foreach ($actions as $key => $action) {
            $menuItem = $actionMenu
                ->makeMenuItem()
                ->setTitle($action)
                ->setHref((string)$this->uriBuilder->buildUriFromRoute($this->moduleName, ['id' => $this->id, 'SET' => ['function' => $key]]));

            if (!$foundDefaultKey) {
                $defaultKey = $key;
                $foundDefaultKey = true;
            }
            if ((int)$this->MOD_SETTINGS['function'] === $key) {
                $menuItem->setActive(true);
                $defaultKey = null;
            }
            $actionMenu->addMenuItem($menuItem);
        }
        if (isset($defaultKey)) {
            $this->MOD_SETTINGS['function'] = $defaultKey;
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($actionMenu);
    }

    /**
     * Generate various messages (rendered as callouts) for the current page record (such as if the page has a special doktype).
     *
     * @return string HTML content with messages
     */
    protected function generateMessagesForCurrentPage(): string
    {
        $content = '';
        $lang = $this->getLanguageService();

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));

        // If page is a folder
        if ($this->pageinfo['doktype'] == PageRepository::DOKTYPE_SYSFOLDER) {
            $moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
            $moduleLoader->load($GLOBALS['TBE_MODULES']);
            $modules = $moduleLoader->getModules();
            if (is_array($modules['web']['sub']['list'] ?? null)) {
                $title = $lang->getLL('goToListModule');
                $message = '<p>' . $lang->getLL('goToListModuleMessage') . '</p>';
                $message .= '<a class="btn btn-info" data-dispatch-action="TYPO3.ModuleMenu.showModule" data-dispatch-args-list="web_list">'
                    . $lang->getLL('goToListModule') . '</a>';
                $view->assignMultiple([
                    'title' => $title,
                    'message' => $message,
                    'state' => InfoboxViewHelper::STATE_INFO,
                ]);
                $content .= $view->render();
            }
        } elseif ($this->pageinfo['doktype'] === PageRepository::DOKTYPE_SHORTCUT) {
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
                        $message .= $targetPage === [] ? $lang->getLL('pageIsMisconfiguredOrNotAccessibleInternalLinkMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:
                        $menuOfPages = $this->pageRepository->getMenu($this->pageinfo['uid'], '*', 'sorting', 'AND hidden = 0');
                        $targetPage = reset($menuOfPages) ?: [];
                        $message .= $targetPage === [] ? $lang->getLL('pageIsMisconfiguredFirstSubpageMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_PARENT_PAGE:
                        $targetPage = $this->getTargetPageIfVisible($this->pageRepository->getPage($this->pageinfo['pid']));
                        $message .= $targetPage === [] ? $lang->getLL('pageIsMisconfiguredParentPageMessage') : '';
                        break;
                    case PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE:
                        $possibleTargetPages = $this->pageRepository->getMenu($this->pageinfo['uid'], '*', 'sorting', 'AND hidden = 0');
                        if ($possibleTargetPages === []) {
                            $message .= $lang->getLL('pageIsMisconfiguredOrNotAccessibleRandomInternalLinkMessage');
                            break;
                        }
                        $message = $lang->getLL('pageIsRandomInternalLinkMessage');
                        $state = InfoboxViewHelper::STATE_INFO;
                        break;
                }
                $this->pageRepository->where_groupAccess = $tempGroupAccess;
                $message = htmlspecialchars($message);
                if ($targetPage !== [] && $shortcutMode !== PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE) {
                    $linkToPid = $this->uriBuilder->buildUriFromRoute($this->moduleName, ['id' => $targetPage['uid']]);
                    $path = BackendUtility::getRecordPath($targetPage['uid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 1000);
                    $linkedPath = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($path) . '</a>';
                    $message .= sprintf(htmlspecialchars($lang->getLL('pageIsInternalLinkMessage')), $linkedPath);
                    $message .= ' (' . htmlspecialchars($lang->sL(BackendUtility::getLabelFromItemlist('pages', 'shortcut_mode', (string)$shortcutMode))) . ')';
                    $state = InfoboxViewHelper::STATE_INFO;
                }
            } else {
                $message = htmlspecialchars($lang->getLL('pageIsMisconfiguredInternalLinkMessage'));
                $state = InfoboxViewHelper::STATE_ERROR;
            }

            $view->assignMultiple([
                'title' => $this->pageinfo['title'],
                'message' => $message,
                'state' => $state,
            ]);
            $content .= $view->render();
        } elseif ($this->pageinfo['doktype'] === PageRepository::DOKTYPE_LINK) {
            if (empty($this->pageinfo['url'])) {
                $view->assignMultiple([
                    'title' => $this->pageinfo['title'],
                    'message' => $lang->getLL('pageIsMisconfiguredExternalLinkMessage'),
                    'state' => InfoboxViewHelper::STATE_ERROR,
                ]);
                $content .= $view->render();
            } else {
                $externalUrl = $this->pageRepository->getExtURL($this->pageinfo);
                if (is_string($externalUrl)) {
                    $externalUrl = htmlspecialchars($externalUrl);
                    $externalUrlHtml = '<a href="' . $externalUrl . '" target="_blank" rel="noreferrer">' . $externalUrl . '</a>';
                    $view->assignMultiple([
                        'title' => $this->pageinfo['title'],
                        'message' => sprintf($lang->getLL('pageIsExternalLinkMessage'), $externalUrlHtml),
                        'state' => InfoboxViewHelper::STATE_INFO,
                    ]);
                    $content .= $view->render();
                }
            }
        }
        // If content from different pid is displayed
        if ($this->pageinfo['content_from_pid']) {
            $contentPage = (array)BackendUtility::getRecord('pages', (int)$this->pageinfo['content_from_pid']);
            $linkToPid = $this->uriBuilder->buildUriFromRoute($this->moduleName, ['id' => $this->pageinfo['content_from_pid']]);
            $title = BackendUtility::getRecordTitle('pages', $contentPage);
            $link = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($title) . ' (PID ' . (int)$this->pageinfo['content_from_pid'] . ')</a>';
            $message = sprintf($lang->getLL('content_from_pid_title'), $link);
            $view->assignMultiple([
                'title' => $title,
                'message' => $message,
                'state' => InfoboxViewHelper::STATE_INFO,
            ]);
            $content .= $view->render();
        } else {
            $links = $this->getPageLinksWhereContentIsAlsoShownOn($this->pageinfo['uid']);
            if (!empty($links)) {
                $message = sprintf($lang->getLL('content_on_pid_title'), $links);
                $view->assignMultiple([
                    'title' => '',
                    'message' => $message,
                    'state' => InfoboxViewHelper::STATE_INFO,
                ]);
                $content .= $view->render();
            }
        }
        return $content;
    }

    /**
     * Get all pages with links where the content of a page $pageId is also shown on
     *
     * @param int $pageId
     * @return string
     */
    protected function getPageLinksWhereContentIsAlsoShownOn($pageId): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('content_from_pid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)));

        $links = [];
        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $linkToPid = $this->uriBuilder->buildUriFromRoute($this->moduleName, ['id' =>  $row['uid']]);
                $title = BackendUtility::getRecordTitle('pages', $row);
                $link = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($title) . ' (PID ' . (int)$row['uid'] . ')</a>';
                $links[] = $link;
            }
        }
        return implode(', ', $links);
    }

    /**
     * @return string $title
     */
    protected function getLocalizedPageTitle(): string
    {
        if ($this->current_sys_language > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));
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
                        $queryBuilder->createNamedParameter($this->current_sys_language, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            BackendUtility::workspaceOL('pages', $localizedPage);
            return $localizedPage['title'];
        }
        return $this->pageinfo['title'];
    }

    /**
     * Main function.
     * Creates some general objects and calls other functions for the main rendering of module content.
     *
     * @param ServerRequestInterface $request
     */
    protected function main(ServerRequestInterface $request): void
    {
        $content = '';
        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        if ($this->id && is_array($this->pageinfo)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
            $content .= ImmediateActionElement::moduleStateUpdateWithCurrentMount('web', (int)$this->id, true);
            if ($this->context instanceof PageLayoutContext) {
                $backendLayout = $this->context->getBackendLayout();

                // Find backend layout / columns
                if (!empty($backendLayout->getColumnPositionNumbers())) {
                    $this->colPosList = implode(',', $backendLayout->getColumnPositionNumbers());
                }
                // Removing duplicates, if any
                $colPosArray = array_unique(GeneralUtility::intExplode(',', $this->colPosList));
                // Accessible columns
                if (isset($this->modSharedTSconfig['properties']['colPos_list']) && trim($this->modSharedTSconfig['properties']['colPos_list']) !== '') {
                    $activeColPosArray = array_unique(GeneralUtility::intExplode(',', trim($this->modSharedTSconfig['properties']['colPos_list'])));
                    // Match with the list which is present in the colPosList for the current page
                    if (!empty($colPosArray) && !empty($activeColPosArray)) {
                        $activeColPosArray = array_unique(array_intersect(
                            $colPosArray,
                            $activeColPosArray
                        ));
                    }
                } else {
                    $activeColPosArray = $colPosArray;
                }
                $this->activeColPosList = implode(',', $activeColPosArray);
                $this->colPosList = implode(',', $colPosArray);
            }

            $content .= $this->generateMessagesForCurrentPage();

            // Render the primary module content:
            $content .= '<form action="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute($this->moduleName, ['id' => $this->id])) . '" id="PageLayoutController" method="post">';
            // Page title
            $content .= '<h1 class="' . ($this->isPageEditable($this->current_sys_language) ? 't3js-title-inlineedit' : '') . '">' . htmlspecialchars($this->getLocalizedPageTitle()) . '</h1>';
            // All other listings
            $content .= $this->renderContent();
            $content .= '</form>';
            // Setting up the buttons for the docheader
            $this->makeButtons($request);
            $this->initializeClipboard($request);
        } else {
            $content .= ImmediateActionElement::moduleStateUpdate('web', (int)$this->id);
            $content .= '<h1>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . '</h1>';
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));
            $view->assignMultiple([
                'title' => $this->getLanguageService()->getLL('clickAPage_header'),
                'message' => $this->getLanguageService()->getLL('clickAPage_content'),
                'state' => InfoboxViewHelper::STATE_INFO,
            ]);
            $content .= $view->render();
        }
        // Set content
        $this->moduleTemplate->setContent($content);
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
        if (!empty($elFromTable) && $this->isContentEditable($this->current_sys_language)) {
            $pasteItem = (int)substr((string)key($elFromTable), 11);
            $pasteRecord = BackendUtility::getRecordWSOL('tt_content', $pasteItem);
            $pasteTitle = BackendUtility::getRecordTitle('tt_content', $pasteRecord, false, true);
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/LayoutModule/Paste')
                    ->assign([
                        'itemOnClipboardUid' => $pasteItem,
                        'itemOnClipboardTitle' => $pasteTitle,
                        'copyMode' => $clipboard->clipData['normal']['mode'] ?? '',
                    ])
            );
        }
    }

    /**
     * Rendering content
     *
     * @return string
     */
    protected function renderContent(): string
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Localization');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LayoutModule/DragDrop');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->pageRenderer->loadRequireJsModule(ImmediateActionElement::MODULE_NAME);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');

        $tableOutput = '';
        $numberOfHiddenElements = 0;

        if ($this->context instanceof PageLayoutContext) {
            // Context may not be set, which happens if the page module is viewed by a user with no access to the
            // current page, or if the ID parameter is malformed. In this case we do not resolve any backend layout
            // or other page structure information and we do not render any "table output" for the module.
            $configuration = $this->context->getDrawingConfiguration();
            $configuration->setDefaultLanguageBinding(!empty($this->modTSconfig['properties']['defLangBinding']));
            $configuration->setActiveColumns(GeneralUtility::trimExplode(',', $this->activeColPosList, true));
            $configuration->setShowHidden((bool)$this->MOD_SETTINGS['tt_content_showHidden']);
            $configuration->setLanguageColumns($this->MOD_MENU['language']);
            $configuration->setShowNewContentWizard(empty($this->modTSconfig['properties']['disableNewContentElementWizard']));
            $configuration->setSelectedLanguageId((int)$this->MOD_SETTINGS['language']);
            if ($this->MOD_SETTINGS['function'] == 2) {
                $configuration->setLanguageMode(true);
            }

            $numberOfHiddenElements = $this->getNumberOfHiddenElements($configuration->getLanguageColumns());

            $pageActionsInstruction = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/PageActions');
            if ($this->context->isPageEditable()) {
                $languageOverlayId = 0;
                $pageLocalizationRecord = BackendUtility::getRecordLocalization('pages', $this->id, (int)$this->current_sys_language);
                if (is_array($pageLocalizationRecord)) {
                    $pageLocalizationRecord = reset($pageLocalizationRecord);
                }
                if (!empty($pageLocalizationRecord['uid'])) {
                    $languageOverlayId = $pageLocalizationRecord['uid'];
                }
                $pageActionsInstruction
                    ->invoke('setPageId', (int)$this->id)
                    ->invoke('setLanguageOverlayId', $languageOverlayId);
            }
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($pageActionsInstruction);
            $tableOutput = GeneralUtility::makeInstance(BackendLayoutRenderer::class, $this->context)->drawContent();
        }

        if ($this->getBackendUser()->check('tables_select', 'tt_content') && $numberOfHiddenElements > 0) {
            // Toggle hidden ContentElements
            $tableOutput .= '
                <div class="form-check">
                    <input type="checkbox" id="checkTt_content_showHidden" class="form-check-input" name="SET[tt_content_showHidden]" value="1" ' . ($this->MOD_SETTINGS['tt_content_showHidden'] ? 'checked="checked"' : '') . ' />
                    <label class="form-check-label" for="checkTt_content_showHidden">
                        ' . htmlspecialchars($this->getLanguageService()->getLL('hiddenCE')) . ' (<span class="t3js-hidden-counter">' . $numberOfHiddenElements . '</span>)
                    </label>
                </div>';
        }

        // Init the content
        $content = '';
        // Additional header content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'] ?? [] as $hook) {
            $params = [];
            $content .= GeneralUtility::callUserFunction($hook, $params, $this);
        }
        $content .= $tableOutput;

        // Additional footer content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook'] ?? [] as $hook) {
            $params = [];
            $content .= GeneralUtility::callUserFunction($hook, $params, $this);
        }
        return $content;
    }

    /**
     * Make the ModuleTemplate public accessible for the use in hooks.
     *
     * @return ModuleTemplate
     */
    public function getModuleTemplate(): ModuleTemplate
    {
        return $this->moduleTemplate;
    }

    /***************************
     *
     * Sub-content functions, rendering specific parts of the module content.
     *
     ***************************/
    /**
     * This creates the buttons for the modules
     * @param ServerRequestInterface $request
     */
    protected function makeButtons(ServerRequestInterface $request): void
    {
        // Add CSH (Context Sensitive Help) icon to tool bar
        $contextSensitiveHelpButton = $this->buttonBar->makeHelpButton()
            ->setModuleName('_MOD_' . $this->moduleName)
            ->setFieldName('columns_' . $this->MOD_SETTINGS['function']);
        $this->buttonBar->addButton($contextSensitiveHelpButton);
        $lang = $this->getLanguageService();
        // View page
        $pageTsConfig = BackendUtility::getPagesTSconfig($this->id);
        // Exclude sysfolders, spacers and recycler by default
        $excludeDokTypes = [
            PageRepository::DOKTYPE_RECYCLER,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_SPACER,
        ];
        // Custom override of values
        if (isset($pageTsConfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
            $excludeDokTypes = GeneralUtility::intExplode(
                ',',
                $pageTsConfig['TCEMAIN.']['preview.']['disableButtonForDokType'],
                true
            );
        }

        if (
            $this->current_sys_language !== -1
            && !in_array((int)$this->pageinfo['doktype'], $excludeDokTypes, true)
            && !VersionState::cast($this->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $languageParameter = $this->current_sys_language ? ('&L=' . $this->current_sys_language) : '';
            $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageinfo['uid'])
                ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
                ->withAdditionalQueryParameters($languageParameter)
                ->buildDispatcherDataAttributes();
            $viewButton = $this->buttonBar->makeLinkButton()
                ->setDataAttributes($previewDataAttributes ?? [])
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
                ->setHref('#');

            $this->buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }
        // Shortcut
        $shortcutButton = $this->buttonBar->makeShortcutButton()
            ->setRouteIdentifier($this->moduleName)
            ->setDisplayName($this->getShortcutTitle())
            ->setArguments([
                'id' => (int)$this->id,
                'SET' => [
                    'tt_content_showHidden' => (bool)$this->MOD_SETTINGS['tt_content_showHidden'],
                    'function' => (int)$this->MOD_SETTINGS['function'],
                    'language' => (int)$this->current_sys_language,
                ],
            ]);
        $this->buttonBar->addButton($shortcutButton);

        // Cache
        $clearCacheButton = $this->buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes(['id' => $this->pageinfo['uid']])
            ->setClasses('t3js-clear-page-cache')
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
            ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
        $this->buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);

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
            $editPageButton = $this->buttonBar->makeLinkButton()
                ->setHref($url)
                ->setTitle($lang->getLL('editPageProperties'))
                ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
            $this->buttonBar->addButton($editPageButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }

        // Edit page properties of page language overlay (Only when one specific language is selected)
        if ((int)$this->MOD_SETTINGS['function'] === 1
            && $this->current_sys_language > 0
            && $this->isPageEditable($this->current_sys_language)
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
                        $queryBuilder->createNamedParameter($this->current_sys_language, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            BackendUtility::workspaceOL('pages', $overlayRecord, $this->getBackendUser()->workspace);
            // Edit button
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
            $editLanguageButton = $this->buttonBar->makeLinkButton()
                ->setHref($url)
                ->setTitle($lang->getLL('editPageLanguageOverlayProperties'))
                ->setIcon($this->iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL));
            $this->buttonBar->addButton($editLanguageButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }
    }

    /*******************************
     *
     * Other functions
     *
     ******************************/
    /**
     * Returns the number of hidden elements (including those hidden by start/end times)
     * on the current page (for the current sys_language)
     *
     * @param array $languageColumns
     * @return int
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
            if ($this->current_sys_language > 0) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->in(
                        'sys_language_uid',
                        [0, $queryBuilder->createNamedParameter($this->current_sys_language, \PDO::PARAM_INT)]
                    )
                );
            }
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($this->current_sys_language, \PDO::PARAM_INT)
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
     * Check if page can be edited by current user
     *
     * @param int $languageId
     * @return bool
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
     *
     * @param int $languageId
     * @return bool
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
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Make the LanguageMenu
     */
    protected function makeLanguageMenu(): void
    {
        if (count($this->MOD_MENU['language']) > 1) {
            $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('languageMenu');
            foreach ($this->MOD_MENU['language'] as $key => $language) {
                $menuItem = $languageMenu
                    ->makeMenuItem()
                    ->setTitle($language)
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute($this->moduleName, ['id' => $this->id, 'SET' => ['language' => $key]]));
                if ((int)$this->current_sys_language === $key) {
                    $menuItem->setActive(true);
                }
                $languageMenu->addMenuItem($menuItem);
            }
            $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        }
    }

    /**
     * Returns the target page if visible
     *
     * @param array $targetPage
     *
     * @return array
     */
    protected function getTargetPageIfVisible(array $targetPage): array
    {
        return !(bool)($targetPage['hidden'] ?? false) ? $targetPage : [];
    }

    /**
     * Returns the shortcut title for the current page
     *
     * @return string
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
}
