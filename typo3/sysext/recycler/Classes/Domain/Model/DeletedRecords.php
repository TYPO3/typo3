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

namespace TYPO3\CMS\Recycler\Domain\Model;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Model class for the 'recycler' extension.
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
class DeletedRecords
{
    /**
     * Array with all deleted rows
     */
    protected array $deletedRows = [];

    /**
     * String with the global limit
     */
    protected string $limit = '';

    /**
     * Array with all available tables
     */
    protected array $table = [];

    /************************************************************
     * GET DATA FUNCTIONS
     *
     *
     ************************************************************/
    /**
     * Load all deleted rows from $table
     * If table is not set, it iterates the TCA tables
     *
     * @param int $id UID from selected page
     * @param string $table Tablename
     * @param int $depth How many levels recursive
     * @param string $limit MySQL LIMIT
     * @param string $filter Filter text
     */
    public function loadData($id, $table, $depth, $limit = '', $filter = ''): self
    {
        // set the limit
        $this->limit = trim($limit);
        if ($table) {
            if (in_array($table, $this->getModifyableTables(), true)) {
                $this->table[] = $table;
                $this->setData($id, $table, $depth, $filter);
            }
        } else {
            foreach ($this->getModifyableTables() as $tableKey) {
                // only go into this table if the limit allows it
                if ($this->limit !== '') {
                    $parts = GeneralUtility::intExplode(',', $this->limit, true);
                    // abort loop if LIMIT 0,0
                    if ($parts[0] === 0 && $parts[1] === 0) {
                        break;
                    }
                }
                $this->table[] = $tableKey;
                $this->setData($id, $tableKey, $depth, $filter);
            }
        }
        return $this;
    }

    /**
     * Find the total count of deleted records
     *
     * @param int $id UID from record
     * @param string $table Tablename from record
     * @param int $depth How many levels recursive
     * @param string $filter Filter text
     * @return int
     */
    public function getTotalCount($id, $table, $depth, $filter): int
    {
        $deletedRecords = $this->loadData($id, $table, $depth, '', $filter)->getDeletedRows();
        $countTotal = 0;
        foreach ($this->table as $tableName) {
            $countTotal += count($deletedRecords[$tableName] ?? []);
        }
        return $countTotal;
    }

    /**
     * Set all deleted rows
     *
     * @param int $id UID from record
     * @param string $table Tablename from record
     * @param int $depth How many levels recursive
     * @param string $filter Filter text
     */
    protected function setData($id, $table, $depth, $filter)
    {
        $deletedField = $this->getDeletedField($table);
        if (!$deletedField) {
            return;
        }

        $id = (int)$id;
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
        $firstResult = 0;
        $maxResults = 0;

        // get the limit
        if (!empty($this->limit)) {
            // count the number of deleted records for this pid
            $queryBuilder = $this->getFilteredQueryBuilder($table, $id, $depth, $filter);

            $deletedCount = (int)$queryBuilder
                ->count('*')
                ->from($table)
                ->andWhere(
                    $queryBuilder->expr()->neq(
                        $deletedField,
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchOne();

            // split the limit
            [$offset, $rowCount] = GeneralUtility::intExplode(',', $this->limit, true);
            // subtract the number of deleted records from the limit's offset
            $result = $offset - $deletedCount;
            // if the result is >= 0
            if ($result >= 0) {
                // store the new offset in the limit and go into the next depth
                $offset = $result;
                $this->limit = implode(',', [$offset, $rowCount]);
                // do NOT query this depth; limit also does not need to be set, we set it anyways
                $allowQuery = false;
            } else {
                // the offset for the temporary limit has to remain like the original offset
                // in case the original offset was just crossed by the amount of deleted records
                $tempOffset = 0;
                if ($offset !== 0) {
                    $tempOffset = $offset;
                }
                // set the offset in the limit to 0
                $newOffset = 0;
                // convert to negative result to the positive equivalent
                $absResult = abs($result);
                // if the result now is > limit's row count
                if ($absResult > $rowCount) {
                    // use the limit's row count as the temporary limit
                    $firstResult = $tempOffset;
                    $maxResults = $rowCount;
                    // set the limit's row count to 0
                    $this->limit = implode(',', [$newOffset, 0]);
                } else {
                    // if the result now is <= limit's row count
                    // use the result as the temporary limit
                    $firstResult = $tempOffset;
                    $maxResults = $absResult;
                    // subtract the result from the row count
                    $newCount = $rowCount - $absResult;
                    // store the new result in the limit's row count
                    $this->limit = implode(',', [$newOffset, $newCount]);
                }
                // allow query for this depth
                $allowQuery = true;
            }
        } else {
            $allowQuery = true;
        }
        // query for actual deleted records
        if ($allowQuery) {
            $queryBuilder = $this->getFilteredQueryBuilder($table, $id, $depth, $filter);
            if ($firstResult) {
                $queryBuilder->setFirstResult($firstResult);
            }
            if ($maxResults) {
                $queryBuilder->setMaxResults($maxResults);
            }
            $queryBuilder = $queryBuilder->select('*')
                ->from($table)
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        $deletedField,
                        $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
                    )
                );
            if ($GLOBALS['TCA'][$table]['ctrl']['tstamp'] ?? false) {
                $queryBuilder = $queryBuilder
                    ->orderBy($GLOBALS['TCA'][$table]['ctrl']['tstamp'], 'desc')
                    ->addOrderBy('uid');
            } else {
                $queryBuilder = $queryBuilder->orderBy('uid');
            }
            $recordsToCheck = $queryBuilder->executeQuery()->fetchAllAssociative();
            if ($recordsToCheck !== []) {
                $this->checkRecordAccess($table, $recordsToCheck);
            }
        }
    }

