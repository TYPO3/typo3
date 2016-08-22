<?php
namespace TYPO3\CMS\Core\Tests\Functional\Collection;

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

use TYPO3\CMS\Core\Collection\RecordCollectionRepository;
use TYPO3\CMS\Core\Collection\StaticRecordCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for \TYPO3\CMS\Core\Collection\RecordCollectionRepository
 */
class RecordCollectionRepositoryTest extends FunctionalTestCase
{
    /**
     * @var RecordCollectionRepository
     */
    protected $subject;

    /**
     * @var string
     */
    protected $testTableName;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->subject = GeneralUtility::makeInstance(RecordCollectionRepository::class);
        $this->testTableName = $this->getUniqueId('tx_testtable');
    }

    protected function tearDown()
    {
        parent::tearDown();

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_collection')
            ->truncate('sys_collection');
    }

    /**
     * @test
     */
    public function doesFindByTypeReturnNull()
    {
        $type = RecordCollectionRepository::TYPE_Static;
        $objects = $this->subject->findByType($type);
        $this->assertNull($objects);
    }

    /**
     * @test
     */
    public function doesFindByTypeReturnObjects()
    {
        $type = RecordCollectionRepository::TYPE_Static;
        $this->insertTestData([
            ['type' => $type, 'table_name' => $this->testTableName],
            ['type' => $type, 'table_name' => $this->testTableName]
        ]);

        $objects = $this->subject->findByType($type);
        $this->assertCount(2, $objects);
        $this->assertInstanceOf(StaticRecordCollection::class, $objects[0]);
        $this->assertInstanceOf(StaticRecordCollection::class, $objects[1]);
    }

    /**
     * @test
     */
    public function doesFindByTableNameReturnNull()
    {
        $objects = $this->subject->findByTableName($this->testTableName);
        $this->assertNull($objects);
    }

    /**
     * @test
     */
    public function doesFindByTableNameReturnObjects()
    {
        $type = RecordCollectionRepository::TYPE_Static;
        $this->insertTestData([
            ['type' => $type, 'table_name' => $this->testTableName],
            ['type' => $type, 'table_name' => $this->testTableName]
        ]);
        $objects = $this->subject->findByTableName($this->testTableName);

        $this->assertCount(2, $objects);
        $this->assertInstanceOf(StaticRecordCollection::class, $objects[0]);
        $this->assertInstanceOf(StaticRecordCollection::class, $objects[1]);
    }

    /**
     * @test
     */
    public function doesFindByTypeAndTableNameReturnNull()
    {
        $type = RecordCollectionRepository::TYPE_Static;
        $objects = $this->subject->findByTypeAndTableName($type, $this->testTableName);

        $this->assertNull($objects);
    }

    /**
     * @test
     */
    public function doesFindByTypeAndTableNameReturnObjects()
    {
        $type = RecordCollectionRepository::TYPE_Static;
        $this->insertTestData([
            ['type' => $type, 'table_name' => $this->testTableName],
            ['type' => $type, 'table_name' => $this->testTableName]
        ]);
        $objects = $this->subject->findByTypeAndTableName($type, $this->testTableName);

        $this->assertCount(2, $objects);
        $this->assertInstanceOf(StaticRecordCollection::class, $objects[0]);
        $this->assertInstanceOf(StaticRecordCollection::class, $objects[1]);
    }

    /**
     * Insert test rows into the sys_collection table
     *
     * @param array $rows
     */
    protected function insertTestData(array $rows)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_collection');

        foreach ($rows as $row) {
            $connection->insert('sys_collection', $row);
        }
    }
}
