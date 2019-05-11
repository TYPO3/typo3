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

use TYPO3\CMS\Core\Category\Collection\CategoryCollection;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class CategoryCollectionTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    private $collectionRecord = [];

    /**
     * @var array Load test fixture extension
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Category/Collection/Fixtures/Extensions/test'
    ];

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->collectionRecord = [
            'uid' => 0,
            'title' => $this->getUniqueId('title'),
            'description' => $this->getUniqueId('description'),
            'table_name' => 'tx_test_test',
        ];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/categoryRelations.csv');
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::fromArray
     */
    public function checkIfFromArrayMethodSetCorrectProperties()
    {
        $subject = new CategoryCollection('tx_test_test');
        $subject->fromArray($this->collectionRecord);
        $this->assertEquals($this->collectionRecord['uid'], $subject->getIdentifier());
        $this->assertEquals($this->collectionRecord['uid'], $subject->getUid());
        $this->assertEquals($this->collectionRecord['title'], $subject->getTitle());
        $this->assertEquals($this->collectionRecord['description'], $subject->getDescription());
        $this->assertEquals($this->collectionRecord['table_name'], $subject->getItemTableName());
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create
     */
    public function canCreateDummyCollection()
    {
        $collection = CategoryCollection::create($this->collectionRecord);
        $this->assertInstanceOf(CategoryCollection::class, $collection);
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create
     */
    public function canCreateDummyCollectionAndFillItems()
    {
        $collection = CategoryCollection::create($this->collectionRecord, true);
        $this->assertInstanceOf(CategoryCollection::class, $collection);
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getCollectedRecords
     */
    public function getCollectedRecordsReturnsEmptyRecordSet()
    {
        $subject = new CategoryCollection('tx_test_test');
        $method = new \ReflectionMethod(CategoryCollection::class, 'getCollectedRecords');
        $method->setAccessible(true);
        $records = $method->invoke($subject);
        $this->assertIsArray($records);
        $this->assertEmpty($records);
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageTableName
     */
    public function isStorageTableNameEqualsToSysCategory()
    {
        $this->assertEquals('sys_category', CategoryCollection::getStorageTableName());
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageItemsField
     */
    public function isStorageItemsFieldEqualsToItems()
    {
        $this->assertEquals('items', CategoryCollection::getStorageItemsField());
    }

    /**
     * @test
     */
    public function canLoadADummyCollectionFromDatabase()
    {
        /** @var $collection CategoryCollection */
        $collection = CategoryCollection::load(1, true, 'tx_test_test');
        // Check the number of record
        $this->assertEquals(5, $collection->count());
        // Check that the first record is the one expected
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tx_test_test');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder
            ->select('*')
            ->from('tx_test_test')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))
            ->execute();
        $record = $statement->fetch();
        $collection->rewind();
        $this->assertEquals($record, $collection->current());
        // Add a new record
        $fakeRecord = [
            'uid' => 6,
            'pid' => 0,
            'title' => $this->getUniqueId('title'),
            'categories' => 0
        ];
        // Check the number of records
        $collection->add($fakeRecord);
        $this->assertEquals(6, $collection->count());
    }

    /**
     * @test
     */
    public function canLoadADummyCollectionFromDatabaseAndAddRecord()
    {
        $collection = CategoryCollection::load(1, true, 'tx_test_test');
        // Add a new record
        $fakeRecord = [
            'uid' => 6,
            'pid' => 0,
            'title' => $this->getUniqueId('title'),
            'categories' => 0
        ];
        // Check the number of records
        $collection->add($fakeRecord);
        $this->assertEquals(6, $collection->count());
    }

    /**
     * @test
     */
    public function canLoadADummyCollectionWithoutContentFromDatabase()
    {
        /** @var $collection CategoryCollection */
        $collection = CategoryCollection::load(1, false, 'tx_test_test');
        // Check the number of record
        $this->assertEquals(0, $collection->count());
    }

    /**
     * @test
     */
    public function canLoadADummyCollectionFromDatabaseAfterRemoveOneRelation()
    {
        // Remove one relation
        $fakeName = [
            'tablenames' => $this->getUniqueId('name')
        ];
        $this->getConnectionPool()
            ->getConnectionForTable('sys_category_record_mm')
            ->update('sys_category_record_mm', $fakeName, ['uid_foreign' => 1]);
        // Check the number of records
        $collection = CategoryCollection::load(1, true, 'tx_test_test');
        $this->assertEquals(4, $collection->count());
    }
}
