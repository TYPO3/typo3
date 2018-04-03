<?php
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
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Recordlist\RecordList;

/**
 * Script Class for Web > Layout module
 */
class PageLayoutController
{
    /**
     * Page Id for which to make the listing
     *
     * @var int
     */
    public $id;

    /**
     * Pointer - for browsing list of records.
     *
     * @var int
     */
    public $pointer;

    /**
     * Thumbnails or not
     *
     * @var string
     */
    public $imagemode;

    /**
     * Search-fields
     *
     * @var string
     */
    public $search_field;

    /**
     * Search-levels
     *
     * @var int
     */
    public $search_levels;

    /**
     * Show-limit
     *
     * @var int
     */
    public $showLimit;

    /**
     * Return URL
     *
     * @var string
     */
    public $returnUrl;

    /**
     * Clear-cache flag - if set, clears page cache for current id.
     *
     * @var bool
     */
    public $clear_cache;

    /**
     * PopView id - for opening a window with the page
     *
     * @var bool
     */
    public $popView;

    /**
     * QuickEdit: Variable, that tells quick edit what to show/edit etc.
     * Format is [tablename]:[uid] with some exceptional values for both parameters (with special meanings).
     *
     * @var string
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public $edit_record;

    /**
     * QuickEdit: If set, this variable tells quick edit that the last edited record had
     * this value as UID and we should look up the new, real uid value in sys_log.
     *
     * @var string
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public $new_unique_uid;

    /**
     * Page select perms clause
     *
     * @var string
     */
    public $perms_clause;

    /**
     * Module TSconfig
     *
     * @var array
     */
    public $modTSconfig;

    /**
     * Module shared TSconfig
     *
     * @var array
     */
    public $modSharedTSconfig;

    /**
     * Current ids page record
     *
     * @var array
     */
    public $pageinfo;

    /**
     * "Pseudo" Description -table name
     *
     * @var string
     */
    public $descrTable;

    /**
     * List of column-integers to edit. Is set from TSconfig, default is "1,0,2,3"
     *
     * @var string
     */
    public $colPosList;

    /**
     * Flag: If content can be edited or not.
     *
     * @var bool
     */
    public $EDIT_CONTENT;

    /**
     * Users permissions integer for this page.
     *
     * @var int
     */
    public $CALC_PERMS;

    /**
     * Currently selected language for editing content elements
     *
     * @var int
     */
    public $current_sys_language;

    /**
     * Module configuration
     *
     * @var array
     */
    public $MCONF = [];

    /**
     * Menu configuration
     *
     * @var array
     */
    public $MOD_MENU = [];

    /**
     * Module settings (session variable)
     *
     * @var array
     */
    public $MOD_SETTINGS = [];

    /**
     * Array of tables to be listed by the Web > Page module in addition to the default tables
     *
     * @var array
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public $externalTables = [];

    /**
     * Module output accumulation
     *
     * @var string
     */
    public $content;

