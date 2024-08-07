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

namespace TYPO3\CMS\Core\Database\Schema;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use TYPO3\CMS\Core\Database\Connection as Typo3Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Schema\ColumnDiff as Typo3ColumnDiff;
use TYPO3\CMS\Core\Database\Schema\SchemaDiff as Typo3SchemaDiff;
use TYPO3\CMS\Core\Database\Schema\TableDiff as Typo3TableDiff;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handling schema migrations per connection.
 *
 * @internal not part of public core API.
 *
 * @todo The whole ConnectionMigrator and comparison stack needs a refactoring. Specially, the normalization steps
 *       needs to be centralized and reworked to a more workable solution. Additionally, the reporting states are
 *       eligible for a refactoring to a more suitable solution to group into safe and unsafe operations along with
 *       database value normalization for column alterations. Further, operating on the properties for the diff classes
 *       is also suboptimal and should be refactored with care. For example, the `normalize*` methods should be reworked.
 */
class ConnectionMigrator
{
    /**
     * @var string Prefix of deleted tables
     */
    protected string $deletedPrefix = 'zzz_deleted_';

    /**
     * @param Table[] $tables
     */
    public function __construct(
        private readonly string $connectionName,
        private readonly Typo3Connection $connection,
        private readonly array $tables,
    ) {}

    /**
     * @param non-empty-string $connectionName
     * @param Typo3Connection $connection
     * @param Table[] $tables
     */
    public static function create(string $connectionName, Typo3Connection $connection, array $tables): self
    {
        return GeneralUtility::makeInstance(
            static::class,
            $connectionName,
            $connection,
            $tables,
        );
    }

    /**
     * Return the raw Doctrine SchemaDiff object for the current connection.
     * This diff contains all changes without any pre-processing.
     */
    public function getSchemaDiff(): Typo3SchemaDiff
    {
        return $this->buildSchemaDiff(false);
    }

    /**
     * Compare current and expected schema definitions and provide updates
     * suggestions in the form of SQL statements.
     */
    public function getUpdateSuggestions(bool $remove = false): array
    {
        $schemaDiff = $this->buildSchemaDiff();
        if ($remove === false) {
            return array_merge_recursive(
                ['add' => [], 'create_table' => [], 'change' => [], 'change_currentValue' => []],
                $this->getNewFieldUpdateSuggestions($schemaDiff),
                $this->getNewTableUpdateSuggestions($schemaDiff),
                $this->getChangedFieldUpdateSuggestions($schemaDiff),
                $this->getChangedTableOptions($schemaDiff)
            );
        }
        return array_merge_recursive(
            ['change' => [], 'change_table' => [], 'drop' => [], 'drop_table' => [], 'tables_count' => []],
            $this->getUnusedFieldUpdateSuggestions($schemaDiff),
            $this->getUnusedTableUpdateSuggestions($schemaDiff),
            $this->getDropTableUpdateSuggestions($schemaDiff),
            $this->getDropFieldUpdateSuggestions($schemaDiff)
        );
    }

    /**
     * Perform add/change/create operations on tables and fields in an optimized, non-interactive, mode.
     */
    public function install(bool $createOnly = false): array
    {
        $result = [];
        $schemaDiff = $this->buildSchemaDiff(false);

        $schemaDiff->droppedTables = [];
        foreach ($schemaDiff->alteredTables as $key => $changedTable) {
            $schemaDiff->alteredTables[$key]->droppedColumns = [];
            $schemaDiff->alteredTables[$key]->droppedIndexes = [];

            // With partial ext_tables.sql files the SchemaManager is detecting
            // existing columns as false positives for a column rename. In this
            // context every rename is actually a new column.
            foreach ($changedTable->renamedColumns as $columnName => $renamedColumn) {
                $changedTable->addedColumns[$renamedColumn->getName()] = new Column(
                    $renamedColumn->getName(),
                    $renamedColumn->getType(),
                    $this->prepareColumnOptions($renamedColumn)
                );
                unset($changedTable->renamedColumns[$columnName]);
            }

            if ($createOnly) {
                // Ignore new indexes that work on columns that need changes
                foreach ($changedTable->addedIndexes as $indexName => $addedIndex) {
                    $indexColumns = array_map(
                        static function (string $columnName): string {
                            // Strip MySQL prefix length information to get real column names
                            $columnName = preg_replace('/\(\d+\)$/', '', $columnName) ?? '';
                            // Strip sqlite '"' from column names
                            return trim($columnName, '"');
                        },
                        $addedIndex->getColumns()
                    );
                    $columnChanges = array_intersect($indexColumns, array_keys($changedTable->modifiedColumns));
                    if (!empty($columnChanges)) {
                        unset($schemaDiff->alteredTables[$key]->addedIndexes[$indexName]);
                    }
                }
                $schemaDiff->alteredTables[$key]->modifiedColumns = [];
                $schemaDiff->alteredTables[$key]->modifiedIndexes = [];
                $schemaDiff->alteredTables[$key]->renamedIndexes = [];
            }
        }

        $statements = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff);
        foreach ($statements as $statement) {
            try {
                $this->connection->executeStatement($statement);
                $result[$statement] = '';
            } catch (DBALException $e) {
                $result[$statement] = $e->getPrevious()->getMessage();
            }
        }

