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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class for handling backend live search.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
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
     * @var QueryParser
     */
    protected $queryParser;

    /**
     * Initialize access settings
     */
    public function __construct()
    {
        $this->userPermissions = $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW);
        $this->queryParser = GeneralUtility::makeInstance(QueryParser::class);
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
        $pageIdList = array_unique(explode(',', implode(',', $pageList)));
        unset($pageList);
        if ($this->queryParser->isValidCommand($searchQuery)) {
            $this->setQueryString($this->queryParser->getSearchQueryValue($searchQuery));
            $tableName = $this->queryParser->getTableNameFromCommand($searchQuery);
            if ($tableName) {
                $recordArray[] = $this->findByTable($tableName, $pageIdList, $this->startCount, $this->limitCount);
            }
        } else {
            $this->setQueryString($searchQuery);
            $recordArray = $this->findByGlobalTableList($pageIdList);
        }
        return $recordArray;
    }

    /**
     * Find records from all registered TCA table & column values.
     *
     * @param array $pageIdList Comma separated list of page IDs
     * @return array Records found in the database matching the searchQuery
     */
    protected function findByGlobalTableList($pageIdList)
    {
        $limit = $this->limitCount;
        $getRecordArray = [];
        foreach ($GLOBALS['TCA'] as $tableName => $value) {
            // if no access for the table (read or write) or table is hidden, skip this table
            if (
                (isset($value['ctrl']['hideTable']) && $value['ctrl']['hideTable'])
                ||
                (
                    !$GLOBALS['BE_USER']->check('tables_select', $tableName) &&
                    !$GLOBALS['BE_USER']->check('tables_modify', $tableName)
                )
            ) {
                continue;
            }
            $recordArray = $this->findByTable($tableName, $pageIdList, 0, $limit);
            $recordCount = count($recordArray);
            if ($recordCount) {
                $limit -= $recordCount;
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
     * @param array $pageIdList Comma separated list of page IDs
     * @param int $firstResult
     * @param int $maxResults
     * @return array Records found in the database matching the searchQuery
     * @see getRecordArray()
     * @see makeQuerySearchByTable()
     * @see extractSearchableFieldsFromTable()
     */
    protected function findByTable($tableName, $pageIdList, $firstResult, $maxResults)
    {
        $fieldsToSearchWithin = $this->extractSearchableFieldsFromTable($tableName);
        $getRecordArray = [];
        if (!empty($fieldsToSearchWithin)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()
                ->removeByType(HiddenRestriction::class)
                ->removeByType(StartTimeRestriction::class)
                ->removeByType(EndTimeRestriction::class);

            $queryBuilder
                ->select('*')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($pageIdList, Connection::PARAM_INT_ARRAY)
                    ),
                    $this->makeQuerySearchByTable($queryBuilder, $tableName, $fieldsToSearchWithin)
                )
                ->setFirstResult($firstResult)
                ->setMaxResults($maxResults);

            if ($tableName === 'pages' && $this->userPermissions) {
                $queryBuilder->andWhere($this->userPermissions);
            }

            $orderBy = $GLOBALS['TCA'][$tableName]['ctrl']['sortby'] ?: $GLOBALS['TCA'][$tableName]['ctrl']['default_sortby'];
            foreach (QueryHelper::parseOrderBy((string)$orderBy) as $orderPair) {
                list($fieldName, $order) = $orderPair;
                $queryBuilder->addOrderBy($fieldName, $order);
            }

            $getRecordArray = $this->getRecordArray($queryBuilder, $tableName);
        }

        return $getRecordArray;
    }

    /**
     * Process the Database operation to get the search result.
     *
     * @param QueryBuilder $queryBuilder Database table name
     * @param string $tableName
     * @return array
     * @see getTitleFromCurrentRow()
     * @see getEditLink()
     */
    protected function getRecordArray($queryBuilder, $tableName)
    {
        $collect = [];
        $result = $queryBuilder->execute();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        while ($row = $result->fetch()) {
            BackendUtility::workspaceOL($tableName, $row);
            if (!is_array($row)) {
                continue;
            }
            $onlineUid = $row['t3ver_oid'] ?: $row['uid'];
            $title = 'id=' . $row['uid'] . ', pid=' . $row['pid'];
            $collect[$onlineUid] = [
                'id' => $tableName . ':' . $row['uid'],
                'pageId' => $tableName === 'pages' ? $row['uid'] : $row['pid'],
                'typeLabel' =>  htmlspecialchars($this->getTitleOfCurrentRecordType($tableName)),
                'iconHTML' => '<span title="' . htmlspecialchars($title) . '">' . $iconFactory->getIconForRecord($tableName, $row, Icon::SIZE_SMALL)->render() . '</span>',
                'title' => htmlspecialchars(BackendUtility::getRecordTitle($tableName, $row)),
                'editLink' => htmlspecialchars($this->getEditLink($tableName, $row))
            ];
        }
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
        if ($tableName === 'pages') {
            $localCalcPerms = $GLOBALS['BE_USER']->calcPerms(BackendUtility::getRecord('pages', $row['uid']));
            $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
        } else {
            $permsEdit = $calcPerms & Permission::CONTENT_EDIT;
        }
        // "Edit" link - Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($permsEdit) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $returnUrl = (string)$uriBuilder->buildUriFromRoute('web_list', ['id' => $row['pid']]);
            $editLink = (string)$uriBuilder->buildUriFromRoute('record_edit', [
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
     * @param QueryBuilder $queryBuilder
     * @param string $tableName Record table name
     * @param array $fieldsToSearchWithin User right based visible fields where we can search within.
     * @return CompositeExpression
     */
    protected function makeQuerySearchByTable(QueryBuilder &$queryBuilder, $tableName, array $fieldsToSearchWithin)
    {
        $constraints = [];

        // If the search string is a simple integer, assemble an equality comparison
        if (MathUtility::canBeInterpretedAsInteger($this->queryString)) {
            foreach ($fieldsToSearchWithin as $fieldName) {
                if ($fieldName !== 'uid'
                    && $fieldName !== 'pid'
                    && !isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])
                ) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
                $fieldType = $fieldConfig['type'];
                $evalRules = $fieldConfig['eval'] ?: '';

                // Assemble the search condition only if the field is an integer, or is uid or pid
                if ($fieldName === 'uid'
                    || $fieldName === 'pid'
                    || ($fieldType === 'input' && $evalRules && GeneralUtility::inList($evalRules, 'int'))
                ) {
                    $constraints[] = $queryBuilder->expr()->eq(
                        $fieldName,
                        $queryBuilder->createNamedParameter($this->queryString, \PDO::PARAM_INT)
                    );
                } elseif ($fieldType === 'text'
                    || $fieldType === 'flex'
                    || ($fieldType === 'input' && (!$evalRules || !preg_match('/\b(?:date|time|int)\b/', $evalRules)))
                ) {
                    // Otherwise and if the field makes sense to be searched, assemble a like condition
                    $constraints[] = $constraints[] = $queryBuilder->expr()->like(
                        $fieldName,
                        $queryBuilder->createNamedParameter(
                            '%' . $queryBuilder->escapeLikeWildcards((int)$this->queryString) . '%',
                            \PDO::PARAM_STR
                        )
                    );
                }
            }
        } else {
            $like = '%' . $queryBuilder->escapeLikeWildcards($this->queryString) . '%';
            foreach ($fieldsToSearchWithin as $fieldName) {
                if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
                    continue;
                }
                $fieldConfig = &$GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
                $fieldType = $fieldConfig['type'];
                $evalRules = $fieldConfig['eval'] ?: '';

                // Check whether search should be case-sensitive or not
                $searchConstraint = $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->comparison(
                        'LOWER(' . $queryBuilder->quoteIdentifier($fieldName) . ')',
                        'LIKE',
                        $queryBuilder->createNamedParameter(mb_strtolower($like), \PDO::PARAM_STR)
                    )
                );

                if (is_array($fieldConfig['search'])) {
                    if (in_array('case', $fieldConfig['search'], true)) {
                        // Replace case insensitive default constraint
                        $searchConstraint = $queryBuilder->expr()->andX(
                            $queryBuilder->expr()->like(
                                $fieldName,
                                $queryBuilder->createNamedParameter($like, \PDO::PARAM_STR)
                            )
                        );
                    }
                    // Apply additional condition, if any
                    if ($fieldConfig['search']['andWhere']) {
                        $searchConstraint->add(
                            QueryHelper::stripLogicalOperatorPrefix($fieldConfig['search']['andWhere'])
                        );
                    }
                }
                // Assemble the search condition only if the field makes sense to be searched
                if ($fieldType === 'text'
                    || $fieldType === 'flex'
                    || ($fieldType === 'input' && (!$evalRules || !preg_match('/\b(?:date|time|int)\b/', $evalRules)))
                ) {
                    if ($searchConstraint->count() !== 0) {
                        $constraints[] = $searchConstraint;
                    }
                }
            }
        }

        // If no search field conditions have been build ensure no results are returned
        if (empty($constraints)) {
            return '0=1';
        }

        return $queryBuilder->expr()->orX(...$constraints);
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
     * Setter for limit value.
     *
     * @param int $limitCount
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
     */
    public function setStartCount($startCount)
    {
        $this->startCount = MathUtility::convertToPositiveInteger($startCount);
    }

    /**
     * Setter for the search query string.
     *
     * @param string $queryString
     */
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;
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
        return implode(',', $tree->ids);
    }
}
