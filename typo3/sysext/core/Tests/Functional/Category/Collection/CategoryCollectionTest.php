<?php
namespace TYPO3\CMS\Core\Tests\Functional\Category\Collection;

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

use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Category\Collection\CategoryCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for \TYPO3\CMS\Core\Category\Collection\CategoryCollection
 */
class CategoryCollectionTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var CategoryCollection
     */
    private $subject;

    /**
     * @var string
     */
    private $tableName = 'tx_foo_5001615c50bed';

    /**
     * @var array
     */
    private $tables = array('sys_category', 'sys_category_record_mm');

    /**
     * @var int
     */
    private $categoryUid = 0;

    /**
     * @var array
     */
    private $collectionRecord = array();

    /**
     * @var int
     */
    private $numberOfRecords = 5;

    /**
     * Sets up this test suite.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(CategoryCollection::class, $this->tableName);
        $this->collectionRecord = array(
            'uid' => 0,
            'title' => $this->getUniqueId('title'),
            'description' => $this->getUniqueId('description'),
            'table_name' => $this->tableName,
        );
        $GLOBALS['TCA'][$this->tableName] = array('ctrl' => array());
        // prepare environment
        $this->createDummyTable();
        $this->populateDummyTable();
        $this->prepareTables();
        $this->makeRelationBetweenCategoryAndDummyTable();
    }

    /**
     * Tears down this test suite.
     */
    protected function tearDown()
    {
        $this->purgePreparedTables();
        $this->dropDummyTable();
        parent::tearDown();
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::fromArray
     * @return void
     */
    public function checkIfFromArrayMethodSetCorrectProperties()
    {
        $this->subject->fromArray($this->collectionRecord);
        $this->assertEquals($this->collectionRecord['uid'], $this->subject->getIdentifier());
        $this->assertEquals($this->collectionRecord['uid'], $this->subject->getUid());
        $this->assertEquals($this->collectionRecord['title'], $this->subject->getTitle());
        $this->assertEquals($this->collectionRecord['description'], $this->subject->getDescription());
        $this->assertEquals($this->collectionRecord['table_name'], $this->subject->getItemTableName());
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create
     * @return void
     */
    public function canCreateDummyCollection()
    {
        $collection = CategoryCollection::create($this->collectionRecord);
        $this->assertInstanceOf(CategoryCollection::class, $collection);
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create
     * @return void
     */
    public function canCreateDummyCollectionAndFillItems()
    {
        $collection = CategoryCollection::create($this->collectionRecord, true);
        $this->assertInstanceOf(CategoryCollection::class, $collection);
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getCollectedRecords
     * @return void
     */
    public function getCollectedRecordsReturnsEmptyRecordSet()
    {
        $method = new \ReflectionMethod(CategoryCollection::class, 'getCollectedRecords');
        $method->setAccessible(true);
        $records = $method->invoke($this->subject);
        $this->assertInternalType('array', $records);
        $this->assertEmpty($records);
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageTableName
     * @return void
     */
    public function isStorageTableNameEqualsToSysCategory()
    {
        $this->assertEquals('sys_category', CategoryCollection::getStorageTableName());
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageItemsField
     * @return void
     */
    public function isStorageItemsFieldEqualsToItems()
    {
        $this->assertEquals('items', CategoryCollection::getStorageItemsField());
    }

    /**
     * @test
     * @return void
     */
    public function canLoadADummyCollectionFromDatabase()
    {
        /** @var $collection CategoryCollection */
        $collection = CategoryCollection::load($this->categoryUid, true, $this->tableName);
        // Check the number of record
        $this->assertEquals($this->numberOfRecords, $collection->count());
        // Check that the first record is the one expected
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable($this->tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where($queryBuilder->expr()->eq('uid', 1))
            ->execute();
        $record = $statement->fetch();
        $collection->rewind();
        $this->assertEquals($record, $collection->current());
        // Add a new record
        $fakeRecord = array(
            'uid' => $this->numberOfRecords + 1,
            'pid' => 0,
            'title' => $this->getUniqueId('title'),
            'categories' => 0
        );
        // Check the number of records
        $collection->add($fakeRecord);
        $this->assertEquals($this->numberOfRecords + 1, $collection->count());
    }

    /**
     * @test
     * @return void
     */
    public function canLoadADummyCollectionFromDatabaseAndAddRecord()
    {
        $collection = CategoryCollection::load($this->categoryUid, true, $this->tableName);
        // Add a new record
        $fakeRecord = array(
            'uid' => $this->numberOfRecords + 1,
            'pid' => 0,
            'title' => $this->getUniqueId('title'),
            'categories' => 0
        );
        // Check the number of records
        $collection->add($fakeRecord);
        $this->assertEquals($this->numberOfRecords + 1, $collection->count());
    }

    /**
     * @test
     * @return void
     */
    public function canLoadADummyCollectionWithoutContentFromDatabase()
    {
        /** @var $collection CategoryCollection */
        $collection = CategoryCollection::load($this->categoryUid, false, $this->tableName);
        // Check the number of record
        $this->assertEquals(0, $collection->count());
    }

    /**
     * @test
     * @return void
     */
    public function canLoadADummyCollectionFromDatabaseAfterRemoveOneRelation()
    {
        // Remove one relation
        $fakeName = array(
            'tablenames' => $this->getUniqueId('name')
        );
        $this->getConnectionPool()
            ->getConnectionForTable('sys_category_record_mm')
            ->update('sys_category_record_mm', $fakeName, ['uid_foreign' => 1]);
        // Check the number of records
        $collection = CategoryCollection::load($this->categoryUid, true, $this->tableName);
        $this->assertEquals($this->numberOfRecords - 1, $collection->count());
    }

    /********************/
    /* INTERNAL METHODS */
    /********************/
    /**
     * Create dummy table for testing purpose
     *
     * @return void
     */
    private function populateDummyTable()
    {
        for ($index = 1; $index <= $this->numberOfRecords; $index++) {
            $values = array(
                'title' => $this->getUniqueId('title')
            );
            $this->getConnectionPool()
                ->getConnectionForTable($this->tableName)
                ->insert($this->tableName, $values);
        }
    }

    /**
     * Make relation between tables
     *
     * @return void
     */
    private function makeRelationBetweenCategoryAndDummyTable()
    {
        for ($index = 1; $index <= $this->numberOfRecords; $index++) {
            $values = array(
                'uid_local' => $this->categoryUid,
                'uid_foreign' => $index,
                'tablenames' => $this->tableName,
                'fieldname' => 'categories'
            );
            $this->getConnectionPool()
                ->getConnectionForTable('sys_category_record_mm')
                ->insert('sys_category_record_mm', $values);
        }
    }

    /**
     * Create dummy table for testing purpose
     *
     * @return void
     */
    private function createDummyTable()
    {
        $connection = $this->getConnectionPool()
            ->getConnectionForTable($this->tableName);
        $currentSchema = $connection->getSchemaManager()->createSchema();
        $targetSchema = clone $currentSchema;

        $table = $targetSchema->createTable($this->tableName);
        $table->addColumn('uid', Type::INTEGER, ['length' => 11, 'unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('pid', Type::INTEGER, ['length' => 11, 'notnull' => true, 'default' => 0]);
        $table->addColumn('title', Type::STRING);
        $table->addColumn('tcategories', Type::INTEGER, ['length' => 11, 'unsigned' => true, 'notnull' => true, 'default' => 0]);
        $table->addColumn('sys_category_is_dummy_record', Type::INTEGER, ['length' => 11, 'unsigned' => true, 'notnull' => true, 'default' => 0]);
        $table->setPrimaryKey(['uid']);

        $queries = $currentSchema->getMigrateToSql($targetSchema, $connection->getDatabasePlatform());
        foreach ($queries as $query) {
            $connection->query($query);
        }
    }

    /**
     * Drop dummy table
     *
     * @return void
     */
    private function dropDummyTable()
    {
        $connection = $this->getConnectionPool()
            ->getConnectionForTable($this->tableName);
        $currentSchema = $connection->getSchemaManager()->createSchema();
        $targetSchema = clone $currentSchema;

        $targetSchema->dropTable($this->tableName);

        $queries = $currentSchema->getMigrateToSql($targetSchema, $connection->getDatabasePlatform());
        foreach ($queries as $query) {
            $connection->query($query);
        }
    }

    /**
     * Add is_dummy_record record and create dummy record
     *
     * @return void
     */
    private function prepareTables()
    {
        $connection = $this->getConnectionPool()
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $currentSchema = $connection->getSchemaManager()->createSchema();
        $targetSchema = clone $currentSchema;

        $columnOptions = ['length' => 1, 'unsigned' => true, 'notnull' => true, 'default' => 0];
        foreach ($this->tables as $table) {
            $targetSchema
                ->getTable($table)
                ->addColumn('is_dummy_record', Type::SMALLINT, $columnOptions);
        }

        $queries = $currentSchema->getMigrateToSql($targetSchema, $connection->getDatabasePlatform());
        foreach ($queries as $query) {
            $connection->query($query);
        }

        $values = array(
            'title' => $this->getUniqueId('title'),
            'l10n_diffsource' => '',
            'description' => '',
            'is_dummy_record' => 1
        );

        $connection->insert('sys_category', $values);
        $this->categoryUid = $connection->lastInsertId();
    }

    /**
     * Drops previously added dummy columns from core tables.
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @see prepareTables()
     */
    private function purgePreparedTables()
    {
        $connection = $this->getConnectionPool()
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $currentSchema = $connection->getSchemaManager()->createSchema();
        $targetSchema = clone $currentSchema;

        foreach ($this->tables as $table) {
            $targetSchema->getTable($table)->dropColumn('is_dummy_record');
        }

        $queries = $currentSchema->getMigrateToSql($targetSchema, $connection->getDatabasePlatform());
        foreach ($queries as $query) {
            $connection->query($query);
        }
    }
}
