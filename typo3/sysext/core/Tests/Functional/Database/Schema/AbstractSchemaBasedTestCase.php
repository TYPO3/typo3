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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\TestingFramework\Core\Testbase;

abstract class AbstractSchemaBasedTestCase extends FunctionalTestCase
{
    /**
     * @var array<string, mixed>
     */
    private ?array $backupTableOptions = null;

    /**
     * @var non-empty-string[]
     */
    protected array $tablesToDrop = [];

    protected function setUp(): void
    {
        $this->initializeDatabase = false;
        parent::setUp();
        $providedData = $this->providedData();
        if (($providedData['emptyDefaultTableOptions'] ?? null) === true) {
            $connection = $this->getConnectionPool()->getConnectionByName('Default');
            $this->backupTableOptions = $connection->getParams()['defaultTableOptions'] ?? null;
            \Closure::bind(static function () use ($connection): void {
                unset($connection->params['defaultTableOptions']);
            }, null, Connection::class)();
        }
        $this->verifyCleanDatabaseState();
        $this->verifyNoDatabaseTablesExists();
    }

    protected function tearDown(): void
    {
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        if ($this->backupTableOptions !== null) {
            $backupTableOptions = $this->backupTableOptions;
            \Closure::bind(static function () use ($connection, $backupTableOptions): void {
                $connection->params['defaultTableOptions'] = $backupTableOptions;
            }, null, Connection::class)();
            $this->backupTableOptions = null;
        }
        $schemaManager = $connection->createSchemaManager();
        foreach ($this->tablesToDrop as $table) {
            if ($schemaManager->tablesExist([$table])) {
                $schemaManager->dropTable($table);
            }
        }
        $this->verifyCleanDatabaseState();
        $this->verifyNoDatabaseTablesExists();
        parent::tearDown();
    }

    protected function verifyCleanDatabaseState(string $additionalCreateStatementsString = ''): void
    {
        $sqlReader = $this->createSqlReader();
        $schemaMigrator = $this->createSchemaMigrator();
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString() . $additionalCreateStatementsString);
        $addCreateChange = $schemaMigrator->getUpdateSuggestions($sqlStatements);
        foreach ($addCreateChange['Default'] as $operation => $targets) {
            if (!empty($targets)) {
                self::fail("Schema probably polluted by previous test, unclean operation: $operation");
            }
        }
        $dropRename = $schemaMigrator->getUpdateSuggestions($sqlStatements, true);
        foreach ($dropRename['Default'] as $operation => $targets) {
            if (!empty($targets)) {
                self::fail("Schema probably polluted by previous test, unclean operation: $operation");
            }
        }
    }

    protected function verifyNoDatabaseTablesExists(): void
    {
        self::assertCount(0, $this->getConnectionPool()->getConnectionByName('Default')->createSchemaManager()->listTableNames());
    }

    protected function verifyMigrationResult(array $result): void
    {
        if ($result === []) {
            return;
        }
        foreach ($result as $hash => $message) {
            self::assertSame('', $message, $hash . ' failed: ' . $message);
        }
    }

    protected function getSchemaManager(): AbstractSchemaManager
    {
        return $this->get(ConnectionPool::class)->getConnectionByName('Default')->createSchemaManager();
    }

    protected function createSqlReader(): SqlReader
    {
        // Ensure SqlReader is not taking any extension into account to retrieve extension table structure files.
        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('getActivePackages')->willReturn([]);
        return new SqlReader(new NoopEventDispatcher(), $packageManagerMock);
    }

    protected function createSchemaMigrator(): SchemaMigrator
    {
        return new class ($this->get(ConnectionPool::class), $this->get(Parser::class), new DefaultTcaSchema(), $this->get('cache.runtime')) extends SchemaMigrator {
            protected function ensureTableDefinitionForAllTCAManagedTables(array $tables): array
            {
                // Do not create tables for any TCA tables (should be empty anyways).
                return $tables;
            }

            protected function enrichTablesFromDefaultTCASchema(array $tables): array
            {
                return $tables;
            }
        };
    }

    /**
     * Note executed only for the first test permutation for testcase.
     *
     * @internal only. Protected for special case \TYPO3\CMS\Core\Tests\Functional\Database\Schema\SchemaMigratorTest
     */
    protected function initializeTestDatabase(
        ContainerInterface $container,
        Testbase $testbase,
        string $dbDriver,
        bool $initializeDatabase,
        string $dbName,
        string $originalDatabaseName,
        string $dbPathSqliteEmpty,
    ): void {
        // parent call omitted by intention.
        $testbase->setUpTestDatabase($dbName, $originalDatabaseName);
        if ($dbDriver === 'pdo_sqlite') {
            $this->getConnectionPool()->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->executeQuery('SELECT 1');
            // Copy sqlite file '/path/functional-sqlite-dbs/test_123.sqlite' to
            // '/path/functional-sqlite-dbs/test_123.empty.sqlite'. This is re-used for consecutive tests.
            copy($dbName, $dbPathSqliteEmpty);
        }
    }

    /**
     * Note only executed for subsequential test permutations for testcase, not the first one.
     *
     * @internal only. Protected for special case \TYPO3\CMS\Core\Tests\Functional\Database\Schema\SchemaMigratorTest
     */
    protected function initializeTestDatabaseAndTruncateTables(Testbase $testbase, bool $initializeDatabase, string $dbPathSqlite, string $dbPathSqliteEmpty): void
    {
        parent::initializeTestDatabaseAndTruncateTables($testbase, true, $dbPathSqlite, $dbPathSqliteEmpty);
    }

    /**
     * Create the base table for all migration tests
     */
    protected function prepareTestTable(SchemaMigrator $schemaMigrator, string $sqlCodeFile): void
    {
        self::assertFileExists($sqlCodeFile);
        $sqlCode = file_get_contents($sqlCodeFile);
        $result = $schemaMigrator->install($this->createSqlReader()->getCreateTableStatementArray($sqlCode));
        $this->verifyMigrationResult($result);
        $this->verifyCleanDatabaseState($sqlCode);
    }

    /**
     * Helper to return the Doctrine Table object for the test table
     */
    protected function getTableDetails(string $tableName): Table
    {
        return $this->getSchemaManager()->introspectTable($tableName);
    }
}
