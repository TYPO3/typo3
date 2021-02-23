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

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\TextType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class SchemaMigratorTest extends FunctionalTestCase
{
    /**
     * @var SqlReader
     */
    protected $sqlReader;

    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @var AbstractSchemaManager
     */
    protected $schemaManager;

    /**
     * @var \TYPO3\CMS\Core\Database\Schema\SchemaMigrator
     */
    protected $subject;

    /**
     * @var string
     */
    protected $tableName = 'a_test_table';

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(SchemaMigrator::class);
        $this->sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->schemaManager = $this->connectionPool->getConnectionForTable($this->tableName)->getSchemaManager();
        $this->prepareTestTable();
    }

    /**
     * Tears down this test suite.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->schemaManager->tablesExist([$this->tableName])) {
            $this->schemaManager->dropTable($this->tableName);
        }
        if ($this->schemaManager->tablesExist(['zzz_deleted_' . $this->tableName])) {
            $this->schemaManager->dropTable('zzz_deleted_' . $this->tableName);
        }
        if ($this->schemaManager->tablesExist(['another_test_table'])) {
            $this->schemaManager->dropTable('another_test_table');
        }
    }

    /**
     * @test
     */
    public function createNewTable()
    {
        if ($this->schemaManager->tablesExist([$this->tableName])) {
            $this->schemaManager->dropTable($this->tableName);
        }

        $statements = $this->readFixtureFile('newTable');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table']
        );

        self::assertCount(6, $this->getTableDetails()->getColumns());
    }

    /**
     * @test
     */
    public function createNewTableIfNotExists()
    {
        $statements = $this->readFixtureFile('ifNotExists');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['create_table']
        );

        self::assertTrue($this->schemaManager->tablesExist(['another_test_table']));
    }

    /**
     * @test
     */
    public function addNewColumns()
    {
        $statements = $this->readFixtureFile('addColumnsToTable');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['add']
        );

        self::assertCount(7, $this->getTableDetails()->getColumns());
        self::assertTrue($this->getTableDetails()->hasColumn('title'));
        self::assertTrue($this->getTableDetails()->hasColumn('description'));
    }

    /**
     * @test
     */
    public function changeExistingColumn()
    {
        $statements = $this->readFixtureFile('changeExistingColumn');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);

        self::assertEquals(50, $this->getTableDetails()->getColumn('title')->getLength());
        self::assertEmpty($this->getTableDetails()->getColumn('title')->getDefault());

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change']
        );

        self::assertEquals(100, $this->getTableDetails()->getColumn('title')->getLength());
        self::assertEquals('Title', $this->getTableDetails()->getColumn('title')->getDefault());
    }

    /**
     * Disabled on sqlite: It does not support adding a not null column to an existing
     * table and throws "Cannot add a NOT NULL column with default value NULL". It's
     * currently unclear if core should handle that by changing the alter table
     * statement on the fly.
     *
     * @test
     * @group not-sqlite
     */
    public function notNullWithoutDefaultValue()
    {
        $statements = $this->readFixtureFile('notNullWithoutDefaultValue');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['add']
        );

        self::assertTrue($this->getTableDetails()->getColumn('aTestField')->getNotnull());
    }

    /**
     * @test
     */
    public function defaultNullWithoutNotNull()
    {
        $statements = $this->readFixtureFile('defaultNullWithoutNotNull');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['add']
        );

        self::assertFalse($this->getTableDetails()->getColumn('aTestField')->getNotnull());
        self::assertNull($this->getTableDetails()->getColumn('aTestField')->getDefault());
    }

    /**
     * @test
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function renameUnusedField()
    {
        $statements = $this->readFixtureFile('unusedColumn');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements, true);

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change']
        );

        self::assertFalse($this->getTableDetails()->hasColumn('hidden'));
        self::assertTrue($this->getTableDetails()->hasColumn('zzz_deleted_hidden'));
    }

    /**
     * @test
     */
    public function renameUnusedTable()
    {
        $statements = $this->readFixtureFile('unusedTable');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements, true);

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change_table']
        );

        self::assertNotContains($this->tableName, $this->schemaManager->listTableNames());
        self::assertContains('zzz_deleted_' . $this->tableName, $this->schemaManager->listTableNames());
    }

    /**
     * Disabled on sqlite: It seems the platform is unable to drop columns for
     * currently unknown reasons.
     *
     * @test
     * @group not-sqlite
     */
    public function dropUnusedField()
    {
        $connection = $this->connectionPool->getConnectionForTable($this->tableName);
        $fromSchema = $this->schemaManager->createSchema();
        $toSchema = clone $fromSchema;
        $toSchema->getTable($this->tableName)->addColumn('zzz_deleted_testfield', 'integer', ['notnull' => false]);
        $statements = $fromSchema->getMigrateToSql(
            $toSchema,
            $connection->getDatabasePlatform()
        );
        $connection->executeUpdate($statements[0]);
        self::assertTrue($this->getTableDetails()->hasColumn('zzz_deleted_testfield'));

        $statements = $this->readFixtureFile('newTable');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements, true);
        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['drop']
        );

        self::assertFalse($this->getTableDetails()->hasColumn('zzz_deleted_testfield'));
    }

    /**
     * @test
     */
    public function dropUnusedTable()
    {
        $this->schemaManager->renameTable($this->tableName, 'zzz_deleted_' . $this->tableName);
        self::assertNotContains($this->tableName, $this->schemaManager->listTableNames());
        self::assertContains('zzz_deleted_' . $this->tableName, $this->schemaManager->listTableNames());

        $statements = $this->readFixtureFile('newTable');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements, true);
        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['drop_table']
        );

        self::assertNotContains($this->tableName, $this->schemaManager->listTableNames());
        self::assertNotContains('zzz_deleted_' . $this->tableName, $this->schemaManager->listTableNames());
    }

    /**
     * @test
     * @group not-postgres
     * @group not-sqlite
     */
    public function installPerformsOnlyAddAndCreateOperations()
    {
        $statements = $this->readFixtureFile('addCreateChange');
        $this->subject->install($statements, true);

        self::assertContains('another_test_table', $this->schemaManager->listTableNames());
        self::assertTrue($this->getTableDetails()->hasColumn('title'));
        self::assertTrue($this->getTableDetails()->hasIndex('title'));
        self::assertTrue($this->getTableDetails()->getIndex('title')->isUnique());
        self::assertNotInstanceOf(BigIntType::class, $this->getTableDetails()->getColumn('pid')->getType());
    }

    /**
     * Disabled on sqlite: The platform seems to have issues with indexes
     * for currently unknown reasons. If that is sorted out, this test can
     * probably be enabled.
     *
     * @test
     */
    public function installDoesNotAddIndexOnChangedColumn()
    {
        $statements = $this->readFixtureFile('addIndexOnChangedColumn');
        $this->subject->install($statements, true);

        self::assertNotInstanceOf(TextType::class, $this->getTableDetails()->getColumn('title')->getType());
        self::assertFalse($this->getTableDetails()->hasIndex('title'));
    }

    /**
     * @test
     */
    public function changeExistingIndex()
    {
        // recreate the table with the indexes applied
        // this is needed for e.g. postgres
        if ($this->schemaManager->tablesExist([$this->tableName])) {
            $this->schemaManager->dropTable($this->tableName);
        }
        $this->prepareTestTable(false);

        $statements = $this->readFixtureFile('changeExistingIndex');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change']
        );

        $indexesAfterChange = $this->schemaManager->listTableIndexes($this->tableName);

        // indexes could be sorted differently thus we filter for index named "parent" only and
        // use that as index to retrieve the modified columns of that index
        $parentIndex = array_values(
            array_filter(
                array_keys($indexesAfterChange),
                function ($key) {
                    return strpos($key, 'parent') !== false;
                }
            )
        );

        $expectedColumnsOfChangedIndex = [
            'pid',
            'deleted'
        ];
        self::assertEquals($expectedColumnsOfChangedIndex, $indexesAfterChange[$parentIndex[0]]->getColumns());
    }

    /**
     * @test
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function installCanPerformChangeOperations()
    {
        $statements = $this->readFixtureFile('addCreateChange');
        $this->subject->install($statements);

        self::assertContains('another_test_table', $this->schemaManager->listTableNames());
        self::assertTrue($this->getTableDetails()->hasColumn('title'));
        self::assertTrue($this->getTableDetails()->hasIndex('title'));
        self::assertTrue($this->getTableDetails()->getIndex('title')->isUnique());
        self::assertInstanceOf(BigIntType::class, $this->getTableDetails()->getColumn('pid')->getType());
    }

    /**
     * @test
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function importStaticDataInsertsRecords()
    {
        $sqlCode = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Fixtures', 'importStaticData.sql']));
        $connection = $this->connectionPool->getConnectionForTable($this->tableName);
        $statements = $this->sqlReader->getInsertStatementArray($sqlCode);
        $this->subject->importStaticData($statements);

        self::assertEquals(2, $connection->count('*', $this->tableName, []));
    }

    /**
     * @test
     */
    public function importStaticDataIgnoresTableDefinitions()
    {
        $sqlCode = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Fixtures', 'importStaticData.sql']));
        $statements = $this->sqlReader->getStatementArray($sqlCode);
        $this->subject->importStaticData($statements);

        self::assertNotContains('another_test_table', $this->schemaManager->listTableNames());
    }

    /**
     * @test
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function changeTableEngine()
    {
        $statements = $this->readFixtureFile('alterTableEngine');
        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);

        $index = array_keys($updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'])[0];
        self::assertStringEndsWith(
            'ENGINE = MyISAM',
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change'][$index]
        );

        $this->subject->migrate(
            $statements,
            $updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change']
        );

        $updateSuggestions = $this->subject->getUpdateSuggestions($statements);
        self::assertEmpty($updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change']);
        self::assertEmpty($updateSuggestions[ConnectionPool::DEFAULT_CONNECTION_NAME]['change']);
    }

    /**
     * Create the base table for all migration tests
     *
     * @param bool $createOnly
     */
    protected function prepareTestTable(bool $createOnly = true)
    {
        $statements = $this->readFixtureFile('newTable');
        $this->subject->install($statements, $createOnly);
    }

    /**
     * Helper to return the Doctrine Table object for the test table
     *
     * @return \Doctrine\DBAL\Schema\Table
     */
    protected function getTableDetails(): Table
    {
        return $this->schemaManager->listTableDetails($this->tableName);
    }

    /**
     * Helper to read a fixture SQL file and convert it into a statement array.
     *
     * @param string $fixtureName
     * @return array
     */
    protected function readFixtureFile(string $fixtureName): array
    {
        $sqlCode = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Fixtures', $fixtureName]) . '.sql');

        return $this->sqlReader->getCreateTableStatementArray($sqlCode);
    }
}
