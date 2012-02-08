<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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
 * Test case for t3lib_collection_RecordCollectionRepository
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_collection_RecordCollectionRepositoryTest extends Tx_Phpunit_TestCase {
	/**
	 * @var t3lib_collection_RecordCollectionRepository
	 */
	protected $fixture;

	/**
	 * @var t3lib_DB
	 */
	protected $databaseMock;

	protected function setUp() {
		$this->databaseMock = $this->getMock(
			't3lib_DB',
			array('exec_UPDATEquery', 'exec_SELECTgetSingleRow', 'exec_SELECTgetRows')
		);

		$this->fixture = $this->getMock(
			't3lib_collection_RecordCollectionRepository',
			array('getDatabase')
		);
		$this->fixture
			->expects($this->any())
			->method('getDatabase')
			->will($this->returnValue($this->databaseMock));
	}

	protected function tearDown() {
		unset($this->databaseMock);
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function doesFindByUidReturnNull() {
		$testUid = rand(1, 1000);

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetSingleRow')
			->will($this->returnValue(NULL));

		$object = $this->fixture->findByUid($testUid);
		$this->assertNull($object);
	}

	/**
	 * @test
	 */
	public function doesFindByUidReturnObject() {
		$testUid = rand(1, 1000);

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetSingleRow')
			->will($this->returnValue(
				array('uid' => $testUid, 'type' => t3lib_collection_RecordCollectionRepository::TYPE_Static)
			));

		$object = $this->fixture->findByUid($testUid);
		$this->assertInstanceOf('t3lib_collection_StaticRecordCollection', $object);
	}

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function doesFindByUidThrowException() {
		$testUid = rand(1, 1000);

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetSingleRow')
			->will($this->returnValue(
				array('uid' => $testUid, 'type' => uniqid('unknown'))
			));

		$object = $this->fixture->findByUid($testUid);
	}

	/**
	 * @test
	 */
	public function doesFindByTypeReturnNull() {
		$type = t3lib_collection_RecordCollectionRepository::TYPE_Static;

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue(NULL));

		$objects = $this->fixture->findByType($type);

		$this->assertNull($objects);
	}

	/**
	 * @test
	 */
	public function doesFindByTypeReturnObjects() {
		$testUid = rand(1, 1000);
		$type = t3lib_collection_RecordCollectionRepository::TYPE_Static;

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue(
				array(
					array('uid' => $testUid, 'type' => $type),
					array('uid' => $testUid, 'type' => $type),
				)
			));

		$objects = $this->fixture->findByType($type);

		$this->assertEquals(2, count($objects));
		$this->assertInstanceOf('t3lib_collection_StaticRecordCollection', $objects[0]);
		$this->assertInstanceOf('t3lib_collection_StaticRecordCollection', $objects[1]);
	}

	/**
	 * @test
	 */
	public function doesFindByTableNameReturnNull() {
		$testTable = uniqid('sys_collection_');

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue(NULL));

		$objects = $this->fixture->findByTableName($testTable);

		$this->assertNull($objects);
	}

	/**
	 * @test
	 */
	public function doesFindByTableNameReturnObjects() {
		$testUid = rand(1, 1000);
		$testTable = uniqid('sys_collection_');
		$type = t3lib_collection_RecordCollectionRepository::TYPE_Static;

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue(
				array(
					array('uid' => $testUid, 'type' => $type),
					array('uid' => $testUid, 'type' => $type),
				)
			));

		$objects = $this->fixture->findByTableName($testTable);

		$this->assertEquals(2, count($objects));
		$this->assertInstanceOf('t3lib_collection_StaticRecordCollection', $objects[0]);
		$this->assertInstanceOf('t3lib_collection_StaticRecordCollection', $objects[1]);
	}

	/**
	 * @test
	 */
	public function doesFindByTypeAndTableNameReturnNull() {
		$testTable = uniqid('sys_collection_');
		$type = t3lib_collection_RecordCollectionRepository::TYPE_Static;

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue(NULL));

		$objects = $this->fixture->findByTypeAndTableName($type, $testTable);

		$this->assertNull($objects);
	}

	/**
	 * @test
	 */
	public function doesFindByTypeAndTableNameReturnObjects() {
		$testUid = rand(1, 1000);
		$testTable = uniqid('sys_collection_');
		$type = t3lib_collection_RecordCollectionRepository::TYPE_Static;

		$this->databaseMock
			->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue(
				array(
					array('uid' => $testUid, 'type' => $type),
					array('uid' => $testUid, 'type' => $type),
				)
			));

		$objects = $this->fixture->findByTypeAndTableName($type, $testTable);

		$this->assertEquals(2, count($objects));
		$this->assertInstanceOf('t3lib_collection_StaticRecordCollection', $objects[0]);
		$this->assertInstanceOf('t3lib_collection_StaticRecordCollection', $objects[1]);
	}
}
?>