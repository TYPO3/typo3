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

namespace TYPO3\CMS\Backend\Tree\View;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractContentPagePositionMap
{
    /**
     * Can be set to the sys_language uid to select content elements for.
     */
    public int $cur_sys_language = 0;

    protected BackendLayoutView $backendLayoutView;

    public function __construct(BackendLayoutView $backendLayoutView)
    {
        $this->backendLayoutView = $backendLayoutView;
    }

    /**
     * Creates a linked position icon
     *
     * @param array|null $row The record row. If this is an array the link will cause an insert after this
     *                        content element, otherwise the link will insert at the first position in the column.
     * @param int $colPos Column position value.
     * @param int $pid PID value.
     * @return string HTML
     */
    abstract protected function insertPositionIcon(?array $row, int $colPos, int $pid): string;

    /**
     * Create content element header (includes record type (CType) icon, content element title, etc.)
     *
     * @param array $row The element row
     * @return string HTML
     */
    abstract protected function getRecordHeader(array $row): string;

    /**
     * Creates HTML for inserting/moving content elements.
     *
     * @param int $pid page id onto which to insert content element.
     * @return string HTML
     */
    public function printContentElementColumns(int $pid): string
    {
        $lines = [];
        $columnsConfiguration = $this->getColumnsConfiguration($pid);
        foreach ($columnsConfiguration as $columnConfiguration) {
            if ($columnConfiguration['isRestricted']) {
                // Do not fetch records of restricted columns
                continue;
            }
            $colPos = $columnConfiguration['colPos'];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $queryBuilder
                ->getRestrictions()
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace))
                ->removeByType(HiddenRestriction::class)
                ->removeByType(StartTimeRestriction::class)
                ->removeByType(EndTimeRestriction::class);
            $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter($colPos, \PDO::PARAM_INT))
                )
                ->orderBy('sorting');

            if ((string)$this->cur_sys_language !== '') {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($this->cur_sys_language, \PDO::PARAM_INT)
                    )
                );
            }

            $res = $queryBuilder->executeQuery();
            $lines[$colPos] = [
                $this->insertPositionIcon(null, $colPos, $pid),
            ];

            while ($row = $res->fetchAssociative()) {
                BackendUtility::workspaceOL('tt_content', $row);
                if (is_array($row)) {
                    $lines[$colPos][] = $this->getRecordHeader($row);
                    $lines[$colPos][] = $this->insertPositionIcon($row, $colPos, $pid);
                }
            }
        }
        return $this->printRecordMap($lines, $columnsConfiguration, $pid);
    }

    /**
     * Creates the table with the content columns
     *
     * @param array $lines Array with arrays of lines for each column
     * @param array $tcaColumnsConfiguration Column configuration array
     * @param int $pid The id of the page
     * @return string HTML
     */
    protected function printRecordMap(array $lines, array $tcaColumnsConfiguration, int $pid): string
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $hideRestrictedColumns = (bool)(BackendUtility::getPagesTSconfig($pid)['mod.']['web_layout.']['hideRestrictedCols'] ?? false);
        $backendLayout = $this->backendLayoutView->getSelectedBackendLayout($pid);

        if (isset($backendLayout['__config']['backend_layout.'])) {
            // Build position map based on the fetched backend layout
            $colCount = (int)($backendLayout['__config']['backend_layout.']['colCount'] ?? 0);
            $rowCount = (int)($backendLayout['__config']['backend_layout.']['rowCount'] ?? 0);

            // Cycle through rows
            $tableRows = [];
            for ($row = 1; $row <= $rowCount; $row++) {
                $rowConfig = $backendLayout['__config']['backend_layout.']['rows.'][$row . '.'] ?? null;
                if (!$rowConfig) {
                    // Skip empty rows
                    continue;
                }

                // Cycle through cells
                $tableCells = [];
                for ($col = 1; $col <= $colCount; $col++) {
                    $columnConfig = $rowConfig['columns.'][$col . '.'] ?? null;
                    if (!$columnConfig) {
                        // Skip empty columns
                        continue;
                    }

                    // Set table cell attributes
                    $tableCellAttributes = [
                        'class' => 'col-nowrap col-min',
                    ];
                    if (isset($columnConfig['colspan'])) {
                        $tableCellAttributes['colspan'] = $columnConfig['colspan'];
                    }
                    if (isset($columnConfig['rowspan'])) {
                        $tableCellAttributes['rowspan'] = $columnConfig['rowspan'];
                    }

                    $columnKey = null;
                    $columnTitle = '';
                    $isRestricted = false;
                    $isUnassigned = true;
                    if (isset($columnConfig['colPos'])) {
                        // If colPos is defined, initialize column information (e.g. title and restricted state)
                        $columnKey = (int)$columnConfig['colPos'];
                        foreach ($tcaColumnsConfiguration as $tcaColumnConfiguration) {
                            if ($tcaColumnConfiguration['colPos'] === $columnKey) {
                                $columnTitle = '<strong>' . htmlspecialchars($lang->sL($tcaColumnConfiguration['title'])) . '</strong>';
                                $isRestricted = $tcaColumnConfiguration['isRestricted'];
                                $isUnassigned = false;
                            }
                        }
                    }

                    // Generate the cell content, based on the columns' state (e.g. restricted or unassigned)
                    $cellContent = '';
                    if ($isRestricted) {
                        if ($hideRestrictedColumns) {
                            // Hide in case this column is not accessible and hideRestrictedColumns is set
                            $tableCellAttributes['class'] = 'hidden';
                        } else {
                            $cellContent = '
                                <p>
                                    ' . $columnTitle . ' <em>(' . htmlspecialchars($lang->getLL('noAccess')) . ')</em>
                                </p>';
                            $tableCellAttributes['class'] .= ' bg-danger bg-opacity-25';
                        }
                    } elseif ($isUnassigned) {
                        if ($hideRestrictedColumns) {
                            // Hide in case this column is not assigned and hideRestrictedColumns is set
                            $tableCellAttributes['class'] = 'hidden';
                        } else {
                            $cellContent = '
                                <em>
                                    ' . htmlspecialchars($lang->sL($columnConfig['name']) ?: '') . '
                                    ' . ' (' . htmlspecialchars($lang->getLL('notAssigned')) . ')' . '
                                </em>';
                            $tableCellAttributes['class'] .= ' bg-warning bg-opacity-25';
                        }
                    } else {
                        // If not restricted and not unassigned, wrap column title and render list (if available)
                        $cellContent = '<p>' . $columnTitle . '</p>';
                        if (!empty($lines[$columnKey])) {
                            $cellContent .= '
                                <ul class="list-unstyled">
                                    ' . implode(LF, array_map(static fn (string $line): string => '<li>' . $line . '</li>', $lines[$columnKey])) . '
                                </ul>';
                        }
                    }

                    // Add the table cell
                    $tableCells[] = '<td ' . GeneralUtility::implodeAttributes($tableCellAttributes) . '>' . $cellContent . '</td>';
                }

                // Add the table row
                $tableRows[] = '<tr>' . implode(LF, $tableCells) . '</tr>';
            }

            // Create the table content
            $tableContent = '<tbody>' . implode(LF, $tableRows) . '</tbody>';
        } else {
            // Build position map based on TCA colPos configuration
            $tableCells = [];
            foreach ($tcaColumnsConfiguration as $tcaColumnConfiguration) {
                if ($hideRestrictedColumns && $tcaColumnConfiguration['isRestricted']) {
                    // Skip in case this column is not accessible and restricted columns should be hidden
                    continue;
                }

                // Generate the cell content, based on the columns' state (e.g. restricted or unassigned)
                $tableCellClasses = 'col-nowrap col-min';
                $columnTitle = '<strong>' . htmlspecialchars($tcaColumnConfiguration['title']) . '</strong>';
                if ($tcaColumnConfiguration['isRestricted']) {
                    // If this colPos is restricted, add an information to the column title and color the cell
                    $tableCellClasses .= ' bg-danger bg-opacity-25';
                    $cellContent = '
                        <p>
                            ' . $columnTitle . ' <em>(' . htmlspecialchars($lang->getLL('noAccess')) . ')</em>
                        </p>';
                } else {
                    // If not restricted, wrap column title and render list (if available)
                    $cellContent = '<p>' . $columnTitle . '</p>';
                    if (!empty($lines[$tcaColumnConfiguration['colPos']])) {
                        $cellContent .= '
                            <ul class="list-unstyled">
                                ' . implode(LF, array_map(static fn (string $line): string => '<li>' . $line . '</li>', $lines[$tcaColumnConfiguration['colPos']])) . '
                            </ul>';
                    }
                }

                // Add the table cell
                $tableCells[] = '<td class="' . $tableCellClasses . '">' . $cellContent . '</td>';
            }

            // Create the table content
            $tableContent = '<tbody><tr>' . implode(LF, $tableCells) . '</tr></tbody>';
        }

        // Return the record map (table)
        return '
            <div class="table-fit">
                <table class="table table-sm table-bordered table-vertical-top">
                    ' . $tableContent . '
                </table>
            </div>';
    }

    /**
     * Fetch TCA colPos list from BackendLayoutView and prepare for map generation.
     * This also takes the "colPos_list" TSconfig into account.
     */
    protected function getColumnsConfiguration(int $id): array
    {
        $columnsConfiguration = $this->backendLayoutView->getColPosListItemsParsed($id);
        if ($columnsConfiguration === []) {
            return [];
        }

        // Prepare the columns configuration (using named keys, etc.)
        foreach ($columnsConfiguration as &$item) {
            $item = [
                'title' => $item[0],
                'colPos' => (int)$item[1],
                'isRestricted' => false,
            ];
        }
        unset($item);

        $sharedColPosList = trim(BackendUtility::getPagesTSconfig($id)['mod.']['SHARED.']['colPos_list'] ?? '');
        if ($sharedColPosList !== '') {
            $activeColPosArray = array_unique(GeneralUtility::intExplode(',', $sharedColPosList));
            if (!empty($columnsConfiguration) && !empty($activeColPosArray)) {
                foreach ($columnsConfiguration as &$item) {
                    if (!in_array((int)$item['colPos'], $activeColPosArray, true)) {
                        $item['isRestricted'] = true;
                    }
                }
                unset($item);
            }
        }

        return $columnsConfiguration;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
