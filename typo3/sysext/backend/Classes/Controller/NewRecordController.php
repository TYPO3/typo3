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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\NewRecordPageTreeView;
use TYPO3\CMS\Backend\Tree\View\PagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Script class for 'db_new'
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class NewRecordController
{
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'pageinfo' => 'Using $pageinfo of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'pidInfo' => 'Using $pidInfo of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'newPagesInto' => 'Using $newPagesInto of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'newContentInto' => 'Using $newContentInto of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'newPagesAfter' => 'Using $newPagesAfter of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'web_list_modTSconfig' => 'Using $web_list_modTSconfig of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'allowedNewTables' => 'Using $allowedNewTables of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'deniedNewTables' => 'Using $deniedNewTables of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'web_list_modTSconfig_pid' => 'Using $web_list_modTSconfig_pid of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'allowedNewTables_pid' => 'Using $allowedNewTables_pid of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'deniedNewTables_pid' => 'Using $deniedNewTables_pid of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'code' => 'Using $code of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'R_URI' => 'Using $R_URI of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'returnUrl' => 'Using $returnUrl of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'pagesOnly' => 'Using $pagesOnly of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'perms_clause' => 'Using $perms_clause of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'content' => 'Using $content of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
        'tRows' => 'Using $tRows of class NewRecordController from outside is discouraged as this variable is only used for internal storage.',
    ];

    /**
     * @var array
     */
    protected $pageinfo = [];

    /**
     * @var array
     */
    protected $pidInfo = [];

    /**
     * @var array
     */
    protected $newRecordSortList;

    /**
     * @var int
     */
    protected $newPagesInto;

    /**
     * @var int
     */
    protected $newContentInto;

    /**
     * @var int
     */
    protected $newPagesAfter;

    /**
     * Determines, whether "Select Position" for new page should be shown
     *
     * @var bool
     */
    protected $newPagesSelectPosition = true;

    /**
     * @var array
     */
    protected $web_list_modTSconfig;

    /**
     * @var array
     */
    protected $allowedNewTables;

    /**
     * @var array
     */
    protected $deniedNewTables;

    /**
     * @var array
     */
    protected $web_list_modTSconfig_pid;

    /**
     * @var array
     */
    protected $allowedNewTables_pid;

    /**
     * @var array
     */
    protected $deniedNewTables_pid;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $R_URI;

    /**
     * @var int
     *
     * @see \TYPO3\CMS\Backend\Tree\View\NewRecordPageTreeView::expandNext()
     * @internal
     */
    public $id;

    /**
     * @var string
     */
    protected $returnUrl;

    /**
     * pagesOnly flag.
     *
     * @var int
     */
    protected $pagesOnly;

    /**
     * @var string
     */
    protected $perms_clause;

    /**
     * Accumulated HTML output
     *
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $tRows;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');

        // @see \TYPO3\CMS\Backend\Tree\View\NewRecordPageTreeView::expandNext()
        $GLOBALS['SOBE'] = $this;

        // @deprecated since TYPO3 v9, will be moved out of __construct() in TYPO3 v10.0
        $this->init($GLOBALS['TYPO3_REQUEST']);
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
        $response = $this->renderContent($request);

        if (empty($response)) {
            $response = new HtmlResponse($this->moduleTemplate->renderContent());
        }

        return $response;
    }

    /**
     * Main processing, creating the list of new record tables to select from
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function main()
    {
        trigger_error('NewRecordController->main() will be replaced by protected method renderContent() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);

        $response = $this->renderContent($GLOBALS['TYPO3_REQUEST']);

        if ($response instanceof RedirectResponse) {
            HttpUtility::redirect($response->getHeaders()['location'][0]);
        }
    }

    /**
     * Creates the position map for pages wizard
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function pagesOnly()
    {
        trigger_error('NewRecordController->pagesOnly() will be replaced by protected method renderPositionTree() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->renderPositionTree();
    }

    /**
     * Create a regular new element (pages and records)
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function regularNew()
    {
        trigger_error('NewRecordController->regularNew() will be replaced by protected method renderNewRecordControls() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->renderNewRecordControls($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * User array sort function used by renderNewRecordControls
     *
     * @param string $a First array element for compare
     * @param string $b First array element for compare
     * @return int -1 for lower, 0 for equal, 1 for greater
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function sortNewRecordsByConfig($a, $b)
    {
        trigger_error('NewRecordController->sortNewRecordsByConfig() will be replaced by protected method sortTableRows() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        return $this->sortTableRows($a, $b);
    }

    /**
     * Links the string $code to a create-new form for a record in $table created on page $pid
     *
     * @param string $linkText Link text
     * @param string $table Table name (in which to create new record)
     * @param int $pid PID value for the "&edit['.$table.']['.$pid.']=new" command (positive/negative)
     * @param bool $addContentTable If $addContentTable is set, then a new tt_content record is created together with pages
     * @return string The link.
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function linkWrap($linkText, $table, $pid, $addContentTable = false)
    {
        trigger_error('NewRecordController->linkWrap() will be replaced by protected method renderLink() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        return $this->renderLink($linkText, $table, $pid, $addContentTable);
    }

    /**
     * Returns TRUE if the tablename $checkTable is allowed to be created on the page with record $pid_row
     *
     * @param array $pid_row Record for parent page.
     * @param string $checkTable Table name to check
     * @return bool Returns TRUE if the tablename $checkTable is allowed to be created on the page with record $pid_row
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function isTableAllowedForThisPage($pid_row, $checkTable)
    {
        trigger_error('NewRecordController->isTableAllowedForThisPage() will be replaced by protected method isTableAllowedOnPage() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        return $this->isTableAllowedOnPage($checkTable, $pid_row);
    }

    /**
     * Returns TRUE if:
     * - $allowedNewTables and $deniedNewTables are empty
     * - the table is not found in $deniedNewTables and $allowedNewTables is not set or the $table tablename is found in
     *   $allowedNewTables
     *
     * If $table tablename is found in $allowedNewTables and $deniedNewTables, $deniedNewTables
     * has priority over $allowedNewTables.
     *
     * @param string $table Table name to test if in allowedTables
     * @param array $allowedNewTables Array of new tables that are allowed.
     * @param array $deniedNewTables Array of new tables that are not allowed.
     * @return bool Returns TRUE if a link for creating new records should be displayed for $table
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function showNewRecLink($table, array $allowedNewTables = [], array $deniedNewTables = [])
    {
        trigger_error('NewRecordController->showNewRecLink() will be replaced by protected method isRecordCreationAllowedForTable() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        return $this->isRecordCreationAllowedForTable($table, $allowedNewTables, $deniedNewTables);
    }

    /**
     * Constructor function for the class
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request): void
    {
        $beUser = $this->getBackendUserAuthentication();
        // Page-selection permission clause (reading)
        $this->perms_clause = $beUser->getPagePermsClause(Permission::PAGE_SHOW);
        // This will hide records from display - it has nothing to do with user rights!!
        $pidList = $beUser->getTSConfig()['options.']['hideRecords.']['pages'] ?? '';
        if (!empty($pidList)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $this->perms_clause .= ' AND ' . $queryBuilder->expr()->notIn(
                'pages.uid',
                GeneralUtility::intExplode(',', $pidList)
            );
        }
        // Setting GPvars:
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        // The page id to operate from
        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $this->pagesOnly = $parsedBody['pagesOnly'] ?? $queryParams['pagesOnly'] ?? null;
        // Setting up the context sensitive menu:
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/PageActions');
        // Creating content
        $this->content = '';
        $this->content .= '<h1>'
            . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.pagetitle')
            . '</h1>';
        // Id a positive id is supplied, ask for the page record with permission information contained:
        if ($this->id > 0) {
            $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        }
        // If a page-record was returned, the user had read-access to the page.
        if ($this->pageinfo['uid']) {
            // Get record of parent page
            $this->pidInfo = BackendUtility::getRecord('pages', $this->pageinfo['pid']) ?: [];
            // Checking the permissions for the user with regard to the parent page: Can he create new pages, new
            // content record, new page after?
            if ($beUser->doesUserHaveAccess($this->pageinfo, 8)) {
                $this->newPagesInto = 1;
            }
            if ($beUser->doesUserHaveAccess($this->pageinfo, 16)) {
                $this->newContentInto = 1;
            }
            if (($beUser->isAdmin() || !empty($this->pidInfo)) && $beUser->doesUserHaveAccess($this->pidInfo, 8)) {
                $this->newPagesAfter = 1;
            }
        } elseif ($beUser->isAdmin()) {
            // Admins can do it all
            $this->newPagesInto = 1;
            $this->newContentInto = 1;
            $this->newPagesAfter = 0;
        } else {
            // People with no permission can do nothing
            $this->newPagesInto = 0;
            $this->newContentInto = 0;
            $this->newPagesAfter = 0;
        }
    }

    /**
     * Main processing, creating the list of new record tables to select from
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    protected function renderContent(ServerRequestInterface $request): ?ResponseInterface
    {
        // If there was a page - or if the user is admin (admins has access to the root) we proceed:
        if (!empty($this->pageinfo['uid']) || $this->getBackendUserAuthentication()->isAdmin()) {
            if (empty($this->pageinfo)) {
                // Explicitly pass an empty array to the docHeader
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
            } else {
                $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
            }
            // Acquiring TSconfig for this module/current page:
            $this->web_list_modTSconfig = BackendUtility::getPagesTSconfig($this->pageinfo['uid'])['mod.']['web_list.'] ?? [];
            $this->allowedNewTables = GeneralUtility::trimExplode(',', $this->web_list_modTSconfig['allowedNewTables'] ?? '', true);
            $this->deniedNewTables = GeneralUtility::trimExplode(',', $this->web_list_modTSconfig['deniedNewTables'] ?? '', true);
            // Acquiring TSconfig for this module/parent page:
            $this->web_list_modTSconfig_pid = BackendUtility::getPagesTSconfig($this->pageinfo['pid'])['mod.']['web_list.'] ?? [];
            $this->allowedNewTables_pid = GeneralUtility::trimExplode(',', $this->web_list_modTSconfig_pid['allowedNewTables'] ?? '', true);
            $this->deniedNewTables_pid = GeneralUtility::trimExplode(',', $this->web_list_modTSconfig_pid['deniedNewTables'] ?? '', true);
            // More init:
            if (!$this->isRecordCreationAllowedForTable('pages')) {
                $this->newPagesInto = 0;
            }
            if (!$this->isRecordCreationAllowedForTable('pages', $this->allowedNewTables_pid, $this->deniedNewTables_pid)) {
                $this->newPagesAfter = 0;
            }
            // Set header-HTML and return_url
            if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
                $title = strip_tags($this->pageinfo[$GLOBALS['TCA']['pages']['ctrl']['label']]);
            } else {
                $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
            }
            $this->moduleTemplate->setTitle($title);
            // GENERATE the HTML-output depending on mode (pagesOnly is the page wizard)
            // Regular new element:
            if (!$this->pagesOnly) {
                $this->renderNewRecordControls($request);
            } elseif ($this->isRecordCreationAllowedForTable('pages')) {
                // Pages only wizard
                $response = $this->renderPositionTree();

                if (!empty($response)) {
                    return $response;
                }
            }
            // Add all the content to an output section
            $this->content .= '<div>' . $this->code . '</div>';
            // Setting up the buttons and markers for docheader
            $this->getButtons();
            // Build the <body> for the module
            $this->moduleTemplate->setContent($this->content);
        }

        return null;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $lang = $this->getLanguageService();
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // Regular new element:
        if (!$this->pagesOnly) {
            // New page
            if ($this->isRecordCreationAllowedForTable('pages')) {
                $newPageButton = $buttonBar->makeLinkButton()
                    ->setHref(GeneralUtility::linkThisScript(['pagesOnly' => '1']))
                    ->setTitle($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newPage'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-page-new', Icon::SIZE_SMALL));
                $buttonBar->addButton($newPageButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // CSH
            $cshButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('new_regular');
            $buttonBar->addButton($cshButton);
        } elseif ($this->isRecordCreationAllowedForTable('pages')) {
            // Pages only wizard
            // CSH
            $buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'new_pages');
            $cshButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('new_pages');
            $buttonBar->addButton($cshButton);
        }
        // Back
        if ($this->returnUrl) {
            $returnButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($returnButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }

        if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
            // View
            $pagesTSconfig = BackendUtility::getPagesTSconfig($this->pageinfo['uid']);
            if (isset($pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
                $excludeDokTypes = GeneralUtility::intExplode(
                    ',',
                    $pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'],
                    true
                );
            } else {
                // exclude sysfolders and recycler by default
                $excludeDokTypes = [
                    PageRepository::DOKTYPE_RECYCLER,
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_SPACER
                ];
            }
            if (!in_array((int)$this->pageinfo['doktype'], $excludeDokTypes, true)) {
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick(BackendUtility::viewOnClick(
                        $this->pageinfo['uid'],
                        '',
                        BackendUtility::BEgetRootLine($this->pageinfo['uid'])
                    ))
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-view-page',
                        Icon::SIZE_SMALL
                    ));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 30);
            }
        }
    }

    /**
     * Renders the position map for pages wizard
     *
     * @return ResponseInterface|null
     */
    protected function renderPositionTree(): ?ResponseInterface
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $numberOfPages = $queryBuilder
            ->count('*')
            ->from('pages')
            ->execute()
            ->fetchColumn(0);

        if ($numberOfPages > 0) {
            $this->code .= '<h3>' . htmlspecialchars($this->getLanguageService()->getLL('selectPosition')) . ':</h3>';
            /** @var \TYPO3\CMS\Backend\Tree\View\PagePositionMap $positionMap */
            $positionMap = GeneralUtility::makeInstance(PagePositionMap::class, NewRecordPageTreeView::class);
            $this->code .= $positionMap->positionTree(
                $this->id,
                $this->pageinfo,
                $this->perms_clause,
                $this->returnUrl
            );
        } else {
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            // No pages yet, no need to prompt for position, redirect to page creation.
            $urlParameters = [
                'edit' => [
                    'pages' => [
                        0 => 'new'
                    ]
                ],
                'returnNewPageId' => 1,
                'returnUrl' => (string)$uriBuilder->buildUriFromRoute('db_new', ['id' => $this->id, 'pagesOnly' => '1'])
            ];
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            @ob_end_clean();

            return new RedirectResponse($url);
        }

        return null;
    }

    /**
     * Render controls for creating a regular new element (pages or records)
     *
     * @param ServerRequestInterface $request
     */
    protected function renderNewRecordControls(ServerRequestInterface $request): void
    {
        $lang = $this->getLanguageService();
        // Initialize array for accumulating table rows:
        $this->tRows = [];
        // Get TSconfig for current page
        $pageTS = BackendUtility::getPagesTSconfig($this->id);
        // Finish initializing new pages options with TSconfig
        // Each new page option may be hidden by TSconfig
        // Enabled option for the position of a new page
        $this->newPagesSelectPosition = !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageSelectPosition']);
        // Pseudo-boolean (0/1) for backward compatibility
        $displayNewPagesIntoLink = $this->newPagesInto && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageInside']);
        $displayNewPagesAfterLink = $this->newPagesAfter && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageAfter']);
        // Slight spacer from header:
        $this->code .= '';
        // New Page
        $table = 'pages';
        $v = $GLOBALS['TCA'][$table];
        $pageIcon = $this->moduleTemplate->getIconFactory()->getIconForRecord(
            $table,
            [],
            Icon::SIZE_SMALL
        )->render();
        $newPageIcon = $this->moduleTemplate->getIconFactory()->getIcon('actions-page-new', Icon::SIZE_SMALL)->render();
        $rowContent = '';
        // New pages INSIDE this pages
        $newPageLinks = [];
        if ($displayNewPagesIntoLink
            && $this->isTableAllowedOnPage('pages', $this->pageinfo)
            && $this->getBackendUserAuthentication()->check('tables_modify', 'pages')
            && $this->getBackendUserAuthentication()->workspaceCreateNewRecord(($this->pageinfo['_ORIG_uid'] ?: $this->id), 'pages')
        ) {
            // Create link to new page inside:
            $recordIcon = $this->moduleTemplate->getIconFactory()->getIconForRecord($table, [], Icon::SIZE_SMALL)->render();
            $newPageLinks[] = $this->renderLink(
                $recordIcon . htmlspecialchars($lang->sL($v['ctrl']['title'])) . ' (' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.inside')) . ')',
                $table,
                $this->id
            );
        }
        // New pages AFTER this pages
        if ($displayNewPagesAfterLink
            && $this->isTableAllowedOnPage('pages', $this->pidInfo)
            && $this->getBackendUserAuthentication()->check('tables_modify', 'pages')
            && $this->getBackendUserAuthentication()->workspaceCreateNewRecord($this->pidInfo['uid'], 'pages')
        ) {
            $newPageLinks[] = $this->renderLink(
                $pageIcon . htmlspecialchars($lang->sL($v['ctrl']['title'])) . ' (' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.after')) . ')',
                'pages',
                -$this->id
            );
        }
        // New pages at selection position
        if ($this->newPagesSelectPosition && $this->isRecordCreationAllowedForTable('pages')) {
            // Link to page-wizard:
            $newPageLinks[] = '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['pagesOnly' => 1])) . '">' . $pageIcon . htmlspecialchars($lang->getLL('pageSelectPosition')) . '</a>';
        }
        // Assemble all new page links
        $numPageLinks = count($newPageLinks);
        for ($i = 0; $i < $numPageLinks; $i++) {
            $rowContent .= '<li>' . $newPageLinks[$i] . '</li>';
        }
        if ($this->isRecordCreationAllowedForTable('pages')) {
            $rowContent = '<ul class="list-tree"><li>' . $newPageIcon . '<strong>' .
                $lang->getLL('createNewPage') . '</strong><ul>' . $rowContent . '</ul></li>';
        } else {
            $rowContent = '<ul class="list-tree"><li><ul>' . $rowContent . '</li></ul>';
        }
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // Compile table row
        $startRows = [$rowContent];
        $iconFile = [];
        // New tables (but not pages) INSIDE this pages
        $isAdmin = $this->getBackendUserAuthentication()->isAdmin();
        $newContentIcon = $this->moduleTemplate->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL)->render();
        if ($this->newContentInto) {
            if (is_array($GLOBALS['TCA'])) {
                $groupName = '';
                foreach ($GLOBALS['TCA'] as $table => $v) {
                    $rootLevelConfiguration = isset($v['ctrl']['rootLevel']) ? (int)$v['ctrl']['rootLevel'] : 0;
                    if ($table !== 'pages'
                        && $this->isRecordCreationAllowedForTable($table)
                        && $this->isTableAllowedOnPage($table, $this->pageinfo)
                        && $this->getBackendUserAuthentication()->check('tables_modify', $table)
                        && ($rootLevelConfiguration === -1 || ($this->id xor $rootLevelConfiguration))
                        && $this->getBackendUserAuthentication()->workspaceCreateNewRecord(($this->pageinfo['_ORIG_uid'] ? $this->pageinfo['_ORIG_uid'] : $this->id), $table)
                    ) {
                        $newRecordIcon = $this->moduleTemplate->getIconFactory()->getIconForRecord($table, [], Icon::SIZE_SMALL)->render();
                        $rowContent = '';
                        $thisTitle = '';
                        // Create new link for record:
                        $newLink = $this->renderLink(
                            $newRecordIcon . htmlspecialchars($lang->sL($v['ctrl']['title'])),
                            $table,
                            $this->id
                        );
                        // If the table is 'tt_content', add link to wizard
                        if ($table === 'tt_content') {
                            $groupName = $lang->getLL('createNewContent');
                            $rowContent = $newContentIcon
                                . '<strong>' . $lang->getLL('createNewContent') . '</strong>'
                                . '<ul>';
                            // If mod.newContentElementWizard.override is set, use that extension's wizard instead:
                            $moduleName = BackendUtility::getPagesTSconfig($this->id)['mod.']['newContentElementWizard.']['override']
                                ?? 'new_content_element_wizard';
                            /** @var \TYPO3\CMS\Core\Http\NormalizedParams */
                            $normalizedParams = $request->getAttribute('normalizedParams');
                            $url = (string)$uriBuilder->buildUriFromRoute($moduleName, ['id' => $this->id, 'returnUrl' => $normalizedParams->getRequestUri()]);
                            $rowContent .= '<li>' . $newLink . ' ' . BackendUtility::wrapInHelp($table, '') . '</li>'
                                . '<li>'
                                . '<a href="' . htmlspecialchars($url) . '" data-title="' . htmlspecialchars($this->getLanguageService()->getLL('newContentElement')) . '" class="t3js-toggle-new-content-element-wizard disabled">'
                                . $newContentIcon . htmlspecialchars($lang->getLL('clickForWizard'))
                                . '</a>'
                                . '</li>'
                                . '</ul>';
                        } else {
                            // Get the title
                            if ($v['ctrl']['readOnly'] || $v['ctrl']['hideTable'] || $v['ctrl']['is_static']) {
                                continue;
                            }
                            if ($v['ctrl']['adminOnly'] && !$isAdmin) {
                                continue;
                            }
                            $nameParts = explode('_', $table);
                            $thisTitle = '';
                            $_EXTKEY = '';
                            if ($nameParts[0] === 'tx' || $nameParts[0] === 'tt') {
                                // Try to extract extension name
                                $title = (string)($v['ctrl']['title'] ?? '');
                                if (strpos($title, 'LLL:EXT:') === 0) {
                                    $_EXTKEY = substr($title, 8);
                                    $_EXTKEY = substr($_EXTKEY, 0, strpos($_EXTKEY, '/'));
                                    if ($_EXTKEY !== '') {
                                        // First try to get localisation of extension title
                                        $temp = explode(':', substr($title, 9 + strlen($_EXTKEY)));
                                        $langFile = $temp[0];
                                        $thisTitle = $lang->sL('LLL:EXT:' . $_EXTKEY . '/' . $langFile . ':extension.title');
                                        // If no localisation available, read title from ext_emconf.php
                                        $extPath = ExtensionManagementUtility::extPath($_EXTKEY);
                                        $extEmConfFile = $extPath . 'ext_emconf.php';
                                        if (!$thisTitle && is_file($extEmConfFile)) {
                                            $EM_CONF = [];
                                            include $extEmConfFile;
                                            $thisTitle = $EM_CONF[$_EXTKEY]['title'];
                                        }
                                        $iconFile[$_EXTKEY] = '<img src="' . PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::getExtensionIcon($extPath, true)) . '" ' . 'width="16" height="16" ' . 'alt="' . $thisTitle . '" />';
                                    }
                                }
                                if (empty($thisTitle)) {
                                    $_EXTKEY = $nameParts[1];
                                    $thisTitle = $nameParts[1];
                                    $iconFile[$_EXTKEY] = '';
                                }
                            } else {
                                $_EXTKEY = 'system';
                                $thisTitle = $lang->getLL('system_records');
                                $iconFile['system'] = $this->moduleTemplate->getIconFactory()->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render();
                            }

                            if ($groupName === '' || $groupName !== $_EXTKEY) {
                                $groupName = empty($v['ctrl']['groupName']) ? $_EXTKEY : $v['ctrl']['groupName'];
                            }
                            $rowContent .= $newLink;
                        }
                        // Compile table row:
                        if ($table === 'tt_content') {
                            $startRows[] = '<li>' . $rowContent . '</li>';
                        } else {
                            $this->tRows[$groupName]['title'] = $thisTitle;
                            $this->tRows[$groupName]['html'][] = $rowContent;
                            $this->tRows[$groupName]['table'][] = $table;
                        }
                    }
                }
            }
        }
        // User sort
        if (isset($pageTS['mod.']['wizards.']['newRecord.']['order'])) {
            $this->newRecordSortList = GeneralUtility::trimExplode(',', $pageTS['mod.']['wizards.']['newRecord.']['order'], true);
        }
        uksort($this->tRows, [$this, 'sortTableRows']);
        // Compile table row:
        $finalRows = [];
        $finalRows[] = implode('', $startRows);
        foreach ($this->tRows as $key => $value) {
            $row = '<li>' . $iconFile[$key] . ' <strong>' . $value['title'] . '</strong><ul>';
            foreach ($value['html'] as $recordKey => $record) {
                $row .= '<li>' . $record . ' ' . BackendUtility::wrapInHelp($value['table'][$recordKey], '') . '</li>';
            }
            $row .= '</ul></li>';
            $finalRows[] = $row;
        }

        $finalRows[] = '</ul>';
        // Make table:
        $this->code .= implode('', $finalRows);
    }

    /**
     * User array sort function used by renderNewRecordControls
     *
     * @param string $a First array element for compare
     * @param string $b First array element for compare
     * @return int -1 for lower, 0 for equal, 1 for greater
     */
    protected function sortTableRows(string $a, string $b): int
    {
        if (!empty($this->newRecordSortList)) {
            if (in_array($a, $this->newRecordSortList) && in_array($b, $this->newRecordSortList)) {
                // Both are in the list, return relative to position in array
                $sub = array_search($a, $this->newRecordSortList) - array_search($b, $this->newRecordSortList);
                $ret = ($sub < 0 ? -1 : $sub == 0) ? 0 : 1;
            } elseif (in_array($a, $this->newRecordSortList)) {
                // First element is in array, put to top
                $ret = -1;
            } elseif (in_array($b, $this->newRecordSortList)) {
                // Second element is in array, put first to bottom
                $ret = 1;
            } else {
                // No element is in array, return alphabetic order
                $ret = strnatcasecmp($this->tRows[$a]['title'], $this->tRows[$b]['title']);
            }
            return $ret;
        }
        // Return alphabetic order
        return strnatcasecmp($this->tRows[$a]['title'], $this->tRows[$b]['title']);
    }

    /**
     * Links the string $code to a create-new form for a record in $table created on page $pid
     *
     * @param string $linkText Link text
     * @param string $table Table name (in which to create new record)
     * @param int $pid PID value for the "&edit['.$table.']['.$pid.']=new" command (positive/negative)
     * @param bool $addContentTable If $addContentTable is set, then a new tt_content record is created together with pages
     * @return string The link.
     */
    protected function renderLink(string $linkText, string $table, int $pid, bool $addContentTable = false): string
    {
        $urlParameters = [
            'edit' => [
                $table => [
                    $pid => 'new'
                ]
            ],
            'returnUrl' => $this->returnUrl
        ];

        if ($table === 'pages' && $addContentTable) {
            $urlParameters['tt_content']['prev'] = 'new';
            $urlParameters['returnNewPageId'] = 1;
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

        return '<a href="' . htmlspecialchars($url) . '">' . $linkText . '</a>';
    }

    /**
     * Returns TRUE if the tablename $checkTable is allowed to be created on the page with record $pid_row
     *
     * @param string $table Table name to check
     * @param array $page Potential parent page
     * @return bool Returns TRUE if the tablename $table is allowed to be created on the $page
     */
    protected function isTableAllowedOnPage(string $table, array $page): bool
    {
        if (empty($page)) {
            return $this->getBackendUserAuthentication()->isAdmin();
        }
        // be_users and be_groups may not be created anywhere but in the root.
        if ($table === 'be_users' || $table === 'be_groups') {
            return false;
        }
        // Checking doktype:
        $doktype = (int)$page['doktype'];
        if (!($allowedTableList = $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'])) {
            $allowedTableList = $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
        }
        // If all tables or the table is listed as an allowed type, return TRUE
        if (strstr($allowedTableList, '*') || GeneralUtility::inList($allowedTableList, $table)) {
            return true;
        }

        return false;
    }

    /**
     * Returns whether the record link should be shown for a table
     *
     * Returns TRUE if:
     * - $allowedNewTables and $deniedNewTables are empty
     * - the table is not found in $deniedNewTables and $allowedNewTables is not set or the $table tablename is found in
     *   $allowedNewTables
     *
     * If $table tablename is found in $allowedNewTables and $deniedNewTables,
     * $deniedNewTables has priority over $allowedNewTables.
     *
     * @param string $table Table name to test if in allowedTables
     * @param array $allowedNewTables Array of new tables that are allowed.
     * @param array $deniedNewTables Array of new tables that are not allowed.
     * @return bool Returns TRUE if a link for creating new records should be displayed for $table
     */
    protected function isRecordCreationAllowedForTable(string $table, array $allowedNewTables = [], array $deniedNewTables = []): bool
    {
        if (!$this->getBackendUserAuthentication()->check('tables_modify', $table)) {
            return false;
        }

        $allowedNewTables = $allowedNewTables ?: $this->allowedNewTables;
        $deniedNewTables = $deniedNewTables ?: $this->deniedNewTables;
        // No deny/allow tables are set:
        if (empty($allowedNewTables) && empty($deniedNewTables)) {
            return true;
        }

        return !in_array($table, $deniedNewTables) && (empty($allowedNewTables) || in_array($table, $allowedNewTables));
    }

    /**
     * Checks if sys_language records are present
     *
     * @return bool
     */
    protected function checkIfLanguagesExist(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_language');
        $queryBuilder->getRestrictions()->removeAll();

        $count = $queryBuilder
            ->count('uid')
            ->from('sys_language')
            ->execute()
            ->fetchColumn(0);
        return (bool)$count;
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
    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
