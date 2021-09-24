<?php

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Position map class for moving content elements.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ContentMovingPagePositionMap extends PagePositionMap
{
    /**
     * @var int
     */
    public $dontPrintPageInsertIcons = 1;

    /**
     * Can be set to the sys_language uid to select content elements for.
     * @var int
     */
    public $cur_sys_language;

    /**
     * Wrapping page title.
     *
     * @param string $str Page title.
     * @param array $rec Page record (?)
     * @return string Wrapped title.
     */
    public function linkPageTitle($str, $rec)
    {
        $url = GeneralUtility::linkThisScript(['uid' => (int)$rec['uid'], 'moveUid' => $this->moveUid]);
        return '<a href="' . htmlspecialchars($url) . '">' . $str . '</a>';
    }

    /**
     * Creates HTML for inserting/moving content elements.
     *
     * @param int $pid page id onto which to insert content element.
     * @param int $moveUid Move-uid (tt_content element uid?)
     * @param string $colPosList List of columns to show
     * @param string $R_URI Request URI
     * @return string HTML
     */
    public function printContentElementColumns($pid, $moveUid, $colPosList, $R_URI)
    {
        $this->R_URI = $R_URI;
        $this->moveUid = $moveUid;
        $colPosArray = GeneralUtility::trimExplode(',', $colPosList, true);
        $lines = [];
        foreach ($colPosArray as $vv) {
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
                    $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter($vv, \PDO::PARAM_INT))
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
            $lines[$vv] = [];
            $lines[$vv][] = $this->insertPositionIcon(null, $vv, $moveUid, $pid);

            while ($row = $res->fetchAssociative()) {
                BackendUtility::workspaceOL('tt_content', $row);
                if (is_array($row)) {
                    $lines[$vv][] = $this->getRecordHeader($row);
                    $lines[$vv][] = $this->insertPositionIcon($row, $vv, $moveUid, $pid);
                }
            }
        }
        return $this->printRecordMap($lines, $colPosArray, $pid);
    }

    /**
     * Creates the table with the content columns
     *
     * @param array $lines Array with arrays of lines for each column
     * @param array $colPosArray Column position array
     * @param int $pid The id of the page
     * @return string HTML
     */
    public function printRecordMap($lines, $colPosArray, $pid = 0)
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
            foreach ($colPosArray as $vv) {
                $row .= '<td class="col-nowrap col-min" width="' . round(100 / $count) . '%">';
                $row .= '<p><strong>' . htmlspecialchars($this->getLanguageService()->sL(BackendUtility::getLabelFromItemlist('tt_content', 'colPos', $vv))) . '</strong></p>';
                if (!empty($lines[$vv])) {
                    $row .= '<ul class="list-unstyled">';
                    foreach ($lines[$vv] as $line) {
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
     * Creates a linked position icon.
     *
     * @param mixed $row Element row. If this is an array the link will cause an insert after this content element, otherwise
     * the link will insert at the first position in the column
     * @param string $vv Column position value.
     * @param int $moveUid Move uid
     * @param int $pid PID value.
     * @return string
     */
    public function insertPositionIcon($row, $vv, $moveUid, $pid)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        if (is_array($row)) {
            $location = $uriBuilder->buildUriFromRoute('tce_db', [
                'cmd[tt_content][' . $moveUid . '][' . $this->moveOrCopy . ']' => '-' . $row['uid'],
                'redirect' => $this->R_URI,
            ]);
        } else {
            $location = $uriBuilder->buildUriFromRoute('tce_db', [
                'cmd[tt_content][' . $moveUid . '][' . $this->moveOrCopy . ']' => $pid,
                'data[tt_content][' . $moveUid . '][colPos]' => $vv,
                'redirect' => $this->R_URI,
            ]);
        }
        return '<a href="' . htmlspecialchars($location) . '" data-bs-dismiss="modal"><i class="t3-icon fa fa-long-arrow-left" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:insertNewRecordHere')) . '"></i></a>';
    }

    /**
     * Create record header (includes the record icon, record title etc.)
     *
     * @param array $row Record row.
     * @return string HTML
     */
    public function getRecordHeader($row)
    {
        $line = '<span ' . BackendUtility::getRecordToolTip($row, 'tt_content') . '">' . $this->iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render() . '</span>';
        $line .= BackendUtility::getRecordTitle('tt_content', $row, true);
        if ($this->moveUid == $row['uid']) {
            $line = '<strong>' . $line . '</strong>';
        }
        return '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['uid' => (int)$row['uid'], 'moveUid' => ''])) . '">' . $line . '</a>';
    }
}
