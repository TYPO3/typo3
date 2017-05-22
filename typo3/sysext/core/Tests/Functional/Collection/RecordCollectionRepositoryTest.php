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

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use TYPO3\CMS\Core\Collection\RecordCollectionRepository;
use TYPO3\CMS\Core\Collection\StaticRecordCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case for \TYPO3\CMS\Core\Collection\RecordCollectionRepository
 */
class RecordCollectionRepositoryTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var RecordCollectionRepository|\PHPUnit_Framework_MockObject_MockObject
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

        $this->subject = $this->getMockBuilder(RecordCollectionRepository::class)
            ->setMethods(['getEnvironmentMode'])
            ->getMock();

        $this->testTableName = $this->getUniqueId('tx_testtable');

        $typoScriptFrontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            null,
            1,
            0
        );
        $typoScriptFrontendController->showHiddenRecords = false;
        $GLOBALS['TSFE'] = $typoScriptFrontendController;
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
            ['uid' => 1, 'type' => $type, 'table_name' => $this->testTableName],
            ['uid' => 2, 'type' => $type, 'table_name' => $this->testTableName]
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
            ['uid' => 1, 'type' => $type, 'table_name' => $this->testTableName],
            ['uid' => 2, 'type' => $type, 'table_name' => $this->testTableName]
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
            ['uid' => 1, 'type' => $type, 'table_name' => $this->testTableName],
            ['uid' => 2, 'type' => $type, 'table_name' => $this->testTableName]
        ]);
        $objects = $this->subject->findByTypeAndTableName($type, $this->testTableName);

        $this->assertCount(2, $objects);
        $this->assertInstanceOf(StaticRecordCollection::class, $objects[0]);
        $this->assertInstanceOf(StaticRecordCollection::class, $objects[1]);
    }

    /**
     * @test
     */
    public function doesFindByUidReturnAnObjectInBackendMode()
    {
        $this->subject->method('getEnvironmentMode')->willReturn('BE');
        $type = RecordCollectionRepository::TYPE_Static;
        $this->insertTestData([
            [
                'uid' => 1,
                'type' => $type,
                'table_name' => $this->testTableName,
                'deleted' => 0,
                'hidden' => 0,
                'starttime' => 0,
                'endtime' => 0
            ]
        ]);
        $object = $this->subject->findByUid(1);

        $this->assertInstanceOf(StaticRecordCollection::class, $object);
    }

    /**
     * @test
     */
    public function doesFindByUidRespectDeletedFieldInBackendMode()
    {
        $this->subject->method('getEnvironmentMode')->willReturn('BE');
        $type = RecordCollectionRepository::TYPE_Static;
        $this->insertTestData([
            [
                'uid' => 1,
                'type' => $type,
                'table_name' => $this->testTableName,
                'deleted' => 1,
                'hidden' => 0,
                'starttime' => 0,
                'endtime' => 0
            ]
        ]);
        $object = $this->subject->findByUid(1);

        $this->assertNull($object);
    }

    /**
     * @test
     */
    public function doesFindByUidIgnoreOtherEnableFieldsInBackendMode()
    {
        $this->subject->method('getEnvironmentMode')->willReturn('BE');
        $type = RecordCollectionRepository::TYPE_Static;
        $this->insertTestData([
            [
                'uid' => 1,
                'type' => $type,
                'table_name' => $this->testTableName,
                'hidden' => 1,
            ],
            [
                'uid' => 2,
                'type' => $type,
                'table_name' => $this->testTableName,
                'starttime' => time() + 99999,
            ],
            [
                'uid' => 3,
                'type' => $type,
                'table_name' => $this->testTableName,
                'endtime' => time() - 99999
            ]
        ]);
        $hiddenObject  = $this->subject->findByUid(1);
        $futureObject  = $this->subject->findByUid(2);
        $expiredObject = $this->subject->findByUid(3);

        $this->assertInstanceOf(StaticRecordCollection::class, $hiddenObject);
        $this->assertInstanceOf(StaticRecordCollection::class, $futureObject);
        $this->assertInstanceOf(StaticRecordCollection::class, $expiredObject);
    }

    /**
     * @test
     */
    public function doesFindByUidReturnAnObjectInFrontendMode()
    {
        $this->subject->method('getEnvironmentMode')->willReturn('FE');
        $type = RecordCollectionRepository::TYPE_Static;
        $this->insertTestData([
            [
                'uid' => 1,
                'type' => $type,
                'table_name' => $this->testTableName,
                'deleted' => 0,
                'hidden' => 0,
                'starttime' => 0,
                'endtime' => 0
            ]
        ]);
        $object = $this->subject->findByUid(1);

        $this->assertInstanceOf(StaticRecordCollection::class, $object);
    }

    /**
     * @test
     */
    public function doesFindByUidRespectEnableFieldsInFrontendMode()
    {
        $this->subject->method('getEnvironmentMode')->willReturn('FE');
        $type = RecordCollectionRepository::TYPE_Static;
        $this->insertTestData([
            [
                'uid' => 1,
                'type' => $type,
                'table_name' => $this->testTableName,
                'deleted' => 1,
            ],
            [
                'uid' => 2,
                'type' => $type,
                'table_name' => $this->testTableName,
                'hidden' => 1,
            ],
            [
                'uid' => 3,
                'type' => $type,
                'table_name' => $this->testTableName,
                'starttime' => time() + 99999,
            ],
            [
                'uid' => 4,
                'type' => $type,
                'table_name' => $this->testTableName,
                'endtime' => time() - 99999
            ]
        ]);
        $deletedObject = $this->subject->findByUid(1);
        $hiddenObject  = $this->subject->findByUid(2);
        $futureObject  = $this->subject->findByUid(3);
        $expiredObject = $this->subject->findByUid(4);

        $this->assertNull($deletedObject);
        $this->assertNull($hiddenObject);
        $this->assertNull($futureObject);
        $this->assertNull($expiredObject);
    }

    /**
     * Insert test rows into the sys_collection table
     *
     * @param array $rows
     */
    protected function insertTestData(array $rows)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_collection');
        $platform = $connection->getDatabasePlatform();
        $sqlServerIdentityDisabled = false;
        if ($platform instanceof SQLServerPlatform) {
            try {
                $connection->exec('SET IDENTITY_INSERT sys_collection ON');
                $sqlServerIdentityDisabled = true;
            } catch (\Doctrine\DBAL\DBALException $e) {
                // Some tables like sys_refindex don't have an auto-increment uid field and thus no
                // IDENTITY column. Instead of testing existance, we just try to set IDENTITY ON
                // and catch the possible error that occurs.
            }
        }

        $types = [];
        $tableDetails = $connection->getSchemaManager()->listTableDetails('sys_collection');
        foreach ($rows as $row) {
            foreach ($row as $columnName => $columnValue) {
                $types[] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
            }
            break;
        }

        foreach ($rows as $row) {
            $connection->insert('sys_collection', $row, $types);
        }

        if ($sqlServerIdentityDisabled) {
            // Reset identity if it has been changed
            $connection->exec('SET IDENTITY_INSERT sys_collection OFF');
        }
    }
}