    /**
     * Helper method for setData() to create a QueryBuilder that filters the records by default.
     */
    protected function getFilteredQueryBuilder(string $table, int $pid, int $depth, string $filter): QueryBuilder
    {
        $pidList = $this->getTreeList($pid, $depth);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        // create the filter WHERE-clause
        $filterConstraint = null;
        if (trim($filter) !== '') {
            $filterConstraint = $queryBuilder->expr()->comparison(
                $queryBuilder->castFieldToTextType($GLOBALS['TCA'][$table]['ctrl']['label']),
                'LIKE',
                $queryBuilder->createNamedParameter(
                    '%' . $queryBuilder->escapeLikeWildcards($filter) . '%'
                )
            );
            if (MathUtility::canBeInterpretedAsInteger($filter)) {
                $filterConstraint = $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($filter, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($filter, Connection::PARAM_INT)
                    ),
                    $filterConstraint
                );
            }
        }

        $maxBindParameters = PlatformInformation::getMaxBindParameters($queryBuilder->getConnection()->getDatabasePlatform());
        $pidConstraints = [];
        foreach (array_chunk($pidList, $maxBindParameters - 10) as $chunk) {
            $pidConstraints[] = $queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
            );
        }
        $queryBuilder->where(
            $queryBuilder->expr()->and(
                $filterConstraint,
                $queryBuilder->expr()->or(...$pidConstraints)
            )
        );

        return $queryBuilder;
    }

    /**
     * Checks whether the current backend user has access to the given records.
     *
     * @param string $table Name of the table
     * @param array $rows Record row
     */
    protected function checkRecordAccess(string $table, array $rows)
    {
        $deleteField = '';
        if ($table === 'pages') {
            // The "checkAccess" method validates access to the passed table/rows. When access to
            // a page record gets validated it is necessary to disable the "delete" field temporarily
            // for the recycler.
            // Else it wouldn't be possible to perform the check as many methods of BackendUtility
            // like "BEgetRootLine", etc. will only work on non-deleted records.
            $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'];
            unset($GLOBALS['TCA'][$table]['ctrl']['delete']);
        }

        foreach ($rows as $row) {
            if ($this->checkAccess($table, $row)) {
                $this->setDeletedRows($table, $row);
            }
        }

        if ($table === 'pages') {
            $GLOBALS['TCA'][$table]['ctrl']['delete'] = $deleteField;
        }
    }

    /************************************************************
     * DELETE FUNCTIONS
     ************************************************************/
    /**
     * Delete element from any table
     *
     * @param array|null $recordsArray Representation of the records
     */
    public function deleteData(?array $recordsArray): bool
    {
        if (is_array($recordsArray)) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], []);
            $tce->disableDeleteClause();
            foreach ($recordsArray as $record) {
                [$table, $uid] = explode(':', $record);
                $tce->deleteEl($table, (int)$uid, true, true);
            }
            return true;
        }
        return false;
    }

    /************************************************************
     * UNDELETE FUNCTIONS
     ************************************************************/
    /**
     * Undelete records
     * If $recursive is TRUE all records below the page uid would be undelete too
     *
     * @param array $recordsArray Representation of the records
     * @param bool $recursive Whether to recursively undelete
     * @return bool|int
     */
    public function undeleteData(array $recordsArray, bool $recursive = false)
    {
        $result = false;
        $affectedRecords = 0;
        $depth = 999;
        $this->deletedRows = [];
        $cmd = [];
        foreach ($recordsArray as $record) {
            [$table, $uid] = explode(':', $record);
            $uid = (int)$uid;
            // get all parent pages and cover them
            $pid = $this->getPidOfUid($uid, $table);
            if ($pid > 0) {
                $parentUidsToRecover = $this->getDeletedParentPages($pid);
                $count = count($parentUidsToRecover);
                for ($i = 0; $i < $count; ++$i) {
                    $parentUid = $parentUidsToRecover[$i];
                    $cmd['pages'][$parentUid]['undelete'] = 1;
                    $affectedRecords++;
                }
                if (isset($cmd['pages'])) {
                    // reverse the page list to recover it from top to bottom
                    $cmd['pages'] = array_reverse($cmd['pages'], true);
                }
            }
            $cmd[$table][$uid]['undelete'] = 1;
            $affectedRecords++;
            if ($table === 'pages' && $recursive) {
                $this->loadData($uid, '', $depth, '');
                $childRecords = $this->getDeletedRows();
                if (!empty($childRecords)) {
                    foreach ($childRecords as $childTable => $childRows) {
                        foreach ($childRows as $childRow) {
                            $cmd[$childTable][$childRow['uid']]['undelete'] = 1;
                        }
                    }
                }
            }
        }
        if ($cmd) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], $cmd);
            $tce->process_cmdmap();
            $result = $affectedRecords;
        }
        return $result;
    }

    /**
     * Returns deleted parent pages
     */
    protected function getDeletedParentPages(int $uid, array &$pages = []): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $record = $queryBuilder
            ->select('uid', 'pid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq($GLOBALS['TCA']['pages']['ctrl']['delete'], 1)
            )
            ->executeQuery()
            ->fetchAssociative();
        if ($record) {
            $pages[] = $record['uid'];
            if ((int)$record['pid'] !== 0) {
                $this->getDeletedParentPages($record['pid'], $pages);
            }
        }

        return $pages;
    }

    /************************************************************
     * SETTER FUNCTIONS
     ************************************************************/
    /**
     * Set deleted rows
     *
     * @param array $row Deleted record row
     */
    public function setDeletedRows(string $table, array $row): void
    {
        $this->deletedRows[$table][] = $row;
    }

    /************************************************************
     * GETTER FUNCTIONS
     ************************************************************/
    /**
     * Get deleted Rows
     */
    public function getDeletedRows(): array
    {
        return $this->deletedRows;
    }

    /**
     * Get tables
     */
    public function getTable(): array
    {
        return $this->table;
    }

    /**
     * Get tree list
     */
    protected function getTreeList(int $id, int $depth, int $begin = 0): array
    {
        $cache = $this->getCache();
        $identifier = md5($id . '_' . $depth . '_' . $begin);
        $pageTree = $cache->get($identifier);
        if ($pageTree === false) {
            $pageTree = $this->resolveTree($id, $depth, $begin, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
            $cache->set($identifier, $pageTree);
        }

        return $pageTree;
    }

    protected function resolveTree(int $id, int $depth, int $begin = 0, string $permsClause = ''): array
    {
        $id = abs($id);
        $theList = [];
        if ($begin === 0) {
            $theList[] = $id;
        }
        if ($depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
            $statement = $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                    QueryHelper::stripLogicalOperatorPrefix($permsClause)
                )
                ->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList[] = $row['uid'];
                }
                if ($depth > 1) {
                    $theList = array_merge($theList, $this->resolveTree($row['uid'], $depth - 1, $begin - 1, $permsClause));
                }
            }
        }
        return $theList;
    }

    /**
     * Get pid of uid
     */
    protected function getPidOfUid(int $uid, string $table): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $pid = $queryBuilder
            ->select('pid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        return (int)$pid;
    }
    /**
     * Checks the page access rights (Code for access check mostly taken from FormEngine)
     * as well as the table access rights of the user.
     *
     * @param string $table The table to check access for
     * @param array $row Record array
     * @return bool Returns TRUE is the user has access, or FALSE if not
     */
    protected function checkAccess(string $table, array $row): bool
    {
        $backendUser = $this->getBackendUser();

        if ($backendUser->isAdmin()) {
            return true;
        }

        if (!$backendUser->check('tables_modify', $table)) {
            return false;
        }

        // Checking if the user has permissions? (Only working as a precaution, because the final permission check is always down in TCE. But it's good to notify the user on beforehand...)
        // First, resetting flags.
        $hasAccess = false;
        $calcPRec = $row;
        BackendUtility::workspaceOL($table, $calcPRec, $backendUser->workspace);
        if (is_array($calcPRec)) {
            if ($table === 'pages') {
                $calculatedPermissions = new Permission($backendUser->calcPerms($calcPRec));
                $hasAccess = $calculatedPermissions->editPagePermissionIsGranted();
            } else {
                $calculatedPermissions = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $calcPRec['pid'])));
                // Fetching pid-record first.
                $hasAccess = $calculatedPermissions->editContentPermissionIsGranted();
            }
            // Check internals regarding access:
            if ($hasAccess) {
                $hasAccess = $backendUser->recordEditAccessInternals($table, $calcPRec);
            }
        }
        return $hasAccess;
    }

    /**
     * Gets an instance of the memory cache.
     */
    protected function getCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Gets the name of the field with the information whether a record is deleted.
     *
     * @param string $tableName Name of the table to get the deleted field for
     * @return string Name of the field with the information whether a record is deleted
     */
    protected function getDeletedField(string $tableName): string
    {
        $tcaForTable = $GLOBALS['TCA'][$tableName] ?? false;
        if ($tcaForTable && !empty($tcaForTable['ctrl']['delete'])) {
            return $tcaForTable['ctrl']['delete'];
        }
        return '';
    }

    /**
     * Returns the modifiable tables of the current user
     */
    protected function getModifyableTables(): array
    {
        if ($this->getBackendUser()->isAdmin()) {
            $tables = array_keys($GLOBALS['TCA']);
        } else {
            $tables = explode(',', $this->getBackendUser()->groupData['tables_modify']);
            // Only find those that are in use
            $tables = array_intersect($tables, array_keys($GLOBALS['TCA']));
        }
        return $tables;
    }
}
