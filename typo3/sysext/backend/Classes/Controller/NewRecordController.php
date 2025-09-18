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
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbContext;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewRecordCreationLinksEvent;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\PagePositionMap;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script class for 'db_new' and 'db_new_pages'
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class NewRecordController
{
    protected array $pageinfo = [];
    protected array $pidInfo = [];
    protected array $newRecordSortList = [];

    protected bool $newPagesInto = false;
    protected bool $newContentInto = false;
    protected bool $newPagesAfter = false;

    /**
     * Determines, whether "Select Position" for new page should be shown
     */
    protected bool $newPagesSelectPosition = true;
    protected array $allowedNewTables = [];
    protected array $deniedNewTables = [];

    /**
     * @var int
     *
     * @see PageTreeView::expandNext()
     * @internal
     */
    public $id;

    protected string $returnUrl = '';
    protected string $perms_clause = '';
    protected array $tRows = [];

    protected ModuleTemplate $view;

    protected ServerRequestInterface $request;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly RecordFactory $recordFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // Redirect if there is still a link with ?pagesOnly=1
        if ($request->getQueryParams()['pagesOnly'] ?? null) {
            $uri = $this->uriBuilder->buildUriFromRoute('db_new_pages', ['id' => (int)($request->getQueryParams()['id'] ?? 0), 'returnUrl' => $request->getQueryParams()['returnUrl'] ?? null]);
            return new RedirectResponse($uri, 301);
        }

        $this->init($request);

        // If there was a page - or if the user is admin (admins has access to the root) we proceed, otherwise just output the header
        if (empty($this->pageinfo['uid']) && !$this->getBackendUserAuthentication()->isAdmin()) {
            return $this->view->renderResponse('NewRecord/NewRecord');
        }

        $recordControls = $this->getNewRecordControls();

        if (count($recordControls) === 1) {
            $items = current($recordControls)['items'] ?? [];
            if (count($items) === 1) {
                $item = current($items);
                return new RedirectResponse($item['url'], 301);
            }
        }

        $this->view->assign('recordTypeGroups', $recordControls);

        // Setting up the buttons and markers for docheader (done after permissions are checked)
        $this->getButtons();
        return $this->view->renderResponse('NewRecord/NewRecord');
    }

    /**
     * Pages only wizard
     */
    public function newPageAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);

        // If there was a page - or if the user is admin (admins has access to the root) we proceed, otherwise just output the header
        if ((empty($this->pageinfo['uid']) && !$this->getBackendUserAuthentication()->isAdmin()) || !$this->isRecordCreationAllowedForTable('pages')) {
            return $this->view->renderResponse('NewRecord/NewPagePosition');
        }
        if (!$this->doPageRecordsExistInSystem()) {
            // No pages yet, no need to prompt for position, redirect to page creation.
            $urlParameters = [
                'edit' => [
                    'pages' => [
                        0 => 'new',
                    ],
                ],
                'returnNewPageId' => 1,
                // @todo add module context
                'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('db_new_pages', ['id' => $this->id]),
            ];
            $url = $this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            return new RedirectResponse($url);
        }
        $positionMap = GeneralUtility::makeInstance(PagePositionMap::class);
        $content = $positionMap->positionTree(
            $this->id,
            $this->pageinfo,
            $this->perms_clause,
            $this->returnUrl,
            $request
        );
        $this->view->assign('pagePositionMapForPagesOnly', $content);
        // Setting up the buttons and markers for docheader (done after permissions are checked)
        $this->getButtons(true);
        return $this->view->renderResponse('NewRecord/NewPagePosition');
    }

    /**
     * Constructor function for the class
     */
    protected function init(ServerRequestInterface $request): void
    {
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->request = $request;
        $beUser = $this->getBackendUserAuthentication();
        // Page-selection permission clause (reading)
        $this->perms_clause = $beUser->getPagePermsClause(Permission::PAGE_SHOW);
        // This will hide records from display - it has nothing to do with user rights!!
        $pidList = (string)($beUser->getTSConfig()['options.']['hideRecords.']['pages'] ?? '');
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
        // Setting up the context sensitive menu:
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/new-content-element-wizard-button.js');
        // If a positive id is supplied, ask for the page record with permission information contained:
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
            $breadcrumbContext = new BreadcrumbContext(
                $this->recordFactory->createResolvedRecordFromDatabaseRow('pages', $this->pageinfo),
                []
            );
            $this->view->getDocHeaderComponent()->setBreadcrumbContext($breadcrumbContext);
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
        $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        if ($this->pageinfo['uid'] ?? false) {
            $labelCapability = $this->tcaSchemaFactory->get('pages')->getCapability(TcaSchemaCapability::Label);
            if ($labelCapability->hasPrimaryField()) {
                $title = strip_tags($this->pageinfo[$labelCapability->getPrimaryFieldName()]);
            }
        }
        $this->view->setTitle($title);
        // Acquiring TSconfig for this module/current page:
        $web_list_modTSconfig = BackendUtility::getPagesTSconfig($this->pageinfo['uid'] ?? 0)['mod.']['web_list.'] ?? [];
        $this->allowedNewTables = GeneralUtility::trimExplode(',', $web_list_modTSconfig['allowedNewTables'] ?? '', true);
        $this->deniedNewTables = GeneralUtility::trimExplode(',', $web_list_modTSconfig['deniedNewTables'] ?? '', true);
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
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(bool $createPage = false): void
    {
        $lang = $this->getLanguageService();
        $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();
        // Regular new element:
        if (!$createPage) {
            // New page
            if ($this->isRecordCreationAllowedForTable('pages')) {
                $newPageButton = $buttonBar->makeLinkButton()
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute('db_new_pages', ['id' => $this->id, 'returnUrl' => $this->returnUrl]))
                    ->setTitle($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newPage'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-page-new', IconSize::SMALL));
                $buttonBar->addButton($newPageButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
        }
        // Back
        if ($this->returnUrl) {
            $returnButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL));
            $buttonBar->addButton($returnButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }

        if ($this->pageinfo['uid'] ?? false) {
            // View
            $previewUriBuilder = PreviewUriBuilder::create($this->pageinfo);
            if ($previewUriBuilder->isPreviewable()) {
                $previewDataAttributes = $previewUriBuilder
                    ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
                    ->buildDispatcherDataAttributes();
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes ?? [])
                    ->setDisabled(!$previewDataAttributes)
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-page', IconSize::SMALL))
                    ->setShowLabelText(true);
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 30);
            }
        }
    }

    protected function doPageRecordsExistInSystem(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $numberOfPages = $queryBuilder
            ->count('*')
            ->from('pages')
            ->executeQuery()
            ->fetchOne();
        return $numberOfPages > 0;
    }

    /**
     * @return list<string>
     */
    protected function getAllowedTables(): array
    {
        $allowedTables = [];

        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $isTablesAllowed = match ($table) {
                'pages' => $this->isRecordCreationAllowedForTable('pages'),
                'tt_content' => false, // Skip, as inserting content elements is part of the page module
                default => $this->newContentInto && $this->isRecordCreationAllowedForTable($table) && $this->isTableAllowedOnPage($schema, $this->pageinfo)
            };

            if ($isTablesAllowed) {
                $allowedTables[] = $table;
            }
        }

        return $allowedTables;
    }

    /**
     * Render controls for creating a regular new element (pages or records)
     */
    protected function getNewRecordControls(): array
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
        $iconFile = [
            'backendaccess' => $this->iconFactory->getIcon('status-user-group-backend', IconSize::SMALL),
            'content' => $this->iconFactory->getIcon('content-panel', IconSize::SMALL)->render(),
            'frontendaccess' => $this->iconFactory->getIcon('status-user-group-frontend', IconSize::SMALL),
            'system' => $this->iconFactory->getIcon('apps-pagetree-root', IconSize::SMALL),
        ];
        $groupTitles = [
            'backendaccess' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:recordgroup.backendaccess'),
            'content' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:recordgroup.content'),
            'frontendaccess' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:recordgroup.frontendaccess'),
            'system' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:system_records'),
        ];
        $groupedLinksOnTop = [];
        foreach ($this->getAllowedTables() as $table) {
            $schema = $this->tcaSchemaFactory->get($table);
            $ctrlTitle = $schema->getTitle();

            if ($table === 'pages') {
                // New pages INSIDE this pages
                $newPageLinks = [];
                $hasPageTypesForDirectCreation = $this->hasRecordTypesForDirectCreation($schema);
                if ($displayNewPagesIntoLink && $this->isTableAllowedOnPage($schema, $this->pageinfo)) {
                    // Create link to new page inside
                    $newPageLinks['inside'] = [
                        'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL),
                        'label' => $lang->sL($ctrlTitle) . ' (' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.inside') . ')',
                    ];
                    if ($hasPageTypesForDirectCreation) {
                        $newPageLinks['inside']['types'] = $this->getRecordTypesForDirectCreation($schema, $this->id);
                    } else {
                        $newPageLinks['inside']['url'] = $this->renderLink($table, $this->id);
                    }
                }
                // New pages AFTER this pages
                if ($displayNewPagesAfterLink && $this->isTableAllowedOnPage($schema, $this->pidInfo)) {
                    $newPageLinks['after'] = [
                        'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL),
                        'label' => $lang->sL($ctrlTitle) . ' (' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.after') . ')',
                    ];
                    if ($hasPageTypesForDirectCreation) {
                        $newPageLinks['after']['types'] = $this->getRecordTypesForDirectCreation($schema, -$this->id);
                    } else {
                        $newPageLinks['after']['url'] = $this->renderLink($table, -$this->id);
                    }
                }
                // New pages at selection position
                if ($this->newPagesSelectPosition) {
                    // Link to page-wizard
                    $newPageLinks['select_position'] = [
                        'url' => $this->renderPageSelectPositionLink(),
                        'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL),
                        'label' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:pageSelectPosition'),
                    ];
                }
                if (!empty($newPageLinks)) {
                    $groupedLinksOnTop['pages'] = [
                        'title' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:createNewPage'),
                        'icon' => $this->iconFactory->getIcon('actions-page-new', IconSize::SMALL),
                        'items' => $newPageLinks,
                    ];
                }
            } else {
                $nameParts = explode('_', $table);
                $groupName = $schema->getRawConfiguration()['groupName'] ?? null;
                if (!isset($iconFile[$groupName]) || $nameParts[0] === 'tx' || $nameParts[0] === 'tt') {
                    $groupName = $groupName ?? $nameParts[1] ?? null;
                    // Try to extract extension name
                    if ($groupName) {
                        $_EXTKEY = '';
                        $titleIsTranslatableLabel = str_starts_with($ctrlTitle, 'LLL:EXT:');
                        if ($titleIsTranslatableLabel) {
                            // In case the title is a locallang reference, we can simply
                            // extract the extension name from the given extension path.
                            $_EXTKEY = substr($ctrlTitle, 8);
                            $_EXTKEY = substr($_EXTKEY, 0, (int)strpos($_EXTKEY, '/'));
                        } elseif (ExtensionManagementUtility::isLoaded($groupName)) {
                            // In case $title is not a locallang reference, we check the groupName to
                            // be a valid extension key. This most probably work since by convention the
                            // first part after tx_ / tt_ is the extension key.
                            $_EXTKEY = $groupName;
                        }
                        // Fetch the group title from the extension name
                        if ($_EXTKEY !== '') {
                            // Try to get the extension title
                            $package = GeneralUtility::makeInstance(PackageManager::class)->getPackage($_EXTKEY);
                            $groupTitle = $lang->sL('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:extension.title');
                            // If no localisation available, read title from the Package MetaData
                            if (!$groupTitle) {
                                $groupTitle = $package->getPackageMetaData()->getTitle();
                            }
                            $extensionIcon = $package->getResources()->getPackageIcon();
                            if ($extensionIcon !== null) {
                                $iconFile[$groupName] = '<img src="' . $this->resourcePublisher->generateUri($extensionIcon, $this->request) . '" width="16" height="16" alt="' . $groupTitle . '" />';
                            }
                            if (!empty($groupTitle)) {
                                $groupTitles[$groupName] = $groupTitle;
                            } else {
                                $groupTitles[$groupName] = ucwords($_EXTKEY);
                            }
                        }
                    } else {
                        // Fall back to "system" in case no $groupName could be found
                        $groupName = 'system';
                    }
                }
                $this->tRows[$groupName]['title'] = $this->tRows[$groupName]['title'] ?? $groupTitles[$groupName] ?? $nameParts[1] ?? $ctrlTitle;
                $this->tRows[$groupName]['icon'] = $this->tRows[$groupName]['icon'] ?? $iconFile[$groupName] ?? $iconFile['system'] ?? '';
                if ($schema->supportsSubSchema()
                    && !$schema->getSubSchemaTypeInformation()->isPointerToForeignFieldInForeignSchema()
                    && $this->hasRecordTypesForDirectCreation($schema)
                ) {
                    $this->tRows[$groupName]['items'][$table]['label'] = $lang->sL($ctrlTitle);
                    $this->tRows[$groupName]['items'][$table]['icon'] = $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL);
                    $this->tRows[$groupName]['items'][$table]['types'] = $this->getRecordTypesForDirectCreation($schema, $this->id);
                } else {
                    $this->tRows[$groupName]['items'][$table] = [
                        'url' => $this->renderLink($table, $this->id),
                        'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL)->render(),
                        'label' => $lang->sL($ctrlTitle),
                    ];
                }
            }
        }
        // User sort
        if (isset($pageTS['mod.']['wizards.']['newRecord.']['order'])) {
            $this->newRecordSortList = GeneralUtility::trimExplode(',', $pageTS['mod.']['wizards.']['newRecord.']['order'], true);
        }
        uksort($this->tRows, $this->sortTableRows(...));
        $this->tRows = array_merge($groupedLinksOnTop, $this->tRows);

        $this->tRows = $this->eventDispatcher->dispatch(
            new ModifyNewRecordCreationLinksEvent($this->tRows, $pageTS, $this->id, $this->request)
        )->groupedCreationLinks;

        return $this->tRows;
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
                $ret = strnatcasecmp($this->tRows[$a]['title'] ?? '', $this->tRows[$b]['title'] ?? '');
            }
            return $ret;
        }
        // Return alphabetic order
        return strnatcasecmp($this->tRows[$a]['title'] ?? '', $this->tRows[$b]['title'] ?? '');
    }

    /**
     * Links the string $code to a create-new form for a record in $table created on page $pid
     *
     * @param string $table Table name (in which to create new record)
     * @param int $pid PID value for the "&edit['.$table.']['.$pid.']=new" command (positive/negative)
     * @param array $additionalParams Additional params, such as "defVals" tp be added to the link
     * @return string The link.
     */
    protected function renderLink(string $table, int $pid, array $additionalParams = []): string
    {
        $params = [
            'edit' => [
                $table => [
                    $pid => 'new',
                ],
            ],
            'returnUrl' => $this->returnUrl ?: $this->request->getAttribute('normalizedParams')->getRequestUri(),
        ];

        if ($additionalParams) {
            $params = array_replace_recursive($params, $additionalParams);
        }

        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', $params);
    }

    /**
     * Generate link to the page position selection "view"
     */
    protected function renderPageSelectPositionLink(): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute(
            'db_new_pages',
            [
                'id' => $this->id,
                'returnUrl' => $this->returnUrl ?: $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]
        );
    }

    /**
     * Returns TRUE if the tablename $checkTable is allowed to be created on the page with record $pid_row
     *
     * @param TcaSchema $schema Table schema
     * @param array $page Potential parent page
     * @return bool Returns TRUE if the tablename $table is allowed to be created on the $page
     */
    protected function isTableAllowedOnPage(TcaSchema $schema, array $page): bool
    {
        $rootLevelCapability = $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel);

        $rootLevelConstraintMatches = ($rootLevelCapability->canExistOnRootLevel() && $this->id === 0) || ($this->id && $rootLevelCapability->canExistOnPages());
        if (empty($page)) {
            return $rootLevelConstraintMatches && $this->getBackendUserAuthentication()->isAdmin();
        }
        if (!$this->getBackendUserAuthentication()->workspaceCanCreateNewRecord($schema->getName())) {
            return false;
        }
        // Checking doktype
        $isAllowed = GeneralUtility::makeInstance(PageDoktypeRegistry::class)->isRecordTypeAllowedForDoktype($schema->getName(), $page['doktype']);
        return $rootLevelConstraintMatches && $isAllowed;
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

        $schema = $this->tcaSchemaFactory->get($table);

        if ($schema->hasCapability(TcaSchemaCapability::AccessReadOnly)
            || $schema->hasCapability(TcaSchemaCapability::HideInUi)
            || ($schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)  && !$this->getBackendUserAuthentication()->isAdmin())
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

    protected function hasRecordTypesForDirectCreation(TcaSchema $schema): bool
    {
        if (count($schema->getSubSchemata()) <= 1) {
            return false;
        }
        foreach ($schema->getSubSchemata() as $subSchema) {
            if ((bool)($subSchema->getRawConfiguration()['creationOptions']['enableDirectRecordTypeCreation'] ?? true) === false) {
                continue;
            }
            return true;
        }
        return false;
    }

    protected function getRecordTypesForDirectCreation(TcaSchema $schema, int $positionId): array
    {
        $recordTypes = [];
        $lang = $this->getLanguageService();
        $recordTypeField = $schema->getSubSchemaTypeInformation()->getFieldName();
        foreach ($schema->getSubSchemata() as $subSchema) {
            $creationOptions = $subSchema->getRawConfiguration()['creationOptions'] ?? [];
            if ((bool)($creationOptions['enableDirectRecordTypeCreation'] ?? true) === false) {
                continue;
            }
            $recordTypeName = array_map(trim(...), explode('.', $subSchema->getName(), 2))[1] ?? '';
            $recordTypes[$recordTypeName] = [
                'url' => $this->renderLink($schema->getName(), $positionId, [
                    'defVals' => [
                        $schema->getName() => [
                            $recordTypeField => $recordTypeName,
                        ],
                    ],
                ]),
                'icon' => $this->iconFactory->getIconForRecord($schema->getName(), [$recordTypeField => $recordTypeName], IconSize::SMALL),
                'label' => $lang->sL($creationOptions['title'] ?? '')
                    ?: $lang->sL(BackendUtility::getLabelFromItemListMerged($this->id, $schema->getName(), $recordTypeField, $recordTypeName))
                    ?: $recordTypeName,
            ];
        }
        return $recordTypes;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
