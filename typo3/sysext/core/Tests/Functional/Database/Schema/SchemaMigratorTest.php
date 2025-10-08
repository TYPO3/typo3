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
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\EnumType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Database\Schema\SchemaDiff;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\CMS\Core\Database\Schema\Types\DateTimeType;
use TYPO3\CMS\Core\Database\Schema\Types\DateType;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\CMS\Core\Database\Schema\Types\TimeType;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\TestingFramework\Core\Testbase;

/**
 * Note that this test disables the normal database schema creation for all loaded extensions albeit creating
 * a database beside using the option not to initialize the database. This is done by intention to test migration
 * against a empty database not taking usual instance table creation into account. Clean database creation with
 * all system extension will be added in a dedicated test case.
 */
final class SchemaMigratorTest extends FunctionalTestCase
{
    private ?array $backupTableOptions = null;

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
        // Clean up for next test
        if ($schemaManager->tablesExist(['a_test_table'])) {
            $schemaManager->dropTable('a_test_table');
        }
        if ($schemaManager->tablesExist(['zzz_deleted_a_test_table'])) {
            $schemaManager->dropTable('zzz_deleted_a_test_table');
        }
        if ($schemaManager->tablesExist(['another_test_table'])) {
            $schemaManager->dropTable('another_test_table');
        }
        if ($schemaManager->tablesExist(['a_textfield_test_table'])) {
            $schemaManager->dropTable('a_textfield_test_table');
        }
        $this->verifyCleanDatabaseState();
        $this->verifyNoDatabaseTablesExists();
        parent::tearDown();
    }

    private function verifyCleanDatabaseState(string $additionalCreateStatementsString = ''): void
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

    private function verifyNoDatabaseTablesExists(): void
    {
        self::assertCount(0, $this->getConnectionPool()->getConnectionByName('Default')->createSchemaManager()->listTableNames());
    }

    private function verifyMigrationResult(array $result): void
    {
        if ($result === []) {
            return;
        }
        foreach ($result as $hash => $message) {
            self::assertSame('', $message, $hash . ' failed: ' . $message);
        }
    }

    private function getSchemaManager(): AbstractSchemaManager
    {
        return $this->get(ConnectionPool::class)->getConnectionByName('Default')->createSchemaManager();
    }

    private function createSqlReader(): SqlReader
    {
        // Ensure SqlReader is not taking any extension into account to retrieve extension table structure files.
        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('getActivePackages')->willReturn([]);
        return new SqlReader(new NoopEventDispatcher(), $packageManagerMock);
    }

    private function createSchemaMigrator(): SchemaMigrator
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
    private function prepareTestTable(SchemaMigrator $schemaMigrator, ?string $sqlCodeFile = null): void
    {
        $sqlCode = file_get_contents($sqlCodeFile ?? __DIR__ . '/../Fixtures/newTable.sql');
        $result = $schemaMigrator->install($this->createSqlReader()->getCreateTableStatementArray($sqlCode));
        $this->verifyMigrationResult($result);
        $this->verifyCleanDatabaseState($sqlCode);
    }

    /**
     * Helper to return the Doctrine Table object for the test table
     */
    private function getTableDetails(?string $tableName = null): Table
    {
        return $this->getSchemaManager()->introspectTable($tableName ?? 'a_test_table');
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function mergingTableDefinitionReturnsLatestColumnDefinition(bool $emptyDefaultTableOptions): void
    {
        $column1 = new Column('testfield', Type::getType('string'), ['length' => 100]);
        $column2 = new Column('testfield', Type::getType('string'), ['length' => 200]);
        $column3 = new Column('testfield', Type::getType('string'), ['length' => 220]);
        $table1 = new Table('a_test_table', [$column1]);
        $table2 = new Table('a_test_table', [$column2]);
        $table3 = new Table('a_test_table', [$column3]);
        $subject = $this->createSchemaMigrator();
        $mergeTableDefinitionsMethod = new \ReflectionMethod(
            SchemaMigrator::class,
            'mergeTableDefinitions'
        );
        $mergedTables = $mergeTableDefinitionsMethod->invoke($subject, [$table1, $table2, $table3]);
        self::assertIsArray($mergedTables);
        self::assertCount(1, $mergedTables);
        self::assertArrayHasKey('a_test_table', $mergedTables);

        $firstTable = $mergedTables['a_test_table'];
        self::assertInstanceOf(Table::class, $firstTable);
        self::assertTrue($firstTable->hasColumn('testfield'));
        self::assertSame($column3, $firstTable->getColumn('testfield'));
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTable(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertCount(6, $this->getTableDetails()->getColumns());
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableDefaultsToEngineInnoDB(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertTrue($this->getTableDetails()->hasOption('engine'));
        self::assertEquals('InnoDB', $this->getTableDetails()->getOption('engine'));
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableWithExplicitEngineInnoDB(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTableInnoDB.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertTrue($this->getTableDetails()->hasOption('engine'));
        self::assertEquals('InnoDB', $this->getTableDetails()->getOption('engine'));
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableWithExplicitEngineMyISAM(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTableMyISAM.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertCount(6, $this->getTableDetails()->getColumns());
        self::assertTrue($this->getTableDetails()->hasOption('engine'));
        self::assertEquals('MyISAM', $this->getTableDetails()->getOption('engine'));
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableWithParsedLatin1BasedColumnsCharsetAndCollationCreatesExpectedColumnForMySQLAndMariaDB(bool $emptyDefaultTableOptions): void
    {
        $assertForColumns = ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7', 'col8', 'col9', 'col10'];
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTableWithLatin1BasedCharsetAndCollate.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $tableDetails = $this->getTableDetails();
        self::assertCount(12, $tableDetails->getColumns());
        foreach ($assertForColumns as $assertForColumn) {
            self::assertTrue($tableDetails->hasColumn($assertForColumn));
            self::assertTrue($tableDetails->getColumn($assertForColumn)->hasPlatformOption('charset'));
            self::assertSame('latin1', $tableDetails->getColumn($assertForColumn)->getPlatformOption('charset'));
            self::assertTrue($tableDetails->getColumn($assertForColumn)->hasPlatformOption('collation'));
            self::assertSame('latin1_swedish_ci', $tableDetails->getColumn($assertForColumn)->getPlatformOption('collation'));
        }
    }

    #[Group('not-mariadb')]
    #[Group('not-mysql')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableWithParsedLatin1BasedColumnsCharsetAndCollationCreatesExpectedColumnForPostgreSQL(bool $emptyDefaultTableOptions): void
    {
        $assertForColumns = ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7', 'col8', 'col9', 'col10'];
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTableWithLatin1BasedCharsetAndCollate.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $tableDetails = $this->getTableDetails();
        self::assertCount(12, $tableDetails->getColumns());
        foreach ($assertForColumns as $assertForColumn) {
            // MySQL/MariaDB collation sets are not exchangeable for PostegreSQL and requires also to be a subset of the
            // connection/table charset and is removed in ConnectionMigrator::applyDefaultPlatformOptionsToColumns().
            self::assertTrue($tableDetails->hasColumn($assertForColumn));
            self::assertFalse($tableDetails->getColumn($assertForColumn)->hasPlatformOption('charset'));
            self::assertFalse($tableDetails->getColumn($assertForColumn)->hasPlatformOption('collation'));
        }
    }

    #[Group('not-mariadb')]
    #[Group('not-mysql')]
    #[Group('not-postgres')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableWithParsedLatin1BasedColumnsCharsetAndCollationCreatesExpectedColumnForSQLite(bool $emptyDefaultTableOptions): void
    {
        $assertForColumns = ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7', 'col8', 'col9', 'col10'];
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTableWithLatin1BasedCharsetAndCollate.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $tableDetails = $this->getTableDetails();
        self::assertCount(12, $tableDetails->getColumns());
        foreach ($assertForColumns as $assertForColumn) {
            // SQLite does not support charset and collation for columns but columns parsed from MySQL/MariaDB like
            // ext_tables.sql should still create tables and columns correctly. SQLite requires to have collation set
            // to binary for CHAR/VARCHAR/TEXT/MEDIUMTEXT/LONGTEXT/JSON which is here verified.
            // See ConnectionMigrator::applyDefaultPlatformOptionsToColumns().
            self::assertTrue($tableDetails->hasColumn($assertForColumn));
            self::assertFalse($tableDetails->getColumn($assertForColumn)->hasPlatformOption('charset'));
            self::assertTrue($tableDetails->getColumn($assertForColumn)->hasPlatformOption('collation'));
            self::assertSame('BINARY', $tableDetails->getColumn($assertForColumn)->getPlatformOption('collation'));
        }
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableWithParsedAsciiBasedColumnsCharsetAndCollationCreatesExpectedColumnForMySQLAndMariaDB(bool $emptyDefaultTableOptions): void
    {
        $assertForColumns = ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7', 'col8', 'col9', 'col10'];
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTableWithAsciiBasedCharsetAndCollate.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $tableDetails = $this->getTableDetails();
        self::assertCount(12, $tableDetails->getColumns());
        foreach ($assertForColumns as $assertForColumn) {
            self::assertTrue($tableDetails->hasColumn($assertForColumn));
            self::assertTrue($tableDetails->getColumn($assertForColumn)->hasPlatformOption('charset'));
            self::assertSame('ascii', $tableDetails->getColumn($assertForColumn)->getPlatformOption('charset'));
            self::assertTrue($tableDetails->getColumn($assertForColumn)->hasPlatformOption('collation'));
            self::assertSame('ascii_bin', $tableDetails->getColumn($assertForColumn)->getPlatformOption('collation'));
        }
    }

    #[Group('not-mariadb')]
    #[Group('not-mysql')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableWithParsedAsciiBasedColumnsCharsetAndCollationCreatesExpectedColumnForPostgreSQL(bool $emptyDefaultTableOptions): void
    {
        $assertForColumns = ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7', 'col8', 'col9', 'col10'];
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTableWithAsciiBasedCharsetAndCollate.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $tableDetails = $this->getTableDetails();
        self::assertCount(12, $tableDetails->getColumns());
        foreach ($assertForColumns as $assertForColumn) {
            // MySQL/MariaDB collation sets are not exchangeable for PostegreSQL and requires also to be a subset of the
            // connection/table charset and is removed in ConnectionMigrator::applyDefaultPlatformOptionsToColumns().
            self::assertTrue($tableDetails->hasColumn($assertForColumn));
            self::assertFalse($tableDetails->getColumn($assertForColumn)->hasPlatformOption('charset'));
            self::assertFalse($tableDetails->getColumn($assertForColumn)->hasPlatformOption('collation'));
        }
    }

    #[Group('not-mariadb')]
    #[Group('not-mysql')]
    #[Group('not-postgres')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableWithParsedAsciiBasedColumnsCharsetAndCollationCreatesExpectedColumnForSQLite(bool $emptyDefaultTableOptions): void
    {
        $assertForColumns = ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7', 'col8', 'col9', 'col10'];
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTableWithAsciiBasedCharsetAndCollate.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $tableDetails = $this->getTableDetails();
        self::assertCount(12, $tableDetails->getColumns());
        foreach ($assertForColumns as $assertForColumn) {
            // SQLite does not support charset and collation for columns but columns parsed from MySQL/MariaDB like
            // ext_tables.sql should still create tables and columns correctly. SQLite requires to have collation set
            // to binary for CHAR/VARCHAR/TEXT/MEDIUMTEXT/LONGTEXT/JSON which is here verified.
            // See ConnectionMigrator::applyDefaultPlatformOptionsToColumns().
            self::assertTrue($tableDetails->hasColumn($assertForColumn));
            self::assertFalse($tableDetails->getColumn($assertForColumn)->hasPlatformOption('charset'));
            self::assertTrue($tableDetails->getColumn($assertForColumn)->hasPlatformOption('collation'));
            self::assertSame('BINARY', $tableDetails->getColumn($assertForColumn)->getPlatformOption('collation'));
        }
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function changeTableCharsetToDefaultIfConfigured(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();

        $this->prepareTestTable($subject);

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('a_test_table');
        $connection->executeStatement('ALTER TABLE a_test_table DEFAULT CHARACTER SET = utf8 COLLATE = utf8_unicode_ci');

        self::assertTrue($this->getTableDetails()->hasOption('charset'));
        $utf8PlatformCharset = $this->getTableDetails()->getOption('charset');
        self::assertContains($utf8PlatformCharset, ['utf8', 'utf8mb3']);

        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertTrue($this->getTableDetails()->hasOption('charset'));
        if ($emptyDefaultTableOptions) {
            // Stay as-is if not default table options are configured
            self::assertEquals($utf8PlatformCharset, $this->getTableDetails()->getOption('charset'));
        } else {
            // Switch to utf8mb4 if default table options are configured
            self::assertEquals('utf8mb4', $this->getTableDetails()->getOption('charset'));
        }
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function createNewTableIfNotExists(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/ifNotExists.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertTrue($this->getSchemaManager()->tablesExist(['another_test_table']));
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function addNewColumns(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/addColumnsToTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['add'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertCount(7, $this->getTableDetails()->getColumns());
        self::assertTrue($this->getTableDetails()->hasColumn('title'));
        self::assertTrue($this->getTableDetails()->hasColumn('description'));
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function changeExistingColumn(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/changeExistingColumn.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        self::assertEquals(50, $this->getTableDetails()->getColumn('title')->getLength());
        self::assertEmpty($this->getTableDetails()->getColumn('title')->getDefault());
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertEquals(100, $this->getTableDetails()->getColumn('title')->getLength());
        self::assertEquals('Title', $this->getTableDetails()->getColumn('title')->getDefault());
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function changeTableWithPossiblyMySQLCachedRowFormatCreateTableOption(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newLargeTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertCount(8, $this->getTableDetails()->getColumns());

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('a_test_table');
        $connection->insert(
            'a_test_table',
            [
                'pid' => 0,
                'title' => 'Lorem ipsum dolor sit amet, consetetur sadipscing.',
                'content' => str_repeat('Lorem ipsum dolor sit amet.', 100),
            ]
        );

        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newLargeTableWithMyISAMFixed.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertCount(8, $this->getTableDetails()->getColumns());
        self::assertTrue($this->getTableDetails()->hasOption('engine'));
        self::assertEquals('MyISAM', $this->getTableDetails()->getOption('engine'));

        $queryBuilder = $connection->createQueryBuilder();
        $rowFormat = $queryBuilder
            ->select(
                'tables.ROW_FORMAT AS row_format',
            )
            ->from('information_schema.TABLES', 'tables')
            ->where(
                $queryBuilder->expr()->eq(
                    'TABLE_TYPE',
                    $queryBuilder->createNamedParameter('BASE TABLE')
                ),
                $queryBuilder->expr()->eq(
                    'TABLE_SCHEMA',
                    $queryBuilder->createNamedParameter($connection->getDatabase())
                ),
                $queryBuilder->expr()->eq(
                    'TABLE_NAME',
                    $queryBuilder->createNamedParameter('a_test_table')
                )
            )
            ->executeQuery()
            ->fetchOne();

        // Reports Dynamic although changed to FIXED (because of existing data and structure)
        self::assertEquals('Dynamic', $rowFormat);

        // …but MySQL cached "create_options" to fixed (and will apply these in upcoming InnoDB change)
        self::assertTrue($this->getTableDetails()->hasOption('create_options'));
        self::assertEquals(['row_format' => 'FIXED'], $this->getTableDetails()->getOption('create_options'));

        // Change back to InnoDB
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newLargeTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertEquals('InnoDB', $this->getTableDetails()->getOption('engine'));
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function notNullWithoutDefaultValue(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/notNullWithoutDefaultValue.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['add'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertTrue($this->getTableDetails()->getColumn('aTestField')->getNotnull());
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function defaultNullWithoutNotNull(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/defaultNullWithoutNotNull.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['add'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertFalse($this->getTableDetails()->getColumn('aTestField')->getNotnull());
        self::assertNull($this->getTableDetails()->getColumn('aTestField')->getDefault());
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function renameUnusedField(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/unusedColumn.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements, true);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertFalse($this->getTableDetails()->hasColumn('hidden'));
        self::assertTrue($this->getTableDetails()->hasColumn('zzz_deleted_hidden'));
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function renameUnusedTable(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/unusedTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements, true);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertNotContains('a_test_table', $this->getSchemaManager()->listTableNames());
        self::assertContains('zzz_deleted_a_test_table', $this->getSchemaManager()->listTableNames());
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function dropUnusedField(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('a_test_table');
        $fromSchema = $this->getSchemaManager()->introspectSchema();
        $tableDiff = new TableDiff(
            $fromSchema->getTable('a_test_table'),
            ['zzz_deleted_testfield' => new Column('zzz_deleted_testfield', Type::getType('integer'), ['notnull' => false])],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
        );
        $schemaDiff = new SchemaDiff(
            [],
            [],
            [],
            ['a_test_table' => $tableDiff],
            [],
            [],
            [],
            [],
        );
        foreach ($connection->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff) as $statement) {
            $connection->executeStatement($statement);
        }
        self::assertTrue($this->getTableDetails()->hasColumn('zzz_deleted_testfield'));
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements, true);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['drop'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertFalse($this->getTableDetails()->hasColumn('zzz_deleted_testfield'));
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function dropUnusedTable(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $this->getSchemaManager()->renameTable('a_test_table', 'zzz_deleted_a_test_table');
        self::assertNotContains('a_test_table', $this->getSchemaManager()->listTableNames());
        self::assertContains('zzz_deleted_a_test_table', $this->getSchemaManager()->listTableNames());
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/newTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements, true);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['drop_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        self::assertNotContains('a_test_table', $this->getSchemaManager()->listTableNames());
        self::assertNotContains('zzz_deleted_a_test_table', $this->getSchemaManager()->listTableNames());
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function installPerformsOnlyAddAndCreateOperations(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/addCreateChange.sql'));
        $result = $subject->install($statements, true);
        $this->verifyMigrationResult($result);
        self::assertContains('another_test_table', $this->getSchemaManager()->listTableNames());
        self::assertTrue($this->getTableDetails()->hasColumn('title'));
        self::assertTrue($this->getTableDetails()->hasIndex('title'));
        self::assertTrue($this->getTableDetails()->getIndex('title')->isUnique());
        self::assertNotInstanceOf(BigIntType::class, $this->getTableDetails()->getColumn('pid')->getType());
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function installDoesNotAddIndexOnChangedColumn(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/addIndexOnChangedColumn.sql'));
        $result = $subject->install($statements, true);
        $this->verifyMigrationResult($result);
        self::assertNotInstanceOf(TextType::class, $this->getTableDetails()->getColumn('title')->getType());
        self::assertFalse($this->getTableDetails()->hasIndex('title'));
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function changeExistingIndex(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/changeExistingIndex.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $indexesAfterChange = $this->getSchemaManager()->listTableIndexes('a_test_table');
        // indexes could be sorted differently thus we filter for index named "parent" only and
        // use that as index to retrieve the modified columns of that index
        $parentIndex = array_values(
            array_filter(
                array_keys($indexesAfterChange),
                static function ($key) {
                    return str_contains($key, 'parent');
                }
            )
        );
        $expectedColumnsOfChangedIndex = [
            'pid',
            'deleted',
        ];
        self::assertEquals($expectedColumnsOfChangedIndex, $indexesAfterChange[$parentIndex[0]]->getColumns());
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function installCanPerformChangeOperations(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/addCreateChange.sql'));
        $result = $subject->install($statements);
        $this->verifyMigrationResult($result);
        self::assertContains('another_test_table', $this->getSchemaManager()->listTableNames());
        self::assertTrue($this->getTableDetails()->hasColumn('title'));
        self::assertTrue($this->getTableDetails()->hasIndex('title'));
        self::assertTrue($this->getTableDetails()->getIndex('title')->isUnique());
        self::assertInstanceOf(BigIntType::class, $this->getTableDetails()->getColumn('pid')->getType());
    }

    #[Group('not-postgres')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function importStaticDataInsertsRecords(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $sqlCode = file_get_contents(__DIR__ . '/../Fixtures/importStaticData.sql');
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('a_test_table');
        $statements = $this->createSqlReader()->getInsertStatementArray($sqlCode);
        $result = $subject->importStaticData($statements);
        $this->verifyMigrationResult($result);
        self::assertEquals(2, $connection->count('*', 'a_test_table', []));
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function importStaticDataIgnoresTableDefinitions(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $sqlCode = file_get_contents(__DIR__ . '/../Fixtures/importStaticData.sql');
        $statements = $this->createSqlReader()->getStatementArray($sqlCode);
        $result = $subject->importStaticData($statements);
        // Table not created and insert statements are returning database errors in the result set, check for that !
        self::assertIsArray($result);
        self::assertCount(2, $result);
        foreach ($result as $hash => $message) {
            self::assertNotSame('', $message);
        }
        self::assertNotContains('another_test_table', $this->getSchemaManager()->listTableNames());
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function changeTableEngine(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject);
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/alterTableEngine.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $index = array_keys($updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'])[0];
        self::assertStringContainsString(
            'ENGINE = MyISAM',
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'][$index]
        );
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        self::assertEmpty($updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change']);
    }

    public static function textFieldDefaultValueTestDataProvider(): \Generator
    {
        foreach ([false, true] as $emptyDefaultTableOptions) {
            $suffix = $emptyDefaultTableOptions ? ' (empty defaultTableOptions)' : '';
            yield 'text not null default empty string value' . $suffix => [
                'fixtureFileName' => 'text-not-null-default-empty-string-value.sql',
                'table' => 'a_textfield_test_table',
                'fieldName' => 'testfield',
                'assertionFileName' => 'text-not-null-default-empty-string-value.csv',
                'expectedDefaultValue' => '',
                'expectedNotNull' => true,
                'expectDefaultValue' => true,
                'emptyDefaultTableOptions' => $emptyDefaultTableOptions,
            ];
            yield 'text default empty string value' . $suffix => [
                'fixtureFileName' => 'text-default-empty-string-value.sql',
                'table' => 'a_textfield_test_table',
                'fieldName' => 'testfield',
                'assertionFileName' => 'text-default-empty-string-value.csv',
                'expectedDefaultValue' => '',
                'expectedNotNull' => false,
                'expectDefaultValue' => true,
                'emptyDefaultTableOptions' => $emptyDefaultTableOptions,
            ];
            yield 'text default NULL' . $suffix => [
                'fixtureFileName' => 'text-default-null.sql',
                'table' => 'a_textfield_test_table',
                'fieldName' => 'testfield',
                'assertionFileName' => 'text-default-null.csv',
                'expectedDefaultValue' => null,
                'expectedNotNull' => false,
                'expectDefaultValue' => true,
                'emptyDefaultTableOptions' => $emptyDefaultTableOptions,
            ];
            yield 'text not null default value string value' . $suffix => [
                'fixtureFileName' => 'text-not-null-default-value-string-value.sql',
                'table' => 'a_textfield_test_table',
                'fieldName' => 'testfield',
                'assertionFileName' => 'text-not-null-default-value-string-value.csv',
                'expectedDefaultValue' => 'database-default-value',
                'expectedNotNull' => true,
                'expectDefaultValue' => true,
                'emptyDefaultTableOptions' => $emptyDefaultTableOptions,
            ];
            yield 'text not null default value string with single quote value' . $suffix => [
                'fixtureFileName' => 'text-not-null-default-value-string-with-single-quote-value.sql',
                'table' => 'a_textfield_test_table',
                'fieldName' => 'testfield',
                'assertionFileName' => 'text-not-null-default-value-string-with-single-quote-value.csv',
                'expectedDefaultValue' => "default-value with a single ' quote",
                'expectedNotNull' => true,
                'expectDefaultValue' => true,
                'emptyDefaultTableOptions' => $emptyDefaultTableOptions,
            ];
        }
    }

    #[DataProvider('textFieldDefaultValueTestDataProvider')]
    #[Test]
    public function textFieldDefaultValueTest(
        string $fixtureFileName,
        string $table,
        string $fieldName,
        string $assertionFileName,
        ?string $expectedDefaultValue,
        bool $expectedNotNull,
        bool $expectDefaultValue,
        bool $emptyDefaultTableOptions,
    ): void {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/TextFieldDefaultValue/' . $fixtureFileName));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);

        $tableDefinition = $this->getConnectionPool()->getConnectionForTable($table)->createSchemaManager()->introspectTable($table);
        self::assertTrue($tableDefinition->hasColumn($fieldName));
        $column = $tableDefinition->getColumn($fieldName);
        if ($expectDefaultValue) {
            self::assertSame($expectedDefaultValue, $column->getDefault());
        } else {
            self::assertNull($column->getDefault());
        }
        self::assertSame($expectedNotNull, $column->getNotnull());

        $this->getConnectionPool()->getConnectionForTable($table)->insert(
            $table,
            [
                'pid' => 0,
            ]
        );
        self::assertCSVDataSet(__DIR__ . '/../Fixtures/TextFieldDefaultValue/Assertions/' . $assertionFileName);
    }

    public static function jsonFieldDefaultValueTestDataProvider(): \Generator
    {
        yield 'json not null default empty object value' => [
            'fixtureFileName' => 'json-not-null-default-empty-object-value.sql',
            'table' => 'a_textfield_test_table',
            'fieldName' => 'testfield',
            'assertionFileName' => 'json-not-null-default-empty-object-value.csv',
            'expectedDefaultValue' => '{}',
            'expectedNotNull' => true,
            'expectDefaultValue' => true,
        ];
        yield 'json default empty object value' => [
            'fixtureFileName' => 'json-default-empty-object-value.sql',
            'table' => 'a_textfield_test_table',
            'fieldName' => 'testfield',
            'assertionFileName' => 'json-default-empty-object-value.csv',
            'expectedDefaultValue' => '{}',
            'expectedNotNull' => false,
            'expectDefaultValue' => true,
        ];
        yield 'json not null default empty array value' => [
            'fixtureFileName' => 'json-not-null-default-empty-array-value.sql',
            'table' => 'a_textfield_test_table',
            'fieldName' => 'testfield',
            'assertionFileName' => 'json-not-null-default-empty-array-value.csv',
            'expectedDefaultValue' => '[]',
            'expectedNotNull' => true,
            'expectDefaultValue' => true,
        ];
        yield 'json default empty array value' => [
            'fixtureFileName' => 'json-default-empty-array-value.sql',
            'table' => 'a_textfield_test_table',
            'fieldName' => 'testfield',
            'assertionFileName' => 'json-default-empty-array-value.csv',
            'expectedDefaultValue' => '[]',
            'expectedNotNull' => false,
            'expectDefaultValue' => true,
        ];
        yield 'json default NULL' => [
            'fixtureFileName' => 'json-default-null.sql',
            'table' => 'a_textfield_test_table',
            'fieldName' => 'testfield',
            'assertionFileName' => 'json-default-null.csv',
            'expectedDefaultValue' => null,
            'expectedNotNull' => false,
            'expectDefaultValue' => true,
        ];
        yield 'json not null default data object value containing single-quote value' => [
            'fixtureFileName' => 'json-not-null-default-data-object-value-with-single-quote-value.sql',
            'table' => 'a_textfield_test_table',
            'fieldName' => 'testfield',
            'assertionFileName' => 'json-not-null-default-data-object-value-with-single-quote-value.csv',
            'expectedDefaultValue' => '{"key1": "value1", "key2": 123, "key3": "value with a \' single quote"}',
            'expectedNotNull' => true,
            'expectDefaultValue' => true,
        ];
        yield 'json not null default data object value containing double-quote value' => [
            'fixtureFileName' => 'json-not-null-default-data-object-value-with-double-quote-value.sql',
            'table' => 'a_textfield_test_table',
            'fieldName' => 'testfield',
            'assertionFileName' => 'json-not-null-default-data-object-value-with-double-quote-value.csv',
            'expectedDefaultValue' => '{"key1": "value1", "key2": 123, "key3": "value with a \" double quote"}',
            'expectedNotNull' => true,
            'expectDefaultValue' => true,
        ];
    }

    #[DataProvider('jsonFieldDefaultValueTestDataProvider')]
    #[Test]
    public function jsonFieldDefaultValueTest(
        string $fixtureFileName,
        string $table,
        string $fieldName,
        string $assertionFileName,
        ?string $expectedDefaultValue,
        bool $expectedNotNull,
        bool $expectDefaultValue,
    ): void {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/JsonFieldDefaultValue/' . $fixtureFileName));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table'];
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);

        $tableDefinition = $this->getConnectionPool()->getConnectionForTable($table)->createSchemaManager()->introspectTable($table);
        self::assertTrue($tableDefinition->hasColumn($fieldName));
        $column = $tableDefinition->getColumn($fieldName);
        if ($expectDefaultValue) {
            self::assertSame($expectedDefaultValue, $column->getDefault());
        } else {
            self::assertNull($column->getDefault());
        }
        self::assertSame($expectedNotNull, $column->getNotnull());

        $this->getConnectionPool()->getConnectionForTable($table)->insert(
            $table,
            [
                'pid' => 0,
            ]
        );
        self::assertCSVDataSet(__DIR__ . '/../Fixtures/JsonFieldDefaultValue/Assertions/' . $assertionFileName);
    }

    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function bigIntPrimaryKeyCrossDatabaseMaxValue(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/bigIntPrimaryKeyTable.sql'));
        $result = $subject->install($statements);
        $this->verifyMigrationResult($result);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/bigIntPrimaryKeyInsert.csv');
        $connection = $this->get(ConnectionPool::class)->getConnectionByName('Default');
        $connection->insert(
            'a_test_table',
            [
                'pid' => 0,
                'title' => 'added',
            ]
        );
        self::assertSame('9223372036854775807', $connection->lastInsertId());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/bigIntPrimaryKeyAssert.csv');
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function mediumTextToLargeTextColumChangeAndRevertWorksAsExpected(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $sqlCode = file_get_contents(__DIR__ . '/../Fixtures/mediumTextTable.sql');
        $result = $subject->install($this->createSqlReader()->getCreateTableStatementArray($sqlCode));
        $this->verifyMigrationResult($result);
        $this->verifyCleanDatabaseState($sqlCode);

        // medium to long text
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/mediumTextTable_changeToLongText.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        self::assertCount(1, $selectedStatements);
        self::assertStringContainsString('CHANGE `text1` `text1` LONGTEXT', $selectedStatements[array_key_first($selectedStatements)]);
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        self::assertCount(0, $selectedStatements);

        // long to medium text
        $statements = $this->createSqlReader()->getCreateTableStatementArray(file_get_contents(__DIR__ . '/../Fixtures/mediumTextTable.sql'));
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        self::assertCount(1, $selectedStatements);
        self::assertStringContainsString('CHANGE `text1` `text1` MEDIUMTEXT', $selectedStatements[array_key_first($selectedStatements)]);
        $result = $subject->migrate($statements, $selectedStatements);
        $this->verifyMigrationResult($result);
        $updateSuggestions = $subject->getUpdateSuggestions($statements);
        $selectedStatements = $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        self::assertCount(0, $selectedStatements);
    }

    #[Group('not-sqlite')]
    #[Group('not-postgres')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function enumTypeFieldCanBeCreated(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $sqlCode = file_get_contents(__DIR__ . '/../Fixtures/enumTable.sql');
        $result = $subject->install($this->createSqlReader()->getCreateTableStatementArray($sqlCode));
        $this->verifyMigrationResult($result);
        $this->verifyCleanDatabaseState($sqlCode);
        $tableDetails = $this->getTableDetails();
        self::assertTrue($tableDetails->hasColumn('test1'));
        $column = $tableDetails->getColumn('test1');
        self::assertInstanceOf(EnumType::class, $column->getType());
        self::assertSame(['', 'v4', 'v6'], $column->getValues());
        $defaultValue = $column->getDefault();
        self::assertIsString($defaultValue);
        self::assertSame('v4', $defaultValue);
    }

    #[Group('not-sqlite')]
    #[Group('not-postgres')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function renameAndRemoveUnusedEnumFieldWorks(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $this->prepareTestTable($subject, __DIR__ . '/../Fixtures/enumTable.sql');
        $removedEnumFieldSqlCode = file_get_contents(__DIR__ . '/../Fixtures/enumTable_removedEnumField.sql');
        // rename unused enum field
        $migrateStatements = $this->createSqlReader()->getCreateTableStatementArray($removedEnumFieldSqlCode);
        $updateStatements = $subject->getUpdateSuggestions($migrateStatements, true);
        $selectedUpdateStatements = $updateStatements[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'];
        $migratedResult = $subject->migrate($migrateStatements, $selectedUpdateStatements);
        $this->verifyMigrationResult($migratedResult);
        self::assertFalse($this->getTableDetails()->hasColumn('test1'));
        self::assertTrue($this->getTableDetails()->hasColumn('zzz_deleted_test1'));
        // remove renamed unused enum field
        $migrateStatements = $this->createSqlReader()->getCreateTableStatementArray($removedEnumFieldSqlCode);
        $updateStatements = $subject->getUpdateSuggestions($migrateStatements, true);
        $selectedUpdateStatements = $updateStatements[ConnectionPool::DEFAULT_CONNECTION_NAME]['drop'];
        $migratedResult = $subject->migrate($migrateStatements, $selectedUpdateStatements);
        $this->verifyMigrationResult($migratedResult);
        $this->verifyCleanDatabaseState($removedEnumFieldSqlCode);
        self::assertFalse($this->getTableDetails()->hasColumn('test1'));
        self::assertFalse($this->getTableDetails()->hasColumn('zzz_deleted_test1'));
    }

    #[Group('not-sqlite')]
    #[Group('not-postgres')]
    #[TestWith(['emptyDefaultTableOptions' => false])]
    #[TestWith(['emptyDefaultTableOptions' => true])]
    #[Test]
    public function setTypeFieldCanBeCreated(bool $emptyDefaultTableOptions): void
    {
        $subject = $this->createSchemaMigrator();
        $sqlCode = file_get_contents(__DIR__ . '/../Fixtures/setTable.sql');
        $result = $subject->install($this->createSqlReader()->getCreateTableStatementArray($sqlCode));
        $this->verifyMigrationResult($result);
        $this->verifyCleanDatabaseState($sqlCode);
        $tableDetails = $this->getTableDetails();
        self::assertTrue($tableDetails->hasColumn('test1'));
        $column = $tableDetails->getColumn('test1');
        self::assertInstanceOf(SetType::class, $column->getType());
        self::assertSame(['a', 'b', 'c', 'd'], $column->getValues());
        $defaultValue = $column->getDefault();
        self::assertIsString($defaultValue);
        self::assertSame('a', $defaultValue);
    }

    public static function tableColumnDoctrineTypeDataSets(): \Generator
    {
        yield 'char => StringType' => [
            'createTableDDL' => "CREATE TABLE a_test_table (test_field CHAR(100) NOT NULL DEFAULT '');",
            'tableName' => 'a_test_table',
            'fieldName' => 'test_field',
            'expectedType' => StringType::class,
            'expectedLength' => 100,
            'expectedFixed' => true,
        ];
        yield 'varchar => StringType' => [
            'createTableDDL' => "CREATE TABLE a_test_table (test_field VARCHAR(100) NOT NULL DEFAULT '');",
            'tableName' => 'a_test_table',
            'fieldName' => 'test_field',
            'expectedType' => StringType::class,
            'expectedLength' => 100,
            'expectedFixed' => false,
        ];
        yield 'int => IntegerType' => [
            'createTableDDL' => 'CREATE TABLE a_test_table (test_field INT(11) NOT NULL DEFAULT 0);',
            'tableName' => 'a_test_table',
            'fieldName' => 'test_field',
            'expectedType' => IntegerType::class,
            'expectedLength' => null,
            'expectedFixed' => null,
        ];
        yield 'json => JsonType' => [
            'createTableDDL' => 'CREATE TABLE a_test_table (test_field JSON);',
            'tableName' => 'a_test_table',
            'fieldName' => 'test_field',
            'expectedType' => JsonType::class,
            'expectedLength' => null,
            'expectedFixed' => null,
        ];
    }

    #[DataProvider('tableColumnDoctrineTypeDataSets')]
    #[Test]
    public function doctrineSchemaManagerReturnsExpectedTypeForField(
        string $createTableDDL,
        string $tableName,
        string $fieldName,
        string $expectedType,
        ?int $expectedLength,
        ?bool $expectedFixed,
    ): void {
        $subject = $this->createSchemaMigrator();
        $result = $subject->install($this->createSqlReader()->getCreateTableStatementArray($createTableDDL));
        $this->verifyMigrationResult($result);
        $this->verifyCleanDatabaseState($createTableDDL);

        $schemaManager = (new ConnectionPool())->getConnectionForTable($tableName)->createSchemaManager();
        self::assertTrue($schemaManager->tableExists($tableName));
        $table = $schemaManager->introspectTable($tableName);
        self::assertTrue($table->hasColumn($fieldName));
        self::assertSame($expectedType, $table->getColumn($fieldName)->getType()::class);
        if ($expectedFixed !== null) {
            self::assertSame($expectedFixed, $table->getColumn($fieldName)->getFixed());
        }
        if ($expectedLength !== null) {
            self::assertSame($expectedLength, $table->getColumn($fieldName)->getLength());
        }
    }

    #[DataProvider('tableColumnDoctrineTypeDataSets')]
    #[Test]
    public function schemaInformationGetTableInfoReturnsExpectedTypeForField(
        string $createTableDDL,
        string $tableName,
        string $fieldName,
        string $expectedType,
        ?int $expectedLength,
        ?bool $expectedFixed,
    ): void {
        $subject = $this->createSchemaMigrator();
        $result = $subject->install($this->createSqlReader()->getCreateTableStatementArray($createTableDDL));
        $this->verifyMigrationResult($result);
        $this->verifyCleanDatabaseState($createTableDDL);

        $schemaInfo = (new ConnectionPool())->getConnectionForTable($tableName)->getSchemaInformation();
        $tableInfo = $schemaInfo->getTableInfo($tableName);
        self::assertTrue($tableInfo->hasColumnInfo($fieldName));
        self::assertSame($expectedType, $tableInfo->getColumnInfo($fieldName)->getType()::class);
        if ($expectedFixed !== null) {
            self::assertSame($expectedFixed, $tableInfo->getColumnInfo($fieldName)->fixed);
        }
        if ($expectedLength !== null) {
            self::assertSame($expectedLength, $tableInfo->getColumnInfo($fieldName)->length);
        }
    }

    #[DataProvider('tableColumnDoctrineTypeDataSets')]
    #[Test]
    public function schemaInformationListTableColumnInfosReturnsExpectedTypeForField(
        string $createTableDDL,
        string $tableName,
        string $fieldName,
        string $expectedType,
        ?int $expectedLength,
        ?bool $expectedFixed,
    ): void {
        $subject = $this->createSchemaMigrator();
        $result = $subject->install($this->createSqlReader()->getCreateTableStatementArray($createTableDDL));
        $this->verifyMigrationResult($result);
        $this->verifyCleanDatabaseState($createTableDDL);

        $schemaInfo = (new ConnectionPool())->getConnectionForTable($tableName)->getSchemaInformation();
        $tableColumnInfos = $schemaInfo->listTableColumnInfos($tableName);
        self::assertArrayHasKey($fieldName, $tableColumnInfos);
        $columnInfo = $tableColumnInfos[$fieldName];
        self::assertSame($expectedType, $columnInfo->getType()::class);
        if ($expectedFixed !== null) {
            self::assertSame($expectedFixed, $columnInfo->fixed);
        }
        if ($expectedLength !== null) {
            self::assertSame($expectedLength, $columnInfo->length);
        }
    }

    public static function verifyPlatformTypeSupportDataSets(): \Generator
    {
        yield 'enum' => [
            'type' => 'enum',
            'typeClassName' => EnumType::class,
            'expectedSupportedPlatforms' => [
                AbstractMySQLPlatform::class,
            ],
        ];
        yield 'set' => [
            'type' => 'set',
            'typeClassName' => SetType::class,
            'expectedSupportedPlatforms' => [
                AbstractMySQLPlatform::class,
                // @todo SQLite and PostgresSQL do not support the set type but are required to be expected here until
                //       registration of the custom TYPO3 SetType is only mapped to supported MySQL/MariaDB platform
                //       connections. That requires to move the type registration for connections into platform aware
                //       doctrine driver middleware or a similar solution instead of registering custom types for all
                //       connections unconditionally.
                SQLitePlatform::class, // wrong, should not be expected
                PostgreSQLPlatform::class, // wrong, should not be expected
            ],
        ];
        yield 'date' => [
            'type' => 'date',
            // @todo TYPO3 knows a mutable and a immutable type, but only the mutable type matches the SQL keyword
            //       with the registered type. Immutable variant is replaced using database column comments, which
            //       is not tested in {@see self::verifyPlatformTypeSupportMatchesExpectation()},
            // Note that TYPO3 replaces doctrine/dbal implementation here.
            'typeClassName' => DateType::class,
            'expectedSupportedPlatforms' => [
                AbstractMySQLPlatform::class,
                SQLitePlatform::class,
                PostgreSQLPlatform::class,
            ],
        ];
        yield 'datetime' => [
            'type' => 'datetime',
            // @todo TYPO3 knows a mutable and a immutable type, but only the mutable type matches the SQL keyword
            //       with the registered type. Immutable variant is replaced using database column comments, which
            //       is not tested in {@see self::verifyPlatformTypeSupportMatchesExpectation()},
            // Note that TYPO3 replaces doctrine/dbal implementation here.
            'typeClassName' => DateTimeType::class,
            'expectedSupportedPlatforms' => [
                AbstractMySQLPlatform::class,
                SQLitePlatform::class,
                PostgreSQLPlatform::class,
            ],
        ];
        yield 'time' => [
            'type' => 'time',
            // Note that TYPO3 replaces doctrine/dbal implementation here.
            'typeClassName' => TimeType::class,
            'expectedSupportedPlatforms' => [
                AbstractMySQLPlatform::class,
                SQLitePlatform::class,
                PostgreSQLPlatform::class,
            ],
        ];
    }

    #[DataProvider('verifyPlatformTypeSupportDataSets')]
    #[Test]
    public function verifyPlatformTypeSupportMatchesExpectation(string $type, string $typeClassName, array $expectedSupportedPlatforms): void
    {
        $platform = $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->getDatabasePlatform();
        $expectTypeRegisterered = false;
        foreach ($expectedSupportedPlatforms as $expectedSupportedPlatform) {
            if ($platform instanceof $expectedSupportedPlatform) {
                $expectTypeRegisterered = true;
                break;
            }
        }
        self::assertSame($expectTypeRegisterered, $platform->hasDoctrineTypeMappingFor($type));
        if ($expectTypeRegisterered) {
            self::assertSame($typeClassName, Type::getType($platform->getDoctrineTypeMapping($type))::class);
        }
    }
}
