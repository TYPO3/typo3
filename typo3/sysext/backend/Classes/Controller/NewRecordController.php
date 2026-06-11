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
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\SchemaLabelResolver;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the 'Create new record' view of the 'db_new' route, reachable from the records module,
 * listing the record types a user may create on a given page.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class NewRecordController
{
    public function __construct(
        private ComponentFactory $componentFactory,
        private ConnectionPool $connectionPool,
        private IconFactory $iconFactory,
        private PageDoktypeRegistry $pageDoktypeRegistry,
        private PageRenderer $pageRenderer,
        private PackageManager $packageManager,
        private UriBuilder $uriBuilder,
        private RecordFactory $recordFactory,
        private ModuleTemplateFactory $moduleTemplateFactory,
        private TcaSchemaFactory $tcaSchemaFactory,
        private EventDispatcherInterface $eventDispatcher,
        private SystemResourceFactory $resourceFactory,
        private SystemResourcePublisherInterface $resourcePublisher,
        private SchemaLabelResolver $schemaLabelResolver,
    ) {}

    /**
     * Collects the record types creatable on the requested page and renders the selection view,
     * redirecting straight to the edit form when only a single creation target is available.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $pageinfo = [];
        $pidInfo = [];
        $newPagesInto = false;
        $newContentInto = false;
        $newPagesAfter = false;
        $tRows = [];
        $beUser = $this->getBackendUserAuthentication();
        // Page-selection permission clause (reading)
        $permsClause = $beUser->getPagePermsClause(Permission::PAGE_SHOW);
        // This will hide records from display - it has nothing to do with user rights!!
        $pidList = (string)($beUser->getTSConfig()['options.']['hideRecords.']['pages'] ?? '');
        if (!empty($pidList)) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $permsClause .= ' AND ' . $queryBuilder->expr()->notIn(
                'pages.uid',
                GeneralUtility::intExplode(',', $pidList)
            );
        }
        // Setting GPvars:
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        // The page id to operate from
        $pageUid = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '', $request);
        // Setting up the context sensitive menu:
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/new-content-element-wizard-button.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/page-wizard/new-page-wizard-button.js');
        // If a positive id is supplied, ask for the page record with permission information contained:
        if ($pageUid > 0) {
            $pageinfo = BackendUtility::readPageAccess($pageUid, $permsClause) ?: [];
        }
        // If a page-record was returned, the user had read-access to the page.
        if ($pageinfo['uid'] ?? false) {
            // Get record of parent page
            $pidInfo = BackendUtility::getRecord('pages', ($pageinfo['pid'] ?? 0)) ?? [];
            // Checking the permissions for the user with regard to the parent page: Can he create new pages, new
            // content record, new page after?
            if ($beUser->doesUserHaveAccess($pageinfo, Permission::PAGE_NEW)) {
                $newPagesInto = true;
            }
            if ($beUser->doesUserHaveAccess($pageinfo, Permission::CONTENT_EDIT)) {
                $newContentInto = true;
            }
            if (($beUser->isAdmin() || !empty($pidInfo)) && $beUser->doesUserHaveAccess($pidInfo, Permission::PAGE_NEW)) {
                $newPagesAfter = true;
            }
            $breadcrumbContext = new BreadcrumbContext(
                $this->recordFactory->createResolvedRecordFromDatabaseRow('pages', $pageinfo),
                []
            );
            $view->getDocHeaderComponent()->setBreadcrumbContext($breadcrumbContext);
        } elseif ($beUser->isAdmin()) {
            // Admins can do it all
            $newPagesInto = true;
            $newContentInto = true;
            $newPagesAfter = false;
        } else {
            // People with no permission can do nothing
            $newPagesInto = false;
            $newContentInto = false;
            $newPagesAfter = false;
        }
        $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        if ($pageinfo['uid'] ?? false) {
            $title = strip_tags(BackendUtility::getRecordTitle('pages', $pageinfo));
        }
        $view->setTitle($title);
        // Acquiring TSconfig for this module/current page:
        $web_list_modTSconfig = BackendUtility::getPagesTSconfig($pageinfo['uid'] ?? 0)['mod.']['web_list.'] ?? [];
        $allowedNewTables = GeneralUtility::trimExplode(',', $web_list_modTSconfig['allowedNewTables'] ?? '', true);
        $deniedNewTables = GeneralUtility::trimExplode(',', $web_list_modTSconfig['deniedNewTables'] ?? '', true);
        // Acquiring TSconfig for this module/parent page
        $web_list_modTSconfig_pid = BackendUtility::getPagesTSconfig($pageinfo['pid'] ?? 0)['mod.']['web_list.'] ?? [];
        $allowedNewTables_pid = GeneralUtility::trimExplode(',', $web_list_modTSconfig_pid['allowedNewTables'] ?? '', true);
        $deniedNewTables_pid = GeneralUtility::trimExplode(',', $web_list_modTSconfig_pid['deniedNewTables'] ?? '', true);
        if (!$this->isRecordCreationAllowedForTable('pages', $allowedNewTables, $deniedNewTables)) {
            $newPagesInto = false;
        }
        if (!$this->isRecordCreationAllowedForTable('pages', $allowedNewTables_pid, $deniedNewTables_pid)) {
            $newPagesAfter = false;
        }

        // If there was a page - or if the user is admin (admins has access to the root) we proceed, otherwise just output the header
        if (empty($pageinfo['uid']) && !$this->getBackendUserAuthentication()->isAdmin()) {
            return $view->renderResponse('NewRecord/NewRecord');
        }

        $lang = $this->getLanguageService();
        // Get TSconfig for current page
        $pageTS = BackendUtility::getPagesTSconfig($pageUid);
        // Finish initializing new pages options with TSconfig
        // Each new page option may be hidden by TSconfig
        $displayNewPagesIntoLink = $newPagesInto && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageInside']);
        $displayNewPagesAfterLink = $newPagesAfter && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageAfter']);
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
        $allowedTables = [];
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $isTablesAllowed = match ($table) {
                'pages' => $this->isRecordCreationAllowedForTable('pages', $allowedNewTables, $deniedNewTables),
                'tt_content' => false, // Skip, as inserting content elements is part of the page module
                default => $newContentInto && $this->isRecordCreationAllowedForTable($table, $allowedNewTables, $deniedNewTables) && $this->isTableAllowedOnPage($schema, $pageinfo, $pageUid)
            };

            if ($isTablesAllowed) {
                $allowedTables[] = $table;
            }
        }
        $groupedLinksOnTop = [];
        foreach ($allowedTables as $table) {
            $schema = $this->tcaSchemaFactory->get($table);
            $ctrlTitle = $schema->getTitle();

            if ($table === 'pages') {
                // New pages INSIDE this pages
                $newPageLinks = [];
                if ($displayNewPagesIntoLink && $this->isTableAllowedOnPage($schema, $pageinfo, $pageUid)) {
                    // Create link to new page inside
                    $newPageLinks['inside'] = [
                        'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL),
                        'label' => $lang->sL($ctrlTitle) . ' (' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.inside') . ')',
                        'wizardConfiguration' => ['positionData' => ['pageUid' => $pageUid, 'insertPosition' => 'inside']],
                    ];
                }
                // New pages AFTER this pages
                if ($displayNewPagesAfterLink && $this->isTableAllowedOnPage($schema, $pidInfo, $pageUid)) {
                    $newPageLinks['after'] = [
                        'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL),
                        'label' => $lang->sL($ctrlTitle) . ' (' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.after') . ')',
                        'wizardConfiguration' => ['positionData' => ['pageUid' => $pageUid, 'insertPosition' => 'after']],
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
                $groupName = $schema->getRawConfiguration()['groupName'] ?? '';
                if (!isset($iconFile[$groupName]) || $nameParts[0] === 'tx' || $nameParts[0] === 'tt') {
                    $groupName = $groupName ?: ($nameParts[1] ?? null);
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
                            $package = $this->packageManager->getPackage($_EXTKEY);
                            $groupTitle = $lang->sL('LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:extension.title');
                            // If no localisation available, read title from the Package MetaData
                            if (!$groupTitle) {
                                $groupTitle = $package->getPackageMetaData()->getTitle();
                            }
                            $extensionIcon = $package->getResources()->getPackageIcon();
                            if ($extensionIcon !== null) {
                                $iconResource = $this->resourceFactory->createPublicResource($extensionIcon);
                                $iconFile[$groupName] = '<img src="' . htmlspecialchars((string)$this->resourcePublisher->generateUri($iconResource, $request)) . '" width="16" height="16" alt="' . $groupTitle . '" />';
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
                $tRows[$groupName]['title'] = $tRows[$groupName]['title'] ?? $groupTitles[$groupName] ?? $nameParts[1] ?? $ctrlTitle;
                $tRows[$groupName]['icon'] = $tRows[$groupName]['icon'] ?? $iconFile[$groupName] ?? $iconFile['system'] ?? '';
                if ($schema->supportsSubSchema()
                    && !$schema->getSubSchemaTypeInformation()->isPointerToForeignFieldInForeignSchema()
                    && $this->hasRecordTypesForDirectCreation($schema)
                ) {
                    $tRows[$groupName]['items'][$table]['label'] = $lang->sL($ctrlTitle);
                    $tRows[$groupName]['items'][$table]['icon'] = $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL);
                    $tRows[$groupName]['items'][$table]['types'] = $this->getRecordTypesForDirectCreation($request, $schema, $pageUid, $returnUrl);
                } else {
                    $tRows[$groupName]['items'][$table] = [
                        'url' => $this->renderLink($request, $table, $pageUid, [], $returnUrl),
                        'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL)->render(),
                        'label' => $lang->sL($ctrlTitle),
                    ];
                }
            }
        }
        // User sort
        $newRecordSortList = isset($pageTS['mod.']['wizards.']['newRecord.']['order'])
            ? GeneralUtility::trimExplode(',', $pageTS['mod.']['wizards.']['newRecord.']['order'], true)
            : [];
        uksort($tRows, static function (string $a, string $b) use ($newRecordSortList, $tRows): int {
            if ($newRecordSortList !== []) {
                if (in_array($a, $newRecordSortList) && in_array($b, $newRecordSortList)) {
                    // Both are in the list, return relative to position in array
                    $sub = array_search($a, $newRecordSortList) - array_search($b, $newRecordSortList);
                    $ret = ($sub < 0 ? -1 : $sub == 0) ? 0 : 1;
                } elseif (in_array($a, $newRecordSortList)) {
                    // First element is in array, put to top
                    $ret = -1;
                } elseif (in_array($b, $newRecordSortList)) {
                    // Second element is in array, put first to bottom
                    $ret = 1;
                } else {
                    // No element is in array, return alphabetic order
                    $ret = strnatcasecmp($tRows[$a]['title'] ?? '', $tRows[$b]['title'] ?? '');
                }
                return $ret;
            }
            // Return alphabetic order
            return strnatcasecmp($tRows[$a]['title'] ?? '', $tRows[$b]['title'] ?? '');
        });
        $tRows = array_merge($groupedLinksOnTop, $tRows);

        $tRows = $this->eventDispatcher->dispatch(
            new ModifyNewRecordCreationLinksEvent($tRows, $pageTS, $pageUid, $request)
        )->groupedCreationLinks;

        $recordControls = $tRows;

        if (count($recordControls) === 1) {
            $items = current($recordControls)['items'] ?? [];
            if (count($items) === 1) {
                $item = current($items);
                // Items for tables with sub-types carry a 'types' sub-array instead of a 'url'
                // and must fall through to render the selection wizard.
                if (isset($item['url'])) {
                    return new RedirectResponse($item['url'], 301);
                }
            }
        }

        $view->assign('recordTypeGroups', $recordControls);

        // Setting up the buttons and markers for docheader (done after permissions are checked)
        // Back
        if ($returnUrl) {
            $view->addButtonToButtonBar($this->componentFactory->createBackButton($returnUrl), ButtonBar::BUTTON_POSITION_LEFT, 10);
        }
        if ($pageinfo['uid'] ?? false) {
            // View
            $previewUriBuilder = PreviewUriBuilder::create($pageinfo);
            if ($previewUriBuilder->isPreviewable()) {
                $view->addButtonToButtonBar(
                    $this->componentFactory->createViewButton($previewUriBuilder
                        ->withRootLine(BackendUtility::BEgetRootLine($pageinfo['uid']))
                        ->buildDispatcherDataAttributes() ?? []),
                    ButtonBar::BUTTON_POSITION_LEFT,
                    30
                );
            }
        }
        return $view->renderResponse('NewRecord/NewRecord');
    }

    /**
     * Links the string $code to a create-new form for a record in $table created on page $pid
     *
     * @param string $table Table name (in which to create new record)
     * @param int $pid PID value for the "&edit['.$table.']['.$pid.']=new" command (positive/negative)
     * @param array $additionalParams Additional params, such as "defVals" tp be added to the link
     * @param string $returnUrl Return URL, falls back to the current request URI when empty
     * @return string The link.
     */
    private function renderLink(ServerRequestInterface $request, string $table, int $pid, array $additionalParams, string $returnUrl): string
    {
        $params = [
            'edit' => [
                $table => [
                    $pid => 'new',
                ],
            ],
            'returnUrl' => $returnUrl ?: $request->getAttribute('normalizedParams')->getRequestUri(),
        ];

        if ($additionalParams) {
            $params = array_replace_recursive($params, $additionalParams);
        }

        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', $params);
    }

    /**
     * Returns TRUE if the tablename $checkTable is allowed to be created on the page with record $pid_row
     *
     * @param TcaSchema $schema Table schema
     * @param array $page Potential parent page
     * @param int $pageUid Current page id
     * @return bool Returns TRUE if the tablename $table is allowed to be created on the $page
     */
    private function isTableAllowedOnPage(TcaSchema $schema, array $page, int $pageUid): bool
    {
        $rootLevelCapability = $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel);

        $rootLevelConstraintMatches = ($rootLevelCapability->canExistOnRootLevel() && $pageUid === 0) || ($pageUid && $rootLevelCapability->canExistOnPages());
        if (empty($page)) {
            return $rootLevelConstraintMatches && $this->getBackendUserAuthentication()->isAdmin();
        }
        if (!$this->getBackendUserAuthentication()->workspaceCanCreateNewRecord($schema->getName())) {
            return false;
        }
        // Checking doktype
        $isAllowed = $this->pageDoktypeRegistry->isRecordTypeAllowedForDoktype($schema->getName(), $page['doktype']);
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
    private function isRecordCreationAllowedForTable(string $table, array $allowedNewTables, array $deniedNewTables): bool
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

        // No deny/allow tables are set:
        if (empty($allowedNewTables) && empty($deniedNewTables)) {
            return true;
        }

        return !in_array($table, $deniedNewTables) && (empty($allowedNewTables) || in_array($table, $allowedNewTables));
    }

    private function hasRecordTypesForDirectCreation(TcaSchema $schema): bool
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

    private function getRecordTypesForDirectCreation(ServerRequestInterface $request, TcaSchema $schema, int $pageUid, string $returnUrl): array
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
                'url' => $this->renderLink($request, $schema->getName(), $pageUid, [
                    'defVals' => [
                        $schema->getName() => [
                            $recordTypeField => $recordTypeName,
                        ],
                    ],
                ], $returnUrl),
                'icon' => $this->iconFactory->getIconForRecord($schema->getName(), [$recordTypeField => $recordTypeName], IconSize::SMALL),
                'label' => $lang->sL($this->schemaLabelResolver->getLabelForFieldValue(
                    $schema->getName(),
                    $recordTypeField,
                    $recordTypeName,
                    [],
                    BackendUtility::getPagesTSconfig($pageUid)['TCEFORM.'][$schema->getName() . '.'][$recordTypeField . '.'] ?? [],
                )) ?: $lang->sL($subSchema->getTitle())
                    ?: $recordTypeName,
            ];
        }
        return $recordTypes;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
