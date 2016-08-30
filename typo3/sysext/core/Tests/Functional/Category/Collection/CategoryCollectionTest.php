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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for \TYPO3\CMS\Core\Category\Collection\CategoryCollection
 */
class CategoryCollectionTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Category\Collection\CategoryCollection
     */
    private $subject;

    /**
     * @var string
     */
    private $tableName = 'tx_foo_5001615c50bed';

    /**
     * @var array
     */
    private $tables = ['sys_category', 'sys_category_record_mm'];

    /**
     * @var int
     */
    private $categoryUid = 0;

    /**
     * @var array
     */
    private $collectionRecord = [];

    /**
     * @var int
     */
    private $numberOfRecords = 5;

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private $database;

    /**
     * Sets up this test suite.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->database = $this->getDatabaseConnection();
        $this->subject = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Category\Collection\CategoryCollection::class, $this->tableName);
        $this->collectionRecord = [
            'uid' => 0,
            'title' => $this->getUniqueId('title'),
            'description' => $this->getUniqueId('description'),
            'table_name' => $this->tableName,
        ];
        $GLOBALS['TCA'][$this->tableName] = ['ctrl' => []];
        // prepare environment
        $this->createDummyTable();
        $this->populateDummyTable();
        $this->prepareTables();
        $this->makeRelationBetweenCategoryAndDummyTable();
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
        $collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create($this->collectionRecord);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Category\Collection\CategoryCollection::class, $collection);
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create
     * @return void
     */
    public function canCreateDummyCollectionAndFillItems()
    {
        $collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create($this->collectionRecord, true);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Category\Collection\CategoryCollection::class, $collection);
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getCollectedRecords
     * @return void
     */
    public function getCollectedRecordsReturnsEmptyRecordSet()
    {
        $method = new \ReflectionMethod(\TYPO3\CMS\Core\Category\Collection\CategoryCollection::class, 'getCollectedRecords');
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
        $this->assertEquals('sys_category', \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageTableName());
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageItemsField
     * @return void
     */
    public function isStorageItemsFieldEqualsToItems()
    {
        $this->assertEquals('items', \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageItemsField());
    }

    /**
     * @test
     * @return void
     */
    public function canLoadADummyCollectionFromDatabase()
    {
        /** @var $collection \TYPO3\CMS\Core\Category\Collection\CategoryCollection */
        $collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::load($this->categoryUid, true, $this->tableName);
        // Check the number of record
        $this->assertEquals($this->numberOfRecords, $collection->count());
        // Check that the first record is the one expected
        $record = $this->database->exec_SELECTgetSingleRow('*', $this->tableName, 'uid=1');
        $collection->rewind();
        $this->assertEquals($record, $collection->current());
        // Add a new record
        $fakeRecord = [
            'uid' => $this->numberOfRecords + 1,
            'pid' => 0,
            'title' => $this->getUniqueId('title'),
            'categories' => 0
        ];
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
        $collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::load($this->categoryUid, true, $this->tableName);
        // Add a new record
        $fakeRecord = [
            'uid' => $this->numberOfRecords + 1,
            'pid' => 0,
            'title' => $this->getUniqueId('title'),
            'categories' => 0
        ];
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
        /** @var $collection \TYPO3\CMS\Core\Category\Collection\CategoryCollection */
        $collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::load($this->categoryUid, false, $this->tableName);
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
        $fakeName = [
            'tablenames' => $this->getUniqueId('name')
        ];
        $this->database->exec_UPDATEquery(
            'sys_category_record_mm',
            'uid_foreign = 1',
            $fakeName
        );
        // Check the number of records
        $collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::load($this->categoryUid, true, $this->tableName);
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
            $values = [
                'title' => $this->getUniqueId('title')
            ];
            $this->database->exec_INSERTquery($this->tableName, $values);
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
            $values = [
                'uid_local' => $this->categoryUid,
                'uid_foreign' => $index,
                'tablenames' => $this->tableName,
                'fieldname' => 'categories'
            ];
            $this->database->exec_INSERTquery('sys_category_record_mm', $values);
        }
    }

    /**
     * Create dummy table for testing purpose
     *
     * @return void
     */
    private function createDummyTable()
    {
        $sql = 'CREATE TABLE ' . $this->tableName . ' (' . LF . TAB .
            'uid int(11) auto_increment,' . LF . TAB .
            'pid int(11) unsigned DEFAULT \'0\' NOT NULL,' . LF . TAB .
            'title tinytext,' . LF . TAB .
            'tcategories int(11) unsigned DEFAULT \'0\' NOT NULL,' . LF . TAB .
            'sys_category_is_dummy_record int(11) unsigned DEFAULT \'0\' NOT NULL,' . LF . LF . TAB .
            'PRIMARY KEY (uid)' . LF . ');';
        $this->database->sql_query($sql);
    }

    /**
     * Drop dummy table
     *
     * @return void
     */
    private function dropDummyTable()
    {
        $sql = 'DROP TABLE ' . $this->tableName . ';';
        $this->database->sql_query($sql);
    }

    /**
     * Add is_dummy_record record and create dummy record
     *
     * @return void
     */
    private function prepareTables()
    {
        $sql = 'ALTER TABLE %s ADD is_dummy_record tinyint(1) unsigned DEFAULT \'0\' NOT NULL';
        foreach ($this->tables as $table) {
            $_sql = sprintf($sql, $table);
            $this->database->sql_query($_sql);
        }
        $values = [
            'title' => $this->getUniqueId('title'),
            'l10n_diffsource' => '',
            'description' => '',
            'is_dummy_record' => 1
        ];
        $this->database->exec_INSERTquery('sys_category', $values);
        $this->categoryUid = $this->database->sql_insert_id();
    }
}
