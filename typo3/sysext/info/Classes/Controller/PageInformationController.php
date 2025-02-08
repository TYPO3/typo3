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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying page information (records, page record properties) in Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
class PageInformationController extends InfoModuleController
{
    protected ?BackendLayoutView $backendLayoutView = null;
    protected array $fieldConfiguration = [];
    protected array $fieldArray = [];

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     */
    protected array $addElement_tdCssClass = [];

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $backendUser = $this->getBackendUser();
        $moduleData = $request->getAttribute('moduleData');
        $allowedModuleOptions = $this->getAllowedModuleOptions();
        if ($moduleData->cleanUp($allowedModuleOptions)) {
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        $depth = (int)($moduleData->get('depth') ?? 0);
        $pages = (string)($moduleData->get('pages') ?? '0');

        if (isset($this->fieldConfiguration[$pages])) {
            $this->fieldArray = $this->fieldConfiguration[$pages]['fields'];
        }

        if ($this->id) {
            $this->view->assignMultiple([
                'pageUid' => $this->id,
                'depthDropdownOptions' => $allowedModuleOptions['depth'],
                'depthDropdownCurrentValue' => $depth,
                'pagesDropdownOptions' => $allowedModuleOptions['pages'],
                'pagesDropdownCurrentValue' => $pages,
                'content' => $this->getTable_pages($this->id, $depth, $request),
            ]);
        }
        return $this->view->renderResponse('PageInformation');
    }

