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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Storage;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform as PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform as SQLServerPlatform;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Service\CacheService;

/**
 * A Storage backend
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Typo3DbBackend implements BackendInterface, SingletonInterface
{
    protected ConnectionPool $connectionPool;
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * Adds a row to the storage
     *
     * @param string $tableName The database table name
     * @param array $fieldValues The row to be inserted
     * @param bool $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
     * @return int The uid of the inserted row
     * @throws SqlErrorException
     */
    public function addRow(string $tableName, array $fieldValues, bool $isRelation = false): int
    {
        if (isset($fieldValues['uid'])) {
            unset($fieldValues['uid']);
        }
        try {
            $connection = $this->connectionPool->getConnectionForTable($tableName);

            $types = [];
            $platform = $connection->getDatabasePlatform();
            if ($platform instanceof SQLServerPlatform || $platform instanceof PostgreSQLPlatform) {
                // mssql and postgres needs to set proper PARAM_LOB and others to update fields.
                $tableDetails = $connection->createSchemaManager()->listTableDetails($tableName);
                foreach ($fieldValues as $columnName => $columnValue) {
                    $types[$columnName] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                }
            }

            $connection->insert($tableName, $fieldValues, $types);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230766, $e);
        }

        $uid = 0;
        if (!$isRelation) {
            // Relation tables have no auto_increment column, so no retrieval must be tried.
            $uid = (int)$connection->lastInsertId($tableName);
            $this->cacheService->clearCacheForRecord($tableName, $uid);
        }
        return $uid;
    }

    /**
     * Updates a row in the storage
     *
     * @param string $tableName The database table name
     * @param array $fieldValues The row to be updated
     * @param bool $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
     * @throws \InvalidArgumentException
     * @throws SqlErrorException
     */
    public function updateRow(string $tableName, array $fieldValues, bool $isRelation = false): void
    {
        if (!isset($fieldValues['uid'])) {
            throw new \InvalidArgumentException('The given row must contain a value for "uid".', 1476045164);
        }

        $uid = (int)$fieldValues['uid'];
        unset($fieldValues['uid']);

        try {
            $connection = $this->connectionPool->getConnectionForTable($tableName);

            $types = [];
            $platform = $connection->getDatabasePlatform();
            if ($platform instanceof SQLServerPlatform || $platform instanceof PostgreSQLPlatform) {
                // mssql and postgres needs to set proper PARAM_LOB and others to update fields.
                $tableDetails = $connection->createSchemaManager()->listTableDetails($tableName);
                foreach ($fieldValues as $columnName => $columnValue) {
                    $columnName = (string)$columnName;
                    $types[$columnName] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                }
            }

            $connection->update($tableName, $fieldValues, ['uid' => $uid], $types);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230767, $e);
        }

        if (!$isRelation) {
            $this->cacheService->clearCacheForRecord($tableName, $uid);
        }
    }

    /**
     * Updates a relation row in the storage.
     *
     * @param string $tableName The database relation table name
     * @param array $fieldValues The row to be updated
     * @throws SqlErrorException
     * @throws \InvalidArgumentException
     */
    public function updateRelationTableRow(string $tableName, array $fieldValues): void
    {
        if (!isset($fieldValues['uid_local']) && !isset($fieldValues['uid_foreign'])) {
            throw new \InvalidArgumentException(
                'The given fieldValues must contain a value for "uid_local" and "uid_foreign".',
                1360500126
            );
        }

        $where = [];
        $where['uid_local'] = (int)$fieldValues['uid_local'];
        $where['uid_foreign'] = (int)$fieldValues['uid_foreign'];
        unset($fieldValues['uid_local']);
        unset($fieldValues['uid_foreign']);

        if (!empty($fieldValues['tablenames'])) {
            $where['tablenames'] = $fieldValues['tablenames'];
            unset($fieldValues['tablenames']);
        }
        if (!empty($fieldValues['fieldname'])) {
            $where['fieldname'] = $fieldValues['fieldname'];
            unset($fieldValues['fieldname']);
        }

        try {
            $this->connectionPool->getConnectionForTable($tableName)->update($tableName, $fieldValues, $where);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230768, $e);
        }
    }

    /**
     * Deletes a row in the storage
     *
     * @param string $tableName The database table name
     * @param array $where An array of where array('fieldname' => value).
     * @param bool $isRelation TRUE if we are currently manipulating a relation table, FALSE by default
     * @throws SqlErrorException
     */
    public function removeRow(string $tableName, array $where, bool $isRelation = false): void
    {
        try {
            $this->connectionPool->getConnectionForTable($tableName)->delete($tableName, $where);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230769, $e);
        }

        if (!$isRelation && isset($where['uid'])) {
            $this->cacheService->clearCacheForRecord($tableName, (int)$where['uid']);
        }
    }

    /**
     * Returns the object data matching the $query.
     *
     * @param QueryInterface $query
     * @return array
     * @throws SqlErrorException
     */
    public function getObjectDataByQuery(QueryInterface $query): array
    {
        $statement = $query->getStatement();
        // todo: remove instanceof checks as soon as getStatement() strictly returns Qom\Statement only
        if ($statement instanceof Statement
            && !$statement->getStatement() instanceof QueryBuilder
        ) {
            $rows = $this->getObjectDataByRawQuery($statement);
        } else {
            $queryParser = GeneralUtility::makeInstance(Typo3DbQueryParser::class);
            if ($statement instanceof Statement
                && $statement->getStatement() instanceof QueryBuilder
            ) {
                $queryBuilder = $statement->getStatement();
            } else {
                $queryBuilder = $queryParser->convertQueryToDoctrineQueryBuilder($query);
            }
            $selectParts = $queryBuilder->getQueryPart('select');
            if ($queryParser->isDistinctQuerySuggested() && !empty($selectParts)) {
                $selectParts[0] = 'DISTINCT ' . $selectParts[0];
                $queryBuilder->selectLiteral(...$selectParts);
            }
            if ($query->getOffset()) {
                $queryBuilder->setFirstResult($query->getOffset());
            }
            if ($query->getLimit()) {
                $queryBuilder->setMaxResults($query->getLimit());
            }
            try {
                $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1472074485, $e);
            }
        }

        if (!empty($rows)) {
            $rows = $this->overlayLanguageAndWorkspace($query->getSource(), $rows, $query);
        }

        return $rows;
    }

    /**
     * Returns the object data using a custom statement
     *
     * @param Qom\Statement $statement
     * @return array
     * @throws SqlErrorException when the raw SQL statement fails in the database
     */
    protected function getObjectDataByRawQuery(Statement $statement): array
    {
        $realStatement = $statement->getStatement();
        $parameters = $statement->getBoundVariables();

        // The real statement is an instance of the Doctrine DBAL QueryBuilder, so fetching
        // this directly is possible
        if ($realStatement instanceof QueryBuilder) {
            try {
                $result = $realStatement->executeQuery();
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1472064721, $e);
            }
            $rows = $result->fetchAllAssociative();
        // Prepared Doctrine DBAL statement
        } elseif ($realStatement instanceof \Doctrine\DBAL\Statement) {
            try {
                $result = $realStatement->executeQuery($parameters);
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1481281404, $e);
            }
            $rows = $result->fetchAllAssociative();
        } else {
            // Do a real raw query. This is very stupid, as it does not allow to use DBAL's real power if
            // several tables are on different databases, so this is used with caution and could be removed
            // in the future
            try {
                $connection = $this->connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
                $statement = $connection->executeQuery($realStatement, $parameters);
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1472064775, $e);
            }

            $rows = $statement->fetchAllAssociative();
        }

        return $rows;
    }

    /**
     * Returns the number of tuples matching the query.
     *
     * @param QueryInterface $query
     * @return int The number of matching tuples
     * @throws BadConstraintException
     * @throws SqlErrorException
     */
    public function getObjectCountByQuery(QueryInterface $query): int
    {
        if ($query->getConstraint() instanceof Statement) {
            throw new BadConstraintException('Could not execute count on queries with a constraint of type TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Statement', 1256661045);
        }

        $statement = $query->getStatement();
        if ($statement instanceof Statement
            && !$statement->getStatement() instanceof QueryBuilder
        ) {
            $rows = $this->getObjectDataByQuery($query);
            $count = count($rows);
        } else {
            $queryParser  = GeneralUtility::makeInstance(Typo3DbQueryParser::class);
            $queryBuilder = $queryParser
                ->convertQueryToDoctrineQueryBuilder($query)
                ->resetQueryPart('orderBy');

            if ($queryParser->isDistinctQuerySuggested()) {
                $source = $queryBuilder->getQueryPart('from')[0];
                // Tablename is already quoted for the DBMS, we need to treat table and field names separately
                $tableName = $source['alias'] ?: $source['table'];
                $fieldName = $queryBuilder->quoteIdentifier('uid');
                $queryBuilder->resetQueryPart('groupBy')
                    ->selectLiteral(sprintf('COUNT(DISTINCT %s.%s)', $tableName, $fieldName));
            } else {
                $queryBuilder->count('*');
            }
            // Ensure to count only records in the current workspace
            $context = GeneralUtility::makeInstance(Context::class);
            $workspaceUid = (int)$context->getPropertyFromAspect('workspace', 'id');
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceUid));

            try {
                $count = $queryBuilder->executeQuery()->fetchOne();
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1472074379, $e);
            }
            if ($query->getOffset()) {
                $count -= $query->getOffset();
            }
            if ($query->getLimit()) {
                $count = min($count, $query->getLimit());
            }
        }
        return (int)max(0, $count);
    }

    /**
     * Checks if a Value Object equal to the given Object exists in the database
     *
     * @param AbstractValueObject $object The Value Object
     * @return int|null The matching uid if an object was found, else FALSE
     * @throws SqlErrorException
     */
    public function getUidOfAlreadyPersistedValueObject(AbstractValueObject $object): ?int
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $dataMap = $dataMapper->getDataMap(get_class($object));
        $tableName = $dataMap->getTableName();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }
        $whereClause = [];
        // loop over all properties of the object to exactly set the values of each database field
        $properties = $object->_getProperties();
        foreach ($properties as $propertyName => $propertyValue) {
            $propertyName = (string)$propertyName;

            // @todo We couple the Backend to the Entity implementation (uid, isClone); changes there breaks this method
            if ($dataMap->isPersistableProperty($propertyName) && $propertyName !== 'uid' && $propertyName !== 'pid' && $propertyName !== 'isClone') {
                $fieldName = $dataMap->getColumnMap($propertyName)->getColumnName();
                if ($propertyValue === null) {
                    $whereClause[] = $queryBuilder->expr()->isNull($fieldName);
                } else {
                    $whereClause[] = $queryBuilder->expr()->eq($fieldName, $queryBuilder->createNamedParameter($dataMapper->getPlainValue($propertyValue)));
                }
            }
        }
        $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->where(...$whereClause);

        try {
            $uid = (int)$queryBuilder
                ->executeQuery()
                ->fetchOne();
            if ($uid > 0) {
                return $uid;
            }
            return null;
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470231748, $e);
        }
    }

    /**
     * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
     * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
     *
     * @param Qom\SourceInterface $source The source (selector or join)
     * @param array $rows
     * @param QueryInterface $query
     * @param int|null $workspaceUid
     * @return array
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function overlayLanguageAndWorkspace(SourceInterface $source, array $rows, QueryInterface $query, int $workspaceUid = null): array
    {
        $context = GeneralUtility::makeInstance(Context::class);
        if ($workspaceUid === null) {
            $workspaceUid = (int)$context->getPropertyFromAspect('workspace', 'id');
        } else {
            // A custom query is needed, so a custom context is cloned
            $context = clone $context;
            $context->setAspect('workspace', GeneralUtility::makeInstance(WorkspaceAspect::class, $workspaceUid));
        }

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        if ($source instanceof SelectorInterface) {
            $tableName = $source->getSelectorName();
            $rows = $this->resolveMovedRecordsInWorkspace($tableName, $rows, $workspaceUid);
            return $this->overlayLanguageAndWorkspaceForSelect($tableName, $rows, $pageRepository, $query);
        }
        if ($source instanceof JoinInterface) {
            $tableName = $source->getRight()->getSelectorName();
            // Special handling of joined select is only needed when doing workspace overlays, which does not happen
            // in live workspace
            if ($workspaceUid === 0) {
                return $this->overlayLanguageAndWorkspaceForSelect($tableName, $rows, $pageRepository, $query);
            }
            return $this->overlayLanguageAndWorkspaceForJoinedSelect($tableName, $rows, $pageRepository, $query);
        }
        // No proper source, so we do not have a table name here
        // we cannot do an overlay and return the original rows instead.
        return $rows;
    }

    /**
     * If the result is a plain SELECT (no JOIN) then the regular overlay process works for tables
     *  - overlay workspace
     *  - overlay language of versioned record again
     */
    protected function overlayLanguageAndWorkspaceForSelect(string $tableName, array $rows, PageRepository $pageRepository, QueryInterface $query): array
    {
        $overlaidRows = [];
        foreach ($rows as $row) {
            $row = $this->overlayLanguageAndWorkspaceForSingleRecord($tableName, $row, $pageRepository, $query);
            if (is_array($row)) {
                $overlaidRows[] = $row;
            }
        }
        return $overlaidRows;
    }

    /**
     * If the result consists of a JOIN (usually happens if a property is a relation with a MM table) then it is necessary
     * to only do overlays for the fields that are contained in the main database table, otherwise a SQL error is thrown.
     * In order to make this happen, a single SQL query is made to fetch all possible field names (= array keys) of
     * a record (TCA[$tableName][columns] does not contain all needed information), which is then used to compute
     * a separate subset of the row which can be overlaid properly.
     */
    protected function overlayLanguageAndWorkspaceForJoinedSelect(string $tableName, array $rows, PageRepository $pageRepository, QueryInterface $query): array
    {
        // No valid rows, so this is skipped
        if (!isset($rows[0]['uid'])) {
            return $rows;
        }
        // First, find out the fields that belong to the "main" selected table which is defined by TCA, and take the first
        // record to find out all possible fields in this database table
        $fieldsOfMainTable = $pageRepository->getRawRecord($tableName, $rows[0]['uid']);
        $overlaidRows = [];
        if (is_array($fieldsOfMainTable)) {
            foreach ($rows as $row) {
                $mainRow = array_intersect_key($row, $fieldsOfMainTable);
                $joinRow = array_diff_key($row, $mainRow);
                $mainRow = $this->overlayLanguageAndWorkspaceForSingleRecord($tableName, $mainRow, $pageRepository, $query);
                if (is_array($mainRow)) {
                    $overlaidRows[] = array_replace($joinRow, $mainRow);
                }
            }
        }
        return $overlaidRows;
    }

    /**
     * Takes one specific row, as defined in TCA and does all overlays.
     *
     * @param string $tableName
     * @param array $row
     * @param PageRepository $pageRepository
     * @param QueryInterface $query
     * @return array|int|mixed|null the overlaid row or false or null if overlay failed.
     */
    protected function overlayLanguageAndWorkspaceForSingleRecord(string $tableName, array $row, PageRepository $pageRepository, QueryInterface $query)
    {
        $querySettings = $query->getQuerySettings();
        // If current row is a translation select its parent
        $languageOfCurrentRecord = 0;
        if (($GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? null)
            && ($row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']] ?? false)
        ) {
            $languageOfCurrentRecord = $row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']];
        }
        if ($querySettings->getLanguageOverlayMode()
            && $languageOfCurrentRecord > 0
            && isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
            && $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] > 0
        ) {
            $row = $pageRepository->getRawRecord(
                $tableName,
                (int)$row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']]
            );
        }
        // Handle workspace overlays
        $pageRepository->versionOL($tableName, $row, true, $querySettings->getIgnoreEnableFields());
        if (is_array($row) && $querySettings->getLanguageOverlayMode()) {
            if ($tableName === 'pages') {
                $row = $pageRepository->getPageOverlay($row, $querySettings->getLanguageUid());
            } else {
                // todo: remove type cast once getLanguageUid strictly returns an int
                $languageUid = (int)$querySettings->getLanguageUid();
                if (!$querySettings->getRespectSysLanguage()
                    && $languageOfCurrentRecord > 0
                    && (!$query instanceof Query || !$query->getParentQuery())
                ) {
                    // No parent query means we're processing the aggregate root.
                    // respectSysLanguage is false which means that records returned by the query
                    // might be from different languages (which is desired).
                    // So we must set the language used for overlay to the language of the current record
                    $languageUid = $languageOfCurrentRecord;
                }
                if (isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
                    && $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] > 0
                    && $languageOfCurrentRecord > 0
                ) {
                    // Force overlay by faking default language record, as getRecordOverlay can only handle default language records
                    $row['uid'] = $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']];
                    $row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']] = 0;
                }
                $row = $pageRepository->getRecordOverlay($tableName, $row, $languageUid, (string)$querySettings->getLanguageOverlayMode());
            }
        }
        return $row;
    }

    /**
     * Fetches the moved record in case it is supported
     * by the table and if there's only one row in the result set
     * (applying this to all rows does not work, since the sorting
     * order would be destroyed and possible limits are not met anymore)
     * The move pointers are later unset (see versionOL() last argument)
     */
    protected function resolveMovedRecordsInWorkspace(string $tableName, array $rows, int $workspaceUid): array
    {
        if ($workspaceUid === 0) {
            return $rows;
        }
        if (!BackendUtility::isTableWorkspaceEnabled($tableName)) {
            return $rows;
        }
        if (count($rows) !== 1) {
            return $rows;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $movedRecords = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('t3ver_state', $queryBuilder->createNamedParameter(VersionState::MOVE_POINTER, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter($workspaceUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($rows[0]['uid'], \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAllAssociative();
        if (!empty($movedRecords)) {
            $rows = $movedRecords;
        }
        return $rows;
    }
}
