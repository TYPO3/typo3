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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Local position map class when creating new Content Elements. Previously this was extended from the PagePositionMap
 * however it is not related to positioning pages, but only contents.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ContentCreationPagePositionMap
{
    /**
     * Can be set to the sys_language uid to select content elements for.
     */
    public int $cur_sys_language = 0;

    /**
     * Default values defined for the item
     */
    public array $defVals = [];

    /**
     * Whether the item should directly be persisted (avoiding FormEngine)
     */
    public bool $saveAndClose = false;

    /**
     * The return url, forwarded to FormEngine (or SimpleDataHandler)
     */
    protected string $R_URI = '';

    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;

    public function __construct(IconFactory $iconFactory, UriBuilder $uriBuilder)
    {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Creates HTML for inserting/moving content elements.
     *
     * @param int $pid page id onto which to insert content element.
     * @param string $colPosList List of columns to show
     * @param string $R_URI Request URI
     * @return string HTML
     */
    public function printContentElementColumns(int $pid, string $colPosList, string $R_URI): string
    {
        $this->R_URI = $R_URI;
        $colPosArray = GeneralUtility::trimExplode(',', $colPosList, true);
        $lines = [];
        foreach ($colPosArray as $colPos) {
            $colPos = (int)$colPos;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace))
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

            $res = $queryBuilder->execute();
            $lines[$colPos] = [];
            $lines[$colPos][] = $this->insertPositionIcon(null, $colPos, $pid);

            while ($row = $res->fetchAssociative()) {
                BackendUtility::workspaceOL('tt_content', $row);
                if (is_array($row)) {
                    $lines[$colPos][] = $this->getRecordHeader($row);
                    $lines[$colPos][] = $this->insertPositionIcon($row, $colPos, $pid);
                }
            }
        }
        return $this->printRecordMap($lines, $colPosArray, $pid);
    }

    /**
     * Creates a linked position icon.
     *
     * @param array|null $row Element row. If this is an array the link will cause an insert after this content element, otherwise
     * the link will insert at the first position in the column
     * @param int $colPos Column position value.
     * @param int $pid PID value.
     * @return string
     */
    protected function insertPositionIcon(?array $row, int $colPos, int $pid): string
    {
        if ($this->saveAndClose) {
            $id = StringUtility::getUniqueId('NEW');
            $parameters['data']['tt_content'][$id] = $this->defVals;
            $parameters['data']['tt_content'][$id]['colPos'] = $colPos;
            $parameters['data']['tt_content'][$id]['pid'] = (is_array($row) ? -$row['uid'] : $pid);
            $parameters['data']['tt_content'][$id]['sys_language_uid'] = $this->cur_sys_language;
            $parameters['redirect'] = $this->R_URI;
            $target = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $parameters);
        } else {
            $target = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'tt_content' => [
                        (is_array($row) ? -$row['uid'] : $pid) => 'new',
                    ],
                ],
                'returnUrl' => $this->R_URI,
                'defVals' => [
                    'tt_content' => array_merge(
                        ['colPos' => $colPos, 'sys_language_uid' => $this->cur_sys_language],
                        $this->defVals
                    ),
                ],
            ]);
        }

        return '
            <button type="button"  class="btn btn-link p-0" data-target="' . htmlspecialchars($target) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:insertNewRecordHere')) . '">
                ' . $this->iconFactory->getIcon('actions-arrow-left', Icon::SIZE_SMALL)->render() . '
            </button>';
    }

    /**
     * Creates the table with the content columns
     *
     * @param array $lines Array with arrays of lines for each column
     * @param array $colPosArray Column position array
     * @param int $pid The id of the page
     * @return string HTML
     */
    protected function printRecordMap($lines, $colPosArray, $pid)
    {
        $count = MathUtility::forceIntegerInRange(count($colPosArray), 1);
        $backendLayoutProvider = GeneralUtility::makeInstance(BackendLayoutView::class);
        $backendLayout = $backendLayoutProvider->getSelectedBackendLayout($pid);
        if (isset($backendLayout['__config']['backend_layout.'])) {
            $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
            $table = '<div class="table-fit"><table class="table table-sm table-bordered table-vertical-top">';
            $colCount = (int)$backendLayout['__config']['backend_layout.']['colCount'];
            $rowCount = (int)$backendLayout['__config']['backend_layout.']['rowCount'];
            $table .= '<colgroup>';
            for ($i = 0; $i < $colCount; $i++) {
                $table .= '<col style="width:' . 100 / $colCount . '%"></col>';
            }
            $table .= '</colgroup>';
            $table .= '<tbody>';
            $tcaItems = $backendLayoutProvider->getColPosListItemsParsed($pid);
            // Cycle through rows
            for ($row = 1; $row <= $rowCount; $row++) {
                $rowConfig = $backendLayout['__config']['backend_layout.']['rows.'][$row . '.'];
                if (!isset($rowConfig)) {
                    continue;
                }
                $table .= '<tr>';
                for ($col = 1; $col <= $colCount; $col++) {
                    $columnConfig = $rowConfig['columns.'][$col . '.'] ?? false;
                    if (!$columnConfig) {
                        continue;
                    }
                    // Which tt_content colPos should be displayed inside this cell
                    $columnKey = (int)($columnConfig['colPos'] ?? 0);
                    $head = '';
                    foreach ($tcaItems as $item) {
                        if ($item[1] == $columnKey) {
                            $head = htmlspecialchars($this->getLanguageService()->sL($item[0]));
                        }
                    }
                    // Render the grid cell
                    $table .= '<td'
                        . (isset($columnConfig['colspan']) ? ' colspan="' . $columnConfig['colspan'] . '"' : '')
                        . (isset($columnConfig['rowspan']) ? ' rowspan="' . $columnConfig['rowspan'] . '"' : '')
                        . ' class="col-nowrap col-min'
                        . (!isset($columnConfig['colPos']) ? ' warning' : '')
                        . (isset($columnConfig['colPos']) && !$head ? ' danger' : '') . '">';
                    // Render header
                    $table .= '<p>';
                    if (isset($columnConfig['colPos']) && $head) {
                        $table .= '<strong>' . $head . '</strong>';
                    } elseif (isset($columnConfig['colPos'])) {
                        $table .= '<em>' . $this->getLanguageService()->getLL('noAccess') . '</em>';
                    } else {
                        $table .= '<em>' . ($this->getLanguageService()->sL($columnConfig['name']) ?: '') . ' (' . $this->getLanguageService()->getLL('notAssigned') . ')' . '</em>';
                    }
                    $table .= '</p>';
                    // Render lines
                    if (isset($columnConfig['colPos']) && $head && !empty($lines[$columnKey])) {
                        $table .= '<ul class="list-unstyled">';
                        foreach ($lines[$columnKey] as $line) {
                            $table .= '<li>' . $line . '</li>';
                        }
                        $table .= '</ul>';
                    }
                    $table .= '</td>';
                }
                $table .= '</tr>';
            }
            $table .= '</tbody>';
            $table .= '</table></div>';
        } else {
            // Traverse the columns here:
            $row = '';
            foreach ($colPosArray as $colPos) {
                $row .= '<td class="col-nowrap col-min" width="' . round(100 / $count) . '%">';
                $row .= '<p><strong>' . htmlspecialchars($this->getLanguageService()->sL(BackendUtility::getLabelFromItemlist('tt_content', 'colPos', $colPos))) . '</strong></p>';
                if (!empty($lines[$colPos])) {
                    $row .= '<ul class="list-unstyled">';
                    foreach ($lines[$colPos] as $line) {
                        $row .= '<li>' . $line . '</li>';
                    }
                    $row .= '</ul>';
                }
                $row .= '</td>';
            }
            $table = '

			<!--
				Map of records in columns:
			-->
			<div class="table-fit">
				<table class="table table-sm table-bordered table-vertical-top">
					<tr>' . $row . '</tr>
				</table>
			</div>

			';
        }
        return $table;
    }

    /**
     * Create record header (includes the record icon, record title etc.)
     *
     * @param array $row Record row.
     * @return string HTML
     */
    protected function getRecordHeader(array $row): string
    {
        $line = '<span ' . BackendUtility::getRecordToolTip($row, 'tt_content') . '">' . $this->iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render() . '</span>';
        $line .= BackendUtility::getRecordTitle('tt_content', $row, true);
        return $line;
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
