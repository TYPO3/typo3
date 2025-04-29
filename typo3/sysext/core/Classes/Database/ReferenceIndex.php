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

namespace TYPO3\CMS\Core\Database;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\ProgressListenerInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reference index processing and relation extraction.
 *
 * @internal Extensions shouldn't fiddle with the reference index themselves, it's task of DataHandler to do this.
 */
#[Autoconfigure(public: true)]
class ReferenceIndex
{
    /**
     * Increase if a change in the code means we will have to force a re-generation of the index.
     * MUST be int since xxhash ignores non-int seeds, see https://github.com/php/php-src/issues/10305
     *
     * @var positive-int
     */
    private const HASH_VERSION = 1;

    /**
     * Key list of tables to exclude from ReferenceIndex. Only $GLOBALS['TCA'] need to be listed here, if at all.
     * This is a performance improvement to skip tables "irrelevant" in refindex scope.
     * An event may alter this.
     * The list depends on TCA and an event, entries are managed once per class instance.
     * Array with fields as keys and booleans as values for fast isset() lookup instead of slower in_array().
     */
    private array $excludedTables = [];

    /**
     * A list of fields that may contain relations per TCA table.
     * The list depends on TCA, entries are created once per table, per class instance.
     */
    private array $tableRelationFieldCache = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SoftReferenceParserFactory $softReferenceParserFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly Registry $registry,
        private readonly FlexFormTools $flexFormTools,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Returns the amount of references for the given record.
     */
    public function getNumberOfReferencedRecords(string $tableName, int $uid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        return (int)$queryBuilder
            ->count('*')->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter($tableName)),
                $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )->executeQuery()->fetchOne();
    }

    /**
     * Update full refindex. Used by 'referenceindex:update' CLI and ext:lowlevel BE UI.
     *
     * @param ProgressListenerInterface|null $progressListener If set, the current progress is added to the listener
     * @return array Header and body status content
     * @internal
     */
    public function updateIndex(bool $testOnly, ?ProgressListenerInterface $progressListener = null): array
    {
        $errors = [];
        $numberOfHandledRecords = 0;

        $isWorkspacesLoaded = ExtensionManagementUtility::isLoaded('workspaces');
        $tcaTableNames = $this->tcaSchemaFactory->all()->getNames();
        // @todo: Ensure tcaSchemaFactory->all() always sorts alphabetically (or add test to verify), then remove this sort()
        sort($tcaTableNames);

        $progressListener?->log('Remember to create missing tables and columns before running this.', LogLevel::WARNING);

        // Remove dangling workspace sys_refindex rows
        $listOfActiveWorkspaces = $this->getListOfActiveWorkspaces();
        $numberOfUnusedWorkspaceRows = $testOnly
            ? $this->getNumberOfUnusedWorkspaceRowsInReferenceIndex($listOfActiveWorkspaces)
            : $this->removeUnusedWorkspaceRowsFromReferenceIndex($listOfActiveWorkspaces);
        if ($numberOfUnusedWorkspaceRows > 0) {
            $error = 'Index table hosted ' . $numberOfUnusedWorkspaceRows . ' indexes for non-existing or deleted workspaces, now removed.';
            $errors[] = $error;
            $progressListener?->log($error, LogLevel::WARNING);
        }

        // Remove sys_refindex rows of tables no longer defined in TCA
        $numberOfRowsOfOldTables = $testOnly
            ? $this->getNumberOfUnusedTablesInReferenceIndex($tcaTableNames)
            : $this->removeReferenceIndexDataFromUnusedDatabaseTables($tcaTableNames);
        if ($numberOfRowsOfOldTables > 0) {
            $error = 'Index table hosted ' . $numberOfRowsOfOldTables . ' indexes for non-existing tables, now removed';
            $errors[] = $error;
            $progressListener?->log($error, LogLevel::WARNING);
        }

        // Main loop traverses all records of all TCA tables
        foreach ($tcaTableNames as $tableName) {
            $tableTcaSchema = $this->tcaSchemaFactory->get($tableName);

            // Count number of records in table to have a correct $numberOfHandledRecords in the end
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $numberOfRecordsInTargetTable = $queryBuilder
                ->count('uid')
                ->from($tableName)
                ->executeQuery()
                ->fetchOne();

            $progressListener?->start($numberOfRecordsInTargetTable, $tableName);

            if ($numberOfRecordsInTargetTable === 0 || $this->shouldExcludeTableFromReferenceIndex($tableName) || empty($this->getTableRelationFields($tableName))) {
                // Table is empty, should be excluded, or can not have relations. Blindly remove any existing sys_refindex rows.
                $numberOfHandledRecords += $numberOfRecordsInTargetTable;
                $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
                $queryBuilder->getRestrictions()->removeAll();
                if ($testOnly) {
                    $countDeleted = $queryBuilder
                        ->count('hash')
                        ->from('sys_refindex')
                        ->where($queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)))
                        ->executeQuery()
                        ->fetchOne();
                } else {
                    $countDeleted = $queryBuilder
                        ->delete('sys_refindex')
                        ->where($queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)))
                        ->executeStatement();
                }
                if ($countDeleted > 0) {
                    $error = 'Index table hosted ' . $countDeleted . ' ignored or outdated indexed, now removed.';
                    $errors[] = $error;
                    $progressListener?->log($error, LogLevel::WARNING);
                }
                $progressListener?->finish();
                continue;
            }

            // Delete lost indexes of table: sys_refindex rows where the uid no longer exists in target table.
            $subQueryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $subQueryBuilder->getRestrictions()->removeAll();
            $subQueryBuilder
                ->select('uid')
                ->from($tableName, 'sub_' . $tableName)
                ->where(
                    $subQueryBuilder->expr()->eq('sub_' . $tableName . '.uid', $subQueryBuilder->quoteIdentifier('sys_refindex.recuid'))
                );
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
            $queryBuilder->getRestrictions()->removeAll();
            if ($testOnly) {
                $numberOfRefindexRowsWithoutExistingTableRow = $queryBuilder
                    ->count('hash')
                    ->from('sys_refindex')
                    ->where(
                        $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)),
                        'NOT EXISTS (' . $subQueryBuilder->getSQL() . ')'
                    )
                    ->executeQuery()
                    ->fetchOne();
            } else {
                $numberOfRefindexRowsWithoutExistingTableRow = $queryBuilder
                    ->delete('sys_refindex')
                    ->where(
                        $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)),
                        'NOT EXISTS (' . $subQueryBuilder->getSQL() . ')'
                    )
                    ->executeStatement();
            }
            if ($numberOfRefindexRowsWithoutExistingTableRow > 0) {
                $error = 'Table ' . $tableName . ' hosted ' . $numberOfRefindexRowsWithoutExistingTableRow . ' lost indexes, now removed.';
                $errors[] = $error;
                $progressListener?->log($error, LogLevel::WARNING);
            }

            // Delete rows in sys_refindex related to this table where the record is soft-deleted=1.
            if ($tableTcaSchema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                $softDeleteFieldName = $tableTcaSchema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
                $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
                $queryBuilder->getRestrictions()->removeAll();
                $numberOfDeletedRecordsInTargetTable = $queryBuilder
                    ->count('uid')
                    ->from($tableName)
                    ->where($queryBuilder->expr()->eq($softDeleteFieldName, 1))
                    ->executeQuery()
                    ->fetchOne();
                if ($numberOfDeletedRecordsInTargetTable > 0) {
                    $numberOfHandledRecords += $numberOfDeletedRecordsInTargetTable;
                    // List of deleted=0 records in target table that have records in sys_refindex.
                    if ($testOnly) {
                        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
                        $queryBuilder->getRestrictions()->removeAll();
                        // $subQueryBuilder actually fills parameter placeholders for the main $queryBuilder.
                        // The subQuery is never meant to be executed on its own, only used to be filled-in
                        // via $subQueryBuilder->getSQL().
                        $subQueryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
                        $subQueryBuilder->getRestrictions()->removeAll();
                        $subQueryBuilder
                            ->select('sub_' . $tableName . '.uid')
                            ->distinct()
                            ->from($tableName, 'sub_' . $tableName)
                            ->join(
                                'sub_' . $tableName,
                                'sys_refindex',
                                'sub_refindex',
                                $queryBuilder->expr()->eq('sub_refindex.recuid', $queryBuilder->quoteIdentifier('sub_' . $tableName . '.uid'))
                            )
                            ->where(
                                $queryBuilder->expr()->eq('sub_refindex.tablename', $queryBuilder->createNamedParameter($tableName)),
                                $queryBuilder->expr()->eq('sub_' . $tableName . '.' . $softDeleteFieldName, 1),
                            );
                        $numberOfRemovedIndexes = $queryBuilder
                            ->count('hash')
                            ->from('sys_refindex')
                            ->where(
                                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)),
                                $queryBuilder->quoteIdentifier('recuid') . ' IN ( ' . $subQueryBuilder->getSQL() . ' )'
                            )
                            ->executeQuery()
                            ->fetchOne();
                    } else {
                        // MySQL is picky when using the same table in a sub-query and an outer delete query, if
                        // it is not materialized into a temporary table. Enforcing a temporary table would mitigate this
                        // MySQL limit, but we simply fetch the affected uid list instead and fire a chunked delete query.
                        // In contrast to $testOnly above, we execute the subQuery, named parameter placeholders need
                        // to be relative to its QueryBuilder.
                        $uidListQueryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
                        $uidListQueryBuilder->getRestrictions()->removeAll();
                        $uidListQueryBuilder
                            ->select('sub_' . $tableName . '.uid')
                            ->distinct()
                            ->from($tableName, 'sub_' . $tableName)
                            ->join(
                                'sub_' . $tableName,
                                'sys_refindex',
                                'sub_refindex',
                                $uidListQueryBuilder->expr()->eq('sub_refindex.recuid', $uidListQueryBuilder->quoteIdentifier('sub_' . $tableName . '.uid'))
                            )
                            ->where(
                                $uidListQueryBuilder->expr()->eq('sub_refindex.tablename', $uidListQueryBuilder->createNamedParameter($tableName)),
                                $uidListQueryBuilder->expr()->eq('sub_' . $tableName . '.' . $softDeleteFieldName, 1),
                            );
                        $uidListOfRemovableIndexes = $uidListQueryBuilder->executeQuery()->fetchFirstColumn();
                        $numberOfRemovedIndexes = 0;
                        // Another variant to solve this would be a limit/offset query for the upper query, feeding delete.
                        // This would be more memory efficient. We however think there shouldn't be *that* many affected
                        // rows to delete in casual scenarios, so we skip that optimization for now since chunking isn't
                        // needed in most cases anyway.
                        // 10k is an arbitrary number. Reasoning: 1MB max query length with 10-char uids (9mio uid-range with comma)
                        // would allow ~10k uids. Combi tablename/recuid is indexed, so delete should be relatively quick even with
                        // larger sets, so delete-hard-locking on for instance innodb shouldn't be a huge issue here.
                        foreach (array_chunk($uidListOfRemovableIndexes, 10000) as $uidChunkOfRemovableIndexes) {
                            $chunkQueryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
                            $chunkQueryBuilder->getRestrictions()->removeAll();
                            $chunkedNumberOfRemovedIndexes = $chunkQueryBuilder
                                ->delete('sys_refindex')
                                ->where(
                                    $chunkQueryBuilder->expr()->eq('tablename', $chunkQueryBuilder->createNamedParameter($tableName)),
                                    $chunkQueryBuilder->expr()->in('recuid', $uidChunkOfRemovableIndexes)
                                )
                                ->executeStatement();
                            $numberOfRemovedIndexes += $chunkedNumberOfRemovedIndexes;
                        }
                    }
                    if ($numberOfRemovedIndexes > 0) {
                        $error = 'Table ' . $tableName . ' hosted ' . $numberOfRemovedIndexes . ' indexes from soft-deleted records, now removed.';
                        $errors[] = $error;
                        $progressListener?->log($error, LogLevel::WARNING);
                    }
                    $progressListener?->advance($numberOfDeletedRecordsInTargetTable);
                }
            }

            // Some additional magic is needed if the table has a field that is the local side of
            // a mm relation. See the variable usage below for details.
            $tableHasLocalSideMmRelation = false;
            foreach ($tableTcaSchema->getFields() as $field) {
                $fieldConfig = $field->getConfiguration();
                if (!empty($fieldConfig['MM'] ?? '')
                    // Catch type=group 'allowed' and type=select 'foreign_table' MM scenarios
                    && (!empty($fieldConfig['allowed'] ?? '') || !empty($fieldConfig['foreign_table'] ?? ''))
                    && empty($fieldConfig['MM_opposite_field'] ?? '')
                ) {
                    $tableHasLocalSideMmRelation = true;
                }
            }

            // Traverse all records in table, not including soft-deleted records
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryResult = $queryBuilder
                ->select('*')
                ->from($tableName)
                ->orderBy('uid')
                ->executeQuery();
            while ($record = $queryResult->fetchAssociative()) {
                $progressListener?->advance();
                if ($isWorkspacesLoaded && $tableHasLocalSideMmRelation && (int)($record['t3ver_wsid'] ?? 0) === 0) {
                    // If we have a record that can be the local side of a workspace relation, workspace records
                    // may point to it, even though the record has no workspace overlay. See workspace ManyToMany
                    // Modify addCategoryRelation as example. In those cases, we need to iterate all active workspaces
                    // and update refindex for all foreign workspace records that point to it.
                    foreach ($listOfActiveWorkspaces as $workspaceId) {
                        $result = $this->updateRefIndexTable($tableName, (int)$record['uid'], $testOnly, $workspaceId, $record);
                        $numberOfHandledRecords++;
                        if ($result['addedNodes'] || $result['deletedNodes']) {
                            $error = 'Record ' . $tableName . ':' . $record['uid'] . ' had ' . $result['addedNodes'] . ' added indexes and ' . $result['deletedNodes'] . ' deleted indexes';
                            $errors[] = $error;
                            $progressListener?->log($error, LogLevel::WARNING);
                        }
                    }
                } else {
                    $result = $this->updateRefIndexTable($tableName, (int)$record['uid'], $testOnly, (int)($record['t3ver_wsid'] ?? 0), $record);
                    $numberOfHandledRecords++;
                    if ($result['addedNodes'] || $result['deletedNodes']) {
                        $error = 'Record ' . $tableName . ':' . $record['uid'] . ' had ' . $result['addedNodes'] . ' added indexes and ' . $result['deletedNodes'] . ' deleted indexes';
                        $errors[] = $error;
                        $progressListener?->log($error, LogLevel::WARNING);
                    }
                }
            }
            $progressListener?->finish();
        }

        $errorCount = count($errors);
        $recordsCheckedString = $numberOfHandledRecords . ' records from ' . count($tcaTableNames) . ' tables were checked/updated.';
        if ($errorCount) {
            $progressListener?->log($recordsCheckedString . ' Updates: ' . $errorCount, LogLevel::WARNING);
        } else {
            $progressListener?->log($recordsCheckedString . ' Index Integrity was perfect!');
        }
        if (!$testOnly) {
            $this->registry->set('core', 'sys_refindex_lastUpdate', $GLOBALS['EXEC_TIME']);
        }
        return ['resultText' => trim($recordsCheckedString), 'errors' => $errors];
    }

    /**
     * Update the sys_refindex table for a record, even one just deleted.
     * This is used by DataHandler ReferenceIndexUpdater as entry method to take care of single records.
     * It is also used internally via updateIndex() by CLI "referenceindex:update" and lowlevel BE module.
     *
     * @param array|null $currentRecord Current full (select *) record from DB. Optimization for updateIndex().
     * @return array Statistics about how many index records were added, deleted and not altered.
     * @internal
     */
    public function updateRefIndexTable(string $tableName, int $uid, bool $testOnly = false, int $workspaceUid = 0, ?array $currentRecord = null): array
    {
        $result = [
            'keptNodes' => 0,
            'deletedNodes' => 0,
            'addedNodes' => 0,
        ];
        if ($uid < 1 || $this->shouldExcludeTableFromReferenceIndex($tableName) || empty($this->getTableRelationFields($tableName))) {
            // Not a valid uid, the table is excluded, or can not contain relations.
            return $result;
        }
        if ($currentRecord === null) {
            // Fetch record if not provided.
            $currentRecord = BackendUtility::getRecord($tableName, $uid);
        }
        $currentRelationHashes = $this->getCurrentRelationHashes($tableName, $uid, $workspaceUid);
        if ($currentRecord === null) {
            // If there is no record because it was hard or soft-deleted, remove any existing sys_refindex rows of it.
            $numberOfLeftOverRelationHashes = count($currentRelationHashes);
            $result['deletedNodes'] = $numberOfLeftOverRelationHashes;
            if ($numberOfLeftOverRelationHashes > 0 && !$testOnly) {
                $this->removeRelationHashes($currentRelationHashes);
            }
            return $result;
        }

        $relations = $this->compileReferenceIndexRowsForRecord($tableName, $currentRecord, $workspaceUid);
        $connection = $this->connectionPool->getConnectionForTable('sys_refindex');
        $relationsToInsert = [];
        foreach ($relations as $relation) {
            if (!is_array($relation)) {
                continue;
            }
            // Exclude any relations TO a specific table
            if (($relation['ref_table'] ?? '') && $this->shouldExcludeTableFromReferenceIndex($relation['ref_table'])) {
                continue;
            }
            $relation['hash'] = hash(algo: 'xxh128', data: implode(',', $relation), options: ['seed' => self::HASH_VERSION]);
            // First, check if already indexed and if so, unset that row (so in the end we know which rows to remove!)
            if (isset($currentRelationHashes[$relation['hash']])) {
                unset($currentRelationHashes[$relation['hash']]);
                $result['keptNodes']++;
            } else {
                // If new, register for bulk add:
                if (!$testOnly) {
                    $relationsToInsert[] = $relation;
                }
                $result['addedNodes']++;
            }
        }
        if (!$testOnly && !empty($relationsToInsert)) {
            $connection->bulkInsert('sys_refindex', $relationsToInsert, array_keys(current($relationsToInsert)));
        }

        // If any existing are left, they are not in the current set anymore. Remove them.
        $numberOfLeftOverRelationHashes = count($currentRelationHashes);
        $result['deletedNodes'] = $numberOfLeftOverRelationHashes;
        if ($numberOfLeftOverRelationHashes > 0 && !$testOnly) {
            $this->removeRelationHashes($currentRelationHashes);
        }

        return $result;
    }

    /**
     * Returns relation information for a record from a TCA table.
     *
     * @return array Array with information about relations
     * @internal
     */
    public function getRelations(string $tableName, array $record, int $workspaceUid): array
    {
        $result = [];
        $relationFields = $this->getTableRelationFields($tableName);
        $tableTcaSchema = $this->tcaSchemaFactory->get($tableName);
        foreach ($relationFields as $fieldName) {
            $value = $record[$fieldName] ?? null;
            if (!$tableTcaSchema->hasField($fieldName)) {
                continue;
            }
            $field = $tableTcaSchema->getField($fieldName);
            $fieldConfig = $field->getConfiguration();
            $resultsFromDatabase = $this->getRelationsFromRelationField($tableName, $value, $fieldConfig, (int)$record['uid'], $workspaceUid, $record);
            if (!empty($resultsFromDatabase)) {
                // Create an entry for the field with all DB relations:
                $result[$fieldName] = [
                    'type' => 'db',
                    'itemArray' => $resultsFromDatabase,
                ];
            }
            if ($field->isType(TableColumnType::FLEX) && is_string($value) && $value !== '') {
                // Traverse the flex data structure looking for db references for flex fields.
                $flexFormRelations = $this->getRelationsFromFlexData($tableName, $fieldName, $record, $workspaceUid);
                if (!empty($flexFormRelations)) {
                    $result[$fieldName] = [
                        'type' => 'flex',
                        'flexFormRels' => $flexFormRelations,
                    ];
                }
            }
            if ((string)$value !== '') {
                // Soft References
                $softRefValue = $value;
                if (!empty($fieldConfig['softref'])) {
                    foreach ($this->softReferenceParserFactory->getParsersBySoftRefParserList($fieldConfig['softref']) as $softReferenceParser) {
                        $parserResult = $softReferenceParser->parse($tableName, $fieldName, (int)$record['uid'], $softRefValue);
                        if ($parserResult->hasMatched()) {
                            $result[$fieldName]['softrefs']['keys'][$softReferenceParser->getParserKey()] = $parserResult->getMatchedElements();
                            if ($parserResult->hasContent()) {
                                $softRefValue = $parserResult->getContent();
                            }
                        }
                    }
                }
                if (!empty($result[$fieldName]['softrefs']) && (string)$value !== (string)$softRefValue && str_contains($softRefValue, '{softref:')) {
                    $result[$fieldName]['softrefs']['tokenizedContent'] = $softRefValue;
                }
            }
        }
        return $result;
    }

    /**
     * Get current sys_refindex rows of table:uid from database with hash as index.
     *
     * @return array<string, true>
     */
    private function getCurrentRelationHashes(string $tableName, int $uid, int $workspaceUid): array
    {
        $connection = $this->connectionPool->getConnectionForTable('sys_refindex');
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $queryResult = $queryBuilder->select('hash')->from('sys_refindex')->where(
            $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)),
            $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            $queryBuilder->expr()->eq('workspace', $queryBuilder->createNamedParameter($workspaceUid, Connection::PARAM_INT))
        )->executeQuery();
        $currentRelationHashes = [];
        while ($relation = $queryResult->fetchAssociative()) {
            $currentRelationHashes[$relation['hash']] = true;
        }
        return $currentRelationHashes;
    }

    /**
     * Remove sys_refindex rows by hash.
     *
     * @param array<string, true> $currentRelationHashes
     */
    private function removeRelationHashes(array $currentRelationHashes): void
    {
        $connection = $this->connectionPool->getConnectionForTable('sys_refindex');
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
        $chunks = array_chunk(array_keys($currentRelationHashes), $maxBindParameters - 10, true);
        foreach ($chunks as $chunk) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder
                ->delete('sys_refindex')
                ->where(
                    $queryBuilder->expr()->in('hash', $queryBuilder->createNamedParameter($chunk, Connection::PARAM_STR_ARRAY))
                )
                ->executeStatement();
        }
    }

    private function compileReferenceIndexRowsForRecord(string $tableName, array $record, int $workspaceUid): array
    {
        $relations = [];

        $tableTcaSchema = $this->tcaSchemaFactory->get($tableName);
        $hiddenFieldValue = $tableTcaSchema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)
            ? (int)$record[$tableTcaSchema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName()]
            : 0;
        $starttimeFieldValue = $tableTcaSchema->hasCapability(TcaSchemaCapability::RestrictionStartTime)
            ? (int)$record[$tableTcaSchema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName()]
            : 0;
        $endtimeFieldValue = $tableTcaSchema->hasCapability(TcaSchemaCapability::RestrictionEndTime)
            ? (int)($record[$tableTcaSchema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName()] ?: 2147483647)
            : 2147483647; // @todo: 2^31-1 (year 2038) and not 2^32-1 since postgres 32-bit int is always signed

        $recordRelations = $this->getRelations($tableName, $record, $workspaceUid);
        foreach ($recordRelations as $fieldName => $fieldRelations) {
            $field = $tableTcaSchema->getField($fieldName);
            if ($tableTcaSchema->isWorkspaceAware()
                && isset($record['t3ver_wsid']) && (int)$record['t3ver_wsid'] !== $workspaceUid
                && $field instanceof RelationalFieldTypeInterface
                && $field->getRelationshipType() !== RelationshipType::ManyToMany
            ) {
                // The given record is workspace-enabled but doesn't live in the selected workspace. Don't add index, it's not actually there.
                // We still add those rows if the record is a local side live record of an MM relation and can be a target of a workspace record.
                // See workspaces ManyToMany Modify addCategoryRelation for details on this case.
                continue;
            }
            if (is_array($fieldRelations['itemArray'] ?? false) && !empty($fieldRelations['itemArray'])) {
                // DB relations in a db field
                $itemArray = $fieldRelations['itemArray'];
                if ($field->isType(TableColumnType::INLINE, TableColumnType::FILE)
                    && $field instanceof RelationalFieldTypeInterface
                    && $field->getRelationshipType()->isSingularRelationship()
                ) {
                    // RelationHandler does not return info on hidden, starttime, endtime for inline non-MM, yet. Add this now.
                    // @todo: Refactor RelationHandler / PlainDataResolver to (optionally?) return full child row record
                    $itemArray = $this->enrichInlineRelations(current($itemArray)['table'], $fieldRelations['itemArray']);
                }
                if ($field->isType(TableColumnType::INLINE, TableColumnType::GROUP, TableColumnType::SELECT)
                    && $field instanceof RelationalFieldTypeInterface
                    && $field->getRelationshipType() === RelationshipType::ManyToMany
                ) {
                    foreach ($itemArray as $itemKey => $item) {
                        // Get rid of soft-deleted foreign records, those should not create refindex entries
                        // @todo: It would be better if RelationHandler would not return soft-deleted MM rows. Unsure
                        //        how to do that since RH only works on the MM table, but it could then also return
                        //        the full foreign row along the way.
                        // @todo: Expensive. This code could be optimized to fetch multiple records at once per foreign
                        //        table, or make RH return full foreign rows in some hopefully efficient way.
                        $foreignSideRecord = BackendUtility::getRecord($item['table'], (int)$item['id']);
                        if ($foreignSideRecord === null) {
                            // @todo: This mixes up ref_sorting when rows are removed here. Shouldn't be
                            //        very problematic, though.
                            unset($itemArray[$itemKey]);
                            continue;
                        }
                        $itemTableSchema = $this->tcaSchemaFactory->get((string)$item['table']);
                        if ($itemTableSchema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
                            $disabledFieldName = $itemTableSchema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();
                            $itemArray[$itemKey][$disabledFieldName] = $foreignSideRecord[$disabledFieldName];
                        }
                        if ($itemTableSchema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
                            $starttimeFieldName = $itemTableSchema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName();
                            $itemArray[$itemKey][$starttimeFieldName] = $foreignSideRecord[$starttimeFieldName];
                        }
                        if ($itemTableSchema->hasCapability(TcaSchemaCapability::RestrictionEndTime)) {
                            $endtimeFieldName = $itemTableSchema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName();
                            $itemArray[$itemKey][$endtimeFieldName] = $foreignSideRecord[$endtimeFieldName];
                        }
                        if ($itemTableSchema->hasCapability(TcaSchemaCapability::Workspace)) {
                            $itemArray[$itemKey]['t3ver_state'] = $foreignSideRecord['t3ver_state'];
                        }
                    }
                }
                $sorting = 0;
                foreach ($itemArray as $refRecord) {
                    $refTable = (string)$refRecord['table'];
                    $refTcaTableSchema = $this->tcaSchemaFactory->get($refTable);
                    $relations[] = [
                        'tablename' => $tableName,
                        'recuid' => (int)$record['uid'],
                        'field' => $fieldName,
                        'hidden' => $hiddenFieldValue,
                        'starttime' => $starttimeFieldValue,
                        'endtime' => $endtimeFieldValue,
                        't3ver_state' => (int)($record['t3ver_state'] ?? 0),
                        'flexpointer' => '',
                        'softref_key' => '',
                        'softref_id' => '',
                        'sorting' => $sorting,
                        'workspace' => $workspaceUid,
                        'ref_table' => $refTable,
                        'ref_uid' => (int)$refRecord['id'],
                        'ref_field' => (string)($refRecord['fieldname'] ?? ''),
                        'ref_hidden' => $refTcaTableSchema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)
                            ? (int)($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName()] ?? 0)
                            : 0,
                        'ref_starttime' => $refTcaTableSchema->hasCapability(TcaSchemaCapability::RestrictionStartTime)
                            ? (int)($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName()] ?? 0)
                            : 0,
                        'ref_endtime' => $refTcaTableSchema->hasCapability(TcaSchemaCapability::RestrictionEndTime)
                            ? (int)(($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName()] ?? 0) ?: 2147483647)
                            : 2147483647,
                        'ref_t3ver_state' => (int)($refRecord['t3ver_state'] ?? 0),
                        'ref_sorting' => (int)($refRecord['sorting_foreign'] ?? 0),
                        'ref_string' => '',
                    ];
                    $sorting++;
                }
            }
            if (is_array($fieldRelations['softrefs']['keys'] ?? false)) {
                // Soft reference relations in a db field
                foreach ($fieldRelations['softrefs']['keys'] as $softrefKey => $elements) {
                    if (!is_array($elements)) {
                        continue;
                    }
                    foreach ($elements as $softrefId => $element) {
                        if (!in_array($element['subst']['type'] ?? '', ['db', 'string'], true)) {
                            continue;
                        }
                        $refTable = '_STRING';
                        $refUid = 0;
                        $refString = '';
                        $refRecord = [];
                        $refTcaTableSchema = null;
                        if ($element['subst']['type'] === 'db') {
                            $explodedRefTableUid = explode(':', $element['subst']['recordRef']);
                            $refTable = $explodedRefTableUid[0];
                            $refUid = (int)$explodedRefTableUid[1];
                            if ($refRecord = BackendUtility::getRecord($refTable, $refUid)) {
                                // @todo: It would be great to refactor the softref parser mess "data structure"
                                //        and let it return the reference record along the way - "db" type softrefs
                                //        fetch those already.
                                $refTcaTableSchema = $this->tcaSchemaFactory->get($refTable);
                            } else {
                                // Sanitize the target record: If it does not exist, we should not create a softref
                                // entry to it. Also ref_uid is the uid of the target record. If a softref parser
                                // goes rogue and misinterprets for instance a huge number ("telephone") as uid,
                                // creating this as softref would try to insert a "bigger than 2^31 int" for ref_uid,
                                // leading to an out of bound insert.
                                // @todo: This late validation of softref stuff should probably be relocated to the
                                //        parsers directly. Refactor this together with the above comment.
                                // @todo: It is fishy that DataHandler does not sanitize fields with attached softref
                                //        parsers, either. Example: tt_content header_link with manual editor edit
                                //        "+49 123456789" which looks like a phone number, but the internal phone
                                //        syntax is "tel:49123456789". DH still happily writes the invalid thing into DB.
                                //        This should be rethought.
                                continue;
                            }
                        } else {
                            $refString = mb_substr($element['subst']['tokenValue'], 0, 1024);
                        }
                        $relations[] = [
                            'tablename' => $tableName,
                            'recuid' => (int)$record['uid'],
                            'field' => $fieldName,
                            'hidden' => $hiddenFieldValue,
                            'starttime' => $starttimeFieldValue,
                            'endtime' => $endtimeFieldValue,
                            't3ver_state' => (int)($record['t3ver_state'] ?? 0),
                            'flexpointer' => '',
                            'softref_key' => (string)$softrefKey,
                            'softref_id' => (string)$softrefId,
                            'sorting' => 0,
                            'workspace' => $workspaceUid,
                            'ref_table' => $refTable,
                            'ref_uid' => $refUid,
                            'ref_field' => '',
                            'ref_hidden' => $refTcaTableSchema?->hasCapability(TcaSchemaCapability::RestrictionDisabledField)
                                ? (int)($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName()] ?? 0)
                                : 0,
                            'ref_starttime' => $refTcaTableSchema?->hasCapability(TcaSchemaCapability::RestrictionStartTime)
                                ? (int)($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName()] ?? 0)
                                : 0,
                            'ref_endtime' => $refTcaTableSchema?->hasCapability(TcaSchemaCapability::RestrictionEndTime)
                                ? (int)(($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName()] ?? 0) ?: 2147483647)
                                : 2147483647,
                            'ref_t3ver_state' => (int)($refRecord['t3ver_state'] ?? 0),
                            'ref_sorting' => 0,
                            'ref_string' => $refString,
                        ];
                    }
                }
            }
            if (is_array($fieldRelations['flexFormRels']['db'] ?? false)) {
                // DB relations in a flex field
                foreach ($fieldRelations['flexFormRels']['db'] as $flexPointer => $subList) {
                    $sorting = 0;
                    foreach ($subList as $refRecord) {
                        // @todo: This has no proper test setup in ReferenceIndexTest and ReferenceIndexWorkspaceLoadedTest.
                        //        We probably need to fetch the target record for inline relations here, as done with
                        //        $fieldRelations['itemArray'] enrichInlineRelations() above, to set ref_ fields hidden, starttime,
                        //        endtime and t3ver_state. Additionally, a test based on categories should verify MM details.
                        $relations[] = [
                            'tablename' => $tableName,
                            'recuid' => (int)$record['uid'],
                            'field' => $fieldName,
                            'hidden' => $hiddenFieldValue,
                            'starttime' => $starttimeFieldValue,
                            'endtime' => $endtimeFieldValue,
                            't3ver_state' => (int)($record['t3ver_state'] ?? 0),
                            'flexpointer' => (string)$flexPointer,
                            'softref_key' => '',
                            'softref_id' => '',
                            'sorting' => $sorting,
                            'workspace' => $workspaceUid,
                            'ref_table' => $refRecord['table'],
                            'ref_uid' => (int)$refRecord['id'],
                            'ref_field' => (string)($refRecord['fieldname'] ?? ''),
                            // @todo: ref_hidden, ref_starttime, ref_endtime, ref_t3ver_state, ref_t3ver_state and ref_sorting need coverage and handling.
                            'ref_hidden' => 0,
                            'ref_starttime' => 0,
                            'ref_endtime' => 2147483647,
                            'ref_t3ver_state' => 0,
                            'ref_sorting' => 0,
                            'ref_string' => '',
                        ];
                        $sorting++;
                    }
                }
            }
            if (is_array($fieldRelations['flexFormRels']['softrefs'] ?? false)) {
                // Soft reference relations in a flex field
                foreach ($fieldRelations['flexFormRels']['softrefs'] as $flexPointer => $subList) {
                    foreach ($subList['keys'] as $softrefKey => $elements) {
                        if (!is_array($elements)) {
                            continue;
                        }
                        foreach ($elements as $softrefId => $element) {
                            if (!in_array($element['subst']['type'] ?? '', ['db', 'string'], true)) {
                                continue;
                            }
                            $refTable = '_STRING';
                            $refUid = 0;
                            $refString = '';
                            $refRecord = [];
                            $refTcaTableSchema = null;
                            if ($element['subst']['type'] === 'db') {
                                $explodedRefTableUid = explode(':', $element['subst']['recordRef']);
                                $refTable = $explodedRefTableUid[0];
                                $refUid = (int)$explodedRefTableUid[1];
                                if ($refRecord = BackendUtility::getRecord($refTable, $refUid)) {
                                    // @todo: It would be great to refactor the softref parser mess "data structure"
                                    //        and let it return the reference record along the way - "db" type softrefs
                                    //        fetch those already.
                                    $refTcaTableSchema = $this->tcaSchemaFactory->get($refTable);
                                }
                            } else {
                                $refString = mb_substr($element['subst']['tokenValue'], 0, 1024);
                            }
                            $relations[] = [
                                'tablename' => $tableName,
                                'recuid' => (int)$record['uid'],
                                'field' => $fieldName,
                                'hidden' => $hiddenFieldValue,
                                'starttime' => $starttimeFieldValue,
                                'endtime' => $endtimeFieldValue,
                                't3ver_state' => (int)($record['t3ver_state'] ?? 0),
                                'flexpointer' => $flexPointer,
                                'softref_key' => (string)$softrefKey,
                                'softref_id' => (string)$softrefId,
                                'sorting' => 0,
                                'workspace' => $workspaceUid,
                                'ref_table' => $refTable,
                                'ref_uid' => $refUid,
                                'ref_field' => '',
                                'ref_hidden' => $refTcaTableSchema?->hasCapability(TcaSchemaCapability::RestrictionDisabledField)
                                    ? (int)($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName()] ?? 0)
                                    : 0,
                                'ref_starttime' => $refTcaTableSchema?->hasCapability(TcaSchemaCapability::RestrictionStartTime)
                                    ? (int)($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName()] ?? 0)
                                    : 0,
                                'ref_endtime' => $refTcaTableSchema?->hasCapability(TcaSchemaCapability::RestrictionEndTime)
                                    ? (int)(($refRecord[$refTcaTableSchema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName()] ?? 0) ?: 2147483647)
                                    : 2147483647,
                                'ref_t3ver_state' => (int)($refRecord['t3ver_state'] ?? 0),
                                'ref_sorting' => 0,
                                'ref_string' => $refString,
                            ];
                        }
                    }
                }
            }
        }
        return $relations;
    }

    /**
     * RelationHandler does not return relation record details when dealing with
     * inline foreign_table relations. We need fields like hidden and starrtime,
     * though. Fetch them now.
     */
    private function enrichInlineRelations(string $tableName, array $itemArray): array
    {
        $selectFields = ['uid'];
        $tableTcaSchema = $this->tcaSchemaFactory->get($tableName);
        if ($tableTcaSchema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
            $selectFields[] = $tableTcaSchema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();
        }
        if ($tableTcaSchema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
            $selectFields[] = $tableTcaSchema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName();
        }
        if ($tableTcaSchema->hasCapability(TcaSchemaCapability::RestrictionEndTime)) {
            $selectFields[] = $tableTcaSchema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName();
        }
        if ($tableTcaSchema->isWorkspaceAware()) {
            $selectFields[] = 't3ver_state';
        }
        if (count($selectFields) === 1) {
            return $itemArray;
        }
        $connection = $this->connectionPool->getConnectionForTable($tableName);
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $rows = [];
        $uidList = array_column($itemArray, 'id');
        foreach (array_chunk($uidList, $maxBindParameters - 10, true) as $chunk) {
            $result = $queryBuilder->select(...$selectFields)->from($tableName)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->orderBy('uid', 'ASC')->executeQuery();
            while ($row = $result->fetchAssociative()) {
                $rows[(int)$row['uid']] = $row;
            }
        }
        foreach ($itemArray as &$item) {
            if (isset($rows[$item['id']])) {
                // @todo: The isset() prevents a PHP array access warning here. It seems this can happen with
                //        inline CSV since RelationHandler->realList() does not verify if attached records
                //        really exist. There is probably a deeper issue with CSV lists here, see #106428 for
                //        more information and reproduce. This area should have a closer look, it looks as if
                //        "count" value instead of uid fields are hand over here - at least with tx_styleguide_inline_11.
                $item = array_merge($item, $rows[$item['id']]);
            }
        }
        return $itemArray;
    }

    private function getRelationsFromFlexData(string $tableName, string $fieldName, array $row, int $workspaceUid): array
    {
        $valueArray = GeneralUtility::xml2array($row[$fieldName] ?? '');
        if (!is_array($valueArray)) {
            // Current flex form values can not be parsed to an array. No relations.
            return [];
        }
        try {
            $tableTcaSchema = $this->tcaSchemaFactory->get($tableName);
            $fieldConfig['config'] = $tableTcaSchema->getField($fieldName)->getConfiguration();
            $dataStructureArray = $this->flexFormTools->parseDataStructureByIdentifier(
                $this->flexFormTools->getDataStructureIdentifier($fieldConfig, $tableName, $fieldName, $row)
            );
        } catch (InvalidIdentifierException) {
            // Data structure can not be resolved or parsed. No relations.
            return [];
        }
        if (!is_array($dataStructureArray['sheets'] ?? false)) {
            // No sheet in DS. Shouldn't happen, though.
            return [];
        }
        $flexRelations = [];
        foreach ($dataStructureArray['sheets'] as $sheetKey => $sheetData) {
            foreach (($sheetData['ROOT']['el'] ?? []) as $sheetElementKey => $sheetElementTca) {
                // For all elements allowed in Data Structure.
                if (($sheetElementTca['type'] ?? '') === 'array') {
                    // This is a section.
                    if (!is_array($sheetElementTca['el'] ?? false) || !is_array($valueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['el'] ?? false)) {
                        // No possible containers defined for this section in DS, or no values set for this section.
                        continue;
                    }
                    foreach ($valueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['el'] as $valueSectionContainerKey => $valueSectionContainers) {
                        // We have containers for this section in values.
                        if (!is_array($valueSectionContainers ?? false)) {
                            // Values don't validate to an array, skip.
                            continue;
                        }
                        foreach ($valueSectionContainers as $valueContainerType => $valueContainerElements) {
                            // For all value containers in this section.
                            if (!is_array($sheetElementTca['el'][$valueContainerType]['el'] ?? false)) {
                                // There is no DS for this container type, skip.
                                continue;
                            }
                            foreach ($sheetElementTca['el'][$valueContainerType]['el'] as $containerElement => $containerElementTca) {
                                // Container type of this value container exists in DS. Iterate DS container to find value relations.
                                if (isset($valueContainerElements['el'][$containerElement]['vDEF'])) {
                                    $fieldValue = $valueContainerElements['el'][$containerElement]['vDEF'];
                                    $structurePath = $sheetKey . '/lDEF/' . $sheetElementKey . '/el/' . $valueSectionContainerKey . '/' . $valueContainerType . '/el/' . $containerElement . '/vDEF/';
                                    if ($fieldValue !== '' && ($containerElementTca['config']['softref'] ?? '') !== '') {
                                        $tokenizedContent = $fieldValue;
                                        foreach ($this->softReferenceParserFactory->getParsersBySoftRefParserList($containerElementTca['config']['softref']) as $softReferenceParser) {
                                            $parserResult = $softReferenceParser->parse($tableName, $fieldName, (int)$row['uid'], $fieldValue, $structurePath);
                                            if ($parserResult->hasMatched()) {
                                                $flexRelations['softrefs'][$structurePath]['keys'][$softReferenceParser->getParserKey()] = $parserResult->getMatchedElements();
                                                if ($parserResult->hasContent()) {
                                                    $tokenizedContent = $parserResult->getContent();
                                                }
                                            }
                                        }
                                        if (!empty($flexRelations['softrefs'][$structurePath]) && $fieldValue !== $tokenizedContent) {
                                            $flexRelations['softrefs'][$structurePath]['tokenizedContent'] = $tokenizedContent;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } elseif (isset($valueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['vDEF'])) {
                    // Not a section but a simple field. Get its relations.
                    $fieldValue = $valueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['vDEF'];
                    $structurePath = $sheetKey . '/lDEF/' . $sheetElementKey . '/vDEF/';
                    $databaseRelations = $this->getRelationsFromRelationField($tableName, $fieldValue, $sheetElementTca['config'] ?? [], (int)$row['uid'], $workspaceUid, $row);
                    if (!empty($databaseRelations)) {
                        $flexRelations['db'][$structurePath] = $databaseRelations;
                    }
                    if ($fieldValue !== '' && ($sheetElementTca['config']['softref'] ?? '') !== '') {
                        $tokenizedContent = $fieldValue;
                        foreach ($this->softReferenceParserFactory->getParsersBySoftRefParserList($sheetElementTca['config']['softref']) as $softReferenceParser) {
                            $parserResult = $softReferenceParser->parse($tableName, $fieldName, (int)$row['uid'], $fieldValue, $structurePath);
                            if ($parserResult->hasMatched()) {
                                $flexRelations['softrefs'][$structurePath]['keys'][$softReferenceParser->getParserKey()] = $parserResult->getMatchedElements();
                                if ($parserResult->hasContent()) {
                                    $tokenizedContent = $parserResult->getContent();
                                }
                            }
                        }
                        if (!empty($flexRelations['softrefs'][$structurePath]) && $fieldValue !== $tokenizedContent) {
                            $flexRelations['softrefs'][$structurePath]['tokenizedContent'] = $tokenizedContent;
                        }
                    }
                }
            }
        }
        return $flexRelations;
    }

    /**
     * Check field configuration if it is a DB relation field and extract DB relations if any
     */
    private function getRelationsFromRelationField(string $tableName, mixed $fieldValue, array $conf, int $uid, int $workspaceUid, array $row): array
    {
        if (empty($conf)) {
            return [];
        }
        if (($conf['type'] === 'inline' || $conf['type'] === 'file') && !empty($conf['foreign_table']) && empty($conf['MM'])) {
            $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
            $dbAnalysis->setUseLiveReferenceIds(false);
            $dbAnalysis->setWorkspaceId($workspaceUid);
            $dbAnalysis->start($fieldValue, $conf['foreign_table'], '', $uid, $tableName, $conf);
            return $dbAnalysis->itemArray;
        }
        if ($this->isDbReferenceField($conf)) {
            $allowedTables = $conf['type'] === 'group' ? $conf['allowed'] : $conf['foreign_table'];
            if ($conf['MM_opposite_field'] ?? false) {
                // Never handle sys_refindex when looking at MM from foreign side
                return [];
            }
            $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
            $dbAnalysis->setWorkspaceId($workspaceUid);
            $dbAnalysis->start($fieldValue, $allowedTables, $conf['MM'] ?? '', $uid, $tableName, $conf);
            $itemArray = $dbAnalysis->itemArray;
            if (ExtensionManagementUtility::isLoaded('workspaces')
                && $workspaceUid > 0
                && !empty($conf['MM'] ?? '')
                // Catch type=group 'allowed' and type=select 'foreign_table' MM scenarios
                && (!empty($conf['allowed'] ?? '') || !empty($conf['foreign_table'] ?? ''))
                && empty($conf['MM_opposite_field'] ?? '')
                && (int)($row['t3ver_wsid'] ?? 0) === 0
            ) {
                // When dealing with local side mm relations in workspace 0, there may be workspace records on the foreign
                // side, for instance when those got an additional category. See ManyToMany Modify addCategoryRelations test.
                // In those cases, the full set of relations must be written to sys_refindex as workspace rows.
                // But, if the relations in this workspace and live are identical, no sys_refindex workspace rows
                // have to be added.
                $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
                $dbAnalysis->setWorkspaceId(0);
                $dbAnalysis->start($fieldValue, $allowedTables, $conf['MM'], $uid, $tableName, $conf);
                $itemArrayLive = $dbAnalysis->itemArray;
                if ($itemArrayLive === $itemArray) {
                    $itemArray = [];
                }
            }
            return $itemArray;
        }
        return [];
    }

    /**
     * Returns true if the TCA/columns field type is a DB reference field
     *
     * @param array $configuration Config array for TCA/columns field
     * @return bool TRUE if DB reference field (group/db or select with foreign-table)
     */
    private function isDbReferenceField(array $configuration): bool
    {
        return
            $configuration['type'] === 'group'
            || (
                in_array($configuration['type'], ['select', 'category', 'inline', 'file'], true)
                && !empty($configuration['foreign_table'])
            );
    }

    /**
     * Returns true if the TCA/columns field may carry references. True for
     * group, inline and friends, for flex, and if there is a 'softref' definition.
     */
    private function isReferenceField(FieldTypeInterface $field): bool
    {
        return
            $this->isDbReferenceField($field->getConfiguration())
            || $field->isType(TableColumnType::FLEX)
            || isset($field->getConfiguration()['softref'])
        ;
    }

    /**
     * List of TCA columns that can have relations. Typically inline, group
     * and friends, as well as flex fields and fields with 'softref' config.
     * If empty, the table can not have relations.
     * Uses a class cache to be quick for multiple calls on same table.
     */
    private function getTableRelationFields(string $tableName): array
    {
        if (isset($this->tableRelationFieldCache[$tableName])) {
            return $this->tableRelationFieldCache[$tableName];
        }
        if (!$this->tcaSchemaFactory->has($tableName)) {
            $this->tableRelationFieldCache[$tableName] = [];
            return [];
        }
        $tableTcaFields = $this->tcaSchemaFactory->get($tableName)->getFields();
        $relationFields = [];
        foreach ($tableTcaFields as $field) {
            if ($this->isReferenceField($field)) {
                $relationFields[] = $field->getName();
            }
        }
        $this->tableRelationFieldCache[$tableName] = $relationFields;
        return $relationFields;
    }

    /**
     * Create list of non-deleted "active" workspace uids. This contains at least 0 "live workspace".
     *
     * @return int[]
     */
    private function getListOfActiveWorkspaces(): array
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            // If ext:workspaces is not loaded, "0" is the only valid one.
            return [0];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_workspace');
        // Workspaces can't be 'hidden', so we only use deleted restriction here.
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuilder->select('uid')->from('sys_workspace')->orderBy('uid')->executeQuery();
        // "0", plus non-deleted workspaces are active
        return array_merge([0 => 0], $result->fetchFirstColumn());
    }

    /**
     * Helper method of updateIndex() to find number of rows in sys_refindex that
     * relate to a non-existing or deleted workspace record, even if workspaces is
     * not loaded at all, but has been loaded somewhere in the past and sys_refindex
     * rows have been created.
     */
    private function getNumberOfUnusedWorkspaceRowsInReferenceIndex(array $activeWorkspaces): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        $numberOfInvalidWorkspaceRecords = $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn('workspace', $queryBuilder->createNamedParameter($activeWorkspaces, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchOne();
        return (int)$numberOfInvalidWorkspaceRecords;
    }

    /**
     * Delete sys_refindex rows of deleted / not existing workspace records, or all if ext:workspace is not loaded.
     */
    private function removeUnusedWorkspaceRowsFromReferenceIndex(array $activeWorkspaces): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn('workspace', $queryBuilder->createNamedParameter($activeWorkspaces, Connection::PARAM_INT_ARRAY))
            )
            ->executeStatement();
    }

    /**
     * When a TCA table with references has been removed, there may be old sys_refindex
     * rows for it. The query finds the number of affected rows.
     */
    private function getNumberOfUnusedTablesInReferenceIndex(array $tableNames): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        $numberOfRowsOfUnusedTables = $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn('tablename', $queryBuilder->createNamedParameter($tableNames, Connection::PARAM_STR_ARRAY))
            )
            ->executeQuery()
            ->fetchOne();
        return (int)$numberOfRowsOfUnusedTables;
    }

    /**
     * When a TCA table with references has been removed, there may be old sys_refindex
     * rows for it. The query deletes those.
     */
    private function removeReferenceIndexDataFromUnusedDatabaseTables(array $tableNames): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn('tablename', $queryBuilder->createNamedParameter($tableNames, Connection::PARAM_STR_ARRAY))
            )
            ->executeStatement();
    }

    /**
     * Checks if a given table should be excluded from ReferenceIndex
     */
    private function shouldExcludeTableFromReferenceIndex(string $tableName): bool
    {
        if (isset($this->excludedTables[$tableName])) {
            return $this->excludedTables[$tableName];
        }
        $event = new IsTableExcludedFromReferenceIndexEvent($tableName);
        $event = $this->eventDispatcher->dispatch($event);
        $this->excludedTables[$tableName] = $event->isTableExcluded();
        return $this->excludedTables[$tableName];
    }
}
