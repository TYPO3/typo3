<?php
namespace TYPO3\CMS\Core\Tests\Unit\Collection;

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

/**
 * Test case for \TYPO3\CMS\Core\Collection\RecordCollectionRepository
 */
class RecordCollectionRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Collection\RecordCollectionRepository
     */
    protected $subject;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseMock;

    /**
     * @var NULL|array
     */
    protected $getSingleRowCallbackReturnValue;

    /**
     * @var NULL|array
     */
    protected $getRowsCallbackReturnValue;

    /**
     * @var string
     */
    protected $testTableName;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        $this->databaseMock = $this->getMock(
            \TYPO3\CMS\Core\Database\DatabaseConnection::class,
            ['exec_UPDATEquery', 'exec_SELECTgetSingleRow', 'exec_SELECTgetRows', 'fullQuoteStr']
        );
        $this->subject = $this->getMock(\TYPO3\CMS\Core\Collection\RecordCollectionRepository::class, ['getDatabaseConnection']);
        $this->subject->expects($this->any())->method('getDatabaseConnection')->will($this->returnValue($this->databaseMock));
        $this->testTableName = $this->getUniqueId('tx_testtable');
    }

    /**
     * @test
     */
    public function doesFindByUidReturnNull()
    {
        $testUid = rand(1, 1000);
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetSingleRow')->will($this->returnCallback([$this, 'getSingleRowCallback']));
        $this->getSingleRowCallbackReturnValue = null;
        $object = $this->subject->findByUid($testUid);
        $this->assertNull($object);
    }

    /**
     * @test
     */
    public function doesFindByUidReturnObject()
    {
        $testUid = rand(1, 1000);
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetSingleRow')->will($this->returnCallback([$this, 'getSingleRowCallback']));
        $this->getSingleRowCallbackReturnValue = [
            'uid' => $testUid,
            'type' => \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static,
            'table_name' => $this->testTableName
        ];
        $object = $this->subject->findByUid($testUid);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Collection\StaticRecordCollection::class, $object);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function doesFindByUidThrowException()
    {
        $testUid = rand(1, 1000);
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetSingleRow')->will($this->returnCallback([$this, 'getSingleRowCallback']));
        $this->getSingleRowCallbackReturnValue = [
            'uid' => $testUid,
            'type' => $this->getUniqueId('unknown')
        ];
        $object = $this->subject->findByUid($testUid);
    }

    /**
     * @test
     */
    public function doesFindByTypeReturnNull()
    {
        $type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback([$this, 'getRowsCallback']));
        $this->getRowsCallbackReturnValue = null;
        $objects = $this->subject->findByType($type);
        $this->assertNull($objects);
    }

    /**
     * @test
     */
    public function doesFindByTypeReturnObjects()
    {
        $testUid = rand(1, 1000);
        $type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback([$this, 'getRowsCallback']));
        $this->getRowsCallbackReturnValue = [
            ['uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName],
            ['uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName]
        ];
        $objects = $this->subject->findByType($type);
        $this->assertEquals(2, count($objects));
        $this->assertInstanceOf(\TYPO3\CMS\Core\Collection\StaticRecordCollection::class, $objects[0]);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Collection\StaticRecordCollection::class, $objects[1]);
    }

    /**
     * @test
     */
    public function doesFindByTableNameReturnNull()
    {
        $testTable = $this->getUniqueId('sys_collection_');
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback([$this, 'getRowsCallback']));
        $this->getRowsCallbackReturnValue = null;
        $objects = $this->subject->findByTableName($testTable);
        $this->assertNull($objects);
    }

    /**
     * @test
     */
    public function doesFindByTableNameReturnObjects()
    {
        $testUid = rand(1, 1000);
        $testTable = $this->getUniqueId('sys_collection_');
        $type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback([$this, 'getRowsCallback']));
        $this->getRowsCallbackReturnValue = [
            ['uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName],
            ['uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName]
        ];
        $objects = $this->subject->findByTableName($testTable);
        $this->assertEquals(2, count($objects));
        $this->assertInstanceOf(\TYPO3\CMS\Core\Collection\StaticRecordCollection::class, $objects[0]);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Collection\StaticRecordCollection::class, $objects[1]);
    }

    /**
     * @test
     */
    public function doesFindByTypeAndTableNameReturnNull()
    {
        $testTable = $this->getUniqueId('sys_collection_');
        $type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback([$this, 'getRowsCallback']));
        $this->getRowsCallbackReturnValue = null;
        $objects = $this->subject->findByTypeAndTableName($type, $testTable);
        $this->assertNull($objects);
    }

    /**
     * @test
     */
    public function doesFindByTypeAndTableNameReturnObjects()
    {
        $testUid = rand(1, 1000);
        $testTable = $this->getUniqueId('sys_collection_');
        $type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
        $this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback([$this, 'getRowsCallback']));
        $this->getRowsCallbackReturnValue = [
            ['uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName],
            ['uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName]
        ];
        $objects = $this->subject->findByTypeAndTableName($type, $testTable);
        $this->assertEquals(2, count($objects));
        $this->assertInstanceOf(\TYPO3\CMS\Core\Collection\StaticRecordCollection::class, $objects[0]);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Collection\StaticRecordCollection::class, $objects[1]);
    }

    /**
     * Callback for exec_SELECTgetSingleRow
     *
     * @param string $fields
     * @param string $table
     * @return NULL|array
     */
    public function getSingleRowCallback($fields, $table)
    {
        if (!is_array($this->getSingleRowCallbackReturnValue) || $fields === '*') {
            $returnValue = $this->getSingleRowCallbackReturnValue;
        } else {
            $returnValue = $this->limitRecordFields($fields, $this->getSingleRowCallbackReturnValue);
        }
        return $returnValue;
    }

    /**
     * Callback for exec_SELECTgetRows
     *
     * @param string $fields
     * @param string $table
     * @return NULL|array
     */
    public function getRowsCallback($fields, $table)
    {
        if (!is_array($this->getRowsCallbackReturnValue) || $fields === '*') {
            $returnValue = $this->getRowsCallbackReturnValue;
        } else {
            $returnValue = [];
            foreach ($this->getRowsCallbackReturnValue as $record) {
                $returnValue[] = $this->limitRecordFields($fields, $record);
            }
        }
        return $returnValue;
    }

    /**
     * Limits record fields to a given field list.
     *
     * @param string $fields List of fields
     * @param array $record The database record (or the simulated one)
     * @return array
     */
    protected function limitRecordFields($fields, array $record)
    {
        $result = [];
        foreach ($record as $field => $value) {
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($fields, $field)) {
                $result[$field] = $value;
            }
        }
        return $result;
    }
}
