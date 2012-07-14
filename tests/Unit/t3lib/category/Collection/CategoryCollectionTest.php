<?php
/**
 * Test case for t3lib_category_CategoryCollection
 *
 * @package TYPO3
 * @subpackage t3lib
 * @author Fabine Udriot <fabien.udriot@typo3.org>
 */
class t3lib_category_CategoryCollectionTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_category_Collection_CategoryCollection
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
	 * @var Tx_Phpunit_Framework
	 */
	private $testingFramework;

	/**
	 * @var t3lib_DB
	 */
	private $database;

	/**
	 * Sets up this test suite.
	 */
	public function setUp() {

		$this->database = $GLOBALS['TYPO3_DB'];

		$this->fixture = new t3lib_category_Collection_CategoryCollection($this->tableName);

		$this->collectionRecord = array(
			'uid' => 0,
			'title' => uniqid('title'),
			'description' => uniqid('description'),
			'table_name' => 'content'
		);

		$GLOBALS['TCA'][$this->tableName] = array('ctrl' => array());

		// prepare environment
		$this->createDummyTable();
		$this->testingFramework = new Tx_Phpunit_Framework('sys_category', array('tx_foo'));
		$this->populateDummyTable();
		$this->prepareTables();
		$this->makeRelationBetweenCategoryAndDummyTable();
	}

	/**
	 * Tears down this test suite.
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
	 * @expectedException RuntimeException
	 * @covers t3lib_category_Collection_CategoryCollection::__construct
	 */
	public function missingTableNameArgumentForObjectCategoryCollection() {
		new t3lib_category_Collection_CategoryCollection();
	}

	/**
	 * @test
	 * @covers t3lib_category_Collection_CategoryCollection::fromArray
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
	 * @covers t3lib_category_Collection_CategoryCollection::create
	 */
	public function canCreateDummyCollection() {
		$collection = t3lib_category_Collection_CategoryCollection::create($this->collectionRecord);
		$this->assertInstanceOf('t3lib_category_collection_categorycollection', $collection);
	}

	/**
	 * @test
	 * @covers t3lib_category_Collection_CategoryCollection::create
	 */
	public function canCreateDummyCollectionAndFillItems() {
		$collection = t3lib_category_Collection_CategoryCollection::create($this->collectionRecord, TRUE);
		$this->assertInstanceOf('t3lib_category_collection_categorycollection', $collection);
	}

	/**
	 * @test
	 * @covers t3lib_category_Collection_CategoryCollection::getCollectedRecords
	 */
	public function getCollectedRecordsReturnsEmptyRecordSet() {
		$method = new ReflectionMethod(
			't3lib_category_Collection_CategoryCollection', 'getCollectedRecords'
		);

		$method->setAccessible(TRUE);
		$records = $method->invoke($this->fixture);
		$this->assertInternalType('array', $records);
		$this->assertEmpty($records);
	}

	/**
	 * @test
	 * @covers t3lib_category_Collection_CategoryCollection::getStorageTableName
	 */
	public function isStorageTableNameEqualsToSysCategory() {
		$this->assertEquals('sys_category', t3lib_category_Collection_CategoryCollection::getStorageTableName());
	}

	/**
	 * @test
	 * @covers t3lib_category_Collection_CategoryCollection::getStorageItemsField
	 */
	public function isStorageItemsFieldEqualsToItems() {
		$this->assertEquals('items', t3lib_category_Collection_CategoryCollection::getStorageItemsField());
	}

	/**
	 * @test
	 */
	public function canLoadADummyCollectionFromDatabase() {

		/** @var $collection t3lib_category_Collection_CategoryCollection */
		$collection = t3lib_category_Collection_CategoryCollection::load($this->categoryUid, TRUE, $this->tableName);

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
	 */
	public function canLoadADummyCollectionFromDatabaseAndAddRecord() {
		$collection = t3lib_category_Collection_CategoryCollection::load($this->categoryUid, TRUE, $this->tableName);

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
	 */
	public function canLoadADummyCollectionWithoutContentFromDatabase() {

		/** @var $collection t3lib_category_Collection_CategoryCollection */
		$collection = t3lib_category_Collection_CategoryCollection::load($this->categoryUid, FALSE, $this->tableName);

		// Check the number of record
		$this->assertEquals(0, $collection->count());
	}

	/********************/
	/* INTERNAL METHODS */
	/********************/

	/**
	 * Create dummy table for testing purpose
	 */
	private function populateDummyTable() {
		$this->numberOfRecords = 5;
		for ($index = 1; $index <= $this->numberOfRecords; $index++) {
			$values = array(
				'title' => uniqid('title'),
			);
			$this->testingFramework->createRecord($this->tableName, $values);
		}
	}

	/**
	 * Make relation between tables
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
	 */
	private function createDummyTable() {
		$sql =<<<EOF
CREATE TABLE {$this->tableName} (
	uid int(11) auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
    title tinytext,
	categories int(11) unsigned DEFAULT '0' NOT NULL,
	sys_category_is_dummy_record int(11) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid)
);
EOF;
		$this->database->sql_query($sql);
	}

	/**
     * Drop dummy table
	 */
	private function dropDummyTable() {
		$sql = 'DROP TABLE ' . $this->tableName . ';';
		$this->database->sql_query($sql);
	}

	/**
	 * Add is_dummy_record record and create dummy record
	 */
	private function prepareTables() {
		$sql = 'ALTER TABLE %s ADD is_dummy_record tinyint(1) unsigned DEFAULT \'0\' NOT NULL';

		foreach ($this->tables as $table) {
			$_sql = sprintf($sql, $table);
			$this->database->sql_query($_sql);
		}

		$values = array(
			'title' => uniqid('title'),
			'is_dummy_record' => 1,
		);
		$this->categoryUid = $this->testingFramework->createRecord('sys_category', $values);
	}

	/**
	 * Remove dummy record and drop field
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