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
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;

/**
 * Helper methods to handle SQL files and transform them into individual statements
 * for further processing.
 *
 * @internal not part of public core API.
 */
class SchemaMigrator
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly Parser $parser,
        private readonly DefaultTcaSchema $defaultTcaSchema,
    ) {}

    /**
     * Compare current and expected schema definitions and provide updates suggestions in the form
     * of SQL statements.
     *
     * @param string[] $statements The CREATE TABLE statements
     * @param bool $remove TRUE for RENAME/DROP table and column suggestions, FALSE for ADD/CHANGE suggestions
     * @return array<string, array> SQL statements to migrate the database to the expected schema, indexed by performed operation
     * @throws DBALException
     * @throws SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws StatementException
     */
    public function getUpdateSuggestions(array $statements, bool $remove = false): array
    {
        $tables = $this->parseCreateTableStatements($statements);
        $updateSuggestions = [];
        foreach ($this->connectionPool->getConnectionNames() as $connectionName) {
            $connection = $this->connectionPool->getConnectionByName($connectionName);
            $connectionMigrator = ConnectionMigrator::create($connectionName, $connection, $tables);
            $updateSuggestions[$connectionName] = $connectionMigrator->getUpdateSuggestions($remove);
        }
        return $updateSuggestions;
    }

    /**
     * Return the raw Doctrine SchemaDiff objects for each connection. This diff contains
     * all changes without any pre-processing.
     *
     * @return array<string, SchemaDiff>
     * @throws DBALException
     * @throws SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws StatementException
     */
    public function getSchemaDiffs(array $statements): array
    {
        $tables = $this->parseCreateTableStatements($statements);
        $schemaDiffs = [];
        foreach ($this->connectionPool->getConnectionNames() as $connectionName) {
            $connection = $this->connectionPool->getConnectionByName($connectionName);
            $connectionMigrator = ConnectionMigrator::create($connectionName, $connection, $tables);
            $schemaDiffs[$connectionName] = $connectionMigrator->getSchemaDiff();
        }
        return $schemaDiffs;
    }

    /**
     * This method executes statements from the update suggestions, or a subset of them
     * filtered by the statements hashes, one by one.
     *
     * @param string[] $statements The CREATE TABLE statements
     * @param string[] $selectedStatements The hashes of the update suggestions to execute
     * @throws DBALException
     * @throws SchemaException
     * @throws \InvalidArgumentException
     * @throws StatementException
     * @throws \RuntimeException
     */
    public function migrate(array $statements, array $selectedStatements): array
    {
        $result = [];
        $updateSuggestionsPerConnection = array_replace_recursive(
            $this->getUpdateSuggestions($statements),
            $this->getUpdateSuggestions($statements, true)
        );

        foreach ($updateSuggestionsPerConnection as $connectionName => $updateSuggestions) {
            unset($updateSuggestions['tables_count'], $updateSuggestions['change_currentValue']);
            $updateSuggestions = array_merge(...array_values($updateSuggestions));
            $statementsToExecute = array_intersect_key($updateSuggestions, $selectedStatements);
            if (count($statementsToExecute) === 0) {
                continue;
            }

            $connection = $this->connectionPool->getConnectionByName($connectionName);
            foreach ($statementsToExecute as $hash => $statement) {
                try {
                    $connection->executeStatement($statement);
                } catch (DBALException $e) {
                    $result[$hash] = $e->getPrevious()->getMessage();
                }
            }
        }
        $this->flushDatabaseSchemaCache();

        return $result;
    }

    /**
     * Perform add/change/create operations on tables and fields in an optimized, non-interactive, mode.
     *
     * @param string[] $statements The CREATE TABLE statements
     * @param bool $createOnly Only perform changes that add fields or create tables
     * @return array<string, string> Error messages for statements that occurred during the installation procedure.
     * @throws DBALException
     * @throws SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws StatementException
     */
    public function install(array $statements, bool $createOnly = false): array
    {
        $tables = $this->parseCreateTableStatements($statements);
        $result = [];
        foreach ($this->connectionPool->getConnectionNames() as $connectionName) {
            $connection = $this->connectionPool->getConnectionByName($connectionName);
            $connectionMigrator = ConnectionMigrator::create($connectionName, $connection, $tables);
            $lastResult = $connectionMigrator->install($createOnly);
            $result = array_merge($result, $lastResult);
        }
        $this->flushDatabaseSchemaCache();

        return $result;
    }

    /**
     * Import static data (INSERT statements)
     */
    public function importStaticData(array $statements, bool $truncate = false): array
    {
        $result = [];
        $insertStatements = [];

        foreach ($statements as $statement) {
            // Only handle insert statements and extract the table at the same time. Extracting
            // the table name is required to perform the inserts on the right connection.
            if (preg_match('/^INSERT\s+INTO\s+`?(\w+)`?(.*)/i', $statement, $matches)) {
                [, $tableName, $sqlFragment] = $matches;
                $insertStatements[$tableName][] = sprintf(
                    'INSERT INTO %s %s',
                    $this->connectionPool->getConnectionForTable($tableName)->quoteIdentifier($tableName),
                    rtrim($sqlFragment, ';')
                );
            }
        }

        foreach ($insertStatements as $tableName => $perTableStatements) {
            $connection = $this->connectionPool->getConnectionForTable($tableName);

            if ($truncate) {
                $connection->truncate($tableName);
            }

            foreach ((array)$perTableStatements as $statement) {
                try {
                    $connection->executeStatement($statement);
                    $result[$statement] = '';
                } catch (DBALException $e) {
                    $result[$statement] = $e->getPrevious()->getMessage();
                }
            }
        }

        return $result;
    }

    /**
     * Parse CREATE TABLE statements into Doctrine Table objects.
     *
     * @param string[] $statements The SQL CREATE TABLE statements
     * @return array<non-empty-string, Table>
     * @throws SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws StatementException
     */
    protected function parseCreateTableStatements(array $statements): array
    {
        $tables = $this->prepareTablesFromStatements($statements);
        $tables = $this->ensureTableDefinitionForAllTCAManagedTables($tables);
        $tables = $this->mergeTableDefinitions($tables);
        $tables = $this->enrichTablesFromDefaultTCASchema($tables);
        $tables = $this->ensureDefaultTCAFieldsAreOrdered($tables);
        return $tables;
    }

    /**
     * Have fields triggered by 'ctrl' settings first in the list. This is done for cosmetic
     * reasons to improve readability of db schema when opening tables in a database browser.
     *
     * @return string[]
     */
    protected function getPrioritizedFieldNames(string $tableName): array
    {
        if (!isset($GLOBALS['TCA'][$tableName]['ctrl'])) {
            return [];
        }

        $prioritizedFieldNames = [
            'uid',
            'pid',
        ];

        $tableDefinition = $GLOBALS['TCA'][$tableName]['ctrl'];

        if (!empty($tableDefinition['crdate'])) {
            $prioritizedFieldNames[] = $tableDefinition['crdate'];
        }
        if (!empty($tableDefinition['tstamp'])) {
            $prioritizedFieldNames[] = $tableDefinition['tstamp'];
        }
        if (!empty($tableDefinition['delete'])) {
            $prioritizedFieldNames[] = $tableDefinition['delete'];
        }
        if (!empty($tableDefinition['enablecolumns']['disabled'])) {
            $prioritizedFieldNames[] = $tableDefinition['enablecolumns']['disabled'];
        }
        if (!empty($tableDefinition['enablecolumns']['starttime'])) {
            $prioritizedFieldNames[] = $tableDefinition['enablecolumns']['starttime'];
        }
        if (!empty($tableDefinition['enablecolumns']['endtime'])) {
            $prioritizedFieldNames[] = $tableDefinition['enablecolumns']['endtime'];
        }
        if (!empty($tableDefinition['enablecolumns']['fe_group'])) {
            $prioritizedFieldNames[] = $tableDefinition['enablecolumns']['fe_group'];
        }
        if (!empty($tableDefinition['languageField'])) {
            $prioritizedFieldNames[] = $tableDefinition['languageField'];
            if (!empty($tableDefinition['transOrigPointerField'])) {
                $prioritizedFieldNames[] = $tableDefinition['transOrigPointerField'];
                $prioritizedFieldNames[] = 'l10n_state';
            }
            if (!empty($tableDefinition['translationSource'])) {
                $prioritizedFieldNames[] = $tableDefinition['translationSource'];
            }
            if (!empty($tableDefinition['transOrigDiffSourceField'])) {
                $prioritizedFieldNames[] = $tableDefinition['transOrigDiffSourceField'];
            }
        }
        if (!empty($tableDefinition['sortby'])) {
            $prioritizedFieldNames[] = $tableDefinition['sortby'];
        }
        if (!empty($tableDefinition['descriptionColumn'])) {
            $prioritizedFieldNames[] = $tableDefinition['descriptionColumn'];
        }
        if (!empty($tableDefinition['editlock'])) {
            $prioritizedFieldNames[] = $tableDefinition['editlock'];
        }
        if (!empty($tableDefinition['origUid'])) {
            $prioritizedFieldNames[] = $tableDefinition['origUid'];
        }
        if (!empty($tableDefinition['versioningWS'])) {
            $prioritizedFieldNames[] = 't3ver_wsid';
            $prioritizedFieldNames[] = 't3ver_oid';
            $prioritizedFieldNames[] = 't3ver_state';
            $prioritizedFieldNames[] = 't3ver_stage';
        }

        return $prioritizedFieldNames;
    }

    /**
     * To give extensions the ability to extend or modify the database schema for core or other extension tables, a
     * collection of DDL statement parts are parsed into partial table classes. This method merges the table definition
     * parts to end up with a single table representation to ease further handling.
     *
     * @param Table[] $tables
     * @return array<non-empty-string, Table>
     */
    private function mergeTableDefinitions(array $tables): array
    {
        $return = [];
        foreach ($tables as $table) {
            $tableName = $this->trimIdentifierQuotes($table->getName());
            if (!array_key_exists($tableName, $return)) {
                $return[$tableName] = $table;
                continue;
            }

            // Merge multiple table definitions. Later definitions overrule identical
            // columns, indexes and foreign_keys. Order of definitions is based on
            // extension load order.
            $currentTableDefinition = $return[$tableName];
            $return[$tableName] = new Table(
                $tableName,
                $this->mergeColumns(...array_values($currentTableDefinition->getColumns()), ...array_values($table->getColumns())),
                $this->mergeIndexes(...array_values($currentTableDefinition->getIndexes()), ...array_values($table->getIndexes())),
                [],
                array_merge($currentTableDefinition->getForeignKeys(), $table->getForeignKeys()),
                array_merge($currentTableDefinition->getOptions(), $table->getOptions())
            );
        }

        return $return;
    }

    /**
     * @param Column ...$columns
     * @return Column[]
     */
    private function mergeColumns(Column ...$columns): array
    {
        $mergedColumns = [];
        foreach ($columns as $column) {
            $mergedColumns[$column->getName()] = $column;
        }
        return array_values($mergedColumns);
    }

    /**
     * @param Index ...$indexes
     * @return Index[]
     */
    private function mergeIndexes(Index ...$indexes): array
    {
        $mergedIndexes = [];
        foreach ($indexes as $index) {
            $mergedIndexes[$index->getName()] = $index;
        }
        return array_values($mergedIndexes);
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
     * @param string[] $statements
     * @return Table[]
     * @throws SchemaException
     * @throws StatementException
     */
    protected function prepareTablesFromStatements(array $statements): array
    {
        $tables = [];
        foreach ($statements as $statement) {
            // We need to keep multiple table definitions at this point so
            // that Extensions can modify existing tables.
            try {
                $tables[] = $this->parser->parse($statement);
            } catch (StatementException $statementException) {
                // Enrich the error message with the full invalid statement
                throw new StatementException(
                    $statementException->getMessage() . ' in statement: ' . LF . $statement,
                    1476171315,
                    $statementException
                );
            }
        }

        // Flatten the array of arrays by one level
        $tables = array_merge(...$tables);

        return $tables;
    }

    /**
     * Ensure we have a table definition for all tables within TCA, add missing ones
     * as "empty" tables without columns. This is needed for DefaultTcaSchema: It goes
     * through TCA to add columns automatically, but needs a table definition of all
     * TCA tables. We're not doing this in DefaultTcaSchema to not introduce a dependency
     * to the Parser class in there, which we have here so conveniently already.
     *
     * @param Table[] $tables
     * @return Table[]
     * @throws SchemaException
     * @throws StatementException
     */
    protected function ensureTableDefinitionForAllTCAManagedTables(array $tables): array
    {
        $tableNamesFromTca = array_keys($GLOBALS['TCA']);
        $tableNamesFromExtTables = [];
        foreach ($tables as $table) {
            $tableNamesFromExtTables[] = $table->getName();
        }
        $tableNamesFromExtTables = array_unique($tableNamesFromExtTables);
        $missingTableNames = array_diff($tableNamesFromTca, $tableNamesFromExtTables);
        foreach ($missingTableNames as $tableName) {
            $createTableSql = 'CREATE TABLE ' . $tableName . '();';
            $tables[] = $this->parser->parse($createTableSql)[0];
        }
        return $tables;
    }

    /**
     * @param array<non-empty-string, Table> $tables
     * @return array<non-empty-string, Table>
     */
    protected function enrichTablesFromDefaultTCASchema(array $tables): array
    {
        return $this->defaultTcaSchema->enrich($tables);
    }

    /**
     * Ensure the default TCA fields are ordered.
     *
     * @param array<non-empty-string, Table> $tables
     * @return array<non-empty-string, Table>
     */
    protected function ensureDefaultTCAFieldsAreOrdered(array $tables): array
    {
        foreach ($tables as $k => $table) {
            $prioritizedColumnNames = $this->getPrioritizedFieldNames($table->getName());
            // no TCA table
            if (empty($prioritizedColumnNames)) {
                continue;
            }

            $prioritizedColumns = [];
            $nonPrioritizedColumns = [];

            foreach ($table->getColumns() as $columnObject) {
                if (in_array($columnObject->getName(), $prioritizedColumnNames, true)) {
                    $prioritizedColumns[] = $columnObject;
                } else {
                    $nonPrioritizedColumns[] = $columnObject;
                }
            }

            $tables[$k] = new Table(
                $table->getName(),
                array_merge($prioritizedColumns, $nonPrioritizedColumns),
                $table->getIndexes(),
                [],
                $table->getForeignKeys(),
                $table->getOptions()
            );
        }
        return $tables;
    }

    protected function flushDatabaseSchemaCache(): void
    {
        Bootstrap::createCache('database_schema')->flush();
    }
}
