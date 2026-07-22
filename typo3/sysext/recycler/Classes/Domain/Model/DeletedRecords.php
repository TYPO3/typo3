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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\Capability\LabelCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Each GeneralUtility::makeInstance() call must return a fresh
 * DeletedRecords instance so that loadData() and getTotalCount()
 * in the controller do not accumulate state across calls.
 *
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true, shared: false)]
class DeletedRecords
{
    /**
     * Array with all deleted rows
     */
    protected array $deletedRows = [];

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        #[Autowire(service: 'cache.runtime')]
        protected readonly FrontendInterface $runtimeCache,
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Load all deleted rows from $table.
     * If table is not set, it iterates the TCA tables.
     *
     * @param int $id UID from selected page
     * @param string $table Tablename
     * @param int $depth How many levels recursive
     * @param string $filter Filter text
     */
    public function loadData(int $id, string $table, int $depth, string $filter = ''): self
    {
        if ($table) {
            $schemata = $this->getRelevantSchemata();
            if (array_key_exists($table, $schemata)) {
                $this->setData($id, $schemata[$table], $depth, $filter);
            }
        } else {
            foreach ($this->getRelevantSchemata() as $schema) {
                $this->setData($id, $schema, $depth, $filter);
            }
        }
        return $this;
    }

    /**
     * Set all deleted rows
     *
     * @param int $id UID from record
     * @param int $depth How many levels recursive
     * @param string $filter Filter text
     */
    protected function setData(int $id, TcaSchema $schema, int $depth, string $filter): void
    {
        $deletedField = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
        if (!$deletedField) {
            return;
        }

        $queryBuilder = $this->getFilteredQueryBuilder($schema, $id, $depth, $filter);
        $queryBuilder = $queryBuilder->select('*')
            ->from($schema->getName())
            ->andWhere(
                $queryBuilder->expr()->eq(
                    $deletedField,
                    $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
                )
            );

        if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
            $queryBuilder = $queryBuilder
                ->orderBy($schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName(), 'desc')
                ->addOrderBy('uid');
        } else {
            $queryBuilder = $queryBuilder->orderBy('uid');
        }
        $recordsToCheck = $queryBuilder->executeQuery()->fetchAllAssociative();
        if ($recordsToCheck !== []) {
            $this->checkRecordAccess($schema->getName(), $recordsToCheck);
        }
    }

    /**
     * Helper method for setData() to create a QueryBuilder that filters the records by default.
     */
    protected function getFilteredQueryBuilder(TcaSchema $schema, int $pid, int $depth, string $filter): QueryBuilder
    {
        $table = $schema->getName();
        $pidList = $this->getTreeList($pid, $depth);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        // create the filter WHERE-clause
        $filterConstraint = null;
        if (trim($filter) !== '') {
            /** @var LabelCapability $labelCapability */
            $labelCapability = $schema->getCapability(TcaSchemaCapability::Label);
            $filterConstraint = $queryBuilder->expr()->comparison(
                $queryBuilder->castFieldToTextType($labelCapability->getPrimaryFieldName()),
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
    protected function checkRecordAccess(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            if ($this->checkAccess($table, $row)) {
                $this->setDeletedRows($table, $row);
            }
        }
    }

    /************************************************************
     * DELETE FUNCTIONS
     ************************************************************/
    /**
     * Delete element from any table
     *
     * @param list<string> $recordsArray Representation of the records as "table:uid" strings
     * @return int Number of records successfully deleted
     */
    public function deleteData(array $recordsArray): int
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->start([], []);
        $deletedCount = 0;
        foreach ($recordsArray as $record) {
            [$table, $uid] = explode(':', $record);
            $errorCountBefore = count($tce->errorLog);
            $tce->deleteAction($table, (int)$uid, false, true);
            if (count($tce->errorLog) === $errorCountBefore) {
                $deletedCount++;
            }
        }
        return $deletedCount;
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
    public function undeleteData(array $recordsArray, bool $recursive = false): bool|int
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
                $this->loadData($uid, '', $depth);
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
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        $deletedField = $this->tcaSchemaFactory->get('pages')->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();

        $record = $queryBuilder
            ->select('uid', 'pid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq($deletedField, 1)
            )
            ->executeQuery()
            ->fetchAssociative();
        if ($record) {
            $pages[] = $record['uid'];
            if ((int)$record['pid'] > 0) {
                $this->getDeletedParentPages($record['pid'], $pages);
            }
        }

        return $pages;
    }

    /**
     * @param array $row Deleted record row
     */
    protected function setDeletedRows(string $table, array $row): void
    {
        $this->deletedRows[$table][] = $row;
    }

    public function getDeletedRows(): array
    {
        return $this->deletedRows;
    }

    protected function getTreeList(int $id, int $depth): array
    {
        $identifier = md5($id . '_' . $depth);
        $pageTree = $this->runtimeCache->get($identifier);
        if ($pageTree === false) {
            $pageTree = $this->resolveTree($id, $depth, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
            $pageTree = array_merge([$id], $pageTree);
            $this->runtimeCache->set($identifier, $pageTree);
        }

        return $pageTree;
    }

    protected function resolveTree(int $id, int $depth, string $permsClause): array
    {
        $id = abs($id);
        $theList = [];
        if ($depth > 0) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
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
                $theList[] = $row['uid'];
                if ($depth > 1) {
                    $theList = array_merge($theList, $this->resolveTree($row['uid'], $depth - 1, $permsClause));
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
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
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

        // Checking if the user has permissions? (Only working as a precaution, because the final permission check
        // is always down in TCE. But it's good to notify the user on beforehand...)
        // First, resetting flags.
        $hasAccess = false;
        $calcPRec = $row;
        BackendUtility::workspaceOL($table, $calcPRec, $backendUser->workspace);
        if (is_array($calcPRec)) {
            if ($table === 'pages') {
                $calculatedPermissions = new Permission($backendUser->calcPerms($calcPRec, false));
                $hasAccess = $calculatedPermissions->editPagePermissionIsGranted();
            } else {
                $rec = BackendUtility::getRecord('pages', $calcPRec['pid'], '*', '', false);
                $calculatedPermissions = new Permission($backendUser->calcPerms($rec, false));
                // Fetching pid-record first.
                $hasAccess = $calculatedPermissions->editContentPermissionIsGranted();
            }
            // Check internals regarding access:
            if ($hasAccess) {
                $hasAccess = $backendUser->checkRecordEditAccess($table, $calcPRec)->isAllowed;
            }
        }
        return $hasAccess;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the modifiable tables of the current user, which have a SoftDelete field.
     *
     * @return TcaSchema[]
     */
    protected function getRelevantSchemata(): array
    {
        $schemata = [];
        $tables = explode(',', $this->getBackendUser()->groupData['tables_modify']);
        foreach ($this->tcaSchemaFactory->all() as $name => $schema) {
            if (!$schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                continue;
            }
            if ($this->getBackendUser()->isAdmin()) {
                $schemata[$name] = $schema;
                continue;
            }
            if (in_array($name, $tables, true)) {
                $schemata[$name] = $schema;
            }
        }
        return $schemata;
    }

}
