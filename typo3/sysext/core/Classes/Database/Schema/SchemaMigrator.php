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
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper methods to handle SQL files and transform them into individual statements
 * for further processing.
 *
 * @internal
 */
class SchemaMigrator
{
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
     * @throws UnexpectedSignalReturnValueTypeException
     * @throws StatementException
     */
    public function getUpdateSuggestions(array $statements, bool $remove = false): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $tables = $this->parseCreateTableStatements($statements);

        $updateSuggestions = [];

        foreach ($connectionPool->getConnectionNames() as $connectionName) {
            $this->adoptDoctrineAutoincrementDetectionForSqlite($tables, $connectionPool->getConnectionByName($connectionName));
            $connectionMigrator = ConnectionMigrator::create(
                $connectionName,
                $tables
            );

            $updateSuggestions[$connectionName] =
                $connectionMigrator->getUpdateSuggestions($remove);
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
     * @throws UnexpectedSignalReturnValueTypeException
     * @throws StatementException
     */
    public function getSchemaDiffs(array $statements): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $tables = $this->parseCreateTableStatements($statements);

        $schemaDiffs = [];

        foreach ($connectionPool->getConnectionNames() as $connectionName) {
            $connectionMigrator = ConnectionMigrator::create(
                $connectionName,
                $tables
            );
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
     * @throws UnexpectedSignalReturnValueTypeException
     * @throws StatementException
     * @throws \RuntimeException
     */
    public function migrate(array $statements, array $selectedStatements): array
    {
        $result = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
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

            $connection = $connectionPool->getConnectionByName($connectionName);
            foreach ($statementsToExecute as $hash => $statement) {
                try {
                    $connection->executeStatement($statement);
                } catch (DBALException $e) {
                    $result[$hash] = $e->getPrevious()->getMessage();
                }
            }
        }
        Bootstrap::createCache('database_schema')->flush();

        return $result;
    }

    /**
     * Perform add/change/create operations on tables and fields in an optimized,
     * non-interactive, mode using the original doctrine SchemaManager ->toSaveSql()
     * method.
     *
     * @param string[] $statements The CREATE TABLE statements
     * @param bool $createOnly Only perform changes that add fields or create tables
     * @return array<string, string> Error messages for statements that occurred during the installation procedure.
     * @throws DBALException
     * @throws SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws UnexpectedSignalReturnValueTypeException
     * @throws StatementException
     */
    public function install(array $statements, bool $createOnly = false): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $tables = $this->parseCreateTableStatements($statements);
        $result = [];

        foreach ($connectionPool->getConnectionNames() as $connectionName) {
            $connectionMigrator = ConnectionMigrator::create(
                $connectionName,
                $tables
            );

            $lastResult = $connectionMigrator->install($createOnly);
            $result = array_merge($result, $lastResult);
        }
        Bootstrap::createCache('database_schema')->flush();

        return $result;
    }

    /**
     * Import static data (INSERT statements)
     */
    public function importStaticData(array $statements, bool $truncate = false): array
    {
        $result = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $insertStatements = [];

        foreach ($statements as $statement) {
            // Only handle insert statements and extract the table at the same time. Extracting
            // the table name is required to perform the inserts on the right connection.
            if (preg_match('/^INSERT\s+INTO\s+`?(\w+)`?(.*)/i', $statement, $matches)) {
                [, $tableName, $sqlFragment] = $matches;
                $insertStatements[$tableName][] = sprintf(
                    'INSERT INTO %s %s',
                    $connectionPool->getConnectionForTable($tableName)->quoteIdentifier($tableName),
                    rtrim($sqlFragment, ';')
                );
            }
        }

        foreach ($insertStatements as $tableName => $perTableStatements) {
            $connection = $connectionPool->getConnectionForTable($tableName);

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
     * @return Table[]
     * @throws SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws StatementException
     * @throws UnexpectedSignalReturnValueTypeException
     */
    public function parseCreateTableStatements(array $statements): array
    {
        $tables = [];
        foreach ($statements as $statement) {
            $createTableParser = GeneralUtility::makeInstance(Parser::class, $statement);

            // We need to keep multiple table definitions at this point so
            // that Extensions can modify existing tables.
            try {
                $tables[] = $createTableParser->parse();
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

        // Add default TCA fields
        $defaultTcaSchema = GeneralUtility::makeInstance(DefaultTcaSchema::class);
        $tables = $defaultTcaSchema->enrich($tables);
        // Ensure the default TCA fields are ordered
        foreach ($tables as $k => $table) {
            $prioritizedColumnNames = $defaultTcaSchema->getPrioritizedFieldNames($table->getName());
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

    /**
     * doctrine/dbal detects both sqlite autoincrement variants (row_id alias and autoincrement) through assumptions
     * which have been made. TYPO3 reads the ext_tables.sql files as MySQL/MariaDB variant, thus not setting the
     * autoincrement value to true for the row_id alias variant, which leads to an endless missmatch during database
     * comparison. This method adopts the doctrine/dbal assumption and apply it to the meta schema to mitigate
     * endless database compare detections in these cases.
     *
     * @see https://github.com/doctrine/dbal/commit/33555d36e7e7d07a5880e01
     *
     * @param Table[] $tables
     */
    protected function adoptDoctrineAutoincrementDetectionForSqlite(array $tables, Connection $connection): void
    {
        if (!($connection->getDatabasePlatform() instanceof SqlitePlatform)) {
            return;
        }
        array_walk($tables, static function (Table $table): void {
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
        });
    }
}