    /**
     * List of column-integers accessible to the current BE user.
     * Is set from TSconfig, default is $colPosList
     *
     * @var string
     */
    public $activeColPosList;

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
     * Initializing the module
     */
    public function init()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->iconFactory = $this->moduleTemplate->getIconFactory();
        $this->buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        // Setting module configuration / page select clause
        $this->MCONF['name'] = $this->moduleName;
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        // Get session data
        $sessionData = $this->getBackendUser()->getSessionData(RecordList::class);
        $this->search_field = !empty($sessionData['search_field']) ? $sessionData['search_field'] : '';
        // GPvars:
        $this->id = (int)GeneralUtility::_GP('id');
        $this->pointer = GeneralUtility::_GP('pointer');
        $this->imagemode = GeneralUtility::_GP('imagemode');
        $this->clear_cache = GeneralUtility::_GP('clear_cache');
        $this->popView = GeneralUtility::_GP('popView');
        $this->edit_record = GeneralUtility::_GP('edit_record');
        $this->new_unique_uid = GeneralUtility::_GP('new_unique_uid');
        $this->search_field = GeneralUtility::_GP('search_field');
        $this->search_levels = GeneralUtility::_GP('search_levels');
        $this->showLimit = GeneralUtility::_GP('showLimit');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        $this->externalTables = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'];
        $sessionData['search_field'] = $this->search_field;
        // Store session data
        $this->getBackendUser()->setAndSaveSessionData(RecordList::class, $sessionData);
        // Load page info array:
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        // Initialize menu
        $this->menuConfig();
        // Setting sys language from session var:
        $this->current_sys_language = (int)$this->MOD_SETTINGS['language'];
        // CSH / Descriptions:
        $this->descrTable = '_MOD_' . $this->moduleName;
    }

    /**
     * Initialize menu array
     */
    public function menuConfig()
    {
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
        $this->modSharedTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.SHARED');
        $this->modTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.' . $this->moduleName);
        // example settings:
        //  $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tx_myext'] =
        //      array ('default' => array(
        //              'MENU' => 'LLL:EXT:tx_myext/locallang_db.xlf:menuDefault',
        //              'fList' =>  'title,description,image',
        //              'icon' => TRUE));
        if (is_array($this->externalTables)) {
            if (!empty($this->externalTables)) {
                GeneralUtility::deprecationLog(
                    'The rendering of records in the page module by using '
                    . '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'cms\'][\'db_layout\'][\'addTables\']'
                    . ' has been deprecated since TYPO3 CMS 8 and will be removed in TYPO3 CMS 9.'
                );
            }
            foreach ($this->externalTables as $table => $tableSettings) {
                // delete the default settings from above
                if (is_array($this->MOD_MENU[$table])) {
                    unset($this->MOD_MENU[$table]);
                }
                if (is_array($tableSettings) && count($tableSettings) > 1) {
                    foreach ($tableSettings as $key => $settings) {
                        $this->MOD_MENU[$table][$key] = $lang->sL($settings['MENU']);
                    }
                }
            }
        }
        // First, select all pages_language_overlay records on the current page. Each represents a possibility for a language on the page. Add these to language selector.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $queryBuilder->getRestrictions()->removeAll();
        if ($this->id) {
            $queryBuilder->select('sys_language.uid AS uid', 'sys_language.title AS title')
                ->from('sys_language')
                ->join(
                    'sys_language',
                    'pages_language_overlay',
                    'pages_language_overlay',
                    $queryBuilder->expr()->eq(
                        'sys_language.uid',
                        $queryBuilder->quoteIdentifier('pages_language_overlay.sys_language_uid')
                    )
                )
                ->where(
                    $queryBuilder->expr()->eq(
                        'pages_language_overlay.deleted',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'pages_language_overlay.pid',
                        $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->gte(
                            'pages_language_overlay.t3ver_state',
                            $queryBuilder->createNamedParameter(
                                (string)new VersionState(VersionState::DEFAULT_STATE),
                                \PDO::PARAM_INT
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            'pages_language_overlay.t3ver_wsid',
                            $queryBuilder->createNamedParameter($this->getBackendUser()->workspace, \PDO::PARAM_INT)
                        )
                    )
                )
                ->groupBy(
                    'pages_language_overlay.sys_language_uid',
                    'sys_language.uid',
                    'sys_language.pid',
                    'sys_language.tstamp',
                    'sys_language.hidden',
                    'sys_language.title',
                    'sys_language.language_isocode',
                    'sys_language.static_lang_isocode',
                    'sys_language.flag',
                    'sys_language.sorting'
                )
                ->orderBy('sys_language.sorting');
            if (!$this->getBackendUser()->isAdmin()) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        'sys_language.hidden',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                );
            }
            $statement = $queryBuilder->execute();
        } else {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
            $statement = $queryBuilder->select('uid', 'title')
                ->from('sys_language')
                ->orderBy('sorting')
                ->execute();
        }
        while ($lRow = $statement->fetch()) {
            if ($this->getBackendUser()->checkLanguageAccess($lRow['uid'])) {
                $this->MOD_MENU['language'][$lRow['uid']] = $lRow['title'];
            }
        }
        // Setting alternative default label:
        if (($this->modSharedTSconfig['properties']['defaultLanguageLabel'] || $this->modTSconfig['properties']['defaultLanguageLabel']) && isset($this->MOD_MENU['language'][0])) {
            $this->MOD_MENU['language'][0] = $this->modTSconfig['properties']['defaultLanguageLabel'] ? $this->modTSconfig['properties']['defaultLanguageLabel'] : $this->modSharedTSconfig['properties']['defaultLanguageLabel'];
        }
        // Initialize the avaiable actions
        $actions = $this->initActions();
        // Clean up settings
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName);
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
    protected function initActions()
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
        // @internal: This is an internal hook for compatibility7 only, this hook will be removed without further notice
        $initActionHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['initActionHook'];
        if (is_array($initActionHook)) {
            foreach ($initActionHook as $hook) {
                $params = [
                    'actions' => &$actions
                ];
                GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
        // page/be_user TSconfig blinding of menu-items
        $actions = BackendUtility::unsetMenuItems($this->modTSconfig['properties'], $actions, 'menu.function');

        return $actions;
    }

    /**
     * This creates the dropdown menu with the different actions this module is able to provide.
     * For now they are Columns, Quick Edit and Languages.
     *
     * @param array $actions array with the available actions
     */
    protected function makeActionMenu(array $actions)
    {
        $actionMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $actionMenu->setIdentifier('actionMenu');
        $actionMenu->setLabel('');

        $defaultKey = null;
        $foundDefaultKey = false;
        foreach ($actions as $key => $action) {
            $menuItem = $actionMenu
                ->makeMenuItem()
                ->setTitle($action)
                ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&SET[function]=' . $key);

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
    public function clearCache()
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
    protected function getHeaderFlashMessagesForCurrentPid()
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
    protected function getPageLinksWhereContentIsAlsoShownOn($pageId)
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
    protected function getLocalizedPageTitle()
    {
        if ($this->current_sys_language > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages_language_overlay');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
            $overlayRecord = $queryBuilder
                ->select('*')
                ->from('pages_language_overlay')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($this->current_sys_language, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
            BackendUtility::workspaceOL('pages_language_overlay', $overlayRecord);
            return $overlayRecord['title'];
        }
        return $this->pageinfo['title'];
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->clearCache();
        $this->main();
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Main function.
     * Creates some general objects and calls other functions for the main rendering of module content.
     */
    public function main()
    {
        $lang = $this->getLanguageService();
        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $access = is_array($this->pageinfo) ? 1 : 0;
        // Content
        $content = '';
        if ($this->id && $access) {
            // Initialize permission settings:
            $this->CALC_PERMS = $this->getBackendUser()->calcPerms($this->pageinfo);
            $this->EDIT_CONTENT = $this->contentIsNotLockedForEditors();

            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);

            // override the default jumpToUrl
            $this->moduleTemplate->addJavaScriptCode('jumpToUrl', '
                function jumpToUrl(URL,formEl) {
                    if (document.editform && TBE_EDITOR.isFormChanged)  {   // Check if the function exists... (works in all browsers?)
                        if (!TBE_EDITOR.isFormChanged()) {
                            window.location.href = URL;
                        } else if (formEl) {
                            if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
                        }
                    } else {
                        window.location.href = URL;
                    }
                }
            ');
            $this->moduleTemplate->addJavaScriptCode('mainJsFunctions', '
                if (top.fsMod) {
                    top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
                    top.fsMod.navFrameHighlightedID["web"] = "pages' . (int)$this->id . '_"+top.fsMod.currentBank; ' . (int)$this->id . ';
                }
                ' . ($this->popView ? BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id)) : '') . '
                function deleteRecord(table,id,url) {   //
                    window.location.href = ' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('tce_db') . '&cmd[')
                                             . ' + table + "][" + id + "][delete]=1&redirect=" + encodeURIComponent(url) + "&prErr=1&uPT=1";
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
                $content .= '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl($this->moduleName, ['id' => $this->id, 'imagemode' =>  $this->imagemode])) . '" id="PageLayoutController" method="post">';
                // Page title
                $content .= '<h1 class="t3js-title-inlineedit">' . htmlspecialchars($this->getLocalizedPageTitle()) . '</h1>';
                // All other listings
                $content .= $this->renderContent();
            }
            $content .= '</form>';
            $content .= $this->searchContent;
            // Setting up the buttons for the docheader
            $this->makeButtons();
            // @internal: This is an internal hook for compatibility7 only, this hook will be removed without further notice
            if ($this->MOD_SETTINGS['function'] != 1 && $this->MOD_SETTINGS['function'] != 2) {
                $renderActionHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['renderActionHook'];
                if (is_array($renderActionHook)) {
                    foreach ($renderActionHook as $hook) {
                        $params = [
                            'deleteButton' => $this->deleteButton,
                            ''
                        ];
                        $content .= GeneralUtility::callUserFunction($hook, $params, $this);
                    }
                }
            }
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
    public function renderContent()
    {
        $this->moduleTemplate->getPageRenderer()->loadJquery();
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        /** @var $dbList \TYPO3\CMS\Backend\View\PageLayoutView */
        $dbList = GeneralUtility::makeInstance(PageLayoutView::class);
        $dbList->thumbs = $this->imagemode;
        $dbList->no_noWrap = 1;
        $dbList->descrTable = $this->descrTable;
        $this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
        $dbList->script = BackendUtility::getModuleUrl($this->moduleName);
        $dbList->showIcon = 0;
        $dbList->setLMargin = 0;
        $dbList->doEdit = $this->EDIT_CONTENT;
        $dbList->ext_CALC_PERMS = $this->CALC_PERMS;
        $dbList->agePrefixes = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears');
        $dbList->id = $this->id;
        $dbList->nextThree = MathUtility::forceIntegerInRange($this->modTSconfig['properties']['editFieldsAtATime'], 0, 10);
        $dbList->option_newWizard = $this->modTSconfig['properties']['disableNewContentElementWizard'] ? 0 : 1;
        $dbList->defLangBinding = $this->modTSconfig['properties']['defLangBinding'] ? 1 : 0;
        if (!$dbList->nextThree) {
            $dbList->nextThree = 1;
        }
        $dbList->externalTables = $this->externalTables;
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
            // Render versioning selector:
            $dbList->HTMLcode .= $this->moduleTemplate->getVersionSelector($this->id);
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
        $headerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'];
        if (is_array($headerContentHook)) {
            foreach ($headerContentHook as $hook) {
                $params = [];
                $content .= GeneralUtility::callUserFunction($hook, $params, $this);
            }
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
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.title.searchIcon'))
                ->setIcon($this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL))
                ->setHref('#');
            $this->buttonBar->addButton($toggleSearchFormButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
            $this->searchContent = $dbList->getSearchBox();
        }
        // Additional footer content
        $footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook'];
        if (is_array($footerContentHook)) {
            foreach ($footerContentHook as $hook) {
                $params = [];
                $content .= GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
        return $content;
    }

    /**
     * @return ModuleTemplate
     */
    public function getModuleTemplate()
    {
        return $this->moduleTemplate;
    }

    /**
     * Print accumulated content of module
     *
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->moduleTemplate->renderContent();
    }

    /***************************
     *
     * Sub-content functions, rendering specific parts of the module content.
     *
     ***************************/
    /**
     * This creates the buttons for the modules
     */
    protected function makeButtons()
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
                ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL))
                ->setHref('#');

            $this->buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }
        // Shortcut
        $shortcutButton = $this->buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setGetVariables([
                'id',
                'M',
                'edit_record',
                'pointer',
                'new_unique_uid',
                'search_field',
                'search_levels',
                'showLimit'
            ])
            ->setSetVariables(array_keys($this->MOD_MENU));
        $this->buttonBar->addButton($shortcutButton);

        // Cache
        if (empty($this->modTSconfig['properties']['disableAdvanced'])) {
            $clearCacheButton = $this->buttonBar->makeLinkButton()
                ->setHref(BackendUtility::getModuleUrl($this->moduleName, ['id' => $this->pageinfo['uid'], 'clear_cache' => '1']))
                ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
            $this->buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
        }
        if (empty($this->modTSconfig['properties']['disableIconToolbar'])) {
            // Edit page properties and page language overlay icons
            if ($this->pageIsNotLockedForEditors() && $this->getBackendUser()->checkLanguageAccess(0)) {
                // Edit localized page_language_overlay only when one specific language is selected
                if ($this->MOD_SETTINGS['function'] == 1 && $this->current_sys_language > 0) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('pages_language_overlay');
                    $queryBuilder->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                        ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
                    $overlayRecord = $queryBuilder
                        ->select('uid')
                        ->from('pages_language_overlay')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'sys_language_uid',
                                $queryBuilder->createNamedParameter($this->current_sys_language, \PDO::PARAM_INT)
                            )
                        )
                        ->setMaxResults(1)
                        ->execute()
                        ->fetch();
                    // Edit button
                    $urlParameters = [
                        'edit' => [
                            'pages_language_overlay' => [
                                $overlayRecord['uid'] => 'edit'
                            ]
                        ],
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ];
                    $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
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
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ];
                $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
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
    public function getNumberOfHiddenElements(array $contentConfig = [])
    {
        /** @var QueryBuilder $queryBuilder */
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
    public function local_linkThisScript($params)
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
    public function pageIsNotLockedForEditors()
    {
        return $this->getBackendUser()->isAdmin() || ($this->CALC_PERMS & Permission::PAGE_EDIT) === Permission::PAGE_EDIT && !$this->pageinfo['editlock'];
    }

    /**
     * Check if content can be edited by current user
     *
     * @return bool
     */
    public function contentIsNotLockedForEditors()
    {
        return $this->getBackendUser()->isAdmin() || ($this->CALC_PERMS & Permission::CONTENT_EDIT) === Permission::CONTENT_EDIT && !$this->pageinfo['editlock'];
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns current PageRenderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Make the LanguageMenu
     */
    protected function makeLanguageMenu()
    {
        if (count($this->MOD_MENU['language']) > 1) {
            $lang = $this->getLanguageService();
            $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('languageMenu');
            foreach ($this->MOD_MENU['language'] as $key => $language) {
                $menuItem = $languageMenu
                    ->makeMenuItem()
                    ->setTitle($language)
                    ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&SET[language]=' . $key);
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
    protected function currentPageHasSubPages()
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
