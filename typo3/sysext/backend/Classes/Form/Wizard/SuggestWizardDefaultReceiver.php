<?php
namespace TYPO3\CMS\Backend\Form\Wizard;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Default implementation of a handler class for an ajax record selector.
 *
 * Normally other implementations should be inherited from this one.
 * queryTable() should not be overwritten under normal circumstances.
 */
class SuggestWizardDefaultReceiver
{
    /**
     * The name of the table to query
     *
     * @var string
     */
    protected $table = '';

    /**
     * The name of the foreign table to query (records from this table will be used for displaying instead of the ones
     * from $table)
     *
     * @var string
     */
    protected $mmForeignTable = '';

    /**
     * The select-clause to use when selecting the records (is manipulated and used by different functions, so it has to
     * be a global var)
     *
     * @var string
     */
    protected $selectClause = '';

    /**
     * The statement by which records will be ordered
     *
     * @var string
     */
    protected $orderByStatement = '';

    /**
     * Additional WHERE clause to be appended to the SQL
     *
     * @var string
     */
    protected $addWhere = '';

    /**
     * Configuration for this selector from TSconfig
     *
     * @var array
     */
    protected $config = [];

    /**
     * The list of pages that are allowed to perform the search for records on
     *
     * @var array Array of PIDs
     */
    protected $allowedPages = [];

