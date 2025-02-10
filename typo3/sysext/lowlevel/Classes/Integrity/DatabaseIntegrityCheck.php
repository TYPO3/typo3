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

namespace TYPO3\CMS\Lowlevel\Integrity;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\CategoryFieldType;
use TYPO3\CMS\Core\Schema\Field\GroupFieldType;
use TYPO3\CMS\Core\Schema\Field\SelectRelationFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class holds functions used by the TYPO3 backend to check the integrity
 * of the database (The DBint module, 'lowlevel' extension)
 *
 * Depends on \TYPO3\CMS\Core\Database\RelationHandler
 *
 * @TODO: Need to really extend this class when the DataHandler library has been
 * @TODO: updated and the whole API is better defined. There are some known bugs
 * @TODO: in this library. Further it would be nice with a facility to not only
 * @TODO: analyze but also clean up!
 * @see \TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController::relationsAction()
 * @see \TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController::recordStatisticsAction()
 */
#[Autoconfigure(public: true)]
class DatabaseIntegrityCheck
{
    /**
     * @var array Will hold id/rec pairs from genTree()
     */
    protected array $pageIdArray = [];

    /**
     * @var array Will hold id/rec pairs from genTree() that are not default language
     */
    protected array $pageTranslatedPageIDArray = [];

    /**
     * @var array From the select-fields
     */
    protected array $checkSelectDBRefs = [];

    /**
     * @var array From the group-fields
     */
    protected array $checkGroupDBRefs = [];

    /**
     * @var array Statistics
     */
    protected array $recStats = [
        'all_valid' => [],
        'published_versions' => [],
        'deleted' => [],
    ];

    protected array $lRecords = [];
    protected string $lostPagesList = '';

    public function __construct(
        protected readonly TcaSchemaFactory $tcaSchemaFactory
    ) {}

    public function getPageTranslatedPageIDArray(): array
    {
        return $this->pageTranslatedPageIDArray;
    }

