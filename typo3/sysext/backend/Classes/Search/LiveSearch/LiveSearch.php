<?php
namespace TYPO3\CMS\Backend\Search\LiveSearch;

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
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class for handling backend live search.
 */
class LiveSearch
{
    /**
     * @var string
     */
    const PAGE_JUMP_TABLE = 'pages';

    /**
     * @var int
     */
    const RECURSIVE_PAGE_LEVEL = 99;

    /**
     * @var int
     */
    const GROUP_TITLE_MAX_LENGTH = 15;

    /**
     * @var int
     */
    const RECORD_TITLE_MAX_LENGTH = 28;

    /**
     * @var string
     */
    private $queryString = '';

    /**
     * @var int
     */
    private $startCount = 0;

    /**
     * @var int
     */
    private $limitCount = 5;

    /**
     * @var string
     */
    protected $userPermissions = '';

    /**
     * @var \TYPO3\CMS\Backend\Search\LiveSearch\QueryParser
     */
    protected $queryParser = null;

    /**
     * Initialize access settings
     */
    public function __construct()
    {
        $this->userPermissions = $GLOBALS['BE_USER']->getPagePermsClause(1);
        $this->queryParser = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Search\LiveSearch\QueryParser::class);
    }

    /**
     * Find records from database based on the given $searchQuery.
     *
     * @param string $searchQuery
     * @return array Result list of database search.
     */
    public function find($searchQuery)
    {
        $recordArray = [];
        $pageList = [];
        $mounts = $GLOBALS['BE_USER']->returnWebmounts();
        foreach ($mounts as $pageId) {
            $pageList[] = $this->getAvailablePageIds($pageId, self::RECURSIVE_PAGE_LEVEL);
        }
        $pageIdList = implode(',', array_unique(explode(',', implode(',', $pageList))));
        unset($pageList);
        $limit = $this->startCount . ',' . $this->limitCount;
        if ($this->queryParser->isValidCommand($searchQuery)) {
            $this->setQueryString($this->queryParser->getSearchQueryValue($searchQuery));
            $tableName = $this->queryParser->getTableNameFromCommand($searchQuery);
            if ($tableName) {
                $recordArray[] = $this->findByTable($tableName, $pageIdList, $limit);
            }
        } else {
            $this->setQueryString($searchQuery);
            $recordArray = $this->findByGlobalTableList($pageIdList);
        }
        return $recordArray;
    }

    /**
     * Retrieve the page record from given $id.
     *
     * @param int $id
     * @return array
     */
    protected function findPageById($id)
    {
        $pageRecord = [];
        $row = BackendUtility::getRecord(self::PAGE_JUMP_TABLE, $id);
        if (is_array($row)) {
            $pageRecord = $row;
        }
        return $pageRecord;
    }

    /**
     * Find records from all registered TCA table & column values.
     *
     * @param string $pageIdList Comma separated list of page IDs
     * @return array Records found in the database matching the searchQuery
     */
    protected function findByGlobalTableList($pageIdList)
    {
        $limit = $this->limitCount;
        $getRecordArray = [];
        foreach ($GLOBALS['TCA'] as $tableName => $value) {
            // if no access for the table (read or write), skip this table
            if (!$GLOBALS['BE_USER']->check('tables_select', $tableName) && !$GLOBALS['BE_USER']->check('tables_modify', $tableName)) {
                continue;
            }
            $recordArray = $this->findByTable($tableName, $pageIdList, '0,' . $limit);
            $recordCount = count($recordArray);
            if ($recordCount) {
                $limit = $limit - $recordCount;
                $getRecordArray[] = $recordArray;
                if ($limit <= 0) {
                    break;
                }
            }
        }
        return $getRecordArray;
    }

    /**
     * Find records by given table name.
     *
     * @param string $tableName Database table name
     * @param string $pageIdList Comma separated list of page IDs
     * @param string $limit MySql Limit notation
     * @return array Records found in the database matching the searchQuery
     * @see getRecordArray()
     * @see makeOrderByTable()
     * @see makeQuerySearchByTable()
     * @see extractSearchableFieldsFromTable()
     */
    protected function findByTable($tableName, $pageIdList, $limit)
    {
        $fieldsToSearchWithin = $this->extractSearchableFieldsFromTable($tableName);
        $getRecordArray = [];
        if (!empty($fieldsToSearchWithin)) {
            $pageBasedPermission = $tableName == 'pages' && $this->userPermissions ? $this->userPermissions : '1=1 ';
            $where = 'pid IN (' . $pageIdList . ') AND ' . $pageBasedPermission . $this->makeQuerySearchByTable($tableName, $fieldsToSearchWithin);
            $getRecordArray = $this->getRecordArray($tableName, $where, $this->makeOrderByTable($tableName), $limit);
        }
        return $getRecordArray;
    }

    /**
     * Process the Database operation to get the search result.
     *
     * @param string $tableName Database table name
     * @param string $where
     * @param string $orderBy
     * @param string $limit MySql Limit notation
     * @return array
     * @see getTitleFromCurrentRow()
     * @see getEditLink()
     */
    protected function getRecordArray($tableName, $where, $orderBy, $limit)
    {
        $collect = [];
        $queryParts = [
            'SELECT' => '*',
            'FROM' => $tableName,
            'WHERE' => $where,
            'ORDERBY' => $orderBy,
            'LIMIT' => $limit
        ];
        $result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $title = 'id=' . $row['uid'] . ', pid=' . $row['pid'];
            $collect[] = [
                'id' => $tableName . ':' . $row['uid'],
                'pageId' => $tableName === 'pages' ? $row['uid'] : $row['pid'],
                'typeLabel' =>  htmlspecialchars($this->getTitleOfCurrentRecordType($tableName)),
                'iconHTML' => '<span title="' . htmlspecialchars($title) . '">' . $iconFactory->getIconForRecord($tableName, $row, Icon::SIZE_SMALL)->render() . '</span>',
                'title' => htmlspecialchars(BackendUtility::getRecordTitle($tableName, $row)),
                'editLink' => htmlspecialchars($this->getEditLink($tableName, $row))
            ];
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($result);
        return $collect;
    }

    /**
     * Build a backend edit link based on given record.
     *
     * @param string $tableName Record table name
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess()
     */
    protected function getEditLink($tableName, $row)
    {
        $pageInfo = BackendUtility::readPageAccess($row['pid'], $this->userPermissions);
        $calcPerms = $GLOBALS['BE_USER']->calcPerms($pageInfo);
        $editLink = '';
        if ($tableName == 'pages') {
            $localCalcPerms = $GLOBALS['BE_USER']->calcPerms(BackendUtility::getRecord('pages', $row['uid']));
            $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
        } else {
            $permsEdit = $calcPerms & Permission::CONTENT_EDIT;
        }
        // "Edit" link - Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($permsEdit) {
            $returnUrl = BackendUtility::getModuleUrl('web_list', ['id' => $row['pid']]);
            $editLink = BackendUtility::getModuleUrl('record_edit', [
                'edit[' . $tableName . '][' . $row['uid'] . ']' => 'edit',
                'returnUrl' => $returnUrl
            ]);
        }
        return $editLink;
    }

    /**
     * Retrieve the record name
     *
     * @param string $tableName Record table name
     * @return string
     */
    protected function getTitleOfCurrentRecordType($tableName)
    {
        return $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
    }

    /**
     * Crops a title string to a limited length and if it really was cropped,
     * wrap it in a <span title="...">|</span>,
     * which offers a tooltip with the original title when moving mouse over it.
     *
     * @param string $title The title string to be cropped
     * @param int $titleLength Crop title after this length - if not set, BE_USER->uc['titleLen'] is used
     * @return string The processed title string, wrapped in <span title="...">|</span> if cropped
     */
    public function getRecordTitlePrep($title, $titleLength = 0)
    {
        // If $titleLength is not a valid positive integer, use BE_USER->uc['titleLen']:
        if (!$titleLength || !MathUtility::canBeInterpretedAsInteger($titleLength) || $titleLength < 0) {
            $titleLength = $GLOBALS['BE_USER']->uc['titleLen'];
        }
        return htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, $titleLength));
    }

    /**
     * Build the MySql where clause by table.
     *
     * @param string $tableName Record table name
     * @param array $fieldsToSearchWithin User right based visible fields where we can search within.
     * @return string
     */
    protected function makeQuerySearchByTable($tableName, array $fieldsToSearchWithin)
    {
        $queryPart = '';
        $whereParts = [];
        // If the search string is a simple integer, assemble an equality comparison
        if (MathUtility::canBeInterpretedAsInteger($this->queryString)) {
            foreach ($fieldsToSearchWithin as $fieldName) {
                if ($fieldName == 'uid' || $fieldName == 'pid' || isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
                    $fieldConfig = &$GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
                    // Assemble the search condition only if the field is an integer, or is uid or pid
                    if ($fieldName == 'uid' || $fieldName == 'pid' || $fieldConfig['type'] == 'input' && $fieldConfig['eval'] && GeneralUtility::inList($fieldConfig['eval'], 'int')) {
                        $whereParts[] = $fieldName . '=' . $this->queryString;
                    } elseif (
                        $fieldConfig['type'] == 'text' ||
                        $fieldConfig['type'] == 'flex' ||
                        ($fieldConfig['type'] == 'input' && (!$fieldConfig['eval'] ||
                        !preg_match('/date|time|int/', $fieldConfig['eval'])))) {
                        // Otherwise and if the field makes sense to be searched, assemble a like condition
                            $whereParts[] = $fieldName . ' LIKE \'%' . $this->queryString . '%\'';
                    }
                }
            }
        } else {
            $like = '\'%' . $GLOBALS['TYPO3_DB']->escapeStrForLike($GLOBALS['TYPO3_DB']->quoteStr($this->queryString, $tableName), $tableName) . '%\'';
            foreach ($fieldsToSearchWithin as $fieldName) {
                if (isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
                    $fieldConfig = &$GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
                    // Check whether search should be case-sensitive or not
                    $format = 'LOWER(%s) LIKE LOWER(%s)';
                    if (is_array($fieldConfig['search'])) {
                        if (in_array('case', $fieldConfig['search'])) {
                            $format = '%s LIKE %s';
                        }
                        // Apply additional condition, if any
                        if ($fieldConfig['search']['andWhere']) {
                            $format = '((' . $fieldConfig['search']['andWhere'] . ') AND (' . $format . '))';
                        }
                    }
                    // Assemble the search condition only if the field makes sense to be searched
                    if ($fieldConfig['type'] == 'text' || $fieldConfig['type'] == 'flex' || $fieldConfig['type'] == 'input' && (!$fieldConfig['eval'] || !preg_match('/date|time|int/', $fieldConfig['eval']))) {
                        $whereParts[] = sprintf($format, $fieldName, $like);
                    }
                }
            }
        }
        // If at least one condition was defined, create the search query
        if (!empty($whereParts)) {
            $queryPart = ' AND (' . implode(' OR ', $whereParts) . ')';
            // And the relevant conditions for deleted and versioned records
            $queryPart .= BackendUtility::deleteClause($tableName);
            $queryPart .= BackendUtility::versioningPlaceholderClause($tableName);
            $queryPart .= BackendUtility::getWorkspaceWhereClause($tableName);
        } else {
            $queryPart = ' AND 0 = 1';
        }
        return $queryPart;
    }

    /**
     * Build the MySql ORDER BY statement.
     *
     * @param string $tableName Record table name
     * @return string
     */
    protected function makeOrderByTable($tableName)
    {
        $orderBy = '';
        if (is_array($GLOBALS['TCA'][$tableName]['ctrl']) && array_key_exists('sortby', $GLOBALS['TCA'][$tableName]['ctrl'])) {
            $sortBy = trim($GLOBALS['TCA'][$tableName]['ctrl']['sortby']);
            if (!empty($sortBy)) {
                $orderBy = 'ORDER BY ' . $sortBy;
            }
        } else {
            $orderBy = $GLOBALS['TCA'][$tableName]['ctrl']['default_sortby'];
        }
        return $GLOBALS['TYPO3_DB']->stripOrderBy($orderBy);
    }

    /**
     * Get all fields from given table where we can search for.
     *
     * @param string $tableName Name of the table for which to get the searchable fields
     * @return array
     */
    protected function extractSearchableFieldsFromTable($tableName)
    {
        // Get the list of fields to search in from the TCA, if any
        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['searchFields'])) {
            $fieldListArray = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'], true);
        } else {
            $fieldListArray = [];
        }
        // Add special fields
        if ($GLOBALS['BE_USER']->isAdmin()) {
            $fieldListArray[] = 'uid';
            $fieldListArray[] = 'pid';
        }
        return $fieldListArray;
    }

    /**
     * Safely retrieve the queryString.
     *
     * @param string $tableName
     * @return string
     */
    public function getQueryString($tableName = '')
    {
        return $GLOBALS['TYPO3_DB']->quoteStr($this->queryString, $tableName);
    }

    /**
     * Setter for limit value.
     *
     * @param int $limitCount
     * @return void
     */
    public function setLimitCount($limitCount)
    {
        $limit = MathUtility::convertToPositiveInteger($limitCount);
        if ($limit > 0) {
            $this->limitCount = $limit;
        }
    }

    /**
     * Setter for start count value.
     *
     * @param int $startCount
     * @return void
     */
    public function setStartCount($startCount)
    {
        $this->startCount = MathUtility::convertToPositiveInteger($startCount);
    }

    /**
     * Setter for the search query string.
     *
     * @param string $queryString
     * @return void
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS()
     */
    public function setQueryString($queryString)
    {
        $this->queryString = GeneralUtility::removeXSS($queryString);
    }

    /**
     * Creates an instance of \TYPO3\CMS\Backend\Tree\View\PageTreeView which will select a
     * page tree to $depth and return the object. In that object we will find the ids of the tree.
     *
     * @param int $id Page id.
     * @param int $depth Depth to go down.
     * @return string Comma separated list of uids
     */
    protected function getAvailablePageIds($id, $depth)
    {
        $tree = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\View\PageTreeView::class);
        $tree->init('AND ' . $this->userPermissions);
        $tree->makeHTML = 0;
        $tree->fieldArray = ['uid', 'php_tree_stop'];
        if ($depth) {
            $tree->getTree($id, $depth, '');
        }
        $tree->ids[] = $id;
        // add workspace pid - workspace permissions are taken into account by where clause later
        $tree->ids[] = -1;
        $idList = implode(',', $tree->ids);
        return $idList;
    }
}
