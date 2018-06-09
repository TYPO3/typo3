<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Schema;

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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
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
     * @var Schema[]
     */
    protected $schema = [];

    /**
     * Compare current and expected schema definitions and provide updates suggestions in the form
     * of SQL statements.
     *
     * @param string[] $statements The CREATE TABLE statements
     * @param bool $remove TRUE for RENAME/DROP table and column suggestions, FALSE for ADD/CHANGE suggestions
     * @return array[] SQL statements to migrate the database to the expected schema, indexed by performed operation
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws StatementException
     */
    public function getUpdateSuggestions(array $statements, bool $remove = false): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $tables = $this->parseCreateTableStatements($statements);

        $updateSuggestions = [];

        foreach ($connectionPool->getConnectionNames() as $connectionName) {
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
     * @param array $statements
     * @return SchemaDiff[]
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
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
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws StatementException
     * @throws \RuntimeException
     */
    public function migrate(array $statements, array $selectedStatements): array
    {
        $result = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $updateSuggestionsPerConnection = array_merge_recursive(
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
                    $connection->executeUpdate($statement);
                } catch (DBALException $e) {
                    $result[$hash] = $e->getPrevious()->getMessage();
                }
            }
        }

        return $result;
    }

    /**
     * Perform add/change/create operations on tables and fields in an optimized,
     * non-interactive, mode using the original doctrine SchemaManager ->toSaveSql()
     * method.
     *
     * @param string[] $statements The CREATE TABLE statements
     * @param bool $createOnly Only perform changes that add fields or create tables
     * @return array[] Error messages for statements that occurred during the installation procedure.
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
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

        return $result;
    }

    /**
     * Import static data (INSERT statements)
     *
     * @param array $statements
     * @param bool $truncate
     * @return array
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
                list(, $tableName, $sqlFragment) = $matches;
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
                    $connection->executeUpdate($statement);
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
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
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

        // @deprecated (?!) Drop any definition of pages_language_overlay in SQL
        // will be removed in TYPO3 v10.0 once the feature is enabled by default
        $disabledPagesLanguageOverlay = GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('unifiedPageTranslationHandling');

        // Add default TCA fields
        $defaultTcaSchema = GeneralUtility::makeInstance(DefaultTcaSchema::class);
        $tables = $defaultTcaSchema->enrich($tables);
        // Ensure the default TCA fields are ordered
        foreach ($tables as $k => $table) {
            if ($disabledPagesLanguageOverlay && $table->getName() === 'pages_language_overlay') {
                unset($tables[$k]);
                continue;
            }
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
                $table->getForeignKeys(),
                0,
                $table->getOptions()
            );
        }

        return $tables;
    }
}
