<?php
namespace TYPO3\CMS\Core\Tests\Unit\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case for \TYPO3\CMS\Core\Collection\RecordCollectionRepository
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class RecordCollectionRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Collection\RecordCollectionRepository
	 */
	protected $fixture;

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
	protected function setUp() {
		$this->databaseMock = $this->getMock(
			'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
			array('exec_UPDATEquery', 'exec_SELECTgetSingleRow', 'exec_SELECTgetRows', 'fullQuoteStr')
		);
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Collection\\RecordCollectionRepository', array('getDatabase'));
		$this->fixture->expects($this->any())->method('getDatabase')->will($this->returnValue($this->databaseMock));
		$this->testTableName = uniqid('tx_testtable');
	}

	/**
	 * Cleans up this test case.
	 */
	protected function tearDown() {
		unset($this->databaseMock);
		unset($this->fixture);
		unset($this->getSingleRowCallbackReturnValue);
		unset($this->getRowsCallbackReturnValue);
		unset($this->testTableName);
	}

	/**
	 * @test
	 */
	public function doesFindByUidReturnNull() {
		$testUid = rand(1, 1000);
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetSingleRow')->will($this->returnCallback(array($this, 'getSingleRowCallback')));
		$this->getSingleRowCallbackReturnValue = NULL;
		$object = $this->fixture->findByUid($testUid);
		$this->assertNull($object);
	}

	/**
	 * @test
	 */
	public function doesFindByUidReturnObject() {
		$testUid = rand(1, 1000);
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetSingleRow')->will($this->returnCallback(array($this, 'getSingleRowCallback')));
		$this->getSingleRowCallbackReturnValue = array(
			'uid' => $testUid,
			'type' => \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static,
			'table_name' => $this->testTableName
		);
		$object = $this->fixture->findByUid($testUid);
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection', $object);
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function doesFindByUidThrowException() {
		$testUid = rand(1, 1000);
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetSingleRow')->will($this->returnCallback(array($this, 'getSingleRowCallback')));
		$this->getSingleRowCallbackReturnValue = array(
			'uid' => $testUid,
			'type' => uniqid('unknown')
		);
		$object = $this->fixture->findByUid($testUid);
	}

	/**
	 * @test
	 */
	public function doesFindByTypeReturnNull() {
		$type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback(array($this, 'getRowsCallback')));
		$this->getRowsCallbackReturnValue = NULL;
		$objects = $this->fixture->findByType($type);
		$this->assertNull($objects);
	}

	/**
	 * @test
	 */
	public function doesFindByTypeReturnObjects() {
		$testUid = rand(1, 1000);
		$type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback(array($this, 'getRowsCallback')));
		$this->getRowsCallbackReturnValue = array(
			array('uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName),
			array('uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName)
		);
		$objects = $this->fixture->findByType($type);
		$this->assertEquals(2, count($objects));
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection', $objects[0]);
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection', $objects[1]);
	}

	/**
	 * @test
	 */
	public function doesFindByTableNameReturnNull() {
		$testTable = uniqid('sys_collection_');
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback(array($this, 'getRowsCallback')));
		$this->getRowsCallbackReturnValue = NULL;
		$objects = $this->fixture->findByTableName($testTable);
		$this->assertNull($objects);
	}

	/**
	 * @test
	 */
	public function doesFindByTableNameReturnObjects() {
		$testUid = rand(1, 1000);
		$testTable = uniqid('sys_collection_');
		$type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback(array($this, 'getRowsCallback')));
		$this->getRowsCallbackReturnValue = array(
			array('uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName),
			array('uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName)
		);
		$objects = $this->fixture->findByTableName($testTable);
		$this->assertEquals(2, count($objects));
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection', $objects[0]);
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection', $objects[1]);
	}

	/**
	 * @test
	 */
	public function doesFindByTypeAndTableNameReturnNull() {
		$testTable = uniqid('sys_collection_');
		$type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback(array($this, 'getRowsCallback')));
		$this->getRowsCallbackReturnValue = NULL;
		$objects = $this->fixture->findByTypeAndTableName($type, $testTable);
		$this->assertNull($objects);
	}

	/**
	 * @test
	 */
	public function doesFindByTypeAndTableNameReturnObjects() {
		$testUid = rand(1, 1000);
		$testTable = uniqid('sys_collection_');
		$type = \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE_Static;
		$this->databaseMock->expects($this->once())->method('exec_SELECTgetRows')->will($this->returnCallback(array($this, 'getRowsCallback')));
		$this->getRowsCallbackReturnValue = array(
			array('uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName),
			array('uid' => $testUid, 'type' => $type, 'table_name' => $this->testTableName)
		);
		$objects = $this->fixture->findByTypeAndTableName($type, $testTable);
		$this->assertEquals(2, count($objects));
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection', $objects[0]);
		$this->assertInstanceOf('TYPO3\\CMS\\Core\\Collection\\StaticRecordCollection', $objects[1]);
	}

	/**
	 * Callback for exec_SELECTgetSingleRow
	 *
	 * @param string $fields
	 * @param string $table
	 * @return NULL|array
	 */
	public function getSingleRowCallback($fields, $table) {
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
	public function getRowsCallback($fields, $table) {
		if (!is_array($this->getRowsCallbackReturnValue) || $fields === '*') {
			$returnValue = $this->getRowsCallbackReturnValue;
		} else {
			$returnValue = array();
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
	protected function limitRecordFields($fields, array $record) {
		$result = array();
		foreach ($record as $field => $value) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($fields, $field)) {
				$result[$field] = $value;
			}
		}
		return $result;
	}

}

?>