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

namespace TYPO3\CMS\Core\Tests\Functional\Category\Collection;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Category\Collection\CategoryCollection;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CategoryCollectionTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    private $collectionRecord = [];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_tca',
    ];

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->collectionRecord = [
            'uid' => 0,
            'title' => StringUtility::getUniqueId('title'),
            'description' => StringUtility::getUniqueId('description'),
            'table_name' => 'tx_test_test',
            'field_name' => 'categories',
        ];
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/categoryRelations.csv');
    }

    #[Test]
    public function checkIfFromArrayMethodSetCorrectProperties(): void
    {
        $subject = new CategoryCollection('tx_test_test');
        $subject->fromArray($this->collectionRecord);
        self::assertEquals($this->collectionRecord['uid'], $subject->getIdentifier());
        self::assertEquals($this->collectionRecord['uid'], $subject->getUid());
        self::assertEquals($this->collectionRecord['title'], $subject->getTitle());
        self::assertEquals($this->collectionRecord['description'], $subject->getDescription());
        self::assertEquals($this->collectionRecord['table_name'], $subject->getItemTableName());
    }

    #[Test]
    public function canCreateDummyCollection(): void
    {
        $collection = CategoryCollection::create($this->collectionRecord);
        self::assertInstanceOf(CategoryCollection::class, $collection);
    }

    #[Test]
    public function canCreateDummyCollectionAndFillItems(): void
    {
        $collection = CategoryCollection::create($this->collectionRecord, true);
        self::assertInstanceOf(CategoryCollection::class, $collection);
    }

    #[Test]
    public function getCollectedRecordsReturnsEmptyRecordSet(): void
    {
        $subject = new CategoryCollection('tx_test_test');
        $method = new \ReflectionMethod(CategoryCollection::class, 'getCollectedRecords');
        $records = $method->invoke($subject);
        self::assertIsArray($records);
        self::assertEmpty($records);
    }

    #[Test]
    public function isStorageTableNameEqualsToSysCategory(): void
    {
        self::assertEquals('sys_category', CategoryCollection::getStorageTableName());
    }

    #[Test]
    public function isStorageItemsFieldEqualsToItems(): void
    {
        self::assertEquals('items', CategoryCollection::getStorageItemsField());
    }

    #[Test]
    public function canLoadADummyCollectionFromDatabase(): void
    {
        $collection = CategoryCollection::load(1, true, 'tx_test_test');
        // Check the number of record
        self::assertEquals(5, $collection->count());
        // Check that the first record is the one expected
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('tx_test_test');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder
            ->select('*')
            ->from('tx_test_test')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))
            ->executeQuery();
        $record = $statement->fetchAssociative();
        $collection->rewind();
        self::assertEquals($record, $collection->current());
        // Add a new record
        $fakeRecord = [
            'uid' => 6,
            'pid' => 0,
            'title' => StringUtility::getUniqueId('title'),
            'categories' => 0,
        ];
        // Check the number of records
        $collection->add($fakeRecord);
        self::assertEquals(6, $collection->count());
    }

    #[Test]
    public function canLoadADummyCollectionFromDatabaseAndAddRecord(): void
    {
        $collection = CategoryCollection::load(1, true, 'tx_test_test');
        // Add a new record
        $fakeRecord = [
            'uid' => 6,
            'pid' => 0,
            'title' => StringUtility::getUniqueId('title'),
            'categories' => 0,
        ];
        // Check the number of records
        $collection->add($fakeRecord);
        self::assertEquals(6, $collection->count());
    }

    #[Test]
    public function canLoadADummyCollectionWithoutContentFromDatabase(): void
    {
        $collection = CategoryCollection::load(1, false, 'tx_test_test');
        // Check the number of record
        self::assertEquals(0, $collection->count());
    }

    #[Test]
    public function canLoadADummyCollectionFromNotExistingCategory(): void
    {
        $collection = CategoryCollection::load(-1, false, 'tx_test_test');
        // Check the number of record
        self::assertEquals(0, $collection->count());
    }

    #[Test]
    public function canLoadADummyCollectionFromDatabaseAfterRemoveOneRelation(): void
    {
        // Remove one relation
        $fakeName = [
            'tablenames' => StringUtility::getUniqueId('name'),
        ];
        $this->getConnectionPool()
            ->getConnectionForTable('sys_category_record_mm')
            ->update('sys_category_record_mm', $fakeName, ['uid_foreign' => 1]);
        // Check the number of records
        $collection = CategoryCollection::load(1, true, 'tx_test_test');
        self::assertEquals(4, $collection->count());
    }
}
