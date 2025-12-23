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

namespace TYPO3\CMS\Info\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Info\Controller\Event\ModifyInfoModuleContentEvent;

/**
 * Status -> Pagetree Overview
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
readonly class PageInformationController
{
    public function __construct(
        protected IconFactory $iconFactory,
        protected UriBuilder $uriBuilder,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected TcaSchemaFactory $tcaSchemaFactory,
        protected ComponentFactory $componentFactory,
        protected BackendLayoutView $backendLayoutView,
        protected ConnectionPool $connectionPool,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();
        $module = $request->getAttribute('module');
        $currentSite = $request->getAttribute('site');
        $moduleData = $request->getAttribute('moduleData');
        $pageId = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);

        $pageinfo = BackendUtility::readPageAccess($pageId, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $hasAccess = false;
        if (($pageId > 0 && $pageinfo !== []) || ($backendUser->isAdmin() && $pageId === 0)) {
            $hasAccess = true;
        }
        if ($pageId === 0 && $backendUser->isAdmin()) {
            $pageinfo = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
        }

        $siteLanguages = [
            $currentSite->getDefaultLanguage()->getLanguageId() => $currentSite->getDefaultLanguage(),
        ];
        foreach ($currentSite->getAvailableLanguages($this->getBackendUser(), false, $pageId) as $language) {
            $siteLanguages[$language->getLanguageId()] = $language;
        }

        $fieldConfiguration = $this->getFieldConfiguration($pageId);
        $allowedModuleOptions = $this->getModuleOptions($siteLanguages, $fieldConfiguration);
        if ($moduleData->cleanUp($allowedModuleOptions)) {
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        $selectedDepth = (int)($moduleData->get('depth') ?? 0);
        $selectedGroup = (string)($moduleData->get('pages') ?? '0'); // field or table list to render
        $selectedLanguage = (int)($moduleData->get('lang') ?? 0);

        $mainContent = $this->renderMainTable($pageId, $selectedDepth, $selectedLanguage, $siteLanguages, $request, $fieldConfiguration[$selectedGroup]['fields'] ?? []);

        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('hasAccess', $hasAccess);
        if ($hasAccess) {
            $view->setTitle($languageService->sL($module->getTitle()), $pageId !== 0 && isset($pageinfo['title']) ? $pageinfo['title'] : '');
            $view->getDocHeaderComponent()->setPageBreadcrumb($pageinfo);
            $view->makeDocHeaderModuleMenu(['id' => $pageId]);
            $view->getDocHeaderComponent()->setShortcutContext($module->getIdentifier(), sprintf('%s [%d]', $languageService->sL($module->getTitle()), $pageId), ['id' => $pageId]);
            $previewUriBuilder = PreviewUriBuilder::create($pageinfo);
            if ($previewUriBuilder->isPreviewable()) {
                // View page
                $previewDataAttributes = $previewUriBuilder
                    ->withRootLine(BackendUtility::BEgetRootLine($pageinfo['uid']))
                    ->buildDispatcherDataAttributes();
                $viewButton = $this->componentFactory->createLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes ?? [])
                    ->setDisabled(!$previewDataAttributes)
                    ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-page', IconSize::SMALL))
                    ->setShowLabelText(true);
                $view->addButtonToButtonBar($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            }
        }
        $event = $this->eventDispatcher->dispatch(new ModifyInfoModuleContentEvent($hasAccess, $request, $module, $view));
        if ($hasAccess) {
            $view->assignMultiple([
                'pageUid' => $pageId,
                'content' => $mainContent,
                'depthDropdownOptions' => $allowedModuleOptions['depth'],
                'depthDropdownCurrentValue' => $selectedDepth,
                'pagesDropdownOptions' => $allowedModuleOptions['pages'],
                'pagesDropdownCurrentValue' => $selectedGroup,
                'langDropdownOptions' => $allowedModuleOptions['lang'],
                'langDropdownCurrentValue' => $selectedLanguage,
                'headerContent' => $event->getHeaderContent(),
                'footerContent' => $event->getFooterContent(),
            ]);
        }
        return $view->renderResponse('PageInformation');
    }

    protected function getModuleOptions(array $siteLanguages, array $fieldConfiguration): array
    {
        $languageService = $this->getLanguageService();
        $menu = [
            'pages' => [],
            'depth' => [
                0 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
            'lang' => [],
        ];
        foreach ($fieldConfiguration as $key => $item) {
            $menu['pages'][$key] = $item['label'];
        }
        foreach ($siteLanguages as $language) {
            $menu['lang'][$language->getLanguageId()] = $language->getTitle();
        }
        return $menu;
    }

    /**
     * Generate configuration for field and table selection from TSConfig.
     */
    protected function getFieldConfiguration(int $pageId): array
    {
        $languageService = $this->getLanguageService();
        $fieldConfiguration = [];
        $modTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_info.']['fieldDefinitions.'] ?? [];
        $allowedTables = $this->getAllowedTableNames();
        foreach ($modTSconfig as $key => $item) {
            $fieldList = str_replace('###ALL_TABLES###', implode(',', $allowedTables), $item['fields']);
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            $key = trim($key, '.');
            $fieldConfiguration[$key] = [
                'label' => $item['label'] ? $languageService->sL($item['label']) : $key,
                'fields' => $fields,
            ];
        }
        return $fieldConfiguration;
    }

    /**
     * A list of table names allowed to be listed when ###ALL_TABLES### is used in TSConfig.
     * Some tables like 'pages' are blinded by default, all remaining ones are user access checked.
     */
    protected function getAllowedTableNames(): array
    {
        $hideTables = ['pages', 'sys_filemounts', 'be_users', 'be_groups']; // Never show these tables
        $allowedTables = [];
        foreach ($this->tcaSchemaFactory->all() as $schemaName => $schema) {
            if (in_array($schemaName, $hideTables, true)
                || $schema->hasCapability(TcaSchemaCapability::HideInUi)
                || !$this->getBackendUser()->check('tables_select', $schemaName)
            ) {
                continue;
            }
            $allowedTables[] = 'table_' . $schemaName;
        }
        return $allowedTables;
    }

    /**
     * Renders records from the pages table from page id
     *
     * @return string HTML for the listing
     */
    protected function renderMainTable(int $id, int $depth, int $language, array $siteLanguages, ServerRequestInterface $request, array $fieldArray): string
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        $out = '';
        $pagesSchema = $this->tcaSchemaFactory->get('pages');
        $languageFieldName = $pagesSchema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        $translationOriginFieldName = $pagesSchema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
            )
            ->executeQuery()
            ->fetchAssociative();
        BackendUtility::workspaceOL('pages', $row);
        if ($language > 0) {
            $localizedParentPageRecord = BackendUtility::getRecordLocalization('pages', $row['uid'], $language);
            if (!empty($localizedParentPageRecord)) {
                $row = $localizedParentPageRecord[0];
                $row['uid'] = $row[$translationOriginFieldName];
            }
        }
        if (!is_array($row)) {
            return '';
        }

        $editUids = [];
        // Getting children
        $theRows = $this->getPageRecordsRecursive($row['uid'], $depth, $language);
        // Get tree root page
        $treeRootPage = $this->getTreeRootPage($row['uid'], $row[$languageFieldName]);
        if ($backendUser->doesUserHaveAccess($treeRootPage, Permission::PAGE_EDIT) && $treeRootPage['uid'] > 0) {
            $editUids[] = $treeRootPage['uid'];
        }
        $out .= $this->pages_drawItem($treeRootPage, $request, $siteLanguages, $fieldArray);
        // Traverse all pages selected:
        foreach ($theRows as $sRow) {
            if ($backendUser->doesUserHaveAccess($sRow, Permission::PAGE_EDIT)) {
                $editUids[] = $sRow['uid'];
            }
            $out .= $this->pages_drawItem($sRow, $request, $siteLanguages, $fieldArray);
        }
        // Header line is drawn
        $headerCells = [];
        $editIdList = implode(',', $editUids);
        // Traverse fields (as set above) in order to create header values:
        foreach ($fieldArray as $field) {
            $editButton = '';
            if (
                $editIdList
                && $pagesSchema->hasField($field)
                && $backendUser->check('tables_modify', 'pages')
                && $backendUser->check('non_exclude_fields', 'pages:' . $field)
            ) {
                $iTitle = sprintf(
                    $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editThisColumn'),
                    rtrim(trim($languageService->sL($pagesSchema->getField($field)->getLabel())), ':')
                );
                $urlParameters = [
                    'edit' => [
                        'pages' => [
                            $editIdList => 'edit',
                        ],
                    ],
                    'columnsOnly' => [
                        'pages' => [$field],
                    ],
                    'module' => 'web_info_overview',
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $editButton = '<a class="btn btn-default" href="' . htmlspecialchars($url)
                    . '" title="' . htmlspecialchars($iTitle) . '">'
                    . $this->iconFactory->getIcon('actions-document-open', IconSize::SMALL)->render() . '</a>';
            }
            switch ($field) {
                case 'title':
                    $headerCells[$field] = $editButton . '&nbsp;<strong>'
                        . $languageService->sL($pagesSchema->getField($field)->getLabel())
                        . '</strong>';
                    break;
                case 'uid':
                    $headerCells[$field] = '';
                    break;
                case 'actual_backend_layout':
                    $headerCells[$field] = htmlspecialchars($languageService->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:actual_backend_layout'));
                    break;
                default:
                    if (str_starts_with($field, 'table_')) {
                        $f2 = substr($field, 6);
                        if ($this->tcaSchemaFactory->has($f2)) {
                            $schema = $this->tcaSchemaFactory->get($f2);
                            $headerCells[$field] = '&nbsp;' .
                                '<span title="' .
                                htmlspecialchars($schema->getTitle($languageService->sL(...)) ?: $f2) .
                                '">' .
                                $this->iconFactory->getIconForRecord($f2, [], IconSize::SMALL)->render() .
                                '</span>';
                        }
                    } else {
                        if ($pagesSchema->hasField($field)) {
                            $headerCells[$field] = $editButton . '&nbsp;<strong>'
                                . htmlspecialchars($languageService->sL($pagesSchema->getField($field)->getLabel()))
                                . '</strong>';
                        } else {
                            // Invalid field configured in `mod.web_info.fieldDefinitions.*`,
                            // using field name as header label.
                            $headerCells[$field] = $editButton . '&nbsp;<strong>'
                                . htmlspecialchars($field)
                                . '</strong>';
                        }
                    }
            }
        }
        return '
            <div class="table-fit">
                <table class="table table-striped table-hover" id="PageInformationControllerTable">
                    <thead>
                        ' . $this->addElement($headerCells, $fieldArray) . '
                    </thead>
                    <tbody>
                        ' . $out . '
                    </tbody>
                </table>
            </div>';
    }

    /**
     * Get tree root page
     *
     * @param int $pid Starting page
     * @param int $language Selected site language
     */
    protected function getTreeRootPage(int $pid, int $language): array
    {
        $backendUser = $this->getBackendUser();
        $pagesSchema = $this->tcaSchemaFactory->get('pages');
        $languageFieldName = $pagesSchema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        $translationOriginFieldName = $pagesSchema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $backendUser->workspace));

        if ($language > 0) {
            return $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq($translationOriginFieldName, $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq($languageFieldName, $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)),
                    $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
        }

        return $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * Adds pages-rows to an array, selecting recursively in the page tree.
     *
     * @param int $pid Starting page id to select from
     * @param string $iconPrefix Prefix for icon code.
     * @param int $depth Depth (decreasing)
     * @param array $rows Array which will accumulate page rows
     * @return array $rows with added rows.
     */
    protected function getPageRecordsRecursive(int $pid, int $depth, int $language, string $iconPrefix = '', array $rows = []): array
    {
        $backendUser = $this->getBackendUser();
        $pagesSchema = $this->tcaSchemaFactory->get('pages');
        $languageFieldName = $pagesSchema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();

        $depth--;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $backendUser->workspace));

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq($languageFieldName, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
            );

        if ($pagesSchema->hasCapability(TcaSchemaCapability::SortByField)) {
            $queryBuilder->orderBy($pagesSchema->getCapability(TcaSchemaCapability::SortByField)->getFieldName());
        }

        if ($depth >= 0) {
            $countQueryBuilder = clone $queryBuilder;
            $countQueryBuilder->resetOrderBy()->count('uid');
            $rowCount = $countQueryBuilder->executeQuery()->fetchOne();
            $result = $queryBuilder->executeQuery();
            $count = 0;
            while ($row = $result->fetchAssociative()) {
                BackendUtility::workspaceOL('pages', $row);
                $uid = $row['uid'];
                if (is_array($row)) {
                    if ($language > 0) {
                        $localizedParentPageRecord = BackendUtility::getRecordLocalization('pages', $row['uid'], $language);
                        if (empty($localizedParentPageRecord)) {
                            continue;
                        }
                        $row = $localizedParentPageRecord[0];
                    }
                    $count++;
                    $row['treeIcons'] = $iconPrefix
                        . '<span class="treeline-icon treeline-icon-join'
                        . ($rowCount === $count ? 'bottom' : '')
                        . '"></span>';
                    $rows[] = $row;
                    // Get the branch
                    $spaceOutIcons = '<span class="treeline-icon treeline-icon-'
                        . ($rowCount === $count ? 'clear' : 'line')
                        . '"></span>';
                    $rows = $this->getPageRecordsRecursive(
                        $uid,
                        $row['php_tree_stop'] ? 0 : $depth,
                        $language,
                        $iconPrefix . $spaceOutIcons,
                        $rows
                    );
                }
            }
        }

        return $rows;
    }

    /**
     * Adds a list item for the pages-rendering
     */
    protected function pages_drawItem(array $row, ServerRequestInterface $request, array $siteLanguages, array $fieldArray): string
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        $pagesSchema = $this->tcaSchemaFactory->get('pages');
        $languageFieldName = $pagesSchema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        $backendLayouts = $this->getBackendLayouts($row, 'backend_layout');
        $backendLayoutsNextLevel = $this->getBackendLayouts($row, 'backend_layout_next_level');
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $theIcon = $this->getIcon($row);
        // Preparing and getting the data-array
        $theData = [];
        foreach ($fieldArray as $field) {
            switch ($field) {
                case 'title':
                    $showPageId = !empty($userTsConfig['options.']['pageTree.']['showPageIdWithTitle']);
                    $pTitle = htmlspecialchars((string)BackendUtility::getProcessedValue('pages', $field, $row[$field], 20, false, false, 0, true, 0, $row));
                    $theData[$field] = '<div class="treeline-container">'
                        . ($row['treeIcons'] ?? '')
                        . $theIcon
                        . ($showPageId ? '[' . $row['uid'] . '] ' : '')
                        . $pTitle
                        . '</div>';
                    break;
                case $languageFieldName:
                    if (count($siteLanguages) === 1) {
                        $theData[$field] = '';
                        break;
                    }
                    $siteLanguage = $siteLanguages[$row[$languageFieldName]] ?? null;
                    if (!$siteLanguage) {
                        $theData[$field] = '';
                        break;
                    }
                    $theData[$field] = $this->iconFactory->getIcon($siteLanguage->getFlagIdentifier(), IconSize::SMALL)->setTitle($siteLanguage->getTitle())->render()
                        . ' ' . $siteLanguage->getTitle();
                    break;
                case 'php_tree_stop':
                    // Intended fall through
                case 'TSconfig':
                    $theData[$field] = $row[$field] ? '<strong>x</strong>' : '&nbsp;';
                    break;
                case 'actual_backend_layout':
                    $backendLayout = $this->backendLayoutView->getBackendLayoutForPage((int)$row['uid']);
                    $theData[$field] = htmlspecialchars($languageService->sL($backendLayout->getTitle()));
                    break;
                case 'backend_layout':
                    $layoutValue = $backendLayouts[$row[$field]] ?? null;
                    $theData[$field] = $this->resolveBackendLayoutValue($layoutValue, $field, $row);
                    break;
                case 'backend_layout_next_level':
                    $layoutValue = $backendLayoutsNextLevel[$row[$field]] ?? null;
                    $theData[$field] = $this->resolveBackendLayoutValue($layoutValue, $field, $row);
                    break;
                case 'uid':
                    $uid = 0;
                    $editButton = '';
                    $viewButton = '';
                    if ($backendUser->doesUserHaveAccess($row, 2) && $row['uid'] > 0) {
                        $uid = (int)$row['uid'];
                        $urlParameters = [
                            'edit' => [
                                'pages' => [
                                    $row['uid'] => 'edit',
                                ],
                            ],
                            'module' => 'web_info_overview',
                            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                        ];
                        $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                        $previewDataAttributes = PreviewUriBuilder::create($row)
                            ->withRootLine(BackendUtility::BEgetRootLine($row['uid']))
                            ->serializeDispatcherAttributes();
                        $viewButton =
                            '<button ' . ($previewDataAttributes ?? 'disabled="true"') . ' class="btn btn-default" title="' .
                            htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' .
                            $this->iconFactory->getIcon('actions-view-page', IconSize::SMALL)->render() .
                            '</button>';
                        if ($backendUser->check('tables_modify', 'pages')) {
                            $editButton =
                                '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' .
                                htmlspecialchars($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editPageProperties')) . '">' .
                                $this->iconFactory->getIcon('actions-page-open', IconSize::SMALL)->render() .
                                '</a>';
                        }
                    }
                    // Since the uid is overwritten with the edit button markup, we need to store
                    // the actual uid to be able to add it as data attribute to the table data cell.
                    // This also makes the distinction between record rows and the header line simpler.
                    $theData['_UID_'] = $uid;
                    $theData[$field] = '<div class="btn-group btn-group-sm" role="group">' . $viewButton . $editButton . '</div>';
                    break;
                case 'shortcut':
                case 'shortcut_mode':
                    if ((int)$row['doktype'] === PageRepository::DOKTYPE_SHORTCUT) {
                        $theData[$field] = htmlspecialchars((string)BackendUtility::getProcessedValue('pages', $field, $row[$field], 0, false, false, 0, true, 0, $row));
                    }
                    break;
                default:
                    if (str_starts_with($field, 'table_')) {
                        $f2 = substr($field, 6);
                        if ($this->tcaSchemaFactory->has($f2)) {
                            $c = $this->numberOfRecords($f2, (int)$row['uid']);
                            $theData[$field] = ($c ?: '');
                        }
                    } else {
                        $theData[$field] = htmlspecialchars((string)BackendUtility::getProcessedValue('pages', $field, $row[$field], 0, false, false, 0, true, 0, $row));
                    }
            }
        }
        return $this->addElement($theData, $fieldArray);
    }

    /**
     * Creates the icon image tag for the page and wraps it in a link which will trigger the click menu.
     */
    protected function getIcon(array $row): string
    {
        $backendUser = $this->getBackendUser();
        $icon = '<span title="' . BackendUtility::getRecordIconAltText($row, 'pages') . '">' . $this->iconFactory->getIconForRecord('pages', $row, IconSize::SMALL)->render() . '</span>';
        // The icon with link
        if ($backendUser->checkRecordEditAccess('pages', $row)->isAllowed) {
            $icon = BackendUtility::wrapClickMenuOnIcon($icon, 'pages', $row['uid']);
        }
        return $icon;
    }

    /**
     * Counts and returns the number of records on the page with $pid
     */
    protected function numberOfRecords(string $table, int $pid): int
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            return 0;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        return (int)$queryBuilder->count('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     *
     * @param array $data Record with field values, NOT htmlspecialchar'ed
     * @return string HTML content for the table row
     */
    protected function addElement(array $data, array $fieldArray): string
    {
        // Start up:
        $attributes = '';
        $rowTag = 'th';
        if (isset($data['_UID_'])) {
            $l10nParent = isset($data['_l10nparent_']) ? (int)$data['_l10nparent_'] : 0;
            $attributes = ' data-uid="' . $data['_UID_'] . '" data-l10nparent="' . $l10nParent . '"';
            $rowTag = 'td';
        }
        $out = '<tr' . $attributes . '>';
        // Init rendering.
        $colsp = '';
        $lastKey = '';
        $c = 0;
        // __label is used as the label key to circumvent problems with uid used as label (see #67756)
        // as it was introduced later on, check if it really exists before using it
        if (array_key_exists('__label', $data)) {
            $fieldArray[0] = '__label';
        }
        // Traverse field array which contains the data to present:
        foreach ($fieldArray as $vKey) {
            if (isset($data[$vKey])) {
                $cssClass = '';
                if ($lastKey === 'title') {
                    $cssClass = 'col-title-flexible';
                }
                if ($lastKey) {
                    $out .= '<' . $rowTag . ' class="' . $cssClass . ' nowrap"' . $colsp . '>' . $data[$lastKey] . '</' . $rowTag . '>';
                }
                $lastKey = $vKey;
                $c = 1;
            } else {
                if (!$lastKey) {
                    $lastKey = $vKey;
                }
                $c++;
            }
            if ($c > 1) {
                $colsp = ' colspan="' . $c . '"';
            } else {
                $colsp = '';
            }
        }
        if ($lastKey) {
            $cssClass = '';
            if ($lastKey === 'title') {
                $cssClass = 'col-title-flexible';
            }
            $out .= '<' . $rowTag . ' class="' . $cssClass . ' nowrap"' . $colsp . '>' . $data[$lastKey] . '</' . $rowTag . '>';
        }
        $out .= '</tr>';
        return $out;
    }

    protected function getBackendLayouts(array $row, string $field): array
    {
        $configuration = ['row' => $row, 'table' => 'pages', 'field' => $field, 'items' => []];
        // Below we call the itemsProcFunc to retrieve properly resolved backend layout items,
        // including the translated labels and the correct field values (backend layout identifiers).
        $this->backendLayoutView->addBackendLayoutItems($configuration);
        $backendLayouts = [];
        foreach ($configuration['items'] ?? [] as $backendLayout) {
            if (($backendLayout['label'] ?? false) && ($backendLayout['value'] ?? false)) {
                $backendLayouts[$backendLayout['value']] = $backendLayout['label'];
            }
        }
        return $backendLayouts;
    }

    protected function resolveBackendLayoutValue(?string $layoutValue, string $field, array $row): string
    {
        $languageService = $this->getLanguageService();
        if ($layoutValue !== null) {
            // Directly return the resolved layout value from BackendLayoutView
            return htmlspecialchars($layoutValue);
        }
        $layoutValue = htmlspecialchars((string)BackendUtility::getProcessedValue('pages', $field, $row[$field], 0, false, false, 0, true, 0, $row));
        if ($layoutValue !== '') {
            // If getProcessedValue() returns a non-empty string, the database field
            // is filled with an invalid value (the backend layout does no longer exist).
            return sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $layoutValue);
        }
        return '';
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
