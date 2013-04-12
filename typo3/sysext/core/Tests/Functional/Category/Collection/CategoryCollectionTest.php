<?php
namespace TYPO3\CMS\Core\Tests\Functional\Category\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Fabien Udriot <fabien.udriot@typo3.org>
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
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case for \TYPO3\CMS\Core\Category\Collection\CategoryCollection
 *
 * @author Fabien Udriot <fabien.udriot@typo3.org>
 */
class CategoryCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Category\Collection\CategoryCollection
	 */
	private $fixture;

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
	 * @var integer
	 */
	private $numberOfRecords = 5;

	/**
	 * @var \Tx_Phpunit_Framework
	 */
	private $testingFramework;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	private $database;

	/**
	 * Sets up this test suite.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->database = $GLOBALS['TYPO3_DB'];
		$this->fixture = new \TYPO3\CMS\Core\Category\Collection\CategoryCollection($this->tableName);
		$this->collectionRecord = array(
			'uid' => 0,
			'title' => uniqid('title'),
			'description' => uniqid('description'),
			'table_name' => $this->tableName,
		);
		$GLOBALS['TCA'][$this->tableName] = array('ctrl' => array());
		// prepare environment
		$this->createDummyTable();
		$this->testingFramework = new \Tx_Phpunit_Framework('sys_category', array('tx_foo'));
		$this->populateDummyTable();
		$this->prepareTables();
		$this->makeRelationBetweenCategoryAndDummyTable();
	}

	/**
	 * Tears down this test suite.
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->testingFramework->cleanUp();
		// clean up environment
		$this->dropDummyTable();
		$this->dropDummyField();
		unset($this->testingFramework);
		unset($this->collectionRecord);
		unset($this->fixture);
		unset($this->database);
	}

	/**
	 * @test
	 * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::fromArray
	 * @return void
	 */
	public function checkIfFromArrayMethodSetCorrectProperties() {
		$this->fixture->fromArray($this->collectionRecord);
		$this->assertEquals($this->collectionRecord['uid'], $this->fixture->getIdentifier());
		$this->assertEquals($this->collectionRecord['uid'], $this->fixture->getUid());
		$this->assertEquals($this->collectionRecord['title'], $this->fixture->getTitle());
		$this->assertEquals($this->collectionRecord['description'], $this->fixture->getDescription());
		$this->assertEquals($this->collectionRecord['table_name'], $this->fixture->getItemTableName());
	}

	/**
	 * @test
	 * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create
	 * @return void
	 */
	public function canCreateDummyCollection() {
		$collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create($this->collectionRecord);
		$this->assertInstanceOf('\TYPO3\CMS\Core\Category\Collection\CategoryCollection', $collection);
	}

	/**
	 * @test
	 * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create
	 * @return void
	 */
	public function canCreateDummyCollectionAndFillItems() {
		$collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::create($this->collectionRecord, TRUE);
		$this->assertInstanceOf('\TYPO3\CMS\Core\Category\Collection\CategoryCollection', $collection);
	}

	/**
	 * @test
	 * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getCollectedRecords
	 * @return void
	 */
	public function getCollectedRecordsReturnsEmptyRecordSet() {
		$method = new \ReflectionMethod('TYPO3\\CMS\\Core\\Category\\Collection\\CategoryCollection', 'getCollectedRecords');
		$method->setAccessible(TRUE);
		$records = $method->invoke($this->fixture);
		$this->assertInternalType('array', $records);
		$this->assertEmpty($records);
	}

	/**
	 * @test
	 * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageTableName
	 * @return void
	 */
	public function isStorageTableNameEqualsToSysCategory() {
		$this->assertEquals('sys_category', \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageTableName());
	}

	/**
	 * @test
	 * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageItemsField
	 * @return void
	 */
	public function isStorageItemsFieldEqualsToItems() {
		$this->assertEquals('items', \TYPO3\CMS\Core\Category\Collection\CategoryCollection::getStorageItemsField());
	}

	/**
	 * @test
	 * @return void
	 */
	public function canLoadADummyCollectionFromDatabase() {
		/** @var $collection \TYPO3\CMS\Core\Category\Collection\CategoryCollection */
		$collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::load($this->categoryUid, TRUE, $this->tableName);
		// Check the number of record
		$this->assertEquals($this->numberOfRecords, $collection->count());
		// Check that the first record is the one expected
		$record = $this->database->exec_SELECTgetSingleRow('*', $this->tableName, 'uid=1');
		$collection->rewind();
		$this->assertEquals($record, $collection->current());
		// Add a new record
		$fakeRecord = array(
			'uid' => $this->numberOfRecords + 1,
			'pid' => 0,
			'title' => uniqid('title'),
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
	public function canLoadADummyCollectionFromDatabaseAndAddRecord() {
		$collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::load($this->categoryUid, TRUE, $this->tableName);
		// Add a new record
		$fakeRecord = array(
			'uid' => $this->numberOfRecords + 1,
			'pid' => 0,
			'title' => uniqid('title'),
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
	public function canLoadADummyCollectionWithoutContentFromDatabase() {
		/** @var $collection \TYPO3\CMS\Core\Category\Collection\CategoryCollection */
		$collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::load($this->categoryUid, FALSE, $this->tableName);
		// Check the number of record
		$this->assertEquals(0, $collection->count());
	}

	/********************/
	/* INTERNAL METHODS */
	/********************/
	/**
	 * Create dummy table for testing purpose
	 *
	 * @return void
	 */
	private function populateDummyTable() {
		for ($index = 1; $index <= $this->numberOfRecords; $index++) {
			$values = array(
				'title' => uniqid('title')
			);
			$this->testingFramework->createRecord($this->tableName, $values);
		}
	}

	/**
	 * Make relation between tables
	 *
	 * @return void
	 */
	private function makeRelationBetweenCategoryAndDummyTable() {
		for ($index = 1; $index <= $this->numberOfRecords; $index++) {
			$values = array(
				'uid_local' => $this->categoryUid,
				'uid_foreign' => $index,
				'tablenames' => $this->tableName
			);
			$this->testingFramework->createRecord('sys_category_record_mm', $values);
		}
	}

	/**
	 * Create dummy table for testing purpose
	 *
	 * @return void
	 */
	private function createDummyTable() {
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
	private function dropDummyTable() {
		$sql = 'DROP TABLE ' . $this->tableName . ';';
		$this->database->sql_query($sql);
	}

	/**
	 * Add is_dummy_record record and create dummy record
	 *
	 * @return void
	 */
	private function prepareTables() {
		$sql = 'ALTER TABLE %s ADD is_dummy_record tinyint(1) unsigned DEFAULT \'0\' NOT NULL';
		foreach ($this->tables as $table) {
			$_sql = sprintf($sql, $table);
			$this->database->sql_query($_sql);
		}
		$values = array(
			'title' => uniqid('title'),
			'is_dummy_record' => 1
		);
		$this->categoryUid = $this->testingFramework->createRecord('sys_category', $values);
	}

	/**
	 * Remove dummy record and drop field
	 *
	 * @return void
	 */
	private function dropDummyField() {
		$sql = 'ALTER TABLE %s DROP COLUMN is_dummy_record';
		foreach ($this->tables as $table) {
			$_sql = sprintf($sql, $table);
			$this->database->sql_query($_sql);
		}
	}

}

?>