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
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\ContentLayoutPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
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
     */
    public $edit_record;

    /**
     * QuickEdit: If set, this variable tells quick edit that the last edited record had
     * this value as UID and we should look up the new, real uid value in sys_log.
     *
     * @var string
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
     * @var array
     */
    protected $eRParts = [];

    /**
     * @var string
     */
    protected $editSelect;

    /**
     * @var bool
     */
    protected $deleteButton;

    /**
     * @var bool
     */
    protected $undoButton;

    /**
     * @var array
     */
    protected $undoButtonR;

    /**
     * @var string
     */
    protected $R_URI;

    /**
     * @var string
     */
    protected $closeUrl;

    /**
     * Caches the available languages in a colPos
     *
     * @var array
     */
    protected $languagesInColumnCache = [];

    /**
     * Caches the amount of content elements as a matrix
     *
     * @var array
     * @internal
     */
    public $contentElementCache = [];

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
     *
     * @return void
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
     *
     * @return void
     */
    public function menuConfig()
    {
        $lang = $this->getLanguageService();
        // MENU-ITEMS:
        $this->MOD_MENU = [
            'tt_content_showHidden' => '',
            'function' => [
                1 => $lang->getLL('m_function_1'),
                0 => $lang->getLL('m_function_0'),
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
        $res = $this->exec_languageQuery($this->id);
        while ($lRow = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            if ($this->getBackendUser()->checkLanguageAccess($lRow['uid'])) {
                $this->MOD_MENU['language'][$lRow['uid']] = $lRow['hidden'] ? '(' . $lRow['title'] . ')' : $lRow['title'];
            }
        }
        // Setting alternative default label:
        if (($this->modSharedTSconfig['properties']['defaultLanguageLabel'] || $this->modTSconfig['properties']['defaultLanguageLabel']) && isset($this->MOD_MENU['language'][0])) {
            $this->MOD_MENU['language'][0] = $this->modTSconfig['properties']['defaultLanguageLabel'] ? $this->modTSconfig['properties']['defaultLanguageLabel'] : $this->modSharedTSconfig['properties']['defaultLanguageLabel'];
        }
        // Clean up settings
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName);
        // For all elements to be shown in draft workspaces & to also show hidden elements by default if user hasn't disabled the option
        if ($this->getBackendUser()->workspace != 0 || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0') {
            $this->MOD_SETTINGS['tt_content_showHidden'] = 1;
        }
        $this->makeActionMenu();
    }

    /**
     * This creates the dropdown menu with the different actions this module is able to provide.
     * For now they are Columns, Quick Edit and Languages.
     *
     * @return void
     */
    protected function makeActionMenu()
    {
        $availableActionArray = [
            0 => $this->getLanguageService()->getLL('m_function_0'),
            1 => $this->getLanguageService()->getLL('m_function_1'),
            2 => $this->getLanguageService()->getLL('m_function_2')
        ];
        // Find if there are ANY languages at all (and if not, remove the language option from function menu).
        $count = $this->getDatabaseConnection()->exec_SELECTcountRows('uid', 'sys_language', $this->getBackendUser()->isAdmin() ? '' : 'hidden=0');
        if (!$count) {
            unset($availableActionArray['2']);
        }
        // page/be_user TSconfig settings and blinding of menu-items
        if ($this->modTSconfig['properties']['QEisDefault']) {
            ksort($availableActionArray);
        }
        $availableActionArray = BackendUtility::unsetMenuItems($this->modTSconfig['properties'], $availableActionArray, 'menu.function');
        // Remove QuickEdit as option if page type is not...
        if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'] . ',6', $this->pageinfo['doktype'])) {
            unset($availableActionArray[0]);
        }
        $actionMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $actionMenu->setIdentifier('actionMenu');
        $actionMenu->setLabel('');

        $defaultKey = null;
        $foundDefaultKey = false;
        foreach ($availableActionArray as $key => $action) {
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
     *
     * @return void
     */
    public function clearCache()
    {
        if ($this->clear_cache) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->stripslashes_values = false;
            $tce->start([], []);
            $tce->clear_cacheCmd($this->id);
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

        // If page is a folder
        if ($this->pageinfo['doktype'] == PageRepository::DOKTYPE_SYSFOLDER) {
            $moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
            $moduleLoader->load($GLOBALS['TBE_MODULES']);
            $modules = $moduleLoader->modules;
            if (is_array($modules['web']['sub']['list'])) {
                $title = $lang->getLL('goToListModule');
                $message = '<p>' . $lang->getLL('goToListModuleMessage') . '</p>';
                $message .= '<a class="btn btn-info" href="javascript:top.goToModule(\'web_list\',1);">' . $lang->getLL('goToListModule') . '</a>';
                $view = GeneralUtility::makeInstance(StandaloneView::class);
                $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));
                $view->assignMultiple([
                    'title' => $title,
                    'message' => $message,
                    'state' => InfoboxViewHelper::STATE_INFO
                ]);
                $content .= $view->render();
            }
        }
        // If content from different pid is displayed
        if ($this->pageinfo['content_from_pid']) {
            $contentPage = BackendUtility::getRecord('pages', (int)$this->pageinfo['content_from_pid']);
            $linkToPid = $this->local_linkThisScript(['id' => $this->pageinfo['content_from_pid']]);
            $title = BackendUtility::getRecordTitle('pages', $contentPage);
            $link = '<a href="' . $linkToPid . '">' . htmlspecialchars($title) . ' (PID ' . (int)$this->pageinfo['content_from_pid'] . ')</a>';
            $message = sprintf($lang->getLL('content_from_pid_title'), $link);
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));
            $view->assignMultiple([
                'title' => $title,
                'message' => $message,
                'state' => InfoboxViewHelper::STATE_INFO
            ]);
            $content .= $view->render();
        }
        return $content;
    }

    /**
     *
     * @return string $title
     */
    protected function getLocalizedPageTitle()
    {
        if ($this->current_sys_language > 0) {
            $overlayRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                'title',
                'pages_language_overlay',
                'pid = ' . (int)$this->id .
                ' AND sys_language_uid = ' . (int)$this->current_sys_language .
                BackendUtility::deleteClause('pages_language_overlay') .
                BackendUtility::versioningPlaceholderClause('pages_language_overlay'),
                '',
                '',
                ''
            );
            return $overlayRecord['title'];
        } else {
            return $this->pageinfo['title'];
        }
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
     *
     * @return void
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
                                             . ' + table + "][" + id + "][delete]=1&redirect=" + encodeURIComponent(url) + "&vC=' . $this->getBackendUser()->veriCode() . '&prErr=1&uPT=1";
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
            if ($this->MOD_SETTINGS['function'] == 0) {
                // QuickEdit
                $content .= '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_db', ['prErr' => 1, 'uPT' => 1])) . '" method="post" enctype="multipart/form-data" name="editform" id="PageLayoutController" onsubmit="return TBE_EDITOR.checkSubmit(1);">';
                $content .= $this->renderQuickEdit();
            } else {
                $content .= '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl($this->moduleName, ['id' => $this->id, 'imagemode' =>  $this->imagemode])) . '" id="PageLayoutController" method="post">';
                // Page title
                $content .= '<h1 class="t3js-title-inlineedit">' . htmlspecialchars($this->getLocalizedPageTitle()) . '</h1>';
                // All other listings
                $content .= $this->renderListContent();
            }
            $content .= '</form>';
            $content .= $this->searchContent;
            // Setting up the buttons for the docheader
            $this->makeButtons($this->MOD_SETTINGS['function'] == 0 ? 'quickEdit' : '');
            // Create LanguageMenu
            $this->makeLanguageMenu();
        } else {
            $this->moduleTemplate->addJavaScriptCode(
                'mainJsFunctions',
                'if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';'
            );
            $content .= '<h1>' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '</h1>';
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
     * Rendering the quick-edit view.
     *
     * @return string
     */
    public function renderQuickEdit()
    {
        $databaseConnection = $this->getDatabaseConnection();
        $beUser = $this->getBackendUser();
        $lang = $this->getLanguageService();
        // Set the edit_record value for internal use in this function:
        $edit_record = $this->edit_record;
        // If a command to edit all records in a column is issue, then select all those elements, and redirect to FormEngine
        if (substr($edit_record, 0, 9) == '_EDIT_COL') {
            $res = $databaseConnection->exec_SELECTquery('*', 'tt_content', 'pid=' . (int)$this->id . ' AND colPos=' . (int)substr($edit_record, 10) . ' AND sys_language_uid=' . (int)$this->current_sys_language . ($this->MOD_SETTINGS['tt_content_showHidden'] ? '' : BackendUtility::BEenableFields('tt_content')) . BackendUtility::deleteClause('tt_content') . BackendUtility::versioningPlaceholderClause('tt_content'), '', 'sorting');
            $idListA = [];
            while ($cRow = $databaseConnection->sql_fetch_assoc($res)) {
                $idListA[] = $cRow['uid'];
            }
            $url = BackendUtility::getModuleUrl('record_edit', [
                'edit[tt_content][' . implode(',', $idListA) . ']' => 'edit',
                'returnUrl' => $this->local_linkThisScript(['edit_record' => ''])
            ]);
            HttpUtility::redirect($url);
        }
        // If the former record edited was the creation of a NEW record, this will look up the created records uid:
        if ($this->new_unique_uid) {
            $res = $databaseConnection->exec_SELECTquery('*', 'sys_log', 'userid=' . (int)$beUser->user['uid'] . ' AND NEWid=' . $databaseConnection->fullQuoteStr($this->new_unique_uid, 'sys_log'));
            $sys_log_row = $databaseConnection->sql_fetch_assoc($res);
            if (is_array($sys_log_row)) {
                $edit_record = $sys_log_row['tablename'] . ':' . $sys_log_row['recuid'];
            }
        }
        $edit_record = $this->makeQuickEditMenu($edit_record);
        // Splitting the edit-record cmd value into table/uid:
        $this->eRParts = explode(':', $edit_record);
        $tableName = $this->eRParts[0];
        // Delete-button flag?
        $this->deleteButton = MathUtility::canBeInterpretedAsInteger($this->eRParts[1]) && $edit_record && ($tableName !== 'pages' && $this->EDIT_CONTENT || $tableName === 'pages' && $this->CALC_PERMS & Permission::PAGE_DELETE);
        // If undo-button should be rendered (depends on available items in sys_history)
        $this->undoButton = false;

        // if there is no content on a page
        // the parameter $this->eRParts[1] will be set to e.g. /new/1
        // which is not an integer value and it will throw an exception here on certain dbms
        // thus let's check that before as there cannot be a history for a new record
        $this->undoButtonR = false;
        if (MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
            $undoRes = $databaseConnection->exec_SELECTquery('tstamp', 'sys_history', 'tablename=' . $databaseConnection->fullQuoteStr($tableName, 'sys_history') . ' AND recuid=' . (int)$this->eRParts[1], '', 'tstamp DESC', '1');
            $this->undoButtonR = $databaseConnection->sql_fetch_assoc($undoRes);
        }
        if ($this->undoButtonR) {
            $this->undoButton = true;
        }
        // Setting up the Return URL for coming back to THIS script (if links take the user to another script)
        $R_URL_parts = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
        $R_URL_getvars = GeneralUtility::_GET();
        unset($R_URL_getvars['popView']);
        unset($R_URL_getvars['new_unique_uid']);
        $R_URL_getvars['edit_record'] = $edit_record;
        $this->R_URI = $R_URL_parts['path'] . '?' . GeneralUtility::implodeArrayForUrl('', $R_URL_getvars);

        // Creating editing form:
        if ($edit_record) {
            // Splitting uid parts for special features, if new:
            list($uidVal, $neighborRecordUid, $ex_colPos) = explode('/', $this->eRParts[1]);

            if ($uidVal === 'new') {
                $command = 'new';
                // Page id of this new record
                $theUid = $this->id;
                if ($neighborRecordUid) {
                    $theUid = $neighborRecordUid;
                }
            } else {
                $command = 'edit';
                $theUid = $uidVal;
                // Convert $uidVal to workspace version if any:
                $draftRecord = BackendUtility::getWorkspaceVersionOfRecord($beUser->workspace, $tableName, $theUid, 'uid');
                if ($draftRecord) {
                    $theUid = $draftRecord['uid'];
                }
            }

            // @todo: Hack because DatabaseInitializeNewRow reads from _GP directly
            $GLOBALS['_GET']['defVals'][$tableName] = [
                'colPos' => (int)$ex_colPos,
                'sys_language_uid' => (int)$this->current_sys_language
            ];

            /** @var TcaDatabaseRecord $formDataGroup */
            $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
            /** @var FormDataCompiler $formDataCompiler */
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            /** @var NodeFactory $nodeFactory */
            $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

            try {
                $formDataCompilerInput = [
                    'tableName' => $tableName,
                    'vanillaUid' => (int)$theUid,
                    'command' => $command,
                ];
                $formData = $formDataCompiler->compile($formDataCompilerInput);

                if ($command !== 'new') {
                    BackendUtility::lockRecords($tableName, $formData['databaseRow']['uid'], $tableName === 'tt_content' ? $formData['databaseRow']['pid'] : 0);
                }

                $formData['renderType'] = 'outerWrapContainer';
                $formResult = $nodeFactory->create($formData)->render();

                $panel = $formResult['html'];
                $formResult['html'] = '';

                /** @var FormResultCompiler $formResultCompiler */
                $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
                $formResultCompiler->mergeResult($formResult);

                $row = $formData['databaseRow'];
                $new_unique_uid = '';
                if ($command === 'new') {
                    $new_unique_uid = $row['uid'];
                }

                // Add hidden fields:
                if ($uidVal == 'new') {
                    $panel .= '<input type="hidden" name="data[' . $tableName . '][' . $row['uid'] . '][pid]" value="' . $row['pid'] . '" />';
                }
                $redirect = ($uidVal == 'new' ? BackendUtility::getModuleUrl(
                    $this->moduleName,
                    ['id' => $this->id, 'new_unique_uid' => $new_unique_uid, 'returnUrl' => $this->returnUrl]
                ) : $this->R_URI);
                $panel .= '
                    <input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
                    <input type="hidden" name="edit_record" value="' . $edit_record . '" />
                    <input type="hidden" name="redirect" value="' . htmlspecialchars($redirect) . '" />
                    ';
                // Add JavaScript as needed around the form:
                $content = $formResultCompiler->JStop() . $panel . $formResultCompiler->printNeededJSFunctions();

                // Display "is-locked" message:
                if ($command === 'edit') {
                    $lockInfo = BackendUtility::isRecordLocked($tableName, $formData['databaseRow']['uid']);
                    if ($lockInfo) {
                        /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
                        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $lockInfo['msg'], '', FlashMessage::WARNING);
                        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                        $defaultFlashMessageQueue->enqueue($flashMessage);
                    }
                }
            } catch (AccessDeniedException $e) {
                // If no edit access, print error message:
                $content = '<h2>' . $lang->getLL('noAccess', true) . '</h2>';
                $content .= '<div>' . $lang->getLL('noAccess_msg') . '<br /><br />' . ($beUser->errorMsg ? 'Reason: ' . $beUser->errorMsg . '<br /><br />' : '') . '</div>';
            }
        } else {
            // If no edit access, print error message:
            $content = '<h2>' . $lang->getLL('noAccess') . '</h2>';
            $content .= '<div>' . $lang->getLL('noAccess_msg') . '</div>';
        }

        // Element selection matrix:
        if ($tableName === 'tt_content' && MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
            $content .= '<h2>' . $lang->getLL('CEonThisPage') . '</h2>';
            // PositionMap
            $posMap = GeneralUtility::makeInstance(ContentLayoutPagePositionMap::class);
            $posMap->cur_sys_language = $this->current_sys_language;
            $content .= $posMap->printContentElementColumns(
                $this->id,
                $this->eRParts[1],
                $this->colPosList,
                $this->MOD_SETTINGS['tt_content_showHidden'],
                $this->R_URI
            );
            // Toggle hidden ContentElements
            $numberOfHiddenElements = $this->getNumberOfHiddenElements();
            if ($numberOfHiddenElements) {
                $content .= '<div class="checkbox">';
                $content .= '<label for="checkTt_content_showHidden">';
                $content .= BackendUtility::getFuncCheck($this->id, 'SET[tt_content_showHidden]', $this->MOD_SETTINGS['tt_content_showHidden'], '', '', 'id="checkTt_content_showHidden"');
                $content .= (!$numberOfHiddenElements ? ('<span class="text-muted">' . $lang->getLL('hiddenCE', true) . '</span>') : $lang->getLL('hiddenCE', true) . ' (' . $numberOfHiddenElements . ')');
                $content .= '</label>';
                $content .= '</div>';
            }
            // CSH
            $content .= BackendUtility::cshItem($this->descrTable, 'quickEdit_selElement', null, '<span class="btn btn-default btn-sm">|</span>');
        }

        return $content;
    }

    /**
     * Rendering all other listings than QuickEdit
     *
     * @return string
     */
    public function renderListContent()
    {
        $this->moduleTemplate->getPageRenderer()->loadJquery();
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
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
        $dbList->agePrefixes = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears');
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
                // Toggle hidden ContentElements
                $numberOfHiddenElements = $this->getNumberOfHiddenElements();
                if ($numberOfHiddenElements > 0) {
                    $h_func_b = '
                        <div class="checkbox">
                            <label for="checkTt_content_showHidden">
                                <input type="checkbox" id="checkTt_content_showHidden" class="checkbox" name="SET[tt_content_showHidden]" value="1" ' . ($this->MOD_SETTINGS['tt_content_showHidden'] ? 'checked="checked"' : '') . ' />
                                ' . $this->getLanguageService()->getLL('hiddenCE', true) . ' (<span class="t3js-hidden-counter">' . $numberOfHiddenElements . '</span>)
                            </label>
                        </div>';
                }

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
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.searchIcon'))
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
     * @return void
     */
    public function printContent()
    {
        echo $this->moduleTemplate->renderContent();
    }

    /***************************
     *
     * Sub-content functions, rendering specific parts of the module content.
     *
     ***************************/
    /**
     * This creates the buttons for die modules
     *
     * @param string $function Identifier for function of module
     * @return void
     */
    protected function makeButtons($function = '')
    {
        $lang = $this->getLanguageService();
        // View page
        if (!VersionState::cast($this->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
            $viewButton = $this->buttonBar->makeLinkButton()
                ->setOnClick(BackendUtility::viewOnClick($this->pageinfo['uid'], '', BackendUtility::BEgetRootLine($this->pageinfo['uid'])))
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage'))
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
        if (!$this->modTSconfig['properties']['disableAdvanced']) {
            $clearCacheButton = $this->buttonBar->makeLinkButton()
                ->setHref(BackendUtility::getModuleUrl($this->moduleName, ['id' => $this->pageinfo['uid'], 'clear_cache' => '1']))
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
            $this->buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
        }
        if (!$this->modTSconfig['properties']['disableIconToolbar']) {
            // Move record
            if (MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
                $urlParameters = [
                    'table' => $this->eRParts[0],
                    'uid' => $this->eRParts[1],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ];
                $moveButton = $this->buttonBar->makeLinkButton()
                    ->setHref(BackendUtility::getModuleUrl('move_element', $urlParameters))
                    ->setTitle($lang->getLL('move_' . ($this->eRParts[0] == 'tt_content' ? 'record' : 'page')))
                    ->setIcon($this->iconFactory->getIcon('actions-' . ($this->eRParts[0] == 'tt_content' ? 'document' : 'page') . '-move', Icon::SIZE_SMALL));
                $this->buttonBar->addButton($moveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            }

            // Edit page properties and page language overlay icons
            if ($this->pageIsNotLockedForEditors() && $this->getBackendUser()->checkLanguageAccess(0)) {
                // Edit localized page_language_overlay only when one specific language is selected
                if ($this->MOD_SETTINGS['function'] == 1 && $this->current_sys_language > 0) {
                    $overlayRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                        'uid',
                        'pages_language_overlay',
                        'pid = ' . (int)$this->id . ' ' .
                        'AND sys_language_uid = ' . (int)$this->current_sys_language .
                        BackendUtility::deleteClause('pages_language_overlay') .
                        BackendUtility::versioningPlaceholderClause('pages_language_overlay'),
                        '',
                        '',
                        ''
                    );
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

            // Add CSH (Context Sensitive Help) icon to tool bar
            $contextSensitiveHelpButton = $this->buttonBar->makeHelpButton()
                ->setModuleName($this->descrTable)
                ->setFieldName(($function === 'quickEdit' ? 'quickEdit' : 'columns_' . $this->MOD_SETTINGS['function']));
            $this->buttonBar->addButton($contextSensitiveHelpButton);

            // QuickEdit
            if ($function == 'quickEdit') {
                // Close Record
                $closeButton = $this->buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick('jumpToUrl(' . GeneralUtility::quoteJSvalue($this->closeUrl) . '); return false;')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL));
                $this->buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 0);

                // Save Record
                $saveButtonDropdown = $this->buttonBar->makeSplitButton();
                $saveButton = $this->buttonBar->makeInputButton()
                    ->setName('_savedok')
                    ->setValue('1')
                    ->setForm('PageLayoutController')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
                $saveButtonDropdown->addItem($saveButton);
                $saveAndCloseButton = $this->buttonBar->makeInputButton()
                    ->setName('_saveandclosedok')
                    ->setValue('1')
                    ->setForm('PageLayoutController')
                    ->setOnClick('document.editform.redirect.value=\'' . $this->closeUrl . '\';')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL));
                $saveButtonDropdown->addItem($saveAndCloseButton);
                $saveAndShowPageButton = $this->buttonBar->makeInputButton()
                    ->setName('_savedokview')
                    ->setValue('1')
                    ->setForm('PageLayoutController')
                    ->setOnClick('document.editform.redirect.value+=\'&popView=1\';')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDocShow'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-save-view', Icon::SIZE_SMALL));
                $saveButtonDropdown->addItem($saveAndShowPageButton);
                $this->buttonBar->addButton($saveButtonDropdown, ButtonBar::BUTTON_POSITION_LEFT, 1);

                // Delete record
                if ($this->deleteButton) {
                    $dataAttributes = [];
                    $dataAttributes['table'] = $this->eRParts[0];
                    $dataAttributes['uid'] = $this->eRParts[1];
                    $dataAttributes['return-url'] = BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id;
                    $deleteButton = $this->buttonBar->makeLinkButton()
                        ->setHref('#')
                        ->setClasses('t3js-editform-delete-record')
                        ->setDataAttributes($dataAttributes)
                        ->setTitle($lang->getLL('deleteItem'))
                        ->setIcon($this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL));
                    $this->buttonBar->addButton($deleteButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
                }

                // History
                if ($this->undoButton) {
                    $undoButton = $this->buttonBar->makeLinkButton()
                        ->setHref('#')
                        ->setOnClick('window.location.href=' .
                            GeneralUtility::quoteJSvalue(
                                BackendUtility::getModuleUrl(
                                    'record_history',
                                    [
                                        'element' => $this->eRParts[0] . ':' . $this->eRParts[1],
                                        'revert' => 'ALL_FIELDS',
                                        'sumUp' => -1,
                                        'returnUrl' => $this->R_URI,
                                    ]
                                )
                            ) . '; return false;')
                        ->setTitle(sprintf($lang->getLL('undoLastChange'), BackendUtility::calcAge($GLOBALS['EXEC_TIME'] - $this->undoButtonR['tstamp'], $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears'))))
                        ->setIcon($this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL));
                    $this->buttonBar->addButton($undoButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
                    $historyButton = $this->buttonBar->makeLinkButton()
                        ->setHref('#')
                        ->setOnClick('jumpToUrl(' .
                            GeneralUtility::quoteJSvalue(
                                BackendUtility::getModuleUrl(
                                    'record_history',
                                    [
                                            'element' => $this->eRParts[0] . ':' . $this->eRParts[1],
                                            'returnUrl' => $this->R_URI,
                                        ]
                                ) . '#latest'
                            ) . ');return false;')
                        ->setTitle($lang->getLL('recordHistory'))
                        ->setIcon($this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL));
                    $this->buttonBar->addButton($historyButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
                }
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
     * @return int
     */
    public function getNumberOfHiddenElements()
    {
        return $this->getDatabaseConnection()->exec_SELECTcountRows(
            'uid',
            'tt_content',
            'pid=' . (int)$this->id . ' AND sys_language_uid=' . (int)$this->current_sys_language . BackendUtility::BEenableFields('tt_content', 1) . BackendUtility::deleteClause('tt_content') . BackendUtility::versioningPlaceholderClause('tt_content')
        );
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
     * Returns a SQL query for selecting sys_language records.
     *
     * @param int $id Page id: If zero, the query will select all sys_language records from root level which are NOT hidden. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
     * @return string Return query string.
     */
    public function exec_languageQuery($id)
    {
        if ($id) {
            $exQ = BackendUtility::deleteClause('pages_language_overlay') .
                ($this->getBackendUser()->isAdmin() ? '' : ' AND sys_language.hidden=0');
            return $this->getDatabaseConnection()->exec_SELECTquery(
                'sys_language.*',
                'pages_language_overlay,sys_language',
                'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid=' . (int)$id . $exQ .
                BackendUtility::versioningPlaceholderClause('pages_language_overlay'),
                'pages_language_overlay.sys_language_uid,sys_language.uid,sys_language.pid,sys_language.tstamp,sys_language.hidden,sys_language.title,sys_language.language_isocode,sys_language.static_lang_isocode,sys_language.flag',
                'sys_language.title'
            );
        } else {
            return $this->getDatabaseConnection()->exec_SELECTquery(
                'sys_language.*',
                'sys_language',
                'sys_language.hidden=0',
                '',
                'sys_language.title'
            );
        }
    }

    /**
     * Check if a column of a page for a language is empty. Translation records are ignored here!
     *
     * @param int $colPos
     * @param int $languageId
     * @return bool
     */
    public function isColumnEmpty($colPos, $languageId)
    {
        foreach ($this->contentElementCache[$languageId][$colPos] as $uid => $row) {
            if ((int)$row['l18n_parent'] === 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get elements for a column and a language
     *
     * @param int $pageId
     * @param int $colPos
     * @param int $languageId
     * @return array
     */
    public function getElementsFromColumnAndLanguage($pageId, $colPos, $languageId)
    {
        if (!isset($this->contentElementCache[$languageId][$colPos])) {
            $languageId = (int)$languageId;
            $whereClause = 'tt_content.pid=' . (int)$pageId . ' AND tt_content.colPos=' . (int)$colPos . ' AND tt_content.sys_language_uid=' . $languageId . BackendUtility::deleteClause('tt_content');
            if ($languageId > 0) {
                $whereClause .= ' AND tt_content.l18n_parent=0 AND sys_language.uid=' . $languageId . ($this->getBackendUser()->isAdmin() ? '' : ' AND sys_language.hidden=0');
            }

            $databaseConnection = $this->getDatabaseConnection();
            $res = $databaseConnection->exec_SELECTquery(
                'tt_content.uid',
                'tt_content,sys_language',
                $whereClause
            );
            while ($row = $databaseConnection->sql_fetch_assoc($res)) {
                $this->contentElementCache[$languageId][$colPos][$row['uid']] = $row;
            }
            $databaseConnection->sql_free_result($res);
        }
        if (is_array($this->contentElementCache[$languageId][$colPos])) {
            return array_keys($this->contentElementCache[$languageId][$colPos]);
        }
        return [];
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
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
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
     * @param $edit_record array
     *
     * @return array
     */
    protected function makeQuickEditMenu($edit_record)
    {
        $lang = $this->getLanguageService();
        $databaseConnection = $this->getDatabaseConnection();
        $beUser = $this->getBackendUser();

        $quickEditMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $quickEditMenu->setIdentifier('quickEditMenu');
        $quickEditMenu->setLabel('');

        // Setting close url/return url for exiting this script:
        // Goes to 'Columns' view if close is pressed (default)
        $this->closeUrl = $this->local_linkThisScript(['SET' => ['function' => 1]]);
        if ($this->returnUrl) {
            $this->closeUrl = $this->returnUrl;
        }
        $retUrlStr = $this->returnUrl ? '&returnUrl=' . rawurlencode($this->returnUrl) : '';

        // Creating the selector box, allowing the user to select which element to edit:
        $isSelected = 0;
        $languageOverlayRecord = '';
        if ($this->current_sys_language) {
            list($languageOverlayRecord) = BackendUtility::getRecordsByField(
                'pages_language_overlay',
                'pid',
                $this->id,
                'AND sys_language_uid=' . (int)$this->current_sys_language
            );
        }
        if (is_array($languageOverlayRecord)) {
            $inValue = 'pages_language_overlay:' . $languageOverlayRecord['uid'];
            $isSelected += (int)$edit_record == $inValue;
            $menuItem = $quickEditMenu->makeMenuItem()
                ->setTitle('[ ' . $lang->getLL('editLanguageHeader', true) . ' ]')
                ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&edit_record=' . $inValue . $retUrlStr)
                ->setActive($edit_record == $inValue);
            $quickEditMenu->addMenuItem($menuItem);
        } else {
            $inValue = 'pages:' . $this->id;
            $isSelected += (int)$edit_record == $inValue;
            $menuItem = $quickEditMenu->makeMenuItem()
                ->setTitle('[ ' . $lang->getLL('editPageProperties', true) . ' ]')
                ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&edit_record=' . $inValue . $retUrlStr)
                ->setActive($edit_record == $inValue);
            $quickEditMenu->addMenuItem($menuItem);
        }
        // Selecting all content elements from this language and allowed colPos:
        $whereClause = 'pid=' . (int)$this->id . ' AND sys_language_uid=' . (int)$this->current_sys_language . ' AND colPos IN (' . $this->colPosList . ')' . ($this->MOD_SETTINGS['tt_content_showHidden'] ? '' : BackendUtility::BEenableFields('tt_content')) . BackendUtility::deleteClause('tt_content') . BackendUtility::versioningPlaceholderClause('tt_content');
        if (!$this->getBackendUser()->user['admin']) {
            $whereClause .= ' AND editlock = 0';
        }
        $res = $databaseConnection->exec_SELECTquery('*', 'tt_content', $whereClause, '', 'colPos,sorting');
        $colPos = null;
        $first = 1;
        // Page is the pid if no record to put this after.
        $prev = $this->id;
        while ($cRow = $databaseConnection->sql_fetch_assoc($res)) {
            BackendUtility::workspaceOL('tt_content', $cRow);
            if (is_array($cRow)) {
                if ($first) {
                    if (!$edit_record) {
                        $edit_record = 'tt_content:' . $cRow['uid'];
                    }
                    $first = 0;
                }
                if (!isset($colPos) || $cRow['colPos'] !== $colPos) {
                    $colPos = $cRow['colPos'];
                    $menuItem = $quickEditMenu->makeMenuItem()
                        ->setTitle(' ')
                        ->setHref('#');
                    $quickEditMenu->addMenuItem($menuItem);
                    $menuItem = $quickEditMenu->makeMenuItem()
                        ->setTitle('__' . $lang->sL(BackendUtility::getLabelFromItemlist('tt_content', 'colPos', $colPos)) . ':__')
                        ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&edit_record=_EDIT_COL:' . $colPos . $retUrlStr);
                    $quickEditMenu->addMenuItem($menuItem);
                }
                $inValue = 'tt_content:' . $cRow['uid'];
                $isSelected += (int)$edit_record == $inValue;
                $menuItem = $quickEditMenu->makeMenuItem()
                    ->setTitle(GeneralUtility::fixed_lgd_cs(($cRow['header'] ? $cRow['header'] : '[' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title') . '] ' . strip_tags($cRow['bodytext'])), $beUser->uc['titleLen']))
                    ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&edit_record=' . $inValue . $retUrlStr)
                    ->setActive($edit_record == $inValue);
                $quickEditMenu->addMenuItem($menuItem);
                $prev = -$cRow['uid'];
            }
        }
        // If edit_record is not set (meaning, no content elements was found for this language) we simply set it to create a new element:
        if (!$edit_record) {
            $edit_record = 'tt_content:new/' . $prev . '/' . $colPos;
            $inValue = 'tt_content:new/' . $prev . '/' . $colPos;
            $isSelected += (int)$edit_record == $inValue;
            $menuItem = $quickEditMenu->makeMenuItem()
                ->setTitle('[ ' . $lang->getLL('newLabel', 1) . ' ]')
                ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&edit_record=' . $inValue . $retUrlStr)
                ->setActive($edit_record == $inValue);
            $quickEditMenu->addMenuItem($menuItem);
        }
        // If none is yet selected...
        if (!$isSelected) {
            $menuItem = $quickEditMenu->makeMenuItem()
                ->setTitle('__________')
                ->setHref('#');
            $quickEditMenu->addMenuItem($menuItem);
            $menuItem = $quickEditMenu->makeMenuItem()
                ->setTitle('[ ' . $lang->getLL('newLabel', true) . ' ]')
                ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&edit_record=' . $edit_record . $retUrlStr)
                ->setActive($edit_record == $inValue);
            $quickEditMenu->addMenuItem($menuItem);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($quickEditMenu);
        return $edit_record;
    }

    /**
     * Make the LanguageMenu
     *
     * @return void
     */
    protected function makeLanguageMenu()
    {
        if (count($this->MOD_MENU['language']) > 1) {
            $lang = $this->getLanguageService();
            $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('languageMenu');
            $languageMenu->setLabel($lang->sL('LLL:EXT:lang/locallang_general.xlf:LGL.language', true));
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
        $count = $this->getDatabaseConnection()->exec_SELECTcountRows(
            'uid',
            'pages',
            'pid = ' . (int)$this->id
                . BackendUtility::deleteClause('pages')
                . BackendUtility::versioningPlaceholderClause('pages')
                . BackendUtility::getWorkspaceWhereClause('pages')
        );

        return $count > 0;
    }
}