    protected function getAllowedModuleOptions(): array
    {
        $menu = [
            'pages' => [],
            'depth' => [
                0 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
        ];

        $this->fillFieldConfiguration($this->id);
        foreach ($this->fieldConfiguration as $key => $item) {
            $menu['pages'][$key] = $item['label'];
        }
        return $menu;
    }

    /**
     * Function, which returns all tables to
     * which the user has access. Also a set of standard tables (pages, sys_filemounts, etc...)
     * are filtered out. So what is left is basically all tables which makes sense to list content from.
     */
    protected function cleanTableNames(): string
    {
        $standardTables = [
            'pages',
            'sys_filemounts',
            'be_users',
            'be_groups',
        ];
        $allowedTableNames = [];
        // Traverse table names and set them in allowedTableNames array IF they can be read-accessed by the user.
        foreach ($this->tcaSchemaFactory->all() as $schemaName => $schema) {
            // Unset common names
            if (in_array($schemaName, $standardTables, true)) {
                continue;
            }
            if ($schema->getRawConfiguration()['hideTable'] ?? false) {
                continue;
            }
            if (!$this->getBackendUser()->check('tables_select', $schemaName)) {
                continue;
            }
            $allowedTableNames[$schemaName] = 'table_' . $schemaName;
        }
        return implode(',', $allowedTableNames);
    }

    /**
     * Generate configuration for field selection
     */
    protected function fillFieldConfiguration(int $pageId): void
    {
        $modTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_info.']['fieldDefinitions.'] ?? [];
        foreach ($modTSconfig as $key => $item) {
            $fieldList = str_replace('###ALL_TABLES###', $this->cleanTableNames(), $item['fields']);
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            $key = trim($key, '.');
            $this->fieldConfiguration[$key] = [
                'label' => $item['label'] ? $this->getLanguageService()->sL($item['label']) : $key,
                'fields' => $fields,
            ];
        }
    }

    /**
     * Renders records from the pages table from page id
     *
     * @return string HTML for the listing
     * @throws RouteNotFoundException
     */
    protected function getTable_pages(int $id, int $depth, ServerRequestInterface $request): string
    {
        $out = '';
        $pagesSchema = $this->tcaSchemaFactory->get('pages');
        $lang = $this->getLanguageService();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
            )
            ->executeQuery()
            ->fetchAssociative();
        BackendUtility::workspaceOL('pages', $row);
        // If there was found a page:
        if (is_array($row)) {
            // Creating elements
            $editUids = [];
            // Getting children
            $theRows = $this->getPageRecordsRecursive($row['uid'], $depth);
            if ($this->getBackendUser()->doesUserHaveAccess($row, Permission::PAGE_EDIT) && $row['uid'] > 0) {
                $editUids[] = $row['uid'];
            }
            $out .= $this->pages_drawItem($row, $request);
            // Traverse all pages selected:
            foreach ($theRows as $sRow) {
                if ($this->getBackendUser()->doesUserHaveAccess($sRow, Permission::PAGE_EDIT)) {
                    $editUids[] = $sRow['uid'];
                }
                $out .= $this->pages_drawItem($sRow, $request);
            }
            // Header line is drawn
            $headerCells = [];
            $editIdList = implode(',', $editUids);
            // Traverse fields (as set above) in order to create header values:
            foreach ($this->fieldArray as $field) {
                $editButton = '';
                if (
                    $editIdList
                    && $pagesSchema->hasField($field)
                    && $this->getBackendUser()->check('tables_modify', 'pages')
                    && $this->getBackendUser()->check('non_exclude_fields', 'pages:' . $field)
                ) {
                    $iTitle = sprintf(
                        $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editThisColumn'),
                        rtrim(trim($lang->sL(BackendUtility::getItemLabel('pages', $field))), ':')
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
                            . $lang->sL($pagesSchema->getField($field)->getLabel())
                            . '</strong>';
                        break;
                    case 'uid':
                        $headerCells[$field] = '';
                        break;
                    case 'actual_backend_layout':
                        $headerCells[$field] = htmlspecialchars($lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:actual_backend_layout'));
                        break;
                    default:
                        if (str_starts_with($field, 'table_')) {
                            $f2 = substr($field, 6);
                            if ($this->tcaSchemaFactory->has($f2)) {
                                $schema = $this->tcaSchemaFactory->get($f2);
                                $headerCells[$field] = '&nbsp;' .
                                    '<span title="' .
                                    htmlspecialchars($lang->sL($schema->getRawConfiguration()['title'] ?? $f2)) .
                                    '">' .
                                    $this->iconFactory->getIconForRecord($f2, [], IconSize::SMALL)->render() .
                                    '</span>';
                            }
                        } else {
                            $headerCells[$field] = $editButton . '&nbsp;<strong>'
                                . htmlspecialchars($lang->sL($pagesSchema->getField($field)->getLabel()))
                                . '</strong>';
                        }
                }
            }
            $out = '
                <div class="table-fit">
                    <table class="table table-striped table-hover" id="PageInformationControllerTable">
                        <thead>
                            ' . $this->addElement($headerCells) . '
                        </thead>
                        <tbody>
                            ' . $out . '
                        </tbody>
                    </table>
                </div>';
        }

        return $out;
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
    protected function getPageRecordsRecursive(int $pid, int $depth, string $iconPrefix = '', array $rows = []): array
    {
        $depth--;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
            );

        $pagesSchema = $this->tcaSchemaFactory->get('pages');
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
                if (is_array($row)) {
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
                        $row['uid'],
                        $row['php_tree_stop'] ? 0 : $depth,
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
    protected function pages_drawItem(array $row, ServerRequestInterface $request): string
    {
        $this->backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
        $backendLayouts = $this->getBackendLayouts($row, 'backend_layout');
        $backendLayoutsNextLevel = $this->getBackendLayouts($row, 'backend_layout_next_level');
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $theIcon = $this->getIcon($row);
        // Preparing and getting the data-array
        $theData = [];
        foreach ($this->fieldArray as $field) {
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
                case 'php_tree_stop':
                    // Intended fall through
                case 'TSconfig':
                    $theData[$field] = $row[$field] ? '<strong>x</strong>' : '&nbsp;';
                    break;
                case 'actual_backend_layout':
                    $backendLayout = $this->backendLayoutView->getBackendLayoutForPage((int)$row['uid']);
                    $theData[$field] = $backendLayout !== null
                        ? htmlspecialchars($this->getLanguageService()->sL($backendLayout->getTitle()))
                        : '';
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
                    if ($this->getBackendUser()->doesUserHaveAccess($row, 2) && $row['uid'] > 0) {
                        $uid = (int)$row['uid'];
                        $urlParameters = [
                            'edit' => [
                                'pages' => [
                                    $row['uid'] => 'edit',
                                ],
                            ],
                            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                        ];
                        $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                        $attributes = PreviewUriBuilder::create((int)$row['uid'])
                            ->withRootLine(BackendUtility::BEgetRootLine($row['uid']))
                            ->serializeDispatcherAttributes();
                        $editButton =
                            '<button ' . ($attributes ?? 'disabled="true"') . ' class="btn btn-default" title="' .
                            htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' .
                            $this->iconFactory->getIcon('actions-view-page', IconSize::SMALL)->render() .
                            '</button>';

                        if ($this->getBackendUser()->check('tables_modify', 'pages')) {
                            $editButton .=
                                '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' .
                                htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:editDefaultLanguagePage')) . '">' .
                                $this->iconFactory->getIcon('actions-page-open', IconSize::SMALL)->render() .
                                '</a>';
                        }
                    }
                    // Since the uid is overwritten with the edit button markup we need to store
                    // the actual uid to be able to add it as data attribute to the table data cell.
                    // This also makes distinction between record rows and the header line simpler.
                    $theData['_UID_'] = $uid;
                    $theData[$field] = '<div class="btn-group btn-group-sm" role="group">' . $editButton . '</div>';
                    break;
                case 'shortcut':
                case 'shortcut_mode':
                    if ((int)$row['doktype'] === PageRepository::DOKTYPE_SHORTCUT) {
                        $theData[$field] = $this->getPagesTableFieldValue($field, $row);
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
                        $theData[$field] = $this->getPagesTableFieldValue($field, $row);
                    }
            }
        }
        $this->addElement_tdCssClass['title'] = 'col-title-flexible';
        return $this->addElement($theData);
    }

    /**
     * Creates the icon image tag for the page and wraps it in a link which will trigger the click menu.
     */
    protected function getIcon(array $row): string
    {
        // Initialization
        $icon = '<span title="' . BackendUtility::getRecordIconAltText($row, 'pages') . '">' . $this->iconFactory->getIconForRecord('pages', $row, IconSize::SMALL)->render() . '</span>';
        // The icon with link
        if ($this->getBackendUser()->recordEditAccessInternals('pages', $row)) {
            $icon = BackendUtility::wrapClickMenuOnIcon($icon, 'pages', $row['uid']);
        }
        return $icon;
    }
    /**
     * Returns the HTML code for rendering a field in the pages table.
     * The row value is processed to a human readable form and the result is parsed through htmlspecialchars().
     *
     * @param string $field The name of the field of which the value should be rendered.
     * @param array $row The pages table row as an associative array.
     * @return string The rendered table field value.
     */
    protected function getPagesTableFieldValue(string $field, array $row): string
    {
        return htmlspecialchars((string)BackendUtility::getProcessedValue('pages', $field, $row[$field], 0, false, false, 0, true, 0, $row));
    }

    /**
     * Counts and returns the number of records on the page with $pid
     */
    protected function numberOfRecords(string $table, int $pid): int
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        return (int)$queryBuilder->count('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
     *
     * @param array $data Is the data array, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
     * @return string HTML content for the table row
     */
    protected function addElement(array $data): string
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
        $fields = $this->fieldArray;
        if (array_key_exists('__label', $data)) {
            $fields[0] = '__label';
        }
        // Traverse field array which contains the data to present:
        foreach ($fields as $vKey) {
            if (isset($data[$vKey])) {
                if ($lastKey) {
                    $cssClass = $this->addElement_tdCssClass[$lastKey] ?? '';
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
            $cssClass = $this->addElement_tdCssClass[$lastKey] ?? '';
            $out .= '
				<' . $rowTag . ' class="' . $cssClass . ' nowrap"' . $colsp . '>' . $data[$lastKey] . '</' . $rowTag . '>';
        }
        $out .= '</tr>';
        return $out;
    }

    protected function getBackendLayouts(array $row, string $field): array
    {
        if ($this->backendLayoutView === null) {
            $this->backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
        }
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
        if ($layoutValue !== null) {
            // Directly return the resolved layout value from BackendLayoutView
            return htmlspecialchars($layoutValue);
        }

        // Fetch field value from database (this is already htmlspecialchar'ed)
        $layoutValue = $this->getPagesTableFieldValue($field, $row);
        if ($layoutValue !== '') {
            // In case getPagesTableFieldValue returns a non-empty string, the database field
            // is filled with an invalid value (the backend layout does no longer exist).
            return sprintf(
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
                $this->getPagesTableFieldValue($field, $row)
            );
        }
        return '';
    }
}
