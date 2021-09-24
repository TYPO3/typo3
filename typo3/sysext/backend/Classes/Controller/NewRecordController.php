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
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\NewRecordPageTreeView;
use TYPO3\CMS\Backend\Tree\View\PagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script class for 'db_new'
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class NewRecordController
{
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

    protected bool $newPagesInto = false;
    protected bool $newContentInto = false;
    protected bool $newPagesAfter = false;

    /**
     * Determines, whether "Select Position" for new page should be shown
     *
     * @var bool
     */
    protected $newPagesSelectPosition = true;

    /**
     * @var array
     */
    protected $allowedNewTables;

    /**
     * @var array
     */
    protected $deniedNewTables;

    /**
     * @var int
     *
     * @see NewRecordPageTreeView::expandNext()
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
    protected $tRows = [];

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var StandaloneView
     */
    protected $view;

    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
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
        $this->initializeView('NewRecord');
        $this->init($request);

        // If there was a page - or if the user is admin (admins has access to the root) we proceed, otherwise just output the header
        if (empty($this->pageinfo['uid']) && !$this->getBackendUserAuthentication()->isAdmin()) {
            $this->moduleTemplate->setContent($this->view->render());
            return new HtmlResponse($this->moduleTemplate->renderContent());
        }

        $response = $this->renderContent($request);
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        // Setting up the buttons and markers for docheader (done after permissions are checked)
        $this->getButtons();
        // Build the <body> for the module
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Constructor function for the class
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request): void
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
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
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/PageActions');
        // Id a positive id is supplied, ask for the page record with permission information contained:
        if ($this->id > 0) {
            $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause) ?: [];
        }
        // If a page-record was returned, the user had read-access to the page.
        if ($this->pageinfo['uid'] ?? false) {
            // Get record of parent page
            $this->pidInfo = BackendUtility::getRecord('pages', ($this->pageinfo['pid'] ?? 0)) ?? [];
            // Checking the permissions for the user with regard to the parent page: Can he create new pages, new
            // content record, new page after?
            if ($beUser->doesUserHaveAccess($this->pageinfo, Permission::PAGE_NEW)) {
                $this->newPagesInto = true;
            }
            if ($beUser->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)) {
                $this->newContentInto = true;
            }
            if (($beUser->isAdmin() || !empty($this->pidInfo)) && $beUser->doesUserHaveAccess($this->pidInfo, Permission::PAGE_NEW)) {
                $this->newPagesAfter = true;
            }
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        } elseif ($beUser->isAdmin()) {
            // Admins can do it all
            $this->newPagesInto = true;
            $this->newContentInto = true;
            $this->newPagesAfter = false;
        } else {
            // People with no permission can do nothing
            $this->newPagesInto = false;
            $this->newContentInto = false;
            $this->newPagesAfter = false;
        }
        if ($this->pageinfo['uid'] ?? false) {
            $title = strip_tags($this->pageinfo[$GLOBALS['TCA']['pages']['ctrl']['label']]);
        } else {
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        }
        $this->moduleTemplate->setTitle($title);
        // Acquiring TSconfig for this module/current page:
        $web_list_modTSconfig = BackendUtility::getPagesTSconfig($this->pageinfo['uid'] ?? 0)['mod.']['web_list.'] ?? [];
        $this->allowedNewTables = GeneralUtility::trimExplode(',', $web_list_modTSconfig['allowedNewTables'] ?? '', true);
        $this->deniedNewTables = GeneralUtility::trimExplode(',', $web_list_modTSconfig['deniedNewTables'] ?? '', true);
    }

    /**
     * Main processing, creating the list of new record tables to select from
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|string
     */
    protected function renderContent(ServerRequestInterface $request)
    {
        // Acquiring TSconfig for this module/parent page
        $web_list_modTSconfig_pid = BackendUtility::getPagesTSconfig($this->pageinfo['pid'] ?? 0)['mod.']['web_list.'] ?? [];
        $allowedNewTables_pid = GeneralUtility::trimExplode(',', $web_list_modTSconfig_pid['allowedNewTables'] ?? '', true);
        $deniedNewTables_pid = GeneralUtility::trimExplode(',', $web_list_modTSconfig_pid['deniedNewTables'] ?? '', true);
        if (!$this->isRecordCreationAllowedForTable('pages')) {
            $this->newPagesInto = false;
        }
        if (!$this->isRecordCreationAllowedForTable('pages', $allowedNewTables_pid, $deniedNewTables_pid)) {
            $this->newPagesAfter = false;
        }
        // GENERATE the HTML-output depending on mode (pagesOnly is the page wizard)
        if (!$this->pagesOnly) {
            // Regular new record
            $this->renderNewRecordControls($request);
            return '';
        }
        if ($this->isRecordCreationAllowedForTable('pages')) {
            // Pages only wizard
            $response = $this->renderPositionTree();
            if ($response instanceof ResponseInterface) {
                return $response;
            }
            if (is_string($response)) {
                $this->view->assign('pagePositionMapForPagesOnly', $response);
            }
        }
        return '';
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
                    ->setHref($this->uriBuilder->buildUriFromRoute('db_new', ['id' => $this->id, 'pagesOnly' => 1, 'returnUrl' => $this->returnUrl]))
                    ->setTitle($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newPage'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL));
                $buttonBar->addButton($newPageButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // CSH
            $cshButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('new_regular');
            $buttonBar->addButton($cshButton);
        } elseif ($this->isRecordCreationAllowedForTable('pages')) {
            // Pages only wizard
            // CSH
            $cshButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('new_pages');
            $buttonBar->addButton($cshButton);
        }
        // Back
        if ($this->returnUrl) {
            $returnButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($returnButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }

        if ($this->pageinfo['uid'] ?? false) {
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
                    PageRepository::DOKTYPE_SPACER,
                ];
            }
            if (!in_array((int)$this->pageinfo['doktype'], $excludeDokTypes, true)) {
                $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageinfo['uid'])
                    ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
                    ->buildDispatcherDataAttributes();
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes ?? [])
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon(
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
     * @return ResponseInterface|string
     */
    protected function renderPositionTree()
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
            ->fetchOne();

        if ($numberOfPages > 0) {
            $positionMap = GeneralUtility::makeInstance(PagePositionMap::class, NewRecordPageTreeView::class);
            return $positionMap->positionTree(
                $this->id,
                $this->pageinfo,
                $this->perms_clause,
                $this->returnUrl
            );
        }
        // No pages yet, no need to prompt for position, redirect to page creation.
        $urlParameters = [
            'edit' => [
                'pages' => [
                    0 => 'new',
                ],
            ],
            'returnNewPageId' => 1,
            'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('db_new', ['id' => $this->id, 'pagesOnly' => '1']),
        ];
        $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
        return new RedirectResponse($url);
    }

    /**
     * Render controls for creating a regular new element (pages or records)
     *
     * @param ServerRequestInterface $request
     */
    protected function renderNewRecordControls(ServerRequestInterface $request): void
    {
        $lang = $this->getLanguageService();
        // Get TSconfig for current page
        $pageTS = BackendUtility::getPagesTSconfig($this->id);
        // Finish initializing new pages options with TSconfig
        // Each new page option may be hidden by TSconfig
        // Enabled option for the position of a new page
        $this->newPagesSelectPosition = !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageSelectPosition']);
        $displayNewPagesIntoLink = $this->newPagesInto && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageInside']);
        $displayNewPagesAfterLink = $this->newPagesAfter && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageAfter']);
        $iconFile = [];
        $groupName = '';
        $groupedLinksOnTop = [];
        foreach ($GLOBALS['TCA'] ?? [] as $table => $v) {
            switch ($table) {
                // New page
                case 'pages':
                    if (!$this->isRecordCreationAllowedForTable('pages')) {
                        break;
                    }
                    // New pages INSIDE this pages
                    $newPageLinks = [];
                    if ($displayNewPagesIntoLink
                        && $this->isTableAllowedOnPage('pages', $this->pageinfo)
                    ) {
                        // Create link to new page inside
                        $newPageLinks[] = $this->renderLink(
                            htmlspecialchars($lang->sL($v['ctrl']['title'])) . ' (' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.inside')) . ')',
                            $table,
                            $this->id
                        );
                    }
                    // New pages AFTER this pages
                    if ($displayNewPagesAfterLink
                        && $this->isTableAllowedOnPage('pages', $this->pidInfo)
                    ) {
                        $newPageLinks[] = $this->renderLink(
                            htmlspecialchars($lang->sL($v['ctrl']['title'])) . ' (' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.after')) . ')',
                            'pages',
                            -$this->id
                        );
                    }
                    // New pages at selection position
                    if ($this->newPagesSelectPosition) {
                        // Link to page-wizard
                        $newPageLinks[] = $this->renderPageSelectPositionLink();
                    }
                    $groupedLinksOnTop['pages'] = [
                        'title' => $lang->getLL('createNewPage'),
                        'icon' => 'actions-page-new',
                        'items' => $newPageLinks,
                    ];
                break;
                case 'tt_content':
                    if (!$this->newContentInto || !$this->isRecordCreationAllowedForTable($table) || !$this->isTableAllowedOnPage($table, $this->pageinfo)) {
                        break;
                    }
                    $groupedLinksOnTop['tt_content'] = [
                        'title' => $lang->getLL('createNewContent'),
                        'icon' => 'actions-document-new',
                        'items' => [
                            $this->renderLink(htmlspecialchars($lang->sL($v['ctrl']['title'])), $table, $this->id),
                            $this->renderNewContentElementWizardLink(),
                        ],
                    ];
                    break;
                default:
                    if (!$this->newContentInto || !$this->isRecordCreationAllowedForTable($table) || !$this->isTableAllowedOnPage($table, $this->pageinfo)) {
                        break;
                    }
                    $nameParts = explode('_', $table);
                    $thisTitle = '';
                    $_EXTKEY = '';
                    if ($nameParts[0] === 'tx' || $nameParts[0] === 'tt') {
                        // Try to extract extension name
                        $title = (string)($v['ctrl']['title'] ?? '');
                        if (strpos($title, 'LLL:EXT:') === 0) {
                            $_EXTKEY = substr($title, 8);
                            $_EXTKEY = substr($_EXTKEY, 0, (int)strpos($_EXTKEY, '/'));
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
                                $extensionIcon = ExtensionManagementUtility::getExtensionIcon($extPath);
                                if (!empty($extensionIcon)) {
                                    $iconFile[$_EXTKEY] = '<img src="' . PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::getExtensionIcon($extPath, true)) . '" width="16" height="16" alt="' . $thisTitle . '" />';
                                }
                            }
                        }
                        if (empty($thisTitle)) {
                            $_EXTKEY = $thisTitle = $nameParts[1];
                        }
                    } else {
                        $_EXTKEY = 'system';
                        $thisTitle = $lang->getLL('system_records');
                        $iconFile['system'] = $this->iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render();
                    }

                    if ($groupName === '' || $groupName !== $_EXTKEY) {
                        $groupName = empty($v['ctrl']['groupName']) ? $_EXTKEY : $v['ctrl']['groupName'];
                    }
                    $this->tRows[$groupName]['title'] = $thisTitle;
                    $this->tRows[$groupName]['icon'] = $iconFile[$groupName] ?? '';
                    $this->tRows[$groupName]['html'][$table] = $this->renderLink(htmlspecialchars($lang->sL($v['ctrl']['title'])), $table, $this->id);
            }
        }
        // User sort
        if (isset($pageTS['mod.']['wizards.']['newRecord.']['order'])) {
            $this->newRecordSortList = GeneralUtility::trimExplode(',', $pageTS['mod.']['wizards.']['newRecord.']['order'], true);
        }
        uksort($this->tRows, [$this, 'sortTableRows']);
        $this->view->assign('groupedLinksOnTop', $groupedLinksOnTop);
        $this->view->assign('recordTypeGroups', $this->tRows);
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
     * @return string The link.
     */
    protected function renderLink(string $linkText, string $table, int $pid): string
    {
        $recordLink = (string)$this->uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    $table => [
                        $pid => 'new',
                    ],
                ],
                'returnUrl' => $this->returnUrl,
            ]
        );
        return '
            <a class="list-group-item list-group-item-action" href="' . htmlspecialchars($recordLink) . '">
                ' . $this->iconFactory->getIconForRecord($table, [], Icon::SIZE_SMALL)->render() . '
                ' . $linkText . '
            </a>';
    }

    /**
     * Generate link to the page position selection "view"
     */
    protected function renderPageSelectPositionLink(): string
    {
        $url = (string)$this->uriBuilder->buildUriFromRoute(
            'db_new',
            [
                'id' => $this->id,
                'pagesOnly' => 1,
                'returnUrl' => $this->returnUrl,
            ]
        );
        return '
            <a href="' . htmlspecialchars($url) . '" class="list-group-item list-group-item-action">
                ' . $this->iconFactory->getIconForRecord('pages', [], Icon::SIZE_SMALL)->render() . '
                ' . htmlspecialchars($this->getLanguageService()->getLL('pageSelectPosition')) . '
            </a>';
    }

    /**
     * Generate link to the new content element wizard
     */
    protected function renderNewContentElementWizardLink(): string
    {
        // If mod.newContentElementWizard.override is set, use that extension's wizard instead:
        $moduleName = BackendUtility::getPagesTSconfig($this->id)['mod.']['newContentElementWizard.']['override'] ?? 'new_content_element_wizard';
        $url = (string)$this->uriBuilder->buildUriFromRoute($moduleName, ['id' => $this->id, 'returnUrl' => $this->returnUrl]);
        $title = htmlspecialchars($this->getLanguageService()->getLL('newContentElement'));
        return '
            <a href="' . htmlspecialchars($url) . '" title="' . $title . '" data-title="' . $title . '" class="list-group-item list-group-item-action t3js-toggle-new-content-element-wizard disabled">
                ' . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() . '
                ' . htmlspecialchars($this->getLanguageService()->getLL('clickForWizard')) . '
            </a>';
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
        $rootLevelConfiguration = (int)($GLOBALS['TCA'][$table]['ctrl']['rootLevel'] ?? 0);
        $rootLevelConstraintMatches = $rootLevelConfiguration === -1 || ($this->id xor $rootLevelConfiguration);
        if (empty($page)) {
            return $rootLevelConstraintMatches && $this->getBackendUserAuthentication()->isAdmin();
        }
        if (!$this->getBackendUserAuthentication()->workspaceCanCreateNewRecord($table)) {
            return false;
        }
        // Checking doktype
        $doktype = (int)$page['doktype'];
        $allowedTableList = $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'] ?? $GLOBALS['PAGES_TYPES']['default']['allowedTables'] ?? '';
        // If all tables or the table is listed as an allowed type, return TRUE
        return $rootLevelConstraintMatches && (strpos($allowedTableList, '*') !== false || GeneralUtility::inList($allowedTableList, $table));
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

        $ctrl = $GLOBALS['TCA'][$table]['ctrl'];
        if (($ctrl['readOnly'] ?? false)
            || ($ctrl['hideTable'] ?? false)
            || ($ctrl['is_static'] ?? false)
            || (($ctrl['adminOnly'] ?? false) && !$this->getBackendUserAuthentication()->isAdmin())
        ) {
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
     * Initializes the view by setting the templateName
     *
     * @param string $templateName
     */
    protected function initializeView(string $templateName): void
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        $this->view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
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