    /**
     * Generates a list of Page-uid's that corresponds to the tables in the tree.
     * This list should ideally include all records in the pages-table.
     *
     * @param int $theID a pid (page-record id) from which to start making the tree
     * @param bool $versions Internal variable, don't set from outside!
     */
    public function genTree($theID, $versions = false)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('uid', 'title', 'doktype', 'deleted', 'hidden', 'sys_language_uid')
            ->from('pages')
            ->orderBy('sorting');
        if ($versions) {
            $queryBuilder->addSelect('t3ver_wsid');
            $queryBuilder->where(
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($theID, Connection::PARAM_INT))
            );
        } else {
            $queryBuilder->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($theID, Connection::PARAM_INT))
            );
        }
        $result = $queryBuilder->executeQuery();
        // Traverse the records selected
        while ($row = $result->fetchAssociative()) {
            $newID = $row['uid'];
            // Register various data for this item:
            if ($row['sys_language_uid'] === 0) {
                $this->pageIdArray[$newID] = $row;
            } else {
                $this->pageTranslatedPageIDArray[$newID] = $row;
            }
            $this->recStats['all_valid']['pages'][$newID] = $newID;
            if ($row['deleted']) {
                $this->recStats['deleted']['pages'][$newID] = $newID;
            }

            if (!isset($this->recStats['hidden'])) {
                $this->recStats['hidden'] = 0;
            }

            if ($row['hidden']) {
                $this->recStats['hidden']++;
            }

            $this->recStats['doktype'][$row['doktype']] ??= 0;
            $this->recStats['doktype'][$row['doktype']]++;
            // Add sub pages:
            $this->genTree($newID);
            // If versions are included in the tree, add those now:
            $this->genTree($newID, true);
        }
    }

    /**
     * Fills $this->lRecords with the records from all tc-tables that are not attached to a PID in the pid-list.
     *
     * @param string $pid_list list of pid's (page-record uid's). This list is probably made by genTree()
     */
    public function lostRecords($pid_list): void
    {
        $this->lostPagesList = '';
        $pageIds = GeneralUtility::intExplode(',', $pid_list);
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $pageIdsForTable = $pageIds;
            // Remove preceding "-1," for non-versioned tables
            if (!$schema->isWorkspaceAware()) {
                $pageIdsForTable = array_combine($pageIdsForTable, $pageIdsForTable);
                unset($pageIdsForTable[-1]);
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $queryResult = $queryBuilder
                ->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->notIn(
                        'pid',
                        $queryBuilder->createNamedParameter($pageIdsForTable, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->executeQuery();
            $lostIdList = [];
            while ($row = $queryResult->fetchAssociative()) {
                $this->lRecords[$table][$row['uid']] = [
                    'uid' => $row['uid'],
                    'pid' => $row['pid'],
                    'title' => strip_tags(BackendUtility::getRecordTitle($table, $row)),
                ];
                $lostIdList[] = $row['uid'];
            }
            if ($table === 'pages') {
                $this->lostPagesList = implode(',', $lostIdList);
            }
        }
    }

    /**
     * Fixes lost record from $table with uid $uid by setting the PID to zero.
     * If there is a disabled column for the record that will be set as well.
     *
     * @param string $table Database tablename
     * @param int $uid The uid of the record which will have the PID value set to 0 (zero)
     * @return bool TRUE if done.
     */
    public function fixLostRecord(string $table, $uid): bool
    {
        if ($table === '') {
            return false;
        }
        if (!$this->tcaSchemaFactory->has($table)) {
            return false;
        }
        if (!$GLOBALS['BE_USER']->isAdmin()) {
            return false;
        }
        if (!is_array($this->lRecords[$table][$uid])) {
            return false;
        }
        $updateFields = [
            'pid' => 0,
        ];
        // If possible a lost record restored is hidden as default
        $schema = $this->tcaSchemaFactory->get($table);
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
            $disableFieldName = $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();
            $updateFields[$disableFieldName] = 1;
        }
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->update($table, $updateFields, ['uid' => (int)$uid]);
        return true;
    }

    /**
     * Counts records from $GLOBALS['TCA']-tables that ARE attached to an existing page.
     *
     * @param string $pid_list list of pid's (page-record uid's). This list is probably made by genTree()
     * @return array an array with the number of records from all $GLOBALS['TCA']-tables that are attached to a PID in the pid-list.
     */
    public function countRecords($pid_list): array
    {
        $list = [];
        $list_n = [];
        $pageIds = GeneralUtility::intExplode(',', $pid_list);
        if (!empty($pageIds)) {
            /** @var TcaSchema $schema */
            foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
                $pageIdsForTable = $pageIds;
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $queryBuilder->getRestrictions()->removeAll();
                $count = $queryBuilder->count('uid')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->in(
                            'pid',
                            $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                        )
                    )
                    ->executeQuery()
                    ->fetchOne();
                if ($count) {
                    $list[$table] = $count;
                }

                // same query excluding all deleted records
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $count = $queryBuilder->count('uid')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->in(
                            'pid',
                            $queryBuilder->createNamedParameter($pageIdsForTable, Connection::PARAM_INT_ARRAY)
                        )
                    )
                    ->executeQuery()
                    ->fetchOne();
                if ($count) {
                    $list_n[$table] = $count;
                }
            }
        }
        return ['all' => $list, 'non_deleted' => $list_n];
    }

    /**
     * Finding relations in database based on type 'group' (database-uid's in a list)
     *
     * @return array An array with all fields listed that somehow are references to other records (foreign-keys)
     */
    public function getGroupFields(): array
    {
        $result = [];
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            foreach ($schema->getFields() as $field) {
                if ($field instanceof SelectRelationFieldType) {
                    $result[$table][] = $field->getName();
                }
                // @todo: this should be handled in a better way?
                if ($field instanceof CategoryFieldType) {
                    $result[$table][] = $field->getName();
                }
                if ($field instanceof GroupFieldType) {
                    $result[$table][] = $field->getName();
                }
            }
        }
        return $result;
    }

    /**
     * This selects non-empty-records from the tables/fields in the fkey_array generated by getGroupFields()
     *
     * @see getGroupFields()
     */
    public function selectNonEmptyRecordsWithFkeys(): void
    {
        $fkey_arrays = $this->getGroupFields();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($fkey_arrays as $table => $fields) {
            $schema = $this->tcaSchemaFactory->get($table);
            $connection = $connectionPool->getConnectionForTable($table);
            $tableColumns = $connection->createSchemaManager()->listTableColumns($table);

            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();

            $queryBuilder->select('*')
                ->from($table);
            $whereClause = [];

            foreach ($fields as $fieldName) {
                // The array index of $tableColumns is the lowercased column name!
                // It is quoted for keywords
                $column = $tableColumns[strtolower($fieldName)]
                    ?? $tableColumns[$connection->quoteIdentifier(strtolower($fieldName))]
                    ?? '';
                if (empty($column)) {
                    // Throw meaningful exception if field does not exist in DB - 'none' is not filtered here since the
                    // method is only called with type=group fields
                    throw new \RuntimeException(
                        'Field ' . $fieldName . ' for table ' . $table . ' has been defined in TCA, but does not exist in DB',
                        1536248937
                    );
                }
                $fieldType = Type::getTypeRegistry()->lookupName($column->getType());
                if (in_array(
                    $fieldType,
                    [Types::BIGINT, Types::INTEGER, Types::SMALLINT, Types::DECIMAL, Types::FLOAT],
                    true
                )) {
                    $whereClause[] = $queryBuilder->expr()->and(
                        $queryBuilder->expr()->isNotNull($fieldName),
                        $queryBuilder->expr()->neq(
                            $fieldName,
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        )
                    );
                } elseif (in_array($fieldType, [Types::STRING, Types::TEXT], true)) {
                    $whereClause[] = $queryBuilder->expr()->and(
                        $queryBuilder->expr()->isNotNull($fieldName),
                        $queryBuilder->expr()->neq(
                            $fieldName,
                            $queryBuilder->createNamedParameter('')
                        )
                    );
                } elseif ($fieldType === Types::BLOB) {
                    $whereClause[] = $queryBuilder->expr()->and(
                        $queryBuilder->expr()->isNotNull($fieldName),
                        $queryBuilder->expr()
                            ->comparison(
                                $queryBuilder->expr()->length($fieldName),
                                ExpressionBuilder::GT,
                                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                            )
                    );
                }
            }
            $queryResult = $queryBuilder->orWhere(...$whereClause)->executeQuery();

            while ($row = $queryResult->fetchAssociative()) {
                foreach ($fields as $field) {
                    if (!isset($row[$field])) {
                        continue;
                    }
                    if (!trim($row[$field])) {
                        continue;
                    }
                    if (!$schema->hasField($field)) {
                        continue;
                    }
                    $fieldType = $schema->getField($field);
                    $fieldConf = $fieldType->getConfiguration();
                    if ($fieldType->isType(TableColumnType::GROUP)) {
                        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
                        $dbAnalysis->initializeForField($table, $fieldType, $row['uid'], $row[$field]);
                        foreach ($dbAnalysis->itemArray as $tempArr) {
                            if (!isset($this->checkGroupDBRefs[$tempArr['table']][$tempArr['id']])) {
                                $this->checkGroupDBRefs[$tempArr['table']][$tempArr['id']] = 0;
                            }
                            $this->checkGroupDBRefs[$tempArr['table']][$tempArr['id']] += 1;
                        }
                    }
                    if (($fieldConf['foreign_table'] ?? false)
                        && $fieldType->isType(TableColumnType::SELECT, TableColumnType::CATEGORY)
                    ) {
                        $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
                        $dbAnalysis->initializeForField($table, $fieldType, $row['uid'], $row[$field]);
                        foreach ($dbAnalysis->itemArray as $tempArr) {
                            if ($tempArr['id'] > 0) {
                                if (!isset($this->checkSelectDBRefs[$fieldConf['foreign_table']][$tempArr['id']])) {
                                    $this->checkSelectDBRefs[$fieldConf['foreign_table']][$tempArr['id']] = 0;
                                }
                                $this->checkSelectDBRefs[$fieldConf['foreign_table']][$tempArr['id']] += 1;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Depends on selectNonEmpty.... to be executed first!!
     *
     * @param array $theArray Table with key/value pairs being table names and arrays with uid numbers
     * @return string HTML Error message
     */
    public function testDBRefs(array $theArray): string
    {
        $rows = [];
        foreach ($theArray as $table => $dbArr) {
            if ($this->tcaSchemaFactory->has($table)) {
                $ids = array_keys($dbArr);
                if (!empty($ids)) {
                    $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable($table);

                    $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());

                    foreach (array_chunk($ids, $maxBindParameters, true) as $chunk) {
                        $queryBuilder = $connection->createQueryBuilder();
                        $queryBuilder->getRestrictions()
                            ->removeAll()
                            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        $queryResult = $queryBuilder
                            ->select('uid')
                            ->from($table)
                            ->where(
                                $queryBuilder->expr()->in(
                                    'uid',
                                    $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                                )
                            )
                            ->executeQuery();
                        while ($row = $queryResult->fetchAssociative()) {
                            if (isset($dbArr[$row['uid']])) {
                                unset($dbArr[$row['uid']]);
                            } else {
                                $rows[] = 'Strange Error. ...';
                            }
                        }
                    }

                    foreach ($dbArr as $theId => $theC) {
                        $rows[] = 'There are ' . $theC . ' records pointing to this missing or deleted record: <code>[' . $table . '][' . $theId . ']</code>';
                    }
                }
            } else {
                $rows[] = 'Codeerror. Table is not a TCA table.';
            }
        }

        return $rows !== [] ? '<ul class="list-unstyled" role="list">' . implode(LF, array_map(static fn(string $row): string => '<li>' . $row . '</li>', $rows)) . '</ul>' : '';
    }

    public function getPageIdArray(): array
    {
        return $this->pageIdArray;
    }

    public function getCheckGroupDBRefs(): array
    {
        return $this->checkGroupDBRefs;
    }

    public function getCheckSelectDBRefs(): array
    {
        return $this->checkSelectDBRefs;
    }

    public function getRecStats(): array
    {
        return $this->recStats;
    }

    public function getLRecords(): array
    {
        return $this->lRecords;
    }

    public function getLostPagesList(): string
    {
        return $this->lostPagesList;
    }
}
