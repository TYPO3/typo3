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

namespace TYPO3\CMS\Backend\Search\LiveSearch;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Search\Event\ModifyQueryForLiveSearchEvent;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class for handling backend live search.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class LiveSearch
{
    private const RECURSIVE_PAGE_LEVEL = 99;

    private string $queryString = '';
    private int $startCount = 0;
    private int $limitCount = 50;
    protected string $userPermissions = '';

    protected UriBuilder $uriBuilder;
    protected IconFactory $iconFactory;
    protected QueryParser $queryParser;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        UriBuilder $uriBuilder,
        IconFactory $iconFactory,
        QueryParser $queryParser,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->uriBuilder = $uriBuilder;
        $this->iconFactory = $iconFactory;
        $this->queryParser = $queryParser;
        $this->eventDispatcher = $eventDispatcher;
        $this->userPermissions = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
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
        $pageIdList = $this->getPageIdList();
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
     * List of available page uids for user, empty array for admin users.
     */
    protected function getPageIdList(): array
    {
        if ($this->getBackendUser()->isAdmin()) {
            return [];
        }
        $mounts = $this->getBackendUser()->returnWebmounts();
        $pageList = $mounts;
        $repository = GeneralUtility::makeInstance(PageTreeRepository::class);
        $repository->setAdditionalWhereClause($this->userPermissions);
        $pages = $repository->getFlattenedPages([$mounts], self::RECURSIVE_PAGE_LEVEL);
        foreach ($pages as $page) {
            $pageList[] = (int)$page['uid'];
        }
        return $pageList;
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
                    !$this->getBackendUser()->check('tables_select', $tableName) &&
                    !$this->getBackendUser()->check('tables_modify', $tableName)
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
                    $this->makeQuerySearchByTable($queryBuilder, $tableName, $fieldsToSearchWithin)
                )
                ->setFirstResult($firstResult)
                ->setMaxResults($maxResults);

            if ($pageIdList !== []) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($pageIdList, Connection::PARAM_INT_ARRAY)
                    )
                );
            }

            if ($tableName === 'pages' && $this->userPermissions) {
                $queryBuilder->andWhere($this->userPermissions);
            }

            $queryBuilder->addOrderBy('uid', 'DESC');

            $event = $this->eventDispatcher->dispatch(new ModifyQueryForLiveSearchEvent($queryBuilder, $tableName));
            $getRecordArray = $this->getRecordArray($event->getQueryBuilder(), $tableName);
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
        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL($tableName, $row);
            if (!is_array($row)) {
                continue;
            }
            $onlineUid = ($row['t3ver_oid'] ?? false) ?: $row['uid'];
            $title = 'id=' . $row['uid'] . ', pid=' . $row['pid'];
            $icon = $this->iconFactory->getIconForRecord($tableName, $row, Icon::SIZE_SMALL);
            $collect[$onlineUid] = [
                'typeLabel' => $this->getTitleOfCurrentRecordType($tableName),
                'uid' => $row['uid'],
                'pid' => $row['pid'],
                'icon' => [
                    'title' => $title,
                    'identifier' => $icon->getIdentifier(),
                    'overlay' => $icon->getOverlayIcon()?->getIdentifier(),
                ],
                'title' => BackendUtility::getRecordTitle($tableName, $row),
                'editLink' => $this->getEditLink($tableName, $row),
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
        $backendUser = $this->getBackendUser();
        $editLink = '';
        if ($tableName === 'pages') {
            $localCalcPerms = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $row['uid']) ?? []));
            $permsEdit = $localCalcPerms->editPagePermissionIsGranted();
        } else {
            $calcPerms = new Permission($backendUser->calcPerms(BackendUtility::readPageAccess($row['pid'], $this->userPermissions) ?: []));
            $permsEdit = $calcPerms->editContentPermissionIsGranted();
        }
        // "Edit" link - Only with proper edit permissions
        if (!($GLOBALS['TCA'][$tableName]['ctrl']['readOnly'] ?? false)
            && (
                $backendUser->isAdmin()
                || (
                    $permsEdit
                    && !($GLOBALS['TCA'][$tableName]['ctrl']['adminOnly'] ?? false)
                    && $backendUser->check('tables_modify', $tableName)
                    && $backendUser->recordEditAccessInternals($tableName, $row)
                )
            )
        ) {
            $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('web_list', ['id' => $row['pid']]);
            $editLink = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit[' . $tableName . '][' . $row['uid'] . ']' => 'edit',
                'returnUrl' => $returnUrl,
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
        return $this->getLanguageService()->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
    }

    /**
     * Build the MySql where clause by table.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $tableName Record table name
     * @param array $fieldsToSearchWithin User right based visible fields where we can search within.
     * @return CompositeExpression
     */
    protected function makeQuerySearchByTable(QueryBuilder $queryBuilder, $tableName, array $fieldsToSearchWithin)
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
                $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] ?? [];
                $fieldType = $fieldConfig['type'] ?? '';

                // Assemble the search condition only if the field is an integer, or is uid or pid
                if ($fieldName === 'uid'
                    || $fieldName === 'pid'
                    || ($fieldType === 'number' && ($fieldConfig['format'] ?? 'integer') === 'integer')
                    || ($fieldType === 'datetime' && !in_array($fieldConfig['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true))
                ) {
                    $constraints[] = $queryBuilder->expr()->eq(
                        $fieldName,
                        $queryBuilder->createNamedParameter($this->queryString, Connection::PARAM_INT)
                    );
                } elseif ($fieldType === 'input'
                    || $fieldType === 'text'
                    || $fieldType === 'flex'
                    || $fieldType === 'email'
                    || $fieldType === 'link'
                    || $fieldType === 'color'
                    || $fieldType === 'slug'
                ) {
                    // Otherwise and if the field makes sense to be searched, assemble a like condition
                    $constraints[] = $queryBuilder->expr()->like(
                        $fieldName,
                        $queryBuilder->createNamedParameter(
                            '%' . $queryBuilder->escapeLikeWildcards($this->queryString) . '%'
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
                $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] ?? [];
                $fieldType = $fieldConfig['type'] ?? '';

                // Check whether search should be case-sensitive or not
                $searchConstraint = $queryBuilder->expr()->and(
                    $queryBuilder->expr()->comparison(
                        'LOWER(' . $queryBuilder->quoteIdentifier($fieldName) . ')',
                        'LIKE',
                        $queryBuilder->createNamedParameter(mb_strtolower($like))
                    )
                );

                if (is_array($fieldConfig['search'] ?? false)) {
                    if (in_array('case', $fieldConfig['search'], true)) {
                        // Replace case insensitive default constraint
                        $searchConstraint = $queryBuilder->expr()->and(
                            $queryBuilder->expr()->like(
                                $fieldName,
                                $queryBuilder->createNamedParameter($like)
                            )
                        );
                    }
                    // Apply additional condition, if any
                    if ($fieldConfig['search']['andWhere'] ?? false) {
                        $searchConstraint = $searchConstraint->with(
                            QueryHelper::stripLogicalOperatorPrefix(QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), $fieldConfig['search']['andWhere']))
                        );
                    }
                }
                // Assemble the search condition only if the field makes sense to be searched
                if ($fieldType === 'input'
                    || $fieldType === 'text'
                    || $fieldType === 'flex'
                    || $fieldType === 'email'
                    || $fieldType === 'link'
                    || $fieldType === 'color'
                    || $fieldType === 'slug'
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

        return $queryBuilder->expr()->or(...$constraints);
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
        if ($this->getBackendUser()->isAdmin()) {
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

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
