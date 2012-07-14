<?php

/**
 * Test case for t3lib_file_Collection_StaticFileCollectionTest
 *
 * @author Fabien Udriot <fabien.udriot@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_file_Collection_StaticFileCollectionTest extends Tx_Phpunit_TestCase {

	/**
	 * @var int
	 */
	private $mockCollectionUid = 0;

	/**
	 * @var int
	 */
	private $mockFileUid = 0;

	/**
	 * @var Tx_Phpunit_Framework
	 */
	private $testingFramework;

	/**
	 * @var array
	 */
	private $tables = array('sys_file_collection', 'sys_file', 'sys_file_reference');

	/**
	 * @var t3lib_DB
	 */
	protected $database;

	public function setUp() {
		$this->database = $GLOBALS['TYPO3_DB'];

		$this->testingFramework = new Tx_Phpunit_Framework('sys_file');
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();
		unset($this->testingFramework);
	}

	/**
	 * @test
	 */
	public function canLoadFileCollection() {

		$this->createDummyRecords();

		/** @var $collection t3lib_category_Collection_CategoryCollection */
		$collection = t3lib_file_Collection_StaticFileCollection::load($this->mockCollectionUid, TRUE);
		$this->assertEquals('1', $collection->count());

	}

	/**
	 * @test
	 */
	public function canCreateFileCollection() {
		$collectionRecord = array(
			'uid' => 0,
			'title' => uniqid('title'),
			'description' => uniqid('description'),
			'table_name' => 'items'
		);
		$collection = t3lib_file_Collection_StaticFileCollection::create($collectionRecord);
		$this->assertInstanceOf('t3lib_file_Collection_StaticFileCollection', $collection);
	}

	/**
	 * Create dummy records
	 */
	private function createDummyRecords() {

		// Creating a new file collection
		$values = array(
			'title' => uniqid('title'),
			'type' => 'static',
			'files' => '1',
		);
		$this->mockCollectionUid = $this->testingFramework->createRecord('sys_file_collection', $values);

		// Creating a new file record
		$values = array(
			'identifier' => uniqid('identifier'),
			'name' => uniqid('name'),
		);
		$this->mockFileUid = $this->testingFramework->createRecord('sys_file', $values);

		// Creating a new file reference
		$values = array(
			'uid_local' => $this->mockFileUid,
			'uid_foreign' => $this->mockCollectionUid,
			'tablenames' => 'sys_file_collection',
			'fieldname' => 'files'
		);
		$this->testingFramework->createRecord('sys_file_reference', $values);
	}
}

?>