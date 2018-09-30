<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Script Class for Web > Layout module
 */
class PageLayoutController
{
    use PublicMethodDeprecationTrait;
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'init' => 'Using PageLayoutController::init() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'main' => 'Using PageLayoutController::main() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'menuConfig' => 'Using PageLayoutController::menuConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'renderContent' => 'Using PageLayoutController::renderContent() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'clearCache' => 'Using PageLayoutController::clearCache() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getModuleTemplate' => 'Using PageLayoutController::getModuleTemplate() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getLocalizedPageTitle' => 'Using PageLayoutController::getLocalizedPageTitle() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getNumberOfHiddenElements' => 'Using PageLayoutController::getNumberOfHiddenElements() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'local_linkThisScript' => 'Using PageLayoutController::local_linkThisScript() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'pageIsNotLockedForEditors' => 'Using PageLayoutController::pageIsNotLockedForEditors() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'contentIsNotLockedForEditors' => 'Using PageLayoutController::contentIsNotLockedForEditors() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'pointer' => 'Using PageLayoutController::$pointer is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'imagemode' => 'Using PageLayoutController::$imagemode is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'search_field' => 'Using PageLayoutController::$search_field is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'search_levels' => 'Using PageLayoutController::$search_levels is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'showLimit' => 'Using PageLayoutController::$showLimit is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'returnUrl' => 'Using PageLayoutController::$returnUrl is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'clear_cache' => 'Using PageLayoutController::$clear_cache is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'popView' => 'Using PageLayoutController::$popView is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'perms_clause' => 'Using PageLayoutController::$perms_clause is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modTSconfig' => 'Using PageLayoutController::$modTSconfig is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modSharedTSconfig' => 'Using PageLayoutController::$modSharedTSconfig is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'descrTable' => 'Using PageLayoutController::$descrTable is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'colPosList' => 'Using PageLayoutController::$colPosList is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'EDIT_CONTENT' => 'Using PageLayoutController::$EDIT_CONTENT is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'CALC_PERMS' => 'Using PageLayoutController::$CALC_PERMS is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'current_sys_language' => 'Using PageLayoutController::$current_sys_language is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'MCONF' => 'Using PageLayoutController::$MCONF is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'MOD_MENU' => 'Using PageLayoutController::$MOD_MENU is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'content' => 'Using PageLayoutController::$content is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'activeColPosList' => 'Using PageLayoutController::$activeColPosList is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * Page Id for which to make the listing
     *
     * @var int
     * @internal
     */
    public $id;

    /**
     * Pointer - for browsing list of records.
     *
     * @var int
     */
    protected $pointer;

    /**
     * Thumbnails or not
     *
     * @var string
     */
    protected $imagemode;

    /**
     * Search-fields
     *
     * @var string
     */
    protected $search_field;

    /**
     * Search-levels
     *
     * @var int
     */
    protected $search_levels;

    /**
     * Show-limit
     *
     * @var int
     */
    protected $showLimit;

    /**
     * Return URL
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * Clear-cache flag - if set, clears page cache for current id.
     *
     * @var bool
     */
    protected $clear_cache;

    /**
     * PopView id - for opening a window with the page
     *
     * @var bool
     */
    protected $popView;

    /**
     * Page select perms clause
     *
     * @var string
     */
    protected $perms_clause;

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
     * @var array
     * @internal
     */
    public $pageinfo;

    /**
     * "Pseudo" Description -table name
     *
     * @var string
     */
    protected $descrTable;

    /**
     * List of column-integers to edit. Is set from TSconfig, default is "1,0,2,3"
     *
     * @var string
     */
    protected $colPosList;

    /**
     * Flag: If content can be edited or not.
     *
     * @var bool
     */
    protected $EDIT_CONTENT;

    /**
     * Users permissions integer for this page.
     *
     * @var int
     */
    protected $CALC_PERMS;

    /**
     * Currently selected language for editing content elements
     *
     * @var int
     */
    protected $current_sys_language;

    /**
     * Module configuration
     *
     * @var array
     */
    protected $MCONF = [];

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
     * Module output accumulation
     *
     * @var string
     */
    protected $content;