        return $result;
    }

    /**
     * If the schema is not for the Default connection remove all tables from the schema
     * that have no mapping in the TYPO3 configuration. This avoids update suggestions
     * for tables that are in the database but have no direct relation to the TYPO3 instance.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function buildSchemaDiff(bool $renameUnused = true): Typo3SchemaDiff
    {
        // Unmapped tables in a non-default connection are ignored by TYPO3
        $tablesForConnection = [];
        if ($this->connectionName !== ConnectionPool::DEFAULT_CONNECTION_NAME) {
            // If there are no mapped tables return a SchemaDiff without any changes
            // to avoid update suggestions for tables not related to TYPO3.
            if (empty($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'] ?? null)) {
                return new SchemaDiff(
                    // createdSchemas
                    [],
                    // droppedSchemas
                    [],
                    // createdTables
                    [],
                    // alteredTables
                    [],
                    // droppedTables:
                    [],
                    // createdSequences
                    [],
                    // alteredSequences
                    [],
                    // droppedSequences
                    [],
                );
            }

            // Collect the table names that have been mapped to this connection.
            $connectionName = $this->connectionName;
            /** @var string[] $tablesForConnection */
            $tablesForConnection = array_keys(
                array_filter(
                    $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'],
                    static function (string $tableConnectionName) use ($connectionName): bool {
                        return $tableConnectionName === $connectionName;
                    }
                )
            );

            // Ignore all tables without mapping if not in the default connection
            $this->connection->getConfiguration()->setSchemaAssetsFilter(
                static function ($assetName) use ($tablesForConnection) {
                    return in_array($assetName, $tablesForConnection, true);
                }
            );
        }

        // Build the schema definitions
        $fromSchema = $this->buildExistingSchemaDefinitions();
        $toSchema = $this->buildExpectedSchemaDefinitions($this->connectionName);

        // Add current table options to the fromSchema
        $tableOptions = $this->getTableOptions($this->getSchemaTableNames($fromSchema));
        foreach ($fromSchema->getTables() as $table) {
            $tableName = $table->getName();
            if (!array_key_exists($tableName, $tableOptions)) {
                continue;
            }
            foreach ($tableOptions[$tableName] as $optionName => $optionValue) {
                $table->addOption($optionName, $optionValue);
            }
        }

        // Build SchemaDiff and handle renames of tables and columns
        $comparator = GeneralUtility::makeInstance(Comparator::class, $this->connection->getDatabasePlatform());
        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);
        if (! $schemaDiff instanceof Typo3SchemaDiff) {
            $schemaDiff = Typo3SchemaDiff::ensure($schemaDiff);
        }
        $schemaDiff = $this->migrateColumnRenamesToDistinctActions($schemaDiff);

        if ($renameUnused) {
            $schemaDiff = $this->migrateUnprefixedRemovedTablesToRenames($schemaDiff);
            $schemaDiff = $this->migrateUnprefixedRemovedFieldsToRenames($schemaDiff);
        }

        // All tables in the default connection are managed by TYPO3
        if ($this->connectionName === ConnectionPool::DEFAULT_CONNECTION_NAME) {
            return $schemaDiff;
        }

        // Remove all tables that are not assigned to this connection from the diff
        $schemaDiff->createdTables = $this->removeUnrelatedTables($schemaDiff->createdTables, $tablesForConnection);
        $schemaDiff->alteredTables = $this->removeUnrelatedTables($schemaDiff->alteredTables, $tablesForConnection);
        $schemaDiff->droppedTables = $this->removeUnrelatedTables($schemaDiff->droppedTables, $tablesForConnection);

        return $schemaDiff;
    }

    protected function buildExistingSchemaDefinitions(): Schema
    {
        return $this->connection->createSchemaManager()->introspectSchema();
    }

    /**
     * Build the expected schema definitions from raw SQL statements.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    protected function buildExpectedSchemaDefinitions(string $connectionName): Schema
    {
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setName($this->connection->getDatabase());
        if (isset($this->connection->getParams()['defaultTableOptions'])) {
            $schemaConfig->setDefaultTableOptions($this->connection->getParams()['defaultTableOptions']);
        }
        /** @var Table[] $tablesForConnection */
        $tablesForConnection = [];
        foreach ($this->tables as $table) {
            $tableName = $table->getName();

            // Skip tables for a different connection
            if ($connectionName !== $this->getConnectionNameForTable($tableName)) {
                continue;
            }
            $table->setSchemaConfig($schemaConfig);
            $tablesForConnection[$tableName] = $table;
        }
        $tablesForConnection = $this->transformTablesForDatabasePlatform($this->connection, $schemaConfig, $tablesForConnection);
        return new Schema($tablesForConnection, [], $schemaConfig);
    }

    /**
     * Extract the update suggestions (SQL statements) for newly added tables
     * from the complete schema diff.
     *
     * @throws \InvalidArgumentException
     */
    protected function getNewTableUpdateSuggestions(Typo3SchemaDiff $schemaDiff): array
    {
        // Build a new schema diff that only contains added tables
        $addTableSchemaDiff = new Typo3SchemaDiff(
            // createdSchemas
            [],
            // droppedSchemas
            [],
            // createdTables
            $schemaDiff->getCreatedTables(),
            // alteredTables
            [],
            // droppedTables
            [],
            // createdSequences
            [],
            // alteredSequences
            [],
            // droppedSequences
            [],
        );

        $statements = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($addTableSchemaDiff);

        return ['create_table' => $this->calculateUpdateSuggestionsHashes($statements)];
    }

    /**
     * Extract the update suggestions (SQL statements) for newly added fields
     * from the complete schema diff.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function getNewFieldUpdateSuggestions(Typo3SchemaDiff $schemaDiff): array
    {
        $changedTables = [];

        foreach ($schemaDiff->alteredTables as $index => $changedTable) {
            if (count($changedTable->addedColumns) !== 0) {
                // Treat each added column with a new diff to get a dedicated suggestions
                // just for this single column.
                foreach ($changedTable->addedColumns as $columnName => $addedColumn) {
                    $changedTables[$index . ':tbl_' . $columnName] = new Typo3TableDiff(
                        // oldTable
                        $this->buildQuotedTable($changedTable->getOldTable()),
                        // addedColumns
                        [$columnName => $addedColumn],
                        // modifiedColumns
                        [],
                        // droppedColumns
                        [],
                        // renamedColumns
                        [],
                        // addedIndexes
                        [],
                        // modifiedIndexes
                        [],
                        // droppedIndexes
                        [],
                        // renamedIndexes
                        [],
                        // addedForeignKeys
                        [],
                        // modifiedForeignKeys
                        [],
                        // droppedForeignKeys
                        [],
                    );
                }
            }

            if (count($changedTable->addedIndexes) !== 0) {
                // Treat each added index with a new diff to get a dedicated suggestions
                // just for this index.
                foreach ($changedTable->addedIndexes as $indexName => $addedIndex) {
                    $changedTables[$index . ':idx_' . $indexName] = new Typo3TableDiff(
                        // oldTable
                        $this->buildQuotedTable($changedTable->getOldTable()),
                        // addedColumns
                        [],
                        // modifiedColumns
                        [],
                        // droppedColumns
                        [],
                        // renamedColumns
                        [],
                        // addedIndexes
                        [$indexName => $this->buildQuotedIndex($addedIndex)],
                        // modifiedIndexes
                        [],
                        // droppedIndexes
                        [],
                        // renamedIndexes
                        [],
                        // addedForeignKeys
                        [],
                        // modifiedForeignKeys
                        [],
                        // droppedForeignKeys
                        [],
                    );
                }
            }

            if (count($changedTable->addedForeignKeys) !== 0) {
                // Treat each added foreign key with a new diff to get a dedicated suggestions
                // just for this foreign key.
                foreach ($changedTable->addedForeignKeys as $addedForeignKey) {
                    $fkIndex = $index . ':fk_' . $addedForeignKey->getName();
                    $changedTables[$fkIndex] = new Typo3TableDiff(
                        // oldTable
                        $this->buildQuotedTable($changedTable->getOldTable()),
                        // addedColumns
                        [],
                        // modifiedColumns
                        [],
                        // droppedColumns
                        [],
                        // renamedColumns
                        [],
                        // addedIndexes
                        [],
                        // modifiedIndexes
                        [],
                        // droppedIndexes
                        [],
                        // renamedIndexes
                        [],
                        // addedForeignKeys
                        [$this->buildQuotedForeignKey($addedForeignKey)],
                        // modifiedForeignKeys
                        [],
                        // droppedForeignKeys
                        [],
                    );
                }
            }
        }

        // Build a new schema diff that only contains added fields
        $addFieldSchemaDiff = new Typo3SchemaDiff(
            // createdSchemas
            [],
            // droppedSchemas
            [],
            // createdTables
            [],
            // alteredTables
            $changedTables,
            // droppedTables
            [],
            // createdSequences
            [],
            // alteredSequences
            [],
            // droppedSequences
            [],
        );

        $statements = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($addFieldSchemaDiff);

        return ['add' => $this->calculateUpdateSuggestionsHashes($statements)];
    }

    /**
     * Extract update suggestions (SQL statements) for changed options (like ENGINE) from the complete schema diff.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function getChangedTableOptions(Typo3SchemaDiff $schemaDiff): array
    {
        $updateSuggestions = [];

        foreach ($schemaDiff->alteredTables as $index => $tableDiff) {
            // Skip processing if this is the base TableDiff class or has no table options set.
            if (!$tableDiff instanceof Typo3TableDiff || count($tableDiff->getTableOptions()) === 0) {
                continue;
            }

            $tableOptions = $tableDiff->getTableOptions();
            $tableOptionsDiff = new Typo3TableDiff(
                // oldTable
                $tableDiff->getOldTable(),
                // addedColumns
                [],
                // modifiedColumns
                [],
                // droppedColumns
                [],
                // renamedColumns
                [],
                // addedIndexes
                [],
                // modifiedIndexes
                [],
                // droppedIndexes
                [],
                // renamedIndexes
                [],
                // addedForeignKeys
                [],
                // modifiedForeignKeys
                [],
                // droppedForeignKeys
                [],
            );
            $tableOptionsDiff->setTableOptions($tableOptions);

            $tableOptionsSchemaDiff = new Typo3SchemaDiff(
                // createdSchemas
                [],
                // droppedSchemas
                [],
                // createdTables
                [],
                // alteredTables
                [$index => $tableOptionsDiff],
                // droppedTables
                [],
                // createdSequences
                [],
                // alteredSequences
                [],
                // droppedSequences
                [],
            );

            $statements = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($tableOptionsSchemaDiff);
            foreach ($statements as $statement) {
                $updateSuggestions['change'][md5($statement)] = $statement;
            }
        }

        return $updateSuggestions;
    }

    /**
     * Extract update suggestions (SQL statements) for changed fields
     * from the complete schema diff.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function getChangedFieldUpdateSuggestions(Typo3SchemaDiff $schemaDiff): array
    {
        $databasePlatform = $this->connection->getDatabasePlatform();
        $updateSuggestions = [];

        foreach ($schemaDiff->alteredTables as $changedTable) {
            // Treat each changed index with a new diff to get a dedicated suggestions
            // just for this index.
            if (count($changedTable->modifiedIndexes) !== 0) {
                foreach ($changedTable->modifiedIndexes as $indexName => $changedIndex) {
                    $indexDiff = new Typo3TableDiff(
                        // oldTable
                        $changedTable->getOldTable(),
                        // addedColumns
                        [],
                        // modifiedColumns
                        [],
                        // droppedColumns
                        [],
                        // renamedColumns
                        [],
                        // addedIndexes
                        [],
                        // modifiedIndexes
                        [$indexName => $changedIndex],
                        // droppedIndexes
                        [],
                        // renamedIndexes
                        [],
                        // addedForeignKeys
                        [],
                        // modifiedForeignKeys
                        [],
                        // droppedForeignKeys
                        [],
                    );

                    $temporarySchemaDiff = new Typo3SchemaDiff(
                        // createdSchemas
                        [],
                        // droppedSchemas
                        [],
                        // createdTables
                        [],
                        // alteredTables
                        [$changedTable->getOldTable()->getName() => $indexDiff],
                        // droppedTables
                        [],
                        // createdSequences
                        [],
                        // alteredSequences
                        [],
                        // droppedSequences
                        [],
                    );

                    $statements = $databasePlatform->getAlterSchemaSQL($temporarySchemaDiff);
                    foreach ($statements as $statement) {
                        $updateSuggestions['change'][md5($statement)] = $statement;
                    }
                }
            }

            // Treat renamed indexes as a field change as it's a simple rename operation
            if (count($changedTable->renamedIndexes) !== 0) {
                // Create a base table diff without any changes, there's no constructor
                // argument to pass in renamed indexes.
                $tableDiff = new Typo3TableDiff(
                    // oldTable
                    $changedTable->getOldTable(),
                    // addedColumns
                    [],
                    // modifiedColumns
                    [],
                    // droppedColumns
                    [],
                    // renamedColumns
                    [],
                    // addedIndexes
                    [],
                    // modifiedIndexes
                    [],
                    // droppedIndexes
                    [],
                    // renamedIndexes
                    [],
                    // addedForeignKeys
                    [],
                    // modifiedForeignKeys
                    [],
                    // droppedForeignKeys
                    [],
                );

                // Treat each renamed index with a new diff to get a dedicated suggestions
                // just for this index.
                foreach ($changedTable->renamedIndexes as $key => $renamedIndex) {
                    $indexDiff = clone $tableDiff;
                    $indexDiff->renamedIndexes = [
                        $changedTable->getOldTable()->getIndex($key)->getQuotedName($databasePlatform) => $renamedIndex,
                    ];

                    $temporarySchemaDiff = new Typo3SchemaDiff(
                        // createdSchemas
                        [],
                        // droppedSchemas
                        [],
                        // createdTables
                        [],
                        // alteredTables
                        [$indexDiff->getOldTable()->getQuotedName($databasePlatform) => $indexDiff],
                        // droppedTables
                        [],
                        // createdSequences
                        [],
                        // alteredSequences
                        [],
                        // droppedSequences
                        [],
                    );

                    $statements = $databasePlatform->getAlterSchemaSQL($temporarySchemaDiff);
                    foreach ($statements as $statement) {
                        $updateSuggestions['change'][md5($statement)] = $statement;
                    }
                }
            }

            if (count($changedTable->modifiedColumns) !== 0) {
                // Treat each changed column with a new diff to get a dedicated suggestions
                // just for this single column.
                foreach ($changedTable->modifiedColumns as $columnName => $changedColumn) {
                    // Field has been renamed and will be handled separately
                    if ($changedColumn->getOldColumn()->getName() !== $changedColumn->getNewColumn()->getName()) {
                        continue;
                    }

                    if ($changedColumn->getOldColumn() !== null) {
                        $changedColumn->oldColumn = $this->buildQuotedColumn($changedColumn->oldColumn);
                    }

                    // Get the current SQL declaration for the column
                    $currentColumn = $changedColumn->getOldColumn();
                    $currentDeclaration = $databasePlatform->getColumnDeclarationSQL(
                        $currentColumn->getQuotedName($this->connection->getDatabasePlatform()),
                        $currentColumn->toArray()
                    );

                    // Build a dedicated diff just for the current column
                    $tableDiff = new Typo3TableDiff(
                        // oldTable
                        $this->buildQuotedTable($changedTable->getOldTable()),
                        // addedColumns
                        [],
                        // modifiedColumns
                        [$columnName => $changedColumn],
                        // droppedColumns
                        [],
                        // renamedColumns
                        [],
                        // addedIndexes
                        [],
                        // modifiedIndexes
                        [],
                        // droppedIndexes
                        [],
                        // renamedIndexes
                        [],
                        // addedForeignKeys
                        [],
                        // modifiedForeignKeys
                        [],
                        // droppedForeignKeys
                        [],
                    );
                    $temporarySchemaDiff = new Typo3SchemaDiff(
                        // createdSchemas
                        [],
                        // droppedSchemas
                        [],
                        // createdTables
                        [],
                        // alteredTables
                        [$tableDiff->getOldTable()->getName() => $tableDiff],
                        // droppedTables
                        [],
                        // createdSequences
                        [],
                        // alteredSequences
                        [],
                        // droppedSequences
                        [],
                    );

                    // Get missing update statements to mimic documented Doctrine DBAL 4 SERIAL to IDENTITY column
                    // migration without loosing sequence table data.
                    // @see https://github.com/doctrine/dbal/blob/4.0.x/docs/en/how-to/postgresql-identity-migration.rst
                    $postgreSQLMigrationStatements = $this->getPostgreSQLMigrationStatements($this->connection, $changedTable, $changedColumn);
                    $indexedSearchPrerequisiteStatements = $this->getIndexedSearchTruncateTablePrerequisiteStatements($this->connection, $changedTable, $changedColumn);
                    if ($indexedSearchPrerequisiteStatements !== []) {
                        foreach ($indexedSearchPrerequisiteStatements as $statement => $reason) {
                            $updateSuggestions['change'][md5($statement)] = $statement;
                            if ($reason !== '') {
                                $updateSuggestions['change_currentValue'][md5($statement)] = $reason;
                            }
                        }
                    }
                    $statements = $databasePlatform->getAlterSchemaSQL($temporarySchemaDiff);
                    foreach ($statements as $statement) {
                        // Combine SERIAL to IDENTITY COLUMN date migration statements to the statement
                        // @todo This is a hackish way to provide data migration along with DDL changes in a connected
                        //       way. There is currently no other way to archive this and again emphasizes the need to
                        //       refactor the complete database analyzer stack and handling.
                        if ($postgreSQLMigrationStatements !== []) {
                            if (str_contains($statement, 'DROP DEFAULT')) {
                                $statement = rtrim($statement, '; ') . ';' . implode(';', $postgreSQLMigrationStatements);
                            }
                            if (str_contains($statement, 'ADD GENERATED BY DEFAULT AS IDENTITY')) {
                                // Due to the proper migration replacement we need to skip the Doctrine DBAL add statement
                                // which will fail anyway - and is covered by the manual update above. This ensures, that
                                // the sequence table is not dropped and recreated with empty state.
                                continue;
                            }
                        }
                        $updateSuggestions['change'][md5($statement)] = $statement;
                        $updateSuggestions['change_currentValue'][md5($statement)] = $currentDeclaration;
                    }
                }
            }

            // Treat each changed foreign key with a new diff to get a dedicated suggestions
            // just for this foreign key.
            if (count($changedTable->modifiedForeignKeys) !== 0) {
                $tableDiff = new Typo3TableDiff(
                    // oldTable
                    $changedTable->getOldTable(),
                    // addedColumns
                    [],
                    // modifiedColumns
                    [],
                    // droppedColumns
                    [],
                    // renamedColumns
                    [],
                    // addedIndexes
                    [],
                    // modifiedIndexes
                    [],
                    // droppedIndexes
                    [],
                    // renamedIndexes
                    [],
                    // addedForeignKeys
                    [],
                    // modifiedForeignKeys
                    [],
                    // droppedForeignKeys
                    [],
                );

                foreach ($changedTable->modifiedForeignKeys as $changedForeignKey) {
                    $foreignKeyDiff = clone $tableDiff;
                    $foreignKeyDiff->modifiedForeignKeys = [$this->buildQuotedForeignKey($changedForeignKey)];

                    $temporarySchemaDiff = new Typo3SchemaDiff(
                        // createdSchemas
                        [],
                        // droppedSchemas
                        [],
                        // createdTables
                        [],
                        // alteredTables
                        [$foreignKeyDiff->getOldTable()->getName() => $foreignKeyDiff],
                        // droppedTables
                        [],
                        // createdSequences
                        [],
                        // alteredSequences
                        [],
                        // droppedSequences
                        [],
                    );

                    $statements = $databasePlatform->getAlterSchemaSQL($temporarySchemaDiff);
                    foreach ($statements as $statement) {
                        $updateSuggestions['change'][md5($statement)] = $statement;
                    }
                }
            }
        }

        return $updateSuggestions;
    }

    /**
     * Extract update suggestions (SQL statements) for tables that are
     * no longer present in the expected schema from the schema diff.
     * In this case the update suggestions are renames of the tables
     * with a prefix to mark them for deletion in a second sweep.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function getUnusedTableUpdateSuggestions(Typo3SchemaDiff $schemaDiff): array
    {
        $databasePlatform = $this->connection->getDatabasePlatform();
        $updateSuggestions = [];
        foreach ($schemaDiff->alteredTables as $tableDiff) {
            // Skip tables that are not being renamed or where the new name isn't prefixed
            // with the deletion marker.
            if ($tableDiff->getNewName() === null
                || !str_starts_with($this->trimIdentifierQuotes($tableDiff->getNewName()), $this->deletedPrefix)
            ) {
                continue;
            }

            $statement = $databasePlatform->getRenameTableSQL(
                $tableDiff->getOldTable()->getQuotedName($databasePlatform),
                $tableDiff->newName
            );
            $updateSuggestions['change_table'][md5($statement)] = $statement;
            $updateSuggestions['tables_count'][md5($statement)] = $this->getTableRecordCount($tableDiff->getOldTable()->getName());
        }

        return $updateSuggestions;
    }

    /**
     * Extract update suggestions (SQL statements) for fields that are
     * no longer present in the expected schema from the schema diff.
     * In this case the update suggestions are renames of the fields
     * with a prefix to mark them for deletion in a second sweep.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function getUnusedFieldUpdateSuggestions(Typo3SchemaDiff $schemaDiff): array
    {
        $databasePlatform = $this->connection->getDatabasePlatform();
        $changedTables = [];
        foreach ($schemaDiff->alteredTables as $tableName => $changedTable) {
            if (count($changedTable->modifiedColumns) === 0) {
                continue;
            }

            // Treat each changed column with a new diff to get a dedicated suggestions
            // just for this single column.
            foreach ($changedTable->modifiedColumns as $index => $changedColumn) {
                // Field has not been renamed
                if ($changedColumn->getOldColumn()->getName() === $changedColumn->getNewColumn()->getName()) {
                    continue;
                }

                $oldFieldName = $changedColumn->getOldColumn()->getQuotedName($databasePlatform);
                $renameColumnTableDiff = new Typo3TableDiff(
                    // oldTable
                    $this->buildQuotedTable($changedTable->getOldTable()),
                    // addedColumns
                    [],
                    // modifiedColumns
                    [$oldFieldName => $changedColumn],
                    // droppedColumns
                    [],
                    // renamedColumns
                    [],
                    // addedIndexes
                    [],
                    // modifiedIndexes
                    [],
                    // droppedIndexes
                    [],
                    // renamedIndexes
                    [],
                    // addedForeignKeys
                    [],
                    // modifiedForeignKeys
                    [],
                    // droppedForeignKeys
                    [],
                );
                if ($databasePlatform instanceof DoctrinePostgreSQLPlatform) {
                    $renameColumnTableDiff->renamedColumns[$oldFieldName] = $changedColumn->getNewColumn();
                }
                $changedTables[$tableName . ':' . $changedColumn->getNewColumn()->getName()] = $renameColumnTableDiff;

                if ($databasePlatform instanceof DoctrineSQLitePlatform) {
                    break;
                }
            }
        }

        // Build a new schema diff that only contains unused fields
        $changedFieldDiff = new Typo3SchemaDiff(
            // createdSchemas
            [],
            // droppedSchemas
            [],
            // createdTables
            [],
            // alteredTables
            $changedTables,
            // droppedTables
            [],
            // createdSequences
            [],
            // alteredSequences
            [],
            // droppedSequences
            [],
        );

        $statements = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($changedFieldDiff);

        return ['change' => $this->calculateUpdateSuggestionsHashes($statements)];
    }

    /**
     * Extract update suggestions (SQL statements) for fields that can
     * be removed from the complete schema diff.
     * Fields that can be removed have been prefixed in a previous run
     * of the schema migration.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function getDropFieldUpdateSuggestions(Typo3SchemaDiff $schemaDiff): array
    {
        $changedTables = [];

        foreach ($schemaDiff->alteredTables as $index => $changedTable) {
            $isSqlite = $this->getDatabasePlatformForTable($index) instanceof DoctrineSQLitePlatform;
            $addMoreOperations = true;

            if (count($changedTable->droppedColumns) !== 0) {
                // Treat each changed column with a new diff to get a dedicated suggestions
                // just for this single column.
                foreach ($changedTable->droppedColumns as $columnName => $removedColumn) {
                    $changedTables[$index . ':tbl_' . $removedColumn->getName()] = new Typo3TableDiff(
                        // oldTable
                        $this->buildQuotedTable($changedTable->getOldTable()),
                        // addedColumns
                        [],
                        // modifiedColumns
                        [],
                        // droppedColumns
                        [$columnName => $this->buildQuotedColumn($removedColumn)],
                        // renamedColumns
                        [],
                        // addedIndexes
                        [],
                        // modifiedIndexes
                        [],
                        // droppedIndexes
                        [],
                        // renamedIndexes
                        [],
                        // addedForeignKeys
                        [],
                        // modifiedForeignKeys
                        [],
                        // droppedForeignKeys
                        [],
                    );
                    if ($isSqlite) {
                        $addMoreOperations = false;
                        break;
                    }
                }
            }

            if ($addMoreOperations && count($changedTable->droppedIndexes) !== 0) {
                // Treat each removed index with a new diff to get a dedicated suggestions
                // just for this index.
                foreach ($changedTable->droppedIndexes as $indexName => $removedIndex) {
                    $changedTables[$index . ':idx_' . $removedIndex->getName()] = new Typo3TableDiff(
                        // oldTable
                        $this->buildQuotedTable($changedTable->getOldTable()),
                        // addedColumns
                        [],
                        // modifiedColumns
                        [],
                        // droppedColumns
                        [],
                        // renamedColumns
                        [],
                        // addedIndexes
                        [],
                        // modifiedIndexes
                        [],
                        // droppedIndexes
                        [$indexName => $this->buildQuotedIndex($removedIndex)],
                        // renamedIndexes
                        [],
                        // addedForeignKeys
                        [],
                        // modifiedForeignKeys
                        [],
                        // droppedForeignKeys
                        [],
                    );
                    if ($isSqlite) {
                        $addMoreOperations = false;
                        break;
                    }
                }
            }

            if ($addMoreOperations && count($changedTable->droppedForeignKeys) !== 0) {
                // Treat each removed foreign key with a new diff to get a dedicated suggestions
                // just for this foreign key.
                foreach ($changedTable->droppedForeignKeys as $removedForeignKey) {
                    $fkIndex = $index . ':fk_' . $removedForeignKey->getName();
                    $changedTables[$fkIndex] = new Typo3TableDiff(
                        // oldTable
                        $this->buildQuotedTable($changedTable->getOldTable()),
                        // addedColumns
                        [],
                        // modifiedColumns
                        [],
                        // droppedColumns
                        [],
                        // renamedColumns
                        [],
                        // addedIndexes
                        [],
                        // modifiedIndexes
                        [],
                        // droppedIndexes
                        [],
                        // renamedIndexes
                        [],
                        // addedForeignKeys
                        [],
                        // modifiedForeignKeys
                        [],
                        // droppedForeignKeys
                        [$this->buildQuotedForeignKey($removedForeignKey)],
                    );
                    if ($isSqlite) {
                        break;
                    }
                }
            }
        }

        // Build a new schema diff that only contains removable fields
        $removedFieldDiff = new Typo3SchemaDiff(
            // createdSchemas
            [],
            // droppedSchemas
            [],
            // createdTables
            [],
            // alteredTables
            $changedTables,
            // droppedTables
            [],
            // createdSequences
            [],
            // alteredSequences
            [],
            // droppedSequences
            [],
        );

        $statements = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($removedFieldDiff);

        return ['drop' => $this->calculateUpdateSuggestionsHashes($statements)];
    }

    /**
     * Extract update suggestions (SQL statements) for tables that can
     * be removed from the complete schema diff.
     * Tables that can be removed have been prefixed in a previous run
     * of the schema migration.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function getDropTableUpdateSuggestions(Typo3SchemaDiff $schemaDiff): array
    {
        $updateSuggestions = [];
        foreach ($schemaDiff->droppedTables as $index => $removedTable) {
            // Build a new schema diff that only contains this table
            $tableDiff = new Typo3SchemaDiff(
                // createdSchemas
                [],
                // droppedSchemas
                [],
                // createdTables
                [],
                // alteredTables
                [],
                // droppedTables
                [$index => $this->buildQuotedTable($removedTable)],
                // createdSequences
                [],
                // alteredSequences
                [],
                // droppedSequences
                [],
            );

            $statements = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($tableDiff);
            foreach ($statements as $statement) {
                $updateSuggestions['drop_table'][md5($statement)] = $statement;
            }

            // Only store the record count for this table for the first statement,
            // assuming that this is the actual DROP TABLE statement.
            $updateSuggestions['tables_count'][md5($statements[0])] = $this->getTableRecordCount(
                $removedTable->getName()
            );
        }

        return $updateSuggestions;
    }

    /**
     * Move tables to be removed that are not prefixed with the deleted prefix to the list
     * of changed tables and set a new prefixed name.
     * Without this help the Doctrine SchemaDiff has no idea if a table has been renamed and
     * performs a drop of the old table and creates a new table, which leads to all data in
     * the old table being lost.
     *
     * @throws \InvalidArgumentException
     */
    protected function migrateUnprefixedRemovedTablesToRenames(Typo3SchemaDiff $schemaDiff): Typo3SchemaDiff
    {
        foreach ($schemaDiff->droppedTables as $index => $removedTable) {
            if (str_starts_with($this->trimIdentifierQuotes($removedTable->getName()), $this->deletedPrefix)) {
                continue;
            }
            $tableDiff = new Typo3TableDiff(
                // oldTable
                $this->buildQuotedTable($removedTable),
                // addedColumns
                [],
                // modifiedColumns
                [],
                // droppedColumns
                [],
                // renamedColumns
                [],
                // addedIndexes
                [],
                // modifiedIndexes
                [],
                // droppedIndexes
                [],
                // renamedIndexes
                [],
                // addedForeignKeys
                [],
                // modifiedForeignKeys
                [],
                // droppedForeignKeys
                [],
            );

            $tableDiff->newName = $this->connection->getDatabasePlatform()->quoteIdentifier(
                substr(
                    $this->deletedPrefix . $removedTable->getName(),
                    0,
                    PlatformInformation::getMaxIdentifierLength($this->connection->getDatabasePlatform())
                )
            );
            $schemaDiff->alteredTables[$index] = $tableDiff;
            unset($schemaDiff->droppedTables[$index]);
        }

        return $schemaDiff;
    }

    /**
     * Scan the list of changed tables for fields that are going to be dropped. If
     * the name of the field does not start with the deleted prefix mark the column
     * for a rename instead of a drop operation.
     *
     * @throws \InvalidArgumentException
     */
    protected function migrateUnprefixedRemovedFieldsToRenames(Typo3SchemaDiff $schemaDiff): Typo3SchemaDiff
    {
        foreach ($schemaDiff->alteredTables as $tableIndex => $changedTable) {
            if (count($changedTable->droppedColumns) === 0) {
                continue;
            }

            foreach ($changedTable->droppedColumns as $columnIndex => $removedColumn) {
                if (str_starts_with($this->trimIdentifierQuotes($removedColumn->getName()), $this->deletedPrefix)) {
                    continue;
                }

                // Build a new column object with the same properties as the removed column
                $renamedColumnName = substr(
                    $this->deletedPrefix . $removedColumn->getName(),
                    0,
                    PlatformInformation::getMaxIdentifierLength($this->connection->getDatabasePlatform())
                );
                $renamedColumn = new Column(
                    $this->connection->quoteIdentifier($renamedColumnName),
                    $removedColumn->getType(),
                    $this->prepareColumnOptions($removedColumn)
                );

                // Build the diff object for the column to rename
                $columnDiff = new Typo3ColumnDiff($this->buildQuotedColumn($removedColumn), $renamedColumn);

                // Add the column with the required rename information to the changed column list
                $schemaDiff->alteredTables[$tableIndex]->modifiedColumns[$columnIndex] = $columnDiff;

                // Remove the column from the list of columns to be dropped
                unset($schemaDiff->alteredTables[$tableIndex]->droppedColumns[$columnIndex]);
            }
        }

        return $schemaDiff;
    }

    /**
     * Revert the automatic rename optimization that Doctrine performs when it detects
     * a column being added and a column being dropped that only differ by name.
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     */
    protected function migrateColumnRenamesToDistinctActions(Typo3SchemaDiff $schemaDiff): Typo3SchemaDiff
    {
        foreach ($schemaDiff->alteredTables as $changedTable) {
            if (count($changedTable->getRenamedColumns()) === 0) {
                continue;
            }

            // Treat each renamed column with a new diff to get a dedicated
            // suggestion just for this single column.
            foreach ($changedTable->renamedColumns as $originalColumnName => $renamedColumn) {
                $columnOptions = $this->prepareColumnOptions($renamedColumn);
                $changedTable->addedColumns[$renamedColumn->getName()] = new Column(
                    $renamedColumn->getName(),
                    $renamedColumn->getType(),
                    $columnOptions
                );
                $changedTable->droppedColumns[$originalColumnName] = new Column(
                    $originalColumnName,
                    $renamedColumn->getType(),
                    $columnOptions
                );

                unset($changedTable->renamedColumns[$originalColumnName]);
            }
        }

        return $schemaDiff;
    }

    /**
     * Return the amount of records in the given table.
     *
     * @throws \InvalidArgumentException
     */
    protected function getTableRecordCount(string $tableName): int
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tableName)
            ->count('*', $tableName, []);
    }

    /**
     * Determine the connection name for a table
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnectionNameForTable(string $tableName): string
    {
        $connectionNames = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames();

        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName])) {
            return in_array($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName], $connectionNames, true)
                ? $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName]
                : ConnectionPool::DEFAULT_CONNECTION_NAME;
        }

        return ConnectionPool::DEFAULT_CONNECTION_NAME;
    }

    /**
     * Replace the array keys with a md5 sum of the actual SQL statement
     *
     * @param string[] $statements
     * @return array<non-empty-string, non-empty-string>
     */
    protected function calculateUpdateSuggestionsHashes(array $statements): array
    {
        return array_combine(array_map(md5(...), $statements), $statements);
    }

    /**
     * Helper for buildSchemaDiff to filter an array of TableDiffs against a list of valid table names.
     *
     * @param array<non-empty-string, Typo3TableDiff|\Doctrine\DBAL\Schema\TableDiff|Table> $tableDiffs
     * @param string[] $validTableNames
     * @return array<non-empty-string, Typo3TableDiff|Table>
     * @throws \InvalidArgumentException
     */
    protected function removeUnrelatedTables(array $tableDiffs, array $validTableNames): array
    {
        $tableDiffs = array_filter(
            $tableDiffs,
            function (Typo3TableDiff|Table $table) use ($validTableNames): bool {
                if ($table instanceof Table) {
                    $tableName = $table->getName();
                } else {
                    $tableName = $table->getNewName() ?? $table->getOldTable()->getName();
                }

                // If the tablename has a deleted prefix strip it of before comparing
                // it against the list of valid table names so that drop operations
                // don't get removed.
                if (str_starts_with($this->trimIdentifierQuotes($tableName), $this->deletedPrefix)) {
                    $tableName = substr($tableName, strlen($this->deletedPrefix));
                }
                return in_array($tableName, $validTableNames, true)
                    || in_array($this->deletedPrefix . $tableName, $validTableNames, true);
            }
        );
        foreach ($tableDiffs as &$tableDiff) {
            if ($tableDiff instanceof Table) {
                continue;
            }
            if (! $tableDiff instanceof Typo3TableDiff) {
                $tableDiff = Typo3TableDiff::ensure($tableDiff);
            }
        }
        return $tableDiffs;
    }

    /**
     * Transform the table information to conform to specific
     * requirements of different database platforms like removing
     * the index substring length for Non-MySQL Platforms.
     *
     * @param Table[] $tables
     * @return Table[]
     * @throws \InvalidArgumentException
     */
    protected function transformTablesForDatabasePlatform(Typo3Connection $connection, SchemaConfig $schemaConfig, array $tables): array
    {
        $defaultTableOptions = $schemaConfig->getDefaultTableOptions();
        $tables = $this->normalizeTablesForTargetConnection($connection, $schemaConfig, $tables);
        foreach ($tables as &$table) {
            $indexes = [];
            foreach ($table->getIndexes() as $key => $index) {
                $indexName = $index->getName();
                // PostgreSQL and sqlite require index names to be unique per database/schema.
                $platform = $connection->getDatabasePlatform();
                if ($platform instanceof DoctrinePostgreSQLPlatform || $platform instanceof DoctrineSQLitePlatform) {
                    $indexName = $indexName . '_' . hash('crc32b', $table->getName() . '_' . $indexName);
                }

                // Remove the length information from column names for indexes if required.
                $cleanedColumnNames = array_map(
                    static function (string $columnName) use ($connection): string {
                        $platform = $connection->getDatabasePlatform();
                        if ($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform) {
                            // Returning the unquoted, unmodified version of the column name since
                            // it can include the length information for BLOB/TEXT columns which
                            // may not be quoted.
                            return $columnName;
                        }

                        return $connection->quoteIdentifier(preg_replace('/\(\d+\)$/', '', $columnName));
                    },
                    $index->getUnquotedColumns()
                );

                $indexes[$key] = new Index(
                    $connection->quoteIdentifier($indexName),
                    $cleanedColumnNames,
                    $index->isUnique(),
                    $index->isPrimary(),
                    $index->getFlags(),
                    $index->getOptions()
                );
            }

            $table = new Table(
                $table->getQuotedName($connection->getDatabasePlatform()),
                $table->getColumns(),
                $indexes,
                [],
                $table->getForeignKeys(),
                array_merge($defaultTableOptions, $table->getOptions())
            );
            $table->setSchemaConfig($schemaConfig);
        }

        return $tables;
    }

    /**
     * Get COLLATION, ROW_FORMAT, COMMENT and ENGINE table options on MySQL connections.
     *
     * @param string[] $tableNames
     * @return array[]
     * @throws \InvalidArgumentException
     */
    protected function getTableOptions(array $tableNames): array
    {
        $tableOptions = [];
        $platform = $this->connection->getDatabasePlatform();
        if (!($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform)) {
            foreach ($tableNames as $tableName) {
                $tableOptions[$tableName] = [];
            }

            return $tableOptions;
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $result = $queryBuilder
            ->select(
                'tables.TABLE_NAME AS table',
                'tables.ENGINE AS engine',
                'tables.ROW_FORMAT AS row_format',
                'tables.TABLE_COLLATION AS collate',
                'tables.TABLE_COMMENT AS comment',
                'CCSA.character_set_name AS charset'
            )
            ->from('information_schema.TABLES', 'tables')
            ->join(
                'tables',
                'information_schema.COLLATION_CHARACTER_SET_APPLICABILITY',
                'CCSA',
                $queryBuilder->expr()->eq(
                    'CCSA.collation_name',
                    $queryBuilder->quoteIdentifier('tables.table_collation')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'TABLE_TYPE',
                    $queryBuilder->createNamedParameter('BASE TABLE')
                ),
                $queryBuilder->expr()->eq(
                    'TABLE_SCHEMA',
                    $queryBuilder->createNamedParameter($this->connection->getDatabase())
                )
            )
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $index = $row['table'];
            unset($row['table']);
            $tableOptions[$index] = $row;
        }

        return $tableOptions;
    }

    /**
     * Helper function to build a table object that has the _quoted attribute set so that the SchemaManager
     * will use quoted identifiers when creating the final SQL statements. This is needed as Doctrine doesn't
     * provide a method to set the flag after the object has been instantiated and there's no possibility to
     * hook into the createSchema() method early enough to influence the original table object.
     */
    protected function buildQuotedTable(Table $table): Table
    {
        $databasePlatform = $this->connection->getDatabasePlatform();

        return new Table(
            $databasePlatform->quoteIdentifier($table->getName()),
            $table->getColumns(),
            $table->getIndexes(),
            [],
            $table->getForeignKeys(),
            $table->getOptions()
        );
    }

    /**
     * Helper function to build a column object that has the _quoted attribute set so that the SchemaManager
     * will use quoted identifiers when creating the final SQL statements. This is needed as Doctrine doesn't
     * provide a method to set the flag after the object has been instantiated and there's no possibility to
     * hook into the createSchema() method early enough to influence the original column object.
     */
    protected function buildQuotedColumn(Column $column): Column
    {
        $databasePlatform = $this->connection->getDatabasePlatform();

        return new Column(
            $databasePlatform->quoteIdentifier($this->trimIdentifierQuotes($column->getName())),
            $column->getType(),
            $this->prepareColumnOptions($column)
        );
    }

    /**
     * Helper function to build an index object that has the _quoted attribute set so that the SchemaManager
     * will use quoted identifiers when creating the final SQL statements. This is needed as Doctrine doesn't
     * provide a method to set the flag after the object has been instantiated and there's no possibility to
     * hook into the createSchema() method early enough to influence the original column object.
     */
    protected function buildQuotedIndex(Index $index): Index
    {
        $databasePlatform = $this->connection->getDatabasePlatform();

        return new Index(
            $databasePlatform->quoteIdentifier($index->getName()),
            $index->getColumns(),
            $index->isUnique(),
            $index->isPrimary(),
            $index->getFlags(),
            $index->getOptions()
        );
    }

    /**
     * Helper function to build a foreign key constraint object that has the _quoted attribute set so that the
     * SchemaManager will use quoted identifiers when creating the final SQL statements. This is needed as Doctrine
     * doesn't provide a method to set the flag after the object has been instantiated and there's no possibility to
     * hook into the createSchema() method early enough to influence the original column object.
     */
    protected function buildQuotedForeignKey(ForeignKeyConstraint $index): ForeignKeyConstraint
    {
        $databasePlatform = $this->connection->getDatabasePlatform();

        return new ForeignKeyConstraint(
            $index->getLocalColumns(),
            $databasePlatform->quoteIdentifier($index->getForeignTableName()),
            $index->getForeignColumns(),
            $databasePlatform->quoteIdentifier($index->getName()),
            $index->getOptions()
        );
    }

    protected function prepareColumnOptions(Column $column): array
    {
        $options = $column->toArray();
        $platformOptions = $column->getPlatformOptions();
        foreach ($platformOptions as $optionName => $optionValue) {
            unset($options[$optionName]);
            if (!isset($options['platformOptions'])) {
                $options['platformOptions'] = [];
            }
            $options['platformOptions'][$optionName] = $optionValue;
        }
        unset($options['name'], $options['type']);
        return $options;
    }

    protected function getDatabasePlatformForTable(string $tableName): AbstractPlatform
    {
        $databasePlatform = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName)->getDatabasePlatform();
        return match (true) {
            $databasePlatform instanceof DoctrinePostgreSQLPlatform,
            $databasePlatform instanceof DoctrineSQLitePlatform,
            $databasePlatform instanceof DoctrineMariaDBPlatform,
            $databasePlatform instanceof DoctrineMySQLPlatform => $databasePlatform,
            default => throw new \RuntimeException(
                sprintf(
                    'Platform "%s" not supported for table "%s" connection.',
                    get_class($databasePlatform),
                    $tableName,
                ),
                1701619871
            ),
        };
    }

    protected function getSchemaTableNames(Schema $schema)
    {
        $tableNames = [];
        foreach ($schema->getTables() as $table) {
            $tableNames[] = $table->getName();
        }
        ksort($tableNames);
        return $tableNames;
    }

    /**
     * Due to portability reasons it is necessary to normalize the virtual generated schema against the target
     * connection platform.
     *
     * - SQLite: Needs some special treatment regarding autoincrement fields. [1]
     * - MySQL/MariaDB: varchar fields needs to have a length, but doctrine dropped the default size. This need's to be
     *   addressed in application code. [2][3]
     *
     * @see https://github.com/doctrine/dbal/commit/33555d36e7e7d07a5880e01 [1]
     * @see https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-abstractplatform-methods-that-describe-the-default-and-the-maximum-column-lengths [2]
     * @see https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md#bc-break-changes-in-handling-string-and-binary-columns [3]
     *
     * @param Table[] $tables
     * @return Table[]
     * @throws DBALException
     */
    protected function normalizeTablesForTargetConnection(Typo3Connection $connection, SchemaConfig $schemaConfig, array $tables): array
    {
        $databasePlatform = $connection->getDatabasePlatform();
        array_walk($tables, function (Table &$table) use ($databasePlatform, $schemaConfig): void {
            $table->setSchemaConfig($schemaConfig);
            $this->normalizeTableIdentifiers($databasePlatform, $table);
            $this->applyDefaultPlatformOptionsToColumns($databasePlatform, $schemaConfig, $table);
            $this->normalizeTableForMariaDBOrMySQL($databasePlatform, $table);
            $this->normalizeTableForPostgreSQL($databasePlatform, $table);
            $this->normalizeTableForSQLite($databasePlatform, $table);
        });

        return $tables;
    }

    /**
     * @param AbstractPlatform $platform
     * @param Table &$table
     */
    protected function normalizeTableIdentifiers(AbstractPlatform $platform, Table &$table): void
    {
        $table = new Table(
            // name
            $platform->quoteIdentifier($this->trimIdentifierQuotes($table->getName())),
            // columns
            $this->normalizeTableColumnIdentifiers($platform, $table->getColumns()),
            // indexes
            $this->normalizeTableIndexIdentifiers($platform, $table->getIndexes()),
            // uniqueConstraints
            $this->normalizeTableUniqueConstraintIdentifiers($platform, $table->getUniqueConstraints()),
            // fkConstraints
            $this->normalizeTableForeignKeyConstraints($platform, $table->getForeignKeys()),
            // options
            $table->getOptions(),
        );
    }

    protected function applyDefaultPlatformOptionsToColumns(AbstractPlatform $platform, SchemaConfig $schemaConfig, Table $table): void
    {
        $defaultTableOptions = $schemaConfig->getDefaultTableOptions();
        $defaultColumnCollation = $defaultTableOptions['collation'] ?? $defaultTableOptions['collate'] ?? '';
        $defaultColumCharset = $defaultTableOptions['charset'] ?? '';
        foreach ($table->getColumns() as $column) {
            $columnType = $column->getType();
            if (($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform)
                && (($columnType instanceof StringType || $columnType instanceof TextType))
            ) {
                $columnCollation = (string)($column->getPlatformOptions()['collation'] ?? '');
                $columnCharset = (string)($column->getPlatformOptions()['charset'] ?? '');
                if ($defaultColumnCollation !== '' && $columnCollation === '') {
                    $column->setPlatformOption('collation', $defaultColumnCollation);
                }
                if ($defaultColumCharset !== '' && $columnCharset === '') {
                    $column->setPlatformOption('charset', $defaultColumCharset);
                }
            }
            if ($platform instanceof DoctrineSQLitePlatform
                && ($columnType instanceof StringType || $columnType instanceof TextType || $columnType instanceof JsonType)
            ) {
                $column->setPlatformOption('collation', 'BINARY');
            }
        }
    }

    /**
     * @param AbstractPlatform $platform
     * @param ForeignKeyConstraint[] $foreignKeyConstraints
     * @return ForeignKeyConstraint[]
     */
    protected function normalizeTableForeignKeyConstraints(AbstractPlatform $platform, array $foreignKeyConstraints): array
    {
        $normalizedForeignKeyConstraints = [];
        foreach ($foreignKeyConstraints as $foreignKeyConstraint) {
            $normalizedForeignKeyConstraints[] = new ForeignKeyConstraint(
                // localColumnNames
                $foreignKeyConstraint->getQuotedLocalColumns($platform),
                // foreignTableName
                $platform->quoteIdentifier($this->trimIdentifierQuotes($foreignKeyConstraint->getForeignTableName())),
                // foreignColumnNames
                $foreignKeyConstraint->getQuotedForeignColumns($platform),
                // name
                $platform->quoteIdentifier($foreignKeyConstraint->getName()),
                // options
                $foreignKeyConstraint->getOptions(),
            );
        }
        return $normalizedForeignKeyConstraints;
    }

    /**
     * Ensure correct initialized identifier names for table unique constraints.
     *
     * @param UniqueConstraint[] $uniqueConstraints
     * @return UniqueConstraint[]
     */
    protected function normalizeTableUniqueConstraintIdentifiers(AbstractPlatform $platform, array $uniqueConstraints): array
    {
        $normalizedUniqueConstraints = [];
        foreach ($uniqueConstraints as $uniqueConstraint) {
            $columns = $uniqueConstraint->getColumns();
            foreach ($columns as &$column) {
                $column = $platform->quoteIdentifier($this->trimIdentifierQuotes($column));
            }
            $normalizedUniqueConstraints[] = new UniqueConstraint(
                // name
                $platform->quoteIdentifier($this->trimIdentifierQuotes($uniqueConstraint->getName())),
                // columns
                $columns,
                // flags
                $uniqueConstraint->getFlags(),
                // options
                $uniqueConstraint->getOptions(),
            );
        }
        return $normalizedUniqueConstraints;
    }

    /**
     * Ensure correct initialized identifier names for table indexes.
     *
     * @param AbstractPlatform $platform
     * @param Index[] $indexes
     * @return Index[]
     */
    protected function normalizeTableIndexIdentifiers(AbstractPlatform $platform, array $indexes): array
    {
        $normalizedIndexes = [];
        foreach ($indexes as $index) {
            $columns = $index->getColumns();
            foreach ($columns as &$column) {
                $column = $platform->quoteIdentifier($this->trimIdentifierQuotes($column));
            }
            $normalizedIndexes[] = new Index(
                // name
                $platform->quoteIdentifier($this->trimIdentifierQuotes($index->getName())),
                // columns
                $columns,
                // isUnique
                $index->isUnique(),
                // isPrimary
                $index->isPrimary(),
                // flags
                $index->getFlags(),
                // options
                $index->getOptions(),
            );
        }
        return $normalizedIndexes;
    }

    /**
     * Ensure correct initialized identifier names for table columns.
     *
     * @param AbstractPlatform $platform
     * @param Column[] $columns
     * @return Column[]
     */
    protected function normalizeTableColumnIdentifiers(AbstractPlatform $platform, array $columns): array
    {
        $normalizedColumns = [];
        foreach ($columns as $column) {
            // It seems that since Doctrine DBAL 4 matching the autoincrement column, when defined as `UNSIGNED` is
            // not working anymore. The platform always create a signed autoincrement primary key, and it looks that
            // this code has not changed between v3 and v4. It's mysterious why we need to remove the UNSIGNED flag
            // for autoincrement columns for SQLite.
            // @todo This needs further validation and investigation.
            if ($column->getAutoincrement() === true && $platform instanceof DoctrineSQLitePlatform) {
                // @todo why do we need this with Doctrine DBAL 4 ???
                $column->setUnsigned(false);
            }
            $columnData = $column->toArray();
            unset($columnData['name'], $columnData['type']);
            $normalizedColumns[] = new Column(
                // name
                $platform->quoteIdentifier($this->trimIdentifierQuotes($column->getName())),
                // type
                $column->getType(),
                // options
                $columnData,
            );
        }
        return $normalizedColumns;
    }

    /**
     * Doctrine DBAL 4+ removed the default length for string and binary fields, but they are required for MariaDB and
     * MySQL database backends. Therefore, we need to normalize the tables and set column length for fields not having
     * them.
     *
     * Missing column length may happen by the `DefaultTCASchema` enriched structure information, which is and should
     * be database vendor unaware. Therefore, we normalize this here now.
     *
     * @see https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md#bc-break-changes-in-handling-string-and-binary-columns
     * @see https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-abstractplatform-methods-that-describe-the-default-and-the-maximum-column-lengths
     */
    protected function normalizeTableForMariaDBOrMySQL(AbstractPlatform $databasePlatform, Table $table): void
    {
        if (!($databasePlatform instanceof DoctrineMariaDBPlatform || $databasePlatform instanceof DoctrineMySQLPlatform)) {
            return;
        }

        foreach ($table->getColumns() as $column) {
            if (!($column->getType() instanceof StringType || $column->getType() instanceof BinaryType)) {
                continue;
            }
            if ($column->getLength() !== null) {
                // Ensure not to exceed the maximum varchar or binary length
                if ($column->getLength() > 4000) {
                    // @todo Should a exception be thrown for this case ?
                    $column->setLength(4000);
                }
                continue;
            }

            // 255 has been the removed `AbstractPlatform->getVarcharDefaultLength()` and
            // `AbstractPlatform->getBinaryMaxLength()` value
            $column->setLength(255);
        }
    }

    /**
     * Normalize fields towards PostgreSQL compatibility.
     */
    protected function normalizeTableForPostgreSQL(AbstractPlatform $databasePlatform, Table $table): void
    {
        if (!($databasePlatform instanceof DoctrinePostgreSQLPlatform)) {
            return;
        }

        foreach ($table->getColumns() as $column) {
            // PostgreSQL does not support length definition for integer type fields. Therefore, we remove the pseudo
            // MySQL length information to avoid compare issues.
            if ((
                $column->getType() instanceof SmallIntType
                || $column->getType() instanceof IntegerType
                || $column->getType() instanceof BigIntType
            ) && $column->getLength() !== null
            ) {
                $column->setLength(null);
            }
        }
    }

    /**
     * Normalize fields towards SQLite compatibility.
     *
     * @see https://github.com/doctrine/dbal/commit/33555d36e7e7d07a5880e01
     */
    protected function normalizeTableForSQLite(AbstractPlatform $databasePlatform, Table $table): void
    {
        if (!($databasePlatform instanceof DoctrineSQLitePlatform)) {
            return;
        }

        foreach ($table->getColumns() as $column) {
            // Doctrine DBAL 4 no longer determines the field type taking field comments into account. Due to the fact
            // that SQLite does not provide a native JSON type, it is created as TEXT field type. In consequence, the
            // current way to compare columns this leads to a change look for JSON fields. To mitigate this, until the
            // real Doctrine DBAL 4 way to compare columns can be enabled we need to mirror that type transformation
            // on the virtual database schema and change the type here.
            // @see https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md#bc-break-removed-platform-commented-type-api
            if ($column->getType() instanceof JsonType) {
                $column->setType(new TextType());
            }
        }

        // doctrine/dbal detects both sqlite autoincrement variants (row_id alias and autoincrement) through assumptions
        // which have been made. TYPO3 reads the ext_tables.sql files as MySQL/MariaDB variant, thus not setting the
        // autoincrement value to true for the row_id alias variant, which leads to an endless missmatch during database
        // comparison. This method adopts the doctrine/dbal assumption and apply it to the meta schema to mitigate
        // endless database compare detections in these cases.
        //
        // @see https://github.com/doctrine/dbal/commit/33555d36e7e7d07a5880e01
        $primaryColumns = $table->getPrimaryKey()?->getColumns() ?? [];
        $primaryKeyColumnCount = count($primaryColumns);
        $firstPrimaryKeyColumnName = $primaryColumns[0] ?? '';
        $singlePrimaryKeyColumn = $table->hasColumn($firstPrimaryKeyColumnName)
            ? $table->getColumn($firstPrimaryKeyColumnName)
            : null;
        if ($primaryKeyColumnCount === 1
            && $singlePrimaryKeyColumn !== null
            && $singlePrimaryKeyColumn->getType() instanceof IntegerType
        ) {
            $singlePrimaryKeyColumn->setAutoincrement(true);
        }
    }

    /**
     * Trim all possible identifier quotes from identifier. This method has been cloned from Doctrine DBAL.
     *
     * @see \Doctrine\DBAL\Schema\AbstractAsset::trimQuotes()
     */
    private function trimIdentifierQuotes(string $identifier): string
    {
        return str_replace(['`', '"', '[', ']'], '', $identifier);
    }

    /**
     * Retrieve data migration statements for PostgreSQL SERIAL to IDENTITY autoincrement column changes.
     *
     * @see ConnectionMigrator::getChangedFieldUpdateSuggestions()
     *
     * @return string[]
     * @throws DBALException
     */
    private function getPostgreSQLMigrationStatements(Typo3Connection $connection, TableDiff $changedTable, ColumnDiff $modifiedColumn): array
    {
        $sequenceInfo = $this->getTableSequenceInformation($connection, $changedTable, $modifiedColumn);
        if ($sequenceInfo === null) {
            return [];
        }
        $newColumn = $modifiedColumn->getNewColumn();
        $tableName = $this->trimIdentifierQuotes($changedTable->getOldTable()->getName());
        $fieldName = $this->trimIdentifierQuotes($newColumn->getName());
        $seqId = $sequenceInfo['seqid'];
        $combinedStatementParts = [];
        // @todo use QueryBuilder to generate the upgrade statement
        $combinedStatementParts[] = sprintf(
            'UPDATE %s SET deptype = %s WHERE (classid, objid, objsubid) = (%s::regclass, %s, 0) AND deptype = %s',
            $connection->quoteIdentifier('pg_depend'),
            $connection->quote('i'),
            $connection->quote('pg_class'),
            $connection->quote((string)$seqId),
            $connection->quote('a'),
        );
        // mark the column as identity column
        // @todo use QueryBuilder to generate the upgrade statement
        $combinedStatementParts[] = sprintf(
            'UPDATE %s SET attidentity = %s WHERE attrelid = %s::regclass AND attname = %s::name',
            $connection->quoteIdentifier('pg_attribute'),
            $connection->quote('d'),
            $connection->quote($tableName),
            $connection->quote($fieldName)
        );
        return $combinedStatementParts;
    }

    /**
     * Fetch PostgreSQL table sequence information. If existing, that means that a old Doctrine DBAL v3 autoincrement
     * sequence has not been migrated and altered yet.
     *
     * @see https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md#bc-break-auto-increment-columns-on-postgresql-are-implemented-as-identity-not-serial
     * @see ConnectionMigrator::getPostgreSQLMigrationStatements()
     *
     * @return array{seqid: int, objid: int}|null
     * @throws DBALException
     */
    private function getTableSequenceInformation(Typo3Connection $connection, TableDiff $changedTable, ColumnDiff $modifiedColumn): ?array
    {
        $oldColumn = $modifiedColumn->getOldColumn();
        $newColumn = $modifiedColumn->getNewColumn();
        $tableName = $this->trimIdentifierQuotes($changedTable->getOldTable()->getName());
        $fieldName = $this->trimIdentifierQuotes($newColumn->getName());
        $isAutoIncrementChange = ($newColumn->getAutoincrement() === true && $newColumn->getAutoincrement() !== $oldColumn->getAutoincrement());

        if (!($connection->getDatabasePlatform() instanceof DoctrinePostgreSQLPlatform && $isAutoIncrementChange)) {
            return null;
        }
        $colNum = $this->getTableFieldColumnNumber($connection, $tableName, $fieldName);
        if ($colNum === null) {
            return null;
        }
        return $this->getSequenceInfo($connection, $tableName, $fieldName, $colNum);
    }

    /**
     * Fetch PostgreSQL table sequence information. If existing, that means that a old Doctrine DBAL v3 autoincrement
     * sequence has not been migrated and altered yet.
     *
     * @see https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md#bc-break-auto-increment-columns-on-postgresql-are-implemented-as-identity-not-serial
     * @see ConnectionMigrator::getTableSequenceInformation()
     *
     * @return array{seqid: int, objid: int}|null
     * @throws DBALException
     */
    private function getSequenceInfo(Typo3Connection $connection, string $table, string $field, int $colNum): ?array
    {
        $quotedTable = $connection->quote($table);
        $colNum = $connection->quote((string)$colNum);
        $quotedPgClass = $connection->quote('pg_class');
        $depType = $connection->quote('a');
        // @todo Use QueryBuilder to retrieve the data
        $sql = sprintf(
            'SELECT classid as seqid, objid FROM pg_depend WHERE (refclassid, refobjid, refobjsubid) = (%s::regclass, %s::regclass, %s) AND classid = %s::regclass AND objsubid = 0 AND deptype = %s;',
            $quotedPgClass,
            $quotedTable,
            $colNum,
            $quotedPgClass,
            $depType
        );
        $rows = $connection->executeQuery($sql)->fetchAllAssociative();
        $count = count($rows);
        if ($count === 1) {
            $row = reset($rows);
            if (is_array($row)) {
                return $row;
            }
        } elseif ($count > 1) {
            // @todo Throw a concrete exception class
            throw new \RuntimeException(
                sprintf(
                    'Found more than one linked sequence table for %s.%s',
                    $table,
                    $field
                ),
                1705673988
            );
        }

        return null;
    }

    /**
     * Fetch PostgreSQL table field column nummber from schema definition.
     *
     * @see https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md#bc-break-auto-increment-columns-on-postgresql-are-implemented-as-identity-not-serial
     * @see ConnectionMigrator::getTableSequenceInformation()
     */
    private function getTableFieldColumnNumber(Typo3Connection $connection, string $table, string $field): ?int
    {
        $table = $connection->quote($table);
        $field = $connection->quote($field);
        // @todo Use QueryBuilder to retrieve the data
        $sql = sprintf(
            'SELECT attnum FROM pg_attribute WHERE attrelid = %s::regclass AND attname = %s::name;',
            $table,
            $field
        );
        $rows = $connection->executeQuery($sql)->fetchAllAssociative();
        $row = reset($rows);
        if (is_array($row)) {
            return (int)$row['attnum'];
        }
        return null;
    }

    /**
     * @todo DataMigration - handle this in another way after refactoring the connection migration stuff.
     *
     * @param Typo3Connection $connection
     * @param TableDiff $changedTable
     * @param ColumnDiff $modifiedColumn
     * @return array<non-empty-string, string>
     */
    private function getIndexedSearchTruncateTablePrerequisiteStatements(Typo3Connection $connection, TableDiff $changedTable, ColumnDiff $modifiedColumn): array
    {
        /** @var array<string, string[]> $tableFields */
        $tableFields = [
            'index_phash' => ['phash', 'phash_grouping', 'contentHash'],
            'index_fulltext' => ['phash'],
            'index_rel' => ['phash', 'wid'],
            'index_words' => ['wid'],
            'index_section' => ['phash', 'phash_t3'],
            'index_grlist' => ['phash', 'phash_x', 'hash_gr_list'],
        ];
        $tableName = $this->trimIdentifierQuotes($changedTable->getOldTable()->getName());
        $oldType = $modifiedColumn->getOldColumn()->getType();
        $newType = $modifiedColumn->getNewColumn()->getType();
        if (($tableFields[$tableName] ?? []) === []
            || !($oldType instanceof IntegerType)
            || !($newType instanceof StringType)
        ) {
            return [];
        }
        $databasePlatform = $connection->getDatabasePlatform();
        if (in_array($this->trimIdentifierQuotes($modifiedColumn->getOldColumn()->getName()), $tableFields[$tableName], true)) {
            return [
                $databasePlatform->getTruncateTableSQL($changedTable->getOldTable()->getQuotedName($databasePlatform)) => 'Truncate table needed due to type change',
            ];
        }
        return [];
    }
}