    /**
     * The maximum number of items to select.
     *
     * @var int
     */
    protected $maxItems = 10;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * The constructor of this class
     *
     * @param string $table The table to query
     * @param array $config The configuration (TCA overlayed with TSconfig) to use for this selector
     */
    public function __construct($table, $config)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->table = $table;
        $this->config = $config;
        // get a list of all the pages that should be looked on
        if (isset($config['pidList'])) {
            $allowedPages = ($pageIds = GeneralUtility::trimExplode(',', $config['pidList']));
            $depth = (int)$config['pidDepth'];
            foreach ($pageIds as $pageId) {
                if ($pageId > 0) {
                    \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($allowedPages, $this->getAllSubpagesOfPage($pageId, $depth));
                }
            }
            $this->allowedPages = array_unique($allowedPages);
        }
        if (isset($config['maxItemsInResultList'])) {
            $this->maxItems = $config['maxItemsInResultList'];
        }
        if ($this->table == 'pages') {
            $this->addWhere = ' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1);
        }
        // if table is versionized, only get the records from the Live Workspace
        // the overlay itself of WS-records is done below
        if ($GLOBALS['TCA'][$this->table]['ctrl']['versioningWS'] == true) {
            $this->addWhere .= ' AND t3ver_wsid = 0';
        }
        if (isset($config['addWhere'])) {
            $this->addWhere .= ' ' . $config['addWhere'];
        }
    }

    /**
     * Queries a table for records and completely processes them
     *
     * Returns a two-dimensional array of almost finished records; the only need to be put into a <li>-structure
     *
     * If you subclass this class, you will most likely only want to overwrite the functions called from here, but not
     * this function itself
     *
     * @param array $params
     * @param int $recursionCounter The parent object
     * @return array Array of rows or FALSE if nothing found
     */
    public function queryTable(&$params, $recursionCounter = 0)
    {
        $rows = [];
        $this->params = &$params;
        $start = $recursionCounter * 50;
        $this->prepareSelectStatement();
        $this->prepareOrderByStatement();
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->table, $this->selectClause, '', $this->orderByStatement, $start . ', 50');
        $allRowsCount = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
        if ($allRowsCount) {
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                // check if we already have collected the maximum number of records
                if (count($rows) > $this->maxItems) {
                    break;
                }
                $this->manipulateRecord($row);
                $this->makeWorkspaceOverlay($row);
                // check if the user has access to the record
                if (!$this->checkRecordAccess($row, $row['uid'])) {
                    continue;
                }
                $spriteIcon = $this->iconFactory->getIconForRecord($this->table, $row, Icon::SIZE_SMALL)->render();
                $uid = $row['t3ver_oid'] > 0 ? $row['t3ver_oid'] : $row['uid'];
                $path = $this->getRecordPath($row, $uid);
                if (strlen($path) > 30) {
                    $languageService = $this->getLanguageService();
                    $croppedPath = '<abbr title="' . htmlspecialchars($path) . '">' .
                        htmlspecialchars(
                                $languageService->csConvObj->crop($languageService->charSet, $path, 10)
                                . '...'
                                . $languageService->csConvObj->crop($languageService->charSet, $path, -20)
                        ) .
                        '</abbr>';
                } else {
                    $croppedPath = htmlspecialchars($path);
                }
                $label = $this->getLabel($row);
                $entry = [
                    'text' => '<span class="suggest-label">' . $label . '</span><span class="suggest-uid">[' . $uid . ']</span><br />
								<span class="suggest-path">' . $croppedPath . '</span>',
                    'table' => $this->mmForeignTable ? $this->mmForeignTable : $this->table,
                    'label' => $label,
                    'path' => $path,
                    'uid' => $uid,
                    'style' => '',
                    'class' => isset($this->config['cssClass']) ? $this->config['cssClass'] : '',
                    'sprite' => $spriteIcon
                ];
                $rows[$this->table . '_' . $uid] = $this->renderRecord($row, $entry);
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
            // if there are less records than we need, call this function again to get more records
            if (count($rows) < $this->maxItems && $allRowsCount >= 50 && $recursionCounter < $this->maxItems) {
                $tmp = self::queryTable($params, ++$recursionCounter);
                $rows = array_merge($tmp, $rows);
            }
        }
        return $rows;
    }

    /**
     * Prepare the statement for selecting the records which will be returned to the selector. May also return some
     * other records (e.g. from a mm-table) which will be used later on to select the real records
     *
     * @return void
     */
    protected function prepareSelectStatement()
    {
        $searchWholePhrase = !isset($this->config['searchWholePhrase']) || $this->config['searchWholePhrase'];
        $searchString = $this->params['value'];
        $searchUid = (int)$searchString;
        if ($searchString !== '') {
            $searchString = $GLOBALS['TYPO3_DB']->quoteStr($searchString, $this->table);
            $likeCondition = ' LIKE \'' . ($searchWholePhrase ? '%' : '') . $GLOBALS['TYPO3_DB']->escapeStrForLike($searchString, $this->table) . '%\'';
            // Search in all fields given by label or label_alt
            $selectFieldsList = $GLOBALS['TCA'][$this->table]['ctrl']['label'] . ',' . $GLOBALS['TCA'][$this->table]['ctrl']['label_alt'] . ',' . $this->config['additionalSearchFields'];
            $selectFields = GeneralUtility::trimExplode(',', $selectFieldsList, true);
            $selectFields = array_unique($selectFields);
            $selectParts = [];
            foreach ($selectFields as $field) {
                $selectParts[] = $field . $likeCondition;
            }
            $this->selectClause = '(' . implode(' OR ', $selectParts) . ')';
            if ($searchUid > 0 && $searchUid == $searchString) {
                $this->selectClause = '(' . $this->selectClause . ' OR uid = ' . $searchUid . ')';
            }
        }
        if (isset($GLOBALS['TCA'][$this->table]['ctrl']['delete'])) {
            $this->selectClause .= ' AND ' . $GLOBALS['TCA'][$this->table]['ctrl']['delete'] . ' = 0';
        }
        if (!empty($this->allowedPages)) {
            $pidList = $GLOBALS['TYPO3_DB']->cleanIntArray($this->allowedPages);
            if (!empty($pidList)) {
                $this->selectClause .= ' AND pid IN (' . implode(', ', $pidList) . ') ';
            }
        }
        // add an additional search condition comment
        if (isset($this->config['searchCondition']) && $this->config['searchCondition'] !== '') {
            $this->selectClause .= ' AND ' . $this->config['searchCondition'];
        }
        // add the global clauses to the where-statement
        $this->selectClause .= $this->addWhere;
    }

    /**
     * Selects all subpages of one page, optionally only up to a certain level
     *
     * @param int $uid The uid of the page
     * @param int $depth The depth to select up to. Defaults to 99
     * @return array of page IDs
     */
    protected function getAllSubpagesOfPage($uid, $depth = 99)
    {
        $pageIds = [$uid];
        $level = 0;
        $pages = [$uid];
        // fetch all
        while ($depth - $level > 0 && !empty($pageIds)) {
            ++$level;
            $pidList = $GLOBALS['TYPO3_DB']->cleanIntArray($pageIds);
            $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', 'pid IN (' . implode(', ', $pidList) . ')', '', '', '', 'uid');
            if (!empty($rows)) {
                $pageIds = array_keys($rows);
                $pages = array_merge($pages, $pageIds);
            } else {
                break;
            }
        }
        return $pages;
    }

    /**
     * Prepares the clause by which the result elements are sorted. See description of ORDER BY in
     * SQL standard for reference.
     *
     * @return void
     */
    protected function prepareOrderByStatement()
    {
        if ($GLOBALS['TCA'][$this->table]['ctrl']['label']) {
            $this->orderByStatement = $GLOBALS['TCA'][$this->table]['ctrl']['label'];
        }
    }

    /**
     * Manipulate a record before using it to render the selector; may be used to replace a MM-relation etc.
     *
     * @param array $row
     */
    protected function manipulateRecord(&$row)
    {
    }

    /**
     * Selects whether the logged in Backend User is allowed to read a specific record
     *
     * @param array $row
     * @param int $uid
     * @return bool
     */
    protected function checkRecordAccess($row, $uid)
    {
        $retValue = true;
        $table = $this->mmForeignTable ?: $this->table;
        if ($table == 'pages') {
            if (!BackendUtility::readPageAccess($uid, $GLOBALS['BE_USER']->getPagePermsClause(1))) {
                $retValue = false;
            }
        } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['is_static']) && (bool)$GLOBALS['TCA'][$table]['ctrl']['is_static']) {
            $retValue = true;
        } else {
            if (!is_array(BackendUtility::readPageAccess($row['pid'], $GLOBALS['BE_USER']->getPagePermsClause(1)))) {
                $retValue = false;
            }
        }
        return $retValue;
    }

    /**
     * Overlay the given record with its workspace-version, if any
     *
     * @param array The record to get the workspace version for
     * @return void (passed by reference)
     */
    protected function makeWorkspaceOverlay(&$row)
    {
        // Check for workspace-versions
        if ($GLOBALS['BE_USER']->workspace != 0 && $GLOBALS['TCA'][$this->table]['ctrl']['versioningWS'] == true) {
            BackendUtility::workspaceOL($this->mmForeignTable ? $this->mmForeignTable : $this->table, $row);
        }
    }

    /**
     * Return the icon for a record - just a wrapper for two functions from \TYPO3\CMS\Backend\Utility\IconUtility
     *
     * @param array $row The record to get the icon for
     * @return string The path to the icon
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8, use IconFactory::getIconForRecord() directly
     */
    protected function getIcon($row)
    {
        GeneralUtility::logDeprecatedFunction();
        return $this->iconFactory->getIconForRecord($this->mmForeignTable ?: $this->table, $row, Icon::SIZE_SMALL)->render();
    }

    /**
     * Returns the path for a record. Is the whole path for all records except pages - for these the last part is cut
     * off, because it contains the pagetitle itself, which would be double information
     *
     * The path is returned uncut, cutting has to be done by calling function.
     *
     * @param array $row The row
     * @param array $record The record
     * @return string The record-path
     */
    protected function getRecordPath(&$row, $uid)
    {
        $titleLimit = max($this->config['maxPathTitleLength'], 0);
        if (($this->mmForeignTable ? $this->mmForeignTable : $this->table) == 'pages') {
            $path = BackendUtility::getRecordPath($uid, '', $titleLimit);
            // For pages we only want the first (n-1) parts of the path,
            // because the n-th part is the page itself
            $path = substr($path, 0, strrpos($path, '/', -2)) . '/';
        } else {
            $path = BackendUtility::getRecordPath($row['pid'], '', $titleLimit);
        }
        return $path;
    }

    /**
     * Returns a label for a given record; usually only a wrapper for \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle
     *
     * @param array $row The record to get the label for
     * @return string The label
     */
    protected function getLabel($row)
    {
        return BackendUtility::getRecordTitle($this->mmForeignTable ? $this->mmForeignTable : $this->table, $row, true);
    }

    /**
     * Calls a user function for rendering the page.
     *
     * This user function should manipulate $entry, especially $entry['text'].
     *
     * @param array $row The row
     * @param array $entry The entry to render
     * @return array The rendered entry (will be put into a <li> later on
     */
    protected function renderRecord($row, $entry)
    {
        // Call renderlet if available (normal pages etc. usually don't have one)
        if ($this->config['renderFunc'] != '') {
            $params = [
                'table' => $this->table,
                'uid' => $row['uid'],
                'row' => $row,
                'entry' => &$entry
            ];
            GeneralUtility::callUserFunction($this->config['renderFunc'], $params, $this, '');
        }
        return $entry;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