    /**
     * List of column-integers accessible to the current BE user.
     * Is set from TSconfig, default is $colPosList
     *
     * @var string
     */
    protected $activeColPosList;

    /**
     * @var string
     */
    protected $editSelect;

    /**
     * Caches the available languages in a colPos
     *
     * @var array
     */
    protected $languagesInColumnCache = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

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
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $GLOBALS['SOBE'] = $this;
        $this->init($request);
        $this->clearCache();
        $this->main($request);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Initializing the module
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request = null): void
    {
        $request = $request ?: $GLOBALS['TYPO3_REQUEST'];
        // Set the GPvars from outside
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        // Setting module configuration / page select clause
        $this->MCONF['name'] = $this->moduleName;
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        // Get session data
        $sessionData = $this->getBackendUser()->getSessionData(__CLASS__);
        $this->search_field = !empty($sessionData['search_field']) ? $sessionData['search_field'] : '';

        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $this->pointer = $parsedBody['pointer'] ?? $queryParams['pointer'] ?? null;
        $this->imagemode = $parsedBody['imagemode'] ?? $queryParams['imagemode'] ?? null;
        $this->clear_cache = $parsedBody['clear_cache'] ?? $queryParams['clear_cache'] ?? null;
        $this->popView = $parsedBody['popView'] ?? $queryParams['popView'] ?? null;
        $this->search_field = $parsedBody['search_field'] ?? $queryParams['search_field'] ?? null;
        $this->search_levels = $parsedBody['search_levels'] ?? $queryParams['search_levels'] ?? null;
        $this->showLimit = $parsedBody['showLimit'] ?? $queryParams['showLimit'] ?? null;
        $returnUrl = $parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? null;
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($returnUrl);

        $sessionData['search_field'] = $this->search_field;
        // Store session data
        $this->getBackendUser()->setAndSaveSessionData(__CLASS__, $sessionData);
        // Load page info array:
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        // Initialize menu
        $this->menuConfig($request);
        // Setting sys language from session var:
        $this->current_sys_language = (int)$this->MOD_SETTINGS['language'];
        // CSH / Descriptions:
        $this->descrTable = '_MOD_' . $this->moduleName;
    }

    /**
     * Initialize menu array
     * @param ServerRequestInterface $request
     */
    protected function menuConfig(ServerRequestInterface $request = null): void
    {
        $request = $request ?: $GLOBALS['TYPO3_REQUEST'];
        // Set the GPvars from outside
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        /** @var SiteInterface $currentSite */
        $currentSite = $request->getAttribute('site');
        $availableLanguages = $currentSite->getAvailableLanguages($this->getBackendUser(), false, $this->id);

        $lang = $this->getLanguageService();
        // MENU-ITEMS:
        $this->MOD_MENU = [
            'tt_content_showHidden' => '',
            'function' => [
                1 => $lang->getLL('m_function_1'),
                2 => $lang->getLL('m_function_2')
            ],
            'language' => [
                0 => $lang->getLL('m_default')
            ]
        ];
        // initialize page/be_user TSconfig settings
        $pageTsConfig = BackendUtility::getPagesTSconfig($this->id);
        $this->modSharedTSconfig['properties'] = $pageTsConfig['mod.']['SHARED.'] ?? [];
        $this->modTSconfig['properties'] = $pageTsConfig['mod.']['web_layout.'] ?? [];

        // First, select all localized page records on the current page.
        // Each represents a possibility for a language on the page. Add these to language selector.
        if ($this->id) {
            // Compile language data for pid != 0 only. The language drop-down is not shown on pid 0
            // since pid 0 can't be localized.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
            $statement = $queryBuilder->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                    )
                )->execute();
            while ($pageTranslation = $statement->fetch()) {
                $languageId = $pageTranslation[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
                if (isset($availableLanguages[$languageId])) {
                    $this->MOD_MENU['language'][$languageId] = $availableLanguages[$languageId]->getTitle();
                }
            }
            // Override the label
            if (isset($availableLanguages[0])) {
                $this->MOD_MENU['language'][0] = $availableLanguages[0]->getTitle();
            }
        }
        // Initialize the available actions
        $actions = $this->initActions();
        // Clean up settings
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $parsedBody['SET'] ?? $queryParams['SET'] ?? [], $this->moduleName);
        // For all elements to be shown in draft workspaces & to also show hidden elements by default if user hasn't disabled the option
        if ($this->getBackendUser()->workspace != 0
            || !isset($this->MOD_SETTINGS['tt_content_showHidden'])
            || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0'
        ) {
            $this->MOD_SETTINGS['tt_content_showHidden'] = 1;
        }
        // Make action menu from available actions
        $this->makeActionMenu($actions);
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
            2 => $this->getLanguageService()->getLL('m_function_2')
        ];
        // Find if there are ANY languages at all (and if not, remove the language option from function menu).
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        if ($this->getBackendUser()->isAdmin()) {
            $queryBuilder->getRestrictions()->removeAll();
        }

        $count = $queryBuilder
            ->count('uid')
            ->from('sys_language')
            ->execute()
            ->fetchColumn(0);

        if (!$count) {
            unset($actions['2']);
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
     * For now they are Columns, Quick Edit and Languages.
     *
     * @param array $actions array with the available actions
     */
    protected function makeActionMenu(array $actions): void
    {
        $actionMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $actionMenu->setIdentifier('actionMenu');
        $actionMenu->setLabel('');

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $defaultKey = null;
        $foundDefaultKey = false;
        foreach ($actions as $key => $action) {
            $menuItem = $actionMenu
                ->makeMenuItem()
                ->setTitle($action)
                ->setHref((string)$uriBuilder->buildUriFromRoute($this->moduleName) . '&id=' . $this->id . '&SET[function]=' . $key);

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
     * Clears page cache for the current id, $this->id
     */
    protected function clearCache(): void
    {
        if ($this->clear_cache && !empty($this->pageinfo)) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], []);
            $dataHandler->clear_cacheCmd($this->id);
        }
    }

    /**
     * Generate the flashmessages for current pid
     *
     * @return string HTML content with flashmessages
     */
    protected function getHeaderFlashMessagesForCurrentPid(): string
    {
        $content = '';
        $lang = $this->getLanguageService();

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));

        // If page is a folder
        if ($this->pageinfo['doktype'] == PageRepository::DOKTYPE_SYSFOLDER) {
            $moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
            $moduleLoader->load($GLOBALS['TBE_MODULES']);
            $modules = $moduleLoader->modules;
            if (is_array($modules['web']['sub']['list'])) {
                $title = $lang->getLL('goToListModule');
                $message = '<p>' . $lang->getLL('goToListModuleMessage') . '</p>';
                $message .= '<a class="btn btn-info" href="javascript:top.goToModule(\'web_list\',1);">' . $lang->getLL('goToListModule') . '</a>';
                $view->assignMultiple([
                    'title' => $title,
                    'message' => $message,
                    'state' => InfoboxViewHelper::STATE_INFO
                ]);
                $content .= $view->render();
            }
        } elseif ($this->pageinfo['doktype'] === PageRepository::DOKTYPE_SHORTCUT) {
            $shortcutMode = (int)$this->pageinfo['shortcut_mode'];
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $targetPage = [];

            if ($this->pageinfo['shortcut'] || $shortcutMode) {
                switch ($shortcutMode) {
                    case PageRepository::SHORTCUT_MODE_NONE:
                        $targetPage = $pageRepository->getPage($this->pageinfo['shortcut']);
                        break;
                    case PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:
                        $targetPage = reset($pageRepository->getMenu($this->pageinfo['shortcut'] ?: $this->pageinfo['uid']));
                        break;
                    case PageRepository::SHORTCUT_MODE_PARENT_PAGE:
                        $targetPage = $pageRepository->getPage($this->pageinfo['pid']);
                        break;
                }

                $message = '';
                if ($shortcutMode === PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE) {
                    $message .= sprintf($lang->getLL('pageIsRandomInternalLinkMessage'));
                } else {
                    $linkToPid = $this->local_linkThisScript(['id' => $targetPage['uid']]);
                    $path = BackendUtility::getRecordPath($targetPage['uid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 1000);
                    $linkedPath = '<a href="' . htmlspecialchars($linkToPid) . '">' . htmlspecialchars($path) . '</a>';
                    $message .= sprintf($lang->getLL('pageIsInternalLinkMessage'), $linkedPath);
                }

                $message .= ' (' . htmlspecialchars($lang->sL(BackendUtility::getLabelFromItemlist('pages', 'shortcut_mode', $shortcutMode))) . ')';

                $view->assignMultiple([
                    'title' => $this->pageinfo['title'],
                    'message' => $message,
                    'state' => InfoboxViewHelper::STATE_INFO
                ]);
                $content .= $view->render();
            } else {
                if (empty($targetPage) && $shortcutMode !== PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE) {
                    $view->assignMultiple([
                        'title' => $this->pageinfo['title'],
                        'message' => $lang->getLL('pageIsMisconfiguredInternalLinkMessage'),
                        'state' => InfoboxViewHelper::STATE_ERROR
                    ]);
                    $content .= $view->render();
                }
            }
        } elseif ($this->pageinfo['doktype'] === PageRepository::DOKTYPE_LINK) {
            if (empty($this->pageinfo['url'])) {
                $view->assignMultiple([
                    'title' => $this->pageinfo['title'],
                    'message' => $lang->getLL('pageIsMisconfiguredExternalLinkMessage'),
                    'state' => InfoboxViewHelper::STATE_ERROR
                ]);
                $content .= $view->render();
            } else {
                $externalUrl = htmlspecialchars(GeneralUtility::makeInstance(PageRepository::class)->getExtURL($this->pageinfo));
                if ($externalUrl !== false) {
                    $externalUrlHtml = '<a href="' . $externalUrl . '" target="_blank" rel="noopener">' . $externalUrl . '</a>';
                    $view->assignMultiple([
                        'title' => $this->pageinfo['title'],
                        'message' => sprintf($lang->getLL('pageIsExternalLinkMessage'), $externalUrlHtml),
                        'state' => InfoboxViewHelper::STATE_INFO
                    ]);
                    $content .= $view->render();
                }
            }
        }
        // If content from different pid is displayed
        if ($this->pageinfo['content_from_pid']) {
            $contentPage = BackendUtility::getRecord('pages', (int)$this->pageinfo['content_from_pid']);
            $linkToPid = $this->local_linkThisScript(['id' => $this->pageinfo['content_from_pid']]);
            $title = BackendUtility::getRecordTitle('pages', $contentPage);
            $link = '<a href="' . htmlspecialchars($linkToPid) . '">' . htmlspecialchars($title) . ' (PID ' . (int)$this->pageinfo['content_from_pid'] . ')</a>';
            $message = sprintf($lang->getLL('content_from_pid_title'), $link);
            $view->assignMultiple([
                'title' => $title,
                'message' => $message,
                'state' => InfoboxViewHelper::STATE_INFO
            ]);
            $content .= $view->render();
        } else {
            $links = $this->getPageLinksWhereContentIsAlsoShownOn($this->pageinfo['uid']);
            if (!empty($links)) {
                $message = sprintf($lang->getLL('content_on_pid_title'), $links);
                $view->assignMultiple([
                    'title' => '',
                    'message' => $message,
                    'state' => InfoboxViewHelper::STATE_INFO
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
        $rows = $queryBuilder->execute()->fetchAll();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $linkToPid = $this->local_linkThisScript(['id' => $row['uid']]);
                $title = BackendUtility::getRecordTitle('pages', $row);
                $link = '<a href="' . htmlspecialchars($linkToPid) . '">' . htmlspecialchars($title) . ' (PID ' . (int)$row['uid'] . ')</a>';
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
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
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
                ->execute()
                ->fetch();
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
    protected function main(ServerRequestInterface $request = null): void
    {
        $request = $request ?: $GLOBALS['TYPO3_REQUEST'];
        $lang = $this->getLanguageService();
        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $access = is_array($this->pageinfo);
        // Content
        $content = '';
        if ($this->id && $access) {
            // Initialize permission settings:
            $this->CALC_PERMS = $this->getBackendUser()->calcPerms($this->pageinfo);
            $this->EDIT_CONTENT = $this->isContentEditable();

            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);

            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

            $this->moduleTemplate->addJavaScriptCode('mainJsFunctions', '
                if (top.fsMod) {
                    top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
                    top.fsMod.navFrameHighlightedID["web"] = top.fsMod.currentBank + "_" + ' . (int)$this->id . ';
                }
                ' . ($this->popView ? BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id)) : '') . '
                function deleteRecord(table,id,url) {   //
                    window.location.href = ' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('tce_db') . '&cmd[')
                                            . ' + table + "][" + id + "][delete]=1&redirect=" + encodeURIComponent(url);
                    return false;
                }
            ');

            // Find backend layout / columns
            $backendLayout = GeneralUtility::callUserFunction(BackendLayoutView::class . '->getSelectedBackendLayout', $this->id, $this);
            if (!empty($backendLayout['__colPosList'])) {
                $this->colPosList = implode(',', $backendLayout['__colPosList']);
            }
            // Removing duplicates, if any
            $this->colPosList = array_unique(GeneralUtility::intExplode(',', $this->colPosList));
            // Accessible columns
            if (isset($this->modSharedTSconfig['properties']['colPos_list']) && trim($this->modSharedTSconfig['properties']['colPos_list']) !== '') {
                $this->activeColPosList = array_unique(GeneralUtility::intExplode(',', trim($this->modSharedTSconfig['properties']['colPos_list'])));
                // Match with the list which is present in the colPosList for the current page
                if (!empty($this->colPosList) && !empty($this->activeColPosList)) {
                    $this->activeColPosList = array_unique(array_intersect(
                        $this->activeColPosList,
                        $this->colPosList
                    ));
                }
            } else {
                $this->activeColPosList = $this->colPosList;
            }
            $this->activeColPosList = implode(',', $this->activeColPosList);
            $this->colPosList = implode(',', $this->colPosList);

            $content .= $this->getHeaderFlashMessagesForCurrentPid();

            // Render the primary module content:
            if ($this->MOD_SETTINGS['function'] == 1 || $this->MOD_SETTINGS['function'] == 2) {
                $content .= '<form action="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute($this->moduleName, ['id' => $this->id, 'imagemode' =>  $this->imagemode])) . '" id="PageLayoutController" method="post">';
                // Page title
                $content .= '<h1 class="t3js-title-inlineedit">' . htmlspecialchars($this->getLocalizedPageTitle()) . '</h1>';
                // All other listings
                $content .= $this->renderContent();
            }
            $content .= '</form>';
            $content .= $this->searchContent;
            // Setting up the buttons for the docheader
            $this->makeButtons($request);

            // Create LanguageMenu
            $this->makeLanguageMenu();
        } else {
            $this->moduleTemplate->addJavaScriptCode(
                'mainJsFunctions',
                'if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';'
            );
            $content .= '<h1>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . '</h1>';
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));
            $view->assignMultiple([
                'title' => $lang->getLL('clickAPage_header'),
                'message' => $lang->getLL('clickAPage_content'),
                'state' => InfoboxViewHelper::STATE_INFO
            ]);
            $content .= $view->render();
        }
        // Set content
        $this->moduleTemplate->setContent($content);
    }

    /**
     * Rendering content
     *
     * @return string
     */
    protected function renderContent(): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $dbList = GeneralUtility::makeInstance(PageLayoutView::class);
        $dbList->thumbs = $this->imagemode;
        $dbList->no_noWrap = 1;
        $dbList->descrTable = $this->descrTable;
        $this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
        $dbList->script = (string)$uriBuilder->buildUriFromRoute($this->moduleName);
        $dbList->showIcon = 0;
        $dbList->setLMargin = 0;
        $dbList->doEdit = $this->EDIT_CONTENT;
        $dbList->ext_CALC_PERMS = $this->CALC_PERMS;
        $dbList->agePrefixes = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears');
        $dbList->id = $this->id;
        $dbList->nextThree = MathUtility::forceIntegerInRange($this->modTSconfig['properties']['editFieldsAtATime'], 0, 10);
        $dbList->option_newWizard = empty($this->modTSconfig['properties']['disableNewContentElementWizard']);
        $dbList->defLangBinding = !empty($this->modTSconfig['properties']['defLangBinding']);
        if (!$dbList->nextThree) {
            $dbList->nextThree = 1;
        }
        // Create menu for selecting a table to jump to (this is, if more than just pages/tt_content elements are found on the page!)
        // also fills $dbList->activeTables
        $dbList->getTableMenu($this->id);
        // Initialize other variables:
        $tableOutput = [];
        $tableJSOutput = [];
        $CMcounter = 0;
        // Traverse the list of table names which has records on this page (that array is populated
        // by the $dblist object during the function getTableMenu()):
        foreach ($dbList->activeTables as $table => $value) {
            $h_func = '';
            $h_func_b = '';
            if (!isset($dbList->externalTables[$table])) {
                // Boolean: Display up/down arrows and edit icons for tt_content records
                $dbList->tt_contentConfig['showCommands'] = 1;
                // Boolean: Display info-marks or not
                $dbList->tt_contentConfig['showInfo'] = 1;
                // Setting up the tt_content columns to show:
                if (is_array($GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['items'])) {
                    $colList = [];
                    $tcaItems = GeneralUtility::callUserFunction(BackendLayoutView::class . '->getColPosListItemsParsed', $this->id, $this);
                    foreach ($tcaItems as $temp) {
                        $colList[] = $temp[1];
                    }
                } else {
                    // ... should be impossible that colPos has no array. But this is the fallback should it make any sense:
                    $colList = ['1', '0', '2', '3'];
                }
                if ($this->colPosList !== '') {
                    $colList = array_intersect(GeneralUtility::intExplode(',', $this->colPosList), $colList);
                }
                // The order of the rows: Default is left(1), Normal(0), right(2), margin(3)
                $dbList->tt_contentConfig['cols'] = implode(',', $colList);
                $dbList->tt_contentConfig['activeCols'] = $this->activeColPosList;
                $dbList->tt_contentConfig['showHidden'] = $this->MOD_SETTINGS['tt_content_showHidden'];
                $dbList->tt_contentConfig['sys_language_uid'] = (int)$this->current_sys_language;
                // If the function menu is set to "Language":
                if ($this->MOD_SETTINGS['function'] == 2) {
                    $dbList->tt_contentConfig['languageMode'] = 1;
                    $dbList->tt_contentConfig['languageCols'] = $this->MOD_MENU['language'];
                    $dbList->tt_contentConfig['languageColsPointer'] = $this->current_sys_language;
                }
                // Toggle hidden ContentElements
                $numberOfHiddenElements = $this->getNumberOfHiddenElements($dbList->tt_contentConfig);
                if ($numberOfHiddenElements > 0) {
                    $h_func_b = '
                        <div class="checkbox">
                            <label for="checkTt_content_showHidden">
                                <input type="checkbox" id="checkTt_content_showHidden" class="checkbox" name="SET[tt_content_showHidden]" value="1" ' . ($this->MOD_SETTINGS['tt_content_showHidden'] ? 'checked="checked"' : '') . ' />
                                ' . htmlspecialchars($this->getLanguageService()->getLL('hiddenCE')) . ' (<span class="t3js-hidden-counter">' . $numberOfHiddenElements . '</span>)
                            </label>
                        </div>';
                }
            } else {
                if (isset($this->MOD_SETTINGS) && isset($this->MOD_MENU)) {
                    $h_func = BackendUtility::getFuncMenu($this->id, 'SET[' . $table . ']', $this->MOD_SETTINGS[$table], $this->MOD_MENU[$table], '', '');
                }
            }
            // Start the dblist object:
            $dbList->itemsLimitSingleTable = 1000;
            $dbList->start($this->id, $table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
            $dbList->counter = $CMcounter;
            $dbList->ext_function = $this->MOD_SETTINGS['function'];
            // Generate the list of elements here:
            $dbList->generateList();
            // Adding the list content to the tableOutput variable:
            $tableOutput[$table] = $h_func . $dbList->HTMLcode . $h_func_b;
            // ... and any accumulated JavaScript goes the same way!
            $tableJSOutput[$table] = $dbList->JScode;
            // Increase global counter:
            $CMcounter += $dbList->counter;
            // Reset variables after operation:
            $dbList->HTMLcode = '';
            $dbList->JScode = '';
        }
        // END: traverse tables
        // For Context Sensitive Menus:
        // Init the content
        $content = '';
        // Additional header content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'] ?? [] as $hook) {
            $params = [];
            $content .= GeneralUtility::callUserFunction($hook, $params, $this);
        }
        // Add the content for each table we have rendered (traversing $tableOutput variable)
        foreach ($tableOutput as $table => $output) {
            $content .= $output;
        }
        // Making search form:
        if (!$this->modTSconfig['properties']['disableSearchBox'] && ($dbList->counter > 0 || $this->currentPageHasSubPages())) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');
            $toggleSearchFormButton = $this->buttonBar->makeLinkButton()
                ->setClasses('t3js-toggle-search-toolbox')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.searchIcon'))
                ->setIcon($this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL))
                ->setHref('#');
            $this->buttonBar->addButton($toggleSearchFormButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
            $this->searchContent = $dbList->getSearchBox();
        }
        // Additional footer content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook'] ?? [] as $hook) {
            $params = [];
            $content .= GeneralUtility::callUserFunction($hook, $params, $this);
        }
        return $content;
    }

    /**
     * @return ModuleTemplate
     */
    protected function getModuleTemplate(): ModuleTemplate
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
        if ($this->MOD_SETTINGS['function'] == 1 || $this->MOD_SETTINGS['function'] == 2) {
            // Add CSH (Context Sensitive Help) icon to tool bar
            $contextSensitiveHelpButton = $this->buttonBar->makeHelpButton()
                ->setModuleName($this->descrTable)
                ->setFieldName('columns_' . $this->MOD_SETTINGS['function']);
            $this->buttonBar->addButton($contextSensitiveHelpButton);
        }
        $lang = $this->getLanguageService();
        // View page
        if (!VersionState::cast($this->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
            $viewButton = $this->buttonBar->makeLinkButton()
                ->setOnClick(BackendUtility::viewOnClick($this->pageinfo['uid'], '', BackendUtility::BEgetRootLine($this->pageinfo['uid'])))
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
                ->setHref('#');

            $this->buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }
        // Shortcut
        $shortcutButton = $this->buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setGetVariables([
                'id',
                'route',
                'edit_record',
                'pointer',
                'new_unique_uid',
                'search_field',
                'search_levels',
                'showLimit'
            ])
            ->setSetVariables(array_keys($this->MOD_MENU));
        $this->buttonBar->addButton($shortcutButton);

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // Cache
        if (empty($this->modTSconfig['properties']['disableAdvanced'])) {
            $clearCacheButton = $this->buttonBar->makeLinkButton()
                ->setHref((string)$uriBuilder->buildUriFromRoute($this->moduleName, ['id' => $this->pageinfo['uid'], 'clear_cache' => '1']))
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
            $this->buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
        }
        if (empty($this->modTSconfig['properties']['disableIconToolbar'])) {
            // Edit page properties and page language overlay icons
            if ($this->isPageEditable() && $this->getBackendUser()->checkLanguageAccess(0)) {
                /** @var \TYPO3\CMS\Core\Http\NormalizedParams */
                $normalizedParams = $request->getAttribute('normalizedParams');
                // Edit localized pages only when one specific language is selected
                if ($this->MOD_SETTINGS['function'] == 1 && $this->current_sys_language > 0) {
                    $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
                    $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('pages');
                    $queryBuilder->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                        ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
                    $overlayRecord = $queryBuilder
                        ->select('uid')
                        ->from('pages')
                        ->where(
                            $queryBuilder->expr()->eq(
                                $localizationParentField,
                                $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                $languageField,
                                $queryBuilder->createNamedParameter($this->current_sys_language, \PDO::PARAM_INT)
                            )
                        )
                        ->setMaxResults(1)
                        ->execute()
                        ->fetch();
                    // Edit button
                    $urlParameters = [
                        'edit' => [
                            'pages' => [
                                $overlayRecord['uid'] => 'edit'
                            ]
                        ],
                        'returnUrl' => $normalizedParams->getRequestUri(),
                    ];

                    $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    $editLanguageButton = $this->buttonBar->makeLinkButton()
                        ->setHref($url)
                        ->setTitle($lang->getLL('editPageLanguageOverlayProperties'))
                        ->setIcon($this->iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL));
                    $this->buttonBar->addButton($editLanguageButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
                }
                $urlParameters = [
                    'edit' => [
                        'pages' => [
                            $this->id => 'edit'
                        ]
                    ],
                    'returnUrl' => $normalizedParams->getRequestUri(),
                ];
                $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $editPageButton = $this->buttonBar->makeLinkButton()
                    ->setHref($url)
                    ->setTitle($lang->getLL('editPageProperties'))
                    ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
                $this->buttonBar->addButton($editPageButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
            }
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
     * @param array $contentConfig
     * @return int
     */
    protected function getNumberOfHiddenElements(array $contentConfig = []): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        $queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                )
            );

        if (!empty($contentConfig['languageCols']) && is_array($contentConfig['languageCols'])) {
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
            ->execute()
            ->fetchColumn(0);

        return (int)$count;
    }

    /**
     * Returns URL to the current script.
     * In particular the "popView" and "new_unique_uid" Get vars are unset.
     *
     * @param array $params Parameters array, merged with global GET vars.
     * @return string URL
     */
    protected function local_linkThisScript($params): string
    {
        $params['popView'] = '';
        $params['new_unique_uid'] = '';
        return GeneralUtility::linkThisScript($params);
    }

    /**
     * Check if page can be edited by current user
     *
     * @return bool
     */
    protected function isPageEditable(): bool
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        return !$this->pageinfo['editlock'] && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::PAGE_EDIT);
    }

    /**
     * Check if page can be edited by current user
     *
     * @return bool
     */
    protected function pageIsNotLockedForEditors(): bool
    {
        return $this->isPageEditable();
    }

    /**
     * Check if content can be edited by current user
     *
     * @return bool
     */
    protected function isContentEditable(): bool
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        return !$this->pageinfo['editlock'] && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT);
    }

    /**
     * Check if content can be edited by current user
     *
     * @return bool
     */
    protected function contentIsNotLockedForEditors(): bool
    {
        return $this->isContentEditable();
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns current PageRenderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Make the LanguageMenu
     */
    protected function makeLanguageMenu(): void
    {
        if (count($this->MOD_MENU['language']) > 1) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('languageMenu');
            foreach ($this->MOD_MENU['language'] as $key => $language) {
                $menuItem = $languageMenu
                    ->makeMenuItem()
                    ->setTitle($language)
                    ->setHref((string)$uriBuilder->buildUriFromRoute($this->moduleName) . '&id=' . $this->id . '&SET[language]=' . $key);
                if ((int)$this->current_sys_language === $key) {
                    $menuItem->setActive(true);
                }
                $languageMenu->addMenuItem($menuItem);
            }
            $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        }
    }

    /**
     * Checks whether the current page has sub pages
     *
     * @return bool
     */
    protected function currentPageHasSubPages(): bool
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        // get workspace id
        $workspaceId = (int)$this->getBackendUser()->workspace;
        $comparisonExpression = $workspaceId === 0 ? 'neq' : 'eq';

        $count = $queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->{$comparisonExpression}(
                    'pid',
                    $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        return (bool)$count;
    }
}
